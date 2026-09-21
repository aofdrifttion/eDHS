<?php
require 'vendor/autoload.php';
require './database_config/config.php';
require_once './database_config/db_helper.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

ob_clean();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '-1');
set_time_limit(300);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Request ไม่ถูกต้อง"]);
    exit;
}

if (!isset($_FILES['excel_file']) || empty($_FILES['excel_file']['name'][0])) {
    echo json_encode(["status" => "error", "message" => "กรุณาเลือกไฟล์เพื่อนำเข้า"]);
    exit;
}

$allowed_extensions = ['xlsx'];
$errors = [];
$uploaded_files = [];
$fund = $_POST['ckd_select'];

foreach ($_FILES['excel_file']['name'] as $index => $name) {
    $tmp_name = $_FILES['excel_file']['tmp_name'][$index];
    $error = $_FILES['excel_file']['error'][$index];
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = "ไฟล์ {$name} อัปโหลดไม่สำเร็จ (error code: {$error})";
        continue;
    }

    if (!in_array($extension, $allowed_extensions)) {
        $errors[] = "ไฟล์ {$name} ไม่ใช่นามสกุลที่รองรับ ({$extension})";
        continue;
    }

    $uploaded_files[] = [
        'name' => $name,
        'tmp_name' => $tmp_name,
        'extension' => $extension
    ];
}

if (!empty($errors)) {
    echo json_encode(["status" => "error", "message" => implode("\n", $errors)]);
    exit;
}

$total_success = 0;
$total_error = 0;
$total_updated = 0;

