@extends('layouts.app')

@section('content')
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-cyan-600 via-sky-700 to-blue-900 p-6 md:p-8 text-white mb-6">
        <div class="absolute -top-20 -right-20 h-56 w-56 rounded-full bg-cyan-300/20 blur-2xl"></div>
        <div class="absolute -bottom-20 -left-20 h-56 w-56 rounded-full bg-blue-200/20 blur-2xl"></div>
        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs tracking-[0.24em] uppercase text-cyan-100/80">Revenue Intelligence</p>
                <h2 class="text-2xl md:text-3xl font-semibold mt-1">AI Lead Closure Dashboard</h2>
                <p class="text-sm text-cyan-100/85 mt-2">Click any metric card to filter insights and charts instantly.</p>
            </div>
            <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur px-4 py-3">
                <p class="text-xs uppercase tracking-widest text-cyan-100">Average AI Closure Score</p>
                <p class="text-3xl font-bold">{{ number_format($avgPredictionScore, 1) }}%</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4 mb-6" id="metricCards">
        <button type="button" data-filter="all" class="metric-card rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md hover:-translate-y-0.5 active">
            <p class="text-xs uppercase tracking-wider text-slate-500">Total Leads</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totalLeads }}</p>
            <p class="text-xs text-slate-500 mt-2">All active records</p>
        </button>
        <button type="button" data-filter="converted" class="metric-card rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-wider text-slate-500">Converted</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $convertedLeads }}</p>
            <p class="text-xs text-slate-500 mt-2">Conversion: {{ number_format($conversionRatio, 2) }}%</p>
        </button>
        <button type="button" data-filter="predicted_high" class="metric-card rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-wider text-slate-500">AI High Confidence</p>
            <p class="mt-2 text-2xl font-semibold text-sky-600">{{ $highConfidenceClosures }}</p>
            <p class="text-xs text-slate-500 mt-2">Likely to close soon</p>
        </button>
        <button type="button" data-filter="needs_attention" class="metric-card rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-wider text-slate-500">Needs Attention</p>
            <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $needsAttentionCount }}</p>
            <p class="text-xs text-slate-500 mt-2">At risk opportunities</p>
        </button>
        <button type="button" data-filter="today_followup" class="metric-card rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-wider text-slate-500">Follow-ups Today</p>
            <p class="mt-2 text-2xl font-semibold text-violet-600">{{ $todayFollowUps }}</p>
            <p class="text-xs text-slate-500 mt-2">Upcoming: {{ $upcomingFollowUps }}</p>
        </button>
        <button type="button" data-filter="overdue" class="metric-card rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
            <p class="text-xs uppercase tracking-wider text-slate-500">Overdue Follow-ups</p>
            <p class="mt-2 text-2xl font-semibold text-red-600">{{ $overdueFollowUps }}</p>
            <p class="text-xs text-slate-500 mt-2">Urgent action needed</p>
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-slate-500">Qualified Leads</p>
            <p class="mt-2 text-2xl font-semibold text-indigo-600">{{ $qualifiedLeads }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-slate-500">Total Opportunity Value</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">₹{{ number_format($totalOpportunityValue, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-slate-500">Revenue Forecast</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-600">₹{{ number_format($forecastRevenue, 2) }}</p>
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
            <h3 class="font-semibold mb-2">Broadcast Notification</h3>
            <form id="broadcastForm" class="flex flex-col md:flex-row gap-2">
                <input type="text" name="message" maxlength="500" required placeholder="Send a notification to all users" class="flex-1 rounded-md border-slate-300">
                <button class="bg-slate-900 text-white px-4 py-2 rounded-md">Send</button>
            </form>
            <p id="broadcastMessage" class="text-sm mt-2"></p>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h3 class="font-semibold mb-1">Monthly Lead Trend</h3>
            <p class="text-xs text-slate-500 mb-3">Lead volume over the last 12 months</p>
            <canvas id="monthlyLeadChart" height="140"></canvas>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold mb-1">Status Distribution</h3>
            <p class="text-xs text-slate-500 mb-3">Auto-refreshes when you click metric cards</p>
            <canvas id="statusChart" height="140"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h3 class="font-semibold mb-1">AI Prediction Buckets</h3>
            <p class="text-xs text-slate-500 mb-3">Risk and closure likelihood distribution</p>
            <canvas id="predictionChart" height="130"></canvas>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold mb-1">Top AI Recommendations</h3>
            <p class="text-xs text-slate-500 mb-3">Highest probability open leads</p>
            <div class="space-y-3 max-h-[320px] overflow-auto pr-1">
                @forelse ($topPredictedLeads as $lead)
                    <a href="{{ $lead['url'] }}" class="block rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 p-3 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $lead['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $lead['source'] }} • {{ ucfirst(str_replace('_', ' ', $lead['status'])) }}</p>
                            </div>
                            <span class="rounded-full bg-sky-100 text-sky-700 text-xs px-2 py-1 font-semibold">{{ $lead['prediction_score'] }}%</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-600">{{ $lead['recommended_action'] }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No prediction data available.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                <div>
                    <h3 class="font-semibold">Interactive Lead Insights</h3>
                    <p class="text-xs text-slate-500">Filtered leads update based on selected KPI card</p>
                </div>
                <div class="text-xs rounded-full bg-slate-100 px-3 py-1 w-fit text-slate-700">
                    Active Filter: <span class="font-semibold" id="activeFilterLabel">All Leads</span> • <span id="activeFilterCount">{{ $totalLeads }}</span> leads
                </div>
            </div>

            <div class="overflow-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Lead</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Follow-up</th>
                            <th class="px-4 py-3">AI Score</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="insightRows" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold mb-1">Source Conversion Rate</h3>
            <p class="text-xs text-slate-500 mb-3">Converted vs total leads per source</p>
            <canvas id="sourceChart" height="250"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const dashboardLeads = @json($dashboardLeadRows);
    const today = "{{ now()->format('Y-m-d') }}";
    const filterLabels = {
        all: 'All Leads',
        converted: 'Converted Leads',
        predicted_high: 'AI High Confidence',
        needs_attention: 'Needs Attention',
        today_followup: 'Follow-ups Today',
        overdue: 'Overdue Follow-ups',
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function isOpenLead(lead) {
        return lead.status !== 'converted' && lead.status !== 'lost';
    }

    function getFilteredLeads(filterKey) {
        return dashboardLeads.filter((lead) => {
            if (filterKey === 'converted') {
                return lead.status === 'converted';
            }
            if (filterKey === 'predicted_high') {
                return isOpenLead(lead) && Number(lead.prediction_score) >= 80;
            }
            if (filterKey === 'needs_attention') {
                return isOpenLead(lead) && Number(lead.prediction_score) < 45;
            }
            if (filterKey === 'today_followup') {
                return lead.follow_up_date === today;
            }
            if (filterKey === 'overdue') {
                return !!lead.follow_up_date && lead.follow_up_date < today && isOpenLead(lead);
            }
            return true;
        });
    }

    function scoreClass(score) {
        if (score >= 80) return 'bg-emerald-100 text-emerald-700';
        if (score >= 70) return 'bg-sky-100 text-sky-700';
        if (score >= 45) return 'bg-amber-100 text-amber-700';
        return 'bg-rose-100 text-rose-700';
    }

    function renderInsightRows(leads) {
        const rows = leads.slice(0, 40).map((lead) => `
            <tr class="hover:bg-slate-50 transition">
                <td class="px-4 py-3">
                    <a href="${escapeHtml(lead.url)}" class="font-medium text-slate-900 hover:text-sky-700">${escapeHtml(lead.name)}</a>
                    <p class="text-xs text-slate-500">${escapeHtml(lead.source)} • ${escapeHtml(lead.assigned_to)}</p>
                </td>
                <td class="px-4 py-3 text-slate-700 capitalize">${escapeHtml(lead.status)}</td>
                <td class="px-4 py-3 text-slate-700">${escapeHtml(lead.follow_up_date || '-')}</td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold ${scoreClass(Number(lead.prediction_score))}">
                        ${escapeHtml(lead.prediction_score)}%
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">${escapeHtml(lead.recommended_action)}</td>
            </tr>
        `);

        const fallback = `
            <tr>
                <td class="px-4 py-5 text-center text-slate-500" colspan="5">No leads match this filter.</td>
            </tr>
        `;

        document.getElementById('insightRows').innerHTML = rows.length ? rows.join('') : fallback;
    }

    function buildStatusData(leads) {
        const statuses = ['new', 'contacted', 'qualified', 'interested', 'converted', 'lost'];
        const counts = statuses.map((status) => leads.filter((lead) => lead.status === status).length);
        return { labels: statuses.map((value) => value.charAt(0).toUpperCase() + value.slice(1)), counts };
    }

    function buildPredictionData(leads) {
        return {
            labels: ['At Risk', 'Moderate', 'Likely', 'High'],
            counts: [
                leads.filter((lead) => Number(lead.prediction_score) < 45).length,
                leads.filter((lead) => Number(lead.prediction_score) >= 45 && Number(lead.prediction_score) < 70).length,
                leads.filter((lead) => Number(lead.prediction_score) >= 70 && Number(lead.prediction_score) < 80).length,
                leads.filter((lead) => Number(lead.prediction_score) >= 80).length,
            ],
        };
    }

    function buildSourceData(leads) {
        const grouped = {};
        leads.forEach((lead) => {
            const key = lead.source || 'Unknown';
            if (!grouped[key]) {
                grouped[key] = { total: 0, converted: 0 };
            }
            grouped[key].total += 1;
            if (lead.status === 'converted') {
                grouped[key].converted += 1;
            }
        });

        const entries = Object.entries(grouped)
            .map(([source, values]) => ({
                source,
                rate: values.total > 0 ? Number(((values.converted / values.total) * 100).toFixed(1)) : 0,
            }))
            .sort((a, b) => b.rate - a.rate)
            .slice(0, 8);

        return {
            labels: entries.map((item) => item.source),
            rates: entries.map((item) => item.rate),
        };
    }

    const monthlyChart = new Chart(document.getElementById('monthlyLeadChart'), {
        type: 'line',
        data: {
            labels: @json($monthlyLabels),
            datasets: [{
                label: 'Leads',
                data: @json($monthlyValues),
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14,165,233,0.2)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    const statusData = buildStatusData(dashboardLeads);
    const statusChart = new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusData.labels,
            datasets: [{
                data: statusData.counts,
                backgroundColor: ['#38bdf8', '#22d3ee', '#f59e0b', '#10b981', '#f43f5e'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10 }
                }
            }
        }
    });

    const predictionData = buildPredictionData(dashboardLeads);
    const predictionChart = new Chart(document.getElementById('predictionChart'), {
        type: 'bar',
        data: {
            labels: predictionData.labels,
            datasets: [{
                label: 'Lead Count',
                data: predictionData.counts,
                backgroundColor: ['#fb7185', '#f59e0b', '#38bdf8', '#34d399'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    const initialSourceData = buildSourceData(dashboardLeads);
    const sourceChart = new Chart(document.getElementById('sourceChart'), {
        type: 'bar',
        data: {
            labels: initialSourceData.labels,
            datasets: [{
                label: 'Conversion %',
                data: initialSourceData.rates,
                backgroundColor: '#0ea5e9',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, max: 100 }
            }
        }
    });

    function updateVisuals(filterKey) {
        const leads = getFilteredLeads(filterKey);
        renderInsightRows(leads);

        document.getElementById('activeFilterLabel').textContent = filterLabels[filterKey] || 'All Leads';
        document.getElementById('activeFilterCount').textContent = leads.length;

        const nextStatus = buildStatusData(leads);
        statusChart.data.labels = nextStatus.labels;
        statusChart.data.datasets[0].data = nextStatus.counts;
        statusChart.update();

        const nextPrediction = buildPredictionData(leads);
        predictionChart.data.labels = nextPrediction.labels;
        predictionChart.data.datasets[0].data = nextPrediction.counts;
        predictionChart.update();

        const nextSource = buildSourceData(leads);
        sourceChart.data.labels = nextSource.labels;
        sourceChart.data.datasets[0].data = nextSource.rates;
        sourceChart.update();
    }

    $('#metricCards').on('click', '.metric-card', function () {
        $('.metric-card').removeClass('active ring-2 ring-sky-500 border-sky-300');
        $(this).addClass('active ring-2 ring-sky-500 border-sky-300');
        updateVisuals($(this).data('filter'));
    });

    $('#broadcastForm').on('submit', function (e) {
        e.preventDefault();

        $.post("{{ route('notifications.broadcast') }}", $(this).serialize())
            .done(function (response) {
                $('#broadcastMessage').removeClass('text-red-600').addClass('text-emerald-600').text(response.message);
                $('#broadcastForm')[0].reset();
            })
            .fail(function (xhr) {
                const message = xhr.responseJSON?.message || 'Unable to send broadcast.';
                $('#broadcastMessage').removeClass('text-emerald-600').addClass('text-red-600').text(message);
            });
    });

    updateVisuals('all');
</script>
@endpush
