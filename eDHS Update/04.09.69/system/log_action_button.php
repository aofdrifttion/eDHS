<?php
session_start();
require_once './database_config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = isset($_POST['action_type']) ? $_POST['action_type'] : 'VIEW';
    $module = isset($_POST['module']) ? $_POST['module'] : 'ระบบ';
    $details = isset($_POST['details']) ? $_POST['details'] : '';

    if (!empty($details) && function_exists('system_log')) {
        system_log($conn, $module, $action_type, $details);
    }
    echo "logged";
}
?>
