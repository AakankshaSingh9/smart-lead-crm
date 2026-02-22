@extends('layouts.app')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-semibold">Follow-up Tracker</h2>
            <p class="text-sm text-slate-500">Track outreach dates, status, and notes</p>
        </div>
        <div class="flex items-center gap-3">
            <button id="openFollowupImportModal" type="button" class="flex items-center justify-center px-4 py-2 bg-white border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-all shadow-sm">
                <i class="ph ph-file-arrow-up mr-2"></i> Import
            </button>
            <button id="openFollowupCreateModal" type="button" class="flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-all shadow-md shadow-indigo-100">
                <i class="ph ph-plus mr-2"></i> Create
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 overflow-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2 pr-3">Lead</th>
                    <th class="py-2 pr-3">Assigned</th>
                    <th class="py-2 pr-3">Follow-up Date</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 pr-3">Notes</th>
                    <th class="py-2 pr-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($followUps as $followUp)
                    @php
                        $isOverdue = $followUp->status === 'pending' && $followUp->follow_up_date->isPast();
                    @endphp
                    <tr class="border-b">
                        <td class="py-2 pr-3">
                            <a href="{{ route('leads.show', $followUp->lead) }}" class="text-sky-700 hover:underline">{{ $followUp->lead->name }}</a>
                        </td>
                        <td class="py-2 pr-3">{{ $followUp->lead->assignedUser->name ?? '-' }}</td>
                        <td class="py-2 pr-3 {{ $isOverdue ? 'text-red-600 font-semibold' : '' }}">{{ $followUp->follow_up_date->format('Y-m-d') }}</td>
                        <td class="py-2 pr-3 capitalize">{{ $followUp->status }}</td>
                        <td class="py-2 pr-3">{{ $followUp->notes ?: '-' }}</td>
                        <td class="py-2 pr-3">
                            <select class="follow-up-status border rounded-md px-2 py-1" data-id="{{ $followUp->id }}">
                                <option value="pending" @selected($followUp->status === 'pending')>Pending</option>
                                <option value="completed" @selected($followUp->status === 'completed')>Completed</option>
                                <option value="missed" @selected($followUp->status === 'missed')>Missed</option>
                            </select>
                        </td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-slate-500" colspan="6">No follow-up records found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $followUps->links() }}
        </div>
    </div>

    <div id="followupCreateModal" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Create Follow-up</h3>
                <button type="button" class="btn-close-followup-modal text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form action="{{ route('followups.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label for="followupLeadId" class="block text-sm font-medium text-slate-700 mb-1">Lead</label>
                    <select id="followupLeadId" name="lead_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select lead</option>
                        @foreach ($leadOptions as $leadOption)
                            <option value="{{ $leadOption->id }}">{{ $leadOption->name }} (#{{ $leadOption->id }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="followupDate" class="block text-sm font-medium text-slate-700 mb-1">Follow-up Date</label>
                    <input id="followupDate" type="date" name="follow_up_date" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="followupStatus" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select id="followupStatus" name="status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        @foreach (\App\Models\FollowUp::STATUSES as $status)
                            <option value="{{ $status }}" @selected($status === 'pending')>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="followupNotes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                    <textarea id="followupNotes" name="notes" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-close-followup-modal px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="followupImportModal" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Import Follow-ups</h3>
                <button type="button" class="btn-close-followup-import text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form action="{{ route('followups.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <p class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg p-3">
                    CSV columns: <code>lead_id</code>, <code>follow_up_date</code>, <code>status</code> (optional), <code>notes</code> (optional).
                </p>
                <input id="followupCsvFile" type="file" name="file" accept=".csv,.txt" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-close-followup-import px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Import</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#openFollowupCreateModal').on('click', function () {
        $('#followupCreateModal').removeClass('hidden');
    });

    $('.btn-close-followup-modal').on('click', function () {
        $('#followupCreateModal').addClass('hidden');
    });

    $('#openFollowupImportModal').on('click', function () {
        $('#followupImportModal').removeClass('hidden');
    });

    $('.btn-close-followup-import').on('click', function () {
        $('#followupImportModal').addClass('hidden');
    });

    $('.follow-up-status').on('change', function () {
        const id = $(this).data('id');
        const status = $(this).val();

        $.ajax({
            url: `/follow-ups/${id}/status`,
            type: 'PATCH',
            data: {status}
        }).fail(function (xhr) {
            alert(xhr.responseJSON?.message || 'Unable to update status.');
        });
    });
</script>
@endpush
