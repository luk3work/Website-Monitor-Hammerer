<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::InProgress => 'In Arbeit',
            self::Blocked => 'Blockiert',
            self::Done => 'Erledigt',
            self::Dismissed => 'Verworfen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::InProgress => 'info',
            self::Blocked => 'danger',
            self::Done => 'success',
            self::Dismissed => 'gray',
        };
    }

    /** Gilt die Aufgabe als noch offen/handlungsrelevant? */
    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress, self::Blocked], true);
    }
}
