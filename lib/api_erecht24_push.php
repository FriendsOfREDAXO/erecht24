<?php

declare(strict_types=1);

use eRecht24\RechtstexteSDK\LegalTextHandler;
use FriendsOfRedaxo\eRecht24\eRecht24Client;

class rex_api_erecht24_push extends rex_api_function
{
    protected $published = true;

    public function execute(): rex_api_result
    {
        $startTime = microtime(true);
        $domain = null;
        $type = null;
        
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
                $this->logWebhook(null, null, 'error', microtime(true) - $startTime, 'No data received');
                $this->sendError(400, 'No data received');
            }

            try {
                $data = json_decode($rawInput, true, flags: JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                $this->logWebhook(null, null, 'error', microtime(true) - $startTime, 'Invalid JSON: ' . $e->getMessage());
                $this->sendError(400, 'Invalid JSON: ' . $e->getMessage());
            }

            // Validate required fields
            $secret = $data['erecht24_secret'] ?? null;
            $type = $data['erecht24_type'] ?? null;

            // Debug: Log parsed data (without sensitive info)
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Parsed data - Secret length: ' . strlen((string) $secret) . ', Type: ' . $type, __FILE__, __LINE__);
            }

            if (!$secret || !$type) {
                $this->logWebhook(null, $type, 'error', microtime(true) - $startTime, 'Missing required fields');
                $this->sendError(422, 'Missing required fields');
            }

            // Validate secret format (alphanumeric)
            if (!is_string($secret) || !preg_match('/^[a-zA-Z0-9]+$/', $secret)) {
                $this->logWebhook(null, $type, 'error', microtime(true) - $startTime, 'Invalid secret format');
                $this->sendError(401, 'Invalid secret format');
            }

            // Validate type format (prevent injection)
            if (!is_string($type) || !preg_match('/^[a-zA-Z]+$/', $type)) {
                $this->logWebhook(null, $type, 'error', microtime(true) - $startTime, 'Invalid type format');
                $this->sendError(422, 'Invalid type format');
            }

            // Get domain record by secret
            $sql = rex_sql::factory();
            $domainData = $sql->setQuery('SELECT domain, api_key FROM ' . rex::getTable('erecht24') . ' WHERE secret = :secret LIMIT 1', ['secret' => $secret])->getArray();

            // Debug: Log domain lookup
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Domain lookup result: ' . print_r($domainData, true), __FILE__, __LINE__);
            }

            if (empty($domainData)) {
                $this->logWebhook(null, $type, 'error', microtime(true) - $startTime, 'Invalid secret');
                $this->sendError(401, 'Invalid secret');
            }

            $domainData = $domainData[0];
            $domain = $domainData['domain'];

            // Handle ping requests
            if ('ping' === $type) {
                $this->logWebhook($domain, $type, 'success', microtime(true) - $startTime);
                $this->sendSuccess(['code' => 200, 'message' => 'pong']);
            }

            // Validate text type
            if (!in_array($type, ['imprint', 'privacyPolicy', 'privacyPolicySocialMedia'])) {
                $this->logWebhook($domain, $type, 'error', microtime(true) - $startTime, 'Invalid type: ' . $type);
                $this->sendError(422, 'Invalid type: ' . $type);
            }

            // Debug: Log API initialization
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Initializing API handler with key: ' . substr($domainData['api_key'], 0, 8) . '...', __FILE__, __LINE__);
            }

            // Create API handler
            $handler = new LegalTextHandler(
                $domainData['api_key'],
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
                $errorMsg = $handler->getLastErrorMessage('de') ?? 'Unknown error from eRecht24 API';
                $this->logWebhook($domain, $type, 'error', microtime(true) - $startTime, $errorMsg);
                $this->sendError(500, $errorMsg);
            }

            // Store text in database
            try {
                $table = rex::getTable('erecht24_texts');

                // Check if text exists
                $exists = rex_sql::factory()
                    ->setTable($table)
                    ->setWhere([
                        'domain' => $domain,
                        'type' => $type,
                    ])
                    ->select()
                    ->getRows() > 0;

                // Prepare data
                $sql = rex_sql::factory();
                $sql->setTable($table);
                $sql->setValue('domain', $domain);
                $sql->setValue('type', $type);
                $sql->setValue('html_de', $document->getHtmlDE() ?? '');
                $sql->setValue('html_en', $document->getHtmlEN() ?? '');
                $sql->setValue('last_fetch', date('Y-m-d H:i:s'));
                $sql->setValue('updatedate', date('Y-m-d H:i:s'));

                if ($exists) {
                    $sql->setWhere([
                        'domain' => $domain,
                        'type' => $type,
                    ]);
                    $sql->update();
                } else {
                    $sql->setValue('createdate', date('Y-m-d H:i:s'));
                    $sql->insert();
                }
            } catch (Throwable $e) {
                if (eRecht24Client::DEBUG) {
                    rex_logger::logError(1, 'Database error: ' . $e->getMessage(), __FILE__, __LINE__);
                }
                $this->logWebhook($domain, $type, 'error', microtime(true) - $startTime, 'Database error: ' . $e->getMessage());
                $this->sendError(500, 'Database error: ' . $e->getMessage());
            }

            // Log successful webhook
            $this->logWebhook($domain, $type, 'success', microtime(true) - $startTime);
            
            // Send successful response
            $this->sendSuccess(['message' => 'Text updated']);
        } catch (Throwable $e) {
            // Log error
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Uncaught error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), __FILE__, __LINE__);
            }
            $this->logWebhook($domain, $type, 'error', microtime(true) - $startTime, 'Internal server error: ' . $e->getMessage());
            $this->sendError(500, 'Internal server error: ' . $e->getMessage());
        }
    }

    protected function requiresCsrfProtection(): bool
    {
        return false;
    }

    /**
     * Logs webhook request to database.
     *
     * @param string|null $domain
     * @param string|null $type
     * @param string $status
     * @param float $responseTime
     * @param string|null $errorMessage
     */
    private function logWebhook(?string $domain, ?string $type, string $status, float $responseTime, ?string $errorMessage = null): void
    {
        try {
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('erecht24_webhook_log'));
            $sql->setValue('domain', $domain ?? 'unknown');
            $sql->setValue('type', $type ?? 'unknown');
            $sql->setValue('status', $status);
            $sql->setValue('response_time', (int) ($responseTime * 1000)); // Convert to milliseconds
            $sql->setValue('error_message', $errorMessage);
            $sql->setValue('createdate', date('Y-m-d H:i:s'));
            $sql->insert();
        } catch (Throwable $e) {
            // Silent fail - logging should not break webhook
            if (eRecht24Client::DEBUG) {
                rex_logger::logError(1, 'Webhook logging failed: ' . $e->getMessage(), __FILE__, __LINE__);
            }
        }
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
