<?php

/**
 * API Endpoint to Fetch Available Appointment Slots in JSON Format
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 */

// Set JSON response header
header('Content-Type: application/json');

// Include required OpenEMR dependencies
require_once(__DIR__ . "/../../verify_session.php");
require_once(__DIR__ . "/../../../interface/globals.php");
require_once("$srcdir/patient.inc.php");
require_once("$srcdir/appointments.inc.php");

use OpenEMR\Services\Utils\DateFormatterUtils;

// Check if the patient is authenticated
if (!isset($_SESSION['pid']) || !isset($_SESSION['patient_portal_onsite_two'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

// Fetch request parameters
$provider_id = $_GET['provider_id'] ?? null;
$category_id = $_GET['category_id'] ?? null;
if (!empty($_GET['start_date'])) {
    $start_date = DateFormatterUtils::DateToYYYYMMDD($_GET['start_date']);
} else {
    $start_date = date("Y-m-d");
}

$search_days = $_GET['search_days'] ?? 7;

preg_match("/(\d\d\d\d)\D*(\d\d)\D*(\d\d)/", $start_date, $matches);
$end_date = date(
    "Y-m-d",
    mktime(0, 0, 0, $matches[2], $matches[3] + $search_days, $matches[1])
);


// Validate required inputs
if (!$provider_id) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
}

// Convert date format

// Compute time slots
$slot_secs = $GLOBALS['calendar_interval'] * 60;
$slot_start_time = strtotime("$start_date 00:00:00");
$slot_end_time = strtotime("$end_date 00:00:00");
$slot_base = (int)($slot_start_time / $slot_secs);
$slot_count = (int)($slot_end_time / $slot_secs) - $slot_base;

if ($slot_count <= 0 || $slot_count > 100000) {
    die("Invalid date range.");
}

$slots_per_day = (int)(60 * 60 * 24 / $slot_secs);
$slots = array_fill(0, $slot_count, 0);

$query = "SELECT pc_eventDate, pc_endDate, pc_startTime, pc_duration, " .
    "pc_recurrtype, pc_recurrspec, pc_alldayevent, pc_catid, pc_prefcatid, pc_title " .
    "FROM openemr_postcalendar_events " .
    "WHERE pc_aid = ? AND " .
    "((pc_endDate >= ? AND pc_eventDate < ?) OR " .
    "(pc_endDate = '0000-00-00' AND pc_eventDate >= ? AND pc_eventDate < ?))";

$sqlBindArray = array();
array_push($sqlBindArray, $provider_id, $start_date, $end_date, $start_date, $end_date);
//////
$events = fetchEvents($start_date, $end_date, null, null, false, 0, $sqlBindArray, $query);

// Process availability
foreach ($events as $event) {
    $event_date = strtotime($event['pc_eventDate'] . " 00:00:00");
    $start_time = strtotime($event['pc_startTime'], $event_date);
    $duration = $event['pc_duration'];
    $category = $event['pc_catid'];
    $preferred_category = $event['pc_prefcatid'];
    $recurring = $event['pc_recurrtype'] !== '0';

    $slot_index = (int)($start_time / $slot_secs) - $slot_base;
    $slot_end_index = $slot_index + ceil($duration / $slot_secs);

    if ($slot_end_index > $slot_count) {
        $slot_end_index = $slot_count;
    }

    if ($category == 2) { // In office
        for ($i = $slot_index; $i < $slot_end_index; ++$i) {
            $slots[$i] |= 1;
        }
    } elseif ($category == 3) { // Out of office
        for ($i = $slot_index; $i < $slot_end_index; ++$i) {
            $slots[$i] |= 2;
        }
    } else { // Reserved
        for ($i = $slot_index; $i < $slot_end_index; ++$i) {
            $slots[$i] |= 4;
        }
    }
}

// Determine available slots
// $available_slots = [];
// $in_office = false;

// for ($i = 0; $i < $slot_count; ++$i) {
//     if (($i % $slots_per_day) == 0) {
//         $in_office = false;
//     }

//     if ($slots[$i] & 1) {
//         $in_office = true;
//     }

//     if ($slots[$i] & 2) {
//         $in_office = false;
//     }

//     if ($in_office && !($slots[$i] & 4)) {
//         $slot_time = date("g:i A", $slot_start_time + ($i * $slot_secs));
//         $available_slots[] = [
//             "date" => date("Y-m-d", $slot_start_time + ($i * $slot_secs)),
//             "time" => $slot_time,
//         ];
//     }
// }

$available_slots = [];
$in_office = false;
$formatted_slots = [];

for ($i = 0; $i < $slot_count; ++$i) {
    if (($i % $slots_per_day) == 0) {
        $in_office = false;
    }

    if ($slots[$i] & 1) {
        $in_office = true;
    }

    if ($slots[$i] & 2) {
        $in_office = false;
    }

    if ($in_office && !($slots[$i] & 4)) {
        $date = date("Y-m-d", $slot_start_time + ($i * $slot_secs));
        $time = date("H.i", $slot_start_time + ($i * $slot_secs));
        $available_slots[$date][] = ["time" => $time];
    }
}

// Convert to required JSON format
foreach ($available_slots as $date => $slots) {
    $formatted_slots[] = [
        "date" => $date,
        "slots" => $slots
    ];
}

// Return JSON response
echo json_encode(["available_slots" => $formatted_slots]);
exit();
