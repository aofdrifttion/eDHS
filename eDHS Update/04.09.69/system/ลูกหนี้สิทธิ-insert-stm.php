<?php
session_start();
require 'vendor/autoload.php';
require './database_config/config.php';
require_once './database_config/db_helper.php';


use PhpOffice\PhpSpreadsheet\IOFactory;

ob_clean();
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('memory_limit', '-1');
set_time_limit(300);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Request ไม่ถูกต้อง"]);
    exit;
}

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== 0) {
    echo json_encode(["status" => "error", "message" => "กรุณาเลือกไฟล์เพื่อนำเข้า"]);
    exit;
}

if (empty($_POST['fund']) || $_POST['fund'] == "00") {
    echo json_encode(["status" => "error", "message" => "กรุณาเลือกลูกหนี้สิทธิ"]);
    exit;
}

$fund = $_POST['fund'];
$file = $_FILES['excel_file']['tmp_name'];
$file_extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['xlsx', 'xls'];

if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(["status" => "error", "message" => "รองรับเฉพาะไฟล์ .xlsx เท่านั้น"]);
    exit;
}

try {
    $data = [];
    if ($file_extension === 'csv') {
        $fileHandle = fopen($file, 'r');
        while (($row = fgetcsv($fileHandle)) !== false) {
            $data[] = $row;
        }
        fclose($fileHandle);
    } else {


        // แก้เป็น — ระบุ Reader ชัดๆ เพื่อให้ handle xls ถูกต้อง
        $readerType = ($file_extension === 'xls') ? 'Xls' : 'Xlsx';
        $reader = IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader, $sheet);
        gc_collect_cycles();
        
    }

    while (!array_filter(end($data))) {
        array_pop($data);
    }

    if (empty($data)) {
        echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลในไฟล์"]);
        exit;
    }

    // ==========================================================
    // 🟢 แปลง Encoding เฉพาะไฟล์ .xls (BIFF เก่า) เท่านั้น!
    // สาเหตุ: PhpSpreadsheet อ่าน .xls เก่า (Windows-874) แล้ว
    //   misinterpret ว่า Latin-1 → double-encoded text (เช่น «¹Ò§» แทน «นาง»)
    // วิธีแก้: UTF-8 → ISO-8859-1 ก่อน แล้ว Windows-874 → UTF-8
    // ⚠️ .xlsx ใช้ UTF-8 ถูกต้องอยู่แล้ว → ห้ามทำ หรือภาษาไทยจะหาย!
    // ==========================================================
    if ($file_extension === 'xls') {
        foreach ($data as &$rowData) {
            if (is_array($rowData)) {
                foreach ($rowData as &$colData) {
                    if ($colData !== null && $colData !== '') {
                        $str  = (string)$colData;
                        
                        // 🛑 ถ้าข้อความมีภาษาไทยที่สมบูรณ์อยู่แล้ว (เช่น ผ่านการ Save จาก Excel มาแล้ว) ให้ข้ามการแปลงไปเลย!
                        if (preg_match('/[\p{Thai}]/u', $str)) {
                            continue;
                        }

                        $raw  = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $str);
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
    // ==========================================================

    // ==========================================================
    // 🛑 ตรวจสอบว่า Header มีภาษาไทยปนมาหรือไม่
    // ==========================================================
    foreach ($data[0] as $col) {
        // ใช้ Regular Expression ตรวจหาตัวอักษรภาษาไทย (ก-ฮ, สระ, วรรณยุกต์)
        if (preg_match('/[\p{Thai}]/u', (string)$col)) {
            echo json_encode([
                "status" => "error", 
                "message" => "ไฟล์ผิดรูปแบบ กรุณาส่งออกข้อมูลโดยกด F7 และนำเข้าใหม่"
            ]);
            exit;
        }
    }
    // ==========================================================


    $header = array_map('strtolower', $data[0]);

    // valid_columns ต้องตรงกับ column ในตาราง imr_tb_check_invoice เท่านั้น
    // 'an' และ 'diagnosis' ไม่มีในตาราง DB → ไม่ต้อง include ตรงๆ
    // (an จะถูก map ไปยัง vn ผ่าน $useAnAsVn ด้านล่าง)
    $valid_columns = [
        "no", "yearbudget", "rep", "id", "vn", "pid", "hn", "ptname", "admdate", "dchdate", "pttypename",
        "pttype_eclaim_name", "accountcode", "accountname", "subfund", "errorcode", "flag", "collected",
        "percentpay", "compensated", "paidtype", "bill", "billdate", "debit", "diff", "down", "up", "total",
        "fund", "hc", "ae", "inst", "ip", "dmis", "op", "prior", "drug", "ontop", "pallativecare", "dmishd",
        "fpnhso", "imr_tb_check_invoice"
    ];

    $file_columns = array_intersect($header, $valid_columns);

    // ✅ กรณีไม่มี vn แต่มี an → ใช้ an แทน vn (map an → vn field ใน DB)
    $useAnAsVn = false;
    $vnFromExcel = in_array("vn", $header);
    $anFromExcel = in_array("an", $header);

    if (!$vnFromExcel && $anFromExcel) {
        $file_columns[] = "vn"; // เพิ่ม vn ปลอมเข้าไปเพื่อให้ insert ได้ (ค่าจะมาจาก an)
        $useAnAsVn = true;
    }

    // เพิ่ม fund ถ้ายังไม่มี
    if (!in_array("fund", $file_columns)) {
        $file_columns[] = "fund";
    }

    $file_columns = array_unique($file_columns);

    $columns_sql = implode(", ", $file_columns);
    $placeholders = implode(", ", array_fill(0, count($file_columns), "?"));
    $query = "INSERT INTO imr_tb_check_invoice ($columns_sql) VALUES ($placeholders)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "SQL เตรียมคำสั่งล้มเหลว: " . $conn->error]);
        exit;
    }

    $success_count = 0;
    $error_count = 0;
    $batchSize = 500;
    $maxRowsPerRun = 3000;

    // ตรวจสอบ rep ว่ามีคำต้องห้ามหรือไม่
    $rep_index = array_search("rep", $header);
    $forbidden_keywords = ['LGO-HD', '_COCD', '_SOCD', 'DCKD'];

    $dataRows = array_slice($data, 1);

    // ==========================================================
    // 🗑️ ตัดแถวสุดท้ายออกถ้าเป็นแถว "รวม" (summary row)
    // ตรวจโดยดูว่า column "no" ไม่ใช่ตัวเลข → ถือเป็นแถวสรุป ไม่นับ
    // ==========================================================
    $no_index = array_search('no', $header);
    if (!empty($dataRows)) {
        $lastRow = end($dataRows);
        $lastNo  = ($no_index !== false && isset($lastRow[$no_index])) ? trim($lastRow[$no_index]) : '';
        if (!is_numeric($lastNo)) {
            array_pop($dataRows); // ตัดแถวสรุปออก (ไม่นับเป็น error)
        }
    }
    // ==========================================================

    if ($rep_index !== false) {
        foreach ($dataRows as $row) {
            $rep_value = isset($row[$rep_index]) ? strtoupper(trim($row[$rep_index])) : '';
            foreach ($forbidden_keywords as $forbidden) {
                if (strpos($rep_value, strtoupper($forbidden)) !== false) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "❌ ไม่สามารถนำเข้าได้: rep ไม่ตรงกลุ่มที่กำหนด"
                    ]);
                    exit;
                }
            }
        }
    }

    // ✅ หา rep แรกจากไฟล์
    $rep_value = null;
    if ($rep_index !== false) {
        foreach ($dataRows as $row) {
            if (!empty($row[$rep_index])) {
                $rep_value = trim($row[$rep_index]);
                break;
            }
        }
    }

    // ✅ ตรวจสอบว่า rep นี้มีอยู่ในฐานข้อมูลหรือยัง
    if ($rep_value) {
        $check_sql = "SELECT COUNT(*) AS cnt FROM imr_tb_check_invoice WHERE rep = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            echo json_encode(["status" => "error", "message" => "เตรียมคำสั่งตรวจสอบซ้ำล้มเหลว: " . $conn->error]);
            exit;
        }
        $check_stmt->bind_param("s", $rep_value);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "❌ ไม่สามารถนำเข้าได้ เพราะข้อมูล rep: $rep_value มีอยู่ในระบบแล้ว"
            ]);
            exit;
        }
    }

    // เริ่มการนำเข้าข้อมูล
    $dataChunks = array_chunk($dataRows, $maxRowsPerRun);

    foreach ($dataChunks as $chunk) {
        $batchValues = [];

        foreach ($chunk as $row) {
            // 1. ตรวจสอบ HN (พื้นฐาน)
            $hn_index = array_search("hn", $header);
            if ($hn_index === false || empty(trim($row[$hn_index]))) {
                $error_count++;
                continue;
            }

            // ==========================================================
            //  ตัวอย่างที่ถูกต้อง: 681200021:681127060513
            // ==========================================================
            if ($fund === 'OFC') {
                $id_index = array_search("id", $header);
                $id_value = ($id_index !== false && isset($row[$id_index])) ? trim($row[$id_index]) : '';

                // ถ้าค่า id ว่าง หรือ ไม่มีเครื่องหมาย : ให้ข้ามแถวนี้ทันที (ไม่ Insert)
                if (empty($id_value) || strpos($id_value, ':') === false) {
                    $error_count++; // นับว่าเป็น Error (ถูกข้าม)
                    continue;       // ข้ามไปรอบถัดไปทันที
                }
            }
            // ==========================================================

            $values = [];
            foreach ($file_columns as $col) {
                if ($col === "fund") {
                    $values[] = $fund;
                } elseif ($col === "vn" && $useAnAsVn) {
                    // ไม่มี vn → ใช้ an แทน
                    $index = array_search("an", $header);
                    $values[] = ($index !== false && isset($row[$index])) ? trim($row[$index]) : null;
                } else {
                    $index = array_search($col, $header);
                    $val = ($index !== false && isset($row[$index])) ? trim($row[$index]) : null;
                    
                    if ($col === 'pid') {
                        $val = encrypt_data($val);
                    }
                    $values[] = $val;
                }
            }

            $batchValues[] = $values;

            if (count($batchValues) >= $batchSize) {
                insertBatch($stmt, $batchValues);
                $success_count += count($batchValues);
                $batchValues = [];
            }
        }

        if (!empty($batchValues)) {
            insertBatch($stmt, $batchValues);
            $success_count += count($batchValues);
        }
    }

    if (function_exists('system_log')) {
        system_log($conn, 'นำเข้า STM & ตัดลูกหนี้ตาม STM', 'IMPORT', "นำเข้า STM (สิทธิ: $fund, Rep: $rep_value, สำเร็จ: $success_count รายการ)");
    }

    echo json_encode([
        "status" => "success",
        "message" => "ดำเนินการเสร็จสิ้น ✅",
        "success_count" => $success_count,
        "error_count" => $error_count, // จำนวนแถวที่ถูกข้าม (รวมถึงรูปแบบ OFC ที่ผิด)
        "progress" => 100
    ]);


    date_default_timezone_set('Asia/Bangkok');
    ///ส่งแจ้งเตือนไลน์กลุ่ม
    $fundText = $fund ?? '-';
    $summaryText = "นำเข้า Statement ($fundText)\n"
                 . "📄 เลขที่ Rep : $rep_value\n"
                 . "✅ สำเร็จ : $success_count รายการ\n"
                 . "❌ ไม่สำเร็จ : $error_count รายการ";

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

    $noteText = "ระบบนำเข้าข้อมูล Statement";
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
                        "text" => "นำเข้าข้อมูล : " . $_SESSION['fullname'],  
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
    // ใช้ Throwable แทน Exception เพื่อดัก ValueError และ Error ทุกประเภทใน PHP 8
    error_log("Error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
    exit;
}

function insertBatch($stmt, $batchValues) {
    foreach ($batchValues as $values) {
        $stmt->bind_param(str_repeat("s", count($values)), ...$values);
        $stmt->execute();
    }
}


function sendNotify($payload, $Client_ID, $Secret) {
    $config_file = __DIR__ . '/database_config/config.json';
    if (file_exists($config_file)) {
        $conf = json_decode(file_get_contents($config_file), true);
        if (isset($conf['notify_enable']) && $conf['notify_enable'] === '0') {
            return; // แจ้งเตือนถูกปิดไว้
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
