<?php
/**
 * eDHS Core Update Engine (Cross-Platform: Windows XAMPP & Linux)
 * ------------------------------------------------------------------
 * เครื่องมืออัปเดตระบบ eDHS อัตโนมัติ:
 * 1. ตรวจสอบเวอร์ชันล่าสุดจาก version.json หรือโฟลเดอร์ eDHS Update/
 * 2. สำรองข้อมูลไฟล์เดิมก่อนทำการเขียนทับอัตโนมัติ
 * 3. คัดลอกไฟล์แพตช์จาก eDHS Update/ เข้าสู่ระบบจริงอย่างปลอดภัย
 * 4. ข้ามไฟล์คอนฟิก (config.php, config.json) ไม่ให้โดนทับ 100%
 * 5. รันสคริปต์ SQL อัปเดตฐานข้อมูล (หากมีไฟล์ patch.sql)
 * 6. รองรับการทำงานทั้งผ่าน CLI (Terminal, .bat, .sh) และ Web Browser
 */

// ป้องกัน Direct Access หากเรียกผ่านเว็บโดยไม่มีการยืนยันตัวตน
$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
$baseDir = __DIR__;

// กำหนดรายการไฟล์และโฟลเดอร์ที่ห้ามเขียนทับเด็ดขาด (Security Whitelist)
$protectedFiles = [
    'config.php',
    'config.json',
    'database_config.php',
    '.env',
    '.git',
    '.gitignore',
    'update.bat',
    'update.sh',
    'update_core.php'
];

/**
 * ฟังก์ชันส่งข้อความแสดงผล (รองรับทั้ง CLI และ Web)
 */
function log_msg($message, $type = 'info', $isCli = true) {
    $timestamp = date('Y-m-d H:i:s');
    $colors = [
        'info'    => "\033[0;36m", // Cyan
        'success' => "\033[0;32m", // Green
        'warn'    => "\033[0;33m", // Yellow
        'error'   => "\033[0;31m", // Red
        'reset'   => "\033[0m"
    ];

    if ($isCli) {
        $color = $colors[$type] ?? $colors['info'];
        echo "[$timestamp] " . $color . $message . $colors['reset'] . PHP_EOL;
    }

    // บันทึกลง Log File
    $logDir = __DIR__ . '/system/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logDir . '/system_update.log', "[$timestamp] [$type] $message" . PHP_EOL, FILE_APPEND);
}

/**
 * ตรวจสอบเวอร์ชันปัจจุบันของระบบ
 */
function get_current_installed_version($baseDir) {
    $changelogPath = $baseDir . '/system/database_config/changelog.json';
    if (file_exists($changelogPath)) {
        $data = json_decode(@file_get_contents($changelogPath), true);
        if (!empty($data['current_version'])) {
            return $data['current_version'];
        }
    }
    return '00.00.00';
}

/**
 * สแกนหาแพตช์ที่มีในโฟลเดอร์ eDHS Update/
 */
function get_available_patch_folders($baseDir) {
    $updateDir = $baseDir . '/eDHS Update';
    if (!is_dir($updateDir)) {
        return [];
    }

    $folders = scandir($updateDir);
    $patches = [];
    foreach ($folders as $f) {
        if ($f === '.' || $f === '..') continue;
        $patchPath = $updateDir . '/' . $f;
        if (is_dir($patchPath)) {
            $patches[] = $f;
        }
    }

    // เรียงลำดับเวอร์ชัน (เวอร์ชันล่าสุดอยู่ท้ายสุด)
    natsort($patches);
    return array_values($patches);
}

/**
 * ดึงข้อมูลเวอร์ชันล่าสุดจาก version.json (หากมี)
 */
function get_latest_version_meta($baseDir) {
    $versionJsonPath = $baseDir . '/version.json';
    if (file_exists($versionJsonPath)) {
        $data = json_decode(@file_get_contents($versionJsonPath), true);
        if (!empty($data['latest_version'])) {
            return $data;
        }
    }
    
    // Fallback: ดูจากโฟลเดอร์ล่าสุดใน eDHS Update/
    $patches = get_available_patch_folders($baseDir);
    if (!empty($patches)) {
        $latest = end($patches);
        return [
            'latest_version' => $latest,
            'patch_folder'   => $latest,
            'release_date'   => date('Y-m-d'),
            'title'          => "Update Patch $latest"
        ];
    }

    return null;
}

/**
 * สำรองไฟล์เดิมก่อนเขียนทับ
 */
