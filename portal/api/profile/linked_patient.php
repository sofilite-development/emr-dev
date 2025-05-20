<?php

use Symfony\Component\VarDumper\VarDumper;

header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once(__DIR__ . '/helper.php');

$response = [
    "error" => "",
    "data" => [],
    "success" => true
];

$pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);

if (!$pid) {
    $response["error"] = "Missing patient ID";
    $response["success"] = false;
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Get patient data
$patients = getLinkedPatient($pid);

if (!$patients) {
    $response["error"] = "No data found for patient";
    $response["success"] = false;
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Return the data as JSON
$response["data"] = $patients;

$jsonOutput = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
if ($jsonOutput === false) {
    echo json_encode(['error' => 'JSON encoding error', 'details' => json_last_error_msg()]);
} else {
    echo $jsonOutput;
}
exit();
