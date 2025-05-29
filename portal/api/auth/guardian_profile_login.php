<?php

header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
session_regenerate_id(true);
$currentPatientId = $_SESSION["pid"];
$guardian_username = $_SESSION['guardian_username'];
$guardian_login_username = $_SESSION['guardian_login_username'];

$ignoreAuth_onsite_portal = true;

require_once(__DIR__ . "/../../lib/appsql.class.php");
require_once("$srcdir/user.inc.php");
require_once __DIR__ . '/../../../src/Common/Auth/JWT.php';


use OpenEMR\Common\Auth\AuthHash;
use OpenEMR\Common\Csrf\CsrfUtils;

use function PHPSTORM_META\type;

$logit = new ApplicationTable();

$response = [
    'status'       => 401,
    'message'      => 'Invalid credentials',
];

$authorizedPortal = false; // flag
DEFINE("TBL_PAT_ACC_ON", "patient_access_onsite");
DEFINE("COL_ID", "id");
DEFINE("COL_PID", "pid");
DEFINE("COL_POR_PWD", "portal_pwd");
DEFINE("COL_POR_USER", "portal_username");
DEFINE("COL_POR_LOGINUSER", "portal_login_username");
DEFINE("COL_POR_PWD_STAT", "portal_pwd_status");
DEFINE("COL_POR_ONETIME", "portal_onetime");



$sql = "SELECT " . implode(",", array(
    COL_ID,
    COL_PID,
    COL_POR_PWD,
    COL_POR_USER,
    COL_POR_LOGINUSER,
    COL_POR_PWD_STAT
)) . " FROM " . TBL_PAT_ACC_ON .
    " WHERE " . COL_POR_LOGINUSER . "= ?";
$auth = privQuery($sql, array($guardian_login_username));

if (!$auth) {
    $logit->portalLog('login attempt guardian profile ', '', ($guardian_username . ':invalid credentials'), '', '0');
    echo json_encode($response);
    exit();
}

if ($_SESSION["guardianId"] != $auth['pid']) {
    echo json_encode($response);
    exit();
}


$sql = "SELECT * FROM `patient_data` WHERE `pid` = ?";
$userData = sqlQuery($sql, array($auth['pid']));

if (empty($userData)) {
    $logit->portalLog('login attempt guardian profile ', '', ($auth[COL_POR_USER] . ':invalid credentials'), '', '0');
    echo json_encode($response);
    exit();
}


if ($userData['allow_patient_portal'] != "YES") {
    // Patient has not authorized portal, so escape
    $logit->portalLog('login attempt', '', ($auth[COL_POR_USER] . ':allow portal turned off'), '', '0');
    $response["status"] = 404;
    $response["message"] = "Patient has not access on portal";
    echo json_encode($response);
    exit();
}

if ($auth['pid'] != $userData['pid']) {
    echo json_encode($response);
    exit();
}

unset($_SESSION['password_update']);
unset($_SESSION['itsme']);
unset($_SESSION['guardian_login_username']);
unset($_SESSION['guardian_username']);



$_SESSION['portal_username'] = $auth[COL_POR_USER];
$_SESSION['portal_login_username'] = $auth[COL_POR_LOGINUSER];

$_SESSION['pid'] = $auth['pid'];
$_SESSION['patient_portal_onsite_two'] = 1;

$tmp = getUserIDInfo($userData['providerID']);
$_SESSION['providerName'] = ($tmp['fname'] ?? '') . ' ' . ($tmp['lname'] ?? '');
$_SESSION['providerUName'] = $tmp['username'] ?? null;
$_SESSION['sessionUser'] = '-patient-'; // $_POST['uname'];
$_SESSION['providerId'] = $userData['providerID'] ? $userData['providerID'] : 'undefined';
$_SESSION['ptName'] = $userData['fname'] . ' ' . $userData['lname'];
// never set authUserID though authUser is used for ACL!
$_SESSION['authUser'] = 'portal-user';
// Set up the csrf private_key (for the paient portal)
//  Note this key always remains private and never leaves server session. It is used to create
//  the csrf tokens.
CsrfUtils::setupCsrfKey();

$logit->portalLog('login', $_SESSION['pid'], ($_SESSION['portal_username'] . ': ' . $_SESSION['ptName'] . ':success'));



function sanitizeData($data)
{
    if (is_array($data)) {
        return array_map('sanitizeData', $data);
    } elseif (is_string($data)) {
        return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
    } else {
        return $data;
    }
}

$response["message"] = "Login successful";
$response["status"] = 200;
$response["patient_data"] = sanitizeData($userData);

$responseData = json_encode($response);
if ($responseData === false) {
    die("JSON encoding error: " . json_last_error_msg());
}
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
echo $responseData;
exit();
