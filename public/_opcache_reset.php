<?php

/**
 * Einmaliger OPcache-Reset nach einem Deploy.
 *
 * Hintergrund: Bei atomaren Symlink-Deploys (Deployer) cached PHP-FPM/OPcache
 * den Bytecode der vorherigen Release weiter (opcache.validate_timestamps=0,
 * .user.ini wird auf Mittwald ignoriert). Dadurch greifen neue PHP-Fixes erst
 * nach einem FPM-Neustart. Dieses Skript läuft IM FPM-Worker und leert dort den
 * geteilten OPcache – die GitHub-Action ruft es direkt nach dem Deploy auf.
 *
 * Schutz: Token = sha256(APP_KEY) (erste 32 Zeichen). APP_KEY liegt nur in der
 * server-seitigen .env bzw. als CI-Secret vor, nie im Repo.
 */

$envPath = __DIR__ . '/../.env';
$key = '';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with($line, 'APP_KEY=')) {
            $key = trim(substr($line, 8), " \t\"'");
            break;
        }
    }
}

$expected = $key !== '' ? substr(hash('sha256', $key), 0, 32) : '';
$given = (string) ($_GET['token'] ?? '');

if ($expected === '' || ! hash_equals($expected, $given)) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain');
echo (function_exists('opcache_reset') && opcache_reset()) ? 'reset-ok' : 'reset-noop';
