<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - Debtor Trend Analytics API
 * ====================================================================================
 * ให้บริการข้อมูลสถิติและแนวโน้มลูกหนี้ย้อนหลังรายผังบัญชี (OPD / IPD)
 * คำนวณยอดถูกต้องตรงกับหน้ารายงานหลัก 100% (รองรับการตัดโอน CR, SSS และเคสไต)
 * รองรับการวิเคราะห์เจาะลึก 3 มิติ:
 * 1. โรงพยาบาลต้นสังกัดจริง (Hospmain จาก HOSxP)
 * 2. แผนก/คลินิกที่รับบริการ (Department / Clinic)
 * 3. ผู้ป่วยภาระหนี้สูงสุด (Top Debtors) & Drill-down รายเดือน
 */

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database_config/config.php';
require_once __DIR__ . '/database_config/db_helper.php';
require_once __DIR__ . '/includes/cr_migration_helper.php';
require_once __DIR__ . '/includes/sss_migration_helper.php';

// ป้องกัน Direct Access หรือยังไม่ได้ล็อกอิน (ถ้าไม่ได้รันผ่าน CLI)
if (php_sapi_name() !== 'cli' && !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login.']);
    exit;
}

$action = $_REQUEST['action'] ?? 'get_trend';
$type = strtoupper($_REQUEST['type'] ?? 'OPD');
if ($type !== 'IPD') $type = 'OPD';

$table = ($type === 'IPD') ? 'imr_tb_debtor_rights_ipd' : 'imr_tb_debtor_rights_opd';
$id_col = ($type === 'IPD') ? 'an' : 'vn';
$date_col = ($type === 'IPD') ? 'dchdate' : 'vstdate';

// -------------------------------------------------------------
// พจนานุกรมชื่อโรงพยาบาลในจังหวัดร้อยเอ็ด และหน่วยบริการที่พบบ่อย (Fallback Cache)
// -------------------------------------------------------------
$fallback_hospitals = [
    '10702' => 'โรงพยาบาลร้อยเอ็ด',
    '10708' => 'โรงพยาบาลร้อยเอ็ด',
    '11061' => 'โรงพยาบาลเกษตรวิสัย',
    '11062' => 'โรงพยาบาลปทุมรัตต์',
    '11063' => 'โรงพยาบาลจตุรพักตรพิมาน',
    '11064' => 'โรงพยาบาลธวัชบุรี',
    '11065' => 'โรงพยาบาลพนมไพร',
    '11066' => 'โรงพยาบาลโพนทอง',
    '11067' => 'โรงพยาบาลอาจสามารถ',
    '11068' => 'โรงพยาบาลเสลภูมิ',
    '11069' => 'โรงพยาบาลเสลภูมิ',
    '11070' => 'โรงพยาบาลสุวรรณภูมิ',
    '11071' => 'โรงพยาบาลเมืองสรวง',
    '11072' => 'โรงพยาบาลโพนทราย',
    '11073' => 'โรงพยาบาลอาจสามารถ',
    '11074' => 'โรงพยาบาลเมยวดี',
    '11075' => 'โรงพยาบาลศรีสมเด็จ',
    '11076' => 'โรงพยาบาลจังหาร',
    '11077' => 'โรงพยาบาลเชียงขวัญ',
    '11078' => 'โรงพยาบาลหนองพอก',
    '11438' => 'โรงพยาบาลทุ่งเขาหลวง',
    '11484' => 'โรงพยาบาลหนองฮี',
    '27990' => 'โรงพยาบาลหนองฮี',
    '28016' => 'โรงพยาบาลศิลาลาด',
    '11504' => 'รพ.ค่ายสมเด็จพระพุทธยอดฟ้าจุฬาโลกมหาราช',
    '14429' => 'ศบส.เทศบาล 1 วัดเหนือ',
    '23576' => 'ศบส.เทศบาล 2 สระสิม',
    '00037' => 'สำนักงานสาธารณสุขจังหวัดร้อยเอ็ด'
];

/**
 * ฟังก์ชันช่วยแปลงชื่อเดือนภาษาไทยย่อและเต็ม
 */
