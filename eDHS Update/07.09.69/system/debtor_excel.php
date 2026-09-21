<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/database_config/config.php';
require_once __DIR__ . '/database_config/db_helper.php';
require_once __DIR__ . '/includes/cr_migration_helper.php';
require_once __DIR__ . '/includes/sss_migration_helper.php';
require __DIR__ . '/vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// --- ตรวจสอบ Input ---
if (!isset($_GET['acc']) || !preg_match('/^\d{10}\.\d{3}$/', $_GET['acc'])) {
    die('❌ รูปแบบ acc ไม่ถูกต้อง');
}
if (!isset($_GET['month']) || !preg_match('/^\d{1,2}-\d{4}$/', $_GET['month'])) {
    die('❌ รูปแบบ month ไม่ถูกต้อง');
}

// 1. รับค่าและเตรียมตัวแปร
$acc    = $_GET['acc'];   
$monthe = $_GET['month']; // เช่น "8-2026"
list($month_num, $year_num) = explode('-', $monthe);
$target_ym = sprintf('%s-%02d', $year_num, $month_num); // เช่น "2026-08"

// 2. ดึงรายการเดือนทั้งหมดในระบบที่ <= target_ym เพื่อค้นหาผ่าน Index (monthtxt IN (...)) เพิ่มความเร็วสูง
$valid_months = [];
$sql_m = "
    SELECT DISTINCT monthtxt FROM (
        SELECT monthtxt FROM imr_tb_debtor_rights_opd WHERE monthtxt IS NOT NULL AND monthtxt != ''
        UNION
        SELECT monthtxt FROM imr_tb_debtor_rights_ipd WHERE monthtxt IS NOT NULL AND monthtxt != ''
    ) AS all_m
";
$res_m = $conn->query($sql_m);
if ($res_m) {
    while ($rm = $res_m->fetch_assoc()) {
        $m_str = trim($rm['monthtxt']);
        $parts = explode('-', $m_str);
        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $ym = sprintf('%04d-%02d', (int)$parts[1], (int)$parts[0]);
            if ($ym <= $target_ym) {
                $valid_months[] = $m_str;
            }
        }
    }
}
if (empty($valid_months)) {
    $valid_months[] = $monthe;
}
$valid_months_sql = "'" . implode("','", array_map([$conn, 'real_escape_string'], $valid_months)) . "'";

// 3. จำแนกประเภทผังบัญชี (CR/SSS Child & Parent Accounts)
$is_cr_child_opd  = ($acc === '1102050101.216');
$is_cr_child_ipd  = ($acc === '1102050101.217');
$is_sss_child_opd = ($acc === '1102050101.309');
$is_sss_child_ipd = ($acc === '1102050101.310');
$is_child_acc     = ($is_cr_child_opd || $is_cr_child_ipd || $is_sss_child_opd || $is_sss_child_ipd);

$is_cr_parent_opd  = in_array($acc, ['1102050101.201', '1102050101.203', '1102050101.209']);
$is_cr_parent_ipd  = in_array($acc, ['1102050101.202']);
$is_sss_parent_opd = in_array($acc, ['1102050101.301', '1102050101.303', '1102050101.307', '1102050101.308']);
$is_sss_parent_ipd = in_array($acc, ['1102050101.302', '1102050101.304']);
$is_parent_acc     = ($is_cr_parent_opd || $is_cr_parent_ipd || $is_sss_parent_opd || $is_sss_parent_ipd);

$cr_config   = get_active_cr_config($conn);
$sss_config  = get_active_sss_config($conn);
$my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';

// 4. โหลดข้อมูลสรุปการโอน CR / SSS สำหรับเดือนที่มีผลบังคับใช้ (ไม่เกิน target_ym)
$cr_opd_visits_all  = [];
$cr_ipd_visits_all  = [];
$sss_opd_visits_all = [];
$sss_ipd_visits_all = [];

