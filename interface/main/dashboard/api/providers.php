<?php

/**
 * Providers API Endpoint
 * Returns a paginated list of providers with optional search filters
 */

// Set content type to JSON
header('Content-Type: application/json');

// Require necessary files
require_once (__DIR__ . '/../../../globals.php');
require_once 'helpers/cors.php';
require_once 'helpers/sanitizeInput.php';

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
    // Get query parameters with defaults
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Validate pagination parameters
    $page = max(1, $page);
    $limit = max(1, min(100, $limit));  // Limit max 100 records per page
    $offset = ($page - 1) * $limit;

    // Build the base query
    $query = "SELECT id, fname, lname, username, active, npi, specialty, 
                CONCAT(fname, ' ', lname) as full_name
              FROM users 
              WHERE authorized = 1 AND active = 1";

    $params = array();

    // Add search conditions if provided
    if (!empty($search)) {
        $query .= ' AND (fname LIKE ? OR lname LIKE ? OR username LIKE ? OR npi = ?)';
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $search]);
    }

    // Count total records for pagination
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as total_providers";
    $countResult = sqlQueryNoLog($countQuery, $params);
    $totalProviders = $countResult['total'];
    $totalPages = ceil($totalProviders / $limit);

    // Add sorting and pagination
    $query .= ' ORDER BY lname, fname LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    // Execute the query
    $result = sqlStatementNoLog($query, $params);
    $providers = array();

    while ($row = sqlFetchArray($result)) {
        $providers[] = array(
            'id' => $row['id'],
            'username' => $row['username'],
            'firstName' => $row['fname'],
            'lastName' => $row['lname'],
            'fullName' => $row['full_name'],
            'npi' => $row['npi'],
            'specialty' => $row['specialty'],
            'status' => $row['active'] ? 'Active' : 'Inactive'
        );
    }

    // Return the paginated response
    echo json_encode([
        'success' => true,
        'data' => [
            'providers' => $providers,
        ],
        'metadata' => [
            'total' => (int) $totalProviders,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPreviousPage' => $page > 1
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}