function backup_files_before_patch($sourceDir, $targetBaseDir, $backupDir, $protectedFiles, $isCli) {
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $backupCount = 0;
    foreach ($iterator as $item) {
        $subPath = substr($item->getPathname(), strlen($sourceDir) + 1);
        $targetPath = $targetBaseDir . '/' . $subPath;
        $backupPath = $backupDir . '/' . $subPath;

        // ข้ามไฟล์ที่ได้รับการปกป้อง
        $fileName = basename($subPath);
        if (in_array($fileName, $protectedFiles)) {
            continue;
        }

        if (file_exists($targetPath)) {
            if (is_dir($targetPath)) {
                if (!is_dir($backupPath)) {
                    @mkdir($backupPath, 0755, true);
                }
            } else {
                $dir = dirname($backupPath);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                @copy($targetPath, $backupPath);
                $backupCount++;
            }
        }
    }

    return $backupCount;
}

/**
 * คัดลอกไฟล์แพตช์เข้าสู่ระบบจริง
 */
function apply_patch_files($sourceDir, $targetBaseDir, $protectedFiles, $isCli) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $updatedFiles = [];
    $skippedFiles = [];

    foreach ($iterator as $item) {
        $subPath = substr($item->getPathname(), strlen($sourceDir) + 1);
        $targetPath = $targetBaseDir . '/' . $subPath;
        $fileName = basename($subPath);

        // ตรวจสอบความปลอดภัย: ห้ามเขียนทับไฟล์คอนฟิกเด็ดขาด
        if (in_array($fileName, $protectedFiles)) {
            $skippedFiles[] = $subPath;
            continue;
        }

        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                @mkdir($targetPath, 0755, true);
            }
        } else {
            // อย่าคัดลอกไฟล์ SQL ตรงๆ เข้าเว็บรูท (จะรันแยกต่างหาก)
            if (substr($fileName, -4) === '.sql') {
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            if (@copy($item->getPathname(), $targetPath)) {
                $updatedFiles[] = $subPath;
            }
        }
    }

    return [
        'updated' => $updatedFiles,
        'skipped' => $skippedFiles
    ];
}

/**
 * รันสคริปต์อัปเดตฐานข้อมูล (SQL Migration) หากมี
 */
