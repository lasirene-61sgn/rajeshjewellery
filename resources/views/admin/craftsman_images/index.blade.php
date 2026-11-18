@extends('layouts.admin')

@section('title', 'Craftsman Images')

@section('content')
<div class="space-y-6">
    <!-- Header & Stats -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Craftsman Images</h1>
            <p class="text-sm text-slate-500">Browse craftsman folders to view assigned design codes and their images.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center space-x-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs font-medium">
                <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                </svg>
                <span>{{ $totalCraftsmen }} Craftsmen</span>
            </div>
            <div class="flex items-center space-x-2 px-3 py-1.5 bg-indigo-50 border border-indigo-200 rounded-lg text-indigo-800 text-xs font-medium">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $totalImages }} Images Uploaded</span>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.craftsman_images.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search craftsmen folders by name, mobile, or email..."
                       class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-slate-900 hover:bg-black text-white text-sm font-medium rounded-lg transition-colors">
                    Search
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.craftsman_images.index') }}" class="w-full sm:w-auto text-center px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Craftsmen Folders Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
        @forelse($craftsmen as $craftsman)
            <a href="{{ route('admin.craftsman_images.show', $craftsman) }}"
               class="group relative bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-amber-400 transition-all duration-200 flex flex-col justify-between">
                
                <div>
                    <!-- Top row: Folder icon & design count badge -->
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 group-hover:bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-600 transition-colors shrink-0">
                            <!-- Folder SVG Icon -->
                            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3h-3.379a3 3 0 01-2.121-.879L8.379 3.999A3 3 0 006.258 3.12H4.5A3 3 0 001.5 6.12v11.88a3 3 0 003 3h15z" opacity="0.3" />
                                <path d="M2 7a2 2 0 012-2h4.586a1 1 0 01.707.293l1.414 1.414a1 1 0 00.707.293H20a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V7z" />
                            </svg>
                        </div>

                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $craftsman->design_codes_count > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                            {{ $craftsman->design_codes_count }} {{ Str::plural('Design', $craftsman->design_codes_count) }}
                        </span>
                    </div>

                    <!-- Craftsman Name & Info -->
                    <h2 class="text-base font-bold text-slate-800 group-hover:text-indigo-600 transition-colors truncate">
                        {{ $craftsman->name }}
                    </h2>
                    @if($craftsman->mobile)
                        <p class="text-xs text-slate-500 mt-0.5 truncate flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ $craftsman->mobile }}
                        </p>
                    @endif
                </div>

                <!-- Card Footer Info -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="font-medium text-slate-700">{{ $craftsman->images_count }}</span> with images
                    </span>

                    <span class="text-indigo-600 font-medium group-hover:translate-x-0.5 transition-transform flex items-center gap-0.5">
                        Open
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </div>
            </a>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-slate-200 p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-700">No Craftsman Folders Found</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    @if(request()->filled('search'))
                        No craftsman matched "{{ request('search') }}". Try a different search term.
                    @else
                        There are currently no craftsmen registered in the system.
                    @endif
                </p>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.craftsman_images.index') }}" class="inline-block mt-4 px-4 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                        Clear Search
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($craftsmen->hasPages())
        <div class="bg-white px-6 py-4 rounded-xl border border-slate-200 shadow-sm">
            {{ $craftsmen->links() }}
        </div>
    @endif
</div>
@endsection
