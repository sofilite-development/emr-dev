<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once(__DIR__ . '/helper.php');


$pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);
$encounterId = $_GET['encounterId'] ?? null;

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Get encounters data
$encounters = $encounterId ? getEncounterById($encounterId) : getAllEncounters($pid);

$response = json_encode($encounters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
if ($response === false) {
    echo json_encode(['error' => 'JSON encoding error', 'details' => json_last_error_msg()]);
} else {
    echo $response;
}
exit();
