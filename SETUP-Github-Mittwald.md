# Einrichtung: GitHub-Versionierung + Mittwald-Deployment

> Diese Anleitung richtet **vor** der Code-Überarbeitung die komplette Infrastruktur ein:
> privates GitHub-Repo als Versionsverwaltung + automatisches Deployment auf Mittwald
> via Deployer/GitHub Actions. Schritt für Schritt, auf Deutsch.
>
> **Sicherheits-Grundsatz:** `.env` und `APP_KEY` gehören **niemals** ins Repo. Der
> APP_KEY entschlüsselt die Site-Secrets und Lizenzschlüssel — bei Verlust sind diese
> unwiederbringlich. Server-Geheimnisse leben nur auf dem Server bzw. als GitHub-Secret.

Die fertigen Dateien liegen im Ordner `repo-setup/`:
`repo-setup/.gitignore`, `repo-setup/deploy.php`, `repo-setup/.github/workflows/deploy.yml`.
Sie werden in Schritt 2 ins Projekt kopiert.

---

## Express-Weg (empfohlen): ein Skript erledigt Schritte 2–3

Statt Schritte 2 und 3 von Hand zu machen, gibt es das Skript **`bootstrap-und-push.sh`**.
Es bootet Laravel 11 + Filament 3, setzt alle App- und Infra-Dateien ein, erzeugt den
`APP_KEY`, initialisiert Git und pusht (optional) direkt nach GitHub.

```bash
cd "/Users/wal/Documents/Claude/Projects/Website Monitor"
# Im Skript oben REPO_URL eintragen (SSH-URL deines Repos) – oder leer für nur-lokal:
#   REPO_URL="git@github.com:DEIN-ACCOUNT/Website-Monitor-Hammerer.git"
bash bootstrap-und-push.sh
```

Voraussetzungen: PHP 8.2+, Composer 2, Git lokal installiert. Danach weiter bei
**Schritt 4** (Deploy-Keys) bzw. **Schritt 10** (Server-`.env` & Migrationen).
Wer die Schritte lieber manuell nachvollzieht, folgt einfach der nummerierten Liste unten.

---

## 0. Was du brauchst (Voraussetzungen)

Lokal installiert:
- **Git** und ein **GitHub-Account** (idealerweise eine Organisation für die Agentur).
- **PHP 8.2+** und **Composer 2**.
- Optional, empfohlen: **GitHub CLI** (`gh`) und **Mittwald CLI** (`mw`) — beschleunigen alles.
- Ein **Mittwald-Account** mit einem Cloud-Projekt (mStudio).

> Reihenfolge im Überblick: Repo anlegen → Laravel booten → Infra-Dateien einsetzen →
> committen/pushen → Deploy-Keys → Mittwald-App → API-Token → GitHub-Secrets →
> deploy.php anpassen → erster Deploy → Server-`.env` & Migrationen → Scheduler.

---

## 1. Privates GitHub-Repo anlegen

**Variante A – Web:** github.com → *New repository* → Name `ops-cockpit` →
**Private** → ohne README/gitignore (kommen aus dem Projekt) → *Create*.

**Variante B – CLI:**
```bash
gh repo create <GITHUB-ORG>/ops-cockpit --private --confirm
```

> **Privat ist Pflicht** — das Dashboard ist die „Kronjuwele" der Agentur.

---

## 2. Laravel booten und Projektdateien einsetzen

Falls noch nicht geschehen (= Phase A der Roadmap):

```bash
composer create-project laravel/laravel ops-cockpit
cd ops-cockpit
composer require filament/filament:"^3.2"
php artisan filament:install --panels
php artisan install:api          # Laravel 11: aktiviert routes/api.php
```

Dann die App-Dateien aus dem Paket einkopieren (siehe `INSTALL.md`, Abschnitt 2) **und**
die drei Infrastruktur-Dateien aus `repo-setup/` ins Projekt-Root übernehmen:

