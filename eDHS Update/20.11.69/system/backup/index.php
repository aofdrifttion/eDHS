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
    <title>ระบบสำรองและนำเข้าข้อมูลฐานข้อมูล - eDHS</title>
    
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
            width: 90%;
            max-width: 90%;
            margin: 0 auto;
        }

        @media (max-width: 992px) {
            .backup-wrapper {
                width: 96%;
                max-width: 96%;
            }
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
        .kpi-icon-audit {
            background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
            color: #0f766e;
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
            max-width: 600px;
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

        .hero-actions-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-start-backup {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .btn-start-backup:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .btn-open-upload {
            background: #ffffff;
            color: #4338ca;
            border: 1.5px solid #c7d2fe;
            padding: 11px 20px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.08);
        }
        .btn-open-upload:hover {
            background: #eef2ff;
            color: #3730a3;
            border-color: #a5b4fc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.16);
        }

        .btn-verify-header {
            background: #ffffff;
            color: #0d9488;
            border: 1.5px solid #99f6e4;
            padding: 11px 20px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.08);
        }
        .btn-verify-header:hover {
            background: #f0fdfa;
            color: #0f766e;
            border-color: #5eead4;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.16);
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
            background-color: #f8fafc;
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

        .sql-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #fef3c7;
            color: #d97706;
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
        .btn-action-restore {
            background: #eef2ff;
            color: #4338ca !important;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            white-space: nowrap !important;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        .btn-action-restore:hover {
            background: #4338ca;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(67, 56, 202, 0.25);
            transform: translateY(-1px);
        }

        .btn-action-download {
            background: #ecfdf5;
            color: #059669 !important;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 6px 13px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            white-space: nowrap !important;
            flex-shrink: 0;
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
            padding: 6px 13px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            white-space: nowrap !important;
            flex-shrink: 0;
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

        /* Upload Dropzone */
        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 36px 20px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .upload-dropzone:hover, .upload-dropzone.dragover {
            border-color: #4338ca;
            background: #f5f7ff;
        }
        .dropzone-icon {
            font-size: 48px;
            color: #6366f1;
            margin-bottom: 8px;
        }
        .file-selected-box {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
        }

        /* Verification Modal Styles */
        .audit-hero-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .audit-hero-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .audit-hero-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #16a34a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
            flex-shrink: 0;
        }
        .audit-chips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .audit-chip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }
        .audit-chip-label {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .audit-chip-value {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Modal width 90% */
        @media (min-width: 1200px) {
            #modalVerifyAudit .modal-xl {
                max-width: 90% !important;
                width: 90% !important;
            }
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
                        <h4 class="backup-title">ระบบสำรองและนำเข้าข้อมูลฐานข้อมูล (Backup & Restore Engine)</h4>
                        <p class="backup-subtitle">จัดการสำรองข้อมูล, นำเข้าฐานข้อมูลเข้าสู่เซิร์ฟเวอร์ (.zip, .sql) และตรวจสอบความสมบูรณ์ทุกตาราง</p>
                    </div>
                </div>
                <div class="backup-header-actions">
                    <span class="backup-badge-engine">
                        <i class="bx bx-shield-quarter"></i> eDHS Backup Engine v2.0
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
                        <div class="kpi-info-value"><?= htmlspecialchars($dbConfig['dbname']); ?> <small class="text-muted">(<?= htmlspecialchars($dbConfig['servername']); ?>)</small></div>
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

                <div class="kpi-card" role="button" onclick="runTableVerification()" style="cursor: pointer;" title="คลิกเพื่อตรวจสอบสถานะตารางฐานข้อมูลทันที">
                    <div class="kpi-icon-box kpi-icon-audit">
                        <i class="bx bx-check-shield"></i>
                    </div>
                    <div>
                        <div class="kpi-info-label">ความสมบูรณ์ฐานข้อมูล</div>
                        <div class="kpi-info-value text-teal d-flex align-items-center gap-1" style="color: #0f766e;">
                            ตรวจสอบตาราง <i class="bx bx-right-arrow-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Action Banner -->
            <div class="backup-hero-box">
                <div class="hero-left">
                    <div class="hero-icon">
                        <i class="bx bx-sync"></i>
                    </div>
                    <div>
                        <h5 class="hero-title">จัดการสำรองและนำเข้าฐานข้อมูล (Backup & Restore Center)</h5>
                        <p class="hero-desc">
                            สำรองข้อมูลฐานข้อมูล <strong><?= htmlspecialchars($dbConfig['dbname']); ?></strong> บนเซิร์ฟเวอร์ <?= htmlspecialchars($dbConfig['servername']); ?> 
                            หรือนำเข้าไฟล์สำรองข้อมูล (.zip, .sql) เข้าสู่เซิร์ฟเวอร์ พร้อมระบบตรวจสอบความถูกต้องทุกตาราง
                        </p>
                    </div>
                </div>
                <div class="hero-actions-group">
                    <form method="post" id="backupForm" class="m-0 d-inline">
                        <input type="hidden" name="action" value="backup">
                        <button type="button" onclick="confirmBackup('<?= htmlspecialchars($dbConfig['dbname']) ?>')" class="btn-start-backup">
                            <i class="bx bx-cloud-download fs-5"></i> สำรองข้อมูลชุดใหม่
                        </button>
                    </form>

                    <button type="button" class="btn-open-upload" data-bs-toggle="modal" data-bs-target="#modalUploadRestore">
                        <i class="bx bx-cloud-upload fs-5"></i> นำเข้าไฟล์สำรอง
                    </button>

                    <button type="button" class="btn-verify-header" onclick="runTableVerification()">
                        <i class="bx bx-check-shield fs-5"></i> ตรวจสอบตาราง
                    </button>
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
                            <th style="width: 50px;" class="text-center">#</th>
                            <th>ชื่อไฟล์สำรอง</th>
                            <th style="width: 130px;">ขนาดไฟล์</th>
                            <th style="width: 190px;">วันที่และเวลาที่สร้าง</th>
                            <th style="width: 380px; min-width: 380px;" class="text-end">การจัดการ</th>
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
                                        <p class="empty-state-text">กดปุ่ม "สำรองข้อมูลชุดใหม่" ด้านบนเพื่อสร้างไฟล์สำรองข้อมูลชุดแรก หรืออัปโหลดไฟล์สำรองเข้ามา</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $index => $backup): ?>
                                <tr class="backup-row">
                                    <td class="text-center text-muted fw-semibold"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="filename-cell">
                                            <div class="<?= ($backup['type'] === 'sql') ? 'sql-icon' : 'zip-icon' ?>">
                                                <i class="bx <?= ($backup['type'] === 'sql') ? 'bxs-file-doc' : 'bxs-file-archive' ?>"></i>
                                            </div>
                                            <div>
                                                <div class="filename-text"><?= htmlspecialchars($backup['name']) ?></div>
                                                <small class="text-muted" style="font-size: 11px;">
                                                    <?= ($backup['type'] === 'sql') ? 'Standard SQL Dump' : 'ZIP Compressed Archive' ?>
                                                </small>
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
                                    <td class="text-end" style="white-space: nowrap;">
                                        <div class="d-inline-flex align-items-center flex-nowrap gap-2">
                                            <button type="button" class="btn-action-restore" onclick="confirmRestoreFromHistory('<?= htmlspecialchars($backup['name'], ENT_QUOTES) ?>', '<?= formatBytes($backup['size']) ?>')" title="นำเข้าฐานข้อมูลนี้เข้าสู่เซิร์ฟเวอร์">
                                                <i class="bx bx-history"></i> นำเข้าสู่ Server
                                            </button>
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

<!-- =========================================================================
     MODAL 1: อัปโหลดและนำเข้าไฟล์สำรองข้อมูล (Upload & Restore Database)
     ========================================================================= -->
<div class="modal fade" id="modalUploadRestore" tabindex="-1" aria-labelledby="modalUploadRestoreLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #ffffff; padding: 20px 24px;">
                <div class="d-flex align-items-center gap-12">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 22px;">
                        <i class="bx bx-cloud-upload"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold m-0" id="modalUploadRestoreLabel" style="font-size: 1.15rem;">นำเข้าไฟล์สำรองข้อมูลสู่เซิร์ฟเวอร์</h5>
                        <small style="color: rgba(255,255,255,0.75);">อัปโหลดไฟล์สำรองข้อมูล (.zip หรือ .sql) เข้าสู่เซิร์ฟเวอร์</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Target Server Box -->
                <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small fw-semibold">เซิร์ฟเวอร์เป้าหมาย (Target Host):</span>
                        <span class="badge bg-dark fw-normal"><?= htmlspecialchars($dbConfig['servername']) ?></span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">ฐานข้อมูล (Database):</span>
                        <span class="badge bg-primary fw-normal"><?= htmlspecialchars($dbConfig['dbname']) ?></span>
                    </div>
                    <div class="small text-muted" style="font-size: 12px; line-height: 1.4;">
                        <i class="bx bx-info-circle text-primary"></i> <strong>ระบบรองรับ 2 รูปแบบ:</strong> 1) นำเข้าทับตารางเดิม (Clean Overwrite) หรือ 2) นำเข้าสู่ฐานข้อมูลว่างที่ยังไม่มีตาราง (สร้างโครงสร้างใหม่ทั้งหมด)
                    </div>
                </div>

                <!-- Dropzone Area -->
                <form id="uploadRestoreForm" enctype="multipart/form-data">
                    <input type="file" id="backupFileInput" name="backup_file" accept=".zip,.sql" style="display: none;" onchange="handleFileSelect(this)">
                    
                    <div class="upload-dropzone" id="dropzone" onclick="document.getElementById('backupFileInput').click()">
                        <div class="dropzone-icon">
                            <i class="bx bx-cloud-upload"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">คลิกเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</h6>
                        <p class="text-muted small mb-0">รองรับไฟล์ฐานข้อมูลนามสกุล <strong>.zip</strong> หรือ <strong>.sql</strong></p>
                    </div>

                    <div id="fileSelectedBox" class="file-selected-box" style="display: none;">
                        <div class="d-flex align-items-center gap-2 text-truncate">
                            <i class="bx bxs-file-archive fs-4 text-indigo" id="selectedFileIcon"></i>
                            <div class="text-truncate">
                                <div class="fw-bold text-dark text-truncate" id="selectedFileName" style="font-size: 13.5px;">filename.zip</div>
                                <div class="text-muted small" id="selectedFileSize">0 MB</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="resetFileSelect()" title="ยกเลิกไฟล์นี้">
                            <i class="bx bx-x fs-5"></i>
                        </button>
                    </div>

                    <!-- Safety Warning -->
                    <div class="alert alert-warning mt-3 mb-3 p-3 border-warning-subtle rounded-3" style="font-size: 12.5px;">
                        <div class="d-flex gap-2">
                            <i class="bx bx-error-circle fs-5 text-warning flex-shrink-0 mt-1"></i>
                            <div>
                                <strong>คำเตือนเรื่องความปลอดภัยของข้อมูล:</strong><br>
                                การนำเข้าไฟล์นี้จะทำการ Drop และสร้างตารางใหม่ตามไฟล์สำรอง ข้อมูลเดิมในตารางที่มีชื่อซ้ำจะถูกเขียนทับ กรุณาตรวจสอบให้แน่ใจก่อนดำเนินการ
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Checkbox -->
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirmOverwriteCheck" onchange="toggleUploadButton()">
                        <label class="form-check-label small fw-semibold" for="confirmOverwriteCheck">
                            ข้าพเจ้ารับทราบและยืนยันการนำเข้าไฟล์สำรองข้อมูลนี้เข้าสู่ฐานข้อมูล
                        </label>
                    </div>
                </form>

            </div>
            <div class="modal-footer bg-light px-4 py-3" style="border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn btn-secondary rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary rounded-3 px-4 fw-bold" id="btnSubmitUpload" disabled onclick="submitUploadRestore()">
                    <i class="bx bx-upload"></i> เริ่มนำเข้าข้อมูลทันที
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: ตรวจสอบความสมบูรณ์ของตารางข้อมูล (Table Verification Audit Report)
     ========================================================================= -->
