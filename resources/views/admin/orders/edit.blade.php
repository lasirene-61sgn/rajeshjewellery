@extends('layouts.admin')

@section('title', 'Edit Order')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Order: #{{ $order->reference_number }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">Modify order specifications or update allocation status.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Orders
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <form action="{{ route('admin.orders.update', $order->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Reference Number -->
                <div>
                    <label for="reference_number" class="block text-sm font-medium text-slate-700">Reference Number</label>
                    <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number', $order->reference_number) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('reference_number') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Product Name -->
                <div>
                    <label for="product_name" class="block text-sm font-medium text-slate-700">Product Name</label>
                    <input type="text" name="product_name" id="product_name" value="{{ old('product_name', $order->product_name) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('product_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Assign Craftsman -->
                <div>
                    <label for="craftsman_id" class="block text-sm font-medium text-slate-700">Assign Craftsman</label>
                    <select name="craftsman_id" id="craftsman_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Unallocated / Select Later --</option>
                        @foreach($craftsmen as $craftsman)
                            <option value="{{ $craftsman->id }}" {{ (old('craftsman_id', $order->craftsman_id) == $craftsman->id) ? 'selected' : '' }}>
                                {{ $craftsman->name }} (Mobile: {{ $craftsman->mobile }})
                            </option>
                        @endforeach
                    </select>
                    @error('craftsman_id') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Order Type -->
                <div>
                    <label for="order_type" class="block text-sm font-medium text-slate-700">Order Type</label>
                    <input type="text" name="order_type" id="order_type" value="{{ old('order_type', $order->order_type) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('order_type') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Size -->
                <div>
                    <label for="size" class="block text-sm font-medium text-slate-700">Size</label>
                    <input type="text" name="size" id="size" value="{{ old('size', $order->size) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('size') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Length -->
                <div>
                    <label for="length" class="block text-sm font-medium text-slate-700">Length</label>
                    <input type="text" name="length" id="length" value="{{ old('length', $order->length) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('length') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Weight -->
                <div>
                    <label for="weight" class="block text-sm font-medium text-slate-700">Weight</label>
                    <input type="text" name="weight" id="weight" value="{{ old('weight', $order->weight) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('weight') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-slate-700">Quantity</label>
                    <input type="text" name="quantity" id="quantity" value="{{ old('quantity', $order->quantity) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('quantity') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Due Date -->
                <div>
                    <label for="due_date" class="block text-sm font-medium text-slate-700">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', optional($order->due_date)->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                    @error('due_date') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Image Upload & Preview -->
                <div>
                    <label for="image" class="block text-sm font-medium text-slate-700">Product Reference Image</label>
                    <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('image') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror

                    @if($order->image)
                        <div class="mt-3 flex items-center space-x-3">
                            <img src="{{ asset('storage/' . $order->image) }}" alt="Current Image" class="w-12 h-12 rounded-lg object-cover border border-slate-200">
                            <span class="text-xs text-slate-500">Current Reference Image</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700">Notes / Instructions</label>
                <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">{{ old('notes', $order->notes) }}</textarea>
                @error('notes') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">Update Order</button>
            </div>
        </form>
    </div>
</div>
@endsection