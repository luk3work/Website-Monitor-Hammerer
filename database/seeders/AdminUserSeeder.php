<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Legt den initialen Admin-Benutzer an bzw. aktualisiert ihn (idempotent).
 * Zugangsdaten kommen aus der Config (config/admin.php -> .env / Secrets).
 * Setzt bewusst role = 'admin', damit canAccessPanel() greift.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = config('admin.email');
        $password = config('admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL/ADMIN_PASSWORD nicht gesetzt – kein Admin angelegt.');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => config('admin.name', 'Administrator'),
                // Klartext übergeben: das User-Modell hasht via 'hashed'-Cast genau einmal.
                'password'          => $password,
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info('Admin-Benutzer sichergestellt: ' . $email);
    }
}
