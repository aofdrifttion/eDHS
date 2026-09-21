<?php
require './database_config/config.php';

$accountcode = $_GET['accountcode'] ?? '';
$month = $_GET['month'] ?? '';

$filename = "updated_debtors_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$accountcode_safe = $conn->real_escape_string($accountcode);
$month_safe = $conn->real_escape_string($month);

$sql = "
    SELECT 
        vn AS vn_an, 
        ptname, 
        rcpnodate AS old_income, 
        income AS new_income, 
        rcpno AS file_path
    FROM imr_tb_debtor_rights_opd 
    WHERE accountcode = '$accountcode_safe' 
      AND monthtxt = '$month_safe' 
      AND rcpno LIKE 'uploads/%'

    UNION ALL

    SELECT 
        an AS vn_an, 
        ptname, 
        rcpnodate AS old_income, 
        income AS new_income, 
        rcpno AS file_path
    FROM imr_tb_debtor_rights_ipd 
    WHERE accountcode = '$accountcode_safe' 
      AND monthtxt = '$month_safe' 
      AND rcpno LIKE 'uploads/%'
";

$result = mysqli_query($conn, $sql);

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';
echo '<table border="1">';
echo '<tr>
        <th colspan="5" style="text-align:center; font-size: 16px;">รายงานการปรับปรุงลูกหนี้ (Account Code: '.$accountcode.' เดือน: '.$month.')</th>
      </tr>';
echo '<tr>
        <th>ลำดับ</th>
        <th>VN/AN</th>
        <th>ชื่อ-สกุล</th>
        <th>ค่ารักษาเดิม (บาท)</th>
        <th>ค่ารักษาใหม่ (บาท)</th>
        <th>เจ้าหน้าที่ผู้ปรับปรุง</th>
      </tr>';

if ($result && mysqli_num_rows($result) > 0) {
    $total_old = 0;
    $total_new = 0;
    $has_data = false;
    $i = 1;

    while ($row = mysqli_fetch_assoc($result)) {
        $vn_an = htmlspecialchars($row['vn_an']);
        $ptname = htmlspecialchars($row['ptname']);
        
        $old_income_val = floatval($row['old_income']);
        $new_income_val = floatval($row['new_income']);
        
        // ดึงชื่อเจ้าหน้าที่ ออกจากไฟล์ตาม logic
        $staffName = "ระบบ/เจ้าหน้าที่";
        if (!empty($row['file_path'])) {
            $file_name = basename($row['file_path']);
            $nameParts = explode('_', $file_name);
            
            if (count($nameParts) >= 4) {
                $fullnameParts = array_slice($nameParts, 2, count($nameParts) - 3);
                $staffName = str_replace('_', ' ', implode(' ', $fullnameParts));
            }
        }

        // เฉพาะเคสที่ rcpnodate > 0
        if ($old_income_val > 0) {
            $has_data = true;
            $total_old += $old_income_val;
            $total_new += $new_income_val;

            echo "<tr>";
            echo "<td>{$i}</td>";
            echo "<td style=\"mso-number-format:'\@';\">{$vn_an}</td>";
            echo "<td>{$ptname}</td>";
            echo "<td>{$old_income_val}</td>";
            echo "<td>{$new_income_val}</td>";
            echo "<td>{$staffName}</td>";
            echo "</tr>";
            $i++;
        }
    }
    
    if ($has_data) {
        echo "<tr>";
        echo "<td colspan='3' style='text-align: right; font-weight: bold;'>รวมทั้งหมด</td>";
        echo "<td><b>{$total_old}</b></td>";
        echo "<td><b>{$total_new}</b></td>";
        echo "<td></td>";
        echo "</tr>";
    } else {
        echo "<tr><td colspan='6' style='text-align: center;'>ไม่พบข้อมูลการปรับปรุงที่เข้าเงื่อนไข</td></tr>";
    }
} else {
    echo "<tr><td colspan='6' style='text-align: center;'>ไม่พบข้อมูลการปรับปรุง</td></tr>";
}

echo '</table></body></html>';

mysqli_close($conn);
?>
