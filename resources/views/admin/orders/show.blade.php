@extends('layouts.admin')

@section('title', 'Order Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header & Back Button -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Order Details: #{{ $order->reference_number ?? $order->id }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">Comprehensive view of order specifications and assignment status.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.orders.edit', $order->id) }}" class="inline-flex items-center px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 text-sm font-medium rounded-lg border border-amber-200 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Order
            </a>
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Orders
            </a>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 sm:p-8 space-y-8">
            
            <!-- Top Grid: Image & Quick Status Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start pb-6 border-b border-slate-200">
                <!-- Product Image -->
                <div class="flex flex-col items-center justify-center bg-slate-50 border border-slate-200 rounded-xl p-4">
                    @if($order->image)
                        <img src="{{ asset('storage/' . $order->image) }}" alt="Product Reference" class="w-48 h-48 rounded-lg object-cover shadow-sm border border-slate-200">
                    @else
                        <div class="w-48 h-48 rounded-lg bg-slate-200 flex items-center justify-center text-slate-400 text-xs italic">
                            No Reference Image
                        </div>
                    @endif
                    <span class="text-xs text-slate-500 mt-3 font-medium">Product Reference Image</span>
                </div>

                <!-- Basic Info & Status -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono uppercase tracking-wider text-slate-400">Order Status</span>
                        @php
                            $badgeStyles = [
                                'pending' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'allocated' => 'bg-sky-50 text-sky-700 border-sky-200',
                                'in_progress' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeStyles[$order->status] ?? 'bg-slate-100 text-slate-800' }}">
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-slate-900">{{ $order->product_name }}</h2>
                        <p class="text-sm font-mono text-indigo-600 mt-0.5">Ref: #{{ $order->reference_number ?? 'N/A' }}</p>
                    </div>

                    <!-- Assigned Craftsman Box -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Assigned Craftsman</span>
                        @if($order->craftsman)
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-900 text-base">{{ $order->craftsman->name }}</div>
                                    <div class="text-xs text-slate-500 flex items-center mt-1">
                                        <svg class="w-4 h-4 mr-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        {{ $order->craftsman->mobile }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-amber-600 text-sm font-medium flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Unallocated (No craftsman assigned yet)
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Detailed Specifications Grid -->
            <div>
                <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">Specifications & Attributes</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                        <span class="text-xs text-slate-400 block">Order Type</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $order->order_type ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                        <span class="text-xs text-slate-400 block">Quantity</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $order->quantity ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                        <span class="text-xs text-slate-400 block">Size</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $order->size ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                        <span class="text-xs text-slate-400 block">Length</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $order->length ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                        <span class="text-xs text-slate-400 block">Weight</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $order->weight ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                        <span class="text-xs text-slate-400 block">Due Date</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $order->due_date ? $order->due_date->format('d M, Y') : 'N/A' }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 col-span-2">
                        <span class="text-xs text-slate-400 block">Created At</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $order->created_at->format('d M, Y - h:i A') }}</span>
                    </div>
                </div>
            </div>

            <!-- Notes / Instructions -->
            @if($order->notes)
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">Special Notes & Instructions</h3>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-slate-700 text-sm whitespace-pre-line">
                        {{ $order->notes }}
                    </div>
                </div>
            @endif

            <!-- Admin Actions Bar -->
            <div class="pt-6 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4">
                @if($order->status !== 'completed')
                    <form action="{{ route('admin.orders.force-complete', $order->id) }}" method="POST" onsubmit="return confirm('Force complete this order?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                            Mark as Force Completed
                        </button>
                    </form>
                @else
                    <span class="text-emerald-600 font-medium text-sm flex items-center">
                        <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Order successfully completed
                    </span>
                @endif

                <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this order?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-sm font-medium rounded-lg border border-rose-200 transition-colors">
                        Delete Order
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection