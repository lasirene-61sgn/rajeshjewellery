@extends('layouts.admin')

@section('title', 'Design Codes Master')

@section('content')
<div class="space-y-6" x-data="designCodeManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Master Design Codes</h1>
            <p class="text-sm text-slate-500">Manage all registered design codes, master nicknames, and their global images.</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.design_codes.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by design code or nickname..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-black text-white text-sm font-medium rounded-lg transition-colors">
                    Search
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.design_codes.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Design Code</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nickname</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Craftsmen</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($designCodes as $design)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($design->image)
                                    <img src="{{ Storage::url($design->image) }}" class="w-12 h-12 rounded object-cover border border-slate-200 shadow-sm cursor-pointer hover:opacity-80 transition-opacity" @click="openImageModal('{{ Storage::url($design->image) }}')">
                                @else
                                    <div class="w-12 h-12 rounded bg-slate-100 flex items-center justify-center text-slate-400 text-xs border border-slate-200">
                                        N/A
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900">{{ $design->code }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-700">{{ $design->nickname ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $design->craftsmen_count > 0 ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $design->craftsmen_count }} Craftsmen
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                <button @click="openEditModal({{ $design->id }}, '{{ $design->code }}', {{ json_encode($design->nickname ?? '') }}, {{ $design->craftsmen->pluck('id') }})" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    Edit
                                </button>
                                <form action="{{ route('admin.design_codes.destroy', $design) }}" method="POST" class="inline" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete this Design Code? This will completely wipe it from the master catalog.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-900 font-semibold">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center">
                                <div class="text-slate-400 mb-1">
                                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <p class="text-slate-500 font-medium">No design codes found in the catalog.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($designCodes->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $designCodes->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Modal -->
    <div x-show="editModalOpen" style="display: none" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="editModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="closeEditModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="editModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <form :action="editFormAction" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-slate-900" id="modal-title" x-text="'Edit Design Code: ' + currentCode">
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Design Nickname</label>
                                        <input type="text" name="nickname" x-model="currentNickname" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Update Master Image</label>
                                        <input type="file" name="image" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Assigned Craftsmen</label>
                                        <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1 bg-slate-50">
                                            @foreach($craftsmen as $craftsman)
                                                <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer transition-colors">
                                                    <input type="checkbox" name="craftsmen_ids[]" value="{{ $craftsman->id }}" x-model="currentCraftsmenIds" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 mr-3">
                                                    <span class="text-sm text-slate-700 font-medium">{{ $craftsman->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl">
                        <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Save Changes
                        </button>
                        <button type="button" @click="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div x-show="imageModalOpen" style="display: none" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="imageModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="imageModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="imageModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-middle bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-3xl w-full">
                <div class="relative">
                    <button @click="imageModalOpen = false" class="absolute top-4 right-4 bg-white/50 hover:bg-white rounded-full p-2 backdrop-blur-sm transition-colors text-slate-900 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <img :src="modalImageUrl" class="w-full h-auto object-contain max-h-[80vh]">
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function designCodeManager() {
        return {
            editModalOpen: false,
            imageModalOpen: false,
            modalImageUrl: '',
            currentId: null,
            currentCode: '',
            currentNickname: '',
            currentCraftsmenIds: [],
            editFormAction: '',

            openEditModal(id, code, nickname, craftsmenIds) {
                this.currentId = id;
                this.currentCode = code;
                this.currentNickname = nickname;
                this.currentCraftsmenIds = craftsmenIds.map(String); // ensure string comparison works
                this.editFormAction = `/admin/design-codes/${id}`;
                this.editModalOpen = true;
            },

            closeEditModal() {
                this.editModalOpen = false;
            },

            openImageModal(url) {
                this.modalImageUrl = url;
                this.imageModalOpen = true;
            }
        }
    }
</script>
@endsection
