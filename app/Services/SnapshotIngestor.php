<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SiteSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Verarbeitet einen verifizierten Reporter-Bericht:
 *  1) legt einen append-only Snapshot an,
 *  2) aktualisiert den aktuellen Plugin-Stand (plugins_seen, upsert),
 *  3) schreibt denormalisierte Felder auf die Site (für schnelle Tabellen),
 *  4) lässt den Status neu bewerten.
 */
class SnapshotIngestor
{
    public function __construct(
        private SiteStatusEvaluator $statusEvaluator,
    ) {}

    /**
     * @param  array<string,mixed>  $payload  dekodierter, verifizierter Push
     */
    public function ingest(Site $site, array $payload): SiteSnapshot
    {
        $report  = $payload['report'] ?? [];
        $core    = $report['site'] ?? [];
        $plugins = $report['plugins'] ?? [];
        $themes  = $report['themes'] ?? [];

        $pluginsActive = collect($plugins)->where('active', true)->count();
        $pluginsUpdate = collect($plugins)->where('update_available', true)->count();
        $themesUpdate  = collect($themes)->where('update_available', true)->count();

        $collectedAt = isset($report['collected_at'])
            ? Carbon::parse($report['collected_at'])
            : null;

        return DB::transaction(function () use (
            $site, $report, $core, $plugins, $pluginsActive, $pluginsUpdate, $themesUpdate, $collectedAt
        ) {
            $snapshot = $site->snapshots()->create([
                'wp_version'       => $core['wp_version']   ?? null,
                'wp_update'        => $core['wp_update']     ?? null,
                'php_version'      => $core['php_version']   ?? null,
                'mysql_version'    => $core['mysql_version'] ?? null,
                'https'            => (bool) ($core['https'] ?? true),
                'is_multisite'     => (bool) ($core['is_multisite'] ?? false),
                'plugins_total'    => count($plugins),
                'plugins_active'   => $pluginsActive,
                'plugins_update'   => $pluginsUpdate,
                'themes_update'    => $themesUpdate,
                'fingerprint'      => $report['fingerprint'] ?? null,
                'raw'              => $report,
                'reporter_version' => $report['reporter_version'] ?? null,
                'collected_at'     => $collectedAt,
                'received_at'      => now(),
            ]);

            $this->upsertPlugins($site, $plugins);

            // Denormalisierte Felder auf der Site fortschreiben.
            $site->forceFill([
                'wp_version'      => $core['wp_version']  ?? $site->wp_version,
                'php_version'     => $core['php_version'] ?? $site->php_version,
                'pending_updates' => $pluginsUpdate + $themesUpdate + (! empty($core['wp_update']) ? 1 : 0),
                'last_seen_at'    => now(),
                'label'           => $site->label ?: ($core['name'] ?? $site->label),
            ])->save();

            $this->statusEvaluator->evaluate($site->fresh());

            return $snapshot;
        });
    }

    /**
     * Aktuellen Plugin-Stand je Site abgleichen (vorhandene 'hold'-Flags bleiben erhalten).
     *
     * @param  array<int,array<string,mixed>>  $plugins
     */
    private function upsertPlugins(Site $site, array $plugins): void
    {
        $seenSlugs = [];

        foreach ($plugins as $p) {
            $slug = $p['slug'] ?? null;
            if (! $slug) {
                continue;
            }
            $seenSlugs[] = $slug;

            $site->plugins()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name'             => $p['name'] ?? null,
                    'author'           => $p['author'] ?? null,
                    'plugin_uri'       => $p['plugin_uri'] ?? null,
                    'version'          => $p['version'] ?? null,
                    'active'           => (bool) ($p['active'] ?? false),
                    'update_available' => (bool) ($p['update_available'] ?? false),
                    'update_version'   => $p['update_version'] ?? null,
                    'last_seen_at'     => now(),
                ]
            );
        }

        // Plugins, die nicht mehr gemeldet wurden (deinstalliert), entfernen –
        // 'hold'-Einträge bleiben als Audit-Spur stehen.
        if (! empty($seenSlugs)) {
            $site->plugins()
                ->whereNotIn('slug', $seenSlugs)
                ->where('hold', false)
                ->delete();
        }
    }
}
