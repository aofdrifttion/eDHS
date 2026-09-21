<?php 
header('Content-Type: text/html; charset=utf-8');
session_start();
require './database_config/config.php';


if (!isset($_SESSION['dpavartxt1']) || empty($_SESSION['dpavartxt1'])) {
          echo "<script>
            alert('ยังไม่มีข้อมูล');
            window.top.location.href = 'index.php';
          </script>";
    exit(); // หยุดการทำงานทันที สำคัญมาก! ไม่งั้นโค้ดข้างล่างจะรันต่อจน Error
}

$dpavartxt1 = $_SESSION['dpavartxt1']; // ลูกหนี้สิทธิยกมาเดือน
$dpavartxt2 = $_SESSION['dpavartxt2']; //ลูกหนี้สิทธิ กลุ่มงานประกันสุขภาพ/บาท
$dpavartxt3 = $_SESSION['dpavartxt3']; //งบทดลอง กลุ่มงานบัญชี/บาท
$dpavartxt4 = $_SESSION['dpavartxt4'];
$dpavartxt5 = $_SESSION['dpavartxt5'];
$dpavartxt6 = $_SESSION['dpavartxt6'];
$dpavartxt7 = $_SESSION['dpavartxt7'];
$dpavartxt8 = $_SESSION['dpavartxt8'];
$dpavartxt9 = $_SESSION['dpavartxt9'];
$dpavartxt10 = $_SESSION['dpavartxt10'];

$dmonth = $_SESSION['dmonth'];
$dmonthlast = $_SESSION['dmonthlast']; 

//ลูกหนี้สิทธิยกไป 5 เดือน ย้อนหลัง
$adpavartxt10 = $_SESSION['adpavartxt10'];
$bdpavartxt10 = $_SESSION['bdpavartxt10'];
$cdpavartxt10 = $_SESSION['cdpavartxt10'];
$ddpavartxt10 = $_SESSION['ddpavartxt10'];

$adpavartxt6 = $_SESSION['adpavartxt6'];
$bdpavartxt6 = $_SESSION['bdpavartxt6'];
$cdpavartxt6 = $_SESSION['cdpavartxt6'];
$ddpavartxt6 = $_SESSION['ddpavartxt6'];

$adpavartxt7 = $_SESSION['adpavartxt7'];
$bdpavartxt7 = $_SESSION['bdpavartxt7'];
$cdpavartxt7 = $_SESSION['cdpavartxt7'];
$ddpavartxt7 = $_SESSION['ddpavartxt7'];

// แยกเดือนและปี
list($month, $year) = explode('-', $dmonth);

// คำนวณปีงบประมาณ
if ((int)$month >= 10) {
    $fiscalYear = $year + 1; // เดือน 10,11,12 -> ปีงบปีถัดไป
} else {
    $fiscalYear = $year; // เดือน 1-9 -> ปีงบปีเดียวกัน
}

// แปลงเป็น พ.ศ.
$fiscalYearThai = $fiscalYear + 543;

// =================================================================
// [อัปเดตล่าสุด] ส่วนคำนวณแยกยอด OPD / IPD ของการ์ดสรุปทุกใบ
// =================================================================
$current_month = $dmonth;      
$previous_month = $dmonthlast;  

// แปลงรูปแบบเดือนปัจจุบันให้อยู่ในฟอร์แมต YYYY-MM เพื่อเทียบข้อมูล
list($m_num, $y_num) = explode('-', $current_month);
$target_ym = sprintf('%s-%02d', $y_num, $m_num);

// -----------------------------------------------------------------
// 1. หาผลรวม "ลูกหนี้ยกมา" (ถอยหลังไปดึง column12 ของเดือนก่อนหน้า)
// -----------------------------------------------------------------
$sql_prev_totals = "
    SELECT 
        SUM(CASE WHEN opd.accountcode IS NOT NULL THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS opd_txt1,
        SUM(CASE WHEN ipd.accountcode IS NOT NULL AND opd.accountcode IS NULL THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS ipd_txt1
    FROM imr_tb_debtor_result r
    LEFT JOIN (
        SELECT DISTINCT accountcode FROM imr_tb_debtor_rights_opd WHERE accountcode IS NOT NULL AND accountcode != ''
    ) opd ON r.code = opd.accountcode
    LEFT JOIN (
        SELECT DISTINCT accountcode FROM imr_tb_debtor_rights_ipd WHERE accountcode IS NOT NULL AND accountcode != ''
    ) ipd ON r.code = ipd.accountcode
    WHERE r.month = '$previous_month'
";
$res_prev = $conn->query($sql_prev_totals);
$row_prev = $res_prev->fetch_assoc();

$opd_txt1  = number_format($row_prev['opd_txt1'] ?? 0, 2);
$ipd_txt1  = number_format($row_prev['ipd_txt1'] ?? 0, 2);

// -----------------------------------------------------------------
// 2. หาผลรวม "ลูกหนี้สิทธิ" (ตั้งหนี้ใหม่เดือนนี้) จากตาราง OPD / IPD โดยตรง
// -----------------------------------------------------------------
// ยอด OPD
$sql_opd_debit = "
    SELECT SUM(IFNULL(debit, 0)) AS total
    FROM imr_tb_debtor_rights_opd
    WHERE CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1), '-', LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) = '$target_ym'
";
$res_opd_debit = $conn->query($sql_opd_debit);
$opd_txt3 = number_format($res_opd_debit->fetch_assoc()['total'] ?? 0, 2);

// ยอด IPD
$sql_ipd_debit = "
    SELECT SUM(IFNULL(debit, 0)) AS total
    FROM imr_tb_debtor_rights_ipd
    WHERE CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1), '-', LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) = '$target_ym'
";
$res_ipd_debit = $conn->query($sql_ipd_debit);
$ipd_txt3 = number_format($res_ipd_debit->fetch_assoc()['total'] ?? 0, 2);

