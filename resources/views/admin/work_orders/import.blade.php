@extends('layouts.admin')

@section('title', 'Import Work Orders')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Import Work Orders</h1>
            <p class="text-sm text-slate-500">Upload Malabar CSV or Excel export files to bulk create work orders.</p>
        </div>
        <a href="{{ $backUrl }}" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
            Back to Orders
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <form action="{{ route('admin.work_orders.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Select CSV / Excel File *</label>
                <input type="file" name="file" required accept=".csv, .txt, .xlsx, .xls" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-lg p-2">
                @error('file')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Due Date Calculation (Days from Order) *</label>
                <select name="due_date_days" required class="w-full text-xs text-slate-600 border border-slate-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="4">4 Days</option>
                    <option value="5">5 Days</option>
                    <option value="6">6 Days</option>
                    <option value="7" selected>7 Days</option>
                </select>
                @error('due_date_days')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="p-4 bg-slate-50 rounded-lg border border-slate-200 text-xs text-slate-600 space-y-1">
                <span class="font-bold text-slate-700 block mb-1">Expected Column Mapping:</span>
                <p>• <strong>Order No</strong> → Reference Number</p>
                <p>• <strong>Order Type</strong> → Unit Type (pcs, etc.)</p>
                <p>• <strong>Order Date</strong> → Created Date</p>
                <p>• <strong>Product</strong> → Product Name</p>
                <p>• <strong>Design Code</strong> → Design Code</p>
                <p>• <strong>Weight</strong> → Target Weight</p>
                <p>• <strong>Size</strong> → Size</p>
                <p>• <strong>Qty</strong> → Quantity</p>
                <p>• <strong>Balance</strong> → Job Type</p>
                <p>• <strong>Remarks</strong> → Instructions</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ $backUrl }}" class="px-4 py-2 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</a>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
                    Upload & Import Orders
                </button>
            </div>
        </form>
    </div>
</div>
@endsection