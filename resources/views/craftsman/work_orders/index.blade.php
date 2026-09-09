@extends('layouts.craftsman')

@section('title', 'My Work Orders')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">My Work Orders</h1>
        <p class="text-sm text-slate-500">Accept allocations, produce jewelry according to specifications, and submit for QC.</p>
    </div>

    <!-- Status Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-3 overflow-x-auto pb-1">
            @php
                $tabs = [
                    'allocated'    => ['label' => 'New Allocations', 'count' => $counts['allocated']],
                    'in_process'   => ['label' => 'In Process / Rework', 'count' => $counts['in_process']],
                    'for_approval' => ['label' => 'Sent for Approval', 'count' => $counts['for_approval']],
                    'completed'    => ['label' => 'Completed History', 'count' => $counts['completed']],
                ];
            @endphp
            @foreach($tabs as $key => $tab)
                <a href="{{ route('craftsman.work-orders.index', ['tab' => $key]) }}"
                   class="inline-flex items-center px-4 py-2.5 rounded-t-lg text-sm font-medium border-b-2 whitespace-nowrap {{ $currentTab === $key ? 'border-amber-600 text-amber-600 bg-white font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    {{ $tab['label'] }}
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $currentTab === $key ? 'bg-amber-100 text-amber-800 font-bold' : 'bg-slate-100 text-slate-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('craftsman.work-orders.index') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="{{ $currentTab }}">
            
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Search Keyword</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Order No, Reference, Name..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
            </div>

            <div class="w-36">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 bg-white">
                    <option value="">All</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-36">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Subcategory</label>
                <select name="subcategory" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 bg-white">
                    <option value="">All</option>
                    @foreach($subcategories as $subcat)
                        <option value="{{ $subcat }}" {{ request('subcategory') === $subcat ? 'selected' : '' }}>{{ $subcat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-36">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Design Code</label>
                <select name="design_code" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 bg-white">
                    <option value="">All</option>
                    @foreach($designCodes as $dc)
                        <option value="{{ $dc->code }}" {{ request('design_code') === $dc->code ? 'selected' : '' }}>{{ $dc->code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-36">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nickname</label>
                <select name="nickname" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 bg-white">
                    <option value="">All</option>
                    @foreach($nicknames as $nick)
                        <option value="{{ $nick }}" {{ request('nickname') === $nick ? 'selected' : '' }}>{{ $nick }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-32">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
            </div>

            <div class="w-32">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700">Filter</button>
                @if(request()->anyFilled(['search', 'category', 'subcategory', 'design_code', 'nickname', 'date_from', 'date_to']))
                    <a href="{{ route('craftsman.work-orders.index', ['tab' => $currentTab]) }}" class="px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Image</th>
                        <th class="px-4 py-3.5">Order No</th>
                        <th class="px-4 py-3.5">Product / Design</th>
                        <th class="px-4 py-3.5">Specifications</th>
                        <th class="px-4 py-3.5">Purity & Target</th>
                        <th class="px-4 py-3.5">Deadline</th>
                        <th class="px-4 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($workOrders as $order)
                        <tr class="hover:bg-slate-50 transition-colors {{ $order->status === 'returned' ? 'bg-rose-50/40' : '' }}">
                            <td class="px-4 py-3">
                                @if($order->design_image)
                                    <img src="{{ asset('storage/' . $order->design_image) }}" class="w-12 h-12 object-cover rounded-lg border">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs">N/A</div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="font-bold text-slate-900 block">{{ $order->work_order_no }}</span>
                                @if($order->status === 'returned')
                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-2xs font-bold bg-rose-100 text-rose-700">QC Defect</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $order->product_name }}</div>
                                <div class="font-mono text-xs text-indigo-600 font-semibold">{{ $order->design_code }}</div>
                            </td>

                            <td class="px-4 py-3 text-xs">
                                <div>{{ $order->category }} @if($order->size) &bull; Size: {{ $order->size }} @endif</div>
                                <div class="text-slate-400">{{ $order->quantity }} {{ $order->unit_type }}</div>
                            </td>

                            <td class="px-4 py-3 text-xs">
                                <div class="font-semibold text-slate-800">{{ $order->hallmark_purity }}</div>
                                <div class="font-bold text-amber-700">{{ number_format($order->target_weight, 3) }}g</div>
                            </td>

                            <td class="px-4 py-3 text-xs">
                                @if($order->return_due_date)
                                    <span class="text-rose-600 font-bold">Rework: {{ $order->return_due_date->format('d M Y') }}</span>
                                @else
                                    <span class="text-slate-800">{{ $order->due_date->format('d M Y') }}</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                @if($order->status === 'allocated')
                                    <form method="POST" action="{{ route('craftsman.work-orders.accept', $order) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold">
                                            Accept Job
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($order->status, ['in_process', 'returned']))
                                    <form method="POST" action="{{ route('craftsman.work-orders.submit', $order) }}" class="inline" onsubmit="return confirm('Submit this piece to Admin for quality inspection?')">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-semibold">
                                            Send for Approval
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('craftsman.work-orders.show', $order) }}" class="text-slate-600 hover:text-amber-600 text-xs font-medium">
                                    Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">No orders found in this section.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($workOrders->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $workOrders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection