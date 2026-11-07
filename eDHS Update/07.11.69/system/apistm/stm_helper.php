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
require_once __DIR__ . '/../includes/cr_migration_helper.php';
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
 * Normalizes date string with Thai/Buddhist Era handling
 */
if (!function_exists('normalize_stm_date')) {
    function normalize_stm_date($rawDate) {
        if (empty($rawDate) || trim($rawDate) === '-') return '';
        $clean = trim(preg_replace('/\s*\/\s*/', '/', $rawDate));
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})(.*)$/', $clean, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = (int)$m[3];
            if ($year < 2400) $year += 543;
            return sprintf('%02d/%02d/%04d%s', $day, $month, $year, $m[4]);
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(.*)$/', $clean, $m)) {
            $year = (int)$m[1];
            if ($year < 2400) $year += 543;
            return sprintf('%02d/%02d/%04d', (int)$m[3], (int)$m[2], $year);
        }
        return $clean;
    }
}

/**
 * 1. Format Type A: UCS Official 4-Sheet Parser
 */
function parse_stm_ucs_4sheet($spreadsheet, $expectedFund = 'UCS', $expectedHospcode = '') {
    $sheetNames = $spreadsheet->getSheetNames();
    $sheet_normal_name = null;
    $sheet_appeal_name = null;
    $sheet_cover_name = null;

    foreach ($sheetNames as $name) {
        $trimmedName = trim($name);
        if (mb_strpos($trimmedName, 'รายละเอียด(ข้อมูลปกติ)') !== false || mb_strpos($trimmedName, 'ข้อมูลปกติ') !== false) {
            $sheet_normal_name = $name;
        } elseif (mb_strpos($trimmedName, 'รายละเอียด(ข้อมูลอุทธรณ์)') !== false || mb_strpos($trimmedName, 'ข้อมูลอุทธรณ์') !== false) {
            $sheet_appeal_name = $name;
        } elseif (mb_strpos($trimmedName, 'รายงานพึงรับ') !== false) {
            $sheet_cover_name = $name;
        }
    }

    if (!$sheet_normal_name) {
        return ['status' => 'error', 'message' => 'ไม่พบแผ่นงานรายละเอียด(ข้อมูลปกติ) ของ สปสช.'];
    }

    $meta = [
        'format_type' => 'UC',
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

    if ($sheet_cover_name) {
        $coverSheet = $spreadsheet->getSheetByName($sheet_cover_name);
        $coverRows = $coverSheet->toArray(null, true, true, false);
        for ($r = 0; $r < min(15, count($coverRows)); $r++) {
            $rowText = implode(' ', array_filter(array_map('strval', $coverRows[$r] ?? [])));
            if (preg_match('/ออกรายงานวันที่\s*([0-9\/\:]+\s*[0-9\:]*)/u', $rowText, $m)) $meta['report_date'] = trim($m[1]);
            if (preg_match('/โรงพยาบาล\s*([0-9]{5})\s*(.*)/u', $rowText, $m)) { $meta['hospcode'] = trim($m[1]); $meta['hospname'] = trim($m[2]); }
            if (preg_match('/จังหวัด\s*(.*)/u', $rowText, $m)) $meta['province'] = trim($m[1]);
            if (preg_match('/เลขที่เอกสาร\s*([0-9A-Za-z\_]+)/u', $rowText, $m)) $meta['docno'] = trim($m[1]);
        }
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

    if (!empty($meta['docno'])) {
        $parts = explode('_', $meta['docno']);
        if (count($parts) >= 2) {
            $second = strtoupper($parts[1]);
            if (strpos($second, 'OP') === 0) { $meta['visit_type'] = 'OPD'; $raw = substr($second, 2); }
            elseif (strpos($second, 'IP') === 0) { $meta['visit_type'] = 'IPD'; $raw = substr($second, 2); }
            else { $raw = $second; }
            if (preg_match('/([A-Z]+)([0-9]{6})/u', $raw, $fm)) {
                $meta['fund'] = $fm[1];
                $meta['period'] = $fm[2];
            } else {
                $meta['fund'] = preg_replace('/[0-9]/', '', $raw);
            }
        }
    }

    if (!empty($expectedHospcode) && !empty($meta['hospcode'])) {
        if (trim($meta['hospcode']) !== trim($expectedHospcode)) {
            return [
                'status' => 'error',
                'message' => "❌ ปฏิเสธการนำเข้า: ไฟล์นี้เป็นของหน่วยบริการ [{$meta['hospcode']} {$meta['hospname']}] ไม่ตรงกับรหัสโรงพยาบาลในระบบ eDHS ของท่าน [{$expectedHospcode}]"
            ];
        }
    }

    $allPatients = [];
    $subgroupTotals = [
        'op' => 0.0, 'ip' => 0.0, 'hc' => 0.0, 'ae' => 0.0, 'inst' => 0.0, 'dmis' => 0.0,
        'pallativecare' => 0.0, 'dmishd' => 0.0, 'pp' => 0.0, 'fs' => 0.0, 'opbkk' => 0.0,
        'room' => 0.0, 'drug' => 0.0, 'treat' => 0.0, 'car' => 0.0, 'wait' => 0.0, 'other' => 0.0, 'ontop' => 0.0,
        'total_comp' => 0.0, 'total_collected' => 0.0
    ];
    $repSummaryMap = [];

    $readPatientSheet = function($sheetName, $isAppeal) use ($spreadsheet, &$allPatients, &$subgroupTotals, &$repSummaryMap, $meta) {
        if (!$sheetName) return;
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $rows = $sheet->toArray(null, true, true, false);
        for ($r = 14; $r < count($rows); $r++) {
            $row = $rows[$r] ?? [];
            $tranId = trim(strval($row[2] ?? ''));
            if (empty($tranId)) continue;

            $rep = trim(strval($row[0] ?? ''));
            $seqNo = trim(strval($row[41] ?? ''));
            $an = trim(strval($row[4] ?? ''));
            $vn = ($meta['visit_type'] === 'IPD' && !empty($an) && $an !== '-') ? $an : $seqNo;

            $collected = (float)str_replace(',', '', strval($row[11] ?? '0'));
            $op = (float)str_replace(',', '', strval($row[21] ?? '0'));
            $ip = (float)str_replace(',', '', strval($row[23] ?? '0'));
            $hc = (float)str_replace(',', '', strval($row[24] ?? '0')) + (float)str_replace(',', '', strval($row[25] ?? '0'));
            $ae = (float)str_replace(',', '', strval($row[26] ?? '0')) + (float)str_replace(',', '', strval($row[27] ?? '0'));
            $inst = (float)str_replace(',', '', strval($row[28] ?? '0'));
            $dmis = (float)str_replace(',', '', strval($row[30] ?? '0')) + (float)str_replace(',', '', strval($row[31] ?? '0'));
            $palliative = (float)str_replace(',', '', strval($row[32] ?? '0'));
            $dmishd = (float)str_replace(',', '', strval($row[33] ?? '0'));
            $pp = (float)str_replace(',', '', strval($row[34] ?? '0'));
            $fs = (float)str_replace(',', '', strval($row[35] ?? '0'));
            $opbkk = (float)str_replace(',', '', strval($row[36] ?? '0'));
            // กฎโครงสร้าง STM สปสช.: คอลัมน์ 37 คือ ยอดชดเชยทั้งสิ้น (ไม่ใช่ ontop), คอลัมน์ 39 คือ COVID
            $compensated = (float)str_replace(',', '', strval($row[37] ?? '0'));
            $ontop = ($meta['visit_type'] === 'IPD') ? 0.0 : ($pp + $fs + $inst);
            $adjrw = (float)str_replace(',', '', strval($row[13] ?? '0'));

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
            $subgroupTotals['ontop'] += $ontop;
            $subgroupTotals['total_comp'] += $compensated;
            $subgroupTotals['total_collected'] += $collected;

            if (!isset($repSummaryMap[$rep])) {
                $repSummaryMap[$rep] = [
                    'rep' => $rep,
                    'case_count' => 0,
                    'total_collected' => 0.0,
                    'total_compensated' => 0.0,
                    'is_appeal' => $isAppeal ? 1 : 0,
                    'source' => trim(strval($row[40] ?? 'FDH'))
                ];
            }
            $repSummaryMap[$rep]['case_count']++;
            $repSummaryMap[$rep]['total_collected'] += $collected;
            $repSummaryMap[$rep]['total_compensated'] += $compensated;

            $allPatients[] = [
                'no' => trim(strval($row[1] ?? '')),
                'rep' => $rep,
                'id' => $tranId,
                'vn' => $vn,
                'an' => ($an !== '-') ? $an : '',
                'hn' => trim(strval($row[3] ?? '')),
                'pid' => trim(strval($row[5] ?? '')),
                'ptname' => trim(strval($row[6] ?? '')),
                'admdate' => normalize_stm_date(trim(strval($row[7] ?? ''))),
                'dchdate' => normalize_stm_date(trim(strval($row[8] ?? ''))),
                'projcode' => trim(strval($row[10] ?? '')),
                'adjrw' => $adjrw,
                'collected' => $collected,
                'compensated' => $compensated,
                'op' => $op,
                'ip' => $ip,
                'hc' => $hc,
                'ae' => $ae,
                'inst' => $inst,
                'dmis' => $dmis,
                'drug' => (float)str_replace(',', '', strval($row[25] ?? '0')) + (float)str_replace(',', '', strval($row[27] ?? '0')) + (float)str_replace(',', '', strval($row[31] ?? '0')),
                'ontop' => $ontop,
                'pallativecare' => $palliative,
                'dmishd' => $dmishd,
                'pp' => $pp,
                'fs' => $fs,
                'opbkk' => $opbkk,
                'room' => 0.0,
                'treat' => ($meta['visit_type'] === 'IPD' ? $ip : $op),
                'car' => 0.0,
                'wait' => 0.0,
                'other' => $ontop,
                'paidtype' => trim(strval($row[40] ?? '')),
                'cat_key' => ($fs > 0 ? 'HERB' : ($hc > 0 ? 'WALKIN' : ($ae > 0 ? 'AE' : ($inst > 0 ? 'INST' : ($pp > 0 ? 'PP' : ($op > 0 ? 'WALKIN' : 'OTHER')))))),
                'is_appeal' => $isAppeal ? 1 : 0
            ];
        }
    };

    $readPatientSheet($sheet_normal_name, false);
    if ($sheet_appeal_name) $readPatientSheet($sheet_appeal_name, true);

    $displayCategories = [
        ['name' => '1. OP (ผู้ป่วยนอกทั่วไป)', 'col' => 'Col 21 (พึงรับ OP)', 'val' => $subgroupTotals['op']],
        ['name' => '2. HC & ยาจิตเวช (Home Care / ยาเฉพาะ)', 'col' => 'Col 24+25 (HC+DRUG)', 'val' => $subgroupTotals['hc']],
        ['name' => '3. AE (ฉุกเฉิน / อุบัติเหตุ)', 'col' => 'Col 26+27 (AE+DRUG)', 'val' => $subgroupTotals['ae']],
        ['name' => '4. INST (อุปกรณ์และอวัยวะเทียม Fee Schedule)', 'col' => 'Col 28 (INST)', 'val' => $subgroupTotals['inst']],
        ['name' => '5. DMIS (โรคเรื้อรังเฉพาะกลุ่ม)', 'col' => 'Col 30+31 (DMIS+DRUG)', 'val' => $subgroupTotals['dmis']],
        ['name' => '6. Palliative Care (การดูแลระยะประคับประคอง)', 'col' => 'Col 32 (Palliative)', 'val' => $subgroupTotals['pallativecare']],
        ['name' => '7. PP (สร้างเสริมสุขภาพและป้องกันโรค)', 'col' => 'Col 34 (PP)', 'val' => $subgroupTotals['pp']],
        ['name' => '8. FS (บริการ Fee Schedule จ่ายตามรายการ)', 'col' => 'Col 35 (FS)', 'val' => $subgroupTotals['fs']]
    ];
    if ($meta['visit_type'] === 'IPD' && $subgroupTotals['ip'] > 0) {
        array_unshift($displayCategories, ['name' => '0. IP (ผู้ป่วยในหลักประกันสุขภาพ)', 'col' => 'Col 23 (พึงรับ IP)', 'val' => $subgroupTotals['ip']]);
    }

    return [
        'status' => 'success',
        'format_type' => 'UC',
        'metadata' => $meta,
        'subgroup_totals' => $subgroupTotals,
        'display_categories' => $displayCategories,
        'rep_summaries' => array_values($repSummaryMap),
        'total_cases' => count($allPatients),
        'normal_cases' => count(array_filter($allPatients, function($p) { return $p['is_appeal'] === 0; })),
        'appeal_cases' => count(array_filter($allPatients, function($p) { return $p['is_appeal'] === 1; })),
        'patients' => $allPatients
    ];
}

/**
 * 2. Format Type B: Non-UC Standard 2-Sheet Parser (BKK, OFC, SRT)
 */
function parse_stm_non_uc_2sheet($spreadsheet, $expectedFund = 'OFC', $expectedHospcode = '') {
    $sheet = $spreadsheet->getSheetByName('พึงรับ');
    if (!$sheet) {
        return ['status' => 'error', 'message' => 'ไม่พบแผ่นงาน "พึงรับ"'];
    }
    $rows = $sheet->toArray(null, true, true, false);

    $meta = [
        'format_type' => 'NON_UC',
        'docno' => '',
        'hospcode' => '',
        'hospname' => '',
        'province' => '',
        'report_date' => '',
        'fund' => $expectedFund,
        'visit_type' => 'OPD',
        'period' => ''
    ];

    for ($r = 0; $r < min(15, count($rows)); $r++) {
        $rowText = implode(' ', array_filter(array_map('strval', $rows[$r] ?? [])));
        if (preg_match('/ออกรายงานวันที่\s*([0-9\/\:]+\s*[0-9\:]*)/u', $rowText, $m)) $meta['report_date'] = trim($m[1]);
        if (preg_match('/โรงพยาบาล\s*([0-9]{5})\s*(.*)/u', $rowText, $m)) { $meta['hospcode'] = trim($m[1]); $meta['hospname'] = trim($m[2]); }
        if (preg_match('/จังหวัด\s*(.*)/u', $rowText, $m)) $meta['province'] = trim($m[1]);
        if (preg_match('/เลขที่เอกสาร\s*([0-9A-Za-z\_]+)/u', $rowText, $m)) $meta['docno'] = trim($m[1]);
    }

    if (!empty($meta['docno'])) {
        $docParts = explode('_', $meta['docno']);
        if (count($docParts) >= 2) {
            $second = strtoupper($docParts[1]);
            if (strpos($second, 'OP') === 0) { $meta['visit_type'] = 'OPD'; $rest = substr($second, 2); }
            elseif (strpos($second, 'IP') === 0) { $meta['visit_type'] = 'IPD'; $rest = substr($second, 2); }
            else { $rest = $second; }
            if (preg_match('/([0-9]{6})/u', $rest, $pm)) $meta['period'] = $pm[1];
        }
    }

    if (!empty($expectedHospcode) && !empty($meta['hospcode'])) {
        if (trim($meta['hospcode']) !== trim($expectedHospcode)) {
            return [
                'status' => 'error',
                'message' => "❌ ปฏิเสธการนำเข้า: ไฟล์นี้เป็นของหน่วยบริการ [{$meta['hospcode']} {$meta['hospname']}] ไม่ตรงกับรหัสโรงพยาบาลในระบบ eDHS ของท่าน [{$expectedHospcode}]"
            ];
        }
    }

    $subgroupTotals = [
        'room' => 0.0, 'inst' => 0.0, 'drug' => 0.0, 'treat' => 0.0, 'car' => 0.0, 'wait' => 0.0, 'other' => 0.0,
        'op' => 0.0, 'ip' => 0.0, 'hc' => 0.0, 'ae' => 0.0, 'dmis' => 0.0, 'ontop' => 0.0,
        'pallativecare' => 0.0, 'dmishd' => 0.0, 'pp' => 0.0, 'fs' => 0.0, 'opbkk' => 0.0,
        'total_comp' => 0.0, 'total_collected' => 0.0
    ];

    $patients = [];
    $currentRep = '';

    for ($r = 0; $r < count($rows); $r++) {
        $row = $rows[$r] ?? [];
        $c0 = trim((string)($row[0] ?? ''));
        $c1 = trim((string)($row[1] ?? ''));

        if (preg_match('/REP\s*NO\s*:\s*([0-9A-Za-z]+)/u', $c0, $rm)) {
            $currentRep = $rm[1];
            continue;
        }

        if (is_numeric($c0) && is_numeric($c1)) {
            $rowRep = !empty($currentRep) ? $currentRep : $c0;
            $hn = trim((string)($row[2] ?? ''));
            $an = trim((string)($row[3] ?? ''));
            $pid = trim((string)($row[4] ?? ''));
            $ptname = trim((string)($row[5] ?? ''));
            $admDate = normalize_stm_date(trim((string)($row[6] ?? '')));
            $dchDate = normalize_stm_date(trim((string)($row[7] ?? '')));
            $projcode = trim((string)($row[8] ?? ''));
            $adjrw = (float)str_replace(',', '', (string)($row[9] ?? '0'));
            $collected = (float)str_replace(',', '', (string)($row[10] ?? '0'));
            $room = (float)str_replace(',', '', (string)($row[12] ?? '0'));
            $inst = (float)str_replace(',', '', (string)($row[13] ?? '0'));
            $drug = (float)str_replace(',', '', (string)($row[14] ?? '0'));
            $treat = (float)str_replace(',', '', (string)($row[15] ?? '0'));
            $car = (float)str_replace(',', '', (string)($row[16] ?? '0'));
            $wait = (float)str_replace(',', '', (string)($row[17] ?? '0'));
            $other = (float)str_replace(',', '', (string)($row[18] ?? '0'));
            $compensated = (float)str_replace(',', '', (string)($row[19] ?? '0'));
            $seqNo = trim((string)($row[20] ?? ''));

            $vn = ($meta['visit_type'] === 'IPD' && !empty($an) && $an !== '-') ? $an : $seqNo;
            $opVal = ($meta['visit_type'] === 'OPD') ? $treat : 0.0;
            $ipVal = ($meta['visit_type'] === 'IPD') ? $treat : 0.0;

            $patients[] = [
                'no' => $c1,
                'rep' => $rowRep,
                'id' => $vn,
                'vn' => $vn,
                'an' => ($an !== '-') ? $an : '',
                'pid' => $pid,
                'hn' => $hn,
                'ptname' => $ptname,
                'admdate' => $admDate,
                'dchdate' => $dchDate,
                'projcode' => $projcode,
                'adjrw' => $adjrw,
                'collected' => $collected,
                'compensated' => $compensated,
                'room' => $room,
                'inst' => $inst,
                'drug' => $drug,
                'op' => $opVal,
                'ip' => $ipVal,
                'hc' => $car,
                'ae' => 0.0,
                'dmis' => 0.0,
                'ontop' => $other,
                'pallativecare' => 0.0,
                'dmishd' => 0.0,
                'pp' => 0.0,
                'fs' => 0.0,
                'opbkk' => ($expectedFund === 'BKK' && $meta['visit_type'] === 'OPD') ? $treat : 0.0,
                'treat' => $treat,
                'car' => $car,
                'wait' => $wait,
                'other' => $other,
                'paidtype' => '',
                'is_appeal' => 0
            ];

            $subgroupTotals['room'] += $room;
            $subgroupTotals['inst'] += $inst;
            $subgroupTotals['drug'] += $drug;
            $subgroupTotals['treat'] += $treat;
            $subgroupTotals['car'] += $car;
            $subgroupTotals['wait'] += $wait;
            $subgroupTotals['other'] += $other;
            $subgroupTotals['op'] += $opVal;
            $subgroupTotals['ip'] += $ipVal;
            $subgroupTotals['hc'] += $car;
            $subgroupTotals['ontop'] += $other;
            $subgroupTotals['total_comp'] += $compensated;
            $subgroupTotals['total_collected'] += $collected;
        }
    }

    $repSummaries = [];
    if ($spreadsheet->sheetNameExists('สรุป(พึงรับ)')) {
        $sumSheet = $spreadsheet->getSheetByName('สรุป(พึงรับ)');
        $sumRows = $sumSheet->toArray(null, true, true, false);
        for ($sr = 3; $sr < count($sumRows); $sr++) {
            $srow = $sumRows[$sr] ?? [];
            $repNo = trim((string)($srow[2] ?? ''));
            if (empty($repNo) || !is_numeric($repNo)) continue;

            $repSummaries[$repNo] = [
                'rep' => $repNo,
                'case_count' => (int)str_replace(',', '', (string)($srow[3] ?? '0')),
                'total_collected' => (float)str_replace(',', '', (string)($srow[6] ?? '0')),
                'total_compensated' => (float)str_replace(',', '', (string)($srow[15] ?? '0')),
                'is_appeal' => 0,
                'source' => 'FDH/e-Claim'
            ];
        }
    }

    if (empty($repSummaries)) {
        foreach ($patients as $p) {
            $r = $p['rep'];
            if (!isset($repSummaries[$r])) {
                $repSummaries[$r] = [
                    'rep' => $r,
                    'case_count' => 0,
                    'total_collected' => 0.0,
                    'total_compensated' => 0.0,
                    'is_appeal' => 0,
                    'source' => 'FDH/e-Claim'
                ];
            }
            $repSummaries[$r]['case_count']++;
            $repSummaries[$r]['total_collected'] += $p['collected'];
            $repSummaries[$r]['total_compensated'] += $p['compensated'];
        }
    }

    $displayCategories = [
        ['name' => '1. ค่าห้อง / ค่าอาหาร', 'col' => 'ROOM', 'val' => $subgroupTotals['room']],
        ['name' => '2. ค่าอวัยวะเทียมและอุปกรณ์ในการบำบัดรักษาโรค', 'col' => 'INST', 'val' => $subgroupTotals['inst']],
        ['name' => '3. ค่ายา สารอาหารทางเส้นเลือด และก๊าซทางการแพทย์', 'col' => 'DRUG', 'val' => $subgroupTotals['drug']],
        ['name' => '4. ค่ารักษาพยาบาล / ตรวจวินิจฉัย / ผ่าตัด / ทำคลอด', 'col' => 'TREAT', 'val' => $subgroupTotals['treat']],
        ['name' => '5. ค่าบริการส่งต่อ / รถพยาบาล', 'col' => 'AMB', 'val' => $subgroupTotals['car']],
        ['name' => '6. กรณีพักรอการจำหน่าย', 'col' => 'WAIT', 'val' => $subgroupTotals['wait']],
        ['name' => '7. ค่าบริการทางการแพทย์อื่นๆ / ค่าบริการ On-top', 'col' => 'OTHER', 'val' => $subgroupTotals['other']],
    ];

    return [
        'status' => 'success',
        'format_type' => 'NON_UC',
        'metadata' => $meta,
        'subgroup_totals' => $subgroupTotals,
        'display_categories' => $displayCategories,
        'rep_summaries' => array_values($repSummaries),
        'total_cases' => count($patients),
        'normal_cases' => count($patients),
        'appeal_cases' => 0,
        'patients' => $patients
    ];
}

/**
 * 3. Format Type C: LGO e-Claim Parser (Detail 58 cols)
 */
function parse_stm_lgo_eclaim($spreadsheet, $expectedFund = 'LGO', $expectedHospcode = '') {
    $sheet = $spreadsheet->getSheetByName('Detail');
    if (!$sheet) {
        return ['status' => 'error', 'message' => 'ไม่พบแผ่นงาน "Detail"'];
    }
    $rows = $sheet->toArray(null, true, true, false);

    $meta = [
        'format_type' => 'LGO_ECLAIM',
        'docno' => '',
        'hospcode' => '',
        'hospname' => '',
        'province' => '',
        'report_date' => '',
        'fund' => 'LGO',
        'visit_type' => 'OPD',
        'period' => ''
    ];

    for ($r = 0; $r < min(6, count($rows)); $r++) {
        $rText = implode(' ', array_filter(array_map('strval', $rows[$r] ?? [])));
        if (preg_match('/ออกรายงานวันที่\s*([0-9\/\:]+\s*[0-9\:]*)/u', $rText, $m)) $meta['report_date'] = trim($m[1]);
        if (preg_match('/โรงพยาบาล\s*([0-9]{5})\s*(.*)/u', $rText, $m)) { $meta['hospcode'] = trim($m[1]); $meta['hospname'] = trim($m[2]); }
        if (preg_match('/จังหวัด\s*(.*)/u', $rText, $m)) $meta['province'] = trim($m[1]);
        if (preg_match('/เลขที่เอกสาร\s*([0-9A-Za-z\_]+)/u', $rText, $m)) $meta['docno'] = trim($m[1]);
    }

    if (strpos($meta['docno'], 'IPLGO') !== false) {
        $meta['visit_type'] = 'IPD';
    } else {
        $meta['visit_type'] = 'OPD';
    }
    if (preg_match('/([0-9]{8})/u', $meta['docno'], $pm)) {
        $meta['period'] = substr($pm[1], 0, 6);
    }

    if (!empty($expectedHospcode) && !empty($meta['hospcode'])) {
        if (trim($meta['hospcode']) !== trim($expectedHospcode)) {
            return [
                'status' => 'error',
                'message' => "❌ ปฏิเสธการนำเข้า: ไฟล์นี้เป็นของหน่วยบริการ [{$meta['hospcode']} {$meta['hospname']}] ไม่ตรงกับรหัสโรงพยาบาลในระบบ eDHS ของท่าน [{$expectedHospcode}]"
            ];
        }
    }

    $subgroupTotals = [
        'room' => 0.0, 'inst' => 0.0, 'drug' => 0.0, 'treat' => 0.0, 'car' => 0.0, 'wait' => 0.0, 'other' => 0.0,
        'op' => 0.0, 'ip' => 0.0, 'hc' => 0.0, 'ae' => 0.0, 'dmis' => 0.0, 'ontop' => 0.0,
        'pallativecare' => 0.0, 'dmishd' => 0.0, 'pp' => 0.0, 'fs' => 0.0, 'opbkk' => 0.0,
        'total_comp' => 0.0, 'total_collected' => 0.0
    ];

    $patients = [];
    $repSummaries = [];

    for ($r = 7; $r < count($rows); $r++) {
        $row = $rows[$r] ?? [];
        $rep = trim((string)($row[0] ?? ''));
        $seq = trim((string)($row[1] ?? ''));
        if (!is_numeric($rep) || !is_numeric($seq)) continue;

        $hn = trim((string)($row[3] ?? ''));
        $an = trim((string)($row[4] ?? ''));
        $pid = trim((string)($row[5] ?? ''));
        $ptname = trim((string)($row[6] ?? ''));
        $admDate = normalize_stm_date(trim((string)($row[8] ?? '')));
        $dchDate = normalize_stm_date(trim((string)($row[9] ?? '')));
        $comp = (float)str_replace(',', '', (string)($row[10] ?? '0'));
        $coll = (float)str_replace(',', '', (string)($row[29] ?? '0'));
        $drug = (float)str_replace(',', '', (string)($row[46] ?? '0'));
        $otlg = (float)str_replace(',', '', (string)($row[44] ?? '0'));
        $inst = (float)str_replace(',', '', (string)($row[43] ?? '0'));
        $seqNo = trim((string)($row[55] ?? ''));

        $vn = ($meta['visit_type'] === 'IPD' && !empty($an) && $an !== '-') ? $an : $seqNo;
        $treat = $comp - $drug - $inst;
        if ($treat < 0) $treat = 0.0;

        $opVal = ($meta['visit_type'] === 'OPD') ? $treat : 0.0;
        $ipVal = ($meta['visit_type'] === 'IPD') ? $treat : 0.0;

        $patients[] = [
            'no' => $seq,
            'rep' => $rep,
            'id' => $vn,
            'vn' => $vn,
            'an' => ($an !== '-') ? $an : '',
            'pid' => $pid,
            'hn' => $hn,
            'ptname' => $ptname,
            'admdate' => $admDate,
            'dchdate' => $dchDate,
            'projcode' => '',
            'adjrw' => 0.0,
            'collected' => $coll,
            'compensated' => $comp,
            'room' => 0.0,
            'inst' => $inst,
            'drug' => $drug,
            'op' => $opVal,
            'ip' => $ipVal,
            'hc' => 0.0,
            'ae' => 0.0,
            'dmis' => 0.0,
            'ontop' => $otlg,
            'pallativecare' => 0.0,
            'dmishd' => 0.0,
            'pp' => 0.0,
            'fs' => 0.0,
            'opbkk' => 0.0,
            'treat' => $treat,
            'car' => 0.0,
            'wait' => 0.0,
            'other' => $otlg,
            'paidtype' => '',
            'is_appeal' => 0
        ];

        $subgroupTotals['drug'] += $drug;
        $subgroupTotals['inst'] += $inst;
        $subgroupTotals['ontop'] += $otlg;
        $subgroupTotals['treat'] += $treat;
        $subgroupTotals['op'] += $opVal;
        $subgroupTotals['ip'] += $ipVal;
        $subgroupTotals['total_comp'] += $comp;
        $subgroupTotals['total_collected'] += $coll;

        if (!isset($repSummaries[$rep])) {
            $repSummaries[$rep] = [
                'rep' => $rep,
                'case_count' => 0,
                'total_collected' => 0.0,
                'total_compensated' => 0.0,
                'is_appeal' => 0,
                'source' => 'e-Claim LGO'
            ];
        }
        $repSummaries[$rep]['case_count']++;
        $repSummaries[$rep]['total_collected'] += $coll;
        $repSummaries[$rep]['total_compensated'] += $comp;
    }

    $displayCategories = [
        ['name' => '1. ค่าห้อง / ค่าอาหาร', 'col' => 'ROOM', 'val' => $subgroupTotals['room']],
        ['name' => '2. ค่าอวัยวะเทียมและอุปกรณ์ในการบำบัดรักษาโรค', 'col' => 'Col AR (43)', 'val' => $subgroupTotals['inst']],
        ['name' => '3. ค่ายา สารอาหารทางเส้นเลือด และก๊าซทางการแพทย์', 'col' => 'Col AU (46)', 'val' => $subgroupTotals['drug']],
        ['name' => '4. ค่ารักษาพยาบาล / บริการทั่วไป', 'col' => 'TREAT', 'val' => $subgroupTotals['treat']],
        ['name' => '5. ค่าบริการส่งต่อ / รถพยาบาล', 'col' => 'AMB', 'val' => $subgroupTotals['car']],
        ['name' => '6. กรณีพักรอการจำหน่าย', 'col' => 'WAIT', 'val' => $subgroupTotals['wait']],
        ['name' => '7. ค่าบริการทางการแพทย์อื่นๆ / On-top LGO', 'col' => 'Col AS (44)', 'val' => $subgroupTotals['ontop']],
    ];

    return [
        'status' => 'success',
        'format_type' => 'LGO_ECLAIM',
        'metadata' => $meta,
        'subgroup_totals' => $subgroupTotals,
        'display_categories' => $displayCategories,
        'rep_summaries' => array_values($repSummaries),
        'total_cases' => count($patients),
        'normal_cases' => count($patients),
        'appeal_cases' => 0,
        'patients' => $patients
    ];
}

/**
 * 4. Format Type D: SSS XML ZIP Parser (SIGN/SOGN)
 */
function parse_stm_sss_zip($filePath, $expectedFund = 'SSS', $expectedHospcode = '') {
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return ['status' => 'error', 'message' => 'ไม่สามารถเปิดไฟล์ ZIP ได้'];
    }

    $isSign = false;
    $isSogn = false;
    $xmlSignName = '';
    $xmlSognName = '';
    $xmlSumsName = '';

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('/SIGNSTMS.*\.xml$/i', $name)) {
            $isSign = true;
            $xmlSignName = $name;
        } elseif (preg_match('/SIGNSUMS.*\.xml$/i', $name)) {
            $xmlSumsName = $name;
        } elseif (preg_match('/SOGNSTMP.*\.xml$/i', $name) || preg_match('/SOGNSTM.*\.xml$/i', $name)) {
            $isSogn = true;
            $xmlSognName = $name;
        }
    }

    if (!$isSign && !$isSogn) {
        $zip->close();
        return ['status' => 'error', 'message' => 'ไม่พบไฟล์ XML ของประกันสังคม (SIGN หรือ SOGN) ในไฟล์ ZIP'];
    }

    $patients = [];
    $totalComp = 0.0;
    $totalColl = 0.0;

    $subgroupTotals = [
        'room' => 0.0, 'inst' => 0.0, 'drug' => 0.0, 'treat' => 0.0, 'car' => 0.0, 'wait' => 0.0, 'other' => 0.0,
        'op' => 0.0, 'ip' => 0.0, 'hc' => 0.0, 'ae' => 0.0, 'dmis' => 0.0, 'ontop' => 0.0,
        'pallativecare' => 0.0, 'dmishd' => 0.0, 'pp' => 0.0, 'fs' => 0.0, 'opbkk' => 0.0,
        'total_comp' => 0.0, 'total_collected' => 0.0
    ];

    if ($isSign) {
        // IPD ประกันสังคม (SIGN)
        $sumsXml = null;
        if (!empty($xmlSumsName)) {
            $sumsXml = simplexml_load_string($zip->getFromName($xmlSumsName));
        }
        $stmsXml = simplexml_load_string($zip->getFromName($xmlSignName));
        $zip->close();

        if (!$stmsXml) {
            return ['status' => 'error', 'message' => 'ไม่สามารถอ่านไฟล์ XML ผู้ป่วยในประกันสังคมได้'];
        }

        $docno = (string)($sumsXml->stmno ?? '11072_SIGNSTM_' . ($sumsXml->period ?? ''));
        $hcode = (string)($sumsXml->hcode ?? $stmsXml->stmdat->hcode ?? '');
        $hname = (string)($sumsXml->hname ?? '');
        $period = (string)($sumsXml->period ?? '');

        if (!empty($expectedHospcode) && !empty($hcode) && trim($hcode) !== trim($expectedHospcode)) {
            return [
                'status' => 'error',
                'message' => "❌ ปฏิเสธการนำเข้า: รหัสโรงพยาบาลในไฟล์ [{$hcode}] ไม่ตรงกับ [{$expectedHospcode}]"
            ];
        }

        $rep = 'SIGN_' . $period;
        $bills = $stmsXml->Bills->Bill ?? [];
        $seq = 1;
        foreach ($bills as $b) {
            $an = trim((string)$b->an);
            $hn = trim((string)$b->hn);
            $pid = trim((string)$b->pid);
            $name = trim((string)$b->name);
            $admDate = normalize_stm_date(trim((string)$b->dateadm));
            $dchDate = normalize_stm_date(trim((string)$b->datedsc));
            $reimb = (float)$b->Reimb;

            $patients[] = [
                'no' => $seq++,
                'rep' => $rep,
                'id' => $an,
                'vn' => $an, // Rule 109: IPD uses AN
                'an' => $an,
                'pid' => $pid,
                'hn' => $hn,
                'ptname' => $name,
                'admdate' => $admDate,
                'dchdate' => $dchDate,
                'projcode' => (string)($b->drg ?? ''),
                'adjrw' => (float)($b->adjrw ?? 0),
                'collected' => $reimb,
                'compensated' => $reimb,
                'room' => 0.0, 'inst' => 0.0, 'drug' => 0.0,
                'op' => 0.0, 'ip' => $reimb,
                'hc' => 0.0, 'ae' => 0.0, 'dmis' => 0.0, 'ontop' => 0.0,
                'pallativecare' => 0.0, 'dmishd' => 0.0, 'pp' => 0.0, 'fs' => 0.0, 'opbkk' => 0.0,
                'treat' => $reimb, 'car' => 0.0, 'wait' => 0.0, 'other' => 0.0,
                'paidtype' => (string)($b->ptype ?? ''),
                'is_appeal' => 0
            ];
            $totalComp += $reimb;
            $totalColl += $reimb;
        }

        $subgroupTotals['total_comp'] = $totalComp;
        $subgroupTotals['total_collected'] = $totalColl;
        $subgroupTotals['ip'] = $totalComp;
        $subgroupTotals['treat'] = $totalComp;

        $displayCategories = [
            ['name' => '1. ค่าห้อง / ค่าอาหาร', 'col' => 'ROOM', 'val' => 0.0],
            ['name' => '2. ค่าอวัยวะเทียมและอุปกรณ์ในการบำบัดรักษาโรค', 'col' => 'INST', 'val' => 0.0],
            ['name' => '3. ค่ายา สารอาหารทางเส้นเลือด และก๊าซทางการแพทย์', 'col' => 'DRUG', 'val' => 0.0],
            ['name' => '4. ค่าบริการผู้ป่วยในเหมาจ่ายตาม DRG (ประกันสังคม)', 'col' => 'DRG Reimb', 'val' => $totalComp],
            ['name' => '5. ค่าบริการส่งต่อ / รถพยาบาล', 'col' => 'AMB', 'val' => 0.0],
            ['name' => '6. กรณีพักรอการจำหน่าย', 'col' => 'WAIT', 'val' => 0.0],
            ['name' => '7. ค่าบริการทางการแพทย์อื่นๆ / On-top', 'col' => 'OTHER', 'val' => 0.0],
        ];

        return [
            'status' => 'success',
            'format_type' => 'NON_UC',
            'metadata' => [
                'docno' => $docno,
                'hospcode' => $hcode,
                'hospname' => $hname,
                'province' => '',
                'report_date' => '',
                'fund' => 'SSS',
                'visit_type' => 'IPD',
                'period' => $period,
                'cover_total_comp' => $totalComp
            ],
            'subgroup_totals' => $subgroupTotals,
            'display_categories' => $displayCategories,
            'rep_summaries' => [[
                'rep' => $rep,
                'case_count' => count($patients),
                'total_collected' => $totalColl,
                'total_compensated' => $totalComp,
                'is_appeal' => 0,
                'source' => 'SSS XML (SIGN)'
            ]],
            'total_cases' => count($patients),
            'normal_cases' => count($patients),
            'appeal_cases' => 0,
            'patients' => $patients
        ];

    } else {
        // OPD ประกันสังคม (SOGN)
        $xmlContent = $zip->getFromName($xmlSognName);
        $zip->close();
        $sognXml = simplexml_load_string($xmlContent);
        if (!$sognXml) {
            return ['status' => 'error', 'message' => 'ไม่สามารถอ่านไฟล์ XML ผู้ป่วยนอกประกันสังคมได้'];
        }

        $docno = (string)($sognXml->STMdoc ?? '11072_SOGNSTM_' . ($sognXml->AccPeriod ?? ''));
        $hcode = (string)($sognXml->hcode ?? '');
        $hname = (string)($sognXml->hname ?? '');
        $period = (string)($sognXml->AccPeriod ?? '');

        if (!empty($expectedHospcode) && !empty($hcode) && trim($hcode) !== trim($expectedHospcode)) {
            return [
                'status' => 'error',
                'message' => "❌ ปฏิเสธการนำเข้า: รหัสโรงพยาบาลในไฟล์ [{$hcode}] ไม่ตรงกับ [{$expectedHospcode}]"
            ];
        }

        $rep = 'SOGN_' . $period;
        $seq = 1;

        if (isset($sognXml->TBills)) {
            foreach ($sognXml->TBills as $tbills) {
                foreach ($tbills->ST as $st) {
                    foreach ($st->HG as $hg) {
                        foreach ($hg->TBill as $tb) {
                            $hn = trim((string)$tb->hn);
                            $vn = trim((string)$tb->invno);
                            $pid = trim((string)$tb->pid);
                            $name = trim((string)$tb->name);
                            $dttran = trim((string)$tb->dttran);
                            $admDate = normalize_stm_date($dttran);
                            $paid = (float)($tb->total ?? $tb->paid ?? 0);
                            $claim = (float)($tb->amount ?? $paid);

                            $patients[] = [
                                'no' => $seq++,
                                'rep' => $rep,
                                'id' => $vn,
                                'vn' => $vn,
                                'an' => '',
                                'pid' => $pid,
                                'hn' => $hn,
                                'ptname' => $name,
                                'admdate' => $admDate,
                                'dchdate' => '',
                                'projcode' => (string)($tb->payplan ?? ''),
                                'adjrw' => 0.0,
                                'collected' => $claim,
                                'compensated' => $paid,
                                'room' => 0.0, 'inst' => 0.0, 'drug' => 0.0,
                                'op' => $paid, 'ip' => 0.0,
                                'hc' => 0.0, 'ae' => 0.0, 'dmis' => 0.0, 'ontop' => 0.0,
                                'pallativecare' => 0.0, 'dmishd' => 0.0, 'pp' => 0.0, 'fs' => 0.0, 'opbkk' => 0.0,
                                'treat' => $paid, 'car' => 0.0, 'wait' => 0.0, 'other' => 0.0,
                                'paidtype' => (string)($tb->care ?? ''),
                                'is_appeal' => 0
                            ];
                            $totalComp += $paid;
                            $totalColl += $claim;
                        }
                    }
                }
            }
        }

        $subgroupTotals['total_comp'] = $totalComp;
        $subgroupTotals['total_collected'] = $totalColl;
        $subgroupTotals['op'] = $totalComp;
        $subgroupTotals['treat'] = $totalComp;

        $displayCategories = [
            ['name' => '1. ค่าห้อง / ค่าอาหาร', 'col' => 'ROOM', 'val' => 0.0],
            ['name' => '2. ค่าอวัยวะเทียมและอุปกรณ์ในการบำบัดรักษาโรค', 'col' => 'INST', 'val' => 0.0],
            ['name' => '3. ค่ายา สารอาหารทางเส้นเลือด และก๊าซทางการแพทย์', 'col' => 'DRUG', 'val' => 0.0],
            ['name' => '4. ค่าบริการผู้ป่วยนอก (ประกันสังคม SOGN)', 'col' => 'SOGN OP', 'val' => $totalComp],
            ['name' => '5. ค่าบริการส่งต่อ / รถพยาบาล', 'col' => 'AMB', 'val' => 0.0],
            ['name' => '6. กรณีพักรอการจำหน่าย', 'col' => 'WAIT', 'val' => 0.0],
            ['name' => '7. ค่าบริการทางการแพทย์อื่นๆ / On-top', 'col' => 'OTHER', 'val' => 0.0],
        ];

        return [
            'status' => 'success',
            'format_type' => 'NON_UC',
            'metadata' => [
                'docno' => $docno,
                'hospcode' => $hcode,
                'hospname' => $hname,
                'province' => '',
                'report_date' => '',
                'fund' => 'SSS',
                'visit_type' => 'OPD',
                'period' => $period,
                'cover_total_comp' => $totalComp
            ],
            'subgroup_totals' => $subgroupTotals,
            'display_categories' => $displayCategories,
            'rep_summaries' => [[
                'rep' => $rep,
                'case_count' => count($patients),
                'total_collected' => $totalColl,
                'total_compensated' => $totalComp,
                'is_appeal' => 0,
                'source' => 'SSS XML (SOGN)'
            ]],
            'total_cases' => count($patients),
            'normal_cases' => count($patients),
            'appeal_cases' => 0,
            'patients' => $patients
        ];
    }
}

/**
 * ฟังก์ชันประมวลผลไฟล์ Statement สปสช. รูปแบบ Individual Sheet (Fee Schedule / บริการเฉพาะ / CR / Walk-in)
 * รองรับไฟล์ Excel รายงานสรุปการขอเบิกชดเชยค่าการรักษาผู้ป่วยของหน่วยบริการ (มีแผ่นงาน "Individual" แผ่นเดียว)
 *
 * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
 * @param string $expectedFund
 * @param string $expectedHospcode
 * @return array
 */
function parse_stm_individual_sheet($spreadsheet, $expectedFund = 'UCS', $expectedHospcode = '') {
    $sheet = $spreadsheet->getSheetByName('Individual');
    if (!$sheet) {
        // Fallback หาชีตที่ชื่อมีคำว่า Individual
        foreach ($spreadsheet->getSheetNames() as $sn) {
            if (strcasecmp(trim($sn), 'Individual') === 0) {
                $sheet = $spreadsheet->getSheetByName($sn);
                break;
            }
        }
    }
    if (!$sheet) {
        return ['status' => 'error', 'message' => 'ไม่พบแผ่นงาน "Individual"'];
    }

    $meta = [
        'format_type' => 'INDIVIDUAL_CR',
        'docno' => '',
        'hospcode' => '',
        'hospname' => '',
        'province' => '',
        'report_date' => '',
        'fund' => $expectedFund,
        'visit_type' => 'OPD',
        'period' => '',
        'accountcode_sp' => ''
    ];

    $highestRow = $sheet->getHighestRow();

    // 1. อ่านข้อมูลหัวรายงานจากแถวที่ 2 ถึง 7
    for ($r = 2; $r <= 7; $r++) {
        $bVal = trim((string)$sheet->getCell("B$r")->getValue());
        $cVal = trim((string)$sheet->getCell("C$r")->getValue());
        if (mb_strpos($bVal, 'วันที่จัดพิมพ์') !== false) {
            $meta['report_date'] = $cVal;
        } elseif (mb_strpos($bVal, 'รหัสหน่วยบริการ') !== false) {
            $meta['hospcode'] = $cVal;
        } elseif (mb_strpos($bVal, 'ชื่อหน่วยบริการ') !== false) {
            $meta['hospname'] = $cVal;
        } elseif (mb_strpos($bVal, 'งวด') !== false) {
            $meta['docno'] = $cVal;
        } elseif (mb_strpos($bVal, 'รหัสผังบัญชี') !== false) {
            $meta['accountcode_sp'] = $cVal;
        }
    }

    if (!empty($meta['docno'])) {
        $docParts = explode('_', $meta['docno']);
        $meta['period'] = $docParts[0] ?? '';
        if (isset($docParts[1])) {
            $sec = strtoupper($docParts[1]);
            $meta['visit_type'] = (strpos($sec, 'IP') === 0) ? 'IPD' : 'OPD';
        }
    }

    // ตรวจสอบความถูกต้องของรหัสโรงพยาบาล (Hospital Code Enforcement)
    if (!empty($expectedHospcode) && !empty($meta['hospcode'])) {
        if (trim($meta['hospcode']) !== trim($expectedHospcode)) {
            return [
                'status' => 'error',
                'message' => "❌ ปฏิเสธการนำเข้า: ไฟล์นี้เป็นของหน่วยบริการ [{$meta['hospcode']} {$meta['hospname']}] ไม่ตรงกับรหัสโรงพยาบาลในระบบ eDHS ของท่าน [{$expectedHospcode}]"
            ];
        }
    }

    $subgroupTotals = [
        'room' => 0.0, 'inst' => 0.0, 'drug' => 0.0, 'treat' => 0.0, 'car' => 0.0, 'wait' => 0.0, 'other' => 0.0,
        'op' => 0.0, 'ip' => 0.0, 'hc' => 0.0, 'ae' => 0.0, 'dmis' => 0.0, 'ontop' => 0.0,
        'pallativecare' => 0.0, 'dmishd' => 0.0, 'pp' => 0.0, 'fs' => 0.0, 'opbkk' => 0.0,
        'total_comp' => 0.0, 'total_collected' => 0.0
    ];

    $catBreakdown = [
        'HERB' => 0.0,
        'TELEMED' => 0.0,
        'WALKIN' => 0.0,
        'DRUG_DELIVERY' => 0.0,
        'AE' => 0.0,
        'INST' => 0.0,
        'PP' => 0.0,
        'OTHER' => 0.0
    ];

    $patients = [];
    $repSummaries = [];

    // 2. อ่านข้อมูลคนไข้รายแถว ตั้งแต่แถวที่ 9 เป็นต้นไป
    for ($r = 9; $r <= $highestRow; $r++) {
        $seq = $sheet->getCell("A$r")->getValue();
        if (!is_numeric($seq)) continue;

        $transferDate = trim((string)$sheet->getCell("B$r")->getValue());
        $hn = trim((string)$sheet->getCell("C$r")->getValue());
        $an = trim((string)$sheet->getCell("D$r")->getValue());
        $typeText = trim((string)$sheet->getCell("E$r")->getValue());
        $cid = trim((string)$sheet->getCell("F$r")->getValue());
        $ptname = trim((string)$sheet->getCell("G$r")->getValue());
        $admdate = trim((string)$sheet->getCell("H$r")->getValue());
        $comp = (float)$sheet->getCell("I$r")->getValue();
        $rep = trim((string)$sheet->getCell("J$r")->getValue());
        $mainFund = trim((string)$sheet->getCell("K$r")->getValue());
        $subFund = trim((string)$sheet->getCell("L$r")->getValue());
        $detail = trim((string)$sheet->getCell("M$r")->getValue());
        $hsend = trim((string)$sheet->getCell("N$r")->getValue());
        $hcode = trim((string)$sheet->getCell("O$r")->getValue());
        $seqNo = trim((string)$sheet->getCell("P$r")->getValue()); // VN 12 หลัก
        $vn = !empty($seqNo) ? $seqNo : '';

        // แยกหมวดเงินชดเชย
        $fs = 0.0; $hc = 0.0; $ae = 0.0; $inst = 0.0; $drug = 0.0;
        $op = 0.0; $pp = 0.0; $pallativecare = 0.0;

        $upperSub = strtoupper($subFund);
        $catKey = 'OTHER';
        if (strpos($upperSub, 'HERB') !== false) {
            $fs = $comp;
            $drug = $comp;
            $catBreakdown['HERB'] += $comp;
            $catKey = 'HERB';
        } elseif (strpos($upperSub, 'TELE') !== false) {
            $op = $comp;
            $hc = $comp;
            $catBreakdown['TELEMED'] += $comp;
            $catKey = 'TELEMED';
        } elseif (strpos($upperSub, 'WALKIN') !== false) {
            $hc = $comp;
            $catBreakdown['WALKIN'] += $comp;
            $catKey = 'WALKIN';
        } elseif (strpos($upperSub, 'DELIVERY') !== false) {
            $drug = $comp;
            $catBreakdown['DRUG_DELIVERY'] += $comp;
            $catKey = 'DRUG_DELIVERY';
        } elseif (strpos($upperSub, 'AE') !== false) {
            $ae = $comp;
            $catBreakdown['AE'] += $comp;
            $catKey = 'AE';
        } elseif (strpos($upperSub, 'INST') !== false) {
            $inst = $comp;
            $catBreakdown['INST'] += $comp;
            $catKey = 'INST';
        } elseif (strpos($upperSub, 'PP_') !== false || strpos($upperSub, 'ANC') !== false || strpos($upperSub, 'FP') !== false) {
            $pp = $comp;
            $catBreakdown['PP'] += $comp;
            $catKey = 'PP';
        } elseif (strpos($upperSub, 'PALLIATIVE') !== false) {
            $pallativecare = $comp;
            $catBreakdown['OTHER'] += $comp;
            $catKey = 'OTHER';
        } else {
            $catBreakdown['OTHER'] += $comp;
            $catKey = 'OTHER';
        }

        $subgroupTotals['fs'] += $fs;
        $subgroupTotals['hc'] += $hc;
        $subgroupTotals['ae'] += $ae;
        $subgroupTotals['inst'] += $inst;
        $subgroupTotals['drug'] += $drug;
        $subgroupTotals['op'] += $op;
        $subgroupTotals['pp'] += $pp;
        $subgroupTotals['pallativecare'] += $pallativecare;
        $subgroupTotals['total_comp'] += $comp;
        $subgroupTotals['total_collected'] += $comp;

        $patients[] = [
            'no' => $seq,
            'rep' => $rep,
            'docno' => $meta['docno'],
            'id' => $seqNo,
            'vn' => $vn,
            'an' => $an,
            'hn' => $hn,
            'pid' => $cid,
            'ptname' => $ptname,
            'admdate' => $admdate,
            'dchdate' => ($meta['visit_type'] === 'IPD') ? $admdate : '',
            'fund' => 'UCS',
            'projcode' => $subFund,
            'subfund' => $subFund,
            'detail' => $detail,
            'collected' => $comp,
            'compensated' => $comp,
            'debit' => 0.0,
            'diff' => 0.0,
            'hc' => $hc,
            'ae' => $ae,
            'inst' => $inst,
            'ip' => 0.0,
            'dmis' => 0.0,
            'op' => $op,
            'drug' => $drug,
            'ontop' => 0.0,
            'pallativecare' => $pallativecare,
            'dmishd' => 0.0,
            'pp' => $pp,
            'fs' => $fs,
            'opbkk' => 0.0,
            'paidtype' => '',
            'cat_key' => $catKey,
            'is_appeal' => 0,
            'transfer_date' => $transferDate
        ];

        if (!isset($repSummaries[$rep])) {
            $repSummaries[$rep] = [
                'rep' => $rep,
                'case_count' => 0,
                'total_collected' => 0.0,
                'total_compensated' => 0.0,
                'is_appeal' => 0,
                'source' => 'สปสช. กองทุนบริการเฉพาะ (Fee Schedule)'
            ];
        }
        $repSummaries[$rep]['case_count']++;
        $repSummaries[$rep]['total_collected'] += $comp;
        $repSummaries[$rep]['total_compensated'] += $comp;
    }

    $displayCategories = [
        ['name' => '1. ยาสมุนไพร / การแพทย์แผนไทย (HERB_FS)', 'col' => 'HERB', 'val' => $catBreakdown['HERB']],
        ['name' => '2. บริการการแพทย์ทางไกล (TELEMED)', 'col' => 'TELE', 'val' => $catBreakdown['TELEMED']],
        ['name' => '3. บริการกรณีเหตุสมควร (Walk-In)', 'col' => 'WALKIN', 'val' => $catBreakdown['WALKIN']],
        ['name' => '4. ค่าบริการจัดส่งยาทางไปรษณีย์', 'col' => 'DELIVERY', 'val' => $catBreakdown['DRUG_DELIVERY']],
        ['name' => '5. อุบัติเหตุฉุกเฉิน Fee Schedule (OPAE/CARAE)', 'col' => 'AE', 'val' => $catBreakdown['AE']],
        ['name' => '6. ค่าอุปกรณ์และอวัยวะเทียม (OPINST)', 'col' => 'INST', 'val' => $catBreakdown['INST']],
        ['name' => '7. ส่งเสริมสุขภาพและป้องกันโรค (PP/ANC/FP)', 'col' => 'PP', 'val' => $catBreakdown['PP']],
        ['name' => '8. บริการเฉพาะอื่นๆ (Palliative Care / Clopidogrel)', 'col' => 'OTHER', 'val' => $catBreakdown['OTHER']],
    ];

    return [
        'status' => 'success',
        'format_type' => 'INDIVIDUAL_CR',
        'metadata' => $meta,
        'subgroup_totals' => $subgroupTotals,
        'display_categories' => $displayCategories,
        'rep_summaries' => array_values($repSummaries),
        'total_cases' => count($patients),
        'normal_cases' => count($patients),
        'appeal_cases' => 0,
        'patients' => $patients
    ];
}

