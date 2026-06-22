# Websitepakete – Katalog & Logik (Quelle: Agentur-Preisblatt)

Abrechnung grundsätzlich **jährlich im Voraus**. Preise netto laut Vorlage.
Zustände pro Site: **gebucht** · **abgewählt** („will der Kunde ausdrücklich nicht") · (nicht gesetzt).

## Hosting
| key | Name | Preis | Gruppe | Logik |
|---|---|---|---|---|
| `hosting.highperf` | High-Performance-Hosting | 350 €/J | hosting_tier | schließt `hosting.light` aus |
| `hosting.light` | High-Performance-Hosting „Light" | 260 €/J | hosting_tier | schließt `hosting.highperf` aus |
| `hosting.domain` | Domainhosting (1 Domain) | 3,50 €/M | addon | – |
| `hosting.webspace_addon` | Zusatzpaket Webspace (+1 GB) | 2,50 €/M | addon | benötigt ein Hosting-Paket (`hosting.highperf` **oder** `hosting.light`) |
| `mail.sendonly` | „Send Only" Mailadresse | 4 €/M | addon | empfohlen bei Formularen/Shop |

## Update-Service
| key | Name | Preis | Logik |
|---|---|---|---|
| `update.website` | Update-Service Website | 39 €/M | schließt `update.shop` aus |
| `update.shop` | Update-Service Shop | 73 €/M | schließt `update.website` aus (Superset inkl. WooCommerce) |

## Service
| key | Name | Preis | Gruppe | Logik |
|---|---|---|---|---|
| `seo.basic` | SEO Basic | 490 € einmalig | seo | – |
| `service.performance` | Performance | 25 €/M | performance | – |
| `service.performance_individual` | Performance „Individual" | 500–1.000 € einmalig | performance | benötigt `seo.basic` **und** `service.performance` |
| `service.reporting` | Reporting (quartalsweise) | 30 €/M | reporting | – |
| `datenschutz.complete` | Datenschutz Complete (Cookiebox+DSGVO+Impressum) | 180 € einmalig + 36 €/M | datenschutz | schließt `datenschutz.cookiebox` aus |
| `datenschutz.cookiebox` | Cookiebox Only | 120 € einmalig + 20 €/M | datenschutz | schließt `datenschutz.complete` aus |
| `security.basic` | Sicherheit Basic (Scans, Monitoring) | 30 €/M | security | schließt `security.advanced` aus |
| `security.advanced` | Sicherheit Advanced (+WAF, Bot Protection, Login-Maskierung, Uptime) | 40 €/M | security | schließt `security.basic` aus |
| `a11y` | Barrierefreiheit (Icon) | 300 € einmalig + 120 €/J | a11y | – |

## Verknüpfung mit dem Monitoring (was die Pakete „im Hintergrund" bedeuten)
- **Update-Service Website/Shop gebucht** → Update-Aufgaben sind SLA-relevant (höhere Priorität, Reporting), Shop zusätzlich WooCommerce-Plugins beobachten.
- **Sicherheit Advanced** → WAF/Bot-Protection/Uptime erwartet → Uptime-Check + (Fingerprint) Security-Plugins prüfen.
- **Datenschutz Complete / Cookiebox** → Consent-/DSGVO-Pflichten (Fingerprint `has_consent`/`has_tracking`) zur Site-Compliance matchen.
- **Barrierefreiheit** → EAA-/Accessibility-Pflicht aktiv halten.
- **Send Only Mailadresse** → Formular-Mailversand-Check (Phase 5, Punkt 10).
- **Reporting** → Quartals-E-Mail-Bericht (Punkt 11).

> Modellierung: `packages` (Katalog mit `requires`, `requires_any`, `excludes`) + `site_packages` (Zustand je Site). Katalog wird per Seeder idempotent gepflegt.
