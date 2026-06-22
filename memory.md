# memory.md — Ops Cockpit

> Diese Datei ist das Gedächtnis des Projekts. **Lies sie zuerst.** Sie sagt dir, was
> das Projekt ist, welche Entscheidungen feststehen (nicht neu aufrollen), wie es
> aufgebaut ist, was schon gebaut ist und was als Nächstes dran ist.
> Den Abschnitt „Kurzfassung" kannst du 1:1 in das Cowork-Feld **Instructions** kopieren.

---

## Kurzfassung (für das Instructions-Feld)

Ops Cockpit ist ein zentrales **Monitoring- und Verwaltungs-Cockpit für die
Kundenwebsites unserer Werbe-/Digitalagentur**. Es sammelt von jeder Site read-only
Telemetrie (Versionen, Plugins, Erreichbarkeit, Compliance-relevante Signale),
zeigt sie in einem dunklen „Operations-Cockpit" und erzeugt automatisch Aufgaben,
wenn etwas zu tun ist. Stack: **Laravel 12 + Filament 3**. Leitsatz: **„Alles grün"
ist der Ruhezustand** — die Oberfläche zeigt primär, was Handlung braucht. **KI
assistiert, entscheidet nie** (Human-in-the-Loop). Sprache der Oberfläche und der
Inhalte: **Deutsch**. Antworte und arbeite auf Deutsch.

---

## 1. Worum es geht (Problem)

Die Agentur betreut viele Kundenwebsites (überwiegend WordPress, teils extern
gehostet) und verkauft Zusatzpakete (Updateservice, Ausfallsicherheit, Compliance).
Bisher verteilt sich der Überblick über Reportings, Mails und Köpfe. Das Cockpit
vereint zentral: **Inventar** (welche Site, welcher Kunde, welches Paket, welche
Lizenzen), **Monitoring** (Erreichbarkeit, Versionsstände, Updates, SSL-/Domain-
Abläufe) und später **Compliance** (welche gesetzlichen Pflichten betreffen welche
Site) sowie **Signale** (neue EU-Richtlinien, fehlerhafte Plugin-Updates).

## 2. Feststehende Entscheidungen (nicht neu diskutieren)

- **Stack:** Laravel 12 + Filament 3, MySQL/MariaDB, PHP 8.2+.
  (Ursprünglich Laravel 11 geplant; beim Bootstrap 06/2026 war Laravel 11 komplett
  durch Security-Advisories gesperrt und Laravel 13 ist mit Filament 3 inkompatibel
  → Laravel 12, von Filament 3 unterstützt, ist die saubere, gepatchte Wahl.)
- **Look & Feel:** dunkles Daten-Cockpit (Dark-Mode als Default, Umschalter bleibt).
- **MVP-Fokus:** zuerst Technik (Monitoring + Inventar), Compliance/KI später.
- **Datenerhebung:** ein eigenes, schlankes **Reporter-Plugin** pro Site, das
  **read-only** liest und **nur ausgehend** (Push) signiert sendet. Kein Inbound,
  keine Fernsteuerung der Kundenseiten.
- **Sicherheit:** Das Dashboard ist die „Kronjuwele" → isoliertes Mittwald-Projekt,
  eigene Subdomain (z. B. `dashboard.deineagentur.at`), 2FA, Audit-Log.
- **KI:** assistiert (extrahieren, sortieren, abgleichen, entwerfen), entscheidet nie;
  jede folgenreiche Aktion bestätigt ein Mensch.
- **Versionierung/Deployment:** privates GitHub-Repo `Website-Monitor-Hammerer` als
  Source of Truth; Auto-Deploy auf Mittwald via **Deployer + offizielle Mittwald-Recipe**
  (GitHub Actions, Push auf `main`). `.env`/`APP_KEY`/Secrets **nie** im Repo; `.env` lebt
  im `shared/`-Verzeichnis auf dem Server. Konfig-Dateien: `repo-setup/`,
  Anleitung: `SETUP-Github-Mittwald.md`, Express-Setup: `bootstrap-und-push.sh`.

## 3. Architektur in Kürze

