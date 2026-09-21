<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
ob_clean();

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('memory_limit', '-1');
set_time_limit(300);

require 'vendor/autoload.php';
require './database_config/config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Request ไม่ถูกต้อง"]);
    exit;
}

if (!isset($_FILES['excel_file'])) {
    echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลไฟล์ที่อัปโหลด (ไฟล์อาจมีขนาดใหญ่เกินไป)"]);
    exit;
}

if ($_FILES['excel_file']['error'] !== 0) {
    $err_code = $_FILES['excel_file']['error'];
    $err_msg = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Error Code: $err_code)";
    if ($err_code == UPLOAD_ERR_INI_SIZE || $err_code == UPLOAD_ERR_FORM_SIZE) {
        $err_msg = "ขนาดไฟล์ใหญ่เกินกว่าที่เซิร์ฟเวอร์รองรับ";
    } elseif ($err_code == UPLOAD_ERR_NO_FILE) {
        $err_msg = "กรุณาเลือกไฟล์";
    }
    echo json_encode(["status" => "error", "message" => $err_msg]);
    exit;
}

$type = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : 'opd'; // 'opd' or 'ipd'
$month = isset($_POST['month']) ? $_POST['month'] : (isset($_POST['monthi']) ? $_POST['monthi'] : '');
$year = isset($_POST['year']) ? $_POST['year'] : (isset($_POST['yeari']) ? $_POST['yeari'] : '');

if (empty($month) || empty($year) || $month === "00" || $year === "00") {
    echo json_encode(["status" => "error", "message" => "กรุณาเลือกเดือนและปีให้ถูกต้อง"]);
    exit;
}

$file_name = $_FILES['excel_file']['name'];
$file_tmp = $_FILES['excel_file']['tmp_name'];
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

if (!in_array($file_extension, ['xls', 'xlsx'])) {
    echo json_encode(["status" => "error", "message" => "รองรับเฉพาะไฟล์ Excel (.xls หรือ .xlsx) เท่านั้น"]);
    exit;
}

// สร้างโฟลเดอร์ temp_uploads สำหรับเก็บไฟล์ชั่วคราว
$temp_dir = __DIR__ . '/temp_uploads';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// ล้างไฟล์ temp เก่าที่ค้างเกิน 2 ชั่วโมง (Garbage Collection)
$files = glob($temp_dir . '/*');
$now = time();
foreach ($files as $f) {
    if (is_file($f) && ($now - filemtime($f) > 7200)) {
        @unlink($f);
    }
}

// สร้าง Unique Token และบันทึกไฟล์ชั่วคราว
$token = bin2hex(random_bytes(16)) . '_' . time() . '.' . $file_extension;
$destination = $temp_dir . '/' . $token;

if (!move_uploaded_file($file_tmp, $destination)) {
    echo json_encode(["status" => "error", "message" => "ไม่สามารถบันทึกไฟล์ชั่วคราวได้"]);
    exit;
}

