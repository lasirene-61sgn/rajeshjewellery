<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Craftsman Portal') - Jewelry ERP</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full flex overflow-hidden font-sans text-slate-800 bg-slate-50" x-data="{ mobileNav: false }">

    <!-- Mobile Drawer Overlay -->
    <div x-show="mobileNav" x-transition.opacity 
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm md:hidden" 
         @click="mobileNav = false" x-cloak></div>

    <!-- Craftsman Sidebar Navigation -->
    <aside :class="mobileNav ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out md:static md:translate-x-0 md:flex md:flex-col shrink-0 border-r border-slate-800 shadow-xl">
        
        <!-- Logo Area -->
        <div class="flex items-center justify-between h-20 px-6 bg-slate-900 border-b border-slate-800">
            <div class="flex items-center space-x-2">
                <!-- Elegant Crown/Diamond Icon for Royal Jewelry Feel -->
                <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L9 9H2L7 14L5 21L12 17L19 21L17 14L22 9H15L12 2Z"/>
                </svg>
                <span class="text-lg font-bold tracking-wider text-amber-400">AURUM</span>
                <span class="text-sm font-light tracking-widest text-slate-500">| CRAFTSMAN</span>
            </div>
            <button @click="mobileNav = false" class="md:hidden text-slate-400 hover:text-amber-400 focus:outline-none transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
            <a href="{{ route('craftsman.dashboard') }}" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('craftsman.dashboard') ? 'bg-amber-500/10 text-amber-400 border-r-2 border-amber-500' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('craftsman.dashboard') ? 'text-amber-400' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('craftsman.work-orders.index') }}" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('craftsman.work-orders.*') ? 'bg-amber-500/10 text-amber-400 border-r-2 border-amber-500' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('craftsman.work-orders.*') ? 'text-amber-400' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                My Work Orders
            </a>
        </nav>

        <!-- Logout Section (Always pushed to the bottom by flex-1 nav above) -->
        <div class="p-4 border-t border-slate-800 bg-slate-900/50">
            <form method="POST" action="{{ route('craftsman.logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full px-4 py-3 text-sm font-medium text-slate-400 rounded-lg hover:bg-rose-500/10 hover:text-rose-400 transition-all duration-200 group">
                    <svg class="w-5 h-5 mr-3 text-slate-500 group-hover:text-rose-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign Out
                </button>
            </form>
        </div>
    </aside>

    <!-- Content Viewport -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">
        
        <!-- Top Header -->
        <header class="relative flex items-center justify-between h-16 px-4 bg-white border-b border-slate-200 shadow-sm sm:px-6">
            
            <!-- Mobile Menu Button -->
            <button @click="mobileNav = true" class="text-slate-500 hover:text-amber-600 focus:outline-none md:hidden transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Centered Dashboard Name on Mobile -->
            <div class="absolute left-1/2 transform -translate-x-1/2 md:hidden">
                <h1 class="text-base font-bold text-slate-800 tracking-wide">@yield('title', 'Dashboard')</h1>
            </div>

            <!-- Right Side User Profile -->
            <div class="flex items-center ml-auto space-x-3">
                <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 text-xs font-semibold tracking-wide text-amber-700 uppercase bg-amber-50 rounded-full border border-amber-200">
                    Craftsman
                </span>
                <div class="flex items-center space-x-3 pl-3 border-l border-slate-200">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-slate-800">{{ Auth::guard('craftsman')->user()->name }}</p>
                        <p class="text-xs text-slate-500">Craftsman Portal</p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-slate-900 flex items-center justify-center text-amber-400 font-bold text-sm border border-amber-500/30 shadow-sm">
                        {{ strtoupper(substr(Auth::guard('craftsman')->user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium shadow-sm flex items-start">
                    <svg class="w-5 h-5 mr-3 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

</body>
</html>