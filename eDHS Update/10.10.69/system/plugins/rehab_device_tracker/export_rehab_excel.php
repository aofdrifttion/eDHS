<?php
/**
 * Rehab & Medical Device Compensation Tracker Plugin
 * Export Claims Data & Executive Summary to Excel
 */

require_once __DIR__ . '/plugin_config.php';

$export_type = isset($_GET['type']) ? $_GET['type'] : 'claims_list';
$where = "1=1";

if (!empty($_GET['fiscal_year']) && $_GET['fiscal_year'] !== 'ALL') {
    $ce_year = intval($_GET['fiscal_year']) - 543;
    $fy_start = ($ce_year - 1) . "-10-01";
    $fy_end   = $ce_year . "-09-30";
    $where .= " AND r.vstdate BETWEEN '$fy_start' AND '$fy_end'";
} elseif (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $s = mysqli_real_escape_string($conn, $_GET['start_date']);
    $e = mysqli_real_escape_string($conn, $_GET['end_date']);
    $where .= " AND r.vstdate BETWEEN '$s' AND '$e'";
}

if (!empty($_GET['fund_type']) && $_GET['fund_type'] !== 'ALL') {
    $f = mysqli_real_escape_string($conn, $_GET['fund_type']);
    if ($f === 'UC' || $f === 'DISABILITY_74') {
        $where .= " AND (r.fund_type = 'UC' OR r.pttype = '74')";
    } else {
        $where .= " AND r.fund_type = '$f'";
    }
}

if (!empty($_GET['claim_status']) && $_GET['claim_status'] !== 'ALL') {
    $st = mysqli_real_escape_string($conn, $_GET['claim_status']);
    $where .= " AND r.claim_status = '$st'";
}

if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (r.hn LIKE '%$s%' OR r.cid LIKE '%$s%' OR r.ptname LIKE '%$s%' OR r.vn LIKE '%$s%' OR r.device_code LIKE '%$s%')";
}

$dev_filter = !empty($_GET['device_code']) ? $_GET['device_code'] : (!empty($_GET['dev_code']) ? $_GET['dev_code'] : '');
if (!empty($dev_filter) && $dev_filter !== 'ALL') {
    $dc = mysqli_real_escape_string($conn, $dev_filter);
    $where .= " AND r.device_code = '$dc'";
}

$dept_filter = !empty($_GET['department_group']) ? $_GET['department_group'] : (!empty($_GET['department']) ? $_GET['department'] : '');
if (!empty($dept_filter) && $dept_filter !== 'ALL') {
    $dg = mysqli_real_escape_string($conn, $dept_filter);
    $where .= " AND r.department_group = '$dg'";
}

$filename = "rehab_device_claims_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output UTF-8 BOM
echo "\xEF\xBB\xBF";

