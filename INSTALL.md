# Installation – Ops Cockpit (Laravel + Filament)

Diese Anleitung setzt die zentrale App auf. **Wichtig:** Dieses Verzeichnis enthält
*nur die App-spezifischen Dateien* (Migrations, Models, Services, Filament, Routes,
Tests). Ein lauffähiges Laravel-Projekt entsteht, indem du diese Dateien in ein
frisches Laravel + Filament kopierst. (Composer ließ sich in der Build-Umgebung nicht
ausführen – Boot/Test machst du lokal bzw. auf Mittwald.)

## 1. Frisches Laravel + Filament

```bash
composer create-project laravel/laravel ops-cockpit
cd ops-cockpit
composer require filament/filament:"^3.2"
php artisan filament:install --panels   # erzeugt den Panel-Provider; danach unten ersetzen
```

## 2. Dateien aus diesem Paket übernehmen

Kopiere aus `laravel-app/` in das neue Projekt (gleiche Pfade):

```
app/Enums/*                          -> app/Enums/
app/Models/*                         -> app/Models/        (User.php ersetzt die Vorlage)
app/Services/*                       -> app/Services/
app/Http/Controllers/Api/*           -> app/Http/Controllers/Api/
app/Console/Commands/*               -> app/Console/Commands/
app/Filament/**                      -> app/Filament/
app/Providers/Filament/AdminPanelProvider.php  -> ersetzt die generierte Datei
database/migrations/*                -> database/migrations/
database/seeders/DemoSeeder.php      -> database/seeders/
routes/api.php                       -> routes/api.php     (siehe Hinweis unten)
routes/console.php                   -> routes/console.php
tests/Feature/IngestEndpointTest.php -> tests/Feature/
.env.example                         -> Werte nach .env übernehmen
```

> **routes/api.php in Laravel 11:** API-Routen sind standardmäßig nicht aktiv.
> Einmalig `php artisan install:api` ausführen (legt `routes/api.php` an und
> registriert das Prefix `/api`), danach den Inhalt aus diesem Paket einsetzen.

## 3. Konfiguration

```bash
cp .env.example .env   # Werte eintragen (DB!)
php artisan key:generate          # ERZEUGT APP_KEY – verschlüsselt Secrets at rest
```

## 4. Datenbank

```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\DemoSeeder   # optional: Demo-Daten + Admin-Login
```

Login (Demo): `admin@deineagentur.at` / `passwort-bitte-aendern` → sofort ändern.
Eigenen Admin anlegen: `php artisan make:filament-user`.

## 5. Scheduler (Mittwald-Cron)

Einen Cron-Job anlegen, der jede Minute läuft:

```
* * * * * cd /pfad/zur/app && php artisan schedule:run >> /dev/null 2>&1
```

Damit greifen `sites:heartbeat-sweep` (alle 15 Min) und `sites:expiry-scan` (täglich 06:30).

## 6. Funktionstest

```bash
php artisan test --filter=IngestEndpointTest
```

Der Test sendet einen HMAC-signierten Push exakt wie das Reporter-Plugin und prüft,
dass gültige Berichte gespeichert und gefälschte/veraltete abgewiesen werden.

## 7. Reporter pro Site ausrollen

Siehe `../reporter-plugin/` und die Haupt-`README.md`, Abschnitt „Onboarding einer Site".
