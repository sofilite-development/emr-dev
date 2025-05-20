
<?php
function getPatientProfileData($pid, $given = "*")
{
    $tableName = "patient_data";
    $fields = getTableFields($tableName);
    $sql = "SELECT $given FROM patient_data WHERE pid=? LIMIT 1";
    $result = sqlQuery($sql, array($pid));

    if (!$result) {
        error_log("No patient data found for PID: $pid");
        return null;
    }

    $data = [];
    foreach ($fields as $field) {
        $data[$field] = !empty($result[$field]) ? utf8_encode($result[$field]) : $result[$field];
    }

    if ($data["providerID"]) {
        $provider = getProviderName($data["providerID"]);
        if ($provider) {
            $data["provider"] = $provider;
        }
    }

    if ($data["state"]) {
        $stateQuery = sqlStatement(
            "SELECT * FROM list_options WHERE option_id = ? AND list_id = ? AND activity = 1",
            [$data["state"], "state"]
        );

        $state = sqlFetchArray($stateQuery);

        if ($state) {
            $data["stateName"] = $state["title"];
        }
    }


    return $data;
}

function getTableFields($table)
{
    $sql = "DESCRIBE $table";
    $result = sqlStatement($sql);

    $fields = [];
    while ($row = sqlFetchArray($result)) {
        $fields[] = $row['Field'];
    }

    return $fields;
}

function getLinkedPatient($patientId) {
    $patientQuery = sqlStatement(
        "SELECT fname, lname, mname, pid, allow_patient_portal 
         FROM patient_data 
         WHERE guardianid = ? 
         AND TIMESTAMPDIFF(YEAR, DOB, CURDATE()) < 18
         AND allow_patient_portal = 'YES'",
        [$patientId]
    );

    $patient = [];

    while($row = sqlFetchArray($patientQuery)){
        // $fullNameParts = array_filter([
        //     $row["fname"] ?? '',
        //     $row["mname"] ?? '',
        //     $row["lname"] ?? ''
        // ]);
        // $row["name"] = implode(" ", $fullNameParts);
        $patient[] = $row;
    }

    return $patient;
}