if ($export_type === 'department_report') {
    $filename = "rehab_department_report_" . date('Ymd_His') . ".xls";
    
    // Group Summary Query (6 Primary Department Groups)
    $sql_groups = "SELECT 
        r.department_group,
        COUNT(DISTINCT r.hn) as patient_count,
        COUNT(*) as case_count,
        COALESCE(SUM(r.qty), 0) as total_qty,
        COALESCE(SUM(r.claim_amount), 0) as sum_claim,
        COALESCE(SUM(r.compensated_amount), 0) as sum_comp,
        COALESCE(SUM(r.diff_amount), 0) as sum_diff,
        SUM(CASE WHEN r.claim_status = 'PAID' THEN 1 ELSE 0 END) as paid_count,
        ROUND((COALESCE(SUM(r.compensated_amount), 0) / NULLIF(SUM(r.claim_amount), 0)) * 100, 1) as success_pct
    FROM `imr_tb_rehab_device_claims` r
    WHERE $where
    GROUP BY r.department_group
    ORDER BY sum_claim DESC";
    $res_groups = mysqli_query($conn, $sql_groups);

    // Detail Query by Device per Department
    $sql_detail = "SELECT 
        r.department_group,
        r.device_code,
        r.device_name,
        COUNT(DISTINCT r.hn) as patient_count,
        COUNT(*) as case_count,
        COALESCE(SUM(r.qty), 0) as total_qty,
        COALESCE(SUM(r.claim_amount), 0) as sum_claim,
        COALESCE(SUM(r.compensated_amount), 0) as sum_comp,
        COALESCE(SUM(r.diff_amount), 0) as sum_diff,
        ROUND((COALESCE(SUM(r.compensated_amount), 0) / NULLIF(SUM(r.claim_amount), 0)) * 100, 1) as success_pct
    FROM `imr_tb_rehab_device_claims` r
    WHERE $where
    GROUP BY r.department_group, r.device_code, r.device_name
    ORDER BY r.department_group ASC, sum_claim DESC";
    $res_detail = mysqli_query($conn, $sql_detail);
    ?>
    <table border="1">
        <tr>
            <th colspan="9" style="font-size: 16pt; font-weight: bold; background-color: #0b5ed7; color: #ffffff; text-align: center; height: 40px;">
                รายงานสรุปการสั่งใช้และเบิกชดเชยอุปกรณ์การแพทย์และเวชกรรมฟื้นฟู แยกตามกลุ่มงาน/แผนก
            </th>
        </tr>
        <tr>
            <th colspan="9" style="font-size: 11pt; text-align: left; background-color: #f8fafc;">
                วันที่พิมพ์รายงาน: <?= date('d/m/Y H:i:s') ?> | โรงพยาบาลโพนทราย
            </th>
        </tr>
    </table>
    <br>
    
    <!-- Table 1: Summary by 6 Department Groups -->
    <table border="1">
        <thead>
            <tr>
                <th colspan="9" style="background-color: #e2e8f0; font-weight: bold; text-align: left;">
                    1. สรุปภาพรวมตามกลุ่มงาน / แผนกที่สั่งจ่ายอุปกรณ์ (Primary Department Summary)
                </th>
            </tr>
            <tr style="background-color: #0284c7; color: #ffffff; font-weight: bold; text-align: center;">
                <th style="width: 50px;">ลำดับ</th>
                <th style="width: 250px;">กลุ่มงาน / แผนก</th>
                <th style="width: 100px;">ผู้ป่วย (คน)</th>
                <th style="width: 100px;">จำนวนครั้ง</th>
                <th style="width: 100px;">จำนวนชิ้น</th>
                <th style="width: 150px;">ยอดเรียกเก็บ (บาท)</th>
                <th style="width: 150px;">ยอดชดเชยจริง (บาท)</th>
                <th style="width: 150px;">ค้างชดเชย (บาท)</th>
                <th style="width: 100px;">% สำเร็จ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            $tot_pts = 0; $tot_cases = 0; $tot_qty = 0; $tot_claim = 0; $tot_comp = 0; $tot_diff = 0;
            while ($g = mysqli_fetch_assoc($res_groups)): 
                $tot_pts += $g['patient_count'];
                $tot_cases += $g['case_count'];
                $tot_qty += $g['total_qty'];
                $tot_claim += $g['sum_claim'];
                $tot_comp += $g['sum_comp'];
                $tot_diff += $g['sum_diff'];
            ?>
            <tr>
                <td align="center"><?= $i++ ?></td>
                <td><b><?= htmlspecialchars($g['department_group']) ?></b></td>
                <td align="right"><?= number_format($g['patient_count']) ?></td>
                <td align="right"><?= number_format($g['case_count']) ?></td>
                <td align="right"><?= number_format($g['total_qty']) ?></td>
                <td align="right"><?= number_format($g['sum_claim'], 2) ?></td>
                <td align="right" style="color: green;"><b><?= number_format($g['sum_comp'], 2) ?></b></td>
                <td align="right" style="color: red;"><?= number_format($g['sum_diff'], 2) ?></td>
                <td align="right"><b><?= number_format($g['success_pct'], 1) ?>%</b></td>
            </tr>
            <?php endwhile; 
            $tot_pct = ($tot_claim > 0) ? ($tot_comp / $tot_claim * 100) : 0;
            ?>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="2" align="center">รวมทั้งสิ้น</td>
                <td align="right"><?= number_format($tot_pts) ?></td>
                <td align="right"><?= number_format($tot_cases) ?></td>
                <td align="right"><?= number_format($tot_qty) ?></td>
                <td align="right"><?= number_format($tot_claim, 2) ?></td>
                <td align="right" style="color: green;"><?= number_format($tot_comp, 2) ?></td>
                <td align="right" style="color: red;"><?= number_format($tot_diff, 2) ?></td>
                <td align="right"><?= number_format($tot_pct, 1) ?>%</td>
            </tr>
        </tbody>
    </table>
    <br>

    <!-- Table 2: Detailed Breakdown by Device in each Department -->
    <table border="1">
        <thead>
            <tr>
                <th colspan="10" style="background-color: #e2e8f0; font-weight: bold; text-align: left;">
                    2. แจกแจงรายละเอียดรายการอุปกรณ์ยอดนิยมในแต่ละกลุ่มงาน (Equipment Breakdown by Department)
                </th>
            </tr>
            <tr style="background-color: #334155; color: #ffffff; font-weight: bold; text-align: center;">
                <th style="width: 50px;">ลำดับ</th>
                <th style="width: 180px;">กลุ่มงาน</th>
                <th style="width: 100px;">รหัส ADP</th>
                <th style="width: 280px;">ชื่ออุปกรณ์ทางการแพทย์</th>
                <th style="width: 90px;">ผู้ป่วย (คน)</th>
                <th style="width: 90px;">จำนวนครั้ง</th>
                <th style="width: 90px;">จำนวนชิ้น</th>
                <th style="width: 130px;">ยอดเรียกเก็บ (บาท)</th>
                <th style="width: 130px;">ชดเชยจริง (บาท)</th>
                <th style="width: 90px;">% สำเร็จ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $j = 1;
            while ($d = mysqli_fetch_assoc($res_detail)): 
            ?>
            <tr>
                <td align="center"><?= $j++ ?></td>
                <td><b><?= htmlspecialchars($d['department_group']) ?></b></td>
                <td align="center" style="mso-number-format:'\@';"><?= htmlspecialchars($d['device_code']) ?></td>
                <td><?= htmlspecialchars($d['device_name']) ?></td>
                <td align="right"><?= number_format($d['patient_count']) ?></td>
                <td align="right"><?= number_format($d['case_count']) ?></td>
                <td align="right"><?= number_format($d['total_qty']) ?></td>
                <td align="right"><?= number_format($d['sum_claim'], 2) ?></td>
                <td align="right" style="color: green;"><?= number_format($d['sum_comp'], 2) ?></td>
                <td align="right"><b><?= number_format($d['success_pct'], 1) ?>%</b></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php
    exit;
}

