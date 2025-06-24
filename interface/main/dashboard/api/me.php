<?php
/**
 * Dashboard API Endpoint
 * Returns user-specific dashboard data
 */

// Set content type to JSON
header('Content-Type: application/json');

// Require necessary files - go up three levels to reach the root directory
require_once(__DIR__ . '/../../../globals.php');
require_once 'cors.php';

// Check if user is logged in and has site ID in session
if (empty($_SESSION['authUser'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated',
        'redirect' => true
    ]);
    exit;
}

// Check if site_id is set in session
if (empty($_SESSION['site_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Session expired or invalid',
        'redirect' => true
    ]);
    exit;
}

try {
    // Get user data
    $username = $_SESSION['authUser'];
    $userQuery = sqlQuery("SELECT * FROM users WHERE username = ?", array($username));
    
    if (!$userQuery) {
        throw new Exception('User not found');
    }

    // Get additional user-specific data as needed
    $data = [
        'user' => [
            'id' => $userQuery['id'],
            'username' => $userQuery['username'],
            'firstName' => $userQuery['fname'],
            'lastName' => $userQuery['lname'],
            'email' => $userQuery['email'] ?? '',
            'role' => $userQuery['authorized'] ?? 0
        ],
    ];

    // Return the data as JSON
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
