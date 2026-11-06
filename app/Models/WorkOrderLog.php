<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderLog extends Model
{
    use HasFactory;

    protected $table = 'work_order_logs';

    protected $fillable = [
        'work_order_id',
        'action',
        'status',
        'attempt_number',
        'actor_type',
        'actor_id',
        'actor_name',
        'note',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'attempt_number' => 'integer',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * Get timestamp in Asia/Kolkata timezone
     */
    public function getKolkataTimeAttribute(): Carbon
    {
        return $this->created_at ? $this->created_at->timezone('Asia/Kolkata') : Carbon::now('Asia/Kolkata');
    }

    /**
     * Human-friendly action title
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created'             => 'Work Order Created',
            'allocated'           => 'Craftsman Allocated',
            'reallocated'         => 'Reallocated to Craftsman',
            'undo_allocation'     => 'Allocation Cleared (Undone)',
            'in_process'          => 'In Process (Accepted)',
            'for_approval'        => 'Submitted for QC Approval',
            'partially_completed' => 'Partially Completed' . ($this->attempt_number ? " (#{$this->attempt_number})" : ''),
            'completed'           => 'Work Order Completed',
            'undo_completion'     => 'Completion Undone',
            'returned'            => 'Returned for Rework' . ($this->attempt_number ? " (#{$this->attempt_number})" : ''),
            'overdue'             => 'Work Order Overdue',
            'rejected'            => 'Order Rejected',
            'reset'               => 'Progress Reset',
            default               => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Tailwind visual theme styling
     */
    public function getThemeAttribute(): array
    {
        return match ($this->action) {
            'created' => [
                'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
                'dot'   => 'bg-blue-500',
                'border'=> 'border-blue-400',
            ],
            'allocated', 'reallocated' => [
                'badge' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'dot'   => 'bg-indigo-500',
                'border'=> 'border-indigo-400',
            ],
            'in_process' => [
                'badge' => 'bg-sky-100 text-sky-800 border-sky-200',
                'dot'   => 'bg-sky-500',
                'border'=> 'border-sky-400',
            ],
            'for_approval' => [
                'badge' => 'bg-purple-100 text-purple-800 border-purple-200',
                'dot'   => 'bg-purple-500',
                'border'=> 'border-purple-400',
            ],
            'partially_completed' => [
                'badge' => 'bg-teal-100 text-teal-800 border-teal-200',
                'dot'   => 'bg-teal-500',
                'border'=> 'border-teal-400',
            ],
            'completed' => [
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'dot'   => 'bg-emerald-500',
                'border'=> 'border-emerald-400',
            ],
            'undo_completion', 'undo_allocation' => [
                'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                'dot'   => 'bg-amber-500',
                'border'=> 'border-amber-400',
            ],
            'returned', 'rejected' => [
                'badge' => 'bg-rose-100 text-rose-800 border-rose-200',
                'dot'   => 'bg-rose-500',
                'border'=> 'border-rose-400',
            ],
            'overdue' => [
                'badge' => 'bg-red-100 text-red-900 border-red-300',
                'dot'   => 'bg-red-600 animate-pulse',
                'border'=> 'border-red-500',
            ],
            default => [
                'badge' => 'bg-slate-100 text-slate-800 border-slate-200',
                'dot'   => 'bg-slate-500',
                'border'=> 'border-slate-300',
            ],
        };
    }
}

