# Ops Cockpit — Gesamtplan / Roadmap

> Arbeitsgrundlage für den systematischen Ausbau. Ziel: ein **grafisch professionelles,
> fehlerfreies Dashboard** mit **sauber integrierter, assistierender KI-Schicht**.
> Stand: 16.06.2026 · Sprache: Deutsch · Leitsatz: „Alles grün" = Ruhezustand, KI assistiert, entscheidet nie.

---

## 0. Zielbild

Am Ende soll das Cockpit so wirken, als käme es von einem Senior-Team mit UI/UX-Kompetenz:

- **Optik:** eigenes, ruhiges Daten-Cockpit mit Agentur-Branding statt Standard-Filament. Klare visuelle Hierarchie, sinnvolle Visualisierungen (Status, Trends, Ablauf-Fristen), poliertes Site-Detail. Dark-Mode-Default, barrierefrei, responsiv.
- **Funktion:** alles tut nachweislich, was es soll (Boot grün, Tests grün, Ingest-Vertrag gesichert, Auto-Aufgaben korrekt).
- **KI:** assistiert beim Extrahieren, Sortieren, Abgleichen, Entwerfen — liefert **Daten in festem Schema, nie Befehle**; jede folgenreiche Aktion bestätigt ein Mensch (Human-in-the-Loop). EU-konform, datensparsam.

---

## 1. Ausgangslage (kompakt)

| Bereich | Stand | Konsequenz für den Plan |
|---|---|---|
| Datenmodell (17 Tabellen) | vollständig als Migrations + Models | Fundament steht, kaum Änderungen nötig |
| Reporter-Plugin (read-only, HMAC) | fertig, syntaktisch geprüft | unverändert lassen |
| Ingest → Snapshot → Status/Tasks | MVP fertig | Logik tragfähig, ggf. erweitern |
| Scheduler (Heartbeat, Ablauf-Scan) | MVP fertig | ok |
| **App-Boot (migrate/test)** | **nie gelaufen** (Composer war gesperrt) | **Phase A zuerst** |
| **Filament-Dashboard** | **Standard-Look**, 5 Kacheln + 1 Tabelle | **Phase B: Redesign** |
| **KI-Layer** | **nur DB-Felder** (`signals.triage`, `obligations.applies_when`), keine Logik | **Phase C: Konzept + Umsetzung** |
| Compliance-Hub, Signal-Triage, Phase-5-Prüfer | Tabellen da, Logik offen | in Phasen C/D |

**Kernrisiko:** UI/KI verfeinern, ohne dass die App nachweislich läuft, heißt „blind polieren". Deshalb steht das Grün-Ziehen am Anfang.

---

## 2. Phase A — Fundament: App booten & grün ziehen

**Ziel:** lauffähige App, Migrationen sauber, Ingest-Test grün, Demo-Daten sichtbar.

1. Frisches Laravel 11 + Filament 3 aufsetzen, Paket-Dateien laut `INSTALL.md` einkopieren.
2. `php artisan install:api` (Laravel 11: aktiviert `routes/api.php`), `key:generate` (APP_KEY = Verschlüsselung der Secrets at rest — niemals verlieren).
3. `migrate` + `DemoSeeder`. Admin-Login sofort ändern.
4. `php artisan test --filter=IngestEndpointTest` → **muss grün sein** (sichert den HMAC-Vertrag).
5. Scheduler-Cron einrichten; `sites:heartbeat-sweep` und `sites:expiry-scan` einmal manuell prüfen.
6. Smoke-Test: einen signierten Push wie das Plugin senden, Snapshot + Statuswechsel + Auto-Task im Cockpit verifizieren.

**Definition of Done:** Login funktioniert, Demo-Sites mit Ampel sichtbar, Test grün, ein manueller Push erzeugt korrekt Snapshot/Status/Task.

**Offene Punkte vorab:** Ziel-Umgebung (lokal vs. Mittwald-Container) · MySQL/MariaDB-Zugang · PHP-Version (8.2+).

---

## 3. Phase B — Dashboard-Redesign (grafisch professionell)

> Das ist der sichtbare Kern. Wir gehen von „funktioniert" zu „wirkt hochwertig".

### B1 — Branding & Theme-Fundament
- Eigenes Filament-Theme (`make:filament-theme`, Tailwind-Build) statt Inline-Farben.
- Agentur-CI: Logo (hell/dunkel), Favicon, `brandName`/`brandLogo`, abgestimmte Primär-/Akzent-/Statusfarben (success/warning/danger müssen WCAG-Kontrast erfüllen, auch auf Dark).
- Typografie, Dichte (kompakt, datenfreundlich), konsistente Abstände, Fokus-/Hover-Zustände.

### B2 — Dashboard-Layout (Startseite)
Kuratierte Hierarchie statt loser Widgets:
- **Zeile 1 — KPIs:** bestehende 5 Kacheln verfeinern (Icon, Farbe nur bei Handlungsbedarf, klare Sekundärtexte). Ggf. klickbar → gefilterte Sites-Liste.
- **Zeile 2 — „Braucht Handlung":** prominent, kritisch zuerst (vorhanden, optisch aufwerten: Severity-Akzent, Zeilen-Aktionen, leere-Liste-Zustand „schön ruhig").
- **Zeile 3 — Visualisierungen (neu):**
  - **Status-Verteilung** (Donut/Bar: online/wartung/offline/unbekannt).
  - **Update-/Health-Trend** über Zeit (aus `site_snapshots` aggregiert).
  - **Ablauf-Timeline** der nächsten ~90 Tage (SSL/Domain/Lizenz), chronologisch.
- Optional: zuletzt geänderte Sites, Health pro Kunde.

