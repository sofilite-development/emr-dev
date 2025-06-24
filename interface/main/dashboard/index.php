<?php

/**
 * main.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Kevin Yeh <kevin.y@integralemr.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Ranganath Pathak <pathak@scrs1.org>
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2016 Kevin Yeh <kevin.y@integralemr.com>
 * @copyright Copyright (c) 2016-2019 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2019 Ranganath Pathak <pathak@scrs1.org>
 * @copyright Copyright (c) 2024 Care Management Solutions, Inc. <stephen.waite@cmsvt.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
$sessionAllowWrite = true;
require_once (__DIR__ . '/../../globals.php');
require_once $GLOBALS['srcdir'] . '/ESign/Api.php';

use Esign\Api;
use OpenEMR\Common\Acl\AclMain;
// use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;
use OpenEMR\Services\LogoService;

$logoService = new LogoService();
$menuLogo = $logoService->getLogo('core/menu/primary/');

// Ensure token_main matches so this script can not be run by itself
//  If do not match, then destroy the session and go back to login screen

if (
    (empty($_SESSION['token_main_php']))
) {
    // Below functions are from auth.inc, which is included in globals.php
    authCloseSession();
    authLoginScreen(false);
    header('Location: ' . $web_root . 'interface/login/login.php');
}
// this will not allow copy/paste of the link to this main.php page or a refresh of this main.php page
//  (default behavior, however, this behavior can be turned off in the prevent_browser_refresh global)

// ^ TO PREVENT REFRESH
// if ($GLOBALS['prevent_browser_refresh'] > 1) {
//     unset($_SESSION['token_main_php']);
// }

$esignApi = new Api();
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo text($openemr_name); ?></title>
    <meta charset="UTF-8">

    <?php Header::setupHeader(['knockout', 'tabs-theme', 'i18next', 'hotkeys']); ?>
    
    <?php
    // Below code block is to prepare certain elements for deciding what links to show on the menu
    //
    // prepare newcrop globals that are used in creating the menu
    if ($GLOBALS['erx_enable']) {
        $newcrop_user_role_sql = sqlQuery('SELECT `newcrop_user_role` FROM `users` WHERE `username` = ?', array($_SESSION['authUser']));
        $GLOBALS['newcrop_user_role'] = $newcrop_user_role_sql['newcrop_user_role'];
        if ($GLOBALS['newcrop_user_role'] === 'erxadmin') {
            $GLOBALS['newcrop_user_role_erxadmin'] = 1;
        }
    }

    // prepare track anything to be used in creating the menu
    $track_anything_sql = sqlQuery("SELECT `state` FROM `registry` WHERE `directory` = 'track_anything'");
    $GLOBALS['track_anything_state'] = ($track_anything_sql['state'] ?? 0);
    // prepare Issues popup link global that is used in creating the menu
    $GLOBALS['allow_issue_menu_link'] = ((AclMain::aclCheckCore('encounters', 'notes', '', 'write') || AclMain::aclCheckCore('encounters', 'notes_a', '', 'write')) &&
        AclMain::aclCheckCore('patients', 'med', '', 'write'));
    ?>

   
    <?php
    $userQuery = sqlQuery('select * from users where username = ?', array($_SESSION['authUser']));

    ?>
   

    <!-- <script defer src="../../../modern-ui/dist/assets/js/script.js"></script>
    <link rel="stylesheet" href="../../../modern-ui/dist/assets/css/styles.css" /> -->

</head>

<body>
    <!-- <div id="root"></div> -->
<iframe src="../../../modern-ui/dist/index.html" style="width:100%; height:100%; border:none;"></iframe>

  
</body>

</html>