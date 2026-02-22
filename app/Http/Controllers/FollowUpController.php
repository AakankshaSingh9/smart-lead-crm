<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFollowUpRequest;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadActivityService;
use App\Services\LeadScoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct(
        private readonly LeadActivityService $leadActivityService,
        private readonly LeadScoringService $leadScoringService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::today();

        FollowUp::query()
            ->where('status', 'pending')
            ->whereDate('follow_up_date', '<', $today)
            ->update(['status' => 'missed']);

        $query = FollowUp::query()->with(['lead.assignedUser', 'user']);

        if ($user->isSalesExecutive()) {
            $query->whereHas('lead', fn ($q) => $q->where('assigned_user_id', $user->id));
        }

        $followUps = $query->orderBy('follow_up_date')->paginate(20);
        $leadOptions = Lead::query()
            ->when($user->isSalesExecutive(), fn ($q) => $q->where('assigned_user_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('followups.index', compact('followUps', 'leadOptions'));
    }

    public function store(StoreFollowUpRequest $request, Lead $lead): JsonResponse
    {
        $user = $request->user();

        if ($user->isSalesExecutive() && $lead->assigned_user_id !== $user->id) {
            abort(403, 'You are not authorized to add follow-ups for this lead.');
        }

        $data = $request->validated();

        $followUp = $this->createFollowUp($lead, $data, $user);

        $followUp->load('user');

        return response()->json([
            'message' => 'Follow-up added successfully.',
            'item' => [
                'id' => $followUp->id,
                'date' => $followUp->follow_up_date->format('Y-m-d'),
                'user' => $followUp->user->name,
                'status' => $followUp->status,
                'notes' => $followUp->notes,
            ],
        ]);
    }

    public function storeFromIndex(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_id' => ['required', 'integer', 'exists:leads,id'],
            'follow_up_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(FollowUp::STATUSES)],
        ]);

        $user = $request->user();
        $lead = Lead::query()->findOrFail($validated['lead_id']);

        if ($user->isSalesExecutive() && $lead->assigned_user_id !== $user->id) {
            abort(403, 'You are not authorized to add follow-ups for this lead.');
        }

        $this->createFollowUp($lead, $validated, $user);

        return redirect()->route('followups.index')->with('success', 'Follow-up created successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return redirect()->route('followups.index')->with('success', 'Unable to read CSV file.');
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);

            return redirect()->route('followups.index')->with('success', 'CSV header is missing.');
        }

        $normalizedHeader = collect($header)
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->values()
            ->all();

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $payload = array_combine($normalizedHeader, array_pad($row, count($normalizedHeader), null));
            if (! is_array($payload)) {
                $errors[] = "Row {$rowNumber}: invalid column mapping.";
                continue;
            }

            $validator = Validator::make($payload, [
                'lead_id' => ['required', 'integer', 'exists:leads,id'],
                'follow_up_date' => ['required', 'date'],
                'notes' => ['nullable', 'string'],
                'status' => ['nullable', Rule::in(FollowUp::STATUSES)],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: ".collect($validator->errors()->all())->join(', ');
                continue;
            }

            $validated = $validator->validated();
            $lead = Lead::query()->find($validated['lead_id']);

            if (! $lead) {
                $errors[] = "Row {$rowNumber}: lead not found.";
                continue;
            }

            if ($user->isSalesExecutive() && $lead->assigned_user_id !== $user->id) {
                $errors[] = "Row {$rowNumber}: you are not allowed to import follow-ups for lead #{$lead->id}.";
                continue;
            }

            $this->createFollowUp($lead, $validated, $user);
            $imported++;
        }

        fclose($handle);

        if (! empty($errors)) {
            return redirect()
                ->route('followups.index')
                ->with('success', "Imported {$imported} follow-up(s). Some rows were skipped: ".implode(' | ', array_slice($errors, 0, 5)));
        }

        return redirect()->route('followups.index')->with('success', "Imported {$imported} follow-up(s) successfully.");
    }

    public function updateStatus(Request $request, FollowUp $followUp): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,completed,missed'],
        ]);

        $user = $request->user();
        $lead = $followUp->lead;

        if ($user->isSalesExecutive() && $lead->assigned_user_id !== $user->id) {
            abort(403, 'You are not authorized to update this follow-up.');
        }

        DB::transaction(function () use ($followUp, $request, $lead, $user): void {
            $status = $request->string('status')->toString();

            $followUp->update([
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : null,
            ]);

            $lead->refresh();
            $lead->update($this->leadScoringService->calculate($lead));

            $this->leadActivityService->log($lead, $user, 'follow_up_status_updated', 'Follow-up status updated.', [
                'follow_up_id' => $followUp->id,
                'status' => $status,
            ]);
        });

        return response()->json([
            'message' => 'Follow-up status updated.',
        ]);
    }

    private function createFollowUp(Lead $lead, array $data, User $user): FollowUp
    {
        return DB::transaction(function () use ($lead, $data, $user) {
            $followUp = $lead->followUps()->create([
                'user_id' => $user->id,
                'follow_up_date' => $data['follow_up_date'],
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'completed_at' => ($data['status'] ?? 'pending') === 'completed' ? now() : null,
            ]);

            $lead->update(['follow_up_date' => $data['follow_up_date']]);
            $lead->refresh();
            $lead->update($this->leadScoringService->calculate($lead));

            $this->leadActivityService->log($lead, $user, 'follow_up_added', 'A new follow-up was scheduled.', [
                'follow_up_id' => $followUp->id,
                'follow_up_date' => $followUp->follow_up_date->format('Y-m-d'),
            ]);

            return $followUp;
        });
    }
}
