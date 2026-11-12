@extends('layouts.admin')

@section('title', 'Work Order Details - ' . $workOrder->work_order_no)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ returnModalOpen: false }">
    <!-- Top Action / Title Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-800">Work Order Details</h1>
                @php
                    $statusThemes = [
                        'pending'             => 'bg-amber-100 text-amber-800 border-amber-200',
                        'allocated'           => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                        'in_process'          => 'bg-sky-100 text-sky-800 border-sky-200',
                        'for_approval'        => 'bg-purple-100 text-purple-800 border-purple-200',
                        'partially_completed' => 'bg-teal-100 text-teal-800 border-teal-200',
                        'completed'           => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'returned'            => 'bg-rose-100 text-rose-800 border-rose-200',
                        'rejected'            => 'bg-red-100 text-red-800 border-red-200',
                    ];
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $statusThemes[$workOrder->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ ucwords(str_replace('_', ' ', $workOrder->status)) }}
                </span>
            </div>
            <p class="text-sm text-slate-500 font-mono mt-0.5">{{ $workOrder->work_order_no }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($workOrder->status === 'completed')
                <!-- Undo Completion Button -->
                <form method="POST" action="{{ route('admin.work_orders.undo-completion', $workOrder) }}" onsubmit="return confirm('Are you sure you want to undo completion? This will revert the work order back to in-process for review or rework.');" class="inline">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 text-xs font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded-lg shadow-sm transition-colors flex items-center gap-1.5" title="Reopen completed work order">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Undo Completion
                    </button>
                </form>

                <!-- Return Button for Completed -->
                <button type="button" @click="returnModalOpen = true" class="px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-300 rounded-lg shadow-sm transition-colors flex items-center gap-1.5" title="Return completed order for rework">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>
                    Return for Rework
                </button>
            @elseif($workOrder->status === 'for_approval')
                <!-- Approve Button -->
                <form method="POST" action="{{ route('admin.work_orders.approve', $workOrder) }}" onsubmit="return confirm('Approve and mark this order fully completed?');" class="inline">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Approve & Complete
                    </button>
                </form>

                <!-- Return Button -->
                <button type="button" @click="returnModalOpen = true" class="px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-300 rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>
                    Return for Rework
                </button>
            @elseif($workOrder->status === 'allocated')
                <!-- Undo Allocation Button -->
                <form method="POST" action="{{ route('admin.work_orders.undo', $workOrder) }}" onsubmit="return confirm('Undo craftsman allocation for this order?');" class="inline">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg shadow-sm transition-colors">
                        Undo Allocation
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.work_orders.edit', $workOrder) }}" class="px-3.5 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-sm">
                Edit
            </a>

            <a href="{{ $backUrl ?? route('admin.work_orders.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-sm">
                Back to Orders
            </a>
        </div>
    </div>

    <!-- Rejection Banner if rejected -->
    @if($workOrder->status === 'rejected')
        <div class="p-5 bg-rose-50 border border-rose-200 rounded-2xl space-y-2 shadow-sm">
            <div class="flex items-center gap-2 text-rose-700 font-bold text-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                Order Rejected by Craftsman ({{ optional($workOrder->craftsman)->name ?? 'Assigned Craftsman' }})
            </div>
            <p class="text-sm text-rose-900 bg-white/80 p-3 rounded-xl border border-rose-200">
                <strong>Reason for Rejection:</strong> {{ $workOrder->reject_reason ?? 'No reason provided.' }}
            </p>

            <!-- Reallocate to Another Craftsman Form -->
            <form method="POST" action="{{ route('admin.work_orders.allocate', $workOrder) }}" class="flex flex-wrap items-center gap-3 pt-3 border-t border-rose-200">
                @csrf
                <span class="text-xs font-bold uppercase text-slate-700">Reallocate To:</span>
                <select name="craftsman_id" required class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Choose Another Craftsman --</option>
                    @foreach(\App\Models\Craftsman::orderBy('name')->get() as $c)
                        <option value="{{ $c->id }}" {{ $c->id == $workOrder->craftsman_id ? 'disabled' : '' }}>
                            {{ $c->name }} ({{ $c->mobile }}) {{ $c->id == $workOrder->craftsman_id ? '- (Previously Rejected)' : '' }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="due_date" value="{{ optional($workOrder->due_date)->format('Y-m-d') }}" class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white" title="Due Date">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors">
                    Confirm Reallocation
                </button>
            </form>
        </div>
    @endif

    <!-- Specifications Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 space-y-6">
        @if($workOrder->design_image)
            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-2">Design Image</span>
                <div class="relative inline-block">
                    <img src="{{ asset('storage/' . $workOrder->design_image) }}" alt="Design Image" class="w-36 h-36 object-cover rounded-xl border border-slate-200 shadow-sm">
                    @if($workOrder->has_seal || stripos((string)$workOrder->reference_no, 'ESO') !== false || !empty($workOrder->seal))
                        <span class="absolute bottom-2 right-2 bg-amber-500 text-white text-[11px] font-black px-2 py-0.5 rounded shadow uppercase">
                            916 SEAL
                        </span>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Reference Number</span>
                <p class="text-sm font-medium text-slate-800 flex items-center gap-2">
                    <span>{{ $workOrder->reference_no ?? 'N/A' }}</span>
                </p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Product Name</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->product_name }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Design Code & Nickname</span>
                <p class="text-sm font-mono font-bold text-indigo-600">
                    {{ $workOrder->design_code }} 
                    @if($workOrder->effective_nickname)
                        <span class="font-sans font-normal text-slate-600">({{ $workOrder->effective_nickname }})</span>
                    @endif
                </p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Assigned Craftsman</span>
                @if($workOrder->craftsman)
                    <p class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        {{ $workOrder->craftsman->name }}
                        <span class="text-xs text-slate-400 font-normal">({{ $workOrder->craftsman->mobile }})</span>
                    </p>
                @else
                    <p class="text-sm font-medium text-amber-600 italic">Unassigned (Pending Allocation)</p>
                @endif
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Craftsman S.No</span>
                <p class="text-sm font-bold text-slate-800 font-mono">
                    @if($workOrder->craftsman_serial_no)
                        <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            #{{ $workOrder->craftsman_serial_no }}
                        </span>
                    @else
                        <span class="text-slate-400 font-normal italic">N/A</span>
                    @endif
                </p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Quantity & Progress</span>
                <p class="text-sm font-medium text-slate-800">
                    Total: <strong class="text-slate-900">{{ $workOrder->quantity }} {{ $workOrder->unit_type }}</strong>
                    <span class="mx-1 text-slate-300">|</span>
                    Done: <strong class="text-emerald-700">{{ $workOrder->completed_quantity ?? 0 }}</strong>
                    <span class="mx-1 text-slate-300">|</span>
                    Bal: <strong class="text-amber-600">{{ $workOrder->balance_quantity }}</strong>
                </p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Category / Subcategory</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->category ?? 'N/A' }} {{ $workOrder->subcategory ? ' / ' . $workOrder->subcategory : '' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Screw Type</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->screw_type ?? 'None / N/A' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Size & Length</span>
                <p class="text-sm font-medium text-slate-800">Size: {{ $workOrder->size ?? 'N/A' }} | Length: {{ $workOrder->length ?? 'N/A' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Target Weight</span>
                <p class="text-sm font-medium text-slate-800">{{ number_format($workOrder->target_weight, 3) }}g</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Gold Hallmark / Purity</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->hallmark_purity ?? 'N/A' }}</p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Due Date</span>
                <p class="text-sm font-medium text-slate-800">
                    {{ optional($workOrder->due_date)->format('d M Y') }}
                    @if($workOrder->return_due_date)
                        <span class="block text-xs font-semibold text-rose-600">Rework Due: {{ optional($workOrder->return_due_date)->format('d M Y') }}</span>
                    @endif
                </p>
            </div>

            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Job Type</span>
                <p class="text-sm font-medium text-slate-800">{{ $workOrder->job_type ?? 'N/A' }}</p>
            </div>
        </div>

        @if($workOrder->instructions)
            <div>
                <span class="block text-xs font-semibold uppercase text-slate-400 mb-1">Instructions / Notes</span>
                <p class="text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100 whitespace-pre-line">{{ $workOrder->instructions }}</p>
            </div>
        @endif
    </div>

    <!-- LOG HISTORY & ACTIVITY TIMELINE CARD -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h2 class="text-lg font-bold text-slate-800">Log History & Lifecycle Timeline</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Chronological activity log recorded in <span class="font-semibold text-indigo-700">Asia/Kolkata (IST)</span> timezone. Includes allocations, progress, rework loops, and partial completion counts.
                </p>
            </div>
            <div class="shrink-0 text-right">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                    Timezone: Asia/Kolkata
                </span>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            @if($workOrder->logs->isNotEmpty())
                <div class="relative pl-6 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                    @foreach($workOrder->logs as $log)
                        @php
                            $theme = $log->theme;
                            $kolkataTime = $log->kolkata_time;
                        @endphp
                        <div class="relative group">
                            <!-- Bullet Marker -->
                            <div class="absolute -left-[27px] sm:-left-[35px] top-1 w-4 h-4 rounded-full border-2 border-white {{ $theme['dot'] }} shadow ring-2 ring-slate-100"></div>

                            <!-- Content Card -->
                            <div class="bg-slate-50/70 hover:bg-slate-50 border border-slate-200/90 rounded-xl p-4 transition-all">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 mb-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold border {{ $theme['badge'] }}">
                                            {{ $log->action_label }}
                                        </span>

                                        @if($log->action === 'partially_completed' && $log->attempt_number)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                                Attempt #{{ $log->attempt_number }}
                                            </span>
                                        @endif

                                        @if($log->action === 'returned' && $log->attempt_number)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                Rework Cycle #{{ $log->attempt_number }}
                                            </span>
                                        @endif

                                        <span class="text-xs text-slate-500 flex items-center gap-1">
                                            <span>by</span>
                                            <strong class="text-slate-700">{{ $log->actor_name ?? 'System' }}</strong>
                                            <span class="text-[11px] text-slate-400">({{ ucfirst($log->actor_type ?? 'system') }})</span>
                                        </span>
                                    </div>

                                    <!-- Date & Time (Asia/Kolkata) -->
                                    <div class="text-xs font-mono text-slate-600 bg-white px-2.5 py-1 rounded-md border border-slate-200 shadow-2xs shrink-0 self-start sm:self-auto flex items-center gap-1.5" title="Asia/Kolkata Time">
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ $kolkataTime->format('d M Y') }}</span>
                                        <span class="text-slate-300">|</span>
                                        <span class="font-bold text-slate-800">{{ $kolkataTime->format('h:i:s A') }}</span>
                                        <span class="text-[10px] text-slate-400 font-sans font-medium">IST</span>
                                    </div>
                                </div>

                                <!-- Note Description -->
                                @if($log->note)
                                    <p class="text-sm text-slate-700 mt-1 font-sans">{{ $log->note }}</p>
                                @endif

                                <!-- Additional Details Pill/Box -->
                                @if(!empty($log->details))
                                    <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex flex-wrap items-center gap-3 text-xs text-slate-600">
                                        @if(isset($log->details['completed_quantity']))
                                            <div class="inline-flex items-center gap-1 bg-white px-2 py-0.5 rounded border border-slate-200">
                                                <span class="text-slate-400">Completed Qty:</span>
                                                <strong class="text-emerald-700">{{ $log->details['completed_quantity'] }}</strong>
                                                @if(isset($log->details['total_quantity']))
                                                    <span class="text-slate-400">/ {{ $log->details['total_quantity'] }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if(isset($log->details['balance_quantity']))
                                            <div class="inline-flex items-center gap-1 bg-white px-2 py-0.5 rounded border border-slate-200">
                                                <span class="text-slate-400">Balance:</span>
                                                <strong class="text-amber-700">{{ $log->details['balance_quantity'] }}</strong>
                                            </div>
                                        @endif

                                        @if(isset($log->details['craftsman_name']))
                                            <div class="inline-flex items-center gap-1 bg-white px-2 py-0.5 rounded border border-slate-200">
                                                <span class="text-slate-400">Craftsman:</span>
                                                <strong class="text-slate-800">{{ $log->details['craftsman_name'] }}</strong>
                                            </div>
                                        @endif

                                        @if(isset($log->details['return_reason']))
                                            <div class="w-full bg-rose-50/70 text-rose-900 border border-rose-200 p-2.5 rounded-lg mt-1">
                                                <strong>Return Reason:</strong> {{ $log->details['return_reason'] }}
                                                @if(isset($log->details['return_due_date']))
                                                    <span class="block text-[11px] text-rose-700 mt-0.5">Target Rework Date: {{ \Carbon\Carbon::parse($log->details['return_due_date'])->format('d M Y') }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if(isset($log->details['reason']) && !isset($log->details['return_reason']))
                                            <div class="w-full bg-red-50/70 text-red-900 border border-red-200 p-2.5 rounded-lg mt-1">
                                                <strong>Rejection Reason:</strong> {{ $log->details['reason'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 px-4 bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3 class="text-sm font-semibold text-slate-700">No History Logged Yet</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                        Timeline events and loop actions (allocations, starts, approval submissions, partial progress, returns, and completions) will be logged here in Asia/Kolkata timezone.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal: Return for Rework -->
    <div x-show="returnModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="returnModalOpen = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <h3 class="text-lg font-bold text-rose-700 flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>
                    Return Order for Rework
                </h3>
                <button type="button" @click="returnModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="text-xs text-slate-500">
                Returning work order <strong class="text-slate-800">{{ $workOrder->work_order_no }}</strong> to craftsman for correction or rework. This will be recorded on the timeline.
            </p>

            <form action="{{ route('admin.work_orders.return', $workOrder) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Return Due Date *</label>
                    <input type="date" name="return_due_date" required min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d', strtotime('+3 days')) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Return / Defect Explanation *</label>
                    <textarea name="return_reason" required rows="3" placeholder="Explain the defect, alteration, or reason for return..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Photo Proof (Optional)</label>
                    <input type="file" name="return_image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button type="button" @click="returnModalOpen = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
