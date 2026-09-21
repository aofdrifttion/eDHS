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
        $clean_hn = trim((string)$row[2]);
        $hn = (is_numeric($clean_hn) && strlen($clean_hn) <= 9) ? str_pad($clean_hn, 9, '0', STR_PAD_LEFT) : $clean_hn;
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

    $valid_keywords = ['LGO-HD', 'LGO_HD', 'LGOHD', 'LGO', 'HD'];
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
        // Pass 1: Match by HN + Date (ความเร็วสูง ดึงผ่าน B-Tree Index ตรงๆ)
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
                OR opd.accountcode LIKE '%.801%'
                OR opd.accountcode LIKE '%.308%'
              )
        ";
        if ($stmt_update1 = $conn->prepare($update_sql1)) {
            $stmt_update1->bind_param("s", $rep_value);
            if ($stmt_update1->execute()) {
                $updated_rows += $stmt_update1->affected_rows;
            }
            $stmt_update1->close();
        }

        // Pass 2: Match by ptname + Date (เฉพาะรายการที่ยังไม่แมตช์)
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
                OR opd.accountcode LIKE '%.801%'
                OR opd.accountcode LIKE '%.308%'
              )
        ";
        if ($stmt_update2 = $conn->prepare($update_sql2)) {
            $stmt_update2->bind_param("s", $rep_value);
            if ($stmt_update2->execute()) {
                $updated_rows += $stmt_update2->affected_rows;
            }
            $stmt_update2->close();
        }

        // Step 2: Fallback เก็บตกรายการที่ยังไม่มี VN จากวันที่บริการล่าสุด
        $fetch_missing_sql = "
            SELECT dckd.no, dckd.hn, dckd.ptname, dckd.admdate
            FROM imr_tb_seamless_dckd AS dckd
            WHERE (dckd.vn IS NULL OR dckd.vn = '' OR dckd.vn = dckd.id)
              AND dckd.rep = ?
        ";
        if ($stmt_missing = $conn->prepare($fetch_missing_sql)) {
            $stmt_missing->bind_param("s", $rep_value);
            $stmt_missing->execute();
            $res_missing = $stmt_missing->get_result();

            while ($mRow = $res_missing->fetch_assoc()) {
                $mNo = $mRow['no'];
                $mHn = trim((string)($mRow['hn'] ?? ''));
                $mPtname = trim((string)($mRow['ptname'] ?? ''));
                $vn_row = null;

                if (!empty($mHn)) {
                    $sHn = $conn->prepare("
                        SELECT vn, vstdate, pttypename, pttype_eclaim_name, accountcode, accountname
                        FROM imr_tb_debtor_rights_opd
                        WHERE hn = ? AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%' OR accountcode LIKE '%.801%' OR accountcode LIKE '%.308%')
                        ORDER BY vn DESC LIMIT 1
                    ");
                    $sHn->bind_param("s", $mHn);
                    $sHn->execute();
                    $vn_row = $sHn->get_result()->fetch_assoc();
                    $sHn->close();
                }

                if (!$vn_row && !empty($mPtname)) {
                    $sNm = $conn->prepare("
                        SELECT vn, vstdate, pttypename, pttype_eclaim_name, accountcode, accountname
                        FROM imr_tb_debtor_rights_opd
                        WHERE (ptname LIKE CONCAT('%', ?) OR ? LIKE CONCAT('%', ptname))
                          AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%' OR accountcode LIKE '%.801%' OR accountcode LIKE '%.308%')
                        ORDER BY vn DESC LIMIT 1
                    ");
                    $sNm->bind_param("ss", $mPtname, $mPtname);
                    $sNm->execute();
                    $vn_row = $sNm->get_result()->fetch_assoc();
                    $sNm->close();
                }

                if ($vn_row) {
                    $sUp = $conn->prepare("
                        UPDATE imr_tb_seamless_dckd 
                        SET vn = ?, pttypename = ?, pttype_eclaim_name = ?, accountcode = ?, accountname = ?
                        WHERE no = ? AND rep = ?
                    ");
                    $sUp->bind_param("sssssis", $vn_row['vn'], $vn_row['pttypename'], $vn_row['pttype_eclaim_name'], $vn_row['accountcode'], $vn_row['accountname'], $mNo, $rep_value);
                    $sUp->execute();
                    $sUp->close();
                    $updated_rows++;
                }
            }
            $stmt_missing->close();
        }
    }

    $success_count += $success;
    $total_updated += $updated_rows;
}

if (function_exists('system_log')) {
    system_log($conn, 'นำเข้าลูกหนี้สิทธิ', 'INSERT', "นำเข้าข้อมูล CKDLGO (กองทุน: $fund) สำเร็จ (เพิ่ม: $success_count, อัปเดต: $total_updated)");
}

echo json_encode([
    "status" => "success",
    "message" => "✅ นำเข้าข้อมูลสำเร็จ",
    "success_count" => $success_count,
    "error_count" => 0,
    "updated_rows" => $total_updated,
]);


?>