try {
    $readerType = ($file_extension === 'xls') ? 'Xls' : 'Xlsx';
    $reader = IOFactory::createReader($readerType);
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false);
    $spreadsheet = $reader->load($destination);
    $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

    // 🧹 คืนหน่วยความจำของ PhpSpreadsheet ทันที
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet, $reader);
    gc_collect_cycles();

    if (count($data) <= 1) {
        @unlink($destination);
        echo json_encode(["status" => "error", "message" => "ไฟล์ Excel ไม่มีข้อมูลรายการ"]);
        exit;
    }

    // ตรวจสอบและแปลง Encoding ภาษาไทยสำหรับ .xls
    if ($file_extension === 'xls') {
        foreach ($data as &$rowData) {
            if (is_array($rowData)) {
                foreach ($rowData as &$colData) {
                    if ($colData !== null && $colData !== '') {
                        $str = (string)$colData;
                        if (preg_match('/[\p{Thai}]/u', $str)) {
                            continue;
                        }
                        $raw = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $str);
                        if ($raw !== false && $raw !== '') {
                            $thai = @iconv('Windows-874', 'UTF-8//IGNORE', $raw);
                            if ($thai !== false && $thai !== '') {
                                $colData = $thai;
                            }
                        }
                    }
                }
            }
        }
        unset($rowData, $colData);
    }

    // ดึง Header ออกจาก Array โดยไม่ Duplicate ข้อมูลใน RAM
    $headerRow = array_shift($data);

    // ตรวจสอบว่า Header มีภาษาไทยปนมาหรือไม่
    foreach ($headerRow as $col) {
        if (preg_match('/[\p{Thai}]/u', (string)$col)) {
            @unlink($destination);
            echo json_encode([
                "status" => "error",
                "message" => "ไฟล์ผิดรูปแบบ กรุณาส่งออกข้อมูลโดยกด F7 และนำเข้าใหม่"
            ]);
            exit;
        }
    }

    $header = array_map('strtolower', $headerRow);
    unset($headerRow);

    $accountcode_idx = array_search('accountcode', $header);
    $accountname_idx = array_search('accountname', $header);
    $debit_idx = array_search('debit', $header);
    $income_idx = array_search('income', $header);
    $no_idx = array_search('no', $header);

    if ($accountcode_idx === false) {
        @unlink($destination);
        echo json_encode([
            "status" => "error",
            "message" => "ไม่พบคอลัมน์ accountcode ในไฟล์ Excel"
        ]);
        exit;
    }

    // ตัดแถวสุดท้ายออกถ้าเป็นแถว "รวม"
    if (!empty($data)) {
        $lastRow = end($data);
        $lastNo  = ($no_idx !== false && isset($lastRow[$no_idx])) ? trim((string)$lastRow[$no_idx]) : '';
        if (!is_numeric($lastNo) && $lastNo !== '') {
            array_pop($data);
        }
    }

    $rights_map = [];
    $total_debit_all = 0;
    $total_income_all = 0;
    $total_rows_valid = 0;
    $zero_debit_total = 0;

    foreach ($data as $row) {
        $code = ($accountcode_idx !== false && isset($row[$accountcode_idx])) ? trim((string)$row[$accountcode_idx]) : '';
        $name = ($accountname_idx !== false && isset($row[$accountname_idx])) ? trim((string)$row[$accountname_idx]) : '';
        
        if ($code === '') {
            continue;
        }

        if ($name === '') {
            $name = 'ไม่ระบุชื่อสิทธิ (' . $code . ')';
        }

        $debit = 0.0;
        if ($debit_idx !== false && isset($row[$debit_idx])) {
            $debit_val = str_replace(',', '', trim((string)$row[$debit_idx]));
            if (is_numeric($debit_val)) {
                $debit = (float)$debit_val;
            }
        }

        if ($debit_idx !== false && $debit <= 0.001) {
            $zero_debit_total++;
        }

        $income = 0.0;
        if ($income_idx !== false && isset($row[$income_idx])) {
            $income_val = str_replace(',', '', trim((string)$row[$income_idx]));
            if (is_numeric($income_val)) {
                $income = (float)$income_val;
            }
        }

        if (!isset($rights_map[$code])) {
            $rights_map[$code] = [
                'accountcode' => $code,
                'accountname' => $name,
                'count' => 0,
                'total_debit' => 0.0,
                'total_income' => 0.0,
                'zero_debit_count' => 0
            ];
        }

        $rights_map[$code]['count']++;
        $rights_map[$code]['total_debit'] += $debit;
        $rights_map[$code]['total_income'] += $income;
        if ($debit_idx !== false && $debit <= 0.001) {
            $rights_map[$code]['zero_debit_count']++;
        }

        $total_debit_all += $debit;
        $total_income_all += $income;
        $total_rows_valid++;
    }

    // แปลง Map เป็น Array และเรียงลำดับตามจำนวนรายการมากไปน้อย
    $rights_list = array_values($rights_map);
    usort($rights_list, function($a, $b) {
        return $b['count'] <=> $a['count'];
    });

    echo json_encode([
        "status" => "success",
        "temp_token" => $token,
        "filename" => $file_name,
        "type" => strtoupper($type),
        "month" => $month,
        "year" => $year,
        "total_rows" => $total_rows_valid,
        "zero_debit_count" => $zero_debit_total,
        "total_debit" => $total_debit_all,
        "total_income" => $total_income_all,
        "rights_count" => count($rights_list),
        "rights" => $rights_list
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    if (file_exists($destination)) {
        @unlink($destination);
    }
    echo json_encode([
        "status" => "error",
        "message" => "เกิดข้อผิดพลาดในการอ่านไฟล์: " . $e->getMessage()
    ]);
}
