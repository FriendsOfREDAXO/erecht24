<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\eRecht24;

use eRecht24\RechtstexteSDK\ApiHandler;
use eRecht24\RechtstexteSDK\Model\Client;
use rex;
use rex_exception;
use rex_logger;
use rex_sql;

/**
 * Client class for eRecht24 integration.
 *
 * @phpstan-type ClientData array{
 *   id: int,
 *   domain: string,
 *   api_key: string,
 *   client_id: string,
 *   secret: string,
 *   updatedate: string,
 *   createdate: string
 * }
 */
class eRecht24Client
{
    public const PLUGIN_KEY = 'ML7mWFmozzpDNDbUtYUM7UghXCsi37nWumSrMAk3Y4nCihQQZK7H7LJ9ufx4fyJu';
    public const DEBUG = false;

    /**
     * Registers a new client with eRecht24 and stores it in the database.
     *
     * @param string $domain The domain to register
     * @param string $apiKey The API key for eRecht24
     * @throws rex_exception If registration fails or domain already exists
     */
    public static function register(string $domain, string $apiKey): void
    {
        // Validate and sanitize domain
        $domain = strtolower(trim($domain));
        if (!self::isValidDomain($domain)) {
            throw new rex_exception('Ungültiges Domain-Format.');
        }

        // Validate API key format
        if (empty($apiKey) || strlen($apiKey) < 10) {
            throw new rex_exception('Ungültiger API-Key.');
        }

        // Check if domain already exists
        $sql = rex_sql::factory();
        $existingDomains = $sql->setQuery(
            'SELECT domain FROM ' . rex::getTable('erecht24') . ' WHERE domain = :domain',
            ['domain' => $domain],
        )->getArray();

        if (!empty($existingDomains)) {
            throw new rex_exception('Diese Domain ist bereits registriert.');
        }

        // Initialize API handler
        $apiHandler = new ApiHandler($apiKey, self::PLUGIN_KEY);

        // Create new client
        $pushUrl = rtrim(rex::getServer(), '/') . '/index.php?rex-api-call=erecht24_push';
        rex_logger::factory()->info('Push URL: ' . $pushUrl);

        $client = (new Client())
            ->setPushUri($pushUrl)
            ->setPushMethod('POST')
            ->setCms('REDAXO')
            ->setCmsVersion(rex::getVersion())
            ->setPluginName('redaxo/erecht24')
            ->setAuthorMail(rex::getErrorEmail());

        // Register client with eRecht24
        $registeredClient = $apiHandler->createClient($client);
        if (!$apiHandler->isLastResponseSuccess()) {
            throw new rex_exception($apiHandler->getLastErrorMessage('de') ?? 'Unknown error');
        }

        // Store in database
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('erecht24'));
        $sql->setValue('domain', $domain);
        $sql->setValue('api_key', $apiKey);
        $sql->setValue('client_id', $registeredClient->getClientId());
        $sql->setValue('secret', $registeredClient->getSecret());
        $sql->setValue('updatedate', date('Y-m-d H:i:s'));
        $sql->setValue('createdate', date('Y-m-d H:i:s'));
        $sql->insert();
    }

    /**
     * Unregisters a client from eRecht24 and removes it from the database.
     *
     * @param string $domain The domain to unregister
     */
    public static function unregister(string $domain): void
    {
        // Get client info
        $sql = rex_sql::factory();
        /** @var array<ClientData> $clients */
        $clients = $sql->setQuery('SELECT * FROM ' . rex::getTable('erecht24') . ' WHERE domain = :domain', ['domain' => $domain])->getArray();

        if (!empty($clients)) {
            /** @var ClientData $client */
            $client = $clients[0];

            // Delete from eRecht24
            if ($client['api_key'] && $client['client_id']) {
                $apiHandler = new ApiHandler($client['api_key'], self::PLUGIN_KEY);
                $apiHandler->deleteClient((int) $client['client_id']);
            }

            // Delete from database
            rex_sql::factory()
                ->setTable(rex::getTable('erecht24'))
                ->setWhere(['domain' => $domain])
                ->delete();

            // Delete texts
            rex_sql::factory()
                ->setTable(rex::getTable('erecht24_texts'))
                ->setWhere(['domain' => $domain])
                ->delete();
        }
    }

    /**
     * Updates client information for a registered domain.
     *
     * @param string $domain The domain to update
     * @param array<string, mixed> $data Update data (push_uri, cms_version, etc.)
     * @throws rex_exception If update fails
     */
    public static function update(string $domain, array $data): void
    {
        // Get client info
        $sql = rex_sql::factory();
        /** @var array<ClientData> $clients */
        $clients = $sql->setQuery('SELECT * FROM ' . rex::getTable('erecht24') . ' WHERE domain = :domain', ['domain' => $domain])->getArray();

        if (empty($clients)) {
            throw new rex_exception('Domain nicht gefunden.');
        }

        /** @var ClientData $clientData */
        $clientData = $clients[0];

        // Initialize API handler
        $apiHandler = new ApiHandler($clientData['api_key'], self::PLUGIN_KEY);

        // Build client object with current data
        $client = (new Client())
            ->setClientId((int) $clientData['client_id'])
            ->setPushUri($data['push_uri'] ?? rtrim(rex::getServer(), '/') . '/index.php?rex-api-call=erecht24_push')
            ->setPushMethod($data['push_method'] ?? 'POST')
            ->setCms($data['cms'] ?? 'REDAXO')
            ->setCmsVersion($data['cms_version'] ?? rex::getVersion())
            ->setPluginName($data['plugin_name'] ?? 'redaxo/erecht24')
            ->setAuthorMail($data['author_mail'] ?? rex::getErrorEmail());

        // Update client with eRecht24
        $updatedClient = $apiHandler->updateClient($client);
        if (!$apiHandler->isLastResponseSuccess()) {
            throw new rex_exception($apiHandler->getLastErrorMessage('de') ?? 'Unknown error');
        }

        // Update secret in database if changed
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('erecht24'));
        $sql->setWhere(['domain' => $domain]);
        $sql->setValue('secret', $updatedClient->getSecret());
        $sql->setValue('updatedate', date('Y-m-d H:i:s'));
        $sql->update();
    }

    /**
     * Gets list of all registered clients for an API key.
     *
     * @param string $apiKey The API key
     * @return array<array<string, mixed>> Array of client data
     * @throws rex_exception If request fails
     */
    public static function getClientList(string $apiKey): array
    {
        $apiHandler = new ApiHandler($apiKey, self::PLUGIN_KEY);
        $collection = $apiHandler->getClientList();

        if (!$apiHandler->isLastResponseSuccess()) {
            throw new rex_exception($apiHandler->getLastErrorMessage('de') ?? 'Unknown error');
        }

        $result = [];
        foreach ($collection as $client) {
            /** @var Client $client */
            $result[] = [
                'client_id' => $client->getClientId(),
                'project_id' => $client->getProjectId(),
                'push_uri' => $client->getPushUri(),
                'push_method' => $client->getPushMethod(),
                'cms' => $client->getCms(),
                'cms_version' => $client->getCmsVersion(),
                'plugin_name' => $client->getPluginName(),
                'author_mail' => $client->getAuthorMail(),
                'created_at' => $client->getCreatedAt(),
                'updated_at' => $client->getUpdatedAt(),
            ];
        }

        return $result;
    }

    /**
     * Manually synchronizes all texts for a domain.
     *
     * @param string $domain The domain to sync
     * @throws rex_exception If sync fails
     */
    public static function syncTexts(string $domain): void
    {
        // Get client info
        $sql = rex_sql::factory();
        /** @var array<ClientData> $clients */
        $clients = $sql->setQuery('SELECT * FROM ' . rex::getTable('erecht24') . ' WHERE domain = :domain', ['domain' => $domain])->getArray();

        if (empty($clients)) {
            throw new rex_exception('Domain nicht gefunden.');
        }

        /** @var ClientData $clientData */
        $clientData = $clients[0];

        // Initialize API handler
        $apiHandler = new ApiHandler($clientData['api_key'], self::PLUGIN_KEY);

        // Sync all three text types
        $types = [
            'imprint' => 'getImprint',
            'privacyPolicy' => 'getPrivacyPolicy',
            'privacyPolicySocialMedia' => 'getPrivacyPolicySocialMedia',
        ];

        foreach ($types as $type => $method) {
            try {
                $document = $apiHandler->$method();

                if ($apiHandler->isLastResponseSuccess()) {
                    // Store or update text in database
                    $table = rex::getTable('erecht24_texts');
                    $exists = rex_sql::factory()
                        ->setTable($table)
                        ->setWhere(['domain' => $domain, 'type' => $type])
                        ->select()
                        ->getRows() > 0;

                    $sql = rex_sql::factory();
                    $sql->setTable($table);
                    $sql->setValue('domain', $domain);
                    $sql->setValue('type', $type);
                    $sql->setValue('html_de', $document->getHtmlDE() ?? '');
                    $sql->setValue('html_en', $document->getHtmlEN() ?? '');
                    $sql->setValue('last_fetch', date('Y-m-d H:i:s'));
                    $sql->setValue('updatedate', date('Y-m-d H:i:s'));

                    if ($exists) {
                        $sql->setWhere(['domain' => $domain, 'type' => $type]);
                        $sql->update();
                    } else {
                        $sql->setValue('createdate', date('Y-m-d H:i:s'));
                        $sql->insert();
                    }
                }
            } catch (\Throwable $e) {
                rex_logger::logError(1, 'Sync failed for ' . $type . ': ' . $e->getMessage(), __FILE__, __LINE__);
            }
        }
    }

    /**
     * Fires a test push to a client.
     *
     * @param int $clientId The client ID
     * @param string $type The push type (ping, imprint, privacyPolicy, privacyPolicySocialMedia)
     * @param string $apiKey The API key
     * @return bool Success status
     * @throws rex_exception If request fails
     */
    public static function fireTestPush(int $clientId, string $type, string $apiKey): bool
    {
        $apiHandler = new ApiHandler($apiKey, self::PLUGIN_KEY);
        $result = $apiHandler->fireTestPush($clientId, $type);

        if (!$apiHandler->isLastResponseSuccess()) {
            throw new rex_exception($apiHandler->getLastErrorMessage('de') ?? 'Unknown error');
        }

        return $result;
    }

    /**
     * Gets a message from the eRecht24 API.
     *
     * @param string $apiKey The API key
     * @param string $lang Language (de or en)
     * @return string|null Message or null
     */
    public static function getMessage(string $apiKey, string $lang = 'de'): ?string
    {
        try {
            $apiHandler = new ApiHandler($apiKey, self::PLUGIN_KEY);
            return $apiHandler->getMessage($lang);
        } catch (\Throwable $e) {
            rex_logger::logError(1, 'getMessage failed: ' . $e->getMessage(), __FILE__, __LINE__);
            return null;
        }
    }

    /**
     * Validates domain format.
     *
     * @param string $domain The domain to validate
     * @return bool True if valid, false otherwise
     */
    private static function isValidDomain(string $domain): bool
    {
        // Basic domain validation
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain);
    }
}
