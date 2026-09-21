<?php
session_start();

$user_fullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'ไม่ระบุชื่อ';
session_write_close();

require 'vendor/autoload.php';
require './database_config/config.php';
require_once './database_config/db_helper.php';
require_once './includes/cr_migration_helper.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');
ob_clean();

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('memory_limit', '-1');
set_time_limit(300);

// ================== VALIDATE ==================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit(json_encode(["status" => "error", "message" => "Request ไม่ถูกต้อง"]));
}

// รับค่า Task ID สำหรับ Progress Bar
$task_id = isset($_POST['task_id']) ? $_POST['task_id'] : '';
$progress_file = '';
if (!empty($task_id) && preg_match('/^[a-zA-Z0-9]+$/', $task_id)) {
    $progress_file = 'progress_' . $task_id . '.txt';
    file_put_contents($progress_file, "READING");
}

function exitWithError($message, $p_file) {
    if (!empty($p_file) && file_exists($p_file)) @unlink($p_file);
    exit(json_encode(["status" => "error", "message" => $message], JSON_UNESCAPED_UNICODE));
}

// ตรวจสอบไฟล์จาก Temp Token หรือ Upload ตรง
$temp_token = isset($_POST['temp_token']) ? basename(trim($_POST['temp_token'])) : '';
$temp_dir = __DIR__ . '/temp_uploads';
$temp_file = $temp_dir . '/' . $temp_token;
$is_temp = false;

if (!empty($temp_token) && file_exists($temp_file)) {
    $file = $temp_file;
    $file_extension = strtolower(pathinfo($temp_file, PATHINFO_EXTENSION));
    $is_temp = true;
} elseif (isset($_FILES['excel_file'])) {
    if ($_FILES['excel_file']['error'] !== 0) {
        $err_code = $_FILES['excel_file']['error'];
        $err_msg = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Error Code: $err_code)";
        if ($err_code == UPLOAD_ERR_INI_SIZE || $err_code == UPLOAD_ERR_FORM_SIZE) {
            $err_msg = "ขนาดไฟล์ใหญ่เกินกว่าที่เซิร์ฟเวอร์รองรับ (จำกัดที่ ".ini_get('upload_max_filesize').")";
        } elseif ($err_code == UPLOAD_ERR_PARTIAL) {
            $err_msg = "ไฟล์ถูกอัปโหลดเพียงบางส่วน";
        } elseif ($err_code == UPLOAD_ERR_NO_FILE) {
            $err_msg = "กรุณาเลือกไฟล์";
        }
        exitWithError($err_msg, $progress_file);
    }
    $file = $_FILES['excel_file']['tmp_name'];
    $file_extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
} else {
    exitWithError("ไม่พบข้อมูลไฟล์ที่ต้องการนำเข้า", $progress_file);
}

$monthi = isset($_POST['monthi']) ? $_POST['monthi'] : (isset($_POST['month']) ? $_POST['month'] : '');
$yeari = isset($_POST['yeari']) ? $_POST['yeari'] : (isset($_POST['year']) ? $_POST['year'] : '');

if (empty($monthi) || empty($yeari) || $monthi == "00" || $yeari == "00") {
    exitWithError("กรุณาเลือกเดือนและปีให้ถูกต้อง", $progress_file);
}

// รับรายการสิทธิการเงินที่เลือก (selected_rights)
$selected_rights = [];
if (isset($_POST['selected_rights'])) {
    if (is_array($_POST['selected_rights'])) {
        $selected_rights = $_POST['selected_rights'];
    } else {
        $decoded = json_decode($_POST['selected_rights'], true);
        if (is_array($decoded)) {
            $selected_rights = $decoded;
        } else {
            $selected_rights = array_filter(array_map('trim', explode(',', (string)$_POST['selected_rights'])));
        }
    }
}
$selected_map = !empty($selected_rights) ? array_flip($selected_rights) : [];

// รับค่าตัวเลือกข้ามรายการภาระหนี้เป็น 0 (skip_zero_debit)
$skip_zero_debit = isset($_POST['skip_zero_debit']) && ($_POST['skip_zero_debit'] === '1' || $_POST['skip_zero_debit'] === 'true' || $_POST['skip_zero_debit'] === 1);

