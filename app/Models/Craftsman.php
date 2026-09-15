<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Craftsman extends Authenticatable
{
    protected $table = 'craftsmen';

    protected $fillable = [
        'name', 'email', 'password', 'plain_password', 'session_id', 'is_active', 'mobile'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected function casts(): array{
        return[
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];
    } 

    public function designCodes(): BelongsToMany
    {
        return $this->belongsToMany(DesignCode::class, 'craftsman_design_code');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'craftsman_id');
    }
}
