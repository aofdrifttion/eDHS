<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - STM UC API Endpoint
 * ====================================================================================
 * REST/AJAX API Endpoint สำหรับนำเข้า Statement สปสช. (STM UC)
 * จัดการ 3 Actions:
 *   1. preview_stm        -> ตรวจสอบความถูกต้อง, อ่านข้อมูลเข้า Memory, ตรวจจับความซ้ำซ้อน และคืนค่าพรีวิว
 *   2. confirm_import_stm -> บันทึกข้อมูลจริงเข้าสู่ imr_tb_check_invoice (พร้อม Overwrite Safe Rollback)
 *   3. delete_stm_doc     -> ลบข้อมูล Statement ตามเลขที่เอกสาร (docno) ทั้งฉบับ หรือตามเลข REP
 * ====================================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../database_config/config.php';
require_once __DIR__ . '/../database_config/db_helper.php';
require_once __DIR__ . '/stm_helper.php';

// รับประกันโครงสร้างตาราง imr_tb_check_invoice ให้พร้อมใช้งานเสมอ (Auto-Migration)
ensure_stm_columns($conn);

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนทำรายการ']);
    exit;
}

$action = $_POST['action'] ?? '';

// ====================================================================================
// ACTION 1: PREVIEW STM (ตรวจสอบความถูกต้องและสร้างข้อมูลพรีวิว)
// ====================================================================================
if ($action === 'preview_stm') {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== 0) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาเลือกไฟล์ Excel เพื่อตรวจสอบ']);
        exit;
    }

    $fund = $_POST['fund'] ?? 'UCS';
    if (empty($fund) || $fund === '00') {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาเลือกลูกหนี้สิทธิก่อนดำเนินการ']);
        exit;
    }

    $fileTmp = $_FILES['excel_file']['tmp_name'];
    $fileName = $_FILES['excel_file']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, ['xls', 'xlsx'])) {
        echo json_encode(['status' => 'error', 'message' => 'รองรับเฉพาะไฟล์ Excel นามสกุล .xls หรือ .xlsx เท่านั้น']);
        exit;
    }

    $expectedHospcode = $configData['hospcode'] ?? '';

    // บันทึกไฟล์ชั่วคราวลงในโฟลเดอร์ temp เพื่อรอการกดยืนยันนำเข้า
    $tempDir = __DIR__ . '/temp';
    if (!file_exists($tempDir)) {
        @mkdir($tempDir, 0777, true);
    } else {
        // ทำความสะอาดไฟล์ชั่วคราวที่ตกค้างเกิน 24 ชั่วโมง
        foreach (glob($tempDir . '/stm_*') as $oldFile) {
            if (is_file($oldFile) && (time() - filemtime($oldFile) > 86400)) {
                @unlink($oldFile);
            }
        }
    }
    $tempFileName = 'stm_' . session_id() . '_' . time() . '.' . $fileExt;
    $tempFilePath = $tempDir . '/' . $tempFileName;

    $moved = is_uploaded_file($fileTmp) ? @move_uploaded_file($fileTmp, $tempFilePath) : @copy($fileTmp, $tempFilePath);
    if (!$moved) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกไฟล์ชั่วคราวบนเซิร์ฟเวอร์ได้']);
        exit;
    }

    // ประมวลผลไฟล์ผ่าน stm_helper
    $result = parse_nhso_stm_workbook($tempFilePath, $fund, $expectedHospcode);

    if ($result['status'] !== 'success') {
        @unlink($tempFilePath);
        echo json_encode($result);
        exit;
    }

    $meta = $result['metadata'];
    $patients = $result['patients'];
    $reps = array_unique(array_column($patients, 'rep'));

    // ตรวจสอบความซ้ำซ้อนในฐานข้อมูล
    $dupCheck = check_stm_duplicate($conn, $meta['docno'], $reps);

    // ตรวจสอบการจับคู่กับลูกหนี้ในระบบ (Matching Rate)
    $vns = array_filter(array_unique(array_column($patients, 'vn')));
    $matchedDebtorCount = 0;
    $debtorInfoSamples = [];

    if (!empty($vns)) {
        $vn_chunks = array_chunk($vns, 1000);
        foreach ($vn_chunks as $chunk) {
            $escaped = "'" . implode("','", array_map(fn($v) => mysqli_real_escape_string($conn, $v), $chunk)) . "'";
            if ($meta['visit_type'] === 'IPD') {
                $q = $conn->query("SELECT an as vn, debit, accountcode, accountname, pttypename, dchdate as sdate FROM imr_tb_debtor_rights_ipd WHERE an IN ($escaped)");
            } else {
                $q = $conn->query("SELECT vn, debit, accountcode, accountname, pttypename, vstdate as sdate FROM imr_tb_debtor_rights_opd WHERE vn IN ($escaped)");
            }
            if ($q) {
                while ($r = $q->fetch_assoc()) {
                    $debtorInfoSamples[$r['vn']] = $r;
                    $matchedDebtorCount++;
                }
            }
        }
    }

    $totalPatients = count($patients);
    $unmatchedCount = $totalPatients - $matchedDebtorCount;

    // เตรียมตัวอย่างคนไข้ 100 รายแรกสำหรับแสดงในแท็บตัวอย่าง
    $samplePatients = [];
    $sampleSlice = array_slice($patients, 0, 100);
    foreach ($sampleSlice as $idx => $sp) {
        $vn = $sp['vn'];
        $dInfo = $debtorInfoSamples[$vn] ?? null;
        $debit = $dInfo ? (float)$dInfo['debit'] : (float)$sp['collected'];
        $diff = round((float)$sp['compensated'] - $debit, 2);

        $samplePatients[] = [
            'seq' => $idx + 1,
            'rep' => $sp['rep'],
            'vn' => $vn,
            'hn' => $sp['hn'],
            'ptname' => $sp['ptname'],
            'admdate' => $sp['admdate'],
            'accountname' => $dInfo['accountname'] ?? ($fund === 'UCS' ? (($meta['visit_type'] ?? 'OPD') === 'IPD' ? 'ลูกหนี้ค่ารักษา UC- IP (รอยืนยันผัง)' : 'ลูกหนี้ค่ารักษา UC- OP (รอยืนยันผัง)') : 'รอยืนยันผังลูกหนี้'),
            'collected' => $sp['collected'],
            'debit' => $debit,
            'compensated' => $sp['compensated'],
            'diff' => $diff,
            'is_matched' => $dInfo ? 1 : 0,
            'is_appeal' => $sp['is_appeal']
        ];
    }

    // เก็บ Session Cache เพื่อให้กดยืนยันนำเข้าได้ทันทีโดยไม่ต้องส่งไฟล์มาใหม่
    $_SESSION['stm_temp_session'] = [
        'temp_file' => $tempFilePath,
        'docno' => $meta['docno'],
        'fund' => $fund,
        'meta' => $meta,
        'subgroup_totals' => $result['subgroup_totals'],
        'total_cases' => $totalPatients,
        'patients' => $patients
    ];

    echo json_encode([
        'status' => 'success',
        'is_preview' => true,
        'duplicate_info' => $dupCheck,
        'metadata' => $meta,
        'subgroup_totals' => $result['subgroup_totals'],
        'rep_summaries' => $result['rep_summaries'],
        'summary_kpi' => [
            'total_cases' => $totalPatients,
            'normal_cases' => $result['normal_cases'],
            'appeal_cases' => $result['appeal_cases'],
            'total_comp' => $result['subgroup_totals']['total_comp'],
            'total_collected' => $result['subgroup_totals']['total_collected'],
            'rep_count' => count($result['rep_summaries']),
            'matched_debtor_count' => $matchedDebtorCount,
            'unmatched_count' => $unmatchedCount,
            'match_percent' => ($totalPatients > 0) ? round(($matchedDebtorCount / $totalPatients) * 100, 2) : 0
        ],
        'sample_patients' => $samplePatients
    ]);
    exit;
}