foreach ($valid_months as $vm) {
    if (is_cr_effective_for_month($cr_config, $vm) && is_cr_auto_split_enabled($cr_config, 'OPD')) {
        $s = get_cr_splitting_summary_for_month($conn, $conn2 ?? null, $vm, 'OPD', $cr_config, $my_hospcode);
        if ($s && !empty($s['cr_visits'])) {
            foreach ($s['cr_visits'] as $v_vn => $vinfo) {
                $cr_opd_visits_all[$v_vn] = $vinfo;
            }
        }
    }
    if (is_cr_effective_for_month($cr_config, $vm) && is_cr_auto_split_enabled($cr_config, 'IPD')) {
        $s = get_cr_splitting_summary_for_month($conn, $conn2 ?? null, $vm, 'IPD', $cr_config, $my_hospcode);
        if ($s && !empty($s['cr_visits'])) {
            foreach ($s['cr_visits'] as $v_an => $vinfo) {
                $cr_ipd_visits_all[$v_an] = $vinfo;
            }
        }
    }
    if (is_sss_effective_for_month($sss_config, $vm) && is_sss_auto_split_enabled($sss_config, 'OPD')) {
        $s = get_sss_splitting_summary_for_month($conn, $conn2 ?? null, $vm, 'OPD', $sss_config, $my_hospcode);
        if ($s && !empty($s['sss_visits'])) {
            foreach ($s['sss_visits'] as $v_vn => $vinfo) {
                $sss_opd_visits_all[$v_vn] = $vinfo;
            }
        }
    }
    if (is_sss_effective_for_month($sss_config, $vm) && is_sss_auto_split_enabled($sss_config, 'IPD')) {
        $s = get_sss_splitting_summary_for_month($conn, $conn2 ?? null, $vm, 'IPD', $sss_config, $my_hospcode);
        if ($s && !empty($s['sss_visits'])) {
            foreach ($s['sss_visits'] as $v_an => $vinfo) {
                $sss_ipd_visits_all[$v_an] = $vinfo;
            }
        }
    }
}

