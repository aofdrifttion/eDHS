<?php
session_start();
include './database_config/config.php';

if (isset($_SESSION['user_id']) && function_exists('system_log')) {
    system_log($conn, 'ระบบสมาชิก', 'LOGOUT', 'ผู้ใช้ออกจากระบบ');
}

session_destroy();

// Redirect พร้อมส่งพารามิเตอร์ logout=success
header("Location: login.php?logout=success");
exit();
