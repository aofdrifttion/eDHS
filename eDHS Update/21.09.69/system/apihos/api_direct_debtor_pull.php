<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - Direct HOSxP Debtor Pull API Service
 * ====================================================================================
 * API สำหรับดึงข้อมูลลูกหนี้ผู้ป่วยนอก (OPD) และผู้ป่วยใน (IPD) ตรงจากฐานข้อมูล HOSxP ($conn2)
 * เข้าสู่ตาราง imr_tb_debtor_rights_opd และ imr_tb_debtor_rights_ipd
 * 
 * คุณสมบัติหลัก:
 * 1. Live Pre-aggregate Preview ดึงสรุปยอดจาก HOSxP ใน 0.2-0.3 วินาที
 * 2. รองรับทั้งโหมดเลือกทั้งเดือน (Monthly) และเลือกช่วงวันที่อิสระ (Custom Date Range)
 * 3. มี Checkbox ให้เลือกเฉพาะสิทธิการเงินที่ต้องการนำเข้า
 * 4. ครบถ้วน 35 คอลัมน์มาตรฐานเทียบเท่าการนำเข้าผ่าน Excel 100%
 * 5. ปลอดภัยด้วย Financial Safeguards (Rule 86) และ Triple Safeguard Deletion (Rule 87)
 * 6. ไม่กระทบระบบการนำเข้าไฟล์ Excel เดิม 100%
 * 
 * @author eDHS Engineering
 * @version 1.0.0 (2026-09-06)
 * ====================================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

@ini_set('display_errors', '0');
@header('Content-Type: application/json; charset=utf-8');

// เชื่อมต่อฐานข้อมูล eDHS ($conn) และ HOSxP ($conn2)
require_once dirname(__DIR__) . '/database_config/config.php';
require_once dirname(__DIR__) . '/database_config/db_helper.php';
require_once dirname(__DIR__) . '/includes/cr_migration_helper.php';

// ฟังก์ชันส่งออก JSON และสิ้นสุดการทำงาน
function send_response($status, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    $res = array_merge([
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], $data);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// จัดการ Unhandled Exception ให้ส่งคืนเป็น JSON เสมอ ป้องกันการพ่น HTML Error หรือ HTTP 500 เปล่า
set_exception_handler(function(Throwable $e) {
    send_response('error', 'ระบบพบข้อผิดพลาด: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')', [], 500);
});

// ตรวจสอบการเชื่อมต่อฐานข้อมูล eDHS
if (!isset($conn) || !$conn) {
    send_response('error', 'ไม่สามารถเชื่อมต่อฐานข้อมูล eDHS ได้ กรุณาตรวจสอบ database_config/config.php', [], 500);
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล HOSxP
if (!isset($conn2) || !$conn2) {
    send_response('error', 'ไม่สามารถเชื่อมต่อฐานข้อมูล HOSxP ($conn2) ได้ กรุณาตรวจสอบการตั้งค่า HOSxP ใน database_config/config.php', [], 500);
}

// ตรวจสอบสิทธิ์การเข้าใช้งาน
$user_id = $_SESSION['user_id'] ?? 0;
$fullname = $_SESSION['fullname'] ?? 'ไม่ระบุชื่อ';
$role = $_SESSION['role'] ?? '';

if (empty($user_id) && empty($_SESSION['role_id']) && empty($_SESSION['user'])) {
    send_response('error', 'กรุณาเข้าสู่ระบบก่อนทำรายการ', [], 401);
}

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

if (empty($action)) {
    send_response('error', 'กรุณาระบุ action ที่ต้องการทำรายการ (เช่น preview_summary, pull_batch, check_progress, delete_batch)', [], 400);
}

/**
 * ฟังก์ชันแปลงวันที่ให้เป็นรูปแบบ YYYY-MM-DD (ค.ศ.) สำหรับ Query HOSxP
 */
function normalize_to_gregorian_date($date_str) {
    if (empty($date_str)) return '';
    $clean = trim($date_str);
    
    // รูปแบบ dd/mm/yyyy หรือ dd-mm-yyyy
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $clean, $m)) {
        $d = intval($m[1]);
        $mon = intval($m[2]);
        $y = intval($m[3]);
        if ($y > 2400) {
            $y -= 543;
        }
        return sprintf('%04d-%02d-%02d', $y, $mon, $d);
    }
    
    // รูปแบบ yyyy-mm-dd
    if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $clean, $m)) {
        $y = intval($m[1]);
        $mon = intval($m[2]);
        $d = intval($m[3]);
        if ($y > 2400) {
            $y -= 543;
        }
        return sprintf('%04d-%02d-%02d', $y, $mon, $d);
    }
    
    $ts = strtotime($clean);
    if ($ts !== false) {
        $y = intval(date('Y', $ts));
        if ($y > 2400) $y -= 543;
        return sprintf('%04d-%s', $y, date('m-d', $ts));
    }
    
    return '';
}

/**
 * ดึงรายการงวดเดือนทั้งหมด (รูปแบบ m-YYYY เช่น 8-2026) ที่อยู่ในช่วงวันที่ ค.ศ.
 */
function get_months_in_gregorian_range($date_start, $date_end) {
    $months = [];
    try {
        $start = new DateTime($date_start);
        $end = new DateTime($date_end);
        $end->modify('first day of this month');
        $cur = clone $start;
        $cur->modify('first day of this month');
        while ($cur <= $end) {
            $months[] = sprintf('%d-%d', intval($cur->format('n')), intval($cur->format('Y')));
            $cur->modify('+1 month');
        }
    } catch (\Throwable $e) {
        // fallback
    }
    return array_values(array_unique($months));
}

if (!function_exists('get_categorized_debtor_accounts')) {
    /**
     * ดึงรายการผังบัญชีลูกหนี้มาตรฐานแยกตามหมวด OPD และ IPD จาก tb_code
     * กรองเฉพาะผังลูกหนี้จริง (ไม่รวมค่าเผื่อหนี้สงสัยจะสูญ และหนี้สูญ)
     */
    function get_categorized_debtor_accounts($conn) {
        $res = mysqli_query($conn, "SELECT Code, Name FROM tb_code WHERE (Code LIKE '1102050101%' OR Code LIKE '1102050102%' OR Name LIKE '%ลูกหนี้%') AND Name NOT LIKE '%ค่าเผื่อ%' AND Code NOT LIKE '110205012%' AND Code NOT LIKE '5108%' ORDER BY Code ASC");
        $opd = [];
        $ipd = [];
        $all = [];
        
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $code = trim($r['Code']);
                $name = trim($r['Name']);
                
                // ข้ามผังบัญชีค่าเผื่อหนี้สงสัยจะสูญ และหนี้สูญ
                if (strpos($code, '110205012') === 0 || strpos($code, '5108') === 0 || strpos($name, 'ค่าเผื่อ') !== false || strpos($name, 'หนี้สูญ') !== false) {
                    continue;
                }
                
                $item = ['code' => $code, 'name' => $name];
                $all[] = $item;
                
                $has_ip = preg_match('/(\bIP\b|ผู้ป่วยใน|ชำระเงินIP|ชำระเงิน IP)/ui', $name);
                $has_op = preg_match('/(\bOP\b|ผู้ป่วยนอก|P&P|ชำระเงินOP|ชำระเงิน OP)/ui', $name);
                
                // ผังมาตรฐาน OPD ที่เจาะจง
                if (in_array($code, ['1102050101.201', '1102050101.203', '1102050101.204', '1102050101.209', '1102050101.216', '1102050101.301', '1102050101.303', '1102050101.309', '1102050101.401', '1102050101.501', '1102050101.503', '1102050101.505', '1102050101.701', '1102050101.702', '1102050101.703', '1102050102.106', '1102050102.108', '1102050102.110', '1102050102.201', '1102050102.301', '1102050102.602', '1102050102.801', '1102050102.803'])) {
                    $has_op = true;
                    $has_ip = false;
                }
                // ผังมาตรฐาน IPD ที่เจาะจง
                if (in_array($code, ['1102050101.202', '1102050101.205', '1102050101.206', '1102050101.217', '1102050101.302', '1102050101.304', '1102050101.308', '1102050101.310', '1102050101.402', '1102050101.502', '1102050101.504', '1102050101.506', '1102050101.704', '1102050102.107', '1102050102.109', '1102050102.111', '1102050102.302', '1102050102.603', '1102050102.802', '1102050102.804'])) {
                    $has_ip = true;
                    $has_op = false;
                }
                
                // กองทุนทดแทน (.307) หรือ ฉุกเฉิน (.109) หรือผังที่ไม่ระบุชัดเจน ให้เลือกได้ทั้งสองฝั่ง
                if (strpos($code, '.307') !== false || (!$has_op && !$has_ip)) {
                    $has_op = true;
                    $has_ip = true;
                }
                
                if ($has_op) {
                    $opd[] = $item;
                }
                if ($has_ip) {
                    $ipd[] = $item;
                }
            }
        }
        return ['opd' => $opd, 'ipd' => $ipd, 'all' => $all];
    }
}

/**
 * ฟังก์ชันช่วยสร้างเงื่อนไข SQL สำหรับการตรวจสอบและลบข้อมูลลูกหนี้สิทธิ
 * รองรับทั้งโหมดรายเดือน (month) และช่วงวันที่ (range) พร้อมการกรองผังบัญชีและประเภทผู้ป่วย
 */
function build_debtor_delete_conditions($conn, $params) {
    $mode = isset($params['mode']) ? trim($params['mode']) : 'month';
    $patient_type = isset($params['patient_type']) ? strtolower(trim($params['patient_type'])) : 'both';
    $target_acc = isset($params['accountcode']) ? trim($params['accountcode']) : '';
    
    $acc_cond_plain = !empty($target_acc) ? " AND accountcode = '" . mysqli_real_escape_string($conn, $target_acc) . "' " : "";
    $acc_cond_opd = !empty($target_acc) ? " AND o.accountcode = '" . mysqli_real_escape_string($conn, $target_acc) . "' " : "";
    $acc_cond_ipd = !empty($target_acc) ? " AND i.accountcode = '" . mysqli_real_escape_string($conn, $target_acc) . "' " : "";
    $acc_cond_result = !empty($target_acc) ? " AND code = '" . mysqli_real_escape_string($conn, $target_acc) . "' " : "";
    
    $monthtxt = '';
    $date_start = '';
    $date_end = '';
    $criteria_desc = '';
    $months_in_range = [];
    
    if ($mode === 'range') {
        $raw_start = isset($params['date_start']) ? trim($params['date_start']) : '';
        $raw_end = isset($params['date_end']) ? trim($params['date_end']) : '';
        
        $date_start = normalize_to_gregorian_date($raw_start);
        $date_end = normalize_to_gregorian_date($raw_end);
        
        if (empty($date_start) || empty($date_end)) {
            return ['error' => 'กรุณาระบุวันที่เริ่มต้นและสิ้นสุดให้ถูกต้อง (เช่น 01/08/2569)'];
        }
        
        if ($date_start > $date_end) {
            $tmp = $date_start;
            $date_start = $date_end;
            $date_end = $tmp;
        }
        
        $start_be = sprintf('%04d-%s', intval(substr($date_start, 0, 4)) + 543, substr($date_start, 5));
        $end_be = sprintf('%04d-%s', intval(substr($date_end, 0, 4)) + 543, substr($date_end, 5));
        $start_th_slash = sprintf('%02d/%02d/%04d', intval(substr($date_start, 8, 2)), intval(substr($date_start, 5, 2)), intval(substr($date_start, 0, 4)) + 543);
        $end_th_slash = sprintf('%02d/%02d/%04d', intval(substr($date_end, 8, 2)), intval(substr($date_end, 5, 2)), intval(substr($date_end, 0, 4)) + 543);
        
        $criteria_desc = "ช่วงวันที่ $start_th_slash - $end_th_slash";
        $months_in_range = get_months_in_gregorian_range($date_start, $date_end);
        
        // OPD: vstdate ใน imr_tb_debtor_rights_opd เก็บเป็น dd/mm/yyyy (พ.ศ.) หรือ yyyy-mm-dd
        $opd_date_cond = "(
            (o.vstdate LIKE '%/%' AND STR_TO_DATE(o.vstdate, '%d/%m/%Y') BETWEEN '$start_be' AND '$end_be')
            OR (o.vstdate LIKE '%/%' AND STR_TO_DATE(o.vstdate, '%d/%m/%Y') BETWEEN '$date_start' AND '$date_end')
            OR (o.vstdate LIKE '%-%' AND o.vstdate BETWEEN '$start_be' AND '$end_be')
            OR (o.vstdate LIKE '%-%' AND o.vstdate BETWEEN '$date_start' AND '$date_end')
        )";
        $opd_date_cond_plain = "(
            (vstdate LIKE '%/%' AND STR_TO_DATE(vstdate, '%d/%m/%Y') BETWEEN '$start_be' AND '$end_be')
            OR (vstdate LIKE '%/%' AND STR_TO_DATE(vstdate, '%d/%m/%Y') BETWEEN '$date_start' AND '$date_end')
            OR (vstdate LIKE '%-%' AND vstdate BETWEEN '$start_be' AND '$end_be')
            OR (vstdate LIKE '%-%' AND vstdate BETWEEN '$date_start' AND '$date_end')
        )";
        
        // IPD: dchdate ใน imr_tb_debtor_rights_ipd เก็บเป็น dd/mm/yyyy (พ.ศ.) หรือ yyyy-mm-dd
        $ipd_date_cond = "(
            (i.dchdate LIKE '%/%' AND STR_TO_DATE(i.dchdate, '%d/%m/%Y') BETWEEN '$start_be' AND '$end_be')
            OR (i.dchdate LIKE '%/%' AND STR_TO_DATE(i.dchdate, '%d/%m/%Y') BETWEEN '$date_start' AND '$date_end')
            OR (i.dchdate LIKE '%-%' AND i.dchdate BETWEEN '$start_be' AND '$end_be')
            OR (i.dchdate LIKE '%-%' AND i.dchdate BETWEEN '$date_start' AND '$date_end')
        )";
        $ipd_date_cond_plain = "(
            (dchdate LIKE '%/%' AND STR_TO_DATE(dchdate, '%d/%m/%Y') BETWEEN '$start_be' AND '$end_be')
            OR (dchdate LIKE '%/%' AND STR_TO_DATE(dchdate, '%d/%m/%Y') BETWEEN '$date_start' AND '$date_end')
            OR (dchdate LIKE '%-%' AND dchdate BETWEEN '$start_be' AND '$end_be')
            OR (dchdate LIKE '%-%' AND dchdate BETWEEN '$date_start' AND '$date_end')
        )";
        
        $escaped_months = array_map(function($m) use ($conn) { return "'" . mysqli_real_escape_string($conn, $m) . "'"; }, $months_in_range);
        $months_in_sql = !empty($escaped_months) ? implode(',', $escaped_months) : "''";
        $result_month_cond = "month IN ($months_in_sql)";
        
    } else {
        // โหมดรายเดือน (month)
        $monthtxt = isset($params['monthtxt']) ? trim($params['monthtxt']) : '';
        if (empty($monthtxt)) {
            $month_input = isset($params['month']) ? trim($params['month']) : '';
            $year_input = isset($params['year']) ? trim($params['year']) : '';
            if (strpos($month_input, '-') !== false && empty($year_input)) {
                $monthtxt = $month_input;
            } elseif (!empty($month_input) && !empty($year_input)) {
                $m = intval($month_input);
                $y = intval($year_input);
                $year_ad = ($y > 2400) ? ($y - 543) : $y;
                $monthtxt = sprintf('%d-%d', $m, $year_ad);
            }
        }
        
        // ตรวจสอบและแปลงปี พ.ศ. เป็น ค.ศ. หากผู้ใช้ระบุเช่น 8-2569
        if (preg_match('/^(\d{1,2})[\/\-](\d{4})$/', $monthtxt, $m_match)) {
            $m_val = intval($m_match[1]);
            $y_val = intval($m_match[2]);
            if ($y_val > 2400) $y_val -= 543;
            $monthtxt = sprintf('%d-%d', $m_val, $y_val);
        }
        
        if (empty($monthtxt)) {
            return ['error' => 'กรุณาระบุงวดเดือนที่ต้องการลบ (เช่น 8-2026)'];
        }
        
        $escaped_month = mysqli_real_escape_string($conn, $monthtxt);
        $criteria_desc = "งวดเดือน $monthtxt";
        $months_in_range = [$monthtxt];
        
        $opd_date_cond = "o.monthtxt = '$escaped_month'";
        $opd_date_cond_plain = "monthtxt = '$escaped_month'";
        $ipd_date_cond = "i.monthtxt = '$escaped_month'";
        $ipd_date_cond_plain = "monthtxt = '$escaped_month'";
        $result_month_cond = "month = '$escaped_month'";
    }
    
    // เติมรายละเอียดสิทธิ์ / ผู้ป่วยในคำอธิบาย
    $pt_desc = ($patient_type === 'opd') ? 'ผู้ป่วยนอก (OPD)' : (($patient_type === 'ipd') ? 'ผู้ป่วยใน (IPD)' : 'ทั้งหมด (OPD และ IPD)');
    $criteria_desc .= " | $pt_desc";
    
    if (!empty($target_acc)) {
        $criteria_desc .= " | ผังบัญชี $target_acc";
    } else {
        $criteria_desc .= " | ทุกผังบัญชี";
    }
    
    return [
        'mode' => $mode,
        'monthtxt' => $monthtxt,
        'date_start' => $date_start,
        'date_end' => $date_end,
        'patient_type' => $patient_type,
        'target_acc' => $target_acc,
        'acc_cond_plain' => $acc_cond_plain,
        'acc_cond_opd' => $acc_cond_opd,
        'acc_cond_ipd' => $acc_cond_ipd,
        'acc_cond_result' => $acc_cond_result,
        'opd_date_cond' => $opd_date_cond,
        'opd_date_cond_plain' => $opd_date_cond_plain,
        'ipd_date_cond' => $ipd_date_cond,
        'ipd_date_cond_plain' => $ipd_date_cond_plain,
        'result_month_cond' => $result_month_cond,
        'criteria_desc' => $criteria_desc,
        'months_in_range' => $months_in_range
    ];
}

