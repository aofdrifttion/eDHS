<?php
// api_get_debtors.php
header('Content-Type: application/json; charset=utf-8');
require_once './database_config/config.php'; 

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50; 
$offset = ($page - 1) * $limit;

$type = $_GET['type'] ?? 'opd';
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$table = ($type === 'ipd') ? 'imr_tb_debtor_rights_ipd' : 'imr_tb_debtor_rights_opd';
$dateField = ($type === 'ipd') ? 'dchdate' : 'vstdate';
$idField = ($type === 'ipd') ? 'an' : 'vn';

$conditions = ["accountcode IN ('1102050102.106', '1102050102.107')"];

// สูตรคำนวณยอดจ่ายรวม: (rcpt_money + follow_money)
// สูตรคำนวณยอดหนี้คงเหลือ: income - (rcpt_money + follow_money)
$calcPaid = "(rcpt_money + IFNULL(follow_money, 0))";
$calcBalance = "(income - $calcPaid)";

// กรองสถานะ
if ($status === 'unpaid') {
    $conditions[] = "$calcBalance > 0.01";
} elseif ($status === 'paid') {
    $conditions[] = "$calcBalance <= 0.01";
}

// กรองคำค้นหา
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $conditions[] = "(hn LIKE '%$search%' OR ptname LIKE '%$search%')";
}

// กรองช่วงวันที่
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

// 1. นับจำนวนรวม
$countSql = "SELECT COUNT(*) as total FROM $table $whereSQL";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// 2. คำนวณยอดสรุป (Dashboard) - ปรับสูตรให้รวม follow_money
$sumSql = "SELECT 
            SUM(income) as total_income,
            SUM($calcPaid) as total_paid,
            SUM(CASE WHEN $calcBalance > 0.01 THEN $calcBalance ELSE 0 END) as total_debt
           FROM $table $whereSQL";
$sumResult = $conn->query($sumSql);
$sumRow = $sumResult->fetch_assoc();

$summary = [
    'total_cases' => $totalRows,
    'total_debt' => $sumRow['total_debt'] ?? 0,
    'total_paid' => $sumRow['total_paid'] ?? 0
];

// 3. ดึงข้อมูล
// เรียงลำดับตามยอดหนี้คงเหลือ (สูตรใหม่)
$orderBy = "ORDER BY $calcBalance > 0.01 DESC, "; 
$orderBy .= "SUBSTR($dateField, 7, 4) DESC, SUBSTR($dateField, 4, 2) DESC, SUBSTR($dateField, 1, 2) DESC";

$sql = "SELECT 
            main.$idField as id, 
            main.hn, 
            main.ptname, 
            main.pttypename, 
            main.$dateField as date_serv, 
            main.income, 
            main.rcpt_money,
            main.follow_money,       -- ดึง follow_money มาด้วย
            track.print_date,
            track.return_doc_file
        FROM $table main
        LEFT JOIN imr_tb_debt_doc_tracking track 
            ON main.$idField = track.vn_an AND track.type = '" . strtoupper($type) . "'
        $whereSQL 
        $orderBy 
        LIMIT $offset, $limit";

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    // แปลงค่าให้เป็นตัวเลข เพื่อป้องกัน Error
    $income = (float)$row['income'];
    $rcpt = (float)$row['rcpt_money'];
    $follow = (float)$row['follow_money']; // ค่าติดตามหนี้
    
    // คำนวณใหม่: จ่ายแล้ว = จ่ายหน้างาน + จ่ายตามหนี้
    $total_paid = $rcpt + $follow;
    $balance = $income - $total_paid;

    // ส่งค่ากลับไปให้ Frontend
    $row['rcpt_money'] = $total_paid; // หลอก Frontend ว่านี่คือยอดจ่ายรวม (จะได้ไม่ต้องแก้ JS)
    $row['balance'] = $balance;
    $row['status_text'] = ($balance > 0.01) ? 'unpaid' : 'paid';
    $row['type'] = strtoupper($type);
    
    if (empty($row['pttypename'])) $row['pttypename'] = '-';
    
    if (!empty($row['return_doc_file'])) {
        $row['doc_url'] = 'uploads/' . $row['return_doc_file'];
    } else {
        $row['doc_url'] = null;
    }
    
    $data[] = $row;
}

echo json_encode([
    'data' => $data,
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'totalRows' => $totalRows,
    'summary' => $summary
]);
?>