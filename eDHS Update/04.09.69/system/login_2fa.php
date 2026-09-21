<?php
session_start();
include './database_config/config.php';

// ตรวจสอบว่าผ่านการล็อกอินขั้นแรกมาหรือยัง
if (!isset($_SESSION['2fa_pending_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['2fa_pending_user_id'];
$error = '';

// ตรวจสอบว่าผู้ใช้มี PIN หรือยัง
$stmt = $conn->prepare("SELECT pin_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$has_pin = !empty($user_data['pin_code']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รวมค่าจากกล่อง input 6 ช่อง
    $pin = $_POST['pin1'] . $_POST['pin2'] . $_POST['pin3'] . $_POST['pin4'] . $_POST['pin5'] . $_POST['pin6'];
    
    if (strlen($pin) !== 6 || !is_numeric($pin)) {
        $error = 'กรุณากรอกรหัส PIN 6 หลักให้ครบถ้วน';
    } else {
        if (!$has_pin) {
            // กรณีตั้งรหัส PIN ใหม่
            $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET pin_code = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_pin, $user_id);
            if ($update_stmt->execute()) {
                // ตั้งค่า Session หลักเพื่อเข้าใช้งานระบบ
                $_SESSION['user_id'] = $_SESSION['2fa_pending_user_id'];
                $_SESSION['role'] = $_SESSION['2fa_pending_role'];
                $_SESSION['fullname'] = $_SESSION['2fa_pending_fullname'];
                
                if (function_exists('system_log')) {
                    system_log($conn, 'ระบบสมาชิก', 'LOGIN', 'เข้าสู่ระบบสำเร็จ (ตั้งรหัส PIN ใหม่)');
                }
                
                // ล้าง Session ชั่วคราว
                unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'], $_SESSION['2fa_pending_fullname']);
                
                echo "<script>window.parent.location.href = 'index.php';</script>";
                exit();
            } else {
                $error = 'เกิดข้อผิดพลาดในการบันทึกรหัส PIN';
            }
        } else {
            // กรณีตรวจสอบรหัส PIN
            if (password_verify($pin, $user_data['pin_code'])) {
                // รหัสถูกต้อง
                $_SESSION['user_id'] = $_SESSION['2fa_pending_user_id'];
                $_SESSION['role'] = $_SESSION['2fa_pending_role'];
                $_SESSION['fullname'] = $_SESSION['2fa_pending_fullname'];
                
                if (function_exists('system_log')) {
                    system_log($conn, 'ระบบสมาชิก', 'LOGIN', 'เข้าสู่ระบบสำเร็จ');
                }
                
                unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'], $_SESSION['2fa_pending_fullname']);
                
                echo "<script>window.parent.location.href = 'index.php';</script>";
                exit();
            } else {
                $error = 'รหัส PIN ไม่ถูกต้อง';
                if (function_exists('system_log')) {
                    // It uses $_SESSION['2fa_pending_fullname'] because $_SESSION['fullname'] is not set yet
                    $pending_user = isset($_SESSION['2fa_pending_fullname']) ? $_SESSION['2fa_pending_fullname'] : 'Unknown User';
                    system_log($conn, 'ระบบสมาชิก', 'LOGIN_FAILED', "ยืนยันรหัส PIN ไม่ถูกต้อง สำหรับผู้ใช้: " . $pending_user);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eDHS - ยืนยันตัวตน 2 ขั้นตอน (2FA)</title>
 
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

        input, button, select, textarea, label, h3, p, a, div, span, small {
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
            text-align: center;
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
        
        .desc {
            text-align: center;
            color: #a1acb8;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .pin-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .pin-input {
            width: 45px;
            height: 55px;
            font-size: 24px;
            text-align: center;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            background-color: #fff;
            color: #566a7f;
            transition: all 0.2s ease;
        }

        .pin-input:focus {
            border-color: #696cff;
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(105, 108, 255, 0.15);
        }

        button[type="submit"] { 
            margin-top: 15px; 
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

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(105, 108, 255, 0.5);
        }
        
        .btn-cancel {
            background-color: transparent;
            color: #8592a3;
            box-shadow: none;
            margin-top: 10px;
            width: 100%;
            padding: 12px 0;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background-color: rgba(133, 146, 163, 0.1);
        }

        .error-msg {
            color: #ff3e1d;
            font-size: 14px;
            margin-bottom: 20px;
            display: <?php echo empty($error) ? 'none' : 'block'; ?>;
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

    <form action="login_2fa.php" method="POST">
        <div class="icon-container">
            <i class="fas fa-key"></i>
        </div>
        <h3><?php echo $has_pin ? 'ยืนยันตัวตน' : 'ตั้งรหัส PIN ใหม่'; ?></h3>
        <p class="desc">
            <?php echo $has_pin ? 'กรุณากรอกรหัส PIN 6 หลักของคุณเพื่อเข้าสู่ระบบ' : 'เพื่อความปลอดภัย กรุณาตั้งรหัส PIN 6 หลักสำหรับการเข้าใช้งานครั้งต่อไป'; ?>
        </p>
        
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        
        <div class="pin-container">
            <input type="password" name="pin1" class="pin-input" maxlength="1" required autocomplete="off">
            <input type="password" name="pin2" class="pin-input" maxlength="1" required autocomplete="off">
            <input type="password" name="pin3" class="pin-input" maxlength="1" required autocomplete="off">
            <input type="password" name="pin4" class="pin-input" maxlength="1" required autocomplete="off">
            <input type="password" name="pin5" class="pin-input" maxlength="1" required autocomplete="off">
            <input type="password" name="pin6" class="pin-input" maxlength="1" required autocomplete="off">
        </div>
        
        <button type="submit"><i class="fas fa-check-circle" style="margin-right: 6px;"></i> ยืนยัน</button>
        <button type="button" class="btn-cancel" onclick="window.parent.location.href='logout.php';">ยกเลิก</button>

        <div class="system-logo-text">
            &copy; eDebtor Hospital System
        </div>
    </form>

    <script>
        // สคริปต์เพื่อให้พิมพ์รหัสแล้วเลื่อนไปช่องถัดไปอัตโนมัติ
        const inputs = document.querySelectorAll('.pin-input');
        
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
        
        // Auto focus ช่องแรก
        if(inputs.length > 0) {
            inputs[0].focus();
        }
    </script>
</body>
</html>
