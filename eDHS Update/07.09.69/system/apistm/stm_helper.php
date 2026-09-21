<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - STM Ingestion Helper
 * ====================================================================================
 * โมดูลช่วยเหลือสำหรับอ่านและตรวจสอบไฟล์ Statement สปสช. (NHSO Official STM Workbook)
 * รองรับ Multi-Sheet Parsing, 4-Layer Validation, Debtor Auto-Enrichment, และ Batch Insert
 * ====================================================================================
 */

if (!defined('EDHS_STM_HELPER')) {
    define('EDHS_STM_HELPER', true);
}

require_once __DIR__ . '/../database_config/config.php';
require_once __DIR__ . '/../database_config/db_helper.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!function_exists('ensure_stm_columns')) {
    /**
     * ตรวจสอบและสร้างโครงสร้างคอลัมน์/ดัชนีของตาราง imr_tb_check_invoice ให้อัตโนมัติ (Zero-Configuration Auto-Migration)
     * เพื่อให้ระบบพร้อมใช้งานทันทีเมื่อนำไปติดตั้งหรืออัปเดตที่โรงพยาบาลอื่นๆ โดยไม่ต้องรันคำสั่ง SQL เอง
     *
     * @param mysqli $conn การเชื่อมต่อฐานข้อมูล MySQLi
     * @param string $tableName ชื่อตารางที่ต้องการตรวจสอบ (ค่าเริ่มต้น: 'imr_tb_check_invoice')
     * @return bool คืนค่า true หากโครงสร้างพร้อมใช้งาน
     */
    function ensure_stm_columns($conn, $tableName = 'imr_tb_check_invoice') {
        static $ensured = [];
        if (isset($ensured[$tableName])) {
            return true;
        }
        if (!$conn) {
            return false;
        }

        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);

        // 1. ตรวจสอบว่ามีตารางหรือไม่ หากไม่มีให้สร้างใหม่ทั้งตาราง
        $chk_table = mysqli_query($conn, "SHOW TABLES LIKE '$safeTable'");
        if (!$chk_table || mysqli_num_rows($chk_table) === 0) {
            $sql_create = "
                CREATE TABLE IF NOT EXISTS `$safeTable` (
                    `no` text DEFAULT NULL,
                    `yearbudget` text DEFAULT NULL,
                    `rep` text DEFAULT NULL,
                    `docno` varchar(100) DEFAULT NULL,
                    `id` text DEFAULT NULL,
                    `vn` text DEFAULT NULL,
                    `pid` text DEFAULT NULL,
                    `hn` text DEFAULT NULL,
                    `ptname` text DEFAULT NULL,
                    `admdate` text DEFAULT NULL,
                    `dchdate` text DEFAULT NULL,
                    `pttypename` text DEFAULT NULL,
                    `pttype_eclaim_name` text DEFAULT NULL,
                    `accountcode` text DEFAULT NULL,
                    `accountname` text DEFAULT NULL,
                    `subfund` text DEFAULT NULL,
                    `errorcode` text DEFAULT NULL,
                    `flag` text DEFAULT NULL,
                    `collected` text DEFAULT NULL,
                    `percentpay` text DEFAULT NULL,
                    `compensated` text DEFAULT NULL,
                    `paidtype` text DEFAULT NULL,
                    `bill` text DEFAULT NULL,
                    `billdate` text DEFAULT NULL,
                    `debit` text DEFAULT NULL,
                    `diff` text DEFAULT NULL,
                    `down` text DEFAULT NULL,
                    `up` text DEFAULT NULL,
                    `total` text DEFAULT NULL,
                    `fund` text DEFAULT NULL,
                    `hc` text DEFAULT NULL,
                    `ae` text DEFAULT NULL,
                    `inst` text DEFAULT NULL,
                    `ip` text DEFAULT NULL,
                    `dmis` text DEFAULT NULL,
                    `op` text DEFAULT NULL,
                    `prior` text DEFAULT NULL,
                    `drug` text DEFAULT NULL,
                    `ontop` text DEFAULT NULL,
                    `pallativecare` text DEFAULT NULL,
                    `dmishd` text DEFAULT NULL,
                    `pp` decimal(12,2) NOT NULL DEFAULT 0.00,
                    `fs` decimal(12,2) NOT NULL DEFAULT 0.00,
                    `opbkk` decimal(12,2) NOT NULL DEFAULT 0.00,
                    `fpnhso` text DEFAULT NULL,
                    `imr_tb_check_invoice` text DEFAULT NULL,
                    KEY `idx_vn` (`vn`(191)),
                    KEY `idx_docno` (`docno`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            @mysqli_query($conn, $sql_create);
            $ensured[$tableName] = true;
            return true;
        }

        // 2. ตรวจสอบและเพิ่มคอลัมน์ docno
        $chk_docno = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE 'docno'");
        if ($chk_docno && mysqli_num_rows($chk_docno) === 0) {
            @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `docno` VARCHAR(100) NULL AFTER `rep`");
            $chk_docno_again = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE 'docno'");
            if ($chk_docno_again && mysqli_num_rows($chk_docno_again) === 0) {
                @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `docno` VARCHAR(100) NULL");
            }
        }

        // 3. ตรวจสอบและเพิ่มดัชนี idx_docno
        $chk_idx_docno = mysqli_query($conn, "SHOW INDEX FROM `$safeTable` WHERE Key_name = 'idx_docno'");
        if ($chk_idx_docno && mysqli_num_rows($chk_idx_docno) === 0) {
            @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD INDEX `idx_docno` (`docno`)");
        }

        // 4. ตรวจสอบและเพิ่มคอลัมน์ pp
        $chk_pp = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE 'pp'");
        if ($chk_pp && mysqli_num_rows($chk_pp) === 0) {
            @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `pp` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `dmishd`");
            $chk_pp_again = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE 'pp'");
            if ($chk_pp_again && mysqli_num_rows($chk_pp_again) === 0) {
                @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `pp` DECIMAL(12,2) NOT NULL DEFAULT 0.00");
            }
        }

        // 5. ตรวจสอบและเพิ่มคอลัมน์ fs
        $chk_fs = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE 'fs'");
        if ($chk_fs && mysqli_num_rows($chk_fs) === 0) {
            @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `fs` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `pp`");
            $chk_fs_again = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE 'fs'");
            if ($chk_fs_again && mysqli_num_rows($chk_fs_again) === 0) {
                @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `fs` DECIMAL(12,2) NOT NULL DEFAULT 0.00");
            }
        }

        // 6. ตรวจสอบและเพิ่มคอลัมน์ opbkk
        $chk_opbkk = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE 'opbkk'");
        if ($chk_opbkk && mysqli_num_rows($chk_opbkk) === 0) {
            @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `opbkk` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `fs`");
            $chk_opbkk_again = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE 'opbkk'");
            if ($chk_opbkk_again && mysqli_num_rows($chk_opbkk_again) === 0) {
                @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `opbkk` DECIMAL(12,2) NOT NULL DEFAULT 0.00");
            }
        }

        // 7. ตรวจสอบและเพิ่มดัชนี idx_vn
        $chk_idx_vn = mysqli_query($conn, "SHOW INDEX FROM `$safeTable` WHERE Key_name = 'idx_vn'");
        if ($chk_idx_vn && mysqli_num_rows($chk_idx_vn) === 0) {
            @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD INDEX `idx_vn` (`vn`(191))");
        }

        $ensured[$tableName] = true;
        return true;
    }
}


