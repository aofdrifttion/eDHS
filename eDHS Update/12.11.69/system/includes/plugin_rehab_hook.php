<?php
/**
 * Rehab & Medical Device Tracker - Real-Time Debtor Event Hook & Bridge
 * eDebtor Hospital System (eDHS) - Phonsai Hospital
 * 
 * Automatically synchronizes debtor cut-offs (ตัดลูกหนี้), receipt attachments,
 * and settlements into imr_tb_rehab_device_claims in 100% real-time.
 */

if (!function_exists('rehab_clean_receipt_no')) {
    function rehab_clean_receipt_no($raw) {
        if ($raw === null) return '';
        $val = trim((string)$raw);
        $val = trim($val, "\"'\t\n\r -");
        if ($val === '' || $val === '-' || $val === '""' || $val === "''" || strlen($val) < 2) {
            return '';
        }
        return $val;
    }
}

/**
 * Event Hook: Triggered instantly when debtor cut-off or receipt changes in eDHS.
 * Handles single or multiple VN/AN identifiers with near-zero overhead.
 *
 * @param mysqli $conn
 * @param string|array $targets Single VN/AN or array of VN/ANs
 * @param string $visit_type 'AUTO' | 'OPD' | 'IPD'
 * @return int Number of rehab claims synchronized
 */
