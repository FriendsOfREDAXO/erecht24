<?php

$addon = rex_addon::get('erecht24');
$id = rex_request('id', 'int');

// Get domain info
$sql = rex_sql::factory();
$domain = $sql->setQuery('SELECT * FROM ' . rex::getTable('erecht24') . ' WHERE id = :id', ['id' => $id])->getArray();
if (empty($domain)) {
    echo rex_view::error($addon->i18n('domain_not_found'));
    return;
}
$domain = $domain[0];

// Get texts
$texts = rex_sql::factory()
  ->setQuery('SELECT * FROM ' . rex::getTable('erecht24_texts') . ' WHERE domain = :domain ORDER BY type ASC', ['domain' => $domain['domain']])
  ->getArray();

// Back button
$content = '<div class="btn-toolbar" style="margin-bottom: 20px;">';
$content .= '<a class="btn btn-default" href="' . rex_url::currentBackendPage(['page' => 'erecht24/settings']) . '">';
$content .= '<i class="rex-icon fa-arrow-left"></i> ' . rex_i18n::msg('back') . '</a>';
$content .= '</div>';

// Domain Info Box
$content .= '<div class="panel panel-default">';
$content .= '<h4>Domain: ' . rex_escape($domain['domain']) . ' (ID: ' . $domain['id'] . ')</h4>';
$content .= '<p class="help-block"><strong>' . $addon->i18n('code_example') . ':</strong></p>';
$content .= '<pre><code>use FriendsOfRedaxo\eRecht24\eRecht24;

if (eRecht24::hasText(' . $domain['id'] . ', \'imprint\')) {
  echo eRecht24::getText(' . $domain['id'] . ', \'imprint\');
}</code></pre>';
$content .= '</div>';

if (count($texts) > 0) {
    // Tab Navigation
    $content .= '<div class="rex-page-nav"><ul class="nav nav-tabs" role="tablist">';
    foreach ($texts as $index => $text) {
        $content .= '<li role="presentation" ' . (0 === $index ? 'class="active"' : '') . '>';
        $content .= '<a href="#tab-' . $text['type'] . '" aria-controls="tab-' . $text['type'] . '" role="tab" data-toggle="tab">';
        $content .= rex_escape($text['type']);
        $content .= '</a></li>';
    }
    $content .= '</ul></div>';

    // Tab Content
    $content .= '<div class="tab-content">';
    foreach ($texts as $index => $text) {
        $content .= '<div role="tabpanel" class="tab-pane ' . (0 === $index ? 'active' : '') . '" id="tab-' . $text['type'] . '">';

        // Code example for this type
        $content .= '<div class="panel panel-default" style="margin-top: 20px;">';
        $content .= '<pre><code>use FriendsOfRedaxo\eRecht24\eRecht24;

echo eRecht24::getText(' . $domain['id'] . ', \'' . $text['type'] . '\');</code></pre>';
        $content .= '</div>';

        // Last fetch date
        if ($text['last_fetch']) {
            $content .= '<p><strong>' . $addon->i18n('last_fetch') . ':</strong> ';
            $content .= rex_formatter::strftime($text['last_fetch'], 'datetime') . '</p>';
        }

        // Language Accordion
        $content .= '<div class="panel-group" id="accordion-' . $text['type'] . '">';

        // German version
        $content .= '<div class="panel panel-default">';
        $content .= '<div class="panel-heading">';
        $content .= '<h4 class="panel-title">';
        $content .= '<a data-toggle="collapse" data-parent="#accordion-' . $text['type'] . '" href="#de-' . $text['type'] . '" aria-expanded="true">';
        $content .= 'Deutsch';
        $content .= '</a>';
        $content .= '</h4>';
        $content .= '</div>';
        $content .= '<div id="de-' . $text['type'] . '" class="panel-collapse collapse in">';
        $content .= '<div class="panel-body">';
        $content .= $text['html_de'];
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // English version if available
        if (!empty($text['html_en'])) {
            $content .= '<div class="panel panel-default">';
            $content .= '<div class="panel-heading">';
            $content .= '<h4 class="panel-title">';
            $content .= '<a data-toggle="collapse" data-parent="#accordion-' . $text['type'] . '" href="#en-' . $text['type'] . '" class="collapsed">';
            $content .= 'Englisch';
            $content .= '</a>';
            $content .= '</h4>';
            $content .= '</div>';
            $content .= '<div id="en-' . $text['type'] . '" class="panel-collapse collapse">';
            $content .= '<div class="panel-body">';
            $content .= $text['html_en'];
            $content .= '</div>';
            $content .= '</div>';
            $content .= '</div>';
        }

        $content .= '</div>'; // End accordion
        $content .= '</div>'; // End tab panel
    }
    $content .= '</div>'; // End tab content
} else {
    $content .= '<div class="alert alert-warning">' . $addon->i18n('no_texts') . '</div>';
}

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('preview'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
