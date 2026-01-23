<?php

$addon = rex_addon::get('erecht24');

use FriendsOfRedaxo\eRecht24\eRecht24Client;

$content = '';
$buttons = '';

// csrf-Schutz
$csrfToken = rex_csrf_token::factory('erecht24');

// Formular abgesendet - Domain hinzufügen
if ('1' == rex_post('formsubmit', 'string') && !$csrfToken->isValid()) {
    echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
} elseif ('1' == rex_post('formsubmit', 'string')) {
    $domain = rex_post('domain', 'string');
    $apiKey = rex_post('api_key', 'string');

    if (!$domain || !$apiKey) {
        echo rex_view::error($addon->i18n('missing_fields'));
    } else {
        try {
            eRecht24Client::register($domain, $apiKey);
            echo rex_view::success($addon->i18n('domain_added'));
        } catch (Throwable $e) {
            echo rex_view::error($e->getMessage());
        }
    }
}

// Handle delete
if ('delete' === rex_get('func', 'string') && ($domain = rex_get('domain', 'string'))) {
    try {
        eRecht24Client::unregister($domain);
        echo rex_view::success($addon->i18n('domain_deleted'));
    } catch (Throwable $e) {
        echo rex_view::error($e->getMessage());
    }
}

// Add form
$formElements = [];

// Domain field
$n = [];
$n['label'] = '<label for="domain">' . $addon->i18n('domain') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="domain" name="domain" value="' . rex_escape(rex_server('SERVER_NAME', 'string', '')) . '">';
$formElements[] = $n;

// API Key field
$n = [];
$n['label'] = '<label for="api_key">' . $addon->i18n('api_key') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="api_key" name="api_key">';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

// Save-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . $addon->i18n('save') . '">' . $addon->i18n('save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');
$buttons = '
<fieldset class="rex-form-action">
    ' . $buttons . '
</fieldset>
';

// Domain list
$list = rex_sql::factory()
    ->setQuery('
        SELECT e.*, 
               MAX(t.last_fetch) as last_fetch 
        FROM ' . rex::getTable('erecht24') . ' e
        LEFT JOIN ' . rex::getTable('erecht24_texts') . ' t 
        ON e.domain = t.domain
        GROUP BY e.id, e.domain, e.api_key, e.client_id, e.updatedate
    ')
    ->getArray();

$listContent = '<div class="table-responsive">';
$listContent .= '<table class="table table-hover">';
$listContent .= '<thead><tr>';
$listContent .= '<th>' . $addon->i18n('id') . '</th>';
$listContent .= '<th>' . $addon->i18n('domain') . '</th>';
$listContent .= '<th>' . $addon->i18n('api_key') . '</th>';
$listContent .= '<th>' . $addon->i18n('client_id') . '</th>';
$listContent .= '<th>' . $addon->i18n('last_update') . '</th>';
$listContent .= '<th>' . $addon->i18n('last_fetch') . '</th>';
$listContent .= '<th class="rex-table-action">' . $addon->i18n('functions') . '</th>';
$listContent .= '</tr></thead>';
$listContent .= '<tbody>';

if (0 === count($list)) {
    $listContent .= '<tr><td colspan="7">' . $addon->i18n('no_domains') . '</td></tr>';
} else {
    foreach ($list as $item) {
        $listContent .= '<tr>';
        $listContent .= '<td>' . rex_escape($item['id']) . '</td>';
        $listContent .= '<td>' . rex_escape($item['domain']) . '</td>';
        $listContent .= '<td>' . rex_escape(substr($item['api_key'], 0, 8) . '...') . '</td>';
        $listContent .= '<td>' . rex_escape($item['client_id']) . '</td>';
        $listContent .= '<td>' . rex_formatter::strftime($item['updatedate'], 'datetime') . '</td>';
        $listContent .= '<td>' . ($item['last_fetch'] ? rex_formatter::strftime($item['last_fetch'], 'datetime') : '-') . '</td>';
        $listContent .= '<td class="rex-table-action">';
        $listContent .= '<a href="' . rex_url::backendPage('erecht24/preview', ['id' => $item['id']]) . '" class="rex-link-expanded">';
        $listContent .= '<i class="rex-icon fa-eye"></i> ' . $addon->i18n('preview') . '</a>';
        $listContent .= '<br><a href="' . rex_url::backendPage('erecht24/test', ['id' => $item['id']]) . '" class="rex-link-expanded">';
        $listContent .= '<i class="rex-icon fa-refresh"></i> ' . $addon->i18n('test') . '</a>';
        $listContent .= '<br><a href="' . rex_url::currentBackendPage(['func' => 'delete', 'domain' => $item['domain']]) . '" class="rex-link-expanded" data-confirm="' . $addon->i18n('delete_confirm') . '">';
        $listContent .= '<i class="rex-icon fa-trash"></i> ' . rex_i18n::msg('delete') . '</a>';
        $listContent .= '</td></tr>';
    }
}

$listContent .= '</tbody></table>';
$listContent .= '</div>';

// Output form
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('add_domain'));
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$formOutput = $fragment->parse('core/page/section.php');

