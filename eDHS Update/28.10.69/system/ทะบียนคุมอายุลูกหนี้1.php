<?php
header('Content-Type: text/html; charset=utf-8');
global $conn, $conn2, $configData;
require_once __DIR__ . '/database_config/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $conn = $GLOBALS['conn'];
    }
}
require_once __DIR__ . '/database_config/db_helper.php';
require_once __DIR__ . '/includes/cr_migration_helper.php';
require_once __DIR__ . '/includes/sss_migration_helper.php';

if (function_exists('system_log') && isset($conn) && $conn instanceof mysqli) {
    $pageName = basename($_SERVER['PHP_SELF']);
    system_log($conn, 'เปิดดูข้อมูล (View)', 'VIEW', "ผู้ใช้เปิดดูหน้ารายงาน/ข้อมูล: " . $pageName);
}

if (!function_exists('getReconParentRecoveries')) {
    function getReconParentRecoveries($conn, $cr_opd, $cr_ipd, $sss_opd, $sss_ipd, $target_ym, $acc_filter = null) {
        // คืนค่าว่าง เพื่อไม่ให้ดึงเคสที่ชำระ/เคลียร์หนี้แล้วในผังแม่กลับมาเป็นหนี้ซ้ำซ้อน
        return [];
    }
}

if (!function_exists('DateThai')) {
    function DateThai($strDate)
    {
        $strYear = date("Y",strtotime($strDate))+543;
        $strMonth= date("n",strtotime($strDate));
        $strDay= date("j",strtotime($strDate));
        $strMonthCut = Array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
        $strMonthThai=$strMonthCut[$strMonth];
        return "$strDay $strMonthThai $strYear";
    }
}

if (!function_exists('DateThaiM')) {
    function DateThaiM($strDate) {
        $strYear = date("Y",strtotime($strDate))+543;
        $strMonth= date("n",strtotime($strDate));
        $strMonthCut = Array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
        $strMonthThai=$strMonthCut[$strMonth];
        return [
            "fullDate" => "$strMonthThai $strYear",
            "fiscalYear" => $strYear 
        ];
    }
}

$strDate = date("Y/m/d");
$yyy = date("Y")+543;

