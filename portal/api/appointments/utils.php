<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');
require_once(__DIR__ . '/helper.php');

// Get `pid` from query params (e.g., ?pid=123)
$pid = $_GET['pid'] ?? null;
$response = [];
$requestedFor = $_GET["for"] ?? null;

if (!$requestedFor) {
    echo json_encode($response);
    exit();
}

if (!$pid) {
    $pid = $_SESSION['pid'] ?? null;
}

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit();
}

if ($requestedFor === "visits") {
    $visitCategories = getVisitCategories();
    $response = $visitCategories;
} elseif ($requestedFor === "providers") {
    $providerList = getProviderList();
    $response = $providerList;
} elseif ($requestedFor === "slots") {
    $providerId = $_GET['provider_id'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $categoryId = $_GET['category_id'] ?? null;

    $availableSlots = getAvailableAppointments($providerId, $startDate, $categoryId);
    $response = $availableSlots;
}

echo json_encode($response);
exit();
