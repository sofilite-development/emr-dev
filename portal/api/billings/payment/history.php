<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../../verify_session.php");
require_once(__DIR__ . '/helper.php');


$pid = $_SESSION['pid'];

try {
    $paymentHistory = getPaymentHistory($pid);
    echo json_encode(["success" => true, "history" => $paymentHistory["payments"], "total_paid" => $paymentHistory["total_paid"]]);
} catch (Exception $e) {
    echo json_encode(["error" => "Internal Server Error", "message" => $e->getMessage()]);
}
