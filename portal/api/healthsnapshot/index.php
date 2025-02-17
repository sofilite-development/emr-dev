<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');

$action = $_GET['action'] ?? null;
$pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit();
}

switch ($action) {
    case 'get_health_snapshot':
        echo json_encode(getHealthSnapshot($pid));
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Fetch Health Snapshot for a Patient
 */
function getHealthSnapshot($pid)
{
    global $sql;

    return [
        'patientID' => $pid,
        'immunizationRecords' => getImmunizationRecords($pid),
        // 'medications' => getMedications($pid),
        // 'prescriptions' => getPrescriptions($pid),
        // 'allergies' => getAllergies($pid),
        // 'labResults' => getLabResults($pid)
    ];
}

/**
 * Fetch Immunization Records
 */
function getImmunizationRecords($pid)
{
    $query = "SELECT im.*, cd.code_text, DATE(administered_date) AS administered_date,
        DATE_FORMAT(administered_date,'%m/%d/%Y') AS administered_formatted, lo.title as route_of_administration,
        u.title, u.fname, u.mname, u.lname, u.npi, u.street, u.streetb, u.city, u.state, u.zip, u.phonew1,
        f.name, f.phone, lo.notes as route_code
        FROM immunizations AS im
        LEFT JOIN codes AS cd ON cd.code = im.cvx_code
        JOIN code_types AS ctype ON ctype.ct_key = 'CVX' AND ctype.ct_id=cd.code_type
        LEFT JOIN list_options AS lo ON lo.list_id = 'drug_route' AND lo.option_id = im.route
        LEFT JOIN users AS u ON u.id = im.administered_by_id
        LEFT JOIN facility AS f ON f.id = u.facility_id
        WHERE im.patient_id=?";
    $result = sqlStatement($query, array($pid));

    $immunRecords = [];
    while ($row = sqlFetchArray($result)) {
        $immunRecords[] = $row;
    }

    return $immunRecords;
}

/**
 * Fetch Medications
 */
function getMedications($pid)
{
    $query = "SELECT drug, dosage, period, refills, provider_id FROM prescriptions WHERE patient_id=?";
    $result = sqlStatement($query, array($pid));

    $medications = [];
    while ($row = sqlFetchArray($result)) {
        $medications[] = $row;
    }

    return $medications;
}

/**
 * Fetch Prescriptions
 */
function getPrescriptions($pid)
{
    $query = "SELECT drug, dosage, period, refills, provider_id FROM prescriptions WHERE patient_id=?";
    $result = sqlStatement($query, array($pid));

    $prescriptions = [];
    while ($row = sqlFetchArray($result)) {
        $prescriptions[] = $row;
    }

    return $prescriptions;
}

/**
 * Fetch Allergies
 */
function getAllergies($pid)
{
    $query = "SELECT allergy, reaction FROM lists WHERE pid=? AND type='allergy'";
    $result = sqlStatement($query, array($pid));

    $allergies = [];
    while ($row = sqlFetchArray($result)) {
        $allergies[] = $row;
    }

    return $allergies;
}

/**
 * Fetch Lab Results
 */
function getLabResults($pid)
{
    $query = "SELECT lo.inc_date, lo.result_text, lo.facility FROM procedure_order AS lo WHERE lo.patient_id=?";
    $result = sqlStatement($query, array($pid));

    $labResults = [];
    while ($row = sqlFetchArray($result)) {
        $labResults[] = $row;
    }

    return $labResults;
}