function getThaiMonthInfo($monthNum, $yearChristian) {
    $thaiMonthsShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $thaiMonthsFull = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

    $m = intval($monthNum);
    $y = intval($yearChristian);
    if ($y < 2400) {
        $yThai = $y + 543;
    } else {
        $yThai = $y;
        $y = $y - 543;
    }
    $yShort = substr((string)$yThai, -2);

    return [
        'month' => $m,
        'year_ad' => $y,
        'year_th' => $yThai,
        'label_short' => ($thaiMonthsShort[$m] ?? $m) . ' ' . $yShort,
        'label_full' => ($thaiMonthsFull[$m] ?? $m) . ' ' . $yThai
    ];
}

/**
 * ดึงชื่อโรงพยาบาลจากรหัสสถานพยาบาล
 */
function resolveHospitalName($conn2, $hospcode, $fallback_hospitals) {
    $hospcode = trim($hospcode);
    if (empty($hospcode) || $hospcode === '-' || $hospcode === '0') {
        return 'ไม่ระบุสถานพยาบาล';
    }
    if (isset($fallback_hospitals[$hospcode])) {
        return $fallback_hospitals[$hospcode];
    }

    if ($conn2 && !$conn2->connect_error) {
        $code_safe = mysqli_real_escape_string($conn2, $hospcode);
        $res = @mysqli_query($conn2, "SELECT name FROM hospcode WHERE hospcode = '$code_safe' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if (!empty($row['name'])) {
                return trim($row['name']);
            }
        }
    }

    return 'รหัส รพ. ' . $hospcode;
}

/**
 * ดึง Hospmain จาก HOSxP (conn2) แบบแบ่ง Chunk ประสิทธิภาพสูง
 */
function fetchHospmainMapFromHosxp($conn2, $visit_ids, $type = 'OPD') {
    $hosp_map = [];
    if (empty($visit_ids) || !$conn2 || $conn2->connect_error) {
        return $hosp_map;
    }

    $chunks = array_chunk($visit_ids, 500);
    foreach ($chunks as $chunk) {
        $in_ids = "'" . implode("','", array_map(function($v) use ($conn2) {
            return mysqli_real_escape_string($conn2, $v);
        }, $chunk)) . "'";

        if ($type === 'IPD') {
            $sql = "SELECT i.an AS id, i.hospmain, h.name
                    FROM ipt i
                    LEFT JOIN hospcode h ON h.hospcode = i.hospmain
                    WHERE i.an IN ($in_ids)";
        } else {
            $sql = "SELECT o.vn AS id, o.hospmain, h.name
                    FROM ovst o
                    LEFT JOIN hospcode h ON h.hospcode = o.hospmain
                    WHERE o.vn IN ($in_ids)";
        }

        $res = @mysqli_query($conn2, $sql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $code = trim($row['hospmain'] ?? '');
                $name = trim($row['name'] ?? '');
                $hosp_map[$row['id']] = [
                    'code' => $code,
                    'name' => $name
                ];
            }
        }
    }

    return $hosp_map;
}

/**
 * ดึงและคำนวณสถิติลูกหนี้รายเดือนที่สอดคล้องกับ datatimestamp-insert2.php 100%
 */
