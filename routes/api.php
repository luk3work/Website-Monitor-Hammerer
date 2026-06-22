<?php

use App\Http\Controllers\Api\IngestController;
use Illuminate\Support\Facades\Route;

/*
 | Ingest-Endpoint für die Reporter-Plugins.
 | Bewusst ohne auth-Middleware: die Authentifizierung erfolgt über die
 | HMAC-Signaturprüfung im Controller (Shared Secret pro Site), nicht über Sessions/Token.
 |
 | URL (mit Standard-Prefix /api):  POST https://dashboard.deineagentur.at/api/ingest
 */
Route::post('/ingest', IngestController::class)->name('ingest');
