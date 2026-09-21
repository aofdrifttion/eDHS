<?php
session_start();
require './database_config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accountcode']) && isset($_POST['month'])) {
    $accountcode = $conn->real_escape_string($_POST['accountcode']);
    $month = $conn->real_escape_string($_POST['month']);

    // ดึงข้อมูลลูกหนี้ที่มีการปรับปรุง (พิจารณาจากร่องรอยการอัปโหลดไฟล์)
    $sql = "
        SELECT 
            vn AS vn_an, 
            ptname, 
            rcpnodate AS old_income, 
            income AS new_income, 
            rcpno AS file_path
        FROM imr_tb_debtor_rights_opd 
        WHERE accountcode = '$accountcode' 
          AND monthtxt = '$month' 
          AND rcpno LIKE 'uploads/%'

        UNION ALL

        SELECT 
            an AS vn_an, 
            ptname, 
            rcpnodate AS old_income, 
            income AS new_income, 
            rcpno AS file_path
        FROM imr_tb_debtor_rights_ipd 
        WHERE accountcode = '$accountcode' 
          AND monthtxt = '$month' 
          AND rcpno LIKE 'uploads/%'
    ";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $total_old = 0;
        $total_new = 0;
        $has_data = false;

        while ($row = mysqli_fetch_assoc($result)) {
            $vn_an = htmlspecialchars($row['vn_an']);
            $ptname = htmlspecialchars($row['ptname']);
            
            // แปลงยอดเงิน
            $old_income_val = floatval($row['old_income']);
            $old_income = number_format($old_income_val, 2);
            
            $new_income_val = floatval($row['new_income']);
            $new_income = number_format($new_income_val, 2);
            
            $file_path = htmlspecialchars($row['file_path']);
            
            // ดึงชื่อเจ้าหน้าที่ ออกจากไฟล์ตาม logic ใน vn.php
            $staffName = "ระบบ/เจ้าหน้าที่";
            $fileButtonHtml = '<span class="text-muted">- ไม่มี -</span>';

            if (!empty($row['file_path'])) {
                $filename = basename($row['file_path']);
                $nameParts = explode('_', $filename);
                
                if (count($nameParts) >= 4) {
                    $fullnameParts = array_slice($nameParts, 2, count($nameParts) - 3);
                    $staffName = str_replace('_', ' ', implode(' ', $fullnameParts));
                }

                $fileButtonHtml = '<a href="' . $file_path . '" target="_blank" class="btn btn-sm btn-outline-info" style="font-size: 11px;">📄 ดูไฟล์แนบ</a>';
            }

            // เฉพาะเคสที่ rcpnodate > 0 (ตามเงื่อนไขใน vn.php)
            if ($old_income_val > 0) {
                $has_data = true;
                $total_old += $old_income_val;
                $total_new += $new_income_val;

                echo "<tr>";
                echo "<td style='text-align: center; vertical-align: middle;'>{$vn_an}</td>";
                echo "<td style='text-align: left; vertical-align: middle;'>{$ptname}</td>";
                echo "<td style='text-align: right; font-weight: bold; color: #bb0707; vertical-align: middle;'>{$old_income}</td>";
                echo "<td style='text-align: right; font-weight: bold; color: #28a745; vertical-align: middle;'>{$new_income}</td>";
                echo "<td style='text-align: center; vertical-align: middle;'>{$staffName}</td>";
                echo "<td style='text-align: center; vertical-align: middle;'>{$fileButtonHtml}</td>";
                echo "</tr>";
            }
        }
        
        if ($has_data) {
            echo "<tr>";
            echo "<td colspan='2' style='text-align: right; font-weight: bold; vertical-align: middle;'>รวมทั้งหมด</td>";
            echo "<td style='text-align: right; font-weight: bold; color: #bb0707; vertical-align: middle;'>" . number_format($total_old, 2) . "</td>";
            echo "<td style='text-align: right; font-weight: bold; color: #28a745; vertical-align: middle;'>" . number_format($total_new, 2) . "</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
        } else {
            echo "<tr><td colspan='6' class='text-center'>ไม่พบข้อมูลการปรับปรุงที่เข้าเงื่อนไข</td></tr>";
        }
    } else {
        echo "<tr><td colspan='6' class='text-center'>ไม่พบข้อมูลการปรับปรุง</td></tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center text-danger'>ข้อมูลไม่ครบถ้วน</td></tr>";
}

mysqli_close($conn);
?>