// ====================================================================================
// ACTION 2: CONFIRM IMPORT STM (ยืนยันการนำเข้าข้อมูลจริงเข้าสู่ระบบ)
// ====================================================================================
if ($action === 'confirm_import_stm') {
    $confirmOverwrite = !empty($_POST['confirm_overwrite']) && $_POST['confirm_overwrite'] == '1';

    if (empty($_SESSION['stm_temp_session']) || empty($_SESSION['stm_temp_session']['patients'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบข้อมูล Statement ในเซสชัน กรุณาเลือกไฟล์และตรวจสอบใหม่อีกครั้ง'
        ]);
        exit;
    }

    $sessionData = $_SESSION['stm_temp_session'];
    $patients = $sessionData['patients'];
    $meta = $sessionData['meta'];
    $docno = $meta['docno'];
    $reps = array_unique(array_column($patients, 'rep'));

    // ตรวจสอบซ้ำอีกครั้งหากไม่ได้กดยืนยัน Overwrite
    if (!$confirmOverwrite) {
        $dupCheck = check_stm_duplicate($conn, $docno, $reps);
        if ($dupCheck['is_duplicate']) {
            echo json_encode([
                'status' => 'duplicate_warning',
                'message' => 'พบข้อมูล Statement นี้อยู่ในระบบแล้ว หากต้องการแทนที่ข้อมูลชุดเดิม กรุณากดยืนยันเขียนทับ',
                'duplicate_info' => $dupCheck
            ]);
            exit;
        }
    }

    // ทำการ Auto-Enrichment และ Batch Insert
    $insertResult = enrich_and_insert_stm_patients($conn, $patients, $meta, $confirmOverwrite);

    if ($insertResult['status'] === 'success') {
        // ลบไฟล์ชั่วคราว
        if (!empty($sessionData['temp_file']) && file_exists($sessionData['temp_file'])) {
            @unlink($sessionData['temp_file']);
        }
        unset($_SESSION['stm_temp_session']);

        // ส่งแจ้งเตือน MorPromt / LINE Group
        send_stm_notification($meta, $insertResult);
    }

    echo json_encode($insertResult);
    exit;
}

