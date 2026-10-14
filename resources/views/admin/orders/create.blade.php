@extends('layouts.admin')

@section('title', 'Create Order')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Create New Order</h1>
            <p class="text-sm text-slate-500 mt-0.5">Fill out order specifics and assign a craftsman if ready.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Orders
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <form action="{{ route('admin.orders.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Reference Number -->
                <div>
                    <label for="reference_number" class="block text-sm font-medium text-slate-700">Reference Number</label>
                    <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. REF-10023" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('reference_number') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Product Name -->
                <div>
                    <label for="product_name" class="block text-sm font-medium text-slate-700">Product Name</label>
                    <input type="text" name="product_name" id="product_name" value="{{ old('product_name') }}" placeholder="e.g. Diamond Ring" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('product_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Assign Craftsman -->
                <div>
                    <label for="craftsman_id" class="block text-sm font-medium text-slate-700">Assign Craftsman</label>
                    <select name="craftsman_id" id="craftsman_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Unallocated / Select Later --</option>
                        @foreach($craftsmen as $craftsman)
                            <option value="{{ $craftsman->id }}" {{ old('craftsman_id') == $craftsman->id ? 'selected' : '' }}>
                                {{ $craftsman->name }} (Mobile: {{ $craftsman->mobile }})
                            </option>
                        @endforeach
                    </select>
                    @error('craftsman_id') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Order Type -->
                <div>
                    <label for="order_type" class="block text-sm font-medium text-slate-700">Order Type</label>
                    <input type="text" name="order_type" id="order_type" value="{{ old('order_type') }}" placeholder="e.g. Custom / Bulk" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('order_type') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Size -->
                <div>
                    <label for="size" class="block text-sm font-medium text-slate-700">Size</label>
                    <input type="text" name="size" id="size" value="{{ old('size') }}" placeholder="e.g. US 7" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('size') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Length -->
                <div>
                    <label for="length" class="block text-sm font-medium text-slate-700">Length</label>
                    <input type="text" name="length" id="length" value="{{ old('length') }}" placeholder="e.g. 18 inches" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('length') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Weight -->
                <div>
                    <label for="weight" class="block text-sm font-medium text-slate-700">Weight</label>
                    <input type="text" name="weight" id="weight" value="{{ old('weight') }}" placeholder="e.g. 5.4g" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('weight') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-slate-700">Quantity</label>
                    <input type="text" name="quantity" id="quantity" value="{{ old('quantity') }}" placeholder="e.g. 1" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('quantity') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Due Date -->
                <div>
                    <label for="due_date" class="block text-sm font-medium text-slate-700">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('due_date') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Image Upload -->
                <div>
                    <label for="image" class="block text-sm font-medium text-slate-700">Product Reference Image</label>
                    <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('image') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700">Notes / Instructions</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Add specific crafting notes..." class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">{{ old('notes') }}</textarea>
                @error('notes') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">Save Order</button>
            </div>
        </form>
    </div>
</div>
@endsection