<div class="modal fade" id="modalVerifyAudit" tabindex="-1" aria-labelledby="modalVerifyAuditLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px; border: 1px solid #cbd5e1; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 18px 24px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #ffffff; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                        <i class="bx bx-check-shield"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold m-0" id="modalVerifyAuditLabel" style="font-size: 1.2rem;">รายงานผลการตรวจสอบความสมบูรณ์ของทุกตาราง (Table Verification Audit)</h5>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-white text-dark border px-2 py-1" style="font-size: 11px;">
                                <i class="bx bx-server text-primary"></i> <span id="auditTargetServer"><?= htmlspecialchars($dbConfig['servername']) ?></span>
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 11px;">
                                <i class="bx bx-data"></i> <span id="auditTargetDb"><?= htmlspecialchars($dbConfig['dbname']) ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" style="background: #f8fafc;">

                <!-- Hero Status Card -->
                <div class="audit-hero-card" id="auditHeroCard">
                    <div class="audit-hero-left">
                        <div class="audit-hero-icon" id="auditHeroIcon">
                            <i class="bx bx-badge-check"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0 text-success" id="auditHeroTitle" style="font-size: 1.18rem;">นำเข้าและตรวจสอบสำเร็จสมบูรณ์ 100% ครบทุกตาราง</h5>
                            <p class="text-muted small m-0 mt-1" id="auditHeroSubtext">
                                ตรวจสอบเมื่อ: <span id="auditCheckedAt" class="fw-semibold text-dark">-</span> | 
                                ใช้เวลาประมวลผล: <span id="auditDuration" class="fw-semibold text-dark">-</span> วินาที
                            </p>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 py-2 rounded-3" onclick="runTableVerification(true)">
                            <i class="bx bx-refresh"></i> รีเฟรชตรวจสอบใหม่อีกครั้ง
                        </button>
                    </div>
                </div>

                <!-- KPI Chips -->
                <div class="audit-chips-grid">
                    <div class="audit-chip">
                        <div class="audit-chip-label"><i class="bx bx-table text-primary"></i> ตารางข้อมูล (BASE TABLE)</div>
                        <div class="audit-chip-value" id="auditChipTables">-</div>
                    </div>
                    <div class="audit-chip">
                        <div class="audit-chip-label"><i class="bx bx-show-alt text-info"></i> มุมมองข้อมูล (VIEW)</div>
                        <div class="audit-chip-value" id="auditChipViews">-</div>
                    </div>
                    <div class="audit-chip">
                        <div class="audit-chip-label"><i class="bx bx-list-ol text-success"></i> จำนวนระเบียนรวม (Total Rows)</div>
                        <div class="audit-chip-value" id="auditChipRows">-</div>
                    </div>
                    <div class="audit-chip">
                        <div class="audit-chip-label"><i class="bx bx-hdd text-warning"></i> ขนาดฐานข้อมูลรวม (Storage)</div>
                        <div class="audit-chip-value" id="auditChipSize">-</div>
                    </div>
                </div>

                <!-- Search & Filters Toolbar -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 bg-white p-2 px-3 rounded-3 border">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small fw-bold">กรองตามประเภท:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="filterAll" onclick="setAuditFilter('all')">ทั้งหมด (<span id="countFilterAll">0</span>)</button>
                            <button type="button" class="btn btn-outline-primary" id="filterTables" onclick="setAuditFilter('tables')">ตารางข้อมูล (<span id="countFilterTables">0</span>)</button>
                            <button type="button" class="btn btn-outline-primary" id="filterViews" onclick="setAuditFilter('views')">มุมมอง View (<span id="countFilterViews">0</span>)</button>
                        </div>
                    </div>
                    <div style="min-width: 250px; position: relative;">
                        <i class="bx bx-search text-muted" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);"></i>
                        <input type="text" id="auditSearchInput" class="form-control form-control-sm ps-4 rounded-3" placeholder="ค้นหาชื่อตาราง..." onkeyup="filterAuditTable()">
                    </div>
                </div>

                <!-- Audit Breakdown Table -->
                <div class="table-responsive bg-white rounded-3 border" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="auditDetailTable">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>ชื่อตาราง (Table Name)</th>
                                <th style="width: 120px;" class="text-center">ประเภท</th>
                                <th style="width: 150px;" class="text-end">จำนวนระเบียน (Rows)</th>
                                <th style="width: 120px;" class="text-end">ขนาดพื้นที่</th>
                                <th style="width: 110px;" class="text-center">Engine</th>
                                <th style="width: 180px;" class="text-center">สถานะความสมบูรณ์</th>
                            </tr>
                        </thead>
                        <tbody id="auditTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">กำลังโหลดข้อมูลการตรวจสอบ...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer bg-white px-4 py-3" style="border-top: 1px solid #e2e8f0;">
                <div class="me-auto text-muted small">
                    <i class="bx bx-info-circle"></i> ทุกตารางและมุมมองได้รับการตรวจสอบโครงสร้างและคำนวณจำนวนระเบียนจริง
                </div>
                <button type="button" class="btn btn-outline-secondary rounded-3 px-3" onclick="copyAuditReport()">
                    <i class="bx bx-copy"></i> คัดลอกรายงานสรุป
                </button>
                <button type="button" class="btn btn-outline-primary rounded-3 px-3" onclick="printAuditReport()">
                    <i class="bx bx-printer"></i> พิมพ์ผลการตรวจสอบ
                </button>
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">
                    ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // State management for Audit Data
    let currentAuditData = null;
    let currentFilterType = 'all';

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
            customClass: { popup: 'rounded-4' }
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
                    customClass: { popup: 'rounded-4' },
                    didOpen: () => { Swal.showLoading(); }
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
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteFilename').value = filename;
                document.getElementById('deleteForm').submit();
            }
        });
    }

    // =========================================================================
    // RESTORE FROM HISTORY (นำเข้าจากไฟล์สำรองเดิม)
    // =========================================================================
    function confirmRestoreFromHistory(filename, filesize) {
        Swal.fire({
            title: 'ยืนยันนำเข้าข้อมูลสู่เซิร์ฟเวอร์?',
            html: `
                <div class="text-start py-2">
                    <div class="p-3 mb-3 bg-light rounded-3 border" style="font-size: 13px;">
                        <div class="mb-1"><strong>ไฟล์สำรอง:</strong> <span class="text-primary">${filename}</span> (${filesize})</div>
                        <div class="mb-1"><strong>เซิร์ฟเวอร์เป้าหมาย:</strong> <?= htmlspecialchars($dbConfig['servername']) ?></div>
                        <div><strong>ฐานข้อมูลเป้าหมาย:</strong> <?= htmlspecialchars($dbConfig['dbname']) ?></div>
                    </div>
                    <div class="alert alert-danger p-2 mb-0 border-0" style="font-size: 12.5px;">
                        <i class="bx bx-error-circle me-1"></i> <strong>คำเตือน:</strong> การนำเข้าจะลบตารางเดิมและเขียนทับด้วยข้อมูลจากไฟล์สำรองนี้ (หรือสร้างตารางใหม่หากยังไม่มีตาราง)
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4338ca',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="bx bx-check-shield"></i> ยืนยัน, เริ่มนำเข้าข้อมูล',
            cancelButtonText: 'ยกเลิก',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                executeRestoreProcess('restore_history', { filename: filename });
            }
        });
    }

    // =========================================================================
    // RESTORE FROM UPLOAD (อัปโหลดและนำเข้าไฟล์ใหม่)
    // =========================================================================
    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('dropzone').style.display = 'none';
            document.getElementById('fileSelectedBox').style.display = 'flex';
            document.getElementById('selectedFileName').textContent = file.name;
            document.getElementById('selectedFileSize').textContent = formatBytesJS(file.size);
            
            const ext = file.name.split('.').pop().toLowerCase();
            const icon = document.getElementById('selectedFileIcon');
            if (ext === 'sql') {
                icon.className = 'bx bxs-file-doc fs-4 text-warning';
            } else {
                icon.className = 'bx bxs-file-archive fs-4 text-indigo';
            }
            toggleUploadButton();
        }
    }

    function resetFileSelect() {
        document.getElementById('backupFileInput').value = '';
        document.getElementById('dropzone').style.display = 'block';
        document.getElementById('fileSelectedBox').style.display = 'none';
        toggleUploadButton();
    }

    function toggleUploadButton() {
        const fileInput = document.getElementById('backupFileInput');
        const check = document.getElementById('confirmOverwriteCheck');
        const btn = document.getElementById('btnSubmitUpload');
        btn.disabled = !(fileInput.files && fileInput.files.length > 0 && check.checked);
    }

    function submitUploadRestore() {
        const fileInput = document.getElementById('backupFileInput');
        if (!fileInput.files || !fileInput.files[0]) return;

        const uploadModalEl = document.getElementById('modalUploadRestore');
        const modalInstance = bootstrap.Modal.getInstance(uploadModalEl);
        if (modalInstance) modalInstance.hide();

        const formData = new FormData();
        formData.append('action', 'restore_upload');
        formData.append('backup_file', fileInput.files[0]);

        executeRestoreProcess('restore_upload', formData, true);
    }

    // Drag and drop listeners
    const dropzone = document.getElementById('dropzone');
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        }, false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        }, false);
    });
    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            document.getElementById('backupFileInput').files = files;
            handleFileSelect(document.getElementById('backupFileInput'));
        }
    });

    // =========================================================================
    // EXECUTE RESTORE (AJAX Runner)
    // =========================================================================
    function executeRestoreProcess(action, payload, isFormData = false) {
        Swal.fire({
            title: 'กำลังนำเข้าข้อมูลสู่เซิร์ฟเวอร์...',
            html: `
                <div class="py-2">
                    <p class="text-muted mb-3" id="restoreStatusMsg">กรุณารอสักครู่ ระบบกำลังสตรีมไฟล์ SQL เข้าสู่ MySQL Server...</p>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%"></div>
                    </div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2" style="font-size: 12px;">
                        <i class="bx bx-error-circle"></i> ห้ามปิดหรือรีเฟรชหน้าต่างนี้จนกว่าการนำเข้าและตรวจสอบจะเสร็จสิ้น!
                    </span>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: { popup: 'rounded-4' },
            didOpen: () => { Swal.showLoading(); }
        });

        let fetchOptions = { method: 'POST' };
        if (isFormData) {
            fetchOptions.body = payload;
        } else {
            const fd = new FormData();
            fd.append('action', action);
            for (let key in payload) {
                fd.append(key, payload[key]);
            }
            fetchOptions.body = fd;
        }

        fetch('api_restore.php', fetchOptions)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'นำเข้าข้อมูลสำเร็จเรียบร้อย!',
                    text: data.message + ' (ใช้เวลา ' + data.duration_seconds + ' วินาที)',
                    timer: 2000,
                    showConfirmButton: false,
                    customClass: { popup: 'rounded-4' }
                }).then(() => {
                    // แสดงผลลัพธ์การตรวจสอบตารางทันที
                    if (data.verification) {
                        renderAuditModal(data.verification);
                        const auditModal = new bootstrap.Modal(document.getElementById('modalVerifyAudit'));
                        auditModal.show();
                    }
                });
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'การนำเข้าข้อมูลล้มเหลว',
                    html: `<div class="text-start p-2"><p class="text-danger mb-0">${err.message}</p></div>`,
                    confirmButtonColor: '#e11d48',
                    customClass: { popup: 'rounded-4' }
                });
            });
    }

    // =========================================================================
    // TABLE VERIFICATION ENGINE
    // =========================================================================
    function runTableVerification(isRefresh = false) {
        if (!isRefresh) {
            Swal.fire({
                title: 'กำลังตรวจสอบทุกตาราง...',
                html: '<p class="text-muted">ระบบกำลังคิวรีนับจำนวนระเบียนและตรวจสอบสถานะตารางทั้งหมดในฐานข้อมูล</p>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: { popup: 'rounded-4' },
                didOpen: () => { Swal.showLoading(); }
            });
        }

        fetch('api_restore.php?action=verify_tables')
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'ไม่สามารถตรวจสอบตารางได้');
                }
                if (!isRefresh) Swal.close();
                renderAuditModal(data);
                if (!isRefresh) {
                    const auditModal = new bootstrap.Modal(document.getElementById('modalVerifyAudit'));
                    auditModal.show();
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'การตรวจสอบตารางล้มเหลว',
                    text: err.message,
                    confirmButtonColor: '#e11d48',
                    customClass: { popup: 'rounded-4' }
                });
            });
    }

    // Render table rows & KPI chips into Audit Modal
    function renderAuditModal(data) {
        currentAuditData = data;

        document.getElementById('auditTargetServer').textContent = data.server || '-';
        document.getElementById('auditTargetDb').textContent = data.database || '-';
        document.getElementById('auditCheckedAt').textContent = data.checked_at || '-';
        document.getElementById('auditDuration').textContent = data.duration_seconds || '0';

        document.getElementById('auditChipTables').textContent = (data.total_tables || 0) + ' ตาราง';
        document.getElementById('auditChipViews').textContent = (data.total_views || 0) + ' มุมมอง';
        document.getElementById('auditChipRows').textContent = (data.total_rows || 0).toLocaleString() + ' แถว';
        document.getElementById('auditChipSize').textContent = formatBytesJS(data.total_size_bytes || 0);

        document.getElementById('countFilterAll').textContent = data.total_all || 0;
        document.getElementById('countFilterTables').textContent = data.total_tables || 0;
        document.getElementById('countFilterViews').textContent = data.total_views || 0;

        // Hero state
        const heroTitle = document.getElementById('auditHeroTitle');
        const heroCard = document.getElementById('auditHeroCard');
        const heroIcon = document.getElementById('auditHeroIcon');
        if (data.all_valid) {
            heroTitle.textContent = `ตรวจสอบแล้ว ${data.total_all} ตาราง: นำเข้าและพร้อมใช้งานสมบูรณ์ 100%`;
            heroTitle.className = 'fw-bold m-0 text-success';
            heroCard.style.background = 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)';
            heroCard.style.borderColor = '#bbf7d0';
            heroIcon.style.background = '#16a34a';
            heroIcon.innerHTML = '<i class="bx bx-badge-check"></i>';
        } else {
            heroTitle.textContent = `พบข้อบกพร่องบางตารางในการตรวจสอบ`;
            heroTitle.className = 'fw-bold m-0 text-danger';
            heroCard.style.background = 'linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%)';
            heroCard.style.borderColor = '#fecaca';
            heroIcon.style.background = '#dc2626';
            heroIcon.innerHTML = '<i class="bx bx-error"></i>';
        }

        renderAuditRows();
    }

    function setAuditFilter(type) {
        currentFilterType = type;
        document.querySelectorAll('#filterAll, #filterTables, #filterViews').forEach(el => el.classList.remove('active'));
        if (type === 'all') document.getElementById('filterAll').classList.add('active');
        if (type === 'tables') document.getElementById('filterTables').classList.add('active');
        if (type === 'views') document.getElementById('filterViews').classList.add('active');
        renderAuditRows();
    }

    function filterAuditTable() {
        renderAuditRows();
    }

    function renderAuditRows() {
        if (!currentAuditData || !currentAuditData.tables) return;
        const tbody = document.getElementById('auditTableBody');
        const search = (document.getElementById('auditSearchInput').value || '').toLowerCase().trim();

        let filtered = currentAuditData.tables.filter(t => {
            if (currentFilterType === 'tables' && t.is_view) return false;
            if (currentFilterType === 'views' && !t.is_view) return false;
            if (search && t.table_name.toLowerCase().indexOf(search) === -1) return false;
            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูลตารางที่ตรงกับเงื่อนไขการค้นหา</td></tr>`;
            return;
        }

        let html = '';
        filtered.forEach((t, idx) => {
            const isView = t.is_view;
            const typeBadge = isView 
                ? '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bx bx-show-alt"></i> VIEW</span>' 
                : '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bx bx-table"></i> TABLE</span>';

            let statusBadge = '';
            if (t.status === 'OK') {
                if (t.row_count > 0) {
                    statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bx bx-check"></i> สมบูรณ์</span>';
                } else {
                    statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bx bx-minus"></i> ว่าง (0 แถว)</span>';
                }
            } else {
                statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bx bx-x"></i> ผิดพลาด</span>';
            }

            const rowFormatted = (t.row_count >= 0) ? t.row_count.toLocaleString() : '<span class="text-danger">ERR</span>';
            const sizeFormatted = isView ? '-' : formatBytesJS(t.size_bytes);

            html += `
                <tr>
                    <td class="text-center text-muted fw-semibold">${idx + 1}</td>
                    <td>
                        <strong class="text-dark">${escapeHtml(t.table_name)}</strong>
                        <div class="text-muted" style="font-size: 11px;">Collation: ${escapeHtml(t.collation || '-')}</div>
                    </td>
                    <td class="text-center">${typeBadge}</td>
                    <td class="text-end fw-bold ${t.row_count > 0 ? 'text-dark' : 'text-muted'}">${rowFormatted}</td>
                    <td class="text-end text-muted small">${sizeFormatted}</td>
                    <td class="text-center"><span class="badge bg-light text-dark border">${escapeHtml(t.engine || '-')}</span></td>
                    <td class="text-center">${statusBadge}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // Helper: Copy audit report to clipboard
    function copyAuditReport() {
        if (!currentAuditData) return;
        let text = `รายงานผลการตรวจสอบฐานข้อมูล eDHS\n`;
        text += `เซิร์ฟเวอร์: ${currentAuditData.server} | ฐานข้อมูล: ${currentAuditData.database}\n`;
        text += `ตรวจสอบเมื่อ: ${currentAuditData.checked_at}\n`;
        text += `ตารางทั้งหมด: ${currentAuditData.total_tables} ตาราง | มุมมอง (VIEW): ${currentAuditData.total_views} | ระเบียนรวม: ${currentAuditData.total_rows.toLocaleString()} แถว\n`;
        text += `ขนาดฐานข้อมูล: ${formatBytesJS(currentAuditData.total_size_bytes)}\n\n`;
        text += `รายละเอียดรายตาราง:\n`;
        currentAuditData.tables.forEach((t, i) => {
            text += `${i+1}. ${t.table_name} [${t.table_type}] : ${(t.row_count >= 0 ? t.row_count.toLocaleString() : 'ERR')} แถว (${t.status_text})\n`;
        });

        navigator.clipboard.writeText(text).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกรายงานสำเร็จ',
                text: 'นำข้อความรายงานไปวางในแชทหรือเอกสารได้ทันที',
                timer: 1800,
                showConfirmButton: false,
                customClass: { popup: 'rounded-4' }
            });
        });
    }

    // Helper: Print audit report
    function printAuditReport() {
        window.print();
    }

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Helper: format bytes in JS
    function formatBytesJS(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
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
            customClass: { popup: 'rounded-4' }
        });
    <?php endif; ?>
</script>

</body>
</html>
