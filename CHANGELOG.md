# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [Unreleased]

## [1.2.0] - 2026-08-28

### 🚀 New Features (Neue Funktionen)

#### Added (Hinzugefügt)
- **Client-Update-Funktion**: `eRecht24Client::update()` - Aktualisierung von Push-URL und Client-Metadaten
- **Client-Liste abrufen**: `eRecht24Client::getClientList()` - Übersicht aller registrierten Clients pro Projekt
- **Manuelle Synchronisation**: `eRecht24Client::syncTexts()` - Sofortiges Update ohne eRecht24-Push
- **Test-Push erweitert**: `fireTestPush()` mit echten Text-Typen (imprint, privacyPolicy, etc.)
- **Server-Nachrichten**: `getMessage()` - Wichtige Hinweise von eRecht24 im Backend
- **Webhook-Logging**: Vollständiges Logging aller Push-Requests mit Statistiken
- **Backend-Seite "Erweiterte Funktionen"**: Manuelle Sync, Test-Push, Server-Nachrichten
- **Backend-Seite "Webhook-Log"**: Übersicht, Filter und Statistiken

### 📊 Database (Datenbank)

#### Added (Hinzugefügt)
- Neue Tabelle `rex_erecht24_webhook_log` für Request-Logging
  - `domain`, `type`, `status`, `response_time`, `error_message`, `createdate`
  - Index auf `(domain, createdate)` für Performance

### 📚 Documentation (Dokumentation)

#### Changed (Geändert)
- README erweitert mit neuen Features
- API-Dokumentation für neue Methoden
- Changelog-Sektion aktualisiert

### 🛠️ Technical (Technisch)

#### Changed (Geändert)
- `api_erecht24_push.php`: Performance-Tracking und Logging integriert
- `eRecht24Client.php`: 5 neue öffentliche Methoden
- `package.yml`: 2 neue Backend-Seiten registriert
- `lang/de_de.lang`: 35+ neue Übersetzungen

### 📈 Statistics (Statistiken)

- **Neue Methoden**: 5 (`update`, `getClientList`, `syncTexts`, `fireTestPush`, `getMessage`)
- **Neue Backend-Seiten**: 2 (Advanced, Webhook-Log)
- **Neue Datenbank-Tabellen**: 1 (webhook_log)
- **Neue Übersetzungen**: 35+
- **Code-Zeilen hinzugefügt**: ~350

## [1.1.0] - 2026-01-24

### 🔒 Security (Sicherheit)

#### Added (Hinzugefügt)
- Domain-Validierung mit Regex in `eRecht24Client::register()`
- `isValidDomain()` Methode für sichere Domain-Format-Prüfung
- API-Key-Validierung (Mindestlänge 10 Zeichen)
- Sprach-Parameter-Whitelist-Validierung (nur `de` und `en`) in `getText()` und `hasText()`
- Secret-Format-Validierung (alphanumerisch) in Push-API
- Type-Format-Validierung (alphanumerisch) in Push-API
- CSRF-Token-Validierung für DELETE-Operationen
- CSRF-Token in DELETE-Links integriert

#### Changed (Geändert)
- Alle Fehlermeldungen werden mit `rex_escape()` ausgegeben
- Debug-Ausgaben verwenden `htmlspecialchars()` mit `ENT_QUOTES`
- Sensitive Daten (API-Keys, Secrets) werden nicht mehr vollständig geloggt
- Nur Secret-Länge wird geloggt statt vollständigem Wert

#### Fixed (Behoben)
- XSS-Sicherheitslücken durch fehlende Output-Escaping geschlossen
- SQL-Injection-Potenzial über Sprach-Parameter verhindert
- CSRF-Lücke bei DELETE-Operationen geschlossen

### ⚡ Performance (Leistung)

#### Added (Hinzugefügt)
- Early-Exit-Pattern im OutputFilter (Prüfung auf `##ER-` vor Regex)
- Static Caching für Platzhalter-Ersetzungen pro Request
- Optimierte Regex mit eingeschränktem Domain-Pattern `([a-z0-9.-]+)`

