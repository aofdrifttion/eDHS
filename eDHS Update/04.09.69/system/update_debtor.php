<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json'); 
require './database_config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vn'])) {
    
    $id_code = $_POST['vn']; 
    
    $new_pttype_code = $_POST['new_pttype_code'];
    $new_pttype_name = $_POST['new_pttype_name'];
    $new_pttype_eclaim_id = $_POST['new_pttype_eclaim_id'];
    $new_pttype_eclaim_name = $_POST['new_pttype_eclaim_name'];
    $new_accountcode = $_POST['new_accountcode'];
    $new_accountname = $_POST['new_accountname'];

    $new_income = $_POST['new_income'];
    
    $new_uc_money = $new_income;
    $new_discount_money = 0;
    $new_paid_money = 0;
    $new_rcpt_money = 0;
    $new_debit = $new_income;
    $new_totalall = $new_income;

    // --------------------------------------------------------------------------
    // ส่วนที่ 1: ตรวจสอบว่าเป็น OPD หรือ IPD + ดึงค่าใช้จ่ายเดิม (Old Income)
    // --------------------------------------------------------------------------
    $table_name = '';
    $pk_column = '';
    $old_income = 0; // ตัวแปรสำหรับเก็บค่าใช้จ่ายเดิมก่อนจะโดนเขียนทับ

    // 1.1 เช็คในตาราง OPD และดึงค่า income เดิมมาเก็บไว้
    $checkOpd = $conn->prepare("SELECT income FROM imr_tb_debtor_rights_opd WHERE vn = ?");
    $checkOpd->bind_param("s", $id_code);
    $checkOpd->execute();
    $checkOpd->store_result();
    if ($checkOpd->num_rows > 0) {
        $table_name = 'imr_tb_debtor_rights_opd';
        $pk_column = 'vn';
        $checkOpd->bind_result($old_income); // ผูกค่าเข้าตัวแปร $old_income
        $checkOpd->fetch();
    }
    $checkOpd->close();

    // 1.2 ถ้าไม่เจอใน OPD ให้เช็คในตาราง IPD และดึงค่า income เดิมมาเก็บไว้เช่นกัน
    if ($table_name == '') {
        $checkIpd = $conn->prepare("SELECT income FROM imr_tb_debtor_rights_ipd WHERE an = ?");
        $checkIpd->bind_param("s", $id_code);
        $checkIpd->execute();
        $checkIpd->store_result();
        if ($checkIpd->num_rows > 0) {
            $table_name = 'imr_tb_debtor_rights_ipd';
            $pk_column = 'an'; 
            $checkIpd->bind_result($old_income); // ผูกค่าเข้าตัวแปร $old_income
            $checkIpd->fetch();
        }
        $checkIpd->close();
    }

    if ($table_name == '') {
        echo "false"; 
        exit;
    }

    // --------------------------------------------------------------------------
    // ส่วนที่ 2: จัดการไฟล์อัปโหลด (ใช้ตามเงื่อนไขบังคับ PDF และขนาดไม่เกิน 10MB เดิม)
    // --------------------------------------------------------------------------
    $file_path = '';
    $upload_dir = __DIR__ . '/uploads/'; 
    $file_sql_part = ""; 
    
    if (!isset($_FILES['rcpno']) || $_FILES['rcpno']['error'] !== UPLOAD_ERR_OK) {
        echo "false";
        exit;
    }

    $file_name = $_FILES['rcpno']['name'];
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $file_tmp = $_FILES['rcpno']['tmp_name'];
    $file_size = $_FILES['rcpno']['size'];

    if (strtolower($file_ext) !== 'pdf' || $file_size > 10485760) {
        echo "false";
        exit;
    }

    // เปิดใช้งาน Session เพื่อดึงข้อมูลผู้ใช้งานตามที่พี่ต้องการ
    session_start(); 
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'UnknownID';
    $raw_fullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'UnknownName';
    $safe_fullname = str_replace(' ', '_', $raw_fullname); 

    $new_file_name = $id_code . '_' . $user_id . '_' . $safe_fullname . '_' . uniqid() . '.' . $file_ext;
    $target_file = $upload_dir . $new_file_name;
    $db_file_path = 'uploads/' . $new_file_name;

    if (move_uploaded_file($file_tmp, $target_file)) {
        $file_sql_part = ", rcpno = ?"; 
        $file_path = $db_file_path; 
    } else {
        echo "false";
        exit;
    }

    // --------------------------------------------------------------------------
    // ส่วนที่ 3: Update ข้อมูล + บันทึกค่าใช้จ่ายเดิมลง rcpnodate
    // --------------------------------------------------------------------------
    
    // เพิ่มการบันทึกค่าลงคอลัมน์ rcpnodate เข้าไปใน SQL UPDATE
    $sql = "UPDATE $table_name
            SET pttype_eclaim_id = ?, pttype_eclaim_name = ?, pttype = ?, pttypename = ?,
                accountcode = ?, accountname = ?, income = ?, uc_money = ?,
                discount_money = ?, paid_money = ?, rcpt_money = ?, debit = ?,
                totalall = ?, rcpnodate = ? $file_sql_part
            WHERE $pk_column = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo "false";
        exit;
    }

    // เนื่องจากสคริปต์เราบังคับให้อัปโหลดไฟล์สำเร็จเท่านั้นถึงจะหลุดมาหน้านี้ได้ 
    // ตัวแปรที่นำมาผูกพารามิเตอร์จึงมีทั้งหมด 16 ตัวพอดี (มีไฟล์แน่นอน)
    $stmt->bind_param("ssssssssssssssss",
        $new_pttype_eclaim_id, $new_pttype_eclaim_name, $new_pttype_code, $new_pttype_name,
        $new_accountcode, $new_accountname, 
        $new_income, $new_uc_money, $new_discount_money, $new_paid_money, $new_rcpt_money, $new_debit, $new_totalall,
        $old_income,   // บันทึกค่าใช้จ่ายเดิมลง rcpnodate
        $file_path,    // บันทึกตำแหน่งไฟล์ลง rcpno
        $id_code       // เงื่อนไข WHERE
    );

    if ($stmt->execute()) {
        if (function_exists('system_log')) {
            system_log($conn, 'ระบบลูกหนี้ (Update)', 'UPDATE', "ผู้ใช้แก้ไขข้อมูลและอัปโหลดเอกสารของลูกหนี้รหัส: " . $id_code);
        }
        echo "true";
    } else {
        echo "false";
    }

    $stmt->close();
}
?>