# Phase B+ — UI/UX & Funktionsausbau (Eingebungen Lukas)

Verbindliche Sammlung aller Wünsche aus der Session, mit konkretem Umsetzungsweg.
Reihenfolge = grobe Priorität. Status: ⬜ offen · 🟦 teilweise · ✅ erledigt.

---

## 1. Sinnvolle Graphen aufs Dashboard 🟦
- Vorhanden: Status-Verteilung (Doughnut), Offene Aufgaben nach Typ (Bar), **Ablauf-Vorschau 90 Tage** (neu).
- Geplant: Update-/Health-Trend über Zeit (aus `site_snapshots` aggregiert), Sites-Health pro Kunde, Reaktionszeit/Heartbeat-Verlauf.

## 2. Menüführung überdenken + zentrale Einstellungsseite ⬜
- **Dediziertes Dashboard** als Startseite (Punkt 6), darunter klar getrennte Bereiche.
- Eine **zentrale Einstellungsseite** (Filament Custom Page unter Gruppe *Verwaltung*), in der alles App-Relevante sitzt: Benachrichtigungen/E-Mail (Punkt 11), Schwellenwerte (offline-Stunden, SSL/Domain-Warnfristen), KI-Provider, Retention.
- **Tabs** innerhalb einer Seite **oben unter dem Breadcrumb** platzieren (Filament: `getHeaderWidgets`/Tabs-Komponente oben), nicht seitlich.
- Durchgängig **Icons** + **hierarchische Typografie** (Seitentitel → Section-Header → Labels → Hilfetexte).

## 3. Live-Vorschau der Website (Mini-Browser) rechts im Site-Detail ⬜
- Ziel: echte gerenderte Mini-Vorschau der Kundenseite in einer schmalen Spalte rechts.
- **Technik-Entscheidung nötig:** Ein simples `<iframe>` scheitert oft an `X-Frame-Options`/CSP der Zielseite (viele Seiten verbieten Framing). Robuste Optionen:
  1. **Screenshot-Service / Headless-Browser** (Phase 5): serverseitig Thumbnail rendern (z. B. eigener Playwright/Chromium-Container auf Mittwald, Cache pro Site, periodisch erneuert). Zuverlässig, umgeht Framing-Sperren.
  2. iframe als „Best effort" mit Fallback-Hinweis, wenn Framing blockiert.
- Empfehlung: Screenshot-Ansatz (deckt sich mit Punkt 10/Phase-5-Headless-Browser → eine Infrastruktur für beides).

## 4. Optische Farb-Hierarchien, Tooltips, z-index ⬜
- Farben strikt semantisch (success/warning/danger/gray), Primär nur für Aktionen/Links.
- Tooltips an Badges/Icons/Kurzwerten (Filament `->tooltip()`).
- z-index sauber staffeln (Dropdowns/Tooltips über Tabellen, Modals darüber) — beim Custom-Theme zentral definieren.

