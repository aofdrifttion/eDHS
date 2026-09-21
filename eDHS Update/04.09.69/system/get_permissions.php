<?php
require './database_config/config.php';
$user_id = $_POST['user_id'];

mysqli_set_charset($conn, 'utf8mb4');

// 1. ดึงสิทธิ์ที่เขามีอยู่แล้ว มาใส่ Array ก่อน
$user_perms = [];
$sql_perms = "SELECT menu_id FROM tb_user_permissions WHERE user_id = '$user_id'";
$res_perms = mysqli_query($conn, $sql_perms);
if ($res_perms) {
    while($row = mysqli_fetch_assoc($res_perms)){
        $user_perms[] = $row['menu_id'];
    }
}

// 2. ดึงรายชื่อเมนูทั้งหมดมาแสดง
$sql_menus = "SELECT * FROM tb_menus ORDER BY sort_order ASC";
$res_menus = mysqli_query($conn, $sql_menus);


$current_group = '';
if (mysqli_num_rows($res_menus) > 0) {
    while($menu = mysqli_fetch_assoc($res_menus)){
        // ถ้าเปลี่ยนกลุ่มเมนู ให้ขึ้นหัวข้อใหม่
        if($current_group != $menu['menu_group']){
            echo "<h6 class='mt-3 mb-2 text-primary fw-bold'>{$menu['menu_group']}</h6>";
            $current_group = $menu['menu_group'];
        }
        
        // เช็คว่าเมนูนี้อยู่ใน Array สิทธิ์ที่ดึงมาแต่แรกไหม
        $checked = in_array($menu['id'], $user_perms) ? 'checked' : '';
        
        echo "
        <div class='form-check form-switch mb-2 ms-3'>
          <input class='form-check-input' type='checkbox' name='menus[]' value='{$menu['id']}' id='menu_{$menu['id']}' {$checked}>
          <label class='form-check-label' for='menu_{$menu['id']}'>
            {$menu['menu_icon']} {$menu['menu_name']}
          </label>
        </div>
        ";
    }
} else {
    echo "<div class='text-danger'>ยังไม่ได้สร้างเมนูในระบบ</div>";
}
?>