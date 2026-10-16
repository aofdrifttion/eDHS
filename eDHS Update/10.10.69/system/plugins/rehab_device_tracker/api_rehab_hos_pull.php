<?php
/**
 * Rehab & Medical Device Compensation Tracker Plugin
 * API: Pull Medical Device Orders from HOSxP ($conn2) and Sync to eDHS ($conn)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/plugin_config.php';
rehab_release_session();

$response = [
    'status' => 'error',
    'message' => '',
    'pulled_count' => 0,
    'inserted_count' => 0,
    'matched_stm_count' => 0
];

if (!isset($conn2) || !$conn2) {
    $response['message'] = 'ไม่สามารถเชื่อมต่อฐานข้อมูล HOSxP ได้ โปรดตรวจสอบการตั้งค่าใน config.json';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$start_date = isset($_REQUEST['start_date']) && !empty($_REQUEST['start_date']) ? $_REQUEST['start_date'] : date('Y-01-01');
$end_date   = isset($_REQUEST['end_date']) && !empty($_REQUEST['end_date']) ? $_REQUEST['end_date'] : date('Y-m-d');
$auto_match = isset($_REQUEST['auto_match']) ? intval($_REQUEST['auto_match']) : 1;

// Validate Date formats
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $response['message'] = 'รูปแบบวันที่ไม่ถูกต้อง (ต้องเป็น YYYY-MM-DD)';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Fetch from HOSxP
// 1. Fetch from HOSxP (Upgraded with User Query: Age, Address, Vsttime, ICD-10 Name & Globally Unique hos_guid)
$sql_hos = "SELECT 
    o.hos_guid,
    o.vn,
    o.an,
    o.hn,
    p.cid,
    CONCAT(COALESCE(p.pname,''), COALESCE(p.fname,''), ' ', COALESCE(p.lname,'')) AS ptname,
    v.age_y AS age,
    p.informaddr,
    o.vstdate,
    o.vsttime,
    o.pttype,
    pt.name AS pttype_name,
    CASE 
        WHEN pt.pcode IN ('UC', 'AA', 'AB') OR pt.hipdata_code = 'UCS' THEN 'UC'
        WHEN pt.pcode IN ('OFC', 'FK', 'CS') OR pt.hipdata_code = 'OFC' THEN 'OFC'
        WHEN pt.pcode IN ('LGO', 'LG') OR pt.hipdata_code = 'LGO' THEN 'LGO'
        WHEN pt.pcode IN ('SSS', 'SS') OR pt.hipdata_code = 'SSS' THEN 'SSS'
        ELSE 'OTHER'
    END AS fund_type,
    o.icode,
    COALESCE(nd.nhso_adp_code, '') AS device_code,
    nd.name AS device_name,
    COALESCE(o.qty, 1) AS qty,
    COALESCE(o.unitprice, 0) AS unit_price,
    COALESCE(o.sum_price, 0) AS claim_amount,
    d.name AS doctor_name,
    COALESCE(od.icd10, v.pdx, '') AS diag_code,
    COALESCE(i.name, '') AS diag_name,
    o.dep_code,
    COALESCE(kd.department, kdv.department, 'ไม่ระบุ') AS department_name,
    CASE 
        WHEN o.dep_code = '042' OR ov.main_dep = '042' THEN 'กายภาพบำบัด'
        WHEN o.dep_code = '005' OR ov.main_dep = '005' THEN 'ทันตกรรม'
        WHEN o.dep_code = '041' OR ov.main_dep = '041' THEN 'แพทย์แผนไทย'
        WHEN o.dep_code = '011' OR ov.main_dep = '011' THEN 'อุบัติเหตุและฉุกเฉิน (ER)'
        WHEN o.dep_code IN ('009','015') OR ov.main_dep IN ('009','015') OR (o.an IS NOT NULL AND o.an != '') THEN 'ผู้ป่วยใน (IPD)'
        WHEN o.dep_code IN ('006','010','014','019','020','021','026','030','034','036','037','040','048','050') 
             OR ov.main_dep IN ('006','010','014','019','020','021','026','030','034','036','037','040','048','050') THEN 'ผู้ป่วยนอก (OPD)'
        ELSE 'อื่นๆ / แผนกสนับสนุน'
    END AS department_group
FROM opitemrece o
INNER JOIN nondrugitems nd ON o.icode = nd.icode
LEFT JOIN patient p ON o.hn = p.hn
LEFT JOIN pttype pt ON o.pttype = pt.pttype
LEFT JOIN vn_stat v ON o.vn = v.vn
LEFT JOIN ovst ov ON o.vn = ov.vn
LEFT JOIN kskdepartment kd ON o.dep_code = kd.depcode
LEFT JOIN kskdepartment kdv ON ov.main_dep = kdv.depcode
LEFT JOIN (
    SELECT vn, icd10 
    FROM ovstdiag 
    WHERE diagtype = '1' 
    GROUP BY vn
) od ON od.vn = o.vn
LEFT JOIN icd101 i ON (i.code = COALESCE(od.icd10, v.pdx))
LEFT JOIN doctor d ON o.doctor = d.code
WHERE o.vstdate BETWEEN ? AND ?
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
                  OR nd.name LIKE '%Splint%'
                  OR nd.name LIKE '%Sling%'
              )
          )
      )
  )
  AND COALESCE(nd.nhso_adp_code, '') NOT IN ('DMISHD', '8313', '8622', '8659')
  AND (nd.nhso_adp_type_id IS NULL OR nd.nhso_adp_type_id != 19)
  AND nd.income NOT IN ('01', '08', '11')
ORDER BY o.vstdate ASC, o.vn ASC";

$stmt = mysqli_prepare($conn2, $sql_hos);
if (!$stmt) {
    $response['message'] = 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL HOSxP: ' . mysqli_error($conn2);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$pulled_count = 0;
$inserted_count = 0;

if ($result) {
    // 2. Prepare Insert/Update in eDHS using Globally Unique hos_guid to prevent duplicates 100%
    $sql_upsert = "INSERT INTO `imr_tb_rehab_device_claims` (
        hos_guid, vn, an, hn, cid, ptname, age, vstdate, vsttime, fund_type, pttype, pttype_name, informaddr,
        dep_code, department_name, department_group,
        icode, device_code, device_name, qty, unit_price, claim_amount,
        diff_amount, claim_status, doctor_name, diag_code, diag_name
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, 'UNCLAIMED', ?, ?, ?
    ) ON DUPLICATE KEY UPDATE 
        vn = VALUES(vn),
        an = VALUES(an),
        hn = VALUES(hn),
        cid = VALUES(cid),
        ptname = VALUES(ptname),
        age = VALUES(age),
        vstdate = VALUES(vstdate),
        vsttime = VALUES(vsttime),
        fund_type = VALUES(fund_type),
        pttype = VALUES(pttype),
        pttype_name = VALUES(pttype_name),
        informaddr = VALUES(informaddr),
        dep_code = VALUES(dep_code),
        department_name = VALUES(department_name),
        department_group = VALUES(department_group),
        device_code = VALUES(device_code),
        device_name = VALUES(device_name),
        qty = VALUES(qty),
        unit_price = VALUES(unit_price),
        claim_amount = VALUES(claim_amount),
        diff_amount = GREATEST(0, VALUES(claim_amount) - compensated_amount),
        doctor_name = VALUES(doctor_name),
        diag_code = VALUES(diag_code),
        diag_name = VALUES(diag_name)";

    // Ensure Unique index on hos_guid so ON DUPLICATE KEY works accurately across OPD and IPD
    $chk_idx = mysqli_query($conn, "SHOW INDEX FROM `imr_tb_rehab_device_claims` WHERE Key_name = 'uniq_hos_guid'");
    if ($chk_idx && mysqli_num_rows($chk_idx) == 0) {
        mysqli_query($conn, "ALTER TABLE `imr_tb_rehab_device_claims` ADD UNIQUE KEY `uniq_hos_guid` (`hos_guid`)");
    }

    $stmt_insert = mysqli_prepare($conn, $sql_upsert);

    $audit_warning_count = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $pulled_count++;

        $hos_guid = !empty($row['hos_guid']) ? $row['hos_guid'] : ('{SYN-' . md5(($row['vn'] ?: $row['an']) . '_' . $row['icode'] . '_' . $row['vstdate']) . '}');
        $vn = $row['vn'];
        $an = $row['an'];
        $hn = $row['hn'];
        $cid = $row['cid'];
        $ptname = $row['ptname'];
        $age = !empty($row['age']) ? intval($row['age']) : null;
        $vstdate = $row['vstdate'];
        $vsttime = $row['vsttime'];
        $fund_type = $row['fund_type'];
        $pttype = $row['pttype'];
        $pttype_name = $row['pttype_name'];
        $informaddr = $row['informaddr'];
        $dep_code = !empty($row['dep_code']) ? $row['dep_code'] : null;
        $department_name = !empty($row['department_name']) ? $row['department_name'] : 'ไม่ระบุ';
        $department_group = !empty($row['department_group']) ? $row['department_group'] : 'อื่นๆ / แผนกสนับสนุน';
        $icode = $row['icode'];
        $device_code = $row['device_code'];
        $device_name = $row['device_name'];
        $qty = floatval($row['qty']);
        $unit_price = floatval($row['unit_price']);
        $claim_amount = floatval($row['claim_amount']);
        $diff_amount = $claim_amount; // initial diff
        $doctor_name = $row['doctor_name'];
        $diag_code = $row['diag_code'];
        $diag_name = $row['diag_name'];

        // Audit Check: Missing CID or missing PDX diagnosis
        $is_cid_missing = (empty($cid) || strlen(trim($cid)) < 13);
        $is_diag_missing = (empty($diag_code) || trim($diag_code) === '-');
        if ($is_cid_missing || $is_diag_missing) {
            $audit_warning_count++;
        }

        if ($stmt_insert) {
            mysqli_stmt_bind_param(
                $stmt_insert,
                "ssssssissssssssssssddddsss",
                $hos_guid, $vn, $an, $hn, $cid, $ptname, $age, $vstdate, $vsttime, $fund_type, $pttype, $pttype_name, $informaddr,
                $dep_code, $department_name, $department_group,
                $icode, $device_code, $device_name, $qty, $unit_price, $claim_amount,
                $diff_amount, $doctor_name, $diag_code, $diag_name
            );
            if (mysqli_stmt_execute($stmt_insert)) {
                $inserted_count++;
            }
        }
    }
    if ($stmt_insert) mysqli_stmt_close($stmt_insert);
}
mysqli_stmt_close($stmt);

// 3. Auto-Match with eDHS STM records using Aggregated Subquery from User Query
$matched_count = 0;
if ($auto_match && $inserted_count > 0) {
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
            ELSE r.compensated_amount 
        END,
        r.diff_amount = GREATEST(0, r.claim_amount - (CASE 
            WHEN c.inst > 0 THEN c.inst
            WHEN c.compensated > 0 THEN c.compensated
            ELSE r.compensated_amount 
        END)),
        r.claim_status = CASE 
            WHEN r.claim_status = 'PAID' THEN 'PAID'
            WHEN (CASE WHEN c.inst > 0 THEN c.inst WHEN c.compensated > 0 THEN c.compensated ELSE r.compensated_amount END) >= r.claim_amount THEN 'PAID'
            WHEN (CASE WHEN c.inst > 0 THEN c.inst WHEN c.compensated > 0 THEN c.compensated ELSE r.compensated_amount END) > 0 THEN 'PARTIAL'
            WHEN (c.errorcode IS NOT NULL AND c.errorcode != '' AND c.errorcode != '0') THEN 'REJECTED'
            WHEN (c.rep IS NOT NULL AND c.rep != '') OR (c.docno IS NOT NULL AND c.docno != '') THEN 'WAIT_STM'
            ELSE r.claim_status
        END,
        r.rep_no = IF(c.rep IS NOT NULL AND c.rep != '' AND c.rep NOT IN ('\"\"', '\'\'', '-'), c.rep, r.rep_no),
        r.stm_docno = IF(c.docno IS NOT NULL AND c.docno != '' AND c.docno NOT IN ('\"\"', '\'\'', '-'), c.docno, r.stm_docno),
        r.denial_code = IF(c.errorcode IS NOT NULL AND c.errorcode != '' AND c.errorcode != '0', c.errorcode, r.denial_code),
        r.error_codes = c.errorcode
    WHERE r.vstdate BETWEEN '$start_date' AND '$end_date'";

    $match_res = mysqli_query($conn, $sql_match);
    if ($match_res) {
        $matched_count = mysqli_affected_rows($conn);
    }

    // Auto-update denial messages with Thai description and type from Master 933 Codes
    $sql_update_desc = "UPDATE `imr_tb_rehab_device_claims` r
    JOIN `imr_tb_rehab_denial_codes` d ON (
        r.denial_code = d.denial_code 
        OR CONCAT('D', r.denial_code) = d.denial_code 
        OR CONCAT('C', r.denial_code) = d.denial_code
        OR REPLACE(REPLACE(r.denial_code, 'D', ''), 'C', '') = d.denial_code
    )
    SET r.denial_message = CONCAT('[', COALESCE(d.deny_type, 'Deny'), '] ', d.description_th)
    WHERE r.vstdate BETWEEN '$start_date' AND '$end_date'
      AND r.denial_code IS NOT NULL AND r.denial_code != '' AND r.denial_code != '0'";
    mysqli_query($conn, $sql_update_desc);

    // 4. Synchronize Debtor Rights Receipts (OPD/IPD)
    $debtor_matched = 0;
    if (function_exists('rehab_sync_debtor_rights')) {
        $debtor_matched = rehab_sync_debtor_rights($conn, "r.vstdate BETWEEN '$start_date' AND '$end_date'");
    }
}

$response['status'] = 'success';
$response['pulled_count'] = $pulled_count;
$response['inserted_count'] = $inserted_count;
$response['matched_stm_count'] = $matched_count;
$response['matched_debtor_count'] = isset($debtor_matched) ? $debtor_matched : 0;
$response['audit_warnings'] = isset($audit_warning_count) ? $audit_warning_count : 0;
$response['date_range'] = "$start_date ถึง $end_date";
$response['message'] = "ตรวจสอบและ Audit ข้อมูลสำเร็จ $pulled_count รายการ (นำเข้า/อัปเดต $inserted_count รายการ, ชนยอด Statement $matched_count รายการ, ตรวจสอบใบเสร็จ/ลูกหนี้สิทธิ " . (isset($debtor_matched) ? $debtor_matched : 0) . " รายการ)";

echo json_encode($response, JSON_UNESCAPED_UNICODE);


