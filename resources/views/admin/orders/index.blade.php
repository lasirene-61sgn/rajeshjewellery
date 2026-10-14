@extends('layouts.admin')

@section('title', 'Customer Orders')

@section('content')
<div class="space-y-6">
    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Customer Orders</h1>
            <p class="text-sm text-slate-500 mt-1">Manage, allocate, and monitor orders assigned to craftsmen.</p>
        </div>
        <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Order
        </a>
    </div>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-4">
        @php $currentStatus = request('status'); @endphp
        <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ is_null($currentStatus) ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">All Orders</a>
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $currentStatus == 'pending' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">Pending</a>
        <a href="{{ route('admin.orders.index', ['status' => 'allocated']) }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $currentStatus == 'allocated' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">Allocated</a>
        <a href="{{ route('admin.orders.index', ['status' => 'in_progress']) }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $currentStatus == 'in_progress' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">In Progress</a>
        <a href="{{ route('admin.orders.index', ['status' => 'completed']) }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $currentStatus == 'completed' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">Completed</a>
    </div>

    <!-- Orders Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Image</th>
                        <th class="px-6 py-3.5">Reference / Product</th>
                        <th class="px-6 py-3.5">Craftsman / Allocation</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Due Date</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
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
                                <span class="text-xs text-slate-400">Qty: {{ $order->quantity ?? '-' }} | Size: {{ $order->size ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(empty($order->craftsman_id))
                                    <!-- Show dropdown if order has no craftsman assigned -->
                                    <form action="{{ route('admin.orders.allocate', $order->id) }}" method="POST" class="flex items-center space-x-2">
                                        @csrf
                                        <select name="craftsman_id" class="text-xs rounded-lg border border-slate-300 py-1.5 px-2 bg-white text-slate-900 focus:ring-1 focus:ring-indigo-500" required>
                                            <option value="">-- Select Craftsman --</option>
                                            @foreach($craftsmen as $craftsman)
                                                <option value="{{ $craftsman->id }}">
                                                    {{ $craftsman->name }} ({{ $craftsman->mobile }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-2.5 py-1.5 bg-slate-900 text-white text-xs font-medium rounded-lg hover:bg-slate-800 transition-colors">
                                            Assign
                                        </button>
                                    </form>
                                @else
                                    <!-- Already allocated: Display Name & Mobile with option to change -->
                                    <div class="flex items-center justify-between space-x-2">
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ optional($order->craftsman)->name ?? 'Assigned' }}</span>
                                            <span class="text-xs text-slate-500 flex items-center mt-0.5">
                                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                {{ optional($order->craftsman)->mobile ?? 'N/A' }}
                                            </span>
                                        </div>
                                        <a href="{{ route('admin.orders.edit', $order->id) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium" title="Reassign Craftsman">
                                            Change
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badgeStyles = [
                                        'pending' => 'bg-slate-100 text-slate-700 border-slate-200',
                                        'allocated' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'in_progress' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badgeStyles[$order->status] ?? 'bg-slate-100 text-slate-800' }}">
                                    {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                {{ $order->due_date ? $order->due_date->format('d M, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-slate-400 hover:text-indigo-600 p-1" title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('admin.orders.edit', $order->id) }}" class="text-slate-400 hover:text-amber-600 p-1" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    @if($order->status !== 'completed')
                                        <form action="{{ route('admin.orders.force-complete', $order->id) }}" method="POST" onsubmit="return confirm('Force complete this order?');" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-slate-400 hover:text-emerald-600 p-1" title="Force Complete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Delete order?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-rose-600 p-1" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">No orders found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection