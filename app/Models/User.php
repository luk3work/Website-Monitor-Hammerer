<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Cockpit-Nutzer (Agentur-Team). Schlankes Rollenmodell: admin|operator|viewer.
 * Implementiert FilamentUser, damit nur freigeschaltete Nutzer ins Panel kommen.
 */
class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Im MVP: jeder aktive Nutzer mit gültiger Rolle. 2FA-Pflicht im Setup ergänzbar.
        return in_array($this->role, ['admin', 'operator', 'viewer'], true);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