// ====================================================================================
// ACTION 3: DELETE STM DOC (ลบข้อมูล Statement ตามเลขที่เอกสาร หรือตามเลข REP)
// ====================================================================================
if ($action === 'delete_stm_doc') {
    $targetId = trim($_POST['target_id'] ?? '');
    $deleteType = trim($_POST['delete_type'] ?? 'docno'); // 'docno' หรือ 'rep'
    $password = trim($_POST['password'] ?? '');
    $userId = $_SESSION['user_id'];

    if (empty($targetId) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลและรหัสผ่านให้ครบถ้วน']);
        exit;
    }

    // ตรวจสอบรหัสผ่านผู้ใช้
    $stmtUser = $conn->prepare("SELECT password, fullname FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $userData = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    if (!$userData || !password_verify($password, $userData['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านไม่ถูกต้อง ไม่สามารถดำเนินการลบได้']);
        exit;
    }

    $conn->begin_transaction();

    try {
        if ($deleteType === 'docno') {
            // ดึงรายการ VN ทั้งหมดภายใต้ docno นี้เพื่อรีเซ็ต mobile='-'
            $stmtSel = $conn->prepare("SELECT vn FROM imr_tb_check_invoice WHERE docno = ?");
            $stmtSel->bind_param("s", $targetId);
            $stmtSel->execute();
            $resSel = $stmtSel->get_result();

            $updOpd = $conn->prepare("UPDATE imr_tb_debtor_rights_opd SET mobile='-' WHERE vn = ?");
            $updIpd = $conn->prepare("UPDATE imr_tb_debtor_rights_ipd SET mobile='-' WHERE an = ?");

            while ($r = $resSel->fetch_assoc()) {
                if (!empty($r['vn'])) {
                    $updOpd->bind_param("s", $r['vn']);
                    $updOpd->execute();
                    $updIpd->bind_param("s", $r['vn']);
                    $updIpd->execute();
                }
            }
            $stmtSel->close();
            $updOpd->close();
            $updIpd->close();

            // ลบจาก imr_tb_check_invoice
            $stmtDel = $conn->prepare("DELETE FROM imr_tb_check_invoice WHERE docno = ?");
            $stmtDel->bind_param("s", $targetId);
            $stmtDel->execute();
            $deletedRows = $stmtDel->affected_rows;
            $stmtDel->close();

            $logDesc = "ลบข้อมูล Statement ทั้งฉบับ (เลขที่เอกสาร: $targetId, รวม $deletedRows รายการ)";
        } else {
            // ลบตาม REP เดี่ยว
            $stmtSel = $conn->prepare("SELECT vn FROM imr_tb_check_invoice WHERE rep = ?");
            $stmtSel->bind_param("s", $targetId);
            $stmtSel->execute();
            $resSel = $stmtSel->get_result();

            $updOpd = $conn->prepare("UPDATE imr_tb_debtor_rights_opd SET mobile='-' WHERE vn = ?");
            $updIpd = $conn->prepare("UPDATE imr_tb_debtor_rights_ipd SET mobile='-' WHERE an = ?");

            while ($r = $resSel->fetch_assoc()) {
                if (!empty($r['vn'])) {
                    $updOpd->bind_param("s", $r['vn']);
                    $updOpd->execute();
                    $updIpd->bind_param("s", $r['vn']);
                    $updIpd->execute();
                }
            }
            $stmtSel->close();
            $updOpd->close();
            $updIpd->close();

            $stmtDel = $conn->prepare("DELETE FROM imr_tb_check_invoice WHERE rep = ?");
            $stmtDel->bind_param("s", $targetId);
            $stmtDel->execute();
            $deletedRows = $stmtDel->affected_rows;
            $stmtDel->close();

            $logDesc = "ลบข้อมูล Statement ตามเลขหนังสือ (REP: $targetId, รวม $deletedRows รายการ)";
        }

        $conn->commit();

        if (function_exists('system_log')) {
            system_log($conn, 'นำเข้า STM & ตัดลูกหนี้ตาม STM', 'DELETE', $logDesc);
        }

        echo json_encode([
            'status' => 'success',
            'message' => "ลบข้อมูลสำเร็จเรียบร้อยแล้ว ($deletedRows รายการ)",
            'deleted_rows' => $deletedRows
        ]);
        exit;

    } catch (\Throwable $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
        ]);
        exit;
    }
}

