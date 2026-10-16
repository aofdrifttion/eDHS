<?php
/**
 * Rehab & Medical Device Compensation Tracker Plugin
 * API: Denial Codes, Actionable Guidance & Resolution Management
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/plugin_config.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list_rejected_claims';
$response = [
    'status' => 'error',
    'message' => ''
];

if ($action === 'list_rejected_claims') {
    $where = "(r.claim_status = 'REJECTED' OR (r.denial_code IS NOT NULL AND r.denial_code != '' AND r.denial_code != '0'))";
    
    $fiscal_year = isset($_REQUEST['fiscal_year']) ? trim($_REQUEST['fiscal_year']) : '';
    $start_date  = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : '';
    $end_date    = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : '';

    if (!empty($fiscal_year) && $fiscal_year !== 'ALL') {
        $ce_year = intval($fiscal_year) - 543;
        $fy_start = ($ce_year - 1) . "-10-01";
        $fy_end   = $ce_year . "-09-30";
        $where .= " AND r.vstdate BETWEEN '$fy_start' AND '$fy_end'";
    } elseif (!empty($start_date) && !empty($end_date)) {
        $s_d = mysqli_real_escape_string($conn, $start_date);
        $e_d = mysqli_real_escape_string($conn, $end_date);
        $where .= " AND r.vstdate BETWEEN '$s_d' AND '$e_d'";
    }

    if (!empty($_REQUEST['fund_type']) && $_REQUEST['fund_type'] !== 'ALL') {
        $f = mysqli_real_escape_string($conn, $_REQUEST['fund_type']);
        $where .= " AND r.fund_type = '$f'";
    }
    if (!empty($_REQUEST['department_group']) && $_REQUEST['department_group'] !== 'ALL') {
        $dg = mysqli_real_escape_string($conn, $_REQUEST['department_group']);
        $where .= " AND r.department_group = '$dg'";
    }
    if (!empty($_REQUEST['category']) && $_REQUEST['category'] !== 'ALL') {
        $cat = trim($_REQUEST['category']);
        if ($cat === 'C_CODE') {
            $where .= " AND (r.denial_code LIKE 'C%' OR r.denial_code IN ('169','179','01','02','03'))";
        } elseif ($cat === 'D_CODE') {
            $where .= " AND (r.denial_code LIKE 'D%' AND r.denial_code NOT IN ('169','179'))";
        }
    }
    if (!empty($_REQUEST['search'])) {
        $s = mysqli_real_escape_string($conn, $_REQUEST['search']);
        $where .= " AND (r.hn LIKE '%$s%' OR r.cid LIKE '%$s%' OR r.ptname LIKE '%$s%' OR r.vn LIKE '%$s%' OR r.denial_code LIKE '%$s%')";
    }
    if (isset($_REQUEST['is_resubmitted']) && $_REQUEST['is_resubmitted'] !== '') {
        $resub = intval($_REQUEST['is_resubmitted']);
        $where .= " AND r.is_resubmitted = $resub";
    }

    // Summary Metrics across current filtered scope
    $sql_summary = "SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(r.claim_amount), 0) as total_amount,
        SUM(CASE WHEN (r.denial_code LIKE 'C%' OR r.denial_code IN ('169','179','01','02','03')) THEN 1 ELSE 0 END) as c_code_count,
        COALESCE(SUM(CASE WHEN (r.denial_code LIKE 'C%' OR r.denial_code IN ('169','179','01','02','03')) THEN r.claim_amount ELSE 0 END), 0) as c_code_amount,
        SUM(CASE WHEN (r.denial_code LIKE 'D%' AND r.denial_code NOT IN ('169','179')) THEN 1 ELSE 0 END) as d_code_count,
        COALESCE(SUM(CASE WHEN (r.denial_code LIKE 'D%' AND r.denial_code NOT IN ('169','179')) THEN r.claim_amount ELSE 0 END), 0) as d_code_amount,
        SUM(CASE WHEN r.is_resubmitted = 1 THEN 1 ELSE 0 END) as resubmitted_count,
        COALESCE(SUM(CASE WHEN r.is_resubmitted = 1 THEN r.claim_amount ELSE 0 END), 0) as resubmitted_amount
    FROM `imr_tb_rehab_device_claims` r
    WHERE $where";

    $sum_res = mysqli_query($conn, $sql_summary);
    $summary = ($sum_res) ? mysqli_fetch_assoc($sum_res) : [
        'total_count' => 0, 'total_amount' => 0,
        'c_code_count' => 0, 'c_code_amount' => 0,
        'd_code_count' => 0, 'd_code_amount' => 0,
        'resubmitted_count' => 0, 'resubmitted_amount' => 0
    ];

    $sql = "SELECT 
        r.id, r.vn, r.an, r.hn, r.cid, r.ptname, r.age, r.vstdate, r.vsttime, r.fund_type, r.pttype, r.pttype_name, r.informaddr,
        r.device_code, r.device_name, r.qty, r.unit_price, r.claim_amount, r.compensated_amount, r.diff_amount,
        r.claim_status, r.rep_no, r.receipt_no, r.receipt_date, r.receipt_source, r.stm_docno, r.denial_code, r.error_codes, r.is_resubmitted, r.resubmit_date, r.note,
        r.doctor_name, r.diag_code, r.diag_name,
        r.dep_code, r.department_name, r.department_group,
        COALESCE(d.description_th, 'รหัสปฏิเสธที่ยังไม่ได้ระบุคำอธิบายในพจนานุกรม') as description_th,
        COALESCE(d.severity, 'RETRY') as severity,
        d.deny_type,
        d.action_guide,
        d.common_causes
    FROM `imr_tb_rehab_device_claims` r
    LEFT JOIN `imr_tb_rehab_denial_codes` d ON (
        r.denial_code = d.denial_code 
        OR CONCAT('D', r.denial_code) = d.denial_code 
        OR CONCAT('C', r.denial_code) = d.denial_code
        OR REPLACE(REPLACE(r.denial_code, 'D', ''), 'C', '') = d.denial_code
    )
    WHERE $where
    ORDER BY r.is_resubmitted ASC, r.vstdate DESC
    LIMIT 300";

    $res = mysqli_query($conn, $sql);
    $items = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $code = $row['denial_code'];
            $dtype = !empty($row['deny_type']) ? $row['deny_type'] : '';
            $is_c = (strcasecmp($dtype, 'Corrective') === 0 || strtoupper(substr($code, 0, 1)) === 'C' || in_array($code, ['169', '179', '01', '02', '03']));
            $is_verify = (strcasecmp($dtype, 'Verify') === 0 || strtoupper(substr($code, 0, 1)) === 'V');
            
            if ($is_verify) {
                $row['category'] = 'VERIFY_CODE';
                $row['category_label'] = 'ติด V (Verify / รอหลักฐาน)';
                $row['category_badge'] = 'bg-info text-white';
            } elseif ($is_c) {
                $row['category'] = 'C_CODE';
                $row['category_label'] = 'ติด C (Claim Error / แก้ไขได้)';
                $row['category_badge'] = 'bg-warning text-dark';
            } else {
                $row['category'] = 'D_CODE';
                $row['category_label'] = 'ติด D (Deny / ปฏิเสธ)';
                $row['category_badge'] = 'bg-danger';
            }
            $row['display_code'] = $code;
            $row['vstdate_th'] = rehab_date_thai($row['vstdate']);
            $row['resubmit_date_th'] = rehab_date_thai($row['resubmit_date']);
            $items[] = $row;
        }
    }

    $response['status'] = 'success';
    $response['count'] = count($items);
    $response['summary'] = $summary;
    $response['items'] = $items;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'get_claim_detail') {
    $claim_id = isset($_REQUEST['claim_id']) ? intval($_REQUEST['claim_id']) : 0;
    if ($claim_id <= 0) {
        $response['message'] = 'ไม่พบรหัสเคลมที่ระบุ';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SELECT 
        r.id, r.vn, r.an, r.hn, r.cid, r.ptname, r.age, r.vstdate, r.vsttime, r.fund_type, r.pttype, r.pttype_name, r.informaddr,
        r.device_code, r.device_name, r.qty, r.unit_price, r.claim_amount, r.compensated_amount, r.diff_amount,
        r.claim_status, r.rep_no, r.receipt_no, r.receipt_date, r.receipt_source, r.stm_docno, r.denial_code, r.error_codes, r.is_resubmitted, r.resubmit_date, r.note,
        r.doctor_name, r.diag_code, r.diag_name,
        r.dep_code, r.department_name, r.department_group,
        COALESCE(d.description_th, '') as description_th,
        COALESCE(d.severity, 'RETRY') as severity,
        d.deny_type,
        d.action_guide,
        d.common_causes
    FROM `imr_tb_rehab_device_claims` r
    LEFT JOIN `imr_tb_rehab_denial_codes` d ON (
        r.denial_code = d.denial_code 
        OR CONCAT('D', r.denial_code) = d.denial_code 
        OR CONCAT('C', r.denial_code) = d.denial_code
        OR REPLACE(REPLACE(r.denial_code, 'D', ''), 'C', '') = d.denial_code
    )
    WHERE r.id = $claim_id
    LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $row['vstdate_th'] = rehab_date_thai($row['vstdate']);
        $row['resubmit_date_th'] = rehab_date_thai($row['resubmit_date']);
        $response['status'] = 'success';
        $response['data'] = $row;
    } else {
        $response['message'] = 'ไม่พบข้อมูลรายการเคลม';
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'get_denial_guide') {
    $code = isset($_REQUEST['denial_code']) ? trim($_REQUEST['denial_code']) : '';
    if (empty($code)) {
        $response['message'] = 'ไม่ได้รับรหัสปฏิเสธ (Missing denial_code)';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $code_esc = mysqli_real_escape_string($conn, $code);
    $sql = "SELECT * FROM `imr_tb_rehab_denial_codes` 
            WHERE denial_code = '$code_esc' OR denial_code = 'D$code_esc' OR CONCAT('D', denial_code) = '$code_esc'
            LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $guide = mysqli_fetch_assoc($res);
        $response['status'] = 'success';
        $response['data'] = $guide;
    } else {
        // Fallback default response
        $response['status'] = 'success';
        $response['data'] = [
            'denial_code' => $code,
            'fund_type' => 'ALL',
            'description_th' => "รหัสปฏิเสธ $code",
            'severity' => 'RETRY',
            'action_guide' => "1. ตรวจสอบรายละเอียดในเอกสารแจ้งปฏิเสธจากกองทุน<br>2. ประสานงานกลุ่มงานประกันสุขภาพ/สปสช.<br>3. ปรับปรุงข้อมูลเวชระเบียนและส่งเบิกซ้ำ",
            'common_causes' => "ยังไม่มีการบันทึกสาเหตุเฉพาะสำหรับรหัสนี้ เจ้าหน้าที่สามารถเพิ่มคำแนะนำได้ในเมนูพจนานุกรมรหัสปฏิเสธ"
        ];
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'mark_resubmitted') {
    $claim_id = isset($_REQUEST['claim_id']) ? intval($_REQUEST['claim_id']) : 0;
    $resubmit = isset($_REQUEST['is_resubmitted']) ? intval($_REQUEST['is_resubmitted']) : 1;
    $resubmit_date = $resubmit ? date('Y-m-d') : null;
    $note = isset($_REQUEST['note']) ? trim($_REQUEST['note']) : '';

    if ($claim_id <= 0) {
        $response['message'] = 'ไม่พบรหัสเคลมที่ต้องการอัปเดต (Invalid Claim ID)';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $date_val = $resubmit_date ? "'$resubmit_date'" : "NULL";
    $status_update = $resubmit ? "claim_status = 'WAIT_STM'," : "";
    $note_update = !empty($note) ? ", note = CONCAT(COALESCE(note,''), '\n[', CURDATE(), '] $note')" : "";

    $sql = "UPDATE `imr_tb_rehab_device_claims` 
            SET 
                $status_update
                is_resubmitted = $resubmit,
                resubmit_date = $date_val
                $note_update
            WHERE id = $claim_id";

    if (mysqli_query($conn, $sql)) {
        $response['status'] = 'success';
        $response['message'] = $resubmit ? 'บันทึกสถานะ "ส่งเบิกซ้ำแล้ว" เรียบร้อย' : 'ยกเลิกสถานะส่งเบิกซ้ำเรียบร้อย';
    } else {
        $response['message'] = 'เกิดข้อผิดพลาดในการบันทึก: ' . mysqli_error($conn);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'list_dictionary') {
    $dict_where = "1=1";
    if (!empty($_REQUEST['search'])) {
        $ds = mysqli_real_escape_string($conn, $_REQUEST['search']);
        $dict_where .= " AND (denial_code LIKE '%$ds%' OR description_th LIKE '%$ds%' OR action_guide LIKE '%$ds%' OR common_causes LIKE '%$ds%')";
    }
    if (!empty($_REQUEST['deny_type']) && $_REQUEST['deny_type'] !== 'ALL') {
        $dt = mysqli_real_escape_string($conn, $_REQUEST['deny_type']);
        $dict_where .= " AND deny_type = '$dt'";
    }

    $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 1000;
    $sql = "SELECT * FROM `imr_tb_rehab_denial_codes` WHERE $dict_where ORDER BY deny_type ASC, denial_code ASC LIMIT $limit";
    $res = mysqli_query($conn, $sql);
    $dict = [];
    if ($res) {
        while ($d = mysqli_fetch_assoc($res)) {
            $dict[] = $d;
        }
    }
    $response['status'] = 'success';
    $response['total'] = count($dict);
    $response['items'] = $dict;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save_dictionary_item') {
    $code = isset($_POST['denial_code']) ? trim($_POST['denial_code']) : '';
    $fund = isset($_POST['fund_type']) ? trim($_POST['fund_type']) : 'ALL';
    $desc = isset($_POST['description_th']) ? trim($_POST['description_th']) : '';
    $sev  = isset($_POST['severity']) ? trim($_POST['severity']) : 'RETRY';
    $guide= isset($_POST['action_guide']) ? trim($_POST['action_guide']) : '';
    $cause= isset($_POST['common_causes']) ? trim($_POST['common_causes']) : '';

    if (empty($code) || empty($desc)) {
        $response['message'] = 'กรุณากรอกรหัสปฏิเสธและคำอธิบายภาษาไทยให้ครบถ้วน';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO `imr_tb_rehab_denial_codes` 
        (`denial_code`, `fund_type`, `description_th`, `severity`, `action_guide`, `common_causes`)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        fund_type = VALUES(fund_type),
        description_th = VALUES(description_th),
        severity = VALUES(severity),
        action_guide = VALUES(action_guide),
        common_causes = VALUES(common_causes)");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssss", $code, $fund, $desc, $sev, $guide, $cause);
        if (mysqli_stmt_execute($stmt)) {
            $response['status'] = 'success';
            $response['message'] = "บันทึกรหัสปฏิเสธ $code เข้าพจนานุกรมเรียบร้อย";
        } else {
            $response['message'] = 'ไม่สามารถบันทึกได้: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'SQL Error: ' . mysqli_error($conn);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$response['message'] = 'ไม่พบคำสั่งที่ระบุ';
echo json_encode($response, JSON_UNESCAPED_UNICODE);
