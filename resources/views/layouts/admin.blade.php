<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin Panel') - Jewelry Management</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="h-full flex overflow-hidden font-sans" x-data="{ sidebarOpen: false, pcSidebarCollapsed: localStorage.getItem('pcSidebarCollapsed') === 'true' }" x-init="$watch('pcSidebarCollapsed', val => localStorage.setItem('pcSidebarCollapsed', val))">

    <!-- Mobile Backdrop -->
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-slate-900/85 backdrop-blur-sm lg:hidden"
        @click="sidebarOpen = false"
        x-cloak></div>

    <!-- Responsive Sidebar -->
    <aside
        :class="{
            'translate-x-0': sidebarOpen,
            '-translate-x-full': !sidebarOpen,
            'lg:w-20': pcSidebarCollapsed,
            'lg:w-64': !pcSidebarCollapsed
        }"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white transition-all duration-300 ease-in-out lg:static lg:translate-x-0 lg:flex lg:flex-col shrink-0">
        <!-- Logo / Brand Header -->
        <div class="flex items-center justify-between h-16 px-6 bg-slate-950 border-b border-slate-800">
            <span class="text-lg font-bold tracking-wider text-indigo-400 truncate" x-show="!pcSidebarCollapsed">JEWELRY ERP</span>
            <span class="text-lg font-bold text-indigo-400 mx-auto" x-show="pcSidebarCollapsed" x-cloak>ERP</span>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <button @click="pcSidebarCollapsed = !pcSidebarCollapsed" class="hidden lg:block text-slate-400 hover:text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
                :title="pcSidebarCollapsed ? 'Dashboard' : ''"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" :class="pcSidebarCollapsed ? 'mx-auto' : 'mr-3 text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span x-show="!pcSidebarCollapsed">Dashboard</span>
            </a>

            <!-- Craftsmen -->
            <a href="{{ route('admin.craftsmen.index') }}"
                :title="pcSidebarCollapsed ? 'Craftsmen' : ''"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.craftsmen.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" :class="pcSidebarCollapsed ? 'mx-auto' : 'mr-3 text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span x-show="!pcSidebarCollapsed">Craftsmen</span>
            </a>

            <!-- Work Orders -->
            <a href="{{ route('admin.work_orders.index') }}"
                :title="pcSidebarCollapsed ? 'Work Orders' : ''"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.work_orders.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.work_orders.*') ? 'text-white' : 'text-slate-400' }}" :class="pcSidebarCollapsed ? 'mx-auto' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span x-show="!pcSidebarCollapsed">Work Orders</span>
            </a>

            <!-- Master Design Codes -->
            <a href="{{ route('admin.design_codes.index') }}"
                :title="pcSidebarCollapsed ? 'Master Design Catalog' : ''"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.design_codes.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.design_codes.*') ? 'text-white' : 'text-slate-400' }}" :class="pcSidebarCollapsed ? 'mx-auto' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span x-show="!pcSidebarCollapsed">Master Design Catalog</span>
            </a>

            <!-- Order -->
            <a href="{{ route('admin.orders.index') }}"
                :title="pcSidebarCollapsed ? 'Customer Orders' : ''"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.orders.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.orders.*') ? 'text-white' : 'text-slate-400' }}" :class="pcSidebarCollapsed ? 'mx-auto' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span x-show="!pcSidebarCollapsed">Customer Orders</span>
            </a>
        </nav>

        <!-- Logout Section with Dropdown on Profile Click -->
        <div class="p-4 border-t border-slate-800 bg-slate-950 relative" x-data="{ profileDropdown: false }">
            <div @click="profileDropdown = !profileDropdown" class="cursor-pointer flex items-center justify-between">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="h-9 w-9 shrink-0 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow" :class="pcSidebarCollapsed ? 'mx-auto' : ''">
                        {{ substr(Auth::guard('admin')->user()->name ?? 'A', 0, 1) }}
                    </div>
                    <div class="truncate" x-show="!pcSidebarCollapsed">
                        <p class="text-xs font-bold text-white truncate">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</p>
                        <p class="text-[10px] text-slate-400 truncate">Click to Sign Out</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-400 shrink-0" x-show="!pcSidebarCollapsed" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <!-- Profile Popup / Logout Menu -->
            <div x-show="profileDropdown" @click.away="profileDropdown = false" x-cloak
                 class="absolute bottom-full left-4 right-4 mb-2 bg-slate-900 border border-slate-700 rounded-lg shadow-xl p-2 z-50">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-3 py-2 text-sm font-medium text-slate-300 rounded-md hover:bg-rose-600 hover:text-white transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Top Navbar -->
        <header class="flex items-center justify-between h-16 px-4 bg-white border-b border-slate-200 sm:px-6 shadow-sm z-10">
            <button @click="sidebarOpen = true" class="text-slate-600 hover:text-slate-900 focus:outline-none lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- PC Sidebar Toggle Button in Top Bar removed to prevent duplication -->
            <div class="hidden lg:block"></div>

            <div class="flex items-center ml-auto space-x-4">
                @php
                    $unassignedCodes = \App\Models\DesignCode::doesntHave('craftsmen')->orderBy('created_at', 'desc')->take(5)->get();
                    $unassignedCount = \App\Models\DesignCode::doesntHave('craftsmen')->count();
                @endphp

                <!-- Visible Unassigned Designs (Inline) -->
                @if($unassignedCount > 0)
                    <div class="hidden md:flex items-center space-x-2 mr-4">
                        <span class="text-xs font-semibold text-slate-500 uppercase">Unassigned:</span>
                        <div class="flex items-center space-x-1">
                            @foreach($unassignedCodes as $dc)
                                <a href="{{ route('admin.design_codes.unassigned') }}" class="px-2 py-1 bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-100 rounded text-xs font-mono font-medium transition-colors">
                                    {{ $dc->code }}
                                </a>
                            @endforeach
                            @if($unassignedCount > 5)
                                <a href="{{ route('admin.design_codes.unassigned') }}" class="text-xs font-medium text-slate-400 hover:text-indigo-600 transition-colors">
                                    +{{ $unassignedCount - 5 }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <span class="text-sm font-medium text-slate-700 hidden sm:block">
                    Logged in as: <span class="font-bold">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</span>
                </span>
                <div class="h-9 w-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow">
                    {{ substr(Auth::guard('admin')->user()->name ?? 'A', 0, 1) }}
                </div>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <!-- Global Flash Messages -->
            @if(session('success'))
            <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            </div>
            @endif

            @if(isset($errors) && $errors->any())
            <div class="mb-6 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Heartbeat Poller (Kicks out displaced session automatically) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(() => {
                fetch("{{ route('admin.heartbeat') }}", {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (response.status === 401 || response.status === 419 || response.redirected) {
                            window.location.href = "{{ route('admin.login') }}";
                        }
                    })
                    .catch(() => {
                        window.location.href = "{{ route('admin.login') }}";
                    });
            }, 3000);
        });
    </script>
</body>

</html>