### B3 — Site-Detail aufwerten
Von schlichter Infolist zu strukturiertem Detail (Tabs/Sektionen):
Überblick · Technik (WP/PHP/Updates) · **Plugins** (mit Update-/Hold-Badges) · **Verlauf** (Snapshot-/Versions-Timeline) · Aufgaben · (später) Compliance · Dokumente.

### B4 — Politur & Qualität
- Zustände sauber: leer / lädt / Fehler — nie weiße Seiten oder rohe Fehler.
- Barrierefreiheit: semantik, Labels, Tastaturbedienung, Kontrast, keine rein farbliche Info.
- Responsiv (Tablet/mobil im Backend), keine Layoutsprünge.
- Mikrointeraktionen nur, wo sie Orientierung geben.

**Definition of Done:** eigenes Branding sichtbar, Startseite mit KPIs + Handlungsliste + mind. zwei Visualisierungen, poliertes Site-Detail, A11y-Check bestanden, mobil ohne Layoutfehler.

**Offene Punkte vorab:** Logo/CI-Assets & Farbwerte der Agentur · welche Visualisierungen Priorität haben · Chart-Lösung (Filament Charts/ApexCharts vs. eigenes).

---

## 4. Phase C — KI-Layer (assistierend, Human-in-the-Loop)

> KI liefert **strukturierte Daten in festem Schema**, niemals ausführbare Befehle. Eingaben aus E-Mail/Feeds gelten als **nicht vertrauenswürdig** (Prompt-Injection-Schutz).

### C0 — Entscheidungen zuerst (Blocker, siehe §6)
Provider mit **EU-Verarbeitung, AVV/DPA, Zero-Retention**; Output-Schema; Retention-Fristen.

### C1 — Compliance-Auto-Matching (Phase 3, überwiegend regelbasiert)
- Kern ist **deterministisch:** `obligations.applies_when` (JSON) gegen die `fingerprint`-Signale jedes Snapshots matchen → `site_obligations` mit `auto_matched=true` ableiten. Kein KI-Zwang.
- **KI assistiert optional:** Katalog-Einträge entwerfen, neue Pflichten aus Signalen vorschlagen, Klartext-Begründung formulieren — Mensch übernimmt in den Katalog.
- UI: Compliance-Sektion im Site-Detail + Pflichten-Katalog-Resource.

### C2 — Signal-Mailbox + KI-Triage (Phase 4)
- Eingang: Feeds (EOL/WP.org), Mailbox, manuell → `signals`.
- **KI-Triage** erzeugt festes JSON: `{summary, affects:[fingerprint-Bedingungen], suggested_obligation_keys:[], severity}` → Validierung gegen Schema → **Prüf-Queue**.
- **Mensch genehmigt/verwirft** (`triage_status`, `reviewed_by`). Erst Freigabe darf Pflichten/Aufgaben anlegen.
- UI: Signal-Inbox-Resource mit Triage-Ansicht, Approve/Dismiss, Audit-Spur.

### C3 — Leitplanken (verbindlich)
Sandboxed Service · Schema-Validierung jeder KI-Antwort · keine Auto-Aktionen · Audit-Log · Datensparsamkeit/PII-Minimierung · Retention · Rate-Limits · Secrets/Keys nie im Code.

**Definition of Done:** Auto-Matching erzeugt nachvollziehbare `site_obligations`; eingehende Signale werden zu validierten Triage-Vorschlägen, die ein Mensch freigeben muss; nichts wird ohne Freigabe wirksam; alles im Audit-Log.

---

## 5. Phase D — Spätere Ausbaustufen (nach B/C)
- **Phase 5 — Dashboard-Prüfer** für Nicht-WP-Seiten (`cms_type='extern'`): Uptime, SSL, Domain, Broken-Links, Formular-Synthetik, A11y/Perf — speisen denselben Status-/Aufgaben-Mechanismus. (Offene Entscheidung: Headless-Browser als Mittwald-Container.)
- **Phase 6 — Reporting & Status-Pages** für Kunden.

---

## 6. Offene Entscheidungen (vor der jeweiligen Phase klären)

| # | Thema | Betrifft | Braucht Entscheidung |
|---|---|---|---|
| 1 | Ziel-Umgebung (lokal vs. Mittwald), DB-Zugang | Phase A | vor Boot |
| 2 | CI-Assets: Logo, Favicon, Farbwerte | Phase B | vor Theme |
| 3 | Priorisierung der Visualisierungen + Chart-Lösung | Phase B | vor B2 |
| 4 | KI-Provider (EU, AVV/DPA, Zero-Retention) | Phase C | vor jeder KI-Logik |
| 5 | Retention-Fristen (Snapshots/Signale/Logs) | Phase C | vor C2 |
| 6 | Headless-Browser-Betrieb | Phase 5 | später |

---

## 7. Sicherheits- & Datenschutz-Leitplanken (durchgängig)
- HMAC-Vertrag nicht brechen (Signatur vor Parsen, ±10-Min-Replay, `hash_equals`).
- Secrets/Lizenzschlüssel verschlüsselt at rest (`encrypted`-Cast, am APP_KEY hängend) — APP_KEY sichern.
- Reporter bleibt outbound-only, kein Inbound, `sslverify` aktiv.
- KI: Daten ≠ Befehle, festes Schema, Mensch bestätigt, Audit-Log, Datensparsamkeit.
- UI: konsequentes Escaping, Capability-Checks, Nonces/CSRF, keine rohen Fehler.

---

## 8. Empfohlene Arbeitsreihenfolge
**A (Boot/Grün) → B (Dashboard-Redesign) → C (KI-Layer) → D (Erweiterungen).**
Pro Bereich am Stück erarbeiten, am Ende jeweils gemeinsamer Review gegen die Definition of Done.
