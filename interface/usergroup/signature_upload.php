<?php

/**
 * Edit Signature.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Rod Roark <rod@sunsetsystems.com>
 * @author    Daniel Pflieger <daniel@mi-squared.com> <daniel@growlingflea.com>
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2018-2019 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2021 Daniel Pflieger <daniel@mi-squared.com> <daniel@growlingflea.com>
 * @copyright Copyright (c) 2021 Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2021 Rod Roark <rod@sunsetsystems.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../globals.php");
require_once("$srcdir/calendar.inc.php");
require_once("$srcdir/options.inc.php");

use OpenEMR\Common\Acl\AclExtended;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Header;
use OpenEMR\Menu\MainMenuRole;
use OpenEMR\Menu\PatientMenuRole;
use OpenEMR\Services\FacilityService;
use OpenEMR\Services\UserService;
use OpenEMR\Events\User\UserEditRenderEvent;

$facilityService = new FacilityService();

if (!AclMain::aclCheckCore('admin', 'users')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Edit User")]);
    exit;
}

if (!$_GET["id"]) {
    exit();
}

// Handle Signature Upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['signature-user']) && $_FILES['signature-user']['error'] == 0) {
    $signaturePath = "NULL"; // Default value

    // Handle signature upload
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    $fileType = mime_content_type($_FILES['signature-user']['tmp_name']);
    $fileExtension = strtolower(pathinfo($_FILES['signature-user']['name'], PATHINFO_EXTENSION));

    // Validate file type and size
    if (!in_array($fileType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Error: Invalid file type!']);
        exit;
    }

    if ($_FILES['signature-user']['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => 'Error: File is too large!']);
        exit;
    }

    // Create upload directory
    $uploadDir = $OE_SITES_BASE . "/uploads/signatures/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $appUrl = $_ENV["APP_URL"] ?? "";

    // Generate unique filename
    $fileName = uniqid() . "_" . basename($_FILES["signature-user"]["name"]);
    $targetFile = $uploadDir . $fileName;

    // Move File & Update Database
    if (move_uploaded_file($_FILES["signature-user"]["tmp_name"], $targetFile)) {
        $signaturePath = add_escape_custom($appUrl . "/sites/uploads/signatures/" . $fileName);
        sqlStatement("UPDATE users SET url=? WHERE id=?", [$signaturePath, $_GET["id"]]);

        echo json_encode(['success' => true, 'id' => $_GET["id"]]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file!']);
        exit;
    }
}
