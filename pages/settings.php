<?php
$addon = rex_addon::get('erecht24');

use \FriendsOfRedaxo\eRecht24\eRecht24Client;

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
if (rex_get('func', 'string') === 'delete' && ($domain = rex_get('domain', 'string'))) {
    // CSRF protection for delete action
    if (!$csrfToken->isValid()) {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        try {
            eRecht24Client::unregister($domain);
            echo rex_view::success($addon->i18n('domain_deleted'));
        } catch (Throwable $e) {
            echo rex_view::error($e->getMessage());
        }
    }
}

// Add form
$formElements = [];

// Domain field
$n = [];
$n['label'] = '<label for="domain">' . $addon->i18n('domain') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="domain" name="domain" value="'.rex_escape(rex_server('SERVER_NAME', 'string', '')).'">';
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

if (count($list) === 0) {
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
        $listContent .= '<br><a href="' . rex_url::currentBackendPage(['func' => 'delete', 'domain' => $item['domain'], '_csrf_token' => $csrfToken->getValue()]) . '" class="rex-link-expanded" data-confirm="' . $addon->i18n('delete_confirm') . '">';
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
