<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'craftsman_id',
        'image',
        'reference_number',
        'product_name',
        'size',
        'length',
        'weight',
        'quantity',
        'order_type',
        'notes',
        'created_date',
        'created_at',
        'due_date',
        'status',
    ];

    protected $casts = [
        'created_date' => 'date',
        'created_at'   => 'datetime',
        'due_date'     => 'datetime',
    ];

    public function craftsman(): BelongsTo
    {
        return $this->belongsTo(Craftsman::class, 'craftsman_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class, 'order_id')->orderBy('created_at', 'asc')->orderBy('id', 'asc');
    }

    public function logActivity(
        string $action,
        ?string $note = null,
        array $details = [],
        ?string $actorType = null,
        ?int $actorId = null,
        ?string $actorName = null
    ): OrderLog {
        if ($actorType === null) {
            if (auth()->guard('admin')->check()) {
                $actorType = 'admin';
                $admin = auth()->guard('admin')->user();
                $actorId = $admin?->id;
                $actorName = $admin?->name ?? 'Admin';
            } elseif (auth()->guard('craftsman')->check()) {
                $actorType = 'craftsman';
                $craftsman = auth()->guard('craftsman')->user();
                $actorId = $craftsman?->id;
                $actorName = $craftsman?->name ?? 'Craftsman';
            } else {
                $actorType = 'system';
                $actorName = 'System';
            }
        }

        return $this->logs()->create([
            'action'     => $action,
            'status'     => $this->status,
            'actor_type' => $actorType,
            'actor_id'   => $actorId,
            'actor_name' => $actorName,
            'note'       => $note,
            'details'    => $details,
        ]);
    }
}