$formOutput = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
<input type="hidden" name="formsubmit" value="1" />
    ' . $csrfToken->getHiddenField() . '
    ' . $formOutput . '
</form>
';

// Output list
$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('domains'));
$fragment->setVar('content', $listContent, false);
$listOutput = $fragment->parse('core/page/section.php');

// Final output
echo $formOutput;
echo $listOutput;

// Outputfilter Hilfe
$helpContent = '<div class="alert alert-info">';
$helpContent .= '<h4>' . $addon->i18n('outputfilter_title') . '</h4>';
$helpContent .= '<p>' . $addon->i18n('outputfilter_info') . '</p>';

// Zeige verfügbare Platzhalter basierend auf vorhandenen Daten
try {
    $sql = rex_sql::factory();
    $sql->setQuery('SELECT t.domain, t.type, t.html_de, t.html_en 
        FROM ' . rex::getTable('erecht24_texts') . ' t
        ORDER BY t.domain, t.type');
    
    $availableTexts = $sql->getArray();

    if (count($availableTexts) > 0) {
        $helpContent .= '<div class="alert alert-success">';
        $helpContent .= '<strong>Verfügbare Platzhalter für deine Daten:</strong><br>';
        $helpContent .= '<pre><code>';
        foreach ($availableTexts as $text) {
            $typeMap = [
                'imprint' => 'IMPRINT',
                'privacyPolicy' => 'PRIVACY',
                'privacyPolicySocialMedia' => 'PRIVACY-SOCIAL',
            ];
            $shortType = $typeMap[$text['type']] ?? strtoupper($text['type']);
            
            // Mit ID
            if (!empty($text['html_de'])) {
                $helpContent .= '##ER-' . $shortType . ':' . $text['id'] . ':de##' . "\n";
            }
            if (!empty($text['html_en'])) {
                $helpContent .= '##ER-' . $shortType . ':' . $text['id'] . ':en##' . "\n";
            }
            
            // Mit Domain
            if (!empty($text['html_de'])) {
                $helpContent .= '##ER-' . $shortType . ':' . rex_escape($text['domain']) . ':de##' . "\n";
            }
        }
        $helpContent .= '</code></pre>';
        $helpContent .= '</div>';
    } else {
        $helpContent .= '<div class="alert alert-warning">';
        $helpContent .= '<strong>Noch keine Texte vorhanden.</strong><br>';
        $helpContent .= 'Bitte synchronisiere die Texte über den Sync-Button im eRecht24 Projekt Manager oder nutze die Test-Funktion.';
        $helpContent .= '</div>';
    }
} catch (Exception $e) {
    $helpContent .= '<div class="alert alert-danger">';
    $helpContent .= 'Fehler beim Laden der Texte: ' . rex_escape($e->getMessage());
    $helpContent .= '</div>';
}

$helpContent .= '<h5>' . $addon->i18n('outputfilter_usage') . '</h5>';
$helpContent .= '<p>' . $addon->i18n('outputfilter_pattern') . '</p>';
$helpContent .= '<ul>';
$helpContent .= '<li>' . $addon->i18n('outputfilter_type') . '</li>';
$helpContent .= '<li>' . $addon->i18n('outputfilter_identifier') . '</li>';
$helpContent .= '<li>' . $addon->i18n('outputfilter_lang') . '</li>';
$helpContent .= '</ul>';
$helpContent .= '<h5>' . $addon->i18n('outputfilter_examples') . '</h5>';
$helpContent .= '<pre><code>';
$helpContent .= '&lt;!-- Datenschutzerklärung mit ID --&gt;' . "\n";
$helpContent .= '##ER-PRIVACY:1:de##' . "\n\n";
$helpContent .= '&lt;!-- Impressum mit Domain --&gt;' . "\n";
$helpContent .= '##ER-IMPRINT:example.com:de##' . "\n\n";
$helpContent .= '&lt;!-- Datenschutz Social Media auf Englisch --&gt;' . "\n";
$helpContent .= '##ER-PRIVACY-SOCIAL:example.com:en##' . "\n\n";
$helpContent .= '&lt;!-- In Modulen oder Templates --&gt;' . "\n";
$helpContent .= '&lt;div class="legal-text"&gt;' . "\n";
$helpContent .= '    &lt;h2&gt;Datenschutzerklärung&lt;/h2&gt;' . "\n";
$helpContent .= '    ##ER-PRIVACY:1:de##' . "\n";
$helpContent .= '&lt;/div&gt;';
$helpContent .= '</code></pre>';
$helpContent .= '<p><small>' . $addon->i18n('outputfilter_note') . '</small></p>';
$helpContent .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('outputfilter_title'));
$fragment->setVar('content', $helpContent, false);
echo $fragment->parse('core/page/section.php');

