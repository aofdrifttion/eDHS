<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';

// ฟังก์ชันแสดงข้อความ Error
function showError($message, $redirect = 'นำเข้าstm.php') {
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'redirect' => $redirect
    ]);
    exit;
}

// ฟังก์ชันลบโฟลเดอร์ชั่วคราว
function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

// ฟังก์ชันประมวลผลไฟล์ XML
function processXML($xmlFile, $conn) {
    if (\PHP_VERSION_ID < 80000) {
        @libxml_disable_entity_loader(true);
    }
    libxml_use_internal_errors(true);

    $contents = file_get_contents($xmlFile);
    $utf8 = iconv(mb_detect_encoding($contents, mb_detect_order(), true), "UTF-8", $contents);
    $xml = simplexml_load_string($utf8);

    if ($xml === false) {
        showError("ไม่สามารถอ่านไฟล์ XML ได้");
    }

    if (!isset($xml->TBills->TBill)) return;

    $stm_doc = trim((string)$xml->STMdoc);
    $amount_total = (float)$xml->amount;

    // 1. จัดการข้อมูลสรุป (Summary)
    $check_summary = "SELECT COUNT(*) FROM imr_tb_seamless_dckd_summary_ofc WHERE stm_doc = ?";
    $stmt_sum = $conn->prepare($check_summary);
    $stmt_sum->bind_param("s", $stm_doc);
    $stmt_sum->execute();
    $stmt_sum->bind_result($sum_exists);
    $stmt_sum->fetch();
    $stmt_sum->close();

    if ($sum_exists > 0) {
        $sql_up_sum = "UPDATE imr_tb_seamless_dckd_summary_ofc SET stm_account_id = ?, hcode = ?, hname = ?, acc_period = ?, amount = ? WHERE stm_doc = ?";
        $stmt_up = $conn->prepare($sql_up_sum);
        $stmt_up->bind_param("ssssds", $xml->stmAccountID, $xml->hcode, $xml->hname, $xml->AccPeriod, $amount_total, $stm_doc);
        $stmt_up->execute();
        $stmt_up->close();
    } else {
        $sql_in_sum = "INSERT INTO imr_tb_seamless_dckd_summary_ofc (stm_account_id, hcode, hname, acc_period, stm_doc, amount) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_in = $conn->prepare($sql_in_sum);
        $stmt_in->bind_param("sssssd", $xml->stmAccountID, $xml->hcode, $xml->hname, $xml->AccPeriod, $stm_doc, $amount_total);
        $stmt_in->execute();
        $stmt_in->close();
    }

    // 2. นำเข้าข้อมูลรายบุคคล (TBill)
    foreach ($xml->TBills->TBill as $tbill) {
        $hn_raw = trim((string)$tbill->hn);
        $hn = (is_numeric($hn_raw) && strlen($hn_raw) <= 9) ? str_pad($hn_raw, 9, '0', STR_PAD_LEFT) : $hn_raw;
        $invno = (string)$tbill->invno;
        $dttran = (string)$tbill->dttran; // รูปแบบ YYYY-MM-DD
        
        // สร้าง VN จำลอง 10 หลัก (Placeholder)
        $date = new DateTime($dttran);
        $year_th = $date->format("Y") + 543;
        $temp_vn = substr($year_th, -2) . $date->format("mdHi");

        // เช็คซ้ำด้วย invno + stm_doc (แม่นยำที่สุด)
        $sql_check_bill = "SELECT COUNT(*) FROM imr_tb_seamless_dckd_ofc WHERE invno = ? AND stm_doc = ?";
        $stmt_cb = $conn->prepare($sql_check_bill);
        $stmt_cb->bind_param("ss", $invno, $stm_doc);
        $stmt_cb->execute();
        $stmt_cb->bind_result($bill_exists);
        $stmt_cb->fetch();
        $stmt_cb->close();

        if ($bill_exists > 0) {
            $sql_up_bill = "UPDATE imr_tb_seamless_dckd_ofc SET sys=?, station=?, hreg=?, hn=?, namepat=?, dttran=?, amount=?, paid=?, rid=?, hdflag=? WHERE invno=? AND stm_doc=?";
            $stmt_up_b = $conn->prepare($sql_up_bill);
            $stmt_up_b->bind_param("ssssssddssss", $tbill->sys, $tbill->station, $tbill->hreg, $hn, $tbill->namepat, $dttran, $tbill->amount, $tbill->paid, $tbill->rid, $tbill->HDflag, $invno, $stm_doc);
            $stmt_up_b->execute();
            $stmt_up_b->close();
        } else {
            $sql_in_bill = "INSERT INTO imr_tb_seamless_dckd_ofc (sys, station, hreg, hn, namepat, invno, dttran, amount, paid, rid, hdflag, vn, stm_doc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_in_b = $conn->prepare($sql_in_bill);
            $stmt_in_b->bind_param("sssssssddssss", $tbill->sys, $tbill->station, $tbill->hreg, $hn, $tbill->namepat, $invno, $dttran, $tbill->amount, $tbill->paid, $tbill->rid, $tbill->HDflag, $temp_vn, $stm_doc);
            $stmt_in_b->execute();
            $stmt_in_b->close();
        }
    }
}

