<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obligation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'applies_when' => 'array',
        'is_active'    => 'boolean',
    ];

    public function siteObligations(): HasMany
    {
        return $this->hasMany(SiteObligation::class);
    }
}
