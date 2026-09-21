<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 🛡️ ป้องกันความปลอดภัยขั้นที่ 1: ตรวจสอบเซสชันผู้ใช้งาน
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ตรวจสอบว่ามีการส่งไฟล์มาจริงไหม
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
        exit();
    }

    $file = $_FILES['pdf_file'];
    $month = filter_input(INPUT_POST, 'month', FILTER_SANITIZE_STRING) ?? date('m-Y');
    $file_type = filter_input(INPUT_POST, 'file_type', FILTER_SANITIZE_STRING) ?? 'document';

    // 🛡️ ป้องกันความปลอดภัยขั้นที่ 2: จำกัดขนาดไฟล์ไม่เกิน 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'File size limits exceeded (Max 10MB).']);
        exit();
    }

    // 📁 ตั้งชื่อโฟลเดอร์ปลายทางที่เตรียมไว้
    $upload_dir = './saved_reports/';
    
    // ถ้ายังไม่มีโฟลเดอร์ ให้ระบบสร้างอัตโนมัติพร้อมตั้งสิทธิ์ความปลอดภัย
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // ทำความสะอาดชื่อไฟล์ ป้องกันการโจมตีประเภท Path Traversal
    $safe_month = preg_replace('/[^a-zA-Z0-9\-]/', '_', $month);
    $safe_type = preg_replace('/[^a-zA-Z0-9\-]/', '_', $file_type);
    
    // ตั้งชื่อไฟล์ปลายทางให้เป็นระบบระเบียบ
    $file_name = 'ทะเบียนคุมลูกหนี้สิทธิ' . '_' . $safe_month. '.pdf';
    $destination = $upload_dir . $file_name;

    // 🛡️ ป้องกันความปลอดภัยขั้นที่ 3: บันทึกไฟล์อย่างปลอดภัย
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // ส่ง URL กลับไปให้ฝั่ง Javascript เอาไปกดเปิดหน้าต่างใหม่ดูวิวรายงานได้ทันที
        $file_url = 'saved_reports/' . $file_name;
        echo json_encode([
            'status' => 'success', 
            'message' => 'File saved successfully.',
            'file_url' => $file_url
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Check folder permissions.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>