<?php
function getVisitCategories()
{
    $categories = [];
    $query = "SELECT pc_catid, pc_catname, pc_duration, pc_end_all_day
              FROM openemr_postcalendar_categories 
              WHERE pc_active = 1 ORDER BY pc_seq";

    $result = sqlStatement($query);

    while ($row = sqlFetchArray($result)) {
        $categories[] = [
            'id' => $row['pc_catid'],
            'name' => xl_appt_category($row['pc_catname']),
            'duration' => $row['pc_end_all_day'] ? 1440 : round($row['pc_duration'] / 60) // Convert duration to minutes
        ];
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

function updateAppointment($appointmentId, $patientId, $providerId, $categoryId, $date, $startTime, $duration, $comments)
{
    global $pid;

    if (!$appointmentId || !$patientId || !$providerId || !$categoryId || !$date || !$startTime || !$duration) {
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
