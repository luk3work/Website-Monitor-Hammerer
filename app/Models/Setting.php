<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Einfacher Key-Value-Speicher für App-Einstellungen.
 * Werte werden als {"v": ...} abgelegt, damit Skalare und Arrays gleich behandelt werden.
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'value' => 'array',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row ? ($row->value['v'] ?? $default) : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => ['v' => $value]]);
    }
}
