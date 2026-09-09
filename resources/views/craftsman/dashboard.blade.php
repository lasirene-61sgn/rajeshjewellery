@extends('layouts.craftsman')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Workshop Overview</h1>
        <p class="text-sm text-slate-500">Track current jobs, return notices, and urgent delivery deadlines.</p>
    </div>

    <!-- Stat Grid (Today) -->
    <h2 class="text-lg font-semibold text-slate-800 mt-2 mb-1">Today's Progress</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Today Allocated -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-blue-500">
            <span class="text-xs font-semibold text-slate-400 uppercase">Today Allocated</span>
            <div class="mt-2 text-3xl font-extrabold text-blue-600">{{ $counts['today_allocated'] }}</div>
        </div>
        <!-- Today In Process -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-amber-500">
            <span class="text-xs font-semibold text-slate-400 uppercase">Today In Process</span>
            <div class="mt-2 text-3xl font-extrabold text-amber-600">{{ $counts['today_in_process'] }}</div>
        </div>
        <!-- Today Completed -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-emerald-500">
            <span class="text-xs font-semibold text-slate-400 uppercase">Today Completed</span>
            <div class="mt-2 text-3xl font-extrabold text-emerald-600">{{ $counts['today_completed'] }}</div>
        </div>
    </div>

    <!-- Stat Grid (Overall) -->
    <h2 class="text-lg font-semibold text-slate-800 mt-6 mb-1">Overall Workspace</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Overall New (Allocated) -->
        <a href="{{ route('craftsman.work-orders.index', ['tab' => 'allocated']) }}" class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:border-blue-400 transition-colors">
            <span class="text-xs font-semibold text-slate-400 uppercase">New (Pending Accept)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-blue-600">{{ $counts['overall_new'] }}</span>
                <span class="text-xs text-blue-600 font-medium">View &rarr;</span>
            </div>
        </a>

        <!-- Rework / Returned Notice -->
        <a href="{{ route('craftsman.work-orders.index', ['tab' => 'in_process']) }}" class="bg-white p-5 rounded-xl border border-rose-200 shadow-sm bg-rose-50/20 hover:border-rose-400 transition-colors">
            <span class="text-xs font-semibold text-rose-600 uppercase">Rejected (Returned)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-rose-600">{{ $counts['overall_rejected'] }}</span>
                <span class="text-xs text-rose-600 font-medium">Action Required &rarr;</span>
            </div>
        </a>

        <!-- Overdue -->
        <div class="bg-white p-5 rounded-xl border border-orange-200 shadow-sm bg-orange-50/20">
            <span class="text-xs font-semibold text-orange-600 uppercase">Overdue Orders</span>
            <div class="mt-2 text-3xl font-extrabold text-orange-600">{{ $counts['overall_overdue'] }}</div>
        </div>
    </div>

    <!-- Assigned Design Codes -->
    <h2 class="text-lg font-semibold text-slate-800 mt-6 mb-1">My Assigned Design Codes</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        @if($assignedDesigns->isEmpty())
            <p class="text-sm text-slate-500">No design codes are currently assigned to you.</p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach($assignedDesigns as $design)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <span class="font-bold mr-1">{{ $design->code }}</span>
                        @if($design->nickname)
                            <span class="text-indigo-500 font-normal">({{ $design->nickname }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Active Orders Widget -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-800 text-sm">Active Job Queue</h2>
            <a href="{{ route('craftsman.work-orders.index') }}" class="text-xs text-amber-600 font-semibold hover:underline">View All Orders &rarr;</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentOrders as $order)
                <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        @if($order->design_image)
                            <img src="{{ asset('storage/' . $order->design_image) }}" class="w-12 h-12 rounded-lg object-cover border">
                        @else
                            <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 text-xs font-bold">N/A</div>
                        @endif
                        <div>
                            <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                <span>{{ $order->work_order_no }}</span>
                                @if($order->status === 'returned')
                                    <span class="px-2 py-0.5 text-2xs uppercase tracking-wide bg-rose-100 text-rose-700 font-bold rounded">QC Return</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500">{{ $order->product_name }} &bull; <span class="font-mono text-indigo-600 font-semibold">{{ $order->design_code }}</span> &bull; {{ number_format($order->target_weight, 3) }}g</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500">Due: <strong class="text-slate-800">{{ $order->return_due_date ? $order->return_due_date->format('d M') : $order->due_date->format('d M') }}</strong></span>
                        @if($order->status === 'allocated')
                            <form method="POST" action="{{ route('craftsman.work-orders.accept', $order) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold">Accept</button>
                            </form>
                        @else
                            <a href="{{ route('craftsman.work-orders.show', $order) }}" class="px-3 py-1.5 bg-slate-800 hover:bg-black text-white rounded text-xs font-semibold">View Job</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 text-sm">No active fabrication orders at this time.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection