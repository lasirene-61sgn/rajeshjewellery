<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DesignCode extends Model
{
    protected $table = 'design_codes';

    protected $fillable = [
        'code',
        'nickname',
    ];

    public function craftsmen(): BelongsToMany{
        return $this->belongsToMany(Craftsman::class, 'craftsman_design_code');
    }
}
