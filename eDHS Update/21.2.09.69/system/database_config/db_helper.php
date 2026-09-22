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

/**
 * แปลงรูปแบบเดือนให้เป็นมาตรฐาน mm-yyyy (พ.ศ.) เช่น '7-2026' -> '07-2569'
 */
if (!function_exists('normalize_monthtxt_be')) {
    function normalize_monthtxt_be($monthtxt) {
        if (is_array($monthtxt)) return '';
        $monthtxt = trim((string)$monthtxt);
        if (empty($monthtxt) || strtoupper($monthtxt) === 'ALL') return '';
        $clean = str_replace('/', '-', $monthtxt);
        $parts = explode('-', $clean);
        if (count($parts) === 2) {
            $m = intval($parts[0]);
            $y = intval($parts[1]);
            if ($y < 2400) $y += 543;
            return sprintf('%02d-%04d', $m, $y);
        }
        return $clean;
    }
}

/**
 * เปรียบเทียบเดือน 2 ค่าในรูปแบบตัวเลขอ้างอิง พ.ศ. (YYYYMM)
 *  -1 หาก $m1 < $m2
 *   0 หาก $m1 == $m2
 *   1 หาก $m1 > $m2
 */
if (!function_exists('compare_monthtxt_be')) {
    function compare_monthtxt_be($m1, $m2) {
        $n1 = normalize_monthtxt_be($m1);
        $n2 = normalize_monthtxt_be($m2);
        if (empty($n1) && empty($n2)) return 0;
        if (empty($n1)) return -1;
        if (empty($n2)) return 1;

        $p1 = explode('-', $n1);
        $p2 = explode('-', $n2);
        $v1 = (count($p1) === 2) ? (intval($p1[1]) * 100 + intval($p1[0])) : 0;
        $v2 = (count($p2) === 2) ? (intval($p2[1]) * 100 + intval($p2[0])) : 0;

        if ($v1 < $v2) return -1;
        if ($v1 > $v2) return 1;
        return 0;
    }
}
/**
 * จัดรูปแบบเลขที่ใบเสร็จให้อยู่ในมาตรฐาน 4 หลัก / 4 หลัก (0000/0000)
 * ตัวอย่าง: 22/22 -> 0022/0022, 1/32 -> 0001/0032, 043/025 -> 0043/0025
 */
if (!function_exists('format_debtor_bill_no')) {
    function format_debtor_bill_no($bill) {
        if ($bill === null) return '';
        $bill = trim((string)$bill);
        if ($bill === '') return '';
        if (strpos($bill, '/') !== false) {
            $parts = explode('/', $bill);
            $front = preg_replace('/\D/', '', $parts[0] ?? '');
            $back = preg_replace('/\D/', '', $parts[1] ?? '');
            if ($front === '' && $back === '') return '';
            $front_padded = str_pad($front, 4, '0', STR_PAD_LEFT);
            $back_padded = str_pad($back, 4, '0', STR_PAD_LEFT);
            return $front_padded . '/' . $back_padded;
        } else {
            $digits = preg_replace('/\D/', '', $bill);
            if ($digits === '') return $bill;
            if (strlen($digits) === 8) {
                return substr($digits, 0, 4) . '/' . substr($digits, 4, 4);
            }
            if (strlen($digits) <= 4) {
                return str_pad($digits, 4, '0', STR_PAD_LEFT) . '/0000';
            }
            return str_pad(substr($digits, 0, strlen($digits) - 4), 4, '0', STR_PAD_LEFT) . '/' . str_pad(substr($digits, -4), 4, '0', STR_PAD_LEFT);
        }
    }
}
/**
 * ตรวจสอบว่าเดือนที่ระบุ (monthtxt เช่น '7-2026', '08-2569') มีผลใช้งานระบบลูกหนี้ข้อตกลงจังหวัด (175 บาท) หรือไม่
 * รองรับ:
 * 1. โหมด 'ALL': ทุกเดือน
 * 2. โหมด 'SPECIFIC': เลือกเฉพาะเดือนที่กำหนดใน active_months (เช่น ['7-2026', '9-2026'] รองรับเดือนเว้นเดือน)
 * 3. โหมด 'START_FROM': กำหนดเดือนเริ่มต้น (effective_start_month) และเดือนสิ้นสุด (effective_end_month)
 */
