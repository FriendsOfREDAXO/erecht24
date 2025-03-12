<?php
use FriendsOfRedaxo\eRecht24\eRecht24Client;


$addon = rex_addon::get('erecht24');
$id = rex_request('id', 'int');

// Back button
$content = '<div class="btn-toolbar" style="margin-bottom: 20px;">';
$content .= '<a class="btn btn-default" href="' . rex_url::currentBackendPage(['page' => 'erecht24/settings']) . '">';
$content .= '<i class="rex-icon fa-arrow-left"></i> ' . rex_i18n::msg('back') . '</a>';
$content .= '</div>';

// Get client info
$sql = rex_sql::factory();
$client = $sql->setQuery(
    'SELECT * FROM ' . rex::getTable('erecht24') . ' WHERE id = :id LIMIT 1',
    ['id' => $id]
);

if ($client->getRows() === 0) {
    echo rex_view::error($addon->i18n('domain_not_found'));
    return;
}

$clientData = $client->getArray()[0];

// Show client info
$content .= '<div class="alert alert-info">';
$content .= '<h4>' . $addon->i18n('test_info') . '</h4>';
$content .= '<dl class="dl-horizontal" style="margin-top:20px">';
$content .= '<dt>' . $addon->i18n('domain') . ':</dt><dd>' . rex_escape($clientData['domain']) . '</dd>';
$content .= '<dt>' . $addon->i18n('client_id') . ':</dt><dd>' . rex_escape($clientData['client_id']) . '</dd>';
$content .= '<dt>' . $addon->i18n('push_url') . ':</dt><dd>' . rex_escape(rex::getServer() . 'index.php?rex-api-call=erecht24_push') . '</dd>';
$content .= '</dl>';
$content .= '</div>';

// Test form
$content .= '<form action="' . rex_url::currentBackendPage() . '" method="post">';
$content .= rex_csrf_token::factory('erecht24_test')->getHiddenField();
$content .= '<input type="hidden" name="id" value="' . $id . '">';

$content .= '<div class="panel panel-default">';
$content .= '<div class="panel-heading"><div class="panel-title">' . $addon->i18n('test_actions') . '</div></div>';
$content .= '<div class="panel-body">';

// Results section if test was performed
if (rex_request_method() === 'post' && rex_csrf_token::factory('erecht24_test')->isValid()) {
    try {
        // Log test start
        rex_logger::factory()->info('Testing eRecht24 push for domain: ' . $clientData['domain']);
        
        // Test push notification
        $apiHandler = new eRecht24\RechtstexteSDK\ApiHandler(
            $clientData['api_key'],
            eRecht24Client::PLUGIN_KEY
        );
        
        rex_logger::factory()->info('Sending test push...');
        
        if ($apiHandler->fireTestPush((int)$clientData['client_id'])) {
            rex_logger::factory()->info('Test push successful');
            $content .= rex_view::success($addon->i18n('test_success'));
        } else {
            throw new rex_exception($apiHandler->getLastErrorMessage('de') ?? 'Unknown error');
        }

    } catch (Throwable $e) {
        rex_logger::factory()->error('Test push error: ' . $e->getMessage());
        $content .= rex_view::error($addon->i18n('test_error') . ': ' . $e->getMessage());
    }
}

$content .= '<div class="btn-toolbar">';
$content .= '<button type="submit" class="btn btn-send" name="test" value="1">';
$content .= '<i class="rex-icon fa-refresh"></i> ' . $addon->i18n('test_execute') . '</button>';
$content .= '</div>';

$content .= '</div>'; // panel-body
$content .= '</div>'; // panel
$content .= '</form>';

// Log section
$content .= '<div class="panel panel-default">';
$content .= '<div class="panel-heading"><div class="panel-title">' . $addon->i18n('test_log') . '</div></div>';
$content .= '<div class="panel-body" style="max-height:400px;overflow:auto">';
$content .= '<pre>';

// Get log entries
$logFile = rex_path::log('erecht24.log');
if (file_exists($logFile)) {
    $content .= rex_escape(file_get_contents($logFile));
} else {
    $content .= $addon->i18n('no_log_entries');
}

$content .= '</pre>';
$content .= '</div></div>';

// Output
$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('test'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
