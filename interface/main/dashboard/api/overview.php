<?php

/**
 * Overview API Endpoint
 * Returns recently assigned tasks and clinical to-do's for the logged-in provider
 */

// Set content type to JSON
header('Content-Type: application/json');

// Require necessary files
require_once (__DIR__ . '/../../../globals.php');
require_once 'cors.php';

// Check if user is logged in
if (empty($_SESSION['authUser'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $username = $_SESSION['authUser'];
    $user = sqlQuery('SELECT id, username, fname, lname, authorized FROM users WHERE username = ?', array($username));

    if (!$user) {
        throw new Exception('User not found');
    }

    $provider_id = $user['id'];
    $data = [];

    // Get recent patient tracker items
    $trackerQuery = sqlStatement(
        'SELECT pt.id, pt.apptdate, pte.status as element, pt.encounter, pt.lastseq,
       pd.fname, pd.lname, pd.pid
FROM patient_tracker pt
LEFT JOIN patient_tracker_element pte ON pte.pt_tracker_id = pt.id
LEFT JOIN patient_data pd ON pd.pid = pt.pid
WHERE pt.original_user = ?
AND pt.apptdate >= CURDATE()
ORDER BY pt.apptdate DESC
LIMIT 10',
        array($provider_id)
    );

    $patientTrackers = [];
    while ($row = sqlFetchArray($trackerQuery)) {
        $patientTrackers[] = [
            'id' => $row['id'],
            'apptdate' => $row['apptdate'],
            'element' => $row['element'],
            'encounter' => $row['encounter'],
            'patient' => [
                'pid' => $row['pid'],
                'name' => $row['fname'] . ' ' . $row['lname']
            ]
        ];
    }

    // Get pending procedure orders
    $ordersQuery = sqlStatement(
        "SELECT po.procedure_order_id, po.date_ordered, po.patient_id, 
       po.provider_id, po.order_status as status, po.order_priority as priority,
       pd.fname, pd.lname
FROM procedure_order po
JOIN patient_data pd ON pd.pid = po.patient_id
WHERE po.provider_id = ?
AND po.order_status IN ('pending', 'routed', 'in_progress')
ORDER BY po.date_ordered DESC
LIMIT 20",
        array($provider_id)
    );

    $procedureOrders = [];
    while ($row = sqlFetchArray($ordersQuery)) {
        $procedureOrders[] = [
            'order_id' => $row['procedure_order_id'],
            'date_ordered' => $row['date_ordered'],
            'status' => $row['status'],
            'priority' => $row['priority'],
            'patient' => [
                'pid' => $row['patient_id'],
                'name' => $row['fname'] . ' ' . $row['lname']
            ]
        ];
    }

    // Get calendar events for the next 7 days
    $calendarQuery = sqlStatement(
        'SELECT 
            pc_eid as event_id,
            pc_title as title,
            pc_eventDate as event_date,
            pc_startTime as start_time,
            pc_endTime as end_time
        FROM openemr_postcalendar_events
        WHERE pc_aid = ?
        AND pc_eventDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY pc_eventDate, pc_startTime',
        array($provider_id)
    );

    $calendarEvents = [];
    while ($row = sqlFetchArray($calendarQuery)) {
        $calendarEvents[] = [
            'id' => $row['event_id'],
            'title' => $row['title'],
            'date' => $row['event_date'],
            'startTime' => $row['start_time'],
            'endTime' => $row['end_time'],
            'dateTime' => $row['event_date'] . ' ' . $row['start_time']
        ];
    }

    $data = [
        'patient_trackers' => $patientTrackers,
        'procedure_orders' => $procedureOrders,
        'calendar_events' => $calendarEvents,
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    // Return the data as JSON
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>