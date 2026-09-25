<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - Debtor Detail Aging Modal Service
 * ====================================================================================
 * ให้บริการดึงข้อมูลรายบุคคลสำหรับหน้าทะเบียนคุมอายุลูกหนี้ (Modal Drill-down)
 * รองรับ:
 * 1. ผังบัญชีแม่และผังบัญชีลูก (CR & SSS Cross-Account Splitting)
 * 2. การแนบใบเสร็จแบบครบวงจร (Multi-Source Receipt Resolution: Breakdown, Rights, Mobile, Payment History)
 * 3. การตัดลูกหนี้รายตัว / แบ่งจ่าย (Partial Payment Life Cycle - Rule 11)
 * 4. การชดเชย Statement (STM Reconciliation & Settle Status)
 * ====================================================================================
 */

header('Content-Type: text/html; charset=utf-8');
global $conn, $conn2, $configData;
require_once __DIR__ . '/database_config/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $conn = $GLOBALS['conn'];
    }
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (file_exists(__DIR__ . "/database_config/config.json")) {
        $cfg = json_decode(file_get_contents(__DIR__ . "/database_config/config.json"), true);
        if (isset($cfg["db1"])) {
            $conn = new mysqli($cfg["db1"]["servername"], $cfg["db1"]["username"], $cfg["db1"]["password"], $cfg["db1"]["dbname"]);
            $conn->set_charset("utf8");
        }
    }
}
require_once __DIR__ . '/database_config/db_helper.php';
require_once __DIR__ . '/includes/cr_migration_helper.php';
require_once __DIR__ . '/includes/sss_migration_helper.php';

if (!function_exists('resolve_debtor_receipt')) {
    function resolve_debtor_receipt($row, $conn, $bd_bill = null, $bd_billdate = null) {
        $r_bill = '';
        $r_billdate = '';

        // 1. จาก Breakdown (CR / SSS)
        if (!empty($bd_bill) && $bd_bill !== '-' && $bd_bill !== '0000/0000') {
            $r_bill = $bd_bill;
        }
        if (!empty($bd_billdate) && $bd_billdate !== '-' && $bd_billdate !== '0000-00-00') {
            $r_billdate = $bd_billdate;
        }

        // 2. จากตารางหลัก bill และ billdate
        if (empty($r_bill) && !empty($row['bill']) && $row['bill'] !== '-' && $row['bill'] !== '0000/0000') {
            $r_bill = $row['bill'];
        }
        if (empty($r_billdate) && !empty($row['billdate']) && $row['billdate'] !== '-' && $row['billdate'] !== '0000-00-00') {
            $r_billdate = $row['billdate'];
        }

        // 3. จาก mobile string (กรณีจัดเก็บเลขที่-วันที่ เช่น 0049/0031-16/09/2569)
        $mobile = trim($row['mobile'] ?? '');
        if ($mobile !== '' && $mobile !== '-') {
            if (strpos($mobile, '-') !== false && strpos($mobile, '/') !== false) {
                $m_parts = explode('-', $mobile, 2);
                if (empty($r_bill) && !empty($m_parts[0]) && $m_parts[0] !== '0000/0000') {
                    $r_bill = trim($m_parts[0]);
                }
                if (empty($r_billdate) && !empty($m_parts[1])) {
                    $r_billdate = trim($m_parts[1]);
                }
            } elseif (empty($r_bill) && preg_match('/^\d{4}\/\d{4}$/', $mobile)) {
                $r_bill = $mobile;
            }
        }

        // 4. จากประวัติการชำระเงินใน imr_tb_payment_history (Rule 11)
        $ref_id = $row['vn'] ?? ($row['an'] ?? '');
        $pt_type = $row['type'] ?? 'OPD';
        if ((empty($r_bill) || empty($r_billdate)) && !empty($ref_id)) {
            static $cached_ph = [];
            $cache_key = "{$ref_id}_{$pt_type}";
            if (!array_key_exists($cache_key, $cached_ph)) {
                $stmt_ph = $conn->prepare("
                    SELECT bill_no, bill_date 
                    FROM imr_tb_payment_history 
                    WHERE ref_vn_an = ? AND patient_type = ? AND bill_no IS NOT NULL AND bill_no != '' AND bill_no != '-'
                    ORDER BY bill_date DESC, id DESC LIMIT 1
                ");
                if ($stmt_ph) {
                    $stmt_ph->bind_param('ss', $ref_id, $pt_type);
                    $stmt_ph->execute();
                    $res_ph = $stmt_ph->get_result();
                    $cached_ph[$cache_key] = $res_ph ? $res_ph->fetch_assoc() : null;
                    $stmt_ph->close();
                } else {
                    $cached_ph[$cache_key] = null;
                }
            }
            $ph = $cached_ph[$cache_key];
            if ($ph) {
                if (empty($r_bill) && !empty($ph['bill_no'])) {
                    $r_bill = $ph['bill_no'];
                }
                if (empty($r_billdate) && !empty($ph['bill_date'])) {
                    $r_billdate = date('d/m/', strtotime($ph['bill_date'])) . ((int)date('Y', strtotime($ph['bill_date'])) + 543);
                }
            }
        }

        return [$r_bill, $r_billdate];
    }
}

if (!isset($_POST['accountcode']) || !isset($_POST['month'])) {
    echo '<div class="alert alert-danger text-center my-3"><i class="bx bx-error-circle me-1"></i> ข้อมูลพารามิเตอร์ไม่ครบถ้วน กรุณาลองใหม่อีกครั้ง</div>';
    exit;
}

$accCode = mysqli_real_escape_string($conn, trim($_POST['accountcode']));
$month   = mysqli_real_escape_string($conn, trim($_POST['month']));
$range   = isset($_POST['range']) ? mysqli_real_escape_string($conn, trim($_POST['range'])) : 'total';

if (strpos($month, '-') === false) {
    echo '<div class="alert alert-danger text-center my-3"><i class="bx bx-error-circle me-1"></i> รูปแบบเดือนไม่ถูกต้อง กรุณาระบุ เช่น 8-2026</div>';
    exit;
}

list($month_num, $year_num) = explode('-', $month);
$target_ym = sprintf('%s-%02d', $year_num, $month_num);
$last_day_date = date('Y-m-t', mktime(0, 0, 0, $month_num, 1, $year_num));

// คำนวณช่วงอายุหนี้
$calc_age_func = function($date_str, $last_day) {
    if (empty($date_str) || strlen($date_str) < 10) return 0;
    $d = (int)substr($date_str, 0, 2);
    $m = (int)substr($date_str, 3, 2);
    $y = (int)substr($date_str, 6, 4);
    if ($y > 2400) $y -= 543;
    $srv_ym = sprintf('%04d-%02d-%02d', $y, $m, $d);
    try {
        $d1 = new DateTime($srv_ym);
        $d2 = new DateTime($last_day);
        $diff = $d1->diff($d2);
        return ($diff->y * 12) + $diff->m;
    } catch (Exception $e) {
        return 0;
    }
};

// จำแนกผังแม่และผังลูก
$is_cr_child_opd  = ($accCode === '1102050101.216');
$is_cr_child_ipd  = ($accCode === '1102050101.217');
$is_sss_child_opd = ($accCode === '1102050101.309');
$is_sss_child_ipd = ($accCode === '1102050101.310');
$is_child_acc     = ($is_cr_child_opd || $is_cr_child_ipd || $is_sss_child_opd || $is_sss_child_ipd);

$is_cr_parent_opd  = in_array($accCode, ['1102050101.201', '1102050101.203', '1102050101.209']);
$is_cr_parent_ipd  = in_array($accCode, ['1102050101.202']);
$is_sss_parent_opd = in_array($accCode, ['1102050101.301', '1102050101.303', '1102050101.307']);
$is_sss_parent_ipd = in_array($accCode, ['1102050101.302', '1102050101.304', '1102050101.308']);
$is_parent_acc     = ($is_cr_parent_opd || $is_cr_parent_ipd || $is_sss_parent_opd || $is_sss_parent_ipd);

$cr_config = get_active_cr_config($conn);
$sss_config = get_active_sss_config($conn);
$my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';

if (!function_exists('getReconParentRecoveries')) {
    function getReconParentRecoveries($conn, $cr_opd, $cr_ipd, $sss_opd, $sss_ipd, $target_ym, $acc_filter = null) {
        $recoveries = [];

        $check_sources = [];
        if ($cr_opd && !empty($cr_opd['cr_visits'])) {
            $check_sources[] = ['type' => 'OPD', 'table' => 'imr_tb_debtor_rights_opd', 'pk' => 'vn', 'visits' => $cr_opd['cr_visits']];
        }
        if ($cr_ipd && !empty($cr_ipd['cr_visits'])) {
            $check_sources[] = ['type' => 'IPD', 'table' => 'imr_tb_debtor_rights_ipd', 'pk' => 'an', 'visits' => $cr_ipd['cr_visits']];
        }
        if ($sss_opd && !empty($sss_opd['sss_visits'])) {
            $check_sources[] = ['type' => 'OPD', 'table' => 'imr_tb_debtor_rights_opd', 'pk' => 'vn', 'visits' => $sss_opd['sss_visits']];
        }
        if ($sss_ipd && !empty($sss_ipd['sss_visits'])) {
            $check_sources[] = ['type' => 'IPD', 'table' => 'imr_tb_debtor_rights_ipd', 'pk' => 'an', 'visits' => $sss_ipd['sss_visits']];
        }

        foreach ($check_sources as $src) {
            $cand_ids = [];
            foreach ($src['visits'] as $v_id => $vinfo) {
                $p_acc = $vinfo['origin_accountcode'] ?? '';
                if (empty($p_acc)) continue;
                if ($acc_filter && $p_acc !== $acc_filter) continue;
                $remain = (float)($vinfo['general_remain_amount'] ?? 0);
                if ($remain > 0.01) {
                    $cand_ids[$v_id] = [
                        'remain' => $remain,
                        'p_acc'  => $p_acc
                    ];
                }
            }

            if (!empty($cand_ids)) {
                $pk = $src['pk'];
                $tbl = $src['table'];
                $chunks = array_chunk(array_keys($cand_ids), 500);
                foreach ($chunks as $chunk) {
                    $ids_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $chunk)) . "'";
                    $q = mysqli_query($conn, "
                        SELECT $pk AS id, bill, billdate, mobile, accountcode
                        FROM $tbl
                        WHERE $pk IN ($ids_str)
                          AND (bill IS NULL OR bill != '0000/0000')
                    ");
                    if ($q) {
                        while ($r = mysqli_fetch_assoc($q)) {
                            $id = $r['id'];
                            $b_date = $r['billdate'] ?? '';
                            $p_acc = $cand_ids[$id]['p_acc'];

                            $is_filtered_out = false;
                            if (!empty($b_date) && $b_date != '-' && strlen($b_date) >= 10) {
                                $bym = sprintf('%04d-%02d', (int)substr($b_date, 6, 4) - 543, (int)substr($b_date, 3, 2));
                                if ($bym <= $target_ym) {
                                    $is_filtered_out = true;
                                }
                            }
                            if ($is_filtered_out) {
                                if (!isset($recoveries[$p_acc])) {
                                    $recoveries[$p_acc] = ['amount' => 0.0, 'count' => 0, 'items' => []];
                                }
                                $recoveries[$p_acc]['amount'] += $cand_ids[$id]['remain'];
                                $recoveries[$p_acc]['count']++;
                                $recoveries[$p_acc]['items'][$id] = $cand_ids[$id]['remain'];
                            }
                        }
                    }
                }
            }
        }
        return $recoveries;
    }
}

// โหลดข้อมูลการตัดโอน CR / SSS
$cr_opd_visits_all  = [];
$cr_ipd_visits_all  = [];
$sss_opd_visits_all = [];
$sss_ipd_visits_all = [];

$cr_opd  = null;
$cr_ipd  = null;
$sss_opd = null;
$sss_ipd = null;

if (is_cr_effective_for_month($cr_config, $month) && is_cr_auto_split_enabled($cr_config, 'OPD')) {
    $cr_opd = get_cr_splitting_summary_for_month($conn, $conn2 ?? null, $month, 'OPD', $cr_config, $my_hospcode);
    if ($cr_opd && !empty($cr_opd['cr_visits'])) $cr_opd_visits_all = $cr_opd['cr_visits'];
}
if (is_cr_effective_for_month($cr_config, $month) && is_cr_auto_split_enabled($cr_config, 'IPD')) {
    $cr_ipd = get_cr_splitting_summary_for_month($conn, $conn2 ?? null, $month, 'IPD', $cr_config, $my_hospcode);
    if ($cr_ipd && !empty($cr_ipd['cr_visits'])) $cr_ipd_visits_all = $cr_ipd['cr_visits'];
}
if (is_sss_effective_for_month($sss_config, $month) && is_sss_auto_split_enabled($sss_config, 'OPD')) {
    $sss_opd = get_sss_splitting_summary_for_month($conn, $conn2 ?? null, $month, 'OPD', $sss_config, $my_hospcode);
    if ($sss_opd && !empty($sss_opd['sss_visits'])) $sss_opd_visits_all = $sss_opd['sss_visits'];
}
if (is_sss_effective_for_month($sss_config, $month) && is_sss_auto_split_enabled($sss_config, 'IPD')) {
    $sss_ipd = get_sss_splitting_summary_for_month($conn, $conn2 ?? null, $month, 'IPD', $sss_config, $my_hospcode);
    if ($sss_ipd && !empty($sss_ipd['sss_visits'])) $sss_ipd_visits_all = $sss_ipd['sss_visits'];
}

