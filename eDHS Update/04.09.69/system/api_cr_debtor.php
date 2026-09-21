<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - CR Debtor API Service
 * ====================================================================================
 * API สำหรับจัดการลูกหนี้ CR: ตรวจสอบโครงสร้างฐานข้อมูล, จัดการคอนฟิกการจับคู่ (Mapping),
 * และประมวลผลจำแนก 8 กลุ่มย่อยของลูกหนี้ CR
 * 
 * @author eDHS Developer
 * @version 1.0.0 (2026-08-24)
 * ====================================================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_config/config.php';
require_once __DIR__ . '/includes/cr_migration_helper.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($conn) || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล eDHS ได้'], JSON_UNESCAPED_UNICODE);
    exit;
}

// เรียก Auto-Migration ตรวจเช็คตารางทุกครั้งที่มีการเรียก API
ensure_cr_tables($conn);

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

switch ($action) {

    // -------------------------------------------------------------
    // 1. ตรวจสอบสถานะความพร้อมของฐานข้อมูล (Schema Check)
    // -------------------------------------------------------------
    case 'check_schema':
        $success = ensure_cr_tables($conn);
        echo json_encode([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'ตารางฐานข้อมูลลูกหนี้ CR พร้อมใช้งาน' : 'เกิดข้อผิดพลาดในการตรวจสอบตาราง',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 2. ดึงการตั้งค่า Mapping Configuration ปัจจุบัน
    // -------------------------------------------------------------
    case 'get_config':
        $config = get_active_cr_config($conn);
        $my_hospcode = isset($conn2) ? get_hosxp_hospital_code($conn2) : '';
        $my_chwpart = isset($conn2) ? get_hosxp_hospital_chwpart($conn2, $my_hospcode) : '';
        $my_province_name = isset($conn2) ? get_hosxp_hospital_province_name($conn2, $my_hospcode) : '';
        $my_hospital_name = isset($conn2) ? get_hosxp_hospital_name($conn2) : '';
        echo json_encode([
            'status' => 'success',
            'config' => $config,
            'hospital_info' => [
                'hospcode' => $my_hospcode,
                'hospitalname' => $my_hospital_name,
                'chwpart' => $my_chwpart,
                'province_name' => $my_province_name
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 2.1 ดึงข้อมูลสถานพยาบาลและจังหวัดของหน่วยบริการนี้จาก HOSxP
    // -------------------------------------------------------------
    case 'get_hospital_info':
        $my_hospcode = isset($conn2) ? get_hosxp_hospital_code($conn2) : '';
        $my_chwpart = isset($conn2) ? get_hosxp_hospital_chwpart($conn2, $my_hospcode) : '';
        $my_province_name = isset($conn2) ? get_hosxp_hospital_province_name($conn2, $my_hospcode) : '';
        $my_hospital_name = isset($conn2) ? get_hosxp_hospital_name($conn2) : '';
        echo json_encode([
            'status' => 'success',
            'hospcode' => $my_hospcode,
            'hospitalname' => $my_hospital_name,
            'chwpart' => $my_chwpart,
            'province_name' => $my_province_name
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

        if (!$json_data || (!isset($json_data['subgroups_opd']) && !isset($json_data['subgroups']))) {
            echo json_encode(['status' => 'error', 'message' => 'รูปแบบข้อมูลไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $escaped_val = mysqli_real_escape_string($conn, json_encode($json_data, JSON_UNESCAPED_UNICODE));
        $user_name = isset($_SESSION['fullname']) && !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Admin';
        $escaped_user = mysqli_real_escape_string($conn, $user_name);

        $sql_update = "
            INSERT INTO imr_tb_cr_config (config_key, config_value, updated_by)
            VALUES ('cr_mapping_settings', '$escaped_val', '$escaped_user')
            ON DUPLICATE KEY UPDATE 
                config_value = VALUES(config_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ";

        if (mysqli_query($conn, $sql_update)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
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

        $default_config = get_default_cr_config();
        $escaped_val = mysqli_real_escape_string($conn, json_encode($default_config, JSON_UNESCAPED_UNICODE));
        $user_name = isset($_SESSION['fullname']) && !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Admin';
        $escaped_user = mysqli_real_escape_string($conn, $user_name);

        $sql_update = "
            INSERT INTO imr_tb_cr_config (config_key, config_value, updated_by)
            VALUES ('cr_mapping_settings', '$escaped_val', '$escaped_user')
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
    // 5.05 ดึงรายการสิทธิการรักษา pttype และ pttypename จากตารางลูกหนี้
    // -------------------------------------------------------------
    case 'get_pttypes':
        $visit_type = isset($_REQUEST['visit_type']) ? strtoupper(trim($_REQUEST['visit_type'])) : 'ALL';
        $results = ['opd' => [], 'ipd' => []];

        if ($visit_type === 'ALL' || $visit_type === 'OPD') {
            $q_opd = mysqli_query($conn, "SELECT DISTINCT pttype, pttypename FROM imr_tb_debtor_rights_opd WHERE pttype IS NOT NULL AND pttype != '' ORDER BY pttype ASC");
            if ($q_opd) {
                while ($r = mysqli_fetch_assoc($q_opd)) {
                    $results['opd'][] = [
                        'pttype' => $r['pttype'],
                        'pttypename' => $r['pttypename']
                    ];
                }
            }
        }
        if ($visit_type === 'ALL' || $visit_type === 'IPD') {
            $q_ipd = mysqli_query($conn, "SELECT DISTINCT pttype, pttypename FROM imr_tb_debtor_rights_ipd WHERE pttype IS NOT NULL AND pttype != '' ORDER BY pttype ASC");
            if ($q_ipd) {
                while ($r = mysqli_fetch_assoc($q_ipd)) {
                    $results['ipd'][] = [
                        'pttype' => $r['pttype'],
                        'pttypename' => $r['pttypename']
                    ];
                }
            }
        }
        echo json_encode(['status' => 'success', 'pttypes' => $results], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 5.1 ตรวจสอบประวัติการใช้งานกลุ่มย่อย (Check Subgroup Usage)
    // -------------------------------------------------------------
    case 'check_subgroup_usage':
        $subgroup_id = isset($_REQUEST['subgroup_id']) ? trim($_REQUEST['subgroup_id']) : '';
        $visit_type = isset($_REQUEST['visit_type']) ? strtoupper(trim($_REQUEST['visit_type'])) : '';
        if (empty($subgroup_id)) {
            echo json_encode(['status' => 'error', 'message' => 'ระบุรหัสกลุ่มย่อยไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $escaped_sg = mysqli_real_escape_string($conn, $subgroup_id);
        $type_where = (!empty($visit_type) && in_array($visit_type, ['OPD', 'IPD'])) ? "AND visit_type = '$visit_type'" : "";
        $sql_check = "SELECT COUNT(*) AS cnt FROM imr_tb_debtor_cr_breakdown 
                      WHERE (cr_type = '$escaped_sg' 
                             OR cr_type LIKE '$escaped_sg,%' 
                             OR cr_type LIKE '%, $escaped_sg,%' 
                             OR cr_type LIKE '%, $escaped_sg' 
                             OR cr_type LIKE '%,$escaped_sg%') $type_where";
        $q_check = mysqli_query($conn, $sql_check);
        $row_check = $q_check ? mysqli_fetch_assoc($q_check) : ['cnt' => 0];
        $cnt = intval($row_check['cnt'] ?? 0);

        echo json_encode([
            'status' => 'success',
            'subgroup_id' => $subgroup_id,
            'visit_type' => $visit_type,
            'is_used' => ($cnt > 0),
            'record_count' => $cnt
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 5.2 ลบกลุ่มย่อย Custom พร้อมตรวจสอบความปลอดภัย (Delete Subgroup)
    // -------------------------------------------------------------
    case 'delete_subgroup':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw_input = file_get_contents('php://input');
        $json_req = json_decode($raw_input, true);
        $subgroup_id = isset($json_req['subgroup_id']) ? trim($json_req['subgroup_id']) : '';
        $visit_type = isset($json_req['visit_type']) ? strtoupper(trim($json_req['visit_type'])) : 'OPD';

        if (empty($subgroup_id)) {
            echo json_encode(['status' => 'error', 'message' => 'ระบุรหัสกลุ่มย่อยไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $standard_keys_opd = ['CR-Instrument', 'CR-walkin', 'CR-AE', 'CR-เกิดสิทธิทันที', 'CR-palliative', 'CR-Tele', 'CR-ยาclopi', 'CR-ยาสมุนไพร'];
        $standard_keys_ipd = ['CR-Instrument', 'CR-รถรีเฟอร์', 'CR-AE', 'CR-เกิดสิทธิทันที'];
        $standard_keys = ($visit_type === 'IPD') ? $standard_keys_ipd : $standard_keys_opd;

        if (in_array($subgroup_id, $standard_keys)) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบกลุ่มย่อยมาตรฐานหลักได้ (สามารถเลือกปิดการใช้งานแทนได้)'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ตรวจสอบประวัติการใช้งาน
        $escaped_sg = mysqli_real_escape_string($conn, $subgroup_id);
        $type_where = in_array($visit_type, ['OPD', 'IPD']) ? "AND visit_type = '$visit_type'" : "";
        $sql_check = "SELECT COUNT(*) AS cnt FROM imr_tb_debtor_cr_breakdown 
                      WHERE (cr_type = '$escaped_sg' 
                             OR cr_type LIKE '$escaped_sg,%' 
                             OR cr_type LIKE '%, $escaped_sg,%' 
                             OR cr_type LIKE '%, $escaped_sg' 
                             OR cr_type LIKE '%,$escaped_sg%') $type_where";
        $q_check = mysqli_query($conn, $sql_check);
        $row_check = $q_check ? mysqli_fetch_assoc($q_check) : ['cnt' => 0];
        $cnt = intval($row_check['cnt'] ?? 0);

        if ($cnt > 0) {
            echo json_encode([
                'status' => 'error',
                'is_used' => true,
                'record_count' => $cnt,
                'message' => "ไม่สามารถลบกลุ่มย่อย '$subgroup_id' ได้ เนื่องจากมีการประมวลผลตัดยอดและบันทึกประวัติลูกหนี้ในระบบแล้วจำนวน " . number_format($cnt) . " รายการ (แนะนำให้เปลี่ยนสถานะเป็น 'ปิดใช้งาน' แทน)"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // โหลด config ปัจจุบันและลบกลุ่มย่อย
        $config = get_active_cr_config($conn);
        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        $found = false;

        if (isset($config[$group_key][$subgroup_id])) {
            unset($config[$group_key][$subgroup_id]);
            $found = true;
        } elseif (isset($config['subgroups'][$subgroup_id])) {
            unset($config['subgroups'][$subgroup_id]);
            $found = true;
        }

        if ($found) {
            $escaped_val = mysqli_real_escape_string($conn, json_encode($config, JSON_UNESCAPED_UNICODE));
            $user_name = isset($_SESSION['fullname']) && !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Admin';
            $escaped_user = mysqli_real_escape_string($conn, $user_name);

            $sql_update = "
                INSERT INTO imr_tb_cr_config (config_key, config_value, updated_by)
                VALUES ('cr_mapping_settings', '$escaped_val', '$escaped_user')
                ON DUPLICATE KEY UPDATE 
                    config_value = VALUES(config_value),
                    updated_by = VALUES(updated_by),
                    updated_at = CURRENT_TIMESTAMP
            ";

            if (mysqli_query($conn, $sql_update)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => "ลบกลุ่มย่อย '$subgroup_id' เรียบร้อยแล้ว",
                    'config' => $config
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
            }
        } else {
            // หากไม่มีใน DB Config แต่ไม่มีประวัติในตาราง breakdown สามารถลบออกจาก UI ได้อย่างปลอดภัย
            echo json_encode([
                'status' => 'success',
                'message' => "ลบกลุ่มย่อย '$subgroup_id' เรียบร้อยแล้ว",
                'config' => $config
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    // -------------------------------------------------------------
    // 6. ดึงรายชื่อกลุ่มย่อยทั้งหมดสำหรับสร้างแท็บบน UI
    // -------------------------------------------------------------
    case 'get_subgroups':
        $visit_type = isset($_REQUEST['visit_type']) ? strtoupper(trim($_REQUEST['visit_type'])) : 'OPD';
        $config = get_active_cr_config($conn);
        $group_key = ($visit_type === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        $subgroups = [];
        $src = isset($config[$group_key]) ? $config[$group_key] : (isset($config['subgroups']) ? $config['subgroups'] : []);

        foreach ($src as $key => $sg) {
            $subgroups[] = [
                'id' => $sg['id'] ?? $key,
                'short_name' => $sg['short_name'] ?? $key,
                'title' => $sg['title'] ?? $key,
                'icon' => $sg['icon'] ?? '',
                'target_accountcode' => $sg['target_accountcode'] ?? '',
                'accountname' => $sg['accountname'] ?? ''
            ];
        }
        echo json_encode([
            'status' => 'success',
            'visit_type' => $visit_type,
            'subgroups' => $subgroups
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 7. ทดสอบสแกนประเมินผลกระทบ (Test Impact Preview)
    // -------------------------------------------------------------
    case 'test_preview':
        $visit_type = isset($_REQUEST['visit_type']) ? strtoupper(trim($_REQUEST['visit_type'])) : 'OPD';
        if ($visit_type !== 'IPD') {
            $visit_type = 'OPD';
        }

        $table = ($visit_type === 'IPD') ? 'imr_tb_debtor_rights_ipd' : 'imr_tb_debtor_rights_opd';
        $id_field = ($visit_type === 'IPD') ? 'an' : 'vn';
        $date_field = ($visit_type === 'IPD') ? 'dchdate' : 'vstdate';

        $preview_month = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : '';
        if (empty($preview_month)) {
            // ดึงเดือนล่าสุด
            $q_lm = mysqli_query($conn, "SELECT monthtxt FROM $table ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC LIMIT 1");
            if ($q_lm && $rlm = mysqli_fetch_assoc($q_lm)) {
                $preview_month = $rlm['monthtxt'];
            }
        }

        $config = get_active_cr_config($conn);
        if ($visit_type === 'IPD') {
            $target_accounts = ['1102050101.202', '1102050101.217'];
        } else {
            $target_accounts = ['1102050101.201', '1102050101.209', '1102050101.203', '1102050101.216'];
        }
        $acc_in = "'" . implode("','", $target_accounts) . "'";

        $sql_preview = "
            SELECT $id_field, accountcode, debit, ptname, $date_field AS vstdate
            FROM $table
            WHERE monthtxt = '$preview_month' AND accountcode IN ($acc_in)
            LIMIT 500
        ";
        $q_prev = mysqli_query($conn, $sql_preview);
        $preview_keys = [];
        $rows_data = [];
        $total_cases = 0;
        $total_debit = 0;

        if ($q_prev) {
            while ($r = mysqli_fetch_assoc($q_prev)) {
                $k = $r[$id_field];
                $preview_keys[] = $k;
                $rows_data[$k] = $r;
                $total_cases++;
                $total_debit += cleanNum($r['debit']);
            }
        }

        $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
        $detected = [];
        if (!empty($preview_keys) && isset($conn2) && $conn2) {
            $detected = detect_cr_types_for_visit_list($conn2, $preview_keys, $visit_type, $config, $my_hospcode, $preview_month);
        }

        $summary_by_sg = [];
        $matched_cases = 0;
        $matched_debit = 0;

        foreach ($preview_keys as $k) {
            $cr_types = isset($detected[$k]['cr_types']) ? $detected[$k]['cr_types'] : [];
            if (!empty($cr_types)) {
                $matched_cases++;
                $d = cleanNum($rows_data[$k]['debit']);
                $matched_debit += $d;
                foreach ($cr_types as $ct) {
                    if (!isset($summary_by_sg[$ct])) {
                        $summary_by_sg[$ct] = ['count' => 0, 'debit' => 0];
                    }
                    $summary_by_sg[$ct]['count']++;
                    $summary_by_sg[$ct]['debit'] += $d;
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'visit_type' => $visit_type,
            'month' => $preview_month,
            'total_scanned_cases' => $total_cases,
            'total_scanned_debit' => $total_debit,
            'matched_cr_cases' => $matched_cases,
            'matched_cr_debit' => $matched_debit,
            'summary_by_subgroup' => $summary_by_sg
        ], JSON_UNESCAPED_UNICODE);
        break;

    // -------------------------------------------------------------
    // 8. ค่าเริ่มต้นหากไม่ตรงกับ Action ใดๆ
    // -------------------------------------------------------------
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Unknown action: ' . htmlspecialchars($action)
        ], JSON_UNESCAPED_UNICODE);
        break;
}
