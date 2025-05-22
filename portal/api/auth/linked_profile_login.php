<?php

header('Content-Type: application/json');
require_once(__DIR__ . "/../../verify_session.php");
session_regenerate_id(true);

$currentPatientId = $_SESSION["pid"];

$ignoreAuth_onsite_portal = true;

require_once(__DIR__ . "/../../lib/appsql.class.php");
require_once("$srcdir/user.inc.php");
require_once __DIR__ . '/../../../src/Common/Auth/JWT.php';

$payload = json_decode(file_get_contents("php://input"), true);


use OpenEMR\Common\Auth\AuthHash;
use OpenEMR\Common\Csrf\CsrfUtils;

use function PHPSTORM_META\type;

$logit = new ApplicationTable();

$authorizedPortal = false; // flag

DEFINE("TBL_PAT_ACC_ON", "patient_data");
DEFINE("COL_ID", "id");
DEFINE("COL_PID", "pid");
DEFINE("COL_POR_PWD", "portal_pwd");
DEFINE("COL_POR_USER", "portal_username");
DEFINE("COL_POR_LOGINUSER", "portal_login_username");
DEFINE("COL_POR_PWD_STAT", "portal_pwd_status");
DEFINE("COL_POR_ONETIME", "portal_onetime");

$response = [
    'status'       => 401,
    'message'      => 'Invalid credentials',
];

$sql = "SELECT * FROM patient_data
WHERE allow_patient_portal = 'YES' 
AND pid = ?
AND guardianid = ?";
$auth = privQuery($sql, array($payload['patientId'], $currentPatientId));

if (!$auth) {
    $logit->portalLog('login attempt sub profile', '', ($_SESSION["portal_username"] . ':invalid credentials'), '', '0');
    echo json_encode($response);
    exit();
}
//TODO poral_username,portal_login_username and mesage

$_SESSION['guardian_username'] = $_SESSION['portal_username'];
$_SESSION['guardian_login_username'] = $_SESSION["portal_login_username"];
$_SESSION['is_sub_profile'] = true;
$_SESSION['portal_username'] = null;
$_SESSION["portal_login_username"] = null;

$_SESSION['pid'] = $auth['pid'];
$_SESSION['patient_portal_onsite_two'] = 1;

$tmp = getUserIDInfo($auth['providerID']);
$_SESSION['providerName'] = ($tmp['fname'] ?? '') . ' ' . ($tmp['lname'] ?? '');
$_SESSION['providerUName'] = $tmp['username'] ?? null;
$_SESSION['sessionUser'] = '-patient-'; // $_POST['uname'];
$_SESSION['providerId'] = $auth['providerID'] ? $auth['providerID'] : 'undefined';
$_SESSION['ptName'] = $auth['fname'] . ' ' . $auth['lname'];
$_SESSION['guardianId'] = $auth['guardianid'];
// never set authUserID though authUser is used for ACL!
$_SESSION['authUser'] = 'portal-user';
// Set up the csrf private_key (for the paient portal)
//  Note this key always remains private and never leaves server session. It is used to create
//  the csrf tokens.
CsrfUtils::setupCsrfKey();
$logit->portalLog('login', $_SESSION['pid'], ("Relatives of " . $_SESSION['guardian_username'] . ': ' . $_SESSION['ptName'] . ':success'));


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
$response["patient_data"] = sanitizeData($auth);

$responseData = json_encode($response);
if ($responseData === false) {
    die("JSON encoding error: " . json_last_error_msg());
}
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
echo $responseData;
exit();
