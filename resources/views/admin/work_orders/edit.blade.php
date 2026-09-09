@extends('layouts.admin')

@section('title', 'Edit ' . $workOrder->work_order_no)

@section('content')
<div class="max-w-3xl mx-auto" x-data="designCodeAutofill(@js($designCodes ?? []), @js(old('design_code', $workOrder->design_code)), @js(old('design_nickname', $workOrder->design_nickname)))">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Edit Order: {{ $workOrder->work_order_no }}</h1>
            <p class="text-sm text-slate-500">Update parameters, design code, and instructions.</p>
        </div>
        <a href="{{ $backUrl }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
            Cancel
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <form action="{{ route('admin.work_orders.update', $workOrder) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="back_url" value="{{ $backUrl }}">

            @if($workOrder->design_image)
                <div class="flex items-center gap-4 p-3 bg-slate-50 border rounded-lg">
                    <img src="{{ asset('storage/' . $workOrder->design_image) }}" class="w-16 h-16 object-cover rounded-lg border">
                    <span class="text-xs text-slate-500">Current Design Image</span>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Replace Design Image</label>
                <input type="file" name="design_image" accept="image/*" class="w-full px-3 py-2 border rounded-lg text-sm text-slate-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Reference Number</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no', $workOrder->reference_no) }}" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Product Name *</label>
                    <input type="text" name="product_name" value="{{ old('product_name', $workOrder->product_name) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <!-- Auto-fill Design Code & Nickname -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-indigo-50/40 rounded-xl border border-indigo-100">
                <div>
                    <label class="block text-xs font-semibold uppercase text-indigo-900 mb-1">Design Code *</label>
                    <input 
                        type="text" 
                        name="design_code" 
                        list="available_design_codes_edit" 
                        x-model="code"
                        required 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm uppercase font-mono font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500 bg-white"
                    >
                    <datalist id="available_design_codes_edit">
                        <template x-for="item in masterList" :key="item.code">
                            <option :value="item.code" x-text="item.nickname ? item.code + ' - ' + item.nickname : item.code"></option>
                        </template>
                    </datalist>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-indigo-900 mb-1">Design Nickname</label>
                    <input 
                        type="text" 
                        name="design_nickname" 
                        x-model="nickname"
                        placeholder="e.g. Solitaire Ring" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 bg-white"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Unit Type *</label>
                    <select name="unit_type" required class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="Piece" {{ old('unit_type', $workOrder->unit_type) === 'Piece' ? 'selected' : '' }}>Piece</option>
                        <option value="Pair" {{ old('unit_type', $workOrder->unit_type) === 'Pair' ? 'selected' : '' }}>Pair</option>
                        <option value="Grams" {{ old('unit_type', $workOrder->unit_type) === 'Grams' ? 'selected' : '' }}>Grams</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" min="1" value="{{ old('quantity', $workOrder->quantity) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Category *</label>
                    <input type="text" name="category" value="{{ old('category', $workOrder->category) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Subcategory</label>
                    <input type="text" name="subcategory" value="{{ old('subcategory', $workOrder->subcategory) }}" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Target Weight (g) *</label>
                    <input type="number" step="0.001" name="target_weight" value="{{ old('target_weight', $workOrder->target_weight) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Gold Hallmark / Purity *</label>
                    <select name="hallmark_purity" required class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="916 (22K)" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '916 (22K)' ? 'selected' : '' }}>916 (22K)</option>
                        <option value="750 (18K)" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '750 (18K)' ? 'selected' : '' }}>750 (18K)</option>
                        <option value="585 (14K)" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '585 (14K)' ? 'selected' : '' }}>585 (14K)</option>
                        <option value="999 (24K)" {{ old('hallmark_purity', $workOrder->hallmark_purity) === '999 (24K)' ? 'selected' : '' }}>999 (24K)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Due Date *</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $workOrder->due_date?->format('Y-m-d')) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Rhodium Polish</label>
                    <select name="rhodium_polish" class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="1" {{ old('rhodium_polish', $workOrder->rhodium_polish) ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ !old('rhodium_polish', $workOrder->rhodium_polish) ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Instructions</label>
                <textarea name="instructions" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm">{{ old('instructions', $workOrder->instructions) }}</textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ $backUrl }}" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</a>
                <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                    Update Work Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function designCodeAutofill(masterList, initialCode = '', initialNickname = '') {
        return {
            code: initialCode,
            nickname: initialNickname,
            masterList: masterList,

            init() {
                this.$watch('code', value => {
                    this.findNickname(value);
                });
            },

            findNickname(searchVal) {
                if (!searchVal) return;
                const search = searchVal.trim().toUpperCase();
                const matched = this.masterList.find(d => d.code.toUpperCase() === search);
                if (matched && matched.nickname) {
                    this.nickname = matched.nickname;
                } else {
                    this.nickname = '';
                }
            }
        };
    }
</script>
@endsection