/**
 * ดึงการตั้งค่า Custom Pttype Mapping จาก config.json
 */
function get_custom_pttype_mappings() {
    $cfg_file = __DIR__ . '/../database_config/config.json';
    if (!file_exists($cfg_file)) {
        return ['OPD' => [], 'IPD' => []];
    }
    $data = json_decode(file_get_contents($cfg_file), true);
    return $data['custom_pttype_mappings'] ?? ['OPD' => [], 'IPD' => []];
}

/**
 * บันทึกการตั้งค่า Custom Pttype Mapping ลง config.json
 */
function save_custom_pttype_mappings($new_mappings) {
    if (empty($new_mappings) || !is_array($new_mappings)) return;
    $cfg_file = __DIR__ . '/../database_config/config.json';
    if (!file_exists($cfg_file)) return;
    
    $cfg = json_decode(file_get_contents($cfg_file), true);
    if (!is_array($cfg)) return;
    if (!isset($cfg['custom_pttype_mappings'])) {
        $cfg['custom_pttype_mappings'] = ['OPD' => [], 'IPD' => []];
    }
    
    foreach ($new_mappings as $key => $acc) {
        $acc = trim($acc);
        if (empty($acc)) continue;
        if (strpos($key, ':') !== false) {
            list($t, $pt) = explode(':', $key, 2);
            $t = strtoupper($t);
            $cfg['custom_pttype_mappings'][$t][$pt] = $acc;
        } else {
            $cfg['custom_pttype_mappings']['OPD'][$key] = $acc;
            $cfg['custom_pttype_mappings']['IPD'][$key] = $acc;
        }
    }
    
    file_put_contents($cfg_file, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * ====================================================================================
 * ACTION: save_pttype_mapping
 * บันทึกการกำหนดผังบัญชีสำหรับสิทธิ (PTTYPE) จาก tb_code ลง config.json ทันที
 * ====================================================================================
 */
if ($action === 'save_pttype_mapping') {
    $type = strtoupper(trim($_REQUEST['type'] ?? 'OPD'));
    if (!in_array($type, ['OPD', 'IPD'])) $type = 'OPD';
    $pttype = trim((string)($_REQUEST['pttype'] ?? ''));
    $accountcode = trim((string)($_REQUEST['accountcode'] ?? ''));
    
    if (empty($pttype)) {
        send_response('error', 'กรุณาระบุรหัสสิทธิ (pttype)');
    }
    
    $cfg_file = __DIR__ . '/../database_config/config.json';
    $cfg = file_exists($cfg_file) ? json_decode(file_get_contents($cfg_file), true) : [];
    if (!is_array($cfg)) $cfg = [];
    if (!isset($cfg['custom_pttype_mappings'])) {
        $cfg['custom_pttype_mappings'] = ['OPD' => [], 'IPD' => []];
    }
    if (!isset($cfg['custom_pttype_mappings'][$type])) {
        $cfg['custom_pttype_mappings'][$type] = [];
    }
    
    if (!empty($accountcode) && $accountcode !== '-') {
        $cfg['custom_pttype_mappings'][$type][$pttype] = $accountcode;
    } else {
        unset($cfg['custom_pttype_mappings'][$type][$pttype]);
    }
    
    $saved = file_put_contents($cfg_file, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($saved === false) {
        send_response('error', 'ไม่สามารถบันทึกลงไฟล์ config.json ได้');
    }
    
    // ดึงชื่อผังบัญชีจาก tb_code ประกอบการตอบกลับ
    $acc_name = '';
    if (!empty($accountcode)) {
        $r_code = mysqli_fetch_assoc(mysqli_query($conn, "SELECT Name FROM tb_code WHERE Code = '" . mysqli_real_escape_string($conn, $accountcode) . "' LIMIT 1"));
        $acc_name = $r_code['Name'] ?? "ผังบัญชี $accountcode";
    }
    
    send_response('success', "บันทึกการกำหนดผังบัญชี [$type: $pttype -> $accountcode] สำเร็จ", [
        'type' => $type,
        'pttype' => $pttype,
        'accountcode' => $accountcode,
        'accountname' => $acc_name
    ]);
}

/**
 * ====================================================================================
 * ACTION: preview_summary
 * ดึงสรุปยอดลูกหนี้แยกตามสิทธิการเงินและผังบัญชีจาก HOSxP อย่างรวดเร็ว (0.2 - 0.3s)
 * ====================================================================================
 */
if ($action === 'preview_summary') {
    $start_time = microtime(true);
    
    $mode = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : 'month'; // month หรือ range
    $patient_type = isset($_REQUEST['patient_type']) ? trim($_REQUEST['patient_type']) : 'both'; // opd, ipd, both
    
    $date_start = '';
    $date_end = '';
    $monthtxt = '';
    
    if ($mode === 'month') {
        $month_input = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : '';
        $year_input = isset($_REQUEST['year']) ? trim($_REQUEST['year']) : '';
        
        // กรณีส่งมาเป็น m-Y ในตัวแปรเดียว (เช่น 8-2026 หรือ 8-2569)
        if (strpos($month_input, '-') !== false && empty($year_input)) {
            $parts = explode('-', $month_input);
            $month_input = $parts[0];
            $year_input = $parts[1];
        }
        
        $m = intval($month_input);
        $y = intval($year_input);
        
        if ($m < 1 || $m > 12 || $y <= 0) {
            send_response('error', 'กรุณาระบุเดือน (1-12) และปี ให้ถูกต้อง');
        }
        
        // แปลงปีเป็น ค.ศ. สำหรับ query HOSxP
        $year_ad = ($y > 2400) ? ($y - 543) : $y;
        
        // ใน eDHS นิยมเก็บ monthtxt เป็น m-ค.ศ. เช่น 8-2026
        $monthtxt = sprintf('%d-%d', $m, $year_ad);
        
        $date_start = sprintf('%04d-%02d-01', $year_ad, $m);
        $days_in_m = date('t', strtotime($date_start));
        $date_end = sprintf('%04d-%02d-%02d', $year_ad, $m, $days_in_m);
        
    } elseif ($mode === 'range') {
        $raw_start = isset($_REQUEST['date_start']) ? trim($_REQUEST['date_start']) : '';
        $raw_end = isset($_REQUEST['date_end']) ? trim($_REQUEST['date_end']) : '';
        
        $date_start = normalize_to_gregorian_date($raw_start);
        $date_end = normalize_to_gregorian_date($raw_end);
        
        if (empty($date_start) || empty($date_end)) {
            send_response('error', 'กรุณาระบุวันที่เริ่มต้นและวันที่สิ้นสุดให้ถูกต้อง (เช่น 01/08/2569 หรือ 2026-08-01)');
        }
        
        if ($date_start > $date_end) {
            $tmp = $date_start;
            $date_start = $date_end;
            $date_end = $tmp;
        }
        
        // สำหรับ monthtxt ในโหมด range จะอิงตามเดือนของวันเริ่มต้น
        $start_ts = strtotime($date_start);
        $monthtxt = sprintf('%d-%d', intval(date('m', $start_ts)), intval(date('Y', $start_ts)));
    } else {
        send_response('error', 'โหมดการดึงข้อมูลไม่ถูกต้อง (ต้องเป็น month หรือ range)');
    }
    
    // ตารางจับคู่ชื่อผังบัญชีพื้นฐานเป็น Fallback (กรณี HOSxP ไม่มีชื่อใน codeaccount)
    $known_accounts = [
        '1102050101.201' => 'ลูกหนี้ค่ารักษา UC- OP ใน CUP',
        '1102050101.202' => 'ลูกหนี้ค่ารักษา UC - IP',
        '1102050101.203' => 'ลูกหนี้ค่ารักษา UC - OP นอก CUP (ในจังหวัดสังกัด สธ.)',
        '1102050101.204' => 'ลูกหนี้ค่ารักษา UC - OP นอก CUP (ต่างจังหวัด)',
        '1102050101.205' => 'ลูกหนี้ค่ารักษา UC - IP นอก CUP (ในจังหวัด)',
        '1102050101.206' => 'ลูกหนี้ค่ารักษา UC - IP นอก CUP (ต่างจังหวัด)',
        '1102050101.209' => 'ลูกหนี้ค่ารักษาด้านการสร้างเสริมสุขภาพและป้องกันโรค (P&P)',
        '1102050101.216' => 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)',
        '1102050101.217' => 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)',
        '1102050101.301' => 'ลูกหนี้ค่ารักษาประกันสังคม OP -เครือข่าย',
        '1102050101.302' => 'ลูกหนี้ค่ารักษาประกันสังคม IP -เครือข่าย',
        '1102050101.307' => 'ลูกหนี้ค่ารักษาประกันสังคม - กองทุนทดแทน',
        '1102050101.308' => 'ลูกหนี้ค่ารักษาประกันสังคม - นอกเครือข่าย IP',
        '1102050101.309' => 'ลูกหนี้ค่ารักษาประกันสังคม - บริการทางการแพทย์เฉพาะทาง OP',
        '1102050101.310' => 'ลูกหนี้ค่ารักษาประกันสังคม - บริการทางการแพทย์เฉพาะทาง IP',
        '1102050101.401' => 'ลูกหนี้ค่ารักษา-เบิกจ่ายตรงกรมบัญชีกลาง OP',
        '1102050101.402' => 'ลูกหนี้ค่ารักษา-เบิกจ่ายตรงกรมบัญชีกลาง IP',
        '1102050102.106' => 'ลูกหนี้ค่ารักษา - ชำระเงิน OP',
        '1102050102.201' => 'ลูกหนี้ค่ารักษา UC ต่างสังกัด สป. (AE OP)',
        '1102050102.602' => 'ลูกหนี้ค่ารักษา - พรบ.รถ OP',
        '1102050102.801' => 'ลูกหนี้ค่ารักษา - เบิกจ่ายตรง อปท. OP',
        '1102050102.802' => 'ลูกหนี้ค่ารักษา - เบิกจ่ายตรง อปท. IP'
    ];
    
    $categorized_accounts = get_categorized_debtor_accounts($conn);
    $saved_mappings = get_custom_pttype_mappings();
    
    // สร้าง Lookup ดึงชื่อผังบัญชีแบบรวดเร็วจากทุกผังใน tb_code
    $all_accounts_lookup = [];
    foreach ($categorized_accounts['all'] as $a) {
        $all_accounts_lookup[$a['code']] = $a['name'];
    }

    // คำนวณงวดเดือนทั้งหมดที่อยู่ในช่วง เพื่อดึงสถานะจาก eDHS
    $months_in_scope = ($mode === 'month') ? [$monthtxt] : get_months_in_gregorian_range($date_start, $date_end);
    if (empty($months_in_scope)) {
        $months_in_scope = [$monthtxt];
    }
    $escaped_months = array_map(function($m) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $m) . "'";
    }, $months_in_scope);
    $in_months_sql = implode(',', $escaped_months);

    // 1. ตรวจสอบการยืนยันตั้งลูกหนี้ในระบบทะเบียนคุมลูกหนี้ (imr_tb_debtor_result.column10 > 0)
    $posted_debtor_accounts = []; // [accountcode => sum_col10]
    $res_deb_posted = mysqli_query($conn, "SELECT code, SUM(column10) as total_c10 FROM imr_tb_debtor_result WHERE month IN ($in_months_sql) GROUP BY code");
    if ($res_deb_posted) {
        while ($dr = mysqli_fetch_assoc($res_deb_posted)) {
            $code_val = trim($dr['code'] ?? '');
            $sc10 = floatval($dr['total_c10']);
            if ($sc10 > 0) {
                $posted_debtor_accounts[$code_val] = $sc10;
            }
        }
    }

    // 2. ตรวจสอบเคสเดิมและเคสที่ออกใบเสร็จ/ตัดหนี้แล้วใน eDHS (OPD)
    // นิยามออกใบเสร็จ: bill != '' OR (mobile != '' AND mobile != '-') OR follow_money > 0
    $edhs_opd_stats = []; // [pttype => ['total' => X, 'billed' => Y]]
    $billed_cond_opd = "((bill IS NOT NULL AND TRIM(bill) != '' AND TRIM(bill) != '-') OR (mobile IS NOT NULL AND TRIM(mobile) != '' AND TRIM(mobile) != '-') OR (follow_money IS NOT NULL AND CAST(follow_money AS DECIMAL(15,2)) > 0))";
    $q_edhs_opd = mysqli_query($conn, "SELECT pttype, COUNT(vn) as total_cases, SUM(IF($billed_cond_opd, 1, 0)) as billed_cases FROM imr_tb_debtor_rights_opd WHERE monthtxt IN ($in_months_sql) GROUP BY pttype");
    if ($q_edhs_opd) {
        while ($r_o = mysqli_fetch_assoc($q_edhs_opd)) {
            $edhs_opd_stats[trim($r_o['pttype'] ?? '')] = [
                'total' => intval($r_o['total_cases']),
                'billed' => intval($r_o['billed_cases'])
            ];
        }
    }

    // 3. ตรวจสอบเคสเดิมและเคสที่ออกใบเสร็จ/ตัดหนี้แล้วใน eDHS (IPD)
    $edhs_ipd_stats = []; // [pttype => ['total' => X, 'billed' => Y]]
    $billed_cond_ipd = "((bill IS NOT NULL AND TRIM(bill) != '' AND TRIM(bill) != '-') OR (mobile IS NOT NULL AND TRIM(mobile) != '' AND TRIM(mobile) != '-') OR (follow_money IS NOT NULL AND CAST(follow_money AS DECIMAL(15,2)) > 0))";
    $q_edhs_ipd = mysqli_query($conn, "SELECT pttype, COUNT(an) as total_cases, SUM(IF($billed_cond_ipd, 1, 0)) as billed_cases FROM imr_tb_debtor_rights_ipd WHERE monthtxt IN ($in_months_sql) GROUP BY pttype");
    if ($q_edhs_ipd) {
        while ($r_i = mysqli_fetch_assoc($q_edhs_ipd)) {
            $edhs_ipd_stats[trim($r_i['pttype'] ?? '')] = [
                'total' => intval($r_i['total_cases']),
                'billed' => intval($r_i['billed_cases'])
            ];
        }
    }

    $rights_list = [];
    $summary = [
        'opd_cases' => 0,
        'opd_income' => 0.0,
        'opd_discount' => 0.0,
        'opd_rcpt' => 0.0,
        'opd_debit' => 0.0,
        'ipd_cases' => 0,
        'ipd_income' => 0.0,
        'ipd_discount' => 0.0,
        'ipd_rcpt' => 0.0,
        'ipd_debit' => 0.0,
        'total_cases' => 0,
        'total_income' => 0.0,
        'total_discount' => 0.0,
        'total_rcpt' => 0.0,
        'total_debit' => 0.0
    ];
    
    // ----------------------------------------------------
    // 1. QUERY สรุปยอด OPD
    // ----------------------------------------------------
    if ($patient_type === 'opd' || $patient_type === 'both') {
        $sql_opd = "SELECT 
                        o.pttype,
                        IFNULL(ptt.name, 'ไม่ระบุชื่อสิทธิ') AS pttypename,
                        IFNULL(ptt.pttype_eclaim_id, '') AS pttype_eclaim_id,
                        IFNULL(e.name, '') AS pttype_eclaim_name,
                        IFNULL(e.ar_opd, '') AS accountcode,
                        IFNULL(ca.name, '') AS accountname,
                        COUNT(o.vn) AS total_cases,
                        SUM(IFNULL(v.income, 0)) AS total_income,
                        SUM(IFNULL(v.discount_money, 0)) AS total_discount,
                        SUM(IFNULL(v.rcpt_money, 0)) AS total_rcpt,
                        SUM(IFNULL(v.income, 0) - IFNULL(v.discount_money, 0) - IFNULL(v.rcpt_money, 0)) AS total_debit
                    FROM ovst o
                    LEFT JOIN vn_stat v ON v.vn = o.vn
                    LEFT JOIN pttype ptt ON ptt.pttype = o.pttype
                    LEFT JOIN pttype_eclaim e ON e.code = ptt.pttype_eclaim_id
                    LEFT JOIN codeaccount ca ON ca.code = e.ar_opd
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an IS NULL OR o.an = '')
                    GROUP BY o.pttype, e.ar_opd
                    ORDER BY total_cases DESC, o.pttype ASC";
                    
        $stmt_opd = mysqli_prepare($conn2, $sql_opd);
        if (!$stmt_opd) {
            send_response('error', 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง Query OPD: ' . mysqli_error($conn2));
        }
        
        mysqli_stmt_bind_param($stmt_opd, 'ss', $date_start, $date_end);
        mysqli_stmt_execute($stmt_opd);
        $res_opd = mysqli_stmt_get_result($stmt_opd);
        
        while ($r = mysqli_fetch_assoc($res_opd)) {
            $pt_code = (string)$r['pttype'];
            $acc_code = trim($r['accountcode']);
            $acc_name = trim($r['accountname']);
            $is_custom = false;
            
            // ตรวจสอบว่าเคยมีบันทึก Custom Mapping หรือไม่
            if (empty($acc_code)) {
                if (isset($saved_mappings['OPD'][$pt_code]) && !empty($saved_mappings['OPD'][$pt_code])) {
                    $acc_code = $saved_mappings['OPD'][$pt_code];
                    $is_custom = true;
                }
            }
            
            if (isset($all_accounts_lookup[$acc_code])) {
                $acc_name = $all_accounts_lookup[$acc_code];
            } elseif (isset($known_accounts[$acc_code])) {
                $acc_name = $known_accounts[$acc_code];
            }
            
            $is_unmapped = empty($acc_code) || $acc_code === '-' || empty($acc_name) || $acc_name === 'ไม่ได้ผูกผังบัญชี';
            if (empty($acc_name)) {
                $acc_name = !empty($acc_code) ? "ผังบัญชี $acc_code" : 'ไม่ได้ผูกผังบัญชี';
            }
            
            $cases = intval($r['total_cases']);
            $income = floatval($r['total_income']);
            $discount = floatval($r['total_discount']);
            $rcpt = floatval($r['total_rcpt']);
            $debit = floatval($r['total_debit']);
            
            $summary['opd_cases'] += $cases;
            $summary['opd_income'] += $income;
            $summary['opd_discount'] += $discount;
            $summary['opd_rcpt'] += $rcpt;
            $summary['opd_debit'] += $debit;
            
            $edhs_stat = $edhs_opd_stats[$pt_code] ?? ['total' => 0, 'billed' => 0];
            $edhs_cases = $edhs_stat['total'];
            $billed_cases = $edhs_stat['billed'];
            $debtor_posted = isset($posted_debtor_accounts[$acc_code]) && ($posted_debtor_accounts[$acc_code] > 0);
            $is_locked = ($billed_cases > 0 || $debtor_posted);
            $is_already_set = ($edhs_cases > 0);
            
            $lock_reason = '';
            if ($billed_cases > 0) {
                $lock_reason = "ออกใบเสร็จ/ตัดหนี้แล้ว $billed_cases รายการ";
            } elseif ($debtor_posted) {
                $lock_reason = "ยืนยันตั้งลูกหนี้ในระบบบัญชีแล้ว (" . number_format($posted_debtor_accounts[$acc_code], 2) . " ฿)";
            }
            
            $rights_list[] = [
                'type' => 'OPD',
                'pttype' => $pt_code,
                'pttypename' => (string)$r['pttypename'],
                'pttype_eclaim_id' => (string)$r['pttype_eclaim_id'],
                'pttype_eclaim_name' => (string)$r['pttype_eclaim_name'],
                'accountcode' => $acc_code,
                'accountname' => $acc_name,
                'is_custom' => $is_custom,
                'is_unmapped' => $is_unmapped,
                'cases' => $cases,
                'income' => $income,
                'discount' => $discount,
                'rcpt' => $rcpt,
                'debit' => $debit,
                'edhs_cases' => $edhs_cases,
                'billed_cases' => $billed_cases,
                'debtor_posted' => $debtor_posted,
                'is_locked' => $is_locked,
                'is_already_set' => $is_already_set,
                'lock_reason' => $lock_reason
            ];
        }
        mysqli_stmt_close($stmt_opd);
    }
    
    // ----------------------------------------------------
    // 2. QUERY สรุปยอด IPD
    // ----------------------------------------------------
    if ($patient_type === 'ipd' || $patient_type === 'both') {
        $cr_config = get_active_cr_config($conn);
        $auto_split_ipd = is_cr_auto_split_enabled($cr_config, 'IPD');

        $sql_ipd = "SELECT 
                        i.pttype,
                        IFNULL(ptt.name, 'ไม่ระบุชื่อสิทธิ') AS pttypename,
                        IFNULL(ptt.pttype_eclaim_id, '') AS pttype_eclaim_id,
                        IFNULL(e.name, '') AS pttype_eclaim_name,
                        IFNULL(e.ar_ipd, '') AS accountcode,
                        IFNULL(ca.name, '') AS accountname,
                        COUNT(i.an) AS total_cases,
                        SUM(IFNULL(a.income, 0)) AS total_income,
                        SUM(IFNULL(a.discount_money, 0)) AS total_discount,
                        SUM(IFNULL(a.rcpt_money, 0)) AS total_rcpt,
                        SUM(IFNULL(a.income, 0) - IFNULL(a.discount_money, 0) - IFNULL(a.rcpt_money, 0)) AS total_debit
                    FROM ipt i
                    LEFT JOIN an_stat a ON a.an = i.an
                    LEFT JOIN pttype ptt ON ptt.pttype = i.pttype
                    LEFT JOIN pttype_eclaim e ON e.code = ptt.pttype_eclaim_id
                    LEFT JOIN codeaccount ca ON ca.code = e.ar_ipd
                    WHERE i.dchdate BETWEEN ? AND ?
                    GROUP BY i.pttype, e.ar_ipd
                    ORDER BY total_cases DESC, i.pttype ASC";
                    
        $stmt_ipd = mysqli_prepare($conn2, $sql_ipd);
        if (!$stmt_ipd) {
            send_response('error', 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง Query IPD: ' . mysqli_error($conn2));
        }
        
        mysqli_stmt_bind_param($stmt_ipd, 'ss', $date_start, $date_end);
        mysqli_stmt_execute($stmt_ipd);
        $res_ipd = mysqli_stmt_get_result($stmt_ipd);
        
        while ($r = mysqli_fetch_assoc($res_ipd)) {
            $pt_code = (string)$r['pttype'];
            $acc_code = trim($r['accountcode']);
            $acc_name = trim($r['accountname']);
            $is_custom = false;
            
            // Safeguard: ถ้าไม่ได้เปิดระบบ CR IPD ให้ยุบผังลูก 1102050101.217 รวมเข้ากับ 1102050101.202 ทันที
            if (!$auto_split_ipd && $acc_code === '1102050101.217') {
                $acc_code = '1102050101.202';
                $acc_name = 'ลูกหนี้ค่ารักษา UC - IP';
            }
            
            // ตรวจสอบว่าเคยมีบันทึก Custom Mapping หรือไม่
            if (empty($acc_code)) {
                if (isset($saved_mappings['IPD'][$pt_code]) && !empty($saved_mappings['IPD'][$pt_code])) {
                    $acc_code = $saved_mappings['IPD'][$pt_code];
                    $is_custom = true;
                }
            }
            
            if (isset($all_accounts_lookup[$acc_code])) {
                $acc_name = $all_accounts_lookup[$acc_code];
            } elseif (isset($known_accounts[$acc_code])) {
                $acc_name = $known_accounts[$acc_code];
            }
            
            $is_unmapped = empty($acc_code) || $acc_code === '-' || empty($acc_name) || $acc_name === 'ไม่ได้ผูกผังบัญชี';
            if (empty($acc_name)) {
                $acc_name = !empty($acc_code) ? "ผังบัญชี $acc_code" : 'ไม่ได้ผูกผังบัญชี';
            }
            
            $cases = intval($r['total_cases']);
            $income = floatval($r['total_income']);
            $discount = floatval($r['total_discount']);
            $rcpt = floatval($r['total_rcpt']);
            $debit = floatval($r['total_debit']);
            
            $summary['ipd_cases'] += $cases;
            $summary['ipd_income'] += $income;
            $summary['ipd_discount'] += $discount;
            $summary['ipd_rcpt'] += $rcpt;
            $summary['ipd_debit'] += $debit;
            
            $edhs_stat = $edhs_ipd_stats[$pt_code] ?? ['total' => 0, 'billed' => 0];
            $edhs_cases = $edhs_stat['total'];
            $billed_cases = $edhs_stat['billed'];
            $debtor_posted = isset($posted_debtor_accounts[$acc_code]) && ($posted_debtor_accounts[$acc_code] > 0);
            $is_locked = ($billed_cases > 0 || $debtor_posted);
            $is_already_set = ($edhs_cases > 0);
            
            $lock_reason = '';
            if ($billed_cases > 0) {
                $lock_reason = "ออกใบเสร็จ/ตัดหนี้แล้ว $billed_cases รายการ";
            } elseif ($debtor_posted) {
                $lock_reason = "ยืนยันตั้งลูกหนี้ในระบบบัญชีแล้ว (" . number_format($posted_debtor_accounts[$acc_code], 2) . " ฿)";
            }
            
            $rights_list[] = [
                'type' => 'IPD',
                'pttype' => $pt_code,
                'pttypename' => (string)$r['pttypename'],
                'pttype_eclaim_id' => (string)$r['pttype_eclaim_id'],
                'pttype_eclaim_name' => (string)$r['pttype_eclaim_name'],
                'accountcode' => $acc_code,
                'accountname' => $acc_name,
                'is_custom' => $is_custom,
                'is_unmapped' => $is_unmapped,
                'cases' => $cases,
                'income' => $income,
                'discount' => $discount,
                'rcpt' => $rcpt,
                'debit' => $debit,
                'edhs_cases' => $edhs_cases,
                'billed_cases' => $billed_cases,
                'debtor_posted' => $debtor_posted,
                'is_locked' => $is_locked,
                'is_already_set' => $is_already_set,
                'lock_reason' => $lock_reason
            ];
        }
        mysqli_stmt_close($stmt_ipd);
    }
    
    // รวมยอด Grand Total
    $summary['total_cases'] = $summary['opd_cases'] + $summary['ipd_cases'];
    $summary['total_income'] = $summary['opd_income'] + $summary['ipd_income'];
    $summary['total_discount'] = $summary['opd_discount'] + $summary['ipd_discount'];
    $summary['total_rcpt'] = $summary['opd_rcpt'] + $summary['ipd_rcpt'];
    $summary['total_debit'] = $summary['opd_debit'] + $summary['ipd_debit'];
    
    $has_locked_rights = false;
    $unmapped_count = 0;
    foreach ($rights_list as $rl) {
        if (!empty($rl['is_locked'])) {
            $has_locked_rights = true;
        }
        if (!empty($rl['is_unmapped'])) {
            $unmapped_count++;
        }
    }
    $summary['has_locked_rights'] = $has_locked_rights;
    $summary['unmapped_count'] = $unmapped_count;
    $summary['edhs_total_existing'] = intval(array_sum(array_column($edhs_opd_stats, 'total')) + array_sum(array_column($edhs_ipd_stats, 'total')));
    $summary['edhs_total_billed'] = intval(array_sum(array_column($edhs_opd_stats, 'billed')) + array_sum(array_column($edhs_ipd_stats, 'billed')));
    
    $elapsed_sec = round(microtime(true) - $start_time, 4);
    
    send_response('success', 'ดึงข้อมูลสรุปยอดจาก HOSxP สำเร็จ', [
        'execution_time_sec' => $elapsed_sec,
        'params' => [
            'mode' => $mode,
            'date_start' => $date_start,
            'date_end' => $date_end,
            'monthtxt' => $monthtxt,
            'patient_type' => $patient_type
        ],
        'debtor_accounts' => $categorized_accounts['all'],
        'opd_accounts' => $categorized_accounts['opd'],
        'ipd_accounts' => $categorized_accounts['ipd'],
        'summary' => $summary,
        'rights_count' => count($rights_list),
        'rights' => $rights_list
    ]);
}