function getMonthlyDebtorStats($conn, $conn2, $accountcode_key, $monthtxt, $type, $cr_config, $sss_config, $my_hospcode) {
    $table = ($type === 'IPD') ? 'imr_tb_debtor_rights_ipd' : 'imr_tb_debtor_rights_opd';
    $id_col = ($type === 'IPD') ? 'an' : 'vn';
    $date_col = ($type === 'IPD') ? 'dchdate' : 'vstdate';

    $is_kidney = (strpos($accountcode_key, '_KIDNEY') !== false);
    $base_account = explode('_', $accountcode_key)[0];

    $kidney_sql = $is_kidney 
        ? "AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%')" 
        : "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%')";

    // Query visits for this month
    $sql = "SELECT $id_col AS id, hn, cid, ptname, $date_col AS vstdate, debit, income, rcpt_money, paid_money, department, clinic
            FROM $table
            WHERE accountcode = '$base_account' AND monthtxt = '$monthtxt' $kidney_sql";
    $res = mysqli_query($conn, $sql);

    $month_visits = [];
    $raw_debit = 0.0;
    $raw_income = 0.0;
    $raw_paid = 0.0;

    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $month_visits[$r['id']] = $r;
            $raw_debit += floatval($r['debit'] ?? 0);
            $raw_income += floatval($r['income'] ?? 0);
            $raw_paid += floatval($r['rcpt_money'] ?? 0) + floatval($r['paid_money'] ?? 0);
        }
    }

    $month_cases = count($month_visits);
    $month_debit = $raw_debit;

    // 1. ตรวจสอบการตัดโอน CR Cross-Account Splitting
    if (!$is_kidney && function_exists('is_cr_effective_for_month') && is_cr_effective_for_month($cr_config, $monthtxt)) {
        $s_cr = get_cr_splitting_summary_for_month($conn, $conn2, $monthtxt, $type, $cr_config, $my_hospcode);
        if ($s_cr && !empty($s_cr['auto_split'])) {
            if (isset($s_cr['transfers_by_account'][$base_account])) {
                $tr = $s_cr['transfers_by_account'][$base_account];
                $month_debit = max(0, $month_debit - floatval($tr['transfer_out']));
                $month_cases = max(0, $month_cases - intval($tr['full_transfer_cases']));
            } elseif ($base_account === '1102050101.216' || $base_account === '1102050101.217') {
                $month_debit = floatval($s_cr['cr_non_kidney_received_total']);
                $month_cases = intval($s_cr['cr_non_kidney_count']);
            }

            // ปรับยอดของแต่ละ Visit
            foreach ($month_visits as $vid => &$vrow) {
                if (isset($s_cr['cr_visits'][$vid])) {
                    $vinfo = $s_cr['cr_visits'][$vid];
                    if (!empty($vinfo['is_full_transfer'])) {
                        unset($month_visits[$vid]); // ตัดออกจากผังแม่โดยสิ้นเชิง
                        continue;
                    }
                    if (isset($vinfo['general_remain_amount'])) {
                        $vrow['debit'] = floatval($vinfo['general_remain_amount']);
                    }
                }
            }
            unset($vrow);
        }
    }

    // 2. ตรวจสอบการตัดโอน SSS Instrument Cross-Account Splitting
    if (!$is_kidney && $sss_config && function_exists('is_sss_effective_for_month') && is_sss_effective_for_month($sss_config, $monthtxt)) {
        $s_sss = get_sss_splitting_summary_for_month($conn, $conn2, $monthtxt, $type, $sss_config, $my_hospcode);
        if ($s_sss && !empty($s_sss['auto_split'])) {
            if (isset($s_sss['transfers_by_account'][$base_account])) {
                $tr_s = $s_sss['transfers_by_account'][$base_account];
                $month_debit = max(0, $month_debit - floatval($tr_s['transfer_out']));
                $month_cases = max(0, $month_cases - intval($tr_s['full_transfer_cases']));
            } elseif ($base_account === '1102050101.309' || $base_account === '1102050101.310') {
                $month_debit = $month_debit + floatval($s_sss['sss_received_total']);
                $month_cases = intval($s_sss['sss_count']);
            }
        }
    }

    return [
        'monthtxt' => $monthtxt,
        'cases' => $month_cases,
        'debit' => $month_debit,
        'income' => $raw_income,
        'paid' => $raw_paid,
        'visits' => $month_visits
    ];
}

