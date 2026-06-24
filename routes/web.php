<?php

use App\Livewire\Cockpit\Benutzer;
use App\Livewire\Cockpit\Berichte;
use App\Livewire\Cockpit\Dashboard;
use App\Livewire\Cockpit\Domains;
use App\Livewire\Cockpit\Einstellungen;
use App\Livewire\Cockpit\Kunden;
use App\Livewire\Cockpit\Seiten;
use Illuminate\Support\Facades\Route;

// Startseite → Cockpit weiterleiten
Route::get('/', fn () => redirect()->route('cockpit.dashboard'));

// Cockpit – geschützt hinter Auth
Route::middleware('auth')->prefix('cockpit')->name('cockpit.')->group(function () {
    Route::get('/',              Dashboard::class)->name('dashboard');
    Route::get('/kunden',        Kunden::class)->name('kunden');
    Route::get('/seiten',        Seiten::class)->name('seiten');
    Route::get('/domains',       Domains::class)->name('domains');
    Route::get('/berichte',      Berichte::class)->name('berichte');
    Route::get('/benutzer',      Benutzer::class)->name('benutzer');
    Route::get('/einstellungen', Einstellungen::class)->name('einstellungen');
});
