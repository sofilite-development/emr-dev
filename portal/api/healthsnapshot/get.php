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

$type = $_GET["type"] ?? null;
if ($type == "") {
    $type = null;
}
$response = [];

$response = getHealthSnapshot($pid, $type);

echo json_encode($response);

/**
 * Fetch Health Snapshot for a Patient
 */
function getHealthSnapshot($pid, $requestFor)
{
    $response = [];
    $response['patientID'] = $pid;

    switch ($requestFor) {
        case "immunizationRecords":
            $response["immunizationRecords"] = getImmunizationRecords($pid);
            break;
        case "problems":
            $response["problems"] = getProblems($pid);
            break;
        case "labResults":
            $response["labResults"] = getLabResults($pid);
            break;
        case "medications":
            $response["medications"] = getMedications($pid);
            break;
        case "allergies":
            $response["allergies"] = getAllergies($pid);
            break;
        case "prescriptions":
            $response["prescriptions"] = getPrescriptions($pid);
            break;
        default:
            $response =  [
                ...$response,
                'immunizationRecords' => getImmunizationRecords($pid),
                'medications' => getMedications($pid),
                'prescriptions' => getPrescriptions($pid),
                'allergies' => getAllergies($pid),
                'labResults' => getLabResults($pid),
                'problems' => getProblems($pid),
            ];
    }

    return $response;
}
