@extends('layouts.craftsman')

@section('title', 'Order ' . $workOrder->work_order_no)

@section('content')
<div class="max-w-4xl mx-auto space-y-6 px-4 sm:px-0">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">{{ $workOrder->reference_no }}</h1>
            <p class="text-xs text-stone-500 mt-0.5">Work Order Specifications & Details</p>
        </div>
        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            @if(in_array($workOrder->status, ['in_process', 'returned']))
                <form method="POST" action="{{ route('craftsman.work-orders.submit', $workOrder) }}" onsubmit="return confirm('Submit this piece for quality approval?')" class="flex-1 sm:flex-none">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                        <span>Submit for Approval</span>
                        <span>&rarr;</span>
                    </button>
                </form>
            @endif
            <a href="{{ $backUrl }}" class="px-4 py-2.5 text-xs sm:text-sm font-bold text-stone-700 bg-white border border-stone-200 rounded-xl hover:bg-stone-50 shadow-sm transition-all text-center">Back</a>
        </div>
    </div>

    <!-- QC Return Alert if returned -->
    @if($workOrder->status === 'returned')
        <div class="p-4 sm:p-5 bg-rose-50 border border-rose-200 rounded-2xl space-y-3 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <span class="text-xs font-black uppercase text-rose-700 tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
                    Quality Rejection Notice (Rework #{{ $workOrder->return_count }})
                </span>
                <span class="text-xs font-extrabold text-rose-700 bg-rose-100/80 px-2.5 py-1 rounded-lg w-fit">New Deadline: {{ $workOrder->return_due_date?->format('d M Y') }}</span>
            </div>
            <p class="text-sm font-semibold text-rose-900 bg-white/60 p-3 rounded-xl border border-rose-200/60">{{ $workOrder->return_reason }}</p>
            @if($workOrder->return_image)
                <div>
                    <span class="text-xs font-bold text-rose-600 block mb-1.5">Defect Proof Image:</span>
                    <img src="{{ asset('storage/' . $workOrder->return_image) }}" class="h-32 sm:h-40 rounded-xl border border-rose-300 object-cover shadow-sm">
                </div>
            @endif
        </div>
    @endif

    <!-- Specifications Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-stone-200/50 border border-stone-200/80 p-5 sm:p-8">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            
            <!-- Image Column -->
            <div class="w-full lg:w-1/3">
                <div class="relative bg-stone-100 aspect-square lg:aspect-auto lg:h-full min-h-[260px] rounded-2xl overflow-hidden border border-stone-200 shadow-inner flex items-center justify-center">
                    @if($workOrder->design_image)
                        <img src="{{ asset('storage/' . $workOrder->design_image) }}" class="w-full h-full object-cover">
                    @else
                        <div class="text-stone-400 text-xs font-bold">No Design Image</div>
                    @endif
                    @if($workOrder->has_seal)
                        <span class="absolute top-3 left-3 bg-amber-500 text-white text-xs font-black px-2.5 py-1 rounded-lg shadow-md uppercase tracking-wider">
                            916 SEAL
                        </span>
                    @endif
                </div>
            </div>

            <!-- Details Column -->
            <div class="w-full lg:w-2/3 flex flex-col justify-between space-y-5">
                <div class="space-y-3">
                    <div class="flex justify-between items-center border-b border-stone-100 pb-3">
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Design Code</span>
                        <span class="text-sm sm:text-base font-black text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-100 font-mono">{{ $workOrder->design_code }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-stone-100 pb-3">
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Product Name</span>
                        <span class="text-xs sm:text-sm font-extrabold text-stone-800 text-right">{{ $workOrder->product_name ?? 'N/A' }}</span>
                    </div>
                </div>

                <!-- Stats Grid Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-center">
                    <div class="bg-stone-50 p-3 rounded-2xl border border-stone-100">
                        <span class="text-[10px] text-stone-400 block uppercase font-bold tracking-wider">Weight</span>
                        <span class="text-xs sm:text-sm font-black text-amber-700 mt-0.5 block">{{ number_format($workOrder->target_weight, 3) }}g</span>
                    </div>
                    <div class="bg-stone-50 p-3 rounded-2xl border border-stone-100">
                        <span class="text-[10px] text-stone-400 block uppercase font-bold tracking-wider">Quantity</span>
                        <span class="text-xs sm:text-sm font-extrabold text-stone-800 mt-0.5 block">{{ $workOrder->quantity }} {{ $workOrder->unit_type }}</span>
                    </div>
                    <div class="bg-stone-50 p-3 rounded-2xl border border-stone-100">
                        <span class="text-[10px] text-stone-400 block uppercase font-bold tracking-wider">Size</span>
                        <span class="text-xs sm:text-sm font-extrabold text-stone-800 mt-0.5 block">{{ $workOrder->size ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-stone-50 p-3 rounded-2xl border border-stone-100">
                        <span class="text-[10px] text-stone-400 block uppercase font-bold tracking-wider">Length</span>
                        <span class="text-xs sm:text-sm font-extrabold text-stone-800 mt-0.5 block">{{ $workOrder->length ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-stone-50 p-3 rounded-2xl border border-stone-100 col-span-2 sm:col-span-2">
                        <span class="text-[10px] text-stone-400 block uppercase font-bold tracking-wider">Due Date</span>
                        <span class="text-xs sm:text-sm font-extrabold text-stone-800 mt-0.5 block">{{ $workOrder->due_date ? \Carbon\Carbon::parse($workOrder->due_date)->format('d M Y') : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($workOrder->instructions)
            <div class="mt-6 pt-6 border-t border-stone-100">
                <span class="text-xs font-bold uppercase text-stone-400 tracking-wider">Notes & Instructions</span>
                <p class="text-xs sm:text-sm text-stone-800 mt-2 bg-amber-50/60 p-4 rounded-2xl border border-amber-200/60 leading-relaxed">{{ $workOrder->instructions }}</p>
            </div>
        @endif
    </div>

</div>
@endsection