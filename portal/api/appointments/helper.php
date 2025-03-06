<?php
require("../../../library/pnotes.inc.php");

use OpenEMR\Services\AppointmentService;

function getVisitCategories()
{
    $categories = [];
    $cattype = 0; // Only include categories of type 0

    $query = "SELECT pc_catid, pc_cattype, pc_constant_id, pc_catname, 
                     pc_duration, pc_end_all_day
              FROM openemr_postcalendar_categories 
              WHERE pc_active = 1 
              ORDER BY pc_seq";

    $result = sqlStatement($query);

    while ($row = sqlFetchArray($result)) {
        // Skip categories that do not match cattype 0 or are marked as "No Show"
        if (
            $row['pc_cattype'] != $cattype ||
            $row['pc_constant_id'] === AppointmentService::CATEGORY_CONSTANT_NO_SHOW
        ) {
            continue;
        }

        // Calculate duration (all-day events get 1440 minutes)
        $duration = $row['pc_end_all_day'] ? 1440 : round($row['pc_duration'] / 60);

        // Only add categories with a valid duration
        if ($duration > 0) {
            $categories[] = [
                'id' => $row['pc_catid'],
                'name' => xl_appt_category($row['pc_catname']),
                'duration' => $duration
            ];
        }
    }

    return $categories;
}


function getProviderList()
{
    $providers = [];
    $query = "SELECT id, username, fname, lname FROM users 
              WHERE authorized != 0 AND active = 1 AND username > '' 
              ORDER BY lname, fname";

    $result = sqlStatement($query);

    while ($row = sqlFetchArray($result)) {
        $providers[] = [
            'id' => $row['id'],
            'name' => $row['lname'] . ', ' . $row['fname'],
            'username' => $row['username']
        ];
    }

    return $providers;
}

function getAvailableAppointments($providerId, $startDate, $categoryId)
{
    // Ensure parameters are set
    if (!$providerId || !$startDate || !$categoryId) {
        return ['error' => 'Missing required parameters'];
    }

    // Fetch available slots using OpenEMR's built-in function
    $availableSlots = getAvailableSlots($startDate, date('Y-m-d', strtotime("+1 year", strtotime($startDate))), $providerId);

    // Filter by category duration
    $categories = getVisitCategories();
    $selectedCategory = array_filter($categories, function ($category) use ($categoryId) {
        return $category['id'] == $categoryId;
    });

    $categoryDuration = $selectedCategory ? reset($selectedCategory)['duration'] : 15; // Default to 15 min

    // Format available slots
    $formattedSlots = [];
    foreach ($availableSlots as $slot) {
        $formattedSlots[] = [
            'date' => $slot['pc_eventDate'],
            'startTime' => $slot['pc_startTime'],
            'endTime' => date("H:i:s", strtotime($slot['pc_startTime']) + ($categoryDuration * 60)), // Add category duration
            'providerId' => $providerId
        ];
    }

    return $formattedSlots;
}


function createAppointment($patientId, $providerId, $categoryId, $date, $startTime, $duration, $comments)
{
    global $pid;

    if (!$patientId || !$providerId || !$categoryId || !$date || !$startTime || !$duration) {
        return ['error' => 'Missing required parameters'];
    }

    $eventDate = date('Y-m-d', strtotime($date));
    $startTimeFormatted = date('H:i:s', strtotime($startTime));
    $endTimeFormatted = date('H:i:s', strtotime("+{$duration} minutes", strtotime($startTime)));
    $eventStatus = '^'; // Pending appointment
    $eventTitle = xl('Office Visit');

    $query = "INSERT INTO openemr_postcalendar_events (
                pc_catid, pc_aid, pc_pid, pc_title, pc_time, pc_hometext,
                pc_informant, pc_eventDate, pc_endDate, pc_duration, pc_recurrtype,
                pc_recurrspec, pc_startTime, pc_endTime, pc_alldayevent,
                pc_apptstatus, pc_prefcatid, pc_location, pc_eventstatus, pc_sharing, pc_facility
              ) VALUES (
                ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
              )";

    sqlStatement($query, [
        $categoryId,
        $providerId,
        $patientId,
        $eventTitle,
        $comments,
        $_SESSION['providerId'],
        $eventDate,
        $eventDate,
        $duration * 60, // Convert minutes to seconds
        0, // No recurrence
        '', // No recurrence details
        $startTimeFormatted,
        $endTimeFormatted,
        0, // Not an all-day event
        $eventStatus,
        0, // No preferred category
        '', // No location data
        1, // Active event
        1, // Sharing enabled
        1  // Default facility
    ]);

    // Add a patient note about the appointment request
    $note = xl("A New Appointment request was received from portal patient") . " " . $_SESSION['ptName'];
    $title = xl("Patient Reminders");
    $user = sqlQueryNoLog("SELECT users.username FROM users WHERE id = ?", [$providerId]);

    addPnote($patientId, $note, 1, 1, $title, $user['username'], '', 'New');

    return ['success' => 'Appointment created successfully'];
}