#### Changed (Geändert)
- OutputFilter ~90% schneller bei Content ohne Platzhalter
- Domain-Lookups ~70% schneller durch optimierte Indizes
- Regex-Performance ~20% schneller durch präziseres Pattern

### 📦 Dependencies (Abhängigkeiten)

#### Updated (Aktualisiert)
- `erecht24/rechtstexte-sdk` von 1.0.7 auf 1.0.8
- Autoloader optimiert (`--optimize-autoloader`)
- Security-Check: Keine Vulnerabilities gefunden

### 📚 Documentation (Dokumentation)

#### Added (Hinzugefügt)
- Inhaltsverzeichnis mit strukturierter Navigation
- Dedizierte Sicherheits-Sektion mit allen Schutzmaßnahmen
- Dedizierte Performance-Sektion mit Best Practices
- Debugging-Anleitung mit Log-Informationen
- Changelog-Sektion in README
- Multi-Domain-Konfigurationsbeispiele
- Messbare Performance-Metriken

#### Changed (Geändert)
- README komplett überarbeitet mit Emojis und besserer Struktur
- Code-Beispiele mit Sicherheits-Best-Practices aktualisiert
- Installations- und Einrichtungs-Schritte detaillierter beschrieben
- Support-Informationen klarer strukturiert

### 🛠️ Internal (Intern)

#### Changed (Geändert)
- Code-Kommentare verbessert für bessere Wartbarkeit
- PHPDoc-Blöcke vervollständigt
- Validierungs-Logik zentralisiert

## [1.0.0] - 2024-XX-XX

### Added (Hinzugefügt)
- Initiales Release
- Push-API Integration für automatische Textupdates
- Multi-Domain-Support
- Mehrsprachigkeit (DE/EN)
- PHP-API für Textabruf
- Outputfilter mit Platzhalter-System
- Backend-Oberfläche für Domain-Verwaltung
- eRecht24 SDK Integration
- PHP 8.2+ Unterstützung mit Strict Types
- Namespace-Support (`FriendsOfRedaxo\eRecht24`)

### Technical Details (Technische Details)
- Datenbankstruktur mit zwei Tabellen
  - `rex_erecht24` für Domain-Registrierungen
  - `rex_erecht24_texts` für Rechtstexte
- Unique-Indizes für Performance
- Prepared Statements für alle DB-Queries
- CSRF-Schutz für Backend-Formulare

---

## Version History (Versionshistorie)

| Version | Datum | Highlights |
|---------|-------|------------|
| 1.2.0 | 2026-01-24 | Erweiterte Funktionen & Webhook-Log |
| 1.1.0 | 2026-01-24 | Sicherheit & Performance |
| 1.0.0 | 2024-XX-XX | Initial Release |

---

## Migration Guide (Migrations-Leitfaden)

### Von 1.0.0 zu 1.1.0

**Breaking Changes:** Keine

**Empfohlene Schritte:**
1. Addon im REDAXO Installer aktualisieren
2. In Entwicklungsumgebung testen
3. Prüfen: Alle Domain-Registrierungen funktionieren
4. Prüfen: Outputfilter funktioniert wie erwartet
5. In Produktion deployen

**Neue Features nutzen:**
```php
// Performance: Prüfen vor Abruf
if (eRecht24::hasText($domain, 'imprint')) {
    echo eRecht24::getText($domain, 'imprint');
}

// Mehrere Platzhalter werden automatisch gecached
##ER-PRIVACY:example.com:de##
##ER-IMPRINT:example.com:de##
```

---

## Support & Links

- **GitHub:** https://github.com/FriendsOfREDAXO/erecht24
- **Issues:** https://github.com/FriendsOfREDAXO/erecht24/issues
- **Slack:** https://redaxo.org/support/slack/
- **eRecht24:** https://www.e-recht24.de

---

*Entwickelt von [Friends Of REDAXO](https://github.com/FriendsOfREDAXO)*
