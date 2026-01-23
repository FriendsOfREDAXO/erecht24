<?php

$addon = rex_addon::get('erecht24');

use FriendsOfRedaxo\eRecht24\eRecht24Client;

$content = '';
$buttons = '';

// csrf-Schutz
$csrfToken = rex_csrf_token::factory('erecht24');

// Formular abgesendet - Outputfilter-Einstellungen
if ('2' == rex_post('formsubmit', 'string') && !$csrfToken->isValid()) {
    echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
} elseif ('2' == rex_post('formsubmit', 'string')) {
    $outputfilterEnabled = rex_post('outputfilter_enabled', 'boolean');
    rex_config::set('erecht24', 'outputfilter_enabled', $outputfilterEnabled);
    echo rex_view::success($addon->i18n('config_saved'));
}

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

// Outputfilter Konfiguration
$outputfilterEnabled = rex_config::get('erecht24', 'outputfilter_enabled', true);

$outputfilterForm = '';
$n = [];
$n['label'] = '<label for="outputfilter_enabled">Outputfilter aktivieren</label>';
$n['field'] = '<input type="checkbox" id="outputfilter_enabled" name="outputfilter_enabled" value="1" ' . ($outputfilterEnabled ? 'checked="checked"' : '') . '> <small>Ersetzt automatisch Platzhalter im Frontend und Backend-Vorschau</small>';
$formElements = [$n];

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$outputfilterForm .= $fragment->parse('core/form/form.php');

// Save-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="Speichern">Speichern</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$outputfilterButtons = $fragment->parse('core/form/submit.php');
$outputfilterButtons = '<fieldset class="rex-form-action">' . $outputfilterButtons . '</fieldset>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', 'Outputfilter');
$fragment->setVar('body', $outputfilterForm, false);
$fragment->setVar('buttons', $outputfilterButtons, false);
$outputfilterOutput = $fragment->parse('core/page/section.php');

$outputfilterOutput = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
<input type="hidden" name="formsubmit" value="2" />
    ' . $csrfToken->getHiddenField() . '
    ' . $outputfilterOutput . '
</form>
';

echo $outputfilterOutput;

// Outputfilter Modal Button
$modalContent = '<div class="alert alert-info">';
$modalContent .= '<p>' . $addon->i18n('outputfilter_info') . '</p>';

// Zeige verfügbare Platzhalter basierend auf vorhandenen Daten
try {
    $sql = rex_sql::factory();
    $sql->setQuery('SELECT t.domain, t.type, t.html_de, t.html_en 
        FROM ' . rex::getTable('erecht24_texts') . ' t
        ORDER BY t.domain, t.type');
    
    $availableTexts = $sql->getArray();

    if (count($availableTexts) > 0) {
        $modalContent .= '<div class="alert alert-success">';
        $modalContent .= '<strong>Verfügbare Platzhalter für deine Daten:</strong><br>';
        $modalContent .= '<pre style="background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto;"><code>';
        foreach ($availableTexts as $text) {
            $typeMap = [
                'imprint' => 'IMPRINT',
                'privacyPolicy' => 'PRIVACY',
                'privacyPolicySocialMedia' => 'PRIVACY-SOCIAL',
            ];
            $shortType = $typeMap[$text['type']] ?? strtoupper($text['type']);
            
            // Mit Domain
            if (!empty($text['html_de'])) {
                $modalContent .= '##ER-' . $shortType . ':' . rex_escape($text['domain']) . ':de##' . "\n";
            }
            if (!empty($text['html_en'])) {
                $modalContent .= '##ER-' . $shortType . ':' . rex_escape($text['domain']) . ':en##' . "\n";
            }
        }
        $modalContent .= '</code></pre>';
        $modalContent .= '</div>';
    } else {
        $modalContent .= '<div class="alert alert-warning">';
        $modalContent .= '<strong>Noch keine Texte vorhanden.</strong><br>';
        $modalContent .= 'Bitte synchronisiere die Texte über den "Test"-Button bei der jeweiligen Domain.';
        $modalContent .= '</div>';
    }
} catch (Exception $e) {
    $modalContent .= '<div class="alert alert-danger">';
    $modalContent .= 'Fehler beim Laden der Texte: ' . rex_escape($e->getMessage());
    $modalContent .= '</div>';
}

$modalContent .= '<h5>Verwendung</h5>';
$modalContent .= '<p>' . $addon->i18n('outputfilter_pattern') . '</p>';
$modalContent .= '<ul>';
$modalContent .= '<li>' . $addon->i18n('outputfilter_type') . '</li>';
$modalContent .= '<li>' . $addon->i18n('outputfilter_identifier') . '</li>';
$modalContent .= '<li>' . $addon->i18n('outputfilter_lang') . '</li>';
$modalContent .= '</ul>';
$modalContent .= '<h5>Beispiele</h5>';
$modalContent .= '<pre style="background: #f5f5f5; padding: 10px;"><code>';
$modalContent .= '&lt;!-- Datenschutzerklärung --&gt;' . "\n";
$modalContent .= '##ER-PRIVACY:example.com:de##' . "\n\n";
$modalContent .= '&lt;!-- Impressum --&gt;' . "\n";
$modalContent .= '##ER-IMPRINT:example.com:de##' . "\n\n";
$modalContent .= '&lt;!-- Datenschutz Social Media --&gt;' . "\n";
$modalContent .= '##ER-PRIVACY-SOCIAL:example.com:en##' . "\n\n";
$modalContent .= '&lt;!-- In Modulen oder Templates --&gt;' . "\n";
$modalContent .= '&lt;div class="legal-text"&gt;' . "\n";
$modalContent .= '    &lt;h2&gt;Datenschutzerklärung&lt;/h2&gt;' . "\n";
$modalContent .= '    ##ER-PRIVACY:example.com:de##' . "\n";
$modalContent .= '&lt;/div&gt;';
$modalContent .= '</code></pre>';
$modalContent .= '</div>';

// Modal HTML
$modal = '
<div class="modal fade" id="outputfilterModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">' . $addon->i18n('outputfilter_title') . '</h4>
            </div>
            <div class="modal-body">
                ' . $modalContent . '
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>
';

echo '<div style="margin-top: 20px;">
    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#outputfilterModal">
        <i class="rex-icon fa-code"></i> Verfügbare Platzhalter anzeigen
    </button>
</div>';
echo $modal;