## 5. Websitepakete pro Site hinterlegen/anhaken (mit Logik) ⬜  ⚠️ Dokument fehlt
- Wunsch: pro Site Pakete **aktiv anhaken** und **explizit abwählen** („will der Kunde nicht"), mit **Abhängigkeiten/Logiken** zwischen Paketen.
- Datenmodell-Idee: `packages`-Katalog (key, name, beschreibung, abhängigkeiten, schließt_aus) + `site_packages` (site_id, package_id, state: `gebucht|abgewählt|n/a`, Notiz). Validierung der Abhängigkeiten beim Speichern (z. B. Paket A setzt B voraus; Paket C schließt D aus).
- **Blocker:** Das angehängte Pakete-Dokument ist nicht angekommen → bitte erneut hochladen. Danach modelliere ich Katalog + Abhängigkeitslogik exakt.

## 6. Wirklich dediziertes Dashboard als „Home" 🟦
- Filament-Standard: Dashboard IST die Startseite, das **Logo oben links verlinkt aufs Dashboard** (bereits so). Sites sind eine eigene Seite (vorhanden).
- To-do: Dashboard inhaltlich so anreichern (Punkt 1), dass es sich als echtes Cockpit-Home anfühlt; ggf. Begrüßung/Portfolio-Überblick.

## 7. SSL- & Domain-Ablauf automatisch abfragen? — JA ✅ (Konzept)
- **SSL:** serverseitig per TLS-Handshake das Zertifikat lesen (`stream_socket_client` + `openssl_x509_parse` → `validTo`). Kein Drittdienst nötig.
- **Domain:** Ablaufdatum via **RDAP** (moderner WHOIS-Nachfolger, JSON, rate-limit-freundlich) abfragen; Fallback WHOIS.
- Umsetzung als **dashboard-seitige Prüfer** (Phase 5), die denselben Status-/Aufgaben-Mechanismus speisen — funktioniert auch für Nicht-WP-Seiten (`cms_type='extern'`). Geplanter Command: `sites:expiry-probe` (täglich), schreibt `ssl_expires_at`/`domain_expires_at`.

## 8. Mehr Plugin-Infos in den Listen ⬜
- Zusätzlich erfassen/anzeigen: **Author**, **PluginURI**, ggf. **Description**; „aktiv seit"/„zuletzt aktualisiert" soweit aus WP verfügbar (Author/URI liefert `get_plugins()` direkt; ein echtes „last updated"-Datum kennt WP für Installationen nicht zuverlässig → ggf. über wp.org-API serverseitig anreichern).
- Vertikaler Schnitt: Reporter erweitern (Felder senden) → Migration `plugins_seen` (Spalten author, plugin_uri) → SnapshotIngestor mappen → UI-Spalten (toggleable) im PluginsRelationManager.

## 9. KI-Layer-Platzierung — globale Suchleiste oben ⬜ (nicht vergessen!)
- KI bleibt fest geplant (siehe `KI-Layer-Konzept.md`, Phase C).
- **Einstieg über die globale Suche oben** (Filament Global Search): natürliche Sprache → KI schlägt vor/findet (z. B. „welche Sites brauchen DSGVO-Consent?"). Ergebnisse sind **Vorschläge**, keine Aktionen (Human-in-the-Loop). Zusätzlich Signal-Triage-Inbox unter *Betrieb*.

## 10. Formular-Funktionsprüfung der Seiten — Schlachtplan (geringer Aufwand)
Ziel: erkennen, ob Kontaktformulare auf Kundenseiten technisch funktionieren.
- **Stufe 1 (günstig, reporter-seitig):** Das Reporter-Plugin meldet bereits `has_forms`/`form_plugins`. Ergänzung: meldet, ob ein **Mail-Versand konfiguriert** ist (SMTP-Plugin aktiv?) und ob die letzte WP-Mail erfolgreich war (Hook `wp_mail_failed` zählen). → Heuristik „Formular vermutlich funktionsfähig".
- **Stufe 2 (synthetischer Test, Phase 5):** dashboard-seitig mit Headless-Browser (gleiche Infrastruktur wie Punkt 3) das Formular **abschicken mit Testmarker** und prüfen, ob (a) Submit ok, (b) eine Test-Mail in einem dedizierten Postfach ankommt. Niederschwelliger Zwischenschritt: nur HTTP-POST aufs Formular-Endpoint + Erfolgsindikator im DOM prüfen.
- Empfehlung: Stufe 1 sofort mitnehmen (fast gratis), Stufe 2 zusammen mit dem Headless-Container.

## 11. E-Mail-Berichte (kritisch sofort, Updates im Intervall) — Schlachtplan
- Laravel **Notifications/Mailable** + Queue. Steuerung in der zentralen Einstellungsseite (Punkt 2):
  - **Sofort-Alerts** bei `severity=critical` (offline, SSL abgelaufen …) — getriggert im `SiteStatusEvaluator`, dedupliziert über `alerts`-Tabelle (existiert).
  - **Digest** (täglich/wöchentlich wählbar) für Updates/Warnungen — Scheduler-Command `reports:send-digest`.
  - Empfänger, Schwellen, Intervalle, Ruhezeiten konfigurierbar; Versand über Mittwald-SMTP.
- Tabellen `alerts`/`alert_channels` sind vorhanden → Logik draufsetzen.

## 12. Sicherheit gegen Schadsoftware/Bots — Status & Ausbau
**Bereits umgesetzt:** Ingest nur via **HMAC-Signatur** (vor dem Parsen geprüft, timing-safe), **Replay-Schutz** (±10 Min), unbekannte/archivierte Sites abgelehnt, **Rate-Limit** pro Site+IP, Reporter ist **outbound-only** (kein Inbound-Kommandokanal), Secrets/Lizenzschlüssel **verschlüsselt at rest** (APP_KEY), 2FA-Felder vorbereitet, `APP_DEBUG=false`.
**Noch ergänzen:**
- **2FA-Pflicht** fürs Panel (Filament unterstützt), starke Passwort-Policy.
- **Login-Throttling/Brute-Force-Schutz** (Laravel/Filament Rate-Limit am Login), evtl. IP-Allowlist fürs `/admin`.
- **Security-Header** (CSP, X-Frame-Options, HSTS) serverseitig; **WAF/Bot-Schutz** auf Mittwald-Ebene prüfen.
- **Backups** der Cockpit-DB + Restore-Test; Audit-Log konsequent füllen.
- Abhängigkeiten regelmäßig per `composer audit` prüfen (CI-Schritt).

---

## Empfohlene Reihenfolge der Umsetzung
1. Dashboard-Graphen vervollständigen (1) + dediziertes Home (6) — *läuft*.
2. Zentrale Einstellungsseite + Menü/Tabs/Typografie (2,4).
3. Websitepakete-Modul (5) — **sobald Dokument da**.
4. Plugin-Infos erweitern (8) + Stufe-1-Formularsignale (10).
5. Auto-Prüfer SSL/Domain (7) + Headless-Infra → Live-Vorschau (3) + Formular Stufe 2 (10).
6. E-Mail-Berichte (11) + Sicherheits-Härtung (12: 2FA, Header).
7. KI-Layer inkl. globale Suche (9, Phase C).
