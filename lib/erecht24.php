<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\eRecht24;

use rex;
use rex_sql;
use rex_logger;
use rex_exception;
use Exception;

class eRecht24
{
   private static $validTypes = [
       'imprint',
       'privacyPolicy',
       'privacyPolicySocialMedia'
   ];

   /**
    * Prüft ob für die angegebene ID/Domain und den Typ ein Text existiert
    *
    * @param int|string $identifier ID oder Domain des Eintrags
    * @param string $type Art des Texts (imprint, privacy_policy, privacy_policy_social_media)
    * @param string $lang Sprache (de, en) - optional für spezifische Sprachprüfung
    * @return bool
    */
   public static function hasText(int|string $identifier, string $type, string $lang = ''): bool 
   {
       try {
           if (!in_array($type, self::$validTypes)) {
               throw new rex_exception('Invalid text type: ' . $type);
           }

           // Validate language parameter to prevent SQL injection
           if ($lang && !in_array($lang, ['de', 'en'])) {
               throw new rex_exception('Invalid language: ' . $lang);
           }

           // Get domain
           $domain = self::getDomain($identifier);
           
           if (!$domain) {
               return false;
           }

           // Check for text
           $sql = rex_sql::factory();
           $where = ['domain' => $domain, 'type' => $type];
           
           if ($lang) {
               // Wenn eine Sprache angegeben ist, prüfe ob der Text in dieser Sprache nicht leer ist
               $columnName = 'html_' . $lang; // Safe after validation
               $sql->setQuery(
                   'SELECT ' . $columnName . ' FROM '.rex::getTable('erecht24_texts').' 
                    WHERE domain = :domain AND type = :type 
                    AND ' . $columnName . ' IS NOT NULL 
                    AND ' . $columnName . ' != ""',
                   $where
               );
           } else {
               // Sonst prüfe ob überhaupt ein Eintrag existiert
               $sql->setQuery(
                   'SELECT id FROM '.rex::getTable('erecht24_texts').' 
                    WHERE domain = :domain AND type = :type',
                   $where
               );
           }

           return $sql->getRows() > 0;

       } catch (Exception $e) {
           // Log error but don't expose to frontend
           rex_logger::logError(1, $e->getMessage(), __FILE__, __LINE__);
           return false;
       }
   }

   /**
    * Gibt den rechtlichen Text für die angegebene ID/Domain und den Typ zurück
    *
    * @param int|string $identifier ID oder Domain des Eintrags
    * @param string $type Art des Texts (imprint, privacy_policy, privacy_policy_social_media)
    * @param string $lang Sprache (de, en)
    * @return string|null Text oder null wenn nicht gefunden
    */
   public static function getText(int|string $identifier, string $type, string $lang = 'de'): ?string 
   {
       try {
           if (!in_array($type, self::$validTypes)) {
               throw new rex_exception('Invalid text type: ' . $type);
           }

           // Validate language parameter to prevent SQL injection
           if (!in_array($lang, ['de', 'en'])) {
               throw new rex_exception('Invalid language: ' . $lang);
           }

           // Get domain
           $domain = self::getDomain($identifier);
           
           if (!$domain) {
               return null;
           }

           // Get text
           $columnName = 'html_' . $lang; // Safe after validation
           $html = rex_sql::factory()
               ->setQuery(
                   'SELECT ' . $columnName . ' FROM '.rex::getTable('erecht24_texts').' WHERE domain = :domain AND type = :type',
                   ['domain' => $domain, 'type' => $type]
               )
               ->getValue($columnName);

           return $html ?: null;

       } catch (Exception $e) {
           rex_logger::logError(1, $e->getMessage(), __FILE__, __LINE__);
           return null;
       }
   }

   /**
    * Gibt die möglichen Text-Typen zurück
    *
    * @return array
    */
   public static function getTypes(): array
   {
       return self::$validTypes;
   }

   /**
    * Ermittelt die Domain anhand der ID oder Domain
    * 
    * @param int|string $identifier ID oder Domain des Eintrags
    * @return string|null Domain oder null wenn nicht gefunden
    */
   private static function getDomain(int|string $identifier): ?string
   {
       $sql = rex_sql::factory();

       if (is_int($identifier)) {
           return $sql->setQuery(
               'SELECT domain FROM '.rex::getTable('erecht24').' WHERE id = :id',
               ['id' => $identifier]
           )->getValue('domain');
       }

       // Prüfen ob Domain existiert
       $exists = $sql->setQuery(
           'SELECT 1 FROM '.rex::getTable('erecht24').' WHERE domain = :domain',
           ['domain' => $identifier]
       )->getRows() > 0;

       return $exists ? $identifier : null;
   }
}
