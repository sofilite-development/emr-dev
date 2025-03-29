<?php

function getPatientBilling($pid, $from_date, $to_date)
{
    $from_datetime = $from_date . ' 00:00:00';
    $to_datetime = $to_date . ' 23:59:59';

    $sql = "SELECT 
                b.code_type, b.code, b.code_text, b.pid, b.provider_id,
                b.billed, b.payer_id, b.units, b.fee, b.bill_date, b.id,
                ins.name AS payer_name,
                fe.encounter, fe.date, fe.reason, fe.provider_id
            FROM form_encounter AS fe
            LEFT JOIN billing AS b ON b.pid = fe.pid AND b.encounter = fe.encounter
            LEFT JOIN insurance_companies AS ins ON b.payer_id = ins.id
            LEFT OUTER JOIN code_types AS c ON c.ct_key = b.code_type
            WHERE fe.date >= ? AND fe.date <= ? AND fe.pid = ? 
            AND c.ct_proc = '1' AND b.activity > 0
            ORDER BY fe.date, fe.id";

    $rows = [];
    $res = sqlStatement($sql, array($from_datetime, $to_datetime, $pid));
    while ($row = sqlFetchArray($res)) {
        $rows[] = $row;
    }

    return $rows;
}

function getGroupedPatientBilling($pid, $from_date, $to_date)
{
    if (!$pid) {
        return [];
    }

    $sql = "SELECT 
                b.code_type, b.code, b.code_text, b.pid, b.provider_id,
                b.billed, b.payer_id, b.units, b.fee, b.bill_date, b.id,
                ins.name AS payer_name,
                fe.encounter, fe.date, fe.reason, fe.provider_id
            FROM form_encounter AS fe
            LEFT JOIN billing AS b ON b.pid = fe.pid AND b.encounter = fe.encounter
            LEFT JOIN insurance_companies AS ins ON b.payer_id = ins.id
            LEFT OUTER JOIN code_types AS c ON c.ct_key = b.code_type
            WHERE fe.date >= ? AND fe.date <= ? AND fe.pid = ?
            AND c.ct_proc = '1' AND b.activity > 0
            ORDER BY fe.date, fe.id";

    $res = sqlStatement($sql, [$from_date . ' 00:00:00', $to_date . ' 23:59:59', $pid]);
    $grouped = [];

    $grandTotal = 0;

    while ($row = sqlFetchArray($res)) {
        $enc_key = $row['encounter'] . '_' . substr($row['date'], 0, 10);

        if (!isset($grouped[$enc_key])) {
            $grouped[$enc_key] = [
                'encounter' => $row['encounter'],
                'date' => $row['date'],
                'reason' => $row['reason'],
                'provider_id' => $row['provider_id'],
                'totals' => ['units' => 0, 'charges' => 0.00],
                'items' => []
            ];
        }

        $grouped[$enc_key]['items'][] = $row;
        $grouped[$enc_key]['totals']['units'] += (int) $row['units'];
        $grouped[$enc_key]['totals']['charges'] += (float) $row['fee'];
        $grandTotal += (float) $row['fee'];
    }

    return ['grandTotal' => $grandTotal, 'encounters' => array_values($grouped)];
}


function getLastMonthBilling($pid)
{
    if (!$pid) {
        return [];
    }

    // Get first and last day of the previous month
    $from_date = date("Y-m-01", strtotime("first day of last month"));
    $to_date = date("Y-m-t", strtotime("last day of last month"));

    return getGroupedPatientBilling($pid, $from_date, $to_date);
}

function getLast7DaysBilling($pid)
{
    if (!$pid) {
        return [];
    }

    $today = date('Y-m-d');
    $sevenDaysAgo = date('Y-m-d', strtotime('-6 days')); // include today = 7 days total

    return getGroupedPatientBilling($pid, $sevenDaysAgo, $today);
}

function getAllPatientBilling($pid)
{
    if (!$pid) {
        return [];
    }

    // Use earliest possible date and today
    $from_date = '1900-01-01';
    $to_date = date('Y-m-d');

    return getGroupedPatientBilling($pid, $from_date, $to_date);
}

function getEncounterBilling($pid, $encounterId)
{
    if (!$pid || !$encounterId) {
        return [];
    }

    $sql = "SELECT 
                b.code_type, b.code, b.code_text, b.pid, b.provider_id,
                b.billed, b.payer_id, b.units, b.fee, b.bill_date, b.id,
                ins.name AS payer_name,
                fe.encounter, fe.date, fe.reason, fe.provider_id
            FROM form_encounter AS fe
            LEFT JOIN billing AS b ON b.pid = fe.pid AND b.encounter = fe.encounter
            LEFT JOIN insurance_companies AS ins ON b.payer_id = ins.id
            LEFT OUTER JOIN code_types AS c ON c.ct_key = b.code_type
            WHERE fe.pid = ? AND fe.encounter = ?
            AND c.ct_proc = '1' AND b.activity > 0
            ORDER BY b.id";

    $result = sqlStatement($sql, [$pid, $encounterId]);
    $items = [];
    $totals = ['units' => 0, 'charges' => 0.00];
    $reason  = "";
    $date  = "";

    while ($row = sqlFetchArray($result)) {
        $items[] = $row;
        $totals['units'] += (int) $row['units'];
        $totals['charges'] += (float) $row['fee'];
        $reason = $row["reason"];
        $date = $row['date'];
    }

    return [
        'encounter' => $encounterId,
        'pid' => $pid,
        'totals' => $totals,
        'items' => $items,
        "reason" => $reason,
        "date" => $date
    ];
}
