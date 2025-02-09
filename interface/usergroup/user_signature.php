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

if (!empty($_GET)) {
    if (!CsrfUtils::verifyCsrfToken($_GET["csrf_token_form"])) {
        CsrfUtils::csrfNotVerified();
    }
}

$facilityService = new FacilityService();

if (!AclMain::aclCheckCore('admin', 'users')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Edit User")]);
    exit;
}

if (!$_GET["id"]) {
    exit();
}

$res = sqlStatement("select * from users where id=?", array($_GET["id"]));
for ($iter = 0; $row = sqlFetchArray($res); $iter++) {
    $result[$iter] = $row;
}

$iter = $result[0];
?>

<html>

<head>

    <?php Header::setupHeader(['common', 'opener', 'erx']); ?>

    <script src="checkpwd_validation.js"></script>

    <?php $use_validate_js = 1; ?>
    <?php require_once($GLOBALS['srcdir'] . "/validation/validation_script.js.php"); ?>
    <?php
    $collectthis = collectValidationPageRules("/interface/usergroup/user_admin.php");
    if (empty($collectthis)) {
        $collectthis = "undefined";
    } else {
        $collectthis = json_sanitize($collectthis["user_form"]["rules"]);
    }
    ?>
    <script>
        function submitform() {
            const button = document.getElementById("save");
            button.innerText = "loading...";
            button.ariaDisabled = true;
            const input = document.getElementById("signature-user");
            if (!input.value) {
                alert("New file missing");
                return;
            }
            if (input.value) {}
            let userId = "<?php echo $_GET['id'] ?? ''; ?>"; // Get ID from PHP

            if (!userId) {
                alert("User ID is missing!");
                return false;
            }

            let post_url = $("#user_form").attr("action") + "?id=" + userId; // Ensure ID is in URL
            let request_method = $("#user_form").attr("method");
            let form_data = new FormData(document.getElementById("user_form"));

            $.ajax({
                url: post_url,
                type: request_method,
                data: form_data,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        var responseData = JSON.parse(response);
                        if (responseData.success) {
                            dlgclose('reload', false);
                        } else {
                            alert("Error: " + responseData.message);
                        }
                    } catch (e) {
                        alert("Unexpected response format");
                    }
                },
                error: function(xhr, status, error) {
                    alert("Upload failed: " + error);
                }
            });

            return false;
        }
    </script>
</head>

<body class="body_top">

    <div class="container">
        <?php
        $is_super_user = AclMain::aclCheckCore('admin', 'super');
        $acl_name = AclExtended::aclGetGroupTitles($iter["username"]);
        $bg_name = '';
        if (is_countable($acl_name)) {
            $bg_count = count($acl_name);
            $selected_user_is_superuser = false;
            for ($i = 0; $i < $bg_count; $i++) {
                if ($acl_name[$i] == "Emergency Login") {
                    $bg_name = $acl_name[$i];
                }
                if (AclExtended::isGroupIncludeSuperuser($acl_name[$i])) {
                    $selected_user_is_superuser = true;
                }
            }
        }
        $disabled_save = !$is_super_user && $selected_user_is_superuser ? 'disabled' : '';
        ?>
        <p class="title">Update Signature</p>&nbsp;
        <br />
        <button class="btn btn-secondary btn-save" name='form_save' href='#' type="button" id="save" onclick="return submitform()" <?php echo $disabled_save; ?>> <span><?php echo xlt('Save'); ?></span> </button>
        <a class="btn btn-link btn-cancel" id='cancel' href='#'><span><?php echo xlt('Cancel'); ?></span></a>

        <br>
        <form name="user_form" id="user_form" method="POST" action="signature_upload.php" enctype="multipart/form-data">
            <!-- signature_URL -->
            <div class="mb-4">
                <div>
                    <label for="signature-user" class="form-label"><?php echo xlt('Update Signature'); ?>:<span class="fa fa-fw fa-xs ml-1 fa-sync" style="cursor: pointer;" data-bind="click: tabRefresh, class: spinner"
                            onclick="return reloadSignature()"></span></label>
                    <input type="file" id="signature-user" name="signature-user" placeholder="Upload signature" accept="image/*" class="form-control">
                </div>
                <div class="mt-2">
                    <img id="signaturePreview" src="<?php echo $iter["url"]; ?>" alt="Signature Preview" style="max-width: 150px; height: auto; border: 1px solid #ccc;">
                </div>
            </div>

        </form>
        <script>
            $(function() {
                $("#cancel").click(function() {
                    dlgclose();
                });
            });

            var preview = document.getElementById('signaturePreview');
            var signatureInput = document.getElementById('signature-user');
            signatureInput.addEventListener('change', function(event) {
                var reader = new FileReader();
                reader.onload = function() {
                    preview.src = reader.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(event.target.files[0]);
            });

            function reloadSignature() {
                preview.src = "<?php echo $iter["url"] ?>";
                signatureInput.value = "";
            }
        </script>
</BODY>

</HTML>