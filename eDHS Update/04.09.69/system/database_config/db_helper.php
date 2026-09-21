<?php
// system/database_config/db_helper.php

// โหลดค่า config ถ้ามีการรวมไฟล์นี้เข้ามา (เพื่อไม่ให้ซ้ำซ้อนกับ config.php)
if (!isset($configData)) {
    $config_file = __DIR__ . "/config.json";
    if (file_exists($config_file)) {
        $configData = json_decode(file_get_contents($config_file), true);
    }
}

// กำหนด Encryption Key และ Fixed IV (16 Bytes)
$encryption_key = isset($configData['encryption_key']) ? $configData['encryption_key'] : 'eDHS_SecretKey_2026_SecureKeyXYZ';
$encryption_iv = isset($configData['encryption_iv']) ? $configData['encryption_iv'] : '1234567890123456';

/**
 * เข้ารหัสข้อมูลด้วย AES-256-CBC (Fixed IV)
 * ใช้ Fixed IV เพื่อให้ข้อมูลเดียวกัน ได้ผลลัพธ์เหมือนเดิม (สามารถใช้ GROUP BY หรือค้นหา Exact Match ได้)
 */
function encrypt_data($data) {
    global $encryption_key, $encryption_iv;
    if (empty($data)) return $data;
    
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $encryption_iv);
    if ($encrypted === false) return $data;
    
    return base64_encode($encrypted);
}

/**
 * ถอดรหัสข้อมูลด้วย AES-256-CBC
 */
function decrypt_data($data) {
    global $encryption_key, $encryption_iv;
    if (empty($data)) return $data;
    
    // ลอง decode base64
    $decoded = base64_decode($data, true);
    if ($decoded === false) return $data; // ถ้าไม่ใช่ base64 แสดงว่าข้อมูลเก่าที่ยังไม่เข้ารหัส
    
    $decrypted = openssl_decrypt($decoded, 'aes-256-cbc', $encryption_key, 0, $encryption_iv);
    
    // ถ้าถอดรหัสไม่ได้ (อาจเป็นข้อมูลเก่า) ให้คืนค่าเดิมกลับไป
    return $decrypted !== false ? $decrypted : $data;
}

/**
 * ฟังก์ชันสำหรับรันคำสั่ง SQL ด้วย Prepared Statement เพื่อป้องกัน SQL Injection
 * ตัวอย่าง: execute_query($conn, "SELECT * FROM users WHERE id = ?", "i", [$id]);
 */
function execute_query($conn, $sql, $types = "", $params = []) {
    // ถ้าไม่มีพารามิเตอร์ ให้ใช้ query ปกติ
    if (empty($params) || empty($types)) {
        return mysqli_query($conn, $sql);
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    // กรณีเป็น INSERT, UPDATE, DELETE จะได้ $result = false แต่รันสำเร็จ
    if ($result === false) {
        if (mysqli_stmt_errno($stmt) === 0) {
            return true;
        } else {
            return false;
        }
    }
    
    return $result;
}

/**
 * แปลงค่าตัวเลขหรือสตริงให้เป็น float ที่ปลอดภัยจากค่าว่าง, null, จุลภาค หรืออักขระที่ไม่ใช่ตัวเลข
 */
if (!function_exists('cleanNum')) {
    function cleanNum($val) {
        if ($val === null || $val === '') return 0.0;
        if (is_numeric($val)) return (float)$val;
        if (is_string($val)) {
            $val = trim(str_replace([',', ' '], '', $val));
            if (is_numeric($val)) return (float)$val;
        }
        return 0.0;
    }
}

/**
 * ฟอร์แมตตัวเลขสกุลเงินอย่างปลอดภัย
 */
if (!function_exists('formatMoney')) {
    function formatMoney($val, $decimals = 2) {
        return number_format(cleanNum($val), $decimals);
    }
}
?>
