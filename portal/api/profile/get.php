<?php

use Symfony\Component\VarDumper\VarDumper;

header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');
require_once(__DIR__ . '/helper.php');


$pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Get patient data
$patientData = getPatientProfileData($pid);

if (!$patientData) {
    echo json_encode(['error' => 'No data found for patient'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Return the data as JSON
$response =  $patientData;

$jsonOutput = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
if ($jsonOutput === false) {
    echo json_encode(['error' => 'JSON encoding error', 'details' => json_last_error_msg()]);
} else {
    echo $jsonOutput;
}
exit();
