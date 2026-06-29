<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'hosted_by_us'      => 'boolean',
        'domain_by_us'      => 'boolean',
        'is_archived'       => 'boolean',
        'tags'              => 'array',
        'secret'            => 'encrypted',
        'status'            => SiteStatus::class,
        'ssl_expires_at'    => 'date',
        'domain_expires_at' => 'date',
        'last_seen_at'      => 'datetime',
        'secret_rotated_at' => 'datetime',
        'pending_updates'   => 'integer',
    ];

    /* -------- Beziehungen -------- */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SiteSnapshot::class)->latest('received_at');
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(SiteSnapshot::class)->latestOfMany('received_at');
    }

    public function plugins(): HasMany
    {
        return $this->hasMany(PluginSeen::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(SiteObligation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** Gebuchte/abgewählte Websitepakete (Pivot-Zustand: booked|declined). */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'site_packages')
            ->withPivot(['state', 'note'])
            ->withTimestamps();
    }

    /* -------- Helfer -------- */

    /**
     * Anzeigename. Die DB-Spalte heißt historisch `label`; das Cockpit
     * (Views + Sortierung) nutzt durchgehend `name` als Alias.
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['label'] ?? null;
    }

    /**
     * Reiner Host der Primär-URL (z. B. „kunde.at"), ohne Schema und „www.".
     * Eine eigene `domain`-Spalte gibt es nicht – sie wird aus `url` abgeleitet.
     */
    public function getDomainAttribute(): ?string
    {
        $url = $this->attributes['url'] ?? null;
        if (! $url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: $url;

        return preg_replace('/^www\./i', '', $host);
    }

    public function isExternal(): bool
    {
        return $this->cms_type === 'extern';
    }

    /** Tage bis SSL-Ablauf (negativ = abgelaufen, null = unbekannt). */
    public function sslDaysLeft(): ?int
    {
        return $this->ssl_expires_at?->diffInDays(now(), false) !== null
            ? (int) round(now()->diffInDays($this->ssl_expires_at, false))
            : null;
    }

    /** Tage bis Domain-Ablauf. */
    public function domainDaysLeft(): ?int
    {
        return $this->domain_expires_at
            ? (int) round(now()->diffInDays($this->domain_expires_at, false))
            : null;
    }

    /** Hat die Site offene, handlungsrelevante Aufgaben? */
    public function openTasks(): HasMany
    {
        return $this->tasks()->whereIn('status', ['open', 'in_progress', 'blocked']);
    }
}