if ($export_type === 'summary_devices') {
    $filename = "rehab_rights_report_" . date('Ymd_His') . ".xls";
    
    // Group Summary Query (5 Primary Groups)
    $sql_groups = "SELECT 
        CASE 
            WHEN r.fund_type = 'OFC' THEN 'เบิกจ่ายตรง (ข้าราชการกรมบัญชีกลาง)'
            WHEN r.fund_type = 'UC' OR r.pttype = '74' THEN 'สปสช. (บัตรทอง / ท.74 คนพิการ)'
            WHEN r.fund_type = 'LGO' THEN 'เบิกจ่ายตรง (อปท. ส่วนท้องถิ่น)'
            WHEN r.fund_type = 'SSS' THEN 'ประกันสังคม (สำนักงานประกันสังคม)'
            ELSE 'พรบ. ผู้ประสบภัยจากรถ / กองทุนอื่นๆ'
        END as right_group,
        COUNT(DISTINCT r.hn) as patient_count,
        COUNT(*) as case_count,
        COALESCE(SUM(r.qty), 0) as total_qty,
        COALESCE(SUM(r.claim_amount), 0) as sum_claim,
        COALESCE(SUM(r.compensated_amount), 0) as sum_comp,
        COALESCE(SUM(r.diff_amount), 0) as sum_diff,
        ROUND((COALESCE(SUM(r.compensated_amount), 0) / NULLIF(SUM(r.claim_amount), 0)) * 100, 1) as success_pct
    FROM `imr_tb_rehab_device_claims` r
    WHERE $where
    GROUP BY right_group
    ORDER BY sum_claim DESC";
    $res_groups = mysqli_query($conn, $sql_groups);

    // Detail Query by pttype
    $sql_detail = "SELECT 
        r.fund_type,
        r.pttype,
        COALESCE(NULLIF(r.pttype_name, ''), CONCAT('สิทธิรหัส ', r.pttype)) as pttype_name,
        CASE 
            WHEN r.fund_type = 'OFC' THEN 'เบิกจ่ายตรง (ข้าราชการ)'
            WHEN r.fund_type = 'UC' OR r.pttype = '74' THEN 'สปสช. (บัตรทอง / ท.74)'
            WHEN r.fund_type = 'LGO' THEN 'อปท.'
            WHEN r.fund_type = 'SSS' THEN 'ประกันสังคม'
            ELSE 'พรบ./อื่นๆ'
        END as parent_group,
        COUNT(DISTINCT r.hn) as patient_count,
        COUNT(*) as case_count,
        COALESCE(SUM(r.qty), 0) as total_qty,
        COALESCE(SUM(r.claim_amount), 0) as sum_claim,
        COALESCE(SUM(r.compensated_amount), 0) as sum_comp,
        COALESCE(SUM(r.diff_amount), 0) as sum_diff,
        ROUND((COALESCE(SUM(r.compensated_amount), 0) / NULLIF(SUM(r.claim_amount), 0)) * 100, 1) as success_pct
    FROM `imr_tb_rehab_device_claims` r
    WHERE $where
    GROUP BY r.fund_type, r.pttype, r.pttype_name
    ORDER BY sum_claim DESC";
    $res_detail = mysqli_query($conn, $sql_detail);
    ?>
    <table border="0" style="margin-bottom: 20px;">
        <tr>
            <th colspan="9" style="font-size: 16pt; font-weight: bold; text-align: left; color: #1e3a8a;">
                รายงานสรุปการเบิกชดเชยอุปกรณ์และเวชกรรมฟื้นฟู แยกตามสิทธิการรักษา
            </th>
        </tr>
        <tr>
            <td colspan="9" style="font-size: 11pt; color: #475569;">
                ระบบติดตามการเบิกชดเชยอุปกรณ์และเวชกรรมฟื้นฟู (eDHS) | วันที่พิมพ์: <?= date('d/m/Y H:i') ?>
            </td>
        </tr>
    </table>

    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="background-color: #1e3a8a; color: #ffffff; font-weight: bold; text-align: center;">
                <th colspan="9" style="font-size: 13pt; text-align: left; padding: 8px;">1. ตารางสรุปภาพรวมตามกลุ่มสิทธิการรักษา (Primary Rights Groups)</th>
            </tr>
            <tr style="background-color: #3b82f6; color: #ffffff; font-weight: bold; text-align: center;">
                <th>ลำดับ</th>
                <th>กลุ่มสิทธิการรักษา</th>
                <th>ผู้ป่วย (คน)</th>
                <th>จำนวนครั้งที่สั่ง</th>
                <th>จำนวนชิ้น</th>
                <th>ยอดเรียกเก็บ (บาท)</th>
                <th>ชดเชยแล้ว (บาท)</th>
                <th>ค้างชดเชย (บาท)</th>
                <th>อัตราสำเร็จ (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            $t_pts = 0; $t_cases = 0; $t_qty = 0; $t_claim = 0; $t_comp = 0; $t_diff = 0;
            while ($g = mysqli_fetch_assoc($res_groups)): 
                $t_pts += $g['patient_count'];
                $t_cases += $g['case_count'];
                $t_qty += $g['total_qty'];
                $t_claim += $g['sum_claim'];
                $t_comp += $g['sum_comp'];
                $t_diff += $g['sum_diff'];
            ?>
            <tr>
                <td align="center"><?= $i++ ?></td>
                <td><b><?= htmlspecialchars($g['right_group']) ?></b></td>
                <td align="right"><?= number_format($g['patient_count']) ?></td>
                <td align="right"><?= number_format($g['case_count']) ?></td>
                <td align="right"><?= number_format($g['total_qty']) ?></td>
                <td align="right"><?= number_format($g['sum_claim'], 2) ?></td>
                <td align="right" style="color: green; font-weight: bold;"><?= number_format($g['sum_comp'], 2) ?></td>
                <td align="right" style="color: red;"><?= number_format($g['sum_diff'], 2) ?></td>
                <td align="right"><b><?= $g['success_pct'] ?>%</b></td>
            </tr>
            <?php endwhile; ?>
            <tr style="background-color: #e2e8f0; font-weight: bold;">
                <td colspan="2" align="center">รวมทั้งสิ้น</td>
                <td align="right"><?= number_format($t_pts) ?></td>
                <td align="right"><?= number_format($t_cases) ?></td>
                <td align="right"><?= number_format($t_qty) ?></td>
                <td align="right"><?= number_format($t_claim, 2) ?></td>
                <td align="right" style="color: green;"><?= number_format($t_comp, 2) ?></td>
                <td align="right" style="color: red;"><?= number_format($t_diff, 2) ?></td>
                <td align="right"><?= $t_claim > 0 ? round(($t_comp / $t_claim) * 100, 1) : 0 ?>%</td>
            </tr>
        </tbody>
    </table>

    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
        <thead>
            <tr style="background-color: #0f766e; color: #ffffff; font-weight: bold; text-align: center;">
                <th colspan="10" style="font-size: 13pt; text-align: left; padding: 8px;">2. ตารางแจกแจงรายละเอียดรายสิทธิการรักษา (Detail by Pttype)</th>
            </tr>
            <tr style="background-color: #14b8a6; color: #ffffff; font-weight: bold; text-align: center;">
                <th>ลำดับ</th>
                <th>กลุ่มหลัก</th>
                <th>รหัสสิทธิ</th>
                <th>ชื่อสิทธิการรักษาใน HOSxP</th>
                <th>ผู้ป่วย (คน)</th>
                <th>จำนวนครั้ง</th>
                <th>จำนวนชิ้น</th>
                <th>ยอดเรียกเก็บ (บาท)</th>
                <th>ชดเชยแล้ว (บาท)</th>
                <th>ส่วนต่าง (บาท)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $j = 1;
            while ($d = mysqli_fetch_assoc($res_detail)): 
            ?>
            <tr>
                <td align="center"><?= $j++ ?></td>
                <td><?= htmlspecialchars($d['parent_group']) ?></td>
                <td align="center" style="mso-number-format:'\@';"><?= htmlspecialchars($d['pttype']) ?></td>
                <td><?= htmlspecialchars($d['pttype_name']) ?></td>
                <td align="right"><?= number_format($d['patient_count']) ?></td>
                <td align="right"><?= number_format($d['case_count']) ?></td>
                <td align="right"><?= number_format($d['total_qty']) ?></td>
                <td align="right"><?= number_format($d['sum_claim'], 2) ?></td>
                <td align="right" style="color: green;"><?= number_format($d['sum_comp'], 2) ?></td>
                <td align="right" style="color: red;"><?= number_format($d['sum_diff'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php
    exit;
}

if ($export_type === 'summary_devices') {
    // Export Summary Table
    $sql = "SELECT 
        COALESCE(NULLIF(r.device_code, ''), 'OTHER') as device_code,
        r.device_name,
        COUNT(*) as total_qty,
        COALESCE(SUM(r.claim_amount), 0) as sum_claim,
        COALESCE(SUM(r.compensated_amount), 0) as sum_comp,
        COALESCE(SUM(r.diff_amount), 0) as sum_diff,
        ROUND((COALESCE(SUM(r.compensated_amount), 0) / NULLIF(SUM(r.claim_amount), 0)) * 100, 1) as success_pct
    FROM `imr_tb_rehab_device_claims` r
    WHERE $where
    GROUP BY r.device_code, r.device_name
    ORDER BY sum_claim DESC";
    $res = mysqli_query($conn, $sql);
    ?>
    <table border="1">
        <thead>
            <tr style="background-color: #2b5797; color: #ffffff; font-weight: bold; text-align: center;">
                <th>ลำดับ</th>
                <th>รหัสอุปกรณ์ (ADP)</th>
                <th>ชื่ออุปกรณ์ทางการแพทย์</th>
                <th>จำนวน (ครั้ง)</th>
                <th>มูลค่าเรียกเก็บ (บาท)</th>
                <th>ชดเชยแล้ว (บาท)</th>
                <th>ส่วนต่าง (บาท)</th>
                <th>% ความสำเร็จ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            $tot_qty = 0; $tot_claim = 0; $tot_comp = 0; $tot_diff = 0;
            while ($row = mysqli_fetch_assoc($res)): 
                $tot_qty += $row['total_qty'];
                $tot_claim += $row['sum_claim'];
                $tot_comp += $row['sum_comp'];
                $tot_diff += $row['sum_diff'];
            ?>
            <tr>
                <td align="center"><?= $i++ ?></td>
                <td align="center"><?= htmlspecialchars($row['device_code']) ?></td>
                <td><?= htmlspecialchars($row['device_name']) ?></td>
                <td align="right"><?= number_format($row['total_qty']) ?></td>
                <td align="right"><?= number_format($row['sum_claim'], 2) ?></td>
                <td align="right"><?= number_format($row['sum_comp'], 2) ?></td>
                <td align="right"><?= number_format($row['sum_diff'], 2) ?></td>
                <td align="right"><?= $row['success_pct'] ?>%</td>
            </tr>
            <?php endwhile; ?>
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="3" align="center">รวมทั้งสิ้น</td>
                <td align="right"><?= number_format($tot_qty) ?></td>
                <td align="right"><?= number_format($tot_claim, 2) ?></td>
                <td align="right"><?= number_format($tot_comp, 2) ?></td>
                <td align="right"><?= number_format($tot_diff, 2) ?></td>
                <td align="right"><?= $tot_claim > 0 ? round(($tot_comp / $tot_claim) * 100, 1) : 0 ?>%</td>
            </tr>
        </tbody>
    </table>
    <?php
    exit;
}

// Default: Claims Detail List
$sql = "SELECT 
    r.*,
    COALESCE(d.description_th, '') as denial_desc,
    COALESCE(d.deny_type, '') as deny_type,
    COALESCE(d.action_guide, '') as action_guide
FROM `imr_tb_rehab_device_claims` r
LEFT JOIN `imr_tb_rehab_denial_codes` d ON (
    r.denial_code = d.denial_code 
    OR CONCAT('D', r.denial_code) = d.denial_code 
    OR CONCAT('C', r.denial_code) = d.denial_code
    OR REPLACE(REPLACE(r.denial_code, 'D', ''), 'C', '') = d.denial_code
)
WHERE $where
ORDER BY r.vstdate DESC, r.id DESC";

$res = mysqli_query($conn, $sql);
?>
<table border="1">
    <thead>
        <tr style="background-color: #0b5ed7; color: #ffffff; font-weight: bold; text-align: center;">
            <th>ลำดับ</th>
            <th>วันที่รับบริการ</th>
            <th>เวลา</th>
            <th>VN</th>
            <th>HN</th>
            <th>เลขประจำตัวประชาชน (CID)</th>
            <th>ชื่อ-สกุล ผู้ป่วย</th>
            <th>อายุ (ปี)</th>
            <th>ที่อยู่ติดต่อ</th>
            <th>สิทธิการรักษา</th>
            <th>กองทุนหลัก</th>
            <th>รหัสอุปกรณ์ (ADP)</th>
            <th>ชื่ออุปกรณ์ทางการแพทย์</th>
            <th>จำนวน</th>
            <th>ราคาต่อหน่วย</th>
            <th>ยอดเรียกเก็บ (บาท)</th>
            <th>ยอดชดเชยจริง (บาท)</th>
            <th>ส่วนต่าง (บาท)</th>
            <th>สถานะการเบิกจ่าย</th>
            <th>รอบเบิก (REP)</th>
            <th>เลขที่ใบเสร็จรับเงิน (Receipt No)</th>
            <th>วันที่ออกใบเสร็จ</th>
            <th>แหล่งข้อมูลใบเสร็จ</th>
            <th>เลขที่ Statement (DocNo)</th>
            <th>ประเภทข้อผิดพลาด</th>
            <th>รหัสปฏิเสธ</th>
            <th>รหัสผิดพลาดทั้งหมด (STM)</th>
            <th>คำอธิบายรหัสปฏิเสธ (สปสช.)</th>
            <th>แนวทางแก้ไข (Guide)</th>
            <th>ส่งเบิกซ้ำ</th>
            <th>วันที่ส่งเบิกซ้ำ</th>
            <th>แพทย์/ผู้สั่ง</th>
            <th>รหัสโรค (Diag)</th>
            <th>ชื่อโรค (ICD-10 Name)</th>
            <th>หมายเหตุ</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $i = 1;
        while ($row = mysqli_fetch_assoc($res)): 
        ?>
        <tr>
            <td align="center"><?= $i++ ?></td>
            <td align="center"><?= $row['vstdate'] ?></td>
            <td align="center"><?= $row['vsttime'] ?: '-' ?></td>
            <td align="center" style="mso-number-format:'\@';"><?= $row['vn'] ?></td>
            <td align="center" style="mso-number-format:'\@';"><?= $row['hn'] ?></td>
            <td align="center" style="mso-number-format:'\@';"><?= $row['cid'] ?></td>
            <td><?= htmlspecialchars($row['ptname']) ?></td>
            <td align="center"><?= $row['age'] ?: '-' ?></td>
            <td><?= htmlspecialchars($row['informaddr'] ?: '-') ?></td>
            <td><?= htmlspecialchars($row['pttype_name']) ?></td>
            <td align="center"><?= htmlspecialchars($row['fund_type']) ?></td>
            <td align="center" style="mso-number-format:'\@';"><?= htmlspecialchars($row['device_code']) ?></td>
            <td><?= htmlspecialchars($row['device_name']) ?></td>
            <td align="right"><?= number_format($row['qty'], 2) ?></td>
            <td align="right"><?= number_format($row['unit_price'], 2) ?></td>
            <td align="right"><?= number_format($row['claim_amount'], 2) ?></td>
            <td align="right"><?= number_format($row['compensated_amount'], 2) ?></td>
            <td align="right"><?= number_format($row['diff_amount'], 2) ?></td>
            <td align="center"><?= htmlspecialchars($row['claim_status']) ?></td>
            <td align="center" style="mso-number-format:'\@';"><?= htmlspecialchars($row['rep_no']) ?></td>
            <td align="center" style="mso-number-format:'\@';"><?= htmlspecialchars($row['receipt_no'] ?? '') ?></td>
            <td align="center"><?= htmlspecialchars($row['receipt_date'] ?? '') ?></td>
            <td align="center"><?= htmlspecialchars($row['receipt_source'] ?? '') ?></td>
            <td align="center" style="mso-number-format:'\@';"><?= htmlspecialchars($row['stm_docno']) ?></td>
            <td align="center"><?= htmlspecialchars($row['deny_type'] ?: '-') ?></td>
            <td align="center"><?= htmlspecialchars($row['denial_code']) ?></td>
            <td align="center"><?= htmlspecialchars($row['error_codes'] ?: '-') ?></td>
            <td><?= htmlspecialchars($row['denial_desc']) ?></td>
            <td><?= htmlspecialchars($row['action_guide'] ?: '-') ?></td>
            <td align="center"><?= $row['is_resubmitted'] ? 'ส่งเบิกซ้ำแล้ว' : 'ยังไม่ส่งซ้ำ' ?></td>
            <td align="center"><?= $row['resubmit_date'] ?: '-' ?></td>
            <td><?= htmlspecialchars($row['doctor_name']) ?></td>
            <td align="center"><?= htmlspecialchars($row['diag_code']) ?></td>
            <td><?= htmlspecialchars($row['diag_name'] ?: '-') ?></td>
            <td><?= htmlspecialchars($row['note']) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
