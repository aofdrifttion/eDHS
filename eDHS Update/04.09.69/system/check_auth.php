<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!function_exists('checkRole')) {
    function checkRole($allowed_roles) {
        if (!isset($_SESSION['role'])) {
            header("Location: login.php");
            exit();
        }
        
        $user_role = $_SESSION['role'];
        
        if (is_array($allowed_roles)) {
            if (!in_array($user_role, $allowed_roles)) {
                die("<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.history.back();</script>");
            }
        } else {
            if ($user_role !== $allowed_roles) {
                die("<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.history.back();</script>");
            }
        }
    }
}
?>
