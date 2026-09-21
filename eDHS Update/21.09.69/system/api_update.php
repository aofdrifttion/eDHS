<?php
/**
 * eDHS Update API
 * รองรับการตรวจสอบและสั่งอัปเดตระบบผ่านหน้าเว็บ
 */
header('Content-Type: application/json; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบความปลอดภัย: ผู้ใช้ต้องเข้าสู่ระบบก่อน
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../update_core.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'check';

if ($action === 'check') {
    $currentVersion = get_current_installed_version($baseDir);
    $meta = get_latest_version_meta($baseDir);
    $latestVersion = $meta['latest_version'] ?? $currentVersion;

    // เช็คว่ามีเวอร์ชันใหม่หรือไม่
    $updateAvailable = false;
    if (!empty($latestVersion) && $latestVersion !== '00.00.00' && $latestVersion !== $currentVersion) {
        $updateAvailable = true;
    }

    echo json_encode([
        'success'          => true,
        'current_version'  => $currentVersion,
        'latest_version'   => $latestVersion,
        'update_available' => $updateAvailable,
        'meta'             => $meta
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'apply') {
    // ผู้สั่งอัปเดตควรเป็น Admin หรือมีสิทธิ์
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถสั่งอัปเดตระบบได้'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $targetVersion = $_POST['version'] ?? null;
    $result = execute_system_update($targetVersion, false);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Invalid action parameter'
], JSON_UNESCAPED_UNICODE);