$monthtxt = $monthi . '-' . $yeari;

try {

    // ================== READ FILE ==================
    class ProgressReadFilterIPD implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
        private $totalRows;
        private $progressFile;
        private $lastPercent = -1;

        public function __construct($totalRows, $progressFile) {
            $this->totalRows = $totalRows > 0 ? $totalRows : 1;
            $this->progressFile = $progressFile;
        }

        public function readCell($columnAddress, $row, $worksheetName = '') {
            if (!empty($this->progressFile) && $row % 20 == 0) {
                $percent = round(($row / $this->totalRows) * 100);
                if ($percent != $this->lastPercent && $percent <= 100) {
                    @file_put_contents($this->progressFile, "R_" . $percent);
                    $this->lastPercent = $percent;
                }
            }
            return true;
        }
    }

    $readerType = ($file_extension === 'xls') ? 'Xls' : 'Xlsx';
    $reader = IOFactory::createReader($readerType);
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false);

    $worksheetInfo = $reader->listWorksheetInfo($file);
    $total_read_rows = isset($worksheetInfo[0]['totalRows']) ? $worksheetInfo[0]['totalRows'] : 1;

    $progressFilter = new ProgressReadFilterIPD($total_read_rows, $progress_file);
    $reader->setReadFilter($progressFilter);
    $spreadsheet = $reader->load($file);

    // 🔥 แจ้งเตือนสถานะ: อ่านไฟล์เสร็จแล้ว กำลังเตรียมแปลงชุดข้อมูล
    if (!empty($progress_file)) {
        @file_put_contents($progress_file, "STAGE_PREP");
    }

    $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

    // 🧹 คืนหน่วยความจำของ PhpSpreadsheet ทันทีหลังจากดึง Array ออกมาแล้ว
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet, $reader, $progressFilter);
    gc_collect_cycles();

    if (count($data) <= 1) {
        exitWithError("ไม่มีข้อมูลในไฟล์ Excel", $progress_file);
    }

    // แปลง Encoding เฉพาะไฟล์ .xls (BIFF เก่า)
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
            exitWithError("ไฟล์ผิดรูปแบบ กรุณาส่งออกข้อมูลโดยกด F7 และนำเข้าใหม่", $progress_file);
        }
    }

    $header = array_map('strtolower', $headerRow);
    unset($headerRow);

    $valid_columns  = [
        "no", "an", "hn", "cid", "ptname", "sex", "birthday", "age", "nation", "admdate", "dchdate", 
        "pttype_eclaim_id", "pttype_eclaim_name", "pttype", "pttypename", "accountcode", "accountname", 
        "status", "income", "uc_money", "discount_money", "paid_money", "rcpt_money", "rcpno", "rcpnodate", 
        "debit", "max_debt_amount", "follow_money", "bill", "billdate", "totalall", "mobile", "tel", 
        "hospcode"
    ];

    $file_columns = array_values(array_unique(array_merge(array_intersect($header, $valid_columns), ["monthtxt"])));

    $accountcode_index = array_search('accountcode', $header);

    // ================== BUILD SQL ==================
    $columns_sql = implode(",", $file_columns);
    $placeholders = implode(",", array_fill(0, count($file_columns), "?"));

    // update เฉพาะ field ที่อยากให้ overwrite พร้อม Safeguard ข้อมูลการเงินและใบเสร็จ
    $update_sql = [];
    foreach ($file_columns as $col) {
        if ($col != "an") { // an เป็น key ห้าม update
            if ($col === 'bill') {
                $update_sql[] = "bill = IF(VALUES(bill) IS NOT NULL AND TRIM(VALUES(bill)) != '', VALUES(bill), bill)";
            } elseif ($col === 'billdate') {
                $update_sql[] = "billdate = IF(VALUES(billdate) IS NOT NULL AND TRIM(VALUES(billdate)) != '', VALUES(billdate), billdate)";
            } elseif ($col === 'follow_money') {
                $update_sql[] = "follow_money = IF(VALUES(follow_money) IS NOT NULL AND TRIM(VALUES(follow_money)) != '' AND CAST(VALUES(follow_money) AS DECIMAL(15,2)) > 0, VALUES(follow_money), follow_money)";
            } elseif ($col === 'rcpno') {
                $update_sql[] = "rcpno = IF(VALUES(rcpno) IS NOT NULL AND TRIM(VALUES(rcpno)) != '', VALUES(rcpno), rcpno)";
            } elseif ($col === 'rcpnodate') {
                $update_sql[] = "rcpnodate = IF(VALUES(rcpnodate) IS NOT NULL AND TRIM(VALUES(rcpnodate)) != '', VALUES(rcpnodate), rcpnodate)";
            } elseif ($col === 'debit') {
                $update_sql[] = "debit = IF((bill IS NOT NULL AND TRIM(bill) != '') OR (follow_money IS NOT NULL AND CAST(follow_money AS DECIMAL(15,2)) > 0), debit, VALUES(debit))";
            } else {
                $update_sql[] = "$col = VALUES($col)";
            }
        }
    }

    $sql = "INSERT INTO imr_tb_debtor_rights_ipd ($columns_sql)
            VALUES ($placeholders)
            ON DUPLICATE KEY UPDATE " . implode(",", $update_sql);

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare fail: " . $conn->error);
    }

    // ================== PROCESS ==================
    $insert_count  = 0;
    $update_count  = 0;
    $error_count   = 0;
    $skipped_count = 0;
    $skipped_zero_count = 0;

    $conn->begin_transaction();

    // ตัดแถวสุดท้ายออกถ้าเป็นแถว "รวม" (no ไม่ใช่ตัวเลข)
    $no_index = array_search('no', $header);
    if (!empty($data)) {
        $lastRow = end($data);
        $lastNo  = ($no_index !== false && isset($lastRow[$no_index])) ? trim((string)$lastRow[$no_index]) : '';
        if (!is_numeric($lastNo) && $lastNo !== '') {
            array_pop($data);
        }
    }

    $total_rows = count($data);
    $current_row = 0;
    $debit_idx = array_search('debit', $header);

    $cr_config = get_active_cr_config($conn);
    $auto_split_ipd = is_cr_auto_split_enabled($cr_config, 'IPD');

    // 🔥 แจ้งเตือนสถานะ: เริ่มต้นการบันทึกฐานข้อมูล
    if (!empty($progress_file)) {
        @file_put_contents($progress_file, "DB_0_0_{$total_rows}");
    }

    foreach ($data as $row) {
        $current_row++;

        $row_code = ($accountcode_index !== false && isset($row[$accountcode_index])) ? trim((string)$row[$accountcode_index]) : '';
        if ($row_code === '') {
            continue;
        }

        // Safeguard: ถ้าไม่ได้เปิดระบบ CR IPD ให้ถือว่า 1102050101.217 คือ 1102050101.202
        $effective_row_code = (!$auto_split_ipd && $row_code === '1102050101.217') ? '1102050101.202' : $row_code;

        // ตรวจสอบสิทธิการเงิน: ถ้ามีกำหนด selected_rights ให้กรองเฉพาะสิทธิที่เลือก
        if (!empty($selected_map)) {
            if (!isset($selected_map[$effective_row_code]) && !isset($selected_map[$row_code])) {
                $skipped_count++;
                continue; // ข้ามแถวที่ไม่ตรงกับสิทธิที่เลือก
            }
        }

        // 🔥 ตรวจสอบภาระหนี้เป็น 0 (ถ้าเปิดใช้งาน skip_zero_debit และมีคอลัมน์ debit)
        if ($skip_zero_debit && $debit_idx !== false) {
            $row_debit = 0.0;
            if (isset($row[$debit_idx])) {
                $d_val = str_replace(',', '', trim((string)$row[$debit_idx]));
                if (is_numeric($d_val)) {
                    $row_debit = (float)$d_val;
                }
            }

            // ข้อยกเว้น: ผังที่ eDHS ต้องนำมาคำนวณยอดหนี้ต่อ แม้ debit ในไฟล์จะเป็น 0 ก็ต้องนำเข้า
            // เช่น 1102050101.217 (CR IP), 1102050101.310 (SSS IP)
            $needs_calculation = (
                strpos($row_code, '1102050101.217') !== false ||
                strpos($effective_row_code, '1102050101.217') !== false ||
                strpos($row_code, '1102050101.310') !== false ||
                strpos($effective_row_code, '1102050101.310') !== false
            );

            if ($row_debit <= 0.001 && !$needs_calculation) {
                $skipped_zero_count++;
                continue; // ข้ามแถวที่ภาระหนี้เป็น 0
            }
        }

        try {
            $values = [];

            foreach ($file_columns as $col) {
                if ($col === "monthtxt") {
                    $values[] = $monthtxt;
                } 
                elseif ($col === "mobile") { 
                    $values[] = '-'; 
                } 
                else {
                    $index = array_search($col, $header);
                    $val = ($index !== false && isset($row[$index]))
                        ? ($row[$index] !== null ? trim((string)$row[$index]) : null)
                        : null;
                    
                    // Safeguard: แปลง 1102050101.217 กลับเป็น 1102050101.202 เมื่อ CR IPD ปิดอยู่
                    if (!$auto_split_ipd) {
                        if ($col === 'accountcode' && $val === '1102050101.217') {
                            $val = '1102050101.202';
                        } elseif ($col === 'accountname' && ($row_code === '1102050101.217' || $effective_row_code === '1102050101.202') && (strpos($val, '217') !== false || strpos($val, 'บริการเฉพาะ') !== false)) {
                            $val = 'ลูกหนี้ค่ารักษา UC - IP';
                        }
                    }

                    // เข้ารหัสฟิลด์ที่กำหนด
                    if ($col === 'cid' || $col === 'tel') {
                        $val = encrypt_data($val);
                    }
                    
                    $values[] = $val;
                }
            }

            $stmt->bind_param(str_repeat("s", count($values)), ...$values);
            $stmt->execute();

            if ($stmt->affected_rows == 1) {
                $insert_count++;
            } elseif ($stmt->affected_rows == 2 || $stmt->affected_rows == 0) {
                $update_count++;
            }

        } catch (\Throwable $e) {
            $error_count++;
        }

        // 🔥 อัปเดต % และจำนวนแถวแบบละเอียดลงไฟล์ Text ทุกๆ 10 แถว หรือเมื่อสุดรอบ
        if (!empty($progress_file) && ($current_row % 10 == 0 || $current_row == $total_rows)) {
            $percent = round(($current_row / $total_rows) * 100);
            @file_put_contents($progress_file, "DB_{$percent}_{$current_row}_{$total_rows}");
        }
    }


    $conn->commit();

    // ลบไฟล์ % และ Temp
    if (!empty($progress_file) && file_exists($progress_file)) @unlink($progress_file);
    if ($is_temp && file_exists($file)) @unlink($file);

    $log_detail = "นำเข้าข้อมูล IPD เดือน $monthtxt สำเร็จ (เพิ่ม: $insert_count, อัปเดต: $update_count, ข้ามสิทธิ: $skipped_count, ข้ามหนี้ 0: $skipped_zero_count จาก " . count($selected_rights) . " สิทธิที่เลือก)";
    if (function_exists('system_log')) {
        system_log($conn, 'นำเข้าลูกหนี้สิทธิ', 'INSERT', $log_detail);
    }

    echo json_encode([
        "status" => "success",
        "message" => "นำเข้าข้อมูลสำเร็จ ✅",
        "insert" => $insert_count,
        "update" => $update_count,
        "skipped" => $skipped_count,
        "skipped_zero" => $skipped_zero_count,
        "error"  => $error_count,
        "selected_rights_count" => count($selected_rights)
    ], JSON_UNESCAPED_UNICODE);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // ================== LINE NOTIFY ==================
    date_default_timezone_set('Asia/Bangkok');
    $summaryText = "นำเข้า IPD เดือน $monthtxt\n"
             . "✅ นำเข้าข้อมูลสำเร็จ: $insert_count\n"
             . "🔄 ปรับปรุงข้อมูล: $update_count\n"
             . "⏭️ ข้ามสิทธิที่ไม่เลือก: $skipped_count\n"
             . ($skipped_zero_count > 0 ? "⏸️ ข้ามภาระหนี้ 0 บาท: $skipped_zero_count\n" : "")
             . "❌ ไม่สามารถนำเข้า: $error_count";

    $thaiMonths = [
        "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน",
        "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม",
        "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];

    $day   = date('d');
    $month = $thaiMonths[(int)date('m')];
    $year  = date('Y') + 543;
    $time  = date('H:i');

    $currentDateTime = "$day $month $year เวลา $time น.";
    $noteText = "ระบบนำเข้าข้อมูลลูกหนี้ IPD สำเร็จ (เลือกนำเข้า " . count($selected_rights) . " สิทธิ)";
    
    $flexMessage = [
        "type" => "flex",
        "altText" => "แจ้งเตือน: eDHS",
        "contents" => [
            "type" => "bubble",
            "size" => "giga",
            "body" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "sm",
                "contents" => [
                    [
                        "type" => "image",
                        "url" => "https://phonsai-hos.com/main/web/img/eDHS0.png",
                        "size" => "sm",
                        "align" => "center",
                        "margin" => "none"
                    ],
                    [
                        "type" => "text",
                        "text" => "eDebtor Hospital System: eDHS",
                        "weight" => "bold",
                        "size" => "md",
                        "align" => "center",
                        "wrap" => true
                    ],
                    [
                        "type" => "separator",
                        "margin" => "md"
                    ],
                    [
                        "type" => "text",
                        "text" => "📥 รายละเอียด : " . $summaryText,
                        "size" => "sm",
                        "wrap" => true
                    ],
                    [
                        "type" => "text",
                        "text" => "นำเข้าข้อมูล : "  . $user_fullname,
                        "size" => "sm",
                        "wrap" => true
                    ],
                    [
                        "type" => "text",
                        "text" => "หมายเหตุ : " . $noteText,  
                        "size" => "sm",
                        "wrap" => true
                    ],
                    [
                        "type" => "text",
                        "text" => "ระบบ : eDHS",
                        "size" => "sm"
                    ],
                    [
                        "type" => "separator",
                        "margin" => "md"
                    ],
                    [
                        "type" => "text",
                        "text" => "วันที่ : " . $currentDateTime,
                        "size" => "sm",
                        "align" => "center",
                        "color" => "#666666"
                    ]
                ]
            ],
            "footer" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "sm",
                "contents" => [
                    [
                        "type" => "text",
                        "text" => "ระบบแจ้งเตือนอัตโนมัติ",
                        "size" => "xs",
                        "align" => "center",
                        "color" => "#999999"
                    ]
                ]
            ]
        ]
    ];

    $payload = [
        "cid" => ["1234567891234"], 
        "messages" => [$flexMessage],
        "message_title" => " ",
        "message_html" => " ",
        "message_text" => " ",
        "message_type" => "HPT"
    ];

    sendNotify($payload, $Client_ID, $Secret);

} catch (\Throwable $e) {

    $conn->rollback();

    if (!empty($progress_file) && file_exists($progress_file)) @unlink($progress_file);
    if ($is_temp && file_exists($file)) @unlink($file);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function sendNotify($payload, $Client_ID, $Secret) {
    $config_file = __DIR__ . '/database_config/config.json';
    if (file_exists($config_file)) {
        $conf = json_decode(file_get_contents($config_file), true);
        if (isset($conf['notify_enable']) && $conf['notify_enable'] === '0') {
            return;
        }
    }

    $url = "https://morpromt2f.moph.go.th/api/notify/send";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "client-key: $Client_ID",
            "secret-key: $Secret"
        ]
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('Notify Error: ' . curl_error($ch));
    } else {
        error_log('Notify Response: ' . $response);
    }

    curl_close($ch);
}
?>
