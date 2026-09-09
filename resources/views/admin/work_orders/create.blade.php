@extends('layouts.admin')

@section('title', 'Create Work Order')

@section('content')
<div class="max-w-3xl mx-auto" x-data="designCodeAutofill(@js($designCodes ?? []), @js(old('design_code')), @js(old('design_nickname')))">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Create Work Order</h1>
            <p class="text-sm text-slate-500">Enter custom piece parameters, target metal weight, and design specifications.</p>
        </div>
        <a href="{{ request('back_url', route('admin.work_orders.index')) }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
            Cancel
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <form 
            action="{{ route('admin.work_orders.store') }}" 
            method="POST" 
            enctype="multipart/form-data" 
            class="space-y-5"
            onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerText = 'Saving...';"
        >
            @csrf

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Design Image</label>
                <input type="file" name="design_image" accept="image/*" class="w-full px-3 py-2 border rounded-lg text-sm text-slate-500 file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Reference Number</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no') }}" placeholder="e.g. REF-2026-001" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Product Name *</label>
                    <input type="text" name="product_name" value="{{ old('product_name') }}" required placeholder="e.g. Royal Solitaire Ring" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <!-- Auto-filling Design Code & Nickname -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-indigo-50/40 rounded-xl border border-indigo-100">
                <div>
                    <label class="block text-xs font-semibold uppercase text-indigo-900 mb-1">Design Code *</label>
                    <input 
                        type="text" 
                        name="design_code" 
                        list="available_design_codes" 
                        x-model="code"
                        required 
                        placeholder="e.g. DS001" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm uppercase font-mono font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500 bg-white"
                    >
                    <datalist id="available_design_codes">
                        <template x-for="item in masterList" :key="item.code">
                            <option :value="item.code" x-text="item.nickname ? item.code + ' - ' + item.nickname : item.code"></option>
                        </template>
                    </datalist>
                    <span class="text-2xs text-slate-400">Select existing or type a new code</span>
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
                    <span class="text-2xs text-slate-400">Auto-filled from master or enter new</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Unit Type *</label>
                    <select name="unit_type" required class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="Piece" {{ old('unit_type') === 'Piece' ? 'selected' : '' }}>Piece</option>
                        <option value="Pair" {{ old('unit_type') === 'Pair' ? 'selected' : '' }}>Pair</option>
                        <option value="Grams" {{ old('unit_type') === 'Grams' ? 'selected' : '' }}>Grams</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Category *</label>
                    <input type="text" name="category" value="{{ old('category') }}" required placeholder="e.g. Rings, Bangles" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Subcategory</label>
                    <input type="text" name="subcategory" value="{{ old('subcategory') }}" placeholder="e.g. Bridal" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Screw Type</label>
                    <select name="screw_type" class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="None / N/A">None / N/A</option>
                        <option value="Bombay Screw">Bombay Screw</option>
                        <option value="South Screw">South Screw</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Rhodium Polish</label>
                    <select name="rhodium_polish" class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="1" {{ old('rhodium_polish', '1') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('rhodium_polish') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Size</label>
                    <input type="text" name="size" value="{{ old('size') }}" placeholder="e.g. 14" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Length</label>
                    <input type="text" name="length" value="{{ old('length') }}" placeholder="e.g. 18 inch" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Target Weight (g) *</label>
                    <input type="number" step="0.001" name="target_weight" value="{{ old('target_weight') }}" required placeholder="e.g. 6.500" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Gold Hallmark / Purity *</label>
                    <select name="hallmark_purity" required class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="916 (22K)">916 (22K)</option>
                        <option value="750 (18K)">750 (18K)</option>
                        <option value="585 (14K)">585 (14K)</option>
                        <option value="999 (24K)">999 (24K)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Due Date *</label>
                    <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+7 days'))) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Job Type</label>
                    <input type="text" name="job_type" value="{{ old('job_type') }}" placeholder="e.g. Handmade / Casting" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 mb-1">Instructions / Notes</label>
                <textarea name="instructions" rows="3" placeholder="Specific workshop instructions..." class="w-full px-3 py-2 border rounded-lg text-sm">{{ old('instructions') }}</textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ request('back_url', route('admin.work_orders.index')) }}" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</a>
                <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                    Save Work Order
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