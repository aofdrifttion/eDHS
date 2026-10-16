<?php
/**
 * Rehab & Medical Device Compensation Tracker Plugin
 * API: Statement (STM) Auto-Matching & Multi-Fund Importer
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/plugin_config.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'auto_match_db';
$response = [
    'status' => 'error',
    'message' => '',
    'matched_count' => 0,
    'updated_count' => 0,
    'total_checked' => 0
];

if ($action === 'auto_match_db') {
    // 1. Auto-Match against eDHS imr_tb_check_invoice table
    $where_claims = "1=1";
    if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
        $s = mysqli_real_escape_string($conn, $_REQUEST['start_date']);
        $e = mysqli_real_escape_string($conn, $_REQUEST['end_date']);
        $where_claims .= " AND r.vstdate BETWEEN '$s' AND '$e'";
    }
    if (!empty($_REQUEST['fund_type']) && $_REQUEST['fund_type'] !== 'ALL') {
        $f = mysqli_real_escape_string($conn, $_REQUEST['fund_type']);
        $where_claims .= " AND r.fund_type = '$f'";
    }

    $sql_match = "UPDATE `imr_tb_rehab_device_claims` r
    JOIN (
        SELECT 
            vn,
            SUM(CAST(compensated AS DECIMAL(12,2))) AS compensated,
            SUM(CAST(inst AS DECIMAL(12,2))) AS inst,
            SUM(CAST(diff AS DECIMAL(12,2))) AS diff,
            GROUP_CONCAT(DISTINCT rep SEPARATOR ', ') AS rep,
            GROUP_CONCAT(DISTINCT errorcode SEPARATOR ', ') AS errorcode,
            GROUP_CONCAT(DISTINCT docno SEPARATOR ', ') AS docno
        FROM `imr_tb_check_invoice`
        WHERE vn IS NOT NULL AND vn != ''
        GROUP BY vn
    ) c ON (r.vn = c.vn OR (r.vn IS NULL AND r.an IS NOT NULL AND r.an = c.vn))
    SET 
        r.compensated_amount = CASE 
            WHEN c.inst > 0 THEN c.inst
            WHEN c.compensated > 0 THEN c.compensated
            WHEN (r.receipt_no IS NOT NULL AND r.receipt_no != '' AND r.receipt_no NOT IN ('\"\"', '\'\'', '-')) AND r.compensated_amount > 0 THEN r.compensated_amount
            ELSE 0 
        END,
        r.diff_amount = GREATEST(0, r.claim_amount - (CASE 
            WHEN c.inst > 0 THEN c.inst
            WHEN c.compensated > 0 THEN c.compensated
            WHEN (r.receipt_no IS NOT NULL AND r.receipt_no != '' AND r.receipt_no NOT IN ('\"\"', '\'\'', '-')) AND r.compensated_amount > 0 THEN r.compensated_amount
            ELSE 0 
        END)),
        r.claim_status = CASE 
            WHEN (CASE WHEN c.inst > 0 THEN c.inst WHEN c.compensated > 0 THEN c.compensated ELSE 0 END) >= r.claim_amount THEN 'PAID'
            WHEN (CASE WHEN c.inst > 0 THEN c.inst WHEN c.compensated > 0 THEN c.compensated ELSE 0 END) > 0 THEN 'PARTIAL'
            WHEN (r.compensated_amount >= r.claim_amount AND r.receipt_no IS NOT NULL AND r.receipt_no != '' AND r.receipt_no NOT IN ('\"\"', '\'\'', '-')) THEN 'PAID'
            WHEN (r.compensated_amount > 0 AND r.receipt_no IS NOT NULL AND r.receipt_no != '' AND r.receipt_no NOT IN ('\"\"', '\'\'', '-')) THEN 'PARTIAL'
            WHEN (c.errorcode IS NOT NULL AND c.errorcode != '' AND c.errorcode != '0') THEN 'REJECTED'
            WHEN (c.rep IS NOT NULL AND c.rep != '') OR (c.docno IS NOT NULL AND c.docno != '') THEN 'WAIT_STM'
            ELSE 'UNCLAIMED'
        END,
        r.rep_no = IF(c.rep IS NOT NULL AND c.rep != '' AND c.rep NOT IN ('\"\"', '\'\'', '-'), c.rep, r.rep_no),
        r.stm_docno = IF(c.docno IS NOT NULL AND c.docno != '' AND c.docno NOT IN ('\"\"', '\'\'', '-'), c.docno, r.stm_docno),
        r.denial_code = IF(c.errorcode IS NOT NULL AND c.errorcode != '' AND c.errorcode != '0', c.errorcode, r.denial_code),
        r.error_codes = c.errorcode
    WHERE $where_claims";

    $match_res = mysqli_query($conn, $sql_match);
    if ($match_res) {
        $affected = mysqli_affected_rows($conn);

        // Auto-update denial messages with Thai description and type from Master 933 Codes
        $sql_update_desc = "UPDATE `imr_tb_rehab_device_claims` r
        JOIN `imr_tb_rehab_denial_codes` d ON (
            r.denial_code = d.denial_code 
            OR CONCAT('D', r.denial_code) = d.denial_code 
            OR CONCAT('C', r.denial_code) = d.denial_code
            OR REPLACE(REPLACE(r.denial_code, 'D', ''), 'C', '') = d.denial_code
        )
        SET r.denial_message = CONCAT('[', COALESCE(d.deny_type, 'Deny'), '] ', d.description_th)
        WHERE $where_claims
          AND r.denial_code IS NOT NULL AND r.denial_code != '' AND r.denial_code != '0'";
        mysqli_query($conn, $sql_update_desc);

        // Synchronize Debtor Rights Receipts (OPD/IPD)
        $debtor_updated = 0;
        if (function_exists('rehab_sync_debtor_rights')) {
            $debtor_updated = rehab_sync_debtor_rights($conn, $where_claims);
        }

        $response['status'] = 'success';
        $response['matched_count'] = $affected;
        $response['matched_debtor_count'] = $debtor_updated;
        $response['message'] = "จับคู่ผลการชดเชยจาก Statement สำเร็จ (อัปเดต $affected รายการ) และตรวจสอบใบเสร็จจากลูกหนี้สิทธิ OPD/IPD สำเร็จ (อัปเดต $debtor_updated รายการ)";
    } else {
        $response['message'] = 'เกิดข้อผิดพลาดในการรันคำสั่งจับคู่: ' . mysqli_error($conn);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'sync_debtor_receipts') {
    $where_claims = "1=1";
    if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
        $s = mysqli_real_escape_string($conn, $_REQUEST['start_date']);
        $e = mysqli_real_escape_string($conn, $_REQUEST['end_date']);
        $where_claims .= " AND r.vstdate BETWEEN '$s' AND '$e'";
    }
    if (!empty($_REQUEST['fund_type']) && $_REQUEST['fund_type'] !== 'ALL') {
        $f = mysqli_real_escape_string($conn, $_REQUEST['fund_type']);
        $where_claims .= " AND r.fund_type = '$f'";
    }

    $debtor_updated = 0;
    if (function_exists('rehab_sync_debtor_rights')) {
        $debtor_updated = rehab_sync_debtor_rights($conn, $where_claims);
    }

    echo json_encode([
        'status' => 'success',
        'matched_debtor_count' => $debtor_updated,
        'message' => "ตรวจสอบและดึงเลขที่ใบเสร็จจากลูกหนี้สิทธิ OPD/IPD สำเร็จ $debtor_updated รายการ"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


if ($action === 'upload_stm_file') {
    // 2. Handle File Upload (Excel or CSV)
    if (!isset($_FILES['stm_file']) || $_FILES['stm_file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'ไม่พบไฟล์หรือการอัปโหลดไฟล์ล้มเหลว';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file_tmp = $_FILES['stm_file']['tmp_name'];
    $file_name = $_FILES['stm_file']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $rows = [];

    // Try PhpSpreadsheet first if available
    $autoload_path = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
    if (file_exists($autoload_path) && in_array($file_ext, ['xlsx', 'xls'])) {
        require_once $autoload_path;
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_tmp);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Exception $e) {
            // fallback
        }
    }

    // Fallback to SimpleXLSX
    if (empty($rows) && $file_ext === 'xlsx') {
        $xlsx_path = dirname(dirname(__DIR__)) . '/xlsx-master/xlsx.php';
        if (file_exists($xlsx_path)) {
            require_once $xlsx_path;
            if ($xlsx = SimpleXLSX::parse($file_tmp)) {
                $rows = $xlsx->rows();
            }
        }
    }

    // Fallback to CSV
    if (empty($rows) && in_array($file_ext, ['csv', 'txt'])) {
        if (($handle = fopen($file_tmp, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }
    }

    if (empty($rows)) {
        $response['message'] = 'ไม่สามารถอ่านข้อมูลจากไฟล์ที่อัปโหลดได้ โปรดตรวจสอบนามสกุลไฟล์ (.xlsx, .xls, .csv)';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Auto-detect header columns
    $header_idx = -1;
    $col_vn = -1;
    $col_hn = -1;
    $col_paid = -1;
    $col_error = -1;
    $col_rep = -1;
    $col_docno = -1;
    $col_date = -1;

    for ($i = 0; $i < min(15, count($rows)); $i++) {
        $r = $rows[$i];
        foreach ($r as $ci => $val) {
            $txt = trim(mb_strtolower(strval($val)));
            if ($col_vn === -1 && (strpos($txt, 'vn') !== false || strpos($txt, 'เลขที่ใบเคลม') !== false || strpos($txt, 'รหัสการรับบริการ') !== false)) {
                $col_vn = $ci;
            }
            if ($col_hn === -1 && (strpos($txt, 'hn') !== false || strpos($txt, 'เลขประจำตัวผู้ป่วย') !== false)) {
                $col_hn = $ci;
            }
            if ($col_paid === -1 && (strpos($txt, 'ชดเชย') !== false || strpos($txt, 'paid') !== false || strpos($txt, 'พึงรับ') !== false || strpos($txt, 'inst') !== false || strpos($txt, 'อุปกรณ์') !== false)) {
                $col_paid = $ci;
            }
            if ($col_error === -1 && (strpos($txt, 'error') !== false || strpos($txt, 'ปฏิเสธ') !== false || strpos($txt, 'denial') !== false || strpos($txt, 'สาเหตุ') !== false)) {
                $col_error = $ci;
            }
            if ($col_rep === -1 && (strpos($txt, 'rep') !== false || strpos($txt, 'รอบ') !== false)) {
                $col_rep = $ci;
            }
            if ($col_docno === -1 && (strpos($txt, 'docno') !== false || strpos($txt, 'เลขที่เอกสาร') !== false || strpos($txt, 'stm') !== false)) {
                $col_docno = $ci;
            }
        }
        if ($col_vn !== -1 || ($col_hn !== -1 && $col_paid !== -1)) {
            $header_idx = $i;
            break;
        }
    }

    if ($header_idx === -1 || $col_vn === -1) {
        // Fallback default index if standard e-Claim STM table
        $col_vn = 5;
        $col_hn = 7;
        $col_paid = 20;
        $col_error = 16;
        $col_rep = 2;
        $col_docno = 3;
        $header_idx = 0;
    }

    $matched = 0;
    $updated = 0;

    for ($i = $header_idx + 1; $i < count($rows); $i++) {
        $r = $rows[$i];
        $vn = isset($r[$col_vn]) ? trim(strval($r[$col_vn])) : '';
        if (empty($vn) || !is_numeric($vn)) continue;

        $paid = ($col_paid !== -1 && isset($r[$col_paid])) ? floatval(str_replace(',', '', $r[$col_paid])) : 0.0;
        $err  = ($col_error !== -1 && isset($r[$col_error])) ? trim(strval($r[$col_error])) : '';
        $rep  = ($col_rep !== -1 && isset($r[$col_rep])) ? trim(strval($r[$col_rep])) : '';
        $doc  = ($col_docno !== -1 && isset($r[$col_docno])) ? trim(strval($r[$col_docno])) : '';

        $matched++;

        // Determine Status
        $status = 'UNCLAIMED';
        if ($paid > 0) {
            $status = 'PAID';
        } elseif (!empty($err) && $err !== '0') {
            $status = 'REJECTED';
        } elseif (!empty($rep) || !empty($doc)) {
            $status = 'WAIT_STM';
        }

        $vn_esc = mysqli_real_escape_string($conn, $vn);
        $err_esc = mysqli_real_escape_string($conn, $err);
        $rep_esc = mysqli_real_escape_string($conn, $rep);
        $doc_esc = mysqli_real_escape_string($conn, $doc);

        $sql_up = "UPDATE `imr_tb_rehab_device_claims`
        SET 
            compensated_amount = IF($paid > 0, $paid, compensated_amount),
            diff_amount = GREATEST(0, claim_amount - IF($paid > 0, $paid, compensated_amount)),
            claim_status = IF(receipt_no IS NOT NULL AND receipt_no != '' AND receipt_no NOT IN ('\"\"', '\'\'', '-') AND compensated_amount >= claim_amount, 'PAID', IF('$status' != 'UNCLAIMED', '$status', claim_status)),
            rep_no = IF('$rep_esc' != '', '$rep_esc', rep_no),
            stm_docno = IF('$doc_esc' != '', '$doc_esc', stm_docno),
            denial_code = IF('$err_esc' != '' AND '$err_esc' != '0', '$err_esc', denial_code)
        WHERE vn = '$vn_esc'";

        if (mysqli_query($conn, $sql_up)) {
            if (mysqli_affected_rows($conn) > 0) {
                $updated++;
            }
        }
    }

    $response['status'] = 'success';
    $response['matched_count'] = $matched;
    $response['updated_count'] = $updated;
    $response['total_checked'] = count($rows);
    $response['message'] = "นำเข้าไฟล์ Statement สำเร็จ: ตรวจพบ $matched เคส (ปรับปรุงยอดชดเชยและสถานะในระบบ $updated เคส)";

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$response['message'] = 'ไม่ระบุคำสั่งการทำงาน (Invalid Action)';
echo json_encode($response, JSON_UNESCAPED_UNICODE);
