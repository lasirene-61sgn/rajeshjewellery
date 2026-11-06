<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLog extends Model
{
    use HasFactory;

    protected $table = 'order_logs';

    protected $fillable = [
        'order_id',
        'action',
        'status',
        'actor_type',
        'actor_id',
        'actor_name',
        'note',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
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
            'created'         => 'Order Created',
            'allocated'       => 'Craftsman Allocated',
            'reallocated'     => 'Craftsman Reallocated',
            'in_progress'     => 'In Progress',
            'completed'       => 'Order Completed',
            'force_completed' => 'Force Completed by Admin',
            'updated'         => 'Order Updated',
            'overdue'         => 'Order Overdue',
            default           => ucwords(str_replace('_', ' ', $this->action)),
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
            'in_progress' => [
                'badge' => 'bg-sky-100 text-sky-800 border-sky-200',
                'dot'   => 'bg-sky-500',
                'border'=> 'border-sky-400',
            ],
            'completed', 'force_completed' => [
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'dot'   => 'bg-emerald-500',
                'border'=> 'border-emerald-400',
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