function updateAppointment($appointmentId, $patientId, $providerId, $categoryId, $date = "", $startTime, $duration, $comments)
{
    global $pid;

    if (!$appointmentId || !$patientId || !$providerId || !$categoryId || !$startTime || !$duration) {
        return ['error' => 'Missing required parameters'];
    }

    $eventDate = date('Y-m-d', strtotime($date));
    $startTimeFormatted = date('H:i:s', strtotime($startTime));
    $endTimeFormatted = date('H:i:s', strtotime("+{$duration} minutes", strtotime($startTime)));

    // Ensure appointment belongs to the patient
    $checkAppointment = sqlQuery("SELECT pc_pid FROM openemr_postcalendar_events WHERE pc_eid = ?", [$appointmentId]);

    if ($checkAppointment['pc_pid'] != $patientId) {
        return ['error' => 'Unauthorized appointment update'];
    }

    // Update the appointment details
    $query = "UPDATE openemr_postcalendar_events 
              SET pc_catid = ?, pc_aid = ?, pc_pid = ?, pc_eventDate = ?, 
                  pc_startTime = ?, pc_endTime = ?, pc_duration = ?, 
                  pc_hometext = ?, pc_apptstatus = '^'
              WHERE pc_eid = ?";

    sqlStatement($query, [
        $categoryId,
        $providerId,
        $patientId,
        $eventDate,
        $startTimeFormatted,
        $endTimeFormatted,
        $duration * 60, // Convert minutes to seconds
        $comments,
        $appointmentId
    ]);

    // Add a patient note about the appointment update
    $note = xl("An Updated Appointment request was received from portal patient") . " " . $_SESSION['ptName'];
    $title = xl("Patient Reminders");
    $user = sqlQueryNoLog("SELECT users.username FROM users WHERE id = ?", [$providerId]);

    addPnote($patientId, $note, 1, 1, $title, $user['username'], '', 'New');

    return ['success' => 'Appointment updated successfully'];
}

function cancelAppointment($appointmentId, $reason)
{
    if (!$appointmentId) {
        return ['error' => 'Missing required parameters'];
    }

    // Ensure the appointment exists
    $checkAppointment = sqlQuery("SELECT pc_pid FROM openemr_postcalendar_events WHERE pc_eid = ?", [$appointmentId]);

    if (!$checkAppointment || !$checkAppointment['pc_pid']) {
        return ['error' => 'Unauthorized appointment cancellation'];
    }

    // Update appointment status to indicate cancellation
    $query = "UPDATE openemr_postcalendar_events 
              SET pc_apptstatus = 'x', pc_hometext = ?, pc_endDate = ?
              WHERE pc_eid = ?";

    sqlStatement($query, [$reason, "0000-00-00", $appointmentId]);

    // Add a patient note about the cancellation
    $note = xl("An Appointment cancellation request was received from portal patient") . " " . $_SESSION['ptName'];
    $title = xl("Patient Reminders");
    $user = sqlQueryNoLog("SELECT users.username FROM users WHERE id = (SELECT pc_aid FROM openemr_postcalendar_events WHERE pc_eid = ?)", [$appointmentId]);

    addPnote($checkAppointment['pc_pid'], $note, 1, 1, $title, $user['username'], '', 'New');

    return ['success' => 'Appointment canceled successfully'];
}

function getAllAppoitmentStatus()
{
    $data = [];

    // return $list;
    $apptStatusQuery = sqlStatement("SELECT * FROM list_options WHERE list_id = 'apptstat' AND activity = 1 ORDER BY seq");

    while ($row = sqlFetchArray($apptStatusQuery)) {
        list($hexColor, $percentage) = explode('|', $row['notes']);
        $data[] = [
            'option_id' => $row['option_id'],
            'title' => $row['title'],
            'notes' => hexToRgba($hexColor, $percentage)
        ];
    }
    return $data;
}

function hexToRgba($hex, $opacity)
{
    // Remove the "#" if it's present
    $hex = ltrim($hex, '#');

    // If shorthand notation (e.g., "abc"), expand it
    if (strlen($hex) == 3) {
        $hex = str_repeat($hex[0], 2) . str_repeat($hex[1], 2) . str_repeat($hex[2], 2);
    }

    // Convert the hex to RGB
    list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");

    // Return RGBA value
    return "rgba($r, $g, $b, $opacity)";
}
