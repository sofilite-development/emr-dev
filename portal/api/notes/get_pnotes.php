<?php

header('Content-Type: application/json');

require_once(__DIR__ . '/../../verify_session.php');
require_once($GLOBALS['srcdir'] . '/pnotes.inc.php');
require_once($GLOBALS['srcdir'] . '/patient.inc.php');
require_once($GLOBALS['srcdir'] . '/options.inc.php');

$pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);
$active = $_GET['active'] ?? 'all'; // "1", "0", or "all"
$docid = isset($_GET['docid']) ? intval($_GET['docid']) : 0;
$orderid = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;

// Pagination
$perPage = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($perPage !== null) ? ($page - 1) * $perPage : 0;

// Sanity check
if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit;
}

$cols = 'id,date,body,user,activity,title,assigned_to,message_status,update_date,update_by';

// Determine limit and offset
$limit = $perPage ?? 'all';

// Fetch pnotes
$pnotes = getPnotesByDate(
    '',         // date (blank = all)
    $active,
    $cols,
    $pid,
    $limit,
    $offset,
    '',         // assigned_to
    $docid,
    '',         // message_status
    $orderid
);

// Total count if paginated
$total = null;
if ($perPage !== null) {
    $countCols = 'count(*) as total';
    $totalResult = getPnotesByDate('', $active, $countCols, $pid, 'all', 0, '', $docid, '', $orderid);
    $total = $totalResult[0]['total'] ?? count($pnotes);
}

// Format output
$response = array_map(function ($note) {
    return [
        'id' => $note['id'],
        'date' => $note['date'],
        'body' => $note['body'],
        'user' => $note['user'],
        'active' => (bool)$note['activity'],
        'title' => $note['title'],
        'assigned_to' => $note['assigned_to'],
        'message_status' => $note['message_status'],
        'update_date' => $note['update_date'],
        'update_by' => $note['update_by'],
    ];
}, $pnotes);

// Final output
$output = ['notes' => $response];
if ($total !== null) {
    $output['pagination'] = [
        'total' => intval($total),
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => ceil($total / $perPage)
    ];
}

echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
