<?php
session_start();
require './database_config/config.php';

// ตรวจสอบการ Login (ถ้าต้องการ)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$enabled = isset($_POST['enabled']) ? $_POST['enabled'] : '0';
$amount = isset($_POST['amount']) ? $_POST['amount'] : '175';
$amount = floatval($amount);

// บันทึกลง config.json
$config_file = __DIR__ . '/database_config/config.json';
if (file_exists($config_file)) {
    $config_data = json_decode(file_get_contents($config_file), true);
    
    if (!isset($config_data['debtor_setting'])) {
        $config_data['debtor_setting'] = [];
    }
    
    $config_data['debtor_setting']['enabled'] = $enabled;
    $config_data['debtor_setting']['amount'] = strval($amount);
    
    file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
} else {
    echo json_encode(['status' => 'error', 'message' => 'Config file not found.']);
    exit();
}

// อัปเดตฐานข้อมูลตามสถานะ
if ($enabled === '1') {
    // 1. คัดลอกค่าเดิมไปไว้ที่ original_debit ก่อน (เฉพาะแถวที่ยังไม่เคยคัดลอก)
    $sql_opd_backup = "UPDATE imr_tb_debtor_rights_opd SET original_debit = debit WHERE accountcode IN ('1102050101.203') AND original_debit IS NULL";
    $conn->query($sql_opd_backup);
    
    $sql_ipd_backup = "UPDATE imr_tb_debtor_rights_ipd SET original_debit = debit WHERE accountcode IN ('1102050101.203') AND original_debit IS NULL";
    $conn->query($sql_ipd_backup);
    
    // 2. อัปเดต debit ให้เป็นยอดที่ตั้งค่าไว้
    $sql_opd_update = "UPDATE imr_tb_debtor_rights_opd SET debit = $amount WHERE accountcode IN ('1102050101.203')";
    $conn->query($sql_opd_update);
    
    $sql_ipd_update = "UPDATE imr_tb_debtor_rights_ipd SET debit = $amount WHERE accountcode IN ('1102050101.203')";
    $conn->query($sql_ipd_update);
} else {
    // 1. คืนค่า debit ดั้งเดิมกลับมา
    $sql_opd_restore = "UPDATE imr_tb_debtor_rights_opd SET debit = original_debit WHERE accountcode IN ('1102050101.203') AND original_debit IS NOT NULL";
    $conn->query($sql_opd_restore);
    
    $sql_ipd_restore = "UPDATE imr_tb_debtor_rights_ipd SET debit = original_debit WHERE accountcode IN ('1102050101.203') AND original_debit IS NOT NULL";
    $conn->query($sql_ipd_restore);
    
    // 2. เคลียร์ original_debit ให้เป็น NULL
    $sql_opd_clear = "UPDATE imr_tb_debtor_rights_opd SET original_debit = NULL WHERE accountcode IN ('1102050101.203')";
    $conn->query($sql_opd_clear);
    
    $sql_ipd_clear = "UPDATE imr_tb_debtor_rights_ipd SET original_debit = NULL WHERE accountcode IN ('1102050101.203')";
    $conn->query($sql_ipd_clear);
}

if (function_exists('system_log')) {
    $statusText = ($enabled === '1') ? 'เปิด' : 'ปิด';
    system_log($conn, 'ตั้งค่าลูกหนี้ (Settings)', 'UPDATE', "ผู้ใช้ปรับปรุงการตั้งค่าลูกหนี้ (สถานะ: $statusText, จำนวนเงิน: $amount)");
}

echo json_encode(['status' => 'success', 'message' => 'Settings updated successfully']);
exit();
?>
