@extends('layouts.craftsman')

@section('title', 'Order Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header & Back Button -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Order Details: #{{ $order->reference_number ?? $order->id }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">Assigned Customer Order Specifications</p>
        </div>
        <a href="{{ route('craftsman.orders.index') }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Assigned Orders
        </a>
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
                        <h2 class="text-2xl font-bold text-slate-900">{{ $order->product_name ?? 'Unnamed Product' }}</h2>
                        <span class="text-sm font-mono text-indigo-600 font-medium">Ref: #{{ $order->reference_number ?? 'N/A' }}</span>
                    </div>

                    <div class="pt-2">
                        @if($order->status !== 'completed')
                            <form action="{{ route('craftsman.orders.update-status', $order->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                
                                @if($order->status === 'allocated')
                                    <input type="hidden" name="status" value="in_progress">
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                        Start Work
                                    </button>
                                @elseif($order->status === 'in_progress')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm inline-flex items-center" onclick="return confirm('Mark this order as completed?');">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Mark Completed
                                    </button>
                                @endif
                            </form>
                        @else
                            <span class="text-sm font-medium text-emerald-600 inline-flex items-center">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Order Finished
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Detailed Specifications Grid -->
            <div>
                <h3 class="text-base font-semibold text-slate-900 mb-4">Specifications</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <span class="text-xs text-slate-400 block font-medium uppercase">Quantity</span>
                        <span class="text-base font-bold text-slate-900 mt-1 block">{{ $order->quantity ?? '-' }}</span>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <span class="text-xs text-slate-400 block font-medium uppercase">Size</span>
                        <span class="text-base font-bold text-slate-900 mt-1 block">{{ $order->size ?? '-' }}</span>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <span class="text-xs text-slate-400 block font-medium uppercase">Length</span>
                        <span class="text-base font-bold text-slate-900 mt-1 block">{{ $order->length ?? '-' }}</span>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <span class="text-xs text-slate-400 block font-medium uppercase">Weight</span>
                        <span class="text-base font-bold text-slate-900 mt-1 block">{{ $order->weight ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Additional Details & Dates -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                <div>
                    <h4 class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Order Type</h4>
                    <p class="text-sm font-medium text-slate-900 mt-1">{{ $order->order_type ?? 'Standard' }}</p>
                </div>

                <div>
                    <h4 class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Due Date</h4>
                    <p class="text-sm font-medium text-slate-900 mt-1">
                        {{ $order->due_date ? $order->due_date->format('d F, Y') : 'No Due Date Assigned' }}
                    </p>
                </div>
            </div>

            <!-- Notes / Instructions -->
            @if($order->notes)
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Notes & Instructions</h4>
                    <div class="mt-2 p-4 bg-amber-50 rounded-xl border border-amber-100 text-sm text-amber-900 whitespace-pre-wrap leading-relaxed">
                        {{ $order->notes }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
