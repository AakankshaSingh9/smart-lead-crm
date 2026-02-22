@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
        <h2 class="text-2xl font-semibold">Lead Details</h2>
        <div class="space-x-2">
            <a href="{{ route('leads.edit', $lead) }}" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-md">Edit</a>
            <a href="{{ route('leads.index') }}" class="bg-slate-200 hover:bg-slate-300 px-4 py-2 rounded-md">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-lg shadow p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Name</dt><dd class="font-medium">{{ $lead->name }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ ucfirst($lead->status) }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd>{{ $lead->email ?? '-' }}</dd></div>
                <div><dt class="text-slate-500">Phone</dt><dd>{{ $lead->phone ?? '-' }}</dd></div>
                <div><dt class="text-slate-500">Source</dt><dd>{{ $lead->source ?? '-' }}</dd></div>
                <div><dt class="text-slate-500">Assigned To</dt><dd>{{ $lead->assignedUser->name ?? '-' }}</dd></div>
                <div><dt class="text-slate-500">Next Follow-up</dt><dd>{{ optional($lead->follow_up_date)->format('Y-m-d') ?? '-' }}</dd></div>
                <div><dt class="text-slate-500">Score</dt><dd><span class="font-semibold">{{ $lead->score }}</span> ({{ ucfirst($lead->score_band) }})</dd></div>
            </dl>

            <div class="mt-4 border-t pt-4">
                <h3 class="font-medium mb-2">Notes</h3>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $lead->notes ?: 'No notes available.' }}</p>
            </div>

            @if ($lead->opportunity)
                <div class="mt-4 border-t pt-4 text-sm">
                    <h3 class="font-medium mb-1">Linked Opportunity</h3>
                    <p>{{ $lead->opportunity->name }} • Stage: {{ ucfirst(str_replace('_', ' ', $lead->opportunity->stage)) }}</p>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-3">Add Follow-up</h3>
            <form id="followUpForm" class="space-y-3">
                <div>
                    <label class="text-sm font-medium">Date *</label>
                    <input type="date" name="follow_up_date" class="mt-1 w-full rounded-md border-slate-300" required>
                </div>
                <div>
                    <label class="text-sm font-medium">Status *</label>
                    <select name="status" class="mt-1 w-full rounded-md border-slate-300">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="missed">Missed</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Notes</label>
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-md border-slate-300"></textarea>
                </div>
                <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white py-2 rounded-md">Save Follow-up</button>
                <p id="followUpMessage" class="text-sm"></p>
            </form>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold mb-3">Follow-up History</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2 pr-3">Date</th>
                        <th class="py-2 pr-3">Added By</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Notes</th>
                    </tr>
                </thead>
                <tbody id="followUpRows">
                    @forelse ($lead->followUps->sortByDesc('follow_up_date') as $followUp)
                        <tr class="border-b">
                            <td class="py-2 pr-3">{{ $followUp->follow_up_date->format('Y-m-d') }}</td>
                            <td class="py-2 pr-3">{{ $followUp->user->name }}</td>
                            <td class="py-2 pr-3 capitalize">{{ $followUp->status }}</td>
                            <td class="py-2 pr-3">{{ $followUp->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr id="followUpEmptyRow"><td colspan="4" class="py-3 text-slate-500">No follow-ups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold mb-3">Lead Timeline</h3>
        <div class="space-y-3">
            @forelse ($lead->activities->sortByDesc('created_at') as $activity)
                <div class="border border-slate-200 rounded-md px-4 py-3">
                    <p class="text-sm font-medium text-slate-900">{{ $activity->description }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ optional($activity->created_at)->format('Y-m-d H:i') }}
                        @if ($activity->user)
                            • {{ $activity->user->name }}
                        @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No activity logged yet.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#followUpForm').on('submit', function (e) {
        e.preventDefault();

        $.post("{{ route('leads.followups.store', $lead) }}", $(this).serialize())
            .done(function (response) {
                $('#followUpMessage').removeClass('text-red-600').addClass('text-emerald-600').text(response.message);
                $('#followUpEmptyRow').remove();
                $('#followUpRows').prepend(
                    `<tr class="border-b">
                        <td class="py-2 pr-3">${response.item.date}</td>
                        <td class="py-2 pr-3">${response.item.user}</td>
                        <td class="py-2 pr-3 capitalize">${response.item.status}</td>
                        <td class="py-2 pr-3">${response.item.notes ?? '-'}</td>
                    </tr>`
                );
                $('#followUpForm')[0].reset();
            })
            .fail(function (xhr) {
                const message = xhr.responseJSON?.message || 'Unable to add follow-up.';
                $('#followUpMessage').removeClass('text-emerald-600').addClass('text-red-600').text(message);
            });
    });
</script>
@endpush
