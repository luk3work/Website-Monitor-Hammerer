<?php

namespace App\Enums;

enum Severity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Warning => 'Bald',
            self::Critical => 'Sofort',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Info => 'gray',
            self::Warning => 'warning',
            self::Critical => 'danger',
        };
    }

    /** Sortiergewicht: kritisch zuerst */
    public function weight(): int
    {
        return match ($this) {
            self::Critical => 0,
            self::Warning => 1,
            self::Info => 2,
        };
    }
}
