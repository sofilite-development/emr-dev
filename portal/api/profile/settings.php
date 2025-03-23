<?php

header("Content-Type: application/json");

require_once(__DIR__ . "/../../verify_session.php");
require_once(dirname(__FILE__) . "/../../../src/Common/Session/SessionUtil.php");
require_once(dirname(__FILE__) . "/../../../interface/globals.php");
require_once(dirname(__FILE__) . "/../../lib/appsql.class.php");

use OpenEMR\Common\Auth\AuthHash;

$response = ["success" => false, "message" => "Invalid request."];

// Ensure the user is authenticated
if (!isset($_SESSION['pid']) || !isset($_SESSION['patient_portal_onsite_two'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$pid = $_SESSION['pid'];

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    DEFINE("TBL_PAT_ACC_ON", "patient_access_onsite");
    DEFINE("COL_ID", "id");
    DEFINE("COL_PID", "pid");
    DEFINE("COL_POR_PWD", "portal_pwd");
    DEFINE("COL_POR_USER", "portal_username");
    DEFINE("COL_POR_LOGINUSER", "portal_login_username");

    // Fetch user credentials from DB
    $sql = "SELECT " . implode(",", [COL_ID, COL_PID, COL_POR_PWD, COL_POR_USER, COL_POR_LOGINUSER]) .
        " FROM " . TBL_PAT_ACC_ON . " WHERE pid = ?";
    $auth = privQuery($sql, [$pid]);

    // Validate current password
    $current_password = trim($input['current_password'] ?? '');
    if (!$auth || !AuthHash::passwordVerify($current_password, $auth[COL_POR_PWD])) {
        echo json_encode(["success" => false, "message" => "Invalid current password."]);
        exit;
    }

    $updates = [];
    $bind = [];

    // Update password if provided
    if (!empty($input['new_password'])) {
        $new_hash = (new AuthHash('auth'))->passwordHash($input['new_password']);
        if (!$new_hash) {
            echo json_encode(["success" => false, "message" => "Failed to hash password."]);
            exit;
        }
        $updates[] = COL_POR_PWD . "=?";
        $bind[] = $new_hash;
    }

    // Update username if provided
    if (!empty($input['new_username'])) {
        $updates[] = COL_POR_LOGINUSER . "=?";
        $bind[] = $input['new_username'];
    }

    // Perform update
    if (!empty($updates)) {
        $sqlUpdate = "UPDATE " . TBL_PAT_ACC_ON . " SET " . implode(", ", $updates) . " WHERE " . COL_ID . "=?";
        $bind[] = $auth[COL_ID];
        privStatement($sqlUpdate, $bind);

        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Username and Password updated successfully."]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "No changes made."]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
