<?php
/**
 * quick_search.php
 * API สำหรับ Quick Search (Ctrl+K) — eDHS
 * ค้นหาลูกหนี้จาก VN, AN, HN, ชื่อ, CID, bill, mobile (ใบเสร็จ+วันที่)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require './database_config/config.php';
require_once './database_config/db_helper.php';
mysqli_set_charset($conn, 'utf8mb4');

// ตรวจสอบว่า Login แล้วหรือยัง
if (empty($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit();
}

$keyword = trim($_GET['q'] ?? '');

if (strlen($keyword) < 2) {
    echo json_encode(['success' => true, 'results' => [], 'total' => 0]);
    exit();
}

$kw       = mysqli_real_escape_string($conn, $keyword);
$kw_enc   = mysqli_real_escape_string($conn, encrypt_data($keyword));
$clean_kw = str_replace(['-', ' '], '', $keyword);
$clean_enc = ($clean_kw !== $keyword && strlen($clean_kw) >= 2) ? mysqli_real_escape_string($conn, encrypt_data($clean_kw)) : '';

$cid_where = "cid = '$kw_enc' OR cid LIKE '%$kw%'";
if ($clean_enc !== '') {
    $cid_where .= " OR cid = '$clean_enc' OR cid LIKE '%$clean_kw%'";
}

$results = [];

// ===============================================================
// ฟังก์ชัน parse ค่า mobile ที่มีรูปแบบ "191/055-12/12/2566"
// → คืน ['receipt' => '191/055', 'rcpdate' => '12/12/2566']
// ===============================================================
function parseMobile($mobile) {
    if (empty($mobile) || $mobile === '-' || $mobile === '') {
        return ['receipt' => '', 'rcpdate' => ''];
    }
    // รูปแบบ: <ใบเสร็จ>-<วว/ดด/ปปปป>
    // ตัวอย่าง: 191/055-12/12/2566  หรือ  001/001-01/04/2568
    if (preg_match('/^(.+)-(\d{2}\/\d{2}\/\d{4})$/', $mobile, $m)) {
        return ['receipt' => trim($m[1]), 'rcpdate' => $m[2]];
    }
    // ถ้าไม่ match ก็คืน mobile ทั้งก้อนเป็น receipt
    return ['receipt' => $mobile, 'rcpdate' => ''];
}

// ===============================================================
// ค้นหาจากตาราง OPD (imr_tb_debtor_rights_opd)
// ===============================================================
$sql_opd = "
    SELECT
        'OPD'                       AS patient_type,
        vn                          AS ref_id,
        hn,
        cid,
        ptname,
        accountcode,
        accountname                 AS right_name,
        accountname,
        pttypename,
        vstdate                     AS visit_date,
        vstdate,
        monthtxt                    AS month,
        IFNULL(debit, 0)            AS debit,
        IFNULL(follow_money, '')    AS follow_money,
        bill,
        billdate,
        mobile
    FROM imr_tb_debtor_rights_opd
    WHERE vn      LIKE '%$kw%'
       OR hn      LIKE '%$kw%'
       OR ($cid_where)
       OR ptname  LIKE '%$kw%'
       OR bill    LIKE '%$kw%'
       OR mobile  LIKE '%$kw%'
    ORDER BY STR_TO_DATE(vstdate, '%d/%m/%Y') DESC
    LIMIT 15
";

$res_opd = mysqli_query($conn, $sql_opd);
if ($res_opd) {
    while ($row = mysqli_fetch_assoc($res_opd)) {
        if (isset($row['cid'])) {
            $row['cid'] = decrypt_data($row['cid']);
        }
        $debit_num        = (float) str_replace(',', '', $row['debit']);
        $follow_num       = (float) str_replace(',', '', $row['follow_money']);
        $row['debit_fmt'] = number_format($debit_num, 2);
        $row['paid_fmt']  = number_format($follow_num, 2);

        // parse mobile → แยกใบเสร็จ + วันที่
        $parsed           = parseMobile($row['mobile']);
        $row['mobile_receipt'] = $parsed['receipt'];
        $row['mobile_rcpdate'] = $parsed['rcpdate'];

        $results[]        = $row;
    }
}

// ===============================================================
// ค้นหาจากตาราง IPD (imr_tb_debtor_rights_ipd)
// ===============================================================
$sql_ipd = "
    SELECT
        'IPD'                       AS patient_type,
        an                          AS ref_id,
        hn,
        cid,
        ptname,
        accountcode,
        accountname                 AS right_name,
        accountname,
        pttypename,
        admdate,
        dchdate                     AS visit_date,
        monthtxt                    AS month,
        IFNULL(debit, 0)            AS debit,
        IFNULL(follow_money, '')    AS follow_money,
        bill,
        billdate,
        mobile
    FROM imr_tb_debtor_rights_ipd
    WHERE an      LIKE '%$kw%'
       OR hn      LIKE '%$kw%'
       OR ($cid_where)
       OR ptname  LIKE '%$kw%'
       OR bill    LIKE '%$kw%'
       OR mobile  LIKE '%$kw%'
    ORDER BY STR_TO_DATE(dchdate, '%d/%m/%Y') DESC
    LIMIT 15
";

$res_ipd = mysqli_query($conn, $sql_ipd);
if ($res_ipd) {
    while ($row = mysqli_fetch_assoc($res_ipd)) {
        if (isset($row['cid'])) {
            $row['cid'] = decrypt_data($row['cid']);
        }
        $debit_num        = (float) str_replace(',', '', $row['debit']);
        $follow_num       = (float) str_replace(',', '', $row['follow_money']);
        $row['debit_fmt'] = number_format($debit_num, 2);
        $row['paid_fmt']  = number_format($follow_num, 2);

        // parse mobile → แยกใบเสร็จ + วันที่
        $parsed           = parseMobile($row['mobile']);
        $row['mobile_receipt'] = $parsed['receipt'];
        $row['mobile_rcpdate'] = $parsed['rcpdate'];

        $results[]        = $row;
    }
}

// ===============================================================
// ค้นหาเมนู (tb_menus) — shortcut ไปหน้าต่างๆ
// ===============================================================
$menus     = [];
$user_id   = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';

if ($user_role === 'admin') {
    $sql_m = "SELECT menu_name, menu_link, menu_icon
              FROM tb_menus
              WHERE menu_name LIKE '%$kw%'
              LIMIT 5";
} else {
    $sql_m = "
        SELECT m.menu_name, m.menu_link, m.menu_icon
        FROM tb_menus m
        JOIN tb_user_permissions p ON m.id = p.menu_id
        WHERE p.user_id = '$user_id'
          AND m.menu_name LIKE '%$kw%'
        LIMIT 5
    ";
}
$res_m = mysqli_query($conn, $sql_m);
if ($res_m) {
    while ($row = mysqli_fetch_assoc($res_m)) {
        $menus[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'keyword' => $keyword,
    'total'   => count($results),
    'results' => $results,
    'menus'   => $menus,
], JSON_UNESCAPED_UNICODE);
