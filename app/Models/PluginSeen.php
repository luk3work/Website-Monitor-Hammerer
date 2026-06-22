<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginSeen extends Model
{
    protected $table = 'plugins_seen';

    protected $guarded = ['id'];

    protected $casts = [
        'active'           => 'boolean',
        'update_available' => 'boolean',
        'hold'             => 'boolean',
        'last_seen_at'     => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