if (!function_exists('is_debtor_setting_effective_for_month')) {
    function is_debtor_setting_effective_for_month($config, $monthtxt) {
        if (empty($config) || !is_array($config)) return false;
        $enabled = isset($config['enabled']) ? strval($config['enabled']) : '0';
        if ($enabled !== '1') return false;
        if (empty($monthtxt)) return false;

        $mode = isset($config['month_mode']) ? strtoupper(trim(strval($config['month_mode']))) : 'START_FROM';

        // 1. โหมดทุกเดือน
        if ($mode === 'ALL') {
            return true;
        }

        // 2. โหมดเลือกเฉพาะเดือน (Specific Months Checklist - รองรับเดือนเว้นเดือน)
        if ($mode === 'SPECIFIC') {
            $active_months = isset($config['active_months']) && is_array($config['active_months']) ? $config['active_months'] : [];
            if (empty($active_months)) return false;
            $norm_cur = normalize_monthtxt_be($monthtxt);
            foreach ($active_months as $am) {
                if (normalize_monthtxt_be($am) === $norm_cur) {
                    return true;
                }
            }
            return false;
        }

        // 3. โหมดกำหนดช่วงเดือน (START_FROM / Range)
        $start_m = isset($config['effective_start_month']) ? trim(strval($config['effective_start_month'])) : 'ALL';
        $end_m = isset($config['effective_end_month']) ? trim(strval($config['effective_end_month'])) : 'NONE';

        $is_after_start = (empty($start_m) || strtoupper($start_m) === 'ALL' || compare_monthtxt_be($monthtxt, $start_m) >= 0);
        $is_before_end = (empty($end_m) || strtoupper($end_m) === 'NONE' || compare_monthtxt_be($monthtxt, $end_m) <= 0);

        return ($is_after_start && $is_before_end);
    }
}

/**
 * แปลงวันที่ไทยหรือวันที่สากล (เช่น '22/09/2569', '22-09-2569', '2026-09-22') เป็นวันที่ SQL (YYYY-MM-DD ค.ศ.)
 * คืนค่า null หากไม่มีวันที่หรือรูปแบบไม่ถูกต้อง เพื่อนำไปบันทึกเป็น SQL NULL อย่างปลอดภัยใน MySQL Strict Mode
 */
if (!function_exists('parse_bill_date_to_sql')) {
    function parse_bill_date_to_sql($dateStr) {
        if ($dateStr === null) return null;
        $dateStr = trim((string)$dateStr);
        if ($dateStr === '' || $dateStr === '-' || $dateStr === '0000-00-00') return null;

        // รูปแบบ DD/MM/YYYY หรือ DD-MM-YYYY (พ.ศ. หรือ ค.ศ.)
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateStr, $m)) {
            $day = (int)$m[1];
            $month = (int)$m[2];
            $year = (int)$m[3];
            if ($year > 2400) {
                $year -= 543;
            }
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && $year > 1900) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // รูปแบบ YYYY-MM-DD หรือ YYYY/MM/DD
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $dateStr, $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
            $day = (int)$m[3];
            if ($year > 2400) {
                $year -= 543;
            }
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && $year > 1900) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }
}

/**
 * ตรวจสอบและสร้างตารางประวัติการแบ่งจ่ายชำระหนี้ (imr_tb_payment_history) อัตโนมัติ (Zero-Configuration Auto-Migration)
 * เพื่อรองรับการนำระบบไปใช้งานในโรงพยาบาลอื่นๆ หรือเซิร์ฟเวอร์ใหม่ (เช่น Ubuntu PHP 8.3 / MySQL 8.0) โดยไม่ต้องรัน SQL เอง
 */
if (!function_exists('ensure_payment_history_table')) {
    function ensure_payment_history_table($conn) {
        if (!$conn) return false;
        
        static $checked = false;
        if ($checked) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `imr_tb_payment_history` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `ref_vn_an` VARCHAR(50) NOT NULL COMMENT 'VN สำหรับ OPD หรือ AN สำหรับ IPD',
          `patient_type` ENUM('OPD','IPD') NOT NULL DEFAULT 'OPD',
          `accountcode` VARCHAR(50) DEFAULT NULL COMMENT 'รหัสผังบัญชี เช่น 1102050102.106',
          `pay_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดเงินที่ชำระในงวดนี้',
          `bill_no` VARCHAR(50) DEFAULT NULL COMMENT 'เลขที่ใบเสร็จ',
          `bill_date` DATE DEFAULT NULL COMMENT 'วันที่ตามใบเสร็จ (ค.ศ. YYYY-MM-DD)',
          `note` TEXT DEFAULT NULL COMMENT 'หมายเหตุการแบ่งจ่าย',
          `record_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_ref_vn_an` (`ref_vn_an`),
          KEY `idx_accountcode` (`accountcode`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        try {
            mysqli_query($conn, $sql);
            $checked = true;
            return true;
        } catch (\Throwable $e) {
            error_log("ensure_payment_history_table error: " . $e->getMessage());
            return false;
        }
    }
}
?>