$parent_recoveries = getReconParentRecoveries($conn, $cr_opd, $cr_ipd, $sss_opd, $sss_ipd, $target_ym);

// เงื่อนไขการตัดรับชำระ
$receiptWhere = "
    CASE
        WHEN billdate IS NOT NULL AND billdate != ''
         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
        THEN CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) > '$target_ym'

        WHEN mobile IS NOT NULL AND mobile != ''
         AND mobile != '-' AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
        THEN CONCAT(
               (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
               '-',
               SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
             ) > '$target_ym'

        ELSE TRUE
    END
";

// ดึงข้อมูลหลักจากฐานข้อมูล
$sql = "
SELECT * FROM (
    /* OPD */
    SELECT vn, vstdate, hn, cid, ptname, mobile, billdate, bill, accountcode,
           IFNULL(debit, 0) as raw_debit,
           CASE
               WHEN billdate IS NOT NULL AND billdate != '' 
                AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
               THEN IFNULL(follow_money, 0)
               WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
                AND CONCAT((SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),'-',SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)) <= '$target_ym'
               THEN IFNULL(follow_money, 0)
               ELSE 0
           END AS paid_follow,
           IFNULL((
               SELECT SUM(ph.pay_amount)
               FROM imr_tb_payment_history ph
               WHERE ph.ref_vn_an = vn AND ph.patient_type = 'OPD'
                 AND ph.bill_date IS NOT NULL AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
           ), 0) AS paid_history,
           pttypename, 'OPD' as type
    FROM imr_tb_debtor_rights_opd
    WHERE accountcode = '$accCode' 
      AND IFNULL(debit, 0) > 0
      AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$target_ym'

    UNION ALL

    /* IPD */
    SELECT an AS vn, dchdate AS vstdate, hn, cid, ptname, mobile, billdate, bill, accountcode,
           IFNULL(debit, 0) as raw_debit,
           CASE
               WHEN billdate IS NOT NULL AND billdate != '' 
                AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
               THEN IFNULL(follow_money, 0)
               WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
                AND CONCAT((SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),'-',SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)) <= '$target_ym'
               THEN IFNULL(follow_money, 0)
               ELSE 0
           END AS paid_follow,
           IFNULL((
               SELECT SUM(ph.pay_amount)
               FROM imr_tb_payment_history ph
               WHERE ph.ref_vn_an = an AND ph.patient_type = 'IPD'
                 AND ph.bill_date IS NOT NULL AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
           ), 0) AS paid_history,
           pttypename, 'IPD' as type
    FROM imr_tb_debtor_rights_ipd
    WHERE accountcode = '$accCode' 
      AND IFNULL(debit, 0) > 0
      AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$target_ym'
) AS all_debtors
WHERE (raw_debit - paid_follow - paid_history) > 0 AND ($receiptWhere)
";

$res = $conn->query($sql);
$final_rows = [];
$raw_seen = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $ref_id = $row['vn'];
        $raw_seen[$ref_id] = true;
        $net_debit = (float)$row['raw_debit'] - (float)$row['paid_follow'] - (float)$row['paid_history'];

        if ($is_parent_acc) {
            $vinfo = null;
            if ($is_cr_parent_opd && isset($cr_opd_visits_all[$ref_id])) {
                $vinfo = $cr_opd_visits_all[$ref_id];
            } elseif ($is_cr_parent_ipd && isset($cr_ipd_visits_all[$ref_id])) {
                $vinfo = $cr_ipd_visits_all[$ref_id];
            } elseif ($is_sss_parent_opd && isset($sss_opd_visits_all[$ref_id])) {
                $vinfo = $sss_opd_visits_all[$ref_id];
            } elseif ($is_sss_parent_ipd && isset($sss_ipd_visits_all[$ref_id])) {
                $vinfo = $sss_ipd_visits_all[$ref_id];
            }

            if ($vinfo !== null) {
                // เคสโอนออก 100% หรือไม่มีหนี้ทั่วไปเหลือ -> ตัดออกจากผังแม่
                if (!empty($vinfo['is_full_transfer']) || (float)($vinfo['general_remain_amount'] ?? 0) <= 0.01) {
                    continue;
                }
                // เคสโอนบางส่วน -> คงเหลือหนี้ทั่วไปในผังแม่
                $net_debit = (float)$vinfo['general_remain_amount'];
            }
        }

        $age = $calc_age_func($row['vstdate'], $last_day_date);
        $matches_range = ($range === 'total') ||
                         ($range === 'lt_3' && $age < 3) ||
                         ($range === '3_12' && $age >= 3 && $age <= 12) ||
                         ($range === 'gt_12' && $age > 12);

        if ($matches_range && $net_debit > 0.01) {
            $row['debit'] = $net_debit;
            $row['age'] = $age;
            list($r_bill, $r_billdate) = resolve_debtor_receipt($row, $conn);
            $row['resolved_bill'] = $r_bill;
            $row['resolved_billdate'] = $r_billdate;
            $final_rows[] = $row;
        }
    }
}

