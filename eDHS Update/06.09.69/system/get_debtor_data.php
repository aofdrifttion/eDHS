<?php
header('Content-Type: application/json');
require './database_config/config.php';
require_once './database_config/db_helper.php';

$vn = isset($_GET['vn']) ? $_GET['vn'] : '';
$vndelete = isset($_GET['vndelete']) ? $_GET['vndelete'] : '';

// กำหนดค่าเริ่มต้นของ response
$response = ['status' => 'error', 'message' => 'Invalid request or empty VN/AN'];


/* ================= DELETE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['vndelete'])) {

    $vndelete = trim($_POST['vndelete']);

    // ตรวจสอบความปลอดภัยทางการเงิน: ห้ามลบหากออกใบเสร็จหรือได้รับเงินชดเชยแล้ว
    $stmt_chk = $conn->prepare("SELECT ((bill IS NOT NULL AND TRIM(bill) != '') OR (follow_money IS NOT NULL AND CAST(follow_money AS DECIMAL(15,2)) > 0)) as is_billed FROM imr_tb_debtor_rights_opd WHERE vn = ?");
    if ($stmt_chk) {
        $stmt_chk->bind_param("s", $vndelete);
        $stmt_chk->execute();
        $res_chk = $stmt_chk->get_result();
        if ($res_chk && $res_chk->num_rows > 0) {
            $r_chk = $res_chk->fetch_assoc();
            if (!empty($r_chk['is_billed'])) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากรายการนี้ถูกออกใบเสร็จหรือได้รับเงินชดเชยแล้ว']);
                exit;
            }
        }
        $stmt_chk->close();
    }

    // ลบจาก OPD
    $sql = "DELETE FROM imr_tb_debtor_rights_opd WHERE vn = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $response['message'] = 'Prepare failed: ' . $conn->error;
    } else {
        $stmt->bind_param("s", $vndelete);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // ล้างข้อมูลตารางแจกแจงกลุ่มย่อย CR / SSS ของคนไข้รายนี้ด้วยเพื่อป้องกันข้อมูลตกค้าง
            $safe_vn = mysqli_real_escape_string($conn, $vndelete);
            @$conn->query("DELETE FROM imr_tb_debtor_cr_breakdown WHERE vn = '$safe_vn'");
            @$conn->query("DELETE FROM imr_tb_debtor_sss_breakdown WHERE vn = '$safe_vn'");

            $response = [
                'status'  => 'success',
                'message' => 'ลบข้อมูลเรียบร้อย',
                'vn'      => $vndelete
            ];
        } else {
            $response = [
                'status'  => 'error',
                'message' => 'ไม่พบข้อมูลสำหรับลบ'
            ];
        }
        $stmt->close();
    }

    echo json_encode($response);
    exit; // 🔥 สำคัญมาก ต้องจบตรงนี้
}
/* ========================================= */


if (!empty($vn)) {
    // 1. ค้นหาในตาราง OPD ก่อน
    $sql_opd = "SELECT * FROM imr_tb_debtor_rights_opd WHERE vn = ?";
    $stmt_opd = $conn->prepare($sql_opd);

    if ($stmt_opd === false) {
        $response['message'] = "Error preparing OPD query: " . $conn->error;
    } else {
        $stmt_opd->bind_param("s", $vn);
        $stmt_opd->execute();
        $result_opd = $stmt_opd->get_result();

        if ($result_opd->num_rows > 0) {
            // --- ถ้าเจอข้อมูลในตาราง OPD ---
            $row = $result_opd->fetch_assoc();
            if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
            if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
            $row['visit_type'] = 'opd'; // เพิ่มประเภทเข้าไปในข้อมูลเพื่อให้ Frontend รู้
            $response = ['status' => 'success', 'data' => $row];
        } else {
            // --- ถ้าไม่เจอใน OPD ให้ค้นหาใน IPD ต่อ ---
            // สังเกตว่าเราจะค้นหาจากคอลัมน์ 'an'
            $sql_ipd = "SELECT * FROM imr_tb_debtor_rights_ipd WHERE an = ?";
            $stmt_ipd = $conn->prepare($sql_ipd);

            if ($stmt_ipd === false) {
                $response['message'] = "Error preparing IPD query: " . $conn->error;
            } else {
                $stmt_ipd->bind_param("s", $vn);
                $stmt_ipd->execute();
                $result_ipd = $stmt_ipd->get_result();

                if ($result_ipd->num_rows > 0) {
                    // --- ถ้าเจอข้อมูลในตาราง IPD ---
                    $row = $result_ipd->fetch_assoc();
                    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
                    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
                    $row['visit_type'] = 'ipd'; // เพิ่มประเภทเข้าไปในข้อมูล
                    $response = ['status' => 'success', 'data' => $row];
                } else {
                    // --- ถ้าไม่เจอทั้งสองตาราง ---
                    $response = ['status' => 'error', 'message' => 'ไม่พบข้อมูลสำหรับ VN/AN นี้'];
                }
                $stmt_ipd->close();
            }
        }
        $stmt_opd->close();
    }
}

echo json_encode($response);
?>