```
[Kundenseite WP] --Reporter-Plugin (2x/Tag, HMAC-signiert, ausgehend)-->
        |
        v
[Zentrale App / Mittwald]  POST /api/ingest
   IngestController  -> SignatureVerifier (HMAC vor dem Parsen, Replay-Fenster)
                     -> SnapshotIngestor  (Snapshot speichern, plugins_seen upsert)
                     -> SiteStatusEvaluator (Status ableiten + Auto-Aufgaben)
        |
        v
[Filament-Cockpit]  KPIs + "Braucht Handlung"-Liste + Sites-Tabelle + Site-Detail
```

**Nicht-WP-Seiten:** kein Plugin möglich → `cms_type = 'extern'`; deren Checks
(Uptime/SSL/Domain/…) kommen dashboard-seitig in Phase 5.

## 4. Ordnerstruktur

```
ops-cockpit/
├── README.md                     Überblick + Onboarding einer Site
├── memory.md                     ← diese Datei
├── reporter-plugin/
│   └── ops-reporter.php          WordPress-Plugin (read-only, outbound, HMAC, Fingerprint)
└── laravel-app/                  Zentrale App (in frisches Laravel+Filament kopieren)
    ├── INSTALL.md                Setup Schritt für Schritt
    ├── app/Enums/                SiteStatus, Severity, TaskStatus
    ├── app/Models/               vollständiges Datenmodell
    ├── app/Services/             SignatureVerifier, SnapshotIngestor, SiteStatusEvaluator
    ├── app/Http/Controllers/Api/ IngestController
    ├── app/Console/Commands/     SitesHeartbeatSweep, SitesExpiryScan
    ├── app/Filament/             AdminPanelProvider, Widgets, Site-/Customer-Resources
    ├── database/migrations/      alle Tabellen
    ├── database/seeders/         DemoSeeder
    └── tests/Feature/            IngestEndpointTest (sichert den HMAC-Vertrag)
```

## 5. Aktueller Stand

| Bereich | Stand |
|---|---|
| Reporter-Plugin inkl. Fingerprint-Signale | ✅ fertig, syntaktisch geprüft |
| Datenmodell (alle 17 Tabellen) | ✅ vollständig als Migrations + Models |
| Ingest-Datenfluss (Signatur → Snapshot → Status/Tasks) | ✅ MVP fertig |
| Scheduler (Heartbeat, Ablauf-Scan) | ✅ MVP fertig |
| Filament-Cockpit (KPIs, Braucht-Handlung, Sites, Kunden) | ✅ MVP fertig |
| Feature-Test für Ingest | ✅ vorhanden |
| Compliance-Hub (obligations) | ⏳ Tabellen da, Logik = Phase 3 |
| Signal-Mailbox + KI-Triage | ⏳ Tabellen da, Logik = Phase 4 |
| Dashboard-Prüfer (Formular-Synthetik, Broken-Links, A11y, Perf) | ⏳ Phase 5 |

**Wichtig:** Die App wurde noch **nicht gebootet** (in der Bau-Umgebung war
Composer gesperrt). Erster echter Lauf (`composer`, `php artisan migrate`,
`php artisan test`) steht aus — siehe `laravel-app/INSTALL.md`.

## 6. Sicherheits-Vertrag (NICHT brechen)

- **HMAC:** `signature = hash_hmac('sha256', timestamp . '.' . rawBody, per_site_secret)`.
  Header: `X-Ops-Site`, `X-Ops-Timestamp`, `X-Ops-Signature`. Body = JSON
  `{site_id, sent_at, nonce, report}`. Server prüft **timing-safe** (`hash_equals`)
  und mit **±10-Min-Replay-Fenster** — **vor** dem JSON-Parsing.
- **Secret pro Site, verschlüsselt at rest** (`encrypted`-Cast). **Nicht hashen!**
  HMAC braucht das Klartext-Secret zum Nachrechnen. Die Verschlüsselung hängt am
  **`APP_KEY`** → diesen niemals verlieren, sonst sind Secrets und Lizenzschlüssel
  unwiederbringlich.
- **Reporter:** kein Inbound-Endpoint, `sslverify` nie abschalten, Secret/Endpoint
  in `wp-config.php` (nicht in DB/Repo). Bei traffic-armen Seiten echten Server-Cron
  auf `wp-cron.php` zeigen lassen + `define('DISABLE_WP_CRON', true);`.

## 7. Konventionen

