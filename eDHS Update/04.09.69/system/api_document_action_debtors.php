<?php
// api_document_action_debtors.php
header('Content-Type: application/json; charset=utf-8');
require_once './database_config/config.php';

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? ''; // VN หรือ AN
$type = $_POST['type'] ?? ''; // OPD หรือ IPD

if (empty($id) || empty($type)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// ตรวจสอบ/สร้าง row ใน DB
$checkSql = "SELECT id, return_doc_file FROM imr_tb_debt_doc_tracking WHERE vn_an = ? AND type = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ss", $id, $type);
$stmt->execute();
$result = $stmt->get_result();
$existingRow = $result->fetch_assoc();

if (!$existingRow) {
    $insertSql = "INSERT INTO imr_tb_debt_doc_tracking (vn_an, type) VALUES (?, ?)";
    $stmtIns = $conn->prepare($insertSql);
    $stmtIns->bind_param("ss", $id, $type);
    $stmtIns->execute();
}

// --- Action: บันทึกว่าพิมพ์แล้ว ---
if ($action === 'mark_printed') {
    $sql = "UPDATE imr_tb_debt_doc_tracking SET print_date = NOW() WHERE vn_an = ? AND type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $id, $type);
    
    if ($stmt->execute()) {
        if (function_exists('system_log')) system_log($conn, 'ระบบติดตามเอกสาร', 'UPDATE', "บันทึกสถานะการพิมพ์เอกสารติดตามหนี้ รหัส: $id ($type)");
        echo json_encode(['status' => 'success', 'message' => 'บันทึกสถานะการพิมพ์แล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ']);
    }

// --- Action: อัปโหลด/แก้ไขไฟล์ ---
} elseif ($action === 'upload') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // 1. ลบไฟล์เก่าก่อน (ถ้ามี) - เพื่อไม่ให้ไฟล์ขยะล้น Server
        if ($existingRow && !empty($existingRow['return_doc_file'])) {
            $oldFilePath = $uploadDir . $existingRow['return_doc_file'];
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        // 2. อัปโหลดไฟล์ใหม่
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $newFileName = "doc_{$type}_{$id}_" . time() . "." . $ext;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $sql = "UPDATE imr_tb_debt_doc_tracking SET return_doc_file = ? WHERE vn_an = ? AND type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $newFileName, $id, $type);
            
            if ($stmt->execute()) {
                if (function_exists('system_log')) system_log($conn, 'ระบบติดตามเอกสาร', 'UPDATE', "อัปโหลดเอกสารติดตามหนี้ รหัส: $id ($type)");
                echo json_encode(['status' => 'success', 'message' => 'อัปโหลดไฟล์เรียบร้อย', 'file' => $newFileName]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'บันทึกฐานข้อมูลไม่สำเร็จ']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ย้ายไฟล์ไม่สำเร็จ']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบไฟล์ที่อัปโหลด']);
    }

// --- Action: [ใหม่] ลบไฟล์เอกสาร ---
} elseif ($action === 'delete_file') {
    if ($existingRow && !empty($existingRow['return_doc_file'])) {
        // 1. ลบไฟล์จริง
        $filePath = 'uploads/' . $existingRow['return_doc_file'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // 2. อัปเดตฐานข้อมูลเป็น NULL
        $sql = "UPDATE imr_tb_debt_doc_tracking SET return_doc_file = NULL WHERE vn_an = ? AND type = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $id, $type);
        
        if ($stmt->execute()) {
            if (function_exists('system_log')) system_log($conn, 'ระบบติดตามเอกสาร', 'DELETE', "ลบไฟล์เอกสารติดตามหนี้ รหัส: $id ($type)");
            echo json_encode(['status' => 'success', 'message' => 'ลบเอกสารเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'อัปเดตฐานข้อมูลไม่สำเร็จ']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบไฟล์ที่ต้องการลบ']);
    }
}
?>