function run_sql_patch_if_exists($patchDir, $baseDir, $isCli) {
    $sqlFiles = glob($patchDir . '/*.sql');
    if (empty($sqlFiles)) {
        return ['executed' => false, 'message' => 'ไม่มีสคริปต์ SQL ในแพตช์นี้'];
    }

    // โหลดคอนฟิกเชื่อมต่อฐานข้อมูล
    $configFile = $baseDir . '/system/database_config/config.php';
    if (!file_exists($configFile)) {
        return ['executed' => false, 'message' => 'ไม่พบไฟล์ config.php สำหรับเชื่อมต่อฐานข้อมูล'];
    }

    try {
        // นำเข้าคอนฟิกเพื่อใช้ $conn หรือ PDO
        require_once $configFile;

        $results = [];
        foreach ($sqlFiles as $sqlFile) {
            $sqlContent = @file_get_contents($sqlFile);
            if (empty(trim($sqlContent))) continue;

            $fileName = basename($sqlFile);
            log_msg("กำลังประมวลผลคำสั่งฐานข้อมูล: $fileName", 'info', $isCli);

            // แยกคำสั่งด้วยเครื่องหมาย ;
            $queries = explode(';', $sqlContent);
            $successCount = 0;
            $failCount = 0;

            foreach ($queries as $q) {
                $q = trim($q);
                if (empty($q)) continue;

                if (isset($conn) && $conn instanceof mysqli) {
                    if ($conn->query($q)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } elseif (isset($pdo) && $pdo instanceof PDO) {
                    try {
                        $pdo->exec($q);
                        $successCount++;
                    } catch (Exception $e) {
                        $failCount++;
                    }
                }
            }

            $results[] = [
                'file' => $fileName,
                'success' => $successCount,
                'fail' => $failCount
            ];
        }

        return ['executed' => true, 'details' => $results];
    } catch (Throwable $t) {
        return ['executed' => false, 'message' => 'เกิดข้อผิดพลาดในการรัน SQL: ' . $t->getMessage()];
    }
}

/**
 * ฟังก์ชันหลักในการรันอัปเดตระบบ
 */
function execute_system_update($targetVersion = null, $isCli = true) {
    global $baseDir, $protectedFiles;

    $currentVersion = get_current_installed_version($baseDir);
    $meta = get_latest_version_meta($baseDir);

    if (empty($targetVersion)) {
        $targetVersion = $meta['patch_folder'] ?? $meta['latest_version'] ?? null;
    }

    if (empty($targetVersion)) {
        log_msg("ไม่พบเวอร์ชันหรือโฟลเดอร์สำหรับอัปเดต", 'error', $isCli);
        return [
            'success' => false,
            'message' => 'ไม่พบเวอร์ชันหรือโฟลเดอร์สำหรับอัปเดต'
        ];
    }

    $patchDir = $baseDir . '/eDHS Update/' . $targetVersion;
    if (!is_dir($patchDir)) {
        log_msg("ไม่พบโฟลเดอร์แพตช์: $patchDir", 'error', $isCli);
        return [
            'success' => false,
            'message' => "ไม่พบโฟลเดอร์แพตช์: $targetVersion"
        ];
    }

    log_msg("---------------------------------------------------------", 'info', $isCli);
    log_msg("เริ่มต้นกระบวนการอัปเดตระบบ eDHS", 'info', $isCli);
    log_msg("เวอร์ชันปัจจุบันในระบบ: $currentVersion", 'info', $isCli);
    log_msg("เวอร์ชันที่กำลังติดตั้ง: $targetVersion", 'info', $isCli);
    log_msg("---------------------------------------------------------", 'info', $isCli);

    // 1. สำรองข้อมูลเดิม
    $timestamp = date('Ymd_His');
    $backupDir = $baseDir . '/system/backup/updates/backup_before_' . $targetVersion . '_' . $timestamp;
    log_msg("กำลังสำรองไฟล์ระบบเดิม...", 'info', $isCli);
    $backupCount = backup_files_before_patch($patchDir, $baseDir, $backupDir, $protectedFiles, $isCli);
    log_msg("สำรองไฟล์สำเร็จ: $backupCount ไฟล์ (เก็บไว้ที่: $backupDir)", 'success', $isCli);

    // 2. คัดลอกไฟล์แพตช์
    log_msg("กำลังติดตั้งไฟล์แพตช์ใหม่เข้าสู่ระบบ...", 'info', $isCli);
    $copyResult = apply_patch_files($patchDir, $baseDir, $protectedFiles, $isCli);
    $updatedCount = count($copyResult['updated']);
    $skippedCount = count($copyResult['skipped']);
    log_msg("คัดลอกไฟล์อัปเดตสำเร็จ: $updatedCount ไฟล์", 'success', $isCli);
    if ($skippedCount > 0) {
        log_msg("ข้ามไฟล์ที่ได้รับการคุ้มครองความปลอดภัย (config.php): $skippedCount ไฟล์", 'warn', $isCli);
    }

    // 3. รันสคริปต์ SQL (ถ้ามี)
    $sqlResult = run_sql_patch_if_exists($patchDir, $baseDir, $isCli);
    if ($sqlResult['executed']) {
        log_msg("อัปเดตโครงสร้างฐานข้อมูลเสร็จสิ้น", 'success', $isCli);
    }

    log_msg("=========================================================", 'success', $isCli);
    log_msg("การอัปเดตระบบเป็นเวอร์ชัน $targetVersion เสร็จสมบูรณ์ 100%!", 'success', $isCli);
    log_msg("=========================================================", 'success', $isCli);

    return [
        'success'         => true,
        'current_version' => $currentVersion,
        'updated_version' => $targetVersion,
        'updated_files'   => $updatedCount,
        'backup_dir'      => $backupDir,
        'sql_result'      => $sqlResult,
        'message'         => "อัปเดตระบบเป็นเวอร์ชัน $targetVersion เรียบร้อยแล้ว ($updatedCount ไฟล์)"
    ];
}

// =========================================================================
// ENTRY POINT (เมื่อรันผ่าน CLI หรือเรียกตรง)
// =========================================================================
if ($isCli) {
    $args = $_SERVER['argv'] ?? [];
    $action = $args[1] ?? '--cli';

    if ($action === '--check') {
        $cur = get_current_installed_version($baseDir);
        $meta = get_latest_version_meta($baseDir);
        $lat = $meta['latest_version'] ?? $cur;
        echo json_encode([
            'current_version'  => $cur,
            'latest_version'   => $lat,
            'update_available' => version_compare($lat, $cur, '>') || ($lat !== $cur && $lat !== '00.00.00'),
            'meta'             => $meta
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit(0);
    }

    // ค่าเริ่มต้น: ดำเนินการอัปเดต
    $target = isset($args[2]) ? $args[2] : null;
    $result = execute_system_update($target, true);
    exit($result['success'] ? 0 : 1);
}