// ====================================================================================
// ACTION 1: get_accounts_list
// ====================================================================================
if ($action === 'get_accounts_list') {
    $sql = "SELECT accountcode, MAX(accountname) AS accountname, COUNT(*) AS total_cases, SUM(debit) AS total_debit
            FROM $table
            WHERE accountcode IS NOT NULL AND accountcode != ''
            GROUP BY accountcode
            ORDER BY total_debit DESC, total_cases DESC";

    $res = mysqli_query($conn, $sql);
    $accounts = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $accounts[] = [
                'code' => $r['accountcode'],
                'name' => $r['accountname'],
                'cases' => intval($r['total_cases']),
                'debit' => floatval($r['total_debit']),
                'display' => $r['accountcode'] . ' - ' . $r['accountname']
            ];
        }
    }

    echo json_encode(['status' => 'success', 'type' => $type, 'accounts' => $accounts], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====================================================================================
// ACTION 2: get_patient_list (สำหรับดูรายชื่อคนไข้รายเดือน และ Export Excel)
// ====================================================================================
if ($action === 'get_patient_list') {
    $accountcode = mysqli_real_escape_string($conn, $_REQUEST['accountcode'] ?? '');
    $monthtxt = mysqli_real_escape_string($conn, $_REQUEST['monthtxt'] ?? '');
    $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 500;
    $is_export = (isset($_REQUEST['export']) && $_REQUEST['export'] === '1');

    if (empty($accountcode) || empty($monthtxt)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing accountcode or monthtxt parameter.']);
        exit;
    }

    $cr_config = get_active_cr_config($conn);
    $sss_config = function_exists('get_active_sss_config') ? get_active_sss_config($conn) : null;
    $my_hospcode = get_hosxp_hospital_code($conn2);

    $month_stat = getMonthlyDebtorStats($conn, $conn2, $accountcode, $monthtxt, $type, $cr_config, $sss_config, $my_hospcode);
    $visits = $month_stat['visits'];

    // ดึง Hospmain จาก HOSxP
    $vids = array_keys($visits);
    $hosp_map = fetchHospmainMapFromHosxp($conn2, $vids, $type);

    $patients = [];
    $total_debit = 0.0;
    $total_income = 0.0;

    foreach ($visits as $vid => $r) {
        if (function_exists('decrypt_data')) {
            if (isset($r['cid'])) $r['cid'] = decrypt_data($r['cid']);
            if (isset($r['ptname'])) $r['ptname'] = decrypt_data($r['ptname']);
        }

        $h_code = $hosp_map[$vid]['code'] ?? '';
        $h_name = $hosp_map[$vid]['name'] ?? '';
        if (empty($h_name)) {
            $h_name = resolveHospitalName($conn2, $h_code, $fallback_hospitals);
        }

        $r['hospcode'] = $h_code ?: '-';
        $r['hospname'] = $h_name ?: 'ไม่ระบุสถานพยาบาล';
        $r['debit'] = floatval($r['debit'] ?? 0);
        $r['income'] = floatval($r['income'] ?? 0);
        $total_debit += $r['debit'];
        $total_income += $r['income'];
        $patients[] = $r;
    }

    // จัดเรียงตามภาระหนี้มากไปน้อย
    usort($patients, function($a, $b) {
        return $b['debit'] <=> $a['debit'];
    });

    if (!$is_export && $limit > 0 && count($patients) > $limit) {
        $patients = array_slice($patients, 0, $limit);
    }

    echo json_encode([
        'status' => 'success',
        'accountcode' => $accountcode,
        'monthtxt' => $monthtxt,
        'total_count' => count($patients),
        'total_debit' => $total_debit,
        'total_income' => $total_income,
        'patients' => $patients
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====================================================================================
// ACTION 3: get_trend (ข้อมูลหลักสำหรับกราฟและเจาะลึก 3 มิติ)
// ====================================================================================
$accountcode = mysqli_real_escape_string($conn, $_REQUEST['accountcode'] ?? '1102050101.203');
$range = $_REQUEST['range'] ?? '12m'; // '6m', 'fiscal', '12m'

// 1. ดึงงวดเดือนทั้งหมดที่มีในระบบสำหรับตารางนี้
$sql_all_months = "
    SELECT monthtxt,
           CAST(SUBSTRING_INDEX(monthtxt, '-', -1) AS UNSIGNED) AS y_num,
           CAST(SUBSTRING_INDEX(monthtxt, '-', 1) AS UNSIGNED) AS m_num
    FROM $table
    WHERE monthtxt IS NOT NULL AND monthtxt != '' AND monthtxt LIKE '%-%'
    GROUP BY monthtxt
    ORDER BY y_num ASC, m_num ASC
";
$res_months = mysqli_query($conn, $sql_all_months);
$available_months = [];
if ($res_months) {
    while ($m_row = mysqli_fetch_assoc($res_months)) {
        $available_months[] = $m_row['monthtxt'];
    }
}

if (empty($available_months)) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลงวดเดือนในระบบสำหรับตาราง ' . $table]);
    exit;
}

// 2. กำหนดช่วงเดือนตาม $range
$total_avail = count($available_months);
$target_months = [];

if ($range === '6m') {
    $target_months = array_slice($available_months, max(0, $total_avail - 6));
} elseif ($range === 'fiscal') {
    // หาปีงบประมาณของเดือนล่าสุด
    $latest_month = end($available_months);
    list($lm_m, $lm_y) = explode('-', $latest_month);
    $lm_m = intval($lm_m);
    $lm_y = intval($lm_y);
    $fiscal_target_year = ($lm_m >= 10) ? $lm_y + 1 : $lm_y;

    foreach ($available_months as $m_txt) {
        list($m, $y) = explode('-', $m_txt);
        $m = intval($m);
        $y = intval($y);
        $f_y = ($m >= 10) ? $y + 1 : $y;
        if ($f_y === $fiscal_target_year) {
            $target_months[] = $m_txt;
        }
    }
    if (count($target_months) < 3) {
        $target_months = array_slice($available_months, max(0, $total_avail - 6));
    }
} else {
    // ค่าเริ่มต้น 12 เดือน
    $target_months = array_slice($available_months, max(0, $total_avail - 12));
}

// 3. ชื่อผังบัญชี
$acc_name = 'ไม่ระบุชื่อผังบัญชี';
$base_account = explode('_', $accountcode)[0];
$is_kidney_acc = (strpos($accountcode, '_KIDNEY') !== false);

$sql_accname = "SELECT accountname FROM $table WHERE accountcode = '$base_account' AND accountname IS NOT NULL AND accountname != '' LIMIT 1";
$res_accname = mysqli_query($conn, $sql_accname);
if ($res_accname && $row_acc = mysqli_fetch_assoc($res_accname)) {
    $acc_name = $row_acc['accountname'];
} else {
    $known_map = [
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
        '1102050101.401' => 'ลูกหนี้ค่ารักษา-เบิกจ่ายตรงกรมบัญชีกลาง OP',
        '1102050102.801' => 'ลูกหนี้ค่ารักษา - เบิกจ่ายตรง อปท. OP'
    ];
    if (isset($known_map[$base_account])) {
        $acc_name = $known_map[$base_account];
    }
}
if ($is_kidney_acc) {
    $acc_name = str_replace('ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง', $acc_name);
}

// 4. ประมวลผลข้อมูลรายเดือน (Monthly Trend Calculation)
$cr_config = get_active_cr_config($conn);
$sss_config = function_exists('get_active_sss_config') ? get_active_sss_config($conn) : null;
$my_hospcode = get_hosxp_hospital_code($conn2);

$monthly_trend = [];
$all_period_visits = []; // เก็บ visits ทั้งหมดตลอดช่วงที่เลือก เพื่อนำมาทำมิติที่ 1, 2, 3
$total_cases_all = 0;
$total_debit_all = 0.0;
$total_income_all = 0.0;

$prev_debit = null;
$prev_cases = null;
$peak_month_label = '-';
$peak_month_debit = 0.0;

foreach ($target_months as $m_txt) {
    list($m_num, $y_num) = explode('-', $m_txt);
    $th_info = getThaiMonthInfo($m_num, $y_num);

    $m_stat = getMonthlyDebtorStats($conn, $conn2, $accountcode, $m_txt, $type, $cr_config, $sss_config, $my_hospcode);

    $cases = intval($m_stat['cases']);
    $debit = floatval($m_stat['debit']);
    $income = floatval($m_stat['income']);
    $paid = floatval($m_stat['paid']);
    $avg_debit = ($cases > 0) ? ($debit / $cases) : 0.0;

    // เก็บ visits สำหรับวิเคราะห์ 3 มิติ
    foreach ($m_stat['visits'] as $vid => $vrow) {
        $all_period_visits[$vid] = [
            'id' => $vid,
            'debit' => floatval($vrow['debit']),
            'income' => floatval($vrow['income'] ?? 0),
            'vstdate' => $vrow['vstdate'] ?? '',
            'monthtxt' => $m_txt,
            'department' => $vrow['department'] ?? '',
            'clinic' => $vrow['clinic'] ?? '',
            'ptname' => $vrow['ptname'] ?? '',
            'hn' => $vrow['hn'] ?? '',
            'cid' => $vrow['cid'] ?? ''
        ];
    }

    // MoM %
    $mom_percent = 0.0;
    $mom_cases_percent = 0.0;
    if ($prev_debit !== null && $prev_debit > 0) {
        $mom_percent = round((($debit - $prev_debit) / $prev_debit) * 100, 1);
    }
    if ($prev_cases !== null && $prev_cases > 0) {
        $mom_cases_percent = round((($cases - $prev_cases) / $prev_cases) * 100, 1);
    }

    if ($debit > $peak_month_debit) {
        $peak_month_debit = $debit;
        $peak_month_label = $th_info['label_full'];
    }

    $monthly_trend[] = [
        'monthtxt' => $m_txt,
        'month' => intval($m_num),
        'year' => intval($y_num),
        'label_short' => $th_info['label_short'],
        'label_full' => $th_info['label_full'],
        'cases' => $cases,
        'distinct_patients' => $cases,
        'income' => $income,
        'debit' => $debit,
        'paid' => $paid,
        'avg_debit' => round($avg_debit, 2),
        'mom_percent' => $mom_percent,
        'mom_cases_percent' => $mom_cases_percent
    ];

    $total_cases_all += $cases;
    $total_debit_all += $debit;
    $total_income_all += $income;
    $prev_debit = $debit;
    $prev_cases = $cases;
}

// 5. คำนวณ MoM ของเดือนล่าสุดเทียบเดือนก่อนหน้า
$latest_mom_growth = 0.0;
$trend_direction = 'flat';
$count_m = count($monthly_trend);
if ($count_m >= 2) {
    $last_val = $monthly_trend[$count_m - 1]['debit'];
    $prev_val = $monthly_trend[$count_m - 2]['debit'];
    if ($prev_val > 0) {
        $latest_mom_growth = round((($last_val - $prev_val) / $prev_val) * 100, 1);
        if ($latest_mom_growth > 0) $trend_direction = 'up';
        elseif ($latest_mom_growth < 0) $trend_direction = 'down';
    }
}

// 6. มิติที่ 1: วิเคราะห์เจาะลึกตามโรงพยาบาลต้นสังกัดจริง (Hospmain จาก HOSxP)
$vids_all = array_keys($all_period_visits);
$hosp_map_all = fetchHospmainMapFromHosxp($conn2, $vids_all, $type);

$hosp_stat = [];
foreach ($all_period_visits as $vid => $vitem) {
    $h_code = $hosp_map_all[$vid]['code'] ?? '';
    $h_name = $hosp_map_all[$vid]['name'] ?? '';
    if (empty($h_name)) {
        $h_name = resolveHospitalName($conn2, $h_code, $fallback_hospitals);
    }

    $h_code = $h_code ?: '-';
    $h_name = $h_name ?: 'ไม่ระบุสถานพยาบาล';

    if (!isset($hosp_stat[$h_code])) {
        $hosp_stat[$h_code] = [
            'hospcode' => $h_code,
            'hospname' => $h_name,
            'cases' => 0,
            'debit' => 0.0
        ];
    }

    $hosp_stat[$h_code]['cases']++;
    $hosp_stat[$h_code]['debit'] += $vitem['debit'];
}

// เรียงลำดับจากภาระหนี้สูงสุด
uasort($hosp_stat, function($a, $b) {
    return $b['debit'] <=> $a['debit'];
});

$top_hospitals = [];
foreach ($hosp_stat as $hs) {
    $pct = ($total_debit_all > 0) ? round(($hs['debit'] / $total_debit_all) * 100, 1) : 0.0;
    $top_hospitals[] = [
        'hospcode' => $hs['hospcode'],
        'hospname' => $hs['hospname'],
        'cases' => $hs['cases'],
        'debit' => round($hs['debit'], 2),
        'avg_debit' => ($hs['cases'] > 0) ? round($hs['debit'] / $hs['cases'], 2) : 0.0,
        'percent' => $pct
    ];
    if (count($top_hospitals) >= 10) break;
}

// 7. มิติที่ 2: วิเคราะห์เจาะลึกตามแผนก / คลินิก (Department / Clinic)
$clinic_stat = [];
foreach ($all_period_visits as $vid => $vitem) {
    $c_name = trim($vitem['clinic'] ?: ($vitem['department'] ?: 'ไม่ระบุแผนก'));
    if (!isset($clinic_stat[$c_name])) {
        $clinic_stat[$c_name] = [
            'clinic_name' => $c_name,
            'cases' => 0,
            'debit' => 0.0
        ];
    }
    $clinic_stat[$c_name]['cases']++;
    $clinic_stat[$c_name]['debit'] += $vitem['debit'];
}

uasort($clinic_stat, function($a, $b) {
    return $b['debit'] <=> $a['debit'];
});

$top_clinics = [];
foreach ($clinic_stat as $cs) {
    $pct = ($total_debit_all > 0) ? round(($cs['debit'] / $total_debit_all) * 100, 1) : 0.0;
    $top_clinics[] = [
        'clinic_name' => $cs['clinic_name'],
        'cases' => $cs['cases'],
        'debit' => round($cs['debit'], 2),
        'percent' => $pct
    ];
    if (count($top_clinics) >= 10) break;
}

// 8. มิติที่ 3: ผู้ป่วยที่มีภาระหนี้สูงสุด 10 อันดับแรก (Top 10 Debtors)
uasort($all_period_visits, function($a, $b) {
    return $b['debit'] <=> $a['debit'];
});

$top_debtors = [];
$top_visits_slice = array_slice($all_period_visits, 0, 10, true);
foreach ($top_visits_slice as $vid => $r) {
    $actual_vid = !empty($r['id']) ? $r['id'] : $vid;
    $pt_name = $r['ptname'];
    $cid = $r['cid'];
    if (function_exists('decrypt_data')) {
        $pt_name = decrypt_data($pt_name);
        $cid = decrypt_data($cid);
    }
    $h_info = $hosp_map_all[$actual_vid] ?? ['code' => '-', 'name' => 'ไม่ระบุสถานพยาบาล'];

    $top_debtors[] = [
        'id' => $actual_vid,
        'hn' => $r['hn'] ?? '-',
        'cid' => $cid ?? '-',
        'ptname' => $pt_name ?? 'ไม่ระบุชื่อ',
        'vstdate' => $r['vstdate'] ?? '-',
        'monthtxt' => $r['monthtxt'] ?? '-',
        'hospname' => $h_info['name'] ?? 'ไม่ระบุสถานพยาบาล',
        'clinic' => $r['clinic'] ?: ($r['department'] ?: '-'),
        'income' => round($r['income'] ?? 0, 2),
        'debit' => round($r['debit'], 2)
    ];
}

// 9. ประกอบผลลัพธ์ JSON ตอบกลับ
$response = [
    'status' => 'success',
    'type' => $type,
    'accountcode' => $accountcode,
    'accountname' => $acc_name,
    'range' => $range,
    'meta' => [
        'accountcode' => $accountcode,
        'accountname' => $acc_name,
        'type' => $type,
        'range' => $range
    ],
    'kpi' => [
        'total_debit' => round($total_debit_all, 2),
        'total_cases' => $total_cases_all,
        'avg_debit_per_case' => ($total_cases_all > 0) ? round($total_debit_all / $total_cases_all, 2) : 0.0,
        'avg_per_case' => ($total_cases_all > 0) ? round($total_debit_all / $total_cases_all, 2) : 0.0,
        'peak_month_label' => $peak_month_label,
        'peak_month_debit' => round($peak_month_debit, 2),
        'latest_mom_growth' => $latest_mom_growth,
        'trend_direction' => $trend_direction
    ],
    'monthly_trend' => $monthly_trend,
    'top_hospitals' => $top_hospitals,
    'top_clinics' => $top_clinics,
    'top_debtors' => $top_debtors,
    'breakdowns' => [
        'hospitals' => $top_hospitals,
        'clinics' => $top_clinics,
        'top_debtors' => $top_debtors
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
