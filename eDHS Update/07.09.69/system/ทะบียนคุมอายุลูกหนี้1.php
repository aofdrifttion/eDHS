<?php
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
if (function_exists('system_log')) {
    $pageName = basename($_SERVER['PHP_SELF']);
    system_log($conn, 'เปิดดูข้อมูล (View)', 'VIEW', "ผู้ใช้เปิดดูหน้ารายงาน/ข้อมูล: " . $pageName);
}

function DateThai($strDate)
{
    $strYear = date("Y",strtotime($strDate))+543;
    $strMonth= date("n",strtotime($strDate));
    $strDay= date("j",strtotime($strDate));
    $strMonthCut = Array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
    $strMonthThai=$strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear";
}

function DateThaiM($strDate) {
    $strYear = date("Y",strtotime($strDate))+543;
    $strMonth= date("n",strtotime($strDate));
    $strMonthCut = Array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
    $strMonthThai=$strMonthCut[$strMonth];
    return [
        "fullDate" => "$strMonthThai $strYear",
        "fiscalYear" => $strYear 
    ];
}

$strDate = date("Y/m/d");
$yyy = date("Y")+543;

if (isset($_POST['month'])) {

    $month = $_POST['month']; 
    
    // หาวันที่สิ้นเดือนของเดือนที่ส่งมา เพื่อใช้เป็นจุดตัดอายุหนี้
    $dateObj = DateTime::createFromFormat('m-Y', $month);
    $gmm = $dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d'); 
    $last_day_of_month = date('t', strtotime($gmm)); // ดึงวันสิ้นเดือนมาแสดงในตาราง

    // แก้ไขตัวแปรที่มีตัว e เกินมาให้แล้วนะครับ
list($month_num, $year_num) = explode('-', $month);
$target_ym = sprintf('%s-%02d', $year_num, $month_num);

// หาเดือนล่าสุดในฐานข้อมูล
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
$max_result = $conn->query($sql_max);
$max_row    = $max_result->fetch_assoc();
$max_ym     = $max_row['ym'] ?? $target_ym;
$is_latest_month = ($target_ym === $max_ym);

// receiptFilter แยก latest/ย้อนหลัง
if ($is_latest_month) {
    $receiptFilter = "
        (billdate IS NULL OR billdate = '' OR billdate = '-')
        AND (mobile IS NULL OR mobile = '' OR mobile = '-')
    ";
} else {
    $receiptFilter = "
        CASE
            WHEN billdate IS NOT NULL AND billdate != ''
             AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
            THEN CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) > '$target_ym'

            WHEN mobile IS NOT NULL AND mobile != ''
             AND mobile != '-' AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
            THEN CONCAT(
                   (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                   '-',
                   SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)   -- ✅ แก้จาก -8 เป็น -6
                 ) > '$target_ym'

            ELSE TRUE
        END
    ";
}

// debit สุทธิ OPD — หัก follow_money + pay_amount แบ่งจ่าย
$netDebitOPD = "
    (IFNULL(debit,0)

    -- หัก follow_money (Logic เดิม)
    - CASE
        WHEN billdate IS NOT NULL AND billdate != ''
         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
         AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
        THEN IFNULL(follow_money,0)

        WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
         AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
         AND mobile LIKE '%/%'
         AND CHAR_LENGTH(mobile) >= 10
         AND CONCAT(
               (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
               '-',
               SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
             ) <= '$target_ym'
        THEN IFNULL(follow_money,0)

        ELSE 0
      END

    -- ✅ หัก pay_amount สะสมจากตารางแบ่งจ่าย ≤ target
    - IFNULL((
        SELECT SUM(ph.pay_amount)
        FROM imr_tb_payment_history ph
        WHERE ph.ref_vn_an = vn
          AND ph.patient_type = 'OPD'
          AND ph.bill_date IS NOT NULL
          AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
      ), 0)
    )
";

// ✅ เพิ่ม netDebitIPD แยกต่างหาก (ใช้ an และ patient_type = 'IPD')
$netDebitIPD = "
    (IFNULL(debit,0)

    - CASE
        WHEN billdate IS NOT NULL AND billdate != ''
         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
         AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
        THEN IFNULL(follow_money,0)

        WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
         AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
         AND mobile LIKE '%/%'
         AND CHAR_LENGTH(mobile) >= 10
         AND CONCAT(
               (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
               '-',
               SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
             ) <= '$target_ym'
        THEN IFNULL(follow_money,0)

        ELSE 0
      END

    -- ✅ IPD ใช้ an และ patient_type = 'IPD'
    - IFNULL((
        SELECT SUM(ph.pay_amount)
        FROM imr_tb_payment_history ph
        WHERE ph.ref_vn_an = an
          AND ph.patient_type = 'IPD'
          AND ph.bill_date IS NOT NULL
          AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
      ), 0)
    )
";


// monthtxt filter
$monthtxtFilter = "
    CONCAT(
      SUBSTRING_INDEX(monthtxt,'-',-1),
      '-',
      LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
    ) <= '$target_ym'
";

// age expression
$ageOPD = "TIMESTAMPDIFF(MONTH,
    STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6),CAST(SUBSTR(vstdate,7,4) AS SIGNED)-543),'%d/%m/%Y'),
    LAST_DAY('$gmm'))";

