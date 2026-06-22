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
require __DIR__ . '/vendor/mittwald/deployer-recipes/recipes/deploy.php';

set('application', 'ops-cockpit');
set('repository', 'git@github.com:<GITHUB-ORG>/ops-cockpit.git');

// Zwischen Releases geteilt: .env und Storage bleiben über Deploys hinweg bestehen.
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);
add('writable_dirs', ['bootstrap/cache', 'storage']);

// Wie viele alte Releases für schnelle Rollbacks aufbewahren.
set('keep_releases', 5);

// Laufzeit-Abhängigkeiten auf der Plattform (Mittwald installiert sie automatisch).
set('mittwald_app_dependencies', [
    'php'      => '~8.3',
    'composer' => '>=2.0',
]);

// Production-Host. document_root in mStudio = /current/public (Laravel webroot).
mittwald_app('<MITTWALD-APP-ID>', hostname: 'mittwald-prod')
    ->set('public_path', '/public')
    ->set('branch', 'main');

/*
 | -------------------------------------------------------------------------
 | Laravel-spezifische Schritte
 | -------------------------------------------------------------------------
 | Die Laravel-Recipe cached Config/Routes/Views automatisch. Migrationen
 | laufen bewusst NACH dem Symlink. --force ist nötig (non-interaktiv);
 | destruktive Migrationen daher nur bewusst und nach Hinweis (siehe Anweisung).
 */
after('deploy:symlink', 'artisan:migrate');

// Bei Fehler: Lock lösen, damit der nächste Lauf nicht blockiert.
after('deploy:failed', 'deploy:unlock');

/*
 | Optional für ein Staging-Environment eine zweite App anlegen und einkommentieren:
 |
 | mittwald_app('<MITTWALD-STAGING-APP-ID>', hostname: 'mittwald-staging')
 |     ->set('public_path', '/public')
 |     ->set('branch', 'develop');
 */
