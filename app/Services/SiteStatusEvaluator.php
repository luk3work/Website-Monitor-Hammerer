<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Models\Setting;
use App\Models\Site;

/**
 * Leitet den Ampel-Status einer Site ab und erzeugt/aktualisiert
 * automatische Aufgaben ("Braucht Handlung").
 *
 * Grundsatz aus dem Anforderungsdoc: "Alles grün" ist der Ruhezustand –
 * Aufgaben entstehen nur, wenn etwas zu tun ist, und werden über einen
 * dedupe_key idempotent gehalten (keine Doppel-Tasks bei wiederholten Läufen).
 */
class SiteStatusEvaluator
{
    /** Ab so vielen Stunden ohne Lebenszeichen gilt eine Site als offline. */
    private int $offlineAfterHours = 26; // > 2 erwartete Tagesmeldungen + Puffer

    /** Vorlaufzeiten für Ablauf-Warnungen (Tage). */
    private int $sslWarnDays = 21;
    private int $domainWarnDays = 30;
    private int $licenseWarnDays = 30;

    /** Schwellenwerte aus den zentralen Einstellungen lesen (mit sicheren Defaults). */
    public function __construct()
    {
        $this->offlineAfterHours = (int) rescue(fn () => Setting::get('offline_after_hours', 26), 26, false);
        $this->sslWarnDays       = (int) rescue(fn () => Setting::get('ssl_warn_days', 21), 21, false);
        $this->domainWarnDays    = (int) rescue(fn () => Setting::get('domain_warn_days', 30), 30, false);
        $this->licenseWarnDays   = (int) rescue(fn () => Setting::get('license_warn_days', 30), 30, false);
    }

    public function evaluate(Site $site): SiteStatus
    {
        $status = $this->deriveStatus($site);

        if ($site->status !== $status) {
            $site->forceFill(['status' => $status])->save();
        }

        $this->syncTasks($site);

        return $status;
    }

    private function deriveStatus(Site $site): SiteStatus
    {
        // Manuell auf Wartung gesetzte Sites behalten ihren Status.
        if ($site->status === SiteStatus::Maintenance) {
            return SiteStatus::Maintenance;
        }

        // Externe Sites haben keinen Reporter -> Status kommt aus dashboard-seitigen
        // Prüfern (Phase 5). Bis dahin: unknown, sofern kein last_seen.
        if ($site->isExternal() && ! $site->last_seen_at) {
            return SiteStatus::Unknown;
        }

        if (! $site->last_seen_at) {
            return SiteStatus::Unknown;
        }

        // Dead-Man's-Switch: zu lange nichts gehört -> offline.
        if ($site->last_seen_at->lt(now()->subHours($this->offlineAfterHours))) {
            return SiteStatus::Offline;
        }

        return SiteStatus::Online;
    }

    /**
     * Erzeugt/aktualisiert automatische Aufgaben anhand des aktuellen Zustands.
     * Offene Auto-Tasks, deren Anlass weggefallen ist, werden geschlossen.
     */
    private function syncTasks(Site $site): void
    {
        $active = []; // dedupe_keys, die aktuell gültig sind

        // 1) Offene Updates (WP-Core/Plugins/Themes summarisch)
        if ($site->pending_updates > 0) {
            $active[] = $this->ensureTask($site, [
                'type'     => 'update',
                'severity' => $site->pending_updates >= 5 ? 'warning' : 'info',
                'title'    => "{$site->pending_updates} ausstehende Update(s)",
            ]);
        }

        // 2) Offline
        if ($site->status === SiteStatus::Offline) {
            $active[] = $this->ensureTask($site, [
                'type'     => 'offline',
                'severity' => 'critical',
                'title'    => 'Site meldet sich nicht (offline?)',
            ]);
        }

        // 3) SSL-Ablauf
        if (($d = $site->sslDaysLeft()) !== null && $d <= $this->sslWarnDays) {
            $active[] = $this->ensureTask($site, [
                'type'     => 'ssl_expiry',
                'severity' => $d <= 7 ? 'critical' : 'warning',
                'title'    => $d < 0 ? 'SSL-Zertifikat abgelaufen' : "SSL läuft in {$d} Tagen ab",
                'due_date' => $site->ssl_expires_at,
            ]);
        }

        // 4) Domain-Ablauf
        if (($d = $site->domainDaysLeft()) !== null && $d <= $this->domainWarnDays) {
            $active[] = $this->ensureTask($site, [
                'type'     => 'domain_expiry',
                'severity' => $d <= 7 ? 'critical' : 'warning',
                'title'    => $d < 0 ? 'Domain abgelaufen' : "Domain läuft in {$d} Tagen ab",
                'due_date' => $site->domain_expires_at,
            ]);
        }

        // 5) Lizenz-Abläufe
        foreach ($site->licenses()->whereNotNull('expires_at')->get() as $license) {
            $d = (int) round(now()->diffInDays($license->expires_at, false));
            if ($d <= $this->licenseWarnDays) {
                $active[] = $this->ensureTask($site, [
                    'type'        => 'license_expiry',
                    'severity'    => $d <= 7 ? 'critical' : 'warning',
                    'title'       => "Lizenz \"{$license->product}\" läuft" . ($d < 0 ? ' ist abgelaufen' : " in {$d} Tagen ab"),
                    'due_date'    => $license->expires_at,
                    'subject_type' => $license->getMorphClass(),
                    'subject_id'  => $license->id,
                    'key_suffix'  => 'license:' . $license->id,
                ]);
            }
        }

        // Weggefallene Auto-Tasks dieser Site schließen.
        $site->tasks()
            ->where('auto_generated', true)
            ->whereIn('status', ['open', 'in_progress', 'blocked'])
            ->when(! empty($active), fn ($q) => $q->whereNotIn('dedupe_key', $active))
            ->update(['status' => 'done', 'resolved_at' => now()]);
    }

    /**
     * Legt eine Auto-Task an oder aktualisiert sie idempotent.
     *
     * @param  array<string,mixed>  $data
     * @return string  der verwendete dedupe_key
     */
    private function ensureTask(Site $site, array $data): string
    {
        $suffix = $data['key_suffix'] ?? $data['type'];
        $dedupeKey = "site:{$site->id}:{$suffix}";

        $site->tasks()->updateOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
                'title'          => $data['title'],
                'type'           => $data['type'],
                'severity'       => $data['severity'] ?? 'info',
                'due_date'       => $data['due_date'] ?? null,
                'subject_type'   => $data['subject_type'] ?? null,
                'subject_id'     => $data['subject_id'] ?? null,
                'auto_generated' => true,
                // status bewusst NICHT mitsetzen: beim Anlegen greift der DB-Default 'open',
                // beim Update bleibt ein evtl. manuell gesetzter Status (z. B. in_progress) erhalten.
            ]
        );

        // Falls eine zuvor geschlossene Auto-Task wieder relevant wird: re-öffnen.
        $site->tasks()
            ->where('dedupe_key', $dedupeKey)
            ->whereIn('status', ['done', 'dismissed'])
            ->update(['status' => 'open', 'resolved_at' => null]);

        return $dedupeKey;
    }
}