// ถ้า action ไม่ตรงกับเงื่อนไขใด
echo json_encode(['status' => 'error', 'message' => 'Action ที่ร้องขอไม่ถูกต้อง']);
exit;

/**
 * ฟังก์ชันส่งแจ้งเตือน MorPromt / Line Group อัตโนมัติ
 */
function send_stm_notification($meta, $result) {
    global $configData;

    $clientId = $configData['Client_ID'] ?? '';
    $secret = $configData['Secret'] ?? '';
    if (empty($clientId) || empty($secret)) return;

    $docno = $meta['docno'] ?? '-';
    $fund = $meta['fund'] ?? '-';
    $totalComp = number_format($result['total_compensated'] ?? 0, 2);
    $inserted = number_format($result['inserted_count'] ?? 0);
    $user = $_SESSION['fullname'] ?? 'User';

    $day = date('d');
    $thaiMonths = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $month = $thaiMonths[(int)date('m')];
    $year = date('Y') + 543;
    $time = date('H:i');
    $dateTime = "$day $month $year เวลา $time น.";

    $summaryText = "นำเข้า Statement ($fund)\n"
                 . "📄 เลขที่เอกสาร: $docno\n"
                 . "👥 บันทึกสำเร็จ: $inserted รายการ\n"
                 . "💰 เงินชดเชยรวม: ฿$totalComp บาท";

    $flexMessage = [
        "type" => "flex",
        "altText" => "แจ้งเตือน: eDHS นำเข้า STM ($fund)",
        "contents" => [
            "type" => "bubble",
            "size" => "giga",
            "body" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "sm",
                "contents" => [
                    [
                        "type" => "text",
                        "text" => "eDebtor Hospital System: eDHS",
                        "weight" => "bold",
                        "size" => "md",
                        "align" => "center"
                    ],
                    ["type" => "separator", "margin" => "md"],
                    ["type" => "text", "text" => "📥 รายละเอียด : \n" . $summaryText, "size" => "sm", "wrap" => true],
                    ["type" => "text", "text" => "ผู้ดำเนินการ : " . $user, "size" => "sm"],
                    ["type" => "text", "text" => "วันที่ : " . $dateTime, "size" => "xs", "align" => "center", "color" => "#666666"]
                ]
            ]
        ]
    ];

    $payload = [
        "cid" => ["1234567891234"],
        "messages" => [$flexMessage],
        "message_title" => "นำเข้า Statement",
        "message_html" => " ",
        "message_text" => " ",
        "message_type" => "HPT"
    ];

    $url = "https://morpromt2f.moph.go.th/api/notify/send";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "client-key: $clientId",
            "secret-key: $secret"
        ]
    ]);
    @curl_exec($ch);
    @curl_close($ch);
}
