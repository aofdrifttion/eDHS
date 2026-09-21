<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - SSS Debtor API Service
 * ====================================================================================
 * API สำหรับจัดการลูกหนี้ประกันสังคม: ตรวจสอบโครงสร้างฐานข้อมูล, จัดการคอนฟิกการจับคู่ (Mapping),
 * และประมวลผลจำแนก/ตัดแยกหมวดย่อย Instrument สำหรับ OPD และ IPD
 * 
 * @author eDHS Developer
 * @version 1.0.0 (2026-08-31)
 * ====================================================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_config/config.php';
require_once __DIR__ . '/includes/sss_migration_helper.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($conn) || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล eDHS ได้'], JSON_UNESCAPED_UNICODE);
    exit;
}

// เรียก Auto-Migration ตรวจเช็คตารางทุกครั้งที่มีการเรียก API
ensure_sss_tables($conn);

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

switch ($action) {

    // -------------------------------------------------------------
    // 1. ตรวจสอบสถานะความพร้อมของฐานข้อมูล (Schema Check)
    // -------------------------------------------------------------
    case 'check_schema':
        $success = ensure_sss_tables($conn);
        echo json_encode([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'ตารางฐานข้อมูลลูกหนี้ประกันสังคม SSS พร้อมใช้งาน' : 'เกิดข้อผิดพลาดในการตรวจสอบตาราง',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 2. ดึงการตั้งค่า Mapping Configuration ปัจจุบัน
    // -------------------------------------------------------------
    case 'get_config':
        $config = get_active_sss_config($conn);
        echo json_encode([
            'status' => 'success',
            'config' => $config
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 3. บันทึกการตั้งค่า Mapping Configuration ใหม่
    // -------------------------------------------------------------
    case 'save_config':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw_input = file_get_contents('php://input');
        $json_data = json_decode($raw_input, true);

        if (!$json_data || (!isset($json_data['subgroups_opd']) && !isset($json_data['subgroups_ipd']))) {
            echo json_encode(['status' => 'error', 'message' => 'รูปแบบข้อมูลไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $escaped_val = mysqli_real_escape_string($conn, json_encode($json_data, JSON_UNESCAPED_UNICODE));
        $user_name = isset($_SESSION['fullname']) && !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Admin';
        $escaped_user = mysqli_real_escape_string($conn, $user_name);

        $sql_update = "
            INSERT INTO imr_tb_sss_config (config_key, config_value, updated_by)
            VALUES ('sss_mapping_settings', '$escaped_val', '$escaped_user')
            ON DUPLICATE KEY UPDATE 
                config_value = VALUES(config_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ";

        if (mysqli_query($conn, $sql_update)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกการตั้งค่าประกันสังคมเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
        }
        break;

    // -------------------------------------------------------------
    // 4. คืนค่าการตั้งค่าเริ่มต้น (Restore Defaults)
    // -------------------------------------------------------------
    case 'restore_defaults':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $default_config = get_default_sss_config();
        $escaped_val = mysqli_real_escape_string($conn, json_encode($default_config, JSON_UNESCAPED_UNICODE));
        $user_name = isset($_SESSION['fullname']) && !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Admin';
        $escaped_user = mysqli_real_escape_string($conn, $user_name);

        $sql_update = "
            INSERT INTO imr_tb_sss_config (config_key, config_value, updated_by)
            VALUES ('sss_mapping_settings', '$escaped_val', '$escaped_user')
            ON DUPLICATE KEY UPDATE 
                config_value = VALUES(config_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ";

        if (mysqli_query($conn, $sql_update)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'คืนค่าการตั้งค่าเริ่มต้นเรียบร้อยแล้ว',
                'config' => $default_config
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
        }
        break;

    // -------------------------------------------------------------
    // 5. ดึงรายการเดือนทั้งหมดในระบบ eDHS สำหรับ Dropdown & Checklists
    // -------------------------------------------------------------
    case 'get_months':
        $months = [];
        $sql_m = "
            SELECT DISTINCT monthtxt FROM (
                SELECT monthtxt FROM imr_tb_debtor_rights_opd WHERE monthtxt IS NOT NULL AND monthtxt <> ''
                UNION
                SELECT monthtxt FROM imr_tb_debtor_rights_ipd WHERE monthtxt IS NOT NULL AND monthtxt <> ''
            ) t
            ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC
        ";
        $q_m = mysqli_query($conn, $sql_m);
        if ($q_m) {
            while ($r = mysqli_fetch_assoc($q_m)) {
                $months[] = $r['monthtxt'];
            }
        }
        echo json_encode(['status' => 'success', 'months' => $months], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 6. จำลองผลกระทบการตัดแยกยอดเงินจริง (Live Impact Simulation / Preview Tool)
    // -------------------------------------------------------------
    case 'preview_impact':
        $monthtxt = isset($_REQUEST['monthtxt']) ? trim($_REQUEST['monthtxt']) : '';
        $visit_type = isset($_REQUEST['visit_type']) ? strtoupper(trim($_REQUEST['visit_type'])) : 'OPD';
        if (!in_array($visit_type, ['OPD', 'IPD'])) $visit_type = 'OPD';

        if (empty($monthtxt)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุเดือนที่ต้องการทดสอบ'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $config = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $raw_input = file_get_contents('php://input');
            $json_data = json_decode($raw_input, true);
            if (isset($json_data['config'])) {
                $config = $json_data['config'];
            }
        }
        if (!$config) $config = get_active_sss_config($conn);

        // สำหรับการจำลองผลกระทบ (Preview / Simulation) บังคับเปิดให้ประมวลผลเดือนที่เลือกทดสอบเสมอ
        $config['month_mode'] = 'ALL';
        $config['auto_split_enabled'] = true;
        if ($visit_type === 'OPD') $config['auto_split_opd_enabled'] = true;
        if ($visit_type === 'IPD') $config['auto_split_ipd_enabled'] = true;

        $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
        $conn2_active = (isset($conn2) && $conn2) ? $conn2 : null;

        if (!$conn2_active) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล HOSxP เพื่ออ่านรายการ Instrument ได้'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $summary = get_sss_splitting_summary_for_month($conn, $conn2_active, $monthtxt, $visit_type, $config, $my_hospcode);

        // จัดกลุ่มสรุปตาม Subgroup
        $subgroup_summary = [];
        $total_cases = 0;
        $total_amount = 0.0;

        $target_subgroups = ($visit_type === 'IPD') ? ($config['subgroups_ipd'] ?? []) : ($config['subgroups_opd'] ?? []);
        foreach ($target_subgroups as $sg_id => $sg) {
            $subgroup_summary[$sg_id] = [
                'id' => $sg_id,
                'title' => $sg['title'] ?? $sg_id,
                'short_name' => $sg['short_name'] ?? $sg_id,
                'target_accountcode' => $sg['target_accountcode'] ?? '',
                'count' => 0,
                'amount' => 0.0
            ];
        }

        if (!empty($summary['sss_visits'])) {
            foreach ($summary['sss_visits'] as $vinfo) {
                $amt = cleanNum($vinfo['sss_amount']);
                $total_cases++;
                $total_amount += $amt;

                $stypes = $vinfo['sss_types'] ?? ['SSS-Instrument'];
                foreach ($stypes as $st) {
                    if (isset($subgroup_summary[$st])) {
                        $subgroup_summary[$st]['count']++;
                        $subgroup_summary[$st]['amount'] += $amt;
                    }
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'monthtxt' => $monthtxt,
            'visit_type' => $visit_type,
            'target_account' => $summary['target_account'],
            'target_original_cases' => $summary['target_original_cases'] ?? 0,
            'target_original_debit' => $summary['target_original_debit'] ?? 0.0,
            'transfer_in_cases' => $summary['transfer_in_cases'] ?? 0,
            'transfer_in_amount' => $summary['transfer_in_amount'] ?? 0.0,
            'final_target_cases' => $summary['final_target_cases'] ?? 0,
            'final_target_debit' => $summary['final_target_debit'] ?? 0.0,
            'total_cases' => $total_cases,
            'total_amount' => $total_amount,
            'transfers_by_account' => $summary['transfers_by_account'],
            'subgroups' => array_values($subgroup_summary),
            'visits_count' => count($summary['sss_visits'])
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 7. ดึงรายการกลุ่มย่อย SSS ทั้งหมด
    // -------------------------------------------------------------
    case 'get_subgroups':
        $visit_type = isset($_REQUEST['visit_type']) ? strtoupper(trim($_REQUEST['visit_type'])) : 'OPD';
        $config = get_active_sss_config($conn);
        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        $subgroups = isset($config[$group_key]) ? $config[$group_key] : [];

        echo json_encode([
            'status' => 'success',
            'visit_type' => $visit_type,
            'subgroups' => $subgroups
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 8. บันทึก / แก้ไขกลุ่มย่อย SSS
    // -------------------------------------------------------------
    case 'save_subgroup':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);

        if (!$input || empty($input['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุรหัสกลุ่มย่อย (Subgroup ID)'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $subgroup_id = trim($input['id']);
        $visit_type = isset($input['visit_type']) ? strtoupper(trim($input['visit_type'])) : 'OPD';
        if (!in_array($visit_type, ['OPD', 'IPD'])) $visit_type = 'OPD';

        $config = get_active_sss_config($conn);
        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        if (!isset($config[$group_key])) {
            $config[$group_key] = [];
        }

        $config[$group_key][$subgroup_id] = [
            'id' => $subgroup_id,
            'enabled' => isset($input['enabled']) ? (bool)$input['enabled'] : true,
            'title' => trim($input['title'] ?? $subgroup_id),
            'short_name' => trim($input['short_name'] ?? $subgroup_id),
            'target_accountcode' => trim($input['target_accountcode'] ?? (($visit_type === 'IPD') ? '1102050101.310_INST' : '1102050101.309_INST')),
            'accountname' => trim($input['accountname'] ?? '- ลูกหนี้ค่าอุปกรณ์/อวัยวะเทียม ประกันสังคม'),
            'icon' => trim($input['icon'] ?? ''),
            'adp_type' => trim($input['adp_type'] ?? '2'),
            'auto_adp_type_2' => isset($input['auto_adp_type_2']) ? (bool)$input['auto_adp_type_2'] : true,
            'custom_adp_codes' => is_array($input['custom_adp_codes'] ?? null) ? array_values(array_filter(array_map('trim', $input['custom_adp_codes']))) : ["7004", "7005", "8612", "8813", "8814"],
            'custom_icodes' => is_array($input['custom_icodes'] ?? null) ? array_values(array_filter(array_map('trim', $input['custom_icodes']))) : []
        ];

        $escaped_val = mysqli_real_escape_string($conn, json_encode($config, JSON_UNESCAPED_UNICODE));
        $user_name = isset($_SESSION['fullname']) && !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Admin';
        $escaped_user = mysqli_real_escape_string($conn, $user_name);

        $sql_update = "
            INSERT INTO imr_tb_sss_config (config_key, config_value, updated_by)
            VALUES ('sss_mapping_settings', '$escaped_val', '$escaped_user')
            ON DUPLICATE KEY UPDATE 
                config_value = VALUES(config_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ";

        if (mysqli_query($conn, $sql_update)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'บันทึกกลุ่มย่อยประกันสังคมเรียบร้อยแล้ว',
                'subgroup' => $config[$group_key][$subgroup_id]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
        }
        break;

    // -------------------------------------------------------------
    // 9. ลบกลุ่มย่อย SSS
    // -------------------------------------------------------------
    case 'delete_subgroup':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);
        $subgroup_id = isset($input['id']) ? trim($input['id']) : '';
        $visit_type = isset($input['visit_type']) ? strtoupper(trim($input['visit_type'])) : 'OPD';

        if (empty($subgroup_id)) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสกลุ่มย่อยที่ต้องการลบ'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $config = get_active_sss_config($conn);
        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';

        if (isset($config[$group_key][$subgroup_id])) {
            unset($config[$group_key][$subgroup_id]);

            $escaped_val = mysqli_real_escape_string($conn, json_encode($config, JSON_UNESCAPED_UNICODE));
            $user_name = isset($_SESSION['fullname']) && !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Admin';
            $escaped_user = mysqli_real_escape_string($conn, $user_name);

            $sql_update = "
                INSERT INTO imr_tb_sss_config (config_key, config_value, updated_by)
                VALUES ('sss_mapping_settings', '$escaped_val', '$escaped_user')
                ON DUPLICATE KEY UPDATE 
                    config_value = VALUES(config_value),
                    updated_by = VALUES(updated_by),
                    updated_at = CURRENT_TIMESTAMP
            ";

            if (mysqli_query($conn, $sql_update)) {
                echo json_encode(['status' => 'success', 'message' => 'ลบกลุ่มย่อยเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบกลุ่มย่อยดังกล่าวในระบบ'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // -------------------------------------------------------------
    // 10. ค้นหารายการ icode จาก nondrugitems และ drugitems ใน HOSxP
    // -------------------------------------------------------------
    case 'search_items':
        $term = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
        if (empty($term) || !isset($conn2) || !$conn2) {
            echo json_encode(['status' => 'success', 'items' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $escaped_term = mysqli_real_escape_string($conn2, $term);
        $items = [];

        // ค้นหาใน nondrugitems
        $sql_nd = "
            SELECT icode, name, nhso_adp_type_id, nhso_adp_code, price 
            FROM nondrugitems 
            WHERE icode LIKE '%$escaped_term%' OR name LIKE '%$escaped_term%' OR nhso_adp_code LIKE '%$escaped_term%'
            LIMIT 20
        ";
        $q_nd = mysqli_query($conn2, $sql_nd);
        if ($q_nd) {
            while ($r = mysqli_fetch_assoc($q_nd)) {
                $items[] = [
                    'icode' => $r['icode'],
                    'name' => $r['name'],
                    'type' => 'nondrug',
                    'adp_type' => $r['nhso_adp_type_id'],
                    'adp_code' => $r['nhso_adp_code'],
                    'price' => floatval($r['price'])
                ];
            }
        }

        // ค้นหาใน drugitems
        $sql_d = "
            SELECT icode, name, strength, nhso_adp_type_id, nhso_adp_code, unitprice 
            FROM drugitems 
            WHERE icode LIKE '%$escaped_term%' OR name LIKE '%$escaped_term%' OR nhso_adp_code LIKE '%$escaped_term%'
            LIMIT 20
        ";
        $q_d = mysqli_query($conn2, $sql_d);
        if ($q_d) {
            while ($r = mysqli_fetch_assoc($q_d)) {
                $items[] = [
                    'icode' => $r['icode'],
                    'name' => $r['name'] . ' ' . $r['strength'],
                    'type' => 'drug',
                    'adp_type' => $r['nhso_adp_type_id'],
                    'adp_code' => $r['nhso_adp_code'],
                    'price' => floatval($r['unitprice'])
                ];
            }
        }

        echo json_encode(['status' => 'success', 'items' => $items], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
        break;
}
