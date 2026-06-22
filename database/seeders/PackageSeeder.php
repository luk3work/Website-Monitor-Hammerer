<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Pflegt den Paketkatalog idempotent (updateOrCreate per key).
 * Läuft bei jedem Deploy und hält den Katalog aktuell.
 * Quelle: PAKETE-KATALOG.md
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            // ---- Hosting ----
            ['key' => 'hosting.highperf', 'name' => 'High-Performance-Hosting', 'category' => 'hosting', 'group' => 'hosting_tier',
                'price_yearly' => 350, 'excludes' => ['hosting.light'], 'sort' => 10,
                'description' => 'SSD Cloudserver, HTTP/2, Advanced WP Caching & CDN, bis 5 GB SSD, tägliche Backups, EU-Standort, 99 % Verfügbarkeit.'],
            ['key' => 'hosting.light', 'name' => 'High-Performance-Hosting „Light"', 'category' => 'hosting', 'group' => 'hosting_tier',
                'price_yearly' => 260, 'excludes' => ['hosting.highperf'], 'sort' => 11,
                'description' => 'Wie High-Performance-Hosting, jedoch bis 1 GB SSD Webspace.'],
            ['key' => 'hosting.domain', 'name' => 'Domainhosting', 'category' => 'hosting', 'group' => 'addon',
                'price_monthly' => 3.50, 'sort' => 20, 'description' => '1 Domain nach Wahl.'],
            ['key' => 'hosting.webspace_addon', 'name' => 'Zusatzpaket Webspace (+1 GB)', 'category' => 'hosting', 'group' => 'addon',
                'price_monthly' => 2.50, 'requires_any' => ['hosting.highperf', 'hosting.light'], 'sort' => 21,
                'description' => 'Zusätzlich 1 GB Webspace. Setzt ein Hosting-Paket voraus.'],
            ['key' => 'mail.sendonly', 'name' => '„Send Only" Mailadresse', 'category' => 'hosting', 'group' => 'addon',
                'price_monthly' => 4, 'sort' => 22, 'description' => 'Verlässlicher Mailversand von Formulareinträgen/Webshopbestellungen (@mailforge.at).'],

            // ---- Update-Service ----
            ['key' => 'update.website', 'name' => 'Update-Service Website', 'category' => 'service', 'group' => 'update',
                'price_monthly' => 39, 'excludes' => ['update.shop'], 'sort' => 30,
                'description' => 'WP Core- & Plugin-Updates inkl. Sichtprüfungen, Sicherheitslücken-Überwachung, Test in Staging. Fehlerbehebung separat.'],
            ['key' => 'update.shop', 'name' => 'Update-Service Shop', 'category' => 'service', 'group' => 'update',
                'price_monthly' => 73, 'excludes' => ['update.website'], 'sort' => 31,
                'description' => 'Wie Update-Service Website, zusätzlich WooCommerce-/Shop-spezifische Plugins.'],

            // ---- Service ----
            ['key' => 'seo.basic', 'name' => 'SEO Basic', 'category' => 'service', 'group' => 'seo',
                'price_once' => 490, 'sort' => 40, 'description' => 'Google Search Console, Favicon, XML-Sitemap, robots.txt, Geschwindigkeitsanalyse, 301-Weiterleitungen.'],
            ['key' => 'service.performance', 'name' => 'Performance', 'category' => 'service', 'group' => 'performance',
                'price_monthly' => 25, 'sort' => 41, 'description' => 'Ladegeschwindigkeit, automatische Bildoptimierung, gzip/deflate, Browser-Caching, Minify CSS & JS.'],
            ['key' => 'service.performance_individual', 'name' => 'Performance „Individual"', 'category' => 'service', 'group' => 'performance',
                'price_once' => 500, 'requires' => ['seo.basic', 'service.performance'], 'sort' => 42,
                'description' => 'PageSpeed-Analyse (mobil + Desktop) und Umsetzung. 500–1.000 € je nach Größe. Ergänzt SEO Basic + Performance.'],
            ['key' => 'service.reporting', 'name' => 'Reporting', 'category' => 'service', 'group' => 'reporting',
                'price_monthly' => 30, 'sort' => 43, 'description' => 'Quartalsweise Auswertung (Geschwindigkeit, Nutzerzahl, Zugriffe u. a.).'],
            ['key' => 'datenschutz.complete', 'name' => 'Datenschutz Complete', 'category' => 'service', 'group' => 'datenschutz',
                'price_once' => 180, 'price_monthly' => 36, 'excludes' => ['datenschutz.cookiebox'], 'sort' => 50,
                'description' => 'Cookiebox + DSGVO-Erklärung + Impressum, inkl. regelmäßiger Überprüfung/Aktualisierung.'],
            ['key' => 'datenschutz.cookiebox', 'name' => 'Cookiebox Only', 'category' => 'service', 'group' => 'datenschutz',
                'price_once' => 120, 'price_monthly' => 20, 'excludes' => ['datenschutz.complete'], 'sort' => 51,
                'description' => 'Cookiebox-Ersteinrichtung. DSGVO-Erklärung vom Kunden. Regelmäßige Überprüfung.'],
            ['key' => 'security.basic', 'name' => 'Sicherheit Basic', 'category' => 'service', 'group' => 'security',
                'price_monthly' => 30, 'excludes' => ['security.advanced'], 'sort' => 60,
                'description' => 'Regelmäßige Viren-/Malware-Scans, Monitoring von Sicherheitslücken, Nachjustierung.'],
            ['key' => 'security.advanced', 'name' => 'Sicherheit Advanced', 'category' => 'service', 'group' => 'security',
                'price_monthly' => 40, 'excludes' => ['security.basic'], 'sort' => 61,
                'description' => 'Wie Basic, zusätzlich Uptime-Monitoring, WAF, Login-Maskierung, Bot Protection.'],
            ['key' => 'a11y', 'name' => 'Barrierefreiheit', 'category' => 'service', 'group' => 'a11y',
                'price_once' => 300, 'price_yearly' => 120, 'sort' => 70,
                'description' => 'Barrierefreiheits-Icon (alle Endgeräte), regelmäßige Funktionsprüfung. Jährliche Lizenzkosten.'],
        ];

        foreach ($packages as $p) {
            Package::updateOrCreate(
                ['key' => $p['key']],
                array_merge([
                    'requires'     => null,
                    'requires_any' => null,
                    'excludes'     => null,
                    'is_active'    => true,
                ], $p)
            );
        }
    }
}
