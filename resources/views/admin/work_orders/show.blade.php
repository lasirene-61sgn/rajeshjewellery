@extends('layouts.admin')

@section('title', 'Work Order - ' . $workOrder->work_order_no)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $workOrder->work_order_no }}</h1>
            <p class="text-sm text-slate-500">Created on {{ $workOrder->created_at->format('d M Y, h:i A') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.work_orders.print', $workOrder) }}" target="_blank" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-sm">
                Print Card
            </a>
            <a href="{{ route('admin.work_orders.edit', [$workOrder, 'back_url' => $backUrl]) }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">
                Edit
            </a>
            <a href="{{ $backUrl }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                Back
            </a>
        </div>
    </div>

    @if($workOrder->status === 'returned')
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <span class="text-xs font-bold uppercase text-rose-700 tracking-wider">Rework Order Notice (x{{ $workOrder->return_count }})</span>
                <p class="text-sm font-medium text-rose-900 mt-1">{{ $workOrder->return_reason }}</p>
                <div class="text-xs text-rose-700 mt-2">Extended Due Date: <span class="font-bold">{{ $workOrder->return_due_date?->format('d M Y') }}</span></div>
            </div>
            @if($workOrder->return_image)
                <img src="{{ asset('storage/' . $workOrder->return_image) }}" alt="Defect" class="w-20 h-20 object-cover rounded-lg border border-rose-300">
            @endif
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row gap-6">
            <div class="sm:w-1/3">
                @if($workOrder->design_image)
                    <img src="{{ asset('storage/' . $workOrder->design_image) }}" alt="Design" class="w-full h-56 object-cover rounded-xl border border-slate-200 shadow-sm">
                @else
                    <div class="w-full h-56 rounded-xl bg-slate-50 border border-dashed border-slate-300 flex items-center justify-center text-slate-400 text-sm">
                        No Image Attached
                    </div>
                @endif
            </div>

            <div class="sm:w-2/3 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Product Name</span>
                    <p class="font-semibold text-slate-800">{{ $workOrder->product_name }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Design Code & Nickname</span>
                    <p class="font-mono font-bold text-indigo-600">{{ $workOrder->design_code }}</p>
                    @if($workOrder->design_nickname)
                        <span class="text-xs text-slate-500 font-medium">({{ $workOrder->design_nickname }})</span>
                    @endif
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Category / Sub</span>
                    <p class="font-medium text-slate-800">{{ $workOrder->category }} ({{ $workOrder->subcategory ?? 'General' }})</p>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Gold Purity</span>
                    <p class="font-medium text-slate-800">{{ $workOrder->hallmark_purity }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Target Weight</span>
                    <p class="font-bold text-slate-800">{{ number_format($workOrder->target_weight, 3) }} g</p>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Quantity / Unit</span>
                    <p class="font-medium text-slate-800">{{ $workOrder->quantity }} {{ $workOrder->unit_type }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Craftsman</span>
                    <p class="font-medium text-slate-800">{{ $workOrder->craftsman->name ?? 'Unassigned' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Due Date</span>
                    <p class="font-bold text-slate-800">{{ $workOrder->due_date?->format('d M Y') }}</p>
                </div>
            </div>
        </div>

        @if($workOrder->instructions)
            <hr class="my-6 border-slate-100">
            <div>
                <span class="text-xs font-semibold uppercase text-slate-400">Workshop Instructions</span>
                <p class="text-sm text-slate-700 mt-1 bg-slate-50 p-3 rounded-lg border border-slate-200">{{ $workOrder->instructions }}</p>
            </div>
        @endif
    </div>
</div>
@endsection