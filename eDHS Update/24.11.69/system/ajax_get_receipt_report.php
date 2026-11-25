<?php
require __DIR__ . '/database_config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = isset($_POST['action']) ? $_POST['action'] : 'summary';
    $receiptNo = isset($_POST['receiptNo']) ? trim(mysqli_real_escape_string($conn, $_POST['receiptNo'])) : '';
    $startDate = isset($_POST['startDate']) ? mysqli_real_escape_string($conn, $_POST['startDate']) : '';
    $endDate   = isset($_POST['endDate']) ? mysqli_real_escape_string($conn, $_POST['endDate']) : '';
    $receiptType = isset($_POST['receiptType']) ? $_POST['receiptType'] : 'all';
    $receiptTypeDetail = isset($_POST['receiptTypeDetail']) ? mysqli_real_escape_string($conn, $_POST['receiptTypeDetail']) : '';

    $whereOPD = [];
    $whereIPD = [];
    $whereCR  = [];
    $whereSSS = [];

    // Filter by receipt type
    if ($receiptType === 'bill') {
        $hasReceiptCondOPD = "((bill IS NOT NULL AND bill != '' AND bill != '-') AND (mobile IS NULL OR mobile = '' OR mobile = '-' OR (mobile NOT LIKE '%/%' AND mobile NOT LIKE '%-%')))";
        $hasReceiptCondIPD = "((bill IS NOT NULL AND bill != '' AND bill != '-') AND (mobile IS NULL OR mobile = '' OR mobile = '-' OR (mobile NOT LIKE '%/%' AND mobile NOT LIKE '%-%')))";
        $whereCR[]  = "1=0";
        $whereSSS[] = "1=0";
    } elseif ($receiptType === 'mobile') {
        $hasReceiptCondOPD = "(mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND (mobile LIKE '%/%' OR mobile LIKE '%-%'))";
        $hasReceiptCondIPD = "(mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND (mobile LIKE '%/%' OR mobile LIKE '%-%'))";
        $whereCR[]  = "(bill IS NOT NULL AND bill != '' AND bill != '-')";
        $whereSSS[] = "(bill IS NOT NULL AND bill != '' AND bill != '-')";
    } else {
        $hasReceiptCondOPD = "((bill IS NOT NULL AND bill != '' AND bill != '-') OR (mobile IS NOT NULL AND mobile != '' AND mobile != '-'))";
        $hasReceiptCondIPD = "((bill IS NOT NULL AND bill != '' AND bill != '-') OR (mobile IS NOT NULL AND mobile != '' AND mobile != '-'))";
        $whereCR[]  = "(bill IS NOT NULL AND bill != '' AND bill != '-')";
        $whereSSS[] = "(bill IS NOT NULL AND bill != '' AND bill != '-')";
    }
    $whereOPD[] = $hasReceiptCondOPD;
    $whereIPD[] = $hasReceiptCondIPD;

    // Deduplication: ป้องกันเคส CR/SSS ที่ถูกดึงแยกจากตารางย่อย ไม่ให้ขึ้นซ้ำซ้อนจากตารางหลัก OPD/IPD
    $whereOPD[] = "NOT EXISTS (SELECT 1 FROM imr_tb_debtor_cr_breakdown cr WHERE cr.vn = imr_tb_debtor_rights_opd.vn AND cr.visit_type = 'OPD' AND (cr.bill = imr_tb_debtor_rights_opd.bill OR (imr_tb_debtor_rights_opd.mobile LIKE CONCAT(cr.bill, '%'))))";
    $whereOPD[] = "NOT EXISTS (SELECT 1 FROM imr_tb_debtor_sss_breakdown sss WHERE sss.vn = imr_tb_debtor_rights_opd.vn AND sss.visit_type = 'OPD' AND (sss.bill = imr_tb_debtor_rights_opd.bill OR (imr_tb_debtor_rights_opd.mobile LIKE CONCAT(sss.bill, '%'))))";

    $whereIPD[] = "NOT EXISTS (SELECT 1 FROM imr_tb_debtor_cr_breakdown cr WHERE cr.vn = imr_tb_debtor_rights_ipd.an AND cr.visit_type = 'IPD' AND (cr.bill = imr_tb_debtor_rights_ipd.bill OR (imr_tb_debtor_rights_ipd.mobile LIKE CONCAT(cr.bill, '%'))))";
    $whereIPD[] = "NOT EXISTS (SELECT 1 FROM imr_tb_debtor_sss_breakdown sss WHERE sss.vn = imr_tb_debtor_rights_ipd.an AND sss.visit_type = 'IPD' AND (sss.bill = imr_tb_debtor_rights_ipd.bill OR (imr_tb_debtor_rights_ipd.mobile LIKE CONCAT(sss.bill, '%'))))";

    if ($receiptNo !== '') {
        $whereOPD[] = "(bill LIKE '%$receiptNo%' OR mobile LIKE '%$receiptNo%')";
        $whereIPD[] = "(bill LIKE '%$receiptNo%' OR mobile LIKE '%$receiptNo%')";
        $whereCR[]  = "bill LIKE '%$receiptNo%'";
        $whereSSS[] = "bill LIKE '%$receiptNo%'";
    }

    if ($startDate !== '' && $endDate !== '') {
        $whereOPD[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') BETWEEN '$startDate' AND '$endDate'";
        $whereIPD[] = "STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') BETWEEN '$startDate' AND '$endDate'";
        $whereCR[]  = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') BETWEEN '$startDate' AND '$endDate'";
        $whereSSS[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') BETWEEN '$startDate' AND '$endDate'";
    } elseif ($startDate !== '') {
        $whereOPD[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') >= '$startDate'";
        $whereIPD[] = "STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') >= '$startDate'";
        $whereCR[]  = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') >= '$startDate'";
        $whereSSS[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') >= '$startDate'";
    } elseif ($endDate !== '') {
        $whereOPD[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') <= '$endDate'";
        $whereIPD[] = "STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') <= '$endDate'";
        $whereCR[]  = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') <= '$endDate'";
        $whereSSS[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') <= '$endDate'";
    }

    $whereOPD[] = "IFNULL(debit,0) > 0";
    $whereIPD[] = "IFNULL(debit,0) > 0";
    $whereCR[]  = "IFNULL(item_amount,0) > 0";
    $whereSSS[] = "IFNULL(item_amount,0) > 0";

    $condOPD = implode(' AND ', $whereOPD);
    $condIPD = implode(' AND ', $whereIPD);
    $condCR  = implode(' AND ', $whereCR);
    $condSSS = implode(' AND ', $whereSSS);

    $baseQuery = "
    SELECT * FROM (
        SELECT 
            'OPD' AS type,
            vn,
            vstdate AS srv_date,
            STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') AS sort_date,
            hn,
            ptname,
            accountname,
            CASE 
                WHEN bill IS NOT NULL AND bill != '' AND bill != '-' THEN bill 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND mobile LIKE '%-%' THEN SUBSTRING_INDEX(mobile, '-', 1)
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN mobile
                ELSE '-' 
            END AS receipt_no,
            CASE 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND (mobile LIKE '%/%' OR mobile LIKE '%-%') THEN 'STM (bill)'
                WHEN bill IS NOT NULL AND bill != '' AND bill != '-' THEN 'ตัดรายตัว (bill)'
                ELSE '-' 
            END AS receipt_type,
            CASE 
                WHEN billdate IS NOT NULL AND billdate != '' AND billdate != '-' THEN billdate 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN SUBSTRING_INDEX(mobile, '-', -1)
                ELSE '-' 
            END AS receipt_date,
            IFNULL(debit,0) AS debit_amount,
            CASE
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND (mobile LIKE '%/%' OR mobile LIKE '%-%') THEN IFNULL((
                    SELECT SUM(compensated) FROM imr_tb_check_invoice 
                    WHERE vn = imr_tb_debtor_rights_opd.vn
                ), 0) + IFNULL((
                    SELECT SUM(compensated) FROM imr_tb_seamless_dckd 
                    WHERE vn = imr_tb_debtor_rights_opd.vn
                ), 0) + IFNULL((
                    SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc 
                    WHERE vn = imr_tb_debtor_rights_opd.vn
                ), 0)
                WHEN bill IS NOT NULL AND bill != '' AND bill != '-' THEN IFNULL(follow_money, 0)
                ELSE 0
            END AS compensate_amount,
            IFNULL(rcpt_money, 0) AS paid_amount,
            CASE 
                WHEN accountcode IN ('1102050101.201', '1102050101.209') THEN 0
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND (mobile LIKE '%/%' OR mobile LIKE '%-%') THEN (IFNULL(debit,0) - IFNULL(follow_money, 0))
                WHEN bill IS NOT NULL AND bill != '' AND bill != '-' THEN (IFNULL(debit, 0) - IFNULL(rcpt_money, 0))
                ELSE IFNULL(debit, 0)
            END AS balance_amount
        FROM imr_tb_debtor_rights_opd
        WHERE $condOPD

        UNION ALL

        SELECT 
            'IPD' AS type,
            an AS vn,
            dchdate AS srv_date,
            STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') AS sort_date,
            hn,
            ptname,
            accountname,
            CASE 
                WHEN bill IS NOT NULL AND bill != '' AND bill != '-' THEN bill 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND mobile LIKE '%-%' THEN SUBSTRING_INDEX(mobile, '-', 1)
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN mobile
                ELSE '-' 
            END AS receipt_no,
            CASE 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND (mobile LIKE '%/%' OR mobile LIKE '%-%') THEN 'STM (bill)'
                WHEN bill IS NOT NULL AND bill != '' AND bill != '-' THEN 'ตัดรายตัว (bill)'
                ELSE '-' 
            END AS receipt_type,
            CASE 
                WHEN billdate IS NOT NULL AND billdate != '' AND billdate != '-' THEN billdate 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN SUBSTRING_INDEX(mobile, '-', -1)
                ELSE '-' 
            END AS receipt_date,
            IFNULL(debit,0) AS debit_amount,
            CASE
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND (mobile LIKE '%/%' OR mobile LIKE '%-%') THEN IFNULL((
                    SELECT SUM(compensated) FROM imr_tb_check_invoice 
                    WHERE vn = imr_tb_debtor_rights_ipd.an
                ), 0) + IFNULL((
                    SELECT SUM(compensated) FROM imr_tb_seamless_dckd 
                    WHERE vn = imr_tb_debtor_rights_ipd.an
                ), 0) + IFNULL((
                    SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc 
                    WHERE vn = imr_tb_debtor_rights_ipd.an
                ), 0)
                WHEN bill IS NOT NULL AND bill != '' AND bill != '-' THEN IFNULL(follow_money, 0)
                ELSE 0
            END AS compensate_amount,
            IFNULL(rcpt_money, 0) AS paid_amount,
            CASE 
                WHEN accountcode IN ('1102050101.201', '1102050101.209') THEN 0
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND (mobile LIKE '%/%' OR mobile LIKE '%-%') THEN (IFNULL(debit,0) - IFNULL(follow_money, 0))
                WHEN bill IS NOT NULL AND bill != '' AND bill != '-' THEN (IFNULL(debit, 0) - IFNULL(rcpt_money, 0))
                ELSE IFNULL(debit, 0)
            END AS balance_amount
        FROM imr_tb_debtor_rights_ipd
        WHERE $condIPD

        UNION ALL

        SELECT 
            visit_type AS type,
            vn,
            vstdate AS srv_date,
            STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') AS sort_date,
            hn,
            ptname,
            CONCAT('ลูกหนี้ค่ารักษา UC - ', visit_type, ' บริการเฉพาะ (CR) [', cr_type, ']') AS accountname,
            bill AS receipt_no,
            'STM (bill)' AS receipt_type,
            billdate AS receipt_date,
            IFNULL(item_amount, 0) AS debit_amount,
            IFNULL(compensated, 0) AS compensate_amount,
            0 AS paid_amount,
            IFNULL(item_amount, 0) AS balance_amount
        FROM imr_tb_debtor_cr_breakdown
        WHERE $condCR

        UNION ALL

        SELECT 
            visit_type AS type,
            vn,
            vstdate AS srv_date,
            STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') AS sort_date,
            hn,
            ptname,
            CONCAT('ลูกหนี้ค่ารักษาประกันสังคม - ', visit_type, ' [', sss_type, ']') AS accountname,
            bill AS receipt_no,
            'STM (bill)' AS receipt_type,
            billdate AS receipt_date,
            IFNULL(item_amount, 0) AS debit_amount,
            IFNULL(compensated, 0) AS compensate_amount,
            0 AS paid_amount,
            IFNULL(item_amount, 0) AS balance_amount
        FROM imr_tb_debtor_sss_breakdown
        WHERE $condSSS
    ) AS all_receipts
    ";

    if ($action === 'summary') {
        $sql = "
            SELECT receipt_no, receipt_type, MAX(receipt_date) AS receipt_date, COUNT(*) AS item_count, 
                   SUM(debit_amount) AS sum_debit, 
                   SUM(paid_amount) AS sum_paid, 
                   SUM(compensate_amount) AS sum_compensate, 
                   SUM(balance_amount) AS sum_balance,
                   SUM(compensate_amount - balance_amount) AS sum_diff,
                   MAX(sort_date) AS max_sort_date
            FROM ($baseQuery) AS base
            GROUP BY receipt_no, receipt_type
            ORDER BY STR_TO_DATE(MAX(receipt_date), '%d/%m/%Y') DESC, max_sort_date DESC, receipt_no DESC
        ";
    } else {
        $typeCondition = $receiptTypeDetail !== '' ? "AND receipt_type = '$receiptTypeDetail'" : "";
        $sql = "
            SELECT *, (compensate_amount - balance_amount) AS diff_amount FROM ($baseQuery) AS base
            WHERE receipt_no = '$receiptNo' $typeCondition
            ORDER BY sort_date DESC
        ";
    }

    $result = mysqli_query($conn, $sql);
    $data = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        // ดึงเลขที่หนังสือ REP Statement แบบชุด (High-Performance Batch Lookup) สำหรับหน้ารายละเอียด
        if ($action !== 'summary' && !empty($data)) {
            $repMap = [];

            // 1. ดึง REP จากเลขที่ใบเสร็จตรงๆ ก่อน (เร็วมากระดับเสี้ยววินาที)
            if (!empty($receiptNo) && $receiptNo !== '-') {
                $qRep = mysqli_query($conn, "SELECT vn, rep FROM imr_tb_check_invoice WHERE bill = '$receiptNo' AND rep IS NOT NULL AND TRIM(rep) != ''");
                if ($qRep) {
                    while ($r = mysqli_fetch_assoc($qRep)) {
                        if (!isset($repMap[$r['vn']])) $repMap[$r['vn']] = trim($r['rep']);
                    }
                }
                $qDckd = mysqli_query($conn, "SELECT vn, rep FROM imr_tb_seamless_dckd WHERE bill = '$receiptNo' AND rep IS NOT NULL AND TRIM(rep) != ''");
                if ($qDckd) {
                    while ($r = mysqli_fetch_assoc($qDckd)) {
                        if (!isset($repMap[$r['vn']])) $repMap[$r['vn']] = trim($r['rep']);
                    }
                }
            }

            // 2. Fallback สำหรับ VN ที่ยังไม่พบ REP (ค้นด้วย VN เป็นชุดละ 1,000 รายการ)
            $missingVns = [];
            foreach ($data as $item) {
                $v = $item['vn'] ?? '';
                if (!empty($v) && !isset($repMap[$v])) {
                    $missingVns[] = "'" . mysqli_real_escape_string($conn, $v) . "'";
                }
            }

            if (!empty($missingVns)) {
                foreach (array_chunk($missingVns, 1000) as $chunk) {
                    $inClause = implode(',', $chunk);
                    $qM = mysqli_query($conn, "SELECT vn, rep FROM imr_tb_check_invoice WHERE vn IN ($inClause) AND rep IS NOT NULL AND TRIM(rep) != ''");
                    if ($qM) {
                        while ($r = mysqli_fetch_assoc($qM)) {
                            if (!isset($repMap[$r['vn']])) $repMap[$r['vn']] = trim($r['rep']);
                        }
                    }
                    $qD = mysqli_query($conn, "SELECT vn, rep FROM imr_tb_seamless_dckd WHERE vn IN ($inClause) AND rep IS NOT NULL AND TRIM(rep) != ''");
                    if ($qD) {
                        while ($r = mysqli_fetch_assoc($qD)) {
                            if (!isset($repMap[$r['vn']])) $repMap[$r['vn']] = trim($r['rep']);
                        }
                    }
                    $qO = mysqli_query($conn, "SELECT vn, stm_doc FROM imr_tb_seamless_dckd_ofc WHERE vn IN ($inClause) AND stm_doc IS NOT NULL AND TRIM(stm_doc) != ''");
                    if ($qO) {
                        while ($r = mysqli_fetch_assoc($qO)) {
                            if (!isset($repMap[$r['vn']])) $repMap[$r['vn']] = trim($r['stm_doc']);
                        }
                    }
                }
            }

            // กำหนดค่า rep_no ให้กับทุกรายการ
            foreach ($data as &$row) {
                $v = $row['vn'] ?? '';
                $row['rep_no'] = isset($repMap[$v]) ? $repMap[$v] : '-';
            }
            unset($row);
        }

        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>