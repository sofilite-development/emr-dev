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

function getStatistics($provider_id)
{
    global $sqlconf;
    $statistics = [
        'today' => [
            'total' => 0,
            'scheduled' => 0,
            'checked_in' => 0,
            'completed' => 0,
            'no_show' => 0,
            'cancelled' => 0
        ],
        'week' => [
            'total' => 0,
            'scheduled' => 0,
            'checked_in' => 0,
            'completed' => 0,
            'no_show' => 0,
            'cancelled' => 0
        ],
        'month' => [
            'total' => 0,
            'scheduled' => 0,
            'checked_in' => 0,
            'completed' => 0,
            'no_show' => 0,
            'cancelled' => 0
        ]
    ];

    try {
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = date('Y-m-01');

        // Query for today's stats
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN pc_apptstatus = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN pc_apptstatus = 'Checked In' THEN 1 ELSE 0 END) as checked_in,
                    SUM(CASE WHEN pc_apptstatus = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN pc_apptstatus = 'No Show' THEN 1 ELSE 0 END) as no_show,
                    SUM(CASE WHEN pc_apptstatus = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
                  FROM openemr_postcalendar_events 
                  WHERE pc_eventDate = ? 
                  AND pc_aid = ?";

        $result = sqlQueryNoLog($query, array($today, $provider_id));
        if ($result) {
            $statistics['today'] = [
                'total' => (int) $result['total'],
                'scheduled' => (int) $result['scheduled'],
                'checked_in' => (int) $result['checked_in'],
                'completed' => (int) $result['completed'],
                'no_show' => (int) $result['no_show'],
                'cancelled' => (int) $result['cancelled']
            ];
        }

        // Query for this week's stats
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN pc_apptstatus = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN pc_apptstatus = 'Checked In' THEN 1 ELSE 0 END) as checked_in,
                    SUM(CASE WHEN pc_apptstatus = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN pc_apptstatus = 'No Show' THEN 1 ELSE 0 END) as no_show,
                    SUM(CASE WHEN pc_apptstatus = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
                  FROM openemr_postcalendar_events 
                  WHERE pc_eventDate BETWEEN ? AND ?
                  AND pc_aid = ?";

        $result = sqlQueryNoLog($query, array($week_start, $today, $provider_id));
        if ($result) {
            $statistics['week'] = [
                'total' => (int) $result['total'],
                'scheduled' => (int) $result['scheduled'],
                'checked_in' => (int) $result['checked_in'],
                'completed' => (int) $result['completed'],
                'no_show' => (int) $result['no_show'],
                'cancelled' => (int) $result['cancelled']
            ];
        }

        // Query for this month's stats
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN pc_apptstatus = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN pc_apptstatus = 'Checked In' THEN 1 ELSE 0 END) as checked_in,
                    SUM(CASE WHEN pc_apptstatus = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN pc_apptstatus = 'No Show' THEN 1 ELSE 0 END) as no_show,
                    SUM(CASE WHEN pc_apptstatus = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
                  FROM openemr_postcalendar_events 
                  WHERE pc_eventDate BETWEEN ? AND ?
                  AND pc_aid = ?";

        $result = sqlQueryNoLog($query, array($month_start, $today, $provider_id));
        if ($result) {
            $statistics['month'] = [
                'total' => (int) $result['total'],
                'scheduled' => (int) $result['scheduled'],
                'checked_in' => (int) $result['checked_in'],
                'completed' => (int) $result['completed'],
                'no_show' => (int) $result['no_show'],
                'cancelled' => (int) $result['cancelled']
            ];
        }

        // Additional stats for appointments by status
        $query = 'SELECT 
                    pc_apptstatus as status,
                    COUNT(*) as count
                  FROM openemr_postcalendar_events 
                  WHERE pc_eventDate >= ?
                  AND pc_aid = ?
                  GROUP BY pc_apptstatus';

        $result = sqlStatementNoLog($query, array(date('Y-m-d', strtotime('-30 days')), $provider_id));
        $appointments_by_status = [];
        while ($row = sqlFetchArray($result)) {
            $appointments_by_status[strtolower(str_replace(' ', '_', $row['status']))] = (int) $row['count'];
        }

        // Recent appointments
        $query = 'SELECT 
                    p.fname, p.lname, p.pid,
                    e.pc_eventDate, e.pc_startTime, e.pc_apptstatus, e.pc_eid
                  FROM openemr_postcalendar_events e
                  JOIN patient_data p ON e.pc_pid = p.pid
                  WHERE e.pc_aid = ?
                  ORDER BY e.pc_eventDate DESC, e.pc_startTime DESC
                  LIMIT 5';

        $recent_appointments = [];
        $result = sqlStatementNoLog($query, array($provider_id));
        while ($row = sqlFetchArray($result)) {
            $recent_appointments[] = [
                'id' => $row['pc_eid'],
                'patient_name' => $row['fname'] . ' ' . $row['lname'],
                'pid' => $row['pid'],
                'date' => $row['pc_eventDate'],
                'time' => $row['pc_startTime'],
                'status' => $row['pc_apptstatus']
            ];
        }

        return [
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'appointments_by_status' => $appointments_by_status,
                'recent_appointments' => $recent_appointments
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function getPatientStats($provider_id)
{
    $query = '
        WITH first_visit AS (
            SELECT pc_pid, MIN(pc_eventDate) AS first_seen
            FROM openemr_postcalendar_events
            WHERE pc_aid = ? AND pc_pid IS NOT NULL
            GROUP BY pc_pid
        )
        SELECT
            COALESCE(SUM(first_seen >= CURDATE() - INTERVAL 30 DAY), 0) AS new_patients,
            COALESCE(SUM(first_seen < CURDATE() - INTERVAL 30 DAY), 0) AS repeat_patients
        FROM first_visit
    ';

    $result = sqlQueryNoLog($query, array($provider_id));

    if ($result) {
        return [
            'success' => true,
            'data' => [
                'new_patients' => (int) $result['new_patients'],
                'repeat_patients' => (int) $result['repeat_patients'],
                'total_patients' => (int) $result['new_patients'] + (int) $result['repeat_patients']
            ]
        ];
    }

    return [
        'success' => false,
        'error' => 'Failed to fetch patient statistics'
    ];
}

function getMessages($provider_id)
{
    $messagesQuery = sqlStatement(
        'SELECT 
        m.id as message_id,
        m.title,
        m.body,
        m.date as message_date,
        m.sender_id,
        m.sender_name,
        m.recipient_id,
        m.recipient_name,
        m.message_status,
        pd1.fname as sender_fname,
        pd1.lname as sender_lname,
        pd2.fname as recipient_fname,
        pd2.lname as recipient_lname
    FROM onsite_mail m
    LEFT JOIN patient_data pd1 ON pd1.pid = m.sender_id
    LEFT JOIN patient_data pd2 ON pd2.pid = m.recipient_id
    WHERE (m.sender_id = ? OR m.recipient_id = ?)
    AND m.deleted = 0
    ORDER BY m.date DESC
    LIMIT 10',
        array($provider_id, $provider_id)
    );

    $messages = [];
    while ($row = sqlFetchArray($messagesQuery)) {
        $messages[] = [
            'id' => $row['message_id'],
            'title' => $row['title'],
            'body' => mb_strimwidth($row['body'], 0, 100, '...'),  // Truncate long messages
            'date' => $row['message_date'],
            'status' => $row['message_status'],
            'sender' => [
                'id' => $row['sender_id'],
                'name' => $row['sender_name'] ?: ($row['sender_fname'] . ' ' . $row['sender_lname'])
            ],
            'recipient' => [
                'id' => $row['recipient_id'],
                'name' => $row['recipient_name'] ?: ($row['recipient_fname'] . ' ' . $row['recipient_lname'])
            ]
        ];
    }
    return $messages;
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

    $messages = getMessages($provider_id);
    $statistics = getStatistics($provider_id);
    $patientStats = getPatientStats($provider_id);

    $data = [
        'patient_trackers' => $patientTrackers,
        'procedure_orders' => $procedureOrders,
        'calendar_events' => $calendarEvents,
        'statistics' => $statistics,
        'messages' => $messages,
        'patient_stats' => $patientStats['data'],
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