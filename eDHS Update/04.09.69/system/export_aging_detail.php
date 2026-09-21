<?php
// ไฟล์: export_aging_detail.php
// ปรับปรุงการส่งออกเป็น TSV (.xls) และกำหนดให้เป็น Text เพื่อป้องกันการจัดรูปแบบผิดพลาด

session_start();
// ตรวจสอบชื่อไฟล์ Config ของพี่ด้วยนะครับ
require './database_config/config.php'; 

// 1. รับค่าที่ส่งมาจาก Hidden Form
$sql = $_POST['sql'] ?? '';
$filename_base = $_POST['filename'] ?? 'Aging_Debtor_Detail';

if (empty($sql)) {
    exit('Error: Missing SQL Query.');
}

// 2. เตรียม Headers สำหรับการส่งออก TSV/Excel (.xls)
// ใช้ .xls เพื่อให้ Excel เปิดได้ง่ายขึ้น และใช้ Content-type ที่เป็นมาตรฐานของ Excel
$filename = "{$filename_base}_".date('YmdHis').".xls"; 

// 🚨 นี่คือ Content-Type ที่จะทำให้ Excel เปิดไฟล์ได้ง่ายกว่า text/csv
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');

// 3. เปิด output stream
$output = fopen('php://output', 'w');

// ตั้งค่า encoding (สำหรับภาษาไทย): เพิ่ม BOM สำหรับ Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); 

// 4. รัน Query
$result = $conn->query($sql);

// 5. เขียน Header Row (ชื่อคอลัมน์) โดยใช้ Tab (\t) เป็นตัวคั่น
$header = [
    'HN', 
    'VN/AN', 
    'ชื่อ-นามสกุล', 
    'มูลค่าลูกหนี้ (บาท)', 
    'วันที่ตั้งหนี้ (ว/ด/ป)', 
    'ประเภท'
];

// ใช้ implode("\t", ...) แทน fputcsv
fwrite($output, implode("\t", $header) . "\n");

// 6. วนลูปเขียน Data
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        
        // 🚨 เทคนิคการบังคับให้เป็น Text: เติม Single Quote (') นำหน้าฟิลด์ที่ต้องการ
        // ใช้ number_format โดยไม่มี comma เพื่อให้ Excel อ่านเป็นตัวเลขได้
        $debt_amount = number_format($row['debt_amount'], 2, '.', ''); 
        
        $rowData = [
            "'" . $row['hn'],           // HN (Text)
            "'" . $row['visit_id'],     // VN/AN (Text)
            $row['ptname'],             // ชื่อ (Text)
            $debt_amount,               // มูลค่าลูกหนี้ (ตัวเลข)
            "'" . (new DateTime($row['debt_date']))->format('d/m/Y'), // วันที่ตั้งหนี้ (Text)
            $row['type']                // ประเภท (Text)
        ];
        
        // เขียน Row Data โดยใช้ Tab (\t) เป็นตัวคั่น
        fwrite($output, implode("\t", $rowData) . "\n");
    }
} else {
    fwrite($output, "ไม่พบข้อมูลลูกหนี้ในเงื่อนไขที่เลือก\n");
}

// 7. ปิด output stream และปิดการเชื่อมต่อ
fclose($output);
$conn->close();
exit; 
?>