/**
 * ====================================================================================
 * ฟังก์ชันช่วยแปลงวันที่ ค.ศ. (YYYY-MM-DD) เป็น พ.ศ. (dd/mm/yyyy)
 * ====================================================================================
 */
function format_date_thai_be($date_str) {
    if (empty($date_str) || $date_str == '0000-00-00') return '';
    $ts = strtotime($date_str);
    if ($ts === false) return $date_str;
    $y = intval(date('Y', $ts)) + 543;
    return date('d/m/', $ts) . $y;
}

/**
 * ====================================================================================
 * ACTION: preview_patient_list
 * ดึงรายการผู้ป่วยรายบุคคล (Patient-level Preview) จาก HOSxP ตามสิทธิที่เลือก ก่อนทำการนำเข้า
 * ====================================================================================
 */
if ($action === 'preview_patient_list') {
    $patient_type = strtoupper(trim($_REQUEST['patient_type'] ?? 'OPD'));
    $pttype = trim((string)($_REQUEST['pttype'] ?? ''));
    $mode = trim($_REQUEST['mode'] ?? 'month');
    $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 500;
    if ($limit <= 0 || $limit > 2000) $limit = 500;

    $date_start = '';
    $date_end = '';
    $monthtxt = '';

    if ($mode === 'month') {
        $month_input = trim($_REQUEST['month'] ?? '');
        if (preg_match('/^(\d{1,2})[\/\-](\d{4})$/', $month_input, $m)) {
            $mon = intval($m[1]);
            $yr = intval($m[2]);
            if ($yr > 2400) $yr -= 543;
            $start_dt = new DateTime(sprintf('%04d-%02d-01', $yr, $mon));
            $end_dt = clone $start_dt;
            $end_dt->modify('last day of this month');
            $date_start = $start_dt->format('Y-m-d');
            $date_end = $end_dt->format('Y-m-d');
            $monthtxt = sprintf('%d-%d', $mon, $yr);
        }
    } else {
        $date_start = normalize_to_gregorian_date($_REQUEST['date_start'] ?? '');
        $date_end = normalize_to_gregorian_date($_REQUEST['date_end'] ?? '');
    }

    if (empty($date_start) || empty($date_end)) {
        send_response('error', 'ไม่สามารถระบุช่วงวันที่ค้นหาได้');
    }

    if (empty($pttype)) {
        send_response('error', 'กรุณาระบุรหัสสิทธิการรักษา (pttype)');
    }

    // ข้อมูลชื่อสิทธิ
    $ptt_row = mysqli_fetch_assoc(mysqli_query($conn2, "SELECT name, pttype_eclaim_id FROM pttype WHERE pttype = '" . mysqli_real_escape_string($conn2, $pttype) . "' LIMIT 1"));
    $pttypename = $ptt_row['name'] ?? "สิทธิ $pttype";

    $patients = [];
    $total_cases = 0;
    $sum_income = 0.0;
    $sum_debit = 0.0;

    if ($patient_type === 'OPD') {
        // 1. นับจำนวนทั้งหมดและผลรวมยอด
        $sql_cnt = "SELECT COUNT(o.vn) AS cnt, SUM(IFNULL(v.income, 0)) AS s_income, SUM(IFNULL(v.income, 0) - IFNULL(v.discount_money, 0) - IFNULL(v.rcpt_money, 0)) AS s_debit
                    FROM ovst o
                    LEFT JOIN vn_stat v ON v.vn = o.vn
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an IS NULL OR o.an = '')
                      AND o.pttype = ?";
        $stmt_c = mysqli_prepare($conn2, $sql_cnt);
        if ($stmt_c) {
            mysqli_stmt_bind_param($stmt_c, 'sss', $date_start, $date_end, $pttype);
            mysqli_stmt_execute($stmt_c);
            $rc = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_c));
            $total_cases = intval($rc['cnt'] ?? 0);
            $sum_income = floatval($rc['s_income'] ?? 0);
            $sum_debit = floatval($rc['s_debit'] ?? 0);
            mysqli_stmt_close($stmt_c);
        }

        // 2. ดึงรายชื่อคนไข้
        $sql_pts = "SELECT 
                        o.vn,
                        o.hn,
                        o.vstdate,
                        o.vsttime,
                        o.pttype,
                        CONCAT(pt.pname, pt.fname, ' ', pt.lname) AS ptname,
                        pt.cid,
                        IF(pt.sex = '1', 'ชาย', IF(pt.sex = '2', 'หญิง', '')) AS sex,
                        v.age_y AS age,
                        v.pdx,
                        sp.name AS department,
                        IFNULL(v.income, 0) AS income,
                        IFNULL(v.uc_money, 0) AS uc_money,
                        IFNULL(v.paid_money, 0) AS paid_money,
                        IFNULL(v.discount_money, 0) AS discount_money,
                        IFNULL(v.rcpt_money, 0) AS rcpt_money,
                        IFNULL(v.income, 0) - IFNULL(v.discount_money, 0) - IFNULL(v.rcpt_money, 0) AS debit
                    FROM ovst o
                    LEFT JOIN vn_stat v ON v.vn = o.vn
                    LEFT JOIN patient pt ON pt.hn = o.hn
                    LEFT JOIN spclty sp ON sp.spclty = o.spclty
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an IS NULL OR o.an = '')
                      AND o.pttype = ?
                    ORDER BY o.vstdate DESC, o.vn DESC
                    LIMIT ?";
        $stmt_p = mysqli_prepare($conn2, $sql_pts);
        if ($stmt_p) {
            mysqli_stmt_bind_param($stmt_p, 'sssi', $date_start, $date_end, $pttype, $limit);
            mysqli_stmt_execute($stmt_p);
            $res_p = mysqli_stmt_get_result($stmt_p);

            $vns = [];
            $rows_raw = [];
            while ($r = mysqli_fetch_assoc($res_p)) {
                $rows_raw[] = $r;
                $v_vn = trim($r['vn']);
                if (!empty($v_vn)) {
                    $vns[] = "'" . mysqli_real_escape_string($conn, $v_vn) . "'";
                }
            }
            mysqli_stmt_close($stmt_p);

            // เช็คสถานะใน eDHS ว่าเคยนำเข้าหรือออกบิลแล้วหรือยัง
            $edhs_status_map = [];
            if (!empty($vns)) {
                $vns_str = implode(',', $vns);
                $q_edhs = mysqli_query($conn, "SELECT vn, bill, mobile, follow_money, accountcode FROM imr_tb_debtor_rights_opd WHERE vn IN ($vns_str)");
                if ($q_edhs) {
                    while ($e = mysqli_fetch_assoc($q_edhs)) {
                        $has_bill = (!empty(trim($e['bill'])) && trim($e['bill']) !== '-') || (!empty(trim($e['mobile'])) && trim($e['mobile']) !== '-');
                        $edhs_status_map[trim($e['vn'])] = $has_bill ? 'BILLED' : 'IMPORTED';
                    }
                }
            }

            foreach ($rows_raw as $r) {
                $vn = trim($r['vn']);
                $patients[] = [
                    'vn_an' => $vn,
                    'hn' => trim($r['hn']),
                    'service_date' => $r['vstdate'],
                    'service_date_th' => format_date_thai_be($r['vstdate']),
                    'service_time' => substr($r['vsttime'] ?? '', 0, 5),
                    'ptname' => trim($r['ptname']),
                    'cid' => trim($r['cid']),
                    'sex' => $r['sex'],
                    'age' => intval($r['age']),
                    'pdx' => trim($r['pdx'] ?? ''),
                    'department' => trim($r['department'] ?? 'ผู้ป่วยนอก'),
                    'income' => floatval($r['income']),
                    'paid_money' => floatval($r['paid_money']),
                    'rcpt_money' => floatval($r['rcpt_money']),
                    'discount_money' => floatval($r['discount_money']),
                    'debit' => floatval($r['debit']),
                    'edhs_status' => $edhs_status_map[$vn] ?? 'NEW'
                ];
            }
        }

    } else {
        // IPD
        $sql_cnt = "SELECT COUNT(i.an) AS cnt, SUM(IFNULL(a.income, 0)) AS s_income, SUM(IFNULL(a.income, 0) - IFNULL(a.discount_money, 0) - IFNULL(a.rcpt_money, 0)) AS s_debit
                    FROM ipt i
                    LEFT JOIN an_stat a ON a.an = i.an
                    WHERE i.dchdate BETWEEN ? AND ?
                      AND i.pttype = ?";
        $stmt_c = mysqli_prepare($conn2, $sql_cnt);
        if ($stmt_c) {
            mysqli_stmt_bind_param($stmt_c, 'sss', $date_start, $date_end, $pttype);
            mysqli_stmt_execute($stmt_c);
            $rc = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_c));
            $total_cases = intval($rc['cnt'] ?? 0);
            $sum_income = floatval($rc['s_income'] ?? 0);
            $sum_debit = floatval($rc['s_debit'] ?? 0);
            mysqli_stmt_close($stmt_c);
        }

        $sql_pts = "SELECT 
                        i.an,
                        i.hn,
                        i.regdate,
                        i.dchdate,
                        i.dchtime,
                        i.pttype,
                        CONCAT(pt.pname, pt.fname, ' ', pt.lname) AS ptname,
                        pt.cid,
                        IF(pt.sex = '1', 'ชาย', IF(pt.sex = '2', 'หญิง', '')) AS sex,
                        a.age_y AS age,
                        a.pdx,
                        IFNULL(a.income, 0) AS income,
                        IFNULL(a.uc_money, 0) AS uc_money,
                        IFNULL(a.paid_money, 0) AS paid_money,
                        IFNULL(a.discount_money, 0) AS discount_money,
                        IFNULL(a.rcpt_money, 0) AS rcpt_money,
                        IFNULL(a.income, 0) - IFNULL(a.discount_money, 0) - IFNULL(a.rcpt_money, 0) AS debit
                    FROM ipt i
                    LEFT JOIN an_stat a ON a.an = i.an
                    LEFT JOIN patient pt ON pt.hn = i.hn
                    WHERE i.dchdate BETWEEN ? AND ?
                      AND i.pttype = ?
                    ORDER BY i.dchdate DESC, i.an DESC
                    LIMIT ?";
        $stmt_p = mysqli_prepare($conn2, $sql_pts);
        if ($stmt_p) {
            mysqli_stmt_bind_param($stmt_p, 'sssi', $date_start, $date_end, $pttype, $limit);
            mysqli_stmt_execute($stmt_p);
            $res_p = mysqli_stmt_get_result($stmt_p);

            $ans = [];
            $rows_raw = [];
            while ($r = mysqli_fetch_assoc($res_p)) {
                $rows_raw[] = $r;
                $v_an = trim($r['an']);
                if (!empty($v_an)) {
                    $ans[] = "'" . mysqli_real_escape_string($conn, $v_an) . "'";
                }
            }
            mysqli_stmt_close($stmt_p);

            $edhs_status_map = [];
            if (!empty($ans)) {
                $ans_str = implode(',', $ans);
                $q_edhs = mysqli_query($conn, "SELECT an, bill, mobile, follow_money, accountcode FROM imr_tb_debtor_rights_ipd WHERE an IN ($ans_str)");
                if ($q_edhs) {
                    while ($e = mysqli_fetch_assoc($q_edhs)) {
                        $has_bill = (!empty(trim($e['bill'])) && trim($e['bill']) !== '-') || (!empty(trim($e['mobile'])) && trim($e['mobile']) !== '-');
                        $edhs_status_map[trim($e['an'])] = $has_bill ? 'BILLED' : 'IMPORTED';
                    }
                }
            }

            foreach ($rows_raw as $r) {
                $an = trim($r['an']);
                $patients[] = [
                    'vn_an' => $an,
                    'hn' => trim($r['hn']),
                    'reg_date' => $r['regdate'],
                    'service_date' => $r['dchdate'],
                    'service_date_th' => format_date_thai_be($r['dchdate']),
                    'service_time' => substr($r['dchtime'] ?? '', 0, 5),
                    'ptname' => trim($r['ptname']),
                    'cid' => trim($r['cid']),
                    'sex' => $r['sex'],
                    'age' => intval($r['age']),
                    'pdx' => trim($r['pdx'] ?? ''),
                    'department' => 'ผู้ป่วยใน',
                    'income' => floatval($r['income']),
                    'paid_money' => floatval($r['paid_money']),
                    'rcpt_money' => floatval($r['rcpt_money']),
                    'discount_money' => floatval($r['discount_money']),
                    'debit' => floatval($r['debit']),
                    'edhs_status' => $edhs_status_map[$an] ?? 'NEW'
                ];
            }
        }
    }

    send_response('success', "ดึงรายชื่อผู้ป่วยสำเร็จ ($patient_type: $pttype)", [
        'patient_type' => $patient_type,
        'pttype' => $pttype,
        'pttypename' => $pttypename,
        'date_start' => $date_start,
        'date_end' => $date_end,
        'total_cases' => $total_cases,
        'sum_income' => $sum_income,
        'sum_debit' => $sum_debit,
        'showing_count' => count($patients),
        'patients' => $patients
    ]);
}

