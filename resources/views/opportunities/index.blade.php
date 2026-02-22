@extends('layouts.app')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-semibold">Opportunities</h2>
            <p class="text-sm text-slate-500">Monitor deal stages and expected revenue</p>
        </div>
        <div class="flex items-center gap-3">
            <button id="openOpportunityImportModal" type="button" class="flex items-center justify-center px-4 py-2 bg-white border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-all shadow-sm">
                <i class="ph ph-file-arrow-up mr-2"></i> Import
            </button>
            <button id="openOpportunityCreateModal" type="button" class="flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-all shadow-md shadow-indigo-100">
                <i class="ph ph-plus mr-2"></i> Create
            </button>
            <div class="text-sm bg-white border border-slate-200 rounded-md px-3 py-2">
                Revenue Forecast: <span class="font-semibold">₹{{ number_format($forecastRevenue, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
        @foreach ($stages as $stage)
            <div class="bg-white rounded-lg border border-slate-200 px-4 py-3">
                <p class="text-xs text-slate-500 uppercase">{{ str_replace('_', ' ', $stage) }}</p>
                <p class="text-xl font-semibold">{{ $stageCounts[$stage] ?? 0 }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow p-4 overflow-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="py-2 pr-3">Opportunity</th>
                    <th class="py-2 pr-3">Lead</th>
                    <th class="py-2 pr-3">Value</th>
                    <th class="py-2 pr-3">Probability</th>
                    <th class="py-2 pr-3">Expected Close</th>
                    <th class="py-2 pr-3">Stage</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($opportunities as $opportunity)
                    <tr class="border-b">
                        <td class="py-2 pr-3">{{ $opportunity->name }}</td>
                        <td class="py-2 pr-3">{{ $opportunity->lead->name ?? '-' }}</td>
                        <td class="py-2 pr-3">₹{{ number_format((float) $opportunity->estimated_value, 2) }}</td>
                        <td class="py-2 pr-3">{{ $opportunity->probability }}%</td>
                        <td class="py-2 pr-3">{{ optional($opportunity->expected_close_date)->format('Y-m-d') ?? '-' }}</td>
                        <td class="py-2 pr-3">
                            <select class="opp-stage border rounded-md px-2 py-1" data-id="{{ $opportunity->id }}">
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage }}" @selected($opportunity->stage === $stage)>{{ ucfirst(str_replace('_', ' ', $stage)) }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-slate-500" colspan="6">No opportunities found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $opportunities->links() }}
        </div>
    </div>

    <div id="opportunityCreateModal" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Create Opportunity</h3>
                <button type="button" class="btn-close-opportunity-modal text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form action="{{ route('opportunities.store') }}" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label for="opportunityName" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                    <input id="opportunityName" type="text" name="name" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="opportunityLeadId" class="block text-sm font-medium text-slate-700 mb-1">Lead</label>
                    <select id="opportunityLeadId" name="lead_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">None</option>
                        @foreach ($leadOptions as $leadOption)
                            <option value="{{ $leadOption->id }}">{{ $leadOption->name }} (#{{ $leadOption->id }})</option>
                        @endforeach
                    </select>
                </div>
                @if (auth()->user()->isAdmin())
                    <div>
                        <label for="opportunityAssignedUser" class="block text-sm font-medium text-slate-700 mb-1">Assigned To</label>
                        <select id="opportunityAssignedUser" name="assigned_user_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Auto / Unassigned</option>
                            @foreach ($salesExecutives as $salesExecutive)
                                <option value="{{ $salesExecutive->id }}">{{ $salesExecutive->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label for="opportunityValue" class="block text-sm font-medium text-slate-700 mb-1">Estimated Value</label>
                    <input id="opportunityValue" type="number" name="estimated_value" step="0.01" min="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="opportunityProbability" class="block text-sm font-medium text-slate-700 mb-1">Probability (%)</label>
                    <input id="opportunityProbability" type="number" name="probability" min="0" max="100" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="opportunityStage" class="block text-sm font-medium text-slate-700 mb-1">Stage</label>
                    <select id="opportunityStage" name="stage" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        @foreach ($stages as $stage)
                            <option value="{{ $stage }}" @selected($stage === 'prospecting')>{{ ucfirst(str_replace('_', ' ', $stage)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="opportunityCloseDate" class="block text-sm font-medium text-slate-700 mb-1">Expected Close Date</label>
                    <input id="opportunityCloseDate" type="date" name="expected_close_date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2 flex justify-end gap-3">
                    <button type="button" class="btn-close-opportunity-modal px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="opportunityImportModal" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Import Opportunities</h3>
                <button type="button" class="btn-close-opportunity-import text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form action="{{ route('opportunities.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <p class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg p-3">
                    CSV columns: <code>name</code>, <code>lead_id</code>, <code>assigned_user_id</code>, <code>estimated_value</code>, <code>probability</code>, <code>expected_close_date</code>, <code>stage</code>.
                </p>
                <input type="file" name="file" accept=".csv,.txt" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-close-opportunity-import px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Import</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#openOpportunityCreateModal').on('click', function () {
        $('#opportunityCreateModal').removeClass('hidden');
    });

    $('.btn-close-opportunity-modal').on('click', function () {
        $('#opportunityCreateModal').addClass('hidden');
    });

    $('#openOpportunityImportModal').on('click', function () {
        $('#opportunityImportModal').removeClass('hidden');
    });

    $('.btn-close-opportunity-import').on('click', function () {
        $('#opportunityImportModal').addClass('hidden');
    });

    $('.opp-stage').on('change', function () {
        const id = $(this).data('id');
        const stage = $(this).val();

        $.ajax({
            url: `/opportunities/${id}/stage`,
            type: 'PATCH',
            data: {stage}
        }).fail(function (xhr) {
            alert(xhr.responseJSON?.message || 'Unable to update opportunity stage.');
        });
    });
</script>
@endpush
