<?php

use App\Console\Commands\SitesExpiryScan;
use App\Console\Commands\SitesHeartbeatSweep;
use App\Console\Commands\SitesProbeExpiry;
use Illuminate\Support\Facades\Schedule;

/*
 | Scheduler (läuft über den Mittwald-Cron, der jede Minute `php artisan schedule:run` aufruft).
 */

// Dead-Man's-Switch: alle 15 Min prüfen, ob Sites zu lange stumm sind.
Schedule::command(SitesHeartbeatSweep::class)->everyFifteenMinutes();

// Ablauf-Scan (SSL/Domain/Lizenz) einmal täglich früh.
Schedule::command(SitesExpiryScan::class)->dailyAt('06:30');

// Automatische SSL-/Domain-Ablauf-Abfrage (TLS + RDAP) einmal täglich.
Schedule::command(SitesProbeExpiry::class)->dailyAt('05:45');
