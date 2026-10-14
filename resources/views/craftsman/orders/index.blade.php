@extends('layouts.craftsman')

@section('title', 'My Assigned Orders')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Assigned Orders</h1>
        <p class="text-sm text-slate-500 mt-1">View your assigned work orders and update progress in real time.</p>
    </div>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-4 overflow-x-auto">
        @php $currentStatus = request('status'); @endphp
        <a href="{{ route('craftsman.orders.index') }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ is_null($currentStatus) ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">All Assigned</a>
        <a href="{{ route('craftsman.orders.index', ['status' => 'allocated']) }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ $currentStatus == 'allocated' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">Allocated</a>
        <a href="{{ route('craftsman.orders.index', ['status' => 'in_progress']) }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ $currentStatus == 'in_progress' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">In Progress</a>
        <a href="{{ route('craftsman.orders.index', ['status' => 'completed']) }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ $currentStatus == 'completed' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">Completed</a>
    </div>

    @php
        $badgeStyles = [
            'allocated' => 'bg-sky-50 text-sky-700 border-sky-200',
            'in_progress' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
        ];
    @endphp

    <!-- MOBILE VIEW: Cards Layout (Visible on Small Screens) -->
    <div class="grid grid-cols-1 gap-4 lg:hidden">
        @forelse($orders as $order)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        @if($order->image)
                            <img src="{{ asset('storage/' . $order->image) }}" alt="Product" class="w-12 h-12 rounded-lg object-cover border border-slate-200 shadow-sm shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 text-xs italic shrink-0">No img</div>
                        @endif
                        <div>
                            <span class="font-mono text-xs font-semibold text-indigo-600 block">#{{ $order->reference_number ?? 'N/A' }}</span>
                            <h3 class="font-bold text-slate-900 text-base leading-tight">{{ $order->product_name }}</h3>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeStyles[$order->status] ?? 'bg-slate-100 text-slate-800' }}">
                        {{ ucwords(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-lg text-xs text-slate-600">
                    <div>
                        <span class="text-slate-400 block">Qty</span>
                        <span class="font-semibold text-slate-900">{{ $order->quantity ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Size</span>
                        <span class="font-semibold text-slate-900">{{ $order->size ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Weight</span>
                        <span class="font-semibold text-slate-900">{{ $order->weight ?? '-' }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                    <span>Due: <strong>{{ $order->due_date ? $order->due_date->format('d M, Y') : 'N/A' }}</strong></span>
                    
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('craftsman.orders.show', $order->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-colors">
                            View
                        </a>
                        @if($order->status !== 'completed')
                            <form action="{{ route('craftsman.orders.update-status', $order->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                @if($order->status === 'allocated')
                                    <input type="hidden" name="status" value="in_progress">
                                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors">
                                        Start Work
                                    </button>
                                @elseif($order->status === 'in_progress')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition-colors" onclick="return confirm('Mark completed?');">
                                        Complete
                                    </button>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center text-slate-400">
                No orders assigned to you at the moment.
            </div>
        @endforelse
    </div>

    <!-- DESKTOP VIEW: Table Layout (Hidden on Mobile, Visible on Large Screens) -->
    <div class="hidden lg:block bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Image</th>
                        <th class="px-6 py-3.5">Reference / Product</th>
                        <th class="px-6 py-3.5">Specs (Qty/Size/Wt)</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Due Date</th>
                        <th class="px-6 py-3.5 text-right">Actions / Progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($order->image)
                                    <img src="{{ asset('storage/' . $order->image) }}" alt="Product" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm">
                                @else
                                    <span class="text-xs text-slate-400 italic">No image</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs font-semibold text-indigo-600 block">#{{ $order->reference_number ?? 'N/A' }}</span>
                                <span class="font-bold text-slate-900 block">{{ $order->product_name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                <div>Qty: <span class="font-medium text-slate-900">{{ $order->quantity ?? '-' }}</span></div>
                                <div>Size: <span class="font-medium text-slate-900">{{ $order->size ?? '-' }}</span> | Wt: <span class="font-medium text-slate-900">{{ $order->weight ?? '-' }}</span></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badgeStyles[$order->status] ?? 'bg-slate-100 text-slate-800' }}">
                                    {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                {{ $order->due_date ? $order->due_date->format('d M, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('craftsman.orders.show', $order->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg text-xs transition-colors">
                                        View Details
                                    </a>

                                    @if($order->status !== 'completed')
                                        <form action="{{ route('craftsman.orders.update-status', $order->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            
                                            @if($order->status === 'allocated')
                                                <input type="hidden" name="status" value="in_progress">
                                                <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                                                    Start Work
                                                </button>
                                            @elseif($order->status === 'in_progress')
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm" onclick="return confirm('Mark this order as completed?');">
                                                    Mark Completed
                                                </button>
                                            @endif
                                        </form>
                                    @else
                                        <span class="text-xs font-medium text-emerald-600 flex items-center justify-end">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Finished
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">No orders assigned to you at the moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($orders->hasPages())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 px-6 py-4">
            {{ $orders->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection