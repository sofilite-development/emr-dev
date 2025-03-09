<?php

use function Clue\StreamFilter\fun;

header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');
require_once(__DIR__ . '/helper.php');


$pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$option = $_GET["option"];
$is_multiple = $_GET["is_multiple"];

$data = $is_multiple == "true" ? generate_multiple_list_json($option) : generate_single_list_json($option);

if (!$data) {
    echo json_encode(['error' => 'No data found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Return the data as JSON
$response =  $data;

$jsonOutput = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
if ($jsonOutput === false) {
    echo json_encode(['error' => 'JSON encoding error', 'details' => json_last_error_msg()]);
} else {
    echo $jsonOutput;
}
exit();

function generate_single_list_json(
    $option_title,
) {
    $_options = [];
    $lres = sqlStatement("SELECT * FROM list_options WHERE list_id = ? AND activity = 1", [$option_title]);

    while ($lrow = sqlFetchArray($lres)) {
        $_options[] = [
            'label' => text($lrow['title']),
            'value' => attr($lrow['option_id']),
        ];
    }
    return $_options;
}


function generate_multiple_list_json($option_titles)
{
    $options = [];
    $titles = explode("|", $option_titles);
    foreach ($titles as $title) {
        $option = generate_single_list_json($title);
        $options[$title] = $option;
    }

    return ($options);
}
