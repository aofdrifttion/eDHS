<?php
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
require_once './database_config/db_helper.php';
require 'vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// --- ตรวจสอบ Input ---
if (!isset($_GET['acc']) || !preg_match('/^\d{10}\.\d{3}$/', $_GET['acc'])) {
    die('❌ รูปแบบ acc ไม่ถูกต้อง');
}
if (!isset($_GET['month']) || !preg_match('/^\d{1,2}-\d{4}$/', $_GET['month'])) {
    die('❌ รูปแบบ month ไม่ถูกต้อง');
}

// 1. รับค่าและเตรียมตัวแปร
$acc    = $_GET['acc'];   
$monthe = $_GET['month']; // เช่น "11-2025"

// แปลง "11-2025" เป็น "2025-11" (YYYY-MM)
// ตัวแปรนี้จะใช้เทียบทั้ง Mobile, Billdate และ Monthtxt ได้เลย เพราะ Format ตรงกันแล้ว
list($month_num, $year_num) = explode('-', $monthe);
$target_ym = sprintf('%s-%02d', $year_num, $month_num); 

// 2. SQL Query
// หาเดือนล่าสุดในฐานข้อมูล

$sql_max = "
    SELECT CONCAT(
        SUBSTRING_INDEX(monthtxt,'-',-1),'-',
        LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
    ) AS ym
    FROM (
        SELECT monthtxt FROM imr_tb_debtor_rights_opd
        WHERE monthtxt IS NOT NULL AND monthtxt != ''
        UNION
        SELECT monthtxt FROM imr_tb_debtor_rights_ipd
        WHERE monthtxt IS NOT NULL AND monthtxt != ''
    ) AS t
    ORDER BY ym DESC
    LIMIT 1
";

$max_result = $conn->query($sql_max);
$max_row    = $max_result->fetch_assoc();
$max_ym     = $max_row['ym'] ?? $target_ym;


    $receiptWhere = "
        CASE
            WHEN billdate IS NOT NULL AND billdate != ''
             AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
            THEN CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) > ?

            WHEN mobile IS NOT NULL AND mobile != ''
             AND mobile != '-' AND CHAR_LENGTH(mobile) >= 10
            THEN CONCAT(
                   (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                   '-',
                   SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)
                 ) > ?

            ELSE TRUE
        END
    ";




$sql = "
SELECT * FROM (
    /* ==== OPD ==== */
    SELECT vn, vstdate, cid, ptname, mobile, billdate, accountcode,
            (IFNULL(debit, 0) - 
                CASE
                    WHEN billdate IS NOT NULL AND billdate != '' 
                     AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                     AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= ?
                    THEN IFNULL(follow_money, 0)

                    WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                     AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                     AND CHAR_LENGTH(mobile) >= 10
                     AND CONCAT(
                             (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                             '-',
                             SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)   -- ✅ แก้จาก -8 เป็น -6
                         ) <= ?
                    THEN IFNULL(follow_money, 0)

                    ELSE 0
                END

                -- ✅ เพิ่มใหม่: หัก pay_amount แบ่งจ่าย ≤ target
                - IFNULL((
                    SELECT SUM(ph.pay_amount)
                    FROM imr_tb_payment_history ph
                    WHERE ph.ref_vn_an = vn
                      AND ph.patient_type = 'OPD'
                      AND ph.bill_date IS NOT NULL
                      AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= ?
                  ), 0)
            ) AS debit,
           pttypename, 'OPD' as type
    FROM imr_tb_debtor_rights_opd
    WHERE accountcode = ? 
      AND IFNULL(debit, 0) > 0
      AND CONCAT(
            SUBSTRING_INDEX(monthtxt,'-',-1),'-',
            LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
          ) <= ?

    UNION ALL

    /* ==== IPD ==== */
    SELECT an AS vn, dchdate AS vstdate, cid, ptname, mobile, billdate, accountcode,
            (IFNULL(debit, 0) - 
                CASE
                    WHEN billdate IS NOT NULL AND billdate != '' 
                     AND billdate != '-' AND CHAR_LENGTH(billdate) >= 10
                     AND CONCAT((SUBSTR(billdate,7,4)-543),'-',SUBSTR(billdate,4,2)) <= ?
                    THEN IFNULL(follow_money, 0)

                    WHEN (billdate IS NULL OR billdate = '' OR billdate = '-')
                     AND mobile IS NOT NULL AND mobile != '' AND mobile != '-'
                     AND CHAR_LENGTH(mobile) >= 10
                     AND CONCAT(
                             (SUBSTR(mobile,CHAR_LENGTH(mobile)-3,4)-543),
                             '-',
                             SUBSTR(mobile,CHAR_LENGTH(mobile)-6,2)   -- ✅ แก้จาก -8 เป็น -6
                         ) <= ?
                    THEN IFNULL(follow_money, 0)

                    ELSE 0
                END

                -- ✅ เพิ่มใหม่: IPD ใช้ an และ patient_type = 'IPD'
                - IFNULL((
                    SELECT SUM(ph.pay_amount)
                    FROM imr_tb_payment_history ph
                    WHERE ph.ref_vn_an = an
                      AND ph.patient_type = 'IPD'
                      AND ph.bill_date IS NOT NULL
                      AND DATE_FORMAT(ph.bill_date,'%Y-%m') <= ?
                  ), 0)
            ) AS debit,
           pttypename, 'IPD' as type
    FROM imr_tb_debtor_rights_ipd
    WHERE accountcode = ? 
      AND IFNULL(debit, 0) > 0
      AND CONCAT(
            SUBSTRING_INDEX(monthtxt,'-',-1),'-',
            LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')
          ) <= ?

) AS all_debtors
WHERE 
    debit > 0
    AND ($receiptWhere)
