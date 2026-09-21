<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require './database_config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนทำการแก้ไขข้อมูล']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $fullname = htmlspecialchars(trim($_POST['fullname']), ENT_QUOTES, 'UTF-8');
    $new_password = trim($_POST['new_password']);
    $new_pin = trim($_POST['new_pin']);

    if (empty($fullname)) {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อ-นามสกุล ห้ามเว้นว่าง']);
        exit();
    }

    $updates = [];
    $types = "";
    $params = [];

    // เพิ่มชื่อ-นามสกุลเข้าในอัปเดต
    $updates[] = "fullname = ?";
    $types .= "s";
    $params[] = $fullname;

    // ตรวจสอบว่าต้องการเปลี่ยนรหัสผ่านหรือไม่
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updates[] = "password = ?";
        $types .= "s";
        $params[] = $hashed_password;
    }

    // ตรวจสอบว่าต้องการเปลี่ยนรหัส PIN หรือไม่
    if (!empty($new_pin)) {
        if (strlen($new_pin) === 6 && is_numeric($new_pin)) {
            $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
            $updates[] = "pin_code = ?";
            $types .= "s";
            $params[] = $hashed_pin;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'รหัส PIN ต้องเป็นตัวเลข 6 หลักเท่านั้น']);
            exit();
        }
    }

    // เพิ่ม user_id สำหรับเงื่อนไข WHERE
    $types .= "i";
    $params[] = $user_id;

    $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            // อัปเดตข้อมูลใน Session
            $_SESSION['fullname'] = $fullname;
            
            // เพิ่ม Audit Log บันทึกการแก้ไขโปรไฟล์
            if (function_exists('system_log')) {
                system_log($conn, 'โปรไฟล์ผู้ใช้ (Profile)', 'UPDATE', 'ผู้ใช้ทำการอัปเดตข้อมูลส่วนตัว (ชื่อ-นามสกุล/รหัสผ่าน/PIN)');
            }

            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเตรียมคำสั่ง SQL ได้']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