// ประมวลผลเคสที่โอนเข้าผังลูก (Child Accounts)
if ($is_child_acc) {
    $visits_all = [];
    $visit_type = 'OPD';
    $scheme = 'CR';
    if ($is_cr_child_opd)  { $visits_all = $cr_opd_visits_all; $visit_type = 'OPD'; $scheme = 'CR'; }
    if ($is_cr_child_ipd)  { $visits_all = $cr_ipd_visits_all; $visit_type = 'IPD'; $scheme = 'CR'; }
    if ($is_sss_child_opd) { $visits_all = $sss_opd_visits_all; $visit_type = 'OPD'; $scheme = 'SSS'; }
    if ($is_sss_child_ipd) { $visits_all = $sss_ipd_visits_all; $visit_type = 'IPD'; $scheme = 'SSS'; }

    $transferred_ids = [];
    foreach ($visits_all as $v_id => $vinfo) {
        if (!empty($vinfo['is_kidney'])) continue;
        if (isset($raw_seen[$v_id])) continue;
        if (($vinfo['origin_accountcode'] ?? '') === $accCode) continue;
        $transferred_ids[] = $v_id;
    }

    if (!empty($transferred_ids)) {
        $pt_table = ($visit_type === 'OPD') ? 'imr_tb_debtor_rights_opd' : 'imr_tb_debtor_rights_ipd';
        $bd_table = ($scheme === 'CR') ? 'imr_tb_debtor_cr_breakdown' : 'imr_tb_debtor_sss_breakdown';
        $type_field = ($scheme === 'CR') ? 'cr_type' : 'sss_type';
        $pk = ($visit_type === 'OPD') ? 'vn' : 'an';
        $date_col = ($visit_type === 'OPD') ? 'vstdate' : 'dchdate';

        $chunks = array_chunk($transferred_ids, 500);
        foreach ($chunks as $chunk) {
            $ids_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $chunk)) . "'";
            $sql_in = "
                SELECT o.$pk AS vn, o.$date_col AS vstdate, o.hn, o.cid, o.ptname, o.mobile, o.billdate, o.bill,
                       b.billdate as bd_billdate, b.compensated as bd_comp, b.bill as bd_bill, b.settle_status,
                       b.bd_type
                FROM $pt_table o
                LEFT JOIN (
                    SELECT vn, MAX(billdate) as billdate, SUM(compensated) as compensated, MAX(bill) as bill, 
                           MAX(settle_status) as settle_status, GROUP_CONCAT(DISTINCT $type_field SEPARATOR ', ') as bd_type
                    FROM $bd_table
                    WHERE visit_type = '$visit_type'
                    GROUP BY vn
                ) b ON b.vn = o.$pk
                WHERE o.$pk IN ($ids_str)
            ";
            $res_in = $conn->query($sql_in);
            if ($res_in) {
                while ($r_in = $res_in->fetch_assoc()) {
                    $v_id = $r_in['vn'];
                    $vinfo = $visits_all[$v_id];
                    $amount_key = ($scheme === 'CR') ? 'cr_amount' : 'sss_amount';
                    $base_debit = (float)(($vinfo['transfer_out'] ?? 0) > 0 ? $vinfo['transfer_out'] : ($vinfo[$amount_key] ?? 0));

                    $bill_d = !empty($r_in['bd_billdate']) ? $r_in['bd_billdate'] : '';
                    $comp   = !empty($r_in['bd_comp']) ? (float)$r_in['bd_comp'] : 0.0;
                    $status = $r_in['settle_status'] ?? '';

                    $is_paid = false;
                    $bym = '';
                    if (!empty($bill_d) && $bill_d != '-' && strlen($bill_d) >= 10) {
                        $by = (int)substr($bill_d, 6, 4) - 543;
                        $bm = (int)substr($bill_d, 3, 2);
                        $bym = sprintf('%04d-%02d', $by, $bm);
                        if ($bym <= $target_ym && ($status === 'SETTLED' || ($comp > 0 && ($base_debit - $comp) <= 0.01))) {
                            $is_paid = true;
                        }
                    }

                    $net_debit = max(0, $base_debit - ($comp > 0 && !empty($bym) && $bym <= $target_ym ? $comp : 0));
                    if ($net_debit > 0.01 && !$is_paid) {
                        $age = $calc_age_func($r_in['vstdate'], $last_day_date);
                        $matches_range = ($range === 'total') ||
                                         ($range === 'lt_3' && $age < 3) ||
                                         ($range === '3_12' && $age >= 3 && $age <= 12) ||
                                         ($range === 'gt_12' && $age > 12);

                        if ($matches_range) {
                            $types_label = !empty($r_in['bd_type']) ? $r_in['bd_type'] : (!empty($vinfo['cr_types']) ? implode(', ', $vinfo['cr_types']) : (!empty($vinfo['sss_types']) ? implode(', ', $vinfo['sss_types']) : ($scheme . ' ' . $visit_type)));
                            $r_in['debit'] = $net_debit;
                            $r_in['raw_debit'] = $base_debit;
                            $r_in['paid_follow'] = $comp;
                            $r_in['paid_history'] = 0;
                            $r_in['accountcode'] = $accCode;
                            $r_in['pttypename'] = $types_label;
                            $r_in['type'] = $visit_type;
                            $r_in['age'] = $age;

                            list($r_bill, $r_billdate) = resolve_debtor_receipt($r_in, $conn, $r_in['bd_bill'] ?? null, $r_in['bd_billdate'] ?? null);
                            $r_in['resolved_bill'] = $r_bill;
                            $r_in['resolved_billdate'] = $r_billdate;
                            $final_rows[] = $r_in;
                        }
                    }
                }
            }
        }
    }
}

