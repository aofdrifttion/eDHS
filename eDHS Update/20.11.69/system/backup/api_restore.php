<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin Only)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'error' => 'Access Denied: คุณไม่มีสิทธิ์เข้าถึงส่วนนี้ (เฉพาะผู้ดูแลระบบ Admin เท่านั้น)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/backup_engine.php';

$configFile = __DIR__ . "/../database_config/config.json";
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบไฟล์ config.json'], JSON_UNESCAPED_UNICODE);
    exit;
}

$configData = json_decode(file_get_contents($configFile), true);
$dbConfig = $configData['db1'];

// เพิ่มเวลาการประมวลผลสำหรับไฟล์ขนาดใหญ่
set_time_limit(600);
ini_set('memory_limit', '1024M');

try {
    $backupEngine = new BackupEngine($dbConfig['servername'], $dbConfig['username'], $dbConfig['password'], $dbConfig['dbname']);
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action === 'verify_tables') {
        // ตรวจสอบสถานะความสมบูรณ์ของทุกตารางในฐานข้อมูล
        $result = $backupEngine->verifyDatabase($dbConfig);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'restore_history') {
        // นำเข้าจากไฟล์สำรองข้อมูลที่มีอยู่ในโฟลเดอร์ backups_data
        $filename = $_POST['filename'] ?? '';
        if (empty($filename)) {
            throw new Exception("ไม่ได้ระบุชื่อไฟล์สำรองข้อมูลที่ต้องการนำเข้า");
        }

        $res = $backupEngine->restoreBackup($filename, false, $dbConfig);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'restore_upload') {
        // นำเข้าจากไฟล์ที่อัปโหลดเข้ามา (.zip หรือ .sql)
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['backup_file']['error'] ?? 'UNKNOWN';
            throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Error code: {$errCode})");
        }

        $origName = $_FILES['backup_file']['name'];
        $tmpName = $_FILES['backup_file']['tmp_name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['zip', 'sql'])) {
            throw new Exception("รองรับเฉพาะไฟล์ .zip และ .sql เท่านั้น");
        }

        $uploadDir = __DIR__ . '/../backups_data/_upload_temp/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetFile = $uploadDir . 'uploaded_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($tmpName, $targetFile)) {
            throw new Exception("ไม่สามารถย้ายไฟล์ที่อัปโหลดไปยังโฟลเดอร์ชั่วคราวได้");
        }

        try {
            $res = $backupEngine->restoreBackup($targetFile, true, $dbConfig);
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            throw $e;
        }
        exit;
    }

    throw new Exception("คำสั่ง Action ไม่ถูกต้อง: " . htmlspecialchars($action));

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
