<?php

$addon = rex_addon::get('erecht24');

// Get filter
$filterDomain = rex_get('domain', 'string', '');
$filterStatus = rex_get('status', 'string', '');

// Build query
$where = [];
$params = [];

if ($filterDomain) {
    $where[] = 'domain = :domain';
    $params['domain'] = $filterDomain;
}

if ($filterStatus) {
    $where[] = 'status = :status';
    $params['status'] = $filterStatus;
}

$whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Get logs
$logs = rex_sql::factory()
    ->setQuery('SELECT * FROM ' . rex::getTable('erecht24_webhook_log') . $whereClause . ' ORDER BY createdate DESC LIMIT 100', $params)
    ->getArray();

// Get unique domains for filter
$domains = rex_sql::factory()
    ->setQuery('SELECT DISTINCT domain FROM ' . rex::getTable('erecht24_webhook_log') . ' ORDER BY domain')
    ->getArray();

// Filter form
$content = '<form method="get" class="form-inline">';
$content .= '<input type="hidden" name="page" value="erecht24/webhook_log">';
$content .= '<div class="form-group">';
$content .= '<label>' . $addon->i18n('filter_domain') . ':</label> ';
$content .= '<select name="domain" class="form-control">';
$content .= '<option value="">' . $addon->i18n('all') . '</option>';
foreach ($domains as $d) {
    $selected = $filterDomain === $d['domain'] ? ' selected' : '';
    $content .= '<option value="' . rex_escape($d['domain']) . '"' . $selected . '>' . rex_escape($d['domain']) . '</option>';
}
$content .= '</select>';
$content .= '</div> ';
$content .= '<div class="form-group">';
$content .= '<label>' . $addon->i18n('filter_status') . ':</label> ';
$content .= '<select name="status" class="form-control">';
$content .= '<option value="">' . $addon->i18n('all') . '</option>';
$content .= '<option value="success"' . ($filterStatus === 'success' ? ' selected' : '') . '>' . $addon->i18n('success') . '</option>';
$content .= '<option value="error"' . ($filterStatus === 'error' ? ' selected' : '') . '>' . $addon->i18n('error') . '</option>';
$content .= '</select>';
$content .= '</div> ';
$content .= '<button type="submit" class="btn btn-primary">' . $addon->i18n('filter') . '</button> ';
$content .= '<a href="' . rex_url::currentBackendPage() . '" class="btn btn-default">' . $addon->i18n('reset') . '</a>';
$content .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('webhook_log_filter'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

// Statistics
$stats = rex_sql::factory()
    ->setQuery('
        SELECT 
            status,
            COUNT(*) as count,
            AVG(response_time) as avg_time,
            MAX(response_time) as max_time,
            MIN(response_time) as min_time
        FROM ' . rex::getTable('erecht24_webhook_log') . '
        GROUP BY status
    ')
    ->getArray();

if (!empty($stats)) {
    $statsContent = '<div class="row">';
    foreach ($stats as $stat) {
        $statusClass = $stat['status'] === 'success' ? 'success' : 'danger';
        $statsContent .= '<div class="col-sm-6">';
        $statsContent .= '<div class="panel panel-' . $statusClass . '">';
        $statsContent .= '<div class="panel-heading"><strong>' . ucfirst($stat['status']) . '</strong></div>';
        $statsContent .= '<div class="panel-body">';
        $statsContent .= '<p><strong>' . $addon->i18n('total') . ':</strong> ' . $stat['count'] . '</p>';
        $statsContent .= '<p><strong>' . $addon->i18n('avg_response_time') . ':</strong> ' . round($stat['avg_time']) . ' ms</p>';
        $statsContent .= '<p><strong>' . $addon->i18n('min_response_time') . ':</strong> ' . round($stat['min_time']) . ' ms</p>';
        $statsContent .= '<p><strong>' . $addon->i18n('max_response_time') . ':</strong> ' . round($stat['max_time']) . ' ms</p>';
        $statsContent .= '</div>';
        $statsContent .= '</div>';
        $statsContent .= '</div>';
    }
    $statsContent .= '</div>';

    $fragment = new rex_fragment();
    $fragment->setVar('title', $addon->i18n('webhook_statistics'));
    $fragment->setVar('body', $statsContent, false);
    echo $fragment->parse('core/page/section.php');
}

// Log table
$logContent = '<div class="table-responsive">';
$logContent .= '<table class="table table-striped table-hover">';
$logContent .= '<thead><tr>';
$logContent .= '<th>' . $addon->i18n('timestamp') . '</th>';
$logContent .= '<th>' . $addon->i18n('domain') . '</th>';
$logContent .= '<th>' . $addon->i18n('type') . '</th>';
$logContent .= '<th>' . $addon->i18n('status') . '</th>';
$logContent .= '<th>' . $addon->i18n('response_time') . '</th>';
$logContent .= '<th>' . $addon->i18n('error_message') . '</th>';
$logContent .= '</tr></thead>';
$logContent .= '<tbody>';

if (empty($logs)) {
    $logContent .= '<tr><td colspan="6">' . $addon->i18n('no_logs') . '</td></tr>';
} else {
    foreach ($logs as $log) {
        $statusClass = $log['status'] === 'success' ? 'success' : 'danger';
        $logContent .= '<tr class="' . $statusClass . '">';
        $logContent .= '<td>' . rex_formatter::strftime($log['createdate'], 'datetime') . '</td>';
        $logContent .= '<td>' . rex_escape($log['domain']) . '</td>';
        $logContent .= '<td>' . rex_escape($log['type']) . '</td>';
        $logContent .= '<td><span class="label label-' . $statusClass . '">' . rex_escape($log['status']) . '</span></td>';
        $logContent .= '<td>' . $log['response_time'] . ' ms</td>';
        $logContent .= '<td>' . ($log['error_message'] ? rex_escape($log['error_message']) : '-') . '</td>';
        $logContent .= '</tr>';
    }
}

$logContent .= '</tbody></table>';
$logContent .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('webhook_log') . ' (' . $addon->i18n('last_100') . ')');
$fragment->setVar('body', $logContent, false);
echo $fragment->parse('core/page/section.php');
