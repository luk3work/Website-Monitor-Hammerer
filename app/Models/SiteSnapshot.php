<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSnapshot extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'https'        => 'boolean',
        'is_multisite' => 'boolean',
        'fingerprint'  => 'array',
        'raw'          => 'array',
        'collected_at' => 'datetime',
        'received_at'  => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
