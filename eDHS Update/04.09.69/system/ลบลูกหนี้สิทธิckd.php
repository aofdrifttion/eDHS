<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
ignore_user_abort(true);
set_time_limit(0);
require './database_config/config.php';

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || !isset($_SESSION['fullname'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_ckd') {
    
    if (empty($_POST['deleteckd']) || empty($_POST['password'])) {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
        exit;
    }

    $deleteckd = $_POST['deleteckd'];
    $password = $_POST['password'];
    $user_id = $_SESSION['user_id'];

    // 2. ตรวจสอบรหัสผ่าน
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านไม่ถูกต้อง']);
        exit;
    }

    // --- เริ่ม Transaction ---
    $conn->begin_transaction();

    try {
        // เตรียมคำสั่ง Update ไว้รอ (เพื่อใช้ซ้ำใน Loop)
        $stmt_update = $conn->prepare("UPDATE imr_tb_debtor_rights_opd SET mobile='-', billdate='', bill='' WHERE vn = ?");

        // 3. แยกกรณีตามประเภทของ $deleteckd
        if (strpos($deleteckd, 'DCKD') !== false || strpos($deleteckd, 'SOCD') !== false || strpos($deleteckd, 'LGO') !== false) {
            
            // ดึง vn จาก imr_tb_seamless_dckd
            $stmt_find = $conn->prepare("SELECT vn FROM imr_tb_seamless_dckd WHERE rep = ?");
            $stmt_find->bind_param("s", $deleteckd);
            $stmt_find->execute();
            $res_find = $stmt_find->get_result();

            while ($row = $res_find->fetch_assoc()) {
                if (!empty($row['vn'])) {
                    $stmt_update->bind_param("s", $row['vn']);
                    $stmt_update->execute();
                }
            }

            // ลบข้อมูลหลัก
            $stmt_del = $conn->prepare("DELETE FROM imr_tb_seamless_dckd WHERE rep = ?");
            $stmt_del->bind_param("s", $deleteckd);
            $stmt_del->execute();

        } else {
            // กรณีเป็น STM_DOC (ดึง vn จากตาราง _ofc)
            $stmt_find = $conn->prepare("SELECT vn FROM imr_tb_seamless_dckd_ofc WHERE stm_doc = ?");
            $stmt_find->bind_param("s", $deleteckd);
            $stmt_find->execute();
            $res_find = $stmt_find->get_result();

            while ($row = $res_find->fetch_assoc()) {
                if (!empty($row['vn'])) {
                    $stmt_update->bind_param("s", $row['vn']);
                    $stmt_update->execute();
                }
            }

            // ลบทั้งสองตาราง
            $stmt_del1 = $conn->prepare("DELETE FROM imr_tb_seamless_dckd_summary_ofc WHERE stm_doc = ?");
            $stmt_del1->bind_param("s", $deleteckd);
            $stmt_del1->execute();

            $stmt_del2 = $conn->prepare("DELETE FROM imr_tb_seamless_dckd_ofc WHERE stm_doc = ?");
            $stmt_del2->bind_param("s", $deleteckd);
            $stmt_del2->execute();
        }

        // ยืนยันการทำงานทั้งหมด
        $conn->commit();
        if (function_exists('system_log')) {
            system_log($conn, 'ระบบลูกหนี้ (Delete)', 'DELETE', "ลบข้อมูลลูกหนี้สิทธิ CKD/OFC ($deleteckd) เรียบร้อยแล้ว");
        }
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);

    } catch (Exception $e) {
        // หากพลาดแม้แต่จุดเดียว ให้คืนค่าทั้งหมด
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
}
?>