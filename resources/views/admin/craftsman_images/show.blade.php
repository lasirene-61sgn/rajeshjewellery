@extends('layouts.admin')

@section('title', $craftsman->name . ' - Design Images')

@section('content')
<div class="space-y-6" x-data="craftsmanImageGallery()">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-3">
        <!-- Breadcrumbs -->
        <nav class="flex items-center text-xs text-slate-500 space-x-2">
            <a href="{{ route('admin.craftsman_images.index') }}" class="hover:text-indigo-600 transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                </svg>
                Craftsman Images
            </a>
            <span>/</span>
            <span class="text-slate-800 font-semibold truncate">{{ $craftsman->name }}</span>
        </nav>

        <!-- Main Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-amber-500/10 border border-amber-300 text-amber-600 flex items-center justify-center font-bold text-lg shrink-0">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3h-3.379a3 3 0 01-2.121-.879L8.379 3.999A3 3 0 006.258 3.12H4.5A3 3 0 001.5 6.12v11.88a3 3 0 003 3h15z" opacity="0.3" />
                        <path d="M2 7a2 2 0 012-2h4.586a1 1 0 01.707.293l1.414 1.414a1 1 0 00.707.293H20a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V7z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-800">{{ $craftsman->name }}</h1>
                        @if($craftsman->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                Inactive
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        @if($craftsman->mobile)
                            <span class="mr-3">Phone: {{ $craftsman->mobile }}</span>
                        @endif
                        @if($craftsman->email)
                            <span>Email: {{ $craftsman->email }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.craftsman_images.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Folders
                </a>
            </div>
        </div>
    </div>

    <!-- Stats & Filter Pills -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('admin.craftsman_images.show', ['craftsman' => $craftsman, 'search' => request('search')]) }}"
           class="p-4 rounded-xl border transition-all {{ !request('filter') ? 'bg-indigo-50/70 border-indigo-200 ring-1 ring-indigo-500/20' : 'bg-white border-slate-200 hover:border-slate-300' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Assigned</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-indigo-100/60 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-2">All design codes mapped to this craftsman</p>
        </a>

        <a href="{{ route('admin.craftsman_images.show', ['craftsman' => $craftsman, 'filter' => 'with_image', 'search' => request('search')]) }}"
           class="p-4 rounded-xl border transition-all {{ request('filter') === 'with_image' ? 'bg-emerald-50/70 border-emerald-200 ring-1 ring-emerald-500/20' : 'bg-white border-slate-200 hover:border-slate-300' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-emerald-700 uppercase tracking-wider">With Images</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['with_images'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-2">Design codes having a photo uploaded</p>
        </a>

        <a href="{{ route('admin.craftsman_images.show', ['craftsman' => $craftsman, 'filter' => 'without_image', 'search' => request('search')]) }}"
           class="p-4 rounded-xl border transition-all {{ request('filter') === 'without_image' ? 'bg-amber-50/70 border-amber-200 ring-1 ring-amber-500/20' : 'bg-white border-slate-200 hover:border-slate-300' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-amber-700 uppercase tracking-wider">Missing Images</p>
                    <p class="text-2xl font-bold text-amber-700 mt-1">{{ $stats['without_images'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-2">Design codes without any image</p>
        </a>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.craftsman_images.show', $craftsman) }}" class="flex flex-col sm:flex-row items-center gap-3">
            @if(request('filter'))
                <input type="hidden" name="filter" value="{{ request('filter') }}">
            @endif
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search design code or nickname in this folder..."
                       class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-slate-900 hover:bg-black text-white text-sm font-medium rounded-lg transition-colors">
                    Search
                </button>
                @if(request()->filled('search') || request()->filled('filter'))
                    <a href="{{ route('admin.craftsman_images.show', $craftsman) }}" class="w-full sm:w-auto text-center px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        Reset Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Design Codes Gallery Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        @forelse($designCodes as $design)
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between group">
                <!-- Image or Placeholder Container -->
                <div class="relative aspect-square bg-slate-100 overflow-hidden flex items-center justify-center">
                    @if($design->image)
                        <img src="{{ Storage::url($design->image) }}"
                             alt="{{ $design->code }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 cursor-pointer"
                             @click="openModal('{{ Storage::url($design->image) }}', '{{ addslashes($design->code) }}', '{{ addslashes($design->nickname ?? '') }}')">
                        
                        <!-- Overlay zoom button on hover -->
                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer pointer-events-none">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-2xs font-semibold bg-white/90 text-slate-800 shadow">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                </svg>
                                Click to View
                            </span>
                        </div>
                    @else
                        <!-- No Image Placeholder -->
                        <div class="p-4 text-center">
                            <div class="w-10 h-10 mx-auto rounded-full bg-slate-200/70 text-slate-400 flex items-center justify-center mb-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-[11px] font-medium text-slate-400">No Image</span>
                        </div>
                    @endif
                </div>

                <!-- Info Block -->
                <div class="p-3 border-t border-slate-100 bg-white">
                    <div class="flex items-center justify-between gap-1">
                        <span class="font-mono font-bold text-sm text-slate-900 truncate" title="{{ $design->code }}">
                            {{ $design->code }}
                        </span>
                        @if($design->image)
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0" title="Has image"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-slate-300 shrink-0" title="Missing image"></span>
                        @endif
                    </div>
                    @if($design->nickname)
                        <p class="text-xs text-slate-500 truncate mt-0.5" title="{{ $design->nickname }}">
                            {{ $design->nickname }}
                        </p>
                    @else
                        <p class="text-xs text-slate-400 italic truncate mt-0.5">
                            No nickname
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-slate-200 p-12 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-700">No Design Codes Found</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    @if(request()->filled('search'))
                        No design code matched "{{ request('search') }}" in this folder.
                    @elseif(request('filter') === 'with_image')
                        This craftsman has no design codes with images uploaded yet.
                    @elseif(request('filter') === 'without_image')
                        All assigned design codes already have images.
                    @else
                        No design codes have been assigned to this craftsman yet.
                    @endif
                </p>
                @if(request()->filled('search') || request()->filled('filter'))
                    <a href="{{ route('admin.craftsman_images.show', $craftsman) }}" class="inline-block mt-4 px-4 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                        Clear Filters
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($designCodes->hasPages())
        <div class="bg-white px-6 py-4 rounded-xl border border-slate-200 shadow-sm">
            {{ $designCodes->links() }}
        </div>
    @endif

    <!-- Alpine.js Image Lightbox Modal -->
    <div x-show="modalOpen"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4"
         @keydown.escape.window="closeModal()"
         x-cloak>
        
        <div class="relative bg-white rounded-2xl max-w-2xl w-full overflow-hidden shadow-2xl border border-slate-800/10"
             @click.away="closeModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
                <div>
                    <h3 class="text-base font-bold text-slate-900 font-mono" x-text="activeCode"></h3>
                    <p class="text-xs text-slate-500" x-text="activeNickname ? activeNickname : 'No nickname'"></p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="activeImage" target="_blank" download class="p-2 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-200 transition-colors" title="Open full size in new tab">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    <button type="button" @click="closeModal()" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Image Preview -->
            <div class="p-6 bg-slate-900/5 flex items-center justify-center min-h-[300px] max-h-[70vh] overflow-hidden">
                <img :src="activeImage" :alt="activeCode" class="max-w-full max-h-[65vh] object-contain rounded-lg shadow-sm">
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-slate-100 bg-white flex items-center justify-between text-xs text-slate-500">
                <span>Craftsman: <strong class="text-slate-700">{{ $craftsman->name }}</strong></span>
                <button type="button" @click="closeModal()" class="px-4 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-medium hover:bg-black transition-colors">
                    Close Preview
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function craftsmanImageGallery() {
        return {
            modalOpen: false,
            activeImage: '',
            activeCode: '',
            activeNickname: '',
            openModal(url, code, nickname) {
                this.activeImage = url;
                this.activeCode = code;
                this.activeNickname = nickname;
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
                this.activeImage = '';
                this.activeCode = '';
                this.activeNickname = '';
            }
        }
    }
</script>
@endsection
