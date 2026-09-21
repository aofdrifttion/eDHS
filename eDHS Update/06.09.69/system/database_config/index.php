<?php
require_once __DIR__ . "/auth_check.php";
$access = check_database_config_access();
if (!$access['allowed']) {
    echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Access Denied - eDHS</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>";
    echo "<link href='https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap' rel='stylesheet'>";
    echo "<style>*{font-family:'Noto Sans Thai',sans-serif;}</style></head>";
    echo "<body class='bg-light d-flex align-items-center justify-content-center' style='min-height: 100vh;'>";
    echo "<div class='card shadow border-0 p-4 text-center' style='max-width: 460px; border-radius: 20px;'>";
    echo "<div class='mb-3 text-danger'><i class='bx bx-shield-x' style='font-size: 72px;'></i></div>";
    echo "<h4 class='fw-bold text-dark mb-2'>ไม่อนุญาตให้เข้าถึง</h4>";
    echo "<p class='text-muted mb-4' style='font-size: 14px;'>ระบบเชื่อมต่อฐานข้อมูลเรียบร้อยแล้ว การเข้าถึงหรือแก้ไขค่าการเชื่อมต่อ จำเป็นต้องเข้าสู่ระบบด้วยสิทธิ์ผู้ดูแลระบบ (Admin) เท่านั้น</p>";
    echo "<a href='../login.php' class='btn btn-primary px-4 py-2 rounded-pill fw-semibold'><i class='bx bx-log-in me-1'></i> ไปยังหน้าเข้าสู่ระบบ</a>";
    echo "</div></body></html>";
    exit();
}
$configFile = __DIR__ . "/config.json";
$configData = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [
    "db1" => [
        "servername" => "",
        "username" => "",
        "password" => "",
        "dbname" => ""
    ],
    "db2" => [
        "servername" => "",
        "username" => "",
        "password" => "",
        "dbname" => ""
    ],
    "hospital" => "",
    "hospcode" => "",
    "Client_ID" => "",
    "Secret" => "",
    "notify_enable" => "1"
];
$notify_enable = isset($configData['notify_enable']) ? $configData['notify_enable'] : "1";
$hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : (isset($configData['hoscode']) ? $configData['hoscode'] : "");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าการเชื่อมต่อฐานข้อมูล - eDHS</title>
    
    <!-- Google Fonts: Noto Sans Thai -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Boxicons CSS -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
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
            font-family: 'Font Awesome 5 Free' !important;
            font-style: normal;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: #1e293b;
            min-height: 100vh;
            padding: 24px 16px 40px 16px;
        }

        .config-wrapper {
            max-width: 1040px;
            margin: 0 auto;
        }

        /* Main Container Card */
        .config-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Header Bar */
        .config-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 45%, #2563eb 100%);
            color: #ffffff;
            padding: 26px 32px;
            position: relative;
            overflow: hidden;
        }
        .config-header::after {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 160px;
            height: 160px;
            background: rgba(255, 255, 255, 0.07);
            border-radius: 50%;
            pointer-events: none;
        }

        .config-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            position: relative;
            z-index: 2;
        }

        .config-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .config-header-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }

        .config-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.2px;
            line-height: 1.3;
        }

        .config-subtitle {
            font-size: 0.88rem;
            margin: 4px 0 0 0;
            color: rgba(255, 255, 255, 0.82);
            font-weight: 400;
        }

        .config-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 12.5px;
            font-weight: 600;
            color: #ffffff;
        }

        /* Card Body */
        .config-body {
            padding: 32px 32px 28px 32px;
        }

        /* Section Cards */
        .section-box {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 22px 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .section-box:hover {
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            transform: translateY(-1px);
        }

        .section-box-db1 {
            border-left: 5px solid #10b981 !important;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 40%);
        }

        .section-box-db2 {
            border-left: 5px solid #2563eb !important;
            background: linear-gradient(180deg, #eff6ff 0%, #ffffff 40%);
        }

        .section-box-hosp {
            border-left: 5px solid #f59e0b !important;
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 40%);
        }

        .section-box-api {
            border-left: 5px solid #8b5cf6 !important;
            background: linear-gradient(180deg, #faf5ff 0%, #ffffff 40%);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        .section-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .section-icon-db1 {
            background: #dcfce7;
            color: #059669;
        }
        .section-icon-db2 {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .section-icon-hosp {
            background: #fef3c7;
            color: #d97706;
        }
        .section-icon-api {
            background: #ede9fe;
            color: #7c3aed;
        }

        .section-badge {
            font-size: 11.5px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .section-badge-db1 {
            background: #dcfce7;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .section-badge-db2 {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .section-badge-hosp {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .section-badge-api {
            background: #ede9fe;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
        }

        /* Form Inputs */
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-right: none;
            color: #64748b;
            border-radius: 10px 0 0 10px;
            padding: 9px 12px;
            font-size: 17px;
        }

        .form-control {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 14px;
            color: #1e293b;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }
        .input-group > .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .input-group > .form-control.has-toggle {
            border-radius: 0;
        }
        .btn-toggle-pw {
            border: 1px solid #cbd5e1;
            border-left: none;
            background: #ffffff;
            color: #64748b;
            border-radius: 0 10px 10px 0;
            padding: 0 14px;
            transition: all 0.2s ease;
        }
        .btn-toggle-pw:hover {
            color: #0f172a;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        /* Switch toggle */
        .custom-switch-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
        }
        .form-check-input {
            width: 2.75rem;
            height: 1.5rem;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        /* Actions Bar */
        .actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            margin-top: 10px;
        }

        .btn-save {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-save:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.4);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .btn-test {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            padding: 12px 26px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-test:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.4);
            transform: translateY(-1px);
            color: #ffffff;
        }

        /* Result container */
        #result {
            transition: all 0.3s ease;
        }
        .result-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 24px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
            margin-top: 20px;
        }

        /* Footer */
        .config-footer {
            text-align: center;
            padding-top: 24px;
            color: #64748b;
            font-size: 12.5px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="config-wrapper">
    <div class="config-card">
        
        <!-- Header -->
        <div class="config-header">
            <div class="config-header-content">
                <div class="config-header-left">
                    <div class="config-header-icon">
                        <i class="bx bx-server"></i>
                    </div>
                    <div>
                        <h4 class="config-title">ตั้งค่าการเชื่อมต่อฐานข้อมูล</h4>
                        <p class="config-subtitle">จัดการข้อมูลการเชื่อมต่อ MySQL/MariaDB สำหรับระบบ eDHS และ HOSxP</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($access['is_setup_mode']): ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold" style="font-size: 12px;">
                            <i class="bx bx-wrench me-1"></i> โหมดตั้งค่าระบบใหม่ (Initial Setup)
                        </span>
                    <?php else: ?>
                        <span class="config-header-badge">
                            <i class="bx bx-shield-quarter"></i> Admin Hub
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Form Body -->
        <div class="config-body">
            <?php if ($access['is_setup_mode']): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center gap-3">
                <i class="bx bx-info-circle fs-2 text-warning"></i>
                <div style="font-size: 13.5px;">
                    <strong class="d-block text-dark">⚙️ ระบบอยู่ในโหมดตั้งค่าเริ่มต้น (Initial Setup Mode)</strong>
                    <span>ยังไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาระบุ IP, Username, Password ของฐานข้อมูลสำหรับโรงพยาบาลนี้ จากนั้นกด <b>"ทดสอบการเชื่อมต่อฐานข้อมูล"</b> และกด <b>"บันทึกการตั้งค่า"</b></span>
                </div>
            </div>
            <?php endif; ?>
            <form action="save_config.php" method="post" id="configForm">
                
                <div class="row">
                    <!-- Database 1 Card (eDHS / im_report) -->
                    <div class="col-lg-6 col-md-12">
                        <div class="section-box section-box-db1">
                            <div class="section-header">
                                <h5 class="section-header-title">
                                    <span class="section-icon section-icon-db1"><i class="bx bx-data"></i></span>
                                    ฐานข้อมูลที่ 1
                                </h5>
                                <span class="section-badge section-badge-db1">
                                    <i class="bx bx-check-circle"></i> ระบบ eDHS
                                </span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-network-chart text-success"></i> Server Name / Host IP:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-chip"></i></span>
                                    <input type="text" class="form-control" name="db1_servername" value="<?= htmlspecialchars($configData['db1']['servername'] ?? ''); ?>" placeholder="เช่น 192.168.0.251 หรือ localhost" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-user text-success"></i> Username:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-user-circle"></i></span>
                                    <input type="text" class="form-control" name="db1_username" value="<?= htmlspecialchars($configData['db1']['username'] ?? ''); ?>" placeholder="ชื่อผู้ใช้งานฐานข้อมูล" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-key text-success"></i> Password:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                                    <input type="password" class="form-control has-toggle" id="db1_pw" name="db1_password" value="<?= htmlspecialchars($configData['db1']['password'] ?? ''); ?>" placeholder="รหัสผ่านฐานข้อมูล">
                                    <button class="btn btn-toggle-pw" type="button" onclick="togglePassword('db1_pw', this)">
                                        <i class="bx bx-show"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label"><i class="bx bx-folder text-success"></i> Database Name:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-coin-stack"></i></span>
                                    <input type="text" class="form-control" name="db1_dbname" value="<?= htmlspecialchars($configData['db1']['dbname'] ?? ''); ?>" placeholder="เช่น im_report" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database 2 Card (HOSxP / hos) -->
                    <div class="col-lg-6 col-md-12">
                        <div class="section-box section-box-db2">
                            <div class="section-header">
                                <h5 class="section-header-title">
                                    <span class="section-icon section-icon-db2"><i class="bx bx-pulse"></i></span>
                                    ฐานข้อมูลที่ 2
                                </h5>
                                <span class="section-badge section-badge-db2">
                                    <i class="bx bx-link-external"></i> HOSxP / HIS
                                </span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-network-chart text-primary"></i> Server Name / Host IP:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-chip"></i></span>
                                    <input type="text" class="form-control" name="db2_servername" value="<?= htmlspecialchars($configData['db2']['servername'] ?? ''); ?>" placeholder="เช่น 192.168.0.251" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-user text-primary"></i> Username:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-user-circle"></i></span>
                                    <input type="text" class="form-control" name="db2_username" value="<?= htmlspecialchars($configData['db2']['username'] ?? ''); ?>" placeholder="ชื่อผู้ใช้งานฐานข้อมูล" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-key text-primary"></i> Password:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                                    <input type="password" class="form-control has-toggle" id="db2_pw" name="db2_password" value="<?= htmlspecialchars($configData['db2']['password'] ?? ''); ?>" placeholder="รหัสผ่านฐานข้อมูล">
                                    <button class="btn btn-toggle-pw" type="button" onclick="togglePassword('db2_pw', this)">
                                        <i class="bx bx-show"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label"><i class="bx bx-folder text-primary"></i> Database Name:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-coin-stack"></i></span>
                                    <input type="text" class="form-control" name="db2_dbname" value="<?= htmlspecialchars($configData['db2']['dbname'] ?? ''); ?>" placeholder="เช่น hos" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Hospital Info Card -->
                    <div class="col-lg-6 col-md-12">
                        <div class="section-box section-box-hosp">
                            <div class="section-header">
                                <h5 class="section-header-title">
                                    <span class="section-icon section-icon-hosp"><i class="bx bx-buildings"></i></span>
                                    ข้อมูลหน่วยบริการ
                                </h5>
                                <span class="section-badge section-badge-hosp">
                                    <i class="bx bx-id-card"></i> Hospital Info
                                </span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-barcode text-warning"></i> รหัสสถานพยาบาล (5 หลัก):</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-hash"></i></span>
                                    <input type="text" class="form-control" name="hospcode" value="<?= htmlspecialchars($hospcode); ?>" placeholder="เช่น 11072" maxlength="10" required>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label"><i class="bx bx-building-house text-warning"></i> ชื่อโรงพยาบาล:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-clinic"></i></span>
                                    <input type="text" class="form-control" name="hospital" value="<?= htmlspecialchars($configData['hospital'] ?? ''); ?>" placeholder="เช่น โรงพยาบาลโพนทราย" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- API & Notification Card -->
                    <div class="col-lg-6 col-md-12">
                        <div class="section-box section-box-api">
                            <div class="section-header">
                                <h5 class="section-header-title">
                                    <span class="section-icon section-icon-api"><i class="bx bx-bell"></i></span>
                                    ระบบแจ้งเตือนและ API Key
                                </h5>
                                <span class="section-badge section-badge-api">
                                    <i class="bx bx-broadcast"></i> Notification
                                </span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-id-card text-info"></i> Client ID:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-code-alt"></i></span>
                                    <input type="text" class="form-control" name="Client_ID" value="<?= htmlspecialchars($configData['Client_ID'] ?? ''); ?>" placeholder="Line Notify หรือ API Client ID">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bx bx-shield-alt-2 text-info"></i> Secret Key:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-key"></i></span>
                                    <input type="password" class="form-control has-toggle" id="api_secret" name="Secret" value="<?= htmlspecialchars($configData['Secret'] ?? ''); ?>" placeholder="Client Secret Key">
                                    <button class="btn btn-toggle-pw" type="button" onclick="togglePassword('api_secret', this)">
                                        <i class="bx bx-show"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="custom-switch-card">
                                <div>
                                    <div class="fw-bold" style="font-size: 13.5px; color: #1e293b;">
                                        <i class="bx bx-bell-ring text-success me-1"></i> แจ้งเตือนผ่าน Line Notify
                                    </div>
                                    <small class="text-muted">ส่งการแจ้งเตือนความเคลื่อนไหวผ่าน Line Notify</small>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="notify_enable" name="notify_enable" value="1" <?= $notify_enable === '1' ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="actions-bar">
                    <div>
                        <button type="button" class="btn-test" id="btnTest" onclick="testConnection()">
                            <i class="bx bx-check-shield fs-5"></i> ทดสอบการเชื่อมต่อฐานข้อมูล
                        </button>
                    </div>
                    <div>
                        <button type="submit" class="btn-save">
                            <i class="bx bx-save fs-5"></i> บันทึกการตั้งค่า
                        </button>
                    </div>
                </div>

            </form>

            <!-- Test Connection Result -->
            <div id="result"></div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="config-footer">
        ระบบบริหารลูกหนี้โรงพยาบาลอิเล็กทรอนิกส์ eDebtor Hospital System (eDHS)<br>
        <small>© 2004 Project Application & Code Application by นายจิรันธนิน ประสารกุลนันท์ นักวิชาการคอมพิวเตอร์</small>
    </footer>
</div>

<script>
    // Toggle Password visibility
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bx-show');
            icon.classList.add('bx-hide');
        } else {
            input.type = 'password';
            icon.classList.remove('bx-hide');
            icon.classList.add('bx-show');
        }
    }

    // AJAX Test Connection
    function testConnection() {
        const btn = document.getElementById('btnTest');
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span> กำลังทดสอบการเชื่อมต่อ...`;

        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = `
            <div class="result-box mt-3 text-center py-4">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div class="fw-semibold text-muted">กำลังทดสอบเชื่อมต่อฐานข้อมูลที่ 1 และ 2...</div>
            </div>
        `;

        const form = document.getElementById('configForm');
        const formData = new FormData(form);

        fetch('test_connection.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalContent;

                resultDiv.innerHTML = `
                    <div class="result-box mt-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div class="fw-bold" style="font-size: 15px; color: #0f172a;">
                                <i class="bx bx-radar text-primary me-1"></i> ผลการทดสอบการเชื่อมต่อฐานข้อมูล
                            </div>
                            <button type="button" class="btn-close btn-sm" onclick="document.getElementById('result').innerHTML=''"></button>
                        </div>
                        <div class="px-2">
                            ${data}
                        </div>
                    </div>
                `;

                // Scroll smoothly to result
                resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
                resultDiv.innerHTML = `
                    <div class="alert alert-danger mt-3 d-flex align-items-center gap-2">
                        <i class="bx bx-error-circle fs-4"></i>
                        <div>เกิดข้อผิดพลาดในการเรียกทดสอบ: ${error}</div>
                    </div>
                `;
            });
    }

    // AJAX Form Submit with connection verification
    document.getElementById('configForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btnSave = document.querySelector('.btn-save');
        const origHtml = btnSave.innerHTML;
        btnSave.disabled = true;
        btnSave.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span> กำลังทดสอบและบันทึก...`;

        const formData = new FormData(this);
        formData.append('ajax', '1');

        fetch('save_config.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnSave.disabled = false;
            btnSave.innerHTML = origHtml;

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'เชื่อมต่อและบันทึกสำเร็จ!',
                    text: 'ฐานข้อมูลเชื่อมต่อถูกต้อง กำลังนำท่านเข้าสู่หน้าหลัก...',
                    timer: 1800,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'rounded-4'
                    }
                }).then(() => {
                    window.location.href = data.redirect || '../index.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ!',
                    html: '<div class="text-start bg-light p-3 rounded border text-danger small mb-2" style="white-space: pre-line;">' + 
                          (data.message || 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้') + 
                          '</div><div class="text-muted small">กรุณาตรวจสอบ Host IP, Username และ Password อีกครั้ง</div>',
                    confirmButtonText: 'ตกลง ตรวจสอบข้อมูลใหม่',
                    customClass: {
                        popup: 'rounded-4',
                        confirmButton: 'btn btn-primary px-4 py-2 rounded-pill'
                    }
                });
            }
        })
        .catch(err => {
            btnSave.disabled = false;
            btnSave.innerHTML = origHtml;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาดในการบันทึก',
                text: String(err),
                customClass: { popup: 'rounded-4' }
            });
        });
    });

    // Check if redirected from non-ajax save
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('saved') === '1' && urlParams.get('connected') === '1') {
        Swal.fire({
            icon: 'success',
            title: 'เชื่อมต่อและบันทึกสำเร็จ!',
            text: 'ฐานข้อมูลเชื่อมต่อถูกต้อง กำลังนำท่านเข้าสู่หน้าหลัก...',
            timer: 1800,
            timerProgressBar: true,
            showConfirmButton: false,
            customClass: {
                popup: 'rounded-4'
            }
        }).then(() => {
            window.location.href = '../index.php';
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (urlParams.get('error_conn')) {
        Swal.fire({
            icon: 'error',
            title: 'การเชื่อมต่อฐานข้อมูลล้มเหลว',
            html: '<div class="text-start bg-light p-3 rounded border text-danger small mb-2" style="white-space: pre-line;">' + 
                  decodeURIComponent(urlParams.get('error_conn')) + 
                  '</div><div class="text-muted small">กรุณาตรวจสอบ Host IP, Username และ Password อีกครั้ง</div>',
            confirmButtonText: 'ตกลง',
            customClass: { popup: 'rounded-4' }
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>

</body>
</html>
