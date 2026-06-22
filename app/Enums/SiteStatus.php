<?php

namespace App\Enums;

enum SiteStatus: string
{
    case Online = 'online';
    case Maintenance = 'maintenance';
    case Offline = 'offline';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Maintenance => 'Wartung',
            self::Offline => 'Offline',
            self::Unknown => 'Unbekannt',
        };
    }

    /** Filament-Farbname */
    public function color(): string
    {
        return match ($this) {
            self::Online => 'success',
            self::Maintenance => 'warning',
            self::Offline => 'danger',
            self::Unknown => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Online => 'heroicon-o-check-circle',
            self::Maintenance => 'heroicon-o-wrench-screwdriver',
            self::Offline => 'heroicon-o-x-circle',
            self::Unknown => 'heroicon-o-question-mark-circle',
        };
    }
}
