# VWS Loginverwaltung – PHP-Prototyp

Ein einfacher PHP-Prototyp mit CRUD-Funktionen und JSON-Datei statt Datenbank.

## Enthaltene Funktionen
- Einträge anzeigen
- Einträge anlegen, bearbeiten und löschen
- Suche über System, Benutzername, URL und Bemerkung
- Responsive Oberfläche mit Bootstrap 5
- Speicherung in `logins.json`

## Sicherheit
Diese Anwendung hat absichtlich **keinen Login**. Wenn sie öffentlich im Internet erreichbar ist, kann grundsätzlich jeder Besucher Einträge lesen, anlegen, ändern oder löschen.
- Keine echten Passwörter oder vertraulichen Zugangsdaten eintragen.
- Vor produktiver Nutzung unbedingt Zugriffsschutz (z. B. Entra ID / App Service Authentication) ergänzen.
- Die Anwendung ist ein Lern-/Funktionsprototyp, kein Passwortmanager.

## Lokal testen
Voraussetzung: PHP 8.1 oder neuer.

```bash
php -S localhost:8000
```

Dann `http://localhost:8000` öffnen.

## Auf Azure App Service (Linux) deployen
1. Dateien in dein GitHub-Repository kopieren.
2. Commit auf den verbundenen Branch pushen.
3. GitHub Actions veröffentlicht die Dateien.
4. App-Service-URL öffnen.

Standardmäßig verwendet die Anwendung unter Azure `/home/data` als Speicherort. Das `/home`-Verzeichnis ist bei App Service Linux für persistente App-Dateien vorgesehen. Optional kann die App-Einstellung `APP_STORAGE_PATH` gesetzt werden.

## Projektstruktur
```text
.
├── index.php
├── assets/
│   └── style.css
├── data/
│   └── .gitkeep
├── .gitignore
└── README.md
```

## Hinweis zu Bootstrap
Bootstrap CSS wird über jsDelivr geladen. Für einen Betrieb ohne externe CDN-Abhängigkeit kann Bootstrap lokal eingebunden werden.