```bash
cp repo-setup/.gitignore                       ops-cockpit/.gitignore
cp repo-setup/deploy.php                        ops-cockpit/deploy.php
mkdir -p ops-cockpit/.github/workflows
cp repo-setup/.github/workflows/deploy.yml      ops-cockpit/.github/workflows/deploy.yml
```

Deployer + Mittwald-Recipe als Dev-Abhängigkeit:
```bash
composer require --dev deployer/deployer mittwald/deployer-recipes
```

---

## 3. Erst-Commit und Push

```bash
cd ops-cockpit
git init -b main
git add .
git status            # KONTROLLE: .env darf NICHT in der Liste stehen!
git commit -m "Initial: Ops Cockpit MVP + Deploy-Setup"
git remote add origin git@github.com:<GITHUB-ORG>/ops-cockpit.git
git push -u origin main
```

> Erscheint `.env` bei `git status`, sofort stoppen und `.gitignore` prüfen.

**Branch-Disziplin (empfohlen):** `main` = produktiv. Neue Arbeit in Feature-Branches,
per Pull Request zusammenführen → automatisch Code-Review + Audit-Spur.

---

## 4. Deploy-SSH-Schlüsselpaar erzeugen

Ein **eigener** Schlüssel nur fürs Deployment (nicht dein persönlicher):

```bash
ssh-keygen -t ed25519 -C "deploy@ops-cockpit" -f ./ops-deploy-key -N ""
```

Erzeugt `ops-deploy-key` (privat) und `ops-deploy-key.pub` (öffentlich).
Beide brauchst du gleich als GitHub-Secrets. **Den privaten Key danach lokal löschen**,
sobald er als Secret hinterlegt ist.

---

## 5. Mittwald-App anlegen

**Variante A – mStudio UI:** im Cloud-Projekt eine **Custom-App (PHP)** anlegen,
**Document-Root = `/current/public`** (Laravel-Webroot; das `/current`-Symlink legt
Deployer an). App-ID notieren.

**Variante B – CLI:**
```bash
mw app create php --document-root /current/public --site-title "Ops Cockpit (PROD)"
mw app get <APP-ID>      # App-ID / Pfade anzeigen
```

> Notiere die **App-ID** — sie kommt in `deploy.php` und in die GitHub-Secrets.

Den **SSH-Host-Key** für die CI ermitteln (Hostname via `mw project get`):
```bash
ssh-keyscan <SSH-HOSTNAME>     # Ausgabe für Secret MITTWALD_SSH_HOST_KEY aufheben
```

---

## 6. Mittwald-API-Token erzeugen

In mStudio unter **Profil → API-Token** ein Token mit minimal nötigen Rechten erzeugen
(Deploy/App-Verwaltung im betroffenen Projekt). Token sicher kopieren — wird nur einmal angezeigt.

---

## 7. GitHub-Secrets hinterlegen

Repo → **Settings → Secrets and variables → Actions → New repository secret**.
Diese fünf Secrets anlegen:

| Secret | Inhalt |
|---|---|
| `MITTWALD_API_TOKEN` | das API-Token aus Schritt 6 |
| `MITTWALD_APP_ID` | die App-ID aus Schritt 5 |
| `MITTWALD_SSH_PRIVATE_KEY` | Inhalt von `ops-deploy-key` |
| `MITTWALD_SSH_PUBLIC_KEY` | Inhalt von `ops-deploy-key.pub` |
| `MITTWALD_SSH_HOST_KEY` | Ausgabe von `ssh-keyscan` (Schritt 5) |

Per CLI alternativ:
```bash
gh secret set MITTWALD_API_TOKEN
gh secret set MITTWALD_APP_ID
gh secret set MITTWALD_SSH_PRIVATE_KEY  < ops-deploy-key
gh secret set MITTWALD_SSH_PUBLIC_KEY   < ops-deploy-key.pub
ssh-keyscan <SSH-HOSTNAME> | gh secret set MITTWALD_SSH_HOST_KEY
```

