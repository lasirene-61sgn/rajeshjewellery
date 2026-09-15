@extends('layouts.admin')

@section('title', 'Craftsman Profile - ' . $craftsman->name)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-800">{{ $craftsman->name }}</h1>
                @if($craftsman->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Active Account
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                        Inactive Account
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">Craftsman registered on {{ $craftsman->created_at->format('d M Y, h:i A') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <form action="{{ route('admin.craftsmen.auto-assign', $craftsman) }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('Are you sure you want to auto-assign all pending orders for this craftsman\'s design codes?');" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 shadow-sm transition-colors">
                    Assign Directly
                </button>
            </form>
            <a href="{{ route('admin.craftsmen.edit', $craftsman) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Craftsman
            </a>
            <a href="{{ route('admin.craftsmen.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                Back to List
            </a>
        </div>
    </div>

    <!-- Account Details Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xs font-bold tracking-wider text-slate-400 uppercase mb-4">Contact Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                <span class="text-xs text-slate-400 uppercase font-semibold">Mobile Number</span>
                <p class="font-bold text-slate-800 text-base mt-0.5">{{ $craftsman->mobile }}</p>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                <span class="text-xs text-slate-400 uppercase font-semibold">Email Address</span>
                <p class="font-medium text-slate-800 text-base mt-0.5">{{ $craftsman->email ?? 'No email set' }}</p>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                <span class="text-xs text-slate-400 uppercase font-semibold">Assigned Design Specializations</span>
                <p class="font-extrabold text-indigo-600 text-base mt-0.5">{{ $craftsman->designCodes->count() }} Codes</p>
            </div>
        </div>
    </div>

    <!-- Specialized Design Codes & Names Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Assigned Design Codes & Names</h3>
                <p class="text-xs text-slate-400 mt-0.5">Design items this artisan is qualified to fabricate.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">
                {{ $craftsman->designCodes->count() }} Total
            </span>
        </div>

        <div class="p-6">
            @if($craftsman->designCodes->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($craftsman->designCodes as $design)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between gap-3">
                            <div class="truncate">
                                <span class="font-mono font-bold text-indigo-700 text-sm block">{{ $design->code }}</span>
                                <span class="text-xs text-slate-600 font-medium truncate block" title="{{ $design->name }}">
                                    {{ $design->nickname ?: 'No Design Name' }}
                                </span>
                            </div>
                            <span class="shrink-0 p-1 bg-indigo-100/60 text-indigo-700 rounded text-2xs font-semibold">
                                Matched
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-slate-400 text-xs">
                    No design codes have been mapped to this craftsman yet.
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Work Orders Assigned -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Recent Work Orders</h3>
                <p class="text-xs text-slate-400 mt-0.5">Last 10 fabrication jobs assigned to this craftsman.</p>
            </div>
            <a href="{{ route('admin.work_orders.index', ['search' => $craftsman->name]) }}" class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline font-semibold">
                Open All in Work Orders &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs text-slate-600">
                <thead class="bg-slate-50 font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Order No</th>
                        <th class="px-4 py-3">Product / Design Code</th>
                        <th class="px-4 py-3">Target Weight</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($craftsman->workOrders as $order)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-bold font-mono text-slate-900">
                                {{ $order->work_order_no }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $order->product_name }}</div>
                                <div class="font-mono text-indigo-600 flex items-center gap-1">
                                    <span>{{ $order->design_code }}</span>
                                    @if($order->design_nickname)
                                        <span class="font-sans text-slate-500 font-normal">({{ $order->design_nickname }})</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800">
                                {{ number_format($order->target_weight, 3) }} g
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = match($order->status) {
                                        'pending'      => 'bg-slate-100 text-slate-700 border-slate-200',
                                        'allocated'    => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'in_process'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'for_approval' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'returned'     => 'bg-rose-50 text-rose-700 border-rose-200 font-bold',
                                        'completed'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default        => 'bg-slate-100 text-slate-600 border-slate-200'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-semibold border {{ $badge }}">
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ $order->due_date?->format('d M Y') ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.work_orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    View Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No work orders currently assigned to this craftsman.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection