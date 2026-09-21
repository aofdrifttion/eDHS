<?php
session_start();
require './database_config/config.php';
require_once './database_config/db_helper.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$enabled = isset($_POST['enabled']) ? $_POST['enabled'] : '0';
$month_mode = isset($_POST['month_mode']) ? trim($_POST['month_mode']) : 'START_FROM';
$active_months = (isset($_POST['active_months']) && is_array($_POST['active_months'])) ? array_values(array_filter($_POST['active_months'])) : [];
$effective_start_month = isset($_POST['effective_start_month']) ? trim($_POST['effective_start_month']) : 'ALL';
$effective_end_month = isset($_POST['effective_end_month']) ? trim($_POST['effective_end_month']) : 'NONE';

// อัตราผัง 1102050101.203 (ในจังหวัดสังกัด สธ.)
$acc_203_base = isset($_POST['acc_203_base']) ? floatval($_POST['acc_203_base']) : 175.0;
$acc_203_refer_node = isset($_POST['acc_203_refer_node']) ? floatval($_POST['acc_203_refer_node']) : 250.0;
$acc_203_refer_center = isset($_POST['acc_203_refer_center']) ? floatval($_POST['acc_203_refer_center']) : 350.0;

// อัตราผัง 1102050102.201 (นอกสังกัด สธ. - รพ.ค่าย / เทศบาล ๑-๒)
$acc_102_ae_max = isset($_POST['acc_102_ae_max']) ? floatval($_POST['acc_102_ae_max']) : 700.0;
$acc_102_refer = isset($_POST['acc_102_refer']) ? floatval($_POST['acc_102_refer']) : 350.0;
$acc_102_walkin = isset($_POST['acc_102_walkin']) ? floatval($_POST['acc_102_walkin']) : 175.0;

// บันทึกลง config.json
$config_file = __DIR__ . '/database_config/config.json';
if (file_exists($config_file)) {
    $config_data = json_decode(file_get_contents($config_file), true);
    
    $config_data['debtor_setting'] = [
        'enabled' => $enabled,
        'month_mode' => $month_mode,
        'active_months' => $active_months,
        'effective_start_month' => $effective_start_month,
        'effective_end_month' => $effective_end_month,
        'amount' => strval($acc_203_base),
        'acc_203' => [
            'base_rate' => strval($acc_203_base),
            'refer_node' => strval($acc_203_refer_node),
            'refer_center' => strval($acc_203_refer_center)
        ],
        'acc_102_201' => [
            'ae_max' => strval($acc_102_ae_max),
            'refer' => strval($acc_102_refer),
            'walkin' => strval($acc_102_walkin)
        ]
    ];
    
    file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
} else {
    echo json_encode(['status' => 'error', 'message' => 'Config file not found.']);
    exit();
}

// เงื่อนไขคุ้มครองยกเว้นเคสไตเทียม 100%
$not_kidney_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%' AND (department IS NULL OR department NOT LIKE '%ไต%') AND (clinic IS NULL OR clinic NOT LIKE '%ไต%'))";

// เงื่อนไขคุ้มครองรายการที่ออกใบเสร็จหรือตัดหนี้แล้ว (ห้ามแก้ไขยอดหนี้ที่ชำระ/ออกบิลแล้ว)
$not_billed_condition = "AND (bill IS NULL OR bill = '' OR bill = '-') AND (mobile IS NULL OR mobile = '' OR mobile = '-')";

