@extends('layouts.craftsman')

@section('title', 'My Work Orders')

@section('content')
<div class="space-y-6 pb-24 lg:pb-0" x-data="{ 
    filterModal: false, 
    activeImageModal: false, 
    currentIndex: 0,
    ordersList: @js($workOrders->items()),
    selectedOrders: [], 
    selectAll: false,
    // Searchable Dropdown States
    categorySearch: '',
    subCategorySearch: '',
    designCodeSearch: '',
    nicknameSearch: '',
    toggleSelectAll() { 
        this.selectAll = !this.selectAll; 
        this.selectedOrders = this.selectAll ? [...document.querySelectorAll('.order-checkbox')].map(el => el.value) : []; 
    },
    openModal(index) {
        this.currentIndex = index;
        this.activeImageModal = true;
    },
    nextOrder() {
        if (this.currentIndex < this.ordersList.length - 1) {
            this.currentIndex++;
        }
    },
    prevOrder() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
        }
    },
    submitBulkAction(actionRoute) {
        if (this.selectedOrders.length === 0) {
            alert('Please select at least one work order.');
            return;
        }
        const form = document.getElementById('bulkActionForm');
        form.action = actionRoute;
        form.removeAttribute('onsubmit');
        form.submit();
    },
    // Filter options for searchable dropdowns
    get filteredCategories() {
        const categories = @js($categories ?? []);
        if (!this.categorySearch) return categories;
        return categories.filter(cat => cat.toLowerCase().includes(this.categorySearch.toLowerCase()));
    },
    get filteredSubCategories() {
        const subCategories = @js($subCategories ?? []);
        if (!this.subCategorySearch) return subCategories;
        return subCategories.filter(sub => sub.toLowerCase().includes(this.subCategorySearch.toLowerCase()));
    },
    get filteredDesignCodes() {
        const designCodes = @js($designCodes ?? []);
        if (!this.designCodeSearch) return designCodes;
        return designCodes.filter(dc => dc.code.toLowerCase().includes(this.designCodeSearch.toLowerCase()));
    },
    get filteredNicknames() {
        const nicknames = @js($nicknames ?? []);
        if (!this.nicknameSearch) return nicknames;
        return nicknames.filter(nn => nn.toLowerCase().includes(this.nicknameSearch.toLowerCase()));
    }
}">
    
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">My Work Orders</h1>
            <p class="text-sm text-slate-500 mt-1">Manage allocations, track due dates, and submit for quality inspection.</p>
        </div>
    </div>

    <!-- Pill-Shaped Status Tabs -->
    <div class="flex space-x-2 overflow-x-auto pb-2 scrollbar-none">
        @php
            $tabs = [
                'in_process'   => ['label' => 'In Process', 'count' => $counts['in_process']],
                'for_approval' => ['label' => 'For Approval', 'count' => $counts['for_approval']],
                'overdue'      => ['label' => 'Overdue', 'count' => $counts['overdue']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('craftsman.work-orders.index', ['tab' => $key, 'per_page' => $perPage ?? 15]) }}"
               class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap shadow-xs border {{ $currentTab === $key ? 'bg-slate-900 text-amber-400 border-slate-900 shadow-md' : 'bg-white text-slate-600 border-slate-200 hover:border-amber-300 hover:text-slate-800' }}">
                {{ $tab['label'] }}
                <span class="ml-2 px-2 py-0.5 text-[10px] rounded-full {{ $currentTab === $key ? 'bg-slate-800 text-amber-400' : 'bg-slate-100 text-slate-500' }}">
                    {{ $tab['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    <!-- DESKTOP FILTER BAR & PER-PAGE SELECTOR -->
    <div class="hidden lg:block bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('craftsman.work-orders.index') }}" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="tab" value="{{ $currentTab }}">
            
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Search Keyword</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Order No, Reference..." class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors">
            </div>

            <!-- Category Searchable Dropdown -->
            <div class="w-40" x-data="{ open: false, selected: '{{ request('category') }}' }">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Category</label>
                <div class="relative">
                    <button type="button" @click="open = !open" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white text-left flex items-center justify-between hover:border-amber-400 transition-colors">
                        <span x-text="selected || 'All'" class="truncate" :class="selected ? 'text-slate-800 font-medium' : 'text-slate-400'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="category" :value="selected">
                    <div x-show="open" @click.away="open = false" x-transition class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                            <input type="text" x-model="categorySearch" placeholder="Search category..." class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="py-1">
                            <button type="button" @click="selected = ''; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50 text-slate-600">All Categories</button>
                            <template x-for="category in filteredCategories" :key="category">
                                <button type="button" @click="selected = category; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50" :class="selected === category ? 'bg-amber-50 text-amber-700 font-medium' : 'text-slate-700'" x-text="category"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sub Category Searchable Dropdown -->
            <div class="w-40" x-data="{ open: false, selected: '{{ request('sub_category') }}' }">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sub Category</label>
                <div class="relative">
                    <button type="button" @click="open = !open" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white text-left flex items-center justify-between hover:border-amber-400 transition-colors">
                        <span x-text="selected || 'All'" class="truncate" :class="selected ? 'text-slate-800 font-medium' : 'text-slate-400'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="sub_category" :value="selected">
                    <div x-show="open" @click.away="open = false" x-transition class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                            <input type="text" x-model="subCategorySearch" placeholder="Search sub category..." class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="py-1">
                            <button type="button" @click="selected = ''; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50 text-slate-600">All Sub Categories</button>
                            <template x-for="subCategory in filteredSubCategories" :key="subCategory">
                                <button type="button" @click="selected = subCategory; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50" :class="selected === subCategory ? 'bg-amber-50 text-amber-700 font-medium' : 'text-slate-700'" x-text="subCategory"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Design Code Searchable Dropdown -->
            <div class="w-40" x-data="{ open: false, selected: '{{ request('design_code') }}' }">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Design Code</label>
                <div class="relative">
                    <button type="button" @click="open = !open" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white text-left flex items-center justify-between hover:border-amber-400 transition-colors">
                        <span x-text="selected || 'All'" class="truncate" :class="selected ? 'text-slate-800 font-medium' : 'text-slate-400'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="design_code" :value="selected">
                    <div x-show="open" @click.away="open = false" x-transition class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                            <input type="text" x-model="designCodeSearch" placeholder="Search code..." class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="py-1">
                            <button type="button" @click="selected = ''; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50 text-slate-600">All Design Codes</button>
                            <template x-for="dc in filteredDesignCodes" :key="dc.code">
                                <button type="button" @click="selected = dc.code; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50" :class="selected === dc.code ? 'bg-amber-50 text-amber-700 font-medium' : 'text-slate-700'" x-text="dc.code"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nickname Searchable Dropdown -->
            <div class="w-40" x-data="{ open: false, selected: '{{ request('nickname') }}' }">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nickname</label>
                <div class="relative">
                    <button type="button" @click="open = !open" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white text-left flex items-center justify-between hover:border-amber-400 transition-colors">
                        <span x-text="selected || 'All'" class="truncate" :class="selected ? 'text-slate-800 font-medium' : 'text-slate-400'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="nickname" :value="selected">
                    <div x-show="open" @click.away="open = false" x-transition class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                            <input type="text" x-model="nicknameSearch" placeholder="Search nickname..." class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="py-1">
                            <button type="button" @click="selected = ''; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50 text-slate-600">All Nicknames</button>
                            <template x-for="nickname in filteredNicknames" :key="nickname">
                                <button type="button" @click="selected = nickname; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50" :class="selected === nickname ? 'bg-amber-50 text-amber-700 font-medium' : 'text-slate-700'" x-text="nickname"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-32">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-2 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors">
            </div>

            <div class="w-32">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-2 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors">
            </div>

            <div class="w-28">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Show</label>
                <select name="per_page" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-colors" onchange="this.form.submit()">
                    @foreach([10, 15, 25, 50, 100] as $val)
                        <option value="{{ $val }}" {{ ($perPage ?? 15) == $val ? 'selected' : '' }}>{{ $val }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2 pb-0.5">
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 shadow-lg shadow-slate-900/20 transition-all">Filter</button>
                @if(request()->anyFilled(['search', 'category', 'sub_category', 'design_code', 'nickname', 'date_from', 'date_to']))
                    <a href="{{ route('craftsman.work-orders.index', ['tab' => $currentTab]) }}" class="px-4 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-colors">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- COMMON BULK FORM WRAPPER -->
    <form id="bulkActionForm" method="POST" onsubmit="event.preventDefault();">
        @csrf

        <!-- MOBILE VIEW: Select All Bar & 2-Column Grid Cards -->
        <div class="block lg:hidden">
            <!-- Select All Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-4 flex items-center justify-between sticky top-0 z-20">
                <label class="flex items-center space-x-3 cursor-pointer select-none">
                    <input type="checkbox" @click="toggleSelectAll()" x-model="selectAll" class="w-5 h-5 rounded border-slate-300 text-amber-600 focus:ring-amber-600">
                    <span class="text-sm font-bold text-slate-800">Select All Items</span>
                </label>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-md" x-text="selectedOrders.length + ' selected'"></span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @forelse($workOrders as $index => $order)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between relative group {{ $order->status === 'returned' ? 'border-rose-300 bg-rose-50/20' : '' }}">
                        <div class="absolute top-2 left-2 right-2 z-10 flex justify-between items-center pointer-events-none">
                            @if($order->status === 'returned')
                                <span class="px-2 py-0.5 text-[9px] uppercase tracking-wide bg-rose-100 text-rose-700 font-extrabold rounded-md shadow-xs pointer-events-auto border border-rose-200">QC Defect</span>
                            @else
                                <span></span>
                            @endif
                            <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" x-model="selectedOrders" class="order-checkbox w-5 h-5 rounded border-slate-300 text-amber-600 focus:ring-amber-600 shadow-md pointer-events-auto bg-white/90">
                        </div>

                        <div>
                            <!-- Tap Image to open Interactive Carousel Modal -->
                            <div @click="openModal({{ $index }})" class="relative bg-slate-50 aspect-square w-full overflow-hidden border-b border-slate-100 flex items-center justify-center cursor-pointer group">
                                @if($order->design_image)
                                    <img src="{{ asset('storage/' . $order->design_image) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="text-slate-300 text-xs font-bold">N/A</div>
                                @endif
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                            </div>

                            <div class="p-3 space-y-2">
                                <div class="text-center">
                                    <span class="inline-block px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $order->design_code }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-baseline pt-1">
                                    <span class="text-xs font-bold text-slate-800 truncate">{{ $order->product_name }}</span>
                                    <span class="text-xs font-extrabold text-amber-600">{{ number_format($order->target_weight, 3) }}g</span>
                                </div>
                                <div class="text-[10px] text-slate-500 flex justify-between pt-2 border-t border-slate-100">
                                    <span class="font-semibold">{{ $order->work_order_no }}</span>
                                    <span>{{ $order->due_date ? \Carbon\Carbon::parse($order->due_date)->format('d M') : '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 p-12 text-center text-slate-400 text-sm bg-white rounded-2xl border border-slate-200 border-dashed">No orders found.</div>
                @endforelse
            </div>
            <div class="mt-6">{{ $workOrders->links() }}</div>
        </div>

        <!-- DESKTOP VIEW: Standard Table -->
        <div class="hidden lg:block bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center flex-wrap gap-3">
                <div class="flex items-center gap-2">
                    <button type="button" @click="submitBulkAction('{{ route('craftsman.work-orders.bulk-print') }}')" class="px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl shadow-lg shadow-slate-900/20 hover:bg-slate-800 transition-all">Print Selected Slips</button>
                    @if($currentTab === 'in_process')
                        <button type="button" @click="submitBulkAction('{{ route('craftsman.work-orders.bulk-submit') }}')" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 transition-all">Bulk Submit for Approval</button>
                    @endif
                </div>
                <span class="text-xs text-slate-500 font-medium">Showing {{ $workOrders->firstItem() ?? 0 }} to {{ $workOrders->lastItem() ?? 0 }} of {{ $workOrders->total() }} entries</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5"><input type="checkbox" @click="toggleSelectAll()" x-model="selectAll" class="rounded border-slate-300 text-amber-600 focus:ring-amber-600"></th>
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
                                <td class="px-4 py-3"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}" x-model="selectedOrders" class="order-checkbox rounded border-slate-300 text-amber-600 focus:ring-amber-600"></td>
                                <td class="px-4 py-3">
                                    @if($order->design_image)
                                        <img src="{{ asset('storage/' . $order->design_image) }}" class="w-12 h-12 object-cover rounded-xl border border-slate-200 shadow-sm">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold">N/A</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-bold text-slate-900 block">{{ $order->work_order_no }}</span>
                                    @if($order->status === 'returned')
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">QC Defect</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $order->product_name }}</div>
                                    <div class="font-mono text-xs text-amber-600 font-semibold">{{ $order->design_code }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="text-slate-700">{{ $order->category }} @if($order->size) &bull; Size: {{ $order->size }} @endif</div>
                                    <div class="text-slate-400">{{ $order->quantity }} {{ $order->unit_type }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="font-semibold text-slate-800">{{ $order->hallmark_purity }}</div>
                                    <div class="font-bold text-amber-600">{{ number_format($order->target_weight, 3) }}g</div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if($order->return_due_date)
                                        <span class="text-rose-600 font-bold bg-rose-50 px-2 py-1 rounded-md border border-rose-100">Rework: {{ \Carbon\Carbon::parse($order->return_due_date)->format('d M Y') }}</span>
                                    @else
                                        <span class="text-slate-700">{{ optional($order->due_date)->format('d M Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    @if(in_array($order->status, ['in_process', 'returned']))
                                        <button type="button" onclick="if(confirm('Submit this piece for QC inspection?')) { submitSingleAction('{{ route('craftsman.work-orders.submit', $order) }}'); }" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">Send Approval</button>
                                    @endif
                                    <a href="{{ route('craftsman.work-orders.show', $order) }}" class="text-slate-500 hover:text-amber-600 text-xs font-bold transition-colors">Details &rarr;</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">No orders found in this section.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($workOrders->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $workOrders->links() }}
                </div>
            @endif
        </div>
    </form>

    <!-- INTERACTIVE NEXT/PREV CAROUSEL MODAL FOR MOBILE -->
    <div x-show="activeImageModal" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
        <div @click.away="activeImageModal = false" class="bg-white w-full max-w-sm rounded-3xl overflow-hidden shadow-2xl relative border border-slate-200">
            <button @click="activeImageModal = false" class="absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-slate-900/70 text-white flex items-center justify-center font-bold text-lg hover:bg-slate-900 transition-colors">&times;</button>
            <button @click="prevOrder()" :class="currentIndex === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-900/80'" class="absolute top-1/2 left-2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-slate-900/60 text-white flex items-center justify-center text-xl font-bold shadow-md transition-all">&lsaquo;</button>
            <button @click="nextOrder()" :class="currentIndex >= ordersList.length - 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-900/80'" class="absolute top-1/2 right-2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-slate-900/60 text-white flex items-center justify-center text-xl font-bold shadow-md transition-all">&rsaquo;</button>
            
            <div class="relative bg-slate-100 aspect-square w-full flex items-center justify-center overflow-hidden">
                <template x-if="ordersList[currentIndex] && ordersList[currentIndex].design_image">
                    <img :src="'/storage/' + ordersList[currentIndex].design_image" class="w-full h-full object-contain">
                </template>
                <template x-if="!ordersList[currentIndex] || !ordersList[currentIndex].design_image">
                    <span class="text-slate-400 text-sm font-bold">No Image Available</span>
                </template>
                <span class="absolute bottom-2 left-3 bg-slate-900/60 text-white text-[10px] px-2 py-0.5 rounded-full backdrop-blur-sm" x-text="(currentIndex + 1) + ' of ' + ordersList.length"></span>
            </div>
            
            <div class="p-5 space-y-3 bg-white">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-400 uppercase">Design Code</span>
                    <span class="text-sm font-extrabold text-amber-600" x-text="ordersList[currentIndex]?.design_code"></span>
                </div>
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-400 uppercase">Product Name</span>
                    <span class="text-xs font-bold text-slate-800" x-text="ordersList[currentIndex]?.product_name"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center pt-1">
                    <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Weight</span>
                        <span class="text-xs font-extrabold text-amber-700" x-text="Number(ordersList[currentIndex]?.target_weight || 0).toFixed(3) + 'g'"></span>
                    </div>
                    <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Size</span>
                        <span class="text-xs font-bold text-slate-800" x-text="ordersList[currentIndex]?.size || 'N/A'"></span>
                    </div>
                    <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Quantity</span>
                        <span class="text-xs font-bold text-slate-800" x-text="ordersList[currentIndex]?.quantity"></span>
                    </div>
                </div>
                <div class="pt-2 flex gap-2">
                    <a :href="'/craftsman/work-orders/' + ordersList[currentIndex]?.id" class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-center text-xs font-bold shadow-lg shadow-slate-900/20 hover:bg-slate-800 transition-all">View Full Sheet &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- MOBILE BOTTOM APP BAR -->
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-slate-900 text-white flex justify-around items-center py-3 px-4 shadow-2xl lg:hidden border-t border-slate-800">
        <button type="button" @click="filterModal = true" class="flex flex-col items-center space-y-1 text-xs font-medium focus:outline-none group">
            <svg class="w-6 h-6 text-slate-400 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            <span class="text-slate-400 group-hover:text-amber-400 transition-colors">Filter</span>
        </button>
        
        @if($currentTab === 'in_process')
            <button type="button" @click="submitBulkAction('{{ route('craftsman.work-orders.bulk-submit') }}')" class="flex flex-col items-center space-y-1 text-xs font-medium focus:outline-none group">
                <svg class="w-6 h-6 text-slate-400 group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-slate-400 group-hover:text-emerald-400 transition-colors">Submit</span>
            </button>
        @endif

        <button type="button" @click="submitBulkAction('{{ route('craftsman.work-orders.bulk-print') }}')" class="flex flex-col items-center space-y-1 text-xs font-medium focus:outline-none group">
            <svg class="w-6 h-6 text-slate-400 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span class="text-slate-400 group-hover:text-amber-400 transition-colors">Print</span>
        </button>
    </div>

    <!-- MOBILE FILTER & SEARCH MODAL WITH SEARCHABLE DROPDOWNS -->
    <div x-show="filterModal" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div @click.away="filterModal = false" class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-2xl p-6 space-y-5 max-h-[85vh] overflow-y-auto shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <h3 class="font-bold text-slate-800 text-lg">Filter & Search</h3>
                <button @click="filterModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-colors">&times;</button>
            </div>
            <form method="GET" action="{{ route('craftsman.work-orders.index') }}" class="space-y-4">
                <input type="hidden" name="tab" value="{{ $currentTab }}">
                
                <!-- Search Keyword -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Search Keyword</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Order No, Reference, Name..." class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors">
                </div>

                <!-- Category Searchable Dropdown -->
                <div class="relative" x-data="{ open: false, selected: '{{ request('category') }}' }">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Category</label>
                    <button type="button" @click="open = !open" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white text-left flex items-center justify-between hover:border-amber-400 transition-colors">
                        <span x-text="selected || 'All Categories'" class="truncate" :class="selected ? 'text-slate-800 font-medium' : 'text-slate-400'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="category" :value="selected">
                    <div x-show="open" @click.away="open = false" x-transition class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                            <input type="text" x-model="categorySearch" placeholder="Search category..." class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="py-1">
                            <button type="button" @click="selected = ''; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50 text-slate-600">All Categories</button>
                            <template x-for="category in filteredCategories" :key="category">
                                <button type="button" @click="selected = category; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50" :class="selected === category ? 'bg-amber-50 text-amber-700 font-medium' : 'text-slate-700'" x-text="category"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Sub Category Searchable Dropdown -->
                <div class="relative" x-data="{ open: false, selected: '{{ request('sub_category') }}' }">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sub Category</label>
                    <button type="button" @click="open = !open" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white text-left flex items-center justify-between hover:border-amber-400 transition-colors">
                        <span x-text="selected || 'All Sub Categories'" class="truncate" :class="selected ? 'text-slate-800 font-medium' : 'text-slate-400'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="sub_category" :value="selected">
                    <div x-show="open" @click.away="open = false" x-transition class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                            <input type="text" x-model="subCategorySearch" placeholder="Search sub category..." class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="py-1">
                            <button type="button" @click="selected = ''; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50 text-slate-600">All Sub Categories</button>
                            <template x-for="subCategory in filteredSubCategories" :key="subCategory">
                                <button type="button" @click="selected = subCategory; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50" :class="selected === subCategory ? 'bg-amber-50 text-amber-700 font-medium' : 'text-slate-700'" x-text="subCategory"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Design Code Searchable Dropdown -->
                <div class="relative" x-data="{ open: false, selected: '{{ request('design_code') }}' }">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Design Code</label>
                    <button type="button" @click="open = !open" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white text-left flex items-center justify-between hover:border-amber-400 transition-colors">
                        <span x-text="selected || 'All Design Codes'" class="truncate" :class="selected ? 'text-slate-800 font-medium' : 'text-slate-400'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="design_code" :value="selected">
                    <div x-show="open" @click.away="open = false" x-transition class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                            <input type="text" x-model="designCodeSearch" placeholder="Search code..." class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="py-1">
                            <button type="button" @click="selected = ''; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50 text-slate-600">All Design Codes</button>
                            <template x-for="dc in filteredDesignCodes" :key="dc.code">
                                <button type="button" @click="selected = dc.code; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50" :class="selected === dc.code ? 'bg-amber-50 text-amber-700 font-medium' : 'text-slate-700'" x-text="dc.code"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Nickname Searchable Dropdown -->
                <div class="relative" x-data="{ open: false, selected: '{{ request('nickname') }}' }">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nickname</label>
                    <button type="button" @click="open = !open" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white text-left flex items-center justify-between hover:border-amber-400 transition-colors">
                        <span x-text="selected || 'All Nicknames'" class="truncate" :class="selected ? 'text-slate-800 font-medium' : 'text-slate-400'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="nickname" :value="selected">
                    <div x-show="open" @click.away="open = false" x-transition class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                            <input type="text" x-model="nicknameSearch" placeholder="Search nickname..." class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="py-1">
                            <button type="button" @click="selected = ''; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50 text-slate-600">All Nicknames</button>
                            <template x-for="nickname in filteredNicknames" :key="nickname">
                                <button type="button" @click="selected = nickname; open = false" class="w-full px-3 py-2 text-left text-xs hover:bg-amber-50" :class="selected === nickname ? 'bg-amber-50 text-amber-700 font-medium' : 'text-slate-700'" x-text="nickname"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors">
                    </div>
                </div>

                <!-- Items Per Page -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Items Per Page</label>
                    <select name="per_page" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-colors">
                        @foreach([10, 15, 25, 50, 100] as $val)
                            <option value="{{ $val }}" {{ ($perPage ?? 15) == $val ? 'selected' : '' }}>{{ $val }} items</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                    <button type="submit" class="flex-1 py-3 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 shadow-lg shadow-slate-900/20 transition-all">Apply Filters</button>
                    <a href="{{ route('craftsman.work-orders.index', ['tab' => $currentTab]) }}" class="px-6 py-3 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 text-center transition-colors">Reset</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SINGLE ACTION FORM FOR JS SUBMISSIONS -->
<form id="singleActionForm" method="POST" class="hidden">
    @csrf
</form>

<script>
    function submitSingleAction(actionUrl) {
        const form = document.getElementById('singleActionForm');
        form.action = actionUrl;
        form.submit();
    }
</script>
@endsection