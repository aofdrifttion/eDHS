<?php
// export_excel_debtors.php
require './database_config/config.php';

$type = $_GET['type'] ?? 'opd';
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$startDate = $_GET['start_date'] ?? ''; 
$endDate = $_GET['end_date'] ?? '';

$filename = "debt_report_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

if (function_exists('system_log')) {
    system_log($conn, 'ระบบลูกหนี้ (Export)', 'EXPORT', "ผู้ใช้ส่งออกข้อมูลรายชื่อคนไข้ (สิทธิ/ประเภท: $type) เป็น Excel");
}

$table = ($type === 'ipd') ? 'imr_tb_debtor_rights_ipd' : 'imr_tb_debtor_rights_opd';
$dateField = ($type === 'ipd') ? 'dchdate' : 'vstdate';

$calcPaid = "(rcpt_money + IFNULL(follow_money, 0))";
$calcBalance = "(income - $calcPaid)";

$conditions = ["accountcode IN ('1102050102.106', '1102050102.107')"];
if ($status === 'unpaid') $conditions[] = "$calcBalance > 0.01";
elseif ($status === 'paid') $conditions[] = "$calcBalance <= 0.01";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $conditions[] = "(hn LIKE '%$search%' OR ptname LIKE '%$search%')";
}

if (!empty($startDate) && !empty($endDate)) {
    $startParts = explode('-', $startDate);
    $startThaiYear = (int)$startParts[0] + 543;
    $startComp = $startThaiYear . $startParts[1] . $startParts[2];

    $endParts = explode('-', $endDate);
    $endThaiYear = (int)$endParts[0] + 543;
    $endComp = $endThaiYear . $endParts[1] . $endParts[2];
    
    $conditions[] = "CONCAT(SUBSTR($dateField, 7, 4), SUBSTR($dateField, 4, 2), SUBSTR($dateField, 1, 2)) BETWEEN '$startComp' AND '$endComp'";
}

$whereSQL = "WHERE " . implode(' AND ', $conditions);
$orderBy = "ORDER BY $calcBalance > 0.01 DESC, SUBSTR($dateField, 7, 4) DESC";

// ดึง follow_money มาด้วย
$sql = "SELECT hn, ptname, pttypename, $dateField as date_serv, income, rcpt_money, follow_money FROM $table $whereSQL $orderBy";
$result = $conn->query($sql);

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';
echo '<table border="1">';
echo '<tr><th>ลำดับ</th><th>ประเภท</th><th>วันที่</th><th>HN</th><th>ชื่อ-สกุล</th><th>สิทธิ</th><th>ยอดหนี้</th><th>ชำระแล้ว</th><th>คงค้าง</th><th>สถานะ</th></tr>';

$i = 1;
while($row = $result->fetch_assoc()) {
    // คำนวณยอดเงิน
    $income = (float)$row['income'];
    $paid = (float)$row['rcpt_money'] + (float)$row['follow_money']; // รวมยอดจ่าย
    $balance = $income - $paid;
    
    $statusText = ($balance > 0.01) ? 'ค้างชำระ' : 'ชำระครบ';
    
    echo "<tr>
            <td>{$i}</td>
            <td>".strtoupper($type)."</td>
            <td>'{$row['date_serv']}</td> 
            <td>{$row['hn']}</td>
            <td>{$row['ptname']}</td>
            <td>{$row['pttypename']}</td>
            <td>{$income}</td>
            <td>{$paid}</td>
            <td>{$balance}</td>
            <td>{$statusText}</td>
          </tr>";
    $i++;
}
echo '</table></body></html>';
?>