<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("Invalid request");
}

$filename = basename($_GET['file']);
$backupDir = __DIR__ . '/../backups_data/';
$filepath = $backupDir . $filename;

// Security check
if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'zip') {
    die("File not found or invalid type");
}

header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
?>
