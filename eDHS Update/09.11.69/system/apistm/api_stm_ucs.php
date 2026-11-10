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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

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

    if (!in_array($fileExt, ['xls', 'xlsx', 'zip'])) {
        echo json_encode(['status' => 'error', 'message' => 'รองรับเฉพาะไฟล์ Excel (.xls, .xlsx) หรือไฟล์บีบอัด (.zip) เท่านั้น']);
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

    $stmServiceType = trim($_POST['stm_service_type'] ?? 'AUTO');

    // ประมวลผลไฟล์ผ่าน stm_helper
    $result = parse_nhso_stm_workbook($tempFilePath, $fund, $expectedHospcode, $stmServiceType);

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

    // ตรวจสอบการจับคู่กับลูกหนี้ในระบบ (Multi-Strategy Matching & Disambiguation Engine)
    $matchResult = resolve_stm_debtor_matches($conn, $patients, $meta['visit_type'] ?? 'OPD', $result['format_type'] ?? 'UC');
    $debtorInfoSamples = $matchResult['debtor_map'];
    $matchedDebtorCount = $matchResult['matched_count'];
    $totalPatients = count($patients);
    $unmatchedCount = $totalPatients - $matchedDebtorCount;

    // คำนวณสรุปจำแนก 8 หมวดสำหรับ Statement บริการเฉพาะ (CR) แยกตัดหนี้ vs รับรู้รายได้
    $isCrFile = (($result['format_type'] ?? '') === 'INDIVIDUAL_CR');
    if ($isCrFile && !empty($result['display_categories'])) {
        $catStats = [
            'HERB' => ['cr_cases' => 0, 'cr_comp' => 0.0, 'rev_cases' => 0, 'rev_comp' => 0.0, 'total_cases' => 0, 'total_comp' => 0.0],
            'TELEMED' => ['cr_cases' => 0, 'cr_comp' => 0.0, 'rev_cases' => 0, 'rev_comp' => 0.0, 'total_cases' => 0, 'total_comp' => 0.0],
            'WALKIN' => ['cr_cases' => 0, 'cr_comp' => 0.0, 'rev_cases' => 0, 'rev_comp' => 0.0, 'total_cases' => 0, 'total_comp' => 0.0],
            'DRUG_DELIVERY' => ['cr_cases' => 0, 'cr_comp' => 0.0, 'rev_cases' => 0, 'rev_comp' => 0.0, 'total_cases' => 0, 'total_comp' => 0.0],
            'AE' => ['cr_cases' => 0, 'cr_comp' => 0.0, 'rev_cases' => 0, 'rev_comp' => 0.0, 'total_cases' => 0, 'total_comp' => 0.0],
            'INST' => ['cr_cases' => 0, 'cr_comp' => 0.0, 'rev_cases' => 0, 'rev_comp' => 0.0, 'total_cases' => 0, 'total_comp' => 0.0],
            'PP' => ['cr_cases' => 0, 'cr_comp' => 0.0, 'rev_cases' => 0, 'rev_comp' => 0.0, 'total_cases' => 0, 'total_comp' => 0.0],
            'OTHER' => ['cr_cases' => 0, 'cr_comp' => 0.0, 'rev_cases' => 0, 'rev_comp' => 0.0, 'total_cases' => 0, 'total_comp' => 0.0]
        ];

        foreach ($patients as $p) {
            $ck = $p['cat_key'] ?? 'OTHER';
            if (!isset($catStats[$ck])) $ck = 'OTHER';
            $cVal = (float)$p['compensated'];
            $catStats[$ck]['total_cases']++;
            $catStats[$ck]['total_comp'] += $cVal;
            if (($p['paidtype'] ?? '') === 'ALREADY_BILLED_PARENT') {
                $catStats[$ck]['rev_cases']++;
                $catStats[$ck]['rev_comp'] += $cVal;
            } else {
                $catStats[$ck]['cr_cases']++;
                $catStats[$ck]['cr_comp'] += $cVal;
            }
        }

        foreach ($result['display_categories'] as &$dc) {
            $colKey = $dc['col'] ?? '';
            $stKey = $colKey;
            if ($stKey === 'TELE') $stKey = 'TELEMED';
            if ($stKey === 'DELIVERY') $stKey = 'DRUG_DELIVERY';
            if (isset($catStats[$stKey])) {
                $dc['cr_cases'] = $catStats[$stKey]['cr_cases'];
                $dc['cr_comp'] = round($catStats[$stKey]['cr_comp'], 2);
                $dc['revenue_cases'] = $catStats[$stKey]['rev_cases'];
                $dc['revenue_comp'] = round($catStats[$stKey]['rev_comp'], 2);
                $dc['total_cases'] = $catStats[$stKey]['total_cases'];
                $dc['total_comp'] = round($catStats[$stKey]['total_comp'], 2);
            }
        }
        unset($dc);
    }

    // ฟังก์ชันคืนชื่อผังบัญชีเริ่มต้นตามสิทธิ
    $getDefaultAccName = function($f, $vt) {
        if ($vt === 'IPD') {
            switch ($f) {
                case 'UCS': return 'ลูกหนี้ค่ารักษา UC- IP (รอยืนยันผัง)';
                case 'OFC': return 'ลูกหนี้ค่ารักษา จ่ายตรงกรมบัญชีกลาง- IP';
                case 'LGO': return 'ลูกหนี้ค่ารักษา จ่ายตรง อปท.- IP';
                case 'BKK': return 'ลูกหนี้ค่ารักษา เบิกจ่ายตรง กทม.- IP';
                case 'SRT':
                case 'BMT': return 'ลูกหนี้ค่ารักษา รัฐวิสาหกิจ- IP';
                case 'SSS': return 'ลูกหนี้ค่ารักษา ประกันสังคม- IP';
                default: return 'รอยืนยันผังลูกหนี้ IPD';
            }
        } else {
            switch ($f) {
                case 'UCS': return 'ลูกหนี้ค่ารักษา UC- OP (รอยืนยันผัง)';
                case 'OFC': return 'ลูกหนี้ค่ารักษา จ่ายตรงกรมบัญชีกลาง- OP';
                case 'LGO': return 'ลูกหนี้ค่ารักษา จ่ายตรง อปท.- OP';
                case 'BKK': return 'ลูกหนี้ค่ารักษา เบิกจ่ายตรง กทม.- OP';
                case 'SRT':
                case 'BMT': return 'ลูกหนี้ค่ารักษา รัฐวิสาหกิจ- OP';
                case 'SSS': return 'ลูกหนี้ค่ารักษา ประกันสังคม- OP';
                default: return 'รอยืนยันผังลูกหนี้ OPD';
            }
        }
    };

    // เตรียมตัวอย่างคนไข้ (กรณี CR ส่งทั้งหมดเพื่อให้ Filter Tab 3 ทำงานได้ครบทุกเคส, หากเป็น STM ปกติสไลซ์ 100 รายการแรก)
    $samplePatients = [];
    $sampleSlice = $isCrFile ? $patients : array_slice($patients, 0, 100);
    foreach ($sampleSlice as $idx => $sp) {
        $vn = $sp['vn'];
        $dInfo = $debtorInfoSamples[$vn] ?? null;
        if (($sp['paidtype'] ?? '') === 'READY_CR') {
            $debit = (float)$sp['collected'];
            $accountName = 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
        } elseif (($sp['paidtype'] ?? '') === 'ALREADY_BILLED_PARENT') {
            $debit = 0.0;
            $accountName = 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR) (มีใบเสร็จเดิม)';
        } else {
            $debit = $dInfo ? (float)$dInfo['debit'] : (float)$sp['collected'];
            $accountName = $dInfo['accountname'] ?? $getDefaultAccName($fund, $meta['visit_type'] ?? 'OPD');
        }
        $diff = round((float)$sp['compensated'] - $debit, 2);

        $samplePatients[] = [
            'seq' => $idx + 1,
            'rep' => $sp['rep'],
            'vn' => $vn,
            'hn' => $sp['hn'],
            'ptname' => $sp['ptname'],
            'admdate' => $sp['admdate'],
            'accountname' => $accountName,
            'collected' => $sp['collected'],
            'debit' => $debit,
            'compensated' => $sp['compensated'],
            'diff' => $diff,
            'is_matched' => $dInfo ? 1 : 0,
            'paidtype' => $sp['paidtype'] ?? '',
            'is_already_billed' => $sp['is_already_billed'] ?? 0,
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
        'display_categories' => $result['display_categories'] ?? [],
        'format_type' => $result['format_type'] ?? 'NON_UC',
        'total_cases' => $totalPatients,
        'match_result' => [
            'ready_cr_cases' => $matchResult['ready_cr_count'] ?? 0,
            'ready_cr_comp' => $matchResult['ready_cr_comp'] ?? 0.0,
            'zero_cr_cases' => $matchResult['zero_cr_count'] ?? 0,
            'zero_cr_claim' => $matchResult['zero_cr_claim'] ?? 0.0,
            'already_billed_cases' => $matchResult['already_billed_count'] ?? 0,
            'already_billed_comp' => $matchResult['already_billed_comp'] ?? 0.0,
            'unmatched_count' => $unmatchedCount
        ],
        'patients' => $patients
    ];

    echo json_encode([
        'status' => 'success',
        'is_preview' => true,
        'duplicate_info' => $dupCheck,
        'metadata' => $meta,
        'subgroup_totals' => $result['subgroup_totals'],
        'display_categories' => $result['display_categories'] ?? [],
        'format_type' => $result['format_type'] ?? 'NON_UC',
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
            'match_percent' => ($totalPatients > 0) ? round(($matchedDebtorCount / $totalPatients) * 100, 2) : 0,
            'ready_cr_cases' => $matchResult['ready_cr_count'] ?? 0,
            'ready_cr_comp' => $matchResult['ready_cr_comp'] ?? 0.0,
            'zero_cr_cases' => $matchResult['zero_cr_count'] ?? 0,
            'zero_cr_claim' => $matchResult['zero_cr_claim'] ?? 0.0,
            'already_billed_cases' => $matchResult['already_billed_count'] ?? 0,
            'already_billed_comp' => $matchResult['already_billed_comp'] ?? 0.0
        ],
        'sample_patients' => $samplePatients
    ]);
    exit;
}

