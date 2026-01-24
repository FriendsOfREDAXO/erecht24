# REDAXO eRecht24 Rechtstexte

Dieses Addon ermöglicht die sichere und einfache Integration von Rechtstexten (Impressum, Datenschutzerklärung) aus dem eRecht24 Projekt Manager in REDAXO.

## 📋 Inhaltsverzeichnis

- [Über eRecht24](#über-erecht24)
- [Features](#features)
- [Installation](#installation)
- [Einrichtung](#einrichtung)
- [Verwendung](#verwendung)
  - [PHP-API](#text-typen-prüfen-und-ausgeben)
  - [Outputfilter](#outputfilter)
- [Sicherheit](#sicherheit)
- [Performance](#performance)
- [Mehrere Domains](#mehrere-domains)
- [Support](#support)

## ⚠️ Hinweis

Dieses Addon wurde nicht von eRecht24 entwickelt und wird auch nicht von eRecht24 supportet. 

**Support:**
- Fragen und Hilfe zum AddOn: [REDAXO Slack](https://redaxo.org/support/slack/)
- Fehler und technische Probleme: [GitHub Issues](https://github.com/FriendsOfREDAXO/erecht24/issues) 

## Über eRecht24 
eRecht24 ist ein deutscher Anbieter bekannt für sein Angebot für rechtssichere Texte, insbesondere für Impressum und Datenschutzerklärungen, die speziell für Webseitenbetreiber, Online-Shops und Unternehmen erstellt werden. Die Plattform bietet einen Projekt-Manager, mit dem Nutzer individuelle Rechtstexte generieren und automatisch aktualisieren lassen können. Weitere Dienste und Tutorials komplettieren das Angebot auch abseits des Webs. 

Mehr Informationen unter: [https://www.e-recht24.de](https://www.e-recht24.de)

## ✨ Features

- ✅ **Push-API Integration**: Automatische Aktualisierung via eRecht24 Push API
- ✅ **Multi-Domain Support**: Verwaltung mehrerer Domains mit eigenen Rechtstexten
- ✅ **Mehrsprachigkeit**: Unterstützung für DE/EN Rechtstexte
- ✅ **Flexible Integration**: PHP-API und automatische Outputfilter-Platzhalter
- ✅ **Sicherheit**: CSRF-Schutz, Input-Validierung, Secret-basierte API-Authentifizierung
- ✅ **Performance**: Caching, optimierte Datenbankabfragen, Early-Exit-Patterns
- ✅ **Einfache Verwaltung**: Backend-Oberfläche für Domain-Registrierung und Vorschau
- ✅ **SDK-basiert**: Nutzung der offiziellen eRecht24 SDK
- ✅ **Modern**: PHP 8.2+, Strict Types, objektorientiertes Design mit Namespaces


## 📦 Installation

### Systemanforderungen
- REDAXO >= 5.18
- PHP >= 8.2
- PHP-Extensions: `curl`, `json`

### Installationsschritte
1. Im REDAXO Installer das Addon **"erecht24"** herunterladen
2. Addon **installieren** und **aktivieren**

## ⚙️ Einrichtung

### eRecht24 Projekt erstellen
1. Im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/) neues Projekt anlegen
2. Rechtstexte für Impressum und Datenschutzerklärung erstellen
3. **API-Schlüssel** über das Zahnradsymbol des Projekts generieren

### REDAXO Konfiguration
1. Im Backend zu **eRecht24 > Einstellungen** navigieren
2. **Domain** eintragen (z.B. `example.com`)
3. **API-Schlüssel** einfügen
4. Speichern und auf **"Test"** klicken, um die Verbindung zu prüfen

## Verwendung

### Namespace
Das Addon verwendet den Namespace `FriendsOfRedaxo\eRecht24`. Für die Verwendung der Klassen muss dieser importiert werden:

```php
use FriendsOfRedaxo\eRecht24\eRecht24;
use FriendsOfRedaxo\eRecht24\eRecht24Client;
```

### Text-Typen prüfen und ausgeben
Die Rechtstexte werden über eine einheitliche PHP-Schnittstelle eingebunden. Es können wahlweise die Domain (string) oder die ID (int) zum Abruf verwendet werden.

```php
use FriendsOfRedaxo\eRecht24\eRecht24;

$id = 1;
// Prüfen, ob Text vorhanden ist
if (eRecht24::hasText($id, 'imprint')) {
    // Text ausgeben
    echo eRecht24::getText($id, 'imprint');
}

// Auswahl nach Domain
if (eRecht24::hasText('domain.tld', 'imprint')) {
    // Text ausgeben
    echo eRecht24::getText('domain.tld', 'imprint');
}
```

### Verfügbare Text-Typen
- `imprint` - Impressum
- `privacyPolicy` - Datenschutzerklärung
- `privacyPolicySocialMedia` - Datenschutzerklärung Social Media

### Sprache wählen
```php
use FriendsOfRedaxo\eRecht24\eRecht24;

// Deutsche Version (Standard) hier mit Abruf per Domain 
echo eRecht24::getText('domain.tld', 'imprint', 'de');

$id = 1;
// Englische Version
echo eRecht24::getText($id, 'imprint', 'en');

// Prüfen, ob englische Version existiert
if (eRecht24::hasText($id, 'imprint', 'en')) {
    echo eRecht24::getText($id, 'imprint', 'en');
}
```

### Integration in Module 
```php
use FriendsOfRedaxo\eRecht24\eRecht24;

$domain = 'example.com';

// Impressum einbinden
if (eRecht24::hasText($domain, 'imprint')) {
    echo '<div class="legal-text imprint">';
    echo eRecht24::getText($domain, 'imprint');
    echo '</div>';
}

// Datenschutzerklärung mit Sprachauswahl
$language = rex_clang::getCurrentId() == 1 ? 'en' : 'de';
if (eRecht24::hasText($domain, 'privacyPolicy', $language)) {
    echo '<div class="legal-text privacy">';
    echo eRecht24::getText($domain, 'privacyPolicy', $language);
    echo '</div>';
}
```

> Tipp: Als Platzhalter im Outputfilter verwenden.

## Outputfilter

Das Addon bietet einen automatischen Outputfilter, der Platzhalter im Frontend und Backend ersetzt. Der Filter ist **automatisch aktiv** nach der Installation und funktioniert sowohl im Frontend als auch im Backend (außer im Edit-Modus des Structure Content Plugins).

### Platzhalter-Syntax

```
##ER-{TYPE}:{DOMAIN}:{LANG}##
```

**Parameter:**
- `TYPE`: Der Typ des Rechtstextes
  - `PRIVACY` - Datenschutzerklärung
  - `IMPRINT` - Impressum
  - `PRIVACY-SOCIAL` - Datenschutzerklärung Social Media
- `DOMAIN`: Die registrierte Domain (z.B. `example.com`)
- `LANG`: Sprache (`de` oder `en`)

### Beispiele

```html
<!-- Datenschutzerklärung -->
##ER-PRIVACY:example.com:de##

<!-- Impressum -->
##ER-IMPRINT:example.com:de##

<!-- Datenschutz Social Media auf Englisch -->
##ER-PRIVACY-SOCIAL:example.com:en##

<!-- In Modulen oder Templates -->
<div class="legal-text">
    <h2>Datenschutzerklärung</h2>
    ##ER-PRIVACY:example.com:de##
</div>

<footer>
    ##ER-IMPRINT:example.com:de##
</footer>
```

### Verfügbare Platzhalter anzeigen

Unter **eRecht24 > Einstellungen** werden automatisch alle verfügbaren Platzhalter für deine registrierten Domains angezeigt. Diese kannst du direkt kopieren und verwenden.

### Verhalten

- **Frontend**: Platzhalter werden immer ersetzt
- **Backend**: Platzhalter werden ersetzt (außer im Edit-Modus von Structure Content)
- **Debug-Modus**: Bei fehlenden Texten wird ein HTML-Kommentar eingefügt
- **Produktion**: Bei fehlenden Texten wird ein leerer String zurückgegeben

### Verwendung in REDAXO

Der Outputfilter kann überall verwendet werden:
- In Templates
- In Modulen
- In YForm-Ausgaben
- In beliebigen HTML-Bereichen


### Programmatische Verwaltung
```php
use FriendsOfRedaxo\eRecht24\eRecht24Client;

// Neue Domain registrieren
try {
    eRecht24Client::register('example.com', 'your-api-key');
    echo 'Domain erfolgreich registriert';
} catch (rex_exception $e) {
    echo 'Fehler bei der Registrierung: ' . rex_escape($e->getMessage());
}

// Domain entfernen
try {
    eRecht24Client::unregister('example.com');
    echo 'Domain erfolgreich entfernt';
} catch (rex_exception $e) {
    echo 'Fehler beim Entfernen: ' . rex_escape($e->getMessage());
}
```

## 🔒 Sicherheit

Das Addon implementiert mehrere Sicherheitsebenen:

### CSRF-Schutz
- Alle Backend-Formulare sind durch CSRF-Tokens geschützt
- DELETE-Operationen erfordern Token-Validierung

### Input-Validierung
- **Domain-Validierung**: Nur gültige Domain-Formate werden akzeptiert
- **API-Key-Validierung**: Mindestlänge und Format-Prüfung
- **Sprach-Parameter**: Whitelist-Validierung (nur `de` und `en`)
- **Text-Typen**: Validierung gegen definierte Typen-Liste

### API-Sicherheit
- **Secret-basierte Authentifizierung**: Push-API verwendet eindeutige Secrets
- **Format-Validierung**: Alphanumerische Secrets, validierte Type-Parameter
- **Keine CSRF-Protection**: API-Endpoint ist für externe Webhooks konzipiert

### Ausgabe-Sicherheit
- `rex_escape()` für alle Benutzereingaben im Backend
- `htmlspecialchars()` mit `ENT_QUOTES` für Debug-Ausgaben
- Prepared Statements für alle Datenbankabfragen

### Logging
- Fehler werden via `rex_logger` protokolliert
- Sensitive Daten (API-Keys, Secrets) werden nicht geloggt
- Debug-Modus kann aktiviert werden (nur für Entwicklung)

## ⚡ Performance

### Optimierungen
- **Outputfilter Early-Exit**: Prüfung auf `##ER-` vor Regex-Ausführung
- **Static Caching**: Platzhalter-Ersetzungen werden pro Request gecached
- **Optimierte Regex**: Eingeschränkte Domain-Pattern für schnellere Matches
- **Indizierte Datenbank**: Unique-Index auf `domain` und `domain+type`
- **Lazy Loading**: Texte werden nur bei Bedarf geladen

### Best Practices
```php
// Performance: Prüfen vor Abruf
if (eRecht24::hasText($domain, 'imprint')) {
    echo eRecht24::getText($domain, 'imprint');
}

// Outputfilter: Nur aktivieren wenn benötigt
// Backend: eRecht24 > Einstellungen > Outputfilter
```

## Klassen-Referenz

### eRecht24Client
Diese Klasse handhabt die Kommunikation mit der eRecht24 API.

```php
namespace FriendsOfRedaxo\eRecht24;

class eRecht24Client 
{
    // Plugin Key für die API-Authentifizierung
    public const PLUGIN_KEY = '...';
    
    // Debug-Modus ein-/ausschalten
    public const DEBUG = false;
    
    /**
     * Registriert eine neue Domain mit eRecht24
     *
     * @param string $domain Die zu registrierende Domain
     * @param string $apiKey Der API-Schlüssel von eRecht24
     * @throws rex_exception Bei Fehlern während der Registrierung
     * @return void
     */
    public static function register(string $domain, string $apiKey): void;
    
    /**
     * Entfernt eine Domain aus eRecht24 und der lokalen Datenbank
     *
     * @param string $domain Die zu entfernende Domain
     * @return void
     */
    public static function unregister(string $domain): void;
}
```

## 🔄 Texte aktualisieren

Die Texte werden automatisch via **Push-API** von eRecht24 aktualisiert:

1. Texte im eRecht24 Projekt Manager ändern
2. Auf **"Sync"** klicken
3. eRecht24 sendet die Änderungen automatisch an REDAXO
4. Texte sind sofort verfügbar

**Manuelle Synchronisation:**
- Im Backend unter **eRecht24 > Einstellungen**
- Auf **"Test"** bei der gewünschten Domain klicken

## 🌐 Mehrere Domains

Das Addon unterstützt die Verwaltung mehrerer Domains:

### Anforderungen pro Domain
1. Eigenes Projekt im eRecht24 Projekt Manager
2. Eigener API-Schlüssel
3. Individuelle Rechtstexte

### Konfiguration
- Jede Domain wird separat im Backend registriert
- Domains werden über ihre URL identifiziert
- Verschiedene Domains können unterschiedliche Texte haben

```php
// Domain-spezifischer Abruf
echo eRecht24::getText('domain1.com', 'imprint');
echo eRecht24::getText('domain2.com', 'imprint');

// Platzhalter pro Domain
##ER-PRIVACY:domain1.com:de##
##ER-PRIVACY:domain2.com:de##
```

## 🐛 Debugging

### Debug-Modus aktivieren
```php
// In lib/eRecht24Client.php
public const DEBUG = true;
```

### Was wird geloggt?
- Push-API Requests und Responses
- Datenbank-Operationen
- API-Fehler von eRecht24
- Platzhalter-Ersetzungen (wenn nicht gefunden)

### Log-Dateien
Logs finden sich im REDAXO System-Log unter **System > Logdateien**.

## 📝 Changelog

### Version 1.1.0
- ✅ Sicherheitsverbesserungen: Input-Validierung, CSRF-Schutz für DELETE
- ✅ Performance-Optimierungen: Caching, Early-Exit-Patterns
- ✅ Domain-Validierung mit Regex
- ✅ Verbesserte Fehlerbehandlung und Logging
- ✅ Erweiterte README-Dokumentation

## ⚖️ Rechtliche Hinweise

Die API und das SDK von eRecht24 unterliegen den **API-Nutzungsbedingungen** von eRecht24 GmbH & Co. KG. 

**Lizenzen:**
- REDAXO-Code: MIT-Lizenz
- eRecht24 SDK: eRecht24 Lizenz (siehe `vendor/`)

Weitere Informationen zur API-Nutzung finden sich im Vendor-Ordner.

## 👥 Credits

**Entwicklung**
- [Friends Of REDAXO](https://github.com/FriendsOfREDAXO)

**Projektleitung**
- [Thomas Skerbis](https://github.com/skerbis)

**Sponsors**
- [KLXM Crossmedia GmbH](https://klxm.de)
- [Marco Hanke](https://github.com/marcohanke)

**Danke an**
- [eRecht24](https://www.e-recht24.de) für die API und SDK

## 🔗 Links

- [REDAXO Website](https://redaxo.org)
- [GitHub Repository](https://github.com/FriendsOfREDAXO/erecht24)
- [REDAXO Slack](https://redaxo.org/support/slack/)
- [eRecht24 Website](https://www.e-recht24.de)
