<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');
require_once(__DIR__ . '/helper.php');
require_once(__DIR__ . "/../../../interface/globals.php");

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$pid = $_GET['pid'] ?? $_POST['patient_id'] ?? ($_SESSION['pid'] ?? null);

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit();
}

switch ($action) {
    case 'get_appointments':
        $current_date2 = date('Y-m-d');
        $apptLimit = 10;
        $appts = fetchNextXAppts($current_date2, $pid, $apptLimit);
        $past_appts = fetchXPastAppts($pid, 10);

        echo json_encode([
            'past_appointments' => $past_appts,
            'upcoming_appointments' => $appts
        ]);
        break;

    case 'create_appointment':
        echo json_encode(createAppointment(
            $pid,
            $_POST['provider_id'] ?? null,
            $_POST['category_id'] ?? null,
            $_POST['date'] ?? null,
            $_POST['start_time'] ?? null,
            $_POST['duration'] ?? null,
            $_POST['comments'] ?? ''
        ));
        break;

    case 'update_appointment':
        echo json_encode(updateAppointment(
            $_POST['appointment_id'] ?? null,
            $pid,
            $_POST['provider_id'] ?? null,
            $_POST['category_id'] ?? null,
            $_POST['date'] ?? null,
            $_POST['start_time'] ?? null,
            $_POST['duration'] ?? null,
            $_POST['comments'] ?? ''
        ));
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
