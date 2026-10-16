<?php
/**
 * Rehab & Medical Device Compensation Tracker Plugin
 * API: Executive Statistics, KPIs, Aging & Chart Engine
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/plugin_config.php';
rehab_release_session();

// Prepare Filters
$where = "1=1";
$params = [];

$fiscal_year = isset($_REQUEST['fiscal_year']) ? trim($_REQUEST['fiscal_year']) : '';
$start_date  = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : '';
$end_date    = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : '';
$fund_type   = isset($_REQUEST['fund_type']) ? trim($_REQUEST['fund_type']) : 'ALL';

if (!empty($fiscal_year) && $fiscal_year !== 'ALL') {
    // Thai Fiscal Year: Oct 1 of (Y-1) to Sep 30 of Y
    // E.g. 2568 -> Oct 1, 2024 to Sep 30, 2025
    $ce_year = intval($fiscal_year) - 543;
    $fy_start = ($ce_year - 1) . "-10-01";
    $fy_end   = $ce_year . "-09-30";
    $where .= " AND vstdate BETWEEN '$fy_start' AND '$fy_end'";
} elseif (!empty($start_date) && !empty($end_date)) {
    $s = mysqli_real_escape_string($conn, $start_date);
    $e = mysqli_real_escape_string($conn, $end_date);
    $where .= " AND vstdate BETWEEN '$s' AND '$e'";
}

if (!empty($fund_type) && $fund_type !== 'ALL') {
    $f = mysqli_real_escape_string($conn, $fund_type);
    if ($f === 'UC' || $f === 'DISABILITY_74') {
        $where .= " AND (fund_type = 'UC' OR pttype = '74')";
    } else {
        $where .= " AND fund_type = '$f'";
    }
}

$dept_filter = !empty($_REQUEST['department_group']) ? $_REQUEST['department_group'] : (!empty($_REQUEST['department']) ? $_REQUEST['department'] : '');
if (!empty($dept_filter) && $dept_filter !== 'ALL') {
    $dg = mysqli_real_escape_string($conn, $dept_filter);
    $where .= " AND department_group = '$dg'";
}

// Check for 5-Year Executive Multi-Department Trend Mode (Slide 1)
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] === '5years_trend') {
    // Dynamic determination of 5 target years based on selected fiscal_year
    $cur_m = (int)date('n');
    $cur_y = (int)date('Y');
    $cur_be = $cur_y + 543;
    $default_fy = ($cur_m >= 10) ? ($cur_be + 1) : $cur_be; // 2570

    $req_fy = !empty($_REQUEST['fiscal_year']) && $_REQUEST['fiscal_year'] !== 'ALL' ? intval($_REQUEST['fiscal_year']) : $default_fy;
    if ($req_fy < 2560 || $req_fy > 2600) {
        $req_fy = $default_fy;
    }

    $end_year = $req_fy;
    $start_year = $end_year - 4;

    $cache_file = __DIR__ . "/rehab_5years_cache_{$end_year}.json";
    if ($end_year === 2569 && !file_exists($cache_file) && file_exists(__DIR__ . '/rehab_5years_cache.json')) {
        $cache_file = __DIR__ . '/rehab_5years_cache.json';
    }

    $need_refresh = isset($_REQUEST['refresh']) && intval($_REQUEST['refresh']) === 1;
    
    if (!$need_refresh && file_exists($cache_file)) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
        if ($cached_data && isset($cached_data['years'])) {
            echo json_encode([
                'status' => 'success',
                'source' => 'cache',
                'target_year' => $end_year,
                'data' => $cached_data
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if (!isset($conn2) || !$conn2) {
        if (file_exists($cache_file)) {
            $cached_data = json_decode(file_get_contents($cache_file), true);
            echo json_encode([
                'status' => 'success',
                'source' => 'cache_fallback',
                'target_year' => $end_year,
                'data' => $cached_data
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (file_exists(__DIR__ . '/rehab_5years_cache.json')) {
            $cached_data = json_decode(file_get_contents(__DIR__ . '/rehab_5years_cache.json'), true);
            echo json_encode([
                'status' => 'success',
                'source' => 'cache_fallback_default',
                'target_year' => $end_year,
                'data' => $cached_data
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['status' => 'error', 'message' => 'HOSxP connection not available'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $years = [];
    for ($y = $start_year; $y <= $end_year; $y++) {
        $ce = $y - 543;
        $s_date = ($ce - 1) . '-10-01';
        $e_date = $ce . '-09-30';
        $years[(string)$y] = [$s_date, $e_date];
    }

    $all_data = [
        'years' => array_keys($years),
        'dept_revenue' => [],
        'dept_patients' => [],
        'dept_orders' => [],
        'total_devices' => [],
        'total_devices_patients' => [],
        'total_devices_orders' => [],
        'physio_service' => [],
        'physio_opd_pts' => [],
        'physio_ipd_pts' => [],
        'physio_by_dept' => []
    ];

    $dept_groups = ['กายภาพบำบัด', 'ทันตกรรม', 'ผู้ป่วยนอก (OPD)', 'อุบัติเหตุและฉุกเฉิน (ER)', 'ผู้ป่วยใน (IPD)', 'แพทย์แผนไทย'];
    foreach ($dept_groups as $dg) {
        $all_data['dept_revenue'][$dg] = [];
        $all_data['dept_patients'][$dg] = [];
        $all_data['dept_orders'][$dg] = [];
        foreach (array_keys($years) as $y) {
            $all_data['dept_revenue'][$dg][$y] = 0;
            $all_data['dept_patients'][$dg][$y] = 0;
            $all_data['dept_orders'][$dg][$y] = 0;
        }
    }

    foreach ($years as $fy => $range) {
        $s = $range[0]; $e = $range[1];
        
        $sql_dept = "SELECT 
            CASE 
                WHEN o.dep_code = '042' OR ov.main_dep = '042' THEN 'กายภาพบำบัด'
                WHEN o.dep_code = '005' OR ov.main_dep = '005' THEN 'ทันตกรรม'
                WHEN o.dep_code = '041' OR ov.main_dep = '041' THEN 'แพทย์แผนไทย'
                WHEN o.dep_code = '011' OR ov.main_dep = '011' THEN 'อุบัติเหตุและฉุกเฉิน (ER)'
                WHEN o.dep_code IN ('009','015') OR ov.main_dep IN ('009','015') OR (o.an IS NOT NULL AND o.an != '') THEN 'ผู้ป่วยใน (IPD)'
                WHEN o.dep_code IN ('006','010','014','019','020','021','026','030','034','036','037','040','048','050') 
                     OR ov.main_dep IN ('006','010','014','019','020','021','026','030','034','036','037','040','048','050') THEN 'ผู้ป่วยนอก (OPD)'
                ELSE 'อื่นๆ / แผนกสนับสนุน'
            END AS department_group,
            COUNT(DISTINCT o.hn) as pts,
            COUNT(*) as order_count,
            SUM(COALESCE(o.sum_price, 0)) as total_sum_price
        FROM opitemrece o
        INNER JOIN nondrugitems nd ON o.icode = nd.icode
        LEFT JOIN ovst ov ON o.vn = ov.vn
        WHERE o.vstdate BETWEEN '$s' AND '$e'
          AND (
              (
                  nd.nhso_adp_type_id = 2
                  OR (nd.income = '02' AND COALESCE(nd.nhso_adp_code, '') != 'DMISHD')
                  OR nd.nhso_adp_code LIKE '8%'
                  OR nd.nhso_adp_code IN ('8901','8902','8307','8601','8602','8608','8610','8701','8703','8704','8705','8706','8707','8708','8711','8526','8304','33501')
                  OR (
                      nd.income IN ('02','14')
                      AND (
                          nd.name LIKE '%กายอุปกรณ์%'
                          OR nd.name LIKE '%รถเข็น%'
                          OR nd.name LIKE '%เบาะ%'
                          OR nd.name LIKE '%ไม้เท้า%'
                          OR nd.name LIKE '%ไม้ค้ำ%'
                          OR nd.name LIKE '%Walker%'
                          OR nd.name LIKE '%Support%'
                          OR nd.name LIKE '%Sling%'
                          OR nd.name LIKE '%Collar%'
                          OR nd.name LIKE '%Splint%'
                      )
                  )
              )
              AND COALESCE(nd.nhso_adp_code, '') NOT IN ('1001','1002','1003','2001','2002','3002')
              AND nd.name NOT LIKE '%ออกซิเจน%'
              AND nd.name NOT LIKE '%Oxygen%'
              AND nd.name NOT LIKE '%ค่าบริการ%'
          )
        GROUP BY department_group";
        
        $res = mysqli_query($conn2, $sql_dept);
        $tot_dev = 0;
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $grp = $r['department_group'];
                if (isset($all_data['dept_revenue'][$grp])) {
                    $all_data['dept_revenue'][$grp][$fy] = floatval($r['total_sum_price']);
                    $all_data['dept_patients'][$grp][$fy] = intval($r['pts']);
                    $all_data['dept_orders'][$grp][$fy] = intval($r['order_count']);
                }
                $tot_dev += floatval($r['total_sum_price']);
            }
        }
        $all_data['total_devices'][$fy] = $tot_dev;

        // Total Hospital Devices Unique Patients & Orders
        $sql_tot_dev = "SELECT 
            COUNT(DISTINCT o.hn) as tot_pts,
            COUNT(*) as tot_orders
        FROM opitemrece o
        INNER JOIN nondrugitems nd ON o.icode = nd.icode
        WHERE o.vstdate BETWEEN '$s' AND '$e'
          AND (
              (
                  nd.nhso_adp_type_id = 2
                  OR (nd.income = '02' AND COALESCE(nd.nhso_adp_code, '') != 'DMISHD')
                  OR nd.nhso_adp_code LIKE '8%'
                  OR nd.nhso_adp_code IN ('8901','8902','8307','8601','8602','8608','8610','8701','8703','8704','8705','8706','8707','8708','8711','8526','8304','33501')
                  OR (
                      nd.income IN ('02','14')
                      AND (
                          nd.name LIKE '%กายอุปกรณ์%'
                          OR nd.name LIKE '%รถเข็น%'
                          OR nd.name LIKE '%เบาะ%'
                          OR nd.name LIKE '%ไม้เท้า%'
                          OR nd.name LIKE '%ไม้ค้ำ%'
                          OR nd.name LIKE '%Walker%'
                          OR nd.name LIKE '%Support%'
                          OR nd.name LIKE '%Sling%'
                          OR nd.name LIKE '%Collar%'
                          OR nd.name LIKE '%Splint%'
                      )
                  )
              )
              AND COALESCE(nd.nhso_adp_code, '') NOT IN ('1001','1002','1003','2001','2002','3002')
              AND nd.name NOT LIKE '%ออกซิเจน%'
              AND nd.name NOT LIKE '%Oxygen%'
              AND nd.name NOT LIKE '%ค่าบริการ%'
          )";
        $res_tot_dev = mysqli_query($conn2, $sql_tot_dev);
        if ($res_tot_dev && $r_td = mysqli_fetch_assoc($res_tot_dev)) {
            $all_data['total_devices_patients'][$fy] = intval($r_td['tot_pts']);
            $all_data['total_devices_orders'][$fy] = intval($r_td['tot_orders']);
        }

        // 2. Physio services (income = '14') by department
        $sql_p_dept = "SELECT 
            CASE 
                WHEN o.dep_code = '042' OR ov.main_dep = '042' THEN 'กายภาพบำบัด'
                WHEN o.dep_code = '005' OR ov.main_dep = '005' THEN 'ทันตกรรม'
                WHEN o.dep_code = '041' OR ov.main_dep = '041' THEN 'แพทย์แผนไทย'
                WHEN o.dep_code = '011' OR ov.main_dep = '011' THEN 'อุบัติเหตุและฉุกเฉิน (ER)'
                WHEN o.dep_code IN ('009','015') OR ov.main_dep IN ('009','015') OR (o.an IS NOT NULL AND o.an != '') THEN 'ผู้ป่วยใน (IPD)'
                WHEN o.dep_code IN ('006','010','014','019','020','021','026','030','034','036','037','040','048','050') 
                     OR ov.main_dep IN ('006','010','014','019','020','021','026','030','034','036','037','040','048','050') THEN 'ผู้ป่วยนอก (OPD)'
                ELSE 'อื่นๆ'
            END AS department_group,
            COUNT(DISTINCT o.hn) as pt_count,
            COUNT(DISTINCT CASE WHEN o.an IS NULL OR o.an = '' THEN o.vn ELSE o.an END) as visit_count,
            SUM(o.sum_price) as sum_price
        FROM opitemrece o
        LEFT JOIN ovst ov ON o.vn = ov.vn
        WHERE o.vstdate BETWEEN '$s' AND '$e'
          AND o.income = '14'
        GROUP BY department_group";
        $res_pd = mysqli_query($conn2, $sql_p_dept);
        if ($res_pd) {
            while ($rpd = mysqli_fetch_assoc($res_pd)) {
                $grp = $rpd['department_group'];
                $all_data['physio_by_dept'][$grp]['revenue'][$fy] = floatval($rpd['sum_price']);
                $all_data['physio_by_dept'][$grp]['patients'][$fy] = intval($rpd['pt_count']);
                $all_data['physio_by_dept'][$grp]['visits'][$fy] = intval($rpd['visit_count']);
            }
        }

        // Total Hospital Physio services (income = '14')
        $sql_p = "SELECT 
            COUNT(DISTINCT o.hn) as pt_count,
            COUNT(DISTINCT CASE WHEN o.an IS NULL OR o.an = '' THEN o.vn ELSE o.an END) as visit_count,
            COUNT(DISTINCT CASE WHEN o.an IS NULL OR o.an = '' THEN o.vn END) as opd_visits,
            COUNT(DISTINCT CASE WHEN o.an IS NOT NULL AND o.an != '' THEN o.an END) as ipd_ans,
            SUM(o.sum_price) as sum_price
        FROM opitemrece o
        WHERE o.vstdate BETWEEN '$s' AND '$e'
          AND o.income = '14'";
        $res_p = mysqli_query($conn2, $sql_p);
        if ($res_p && $rp = mysqli_fetch_assoc($res_p)) {
            $all_data['physio_service'][$fy] = floatval($rp['sum_price']);
            $all_data['physio_opd_pts'][$fy] = intval($rp['opd_visits']);
            $all_data['physio_ipd_pts'][$fy] = intval($rp['ipd_ans']);
            $all_data['physio_by_dept']['รวมทั้งโรงพยาบาล']['revenue'][$fy] = floatval($rp['sum_price']);
            $all_data['physio_by_dept']['รวมทั้งโรงพยาบาล']['patients'][$fy] = intval($rp['pt_count']);
            $all_data['physio_by_dept']['รวมทั้งโรงพยาบาล']['visits'][$fy] = intval($rp['visit_count']);
        }
    }

    file_put_contents($cache_file, json_encode($all_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode([
        'status' => 'success',
        'source' => 'live_hosxp',
        'data' => $all_data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check for Data Audit Report Mode (Checklist 4 Dimensions)
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] === 'data_audit') {
    // 1. Checklist 1: Completeness Audit (HOSxP vs eDHS)
    $edhs_stat_res = mysqli_query($conn, "SELECT 
        COUNT(*) as total_claims,
        COUNT(DISTINCT hn) as total_pts,
        COALESCE(SUM(claim_amount), 0) as total_claim_amount,
        COALESCE(SUM(compensated_amount), 0) as total_comp_amount,
        COALESCE(SUM(diff_amount), 0) as total_diff_amount
    FROM `imr_tb_rehab_device_claims` WHERE $where");
    $edhs_stat = ($edhs_stat_res) ? mysqli_fetch_assoc($edhs_stat_res) : [
        'total_claims' => 0, 'total_pts' => 0, 'total_claim_amount' => 0, 'total_comp_amount' => 0, 'total_diff_amount' => 0
    ];

    $audit_s = !empty($s) ? $s : (!empty($fy_start) ? $fy_start : '2025-10-01');
    $audit_e = !empty($e) ? $e : (!empty($fy_end) ? $fy_end : date('Y-m-d'));

    $hos_count = null;
    $hos_amount = null;
    $sync_gap = 0;
    if (isset($conn2) && $conn2) {
        $h_res = mysqli_query($conn2, "SELECT COUNT(*) as h_cnt, COALESCE(SUM(o.sum_price), 0) as h_amt
            FROM opitemrece o
            INNER JOIN nondrugitems nd ON o.icode = nd.icode
            WHERE o.vstdate BETWEEN '$audit_s' AND '$audit_e'
              AND (
                  nd.nhso_adp_type_id = 2
                  OR (nd.income = '02' AND COALESCE(nd.nhso_adp_code, '') != 'DMISHD')
                  OR nd.nhso_adp_code LIKE '8%'
                  OR nd.nhso_adp_code IN ('8901','8902','8307','8601','8602','8608','8610','8701','8703','8704','8705','8706','8707','8708','8711','8526','8304','33501')
              )
              AND COALESCE(nd.nhso_adp_code, '') NOT IN ('1001','1002','1003','2001','2002','3002')
              AND nd.name NOT LIKE '%ออกซิเจน%' AND nd.name NOT LIKE '%Oxygen%' AND nd.name NOT LIKE '%ค่าบริการ%'");
        if ($h_res && $hr = mysqli_fetch_assoc($h_res)) {
            $hos_count = intval($hr['h_cnt']);
            $hos_amount = floatval($hr['h_amt']);
            $sync_gap = max(0, $hos_count - intval($edhs_stat['total_claims']));
        }
    }

    // 2. Checklist 2: STM Statement Reconciliation
    $stm_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
        COALESCE(SUM(CASE WHEN claim_status = 'PAID' THEN 1 ELSE 0 END), 0) as paid_cnt,
        COALESCE(SUM(CASE WHEN claim_status = 'PAID' THEN compensated_amount ELSE 0 END), 0) as paid_amt,
        COALESCE(SUM(CASE WHEN claim_status = 'PARTIAL' THEN 1 ELSE 0 END), 0) as partial_cnt,
        COALESCE(SUM(CASE WHEN claim_status = 'PARTIAL' THEN compensated_amount ELSE 0 END), 0) as partial_amt,
        COALESCE(SUM(CASE WHEN claim_status = 'REJECTED' THEN 1 ELSE 0 END), 0) as rejected_cnt,
        COALESCE(SUM(CASE WHEN claim_status = 'REJECTED' THEN claim_amount ELSE 0 END), 0) as rejected_amt,
        COALESCE(SUM(CASE WHEN claim_status = 'WAIT_STM' THEN 1 ELSE 0 END), 0) as wait_stm_cnt,
        COALESCE(SUM(CASE WHEN claim_status = 'WAIT_STM' THEN claim_amount ELSE 0 END), 0) as wait_stm_amt,
        COALESCE(SUM(CASE WHEN claim_status = 'UNCLAIMED' THEN 1 ELSE 0 END), 0) as unclaimed_cnt,
        COALESCE(SUM(CASE WHEN claim_status = 'UNCLAIMED' THEN claim_amount ELSE 0 END), 0) as unclaimed_amt
    FROM `imr_tb_rehab_device_claims` WHERE $where"));

    // 3. Checklist 3: Pre-claim Quality Audit (Missing CID / PDX)
    $qual_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
        COALESCE(SUM(CASE WHEN cid IS NULL OR LENGTH(TRIM(cid)) < 13 THEN 1 ELSE 0 END), 0) as missing_cid_cnt,
        COALESCE(SUM(CASE WHEN diag_code IS NULL OR TRIM(diag_code) IN ('', '-', '0') THEN 1 ELSE 0 END), 0) as missing_diag_cnt,
        COALESCE(SUM(CASE WHEN (cid IS NULL OR LENGTH(TRIM(cid)) < 13) OR (diag_code IS NULL OR TRIM(diag_code) IN ('', '-', '0')) THEN 1 ELSE 0 END), 0) as total_invalid_cnt
    FROM `imr_tb_rehab_device_claims` WHERE $where"));

    // 4. Checklist 4: Aging & Overdue Claims Audit
    $aging_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
        COALESCE(SUM(CASE WHEN claim_status IN ('UNCLAIMED','WAIT_STM') AND DATEDIFF(CURDATE(), vstdate) > 90 THEN 1 ELSE 0 END), 0) as overdue_90d_cnt,
        COALESCE(SUM(CASE WHEN claim_status IN ('UNCLAIMED','WAIT_STM') AND DATEDIFF(CURDATE(), vstdate) > 90 THEN claim_amount ELSE 0 END), 0) as overdue_90d_amt,
        COALESCE(SUM(CASE WHEN claim_status IN ('UNCLAIMED','WAIT_STM') AND DATEDIFF(CURDATE(), vstdate) BETWEEN 31 AND 90 THEN 1 ELSE 0 END), 0) as aging_31_90d_cnt
    FROM `imr_tb_rehab_device_claims` WHERE $where"));

    // Calculate Audit Health Score (0 - 100%)
    $total_c = max(1, intval($edhs_stat['total_claims']));
    $flaws = intval($qual_res['total_invalid_cnt']) + intval($stm_res['rejected_cnt']) + $sync_gap;
    $health_score = max(0, min(100, round((($total_c - $flaws) / $total_c) * 100, 1)));

    echo json_encode([
        'status' => 'success',
        'data' => [
            'summary' => $edhs_stat,
            'hosxp_check' => [
                'hosxp_count' => $hos_count,
                'hosxp_amount' => $hos_amount,
                'sync_gap' => $sync_gap,
                'is_in_sync' => ($sync_gap === 0)
            ],
            'stm_reconciliation' => $stm_res,
            'quality_audit' => $qual_res,
            'aging_audit' => $aging_res,
            'health_score' => $health_score,
            'period' => [
                'start_date' => $audit_s,
                'end_date' => $audit_e
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check for Paginated Claims Table Mode (Tab 2)
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] === 'claims_table') {
    $c_where = $where;
    if (!empty($_REQUEST['status']) && $_REQUEST['status'] !== 'ALL') {
        $st = mysqli_real_escape_string($conn, $_REQUEST['status']);
        $c_where .= " AND claim_status = '$st'";
    }
    $req_dev = !empty($_REQUEST['dev_code']) ? $_REQUEST['dev_code'] : (!empty($_REQUEST['device_code']) ? $_REQUEST['device_code'] : '');
    if (!empty($req_dev) && $req_dev !== 'ALL') {
        $dc = mysqli_real_escape_string($conn, $req_dev);
        $c_where .= " AND device_code = '$dc'";
    }
    if (!empty($_REQUEST['search'])) {
        $s = mysqli_real_escape_string($conn, $_REQUEST['search']);
        $c_where .= " AND (hn LIKE '%$s%' OR cid LIKE '%$s%' OR ptname LIKE '%$s%' OR vn LIKE '%$s%' OR device_code LIKE '%$s%' OR receipt_no LIKE '%$s%')";
    }
    if (!empty($_REQUEST['pttype']) && $_REQUEST['pttype'] !== 'ALL') {
        $pt = mysqli_real_escape_string($conn, $_REQUEST['pttype']);
        $c_where .= " AND pttype = '$pt'";
    }
    if (!empty($_REQUEST['right_group']) && $_REQUEST['right_group'] !== 'ALL') {
        $rg = mysqli_real_escape_string($conn, $_REQUEST['right_group']);
        if ($rg === 'DISABILITY_74') {
            $c_where .= " AND pttype = '74'";
        } elseif ($rg === 'UC_GENERAL') {
            $c_where .= " AND fund_type = 'UC' AND pttype != '74'";
        } elseif ($rg === 'OFC_GOV') {
            $c_where .= " AND fund_type = 'OFC'";
        } elseif ($rg === 'LGO_LOCAL') {
            $c_where .= " AND fund_type = 'LGO'";
        } elseif ($rg === 'SSS_SOCIAL') {
            $c_where .= " AND fund_type = 'SSS'";
        } elseif ($rg === 'OTHER_FUNDS') {
            $c_where .= " AND fund_type NOT IN ('UC', 'OFC', 'LGO', 'SSS') AND pttype != '74'";
        }
    }

    $cnt_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM `imr_tb_rehab_device_claims` WHERE $c_where");
    $total_count = ($cnt_res) ? intval(mysqli_fetch_assoc($cnt_res)['total']) : 0;

    $offset = isset($_REQUEST['offset']) ? max(0, intval($_REQUEST['offset'])) : 0;
    $limit  = isset($_REQUEST['limit']) ? max(1, min(1000, intval($_REQUEST['limit']))) : 50;

    $sql_list = "SELECT 
        id, vn, an, hn, cid, ptname, age, vstdate, vsttime, fund_type, pttype, pttype_name, informaddr,
        device_code, device_name, dep_code, department_name, department_group, qty, unit_price, claim_amount, compensated_amount, diff_amount,
        claim_status, rep_no, receipt_no, receipt_date, receipt_source, stm_docno, denial_code, error_codes, is_resubmitted, resubmit_date, doctor_name, diag_code, diag_name, note
    FROM `imr_tb_rehab_device_claims`
    WHERE $c_where
    ORDER BY vstdate DESC, id DESC
    LIMIT $offset, $limit";

    $list_res = mysqli_query($conn, $sql_list);
    $items = [];
    if ($list_res) {
        while ($row = mysqli_fetch_assoc($list_res)) {
            $items[] = $row;
        }
    }

    echo json_encode([
        'status' => 'success',
        'total' => $total_count,
        'offset' => $offset,
        'limit' => $limit,
        'items' => $items
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Overall KPIs
$sql_kpi = "SELECT 
    COUNT(*) as total_cases,
    COUNT(DISTINCT hn) as total_patients,
    COALESCE(SUM(claim_amount), 0) as total_claim_amount,
    COALESCE(SUM(compensated_amount), 0) as total_compensated_amount,
    COALESCE(SUM(diff_amount), 0) as total_diff_amount,
    
    SUM(CASE WHEN claim_status IN ('PAID', 'PARTIAL') THEN 1 ELSE 0 END) as paid_count,
    COALESCE(SUM(CASE WHEN claim_status IN ('PAID', 'PARTIAL') THEN compensated_amount ELSE 0 END), 0) as paid_amount,
    
    SUM(CASE WHEN claim_status = 'WAIT_STM' THEN 1 ELSE 0 END) as wait_stm_count,
    COALESCE(SUM(CASE WHEN claim_status = 'WAIT_STM' THEN claim_amount ELSE 0 END), 0) as wait_stm_amount,
    
    SUM(CASE WHEN claim_status = 'REJECTED' THEN 1 ELSE 0 END) as rejected_count,
    COALESCE(SUM(CASE WHEN claim_status = 'REJECTED' THEN claim_amount ELSE 0 END), 0) as rejected_amount,
    
    SUM(CASE WHEN claim_status = 'RECALLED' THEN 1 ELSE 0 END) as recalled_count,
    COALESCE(SUM(CASE WHEN claim_status = 'RECALLED' THEN claim_amount ELSE 0 END), 0) as recalled_amount,
    
    SUM(CASE WHEN claim_status = 'UNCLAIMED' THEN 1 ELSE 0 END) as unclaimed_count,
    COALESCE(SUM(CASE WHEN claim_status = 'UNCLAIMED' THEN claim_amount ELSE 0 END), 0) as unclaimed_amount
FROM `imr_tb_rehab_device_claims`
WHERE $where";

$res_kpi = mysqli_query($conn, $sql_kpi);
$kpi = mysqli_fetch_assoc($res_kpi);

$total_cases = intval($kpi['total_cases']);
$total_claim_amount = floatval($kpi['total_claim_amount']);
$paid_count = intval($kpi['paid_count']);
$paid_amount = floatval($kpi['paid_amount']);

$urgent_count = intval($kpi['rejected_count']) + intval($kpi['recalled_count']) + intval($kpi['unclaimed_count']);
$urgent_amount = floatval($kpi['rejected_amount']) + floatval($kpi['recalled_amount']) + floatval($kpi['unclaimed_amount']);

$success_rate_count = $total_cases > 0 ? round(($paid_count / $total_cases) * 100, 1) : 0;
$success_rate_amount = $total_claim_amount > 0 ? round(($paid_amount / $total_claim_amount) * 100, 1) : 0;

// 2. Status Breakdown (for Donut Chart)
$sql_status = "SELECT claim_status, COUNT(*) as cnt, COALESCE(SUM(claim_amount), 0) as amount
FROM `imr_tb_rehab_device_claims`
WHERE $where
GROUP BY claim_status";
$res_status = mysqli_query($conn, $sql_status);
$status_data = [
    'PAID' => ['count' => 0, 'amount' => 0, 'label' => 'เคลมผ่าน/ชดเชยแล้ว'],
    'WAIT_STM' => ['count' => 0, 'amount' => 0, 'label' => 'รอผล STM'],
    'REJECTED' => ['count' => 0, 'amount' => 0, 'label' => 'ปฏิเสธ/เคลมไม่ผ่าน'],
    'RECALLED' => ['count' => 0, 'amount' => 0, 'label' => 'ถูกดึงเงินคืน'],
    'UNCLAIMED' => ['count' => 0, 'amount' => 0, 'label' => 'รอผลตรวจสอบ']
];
while ($r = mysqli_fetch_assoc($res_status)) {
    $st = $r['claim_status'];
    if ($st == 'PARTIAL') $st = 'PAID';
    if (isset($status_data[$st])) {
        $status_data[$st]['count'] += intval($r['cnt']);
        $status_data[$st]['amount'] += floatval($r['amount']);
    }
}

// 3. STM Aging (< 30 days, 31-60 days, > 60 days) for UNPAID/WAITING
$sql_aging = "SELECT 
    SUM(CASE WHEN DATEDIFF(CURDATE(), vstdate) <= 30 THEN 1 ELSE 0 END) as age_under_30,
    SUM(CASE WHEN DATEDIFF(CURDATE(), vstdate) BETWEEN 31 AND 60 THEN 1 ELSE 0 END) as age_31_60,
    SUM(CASE WHEN DATEDIFF(CURDATE(), vstdate) > 60 THEN 1 ELSE 0 END) as age_over_60,
    COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), vstdate) <= 30 THEN claim_amount ELSE 0 END), 0) as amt_under_30,
    COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), vstdate) BETWEEN 31 AND 60 THEN claim_amount ELSE 0 END), 0) as amt_31_60,
    COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), vstdate) > 60 THEN claim_amount ELSE 0 END), 0) as amt_over_60
FROM `imr_tb_rehab_device_claims`
WHERE $where AND claim_status IN ('WAIT_STM', 'UNCLAIMED', 'REJECTED')";
$res_aging = mysqli_query($conn, $sql_aging);
$aging_data = mysqli_fetch_assoc($res_aging);

// 4. Daily / Monthly Trend (Last 12 intervals)
$sql_trend = "SELECT 
    DATE_FORMAT(vstdate, '%Y-%m') as period_key,
    DATE_FORMAT(vstdate, '%M %Y') as period_label,
    SUM(CASE WHEN claim_status IN ('PAID', 'PARTIAL') THEN 1 ELSE 0 END) as paid,
    SUM(CASE WHEN claim_status = 'WAIT_STM' THEN 1 ELSE 0 END) as wait_stm,
    SUM(CASE WHEN claim_status = 'REJECTED' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN claim_status = 'UNCLAIMED' THEN 1 ELSE 0 END) as unclaimed,
    COALESCE(SUM(claim_amount), 0) as total_claim,
    COALESCE(SUM(compensated_amount), 0) as total_comp
FROM `imr_tb_rehab_device_claims`
WHERE $where
GROUP BY period_key
ORDER BY period_key DESC
LIMIT 12";
$res_trend = mysqli_query($conn, $sql_trend);
$trend_data = [];
while ($t = mysqli_fetch_assoc($res_trend)) {
    $trend_data[] = $t;
}
$trend_data = array_reverse($trend_data); // chronological

// 5. Top 10 Highest-Value Equipment Cards
$sql_top10 = "SELECT 
    device_code,
    device_name,
    COUNT(DISTINCT hn) as patient_count,
    COALESCE(SUM(qty), COUNT(*)) as total_qty,
    COALESCE(SUM(claim_amount), 0) as sum_claim,
    COALESCE(SUM(compensated_amount), 0) as sum_comp,
    COALESCE(SUM(diff_amount), 0) as sum_diff,
    ROUND((COALESCE(SUM(compensated_amount), 0) / NULLIF(SUM(claim_amount), 0)) * 100, 1) as success_pct
FROM `imr_tb_rehab_device_claims`
WHERE $where
GROUP BY device_code, device_name
ORDER BY sum_claim DESC
LIMIT 10";
$res_top10 = mysqli_query($conn, $sql_top10);
$top10_devices = [];
$rank = 1;
while ($d = mysqli_fetch_assoc($res_top10)) {
    $d['rank'] = $rank++;
    $top10_devices[] = $d;
}
$top3_devices = array_slice($top10_devices, 0, 3);

// 6. Device Summary Table (All Equipment)
$sql_devices = "SELECT 
    COALESCE(NULLIF(device_code, ''), 'OTHER') as device_code,
    device_name,
    COUNT(*) as total_qty,
    COALESCE(SUM(claim_amount), 0) as sum_claim,
    COALESCE(SUM(compensated_amount), 0) as sum_comp,
    COALESCE(SUM(diff_amount), 0) as sum_diff,
    ROUND((COALESCE(SUM(compensated_amount), 0) / NULLIF(SUM(claim_amount), 0)) * 100, 1) as success_pct
FROM `imr_tb_rehab_device_claims`
WHERE $where
GROUP BY device_code, device_name
ORDER BY sum_claim DESC";
$res_devices = mysqli_query($conn, $sql_devices);
$all_devices = [];
while ($d = mysqli_fetch_assoc($res_devices)) {
    $all_devices[] = $d;
}

// 7. Top Denial Reasons (with Thai translations from imr_tb_rehab_denial_codes)
$where_denials = preg_replace('/\b(fund_type|pttype|vstdate|claim_status)\b/', 'r.$1', $where);
$sql_denials = "SELECT 
    r.denial_code,
    COALESCE(d.description_th, 'รหัสปฏิเสธที่ยังไม่ได้ระบุคำอธิบาย') as description_th,
    COALESCE(d.severity, 'RETRY') as severity,
    COUNT(*) as reject_count,
    COALESCE(SUM(r.claim_amount), 0) as reject_amount
FROM `imr_tb_rehab_device_claims` r
LEFT JOIN `imr_tb_rehab_denial_codes` d ON (r.denial_code = d.denial_code OR CONCAT('D', r.denial_code) = d.denial_code)
WHERE $where_denials AND (r.denial_code IS NOT NULL AND r.denial_code != '' AND r.denial_code != '0')
GROUP BY r.denial_code, d.description_th, d.severity
ORDER BY reject_count DESC, reject_amount DESC
LIMIT 10";
$res_denials = mysqli_query($conn, $sql_denials);
$denial_reasons = [];
if ($res_denials) {
    while ($dr = mysqli_fetch_assoc($res_denials)) {
        $denial_reasons[] = $dr;
    }
}

// 8. Rights & Fund Breakdown Report (รายงานแยกตามสิทธิการรักษา - รวมคนพิการ ท.74 เข้ากับ สปสช. UC)
$sql_right_groups = "SELECT 
    CASE 
        WHEN fund_type = 'OFC' THEN 'OFC เบิกจ่ายตรงข้าราชการ'
        WHEN fund_type = 'UC' OR pttype = '74' THEN 'UC สปสช. (บัตรทอง / ท.74)'
        WHEN fund_type = 'LGO' THEN 'LGO เบิกจ่ายตรง อปท.'
        WHEN fund_type = 'SSS' THEN 'SSS ประกันสังคม'
        ELSE 'OTHER พรบ. / กองทุนอื่นๆ'
    END as right_group,
    CASE 
        WHEN fund_type = 'OFC' THEN 'OFC'
        WHEN fund_type = 'UC' OR pttype = '74' THEN 'UC'
        WHEN fund_type = 'LGO' THEN 'LGO'
        WHEN fund_type = 'SSS' THEN 'SSS'
        ELSE 'OTHER'
    END as group_code,
    COUNT(DISTINCT hn) as patient_count,
    COUNT(*) as case_count,
    COALESCE(SUM(qty), 0) as total_qty,
    COALESCE(SUM(claim_amount), 0) as sum_claim,
    COALESCE(SUM(compensated_amount), 0) as sum_comp,
    COALESCE(SUM(diff_amount), 0) as sum_diff,
    ROUND((COALESCE(SUM(compensated_amount), 0) / NULLIF(SUM(claim_amount), 0)) * 100, 1) as success_pct
FROM `imr_tb_rehab_device_claims`
WHERE $where
GROUP BY right_group, group_code
ORDER BY sum_claim DESC";

$res_rg = mysqli_query($conn, $sql_right_groups);
$right_groups = [];
if ($res_rg) {
    while ($rg = mysqli_fetch_assoc($res_rg)) {
        $right_groups[] = $rg;
    }
}

// สรุปแจกแจงละเอียดตามรหัสสิทธิ (Detail by pttype)
$sql_rights_detail = "SELECT 
    fund_type,
    pttype,
    COALESCE(NULLIF(pttype_name, ''), CONCAT('สิทธิรหัส ', pttype)) as pttype_name,
    CASE 
        WHEN fund_type = 'OFC' THEN 'เบิกจ่ายตรง (ข้าราชการ)'
        WHEN fund_type = 'UC' OR pttype = '74' THEN 'สปสช. (บัตรทอง / ท.74)'
        WHEN fund_type = 'LGO' THEN 'อปท.'
        WHEN fund_type = 'SSS' THEN 'ประกันสังคม'
        ELSE 'พรบ./อื่นๆ'
    END as parent_group,
    CASE 
        WHEN fund_type = 'OFC' THEN 'OFC_GOV'
        WHEN fund_type = 'UC' OR pttype = '74' THEN 'UC_GENERAL'
        WHEN fund_type = 'LGO' THEN 'LGO_LOCAL'
        WHEN fund_type = 'SSS' THEN 'SSS_SOCIAL'
        ELSE 'OTHER_FUNDS'
    END as group_code,
    COUNT(DISTINCT hn) as patient_count,
    COUNT(*) as case_count,
    COALESCE(SUM(qty), 0) as total_qty,
    COALESCE(SUM(claim_amount), 0) as sum_claim,
    COALESCE(SUM(compensated_amount), 0) as sum_comp,
    COALESCE(SUM(diff_amount), 0) as sum_diff,
    ROUND((COALESCE(SUM(compensated_amount), 0) / NULLIF(SUM(claim_amount), 0)) * 100, 1) as success_pct
FROM `imr_tb_rehab_device_claims`
WHERE $where
GROUP BY fund_type, pttype, pttype_name
ORDER BY sum_claim DESC";

$res_rd = mysqli_query($conn, $sql_rights_detail);
$rights_detail = [];
if ($res_rd) {
    while ($rd = mysqli_fetch_assoc($res_rd)) {
        $rights_detail[] = $rd;
    }
}

// 1. สรุปข้อมูลแยกตามกลุ่มงาน/แผนก (Department Summary)
$sql_dept_groups = "SELECT 
    department_group,
    COUNT(DISTINCT hn) as patient_count,
    COUNT(*) as case_count,
    COALESCE(SUM(qty), 0) as total_qty,
    COALESCE(SUM(claim_amount), 0) as sum_claim,
    COALESCE(SUM(compensated_amount), 0) as sum_comp,
    COALESCE(SUM(diff_amount), 0) as sum_diff,
    SUM(CASE WHEN claim_status = 'PAID' THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN claim_status = 'WAIT_STM' THEN 1 ELSE 0 END) as wait_stm_count,
    SUM(CASE WHEN claim_status = 'REJECTED' THEN 1 ELSE 0 END) as rejected_count,
    SUM(CASE WHEN claim_status = 'UNCLAIMED' THEN 1 ELSE 0 END) as unclaimed_count,
    ROUND((COALESCE(SUM(compensated_amount), 0) / NULLIF(SUM(claim_amount), 0)) * 100, 1) as success_pct
FROM `imr_tb_rehab_device_claims`
WHERE $where
GROUP BY department_group
ORDER BY sum_claim DESC";

$res_dg = mysqli_query($conn, $sql_dept_groups);
$department_groups = [];
if ($res_dg) {
    while ($dg = mysqli_fetch_assoc($res_dg)) {
        $department_groups[] = $dg;
    }
}

// 2. รายละเอียดอุปกรณ์ยอดนิยมแยกตามกลุ่มงาน (Department Detail Items)
$sql_dept_items = "SELECT 
    department_group,
    device_code,
    device_name,
    COUNT(DISTINCT hn) as patient_count,
    COUNT(*) as order_count,
    COALESCE(SUM(qty), 0) as total_qty,
    COALESCE(SUM(claim_amount), 0) as sum_claim,
    COALESCE(SUM(compensated_amount), 0) as sum_comp,
    COALESCE(SUM(diff_amount), 0) as sum_diff,
    ROUND((COALESCE(SUM(compensated_amount), 0) / NULLIF(SUM(claim_amount), 0)) * 100, 1) as success_pct
FROM `imr_tb_rehab_device_claims`
WHERE $where
GROUP BY department_group, device_code, device_name
ORDER BY department_group ASC, sum_claim DESC";

$res_di = mysqli_query($conn, $sql_dept_items);
$department_items = [];
if ($res_di) {
    while ($di = mysqli_fetch_assoc($res_di)) {
        $department_items[] = $di;
    }
}

$output = [
    'status' => 'success',
    'kpi' => [
        'total_cases' => $total_cases,
        'total_patients' => intval($kpi['total_patients']),
        'total_claim_amount' => $total_claim_amount,
        'total_compensated_amount' => floatval($kpi['total_compensated_amount']),
        'total_diff_amount' => floatval($kpi['total_diff_amount']),
        'paid_count' => $paid_count,
        'paid_amount' => $paid_amount,
        'wait_stm_count' => intval($kpi['wait_stm_count']),
        'wait_stm_amount' => floatval($kpi['wait_stm_amount']),
        'rejected_count' => intval($kpi['rejected_count']),
        'rejected_amount' => floatval($kpi['rejected_amount']),
        'recalled_count' => intval($kpi['recalled_count']),
        'recalled_amount' => floatval($kpi['recalled_amount']),
        'unclaimed_count' => intval($kpi['unclaimed_count']),
        'unclaimed_amount' => floatval($kpi['unclaimed_amount']),
        'urgent_count' => $urgent_count,
        'urgent_amount' => $urgent_amount,
        'success_rate_count' => $success_rate_count,
        'success_rate_amount' => $success_rate_amount
    ],
    'status_data' => $status_data,
    'aging_data' => $aging_data,
    'trend_data' => $trend_data,
    'top10_devices' => $top10_devices,
    'top3_devices' => $top3_devices,
    'all_devices' => $all_devices,
    'denial_reasons' => $denial_reasons,
    'rights_report' => [
        'right_groups' => $right_groups,
        'groups' => $right_groups,
        'detail' => $rights_detail
    ],
    'department_report' => [
        'groups' => $department_groups,
        'items' => $department_items
    ]
];

echo json_encode($output, JSON_UNESCAPED_UNICODE);
