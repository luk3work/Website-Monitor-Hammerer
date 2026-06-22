<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignalSource extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_polled_at' => 'datetime',
    ];

    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class);
    }
}
