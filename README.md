# Maidem Umami Dashboard

TYPO3 14, Dashboard-Widgets (`typo3/cms-dashboard`) für [Umami](https://umami.is)-Web-Analytics. Composer-Paket `maidem/umami-dashboard`, Extension-Key `umami_dashboard`.

## Widgets

Alle Widgets zeigen die letzten 14 Tage und stehen in der Dashboard-Gruppe „System-Info":

- **Besucher** (`umamiVisitors`) – Zahlen-Kachel
- **Seitenaufrufe** (`umamiPageviews`) – Zahlen-Kachel
- **Umami Verlauf** (`umamiPageviewsChart`) – Balkendiagramm, Seitenaufrufe und Besucher pro Tag
- **Besucher nach Land** (`umamiCountries`) – Top 10 Länder mit Flagge, z. B. „🇩🇪 Deutschland – 812"

## Voraussetzungen

- TYPO3 14.1+, PHP 8.4+ mit `intl` und `mbstring`
- `typo3/cms-dashboard`
- Erreichbare Umami-Instanz (Self-Hosted) mit Benutzer und Website-ID

## Setup

```bash
composer config repositories.umami-dashboard vcs git@github.com:maidem/umami_dashboard.git
composer require maidem/umami-dashboard
```

Danach im Backend unter **Dashboard → Widget hinzufügen** die Umami-Widgets wählen.

## Konfiguration

Erweiterungskonfiguration `umami_dashboard` (**Einstellungen → Erweiterungskonfiguration**) oder Umgebungsvariablen, die Vorrang haben:

| Extension-Konfiguration | Umgebungsvariable | Beispiel |
| --- | --- | --- |
| `host` | `UMAMI_HOST` | `https://log.example.com` |
| `websiteId` | `UMAMI_WEBSITE_ID` | UUID aus Umami (Einstellungen → Websites → Bearbeiten) |
| `username` | `UMAMI_USERNAME` | eigener Nur-Lese-Benutzer |
| `password` | `UMAMI_PASSWORD` | nur per Env-Variable setzen |

Sind Angaben unvollständig oder die API nicht erreichbar, zeigen die Widgets 0 bzw. eine leere Liste.

## Technik

- `Classes/Service/UmamiStatisticService.php` – Login per `POST /api/auth/login`, danach `/stats`, `/pageviews` und `/metrics?type=country`. Das Ergebnis wird 5 Minuten im Cache `umami_dashboard` gehalten (`ext_localconf.php`).
- `Classes/Dashboard/*DataProvider.php` – bereiten die Daten für `NumberWithIconWidget`, `BarChartWidget` und `ListWidget` auf.
- `Configuration/Services.yaml` – Widget-Registrierung
- `Resources/Private/Language/locallang.xlf` – Widget-Labels
