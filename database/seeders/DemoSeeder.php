<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Site;
use App\Services\SiteStatusEvaluator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Befüllt das Cockpit mit realistischen Demo-Daten, damit man Status, Ampeln,
 * Updates und die "Braucht Handlung"-Liste sofort sieht.
 *
 *   php artisan db:seed --class=Database\\Seeders\\DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(SiteStatusEvaluator $evaluator): void
    {
        // Admin-Login fürs Panel.
        \App\Models\User::query()->firstOrCreate(
            ['email' => 'admin@deineagentur.at'],
            ['name' => 'Agentur Admin', 'password' => Hash::make('passwort-bitte-aendern'), 'role' => 'admin']
        );

        $defs = [
            ['Ried Immobilien', 'ried-immobilien', 'ried-immobilien.at', 'online',      '6.7.1', '8.3', 2, 156, 30],
            ['Huber Landtechnik', 'landtechnik-huber', 'landtechnik-huber.at', 'online', '6.6.2', '8.1', 7, 210, 12],
            ['Gasthaus Zentral', 'gasthaus-zentral', 'gasthaus-zentral.at', 'maintenance', '6.7.1', '8.2', 1, 67, 4],
            ['Innviertel Shop', 'innviertel-shop', 'innviertel-shop.at', 'online',        '6.7.1', '8.3', 9, 33, 200],
        ];

        foreach ($defs as [$name, $sid, $domain, $status, $wp, $php, $updates, $domainDays, $sslDays]) {
            $customer = Customer::query()->create([
                'name'    => $name,
                'company' => $name,
                'email'   => 'kontakt@' . $domain,
            ]);

            $site = Site::query()->create([
                'customer_id'       => $customer->id,
                'site_id'           => $sid,
                'label'             => $name,
                'url'               => 'https://' . $domain,
                'cms_type'          => 'wordpress',
                'package_tier'      => 'Care Pro',
                'update_interval_days' => 14,
                'sla_hours'         => 24,
                'secret'            => bin2hex(random_bytes(32)),
                'secret_rotated_at' => now(),
                'wp_version'        => $wp,
                'php_version'       => $php,
                'pending_updates'   => $updates,
                'last_seen_at'      => $status === 'online' || $status === 'maintenance' ? now()->subHours(3) : now()->subDays(2),
                'status'            => $status,
                'ssl_expires_at'    => now()->addDays($sslDays),
                'domain_expires_at' => now()->addDays($domainDays),
                'tags'              => ['demo'],
            ]);

            // Ein paar Plugins, eines mit verfügbarem Update.
            $site->plugins()->createMany([
                ['slug' => 'woocommerce', 'name' => 'WooCommerce', 'version' => '9.4.1', 'active' => true, 'update_available' => $updates > 0, 'update_version' => $updates > 0 ? '9.5.0' : null, 'last_seen_at' => now()],
                ['slug' => 'wpforms-lite', 'name' => 'WPForms Lite', 'version' => '1.9.1', 'active' => true, 'update_available' => false, 'last_seen_at' => now()],
                ['slug' => 'borlabs-cookie', 'name' => 'Borlabs Cookie', 'version' => '3.2.0', 'active' => true, 'update_available' => $updates > 3, 'update_version' => $updates > 3 ? '3.2.4' : null, 'last_seen_at' => now()],
            ]);

            // Status + Auto-Tasks aus dem Zustand ableiten (erzeugt die Braucht-Handlung-Einträge).
            $evaluator->evaluate($site->fresh());
        }

        $this->command?->info('Demo-Daten angelegt. Login: admin@deineagentur.at / passwort-bitte-aendern');
    }
}
