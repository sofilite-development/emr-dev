<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
require_once(__DIR__ . '/helper.php');


$pid = $_SESSION['pid'];

$flag = $_GET['flag'] ?? "";
try {
    if ($flag === "all") {
        $billings = getAllPatientBilling($pid);
    } else {
        $billings = getLastMonthBilling($pid);
    }
    echo json_encode(["success" => true, "billings" => $billings]);
} catch (Exception $e) {
    echo json_encode(["error" => "Internal Server Error", "message" => $e->getMessage()]);
}
