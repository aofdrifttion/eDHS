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
$effective_start_month = isset($_POST['effective_start_month']) ? trim($_POST['effective_start_month']) : 'ALL';

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
        'effective_start_month' => $effective_start_month,
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

// -------------------------------------------------------------
// คำนวณขอบเขตเดือนที่มีผลบังคับใช้
// -------------------------------------------------------------
$month_condition = "";
if (!empty($effective_start_month) && strtoupper($effective_start_month) !== 'ALL') {
    $q_months = mysqli_query($conn, "SELECT DISTINCT monthtxt FROM imr_tb_debtor_rights_opd WHERE monthtxt IS NOT NULL AND monthtxt != ''");
    $target_months = [];
    if ($q_months) {
        while ($rm = mysqli_fetch_assoc($q_months)) {
            $mt = trim($rm['monthtxt']);
            if (compare_monthtxt_be($mt, $effective_start_month) >= 0) {
                $target_months[] = mysqli_real_escape_string($conn, $mt);
            }
        }
    }
    if (!empty($target_months)) {
        $month_condition = "AND monthtxt IN ('" . implode("','", $target_months) . "')";
    } else {
        $month_condition = "AND 1 = 0";
    }
}

// เงื่อนไขคุ้มครองยกเว้นเคสไตเทียม 100%
$not_kidney_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%' AND (department IS NULL OR department NOT LIKE '%ไต%') AND (clinic IS NULL OR clinic NOT LIKE '%ไต%'))";

// เงื่อนไขคุ้มครองรายการที่ออกใบเสร็จหรือตัดหนี้แล้ว (ห้ามแก้ไขยอดหนี้ที่ชำระ/ออกบิลแล้ว)
$not_billed_condition = "AND (bill IS NULL OR bill = '')";

if ($enabled === '1') {
    // -------------------------------------------------------------
    // 1. จัดการผัง 1102050101.203 (ในจังหวัดสังกัด สธ.)
    // -------------------------------------------------------------
    // 1.1 สำรองยอดหนี้เดิมเฉพาะแถวที่ยังไม่เคยสำรอง
    $sql_opd_backup_203 = "UPDATE imr_tb_debtor_rights_opd 
                           SET original_debit = debit 
                           WHERE accountcode = '1102050101.203' 
                             AND original_debit IS NULL 
                             $not_kidney_condition 
                             $not_billed_condition 
                             $month_condition";
    $conn->query($sql_opd_backup_203);
    
    // 1.2 อัปเดตยอดหนี้ตามประเภทบริการ (ค่ามาตรฐานทั่วไปคือ acc_203_base)
    $sql_opd_update_203 = "UPDATE imr_tb_debtor_rights_opd 
                           SET debit = $acc_203_base 
                           WHERE accountcode = '1102050101.203' 
                             $not_kidney_condition 
                             $not_billed_condition 
                             $month_condition";
    $conn->query($sql_opd_update_203);
    
    // -------------------------------------------------------------
    // 2. จัดการผัง 1102050102.201 (นอกสังกัด สธ. - รพ.ค่าย / เทศบาล ๑-๒)
    // -------------------------------------------------------------
    // 2.1 สำรองยอดหนี้เดิม
    $sql_opd_backup_102 = "UPDATE imr_tb_debtor_rights_opd 
                           SET original_debit = debit 
                           WHERE accountcode = '1102050102.201' 
                             AND original_debit IS NULL 
                             $not_billed_condition 
                             $month_condition";
    $conn->query($sql_opd_backup_102);
    
    // 2.2 อัปเดตยอดหนี้กรณีฉุกเฉิน (AE) เป็น จ่ายตามจริงไม่เกิน acc_102_ae_max (เพดาน 700 บาท)
    $sql_opd_update_102 = "UPDATE imr_tb_debtor_rights_opd 
                           SET debit = LEAST(original_debit, $acc_102_ae_max) 
                           WHERE accountcode = '1102050102.201' 
                             $not_billed_condition 
                             $month_condition";
    $conn->query($sql_opd_update_102);

} else {
    // -------------------------------------------------------------
    // ปิดระบบ: คืนค่า debit ดั้งเดิมกลับมาสำหรับทุกผัง
    // -------------------------------------------------------------
    // 1. คืนค่าผัง 1102050101.203
    $sql_opd_restore_203 = "UPDATE imr_tb_debtor_rights_opd 
                            SET debit = original_debit 
                            WHERE accountcode = '1102050101.203' 
                              AND original_debit IS NOT NULL 
                              $not_billed_condition";
    $conn->query($sql_opd_restore_203);
    
    $sql_opd_clear_203 = "UPDATE imr_tb_debtor_rights_opd 
                          SET original_debit = NULL 
                          WHERE accountcode = '1102050101.203' 
                            $not_billed_condition";
    $conn->query($sql_opd_clear_203);
    
    // 2. คืนค่าผัง 1102050102.201
    $sql_opd_restore_102 = "UPDATE imr_tb_debtor_rights_opd 
                            SET debit = original_debit 
                            WHERE accountcode = '1102050102.201' 
                              AND original_debit IS NOT NULL 
                              $not_billed_condition";
    $conn->query($sql_opd_restore_102);
    
    $sql_opd_clear_102 = "UPDATE imr_tb_debtor_rights_opd 
                          SET original_debit = NULL 
                          WHERE accountcode = '1102050102.201' 
                            $not_billed_condition";
    $conn->query($sql_opd_clear_102);
}

if (function_exists('system_log')) {
    $statusText = ($enabled === '1') ? 'เปิด' : 'ปิด';
    system_log($conn, 'ตั้งค่าลูกหนี้ (Settings)', 'UPDATE', "ผู้ใช้ปรับปรุงการตั้งค่าลูกหนี้ข้อตกลงจังหวัด (สถานะ: $statusText, เดือนเริ่มต้น: $effective_start_month, 203_base: $acc_203_base, 102_ae_max: $acc_102_ae_max)");
}

echo json_encode(['status' => 'success', 'message' => 'บันทึกการตั้งค่าระบบลูกหนี้ข้อตกลงจังหวัดเรียบร้อยแล้ว']);
exit();
?>
