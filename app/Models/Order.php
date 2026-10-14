<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function craftsman()
    {
        return $this->belongsTo(Craftsman::class, 'craftsman_id');
    }
}
