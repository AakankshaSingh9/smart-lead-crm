<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportLeadsRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadActivityService;
use App\Services\LeadImportService;
use App\Services\LeadScoringService;
use App\Services\NotificationService;
use App\Services\OpportunityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadScoringService $leadScoringService,
        private readonly LeadActivityService $leadActivityService,
        private readonly OpportunityService $opportunityService,
        private readonly NotificationService $notificationService,
        private readonly LeadImportService $leadImportService,
    ) {
    }

    public function index(Request $request): View
    {
        $salesExecutives = User::query()->where('role', 'sales_executive')->orderBy('name')->get(['id', 'name']);
        $sources = Lead::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source')->values();

        return view('leads.index', compact('salesExecutives', 'sources'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $user = $request->user();

        $baseQuery = Lead::query()->with('assignedUser:id,name');

        if ($user->isSalesExecutive()) {
            $baseQuery->where('assigned_user_id', $user->id);
        }

        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->when($user->isAdmin() && $request->filled('assigned_user_id'), fn ($q) => $q->where('assigned_user_id', $request->integer('assigned_user_id')))
            ->when($request->filled('score_band'), fn ($q) => $q->where('score_band', $request->string('score_band')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_to')));

        $search = trim((string) data_get($request->input('search'), 'value', ''));
        if ($search !== '') {
            $filteredQuery->where(function ($nested) use ($search) {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $filteredQuery)->count();

        $columns = [
            0 => 'name',
            1 => 'status',
            2 => 'source',
            3 => 'assigned_user_id',
            4 => 'follow_up_date',
            5 => 'score',
            6 => 'created_at',
        ];

        $orderColumnIndex = (int) data_get($request->input('order'), '0.column', 6);
        $orderDir = strtolower((string) data_get($request->input('order'), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';

        $start = max(0, (int) $request->integer('start', 0));
        $length = max(10, min(200, (int) $request->integer('length', 10)));

        $leads = $filteredQuery
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $today = Carbon::today();

        $data = $leads->map(function (Lead $lead) use ($today, $request) {
            $isOverdue = $lead->follow_up_date && $lead->follow_up_date->lt($today) && ! in_array($lead->status, ['converted', 'lost'], true);

            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'status' => ucfirst($lead->status),
                'source' => $lead->source ?: '-',
                'assigned' => $lead->assignedUser?->name ?: '-',
                'follow_up_date' => $lead->follow_up_date?->format('Y-m-d') ?: '-',
                'score' => $lead->score,
                'score_band' => ucfirst($lead->score_band),
                'is_overdue' => $isOverdue,
                'created_at' => optional($lead->created_at)->format('Y-m-d'),
                'actions' => [
                    'show' => route('leads.show', $lead),
                    'edit' => route('leads.edit', $lead),
                    'delete' => route('leads.destroy', $lead),
                    'can_delete' => $request->user()->isAdmin(),
                ],
            ];
        });

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create(): View
    {
        $salesExecutives = User::query()->where('role', 'sales_executive')->orderBy('name')->get();

        return view('leads.form', [
            'lead' => new Lead(),
            'salesExecutives' => $salesExecutives,
            'statuses' => Lead::STATUSES,
            'action' => route('leads.store'),
            'method' => 'POST',
            'title' => 'Create Lead',
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse|JsonResponse
    {
        $actor = $request->user();
        $data = $request->validated();

        if ($actor->isSalesExecutive()) {
            $data['assigned_user_id'] = $actor->id;
        }

        $lead = DB::transaction(function () use ($data, $actor) {
            $lead = Lead::query()->create($data);
            $this->applyLeadAutomation($lead, $actor, 'lead_created', 'Lead created manually.');

            return $lead;
        });

        if ($request->ajax()) {
            return response()->json([
                'message' => 'Lead created successfully.',
                'lead_id' => $lead->id,
            ], 201);
        }

        return redirect()->route('leads.index')->with('success', 'Lead created successfully.');
    }

    public function import(ImportLeadsRequest $request): JsonResponse
    {
        try {
            $result = $this->leadImportService->import($request->file('file'), $request->user());
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'imported' => 0,
                'errors' => collect($exception->errors())->flatten()->values(),
            ], 422);
        }

        if (! $result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    public function show(Lead $lead): View
    {
        $this->authorizeLead($lead);

        $lead->load([
            'assignedUser',
            'followUps.user',
            'activities.user',
            'opportunity',
        ]);

        return view('leads.show', compact('lead'));
    }

    public function edit(Lead $lead): View
    {
        $this->authorizeLead($lead);

        $salesExecutives = User::query()->where('role', 'sales_executive')->orderBy('name')->get();

        return view('leads.form', [
            'lead' => $lead,
            'salesExecutives' => $salesExecutives,
            'statuses' => Lead::STATUSES,
            'action' => route('leads.update', $lead),
            'method' => 'PUT',
            'title' => 'Edit Lead',
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $this->authorizeLead($lead);

        $actor = $request->user();
        $data = $request->validated();

        if ($actor->isSalesExecutive()) {
            $data['assigned_user_id'] = $actor->id;
        }

        DB::transaction(function () use ($lead, $data, $actor) {
            $originalStatus = $lead->status;
            $originalAssignee = $lead->assigned_user_id;

            $lead->update($data);

            $this->applyLeadAutomation($lead, $actor, 'lead_updated', 'Lead details updated.');

            if ($originalAssignee !== $lead->assigned_user_id && $lead->assignedUser) {
                $this->notificationService->notifyLeadAssigned($lead, $lead->assignedUser, $actor);
            }

            if ($originalStatus !== 'converted' && $lead->status === 'converted') {
                $opportunity = $this->opportunityService->createFromLead($lead, $actor);
                $this->leadActivityService->log($lead, $actor, 'lead_converted', 'Lead converted and opportunity created.', [
                    'opportunity_id' => $opportunity->id,
                ]);
                $this->notificationService->notifyLeadConverted($lead, $actor);
                $this->notificationService->notifyOpportunityCreated($opportunity, $actor);
            }
        });

        if ($request->ajax()) {
            return response()->json(['message' => 'Lead updated successfully.']);
        }

        return redirect()->route('leads.show', $lead)->with('success', 'Lead updated successfully.');
    }

    public function destroy(Request $request, Lead $lead): RedirectResponse|JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Only admin can delete leads.');
        }

        DB::transaction(function () use ($lead, $request): void {
            $this->leadActivityService->log($lead, $request->user(), 'lead_deleted', 'Lead deleted by admin.');
            $lead->delete();
        });

        if ($request->ajax()) {
            return response()->json(['message' => 'Lead deleted successfully.']);
        }

        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }

    private function authorizeLead(Lead $lead): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($lead->assigned_user_id !== $user->id) {
            abort(403, 'You are not authorized to access this lead.');
        }
    }

    private function applyLeadAutomation(Lead $lead, User $actor, string $activityType, string $activityMessage): void
    {
        $lead->loadMissing('followUps', 'assignedUser');
        $lead->update($this->leadScoringService->calculate($lead));

        $this->leadActivityService->log($lead, $actor, $activityType, $activityMessage, [
            'status' => $lead->status,
            'assigned_user_id' => $lead->assigned_user_id,
            'score' => $lead->score,
            'score_band' => $lead->score_band,
        ]);
    }
}
