<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - CR Debtor Auto-Migration & Classification Helper
 * ====================================================================================
 * โมดูลช่วยตรวจสอบและสร้างตารางฐานข้อมูลอัตโนมัติ (Zero-Configuration Auto-Migration)
 * สำหรับรองรับการใช้งานที่โรงพยาบาลอื่นๆ และเป็นแกนกลางในการจำแนก 8 กลุ่มย่อยของลูกหนี้ CR
 * 
 * @author eDHS Developer
 * @version 1.0.0 (2026-08-24)
 * ====================================================================================
 */

if (!function_exists('cleanNum')) {
    function cleanNum($val) {
        if ($val === null || $val === '') return 0.0;
        return floatval(str_replace(',', '', (string)$val));
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney($number, $decimals = 2) {
        return number_format((float)cleanNum($number), $decimals, '.', ',');
    }
}

if (!function_exists('ensure_cr_tables')) {
    /**
     * ตรวจสอบและสร้างตารางฐานข้อมูลสำหรับลูกหนี้ CR โดยอัตโนมัติ
     * 
     * @param mysqli $conn การเชื่อมต่อฐานข้อมูล eDHS
     * @return bool สถานะความสำเร็จ
     */
    function ensure_cr_tables($conn) {
        if (!$conn) return false;

        // 1. ตารางเก็บรายละเอียดการตัดแยกค่าใช้จ่ายลูกหนี้ CR ระดับรายการ (Breakdown Table)
        $sql_breakdown = "
            CREATE TABLE IF NOT EXISTS `imr_tb_debtor_cr_breakdown` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `visit_type` ENUM('OPD', 'IPD') NOT NULL DEFAULT 'OPD' COMMENT 'ประเภทผู้ป่วย OPD หรือ IPD',
                `vn` VARCHAR(30) NOT NULL COMMENT 'VN สำหรับผู้ป่วยนอก หรือ AN สำหรับผู้ป่วยใน',
                `hn` VARCHAR(15) NOT NULL COMMENT 'HN ผู้ป่วย',
                `cid` VARCHAR(255) NULL COMMENT 'เลขประจำตัวประชาชน (เข้ารหัส)',
                `ptname` VARCHAR(150) NULL COMMENT 'ชื่อ-สกุล ผู้ป่วย',
                `vstdate` VARCHAR(10) NOT NULL COMMENT 'วันที่รับบริการ วว/ดด/ปปปป (พ.ศ.)',
                `monthtxt` VARCHAR(10) NOT NULL COMMENT 'เดือน-ปี เช่น 01-2569',
                `parent_accountcode` VARCHAR(50) NOT NULL COMMENT 'รหัสผังบัญชีหลักเดิม เช่น 1102050101.216, .201, .209, .203',
                `cr_type` VARCHAR(50) NOT NULL COMMENT 'กลุ่มย่อย CR เช่น CR-Instrument, CR-walkin, CR-Tele ฯลฯ',
                `target_accountcode` VARCHAR(50) NOT NULL COMMENT 'รหัสผังย่อยสำหรับตั้งหนี้ เช่น 1102050101.216_CR_INST',
                `item_code` VARCHAR(30) NULL COMMENT 'รหัสยา/เวชภัณฑ์/หัตถการ icode',
                `item_name` VARCHAR(255) NULL COMMENT 'ชื่อรายการ',
                `adp_type` VARCHAR(10) NULL COMMENT 'หมวด ADP TYPE (1, 2, 3, 4, 5)',
                `adp_code` VARCHAR(50) NULL COMMENT 'รหัสเบิก สปสช. nhso_adp_code',
                `qty` DECIMAL(10,2) DEFAULT 1.00 COMMENT 'จำนวนที่เบิก',
                `unit_price` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'ราคาต่อหน่วย',
                `item_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดเงินของรายการ CR นี้',
                `general_remain_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดค่าบริการทั่วไปคงเหลือของ Visit',
                `compensated` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'เงินชดเชยที่ได้รับของส่วน CR',
                `bill` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'เลขที่ใบเสร็จตัดหนี้ CR',
                `billdate` VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'วันที่ออกใบเสร็จตัดหนี้ CR',
                `settle_status` ENUM('WAIT','PARTIAL','SETTLED') NOT NULL DEFAULT 'WAIT' COMMENT 'สถานะการตัดหนี้ CR',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_visit_vn_cr` (`visit_type`, `vn`, `cr_type`),
                INDEX `idx_vn_type` (`visit_type`, `vn`),
                INDEX `idx_month` (`monthtxt`),
                INDEX `idx_cr_type` (`cr_type`),
                INDEX `idx_target_acc` (`target_accountcode`),
                INDEX `idx_parent_acc` (`parent_accountcode`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        @mysqli_query($conn, $sql_breakdown);

        // Auto-migrate columns if table already existed
        $q_chk_comp = mysqli_query($conn, "SHOW COLUMNS FROM `imr_tb_debtor_cr_breakdown` LIKE 'compensated'");
        if ($q_chk_comp && mysqli_num_rows($q_chk_comp) === 0) {
            @mysqli_query($conn, "ALTER TABLE `imr_tb_debtor_cr_breakdown` 
                ADD COLUMN `compensated` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `general_remain_amount`,
                ADD COLUMN `bill` VARCHAR(50) NOT NULL DEFAULT '' AFTER `compensated`,
                ADD COLUMN `billdate` VARCHAR(10) NOT NULL DEFAULT '' AFTER `bill`,
                ADD COLUMN `settle_status` ENUM('WAIT','PARTIAL','SETTLED') NOT NULL DEFAULT 'WAIT' AFTER `billdate`");
        }

        // 2. ตารางเก็บการตั้งค่า Mapping Configuration ของลูกหนี้ CR
        $sql_config = "
            CREATE TABLE IF NOT EXISTS `imr_tb_cr_config` (
                `config_key` VARCHAR(50) PRIMARY KEY COMMENT 'คีย์การตั้งค่า เช่น cr_mapping_settings',
                `config_value` LONGTEXT NOT NULL COMMENT 'ข้อมูลการตั้งค่ารูปแบบ JSON',
                `updated_by` VARCHAR(100) NULL COMMENT 'ผู้แก้ไขล่าสุด',
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        @mysqli_query($conn, $sql_config);

        // ตรวจสอบว่ามีการลงค่า Default Config หรือยัง หากยังไม่มีให้ใส่ค่าตั้งต้น
        $check_cfg = mysqli_query($conn, "SELECT config_key FROM imr_tb_cr_config WHERE config_key = 'cr_mapping_settings' LIMIT 1");
        if ($check_cfg && mysqli_num_rows($check_cfg) === 0) {
            $default_config = get_default_cr_config();
            $json_val = mysqli_real_escape_string($conn, json_encode($default_config, JSON_UNESCAPED_UNICODE));
            @mysqli_query($conn, "INSERT INTO imr_tb_cr_config (config_key, config_value, updated_by) VALUES ('cr_mapping_settings', '$json_val', 'System Default')");
        }

        return true;
    }
}

if (!function_exists('get_default_cr_config')) {
    /**
     * คืนค่าโครงสร้างการตั้งค่าเริ่มต้นแยกตามผู้ป่วยนอก (OPD) และผู้ป่วยใน (IPD)
     */
    function get_default_cr_config() {
        return [
            "month_mode" => "ALL",
            "effective_start_month" => "ALL", // "ALL" หรือระบุเดือน เช่น "07-2569"
            "active_months" => [],
            "auto_split_enabled" => true,     // Backward compatibility Master Switch
            "auto_split_opd_enabled" => true, // เปิดใช้งานการตัดแยกยอดเงินจริง (CR OPD)
            "auto_split_ipd_enabled" => true, // เปิดใช้งานการตัดแยกยอดเงินจริง (CR IPD)
            "parent_accounts" => [
                // ผัง OPD (4 ผัง)
                "1102050101.201" => ["enabled" => true, "type" => "OPD", "name" => "ลูกหนี้ค่ารักษา UC- OP ใน CUP"],
                "1102050101.209" => ["enabled" => true, "type" => "OPD", "name" => "ลูกหนี้ค่ารักษาด้านการสร้างเสริมสุขภาพและป้องกันโรค (P&P)"],
                "1102050101.203" => ["enabled" => true, "type" => "OPD", "name" => "ลูกหนี้ค่ารักษา UC - OP นอก CUP (ในจังหวัดสังกัด สธ.)"],
                "1102050101.216" => ["enabled" => true, "type" => "OPD", "name" => "ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)"],
                // ผัง IPD (2 ผัง)
                "1102050101.202" => ["enabled" => true, "type" => "IPD", "name" => "ลูกหนี้ค่ารักษา UC - IP"],
                "1102050101.217" => ["enabled" => true, "type" => "IPD", "name" => "ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)"]
            ],
            // -------------------------------------------------------------
            // กลุ่มย่อยสำหรับผู้ป่วยนอก (OPD Subgroups - 8 กลุ่มหลัก)
            // -------------------------------------------------------------
            "subgroups_opd" => [
                "CR-Instrument" => [
                    "id" => "CR-Instrument",
                    "enabled" => true,
                    "title" => "CR-Instrument (อุปกรณ์และอวัยวะเทียม)",
                    "short_name" => "CR-Instrument",
                    "target_accountcode" => "1102050101.216_CR_INST",
                    "accountname" => "- ลูกหนี้ค่าอุปกรณ์/อวัยวะเทียม (CR-Instrument)",
                    "icon" => "",
                    "adp_type" => "2",
                    "auto_adp_type_2" => true,
                    "custom_adp_codes" => ["7004", "7005", "8612", "8813", "8814"],
                    "custom_icodes" => []
                ],
                "CR-walkin" => [
                    "id" => "CR-walkin",
                    "enabled" => true,
                    "title" => "CR-walkin (บริการข้าม CUP / 30 บาทรักษาทุกที่)",
                    "short_name" => "CR-walkin",
                    "target_accountcode" => "1102050101.216_CR_WALKIN",
                    "accountname" => "- ลูกหนี้บริการ Walk-in ข้าม CUP (CR-walkin)",
                    "icon" => "",
                    "adp_type" => "1",
                    "adp_codes" => ["WALKIN"],
                    "pttypes" => ["23"],
                    "pttypenames" => ["Walkin", "walkin", "WALKIN"],
                    "check_hospmain_diff" => true,
                    "check_cross_region_only" => true,
                    "hospital_chwpart" => "",
                    "check_adp_walkin" => true,
                    "custom_icodes" => []
                ],
                "CR-AE" => [
                    "id" => "CR-AE",
                    "enabled" => true,
                    "title" => "CR-AE (อุบัติเหตุ/ฉุกเฉินเฉพาะจุด)",
                    "short_name" => "CR-AE",
                    "target_accountcode" => "1102050101.216_CR_AE",
                    "accountname" => "- ลูกหนี้ฉุกเฉิน/อุบัติเหตุ (CR-AE)",
                    "icon" => "",
                    "adp_type" => "1",
                    "pttypes" => ["98", "97", "91", "AM"],
                    "pttypenames" => ["ฉุกเฉิน", "อุบัติเหตุ", "AE"],
                    "departments" => ["ER", "ฉุกเฉิน", "ห้องอุบัติเหตุ-ฉุกเฉิน"],
                    "check_er_regist" => true,
                    "check_cross_region_only" => true,
                    "hospital_chwpart" => "",
                    "transfer_percent" => 100,
                    "custom_icodes" => []
                ],
                "CR-เกิดสิทธิทันที" => [
                    "id" => "CR-เกิดสิทธิทันที",
                    "enabled" => true,
                    "title" => "CR-เกิดสิทธิทันที (New Born / เปลี่ยนสิทธิแรกเกิด)",
                    "short_name" => "CR-เกิดสิทธิทันที",
                    "target_accountcode" => "1102050101.216_CR_NEWBORN",
                    "accountname" => "- ลูกหนี้เกิดสิทธิทันที (CR-เกิดสิทธิทันที)",
                    "icon" => "",
                    "pttypes" => ["XX"],
                    "pttypenames" => ["สิทธิว่าง", "แรกเกิด", "Newborn", "NEWBORN"],
                    "ptsubtypes" => ["เด็กแรกเกิด", "Newborn", "NEWBORN"],
                    "max_age_days" => 28,
                    "transfer_percent" => 100,
                    "custom_icodes" => []
                ],
                "CR-palliative" => [
                    "id" => "CR-palliative",
                    "enabled" => true,
                    "title" => "CR-palliative (บริการดูแลผู้ป่วยระยะประคับประคอง)",
                    "short_name" => "CR-palliative",
                    "target_accountcode" => "1102050101.216_CR_PALLIATIVE",
                    "accountname" => "- ลูกหนี้บริการประคับประคอง (CR-palliative)",
                    "icon" => "",
                    "icd10_rules" => ["Z515", "Z51.5"],
                    "require_chronic_or_opioid" => true,
                    "chronic_icd10_regex" => "B2[0-4]|^C|D[0-4]|I5|I6|J44|K704|K717|K72|N185",
                    "custom_icodes" => []
                ],
                "CR-Tele" => [
                    "id" => "CR-Tele",
                    "enabled" => true,
                    "title" => "CR-Tele (บริการการแพทย์ทางไกล / Telemedicine)",
                    "short_name" => "CR-Tele",
                    "target_accountcode" => "1102050101.216_CR_TELE",
                    "accountname" => "- ลูกหนี้บริการการแพทย์ทางไกล (CR-Tele)",
                    "icon" => "",
                    "adp_type" => "1",
                    "adp_codes" => ["TELMED"],
                    "ovstist_export_codes" => ["5"],
                    "custom_icodes" => []
                ],
                "CR-ยาclopi" => [
                    "id" => "CR-ยาclopi",
                    "enabled" => true,
                    "title" => "CR-ยาclopi (ยา Clopidogrel Fee Schedule)",
                    "short_name" => "CR-ยาclopi",
                    "target_accountcode" => "1102050101.216_CR_CLOPI",
                    "accountname" => "- ลูกหนี้ค่ายา Clopidogrel (CR-ยาclopi)",
                    "icon" => "",
                    "adp_type" => "3",
                    "name_regex" => "Clopidrogrel|Clopidogrel",
                    "adp_codes" => ["3799977101"],
                    "did_prefix" => "1248460000039721217",
                    "custom_icodes" => []
                ],
                "CR-ยาสมุนไพร" => [
                    "id" => "CR-ยาสมุนไพร",
                    "enabled" => true,
                    "title" => "CR-ยาสมุนไพร (ยาสมุนไพร/แพทย์แผนไทย)",
                    "short_name" => "CR-ยาสมุนไพร",
                    "target_accountcode" => "1102050101.216_CR_HERB",
                    "accountname" => "- ลูกหนี้ยาสมุนไพร/แพทย์แผนไทย (CR-ยาสมุนไพร)",
                    "icon" => "",
                    "adp_type" => "4",
                    "require_ttmt" => true,
                    "sks_product_categories" => [3, 4],
                    "custom_icodes" => []
                ]
            ],
            // -------------------------------------------------------------
            // กลุ่มย่อยสำหรับผู้ป่วยใน (IPD Subgroups)
            // -------------------------------------------------------------
            "subgroups_ipd" => [
                "CR-Instrument" => [
                    "id" => "CR-Instrument",
                    "enabled" => true,
                    "title" => "CR-Instrument (อุปกรณ์และอวัยวะเทียม IPD / ผ่าตัด / เลนส์ตา / ดามเหล็ก)",
                    "short_name" => "CR-Instrument",
                    "target_accountcode" => "1102050101.217_CR_INST",
                    "accountname" => "- ลูกหนี้ค่าอุปกรณ์/อวัยวะเทียม IPD (CR-Instrument)",
                    "icon" => "",
                    "adp_type" => "2",
                    "auto_adp_type_2" => true,
                    "custom_adp_codes" => ["7004", "7005", "8612", "8813", "8814"],
                    "custom_icodes" => []
                ],
                "CR-รถรีเฟอร์" => [
                    "id" => "CR-รถรีเฟอร์",
                    "enabled" => true,
                    "title" => "CR-รถรีเฟอร์ (ค่ารถพยาบาลส่งต่อ / Refer IPD)",
                    "short_name" => "CR-รถรีเฟอร์",
                    "target_accountcode" => "1102050101.217_CR_REFER",
                    "accountname" => "- ลูกหนี้ค่ารถพยาบาลส่งต่อ Refer (CR-รถรีเฟอร์)",
                    "icon" => "",
                    "adp_type" => "5",
                    "custom_adp_codes" => ["S1801", "S1802", "COVV01", "55999", "REFER"],
                    "name_keywords" => ["ค่ารถพยาบาลส่งต่อ", "refer", "รถส่งต่อ", "รถรีเฟอร์", "รถพยาบาล"],
                    "custom_icodes" => ["3900320", "3002101", "3900188", "3900189", "3900190", "3900191", "3900192", "3900279", "3900288", "3900309", "3900498", "3900612", "3900614"]
                ],
                "CR-AE" => [
                    "id" => "CR-AE",
                    "enabled" => true,
                    "title" => "CR-AE (อุบัติเหตุ/ฉุกเฉินเฉพาะจุด IPD)",
                    "short_name" => "CR-AE",
                    "target_accountcode" => "1102050101.217_CR_AE",
                    "accountname" => "- ลูกหนี้ฉุกเฉิน/อุบัติเหตุ IPD (CR-AE)",
                    "icon" => "",
                    "adp_type" => "1",
                    "pttypes" => ["91", "AM"],
                    "pttypenames" => ["ฉุกเฉิน", "อุบัติเหตุ", "AE"],
                    "transfer_percent" => 100,
                    "custom_icodes" => []
                ],
                "CR-เกิดสิทธิทันที" => [
                    "id" => "CR-เกิดสิทธิทันที",
                    "enabled" => true,
                    "title" => "CR-เกิดสิทธิทันที (New Born / เปลี่ยนสิทธิแรกเกิด IPD)",
                    "short_name" => "CR-เกิดสิทธิทันที",
                    "target_accountcode" => "1102050101.217_CR_NEWBORN",
                    "accountname" => "- ลูกหนี้เกิดสิทธิทันที IPD (CR-เกิดสิทธิทันที)",
                    "icon" => "",
                    "pttypes" => ["XX"],
                    "pttypenames" => ["สิทธิว่าง", "แรกเกิด", "Newborn", "NEWBORN"],
                    "transfer_percent" => 100,
                    "custom_icodes" => []
                ]
            ]
        ];
    }
}

if (!function_exists('get_active_cr_config')) {
    /**
     * ดึงการตั้งค่า CR ปัจจุบันจากฐานข้อมูล (หากไม่มีให้คืนค่า Default)
     * รองรับ Backward Compatibility แปลงโครงสร้าง subgroups เดิมเป็น subgroups_opd / subgroups_ipd
     */
    function get_active_cr_config($conn) {
        ensure_cr_tables($conn);
        $res = mysqli_query($conn, "SELECT config_value FROM imr_tb_cr_config WHERE config_key = 'cr_mapping_settings' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $decoded = json_decode($row['config_value'], true);
            if (is_array($decoded)) {
                $def = get_default_cr_config();
                if (!isset($decoded['subgroups_opd']) && isset($decoded['subgroups'])) {
                    $decoded['subgroups_opd'] = $decoded['subgroups'];
                }
                if (!isset($decoded['subgroups_ipd'])) {
                    $decoded['subgroups_ipd'] = $def['subgroups_ipd'];
                }
                if (!isset($decoded['subgroups_opd'])) {
                    $decoded['subgroups_opd'] = $def['subgroups_opd'];
                }
                // ตรวจสอบและเติม pttypes/pttypenames ให้ CR-walkin หากยังไม่มีในคอนฟิกเดิม
                if (isset($decoded['subgroups_opd']['CR-walkin'])) {
                    if (!isset($decoded['subgroups_opd']['CR-walkin']['pttypes'])) {
                        $decoded['subgroups_opd']['CR-walkin']['pttypes'] = $def['subgroups_opd']['CR-walkin']['pttypes'] ?? ['23'];
                    }
                    if (!isset($decoded['subgroups_opd']['CR-walkin']['pttypenames'])) {
                        $decoded['subgroups_opd']['CR-walkin']['pttypenames'] = $def['subgroups_opd']['CR-walkin']['pttypenames'] ?? ['Walkin', 'walkin', 'WALKIN'];
                    }
                }
                // ตรวจสอบและเติม effective_start_month / effective_end_month ให้ทุกกลุ่มย่อย
                foreach (['subgroups_opd', 'subgroups_ipd'] as $gk) {
                    if (isset($decoded[$gk]) && is_array($decoded[$gk])) {
                        foreach ($decoded[$gk] as $sg_k => &$sg_v) {
                            if (!isset($sg_v['effective_start_month']) || $sg_v['effective_start_month'] === '') {
                                $sg_v['effective_start_month'] = 'ALL';
                            }
                            if (!isset($sg_v['effective_end_month'])) {
                                $sg_v['effective_end_month'] = '';
                            }
                        }
                        unset($sg_v);
                    }
                }
                return $decoded;
            }
        }
        return get_default_cr_config();
    }
}

if (!function_exists('normalize_monthtxt_be')) {
    /**
     * แปลงรูปแบบเดือนให้เป็นมาตรฐาน mm-yyyy (พ.ศ.) เช่น '7-2026' -> '07-2569'
     * รองรับค่าว่าง, 'ALL', และป้องกัน Error กรณีรับค่าเป็น Array
     */
    function normalize_monthtxt_be($monthtxt) {
        if (is_array($monthtxt)) return '';
        $monthtxt = trim((string)$monthtxt);
        if (empty($monthtxt) || strtoupper($monthtxt) === 'ALL') return '';
        $clean = str_replace('/', '-', $monthtxt);
        $parts = explode('-', $clean);
        if (count($parts) === 2) {
            $m = intval($parts[0]);
            $y = intval($parts[1]);
            if ($y < 2400) $y += 543;
            return sprintf('%02d-%04d', $m, $y);
        }
        return $clean;
    }
}

if (!function_exists('compare_monthtxt_be')) {
    /**
     * เปรียบเทียบเดือน 2 ค่าในรูปแบบตัวเลขอ้างอิง พ.ศ. (YYYYMM)
     * คืนค่า:
     *  -1 หาก $m1 < $m2
     *   0 หาก $m1 == $m2
     *   1 หาก $m1 > $m2
     */
    function compare_monthtxt_be($m1, $m2) {
        $n1 = normalize_monthtxt_be($m1);
        $n2 = normalize_monthtxt_be($m2);
        if (empty($n1) && empty($n2)) return 0;
        if (empty($n1)) return -1;
        if (empty($n2)) return 1;

        $p1 = explode('-', $n1);
        $p2 = explode('-', $n2);
        $v1 = (count($p1) === 2) ? (intval($p1[1]) * 100 + intval($p1[0])) : 0;
        $v2 = (count($p2) === 2) ? (intval($p2[1]) * 100 + intval($p2[0])) : 0;

        if ($v1 < $v2) return -1;
        if ($v1 > $v2) return 1;
        return 0;
    }
}

if (!function_exists('is_cr_subgroup_effective_for_month')) {
    /**
     * ตรวจสอบว่ากลุ่มย่อย CR เปิดใช้งาน และ มีผลบังคับใช้ในเดือนที่ระบุหรือไม่ (Subgroup Effective Month Range)
     *
     * @param array $config คอนฟิก CR ทั้งหมด
     * @param string $subgroup_id รหัสกลุ่มย่อย เช่น 'CR-Instrument', 'CR-walkin'
     * @param string $visit_type 'OPD' หรือ 'IPD'
     * @param string|array|null $monthtxt เดือนที่ต้องการตรวจสอบ เช่น '8-2026', '08-2569' หรือ Array ของเดือน
     * @return bool
     */
    function is_cr_subgroup_effective_for_month($config, $subgroup_id, $visit_type = 'OPD', $monthtxt = null) {
        if (empty($config) || !is_array($config)) return true;

        // หากส่งเดือนมาเป็น Array (เช่น จากช่วงวันที่ข้ามหลายเดือน) ให้ตรวจสอบว่ามีผลในเดือนใดเดือนหนึ่งหรือไม่
        if (is_array($monthtxt)) {
            if (empty($monthtxt)) return true;
            foreach ($monthtxt as $m) {
                if (is_cr_subgroup_effective_for_month($config, $subgroup_id, $visit_type, $m)) {
                    return true;
                }
            }
            return false;
        }

        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        $sg = null;
        if (isset($config[$group_key][$subgroup_id])) {
            $sg = $config[$group_key][$subgroup_id];
        } elseif (isset($config['subgroups'][$subgroup_id])) {
            $sg = $config['subgroups'][$subgroup_id];
        }
        if (!$sg) return false;

        // 1. ตรวจสอบสถานะเปิดใช้งาน (enabled)
        if (isset($sg['enabled']) && $sg['enabled'] === false) {
            return false;
        }

        // หากไม่ได้ระบุเดือน หรือระบุเป็น 'ALL' ให้ถือว่ามีผลตามสถานะ enabled
        if (empty($monthtxt) || strtoupper(trim((string)$monthtxt)) === 'ALL') {
            return true;
        }

        // 2. ตรวจสอบเดือนเริ่มต้น (effective_start_month)
        $start_m = isset($sg['effective_start_month']) ? trim((string)$sg['effective_start_month']) : 'ALL';
        if (!empty($start_m) && strtoupper($start_m) !== 'ALL') {
            if (compare_monthtxt_be($monthtxt, $start_m) < 0) {
                return false; // เดือนของ visit มาก่อนเดือนเริ่มต้นของกลุ่มย่อย
            }
        }

        // 3. ตรวจสอบเดือนสิ้นสุด (effective_end_month)
        $end_m = isset($sg['effective_end_month']) ? trim((string)$sg['effective_end_month']) : '';
        if (!empty($end_m) && strtoupper($end_m) !== 'ALL') {
            if (compare_monthtxt_be($monthtxt, $end_m) > 0) {
                return false; // เดือนของ visit มาหลังเดือนสิ้นสุดของกลุ่มย่อย
            }
        }

        return true;
    }
}

if (!function_exists('is_cr_effective_for_month')) {
    /**
     * ตรวจสอบว่าเดือนที่เลือก (monthtxt เช่น '7-2026', '07-2569' หรือ '07/2569') มีผลใช้งานระบบ CR หรือไม่
     * รองรับโหมด ALL (ทุกเดือน), SPECIFIC (เลือกเฉพาะเดือนที่กำหนด), START_FROM (ตั้งแต่เดือนเริ่มต้น)
     */
    function is_cr_effective_for_month($config, $monthtxt) {
        if (empty($config) || !is_array($config)) return true;
        if (empty($monthtxt)) return true;

        $month_mode = isset($config['month_mode']) ? strtoupper(trim($config['month_mode'])) : '';
        
        // 1. โหมดเปิดใช้งานทุกเดือน
        if ($month_mode === 'ALL') {
            return true;
        }

        $norm_cur = normalize_monthtxt_be($monthtxt);

        // 2. โหมดเลือกเปิด/ปิดเฉพาะเดือน (Specific Months Checklist)
        if ($month_mode === 'SPECIFIC') {
            $active_months = isset($config['active_months']) ? (array)$config['active_months'] : [];
            $norm_active = array_map('normalize_monthtxt_be', $active_months);
            return in_array($norm_cur, $norm_active);
        }

        // 3. โหมดกำหนดเดือนเริ่มต้นเป็นต้นไป (Start From Month / Default Compatibility)
        $start_m = isset($config['effective_start_month']) ? trim($config['effective_start_month']) : 'ALL';
        if (empty($start_m) || strtoupper($start_m) === 'ALL') {
            return true;
        }

        $norm_start = normalize_monthtxt_be($start_m);
        $p_cur = explode('-', $norm_cur);
        $p_start = explode('-', $norm_start);

        if (count($p_cur) === 2 && count($p_start) === 2) {
            $m_cur = intval($p_cur[0]);
            $y_cur = intval($p_cur[1]);
            $m_start = intval($p_start[0]);
            $y_start = intval($p_start[1]);

            if ($y_cur > $y_start) return true;
            if ($y_cur === $y_start && $m_cur >= $m_start) return true;
            return false;
        }

        return true;
    }
}

if (!function_exists('is_cr_auto_split_enabled')) {
    /**
     * ตรวจสอบว่าเปิดใช้งานการตัดแยกยอดเงินจริง (Auto-Debit Splitting) หรือไม่
     * รองรับการแยกเปิด/ปิดอิสระระหว่าง OPD และ IPD
     */
    function is_cr_auto_split_enabled($config, $visit_type = null) {
        if (empty($config) || !is_array($config)) return true;

        if ($visit_type === 'OPD') {
            if (isset($config['auto_split_opd_enabled'])) {
                return ($config['auto_split_opd_enabled'] !== false);
            }
            return ($config['auto_split_enabled'] ?? true) !== false;
        }

        if ($visit_type === 'IPD') {
            if (isset($config['auto_split_ipd_enabled'])) {
                return ($config['auto_split_ipd_enabled'] !== false);
            }
            return ($config['auto_split_enabled'] ?? true) !== false;
        }

        $opd = isset($config['auto_split_opd_enabled']) ? ($config['auto_split_opd_enabled'] !== false) : ($config['auto_split_enabled'] ?? true);
        $ipd = isset($config['auto_split_ipd_enabled']) ? ($config['auto_split_ipd_enabled'] !== false) : ($config['auto_split_enabled'] ?? true);
        return ($opd || $ipd);
    }
}

if (!function_exists('is_cr_subgroup_enabled')) {
    /**
     * ตรวจสอบว่ากลุ่มย่อย CR นั้นๆ เปิดใช้งานอยู่หรือไม่ แยกตาม OPD และ IPD
     * รองรับการตรวจสอบขอบเขตเดือนของกลุ่มย่อย (Subgroup Effective Month) เมื่อส่ง $monthtxt
     */
    function is_cr_subgroup_enabled($config, $subgroup_id, $visit_type = 'OPD', $monthtxt = null) {
        if (!empty($monthtxt)) {
            return is_cr_subgroup_effective_for_month($config, $subgroup_id, $visit_type, $monthtxt);
        }
        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        if (!isset($config[$group_key]) || !isset($config[$group_key][$subgroup_id])) {
            if (isset($config['subgroups'][$subgroup_id])) {
                return ($config['subgroups'][$subgroup_id]['enabled'] !== false);
            }
            return false;
        }
        if (!isset($config[$group_key][$subgroup_id]['enabled'])) {
            return true;
        }
        return ($config[$group_key][$subgroup_id]['enabled'] !== false);
    }
}

if (!function_exists('is_cr_parent_account_enabled')) {
    /**
     * ตรวจสอบว่าผังแม่ CR นั้นๆ เปิดใช้งานอยู่หรือไม่
     */
    function is_cr_parent_account_enabled($config, $accountcode) {
        if (empty($config) || !is_array($config)) return true;
        if (isset($config['parent_accounts']) && isset($config['parent_accounts'][$accountcode])) {
            return ($config['parent_accounts'][$accountcode]['enabled'] !== false);
        }
        return true;
    }
}

if (!function_exists('get_hosxp_hospital_code')) {
    /**
     * ดึงรหัสสถานพยาบาลหลัก (hospcode) จากตาราง opdconfig ในฐานข้อมูล HOSxP ($conn2) พร้อมแคช
     */
    function get_hosxp_hospital_code($conn2) {
        static $cached_hospcode = null;
        if ($cached_hospcode !== null) return $cached_hospcode;
        if (!$conn2) return '';
        $q = @mysqli_query($conn2, "SELECT hospitalcode FROM opdconfig LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) {
            $cached_hospcode = trim($r['hospitalcode']);
            return $cached_hospcode;
        }
        return '';
    }
}

if (!function_exists('get_hosxp_hospital_name')) {
    /**
     * ดึงชื่อโรงพยาบาลจากตาราง opdconfig ในฐานข้อมูล HOSxP ($conn2) พร้อมแคช
     */
    function get_hosxp_hospital_name($conn2) {
        static $cached_hospname = null;
        if ($cached_hospname !== null) return $cached_hospname;
        if (!$conn2) return '';
        $q = @mysqli_query($conn2, "SELECT hospitalname FROM opdconfig LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) {
            $cached_hospname = trim($r['hospitalname']);
            return $cached_hospname;
        }
        return '';
    }
}

if (!function_exists('get_hosxp_hospital_chwpart')) {
    /**
     * ดึงรหัสจังหวัด (chwpart เช่น 45) ของโรงพยาบาลจากตาราง hospcode ในฐานข้อมูล HOSxP ($conn2) พร้อมแคช
     */
    function get_hosxp_hospital_chwpart($conn2, $my_hospcode = '') {
        static $cached_chwpart = null;
        if ($cached_chwpart !== null) return $cached_chwpart;
        if (!$conn2) return '';
        if (empty($my_hospcode)) {
            $my_hospcode = get_hosxp_hospital_code($conn2);
        }
        if (empty($my_hospcode)) return '';
        $hosp_esc = mysqli_real_escape_string($conn2, $my_hospcode);
        $q = @mysqli_query($conn2, "SELECT chwpart FROM hospcode WHERE hospcode = '$hosp_esc' LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) {
            $cached_chwpart = trim($r['chwpart']);
            return $cached_chwpart;
        }
        return '';
    }
}

if (!function_exists('get_hosxp_hospital_province_name')) {
    /**
     * ดึงชื่อจังหวัดของโรงพยาบาลจากตาราง hospcode ในฐานข้อมูล HOSxP ($conn2) พร้อมแคช
     */
    function get_hosxp_hospital_province_name($conn2, $my_hospcode = '') {
        static $cached_provname = null;
        if ($cached_provname !== null) return $cached_provname;
        if (!$conn2) return '';
        if (empty($my_hospcode)) {
            $my_hospcode = get_hosxp_hospital_code($conn2);
        }
        if (empty($my_hospcode)) return '';
        $hosp_esc = mysqli_real_escape_string($conn2, $my_hospcode);
        $q = @mysqli_query($conn2, "SELECT province_name FROM hospcode WHERE hospcode = '$hosp_esc' LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) {
            $cached_provname = trim($r['province_name']);
            return $cached_provname;
        }
        return '';
    }
}

if (!function_exists('detect_cr_types_for_visit_list')) {
    /**
     * ตรวจจับและจัดกลุ่ม CR ให้กับชุด Visit (OPD/IPD) จากฐานข้อมูล HOSxP ($conn2)
     * 
     * @param mysqli $conn2 การเชื่อมต่อฐานข้อมูล HOSxP
     * @param array $vn_list รายการ VN หรือ AN
     * @param string $visit_type 'OPD' หรือ 'IPD'
     * @param array $config การตั้งค่า CR Config
     * @param string $my_hospcode รหัสโรงพยาบาลของหน่วยบริการนี้
     * @return array ข้อมูลการแมป [vn => ['cr_types' => ['CR-Instrument', ...], 'items' => [...]]]
     */
    function detect_cr_types_for_visit_list($conn2, $vn_list, $visit_type = 'OPD', $config = null, $my_hospcode = '', $monthtxt = null) {
        if (!$conn2 || empty($vn_list)) return [];
        if (!$config) $config = get_default_cr_config();

        // หากไม่ได้ส่ง my_hospcode มา ให้ดึงจาก opdconfig อัตโนมัติ
        if (empty($my_hospcode) && $conn2) {
            $my_hospcode = get_hosxp_hospital_code($conn2);
        }

        // ดึงรหัสจังหวัดของหน่วยบริการนี้ (ใช้สำหรับตรวจสอบ Cross Region ต่างจังหวัด/ต่างเขต ตามเกณฑ์ข้อ 3)
        $cfg_walkin = isset($config['subgroups_opd']['CR-walkin']) ? $config['subgroups_opd']['CR-walkin'] : [];
        $my_chwpart = !empty($cfg_walkin['hospital_chwpart']) ? trim($cfg_walkin['hospital_chwpart']) : get_hosxp_hospital_chwpart($conn2, $my_hospcode);
        $is_cross_region_only = !isset($cfg_walkin['check_cross_region_only']) || !empty($cfg_walkin['check_cross_region_only']);
        $check_adp_walkin = !isset($cfg_walkin['check_adp_walkin']) || !empty($cfg_walkin['check_adp_walkin']);

        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        $subgroups_cfg = isset($config[$group_key]) ? $config[$group_key] : [];

        // 🌟 ตรวจสอบความมีผลของแต่ละกลุ่มย่อยตามขอบเขตเดือน (Subgroup Effective Month)
        $is_inst_enabled = is_cr_subgroup_effective_for_month($config, 'CR-Instrument', $visit_type, $monthtxt);
        $inst_cfg = isset($subgroups_cfg['CR-Instrument']) ? $subgroups_cfg['CR-Instrument'] : [];
        if (empty($inst_cfg) && isset($config['subgroups']['CR-Instrument'])) {
            $inst_cfg = $config['subgroups']['CR-Instrument'];
        }
        $inst_adps = !empty($inst_cfg['custom_adp_codes']) ? array_values(array_filter(array_map('trim', array_map('strval', $inst_cfg['custom_adp_codes'])))) : ["7004", "7005", "8612", "8813", "8814"];
        $inst_icodes = !empty($inst_cfg['custom_icodes']) ? array_values(array_filter(array_map('trim', array_map('strval', $inst_cfg['custom_icodes'])))) : [];
        $auto_adp2 = !isset($inst_cfg['auto_adp_type_2']) || ($inst_cfg['auto_adp_type_2'] !== false);
        $is_walkin_enabled = ($visit_type === 'OPD') && is_cr_subgroup_effective_for_month($config, 'CR-walkin', 'OPD', $monthtxt);
        $is_ae_enabled = is_cr_subgroup_effective_for_month($config, 'CR-AE', $visit_type, $monthtxt);
        $is_newborn_enabled = is_cr_subgroup_effective_for_month($config, 'CR-เกิดสิทธิทันที', $visit_type, $monthtxt);
        $is_pall_enabled = ($visit_type === 'OPD') && is_cr_subgroup_effective_for_month($config, 'CR-palliative', 'OPD', $monthtxt);
        $is_tele_enabled = ($visit_type === 'OPD') && is_cr_subgroup_effective_for_month($config, 'CR-Tele', 'OPD', $monthtxt);
        $tele_adp_codes = !empty($config['subgroups_opd']['CR-Tele']['adp_codes']) ? array_map('strtoupper', $config['subgroups_opd']['CR-Tele']['adp_codes']) : ['TELMED'];
        $tele_icodes = !empty($config['subgroups_opd']['CR-Tele']['custom_icodes']) ? $config['subgroups_opd']['CR-Tele']['custom_icodes'] : [];
        $tele_ovst_codes = !empty($config['subgroups_opd']['CR-Tele']['ovstist_export_codes']) ? $config['subgroups_opd']['CR-Tele']['ovstist_export_codes'] : ['5'];

        $is_clopi_enabled = ($visit_type === 'OPD') && is_cr_subgroup_effective_for_month($config, 'CR-ยาclopi', 'OPD', $monthtxt);
        $is_herb_enabled = ($visit_type === 'OPD') && is_cr_subgroup_effective_for_month($config, 'CR-ยาสมุนไพร', 'OPD', $monthtxt);
        $is_refer_enabled = ($visit_type === 'IPD') && is_cr_subgroup_effective_for_month($config, 'CR-รถรีเฟอร์', 'IPD', $monthtxt);

        $vns_with_adp_walkin = [];

        $escaped_vns = [];
        foreach ($vn_list as $v) {
            $escaped_vns[] = "'" . mysqli_real_escape_string($conn2, trim($v)) . "'";
        }
        $in_sql = implode(",", $escaped_vns);

        $results = [];
        foreach ($vn_list as $v) {
            $results[$v] = [
                'cr_types' => [],
                'cr_total_amount' => 0.0,
                'items' => []
            ];
        }

        // -------------------------------------------------------------
        // 1. ตรวจจับจากรายการยาและเวชภัณฑ์ (opitemrece + nondrugitems + drugitems)
        // -------------------------------------------------------------
        $pk_field = ($visit_type === 'IPD') ? 'oo.an' : 'oo.vn';
        $sql_items = "
            SELECT 
                $pk_field AS vn,
                oo.icode,
                oo.qty,
                oo.unitprice,
                oo.sum_price,
                nd.name AS nondrug_name,
                nd.nhso_adp_type_id AS nondrug_adp_type,
                nd.nhso_adp_code AS nondrug_adp_code,
                d.name AS drug_name,
                d.strength,
                d.nhso_adp_type_id AS drug_adp_type,
                d.nhso_adp_code AS drug_adp_code,
                d.did,
                d.ttmt_code,
                d.sks_product_category_id
            FROM opitemrece oo
            LEFT JOIN nondrugitems nd ON nd.icode = oo.icode
            LEFT JOIN drugitems d ON d.icode = oo.icode
            WHERE $pk_field IN ($in_sql)
        ";

        $q_items = @mysqli_query($conn2, $sql_items);
        if ($q_items && mysqli_num_rows($q_items) > 0) {
            while ($row = mysqli_fetch_assoc($q_items)) {
                $vn = $row['vn'];
                $sum_price = floatval($row['sum_price']);
                $item_name = !empty($row['nondrug_name']) ? $row['nondrug_name'] : (!empty($row['drug_name']) ? $row['drug_name'] . ' ' . $row['strength'] : 'รายการ #' . $row['icode']);
                
                // ตรวจ CR-Instrument (ADP Type 2 หรือ matching ADP codes / custom icodes) ทั้ง OPD และ IPD
                if ($is_inst_enabled) {
                    $is_inst_match = false;
                    $adp_code = trim((string)($row['nondrug_adp_code'] ?: $row['drug_adp_code']));
                    $adp_type = trim((string)($row['nondrug_adp_type'] ?: $row['drug_adp_type']));
                    $icode = trim((string)$row['icode']);

                    // 1. ตรวจจับอัตโนมัติหากเปิด auto_adp_type_2 และเป็นหมวด 2
                    if ($auto_adp2 && ($adp_type === '2')) {
                        $is_inst_match = true;
                    }
                    // 2. ตรวจจับจากรหัส ADP พิเศษที่ระบุใน Config (ไม่มี regex แทรก เพื่อให้สามารถลบรหัสที่ไม่ต้องการออกได้จริง)
                    if (!$is_inst_match && $adp_code !== '' && !empty($inst_adps)) {
                        if (in_array($adp_code, $inst_adps)) {
                            $is_inst_match = true;
                        }
                    }
                    // 3. ตรวจจับจากรหัส icode ยา/เวชภัณฑ์ที่ระบุ (ถ้ามี)
                    if (!$is_inst_match && $icode !== '' && !empty($inst_icodes) && in_array($icode, $inst_icodes)) {
                        $is_inst_match = true;
                    }

                    if ($is_inst_match) {
                        if (!in_array('CR-Instrument', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-Instrument';
                        $results[$vn]['cr_total_amount'] += $sum_price;
                        $results[$vn]['items'][] = [
                            'cr_type' => 'CR-Instrument',
                            'icode' => $row['icode'],
                            'item_name' => $item_name,
                            'adp_type' => $adp_type ?: '2',
                            'adp_code' => $adp_code ?: '7004',
                            'qty' => floatval($row['qty']),
                            'unit_price' => floatval($row['unitprice']),
                            'amount' => $sum_price
                        ];
                    }
                }

                // 🌟 ตรวจ CR-รถรีเฟอร์ (เฉพาะ IPD)
                if ($visit_type === 'IPD' && $is_refer_enabled) {
                    $refer_cfg = isset($subgroups_cfg['CR-รถรีเฟอร์']) ? $subgroups_cfg['CR-รถรีเฟอร์'] : [];
                    $refer_adps = !empty($refer_cfg['custom_adp_codes']) ? $refer_cfg['custom_adp_codes'] : ["S1801", "S1802", "COVV01", "55999", "REFER"];
                    $refer_icodes = !empty($refer_cfg['custom_icodes']) ? $refer_cfg['custom_icodes'] : ["3900320", "3002101", "3900188", "3900189", "3900190", "3900191", "3900192", "3900279", "3900288", "3900309", "3900498", "3900612", "3900614"];
                    
                    $is_refer_match = false;
                    if (in_array((string)$row['nondrug_adp_code'], $refer_adps)) $is_refer_match = true;
                    if (in_array((string)$row['icode'], $refer_icodes)) $is_refer_match = true;
                    if (!$is_refer_match && !empty($row['nondrug_name'])) {
                        if (preg_match('/รถพยาบาลส่งต่อ|refer|รถส่งต่อ|รถรีเฟอร์/i', $row['nondrug_name'])) {
                            $is_refer_match = true;
                        }
                    }

                    if ($is_refer_match) {
                        if (!in_array('CR-รถรีเฟอร์', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-รถรีเฟอร์';
                        $results[$vn]['cr_total_amount'] += $sum_price;
                        $results[$vn]['items'][] = [
                            'cr_type' => 'CR-รถรีเฟอร์',
                            'icode' => $row['icode'],
                            'item_name' => $item_name,
                            'adp_type' => $row['nondrug_adp_type'] ?: '5',
                            'adp_code' => $row['nondrug_adp_code'] ?: 'REFER',
                            'qty' => floatval($row['qty']),
                            'unit_price' => floatval($row['unitprice']),
                            'amount' => $sum_price
                        ];
                    }
                }

                // ตรวจ CR-Tele (TELMED / export 5 / custom icodes) (OPD)
                $match_tele_adp = (!empty($tele_adp_codes) && (in_array(strtoupper((string)$row['nondrug_adp_code']), $tele_adp_codes) || in_array(strtoupper((string)$row['drug_adp_code']), $tele_adp_codes)));
                $match_tele_icode = (!empty($tele_icodes) && in_array((string)$row['icode'], $tele_icodes));

                if ($is_tele_enabled && ($match_tele_adp || $match_tele_icode)) {
                    if (!in_array('CR-Tele', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-Tele';
                    $results[$vn]['cr_total_amount'] += $sum_price;
                    $results[$vn]['items'][] = [
                        'cr_type' => 'CR-Tele',
                        'icode' => $row['icode'],
                        'item_name' => $item_name,
                        'adp_type' => '1',
                        'adp_code' => !empty($row['nondrug_adp_code']) ? $row['nondrug_adp_code'] : ($row['drug_adp_code'] ?: 'TELMED'),
                        'qty' => floatval($row['qty']),
                        'unit_price' => floatval($row['unitprice']),
                        'amount' => $sum_price
                    ];
                }

                // ตรวจ CR-ยาclopi (OPD)
                if ($is_clopi_enabled && (
                    preg_match('/Clopidrogrel|Clopidogrel/i', (string)$row['drug_name']) || 
                    $row['drug_adp_code'] === '3799977101' || 
                    strpos((string)$row['did'], '1248460000039721217') === 0
                )) {
                    if (!in_array('CR-ยาclopi', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-ยาclopi';
                    $results[$vn]['cr_total_amount'] += $sum_price;
                    $results[$vn]['items'][] = [
                        'cr_type' => 'CR-ยาclopi',
                        'icode' => $row['icode'],
                        'item_name' => $item_name,
                        'adp_type' => '3',
                        'adp_code' => $row['drug_adp_code'],
                        'qty' => floatval($row['qty']),
                        'unit_price' => floatval($row['unitprice']),
                        'amount' => $sum_price
                    ];
                }

                // ตรวจ CR-ยาสมุนไพร (OPD)
                if ($is_herb_enabled && (
                    !empty($row['ttmt_code']) || 
                    in_array(intval($row['sks_product_category_id']), [3, 4]) || 
                    $row['drug_adp_type'] == '4'
                )) {
                    if (!in_array('CR-ยาสมุนไพร', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-ยาสมุนไพร';
                    $results[$vn]['cr_total_amount'] += $sum_price;
                    $results[$vn]['items'][] = [
                        'cr_type' => 'CR-ยาสมุนไพร',
                        'icode' => $row['icode'],
                        'item_name' => $item_name,
                        'adp_type' => '4',
                        'adp_code' => $row['drug_adp_code'],
                        'qty' => floatval($row['qty']),
                        'unit_price' => floatval($row['unitprice']),
                        'amount' => $sum_price
                    ];
                }

                // บันทึกว่า Visit นี้มีรหัส ADP 'WALKIN' หรือไม่ (สำหรับใช้ประเมินร่วมกับเงื่อนไข Cross Region)
                if ($is_walkin_enabled && (
                    strtoupper((string)($row['nondrug_adp_code'] ?? '')) === 'WALKIN' || 
                    strtoupper((string)($row['drug_adp_code'] ?? '')) === 'WALKIN'
                )) {
                    $vns_with_adp_walkin[$vn] = true;
                }

                // ตรวจกลุ่มย่อยกำหนดเอง (Custom Subgroups ตาม visit_type)
                if (!empty($subgroups_cfg) && is_array($subgroups_cfg)) {
                    $standard_keys = ['CR-Instrument', 'CR-walkin', 'CR-AE', 'CR-เกิดสิทธิทันที', 'CR-palliative', 'CR-Tele', 'CR-ยาclopi', 'CR-ยาสมุนไพร', 'CR-รถรีเฟอร์'];
                    foreach ($subgroups_cfg as $sg_id => $sg_cfg) {
                        if (in_array($sg_id, $standard_keys)) continue;
                        if (!is_cr_subgroup_effective_for_month($config, $sg_id, $visit_type, $monthtxt)) continue;

                        $custom_adps = !empty($sg_cfg['custom_adp_codes']) ? (array)$sg_cfg['custom_adp_codes'] : [];
                        $custom_icodes = !empty($sg_cfg['custom_icodes']) ? (array)$sg_cfg['custom_icodes'] : [];
                        
                        $is_custom_matched = false;
                        if (!empty($custom_adps)) {
                            if (in_array((string)$row['nondrug_adp_code'], $custom_adps) || in_array((string)$row['drug_adp_code'], $custom_adps)) {
                                $is_custom_matched = true;
                            }
                        }
                        if (!empty($custom_icodes) && in_array((string)$row['icode'], $custom_icodes)) {
                            $is_custom_matched = true;
                        }

                        if ($is_custom_matched) {
                            if (!in_array($sg_id, $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = $sg_id;
                            $results[$vn]['cr_total_amount'] += $sum_price;
                            $results[$vn]['items'][] = [
                                'cr_type' => $sg_id,
                                'icode' => $row['icode'],
                                'item_name' => $item_name,
                                'adp_type' => $row['nondrug_adp_type'] ?: $row['drug_adp_type'],
                                'adp_code' => $row['nondrug_adp_code'] ?: $row['drug_adp_code'],
                                'qty' => floatval($row['qty']),
                                'unit_price' => floatval($row['unitprice']),
                                'amount' => $sum_price
                            ];
                        }
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // 2. ตรวจจับ CR-Palliative, CR-AE, CR-เกิดสิทธิทันที, CR-walkin จากตาราง Visit
        // -------------------------------------------------------------
        if ($visit_type === 'OPD') {
            $sql_visit = "
                SELECT 
                    o.vn,
                    o.hn,
                    o.hospmain,
                    o.pt_subtype,
                    o.pttype,
                    pty.name AS pttypename,
                    TIMESTAMPDIFF(DAY, pt.birthday, o.vstdate) AS age_days,
                    ov.export_code AS ovstist_export,
                    dep.department AS dep_name,
                    sp.name AS spclty_name,
                    er.vn AS is_er,
                    ho_main.chwpart AS hospmain_chwpart,
                    ho_main.province_name AS hospmain_province,
                    ho_main.name AS hospmain_name,
                    pt.chwpart AS pt_chwpart,
                    (SELECT GROUP_CONCAT(icd10) FROM ovstdiag WHERE vn = o.vn) AS diags
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn = o.hn
                LEFT JOIN hospcode ho_main ON ho_main.hospcode = o.hospmain
                LEFT JOIN pttype pty ON pty.pttype = o.pttype
                LEFT JOIN ovstist ov ON ov.ovstist = o.ovstist
                LEFT JOIN kskdepartment dep ON dep.depcode = o.main_dep
                LEFT JOIN spclty sp ON sp.spclty = o.spclty
                LEFT JOIN er_regist er ON er.vn = o.vn
                WHERE o.vn IN ($in_sql)
            ";
        } else {
            $sql_visit = "
                SELECT 
                    i.an AS vn,
                    i.hn,
                    i.pttype,
                    pty.name AS pttypename,
                    TIMESTAMPDIFF(DAY, pt.birthday, i.regdate) AS age_days,
                    w.name AS ward_name,
                    (SELECT GROUP_CONCAT(icd10) FROM iptdiag WHERE an = i.an) AS diags
                FROM ipt i
                LEFT JOIN patient pt ON pt.hn = i.hn
                LEFT JOIN pttype pty ON pty.pttype = i.pttype
                LEFT JOIN ward w ON w.ward = i.ward
                WHERE i.an IN ($in_sql)
            ";
        }

        $q_visit = @mysqli_query($conn2, $sql_visit);
        if ($q_visit && mysqli_num_rows($q_visit) > 0) {
            while ($vrow = mysqli_fetch_assoc($q_visit)) {
                $vn = $vrow['vn'];
                $diags = (string)$vrow['diags'];

                // ตรวจ Palliative (ICD-10 Z515 / Z51.5)
                if ($is_pall_enabled && preg_match('/Z515|Z51\.5/i', $diags)) {
                    if (!in_array('CR-palliative', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-palliative';
                    $results[$vn]['items'][] = [
                        'cr_type' => 'CR-palliative',
                        'icode' => 'Z515',
                        'item_name' => 'บริการดูแลผู้ป่วยระยะประคับประคอง (' . $diags . ')',
                        'adp_type' => '1',
                        'adp_code' => 'Z515',
                        'qty' => 1,
                        'unit_price' => 0.0,
                        'amount' => 0.0
                    ];
                }

                // 🌟 ตรวจ CR-เกิดสิทธิทันที (อ่านเกณฑ์อายุและ pt_subtype จาก Config)
                if ($is_newborn_enabled) {
                    $newborn_cfg = isset($subgroups_cfg['CR-เกิดสิทธิทันที']) ? $subgroups_cfg['CR-เกิดสิทธิทันที'] : [];
                    $max_age = isset($newborn_cfg['max_age_days']) ? intval($newborn_cfg['max_age_days']) : 28;
                    $newborn_subtypes = !empty($newborn_cfg['ptsubtypes']) ? $newborn_cfg['ptsubtypes'] : ['เด็กแรกเกิด', 'Newborn', 'NEWBORN'];
                    $age_days = isset($vrow['age_days']) ? intval($vrow['age_days']) : 999;
                    $subtype = isset($vrow['pt_subtype']) ? (string)$vrow['pt_subtype'] : '';

                    $is_newborn_match = ($age_days >= 0 && $age_days <= $max_age);
                    if (!$is_newborn_match && !empty($subtype)) {
                        foreach ($newborn_subtypes as $st) {
                            if (!empty($st) && mb_stripos($subtype, trim($st)) !== false) {
                                $is_newborn_match = true;
                                break;
                            }
                        }
                    }
                    if ($is_newborn_match) {
                        if (!in_array('CR-เกิดสิทธิทันที', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-เกิดสิทธิทันที';
                        $has_nb_item = false;
                        foreach ($results[$vn]['items'] as $it) {
                            if ($it['cr_type'] === 'CR-เกิดสิทธิทันที') { $has_nb_item = true; break; }
                        }
                        if (!$has_nb_item) {
                            $results[$vn]['items'][] = [
                                'cr_type' => 'CR-เกิดสิทธิทันที',
                                'icode' => 'NEWBORN',
                                'item_name' => 'บริการเด็กแรกเกิด/เปลี่ยนสิทธิแรกเกิด (อายุ ' . $age_days . ' วัน)',
                                'adp_type' => '1',
                                'adp_code' => 'NEWBORN',
                                'qty' => 1,
                                'unit_price' => 0.0,
                                'amount' => 0.0
                            ];
                        }
                    }
                }

                // 🌟 ตรวจสอบสิทธิการรักษาของ Visit เพื่อใช้แยกเคส CR-walkin และ CR-AE
                $visit_pttype = trim((string)($vrow['pttype'] ?? ''));
                $visit_pttypename = trim((string)($vrow['pttypename'] ?? ''));

                // 1. ตรวจสอบว่าสิทธิของ Visit นี้ตรงกับกลุ่ม CR-walkin หรือไม่
                $walkin_pttypes = !empty($cfg_walkin['pttypes']) ? (array)$cfg_walkin['pttypes'] : ['23'];
                $walkin_pttypenames = !empty($cfg_walkin['pttypenames']) ? (array)$cfg_walkin['pttypenames'] : ['Walkin', 'walkin', 'WALKIN'];
                $is_walkin_pttype_match = false;
                if ($is_walkin_enabled && !empty($visit_pttype) && !empty($walkin_pttypes) && in_array($visit_pttype, $walkin_pttypes)) {
                    $is_walkin_pttype_match = true;
                }
                if ($is_walkin_enabled && !$is_walkin_pttype_match && !empty($visit_pttypename) && !empty($walkin_pttypenames)) {
                    foreach ($walkin_pttypenames as $kword) {
                        if (!empty($kword) && mb_stripos($visit_pttypename, trim($kword)) !== false) {
                            $is_walkin_pttype_match = true;
                            break;
                        }
                    }
                }

                // 2. ตรวจสอบว่าสิทธิของ Visit นี้ตรงกับกลุ่ม CR-AE หรือไม่
                $ae_cfg = isset($subgroups_cfg['CR-AE']) ? $subgroups_cfg['CR-AE'] : [];
                $ae_pttypes = !empty($ae_cfg['pttypes']) ? (array)$ae_cfg['pttypes'] : ['98', '97', '91', 'AM'];
                $ae_pttypenames = !empty($ae_cfg['pttypenames']) ? (array)$ae_cfg['pttypenames'] : ['ฉุกเฉิน', 'อุบัติเหตุ', 'AE'];
                $is_ae_pttype_match = false;
                if ($is_ae_enabled && !empty($visit_pttype) && !empty($ae_pttypes) && in_array($visit_pttype, $ae_pttypes)) {
                    $is_ae_pttype_match = true;
                }
                if ($is_ae_enabled && !$is_ae_pttype_match && !empty($visit_pttypename) && !empty($ae_pttypenames)) {
                    foreach ($ae_pttypenames as $kword) {
                        if (!empty($kword) && mb_stripos($visit_pttypename, trim($kword)) !== false) {
                            $is_ae_pttype_match = true;
                            break;
                        }
                    }
                }

                // 🌟 ตรวจ CR-AE (อุบัติเหตุ/ฉุกเฉินเฉพาะจุด: หากเป็นสิทธิ Walk-in โดยตรง จะไม่ถูกจัดเข้า CR-AE)
                if ($visit_type === 'OPD' && $is_ae_enabled && !$is_walkin_pttype_match) {
                    $ae_deps = !empty($ae_cfg['departments']) ? $ae_cfg['departments'] : ['ER', 'ฉุกเฉิน', 'ห้องอุบัติเหตุ-ฉุกเฉิน'];
                    $ae_cross_region_only = !isset($ae_cfg['check_cross_region_only']) || !empty($ae_cfg['check_cross_region_only']);
                    $ae_my_chwpart = !empty($ae_cfg['hospital_chwpart']) ? trim($ae_cfg['hospital_chwpart']) : $my_chwpart;

                    $dep_name = (string)($vrow['dep_name'] ?? '');
                    $is_er = !empty($vrow['is_er']);
                    $is_ae_match = $is_ae_pttype_match || $is_er || ($vrow['ovstist_export'] === '1');

                    // ตรวจจากชื่อแผนก (หากยังไม่ตรงจากสิทธิหรือห้อง ER)
                    if (!$is_ae_match && !empty($dep_name)) {
                        foreach ($ae_deps as $dep) {
                            if (!empty($dep) && mb_stripos($dep_name, trim($dep)) !== false) {
                                $is_ae_match = true;
                                break;
                            }
                        }
                    }

                    // เงื่อนไขต่างจังหวัด (Cross Region): คัดเอาเฉพาะเคสต่างจังหวัดเหมือนกัน เน้นใช้สำหรับเคสฉุกเฉินที่ข้ามจังหวัด
                    $hospmain = trim((string)($vrow['hospmain'] ?? ''));
                    $hospmain_chw = trim((string)($vrow['hospmain_chwpart'] ?? ''));
                    $pt_chw = trim((string)($vrow['pt_chwpart'] ?? ''));
                    $target_chw = !empty($hospmain_chw) ? $hospmain_chw : $pt_chw;
                    $hospmain_prov = trim((string)($vrow['hospmain_province'] ?? ''));
                    $hospmain_name = trim((string)($vrow['hospmain_name'] ?? ''));

                    if ($is_ae_match && $ae_cross_region_only) {
                        // หากเป็นผู้ป่วยใน CUP ตัวเอง -> ไม่เข้า CR-AE (คงไว้ในผัง OP เหมาจ่ายตามปกติ)
                        if (empty($hospmain) || $hospmain === $my_hospcode) {
                            $is_ae_match = false;
                        } elseif (!empty($ae_my_chwpart) && !empty($target_chw) && $target_chw === $ae_my_chwpart) {
                            // หากเป็นผู้ป่วยต่าง CUP แต่ในจังหวัดเดียวกัน -> ไม่เข้า CR-AE (คงไว้ในผัง นค. ในจังหวัด .203)
                            $is_ae_match = false;
                        }
                    }

                    if ($is_ae_match) {
                        if (!in_array('CR-AE', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-AE';
                        $has_ae_item = false;
                        foreach ($results[$vn]['items'] as $it) {
                            if ($it['cr_type'] === 'CR-AE') { $has_ae_item = true; break; }
                        }
                        if (!$has_ae_item) {
                            $prov_label = !empty($hospmain_prov) ? " ($hospmain_prov)" : (!empty($target_chw) ? " (จว. $target_chw)" : "");
                            $name_label = !empty($hospmain_name) ? " " . $hospmain_name : "";
                            $hosp_info = !empty($hospmain) ? " Hospmain: {$hospmain}{$name_label}{$prov_label}" : "";
                            $results[$vn]['items'][] = [
                                'cr_type' => 'CR-AE',
                                'icode' => 'ER/AE',
                                'item_name' => 'บริการผู้ป่วยอุบัติเหตุ/ฉุกเฉินเฉพาะจุด' . ($ae_cross_region_only ? ' ข้ามจังหวัด' : '') . ' (' . ($dep_name ?: 'ห้องฉุกเฉิน ER') . $hosp_info . ')',
                                'adp_type' => '1',
                                'adp_code' => 'AE',
                                'qty' => 1,
                                'unit_price' => 0.0,
                                'amount' => 0.0
                            ];
                        }
                    }
                }

                // 🌟 ตรวจ CR-AE สำหรับ IPD (เมื่อเข้าเงื่อนไขสิทธิการรักษา AE หรืออุบัติเหตุ)
                if ($visit_type === 'IPD' && $is_ae_enabled && $is_ae_pttype_match) {
                    if (!in_array('CR-AE', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-AE';
                    $has_ae_item = false;
                    foreach ($results[$vn]['items'] as $it) {
                        if ($it['cr_type'] === 'CR-AE') { $has_ae_item = true; break; }
                    }
                    if (!$has_ae_item) {
                        $results[$vn]['items'][] = [
                            'cr_type' => 'CR-AE',
                            'icode' => 'AE_IPD',
                            'item_name' => 'บริการผู้ป่วยอุบัติเหตุ/ฉุกเฉินเฉพาะจุด IPD (' . ($visit_pttypename ?: $visit_pttype) . ')',
                            'adp_type' => '1',
                            'adp_code' => 'AE',
                            'qty' => 1,
                            'unit_price' => 0.0,
                            'amount' => 0.0
                        ];
                    }
                }

                // ---------------------------------------------------------
                // ตรวจ CR-walkin: ตามสิทธิการรักษา (pttype) หรือ เกณฑ์ข้อ 3 (Cross Region ต่างจังหวัด/ต่างเขต)
                // ---------------------------------------------------------
                // หากเป็นสิทธิ AE โดยตรง ($is_ae_pttype_match) จะไม่จัดเข้า CR-walkin!
                // ---------------------------------------------------------
                if ($visit_type === 'OPD' && $is_walkin_enabled && !$is_ae_pttype_match) {
                    $is_already_ae = in_array('CR-AE', $results[$vn]['cr_types']);

                    if (!$is_already_ae) {
                        $hospmain = trim((string)($vrow['hospmain'] ?? ''));
                        $hospmain_chw = trim((string)($vrow['hospmain_chwpart'] ?? ''));
                        $pt_chw = trim((string)($vrow['pt_chwpart'] ?? ''));
                        $target_chw = !empty($hospmain_chw) ? $hospmain_chw : $pt_chw;
                        $hospmain_prov = trim((string)($vrow['hospmain_province'] ?? ''));
                        $hospmain_name = trim((string)($vrow['hospmain_name'] ?? ''));
                        $has_adp_walkin_item = !empty($vns_with_adp_walkin[$vn]);

                        $is_match_walkin = false;

                        // 1. ตรวจจับจากสิทธิการรักษา Walk-in (pttype/pttypename) โดยตรง
                        if ($is_walkin_pttype_match) {
                            $is_match_walkin = true;
                        } else {
                            // 2. ตรวจสอบตามเกณฑ์ข้อ 3 (ผู้ป่วยต่างจังหวัด / ต่างเขต Cross Region หรือรหัส ADP 'WALKIN')
                            if ($is_cross_region_only) {
                                if (!empty($hospmain) && $hospmain !== $my_hospcode) {
                                    if (!empty($my_chwpart) && !empty($target_chw)) {
                                        if ($target_chw !== $my_chwpart) {
                                            $is_match_walkin = true;
                                        }
                                    } elseif (!empty($my_chwpart) && empty($target_chw)) {
                                        if ($check_adp_walkin && $has_adp_walkin_item) {
                                            $is_match_walkin = true;
                                        }
                                    }
                                }
                            } else {
                                if (!empty($my_hospcode) && !empty($hospmain) && $hospmain !== $my_hospcode) {
                                    $is_match_walkin = true;
                                } elseif ($check_adp_walkin && $has_adp_walkin_item) {
                                    $is_match_walkin = true;
                                }
                            }
                        }

                        if ($is_match_walkin) {
                            if (!in_array('CR-walkin', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-walkin';
                            $has_walkin_item = false;
                            foreach ($results[$vn]['items'] as $it) {
                                if ($it['cr_type'] === 'CR-walkin') { $has_walkin_item = true; break; }
                            }
                            if (!$has_walkin_item) {
                                $prov_label = !empty($hospmain_prov) ? " ($hospmain_prov)" : (!empty($target_chw) ? " (จว. $target_chw)" : " (ต่างจังหวัด)");
                                $name_label = !empty($hospmain_name) ? " " . $hospmain_name : "";
                                $match_reason = $is_walkin_pttype_match ? 'ตามสิทธิการรักษา (' . ($visit_pttypename ?: $visit_pttype) . ')' : 'ต่างจังหวัด/ต่างเขต';
                                $results[$vn]['items'][] = [
                                    'cr_type' => 'CR-walkin',
                                    'icode' => 'WALKIN',
                                    'item_name' => 'บริการ Walk-in ' . $match_reason . ' (Hospmain: ' . $hospmain . $name_label . $prov_label . ')',
                                    'adp_type' => '1',
                                    'adp_code' => 'WALKIN',
                                    'qty' => 1,
                                    'unit_price' => 0.0,
                                    'amount' => 0.0
                                ];
                            }
                        }
                    }
                }

                // ตรวจ Tele จาก export_code
                if ($visit_type === 'OPD' && $is_tele_enabled) {
                    if (!empty($tele_ovst_codes) && in_array((string)$vrow['ovstist_export'], $tele_ovst_codes)) {
                        if (!in_array('CR-Tele', $results[$vn]['cr_types'])) $results[$vn]['cr_types'][] = 'CR-Tele';
                        $has_tele_item = false;
                        foreach ($results[$vn]['items'] as $it) {
                            if ($it['cr_type'] === 'CR-Tele') { $has_tele_item = true; break; }
                        }
                        if (!$has_tele_item) {
                            $results[$vn]['items'][] = [
                                'cr_type' => 'CR-Tele',
                                'icode' => 'TELMED',
                                'item_name' => 'บริการการแพทย์ทางไกล / Telemedicine (ovstist: ' . ($vrow['ovstist_export'] ?: '5') . ')',
                                'adp_type' => '1',
                                'adp_code' => 'TELMED',
                                'qty' => 1,
                                'unit_price' => 0.0,
                                'amount' => 0.0
                            ];
                        }
                    }
                }
            }
        }

        return $results;
    }
}

if (!function_exists('get_cr_splitting_summary_for_month')) {
    /**
     * คำนวณสรุปการตัดแยกยอดเงิน CR ข้ามผังบัญชีสำหรับเดือนที่กำหนด (OPD/IPD)
     * 
     * @param mysqli $conn
     * @param mysqli $conn2
     * @param string $monthtxt
     * @param string $visit_type 'OPD' หรือ 'IPD'
     * @param array $config
     * @param string $my_hospcode
     * @return array
     */
    function get_cr_splitting_summary_for_month($conn, $conn2, $monthtxt, $visit_type = 'OPD', $config = null, $my_hospcode = '', $vn_filter = null) {
        static $cache = [];
        if (!$config) $config = get_active_cr_config($conn);
        $cfg_hash = md5(json_encode($config));
        $vn_hash = ($vn_filter !== null) ? md5(implode(',', (array)$vn_filter)) : 'all';
        $cache_key = "{$monthtxt}_{$visit_type}_{$cfg_hash}_{$vn_hash}";
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }
        $is_effective = is_cr_effective_for_month($config, $monthtxt);
        $auto_split = is_cr_auto_split_enabled($config, $visit_type);

        if (!$is_effective || !$auto_split || !$conn2 || ($vn_filter !== null && empty($vn_filter))) {
            $res = [
                'is_effective' => $is_effective,
                'auto_split' => $auto_split,
                'detected_map' => [],
                'transfers_by_account' => [],
                'target_account' => ($visit_type === 'OPD') ? '1102050101.216' : '1102050101.217',
                'cr_received_total' => 0.0,
                'cr_non_kidney_count' => 0,
                'cr_kidney_count' => 0,
                'cr_non_kidney_diff_count' => 0,
                'cr_kidney_diff_count' => 0,
                'cr_non_kidney_received_total' => 0.0,
                'cr_kidney_received_total' => 0.0,
                'cr_visits' => []
            ];
            $cache[$cache_key] = $res;
            return $res;
        }

        if ($visit_type === 'OPD') {
            $raw_parents = ['1102050101.201', '1102050101.209', '1102050101.203'];
            $parent_accounts = [];
            foreach ($raw_parents as $pa) {
                if (is_cr_parent_account_enabled($config, $pa)) {
                    $parent_accounts[] = $pa;
                }
            }
            $target_acc = '1102050101.216';
            $table = 'imr_tb_debtor_rights_opd';
            $pk = 'vn';
            $date_col = 'vstdate';
        } else {
            $raw_parents = ['1102050101.202'];
            $parent_accounts = [];
            foreach ($raw_parents as $pa) {
                if (is_cr_parent_account_enabled($config, $pa)) {
                    $parent_accounts[] = $pa;
                }
            }
            $target_acc = '1102050101.217';
            $table = 'imr_tb_debtor_rights_ipd';
            $pk = 'an';
            $date_col = 'dchdate';
        }

        $all_accs = array_merge($parent_accounts, [$target_acc]);
        $acc_in = "'" . implode("','", $all_accs) . "'";
        $escaped_month = mysqli_real_escape_string($conn, $monthtxt);

        $orig_debit_field = ($visit_type === 'OPD') ? ", original_debit" : "";
        $sql = "SELECT $pk, accountcode, debit $orig_debit_field, income, ptname, pttypename, pttype, $date_col AS vstdate FROM $table WHERE monthtxt = '$escaped_month' AND accountcode IN ($acc_in)";
        if ($vn_filter !== null && !empty($vn_filter)) {
            $escaped_filtered_vns = array_map(function($v) use ($conn) { return "'" . mysqli_real_escape_string($conn, trim($v)) . "'"; }, $vn_filter);
            $sql .= " AND $pk IN (" . implode(",", $escaped_filtered_vns) . ")";
        }
        $q = mysqli_query($conn, $sql);
        $all_vns = [];
        $visits_by_acc = [];
        $rows_data = [];

        if ($q && mysqli_num_rows($q) > 0) {
            while ($r = mysqli_fetch_assoc($q)) {
                $v = $r[$pk];
                $all_vns[] = $v;
                $visits_by_acc[$r['accountcode']][] = $r;
                $rows_data[$v] = $r;
            }
        }

        $detected_map = [];
        $comp_map = [];
        if (!empty($all_vns)) {
            $unique_vns = array_values(array_unique($all_vns));
            $detected_map = detect_cr_types_for_visit_list($conn2, $unique_vns, $visit_type, $config, $my_hospcode, $monthtxt);

            // ดึงยอดเงินชดเชย (follow_money + STM Statement) สำหรับทุก visit เพื่อจัดสรรเงินชดเชยตามผังบัญชี
            $escaped_all_vns = "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $unique_vns)) . "'";
            $stm_join = ($visit_type === 'IPD') ? "stm.vn = o.an" : "stm.vn = o.vn";
            $comp_q = mysqli_query($conn, "SELECT o.$pk,
                (IFNULL(o.follow_money, 0)
                 + IFNULL((SELECT SUM(compensated) FROM imr_tb_check_invoice stm WHERE $stm_join), 0)
                 + IFNULL((SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc WHERE vn = o.$pk), 0)
                 + IFNULL((SELECT SUM(compensated) FROM imr_tb_seamless_dckd WHERE vn = o.$pk), 0)
                ) AS total_comp
                FROM $table o
                WHERE o.$pk IN ($escaped_all_vns)");
            if ($comp_q) {
                while ($c_row = mysqli_fetch_assoc($comp_q)) {
                    $comp_map[$c_row[$pk]] = floatval($c_row['total_comp']);
                }
            }

            // ดึงยอดชดเชยที่ถูกจัดสรรและบันทึกไว้ในตาราง breakdown แล้ว (สำหรับผังลูก CR)
            $cr_bd_comp_map = [];
            $q_bd = mysqli_query($conn, "SELECT vn, compensated FROM imr_tb_debtor_cr_breakdown WHERE visit_type = '$visit_type' AND vn IN ($escaped_all_vns)");
            if ($q_bd) {
                while ($bd_r = mysqli_fetch_assoc($q_bd)) {
                    $cr_bd_comp_map[$bd_r['vn']] = floatval($bd_r['compensated']);
                }
            }
        }

        $group_cfg_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        $subgroups_cfg = isset($config[$group_cfg_key]) ? $config[$group_cfg_key] : [];

        $is_ae_enabled = is_cr_subgroup_enabled($config, 'CR-AE', $visit_type, $monthtxt);
        $ae_cfg = isset($subgroups_cfg['CR-AE']) ? $subgroups_cfg['CR-AE'] : [];
        $ae_pttypes = !empty($ae_cfg['pttypes']) ? array_map('trim', (array)$ae_cfg['pttypes']) : ['98', '97', '91', 'AM'];
        $ae_pttypenames = !empty($ae_cfg['pttypenames']) ? array_map('trim', (array)$ae_cfg['pttypenames']) : ['ฉุกเฉิน', 'อุบัติเหตุ', 'AE'];

        $is_nb_enabled = is_cr_subgroup_enabled($config, 'CR-เกิดสิทธิทันที', $visit_type, $monthtxt);
        $nb_cfg = isset($subgroups_cfg['CR-เกิดสิทธิทันที']) ? $subgroups_cfg['CR-เกิดสิทธิทันที'] : [];
        $nb_pttypes = !empty($nb_cfg['pttypes']) ? array_map('trim', (array)$nb_cfg['pttypes']) : ['XX'];
        $nb_pttypenames = !empty($nb_cfg['pttypenames']) ? array_map('trim', (array)$nb_cfg['pttypenames']) : ['สิทธิว่าง', 'แรกเกิด', 'Newborn', 'NEWBORN'];

        $transfers = [];
        $cr_received_total = 0.0;
        $cr_visits = []; // รายการที่เข้าเกณฑ์ CR ทั้งหมด (เพื่อใช้แสดงใน .216/.217)

        foreach ($parent_accounts as $p_acc) {
            $transfers[$p_acc] = [
                'original_debit' => 0.0,
                'transfer_out' => 0.0,
                'comp_out' => 0.0,
                'remain_debit' => 0.0,
                'cases' => 0,
                'cr_cases' => 0,
                'full_transfer_cases' => 0,
                'partial_transfer_cases' => 0
            ];

            if (isset($visits_by_acc[$p_acc])) {
                foreach ($visits_by_acc[$p_acc] as $v) {
                    $vn = $v[$pk];
                    $d = cleanNum($v['debit']);
                    $transfers[$p_acc]['original_debit'] += $d;
                    $transfers[$p_acc]['cases']++;

                    $v_pttype = trim($v['pttype'] ?? '');
                    $v_pttypename = trim($v['pttypename'] ?? '');

                    // ตรวจสอบ CR-AE จาก pttype / pttypename ในตารางลูกหนี้ eDHS
                    if ($is_ae_enabled) {
                        $match_ae = false;
                        if (!empty($v_pttype) && in_array($v_pttype, $ae_pttypes)) {
                            $match_ae = true;
                        }
                        if (!$match_ae && !empty($v_pttypename)) {
                            foreach ($ae_pttypenames as $kw) {
                                if (!empty($kw) && mb_stripos($v_pttypename, $kw) !== false) {
                                    $match_ae = true;
                                    break;
                                }
                            }
                        }
                        if ($match_ae) {
                            if (!isset($detected_map[$vn])) {
                                $detected_map[$vn] = ['cr_types' => [], 'cr_total_amount' => 0.0, 'items' => []];
                            }
                            if (!in_array('CR-AE', $detected_map[$vn]['cr_types'])) {
                                $detected_map[$vn]['cr_types'][] = 'CR-AE';
                            }
                        }
                    }

                    // ตรวจสอบ CR-เกิดสิทธิทันที จาก pttype / pttypename ในตารางลูกหนี้ eDHS
                    if ($is_nb_enabled) {
                        $match_nb = false;
                        if (!empty($v_pttype) && in_array($v_pttype, $nb_pttypes)) {
                            $match_nb = true;
                        }
                        if (!$match_nb && !empty($v_pttypename)) {
                            foreach ($nb_pttypenames as $kw) {
                                if (!empty($kw) && mb_stripos($v_pttypename, $kw) !== false) {
                                    $match_nb = true;
                                    break;
                                }
                            }
                        }
                        if ($match_nb) {
                            if (!isset($detected_map[$vn])) {
                                $detected_map[$vn] = ['cr_types' => [], 'cr_total_amount' => 0.0, 'items' => []];
                            }
                            if (!in_array('CR-เกิดสิทธิทันที', $detected_map[$vn]['cr_types'])) {
                                $detected_map[$vn]['cr_types'][] = 'CR-เกิดสิทธิทันที';
                            }
                        }
                    }

                    // 🌟 ลำดับความสำคัญ (Priority Rule): หากเคสนี้ได้รับการจัดกลุ่มเป็น CR-AE (ฉุกเฉิน/อุบัติเหตุ) แล้ว
                    // จะให้ความสำคัญสูงสุดแก่ CR-AE 100% โดยตัด CR-walkin ออก เพื่อไม่ให้เกิดการหารครึ่งยอดหนี้
                    if (isset($detected_map[$vn]['cr_types']) && in_array('CR-AE', $detected_map[$vn]['cr_types'])) {
                        if (in_array('CR-walkin', $detected_map[$vn]['cr_types'])) {
                            $detected_map[$vn]['cr_types'] = array_values(array_diff($detected_map[$vn]['cr_types'], ['CR-walkin']));
                            if (!empty($detected_map[$vn]['items'])) {
                                $detected_map[$vn]['items'] = array_values(array_filter($detected_map[$vn]['items'], function($it) {
                                    return ($it['cr_type'] ?? '') !== 'CR-walkin';
                                }));
                            }
                        }
                    }

                    $cr_info = isset($detected_map[$vn]) ? $detected_map[$vn] : null;
                    if ($cr_info && !empty($cr_info['cr_types'])) {
                        $transfers[$p_acc]['cr_cases']++;
                        
                        $has_orig_debit = (!empty($v['original_debit']) && cleanNum($v['original_debit']) > 0);
                        $real_cost = $has_orig_debit ? cleanNum($v['original_debit']) : $d;

                        // โอน 100% สำหรับกลุ่ม CR-AE, CR-เกิดสิทธิทันที, CR-walkin, CR-palliative
                        $is_100_transfer = (
                            in_array('CR-AE', $cr_info['cr_types']) || 
                            in_array('CR-เกิดสิทธิทันที', $cr_info['cr_types']) ||
                            in_array('CR-walkin', $cr_info['cr_types']) ||
                            in_array('CR-palliative', $cr_info['cr_types'])
                        );

                        if ($is_100_transfer) {
                            $cr_amt = $real_cost;
                            $transfer_out_from_parent = $d; // ตัดยอดทั้งหมดที่มีในผังแม่ให้เหลือ 0.00
                            $parent_remain = 0.0;
                            $is_full_transfer = true;
                        } else {
                            $item_sum = 0;
                            if (!empty($cr_info['items'])) {
                                foreach ($cr_info['items'] as $item) {
                                    $item_sum += cleanNum($item['amount'] ?? 0);
                                }
                            }
                            
                            if ($p_acc === '1102050101.203' && $has_orig_debit) {
                                // ผัง 203 ที่เปิดระบบข้อตกลงจังหวัด: ยอดอุปกรณ์โอนไป CR .216 และผังแม่ 203 คงภาระหนี้เหมาจ่ายไว้ที่ $d
                                $cr_amt = ($item_sum > 0) ? $item_sum : $d;
                                $transfer_out_from_parent = 0.0; // ไม่หักยอดเหมาจ่ายของผังแม่ออก
                                $parent_remain = $d;
                                $is_full_transfer = false;
                            } else {
                                $cr_amt = ($item_sum > 0 && $item_sum < $d) ? $item_sum : $d;
                                $transfer_out_from_parent = $cr_amt;
                                $parent_remain = max(0.0, $d - $cr_amt);
                                $is_full_transfer = ($cr_amt >= $d);
                            }
                        }

                        $transfers[$p_acc]['transfer_out'] += $transfer_out_from_parent;
                        $cr_received_total += $cr_amt;

                        if ($is_full_transfer) {
                            $transfers[$p_acc]['full_transfer_cases']++;
                        } else {
                            $transfers[$p_acc]['partial_transfer_cases']++;
                        }

                        $total_comp_v = isset($comp_map[$vn]) ? $comp_map[$vn] : 0.0;
                        if (isset($cr_bd_comp_map[$vn])) {
                            $comp_transferred = $cr_bd_comp_map[$vn];
                        } else {
                            $comp_transferred = min($total_comp_v, $cr_amt);
                        }
                        $transfers[$p_acc]['comp_out'] += $comp_transferred;

                        $is_k = (!empty($v['pttypename']) && (strpos($v['pttypename'], 'ฟอกไต') !== false || strpos($v['pttypename'], 'ไต') !== false));

                        $cr_visits[$vn] = [
                            'origin_accountcode' => $p_acc,
                            'original_debit' => $has_orig_debit ? $real_cost : $d,
                            'cr_types' => $cr_info['cr_types'],
                            'cr_amount' => $cr_amt,
                            'transfer_out' => $transfer_out_from_parent,
                            'cr_compensated' => $comp_transferred,
                            'general_remain_amount' => $parent_remain,
                            'is_full_transfer' => $is_full_transfer,
                            'pttypename' => $v['pttypename'] ?? '',
                            'is_kidney' => $is_k,
                            'vstdate' => $v['vstdate'] ?? ''
                        ];
                    }
                }
            }
            $transfers[$p_acc]['remain_debit'] = $transfers[$p_acc]['original_debit'] - $transfers[$p_acc]['transfer_out'];
        }

        // Visits ที่อยู่ใน .216 / .217 อยู่เดิม
        if (isset($visits_by_acc[$target_acc])) {
            foreach ($visits_by_acc[$target_acc] as $v) {
                $vn = $v[$pk];
                $d = cleanNum($v['debit']);
                $cr_info = isset($detected_map[$vn]) ? $detected_map[$vn] : null;
                $types = ($cr_info && !empty($cr_info['cr_types'])) ? $cr_info['cr_types'] : ['CR-Instrument'];
                $is_k = (!empty($v['pttypename']) && (strpos($v['pttypename'], 'ฟอกไต') !== false || strpos($v['pttypename'], 'ไต') !== false));
                if (isset($cr_bd_comp_map[$vn])) {
                    $native_comp = $cr_bd_comp_map[$vn];
                } else {
                    $native_comp = isset($comp_map[$vn]) ? $comp_map[$vn] : 0.0;
                }

                $cr_visits[$vn] = [
                    'origin_accountcode' => $target_acc,
                    'original_debit' => $d,
                    'cr_types' => $types,
                    'cr_amount' => $d,
                    'transfer_out' => 0.0,
                    'cr_compensated' => $native_comp,
                    'general_remain_amount' => 0.0,
                    'is_full_transfer' => true,
                    'pttypename' => $v['pttypename'] ?? '',
                    'is_kidney' => $is_k,
                    'vstdate' => $v['vstdate'] ?? ''
                ];
            }
        }

        $cr_non_kidney_count = 0;
        $cr_kidney_count = 0;
        $cr_non_kidney_diff_count = 0;
        $cr_kidney_diff_count = 0;
        $cr_non_kidney_received_total = 0.0;
        $cr_kidney_received_total = 0.0;
        $cr_non_kidney_received_comp = 0.0;
        $cr_kidney_received_comp = 0.0;

        foreach ($cr_visits as $cv) {
            $comp_item = isset($cv['cr_compensated']) ? floatval($cv['cr_compensated']) : 0.0;
            $amt = cleanNum($cv['cr_amount']);
            if (!empty($cv['is_kidney'])) {
                $cr_kidney_count++;
                if ($amt > 0) {
                    $cr_kidney_diff_count++;
                }
                $cr_kidney_received_total += $amt;
                $cr_kidney_received_comp += $comp_item;
            } else {
                $cr_non_kidney_count++;
                if ($amt > 0) {
                    $cr_non_kidney_diff_count++;
                }
                $cr_non_kidney_received_total += $amt;
                $cr_non_kidney_received_comp += $comp_item;
            }
        }

        $res = [
            'is_effective' => $is_effective,
            'auto_split' => $auto_split,
            'detected_map' => $detected_map,
            'transfers_by_account' => $transfers,
            'target_account' => $target_acc,
            'cr_received_total' => $cr_received_total,
            'cr_received_comp' => ($cr_non_kidney_received_comp + $cr_kidney_received_comp),
            'cr_non_kidney_count' => $cr_non_kidney_count,
            'cr_kidney_count' => $cr_kidney_count,
            'cr_non_kidney_diff_count' => $cr_non_kidney_diff_count,
            'cr_kidney_diff_count' => $cr_kidney_diff_count,
            'cr_non_kidney_received_total' => $cr_non_kidney_received_total,
            'cr_kidney_received_total' => $cr_kidney_received_total,
            'cr_non_kidney_received_comp' => $cr_non_kidney_received_comp,
            'cr_kidney_received_comp' => $cr_kidney_received_comp,
            'cr_visits' => $cr_visits
        ];

        $cache[$cache_key] = $res;
        return $res;
    }
}

if (!function_exists('sync_cr_breakdown_records')) {
    /**
     * บันทึกหรืออัปเดตข้อมูลการตัดแยกรายการ CR ลงในตาราง imr_tb_debtor_cr_breakdown
     */
    function sync_cr_breakdown_records($conn, $cr_summary, $monthtxt, $visit_type = 'OPD') {
        if (!$conn || empty($cr_summary['cr_visits'])) return 0;
        ensure_cr_tables($conn);

        $escaped_month = mysqli_real_escape_string($conn, $monthtxt);
        $v_type = ($visit_type === 'IPD') ? 'IPD' : 'OPD';
        $main_table = ($v_type === 'IPD') ? 'imr_tb_debtor_rights_ipd' : 'imr_tb_debtor_rights_opd';
        $pk = ($v_type === 'IPD') ? 'an' : 'vn';
        $date_col = ($v_type === 'IPD') ? 'dchdate' : 'vstdate';

        // 1. ดึงข้อมูลพื้นฐานของ visits
        $vns = array_keys($cr_summary['cr_visits']);
        $escaped_vns = "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $vns)) . "'";
        $sql = "SELECT $pk AS vn, hn, cid, ptname, $date_col AS vstdate, debit FROM $main_table WHERE $pk IN ($escaped_vns) AND monthtxt = '$escaped_month'";
        $q = mysqli_query($conn, $sql);
        $patient_map = [];
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $patient_map[$r['vn']] = $r;
            }
        }

        $inserted = 0;
        foreach ($cr_summary['cr_visits'] as $vn => $vinfo) {
            if (!isset($patient_map[$vn])) continue;
            $p = $patient_map[$vn];
            $hn = mysqli_real_escape_string($conn, $p['hn'] ?? '');
            $cid = mysqli_real_escape_string($conn, $p['cid'] ?? '');
            $ptname = mysqli_real_escape_string($conn, $p['ptname'] ?? '');
            $vstdate = mysqli_real_escape_string($conn, $p['vstdate'] ?? '');
            $parent_acc = mysqli_real_escape_string($conn, $vinfo['origin_accountcode']);
            $target_acc = ($v_type === 'IPD') ? '1102050101.217' : '1102050101.216';
            $cr_types = $vinfo['cr_types'];
            $cr_type_str = mysqli_real_escape_string($conn, implode(', ', $cr_types));
            $cr_amount = floatval($vinfo['cr_amount']);
            $gen_remain = floatval($vinfo['general_remain_amount']);
            $safe_vn = mysqli_real_escape_string($conn, $vn);

            // บันทึกแบบ ON DUPLICATE KEY UPDATE
            $sql_ins = "
                INSERT INTO imr_tb_debtor_cr_breakdown 
                (visit_type, vn, hn, cid, ptname, vstdate, monthtxt, parent_accountcode, cr_type, target_accountcode, item_amount, general_remain_amount)
                VALUES 
                ('$v_type', '$safe_vn', '$hn', '$cid', '$ptname', '$vstdate', '$escaped_month', '$parent_acc', '$cr_type_str', '$target_acc', $cr_amount, $gen_remain)
                ON DUPLICATE KEY UPDATE
                    cr_type = VALUES(cr_type),
                    parent_accountcode = VALUES(parent_accountcode),
                    item_amount = VALUES(item_amount),
                    general_remain_amount = VALUES(general_remain_amount),
                    updated_at = CURRENT_TIMESTAMP
            ";
            if (@mysqli_query($conn, $sql_ins)) {
                $inserted++;
            }
        }
        return $inserted;
    }
}
