<?php
require_once 'check_auth.php';
require_once './database_config/config.php';
require_once './database_config/db_helper.php';

// ป้องกันคนอื่นที่ไม่ใช่แอดมินเรียก API
checkRole(['admin']);

// ตั้งให้ส่งค่ากลับเป็น JSON
header('Content-Type: application/json');

// ป้องกัน Time Out เมื่อประมวลผลข้อมูลเยอะ
set_time_limit(0);
ini_set('memory_limit', '1024M');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ตรวจสอบข้อมูลที่ส่งมา
    $table_name = isset($_POST['table_name']) ? trim($_POST['table_name']) : '';
    $primary_key = isset($_POST['primary_key']) ? trim($_POST['primary_key']) : '';
    $target_column = isset($_POST['target_column']) ? trim($_POST['target_column']) : '';

    if (empty($table_name) || empty($primary_key) || empty($target_column)) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลที่ส่งมาไม่ครบถ้วน']);
        exit;
    }

    // Sanitize ป้องกัน SQL Injection จากชื่อคอลัมน์ (ไม่อนุญาตอักขระพิเศษ)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name) || 
        !preg_match('/^[a-zA-Z0-9_]+$/', $primary_key) || 
        !preg_match('/^[a-zA-Z0-9_]+$/', $target_column)) {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อตารางและคอลัมน์ต้องเป็นตัวอักษรภาษาอังกฤษ ตัวเลข หรือขีดล่าง(_) เท่านั้น']);
        exit;
    }

    // 1. ตรวจสอบว่าตารางนี้มีอยู่จริงหรือไม่
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
    if (mysqli_num_rows($check_table) == 0) {
        echo json_encode(['status' => 'error', 'message' => "ไม่พบตาราง '$table_name' ในฐานข้อมูล"]);
        exit;
    }

    // 2. ดึงข้อมูลทั้งหมดที่ยังไม่ได้เข้ารหัส
    // สมมติฐาน: ข้อมูลเข้ารหัส AES-256 Base64 มักจะยาวกว่าข้อมูลดิบมาก (เช่น CID 13 หลัก พอเข้ารหัสจะยาวเกิน 24 ตัวอักษร)
    // ตรงนี้เราเช็คคร่าวๆ ว่าถ้าน้อยกว่า 30 และไม่ใช่ค่าว่าง ถือว่าเป็น Plain Text
    // *หากเป้าหมายเป็นข้อความยาวอยู่แล้ว (เช่น ชื่อ) อาจจะต้องใช้วิธีเช็คแบบอื่น หรือดึงมาเช็คทีละตัว
    
    $sql = "SELECT `$primary_key`, `$target_column` FROM `$table_name` WHERE LENGTH(`$target_column`) < 30 AND `$target_column` != ''";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Query ผิดพลาด: ' . mysqli_error($conn)]);
        exit;
    }

    $total_rows = mysqli_num_rows($result);
    
    if ($total_rows == 0) {
        echo json_encode(['status' => 'success', 'message' => "ไม่พบข้อมูลที่ต้องเข้ารหัสในคอลัมน์ '$target_column' (หรือข้อมูลอาจถูกเข้ารหัสหมดแล้ว)"]);
        exit;
    }

    $count = 0;

    // 3. เริ่มวนลูปทีละรายการ
    while ($row = mysqli_fetch_assoc($result)) {
        $id_val = $row[$primary_key];
        $plain_text = trim($row[$target_column]);

        // ข้ามถ้าว่าง
        if (empty($plain_text)) continue;

        // เข้าสู่กระบวนการเข้ารหัส (Encrypt)
        $encrypted_text = encrypt_data($plain_text);

        // 4. อัปเดตข้อมูลกลับ
        $update_sql = "UPDATE `$table_name` SET `$target_column` = ? WHERE `$primary_key` = ?";
        
        $stmt = mysqli_prepare($conn, $update_sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $encrypted_text, $id_val);
            mysqli_stmt_execute($stmt);
            $count++;
            mysqli_stmt_close($stmt);
        }
    }

    echo json_encode([
        'status' => 'success', 
        'message' => "เข้ารหัสข้อมูลสำเร็จทั้งหมด " . number_format($count) . " รายการ จากตาราง $table_name"
    ]);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
}
?>
