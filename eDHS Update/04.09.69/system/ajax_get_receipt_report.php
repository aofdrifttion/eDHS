<?php
require './database_config/config.php';

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

    // Filter by receipt type
    if ($receiptType === 'bill') {
        $hasReceiptCond = "(bill IS NOT NULL AND bill != '')";
    } elseif ($receiptType === 'mobile') {
        $hasReceiptCond = "((bill IS NULL OR bill = '') AND (mobile IS NOT NULL AND mobile != '' AND mobile != '-'))";
    } else {
        $hasReceiptCond = "((bill IS NOT NULL AND bill != '') OR (mobile IS NOT NULL AND mobile != '' AND mobile != '-'))";
    }
    $whereOPD[] = $hasReceiptCond;
    $whereIPD[] = $hasReceiptCond;

    if ($action === 'summary' && $receiptNo !== '') {
        $whereOPD[] = "(bill LIKE '%$receiptNo%' OR mobile LIKE '%$receiptNo%')";
        $whereIPD[] = "(bill LIKE '%$receiptNo%' OR mobile LIKE '%$receiptNo%')";
    }

    if ($startDate !== '' && $endDate !== '') {
        $whereOPD[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') BETWEEN '$startDate' AND '$endDate'";
        $whereIPD[] = "STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') BETWEEN '$startDate' AND '$endDate'";
    } elseif ($startDate !== '') {
        $whereOPD[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') >= '$startDate'";
        $whereIPD[] = "STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') >= '$startDate'";
    } elseif ($endDate !== '') {
        $whereOPD[] = "STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') <= '$endDate'";
        $whereIPD[] = "STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') <= '$endDate'";
    }

    $whereOPD[] = "IFNULL(debit,0) > 0";
    $whereIPD[] = "IFNULL(debit,0) > 0";

    $condOPD = implode(' AND ', $whereOPD);
    $condIPD = implode(' AND ', $whereIPD);

    $baseQuery = "
    SELECT * FROM (
        SELECT 
            'OPD' AS type,
            vstdate AS srv_date,
            STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') AS sort_date,
            hn,
            ptname,
            accountname,
            CASE 
                WHEN bill IS NOT NULL AND bill != '' THEN bill 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN mobile
                ELSE '-' 
            END AS receipt_no,
            CASE 
                WHEN bill IS NOT NULL AND bill != '' THEN 'ตัดรายตัว (bill)'
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN 'STM (bill)'
                ELSE '-' 
            END AS receipt_type,
            CASE 
                WHEN billdate IS NOT NULL AND billdate != '' THEN billdate 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN SUBSTRING_INDEX(mobile, '-', -1)
                ELSE '-' 
            END AS receipt_date,
            IFNULL(debit,0) AS debit_amount,
            CASE
                WHEN bill IS NOT NULL AND bill != '' THEN IFNULL(follow_money, 0)
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN IFNULL((
                    SELECT SUM(compensated) FROM imr_tb_check_invoice 
                    WHERE vn = imr_tb_debtor_rights_opd.vn
                ), 0) + IFNULL((
                    SELECT SUM(compensated) FROM imr_tb_seamless_dckd 
                    WHERE vn = imr_tb_debtor_rights_opd.vn
                ), 0) + IFNULL((
                    SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc 
                    WHERE vn = imr_tb_debtor_rights_opd.vn
                ), 0)
                ELSE 0
            END AS compensate_amount,
            IFNULL(rcpt_money, 0) AS paid_amount,
            CASE 
                WHEN accountcode IN ('1102050101.201', '1102050101.209') THEN 0
                WHEN bill IS NOT NULL AND bill != '' THEN (IFNULL(debit, 0) - IFNULL(rcpt_money, 0))
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN (IFNULL(debit,0) - IFNULL(follow_money, 0))
                ELSE IFNULL(debit, 0)
            END AS balance_amount
        FROM imr_tb_debtor_rights_opd
        WHERE $condOPD

        UNION ALL

        SELECT 
            'IPD' AS type,
            dchdate AS srv_date,
            STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543), '%d/%m/%Y') AS sort_date,
            hn,
            ptname,
            accountname,
            CASE 
                WHEN bill IS NOT NULL AND bill != '' THEN bill 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN mobile
                ELSE '-' 
            END AS receipt_no,
            CASE 
                WHEN bill IS NOT NULL AND bill != '' THEN 'ตัดรายตัว (bill)'
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN 'STM (bill)'
                ELSE '-' 
            END AS receipt_type,
            CASE 
                WHEN billdate IS NOT NULL AND billdate != '' THEN billdate 
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN SUBSTRING_INDEX(mobile, '-', -1)
                ELSE '-' 
            END AS receipt_date,
            IFNULL(debit,0) AS debit_amount,
            CASE
                WHEN bill IS NOT NULL AND bill != '' THEN IFNULL(follow_money, 0)
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN IFNULL((
                    SELECT SUM(compensated) FROM imr_tb_check_invoice 
                    WHERE vn = imr_tb_debtor_rights_ipd.an
                ), 0) + IFNULL((
                    SELECT SUM(compensated) FROM imr_tb_seamless_dckd 
                    WHERE vn = imr_tb_debtor_rights_ipd.an
                ), 0) + IFNULL((
                    SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc 
                    WHERE vn = imr_tb_debtor_rights_ipd.an
                ), 0)
                ELSE 0
            END AS compensate_amount,
            IFNULL(rcpt_money, 0) AS paid_amount,
            CASE 
                WHEN accountcode IN ('1102050101.201', '1102050101.209') THEN 0
                WHEN bill IS NOT NULL AND bill != '' THEN (IFNULL(debit, 0) - IFNULL(rcpt_money, 0))
                WHEN mobile IS NOT NULL AND mobile != '' AND mobile != '-' THEN (IFNULL(debit,0) - IFNULL(follow_money, 0))
                ELSE IFNULL(debit, 0)
            END AS balance_amount
        FROM imr_tb_debtor_rights_ipd
        WHERE $condIPD
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
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
