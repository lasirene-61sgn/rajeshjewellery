@extends('layouts.admin')

@section('title', $craftsman->name . ' - Design Images')

@section('content')
@php
    $galleryImages = collect($designCodes->items())
        ->filter(fn($d) => !empty($d->image))
        ->values()
        ->map(fn($d) => [
            'id'           => $d->id,
            'code'         => $d->code,
            'nickname'     => $d->nickname ?? '',
            'product_name' => $d->product_name ?? '',
            'image'        => Storage::url($d->image),
        ])
        ->values();
@endphp
<div class="space-y-6" x-data="craftsmanImageGallery(@js($galleryImages))">
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
        <a href="{{ route('admin.craftsman_images.show', ['craftsman' => $craftsman, 'search' => request('search'), 'product_name' => request('product_name'), 'nickname' => request('nickname')]) }}"
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

        <a href="{{ route('admin.craftsman_images.show', ['craftsman' => $craftsman, 'filter' => 'with_image', 'search' => request('search'), 'product_name' => request('product_name'), 'nickname' => request('nickname')]) }}"
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

        <a href="{{ route('admin.craftsman_images.show', ['craftsman' => $craftsman, 'filter' => 'without_image', 'search' => request('search'), 'product_name' => request('product_name'), 'nickname' => request('nickname')]) }}"
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

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.craftsman_images.show', $craftsman) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            @if(request('filter'))
                <input type="hidden" name="filter" value="{{ request('filter') }}">
            @endif

            <!-- Keyword Search -->
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search design code, nickname, or product..."
                       class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Product Name Dropdown Filter -->
            <div class="w-full sm:w-52">
                <select name="product_name"
                        onchange="this.form.submit()"
                        class="w-full py-2 px-3 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-slate-700">
                    <option value="">All Product Names</option>
                    @foreach($productNames as $pName)
                        <option value="{{ $pName }}" {{ request('product_name') == $pName ? 'selected' : '' }}>
                            {{ $pName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Design Nickname Dropdown Filter -->
            <div class="w-full sm:w-52">
                <select name="nickname"
                        onchange="this.form.submit()"
                        class="w-full py-2 px-3 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-slate-700">
                    <option value="">All Nicknames</option>
                    @foreach($nicknames as $nName)
                        <option value="{{ $nName }}" {{ (request('nickname') == $nName || request('design_nickname') == $nName) ? 'selected' : '' }}>
                            {{ $nName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-slate-900 hover:bg-black text-white text-sm font-medium rounded-lg transition-colors">
                    Filter
                </button>
                @if(request()->filled('search') || request()->filled('filter') || request()->filled('product_name') || request()->filled('nickname') || request()->filled('design_nickname'))
                    <a href="{{ route('admin.craftsman_images.show', $craftsman) }}" class="w-full sm:w-auto text-center px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors whitespace-nowrap">
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
                             @click="openModalById({{ $design->id }})">
                        
                        <!-- Overlay zoom button on hover -->
                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer"
                             @click="openModalById({{ $design->id }})">
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
                    @if($design->product_name)
                        <p class="text-[11px] font-semibold text-indigo-600 truncate mt-0.5" title="{{ $design->product_name }}">
                            {{ $design->product_name }}
                        </p>
                    @endif
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
                    @if(request()->filled('nickname') && request()->filled('search'))
                        No design code matched nickname "{{ request('nickname') }}" and search "{{ request('search') }}".
                    @elseif(request()->filled('nickname'))
                        No design code matched nickname "{{ request('nickname') }}" for this craftsman.
                    @elseif(request()->filled('product_name') && request()->filled('search'))
                        No design code matched product "{{ request('product_name') }}" and search "{{ request('search') }}".
                    @elseif(request()->filled('product_name'))
                        No design code matched product "{{ request('product_name') }}" for this craftsman.
                    @elseif(request()->filled('search'))
                        No design code matched "{{ request('search') }}" in this folder.
                    @elseif(request('filter') === 'with_image')
                        This craftsman has no design codes with images uploaded yet.
                    @elseif(request('filter') === 'without_image')
                        All assigned design codes already have images.
                    @else
                        No design codes have been assigned to this craftsman yet.
                    @endif
                </p>
                @if(request()->filled('search') || request()->filled('filter') || request()->filled('product_name') || request()->filled('nickname') || request()->filled('design_nickname'))
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
         @keydown.arrow-right.window="modalOpen && nextImage()"
         @keydown.arrow-left.window="modalOpen && prevImage()"
         x-cloak>
        
        <div class="relative bg-white rounded-2xl max-w-3xl w-full overflow-hidden shadow-2xl border border-slate-800/10"
             @click.away="closeModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900 font-mono" x-text="activeCode"></h3>
                            <template x-if="activeProduct">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="activeProduct"></span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="activeNickname ? activeNickname : 'No nickname'"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-200/80 text-slate-700 border border-slate-300"
                          x-show="totalImages > 0"
                          x-text="`${currentIndex + 1} / ${totalImages}`">
                    </span>
                    <a :href="activeImage" target="_blank" download class="p-2 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-200 transition-colors" title="Open full size in new tab">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    <button type="button" @click="closeModal()" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-200 transition-colors" title="Close (Esc)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Image Preview: Click on image advances to next image until the end -->
            <div class="relative p-6 bg-slate-900/5 flex items-center justify-center min-h-[340px] max-h-[70vh] overflow-hidden select-none">
                <!-- Floating Previous Button -->
                <button type="button"
                        x-show="hasPrev"
                        @click.stop="prevImage()"
                        class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-slate-900/70 hover:bg-slate-900 text-white flex items-center justify-center shadow-lg backdrop-blur-sm transition-all hover:scale-105 focus:outline-none"
                        title="Previous Image (Left Arrow)">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Clickable Image Container -->
                <div class="relative group/modalImg flex items-center justify-center">
                    <img :src="activeImage"
                         :alt="activeCode"
                         class="max-w-full max-h-[65vh] object-contain rounded-lg shadow-sm transition-all"
                         :class="hasNext ? 'cursor-pointer hover:opacity-95' : 'cursor-default'"
                         @click="nextImage()"
                         :title="hasNext ? 'Click image to show next (' + (currentIndex + 2) + '/' + totalImages + ')' : 'Last image in this gallery'">

                    <!-- Hover hint pill on image if next image exists -->
                    <template x-if="hasNext">
                        <div @click="nextImage()"
                             class="absolute bottom-3 px-3 py-1 rounded-full bg-slate-900/75 text-white text-xs font-medium opacity-0 group-hover/modalImg:opacity-100 transition-opacity cursor-pointer backdrop-blur-sm shadow pointer-events-auto">
                            Click image for next &rarr;
                        </div>
                    </template>
                </div>

                <!-- Floating Next Button -->
                <button type="button"
                        x-show="hasNext"
                        @click.stop="nextImage()"
                        class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-slate-900/70 hover:bg-slate-900 text-white flex items-center justify-center shadow-lg backdrop-blur-sm transition-all hover:scale-105 focus:outline-none"
                        title="Next Image (Right Arrow or Click Image)">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-slate-100 bg-white flex items-center justify-between text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <span>Craftsman: <strong class="text-slate-700">{{ $craftsman->name }}</strong></span>
                    <template x-if="hasNext">
                        <span class="text-indigo-600 font-medium ml-2">Click image to show next</span>
                    </template>
                    <template x-if="!hasNext && totalImages > 1">
                        <span class="text-amber-600 font-semibold ml-2">End of images</span>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="prevImage()"
                            :disabled="!hasPrev"
                            :class="hasPrev ? 'hover:bg-slate-100 text-slate-700 border-slate-300' : 'opacity-40 cursor-not-allowed text-slate-400 border-slate-200'"
                            class="px-3 py-1.5 border rounded-lg text-xs font-medium transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Prev
                    </button>
                    <button type="button"
                            @click="nextImage()"
                            :disabled="!hasNext"
                            :class="hasNext ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'opacity-40 cursor-not-allowed bg-slate-200 text-slate-400'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center gap-1">
                        Next
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button type="button" @click="closeModal()" class="px-4 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-medium hover:bg-black transition-colors ml-1">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function craftsmanImageGallery(initialImages = []) {
        return {
            modalOpen: false,
            images: initialImages || [],
            currentIndex: 0,
            activeImage: '',
            activeCode: '',
            activeNickname: '',
            activeProduct: '',

            get totalImages() {
                return this.images.length;
            },

            get hasNext() {
                return this.currentIndex < this.images.length - 1;
            },

            get hasPrev() {
                return this.currentIndex > 0;
            },

            openModalById(designId) {
                const idx = this.images.findIndex(item => item.id == designId);
                if (idx !== -1) {
                    this.currentIndex = idx;
                    this.updateActive();
                    this.modalOpen = true;
                }
            },

            openModal(url, code, nickname = '', product = '') {
                const idx = this.images.findIndex(item => item.image === url || item.code === code);
                if (idx !== -1) {
                    this.currentIndex = idx;
                    this.updateActive();
                } else {
                    this.activeImage = url;
                    this.activeCode = code;
                    this.activeNickname = nickname;
                    this.activeProduct = product;
                }
                this.modalOpen = true;
            },

            updateActive() {
                if (this.images.length === 0) return;
                const item = this.images[this.currentIndex];
                if (item) {
                    this.activeImage = item.image;
                    this.activeCode = item.code;
                    this.activeNickname = item.nickname || '';
                    this.activeProduct = item.product_name || '';
                }
            },

            nextImage() {
                if (this.currentIndex < this.images.length - 1) {
                    this.currentIndex++;
                    this.updateActive();
                }
            },

            prevImage() {
                if (this.currentIndex > 0) {
                    this.currentIndex--;
                    this.updateActive();
                }
            },

            closeModal() {
                this.modalOpen = false;
            }
        }
    }
</script>
@endsection
