<?php
header('Content-Type: application/json');

require_once(__DIR__ . "/../../verify_session.php");
require_once __DIR__ . '/../../../src/Common/Auth/JWT.php';

try {
    $owner = $_SESSION['portal_login_username'] ?? $_SESSION['authUser'] ?? null;
    $pid = $_GET['pid'] ?? ($_SESSION['pid'] ?? null);
    $deviceId = $_GET['device_id'] ?? null;

    // ✅ Enforce device_id is required
    if (!$deviceId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'device_id is required'
        ]);
        exit;
    }

    // ✅ Optionally enforce other required fields
    if (!$owner || !$pid) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required user/session information'
        ]);
        exit;
    }

    // Prepare JWT payload
    $payload = [
        'patient_id' => $pid,
        'username' => $owner,
        'device_id' => $deviceId
    ];

    // Encode JWT
    $token = JWT::encode($payload);

    // ✅ Success response
    echo json_encode([
        'success' => true,
        'access_token' => $token,
        'device_id' => $deviceId,
        'username' => $owner
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    // ✅ Catch unexpected errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'details' => $e->getMessage() // You can remove 'details' in production
    ]);
}
