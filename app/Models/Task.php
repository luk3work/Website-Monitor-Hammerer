<?php

namespace App\Models;

use App\Enums\Severity;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'severity'       => Severity::class,
        'status'         => TaskStatus::class,
        'due_date'       => 'date',
        'resolved_at'    => 'datetime',
        'auto_generated' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'blocked']);
    }
}
