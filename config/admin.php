<?php

/*
 | Zugangsdaten für den initialen Admin-Benutzer.
 |
 | Werden über die .env gesetzt (im Deploy aus GitHub-Secrets erzeugt) und beim
 | Config-Cache gebacken, damit der AdminUserSeeder sie auch nach `artisan optimize`
 | zuverlässig lesen kann (env() liefert bei gecachter Config sonst null).
 */

return [
    'name'     => env('ADMIN_NAME', 'Administrator'),
    'email'    => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];
