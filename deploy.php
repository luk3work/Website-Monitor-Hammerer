<?php

namespace Deployer;

/**
 * Deployer-Konfiguration für das Ops Cockpit (Laravel 11 + Filament 3) auf Mittwald.
 *
 * Voraussetzungen (siehe SETUP-Github-Mittwald.md):
 *   composer require --dev deployer/deployer mittwald/deployer-recipes
 *
 * Platzhalter vor dem ersten Deploy ersetzen:
 *   <GITHUB-ORG>   -> dein GitHub-Account/Org
 *   <MITTWALD-APP-ID> -> ID der in mStudio angelegten App
 */

require 'recipe/laravel.php';
require __DIR__ . '/vendor/autoload.php';
// Kompatibilitäts-Wrapper VOR der Mittwald-Recipe laden (Deployer-7-Helfer für Deployer 8).
require __DIR__ . '/deploy_polyfill.php';
require __DIR__ . '/vendor/mittwald/deployer-recipes/recipes/deploy.php';

set('application', 'ops-cockpit');
set('repository', 'https://github.com/luk3work/Website-Monitor-Hammerer.git');

// Code aus dem CI-Checkout hochladen (local_archive) -> der Server muss NICHT
// selbst aus dem privaten GitHub klonen (keine Server->GitHub-Auth nötig).
set('update_code_strategy', 'local_archive');

// Zwischen Releases geteilt: .env und Storage bleiben über Deploys hinweg bestehen.
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);
add('writable_dirs', ['bootstrap/cache', 'storage']);

// Wie viele alte Releases für schnelle Rollbacks aufbewahren.
set('keep_releases', 5);

// Laufzeit-Abhängigkeiten auf der Plattform (Mittwald installiert sie automatisch).
set('mittwald_app_dependencies', [
    'php'      => '~8.4',
    'composer' => '>=2.0',
]);

// Production-Host (App-ID wird zusätzlich per CLI-Secret MITTWALD_APP_ID überschrieben).
// document_root in mStudio = /current/public (Laravel webroot).
mittwald_app('a-f6ti4y', hostname: 'mittwald-prod')
    ->set('public_path', '/public')
    ->set('branch', 'main');

// Die Recipe würde sonst versuchen, einen Virtual Host namens "mittwald-prod"
// anzulegen (kein echter Domainname -> 400 Bad Request). Die Domain-Verknüpfung
// nehmen wir bewusst manuell in mStudio vor.
task('mittwald:domain')->disable();

/*
 | -------------------------------------------------------------------------
 | Laravel-spezifische Schritte
 | -------------------------------------------------------------------------
 | Die Laravel-Recipe cached Config/Routes/Views automatisch. Migrationen
 | laufen bewusst NACH dem Symlink. --force ist nötig (non-interaktiv);
 | destruktive Migrationen daher nur bewusst und nach Hinweis (siehe Anweisung).
 */
// Alle Laravel-Caches leeren BEVOR der neue Code aktiv wird.
// Verhindert stale kompilierte Views, Config-Cache und OPcache nach Updates.
task('artisan:cache:clear:all', function () {
    run('cd {{release_path}} && {{bin/php}} artisan view:clear');
    run('cd {{release_path}} && {{bin/php}} artisan config:clear');
    run('cd {{release_path}} && {{bin/php}} artisan route:clear');
    run('cd {{release_path}} && {{bin/php}} artisan cache:clear');
})->desc('Alle Laravel-Caches leeren');
before('deploy:symlink', 'artisan:cache:clear:all');

after('deploy:symlink', 'artisan:migrate');

// Initialen Admin-Benutzer nach der Migration sicherstellen (idempotent).
task('artisan:seed:admin', function () {
    run('cd {{release_path}} && {{bin/php}} artisan db:seed --class=AdminUserSeeder --force');
})->desc('Admin-Benutzer anlegen/aktualisieren');
after('artisan:migrate', 'artisan:seed:admin');

// Paketkatalog idempotent pflegen (hält die packages-Tabelle aktuell).
task('artisan:seed:packages', function () {
    run('cd {{release_path}} && {{bin/php}} artisan db:seed --class=PackageSeeder --force');
})->desc('Paketkatalog aktualisieren');
after('artisan:seed:admin', 'artisan:seed:packages');

// Optional: Demo-Daten einspielen. Läuft nur, wenn der Deploy mit
// -o run_demo_seed=true gestartet wurde (manueller Workflow-Trigger).
task('artisan:seed:demo', function () {
    if (get('run_demo_seed', 'false') !== 'true') {
        return;
    }
    run('cd {{release_path}} && {{bin/php}} artisan db:seed --class=DemoSeeder --force');
})->desc('Demo-Daten einspielen (nur auf Anforderung)');
after('artisan:seed:admin', 'artisan:seed:demo');

// Bei Fehler: Lock lösen, damit der nächste Lauf nicht blockiert.
after('deploy:failed', 'deploy:unlock');

/*
 | Server-.env aus dem CI bereitstellen.
 | Die GitHub Action schreibt .env.deploy aus Secrets (APP_KEY, DB_PASSWORD).
 | Diese Datei wird VOR dem Teilen nach shared/.env hochgeladen, damit
 | composer/artisan und vor allem die Migrationen die DB-Zugangsdaten finden.
 */
task('deploy:dotenv', function () {
    if (file_exists(__DIR__ . '/.env.deploy')) {
        upload(__DIR__ . '/.env.deploy', '{{deploy_path}}/shared/.env');
    }
})->desc('Server-.env hochladen');
before('deploy:shared', 'deploy:dotenv');

/*
 | Optional für ein Staging-Environment eine zweite App anlegen und einkommentieren:
 |
 | mittwald_app('<MITTWALD-STAGING-APP-ID>', hostname: 'mittwald-staging')
 |     ->set('public_path', '/public')
 |     ->set('branch', 'develop');
 */
