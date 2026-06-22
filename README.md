# Ops Cockpit

Zentrales Monitoring- und Verwaltungs-Cockpit für die Kundenwebsites der Agentur.
Diese Umsetzung deckt das **MVP (Phase 0–1: Monitoring + Inventar)** aus dem
Anforderungsdokument ab und legt das vollständige Datenmodell als Fundament für
die späteren Phasen (Compliance-Hub, Signal-/KI-Triage, tiefes Monitoring) an.

Entscheidungen laut Definitionsphase: **Laravel 11 + Filament 3**, **dunkles
Daten-Cockpit**, **MVP zuerst auf Technik (Monitoring + Inventar)**.

---

## Was hier drin ist

```
ops-cockpit/
├── reporter-plugin/
│   └── ops-reporter.php          WordPress-Plugin: read-only, outbound-only,
│                                 HMAC-signiert, mit Fingerprint-Signalen
└── laravel-app/                  Zentrale App (in frisches Laravel+Filament kopieren)
    ├── INSTALL.md                Schritt-für-Schritt-Setup
    ├── app/Enums/                SiteStatus, Severity, TaskStatus
    ├── app/Models/               vollständiges Datenmodell (Abschnitt 8)
    ├── app/Services/             SignatureVerifier, SnapshotIngestor, SiteStatusEvaluator
    ├── app/Http/Controllers/Api/ IngestController (gehärteter Eingang)
    ├── app/Console/Commands/     Heartbeat-Sweep, Ablauf-Scan
    ├── app/Filament/             Cockpit-Widgets + Sites-/Kunden-Resources
    ├── database/migrations/      alle Tabellen
    ├── database/seeders/         DemoSeeder
    └── tests/Feature/            Ingest-Vertrag (HMAC) abgesichert
```

---

## Architektur in einem Absatz

Auf jeder Kundenseite läuft das **Reporter-Plugin** und schickt 2×/Tag einen
signierten, *ausgehenden* Statusbericht (Versionen, Plugins, Themes, Fingerprint-
Signale) an den **Ingest-Endpoint** der zentralen App. Dieser prüft die HMAC-Signatur
**vor** dem Parsen, lehnt unbekannte/veraltete Anfragen ab und speichert einen
append-only **Snapshot**. Der **SiteStatusEvaluator** leitet daraus den Ampel-Status
ab (Dead-Man's-Switch: meldet sich eine Seite zu lange nicht → offline) und erzeugt
idempotente **Aufgaben** für Updates, SSL-/Domain-/Lizenz-Abläufe. Das **Filament-
Cockpit** zeigt oben KPIs, darunter die „Braucht Handlung"-Liste (kritisch zuerst),
darunter die Sites-Tabelle mit Ampel, Versionen und Ablauf-Restzeiten. „Alles grün"
ist der Ruhezustand.

---

## Sicherheit (umgesetzt)

- **Reporter:** outbound-only, kein Inbound-Endpoint; per-Site-HMAC-Secret aus
  `wp-config.php` (nicht in DB/Repo); `sslverify` aktiv; Replay-Schutz via Timestamp.
- **Ingest:** Signaturprüfung vor dem Parsen (`hash_equals`, timing-safe); ±10-Min-
  Zeitfenster; unbekannte/archivierte Site-IDs abgelehnt; Rate-Limit pro Site+IP.
- **App:** Site-Secrets und Lizenzschlüssel **verschlüsselt at rest** (`encrypted`-Cast,
  hängt am `APP_KEY`); Audit-Log-Tabelle, Rollen- und 2FA-Felder vorbereitet.

---

## Onboarding einer Site

1. Im Cockpit unter **Sites → Neu** anlegen. Eine **Site-ID** vergeben (z. B.
   `ried-immobilien`) und das angebotene **Secret** generieren (wird verschlüsselt
   gespeichert, einmal im Klartext anzeigbar).
2. `ops-reporter.php` als Plugin auf der Kundenseite installieren und aktivieren.
3. In die `wp-config.php` der Seite eintragen:
   ```php
   define( 'OPS_REPORTER_ENDPOINT', 'https://dashboard.deineagentur.at/api/ingest' );
   define( 'OPS_REPORTER_SITE_ID',  'ried-immobilien' );
   define( 'OPS_REPORTER_SECRET',   '<das-generierte-secret>' );
   ```
4. Optional sofort testen: `wp ops-reporter send` (WP-CLI). Im Cockpit erscheint
   nach Eingang der erste Snapshot, Status springt auf grün.
5. Bei traffic-armen Seiten echten Server-Cron auf `wp-cron.php` zeigen lassen und
   `define('DISABLE_WP_CRON', true);` setzen, sonst Fehlalarme durch den Dead-Man's-Switch.

**Offboarding:** Site im Cockpit archivieren → der Ingest weist weitere Pushes ab
(Secret wird nicht mehr akzeptiert). Plugin auf der Seite deaktivieren.

---

## Mapping zum Anforderungsdokument

| Abschnitt | Anforderung | Status in diesem Paket |
|---|---|---|
| 3.1 | Reporter-Plugin inkl. Fingerprint-Signale | ✅ vollständig |
| 3.2 / 10 | Laravel + Filament, Ingest, Scheduler | ✅ MVP |
| 6 | Sicherheitsanforderungen (HMAC, Replay, at-rest-Verschlüsselung) | ✅ Kern umgesetzt |
| 7 | Dunkles Cockpit, KPIs, „Braucht Handlung", Sites-Liste, Site-Detail | ✅ MVP |
| 8 | Datenmodell (alle Tabellen) | ✅ vollständig angelegt |
| 2 / 5 | Aufgaben/Fristen-Mechanik (eine Mechanik für Updates + Abläufe) | ✅ umgesetzt |
| 3.3 / 4 | KI-Triage, Signal-Mailbox | ⏳ Tabellen da, Logik = Phase 4 |
| 3 / 5 | Compliance-Hub, Dokumenten-Versionierung | ⏳ Tabellen da, UI = Phase 3 |
| 5 | Formular-Synthetik, Broken-Links, Accessibility, Performance | ⏳ Phase 5 |

✅ = in diesem Paket · ⏳ = Fundament gelegt, Ausbau in späterer Phase

---

## Nächste sinnvolle Schritte

1. App lokal/auf Mittwald booten (INSTALL.md), `php artisan test` grün ziehen.
2. Compliance-Hub (Phase 3): `obligations`-Katalog befüllen und Auto-Matching aus
   den Fingerprint-Signalen der Snapshots ableiten (`applies_when`).
3. Dashboard-seitige Prüfer (Phase 5) für Nicht-WP-Seiten: Uptime, SSL, Domain,
   Broken-Links — füttern denselben Status-/Aufgaben-Mechanismus.
