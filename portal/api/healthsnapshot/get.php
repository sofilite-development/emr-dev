<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');
require_once(__DIR__ . '/helper.php');

$pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit();
}

$response = [];

$response = getHealthSnapshot($pid);

echo json_encode($response);

/**
 * Fetch Health Snapshot for a Patient
 */
function getHealthSnapshot($pid)
{
    global $sql;

    return [
        'patientID' => $pid,
        'immunizationRecords' => getImmunizationRecords($pid),
        'medications' => getMedications($pid),
        'prescriptions' => getPrescriptions($pid),
        'allergies' => getAllergies($pid),
        'labResults' => getLabResults($pid),
        'problems' => getProblems($pid),
    ];
}
