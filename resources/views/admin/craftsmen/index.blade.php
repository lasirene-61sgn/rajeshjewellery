@extends('layouts.admin')

@section('title', 'Craftsmen Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Craftsmen Directory</h1>
            <p class="text-sm text-slate-500">Manage workshop artisans, assignable design codes, and portal access.</p>
        </div>
        <div>
            <a href="{{ route('admin.craftsmen.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Craftsman
            </a>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.craftsmen.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, mobile, email, or design code..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="w-full sm:w-48">
                <select name="status" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
            </div>
            <div class="w-full sm:w-48">
                <select name="design_code" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Design Codes</option>
                    @foreach($designCodes as $design)
                        <option value="{{ $design->code }}" {{ request('design_code') === $design->code ? 'selected' : '' }}>
                            {{ $design->code }} @if($design->nickname) - ({{ $design->nickname }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-black text-white text-sm font-medium rounded-lg transition-colors">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'status', 'design_code']))
                    <a href="{{ route('admin.craftsmen.index') }}" class="px-3 py-2 text-slate-500 hover:bg-slate-100 rounded-lg text-sm">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Craftsman</th>
                        <th class="px-4 py-3.5">Contact Details</th>
                        <th class="px-4 py-3.5">Assigned Design Codes</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($craftsmen as $craftsman)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3.5 font-bold text-slate-900">
                                {{ $craftsman->name }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="text-slate-800 font-medium">{{ $craftsman->mobile }}</div>
                                <div class="text-xs text-slate-400">{{ $craftsman->email ?? 'No email' }}</div>
                                @if($craftsman->plain_password)
                                    <div class="text-xs text-slate-500 mt-1">Pwd: <span class="font-mono bg-slate-100 px-1 rounded">{{ $craftsman->plain_password }}</span></div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex flex-wrap gap-1 max-w-md">
                                    @forelse($craftsman->designCodes->take(6) as $code)
                                        <span class="inline-block px-2 py-0.5 rounded bg-indigo-50 border border-indigo-100 text-indigo-700 font-mono text-xs font-semibold" title="{{ $code->nickname }}">
                                            {{ $code->code }}
                                            @if($code->nickname)
                                                <span class="font-sans text-slate-500 font-normal">({{ $code->nickname }})</span>
                                            @endif
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 italic">No design codes linked</span>
                                    @endforelse
                                    @if($craftsman->designCodes->count() > 6)
                                        <span class="text-2xs text-slate-400 self-center font-semibold">+{{ $craftsman->designCodes->count() - 6 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($craftsman->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('admin.craftsmen.show', $craftsman) }}" class="text-xs font-medium text-slate-600 hover:text-indigo-600">View</a>
                                <a href="{{ route('admin.craftsmen.edit', $craftsman) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-900">Edit</a>
                                <form action="{{ route('admin.craftsmen.destroy', $craftsman) }}" method="POST" class="inline" onsubmit="return confirm('Delete this craftsman account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">No craftsmen registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($craftsmen->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $craftsmen->links() }}
            </div>
        @endif
    </div>
</div>
@endsection