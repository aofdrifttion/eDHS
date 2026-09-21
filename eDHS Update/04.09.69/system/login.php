<?php
session_start();
include './database_config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
    $password = $_POST['password']; // do not trim or htmlspecialchars passwords to preserve integrity

    $stmt = $conn->prepare("SELECT id, fullname, password, role, status FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['status'] !== 'approved') {
            die("บัญชีของคุณยังไม่ได้รับการอนุมัติ");
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['2fa_pending_user_id'] = $user['id'];
            $_SESSION['2fa_pending_role'] = $user['role'];
            $_SESSION['2fa_pending_fullname'] = $user['fullname'];
            // พาไปหน้ากรอก PIN 6 หลัก
            echo "<script>
                    window.location.href = 'login_2fa.php';
                  </script>";
            exit();
           
        } else {
            if (function_exists('system_log')) {
                system_log($conn, 'ระบบสมาชิก', 'LOGIN_FAILED', "เข้าสู่ระบบไม่สำเร็จ (รหัสผ่านผิด) สำหรับผู้ใช้: $username");
            }
            session_unset();
            echo "<script>alert('รหัสผ่านไม่ถูกต้อง'); window.location.href='login.php';</script>";
        }
    } else {
        if (function_exists('system_log')) {
            system_log($conn, 'ระบบสมาชิก', 'LOGIN_FAILED', "เข้าสู่ระบบไม่สำเร็จ (ไม่พบชื่อผู้ใช้: $username)");
        }
        echo "<script>alert('ไม่พบชื่อผู้ใช้งานนี้'); window.location.href='login.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eDebtor Hospital System (eDHS) - Login</title>
 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600&family=Public+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <style media="screen">
        *, *:before, *:after { 
            padding: 0; 
            margin: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            background-color: #f5f5f9; 
            /* Premium Dashboard Animated Gradient Background */
            background-image: 
                radial-gradient(at 0% 0%, rgba(105, 108, 255, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(0, 210, 255, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(105, 108, 255, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(255, 171, 0, 0.1) 0px, transparent 50%);
            font-family: 'Noto Sans Thai', sans-serif !important;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #566a7f;
            overflow: hidden;
        }

        input, button, select, textarea, label, h3, p, a, div, span, small, .swal2-popup, .swal2-popup * {
            font-family: 'Noto Sans Thai', sans-serif !important;
        }

        /* ป้องกันฟอนต์ไอคอน Font Awesome และ Boxicons ไม่ให้ถูกทับ */
        .fa, .fas, .far, .fal, .fad, .fab, 
        .fa:before, .fas:before, .far:before, .fal:before, .fad:before, .fab:before,
        i[class*="fa-"], i[class*="fa-"]:before {
            font-family: 'Font Awesome 5 Free' !important;
        }
        .fab, .fab:before {
            font-family: 'Font Awesome 5 Brands' !important;
        }
        .bx, .bxs, .bxl, [class^="bx-"], [class*=" bx-"],
        .bx:before, .bxs:before, .bxl:before,
        i[class*="bx-"], i[class*="bx-"]:before {
            font-family: 'boxicons' !important;
        }

        /* Abstract shapes for background */
        .shape {
            position: absolute;
            filter: blur(60px);
            z-index: -1;
            opacity: 0.6;
            border-radius: 50%;
            animation: float 10s infinite ease-in-out alternate;
        }
        .shape-1 { width: 300px; height: 300px; background: #696cff; top: -100px; left: -100px; }
        .shape-2 { width: 400px; height: 400px; background: #00d2ff; bottom: -150px; right: -100px; animation-delay: -5s; }
        .shape-3 { width: 200px; height: 200px; background: #ffab00; top: 40%; left: 60%; animation-duration: 15s; }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -50px) scale(1.1); }
        }

        /* Glassmorphism Login Card */
        form { 
            width: 100%; 
            max-width: 420px;
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 16px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); 
            padding: 45px 40px; 
            position: relative;
            z-index: 10;
            margin: 0 15px; /* Add margin for small screens */
        }

        /* Header Icon */
        .icon-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 65px;
            height: 65px;
            background: rgba(105, 108, 255, 0.1);
            color: #696cff;
            border-radius: 15px;
            font-size: 30px;
            margin: 0 auto 20px auto;
            box-shadow: 0 4px 15px rgba(105, 108, 255, 0.2);
        }

        form h3 { 
            font-size: 24px; 
            font-weight: 700; 
            text-align: center; 
            color: #32475c; 
            margin-bottom: 5px;
        }
        
        form p.subtitle {
            text-align: center;
            color: #a1acb8;
            font-size: 14px;
            margin-bottom: 30px;
        }

        label { 
            display: block; 
            margin-top: 15px; 
            font-size: 13px; 
            font-weight: 600; 
            color: #566a7f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input { 
            display: block; 
            height: 48px; 
            width: 100%; 
            background-color: #fff; 
            border: 1px solid #d9dee3; 
            border-radius: 8px; 
            padding: 0 15px; 
            margin-top: 8px; 
            font-size: 15px; 
            font-weight: 400;
            color: #566a7f;
            transition: all 0.2s ease-in-out;
        }

        input:focus {
            border-color: #696cff;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(105, 108, 255, 0.15);
        }

        ::placeholder { 
            color: #c9d2db; 
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper input {
            padding-right: 40px;
            margin-top: 8px; 
        }
        
        .password-wrapper i {
            position: absolute;
            right: 15px;
            top: 55%; 
            transform: translateY(-50%);
            cursor: pointer;
            color: #b4bdc6;
            transition: color 0.3s;
            font-size: 16px;
        }
        
        .password-wrapper i:hover {
            color: #696cff;
        }

        button { 
            margin-top: 35px; 
            width: 100%; 
            background: linear-gradient(135deg, #696cff 0%, #5f61e6 100%);
            color: #ffffff; 
            padding: 12px 0; 
            font-size: 16px; 
            font-weight: 600; 
            border-radius: 8px; 
            cursor: pointer; 
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px 0 rgba(105, 108, 255, 0.4);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(105, 108, 255, 0.5);
        }

        .btn-register {
            display: block;
            text-align: center;
            margin-top: 15px; 
            width: 100%; 
            background: #fff;
            color: #696cff; 
            padding: 12px 0; 
            font-size: 16px; 
            font-weight: 600; 
            border-radius: 8px; 
            cursor: pointer; 
            border: 1px solid #696cff;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-register:hover {
            background: rgba(105, 108, 255, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 4px 14px 0 rgba(105, 108, 255, 0.2);
        }

        .system-logo-text {
            text-align: center;
            font-size: 12px;
            color: #a1acb8;
            margin-top: 25px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    
    <!-- Background Animated Shapes -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>

    <form action="./login.php" method="POST" autocomplete="off">
        <div class="icon-container">
            <i class="fas fa-chart-pie"></i>
        </div>
        <h3>eDHS Dashboard</h3>
        <p class="subtitle">ระบบบริหารจัดการลูกหนี้โรงพยาบาล</p>
        
        <label for="username">ชื่อผู้ใช้ (Username)</label>
        <input type="text" id="username" name="username" placeholder="ระบุชื่อผู้ใช้ของคุณ" required autocomplete="off">
        
        <label for="password">รหัสผ่าน (Password)</label>
        <div class="password-wrapper">
            <input type="password" id="password" name="password" placeholder="ระบุรหัสผ่านของคุณ" required style="margin-top:0;" autocomplete="new-password">
            <i class="fas fa-eye" id="togglePassword"></i>
        </div>
        
        <button type="submit"><i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ</button>
        <a href="register.php" class="btn-register"><i class="fas fa-user-plus me-2"></i> สมัครสมาชิก</a>

        <div class="system-logo-text" onclick="showChangelog()" style="cursor: pointer;" title="คลิกเพื่อดูประวัติการอัปเดตระบบ">
            &copy; eDebtor Hospital System เวอร์ชั่น <?= htmlspecialchars(get_system_version()); ?>
            <i class="fas fa-info-circle" style="color: #696cff; margin-left: 4px; font-size: 11px;"></i>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });

        function showChangelog() {
            var releases = <?= json_encode(get_system_changelog(), JSON_UNESCAPED_UNICODE); ?>;
            var htmlContent = '<div style="text-align: left; max-height: 380px; overflow-y: auto; padding-right: 5px; font-family: inherit;">';
            if (releases && releases.length > 0) {
                releases.forEach(function(rel, idx) {
                    var isLatest = (idx === 0);
                    htmlContent += '<div style="margin-bottom: 15px; padding: 12px; border-radius: 8px; background: ' + (isLatest ? '#f0f3ff; border: 1px solid #d0d7ff;' : '#f8f9fa; border: 1px solid #e9ecef;') + '">';
                    htmlContent += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">';
                    htmlContent += '<span style="font-weight: bold; color: #566a7f; font-size: 14px;"><i class="fas fa-code-branch" style="color: #696cff; margin-right: 6px;"></i>v' + rel.version + '</span>';
                    htmlContent += '<small style="color: #8a92a6; font-size: 12px;">' + (rel.date || '') + '</small>';
                    htmlContent += '</div>';
                    htmlContent += '<div style="font-size: 13px; font-weight: 600; color: #3b4256; margin-bottom: 6px;">' + (rel.title || '') + '</div>';
                    if (rel.changes && rel.changes.length > 0) {
                        htmlContent += '<ul style="margin: 0; padding-left: 18px; font-size: 12.5px; color: #566a7f;">';
                        rel.changes.forEach(function(c) {
                            htmlContent += '<li style="margin-bottom: 4px;"><strong>[' + c.tag + ']</strong> ' + c.description + '</li>';
                        });
                        htmlContent += '</ul>';
                    }
                    htmlContent += '</div>';
                });
            } else {
                htmlContent += '<p style="text-align: center; color: #8a92a6;">ไม่มีข้อมูลการอัปเดต</p>';
            }
            htmlContent += '</div>';

            Swal.fire({
                title: '📋 ประวัติการอัปเดตระบบ (eDHS)',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'ปิดหน้าต่าง',
                confirmButtonColor: '#696cff',
                customClass: {
                    popup: 'rounded-4'
                }
            });
        }
    </script>
</body>
</html>