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
}
