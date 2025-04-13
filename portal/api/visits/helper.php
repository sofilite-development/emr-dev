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

function getEncounterById($encounterId, $pid) //date, vitals, notes, lab order, lab results
{
    // Base encounter details
    $sql = "SELECT fe.*, u.fname, u.lname, f.name as facility_name, cat.pc_catname
            FROM form_encounter AS fe
            LEFT JOIN users AS u ON fe.provider_id = u.id
            LEFT JOIN facility AS f ON fe.facility_id = f.id
            LEFT JOIN openemr_postcalendar_categories AS cat ON fe.pc_catid = cat.pc_catid
            WHERE fe.id = ?";
    $result = sqlQuery($sql, [$encounterId]);

    if (!$result) {
        return null;
    }

    $encounter = [
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

    $vitalsForm = sqlQuery("SELECT form_id FROM forms WHERE encounter = ? AND formdir = 'vitals' AND deleted = 0", [$result['encounter']]);
    if ($vitalsForm) {
        $vitals = sqlQuery("SELECT * FROM form_vitals WHERE id = ?", [$vitalsForm['form_id']]);
        $encounter['vitals'] = $vitals ?: null;
    } else {
        $encounter['vitals'] = null;
    }

    // Get lab orders
    $labOrders = sqlStatement("SELECT * FROM procedure_order WHERE encounter_id = ? AND activity = 1", [$result['encounter']]);
    $encounter['lab_orders'] = [];
    while ($row = sqlFetchArray($labOrders)) {
        // get results of laborders
        $orderResults = sqlQuery(
            "SELECT * FROM processed_order WHERE order_id = ?",
            array($row['procedure_order_id'])
        );
        $row["result"] = $orderResults;

        $encounter['lab_orders'][] = $row;
    }

    $clinicalNotes = sqlStatement(
        "SELECT id, date, codetext, description, clinical_notes_type, clinical_notes_category 
         FROM form_clinical_notes 
         WHERE encounter = ? AND activity = 1",
        [$result['encounter']]
    );

    $encounter['clinical_notes'] = [];
    while ($row = sqlFetchArray($clinicalNotes)) {
        $typeKey = $row['clinical_notes_type'];
        $catKey = $row['clinical_notes_category'];

        $encounter['clinical_notes'][] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'type' => [
                'key' => $typeKey,
                'label' => $typeLabels[$typeKey] ?? ucfirst(str_replace('_', ' ', $typeKey))
            ],
            'category' => [
                'key' => $catKey,
                'label' => $categoryLabels[$catKey] ?? ucfirst(str_replace('_', ' ', $catKey))
            ],
            'title' => $row['codetext'],
            'description' => $row['description'],
        ];
    }

    $dictations = sqlStatement(
        "
            SELECT f.id AS form_id, f.date, f.pid, f.form_name, 
            f.encounter, f.user, f.groupname, f.formdir,
            fd.id AS dictation_id, fd.dictation, fd.additional_notes 
            FROM forms f
            LEFT JOIN form_dictation fd ON fd.id = f.form_id 
            WHERE f.pid = ? 
            AND f.encounter = ? 
            AND f.formdir = 'dictation'
            AND f.deleted = 0",
        array($pid, $result['encounter'])
    );

    $encounter['dictations'] = [];
    while ($row = sqlFetchArray($dictations)) {
        $encounter['dictations'][] = $row;
    }

    return $encounter;
}
