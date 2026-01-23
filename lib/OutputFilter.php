<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\eRecht24;

use rex;
use rex_extension;
use rex_extension_point;
use rex_logger;
use rex_request;

use function in_array;

/**
 * Outputfilter für eRecht24 Rechtstexte
 * Ersetzt Platzhalter wie ##ER-PRIVACY:1:de## mit den entsprechenden Rechtstexten.
 */
class OutputFilter
{
    /**
     * Registriert den Outputfilter.
     */
    public static function register(): void
    {
        rex_extension::register('OUTPUT_FILTER', [self::class, 'filter']);
    }

    /**
     * Filtert den Output und ersetzt eRecht24 Platzhalter.
     *
     * @param rex_extension_point<string> $ep
     */
    public static function filter(rex_extension_point $ep): string
    {
        $content = $ep->getSubject();

        // Debug-Logging
        if (rex::isDebugMode()) {
            rex_logger::factory()->debug('eRecht24 Outputfilter aufgerufen');
            if (str_contains($content, '##ER-')) {
                rex_logger::factory()->debug('eRecht24 Platzhalter gefunden im Content');
            }
        }

        // Nicht im Edit-Modus des Structure Content Plugins filtern
        if (self::isStructureEditMode()) {
            if (rex::isDebugMode()) {
                rex_logger::factory()->debug('eRecht24 Outputfilter: Structure Edit-Modus erkannt, überspringe Filter');
            }
            return $content;
        }

        // Suche nach allen eRecht24 Platzhaltern
        // Format: ##ER-{TYPE}:{DOMAIN}:{LANG}##
        // Beispiele: ##ER-PRIVACY:example.com:de##, ##ER-IMPRINT:example.com:en##
        $pattern = '/##ER-(PRIVACY|IMPRINT|PRIVACY-SOCIAL):([^:]+):([a-z]{2})##/i';

        $result = preg_replace_callback($pattern, [self::class, 'replacePlaceholder'], $content);

        if (rex::isDebugMode() && $result !== $content) {
            rex_logger::factory()->debug('eRecht24 Outputfilter: Platzhalter ersetzt');
        }

        return $result;
    }

    /**
     * Ersetzt einen einzelnen Platzhalter.
     *
     * @param array<int, string> $matches
     */
    private static function replacePlaceholder(array $matches): string
    {
        $typeShort = strtoupper($matches[1]);
        $identifier = $matches[2];
        $lang = strtolower($matches[3]);

        if (rex::isDebugMode()) {
            rex_logger::factory()->debug('eRecht24 Platzhalter gefunden: ' . $matches[0]);
            rex_logger::factory()->debug('Type: ' . $typeShort . ', Identifier: ' . $identifier . ', Lang: ' . $lang);
        }

        // Konvertiere Kurzform zu vollständigem Typ
        $type = self::convertType($typeShort);

        if (null === $type) {
            // Ungültiger Typ - gib Platzhalter unverändert zurück
            if (rex::isDebugMode()) {
                rex_logger::factory()->debug('eRecht24: Ungültiger Typ: ' . $typeShort);
            }
            return $matches[0];
        }

        // Hole den Text aus der Datenbank
        $text = eRecht24::getText($identifier, $type, $lang);

        // Wenn kein Text gefunden wurde, gib einen Kommentar zurück (für Entwicklung)
        // oder leeren String (für Produktion)
        if (null === $text || '' === $text) {
            if (rex::isDebugMode()) {
                rex_logger::factory()->debug('eRecht24: Kein Text gefunden für ' . $identifier . ' / ' . $type . ' / ' . $lang);
                return '<!-- eRecht24: Kein Text gefunden für ' . htmlspecialchars($matches[0]) . ' -->';
            }
            return '';
        }

        if (rex::isDebugMode()) {
            rex_logger::factory()->debug('eRecht24: Text erfolgreich ersetzt für ' . $matches[0]);
        }

        return $text;
    }

    /**
     * Konvertiert die Kurzform des Typs zur vollständigen Form.
     */
    private static function convertType(string $typeShort): ?string
    {
        $mapping = [
            'PRIVACY' => 'privacyPolicy',
            'IMPRINT' => 'imprint',
            'PRIVACY-SOCIAL' => 'privacyPolicySocialMedia',
        ];

        return $mapping[$typeShort] ?? null;
    }

    /**
     * Prüft ob wir uns im Edit-Modus des Structure Content Plugins befinden.
     */
    private static function isStructureEditMode(): bool
    {
        // Im Frontend sind wir nie im Edit-Modus
        if (!rex::isBackend()) {
            return false;
        }

        // Prüfe ob wir in der Structure-Sektion sind
        $page = rex_request::get('page', 'string', '');
        $function = rex_request::get('function', 'string', '');

        // Edit-Modus wenn:
        // - Wir auf der content Seite sind
        // - UND eine edit/add Funktion aufgerufen wird
        // - ODER ein article_id Parameter vorhanden ist (Edit-Modus)
        if ('content' === $page || 'content/edit' === $page) {
            if (in_array($function, ['edit', 'add'], true)) {
                return true;
            }
            if (rex_request::get('article_id', 'int', 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
