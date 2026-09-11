@extends('layouts.admin')

@section('title', 'Work Order Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Work Order Details</h1>
            <p class="text-sm text-slate-500 font-mono">{{ $workOrder->work_order_no }}</p>
        </div>
        <a href="{{ $backUrl ?? route('admin.work_orders.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
            Back to Orders
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 space-y-6">
        @if($workOrder->design_image)
            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-2">Design Image</span>
                <img src="{{ asset('storage/' . $workOrder->design_image) }}" alt="Design Image" class="w-32 h-32 object-cover rounded-xl border border-slate-200 shadow-sm">
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Reference Number</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->reference_no ?? 'N/A' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Product Name</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->product_name }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Design Code & Nickname</span>
                <p class="text-sm font-mono font-bold text-indigo-600">
                    {{ $workOrder->design_code }} 
                    @if($workOrder->effective_nickname)
                        <span class="font-sans font-normal text-slate-600">({{ $workOrder->effective_nickname }})</span>
                    @endif
                </p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Unit Type & Quantity</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->quantity }} {{ $workOrder->unit_type }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Category / Subcategory</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->category ?? 'N/A' }} {{ $workOrder->subcategory ? ' / ' . $workOrder->subcategory : '' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Screw Type</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->screw_type ?? 'None / N/A' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Rhodium Polish</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->rhodium_polish ? 'Yes' : 'No' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Size & Length</span>
                <p class="text-sm font-medium text-slate-800">Size: {{ $workOrder->size ?? 'N/A' }} | Length: {{ $workOrder->length ?? 'N/A' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Target Weight</span>
                <p class="text-sm font-medium text-slate-800">{{ number_format($workOrder->target_weight, 3) }}g</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Gold Hallmark / Purity</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->hallmark_purity ?? 'N/A' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Due Date</span>
                <p class="text-sm font-medium text-slate-800">{{ optional($workOrder->due_date)->format('d M Y') }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Job Type</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->job_type ?? 'N/A' }}</p>
            </div>
        </div>

        <div>
            <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Instructions / Notes</span>
            <p class="text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100">{{ $workOrder->instructions ?? 'No workshop instructions provided.' }}</p>
        </div>
    </div>
</div>
@endsection