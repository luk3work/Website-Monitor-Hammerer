<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plugin-Katalog der Agentur:
 *  - type=own:      eigene WordPress-Plugins (Basis fürs spätere Update-System,
 *                   siehe Memory plugin-update-system).
 *  - type=external: zugekaufte/fremde Plugins mit Paket-Zuordnung, Notizen und
 *                   (später) automatisch gesammelten News.
 */
class Plugin extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'news'      => 'array',
        'is_active' => 'boolean',
    ];

    /** Optionales Paket (über package_key), dem ein externes Plugin zugeordnet ist. */
    public function package(): ?Package
    {
        return $this->package_key
            ? Package::query()->where('key', $this->package_key)->first()
            : null;
    }
}
