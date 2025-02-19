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
