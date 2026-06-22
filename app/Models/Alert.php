<?php

namespace App\Models;

use App\Enums\Severity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'severity'    => Severity::class,
        'sent_at'     => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(AlertChannel::class, 'alert_channel_id');
    }
}
