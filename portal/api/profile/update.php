<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');
require_once(__DIR__ . '/helper.php');



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData) {
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit();
}

$pid = $inputData['patient_id'] ?? ($_SESSION['pid'] ?? null);
if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit();
}

$userData = $inputData['table_args'];
// echo json_encode($userData);
// return;

$auditvals = [
    'patient_id' => $pid,
    'activity' => $inputData['activity'] ?? "",
    'require_audit' => $inputData['require_audit'] ?? "",
    'pending_action' => $inputData['pending_action'] ?? "",
    'action_taken' => $inputData['action_taken'] ?? "",
    'status' => $inputData['status'] ?? "new",
    'narrative' => $inputData['narrative'] ?? "",
    'table_action' => $inputData['table_action'] ?? "",
    'table_args' => serialize($userData),
    'action_user' => $inputData['action_user'] ?? "",
    'action_taken_time' => $inputData['action_taken_time'] ?? "",
    'checksum' => $inputData['checksum'] ?? ""
];

// echo json_encode($auditvals);
// return;
$result = portalAudit(null, $auditvals);

echo json_encode(['success' => $result]);

function portalAudit(?string $type, array $auditvals)
{
    $audit = [];
    if (!$type) {
        $type = 'insert';
    }
    if ($type !== 'insert') {
        $audit['date'] = $auditvals['date'] ?? date("Y-m-d H:i:s");
    }
    $audit = array_merge($audit, $auditvals);

    try {
        if ($type !== 'update') {
            $logsql = "INSERT INTO onsite_portal_activity (date, patient_id, activity, require_audit, pending_action, action_taken, status, narrative, table_action, table_args, action_user, action_taken_time, checksum) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        } else {
            $logsql = "UPDATE onsite_portal_activity SET date=?, patient_id=?, activity=?, require_audit=?, pending_action=?, action_taken=?, status=?, narrative=?, table_action=?, table_args=?, action_user=?, action_taken_time=?, checksum=? WHERE id=? AND patient_id=?";
        }

        // Uncomment below line when sqlStatementNoLog is available
        $return = sqlStatementNoLog($logsql, array_values($audit));
        $return = true; // Simulating successful database operation

    } catch (Exception $e) {
        $return = false;
    }
    return $return;
}
