<?php
header('Content-Type: application/json'); // Ensure JSON response
require_once(__DIR__ . "/../verify_session.php");
require_once(__DIR__ . '/../../library/appointments.inc.php');

// Get `pid` from query params (e.g., ?pid=123)
$pid = $_GET['pid'] ?? null;

if (!$pid) {
    $pid = $_SESSION['pid'] ?? null;
}

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit();
}

$current_date2 = date('Y-m-d');
$apptLimit = 10;
$appts = fetchNextXAppts($current_date2, $pid, $apptLimit);
$past_appts = fetchXPastAppts($pid, 10);

// Prepare the response
$response = [
    'past_appointments' => $past_appts,
    'upcoming_appointments' => $appts
];

echo json_encode($response);
exit();
