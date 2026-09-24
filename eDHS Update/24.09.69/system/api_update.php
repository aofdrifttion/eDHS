<?php
/**
 * eDHS Update API
 * รองรับการตรวจสอบและสั่งอัปเดตระบบผ่านหน้าเว็บ
 */
header('Content-Type: application/json; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// อนุญาตให้ทุกคนสามารถตรวจสอบและสั่งอัปเดตระบบได้ (เพื่อให้ระบบได้รับการอัปเดตอย่างทั่วถึง)
require_once __DIR__ . '/../update_core.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'check';
$force_param = $_POST['force'] ?? $_GET['force'] ?? null;
$force = ($force_param === '1' || $force_param === 1 || $force_param === true || $force_param === 'true');

if ($action === 'check') {
    $currentVersion = get_current_installed_version($baseDir);
    // ตรวจสอบทั้ง Remote GitHub และในเครื่อง
    $meta = get_latest_version_meta($baseDir, true);
    $latestVersion = $meta['latest_version'] ?? $currentVersion;

    // เช็คว่ามีเวอร์ชันใหม่หรือไม่
    $updateAvailable = false;
    if (!empty($latestVersion) && $latestVersion !== '00.00.00' && $latestVersion !== $currentVersion) {
        $updateAvailable = true;
    }

    $upcomingRelease = null;
    if ($updateAvailable) {
        $patchChangelog = $baseDir . '/eDHS Update/' . $latestVersion . '/system/database_config/changelog.json';
        if (file_exists($patchChangelog)) {
            $pData = json_decode(@file_get_contents($patchChangelog), true);
            if (!empty($pData['releases'])) {
                foreach ($pData['releases'] as $pr) {
                    if (isset($pr['version']) && $pr['version'] === $latestVersion) {
                        $upcomingRelease = $pr;
                        break;
                    }
                }
            }
        }
        if (!$upcomingRelease && !empty($meta)) {
            $changes = [];
            if (!empty($meta['changes']) && is_array($meta['changes'])) {
                $changes = $meta['changes'];
            } elseif (!empty($meta['changelog_summary']) && is_array($meta['changelog_summary'])) {
                foreach ($meta['changelog_summary'] as $sumText) {
                    $changes[] = [
                        'category' => 'improve',
                        'tag' => 'รายการปรับปรุง',
                        'color' => 'success',
                        'icon' => 'bx-check-circle',
                        'description' => $sumText
                    ];
                }
            }
            $upcomingRelease = [
                'version' => $latestVersion,
                'date'    => $meta['release_date'] ?? date('Y-m-d'),
                'title'   => $meta['title'] ?? "อัปเดตเวอร์ชัน $latestVersion",
                'changes' => $changes
            ];
        }
    }

    echo json_encode([
        'success'          => true,
        'current_version'  => $currentVersion,
        'latest_version'   => $latestVersion,
        'update_available' => $updateAvailable,
        'upcoming_release' => $upcomingRelease,
        'meta'             => $meta,
        'git_available'    => (find_git_binary() !== null)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'apply') {
    $targetVersion = $_POST['version'] ?? $_GET['version'] ?? null;

    // 1. ดึงข้อมูลล่าสุดจาก GitHub ผ่าน Git ก่อน (ถ้ามี Git)
    if (find_git_binary()) {
        git_pull_latest($baseDir);
    }

    // 2. ดำเนินการติดตั้งแพตช์ สำรองไฟล์ และอัปเดตระบบ (รันผ่าน Web API: $isCli = false)
    $result = execute_system_update($targetVersion, false, $force);

    // ล้าง Cache การตรวจสอบอัปเดต
    $cacheFile = $baseDir . '/system/logs/remote_update_cache.json';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Invalid action parameter'
], JSON_UNESCAPED_UNICODE);
