<?php
require_once __DIR__ . "/auth_check.php";
$access = check_database_config_access();
if (!$access['allowed']) {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $is_ajax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    $db1_server = trim($_POST["db1_servername"] ?? '');
    $db1_user   = trim($_POST["db1_username"] ?? '');
    $db1_pass   = $_POST["db1_password"] ?? '';
    $db1_name   = trim($_POST["db1_dbname"] ?? '');

    $db2_server = trim($_POST["db2_servername"] ?? '');
    $db2_user   = trim($_POST["db2_username"] ?? '');
    $db2_pass   = $_POST["db2_password"] ?? '';
    $db2_name   = trim($_POST["db2_dbname"] ?? '');

    // ทดสอบเชื่อมต่อทั้ง 2 ฐานข้อมูลก่อนบันทึก
    $driver = new mysqli_driver();
    $orig_report = $driver->report_mode;
    $driver->report_mode = MYSQLI_REPORT_OFF;

    $errors = [];

    // 1. ทดสอบ DB1 (eDHS)
    try {
        $test1 = mysqli_init();
        $test1->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        $conn1_ok = @$test1->real_connect($db1_server, $db1_user, $db1_pass, $db1_name);
        if (!$conn1_ok) {
            $errors[] = "❌ ฐานข้อมูลที่ 1 (eDHS): " . ($test1->connect_error ?: 'ไม่สามารถเชื่อมต่อได้');
        } else {
            $test1->close();
        }
    } catch (\Throwable $e) {
        $errors[] = "❌ ฐานข้อมูลที่ 1 (eDHS): " . $e->getMessage();
    }

    // 2. ทดสอบ DB2 (HOSxP)
    try {
        $test2 = mysqli_init();
        $test2->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        $conn2_ok = @$test2->real_connect($db2_server, $db2_user, $db2_pass, $db2_name);
        if (!$conn2_ok) {
            $errors[] = "❌ ฐานข้อมูลที่ 2 (HOSxP): " . ($test2->connect_error ?: 'ไม่สามารถเชื่อมต่อได้');
        } else {
            $test2->close();
        }
    } catch (\Throwable $e) {
        $errors[] = "❌ ฐานข้อมูลที่ 2 (HOSxP): " . $e->getMessage();
    }

    $driver->report_mode = $orig_report;

    // หากมีการเชื่อมต่อล้มเหลว ไม่อนุญาตให้เด้งไปหน้าหลัก ให้แจ้งเตือนข้อผิดพลาด
    if (!empty($errors)) {
        $error_msg = implode("\n", $errors);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => $error_msg
            ]);
            exit;
        } else {
            header("Location: index.php?error_conn=" . urlencode($error_msg));
            exit;
        }
    }

    // เมื่อเชื่อมต่อถูกต้องสมบูรณ์ทั้ง 2 ฐานแล้ว จึงบันทึกไฟล์ config.json
    $configFile = __DIR__ . "/config.json";
    $configData = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

    $configData["db1"] = [
        "servername" => $db1_server,
        "username" => $db1_user,
        "password" => $db1_pass,
        "dbname" => $db1_name
    ];
    
    $configData["db2"] = [
        "servername" => $db2_server,
        "username" => $db2_user,
        "password" => $db2_pass,
        "dbname" => $db2_name
    ];
    
    $configData["hospital"] = $_POST["hospital"] ?? '';
    $configData["hospcode"] = $_POST["hospcode"] ?? '';
    $configData["Client_ID"] = $_POST["Client_ID"] ?? '';
    $configData["Secret"] = $_POST["Secret"] ?? '';
    $configData["notify_enable"] = isset($_POST["notify_enable"]) ? $_POST["notify_enable"] : "0";

    file_put_contents(__DIR__ . "/config.json", json_encode($configData, JSON_PRETTY_PRINT));

    // เด้งไปหน้าหลักของระบบ (system/index.php)
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => 'เชื่อมต่อฐานข้อมูลถูกต้องและบันทึกการตั้งค่าเรียบร้อยแล้ว',
            'redirect' => '../index.php'
        ]);
        exit;
    } else {
        header("Location: index.php?saved=1&connected=1");
        exit;
    }
}
?>