// หากเป็นผังแม่ ให้ตรวจสอบยอดคงเหลือสุทธิ (Col 12) และยอดโอนออกรวม
if ($is_parent_acc) {
    // 1. ตรวจสอบ Column 12 ใน imr_tb_debtor_result หากยอดคงเหลือเป็น 0 แสดงว่าผังแม่ได้รับการชำระ/โอนครบแล้ว
    $q_c12 = $conn->query("SELECT column12 FROM imr_tb_debtor_result WHERE code = '$accCode' AND month = '$month'");
    if ($q_c12 && $r_c12 = $q_c12->fetch_assoc()) {
        $c12_num = (float)str_replace(',', '', $r_c12['column12'] ?? 0);
        if ($c12_num <= 0.01) {
            $final_rows = [];
        }
    }

    if (!empty($final_rows)) {
        $mother_transfer_out = 0.0;
        if ($is_cr_parent_opd && isset($cr_opd['transfers_by_account'][$accCode])) {
            $mother_transfer_out += (float)$cr_opd['transfers_by_account'][$accCode]['transfer_out'];
        }
        if ($is_cr_parent_ipd && isset($cr_ipd['transfers_by_account'][$accCode])) {
            $mother_transfer_out += (float)$cr_ipd['transfers_by_account'][$accCode]['transfer_out'];
        }
        if ($is_sss_parent_opd && isset($sss_opd['transfers_by_account'][$accCode])) {
            $mother_transfer_out += (float)$sss_opd['transfers_by_account'][$accCode]['transfer_out'];
        }
        if ($is_sss_parent_ipd && isset($sss_ipd['transfers_by_account'][$accCode])) {
            $mother_transfer_out += (float)$sss_ipd['transfers_by_account'][$accCode]['transfer_out'];
        }

        $mother_raw_sum = 0.0;
        foreach ($final_rows as $fr) {
            $mother_raw_sum += (float)$fr['debit'];
        }

        if ($mother_transfer_out >= $mother_raw_sum && $mother_raw_sum > 0) {
            $final_rows = [];
        }
    }
}

// สรุปยอดรวมสำหรับ KPI Cards
$tot_cases = count($final_rows);
$tot_raw_debit = 0.0;
$tot_paid = 0.0;
$tot_net_debit = 0.0;

