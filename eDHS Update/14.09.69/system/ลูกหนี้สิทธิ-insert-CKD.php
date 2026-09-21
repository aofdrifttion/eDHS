<?php
require 'vendor/autoload.php';
require './database_config/config.php';
require_once './database_config/db_helper.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

@ob_clean();
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

$allowed_extensions = ['xlsx', 'xls', 'zip', 'xml', 'csv'];
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
        if ($file_extension === 'zip') {
            $zip = new ZipArchive;
            if ($zip->open($file) === TRUE) {
                $foundXml = null;
                $foundHtm = null;
                for ($z = 0; $z < $zip->numFiles; $z++) {
                    $zName = $zip->getNameIndex($z);
                    if (preg_match('/\.xml$/i', $zName) && !preg_match('/sum/i', $zName)) {
                        $foundXml = $zName;
                        break;
                    } elseif (preg_match('/\.xml$/i', $zName) && !$foundXml) {
                        $foundXml = $zName;
                    } elseif (preg_match('/\.htm$/i', $zName)) {
                        $foundHtm = $zName;
                    }
                }

                if ($foundXml) {
                    $xmlContent = $zip->getFromName($foundXml);
                    $zip->close();
                    $utf8 = iconv(mb_detect_encoding($xmlContent, mb_detect_order(), true) ?: 'UTF-8', "UTF-8//IGNORE", $xmlContent);
                    $xml = simplexml_load_string($utf8);
                    if ($xml && isset($xml->TBills->TBill)) {
                        $stm_doc = trim((string)($xml->STMdoc ?? ''));
                        $data = [];
                        $data[] = ["no", "yearbudget", "rep", "id", "vn", "pid", "hn", "ptname", "admdate", "dchdate", "pttypename", "subfund", "collected", "compensated", "paidtype", "fund"];
                        $xNo = 1;
                        foreach ($xml->TBills->TBill as $tbill) {
                            $xHn = trim((string)$tbill->hn);
                            $xPtname = trim((string)$tbill->namepat);
                            $xDttran = trim((string)$tbill->dttran);
                            $dt = new DateTime($xDttran);
                            $y = (int)$dt->format('Y') + 543;
                            $m = (int)$dt->format('m');
                            $d = $dt->format('d');
                            $xAdmdate = sprintf('%02d/%02d/%04d', (int)$d, $m, $y);
                            $xYearbudget = ($m >= 10) ? ($y + 1) : $y;
                            $xCollected = (float)$tbill->amount;
                            $xCompensated = (float)$tbill->paid;
                            $xInvno = trim((string)$tbill->invno);
                            $data[] = [
                                (string)$xNo++, (string)$xYearbudget, $stm_doc, $xInvno, $xInvno, '', $xHn, $xPtname,
                                $xAdmdate, $xAdmdate, 'ฟอกไต', 'HD', (string)$xCollected, (string)$xCompensated, 'โอนเงิน', $fund
                            ];
                        }
                    }
                } elseif ($foundHtm) {
                    $htmContent = $zip->getFromName($foundHtm);
                    $zip->close();
                    $htmUtf8 = iconv('Windows-874', 'UTF-8//IGNORE', $htmContent);
                    $docNo = '';
                    if (preg_match('/เลขที่เอกสาร\s*[:\s]*([0-9a-zA-Z_]+)/u', $htmUtf8, $m)) {
                        $docNo = $m[1];
                    } elseif (preg_match('/11072_SOCDSTM_[0-9]+/u', $htmUtf8, $m)) {
                        $docNo = $m[0];
                    }
                    $totalCases = 0;
                    if (preg_match('/จำนวนรายการทั้งสิ้น.*?([0-9]+)/us', $htmUtf8, $m)) {
                        $totalCases = (int)$m[1];
                    }
                    if ($totalCases === 0) {
                        echo json_encode([
                            "status" => "success",
                            "message" => "นำเข้าข้อมูลสำเร็จ ✅ (เอกสาร $docNo ตรวจสอบเรียบร้อย: มียอดเบิก 0 รายการ 0.00 บาท)",
                            "success_count" => 0,
                            "error_count" => 0,
                            "updated_rows" => 0,
                            "progress" => 100
                        ]);
                        exit;
                    }
                } else {
                    $zip->close();
                }
            }
        } elseif ($file_extension === 'xml') {
            $xmlContent = file_get_contents($file);
            $utf8 = iconv(mb_detect_encoding($xmlContent, mb_detect_order(), true) ?: 'UTF-8', "UTF-8//IGNORE", $xmlContent);
            $xml = simplexml_load_string($utf8);
            if ($xml && isset($xml->TBills->TBill)) {
                $stm_doc = trim((string)($xml->STMdoc ?? ''));
                $data = [];
                $data[] = ["no", "yearbudget", "rep", "id", "vn", "pid", "hn", "ptname", "admdate", "dchdate", "pttypename", "subfund", "collected", "compensated", "paidtype", "fund"];
                $xNo = 1;
                foreach ($xml->TBills->TBill as $tbill) {
                    $xHn = trim((string)$tbill->hn);
                    if (!empty($xHn) && is_numeric($xHn) && strlen($xHn) <= 9) {
                        $xHn = str_pad($xHn, 9, '0', STR_PAD_LEFT);
                    }
                    $xPtname = trim((string)$tbill->namepat);
                    $xDttran = trim((string)$tbill->dttran);
                    $dt = new DateTime($xDttran);
                    $y = (int)$dt->format('Y') + 543;
                    $m = (int)$dt->format('m');
                    $d = $dt->format('d');
                    $xAdmdate = sprintf('%02d/%02d/%04d', (int)$d, $m, $y);
                    $xYearbudget = ($m >= 10) ? ($y + 1) : $y;
                    $xCollected = (float)$tbill->amount;
                    $xCompensated = (float)$tbill->paid;
                    $xInvno = trim((string)$tbill->invno);
                    $data[] = [
                        (string)$xNo++, (string)$xYearbudget, $stm_doc, $xInvno, $xInvno, '', $xHn, $xPtname,
                        $xAdmdate, $xAdmdate, 'ฟอกไต', 'HD', (string)$xCollected, (string)$xCompensated, 'โอนเงิน', $fund
                    ];
                }
            }
        } elseif ($file_extension === 'csv') {
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

        // Auto-detect official NHSO DCKD workbook format vs legacy flat format
        $isLegacyHeader = in_array('hn', array_map('strtolower', array_map('trim', array_map('strval', $data[0] ?? []))));

        if (!$isLegacyHeader) {
            $dckdHeaderIndex = -1;
            for ($rowIdx = 0; $rowIdx < min(15, count($data)); $rowIdx++) {
                $rowValues = array_map('trim', array_map('strval', $data[$rowIdx] ?? []));
                foreach ($rowValues as $val) {
                    if (preg_match('/rep\s*no/i', $val) || preg_match('/trans\s*id/i', $val)) {
                        $dckdHeaderIndex = $rowIdx;
                        break 2;
                    }
                }
            }

            if ($dckdHeaderIndex !== -1) {
                $normalizedData = [];
                $headerRow = [
                    "no", "yearbudget", "rep", "id", "vn", "pid", "hn", "ptname", 
                    "admdate", "dchdate", "pttypename", "subfund", "collected", "compensated", "paidtype", "fund"
                ];
                $normalizedData[] = $headerRow;

                // Pre-build PID to HN map so merged rows with blank HN in DCKD workbooks are restored
                $pid_to_hn_map = [];
                for ($chkR = $dckdHeaderIndex + 1; $chkR < count($data); $chkR++) {
                    $chkRow = $data[$chkR] ?? [];
                    $chkPid = trim((string)($chkRow[6] ?? ''));
                    $chkHn = trim((string)($chkRow[4] ?? ''));
                    if (!empty($chkPid) && !empty($chkHn)) {
                        if (is_numeric($chkHn) && strlen($chkHn) <= 9) {
                            $chkHn = str_pad($chkHn, 9, '0', STR_PAD_LEFT);
                        }
                        $pid_to_hn_map[$chkPid] = $chkHn;
                    }
                }

                for ($r = $dckdHeaderIndex + 1; $r < count($data); $r++) {
                    $row = $data[$r] ?? [];
                    $no = trim((string)($row[0] ?? ''));
                    if (!is_numeric($no)) continue;

                    $rep = trim((string)($row[2] ?? ''));
                    $id = trim((string)($row[3] ?? ''));
                    $hn = trim((string)($row[4] ?? ''));
                    if (!empty($hn) && is_numeric($hn) && strlen($hn) <= 9) {
                        $hn = str_pad($hn, 9, '0', STR_PAD_LEFT);
                    }
                    $an = trim((string)($row[5] ?? ''));
                    $pid = trim((string)($row[6] ?? ''));
                    $ptname = trim((string)($row[7] ?? ''));

                    // คืนค่า HN ให้กับแถวถัดมาของผู้ป่วยคนเดิม (กรณี NHSO ละเว้น HN ในแถวถัดไป)
                    if (empty($hn) && !empty($pid) && isset($pid_to_hn_map[$pid])) {
                        $hn = $pid_to_hn_map[$pid];
                    }
                    $pttype = trim((string)($row[8] ?? ''));
                    $admdate = trim((string)($row[10] ?? ''));
                    $dchdate = trim((string)($row[11] ?? ''));
                    $subfund = trim((string)($row[13] ?? ''));
                    $collected = (float)str_replace(',', '', (string)($row[15] ?? '0'));
                    $compensated = (float)str_replace(',', '', (string)($row[16] ?? '0'));
                    $paidtype = trim((string)($row[18] ?? ''));

                    $vn = (!empty($an) && $an !== '-') ? $an : $id;

                    // แปลง admdate และ dchdate จาก ค.ศ. เป็น พ.ศ. (DD/MM/YYYY) ให้ตรงกับ vstdate ในตารางลูกหนี้ eDHS
                    $yearbudget = (int)date('Y') + 543;
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $admdate, $dm)) {
                        $d = str_pad($dm[1], 2, '0', STR_PAD_LEFT);
                        $m = str_pad($dm[2], 2, '0', STR_PAD_LEFT);
                        $y = (int)$dm[3];
                        if ($y < 2400) $y += 543;
                        $admdate = "$d/$m/$y";
                        $yearbudget = ($m >= 10) ? ($y + 1) : $y;
                    }
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dchdate, $dm)) {
                        $d = str_pad($dm[1], 2, '0', STR_PAD_LEFT);
                        $m = str_pad($dm[2], 2, '0', STR_PAD_LEFT);
                        $y = (int)$dm[3];
                        if ($y < 2400) $y += 543;
                        $dchdate = "$d/$m/$y";
                    }

                    $normalizedData[] = [
                        $no, (string)$yearbudget, $rep, $id, $vn, $pid, $hn, $ptname,
                        $admdate, $dchdate, $pttype, $subfund, (string)$collected, (string)$compensated, $paidtype, $fund
                    ];
                }
                $data = $normalizedData;
            }
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
        $required_keywords = ['_SOCD', 'SOCD', 'DCKD', 'COCD', 'HD'];
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
                        } elseif ($col === 'hn' && !empty($val) && is_numeric($val) && strlen($val) <= 9) {
                            $val = str_pad($val, 9, '0', STR_PAD_LEFT);
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
            // Pass 1: Match by HN + Date (Lightning fast exact match)
            $update_sql1 = "
                UPDATE imr_tb_seamless_dckd AS dckd
                JOIN imr_tb_debtor_rights_opd AS opd
                  ON dckd.admdate = opd.vstdate
                  AND dckd.hn = opd.hn
                SET 
                  dckd.vn = opd.vn,
                  dckd.pttypename = opd.pttypename,
                  dckd.pttype_eclaim_name = opd.pttype_eclaim_name,
                  dckd.accountcode = opd.accountcode,
                  dckd.accountname = opd.accountname
                WHERE 
                  dckd.rep = ?
                  AND dckd.hn IS NOT NULL AND dckd.hn != ''
                  AND (
                    opd.pttypename LIKE '%ฟอกไต%' 
                    OR opd.pttypename LIKE '%ไต%'
                  )
            ";
            $stmt_update1 = $conn->prepare($update_sql1);
            $stmt_update1->bind_param("s", $rep_value);
            $stmt_update1->execute();
            $updated_rows += $stmt_update1->affected_rows;
            $stmt_update1->close();

            // Pass 2: Match by ptname + Date (เฉพาะรายการที่ยังไม่ได้จับคู่)
            $update_sql2 = "
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
                  AND (dckd.vn IS NULL OR dckd.vn = '' OR dckd.vn = dckd.id)
                  AND (
                    opd.pttypename LIKE '%ฟอกไต%' 
                    OR opd.pttypename LIKE '%ไต%'
                  )
            ";
            $stmt_update2 = $conn->prepare($update_sql2);
            $stmt_update2->bind_param("s", $rep_value);
            $stmt_update2->execute();
            $updated_rows += $stmt_update2->affected_rows;
            $stmt_update2->close();
        }

        $total_success += $success_count;
        $total_error += $error_count;
        $total_updated += $updated_rows;

        // Function แปลงวันที่ พ.ศ. ➜ ค.ศ. แบบ yyyy-mm-dd (รองรับทั้ง พ.ศ. และ ค.ศ.)
        if (!function_exists('convertThaiDateToMysql')) {
            function convertThaiDateToMysql($thaiDate) {
                $parts = explode('/', trim((string)$thaiDate));
                if (count($parts) === 3) {
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $year = (int)$parts[2];
                    if ($year >= 2400) $year -= 543;
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
                return null;
            }
        }

        // Step 2: Update VN จากวันที่ใกล้เคียงก่อนหน้า (ถ้ายังอัปเดตไม่ได้)
        $fetch_missing_sql = "
            SELECT dckd.no, dckd.hn, dckd.ptname, dckd.admdate
            FROM imr_tb_seamless_dckd AS dckd
            WHERE (dckd.vn IS NULL OR dckd.vn = '' OR dckd.vn = dckd.id)
              AND dckd.rep = ?
        ";
        $stmt_missing = $conn->prepare($fetch_missing_sql);
        $stmt_missing->bind_param("s", $rep_value);
        $stmt_missing->execute();
        $result = $stmt_missing->get_result();

        while ($row = $result->fetch_assoc()) {
            $no = $row['no'];
            $hn = trim((string)($row['hn'] ?? ''));
            $ptname = trim((string)($row['ptname'] ?? ''));

            $vn_row = null;
            if (!empty($hn)) {
                $stmt_hn = $conn->prepare("
                    SELECT vn, vstdate, pttypename, pttype_eclaim_name, accountcode, accountname
                    FROM imr_tb_debtor_rights_opd
                    WHERE hn = ? AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%')
                    ORDER BY vn DESC LIMIT 1
                ");
                $stmt_hn->bind_param("s", $hn);
                $stmt_hn->execute();
                $vn_res = $stmt_hn->get_result();
                $vn_row = $vn_res->fetch_assoc();
                $stmt_hn->close();
            }

            if (!$vn_row && !empty($ptname)) {
                $stmt_vn = $conn->prepare("
                    SELECT vn, vstdate, pttypename, pttype_eclaim_name, accountcode, accountname
                    FROM imr_tb_debtor_rights_opd
                    WHERE (ptname LIKE CONCAT('%', ?) OR ? LIKE CONCAT('%', ptname))
                      AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%')
                    ORDER BY vn DESC LIMIT 1
                ");
                $stmt_vn->bind_param("ss", $ptname, $ptname);
                $stmt_vn->execute();
                $vn_result = $stmt_vn->get_result();
                $vn_row = $vn_result->fetch_assoc();
                $stmt_vn->close();
            }

            if ($vn_row) {
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
                $stmt_update_missing->close();
            }
        }
        $stmt_missing->close();



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
