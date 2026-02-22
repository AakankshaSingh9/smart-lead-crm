<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Smart CRM') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-item-active {
            background-color: #4f46e5;
            color: #ffffff !important;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.25);
        }
        .sidebar-item-active i {
            color: #ffffff !important;
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="h-screen overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <aside id="sidebar" class="w-64 bg-slate-900 shrink-0 h-screen sticky top-0">
            <div class="flex flex-col h-full min-h-0">
                <div class="flex items-center justify-center h-16 bg-slate-950 px-6">
                    <i class="ph-fill ph-rocket-launch text-indigo-400 text-2xl mr-2"></i>
                    <span class="text-white font-bold text-xl tracking-tight">Smart CRM</span>
                </div>

                <nav class="flex-1 min-h-0 px-4 py-6 space-y-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'sidebar-item-active' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="ph ph-squares-four mr-3 text-lg"></i> Dashboard
                    </a>
                    <a href="{{ route('leads.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('leads.*') ? 'sidebar-item-active' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="ph ph-user-list mr-3 text-lg"></i> Leads
                    </a>
                    <a href="{{ route('followups.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('followups.*') ? 'sidebar-item-active' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="ph ph-calendar-check mr-3 text-lg"></i> Follow-ups
                    </a>
                    <a href="{{ route('opportunities.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('opportunities.*') ? 'sidebar-item-active' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="ph ph-chart-line-up mr-3 text-lg"></i> Opportunities
                    </a>
                </nav>

                <div class="p-4 bg-slate-950/50">
                    <div class="flex items-center mb-4 px-2">
                        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold mr-3">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400 uppercase tracking-tighter">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-4 py-2 text-sm font-medium text-red-400 hover:bg-red-500/10 rounded-lg transition-all group">
                            <i class="ph ph-sign-out mr-3 text-lg group-hover:translate-x-1 transition-transform"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden">
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-8 shrink-0">
                <button id="mobileMenuBtn" class="hidden text-slate-600 p-2" type="button" aria-label="Toggle menu">
                    <i class="ph ph-list text-2xl"></i>
                </button>

                <div class="flex items-center ml-auto space-x-4">
                    <div class="relative">
                        <button id="notificationBell" class="p-2 text-slate-500 hover:bg-slate-100 rounded-full transition-colors relative">
                            <i class="ph ph-bell text-2xl"></i>
                            <span id="notificationCount" class="hidden absolute top-1 right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] text-white items-center justify-center font-bold"></span>
                            </span>
                        </button>

                        <div id="notificationDropdown" class="hidden absolute right-0 mt-3 w-80 bg-white border border-slate-200 rounded-xl shadow-xl z-50 overflow-hidden">
                            <div class="p-4 border-b bg-slate-50 flex justify-between items-center">
                                <h3 class="font-semibold text-slate-800">Notifications</h3>
                                <button id="markAllRead" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Clear all</button>
                            </div>
                            <div id="notificationItems" class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                                </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-8 bg-slate-50">
                @if (session('success'))
                    <div class="mb-6 flex items-center p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-r-lg shadow-sm">
                        <i class="ph-fill ph-check-circle mr-3 text-xl"></i>
                        {{ session('success') }}
                    </div>
                @endif

                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Notification Logic
        function renderNotifications(payload) {
            const unread = Number(payload.unread_count || 0);
            const $countContainer = $('#notificationCount');
            const $countText = $countContainer.find('span:last-child');

            if (unread > 0) {
                $countContainer.removeClass('hidden');
                $countText.text(unread > 9 ? '9+' : unread);
            } else {
                $countContainer.addClass('hidden');
            }

            const items = payload.items || [];
            if (!items.length) {
                $('#notificationItems').html('<div class="p-8 text-center text-slate-400"><i class="ph ph-ghost text-4xl block mb-2"></i><p class="text-sm">All caught up!</p></div>');
                return;
            }

            const html = items.map(item => `
                <a href="${item.route || '#'}" data-id="${item.id}" class="notification-item flex p-4 transition-colors ${item.read_at ? 'bg-white' : 'bg-indigo-50/50 hover:bg-indigo-50'}">
                    <div class="mr-3 mt-1 h-2 w-2 rounded-full ${item.read_at ? 'bg-transparent' : 'bg-indigo-500'}"></div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">${item.title}</p>
                        <p class="text-xs text-slate-600 line-clamp-2">${item.message}</p>
                        <p class="text-[10px] text-slate-400 mt-2 font-medium uppercase">${item.created_at}</p>
                    </div>
                </a>
            `).join('');

            $('#notificationItems').html(html);
        }

        // Dropdown Toggle
        $('#notificationBell').on('click', function(e) {
            e.stopPropagation();
            $('#notificationDropdown').toggleClass('hidden');
        });

        $(document).on('click', (e) => {
            if (!$(e.target).closest('#notificationBell').length) $('#notificationDropdown').addClass('hidden');
        });

        // Initialize
        function poll() { $.get("{{ route('notifications.poll') }}").done(renderNotifications); }
        poll();
        setInterval(poll, 30000);
    </script>
    @stack('scripts')
</body>
</html>
