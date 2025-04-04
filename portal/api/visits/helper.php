<?php

function getAllEncounters($pid)
{
    $sql = "SELECT fe.id, fe.date, fe.reason, fe.encounter, u.fname, u.lname, 
                   openemr_postcalendar_categories.pc_catname
            FROM form_encounter AS fe
            LEFT JOIN users AS u ON fe.provider_id = u.id
            LEFT JOIN openemr_postcalendar_categories ON fe.pc_catid = openemr_postcalendar_categories.pc_catid
            WHERE fe.pid = ?
            ORDER BY fe.date DESC";

    $results = sqlStatement($sql, [$pid]);

    $encounters = [];
    while ($row = sqlFetchArray($results)) {
        $encounters[] = [
            'id' => $row['id'],
            'encounter' => $row['encounter'],
            'date' => $row['date'],
            'reason' => $row['reason'],
            'provider' => $row['fname'] . ' ' . $row['lname'],
            'category' => $row['pc_catname'],
        ];
    }

    return $encounters;
}

function getEncounterById($encounterId)
{
    $sql = "SELECT fe.*, u.fname, u.lname, f.name as facility_name,
                   cat.pc_catname
            FROM form_encounter AS fe
            LEFT JOIN users AS u ON fe.provider_id = u.id
            LEFT JOIN facility AS f ON fe.facility_id = f.id
            LEFT JOIN openemr_postcalendar_categories AS cat ON fe.pc_catid = cat.pc_catid
            WHERE fe.id = ?";

    $result = sqlQuery($sql, [$encounterId]);

    if (!$result) {
        return null;
    }

    return [
        'id' => $result['id'],
        'encounter' => $result['encounter'],
        'date' => $result['date'],
        'reason' => $result['reason'],
        'facility' => $result['facility_name'],
        'category' => $result['pc_catname'],
        'provider' => $result['fname'] . ' ' . $result['lname'],
        'billing_facility' => $result['billing_facility'],
        'sensitivity' => $result['sensitivity'],
        'referral_source' => $result['referral_source'],
        'class_code' => $result['class_code'],
        'pos_code' => $result['pos_code'],
        'encounter_type' => $result['encounter_type_code'],
        'encounter_type_description' => $result['encounter_type_description'],
        'referring_provider_id' => $result['referring_provider_id'],
        'ordering_provider_id' => $result['ordering_provider_id'],
        'discharge_disposition' => $result['discharge_disposition'],
        'in_collection' => $result['in_collection'],
    ];
}
