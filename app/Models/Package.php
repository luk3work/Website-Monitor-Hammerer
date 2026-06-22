<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'requires'      => 'array',
        'requires_any'  => 'array',
        'excludes'      => 'array',
        'is_active'     => 'boolean',
        'price_once'    => 'decimal:2',
        'price_monthly' => 'decimal:2',
        'price_yearly'  => 'decimal:2',
    ];

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_packages')
            ->withPivot(['state', 'note'])
            ->withTimestamps();
    }

    /** Kompakte Preisdarstellung für Listen. */
    public function priceLabel(): string
    {
        $parts = [];
        if ($this->price_once)    { $parts[] = number_format((float) $this->price_once, 0, ',', '.') . ' € einmalig'; }
        if ($this->price_monthly) { $parts[] = number_format((float) $this->price_monthly, 2, ',', '.') . ' €/M'; }
        if ($this->price_yearly)  { $parts[] = number_format((float) $this->price_yearly, 0, ',', '.') . ' €/J'; }

        return $parts ? implode(' + ', $parts) : '–';
    }
}
