<?php
/**
 * Rehab & Medical Device Compensation Tracker Plugin
 * Configuration & Auto-Migration System
 * eDebtor Hospital System (eDHS) - Phonsai Hospital
 */

// Load Core eDHS Database Configuration
$root_config = dirname(dirname(__DIR__)) . '/database_config/config.php';
if (file_exists($root_config)) {
    require_once $root_config;
} else {
    // Fallback if accessed relatively
    require_once '../../database_config/config.php';
}

if (!isset($conn) || !$conn) {
    die("Error: Unable to connect to eDHS Database.");
}

// Ensure full 4-byte UTF-8 encoding for Thai and Emojis
mysqli_set_charset($conn, 'utf8mb4');

// Function to safely execute migrations
function rehab_auto_migrate($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    // 0. Defensive Restoration for tb_menus emojis if any got converted to '?'
    $chk_corrupt = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `tb_menus` WHERE `menu_icon` LIKE '%?%'");
    if ($chk_corrupt && mysqli_fetch_assoc($chk_corrupt)['cnt'] > 0) {
        $menu_emojis = [
            1 => '📂',
            2 => '📂',
            3 => '📝',
            4 => '📂',
            5 => '🏷️',
            6 => '🏷️',
            7 => '⌛',
            8 => '📝',
            9 => '📝',
            10 => '💰',
            11 => '📧',
            12 => '⚖️',
            13 => '📈',
            14 => '📈',
            15 => '📈',
            20 => '🦽'
        ];
        foreach ($menu_emojis as $mid => $emo) {
            $stmt_m = $conn->prepare("UPDATE `tb_menus` SET `menu_icon` = ? WHERE `id` = ?");
            if ($stmt_m) {
                $stmt_m->bind_param("si", $emo, $mid);
                $stmt_m->execute();
                $stmt_m->close();
            }
        }
    }

    // 1. Create Claims Tracking Table
    $sql_claims = "CREATE TABLE IF NOT EXISTS `imr_tb_rehab_device_claims` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `hos_guid` VARCHAR(50) DEFAULT NULL,
        `vn` VARCHAR(20) DEFAULT NULL,
        `an` VARCHAR(20) DEFAULT NULL,
        `hn` VARCHAR(15) DEFAULT NULL,
        `cid` VARCHAR(17) DEFAULT NULL,
        `ptname` VARCHAR(150) DEFAULT NULL,
        `age` INT(3) DEFAULT NULL,
        `vstdate` DATE DEFAULT NULL,
        `vsttime` TIME DEFAULT NULL,
        `fund_type` VARCHAR(20) DEFAULT 'UC',
        `pttype` VARCHAR(10) DEFAULT NULL,
        `pttype_name` VARCHAR(150) DEFAULT NULL,
        `informaddr` TEXT DEFAULT NULL,
        `icode` VARCHAR(15) DEFAULT NULL,
        `device_code` VARCHAR(20) DEFAULT NULL,
        `device_name` VARCHAR(255) DEFAULT NULL,
        `device_category` VARCHAR(100) DEFAULT 'อุปกรณ์ฟื้นฟูสมรรถภาพ',
        `qty` DECIMAL(10,2) DEFAULT 1.00,
        `unit_price` DECIMAL(12,2) DEFAULT 0.00,
        `claim_amount` DECIMAL(12,2) DEFAULT 0.00,
        `compensated_amount` DECIMAL(12,2) DEFAULT 0.00,
        `diff_amount` DECIMAL(12,2) DEFAULT 0.00,
        `claim_status` VARCHAR(30) DEFAULT 'UNCLAIMED',
        `rep_no` VARCHAR(100) DEFAULT NULL,
        `receipt_no` VARCHAR(100) DEFAULT NULL,
        `receipt_date` VARCHAR(50) DEFAULT NULL,
        `receipt_source` VARCHAR(20) DEFAULT NULL,
        `stm_docno` VARCHAR(100) DEFAULT NULL,
        `stm_date` DATE DEFAULT NULL,
        `denial_code` VARCHAR(50) DEFAULT NULL,
        `error_codes` TEXT DEFAULT NULL,
        `denial_message` TEXT DEFAULT NULL,
        `is_resubmitted` TINYINT(1) DEFAULT 0,
        `resubmit_date` DATE DEFAULT NULL,
        `doctor_name` VARCHAR(150) DEFAULT NULL,
        `diag_code` VARCHAR(20) DEFAULT NULL,
        `diag_name` VARCHAR(255) DEFAULT NULL,
        `note` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_hos_guid` (`hos_guid`),
        INDEX `idx_vn` (`vn`),
        INDEX `idx_hn` (`hn`),
        INDEX `idx_vstdate` (`vstdate`),
        INDEX `idx_fund` (`fund_type`),
        INDEX `idx_device` (`device_code`),
        INDEX `idx_status` (`claim_status`),
        INDEX `idx_denial` (`denial_code`),
        INDEX `idx_receipt` (`receipt_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    mysqli_query($conn, $sql_claims);

    // Defensive check to add new columns if table existed previously
    $cols_to_add = [
        'hos_guid' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `hos_guid` VARCHAR(50) DEFAULT NULL AFTER `id`, ADD UNIQUE KEY `uniq_hos_guid` (`hos_guid`)",
        'age' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `age` INT(3) DEFAULT NULL AFTER `ptname`",
        'vsttime' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `vsttime` TIME DEFAULT NULL AFTER `vstdate`",
        'informaddr' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `informaddr` TEXT DEFAULT NULL AFTER `pttype_name`",
        'receipt_no' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `receipt_no` VARCHAR(100) DEFAULT NULL AFTER `rep_no`",
        'receipt_date' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `receipt_date` VARCHAR(50) DEFAULT NULL AFTER `receipt_no`",
        'receipt_source' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `receipt_source` VARCHAR(20) DEFAULT NULL AFTER `receipt_date`",
        'diag_name' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `diag_name` VARCHAR(255) DEFAULT NULL AFTER `diag_code`",
        'error_codes' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `error_codes` TEXT DEFAULT NULL AFTER `denial_code`",
        'dep_code' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `dep_code` VARCHAR(10) DEFAULT NULL AFTER `informaddr`",
        'department_name' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `department_name` VARCHAR(100) DEFAULT NULL AFTER `dep_code`",
        'department_group' => "ALTER TABLE `imr_tb_rehab_device_claims` ADD COLUMN `department_group` VARCHAR(50) DEFAULT 'อื่นๆ / แผนกสนับสนุน' AFTER `department_name`, ADD INDEX `idx_dept_group` (`department_group`)"
    ];
    foreach ($cols_to_add as $c_col => $c_sql) {
        $chk_c = mysqli_query($conn, "SHOW COLUMNS FROM `imr_tb_rehab_device_claims` LIKE '$c_col'");
        if ($chk_c && mysqli_num_rows($chk_c) == 0) {
            mysqli_query($conn, $c_sql);
        }
    }

    // 2. Create Denial Codes Dictionary Table
    $sql_denials = "CREATE TABLE IF NOT EXISTS `imr_tb_rehab_denial_codes` (
        `denial_code` VARCHAR(50) PRIMARY KEY,
        `fund_type` VARCHAR(20) DEFAULT 'ALL',
        `deny_type` VARCHAR(50) DEFAULT 'Corrective',
        `description_th` TEXT DEFAULT NULL,
        `severity` ENUM('RETRY','APPEAL','UNCOLLECTIBLE') DEFAULT 'RETRY',
        `action_guide` TEXT DEFAULT NULL,
        `common_causes` TEXT DEFAULT NULL,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    mysqli_query($conn, $sql_denials);

    $chk_dt = mysqli_query($conn, "SHOW COLUMNS FROM `imr_tb_rehab_denial_codes` LIKE 'deny_type'");
    if ($chk_dt && mysqli_num_rows($chk_dt) == 0) {
        mysqli_query($conn, "ALTER TABLE `imr_tb_rehab_denial_codes` ADD COLUMN `deny_type` VARCHAR(50) DEFAULT 'Corrective' AFTER `fund_type`");
    }

    // 3. Seed Knowledgebase Denial Codes from Excel (933 Codes) if table count is low
    $chk_denial = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `imr_tb_rehab_denial_codes`");
    $cnt_denial = ($chk_denial) ? mysqli_fetch_assoc($chk_denial)['cnt'] : 0;

    $c_deny_excel = dirname(dirname(dirname(__DIR__))) . '/comment/รายละเอียดคำอธิบาย C Deny.xlsx';
    if ($cnt_denial < 500 && file_exists($c_deny_excel)) {
        $autoload = dirname(dirname(dirname(__DIR__))) . '/system/vendor/autoload.php';
        if (file_exists($autoload)) require_once $autoload;
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($c_deny_excel);
                $sh = $ss->getSheetByName('รายละเอียด');
                if ($sh) {
                    $stmt_seed = mysqli_prepare($conn, "INSERT INTO `imr_tb_rehab_denial_codes` 
                        (denial_code, fund_type, deny_type, description_th, severity, action_guide, common_causes, updated_at)
                        VALUES (?, 'ALL', ?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                        deny_type = VALUES(deny_type),
                        description_th = VALUES(description_th),
                        severity = VALUES(severity),
                        action_guide = VALUES(action_guide),
                        common_causes = VALUES(common_causes),
                        updated_at = NOW()");
                    if ($stmt_seed) {
                        for ($rx = 2; $rx <= $sh->getHighestRow(); $rx++) {
                            $code_x = trim((string)$sh->getCell('B' . $rx)->getValue());
                            if ($code_x === '') continue;
                            $type_x = trim((string)$sh->getCell('A' . $rx)->getValue());
                            $desc_x = trim((string)$sh->getCell('C' . $rx)->getValue());
                            $guide_x = trim((string)$sh->getCell('D' . $rx)->getValue());
                            $sev_x = (strcasecmp($type_x, 'Deny') === 0) ? 'UNCOLLECTIBLE' : ((strcasecmp($type_x, 'Verify') === 0) ? 'APPEAL' : 'RETRY');
                            $cause_x = "สปสช. ระบุประเภท $type_x (รหัส $code_x)";
                            mysqli_stmt_bind_param($stmt_seed, "ssssss", $code_x, $type_x, $desc_x, $sev_x, $guide_x, $cause_x);
                            mysqli_stmt_execute($stmt_seed);
                        }
                        mysqli_stmt_close($stmt_seed);
                    }
                }
            } catch (\Exception $e) {}
        }
    }
    $chk_denial = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `imr_tb_rehab_denial_codes`");
    $cnt_denial = ($chk_denial) ? mysqli_fetch_assoc($chk_denial)['cnt'] : 0;

    if ($cnt_denial == 0) {
        $seeds = [
            [
                'D179', 'UC',
                'ระบุรหัสอุปกรณ์กายอุปกรณ์ไม่ถูกต้อง หรือไม่มีในบัญชีรายการที่ สปสช. กำหนด',
                'RETRY',
                '1. ตรวจสอบรหัส ADP หมวด 2 ให้ตรงกับประกาศ สปสช. ล่าสุด เช่น รถนั่งคนพิการ (8901/8902), L-S Support (8307), Knee Support (8608), อุปกรณ์พยุงส้นเท้า (8610)<br>2. แก้ไขรหัสในระบบ HOSxP และ e-Claim ให้ตรงกัน<br>3. แนบแบบฟอร์มการประเมินและสั่งใช้อุปกรณ์ แล้วส่งเบิกซ้ำ (Resubmit)',
                'พิมพ์รหัสผิดพลาด, ใช้รหัสเก่าที่ยกเลิกแล้ว, หรือเลือกหมวดหมู่อุปกรณ์ไม่ตรงกับข้อบ่งชี้'
            ],
            [
                'D01', 'ALL',
                'ข้อมูลผู้รับบริการไม่ตรงกับฐานข้อมูลทะเบียนราษฎร์ หรือสิทธิไม่ตรง',
                'RETRY',
                '1. ตรวจสอบเลขประจำตัวประชาชน (CID), คำนำหน้า, ชื่อ-นามสกุล, วันเดือนปีเกิด ใน HOSxP ให้ตรงกับบัตรประชาชนจริง<br>2. ตรวจสอบสิทธิที่หน้าเว็บ สปสช./กรมบัญชีกลาง ในวันที่มารับบริการ<br>3. ปรับปรุงข้อมูลและส่งเบิกใหม่',
                'สะกดชื่อ-สกุลผิด, เลขบัตร 13 หลักสลับ, หรือผู้ป่วยเปลี่ยนชื่อตัว/ชื่อสกุล'
            ],
            [
                'D02', 'ALL',
                'ผู้ป่วยมีสิทธิการรักษาอื่นที่มีสิทธิเบิกก่อน (เช่น สิทธิประกันสังคม หรือ กองทุนเงินทดแทน)',
                'APPEAL',
                '1. ตรวจสอบว่าผู้ป่วยประสบอุบัติเหตุจากการทำงานหรือไม่ (กองทุนเงินทดแทน/พรบ.)<br>2. หากเป็นสิทธิข้าราชการ/ประกันสังคม ให้ส่งเบิกต้นสังกัดก่อน<br>3. หากตรวจสอบแล้วเป็นสิทธิบัตรทองถูกต้อง ให้แนบหลักฐานการสิ้นสุดสิทธิอื่นแล้วส่งอุทธรณ์',
                'มีสิทธิประกันสังคมซ้อน, สิทธิข้าราชการซ้อน, หรือเป็นอุบัติเหตุจราจร/จากการทำงาน'
            ],
            [
                'C01', 'ALL',
                'เกินจำนวนครั้งหรือโควตาที่กำหนดต่อปี (Frequency Limit Exceeded)',
                'APPEAL',
                '1. ตรวจสอบประวัติการได้รับอุปกรณ์ย้อนหลังของผู้ป่วย เช่น รถนั่งคนพิการกำหนดเบิกได้ 1 คัน ต่อ 3 ปี, Walker กำหนด 1 อัน ต่อ 2 ปี<br>2. หากอุปกรณ์เดิมชำรุดเสียหายสิ้นเชิงหรือสรีระผู้ป่วยเปลี่ยน ให้แพทย์ทำหนังสือรับรองเหตุผลความจำเป็นแล้วส่งอุทธรณ์',
                'ผู้ป่วยเคยได้รับอุปกรณ์ชนิดเดียวกันจากหน่วยบริการอื่นหรือ รพ.โพนทราย ภายในระยะเวลากำหนด'
            ],
            [
                'C02', 'ALL',
                'ส่งเบิกเกินระยะเวลาที่กำหนด (Late Submission)',
                'APPEAL',
                '1. ตรวจสอบวันที่รับบริการเทียบกับเกณฑ์กำหนดส่งเบิก (ปกติภายใน 30-90 วัน)<br>2. จัดทำบันทึกชี้แจงเหตุผลความจำเป็นที่มีการส่งล่าช้าลงนามโดยผู้บริหาร<br>3. แนบเอกสารส่งอุทธรณ์ผ่านระบบ e-Claim กองทุน',
                'ส่งข้อมูลล่าช้าหลังสิ้นสุดรอบบิลหรือเลยกำหนดระยะเวลาปิดรอบงบประมาณ'
            ],
            [
                'E01', 'ALL',
                'รหัสการวินิจฉัยโรคหลัก (Principal Diagnosis) ไม่สอดคล้องกับข้อบ่งชี้การใช้อุปกรณ์',
                'RETRY',
                '1. ตรวจสอบรหัสโรค ICD-10 จากแพทย์ผู้ตรวจ เช่น L-S Support ต้องมีรหัสโรคปวดหลัง/กระดูกสันหลัง (M54.5, M47.8), รถนั่งคนพิการต้องมีรหัสอัมพาต/กล้ามเนื้ออ่อนแรง (G81.9, G82.2)<br>2. ให้แพทย์ผู้ดูแลปรับแก้รหัสการวินิจฉัยให้ครอบคลุม แล้วส่งเบิกซ้ำ',
                'ระบุรหัสโรคทั่วไป (Z-code) หรือรหัสโรคที่ไม่เข้าเกณฑ์ข้อบ่งชี้ทางการแพทย์'
            ],
            [
                'E02', 'ALL',
                'ไม่มีเอกสารหรือใบสั่งการฟื้นฟู/ใบรับรองแพทย์แนบในระบบ e-Claim',
                'RETRY',
                '1. จัดเตรียมเอกสาร: ใบสั่งอุปกรณ์ (Medical Order), ใบประเมินสมรรถภาพทางกายภาพบำบัด, สำเนาสมุดคนพิการ (ถ้ามี)<br>2. สแกนเป็นไฟล์ PDF และอัปโหลดแนบในระบบ e-Claim<br>3. ปรับสถานะเป็นส่งเบิกซ้ำ',
                'เจ้าหน้าที่ไม่ได้อัปโหลดไฟล์แนบตอนส่งข้อมูล หรือไฟล์แนบเปิดไม่ได้/ภาพไม่ชัดเจน'
            ],
            [
                'E03', 'ALL',
                'อุปกรณ์นี้กำหนดให้เบิกจ่ายได้เฉพาะกรณีผู้ป่วยใน (IPD Only)',
                'APPEAL',
                '1. ตรวจสอบว่าผู้ป่วยรับบริการเป็น OPD หรือ IPD<br>2. หากเป็นกรณีส่งต่อหรือรับบริการต่อเนื่อง ให้ตรวจสอบเกณฑ์ระเบียบกองทุนหรือเปลี่ยนรหัสอุปกรณ์ที่อนุญาตใน OPD',
                'สั่งจ่ายอุปกรณ์ที่มีเงื่อนไขเฉพาะผู้ป่วยในในห้องตรวจผู้ป่วยนอก'
            ],
            [
                'E04', 'ALL',
                'ราคาที่เรียกเก็บสูงกว่าอัตราเพดานที่ สปสช./กรมบัญชีกลาง กำหนด (Price Ceiling Exceeded)',
                'RETRY',
                '1. ตรวจสอบอัตราเพดานราคาจ่ายชดเชยตามประกาศ (เช่น รถนั่งคนพิการปรับไม่ได้ 4,400 บาท, L-S Support 1,000 บาท)<br>2. ปรับลดราคาเรียกเก็บส่วนเกินให้เท่ากับราคาเพดาน หรือแยกเก็บส่วนเกินจากสิทธิผู้ป่วย<br>3. ส่งเบิกซ้ำด้วยยอดที่ถูกต้อง',
                'ตั้งราคาเรียกเก็บสูงกว่าเกณฑ์ที่กองทุนกำหนดให้ชดเชย'
            ],
            [
                'R01', 'ALL',
                'รายการถูกดึงเงินคืนเนื่องจากตรวจพบการเบิกซ้ำซ้อน (Recalled / Clawback)',
                'APPEAL',
                '1. ตรวจสอบใน Statement รอบก่อนหน้าว่าได้รับโอนเงินไปแล้วหรือไม่<br>2. หากยืนยันว่าเป็นคนละครั้งการรับบริการ ให้แนบประวัติการตรวจและเวชระเบียนเพื่อยื่นอุทธรณ์ยืนยันความถูกต้อง',
                'ระบบกองทุนตรวจพบเลข VN หรือวันที่รับบริการซ้ำกับที่เคยจ่ายเงินไปแล้ว'
            ],
            [
                '179', 'UC',
                'ระบุรหัสอุปกรณ์กายอุปกรณ์ไม่ถูกต้อง หรือไม่มีในบัญชีรายการที่ สปสช. กำหนด (D179)',
                'RETRY',
                '1. ตรวจสอบรหัส ADP หมวด 2 ให้ตรงกับประกาศ สปสช. ล่าสุด เช่น รถนั่งคนพิการ (8901/8902), L-S Support (8307), Knee Support (8608), อุปกรณ์พยุงส้นเท้า (8610)<br>2. แก้ไขรหัสในระบบ HOSxP และ e-Claim ให้ตรงกัน<br>3. แนบแบบฟอร์มการประเมินและสั่งใช้อุปกรณ์ แล้วส่งเบิกซ้ำ (Resubmit)',
                'พิมพ์รหัสผิดพลาด, ใช้รหัสเก่าที่ยกเลิกแล้ว, หรือเลือกหมวดหมู่อุปกรณ์ไม่ตรงกับข้อบ่งชี้'
            ],
            [
                '210', 'ALL',
                'ส่งเบิกเกินระยะเวลาที่กำหนดให้ส่งข้อมูล (Late Submission / D210)',
                'APPEAL',
                '1. ตรวจสอบวันที่รับบริการเทียบกับเกณฑ์กำหนดส่งเบิกของ สปสช. (ต้องส่งภายในระยะเวลาที่กำหนด)<br>2. หากมีเหตุสุดวิสัย ให้ทำหนังสือชี้แจงเหตุผลความจำเป็นลงนามโดยผู้บริหาร รพ.<br>3. ยื่นอุทธรณ์ผ่านระบบ e-Claim',
                'ส่งข้อมูลล่าช้าหลังสิ้นสุดรอบบิล หรือเลยกำหนดระยะเวลาปิดรอบงบประมาณ'
            ],
            [
                '349', 'ALL',
                'รหัสโรคหลัก/โรคร่วมไม่สอดคล้องกับข้อบ่งชี้การใช้อุปกรณ์หรือหัตถการ (D349)',
                'RETRY',
                '1. ตรวจสอบการวินิจฉัยโรค (ICD-10) จากแพทย์ผู้ตรวจ เช่น ปวดหลังเรื้อรัง (M54.5) หรืออัมพาตครึ่งซีก (G81.9)<br>2. ให้แพทย์ปรับแก้รหัสโรคให้ตรงตามเกณฑ์ สปสช.<br>3. ส่งเบิกซ้ำ (Resubmit)',
                'ระบุรหัสโรคทั่วไปที่ไม่เข้าเกณฑ์ข้อบ่งชี้ทางการแพทย์'
            ],
            [
                '853', 'ALL',
                'ตรวจพบการบันทึกข้อมูลค่ารักษาหรือเบิกอุปกรณ์ซ้ำซ้อนในวันเดียวกัน (D853)',
                'APPEAL',
                '1. ตรวจสอบว่าในวันดังกล่าวมีการสั่งอุปกรณ์ซ้ำ 2 รายการ หรือเคยเคลมในสิทธิอื่นไปแล้วหรือไม่<br>2. หากเป็นความจำเป็นทางการแพทย์คนละรายการ ให้แนบใบเวชระเบียนชี้แจงเพื่อยื่นอุทธรณ์',
                'กดบันทึกรายการซ้ำใน HOSxP หรือส่งเคลมเบิ้ล 2 ครั้ง'
            ],
            [
                '438', 'ALL',
                'ไม่พบข้อมูลการลงทะเบียนสิทธิ หรือหน่วยบริการปฐมภูมิไม่ตรงตามเงื่อนไข (D438)',
                'RETRY',
                '1. ตรวจสอบสิทธิในวันรับบริการผ่านระบบตรวจสอบสิทธิ สปสช.<br>2. หากมีใบส่งตัว (Refer) ให้ตรวจสอบว่าได้บันทึกเลขที่ใบส่งตัวและ รพ.ต้นสังกัดครบถ้วนหรือไม่<br>3. ปรับปรุงข้อมูลส่งตัวแล้วส่งเบิกใหม่',
                'สิทธิหมดอายุ, ไม่ได้แนบเลขที่ใบส่งตัว หรือเลือกหน่วยบริการเจ้าของสิทธิผิด'
            ],
            [
                '237', 'ALL',
                'เลขประจำตัวประชาชน 13 หลักไม่ถูกต้อง หรือไม่พบในฐานข้อมูลทะเบียนราษฎร (D237)',
                'RETRY',
                '1. ขอสำเนาบัตรประชาชนตัวจริงจากผู้ป่วยเพื่อตรวจสอบเลข 13 หลัก<br>2. อัปเดตข้อมูลผู้ป่วยใน HOSxP ให้ถูกต้อง<br>3. ปรับปรุงข้อมูลแล้วส่งเบิกใหม่',
                'คีย์เลขประจำตัวประชาชน 13 หลักสลับ หรือผิดหลักสุดท้าย (Check digit ไม่ผ่าน)'
            ],
            [
                '169', 'ALL',
                'ระบุรหัสบริการไม่ตรงกับระดับของหน่วยบริการ (Service Level Mismatch / D169)',
                'APPEAL',
                '1. ตรวจสอบว่ารหัสอุปกรณ์ดังกล่าวเปิดให้ รพ.ชุมชน (F2) จ่ายชดเชยได้หรือไม่<br>2. หากต้องจ่ายโดยแพทย์เฉพาะทาง ให้แนบใบรับรองจากแพทย์เฉพาะทางประกอบการอุทธรณ์',
                'รายการที่กำหนดให้เฉพาะ รพ.ศูนย์/รพ.ทั่วไป สั่งจ่าย'
            ],
            [
                '309', 'ALL',
                'อัตราค่าบริการหรืออุปกรณ์เกินเพดานที่ สปสช. กำหนด (D309)',
                'RETRY',
                '1. ปรับลดยอดเรียกเก็บให้ไม่เกินอัตราเพดานมาตรฐานของ สปสช.<br>2. ส่งเบิกซ้ำตามอัตราเพดานที่ถูกต้อง',
                'บันทึกราคาจำหน่ายสูงกว่าเกณฑ์ราคาชดเชยของกองทุน'
            ],
            [
                'D99', 'ALL',
                'รหัสปฏิเสธอื่นๆ / ตรวจพบข้อผิดพลาดทั่วไปในระบบ',
                'RETRY',
                '1. ตรวจสอบข้อความปฏิเสธดั้งเดิมในเอกสาร Statement (STM)<br>2. ประสานงานกลุ่มงานประกันสุขภาพหรือสำนักงาน สปสช. เขต เพื่อสอบถามเงื่อนไขเฉพาะ<br>3. ดำเนินการปรับปรุงตามคำแนะนำของเจ้าหน้าที่กองทุน',
                'ข้อผิดพลาดทางเทคนิคของระบบเครือข่าย หรือรูปแบบไฟล์ส่งออกไม่สมบูรณ์'
            ]
        ];

        $stmt = mysqli_prepare($conn, "INSERT INTO `imr_tb_rehab_denial_codes` 
            (`denial_code`, `fund_type`, `description_th`, `severity`, `action_guide`, `common_causes`) 
            VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            foreach ($seeds as $s) {
                mysqli_stmt_bind_param($stmt, "ssssss", $s[0], $s[1], $s[2], $s[3], $s[4], $s[5]);
                mysqli_stmt_execute($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }

    // 4. Register Menu into tb_menus if not exists
    $chk_menu = mysqli_query($conn, "SELECT id FROM `tb_menus` WHERE `menu_link` LIKE '%rehab_device_tracker%' LIMIT 1");
    if ($chk_menu && mysqli_num_rows($chk_menu) == 0) {
        $sql_add_menu = "INSERT INTO `tb_menus` 
            (`menu_group`, `menu_name`, `menu_link`, `menu_icon`, `sort_order`) 
            VALUES 
            ('ศูนย์จัดเก็บรายได้', 'ติดตามชดเชยอุปกรณ์', 'plugins/rehab_device_tracker/index.php', '<i class=\"bx bx-accessibility text-primary\"></i>', 10.5)";
        if (mysqli_query($conn, $sql_add_menu)) {
            $new_menu_id = mysqli_insert_id($conn);
            // Grant permission to all existing users so it appears immediately
            $get_users = mysqli_query($conn, "SELECT id FROM `users`");
            if ($get_users) {
                while ($u = mysqli_fetch_assoc($get_users)) {
                    $uid = $u['id'];
                    mysqli_query($conn, "INSERT IGNORE INTO `tb_user_permissions` (`user_id`, `menu_id`) VALUES ('$uid', '$new_menu_id')");
                }
            }
        }
    }

    return true;
}

// Automatically trigger migration once per session to eliminate heavy DDL overhead on every request
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (empty($_SESSION['rehab_auto_migrated_v1'])) {
    rehab_auto_migrate($conn);
    $_SESSION['rehab_auto_migrated_v1'] = true;
}

// Helper to release session lock for parallel AJAX requests
function rehab_release_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

// Helper for Thai Date Formatting
function rehab_date_thai($date) {
    if (!$date || $date == '0000-00-00') return '-';
    $time = strtotime($date);
    if (!$time) return '-';
    $thai_months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $d = date("j", $time);
    $m = $thai_months[intval(date("n", $time))];
    $y = date("Y", $time) + 543;
    return "$d $m $y";
}

// Helper for Fund Name
function rehab_fund_label($fund) {
    switch (strtoupper(trim($fund))) {
        case 'UC':
        case 'UCS':
            return '<span class="badge bg-label-success">สปสช. (UC)</span>';
        case 'OFC':
        case 'CSMBS':
            return '<span class="badge bg-label-primary">เบิกจ่ายตรง (ข้าราชการ)</span>';
        case 'LGO':
            return '<span class="badge bg-label-info">อปท.</span>';
        case 'SSS':
            return '<span class="badge bg-label-warning">ประกันสังคม</span>';
        default:
            return '<span class="badge bg-label-secondary">' . htmlspecialchars($fund ?: 'อื่นๆ') . '</span>';
    }
}

// Helper for Claim Status Badge
function rehab_status_badge($status) {
    switch (strtoupper(trim($status))) {
        case 'PAID':
            return '<span class="badge bg-success"><i class="bx bx-check me-1"></i>เคลมผ่าน/ชดเชยแล้ว</span>';
        case 'WAIT_STM':
            return '<span class="badge bg-warning text-dark"><i class="bx bx-time me-1"></i>รอผล STM</span>';
        case 'REJECTED':
            return '<span class="badge bg-danger"><i class="bx bx-x me-1"></i>ปฏิเสธ/เคลมไม่ผ่าน</span>';
        case 'RECALLED':
            return '<span class="badge bg-dark"><i class="bx bx-undo me-1"></i>ถูกดึงเงินคืน</span>';
        case 'PARTIAL':
            return '<span class="badge bg-info"><i class="bx bx-pie-chart-alt me-1"></i>ชดเชยบางส่วน</span>';
        case 'UNCLAIMED':
        default:
            return '<span class="badge bg-secondary"><i class="bx bx-minus me-1"></i>รอผลตรวจสอบ</span>';
    }
}

/**
 * Sanitize receipt numbers, stripping quotes, hyphens, and whitespace.
 * Returns empty string if invalid or a placeholder.
 */
function rehab_clean_receipt_no($raw) {
    if ($raw === null) return '';
    $val = trim((string)$raw);
    $val = trim($val, "\"'\t\n\r -");
    if ($val === '' || $val === '-' || $val === '""' || $val === "''" || strlen($val) < 2) {
        return '';
    }
    return $val;
}

/**
 * Synchronize Debtor Rights Receipts & Compensation
 * Queries imr_tb_debtor_rights_opd (by vn) and imr_tb_debtor_rights_ipd (by an)
 * Extracts receipt number and date from `mobile` (format: [receipt]-[date]) and `bill`/`billdate`
 * Sets compensated amount if missing from Statement, sets PAID / PARTIAL status.
 * STRICT: Ignores empty quote strings '""' and ensures unreceipted debtors are not marked as paid.
 */
function rehab_sync_debtor_rights($conn, $where_condition = "1=1") {
    $updated_total = 0;

    // 1. Process OPD debtor matching
    $sql_opd = "SELECT r.id, r.vn, r.claim_amount, r.claim_status, r.compensated_amount, r.rep_no,
                       o.bill, o.billdate, o.mobile, o.follow_money, o.rcpno
                FROM `imr_tb_rehab_device_claims` r
                JOIN `imr_tb_debtor_rights_opd` o ON r.vn = o.vn
                WHERE (
                    (o.mobile IS NOT NULL AND o.mobile NOT IN ('', '-', '\"\"', \"''\") AND LENGTH(REPLACE(REPLACE(TRIM(o.mobile), '\"', ''), '\'', '')) >= 3)
                    OR (o.bill IS NOT NULL AND o.bill NOT IN ('', '-', '\"\"', \"''\") AND LENGTH(REPLACE(REPLACE(TRIM(o.bill), '\"', ''), '\'', '')) >= 3)
                    OR (o.follow_money IS NOT NULL AND CAST(o.follow_money AS DECIMAL(10,2)) > 0)
                    OR (o.rcpno IS NOT NULL AND o.rcpno NOT IN ('', '-', '\"\"', \"''\") AND LENGTH(REPLACE(REPLACE(TRIM(o.rcpno), '\"', ''), '\'', '')) >= 3)
                ) AND $where_condition";
    $res_opd = mysqli_query($conn, $sql_opd);
    
    if ($res_opd && mysqli_num_rows($res_opd) > 0) {
        $stmt_upd = mysqli_prepare($conn, "UPDATE `imr_tb_rehab_device_claims` SET 
            receipt_no = ?,
            receipt_date = ?,
            receipt_source = 'DEBTOR_OPD',
            compensated_amount = IF(compensated_amount > 0, compensated_amount, ?),
            diff_amount = GREATEST(0, claim_amount - IF(compensated_amount > 0, compensated_amount, ?)),
            claim_status = IF(claim_status = 'PAID', 'PAID', ?),
            rep_no = IF(rep_no IS NOT NULL AND rep_no != '' AND rep_no NOT IN ('\"\"', \"''\", '-'), rep_no, ?)
            WHERE id = ?");

        if ($stmt_upd) {
            while ($r = mysqli_fetch_assoc($res_opd)) {
                $mob = trim($r['mobile'] ?? '');
                $bill = trim($r['bill'] ?? '');
                $bdate = trim($r['billdate'] ?? '');
                $rcpno = trim($r['rcpno'] ?? '');
                $follow = floatval(trim($r['follow_money'] ?? 0));
                $claim_amt = floatval($r['claim_amount']);
                
                $receipt_no = '';
                $receipt_date = '';
                
                if (!empty($mob) && $mob !== '-') {
                    $parts = explode('-', $mob);
                    $clean_part = rehab_clean_receipt_no($parts[0]);
                    if (!empty($clean_part)) {
                        $receipt_no = $clean_part;
                        if (isset($parts[1])) $receipt_date = trim($parts[1]);
                    }
                }
                
                if (empty($receipt_no) && !empty($bill) && $bill !== '-') {
                    $clean_b = rehab_clean_receipt_no($bill);
                    if (!empty($clean_b)) {
                        $receipt_no = $clean_b;
                        $receipt_date = $bdate;
                    }
                }
                
                if (empty($receipt_no) && !empty($rcpno)) {
                    $clean_rcp = rehab_clean_receipt_no($rcpno);
                    if (!empty($clean_rcp)) {
                        $receipt_no = $clean_rcp;
                    }
                }
                
                // CRITICAL DEFENSIVE CHECK:
                // If there is NO valid receipt number AND NO positive follow money -> SKIP!
                if (empty($receipt_no) && $follow <= 0) {
                    continue;
                }
                
                $comp = $claim_amt;
                if ($follow > 0) {
                    $comp = min($claim_amt, $follow);
                }
                
                $st = ($comp >= $claim_amt) ? 'PAID' : 'PARTIAL';
                $rcpt_param = !empty($receipt_no) ? $receipt_no : null;
                $rcpt_date_param = !empty($receipt_date) ? $receipt_date : null;
                $rep_param = !empty($receipt_no) ? $receipt_no : null;
                
                mysqli_stmt_bind_param($stmt_upd, "ssddssi", $rcpt_param, $rcpt_date_param, $comp, $comp, $st, $rep_param, $r['id']);
                if (mysqli_stmt_execute($stmt_upd)) {
                    $updated_total++;
                }
            }
            mysqli_stmt_close($stmt_upd);
        }
    }

    // 2. Process IPD debtor matching
    $sql_ipd = "SELECT r.id, r.an, r.claim_amount, r.claim_status, r.compensated_amount, r.rep_no,
                       i.bill, i.billdate, i.mobile, i.follow_money, i.rcpno
                FROM `imr_tb_rehab_device_claims` r
                JOIN `imr_tb_debtor_rights_ipd` i ON r.an = i.an
                WHERE (
                    (i.mobile IS NOT NULL AND i.mobile NOT IN ('', '-', '\"\"', \"''\") AND LENGTH(REPLACE(REPLACE(TRIM(i.mobile), '\"', ''), '\'', '')) >= 3)
                    OR (i.bill IS NOT NULL AND i.bill NOT IN ('', '-', '\"\"', \"''\") AND LENGTH(REPLACE(REPLACE(TRIM(i.bill), '\"', ''), '\'', '')) >= 3)
                    OR (i.follow_money IS NOT NULL AND CAST(i.follow_money AS DECIMAL(10,2)) > 0)
                    OR (i.rcpno IS NOT NULL AND i.rcpno NOT IN ('', '-', '\"\"', \"''\") AND LENGTH(REPLACE(REPLACE(TRIM(i.rcpno), '\"', ''), '\'', '')) >= 3)
                ) AND $where_condition";
    $res_ipd = mysqli_query($conn, $sql_ipd);
    
    if ($res_ipd && mysqli_num_rows($res_ipd) > 0) {
        $stmt_upd_ipd = mysqli_prepare($conn, "UPDATE `imr_tb_rehab_device_claims` SET 
            receipt_no = ?,
            receipt_date = ?,
            receipt_source = 'DEBTOR_IPD',
            compensated_amount = IF(compensated_amount > 0, compensated_amount, ?),
            diff_amount = GREATEST(0, claim_amount - IF(compensated_amount > 0, compensated_amount, ?)),
            claim_status = IF(claim_status = 'PAID', 'PAID', ?),
            rep_no = IF(rep_no IS NOT NULL AND rep_no != '' AND rep_no NOT IN ('\"\"', \"''\", '-'), rep_no, ?)
            WHERE id = ?");

        if ($stmt_upd_ipd) {
            while ($r = mysqli_fetch_assoc($res_ipd)) {
                $mob = trim($r['mobile'] ?? '');
                $bill = trim($r['bill'] ?? '');
                $bdate = trim($r['billdate'] ?? '');
                $rcpno = trim($r['rcpno'] ?? '');
                $follow = floatval(trim($r['follow_money'] ?? 0));
                $claim_amt = floatval($r['claim_amount']);
                
                $receipt_no = '';
                $receipt_date = '';
                
                if (!empty($mob) && $mob !== '-') {
                    $parts = explode('-', $mob);
                    $clean_part = rehab_clean_receipt_no($parts[0]);
                    if (!empty($clean_part)) {
                        $receipt_no = $clean_part;
                        if (isset($parts[1])) $receipt_date = trim($parts[1]);
                    }
                }
                
                if (empty($receipt_no) && !empty($bill) && $bill !== '-') {
                    $clean_b = rehab_clean_receipt_no($bill);
                    if (!empty($clean_b)) {
                        $receipt_no = $clean_b;
                        $receipt_date = $bdate;
                    }
                }
                
                if (empty($receipt_no) && !empty($rcpno)) {
                    $clean_rcp = rehab_clean_receipt_no($rcpno);
                    if (!empty($clean_rcp)) {
                        $receipt_no = $clean_rcp;
                    }
                }
                
                // CRITICAL DEFENSIVE CHECK:
                // If there is NO valid receipt number AND NO positive follow money -> SKIP!
                if (empty($receipt_no) && $follow <= 0) {
                    continue;
                }
                
                $comp = $claim_amt;
                if ($follow > 0) {
                    $comp = min($claim_amt, $follow);
                }
                
                $st = ($comp >= $claim_amt) ? 'PAID' : 'PARTIAL';
                $rcpt_param = !empty($receipt_no) ? $receipt_no : null;
                $rcpt_date_param = !empty($receipt_date) ? $receipt_date : null;
                $rep_param = !empty($receipt_no) ? $receipt_no : null;
                
                mysqli_stmt_bind_param($stmt_upd_ipd, "ssddssi", $rcpt_param, $rcpt_date_param, $comp, $comp, $st, $rep_param, $r['id']);
                if (mysqli_stmt_execute($stmt_upd_ipd)) {
                    $updated_total++;
                }
            }
            mysqli_stmt_close($stmt_upd_ipd);
        }
    }

    return $updated_total;
}