/**
 * ตรวจสอบความถูกต้องและอ่านโครงสร้างไฟล์ Statement สปสช.
 * 
 * @param string $filePath เส้นทางไฟล์ Excel ชั่วคราว
 * @param string $expectedFund สิทธิที่ผู้ใช้เลือกในระบบ (เช่น 'UCS', 'OFC', 'SSS', 'LGO')
 * @param string $expectedHospcode รหัสหน่วยบริการของโรงพยาบาล
 * @return array ข้อมูล metadata, สรุปรายหมวด, สรุปราย REP, และรายชื่อผู้ป่วยทั้งหมด
 */
function parse_nhso_stm_workbook($filePath, $expectedFund = 'UCS', $expectedHospcode = '') {
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'message' => 'ไม่พบไฟล์ที่ระบุในเซิร์ฟเวอร์'];
    }

    ini_set('memory_limit', '512M');
    set_time_limit(300);

    try {
        $file_ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $readerType = ($file_ext === 'xlsx') ? 'Xlsx' : 'Xls';
        $reader = IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($filePath);

        $sheetNames = $spreadsheet->getSheetNames();

        // -------------------------------------------------------------
        // ชั้นที่ 1: ตรวจสอบโครงสร้างไฟล์ (Format Integrity)
        // -------------------------------------------------------------
        $has_nhso_sheet = false;
        $sheet_normal_name = null;
        $sheet_appeal_name = null;
        $sheet_summary_name = null;
        $sheet_cover_name = null;

        foreach ($sheetNames as $name) {
            $trimmedName = trim($name);
            if (mb_strpos($trimmedName, 'รายละเอียด(ข้อมูลปกติ)') !== false || mb_strpos($trimmedName, 'ข้อมูลปกติ') !== false) {
                $has_nhso_sheet = true;
                $sheet_normal_name = $name;
            } elseif (mb_strpos($trimmedName, 'รายละเอียด(ข้อมูลอุทธรณ์)') !== false || mb_strpos($trimmedName, 'ข้อมูลอุทธรณ์') !== false) {
                $sheet_appeal_name = $name;
            } elseif (mb_strpos($trimmedName, 'รายงานสรุป') !== false) {
                $sheet_summary_name = $name;
            } elseif (mb_strpos($trimmedName, 'รายงานพึงรับ') !== false) {
                $sheet_cover_name = $name;
            }
        }

        if (!$has_nhso_sheet || !$sheet_normal_name) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $reader);
            return [
                'status' => 'error',
                'message' => 'รูปแบบไฟล์ไม่ถูกต้อง: ไม่พบแผ่นงาน "รายละเอียด(ข้อมูลปกติ)" ของ สปสช. กรุณาใช้ไฟล์ STM ต้นฉบับจากระบบ e-Claim/FDH'
            ];
        }

        // -------------------------------------------------------------
        // สกัดข้อมูล Header Metadata จาก Sheet ปะหน้า หรือ Sheet ปกติ
        // -------------------------------------------------------------
        $meta = [
            'docno' => '',
            'hospcode' => '',
            'hospname' => '',
            'province' => '',
            'report_date' => '',
            'fund' => 'UCS',
            'visit_type' => 'OPD',
            'period' => '',
            'cover_normal_cases' => 0,
            'cover_normal_comp' => 0.0,
            'cover_appeal_cases' => 0,
            'cover_appeal_comp' => 0.0,
            'cover_total_comp' => 0.0
        ];

        // อ่าน Sheet ปะหน้า (Sheet 0: รายงานพึงรับ)
        if ($sheet_cover_name) {
            $coverSheet = $spreadsheet->getSheetByName($sheet_cover_name);
            $coverRows = $coverSheet->toArray(null, true, true, false);
            
            for ($r = 0; $r < min(15, count($coverRows)); $r++) {
                $rowText = implode(' ', array_filter(array_map('strval', $coverRows[$r] ?? [])));
                
                // วันที่ออกรายงาน
                if (preg_match('/ออกรายงานวันที่\s*([0-9\/\:]+\s*[0-9\:]*)/u', $rowText, $m)) {
                    $meta['report_date'] = trim($m[1]);
                }
                // โรงพยาบาล
                if (preg_match('/โรงพยาบาล\s*([0-9]{5})\s*(.*)/u', $rowText, $m)) {
                    $meta['hospcode'] = trim($m[1]);
                    $meta['hospname'] = trim($m[2]);
                }
                // จังหวัด
                if (preg_match('/จังหวัด\s*(.*)/u', $rowText, $m)) {
                    $meta['province'] = trim($m[1]);
                }
                // เลขที่เอกสาร
                if (preg_match('/เลขที่เอกสาร\s*([0-9A-Za-z\_]+)/u', $rowText, $m)) {
                    $meta['docno'] = trim($m[1]);
                }
            }

            // ดึงยอดสรุปจาก Sheet ปะหน้า
            for ($r = 10; $r < count($coverRows); $r++) {
                $c0 = trim(strval($coverRows[$r][0] ?? ''));
                if (mb_strpos($c0, 'ผู้ป่วยนอก') !== false || mb_strpos($c0, 'ผู้ป่วยใน') !== false) {
                    $cases = (int)str_replace(',', '', strval($coverRows[$r][2] ?? '0'));
                    $comp = (float)str_replace(',', '', strval($coverRows[$r][4] ?? '0'));
                    if ($meta['cover_normal_cases'] === 0) {
                        $meta['cover_normal_cases'] = $cases;
                        $meta['cover_normal_comp'] = $comp;
                    } else {
                        $meta['cover_appeal_cases'] = $cases;
                        $meta['cover_appeal_comp'] = $comp;
                    }
                }
            }
            $meta['cover_total_comp'] = $meta['cover_normal_comp'] + $meta['cover_appeal_comp'];
        }

        // หากยังไม่มีเลขที่เอกสาร ให้ค้นจาก Sheet สรุป หรือ Sheet ปกติ
        if (empty($meta['docno'])) {
            $normalSheet = $spreadsheet->getSheetByName($sheet_normal_name);
            for ($r = 1; $r <= 8; $r++) {
                $cellVal = strval($normalSheet->getCell("A$r")->getValue());
                if (preg_match('/เลขที่เอกสาร\s*([0-9A-Za-z\_]+)/u', $cellVal, $m)) {
                    $meta['docno'] = trim($m[1]);
                }
                if (empty($meta['hospcode']) && preg_match('/โรงพยาบาล\s*([0-9]{5})\s*(.*)/u', $cellVal, $m)) {
                    $meta['hospcode'] = trim($m[1]);
                    $meta['hospname'] = trim($m[2]);
                }
            }
        }

        // วิเคราะห์ประเภทสิทธิและประเภทผู้ป่วยจากเลขที่เอกสาร (เช่น 11072_OPUCS256908_01)
        if (!empty($meta['docno'])) {
            $docParts = explode('_', $meta['docno']);
            if (count($docParts) >= 2) {
                $typeFundPart = strtoupper($docParts[1]);
                if (strpos($typeFundPart, 'OP') === 0) {
                    $meta['visit_type'] = 'OPD';
                    $rawFund = substr($typeFundPart, 2);
                } elseif (strpos($typeFundPart, 'IP') === 0) {
                    $meta['visit_type'] = 'IPD';
                    $rawFund = substr($typeFundPart, 2);
                } else {
                    $rawFund = $typeFundPart;
                }
                // ตัดงวดเดือน 6 หลักท้ายออก (เช่น 256908)
                if (preg_match('/([A-Z]+)([0-9]{6})/u', $rawFund, $fm)) {
                    $meta['fund'] = $fm[1];
                    $meta['period'] = $fm[2];
                } else {
                    $meta['fund'] = preg_replace('/[0-9]/', '', $rawFund);
                }
            }
        }

        // -------------------------------------------------------------
        // ชั้นที่ 2: ตรวจสอบรหัสโรงพยาบาล (Hospital Code Match)
        // -------------------------------------------------------------
        if (!empty($expectedHospcode) && !empty($meta['hospcode'])) {
            if (trim($meta['hospcode']) !== trim($expectedHospcode)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $reader);
                return [
                    'status' => 'error',
                    'message' => "❌ ปฏิเสธการนำเข้า: ไฟล์นี้เป็นของหน่วยบริการ [{$meta['hospcode']} {$meta['hospname']}] ไม่ตรงกับรหัสโรงพยาบาลในระบบ eDHS ของท่าน [{$expectedHospcode}]"
                ];
            }
        }

        // -------------------------------------------------------------
        // ชั้นที่ 3: ตรวจสอบความสอดคล้องของสิทธิ (Fund Match)
        // -------------------------------------------------------------
        if (!empty($expectedFund) && !empty($meta['fund'])) {
            if (strtoupper(trim($meta['fund'])) !== strtoupper(trim($expectedFund))) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $reader);
                return [
                    'status' => 'error',
                    'message' => "❌ สิทธิไม่ตรงกัน: สิทธิในไฟล์ Statement คือ [{$meta['fund']}] แต่ท่านเลือกลูกหนี้สิทธิ [{$expectedFund}] กรุณาเลือกสิทธิให้ตรงก่อนนำเข้า"
                ];
            }
        }

        // -------------------------------------------------------------
        // อ่านข้อมูลรายละเอียด Sheet 2 (ข้อมูลปกติ) และ Sheet 3 (ข้อมูลอุทธรณ์)
        // -------------------------------------------------------------
        $allPatients = [];
        $subgroupTotals = [
            'op' => 0.0,
            'ip' => 0.0,
            'hc' => 0.0,
            'ae' => 0.0,
            'inst' => 0.0,
            'dmis' => 0.0,
            'pallativecare' => 0.0,
            'dmishd' => 0.0,
            'pp' => 0.0,
            'fs' => 0.0,
            'opbkk' => 0.0,
            'total_comp' => 0.0,
            'total_collected' => 0.0
        ];

        $repSummaryMap = [];

        // ฟังก์ชันย่อยสำหรับอ่านแต่ละ Sheet ผู้ป่วย
        $readPatientSheet = function($sheetName, $isAppeal) use (
            $spreadsheet, &$allPatients, &$subgroupTotals, &$repSummaryMap, $meta
        ) {
            if (!$sheetName) return;
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $rows = $sheet->toArray(null, true, true, false);
            $rowCount = count($rows);

            for ($r = 14; $r < $rowCount; $r++) {
                $row = $rows[$r] ?? [];
                $tranId = trim(strval($row[2] ?? ''));
                if (empty($tranId)) continue; // ข้ามแถวที่ไม่ใช่ผู้ป่วย

                $rep = trim(strval($row[0] ?? ''));
                $seqNo = trim(strval($row[41] ?? '')); // SEQ NO (Col 41)
                $an = trim(strval($row[4] ?? ''));    // AN (Col 4)
                $vn = ($meta['visit_type'] === 'IPD' && !empty($an)) ? $an : $seqNo;

                $collected = (float)str_replace(',', '', strval($row[11] ?? '0'));
                $op = (float)str_replace(',', '', strval($row[21] ?? '0'));
                $ip = (float)str_replace(',', '', strval($row[23] ?? '0'));
                $hc_base = (float)str_replace(',', '', strval($row[24] ?? '0'));
                $hc_drug = (float)str_replace(',', '', strval($row[25] ?? '0'));
                $hc = $hc_base + $hc_drug;

                $ae_base = (float)str_replace(',', '', strval($row[26] ?? '0'));
                $ae_drug = (float)str_replace(',', '', strval($row[27] ?? '0'));
                $ae = $ae_base + $ae_drug;

                $inst = (float)str_replace(',', '', strval($row[28] ?? '0'));

                $dmis_base = (float)str_replace(',', '', strval($row[30] ?? '0'));
                $dmis_drug = (float)str_replace(',', '', strval($row[31] ?? '0'));
                $dmis = $dmis_base + $dmis_drug;

                $palliative = (float)str_replace(',', '', strval($row[32] ?? '0'));
                $dmishd = (float)str_replace(',', '', strval($row[33] ?? '0'));
                $pp = (float)str_replace(',', '', strval($row[34] ?? '0'));
                $fs = (float)str_replace(',', '', strval($row[35] ?? '0'));
                $opbkk = (float)str_replace(',', '', strval($row[36] ?? '0'));
                $compensated = (float)str_replace(',', '', strval($row[37] ?? '0'));

                $total_drug = $hc_drug + $ae_drug + $dmis_drug;
                $ontop = $pp + $fs + $inst;

                $subgroupTotals['op'] += $op;
                $subgroupTotals['ip'] += $ip;
                $subgroupTotals['hc'] += $hc;
                $subgroupTotals['ae'] += $ae;
                $subgroupTotals['inst'] += $inst;
                $subgroupTotals['dmis'] += $dmis;
                $subgroupTotals['pallativecare'] += $palliative;
                $subgroupTotals['dmishd'] += $dmishd;
                $subgroupTotals['pp'] += $pp;
                $subgroupTotals['fs'] += $fs;
                $subgroupTotals['opbkk'] += $opbkk;
                $subgroupTotals['total_comp'] += $compensated;
                $subgroupTotals['total_collected'] += $collected;

                // สะสมสถิติราย REP
                if (!isset($repSummaryMap[$rep])) {
                    $repSummaryMap[$rep] = [
                        'rep' => $rep,
                        'is_appeal' => $isAppeal,
                        'type_text' => $isAppeal ? 'ข้อมูลอุทธรณ์' : 'ข้อมูลปกติ',
                        'case_count' => 0,
                        'total_collected' => 0.0,
                        'total_compensated' => 0.0,
                        'source' => trim(strval($row[40] ?? 'FDH'))
                    ];
                }
                $repSummaryMap[$rep]['case_count']++;
                $repSummaryMap[$rep]['total_collected'] += $collected;
                $repSummaryMap[$rep]['total_compensated'] += $compensated;

                $allPatients[] = [
                    'rep' => $rep,
                    'no' => trim(strval($row[1] ?? '')),
                    'id' => $tranId,
                    'hn' => trim(strval($row[3] ?? '')),
                    'an' => $an,
                    'pid' => trim(strval($row[5] ?? '')),
                    'ptname' => trim(strval($row[6] ?? '')),
                    'admdate' => trim(strval($row[7] ?? '')),
                    'dchdate' => trim(strval($row[8] ?? '')),
                    'maininscl' => trim(strval($row[9] ?? $meta['fund'])),
                    'projcode' => trim(strval($row[10] ?? '')),
                    'collected' => $collected,
                    'op' => $op,
                    'ip' => $ip,
                    'hc' => $hc,
                    'ae' => $ae,
                    'inst' => $inst,
                    'dmis' => $dmis,
                    'drug' => $total_drug,
                    'ontop' => $ontop,
                    'pallativecare' => $palliative,
                    'dmishd' => $dmishd,
                    'pp' => $pp,
                    'fs' => $fs,
                    'opbkk' => $opbkk,
                    'compensated' => $compensated,
                    'paidtype' => trim(strval($row[40] ?? '')),
                    'vn' => $vn,
                    'seq_no' => $seqNo,
                    'is_appeal' => $isAppeal ? 1 : 0
                ];
            }
        };

        // อ่านข้อมูลปกติ (Sheet 2)
        $readPatientSheet($sheet_normal_name, false);

        // อ่านข้อมูลอุทธรณ์ (Sheet 3 ถ้ามี)
        if ($sheet_appeal_name) {
            $readPatientSheet($sheet_appeal_name, true);
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader);
        gc_collect_cycles();

        return [
            'status' => 'success',
            'metadata' => $meta,
            'subgroup_totals' => $subgroupTotals,
            'rep_summaries' => array_values($repSummaryMap),
            'total_cases' => count($allPatients),
            'normal_cases' => count(array_filter($allPatients, fn($p) => $p['is_appeal'] === 0)),
            'appeal_cases' => count(array_filter($allPatients, fn($p) => $p['is_appeal'] === 1)),
            'patients' => $allPatients
        ];

    } catch (\Throwable $e) {
        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการประมวลผลไฟล์ Excel: ' . $e->getMessage()
        ];
    }
}