// --- เริ่มการทำงานหลัก ---
if (!empty($_FILES['excel_file']['name'][0])) {
    foreach ($_FILES['excel_file']['tmp_name'] as $index => $tmpName) {
        $fileType = strtolower(pathinfo($_FILES['excel_file']['name'][$index], PATHINFO_EXTENSION));
        if ($fileType === "zip") {
            $zip = new ZipArchive;
            $extractPath = __DIR__ . '/temp_xml_' . uniqid() . '/';
            mkdir($extractPath, 0777, true);
            if ($zip->open($tmpName) === TRUE) {
                $zip->extractTo($extractPath);
                $zip->close();
                foreach (glob($extractPath . '*.xml') as $xmlPath) { processXML($xmlPath, $conn); }
                deleteDirectory($extractPath);
            }
        } elseif ($fileType === "xml") {
            processXML($tmpName, $conn);
        }
    }

    // 🔥 สเต็ป 1: Mass Update (จับคู่เคสที่วันที่และ HN ตรงกันเป๊ะก่อน เพื่อความเร็ว)
    $mass_update_sql = "
        UPDATE imr_tb_seamless_dckd_ofc AS ofc
        JOIN imr_tb_debtor_rights_opd AS opd
          ON ofc.hn = opd.hn 
          AND DATE(ofc.dttran) = DATE_SUB(STR_TO_DATE(opd.vstdate, '%d/%m/%Y'), INTERVAL 543 YEAR)
        SET ofc.vn = opd.vn
        WHERE (opd.pttypename LIKE '%ฟอกไต%' OR opd.pttypename LIKE '%ไต%')
          AND LENGTH(ofc.vn) < 12 
    ";
    $conn->query($mass_update_sql);

    // 🔥 สเต็ป 2: Refined Search (วนลูปเก็บตกเคส IPD, HN เปลี่ยน หรือเคสที่วันที่ไม่ตรงเป๊ะ)
    $fetch_sql = "SELECT id, hn, namepat, dttran, stm_doc FROM imr_tb_seamless_dckd_ofc WHERE LENGTH(vn) < 12";
    $res = $conn->query($fetch_sql);

    if ($res && $res->num_rows > 0) {
        // เตรียม Statement ไว้ล่วงหน้าลดภาระ DB: ค้นหาตาม HN
        $find_sql = "SELECT vn FROM imr_tb_debtor_rights_opd 
                     WHERE hn = ? 
                     AND DATE_SUB(STR_TO_DATE(vstdate, '%d/%m/%Y'), INTERVAL 543 YEAR) BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND ?
                     AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%')
                     ORDER BY ABS(DATEDIFF(?, DATE_SUB(STR_TO_DATE(vstdate, '%d/%m/%Y'), INTERVAL 543 YEAR))) ASC LIMIT 1";
        $stmt_find = $conn->prepare($find_sql);

        // ค้นหาสำรองตามชื่อผู้ป่วย (กรณี HN เปลี่ยน หรือโอนย้ายจาก รพ. อื่น)
        $find_by_name_sql = "SELECT vn FROM imr_tb_debtor_rights_opd 
                             WHERE (ptname LIKE CONCAT('%', ?) OR ? LIKE CONCAT('%', ptname))
                             AND DATE_SUB(STR_TO_DATE(vstdate, '%d/%m/%Y'), INTERVAL 543 YEAR) BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND ?
                             AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%')
                             ORDER BY ABS(DATEDIFF(?, DATE_SUB(STR_TO_DATE(vstdate, '%d/%m/%Y'), INTERVAL 543 YEAR))) ASC LIMIT 1";
        $stmt_find_name = $conn->prepare($find_by_name_sql);

        $up_vn_sql = "UPDATE imr_tb_seamless_dckd_ofc SET vn = ? WHERE id = ? AND stm_doc = ?";
        $stmt_up_vn = $conn->prepare($up_vn_sql);

        while ($row = $res->fetch_assoc()) {
            $dt_mysql = (new DateTime($row['dttran']))->format('Y-m-d');
            $v_row = null;

            if (!empty($row['hn'])) {
                $stmt_find->bind_param("ssss", $row['hn'], $dt_mysql, $dt_mysql, $dt_mysql);
                $stmt_find->execute();
                $find_res = $stmt_find->get_result();
                $v_row = $find_res->fetch_assoc();
            }

            if (!$v_row && !empty($row['namepat'])) {
                $stmt_find_name->bind_param("sssss", $row['namepat'], $row['namepat'], $dt_mysql, $dt_mysql, $dt_mysql);
                $stmt_find_name->execute();
                $find_name_res = $stmt_find_name->get_result();
                $v_row = $find_name_res->fetch_assoc();
            }

            if ($v_row) {
                $stmt_up_vn->bind_param("sis", $v_row['vn'], $row['id'], $row['stm_doc']);
                $stmt_up_vn->execute();
            }
        }
        $stmt_find->close();
        $stmt_find_name->close();
        $stmt_up_vn->close();
    }

    if (function_exists('system_log')) {
        system_log($conn, 'นำเข้าลูกหนี้สิทธิ', 'INSERT', "นำเข้าข้อมูล CKD (OFC/XML) สำเร็จและจับคู่ VN แล้ว");
    }

    echo json_encode(['status' => 'success', 'message' => 'นำเข้าและจับคู่ VN เสร็จสมบูรณ์', 'redirect' => 'นำเข้าstm.php']);
} else {
    showError("กรุณาเลือกไฟล์เพื่ออัปโหลด");
}
?>
