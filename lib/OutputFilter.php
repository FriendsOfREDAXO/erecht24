<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\eRecht24;

use rex;
use rex_config;
use rex_extension;
use rex_extension_point;
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

        // Pr\u00fcfe ob der Outputfilter aktiviert ist
        if (!rex_config::get('erecht24', 'outputfilter_enabled', true)) {
            return $content;
        }

        // Im Backend nur im Content-Vorschaumodus filtern, sonst nirgendwo
        if (rex::isBackend() && !self::isContentPreviewMode()) {
            return $content;
        }

        // Performance: Prüfe ob Platzhalter vorhanden sind bevor Regex läuft
        if (false === strpos($content, '##ER-')) {
            return $content;
        }

        // Suche nach allen eRecht24 Platzhaltern
        // Format: ##ER-{TYPE}:{DOMAIN}:{LANG}##
        // Beispiele: ##ER-PRIVACY:example.com:de##, ##ER-IMPRINT:example.com:en##
        // Restrict domain pattern to valid characters
        $pattern = '/##ER-(PRIVACY|IMPRINT|PRIVACY-SOCIAL):([a-z0-9.-]+):([a-z]{2})##/i';

        return preg_replace_callback($pattern, [self::class, 'replacePlaceholder'], $content);
    }

    /**
     * Ersetzt einen einzelnen Platzhalter.
     *
     * @param array<int, string> $matches
     */
    private static function replacePlaceholder(array $matches): string
    {
        $typeShort = strtoupper($matches[1]);
        $domain = strtolower(trim($matches[2]));
        $lang = strtolower($matches[3]);

        // Additional validation
        if (!in_array($lang, ['de', 'en'])) {
            return $matches[0];
        }

        // Konvertiere Kurzform zu vollständigem Typ
        $type = self::convertType($typeShort);

        if (null === $type) {
            // Ungültiger Typ - gib Platzhalter unverändert zurück
            return $matches[0];
        }

        // Cache key für Performance
        static $cache = [];
        $cacheKey = $domain . '_' . $type . '_' . $lang;

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        // Hole den Text aus der Datenbank
        $text = eRecht24::getText($domain, $type, $lang);

        // Wenn kein Text gefunden wurde, gib einen Kommentar zurück (im Debug-Modus)
        if (null === $text || '' === $text) {
            $result = rex::isDebugMode() 
                ? '<!-- eRecht24: Kein Text gefunden für ' . htmlspecialchars($matches[0], ENT_QUOTES, 'UTF-8') . ' -->' 
                : '';
            $cache[$cacheKey] = $result;
            return $result;
        }

        $cache[$cacheKey] = $text;
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
     * Prüft ob wir uns im Content-Vorschaumodus befinden.
     * Nur in diesem Modus soll der Filter im Backend aktiv sein.
     */
    private static function isContentPreviewMode(): bool
    {
        $page = rex_request::get('page', 'string', '');
        $function = rex_request::get('function', 'string', '');

        // Vorschaumodus wenn:
        // - Wir auf der content/edit Seite sind
        // - UND KEINE edit/add Funktion aufgerufen wird
        if ('content/edit' === $page) {
            return !in_array($function, ['edit', 'add'], true);
        }

        return false;
    }
}