/**
 * Main Dispatcher: parse_nhso_stm_workbook
 * ตรวจสอบความถูกต้องและอ่านโครงสร้างไฟล์ Statement สปสช. และ Non-UC (UCS, OFC, LGO, BKK, SRT, SSS)
 * 
 * @param string $filePath เส้นทางไฟล์ Excel หรือ ZIP ชั่วคราว
 * @param string $expectedFund สิทธิที่ผู้ใช้เลือกในระบบ (เช่น 'UCS', 'OFC', 'SSS', 'LGO', 'BKK', 'SRT')
 * @param string $expectedHospcode รหัสหน่วยบริการของโรงพยาบาล
 * @return array ข้อมูล metadata, สรุปรายหมวด, สรุปราย REP, และรายชื่อผู้ป่วยทั้งหมด
 */
function parse_nhso_stm_workbook($filePath, $expectedFund = 'UCS', $expectedHospcode = '', $forcedServiceType = 'AUTO') {
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'message' => 'ไม่พบไฟล์ที่ระบุในเซิร์ฟเวอร์'];
    }

    ini_set('memory_limit', '512M');
    set_time_limit(300);

    $file_ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // ตรวจสอบกรณีไฟล์ ZIP (ประกันสังคม)
    if ($file_ext === 'zip') {
        return parse_stm_sss_zip($filePath, $expectedFund, $expectedHospcode);
    }

    try {
        $readerType = ($file_ext === 'xlsx') ? 'Xlsx' : 'Xls';
        $reader = IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($filePath);

        $sheetNames = $spreadsheet->getSheetNames();

        foreach ($sheetNames as $sn) {
            $trimmed = trim($sn);
            // 1. ตรวจพบแผ่นงานของ สปสช. (UCS 4 sheets)
            if (mb_strpos($trimmed, 'รายละเอียด(ข้อมูลปกติ)') !== false || mb_strpos($trimmed, 'ข้อมูลปกติ') !== false) {
                $res = parse_stm_ucs_4sheet($spreadsheet, $expectedFund, $expectedHospcode);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $reader);
                if ($res['status'] === 'success') {
                    $is_cr = ($forcedServiceType === 'CR') || ($forcedServiceType === 'AUTO' && (
                        preg_match('/(OPUCS|IPUCS).*_02/i', $res['metadata']['docno'] ?? '') !== 0 ||
                        preg_match('/_02(\.|\b)/i', basename($filePath)) !== 0
                    ));
                    if ($is_cr) {
                        $res['format_type'] = 'INDIVIDUAL_CR';
                        $res['metadata']['is_cr'] = true;
                        $res['metadata']['format_type'] = 'INDIVIDUAL_CR';
                    }
                }
                return $res;
            }
            // 2. ตรวจพบแผ่นงานของ Non-UC มาตรฐาน (BKK, OFC, SRT 2 sheets)
            if ($trimmed === 'พึงรับ') {
                $res = parse_stm_non_uc_2sheet($spreadsheet, $expectedFund, $expectedHospcode);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $reader);
                return $res;
            }
            // 3. ตรวจพบแผ่นงานของ e-Claim อปท. (LGO e-Claim)
            if ($trimmed === 'Detail') {
                $res = parse_stm_lgo_eclaim($spreadsheet, $expectedFund, $expectedHospcode);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $reader);
                return $res;
            }
            // 4. ตรวจพบแผ่นงาน Individual ของ สปสช. (รายงานสรุปการขอเบิกชดเชยค่ารักษาพยาบาล Fee Schedule / CR / Walk-in)
            if (strcasecmp($trimmed, 'Individual') === 0) {
                $res = parse_stm_individual_sheet($spreadsheet, $expectedFund, $expectedHospcode);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $reader);
                return $res;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader);
        return [
            'status' => 'error',
            'message' => 'รูปแบบไฟล์ไม่ถูกต้อง: ไม่พบแผ่นงาน Statement ที่ระบบรองรับ (ข้อมูลปกติ, พึงรับ, Detail, หรือ Individual)'
        ];

    } catch (\Throwable $e) {
        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการประมวลผลไฟล์: ' . $e->getMessage()
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
        $escaped_reps = array_map(function($r) use ($conn) { return "'" . mysqli_real_escape_string($conn, $r) . "'"; }, array_unique($reps));
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
 * Smart Multi-Strategy Debtor Matching & Disambiguation Engine
 * 
 * ค้นหาและจับคู่ลูกหนี้ eDHS อย่างแม่นยำ 100%
 * 1. ตรวจสอบ SEQ NO ว่าเป็น VN 12 หลักตรงตัวหรือไม่ (Priority 1: รพ.ทั่วไป / รพ.โพนทราย)
 * 2. ตรวจสอบ HOSxP ovst_seq (ถ้ามี conn2) เพื่อแปลง seq_id (7 หลัก) -> vn
 * 3. Fallback: แปลงวันเข้ารักษา (Col 8) เป็น VN Prefix 10 หลัก (YYMMDDHHMM%) + HN
 * 4. Fallback: จับคู่ด้วย วันที่รับบริการ (vstdate) + HN พร้อมคัดกรองเคสหลาย Visit (Multi-Visit Disambiguation)
 * 5. Consumed VN Lock: ป้องกันไม่ให้คนไข้ที่มีหลาย Visit ในวันเดียวกันหยิบ VN ตัวเดียวกันซ้ำ
 * 6. ปรับปรุงค่า $p['vn'] เป็นเลข VN 12 หลักจริงที่ค้นพบจากระบบลูกหนี้
 * 
 * @param mysqli $conn
 * @param array &$patients
 * @param string $visitType
 * @return array ['debtor_map' => array, 'matched_count' => int, 'unmatched_count' => int]
 */
function resolve_stm_debtor_matches($conn, &$patients, $visitType = 'OPD', $formatType = 'UC') {
    global $conn2;
    $is_cr_file = (strpos($formatType, 'INDIVIDUAL') !== false || strpos($formatType, 'CR') !== false);
    $matched_debtor_map = [];
    $matched_count = 0;
    $consumed_vns = [];

    if (empty($patients)) {
        return [
            'debtor_map' => [],
            'matched_count' => 0,
            'unmatched_count' => 0
        ];
    }

    // 1. จำแนกและรวบรวมตัวระบุตัวตน (Identifiers)
    $all_vns = [];
    $all_hns = [];
    $all_dates = [];
    $seq_ids_to_check = [];

    foreach ($patients as $idx => $p) {
        $vn = trim(strval($p['vn'] ?? ''));
        $hn = trim(strval($p['hn'] ?? ''));
        $adm = trim(strval($p['admdate'] ?? ''));
        $dateOnly = substr($adm, 0, 10);

        if (!empty($vn)) {
            $all_vns[] = $vn;
            if (is_numeric($vn) && strlen($vn) < 12) {
                $seq_ids_to_check[] = (int)$vn;
            }
        }
        if (!empty($hn)) {
            $all_hns[] = $hn;
            $all_hns[] = ltrim($hn, '0');
        }
        if (!empty($dateOnly)) {
            $all_dates[] = $dateOnly;
        }
    }

    $all_vns = array_unique($all_vns);
    $all_hns = array_unique(array_filter($all_hns));
    $all_dates = array_unique($all_dates);
    $seq_ids_to_check = array_unique($seq_ids_to_check);

    // 2. ตรวจสอบ HOSxP ovst_seq (ถ้ามี conn2 และมี SEQ NO ที่ไม่ใช่ VN 12 หลัก)
    $seq_to_vn = [];
    if (isset($conn2) && $conn2 && !empty($seq_ids_to_check) && $visitType === 'OPD') {
        $seq_chunks = array_chunk($seq_ids_to_check, 1000);
        foreach ($seq_chunks as $schunk) {
            $slist = implode(',', $schunk);
            $qSeq = $conn2->query("SELECT seq_id, vn FROM ovst_seq WHERE seq_id IN ($slist)");
            if ($qSeq) {
                while ($sqr = $qSeq->fetch_assoc()) {
                    $seq_to_vn[(string)$sqr['seq_id']] = (string)$sqr['vn'];
                    $all_vns[] = (string)$sqr['vn'];
                }
            }
        }
        $all_vns = array_unique($all_vns);
    }

    // 3. Batch Query ลูกหนี้จากฐานข้อมูล eDHS (im_report)
    $debtor_by_vn = [];
    $debtor_by_hn_prefix = [];
    $debtor_by_hn_date = [];

    if ($visitType === 'IPD') {
        if (!empty($all_vns)) {
            $vn_chunks = array_chunk($all_vns, 1000);
            foreach ($vn_chunks as $chunk) {
                $escaped_arr = [];
                foreach ($chunk as $v) {
                    $escaped_arr[] = $conn->real_escape_string($v);
                }
                $escaped = "'" . implode("','", $escaped_arr) . "'";
                $q = $conn->query("SELECT an as vn, debit, accountcode, accountname, pttypename, pttype_eclaim_name, dchdate as sdate FROM imr_tb_debtor_rights_ipd WHERE an IN ($escaped)");
                if ($q) {
                    while ($r = $q->fetch_assoc()) {
                        $debtor_by_vn[$r['vn']] = $r;
                    }
                }
            }
        }
    } else {
        // ฝั่ง OPD: ดึงทั้งจาก VN ตรง และ Candidate จาก HN + vstdate
        // 3.1 ดึงจาก VN ตรง
        if (!empty($all_vns)) {
            $vn_chunks = array_chunk($all_vns, 1000);
            foreach ($vn_chunks as $chunk) {
                $escaped_arr = [];
                foreach ($chunk as $v) {
                    $escaped_arr[] = $conn->real_escape_string($v);
                }
                $escaped = "'" . implode("','", $escaped_arr) . "'";
                $q = $conn->query("SELECT vn, hn, debit, accountcode, accountname, pttypename, pttype_eclaim_name, vstdate as sdate, vstdate, vsttime, mobile, bill FROM imr_tb_debtor_rights_opd WHERE vn IN ($escaped)");
                if ($q) {
                    while ($r = $q->fetch_assoc()) {
                        $vn = $r['vn'];
                        $debtor_by_vn[$vn] = $r;
                        $cleanHn = ltrim($r['hn'], '0');
                        if (strlen($vn) >= 10) {
                            $debtor_by_hn_prefix[$cleanHn][substr($vn, 0, 10)][] = $r;
                        }
                        $debtor_by_hn_date[$cleanHn][$r['vstdate']][] = $r;
                    }
                }
            }
        }

        // 3.2 ดึง Candidate เพิ่มเติมจาก HN + vstdate เพื่อรองรับกรณีที่ SEQ NO ไม่ใช่ VN
        if (!empty($all_hns) && !empty($all_dates)) {
            $hn_chunks = array_chunk($all_hns, 500);
            $escaped_dates = [];
            foreach ($all_dates as $d) {
                $escaped_dates[] = $conn->real_escape_string($d);
            }
            $date_list = "'" . implode("','", $escaped_dates) . "'";
            foreach ($hn_chunks as $hchunk) {
                $escaped_hns = [];
                foreach ($hchunk as $h) {
                    $escaped_hns[] = $conn->real_escape_string($h);
                }
                $hn_list = "'" . implode("','", $escaped_hns) . "'";
                $sql = "SELECT vn, hn, debit, accountcode, accountname, pttypename, pttype_eclaim_name, vstdate as sdate, vstdate, vsttime, mobile, bill 
                        FROM imr_tb_debtor_rights_opd 
                        WHERE (hn IN ($hn_list) OR CAST(hn AS UNSIGNED) IN ($hn_list))
                          AND vstdate IN ($date_list)";
                $q = $conn->query($sql);
                if ($q) {
                    while ($r = $q->fetch_assoc()) {
                        $vn = $r['vn'];
                        if (!isset($debtor_by_vn[$vn])) {
                            $debtor_by_vn[$vn] = $r;
                        }
                        $cleanHn = ltrim($r['hn'], '0');
                        if (strlen($vn) >= 10) {
                            $debtor_by_hn_prefix[$cleanHn][substr($vn, 0, 10)][] = $r;
                        }
                        $debtor_by_hn_date[$cleanHn][$r['vstdate']][] = $r;
                    }
                }
            }
        }
    }

    // 3.3 ดึงข้อมูล CR Breakdown (รองรับทั้ง OPD 1102050101.216 และ IPD 1102050101.217)
    $cr_breakdown_by_vn = [];
    if (!empty($all_vns)) {
        $vn_chunks = array_chunk($all_vns, 1000);
        foreach ($vn_chunks as $chunk) {
            $escaped_arr = [];
            foreach ($chunk as $v) {
                $escaped_arr[] = $conn->real_escape_string($v);
            }
            $escaped = "'" . implode("','", $escaped_arr) . "'";
            $qCr = $conn->query("SELECT vn, cr_type, target_accountcode, parent_accountcode, item_amount, general_remain_amount, bill, billdate, settle_status FROM imr_tb_debtor_cr_breakdown WHERE vn IN ($escaped) AND visit_type = '$visitType'");
            if ($qCr) {
                while ($cr = $qCr->fetch_assoc()) {
                    $cr_breakdown_by_vn[$cr['vn']][] = $cr;
                }
            }
        }
    }

    // 4. Waterfall Matching Engine พร้อม Consumed VN Lock
    foreach ($patients as $idx => &$p) {
        $matched = null;
        $origVn = trim(strval($p['vn'] ?? ''));
        $cleanHn = ltrim(trim(strval($p['hn'] ?? '')), '0');
        $adm = trim(strval($p['admdate'] ?? ''));
        $dateOnly = substr($adm, 0, 10);
        $claim = (float)($p['collected'] ?? 0);
        $compVal = (float)($p['compensated'] ?? 0);

        // กฎความปลอดภัย CR Statement: ไม่ข้ามรายการที่ยอดชดเชย 0.00 บาท เนื่องจากได้รับการพิจารณาชดเชย 0.00 บาทจริงตาม Statement


        // สกัด Prefix 10 หลัก (YYMMDDHHMM) จาก admdate เช่น "31/07/2569 09:44:00"
        $prefix10 = '';
        $vstHhmm = '';
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s*(\d{1,2}):(\d{1,2})/', $adm, $dm)) {
            $d = str_pad($dm[1], 2, '0', STR_PAD_LEFT);
            $mo = str_pad($dm[2], 2, '0', STR_PAD_LEFT);
            $y = (int)$dm[3];
            $hh = str_pad($dm[4], 2, '0', STR_PAD_LEFT);
            $mi = str_pad($dm[5], 2, '0', STR_PAD_LEFT);
            $yShort = substr((string)$y, -2);
            $prefix10 = $yShort . $mo . $d . $hh . $mi;
            $vstHhmm = "$hh:$mi";
        }

        // Strategy 1: Map จาก HOSxP ovst_seq (ถ้ามี)
        if (isset($seq_to_vn[$origVn])) {
            $mappedVn = $seq_to_vn[$origVn];
            if (isset($debtor_by_vn[$mappedVn]) && !isset($consumed_vns[$mappedVn])) {
                $matched = $debtor_by_vn[$mappedVn];
            }
        }

        // Strategy 2: Priority 1 - Exact VN Match (สำหรับกรณีปกติ เช่น รพ.โพนทราย)
        // อนุญาตให้หลายรายการของ Visit เดียวกันใน STM แมตช์ได้โดยไม่ถูก Consumed VN ล็อก
        if (!$matched && isset($debtor_by_vn[$origVn])) {
            $matched = $debtor_by_vn[$origVn];
        }

        // Strategy 3: Fallback - Time Prefix 10 หลัก (YYMMDDHHMM%) + HN
        if (!$matched && $visitType === 'OPD' && !empty($prefix10) && isset($debtor_by_hn_prefix[$cleanHn][$prefix10])) {
            $cands = array_filter($debtor_by_hn_prefix[$cleanHn][$prefix10], function($c) use ($consumed_vns) {
                return !isset($consumed_vns[$c['vn']]);
            });
            if (count($cands) === 1) {
                $matched = reset($cands);
            } elseif (count($cands) > 1) {
                // ถ้ามีเวลาตรงกันในนาทีเดียวกัน ให้เลือกยอดเงินที่ใกล้เคียงที่สุด
                usort($cands, function($a, $b) use ($claim) {
                    $diffA = abs((float)$a['debit'] - $claim);
                    $diffB = abs((float)$b['debit'] - $claim);
                    if ($diffA == $diffB) return 0;
                    return ($diffA < $diffB) ? -1 : 1;
                });
                $matched = $cands[0];
            }
        }

        // Strategy 4: Fallback - vstdate + HN พร้อม Disambiguation เวลาและยอดเงิน
        if (!$matched && $visitType === 'OPD' && !empty($dateOnly) && isset($debtor_by_hn_date[$cleanHn][$dateOnly])) {
            $cands = array_filter($debtor_by_hn_date[$cleanHn][$dateOnly], function($c) use ($consumed_vns) {
                return !isset($consumed_vns[$c['vn']]);
            });
            if (count($cands) === 1) {
                $matched = reset($cands);
            } elseif (count($cands) > 1) {
                // คัดเลือก Visit ที่ใกล้เคียงเวลาและยอดเงินที่สุด
                $targetTime = !empty($vstHhmm) ? $vstHhmm . ':00' : '12:00:00';
                usort($cands, function($a, $b) use ($targetTime, $claim) {
                    $tA = !empty($a['vsttime']) ? strtotime($a['vsttime']) : 0;
                    $tB = !empty($b['vsttime']) ? strtotime($b['vsttime']) : 0;
                    $tTarget = strtotime($targetTime);
                    $diffA = abs($tA - $tTarget);
                    $diffB = abs($tB - $tTarget);
                    if ($diffA !== $diffB) {
                        return ($diffA < $diffB) ? -1 : 1;
                    }
                    $diffAmtA = abs((float)$a['debit'] - $claim);
                    $diffAmtB = abs((float)$b['debit'] - $claim);
                    if ($diffAmtA == $diffAmtB) return 0;
                    return ($diffAmtA < $diffAmtB) ? -1 : 1;
                });
                $matched = $cands[0];
            }
        }

        // เมื่อจับคู่ได้: บันทึก Consumed Lock และปรับปรุงค่า VN เป็น 12 หลักจริง
        if ($matched) {
            $realVn = $matched['vn'];
            $consumed_vns[$realVn] = true;
            $p['vn'] = $realVn;
            $p['is_matched'] = 1;
            $matched_count++;

            $target_cr_acc = ($visitType === 'IPD') ? '1102050101.217' : '1102050101.216';
            $target_cr_name = ($visitType === 'IPD') ? 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)' : 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';

            if ($visitType === 'IPD' && !$is_cr_file) {
                // สำหรับ IPD ปกติ: ตรวจสอบว่ามีบันทึกใน imr_tb_debtor_cr_breakdown หรือไม่
                if (isset($cr_breakdown_by_vn[$realVn])) {
                    $matched['cr_breakdown'] = $cr_breakdown_by_vn[$realVn];
                    $p['has_cr_breakdown'] = 1;
                }
                $p['paidtype'] = 'READY_NORMAL';
                $p['is_already_billed'] = 0;
            } elseif ($is_cr_file) {
                // ตรวจสอบกรณีไฟล์ Statement บริการเฉพาะ (CR / Fee Schedule: format_type === 'INDIVIDUAL' หรือ 'INDIVIDUAL_CR')
                $compVal = (float)($p['compensated'] ?? 0);
                $has_cr_wait = false;
                $cr_wait_item_amount = 0.0;
                if (isset($cr_breakdown_by_vn[$realVn])) {
                    foreach ($cr_breakdown_by_vn[$realVn] as $cr) {
                        if ($cr['settle_status'] === 'WAIT' || empty($cr['bill'])) {
                            $has_cr_wait = true;
                            $cr_wait_item_amount += (float)($cr['item_amount'] ?? 0);
                        }
                    }
                }
                $is_native_cr = (strpos($matched['accountcode'], $target_cr_acc) !== false);
                // ตรวจสอบสถานะการแนบใบเสร็จ Statement: ในระบบ eDHS ลูกหนี้ OPD สถานะตัดหนี้ Statement เก็บในฟิลด์ mobile (รูปแบบ 'เลขที่/เล่มที่-วว/ดด/ปปปป')
                // ส่วนฟิลด์ bill ใน imr_tb_debtor_rights_opd เก็บเลขใบเสร็จ รพ. จาก HOSxP (เช่น 0001/0001) จึงต้องเช็คเฉพาะ mobile หรือ cr_breakdown ที่ SETTLED แล้ว
                $is_parent_billed = (!empty($matched['mobile']) && $matched['mobile'] !== '-');

                if ($has_cr_wait || ($is_native_cr && !$is_parent_billed) || (!$is_parent_billed)) {
                    $p['paidtype'] = 'READY_CR';
                    $p['is_already_billed'] = 0;
                    $matched['accountcode'] = $target_cr_acc;
                    $matched['accountname'] = $target_cr_name;
                    if ($has_cr_wait && $cr_wait_item_amount > 0) {
                        $matched['debit'] = $cr_wait_item_amount;
                    } elseif ($is_native_cr && (float)$matched['debit'] > 0) {
                        $matched['debit'] = (float)$matched['debit'];
                    } else {
                        $matched['debit'] = (float)($p['collected'] ?? $p['compensated']);
                    }
                } elseif ($is_parent_billed) {
                    $p['paidtype'] = 'ALREADY_BILLED_PARENT';
                    $p['is_already_billed'] = 1;
                    $matched['accountcode'] = $target_cr_acc;
                    $matched['accountname'] = $target_cr_name . ' (มีใบเสร็จเดิม)';
                    $matched['debit'] = 0.0;
                } else {
                    $p['paidtype'] = 'READY_CR';
                    $p['is_already_billed'] = 0;
                    $matched['accountcode'] = $target_cr_acc;
                    $matched['accountname'] = $target_cr_name;
                }
            } else {
                // กรณีไฟล์ STM ผู้ป่วยนอกปกติ (เหมาจ่าย/บริการทั่วไป 4 แผ่นงาน หรือ Non-UC)
                // ตัดหนี้เข้าผังบัญชีเดิมของคนไข้ (เช่น 1102050101.201 ใน CUP หรือ .209 นอก CUP) 100%
                $p['paidtype'] = 'READY_NORMAL';
                $p['is_already_billed'] = 0;
            }

            $matched_debtor_map[$realVn] = $matched;
        } else {
            $p['is_matched'] = 0;
            $p['paidtype'] = $is_cr_file ? 'READY_CR' : 'UNMATCHED';
            $p['is_already_billed'] = 0;
            if ($is_cr_file) {
                $target_cr_acc = ($visitType === 'IPD') ? '1102050101.217' : '1102050101.216';
                $target_cr_name = ($visitType === 'IPD') ? 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)' : 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
                $matched_debtor_map[$origVn] = [
                    'vn' => $origVn,
                    'hn' => $cleanHn,
                    'debit' => $claim > 0 ? $claim : $compVal,
                    'accountcode' => $target_cr_acc,
                    'accountname' => $target_cr_name,
                    'pttypename' => 'บัตรทอง UC (CR)',
                    'pttype_eclaim_name' => 'UC บริการเฉพาะ (CR)',
                    'bill' => '',
                    'mobile' => '-'
                ];
            }
        }
    }
    unset($p);

    $ready_cr_count = 0;
    $ready_cr_comp = 0.0;
    $zero_cr_count = 0;
    $zero_cr_claim = 0.0;
    $already_billed_count = 0;
    $already_billed_comp = 0.0;
    foreach ($patients as $pItem) {
        $cVal = (float)($pItem['compensated'] ?? 0);
        $claimVal = (float)($pItem['collected'] ?? 0);
        if (($pItem['paidtype'] ?? '') === 'READY_CR') {
            $ready_cr_count++;
            $ready_cr_comp += $cVal;
            if ($cVal <= 0.0) {
                $zero_cr_count++;
                $zero_cr_claim += $claimVal;
            }
        } elseif (($pItem['paidtype'] ?? '') === 'ALREADY_BILLED_PARENT') {
            $already_billed_count++;
            $already_billed_comp += $cVal;
        }
    }

    return [
        'debtor_map' => $matched_debtor_map,
        'matched_count' => $matched_count,
        'unmatched_count' => count($patients) - $matched_count,
        'ready_cr_count' => $ready_cr_count,
        'ready_cr_comp' => $ready_cr_comp,
        'zero_cr_count' => $zero_cr_count,
        'zero_cr_claim' => $zero_cr_claim,
        'already_billed_count' => $already_billed_count,
        'already_billed_comp' => $already_billed_comp
    ];
}