// -----------------------------------------------------------------
// 3. หาผลรวม "ตัดลูกหนี้" (column10) และ "ยกไป" (column12) ของเดือนปัจจุบัน
// -----------------------------------------------------------------
$sql_curr_totals = "
    SELECT 
        -- การ์ดตัดลูกหนี้ (column10)
        SUM(CASE WHEN opd.accountcode IS NOT NULL THEN CAST(REPLACE(IFNULL(r.column10, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS opd_txt7,
        SUM(CASE WHEN ipd.accountcode IS NOT NULL AND opd.accountcode IS NULL THEN CAST(REPLACE(IFNULL(r.column10, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS ipd_txt7,
        
        -- การ์ดลูกหนี้สิทธิยกไป (column12)
        SUM(CASE WHEN opd.accountcode IS NOT NULL THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS opd_txt10,
        SUM(CASE WHEN ipd.accountcode IS NOT NULL AND opd.accountcode IS NULL THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS ipd_txt10
    FROM imr_tb_debtor_result r
    LEFT JOIN (
        SELECT DISTINCT accountcode FROM imr_tb_debtor_rights_opd WHERE accountcode IS NOT NULL AND accountcode != ''
    ) opd ON r.code = opd.accountcode
    LEFT JOIN (
        SELECT DISTINCT accountcode FROM imr_tb_debtor_rights_ipd WHERE accountcode IS NOT NULL AND accountcode != ''
    ) ipd ON r.code = ipd.accountcode
    WHERE r.month = '$current_month'
";
$res_curr = $conn->query($sql_curr_totals);
$row_curr = $res_curr->fetch_assoc();

$opd_txt7  = number_format($row_curr['opd_txt7'] ?? 0, 2);
$ipd_txt7  = number_format($row_curr['ipd_txt7'] ?? 0, 2);

$opd_txt10 = number_format($row_curr['opd_txt10'] ?? 0, 2);
$ipd_txt10 = number_format($row_curr['ipd_txt10'] ?? 0, 2);

// -----------------------------------------------------------------
// 4. คำนวณสัดส่วน % สำหรับ Progress Bar ด้านข้าง
// -----------------------------------------------------------------
$carry_forward_num = (float)str_replace(',', '', $dpavartxt10);
$pct_opd = 0; $pct_ipd = 0;
if ($carry_forward_num > 0) {
    $pct_opd = (($row_curr['opd_txt10'] ?? 0) / $carry_forward_num) * 100;
    $pct_ipd = (($row_curr['ipd_txt10'] ?? 0) / $carry_forward_num) * 100;
}



// ฟังก์ชันแปลงเดือนเป็นชื่อไทย
function monthThai($monthYear) {
    // แยกเดือนและปีออกจากกัน
    list($month, $year) = explode('-', $monthYear);

    // ชื่อเดือนภาษาไทย
    $thaiMonths = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];

    // แปลงปีเป็น พ.ศ.
    $thaiYear = $year + 543;

    // คืนค่าเป็น “ชื่อเดือน ปีพ.ศ.”
    return $thaiMonths[(int)$month] . ' ' . $thaiYear;
}

$monthNow = monthThai($dmonth);
$monthLast = monthThai($dmonthlast);


// เดือนปัจจุบันจาก session เช่น "8-2025"
list($month, $year) = explode('-', $_SESSION['dmonth']); 

// ฟังก์ชันชื่อเดือนภาษาไทย
$monthNames = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

// วนย้อนหลัง 4 เดือน + เดือนปัจจุบัน
$labels = [];
for ($i = 4; $i >= 0; $i--) {
    $m = $month - $i;
    $y = $year;
    if ($m <= 0) {
        $m += 12;
        $y--;
    }
    $labels[] = $monthNames[$m];
}

list($aging_month, $aging_year) = explode('-', $dmonth);
$cal_date = "{$aging_year}-{$aging_month}-01"; // 'YYYY-MM-01' คือวันที่ใช้คำนวณ (Gregorian)


// 1. เตรียมวันที่คำนวณ (Calculation Date) อิงตาม Logic ใหม่
$month_for_query = $dmonth; // ใช้ dmonth จาก Session เดิม (รูปแบบ m-Y เช่น 8-2025)

// หาวันที่สิ้นเดือนของเดือนที่ส่งมา เพื่อใช้เป็นจุดตัดอายุหนี้
$dateObj = DateTime::createFromFormat('n-Y', $month_for_query); // ใช้ 'n-Y' รองรับเดือนแบบไม่มีเลข 0 นำหน้า
$gmm = $dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d'); 
$last_day_of_month = date('t', strtotime($gmm));

list($month_num, $year_num) = explode('-', $month_for_query);
$target_ym = sprintf('%s-%02d', $year_num, $month_num); 




// เงื่อนไข monthtxt — รองรับ '4-2026'
$monthtxtCond = "
    CONCAT(
        SUBSTRING_INDEX(monthtxt,'-',-1),
        '-',
        LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
    ) <= '$target_ym'
";

// ✅ แยก netDebit OPD และ IPD
$netDebitOPD = "
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
               SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)   -- ✅ แก้จาก -8 เป็น -6
             ) <= '$target_ym'
        THEN IFNULL(follow_money,0)

        ELSE 0
      END
    -- ✅ หัก pay_amount OPD
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
               SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)   -- ✅ แก้จาก -8 เป็น -6
             ) <= '$target_ym'
        THEN IFNULL(follow_money,0)

        ELSE 0
      END
    -- ✅ หัก pay_amount IPD
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
    ORDER BY ym DESC LIMIT 1
";
$max_result      = $conn->query($sql_max);
$max_row         = $max_result->fetch_assoc();
$max_ym          = $max_row['ym'] ?? $target_ym;
$is_latest_month = ($target_ym === $max_ym);

// ✅ receiptFilter แยก latest/ย้อนหลัง + แก้ -8 → -6
if ($is_latest_month) {
    $receiptFilter = "
        (billdate IS NULL OR billdate = '' OR billdate = '-')
        AND (mobile IS NULL OR mobile = '' OR mobile = '-' OR mobile NOT LIKE '%/%')
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
                   SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)   -- ✅ แก้ -8 → -6
                 ) > '$target_ym'

            ELSE TRUE
        END
    ";
}

// age expression
$ageOPD = "TIMESTAMPDIFF(MONTH,
    STR_TO_DATE(
        CONCAT(SUBSTR(vstdate,1,6), CAST(SUBSTR(vstdate,7,4) AS SIGNED)-543),
        '%d/%m/%Y'),
    LAST_DAY('$gmm'))";

$ageIPD = "TIMESTAMPDIFF(MONTH,
    STR_TO_DATE(
        CONCAT(SUBSTR(dchdate,1,6), CAST(SUBSTR(dchdate,7,4) AS SIGNED)-543),
        '%d/%m/%Y'),
    LAST_DAY('$gmm'))";


$sql_aging_debt = "
SELECT
    SUM(sum_lt_3)  AS debt_90_days,
    SUM(sum_3_12)  AS debt_91_days_to_1_year,
    SUM(sum_gt_12) AS debt_over_1_year
FROM (

    /* ==== OPD ==== */
    SELECT
        SUM(CASE WHEN $ageOPD <  3                   THEN $netDebitOPD ELSE 0 END) AS sum_lt_3,
        SUM(CASE WHEN $ageOPD >= 3 AND $ageOPD <= 12 THEN $netDebitOPD ELSE 0 END) AS sum_3_12,
        SUM(CASE WHEN $ageOPD >  12                  THEN $netDebitOPD ELSE 0 END) AS sum_gt_12
    FROM imr_tb_debtor_rights_opd
    WHERE IFNULL(debit,0) > 0
      AND $monthtxtCond
      AND ($receiptFilter)

    UNION ALL

    /* ==== IPD ==== */
    SELECT
        SUM(CASE WHEN $ageIPD <  3                   THEN $netDebitIPD ELSE 0 END) AS sum_lt_3,
        SUM(CASE WHEN $ageIPD >= 3 AND $ageIPD <= 12 THEN $netDebitIPD ELSE 0 END) AS sum_3_12,
        SUM(CASE WHEN $ageIPD >  12                  THEN $netDebitIPD ELSE 0 END) AS sum_gt_12
    FROM imr_tb_debtor_rights_ipd
    WHERE IFNULL(debit,0) > 0
      AND $monthtxtCond
      AND ($receiptFilter)


) AS main_query
";
    
// รัน Query
$result = $conn->query($sql_aging_debt);

// ตรวจผลลัพธ์
if ($result && $row = $result->fetch_assoc()) {
    $debt_90_days = number_format($row['debt_90_days'], 2);
    $debt_91_days_to_1_year = number_format($row['debt_91_days_to_1_year'], 2);
    $debt_over_1_year = number_format($row['debt_over_1_year'], 2);
} else {
    // กำหนดค่าเริ่มต้นเป็น 0 หากเกิดข้อผิดพลาด
    $debt_90_days = '0.00';
    $debt_91_days_to_1_year = '0.00';
    $debt_over_1_year = '0.00';
    
    // พี่ครับ ถ้ามี Error ในช่วง Dev ลองเปิดบรรทัดนี้เพื่อเช็คดูนะครับ จะได้ไม่ต้องเดา
    // error_log("SQL Error: " . $conn->error); 
}


// =================================================================
// ส่วนที่ 1: ตั้งต้นจาก imr_tb_debtor_result แล้วเอา code ไปเช็คในตารางสิทธิ OPD / IPD
// (เพิ่มการแปลง Text เป็น Decimal และตัดลูกน้ำออกก่อนทำการ SUM)
// =================================================================

$target_month = $dmonth; // ใช้เดือนปัจจุบันจาก Session

$sql_totals = "
    SELECT 
        SUM(CASE 
            WHEN opd.accountcode IS NOT NULL 
            THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) 
            ELSE 0 
        END) AS total_opd,
        
        SUM(CASE 
            WHEN ipd.accountcode IS NOT NULL AND opd.accountcode IS NULL 
            THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) 
            ELSE 0 
        END) AS total_ipd,
        
        /* ยอดที่หาไม่พบในตารางสิทธิ */
        SUM(CASE 
            WHEN opd.accountcode IS NULL AND ipd.accountcode IS NULL 
            THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) 
            ELSE 0 
        END) AS total_unmapped

    FROM imr_tb_debtor_result r
    
    LEFT JOIN (
        SELECT DISTINCT accountcode 
        FROM imr_tb_debtor_rights_opd 
        WHERE accountcode IS NOT NULL AND accountcode != ''
    ) opd ON r.code = opd.accountcode
    
    LEFT JOIN (
        SELECT DISTINCT accountcode 
        FROM imr_tb_debtor_rights_ipd 
        WHERE accountcode IS NOT NULL AND accountcode != ''
    ) ipd ON r.code = ipd.accountcode
    
    WHERE r.month = '$target_month'