// ====================================================================================
// ACTION: EXPORT CR SUMMARY (ส่งออกรายงานสรุป Statement บริการเฉพาะ CR เป็น Excel/CSV)
// ====================================================================================
if ($action === 'export_cr_summary') {
    if (empty($_SESSION['stm_temp_session']) || empty($_SESSION['stm_temp_session']['patients'])) {
        header('Content-Type: text/html; charset=utf-8');
        echo "<script>alert('ไม่พบข้อมูล Statement ในเซสชัน กรุณานำเข้าและพรีวิวไฟล์ใหม่อีกครั้ง'); window.close();</script>";
        exit;
    }

    $sessionData = $_SESSION['stm_temp_session'];
    $meta = $sessionData['meta'];
    $displayCategories = $sessionData['display_categories'] ?? [];
    $matchRes = $sessionData['match_result'] ?? [];
    $totalComp = $sessionData['subgroup_totals']['total_comp'] ?? 0.0;
    $totalCases = $sessionData['total_cases'] ?? 0;
    $readyCases = $matchRes['ready_cr_cases'] ?? 0;
    $readyComp = $matchRes['ready_cr_comp'] ?? 0.0;
    $zeroCases = $matchRes['zero_cr_cases'] ?? 0;
    $zeroClaim = $matchRes['zero_cr_claim'] ?? 0.0;
    $readyNonZeroCases = max(0, $readyCases - $zeroCases);
    $billedCases = $matchRes['already_billed_cases'] ?? 0;
    $billedComp = $matchRes['already_billed_comp'] ?? 0.0;

    $docno = $meta['docno'] ?? 'STM_CR';
    $fileName = 'CR_Summary_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $docno) . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Write UTF-8 BOM for Thai Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    // Title & Metadata
    fputcsv($output, ['รายงานสรุปการนำเข้า Statement บริการเฉพาะ (STM CR) - eDebtor Hospital System (eDHS)']);
    fputcsv($output, ['เลขที่เอกสาร / งวด', $meta['docno'] ?? '-', 'หน่วยบริการ', ($meta['hospcode'] ?? '') . ' ' . ($meta['hospname'] ?? '')]);
    fputcsv($output, ['วันที่จัดพิมพ์รายงาน', $meta['report_date'] ?? '-', 'ประเภทการรักษา', $meta['visit_type'] ?? 'OPD']);
    fputcsv($output, []);

    // Summary Section
    fputcsv($output, ['=== สรุปภาพรวมการตัดหนี้และรับรู้รายได้ชดเชย (Financial Settlement Overview) ===']);
    fputcsv($output, ['รายการ', 'จำนวนราย', 'ยอดเงินชดเชย (บาท)', 'การจัดการทางบัญชี / ผังลูกหนี้ eDHS']);
    fputcsv($output, ['1. พร้อมตัดหนี้ CR (ยอดชดเชยปกติ)', $readyNonZeroCases, number_format($readyComp, 2, '.', ''), 'ตัดหนี้ผังลูกหนี้ CR (1102050101.216) แนบใบเสร็จตาม Statement']);
    fputcsv($output, ['2. ชดเชย 0.00 บาท (ตัดหนี้ผัง CR)', $zeroCases, '0.00', 'ตัดหนี้ผังลูกหนี้ CR (1102050101.216) แนบใบเสร็จรับรู้ชดเชย 0.00 บาท']);
    fputcsv($output, ['3. รับรู้เป็นรายได้ชดเชย (มีใบเสร็จเดิม)', $billedCases, number_format($billedComp, 2, '.', ''), 'รับรู้เป็นรายได้ (ไม่ตัดหนี้ซ้ำ) เนื่องจากมีใบเสร็จตัดหนี้ในผังหลักแล้ว']);
    fputcsv($output, ['4. รวมทั้งฉบับ', $totalCases, number_format($totalComp, 2, '.', ''), 'ยอดเงินชดเชยสุทธิที่ได้รับจริงจาก สปสช. (ครบถ้วน 100%)']);
    fputcsv($output, []);

    // Category Breakdown Section
    fputcsv($output, ['=== จำแนกตาม 8 หมวดเงินชดเชยบริการเฉพาะ (สปสช.) ===']);
    fputcsv($output, [
        'ลำดับ',
        'หมวดการชดเชยค่าบริการทางการแพทย์',
        'พร้อมตัดหนี้ CR (จำนวนราย)',
        'ยอดตัดหนี้ CR (บาท)',
        'มีใบเสร็จเดิม (จำนวนราย)',
        'ยอดรับรู้รายได้ (บาท)',
        'รวมเงินชดเชย (บาท)',
        'สัดส่วน (%)'
    ]);

    $seq = 1;
    foreach ($displayCategories as $dc) {
        $name = $dc['name'] ?? '';
        $cCases = $dc['cr_cases'] ?? 0;
        $cComp = $dc['cr_comp'] ?? 0.0;
        $rCases = $dc['revenue_cases'] ?? 0;
        $rComp = $dc['revenue_comp'] ?? 0.0;
        $tComp = $dc['total_comp'] ?? $dc['val'] ?? 0.0;
        $pct = ($totalComp > 0) ? round(($tComp / $totalComp) * 100, 2) : 0;

        fputcsv($output, [
            $seq++,
            $name,
            $cCases,
            number_format($cComp, 2, '.', ''),
            $rCases,
            number_format($rComp, 2, '.', ''),
            number_format($tComp, 2, '.', ''),
            $pct . '%'
        ]);
    }

    fputcsv($output, [
        'รวมทั้งสิ้น',
        '8 หมวดบริการเฉพาะ',
        $readyCases,
        number_format($readyComp, 2, '.', ''),
        $billedCases,
        number_format($billedComp, 2, '.', ''),
        number_format($totalComp, 2, '.', ''),
        '100.00%'
    ]);

    fclose($output);
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