if ($enabled === '1') {
    // -------------------------------------------------------------
    // 1. จำแนกเดือนทั้งหมดในฐานข้อมูล OPD:
    //    $target_months = เดือนที่มีผลบังคับใช้ (คำนวณข้อตกลง 175)
    //    $outside_months = เดือนที่อยู่นอกข้อตกลง (คืนค่ายอดจริงตาม HOSxP)
    // -------------------------------------------------------------
    $q_months = mysqli_query($conn, "SELECT DISTINCT monthtxt FROM imr_tb_debtor_rights_opd WHERE monthtxt IS NOT NULL AND monthtxt != ''");
    $target_months = [];
    $outside_months = [];
    if ($q_months) {
        while ($rm = mysqli_fetch_assoc($q_months)) {
            $mt = trim($rm['monthtxt']);
            if ($mt === '') continue;
            if (is_debtor_setting_effective_for_month($config_data['debtor_setting'], $mt)) {
                $target_months[] = mysqli_real_escape_string($conn, $mt);
            } else {
                $outside_months[] = mysqli_real_escape_string($conn, $mt);
            }
        }
    }

    // -------------------------------------------------------------
    // 2. คำนวณยอดหนี้ข้อตกลงสำหรับเดือนที่มีผล ($target_months)
    // -------------------------------------------------------------
    if (!empty($target_months)) {
        $target_month_sql = "AND monthtxt IN ('" . implode("','", $target_months) . "')";

        // 2.1 จัดการผัง 1102050101.203 (ในจังหวัดสังกัด สธ.)
        // สำรองยอดหนี้เดิมเฉพาะแถวที่ยังไม่เคยสำรอง
        $conn->query("UPDATE imr_tb_debtor_rights_opd 
                      SET original_debit = debit 
                      WHERE accountcode = '1102050101.203' 
                        AND original_debit IS NULL 
                        $not_kidney_condition 
                        $not_billed_condition 
                        $target_month_sql");
        
        // อัปเดตยอดหนี้เป็นอัตราข้อตกลง
        $conn->query("UPDATE imr_tb_debtor_rights_opd 
                      SET debit = $acc_203_base 
                      WHERE accountcode = '1102050101.203' 
                        $not_kidney_condition 
                        $not_billed_condition 
                        $target_month_sql");
        
        // 2.2 จัดการผัง 1102050102.201 (นอกสังกัด สธ. - รพ.ค่าย / เทศบาล ๑-๒)
        // สำรองยอดหนี้เดิม
        $conn->query("UPDATE imr_tb_debtor_rights_opd 
                      SET original_debit = debit 
                      WHERE accountcode = '1102050102.201' 
                        AND original_debit IS NULL 
                        $not_billed_condition 
                        $target_month_sql");
        
        // อัปเดตยอดหนี้กรณีฉุกเฉิน (AE) เป็น จ่ายตามจริงไม่เกิน acc_102_ae_max
        $conn->query("UPDATE imr_tb_debtor_rights_opd 
                      SET debit = LEAST(original_debit, $acc_102_ae_max) 
                      WHERE accountcode = '1102050102.201' 
                        $not_billed_condition 
                        $target_month_sql");
    }

    // -------------------------------------------------------------
    // 3. คืนค่ายอดจริงดั้งเดิมสำหรับเดือนที่อยู่นอกข้อตกลง ($outside_months)
    //    (เช่น เดือนเว้นเดือน หรือเดือนที่ผู้ใช้ยกเลิกการติ๊กออก)
    // -------------------------------------------------------------
    if (!empty($outside_months)) {
        $outside_month_sql = "AND monthtxt IN ('" . implode("','", $outside_months) . "')";

        // คืนค่าผัง 1102050101.203
        $conn->query("UPDATE imr_tb_debtor_rights_opd 
                      SET debit = original_debit 
                      WHERE accountcode = '1102050101.203' 
                        AND original_debit IS NOT NULL 
                        $not_billed_condition 
                        $outside_month_sql");
        
        $conn->query("UPDATE imr_tb_debtor_rights_opd 
                      SET original_debit = NULL 
                      WHERE accountcode = '1102050101.203' 
                        $not_billed_condition 
                        $outside_month_sql");

        // คืนค่าผัง 1102050102.201
        $conn->query("UPDATE imr_tb_debtor_rights_opd 
                      SET debit = original_debit 
                      WHERE accountcode = '1102050102.201' 
                        AND original_debit IS NOT NULL 
                        $not_billed_condition 
                        $outside_month_sql");

        $conn->query("UPDATE imr_tb_debtor_rights_opd 
                      SET original_debit = NULL 
                      WHERE accountcode = '1102050102.201' 
                        $not_billed_condition 
                        $outside_month_sql");
    }

} else {
    // -------------------------------------------------------------
    // ปิดระบบ: คืนค่า debit ดั้งเดิมกลับมาสำหรับทุกผังและทุกเดือน
    // -------------------------------------------------------------
    // 1. คืนค่าผัง 1102050101.203
    $conn->query("UPDATE imr_tb_debtor_rights_opd 
                  SET debit = original_debit 
                  WHERE accountcode = '1102050101.203' 
                    AND original_debit IS NOT NULL 
                    $not_billed_condition");
    
    $conn->query("UPDATE imr_tb_debtor_rights_opd 
                  SET original_debit = NULL 
                  WHERE accountcode = '1102050101.203' 
                    $not_billed_condition");
    
    // 2. คืนค่าผัง 1102050102.201
    $conn->query("UPDATE imr_tb_debtor_rights_opd 
                  SET debit = original_debit 
                  WHERE accountcode = '1102050102.201' 
                    AND original_debit IS NOT NULL 
                    $not_billed_condition");
    
    $conn->query("UPDATE imr_tb_debtor_rights_opd 
                  SET original_debit = NULL 
                  WHERE accountcode = '1102050102.201' 
                    $not_billed_condition");
}

if (function_exists('system_log')) {
    $statusText = ($enabled === '1') ? 'เปิด' : 'ปิด';
    $modeText = ($month_mode === 'SPECIFIC') ? "กำหนดรายเดือน (" . count($active_months) . " เดือน)" : (($month_mode === 'ALL') ? "ทุกเดือน" : "ช่วงเดือน ($effective_start_month ถึง $effective_end_month)");
    system_log($conn, 'ตั้งค่าลูกหนี้ (Settings)', 'UPDATE', "ผู้ใช้ปรับปรุงการตั้งค่าลูกหนี้ข้อตกลงจังหวัด (สถานะ: $statusText, โหมด: $modeText, 203_base: $acc_203_base, 102_ae_max: $acc_102_ae_max)");
}

echo json_encode(['status' => 'success', 'message' => 'บันทึกการตั้งค่าระบบลูกหนี้ข้อตกลงจังหวัดเรียบร้อยแล้ว']);
exit();
?>