foreach ($final_rows as $r) {
    $tot_raw_debit += (float)($r['raw_debit'] ?? $r['debit']);
    $tot_paid += ((float)($r['paid_follow'] ?? 0) + (float)($r['paid_history'] ?? 0));
    $tot_net_debit += (float)$r['debit'];
}
?>

<!-- 🌟 ส่วนแสดงผล KPI Summary Cards และเครื่องมือค้นหา -->
<div class="modal-kpi-bar mb-3">
    <div class="row g-2">
        <div class="col-6 col-md-3">
            <div class="card shadow-none border bg-light-subtle p-2 text-center h-100">
                <div class="text-muted small fw-semibold">จำนวนลูกหนี้</div>
                <div class="fs-5 fw-bold text-primary mt-1"><?= number_format($tot_cases) ?> <span class="fs-6 fw-normal text-muted">ราย</span></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-none border bg-light-subtle p-2 text-center h-100">
                <div class="text-muted small fw-semibold">ภาระหนี้เดิม</div>
                <div class="fs-5 fw-bold text-secondary mt-1">฿<?= number_format($tot_raw_debit, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-none border bg-light-subtle p-2 text-center h-100">
                <div class="text-muted small fw-semibold">ชำระแล้ว / ชดเชย</div>
                <div class="fs-5 fw-bold text-success mt-1">฿<?= number_format($tot_paid, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-none border bg-light-subtle p-2 text-center h-100">
                <div class="text-muted small fw-semibold">หนี้คงค้างสุทธิ</div>
                <div class="fs-5 fw-bold text-danger mt-1">฿<?= number_format($tot_net_debit, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary px-3 py-2 fs-7">
            <i class="bx bx-check-shield me-1"></i> ผัง: <?= htmlspecialchars($accCode) ?>
        </span>
        <span class="badge bg-label-secondary px-3 py-2 fs-7">
            งวด: <?= htmlspecialchars($month) ?>
        </span>
        <span class="badge bg-label-info px-3 py-2 fs-7">
            อายุหนี้: <?= $range === 'lt_3' ? '< 3 เดือน' : ($range === '3_12' ? '3-12 เดือน' : ($range === 'gt_12' ? '> 12 เดือน' : 'รวมทั้งหมด')) ?>
        </span>
    </div>
    <div style="min-width: 280px;">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
            <input type="text" id="debtorDetailSearch" class="form-control border-start-0" placeholder="ค้นหา HN, ชื่อผู้ป่วย, ใบเสร็จ, กลุ่มย่อย..." autocomplete="off">
        </div>
    </div>
</div>

<?php if ($tot_cases > 0): ?>
<div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
    <table class="table table-bordered table-striped table-hover table-sm align-middle" id="debtorDetailTable" style="font-size: 13.5px;">
        <thead class="table-light sticky-top text-center" style="z-index: 10;">
            <tr style="background-color: #f1f3f5;">
                <th style="width: 50px;">ลำดับ</th>
                <th style="width: 60px;">ประเภท</th>
                <th style="width: 95px;">วันที่บริการ</th>
                <th style="width: 90px;">HN</th>
                <th>ชื่อ-สกุล ผู้ป่วย</th>
                <th style="width: 170px;">สิทธิ / กลุ่มบริการเฉพาะ</th>
                <th class="text-end" style="width: 105px;">ภาระหนี้เดิม</th>
                <th class="text-end" style="width: 105px;">ชำระ/ชดเชย</th>
                <th class="text-end" style="width: 110px;">หนี้คงเหลือสุทธิ</th>
                <th class="text-center" style="width: 185px;">สถานะใบเสร็จ / การตัดหนี้</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($final_rows as $row): 
                $bill_no = $row['resolved_bill'] ?? '';
                $bill_date = $row['resolved_billdate'] ?? '';
                $paid_hist = (float)($row['paid_history'] ?? 0);
                $paid_fol  = (float)($row['paid_follow'] ?? 0);
                $total_p   = $paid_hist + $paid_fol;
                $raw_d     = (float)($row['raw_debit'] ?? $row['debit']);
                $net_d     = (float)$row['debit'];

                // สร้างป้ายสถานะใบเสร็จแบบแม่นยำ
                $badge_html = '';
                if (!empty($bill_no) && $bill_no !== '-') {
                    $b_date_txt = !empty($bill_date) && $bill_date !== '-' ? " ($bill_date)" : "";
                    $badge_html = '<span class="badge bg-label-success text-truncate" title="เลขที่ใบเสร็จ: '.$bill_no.' '.$b_date_txt.'"><i class="bx bx-check-circle me-1"></i>'.$bill_no.$b_date_txt.'</span>';
                } elseif ($paid_hist > 0) {
                    $badge_html = '<span class="badge bg-label-info" title="แบ่งจ่ายแล้ว: ฿'.number_format($paid_hist,2).'"><i class="bx bx-pie-chart-alt-2 me-1"></i>แบ่งจ่าย (฿'.number_format($paid_hist, 0).')</span>';
                } else {
                    $badge_html = '<span class="badge bg-label-warning"><i class="bx bx-time-five me-1"></i>ยังไม่มีใบเสร็จ</span>';
                }
            ?>
            <tr class="patient-detail-row">
                <td class="text-center text-muted row-index"><?= $i++ ?></td>
                <td class="text-center">
                    <span class="badge <?= $row['type'] === 'IPD' ? 'bg-label-danger' : 'bg-label-primary' ?> px-2">
                        <?= htmlspecialchars($row['type']) ?>
                    </span>
                </td>
                <td class="text-center"><?= htmlspecialchars($row['vstdate']) ?></td>
                <td class="text-center fw-semibold text-secondary"><?= htmlspecialchars($row['hn'] ?? '-') ?></td>
                <td>
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($row['ptname']) ?></span>
                </td>
                <td>
                    <span class="badge bg-label-secondary text-wrap text-start" style="font-size: 11.5px; font-weight: 500;">
                        <?= htmlspecialchars($row['pttypename'] ?? '-') ?>
                    </span>
                </td>
                <td class="text-end text-muted"><?= number_format($raw_d, 2) ?></td>
                <td class="text-end text-success"><?= $total_p > 0 ? number_format($total_p, 2) : '-' ?></td>
                <td class="text-end fw-bold text-danger"><?= number_format($net_d, 2) ?></td>
                <td class="text-center"><?= $badge_html ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light sticky-bottom fw-bold" style="z-index: 5;">
            <tr style="background-color: #f8f9fa;">
                <td colspan="6" class="text-center fs-6">รวมทั้งสิ้น (<span id="visibleRowCount"><?= number_format($tot_cases) ?></span> ราย)</td>
                <td class="text-end text-muted" id="footerRawDebit">฿<?= number_format($tot_raw_debit, 2) ?></td>
                <td class="text-end text-success" id="footerPaidDebit">฿<?= number_format($tot_paid, 2) ?></td>
                <td class="text-end text-danger fs-6" id="footerNetDebit">฿<?= number_format($tot_net_debit, 2) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
// ระบบค้นหาแบบเรียลไทม์ภายใน Modal
$('#debtorDetailSearch').on('keyup', function() {
    var val = $(this).val().toLowerCase().trim();
    var visibleCount = 0;
    var sumRaw = 0.0, sumPaid = 0.0, sumNet = 0.0;

    $('#debtorDetailTable tbody tr.patient-detail-row').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(val) > -1) {
            $(this).show();
            visibleCount++;
            $(this).find('.row-index').text(visibleCount);
            
            // สะสมยอด
            var raw = parseFloat($(this).find('td:eq(6)').text().replace(/,/g, '')) || 0;
            var paid = parseFloat($(this).find('td:eq(7)').text().replace(/,/g, '')) || 0;
            var net = parseFloat($(this).find('td:eq(8)').text().replace(/,/g, '')) || 0;
            sumRaw += raw;
            sumPaid += paid;
            sumNet += net;
        } else {
            $(this).hide();
        }
    });

    $('#visibleRowCount').text(visibleCount.toLocaleString());
    $('#footerRawDebit').text('฿' + sumRaw.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#footerPaidDebit').text('฿' + sumPaid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#footerNetDebit').text('฿' + sumNet.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
});
</script>

<?php else: ?>
<div class="alert alert-secondary text-center my-4 py-4">
    <i class="bx bx-check-double text-success mb-2" style="font-size: 36px;"></i>
    <div class="fs-6 fw-bold">ไม่พบข้อมูลลูกหนี้คงค้างตามเงื่อนไขที่เลือก</div>
    <div class="text-muted small mt-1">รายการลูกหนี้ในหมวดหมู่นี้ได้รับการชำระ/ชดเชย หรือโอนไปยังผังลูกครบถ้วนแล้ว ณ งวดบัญชี <?= htmlspecialchars($month) ?></div>
</div>
<?php endif; ?>