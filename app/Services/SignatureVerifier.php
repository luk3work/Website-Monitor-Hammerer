<?php

namespace App\Services;

use App\Models\Site;

/**
 * Prüft die Authentizität eines Reporter-Push, BEVOR der Body geparst wird.
 *
 * Gegenstück zum WordPress-Reporter:
 *   signature = HMAC_SHA256( timestamp . "." . rawBody , per_site_secret )
 *
 * Reihenfolge bewusst: erst Signatur, dann JSON-Parsing – ein gefälschter
 * Body wird so nie an den Parser durchgereicht.
 */
class SignatureVerifier
{
    /** Akzeptiertes Zeitfenster gegen Replay (Sekunden). */
    private int $toleranceSeconds = 600; // ±10 Minuten

    /**
     * @return array{ok:bool,reason?:string,site?:Site}
     */
    public function verify(string $siteId, string $timestamp, string $signature, string $rawBody): array
    {
        if ($siteId === '' || $timestamp === '' || $signature === '') {
            return ['ok' => false, 'reason' => 'missing_headers'];
        }

        // Replay-Schutz: Zeitstempel muss frisch sein.
        if (! ctype_digit($timestamp)) {
            return ['ok' => false, 'reason' => 'bad_timestamp'];
        }
        $delta = abs(time() - (int) $timestamp);
        if ($delta > $this->toleranceSeconds) {
            return ['ok' => false, 'reason' => 'stale_timestamp'];
        }

        // Unbekannte / archivierte Site ablehnen.
        $site = Site::query()
            ->where('site_id', $siteId)
            ->where('is_archived', false)
            ->first();

        if (! $site || ! $site->secret) {
            return ['ok' => false, 'reason' => 'unknown_site'];
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $site->secret);

        // Timing-safe vergleichen.
        if (! hash_equals($expected, $signature)) {
            return ['ok' => false, 'reason' => 'bad_signature'];
        }

        return ['ok' => true, 'site' => $site];
    }
}
