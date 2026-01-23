# REDAXO eRecht24 Rechtstexte

Dieses Addon ermöglicht die einfache Integration von Rechtstexten (Impressum, Datenschutzerklärung) aus dem eRecht24 Projekt Manager in REDAXO.

☝️ Dieses Addon wurde nicht von eRecht24 entwickelt und wird auch nicht von eRecht24 supportet. 

Für Fragen und Hilfe zum AddOn bitte in [Slack](https://redaxo.org/support/slack/) melden.
Bei Fehlern und technischen Problemen bitte Issue bei GitHub anlegen. 

## Über eRecht24 
eRecht24 ist ein deutscher Anbieter bekannt für sein Angebot für rechtssichere Texte, insbesondere für Impressum und Datenschutzerklärungen, die speziell für Webseitenbetreiber, Online-Shops und Unternehmen erstellt werden. Die Plattform bietet einen Projekt-Manager, mit dem Nutzer individuelle Rechtstexte generieren und automatisch aktualisieren lassen können. Weitere Dienste und Tutorials komplettieren das Angebot auch abseits des Webs. 

Mehr Informationen unter: [https://www.e-recht24.de](https://www.e-recht24.de)

## Features
- Aktualisierung der Texte via Push API von eRecht24 (Sync-Button)
- Unterstützung mehrerer Domains
- Registrierung über API-Key bei eRecht24
- Löschen und Deregistrierung der Domain inkl. Löschen der Texte lokal. 
- Mehrsprachige Texte (DE/EN)
- Einfache Integration via PHP-Methoden
- Nutzung der eRecht24 SDK
- Objektorientiertes Design mit Namespace-Support


## Installation
1. Im REDAXO Installer das Addon "erecht24" herunterladen.
2. Addon installieren und aktivieren.

## Einrichtung
1. Im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/) ein neues Projekt anlegen.
2. Texte für Impressum und Datenschutzerklärung über eRecht24 erstellen.
3. API-Schlüssel über das Zahnradsymbol des Projekts generieren.
4. In REDAXO unter eRecht24 Rechtstexte > Einstellungen:
   - Domain eintragen
   - API-Schlüssel einfügen
   - Speichern

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
    echo 'Fehler bei der Registrierung: ' . $e->getMessage();
}

// Domain entfernen
try {
    eRecht24Client::unregister('example.com');
    echo 'Domain erfolgreich entfernt';
} catch (rex_exception $e) {
    echo 'Fehler beim Entfernen: ' . $e->getMessage();
}
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

## Texte aktualisieren
Die Texte werden via Push-API von eRecht24 aktualisiert, sobald sie im eRecht24 Projekt Manager geändert werden und Sync gedrückt wurde.

## Mehrere Domains
Das Addon unterstützt mehrere Domains. Jede Domain benötigt:
1. Ein eigenes Projekt im eRecht24 Projekt Manager
2. Einen eigenen API-Schlüssel

## Rechtliche Hinweise
Die API und das SDK von eRecht24 unterliegen den API-Nutzungsbedingungen von eRecht24 GmbH & Co. KG. Weitere Informationen zur API-Nutzung finden sich im Vendor-Ordner.
REDAXO-Code-Bestandteile fallen unter MIT-Lizenz. API-spezifischer Code fällt unter der eRecht24 Lizenz.

## Autor
**Friends Of REDAXO**
* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

**Projektleitung**
[Thomas Skerbis](https://github.com/skerbis)

**Sponsors**
- [KLXM Crossmedia GmbH](https://klxm.de)
- [Marco Hanke](https://github.com/marcohanke)

**Dank an:**
[https://www.e-recht24.de](https://www.e-recht24.de)
