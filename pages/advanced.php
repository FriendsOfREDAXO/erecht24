<?php

$addon = rex_addon::get('erecht24');

use FriendsOfRedaxo\eRecht24\eRecht24Client;

$content = '';
$csrfToken = rex_csrf_token::factory('erecht24_advanced');

// Handle manual sync
if ('sync' === rex_post('action', 'string') && $csrfToken->isValid()) {
    $domain = rex_post('domain', 'string');
    try {
        eRecht24Client::syncTexts($domain);
        echo rex_view::success($addon->i18n('sync_success'));
    } catch (Throwable $e) {
        echo rex_view::error(rex_escape($e->getMessage()));
    }
}

// Handle test push
if ('testpush' === rex_post('action', 'string') && $csrfToken->isValid()) {
    $domain = rex_post('domain', 'string');
    $pushType = rex_post('push_type', 'string', 'ping');
    
    try {
        // Get client info
        $sql = rex_sql::factory();
        $client = $sql->setQuery('SELECT * FROM ' . rex::getTable('erecht24') . ' WHERE domain = :domain', ['domain' => $domain])->getArray();
        
        if (!empty($client)) {
            $result = eRecht24Client::fireTestPush((int) $client[0]['client_id'], $pushType, $client[0]['api_key']);
            if ($result) {
                echo rex_view::success($addon->i18n('testpush_success'));
            } else {
                echo rex_view::error($addon->i18n('testpush_failed'));
            }
        }
    } catch (Throwable $e) {
        echo rex_view::error(rex_escape($e->getMessage()));
    }
}

// Get all domains
$domains = rex_sql::factory()
    ->setQuery('SELECT * FROM ' . rex::getTable('erecht24') . ' ORDER BY domain')
    ->getArray();

if (empty($domains)) {
    echo rex_view::info($addon->i18n('no_domains'));
} else {
    // Manual Sync Section
    $content = '<h3>' . $addon->i18n('manual_sync') . '</h3>';
    $content .= '<p>' . $addon->i18n('manual_sync_description') . '</p>';
    
    foreach ($domains as $domainData) {
        $content .= '<div class="panel panel-default">';
        $content .= '<div class="panel-heading"><strong>' . rex_escape($domainData['domain']) . '</strong></div>';
        $content .= '<div class="panel-body">';
        
        // Sync button
        $content .= '<form method="post" style="display:inline-block; margin-right:10px;">';
        $content .= '<input type="hidden" name="action" value="sync">';
        $content .= '<input type="hidden" name="domain" value="' . rex_escape($domainData['domain']) . '">';
        $content .= $csrfToken->getHiddenField();
        $content .= '<button type="submit" class="btn btn-primary">';
        $content .= '<i class="rex-icon fa-refresh"></i> ' . $addon->i18n('sync_now');
        $content .= '</button>';
        $content .= '</form>';
        
        // Test Push form
        $content .= '<form method="post" style="display:inline-block;">';
        $content .= '<input type="hidden" name="action" value="testpush">';
        $content .= '<input type="hidden" name="domain" value="' . rex_escape($domainData['domain']) . '">';
        $content .= $csrfToken->getHiddenField();
        $content .= '<select name="push_type" class="form-control" style="width:auto; display:inline-block;">';
        $content .= '<option value="ping">Ping</option>';
        $content .= '<option value="imprint">Impressum</option>';
        $content .= '<option value="privacyPolicy">Datenschutz</option>';
        $content .= '<option value="privacyPolicySocialMedia">Datenschutz Social Media</option>';
        $content .= '</select>';
        $content .= ' <button type="submit" class="btn btn-default">';
        $content .= '<i class="rex-icon fa-paper-plane"></i> ' . $addon->i18n('test_push');
        $content .= '</button>';
        $content .= '</form>';
        
        $content .= '</div>';
        $content .= '</div>';
    }
    
    $fragment = new rex_fragment();
    $fragment->setVar('title', $addon->i18n('advanced_functions'));
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
    
    // API Messages Section
    $messageContent = '<h3>' . $addon->i18n('api_messages') . '</h3>';
    $messageContent .= '<p>' . $addon->i18n('api_messages_description') . '</p>';
    
    foreach ($domains as $domainData) {
        $message = eRecht24Client::getMessage($domainData['api_key'], 'de');
        if ($message) {
            $messageContent .= '<div class="alert alert-info">';
            $messageContent .= '<strong>' . rex_escape($domainData['domain']) . ':</strong> ';
            $messageContent .= rex_escape($message);
            $messageContent .= '</div>';
        }
    }
    
    $fragment = new rex_fragment();
    $fragment->setVar('title', $addon->i18n('server_messages'));
    $fragment->setVar('body', $messageContent, false);
    echo $fragment->parse('core/page/section.php');
}