// 5. ดึงข้อมูลลูกหนี้ดิบจากฐานข้อมูลตาม accountcode ที่ร้องขอ
$receiptWhere = "
    CASE
        WHEN billdate IS NOT NULL AND billdate != ''
         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
        THEN CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) > ?

        WHEN mobile IS NOT NULL AND mobile != ''
         AND mobile != '-' AND CHAR_LENGTH(mobile) >= 10
         AND mobile LIKE '%/%'
        THEN CONCAT(
               (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
               '-',
               SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
             ) > ?

        ELSE TRUE
    END
";

// ตรวจสอบสถานะการเปิดใช้งาน CR IPD สำหรับเดือนเป้าหมาย (สอดคล้องกับ ทะเบียนคุมลูกหนี้1.php บรรทัดที่ 64)
$auto_split_cr_ipd = (is_cr_effective_for_month($cr_config, $monthe) && is_cr_auto_split_enabled($cr_config, 'IPD'));
$ipd_acc_param1 = $acc;
$ipd_acc_param2 = (!$auto_split_cr_ipd && $acc === '1102050101.202') ? '1102050101.217' : $acc;
if (!$auto_split_cr_ipd && $acc === '1102050101.217') {
    $ipd_acc_param1 = 'NONE';
    $ipd_acc_param2 = 'NONE';
}

$sql = "
SELECT * FROM (
    /* ==== OPD ==== */
    SELECT vn, vstdate, cid, ptname, mobile, billdate, accountcode,
            (IFNULL(debit, 0) - 
                CASE
                    WHEN billdate IS NOT NULL AND billdate != '' 
                     AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                     AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= ?
                    THEN IFNULL(follow_money, 0)

                    WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                     AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                     AND CHAR_LENGTH(mobile) >= 10
                     AND mobile LIKE '%/%'
                     AND CONCAT(
                             (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                             '-',
                             SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                         ) <= ?
                    THEN IFNULL(follow_money, 0)

                    ELSE 0
                END
                - IFNULL((
                    SELECT SUM(ph.pay_amount)
                    FROM imr_tb_payment_history ph
                    WHERE ph.ref_vn_an = vn
                      AND ph.patient_type = 'OPD'
                      AND ph.bill_date IS NOT NULL
                      AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= ?
                  ), 0)
            ) AS debit,
           pttypename, 'OPD' as type
    FROM imr_tb_debtor_rights_opd
    WHERE accountcode = ? 
      AND IFNULL(debit, 0) > 0
      AND monthtxt IN ($valid_months_sql)

    UNION ALL

    /* ==== IPD ==== */
    SELECT an AS vn, dchdate AS vstdate, cid, ptname, mobile, billdate, accountcode,
            (IFNULL(debit, 0) - 
                CASE
                    WHEN billdate IS NOT NULL AND billdate != '' 
                     AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                     AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= ?
                    THEN IFNULL(follow_money, 0)

                    WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                     AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                     AND CHAR_LENGTH(mobile) >= 10
                     AND mobile LIKE '%/%'
                     AND CONCAT(
                             (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                             '-',
                             SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                         ) <= ?
                    THEN IFNULL(follow_money, 0)

                    ELSE 0
                END
                - IFNULL((
                    SELECT SUM(ph.pay_amount)
                    FROM imr_tb_payment_history ph
                    WHERE ph.ref_vn_an = an
                      AND ph.patient_type = 'IPD'
                      AND ph.bill_date IS NOT NULL
                      AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= ?
                  ), 0)
            ) AS debit,
           pttypename, 'IPD' as type
    FROM imr_tb_debtor_rights_ipd
    WHERE accountcode IN (?, ?) 
      AND IFNULL(debit, 0) > 0
      AND monthtxt IN ($valid_months_sql)
) AS all_debtors
WHERE 
    debit > 0
    AND ($receiptWhere)
ORDER BY pttypename, vn
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssssssss',
    $target_ym, $target_ym, $target_ym, $acc,
    $target_ym, $target_ym, $target_ym, $ipd_acc_param1, $ipd_acc_param2,
    $target_ym, $target_ym
);
$stmt->execute();
$res = $stmt->get_result();

$final_rows = [];
$raw_seen = [];

// ประมวลผลแถวข้อมูลดิบ และตัดรายการที่โอนออกสำหรับผังแม่
while ($row = $res->fetch_assoc()) {
    $ref_id = $row['vn'];
    $raw_seen[$ref_id] = true;

    if ($is_parent_acc) {
        // ตรวจสอบการโอนออก CR OPD
        if ($is_cr_parent_opd && isset($cr_opd_visits_all[$ref_id])) {
            $vinfo = $cr_opd_visits_all[$ref_id];
            if (!empty($vinfo['is_full_transfer']) || (float)$vinfo['general_remain_amount'] <= 0.01) {
                continue; // โอนออกทั้งหมด ไม่นำมาแสดงในผังแม่
            }
            // ปรับลดยอด debit ของผังแม่ให้เหลือเฉพาะยอดทั่วไป
            $row['debit'] = (float)$vinfo['general_remain_amount'];
        }
        // ตรวจสอบการโอนออก CR IPD
        if ($is_cr_parent_ipd && isset($cr_ipd_visits_all[$ref_id])) {
            $vinfo = $cr_ipd_visits_all[$ref_id];
            if (!empty($vinfo['is_full_transfer']) || (float)$vinfo['general_remain_amount'] <= 0.01) {
                continue;
            }
            $row['debit'] = (float)$vinfo['general_remain_amount'];
        }
        // ตรวจสอบการโอนออก SSS OPD
        if ($is_sss_parent_opd && isset($sss_opd_visits_all[$ref_id])) {
            $vinfo = $sss_opd_visits_all[$ref_id];
            if (!empty($vinfo['is_full_transfer']) || (float)$vinfo['general_remain_amount'] <= 0.01) {
                continue;
            }
            $row['debit'] = (float)$vinfo['general_remain_amount'];
        }
        // ตรวจสอบการโอนออก SSS IPD
        if ($is_sss_parent_ipd && isset($sss_ipd_visits_all[$ref_id])) {
            $vinfo = $sss_ipd_visits_all[$ref_id];
            if (!empty($vinfo['is_full_transfer']) || (float)$vinfo['general_remain_amount'] <= 0.01) {
                continue;
            }
            $row['debit'] = (float)$vinfo['general_remain_amount'];
        }
    }

    $final_rows[] = $row;
}

// 5.1 คืนรายการที่มียอดคงเหลือในผังแม่ (Parent Accounts) แต่ถูกกรองออกไปโดยเงื่อนไขใบเสร็จของผังลูก (เช่น เคสตัดโอน CR บางส่วน แต่ผังแม่ยังมีหนี้คงเหลือ)
if ($is_parent_acc) {
    $parent_visits_to_check = [];
    if ($is_cr_parent_opd)  $parent_visits_to_check = $cr_opd_visits_all;
    if ($is_cr_parent_ipd)  $parent_visits_to_check = $cr_ipd_visits_all;
    if ($is_sss_parent_opd) $parent_visits_to_check = $sss_opd_visits_all;
    if ($is_sss_parent_ipd) $parent_visits_to_check = $sss_ipd_visits_all;

    $missing_parent_ids = [];
    foreach ($parent_visits_to_check as $v_id => $vinfo) {
        if (isset($raw_seen[$v_id])) continue;
        if (empty($vinfo['origin_accountcode']) || $vinfo['origin_accountcode'] !== $acc) continue;
        $remain = (float)($vinfo['general_remain_amount'] ?? 0);
        if ($remain > 0.01) {
            $missing_parent_ids[$v_id] = $remain;
        }
    }

    if (!empty($missing_parent_ids)) {
        $pt_table = ($is_cr_parent_opd || $is_sss_parent_opd) ? 'imr_tb_debtor_rights_opd' : 'imr_tb_debtor_rights_ipd';
        $pk_col = ($is_cr_parent_opd || $is_sss_parent_opd) ? 'vn' : 'an';
        $date_select = ($is_cr_parent_opd || $is_sss_parent_opd) ? 'vstdate' : 'dchdate AS vstdate';
        $v_type = ($is_cr_parent_opd || $is_sss_parent_opd) ? 'OPD' : 'IPD';

        $id_chunks = array_chunk(array_keys($missing_parent_ids), 500);
        foreach ($id_chunks as $chunk) {
            $ids_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $chunk)) . "'";
            $sql_parent = "
                SELECT $pk_col AS vn, $date_select, cid, ptname, mobile, billdate, accountcode, pttypename
                FROM $pt_table
                WHERE $pk_col IN ($ids_str)
                  AND (bill IS NULL OR bill != '0000/0000')
            ";
            $res_parent = $conn->query($sql_parent);
            if ($res_parent) {
                while ($r_p = $res_parent->fetch_assoc()) {
                    $pid = $r_p['vn'];
                    $final_rows[] = [
                        'vn'          => $r_p['vn'],
                        'vstdate'     => $r_p['vstdate'],
                        'cid'         => $r_p['cid'],
                        'ptname'      => $r_p['ptname'],
                        'mobile'      => '-',
                        'billdate'    => '',
                        'accountcode' => $r_p['accountcode'],
                        'debit'       => (float)$missing_parent_ids[$pid],
                        'pttypename'  => $r_p['pttypename'],
                        'type'        => $v_type
                    ];
                    $raw_seen[$pid] = true;
                }
            }
        }
    }
}

