<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Opportunity::query()->with(['lead:id,name,status', 'assignedUser:id,name']);

        if ($user->isSalesExecutive()) {
            $query->where('assigned_user_id', $user->id);
        }

        $opportunities = $query->orderByDesc('created_at')->paginate(20);
        $forecastRevenue = (float) $query->get()->sum(fn (Opportunity $opportunity) => ((float) $opportunity->estimated_value) * ((int) $opportunity->probability / 100));

        $stageCounts = Opportunity::query()
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');
        $leadOptions = Lead::query()
            ->when($user->isSalesExecutive(), fn ($q) => $q->where('assigned_user_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name']);
        $salesExecutives = User::query()->where('role', 'sales_executive')->orderBy('name')->get(['id', 'name']);

        return view('opportunities.index', [
            'opportunities' => $opportunities,
            'forecastRevenue' => $forecastRevenue,
            'stageCounts' => $stageCounts,
            'stages' => Opportunity::STAGES,
            'leadOptions' => $leadOptions,
            'salesExecutives' => $salesExecutives,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'stage' => ['required', Rule::in(Opportunity::STAGES)],
        ]);

        if ($user->isSalesExecutive()) {
            $validated['assigned_user_id'] = $user->id;
        }

        if (! empty($validated['lead_id'])) {
            $lead = Lead::query()->find($validated['lead_id']);

            if ($lead && $user->isSalesExecutive() && $lead->assigned_user_id !== $user->id) {
                abort(403, 'Not authorized to create opportunities for this lead.');
            }

            if (empty($validated['assigned_user_id'])) {
                $validated['assigned_user_id'] = $lead?->assigned_user_id;
            }
        }

        Opportunity::query()->create([
            'name' => $validated['name'],
            'lead_id' => $validated['lead_id'] ?? null,
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'estimated_value' => $validated['estimated_value'] ?? 0,
            'probability' => $validated['probability'] ?? 0,
            'expected_close_date' => $validated['expected_close_date'] ?? null,
            'stage' => $validated['stage'],
        ]);

        return redirect()->route('opportunities.index')->with('success', 'Opportunity created successfully.');
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
            return redirect()->route('opportunities.index')->with('success', 'Unable to read CSV file.');
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);

            return redirect()->route('opportunities.index')->with('success', 'CSV header is missing.');
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
                'name' => ['required', 'string', 'max:255'],
                'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
                'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
                'estimated_value' => ['nullable', 'numeric', 'min:0'],
                'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
                'expected_close_date' => ['nullable', 'date'],
                'stage' => ['nullable', Rule::in(Opportunity::STAGES)],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: ".collect($validator->errors()->all())->join(', ');
                continue;
            }

            $validated = $validator->validated();
            $lead = null;

            if (! empty($validated['lead_id'])) {
                $lead = Lead::query()->find($validated['lead_id']);
                if ($lead && $user->isSalesExecutive() && $lead->assigned_user_id !== $user->id) {
                    $errors[] = "Row {$rowNumber}: not authorized for lead #{$lead->id}.";
                    continue;
                }
            }

            if ($user->isSalesExecutive()) {
                $validated['assigned_user_id'] = $user->id;
            } elseif (empty($validated['assigned_user_id'])) {
                $validated['assigned_user_id'] = $lead?->assigned_user_id;
            }

            Opportunity::query()->create([
                'name' => $validated['name'],
                'lead_id' => $validated['lead_id'] ?? null,
                'assigned_user_id' => $validated['assigned_user_id'] ?? null,
                'estimated_value' => $validated['estimated_value'] ?? 0,
                'probability' => $validated['probability'] ?? 0,
                'expected_close_date' => $validated['expected_close_date'] ?? null,
                'stage' => $validated['stage'] ?? 'prospecting',
            ]);

            $imported++;
        }

        fclose($handle);

        if (! empty($errors)) {
            return redirect()
                ->route('opportunities.index')
                ->with('success', "Imported {$imported} opportunity(s). Some rows were skipped: ".implode(' | ', array_slice($errors, 0, 5)));
        }

        return redirect()->route('opportunities.index')->with('success', "Imported {$imported} opportunity(s) successfully.");
    }

    public function updateStage(Request $request, Opportunity $opportunity): JsonResponse
    {
        $request->validate([
            'stage' => ['required', 'in:prospecting,proposal,negotiation,closed_won,closed_lost'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        if ($user->isSalesExecutive() && $opportunity->assigned_user_id !== $user->id) {
            abort(403, 'Not authorized to update this opportunity.');
        }

        $opportunity->update([
            'stage' => $request->string('stage')->toString(),
            'probability' => $request->filled('probability') ? $request->integer('probability') : $opportunity->probability,
        ]);

        return response()->json(['message' => 'Opportunity updated.']);
    }
}
