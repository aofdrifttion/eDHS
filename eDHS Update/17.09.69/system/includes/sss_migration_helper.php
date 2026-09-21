<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - SSS Debtor Auto-Migration & Classification Helper
 * ====================================================================================
 * โมดูลช่วยตรวจสอบและสร้างตารางฐานข้อมูลอัตโนมัติ (Zero-Configuration Auto-Migration)
 * และเป็นแกนกลางในการจำแนกและตัดแยกหมวดย่อย Instrument สำหรับลูกหนี้ประกันสังคม (SSS)
 * 
 * OPD: ตัด Instrument จาก .301, .303, .307 เข้า .309
 * IPD: ตัด Instrument จาก .302, .304, .308 เข้า .310
 * 
 * @author eDHS Developer
 * @version 1.0.0 (2026-08-31)
 * ====================================================================================
 */

if (!function_exists('cleanNum')) {
    function cleanNum($val) {
        if ($val === null || $val === '') return 0.0;
        if (is_numeric($val)) return (float)$val;
        if (is_string($val)) {
            $val = trim(str_replace([',', ' '], '', $val));
            if (is_numeric($val)) return (float)$val;
        }
        return 0.0;
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney($number, $decimals = 2) {
        return number_format((float)cleanNum($number), $decimals, '.', ',');
    }
}

if (!function_exists('ensure_sss_tables')) {
    /**
     * ตรวจสอบและสร้างตารางฐานข้อมูลสำหรับลูกหนี้ประกันสังคม (SSS) โดยอัตโนมัติ
     * 
     * @param mysqli $conn การเชื่อมต่อฐานข้อมูล eDHS
     * @return bool สถานะความสำเร็จ
     */
    function ensure_sss_tables($conn) {
        if (!$conn) return false;

        // 1. ตารางเก็บรายละเอียดการตัดแยกค่าใช้จ่ายลูกหนี้ประกันสังคม SSS ระดับรายการ (Breakdown Table)
        $sql_breakdown = "
            CREATE TABLE IF NOT EXISTS `imr_tb_debtor_sss_breakdown` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `visit_type` ENUM('OPD', 'IPD') NOT NULL DEFAULT 'OPD' COMMENT 'ประเภทผู้ป่วย OPD หรือ IPD',
                `vn` VARCHAR(30) NOT NULL COMMENT 'VN สำหรับผู้ป่วยนอก หรือ AN สำหรับผู้ป่วยใน',
                `hn` VARCHAR(15) NOT NULL COMMENT 'HN ผู้ป่วย',
                `cid` VARCHAR(255) NULL COMMENT 'เลขประจำตัวประชาชน (เข้ารหัส)',
                `ptname` VARCHAR(150) NULL COMMENT 'ชื่อ-สกุล ผู้ป่วย',
                `vstdate` VARCHAR(10) NOT NULL COMMENT 'วันที่รับบริการ วว/ดด/ปปปป (พ.ศ.)',
                `monthtxt` VARCHAR(10) NOT NULL COMMENT 'เดือน-ปี เช่น 01-2569',
                `parent_accountcode` VARCHAR(50) NOT NULL COMMENT 'รหัสผังบัญชีหลักเดิม เช่น 1102050101.301, .303, .307, .302, .304, .308',
                `sss_type` VARCHAR(50) NOT NULL COMMENT 'กลุ่มย่อย SSS เช่น SSS-Instrument ฯลฯ',
                `target_accountcode` VARCHAR(50) NOT NULL COMMENT 'รหัสผังย่อยสำหรับตั้งหนี้ เช่น 1102050101.309_INST, 1102050101.310_INST',
                `item_code` VARCHAR(30) NULL COMMENT 'รหัสยา/เวชภัณฑ์/หัตถการ icode',
                `item_name` VARCHAR(255) NULL COMMENT 'ชื่อรายการ',
                `adp_type` VARCHAR(10) NULL COMMENT 'หมวด ADP TYPE (1, 2, 3, 4, 5)',
                `adp_code` VARCHAR(50) NULL COMMENT 'รหัสเบิก สปสช./ประกันสังคม',
                `qty` DECIMAL(10,2) DEFAULT 1.00 COMMENT 'จำนวนที่เบิก',
                `unit_price` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'ราคาต่อหน่วย',
                `item_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดเงินของรายการนี้',
                `general_remain_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดค่าบริการทั่วไปคงเหลือของ Visit',
                `compensated` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'เงินชดเชยที่ได้รับของส่วน SSS',
                `bill` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'เลขที่ใบเสร็จตัดหนี้ SSS',
                `billdate` VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'วันที่ออกใบเสร็จตัดหนี้ SSS',
                `settle_status` ENUM('WAIT','PARTIAL','SETTLED') NOT NULL DEFAULT 'WAIT' COMMENT 'สถานะการตัดหนี้ SSS',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_vn_type` (`visit_type`, `vn`),
                INDEX `idx_month` (`monthtxt`),
                INDEX `idx_sss_type` (`sss_type`),
                INDEX `idx_target_acc` (`target_accountcode`),
                INDEX `idx_parent_acc` (`parent_accountcode`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        @mysqli_query($conn, $sql_breakdown);

        // Auto-migrate columns if table already existed
        $q_chk_comp = mysqli_query($conn, "SHOW COLUMNS FROM `imr_tb_debtor_sss_breakdown` LIKE 'compensated'");
        if ($q_chk_comp && mysqli_num_rows($q_chk_comp) === 0) {
            @mysqli_query($conn, "ALTER TABLE `imr_tb_debtor_sss_breakdown` 
                ADD COLUMN `compensated` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `general_remain_amount`,
                ADD COLUMN `bill` VARCHAR(50) NOT NULL DEFAULT '' AFTER `compensated`,
                ADD COLUMN `billdate` VARCHAR(10) NOT NULL DEFAULT '' AFTER `bill`,
                ADD COLUMN `settle_status` ENUM('WAIT','PARTIAL','SETTLED') NOT NULL DEFAULT 'WAIT' AFTER `billdate`");
        }

        // 2. ตารางเก็บการตั้งค่า Mapping Configuration ของลูกหนี้ประกันสังคม SSS
        $sql_config = "
            CREATE TABLE IF NOT EXISTS `imr_tb_sss_config` (
                `config_key` VARCHAR(50) PRIMARY KEY COMMENT 'คีย์การตั้งค่า เช่น sss_mapping_settings',
                `config_value` LONGTEXT NOT NULL COMMENT 'ข้อมูลการตั้งค่ารูปแบบ JSON',
                `updated_by` VARCHAR(100) NULL COMMENT 'ผู้แก้ไขล่าสุด',
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        @mysqli_query($conn, $sql_config);

        // ตรวจสอบว่ามีการลงค่า Default Config หรือยัง หากยังไม่มีให้ใส่ค่าตั้งต้น
        $check_cfg = mysqli_query($conn, "SELECT config_key FROM imr_tb_sss_config WHERE config_key = 'sss_mapping_settings' LIMIT 1");
        if ($check_cfg && mysqli_num_rows($check_cfg) === 0) {
            $default_config = get_default_sss_config();
            $json_val = mysqli_real_escape_string($conn, json_encode($default_config, JSON_UNESCAPED_UNICODE));
            @mysqli_query($conn, "INSERT INTO imr_tb_sss_config (config_key, config_value, updated_by) VALUES ('sss_mapping_settings', '$json_val', 'System Default')");
        }

        return true;
    }
}

if (!function_exists('get_default_sss_config')) {
    /**
     * คืนค่าโครงสร้างการตั้งค่าเริ่มต้นสำหรับระบบลูกหนี้ประกันสังคม (SSS)
     */
    function get_default_sss_config() {
        return [
            "month_mode" => "ALL",
            "effective_start_month" => "ALL", // "ALL" หรือระบุเดือน เช่น "07-2569"
            "active_months" => [],
            "auto_split_enabled" => true,     // Master Switch
            "auto_split_opd_enabled" => true, // สวิตช์ตัดแยกยอดจริง SSS OPD
            "auto_split_ipd_enabled" => true, // สวิตช์ตัดแยกยอดจริง SSS IPD
            "parent_accounts" => [
                // ผัง OPD ประกันสังคม (4 ผัง)
                "1102050101.301" => ["enabled" => true, "type" => "OPD", "name" => "ลูกหนี้ค่ารักษาประกันสังคม OP -เครือข่าย"],
                "1102050101.303" => ["enabled" => true, "type" => "OPD", "name" => "ลูกหนี้ค่ารักษาประกันสังคม OP - นอกเครือข่าย สังกัด สป.สธ."],
                "1102050101.307" => ["enabled" => true, "type" => "OPD", "name" => "ลูกหนี้ค่ารักษาประกันสังคม - กองทุนทดแทน"],
                "1102050101.309" => ["enabled" => true, "type" => "OPD", "name" => "ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP"],
                // ผัง IPD ประกันสังคม (4 ผัง)
                "1102050101.302" => ["enabled" => true, "type" => "IPD", "name" => "ลูกหนี้ค่ารักษาประกันสังคม IP - เครือข่าย"],
                "1102050101.304" => ["enabled" => true, "type" => "IPD", "name" => "ลูกหนี้ค่ารักษาประกันสังคม IP - นอกเครือข่าย สังกัด สป.สธ."],
                "1102050101.308" => ["enabled" => true, "type" => "IPD", "name" => "ลูกหนี้ค่ารักษาประกันสังคม 72 ชั่วโมงแรก"],
                "1102050101.310" => ["enabled" => true, "type" => "IPD", "name" => "ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง IP"]
            ],
            // -------------------------------------------------------------
            // กลุ่มย่อยสำหรับผู้ป่วยนอก (OPD Subgroup: Instrument)
            // -------------------------------------------------------------
            "subgroups_opd" => [
                "SSS-Instrument" => [
                    "id" => "SSS-Instrument",
                    "enabled" => true,
                    "title" => "SSS-Instrument (อุปกรณ์และอวัยวะเทียม OP)",
                    "short_name" => "SSS-Instrument",
                    "target_accountcode" => "1102050101.309_INST",
                    "accountname" => "- ลูกหนี้ค่าอุปกรณ์/อวัยวะเทียม ประกันสังคม (SSS-Instrument)",
                    "icon" => "",
                    "adp_type" => "2",
                    "auto_adp_type_2" => true,
                    "effective_start_month" => "ALL",
                    "effective_end_month" => "",
                    "custom_adp_codes" => ["7004", "7005", "8612", "8813", "8814"],
                    "custom_icodes" => []
                ]
            ],
            // -------------------------------------------------------------
            // กลุ่มย่อยสำหรับผู้ป่วยใน (IPD Subgroup: Instrument)
            // -------------------------------------------------------------
            "subgroups_ipd" => [
                "SSS-Instrument" => [
                    "id" => "SSS-Instrument",
                    "enabled" => true,
                    "title" => "SSS-Instrument (อุปกรณ์และอวัยวะเทียม IP)",
                    "short_name" => "SSS-Instrument",
                    "target_accountcode" => "1102050101.310_INST",
                    "accountname" => "- ลูกหนี้ค่าอุปกรณ์/อวัยวะเทียม ประกันสังคม IP (SSS-Instrument)",
                    "icon" => "",
                    "adp_type" => "2",
                    "auto_adp_type_2" => true,
                    "effective_start_month" => "ALL",
                    "effective_end_month" => "",
                    "custom_adp_codes" => ["7004", "7005", "8612", "8813", "8814"],
                    "custom_icodes" => []
                ]
            ]
        ];
    }
}

if (!function_exists('get_active_sss_config')) {
    /**
     * ดึงการตั้งค่า SSS ปัจจุบันจากฐานข้อมูล (หากไม่มีให้คืนค่า Default)
     */
    function get_active_sss_config($conn) {
        ensure_sss_tables($conn);
        $res = mysqli_query($conn, "SELECT config_value FROM imr_tb_sss_config WHERE config_key = 'sss_mapping_settings' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $parsed = json_decode($row['config_value'], true);
            if (is_array($parsed)) {
                $defaults = get_default_sss_config();
                
                // ผสาน parent accounts
                if (isset($defaults['parent_accounts'])) {
                    foreach ($defaults['parent_accounts'] as $acc => $pinfo) {
                        if (!isset($parsed['parent_accounts'][$acc])) {
                            $parsed['parent_accounts'][$acc] = $pinfo;
                        }
                    }
                }
                
                // ผสาน subgroups_opd
                if (!isset($parsed['subgroups_opd'])) {
                    $parsed['subgroups_opd'] = $defaults['subgroups_opd'];
                } else {
                    foreach ($defaults['subgroups_opd'] as $sg_id => $sg_val) {
                        if (!isset($parsed['subgroups_opd'][$sg_id])) {
                            $parsed['subgroups_opd'][$sg_id] = $sg_val;
                        }
                    }
                }

                // ผสาน subgroups_ipd
                if (!isset($parsed['subgroups_ipd'])) {
                    $parsed['subgroups_ipd'] = $defaults['subgroups_ipd'];
                } else {
                    foreach ($defaults['subgroups_ipd'] as $sg_id => $sg_val) {
                        if (!isset($parsed['subgroups_ipd'][$sg_id])) {
                            $parsed['subgroups_ipd'][$sg_id] = $sg_val;
                        }
                    }
                }

                // ตรวจสอบและเติม effective_start_month / effective_end_month ให้ทุกกลุ่มย่อย SSS
                foreach (['subgroups_opd', 'subgroups_ipd'] as $gk) {
                    if (isset($parsed[$gk]) && is_array($parsed[$gk])) {
                        foreach ($parsed[$gk] as $sg_k => &$sg_v) {
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

                if (!isset($parsed['auto_split_opd_enabled'])) {
                    $parsed['auto_split_opd_enabled'] = $parsed['auto_split_enabled'] ?? true;
                }
                if (!isset($parsed['auto_split_ipd_enabled'])) {
                    $parsed['auto_split_ipd_enabled'] = $parsed['auto_split_enabled'] ?? true;
                }

                // Auto-migrate: บังคับให้อัปเดต 1102050101.308 เป็น IPD อัตโนมัติทุก รพ. ที่อัปเดตโค้ด
                if (isset($parsed['parent_accounts']['1102050101.308']) && ($parsed['parent_accounts']['1102050101.308']['type'] ?? '') !== 'IPD') {
                    $parsed['parent_accounts']['1102050101.308']['type'] = 'IPD';
                    $json_sync = mysqli_real_escape_string($conn, json_encode($parsed, JSON_UNESCAPED_UNICODE));
                    @mysqli_query($conn, "UPDATE imr_tb_sss_config SET config_value = '$json_sync', updated_by = 'Auto-Migration v.17.09.69' WHERE config_key = 'sss_mapping_settings'");
                }

                return $parsed;
            }
        }
        return get_default_sss_config();
    }
}

if (!function_exists('normalize_sss_monthtxt_be')) {
    /**
     * ฟังก์ชันแปลงงวดเดือนให้อยู่ในรูป พ.ศ. สองหลัก-สี่หลักมาตรฐาน (MM-YYYY)
     */
    function normalize_sss_monthtxt_be($monthtxt) {
        if (is_array($monthtxt)) return 'ALL';
        $monthtxt = trim((string)$monthtxt);
        if (empty($monthtxt) || strtoupper($monthtxt) === 'ALL') return 'ALL';
        $parts = explode('-', str_replace('/', '-', $monthtxt));
        if (count($parts) !== 2) return $monthtxt;
        $m = str_pad(trim($parts[0]), 2, '0', STR_PAD_LEFT);
        $y = intval(trim($parts[1]));
        if ($y < 2400 && $y > 1900) {
            $y += 543;
        }
        return sprintf("%02d-%04d", $m, $y);
    }
}

if (!function_exists('is_sss_effective_for_month')) {
    /**
     * ตรวจสอบว่าเดือนที่ระบุอยู่ในช่วงที่เปิดใช้งานระบบจำแนกและตัดแยกหนี้ประกันสังคม SSS หรือไม่
     */
    function is_sss_effective_for_month($config, $monthtxt) {
        if (empty($monthtxt)) return true;
        if (empty($config) || !is_array($config)) return true;

        $month_mode = isset($config['month_mode']) ? strtoupper($config['month_mode']) : 'ALL';
        if ($month_mode === 'ALL') {
            return true;
        }

        $norm_cur = normalize_sss_monthtxt_be($monthtxt);

        // โหมดเลือกเฉพาะเดือน (Specific Months Checklist)
        if ($month_mode === 'SPECIFIC') {
            $active_months = isset($config['active_months']) ? (array)$config['active_months'] : [];
            $norm_active = array_map('normalize_sss_monthtxt_be', $active_months);
            return in_array($norm_cur, $norm_active);
        }

        // โหมดกำหนดเดือนเริ่มต้นเป็นต้นไป (Start From Month)
        $start_m = isset($config['effective_start_month']) ? trim($config['effective_start_month']) : 'ALL';
        if (empty($start_m) || strtoupper($start_m) === 'ALL') {
            return true;
        }

        $norm_start = normalize_sss_monthtxt_be($start_m);
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

if (!function_exists('is_sss_auto_split_enabled')) {
    /**
     * ตรวจสอบว่าเปิดใช้งานการตัดแยกยอดเงินจริง (Auto-Debit Splitting) หรือไม่ สำหรับ OPD / IPD
     */
    function is_sss_auto_split_enabled($config, $visit_type = null) {
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

if (!function_exists('normalize_monthtxt_be')) {
    /**
     * แปลงรูปแบบเดือนให้เป็นมาตรฐาน mm-yyyy (พ.ศ.) เช่น '7-2026' -> '07-2569'
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

if (!function_exists('is_sss_subgroup_effective_for_month')) {
    /**
     * ตรวจสอบว่ากลุ่มย่อย SSS เปิดใช้งาน และ มีผลบังคับใช้ในเดือนที่ระบุหรือไม่ (Subgroup Effective Month Range)
     *
     * @param array $config คอนฟิก SSS ทั้งหมด
     * @param string $subgroup_id รหัสกลุ่มย่อย เช่น 'SSS-Instrument'
     * @param string $visit_type 'OPD' หรือ 'IPD'
     * @param string|array|null $monthtxt เดือนที่ต้องการตรวจสอบ เช่น '8-2026', '08-2569' หรือ Array ของเดือน
     * @return bool
     */
    function is_sss_subgroup_effective_for_month($config, $subgroup_id, $visit_type = 'OPD', $monthtxt = null) {
        if (empty($config) || !is_array($config)) return true;

        // หากส่งเดือนมาเป็น Array (เช่น จากช่วงวันที่ข้ามหลายเดือน) ให้ตรวจสอบว่ามีผลในเดือนใดเดือนหนึ่งหรือไม่
        if (is_array($monthtxt)) {
            if (empty($monthtxt)) return true;
            foreach ($monthtxt as $m) {
                if (is_sss_subgroup_effective_for_month($config, $subgroup_id, $visit_type, $m)) {
                    return true;
                }
            }
            return false;
        }

        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        if (!isset($config[$group_key]) || !isset($config[$group_key][$subgroup_id])) {
            return false;
        }
        $sg = $config[$group_key][$subgroup_id];

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
                return false; // เดือนของ visit เกินเดือนสิ้นสุดของกลุ่มย่อย
            }
        }

        return true;
    }
}

if (!function_exists('is_sss_subgroup_enabled')) {
    /**
     * ตรวจสอบว่ากลุ่มย่อย SSS นั้นๆ เปิดใช้งานอยู่หรือไม่ แยกตาม OPD และ IPD (และตรวจขอบเขตเดือนถ้ามีการระบุ)
     */
    function is_sss_subgroup_enabled($config, $subgroup_id, $visit_type = 'OPD', $monthtxt = null) {
        if (!empty($monthtxt)) {
            return is_sss_subgroup_effective_for_month($config, $subgroup_id, $visit_type, $monthtxt);
        }
        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        if (!isset($config[$group_key]) || !isset($config[$group_key][$subgroup_id])) {
            return false;
        }
        if (!isset($config[$group_key][$subgroup_id]['enabled'])) {
            return true;
        }
        return ($config[$group_key][$subgroup_id]['enabled'] !== false);
    }
}

if (!function_exists('is_sss_parent_account_enabled')) {
    /**
     * ตรวจสอบว่าผังแม่ SSS นั้นๆ เปิดใช้งานอยู่หรือไม่
     */
    function is_sss_parent_account_enabled($config, $accountcode) {
        if (empty($config) || !is_array($config)) return true;
        if (isset($config['parent_accounts']) && isset($config['parent_accounts'][$accountcode])) {
            return ($config['parent_accounts'][$accountcode]['enabled'] !== false);
        }
        return true;
    }
}

if (!function_exists('detect_sss_types_for_visit_list')) {
    /**
     * ตรวจจับและจัดกลุ่ม Instrument ให้กับชุด Visit ประกันสังคม (OPD/IPD) จากฐานข้อมูล HOSxP ($conn2)
     * 
     * @param mysqli $conn2 การเชื่อมต่อฐานข้อมูล HOSxP
     * @param array $vn_list รายการ VN หรือ AN
     * @param string $visit_type 'OPD' หรือ 'IPD'
     * @param array $config การตั้งค่า SSS Config
     * @param string $my_hospcode รหัสโรงพยาบาล
     * @param string|null $monthtxt งวดเดือน เช่น '8-2026' สำหรับตรวจสอบ Subgroup Effective Month
     * @return array ข้อมูลการแมป [vn => ['sss_types' => ['SSS-Instrument'], 'items' => [...]]]
     */
    function detect_sss_types_for_visit_list($conn2, $vn_list, $visit_type = 'OPD', $config = null, $my_hospcode = '', $monthtxt = null) {
        if (!$conn2 || empty($vn_list)) return [];
        if (!$config) $config = get_default_sss_config();

        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        $subgroups_cfg = isset($config[$group_key]) ? $config[$group_key] : [];

        // 🌟 ตรวจสอบความมีผลของกลุ่มย่อย SSS-Instrument ตามขอบเขตเดือน (Subgroup Effective Month)
        $is_inst_enabled = is_sss_subgroup_effective_for_month($config, 'SSS-Instrument', $visit_type, $monthtxt);
        $inst_cfg = isset($subgroups_cfg['SSS-Instrument']) ? $subgroups_cfg['SSS-Instrument'] : [];
        if (empty($inst_cfg) && isset($config['subgroups']['SSS-Instrument'])) {
            $inst_cfg = $config['subgroups']['SSS-Instrument'];
        }
        $inst_adps = !empty($inst_cfg['custom_adp_codes']) ? array_values(array_filter(array_map('trim', array_map('strval', $inst_cfg['custom_adp_codes'])))) : ["7004", "7005", "8612", "8813", "8814"];
        $inst_icodes = !empty($inst_cfg['custom_icodes']) ? array_values(array_filter(array_map('trim', array_map('strval', $inst_cfg['custom_icodes'])))) : [];
        $auto_adp2 = !isset($inst_cfg['auto_adp_type_2']) || ($inst_cfg['auto_adp_type_2'] !== false);

        $escaped_vns = [];
        foreach ($vn_list as $v) {
            $escaped_vns[] = "'" . mysqli_real_escape_string($conn2, trim($v)) . "'";
        }
        $in_sql = implode(",", $escaped_vns);

        $results = [];
        foreach ($vn_list as $v) {
            $results[$v] = [
                'sss_types' => [],
                'sss_total_amount' => 0.0,
                'items' => []
            ];
        }

        // ตรวจจับจากรายการยาและเวชภัณฑ์ (opitemrece + nondrugitems + drugitems)
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
                d.nhso_adp_code AS drug_adp_code
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
                
                // ตรวจสอบ Instrument (ADP Type 2 หรือ matching ADP codes / custom icodes)
                if ($is_inst_enabled) {
                    $is_match = false;
                    $adp_code = trim((string)($row['nondrug_adp_code'] ?: $row['drug_adp_code']));
                    $adp_type = trim((string)($row['nondrug_adp_type'] ?: $row['drug_adp_type']));
                    $icode = trim((string)$row['icode']);

                    if ($auto_adp2 && ($adp_type === '2')) {
                        $is_match = true;
                    }
                    if (!$is_match && $adp_code !== '' && !empty($inst_adps)) {
                        if (in_array($adp_code, $inst_adps)) {
                            $is_match = true;
                        }
                    }
                    if (!$is_match && $icode !== '' && !empty($inst_icodes) && in_array($icode, $inst_icodes)) {
                        $is_match = true;
                    }

                    if ($is_match) {
                        if (!in_array('SSS-Instrument', $results[$vn]['sss_types'])) {
                            $results[$vn]['sss_types'][] = 'SSS-Instrument';
                        }
                        $results[$vn]['sss_total_amount'] += $sum_price;
                        $results[$vn]['items'][] = [
                            'sss_type' => 'SSS-Instrument',
                            'icode' => $row['icode'],
                            'item_name' => $item_name,
                            'adp_type' => $adp_type ?: '2',
                            'adp_code' => $adp_code,
                            'qty' => floatval($row['qty']),
                            'unit_price' => floatval($row['unitprice']),
                            'amount' => $sum_price
                        ];
                    }
                }
            }
        }

        return $results;
    }
}

if (!function_exists('get_sss_splitting_summary_for_month')) {
    /**
     * คำนวณสรุปการตัดแยกยอดเงิน SSS ข้ามผังบัญชีสำหรับเดือนที่กำหนด (OPD/IPD)
     * 
     * @param mysqli $conn
     * @param mysqli $conn2
     * @param string $monthtxt
     * @param string $visit_type 'OPD' หรือ 'IPD'
     * @param array $config
     * @param string $my_hospcode
     * @param array|null $vn_filter
     * @return array
     */
    function get_sss_splitting_summary_for_month($conn, $conn2, $monthtxt, $visit_type = 'OPD', $config = null, $my_hospcode = '', $vn_filter = null) {
        static $cache = [];
        if (!$config) $config = get_active_sss_config($conn);
        $cfg_hash = md5(json_encode($config));
        $vn_hash = ($vn_filter !== null) ? md5(implode(',', (array)$vn_filter)) : 'all';
        $cache_key = "{$monthtxt}_{$visit_type}_{$cfg_hash}_{$vn_hash}";
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }
        $is_effective = is_sss_effective_for_month($config, $monthtxt);
        $auto_split = is_sss_auto_split_enabled($config, $visit_type);

        if (!$is_effective || !$auto_split || !$conn2 || ($vn_filter !== null && empty($vn_filter))) {
            $res = [
                'is_effective' => $is_effective,
                'auto_split' => $auto_split,
                'detected_map' => [],
                'transfers_by_account' => [],
                'target_account' => ($visit_type === 'OPD') ? '1102050101.309' : '1102050101.310',
                'sss_received_total' => 0.0,
                'sss_count' => 0,
                'sss_visits' => []
            ];
            $cache[$cache_key] = $res;
            return $res;
        }

        if ($visit_type === 'OPD') {
            $raw_parents = ['1102050101.301', '1102050101.303', '1102050101.307'];
            $parent_accounts = [];
            foreach ($raw_parents as $pa) {
                if (is_sss_parent_account_enabled($config, $pa)) {
                    $parent_accounts[] = $pa;
                }
            }
            $target_acc = '1102050101.309';
            $table = 'imr_tb_debtor_rights_opd';
            $pk = 'vn';
            $date_col = 'vstdate';
        } else {
            $raw_parents = ['1102050101.302', '1102050101.304', '1102050101.308'];
            $parent_accounts = [];
            foreach ($raw_parents as $pa) {
                if (is_sss_parent_account_enabled($config, $pa)) {
                    $parent_accounts[] = $pa;
                }
            }
            $target_acc = '1102050101.310';
            $table = 'imr_tb_debtor_rights_ipd';
            $pk = 'an';
            $date_col = 'dchdate';
        }

        $all_accs = array_merge($parent_accounts, [$target_acc]);
        $acc_in = "'" . implode("','", $all_accs) . "'";
        $escaped_month = mysqli_real_escape_string($conn, $monthtxt);

        $sql = "SELECT $pk, accountcode, debit, income, ptname, pttypename, pttype, $date_col AS vstdate FROM $table WHERE monthtxt = '$escaped_month' AND accountcode IN ($acc_in)";
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
            $detected_map = detect_sss_types_for_visit_list($conn2, $unique_vns, $visit_type, $config, $my_hospcode, $monthtxt);

            // ดึงยอดเงินชดเชย (follow_money + STM Statement) สำหรับทุก visit เพื่อจัดสรรเงินชดเชยตามผังบัญชี SSS
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

            // ดึงยอดชดเชยที่ถูกจัดสรรและบันทึกไว้ในตาราง breakdown แล้ว (สำหรับผังลูก SSS)
            $sss_bd_comp_map = [];
            $q_bd = mysqli_query($conn, "SELECT vn, compensated FROM imr_tb_debtor_sss_breakdown WHERE visit_type = '$visit_type' AND vn IN ($escaped_all_vns)");
            if ($q_bd) {
                while ($bd_r = mysqli_fetch_assoc($q_bd)) {
                    $sss_bd_comp_map[$bd_r['vn']] = floatval($bd_r['compensated']);
                }
            }
        }

        $transfers = [];
        $sss_received_total = 0.0;
        $sss_visits = [];

        foreach ($parent_accounts as $p_acc) {
            $transfers[$p_acc] = [
                'original_debit' => 0.0,
                'transfer_out' => 0.0,
                'comp_out' => 0.0,
                'remain_debit' => 0.0,
                'cases' => 0,
                'sss_cases' => 0,
                'full_transfer_cases' => 0,
                'partial_transfer_cases' => 0
            ];

            if (isset($visits_by_acc[$p_acc])) {
                foreach ($visits_by_acc[$p_acc] as $v) {
                    $vn = $v[$pk];
                    $d = cleanNum($v['debit']);
                    $transfers[$p_acc]['original_debit'] += $d;
                    $transfers[$p_acc]['cases']++;

                    $sss_info = isset($detected_map[$vn]) ? $detected_map[$vn] : null;
                    if ($sss_info && !empty($sss_info['sss_types'])) {
                        $transfers[$p_acc]['sss_cases']++;
                        $sss_amt = $d;

                        if (!empty($sss_info['items'])) {
                            $item_sum = 0;
                            foreach ($sss_info['items'] as $item) {
                                $item_sum += cleanNum($item['amount'] ?? 0);
                            }
                            if ($item_sum > 0 && $item_sum < $d) {
                                $sss_amt = $item_sum;
                            }
                        }

                        $transfers[$p_acc]['transfer_out'] += $sss_amt;
                        $sss_received_total += $sss_amt;

                        $is_full_transfer = ($sss_amt >= $d);
                        if ($is_full_transfer) {
                            $transfers[$p_acc]['full_transfer_cases']++;
                        } else {
                            $transfers[$p_acc]['partial_transfer_cases']++;
                        }

                        $total_comp_v = isset($comp_map[$vn]) ? $comp_map[$vn] : 0.0;
                        if (isset($sss_bd_comp_map[$vn])) {
                            $comp_transferred = $sss_bd_comp_map[$vn];
                        } else {
                            $comp_transferred = min($total_comp_v, $sss_amt);
                        }
                        $transfers[$p_acc]['comp_out'] += $comp_transferred;

                        $sss_visits[$vn] = [
                            'origin_accountcode' => $p_acc,
                            'original_debit' => $d,
                            'sss_types' => $sss_info['sss_types'],
                            'sss_amount' => $sss_amt,
                            'transfer_out' => $sss_amt,
                            'sss_compensated' => $comp_transferred,
                            'general_remain_amount' => max(0, $d - $sss_amt),
                            'is_full_transfer' => $is_full_transfer,
                            'pttypename' => $v['pttypename'] ?? '',
                            'vstdate' => $v['vstdate'] ?? ''
                        ];
                    }
                }
            }
            $transfers[$p_acc]['remain_debit'] = $transfers[$p_acc]['original_debit'] - $transfers[$p_acc]['transfer_out'];
        }

        // Visits ที่อยู่ใน .309 / .310 อยู่เดิม
        $target_orig_cases = 0;
        $target_orig_debit = 0.0;
        $target_orig_comp = 0.0;
        if (isset($visits_by_acc[$target_acc])) {
            $target_orig_cases = count($visits_by_acc[$target_acc]);
            foreach ($visits_by_acc[$target_acc] as $v) {
                $vn = $v[$pk];
                $d = cleanNum($v['debit']);
                $target_orig_debit += $d;
                if (isset($sss_bd_comp_map[$vn])) {
                    $native_c = $sss_bd_comp_map[$vn];
                } else {
                    $native_c = isset($comp_map[$vn]) ? $comp_map[$vn] : 0.0;
                }
                $target_orig_comp += $native_c;
                $sss_info = isset($detected_map[$vn]) ? $detected_map[$vn] : null;
                $types = ($sss_info && !empty($sss_info['sss_types'])) ? $sss_info['sss_types'] : ['SSS-Instrument'];

                $sss_visits[$vn] = [
                    'origin_accountcode' => $target_acc,
                    'original_debit' => $d,
                    'sss_types' => $types,
                    'sss_amount' => $d,
                    'transfer_out' => 0.0,
                    'sss_compensated' => $native_c,
                    'general_remain_amount' => 0.0,
                    'is_full_transfer' => true,
                    'pttypename' => $v['pttypename'] ?? '',
                    'vstdate' => $v['vstdate'] ?? ''
                ];
            }
        }

        $transfer_in_cases = 0;
        $sss_received_comp = 0.0;
        foreach ($transfers as $tr) {
            $transfer_in_cases += intval($tr['sss_cases'] ?? 0);
            $sss_received_comp += floatval($tr['comp_out'] ?? 0);
        }

        $sss_count = count($sss_visits);

        $res = [
            'is_effective' => $is_effective,
            'auto_split' => $auto_split,
            'detected_map' => $detected_map,
            'transfers_by_account' => $transfers,
            'target_account' => $target_acc,
            'target_original_cases' => $target_orig_cases,
            'target_original_debit' => $target_orig_debit,
            'target_original_comp' => $target_orig_comp,
            'transfer_in_cases' => $transfer_in_cases,
            'transfer_in_amount' => $sss_received_total,
            'final_target_cases' => $target_orig_cases + $transfer_in_cases,
            'final_target_debit' => $target_orig_debit + $sss_received_total,
            'sss_received_total' => $sss_received_total,
            'sss_received_comp' => $sss_received_comp,
            'sss_count' => $sss_count,
            'sss_visits' => $sss_visits
        ];

        $cache[$cache_key] = $res;
        return $res;
    }
}

if (!function_exists('sync_sss_breakdown_records')) {
    /**
     * ซิงค์บันทึกการตัดแยกรายการ SSS ลงในตาราง imr_tb_debtor_sss_breakdown
     */
    function sync_sss_breakdown_records($conn, $sss_summary, $monthtxt, $visit_type = 'OPD') {
        if (!$conn || empty($sss_summary) || empty($sss_summary['sss_visits'])) return false;

        $target_acc = $sss_summary['target_account'] ?? (($visit_type === 'IPD') ? '1102050101.310' : '1102050101.309');
        $escaped_month = mysqli_real_escape_string($conn, $monthtxt);
        $escaped_type = mysqli_real_escape_string($conn, $visit_type);

        $vns_to_save = array_keys($sss_summary['sss_visits']);
        if (empty($vns_to_save)) return true;

        $pk = ($visit_type === 'IPD') ? 'an' : 'vn';
        $table = ($visit_type === 'IPD') ? 'imr_tb_debtor_rights_ipd' : 'imr_tb_debtor_rights_opd';
        $date_col = ($visit_type === 'IPD') ? 'dchdate' : 'vstdate';

        $escaped_vns = array_map(function($v) use ($conn) { return "'" . mysqli_real_escape_string($conn, $v) . "'"; }, $vns_to_save);
        $vns_str = implode(',', $escaped_vns);

        $pinfo_map = [];
        $q_pat = mysqli_query($conn, "SELECT $pk AS vn, hn, cid, ptname, $date_col AS vstdate, accountcode FROM $table WHERE monthtxt = '$escaped_month' AND $pk IN ($vns_str)");
        if ($q_pat) {
            while ($prow = mysqli_fetch_assoc($q_pat)) {
                $pinfo_map[$prow['vn']] = $prow;
            }
        }

        // ลบข้อมูลเก่าของเดือนและกลุ่ม visits เหล่านี้
        @mysqli_query($conn, "DELETE FROM imr_tb_debtor_sss_breakdown WHERE monthtxt = '$escaped_month' AND visit_type = '$escaped_type' AND vn IN ($vns_str)");

        $insert_values = [];
        foreach ($sss_summary['sss_visits'] as $vn => $vinfo) {
            $p = isset($pinfo_map[$vn]) ? $pinfo_map[$vn] : null;
            $hn = $p ? mysqli_real_escape_string($conn, $p['hn']) : '';
            $cid = $p ? mysqli_real_escape_string($conn, $p['cid']) : '';
            $ptname = $p ? mysqli_real_escape_string($conn, $p['ptname']) : '';
            $vstdate = $p ? mysqli_real_escape_string($conn, $p['vstdate']) : ($vinfo['vstdate'] ?? '');
            $parent_acc = mysqli_real_escape_string($conn, $vinfo['origin_accountcode'] ?? ($p ? $p['accountcode'] : ''));

            $detected = isset($sss_summary['detected_map'][$vn]) ? $sss_summary['detected_map'][$vn] : null;
            $items = ($detected && !empty($detected['items'])) ? $detected['items'] : [];

            if (!empty($items)) {
                foreach ($items as $it) {
                    $item_code = mysqli_real_escape_string($conn, $it['icode'] ?? '');
                    $item_name = mysqli_real_escape_string($conn, $it['item_name'] ?? '');
                    $adp_type = mysqli_real_escape_string($conn, $it['adp_type'] ?? '2');
                    $adp_code = mysqli_real_escape_string($conn, $it['adp_code'] ?? '');
                    $qty = cleanNum($it['qty'] ?? 1);
                    $u_price = cleanNum($it['unit_price'] ?? 0);
                    $amt = cleanNum($it['amount'] ?? 0);
                    $remain_amt = cleanNum($vinfo['general_remain_amount'] ?? 0);
                    $sss_type = mysqli_real_escape_string($conn, $it['sss_type'] ?? 'SSS-Instrument');
                    $sub_target = ($visit_type === 'IPD') ? '1102050101.310_INST' : '1102050101.309_INST';

                    $insert_values[] = "('$escaped_type', '$vn', '$hn', '$cid', '$ptname', '$vstdate', '$escaped_month', '$parent_acc', '$sss_type', '$sub_target', '$item_code', '$item_name', '$adp_type', '$adp_code', $qty, $u_price, $amt, $remain_amt)";
                }
            } else {
                $sss_type = mysqli_real_escape_string($conn, !empty($vinfo['sss_types'][0]) ? $vinfo['sss_types'][0] : 'SSS-Instrument');
                $amt = cleanNum($vinfo['sss_amount'] ?? 0);
                $remain_amt = cleanNum($vinfo['general_remain_amount'] ?? 0);
                $sub_target = ($visit_type === 'IPD') ? '1102050101.310_INST' : '1102050101.309_INST';

                $insert_values[] = "('$escaped_type', '$vn', '$hn', '$cid', '$ptname', '$vstdate', '$escaped_month', '$parent_acc', '$sss_type', '$sub_target', 'AUTO', 'อุปกรณ์/อวัยวะเทียม ประกันสังคม', '2', '7004', 1.00, $amt, $amt, $remain_amt)";
            }
        }

        if (!empty($insert_values)) {
            $chunks = array_chunk($insert_values, 200);
            foreach ($chunks as $chk) {
                $sql_ins = "INSERT INTO imr_tb_debtor_sss_breakdown 
                    (visit_type, vn, hn, cid, ptname, vstdate, monthtxt, parent_accountcode, sss_type, target_accountcode, item_code, item_name, adp_type, adp_code, qty, unit_price, item_amount, general_remain_amount)
                    VALUES " . implode(',', $chk);
                @mysqli_query($conn, $sql_ins);
            }
        }

        return true;
    }
}
