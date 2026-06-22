<?php

namespace Deployer\Support;

/*
 | Kompatibilitäts-Wrapper für mittwald/deployer-recipes (v1.0.1).
 |
 | Die Recipe wurde für Deployer 7 geschrieben und ruft Hilfsfunktionen im
 | Namespace Deployer\Support auf, die in Deployer 8 entfernt wurden
 | (zugunsten der nativen PHP-8-Funktionen). Wir definieren sie hier als dünne
 | Wrapper, damit die Recipe unter Deployer 8 lauffähig bleibt.
 |
 | Muss VOR der Mittwald-Recipe geladen werden (siehe deploy.php).
 */

if (! function_exists('Deployer\\Support\\starts_with')) {
    function starts_with(string $haystack, string $needle): bool
    {
        return \str_starts_with($haystack, $needle);
    }
}

if (! function_exists('Deployer\\Support\\ends_with')) {
    function ends_with(string $haystack, string $needle): bool
    {
        return \str_ends_with($haystack, $needle);
    }
}

if (! function_exists('Deployer\\Support\\str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return \str_contains($haystack, $needle);
    }
}
