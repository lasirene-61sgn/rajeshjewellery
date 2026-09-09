@extends('layouts.admin')

@section('title', 'Work Orders')

@section('content')
<div x-data="workOrderManager()" class="space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Work Orders</h1>
            <p class="text-sm text-slate-500">Track jewelry fabrication, allocate craftsmen, and approve finished pieces.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.work_orders.create', ['back_url' => request()->fullUrl()]) }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Work Order
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-2 overflow-x-auto pb-1" aria-label="Tabs">
            @php
                $tabs = [
                    'all'          => ['label' => 'All Orders', 'count' => $counts['all']],
                    'pending'      => ['label' => 'Pending', 'count' => $counts['pending']],
                    'allocated'    => ['label' => 'Allocated', 'count' => $counts['allocated']],
                    'in_process'   => ['label' => 'In Process / Rework', 'count' => $counts['in_process']],
                    'for_approval' => ['label' => 'For Approval', 'count' => $counts['for_approval']],
                    'completed'    => ['label' => 'Completed', 'count' => $counts['completed']],
                    'overdue'      => ['label' => 'Overdue', 'count' => $counts['overdue']],
                ];
            @endphp

            @foreach($tabs as $key => $tab)
                <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => 1]) }}"
                   class="inline-flex items-center px-4 py-2.5 rounded-t-lg text-sm font-medium transition-colors border-b-2 whitespace-nowrap {{ $currentTab === $key ? 'border-indigo-600 text-indigo-600 bg-white font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                    {{ $tab['label'] }}
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $currentTab === $key ? 'bg-indigo-100 text-indigo-700 font-bold' : 'bg-slate-100 text-slate-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.work_orders.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <input type="hidden" name="tab" value="{{ $currentTab }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}">

            <!-- Keyword Search -->
            <div class="lg:col-span-2">
                <label class="block text-2xs uppercase font-bold text-slate-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Order No, Product, Ref, Code, Nickname..." class="w-full px-3 py-2 text-xs border rounded-lg focus:ring-2 focus:ring-indigo-500 border-slate-300">
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-2xs uppercase font-bold text-slate-500 mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 text-xs border rounded-lg focus:ring-2 focus:ring-indigo-500 border-slate-300 bg-white">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Subcategory Filter -->
            <div>
                <label class="block text-2xs uppercase font-bold text-slate-500 mb-1">Subcategory</label>
                <select name="subcategory" class="w-full px-3 py-2 text-xs border rounded-lg focus:ring-2 focus:ring-indigo-500 border-slate-300 bg-white">
                    <option value="">All Subcategories</option>
                    @foreach($subcategories as $subcat)
                        <option value="{{ $subcat }}" {{ request('subcategory') === $subcat ? 'selected' : '' }}>{{ $subcat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Craftsman Filter -->
            <div>
                <label class="block text-2xs uppercase font-bold text-slate-500 mb-1">Craftsman</label>
                <select name="craftsman_id" class="w-full px-3 py-2 text-xs border rounded-lg focus:ring-2 focus:ring-indigo-500 border-slate-300 bg-white">
                    <option value="">All Craftsmen</option>
                    @foreach($craftsmen as $craftsman)
                        <option value="{{ $craftsman->id }}" {{ request('craftsman_id') == $craftsman->id ? 'selected' : '' }}>
                            {{ $craftsman->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Design Code Filter Dropdown -->
            <div>
                <label class="block text-2xs uppercase font-bold text-slate-500 mb-1">Design Code</label>
                <select name="design_code" class="w-full px-3 py-2 text-xs border rounded-lg focus:ring-2 focus:ring-indigo-500 border-slate-300 bg-white">
                    <option value="">All Design Codes</option>
                    @foreach($designCodes as $design)
                        <option value="{{ $design->code }}" {{ request('design_code') === $design->code ? 'selected' : '' }}>
                            {{ $design->code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Nickname Filter Dropdown -->
            <div>
                <label class="block text-2xs uppercase font-bold text-slate-500 mb-1">Nickname</label>
                <select name="nickname" class="w-full px-3 py-2 text-xs border rounded-lg focus:ring-2 focus:ring-indigo-500 border-slate-300 bg-white">
                    <option value="">All Nicknames</option>
                    @foreach($nicknames as $nick)
                        <option value="{{ $nick }}" {{ request('nickname') === $nick ? 'selected' : '' }}>
                            {{ $nick }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filters -->
            <div>
                <label class="block text-2xs uppercase font-bold text-slate-500 mb-1">Due Date From</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full px-3 py-2 text-xs border rounded-lg focus:ring-2 focus:ring-indigo-500 border-slate-300">
            </div>

            <div>
                <label class="block text-2xs uppercase font-bold text-slate-500 mb-1">Due Date To</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full px-3 py-2 text-xs border rounded-lg focus:ring-2 focus:ring-indigo-500 border-slate-300">
            </div>

            <!-- Filter Buttons -->
            <div class="lg:col-span-4 flex items-end gap-2 pt-1">
                <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-black text-white text-xs font-semibold rounded-lg transition-colors">
                    Apply Filters
                </button>
                @if(request()->anyFilled(['search', 'category', 'subcategory', 'craftsman_id', 'design_code', 'from_date', 'to_date']))
                    <a href="{{ route('admin.work_orders.index', ['tab' => $currentTab, 'per_page' => $perPage]) }}" class="px-3 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-xs font-medium">
                        Reset Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bulk Allocation Bar -->
    <div x-show="selectedOrders.length > 0" x-cloak class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center text-xs font-semibold text-indigo-900">
            <span x-text="selectedOrders.length" class="mr-1 font-bold text-sm"></span> orders selected for bulk allocation
        </div>
        <form method="POST" action="{{ route('admin.work_orders.bulk-allocate') }}" class="flex items-center gap-2">
            @csrf
            <template x-for="id in selectedOrders" :key="id">
                <input type="hidden" name="order_ids[]" :value="id">
            </template>
            <select name="craftsman_id" required class="px-3 py-1.5 border border-indigo-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Choose Craftsman --</option>
                @foreach($craftsmen as $craftsman)
                    <option value="{{ $craftsman->id }}">{{ $craftsman->name }} ({{ $craftsman->mobile }})</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold">
                Allocate Selected
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="p-4 w-4">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="px-4 py-3.5">Image</th>
                        <th class="px-4 py-3.5">Order / Ref No</th>
                        <th class="px-4 py-3.5">Product & Design Code (Nickname)</th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5">Weight / Qty</th>
                        <th class="px-4 py-3.5">Craftsman</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Due Date</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($workOrders as $order)
                        <tr class="hover:bg-slate-50 transition-colors {{ $order->status === 'returned' ? 'bg-amber-50/40' : '' }}">
                            <td class="p-4">
                                <input type="checkbox" :value="{{ $order->id }}" x-model="selectedOrders" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </td>

                            <td class="px-4 py-3">
                                @if($order->design_image)
                                    <img src="{{ asset('storage/' . $order->design_image) }}" alt="Design" class="w-12 h-12 object-cover rounded-lg border border-slate-200 shadow-sm">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-medium border border-slate-200">
                                        N/A
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 font-mono">{{ $order->work_order_no }}</div>
                                @if($order->reference_no)
                                    <div class="text-xs text-slate-400">Ref: {{ $order->reference_no }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $order->product_name }}</div>
                                <div class="text-xs font-mono font-semibold text-indigo-600 flex items-center gap-1 mt-0.5">
                                    <span>{{ $order->design_code }}</span>
                                    @if($order->design_nickname)
                                        <span class="font-sans text-slate-500 font-normal">
                                            ({{ $order->design_nickname }})
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="text-slate-700 font-medium">{{ $order->category }}</div>
                                @if($order->subcategory)
                                    <div class="text-xs text-slate-400">{{ $order->subcategory }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ number_format($order->target_weight, 3) }}g</div>
                                <div class="text-xs text-slate-400">{{ $order->quantity }} {{ $order->unit_type }}</div>
                            </td>

                            <td class="px-4 py-3">
                                @if($order->craftsman)
                                    <span class="font-medium text-slate-800 block">{{ $order->craftsman->name }}</span>
                                    @if($order->status === 'allocated')
                                        <form method="POST" action="{{ route('admin.work_orders.undo', $order) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs text-rose-600 hover:underline">Undo</button>
                                        </form>
                                    @endif
                                @else
                                    <button @click="openAllocateModal({{ $order->id }}, '{{ $order->work_order_no }}')" class="text-xs font-semibold text-indigo-600 hover:underline">
                                        + Assign Craftsman
                                    </button>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @php
                                    $badge = match($order->status) {
                                        'pending'      => 'bg-slate-100 text-slate-700 border-slate-200',
                                        'allocated'    => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'in_process'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'for_approval' => 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse',
                                        'returned'     => 'bg-rose-50 text-rose-700 border-rose-200 font-bold',
                                        'completed'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default        => 'bg-slate-100 text-slate-600 border-slate-200'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $badge }}">
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    @if($order->status === 'returned') (x{{ $order->return_count }}) @endif
                                </span>
                            </td>

                            <td class="px-4 py-3 text-xs">
                                @if($order->return_due_date)
                                    <div class="text-rose-600 font-bold">Rework: {{ \Carbon\Carbon::parse($order->return_due_date)->format('d M Y') }}</div>
                                    <div class="line-through text-slate-400">{{ \Carbon\Carbon::parse($order->due_date)->format('d M Y') }}</div>
                                @else
                                    <div class="{{ \Carbon\Carbon::parse($order->due_date)->isPast() && $order->status !== 'completed' ? 'text-rose-600 font-bold' : 'text-slate-700' }}">
                                        {{ \Carbon\Carbon::parse($order->due_date)->format('d M Y') }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                @if($order->status === 'for_approval')
                                    <form method="POST" action="{{ route('admin.work_orders.approve', $order) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-semibold">
                                            Approve
                                        </button>
                                    </form>

                                    <button @click="openReturnModal({{ $order->id }}, '{{ $order->work_order_no }}')" class="px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-semibold">
                                        Return
                                    </button>
                                @endif

                                <a href="{{ route('admin.work_orders.print', $order) }}" target="_blank" class="text-slate-500 hover:text-slate-800 text-xs font-medium">
                                    Print
                                </a>

                                <a href="{{ route('admin.work_orders.show', [$order, 'back_url' => request()->fullUrl()]) }}" class="text-slate-600 hover:text-indigo-600 text-xs font-medium">
                                    View
                                </a>

                                <a href="{{ route('admin.work_orders.edit', [$order, 'back_url' => request()->fullUrl()]) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-400">
                                No work orders found matching this filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <form method="GET" action="{{ route('admin.work_orders.index') }}" class="flex items-center gap-2 text-xs text-slate-500">
                @foreach(request()->except(['per_page', 'page']) as $name => $val)
                    <input type="hidden" name="{{ $name }}" value="{{ $val }}">
                @endforeach
                <span>Show:</span>
                <select name="per_page" onchange="this.form.submit()" class="border border-slate-300 rounded px-2 py-1 bg-white text-xs font-semibold text-slate-700">
                    @foreach($allowedPerPage as $limit)
                        <option value="{{ $limit }}" {{ (int) $perPage === $limit ? 'selected' : '' }}>{{ $limit }}</option>
                    @endforeach
                </select>
                <span>entries per page</span>
            </form>

            <div>
                {{ $workOrders->links() }}
            </div>
        </div>
    </div>

    <!-- Modal: Allocate -->
    <div x-show="allocateModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="allocateModalOpen = false" class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-slate-800 mb-2">Allocate Work Order</h3>
            <p class="text-xs text-slate-500 mb-4">Assign Order <span class="font-bold font-mono text-indigo-600" x-text="selectedOrderNo"></span> to a craftsman.</p>
            
            <form :action="'/admin/work-orders/' + selectedOrderId + '/allocate'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Select Craftsman *</label>
                    <select name="craftsman_id" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white">
                        <option value="">-- Choose Craftsman --</option>
                        @foreach($craftsmen as $craftsman)
                            <option value="{{ $craftsman->id }}">{{ $craftsman->name }} ({{ $craftsman->mobile }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="allocateModalOpen = false" class="px-4 py-2 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg">Confirm Allocation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Return -->
    <div x-show="returnModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="returnModalOpen = false" class="bg-white rounded-xl max-w-lg w-full p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-rose-700 mb-1">Return Order for Rework</h3>
            <p class="text-xs text-slate-500 mb-4">Order <span class="font-bold font-mono text-slate-900" x-text="selectedOrderNo"></span> will be sent back to craftsman.</p>

            <form :action="'/admin/work-orders/' + selectedOrderId + '/return'" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">New Rework Due Date *</label>
                    <input type="date" name="return_due_date" required min="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Reason / Defect Description *</label>
                    <textarea name="return_reason" required rows="3" placeholder="Explain the defect or damage..." class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Defect Proof Image (Optional)</label>
                    <input type="file" name="return_image" accept="image/*" class="w-full text-xs text-slate-500">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="returnModalOpen = false" class="px-4 py-2 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg">Send Back to Craftsman</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function workOrderManager() {
        return {
            selectedOrders: [],
            allocateModalOpen: false,
            returnModalOpen: false,
            selectedOrderId: null,
            selectedOrderNo: '',

            toggleSelectAll(e) {
                if (e.target.checked) {
                    this.selectedOrders = @json($workOrders->pluck('id'));
                } else {
                    this.selectedOrders = [];
                }
            },
            openAllocateModal(id, no) {
                this.selectedOrderId = id;
                this.selectedOrderNo = no;
                this.allocateModalOpen = true;
            },
            openReturnModal(id, no) {
                this.selectedOrderId = id;
                this.selectedOrderNo = no;
                this.returnModalOpen = true;
            }
        }
    }
</script>
@endsection