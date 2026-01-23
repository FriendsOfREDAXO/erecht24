<?php

declare(strict_types=1);

use eRecht24\RechtstexteSDK\LegalTextHandler;
use FriendsOfRedaxo\eRecht24\eRecht24Client;

class rex_api_erecht24_push extends rex_api_function
{
    protected $published = true;

    public function execute(): rex_api_result
    {
        try {
            // Clear output buffer
            rex_response::cleanOutputBuffers();

            // Set response headers
            header('Content-Type: application/json');

            // Debug: Log incoming request
            $rawInput = file_get_contents('php://input');
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Incoming request: ' . $rawInput, __FILE__, __LINE__);
            }

            // Get POST data as JSON
            if (!$rawInput) {
                $this->sendError(400, 'No data received');
            }

            try {
                $data = json_decode($rawInput, true, flags: JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                $this->sendError(400, 'Invalid JSON: ' . $e->getMessage());
            }

            // Validate required fields
            $secret = $data['erecht24_secret'] ?? null;
            $type = $data['erecht24_type'] ?? null;

            // Debug: Log parsed data
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Parsed data - Secret: ' . $secret . ', Type: ' . $type, __FILE__, __LINE__);
            }

            if (!$secret || !$type) {
                $this->sendError(422, 'Missing required fields');
            }

            // Get domain record by secret
            $sql = rex_sql::factory();
            $domain = $sql->setQuery('SELECT domain, api_key FROM ' . rex::getTable('erecht24') . ' WHERE secret = :secret LIMIT 1', ['secret' => $secret])->getArray();

            // Debug: Log domain lookup
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Domain lookup result: ' . print_r($domain, true), __FILE__, __LINE__);
            }

            if (empty($domain)) {
                $this->sendError(401, 'Invalid secret');
            }

            // Handle ping requests
            if ('ping' === $type) {
                $this->sendSuccess(['code' => 200, 'message' => 'pong']);
            }

            // Validate text type
            if (!in_array($type, ['imprint', 'privacyPolicy', 'privacyPolicySocialMedia'])) {
                $this->sendError(422, 'Invalid type: ' . $type);
            }

            $domain = $domain[0];

            // Debug: Log API initialization
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Initializing API handler with key: ' . substr($domain['api_key'], 0, 8) . '...', __FILE__, __LINE__);
            }

            // Create API handler
            $handler = new LegalTextHandler(
                $domain['api_key'],
                $type,
                eRecht24Client::PLUGIN_KEY,
            );

            $document = $handler->importDocument();

            // Debug: Log API response
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'API Response success: ' . ($handler->isLastResponseSuccess() ? 'true' : 'false'), __FILE__, __LINE__);
                if (!$handler->isLastResponseSuccess()) {
                    rex_logger::logError(1, 'API Error: ' . $handler->getLastErrorMessage('de'), __FILE__, __LINE__);
                }
            }

            if (!$handler->isLastResponseSuccess()) {
                $this->sendError(500, $handler->getLastErrorMessage('de') ?? 'Unknown error from eRecht24 API');
            }

            // Store text in database
            try {
                $table = rex::getTable('erecht24_texts');

                // Check if text exists
                $exists = rex_sql::factory()
                    ->setTable($table)
                    ->setWhere([
                        'domain' => $domain['domain'],
                        'type' => $type,
                    ])
                    ->select()
                    ->getRows() > 0;

                // Prepare data
                $sql = rex_sql::factory();
                $sql->setTable($table);
                $sql->setValue('domain', $domain['domain']);
                $sql->setValue('type', $type);
                $sql->setValue('html_de', $document->getHtmlDE() ?? '');
                $sql->setValue('html_en', $document->getHtmlEN() ?? '');
                $sql->setValue('last_fetch', date('Y-m-d H:i:s'));
                $sql->setValue('updatedate', date('Y-m-d H:i:s'));

                if ($exists) {
                    $sql->setWhere([
                        'domain' => $domain['domain'],
                        'type' => $type,
                    ]);
                    $sql->update();
                } else {
                    $sql->insert();
                }
            } catch (Throwable $e) {
                if (eRecht24Client::DEBUG) {
                    rex_logger::logError(1, 'Database error: ' . $e->getMessage(), __FILE__, __LINE__);
                }
                $this->sendError(500, 'Database error: ' . $e->getMessage());
            }

            // Send successful response
            $this->sendSuccess(['message' => 'Text updated']);
        } catch (Throwable $e) {
            // Log error
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Uncaught error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), __FILE__, __LINE__);
            }
            $this->sendError(500, 'Internal server error: ' . $e->getMessage());
        }
    }

    protected function requiresCsrfProtection(): bool
    {
        return false;
    }

    private function sendError(int $code, string $message): never
    {
        $codes = [
            400 => 'HTTP/1.1 400 Bad Request',
            401 => 'HTTP/1.1 401 Unauthorized',
            422 => 'HTTP/1.1 422 Unprocessable Entity',
            500 => 'HTTP/1.1 500 Internal Server Error',
        ];

        header($codes[$code] ?? 'HTTP/1.1 500 Internal Server Error');
        echo json_encode(['message' => $message]);
        exit;
    }

    private function sendSuccess(array $data): never
    {
        header('HTTP/1.1 200 OK');
        echo json_encode($data);
        exit;
    }
}
