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

    @if($workOrder->status === 'rejected')
        <div class="p-5 bg-rose-50 border border-rose-200 rounded-2xl space-y-2 shadow-sm">
            <div class="flex items-center gap-2 text-rose-700 font-bold text-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                Order Rejected by Craftsman ({{ optional($workOrder->craftsman)->name ?? 'Assigned Craftsman' }})
            </div>
            <p class="text-sm text-rose-900 bg-white/80 p-3 rounded-xl border border-rose-200">
                <strong>Reason for Rejection:</strong> {{ $workOrder->reject_reason ?? 'No reason provided.' }}
            </p>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 space-y-6">
        @if($workOrder->design_image)
            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-2">Design Image</span>
                <div class="relative inline-block">
                    <img src="{{ asset('storage/' . $workOrder->design_image) }}" alt="Design Image" class="w-36 h-36 object-cover rounded-xl border border-slate-200 shadow-sm">
                    @if($workOrder->has_seal)
                        <span class="absolute bottom-2 right-2 bg-amber-500 text-white text-[11px] font-black px-2 py-0.5 rounded shadow uppercase">
                            916 SEAL
                        </span>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Reference Number</span>
                <p class="text-sm font-medium text-slate-800 flex items-center gap-2">
                    <span>{{ $workOrder->reference_no ?? 'N/A' }}</span>
                    <!-- @if($workOrder->has_seal)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            916 SEAL
                        </span>
                    @endif -->
                </p>
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
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Quantity & Progress</span>
                <p class="text-sm font-medium text-slate-800">
                    Total: <strong class="text-slate-900">{{ $workOrder->quantity }} {{ $workOrder->unit_type }}</strong>
                    <span class="mx-1 text-slate-300">|</span>
                    Done: <strong class="text-emerald-700">{{ $workOrder->completed_quantity ?? 0 }}</strong>
                    <span class="mx-1 text-slate-300">|</span>
                    Bal: <strong class="text-amber-600">{{ $workOrder->balance_quantity }}</strong>
                </p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Category / Subcategory</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->category ?? 'N/A' }} {{ $workOrder->subcategory ? ' / ' . $workOrder->subcategory : '' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Screw Type</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->screw_type ?? 'None / N/A' }}</p>
            </div>

            <!-- <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Rhodium Polish</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->rhodium_polish ? 'Yes' : 'No' }}</p>
            </div> -->

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