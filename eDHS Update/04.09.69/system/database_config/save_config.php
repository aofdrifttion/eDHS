<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $configFile = __DIR__ . "/config.json";
    $configData = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

    $configData["db1"] = [
        "servername" => $_POST["db1_servername"],
        "username" => $_POST["db1_username"],
        "password" => $_POST["db1_password"],
        "dbname" => $_POST["db1_dbname"]
    ];
    
    $configData["db2"] = [
        "servername" => $_POST["db2_servername"],
        "username" => $_POST["db2_username"],
        "password" => $_POST["db2_password"],
        "dbname" => $_POST["db2_dbname"]
    ];
    
    $configData["hospital"] = $_POST["hospital"];
    $configData["hospcode"] = $_POST["hospcode"];
    $configData["Client_ID"] = $_POST["Client_ID"];
    $configData["Secret"] = $_POST["Secret"];
    $configData["notify_enable"] = isset($_POST["notify_enable"]) ? $_POST["notify_enable"] : "0";

    file_put_contents(__DIR__ . "/config.json", json_encode($configData, JSON_PRETTY_PRINT));

    header("Location: index.php?saved=1");
    exit;
}
?>