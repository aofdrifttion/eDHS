<?php
session_start();

// 🔥 1. ดึงค่าชื่อคนทำมาก่อน แล้ว "ปลดล็อก Session ทันที" เพื่อให้ check_progress.php เข้ามาอ่านค่า % ได้ไม่ติดคิว
$user_fullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'ไม่ระบุชื่อ';
session_write_close(); 

require 'vendor/autoload.php';
require './database_config/config.php';
require_once './database_config/db_helper.php';

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

// 🔥 2. รับค่า Task ID และเช็คความปลอดภัยก่อนสร้างไฟล์ %
$task_id = isset($_POST['task_id']) ? $_POST['task_id'] : '';
$progress_file = '';
if (!empty($task_id) && preg_match('/^[a-zA-Z0-9]+$/', $task_id)) {
    $progress_file = 'progress_' . $task_id . '.txt';
    file_put_contents($progress_file, "READING"); // เริ่มต้นไว้ที่ 0% ก่อน
}

// ฟังก์ชันช่วยเคลียร์ไฟล์ขยะถ้าเกิด Error ก่อนเริ่มทำงาน
function exitWithError($message, $p_file) {
    if (!empty($p_file) && file_exists($p_file)) @unlink($p_file);
    exit(json_encode(["status" => "error", "message" => $message], JSON_UNESCAPED_UNICODE));
}

// 🔥 3. ตรวจสอบไฟล์จาก Temp Token หรือ Upload ตรง
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

if (empty($_POST['month']) || empty($_POST['year']) || $_POST['month'] == "00" || $_POST['year'] == "00") {
    exitWithError("กรุณาเลือกเดือนและปีให้ถูกต้อง", $progress_file);
}

// 🔥 4. รับรายการสิทธิการเงินที่ผู้ใช้เลือก (selected_rights)
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

$monthtxt = $_POST['month'] . '-' . $_POST['year'];