/**
 * ====================================================================================
 * ACTION: pull_batch
 * ดึงข้อมูลผู้ป่วยรายตัว 35 คอลัมน์จาก HOSxP และบันทึกลงตารางจริงทีละ Batch (Chunk Ingestion)
 * ====================================================================================
 */
if ($action === 'pull_batch') {
    $mode = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : 'month';
    $patient_type = isset($_REQUEST['patient_type']) ? strtolower(trim($_REQUEST['patient_type'])) : 'opd'; // opd หรือ ipd
    $offset = isset($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0;
    $batch_size = isset($_REQUEST['batch_size']) ? intval($_REQUEST['batch_size']) : 500;
    if ($batch_size <= 0 || $batch_size > 1000) $batch_size = 500;
    
    $date_start = '';
    $date_end = '';
    $monthtxt = '';
    
    if ($mode === 'month') {
        $month_input = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : '';
        $year_input = isset($_REQUEST['year']) ? trim($_REQUEST['year']) : '';
        if (strpos($month_input, '-') !== false && empty($year_input)) {
            $parts = explode('-', $month_input);
            $month_input = $parts[0];
            $year_input = $parts[1];
        }
        $m = intval($month_input);
        $y = intval($year_input);
        $year_ad = ($y > 2400) ? ($y - 543) : $y;
        $monthtxt = sprintf('%d-%d', $m, $year_ad);
        $date_start = sprintf('%04d-%02d-01', $year_ad, $m);
        $days_in_m = date('t', strtotime($date_start));
        $date_end = sprintf('%04d-%02d-%02d', $year_ad, $m, $days_in_m);
    } else {
        $raw_start = isset($_REQUEST['date_start']) ? trim($_REQUEST['date_start']) : '';
        $raw_end = isset($_REQUEST['date_end']) ? trim($_REQUEST['date_end']) : '';
        $date_start = normalize_to_gregorian_date($raw_start);
        $date_end = normalize_to_gregorian_date($raw_end);
        if ($date_start > $date_end) {
            $tmp = $date_start;
            $date_start = $date_end;
            $date_end = $tmp;
        }
        $start_ts = strtotime($date_start);
        $monthtxt = sprintf('%d-%d', intval(date('m', $start_ts)), intval(date('Y', $start_ts)));
    }
    
    // กรองเฉพาะสิทธิที่เลือก (selected_rights)
    $selected_rights = [];
    if (isset($_REQUEST['selected_rights'])) {
        $raw_sr = $_REQUEST['selected_rights'];
        if (is_array($raw_sr)) {
            $selected_rights = $raw_sr;
        } else {
            $decoded = json_decode($raw_sr, true);
            if (is_array($decoded)) {
                $selected_rights = $decoded;
            } else {
                $selected_rights = array_filter(array_map('trim', explode(',', (string)$raw_sr)));
            }
        }
    }
    
    // คัดกรองรหัสสิทธิ (ลบ prefix OPD: หรือ IPD: ออกถ้ามี)
    $clean_rights = [];
    foreach ($selected_rights as $sr) {
        $sr_str = trim((string)$sr);
        if (strpos($sr_str, ':') !== false) {
            list($pt, $pcode) = explode(':', $sr_str, 2);
            if (strtolower($pt) === $patient_type) {
                $clean_rights[] = $pcode;
            }
        } else {
            $clean_rights[] = $sr_str;
        }
    }
    $clean_rights = array_unique(array_filter($clean_rights));
    
    // รับ Custom Pttype Mappings จาก client หรือ saved config
    $custom_mappings = [];
    if (isset($_REQUEST['custom_mappings'])) {
        $raw_cm = $_REQUEST['custom_mappings'];
        if (is_array($raw_cm)) {
            $custom_mappings = $raw_cm;
        } else {
            $decoded = json_decode($raw_cm, true);
            if (is_array($decoded)) {
                $custom_mappings = $decoded;
            }
        }
    }
    if (!empty($custom_mappings)) {
        save_custom_pttype_mappings($custom_mappings);
    }
    $saved_mappings = get_custom_pttype_mappings();
    
    $categorized_accounts = get_categorized_debtor_accounts($conn);
    $all_accounts_lookup = [];
    foreach ($categorized_accounts['all'] as $acc_item) {
        $all_accounts_lookup[$acc_item['code']] = $acc_item['name'];
    }
    
    // ดึงรหัสโรงพยาบาล hospcode
    global $configData;
    $hospcode = $configData['hospcode'] ?? '';
    if (empty($hospcode)) {
        $r_hosp = mysqli_fetch_assoc(mysqli_query($conn2, "SELECT hospitalcode FROM opdconfig LIMIT 1"));
        $hospcode = $r_hosp['hospitalcode'] ?? '';
    }
    
    $known_accounts = [
        '1102050101.201' => 'ลูกหนี้ค่ารักษา UC- OP ใน CUP',
        '1102050101.202' => 'ลูกหนี้ค่ารักษา UC - IP',
        '1102050101.203' => 'ลูกหนี้ค่ารักษา UC - OP นอก CUP (ในจังหวัดสังกัด สธ.)',
        '1102050101.204' => 'ลูกหนี้ค่ารักษา UC - OP นอก CUP (ต่างจังหวัด)',
        '1102050101.205' => 'ลูกหนี้ค่ารักษา UC - IP นอก CUP (ในจังหวัด)',
        '1102050101.206' => 'ลูกหนี้ค่ารักษา UC - IP นอก CUP (ต่างจังหวัด)',
        '1102050101.209' => 'ลูกหนี้ค่ารักษาด้านการสร้างเสริมสุขภาพและป้องกันโรค (P&P)',
        '1102050101.216' => 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)',
        '1102050101.217' => 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)',
        '1102050101.301' => 'ลูกหนี้ค่ารักษาประกันสังคม OP -เครือข่าย',
        '1102050101.302' => 'ลูกหนี้ค่ารักษาประกันสังคม IP -เครือข่าย',
        '1102050101.307' => 'ลูกหนี้ค่ารักษาประกันสังคม - กองทุนทดแทน',
        '1102050101.308' => 'ลูกหนี้ค่ารักษาประกันสังคม - นอกเครือข่าย IP',
        '1102050101.309' => 'ลูกหนี้ค่ารักษาประกันสังคม - บริการทางการแพทย์เฉพาะทาง OP',
        '1102050101.310' => 'ลูกหนี้ค่ารักษาประกันสังคม - บริการทางการแพทย์เฉพาะทาง IP',
        '1102050101.401' => 'ลูกหนี้ค่ารักษา-เบิกจ่ายตรงกรมบัญชีกลาง OP',
        '1102050101.402' => 'ลูกหนี้ค่ารักษา-เบิกจ่ายตรงกรมบัญชีกลาง IP',
        '1102050102.106' => 'ลูกหนี้ค่ารักษา - ชำระเงิน OP',
        '1102050102.201' => 'ลูกหนี้ค่ารักษา UC ต่างสังกัด สป. (AE OP)',
        '1102050102.602' => 'ลูกหนี้ค่ารักษา - พรบ.รถ OP',
        '1102050102.801' => 'ลูกหนี้ค่ารักษา - เบิกจ่ายตรง อปท. OP',
        '1102050102.802' => 'ลูกหนี้ค่ารักษา - เบิกจ่ายตรง อปท. IP'
    ];
    
    $batch_inserted = 0;
    $batch_updated = 0;
    $batch_skipped = 0;
    $rows_processed = 0;

    // ตรวจสอบความปลอดภัย: หากผังบัญชีที่เลือกได้รับการยืนยันตั้งลูกหนี้ยกไปในระบบทะเบียนคุมแล้ว (imr_tb_debtor_result.column10 > 0)
    $escaped_m_pull = mysqli_real_escape_string($conn, $monthtxt);
    $q_deb_chk = mysqli_query($conn, "SELECT code, column10 FROM imr_tb_debtor_result WHERE month = '$escaped_m_pull' AND column10 > 0");
    $confirmed_accs = [];
    if ($q_deb_chk) {
        while ($drc = mysqli_fetch_assoc($q_deb_chk)) {
            $confirmed_accs[] = trim($drc['code']);
        }
    }
    
    // ====================================================================
    // กรณีผู้ป่วยนอก (OPD)
    // ====================================================================
    if ($patient_type === 'opd') {
        // เงื่อนไขสิทธิ
        $rights_sql = "";
        if (!empty($clean_rights)) {
            $escaped_rights = array_map(function($val) use ($conn2) {
                return "'" . mysqli_real_escape_string($conn2, $val) . "'";
            }, $clean_rights);
            $rights_sql = " AND o.pttype IN (" . implode(',', $escaped_rights) . ") ";
        }
        
        // นับจำนวนเคสทั้งหมดสำหรับ criteria นี้ (เฉพาะรอบแรก offset = 0)
        $total_cases = 0;
        if (isset($_REQUEST['total_cases']) && intval($_REQUEST['total_cases']) > 0) {
            $total_cases = intval($_REQUEST['total_cases']);
        } else {
            $cnt_sql = "SELECT COUNT(o.vn) AS c FROM ovst o WHERE o.vstdate BETWEEN ? AND ? AND (o.an IS NULL OR o.an = '') $rights_sql";
            $cnt_stmt = mysqli_prepare($conn2, $cnt_sql);
            mysqli_stmt_bind_param($cnt_stmt, 'ss', $date_start, $date_end);
            mysqli_stmt_execute($cnt_stmt);
            $cnt_res = mysqli_stmt_get_result($cnt_stmt);
            $total_cases = intval(mysqli_fetch_assoc($cnt_res)['c'] ?? 0);
            mysqli_stmt_close($cnt_stmt);
        }
        
        // ดึงชุดข้อมูลตาม offset และ limit
        $pull_sql = "SELECT 
                        o.vn,
                        IFNULL(o.an, '') AS an,
                        o.hn,
                        pt.cid,
                        CONCAT(pt.pname, pt.fname, ' ', pt.lname) AS ptname,
                        IF(pt.sex = '1', 'ชาย', IF(pt.sex = '2', 'หญิง', '')) AS sex,
                        v.age_y AS age,
                        pt.nationality AS nation,
                        o.vstdate,
                        o.vsttime,
                        o.pt_subtype AS ptsubtype,
                        sp.name AS department,
                        sp.name AS clinic,
                        ptt.pttype_eclaim_id,
                        e.name AS pttype_eclaim_name,
                        o.pttype,
                        ptt.name AS pttypename,
                        e.ar_opd AS accountcode,
                        ca.name AS accountname,
                        IFNULL(v.income, 0) AS income,
                        IFNULL(v.uc_money, 0) AS uc_money,
                        IFNULL(v.discount_money, 0) AS discount_money,
                        IFNULL(v.paid_money, 0) AS paid_money,
                        IFNULL(v.rcpt_money, 0) AS rcpt_money,
                        v.rcpno_list AS rcpno,
                        IFNULL(v.income, 0) - IFNULL(v.discount_money, 0) - IFNULL(v.rcpt_money, 0) AS debit,
                        COALESCE(NULLIF(TRIM(pt.mobile_phone_number), ''), NULLIF(TRIM(pt.hometel), ''), NULLIF(TRIM(pt.informtel), '')) AS mobile_contact,
                        pt.hometel AS tel
                    FROM ovst o
                    LEFT JOIN vn_stat v ON v.vn = o.vn
                    LEFT JOIN patient pt ON pt.hn = o.hn
                    LEFT JOIN pttype ptt ON ptt.pttype = o.pttype
                    LEFT JOIN pttype_eclaim e ON e.code = ptt.pttype_eclaim_id
                    LEFT JOIN codeaccount ca ON ca.code = e.ar_opd
                    LEFT JOIN spclty sp ON sp.spclty = o.spclty
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an IS NULL OR o.an = '')
                      $rights_sql
                    ORDER BY o.vn ASC
                    LIMIT ?, ?";
                    
        $stmt_pull = mysqli_prepare($conn2, $pull_sql);
        mysqli_stmt_bind_param($stmt_pull, 'ssii', $date_start, $date_end, $offset, $batch_size);
        mysqli_stmt_execute($stmt_pull);
        $res_pull = mysqli_stmt_get_result($stmt_pull);
        
        $rows_batch = [];
        $vns_list = [];
        while ($r = mysqli_fetch_assoc($res_pull)) {
            $rows_batch[] = $r;
            $v_vn = trim($r['vn']);
            if (!empty($v_vn)) {
                $vns_list[] = "'" . mysqli_real_escape_string($conn, $v_vn) . "'";
            }
        }
        mysqli_stmt_close($stmt_pull);
        
        // Prefetch เคสเดิมใน eDHS เพื่อตรวจจับ "ตั้งลูกหนี้แล้ว" หรือ "ออกใบเสร็จแล้ว" (Anti-Duplicate & Anti-Overwrite)
        // Prefetch เคสเดิมใน eDHS เพื่อตรวจจับ "ตั้งลูกหนี้แล้ว" หรือ "ออกใบเสร็จแล้ว" (Anti-Duplicate & Anti-Overwrite)
        $existing_edhs = [];
        if (!empty($vns_list)) {
            $vns_in = implode(',', $vns_list);
            $q_chk = mysqli_query($conn, "SELECT vn, no, accountcode, debit, bill, mobile, follow_money FROM imr_tb_debtor_rights_opd WHERE vn IN ($vns_in)");
            if ($q_chk) {
                while ($c = mysqli_fetch_assoc($q_chk)) {
                    $vn_key = trim($c['vn']);
                    $has_bill = (!empty(trim($c['bill'])) && trim($c['bill']) !== '-');
                    $has_mobile_receipt = (!empty(trim($c['mobile'])) && trim($c['mobile']) !== '-');
                    $has_follow = (floatval($c['follow_money']) > 0);
                    $has_acc = (!empty(trim($c['accountcode'])));
                    $has_debit = (floatval($c['debit']) > 0);
                    
                    $existing_edhs[$vn_key] = [
                        'no' => !empty($c['no']) ? intval($c['no']) : null,
                        'accountcode' => trim($c['accountcode']),
                        'has_bill' => ($has_bill || $has_mobile_receipt || $has_follow),
                        'is_debtor_set' => ($has_acc && $has_debit)
                    ];
                }
            }
            $q_ph = mysqli_query($conn, "SELECT DISTINCT ref_vn_an FROM imr_tb_payment_history WHERE ref_vn_an IN ($vns_in)");
            if ($q_ph) {
                while ($ph = mysqli_fetch_assoc($q_ph)) {
                    $vn_key = trim($ph['ref_vn_an']);
                    if (isset($existing_edhs[$vn_key])) {
                        $existing_edhs[$vn_key]['has_bill'] = true;
                    } else {
                        $existing_edhs[$vn_key] = ['no' => null, 'accountcode' => '', 'has_bill' => true, 'is_debtor_set' => true];
                    }
                }
            }
        }
        
        // คำนวณลำดับที่ (no) ต่อเนื่องสำหรับ OPD
        $max_no_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(no), 0) AS max_no FROM imr_tb_debtor_rights_opd WHERE monthtxt = '$escaped_m_pull'"));
        $current_no_seq = max(intval($max_no_res['max_no'] ?? 0), $offset);

        // เตรียม Prepared Statement บันทึกข้อมูลลง eDHS
        $ins_sql = "INSERT INTO imr_tb_debtor_rights_opd (
                        no, vn, an, hn, cid, ptname, sex, age, nation, vstdate, vsttime, ptsubtype,
                        department, clinic, pttype_eclaim_id, pttype_eclaim_name, pttype, pttypename,
                        accountcode, accountname, income, uc_money, discount_money, paid_money, rcpt_money,
                        rcpno, debit, totalall, mobile, tel, hospcode, monthtxt
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        no = IF(no IS NOT NULL AND no > 0, no, VALUES(no)),
                        debit = IF((bill IS NOT NULL AND TRIM(bill) != '' AND TRIM(bill) != '-') OR (mobile IS NOT NULL AND TRIM(mobile) != '' AND TRIM(mobile) != '-') OR (follow_money IS NOT NULL AND CAST(follow_money AS DECIMAL(15,2)) > 0), debit, VALUES(debit))";
                        
        $stmt_ins = mysqli_prepare($conn, $ins_sql);
        if (!$stmt_ins) {
            send_response('error', 'Prepare Insert OPD Failed: ' . mysqli_error($conn));
        }
        
        foreach ($rows_batch as $row) {
            $rows_processed++;
            $vn = trim($row['vn']);
            
            // กฎเหล็ก: "ถ้าตรวจเจอว่ามีการตั้งลูกหนี้หรือออกใบเสร็จ ห้ามนำเข้าซ้ำ"
            if (isset($existing_edhs[$vn])) {
                $ex = $existing_edhs[$vn];
                if ($ex['has_bill'] || $ex['is_debtor_set']) {
                    $batch_skipped++;
                    continue; // ข้ามทันที ไม่บันทึกซ้ำ ไม่เขียนทับข้อมูลเดิม 100%
                }
            }
            
            // กำหนดลำดับที่ no (ถ้ามีเดิมอยู่แล้วให้ใช้เดิม ถ้ายังไม่มีให้รันต่อตามลำดับ)
            if (isset($existing_edhs[$vn]['no']) && intval($existing_edhs[$vn]['no']) > 0) {
                $row_no = intval($existing_edhs[$vn]['no']);
            } else {
                $current_no_seq++;
                $row_no = $current_no_seq;
            }

            $an = trim($row['an']);
            $hn = trim($row['hn']);
            $cid_enc = encrypt_data(trim($row['cid'] ?? ''));
            
            // เบอร์โทรศัพท์ติดต่อจริงเก็บใน tel_enc
            $raw_tel = !empty(trim($row['mobile_contact'] ?? '')) ? trim($row['mobile_contact']) : trim($row['tel'] ?? '');
            $tel_enc = encrypt_data($raw_tel);
            
            $ptname = trim($row['ptname']);
            $sex = trim($row['sex']);
            $age = trim((string)$row['age']);
            $nation = trim((string)$row['nation']);
            $vstdate_be = format_date_thai_be($row['vstdate']);
            $vsttime = trim((string)$row['vsttime']);
            $ptsubtype = trim((string)$row['ptsubtype']);
            $department = trim((string)$row['department']);
            $clinic = trim((string)$row['clinic']);
            $pttype_eclaim_id = trim((string)$row['pttype_eclaim_id']);
            $pttype_eclaim_name = trim((string)$row['pttype_eclaim_name']);
            $pttype = trim((string)$row['pttype']);
            $pttypename = trim((string)$row['pttypename']);
            
            $accountcode = trim((string)$row['accountcode']);
            $accountname = trim((string)$row['accountname']);
            
            // นำ Custom Mapping มาใช้กรณีไม่มีผังบัญชีจาก HOSxP
            if (empty($accountcode)) {
                if (isset($custom_mappings["OPD:$pttype"]) && !empty($custom_mappings["OPD:$pttype"])) {
                    $accountcode = trim($custom_mappings["OPD:$pttype"]);
                } elseif (isset($custom_mappings[$pttype]) && !empty($custom_mappings[$pttype])) {
                    $accountcode = trim($custom_mappings[$pttype]);
                } elseif (isset($saved_mappings['OPD'][$pttype]) && !empty($saved_mappings['OPD'][$pttype])) {
                    $accountcode = trim($saved_mappings['OPD'][$pttype]);
                }
            }
            
            // ตรวจสอบว่าผังบัญชีนี้ยืนยันตั้งลูกหนี้ในทะเบียนคุมแล้วหรือไม่
            if (!empty($accountcode) && in_array($accountcode, $confirmed_accs)) {
                $batch_skipped++;
                continue; // ข้ามสิทธิที่ยืนยันตั้งลูกหนี้ในระบบบัญชีแล้ว
            }
            
            if (isset($all_accounts_lookup[$accountcode])) {
                $accountname = $all_accounts_lookup[$accountcode];
            } elseif (isset($known_accounts[$accountcode])) {
                $accountname = $known_accounts[$accountcode];
            }
            
            $income = (string)$row['income'];
            $uc_money = (string)$row['uc_money'];
            $discount_money = (string)$row['discount_money'];
            $paid_money = (string)$row['paid_money'];
            $rcpt_money = (string)$row['rcpt_money'];
            $rcpno = trim((string)$row['rcpno']);
            $debit = (string)$row['debit'];
            $totalall = $income;
            
            // ข้อกำหนดผู้ใช้: ข้อมูลใหม่ที่จะดึงจาก hosxp ฟิลด์ mobile จะใส่ '-' เป็นค่าแรก
            $mobile = '-';
            
            // คำนวณ monthtxt ตามวันรับบริการจริง
            $row_monthtxt = $monthtxt;
            if (!empty($row['vstdate'])) {
                $r_ts = strtotime($row['vstdate']);
                if ($r_ts !== false) {
                    $row_monthtxt = sprintf('%d-%d', intval(date('m', $r_ts)), intval(date('Y', $r_ts)));
                }
            }
            
            mysqli_stmt_bind_param(
                $stmt_ins,
                'isssssssssssssssssssssssssssssss',
                $row_no, $vn, $an, $hn, $cid_enc, $ptname, $sex, $age, $nation, $vstdate_be, $vsttime, $ptsubtype,
                $department, $clinic, $pttype_eclaim_id, $pttype_eclaim_name, $pttype, $pttypename,
                $accountcode, $accountname, $income, $uc_money, $discount_money, $paid_money, $rcpt_money,
                $rcpno, $debit, $totalall, $mobile, $tel_enc, $hospcode, $row_monthtxt
            );
            
            mysqli_stmt_execute($stmt_ins);
            $aff = mysqli_stmt_affected_rows($stmt_ins);
            if ($aff === 1) {
                $batch_inserted++;
            } elseif ($aff === 2 || $aff === 0) {
                $batch_updated++;
            }
        }
        mysqli_stmt_close($stmt_ins);
        
        // --------------------------------------------------------
        // ปรับยอดภาระหนี้เหมาจ่ายผัง .203 และ .201 ตาม debtor_setting (เฉพาะรอบสุดท้ายเพื่อประสิทธิภาพ)
        // --------------------------------------------------------
        $next_offset_tmp = $offset + $rows_processed;
        $is_last_batch_opd = ($rows_processed < $batch_size || $next_offset_tmp >= $total_cases);
        
        if ($is_last_batch_opd) {
            $config_file = dirname(__DIR__) . '/database_config/config.json';
            if (file_exists($config_file)) {
                $config_data = json_decode(file_get_contents($config_file), true);
                if (isset($config_data['debtor_setting']) && ($config_data['debtor_setting']['enabled'] ?? '0') === '1') {
                    require_once dirname(__DIR__) . '/database_config/db_helper.php';
                    $ds_cfg = $config_data['debtor_setting'];
                    if (is_debtor_setting_effective_for_month($ds_cfg, $monthtxt)) {
                        $escaped_m = mysqli_real_escape_string($conn, $monthtxt);
                        $not_kidney_sql = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%' AND (department IS NULL OR department NOT LIKE '%ไต%') AND (clinic IS NULL OR clinic NOT LIKE '%ไต%'))";
                        $not_billed_sql = "AND (bill IS NULL OR bill = '' OR bill = '-') AND (mobile IS NULL OR mobile = '' OR mobile = '-')";
                        
                        $acc_203_base = floatval($ds_cfg['acc_203']['base_rate'] ?? 175.0);
                        mysqli_query($conn, "UPDATE imr_tb_debtor_rights_opd SET original_debit = debit WHERE accountcode = '1102050101.203' AND monthtxt = '$escaped_m' AND original_debit IS NULL $not_kidney_sql $not_billed_sql");
                        mysqli_query($conn, "UPDATE imr_tb_debtor_rights_opd SET debit = $acc_203_base WHERE accountcode = '1102050101.203' AND monthtxt = '$escaped_m' $not_kidney_sql $not_billed_sql");
                        
                        $acc_102_ae_max = floatval($ds_cfg['acc_102_201']['ae_max'] ?? 700.0);
                        mysqli_query($conn, "UPDATE imr_tb_debtor_rights_opd SET original_debit = debit WHERE accountcode = '1102050102.201' AND monthtxt = '$escaped_m' AND original_debit IS NULL $not_billed_sql");
                        mysqli_query($conn, "UPDATE imr_tb_debtor_rights_opd SET debit = LEAST(original_debit, $acc_102_ae_max) WHERE accountcode = '1102050102.201' AND monthtxt = '$escaped_m' $not_billed_sql");
                    }
                }
            }
        }
    }
    
    // ====================================================================
    // กรณีผู้ป่วยใน (IPD)
    // ====================================================================
    if ($patient_type === 'ipd') {
        $cr_config = get_active_cr_config($conn);
        $auto_split_ipd = is_cr_auto_split_enabled($cr_config, 'IPD');

        $rights_sql = "";
        if (!empty($clean_rights)) {
            $escaped_rights = array_map(function($val) use ($conn2) {
                return "'" . mysqli_real_escape_string($conn2, $val) . "'";
            }, $clean_rights);
            $rights_sql = " AND i.pttype IN (" . implode(',', $escaped_rights) . ") ";
        }
        
        $total_cases = 0;
        if (isset($_REQUEST['total_cases']) && intval($_REQUEST['total_cases']) > 0) {
            $total_cases = intval($_REQUEST['total_cases']);
        } else {
            $cnt_sql = "SELECT COUNT(i.an) AS c FROM ipt i WHERE i.dchdate BETWEEN ? AND ? $rights_sql";
            $cnt_stmt = mysqli_prepare($conn2, $cnt_sql);
            mysqli_stmt_bind_param($cnt_stmt, 'ss', $date_start, $date_end);
            mysqli_stmt_execute($cnt_stmt);
            $cnt_res = mysqli_stmt_get_result($cnt_stmt);
            $total_cases = intval(mysqli_fetch_assoc($cnt_res)['c'] ?? 0);
            mysqli_stmt_close($cnt_stmt);
        }
        
        $pull_sql = "SELECT 
                        i.an,
                        i.hn,
                        pt.cid,
                        CONCAT(pt.pname, pt.fname, ' ', pt.lname) AS ptname,
                        IF(pt.sex = '1', 'ชาย', IF(pt.sex = '2', 'หญิง', '')) AS sex,
                        pt.birthday,
                        a.age_y AS age,
                        pt.nationality AS nation,
                        i.regdate AS admdate,
                        i.dchdate,
                        ptt.pttype_eclaim_id,
                        e.name AS pttype_eclaim_name,
                        i.pttype,
                        ptt.name AS pttypename,
                        e.ar_ipd AS accountcode,
                        ca.name AS accountname,
                        'Discharge' AS status,
                        IFNULL(a.income, 0) AS income,
                        IFNULL(a.uc_money, 0) AS uc_money,
                        IFNULL(a.discount_money, 0) AS discount_money,
                        IFNULL(a.paid_money, 0) AS paid_money,
                        IFNULL(a.rcpt_money, 0) AS rcpt_money,
                        a.rcpno_list AS rcpno,
                        IFNULL(a.income, 0) - IFNULL(a.discount_money, 0) - IFNULL(a.rcpt_money, 0) AS debit,
                        (SELECT MAX(max_debt_amount) FROM ipt_pttype WHERE an = i.an) AS max_debt_amount,
                        COALESCE(NULLIF(TRIM(pt.mobile_phone_number), ''), NULLIF(TRIM(pt.hometel), ''), NULLIF(TRIM(pt.informtel), '')) AS mobile_contact,
                        pt.hometel AS tel
                    FROM ipt i
                    LEFT JOIN an_stat a ON a.an = i.an
                    LEFT JOIN patient pt ON pt.hn = i.hn
                    LEFT JOIN pttype ptt ON ptt.pttype = i.pttype
                    LEFT JOIN pttype_eclaim e ON e.code = ptt.pttype_eclaim_id
                    LEFT JOIN codeaccount ca ON ca.code = e.ar_ipd
                    WHERE i.dchdate BETWEEN ? AND ?
                      $rights_sql
                    ORDER BY i.an ASC
                    LIMIT ?, ?";
                    
        $stmt_pull = mysqli_prepare($conn2, $pull_sql);
        mysqli_stmt_bind_param($stmt_pull, 'ssii', $date_start, $date_end, $offset, $batch_size);
        mysqli_stmt_execute($stmt_pull);
        $res_pull = mysqli_stmt_get_result($stmt_pull);
        
        $rows_batch = [];
        $ans_list = [];
        while ($r = mysqli_fetch_assoc($res_pull)) {
            $rows_batch[] = $r;
            $v_an = trim($r['an']);
            if (!empty($v_an)) {
                $ans_list[] = "'" . mysqli_real_escape_string($conn, $v_an) . "'";
            }
        }
        mysqli_stmt_close($stmt_pull);
        
        // Prefetch เคสเดิมใน eDHS เพื่อตรวจจับ "ตั้งลูกหนี้แล้ว" หรือ "ออกใบเสร็จแล้ว" (IPD)
        $existing_edhs = [];
        if (!empty($ans_list)) {
            $ans_in = implode(',', $ans_list);
            $q_chk = mysqli_query($conn, "SELECT an, no, accountcode, debit, bill, mobile, follow_money FROM imr_tb_debtor_rights_ipd WHERE an IN ($ans_in)");
            if ($q_chk) {
                while ($c = mysqli_fetch_assoc($q_chk)) {
                    $an_key = trim($c['an']);
                    $has_bill = (!empty(trim($c['bill'])) && trim($c['bill']) !== '-');
                    $has_mobile_receipt = (!empty(trim($c['mobile'])) && trim($c['mobile']) !== '-');
                    $has_follow = (floatval($c['follow_money']) > 0);
                    $has_acc = (!empty(trim($c['accountcode'])));
                    $has_debit = (floatval($c['debit']) > 0);
                    
                    $existing_edhs[$an_key] = [
                        'no' => !empty($c['no']) ? intval($c['no']) : null,
                        'accountcode' => trim($c['accountcode']),
                        'has_bill' => ($has_bill || $has_mobile_receipt || $has_follow),
                        'is_debtor_set' => ($has_acc && $has_debit)
                    ];
                }
            }
            $q_ph = mysqli_query($conn, "SELECT DISTINCT ref_vn_an FROM imr_tb_payment_history WHERE ref_vn_an IN ($ans_in)");
            if ($q_ph) {
                while ($ph = mysqli_fetch_assoc($q_ph)) {
                    $an_key = trim($ph['ref_vn_an']);
                    if (isset($existing_edhs[$an_key])) {
                        $existing_edhs[$an_key]['has_bill'] = true;
                    } else {
                        $existing_edhs[$an_key] = ['no' => null, 'accountcode' => '', 'has_bill' => true, 'is_debtor_set' => true];
                    }
                }
            }
        }
        
        // คำนวณลำดับที่ (no) ต่อเนื่องสำหรับ IPD
        $max_no_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(no), 0) AS max_no FROM imr_tb_debtor_rights_ipd WHERE monthtxt = '$escaped_m_pull'"));
        $current_no_seq = max(intval($max_no_res['max_no'] ?? 0), $offset);

        $ins_sql = "INSERT INTO imr_tb_debtor_rights_ipd (
                        no, an, hn, cid, ptname, sex, birthday, age, nation, admdate, dchdate,
                        pttype_eclaim_id, pttype_eclaim_name, pttype, pttypename, accountcode, accountname,
                        status, income, uc_money, discount_money, paid_money, rcpt_money, rcpno,
                        debit, max_debt_amount, totalall, mobile, tel, hospcode, monthtxt
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        no = IF(no IS NOT NULL AND no > 0, no, VALUES(no)),
                        debit = IF((bill IS NOT NULL AND TRIM(bill) != '' AND TRIM(bill) != '-') OR (mobile IS NOT NULL AND TRIM(mobile) != '' AND TRIM(mobile) != '-') OR (follow_money IS NOT NULL AND CAST(follow_money AS DECIMAL(15,2)) > 0), debit, VALUES(debit))";
                        
        $stmt_ins = mysqli_prepare($conn, $ins_sql);
        if (!$stmt_ins) {
            send_response('error', 'Prepare Insert IPD Failed: ' . mysqli_error($conn));
        }
        
        foreach ($rows_batch as $row) {
            $rows_processed++;
            $an = trim($row['an']);
            
            // กฎเหล็ก: "ถ้าตรวจเจอว่ามีการตั้งลูกหนี้หรือออกใบเสร็จ ห้ามนำเข้าซ้ำ"
            if (isset($existing_edhs[$an])) {
                $ex = $existing_edhs[$an];
                if ($ex['has_bill'] || $ex['is_debtor_set']) {
                    $batch_skipped++;
                    continue; // ข้ามทันที ไม่บันทึกซ้ำ ไม่เขียนทับข้อมูลเดิม 100%
                }
            }
            
            // กำหนดลำดับที่ no (ถ้ามีเดิมอยู่แล้วให้ใช้เดิม ถ้ายังไม่มีให้รันต่อตามลำดับ)
            if (isset($existing_edhs[$an]['no']) && intval($existing_edhs[$an]['no']) > 0) {
                $row_no = intval($existing_edhs[$an]['no']);
            } else {
                $current_no_seq++;
                $row_no = $current_no_seq;
            }

            $hn = trim($row['hn']);
            $cid_enc = encrypt_data(trim($row['cid'] ?? ''));
            
            // เบอร์โทรศัพท์ติดต่อจริงเก็บใน tel_enc
            $raw_tel = !empty(trim($row['mobile_contact'] ?? '')) ? trim($row['mobile_contact']) : trim($row['tel'] ?? '');
            $tel_enc = encrypt_data($raw_tel);
            
            $ptname = trim($row['ptname']);
            $sex = trim($row['sex']);
            $birthday_be = format_date_thai_be($row['birthday']);
            $age = trim((string)$row['age']);
            $nation = trim((string)$row['nation']);
            $admdate_be = format_date_thai_be($row['admdate']);
            $dchdate_be = format_date_thai_be($row['dchdate']);
            $pttype_eclaim_id = trim((string)$row['pttype_eclaim_id']);
            $pttype_eclaim_name = trim((string)$row['pttype_eclaim_name']);
            $pttype = trim((string)$row['pttype']);
            $pttypename = trim((string)$row['pttypename']);
            
            $accountcode = trim((string)$row['accountcode']);
            $accountname = trim((string)$row['accountname']);

            // Safeguard: ถ้าไม่ได้เปิดระบบ CR IPD ให้ยุบผังลูก 1102050101.217 รวมเข้ากับ 1102050101.202 ทันที
            if (!$auto_split_ipd && $accountcode === '1102050101.217') {
                $accountcode = '1102050101.202';
                $accountname = 'ลูกหนี้ค่ารักษา UC - IP';
            }
            
            // นำ Custom Mapping มาใช้กรณีไม่มีผังบัญชีจาก HOSxP
            if (empty($accountcode)) {
                if (isset($custom_mappings["IPD:$pttype"]) && !empty($custom_mappings["IPD:$pttype"])) {
                    $accountcode = trim($custom_mappings["IPD:$pttype"]);
                } elseif (isset($custom_mappings[$pttype]) && !empty($custom_mappings[$pttype])) {
                    $accountcode = trim($custom_mappings[$pttype]);
                } elseif (isset($saved_mappings['IPD'][$pttype]) && !empty($saved_mappings['IPD'][$pttype])) {
                    $accountcode = trim($saved_mappings['IPD'][$pttype]);
                }
            }
            
            // ตรวจสอบว่าผังบัญชีนี้ยืนยันตั้งลูกหนี้ในทะเบียนคุมแล้วหรือไม่
            if (!empty($accountcode) && in_array($accountcode, $confirmed_accs)) {
                $batch_skipped++;
                continue; // ข้ามสิทธิที่ยืนยันตั้งลูกหนี้ในระบบบัญชีแล้ว
            }
            
            if (isset($all_accounts_lookup[$accountcode])) {
                $accountname = $all_accounts_lookup[$accountcode];
            } elseif (isset($known_accounts[$accountcode])) {
                $accountname = $known_accounts[$accountcode];
            }
            
            $status = trim((string)$row['status']);
            $income = (string)$row['income'];
            $uc_money = (string)$row['uc_money'];
            $discount_money = (string)$row['discount_money'];
            $paid_money = (string)$row['paid_money'];
            $rcpt_money = (string)$row['rcpt_money'];
            $rcpno = trim((string)$row['rcpno']);
            $debit = (string)$row['debit'];
            $max_debt_amount = trim((string)$row['max_debt_amount']);
            $totalall = $income;
            
            // ข้อกำหนดผู้ใช้: ข้อมูลใหม่ที่จะดึงจาก hosxp ฟิลด์ mobile จะใส่ '-' เป็นค่าแรก
            $mobile = '-';
            
            $row_monthtxt = $monthtxt;
            if (!empty($row['dchdate'])) {
                $r_ts = strtotime($row['dchdate']);
                if ($r_ts !== false) {
                    $row_monthtxt = sprintf('%d-%d', intval(date('m', $r_ts)), intval(date('Y', $r_ts)));
                }
            }
            
            mysqli_stmt_bind_param(
                $stmt_ins,
                'issssssssssssssssssssssssssssss',
                $row_no, $an, $hn, $cid_enc, $ptname, $sex, $birthday_be, $age, $nation, $admdate_be, $dchdate_be,
                $pttype_eclaim_id, $pttype_eclaim_name, $pttype, $pttypename, $accountcode, $accountname,
                $status, $income, $uc_money, $discount_money, $paid_money, $rcpt_money, $rcpno,
                $debit, $max_debt_amount, $totalall, $mobile, $tel_enc, $hospcode, $row_monthtxt
            );
            
            mysqli_stmt_execute($stmt_ins);
            $aff = mysqli_stmt_affected_rows($stmt_ins);
            if ($aff === 1) {
                $batch_inserted++;
            } elseif ($aff === 2 || $aff === 0) {
                $batch_updated++;
            }
        }
        mysqli_stmt_close($stmt_ins);
    }
    
    $next_offset = $offset + $rows_processed;
    $has_more = ($rows_processed === $batch_size && $next_offset < $total_cases);
    $percent = ($total_cases > 0) ? min(100, round(($next_offset / $total_cases) * 100)) : 100;
    
    // บันทึก System Log เมื่อเสร็จสิ้นรอบสุดท้าย
    if (!$has_more && function_exists('system_log')) {
        system_log($conn, 'นำเข้าลูกหนี้ HOSxP', 'PULL_DIRECT', "ดึงข้อมูล $patient_type งวด $monthtxt เสร็จสมบูรณ์ ($next_offset รายการ, เพิ่มใหม่: $batch_inserted, ข้าม: $batch_skipped)");
    }
    
    send_response('success', 'บันทึกข้อมูล Batch สำเร็จ', [
        'patient_type' => $patient_type,
        'monthtxt' => $monthtxt,
        'offset' => $offset,
        'batch_size' => $batch_size,
        'rows_processed' => $rows_processed,
        'batch_inserted' => $batch_inserted,
        'batch_updated' => $batch_updated,
        'batch_skipped' => $batch_skipped,
        'next_offset' => $next_offset,
        'total_cases' => $total_cases,
        'percent' => $percent,
        'has_more' => $has_more
    ]);
}