function rehab_hook_sync_vns($conn, $targets, $visit_type = 'AUTO') {
    if (!isset($conn) || !$conn) return 0;
    if (empty($targets)) return 0;

    if (!is_array($targets)) {
        $targets = [$targets];
    }

    $clean_targets = [];
    foreach ($targets as $t) {
        $t = trim((string)$t);
        if ($t !== '' && $t !== '-') {
            $clean_targets[] = mysqli_real_escape_string($conn, $t);
        }
    }
    if (empty($clean_targets)) return 0;

    // Fast static check if plugin claims table exists
    static $plugin_table_exists = null;
    if ($plugin_table_exists === null) {
        $chk = mysqli_query($conn, "SHOW TABLES LIKE 'imr_tb_rehab_device_claims'");
        $plugin_table_exists = ($chk && mysqli_num_rows($chk) > 0);
    }
    if (!$plugin_table_exists) return 0;

    // Ultra-fast filter: check if any target VN/AN actually exists in rehab device claims
    $in_clause = "'" . implode("','", $clean_targets) . "'";
    $chk_match = mysqli_query($conn, "SELECT id FROM `imr_tb_rehab_device_claims` WHERE vn IN ($in_clause) OR an IN ($in_clause) LIMIT 1");
    if (!$chk_match || mysqli_num_rows($chk_match) === 0) {
        // Unrelated debtor cut (e.g., general medicine / non-rehab visit) -> return immediately
        return 0;
    }

    $updated_count = 0;

    // 1. Process OPD targets
    if ($visit_type === 'AUTO' || $visit_type === 'OPD') {
        $sql_opd = "SELECT r.id, r.vn, r.claim_amount, r.claim_status, r.compensated_amount, r.rep_no, r.receipt_source,
                           o.bill, o.billdate, o.mobile, o.follow_money, o.rcpno
                    FROM `imr_tb_rehab_device_claims` r
                    JOIN `imr_tb_debtor_rights_opd` o ON r.vn = o.vn
                    WHERE r.vn IN ($in_clause)";
        $res_opd = mysqli_query($conn, $sql_opd);

        if ($res_opd && mysqli_num_rows($res_opd) > 0) {
            $stmt_set = mysqli_prepare($conn, "UPDATE `imr_tb_rehab_device_claims` SET 
                receipt_no = ?,
                receipt_date = ?,
                receipt_source = 'DEBTOR_OPD',
                compensated_amount = IF(compensated_amount > 0, compensated_amount, ?),
                diff_amount = GREATEST(0, claim_amount - IF(compensated_amount > 0, compensated_amount, ?)),
                claim_status = IF(claim_status = 'PAID', 'PAID', ?),
                rep_no = IF(rep_no IS NOT NULL AND rep_no != '' AND rep_no NOT IN ('\"\"', \"''\", '-'), rep_no, ?)
                WHERE id = ?");

            $stmt_clear = mysqli_prepare($conn, "UPDATE `imr_tb_rehab_device_claims` SET
                receipt_no = NULL,
                receipt_date = NULL,
                receipt_source = NULL,
                compensated_amount = 0.00,
                diff_amount = claim_amount,
                claim_status = IF(rep_no IS NOT NULL AND rep_no != '' AND rep_no NOT IN ('\"\"', \"''\", '-'), 'WAIT_STM', 'UNCLAIMED')
                WHERE id = ? AND receipt_source = 'DEBTOR_OPD'");

            while ($r = mysqli_fetch_assoc($res_opd)) {
                $receipt_no = '';
                $receipt_date = '';

                $mob = trim($r['mobile'] ?? '');
                $bill = trim($r['bill'] ?? '');
                $bdate = trim($r['billdate'] ?? '');
                $rcpno = trim($r['rcpno'] ?? '');
                $follow = floatval(trim($r['follow_money'] ?? 0));
                $claim_amt = floatval($r['claim_amount']);

                if (!empty($mob) && $mob !== '-') {
                    $parts = explode('-', $mob);
                    $clean_part = rehab_clean_receipt_no($parts[0]);
                    if (!empty($clean_part)) {
                        $receipt_no = $clean_part;
                        if (isset($parts[1])) $receipt_date = trim($parts[1]);
                    }
                }

                if (empty($receipt_no) && !empty($bill) && $bill !== '-') {
                    $clean_b = rehab_clean_receipt_no($bill);
                    if (!empty($clean_b)) {
                        $receipt_no = $clean_b;
                        $receipt_date = $bdate;
                    }
                }

                if (empty($receipt_no) && !empty($rcpno)) {
                    $clean_rcp = rehab_clean_receipt_no($rcpno);
                    if (!empty($clean_rcp)) {
                        $receipt_no = $clean_rcp;
                    }
                }

                // If receipt was deleted or is absent
                if (empty($receipt_no) && $follow <= 0) {
                    if ($stmt_clear && $r['receipt_source'] === 'DEBTOR_OPD') {
                        mysqli_stmt_bind_param($stmt_clear, "i", $r['id']);
                        if (mysqli_stmt_execute($stmt_clear)) $updated_count++;
                    }
                    continue;
                }

                $comp = $claim_amt;
                if ($follow > 0) {
                    $comp = min($claim_amt, $follow);
                }

                $st = ($comp >= $claim_amt) ? 'PAID' : 'PARTIAL';
                $rcpt_param = !empty($receipt_no) ? $receipt_no : null;
                $rcpt_date_param = !empty($receipt_date) ? $receipt_date : null;
                $rep_param = !empty($receipt_no) ? $receipt_no : null;

                if ($stmt_set) {
                    mysqli_stmt_bind_param($stmt_set, "ssddssi", $rcpt_param, $rcpt_date_param, $comp, $comp, $st, $rep_param, $r['id']);
                    if (mysqli_stmt_execute($stmt_set)) {
                        $updated_count++;
                    }
                }
            }

            if ($stmt_set) mysqli_stmt_close($stmt_set);
            if ($stmt_clear) mysqli_stmt_close($stmt_clear);
        }
    }

    // 2. Process IPD targets
    if ($visit_type === 'AUTO' || $visit_type === 'IPD') {
        $sql_ipd = "SELECT r.id, r.an, r.claim_amount, r.claim_status, r.compensated_amount, r.rep_no, r.receipt_source,
                           i.bill, i.billdate, i.mobile, i.follow_money, i.rcpno
                    FROM `imr_tb_rehab_device_claims` r
                    JOIN `imr_tb_debtor_rights_ipd` i ON r.an = i.an
                    WHERE r.an IN ($in_clause)";
        $res_ipd = mysqli_query($conn, $sql_ipd);

        if ($res_ipd && mysqli_num_rows($res_ipd) > 0) {
            $stmt_set_ipd = mysqli_prepare($conn, "UPDATE `imr_tb_rehab_device_claims` SET 
                receipt_no = ?,
                receipt_date = ?,
                receipt_source = 'DEBTOR_IPD',
                compensated_amount = IF(compensated_amount > 0, compensated_amount, ?),
                diff_amount = GREATEST(0, claim_amount - IF(compensated_amount > 0, compensated_amount, ?)),
                claim_status = IF(claim_status = 'PAID', 'PAID', ?),
                rep_no = IF(rep_no IS NOT NULL AND rep_no != '' AND rep_no NOT IN ('\"\"', \"''\", '-'), rep_no, ?)
                WHERE id = ?");

            $stmt_clear_ipd = mysqli_prepare($conn, "UPDATE `imr_tb_rehab_device_claims` SET
                receipt_no = NULL,
                receipt_date = NULL,
                receipt_source = NULL,
                compensated_amount = 0.00,
                diff_amount = claim_amount,
                claim_status = IF(rep_no IS NOT NULL AND rep_no != '' AND rep_no NOT IN ('\"\"', \"''\", '-'), 'WAIT_STM', 'UNCLAIMED')
                WHERE id = ? AND receipt_source = 'DEBTOR_IPD'");

            while ($r = mysqli_fetch_assoc($res_ipd)) {
                $receipt_no = '';
                $receipt_date = '';

                $mob = trim($r['mobile'] ?? '');
                $bill = trim($r['bill'] ?? '');
                $bdate = trim($r['billdate'] ?? '');
                $rcpno = trim($r['rcpno'] ?? '');
                $follow = floatval(trim($r['follow_money'] ?? 0));
                $claim_amt = floatval($r['claim_amount']);

                if (!empty($mob) && $mob !== '-') {
                    $parts = explode('-', $mob);
                    $clean_part = rehab_clean_receipt_no($parts[0]);
                    if (!empty($clean_part)) {
                        $receipt_no = $clean_part;
                        if (isset($parts[1])) $receipt_date = trim($parts[1]);
                    }
                }

                if (empty($receipt_no) && !empty($bill) && $bill !== '-') {
                    $clean_b = rehab_clean_receipt_no($bill);
                    if (!empty($clean_b)) {
                        $receipt_no = $clean_b;
                        $receipt_date = $bdate;
                    }
                }

                if (empty($receipt_no) && !empty($rcpno)) {
                    $clean_rcp = rehab_clean_receipt_no($rcpno);
                    if (!empty($clean_rcp)) {
                        $receipt_no = $clean_rcp;
                    }
                }

                // If receipt was deleted or absent
                if (empty($receipt_no) && $follow <= 0) {
                    if ($stmt_clear_ipd && $r['receipt_source'] === 'DEBTOR_IPD') {
                        mysqli_stmt_bind_param($stmt_clear_ipd, "i", $r['id']);
                        if (mysqli_stmt_execute($stmt_clear_ipd)) $updated_count++;
                    }
                    continue;
                }

                $comp = $claim_amt;
                if ($follow > 0) {
                    $comp = min($claim_amt, $follow);
                }

                $st = ($comp >= $claim_amt) ? 'PAID' : 'PARTIAL';
                $rcpt_param = !empty($receipt_no) ? $receipt_no : null;
                $rcpt_date_param = !empty($receipt_date) ? $receipt_date : null;
                $rep_param = !empty($receipt_no) ? $receipt_no : null;

                if ($stmt_set_ipd) {
                    mysqli_stmt_bind_param($stmt_set_ipd, "ssddssi", $rcpt_param, $rcpt_date_param, $comp, $comp, $st, $rep_param, $r['id']);
                    if (mysqli_stmt_execute($stmt_set_ipd)) {
                        $updated_count++;
                    }
                }
            }

            if ($stmt_set_ipd) mysqli_stmt_close($stmt_set_ipd);
            if ($stmt_clear_ipd) mysqli_stmt_close($stmt_clear_ipd);
        }
    }

    return $updated_count;
}
