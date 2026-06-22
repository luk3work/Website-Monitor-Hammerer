<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteObligation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'auto_matched'    => 'boolean',
        'due_date'        => 'date',
        'last_checked_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }
}
