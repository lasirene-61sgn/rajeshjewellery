<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Override;

class WorkOrder extends Model
{


    use HasFactory;

    protected $table = 'work_orders';

    protected $fillable = [
        'work_order_no',
        'reference_no',
        'product_name',
        'design_code',
        'design_nickname',
        'category',
        'subcategory',
        'unit_type',
        'quantity',
        'screw_type',
        'size',
        'length',
        'rhodium_polish',
        'hallmark_purity',
        'target_weight',
        'due_date',
        'job_type',
        'instructions',
        'design_image',
        'craftsman_id',
        'status',
        'allocated_at',
        'accepted_at',
        'submitted_at',
        'completed_at',
        'return_reason',
        'return_image',
        'return_due_date',
        'return_count',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'rhodium_polish' => 'boolean',
            'target_weight' => 'decimal:3',
            'quantity' => 'integer',
            'return_count' => 'integer',
            'due_date'  => 'date',
            'return_due_date' => 'date',
            'allocated_at' => 'datetime',
            'accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $order) {
            if (empty($order->work_order_no)) {
                $year = now()->format('Y');
                $prefix = "WO-{$year}-";

                // Find the highest numeric suffix currently saved for this year
                $latestMax = DB::table('work_orders')
                    ->where('work_order_no', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->selectRaw("MAX(CAST(SUBSTRING(work_order_no, 9) AS UNSIGNED)) as max_no")
                    ->value('max_no');

                $nextNumber = ($latestMax ? (int) $latestMax : 0) + 1;

                $order->work_order_no = sprintf('%s%04d', $prefix, $nextNumber);
            }
        });
    }

    public function craftsman(): BelongsTo
    {
        return $this->belongsTo(Craftsman::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeAllocated(Builder $query): Builder
    {
        return $query->where('status', 'allocated');
    }

    public function scopeInProcess(Builder $query): Builder
    {
        return $query->where('status', ['inprocess', 'returned']);
    }

    public function scopeForApproval(Builder $query): Builder
    {
        return $query->where('status', 'for_approval');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverDue(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where('status', '!=', 'completed')
            ->where(function ($q) use ($today) {
                $q->where(function ($sub) use ($today) {
                    $sub->whereNotNull('return_due_date')
                        ->where('return_due_date', '<', $today);
                })->orWhere(function ($sub) use ($today) {
                    $sub->whereNull('return_due_date')
                        ->where('due_date', '<', $today);
                });
            });
    }

    public function getEffectiveNicknameAttribute(): ?string
    {
        // 1. If work order has its own nickname (and it's not just the code), use it
        if (!empty($this->design_nickname) && $this->design_nickname !== $this->design_code) {
            return $this->design_nickname;
        }

        // 2. Otherwise, look it up dynamically from the DesignCode master table
        $master = DB::table('design_codes')->where('code', $this->design_code)->first();

        // Only return nickname if it's a real name, not the code itself
        if ($master && !empty($master->nickname) && $master->nickname !== $master->code) {
            return $master->nickname;
        }

        return null;
    }
}