$ageIPD = "TIMESTAMPDIFF(MONTH,
    STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6),CAST(SUBSTR(dchdate,7,4) AS SIGNED)-543),'%d/%m/%Y'),
    LAST_DAY('$gmm'))";

$sql = "
SELECT 
    base.code AS accountcode,
    COALESCE(tc.Name,'ไม่พบชื่อบัญชี') AS accountname,
    IFNULL(tx.count_lt_3,0)  AS count_lt_3,
    IFNULL(tx.sum_lt_3,0)    AS sum_lt_3,
    IFNULL(tx.count_3_12,0)  AS count_3_12,
    IFNULL(tx.sum_3_12,0)    AS sum_3_12,
    IFNULL(tx.count_gt_12,0) AS count_gt_12,
    IFNULL(tx.sum_gt_12,0)   AS sum_gt_12,
    IFNULL(tx.count_total,0) AS count_total,
    IFNULL(tx.sum_total,0)   AS sum_total
FROM (
    SELECT DISTINCT code FROM imr_tb_debtor_result WHERE month = '$month'
) AS base
LEFT JOIN tb_code tc ON base.code = tc.Code
LEFT JOIN (

    SELECT accountcode,
        SUM(count_lt_3)  AS count_lt_3,  SUM(sum_lt_3)  AS sum_lt_3,
        SUM(count_3_12)  AS count_3_12,  SUM(sum_3_12)  AS sum_3_12,
        SUM(count_gt_12) AS count_gt_12, SUM(sum_gt_12) AS sum_gt_12,
        SUM(count_total) AS count_total, SUM(sum_total) AS sum_total
    FROM (

        /* ==== OPD ==== */
        SELECT accountcode,
            SUM(CASE WHEN $ageOPD <  3              THEN 1 ELSE 0 END) AS count_lt_3,
            SUM(CASE WHEN $ageOPD <  3              THEN $netDebitOPD ELSE 0 END) AS sum_lt_3,
            SUM(CASE WHEN $ageOPD >= 3 AND $ageOPD <= 12 THEN 1 ELSE 0 END) AS count_3_12,
            SUM(CASE WHEN $ageOPD >= 3 AND $ageOPD <= 12 THEN $netDebitOPD ELSE 0 END) AS sum_3_12,
            SUM(CASE WHEN $ageOPD >  12             THEN 1 ELSE 0 END) AS count_gt_12,
            SUM(CASE WHEN $ageOPD >  12             THEN $netDebitOPD ELSE 0 END) AS sum_gt_12,
            COUNT(*) AS count_total,
            SUM($netDebitOPD) AS sum_total
        FROM imr_tb_debtor_rights_opd
        WHERE IFNULL(debit,0) > 0
          AND $monthtxtFilter
          AND ($receiptFilter)
          AND $netDebitOPD > 0
        GROUP BY accountcode

        UNION ALL

        /* ==== IPD ==== */
        SELECT accountcode,
            SUM(CASE WHEN $ageIPD <  3               THEN 1 ELSE 0 END) AS count_lt_3,
            SUM(CASE WHEN $ageIPD <  3               THEN $netDebitIPD ELSE 0 END) AS sum_lt_3,  -- ✅ IPD
            SUM(CASE WHEN $ageIPD >= 3 AND $ageIPD <= 12 THEN 1 ELSE 0 END) AS count_3_12,
            SUM(CASE WHEN $ageIPD >= 3 AND $ageIPD <= 12 THEN $netDebitIPD ELSE 0 END) AS sum_3_12,  -- ✅ IPD
            SUM(CASE WHEN $ageIPD >  12              THEN 1 ELSE 0 END) AS count_gt_12,
            SUM(CASE WHEN $ageIPD >  12              THEN $netDebitIPD ELSE 0 END) AS sum_gt_12,  -- ✅ IPD
            COUNT(*) AS count_total,
            SUM($netDebitIPD) AS sum_total  -- ✅ IPD
        FROM imr_tb_debtor_rights_ipd
        WHERE IFNULL(debit,0) > 0
          AND $monthtxtFilter
          AND ($receiptFilter)
          AND $netDebitIPD > 0
        GROUP BY accountcode

    ) AS main_query
    GROUP BY accountcode

) AS tx ON base.code = tx.accountcode
ORDER BY base.code ASC
";


    $result = mysqli_query($conn, $sql);
    
    // เพิ่มการดักจับ Error กรณีที่รัน SQL ไม่ผ่าน เพื่อไม่ให้ PHP 8 ฟ้อง TypeError
    if (!$result) {
        die("<div style='color:red; padding:20px; text-align:center;'><strong>เกิดข้อผิดพลาดจากฐานข้อมูล:</strong> " . mysqli_error($conn) . "</div>");
    }

    // ----------------------------------------------------------------------
    // STEP 1: แยกข้อมูลใส่ตะกร้า (Arrays)
    // ----------------------------------------------------------------------
    $rows101 = []; 
    $rows102 = []; 

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $accCode = $row['accountcode'];
            $codeGroup = substr($accCode, 7, 3); // เช็คกลุ่มรหัส

            if ($codeGroup === '101') {
                $rows101[] = $row; 
            } elseif ($codeGroup === '102') {
                $rows102[] = $row;
            }
        }
    }

    // ----------------------------------------------------------------------
    // ฟังก์ชั่นช่วยแสดงแถวตาราง (Render Function)
    // ----------------------------------------------------------------------
    function renderRows($rows, $groupName, $month) {
        if (empty($rows)) return ['c1'=>0, 's1'=>0, 'c2'=>0, 's2'=>0, 'c3'=>0, 's3'=>0, 'c_tot'=>0, 's_tot'=>0];

        $sum_c1 = 0; $sum_s1 = 0;
        $sum_c2 = 0; $sum_s2 = 0;
        $sum_c3 = 0; $sum_s3 = 0;
        $sum_c_tot = 0; $sum_s_tot = 0;

        echo '<tr><td colspan="10" style="font-weight:bold; padding: 6px; text-align: left;">' . $groupName . '</td></tr>';

        foreach ($rows as $row) {
            $sum_c1 += $row["count_lt_3"]; $sum_s1 += $row["sum_lt_3"];
            $sum_c2 += $row["count_3_12"]; $sum_s2 += $row["sum_3_12"];
            $sum_c3 += $row["count_gt_12"]; $sum_s3 += $row["sum_gt_12"];
            $sum_c_tot += $row["count_total"]; $sum_s_tot += $row["sum_total"];

            // สร้างลิงก์ให้คลิกได้ พร้อมฝังข้อมูล (data-*)
            $acc = $row["accountcode"];
            $accName = $row["accountname"];
            
            $link = function($val, $range) use ($acc, $accName, $month) {
                if($val == 0) return "-";
                return '<a href="javascript:void(0);" class="text-primary fw-bold view-detail" 
                        data-acc="'.$acc.'" data-accname="'.$accName.'" data-range="'.$range.'" data-month="'.$month.'">' 
                        . number_format($val, 0) . '</a>';
            };

            echo '<tr data-code="'.$acc.'">
            <td style="text-align: center;">' . $acc . '</td>
            <td style="text-align: left;">' . $accName . '</td>
            <td style="text-align: right;">' . $link($row["count_lt_3"], 'lt_3') . '</td>
            <td style="text-align: right;">' . number_format($row["sum_lt_3"], 2) . '</td>
            <td style="text-align: right;">' . $link($row["count_3_12"], '3_12') . '</td>
            <td style="text-align: right;">' . number_format($row["sum_3_12"], 2) . '</td>
            <td style="text-align: right;">' . $link($row["count_gt_12"], 'gt_12') . '</td>
            <td style="text-align: right;">' . number_format($row["sum_gt_12"], 2) . '</td>
            <td style="text-align: right;">' . $link($row["count_total"], 'total') . '</td>
            <td style="text-align: right;">' . number_format($row["sum_total"], 2) . '</td>
            </tr>';
        }

        // --- ส่วนที่ทำยอดรวมกลุ่ม
        echo '<tr style="background-color: #fcfcfc;  border-top: 1px;">
        <td colspan="2" style="text-align: center;">รวม ' . $groupName . '</td>
        <td style="text-align: right;">'.number_format($sum_c1,0).'</td>
        <td style="text-align: right;">'.number_format($sum_s1,2).'</td>
        <td style="text-align: right;">'.number_format($sum_c2,0).'</td>
        <td style="text-align: right;">'.number_format($sum_s2,2).'</td>
        <td style="text-align: right;">'.number_format($sum_c3,0).'</td>
        <td style="text-align: right;">'.number_format($sum_s3,2).'</td>
        <td style="text-align: right;">'.number_format($sum_c_tot,0).'</td>
        <td style="text-align: right;">'.number_format($sum_s_tot,2).'</td>
        </tr>';

        return [
            'c1' => $sum_c1, 's1' => $sum_s1, 
            'c2' => $sum_c2, 's2' => $sum_s2, 
            'c3' => $sum_c3, 's3' => $sum_s3, 
            'c_tot' => $sum_c_tot, 's_tot' => $sum_s_tot
        ];
    }



    // ----------------------------------------------------------------------
    // STEP 2: เริ่มสร้างตารางหลัก (Main Table Output)
    // ----------------------------------------------------------------------
    ?>
    <div id="printable-area">
        <table border="1" style="width:100%; border-collapse: collapse; border: 1px solid #ddd;">
            <thead>
                <tr>
                    <th colspan="10" style="height: 50px;text-align: center;font-size: 16px; background-color: #fff;border: none;"> 
                        <?php echo isset($hospital) ? $hospital : ''; ?> ทะเบียนคุมอายุลูกหนี้ ปีงบประมาณ<span id="vtxt3_top"><?php $result1 = DateThaiM($gmm); echo $result1['fiscalYear']; ?></span><br>
                        เดือน <span id="vtxt0_top"><?php echo $result1['fullDate']; ?></span>
                    </th>
                </tr>

                <tr style="background-color: #f9f9f9;">
                    <th rowspan="3" style="text-align:center; border:1px solid #ddd;">รหัสบัญชี</th>
                    <th rowspan="3" style="text-align:center; border:1px solid #ddd;">ชื่อบัญชี</th>
                    <th colspan="6" style="text-align:center; border:1px solid #ddd;">อายุลูกหนี้ ณ วันที่ <?php echo $last_day_of_month . " " . $result1['fullDate']; ?></th>
                    <th colspan="2" rowspan="2" style="text-align:center; border:1px solid #ddd;">รวม</th>
                </tr>
                <tr style="background-color: #f9f9f9;">
                    <th colspan="2" style="text-align:center; border:1px solid #ddd;">&lt; 3 เดือน</th>
                    <th colspan="2" style="text-align:center; border:1px solid #ddd;">&gt; 3 เดือน &lt; 12 เดือน</th>
                    <th colspan="2" style="text-align:center; border:1px solid #ddd;">&gt; 12 เดือน</th>
                </tr>
                <tr style="background-color: #f9f9f9;">
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (ราย)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (บาท)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (ราย)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (บาท)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (ราย)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (บาท)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (ราย)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($rows101) > 0 || count($rows102) > 0) {
                    
                    // บรรทัดนี้พี่น่าจะแก้แล้ว (ส่ง $month ไปด้วย)
                    $totals101 = renderRows($rows101, "ลูกหนี้การค้า - หน่วยงานภาครัฐ", $month);
                    
                    // บรรทัด 228 เจ้าปัญหา! น้องเติม , $month เข้าไปให้ตรงนี้แล้วครับ
                    $totals102 = renderRows($rows102, "ลูกหนี้การค้า - บุคคลภายนอก", $month);

                    // 3. แสดงยอดรวมทั้งสิ้น (Grand Total)
                    $g_c1 = $totals101['c1'] + $totals102['c1'];
                    $g_s1 = $totals101['s1'] + $totals102['s1'];
                    $g_c2 = $totals101['c2'] + $totals102['c2'];
                    $g_s2 = $totals101['s2'] + $totals102['s2'];
                    $g_c3 = $totals101['c3'] + $totals102['c3'];
                    $g_s3 = $totals101['s3'] + $totals102['s3'];
                    $g_c_tot = $totals101['c_tot'] + $totals102['c_tot'];
                    $g_s_tot = $totals101['s_tot'] + $totals102['s_tot'];

                    echo '<tr style="font-weight: bold; background-color: #f9f9f9;">
                    <td colspan="2" style="text-align: center; height: 34px;background-color: #f9f9f9;">รวมทั้งหมดทั้งสิ้น</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_c1,0).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_s1,2).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_c2,0).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_s2,2).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_c3,0).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_s3,2).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_c_tot,0).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_s_tot,2).'</td>
                    </tr>';

                } else {
                    echo '<tr><td colspan="10" style="text-align: center;">รอสรุปข้อมูลจากหน้ากระทบยอดลูกหนี้ก่อน</td></tr>';
                }


                ?>
            </tbody>
        </table>
    
    
        <br><br>

        <?php
        // ดึงข้อมูลชื่อคนเซ็นจากฐานข้อมูล
        $sql = "SELECT * FROM summary_responsibles ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        $data = $result->fetch_assoc();
        ?>

        <table class="signature-table" style="width:100%; border:none; margin-top: 30px;">
            <tr style="vertical-align: bottom;">
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">
                    <p style="margin: 2px;" >(<?php echo $data['name1'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo $data['position1'];?></p>
                    ผู้จัดทำรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ
                </td>
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">
                    <p style="margin: 2px;" >(<?php echo $data['name2'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo $data['position2'];?></p>
                    ผู้ตรวจสอบรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ
                </td>
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">                                    
                    <p style="margin: 2px;" >(<?php echo $data['name3'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo $data['position3'];?></p>
                    ผู้บันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี
                </td>
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">
                    <p style="margin: 2px;" >(<?php echo $data['name4'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo $data['position4'];?></p>
                    ผู้ตรวจสอบการบันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี
                </td>
            </tr>
            <tr>
                <td colspan="4" style="height: 35px; border: none;"></td>
            </tr>
            <tr style="vertical-align: bottom;">
                <td colspan="3" style="border: none;"></td>
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">
                    <p style="margin: 2px;" >(<?php echo $data['name5'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo !empty($data['position5']) ? $data['position5'] : 'ผู้อำนวยการโรงพยาบาล';?></p>
                    ผู้อำนวยการโรงพยาบาล<br>หัวหน้าหน่วยงาน
                </td>
            </tr>
        </table>
    </div> 

    <div style="text-align: right; padding-right: 20px; margin-top: 40px; margin-bottom: 50px;">
        <button onclick="printReportEnhanced()" class="btn-print">
            🖨️ พิมพ์รายงาน
        </button>
    </div>

    <script>
    function printReportEnhanced() {
        // 1. ดึงเนื้อหา HTML ที่เราเตรียมไว้ในกล่อง printable-area
        var printContents = document.getElementById('printable-area').innerHTML;
        
        // 2. เปิดหน้าต่างใหม่ขึ้นมา (Popup)
        var printWindow = window.open('', '', 'height=800,width=1200');
        
        // 3. เขียนโครงสร้าง HTML สำหรับการพิมพ์โดยเฉพาะลงไป
        printWindow.document.write('<html><head><title>พิมพ์รายงานทะเบียนคุมอายุลูกหนี้</title>');
        
        // --- CSS ขั้นเทพ สำหรับจัดหน้ารายงาน ---
        printWindow.document.write('<style>');
        printWindow.document.write(`
            /* ตั้งค่าฟอนต์มาตรฐานรายงานราชการ */
            @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');
            body {
                font-family: 'TH Sarabun New', 'Sarabun', Tahoma, sans-serif;
                font-size: 14pt; /* ขนาดตัวหนังสือตอนปริ้น */
                color: #000;
                line-height: 1.2;
            }

            /* ตั้งค่ากระดาษ แนวนอน และขอบกระดาษ */
            @media print {
                @page {
                    size: landscape; 
                    margin: 10mm; /* ขอบกระดาษ 1.5 ซม. */
                }
            }

            /* จัดการหัวตารางหลัก */
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 20px; 
            }
            /* เส้นขอบตาราง สีดำทึบ 1px */
            th, td { 
                border: 1px solid #000 !important; 
                padding: 3px 2px;
                vertical-align: middle;
            }
            /* สีพื้นหลังหัวตาราง ให้ปริ้นติดออกมาด้วย */
            th { 
                background-color: #f0f0f0 !important; 
                -webkit-print-color-adjust: exact; 
                color-adjust: exact;
                text-align: center;
                font-weight: bold;
                font-size: 14pt;
            }

            /* --- เทคนิคจัดการการขึ้นหน้าใหม่ (Page Break) --- */
            
            /* 1. หัวตาราง (thead) ให้พยายามโชว์ซ้ำเมื่อขึ้นหน้าใหม่ (บางเบราว์เซอร์รองรับ) */
            thead { display: table-header-group; }
            
            /* 2. แถวข้อมูล (tr) ห้ามขาดครึ่งกลางบรรทัดเมื่อสิ้นสุดหน้ากระดาษ */
            tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* --- จัดการตารางลายเซ็น --- */
            .signature-table {
                margin-top: 30px;
                /* สำคัญมาก! ห้ามตารางลายเซ็นถูกตัดแบ่งไปอยู่คนละหน้าเด็ดขาด */
                page-break-inside: avoid;
                break-inside: avoid;
                border: none !important;
            }
            .signature-table td { 
                border: none !important; /* ลายเซ็นไม่ต้องมีเส้นขอบ */
                text-align: center; 
                vertical-align: top;
                padding: 5px;
                font-size: 16pt;
                font-weight: bold;
            }
            /* พื้นที่ว่างสำหรับเซ็นชื่อ */
            .signature-space {
                height: 30px; 
            }
            
            /* ซ่อนลิงก์สีน้ำเงินตอนปริ้น ให้เป็นตัวดำปกติ */
            a { text-decoration: none; color: #000 !important; }
            
            /* ปรับขนาดหัวรายงาน */
            h2, h3 { margin: 5px 0; text-align: center; }

        `);
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        
        // 4. ยัดเนื้อหาตารางลงไป
        printWindow.document.write(printContents);
        
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        // 5. รอโหลดเสร็จแล้วสั่งปริ้น
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
             printWindow.close(); 
        }, 800); // เพิ่มเวลาหน่วงนิดนึงเผื่อโหลดฟอนต์
    }
    </script>

    <style>
        /* CSS สำหรับปุ่มในหน้าเว็บปกติ (ไม่เกี่ยวกับการปริ้น) */
        .btn-print {
            background-color: #0000a0; 
            color: #fff; 
            padding: 10px 25px; 
            border: none; 
            border-radius: 20px; 
            font-size: 16px; 
            cursor: pointer; 
            font-weight: bold; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        .btn-print:hover {
            background-color: #000080;
        }
        /* ซ่อนเส้นขอบตารางลายเซ็นในหน้าจอปกติด้วย */
        .signature-table td { border: none !important; }
    </style>

<?php
} // ปิดปีกกาของ if (isset($_POST['month']))
mysqli_close($conn);
?>