// 6. เพิ่มรายการผู้ป่วยที่ถูกตัดโอนเข้ามา สำหรับผังลูก (Child Accounts)
if ($is_cr_child_opd && !empty($cr_opd_visits_all)) {
    $transferred_vns = [];
    foreach ($cr_opd_visits_all as $v_vn => $vinfo) {
        if (!empty($vinfo['is_kidney'])) continue; // เคสฟอกไตถูกดึงจากตารางหลักแล้ว
        if (isset($raw_seen[$v_vn])) continue;
        if (($vinfo['origin_accountcode'] ?? '') === $acc) continue; // ข้ามเคสที่เป็นผังลูกผังนี้แต่เดิม (ถูกประมวลผลในข้อ 5 แล้ว)
        $transferred_vns[] = $v_vn;
    }
    if (!empty($transferred_vns)) {
        $vn_chunks = array_chunk($transferred_vns, 500);
        foreach ($vn_chunks as $chunk) {
            $vns_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $chunk)) . "'";
            $sql_in = "
                SELECT o.vn, o.vstdate, o.cid, o.ptname, o.mobile, o.billdate,
                       o.follow_money, o.bill, o.pttypename,
                       b.billdate as bd_billdate, b.compensated as bd_comp, b.bill as bd_bill, b.settle_status
                FROM imr_tb_debtor_rights_opd o
                LEFT JOIN (
                    SELECT vn, MAX(billdate) as billdate, SUM(compensated) as compensated, MAX(bill) as bill, MAX(settle_status) as settle_status
                    FROM imr_tb_debtor_cr_breakdown
                    WHERE visit_type = 'OPD'
                    GROUP BY vn
                ) b ON b.vn = o.vn
                WHERE o.vn IN ($vns_str)
            ";
            $res_in = $conn->query($sql_in);
            if ($res_in) {
                while ($r_in = $res_in->fetch_assoc()) {
                    $v_vn = $r_in['vn'];
                    $vinfo = $cr_opd_visits_all[$v_vn];
                    $cr_debit = (float)($vinfo['transfer_out'] > 0 ? $vinfo['transfer_out'] : $vinfo['cr_amount']);

                    $bill_d  = !empty($r_in['bd_billdate']) ? $r_in['bd_billdate'] : '';
                    $comp    = !empty($r_in['bd_comp']) ? (float)$r_in['bd_comp'] : 0.0;
                    $bill_no = !empty($r_in['bd_bill']) ? $r_in['bd_bill'] : '';
                    $status  = $r_in['settle_status'] ?? '';

                    $is_paid = false;
                    $bym = '';
                    if (!empty($bill_d) && $bill_d != '-' && strlen($bill_d) >= 10) {
                        $by = (int)substr($bill_d, 6, 4) - 543;
                        $bm = (int)substr($bill_d, 3, 2);
                        $bym = sprintf('%04d-%02d', $by, $bm);
                        if ($bym <= $target_ym && ($status === 'SETTLED' || ($comp > 0 && ($cr_debit - $comp) <= 0.01))) {
                            $is_paid = true;
                        }
                    }

                    $net_debit = max(0, $cr_debit - ($comp > 0 && !empty($bym) && $bym <= $target_ym ? $comp : 0));
                    if ($net_debit > 0.01 && !$is_paid) {
                        $types_label = !empty($vinfo['cr_types']) ? implode(', ', $vinfo['cr_types']) : 'CR';
                        $final_rows[] = [
                            'vn'          => $r_in['vn'],
                            'vstdate'     => $r_in['vstdate'],
                            'cid'         => $r_in['cid'],
                            'ptname'      => $r_in['ptname'],
                            'mobile'      => '-',
                            'billdate'    => '',
                            'accountcode' => '1102050101.216',
                            'debit'       => $net_debit,
                            'pttypename'  => 'CR: ' . $types_label,
                            'type'        => 'OPD'
                        ];
                    }
                }
            }
        }
    }
} elseif ($is_cr_child_ipd && !empty($cr_ipd_visits_all)) {
    $transferred_ans = [];
    foreach ($cr_ipd_visits_all as $v_an => $vinfo) {
        if (isset($raw_seen[$v_an])) continue;
        if (($vinfo['origin_accountcode'] ?? '') === $acc) continue;
        $transferred_ans[] = $v_an;
    }
    if (!empty($transferred_ans)) {
        $an_chunks = array_chunk($transferred_ans, 500);
        foreach ($an_chunks as $chunk) {
            $ans_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $chunk)) . "'";
            $sql_in = "
                SELECT i.an as vn, i.dchdate as vstdate, i.cid, i.ptname, i.mobile, i.billdate,
                       i.follow_money, i.bill, i.pttypename,
                       b.billdate as bd_billdate, b.compensated as bd_comp, b.bill as bd_bill, b.settle_status
                FROM imr_tb_debtor_rights_ipd i
                LEFT JOIN (
                    SELECT vn, MAX(billdate) as billdate, SUM(compensated) as compensated, MAX(bill) as bill, MAX(settle_status) as settle_status
                    FROM imr_tb_debtor_cr_breakdown
                    WHERE visit_type = 'IPD'
                    GROUP BY vn
                ) b ON b.vn = i.an
                WHERE i.an IN ($ans_str)
            ";
            $res_in = $conn->query($sql_in);
            if ($res_in) {
                while ($r_in = $res_in->fetch_assoc()) {
                    $v_an = $r_in['vn'];
                    $vinfo = $cr_ipd_visits_all[$v_an];
                    $cr_debit = (float)($vinfo['transfer_out'] > 0 ? $vinfo['transfer_out'] : $vinfo['cr_amount']);

                    $bill_d  = !empty($r_in['bd_billdate']) ? $r_in['bd_billdate'] : '';
                    $comp    = !empty($r_in['bd_comp']) ? (float)$r_in['bd_comp'] : 0.0;
                    $bill_no = !empty($r_in['bd_bill']) ? $r_in['bd_bill'] : '';
                    $status  = $r_in['settle_status'] ?? '';

                    $is_paid = false;
                    $bym = '';
                    if (!empty($bill_d) && $bill_d != '-' && strlen($bill_d) >= 10) {
                        $by = (int)substr($bill_d, 6, 4) - 543;
                        $bm = (int)substr($bill_d, 3, 2);
                        $bym = sprintf('%04d-%02d', $by, $bm);
                        if ($bym <= $target_ym && ($status === 'SETTLED' || ($comp > 0 && ($cr_debit - $comp) <= 0.01))) {
                            $is_paid = true;
                        }
                    }

                    $net_debit = max(0, $cr_debit - ($comp > 0 && !empty($bym) && $bym <= $target_ym ? $comp : 0));
                    if ($net_debit > 0.01 && !$is_paid) {
                        $types_label = !empty($vinfo['cr_types']) ? implode(', ', $vinfo['cr_types']) : 'CR IPD';
                        $final_rows[] = [
                            'vn'          => $r_in['vn'],
                            'vstdate'     => $r_in['vstdate'],
                            'cid'         => $r_in['cid'],
                            'ptname'      => $r_in['ptname'],
                            'mobile'      => '-',
                            'billdate'    => '',
                            'accountcode' => '1102050101.217',
                            'debit'       => $net_debit,
                            'pttypename'  => 'CR: ' . $types_label,
                            'type'        => 'IPD'
                        ];
                    }
                }
            }
        }
    }
} elseif ($is_sss_child_opd && !empty($sss_opd_visits_all)) {
    $transferred_vns = [];
    foreach ($sss_opd_visits_all as $v_vn => $vinfo) {
        if (isset($raw_seen[$v_vn])) continue;
        if (($vinfo['origin_accountcode'] ?? '') === $acc) continue;
        $transferred_vns[] = $v_vn;
    }
    if (!empty($transferred_vns)) {
        $vn_chunks = array_chunk($transferred_vns, 500);
        foreach ($vn_chunks as $chunk) {
            $vns_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $chunk)) . "'";
            $sql_in = "
                SELECT o.vn, o.vstdate, o.cid, o.ptname, o.mobile, o.billdate,
                       o.follow_money, o.bill, o.pttypename,
                       b.billdate as bd_billdate, b.compensated as bd_comp, b.bill as bd_bill, b.settle_status
                FROM imr_tb_debtor_rights_opd o
                LEFT JOIN (
                    SELECT vn, MAX(billdate) as billdate, SUM(compensated) as compensated, MAX(bill) as bill, MAX(settle_status) as settle_status
                    FROM imr_tb_debtor_sss_breakdown
                    WHERE visit_type = 'OPD'
                    GROUP BY vn
                ) b ON b.vn = o.vn
                WHERE o.vn IN ($vns_str)
            ";
            $res_in = $conn->query($sql_in);
            if ($res_in) {
                while ($r_in = $res_in->fetch_assoc()) {
                    $v_vn = $r_in['vn'];
                    $vinfo = $sss_opd_visits_all[$v_vn];
                    $sss_debit = (float)($vinfo['transfer_out'] > 0 ? $vinfo['transfer_out'] : $vinfo['sss_amount']);

                    $bill_d  = !empty($r_in['bd_billdate']) ? $r_in['bd_billdate'] : '';
                    $comp    = !empty($r_in['bd_comp']) ? (float)$r_in['bd_comp'] : 0.0;
                    $bill_no = !empty($r_in['bd_bill']) ? $r_in['bd_bill'] : '';
                    $status  = $r_in['settle_status'] ?? '';

                    $is_paid = false;
                    $bym = '';
                    if (!empty($bill_d) && $bill_d != '-' && strlen($bill_d) >= 10) {
                        $by = (int)substr($bill_d, 6, 4) - 543;
                        $bm = (int)substr($bill_d, 3, 2);
                        $bym = sprintf('%04d-%02d', $by, $bm);
                        if ($bym <= $target_ym && ($status === 'SETTLED' || ($comp > 0 && ($sss_debit - $comp) <= 0.01))) {
                            $is_paid = true;
                        }
                    }

                    $net_debit = max(0, $sss_debit - ($comp > 0 && !empty($bym) && $bym <= $target_ym ? $comp : 0));
                    if ($net_debit > 0.01 && !$is_paid) {
                        $types_label = !empty($vinfo['sss_types']) ? implode(', ', $vinfo['sss_types']) : 'SSS';
                        $final_rows[] = [
                            'vn'          => $r_in['vn'],
                            'vstdate'     => $r_in['vstdate'],
                            'cid'         => $r_in['cid'],
                            'ptname'      => $r_in['ptname'],
                            'mobile'      => '-',
                            'billdate'    => '',
                            'accountcode' => '1102050101.309',
                            'debit'       => $net_debit,
                            'pttypename'  => 'SSS: ' . $types_label,
                            'type'        => 'OPD'
                        ];
                    }
                }
            }
        }
    }
} elseif ($is_sss_child_ipd && !empty($sss_ipd_visits_all)) {
    $transferred_ans = [];
    foreach ($sss_ipd_visits_all as $v_an => $vinfo) {
        if (isset($raw_seen[$v_an])) continue;
        if (($vinfo['origin_accountcode'] ?? '') === $acc) continue;
        $transferred_ans[] = $v_an;
    }
    if (!empty($transferred_ans)) {
        $an_chunks = array_chunk($transferred_ans, 500);
        foreach ($an_chunks as $chunk) {
            $ans_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $chunk)) . "'";
            $sql_in = "
                SELECT i.an as vn, i.dchdate as vstdate, i.cid, i.ptname, i.mobile, i.billdate,
                       i.follow_money, i.bill, i.pttypename,
                       b.billdate as bd_billdate, b.compensated as bd_comp, b.bill as bd_bill, b.settle_status
                FROM imr_tb_debtor_rights_ipd i
                LEFT JOIN (
                    SELECT vn, MAX(billdate) as billdate, SUM(compensated) as compensated, MAX(bill) as bill, MAX(settle_status) as settle_status
                    FROM imr_tb_debtor_sss_breakdown
                    WHERE visit_type = 'IPD'
                    GROUP BY vn
                ) b ON b.vn = i.an
                WHERE i.an IN ($ans_str)
            ";
            $res_in = $conn->query($sql_in);
            if ($res_in) {
                while ($r_in = $res_in->fetch_assoc()) {
                    $v_an = $r_in['vn'];
                    $vinfo = $sss_ipd_visits_all[$v_an];
                    $sss_debit = (float)($vinfo['transfer_out'] > 0 ? $vinfo['transfer_out'] : $vinfo['sss_amount']);

                    $bill_d  = !empty($r_in['bd_billdate']) ? $r_in['bd_billdate'] : '';
                    $comp    = !empty($r_in['bd_comp']) ? (float)$r_in['bd_comp'] : 0.0;
                    $bill_no = !empty($r_in['bd_bill']) ? $r_in['bd_bill'] : '';
                    $status  = $r_in['settle_status'] ?? '';

                    $is_paid = false;
                    $bym = '';
                    if (!empty($bill_d) && $bill_d != '-' && strlen($bill_d) >= 10) {
                        $by = (int)substr($bill_d, 6, 4) - 543;
                        $bm = (int)substr($bill_d, 3, 2);
                        $bym = sprintf('%04d-%02d', $by, $bm);
                        if ($bym <= $target_ym && ($status === 'SETTLED' || ($comp > 0 && ($sss_debit - $comp) <= 0.01))) {
                            $is_paid = true;
                        }
                    }

                    $net_debit = max(0, $sss_debit - ($comp > 0 && !empty($bym) && $bym <= $target_ym ? $comp : 0));
                    if ($net_debit > 0.01 && !$is_paid) {
                        $types_label = !empty($vinfo['sss_types']) ? implode(', ', $vinfo['sss_types']) : 'SSS IPD';
                        $final_rows[] = [
                            'vn'          => $r_in['vn'],
                            'vstdate'     => $r_in['vstdate'],
                            'cid'         => $r_in['cid'],
                            'ptname'      => $r_in['ptname'],
                            'mobile'      => '-',
                            'billdate'    => '',
                            'accountcode' => '1102050101.310',
                            'debit'       => $net_debit,
                            'pttypename'  => 'SSS: ' . $types_label,
                            'type'        => 'IPD'
                        ];
                    }
                }
            }
        }
    }
}

