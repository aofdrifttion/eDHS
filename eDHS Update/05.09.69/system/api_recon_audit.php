<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - Reconciliation Audit API Service
 * ====================================================================================
 * API สำหรับตรวจสอบและวินิจฉัยความสอดคล้องของการยันยอดบัญชี (Reconciliation Audit)
 * ระหว่างข้อมูลทะเบียนคุมลูกหนี้ (Col 12 - งบทดลอง/บัญชีแยกประเภท) 
 * กับ ยอดลูกหนี้รายตัวจริงในระบบ (debtor_excel.php - ฐานข้อมูลผู้ป่วย OPD/IPD)
 * 
 * @author eDHS Engineering
 * @version 1.0.0 (2026-09-05)
 * ====================================================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_config/config.php';

if (!isset($conn) || !$conn) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล eDHS ได้'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
$month = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : '';

if (empty($month)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'กรุณาระบุเดือนที่ต้องการตรวจสอบ (เช่น 7-2026)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// แยกเดือนและปี
$parts = explode('-', $month);
if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'รูปแบบเดือนไม่ถูกต้อง (ต้องเป็น m-Y เช่น 7-2026)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$month_num = intval($parts[0]);
$year_num = intval($parts[1]);
$target_ym = sprintf('%s-%02d', $year_num, $month_num);

// คำนวณเดือนก่อนหน้า ($monthlast)
$m_val = $month_num - 1;
$y_val = $year_num;
if ($m_val == 0) {
    $m_val = 12;
    $y_val--;
}
$monthlast = $m_val . '-' . $y_val;
$monthlast_ym = sprintf('%s-%02d', $y_val, $m_val);

/**
 * เงื่อนไขการตัดรับชำระสำหรับ debtor_excel ตาม logic ระบบ
 */
function getReceiptConditionSql($target_ym_param) {
    return "
        CASE
            WHEN billdate IS NOT NULL AND billdate != ''
             AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
            THEN CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) > '$target_ym_param'

            WHEN mobile IS NOT NULL AND mobile != ''
             AND mobile != '-' AND CHAR_LENGTH(mobile) >= 10
            THEN CONCAT(
                   (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                   '-',
                   SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                 ) > '$target_ym_param'

            ELSE TRUE
        END
    ";
}

/**
 * คำนวณยอด Excel รวมของทุกผังบัญชีด้วย Batch Query ความเร็วสูง (Group by accountcode)
 */
function getBatchExcelTotals($conn, $target_ym_param) {
    $receiptWhere = getReceiptConditionSql($target_ym_param);

    $sql = "
    SELECT accountcode, 
           SUM(debit) AS total_excel_debit, 
           COUNT(*) AS count_excel
    FROM (
        /* OPD */
        SELECT accountcode, billdate, mobile,
                (IFNULL(debit, 0) - 
                    CASE
                        WHEN billdate IS NOT NULL AND billdate != '' 
                         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                         AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym_param'
                        THEN IFNULL(follow_money, 0)

                        WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                         AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                         AND CHAR_LENGTH(mobile) >= 10
                         AND CONCAT(
                                 (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                                 '-',
                                 SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                             ) <= '$target_ym_param'
                        THEN IFNULL(follow_money, 0)

                        ELSE 0
                    END
                    - IFNULL((
                        SELECT SUM(ph.pay_amount)
                        FROM imr_tb_payment_history ph
                        WHERE ph.ref_vn_an = vn
                          AND ph.patient_type = 'OPD'
                          AND ph.bill_date IS NOT NULL
                          AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym_param'
                      ), 0)
                ) AS debit
        FROM imr_tb_debtor_rights_opd
        WHERE IFNULL(debit, 0) > 0
          AND CONCAT(
                SUBSTRING_INDEX(monthtxt,'-',-1),'-',
                LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
              ) <= '$target_ym_param'

        UNION ALL

        /* IPD */
        SELECT accountcode, billdate, mobile,
                (IFNULL(debit, 0) - 
                    CASE
                        WHEN billdate IS NOT NULL AND billdate != '' 
                         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                         AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym_param'
                        THEN IFNULL(follow_money, 0)

                        WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                         AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                         AND CHAR_LENGTH(mobile) >= 10
                         AND CONCAT(
                                 (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                                 '-',
                                 SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                             ) <= '$target_ym_param'
                        THEN IFNULL(follow_money, 0)

                        ELSE 0
                    END
                    - IFNULL((
                        SELECT SUM(ph.pay_amount)
                        FROM imr_tb_payment_history ph
                        WHERE ph.ref_vn_an = an
                          AND ph.patient_type = 'IPD'
                          AND ph.bill_date IS NOT NULL
                          AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym_param'
                      ), 0)
                ) AS debit
        FROM imr_tb_debtor_rights_ipd
        WHERE IFNULL(debit, 0) > 0
          AND CONCAT(
                SUBSTRING_INDEX(monthtxt,'-',-1),'-',
                LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
              ) <= '$target_ym_param'
    ) AS all_debtors
    WHERE debit > 0 AND ($receiptWhere)
    GROUP BY accountcode
    ";

    $result = mysqli_query($conn, $sql);
    $excel_data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $excel_data[$row['accountcode']] = [
                'total_debit' => floatval($row['total_excel_debit']),
                'count' => intval($row['count_excel'])
            ];
        }
    }
    return $excel_data;
}

// ------------------------------------------------------------------------------------
// ACTION 1: สรุปภาพรวมการตรวจสอบยันยอดทั้งเดือน (Summary KPI & Account List)
// ------------------------------------------------------------------------------------
if ($action === 'get_month_audit_summary') {
    $batch_excel = getBatchExcelTotals($conn, $target_ym);

    // ดึงข้อมูลตารางทะเบียนคุมของเดือนที่ระบุ (ตรงตามลอจิก ทะเบียนคุมลูกหนี้1.php)
    $sql_table = "
    SELECT 
        main_query.accountcode,
        tb_code.Name AS accountname,
        SUM(main_query.total_debit) AS total_debit,
        imr_tb_debtor_result.column6,
        imr_tb_debtor_result.column7,
        imr_tb_debtor_result.column8,
        imr_tb_debtor_result.column9,
        imr_tb_debtor_result.column10,
        imr_tb_debtor_result.column11,
        imr_tb_debtor_result.column12,
        column10_last_query.column10last AS column10last
    FROM (
        SELECT accountcode, SUM(IF(monthtxt = '$month', debit, 0)) AS total_debit, '$month' AS monthtxt
        FROM imr_tb_debtor_rights_opd GROUP BY accountcode
        UNION ALL
        SELECT accountcode, SUM(IF(monthtxt = '$month', debit, 0)) AS total_debit, '$month' AS monthtxt
        FROM imr_tb_debtor_rights_ipd GROUP BY accountcode
    ) AS main_query
    LEFT OUTER JOIN tb_code ON main_query.accountcode = tb_code.Code
    LEFT OUTER JOIN imr_tb_debtor_result ON main_query.accountcode = imr_tb_debtor_result.code AND imr_tb_debtor_result.month = main_query.monthtxt
    LEFT OUTER JOIN (
        SELECT code, column12 AS column10last FROM imr_tb_debtor_result WHERE month = '$monthlast'
    ) AS column10_last_query ON main_query.accountcode = column10_last_query.code
    GROUP BY main_query.accountcode, tb_code.Name, imr_tb_debtor_result.column6, imr_tb_debtor_result.column7, 
             imr_tb_debtor_result.column8, imr_tb_debtor_result.column9, imr_tb_debtor_result.column10, 
             imr_tb_debtor_result.column11, imr_tb_debtor_result.column12, column10_last_query.column10last
    ORDER BY main_query.accountcode ASC
    ";

    $result_tbl = mysqli_query($conn, $sql_table);

    // คำนวณวันที่ปิดงบการเงิน (วันที่ 10 ของเดือนถัดไป)
    $cutoff_date = date('Y-m-d', strtotime("$year_num-$month_num-01 +1 month +9 days"));
    $today = date('Y-m-d');
    $is_closed = ($today > $cutoff_date);
    $strMonthCut = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
    $cutoff_ts = strtotime($cutoff_date);
    $cutoff_th_day = date('j', $cutoff_ts);
    $cutoff_th_month = $strMonthCut[intval(date('n', $cutoff_ts))];
    $cutoff_th_year = date('Y', $cutoff_ts) + 543;
    $cutoff_date_thai = "$cutoff_th_day $cutoff_th_month $cutoff_th_year";
    $cutoff_date_short = date('d/m/', $cutoff_ts) . $cutoff_th_year;

    $accounts = [];
    $summary = [
        'total_accounts' => 0,
        'matched_count' => 0,
        'mismatched_count' => 0,
        'pending_count' => 0,
        'inactive_count' => 0,
        'total_table_col12' => 0.0,
        'total_excel' => 0.0,
        'total_variance_abs' => 0.0,
        'closing_info' => [
            'cutoff_date' => $cutoff_date,
            'cutoff_date_thai' => $cutoff_date_thai,
            'cutoff_date_short' => $cutoff_date_short,
            'is_closed' => $is_closed,
            'status_text' => $is_closed ? 'ปิดรอบงบการเงินแล้ว' : 'เปิดบันทึก/แก้ไขงบ',
            'badge_class' => $is_closed ? 'bg-label-secondary' : 'bg-label-success',
            'badge_icon' => $is_closed ? 'bx-lock-alt' : 'bx-edit'
        ]
    ];

    if ($result_tbl) {
        while ($r = mysqli_fetch_assoc($result_tbl)) {
            $code = $r['accountcode'];
            $name = $r['accountname'] ?? 'ไม่ระบุชื่อผัง';
            $debit_opd = floatval($r['total_debit'] ?? 0);
            $raw_col6 = $r['column6'];
            $col6 = !empty($raw_col6) ? floatval(str_replace(',', '', $raw_col6)) : 0.0;
            $raw_col10 = $r['column10'];
            $col10 = !empty($raw_col10) ? floatval(str_replace(',', '', $raw_col10)) : 0.0;
            $raw_col12 = trim($r['column12'] ?? '');
            $col10last_num = floatval(str_replace(',', '', $r['column10last'] ?? 0));

            $excel_info = $batch_excel[$code] ?? ['total_debit' => 0.0, 'count' => 0];
            $excel_total = $excel_info['total_debit'];
            $excel_count = $excel_info['count'];

            $is_numeric_col12 = (!empty($raw_col12) && is_numeric(str_replace(',', '', $raw_col12)));
            $col12 = $is_numeric_col12 ? floatval(str_replace(',', '', $raw_col12)) : null;

            // วินิจฉัยสถานะ
            $status = 'MATCH';
            $status_label = 'ตรงกัน 100%';
            $pending_reason = '';
            $diff = 0.0;
            $is_carried_static = false;

            // ตรวจสอบกรณีผังบัญชีที่ "มีแค่ยอดยกมาจากเดือนก่อน ไม่มีการตั้งหนี้ใหม่ และไม่มียอดชดเชยในเดือนนี้"
            $is_no_activity = ($debit_opd == 0.0 && $col6 == 0.0 && $col10 == 0.0);

            if ($debit_opd > 0 && $col6 == 0.0) {
                $status = 'PENDING';
                $pending_reason = 'รอลงงบทดลองบัญชี';
                $status_label = 'รอลงงบทดลองบัญชี';
            } elseif ($raw_col12 === 'รอยืนยันจากบัญชี') {
                $status = 'PENDING';
                $pending_reason = 'รอยืนยันบัญชี';
                $status_label = 'รอยืนยันบัญชี';
            } elseif ($col12 === null) {
                if ($excel_total == 0.0 && $debit_opd == 0.0 && $col10last_num == 0.0) {
                    $status = 'INACTIVE';
                    $status_label = 'ไม่มียอดเคลื่อนไหว';
                } else {
                    $status = 'PENDING';
                    $pending_reason = 'ยังไม่ลงข้อมูลทะเบียนคุม';
                    $status_label = 'ยังไม่ลงข้อมูลทะเบียนคุม';
                }
            } else {
                $diff = round($excel_total - $col12, 2);
                if (abs($diff) < 0.01) {
                    if ($col12 == 0.0 && $excel_total == 0.0 && $debit_opd == 0.0 && $col10last_num == 0.0) {
                        $status = 'INACTIVE';
                        $status_label = 'ไม่มียอดเคลื่อนไหว';
                    } elseif ($is_no_activity && $col10last_num > 0) {
                        // ✨ ผังที่มีแค่ยอดยกมาจากหลายเดือนก่อน ไม่มีการตั้งหนี้ใหม่ ไม่มียอดชดเชย (เช่น 1102050101.303)
                        $status = 'MATCH';
                        $is_carried_static = true;
                        $status_label = 'ยอดยกมาคงเดิม (ตรงกัน 100%)';
                    } else {
                        $status = 'MATCH';
                        $status_label = 'ตรงกัน 100%';
                    }
                } else {
                    $status = 'MISMATCH';
                    if ($is_no_activity && $col10last_num > 0) {
                        $status_label = 'ยอดยกมามียอดต่าง ฿' . number_format(abs($diff), 2);
                    } else {
                        $status_label = 'มียอดต่าง ฿' . number_format(abs($diff), 2);
                    }
                }
            }

            // คำนวณ Summary
            $summary['total_accounts']++;
            if ($status === 'MATCH') {
                $summary['matched_count']++;
                $summary['total_table_col12'] += ($col12 ?? 0);
                $summary['total_excel'] += $excel_total;
            } elseif ($status === 'MISMATCH') {
                $summary['mismatched_count']++;
                $summary['total_table_col12'] += ($col12 ?? 0);
                $summary['total_excel'] += $excel_total;
                $summary['total_variance_abs'] += abs($diff);
            } elseif ($status === 'PENDING') {
                $summary['pending_count']++;
                $summary['total_excel'] += $excel_total;
                if ($col12 !== null) $summary['total_table_col12'] += $col12;
            } elseif ($status === 'INACTIVE') {
                $summary['inactive_count']++;
            }

            $accounts[] = [
                'accountcode' => $code,
                'accountname' => $name,
                'total_debit_insurance' => $debit_opd,
                'total_debit_fmt' => number_format($debit_opd, 2),
                'col6' => $col6,
                'col6_fmt' => number_format($col6, 2),
                'col10' => $col10,
                'col10_fmt' => number_format($col10, 2),
                'raw_col12' => $raw_col12,
                'col12' => $col12,
                'col12_fmt' => ($col12 !== null) ? number_format($col12, 2) : ($raw_col12 ?: '-'),
                'excel_total' => $excel_total,
                'excel_total_fmt' => number_format($excel_total, 2),
                'excel_count' => $excel_count,
                'diff' => $diff,
                'diff_fmt' => number_format($diff, 2),
                'status' => $status,
                'status_label' => $status_label,
                'is_carried_static' => $is_carried_static,
                'pending_reason' => $pending_reason
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'month' => $month,
        'summary' => $summary,
        'accounts' => $accounts
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------------------------------
// ACTION 2: วินิจฉัยเชิงลึกรายผังบัญชี 3 มิติ (3-Dimension Variance Diagnostics)
// ------------------------------------------------------------------------------------
if ($action === 'get_account_audit_detail') {
    $accountcode = isset($_REQUEST['accountcode']) ? trim($_REQUEST['accountcode']) : '';
    if (empty($accountcode)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุรหัสผังบัญชี'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 1. ข้อมูลผังบัญชี
    $acc_stmt = mysqli_prepare($conn, "SELECT Name FROM tb_code WHERE Code = ? LIMIT 1");
    mysqli_stmt_bind_param($acc_stmt, 's', $accountcode);
    mysqli_stmt_execute($acc_stmt);
    $acc_res = mysqli_stmt_get_result($acc_stmt);
    $acc_row = mysqli_fetch_assoc($acc_res);
    $accountname = $acc_row['Name'] ?? 'ไม่ระบุชื่อผัง';

    // 2. ข้อมูลจากตารางทะเบียนคุมเดือนนี้ และเดือนก่อน
    $sql_row = "
    SELECT 
        imr_tb_debtor_result.*,
        prev.column12 AS col10last,
        IFNULL(opd_curr.total_debit, 0) + IFNULL(ipd_curr.total_debit, 0) AS total_debit_insurance
    FROM (SELECT '$accountcode' AS code) c
    LEFT JOIN imr_tb_debtor_result ON c.code = imr_tb_debtor_result.code AND imr_tb_debtor_result.month = '$month'
    LEFT JOIN imr_tb_debtor_result prev ON c.code = prev.code AND prev.month = '$monthlast'
    LEFT JOIN (
        SELECT accountcode, SUM(debit) AS total_debit FROM imr_tb_debtor_rights_opd WHERE monthtxt = '$month' GROUP BY accountcode
    ) opd_curr ON c.code = opd_curr.accountcode
    LEFT JOIN (
        SELECT accountcode, SUM(debit) AS total_debit FROM imr_tb_debtor_rights_ipd WHERE monthtxt = '$month' GROUP BY accountcode
    ) ipd_curr ON c.code = ipd_curr.accountcode
    ";
    $q_row = mysqli_query($conn, $sql_row);
    $row_data = mysqli_fetch_assoc($q_row) ?: [];

    $col3_num = floatval(str_replace(',', '', $row_data['col10last'] ?? 0));
    $col5_num = floatval($row_data['total_debit_insurance'] ?? 0);
    $raw_col6 = $row_data['column6'] ?? '';
    $col6_num = !empty($raw_col6) ? floatval(str_replace(',', '', $raw_col6)) : 0.0;
    $raw_col10 = $row_data['column10'] ?? '';
    $col10_num = !empty($raw_col10) ? floatval(str_replace(',', '', $raw_col10)) : 0.0;
    $raw_col12 = trim($row_data['column12'] ?? '');
    $col12_num = (!empty($raw_col12) && is_numeric(str_replace(',', '', $raw_col12))) ? floatval(str_replace(',', '', $raw_col12)) : null;

    // 3. ยอด Excel จริงของเดือนนี้
    $receiptWhere = getReceiptConditionSql($target_ym);
    $sql_ex = "
    SELECT SUM(debit) as total_excel_debit, COUNT(*) as count_excel
    FROM (
        SELECT vn, billdate, mobile,
                (IFNULL(debit, 0) - 
                    CASE
                        WHEN billdate IS NOT NULL AND billdate != '' 
                         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                         AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
                        THEN IFNULL(follow_money, 0)

                        WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                         AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                         AND CHAR_LENGTH(mobile) >= 10
                         AND CONCAT(
                                 (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                                 '-',
                                 SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                             ) <= '$target_ym'
                        THEN IFNULL(follow_money, 0)

                        ELSE 0
                    END
                    - IFNULL((
                        SELECT SUM(ph.pay_amount)
                        FROM imr_tb_payment_history ph
                        WHERE ph.ref_vn_an = vn
                          AND ph.patient_type = 'OPD'
                          AND ph.bill_date IS NOT NULL
                          AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
                      ), 0)
                ) AS debit
        FROM imr_tb_debtor_rights_opd
        WHERE accountcode = '$accountcode' AND IFNULL(debit, 0) > 0
          AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$target_ym'

        UNION ALL

        SELECT an AS vn, billdate, mobile,
                (IFNULL(debit, 0) - 
                    CASE
                        WHEN billdate IS NOT NULL AND billdate != '' 
                         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                         AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
                        THEN IFNULL(follow_money, 0)

                        WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                         AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                         AND CHAR_LENGTH(mobile) >= 10
                         AND CONCAT(
                                 (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                                 '-',
                                 SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                             ) <= '$target_ym'
                        THEN IFNULL(follow_money, 0)

                        ELSE 0
                    END
                    - IFNULL((
                        SELECT SUM(ph.pay_amount)
                        FROM imr_tb_payment_history ph
                        WHERE ph.ref_vn_an = an
                          AND ph.patient_type = 'IPD'
                          AND ph.bill_date IS NOT NULL
                          AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
                      ), 0)
                ) AS debit
        FROM imr_tb_debtor_rights_ipd
        WHERE accountcode = '$accountcode' AND IFNULL(debit, 0) > 0
          AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$target_ym'
    ) AS all_debtors
    WHERE debit > 0 AND ($receiptWhere)
    ";
    $q_ex = mysqli_query($conn, $sql_ex);
    $row_ex = mysqli_fetch_assoc($q_ex);
    $excel_total = floatval($row_ex['total_excel_debit'] ?? 0);
    $excel_count = intval($row_ex['count_excel'] ?? 0);

    // 4. ยอด Excel เดือนก่อน ($monthlast_ym) สำหรับตรวจมิติที่ 3
    $receiptWhereLast = getReceiptConditionSql($monthlast_ym);
    $sql_ex_last = "
    SELECT SUM(debit) as total_excel_debit, COUNT(*) as count_excel
    FROM (
        SELECT vn, billdate, mobile, (IFNULL(debit, 0) - IFNULL(follow_money, 0)) AS debit
        FROM imr_tb_debtor_rights_opd
        WHERE accountcode = '$accountcode' AND IFNULL(debit, 0) > 0
          AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$monthlast_ym'
        UNION ALL
        SELECT an AS vn, billdate, mobile, (IFNULL(debit, 0) - IFNULL(follow_money, 0)) AS debit
        FROM imr_tb_debtor_rights_ipd
        WHERE accountcode = '$accountcode' AND IFNULL(debit, 0) > 0
          AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$monthlast_ym'
    ) AS all_last
    WHERE debit > 0 AND ($receiptWhereLast)
    ";
    $q_ex_last = mysqli_query($conn, $sql_ex_last);
    $row_ex_last = mysqli_fetch_assoc($q_ex_last);
    $excel_last_total = floatval($row_ex_last['total_excel_debit'] ?? 0);

    // 5. คำนวณยอดรับชำระจริงในเดือนนี้จาก DB (สำหรับตรวจมิติที่ 2)
    $sql_receipts_this_month = "
    SELECT SUM(settled) as total_settled
    FROM (
        SELECT IFNULL(follow_money, 0) as settled
        FROM imr_tb_debtor_rights_opd
        WHERE accountcode = '$accountcode'
          AND (
            (billdate IS NOT NULL AND billdate != '' AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10 AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) = '$target_ym')
            OR
            ((billdate IS NULL OR billdate = '' OR billdate = '-') AND mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND CHAR_LENGTH(mobile) >= 10 AND CONCAT((SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),'-',SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)) = '$target_ym')
          )
        UNION ALL
        SELECT IFNULL(follow_money, 0) as settled
        FROM imr_tb_debtor_rights_ipd
        WHERE accountcode = '$accountcode'
          AND (
            (billdate IS NOT NULL AND billdate != '' AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10 AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) = '$target_ym')
            OR
            ((billdate IS NULL OR billdate = '' OR billdate = '-') AND mobile IS NOT NULL AND mobile != '' AND mobile != '-' AND CHAR_LENGTH(mobile) >= 10 AND CONCAT((SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),'-',SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)) = '$target_ym')
          )
        UNION ALL
        SELECT IFNULL(pay_amount, 0) as settled
        FROM imr_tb_payment_history ph
        JOIN imr_tb_debtor_rights_opd opd ON ph.ref_vn_an = opd.vn
        WHERE opd.accountcode = '$accountcode' AND DATE_FORMAT(ph.bill_date, '%Y-%m') = '$target_ym'
    ) AS s
    ";
    $q_rec = mysqli_query($conn, $sql_receipts_this_month);
    $row_rec = mysqli_fetch_assoc($q_rec);
    $db_receipts_this_month = floatval($row_rec['total_settled'] ?? 0);

    // --- วินิจฉัย 3 มิติ ---
    // มิติ 1: การตั้งลูกหนี้ต้นงวด (Setup Variance: Insurance vs Accounting)
    $dim1_diff = round($col5_num - $col6_num, 2);
    $dim1_status = (abs($dim1_diff) < 0.01) ? 'MATCH' : 'DIFF';
    if ($col6_num == 0.0 && empty($raw_col6) && $col5_num > 0) {
        $dim1_status = 'PENDING';
        $dim1_desc = "กลุ่มงานบัญชียังไม่ได้ลงข้อมูลในงบทดลอง (รอลงข้อมูล)";
        $dim1_comp = "รอลงข้อมูล";
        $dim1_dir = "PENDING";
    } elseif ($col5_num == 0.0 && $col6_num == 0.0) {
        $dim1_desc = "ไม่มีการตั้งลูกหนี้ใหม่ในเดือนนี้ (ยอดส่งเบิกและงบทดลอง = 0.00 บาท)";
        $dim1_comp = "ตรงกัน";
        $dim1_dir = "MATCH";
    } elseif (abs($dim1_diff) < 0.01) {
        $dim1_desc = "ยอดตั้งลูกหนี้กลุ่มงานประกันฯ และยอดในงบทดลองของกลุ่มงานบัญชีตรงกัน 100%";
        $dim1_comp = "ตรงกัน";
        $dim1_dir = "MATCH";
    } elseif ($dim1_diff > 0) {
        $dim1_desc = "ยอดตั้งลูกหนี้ของประกันฯ มากกว่างบทดลองบัญชี " . number_format($dim1_diff, 2) . " บาท (อาจมีการส่งเบิก/นำเข้าเพิ่มหลังปิดงบ หรือบัญชีลงไม่ครบ)";
        $dim1_comp = "ประกันฯ มากกว่า";
        $dim1_dir = "GREATER";
    } else {
        $dim1_desc = "ยอดตั้งลูกหนี้ของประกันฯ น้อยกว่างบทดลองบัญชี " . number_format(abs($dim1_diff), 2) . " บาท (งบทดลองบัญชีบันทึกมากกว่า)";
        $dim1_comp = "ประกันฯ น้อยกว่า";
        $dim1_dir = "LESS";
    }

    // มิติ 2: การตัดหนี้/รับชำระระหว่างงวด (Settlement Variance: Accounting cut vs actual receipts)
    $dim2_diff = round($col10_num - $db_receipts_this_month, 2);
    $dim2_status = (abs($dim2_diff) < 0.01) ? 'MATCH' : 'DIFF';
    if ($col10_num == 0.0 && empty($raw_col10) && $db_receipts_this_month > 0) {
        $dim2_status = 'PENDING';
        $dim2_desc = "มีรายการรับชำระในระบบผู้ป่วย " . number_format($db_receipts_this_month, 2) . " บาท แต่บัญชียังไม่ได้ลงยอดตัดหนี้";
        $dim2_comp = "รอยืนยันตัดหนี้";
        $dim2_dir = "PENDING";
    } elseif ($col10_num == 0.0 && $db_receipts_this_month == 0.0) {
        $dim2_desc = "ไม่มีการตัดหนี้หรือรับชำระในเดือนนี้ (ยอดตัดหนี้และใบเสร็จ = 0.00 บาท)";
        $dim2_comp = "ตรงกัน";
        $dim2_dir = "MATCH";
    } elseif (abs($dim2_diff) < 0.01) {
        $dim2_desc = "ยอดตัดหนี้ในงบทดลองตรงกับยอดรับชำระจริงในระบบผู้ป่วย " . number_format($db_receipts_this_month, 2) . " บาท";
        $dim2_comp = "ตรงกัน";
        $dim2_dir = "MATCH";
    } elseif ($dim2_diff > 0) {
        $dim2_desc = "บัญชีตัดหนี้มากกว่าใบเสร็จในระบบผู้ป่วย " . number_format($dim2_diff, 2) . " บาท (มีรายการที่บัญชีตัดแต่ประกันยังไม่ได้ลงเลขใบเสร็จใน eDHS)";
        $dim2_comp = "บัญชีตัดหนี้ มากกว่า";
        $dim2_dir = "GREATER";
    } else {
        $dim2_desc = "บัญชีตัดหนี้น้อยกว่าใบเสร็จในระบบผู้ป่วย " . number_format(abs($dim2_diff), 2) . " บาท (ใบเสร็จในระบบผู้ป่วยมากกว่า รอทางบัญชีตรวจสอบเพื่อตัดหนี้ในงบทดลอง)";
        $dim2_comp = "บัญชีตัดหนี้ น้อยกว่า";
        $dim2_dir = "LESS";
    }

    // มิติ 3: ยอดต่างสะสมยกมาจากเดือนก่อนหน้า (Historical Accumulated Variance)
    $dim3_diff = round($col3_num - $excel_last_total, 2);
    $dim3_status = (abs($dim3_diff) < 0.01) ? 'MATCH' : 'DIFF';
    if (abs($dim3_diff) < 0.01) {
        $dim3_desc = "ยอดยกมาจากเดือนก่อนหน้าตรงกันสมบูรณ์ (ไม่มีผลต่างสะสมตกค้างจากอดีต)";
        $dim3_comp = "ตรงกัน";
        $dim3_dir = "MATCH";
    } elseif ($dim3_diff > 0) {
        $dim3_desc = "ยอดยกมามากกว่าลูกหนี้สิ้นเดือนก่อน ($monthlast) จำนวน " . number_format($dim3_diff, 2) . " บาท (เป็นผลต่างสะสมในอดีตยกยอดมา)";
        $dim3_comp = "ยอดยกมา มากกว่า";
        $dim3_dir = "GREATER";
    } else {
        $dim3_desc = "ยอดยกมาน้อยกว่าลูกหนี้สิ้นเดือนก่อน ($monthlast) จำนวน " . number_format(abs($dim3_diff), 2) . " บาท (ลูกหนี้สิ้นเดือนก่อนมากกว่า)";
        $dim3_comp = "ยอดยกมา น้อยกว่า";
        $dim3_dir = "LESS";
    }

    // สรุปภาพรวม
    $overall_diff = ($col12_num !== null) ? round($excel_total - $col12_num, 2) : 0.0;
    if (abs($overall_diff) < 0.01) {
        $overall_comp = "ตรงกัน";
        $overall_dir = "MATCH";
    } elseif ($overall_diff > 0) {
        $overall_comp = "ลูกหนี้รายตัว มากกว่า";
        $overall_dir = "GREATER";
    } else {
        $overall_comp = "ลูกหนี้รายตัว น้อยกว่า";
        $overall_dir = "LESS";
    }
    $is_carried_static = ($col5_num == 0.0 && $col6_num == 0.0 && $col10_num == 0.0 && $col3_num > 0 && abs($overall_diff) < 0.01);

    // คู่มือแนวทางปฏิบัติ (Action Guide)
    $insurance_action = [];
    $accounting_action = [];

    if ($is_carried_static) {
        $insurance_action[] = "เป็นลูกหนี้ยอดยกมาจากเดือนก่อนหน้า ไม่มีการตั้งหนี้ใหม่และไม่มียอดชดเชยในเดือนนี้ (ยอดยกมาตรงกับทะเบียนคุมรายตัว 100%)";
        $accounting_action[] = "ยอดยกมาคงเดิมตรงกับงบทดลอง ไม่มียอดเคลื่อนไหวที่ต้องบันทึกเพิ่มในเดือนนี้";
    } else {
        if (abs($dim1_diff) >= 0.01) {
            $insurance_action[] = "ตรวจสอบรายงานส่งเบิกประจำเดือนว่ามียอด Visit ที่ยกเลิกหรือเพิ่มเข้ามาระหว่างเดือนหลังส่งยอดให้บัญชีหรือไม่";
            $accounting_action[] = "ตรวจสอบว่างบทดลองบันทึกตรงตามใบปะหน้าสรุปลูกหนี้สิทธิของประกันฯ หรือไม่ (ต่าง ฿" . number_format(abs($dim1_diff), 2) . ")";
        }
        if (abs($dim2_diff) >= 0.01) {
            $insurance_action[] = "ตรวจสอบเล่มใบเสร็จรับเงิน/Statement ธนาคาร หรือยอดตามหนี้ว่ามีรายการรับเงินที่ยังไม่ได้กรอกเลขใบเสร็จ/วันที่ลงใน eDHS หรือไม่";
            $accounting_action[] = "เทียบรายการตัดหนี้กับรายงานยอดรับชำระจริงจากฝ่ายการเงิน/ประกันฯ";
        }
        if (abs($dim3_diff) >= 0.01) {
            $insurance_action[] = "ย้อนดูเดือนก่อนหน้า ($monthlast) ร่วมกับฝ่ายบัญชีเพื่อยืนยันรายการลูกหนี้รายตัวที่คงค้าง";
            $accounting_action[] = "หากเป็นผลต่างในอดีตที่ได้รับการอนุมัติปรับปรุงแล้ว สามารถบันทึกในส่วนปรับปรุงลูกหนี้เดือนที่ผ่านมา";
        }
        if (empty($insurance_action)) {
            $insurance_action[] = "ข้อมูลลูกหนี้รายตัวและทะเบียนคุมถูกต้องสมบูรณ์ ไม่มีข้อผิดพลาดที่ต้องแก้ไข";
        }
        if (empty($accounting_action)) {
            $accounting_action[] = "ยอดงบทดลองและยอดตัดหนี้สอดคล้องกับทะเบียนลูกหนี้ผู้ป่วยรายตัว 100%";
        }
    }

    echo json_encode([
        'status' => 'success',
        'accountcode' => $accountcode,
        'accountname' => $accountname,
        'month' => $month,
        'monthlast' => $monthlast,
        'col3_last' => $col3_num,
        'col3_last_fmt' => number_format($col3_num, 2),
        'col5_insurance' => $col5_num,
        'col5_fmt' => number_format($col5_num, 2),
        'col6_accounting' => $col6_num,
        'col6_fmt' => number_format($col6_num, 2),
        'col10_accounting' => $col10_num,
        'col10_fmt' => number_format($col10_num, 2),
        'col12_table' => $col12_num,
        'col12_fmt' => ($col12_num !== null) ? number_format($col12_num, 2) : ($raw_col12 ?: '-'),
        'excel_total' => $excel_total,
        'excel_total_fmt' => number_format($excel_total, 2),
        'excel_count' => $excel_count,
        'overall_diff' => $overall_diff,
        'overall_diff_fmt' => number_format(abs($overall_diff), 2),
        'overall_comp' => $overall_comp,
        'overall_dir' => $overall_dir,
        'dimensions' => [
            'dim1_setup' => [
                'title' => 'มิติที่ 1: การตั้งลูกหนี้ต้นงวด (Setup Variance)',
                'col5_insurance' => $col5_num,
                'col6_accounting' => $col6_num,
                'diff' => $dim1_diff,
                'diff_fmt' => number_format(abs($dim1_diff), 2),
                'comparison' => $dim1_comp,
                'dir' => $dim1_dir,
                'status' => $dim1_status,
                'description' => $dim1_desc
            ],
            'dim2_settlement' => [
                'title' => 'มิติที่ 2: การตัดหนี้/รับชำระระหว่างงวด (Settlement Variance)',
                'col10_accounting' => $col10_num,
                'db_receipts' => $db_receipts_this_month,
                'diff' => $dim2_diff,
                'diff_fmt' => number_format(abs($dim2_diff), 2),
                'comparison' => $dim2_comp,
                'dir' => $dim2_dir,
                'status' => $dim2_status,
                'description' => $dim2_desc
            ],
            'dim3_historical' => [
                'title' => 'มิติที่ 3: ยอดต่างสะสมยกมาจากเดือนก่อนหน้า (Historical Variance)',
                'col3_beginning' => $col3_num,
                'excel_last_total' => $excel_last_total,
                'diff' => $dim3_diff,
                'diff_fmt' => number_format(abs($dim3_diff), 2),
                'comparison' => $dim3_comp,
                'dir' => $dim3_dir,
                'status' => $dim3_status,
                'description' => $dim3_desc
            ]
        ],
        'action_guide' => [
            'insurance' => $insurance_action,
            'accounting' => $accounting_action
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ถ้าไม่มี action ที่ระบุ
echo json_encode([
    'status' => 'error',
    'message' => 'ไม่พบ Action ที่ร้องขอ (' . htmlspecialchars($action) . ')'
], JSON_UNESCAPED_UNICODE);
exit;
