<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require './database_config/config.php';

if(isset($_POST['accountcode']) && isset($_POST['month']) && isset($_POST['range'])) {
    
    $accCode = mysqli_real_escape_string($conn, $_POST['accountcode']);
    $month   = mysqli_real_escape_string($conn, $_POST['month']);
    $range   = mysqli_real_escape_string($conn, $_POST['range']);

    list($month_num, $year_num) = explode('-', $month);
    $target_ym = sprintf('%s-%02d', $year_num, $month_num); // เช่น '2026-03'

    // วันสุดท้ายของเดือน target (ค.ศ.) สำหรับ TIMESTAMPDIFF
    $last_day = date('Y-m-t', mktime(0, 0, 0, $month_num, 1, $year_num)); // เช่น '2026-03-31'

    // ✅ หาเดือนล่าสุดในฐานข้อมูล (Logic เดียวกับหน้าหลัก)
    $sql_max = "
        SELECT ym FROM (
            SELECT CONCAT(
                SUBSTRING_INDEX(monthtxt,'-',-1),'-',
                LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
            ) AS ym
            FROM (
                SELECT monthtxt FROM imr_tb_debtor_rights_opd
                WHERE monthtxt IS NOT NULL AND monthtxt != ''
                UNION
                SELECT monthtxt FROM imr_tb_debtor_rights_ipd
                WHERE monthtxt IS NOT NULL AND monthtxt != ''
            ) AS t
        ) AS ym_list
        WHERE ym REGEXP '^[0-9]{4}-[0-9]{2}$'
        ORDER BY ym DESC
        LIMIT 1
    ";
    $max_result   = mysqli_query($conn, $sql_max);
    $max_row      = mysqli_fetch_assoc($max_result);
    $max_ym       = $max_row['ym'] ?? $target_ym;
    $is_latest_month = ($target_ym === $max_ym);

    // ------------------------------------------------------------------
    // เงื่อนไขอายุหนี้ — ใช้ TIMESTAMPDIFF เทียบกับ '$last_day'
    // แปลงวันที่ไทย dd/mm/YYYY → ค.ศ. ด้วย STR_TO_DATE + ลบ 543
    // ------------------------------------------------------------------
    $ageExprOPD = "TIMESTAMPDIFF(MONTH,
                     STR_TO_DATE(
                       CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS UNSIGNED)-543),
                       '%d/%m/%Y'),
                     '$last_day')";

    $ageExprIPD = "TIMESTAMPDIFF(MONTH,
                     STR_TO_DATE(
                       CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS UNSIGNED)-543),
                       '%d/%m/%Y'),
                     '$last_day')";

    if($range == 'lt_3') {
        $rangeConditionOPD = "AND $ageExprOPD < 3";
        $rangeConditionIPD = "AND $ageExprIPD < 3";
    } elseif($range == '3_12') {
        $rangeConditionOPD = "AND $ageExprOPD >= 3 AND $ageExprOPD <= 12";
        $rangeConditionIPD = "AND $ageExprIPD >= 3 AND $ageExprIPD <= 12";
    } elseif($range == 'gt_12') {
        $rangeConditionOPD = "AND $ageExprOPD > 12";
        $rangeConditionIPD = "AND $ageExprIPD > 12";
    } else {
        $rangeConditionOPD = "";
        $rangeConditionIPD = "";
    }

    // ------------------------------------------------------------------
    // เงื่อนไข follow_money — หักเฉพาะเมื่อตัดใบเสร็จ ≤ target
    // ใช้ Logic เดียวกับ debtor_excel.php
    // ------------------------------------------------------------------
    $followMoneyCase = "
        CASE
            WHEN billdate IS NOT NULL AND billdate != '' 
             AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
             AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
            THEN IFNULL(follow_money, 0)

            WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
             AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
             AND mobile LIKE '%/%'
             AND CHAR_LENGTH(mobile) >= 10
             AND CONCAT(
                   (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                   '-',
                   SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)   -- ✅ แก้จาก -8 เป็น -6
                 ) <= '$target_ym'
            THEN IFNULL(follow_money, 0)

            ELSE 0
        END
    ";

    // ✅ เพิ่ม pay_amount OPD
    $payAmountOPD = "
        IFNULL((
            SELECT SUM(ph.pay_amount)
            FROM imr_tb_payment_history ph
            WHERE ph.ref_vn_an = vn
              AND ph.patient_type = 'OPD'
              AND ph.bill_date IS NOT NULL
              AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
        ), 0)
    ";

    // ✅ เพิ่ม pay_amount IPD
    $payAmountIPD = "
        IFNULL((
            SELECT SUM(ph.pay_amount)
            FROM imr_tb_payment_history ph
            WHERE ph.ref_vn_an = an
              AND ph.patient_type = 'IPD'
              AND ph.bill_date IS NOT NULL
              AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
        ), 0)
    ";

    // ------------------------------------------------------------------
    // ✅ เงื่อนไขใบเสร็จ — แยก latest month vs ย้อนหลัง เหมือนหน้าหลัก
    // ------------------------------------------------------------------
    if ($is_latest_month) {
        // เดือนล่าสุด: แสดงเฉพาะรายการที่ยังไม่มีใบเสร็จเลย (billdate AND mobile ว่าง)
        $receiptCase = "
            (billdate IS NULL OR billdate = '' OR billdate = '-')
            AND (mobile IS NULL OR mobile = '' OR mobile = '-' OR mobile NOT LIKE '%/%')
        ";
    } else {
        // เดือนย้อนหลัง: แสดงรายการที่ใบเสร็จออกหลัง target_ym (ยังค้างอยู่ ณ เดือนนั้น)
        $receiptCase = "
            CASE
                WHEN billdate IS NOT NULL AND billdate != ''
                 AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                THEN CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) > '$target_ym'

                WHEN mobile IS NOT NULL AND mobile != ''
                 AND mobile != '-' AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
                THEN CONCAT(
                       (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                       '-',
                       SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                     ) > '$target_ym'

                ELSE TRUE
            END
        ";
    }

    // ------------------------------------------------------------------
    // เงื่อนไข monthtxt — แก้ LPAD รองรับ '4-2026' (ไม่มี 0 นำหน้า)
    // ------------------------------------------------------------------
    $monthtxtCond = "
        CONCAT(
          SUBSTRING_INDEX(monthtxt,'-',-1),
          '-',
          LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
        ) <= '$target_ym'
    ";