/**
 * ดำเนินการจับคู่ลูกหนี้ eDHS (Debtor Enrichment) และ Batch Insert
 */
function enrich_and_insert_stm_patients($conn, $patients, $meta, $overwrite = false) {
    if (empty($patients)) {
        return ['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้ป่วยที่จะบันทึก'];
    }

    ensure_stm_columns($conn);
    if (function_exists('ensure_cr_tables')) {
        ensure_cr_tables($conn);
    }

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
            // รับประกันความปลอดภัย: ครอบคลุมทั้ง docno เดียวกัน หรือชุดเลข REP เดียวกันที่ตกค้างเดิม (docno IS NULL หรือ '')
            $reps = array_unique(array_column($patients, 'rep'));
            $delete_conditions = [];
            if (!empty($docno)) {
                $escaped_doc = mysqli_real_escape_string($conn, $docno);
                $delete_conditions[] = "docno = '$escaped_doc'";
            }
            if (!empty($reps)) {
                $rep_list = "'" . implode("','", array_map(function($r) use ($conn) { return mysqli_real_escape_string($conn, $r); }, $reps)) . "'";
                $delete_conditions[] = "rep IN ($rep_list)";
            }

            if (!empty($delete_conditions)) {
                $where_clause = implode(" OR ", $delete_conditions);
                $q_vns = $conn->query("SELECT vn FROM imr_tb_check_invoice WHERE $where_clause");
                if ($q_vns) {
                    $upd_opd = $conn->prepare("UPDATE imr_tb_debtor_rights_opd SET mobile='-' WHERE vn = ?");
                    $upd_ipd = $conn->prepare("UPDATE imr_tb_debtor_rights_ipd SET mobile='-' WHERE an = ?");
                    $upd_cr = $conn->prepare("UPDATE imr_tb_debtor_cr_breakdown SET compensated = 0, settle_status = 'WAIT' WHERE vn = ?");
                    while ($rv = $q_vns->fetch_assoc()) {
                        if (!empty($rv['vn'])) {
                            $upd_opd->bind_param("s", $rv['vn']);
                            $upd_opd->execute();
                            $upd_ipd->bind_param("s", $rv['vn']);
                            $upd_ipd->execute();
                            if ($upd_cr) {
                                $upd_cr->bind_param("s", $rv['vn']);
                                $upd_cr->execute();
                            }
                        }
                    }
                    $upd_opd->close();
                    $upd_ipd->close();
                    if ($upd_cr) $upd_cr->close();
                }
                $conn->query("DELETE FROM imr_tb_check_invoice WHERE $where_clause");
            }
        }

        // -------------------------------------------------------------
        // Auto-Enrichment: จับคู่และฟื้นฟูเลข VN ผ่าน Multi-Strategy Matching Engine
        // -------------------------------------------------------------
        $matchResult = resolve_stm_debtor_matches($conn, $patients, $visit_type, $meta['format_type'] ?? 'UC');
        $debtor_map = $matchResult['debtor_map'];

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

        $is_cr_doc = (strpos($meta['format_type'] ?? '', 'CR') !== false || strpos($meta['format_type'] ?? '', 'INDIVIDUAL') !== false || !empty($meta['is_cr']));

        foreach ($patients as $p) {
            $comp = (float)$p['compensated'];
            $collected = (float)$p['collected'];

            // กฎความปลอดภัยตาม Grill-me: ข้ามเฉพาะเคสที่มีใบเสร็จในผังหลักแล้ว (ALREADY_BILLED_PARENT)
            // สำหรับเคสที่ชดเชย 0.00 บาท ให้นำเข้าตามปกติเพื่อรับรู้ผล Statement และตัดหนี้/แนบใบเสร็จ
            if (($p['paidtype'] ?? '') === 'ALREADY_BILLED_PARENT') {
                continue;
            }

            // กฎข้อ 109: ผู้ป่วยใน (IPD) ต้องเก็บ AN ในคอลัมน์ vn เสมอ
            $anVal = trim($p['an'] ?? '');
            $vn = ($visit_type === 'IPD' && !empty($anVal) && $anVal !== '-') ? $anVal : $p['vn'];
            $total_comp += $comp;

            // ตรวจสอบลูกหนี้เพื่อ Enrich ข้อมูล
            $d_info = $debtor_map[$vn] ?? null;
            if ($d_info) {
                $matched_debtor_count++;
                if (($p['paidtype'] ?? '') === 'READY_CR') {
                    // รายการเฉพาะ CR: ยอดเดบิตคือยอดเรียกเก็บของรายการ CR นั้นๆ
                    $debit = isset($d_info['debit']) && (float)$d_info['debit'] > 0 ? (float)$d_info['debit'] : $collected;
                    $accCode = '1102050101.216';
                    $accName = 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
                    $pttypeName = !empty($d_info['pttypename']) ? $d_info['pttypename'] : 'บัตรทอง UC (CR)';
                    $eclaimName = !empty($d_info['pttype_eclaim_name']) ? $d_info['pttype_eclaim_name'] : 'UC บริการเฉพาะ (CR)';
                } else {
                    $debit = (float)$d_info['debit'];
                    $accCode = $d_info['accountcode'];
                    $accName = $d_info['accountname'];
                    $pttypeName = $d_info['pttypename'];
                    $eclaimName = $d_info['pttype_eclaim_name'];
                }
            } else {
                $unmatched_count++;
                $debit = $collected; // Fallback ยอดเรียกเก็บ
                if (($p['paidtype'] ?? '') === 'READY_CR' || $is_cr_doc) {
                    $accCode = ($visit_type === 'IPD') ? '1102050101.217' : '1102050101.216';
                    $accName = ($visit_type === 'IPD') ? 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)' : 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
                    $pttypeName = 'บัตรทอง UC (CR)';
                    $eclaimName = 'UC บริการเฉพาะ (CR)';
                } elseif ($visit_type === 'IPD') {
                    switch ($fund) {
                        case 'UCS':
                            $accCode = '1102050101.202';
                            $accName = 'ลูกหนี้ค่ารักษา UC- IP (รอยืนยันผัง)';
                            $pttypeName = 'บัตรทอง UC (IPD)';
                            $eclaimName = 'UC ใน CUP (IPD)';
                            break;
                        case 'OFC':
                            $accCode = '1102050102.302';
                            $accName = 'ลูกหนี้ค่ารักษา จ่ายตรงกรมบัญชีกลาง- IP';
                            $pttypeName = 'จ่ายตรงกรมบัญชีกลาง (IPD)';
                            $eclaimName = 'OFC IPD';
                            break;
                        case 'LGO':
                            $accCode = '1102050102.307';
                            $accName = 'ลูกหนี้ค่ารักษา จ่ายตรง อปท.- IP';
                            $pttypeName = 'อปท. จ่ายตรง (IPD)';
                            $eclaimName = 'LGO IPD';
                            break;
                        case 'BKK':
                            $accCode = '1102050102.309';
                            $accName = 'ลูกหนี้ค่ารักษา เบิกจ่ายตรง กทม.- IP';
                            $pttypeName = 'กทม. จ่ายตรง (IPD)';
                            $eclaimName = 'BKK IPD';
                            break;
                        case 'SRT':
                        case 'BMT':
                            $accCode = '1102050102.310';
                            $accName = 'ลูกหนี้ค่ารักษา รัฐวิสาหกิจ- IP';
                            $pttypeName = 'รัฐวิสาหกิจ (IPD)';
                            $eclaimName = 'SRT IPD';
                            break;
                        case 'SSS':
                            $accCode = '1102050102.306';
                            $accName = 'ลูกหนี้ค่ารักษา ประกันสังคม- IP';
                            $pttypeName = 'ประกันสังคม (IPD)';
                            $eclaimName = 'SSS IPD';
                            break;
                        default:
                            $accCode = '';
                            $accName = 'รอยืนยันผังลูกหนี้';
                            $pttypeName = $fund;
                            $eclaimName = $fund . ' IPD';
                    }
                } else {
                    switch ($fund) {
                        case 'UCS':
                            $accCode = '1102050101.201';
                            $accName = 'ลูกหนี้ค่ารักษา UC- OP (รอยืนยันผัง)';
                            $pttypeName = 'บัตรทอง UC (OPD)';
                            $eclaimName = 'UC ใน CUP';
                            break;
                        case 'OFC':
                            $accCode = '1102050102.301';
                            $accName = 'ลูกหนี้ค่ารักษา จ่ายตรงกรมบัญชีกลาง- OP';
                            $pttypeName = 'จ่ายตรงกรมบัญชีกลาง (OPD)';
                            $eclaimName = 'OFC OPD';
                            break;
                        case 'LGO':
                            $accCode = '1102050102.303';
                            $accName = 'ลูกหนี้ค่ารักษา จ่ายตรง อปท.- OP';
                            $pttypeName = 'อปท. จ่ายตรง (OPD)';
                            $eclaimName = 'LGO OPD';
                            break;
                        case 'BKK':
                            $accCode = '1102050102.308';
                            $accName = 'ลูกหนี้ค่ารักษา เบิกจ่ายตรง กทม.- OP';
                            $pttypeName = 'กทม. จ่ายตรง (OPD)';
                            $eclaimName = 'BKK OPD';
                            break;
                        case 'SRT':
                        case 'BMT':
                            $accCode = '1102050102.304';
                            $accName = 'ลูกหนี้ค่ารักษา รัฐวิสาหกิจ- OP';
                            $pttypeName = 'รัฐวิสาหกิจ (OPD)';
                            $eclaimName = 'SRT OPD';
                            break;
                        case 'SSS':
                            $accCode = '1102050102.305';
                            $accName = 'ลูกหนี้ค่ารักษา ประกันสังคม- OP';
                            $pttypeName = 'ประกันสังคม (OPD)';
                            $eclaimName = 'SSS OPD';
                            break;
                        default:
                            $accCode = '';
                            $accName = 'รอยืนยันผังลูกหนี้';
                            $pttypeName = $fund;
                            $eclaimName = $fund . ' OPD';
                    }
                }
            }

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

            // ==============================================================
            // 🌟 ตรวจสอบและแยกแถวผังลูก CR (1102050101.217) สำหรับผู้ป่วยใน (IPD)
            // ==============================================================
            $cr_records = $d_info['cr_breakdown'] ?? [];
            $ae_val = (float)($p['ae'] ?? 0);
            $inst_val = (float)($p['inst'] ?? 0);
            $total_cr_stm = $ae_val + $inst_val;

            if ($visit_type === 'IPD' && !empty($cr_records) && $total_cr_stm > 0) {
                $cr_comp_total = 0.0;
                $cr_debt_total = 0.0;
                $cr_updates = [];

                foreach ($cr_records as $cr_rec) {
                    $cr_type = trim($cr_rec['cr_type'] ?? '');
                    $cr_item_amt = (float)($cr_rec['item_amount'] ?? 0);
                    $cr_debt_total += $cr_item_amt;
                    
                    // จัดสรรชดเชยตามประเภท CR โดยตรง
                    $allocated_comp = 0.0;
                    if (strpos($cr_type, 'รถรีเฟอร์') !== false || strpos($cr_type, 'AE') !== false) {
                        $allocated_comp = ($ae_val > 0) ? min($ae_val, $cr_item_amt) : 0.0;
                        if ($allocated_comp == 0 && $total_cr_stm > 0) {
                            $allocated_comp = min($total_cr_stm, $cr_item_amt);
                        }
                    } elseif (strpos($cr_type, 'Instrument') !== false || strpos($cr_type, 'เครื่องมือ') !== false) {
                        $allocated_comp = ($inst_val > 0) ? min($inst_val, $cr_item_amt) : 0.0;
                        if ($allocated_comp == 0 && $total_cr_stm > 0) {
                            $allocated_comp = min($total_cr_stm, $cr_item_amt);
                        }
                    } else {
                        $allocated_comp = min(max(0.0, $total_cr_stm - $cr_comp_total), $cr_item_amt);
                    }
                    if ($allocated_comp <= 0 && $total_cr_stm > 0) {
                        $allocated_comp = min($total_cr_stm, $cr_item_amt);
                    }
                    
                    $cr_comp_total += $allocated_comp;
                    $cr_updates[] = [
                        'vn' => $vn,
                        'cr_type' => $cr_type,
                        'compensated' => $allocated_comp,
                        'settle_status' => ($allocated_comp >= $cr_item_amt ? 'SETTLED' : 'PARTIAL')
                    ];
                }

                if ($cr_comp_total > 0) {
                    // 1. บันทึกแถวผังลูก CR (1102050101.217) ลง imr_tb_check_invoice
                    $childAccCode = '1102050101.217';
                    $childAccName = 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)';
                    $childPttypeName = !empty($d_info['pttypename']) ? $d_info['pttypename'] : 'บัตรทอง UC (CR)';
                    $childEclaimName = 'UC บริการเฉพาะ (CR)';
                    $childDebit = $cr_debt_total;
                    $childComp = $cr_comp_total;
                    $childCollected = $cr_debt_total;
                    $childDiff = round($childComp - $childDebit, 2);
                    $childDown = ($childDiff > 0) ? $childDiff : 0.0;
                    $childUp = ($childDiff < 0) ? $childDiff : 0.0;
                    $childTotal = $childComp;
                    $childPaidType = 'READY_CR';
                    $childAe = (strpos($cr_records[0]['cr_type'] ?? '', 'Instrument') === false) ? $childComp : 0.0;
                    $childInst = (strpos($cr_records[0]['cr_type'] ?? '', 'Instrument') !== false) ? $childComp : 0.0;
                    $zeroFloat = 0.0;

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
                        $childPttypeName,
                        $childEclaimName,
                        $childAccCode,
                        $childAccName,
                        $p['projcode'],
                        $childCollected,
                        $childComp,
                        $childDebit,
                        $childDiff,
                        $childDown,
                        $childUp,
                        $childTotal,
                        $fund,
                        $zeroFloat, // hc
                        $childAe, // ae
                        $childInst, // inst
                        $zeroFloat, // ip
                        $zeroFloat, // dmis
                        $zeroFloat, // op
                        $zeroFloat, // drug
                        $zeroFloat, // ontop
                        $zeroFloat, // pallativecare
                        $zeroFloat, // dmishd
                        $zeroFloat, // pp
                        $zeroFloat, // fs
                        $zeroFloat, // opbkk
                        $childPaidType
                    );
                    $stmt->execute();
                    $inserted_count++;
                    $matched_debtor_count++;

                    // 2. อัปเดตยอดชดเชยและสถานะในตาราง imr_tb_debtor_cr_breakdown
                    foreach ($cr_updates as $cu) {
                        $upd_sql = "UPDATE imr_tb_debtor_cr_breakdown 
                                    SET compensated = ?, settle_status = ?, updated_at = NOW() 
                                    WHERE visit_type = 'IPD' AND vn = ?";
                        $stmt_cr_upd = $conn->prepare($upd_sql);
                        if ($stmt_cr_upd) {
                            $stmt_cr_upd->bind_param("dss", $cu['compensated'], $cu['settle_status'], $cu['vn']);
                            $stmt_cr_upd->execute();
                            $stmt_cr_upd->close();
                        }
                    }

                    // 3. ปรับยอดของผังแม่ IPD เพื่อบันทึกแถวแม่สุทธิ
                    $motherRemainFromBd = isset($cr_records[0]['general_remain_amount']) ? (float)$cr_records[0]['general_remain_amount'] : 0.0;
                    $debit = ($motherRemainFromBd > 0) ? $motherRemainFromBd : max(0.0, (float)$d_info['debit'] - $cr_debt_total);
                    $comp = max(0.0, $comp - $cr_comp_total);
                    $collected = max(0.0, $collected - $cr_debt_total);
                    $p['ae'] = max(0.0, (float)$p['ae'] - $childAe);
                    $p['inst'] = max(0.0, (float)$p['inst'] - $childInst);
                }
            }

            // คำนวณผลต่างและส่วนต่ำ/ส่วนสูง
            $diff = round($comp - $debit, 2);
            $down = ($diff > 0) ? $diff : 0.0;
            $up = ($diff < 0) ? $diff : 0.0;
            $total = $comp;

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

            // ==============================================================
            // 🌟 ซิงค์หรือสร้างรายการลูกหนี้ CR ใน imr_tb_debtor_cr_breakdown 
            // ให้มีข้อมูลในผัง CR (.216 หรือ .217) ครบ 100% ทุกรายการตามข้อตกลง Grill-me
            // ==============================================================
            if (($p['paidtype'] ?? '') === 'READY_CR' || $is_cr_doc) {
                $target_cr_acc = ($visit_type === 'IPD') ? '1102050101.217' : '1102050101.216';
                $parent_default = ($visit_type === 'IPD') ? '1102050101.202' : '1102050101.201';
                $cr_proj = trim($p['projcode'] ?? '');
                $cr_type_title = !empty($cr_proj) ? 'บริการเฉพาะ (' . $cr_proj . ')' : 'บริการเฉพาะ (CR)';
                $safe_vn = mysqli_real_escape_string($conn, $vn);
                $safe_hn = mysqli_real_escape_string($conn, $p['hn'] ?? '');
                $safe_cid = !empty($p['pid']) ? mysqli_real_escape_string($conn, $p['pid']) : '';
                $safe_ptname = mysqli_real_escape_string($conn, $p['ptname'] ?? '');
                $safe_admdate = mysqli_real_escape_string($conn, $admDate);
                $parent_acc = !empty($d_info['accountcode']) ? mysqli_real_escape_string($conn, $d_info['accountcode']) : $parent_default;

                // ตรวจสอบว่ามีแถวใน imr_tb_debtor_cr_breakdown อยู่แล้วหรือไม่
                $chk_q = mysqli_query($conn, "SELECT id, bill, settle_status FROM imr_tb_debtor_cr_breakdown WHERE visit_type = '$visit_type' AND vn = '$safe_vn' LIMIT 1");
                if ($chk_r = mysqli_fetch_assoc($chk_q)) {
                    // มีแถวอยู่แล้ว: อัปเดตยอดชดเชย
                    $upd_comp_sql = "UPDATE imr_tb_debtor_cr_breakdown 
                                     SET compensated = ?, updated_at = NOW() 
                                     WHERE id = ?";
                    $stmt_upd = $conn->prepare($upd_comp_sql);
                    if ($stmt_upd) {
                        $stmt_upd->bind_param("di", $comp, $chk_r['id']);
                        $stmt_upd->execute();
                        $stmt_upd->close();
                    }
                } else {
                    // ยังไม่มีแถว: สร้างแถวใหม่ใน imr_tb_debtor_cr_breakdown ให้อัตโนมัติ (Zero-Config CR Sync)
                    $monthtxt = '';
                    if (preg_match('/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/u', $admDate, $dm)) {
                        $monthtxt = str_pad($dm[2], 2, '0', STR_PAD_LEFT) . '/' . $dm[3];
                    }
                    $safe_month = mysqli_real_escape_string($conn, $monthtxt);
                    $ins_cr_sql = "INSERT INTO imr_tb_debtor_cr_breakdown 
                                   (visit_type, vn, hn, cid, ptname, vstdate, monthtxt, parent_accountcode, cr_type, target_accountcode, item_amount, general_remain_amount, compensated, settle_status, items_data, created_at, updated_at)
                                   VALUES 
                                   ('$visit_type', '$safe_vn', '$safe_hn', '$safe_cid', '$safe_ptname', '$safe_admdate', '$safe_month', '$parent_acc', '$cr_type_title', '$target_cr_acc', $debit, 0.00, $comp, 'WAIT', '[]', NOW(), NOW())";
                    @mysqli_query($conn, $ins_cr_sql);
                }
            }
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
