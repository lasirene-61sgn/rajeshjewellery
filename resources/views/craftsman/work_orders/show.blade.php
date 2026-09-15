@extends('layouts.craftsman')

@section('title', 'Order ' . $workOrder->work_order_no)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-800">{{ $workOrder->work_order_no }}</h1>
            <p class="text-sm text-stone-500">{{ $workOrder->product_name }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if(in_array($workOrder->status, ['in_process', 'returned']))
                <form method="POST" action="{{ route('craftsman.work-orders.submit', $workOrder) }}" onsubmit="return confirm('Submit this piece for quality approval?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                        Submit for Approval
                    </button>
                </form>
            @endif
            <a href="{{ $backUrl }}" class="px-4 py-2 text-sm font-medium text-stone-600 bg-white border border-stone-300 rounded-lg hover:bg-stone-50">Back</a>
        </div>
    </div>

    <!-- QC Return Alert if returned -->
    @if($workOrder->status === 'returned')
        <div class="p-5 bg-rose-50 border-2 border-rose-300 rounded-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase text-rose-700 tracking-wider">Quality Rejection Notice (Rework #{{ $workOrder->return_count }})</span>
                <span class="text-xs font-bold text-rose-700">New Deadline: {{ $workOrder->return_due_date?->format('d M Y') }}</span>
            </div>
            <p class="text-sm font-semibold text-rose-900">{{ $workOrder->return_reason }}</p>
            @if($workOrder->return_image)
                <div>
                    <span class="text-xs text-rose-600 block mb-1">Defect Proof Image:</span>
                    <img src="{{ asset('storage/' . $workOrder->return_image) }}" class="h-32 rounded-lg border border-rose-300 object-cover">
                </div>
            @endif
        </div>
    @endif

    <!-- Specifications Card -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row gap-6">
            <div class="sm:w-1/3">
                @if($workOrder->design_image)
                    <img src="{{ asset('storage/' . $workOrder->design_image) }}" class="w-full h-56 object-cover rounded-xl border border-stone-200 shadow-sm">
                @else
                    <div class="w-full h-56 rounded-xl bg-stone-50 border border-dashed flex items-center justify-center text-stone-400 text-xs">No Design Image</div>
                @endif
            </div>

            <div class="sm:w-2/3 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-xs text-stone-400 uppercase font-semibold">Design Code</span>
                    <p class="font-mono font-bold text-indigo-600 text-base">{{ $workOrder->design_code }}</p>
                </div>
                <div>
                    <span class="text-xs text-stone-400 uppercase font-semibold">Gold Hallmark / Purity</span>
                    <p class="font-bold text-stone-800">{{ $workOrder->hallmark_purity }}</p>
                </div>
                <div>
                    <span class="text-xs text-stone-400 uppercase font-semibold">Target Metal Weight</span>
                    <p class="font-extrabold text-amber-600 text-lg">{{ number_format($workOrder->target_weight, 3) }} g</p>
                </div>
                <div>
                    <span class="text-xs text-stone-400 uppercase font-semibold">Quantity / Unit</span>
                    <p class="font-semibold text-stone-800">{{ $workOrder->quantity }} {{ $workOrder->unit_type }}</p>
                </div>
                <div>
                    <span class="text-xs text-stone-400 uppercase font-semibold">Screw Type</span>
                    <p class="font-medium text-stone-800">{{ $workOrder->screw_type ?? 'None / N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs text-stone-400 uppercase font-semibold">Rhodium Polish</span>
                    <p class="font-semibold {{ $workOrder->rhodium_polish ? 'text-emerald-600' : 'text-stone-500' }}">{{ $workOrder->rhodium_polish ? 'Required (Yes)' : 'No' }}</p>
                </div>
            </div>
        </div>

        @if($workOrder->instructions)
            <hr class="my-6 border-stone-100">
            <div>
                <span class="text-xs font-semibold uppercase text-stone-400">Workshop Instructions</span>
                <p class="text-sm text-stone-800 mt-1 bg-amber-50/50 p-4 rounded-lg border border-amber-100 leading-relaxed">{{ $workOrder->instructions }}</p>
            </div>
        @endif
    </div>

</div>
@endsection