";

$res_totals = $conn->query($sql_totals);
$row_totals = $res_totals->fetch_assoc();

$total_opd_numeric = $row_totals['total_opd'] ?? 0;
$total_ipd_numeric = $row_totals['total_ipd'] ?? 0;
$total_unmapped_numeric = $row_totals['total_unmapped'] ?? 0;

// แปลงฟอร์แมตให้มีลูกน้ำเพื่อเอาไปแสดงผล
$total_opd = number_format($total_opd_numeric, 2);
$total_ipd = number_format($total_ipd_numeric, 2);
$total_unmapped = number_format($total_unmapped_numeric, 2);

// =================================================================
// ส่วนที่ 2: คำนวณสัดส่วน % สำหรับ Progress Bar
// =================================================================
$carry_forward_num = (float)str_replace(',', '', $dpavartxt10);

$pct_opd = 0;
$pct_ipd = 0;
$pct_unmapped = 0;

if ($carry_forward_num > 0) {
    $pct_opd = ($total_opd_numeric / $carry_forward_num) * 100;
    $pct_ipd = ($total_ipd_numeric / $carry_forward_num) * 100;
    $pct_unmapped = ($total_unmapped_numeric / $carry_forward_num) * 100;
}

?>


<html lang="th"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <style>
        /* ==========================================================================
           eDHS System Font: Noto Sans Thai
           ========================================================================== */
        body, 
        .container,
        table, th, td, 
        input, button, select, textarea, 
        label, h1, h2, h3, h4, h5, h6, 
        p, a, span, small, strong, b, div,
        .swal2-popup, .swal2-popup * {
            font-family: 'Noto Sans Thai', sans-serif !important;
        }

        /* ป้องกันฟอนต์ไอคอน Boxicons, Font Awesome และ Iconify ไม่ให้ถูกทับ */
        .bx, .bxs, .bxl, [class^="bx-"], [class*=" bx-"], 
        .bx:before, .bxs:before, .bxl:before, 
        i[class*="bx-"], i[class*="bx-"]:before {
            font-family: 'boxicons' !important;
        }

        .fa, .fas, .far, .fal, .fad, .fab, 
        .fa:before, .fas:before, .far:before, .fal:before, .fad:before, .fab:before, 
        i[class*="fa-"], i[class*="fa-"]:before {
            font-family: 'Font Awesome 5 Free' !important;
        }
        .fab, .fab:before {
            font-family: 'Font Awesome 5 Brands' !important;
        }
        iconify-icon {
            font-family: unset;
        }
        
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent1: #4cc9f0;
            --accent2: #4895ef;
            --accent3: #560bad;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        .bg-primary {
            background-color: var(--primary);
        }
        
        .bg-secondary {
            background-color: var(--secondary);
        }
        
        .bg-accent1 {
            background-color: var(--accent1);
        }
        
        .bg-accent2 {
            background-color: var(--accent2);
        }
        
        .bg-accent3 {
            background-color: var(--accent3);
        }
        
        .text-primary {
            color: var(--primary);
        }
        
        .text-secondary {
            color: var(--secondary);
        }
        
        .text-accent1 {
            color: var(--accent1);
        }
        
        .text-accent2 {
            color: var(--accent2);
        }
        
        .text-accent3 {
            color: var(--accent3);
        }
        
        .border-primary {
            border-color: var(--primary);
        }
        
        .number-highlight {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .card {
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .gradient-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .gradient-footer {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .table-row-hover:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .highlight-row {
            background-color: rgba(67, 97, 238, 0.1);
        }
        
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% {
                left: -100%;
                top: -100%;
            }
            100% {
                left: 100%;
                top: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .number-highlight {
                font-size: 1.2rem;
            }
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }
        
        .icon-bg {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-download-dashboard {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: #ffffff !important;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 22px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            border: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        .btn-download-dashboard:hover {
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
            color: #ffffff !important;
        }
    </style>
<style>*, ::before, ::after{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }/* ! tailwindcss v3.4.16 | MIT License | https://tailwindcss.com */*,::after,::before{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}::after,::before{--tw-content:''}:host,html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;font-family:ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,pre,samp{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,input:where([type=button]),input:where([type=reset]),input:where([type=submit]){-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dd,dl,figure,h1,h2,h3,h4,h5,h6,hr,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}menu,ol,ul{list-style:none;margin:0;padding:0}dialog{padding:0}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}[role=button],button{cursor:pointer}:disabled{cursor:default}audio,canvas,embed,iframe,img,object,svg,video{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]:where(:not([hidden=until-found])){display:none}.container{width:100%}@media (min-width: 640px){.container{max-width:640px}}@media (min-width: 768px){.container{max-width:768px}}@media (min-width: 1024px){.container{max-width:1024px}}@media (min-width: 1280px){.container{max-width:1280px}}@media (min-width: 1536px){.container{max-width:1536px}}.m-4{margin:1rem}.mx-auto{margin-left:auto;margin-right:auto}.mb-2{margin-bottom:0.5rem}.mb-3{margin-bottom:0.75rem}.mb-4{margin-bottom:1rem}.mb-8{margin-bottom:2rem}.ml-2{margin-left:0.5rem}.ml-3{margin-left:0.75rem}.mr-4{margin-right:1rem}.flex{display:flex}.grid{display:grid}.h-12{height:3rem}.h-2\.5{height:0.625rem}.h-20{height:5rem}.h-6{height:1.5rem}.h-8{height:2rem}.h-full{height:100%}.w-12{width:3rem}.w-20{width:5rem}.w-6{width:1.5rem}.w-8{width:2rem}.w-full{width:100%}.min-w-full{min-width:100%}.max-w-6xl{max-width:72rem}.flex-grow{flex-grow:1}.grid-cols-1{grid-template-columns:repeat(1, minmax(0, 1fr))}.flex-col{flex-direction:column}.items-center{align-items:center}.justify-center{justify-content:center}.justify-between{justify-content:space-between}.gap-6{gap:1.5rem}.gap-8{gap:2rem}.overflow-hidden{overflow:hidden}.overflow-x-auto{overflow-x-auto}.rounded-full{border-radius:9999px}.rounded-lg{border-radius:0.5rem}.rounded-xl{border-radius:0.75rem}.rounded-b-lg{border-bottom-right-radius:0.5rem;border-bottom-left-radius:0.5rem}.rounded-tl-lg{border-top-left-radius:0.5rem}.rounded-tr-lg{border-top-right-radius:0.5rem}.border-b{border-bottom-width:1px}.border-l-4{border-left-width:4px}.bg-gray-200{--tw-bg-opacity:1;background-color:rgb(229 231 235 / var(--tw-bg-opacity, 1))}.bg-gray-50{--tw-bg-opacity:1;background-color:rgb(249 250 251 / var(--tw-bg-opacity, 1))}.bg-white{--tw-bg-opacity:1;background-color:rgb(255 255 255 / var(--tw-bg-opacity, 1))}.bg-white\/10{background-color:rgb(255 255 255 / 0.1)}.bg-gradient-to-br{background-image:linear-gradient(to bottom right, var(--tw-gradient-stops))}.p-3{padding:0.75rem}.p-4{padding:1rem}.p-5{padding:1.25rem}.p-6{padding:1.5rem}.px-4{padding-left:1rem;padding-right:1rem}.py-3{padding-top:0.75rem;padding-bottom:0.75rem}.py-8{padding-top:2rem;padding-bottom:2rem}.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}.text-2xl{font-size:1.5rem;line-height:2rem}.text-3xl{font-size:1.875rem;line-height:2.25rem}.text-lg{font-size:1.125rem;line-height:1.75rem}.text-sm{font-size:0.875rem;line-height:1.25rem}.text-xl{font-size:1.25rem;line-height:1.75rem}.font-bold{font-weight:700}.font-medium{font-weight:500}.font-semibold{font-weight:600}.text-gray-500{--tw-text-opacity:1;color:rgb(107 114 128 / var(--tw-text-opacity, 1))}.text-gray-600{--tw-text-opacity:1;color:rgb(75 85 99 / var(--tw-text-opacity, 1))}.text-gray-700{--tw-text-opacity:1;color:rgb(55 65 81 / var(--tw-text-opacity, 1))}.text-gray-800{--tw-text-opacity:1;color:rgb(31 41 55 / var(--tw-text-opacity, 1))}.text-white{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.opacity-90{opacity:0.9}.shadow-lg{--tw-shadow:0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);--tw-shadow-colored:0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -4px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}@media (min-width: 768px){.md\:mb-0{margin-bottom:0px}.md\:grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}.md\:grid-cols-3{grid-template-columns:repeat(3, minmax(0, 1fr))}.md\:flex-row{flex-direction:row}.md\:text-right{text-align:right}.md\:text-3xl{font-size:1.875rem;line-height:2.25rem}}@media (min-width: 1024px){.lg\:col-span-1{grid-column:span 1 / span 1}.lg\:col-span-2{grid-column:span 2 / span 2}.lg\:grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}.lg\:grid-cols-3{grid-template-columns:repeat(3, minmax(0, 1fr))}.lg\:grid-cols-4{grid-template-columns:repeat(4, minmax(0, 1fr))}}</style></head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="text-right" style="padding-right: 20px; margin-bottom: 8px;"> 
            <button type="button" class="btn-download-dashboard" onclick="downloadDashboardAsImage()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                ดาวน์โหลดรูปภาพ Dashboard
            </button>
        </div>
        
        <div id="dashboardContent" style="padding: 20px;">

            <header class="card gradient-header text-white p-6 mb-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center mb-4 md:mb-0">
                        <div class="bg-white p-3 rounded-full mr-4">
                            <img src="./images/logo.gif" style="width:100px">
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold"><?php echo $hospital; ?></h1>
                            <p class="text-lg opacity-90">ทะเบียนคุมลูกหนี้สิทธิ</p>
                        </div>
                    </div>
                    <div class="text-center md:text-right bg-white/10 p-4 rounded-lg">
                        <p class="text-xl font-semibold">ปีงบประมาณ <?php echo $fiscalYearThai; ?></p>
                        <p class="text-lg">เดือน <?php echo $monthNow; ?></p>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="card bg-white p-5 stat-card border-l-4 border-primary flex flex-col justify-between">
                    <div>
                        <div class="flex items-center mb-3">
                            <div class="icon-bg bg-primary/10">
                                <svg class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"></path>
                                    <path d="M14 17H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"></path>
                                </svg>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-gray-800">ลูกหนี้สิทธิยกมา</h3>
                        </div>
                        <p class="number-highlight"><?php echo $dpavartxt1; ?> บาท</p>
                        <p class="text-gray-500 text-sm mb-2">เดือน <?php echo $monthLast; ?></p>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-600 space-y-1">
                        <div class="flex justify-between"><span>OPD (ผู้ป่วยนอก):</span><span class="font-medium text-gray-800"><?php echo $opd_txt1; ?> บ.</span></div>
                        <div class="flex justify-between"><span>IPD (ผู้ป่วยใน):</span><span class="font-medium text-gray-800"><?php echo $ipd_txt1; ?> บ.</span></div>
                    </div>
                </div>
                
                <div class="card bg-white p-5 stat-card border-l-4 border-accent2 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center mb-3">
                            <div class="icon-bg bg-accent2/10">
                                <svg class="w-6 h-6 text-accent2" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path>
                                </svg>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-gray-800">ลูกหนี้สิทธิ</h3>
                        </div>
                        <p class="number-highlight text-accent2"><?php echo $dpavartxt3; ?> บาท</p>
                        <p class="text-gray-500 text-sm mb-2">เดือน <?php echo $monthNow; ?></p>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-600 space-y-1">
                        <div class="flex justify-between"><span>OPD (ผู้ป่วยนอก):</span><span class="font-medium text-gray-800"><?php echo $opd_txt3; ?> บ.</span></div>
                        <div class="flex justify-between"><span>IPD (ผู้ป่วยใน):</span><span class="font-medium text-gray-800"><?php echo $ipd_txt3; ?> บ.</span></div>
                    </div>
                </div>
                
                <div class="card bg-white p-5 stat-card border-l-4 border-accent3 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center mb-3">
                            <div class="icon-bg bg-accent3/10">
                                <svg class="w-6 h-6 text-accent3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"></path>
                                </svg>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-gray-800">ตัดลูกหนี้</h3>
                        </div>
                        <p class="number-highlight text-accent3"><?php echo $dpavartxt7; ?> บาท</p>
                        <p class="text-gray-500 text-sm mb-2">เดือน <?php echo $monthNow; ?></p>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-600 space-y-1">
                        <div class="flex justify-between"><span>OPD (ผู้ป่วยนอก):</span><span class="font-medium text-gray-800"><?php echo $opd_txt7; ?> บ.</span></div>
                        <div class="flex justify-between"><span>IPD (ผู้ป่วยใน):</span><span class="font-medium text-gray-800"><?php echo $ipd_txt7; ?> บ.</span></div>
                    </div>
                </div>
                
                <div class="card bg-white p-5 stat-card border-l-4 border-accent1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center mb-3">
                            <div class="icon-bg bg-accent1/10">
                                <svg class="w-6 h-6 text-accent1" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"></path>
                                </svg>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-gray-800">ลูกหนี้สิทธิยกไป</h3>
                        </div>
                        <p class="number-highlight text-accent1"><?php echo $dpavartxt10; ?> บาท</p>
                        <p class="text-gray-500 text-sm mb-2">เดือน <?php echo $monthNow; ?></p>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-600 space-y-1">
                        <div class="flex justify-between"><span>OPD (ผู้ป่วยนอก):</span><span class="font-medium text-gray-800"><?php echo $opd_txt10; ?> บ.</span></div>
                        <div class="flex justify-between"><span>IPD (ผู้ป่วยใน):</span><span class="font-medium text-gray-800"><?php echo $ipd_txt10; ?> บ.</span></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 items-stretch">
                <div class="lg:col-span-2 h-full">
                    <div class="card bg-white h-full flex flex-col overflow-hidden">
                        <div class="bg-primary/10 p-4">
                            <div class="flex items-center">
                                <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"></path>
                                </svg>
                                <h2 class="text-xl font-bold ml-2 text-gray-800">รายงานสรุปลูกหนี้สิทธิ</h2>
                            </div>
                        </div>
                        <div class="overflow-x-auto p-4 flex-grow">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th class="py-3 px-4 text-left rounded-tl-lg">ลำดับ</th>
                                        <th class="py-3 px-4 text-left">รายการ</th>
                                        <th class="py-3 px-4 text-right rounded-tr-lg">จำนวนเงิน (บาท)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b table-row-hover">
                                        <td class="py-3 px-4 text-gray-800">1</td>
                                        <td class="py-3 px-4 text-gray-800">ลูกหนี้สิทธิยกมาเดือน <?php echo $monthLast; ?> </td>
                                        <td class="py-3 px-4 text-right font-medium text-gray-800" ><?php echo $dpavartxt1; ?></td>
                                    </tr>
                                    <tr class="border-b table-row-hover">
                                        <td class="py-3 px-4 text-gray-800">2</td>
                                        <td class="py-3 px-4 text-gray-800">จำนวนลูกหนี้/ราย เดือน <?php echo $monthNow; ?></td>
                                        <td class="py-3 px-4 text-right font-medium text-gray-800"><?php echo $dpavartxt2; ?> คน</td>
                                    </tr>
                                    <tr class="border-b table-row-hover">
                                        <td class="py-3 px-4 text-gray-800">3</td>
                                        <td class="py-3 px-4 text-gray-800">ลูกหนี้สิทธิ กลุ่มงานประกันสุขภาพ/บาท</td>
                                        <td class="py-3 px-4 text-right font-medium text-gray-800"><?php echo $dpavartxt3; ?></td>
                                    </tr>
                                    <tr class="border-b table-row-hover">
                                        <td class="py-3 px-4 text-gray-800">4</td>
                                        <td class="py-3 px-4 text-gray-800">งบทดลอง กลุ่มงานบัญชี/บาท</td>
                                        <td class="py-3 px-4 text-right font-medium text-gray-800"><?php echo $dpavartxt4; ?></td>
                                    </tr>
                                    <tr class="border-b table-row-hover">
                                        <td class="py-3 px-4 text-gray-800">5</td>
                                        <td class="py-3 px-4 text-gray-800">ยอดส่วนต่าง</td>
                                        <td class="py-3 px-4 text-right font-medium text-gray-800"><?php echo $dpavartxt5; ?></td>
                                    </tr>
                                    <tr class="border-b table-row-hover">
                                        <td class="py-3 px-4 text-gray-800">6</td>
                                        <td class="py-3 px-4 text-gray-800">ตัดลูกหนี้/บาท</td>
                                        <td class="py-3 px-4 text-right font-medium text-gray-800"><?php echo $dpavartxt7; ?></td>
                                    </tr>
                                    <tr class="border-b table-row-hover">
                                        <td class="py-3 px-4 text-gray-800">7</td>
                                        <td class="py-3 px-4 text-gray-800">เงินโอนในเดือน<?php echo $monthNow; ?></td>
                                        <td class="py-3 px-4 text-right font-medium text-gray-800"><?php echo $dpavartxt6; ?></td>
                                    </tr>
                                    <tr class="highlight-row rounded-b-lg">
                                        <td class="py-3 px-4 text-gray-800 font-bold">8</td>
                                        <td class="py-3 px-4 font-bold text-gray-800">ลูกหนี้สิทธิยกไป</td>
                                        <td class="py-3 px-4 text-right font-bold text-primary"><?php echo $dpavartxt10; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <div class="lg:col-span-1 h-full">
                    <div class="card bg-white h-full flex flex-col">
                        <div class="bg-accent3/10 p-4">
                            <div class="flex items-center">
                                <svg class="w-8 h-8 text-accent3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"></path>
                                </svg>
                                <h2 class="text-xl font-bold ml-2 text-gray-800">สรุปลูกหนี้สิทธิ</h2>
                            </div>
                        </div>

                        <div class="flex-grow flex flex-col items-center justify-center text-center p-6 bg-gradient-to-br from-accent3/5 to-primary/5 m-4 rounded-xl" style="padding-bottom: 0; padding-top: 0; margin-bottom: 0;">
                            <div class="bg-white p-4 rounded-full shadow-lg mb-4">
                                <svg class="w-20 h-20 text-accent3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-2 text-gray-800">ลูกหนี้สิทธิยกไป</h3>
                            <p class="text-3xl font-bold text-accent3 mb-2"><?php echo $dpavartxt10; ?> บาท</p>
                        </div>

                        <div class="p-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-gray-700">OPD(ผู้ป่วยนอก):</span>
                                <span class="font-medium text-gray-700"><?php echo $total_opd; ?> บาท</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                                <div class="bg-primary progress-bar" style="width: <?php echo $pct_opd; ?>%;"></div>
                            </div>

                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-gray-700">IPD(ผู้ป่วยใน):</span>
                                <span class="font-medium text-gray-700"><?php echo $total_ipd; ?> บาท</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-accent3 progress-bar" style="width: <?php echo $pct_ipd; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="card bg-white overflow-hidden">
                    <div class="bg-accent2/10 p-4">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-accent2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"></path>
                            </svg>
                            <h2 class="text-xl font-bold ml-2 text-gray-800">เปรียบเทียบย้อนหลัง 5 เดือน</h2>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="chart-container">
                            <canvas id="comparisonChart" width="512" height="300" style="display: block; box-sizing: border-box; height: 300px; width: 512px;"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-white overflow-hidden">
                    <div class="bg-accent1/10 p-4">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-accent1" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"></path>
                            </svg>
                            <h2 class="text-xl font-bold ml-2 text-gray-800">ลูกหนี้สิทธิยกไป ย้อนหลัง 5 เดือน</h2>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="chart-container">
                            <canvas id="debtChart" width="512" height="300" style="display: block; box-sizing: border-box; height: 300px; width: 512px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                
                <div class="card bg-white p-6 flex flex-col items-center justify-center text-center cursor-pointer" 
                     style="border-left: 0.55rem solid #f99715 !important;"
                     onclick="openAgingModal('<= 3', 'มูลค่าลูกหนี้ 90 วัน (โดยประมาณ)')"> <h3 class="text-lg font-semibold text-gray-800 mb-2">มูลค่าลูกหนี้ 90 วัน</h3>
                    <p class="text-3xl font-bold text-gray-800 mb-2" style="color: #f99715;"><?php echo $debt_90_days; ?> บาท</p>
                    <span style="color: #bababa;">(โดยประมาณ)</span>
                </div>
                
                <div class="card bg-white p-6 flex flex-col items-center justify-center text-center cursor-pointer"
                     onclick="openAgingModal('> 3 AND <= 12', 'มูลค่าลูกหนี้ 90 วัน ไม่เกิน 1 ปี (โดยประมาณ)')"> <h3 class="text-lg font-semibold text-gray-800 mb-2">มูลค่าลูกหนี้ 90 วัน ไม่เกิน 1 ปี</h3>
                    <p class="text-3xl font-bold text-gray-800 mb-2" style="color: #ff5c1b;"><?php echo $debt_91_days_to_1_year; ?> บาท</p>
                    <span style="color: #bababa;">(โดยประมาณ)</span>
                </div>

                <div class="card bg-white p-6 flex flex-col items-center justify-center text-center cursor-pointer"
                     onclick="openAgingModal('> 12', 'มูลค่าลูกหนี้เกิน 1 ปี (โดยประมาณ)')"> <h3 class="text-lg font-semibold text-gray-800 mb-2">มูลค่าลูกหนี้เกิน 1 ปี </h3>
                    <p class="text-3xl font-bold text-gray-800 mb-2" style="color: #f92805;"><?php echo $debt_over_1_year; ?> บาท</p>
                    <span style="color: #bababa;">(โดยประมาณ)</span>
                </div>

            </div>

            <footer class="card gradient-footer text-white p-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <h3 class="text-xl font-bold"><?php echo $hospital; ?></h3>
                        <p class="opacity-90">รายงานทะเบียนคุมลูกหนี้สิทธิ ปีงบประมาณ <?php echo $fiscalYearThai; ?></p>
                    </div>
                </div>
            </footer>

            <div id="agingDebtModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/5 shadow-lg rounded-md bg-white">

                    <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                        <h3 id="modalTitle" class="text-xl font-bold text-gray-900"></h3>
                        <div class="flex items-center space-x-2"> 
                            <button 
                                id="exportDetailBtn" 
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 text-sm rounded transition duration-150 ease-in-out"
                                onclick="exportAgingDetailToExcel()"
                                style="display: none;">
                                ส่งออกเป็น Excel 📁
                            </button>
                            <button class="text-gray-400 hover:text-gray-600" onclick="closeModal('agingDebtModal')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 max-h-96 overflow-y-auto" style="max-height: 70%;">
                        <div id="modalContent" class="text-gray-600">
                            กำลังโหลดข้อมูล...
                        </div>
                    </div>
                    
                    <div id="patientDetailSection" class="mt-6 pt-4 border-t border-gray-200 hidden">
                        <h4 id="detailTitle" class="text-lg font-semibold text-gray-800 mb-3">รายชื่อคนไข้ (Drill-down)</h4>
                        <div id="patientDetailTable" class="max-h-80 overflow-y-auto" style="max-height: 70%;">
                            เลือกสิทธิเพื่อดูรายละเอียด...
                        </div>
                        <button class="mt-3 text-sm text-blue-500 hover:text-blue-700" onclick="closeDetailSection()">
                            ← กลับสู่ตารางสรุปสิทธิ
                        </button>
                    </div>

                </div>
            </div>

            <script>
                /**
             * ฟังก์ชันเปิด Modal และเรียก AJAX เพื่อดึงข้อมูลสรุปตาม Account Name
             * @param {string} ageCondition - เงื่อนไขอายุหนี้ (e.g., '<= 90')
             * @param {string} title - ชื่อหัวข้อ Modal (e.g., 'มูลค่าลูกหนี้ 90 วัน')
             */
            function openAgingModal(ageCondition, title) {
                // 1. กำหนดหัวข้อและเปิด Modal
                document.getElementById('modalTitle').innerText = title;
                document.getElementById('agingDebtModal').classList.remove('hidden');

                // 2. ตั้งค่าสถานะเริ่มต้น (State Reset)
                // ซ่อนส่วน Detail และปุ่ม Export
                document.getElementById('patientDetailSection').classList.add('hidden');
                
                // 🚨 สำคัญ: ต้องใช้ .style.display เพราะปุ่ม Export ถูกควบคุมด้วย style="display: none;"
                const exportBtn = document.getElementById('exportDetailBtn');
                if (exportBtn) {
                    exportBtn.style.display = 'none'; 
                }
                
                // 3. แสดงส่วน Summary (#modalContent) และแสดงสถานะกำลังโหลด
                // 🚨 แก้ปัญหาข้อมูลหาย: ต้องลบ hidden class ออกก่อน
                document.getElementById('modalContent').classList.remove('hidden'); 
                document.getElementById('modalContent').innerHTML = '<div class="text-center py-4 text-blue-500 font-medium">กำลังโหลดข้อมูล... โปรดรอสักครู่</div>';

                // 4. เรียกฟังก์ชัน AJAX
                // **(เราต้องมาสร้าง/ทบทวนฟังก์ชันนี้ในขั้นตอนต่อไป)**
                fetchAccountSummary(ageCondition); 
            }

            /**
             * ฟังก์ชันปิด Modal ทั่วไป
             */
            function closeModal(id) {
                document.getElementById(id).classList.add('hidden');
            }

            /**
             * ฟังก์ชัน AJAX เพื่อดึงข้อมูลสรุปตาม Account Name
             * @param {string} ageCondition - เงื่อนไขอายุหนี้
             */
            function fetchAccountSummary(ageCondition) {
                const data = new URLSearchParams();
                data.append('action', 'summary');
                data.append('cal_date', cal_date); // ใช้ตัวแปร PHP ที่ถูก Inject เข้ามา
                data.append('age_condition', ageCondition);
                data.append('dmonth', '<?php echo $dmonth; ?>');

                fetch('fetch_aging_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('modalContent').innerHTML = data.html_table;
                    } else {
                        document.getElementById('modalContent').innerHTML = '<div class="text-red-600">Error: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    document.getElementById('modalContent').innerHTML = '<div class="text-red-600">เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์.</div>';
                });
            }

            /**
             * ฟังก์ชัน AJAX สำหรับ Drill-down ดึงรายละเอียดคนไข้
             * @param {string} accountName - ชื่อสิทธิที่ถูกคลิก
             * @param {string} ageCondition - เงื่อนไขอายุหนี้
             */

            function openPatientDrilldown(accountname, ageCondition) {
                // 🚨 เตือน: ต้องมี showLoadingSwal และ Swal.close() เพื่อให้รู้ว่าระบบกำลังทำงาน
                //showLoadingSwal(`กำลังโหลดรายละเอียด`, `สิทธิ: ${accountname}...`);

                // **จุด 1: แก้ไข** ใช้ $cal_date จาก PHP โดยตรง (ตรงตามที่พี่ให้มา)
                const calDate = '<?php echo $cal_date; ?>';
                
                // **จุด 2: แก้ไข** ดึงหัวข้อ Modal สรุปมาใช้ด้วย Vanilla JS
                const summaryModalTitle = document.getElementById('modalTitle').innerText;
                
                // 1. **จุด 3: แก้ไข** ซ่อนตารางสรุปทันทีที่เริ่มโหลดด้วย classList.add('hidden')
                document.getElementById('modalContent').classList.add('hidden');
                
                $.ajax({
                    url: 'fetch_aging_data.php',
                    type: 'POST',
                    data: {
                        action: 'detail',
                        cal_date: calDate,
                        age_condition: ageCondition,
                        accountname: accountname,
                        dmonth: '<?php echo $dmonth; ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            
                            // 2. **จุด 4: แก้ไข** อัปเดต Title ของ Detail Section ด้วย innerText
                            document.getElementById('detailTitle').innerText = `รายชื่อคนไข้ของสิทธิ: ${accountname} (${summaryModalTitle})`;
                            
                            // 3. **จุด 5: แก้ไข** ใส่ตารางลงใน Patient Detail Table ด้วย innerHTML
                            document.getElementById('patientDetailTable').innerHTML = response.html_table;

                            // 4. **จุด 6: แก้ไข** แสดงส่วนรายละเอียด (Detail Section) ด้วย classList.remove('hidden')
                            document.getElementById('patientDetailSection').classList.remove('hidden');

                            // 🌟 ขั้นตอนสำคัญ: เก็บ SQL Query และ Title ในปุ่ม Export
                            // คง jQuery ไว้สำหรับการใช้ .data() ซึ่งสะดวกกว่า Vanilla JS
                            const exportBtn = $('#exportDetailBtn'); 
                            exportBtn.data('sql-query', response.sql);
                            exportBtn.data('export-title', `ลูกหนี้_${accountname}_${summaryModalTitle.replace(/[^a-zA-Z0-9ก-๙]/g, '_')}`);
                            
                            // 5. **จุด 7: แก้ไข** แสดงปุ่ม Export ด้วย style.display
                            document.getElementById('exportDetailBtn').style.display = 'block'; 
                            
                        } else {
                            Swal.fire('ข้อผิดพลาด!', response.message, 'error');
                            // 6. **จุด 8: แก้ไข** หาก error ให้แสดง Summary กลับมา
                            document.getElementById('modalContent').classList.remove('hidden'); // แสดง Summary
                            document.getElementById('patientDetailSection').classList.add('hidden'); // ซ่อน Detail
                            document.getElementById('exportDetailBtn').style.display = 'none'; // ซ่อนปุ่ม Export
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('ข้อผิดพลาดทางเทคนิค!', 'ไม่สามารถดึงรายละเอียดลูกหนี้ได้', 'error');
                        // 7. **จุด 8: แก้ไข** หาก error ให้แสดง Summary กลับมา
                        document.getElementById('modalContent').classList.remove('hidden'); // แสดง Summary
                        document.getElementById('patientDetailSection').classList.add('hidden'); // ซ่อน Detail
                        document.getElementById('exportDetailBtn').style.display = 'none'; // ซ่อนปุ่ม Export
                    },
                    complete: function() {
                        Swal.close();
                    }
                });
            }

            function closeDetailSection() {
                // ซ่อนส่วนรายละเอียด
                document.getElementById('patientDetailSection').classList.add('hidden');
                
                // 🌟 ต้องแน่ใจว่าส่วน Summary กลับมาแสดงอย่างชัดเจน
                document.getElementById('modalContent').classList.remove('hidden');
                
                // ซ่อนปุ่ม Export
                document.getElementById('exportDetailBtn').style.display = 'none'; 
            }

            function showLoadingSwal(title = 'กำลังโหลด', text = 'โปรดรอสักครู่...') {
                // 🚨 เตือน: ต้องแน่ใจว่าได้ include SweetAlert2 library ก่อนถึงจะใช้ Swal.fire ได้
                Swal.fire({
                    title: title,
                    text: text,
                    allowOutsideClick: false, // ป้องกันการปิดด้วยการคลิกนอกหน้าต่าง
                    showConfirmButton: false, // ไม่แสดงปุ่มยืนยัน
                    willOpen: () => {
                        Swal.showLoading(); // แสดง Icon โหลด
                    }
                });
            }



            function exportAgingDetailToExcel() {
                
                // 1. ดึง Element เป้าหมาย
                const detailTableContainer = document.getElementById('patientDetailTable');
                // ต้องหาแท็ก <table> ภายใน container ของรายละเอียดคนไข้
                let table = detailTableContainer.querySelector('table'); 
                
                if (!table) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่พบตารางข้อมูลสำหรับส่งออก', 'warning');
                    return;
                }
                
                // 2. ดึงชื่อไฟล์ที่บันทึกไว้จากปุ่ม (จากฟังก์ชัน openPatientDrilldown)
                const exportBtn = document.getElementById('exportDetailBtn');
                // ใช้ getAttribute แทน .data() ของ jQuery (ถ้าพี่ใช้ Vanilla JS)
                // แต่เนื่องจากใน openPatientDrilldown เราใช้ jQuery.data() เราจะใช้ jQuery ต่อในส่วนนี้
                // (หากต้องการเปลี่ยนเป็น Vanilla JS ทั้งหมด เราต้องไปเปลี่ยน openPatientDrilldown ด้วย)
                
                // 💡 สมมติว่าพี่ต้องการคง jQuery ในส่วนนี้เพื่อใช้ .data() ที่เราได้ทำไว้แล้ว:
                const $exportBtn = $('#exportDetailBtn');
                const fileNameBase = $exportBtn.data('export-title') || 'AgingDetail'; 
                
                // 3. ประมวลผลและสร้าง Workbook
                let workbook = XLSX.utils.book_new();
                
                // 🌟 { raw: true } สำคัญมาก: เพื่อบังคับให้ข้อมูลทั้งหมดเป็น Text
                let worksheet = XLSX.utils.table_to_sheet(table, { raw: true }); 
                
                XLSX.utils.book_append_sheet(workbook, worksheet, "Patient Detail");
                
                // 4. สั่งดาวน์โหลดด้วยชื่อไฟล์ที่มี Timestamp
                const today = new Date().toISOString().slice(0, 10).replace(/-/g, '');
                XLSX.writeFile(workbook, `${fileNameBase}_${today}.xlsx`);
                
                // 5. ปิด Swal Loading (ถ้ามี) หรือแสดงข้อความสำเร็จ
                Swal.fire({ 
                    title: 'การส่งออกสำเร็จ!', 
                    text: 'โปรดตรวจสอบไฟล์ .xlsx ที่ดาวน์โหลด',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            }

            </script>


    </div>

</div>

    <script>
        // Animation for progress bars
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            setTimeout(() => {
                progressBars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 300);
                });
            }, 500);
        });

        var cal_date = '<?php echo $cal_date; ?>'; // ค่าที่ได้จาก PHP เช่น '2023-10-01'

        var m1 = '<?php echo $labels[0]; ?>';
        var m2 = '<?php echo $labels[1]; ?>';
        var m3 = '<?php echo $labels[2]; ?>';
        var m4 = '<?php echo $labels[3]; ?>';
        var m5 = '<?php echo $labels[4]; ?>';

        var v61 = parseFloat('<?php echo  $dpavartxt6; ?>'.replace(/,/g, ''));
        var v62 = parseFloat('<?php echo $adpavartxt6; ?>'.replace(/,/g, ''));
        var v63 = parseFloat('<?php echo $bdpavartxt6; ?>'.replace(/,/g, ''));
        var v64 = parseFloat('<?php echo $cdpavartxt6; ?>'.replace(/,/g, ''));
        var v65 = parseFloat('<?php echo $ddpavartxt6; ?>'.replace(/,/g, ''));


        var v71 = parseFloat('<?php echo  $dpavartxt7; ?>'.replace(/,/g, ''));
        var v72 = parseFloat('<?php echo $adpavartxt7; ?>'.replace(/,/g, ''));
        var v73 = parseFloat('<?php echo $bdpavartxt7; ?>'.replace(/,/g, ''));
        var v74 = parseFloat('<?php echo $cdpavartxt7; ?>'.replace(/,/g, ''));
        var v75 = parseFloat('<?php echo $ddpavartxt7; ?>'.replace(/,/g, ''));

        // ตั้งค่าฟอนต์ Noto Sans Thai สำหรับ Chart.js ทั้งหมด
        if (typeof Chart !== 'undefined' && Chart.defaults && Chart.defaults.font) {
            Chart.defaults.font.family = "'Noto Sans Thai', sans-serif";
        }

        // ข้อมูลสำหรับกราฟเปรียบเทียบ
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        const comparisonChart = new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: [m1, m2, m3, m4, m5],
                datasets: [
                    {
                        label: 'ตัดลูกหนี้/บาท',
                        data: [v75, v74, v73, v72, v71],
                        backgroundColor: '#560bad',
                        borderColor: '#560bad',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'เงินโอนในเดือนนี้',
                        data: [v65, v64, v63, v62, v61],
                        backgroundColor: '#4cc9f0',
                        borderColor: '#4cc9f0',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' บาท';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#333',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw.toLocaleString() + ' บาท';
                            }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });

        


        var vm1 = parseFloat('<?php echo $dpavartxt10; ?>'.replace(/,/g, ''));
        var vm2 = parseFloat('<?php echo $adpavartxt10; ?>'.replace(/,/g, ''));
        var vm3 = parseFloat('<?php echo $bdpavartxt10; ?>'.replace(/,/g, ''));
        var vm4 = parseFloat('<?php echo $cdpavartxt10; ?>'.replace(/,/g, ''));
        var vm5 = parseFloat('<?php echo $ddpavartxt10; ?>'.replace(/,/g, ''));


        // ข้อมูลสำหรับกราฟลูกหนี้สิทธิยกไป
        const debtCtx = document.getElementById('debtChart').getContext('2d');
        const debtChart = new Chart(debtCtx, {
            type: 'line',
            data: {
                labels: [m1, m2, m3, m4, m5],
                datasets: [{
                    label: 'ลูกหนี้สิทธิยกไป',
                    data: [vm5, vm4, vm3, vm2, vm1],
                    backgroundColor: 'rgba(67, 97, 238, 0.2)',
                    borderColor: '#4361ee',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4361ee',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: '#4361ee',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' บาท';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#333',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw.toLocaleString() + ' บาท';
                            }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });

        // **NEW: ฟังก์ชันดาวน์โหลด Dashboard เป็นรูปภาพ**
        function downloadDashboardAsImage() {
            // กำหนด Element ที่มี id="dashboardContent" เป็นเป้าหมายในการจับภาพ
            const dashboardElement = document.getElementById('dashboardContent');

            if (!dashboardElement) {
                alert('Error: ไม่พบส่วนประกอบ Dashboard ที่มี ID #dashboardContent');
                return;
            }

            // ใช้ html2canvas แปลง HTML เป็น Canvas
            html2canvas(dashboardElement, {
                scale: 2, // ปรับ Scale เป็น 2 เท่า เพื่อให้ภาพ PNG คมชัดขึ้น
                useCORS: true, // อนุญาตให้ดึงรูปภาพจากภายนอกมาใช้งาน (ถ้ามี)
                logging: false, // ปิดการ log ใน console
                // เพิ่มการจัดการขนาดหน้าจอเพื่อให้จับภาพได้ครบถ้วน
                windowWidth: dashboardElement.scrollWidth, 
                windowHeight: dashboardElement.scrollHeight,
            }).then(function(canvas) {
                // แปลง Canvas เป็น Data URL (PNG)
                const imageURL = canvas.toDataURL("image/png");

                // สร้าง Link ชั่วคราวเพื่อสั่งดาวน์โหลด
                const link = document.createElement('a');
                link.href = imageURL;
                
                // ตั้งชื่อไฟล์โดยใส่ วันที่ ปี-เดือน-วัน ลงไป
                const today = new Date().toISOString().slice(0, 10).replace(/-/g, ''); 
                link.download = `รายงานผู้บริหาร_Dashbord_${today}.png`; 
                
                // สั่งให้ดาวน์โหลด
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }).catch(err => {
                console.error("Error during html2canvas:", err);
                alert("เกิดข้อผิดพลาดในการดาวน์โหลดภาพ: " + err.message);
            });
        }
    </script>

</body></html>