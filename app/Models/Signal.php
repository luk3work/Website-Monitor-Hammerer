<?php

namespace App\Models;

use App\Enums\Severity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signal extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'severity'    => Severity::class,
        'triage'      => 'array',
        'received_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(SignalSource::class, 'signal_source_id');
    }
}
