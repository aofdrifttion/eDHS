<?php
$configData = json_decode(file_get_contents(__DIR__ . "/database_config/config.json"), true);
$db1 = $configData["db1"];
$conn = new mysqli($db1["servername"], $db1["username"], $db1["password"], $db1["dbname"]);
mysqli_set_charset($conn, "utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// check if menu exists
$res = $conn->query("SELECT * FROM tb_menus WHERE menu_link = 'report_logs.php'");
if ($res->num_rows == 0) {
    $sql = "INSERT INTO tb_menus (menu_name, menu_link, menu_icon, menu_group, sort_order) 
            VALUES ('รายงานประวัติการใช้งาน', 'report_logs.php', '<i class=\"bx bx-list-ul\"></i>', 'ผู้ดูแลระบบ', 99)";
    if ($conn->query($sql) === TRUE) {
        echo "Menu added successfully\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} else {
    echo "Menu already exists\n";
}
?>
