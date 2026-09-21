<?php
// ตั้งค่าเพื่อป้องกันการเข้าถึงโดยตรง และเตรียมการเชื่อมต่อ
header('Content-Type: application/json; charset=utf-8');
session_start();
// ตรวจสอบชื่อไฟล์ Config ของพี่ด้วยนะครับ
require './database_config/config.php'; 

// 1. รับค่าที่ส่งมาจาก AJAX
$action = $_POST['action'] ?? '';
// $cal_date ที่ส่งมานี้คือ 'YYYY-MM-01' จาก dashboard.php (ใช้ในการคำนวณอายุหนี้)
$cal_date = $_POST['cal_date'] ?? ''; 
$age_condition = $_POST['age_condition'] ?? ''; // เช่น '<= 3' หรือ '> 3 AND <= 12'
$accountname = $_POST['accountname'] ?? '';
$dmonth = $_POST['dmonth'] ?? ''; 

if (empty($cal_date) || empty($age_condition) || empty($action)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit();
}

/**
 * 🚨 ปรับปรุง SQL สำหรับ MariaDB เวอร์ชันเก่า (No WITH Clause)
 * วิธีแก้: สร้าง string ของ Subquery (UNION ALL) เก็บไว้ในตัวแปร
 * แล้วเอาไปแปะใน FROM (...) AS Alias แทนการใช้ WITH
 */

// ส่วนของ Query ย่อย (ไส้ใน) ที่ดึงข้อมูล OPD และ IPD
// พี่สังเกตว่าผมเอาคำสั่ง WITH ออกแล้วนะครับ
$subquery_union = "
    -- OPD Data
    SELECT
        t.vn AS visit_id,
        t.hn,
        t.ptname,
        t.accountname,
        CAST(REPLACE(t.debit, ',', '') AS DECIMAL(15, 2)) AS debt_amount,
        -- แปลง 'DD/MM/BBBB' จาก vstdate เป็น Date Gregorian (YYYY-MM-DD)
        STR_TO_DATE(
            CONCAT(SUBSTR(t.vstdate, 1, 6), (CAST(SUBSTR(t.vstdate, 7, 4) AS SIGNED) - 543)), 
            '%d/%m/%Y'
        ) AS debt_date, 
        'OPD' AS type
    FROM imr_tb_debtor_rights_opd t
    WHERE
        (t.bill IS NULL OR t.bill = '') 
        AND (t.mobile IS NULL OR t.mobile = '-' OR t.mobile = '')
        AND t.debit IS NOT NULL AND t.debit != '' AND CAST(REPLACE(t.debit, ',', '') AS DECIMAL(15, 2)) > 0
        -- กรองด้วย vstdate (ที่แปลงแล้ว) <= วันที่คำนวณ
        AND STR_TO_DATE(CONCAT(SUBSTR(t.vstdate, 1, 6), (CAST(SUBSTR(t.vstdate, 7, 4) AS SIGNED) - 543)), '%d/%m/%Y') <= '{$cal_date}'

    UNION ALL

    -- IPD Data
    SELECT
        t.an AS visit_id,
        t.hn,
        t.ptname,
        t.accountname,
        CAST(REPLACE(t.debit, ',', '') AS DECIMAL(15, 2)) AS debt_amount,
        -- แปลง 'DD/MM/BBBB' จาก dchdate เป็น Date Gregorian (YYYY-MM-DD)
        STR_TO_DATE(
            CONCAT(SUBSTR(t.dchdate, 1, 6), (CAST(SUBSTR(t.dchdate, 7, 4) AS SIGNED) - 543)), 
            '%d/%m/%Y'
        ) AS debt_date,
        'IPD' AS type
    FROM imr_tb_debtor_rights_ipd t
    WHERE
        (t.bill IS NULL OR t.bill = '') 
        AND (t.mobile IS NULL OR t.mobile = '-' OR t.mobile = '')
        AND t.debit IS NOT NULL AND t.debit != '' AND CAST(REPLACE(t.debit, ',', '') AS DECIMAL(15, 2)) > 0
        -- กรองด้วย dchdate (ที่แปลงแล้ว) <= วันที่คำนวณ
        AND STR_TO_DATE(CONCAT(SUBSTR(t.dchdate, 1, 6), (CAST(SUBSTR(t.dchdate, 7, 4) AS SIGNED) - 543)), '%d/%m/%Y') <= '{$cal_date}'
";

$html_table = '';
$sql = '';

