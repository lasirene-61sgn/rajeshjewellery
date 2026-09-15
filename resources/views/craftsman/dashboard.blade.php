@extends('layouts.craftsman')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    
    <!-- Section 1: Today's Progress -->
    <div>
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
            <span class="w-1 h-6 bg-amber-500 rounded-full mr-3"></span>
            Today's Progress
        </h2>
        
        <div class="grid grid-cols-2 gap-4 sm:gap-6">
            <!-- Today In Process -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
                <!-- Subtle background decoration -->
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-50 rounded-full opacity-50 group-hover:scale-110 transition-transform duration-500"></div>
                
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div class="w-12 h-12 rounded-xl bg-slate-900 flex items-center justify-center text-amber-400 mb-4 shadow-lg shadow-slate-900/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ $counts['today_in_process'] }}</div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1 block">In Process</span>
                    </div>
                </div>
            </div>

            <!-- Today Completed -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
                <!-- Subtle background decoration -->
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full opacity-50 group-hover:scale-110 transition-transform duration-500"></div>

                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white mb-4 shadow-lg shadow-emerald-600/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ $counts['today_completed'] }}</div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1 block">Completed</span>
                    </div>
                </div>
            </div>

            <!-- HIDDEN FOR FUTURE USE -->
            <!-- Today Allocated 
            <div class="bg-white p-4 rounded-2xl border border-amber-900/10 shadow-sm flex flex-col justify-between">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-700 mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-stone-800">{{ $counts['today_allocated'] }}</div>
                    <span class="text-xs font-medium text-stone-500 mt-1 block">Today Orders</span>
                </div>
            </div> 
            -->
        </div>
    </div>

    <!-- Section 2: Overall Workspace Stats -->
    <div>
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
            <span class="w-1 h-6 bg-slate-800 rounded-full mr-3"></span>
            Overall Workspace
        </h2>
        
        <div class="grid grid-cols-2 gap-4 sm:gap-6">
            
            <!-- Overall In Process (Link) -->
            <a href="{{ route('craftsman.work-orders.index', ['tab' => 'in_process']) }}" class="group bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:border-amber-400 hover:shadow-lg transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <span class="text-2xl font-bold text-slate-800 group-hover:text-amber-600 transition-colors">{{ $counts['overall_in_process'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-700 block group-hover:text-slate-900">Active Jobs</span>
                    <span class="text-[10px] text-amber-600 font-medium mt-1 inline-flex items-center">View Details <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></span>
                </div>
            </a>

            <!-- Rejected / Returned (Link) -->
            <a href="{{ route('craftsman.work-orders.index', ['tab' => 'returned']) }}" class="group bg-white p-5 rounded-2xl border border-rose-100 shadow-sm hover:border-rose-400 hover:shadow-lg transition-all duration-300 flex flex-col justify-between bg-rose-50/30">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <span class="text-2xl font-bold text-rose-600">{{ $counts['overall_rejected'] }}</span>
                </div>
                <div>
                    <span class="text-xs font-bold text-rose-800 block">Returned / QC Fail</span>
                    <span class="text-[10px] text-rose-600 font-medium mt-1 inline-flex items-center">Action Required <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                </div>
            </a>

            <!-- Overdue Orders -->
            <div class="col-span-2 sm:col-span-1 bg-white p-5 rounded-2xl border border-orange-100 shadow-sm flex flex-col justify-between bg-orange-50/30">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-2xl font-bold text-orange-600">{{ $counts['overall_overdue'] }}</span>
                </div>
                <div>
                    <span class="text-xs font-bold text-orange-800 block">Overdue Orders</span>
                    <span class="text-[10px] text-orange-600 font-medium mt-1 block">Needs Immediate Attention</span>
                </div>
            </div>

            <!-- HIDDEN FOR FUTURE USE -->
            <!-- Overall New (Allocated) 
            <a href="{{ route('craftsman.work-orders.index', ['tab' => 'allocated']) }}" class="bg-white p-4 rounded-2xl border border-amber-900/10 shadow-sm hover:border-amber-600/40 transition-colors flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <span class="text-2xl sm:text-3xl font-extrabold text-stone-800">{{ $counts['overall_new'] }}</span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-stone-700 block">New / Pending Accept</span>
                    <span class="text-[10px] text-amber-700 font-medium mt-0.5 block">View &rarr;</span>
                </div>
            </a> 
            -->
        </div>
    </div>

    <!-- Section 3: Assigned Design Codes -->
    <div>
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
            <span class="w-1 h-6 bg-amber-500 rounded-full mr-3"></span>
            My Assigned Design Codes
        </h2>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            @if($assignedDesigns->isEmpty())
                <div class="flex flex-col items-center justify-center py-4 text-slate-400">
                    <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                    <p class="text-sm font-medium">No design codes are currently assigned to you.</p>
                </div>
            @else
                <div class="flex flex-wrap gap-3">
                    @foreach($assignedDesigns as $design)
                        <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-50 text-slate-700 border border-slate-200 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800 transition-colors cursor-default shadow-sm">
                            <span class="font-bold mr-2">{{ $design->code }}</span>
                            @if($design->nickname)
                                <span class="text-slate-400 font-normal text-xs border-l border-slate-300 pl-2 ml-1">{{ $design->nickname }}</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- HIDDEN FOR FUTURE USE -->
    <!-- Active Orders Widget 
    <div class="bg-white rounded-2xl border border-amber-900/10 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h2 class="font-bold text-stone-800 text-sm">Active Job Queue</h2>
            <a href="{{ route('craftsman.work-orders.index') }}" class="text-xs text-amber-700 font-semibold hover:underline">View All &rarr;</a>
        </div>
        <div class="divide-y divide-stone-100">
            @forelse($recentOrders as $order)
                <div class="p-4 flex flex-col sm:flex-ro sm:items-center sm:justify-between gap-3 hover:bg-stone-50">
                    <div class="flex items-center gap-3">
                        @if($order->design_image)
                            <img src="{{ asset('storage/' . $order->design_image) }}" class="w-12 h-12 rounded-xl object-cover border border-stone-200">
                        @else
                            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-amber-700 text-xs font-bold">N/A</div>
                        @endif
                        <div>
                            <div class="font-bold text-stone-800 text-sm flex items-center gap-2">
                                <span>{{ $order->work_order_no }}</span>
                                @if($order->status === 'returned')
                                    <span class="px-2 py-0.5 text-[10px] uppercase tracking-wide bg-rose-100 text-rose-700 font-bold rounded-md">QC Return</span>
                                @endif
                            </div>
                            <div class="text-xs text-stone-500">{{ $order->product_name }} &bull; <span class="font-mono text-amber-700 font-semibold">{{ $order->design_code }}</span> &bull; {{ number_format($order->target_weight, 3) }}g</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-stone-100">
                        <span class="text-xs text-stone-500">Due: <strong class="text-stone-800">{{ $order->return_due_date ? $order->return_due_date->format('d M') : $order->due_date->format('d M') }}</strong></span>
                        @if($order->status === 'allocated')
                            <form method="POST" action="{{ route('craftsman.work-orders.accept', $order) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-amber-700 hover:bg-amber-800 text-white rounded-xl text-xs font-semibold shadow-sm">Accept</button>
                            </form>
                        @else
                            <a href="{{ route('craftsman.work-orders.show', $order) }}" class="px-3 py-1.5 bg-stone-800 hover:bg-stone-900 text-white rounded-xl text-xs font-semibold shadow-sm">View Job</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-stone-400 text-sm">No active fabrication orders at this time.</div>
            @endforelse
        </div>
    </div> 
    -->
</div>
@endsection