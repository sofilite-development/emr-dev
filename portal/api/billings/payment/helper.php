<?php

function recordStripeFrontPaymentWithActivity($pid, $amount, $encounter = null, $method = 'credit_card', $desc = 'Stripe Portal Payment') {
    if (!$pid || !$amount) return false;

    global $USERID;
    if (!$USERID) {
        $USERID = $_SESSION['authUserID'] ?? 1;
    }

    $timestamp = date('Y-m-d H:i:s');
    $source = "portal_payment";

    $patdata = sqlQuery("SELECT fname, lname, mname FROM patient_data WHERE pid = ?", [$pid]);
    $NameNew = trim(($patdata['fname'] ?? '') . " " . ($patdata['lname'] ?? '') . " " . ($patdata['mname'] ?? ''));

    // Step 1: Create ar_session
    $adjustment_code = $encounter ? 'patient_payment' : 'pre_payment';
    $session_id = sqlInsert(
        "INSERT INTO ar_session (payer_id, patient_id, user_id, closed, reference, check_date, deposit_date, pay_total, payment_type, description, adjustment_code, post_to_date, payment_method)
         VALUES (0, ?, ?, 0, ?, NOW(), NOW(), ?, 'patient', ?, ?, NOW(), ?)",
        [$pid, $USERID, $source, $amount, $NameNew, $adjustment_code, $method]
    );

    // Step 2: Log in payments table
    frontPayment(
        $pid,
        $encounter ?: 0,
        $method,
        $source,
        $encounter ? 0 : $amount,  // amount1 = 0 for encounter
        $encounter ? $amount : 0,  // amount2 = 0 for prepayment
        $timestamp
    );

    // Step 3: If encounter exists, apply line items into ar_activity
    if ($encounter) {
        $remainingAmount = $amount;

        // Get billing codes for this encounter
        $billingRows = sqlStatement(
            "SELECT code_type, code, modifier, fee FROM billing
             LEFT JOIN code_types ON billing.code_type = code_types.ct_key
             WHERE billing.pid = ? AND billing.encounter = ? AND activity = 1 AND code_types.ct_fee = 1
             ORDER BY code, modifier",
            [$pid, $encounter]
        );

        while ($row = sqlFetchArray($billingRows)) {
            $codeType = $row['code_type'];
            $code = $row['code'];
            $modifier = $row['modifier'];
            $fee = $row['fee'];

            // Get what's already paid for this code
            $paidRow = sqlQuery(
                "SELECT SUM(pay_amount) as paid, SUM(adj_amount) as adjusted
                 FROM ar_activity WHERE pid = ? AND encounter = ? AND code_type = ? AND code = ? AND modifier = ? AND deleted IS NULL",
                [$pid, $encounter, $codeType, $code, $modifier]
            );
            $alreadyPaid = $paidRow['paid'] + $paidRow['adjusted'];
            $remainder = $fee - $alreadyPaid;

            if (round($remainder, 2) <= 0) continue;

            $toApply = min($remainder, $remainingAmount);
            $remainingAmount -= $toApply;

            $seqRow = sqlQuery("SELECT IFNULL(MAX(sequence_no),0) + 1 AS next_seq FROM ar_activity WHERE pid = ? AND encounter = ?", [$pid, $encounter]);

            sqlInsert(
                "INSERT INTO ar_activity (pid, encounter, sequence_no, code_type, code, modifier, payer_type, post_time, post_user, session_id, pay_amount, account_code)
                 VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), ?, ?, ?, 'PP')",
                [$pid, $encounter, $seqRow['next_seq'], $codeType, $code, $modifier, $USERID, $session_id, $toApply]
            );

            if ($remainingAmount <= 0) break;
        }

        // Step 4: If any excess remains, insert one last ar_activity
        if ($remainingAmount > 0) {
            $seqRow = sqlQuery("SELECT IFNULL(MAX(sequence_no),0) + 1 AS next_seq FROM ar_activity WHERE pid = ? AND encounter = ?", [$pid, $encounter]);
            sqlInsert(
                "INSERT INTO ar_activity (pid, encounter, sequence_no, payer_type, post_time, post_user, session_id, pay_amount, account_code)
                 VALUES (?, ?, ?, 0, NOW(), ?, ?, ?, 'PP')",
                [$pid, $encounter, $seqRow['next_seq'], $USERID, $session_id, $remainingAmount]
            );
        }
    }

    return $session_id;
}