/**
 * ตรวจสอบความซ้ำซ้อนของ Statement ในฐานข้อมูล
 */
function check_stm_duplicate($conn, $docno, $reps = []) {
    ensure_stm_columns($conn);
    $result = [
        'is_duplicate' => false,
        'docno_exists' => false,
        'existing_count' => 0,
        'existing_reps_count' => 0,
        'existing_total' => 0.0,
        'matched_reps' => []
    ];

    if (!empty($docno)) {
        $stmt = $conn->prepare("SELECT COUNT(*) as c, COALESCE(SUM(compensated), 0) as s FROM imr_tb_check_invoice WHERE docno = ?");
        if ($stmt) {
            $stmt->bind_param("s", $docno);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row && $row['c'] > 0) {
                $result['is_duplicate'] = true;
                $result['docno_exists'] = true;
                $result['existing_count'] = (int)$row['c'];
                $result['existing_total'] = (float)$row['s'];
                return $result;
            }
        }
    }

    // ตรวจสอบจากชุดเลข REP
    if (!empty($reps) && is_array($reps)) {
        $escaped_reps = array_map(fn($r) => "'" . mysqli_real_escape_string($conn, $r) . "'", array_unique($reps));
        $rep_sql = implode(',', $escaped_reps);
        $q = $conn->query("SELECT rep, COUNT(*) as c, COALESCE(SUM(compensated), 0) as s FROM imr_tb_check_invoice WHERE rep IN ($rep_sql) GROUP BY rep");
        if ($q && $q->num_rows > 0) {
            $result['is_duplicate'] = true;
            while ($r = $q->fetch_assoc()) {
                $result['existing_count'] += (int)$r['c'];
                $result['existing_total'] += (float)$r['s'];
                $result['matched_reps'][] = $r['rep'];
            }
            $result['existing_reps_count'] = count($result['matched_reps']);
        }
    }

    return $result;
}

