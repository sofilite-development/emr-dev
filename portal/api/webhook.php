<?php
session_start();
$_GET['site'] = 'default';
$ignoreAuth = true;
$fake_register_globals = false;
require_once(__DIR__ . '/../../interface/globals.php');
require_once(__DIR__ . '/../../vendor/autoload.php');
header('Content-Type: application/json');

$response = [
    "status" => 404,
    "message" => "API not found!"
];

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code($response["status"]);
    echo json_encode($response);
    exit();
}

$payloadJson = @file_get_contents('php://input');
$payload = json_decode($payloadJson) ?? [];
$data = $payload->data ?? null;
$event = $payload->event ?? null;

if (empty($data) || empty($event)) {
    $response["message"] = "Payload should have data and event!";
    http_response_code($response["status"]);
    echo json_encode($response);
    exit();
}
//Payload
// {
//     "event": {
//         "type": "appointment",
//         "name": "appointment.create",
//         "id": 234,
//         "serial": 1
//     },
//     "data": {
//         "appointment": {},
//         "patient": {}
//     }
// }


$response["status"] = 200;
$response["message"] = "success";
$response["data"] = $data;
echo json_encode($response);
http_response_code(
    $response["status"]
);