try {

    // ================== READ FILE ==================
    class ProgressReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
        private $totalRows;
        private $progressFile;
        private $lastPercent = -1;

        public function __construct($totalRows, $progressFile) {
            $this->totalRows = $totalRows > 0 ? $totalRows : 1;
            $this->progressFile = $progressFile;
        }

        public function readCell($columnAddress, $row, $worksheetName = '') {
            if (!empty($this->progressFile) && $row % 50 == 0) {
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

    $progressFilter = new ProgressReadFilter($total_read_rows, $progress_file);
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

    $valid_columns = [
        "no","vn","an","hn","cid","ptname","sex","age","nation","vstdate","vsttime","ptsubtype","department",
        "clinic","pttype_eclaim_id","pttype_eclaim_name","pttype","pttypename","accountcode","accountname","income",
        "uc_money","discount_money","paid_money","rcpt_money","rcpno","rcpnodate","debit","follow_money","bill","billdate",
        "totalall","mobile","tel","hospcode"
    ];

    $file_columns = array_values(array_unique(array_merge(array_intersect($header, $valid_columns), ["monthtxt"])));

    $accountcode_index = array_search('accountcode', $header);

    // ================== BUILD SQL ==================
    $columns_sql = implode(",", $file_columns);
    $placeholders = implode(",", array_fill(0, count($file_columns), "?"));

    // update เฉพาะ field ที่อยากให้ overwrite
    $update_sql = [];
    foreach ($file_columns as $col) {
        if ($col != "vn") { // vn เป็น key ห้าม update
            $update_sql[] = "$col = VALUES($col)";
        }
    }

    $sql = "INSERT INTO imr_tb_debtor_rights_opd ($columns_sql)
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

        // 🔥 ตรวจสอบสิทธิการเงิน: ถ้ามีกำหนด selected_rights ให้กรองเฉพาะสิทธิที่เลือก
        if (!empty($selected_map)) {
            if (!isset($selected_map[$row_code])) {
                $skipped_count++;
                continue; // ข้ามแถวที่ไม่ตรงกับสิทธิที่เลือก
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

        // 🔥 อัปเดต % และจำนวนแถวแบบละเอียดลงไฟล์ Text ทุกๆ 20 แถว หรือเมื่อครบ
        if (!empty($progress_file) && ($current_row % 20 == 0 || $current_row == $total_rows)) {
            $percent = round(($current_row / $total_rows) * 100);
            @file_put_contents($progress_file, "DB_{$percent}_{$current_row}_{$total_rows}");
        }
    }

    // อัปเดตตั้งค่าภาระหนี้อัตโนมัติ (ถ้าเปิดระบบไว้ และเดือนที่นำเข้ามีผลบังคับใช้)
    $config_file = __DIR__ . '/database_config/config.json';
    if (file_exists($config_file)) {
        $config_data = json_decode(file_get_contents($config_file), true);
        if (isset($config_data['debtor_setting']) && $config_data['debtor_setting']['enabled'] === '1') {
            require_once __DIR__ . '/database_config/db_helper.php';
            $ds_cfg = $config_data['debtor_setting'];
            $start_m = $ds_cfg['effective_start_month'] ?? 'ALL';
            $is_m_effective = (empty($start_m) || strtoupper($start_m) === 'ALL' || compare_monthtxt_be($monthtxt, $start_m) >= 0);
            
            if ($is_m_effective) {
                $escaped_m = mysqli_real_escape_string($conn, $monthtxt);
                $not_kidney_sql = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%' AND (department IS NULL OR department NOT LIKE '%ไต%') AND (clinic IS NULL OR clinic NOT LIKE '%ไต%'))";
                $not_billed_sql = "AND (bill IS NULL OR bill = '')";
                
                // 1. ผัง 1102050101.203 (ในสังกัด สธ.)
                $acc_203_base = floatval($ds_cfg['acc_203']['base_rate'] ?? 175.0);
                $conn->query("UPDATE imr_tb_debtor_rights_opd SET original_debit = debit WHERE accountcode = '1102050101.203' AND monthtxt = '$escaped_m' AND original_debit IS NULL $not_kidney_sql $not_billed_sql");
                $conn->query("UPDATE imr_tb_debtor_rights_opd SET debit = $acc_203_base WHERE accountcode = '1102050101.203' AND monthtxt = '$escaped_m' $not_kidney_sql $not_billed_sql");
                
                // 2. ผัง 1102050102.201 (นอกสังกัด สธ. - รพ.ค่าย / เทศบาล ๑-๒)
                $acc_102_ae_max = floatval($ds_cfg['acc_102_201']['ae_max'] ?? 700.0);
                $conn->query("UPDATE imr_tb_debtor_rights_opd SET original_debit = debit WHERE accountcode = '1102050102.201' AND monthtxt = '$escaped_m' AND original_debit IS NULL $not_billed_sql");
                $conn->query("UPDATE imr_tb_debtor_rights_opd SET debit = LEAST(original_debit, $acc_102_ae_max) WHERE accountcode = '1102050102.201' AND monthtxt = '$escaped_m' $not_billed_sql");
            }
        }
    }

    $conn->commit();

    // นำเข้าเสร็จแล้ว ลบไฟล์ % และไฟล์ Temp ชั่วคราวทิ้งทันที
    if (!empty($progress_file) && file_exists($progress_file)) @unlink($progress_file);
    if ($is_temp && file_exists($file)) @unlink($file);

    $log_detail = "นำเข้าข้อมูล OPD เดือน $monthtxt สำเร็จ (เพิ่ม: $insert_count, อัปเดต: $update_count, ข้าม: $skipped_count จาก " . count($selected_rights) . " สิทธิที่เลือก)";
    if (function_exists('system_log')) {
        system_log($conn, 'นำเข้าลูกหนี้สิทธิ', 'INSERT', $log_detail);
    }

    echo json_encode([
        "status" => "success",
        "message" => "นำเข้าข้อมูลสำเร็จ ✅",
        "insert" => $insert_count,
        "update" => $update_count,
        "skipped" => $skipped_count,
        "error"  => $error_count,
        "selected_rights_count" => count($selected_rights)
    ], JSON_UNESCAPED_UNICODE);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // ================== LINE NOTIFY ==================
    date_default_timezone_set('Asia/Bangkok');
    $summaryText = "นำเข้า OPD เดือน $monthtxt\n"
             . "✅ นำเข้าข้อมูลสำเร็จ: $insert_count\n"
             . "🔄 ปรับปรุงข้อมูล: $update_count\n"
             . "⏭️ ข้ามสิทธิที่ไม่เลือก: $skipped_count\n"
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
    $noteText = "ระบบนำเข้าข้อมูลลูกหนี้ OPD สำเร็จ (เลือกนำเข้า " . count($selected_rights) . " สิทธิ)";
    
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