/**
 * ดำเนินการจับคู่ลูกหนี้ eDHS (Debtor Enrichment) และ Batch Insert
 */
function enrich_and_insert_stm_patients($conn, $patients, $meta, $overwrite = false) {
    if (empty($patients)) {
        return ['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้ป่วยที่จะบันทึก'];
    }

    ensure_stm_columns($conn);

    $docno = $meta['docno'] ?? '';
    $visit_type = $meta['visit_type'] ?? 'OPD';
    $fund = $meta['fund'] ?? 'UCS';

    $conn->begin_transaction();

    try {
        // -------------------------------------------------------------
        // กรณี Overwrite: ลบข้อมูลชุดเดิมออกอย่างปลอดภัย
        // -------------------------------------------------------------
        if ($overwrite) {
            // 1. ดึง VN/AN เดิมที่จะลบเพื่อรีเซ็ต mobile='-'
            if (!empty($docno)) {
                $stmt_sel = $conn->prepare("SELECT vn FROM imr_tb_check_invoice WHERE docno = ?");
                $stmt_sel->bind_param("s", $docno);
                $stmt_sel->execute();
                $res_sel = $stmt_sel->get_result();
                $upd_opd = $conn->prepare("UPDATE imr_tb_debtor_rights_opd SET mobile='-' WHERE vn = ?");
                $upd_ipd = $conn->prepare("UPDATE imr_tb_debtor_rights_ipd SET mobile='-' WHERE an = ?");
                while ($r = $res_sel->fetch_assoc()) {
                    $v = $r['vn'];
                    if (!empty($v)) {
                        $upd_opd->bind_param("s", $v);
                        $upd_opd->execute();
                        $upd_ipd->bind_param("s", $v);
                        $upd_ipd->execute();
                    }
                }
                $stmt_sel->close();
                $upd_opd->close();
                $upd_ipd->close();

                // ลบข้อมูลเดิม
                $stmt_del = $conn->prepare("DELETE FROM imr_tb_check_invoice WHERE docno = ?");
                $stmt_del->bind_param("s", $docno);
                $stmt_del->execute();
                $stmt_del->close();
            } else {
                // ลบด้วยชุด REP
                $reps = array_unique(array_column($patients, 'rep'));
                if (!empty($reps)) {
                    $rep_list = "'" . implode("','", array_map(fn($r) => mysqli_real_escape_string($conn, $r), $reps)) . "'";
                    $q_vns = $conn->query("SELECT vn FROM imr_tb_check_invoice WHERE rep IN ($rep_list)");
                    if ($q_vns) {
                        $upd_opd = $conn->prepare("UPDATE imr_tb_debtor_rights_opd SET mobile='-' WHERE vn = ?");
                        $upd_ipd = $conn->prepare("UPDATE imr_tb_debtor_rights_ipd SET mobile='-' WHERE an = ?");
                        while ($rv = $q_vns->fetch_assoc()) {
                            if (!empty($rv['vn'])) {
                                $upd_opd->bind_param("s", $rv['vn']);
                                $upd_opd->execute();
                                $upd_ipd->bind_param("s", $rv['vn']);
                                $upd_ipd->execute();
                            }
                        }
                        $upd_opd->close();
                        $upd_ipd->close();
                    }
                    $conn->query("DELETE FROM imr_tb_check_invoice WHERE rep IN ($rep_list)");
                }
            }
        }

        // -------------------------------------------------------------
        // Auto-Enrichment: สร้างพจนานุกรมลูกหนี้จากตาราง eDHS
        // -------------------------------------------------------------
        $all_vns = array_filter(array_unique(array_column($patients, 'vn')));
        $debtor_map = [];

        if (!empty($all_vns)) {
            $vn_chunks = array_chunk($all_vns, 1000);
            foreach ($vn_chunks as $chunk) {
                $escaped = "'" . implode("','", array_map(fn($v) => mysqli_real_escape_string($conn, $v), $chunk)) . "'";
                if ($visit_type === 'IPD') {
                    $q = $conn->query("SELECT an as vn, debit, accountcode, accountname, pttypename, pttype_eclaim_name, dchdate as sdate FROM imr_tb_debtor_rights_ipd WHERE an IN ($escaped)");
                } else {
                    $q = $conn->query("SELECT vn, debit, accountcode, accountname, pttypename, pttype_eclaim_name, vstdate as sdate FROM imr_tb_debtor_rights_opd WHERE vn IN ($escaped)");
                }
                if ($q) {
                    while ($r = $q->fetch_assoc()) {
                        $debtor_map[$r['vn']] = $r;
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // เตรียม Prepared Statement สำหรับ Insert
        // -------------------------------------------------------------
        $sql = "INSERT INTO imr_tb_check_invoice (
            no, yearbudget, rep, docno, id, vn, pid, hn, ptname, admdate, dchdate,
            pttypename, pttype_eclaim_name, accountcode, accountname, subfund,
            collected, compensated, debit, diff, down, up, total, fund,
            hc, ae, inst, ip, dmis, op, drug, ontop, pallativecare, dmishd,
            pp, fs, opbkk, paidtype
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception("เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
        }

        $inserted_count = 0;
        $matched_debtor_count = 0;
        $unmatched_count = 0;
        $total_comp = 0.0;

        foreach ($patients as $p) {
            $vn = $p['vn'];
            $comp = (float)$p['compensated'];
            $collected = (float)$p['collected'];
            $total_comp += $comp;

            // ตรวจสอบลูกหนี้เพื่อ Enrich ข้อมูล
            $d_info = $debtor_map[$vn] ?? null;
            if ($d_info) {
                $matched_debtor_count++;
                $debit = (float)$d_info['debit'];
                $accCode = $d_info['accountcode'];
                $accName = $d_info['accountname'];
                $pttypeName = $d_info['pttypename'];
                $eclaimName = $d_info['pttype_eclaim_name'];
            } else {
                $unmatched_count++;
                $debit = $collected; // Fallback ยอดเรียกเก็บ
                if ($visit_type === 'IPD') {
                    $accCode = ($fund === 'UCS') ? '1102050101.202' : '';
                    $accName = ($fund === 'UCS') ? 'ลูกหนี้ค่ารักษา UC- IP (รอยืนยันผัง)' : 'รอยืนยันผังลูกหนี้';
                    $pttypeName = ($fund === 'UCS') ? 'บัตรทอง UC (IPD)' : $fund;
                    $eclaimName = 'UC ใน CUP (IPD)';
                } else {
                    $accCode = ($fund === 'UCS') ? '1102050101.201' : '';
                    $accName = ($fund === 'UCS') ? 'ลูกหนี้ค่ารักษา UC- OP (รอยืนยันผัง)' : 'รอยืนยันผังลูกหนี้';
                    $pttypeName = ($fund === 'UCS') ? 'บัตรทอง UC' : $fund;
                    $eclaimName = 'UC ใน CUP';
                }
            }

            // คำนวณผลต่างและส่วนต่ำ/ส่วนสูง
            $diff = round($comp - $debit, 2);
            $down = ($diff > 0) ? $diff : 0.0;
            $up = ($diff < 0) ? $diff : 0.0;
            $total = $comp;

            // คำนวณปีงบประมาณจากวันที่ (พ.ศ.)
            $admDate = $p['admdate'];
            $yearbudget = date('Y') + 543;
            if (preg_match('/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/u', $admDate, $dm)) {
                $m = (int)$dm[2];
                $y = (int)$dm[3];
                if ($y < 2400) $y += 543; // แปลง ค.ศ. เป็น พ.ศ.
                $yearbudget = ($m >= 10) ? ($y + 1) : $y;
            }

            // เข้ารหัสเลขบัตรประชาชน
            $encryptedPid = !empty($p['pid']) ? encrypt_data($p['pid']) : '';

            $stmt->bind_param(
                "ssssssssssssssssdddddddsddddddddddddds",
                $p['no'],
                $yearbudget,
                $p['rep'],
                $docno,
                $p['id'],
                $vn,
                $encryptedPid,
                $p['hn'],
                $p['ptname'],
                $admDate,
                $p['dchdate'],
                $pttypeName,
                $eclaimName,
                $accCode,
                $accName,
                $p['projcode'],
                $collected,
                $comp,
                $debit,
                $diff,
                $down,
                $up,
                $total,
                $fund,
                $p['hc'],
                $p['ae'],
                $p['inst'],
                $p['ip'],
                $p['dmis'],
                $p['op'],
                $p['drug'],
                $p['ontop'],
                $p['pallativecare'],
                $p['dmishd'],
                $p['pp'],
                $p['fs'],
                $p['opbkk'],
                $p['paidtype']
            );

            $stmt->execute();
            $inserted_count++;
        }

        $stmt->close();
        $conn->commit();

        // บันทึก System Audit Log
        if (function_exists('system_log')) {
            $user_name = $_SESSION['fullname'] ?? 'User';
            $logAction = $overwrite ? 'REPLACE' : 'IMPORT';
            $logMsg = "นำเข้า STM UC สำเร็จ (เอกสาร: $docno, กองทุน: $fund, บันทึก: $inserted_count รายการ, ชดเชยรวม: " . number_format($total_comp, 2) . " บ., แมตช์ลูกหนี้: $matched_debtor_count/$inserted_count)";
            system_log($conn, 'นำเข้า STM & ตัดลูกหนี้ตาม STM', $logAction, $logMsg);
        }

        return [
            'status' => 'success',
            'message' => 'บันทึกข้อมูล Statement สำเร็จเรียบร้อยแล้ว',
            'docno' => $docno,
            'inserted_count' => $inserted_count,
            'matched_debtor_count' => $matched_debtor_count,
            'unmatched_count' => $unmatched_count,
            'total_compensated' => $total_comp
        ];

    } catch (\Throwable $e) {
        $conn->rollback();
        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()
        ];
    }
}
