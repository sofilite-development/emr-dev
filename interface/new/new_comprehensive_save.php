<?php

/**
 * new_comprehensive_save.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Rod Roark <rod@sunsetsystems.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2009-2017 Rod Roark <rod@sunsetsystems.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../globals.php");
require_once("../../library/RabbitMQ/RabbitMQService.php");

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Services\ContactService;
use OpenEMR\Events\Patient\PatientBeforeCreatedAuxEvent;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Library\RabbitMQ\RabbitMQService;



if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
    CsrfUtils::csrfNotVerified();
}

// Validation for non-unique external patient identifier.
$alertmsg = '';
if (!empty($_POST["form_pubpid"])) {
    $form_pubpid = trim($_POST["form_pubpid"]);
    $result = sqlQuery("SELECT count(*) AS count FROM patient_data WHERE " .
        "pubpid = ?", array($form_pubpid));
    if ($result['count']) {
        // Error, not unique.
        $alertmsg = xl('Warning: Patient ID is not unique!');
    }
}

require_once("$srcdir/pid.inc.php");
require_once("$srcdir/patient.inc.php");
require_once("$srcdir/options.inc.php");

// Update patient_data and employer_data:
// First, we prepare the data for insert into DB by querying the layout
// fields to see what valid fields we have to insert from the post we are receiving
$newdata = array();
$newdata['patient_data'] = array();
$newdata['employer_data'] = array();
$fres = sqlStatement("SELECT * FROM layout_options " .
    "WHERE form_id = 'DEM' AND (uor > 0 OR field_id = 'pubpid') AND field_id != '' " .
    "ORDER BY group_id, seq");
$addressFieldsToSave = array();
while ($frow = sqlFetchArray($fres)) {
    $data_type = $frow['data_type'];
    $field_id = $frow['field_id'];
    // $value     = '';
    $colname = $field_id;
    $tblname = 'patient_data';
    if (strpos($field_id, 'em_') === 0) {
        $colname = substr($field_id, 3);
        $tblname = 'employer_data';
    }

    //get value only if field exist in $_POST (prevent deleting of field with disabled attribute)
    // TODO: why is this a different conditional than demographics_save.php...
    if ($data_type == 54) { // address list
        $addressFieldsToSave[$field_id] = get_layout_form_value($frow);
    } else if (isset($_POST["form_$field_id"]) || $field_id == "pubpid") {
        $value = get_layout_form_value($frow);
        $newdata[$tblname][$colname] = $value;
    }
}


function handleSendingMsgWithNewPatientData($pid)
{
    $rabbitMQ = new RabbitMQService();
    try {

        $patient = sqlQuery("SELECT * FROM patient_data WHERE pid = ?", array($pid));
        if (!empty($patient)) {
            $providerData = array();
            $organizationData = array();
            $provider = sqlQuery("SELECT * FROM users WHERE id = ?", array($patient['providerID']));
            if (!empty($provider)) {
                $providerData = array(
                    'npi' => cleanUtf8($provider['npi']),
                    'firstName' => cleanUtf8($provider['lname']),
                    'lastName' => cleanUtf8($provider['fname']),
                    'middleName' => cleanUtf8($provider['mname']),
                    'email' => cleanUtf8($provider['email']),
                    'phone' => cleanUtf8($provider['phone']),
                    'street' => cleanUtf8($provider['street']),
                    'city' => cleanUtf8($provider['city']),
                    'state' => cleanUtf8($provider['state']),
                );
                $organization = sqlQuery("SELECT * FROM facility WHERE id = ?", array($provider['facility_id']));
                if (!empty($organization)) {
                    $organizationData = array(
                        'id' => cleanUtf8($organization['id']),
                        'name' => cleanUtf8($organization['name']),
                        'street' => cleanUtf8($organization['street']),
                        'city' => cleanUtf8($organization['city']),
                        'state' => cleanUtf8($organization['state']),
                        'zip' => cleanUtf8($organization['postal_code']),
                        'uuid' => UuidRegistry::uuidToString($organization['uuid']),
                        'country' => cleanUtf8($organization['country_code']),
                        'fax' => cleanUtf8($organization['fax']),
                        'phone' => cleanUtf8($organization['phone']),
                    );
                }
            }
            $patientData = array(
                'patientMrnId' => cleanUtf8($patient['patientMrnId']),
                'title' => cleanUtf8($patient['title']),
                'fname' => cleanUtf8($patient['fname']),
                'lname' => cleanUtf8($patient['lname']),
                'mname' => cleanUtf8($patient['mname']),
                'dob' => cleanUtf8($patient['DOB']),
                'email' => cleanUtf8($patient['email']),
                'secondaryEmail' => cleanUtf8($patient['email_direct']),
                'street' => cleanUtf8($patient['street']),
                'postalCode' => cleanUtf8($patient['postal_code']),
                'city' => cleanUtf8($patient['city']),
                'state' => cleanUtf8($patient['state']),
                'countryCode' => cleanUtf8($patient['country_code']),
                'driversLicense' => cleanUtf8($patient['drivers_license']),
                'homePhone' => cleanUtf8($patient['phone_home']),
                'phoneBiz' => cleanUtf8($patient['phone_biz']),
                'workPhone' => cleanUtf8($patient['phone_contact']),
                'cellPhone' => cleanUtf8($patient['phone_cell']),
                'pharmacyId' => cleanUtf8($patient['pharmacy_id']),
                'status' => cleanUtf8($patient['status']),
                'contactRelationship' => cleanUtf8($patient['contact_relationship']),
                'date' => cleanUtf8($patient['date']),
                'sex' => cleanUtf8($patient['sex']),
                'race' => cleanUtf8($patient['race']),
                'ethnicity' => cleanUtf8($patient['ethnicity']),
                'provider' => $providerData,
                'organization' => $organizationData,
            );
        }

        $message = json_encode(
            $patientData,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        if ($message === false) {
            error_log("Patient Data : " . print_r($patientData, true));
            throw new Exception("Failed to encode patient data: " . json_last_error_msg());
        }
        $rabbitMQ->sendMessage($message, 'patient_created',);
        $rabbitMQ->close();
    } catch (\Exception $e) {
        error_log("Failed to send Patient Data to rabbitmq: " . $e->getMessage());
        error_log("Patient Data that failed: " . print_r($patientData, true));
        echo "Failed to send Patient Data to rabbitmq: " . $e->getMessage();
    }
}

$guardianId = $newdata['patient_data']["guardianid"] ?? null;

if ($guardianId) {
    $guardian = sqlQuery(
        "SELECT fname, lname, mname, sex, city, state, postal_code, nationality_country, phone_contact, phone_cell, email 
     FROM patient_data 
     WHERE pid = ?",
        array($guardianId)
    );

    $newdata['patient_data']["guardianrelationship"] = $newdata['patient_data']["guardian_relationship"] ?? null;

    if ($guardian) {
        $fullNameParts = array_filter([
            $guardian["fname"] ?? '',
            $guardian["mname"] ?? '',
            $guardian["lname"] ?? ''
        ]);

        $newdata['patient_data']["guardiansname"] = implode(" ", $fullNameParts) ?? $newdata['patient_data']["guardiansname"] ?? null;
        $newdata['patient_data']["guardiansex"] = $guardian["sex"] ?? $newdata['patient_data']["guardiansex"] ?? null;
        $newdata['patient_data']["guardiancity"] = $guardian["city"] ?? $newdata['patient_data']["guardiancity"] ?? null;
        $newdata['patient_data']["guardianstate"] = $guardian["state"] ?? $newdata['patient_data']["guardianstate"] ?? null;
        $newdata['patient_data']["guardiancountry"] = $guardian["nationality_country"] ?? $newdata['patient_data']["guardiancountry"] ?? null;
        $newdata['patient_data']["guardianpostalcode"] = $guardian["postal_code"] ?? $newdata['patient_data']["guardianpostalcode"] ?? null;
        $newdata['patient_data']["guardianphone"] = $guardian["phone_contact"] ?? $newdata['patient_data']["guardianphone"] ?? null;
        $newdata['patient_data']["guardianworkphone"] = $guardian["phone_cell"] ?? $newdata['patient_data']["guardianworkphone"] ?? null;
        $newdata['patient_data']["guardianemail"] = $guardian["email"] ?? $newdata['patient_data']["guardianemail"] ?? null;
    }
}

// Use the global helper to use the PatientService to create a new patient
// The result contains the pid, so use that to set the global session pid
$pid = updatePatientData(null, $newdata['patient_data'], true);
if (!empty($pid)) {
    handleSendingMsgWithNewPatientData($pid);
}

if (empty($pid)) {
    die("Internal error: setpid(" . text($pid) . ") failed!");
}
setpid($pid);
if (!$GLOBALS['omit_employers']) {
    updateEmployerData($pid, $newdata['employer_data'], true);
}

if (!empty($addressFieldsToSave)) {
    // TODO: we would handle other types of address fields here, for now we will just go through and populate the patient
    // address information
    // TODO: how are error messages supposed to display if the save fails?
    foreach ($addressFieldsToSave as $field => $addressFieldData) {
        // if we need to save other kinds of addresses we could do that here with our field column...
        $contactService = new ContactService();
        $contactService->saveContactsForPatient($pid, $addressFieldData);
    }
}

/**
 * Parse demographics data to listeners who want data that is not directly available in
 * the patient_data table on update
 */
