<?php
require './database_config/config.php';

$user_id = $_POST['user_id'] ?? '';
$menus = $_POST['menus'] ?? [];

if(!empty($user_id)){
    // ลบสิทธิ์เก่าออกให้หมดเกลี้ยงก่อน (วิธีนี้ชัวร์สุด ไม่ซ้ำซ้อน)
    $clean_old = "DELETE FROM tb_user_permissions WHERE user_id = '$user_id'";
    mysqli_query($conn, $clean_old);

    // ถ้ามีการติ๊กเลือกเมนูมา ให้วนลูป Insert
    if(!empty($menus) && is_array($menus)){
        foreach($menus as $menu_id){
            $menu_id = mysqli_real_escape_string($conn, $menu_id);
            $sql_insert = "INSERT INTO tb_user_permissions (user_id, menu_id) VALUES ('$user_id', '$menu_id')";
            mysqli_query($conn, $sql_insert);
        }
    }
    echo "success";
} else {
    echo "ไม่พบรหัสผู้ใช้";
}
?>