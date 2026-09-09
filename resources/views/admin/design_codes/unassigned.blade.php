@extends('layouts.admin')

@section('title', 'Unassigned Design Codes')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Unassigned Design Codes</h1>
        <p class="text-sm text-slate-500">Design codes that have not yet been assigned to any craftsman.</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
        <form method="GET" action="{{ route('admin.design_codes.unassigned') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search code or nickname..." class="w-full sm:max-w-xs px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Search</button>
            @if(request()->anyFilled(['search']))
                <a href="{{ route('admin.design_codes.unassigned') }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-6 py-4 font-semibold">Design Code</th>
                    <th class="px-6 py-4 font-semibold">Nickname</th>
                    <th class="px-6 py-4 font-semibold">Created At</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($designCodes as $design)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-indigo-600">{{ $design->code }}</td>
                        <td class="px-6 py-4 text-slate-700">{{ $design->nickname ?: 'N/A' }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $design->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.craftsmen.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Assign to Craftsman &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <p class="text-base font-medium text-slate-900">No unassigned design codes found</p>
                                <p class="mt-1 text-sm text-slate-500">All design codes are currently assigned, or none match your search.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($designCodes->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $designCodes->links() }}
        </div>
    @endif
</div>
@endsection
