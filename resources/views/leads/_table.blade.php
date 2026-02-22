<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Source</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Assigned</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Follow-up</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-100">
            @forelse ($leads as $lead)
                @php
                    $isOverdue = $lead->follow_up_date && $lead->follow_up_date->lt($overdueThreshold) && !in_array($lead->status, ['converted', 'lost'], true);
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-medium">{{ $lead->name }}</p>
                        <p class="text-xs text-slate-500">{{ $lead->email ?? '-' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">{{ ucfirst($lead->status) }}</span>
                    </td>
                    <td class="px-4 py-3">{{ $lead->source ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $lead->assignedUser->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @if ($lead->follow_up_date)
                            <span class="{{ $isOverdue ? 'text-red-600 font-semibold' : '' }}">{{ $lead->follow_up_date->format('Y-m-d') }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 space-x-2 text-sm">
                        <a href="{{ route('leads.show', $lead) }}" class="text-sky-600 hover:underline">View</a>
                        <a href="{{ route('leads.edit', $lead) }}" class="text-amber-600 hover:underline">Edit</a>
                        @if (auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('leads.destroy', $lead) }}" class="inline" onsubmit="return confirm('Delete this lead?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">No leads found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $leads->links() }}
</div>
