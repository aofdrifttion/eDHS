<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ตรวจสอบสิทธิ์ Admin และ redirect กลับไปหน้า login หากไม่มีสิทธิ์
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<script>alert('Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href='../login.php';</script>");
}

require_once 'backup_engine.php';

$configFile = __DIR__ . "/../database_config/config.json";
if (!file_exists($configFile)) {
    die("ไม่พบไฟล์ config.json");
}
$configData = json_decode(file_get_contents($configFile), true);

$dbConfig = $configData['db1']; // Default backup DB1
$backupEngine = new BackupEngine($dbConfig['servername'], $dbConfig['username'], $dbConfig['password'], $dbConfig['dbname']);

$message = '';
$msgType = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'backup') {
        try {
            $filename = $backupEngine->generateBackup();
            $message = "สำรองข้อมูลสำเร็จเรียบร้อย: " . $filename;
            $msgType = 'success';
        } catch (Exception $e) {
            $message = "เกิดข้อผิดพลาดในการสำรองข้อมูล: " . $e->getMessage();
            $msgType = 'danger';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['filename'])) {
        if ($backupEngine->deleteBackup($_POST['filename'])) {
            $message = "ลบไฟล์สำรองข้อมูลสำเร็จเรียบร้อย";
            $msgType = 'success';
        } else {
            $message = "ไม่สามารถลบไฟล์สำรองข้อมูลได้";
            $msgType = 'danger';
        }
    }
}

$backups = $backupEngine->listBackups();

// คำนวณข้อมูลสรุป (KPI)
$totalBackups = count($backups);
$totalSize = 0;
$latestBackupDate = null;

if (!empty($backups)) {
    foreach ($backups as $b) {
        $totalSize += $b['size'];
    }
    $latestBackupDate = $backups[0]['date'];
}

