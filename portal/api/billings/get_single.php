<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once(__DIR__ . '/helper.php');


$pid = $_SESSION['pid'];

$encounterId = $_GET['encounter'] ?? "";
try {
    $bill = getEncounterBilling($pid, $encounterId);
    echo json_encode(["success" => true, "bill" => $bill]);
} catch (Exception $e) {
    echo json_encode(["error" => "Internal Server Error", "message" => $e->getMessage()]);
}