// 6.9 ล้างเลขที่และวันที่ใบเสร็จสำหรับลูกหนี้คงเหลือที่ยังไม่ชำระ (Unpaid Debtors Must NOT Have Receipt Numbers)
foreach ($final_rows as &$frow) {
    $frow['mobile'] = '-';
    $frow['billdate'] = '';
}
unset($frow);

// 7. จัดเรียงข้อมูลเพื่อความสวยงาม
usort($final_rows, function($a, $b) {
    $cmp = strcmp($a['pttypename'] ?? '', $b['pttypename'] ?? '');
    if ($cmp !== 0) return $cmp;
    return strcmp($a['vn'] ?? '', $b['vn'] ?? '');
});

// 8. ตรวจสอบว่ามีข้อมูลหรือไม่
if (empty($final_rows)) {
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'แจ้งเตือน',
                text: 'ไม่พบข้อมูลลูกหนี้รายตัวของรหัสผังบัญชีดังกล่าว...!',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.history.back();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// 9. สร้างชีต Excel ด้วย PhpSpreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 9.1 Header row
$firstRow = $final_rows[0];
$headers = array_keys($firstRow);
$sheet->fromArray($headers, NULL, 'A1');

$debitColIndex = array_search('debit', $headers) + 1;

// 9.2 Data rows & คำนวณยอดรวม
$row = 2;
$total_debit = 0.0;

foreach ($final_rows as $data) {
    $col = 1;
    
    // ถอดรหัส cid ถ้ามีการเข้ารหัส
    if (isset($data['cid'])) {
        $data['cid'] = decrypt_data($data['cid']);
    }
    if (isset($data['tel'])) {
        $data['tel'] = decrypt_data($data['tel']);
    }

    $total_debit += (float)$data['debit'];

    foreach ($data as $key => $value) {
        if ($key === 'debit') {
            $sheet->getCellByColumnAndRow($col, $row)
                  ->setValueExplicit((float)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyleByColumnAndRow($col, $row)
                  ->getNumberFormat()
                  ->setFormatCode('#,##0.00');
        } else {
            $sheet->getCellByColumnAndRow($col, $row)
                  ->setValueExplicit((string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getStyleByColumnAndRow($col, $row)
                  ->getNumberFormat()
                  ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }
        $col++;
    }
    $row++;
}

// 9.3 แถวสรุปผลรวมท้ายตาราง (Footer Row)
if ($debitColIndex > 0) {
    $labelCol = ($debitColIndex > 1) ? $debitColIndex - 1 : 1;
    $sheet->setCellValueByColumnAndRow($labelCol, $row, 'รวมทั้งสิ้น :');
    
    $cellDebit = $sheet->getCellByColumnAndRow($debitColIndex, $row);
    $cellDebit->setValueExplicit($total_debit, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

    $sheet->getStyleByColumnAndRow($labelCol, $row)->getFont()->setBold(true);
    $sheet->getStyleByColumnAndRow($debitColIndex, $row)->getFont()->setBold(true);
    
    $sheet->getStyleByColumnAndRow($debitColIndex, $row)
          ->getNumberFormat()
          ->setFormatCode('#,##0.00');
}

// 9.4 ปรับขนาดคอลัมน์อัตโนมัติ
$highestColumn = $sheet->getHighestColumn();
foreach (range('A', $highestColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 10. ดาวน์โหลดไฟล์ Excel
$filename = "Debtor_{$acc}_{$monthe}.xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

if (function_exists('system_log')) {
    system_log($conn, 'ระบบลูกหนี้ (Export)', 'EXPORT', "ผู้ใช้ส่งออกรายงานข้อมูลลูกหนี้รายตัวเป็น Excel ผัง $acc เดือน $monthe");
}

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;