- Eloquent-Models: `$guarded = ['id']`, Casts explizit; Enums für Status/Schwere.
- **Auto-Aufgaben sind idempotent** über `tasks.dedupe_key` (`site:{id}:{typ}`) und
  `auto_generated = true`. Manuell verschobene Tasks (z. B. `in_progress`) werden
  von Neubewertungen **nicht** zurückgesetzt. Weggefallene Anlässe → Task auf `done`.
- **„Alles grün" = Ruhezustand:** Aufgaben/Alerts nur erzeugen, wenn wirklich etwas
  zu tun ist.
- UI-Texte auf Deutsch. Datumsformat `d.m.Y`.
- Schwellen im `SiteStatusEvaluator`: offline nach **26 h** ohne Lebenszeichen;
  SSL-Warnung **21 Tage**, Domain-/Lizenz-Warnung **30 Tage** vorher.

## 8. Häufige Aufgaben (Cheat-Sheet)

```bash
# Setup (einmalig) – Details in laravel-app/INSTALL.md
composer create-project laravel/laravel ops-cockpit
composer require filament/filament:"^3.2"
php artisan install:api        # Laravel 11: aktiviert routes/api.php + /api-Prefix
php artisan key:generate
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\DemoSeeder   # Demo-Daten + Admin

# Tests
php artisan test --filter=IngestEndpointTest

# Scheduler lokal beobachten
php artisan schedule:work

# Admin-Login (Demo): admin@deineagentur.at / passwort-bitte-aendern  -> sofort ändern
```

**Site onboarden:** im Cockpit unter *Sites → Neu* anlegen (Site-ID + Secret
generieren) → `ops-reporter.php` auf der Kundenseite aktivieren → in deren
`wp-config.php` `OPS_REPORTER_ENDPOINT`, `OPS_REPORTER_SITE_ID`, `OPS_REPORTER_SECRET`
eintragen → optional `wp ops-reporter send` zum Sofort-Test.

## 9. Nächste sinnvolle Schritte (Reihenfolge)

1. **App booten & grün ziehen** (INSTALL.md, `php artisan test`).
2. **Compliance-Hub (Phase 3):** `obligations`-Katalog befüllen (DSGVO-Consent,
   EAA/Barrierefreiheit, Impressum, lokale Fonts …) und **Auto-Matching** bauen:
   aus den `fingerprint`-Daten jedes Snapshots über `obligations.applies_when`
   die passenden `site_obligations` ableiten. Die Signale dafür liegen schon in
   jedem Snapshot.
3. **Phase 5 – Dashboard-Prüfer** für Nicht-WP- und Zusatz-Checks (Uptime, SSL,
   Domain, Broken-Links, Formular-Synthetik). Speisen denselben Status-/Task-Mechanismus.
4. **Phase 4 – Signal-Mailbox + KI-Triage** (festes Output-Schema, sandboxed,
   Prüf-Queue mit menschlicher Freigabe).
5. **Phase 6 – Reporting & Status-Pages** für Kunden.

## 10. Offene Entscheidungen (vor dem jeweiligen Ausbau klären)

- **KI-Provider:** welcher? Anforderung: EU-Verarbeitung, AVV/DPA, Zero-Retention.
- **Headless-Browser** (für Formular-Synthetik): als Mittwald-Container betreiben?
- **Retention:** Aufbewahrungsfristen für Snapshots/Signale/Logs (DSGVO gilt auch
  für die Cockpit-DB selbst).
- **Nicht-WP-Seiten:** ab wann aktiv mitnehmen (Tabellen-Flag `cms_type='extern'`
  ist da, Checker fehlen noch).

## 11. Glossar

- **Reporter** – das WordPress-Plugin auf jeder Kundenseite (read-only, sendet Push).
- **Snapshot** – ein einzelner, append-only Bericht einer Site zu einem Zeitpunkt.
- **Fingerprint** – leichte Signale aus dem Snapshot (Shop? Formulare? Tracking?
  Consent? Pflichtseiten? Drittdienste?) für späteres Compliance-Auto-Matching.
- **Dead-Man's-Switch** – meldet sich eine Site zu lange nicht, gilt sie als offline.
- **Obligation** – eine gesetzliche/vertragliche Pflicht im Katalog.
- **Signal** – ein externer Hinweis (Newsletter, EOL-Feed, Mailbox), der ggf. Pflichten
  oder Aufgaben auslöst.
- **Care-Plan / Paket-Tier** – das vom Kunden gebuchte Service-Paket (steuert Intervalle/SLA).
```
