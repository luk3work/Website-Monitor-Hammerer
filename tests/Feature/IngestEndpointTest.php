<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sichert den Eingangs-Vertrag ab: nur korrekt HMAC-signierte, frische Pushes
 * einer bekannten Site werden akzeptiert. Spiegelt exakt das, was das
 * WordPress-Reporter-Plugin sendet.
 */
class IngestEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function makeSite(string $secret): Site
    {
        $customer = Customer::create(['name' => 'Testkunde']);

        return Site::create([
            'customer_id' => $customer->id,
            'site_id'     => 'test-site',
            'label'       => 'Test Site',
            'url'         => 'https://test.example',
            'cms_type'    => 'wordpress',
            'secret'      => $secret,
        ]);
    }

    /** Baut Body + Signatur genau wie der Reporter. */
    private function signedRequest(string $siteId, string $secret, array $report): array
    {
        $payload = [
            'site_id' => $siteId,
            'sent_at' => time(),
            'nonce'   => 'abc123',
            'report'  => $report,
        ];
        $body      = json_encode($payload);
        $timestamp = (string) $payload['sent_at'];
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        return [$body, $timestamp, $signature];
    }

    private function sampleReport(): array
    {
        return [
            'reporter_version' => '1.0.0',
            'collected_at'     => now()->toIso8601String(),
            'site'             => ['wp_version' => '6.7.1', 'wp_update' => null, 'php_version' => '8.3', 'https' => true],
            'plugins'          => [
                ['slug' => 'woocommerce', 'name' => 'WooCommerce', 'version' => '9.4.1', 'active' => true, 'update_available' => true, 'update_version' => '9.5.0'],
            ],
            'themes'           => [],
            'fingerprint'      => ['has_woocommerce' => true, 'has_forms' => false],
        ];
    }

    public function test_valid_push_is_accepted_and_creates_snapshot(): void
    {
        $secret = bin2hex(random_bytes(32));
        $site   = $this->makeSite($secret);
        [$body, $ts, $sig] = $this->signedRequest('test-site', $secret, $this->sampleReport());

        $res = $this->call('POST', '/api/ingest', [], [], [], [
            'HTTP_X-Ops-Site'      => 'test-site',
            'HTTP_X-Ops-Timestamp' => $ts,
            'HTTP_X-Ops-Signature' => $sig,
            'CONTENT_TYPE'         => 'application/json',
        ], $body);

        $res->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseCount('site_snapshots', 1);
        $this->assertDatabaseHas('plugins_seen', ['site_id' => $site->id, 'slug' => 'woocommerce', 'update_available' => true]);
        $this->assertNotNull($site->fresh()->last_seen_at);
    }

    public function test_wrong_signature_is_rejected(): void
    {
        $secret = bin2hex(random_bytes(32));
        $this->makeSite($secret);
        [$body, $ts] = $this->signedRequest('test-site', $secret, $this->sampleReport());

        $res = $this->call('POST', '/api/ingest', [], [], [], [
            'HTTP_X-Ops-Site'      => 'test-site',
            'HTTP_X-Ops-Timestamp' => $ts,
            'HTTP_X-Ops-Signature' => 'deadbeef',
            'CONTENT_TYPE'         => 'application/json',
        ], $body);

        $res->assertUnauthorized();
        $this->assertDatabaseCount('site_snapshots', 0);
    }

    public function test_unknown_site_is_rejected(): void
    {
        $secret = bin2hex(random_bytes(32));
        [$body, $ts, $sig] = $this->signedRequest('does-not-exist', $secret, $this->sampleReport());

        $res = $this->call('POST', '/api/ingest', [], [], [], [
            'HTTP_X-Ops-Site'      => 'does-not-exist',
            'HTTP_X-Ops-Timestamp' => $ts,
            'HTTP_X-Ops-Signature' => $sig,
            'CONTENT_TYPE'         => 'application/json',
        ], $body);

        $res->assertUnauthorized();
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $secret = bin2hex(random_bytes(32));
        $this->makeSite($secret);

        $payload = ['site_id' => 'test-site', 'sent_at' => time() - 3600, 'report' => $this->sampleReport()];
        $body    = json_encode($payload);
        $ts      = (string) $payload['sent_at'];
        $sig     = hash_hmac('sha256', $ts . '.' . $body, $secret);

        $res = $this->call('POST', '/api/ingest', [], [], [], [
            'HTTP_X-Ops-Site'      => 'test-site',
            'HTTP_X-Ops-Timestamp' => $ts,
            'HTTP_X-Ops-Signature' => $sig,
            'CONTENT_TYPE'         => 'application/json',
        ], $body);

        $res->assertUnauthorized();
    }
}
