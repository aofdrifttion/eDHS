<?php
include './database_config/config.php';

$message = ""; // ใช้สำหรับแสดงข้อความแจ้งเตือน

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = htmlspecialchars(trim($_POST['fullname']), ENT_QUOTES, 'UTF-8');
    $role = $_POST['role'];
    
    // จัดการ PIN 6 หลัก
    $pin_code = trim($_POST['pin_code']);
    if (strlen($pin_code) === 6 && is_numeric($pin_code)) {
        $hashed_pin = password_hash($pin_code, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, role, status, pin_code) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("sssss", $username, $password, $fullname, $role, $hashed_pin);

        if ($stmt->execute()) {
            $message = '<div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center shadow-sm"><i class="fas fa-check-circle mr-3 text-xl"></i><span>สมัครสมาชิกเรียบร้อย! กรุณารอผู้ดูแลระบบอนุมัติ</span></div>';
        } else {
            $message = '<div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 flex items-center shadow-sm"><i class="fas fa-exclamation-circle mr-3 text-xl"></i><span>เกิดข้อผิดพลาด กรุณาลองใหม่</span></div>';
        }
    } else {
        $message = '<div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 flex items-center shadow-sm"><i class="fas fa-exclamation-circle mr-3 text-xl"></i><span>รหัส PIN ต้องเป็นตัวเลข 6 หลักเท่านั้น</span></div>';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>สมัครสมาชิก - eDHS</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&family=Public+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Public Sans', 'Poppins', 'sans-serif'],
                    },
                    colors: {
                        primary: '#696cff',
                        primaryHover: '#5f61e6',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            background-color: #f5f5f9; 
            background-image: 
                radial-gradient(at 0% 0%, rgba(105, 108, 255, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(0, 210, 255, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(105, 108, 255, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(255, 171, 0, 0.1) 0px, transparent 50%);
            overflow-x: hidden;
            font-family: 'Public Sans', 'Poppins', sans-serif;
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
        .shape-2 { width: 400px; height: 400px; background: #00d2ff; right: -100px; animation-delay: -5s; }
        .shape-3 { width: 200px; height: 200px; background: #ffab00; top: 40%; left: 60%; animation-duration: 15s; }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -50px) scale(1.1); }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-[#566a7f] relative">

    <!-- Background Animated Shapes -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>

    <div class="glass-card w-full max-w-lg rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.08)] p-8 relative z-10 my-8">
        
        <div class="flex flex-col items-center mb-6">
            <div class="w-16 h-16 bg-indigo-50 text-primary rounded-xl flex items-center justify-center text-3xl mb-4 shadow-[0_4px_15px_rgba(105,108,255,0.2)]">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2 class="text-2xl font-bold text-[#32475c]">สมัครสมาชิก</h2>
            <p class="text-sm text-[#a1acb8] mt-1">เข้าร่วมใช้งานระบบ eDHS Dashboard</p>
        </div>

        <?= $message; ?>

        <form method="POST" class="space-y-5" autocomplete="off">
            
            <!-- ชื่อ-นามสกุล -->
            <div>
                <label class="block text-xs font-semibold text-[#566a7f] uppercase tracking-wide mb-2">ชื่อ-นามสกุล</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors group-focus-within:text-primary text-gray-400">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <input type="text" name="fullname" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors bg-white" placeholder="กรอกชื่อ-นามสกุล" required autocomplete="off">
                </div>
            </div>

            <!-- ชื่อผู้ใช้ -->
            <div>
                <label class="block text-xs font-semibold text-[#566a7f] uppercase tracking-wide mb-2">ชื่อผู้ใช้ (Username)</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors group-focus-within:text-primary text-gray-400">
                        <i class="fas fa-user"></i>
                    </div>
                    <input type="text" name="username" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors bg-white" placeholder="กรอกชื่อผู้ใช้" required autocomplete="off">
                </div>
            </div>

            <!-- รหัสผ่าน -->
            <div>
                <label class="block text-xs font-semibold text-[#566a7f] uppercase tracking-wide mb-2">รหัสผ่าน (Password)</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors group-focus-within:text-primary text-gray-400">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" id="reg_password" name="password" class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors bg-white" placeholder="ตั้งรหัสผ่านของคุณ" required autocomplete="new-password">
                    <button type="button" id="toggleRegPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-primary transition-colors">
                        <i class="fas fa-eye" id="eyeIconReg"></i>
                    </button>
                </div>
            </div>

            <!-- รหัส PIN -->
            <div>
                <label class="block text-xs font-semibold text-[#566a7f] uppercase tracking-wide mb-2">รหัส PIN 6 หลัก <span class="text-gray-400 normal-case">(ใช้ยืนยันการทำรายการสำคัญ)</span></label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors group-focus-within:text-primary text-gray-400">
                        <i class="fas fa-key"></i>
                    </div>
                    <input type="password" id="reg_pin" name="pin_code" class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors bg-white tracking-[0.3em] font-mono" placeholder="ตั้งรหัส PIN 6 หลัก" maxlength="6" pattern="\d{6}" required autocomplete="new-password">
                    <button type="button" id="toggleRegPin" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-primary transition-colors">
                        <i class="fas fa-eye" id="eyeIconRegPin"></i>
                    </button>
                </div>
            </div>

            <!-- บทบาท -->
            <div>
                <label class="block text-xs font-semibold text-[#566a7f] uppercase tracking-wide mb-2">เลือกบทบาท (Role)</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors group-focus-within:text-primary text-gray-400">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <select name="role" class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors bg-white appearance-none cursor-pointer text-[#566a7f]" required>
                        <option value="" disabled selected>-- กรุณาเลือกบทบาท --</option>
                        <option value="insurance">งานประกันฯ</option>
                        <option value="finance">งานการเงิน</option>
                        <option value="accounting">งานบัญชี</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex gap-4 mt-2">
                <button type="submit" class="flex-1 bg-gradient-to-br from-primary to-primaryHover text-white py-3 rounded-lg font-semibold shadow-[0_4px_14px_rgba(105,108,255,0.4)] hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(105,108,255,0.5)] transition-all duration-300">
                    <i class="fas fa-check-circle mr-2"></i> ยืนยันการสมัคร
                </button>
                <a href="./login.php" class="flex-none px-6 py-3 bg-white text-gray-500 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50 hover:text-gray-700 transition-colors flex items-center justify-center">
                    ยกเลิก
                </a>
            </div>

        </form>

        <div class="text-center mt-6 text-xs text-[#a1acb8] font-medium">
            &copy; eDebtor Hospital System
        </div>
    </div>

<script>
    // Toggle Password Visibility
    document.getElementById('toggleRegPassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('reg_password');
        const eyeIcon = document.getElementById('eyeIconReg');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });

    // Toggle PIN Visibility
    document.getElementById('toggleRegPin').addEventListener('click', function () {
        const pinInput = document.getElementById('reg_pin');
        const eyeIcon = document.getElementById('eyeIconRegPin');
        
        if (pinInput.type === 'password') {
            pinInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            pinInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });
</script>
</body>
</html>