foreach ($uploaded_files as $upload) {
    $file = $upload['tmp_name'];
    $file_extension = $upload['extension'];
    $data = [];

    try {
        if ($file_extension === 'csv') {
            $fileHandle = fopen($file, 'r');
            while (($row = fgetcsv($fileHandle)) !== false) {
                $data[] = $row;
            }
            fclose($fileHandle);
        } else {
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $reader, $sheet);
            gc_collect_cycles();
        }

        while (!array_filter(end($data))) {
            array_pop($data);
        }

        if (empty($data)) {
            echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลในไฟล์ {$upload['name']}"]);
            exit;
        }

        $header = array_map('strtolower', $data[0]);

        $valid_columns = ["no", "yearbudget", "rep", "id", "vn", "pid", "hn", "ptname", "admdate", "dchdate", "pttypename",
            "pttype_eclaim_name", "accountcode", "accountname", "subfund", "errorcode", "flag", "collected",
            "percentpay", "compensated", "paidtype", "bill", "billdate", "debit", "diff", "down", "up", "total",
            "fund", "hc", "ae", "inst", "ip", "dmis", "op", "prior", "drug", "ontop", "pallativecare", "dmishd",
            "fpnhso", "diagnosis"];

        $file_columns = array_intersect($header, $valid_columns);
        $useAnAsVn = false;
        $vnFromExcel = in_array("vn", $header);
        $anFromExcel = in_array("an", $header);

        if (!$vnFromExcel && $anFromExcel) {
            $file_columns[] = "vn";
            $useAnAsVn = true;
        }

        if (!in_array("fund", $file_columns)) {
            $file_columns[] = "fund";
        }

        $file_columns = array_unique($file_columns);
        $columns_sql = implode(", ", $file_columns);
        $placeholders = implode(", ", array_fill(0, count($file_columns), "?"));
        $query = "INSERT INTO imr_tb_seamless_dckd ($columns_sql) VALUES ($placeholders)";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "SQL เตรียมคำสั่งล้มเหลว: " . $conn->error]);
            exit;
        }

        $success_count = 0;
        $error_count = 0;
        $batchSize = 500;
        $maxRowsPerRun = 3000;

        $rep_index = array_search("rep", $header);
        $required_keywords = ['_SOCD', 'DCKD'];
        $found_valid_keyword = false;

        if ($rep_index !== false) {
            foreach (array_slice($data, 1) as $row) {
                $rep_value = isset($row[$rep_index]) ? strtoupper(trim($row[$rep_index])) : '';
                foreach ($required_keywords as $keyword) {
                    if (strpos($rep_value, strtoupper($keyword)) !== false) {
                        $found_valid_keyword = true;
                        break 2;
                    }
                }
            }
            if (!$found_valid_keyword) {
                echo json_encode(["status" => "error", "message" => "❌ ไม่สามารถนำเข้าได้: rep ไม่ตรงกลุ่มที่กำหนด"]);
                exit;
            }
        }

        $dataRows = array_slice($data, 1);
        $rep_value = null;
        if ($rep_index !== false) {
            foreach ($dataRows as $row) {
                if (!empty($row[$rep_index])) {
                    $rep_value = trim($row[$rep_index]);
                    break;
                }
            }
        }

        if ($rep_value) {
            $check_sql = "SELECT COUNT(*) AS cnt FROM imr_tb_seamless_dckd WHERE rep = ?";
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
                echo json_encode(["status" => "error", "message" => "❌ ไม่สามารถนำเข้าได้ เพราะข้อมูล rep: $rep_value มีอยู่ในระบบแล้ว"]);
                exit;
            }
        }

        $dataChunks = array_chunk($dataRows, $maxRowsPerRun);
        foreach ($dataChunks as $chunk) {
            $batchValues = [];
            foreach ($chunk as $row) {
                $hn_index = array_search("hn", $header);
                if ($hn_index === false || empty(trim($row[$hn_index]))) {
                    $error_count++;
                    continue;
                }
                $values = [];
                foreach ($file_columns as $col) {
                    if ($col === "fund") {
                        $values[] = $fund;
                    } elseif ($col === "vn" && $useAnAsVn) {
                        $index = array_search("an", $header);
                        $values[] = $index !== false && isset($row[$index]) ? mb_convert_encoding($row[$index], "UTF-8", "auto") : null;
                    } else {
                        $index = array_search($col, $header);
                        $val = $index !== false && isset($row[$index]) ? mb_convert_encoding($row[$index], "UTF-8", "auto") : null;
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
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("s", $rep_value);
            $stmt_update->execute();
            $updated_rows = $stmt_update->affected_rows;
        }

        $total_success += $success_count;
        $total_error += $error_count;
        $total_updated += $updated_rows;


        // Function แปลงวันที่ พ.ศ. ➜ ค.ศ. แบบ yyyy-mm-dd
        function convertThaiDateToMysql($thaiDate) {
            $parts = explode('/', $thaiDate);
            if (count($parts) === 3) {
                $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                $year_thai = $parts[2];
                $year = (int)$year_thai - 543;
                return "$year-$month-$day";
            }
            return null;
        }

        // Step 2: Update VN จากวันที่ใกล้เคียงก่อนหน้า (ถ้ายังอัปเดตไม่ได้)
        $fetch_missing_sql = "
            SELECT dckd.no, dckd.ptname, dckd.admdate
            FROM imr_tb_seamless_dckd AS dckd
            WHERE (dckd.vn IS NULL OR dckd.vn = '')
              AND dckd.rep = ?
        ";
        $stmt_missing = $conn->prepare($fetch_missing_sql);
        $stmt_missing->bind_param("s", $rep_value);
        $stmt_missing->execute();
        $result = $stmt_missing->get_result();

        while ($row = $result->fetch_assoc()) {
            $no = $row['no'];
            $ptname = $row['ptname'];
            $admdate = $row['admdate'];
            $admdate_mysql = convertThaiDateToMysql($admdate);

            if (!$admdate_mysql) {
                continue; // ข้ามถ้าแปลงวันที่ไม่ได้
            }

            $find_vn_sql = "
              SELECT vn,vstdate, pttypename, pttype_eclaim_name, accountcode, accountname
                FROM imr_tb_debtor_rights_opd
                WHERE 
                  DATE(CONCAT(
                        YEAR(STR_TO_DATE(vstdate, '%d/%m/%Y')) - 543, '-',
                        LPAD(MONTH(STR_TO_DATE(vstdate, '%d/%m/%Y')), 2, '0'), '-',
                        LPAD(DAY(STR_TO_DATE(vstdate, '%d/%m/%Y')), 2, '0')
                      )) <= ?
                    AND (
                      ptname LIKE CONCAT('%', ?) 
                      OR ? LIKE CONCAT('%', ptname)
                    )
                  AND (
                    pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%'
                  )
                ORDER BY vn desc
              LIMIT 1
            ";
            $stmt_vn = $conn->prepare($find_vn_sql);
            $stmt_vn->bind_param("sss", $admdate_mysql, $ptname, $ptname);
            $stmt_vn->execute();
            $vn_result = $stmt_vn->get_result();

            if ($vn_row = $vn_result->fetch_assoc()) {
                $vn = $vn_row['vn'];
                $pttypename = $vn_row['pttypename'];
                $pttype_eclaim_name = $vn_row['pttype_eclaim_name'];
                $accountcode = $vn_row['accountcode'];
                $accountname = $vn_row['accountname'];

                $update_missing_sql = "
                    UPDATE imr_tb_seamless_dckd
                    SET vn = ?, pttypename = ?, pttype_eclaim_name = ?, accountcode = ?, accountname = ?
                    WHERE no = ? AND rep = ?
                ";
                $stmt_update_missing = $conn->prepare($update_missing_sql);
                $stmt_update_missing->bind_param("sssssis", $vn, $pttypename, $pttype_eclaim_name, $accountcode, $accountname, $no, $rep_value);
                $stmt_update_missing->execute();

                if ($stmt_update_missing->affected_rows > 0) {
                    $total_updated++;
                }
            }
        }



    } catch (\Throwable $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาดกับไฟล์ {$upload['name']}: " . $e->getMessage()]);
        exit;
    }
}

if (function_exists('system_log')) {
    system_log($conn, 'นำเข้าลูกหนี้สิทธิ', 'INSERT', "นำเข้าข้อมูล CKD (กองทุน: $fund) สำเร็จ (เพิ่ม: $total_success, อัปเดต: $total_updated)");
}

echo json_encode([
    "status" => "success",
    "message" => "นำเข้าข้อมูลสำเร็จ ✅",
    "success_count" => $total_success,
    "error_count" => $total_error,
    "updated_rows" => $total_updated,
    "progress" => 100
]);
exit;

function insertBatch($stmt, $batchValues) {
    foreach ($batchValues as $values) {
        $stmt->bind_param(str_repeat("s", count($values)), ...$values);
        $stmt->execute();
    }
}
?>
