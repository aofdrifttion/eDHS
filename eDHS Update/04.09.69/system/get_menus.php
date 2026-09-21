<?php
require 'c:/xampp8/htdocs/eDHS/system/database_config/config.php';
$result = $conn->query('SELECT menu_name, menu_link FROM tb_menus ORDER BY sort_order ASC');
while($row = $result->fetch_assoc()) {
    echo $row['menu_name'] . ' -> ' . $row['menu_link'] . "\n";
}
?>
