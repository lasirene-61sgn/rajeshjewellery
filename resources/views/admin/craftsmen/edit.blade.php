@extends('layouts.admin')

@section('title', 'Edit Craftsman - ' . $craftsman->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Edit Craftsman: {{ $craftsman->name }}</h1>
            <p class="text-sm text-slate-500">Update account credentials, status, and mapped design codes.</p>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('admin.craftsmen.auto-assign', $craftsman) }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('Are you sure you want to auto-assign all pending orders for this craftsman\'s design codes?');" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 shadow-sm">
                    Assign Directly
                </button>
            </form>
            <a href="{{ route('admin.craftsmen.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                Back to List
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <form action="{{ route('admin.craftsmen.update', $craftsman) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Account Details -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold tracking-wider text-slate-400 uppercase">Account Details</h3>
                    <label class="flex items-center text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $craftsman->is_active) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2">
                        Active Account
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $craftsman->name) }}" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 @error('name') border-rose-500 @enderror">
                        @error('name') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Mobile Number *</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $craftsman->mobile) }}" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 @error('mobile') border-rose-500 @enderror">
                        @error('mobile') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Email Address *</label>
                        <input type="email" name="email" value="{{ old('email', $craftsman->email) }}" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 @error('email') border-rose-500 @enderror">
                        @error('email') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">New Password (Leave empty to keep current)</label>
                        <input type="password" name="password" placeholder="Leave empty to remain unchanged" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 @error('password') border-rose-500 @enderror">
                        @error('password') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            <!-- Design Codes Table -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold tracking-wider text-slate-700 uppercase">Assigned Design Codes & Names</h3>
                        <p class="text-xs text-slate-400">Manage assigned codes and update design names in master catalog.</p>
                    </div>
                </div>

                <input type="text" id="designSearch" onkeyup="filterDesigns()" placeholder="Type to filter design codes or names..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                <div class="border border-slate-200 rounded-xl overflow-hidden bg-white max-h-72 overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs" id="designsTable">
                        <thead class="bg-slate-50 sticky top-0 font-semibold text-slate-600">
                            <tr>
                                <th class="p-3 w-10 text-center">Select</th>
                                <th class="p-3 w-36">Design Code</th>
                                <th class="p-3">Design Name</th>
                                <th class="p-3 w-48">Design Image</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php
                                $assignedIds = $craftsman->designCodes->pluck('id')->toArray();
                            @endphp

                            @foreach($designCodes as $design)
                                @php
                                    $isAssigned = in_array($design->id, old('design_codes', $assignedIds));
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors design-row">
                                    <td class="p-3 text-center">
                                        <input 
                                            type="checkbox" 
                                            name="design_codes[]" 
                                            value="{{ $design->id }}" 
                                            id="design_{{ $design->id }}"
                                            {{ $isAssigned ? 'checked' : '' }}
                                            onchange="toggleNameInput({{ $design->id }})"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                    </td>
                                    <td class="p-3 font-mono font-bold text-slate-900 code-col">
                                        <label for="design_{{ $design->id }}" class="cursor-pointer">
                                            {{ $design->code }}
                                        </label>
                                    </td>
                                    <td class="p-3 name-col">
                                        <input 
                                            type="text" 
                                            name="design_names[{{ $design->id }}]" 
                                            id="name_input_{{ $design->id }}"
                                            value="{{ old('design_names.' . $design->id, $design->nickname) }}"
                                            placeholder="Enter design name" 
                                            {{ !$isAssigned ? 'disabled' : '' }}
                                            class="w-full px-2.5 py-1.5 border border-slate-300 rounded text-xs disabled:bg-slate-100 disabled:text-slate-400 focus:ring-2 focus:ring-indigo-500"
                                        >
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-2">
                                            @if($design->image)
                                                <div class="flex flex-col gap-1 items-center">
                                                    <img src="{{ Storage::url($design->image) }}" class="w-10 h-10 rounded object-cover border border-slate-200">
                                                    <label class="text-[9px] text-rose-500 flex items-center gap-1 cursor-pointer">
                                                        <input type="checkbox" name="delete_design_images[]" value="{{ $design->id }}" id="delete_image_{{ $design->id }}" {{ !$isAssigned ? 'disabled' : '' }}> Remove
                                                    </label>
                                                </div>
                                            @endif
                                            <input 
                                                type="file" 
                                                name="existing_design_images[{{ $design->id }}]" 
                                                id="image_input_{{ $design->id }}"
                                                accept="image/*"
                                                {{ !$isAssigned ? 'disabled' : '' }}
                                                class="w-full text-[10px] file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 disabled:opacity-50"
                                            >
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3.5 bg-indigo-50/50 border border-indigo-100 rounded-xl">
                    <span class="text-xs font-bold text-indigo-700 uppercase tracking-wide">+ Register & Assign Additional Design Code</span>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                        <input type="text" name="new_design_code" value="{{ old('new_design_code') }}" placeholder="Design Code (e.g. DS004)" class="px-3 py-2 border rounded-lg text-xs bg-white border-slate-300">
                        <input type="text" name="new_design_nickname" value="{{ old('new_design_nickname') }}" placeholder="Design Name (e.g. Diamond Pendant)" class="px-3 py-2 border rounded-lg text-xs bg-white border-slate-300">
                        <input type="file" name="design_image" accept="image/*" class="px-3 py-2 border rounded-lg text-xs bg-white border-slate-300 text-slate-500">
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.craftsmen.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</a>
                <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                    Update Craftsman
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleNameInput(id) {
        const checkbox = document.getElementById('design_' + id);
        const input = document.getElementById('name_input_' + id);
        const imageInput = document.getElementById('image_input_' + id);
        const deleteInput = document.getElementById('delete_image_' + id);

        input.disabled = !checkbox.checked;
        if(imageInput) imageInput.disabled = !checkbox.checked;
        if(deleteInput) deleteInput.disabled = !checkbox.checked;

        if (checkbox.checked) {
            input.focus();
        }
    }

    function filterDesigns() {
        const query = document.getElementById('designSearch').value.toLowerCase();
        document.querySelectorAll('#designsTable .design-row').forEach(row => {
            const code = row.querySelector('.code-col').innerText.toLowerCase();
            const name = row.querySelector('.name-col input').value.toLowerCase();
            row.style.display = (code.includes(query) || name.includes(query)) ? '' : 'none';
        });
    }
</script>
@endsection