// 3. การแมปเงื่อนไขอายุหนี้จาก (เดือน) เป็น (วัน)
// ใช้ t.debt_date ได้เลย เพราะมันจะถูกส่งออกมาจาก Subquery
$debt_date_calc = "DATEDIFF('{$cal_date}', t.debt_date)";
$final_age_condition = '';

if ($age_condition === '<= 3') {
    // 0-90 วัน
    $final_age_condition = "<= 90";
} elseif ($age_condition === '> 3 AND <= 12') {
    // 91 วัน ถึง 1 ปี (365 วัน)
    $final_age_condition = "> 90 AND {$debt_date_calc} <= 365"; 
} elseif ($age_condition === '> 12') {
    // เกิน 1 ปี (365 วัน)
    $final_age_condition = "> 365";
} else {
    // Fallback
    $final_age_condition = $age_condition; 
}


// 4. ประมวลผลตาม Action
if ($action === 'summary') {
    
    // Action 1: Group by Account Name (สำหรับ Modal)
    // *** แก้ไข: เอา $subquery_union มาใส่ใน FROM (...) AS t ***
    $sql = "
        SELECT
            t.accountname,
            COUNT(t.visit_id) AS num_debtors,
            SUM(t.debt_amount) AS total_debt_amount
        FROM ({$subquery_union}) AS t
        WHERE 
            {$debt_date_calc} {$final_age_condition}
        GROUP BY t.accountname
        ORDER BY total_debt_amount DESC;
    ";

    $result = $conn->query($sql);

    // 5. สร้าง HTML Table สำหรับ Summary
    if ($result && $result->num_rows > 0) {
        $html_table = '
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สิทธิลูกหนี้</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนรายการ (VN/AN)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">มูลค่าลูกหนี้ (บาท)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';
        
        while ($row = $result->fetch_assoc()) {
            $onclick = "openPatientDrilldown('".mysqli_real_escape_string($conn, $row['accountname'])."', '{$age_condition}')";
            $html_table .= '
                <tr class="hover:bg-yellow-50 cursor-pointer" onclick="'.$onclick.'">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        '.$row['accountname'].'
                        <span class="text-xs text-blue-500 block">คลิกเพื่อดูรายละเอียดคนไข้</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                        '.number_format($row['num_debtors']).'
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-primary">
                        '.number_format($row['total_debt_amount'], 2).'
                    </td>
                </tr>
            ';
        }

        $html_table .= '
                </tbody>
            </table>
            </div>';
    } else {
        $html_table = '<p class="text-center py-5 text-gray-500">ไม่พบข้อมูลลูกหนี้คงเหลือในเงื่อนไขนี้</p>';
    }

} elseif ($action === 'detail') {

    // Action 2: Patient Drill-down (สำหรับส่วน Detail)
    $accountname_safe = mysqli_real_escape_string($conn, $accountname);
    
    // *** แก้ไข: เอา $subquery_union มาใส่ใน FROM (...) AS t ***
    $sql = "
        SELECT
            t.hn,
            t.visit_id,
            t.ptname,
            t.debt_amount,
            t.debt_date,
            t.type
        FROM ({$subquery_union}) AS t
        WHERE
            t.accountname = '{$accountname_safe}'
            AND {$debt_date_calc} {$final_age_condition}
        ORDER BY t.debt_amount DESC;
    ";

    $result = $conn->query($sql);
    
    // 5. สร้าง HTML Table สำหรับ Detail
    if ($result && $result->num_rows > 0) {
        $html_table = '
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HN/ชื่อคนไข้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">VN/AN</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่ตั้งหนี้</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ค่าใช้จ่าย (บาท)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';
        
        while ($row = $result->fetch_assoc()) {
            $html_table .= '
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        '.$row['hn'].' - '.$row['ptname'].'
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">'.$row['visit_id'].'</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">'.$row['type'].'</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">'.(new DateTime($row['debt_date']))->format('d/m/Y').'</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-red-600">
                        '.number_format($row['debt_amount'], 2).'
                    </td>
                </tr>
            ';
        }

        $html_table .= '
                </tbody>
            </table>
            </div>';

    } else {
        $html_table = '<p class="text-center py-5 text-gray-500">ไม่พบรายชื่อคนไข้ในเงื่อนไขนี้</p>';
    }
}

// 6. ส่งผลลัพธ์กลับ
echo json_encode(['status' => 'success', 'html_table' => $html_table, 'sql' => $sql]);
$conn->close();
?>