/**
 * ====================================================================================
 * ACTION: check_delete_safety
 * ตรวจสอบความปลอดภัยทางการเงินและสถิติเคสก่อนดำเนินการลบข้อมูลลูกหนี้
 * ตรวจสอบสถานะใบเสร็จ, ประวัติชำระเงิน, ทะเบียนคุมลูกหนี้, ตารางแยกรับชำระ CR/SSS
 * ====================================================================================
 */
if ($action === 'check_delete_safety') {
    $cond_res = build_debtor_delete_conditions($conn, $_REQUEST);
    if (isset($cond_res['error'])) {
        send_response('error', $cond_res['error']);
    }

    $patient_type = $cond_res['patient_type'];
    $opd_date_cond = $cond_res['opd_date_cond'];
    $ipd_date_cond = $cond_res['ipd_date_cond'];
    $acc_cond_opd = $cond_res['acc_cond_opd'];
    $acc_cond_ipd = $cond_res['acc_cond_ipd'];
    $acc_cond_result = $cond_res['acc_cond_result'];
    $result_month_cond = $cond_res['result_month_cond'];
    $criteria_desc = $cond_res['criteria_desc'];

    $total_opd = 0;
    $billed_opd = 0;
    $unbilled_opd = 0;

    $total_ipd = 0;
    $billed_ipd = 0;
    $unbilled_ipd = 0;

    // 1. ตรวจสอบข้อมูล OPD
    if ($patient_type === 'opd' || $patient_type === 'both') {
        $q_tot_o = mysqli_query($conn, "SELECT COUNT(*) as c FROM imr_tb_debtor_rights_opd o WHERE $opd_date_cond $acc_cond_opd");
        $total_opd = intval(mysqli_fetch_assoc($q_tot_o)['c'] ?? 0);

        if ($total_opd > 0) {
            $q_bill_o = mysqli_query($conn, "SELECT COUNT(DISTINCT o.vn) as c 
                FROM imr_tb_debtor_rights_opd o
                LEFT JOIN imr_tb_payment_history ph ON ph.ref_vn_an = o.vn
                LEFT JOIN imr_tb_debtor_cr_breakdown cr ON cr.vn = o.vn AND cr.visit_type = 'OPD' AND ((cr.bill IS NOT NULL AND TRIM(cr.bill) != '' AND TRIM(cr.bill) != '-') OR (cr.compensated IS NOT NULL AND CAST(cr.compensated AS DECIMAL(15,2)) > 0))
                LEFT JOIN imr_tb_debtor_sss_breakdown sss ON sss.vn = o.vn AND sss.visit_type = 'OPD' AND ((sss.bill IS NOT NULL AND TRIM(sss.bill) != '' AND TRIM(sss.bill) != '-') OR (sss.compensated IS NOT NULL AND CAST(sss.compensated AS DECIMAL(15,2)) > 0))
                WHERE $opd_date_cond $acc_cond_opd
                  AND (
                    (o.bill IS NOT NULL AND TRIM(o.bill) != '' AND TRIM(o.bill) != '-')
                    OR (o.mobile IS NOT NULL AND TRIM(o.mobile) != '' AND TRIM(o.mobile) != '-')
                    OR (o.follow_money IS NOT NULL AND CAST(o.follow_money AS DECIMAL(15,2)) > 0)
                    OR (ph.ref_vn_an IS NOT NULL)
                    OR (cr.vn IS NOT NULL)
                    OR (sss.vn IS NOT NULL)
                  )");
            $billed_opd = intval(mysqli_fetch_assoc($q_bill_o)['c'] ?? 0);
            $unbilled_opd = max(0, $total_opd - $billed_opd);
        }
    }

    // 2. ตรวจสอบข้อมูล IPD
    if ($patient_type === 'ipd' || $patient_type === 'both') {
        $q_tot_i = mysqli_query($conn, "SELECT COUNT(*) as c FROM imr_tb_debtor_rights_ipd i WHERE $ipd_date_cond $acc_cond_ipd");
        $total_ipd = intval(mysqli_fetch_assoc($q_tot_i)['c'] ?? 0);

        if ($total_ipd > 0) {
            $q_bill_i = mysqli_query($conn, "SELECT COUNT(DISTINCT i.an) as c 
                FROM imr_tb_debtor_rights_ipd i
                LEFT JOIN imr_tb_payment_history ph ON ph.ref_vn_an = i.an
                LEFT JOIN imr_tb_debtor_cr_breakdown cr ON cr.vn = i.an AND cr.visit_type = 'IPD' AND ((cr.bill IS NOT NULL AND TRIM(cr.bill) != '' AND TRIM(cr.bill) != '-') OR (cr.compensated IS NOT NULL AND CAST(cr.compensated AS DECIMAL(15,2)) > 0))
                LEFT JOIN imr_tb_debtor_sss_breakdown sss ON sss.vn = i.an AND sss.visit_type = 'IPD' AND ((sss.bill IS NOT NULL AND TRIM(sss.bill) != '' AND TRIM(sss.bill) != '-') OR (sss.compensated IS NOT NULL AND CAST(sss.compensated AS DECIMAL(15,2)) > 0))
                WHERE $ipd_date_cond $acc_cond_ipd
                  AND (
                    (i.bill IS NOT NULL AND TRIM(i.bill) != '' AND TRIM(i.bill) != '-')
                    OR (i.mobile IS NOT NULL AND TRIM(i.mobile) != '' AND TRIM(i.mobile) != '-')
                    OR (i.follow_money IS NOT NULL AND CAST(i.follow_money AS DECIMAL(15,2)) > 0)
                    OR (ph.ref_vn_an IS NOT NULL)
                    OR (cr.vn IS NOT NULL)
                    OR (sss.vn IS NOT NULL)
                  )");
            $billed_ipd = intval(mysqli_fetch_assoc($q_bill_i)['c'] ?? 0);
            $unbilled_ipd = max(0, $total_ipd - $billed_ipd);
        }
    }

    // 3. ตรวจสอบการยืนยันตั้งลูกหนี้ในทะเบียนคุมลูกหนี้ (imr_tb_debtor_result.column10 > 0)
    $total_c10_confirmed = 0.0;
    if (!empty($result_month_cond)) {
        $q_deb_chk = mysqli_query($conn, "SELECT SUM(column10) as total_c10 FROM imr_tb_debtor_result WHERE $result_month_cond $acc_cond_result");
        $total_c10_confirmed = floatval(mysqli_fetch_assoc($q_deb_chk)['total_c10'] ?? 0);
    }

    $total_cases = $total_opd + $total_ipd;
    $total_billed = $billed_opd + $billed_ipd;
    $total_unbilled = $unbilled_opd + $unbilled_ipd;

    send_response('success', 'ตรวจสอบความปลอดภัยทางการเงินเรียบร้อยแล้ว', [
        'total_cases' => $total_cases,
        'total_unbilled' => $total_unbilled,
        'total_billed' => $total_billed,
        'total_c10_confirmed' => $total_c10_confirmed,
        'criteria_desc' => $criteria_desc,
        'patient_type' => $patient_type,
        'opd' => [
            'total' => $total_opd,
            'billed' => $billed_opd,
            'unbilled' => $unbilled_opd
        ],
        'ipd' => [
            'total' => $total_ipd,
            'billed' => $billed_ipd,
            'unbilled' => $unbilled_ipd
        ]
    ]);
}

/**
 * ====================================================================================
 * ACTION: delete_batch
 * ลบข้อมูลลูกหนี้สิทธิตามงวดเดือน/ช่วงวันที่/ผังบัญชี
 * รองรับ delete_scope:
 *   - 'unbilled_only': ลบเฉพาะเคสที่ยังไม่ออกใบเสร็จ (ปลอดภัยสูงสุด)
 *   - 'all_force': ลบทั้งหมดตามที่ผู้ใช้ยืนยัน (รวมเคสที่มีใบเสร็จ)
 * ====================================================================================
 */
if ($action === 'delete_batch') {
    $cond_res = build_debtor_delete_conditions($conn, $_REQUEST);
    if (isset($cond_res['error'])) {
        send_response('error', $cond_res['error']);
    }

    $patient_type = $cond_res['patient_type'];
    $opd_date_cond = $cond_res['opd_date_cond'];
    $ipd_date_cond = $cond_res['ipd_date_cond'];
    $acc_cond_opd = $cond_res['acc_cond_opd'];
    $acc_cond_ipd = $cond_res['acc_cond_ipd'];
    $criteria_desc = $cond_res['criteria_desc'];
    
    // delete_scope: 'unbilled_only' (ค่าเริ่มต้น) หรือ 'all_force'
    $delete_scope = isset($_REQUEST['delete_scope']) ? strtolower(trim($_REQUEST['delete_scope'])) : 'unbilled_only';

    // ดำเนินการลบแบบ Cascade ภายใต้ Database Transaction
    mysqli_begin_transaction($conn);
    try {
        $deleted_opd = 0;
        $deleted_ipd = 0;
        
        // =========================================================
        // กรณีลบเฉพาะเคสที่ยังไม่ออกใบเสร็จ (unbilled_only)
        // =========================================================
        if ($delete_scope === 'unbilled_only') {
            
            if ($patient_type === 'opd' || $patient_type === 'both') {
                // 1. ลบ Breakdown CR เฉพาะที่สัมพันธ์กับเคส OPD ที่ยังไม่ออกใบเสร็จ
                mysqli_query($conn, "DELETE cr FROM imr_tb_debtor_cr_breakdown cr 
                    JOIN imr_tb_debtor_rights_opd o ON o.vn = cr.vn AND cr.visit_type = 'OPD'
                    LEFT JOIN imr_tb_payment_history ph ON ph.ref_vn_an = o.vn
                    WHERE $opd_date_cond $acc_cond_opd
                      AND (o.bill IS NULL OR TRIM(o.bill) = '' OR TRIM(o.bill) = '-')
                      AND (o.mobile IS NULL OR TRIM(o.mobile) = '' OR TRIM(o.mobile) = '-')
                      AND (o.follow_money IS NULL OR CAST(o.follow_money AS DECIMAL(15,2)) = 0)
                      AND (cr.bill IS NULL OR TRIM(cr.bill) = '' OR TRIM(cr.bill) = '-')
                      AND (cr.compensated IS NULL OR CAST(cr.compensated AS DECIMAL(15,2)) = 0)
                      AND ph.ref_vn_an IS NULL");

                // 2. ลบ Breakdown SSS เฉพาะที่สัมพันธ์กับเคส OPD ที่ยังไม่ออกใบเสร็จ
                mysqli_query($conn, "DELETE sss FROM imr_tb_debtor_sss_breakdown sss 
                    JOIN imr_tb_debtor_rights_opd o ON o.vn = sss.vn AND sss.visit_type = 'OPD'
                    LEFT JOIN imr_tb_payment_history ph ON ph.ref_vn_an = o.vn
                    WHERE $opd_date_cond $acc_cond_opd
                      AND (o.bill IS NULL OR TRIM(o.bill) = '' OR TRIM(o.bill) = '-')
                      AND (o.mobile IS NULL OR TRIM(o.mobile) = '' OR TRIM(o.mobile) = '-')
                      AND (o.follow_money IS NULL OR CAST(o.follow_money AS DECIMAL(15,2)) = 0)
                      AND (sss.bill IS NULL OR TRIM(sss.bill) = '' OR TRIM(sss.bill) = '-')
                      AND (sss.compensated IS NULL OR CAST(sss.compensated AS DECIMAL(15,2)) = 0)
                      AND ph.ref_vn_an IS NULL");

                // 3. ลบจากตารางหลัก OPD เฉพาะเคสที่ยังไม่ออกใบเสร็จและไม่มีประวัติการเงิน
                mysqli_query($conn, "DELETE o FROM imr_tb_debtor_rights_opd o
                    LEFT JOIN imr_tb_payment_history ph ON ph.ref_vn_an = o.vn
                    LEFT JOIN imr_tb_debtor_cr_breakdown cr ON cr.vn = o.vn AND cr.visit_type = 'OPD' AND ((cr.bill IS NOT NULL AND TRIM(cr.bill) != '' AND TRIM(cr.bill) != '-') OR (cr.compensated IS NOT NULL AND CAST(cr.compensated AS DECIMAL(15,2)) > 0))
                    LEFT JOIN imr_tb_debtor_sss_breakdown sss ON sss.vn = o.vn AND sss.visit_type = 'OPD' AND ((sss.bill IS NOT NULL AND TRIM(sss.bill) != '' AND TRIM(sss.bill) != '-') OR (sss.compensated IS NOT NULL AND CAST(sss.compensated AS DECIMAL(15,2)) > 0))
                    WHERE $opd_date_cond $acc_cond_opd
                      AND (o.bill IS NULL OR TRIM(o.bill) = '' OR TRIM(o.bill) = '-')
                      AND (o.mobile IS NULL OR TRIM(o.mobile) = '' OR TRIM(o.mobile) = '-')
                      AND (o.follow_money IS NULL OR CAST(o.follow_money AS DECIMAL(15,2)) = 0)
                      AND ph.ref_vn_an IS NULL
                      AND cr.vn IS NULL
                      AND sss.vn IS NULL");
                $deleted_opd = mysqli_affected_rows($conn);
            }

            if ($patient_type === 'ipd' || $patient_type === 'both') {
                // 1. ลบ Breakdown CR เฉพาะที่สัมพันธ์กับเคส IPD ที่ยังไม่ออกใบเสร็จ
                mysqli_query($conn, "DELETE cr FROM imr_tb_debtor_cr_breakdown cr 
                    JOIN imr_tb_debtor_rights_ipd i ON i.an = cr.vn AND cr.visit_type = 'IPD'
                    LEFT JOIN imr_tb_payment_history ph ON ph.ref_vn_an = i.an
                    WHERE $ipd_date_cond $acc_cond_ipd
                      AND (i.bill IS NULL OR TRIM(i.bill) = '' OR TRIM(i.bill) = '-')
                      AND (i.mobile IS NULL OR TRIM(i.mobile) = '' OR TRIM(i.mobile) = '-')
                      AND (i.follow_money IS NULL OR CAST(i.follow_money AS DECIMAL(15,2)) = 0)
                      AND (cr.bill IS NULL OR TRIM(cr.bill) = '' OR TRIM(cr.bill) = '-')
                      AND (cr.compensated IS NULL OR CAST(cr.compensated AS DECIMAL(15,2)) = 0)
                      AND ph.ref_vn_an IS NULL");

                // 2. ลบ Breakdown SSS เฉพาะที่สัมพันธ์กับเคส IPD ที่ยังไม่ออกใบเสร็จ
                mysqli_query($conn, "DELETE sss FROM imr_tb_debtor_sss_breakdown sss 
                    JOIN imr_tb_debtor_rights_ipd i ON i.an = sss.vn AND sss.visit_type = 'IPD'
                    LEFT JOIN imr_tb_payment_history ph ON ph.ref_vn_an = i.an
                    WHERE $ipd_date_cond $acc_cond_ipd
                      AND (i.bill IS NULL OR TRIM(i.bill) = '' OR TRIM(i.bill) = '-')
                      AND (i.mobile IS NULL OR TRIM(i.mobile) = '' OR TRIM(i.mobile) = '-')
                      AND (i.follow_money IS NULL OR CAST(i.follow_money AS DECIMAL(15,2)) = 0)
                      AND (sss.bill IS NULL OR TRIM(sss.bill) = '' OR TRIM(sss.bill) = '-')
                      AND (sss.compensated IS NULL OR CAST(sss.compensated AS DECIMAL(15,2)) = 0)
                      AND ph.ref_vn_an IS NULL");

                // 3. ลบจากตารางหลัก IPD เฉพาะเคสที่ยังไม่ออกใบเสร็จและไม่มีประวัติการเงิน
                mysqli_query($conn, "DELETE i FROM imr_tb_debtor_rights_ipd i
                    LEFT JOIN imr_tb_payment_history ph ON ph.ref_vn_an = i.an
                    LEFT JOIN imr_tb_debtor_cr_breakdown cr ON cr.vn = i.an AND cr.visit_type = 'IPD' AND ((cr.bill IS NOT NULL AND TRIM(cr.bill) != '' AND TRIM(cr.bill) != '-') OR (cr.compensated IS NOT NULL AND CAST(cr.compensated AS DECIMAL(15,2)) > 0))
                    LEFT JOIN imr_tb_debtor_sss_breakdown sss ON sss.vn = i.an AND sss.visit_type = 'IPD' AND ((sss.bill IS NOT NULL AND TRIM(sss.bill) != '' AND TRIM(sss.bill) != '-') OR (sss.compensated IS NOT NULL AND CAST(sss.compensated AS DECIMAL(15,2)) > 0))
                    WHERE $ipd_date_cond $acc_cond_ipd
                      AND (i.bill IS NULL OR TRIM(i.bill) = '' OR TRIM(i.bill) = '-')
                      AND (i.mobile IS NULL OR TRIM(i.mobile) = '' OR TRIM(i.mobile) = '-')
                      AND (i.follow_money IS NULL OR CAST(i.follow_money AS DECIMAL(15,2)) = 0)
                      AND ph.ref_vn_an IS NULL
                      AND cr.vn IS NULL
                      AND sss.vn IS NULL");
                $deleted_ipd = mysqli_affected_rows($conn);
            }

        } else {
            // =========================================================
            // กรณีลบทั้งหมดตามที่ผู้ใช้ยืนยัน (all_force)
            // =========================================================
            if ($patient_type === 'opd' || $patient_type === 'both') {
                mysqli_query($conn, "DELETE cr FROM imr_tb_debtor_cr_breakdown cr JOIN imr_tb_debtor_rights_opd o ON o.vn = cr.vn AND cr.visit_type = 'OPD' WHERE $opd_date_cond $acc_cond_opd");
                mysqli_query($conn, "DELETE sss FROM imr_tb_debtor_sss_breakdown sss JOIN imr_tb_debtor_rights_opd o ON o.vn = sss.vn AND sss.visit_type = 'OPD' WHERE $opd_date_cond $acc_cond_opd");
                mysqli_query($conn, "DELETE o FROM imr_tb_debtor_rights_opd o WHERE $opd_date_cond $acc_cond_opd");
                $deleted_opd = mysqli_affected_rows($conn);
            }
            
            if ($patient_type === 'ipd' || $patient_type === 'both') {
                mysqli_query($conn, "DELETE cr FROM imr_tb_debtor_cr_breakdown cr JOIN imr_tb_debtor_rights_ipd i ON i.an = cr.vn AND cr.visit_type = 'IPD' WHERE $ipd_date_cond $acc_cond_ipd");
                mysqli_query($conn, "DELETE sss FROM imr_tb_debtor_sss_breakdown sss JOIN imr_tb_debtor_rights_ipd i ON i.an = sss.vn AND sss.visit_type = 'IPD' WHERE $ipd_date_cond $acc_cond_ipd");
                mysqli_query($conn, "DELETE i FROM imr_tb_debtor_rights_ipd i WHERE $ipd_date_cond $acc_cond_ipd");
                $deleted_ipd = mysqli_affected_rows($conn);
            }
        }
        
        mysqli_commit($conn);
        
        $total_deleted = $deleted_opd + $deleted_ipd;
        $scope_desc = ($delete_scope === 'unbilled_only') ? 'เฉพาะเคสที่ยังไม่ออกใบเสร็จ' : 'ทั้งหมด (รวมเคสที่มีใบเสร็จ)';
        
        if (function_exists('system_log')) {
            system_log($conn, 'ลบลูกหนี้สิทธิ', 'DELETE_SAFE', "ลบข้อมูลลูกหนี้ ($criteria_desc | $scope_desc) สำเร็จ (OPD: $deleted_opd, IPD: $deleted_ipd, รวม: $total_deleted รายการ)");
        }
        
        send_response('success', "ลบข้อมูลลูกหนี้สำเร็จ ($scope_desc)", [
            'criteria_desc' => $criteria_desc,
            'delete_scope' => $delete_scope,
            'deleted_opd' => $deleted_opd,
            'deleted_ipd' => $deleted_ipd,
            'total_deleted' => $total_deleted
        ]);
    } catch (\Throwable $e) {
        mysqli_rollback($conn);
        send_response('error', 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage());
    }
}

// กรณีระบุ Action ที่ไม่ถูกต้อง
send_response('error', "Action '$action' ไม่ถูกต้อง", [], 400);