// ฟังก์ชันแปลงขนาดไฟล์ให้อ่านง่าย
function formatBytes($bytes, $precision = 2) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, $precision) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, $precision) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' Bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบสำรองข้อมูลฐานข้อมูล - eDHS</title>
    
    <!-- Google Fonts: Noto Sans Thai -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Boxicons CSS -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ==========================================================================
           eDHS System Font: Noto Sans Thai
           ========================================================================== */
        * {
            font-family: 'Noto Sans Thai', sans-serif !important;
            box-sizing: border-box;
        }

        /* Protect Boxicons and Font Awesome */
        i.bx, i.bxs, i.bx-fw, [class^="bx-"], [class*=" bx-"], .bx {
            font-family: 'boxicons' !important;
            font-style: normal;
        }
        i.fa, i.fas, i.far, i.fal, i.fad, i.fab, [class^="fa-"], [class*=" fa-"], .fa, .fas {
            font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
            font-style: normal;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: #1e293b;
            min-height: 100vh;
            padding: 24px 16px 40px 16px;
        }

        .backup-wrapper {
            max-width: 1120px;
            margin: 0 auto;
        }

        /* Main Container Card */
        .backup-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Header Bar */
        .backup-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: #ffffff;
            padding: 26px 32px;
            position: relative;
            overflow: hidden;
        }
        .backup-header::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            pointer-events: none;
        }

        .backup-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            position: relative;
            z-index: 2;
        }

        .backup-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .backup-header-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            flex-shrink: 0;
        }

        .backup-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
        }

        .backup-subtitle {
            font-size: 0.88rem;
            margin: 4px 0 0 0;
            color: rgba(255, 255, 255, 0.8);
        }

        .backup-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .backup-badge-engine {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
        }

        /* Card Body */
        .backup-body {
            padding: 30px 32px 32px 32px;
        }

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 26px;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
            transition: all 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }

        .kpi-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .kpi-icon-db {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
        }
        .kpi-icon-files {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #059669;
        }
        .kpi-icon-size {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }
        .kpi-icon-date {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            color: #7c3aed;
        }

        .kpi-info-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 2px;
        }
        .kpi-info-value {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
            word-break: break-all;
        }

        /* Action Hero Banner */
        .backup-hero-box {
            background: linear-gradient(135deg, #eff6ff 0%, #f8faff 50%, #ffffff 100%);
            border: 1px solid #bfdbfe;
            border-radius: 16px;
            padding: 26px 28px;
            margin-bottom: 30px;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 16px;
            max-width: 650px;
        }

        .hero-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.18);
        }

        .hero-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .hero-desc {
            font-size: 13.5px;
            color: #475569;
            margin: 0;
            line-height: 1.5;
        }

        .btn-start-backup {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            padding: 13px 30px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 9px;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .btn-start-backup:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
            transform: translateY(-2px);
            color: #ffffff;
        }

        /* History Table Header */
        .table-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-search-box {
            position: relative;
            min-width: 260px;
        }
        .table-search-box input {
            padding-left: 36px;
            border-radius: 10px;
            font-size: 13.5px;
            border: 1px solid #cbd5e1;
        }
        .table-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 17px;
        }

        /* Table Design */
        .table-custom-wrapper {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
        }

        .table-custom {
            margin-bottom: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom thead th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 18px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .table-custom tbody tr {
            transition: background-color 0.15s ease;
        }
        .table-custom tbody tr:hover {
            background-color: #f1f5f9;
        }

        .table-custom tbody td {
            padding: 14px 18px;
            vertical-align: middle;
            font-size: 13.5px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tbody tr:last-child td {
            border-bottom: none;
        }

        .filename-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #0f172a;
        }

        .zip-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #e0f2fe;
            color: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .badge-size {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-date {
            color: #64748b;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Action buttons */
        .btn-action-download {
            background: #ecfdf5;
            color: #059669 !important;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-action-download:hover {
            background: #059669;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
            transform: translateY(-1px);
        }

        .btn-action-delete {
            background: #fff1f2;
            color: #e11d48 !important;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-action-delete:hover {
            background: #e11d48;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(225, 29, 72, 0.25);
            transform: translateY(-1px);
        }

        /* Empty State */
        .empty-state {
            padding: 48px 24px;
            text-align: center;
        }
        .empty-state-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: #f1f5f9;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 14px auto;
        }
        .empty-state-title {
            font-size: 16px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }
        .empty-state-text {
            font-size: 13.5px;
            color: #94a3b8;
            margin: 0;
        }

        /* Footer */
        .backup-footer {
            text-align: center;
            padding-top: 24px;
            color: #64748b;
            font-size: 12.5px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="backup-wrapper">
    <div class="backup-card">
        
        <!-- Header -->
        <div class="backup-header">
            <div class="backup-header-content">
                <div class="backup-header-left">
                    <div class="backup-header-icon">
                        <i class="bx bx-server"></i>
                    </div>
                    <div>
                        <h4 class="backup-title">ระบบสำรองข้อมูลฐานข้อมูล</h4>
                        <p class="backup-subtitle">จัดการสำรองและบีบอัดฐานข้อมูล eDHS (.zip) เพื่อความปลอดภัยของข้อมูลโรงพยาบาล</p>
                    </div>
                </div>
                <div class="backup-header-actions">
                    <span class="backup-badge-engine">
                        <i class="bx bx-shield-quarter"></i> eDHS Backup Engine
                    </span>
                    <a href="../index.php" class="btn-back">
                        <i class="bx bx-arrow-back"></i> กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="backup-body">

            <!-- KPI Summary Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-db">
                        <i class="bx bx-data"></i>
                    </div>
                    <div>
                        <div class="kpi-info-label">ฐานข้อมูลเป้าหมาย</div>
                        <div class="kpi-info-value"><?= htmlspecialchars($dbConfig['dbname']); ?></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-files">
                        <i class="bx bx-file-blank"></i>
                    </div>
                    <div>
                        <div class="kpi-info-label">จำนวนไฟล์สำรอง</div>
                        <div class="kpi-info-value"><?= number_format($totalBackups); ?> ไฟล์</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-size">
                        <i class="bx bx-pie-chart-alt-2"></i>
                    </div>
                    <div>
                        <div class="kpi-info-label">ขนาดไฟล์รวมทั้งหมด</div>
                        <div class="kpi-info-value"><?= formatBytes($totalSize); ?></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-date">
                        <i class="bx bx-time-five"></i>
                    </div>
                    <div>
                        <div class="kpi-info-label">สำรองข้อมูลล่าสุด</div>
                        <div class="kpi-info-value">
                            <?= $latestBackupDate ? date('d/m/Y H:i', $latestBackupDate) : 'ยังไม่มีข้อมูล'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Action Banner -->
            <div class="backup-hero-box">
                <div class="hero-left">
                    <div class="hero-icon">
                        <i class="bx bx-cloud-upload"></i>
                    </div>
                    <div>
                        <h5 class="hero-title">สร้างไฟล์สำรองข้อมูลชุดใหม่ (Create Backup)</h5>
                        <p class="hero-desc">
                            ระบบจะทำการดึงโครงสร้างตารางและข้อมูลทั้งหมดของฐานข้อมูล 
                            <strong><?= htmlspecialchars($dbConfig['dbname']); ?></strong> 
                            บนเซิร์ฟเวอร์ <?= htmlspecialchars($dbConfig['servername']); ?> และบีบอัดเป็นไฟล์ .zip อัตโนมัติ
                        </p>
                    </div>
                </div>
                <div>
                    <form method="post" id="backupForm" class="m-0">
                        <input type="hidden" name="action" value="backup">
                        <button type="button" onclick="confirmBackup('<?= htmlspecialchars($dbConfig['dbname']) ?>')" class="btn-start-backup">
                            <i class="bx bx-cloud-download fs-5"></i> เริ่มสำรองข้อมูลทันที
                        </button>
                    </form>
                </div>
            </div>

            <!-- History Table Section -->
            <div class="table-section-header">
                <h5 class="table-section-title">
                    <i class="bx bx-history text-primary"></i> ประวัติการสำรองข้อมูล (Backup History)
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-2" style="font-size: 11.5px;">
                        <?= $totalBackups ?> รายการ
                    </span>
                </h5>
                <div class="table-search-box">
                    <i class="bx bx-search table-search-icon"></i>
                    <input type="text" id="backupSearchInput" class="form-control" placeholder="ค้นหาชื่อไฟล์สำรอง..." onkeyup="filterBackupTable()">
                </div>
            </div>

            <div class="table-custom-wrapper">
                <table class="table table-custom" id="backupTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;" class="text-center">#</th>
                            <th>ชื่อไฟล์สำรอง</th>
                            <th style="width: 140px;">ขนาดไฟล์</th>
                            <th style="width: 200px;">วันที่และเวลาที่สร้าง</th>
                            <th style="width: 220px;" class="text-end">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bx bx-folder-open"></i>
                                        </div>
                                        <div class="empty-state-title">ยังไม่มีไฟล์สำรองข้อมูล</div>
                                        <p class="empty-state-text">กดปุ่ม "เริ่มสำรองข้อมูลทันที" ด้านบนเพื่อสร้างไฟล์สำรองข้อมูลชุดแรก</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $index => $backup): ?>
                                <tr class="backup-row">
                                    <td class="text-center text-muted fw-semibold"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="filename-cell">
                                            <div class="zip-icon">
                                                <i class="bx bxs-file-archive"></i>
                                            </div>
                                            <div>
                                                <div class="filename-text"><?= htmlspecialchars($backup['name']) ?></div>
                                                <small class="text-muted" style="font-size: 11px;">ZIP Archive Format</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-size">
                                            <?= formatBytes($backup['size']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-date">
                                            <i class="bx bx-calendar-event text-primary"></i>
                                            <?= date('d/m/Y H:i:s', $backup['date']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="download.php?file=<?= urlencode($backup['name']) ?>" class="btn-action-download" title="ดาวน์โหลดไฟล์สำรอง">
                                                <i class="bx bx-download"></i> ดาวน์โหลด
                                            </a>
                                            <button type="button" class="btn-action-delete" onclick="confirmDelete('<?= htmlspecialchars($backup['name'], ENT_QUOTES) ?>')" title="ลบไฟล์">
                                                <i class="bx bx-trash"></i> ลบ
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="backup-footer">
        ระบบบริหารลูกหนี้โรงพยาบาลอิเล็กทรอนิกส์ eDebtor Hospital System (eDHS)<br>
        <small>© 2004 Project Application & Code Application by นายจิรันธนิน ประสารกุลนันท์ นักวิชาการคอมพิวเตอร์</small>
    </footer>
</div>

<!-- Hidden Delete Form -->
<form id="deleteForm" method="post" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="filename" id="deleteFilename">
</form>

<script>
    // Confirmation before starting backup
    function confirmBackup(dbName) {
        Swal.fire({
            title: 'ยืนยันการสำรองข้อมูล?',
            text: "ระบบจะทำการสำรองข้อมูลฐานข้อมูลหลัก (" + dbName + ") และบีบอัดเป็นไฟล์ ZIP",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="bx bx-check"></i> ใช่, เริ่มสำรองข้อมูล',
            cancelButtonText: 'ยกเลิก',
            customClass: {
                popup: 'rounded-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังสำรองข้อมูล...',
                    html: `
                        <div class="py-2">
                            <p class="text-muted mb-2">กรุณารอสักครู่ ระบบกำลัง Dump ข้อมูลและสร้างไฟล์ .zip</p>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2" style="font-size: 12.5px;">
                                <i class="bx bx-error-circle"></i> กรุณาอย่าปิดหรือรีเฟรชหน้าต่างนี้จนกว่าจะเสร็จสิ้น!
                            </span>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'rounded-4'
                    },
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                document.getElementById('backupForm').submit();
            }
        });
    }

    // Confirmation before deleting backup file
    function confirmDelete(filename) {
        Swal.fire({
            title: 'ยืนยันการลบไฟล์?',
            html: `ต้องการลบไฟล์ <strong>${filename}</strong> ใช่หรือไม่?<br><small class="text-danger">เมื่อลบแล้วจะไม่สามารถกู้คืนไฟล์นี้ได้</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="bx bx-trash"></i> ใช่, ลบไฟล์',
            cancelButtonText: 'ยกเลิก',
            customClass: {
                popup: 'rounded-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteFilename').value = filename;
                document.getElementById('deleteForm').submit();
            }
        });
    }

    // Live search filter for backup table
    function filterBackupTable() {
        const input = document.getElementById('backupSearchInput');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('#backupTable tbody tr.backup-row');

        rows.forEach(row => {
            const filenameEl = row.querySelector('.filename-text');
            if (filenameEl) {
                const text = filenameEl.textContent || filenameEl.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }

    // Show Message Toast on page load if message exists
    <?php if (!empty($message)): ?>
        Swal.fire({
            icon: '<?= $msgType === "success" ? "success" : "error" ?>',
            title: '<?= $msgType === "success" ? "ดำเนินการสำเร็จ" : "เกิดข้อผิดพลาด" ?>',
            text: '<?= addslashes($message) ?>',
            timer: 3000,
            showConfirmButton: false,
            customClass: {
                popup: 'rounded-4'
            }
        });
    <?php endif; ?>
</script>

</body>
</html>
