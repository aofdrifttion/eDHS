<?php
require './database_config/config.php';

$mode = $_POST['mode'] ?? 'month';
$whereSQL_opd = "";
$whereSQL_ipd = "";

if ($mode == 'range') {
    $startInput = $_POST['start'] ?? '';
    $endInput = $_POST['end'] ?? '';
    if(empty($startInput) || empty($endInput)){
        echo json_encode(['html' => '<li><div class="dropdown-item">กรุณาระบุวันที่ให้ครบถ้วน</div></li>', 'count' => 0]);
        exit;
    }
    function convertToThaiYearCompare($dateInput) {
        list($d, $m, $y) = explode('/', $dateInput);
        if ((int)$y < 2400) { $y = (int)$y + 543; }
        return $y . $m . $d;
    }
    $start_compare = convertToThaiYearCompare($startInput);
    $end_compare = convertToThaiYearCompare($endInput);

    $whereSQL_opd = " CONCAT( SUBSTRING_INDEX(vstdate, '/', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(vstdate, '/', 2), '/', -1), LPAD(SUBSTRING_INDEX(vstdate, '/', 1), 2, '0') ) BETWEEN '$start_compare' AND '$end_compare' ";
    $whereSQL_ipd = " CONCAT( SUBSTRING_INDEX(dchdate, '/', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(dchdate, '/', 2), '/', -1), LPAD(SUBSTRING_INDEX(dchdate, '/', 1), 2, '0') ) BETWEEN '$start_compare' AND '$end_compare' ";
} else {
    $datepost = $_POST['datepost'] ?? '';
    if($datepost == "0-0000" || empty($datepost)){
        echo json_encode(['html' => '<li><div class="dropdown-item">ไม่พบข้อมูล</div></li>', 'count' => 0]);
        exit;
    }
    $whereSQL_opd = "monthtxt = '$datepost'";
    $whereSQL_ipd = "monthtxt = '$datepost'";
}

$sql_rights = "SELECT accountcode, MAX(accountname) as accountname FROM (
    SELECT accountcode, MAX(accountname) as accountname FROM imr_tb_debtor_rights_opd WHERE $whereSQL_opd GROUP BY accountcode
    UNION
    SELECT accountcode, MAX(accountname) as accountname FROM imr_tb_debtor_rights_ipd WHERE $whereSQL_ipd GROUP BY accountcode
) as t GROUP BY accountcode ORDER BY accountcode";

$res = mysqli_query($conn, $sql_rights);

$rights_map = [];
if ($res && mysqli_num_rows($res) > 0) {
    while($row = mysqli_fetch_assoc($res)) {
        if (!empty($row['accountcode'])) {
            $rights_map[$row['accountcode']] = $row['accountname'];
        }
    }
}

// 💡 [CR & SSS Engine Standard] บรรจุผังลูกจำลอง (.217, .310, .216, .309) เข้าสู่รายการสิทธิอัตโนมัติเมื่อเปิดใช้งานระบบ
require_once './includes/cr_migration_helper.php';
require_once './includes/sss_migration_helper.php';

// 1. ตรวจสอบ CR IPD -> 1102050101.217
$cr_config = get_active_cr_config($conn);
$is_cr_eff_ipd = ($mode === 'range') ? true : is_cr_effective_for_month($cr_config, $datepost);
$auto_split_cr_ipd = is_cr_auto_split_enabled($cr_config, 'IPD');
if ($is_cr_eff_ipd && $auto_split_cr_ipd) {
    if (isset($rights_map['1102050101.202']) && !isset($rights_map['1102050101.217'])) {
        $rights_map['1102050101.217'] = 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)';
    }
}

// 2. ตรวจสอบ CR OPD -> 1102050101.216
$is_cr_eff_opd = ($mode === 'range') ? true : is_cr_effective_for_month($cr_config, $datepost);
$auto_split_cr_opd = is_cr_auto_split_enabled($cr_config, 'OPD');
if ($is_cr_eff_opd && $auto_split_cr_opd) {
    $has_cr_opd_parents = (isset($rights_map['1102050101.201']) || isset($rights_map['1102050101.209']) || isset($rights_map['1102050101.203']));
    if ($has_cr_opd_parents && !isset($rights_map['1102050101.216'])) {
        $rights_map['1102050101.216'] = 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
    }
}

// 3. ตรวจสอบ SSS IPD -> 1102050101.310
$sss_config = get_active_sss_config($conn);
$is_sss_eff_ipd = ($mode === 'range') ? true : is_sss_effective_for_month($sss_config, $datepost);
$auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config, 'IPD');
if ($is_sss_eff_ipd && $auto_split_sss_ipd) {
    $has_sss_ipd_parents = (isset($rights_map['1102050101.302']) || isset($rights_map['1102050101.304']) || isset($rights_map['1102050101.308']));
    if ($has_sss_ipd_parents && !isset($rights_map['1102050101.310'])) {
        $rights_map['1102050101.310'] = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง IP';
    }
}

// 4. ตรวจสอบ SSS OPD -> 1102050101.309
$is_sss_eff_opd = ($mode === 'range') ? true : is_sss_effective_for_month($sss_config, $datepost);
$auto_split_sss_opd = is_sss_auto_split_enabled($sss_config, 'OPD');
if ($is_sss_eff_opd && $auto_split_sss_opd) {
    $has_sss_opd_parents = (isset($rights_map['1102050101.301']) || isset($rights_map['1102050101.303']) || isset($rights_map['1102050101.307']));
    if ($has_sss_opd_parents && !isset($rights_map['1102050101.309'])) {
        $rights_map['1102050101.309'] = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP';
    }
}

// เรียงลำดับรหัสผังบัญชีให้ถูกต้องตามตัวเลข
ksort($rights_map);

$html = '<li>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="all" id="selectAllRights" checked>
                <label class="form-check-label fw-bold" for="selectAllRights">เลือกทั้งหมด</label>
            </div>
        </li>
        <li><hr class="dropdown-divider"></li>';

$count = 0;
if (!empty($rights_map)) {
    foreach ($rights_map as $code => $name) {
        $count++;
        $html .= '<li>
                    <div class="form-check mt-1">
                        <input class="form-check-input right-checkbox" type="checkbox" value="'.$code.'" id="right_'.md5($code).'" checked>
                        <label class="form-check-label" for="right_'.md5($code).'" style="font-size: 15px;">'
                            .$code.' - '.$name.
                        '</label>
                    </div>
                  </li>';
    }
} else {
    $html = '<li><div class="dropdown-item">ไม่มีสิทธิในเดือนนี้</div></li>';
}

echo json_encode(['html' => $html, 'count' => $count]);
?>
