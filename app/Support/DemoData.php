<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Site;
use App\Services\SiteStatusEvaluator;
use Illuminate\Support\Facades\DB;

/**
 * Schaltbare Demo-Daten für die Einstellungen.
 * enable() legt vier Beispiel-Sites an (idempotent), disable() entfernt sie sauber.
 * Demo-Datensätze sind markiert: Sites über Tag "demo", Kunden über notes = "demo-data".
 */
class DemoData
{
    /** Markierung, an der Demo-Daten erkannt und wieder entfernt werden. */
    private const CUSTOMER_MARK = 'demo-data';

    public static function enable(): void
    {
        if (Site::query()->whereJsonContains('tags', 'demo')->exists()) {
            return; // bereits vorhanden
        }

        $evaluator = app(SiteStatusEvaluator::class);

        // [Name, site_id, domain, status, wp, php, updates, domainDays, sslDays]
        $defs = [
            ['Ried Immobilien', 'demo-ried-immobilien', 'ried-immobilien.at', 'online', '6.7.1', '8.3', 2, 156, 30],
            ['Huber Landtechnik', 'demo-landtechnik-huber', 'landtechnik-huber.at', 'online', '6.6.2', '8.1', 7, 210, 12],
            ['Gasthaus Zentral', 'demo-gasthaus-zentral', 'gasthaus-zentral.at', 'maintenance', '6.7.1', '8.2', 1, 67, 4],
            ['Innviertel Shop', 'demo-innviertel-shop', 'innviertel-shop.at', 'online', '6.7.1', '8.3', 9, 33, 200],
        ];

        DB::transaction(function () use ($defs, $evaluator) {
            foreach ($defs as [$name, $sid, $domain, $status, $wp, $php, $updates, $domainDays, $sslDays]) {
                $customer = Customer::query()->create([
                    'name'    => $name,
                    'company' => $name,
                    'email'   => 'kontakt@' . $domain,
                    'notes'   => self::CUSTOMER_MARK,
                ]);

                $site = Site::query()->create([
                    'customer_id'          => $customer->id,
                    'site_id'              => $sid,
                    'label'                => $name,
                    'url'                  => 'https://' . $domain,
                    'cms_type'             => 'wordpress',
                    'package_tier'         => 'Care Pro',
                    'update_interval_days' => 14,
                    'sla_hours'            => 24,
                    'secret'               => bin2hex(random_bytes(32)),
                    'secret_rotated_at'    => now(),
                    'wp_version'           => $wp,
                    'php_version'          => $php,
                    'pending_updates'      => $updates,
                    'last_seen_at'         => in_array($status, ['online', 'maintenance'], true) ? now()->subHours(3) : now()->subDays(2),
                    'status'               => $status,
                    'ssl_expires_at'       => now()->addDays($sslDays),
                    'domain_expires_at'    => now()->addDays($domainDays),
                    'tags'                 => ['demo'],
                ]);

                $site->plugins()->createMany([
                    ['slug' => 'woocommerce', 'name' => 'WooCommerce', 'author' => 'Automattic', 'version' => '9.4.1', 'active' => true, 'update_available' => $updates > 0, 'update_version' => $updates > 0 ? '9.5.0' : null, 'last_seen_at' => now()],
                    ['slug' => 'elementor', 'name' => 'Elementor', 'author' => 'Elementor.com', 'version' => '3.25.0', 'active' => true, 'update_available' => $updates > 3, 'update_version' => $updates > 3 ? '3.26.1' : null, 'last_seen_at' => now()],
                    ['slug' => 'borlabs-cookie', 'name' => 'Borlabs Cookie', 'author' => 'Borlabs', 'version' => '3.2.0', 'active' => true, 'update_available' => false, 'last_seen_at' => now()],
                ]);

                $evaluator->evaluate($site->fresh());
            }
        });
    }

    public static function disable(): void
    {
        DB::transaction(function () {
            $sites = Site::query()->whereJsonContains('tags', 'demo')->get();

            foreach ($sites as $site) {
                $site->tasks()->delete();
                $site->plugins()->delete();
                $site->packages()->detach();
                $site->snapshots()->delete();
                $site->delete();
            }

            Customer::query()->where('notes', self::CUSTOMER_MARK)->delete();
        });
    }
}
