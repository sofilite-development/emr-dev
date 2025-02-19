<?php

/**
 * Fetch Immunization Records
 */
function getImmunizationRecords($pid)
{
    $query = "SELECT im.*, cd.code_text, DATE(administered_date) AS administered_date,
        DATE_FORMAT(administered_date,'%m/%d/%Y') AS administered_formatted, lo.title as route_of_administration,
        u.title, u.fname, u.mname, u.lname, u.npi, u.street, u.streetb, u.city, u.state, u.zip, u.phonew1,
        f.name, f.phone, lo.notes as route_code
        FROM immunizations AS im
        LEFT JOIN codes AS cd ON cd.code = im.cvx_code
        JOIN code_types AS ctype ON ctype.ct_key = 'CVX' AND ctype.ct_id=cd.code_type
        LEFT JOIN list_options AS lo ON lo.list_id = 'drug_route' AND lo.option_id = im.route
        LEFT JOIN users AS u ON u.id = im.administered_by_id
        LEFT JOIN facility AS f ON f.id = u.facility_id
        WHERE im.patient_id=?";
    $result = sqlStatement($query, array($pid));

    $immunRecords = [];
    while ($row = sqlFetchArray($result)) {
        $immunRecords[] = $row;
    }

    return $immunRecords;
}

/**
 * Fetch Medications
 */
function getMedications($pid)
{
    $query = "SELECT id, title AS medication, begdate AS start_date, enddate AS stop_date, 
                     diagnosis, comments, activity 
              FROM lists 
              WHERE pid = ? AND type = 'medication' 
              ORDER BY begdate";

    $result = sqlStatement($query, array($pid));

    $medications = [];
    while ($row = sqlFetchArray($result)) {
        $medications[] = [
            'id' => $row['id'],
            'medication' => $row['medication'],
            'start_date' => $row['start_date'],
            'stop_date' => $row['stop_date'],
            'diagnosis' => $row['diagnosis'],
            'comments' => $row['comments'],
            'active' => ($row['activity'] == 1) ? true : false
        ];
    }

    return $medications;
}

/**
 * Fetch Prescriptions
 */
function getPrescriptions($pid)
{
    $sql = "SELECT * FROM prescriptions WHERE `patient_id` = ? AND `end_date` IS NULL ORDER BY `start_date`";
    $result = sqlStatement($sql, array($pid));

    $prescriptions = [];
    while ($row = sqlFetchArray($result)) {
        $prescriptions[] = $row;
    }

    return $prescriptions;
}

/**
 * Fetch Allergies
 */
function getAllergies($pid)
{
    $sql = "SELECT * FROM lists WHERE pid = ? AND type = 'allergy' ORDER BY begdate";

    $result = sqlStatement($sql, array($pid));

    $allergies = [];
    while ($row = sqlFetchArray($result)) {
        $allergies[] = $row;
    }

    return $allergies;
}

/**
 * Fetch Lab Results
 */
function getLabResults($pid)
{
    $selects = "po.procedure_order_id, po.date_ordered, pc.procedure_order_seq, 
                pt1.procedure_type_id AS order_type_id, pc.procedure_name, 
                pr.procedure_report_id, pr.date_report, pr.date_collected, pr.specimen_num, 
                pr.report_status, pr.review_status";

    $joins = "JOIN procedure_order_code AS pc ON pc.procedure_order_id = po.procedure_order_id
              LEFT JOIN procedure_type AS pt1 ON pt1.lab_id = po.lab_id AND pt1.procedure_code = pc.procedure_code
              LEFT JOIN procedure_report AS pr ON pr.procedure_order_id = po.procedure_order_id 
              AND pr.procedure_order_seq = pc.procedure_order_seq";

    $orderby = "po.date_ordered, po.procedure_order_id, 
                pc.procedure_order_seq, pr.procedure_report_id";

    $where = "po.patient_id = ?";

    // Fetch lab orders
    $query = "SELECT $selects FROM procedure_order AS po $joins WHERE $where ORDER BY $orderby";
    $res = sqlStatement($query, array($pid));

    $labResults = [];
    while ($row = sqlFetchArray($res)) {
        $orderTypeId = empty($row['order_type_id']) ? 0 : ($row['order_type_id'] + 0);
        $reportId = empty($row['procedure_report_id']) ? 0 : ($row['procedure_report_id'] + 0);

        $selectsResults = "pt2.procedure_type, pt2.procedure_code, pt2.units AS pt2_units, 
                           pt2.range AS pt2_range, pt2.procedure_type_id AS procedure_type_id, 
                           pt2.name AS name, pt2.description, pt2.seq AS seq, 
                           ps.procedure_result_id, ps.result_code AS result_code, ps.result_text, 
                           ps.abnormal, ps.result, ps.range, ps.result_status, ps.facility, 
                           ps.comments, ps.units";

        // Condition for `procedure_type_id`
        $pt2cond = "pt2.parent = '" . add_escape_custom($orderTypeId) . "' 
                    AND (pt2.procedure_type LIKE 'res%' OR pt2.procedure_type LIKE 'rec%')";

        // Condition for `procedure_report_id`
        $pscond = "ps.procedure_report_id = '" . add_escape_custom($reportId) . "'";

        $joincond = "ps.result_code = pt2.procedure_code";

        // Full outer join emulation using UNION
        $queryResults = "(SELECT $selectsResults FROM procedure_type AS pt2 
                         LEFT JOIN procedure_result AS ps ON $pscond AND $joincond 
                         WHERE $pt2cond)
                         UNION
                         (SELECT $selectsResults FROM procedure_result AS ps 
                         LEFT JOIN procedure_type AS pt2 ON $pt2cond AND $joincond 
                         WHERE $pscond)
                         ORDER BY seq, name, procedure_type_id, result_code";

        $rres = sqlStatement($queryResults);
        $results = [];

        while ($rrow = sqlFetchArray($rres)) {
            $results[] = [
                "test_name" => $rrow['name'],
                "abnormal" => $rrow['abnormal'],
                "result" => $rrow['result'],
                "range" => $rrow['pt2_range'],
                "units" => $rrow['pt2_units'],
                "result_status" => $rrow['result_status'],
            ];
        }

        $labResults[] = [
            "order_id" => $row['procedure_order_id'],
            "order_date" => $row['date_ordered'],
            "procedure_name" => $row['procedure_name'],
            "date_report" => $row['date_report'],
            "date_collected" => $row['date_collected'],
            "specimen_number" => $row['specimen_num'],
            "report_status" => $row['report_status'],
            "review_status" => $row['review_status'],
            "test_results" => $results
        ];
    }

    return $labResults;
}

/**
 * Fetch Problems
 */
function getProblems($pid)
{
    $sql = "SELECT * FROM lists WHERE pid = ? AND type = 'medical_problem' ORDER BY begdate";
    $res = sqlStatement($sql, array($pid));

    $problems = [];
    while ($row = sqlFetchArray($res)) {
        $problems[] = [
            "title" => $row['title'],
            "reported_date" => $row['date'],
            "start_date" => $row['begdate'],
            "end_date" => $row['enddate'],
        ];
    }

    return $problems;
}