$sql = "
    SELECT * FROM (

        /* ==== OPD ==== */
        SELECT 'OPD' AS type,
               vstdate AS srv_date,
               hn, ptname,
               billdate, mobile,

               -- ✅ หัก follow_money + pay_amount
               (IFNULL(debit,0) - ($followMoneyCase) - $payAmountOPD) AS debit,

               CASE
                   WHEN billdate IS NOT NULL AND billdate != ''
                    AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                   THEN CONCAT('✅ ', billdate)
                   WHEN mobile IS NOT NULL AND mobile != ''
                    AND mobile != '-' AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
                   THEN CONCAT('✅ ', SUBSTR(mobile, CHAR_LENGTH(mobile)-9, 10))
                   ELSE '⏳ ยังไม่มีใบเสร็จ'
               END AS receipt_status

        FROM imr_tb_debtor_rights_opd
        WHERE accountcode = '$accCode'
          AND IFNULL(debit, 0) > 0
          AND $monthtxtCond
        $rangeConditionOPD

        UNION ALL

        /* ==== IPD ==== */
        SELECT 'IPD' AS type,
               dchdate AS srv_date,
               hn, ptname,
               billdate, mobile,

               -- ✅ หัก follow_money + pay_amount
               (IFNULL(debit,0) - ($followMoneyCase) - $payAmountIPD) AS debit,

               CASE
                   WHEN billdate IS NOT NULL AND billdate != ''
                    AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                   THEN CONCAT('✅ ', billdate)
                   WHEN mobile IS NOT NULL AND mobile != ''
                    AND mobile != '-' AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
                   THEN CONCAT('✅ ', SUBSTR(mobile, CHAR_LENGTH(mobile)-9, 10))
                   ELSE '⏳ ยังไม่มีใบเสร็จ'
               END AS receipt_status

        FROM imr_tb_debtor_rights_ipd
        WHERE accountcode = '$accCode'
          AND IFNULL(debit, 0) > 0
          AND $monthtxtCond
        $rangeConditionIPD

    ) AS all_debtors
    WHERE debit > 0
      AND ($receiptCase)
    ORDER BY srv_date DESC
";

    $result = mysqli_query($conn, $sql);

    if(!$result) {
        echo '<div class="alert alert-danger"><strong>Query Error:</strong><br>' . mysqli_error($conn) . '</div>';
        exit;
    }

    if(mysqli_num_rows($result) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-striped table-sm" style="font-size: 14px;">';
        echo '<thead class="text-center">
        <tr style="background-color:#dfdfdf;">
            <th>ลำดับ</th>
            <th>ประเภท</th>
            <th>วันที่รับบริการ</th>
            <th>HN</th>
            <th>ชื่อ-สกุล</th>
            <th>จำนวนเงิน (บาท)</th>
            <th>สถานะใบเสร็จ</th>
        </tr>
      </thead><tbody>';

        $total_debit = 0;
        $i = 1;
        while($row = mysqli_fetch_assoc($result)) {
            $total_debit += $row['debit'];

            // กำหนดสีตามสถานะ
            $status = $row['receipt_status'];
            if (strpos($status, '✅') === 0) {
                $statusStyle = 'color: green; font-weight: bold;';
            } else {
                $statusStyle = 'color: #e67e00; font-weight: bold;';
            }

            echo '<tr>
                    <td class="text-center">'.$i.'</td>
                    <td class="text-center">'.$row['type'].'</td>
                    <td class="text-center">'.$row['srv_date'].'</td>
                    <td class="text-center">'.$row['hn'].'</td>
                    <td>'.$row['ptname'].'</td>
                    <td class="text-end">'.number_format($row['debit'],2).'</td>
                    <td class="text-center" style="'.$statusStyle.'">'.$status.'</td>
                  </tr>';
            $i++;
        }

        echo '<tr class="fw-bold bg-light">
            <td colspan="5" class="text-center" style="font-size:16px;">
                รวมยอดเงินสิทธิรหัส '.$accCode.'
            </td>
            <td class="text-end text-danger" style="font-size:16px;">
                '.number_format($total_debit,2).'
            </td>
            <td></td>
          </tr>';
        echo '</tbody></table></div>';

    } else {
        echo '<div class="alert alert-warning text-center">ไม่พบข้อมูลรายบุคคล</div>';
    }

} else {
    echo '<div class="alert alert-danger text-center">ส่งค่ามาไม่ครบ กรุณาตรวจสอบ</div>';
}

mysqli_close($conn);
?>