---

## 8. deploy.php anpassen

In `deploy.php` die Platzhalter ersetzen:
- `<GITHUB-ORG>` → dein GitHub-Account/Org (Zeile `repository`).
- `<MITTWALD-APP-ID>` → die App-ID aus Schritt 5.
- PHP-Version unter `mittwald_app_dependencies` ggf. an dein Projekt anpassen.

Commit + Push:
```bash
git add deploy.php && git commit -m "Deploy-Ziel konfiguriert" && git push
```

---

## 9. Erster Deploy

**Empfohlen: zuerst lokal testen** (klarere Fehlermeldungen als in der CI):
```bash
export MITTWALD_API_TOKEN=<dein-token>
./vendor/bin/dep deploy \
  -o mittwald_ssh_public_key_file=./ops-deploy-key.pub \
  -o mittwald_ssh_private_key_file=./ops-deploy-key
```

Läuft das durch, übernimmt ab jetzt **jeder Push auf `main`** die GitHub Action
(`.github/workflows/deploy.yml`) automatisch. Manuell auslösbar über den
*Actions*-Tab → *Deploy (Mittwald)* → *Run workflow*.

---

## 10. Server-Konfiguration (einmalig)

Per SSH auf die App, im **shared**-Verzeichnis die `.env` anlegen (bleibt über Deploys bestehen):

```bash
# auf dem Mittwald-Server, im App-Verzeichnis:
cp current/.env.example shared/.env
nano shared/.env            # DB-Zugang, APP_URL=https://dashboard.deineagentur.at, APP_ENV=production, APP_DEBUG=false
php current/artisan key:generate --force     # ERZEUGT APP_KEY -> sichern!
php current/artisan migrate --force
php current/artisan db:seed --class=Database\\Seeders\\DemoSeeder   # optional
```

> **APP_KEY sichern** (z. B. Passwortmanager der Agentur). Ohne ihn sind alle
> verschlüsselten Secrets verloren.

**Admin-Login** sofort ändern bzw. eigenen anlegen: `php current/artisan make:filament-user`.

**Scheduler-Cron** in mStudio einrichten (jede Minute):
```
* * * * * cd /current && php artisan schedule:run >> /dev/null 2>&1
```
Damit greifen `sites:heartbeat-sweep` (alle 15 Min) und `sites:expiry-scan` (täglich 06:30).

---

## 11. Funktionstest

```bash
php current/artisan test --filter=IngestEndpointTest
```
Plus: im Cockpit einloggen, Demo-Sites mit Ampel sichtbar? Ein signierter Test-Push
erzeugt Snapshot/Status/Task?

---

## 12. Sicherheits-Checkliste (vor „fertig")

- [ ] Repo ist **privat**.
- [ ] `.env` ist **nicht** im Repo (`git ls-files | grep .env` → leer).
- [ ] `APP_KEY` sicher außerhalb des Repos gesichert.
- [ ] Deploy-SSH-Key ist ein **eigener** Key; privater Key lokal gelöscht, nur als Secret vorhanden.
- [ ] API-Token mit **minimalen** Rechten.
- [ ] `APP_DEBUG=false`, `APP_ENV=production` auf dem Server.
- [ ] HTTPS erzwungen; eigene Subdomain (`dashboard.deineagentur.at`), 2FA aktiv.
- [ ] Migrationen im Deploy sind **nicht-destruktiv** (sonst bewusst & angekündigt).

---

## Wie geht es weiter?

Steht die Infrastruktur, beginnt **Phase B (Dashboard-Redesign)** aus der `Roadmap.md` —
ab dann arbeitet jede Änderung sauber über Branch → PR → automatischer Deploy.

**Quellen (Mittwald):**
- Deployer-Guide: https://developer.mittwald.de/docs/v2/guides/deployment/deployer/
- Deployer-Recipes: https://github.com/mittwald/deployer-recipes
