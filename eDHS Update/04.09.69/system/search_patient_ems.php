<?php
require './database_config/config.php';
require_once './database_config/db_helper.php';

header('Content-Type: application/json; charset=utf-8');

$term = isset($_POST['term']) ? trim($_POST['term']) : '';

if ($term === '') {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];
$seen_hn = [];
$term_like = '%' . $term . '%';

// 1. ค้นหาจากฐานข้อมูล HOSxP (ตาราง patient) เป็นหลัก (ข้อมูลผู้ป่วยหลักของโรงพยาบาล HN ไม่ซ้ำแน่นอน)
if (isset($conn2) && !$conn2->connect_error) {
    $stmt = $conn2->prepare("
        SELECT hn, cid, 
               TRIM(CONCAT(COALESCE(pname, ''), COALESCE(fname, ''), ' ', COALESCE(lname, ''))) AS ptname,
               CASE WHEN sex = '1' THEN 'ชาย' WHEN sex = '2' THEN 'หญิง' ELSE '' END AS sex,
               TIMESTAMPDIFF(YEAR, birthday, CURDATE()) AS age
        FROM patient
        WHERE hn LIKE ? 
           OR CONCAT(COALESCE(pname, ''), COALESCE(fname, ''), ' ', COALESCE(lname, '')) LIKE ?
           OR fname LIKE ?
           OR lname LIKE ?
           OR cid LIKE ?
        ORDER BY 
           CASE 
               WHEN hn = ? THEN 1
               WHEN hn LIKE CONCAT('%', ?) THEN 2
               WHEN fname LIKE CONCAT(?, '%') THEN 3
               WHEN cid LIKE CONCAT(?, '%') THEN 4
               ELSE 5
           END,
           hn DESC
        LIMIT 15
    ");
    if ($stmt) {
        $stmt->bind_param("sssssssss", 
            $term_like, $term_like, $term_like, $term_like, $term_like,
            $term, $term, $term, $term
        );
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $hn = trim($row['hn']);
            if (!empty($hn) && !isset($seen_hn[$hn])) {
                $seen_hn[$hn] = true;
                $age_str = ($row['age'] !== null && $row['age'] !== '') ? ($row['age'] . ' ปี') : '';
                $data[] = [
                    'hn' => $hn,
                    'cid' => trim($row['cid'] ?? ''),
                    'ptname' => trim($row['ptname'] ?? ''),
                    'sex' => $row['sex'] ?? '',
                    'age' => $age_str
                ];
            }
        }
        $stmt->close();
    }
}

// 2. ถ้าไม่พบข้อมูลจาก HOSxP หรือไม่ได้เชื่อมต่อ HOSxP ให้ดึงจากตารางลูกหนี้ eDHS (imr_tb_debtor_rights_opd)
// โดยใช้ GROUP BY hn เพื่อการันตีไม่ให้มีรายการ HN ซ้ำกัน 100%
if (count($data) < 15) {
    $remaining_limit = 15 - count($data);
    $encrypted_term = encrypt_data($term);

    $stmt2 = $conn->prepare("
        SELECT hn, 
               MAX(cid) AS cid, 
               MAX(ptname) AS ptname, 
               MAX(sex) AS sex, 
               MAX(age) AS age
        FROM imr_tb_debtor_rights_opd
        WHERE ptname LIKE CONCAT('%', ?, '%') 
           OR hn LIKE CONCAT('%', ?, '%') 
           OR cid = ? 
        GROUP BY hn
        ORDER BY MAX(no) DESC
        LIMIT ?
    ");
    if ($stmt2) {
        $stmt2->bind_param("sssi", $term, $term, $encrypted_term, $remaining_limit);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while ($row = $result2->fetch_assoc()) {
            $hn = trim($row['hn']);
            if (!empty($hn) && !isset($seen_hn[$hn])) {
                $seen_hn[$hn] = true;
                $row['cid'] = decrypt_data($row['cid']);
                $age_val = trim($row['age'] ?? '');
                if ($age_val !== '' && !preg_match('/ปี/u', $age_val)) {
                    $age_val .= ' ปี';
                }
                $data[] = [
                    'hn' => $hn,
                    'cid' => $row['cid'] ?? '',
                    'ptname' => trim($row['ptname'] ?? ''),
                    'sex' => trim($row['sex'] ?? ''),
                    'age' => $age_val
                ];
            }
        }
        $stmt2->close();
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>