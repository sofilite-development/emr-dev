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

function getLastMonthBilling($pid)
{
    if (!$pid) {
        return [];
    }

    // Get first and last day of the previous month
    $from_date = date("Y-m-01", strtotime("first day of last month"));
    $to_date = date("Y-m-t", strtotime("last day of last month"));

    return getPatientBilling($pid, $from_date, $to_date);
}

function getLast7DaysBilling($pid)
{
    if (!$pid) {
        return [];
    }

    $today = date('Y-m-d');
    $sevenDaysAgo = date('Y-m-d', strtotime('-6 days')); // include today = 7 days total

    return getPatientBilling($pid, $sevenDaysAgo, $today);
}

function getAllPatientBilling($pid)
{
    if (!$pid) {
        return [];
    }

    // Use earliest possible date and today
    $from_date = '1900-01-01';
    $to_date = date('Y-m-d');

    return getPatientBilling($pid, $from_date, $to_date);
}
