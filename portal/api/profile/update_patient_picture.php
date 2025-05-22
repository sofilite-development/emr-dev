<?php

use Symfony\Component\VarDumper\VarDumper;

header('Content-Type: application/json');

require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once(__DIR__ . '/helper.php');

$appUrl = $_ENV['APP_URL'] ?? 'http://localhost';

$pid = $_POST['pid'] ?? ($_SESSION['pid'] ?? null);

if (!$pid) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit();
}

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit();
}

// Allowed extensions
$allowedExtensions = ['webp', 'png', 'jpeg', 'jpg', 'svg'];
$file = $_FILES['profile_picture'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedExtensions)) {
    echo json_encode(['error' => 'Unsupported file type. Only webp, png, jpeg, jpg, and svg are allowed.']);
    exit();
}

// Image validation (except svg)
if ($fileExt !== 'svg') {
    $imageCheck = getimagesize($file['tmp_name']);
    if ($imageCheck === false) {
        echo json_encode(['error' => 'Uploaded file is not a valid image']);
        exit();
    }
}

$uploadDir = __DIR__ . "/../../../sites/default/profile_pictures/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$sanitizedFileName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($file['name']));
$newFileName = "patient_" . intval($pid) . "_" . time() . "_" . $sanitizedFileName;
$targetPath = $uploadDir . $newFileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativePath = "sites/default/profile_pictures/" . $newFileName;
    $publicUrl = rtrim($appUrl, '/') . '/' . $relativePath;

    // Save relative path to DB
    $updateSql = "UPDATE patient_data SET profile_picture = ? WHERE pid = ?";
    sqlStatement($updateSql, [$relativePath, $pid]);

    echo json_encode([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'url' => $publicUrl
    ]);
} else {
    echo json_encode(['error' => 'Failed to upload file']);
}

exit();
