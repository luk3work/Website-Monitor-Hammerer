<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SignatureVerifier;
use App\Services\SnapshotIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Einziger Eingangspunkt des Cockpits für Reporter-Daten.
 *
 * Härtung (siehe Sicherheitsanforderungen, Abschnitt 6):
 *  - Signaturprüfung VOR dem JSON-Parsing
 *  - Replay-Schutz über Zeitstempel-Fenster (im SignatureVerifier)
 *  - unbekannte/archivierte site_id wird abgelehnt
 *  - Rate-Limiting pro site_id + IP
 *  - kein Inbound-Kommandokanal: der Controller akzeptiert ausschließlich Berichte
 */
class IngestController extends Controller
{
    public function __construct(
        private SignatureVerifier $verifier,
        private SnapshotIngestor $ingestor,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $siteId    = (string) $request->header('X-Ops-Site', '');
        $timestamp = (string) $request->header('X-Ops-Timestamp', '');
        $signature = (string) $request->header('X-Ops-Signature', '');
        $rawBody   = $request->getContent();

        // Rate-Limit: max. 12 Berichte/Stunde je Site+IP (2x/Tag erwartet, großzügiger Puffer).
        $throttleKey = 'ingest:' . sha1($siteId . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 12)) {
            return response()->json(['error' => 'rate_limited'], 429);
        }
        RateLimiter::hit($throttleKey, 3600);

        // 1) Signatur prüfen – bevor irgendetwas geparst wird.
        $check = $this->verifier->verify($siteId, $timestamp, $signature, $rawBody);
        if (! $check['ok']) {
            // Bewusst generische Antwort nach außen, Details nur ins Log.
            Log::warning('Ingest abgelehnt', ['site' => $siteId, 'reason' => $check['reason'] ?? 'unknown', 'ip' => $request->ip()]);
            return response()->json(['error' => 'unauthorized'], 401);
        }

        // 2) Erst jetzt parsen.
        $payload = json_decode($rawBody, true);
        if (! is_array($payload) || ! isset($payload['report'])) {
            return response()->json(['error' => 'bad_payload'], 422);
        }

        // 3) Verarbeiten.
        $snapshot = $this->ingestor->ingest($check['site'], $payload);

        // 204 würde reichen; 200 mit Mini-Bestätigung erleichtert das Debugging am Reporter.
        return response()->json([
            'ok'          => true,
            'snapshot_id' => $snapshot->id,
            'received_at' => $snapshot->received_at->toIso8601String(),
        ], 200);
    }
}
