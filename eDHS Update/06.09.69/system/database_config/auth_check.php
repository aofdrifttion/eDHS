<?php
// system/database_config/auth_check.php
// ตัวช่วยตรวจสอบสิทธิ์การเข้าถึงหน้าตั้งค่าฐานข้อมูล (Smart Setup Guard)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ตรวจสอบสิทธิ์การเข้าถึงหน้าตั้งค่าฐานข้อมูล
 * รองรับทั้ง:
 * 1. เข้าสู่ระบบด้วยสิทธิ์ Admin
 * 2. เข้าใช้งานจากเครื่อง Server โดยตรง (Localhost: 127.0.0.1, ::1)
 * 3. โหมดติดตั้งระบบใหม่ (Initial Setup Mode) เมื่อฐานข้อมูลยังไม่ได้ตั้งค่า หรือเชื่อมต่อไม่ได้
 * 
 * @return array ['allowed' => bool, 'is_setup_mode' => bool, 'reason' => string]
 */
function check_database_config_access() {
    // 1. เข้าสู่ระบบด้วยสิทธิ์ Admin
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return [
            'allowed' => true,
            'is_setup_mode' => false,
            'reason' => 'admin_authenticated'
        ];
    }

    // 2. เรียกจากเครื่อง Server โดยตรง (Localhost)
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (in_array($client_ip, ['127.0.0.1', '::1', 'localhost'])) {
        return [
            'allowed' => true,
            'is_setup_mode' => false,
            'reason' => 'localhost'
        ];
    }

    // 3. ตรวจสอบไฟล์ config.json หากยังไม่มีไฟล์หรือไม่มีการกำหนดค่า
    $configFile = __DIR__ . "/config.json";
    if (!file_exists($configFile)) {
        return [
            'allowed' => true,
            'is_setup_mode' => true,
            'reason' => 'missing_config'
        ];
    }

    $configData = @json_decode(file_get_contents($configFile), true);
    if (!is_array($configData) || empty($configData['db1']['servername']) || empty($configData['db1']['dbname'])) {
        return [
            'allowed' => true,
            'is_setup_mode' => true,
            'reason' => 'empty_config'
        ];
    }

    // 4. ตรวจสอบว่าสามารถเชื่อมต่อฐานข้อมูล DB1 (eDHS) ได้หรือไม่
    $db1 = $configData['db1'];
    $connected = false;

    // ปิด warning error เพื่อไม่ให้ throw exception ใน PHP 8.1+
    $driver = new mysqli_driver();
    $orig_report = $driver->report_mode;
    $driver->report_mode = MYSQLI_REPORT_OFF;

    try {
        $conn_test = mysqli_init();
        if ($conn_test) {
            // ตั้ง Timeout สั้น 2 วินาที เพื่อไม่ให้หน้าเว็บหน่วง
            $conn_test->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
            $connected = @$conn_test->real_connect(
                $db1['servername'],
                $db1['username'] ?? '',
                $db1['password'] ?? '',
                $db1['dbname']
            );
        }
    } catch (\Throwable $e) {
        $connected = false;
    }

    // หากต่อ DB1 ไม่ติด (เช่น ย้ายโฟลเดอร์มา รพ. อื่น IP/User เดิมใช้ไม่ได้)
    // ถือเป็น Initial Setup Mode ต้องอนุญาตให้เข้ามาตั้งค่าและทดสอบการเชื่อมต่อได้
    if (!$connected) {
        $driver->report_mode = $orig_report;
        return [
            'allowed' => true,
            'is_setup_mode' => true,
            'reason' => 'db_unreachable'
        ];
    }

    // 5. หากต่อ DB1 ติดแล้ว ให้เช็คว่ามีตาราง users และมีผู้ใช้ Admin หรือไม่
    try {
        $res = @$conn_test->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            $conn_test->close();
            $driver->report_mode = $orig_report;
            return [
                'allowed' => true,
                'is_setup_mode' => true,
                'reason' => 'no_admin_account'
            ];
        }
        $conn_test->close();
    } catch (\Throwable $e) {
        // กรณีตาราง users ยังไม่มี
        $conn_test->close();
        $driver->report_mode = $orig_report;
        return [
            'allowed' => true,
            'is_setup_mode' => true,
            'reason' => 'users_table_missing'
        ];
    }

    $driver->report_mode = $orig_report;

    // ฐานข้อมูลสมบูรณ์แล้ว แต่ไม่มีสิทธิ์ Admin และไม่ได้เข้าจาก Localhost
    return [
        'allowed' => false,
        'is_setup_mode' => false,
        'reason' => 'login_required'
    ];
}
