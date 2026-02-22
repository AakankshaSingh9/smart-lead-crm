@extends('layouts.app')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Lead Management</h2>
            <p class="text-sm text-slate-500">Track, filter, and manage your sales pipeline</p>
        </div>
        <div class="flex items-center gap-3">
            <button id="openImportModal" class="flex items-center justify-center px-4 py-2 bg-white border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-all shadow-sm">
                <i class="ph ph-file-arrow-up mr-2"></i> Import
            </button>
            <a href="{{ route('leads.create') }}" class="flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-all shadow-md shadow-indigo-100">
                <i class="ph ph-plus mr-2"></i> Create
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
        <div class="flex items-center mb-4 text-slate-700 font-semibold text-sm">
            <i class="ph ph-funnel mr-2"></i> Quick Filters
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="lg:col-span-1">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="ph ph-magnifying-glass"></i>
                    </span>
                    <input type="text" id="filterSearch" placeholder="Search leads..." class="block w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
            </div>

            <select id="filterStatus" class="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2">
                <option value="">All Statuses</option>
                @foreach (\App\Models\Lead::STATUSES as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <select id="filterScoreBand" class="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2">
                <option value="">All Scores</option>
                @foreach (\App\Models\Lead::SCORE_BANDS as $band)
                    <option value="{{ $band }}">{{ ucfirst($band) }}</option>
                @endforeach
            </select>

            @if (auth()->user()->isAdmin())
                <select id="filterAssigned" class="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2">
                    <option value="">All Owners</option>
                    @foreach ($salesExecutives as $sales)
                        <option value="{{ $sales->id }}">{{ $sales->name }}</option>
                    @endforeach
                </select>
            @endif

            <div class="lg:col-span-2 flex items-center gap-2">
                <input type="date" id="filterDateFrom" class="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg block w-full p-2" title="From">
                <span class="text-slate-400 text-xs font-bold">TO</span>
                <input type="date" id="filterDateTo" class="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg block w-full p-2" title="To">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table id="leadsTable" class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Lead Contact</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Source</th>
                        <th class="px-6 py-4">Assigned To</th>
                        <th class="px-6 py-4">Next Step</th>
                        <th class="px-6 py-4 text-center">Engagement</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    </tbody>
            </table>
        </div>
    </div>

    <div id="importModal" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform transition-all">
            <div class="px-6 py-4 bg-slate-50 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Bulk Import Leads</h3>
                <button id="closeImportModal" class="text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>

            <div class="p-6">
                <div class="mb-4 p-3 bg-indigo-50 border border-indigo-100 rounded-lg">
                    <p class="text-xs text-indigo-700 leading-relaxed">
                        <span class="font-bold">Formatting Tip:</span> Use CSV format with columns: <code class="bg-indigo-100 px-1 rounded">name</code>, <code class="bg-indigo-100 px-1 rounded">email</code>, <code class="bg-indigo-100 px-1 rounded">source</code>, etc.
                    </p>
                </div>

                <form id="importForm" enctype="multipart/form-data" class="space-y-5">
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center hover:border-indigo-400 transition-colors cursor-pointer group">
                        <input type="file" name="file" id="csvFile" accept=".csv" required class="hidden">
                        <label for="csvFile" class="cursor-pointer">
                            <i class="ph ph-cloud-arrow-up text-4xl text-slate-300 group-hover:text-indigo-500 transition-colors"></i>
                            <p class="mt-2 text-sm text-slate-600" id="fileNameDisplay">Click to upload or drag and drop CSV</p>
                        </label>
                    </div>

                    <div id="importResult" class="empty:hidden rounded-lg p-3"></div>

                    <div class="flex gap-3 justify-end">
                        <button type="button" class="btn-close-modal px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Cancel</button>
                        <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-md">Start Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Styling constants for DataTables
    const statusBadges = {
        'new': 'bg-blue-100 text-blue-700 border-blue-200',
        'contacted': 'bg-amber-100 text-amber-700 border-amber-200',
        'qualified': 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'lost': 'bg-slate-100 text-slate-600 border-slate-200',
    };

    const table = $('#leadsTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "{{ route('leads.datatable') }}",
            data: (d) => {
                d.status = $('#filterStatus').val();
                d.score_band = $('#filterScoreBand').val();
                d.assigned_user_id = $('#filterAssigned').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
                d.search.value = $('#filterSearch').val();
            }
        },
        columnDefs: [
            { targets: '_all', className: 'px-6 py-4 align-middle' }
        ],
        columns: [
            {
                data: 'name',
                render: function (data, type, row) {
                    return `
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center mr-3 text-slate-500 font-bold text-xs uppercase">
                                ${data.charAt(0)}
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800">${data}</div>
                                <div class="text-[11px] text-slate-400 font-medium">${row.email || 'No email'}</div>
                            </div>
                        </div>`;
                }
            },
            {
                data: 'status',
                render: function(data) {
                    const style = statusBadges[data.toLowerCase()] || 'bg-slate-100 text-slate-600';
                    return `<span class="px-2 py-1 rounded-full border ${style} text-[10px] font-bold uppercase tracking-tight">${data}</span>`;
                }
            },
            { data: 'source' },
            {
                data: 'assigned',
                render: (data) => `<span class="text-slate-600 italic font-medium">${data || 'Unassigned'}</span>`
            },
            {
                data: 'follow_up_date',
                render: function (data, type, row) {
                    if (data === '-') return '<span class="text-slate-300">N/A</span>';
                    const colorClass = row.is_overdue ? 'text-red-500' : 'text-slate-600';
                    return `<div class="flex items-center ${colorClass} font-medium">
                                <i class="ph ph-clock mr-1.5"></i> ${data}
                            </div>`;
                }
            },
            {
                data: 'score',
                className: 'text-center',
                render: function (data, type, row) {
                    const bandColor = row.score_band === 'hot' ? 'bg-red-500' : (row.score_band === 'warm' ? 'bg-amber-500' : 'bg-blue-500');
                    return `<div class="flex flex-col items-center">
                                <div class="w-16 bg-slate-100 rounded-full h-1.5 mb-1">
                                    <div class="${bandColor} h-1.5 rounded-full" style="width: ${data}%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 uppercase">${row.score_band} (${data})</span>
                            </div>`;
                }
            },
            {
                data: 'actions',
                orderable: false,
                className: 'text-right',
                render: function (actions) {
                    return `
                    <div class="flex items-center justify-end gap-2">
                        <a href="${actions.show}" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="View">
                            <i class="ph-bold ph-eye text-lg"></i>
                        </a>
                        <a href="${actions.edit}" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                            <i class="ph-bold ph-pencil-simple text-lg"></i>
                        </a>
                    </div>`;
                }
            }
        ]
    });

    // Modal and File Logic
    $('#csvFile').on('change', function() {
        $('#fileNameDisplay').text(this.files[0].name).addClass('text-indigo-600 font-bold');
    });

    $('.btn-close-modal, #closeImportModal').on('click', function() {
        $('#importModal').addClass('hidden');
    });

    $('#openImportModal').on('click', () => $('#importModal').removeClass('hidden'));

    $('#filterSearch, #filterStatus, #filterScoreBand, #filterAssigned, #filterDateFrom, #filterDateTo').on('input change', function () {
        table.ajax.reload();
    });
</script>
@endpush
