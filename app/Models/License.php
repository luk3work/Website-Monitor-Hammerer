<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'license_key' => 'encrypted', // at rest verschlüsselt
        'expires_at'  => 'date',
        'cost'        => 'decimal:2',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
