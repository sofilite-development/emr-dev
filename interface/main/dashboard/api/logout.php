<?php
header_remove();
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

/**
 * Logout API Endpoint
 * Handles user logout and session termination
 */

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     http_response_code(405);  // Method Not Allowed
//     echo json_encode([
//         'success' => false,
//         'message' => 'Only POST method is allowed'
//     ]);
//     exit;
// }

// Require necessary files
require_once (__DIR__ . '/../../../globals.php');

// Check if user is logged in
if (empty($_SESSION['authUser'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No active session found'
    ]);
    exit;
}

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // if (!empty($_SESSION['authUser'])) {
    //     $username = $_SESSION['authUser'];
    //     // Clear session data
    //     $_SESSION = [];
    //     // Delete session cookie
    //     if (ini_get('session.use_cookies')) {
    //         $params = session_get_cookie_params();
    //         setcookie(session_name(), '', time() - 42000,
    //             $params['path'], $params['domain'],
    //             $params['secure'], $params['httponly']);
    //     }
    //     // Destroy the session
    //     session_destroy();
    //     error_log('User logged out: ' . $username);
    // }

    authCloseSession();
    authLoginScreen(false);
    header('Location: ' . $web_root . 'interface/login/login.php');
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error during logout',
        'error' => $e->getMessage()
    ]);
}