if (isset($_POST['month'])) {

    $month = $_POST['month']; 
    if (strpos($month, '-') === false) {
        exit;
    }
    
    // หาวันที่สิ้นเดือนของเดือนที่ส่งมา เพื่อใช้เป็นจุดตัดอายุหนี้
    $dateObj = DateTime::createFromFormat('m-Y', $month);
    $gmm = $dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d'); 
    $last_day_of_month = date('t', strtotime($gmm)); // ดึงวันสิ้นเดือนมาแสดงในตาราง

    list($month_num, $year_num) = explode('-', $month);
    $target_ym = sprintf('%s-%02d', $year_num, $month_num);
    $last_day_date = date('Y-m-t', mktime(0, 0, 0, $month_num, 1, $year_num));

    // เงื่อนไขการตัดรับชำระสำหรับลูกหนี้
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

    // คำนวณอายุหนี้แบบไดนามิกเทียบกับวันสิ้นเดือนของงวดที่เลือก
    $ageExprOPD = "TIMESTAMPDIFF(MONTH,
        STR_TO_DATE(CONCAT(SUBSTR(vstdate,1,6),CAST(SUBSTR(vstdate,7,4) AS SIGNED)-543),'%d/%m/%Y'),
        '$last_day_date')";

    $ageExprIPD = "TIMESTAMPDIFF(MONTH,
        STR_TO_DATE(CONCAT(SUBSTR(dchdate,1,6),CAST(SUBSTR(dchdate,7,4) AS SIGNED)-543),'%d/%m/%Y'),
        '$last_day_date')";

    // SQL ดึงยอดเบื้องต้นแยกตามอายุหนี้แบบรวมศูนย์ความเร็วสูง
    $sql_batch = "
    SELECT accountcode,
        SUM(CASE WHEN age < 3 THEN 1 ELSE 0 END) AS count_lt_3,
        SUM(CASE WHEN age < 3 THEN debit ELSE 0 END) AS sum_lt_3,
        SUM(CASE WHEN age >= 3 AND age <= 12 THEN 1 ELSE 0 END) AS count_3_12,
        SUM(CASE WHEN age >= 3 AND age <= 12 THEN debit ELSE 0 END) AS sum_3_12,
        SUM(CASE WHEN age > 12 THEN 1 ELSE 0 END) AS count_gt_12,
        SUM(CASE WHEN age > 12 THEN debit ELSE 0 END) AS sum_gt_12,
        COUNT(*) AS count_total,
        SUM(debit) AS sum_total
    FROM (
        /* OPD */
        SELECT accountcode,
               $ageExprOPD AS age,
               (IFNULL(debit, 0) - 
                    CASE
                        WHEN billdate IS NOT NULL AND billdate != '' 
                         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                         AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
                        THEN IFNULL(follow_money, 0)

                        WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                         AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                         AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
                         AND CONCAT(
                                 (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                                 '-',
                                 SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                             ) <= '$target_ym'
                        THEN IFNULL(follow_money, 0)

                        ELSE 0
                    END
                    - IFNULL((
                        SELECT SUM(ph.pay_amount)
                        FROM imr_tb_payment_history ph
                        WHERE ph.ref_vn_an = vn
                          AND ph.patient_type = 'OPD'
                          AND ph.bill_date IS NOT NULL
                          AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
                      ), 0)
               ) AS debit
        FROM imr_tb_debtor_rights_opd
        WHERE IFNULL(debit, 0) > 0
          AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$target_ym'
          AND ($receiptWhere)

        UNION ALL

        /* IPD */
        SELECT accountcode,
               $ageExprIPD AS age,
               (IFNULL(debit, 0) - 
                    CASE
                        WHEN billdate IS NOT NULL AND billdate != '' 
                         AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                         AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= '$target_ym'
                        THEN IFNULL(follow_money, 0)

                        WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                         AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                         AND mobile LIKE '%/%' AND CHAR_LENGTH(mobile) >= 10
                         AND CONCAT(
                                 (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                                 '-',
                                 SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                             ) <= '$target_ym'
                        THEN IFNULL(follow_money, 0)

                        ELSE 0
                    END
                    - IFNULL((
                        SELECT SUM(ph.pay_amount)
                        FROM imr_tb_payment_history ph
                        WHERE ph.ref_vn_an = an
                          AND ph.patient_type = 'IPD'
                          AND ph.bill_date IS NOT NULL
                          AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= '$target_ym'
                      ), 0)
               ) AS debit
        FROM imr_tb_debtor_rights_ipd
        WHERE IFNULL(debit, 0) > 0
          AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$target_ym'
          AND ($receiptWhere)
    ) AS combined
    WHERE debit > 0
    GROUP BY accountcode
    ";

    $res_batch = $conn->query($sql_batch);
    $aging = [];
    if ($res_batch) {
        while ($r = $res_batch->fetch_assoc()) {
            $aging[$r['accountcode']] = [
                'count_lt_3'  => (int)$r['count_lt_3'],
                'sum_lt_3'    => (float)$r['sum_lt_3'],
                'count_3_12'  => (int)$r['count_3_12'],
                'sum_3_12'    => (float)$r['sum_3_12'],
                'count_gt_12' => (int)$r['count_gt_12'],
                'sum_gt_12'   => (float)$r['sum_gt_12'],
                'count_total' => (int)$r['count_total'],
                'sum_total'   => (float)$r['sum_total']
            ];
        }
    }

    // ฟังก์ชั่นคำนวณอายุหนี้จากวันที่รับบริการ (dd/mm/YYYY)
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

    $add_bucket_func = function(&$target, $age, $debit, $count = 1) {
        if ($age < 3) {
            $target['count_lt_3'] += $count;
            $target['sum_lt_3']   += $debit;
        } elseif ($age <= 12) {
            $target['count_3_12'] += $count;
            $target['sum_3_12']   += $debit;
        } else {
            $target['count_gt_12'] += $count;
            $target['sum_gt_12']   += $debit;
        }
        $target['count_total'] += $count;
        $target['sum_total']   += $debit;
    };

    $deduct_bucket_func = function(&$target, $age, $debit, $count = 1) {
        if ($age < 3) {
            $target['count_lt_3'] = max(0, $target['count_lt_3'] - $count);
            $target['sum_lt_3']   = max(0.0, $target['sum_lt_3'] - $debit);
        } elseif ($age <= 12) {
            $target['count_3_12'] = max(0, $target['count_3_12'] - $count);
            $target['sum_3_12']   = max(0.0, $target['sum_3_12'] - $debit);
        } else {
            $target['count_gt_12'] = max(0, $target['count_gt_12'] - $count);
            $target['sum_gt_12']   = max(0.0, $target['sum_gt_12'] - $debit);
        }
        $target['count_total'] = max(0, $target['count_total'] - $count);
        $target['sum_total']   = max(0.0, $target['sum_total'] - $debit);
    };

    // 🌟 ประมวลผลการตัดโอนบัญชีแม่-ลูก CR & SSS แบบสะสมทุกงวดถึงงวดปัจจุบัน (Cumulative Splitting Engine)
    $cr_config = get_active_cr_config($conn);
    $sss_config = get_active_sss_config($conn);
    $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';

    $q_months = mysqli_query($conn, "
        SELECT DISTINCT monthtxt 
        FROM (
            SELECT monthtxt FROM imr_tb_debtor_rights_opd WHERE IFNULL(debit,0) > 0
            UNION 
            SELECT monthtxt FROM imr_tb_debtor_rights_ipd WHERE IFNULL(debit,0) > 0
        ) t
        WHERE CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$target_ym'
        ORDER BY SUBSTRING_INDEX(monthtxt,'-',-1) ASC, CAST(SUBSTRING_INDEX(monthtxt,'-',1) AS UNSIGNED) ASC
    ");

    $cum = [
        'cr_opd'  => ['transfers' => [], 'visits' => []],
        'cr_ipd'  => ['transfers' => [], 'visits' => []],
        'sss_opd' => ['transfers' => [], 'visits' => []],
        'sss_ipd' => ['transfers' => [], 'visits' => []],
    ];

    if ($q_months) {
        $auto_split_cr_opd  = is_cr_auto_split_enabled($cr_config, 'OPD');
        $auto_split_cr_ipd  = is_cr_auto_split_enabled($cr_config, 'IPD');
        $auto_split_sss_opd = is_sss_auto_split_enabled($sss_config, 'OPD');
        $auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config, 'IPD');

        while ($rm = mysqli_fetch_assoc($q_months)) {
            $m_txt = $rm['monthtxt'];

            if ($auto_split_cr_opd && is_cr_effective_for_month($cr_config, $m_txt)) {
                $c = get_cr_splitting_summary_for_month($conn, $conn2 ?? null, $m_txt, 'OPD', $cr_config, $my_hospcode);
                if ($c && !empty($c['transfers_by_account'])) {
                    foreach ($c['transfers_by_account'] as $acc => $tr) {
                        if (!isset($cum['cr_opd']['transfers'][$acc])) {
                            $cum['cr_opd']['transfers'][$acc] = ['transfer_out' => 0.0, 'full_cases' => 0];
                        }
                        $cum['cr_opd']['transfers'][$acc]['transfer_out'] += (float)($tr['transfer_out'] ?? 0);
                        $cum['cr_opd']['transfers'][$acc]['full_cases'] += (int)($tr['full_transfer_cases'] ?? 0);
                    }
                }
                if (!empty($c['cr_visits'])) {
                    $cum['cr_opd']['visits'] = $cum['cr_opd']['visits'] + $c['cr_visits'];
                }
            }

            if ($auto_split_cr_ipd && is_cr_effective_for_month($cr_config, $m_txt)) {
                $c = get_cr_splitting_summary_for_month($conn, $conn2 ?? null, $m_txt, 'IPD', $cr_config, $my_hospcode);
                if ($c && !empty($c['transfers_by_account'])) {
                    foreach ($c['transfers_by_account'] as $acc => $tr) {
                        if (!isset($cum['cr_ipd']['transfers'][$acc])) {
                            $cum['cr_ipd']['transfers'][$acc] = ['transfer_out' => 0.0, 'full_cases' => 0];
                        }
                        $cum['cr_ipd']['transfers'][$acc]['transfer_out'] += (float)($tr['transfer_out'] ?? 0);
                        $cum['cr_ipd']['transfers'][$acc]['full_cases'] += (int)($tr['full_transfer_cases'] ?? 0);
                    }
                }
                if (!empty($c['cr_visits'])) {
                    $cum['cr_ipd']['visits'] = $cum['cr_ipd']['visits'] + $c['cr_visits'];
                }
            }

            if ($auto_split_sss_opd && is_sss_effective_for_month($sss_config, $m_txt)) {
                $s = get_sss_splitting_summary_for_month($conn, $conn2 ?? null, $m_txt, 'OPD', $sss_config, $my_hospcode);
                if ($s && !empty($s['transfers_by_account'])) {
                    foreach ($s['transfers_by_account'] as $acc => $tr) {
                        if (!isset($cum['sss_opd']['transfers'][$acc])) {
                            $cum['sss_opd']['transfers'][$acc] = ['transfer_out' => 0.0, 'full_cases' => 0];
                        }
                        $cum['sss_opd']['transfers'][$acc]['transfer_out'] += (float)($tr['transfer_out'] ?? 0);
                        $cum['sss_opd']['transfers'][$acc]['full_cases'] += (int)($tr['full_transfer_cases'] ?? 0);
                    }
                }
                if (!empty($s['sss_visits'])) {
                    $cum['sss_opd']['visits'] = $cum['sss_opd']['visits'] + $s['sss_visits'];
                }
            }

            if ($auto_split_sss_ipd && is_sss_effective_for_month($sss_config, $m_txt)) {
                $s = get_sss_splitting_summary_for_month($conn, $conn2 ?? null, $m_txt, 'IPD', $sss_config, $my_hospcode);
                if ($s && !empty($s['transfers_by_account'])) {
                    foreach ($s['transfers_by_account'] as $acc => $tr) {
                        if (!isset($cum['sss_ipd']['transfers'][$acc])) {
                            $cum['sss_ipd']['transfers'][$acc] = ['transfer_out' => 0.0, 'full_cases' => 0];
                        }
                        $cum['sss_ipd']['transfers'][$acc]['transfer_out'] += (float)($tr['transfer_out'] ?? 0);
                        $cum['sss_ipd']['transfers'][$acc]['full_cases'] += (int)($tr['full_transfer_cases'] ?? 0);
                    }
                }
                if (!empty($s['sss_visits'])) {
                    $cum['sss_ipd']['visits'] = $cum['sss_ipd']['visits'] + $s['sss_visits'];
                }
            }
        }
    }

    // ประมวลผลยอดโอนเข้าผังลูก (Child Accounts)
    $process_child_func = function(&$aging, $visits_all, $child_acc, $visit_type, $scheme) use ($conn, $target_ym, $last_day_date, $calc_age_func, $add_bucket_func) {
        if (empty($visits_all)) return;
        if (!isset($aging[$child_acc])) {
            $aging[$child_acc] = ['count_lt_3'=>0,'sum_lt_3'=>0.0,'count_3_12'=>0,'sum_3_12'=>0.0,'count_gt_12'=>0,'sum_gt_12'=>0.0,'count_total'=>0,'sum_total'=>0.0];
        }

        $pt_table = ($visit_type === 'OPD') ? 'imr_tb_debtor_rights_opd' : 'imr_tb_debtor_rights_ipd';
        $bd_table = ($scheme === 'CR') ? 'imr_tb_debtor_cr_breakdown' : 'imr_tb_debtor_sss_breakdown';
        $pk = ($visit_type === 'OPD') ? 'vn' : 'an';
        $date_col = ($visit_type === 'OPD') ? 'vstdate' : 'dchdate';

        $q_seen = $conn->query("SELECT $pk AS id FROM $pt_table WHERE accountcode = '$child_acc' AND CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1),'-',LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) <= '$target_ym'");
        $raw_seen = [];
        if ($q_seen) {
            while ($r = $q_seen->fetch_assoc()) $raw_seen[$r['id']] = true;
        }

        $transferred_ids = [];
        foreach ($visits_all as $v_id => $vinfo) {
            if (!empty($vinfo['is_kidney'])) continue;
            if (isset($raw_seen[$v_id])) continue;
            if (($vinfo['origin_accountcode'] ?? '') === $child_acc) continue;
            $transferred_ids[] = $v_id;
        }

        if (!empty($transferred_ids)) {
            $chunks = array_chunk($transferred_ids, 500);
            foreach ($chunks as $chunk) {
                $ids_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $chunk)) . "'";
                $sql_in = "
                    SELECT o.$pk AS id, o.$date_col AS srv_date, o.bill, o.billdate,
                           b.billdate as bd_billdate, b.compensated as bd_comp, b.bill as bd_bill, b.settle_status
                    FROM $pt_table o
                    LEFT JOIN (
                        SELECT vn, MAX(billdate) as billdate, SUM(compensated) as compensated, MAX(bill) as bill, MAX(settle_status) as settle_status
                        FROM $bd_table
                        WHERE visit_type = '$visit_type'
                        GROUP BY vn
                    ) b ON b.vn = o.$pk
                    WHERE o.$pk IN ($ids_str)
                ";
                $res_in = $conn->query($sql_in);
                if ($res_in) {
                    while ($r_in = $res_in->fetch_assoc()) {
                        $v_id = $r_in['id'];
                        $vinfo = $visits_all[$v_id];
                        $amount_key = ($scheme === 'CR') ? 'cr_amount' : 'sss_amount';
                        $base_debit = (float)(($vinfo['transfer_out'] ?? 0) > 0 ? $vinfo['transfer_out'] : ($vinfo[$amount_key] ?? 0));

                        $bill_d  = !empty($r_in['bd_billdate']) ? $r_in['bd_billdate'] : '';
                        $comp    = !empty($r_in['bd_comp']) ? (float)$r_in['bd_comp'] : 0.0;
                        $status  = $r_in['settle_status'] ?? '';
                        $bill_no = !empty($r_in['bd_bill']) ? trim($r_in['bd_bill']) : '';

                        $is_paid = false;
                        $bym = '';
                        if (!empty($bill_d) && $bill_d != '-' && strlen($bill_d) >= 10) {
                            $by = (int)substr($bill_d, 6, 4) - 543;
                            $bm = (int)substr($bill_d, 3, 2);
                            $bym = sprintf('%04d-%02d', $by, $bm);
                            if ($bym <= $target_ym && ($status === 'SETTLED' || (!empty($bill_no) && $bill_no !== '-' && $bill_no !== '0000/0000') || ($comp > 0 && ($base_debit - $comp) <= 0.01))) {
                                $is_paid = true;
                            }
                        }

                        $net_debit = max(0, $base_debit - ($comp > 0 && !empty($bym) && $bym <= $target_ym ? $comp : 0));
                        if ($net_debit > 0.01 && !$is_paid) {
                            $age = $calc_age_func($r_in['srv_date'], $last_day_date);
                            $add_bucket_func($aging[$child_acc], $age, $net_debit, 1);
                        }
                    }
                }
            }
        }
    };

    // หักยอดโอนออกจากผังแม่ (Mother Accounts) แบบทดลดยอดต่อเนื่องทุกช่วงอายุ (Cascading Bucket Deduction)
    $deduct_mother_cum_func = function(&$aging, $transfers) {
        if (empty($transfers)) return;
        foreach ($transfers as $acc => $tr) {
            if (!isset($aging[$acc])) continue;
            $rem_debit = (float)$tr['transfer_out'];
            $rem_count = (int)$tr['full_cases'];

            $aging[$acc]['sum_total'] = max(0.0, $aging[$acc]['sum_total'] - $rem_debit);
            $aging[$acc]['count_total'] = max(0, $aging[$acc]['count_total'] - $rem_count);

            $d_debit = min($aging[$acc]['sum_lt_3'], $rem_debit);
            $d_count = min($aging[$acc]['count_lt_3'], $rem_count);
            $aging[$acc]['sum_lt_3'] -= $d_debit;
            $aging[$acc]['count_lt_3'] -= $d_count;
            $rem_debit -= $d_debit;
            $rem_count -= $d_count;

            if ($rem_debit > 0.001 || $rem_count > 0) {
                $d_debit = min($aging[$acc]['sum_3_12'], $rem_debit);
                $d_count = min($aging[$acc]['count_3_12'], $rem_count);
                $aging[$acc]['sum_3_12'] -= $d_debit;
                $aging[$acc]['count_3_12'] -= $d_count;
                $rem_debit -= $d_debit;
                $rem_count -= $d_count;
            }

            if ($rem_debit > 0.001 || $rem_count > 0) {
                $d_debit = min($aging[$acc]['sum_gt_12'], $rem_debit);
                $d_count = min($aging[$acc]['count_gt_12'], $rem_count);
                $aging[$acc]['sum_gt_12'] -= $d_debit;
                $aging[$acc]['count_gt_12'] -= $d_count;
            }
        }
    };

    // 1. หักยอดโอนสะสมออกจากผังแม่
    $deduct_mother_cum_func($aging, $cum['cr_opd']['transfers']);
    $deduct_mother_cum_func($aging, $cum['cr_ipd']['transfers']);
    $deduct_mother_cum_func($aging, $cum['sss_opd']['transfers']);
    $deduct_mother_cum_func($aging, $cum['sss_ipd']['transfers']);

    // 2. กระจายเคสโอนสะสมเข้าผังลูกตามอายุบริการจริง
    $process_child_func($aging, $cum['cr_opd']['visits'], '1102050101.216', 'OPD', 'CR');
    $process_child_func($aging, $cum['cr_ipd']['visits'], '1102050101.217', 'IPD', 'CR');
    $process_child_func($aging, $cum['sss_opd']['visits'], '1102050101.309', 'OPD', 'SSS');
    $process_child_func($aging, $cum['sss_ipd']['visits'], '1102050101.310', 'IPD', 'SSS');

    // ----------------------------------------------------------------------
    // STEP 1: แยกข้อมูลใส่ตะกร้า (Arrays) จากทะเบียนคุมลูกหนี้
    // ----------------------------------------------------------------------
    $rows101 = []; 
    $rows102 = []; 

    $sql_accounts = "
        SELECT DISTINCT u.accountcode, COALESCE(tc.Name, 'ไม่พบชื่อบัญชี') AS accountname
        FROM (
            SELECT code AS accountcode FROM imr_tb_debtor_result
            UNION
            SELECT accountcode FROM imr_tb_debtor_rights_opd
            UNION
            SELECT accountcode FROM imr_tb_debtor_rights_ipd
        ) u
        LEFT JOIN tb_code tc ON u.accountcode = tc.Code
        WHERE u.accountcode LIKE '1102050101.%' OR u.accountcode LIKE '1102050102.%'
        ORDER BY u.accountcode ASC
    ";
    $res_accs = mysqli_query($conn, $sql_accounts);

    if ($res_accs && mysqli_num_rows($res_accs) > 0) {
        while ($acc_row = mysqli_fetch_assoc($res_accs)) {
            $accCode = $acc_row['accountcode'];
            $codeGroup = substr($accCode, 7, 3);

            $ag = $aging[$accCode] ?? [
                'count_lt_3'  => 0,
                'sum_lt_3'    => 0.0,
                'count_3_12'  => 0,
                'sum_3_12'    => 0.0,
                'count_gt_12' => 0,
                'sum_gt_12'   => 0.0,
                'count_total' => 0,
                'sum_total'   => 0.0
            ];

            $row_data = [
                'accountcode' => $accCode,
                'accountname' => $acc_row['accountname'],
                'count_lt_3'  => $ag['count_lt_3'],
                'sum_lt_3'    => $ag['sum_lt_3'],
                'count_3_12'  => $ag['count_3_12'],
                'sum_3_12'    => $ag['sum_3_12'],
                'count_gt_12' => $ag['count_gt_12'],
                'sum_gt_12'   => $ag['sum_gt_12'],
                'count_total' => $ag['count_total'],
                'sum_total'   => $ag['sum_total']
            ];

            if ($codeGroup === '101') {
                $rows101[] = $row_data; 
            } elseif ($codeGroup === '102') {
                $rows102[] = $row_data;
            }
        }
    }

    // ----------------------------------------------------------------------
    // ฟังก์ชั่นช่วยแสดงแถวตาราง (Render Function)
    // ----------------------------------------------------------------------
    if (!function_exists('renderRows')) {
    function renderRows($rows, $groupName, $month) {
        if (empty($rows)) return ['c1'=>0, 's1'=>0, 'c2'=>0, 's2'=>0, 'c3'=>0, 's3'=>0, 'c_tot'=>0, 's_tot'=>0];

        $sum_c1 = 0; $sum_s1 = 0;
        $sum_c2 = 0; $sum_s2 = 0;
        $sum_c3 = 0; $sum_s3 = 0;
        $sum_c_tot = 0; $sum_s_tot = 0;

        echo '<tr><td colspan="10" style="font-weight:bold; padding: 6px; text-align: left;">' . $groupName . '</td></tr>';

        foreach ($rows as $row) {
            $sum_c1 += $row["count_lt_3"]; $sum_s1 += $row["sum_lt_3"];
            $sum_c2 += $row["count_3_12"]; $sum_s2 += $row["sum_3_12"];
            $sum_c3 += $row["count_gt_12"]; $sum_s3 += $row["sum_gt_12"];
            $sum_c_tot += $row["count_total"]; $sum_s_tot += $row["sum_total"];

            // สร้างลิงก์ให้คลิกได้ พร้อมฝังข้อมูล (data-*)
            $acc = $row["accountcode"];
            $accName = $row["accountname"];
            
            $link = function($val, $range) use ($acc, $accName, $month) {
                if($val == 0) return "-";
                return '<a href="javascript:void(0);" class="text-primary fw-bold view-detail" 
                        data-acc="'.$acc.'" data-accname="'.$accName.'" data-range="'.$range.'" data-month="'.$month.'">' 
                        . number_format($val, 0) . '</a>';
            };

            echo '<tr data-code="'.$acc.'">
            <td style="text-align: center;">' . $acc . '</td>
            <td style="text-align: left;">' . $accName . '</td>
            <td style="text-align: right;">' . $link($row["count_lt_3"], 'lt_3') . '</td>
            <td style="text-align: right;">' . number_format($row["sum_lt_3"], 2) . '</td>
            <td style="text-align: right;">' . $link($row["count_3_12"], '3_12') . '</td>
            <td style="text-align: right;">' . number_format($row["sum_3_12"], 2) . '</td>
            <td style="text-align: right;">' . $link($row["count_gt_12"], 'gt_12') . '</td>
            <td style="text-align: right;">' . number_format($row["sum_gt_12"], 2) . '</td>
            <td style="text-align: right;">' . $link($row["count_total"], 'total') . '</td>
            <td style="text-align: right;">' . number_format($row["sum_total"], 2) . '</td>
            </tr>';
        }

        // --- ส่วนที่ทำยอดรวมกลุ่ม
        echo '<tr style="background-color: #fcfcfc;  border-top: 1px;">
        <td colspan="2" style="text-align: center;">รวม ' . $groupName . '</td>
        <td style="text-align: right;">'.number_format($sum_c1,0).'</td>
        <td style="text-align: right;">'.number_format($sum_s1,2).'</td>
        <td style="text-align: right;">'.number_format($sum_c2,0).'</td>
        <td style="text-align: right;">'.number_format($sum_s2,2).'</td>
        <td style="text-align: right;">'.number_format($sum_c3,0).'</td>
        <td style="text-align: right;">'.number_format($sum_s3,2).'</td>
        <td style="text-align: right;">'.number_format($sum_c_tot,0).'</td>
        <td style="text-align: right;">'.number_format($sum_s_tot,2).'</td>
        </tr>';

        return [
            'c1' => $sum_c1, 's1' => $sum_s1, 
            'c2' => $sum_c2, 's2' => $sum_s2, 
            'c3' => $sum_c3, 's3' => $sum_s3, 
            'c_tot' => $sum_c_tot, 's_tot' => $sum_s_tot
        ];
    }
    }



    // ----------------------------------------------------------------------
    // STEP 2: เริ่มสร้างตารางหลัก (Main Table Output)
    // ----------------------------------------------------------------------
    ?>
    <div id="printable-area">
        <table border="1" style="width:100%; border-collapse: collapse; border: 1px solid #ddd;">
            <thead>
                <tr>
                    <th colspan="10" style="height: 50px;text-align: center;font-size: 16px; background-color: #fff;border: none;"> 
                        <?php echo isset($hospital) ? $hospital : ''; ?> ทะเบียนคุมอายุลูกหนี้ ปีงบประมาณ<span id="vtxt3_top"><?php $result1 = DateThaiM($gmm); echo $result1['fiscalYear']; ?></span><br>
                        เดือน <span id="vtxt0_top"><?php echo $result1['fullDate']; ?></span>
                    </th>
                </tr>

                <tr style="background-color: #f9f9f9;">
                    <th rowspan="3" style="text-align:center; border:1px solid #ddd;">รหัสบัญชี</th>
                    <th rowspan="3" style="text-align:center; border:1px solid #ddd;">ชื่อบัญชี</th>
                    <th colspan="6" style="text-align:center; border:1px solid #ddd;">อายุลูกหนี้ ณ วันที่ <?php echo $last_day_of_month . " " . $result1['fullDate']; ?></th>
                    <th colspan="2" rowspan="2" style="text-align:center; border:1px solid #ddd;">รวม</th>
                </tr>
                <tr style="background-color: #f9f9f9;">
                    <th colspan="2" style="text-align:center; border:1px solid #ddd;">&lt; 3 เดือน</th>
                    <th colspan="2" style="text-align:center; border:1px solid #ddd;">&gt; 3 เดือน &lt; 12 เดือน</th>
                    <th colspan="2" style="text-align:center; border:1px solid #ddd;">&gt; 12 เดือน</th>
                </tr>
                <tr style="background-color: #f9f9f9;">
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (ราย)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (บาท)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (ราย)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (บาท)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (ราย)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (บาท)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (ราย)</th>
                    <th style="text-align:center; border:1px solid #ddd;">จำนวน (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($rows101) > 0 || count($rows102) > 0) {
                    
                    // บรรทัดนี้พี่น่าจะแก้แล้ว (ส่ง $month ไปด้วย)
                    $totals101 = renderRows($rows101, "ลูกหนี้การค้า - หน่วยงานภาครัฐ", $month);
                    
                    // บรรทัด 228 เจ้าปัญหา! น้องเติม , $month เข้าไปให้ตรงนี้แล้วครับ
                    $totals102 = renderRows($rows102, "ลูกหนี้การค้า - บุคคลภายนอก", $month);

                    // 3. แสดงยอดรวมทั้งสิ้น (Grand Total)
                    $g_c1 = $totals101['c1'] + $totals102['c1'];
                    $g_s1 = $totals101['s1'] + $totals102['s1'];
                    $g_c2 = $totals101['c2'] + $totals102['c2'];
                    $g_s2 = $totals101['s2'] + $totals102['s2'];
                    $g_c3 = $totals101['c3'] + $totals102['c3'];
                    $g_s3 = $totals101['s3'] + $totals102['s3'];
                    $g_c_tot = $totals101['c_tot'] + $totals102['c_tot'];
                    $g_s_tot = $totals101['s_tot'] + $totals102['s_tot'];

                    echo '<tr style="font-weight: bold; background-color: #f9f9f9;">
                    <td colspan="2" style="text-align: center; height: 34px;background-color: #f9f9f9;">รวมทั้งหมดทั้งสิ้น</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_c1,0).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_s1,2).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_c2,0).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_s2,2).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_c3,0).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_s3,2).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_c_tot,0).'</td>
                    <td style="text-align: right;background-color: #f9f9f9;">'.number_format($g_s_tot,2).'</td>
                    </tr>';

                } else {
                    echo '<tr><td colspan="10" style="text-align: center;">รอสรุปข้อมูลจากหน้ากระทบยอดลูกหนี้ก่อน</td></tr>';
                }


                ?>
            </tbody>
        </table>
    
    
        <br><br>

        <?php
        // ดึงข้อมูลชื่อคนเซ็นจากฐานข้อมูล
        $sql = "SELECT * FROM summary_responsibles ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        $data = $result->fetch_assoc();
        ?>

        <table class="signature-table" style="width:100%; border:none; margin-top: 30px;">
            <tr style="vertical-align: bottom;">
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">
                    <p style="margin: 2px;" >(<?php echo $data['name1'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo $data['position1'];?></p>
                    ผู้จัดทำรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ
                </td>
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">
                    <p style="margin: 2px;" >(<?php echo $data['name2'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo $data['position2'];?></p>
                    ผู้ตรวจสอบรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ
                </td>
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">                                    
                    <p style="margin: 2px;" >(<?php echo $data['name3'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo $data['position3'];?></p>
                    ผู้บันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี
                </td>
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">
                    <p style="margin: 2px;" >(<?php echo $data['name4'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo $data['position4'];?></p>
                    ผู้ตรวจสอบการบันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี
                </td>
            </tr>
            <tr>
                <td colspan="4" style="height: 35px; border: none;"></td>
            </tr>
            <tr style="vertical-align: bottom;">
                <td colspan="3" style="border: none;"></td>
                <td style="width: 25%; text-align: center; border: none; font-size: 15px;">
                    <p style="margin: 2px;" >(<?php echo $data['name5'];?>)</p>
                    <p style="margin: 2px;" >ตำแหน่ง <?php echo !empty($data['position5']) ? $data['position5'] : 'ผู้อำนวยการโรงพยาบาล';?></p>
                    ผู้อำนวยการโรงพยาบาล<br>หัวหน้าหน่วยงาน
                </td>
            </tr>
        </table>
    </div> 

    <div style="text-align: right; padding-right: 20px; margin-top: 40px; margin-bottom: 50px;">
        <button type="button" onclick="exportAgingToExcel()" class="btn-excel">
            📊 ส่งออก Excel
        </button>
        <button type="button" onclick="printReportEnhanced()" class="btn-print">
            🖨️ พิมพ์รายงาน
        </button>
    </div>

    <script>
    function exportAgingToExcel() {
        var printable = document.getElementById('printable-area');
        if (!printable) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบข้อมูล',
                    text: 'กรุณารอโหลดข้อมูลก่อนทำการส่งออก Excel',
                    confirmButtonText: 'ตกลง'
                });
            } else {
                alert('ไม่พบข้อมูลสำหรับส่งออก Excel');
            }
            return;
        }

        // โคลนเนื้อหาออกมาเพื่อจัดรูปแบบสำหรับ Excel โดยไม่กระทบหน้าจอจริง
        var clone = printable.cloneNode(true);

        // 1. ปลดลิงก์ <a> (view-detail) ให้เหลือแค่ข้อความ/ตัวเลขธรรมดา
        var links = clone.querySelectorAll('a');
        links.forEach(function(a) {
            var text = a.textContent.trim();
            if (a.parentNode) {
                a.parentNode.textContent = text;
            }
        });

        // 2. จัดระเบียบตารางหลัก
        var mainTable = clone.querySelector('table');
        if (mainTable) {
            mainTable.setAttribute('border', '1');
            mainTable.style.borderCollapse = 'collapse';
            mainTable.style.width = '100%';

            // กำหนดความกว้างคอลัมน์มาตรฐานสำหรับ Excel ผ่าน colgroup
            var colgroup = document.createElement('colgroup');
            colgroup.innerHTML = `
                <col width="140" style="mso-width-source:userset;mso-width-alt:4500;width:105pt">
                <col width="280" style="mso-width-source:userset;mso-width-alt:9000;width:210pt">
                <col width="100" style="mso-width-source:userset;mso-width-alt:3200;width:75pt">
                <col width="120" style="mso-width-source:userset;mso-width-alt:3800;width:90pt">
                <col width="100" style="mso-width-source:userset;mso-width-alt:3200;width:75pt">
                <col width="120" style="mso-width-source:userset;mso-width-alt:3800;width:90pt">
                <col width="100" style="mso-width-source:userset;mso-width-alt:3200;width:75pt">
                <col width="120" style="mso-width-source:userset;mso-width-alt:3800;width:90pt">
                <col width="100" style="mso-width-source:userset;mso-width-alt:3200;width:75pt">
                <col width="130" style="mso-width-source:userset;mso-width-alt:4200;width:98pt">
            `;
            mainTable.insertBefore(colgroup, mainTable.firstChild);

            // ปรับแต่ง Headers
            var ths = mainTable.querySelectorAll('th');
            ths.forEach(function(th) {
                th.style.fontFamily = "'TH Sarabun New', 'Sarabun', Tahoma, sans-serif";
                th.style.fontSize = '14pt';
                if (th.getAttribute('colspan') === '10') {
                    // หัวรายงานบรรทัดบนสุด
                    th.style.border = 'none';
                    th.style.backgroundColor = '#ffffff';
                    th.style.fontSize = '16pt';
                    th.style.fontWeight = 'bold';
                    th.style.textAlign = 'center';
                } else {
                    th.style.border = '0.5pt solid #000000';
                    th.style.backgroundColor = '#eaeaea';
                    th.style.fontWeight = 'bold';
                    th.style.textAlign = 'center';
                }
            });

            // ปรับแต่ง Data Rows ใน tbody
            var rows = mainTable.querySelectorAll('tbody tr');
            rows.forEach(function(tr) {
                var tds = tr.querySelectorAll('td');
                if (tds.length === 0) return;

                // กรณีหัวกลุ่ม (เช่น ลูกหนี้การค้า - หน่วยงานภาครัฐ)
                if (tds.length === 1 && tds[0].getAttribute('colspan') === '10') {
                    tds[0].style.border = '0.5pt solid #000000';
                    tds[0].style.backgroundColor = '#eef2f7';
                    tds[0].style.fontWeight = 'bold';
                    tds[0].style.fontSize = '14pt';
                    tds[0].style.padding = '6px';
                    tds[0].style.textAlign = 'left';
                    return;
                }

                // กรณีแถวรวมกลุ่ม หรือแถวรวมทั้งหมดทั้งสิ้น (td แรก colspan="2")
                if (tds[0].getAttribute('colspan') === '2') {
                    tds.forEach(function(td, idx) {
                        td.style.border = '0.5pt solid #000000';
                        td.style.padding = '5px';
                        td.style.fontSize = '13pt';
                        td.style.fontWeight = 'bold';
                        td.style.backgroundColor = '#f9f9f9';

                        if (idx === 0) {
                            td.style.textAlign = 'center';
                        } else {
                            var textVal = td.textContent.trim();
                            if (textVal === '-' || textVal === '') {
                                td.style.textAlign = 'center';
                            } else {
                                if (idx % 2 === 1) {
                                    // คอลัมน์จำนวนราย (จำนวนเต็ม)
                                    td.setAttribute('style', td.getAttribute('style') + "; mso-number-format:'\\#\\,\\#\\#0'; text-align: right;");
                                } else {
                                    // คอลัมน์จำนวนเงิน (ทศนิยม 2 ตำแหน่ง)
                                    td.setAttribute('style', td.getAttribute('style') + "; mso-number-format:'\\#\\,\\#\\#0\\.00'; text-align: right;");
                                }
                            }
                        }
                    });
                    return;
                }

                // กรณีแถวข้อมูลผังบัญชีปกติ (10 คอลัมน์)
                if (tds.length === 10) {
                    tds.forEach(function(td, idx) {
                        td.style.border = '0.5pt solid #000000';
                        td.style.padding = '4px';
                        td.style.fontSize = '13pt';

                        if (idx === 0) {
                            // รหัสบัญชี: บังคับ Text format (@) เพื่อคงรูปจุดทศนิยมและตัวเลขครบถ้วน
                            td.setAttribute('style', td.getAttribute('style') + "; mso-number-format:'\\@'; text-align: center;");
                        } else if (idx === 1) {
                            // ชื่อผังบัญชี
                            td.style.textAlign = 'left';
                        } else {
                            var val = td.textContent.trim();
                            if (val === '-' || val === '') {
                                td.style.textAlign = 'center';
                            } else if (idx % 2 === 0) {
                                // idx 2, 4, 6, 8 คือ จำนวน (ราย) -> Integer
                                td.setAttribute('style', td.getAttribute('style') + "; mso-number-format:'\\#\\,\\#\\#0'; text-align: right;");
                            } else {
                                // idx 3, 5, 7, 9 คือ จำนวน (บาท) -> Decimal 2 ตำแหน่ง
                                td.setAttribute('style', td.getAttribute('style') + "; mso-number-format:'\\#\\,\\#\\#0\\.00'; text-align: right;");
                            }
                        }
                    });
                }
            });

            // 3. ปรับแถบลายเซ็น 5 ท่านลงเป็นส่วนท้ายของตาราง (tfoot) เพื่อให้อยู่ในกรอบ 10 คอลัมน์อย่างสมบูรณ์แบบ
            var sigTable = clone.querySelector('.signature-table');
            if (sigTable) {
                var sigRows = sigTable.querySelectorAll('tr');
                if (sigRows.length >= 3) {
                    var r1Tds = sigRows[0].querySelectorAll('td');
                    var r3Tds = sigRows[2].querySelectorAll('td');

                    var p1 = r1Tds[0] ? r1Tds[0].innerHTML : '';
                    var p2 = r1Tds[1] ? r1Tds[1].innerHTML : '';
                    var p3 = r1Tds[2] ? r1Tds[2].innerHTML : '';
                    var p4 = r1Tds[3] ? r1Tds[3].innerHTML : '';
                    var p5 = (r3Tds.length >= 2 && r3Tds[1]) ? r3Tds[1].innerHTML : (r3Tds[0] ? r3Tds[0].innerHTML : '');

                    var tfoot = document.createElement('tfoot');
                    tfoot.innerHTML = `
                        <tr><td colspan="10" style="border:none; height:25px;"></td></tr>
                        <tr>
                            <td colspan="2" style="border:none; text-align:center; vertical-align:top; font-size:13pt; font-weight:bold; padding:4px;">${p1}</td>
                            <td colspan="3" style="border:none; text-align:center; vertical-align:top; font-size:13pt; font-weight:bold; padding:4px;">${p2}</td>
                            <td colspan="2" style="border:none; text-align:center; vertical-align:top; font-size:13pt; font-weight:bold; padding:4px;">${p3}</td>
                            <td colspan="3" style="border:none; text-align:center; vertical-align:top; font-size:13pt; font-weight:bold; padding:4px;">${p4}</td>
                        </tr>
                        <tr><td colspan="10" style="border:none; height:35px;"></td></tr>
                        <tr>
                            <td colspan="7" style="border:none;"></td>
                            <td colspan="3" style="border:none; text-align:center; vertical-align:top; font-size:13pt; font-weight:bold; padding:4px;">${p5}</td>
                        </tr>
                        <tr><td colspan="10" style="border:none; height:20px;"></td></tr>
                    `;
                    mainTable.appendChild(tfoot);
                    sigTable.remove();
                }
            }
        }

        // 4. ประกอบเอกสาร HTML รูปแบบ Excel Workbook
        var excelContent = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
              xmlns:x="urn:schemas-microsoft-com:office:excel" 
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>ทะเบียนคุมอายุลูกหนี้</x:Name>
                            <x:WorksheetOptions>
                                <x:DisplayGridlines/>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                body { 
                    font-family: 'TH Sarabun New', 'Sarabun', 'Cordia New', Tahoma, sans-serif; 
                    font-size: 14pt; 
                    color: #000;
                }
                table { 
                    border-collapse: collapse; 
                    width: 100%; 
                }
                th, td { 
                    font-family: 'TH Sarabun New', 'Sarabun', 'Cordia New', Tahoma, sans-serif; 
                }
            </style>
        </head>
        <body>
            ${clone.innerHTML}
        </body>
        </html>`;

        // 5. บันทึกประวัติการส่งออก (Audit Log)
        var monthTxt = '';
        var elMonth = document.getElementById('vtxt0_top');
        if (elMonth && elMonth.textContent.trim()) {
            monthTxt = elMonth.textContent.trim();
        } else {
            monthTxt = '<?php echo isset($result1["fullDate"]) ? $result1["fullDate"] : (isset($month) ? $month : ""); ?>';
        }

        if (typeof $ !== 'undefined' && $.post) {
            $.post('log_action_button.php', {
                module: 'หน้ารายการ: ทะบียนคุมอายุลูกหนี้.php',
                action_type: 'EXPORT',
                details: 'ผู้ใช้กดปุ่ม: 📊 ส่งออก Excel (ทะเบียนคุมอายุลูกหนี้ เดือน ' + monthTxt + ')'
            });
        }

        // 6. ดาวน์โหลดไฟล์ Excel พร้อม UTF-8 BOM
        var blob = new Blob(['\uFEFF' + excelContent], {
            type: 'application/vnd.ms-excel;charset=utf-8;'
        });

        var safeMonth = monthTxt.replace(/[/\\?%*:|"<>]/g, '-').trim();
        var filename = 'รายงานทะเบียนคุมอายุลูกหนี้_' + (safeMonth || 'report') + '.xls';

        if (navigator.msSaveBlob) {
            navigator.msSaveBlob(blob, filename);
        } else {
            var link = document.createElement('a');
            if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }
        }
    }

    function printReportEnhanced() {
        // 1. ดึงเนื้อหา HTML ที่เราเตรียมไว้ในกล่อง printable-area
        var printContents = document.getElementById('printable-area').innerHTML;
        
        // 2. เปิดหน้าต่างใหม่ขึ้นมา (Popup)
        var printWindow = window.open('', '', 'height=800,width=1200');
        
        // 3. เขียนโครงสร้าง HTML สำหรับการพิมพ์โดยเฉพาะลงไป
        printWindow.document.write('<html><head><title>พิมพ์รายงานทะเบียนคุมอายุลูกหนี้</title>');
        
        // --- CSS ขั้นเทพ สำหรับจัดหน้ารายงาน ---
        printWindow.document.write('<style>');
        printWindow.document.write(`
            /* ตั้งค่าฟอนต์มาตรฐานรายงานราชการ */
            @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');
            body {
                font-family: 'TH Sarabun New', 'Sarabun', Tahoma, sans-serif;
                font-size: 14pt; /* ขนาดตัวหนังสือตอนปริ้น */
                color: #000;
                line-height: 1.2;
            }

            /* ตั้งค่ากระดาษ แนวนอน และขอบกระดาษ */
            @media print {
                @page {
                    size: landscape; 
                    margin: 10mm; /* ขอบกระดาษ 1.5 ซม. */
                }
            }

            /* จัดการหัวตารางหลัก */
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 20px; 
            }
            /* เส้นขอบตาราง สีดำทึบ 1px */
            th, td { 
                border: 1px solid #000 !important; 
                padding: 3px 2px;
                vertical-align: middle;
            }
            /* สีพื้นหลังหัวตาราง ให้ปริ้นติดออกมาด้วย */
            th { 
                background-color: #f0f0f0 !important; 
                -webkit-print-color-adjust: exact; 
                color-adjust: exact;
                text-align: center;
                font-weight: bold;
                font-size: 14pt;
            }

            /* --- เทคนิคจัดการการขึ้นหน้าใหม่ (Page Break) --- */
            
            /* 1. หัวตาราง (thead) ให้พยายามโชว์ซ้ำเมื่อขึ้นหน้าใหม่ (บางเบราว์เซอร์รองรับ) */
            thead { display: table-header-group; }
            
            /* 2. แถวข้อมูล (tr) ห้ามขาดครึ่งกลางบรรทัดเมื่อสิ้นสุดหน้ากระดาษ */
            tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* --- จัดการตารางลายเซ็น --- */
            .signature-table {
                margin-top: 30px;
                /* สำคัญมาก! ห้ามตารางลายเซ็นถูกตัดแบ่งไปอยู่คนละหน้าเด็ดขาด */
                page-break-inside: avoid;
                break-inside: avoid;
                border: none !important;
            }
            .signature-table td { 
                border: none !important; /* ลายเซ็นไม่ต้องมีเส้นขอบ */
                text-align: center; 
                vertical-align: top;
                padding: 5px;
                font-size: 16pt;
                font-weight: bold;
            }
            /* พื้นที่ว่างสำหรับเซ็นชื่อ */
            .signature-space {
                height: 30px; 
            }
            
            /* ซ่อนลิงก์สีน้ำเงินตอนปริ้น ให้เป็นตัวดำปกติ */
            a { text-decoration: none; color: #000 !important; }
            
            /* ปรับขนาดหัวรายงาน */
            h2, h3 { margin: 5px 0; text-align: center; }

        `);
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        
        // 4. ยัดเนื้อหาตารางลงไป
        printWindow.document.write(printContents);
        
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        // 5. รอโหลดเสร็จแล้วสั่งปริ้น
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
             printWindow.close(); 
        }, 800); // เพิ่มเวลาหน่วงนิดนึงเผื่อโหลดฟอนต์
    }
    </script>

    <style>
        /* CSS สำหรับปุ่มในหน้าเว็บปกติ (ไม่เกี่ยวกับการปริ้น) */
        .btn-excel {
            background-color: #198754; 
            color: #fff; 
            padding: 10px 25px; 
            border: none; 
            border-radius: 20px; 
            font-size: 16px; 
            cursor: pointer; 
            font-weight: bold; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: 0.3s;
            margin-right: 10px;
        }
        .btn-excel:hover {
            background-color: #157347;
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }
        .btn-print {
            background-color: #0000a0; 
            color: #fff; 
            padding: 10px 25px; 
            border: none; 
            border-radius: 20px; 
            font-size: 16px; 
            cursor: pointer; 
            font-weight: bold; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        .btn-print:hover {
            background-color: #000080;
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }
        /* ซ่อนเส้นขอบตารางลายเซ็นในหน้าจอปกติด้วย */
        .signature-table td { border: none !important; }
    </style>

<?php
} // ปิดปีกกาของ if (isset($_POST['month']))
?>