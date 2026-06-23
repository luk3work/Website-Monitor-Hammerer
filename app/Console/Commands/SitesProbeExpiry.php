<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\SiteStatusEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Fragt SSL- und Domain-Ablauf automatisch ab und schreibt sie auf die Site.
 *  - SSL: TLS-Handshake + Zertifikat auslesen (kein Drittdienst).
 *  - Domain: RDAP (moderner WHOIS-Nachfolger, öffentlich, JSON).
 * Anschließend Neubewertung -> erzeugt bei Bedarf Ablauf-Aufgaben.
 */
class SitesProbeExpiry extends Command
{
    protected $signature = 'sites:probe-expiry {--site= : nur eine Site-ID prüfen}';

    protected $description = 'Liest SSL- und Domain-Ablauf je Site automatisch aus (TLS + RDAP).';

    public function handle(SiteStatusEvaluator $evaluator): int
    {
        $query = Site::query()->where('is_archived', false);
        if ($this->option('site')) {
            $query->where('site_id', $this->option('site'));
        }

        $checked = 0;

        foreach ($query->get() as $site) {
            $host = parse_url((string) $site->url, PHP_URL_HOST);
            if (! $host) {
                continue;
            }

            if ($ssl = $this->probeSsl($host)) {
                $site->ssl_expires_at = $ssl;
            }
            if ($domain = $this->probeDomain($host)) {
                $site->domain_expires_at = $domain;
            }

            if ($site->isDirty(['ssl_expires_at', 'domain_expires_at'])) {
                $site->save();
            }

            $evaluator->evaluate($site->fresh());
            $checked++;
        }

        $this->info("Geprüft: {$checked} Site(s).");

        return self::SUCCESS;
    }

    /** SSL-Zertifikat per TLS-Handshake lesen und Ablaufdatum zurückgeben. */
    private function probeSsl(string $host): ?Carbon
    {
        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'SNI_enabled'       => true,
                    'peer_name'         => $host,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$host}:443",
                $errno,
                $errstr,
                7,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $client) {
                return null;
            }

            $params = stream_context_get_params($client);
            $cert   = $params['options']['ssl']['peer_certificate'] ?? null;
            fclose($client);

            if (! $cert) {
                return null;
            }

            $parsed = openssl_x509_parse($cert);
            $ts     = $parsed['validTo_time_t'] ?? null;

            return $ts ? Carbon::createFromTimestamp($ts) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Domain-Ablauf via RDAP (rdap.org leitet an die zuständige Registry weiter). */
    private function probeDomain(string $host): ?Carbon
    {
        // Registrierbare Domain heuristisch: die letzten beiden Labels.
        // (Für mehrteilige Endungen wie .co.uk ungenau – v1, später per Public-Suffix-Liste.)
        $parts  = explode('.', $host);
        $domain = count($parts) >= 2 ? implode('.', array_slice($parts, -2)) : $host;

        try {
            $response = Http::timeout(8)->acceptJson()->get("https://rdap.org/domain/{$domain}");
            if (! $response->ok()) {
                return null;
            }

            foreach ((array) $response->json('events') as $event) {
                if (($event['eventAction'] ?? '') === 'expiration' && ! empty($event['eventDate'])) {
                    return Carbon::parse($event['eventDate']);
                }
            }
        } catch (\Throwable $e) {
            // RDAP nicht verfügbar -> still überspringen.
        }

        return null;
    }
}
