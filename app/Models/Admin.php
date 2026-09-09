<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Override;

class Admin extends Authenticatable
{

    use HasFactory, Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name','email',
        'email_verified_at',
        'session_id', 'password', 'is_active'
    ];

    protected $casts = [
        'password',
        'remembertoken'
    ];

    #[Override]
    protected function casts()
    {
        return[
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