ORDER BY pttypename, vn
";

$stmt = $conn->prepare($sql);

    // เพิ่มจาก 10 → 12 ตัว
    $stmt->bind_param('ssssssssssss',
        $target_ym,   // 1. OPD billdate ≤
        $target_ym,   // 2. OPD mobile ≤
        $target_ym,   // 3. OPD pay_amount ≤  ✅ เพิ่มใหม่
        $acc,         // 4. OPD accountcode
        $target_ym,   // 5. OPD monthtxt
        $target_ym,   // 6. IPD billdate ≤
        $target_ym,   // 7. IPD mobile ≤
        $target_ym,   // 8. IPD pay_amount ≤  ✅ เพิ่มใหม่
        $acc,         // 9. IPD accountcode
        $target_ym,   // 10. IPD monthtxt
        $target_ym,   // 11. outer billdate >
        $target_ym    // 12. outer mobile >
    );


$stmt->execute();
$result = $stmt->get_result();

// 5) ตรวจสอบว่ามีข้อมูล
if ($result->num_rows === 0) {
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'แจ้งเตือน',
                text: 'ไม่พบข้อมูลลูกหนี้รายตัวของรหัสผังบัญชีดังกล่าว...!',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.history.back(); // หรือใช้ window.location.href = 'กลับหน้ารายงาน';
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}



// 6) สร้างชีต Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ตรวจสอบว่ามีข้อมูลหรือไม่ (เผื่อผ่านการเช็ค num_rows มาแล้ว)
if ($result->num_rows > 0) {
    
    // 6.1) Header row
    $firstRow = $result->fetch_assoc();
    $headers = array_keys($firstRow);
    $sheet->fromArray($headers, NULL, 'A1');
    
    // หาตำแหน่งคอลัมน์ของ 'debit' (จะได้ใส่ยอดรวมถูกช่อง ไม่ต้องนั่งนับเอง)
    // +1 เพราะ array เริ่มที่ 0 แต่ Excel เริ่มที่ 1
    $debitColIndex = array_search('debit', $headers) + 1; 

    $result->data_seek(0); // ย้อน Cursor กลับไปบรรทัดแรก

    // 6.2) Data rows & คำนวณยอดรวม
    $row = 2;
    $total_debit = 0; // 🟢 ตัวแปรเก็บผลรวม

    while ($data = $result->fetch_assoc()) {
        $col = 1;
        
        // ถอดรหัส cid และ tel ถ้ามี
        if (isset($data['cid'])) {
            $data['cid'] = decrypt_data($data['cid']);
        }
        if (isset($data['tel'])) {
            $data['tel'] = decrypt_data($data['tel']);
        }

        // 🟢 บวกยอดเงินเข้าตัวแปร (แปลงเป็น float เพื่อความชัวร์)
        $total_debit += (float)$data['debit'];

        foreach (array_values($data) as $value) {
            $sheet->getCellByColumnAndRow($col, $row)
                  ->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
            // จัด Format เป็น Text ตามเดิมของพี่
            $sheet->getStyleByColumnAndRow($col, $row)
                  ->getNumberFormat()
                  ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
            $col++;
        }
        $row++;
    }

    // 6.3) 🟢 เพิ่มบรรทัดยอดรวม (Footer)
    // ถ้าหาคอลัมน์ debit เจอ ให้ใส่ยอดรวม
    if ($debitColIndex > 0) {
        // ใส่คำว่า "รวมทั้งสิ้น" ไว้ช่องก่อนหน้า debit
        $labelCol = ($debitColIndex > 1) ? $debitColIndex - 1 : 1;
        $sheet->setCellValueByColumnAndRow($labelCol, $row, 'รวมทั้งสิ้น :');
        
        // ใส่ค่าผลรวม
        $cellDebit = $sheet->getCellByColumnAndRow($debitColIndex, $row);
        $cellDebit->setValue($total_debit);

        // จัดความสวยงาม (ตัวหนา + ใส่ลูกน้ำ + ทศนิยม 2 ตำแหน่ง)
        $sheet->getStyleByColumnAndRow($labelCol, $row)->getFont()->setBold(true); // ตัวหนาคำว่ารวม
        $sheet->getStyleByColumnAndRow($debitColIndex, $row)->getFont()->setBold(true); // ตัวหนายอดเงิน
        
        $sheet->getStyleByColumnAndRow($debitColIndex, $row)
              ->getNumberFormat()
              ->setFormatCode('#,##0.00'); // รูปแบบ 1,234.56
    }
}

// 6.4) ปรับความกว้างอัตโนมัติ
$highestColumn = $sheet->getHighestColumn();
foreach (range('A', $highestColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 7) ดาวน์โหลดไฟล์ Excel
$filename = "Debtor_{$acc}_{$monthe}.xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

if (function_exists('system_log')) {
    system_log($conn, 'ระบบลูกหนี้ (Export)', 'EXPORT', "ผู้ใช้ส่งออกรายงานข้อมูลลูกหนี้รายตัวเป็น Excel");
}

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;