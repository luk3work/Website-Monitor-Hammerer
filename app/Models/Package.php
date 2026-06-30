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

    /** Passendes Tabler-Icon abgeleitet aus key/group/category/name. */
    public function iconClass(): string
    {
        $k = strtolower(($this->key ?? '') . ' ' . ($this->group ?? '') . ' ' . ($this->category ?? '') . ' ' . ($this->name ?? ''));

        return match (true) {
            str_contains($k, 'domain')                                   => 'ti-world',
            str_contains($k, 'mail')                                     => 'ti-mail',
            str_contains($k, 'hosting'), str_contains($k, 'webspace')    => 'ti-server',
            str_contains($k, 'update')                                   => 'ti-refresh',
            str_contains($k, 'seo')                                      => 'ti-trending-up',
            str_contains($k, 'performance')                              => 'ti-bolt',
            str_contains($k, 'report')                                   => 'ti-chart-bar',
            str_contains($k, 'datenschutz'), str_contains($k, 'cookie')  => 'ti-shield-lock',
            str_contains($k, 'security'), str_contains($k, 'sicherheit') => 'ti-shield-check',
            str_contains($k, 'a11y'), str_contains($k, 'barriere')       => 'ti-accessible',
            default                                                      => 'ti-package',
        };
    }
}
