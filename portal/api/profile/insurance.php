<?php

use Symfony\Component\VarDumper\VarDumper;

header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once("$srcdir/report.inc.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');
require_once(__DIR__ . '/helper.php');

$pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);


if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Get patient data
$tertiaryInsurances = getRecInsuranceData($pid, "tertiary");
$secondaryInsurances = getRecInsuranceData($pid, "secondary");
$primaryInsurances = getRecInsuranceData($pid, "primary");

$response = [
    "primary" => $primaryInsurances ?? [],
    "tertiary" => $tertiaryInsurances ?? [],
    "secondary" => $secondaryInsurances ?? [],
];

// echo json_encode($response);

$jsonOutput = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
if ($jsonOutput === false) {
    echo json_encode(['error' => 'JSON encoding error', 'details' => json_last_error_msg()]);
} else {
    echo $jsonOutput;
}
exit();
