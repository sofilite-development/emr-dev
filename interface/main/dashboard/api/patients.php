<?php

/**
 * Patients API Endpoint
 * Returns a paginated list of patients with optional search filters
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
    $providerId = isset($_GET['providerId']) ? (int) $_GET['providerId'] : null;

    // Validate pagination parameters
    $page = max(1, $page);
    $limit = max(1, min(100, $limit));  // Limit max 100 records per page
    $offset = ($page - 1) * $limit;

    // Build the base query
    $query = "SELECT p.*, 
                CONCAT(p.fname, ' ', p.lname) as full_name,
                u.fname as provider_fname, 
                u.lname as provider_lname
              FROM patient_data p
              LEFT JOIN users u ON p.providerID = u.id
              WHERE p.pid > 0";

    $params = array();

    // Add search conditions if provided
    if (!empty($search)) {
        $query .= ' AND (p.fname LIKE ? OR p.lname LIKE ? OR p.pid = ? OR p.pubpid = ? OR p.phone_cell LIKE ? OR p.email LIKE ?)';
        $searchTerm = "%$search%";
        $params = array_merge($params, array_fill(0, 5, $searchTerm));
        $params[] = $search;  // For exact PID match
    }

    // Add provider filter if provided
    if (!empty($providerId)) {
        $query .= ' AND p.providerID = ?';
        $params[] = $providerId;
    }

    // Add site filter
    $query .= ' AND p.pid IN (SELECT pid FROM patient_data WHERE pid = p.pid)';

    // Count total records for pagination
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as total_patients";
    $countResult = sqlQueryNoLog($countQuery, $params);
    $totalPatients = $countResult['total'];
    $totalPages = ceil($totalPatients / $limit);

    // Add sorting and pagination
    $query .= ' ORDER BY p.lname, p.fname LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    // Execute the query
    $result = sqlStatementNoLog($query, $params);
    $patients = array();

    while ($row = sqlFetchArray($result)) {
        $patients[] = array(
            'id' => $row['pid'],
            'pid' => $row['pid'],
            'pubpid' => $row['pubpid'],
            'firstName' => $row['fname'],
            'lastName' => $row['lname'],
            'fullName' =>$row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname'],
            'dob' => $row['DOB'],
            'gender' => $row['sex'],
            'phone' => $row['phone_cell'] ?: $row['phone_home'],
            'email' => $row['email'],
            'provider' => $row['provider_fname'] || $row['provider_lname']
                ? $row['provider_fname'] . ' ' . $row['provider_lname']
                : null
        );
    }

    // Return the paginated response
    echo json_encode([
        'success' => true,
        'data' => [
            'patients' => $patients,
        ],
        'metadata' => [
            'total' => (int) $totalPatients,
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

/**
 * Validates patient data
 * @param array $data Patient data to validate
 * @return array Array of validation errors, empty if valid
 */
function validatePatientData($data)
{
    $errors = [];

    // Required fields
    $required = [
        'fname' => 'First Name',
        'lname' => 'Last Name',
        'DOB' => 'Date of Birth',
        'sex' => 'Gender',
    ];

    foreach ($required as $field => $label) {
        if (empty($data[$field])) {
            $errors[] = "$label is required";
        }
    }

    // Validate email if provided
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Validate date of birth
    if (!empty($data['DOB'])) {
        $dob = date_parse($data['DOB']);
        if (!checkdate($dob['month'], $dob['day'], $dob['year'])) {
            $errors[] = 'Invalid date of birth';
        }
    }

    // Validate gender
    $validGenders = ['Male', 'Female', 'Other', 'Unknown'];
    if (!empty($data['sex']) && !in_array(ucfirst(strtolower($data['sex'])), $validGenders)) {
        $errors[] = 'Invalid gender';
    }

    return $errors;
}

// Handle POST request to create a new patient
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }

        // Sanitize input data
        $patientData = [
            'fname' => sanitizeInput($input['firstName'] ?? '', 'string'),
            'mname' => sanitizeInput($input['middleName'] ?? '', 'string'),
            'lname' => sanitizeInput($input['lastName'] ?? '', 'string'),
            'DOB' => sanitizeInput($input['dateOfBirth'] ?? '', 'date'),
            'sex' => sanitizeInput($input['gender'] ?? '', 'string'),
            'ss' => sanitizeInput($input['ssn'] ?? '', 'string'),
            'providerID' => sanitizeInput($input['providerId'] ?? null, 'int'),
            'status' => 'active',
            'pubpid' => '',  // Will be generated
            'pid' => 0,  // Will be auto-incremented
            'regdate' => date('Y-m-d H:i:s'),
            'ref_providerID' => '',
            'email' => sanitizeInput($input['email'] ?? '', 'email'),
            'phone_cell' => sanitizeInput($input['phone'] ?? '', 'phone'),
            'phone_home' => sanitizeInput($input['phoneHome'] ?? '', 'phone'),
            'street' => sanitizeInput($input['address'] ?? '', 'string'),
            'postal_code' => sanitizeInput($input['postalCode'] ?? '', 'string'),
            'city' => sanitizeInput($input['city'] ?? '', 'string'),
            'state' => sanitizeInput($input['state'] ?? '', 'string'),
            'country_code' => sanitizeInput($input['country'] ?? 'US', 'string'),
        ];

        // Validate required fields
        $validationErrors = validatePatientData($patientData);
        if (!empty($validationErrors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'errors' => $validationErrors
            ]);
            exit;
        }

        // Generate pubpid if not provided
        if (empty($patientData['pubpid'])) {
            $patientData['pubpid'] = strtoupper(substr($patientData['lname'], 0, 3)
                . substr($patientData['fname'], 0, 1)
                . date('Ymd'));
        }

        // Start transaction
        sqlBeginTrans();

        // Insert patient data
        $pid = sqlInsert('INSERT INTO patient_data SET '
                . 'pid = ?,
            pubpid = ?,
            title = ?,
            fname = ?,
            lname = ?,
            mname = ?,
            DOB = ?,
            sex = ?,
            ss = ?,
            email = ?,
            phone_cell = ?,
            phone_home = ?,
            street = ?,
            postal_code = ?,
            city = ?,
            state = ?,
            country_code = ?,
            providerID = ?,
            status = ?,
            regdate = ?',
            [
                $patientData['pid'],
                $patientData['pubpid'],
                $patientData['title'] ?? '',
                $patientData['fname'],
                $patientData['lname'],
                $patientData['mname'],
                $patientData['DOB'],
                $patientData['sex'],
                $patientData['ss'] ?? '',
                $patientData['email'],
                $patientData['phone_cell'],
                $patientData['phone_home'] ?? '',
                $patientData['street'] ?? '',
                $patientData['postal_code'] ?? '',
                $patientData['city'] ?? '',
                $patientData['state'] ?? '',
                $patientData['country_code'],
                $patientData['providerID'] ?? 0,
                $patientData['status'],
                $patientData['regdate']
            ]);

        if (!$pid) {
            throw new Exception('Failed to create patient record');
        }

        // Commit transaction
        sqlCommitTrans();

        // Return success response
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $pid,
                'pid' => $pid,
                'pubpid' => $patientData['pubpid'],
                'message' => 'Patient created successfully'
            ]
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        if (sqlGetTransaction()) {
            sqlRollbackTrans();
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    exit;
}
