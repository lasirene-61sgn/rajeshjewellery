@extends('layouts.admin')

@section('title', 'Edit Work Order')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Edit Work Order</h1>
            <p class="text-sm text-slate-500 font-mono">{{ $workOrder->work_order_no }}</p>
        </div>
        <a href="{{ $backUrl ?? route('admin.work_orders.index') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-lg transition-colors">
            Cancel
        </a>
    </div>

    <form method="POST" action="{{ route('admin.work_orders.update', $workOrder) }}" enctype="multipart/form-data" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Design Image</label>
            
            @if($workOrder->design_image)
            <div class="relative inline-block mb-2">
                <a href="{{ asset('storage/' . $workOrder->design_image) }}" target="_blank">
                    <img src="{{ asset('storage/' . $workOrder->design_image) }}" alt="Design" class="w-16 h-16 object-cover rounded-lg border shadow-sm">
                </a>
                @if(stripos($workOrder->reference_no, 'ESO') !== false || $workOrder->seal)
                <span class="absolute bottom-1 right-1 bg-amber-500 text-white text-[9px] font-bold px-1 rounded shadow">
                    916 SEAL
                </span>
                @endif
            </div>
            @endif
            
            <input type="file" name="design_image" accept="image/*" class="w-full px-3 py-2 border rounded-lg text-sm text-slate-500 file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Reference Number</label>
                <input type="text" name="reference_no" value="{{ old('reference_no', $workOrder->reference_no) }}" placeholder="e.g. REF-2026-001" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Product Name *</label>
                <input type="text" name="product_name" value="{{ old('product_name', $workOrder->product_name) }}" required placeholder="e.g. Royal Solitaire Ring" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Design Code *</label>
            <input type="text" name="design_code" value="{{ old('design_code', $workOrder->design_code) }}" required placeholder="e.g. DS001" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm uppercase font-mono font-bold text-indigo-700 bg-white">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Unit Type *</label>
                <select name="unit_type" required class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="Piece" {{ old('unit_type', $workOrder->unit_type) === 'Piece' ? 'selected' : '' }}>Piece</option>
                    <option value="Pair" {{ old('unit_type', $workOrder->unit_type) === 'Pair' ? 'selected' : '' }}>Pair</option>
                    <option value="Grams" {{ old('unit_type', $workOrder->unit_type) === 'Grams' ? 'selected' : '' }}>Grams</option>
                    <option value="pcs" {{ old('unit_type', $workOrder->unit_type) === 'pcs' ? 'selected' : '' }}>pcs</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Quantity *</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', $workOrder->quantity) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Category</label>
                <input type="text" name="category" value="{{ old('category', $workOrder->category) }}" placeholder="e.g. Rings, Bangles" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Subcategory</label>
                <input type="text" name="subcategory" value="{{ old('subcategory', $workOrder->subcategory) }}" placeholder="e.g. Bridal" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Screw Type</label>
                <select name="screw_type" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="None / N/A" {{ old('screw_type', $workOrder->screw_type) === 'None / N/A' ? 'selected' : '' }}>None / N/A</option>
                    <option value="Bombay Screw" {{ old('screw_type', $workOrder->screw_type) === 'Bombay Screw' ? 'selected' : '' }}>Bombay Screw</option>
                    <option value="South Screw" {{ old('screw_type', $workOrder->screw_type) === 'South Screw' ? 'selected' : '' }}>South Screw</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Rhodium Polish</label>
                <select name="rhodium_polish" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="1" {{ old('rhodium_polish', $workOrder->rhodium_polish) == '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ old('rhodium_polish', $workOrder->rhodium_polish) == '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Size</label>
                <input type="text" name="size" value="{{ old('size', $workOrder->size) }}" placeholder="e.g. 14" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Length</label>
                <input type="text" name="length" value="{{ old('length', $workOrder->length) }}" placeholder="e.g. 18 inch" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Target Weight (g) *</label>
                <input type="number" step="0.001" name="target_weight" value="{{ old('target_weight', $workOrder->target_weight) }}" required placeholder="e.g. 6.500" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Gold Hallmark / Purity *</label>
                <select name="hallmark_purity" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="916 (22K)" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '916 (22K)' ? 'selected' : '' }}>916 (22K)</option>
                    <option value="22K" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '22K' ? 'selected' : '' }}>22K</option>
                    <option value="750 (18K)" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '750 (18K)' ? 'selected' : '' }}>750 (18K)</option>
                    <option value="585 (14K)" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '585 (14K)' ? 'selected' : '' }}>585 (14K)</option>
                    <option value="999 (24K)" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '999 (24K)' ? 'selected' : '' }}>999 (24K)</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Seal Type (e.g. 916 Seal)</label>
                <input type="text" name="seal" value="{{ old('seal', $workOrder->seal) }}" placeholder="e.g. 916 Seal" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Job Type</label>
                <input type="text" name="job_type" value="{{ old('job_type', $workOrder->job_type) }}" placeholder="e.g. Handmade / Casting" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Due Date *</label>
                <input type="date" name="due_date" value="{{ old('due_date', optional($workOrder->due_date)->format('Y-m-d')) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Instructions / Notes</label>
            <textarea name="instructions" rows="3" placeholder="Specific workshop instructions..." class="w-full px-3 py-2 border rounded-lg text-sm">{{ old('instructions', $workOrder->instructions) }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ $backUrl ?? route('admin.work_orders.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</a>
            <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                Update Work Order
            </button>
        </div>
    </form>
</div>
@endsection