$GLOBALS["kernel"]->getEventDispatcher()->dispatch(new PatientBeforeCreatedAuxEvent($pid, $_POST), PatientBeforeCreatedAuxEvent::EVENT_HANDLE, 10);


$i1dob = DateToYYYYMMDD(filter_input(INPUT_POST, "i1subscriber_DOB"));
$i1date = DateToYYYYMMDD(filter_input(INPUT_POST, "i1effective_date"));

newHistoryData($pid);
// no need to save insurance for simple demos
if (!$GLOBALS['simplified_demographics']) {
    newInsuranceData(
        $pid,
        "primary",
        filter_input(INPUT_POST, "i1provider"),
        filter_input(INPUT_POST, "i1policy_number"),
        filter_input(INPUT_POST, "i1group_number"),
        filter_input(INPUT_POST, "i1plan_name"),
        filter_input(INPUT_POST, "i1subscriber_lname"),
        filter_input(INPUT_POST, "i1subscriber_mname"),
        filter_input(INPUT_POST, "i1subscriber_fname"),
        filter_input(INPUT_POST, "form_i1subscriber_relationship"),
        filter_input(INPUT_POST, "i1subscriber_ss"),
        $i1dob,
        filter_input(INPUT_POST, "i1subscriber_street"),
        filter_input(INPUT_POST, "i1subscriber_postal_code"),
        filter_input(INPUT_POST, "i1subscriber_city"),
        filter_input(INPUT_POST, "form_i1subscriber_state"),
        filter_input(INPUT_POST, "form_i1subscriber_country"),
        filter_input(INPUT_POST, "i1subscriber_phone"),
        filter_input(INPUT_POST, "i1subscriber_employer"),
        filter_input(INPUT_POST, "i1subscriber_employer_street"),
        filter_input(INPUT_POST, "i1subscriber_employer_city"),
        filter_input(INPUT_POST, "i1subscriber_employer_postal_code"),
        filter_input(INPUT_POST, "form_i1subscriber_employer_state"),
        filter_input(INPUT_POST, "form_i1subscriber_employer_country"),
        filter_input(INPUT_POST, 'i1copay'),
        filter_input(INPUT_POST, 'form_i1subscriber_sex'),
        $i1date,
        filter_input(INPUT_POST, 'i1accept_assignment')
    );

    //Dont save more than one insurance since only one is allowed / save space in DB
    if (!$GLOBALS['insurance_only_one']) {
        $i2dob = DateToYYYYMMDD(filter_input(INPUT_POST, "i2subscriber_DOB"));
        $i2date = DateToYYYYMMDD(filter_input(INPUT_POST, "i2effective_date"));

        newInsuranceData(
            $pid,
            "secondary",
            filter_input(INPUT_POST, "i2provider"),
            filter_input(INPUT_POST, "i2policy_number"),
            filter_input(INPUT_POST, "i2group_number"),
            filter_input(INPUT_POST, "i2plan_name"),
            filter_input(INPUT_POST, "i2subscriber_lname"),
            filter_input(INPUT_POST, "i2subscriber_mname"),
            filter_input(INPUT_POST, "i2subscriber_fname"),
            filter_input(INPUT_POST, "form_i2subscriber_relationship"),
            filter_input(INPUT_POST, "i2subscriber_ss"),
            $i2dob,
            filter_input(INPUT_POST, "i2subscriber_street"),
            filter_input(INPUT_POST, "i2subscriber_postal_code"),
            filter_input(INPUT_POST, "i2subscriber_city"),
            filter_input(INPUT_POST, "form_i2subscriber_state"),
            filter_input(INPUT_POST, "form_i2subscriber_country"),
            filter_input(INPUT_POST, "i2subscriber_phone"),
            filter_input(INPUT_POST, "i2subscriber_employer"),
            filter_input(INPUT_POST, "i2subscriber_employer_street"),
            filter_input(INPUT_POST, "i2subscriber_employer_city"),
            filter_input(INPUT_POST, "i2subscriber_employer_postal_code"),
            filter_input(INPUT_POST, "form_i2subscriber_employer_state"),
            filter_input(INPUT_POST, "form_i2subscriber_employer_country"),
            filter_input(INPUT_POST, 'i2copay'),
            filter_input(INPUT_POST, 'form_i2subscriber_sex'),
            $i2date,
            filter_input(INPUT_POST, 'i2accept_assignment')
        );

        $i3dob = DateToYYYYMMDD(filter_input(INPUT_POST, "i3subscriber_DOB"));
        $i3date = DateToYYYYMMDD(filter_input(INPUT_POST, "i3effective_date"));

        newInsuranceData(
            $pid,
            "tertiary",
            filter_input(INPUT_POST, "i3provider"),
            filter_input(INPUT_POST, "i3policy_number"),
            filter_input(INPUT_POST, "i3group_number"),
            filter_input(INPUT_POST, "i3plan_name"),
            filter_input(INPUT_POST, "i3subscriber_lname"),
            filter_input(INPUT_POST, "i3subscriber_mname"),
            filter_input(INPUT_POST, "i3subscriber_fname"),
            filter_input(INPUT_POST, "form_i3subscriber_relationship"),
            filter_input(INPUT_POST, "i3subscriber_ss"),
            $i3dob,
            filter_input(INPUT_POST, "i3subscriber_street"),
            filter_input(INPUT_POST, "i3subscriber_postal_code"),
            filter_input(INPUT_POST, "i3subscriber_city"),
            filter_input(INPUT_POST, "form_i3subscriber_state"),
            filter_input(INPUT_POST, "form_i3subscriber_country"),
            filter_input(INPUT_POST, "i3subscriber_phone"),
            filter_input(INPUT_POST, "i3subscriber_employer"),
            filter_input(INPUT_POST, "i3subscriber_employer_street"),
            filter_input(INPUT_POST, "i3subscriber_employer_city"),
            filter_input(INPUT_POST, "i3subscriber_employer_postal_code"),
            filter_input(INPUT_POST, "form_i3subscriber_employer_state"),
            filter_input(INPUT_POST, "form_i3subscriber_employer_country"),
            filter_input(INPUT_POST, 'i3copay'),
            filter_input(INPUT_POST, 'form_i3subscriber_sex'),
            $i3date,
            filter_input(INPUT_POST, 'i3accept_assignment')
        );
    }
}


?>



<html>

<body>
    <script>
        <?php
        if ($alertmsg) {
            echo "alert(" . js_escape($alertmsg) . ");\n";
        }

        echo "window.location='$rootdir/patient_file/summary/demographics.php?" .
            "set_pid=" . attr_url($pid) . "&is_new=1';\n";
        ?>
    </script>

</body>

</html>