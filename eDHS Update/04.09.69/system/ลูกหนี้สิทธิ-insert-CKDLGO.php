<?php
require 'vendor/autoload.php';
require './database_config/config.php';
require_once './database_config/db_helper.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_time_limit(300);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Request ไม่ถูกต้อง"]);
    exit;
}

if (!isset($_FILES['excel_file']) || empty($_FILES['excel_file']['name'][0])) {
    echo json_encode(["status" => "error", "message" => "กรุณาเลือกไฟล์ Excel"]);
    exit;
}

$fund = $_POST['ckd_select'] ?? '';
$success_count = 0; 
$error_count = 0;
$total_files = count($_FILES['excel_file']['tmp_name']);

for ($i = 0; $i < $total_files; $i++) {
    $file_name = $_FILES['excel_file']['name'][$i];
    $file_tmp = $_FILES['excel_file']['tmp_name'][$i];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($file_ext, ['xlsx'])) {
        echo json_encode(["status" => "error", "message" => "❌ ไฟล์ $file_name ไม่ใช่ไฟล์ Excel ที่รองรับ (.xlsx)"]);
        exit;
    }

    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false);
    $spreadsheet = $reader->load($file_tmp);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet, $reader, $sheet);
    gc_collect_cycles();

    $data = array_slice($data, 10); // เริ่มข้อมูลจากแถวที่ 11 (index 10)
    $header = ["no", "rep", "hn", "pid", "ptname", "pttype", "admdate", "compensated", "note"];

    $mappedData = [];
    foreach ($data as $row) {
        if (!isset($row[0]) || !is_numeric($row[0])) continue;

        $rep = strtoupper(trim($row[1]));
        $pid = encrypt_data(strval(trim($row[3])));
        $hn = strval(intval($row[2]));
        $ptname = trim($row[4]);
        $admdate = trim($row[6]);

        // แปลงปี ค.ศ. เป็น พ.ศ. หากพบปี ค.ศ. 4 หลัก แล้วบันทึกใหม่
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $admdate, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = (int)$matches[3];
            if ($year < 2500) {
                $year += 543;
                $admdate = sprintf('%02d/%02d/%04d', $day, $month, $year);
            }
        }

        $compensated = floatval($row[7]);

        $mappedData[] = [$row[0], $rep, $pid, $hn, $ptname, $admdate, $compensated, $fund];
    }

    $valid_keywords = ['LGO-HD'];
    $found_valid_rep = false;
    foreach ($mappedData as $r) {
        foreach ($valid_keywords as $k) {
            if (strpos($r[1], $k) !== false) {
                $found_valid_rep = true;
                break 2;
            }
        }
    }

    if (!$found_valid_rep) {
        echo json_encode(["status" => "error", "message" => "❌ ไม่สามารถนำเข้าได้: rep ไม่ตรงกลุ่มที่กำหนด"]);
        exit;
    }

    $rep_value = $mappedData[0][1];
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM imr_tb_seamless_dckd WHERE rep = ?");
    $stmt_check->bind_param("s", $rep_value);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();
    if ($count > 0) {
        echo json_encode(["status" => "error", "message" => "❌ rep นี้มีในระบบแล้ว"]);
        exit;
    }

    $sql = "INSERT INTO imr_tb_seamless_dckd (no, rep, pid, hn, ptname, admdate, compensated, fund)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $success = 0;
    foreach ($mappedData as $row) {
        $stmt->bind_param("isssssss", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
        if ($stmt->execute()) {
            $success++;
        }
    }

    $updated_rows = 0;
    if ($rep_value) {
        $update_sql = "
            UPDATE imr_tb_seamless_dckd AS dckd
            JOIN imr_tb_debtor_rights_opd AS opd
              ON dckd.admdate = opd.vstdate
              AND (
                opd.ptname LIKE CONCAT('%', dckd.ptname, '%') 
                OR dckd.ptname LIKE CONCAT('%', opd.ptname, '%')
              )
            SET 
              dckd.vn = opd.vn,
              dckd.pttypename = opd.pttypename,
              dckd.pttype_eclaim_name = opd.pttype_eclaim_name,
              dckd.accountcode = opd.accountcode,
              dckd.accountname = opd.accountname
            WHERE 
              dckd.rep = ?
              AND (
                opd.pttypename LIKE '%ฟอกไต%' 
                OR opd.pttypename LIKE '%ไต%'
              )
        ";

        if ($stmt_update = $conn->prepare($update_sql)) {
            $stmt_update->bind_param("s", $rep_value);
            if ($stmt_update->execute()) {
                $updated_rows = $stmt_update->affected_rows;
            } else {
                error_log("❌ Error while updating VN: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            error_log("❌ Failed to prepare update statement: " . $conn->error);
        }
    }

    $success_count += $success;
    $error_count += $updated_rows;
}

if (function_exists('system_log')) {
    system_log($conn, 'นำเข้าลูกหนี้สิทธิ', 'INSERT', "นำเข้าข้อมูล CKDLGO (กองทุน: $fund) สำเร็จ (เพิ่ม: $success_count, อัปเดต: $error_count)");
}

echo json_encode([
    "status" => "success",
    "message" => "✅ นำเข้าข้อมูลสำเร็จ",
    "success_count" => $success_count,
    "error_count" => $error_count,
]);


?>


