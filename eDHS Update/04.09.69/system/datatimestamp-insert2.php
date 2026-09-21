<?php

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
    function formatMoney($val, $decimals = 2) {
        return number_format(cleanNum($val), $decimals);
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ignore_user_abort(true);
set_time_limit(0); // No time limit

require './database_config/config.php';
require_once './database_config/db_helper.php';
require_once './includes/cr_migration_helper.php';
require_once './includes/sss_migration_helper.php';

if (!function_exists('parseToThaiDateRange')) {
    function parseToThaiDateRange($startDate, $endDate) {
        if (empty($startDate) || empty($endDate)) return null;
        $parseDate = function($d) {
            $d = trim($d);
            if (strpos($d, '-') !== false) {
                $parts = explode('-', $d);
                if (count($parts) == 3) {
                    $y = intval($parts[0]);
                    $m = str_pad(intval($parts[1]), 2, '0', STR_PAD_LEFT);
                    $day = str_pad(intval($parts[2]), 2, '0', STR_PAD_LEFT);
                    if ($y < 2400) $y += 543;
                    return "$y-$m-$day";
                }
            } elseif (strpos($d, '/') !== false) {
                $parts = explode('/', $d);
                if (count($parts) == 3) {
                    $day = str_pad(intval($parts[0]), 2, '0', STR_PAD_LEFT);
                    $m = str_pad(intval($parts[1]), 2, '0', STR_PAD_LEFT);
                    $y = intval($parts[2]);
                    if ($y < 2400) $y += 543;
                    return "$y-$m-$day";
                }
            }
            return null;
        };
        $start_th = $parseDate($startDate);
        $end_th = $parseDate($endDate);
        if ($start_th && $end_th) {
            return ['start_th' => $start_th, 'end_th' => $end_th];
        }
        return null;
    }
}

if (!function_exists('getMonthsInRange')) {
    function getMonthsInRange($startDate, $endDate) {
        $months = [];
        $ts_curr = strtotime($startDate);
        $ts_end = strtotime($endDate);
        if ($ts_curr && $ts_end) {
            while ($ts_curr <= $ts_end) {
                $m_txt = intval(date('n', $ts_curr)) . '-' . date('Y', $ts_curr);
                $months[$m_txt] = true;
                $ts_curr = strtotime('+1 month', strtotime(date('Y-m-01', $ts_curr)));
            }
        }
        return !empty($months) ? array_keys($months) : [];
    }
}

if (!function_exists('thai_date_short')) {
    function thai_date_short($time){   // 19  ธ.ค. 2556a   2022-05-24
        $y = substr($time,0,4);
        $yyyy = $y+543;
        $d = substr($time,8,2);
        $m = substr($time,5,2);

        if($m == '01'){
            $mtxt = 'ม.ค.';
        }elseif ($m == '02') {
            $mtxt = 'ก.พ.';
        }elseif ($m == '03') {
            $mtxt = 'มี.ค.';
        }elseif ($m == '04') {
            $mtxt = 'เม.ย.';
        }elseif ($m == '05') {
            $mtxt = 'พ.ค.';
        }elseif ($m == '06') {
            $mtxt = 'มิ.ย.';
        }elseif ($m == '07') {
            $mtxt = 'ก.ค.';
        }elseif ($m == '08') {
            $mtxt = 'ส.ค.';
        }elseif ($m == '09') {
            $mtxt = 'ก.ย.';
        }elseif ($m == '10') {
            $mtxt = 'ต.ค.';
        }elseif ($m == '11') {
            $mtxt = 'พ.ย.';
        }elseif ($m == '12') {
            $mtxt = 'ธ.ค.';
        }else{
            $mtxt = '';
        }

        $thai_date_return = $d.' '.$mtxt.' '.$yyyy;
        return $thai_date_return;
    }
}

        if (!function_exists('parseThaiDate')) {
            function parseThaiDate($dateStr) {
                $dateStr = trim((string)$dateStr);
                if (empty($dateStr) || $dateStr === '-') return null;

                // Extract DD/MM/YYYY pattern if surrounded by other characters (e.g. mobile field or receipt info)
                if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $dateStr, $matches)) {
                    $d = (int)$matches[1];
                    $m = (int)$matches[2];
                    $y = (int)$matches[3];
                    if ($y > 2400) {
                        $y -= 543;
                    }
                    if ($d >= 1 && $d <= 31 && $m >= 1 && $m <= 12 && $y > 1900) {
                        try {
                            return new DateTime(sprintf('%04d-%02d-%02d', $y, $m, $d));
                        } catch (Exception $e) {
                            return null;
                        }
                    }
                }

                // Format YYYY-MM-DD or YYYY-MM-DD HH:MM:SS
                if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $dateStr, $matches)) {
                    $y = (int)$matches[1];
                    $m = (int)$matches[2];
                    $d = (int)$matches[3];
                    if ($y > 2400) {
                        $y -= 543;
                    }
                    if ($d >= 1 && $d <= 31 && $m >= 1 && $m <= 12 && $y > 1900) {
                        try {
                            return new DateTime(sprintf('%04d-%02d-%02d', $y, $m, $d));
                        } catch (Exception $e) {
                            return null;
                        }
                    }
                }

                return null;
            }
        }

if (!function_exists('fundLabel')) {
    function fundLabel($source) {
        if (strpos($source, 'UCS') !== false) {
            return 'UCS';
        } elseif (strpos($source, 'SSS') !== false) {
            return 'SSS';
        } elseif (strpos($source, 'OFC') !== false) {
            return 'OFC';
        } elseif (strpos($source, 'LGO') !== false) {
            return 'LGO';
        } else {
            return '';
        }
    }
}

if (!function_exists('getSourceShort')) {
    function getSourceShort($source) {
        if (strpos($source, 'UCS') !== false) return 'UCS';
        if (strpos($source, 'SSS') !== false) return 'SSS';
        if (strpos($source, 'OFC') !== false) return 'OFC';
        if (strpos($source, 'LGO') !== false) return 'LGO';
        return 'ไม่ระบุ';
    }
}

        if (isset($_POST['action']) && $_POST['action'] == 'save_new_debtor') {

            // รับค่าจากฟอร์ม
            $patient_type = $_POST['patient_type'];
            $accountcode = $_POST['accountcode'];
            $accountname = $_POST['accountname'];
            $hospcode = $_POST['hospcode'];
            $monthtxt = $_POST['monthtxt'];
            $totalall = !empty($_POST['totalall']) ? floatval($_POST['totalall']) : 0.00;

            // ---------------------------------------------------------
            // 💡 ระบบแปลงวันที่ (เช่น "1-2018" -> "1/01/2561")
            // ---------------------------------------------------------
            $formatted_date = "";
            if (!empty($monthtxt)) {
                $date_parts = explode('-', $monthtxt); // แยกเดือนกับปีด้วยขีด (-)
                if (count($date_parts) == 2) {
                    $m = str_pad($date_parts[0], 2, "0", STR_PAD_LEFT); // เติม 0 ให้เดือนถ้าเป็นเลขตัวเดียว เช่น 1 -> 01
                    $y = (int)$date_parts[1] + 543; // บวก 543 ให้กลายเป็นปี พ.ศ.
                    $formatted_date = "01/" . $m . "/" . $y; // ประกอบร่างให้กลายเป็น 1/MM/YYYY
                }
            }

            // 💡 สุ่มเลข 12 หลักสำหรับ vn (ปีเดือนวันชั่วโมงนาทีวินาที) ป้องกันการซ้ำ
            $vn = date('ymdHis');

            // 💡 สุ่มเลข 3 หลัก สำหรับฟิลด์ no
            $no = rand(100, 999); // จะได้เลข 100 - 999

            $hn = $_POST['hn'];
            $cid = $_POST['cid'];
            $mobile = $_POST['mobile'];

            if ($patient_type === 'OPD') {
                $table = 'imr_tb_debtor_rights_opd';
                $date_col = 'vstdate'; // คอลัมน์สำหรับ OPD
            } else {
                $table = 'imr_tb_debtor_rights_ipd';
                $date_col = 'dchdate'; // คอลัมน์สำหรับ IPD
            }

            $income = $totalall;
            $uc_money = $totalall;
            $debit = $totalall;

            $sql = "INSERT INTO $table (no, vn, hn, cid, accountcode, accountname, income, uc_money, debit, totalall, mobile, hospcode, monthtxt, $date_col)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param("ssssssdddsssss", $no, $vn, $hn, $cid, $accountcode, $accountname, $income, $uc_money, $debit, $totalall, $mobile, $hospcode, $monthtxt, $formatted_date);

                if ($stmt->execute()) {
                    if (function_exists('system_log')) {
                        system_log($conn, 'ตั้งลูกหนี้/รับชำระ', 'CREATE', "เพิ่มลูกหนี้ใหม่ (ประเภท: $patient_type, รหัสผังบัญชี: $accountcode)");
                    }
                    echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลผังบัญชีใหม่เรียบร้อยแล้ว']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ: ' . $stmt->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เตรียมคำสั่ง SQL ไม่สำเร็จ: ' . $conn->error]);
            }

            exit();
        }





        if (isset($_POST['action_delete_partial'])) {

            // รับค่าตัวแปร
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            $vn = mysqli_real_escape_string($conn, $_POST['vn']);
            $type = mysqli_real_escape_string($conn, $_POST['type']);

            $main_table = ($type == 'OPD') ? 'imr_tb_debtor_rights_opd' : 'imr_tb_debtor_rights_ipd';
            $pk_col = ($type == 'OPD') ? 'vn' : 'an';

            // 1. สั่งลบข้อมูล
            $sql_del = "DELETE FROM imr_tb_payment_history WHERE id = '$id'";
            if (!mysqli_query($conn, $sql_del)) {
                echo "Error deleting: " . mysqli_error($conn);
                exit;
            }

            // 2. คำนวณยอดรวมใหม่
            $sql_sum = "SELECT SUM(pay_amount) as total FROM imr_tb_payment_history WHERE ref_vn_an = '$vn'";
            $res_sum = mysqli_query($conn, $sql_sum);
            $row_sum = mysqli_fetch_assoc($res_sum);
            $total_paid = $row_sum['total'] ? floatval(cleanNum($row_sum['total'])) : 0.0;

            // ตรวจสอบยอดหนี้ตั้งต้น
            $chk_debt = mysqli_query($conn, "SELECT debit FROM $main_table WHERE $pk_col = '$vn'");
            $row_debt = mysqli_fetch_assoc($chk_debt);
            $debit_val = isset($row_debt['debit']) ? floatval(cleanNum($row_debt['debit'])) : 0.0;

            // ค้นหาใบเสร็จงวดสุดท้ายที่เหลืออยู่ (Last Receipt Rule)
            $last_receipt_q = mysqli_query($conn, "SELECT bill_no, bill_date FROM imr_tb_payment_history WHERE ref_vn_an = '$vn' AND bill_no != '' ORDER BY id DESC LIMIT 1");
            $last_bill_no = '';
            $last_bill_date = '';
            if ($last_receipt_q && $lr = mysqli_fetch_assoc($last_receipt_q)) {
                $last_bill_no = mysqli_real_escape_string($conn, $lr['bill_no']);
                if (!empty($lr['bill_date'])) {
                    $d_lr = date('d', strtotime($lr['bill_date']));
                    $m_lr = date('m', strtotime($lr['bill_date']));
                    $y_lr = date('Y', strtotime($lr['bill_date'])) + 543;
                    $last_bill_date = "$d_lr/$m_lr/$y_lr";
                }
            }

            // 3. อัปเดตตารางหลัก: ถ้าจ่ายครบให้ใช้บิลงวดสุดท้าย ถ้าไม่ครบเคลียร์ bill/billdate เพื่อคืนสถานะค้างชำระ
            if (($debit_val - $total_paid) <= 0.01 && $total_paid > 0) {
                $sql_update = "UPDATE $main_table SET follow_money = '$total_paid', bill = '$last_bill_no', billdate = '$last_bill_date' WHERE $pk_col = '$vn'";
            } else {
                $sql_update = "UPDATE $main_table SET follow_money = '$total_paid', bill = '', billdate = '' WHERE $pk_col = '$vn'";
            }
            mysqli_query($conn, $sql_update);

            // ส่งค่ากลับไปบอก JS ว่าลบเสร็จแล้ว
            if (function_exists('system_log')) {
                system_log($conn, 'ตั้งลูกหนี้/รับชำระ', 'DELETE', "ลบประวัติรับชำระเงินงวดบางส่วน (ID: $id, VN/AN: $vn)");
            }
            echo "deleted";
            exit; // จบการทำงานทันที (สำคัญมาก)
        }






        if (isset($_POST['action_save_partial'])) {

            $vn = mysqli_real_escape_string($conn, $_POST['vn']);
            $amount = str_replace(',', '', $_POST['amount']); // เอาลูกน้ำออก
            $bill_no = mysqli_real_escape_string($conn, $_POST['bill_no']);
            $bill_date_thai = mysqli_real_escape_string($conn, $_POST['bill_date']); // รับค่าวันที่แบบไทย (dd/mm/yyyy)
            $type = mysqli_real_escape_string($conn, $_POST['type']); // OPD หรือ IPD
            $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';
            $target_accountcode = isset($_POST['accountcode']) ? mysqli_real_escape_string($conn, $_POST['accountcode']) : '';

            // รับค่า ID สำหรับกรณีแก้ไข (ถ้ามี)
            $history_id = isset($_POST['history_id']) ? mysqli_real_escape_string($conn, $_POST['history_id']) : '';

            // แปลงวันที่ไทยเป็น Y-m-d เพื่อเก็บลงฐานข้อมูล
            $bill_date_sql = NULL;
            if ($bill_date_thai != '') {
                $parts = explode('/', $bill_date_thai);
                if (count($parts) == 3) {
                    $d = $parts[0];
                    $m = $parts[1];
                    $y = (int)$parts[2] - 543; // แปลง พ.ศ. เป็น ค.ศ.
                    $bill_date_sql = "$y-$m-$d";
                }
            }

            // กำหนดตารางหลักที่จะไปอัปเดตยอดรวม
            $main_table = ($type == 'OPD') ? 'imr_tb_debtor_rights_opd' : 'imr_tb_debtor_rights_ipd';
            $pk_col = ($type == 'OPD') ? 'vn' : 'an';

            // ตัวแปรเก็บ SQL ที่จะรัน (Insert หรือ Update)
            $sql_action = "";

            // =========================================================
            // ตรวจสอบเงื่อนไข: เป็นการ "แก้ไข" หรือ "เพิ่มใหม่"
            // =========================================================
            if (!empty($history_id)) {
                // --- กรณีแก้ไข (Update) ---
                $sql_action = "UPDATE imr_tb_payment_history SET
                               pay_amount = '$amount',
                               bill_no = '$bill_no',
                               bill_date = '$bill_date_sql',
                               note = '$note'
                               WHERE id = '$history_id'";
            } else {
                // --- กรณีเพิ่มใหม่ (Insert) ---

                // 1. เช็คก่อนว่าเคยมีประวัติการแบ่งจ่ายในตารางใหม่หรือยัง?
                $chk_history = mysqli_query($conn, "SELECT id FROM imr_tb_payment_history WHERE ref_vn_an = '$vn' LIMIT 1");

                // ถ้ายังไม่เคยมีประวัติเลย แต่ในตารางหลักมียอดเงินอยู่ (ยอดเก่า) ให้ย้ายยอดเก่ามาเป็นงวดแรกก่อน
                if (mysqli_num_rows($chk_history) == 0) {
                    $chk_main = mysqli_query($conn, "SELECT follow_money, bill FROM $main_table WHERE $pk_col = '$vn'");
                    $row_main = mysqli_fetch_assoc($chk_main);
                    $legacy_money = isset($row_main['follow_money']) ? $row_main['follow_money'] : 0;

                    if ($legacy_money > 0) {
                         $old_bill = !empty($row_main['bill']) ? $row_main['bill'] : 'ยอดยกมา';
                         $sql_migrate = "INSERT INTO imr_tb_payment_history
                                         (ref_vn_an, patient_type, accountcode, pay_amount, bill_no, note)
                                         VALUES ('$vn', '$type', '$target_accountcode', '$legacy_money', '$old_bill', 'ยอดเดิมก่อนเริ่มแบ่งจ่าย')";
                         mysqli_query($conn, $sql_migrate);
                    }
                }

                // 2. คำสั่ง Insert รายการใหม่
                $sql_action = "INSERT INTO imr_tb_payment_history
                               (ref_vn_an, patient_type, accountcode, pay_amount, bill_no, bill_date, note)
                               VALUES ('$vn', '$type', '$target_accountcode', '$amount', '$bill_no', '$bill_date_sql', '$note')";
            }

            // =========================================================
            // เริ่มทำงาน SQL (Insert/Update) และคำนวณยอดรวมใหม่
            // =========================================================
            if (mysqli_query($conn, $sql_action)) {

                // 3. คำนวณยอดรวมทั้งหมดจากประวัติ เพื่อเอาไปอัปเดตตารางหลัก
                $sql_sum = "SELECT SUM(pay_amount) as total FROM imr_tb_payment_history WHERE ref_vn_an = '$vn'";
                $res_sum = mysqli_query($conn, $sql_sum);
                $row_sum = mysqli_fetch_assoc($res_sum);
                $total_paid = $row_sum['total'] ? floatval(cleanNum($row_sum['total'])) : 0.0;

                // ตรวจสอบยอดหนี้ตั้งต้น
                $chk_debt = mysqli_query($conn, "SELECT debit FROM $main_table WHERE $pk_col = '$vn'");
                $row_debt = mysqli_fetch_assoc($chk_debt);
                $debit_val = isset($row_debt['debit']) ? floatval(cleanNum($row_debt['debit'])) : 0.0;

                // ค้นหาใบเสร็จงวดสุดท้าย (Last Receipt Rule)
                $last_receipt_q = mysqli_query($conn, "SELECT bill_no, bill_date FROM imr_tb_payment_history WHERE ref_vn_an = '$vn' AND bill_no != '' ORDER BY id DESC LIMIT 1");
                $last_bill_no = '';
                $last_bill_date = '';
                if ($last_receipt_q && $lr = mysqli_fetch_assoc($last_receipt_q)) {
                    $last_bill_no = mysqli_real_escape_string($conn, $lr['bill_no']);
                    if (!empty($lr['bill_date'])) {
                        $d_lr = date('d', strtotime($lr['bill_date']));
                        $m_lr = date('m', strtotime($lr['bill_date']));
                        $y_lr = date('Y', strtotime($lr['bill_date'])) + 543;
                        $last_bill_date = "$d_lr/$m_lr/$y_lr";
                    }
                }

                // หากเป็นการบันทึกรับชำระของผังลูก CR หรือ SSS ให้อัปเดตสถานะในตาราง breakdown ด้วย
                $is_cr_child = (strpos($target_accountcode, '1102050101.216') !== false || strpos($target_accountcode, '1102050101.217') !== false);
                $is_sss_child = (strpos($target_accountcode, '1102050101.309') !== false || strpos($target_accountcode, '1102050101.310') !== false);

                if ($is_cr_child) {
                    $sql_sum_cr = "SELECT SUM(pay_amount) as total FROM imr_tb_payment_history WHERE ref_vn_an = '$vn' AND accountcode LIKE '%1102050101.21%'";
                    $res_sum_cr = mysqli_query($conn, $sql_sum_cr);
                    $row_sum_cr = mysqli_fetch_assoc($res_sum_cr);
                    $cr_paid = $row_sum_cr['total'] ? floatval(cleanNum($row_sum_cr['total'])) : $total_paid;

                    mysqli_query($conn, "UPDATE imr_tb_debtor_cr_breakdown 
                        SET compensated = '$cr_paid', bill = '$last_bill_no', billdate = '$last_bill_date',
                            settle_status = (CASE WHEN '$cr_paid' >= item_amount THEN 'SETTLED' ELSE 'PARTIAL' END)
                        WHERE visit_type = '$type' AND vn = '$vn'");
                } elseif ($is_sss_child) {
                    $sql_sum_sss = "SELECT SUM(pay_amount) as total FROM imr_tb_payment_history WHERE ref_vn_an = '$vn' AND accountcode LIKE '%1102050101.3%'";
                    $res_sum_sss = mysqli_query($conn, $sql_sum_sss);
                    $row_sum_sss = mysqli_fetch_assoc($res_sum_sss);
                    $sss_paid = $row_sum_sss['total'] ? floatval(cleanNum($row_sum_sss['total'])) : $total_paid;

                    mysqli_query($conn, "UPDATE imr_tb_debtor_sss_breakdown 
                        SET compensated = '$sss_paid', bill = '$last_bill_no', billdate = '$last_bill_date',
                            settle_status = (CASE WHEN '$sss_paid' >= item_amount THEN 'SETTLED' ELSE 'PARTIAL' END)
                        WHERE visit_type = '$type' AND vn = '$vn'");
                }

                // 4. อัปเดตตารางหลัก: ถ้าจ่ายครบใช้บิลงวดสุดท้าย ถ้ายังไม่ครบปล่อยว่าง bill/billdate
                if (($debit_val - $total_paid) <= 0.01 && $total_paid > 0) {
                    $sql_update = "UPDATE $main_table SET
                                   follow_money = '$total_paid',
                                   bill = '$last_bill_no',
                                   billdate = '$last_bill_date'
                                   WHERE $pk_col = '$vn'";
                } else {
                    $sql_update = "UPDATE $main_table SET
                                   follow_money = '$total_paid',
                                   bill = '',
                                   billdate = ''
                                   WHERE $pk_col = '$vn'";
                }

                if(mysqli_query($conn, $sql_update)){
                     if (function_exists('system_log')) {
                         $action_type = (!empty($history_id)) ? 'UPDATE' : 'CREATE';
                         $action_text = (!empty($history_id)) ? "แก้ไข" : "เพิ่ม";
                         system_log($conn, 'ตั้งลูกหนี้/รับชำระ', $action_type, "$action_text ประวัติรับชำระเงินบางส่วน (VN/AN: $vn, จำนวน: $amount)");
                     }
                     echo "success";
                } else {
                     echo "error_update_main: " . mysqli_error($conn);
                }

            } else {
                echo "error_action: " . mysqli_error($conn);
            }
            exit;
        }


if (isset($_POST['action'])) {

    $mn = mysqli_real_escape_string($conn, $_POST['action']); // ป้องกัน SQL Injection
    $start_date_opd = isset($_POST['start_date_opd']) ? mysqli_real_escape_string($conn, $_POST['start_date_opd']) : '';
    $end_date_opd = isset($_POST['end_date_opd']) ? mysqli_real_escape_string($conn, $_POST['end_date_opd']) : '';

    $has_date_range = (!empty($start_date_opd) && !empty($end_date_opd));
    $date_condition_o = "";
    $date_condition = "";
    $months_to_process = [$mn];

    if ($has_date_range) {
        $range_info = parseToThaiDateRange($start_date_opd, $end_date_opd);
        if ($range_info) {
            $start_date_th = $range_info['start_th'];
            $end_date_th = $range_info['end_th'];

            $date_condition_o = " STR_TO_DATE(o.vstdate, '%d/%m/%Y') BETWEEN '$start_date_th' AND '$end_date_th' ";
            $date_condition = " STR_TO_DATE(vstdate, '%d/%m/%Y') BETWEEN '$start_date_th' AND '$end_date_th' ";
            $months_to_process = getMonthsInRange($start_date_opd, $end_date_opd);
        } else {
            $has_date_range = false;
        }
    }

    if (!$has_date_range) {
        $date_condition_o = " o.monthtxt = '$mn' ";
        $date_condition = " monthtxt = '$mn' ";
    }

    $target_vns_str = "''";
    $target_vns_11504_str = "''";
    $target_vns_14429_str = "''";
    $target_vns_23576_str = "''";
    $vn_q = mysqli_query($conn, "SELECT vn FROM imr_tb_debtor_rights_opd WHERE accountcode = '1102050101.203' AND $date_condition");
    if($vn_q && mysqli_num_rows($vn_q) > 0) {
        $vn_list = [];
        while ($v = mysqli_fetch_assoc($vn_q)) {
    if (isset($v['cid'])) $v['cid'] = decrypt_data($v['cid']);
    if (isset($v['tel'])) $v['tel'] = decrypt_data($v['tel']);
            $vn_list[] = "'" . mysqli_real_escape_string($conn2, $v['vn']) . "'";
        }
        $in_vns = implode(",", $vn_list);
        $hosp_q = mysqli_query($conn2, "SELECT vn, hospmain FROM ovst WHERE vn IN ($in_vns) AND hospmain IN ('11504', '14429', '23576')");
        if($hosp_q) {
            $target_vns = [];
            $vns_11504 = [];
            $vns_14429 = [];
            $vns_23576 = [];
            while ($h = mysqli_fetch_assoc($hosp_q)) {
    if (isset($h['cid'])) $h['cid'] = decrypt_data($h['cid']);
    if (isset($h['tel'])) $h['tel'] = decrypt_data($h['tel']);
                $target_vns[] = "'" . $h['vn'] . "'";
                if ($h['hospmain'] == '11504') $vns_11504[] = "'" . $h['vn'] . "'";
                elseif ($h['hospmain'] == '14429') $vns_14429[] = "'" . $h['vn'] . "'";
                elseif ($h['hospmain'] == '23576') $vns_23576[] = "'" . $h['vn'] . "'";
            }
            if(count($target_vns) > 0) $target_vns_str = implode(",", $target_vns);
            if(count($vns_11504) > 0) $target_vns_11504_str = implode(",", $vns_11504);
            if(count($vns_14429) > 0) $target_vns_14429_str = implode(",", $vns_14429);
            if(count($vns_23576) > 0) $target_vns_23576_str = implode(",", $vns_23576);
        }
    }

    // 💡 ปรับปรุง SQL: ปลดล็อกเงื่อนไขผัง .216 ออก เพื่อให้ดักจับและแตกแถวไตย่อยได้ใน 'ทุกผังบัญชี'
    $sql = "
            SELECT
                main.accountcode_key,
                main.accountcode,
                main.accountname,
                main.cvn,
                main.rcpt_money,
                main.debit,
                main.compensated,
                main.seamless_dckd,
                main.seamless_dckd_ofc,
                main.sort_type
            FROM (
                SELECT
                    -- 1. ทุกผังบัญชีถ้าเป็นเคสไต ให้พ่วง _KIDNEY ต่อท้ายรหัสเพื่อแยกแถวรายการย่อย
                    CASE
                        WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN CONCAT(o.accountcode, '_KIDNEY')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN CONCAT(o.accountcode, '_HOSP_11504')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN CONCAT(o.accountcode, '_HOSP_14429')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN CONCAT(o.accountcode, '_HOSP_23576')
                        ELSE o.accountcode
                    END AS accountcode_key,
                    o.accountcode,
                    -- 2. เปลี่ยนชื่อสิทธิย่อยอัตโนมัติตามสิทธิหลัก: เปลี่ยน 'ลูกหนี้ค่ารักษา' เป็น '- ผู้ป่วยไตวายเรื้อรัง'
                    CASE
                        WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN
                            REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN
                            REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ รพ.ค่าย เบิก นค.')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN
                            REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 1 วัดเหนือ เบิก นค.')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN
                            REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 2 สระสิม เบิก นค.')
                        ELSE o.accountname
                    END AS accountname,
                    COUNT(o.vn) AS cvn,
                    SUM(IFNULL(o.rcpt_money, 0)) AS rcpt_money,
                    SUM(IFNULL(o.debit, 0)) AS debit,
                    SUM(
                        IFNULL((SELECT SUM(compensated) FROM imr_tb_check_invoice WHERE vn = o.vn), 0)
                        + IFNULL(o.follow_money, 0)
                    ) AS compensated,
                    SUM(
                        IFNULL((SELECT SUM(compensated) FROM imr_tb_seamless_dckd WHERE vn = o.vn), 0)
                    ) AS seamless_dckd,
                    SUM(CASE WHEN o.accountcode = '1102050101.401' THEN
                        IFNULL((SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc WHERE vn = o.vn), 0)
                    ELSE 0 END) AS seamless_dckd_ofc,
                    -- 3. กำหนดค่าน้ำหนักเพื่อล็อกตำแหน่งการเรียงลำดับ (สิทธิหลัก=1 ขึ้นก่อน, สิทธิย่อยโรคไต=2 ตามท้ายใต้กลุ่ม)
                    CASE
                        WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN 2
                        WHEN (o.accountcode = '1102050101.203' AND (o.vn IN ($target_vns_11504_str) OR o.vn IN ($target_vns_14429_str) OR o.vn IN ($target_vns_23576_str))) THEN 2
                        ELSE 1
                    END AS sort_type
                FROM imr_tb_debtor_rights_opd o
                WHERE $date_condition_o
                GROUP BY
                    CASE
                        WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN CONCAT(o.accountcode, '_KIDNEY')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN CONCAT(o.accountcode, '_HOSP_11504')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN CONCAT(o.accountcode, '_HOSP_14429')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN CONCAT(o.accountcode, '_HOSP_23576')
                        ELSE o.accountcode
                    END,
                    o.accountcode,
                    CASE
                        WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN
                            REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN
                            REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ รพ.ค่าย เบิก นค.')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN
                            REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 1 วัดเหนือ เบิก นค.')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN
                            REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 2 สระสิม เบิก นค.')
                        ELSE o.accountname
                    END
            ) AS main
            JOIN (
                -- หาผลรวมยอดจำนวนสถิติรายผังบัญชี เพื่อจัดกลุ่มให้ผังหลักและผังย่อยโรคไตเกาะติดเรียงไปด้วยกัน
                SELECT accountcode, COUNT(vn) AS total_cvn
                FROM imr_tb_debtor_rights_opd
                WHERE $date_condition
                GROUP BY accountcode
            ) AS ordering ON main.accountcode = ordering.accountcode
            ORDER BY
                ordering.total_cvn DESC,   -- เรียงกลุ่มผังบัญชีที่มียอดคนไข้มากไปน้อย
                main.accountcode ASC,      -- เรียงตามรหัสผังบัญชีภายในกลุ่ม
                main.sort_type ASC         -- ล็อกแถวปกติขึ้นก่อน แล้วตามด้วยแถวโรคไตของผังนั้นๆ เสมอ
        ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        echo "<div style='color:red; font-weight:bold;'>เกิดข้อผิดพลาดในการดึงข้อมูล: " . mysqli_error($conn) . "</div>";
        exit;
    }

    echo '<table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>รหัสผังบัญชี</th>
                    <th>ชื่อผังบัญชี</th>
                    <th>จำนวน</th>
                    <th>ภาระหนี้</th>
                    <th>ชดเชย STM</th>
                    <th>ชดเชย DCKD</th>
                    <th>ส่วนต่าง</th>
                </tr>
            </thead>
            <tbody>';

    $cr_config = get_active_cr_config($conn);
    $auto_split = !empty($cr_config['auto_split_enabled']);
    $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';

    $cr_summary = [
        'is_effective' => false,
        'auto_split' => $auto_split,
        'transfers_by_account' => [],
        'target_account' => '1102050101.216',
        'cr_received_total' => 0.0,
        'cr_non_kidney_count' => 0,
        'cr_kidney_count' => 0,
        'cr_non_kidney_received_total' => 0.0,
        'cr_kidney_received_total' => 0.0,
        'cr_visits' => []
    ];
    $has_any_cr = false;

    $sss_config = get_active_sss_config($conn);
    $auto_split_sss = is_sss_auto_split_enabled($sss_config, 'OPD');
    $sss_summary = [
        'is_effective' => false,
        'auto_split' => $auto_split_sss,
        'transfers_by_account' => [],
        'target_account' => '1102050101.309',
        'sss_received_total' => 0.0,
        'sss_count' => 0,
        'sss_visits' => []
    ];
    $has_any_sss = false;

    $inactive_cr_months = [];
    $inactive_sss_months = [];

    foreach ($months_to_process as $m_txt) {
        if (is_cr_effective_for_month($cr_config, $m_txt) && $auto_split) {
            $vn_filter_arg = null;
            if ($has_date_range) {
                $vns_m_q = mysqli_query($conn, "SELECT vn FROM imr_tb_debtor_rights_opd WHERE monthtxt = '$m_txt' AND $date_condition AND accountcode IN ('1102050101.201','1102050101.209','1102050101.203','1102050101.216')");
                $vn_filter_arg = [];
                if ($vns_m_q) {
                    while ($vr = mysqli_fetch_assoc($vns_m_q)) {
                        $vn_filter_arg[] = $vr['vn'];
                    }
                }
            }
            if (!$has_date_range || !empty($vn_filter_arg)) {
                $s_cr = get_cr_splitting_summary_for_month($conn, $conn2, $m_txt, 'OPD', $cr_config, $my_hospcode, $vn_filter_arg);
                if ($s_cr) {
                    $has_any_cr = true;
                    $cr_summary['is_effective'] = true;
                    $cr_summary['cr_received_total'] += $s_cr['cr_received_total'];
                    $cr_summary['cr_non_kidney_received_total'] += $s_cr['cr_non_kidney_received_total'];
                    $cr_summary['cr_non_kidney_count'] += $s_cr['cr_non_kidney_count'];
                    $cr_summary['cr_kidney_count'] += $s_cr['cr_kidney_count'];
                    $cr_summary['cr_kidney_received_total'] += $s_cr['cr_kidney_received_total'];
                    if (!isset($cr_summary['cr_non_kidney_received_comp'])) $cr_summary['cr_non_kidney_received_comp'] = 0.0;
                    $cr_summary['cr_non_kidney_received_comp'] += cleanNum($s_cr['cr_non_kidney_received_comp'] ?? 0);
                    foreach ($s_cr['cr_visits'] as $v_k => $v_val) {
                        $cr_summary['cr_visits'][$v_k] = $v_val;
                    }
                    foreach ($s_cr['transfers_by_account'] as $acc => $tr) {
                        if (!isset($cr_summary['transfers_by_account'][$acc])) {
                            $cr_summary['transfers_by_account'][$acc] = [
                                'original_debit' => 0.0,
                                'transfer_out' => 0.0,
                                'comp_out' => 0.0,
                                'remain_debit' => 0.0,
                                'cases' => 0,
                                'cr_cases' => 0,
                                'full_transfer_cases' => 0,
                                'partial_transfer_cases' => 0
                            ];
                        }
                        $cr_summary['transfers_by_account'][$acc]['original_debit'] += $tr['original_debit'];
                        $cr_summary['transfers_by_account'][$acc]['transfer_out'] += $tr['transfer_out'];
                        $cr_summary['transfers_by_account'][$acc]['comp_out'] += cleanNum($tr['comp_out'] ?? 0);
                        $cr_summary['transfers_by_account'][$acc]['remain_debit'] += $tr['remain_debit'];
                        $cr_summary['transfers_by_account'][$acc]['cases'] += $tr['cases'];
                        $cr_summary['transfers_by_account'][$acc]['cr_cases'] += $tr['cr_cases'];
                        $cr_summary['transfers_by_account'][$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
                        $cr_summary['transfers_by_account'][$acc]['partial_transfer_cases'] += $tr['partial_transfer_cases'];
                    }
                    if (!$has_date_range) {
                        sync_cr_breakdown_records($conn, $s_cr, $m_txt, 'OPD');
                    }
                }
            }
        } else {
            $inactive_cr_months[] = $m_txt;
        }

        if (is_sss_effective_for_month($sss_config, $m_txt) && $auto_split_sss) {
            $vn_filter_sss = null;
            if ($has_date_range) {
                $vns_sss_q = mysqli_query($conn, "SELECT vn FROM imr_tb_debtor_rights_opd WHERE monthtxt = '$m_txt' AND $date_condition AND accountcode IN ('1102050101.301','1102050101.303','1102050101.307','1102050101.308','1102050101.309')");
                $vn_filter_sss = [];
                if ($vns_sss_q) {
                    while ($vr = mysqli_fetch_assoc($vns_sss_q)) {
                        $vn_filter_sss[] = $vr['vn'];
                    }
                }
            }
            if (!$has_date_range || !empty($vn_filter_sss)) {
                $s_sss = get_sss_splitting_summary_for_month($conn, $conn2, $m_txt, 'OPD', $sss_config, $my_hospcode, $vn_filter_sss);
                if ($s_sss) {
                    $has_any_sss = true;
                    $sss_summary['is_effective'] = true;
                    $sss_summary['sss_received_total'] += $s_sss['sss_received_total'];
                    $sss_summary['sss_count'] += $s_sss['sss_count'];
                    if (!isset($sss_summary['sss_received_comp'])) $sss_summary['sss_received_comp'] = 0.0;
                    $sss_summary['sss_received_comp'] += cleanNum($s_sss['sss_received_comp'] ?? 0);
                    foreach ($s_sss['sss_visits'] as $v_k => $v_val) {
                        $sss_summary['sss_visits'][$v_k] = $v_val;
                    }
                    foreach ($s_sss['transfers_by_account'] as $acc => $tr) {
                        if (!isset($sss_summary['transfers_by_account'][$acc])) {
                            $sss_summary['transfers_by_account'][$acc] = [
                                'original_debit' => 0.0,
                                'transfer_out' => 0.0,
                                'comp_out' => 0.0,
                                'remain_debit' => 0.0,
                                'cases' => 0,
                                'sss_cases' => 0,
                                'full_transfer_cases' => 0,
                                'partial_transfer_cases' => 0
                            ];
                        }
                        $sss_summary['transfers_by_account'][$acc]['original_debit'] += $tr['original_debit'];
                        $sss_summary['transfers_by_account'][$acc]['transfer_out'] += $tr['transfer_out'];
                        $sss_summary['transfers_by_account'][$acc]['comp_out'] += cleanNum($tr['comp_out'] ?? 0);
                        $sss_summary['transfers_by_account'][$acc]['remain_debit'] += $tr['remain_debit'];
                        $sss_summary['transfers_by_account'][$acc]['cases'] += $tr['cases'];
                        $sss_summary['transfers_by_account'][$acc]['sss_cases'] += $tr['sss_cases'];
                        $sss_summary['transfers_by_account'][$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
                        $sss_summary['transfers_by_account'][$acc]['partial_transfer_cases'] += $tr['partial_transfer_cases'];
                    }
                    if (!$has_date_range) {
                        sync_sss_breakdown_records($conn, $s_sss, $m_txt, 'OPD');
                    }
                }
            }
        } else {
            $inactive_sss_months[] = $m_txt;
        }
    }

    // รวมยอดลูกหนี้เดิมของผัง .216 จากเดือนที่ยังไม่ได้เปิดระบบ CR ในช่วงเวลาที่เลือก
    if (!empty($inactive_cr_months) && $has_any_cr) {
        $in_m_str = "'" . implode("','", array_map(function($m) use ($conn) { return mysqli_real_escape_string($conn, $m); }, $inactive_cr_months)) . "'";
        $q_inact = mysqli_query($conn, "SELECT COUNT(o.vn) as c, COALESCE(SUM(o.debit), 0) as d,
                                         COALESCE(SUM(IFNULL(o.follow_money, 0) + IFNULL(stm.compensated, 0) + IFNULL(ckd_ofc.amount, 0) + IFNULL(ckd.compensated, 0)), 0) as comp
                                         FROM imr_tb_debtor_rights_opd o
                                         LEFT JOIN imr_tb_check_invoice stm ON stm.vn = o.vn
                                         LEFT JOIN imr_tb_seamless_dckd ckd ON ckd.vn = o.vn
                                         LEFT JOIN imr_tb_seamless_dckd_ofc ckd_ofc ON ckd_ofc.vn = o.vn
                                         WHERE o.accountcode = '1102050101.216' 
                                         AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') 
                                         AND o.monthtxt IN ($in_m_str) AND $date_condition");
        if ($q_inact && $r_inact = mysqli_fetch_assoc($q_inact)) {
            $cr_summary['cr_non_kidney_count'] += intval($r_inact['c']);
            $cr_summary['cr_non_kidney_received_total'] += floatval($r_inact['d']);
            $cr_summary['cr_received_total'] += floatval($r_inact['d']);
            if (!isset($cr_summary['cr_non_kidney_received_comp'])) $cr_summary['cr_non_kidney_received_comp'] = 0.0;
            $cr_summary['cr_non_kidney_received_comp'] += floatval($r_inact['comp']);
            if (!isset($cr_summary['cr_received_comp'])) $cr_summary['cr_received_comp'] = 0.0;
            $cr_summary['cr_received_comp'] += floatval($r_inact['comp']);
        }
    }

    // รวมยอดลูกหนี้เดิมของผัง .309 จากเดือนที่ยังไม่ได้เปิดระบบ SSS ในช่วงเวลาที่เลือก
    if (!empty($inactive_sss_months) && $has_any_sss) {
        $in_m_str_s = "'" . implode("','", array_map(function($m) use ($conn) { return mysqli_real_escape_string($conn, $m); }, $inactive_sss_months)) . "'";
        $q_inact_s = mysqli_query($conn, "SELECT COUNT(vn) as c, COALESCE(SUM(debit), 0) as d 
                                           FROM imr_tb_debtor_rights_opd 
                                           WHERE accountcode = '1102050101.309' 
                                           AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') 
                                           AND monthtxt IN ($in_m_str_s) AND $date_condition");
        if ($q_inact_s && $r_inact_s = mysqli_fetch_assoc($q_inact_s)) {
            $sss_summary['sss_count'] += intval($r_inact_s['c']);
            $sss_summary['sss_received_total'] += floatval($r_inact_s['d']);
        }
    }

    if (!$has_any_cr) $cr_summary = null;
    if (!$has_any_sss) $sss_summary = null;

    if ($result && mysqli_num_rows($result) > 0) {
        $svn = $sdebit = $compensated = $seamless_dckd = $diffstms = 0;
        $rendered_216 = false;
        $rendered_309 = false;

        while ($row = mysqli_fetch_assoc($result)) {
            if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
            if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
            $seamless_dckd_final = $row["seamless_dckd"];
            $accountcode = $row["accountcode"];

            // รวมยอดสิทธิข้าราชการ/หน่วยงานต้นสังกัด
            if ($row["accountcode"] === '1102050101.401') {
                $seamless_dckd_final += $row["seamless_dckd_ofc"];
            }

            // 💡 ปรับปรุงส่วนตรวจสอบสถานะบิล: แยกเงื่อนไขคัดกรองเคสไตและเคสปกติของแต่ละผังออกจากกันอย่างเด่นชัด ยอดสรุปถึงจะตรงกัน
            $is_kidney = (strpos($row["accountcode_key"], '_KIDNEY') !== false);
            $is_hosp_11504 = (strpos($row["accountcode_key"], '_HOSP_11504') !== false);
            $is_hosp_14429 = (strpos($row["accountcode_key"], '_HOSP_14429') !== false);
            $is_hosp_23576 = (strpos($row["accountcode_key"], '_HOSP_23576') !== false);
            $is_hospmain = ($is_hosp_11504 || $is_hosp_14429 || $is_hosp_23576);

            // ปรับยอด CR Cross-Account Splitting (ลดผังแม่ .201, .209, .203 และเพิ่มผัง .216)
            if ($cr_summary && !$is_kidney && !$is_hospmain) {
                if (isset($cr_summary['transfers_by_account'][$accountcode])) {
                    $tr = $cr_summary['transfers_by_account'][$accountcode];
                    $row["debit"] = max(0, cleanNum($row["debit"]) - $tr['transfer_out']);
                    $row["cvn"] = max(0, intval($row["cvn"]) - $tr['full_transfer_cases']);
                    if (isset($tr['comp_out'])) {
                        $row["compensated"] = max(0, cleanNum($row["compensated"]) - $tr['comp_out']);
                    }
                } elseif ($accountcode === '1102050101.216') {
                    $rendered_216 = true;
                    $row["debit"] = $cr_summary['cr_non_kidney_received_total'];
                    $row["cvn"] = $cr_summary['cr_non_kidney_count'];
                    if (!empty($cr_summary['cr_non_kidney_received_comp'])) {
                        $row["compensated"] = cleanNum($cr_summary['cr_non_kidney_received_comp']);
                    }
                }
            }

            // ปรับยอด SSS Instrument Cross-Account Splitting (ลดผังแม่ .301, .303, .307, .308 และเพิ่มผัง .309)
            if ($sss_summary && !$is_kidney && !$is_hospmain) {
                if (isset($sss_summary['transfers_by_account'][$accountcode])) {
                    $tr_sss = $sss_summary['transfers_by_account'][$accountcode];
                    $row["debit"] = max(0, cleanNum($row["debit"]) - $tr_sss['transfer_out']);
                    $row["cvn"] = max(0, intval($row["cvn"]) - $tr_sss['full_transfer_cases']);
                    if (isset($tr_sss['comp_out'])) {
                        $row["compensated"] = max(0, cleanNum($row["compensated"]) - $tr_sss['comp_out']);
                    }
                } elseif ($accountcode === '1102050101.309') {
                    $rendered_309 = true;
                    $row["debit"] = cleanNum($row["debit"]) + cleanNum($sss_summary['sss_received_total']);
                    $row["cvn"] = intval($row["cvn"]) + $sss_summary['sss_count'];
                    if (!empty($sss_summary['sss_received_comp'])) {
                        $row["compensated"] = cleanNum($row["compensated"]) + cleanNum($sss_summary['sss_received_comp']);
                    }
                }
            }

            if ($is_kidney) {
                $sub_condition = "AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%')";
            } elseif ($is_hosp_11504) {
                $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') AND vn IN ($target_vns_11504_str)";
            } elseif ($is_hosp_14429) {
                $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') AND vn IN ($target_vns_14429_str)";
            } elseif ($is_hosp_23576) {
                $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') AND vn IN ($target_vns_23576_str)";
            } else {
                if ($accountcode == '1102050101.203') {
                    $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') AND vn NOT IN ($target_vns_str)";
                } else {
                    $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%')";
                }
            }

            $sqlzz = "SELECT
                        COUNT(accountcode) AS qqq,
                        COUNT(bill) AS www,
                        CASE
                            WHEN COUNT(accountcode) = COUNT(bill) THEN 'Y'
                            ELSE 'N'
                        END AS status_check
                    FROM imr_tb_debtor_rights_opd
                    WHERE accountcode='$accountcode'
                    AND $date_condition $sub_condition";

            $resultzz = $conn->query($sqlzz);

            if ($resultzz) {
                $rowzz = $resultzz->fetch_assoc();
                if ($accountcode == '1102050101.201' || $accountcode == '1102050101.209') {
                    $diffstm = 0;
                } else {
                    if ($rowzz['status_check'] == 'Y') {
                        if ((cleanNum($row["compensated"]) + cleanNum($seamless_dckd_final)) > 0) {
                            $diffstm = cleanNum($row["debit"]) - (cleanNum($row["compensated"]) + cleanNum($seamless_dckd_final));
                        } else {
                            $diffstm = 0;
                        }
                    } else {
                        $diffstm = cleanNum($row["debit"]) - (cleanNum($row["compensated"]) + cleanNum($seamless_dckd_final));
                    }
                }
            } else {
                echo "Error: " . $conn->error;
            }

            // 💡 [แก้ไขสำเร็จ] ฝังการสลับสีข้อความลิงก์: ถ้าเป็นก้อนแถวย่อยโรคไต สั่งเปลี่ยนสีตัวหนังสือเป็นส้มอิฐทันทีคราบบบ
            $link_style = ($is_kidney || $is_hospmain) ? 'style="color: #cd641f; font-weight: bold;"' : '';

            echo '<tr>
                <td><a href="#" onclick="stmgeto(\'' . $row["accountcode_key"] . '\', \'' . $row["accountname"] . '\')">' . htmlspecialchars($row["accountcode"]) . '</a></td>

                <td><a href="#" ' . $link_style . ' onclick="stmgeto(\'' . $row["accountcode_key"] . '\', \'' . $row["accountname"] . '\')">' . htmlspecialchars($row["accountname"]) . '</a></td>

                <td>' . number_format((float)$row["cvn"], 0) . '</td>
                <td style="color: blue; font-weight: bold;">' . formatMoney($row["debit"], 2) . '</td>
                <td style="color: #00b215; font-weight: bold;">' . formatMoney($row["compensated"], 2) . '</td>
                <td style="color: #a49822; font-weight: bold;">' . formatMoney($seamless_dckd_final, 2) . '</td>
                <td style="color: #cd641f; font-weight: bold;">' . formatMoney($diffstm, 2) . '</td>
            </tr>';

            $svn += $row["cvn"];
            $sdebit += $row["debit"];
            $compensated += $row["compensated"];
            $seamless_dckd += $seamless_dckd_final;
            $diffstms += $diffstm;
        }

        // 💡 [CR OPD Engine Standard] แสดงแถวผังลูกจำลอง 1102050101.216 อัตโนมัติหากไม่มีในฐานข้อมูลดิบ
        if (!$rendered_216 && $cr_summary && !empty($cr_summary['cr_non_kidney_count'])) {
            $acc216 = '1102050101.216';
            $accname216 = 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
            $cnt216 = $cr_summary['cr_non_kidney_count'];
            $tot216 = $cr_summary['cr_non_kidney_received_total'];
            $comp216 = cleanNum($cr_summary['cr_non_kidney_received_comp'] ?? 0);
            $diff216 = max(0, $tot216 - $comp216);

            echo '<tr>
                <td><a href="#" onclick="stmgeto(\'' . $acc216 . '\', \'' . $accname216 . '\')">' . $acc216 . '</a></td>
                <td><a href="#" onclick="stmgeto(\'' . $acc216 . '\', \'' . $accname216 . '\')">' . $accname216 . '</a></td>
                <td>' . number_format((float)$cnt216, 0) . '</td>
                <td style="color: blue; font-weight: bold;">' . formatMoney($tot216, 2) . '</td>
                <td style="color: #00b215; font-weight: bold;">' . formatMoney($comp216, 2) . '</td>
                <td style="color: #a49822; font-weight: bold;">0.00</td>
                <td style="color: #cd641f; font-weight: bold;">' . formatMoney($diff216, 2) . '</td>
            </tr>';

            $svn += $cnt216;
            $sdebit += $tot216;
            $compensated += $comp216;
            $diffstms += $diff216;
        }

        // 💡 [SSS OPD Engine Standard] แสดงแถวผังลูกจำลอง 1102050101.309 อัตโนมัติหากไม่มีในฐานข้อมูลดิบ
        if (!$rendered_309 && $sss_summary && !empty($sss_summary['sss_count'])) {
            $acc309 = '1102050101.309';
            $accname309 = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP';
            $cnt309 = $sss_summary['sss_count'];
            $tot309 = $sss_summary['sss_received_total'];
            $comp309 = cleanNum($sss_summary['sss_received_comp'] ?? 0);
            $diff309 = max(0, $tot309 - $comp309);

            echo '<tr>
                <td><a href="#" onclick="stmgeto(\'' . $acc309 . '\', \'' . $accname309 . '\')">' . $acc309 . '</a></td>
                <td><a href="#" onclick="stmgeto(\'' . $acc309 . '\', \'' . $accname309 . '\')">' . $accname309 . '</a></td>
                <td>' . number_format((float)$cnt309, 0) . '</td>
                <td style="color: blue; font-weight: bold;">' . formatMoney($tot309, 2) . '</td>
                <td style="color: #00b215; font-weight: bold;">' . formatMoney($comp309, 2) . '</td>
                <td style="color: #a49822; font-weight: bold;">0.00</td>
                <td style="color: #cd641f; font-weight: bold;">' . formatMoney($diff309, 2) . '</td>
            </tr>';

            $svn += $cnt309;
            $sdebit += $tot309;
            $compensated += $comp309;
            $diffstms += $diff309;
        }

        echo '<tr style="background:#f5f5f5;">
                <td colspan="2" style="text-align:center; font-weight:bold; color:blue;">รวม</td>
                <td>' . number_format((float)$svn, 0) . '</td>
                <td style="color: blue; font-weight: bold;">' . formatMoney($sdebit, 2) . '</td>
                <td style="color: #00b215; font-weight: bold;">' . formatMoney($compensated, 2) . '</td>
                <td style="color: #a49822; font-weight: bold;">' . formatMoney($seamless_dckd, 2) . '</td>
                <td style="color: #cd641f; font-weight: bold;">' . formatMoney($diffstms, 2) . '</td>
            </tr>';
    } else {
        echo '<tr><td colspan="7" style="text-align:center;">ไม่พบข้อมูลในเดือนที่เลือก</td></tr>';
    }

    echo '</tbody></table>';
}





if (isset($_POST['action2'])) {

    $mn = mysqli_real_escape_string($conn, $_POST['action2']);
    $start_date_ipd = isset($_POST['start_date_ipd']) ? mysqli_real_escape_string($conn, $_POST['start_date_ipd']) : '';
    $end_date_ipd = isset($_POST['end_date_ipd']) ? mysqli_real_escape_string($conn, $_POST['end_date_ipd']) : '';

    $has_date_range_ipd = (!empty($start_date_ipd) && !empty($end_date_ipd));
    $date_condition_i = "";
    $date_condition_plain_i = "";
    $months_to_process_ipd = [$mn];

    if ($has_date_range_ipd) {
        $range_ipd = parseToThaiDateRange($start_date_ipd, $end_date_ipd);
        if ($range_ipd) {
            $start_date_th_i = $range_ipd['start_th'];
            $end_date_th_i = $range_ipd['end_th'];

            $date_condition_i = " STR_TO_DATE(i.dchdate, '%d/%m/%Y') BETWEEN '$start_date_th_i' AND '$end_date_th_i' ";
            $date_condition_plain_i = " STR_TO_DATE(dchdate, '%d/%m/%Y') BETWEEN '$start_date_th_i' AND '$end_date_th_i' ";
            $months_to_process_ipd = getMonthsInRange($start_date_ipd, $end_date_ipd);
        } else {
            $has_date_range_ipd = false;
        }
    }

    if (!$has_date_range_ipd) {
        $date_condition_i = " i.monthtxt = '$mn' ";
        $date_condition_plain_i = " monthtxt = '$mn' ";
    }

    $sql = "
        SELECT
            i.accountcode,
            i.accountname,
            COUNT(i.an) AS can,
            SUM(IFNULL(i.rcpt_money, 0)) AS rcpt_money,
            SUM(IFNULL(i.debit, 0)) AS debit,
            SUM(IFNULL(stm.compensated, 0) + IFNULL(i.follow_money, 0)) AS compensated
        FROM imr_tb_debtor_rights_ipd AS i
        LEFT JOIN imr_tb_check_invoice AS stm ON stm.vn = i.an
        WHERE $date_condition_i
        GROUP BY i.accountcode, i.accountname
        ORDER BY can DESC
    ";

    $result = mysqli_query($conn, $sql);

    echo '<table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>รหัสผังบัญชี</th>
                    <th>ชื่อผังบัญชี</th>
                    <th>จำนวน</th>
                    <th>ภาระหนี้</th>
                    <th>ชดเชย STM</th>
                    <th>ชดเชย DCKD</th>
                    <th>ส่วนต่าง</th>
                </tr>
            </thead>
            <tbody>';

    $cr_config_ipd = get_active_cr_config($conn);
    $auto_split_ipd = !empty($cr_config_ipd['auto_split_enabled']);
    $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';

    $cr_summary_ipd = [
        'is_effective' => false,
        'auto_split' => $auto_split_ipd,
        'transfers_by_account' => [],
        'target_account' => '1102050101.217',
        'cr_received_total' => 0.0,
        'cr_non_kidney_count' => 0,
        'cr_kidney_count' => 0,
        'cr_non_kidney_received_total' => 0.0,
        'cr_kidney_received_total' => 0.0,
        'cr_visits' => []
    ];
    $has_any_cr_ipd = false;

    $sss_config_ipd = get_active_sss_config($conn);
    $auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config_ipd, 'IPD');
    $sss_summary_ipd = [
        'is_effective' => false,
        'auto_split' => $auto_split_sss_ipd,
        'transfers_by_account' => [],
        'target_account' => '1102050101.310',
        'sss_received_total' => 0.0,
        'sss_count' => 0,
        'sss_visits' => []
    ];
    $has_any_sss_ipd = false;

    $inactive_cr_months_ipd = [];
    $inactive_sss_months_ipd = [];

    foreach ($months_to_process_ipd as $m_txt_i) {
        if (is_cr_effective_for_month($cr_config_ipd, $m_txt_i) && $auto_split_ipd) {
            $an_filter_cr = null;
            if ($has_date_range_ipd) {
                $ans_cr_q = mysqli_query($conn, "SELECT an FROM imr_tb_debtor_rights_ipd WHERE monthtxt = '$m_txt_i' AND $date_condition_plain_i AND accountcode IN ('1102050101.202', '1102050101.217')");
                $an_filter_cr = [];
                if ($ans_cr_q) {
                    while ($ar = mysqli_fetch_assoc($ans_cr_q)) {
                        $an_filter_cr[] = $ar['an'];
                    }
                }
            }
            if (!$has_date_range_ipd || !empty($an_filter_cr)) {
                $s_cr_i = get_cr_splitting_summary_for_month($conn, $conn2, $m_txt_i, 'IPD', $cr_config_ipd, $my_hospcode, $an_filter_cr);
                if ($s_cr_i) {
                    $has_any_cr_ipd = true;
                    $cr_summary_ipd['is_effective'] = true;
                    $cr_summary_ipd['cr_received_total'] += $s_cr_i['cr_received_total'];
                    $cr_summary_ipd['cr_non_kidney_received_total'] += $s_cr_i['cr_non_kidney_received_total'];
                    $cr_summary_ipd['cr_non_kidney_count'] += $s_cr_i['cr_non_kidney_count'];
                    $cr_summary_ipd['cr_kidney_count'] += $s_cr_i['cr_kidney_count'];
                    $cr_summary_ipd['cr_kidney_received_total'] += $s_cr_i['cr_kidney_received_total'];
                    if (!isset($cr_summary_ipd['cr_received_comp'])) $cr_summary_ipd['cr_received_comp'] = 0.0;
                    $cr_summary_ipd['cr_received_comp'] += cleanNum($s_cr_i['cr_received_comp'] ?? 0);
                    foreach ($s_cr_i['cr_visits'] as $v_k => $v_val) {
                        $cr_summary_ipd['cr_visits'][$v_k] = $v_val;
                    }
                    foreach ($s_cr_i['transfers_by_account'] as $acc => $tr) {
                        if (!isset($cr_summary_ipd['transfers_by_account'][$acc])) {
                            $cr_summary_ipd['transfers_by_account'][$acc] = [
                                'original_debit' => 0.0,
                                'transfer_out' => 0.0,
                                'comp_out' => 0.0,
                                'remain_debit' => 0.0,
                                'cases' => 0,
                                'cr_cases' => 0,
                                'full_transfer_cases' => 0,
                                'partial_transfer_cases' => 0
                            ];
                        }
                        $cr_summary_ipd['transfers_by_account'][$acc]['original_debit'] += $tr['original_debit'];
                        $cr_summary_ipd['transfers_by_account'][$acc]['transfer_out'] += $tr['transfer_out'];
                        $cr_summary_ipd['transfers_by_account'][$acc]['comp_out'] += cleanNum($tr['comp_out'] ?? 0);
                        $cr_summary_ipd['transfers_by_account'][$acc]['remain_debit'] += $tr['remain_debit'];
                        $cr_summary_ipd['transfers_by_account'][$acc]['cases'] += $tr['cases'];
                        $cr_summary_ipd['transfers_by_account'][$acc]['cr_cases'] += $tr['cr_cases'];
                        $cr_summary_ipd['transfers_by_account'][$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
                        $cr_summary_ipd['transfers_by_account'][$acc]['partial_transfer_cases'] += $tr['partial_transfer_cases'];
                    }
                    if (!$has_date_range_ipd) {
                        sync_cr_breakdown_records($conn, $s_cr_i, $m_txt_i, 'IPD');
                    }
                }
            }
        } else {
            $inactive_cr_months_ipd[] = $m_txt_i;
        }

        if (is_sss_effective_for_month($sss_config_ipd, $m_txt_i) && $auto_split_sss_ipd) {
            $an_filter_sss = null;
            if ($has_date_range_ipd) {
                $ans_sss_q = mysqli_query($conn, "SELECT an FROM imr_tb_debtor_rights_ipd WHERE monthtxt = '$m_txt_i' AND $date_condition_plain_i AND accountcode IN ('1102050101.302', '1102050101.304', '1102050101.310')");
                $an_filter_sss = [];
                if ($ans_sss_q) {
                    while ($ar = mysqli_fetch_assoc($ans_sss_q)) {
                        $an_filter_sss[] = $ar['an'];
                    }
                }
            }
            if (!$has_date_range_ipd || !empty($an_filter_sss)) {
                $s_sss_i = get_sss_splitting_summary_for_month($conn, $conn2, $m_txt_i, 'IPD', $sss_config_ipd, $my_hospcode, $an_filter_sss);
                if ($s_sss_i) {
                    $has_any_sss_ipd = true;
                    $sss_summary_ipd['is_effective'] = true;
                    $sss_summary_ipd['sss_received_total'] += $s_sss_i['sss_received_total'];
                    $sss_summary_ipd['sss_count'] += $s_sss_i['sss_count'];
                    if (!isset($sss_summary_ipd['sss_received_comp'])) $sss_summary_ipd['sss_received_comp'] = 0.0;
                    $sss_summary_ipd['sss_received_comp'] += cleanNum($s_sss_i['sss_received_comp'] ?? 0);
                    foreach ($s_sss_i['sss_visits'] as $v_k => $v_val) {
                        $sss_summary_ipd['sss_visits'][$v_k] = $v_val;
                    }
                    foreach ($s_sss_i['transfers_by_account'] as $acc => $tr) {
                        if (!isset($sss_summary_ipd['transfers_by_account'][$acc])) {
                            $sss_summary_ipd['transfers_by_account'][$acc] = [
                                'original_debit' => 0.0,
                                'transfer_out' => 0.0,
                                'comp_out' => 0.0,
                                'remain_debit' => 0.0,
                                'cases' => 0,
                                'sss_cases' => 0,
                                'full_transfer_cases' => 0,
                                'partial_transfer_cases' => 0
                            ];
                        }
                        $sss_summary_ipd['transfers_by_account'][$acc]['original_debit'] += $tr['original_debit'];
                        $sss_summary_ipd['transfers_by_account'][$acc]['transfer_out'] += $tr['transfer_out'];
                        $sss_summary_ipd['transfers_by_account'][$acc]['comp_out'] += cleanNum($tr['comp_out'] ?? 0);
                        $sss_summary_ipd['transfers_by_account'][$acc]['remain_debit'] += $tr['remain_debit'];
                        $sss_summary_ipd['transfers_by_account'][$acc]['cases'] += $tr['cases'];
                        $sss_summary_ipd['transfers_by_account'][$acc]['sss_cases'] += $tr['sss_cases'];
                        $sss_summary_ipd['transfers_by_account'][$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
                        $sss_summary_ipd['transfers_by_account'][$acc]['partial_transfer_cases'] += $tr['partial_transfer_cases'];
                    }
                    if (!$has_date_range_ipd) {
                        sync_sss_breakdown_records($conn, $s_sss_i, $m_txt_i, 'IPD');
                    }
                }
            }
        } else {
            $inactive_sss_months_ipd[] = $m_txt_i;
        }
    }

    // รวมยอดลูกหนี้เดิมของผัง .217 จากเดือนที่ยังไม่ได้เปิดระบบ CR ในช่วงเวลาที่เลือก
    if (!empty($inactive_cr_months_ipd) && $has_any_cr_ipd) {
        $in_m_str_i = "'" . implode("','", array_map(function($m) use ($conn) { return mysqli_real_escape_string($conn, $m); }, $inactive_cr_months_ipd)) . "'";
        $q_inact_i = mysqli_query($conn, "SELECT COUNT(o.an) as c, COALESCE(SUM(o.debit), 0) as d,
                                           COALESCE(SUM(IFNULL(o.follow_money, 0) + IFNULL(stm.compensated, 0)), 0) as comp 
                                           FROM imr_tb_debtor_rights_ipd o
                                           LEFT JOIN imr_tb_check_invoice stm ON stm.an = o.an
                                           WHERE o.accountcode = '1102050101.217' 
                                           AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')
                                           AND o.monthtxt IN ($in_m_str_i) AND $date_condition_plain_i");
        if ($q_inact_i && $r_inact_i = mysqli_fetch_assoc($q_inact_i)) {
            $cr_summary_ipd['cr_non_kidney_count'] += intval($r_inact_i['c']);
            $cr_summary_ipd['cr_non_kidney_received_total'] += floatval($r_inact_i['d']);
            $cr_summary_ipd['cr_received_total'] += floatval($r_inact_i['d']);
            if (!isset($cr_summary_ipd['cr_received_comp'])) $cr_summary_ipd['cr_received_comp'] = 0.0;
            $cr_summary_ipd['cr_received_comp'] += floatval($r_inact_i['comp']);
        }
    }

    // รวมยอดลูกหนี้เดิมของผัง .310 จากเดือนที่ยังไม่ได้เปิดระบบ SSS ในช่วงเวลาที่เลือก
    if (!empty($inactive_sss_months_ipd) && $has_any_sss_ipd) {
        $in_m_str_si = "'" . implode("','", array_map(function($m) use ($conn) { return mysqli_real_escape_string($conn, $m); }, $inactive_sss_months_ipd)) . "'";
        $q_inact_si = mysqli_query($conn, "SELECT COUNT(an) as c, COALESCE(SUM(debit), 0) as d 
                                            FROM imr_tb_debtor_rights_ipd 
                                            WHERE accountcode = '1102050101.310' 
                                            AND monthtxt IN ($in_m_str_si) AND $date_condition_plain_i");
        if ($q_inact_si && $r_inact_si = mysqli_fetch_assoc($q_inact_si)) {
            $sss_summary_ipd['sss_count'] += intval($r_inact_si['c']);
            $sss_summary_ipd['sss_received_total'] += floatval($r_inact_si['d']);
        }
    }

    if (!$has_any_cr_ipd) $cr_summary_ipd = null;
    if (!$has_any_sss_ipd) $sss_summary_ipd = null;

    if ($result && mysqli_num_rows($result) > 0) {
        $san = $sdebit = $compensated = $diffstms = 0;
        $rendered_217 = false;
        $rendered_310 = false;

        while ($row = mysqli_fetch_assoc($result)) {
            if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
            if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);

            // ปรับยอด CR Cross-Account Splitting (ลดผังแม่ .202 และเพิ่มผัง .217)
            if ($cr_summary_ipd) {
                if (isset($cr_summary_ipd['transfers_by_account'][$row['accountcode']])) {
                    $tr_ipd = $cr_summary_ipd['transfers_by_account'][$row['accountcode']];
                    $row["debit"] = max(0, cleanNum($row["debit"]) - $tr_ipd['transfer_out']);
                    $row["can"] = max(0, intval($row["can"]) - $tr_ipd['full_transfer_cases']);
                    if (isset($tr_ipd['comp_out'])) {
                        $row["compensated"] = max(0, cleanNum($row["compensated"]) - $tr_ipd['comp_out']);
                    }
                } elseif ($row['accountcode'] === '1102050101.217') {
                    $rendered_217 = true;
                    $row["debit"] = cleanNum($cr_summary_ipd['cr_received_total']);
                    $row["can"] = $cr_summary_ipd['cr_non_kidney_count'];
                    if (!empty($cr_summary_ipd['cr_received_comp'])) {
                        $row["compensated"] = cleanNum($cr_summary_ipd['cr_received_comp']);
                    }
                }
            }

            // ปรับยอด SSS Instrument Cross-Account Splitting (ลดผังแม่ .302, .304 และเพิ่มผัง .310)
            if ($sss_summary_ipd) {
                if (isset($sss_summary_ipd['transfers_by_account'][$row['accountcode']])) {
                    $tr_sss_ipd = $sss_summary_ipd['transfers_by_account'][$row['accountcode']];
                    $row["debit"] = max(0, cleanNum($row["debit"]) - $tr_sss_ipd['transfer_out']);
                    $row["can"] = max(0, intval($row["can"]) - $tr_sss_ipd['full_transfer_cases']);
                    if (isset($tr_sss_ipd['comp_out'])) {
                        $row["compensated"] = max(0, cleanNum($row["compensated"]) - $tr_sss_ipd['comp_out']);
                    }
                } elseif ($row['accountcode'] === '1102050101.310') {
                    $rendered_310 = true;
                    $row["debit"] = cleanNum($row["debit"]) + cleanNum($sss_summary_ipd['sss_received_total']);
                    $row["can"] = intval($row["can"]) + $sss_summary_ipd['sss_count'];
                    if (!empty($sss_summary_ipd['sss_received_comp'])) {
                        $row["compensated"] = cleanNum($row["compensated"]) + cleanNum($sss_summary_ipd['sss_received_comp']);
                    }
                }
            }

            if ($row["accountcode"] == '1102050101.201' || $row["accountcode"] == '1102050101.209') {
                $diffstm = 0;
            } else {
                $diffstm = cleanNum($row["debit"]) - cleanNum($row["compensated"]);
            }

            echo '<tr>
                    <td><a href="#" onclick="stmgeti(\'' . $row["accountcode"] . '\', \'' . $row["accountname"] . '\')">' . htmlspecialchars($row["accountcode"]) . '</a></td>
                    <td><a href="#" onclick="stmgeti(\'' . $row["accountcode"] . '\', \'' . $row["accountname"] . '\')">' . htmlspecialchars($row["accountname"]) . '</a></td>
                    <td>' . number_format((float)$row["can"], 0) . '</td>
                    <td style="color: blue; font-weight: bold;">' . formatMoney($row["debit"], 2) . '</td>
                    <td style="color: #00b215; font-weight: bold;">' . formatMoney($row["compensated"], 2) . '</td>
                    <td style="color: #a49822; font-weight: bold;">-</td>
                    <td style="color: #cd641f; font-weight: bold;">' . formatMoney($diffstm, 2) . '</td>
                </tr>';

            $san += $row["can"];
            $sdebit += $row["debit"];
            $compensated += $row["compensated"];
            $diffstms += $diffstm;
        }

        // 💡 [CR IPD Engine Standard] แสดงแถวผังลูกจำลอง 1102050101.217 อัตโนมัติหากไม่มีในฐานข้อมูลดิบ
        if (!$rendered_217 && $cr_summary_ipd && count($cr_summary_ipd['cr_visits']) > 0) {
            $acc217 = '1102050101.217';
            $accname217 = 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)';
            $cnt217 = count($cr_summary_ipd['cr_visits']);
            $tot217 = $cr_summary_ipd['cr_received_total'];
            $comp217 = cleanNum($cr_summary_ipd['cr_received_comp'] ?? 0);
            $diff217 = max(0, $tot217 - $comp217);

            echo '<tr>
                    <td><a href="#" onclick="stmgeti(\'' . $acc217 . '\', \'' . $accname217 . '\')">' . $acc217 . '</a></td>
                    <td><a href="#" onclick="stmgeti(\'' . $acc217 . '\', \'' . $accname217 . '\')">' . $accname217 . '</a></td>
                    <td>' . number_format((float)$cnt217, 0) . '</td>
                    <td style="color: blue; font-weight: bold;">' . formatMoney($tot217, 2) . '</td>
                    <td style="color: #00b215; font-weight: bold;">' . formatMoney($comp217, 2) . '</td>
                    <td style="color: #a49822; font-weight: bold;">-</td>
                    <td style="color: #cd641f; font-weight: bold;">' . formatMoney($diff217, 2) . '</td>
                </tr>';

            $san += $cnt217;
            $sdebit += $tot217;
            $compensated += $comp217;
            $diffstms += $diff217;
        }

        // 💡 [SSS IPD Engine Standard] แสดงแถวผังลูกจำลอง 1102050101.310 อัตโนมัติหากไม่มีในฐานข้อมูลดิบ
        if (!$rendered_310 && $sss_summary_ipd && count($sss_summary_ipd['sss_visits']) > 0) {
            $acc310 = '1102050101.310';
            $accname310 = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง IP';
            $cnt310 = count($sss_summary_ipd['sss_visits']);
            $tot310 = $sss_summary_ipd['sss_received_total'];
            $comp310 = cleanNum($sss_summary_ipd['sss_received_comp'] ?? 0);
            $diff310 = max(0, $tot310 - $comp310);

            echo '<tr>
                    <td><a href="#" onclick="stmgeti(\'' . $acc310 . '\', \'' . $accname310 . '\')">' . $acc310 . '</a></td>
                    <td><a href="#" onclick="stmgeti(\'' . $acc310 . '\', \'' . $accname310 . '\')">' . $accname310 . '</a></td>
                    <td>' . number_format((float)$cnt310, 0) . '</td>
                    <td style="color: blue; font-weight: bold;">' . formatMoney($tot310, 2) . '</td>
                    <td style="color: #00b215; font-weight: bold;">' . formatMoney($comp310, 2) . '</td>
                    <td style="color: #a49822; font-weight: bold;">-</td>
                    <td style="color: #cd641f; font-weight: bold;">' . formatMoney($diff310, 2) . '</td>
                </tr>';

            $san += $cnt310;
            $sdebit += $tot310;
            $compensated += $comp310;
            $diffstms += $diff310;
        }

        echo '</tbody>
              <tfoot>
                <tr style="background:#f5f5f5;">
                    <td colspan="2" style="text-align:center; color:blue; font-weight:bold;">รวม</td>
                    <td>' . number_format((float)$san, 0) . '</td>
                    <td style="color: blue; font-weight: bold;">' . formatMoney($sdebit, 2) . '</td>
                    <td style="color: #00b215; font-weight: bold;">' . formatMoney($compensated, 2) . '</td>
                    <td style="color: #a49822; font-weight: bold;">0.00</td>
                    <td style="color: #cd641f; font-weight: bold;">' . formatMoney($diffstms, 2) . '</td>
                </tr>
              </tfoot>';
    } else {
        echo '<tr><td colspan="7" style="text-align:center;">ไม่พบข้อมูลในเดือนที่เลือก</td></tr>';
    }

    echo '</table>';
}





        if(isset($_POST['actionrep'])){
          $sql = "SELECT maininscl,department,COUNT(rep) as rep,sum(uc_money) as uc_money,sum(collected) as collected ,sum(compensated) as compensated
                  FROM imr_tb_check_rep
                  GROUP BY  maininscl
                  ORDER BY rep desc";

          $result = mysqli_query($conn, $sql);

          echo '<table class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th>สิทธิหลัก</th>
                      <th>ลูกหนี้สิทธิ</th>
                      <th>เรียกเก็บ</th>
                      <th>เงินชดเชย</th>
                    </tr>
                  </thead>
                  <tbody>';

            if ($result && mysqli_num_rows($result) > 0) {
              // output data of each row
              $i=1;$uc_money=0;$collected=0;$compensated=0;
              while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);

                echo'<tr>
                      <td>'.$row["maininscl"].'</td>
                      <td>'.number_format((float)$row["uc_money"],0).'</td>
                      <td>'.formatMoney($row["collected"], 2).'</td>
                      <td>'.formatMoney($row["compensated"], 2).'</td>
                    </tr>
                ';

                $uc_money = $uc_money + cleanNum($row["uc_money"]);
                $collected = $collected + cleanNum($row["collected"]);
                $compensated = $compensated + cleanNum($row["compensated"]);

                $i++;
                }

                echo'<tr>
                      <td style="text-align:center;color: blue;font-weight: bold;" colspan="1">รวม</td>
                      <td style="color: blue;font-weight: bold;" >'.number_format((float)$uc_money,0).'</td>
                      <td style="color: blue;font-weight: bold;" >'.formatMoney($collected, 2).'</td>
                      <td style="color: blue;font-weight: bold;" >'.formatMoney($compensated, 2).'</td>
                    </tr>';

              }

              echo'</tbody>
                  </table>';
        }





        if (isset($_POST['actionckd'])) {

          $sql = "(
          SELECT
          stm_doc AS doc_no,
          SUM(amount) AS compensated,
          'OFC' AS fund,
          'ฟอกไต OFC-สิทธิข้าราชการ/สิทธิหน่วยงานต้นสังกัด' AS source
          FROM imr_tb_seamless_dckd_summary_ofc
          GROUP BY stm_doc
          )
          UNION ALL
          (
          SELECT
          rep AS doc_no,
          SUM(compensated) AS compensated,
          fund,
          CASE
          WHEN fund = 'UCS' THEN 'ฟอกไต UCS-สิทธิบัตรทอง'
          WHEN fund = 'SSS' THEN 'ฟอกไต SSS-สิทธิประกันสังคม'
          WHEN fund = 'LGO' THEN 'ฟอกไต LGO-สิทธิ อปท.'
          ELSE CONCAT('ฟอกไต ', fund, ' - สิทธิ')
          END AS source
          FROM imr_tb_seamless_dckd
          GROUP BY rep, fund
          )
          ORDER BY FIELD(source,
          'ฟอกไต UCS-สิทธิบัตรทอง',
          'ฟอกไต SSS-สิทธิประกันสังคม',
          'ฟอกไต OFC-สิทธิข้าราชการ/สิทธิหน่วยงานต้นสังกัด',
          'ฟอกไต LGO-สิทธิ อปท.'
        ), doc_no DESC";

        $result = mysqli_query($conn, $sql);

        echo '<table class="table table-striped table-bordered">
        <thead>
        <tr>
        <th style="background-color: #ededed;">กองทุน</th>
        <th style="background-color: #ededed;">เลขหนังสือ</th>
        <th style="background-color: #ededed;">เงินชดเชย DCKD</th>
        </tr>
        </thead>
        <tbody>';

        if ($result && mysqli_num_rows($result) > 0) {
          $group_total = 0;
          $compensated_total = 0;
          $ucstotal = 0;
          $ssstotal = 0;
          $ofctotal = 0;

          $current_source = '';
          $first_row = true;

          while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
            // เปลี่ยนกลุ่ม
            if ($current_source !== $row["source"]) {
                // แสดงรวมกลุ่มก่อนหน้า
              if (!$first_row) {
                echo '<tr>
                <td colspan="2" style="text-align:right; font-weight:bold; color:green;">รวมชดเชย ' . fundLabel($current_source) . '</td>
                <td style="font-weight:bold; color:green;">' . formatMoney($group_total, 2) . '</td>
                </tr>';
              }

              $current_source = $row["source"];
              $group_total = 0;

              echo '<tr>
              <td colspan="3" style="background-color:#f0f0f0;font-weight:bold;">' . $current_source . '</td>
              </tr>';

              $first_row = false;
            }

            echo '<tr>
            <td>' . $row["fund"] . '</td>
            <td>' . $row["doc_no"] . '</td>
            <td>' . formatMoney($row["compensated"], 2) . '</td>
            </tr>';

            $group_total += $row["compensated"];
            $compensated_total += $row["compensated"];

            // รวมแยกกองทุน
            if ($row["fund"] === "UCS") {
              $ucstotal += $row["compensated"];
            } elseif ($row["fund"] === "SSS") {
              $ssstotal += $row["compensated"];
            } elseif ($row["fund"] === "OFC") {
              $ofctotal += $row["compensated"];
            }
          }

        // รวมกลุ่มสุดท้าย
          echo '<tr>
          <td colspan="2" style="text-align:right; font-weight:bold; color:green;">รวมชดเชย ' . fundLabel($current_source) . '</td>
          <td style="font-weight:bold; color:green;">' . formatMoney($group_total, 2) . '</td>
          </tr>';


        // 🔷 รวมทั้งหมด
          echo '<tr>
          <td colspan="2" style="text-align:center;color: blue;font-weight: bold;">รวมทั้งหมดทุกกองทุน</td>
          <td style="color: blue;font-weight: bold;">' . formatMoney($compensated_total, 2) . '</td>
          </tr>';
        }

        echo '</tbody></table>';
      }








        if(isset($_POST['actionstm'])){
          $sql = "SELECT accountcode,accountname,COUNT(vn) as cvn,sum(debit) as debit,sum(compensated) as compensated,sum(diff) as diff,sum(down) as down,sum(up) as up
                  FROM imr_tb_check_invoice

                  GROUP BY  accountname
                  ORDER BY cvn desc";

          $result = mysqli_query($conn, $sql);

          echo '<table class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th style="background-color: #ededed;">รหัสผังบัญชี</th>
                      <th style="background-color: #ededed;">ชื่อผังบัญชี</th>
                      <th style="background-color: #ededed;">จำนวน</th>
                      <th style="background-color: #ededed;">ภาระหนี้</th>
                      <th style="background-color: #ededed;">เงินชดเชย</th>
                      <th style="background-color: #ededed;">ผลต่าง</th>
                      <th style="background-color: #ededed;">ส่วนต่ำ</th>
                      <th style="background-color: #ededed;">ส่วนสูง</th>
                    </tr>
                  </thead>
                  <tbody>';


        if ($result && mysqli_num_rows($result) > 0) {
          // output data of each row
          $i=1;$san=0;$srcpt_money=0;$sdebit=0;$diff=0;$down=0;$up=0;
          while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);

            echo'<tr>
                  <td>'.$row["accountcode"].'</td>
                  <td>'.$row["accountname"].'</td>
                  <td>'.number_format((float)$row["cvn"],0).'</td>
                  <td>'.formatMoney($row["debit"], 2).'</td>
                  <td>'.formatMoney($row["compensated"], 2).'</td>
                  <td>'.formatMoney($row["diff"], 2).'</td>
                  <td>'.formatMoney($row["down"], 2).'</td>
                  <td>'.formatMoney($row["up"], 2).'</td>

                </tr>
            ';

            $san = $san + cleanNum($row["cvn"]);
            $sdebit = $sdebit + cleanNum($row["debit"]);
            $srcpt_money = $srcpt_money + cleanNum($row["compensated"]);
            $diff = $diff + cleanNum($row["diff"]);
            $down = $down + cleanNum($row["down"]);
            $up = $up + cleanNum($row["up"]);

            $i++;
            }

            echo'<tr>
                  <td style="text-align:center;color: blue;font-weight: bold;" colspan="2">รวม</td>
                  <td>'.number_format((float)$san,0).'</td>
                  <td>'.formatMoney($sdebit, 2).'</td>
                  <td style="color: blue;font-weight: bold;" >'.formatMoney($srcpt_money, 2).'</td>
                  <td style="color: #ff6d07;font-weight: bold;" >'.formatMoney($diff, 2).'</td>
                  <td style="color: #34b300;font-weight: bold;" >'.formatMoney($down, 2).'</td>
                  <td style="color: #ff2800;font-weight: bold;" >'.formatMoney($up, 2).'</td>

                </tr>';

          }

          echo'</tbody>
              </table>';
        }


        // =========================================================================
        // [อัปเดตอัจฉริยะ] ชุดแสดงผลเมื่อคลิกผังบัญชีสรุป ผู้ป่วยนอก (stmgeto)
        // ดึงข้อมูลสถานพยาบาลหลัก (Hospmain) "ปัจจุบันล่าสุด" ของคนไข้รายบุคคลผ่าน $conn2
        // =========================================================================
        if(isset($_POST['stmgeto'])){
            $stmget = mysqli_real_escape_string($conn, $_POST['stmgeto']);
            $mn = mysqli_real_escape_string($conn, $_POST['yyy']);

            $start_date_opd = isset($_POST['start_date_opd']) ? mysqli_real_escape_string($conn, $_POST['start_date_opd']) : '';
            $end_date_opd = isset($_POST['end_date_opd']) ? mysqli_real_escape_string($conn, $_POST['end_date_opd']) : '';

            $has_date_range_opd = (!empty($start_date_opd) && !empty($end_date_opd));
            $date_condition_stm_o = "";
            $months_to_process_opd = [$mn];

            if ($has_date_range_opd) {
                $range_info_opd = parseToThaiDateRange($start_date_opd, $end_date_opd);
                if ($range_info_opd) {
                    $start_date_th_stm = $range_info_opd['start_th'];
                    $end_date_th_stm = $range_info_opd['end_th'];

                    $date_condition_stm_o = " STR_TO_DATE(o.vstdate, '%d/%m/%Y') BETWEEN '$start_date_th_stm' AND '$end_date_th_stm' ";
                    $months_to_process_opd = getMonthsInRange($start_date_opd, $end_date_opd);
                } else {
                    $has_date_range_opd = false;
                }
            }

            if (!$has_date_range_opd) {
                $date_condition_stm_o = " o.monthtxt = '$mn' ";
            }

            $target_vns_str = "''";
            $target_vns_11504_str = "''";
            $target_vns_14429_str = "''";
            $target_vns_23576_str = "''";
            $vn_q = mysqli_query($conn, "SELECT o.vn FROM imr_tb_debtor_rights_opd o WHERE o.accountcode = '1102050101.203' AND $date_condition_stm_o");
            if($vn_q && mysqli_num_rows($vn_q) > 0) {
                $vn_list = [];
                while ($v = mysqli_fetch_assoc($vn_q)) {
                    if (isset($v['cid'])) $v['cid'] = decrypt_data($v['cid']);
                    if (isset($v['tel'])) $v['tel'] = decrypt_data($v['tel']);
                    $vn_list[] = "'" . mysqli_real_escape_string($conn2, $v['vn']) . "'";
                }
                $in_vns = implode(",", $vn_list);
                $hosp_q = mysqli_query($conn2, "SELECT vn, hospmain FROM ovst WHERE vn IN ($in_vns) AND hospmain IN ('11504', '14429', '23576')");
                if($hosp_q) {
                    $target_vns = [];
                    $vns_11504 = [];
                    $vns_14429 = [];
                    $vns_23576 = [];
                    while ($h = mysqli_fetch_assoc($hosp_q)) {
                        if (isset($h['cid'])) $h['cid'] = decrypt_data($h['cid']);
                        if (isset($h['tel'])) $h['tel'] = decrypt_data($h['tel']);
                        $target_vns[] = "'" . $h['vn'] . "'";
                        if ($h['hospmain'] == '11504') $vns_11504[] = "'" . $h['vn'] . "'";
                        elseif ($h['hospmain'] == '14429') $vns_14429[] = "'" . $h['vn'] . "'";
                        elseif ($h['hospmain'] == '23576') $vns_23576[] = "'" . $h['vn'] . "'";
                    }
                    if(count($target_vns) > 0) $target_vns_str = implode(",", $target_vns);
                    if(count($vns_11504) > 0) $target_vns_11504_str = implode(",", $vns_11504);
                    if(count($vns_14429) > 0) $target_vns_14429_str = implode(",", $vns_14429);
                    if(count($vns_23576) > 0) $target_vns_23576_str = implode(",", $vns_23576);
                }
            }

            // 1. ดึงการตั้งค่า CR Splitting สำหรับ OPD
            $cr_config = get_active_cr_config($conn);
            $auto_split_opd = is_cr_auto_split_enabled($cr_config, 'OPD');
            $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
            $all_cr_visits = [];
            $is_cr_effective = false;

            // 1.1 ดึงการตั้งค่า SSS Splitting สำหรับ OPD
            $sss_config = get_active_sss_config($conn);
            $auto_split_sss_opd = is_sss_auto_split_enabled($sss_config, 'OPD');
            $all_sss_visits = [];
            $is_sss_effective = false;

            foreach ($months_to_process_opd as $m_stm_txt) {
                if (is_cr_effective_for_month($cr_config, $m_stm_txt) && $auto_split_opd) {
                    $is_cr_effective = true;
                    $vn_filter_cr = null;
                    if ($has_date_range_opd) {
                        $vns_m_q = mysqli_query($conn, "SELECT vn FROM imr_tb_debtor_rights_opd o WHERE monthtxt = '$m_stm_txt' AND $date_condition_stm_o AND accountcode IN ('1102050101.201','1102050101.209','1102050101.203','1102050101.216')");
                        $vn_filter_cr = [];
                        if ($vns_m_q) {
                            while ($vr = mysqli_fetch_assoc($vns_m_q)) {
                                $vn_filter_cr[] = $vr['vn'];
                            }
                        }
                    }
                    if (!$has_date_range_opd || !empty($vn_filter_cr)) {
                        $s = get_cr_splitting_summary_for_month($conn, $conn2, $m_stm_txt, 'OPD', $cr_config, $my_hospcode, $vn_filter_cr);
                        if ($s && !empty($s['cr_visits'])) {
                            foreach ($s['cr_visits'] as $v_k => $v_v) {
                                $all_cr_visits[$v_k] = $v_v;
                            }
                        }
                    }
                }

                if (is_sss_effective_for_month($sss_config, $m_stm_txt) && $auto_split_sss_opd) {
                    $is_sss_effective = true;
                    $vn_filter_sss = null;
                    if ($has_date_range_opd) {
                        $vns_sss_q = mysqli_query($conn, "SELECT vn FROM imr_tb_debtor_rights_opd o WHERE monthtxt = '$m_stm_txt' AND $date_condition_stm_o AND accountcode IN ('1102050101.301','1102050101.303','1102050101.307','1102050101.308','1102050101.309')");
                        $vn_filter_sss = [];
                        if ($vns_sss_q) {
                            while ($vr = mysqli_fetch_assoc($vns_sss_q)) {
                                $vn_filter_sss[] = $vr['vn'];
                            }
                        }
                    }
                    if (!$has_date_range_opd || !empty($vn_filter_sss)) {
                        $s_sss = get_sss_splitting_summary_for_month($conn, $conn2, $m_stm_txt, 'OPD', $sss_config, $my_hospcode, $vn_filter_sss);
                        if ($s_sss && !empty($s_sss['sss_visits'])) {
                            foreach ($s_sss['sss_visits'] as $v_k => $v_v) {
                                $all_sss_visits[$v_k] = $v_v;
                            }
                        }
                    }
                }
            }

            $is_kidney_click = (strpos($stmget, '_KIDNEY') !== false);
            $is_target_216 = ($stmget === '1102050101.216' || (strpos($stmget, '1102050101.216') !== false && !$is_kidney_click));
            $is_target_309 = ($stmget === '1102050101.309' || (strpos($stmget, '1102050101.309') !== false && !$is_kidney_click));
            $is_hosp_11504_click = (strpos($stmget, '_HOSP_11504') !== false);
            $is_hosp_14429_click = (strpos($stmget, '_HOSP_14429') !== false);
            $is_hosp_23576_click = (strpos($stmget, '_HOSP_23576') !== false);
            $is_hospmain_click = ($is_hosp_11504_click || $is_hosp_14429_click || $is_hosp_23576_click);
            $is_sss_parent_opd = $is_sss_effective && $auto_split_sss_opd && in_array(explode('_', $stmget)[0], ['1102050101.301', '1102050101.303', '1102050101.307', '1102050101.308']) && is_sss_parent_account_enabled($sss_config, explode('_', $stmget)[0]) && !$is_kidney_click && !$is_hospmain_click;
            $is_cr_parent_opd = $is_cr_effective && $auto_split_opd && in_array(explode('_', $stmget)[0], ['1102050101.201', '1102050101.209', '1102050101.203']) && is_cr_parent_account_enabled($cr_config, explode('_', $stmget)[0]) && !$is_kidney_click && !$is_hospmain_click;

            if ($is_kidney_click) {
                $base_account = str_replace('_KIDNEY', '', $stmget);
                $condition = "o.accountcode = '$base_account' AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%')";
            } elseif ($is_hosp_11504_click) {
                $base_account = str_replace('_HOSP_11504', '', $stmget);
                $condition = "o.accountcode = '$base_account' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') AND o.vn IN ($target_vns_11504_str)";
            } elseif ($is_hosp_14429_click) {
                $base_account = str_replace('_HOSP_14429', '', $stmget);
                $condition = "o.accountcode = '$base_account' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') AND o.vn IN ($target_vns_14429_str)";
            } elseif ($is_hosp_23576_click) {
                $base_account = str_replace('_HOSP_23576', '', $stmget);
                $condition = "o.accountcode = '$base_account' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') AND o.vn IN ($target_vns_23576_str)";
            } else {
                if ($is_target_216 && !empty($all_cr_visits)) {
                    $transfer_vns = array_keys($all_cr_visits);
                    $transfer_vns_quoted = !empty($transfer_vns) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_vns)) . "'" : "''";
                    $condition = "((o.accountcode = '1102050101.216') OR (o.vn IN ($transfer_vns_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
                } elseif ($is_target_309 && !empty($all_sss_visits)) {
                    $transfer_sss_vns = array_keys($all_sss_visits);
                    $transfer_sss_vns_quoted = !empty($transfer_sss_vns) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_sss_vns)) . "'" : "''";
                    $condition = "((o.accountcode = '1102050101.309') OR (o.vn IN ($transfer_sss_vns_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
                } elseif ($stmget == '1102050101.203') {
                    $condition = "o.accountcode = '$stmget' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') AND o.vn NOT IN ($target_vns_str)";
                } else {
                    $condition = "o.accountcode = '$stmget' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
                }
            }

            $sql = "
                SELECT
                    COALESCE(stm.rep, ckd.rep, ckd_ofc.stm_doc, o.bill) AS bookno,
                    o.accountcode,
                    CASE
                        WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', 'ผู้ป่วยไตวายเรื้อรัง')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ รพ.ค่าย เบิก นค.')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 1 วัดเหนือ เบิก นค.')
                        WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 2 สระสิม เบิก นค.')
                        ELSE o.accountname
                    END AS accountname,
                    o.vn, o.hn, o.vstdate, o.cid, o.ptname, o.debit, o.income, (o.income - COALESCE(o.original_debit, o.debit)) as incomediff,
                    stm.compensated,
                    ckd.compensated AS ckd_compensated,
                    ckd_ofc.amount AS ofc_compensated,
                    o.bill, o.follow_money, o.totalall,
                    o.mobile, o.billdate, o.pttypename, o.monthtxt
                FROM imr_tb_debtor_rights_opd o
                LEFT JOIN imr_tb_check_invoice stm ON stm.vn = o.vn
                LEFT JOIN imr_tb_seamless_dckd ckd ON ckd.vn = o.vn
                LEFT JOIN imr_tb_seamless_dckd_ofc ckd_ofc ON ckd_ofc.vn = o.vn
                WHERE $condition AND $date_condition_stm_o
                GROUP BY o.vn
                ORDER BY o.accountcode ASC, o.vstdate ASC
            ";

            $result = mysqli_query($conn, $sql);
            $raw_rows = [];
            $vns_to_detect = [];
            if ($result && mysqli_num_rows($result) > 0) {
                while ($r = mysqli_fetch_assoc($result)) {
                    $raw_rows[] = $r;
                    if (!empty($r['vn'])) $vns_to_detect[] = $r['vn'];
                }
            }

            $is_cr_target_account_opd = $is_cr_effective && $auto_split_opd && ($is_target_216 || strpos($stmget, '1102050101.216') !== false) && !$is_kidney_click;
            $is_sss_target_account_opd = $is_sss_effective && $auto_split_sss_opd && ($is_target_309 || strpos($stmget, '1102050101.309') !== false) && !$is_kidney_click;

            $cr_map = [];
            $sss_map = [];
            $subgroup_stats = [];

            $check_stm_m = !empty($mn) ? $mn : (!empty($months_to_process_opd) ? $months_to_process_opd : null);

            if ($is_cr_target_account_opd || $is_cr_parent_opd) {
                if (!empty($vns_to_detect) && $conn2) {
                    $cr_map = detect_cr_types_for_visit_list($conn2, $vns_to_detect, 'OPD', $cr_config, $my_hospcode, $check_stm_m);
                }

                $subgroup_stats = [
                    'ALL' => [
                        'id' => 'ALL',
                        'title' => 'ทั้งหมด (All CR)',
                        'short_name' => 'ทั้งหมด',
                        'count' => 0,
                        'debit' => 0.0
                    ]
                ];
                $sg_source_opd = isset($cr_config['subgroups_opd']) ? $cr_config['subgroups_opd'] : (isset($cr_config['subgroups']) ? $cr_config['subgroups'] : []);
                if (!empty($sg_source_opd)) {
                    foreach ($sg_source_opd as $sg_id => $sg) {
                        if (!is_cr_subgroup_enabled($cr_config, $sg_id, 'OPD', $check_stm_m)) continue;
                        $subgroup_stats[$sg_id] = [
                            'id' => $sg_id,
                            'title' => $sg['title'] ?? $sg_id,
                            'short_name' => $sg['short_name'] ?? $sg_id,
                            'count' => 0,
                            'debit' => 0.0
                        ];
                    }
                }
            } elseif ($is_sss_target_account_opd || $is_sss_parent_opd) {
                if (!empty($vns_to_detect) && $conn2) {
                    $sss_map = detect_sss_types_for_visit_list($conn2, $vns_to_detect, 'OPD', $sss_config, $my_hospcode, $check_stm_m);
                }

                $subgroup_stats = [
                    'ALL' => [
                        'id' => 'ALL',
                        'title' => 'ทั้งหมด (All SSS)',
                        'short_name' => 'ทั้งหมด',
                        'count' => 0,
                        'debit' => 0.0
                    ]
                ];
                $sg_source_sss_opd = isset($sss_config['subgroups_opd']) ? $sss_config['subgroups_opd'] : [];
                if (!empty($sg_source_sss_opd)) {
                    foreach ($sg_source_sss_opd as $sg_id => $sg) {
                        if (!is_sss_subgroup_enabled($sss_config, $sg_id, 'OPD', $check_stm_m)) continue;
                        $subgroup_stats[$sg_id] = [
                            'id' => $sg_id,
                            'title' => $sg['title'] ?? $sg_id,
                            'short_name' => $sg['short_name'] ?? $sg_id,
                            'count' => 0,
                            'debit' => 0.0
                        ];
                    }
                }
            }

            $cr_bd_map = [];
            $sss_bd_map = [];
            if (!empty($vns_to_detect)) {
                $safe_det_vns = "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $vns_to_detect)) . "'";
                $q_cr_bd = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_cr_breakdown WHERE visit_type = 'OPD' AND vn IN ($safe_det_vns)");
                if ($q_cr_bd) {
                    while ($bd = mysqli_fetch_assoc($q_cr_bd)) {
                        $cr_bd_map[$bd['vn']] = $bd;
                    }
                }
                $q_sss_bd = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_sss_breakdown WHERE visit_type = 'OPD' AND vn IN ($safe_det_vns)");
                if ($q_sss_bd) {
                    while ($bd = mysqli_fetch_assoc($q_sss_bd)) {
                        $sss_bd_map[$bd['vn']] = $bd;
                    }
                }
            }

            $all_display_rows = [];

            foreach ($raw_rows as $row) {
                $vn = $row['vn'];
                $row['income_original'] = floatval(cleanNum($row['income']));
                $row['incomediff_original'] = floatval(cleanNum($row['incomediff']));
                $is_transferred_visit = isset($all_cr_visits[$vn]);
                $is_sss_transferred_visit = isset($all_sss_visits[$vn]);

                if ($is_target_216 && !empty($all_cr_visits)) {
                    if ($is_transferred_visit) {
                        $vinfo = $all_cr_visits[$vn];
                        if (!empty($vinfo['is_kidney'])) continue;
                        $row['debit'] = $vinfo['cr_amount'];
                        $row['income'] = $vinfo['cr_amount'];
                        $row['incomediff'] = 0.0;
                        $row['cr_origin_acc'] = $vinfo['origin_accountcode'] ?? ($vinfo['origin_account'] ?? '1102050101.201');
                        $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                        $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];

                        if (isset($cr_bd_map[$vn])) {
                            $row['follow_money'] = cleanNum($cr_bd_map[$vn]['compensated']);
                            $row['bill'] = $cr_bd_map[$vn]['bill'];
                            $row['billdate'] = $cr_bd_map[$vn]['billdate'];
                            if (!empty($cr_bd_map[$vn]['bill'])) $row['bookno'] = $cr_bd_map[$vn]['bill'];
                        } else {
                            $row['follow_money'] = min(cleanNum($row['follow_money']), floatval($vinfo['cr_amount']));
                        }
                    } else {
                        $row['cr_origin_acc'] = '1102050101.216';
                        $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                        $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];
                        $v_month = !empty($row['monthtxt']) ? $row['monthtxt'] : '';
                        if (empty($v_month) && !empty($row['vstdate'])) {
                            $p_v = explode('/', $row['vstdate']);
                            if (count($p_v) === 3) {
                                $y = intval($p_v[2]);
                                if ($y > 2400) $y -= 543;
                                $v_month = intval($p_v[1]) . '-' . $y;
                            }
                        }
                        if (is_cr_effective_for_month($cr_config, $v_month) && isset($cr_bd_map[$vn])) {
                            $row['follow_money'] = cleanNum($cr_bd_map[$vn]['compensated']);
                            $row['bill'] = $cr_bd_map[$vn]['bill'];
                            $row['billdate'] = $cr_bd_map[$vn]['billdate'];
                            if (!empty($cr_bd_map[$vn]['bill'])) $row['bookno'] = $cr_bd_map[$vn]['bill'];
                        }
                    }
                    $all_display_rows[] = $row;

                } elseif ($is_target_309 && !empty($all_sss_visits)) {
                    if ($is_sss_transferred_visit) {
                        $vinfo = $all_sss_visits[$vn];
                        $row['debit'] = $vinfo['sss_amount'];
                        $row['income'] = $vinfo['sss_amount'];
                        $row['incomediff'] = 0.0;
                        $row['sss_origin_acc'] = $vinfo['origin_accountcode'] ?? '1102050101.301';
                        $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];

                        if (isset($sss_bd_map[$vn])) {
                            $row['follow_money'] = cleanNum($sss_bd_map[$vn]['compensated']);
                            $row['bill'] = $sss_bd_map[$vn]['bill'];
                            $row['billdate'] = $sss_bd_map[$vn]['billdate'];
                            if (!empty($sss_bd_map[$vn]['bill'])) $row['bookno'] = $sss_bd_map[$vn]['bill'];
                        } else {
                            $row['follow_money'] = min(cleanNum($row['follow_money']), floatval($vinfo['sss_amount']));
                        }
                    } else {
                        $row['sss_origin_acc'] = '1102050101.309';
                        $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];
                        $v_month = !empty($row['monthtxt']) ? $row['monthtxt'] : '';
                        if (empty($v_month) && !empty($row['vstdate'])) {
                            $p_v = explode('/', $row['vstdate']);
                            if (count($p_v) === 3) {
                                $y = intval($p_v[2]);
                                if ($y > 2400) $y -= 543;
                                $v_month = intval($p_v[1]) . '-' . $y;
                            }
                        }
                        if (is_sss_effective_for_month($sss_config, $v_month) && isset($sss_bd_map[$vn])) {
                            $row['follow_money'] = cleanNum($sss_bd_map[$vn]['compensated']);
                            $row['bill'] = $sss_bd_map[$vn]['bill'];
                            $row['billdate'] = $sss_bd_map[$vn]['billdate'];
                            if (!empty($sss_bd_map[$vn]['bill'])) $row['bookno'] = $sss_bd_map[$vn]['bill'];
                        }
                    }
                    $all_display_rows[] = $row;

                } elseif (!empty($all_cr_visits) && !$is_kidney_click && $is_cr_parent_opd) {
                    if ($is_transferred_visit) {
                        $vinfo = $all_cr_visits[$vn];
                        if ($vinfo['is_full_transfer']) {
                            continue;
                        } else {
                            $row['debit'] = $vinfo['general_remain_amount'];
                            $row['cr_transferred_out'] = $vinfo['cr_amount'];
                            $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                            $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];

                            // หักเงินชดเชยที่โอนไปผังลูกออก
                            $cr_comp = isset($cr_bd_map[$vn]) ? cleanNum($cr_bd_map[$vn]['compensated']) : min(cleanNum($row['follow_money']), floatval($vinfo['cr_amount']));
                            $mother_comp = max(0, cleanNum($row['follow_money']) - $cr_comp);
                            $row['follow_money'] = $mother_comp;

                            // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                            if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                                $row['bill'] = '';
                                $row['billdate'] = '';
                                if ($row['bookno'] == ($cr_bd_map[$vn]['bill'] ?? '') || $row['bookno'] == ($vinfo['bill'] ?? '')) {
                                    $row['bookno'] = '';
                                }
                            }
                            $all_display_rows[] = $row;
                        }
                    } else {
                        $all_display_rows[] = $row;
                    }
                } elseif (!empty($all_sss_visits) && !$is_kidney_click && $is_sss_parent_opd) {
                    if ($is_sss_transferred_visit) {
                        $vinfo = $all_sss_visits[$vn];
                        if ($vinfo['is_full_transfer']) {
                            continue;
                        } else {
                            $row['debit'] = $vinfo['general_remain_amount'];
                            $row['sss_transferred_out'] = $vinfo['sss_amount'];
                            $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                            $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];

                            // หักเงินชดเชยที่โอนไปผังลูกออก
                            $sss_comp = isset($sss_bd_map[$vn]) ? cleanNum($sss_bd_map[$vn]['compensated']) : min(cleanNum($row['follow_money']), floatval($vinfo['sss_amount']));
                            $mother_comp = max(0, cleanNum($row['follow_money']) - $sss_comp);
                            $row['follow_money'] = $mother_comp;

                            if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                                $row['bill'] = '';
                                $row['billdate'] = '';
                                if ($row['bookno'] == ($sss_bd_map[$vn]['bill'] ?? '') || $row['bookno'] == ($vinfo['bill'] ?? '')) {
                                    $row['bookno'] = '';
                                }
                            }
                            $all_display_rows[] = $row;
                        }
                    } else {
                        $all_display_rows[] = $row;
                    }
                } else {
                    $all_display_rows[] = $row;
                }
            }

            echo '<div class="table-scroll">';
            echo '<table class="table table-striped table-bordered stmgetovlookup" border="1">
                      <thead>
                        <tr>
                            <th style="font-weight: bold; background-color: #188b13; color: #fff; vertical-align: middle;" colspan="15">
                                📑 ตารางแจกแจงรายการลูกหนี้รายคน
                            </th>
                        </tr>
                        <tr style="font-weight: bold;">
                          <th class="sortable-header" data-column="0">ลำดับ</th>
                          <th class="sortable-header" data-column="1">เลขหนังสือ/บิล</th>
                          <th class="sortable-header" data-column="2">VN</th>
                          <th class="sortable-header" data-column="3">วันที่รับบริการ</th>
                          <th class="sortable-header" data-column="4">CID</th>
                          <th class="sortable-header" data-column="5">ชื่อผู้ป่วย</th>
                          <th class="sortable-header" data-column="6">สิทธิ Hos</th>
                          <th class="sortable-header" data-column="7">ระยะเวลาหนี้</th>
                          <th class="sortable-header" data-column="8">ค่าใช้จ่าย</th>
                          <th class="sortable-header" data-column="9">ชำระแล้ว</th>
                          <th class="sortable-header" data-column="10">ภาระหนี้</th>
                          <th class="sortable-header" data-column="11">ชดเชย STM</th>
                          <th class="sortable-header" data-column="12">ชดเชย DCKD</th>
                          <th class="sortable-header" data-column="13">ส่วนต่าง</th>
                          <th class="sortable-header" data-column="14">Hospmain</th>
                        </tr>
                      </thead>
                      <tbody id="data_tabel_listview" style="font-size: 14px;">';

            $i = 0;
            $srcpt_money = 0;
            $income_money = 0;
            $income_moneyd = 0;
            $stm_total = 0;
            $seamless_dckd = 0;
            $diff_total = 0;

            $parseThaiDate = function($dateStr) {
                return parseThaiDate($dateStr);
            };

            foreach ($all_display_rows as $row) {
                $i++;
                $vn = $row['vn'];
                if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
                if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);

                $is_row_kidney = ($is_kidney_click || strpos($row["pttypename"], 'ฟอกไต') !== false || strpos($row["pttypename"], 'ไต') !== false);
                $row_color = $is_row_kidney ? 'style="color: #cd641f;"' : '';

                $seamless_dckd_total = cleanNum($row["ckd_compensated"] ?? 0) + cleanNum($row["ofc_compensated"] ?? 0);
                $isMissingAll = empty($row["bookno"]) && empty($row["compensated"]) && empty($seamless_dckd_total);
                $style = $isMissingAll ? 'style="color:red;"' : '';
                if ($row["debit"] == '0') {
                    $style = 'style="color:#000;"';
                }
                if ($is_row_kidney) {
                    $style = 'style="color: #cd641f;"';
                }

                // --- ส่วนคำนวณวันค้างชำระ (Aging) ---
                $days_count = 0;
                $status_text = " วัน";
                if (!empty($row["vstdate"])) {
                    $startDate = $parseThaiDate($row["vstdate"]);
                    $endDate = new DateTime();
                    if (!empty($row['billdate'])) {
                        $parsed = $parseThaiDate($row['billdate']);
                        if ($parsed) $endDate = $parsed;
                    } elseif (!empty($row['mobile'])) {
                        $parsed = $parseThaiDate($row['mobile']);
                        if ($parsed) $endDate = $parsed;
                    }
                    if ($startDate) {
                        $diff = $startDate->diff($endDate);
                        $days_count = $diff->days;
                    }
                }

                // คำนวณค่าชดเชย STM
                $stm_compensation_value = !empty($row["follow_money"]) ? cleanNum($row["follow_money"]) : cleanNum($row["compensated"] ?? 0);


                $diff_amount = cleanNum($row["debit"]) - cleanNum($stm_compensation_value) - cleanNum($seamless_dckd_total);

                // 💡 Hospmain lookup (ดึงของ Visit นั้นโดยตรง เพื่อความถูกต้องสูงสุด)
                $hosp_detail = "-";
                $current_vn = mysqli_real_escape_string($conn2, $row["vn"]);
                $sql_hosp = "SELECT CONCAT(o.hospmain, ' (', ho.`name`, ')') AS hosp_detail
                                FROM ovst o
                                LEFT JOIN hospcode ho ON ho.hospcode = o.hospmain
                                WHERE o.vn = '$current_vn'
                                AND o.hospmain IS NOT NULL
                                AND o.hospmain <> ''
                                LIMIT 1;";
                $result_hosp = mysqli_query($conn2, $sql_hosp);
                if ($result_hosp && mysqli_num_rows($result_hosp) > 0) {
                    $row_hosp = mysqli_fetch_assoc($result_hosp);
                    $hosp_detail = $row_hosp["hosp_detail"];
                } else {
                    // หากไม่มีข้อมูลใน Visit ให้ค้นหาจาก Visit ล่าสุดของคนไข้
                    $sql_hosp_fb = "SELECT CONCAT(o.hospmain, ' (', ho.`name`, ')') AS hosp_detail
                                    FROM ovst o
                                    LEFT JOIN hospcode ho ON ho.hospcode = o.hospmain
                                    WHERE o.hn = (
                                        SELECT hn
                                        FROM ovst
                                        WHERE vn = '$current_vn'
                                        LIMIT 1
                                    )
                                    AND o.hospmain IS NOT NULL
                                    AND o.hospmain <> ''
                                    ORDER BY o.vstdate DESC, o.vsttime DESC
                                    LIMIT 1;";
                    $result_hosp_fb = mysqli_query($conn2, $sql_hosp_fb);
                    if ($result_hosp_fb && mysqli_num_rows($result_hosp_fb) > 0) {
                        $row_hosp_fb = mysqli_fetch_assoc($result_hosp_fb);
                        $hosp_detail = $row_hosp_fb["hosp_detail"];
                    }
                }

                $cr_types = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : (isset($row['cr_types']) ? $row['cr_types'] : []);
                $cr_items = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : (isset($row['items']) ? $row['items'] : []);
                $sss_types = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
                $sss_items = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

                $all_subtypes = array_unique(array_merge($cr_types, $sss_types));
                $cr_type_str = implode(',', $all_subtypes);

                // 🌟 คำนวณยอดเงินแยกตามกลุ่มย่อย CR จากรายการ items จริง เพื่อป้องกันการปนกันของยอดเงิน
                $cr_subgroup_amounts = [];
                if (!empty($cr_items)) {
                    foreach ($cr_items as $cit) {
                        $ctype = trim((string)($cit['cr_type'] ?? ''));
                        if (!empty($ctype)) {
                            if (!isset($cr_subgroup_amounts[$ctype])) {
                                $cr_subgroup_amounts[$ctype] = 0.0;
                            }
                            $cr_subgroup_amounts[$ctype] += floatval(cleanNum($cit['amount'] ?? 0));
                        }
                    }
                }
                if (!empty($cr_types)) {
                    $allocated_sum = array_sum($cr_subgroup_amounts);
                    $unallocated = max(0, floatval(cleanNum($row['debit'])) - $allocated_sum);
                    $zero_types = [];
                    foreach ($cr_types as $ct) {
                        if (!isset($cr_subgroup_amounts[$ct]) || $cr_subgroup_amounts[$ct] <= 0.001) {
                            $zero_types[] = $ct;
                        }
                    }
                    if ($unallocated > 0 && !empty($zero_types)) {
                        $each_amt = $unallocated / count($zero_types);
                        foreach ($zero_types as $zt) {
                            $cr_subgroup_amounts[$zt] = $each_amt;
                        }
                    }
                }

                // คำนวณยอดเงินแยกตามกลุ่มย่อย SSS
                $sss_subgroup_amounts = [];
                if (!empty($sss_items)) {
                    foreach ($sss_items as $sit) {
                        $stype = trim((string)($sit['cr_type'] ?? ''));
                        if (!empty($stype)) {
                            if (!isset($sss_subgroup_amounts[$stype])) {
                                $sss_subgroup_amounts[$stype] = 0.0;
                            }
                            $sss_subgroup_amounts[$stype] += floatval(cleanNum($sit['amount'] ?? 0));
                        }
                    }
                }
                if (!empty($sss_types)) {
                    $allocated_sum_s = array_sum($sss_subgroup_amounts);
                    $unallocated_s = max(0, floatval(cleanNum($row['debit'])) - $allocated_sum_s);
                    $zero_types_s = [];
                    foreach ($sss_types as $st) {
                        if (!isset($sss_subgroup_amounts[$st]) || $sss_subgroup_amounts[$st] <= 0.001) {
                            $zero_types_s[] = $st;
                        }
                    }
                    if ($unallocated_s > 0 && !empty($zero_types_s)) {
                        $each_amt_s = $unallocated_s / count($zero_types_s);
                        foreach ($zero_types_s as $zs) {
                            $sss_subgroup_amounts[$zs] = $each_amt_s;
                        }
                    }
                }

                if ($is_cr_target_account_opd || $is_cr_parent_opd) {
                    $subgroup_stats['ALL']['count']++;
                    $subgroup_stats['ALL']['debit'] += cleanNum($row['debit']);
                    foreach ($cr_types as $ct) {
                        if (isset($subgroup_stats[$ct])) {
                            $subgroup_stats[$ct]['count']++;
                            $sg_amt = isset($cr_subgroup_amounts[$ct]) && $cr_subgroup_amounts[$ct] > 0 
                                ? $cr_subgroup_amounts[$ct] 
                                : cleanNum($row['debit']);
                            $subgroup_stats[$ct]['debit'] += $sg_amt;
                        }
                    }
                } elseif ($is_sss_target_account_opd || $is_sss_parent_opd) {
                    $subgroup_stats['ALL']['count']++;
                    $subgroup_stats['ALL']['debit'] += cleanNum($row['debit']);
                    foreach ($sss_types as $st) {
                        if (isset($subgroup_stats[$st])) {
                            $subgroup_stats[$st]['count']++;
                            $sg_amt = isset($sss_subgroup_amounts[$st]) && $sss_subgroup_amounts[$st] > 0 
                                ? $sss_subgroup_amounts[$st] 
                                : cleanNum($row['debit']);
                            $subgroup_stats[$st]['debit'] += $sg_amt;
                        }
                    }
                }

                $cr_badge_html = '';
                if ($is_cr_target_account_opd || $is_cr_parent_opd) {
                    $original_debit_display = floatval(cleanNum($row['debit']));
                    $cr_amt_display = floatval(cleanNum($row['debit']));
                    $remain_debit_display = 0.0;

                    if ($is_cr_parent_opd) {
                        $cr_amt_display = floatval(cleanNum($row['cr_transferred_out'] ?? 0));
                        $original_debit_display = $original_debit_display + $cr_amt_display;
                        $remain_debit_display = floatval(cleanNum($row['debit']));
                    } elseif ($is_cr_target_account_opd && isset($all_cr_visits[$vn])) {
                        $vinfo = $all_cr_visits[$vn];
                        $original_debit_display = floatval(cleanNum($vinfo['general_remain_amount'] + $vinfo['cr_amount']));
                        $cr_amt_display = floatval(cleanNum($vinfo['cr_amount']));
                        $remain_debit_display = max(0, $original_debit_display - $cr_amt_display);
                    }

                    $cr_payload_data = [
                        'vn' => (string)$row['vn'],
                        'hn' => (string)($row['hn'] ?? ''),
                        'ptname' => (string)($row['ptname'] ?? ''),
                        'vstdate' => (string)($row['vstdate'] ?? ''),
                        'pttypename' => (string)($row['pttypename'] ?? ''),
                        'hospmain' => (string)$hosp_detail,
                        'income' => floatval(cleanNum($row['income_original'] ?? $row['income'])),
                        'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                        'debit' => $original_debit_display,
                        'origin_acc' => (string)($row['cr_origin_acc'] ?? ($is_cr_target_account_opd ? '1102050101.216' : $stmget)),
                        'target_acc' => '1102050101.216',
                        'cr_types' => $cr_types,
                        'cr_amount' => $cr_amt_display,
                        'remain_debit' => $remain_debit_display,
                        'items' => $cr_items
                    ];
                    $payload_json_str = htmlspecialchars(json_encode($cr_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                    foreach ($cr_types as $ct) {
                        $ct_amt = isset($cr_subgroup_amounts[$ct]) ? floatval($cr_subgroup_amounts[$ct]) : 0;
                        $ct_amt_label = ($ct_amt > 0) ? ' (฿' . number_format($ct_amt, 2) . ')' : '';
                        $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge" data-cr-subgroup="' . htmlspecialchars($ct) . '" data-filter-subgroup="' . htmlspecialchars($ct) . '" style="font-size:11px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'' . htmlspecialchars($ct, ENT_QUOTES) . '\')" title="คลิกดูรายการเฉพาะกลุ่ม ' . htmlspecialchars($ct) . $ct_amt_label . '">' . htmlspecialchars($ct) . '</span>';
                    }

                    if (!empty($row['cr_origin_acc']) && $row['cr_origin_acc'] !== '1102050101.216') {
                        $orig_short = str_replace('1102050101.', '.', $row['cr_origin_acc']);
                        $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'ALL\')" title="โอนมาจากผัง ' . htmlspecialchars($row['cr_origin_acc']) . ' (คลิกเพื่อดูทุกกลุ่มย่อยที่โอนมา)"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                    } elseif (!empty($row['cr_transferred_out']) && $row['cr_transferred_out'] > 0) {
                        $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'ALL\')" title="ตัดยอด CR ออกไปผัง .216 จำนวน ฿' . number_format($row['cr_transferred_out'], 2) . ' (คลิกเพื่อดูรายการ)"><i class="bx bx-cut"></i> ตัด CR -฿' . number_format($row['cr_transferred_out'], 2) . '</span>';
                    }
                } elseif ($is_sss_target_account_opd || $is_sss_parent_opd) {
                    $original_debit_display = floatval(cleanNum($row['debit']));
                    $sss_amt_display = floatval(cleanNum($row['debit']));
                    $remain_debit_display = 0.0;

                    if ($is_sss_parent_opd) {
                        $sss_amt_display = floatval(cleanNum($row['sss_transferred_out'] ?? 0));
                        $original_debit_display = $original_debit_display + $sss_amt_display;
                        $remain_debit_display = floatval(cleanNum($row['debit']));
                    } elseif ($is_sss_target_account_opd && isset($all_sss_visits[$vn])) {
                        $vinfo = $all_sss_visits[$vn];
                        $original_debit_display = floatval(cleanNum($vinfo['general_remain_amount'] + $vinfo['sss_amount']));
                        $sss_amt_display = floatval(cleanNum($vinfo['sss_amount']));
                        $remain_debit_display = max(0, $original_debit_display - $sss_amt_display);
                    }

                    $sss_payload_data = [
                        'vn' => (string)$row['vn'],
                        'hn' => (string)($row['hn'] ?? ''),
                        'ptname' => (string)($row['ptname'] ?? ''),
                        'vstdate' => (string)($row['vstdate'] ?? ''),
                        'pttypename' => (string)($row['pttypename'] ?? ''),
                        'hospmain' => (string)$hosp_detail,
                        'income' => floatval(cleanNum($row['income_original'] ?? $row['income'])),
                        'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                        'debit' => $original_debit_display,
                        'origin_acc' => (string)($row['sss_origin_acc'] ?? ($is_sss_target_account_opd ? '1102050101.309' : $stmget)),
                        'target_acc' => '1102050101.309',
                        'sss_types' => $sss_types,
                        'sss_amount' => $sss_amt_display,
                        'remain_debit' => $remain_debit_display,
                        'items' => $sss_items
                    ];
                    $payload_json_str = htmlspecialchars(json_encode($sss_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                    foreach ($sss_types as $st) {
                        $st_amt = isset($sss_subgroup_amounts[$st]) ? floatval($sss_subgroup_amounts[$st]) : 0;
                        $st_amt_label = ($st_amt > 0) ? ' (฿' . number_format($st_amt, 2) . ')' : '';
                        $cr_badge_html .= ' <span class="badge bg-label-success font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge" data-cr-subgroup="' . htmlspecialchars($st) . '" data-filter-subgroup="' . htmlspecialchars($st) . '" style="font-size:11px; cursor:pointer;" data-sss-payload="' . $payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="คลิกดูรายการอุปกรณ์ SSS ' . htmlspecialchars($st) . $st_amt_label . '">' . htmlspecialchars($st) . '</span>';
                    }

                    if (!empty($row['sss_origin_acc']) && $row['sss_origin_acc'] !== '1102050101.309') {
                        $orig_short = str_replace('1102050101.', '.', $row['sss_origin_acc']);
                        $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="โอนมาจากผัง ' . htmlspecialchars($row['sss_origin_acc']) . ' (คลิกเพื่อดูรายการ)"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                    } elseif (!empty($row['sss_transferred_out']) && $row['sss_transferred_out'] > 0) {
                        $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="ตัดยอด SSS ออกไปผัง .309 จำนวน ฿' . number_format($row['sss_transferred_out'], 2) . ' (คลิกเพื่อดูรายการ)"><i class="bx bx-cut"></i> ตัด SSS -฿' . number_format($row['sss_transferred_out'], 2) . '</span>';
                    }
                }

                $all_subgroup_amounts = array_merge($cr_subgroup_amounts, $sss_subgroup_amounts);
                $amounts_json = htmlspecialchars(json_encode($all_subgroup_amounts, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                echo '<tr class="main-patient-row" data-cr-type="' . htmlspecialchars($cr_type_str) . '" data-cr-amounts="' . $amounts_json . '" data-full-debit="' . floatval(cleanNum($row['debit'])) . '">';
                echo "<td class='text-center' $style>" . $i . "</td>";
                echo "<td class='text-center' $style>" . htmlspecialchars($row["bookno"] ?? ($row["bill"] ?? '')) . "</td>";
                echo "<td class='text-center fw-semibold' $style>" . htmlspecialchars($row["vn"]) . "</td>";
                echo "<td class='text-center' $style>" . htmlspecialchars($row["vstdate"]) . "</td>";
                echo "<td class='text-center' $style>" . htmlspecialchars($row["cid"]) . "</td>";
                echo "<td class='text-start' $style>" . htmlspecialchars($row["ptname"]) . $cr_badge_html . "</td>";
                echo "<td class='text-start' $style>" . htmlspecialchars($row["pttypename"]) . "</td>";
                echo "<td class='text-center' $style>" . number_format($days_count) . $status_text . "</td>";

                echo "<td class='text-end' $style>" . formatMoney($row["income"], 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($row["incomediff"], 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($row["debit"], 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($stm_compensation_value, 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($seamless_dckd_total, 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($diff_amount, 2) . "</td>";

                echo "<td class='text-start' $style>" . htmlspecialchars($hosp_detail) . "</td>";
                echo "</tr>";

                $srcpt_money += cleanNum($row["debit"]);
                $income_money += cleanNum($row["income"]);
                $income_moneyd += cleanNum($row["incomediff"]);
                $stm_total += cleanNum($stm_compensation_value);
                $seamless_dckd += cleanNum($seamless_dckd_total);
                $diff_total += cleanNum($diff_amount);
            }

            echo '<span id="total_patient_count" data-count="'.$i.'" style="display:none;"></span>';
            echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            echo '</tbody>';

            if (!empty($all_display_rows)) {
                echo '<tfoot>
                    <tr class="summary-total-row" style="background: linear-gradient(268deg, #2a3d5d 0%, #1e2b4b 100%) !important; color: #ffffff !important;">
                        <td colspan="8" class="text-center fw-bold" style="background: transparent !important; color: #ffffff !important;">รวมทั้งสิ้น</td>
                        <td class="text-end fw-bold" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($income_money, 2) . '</td>
                        <td class="text-end fw-bold" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($income_moneyd, 2) . '</td>
                        <td class="text-end fw-bold" id="o1" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($srcpt_money, 2) . '</td>
                        <td class="text-end fw-bold" id="o2" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($stm_total, 2) . '</td>
                        <td class="text-end fw-bold" id="o4" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($seamless_dckd, 2) . '</td>
                        <td class="text-end fw-bold" id="o3" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($diff_total, 2) . '</td>
                        <td style="background: transparent !important; color: #ffffff !important;"></td>
                    </tr>
                </tfoot>';
            }
            echo '</table></div>';
            if ($is_cr_target_account_opd || $is_cr_parent_opd || $is_sss_target_account_opd || $is_sss_parent_opd) {
                echo '<script id="cr_subgroup_summary_json" type="application/json">' . json_encode($subgroup_stats, JSON_UNESCAPED_UNICODE) . '</script>';
            } else {
                echo '<script id="cr_subgroup_summary_json" type="application/json">{}</script>';
            }
        }

        if(isset($_POST['stmgeti'])){

            $stmget = mysqli_real_escape_string($conn, $_POST['stmgeti']);
            $mn = mysqli_real_escape_string($conn, $_POST['yyy']);

            $start_date_ipd = isset($_POST['start_date_ipd']) ? mysqli_real_escape_string($conn, $_POST['start_date_ipd']) : '';
            $end_date_ipd = isset($_POST['end_date_ipd']) ? mysqli_real_escape_string($conn, $_POST['end_date_ipd']) : '';

            $has_date_range_ipd = (!empty($start_date_ipd) && !empty($end_date_ipd));
            $date_condition_stm_i = "";
            $months_to_process_ipd = [$mn];

            if ($has_date_range_ipd) {
                $range_info_ipd = parseToThaiDateRange($start_date_ipd, $end_date_ipd);
                if ($range_info_ipd) {
                    $start_date_th_stm_i = $range_info_ipd['start_th'];
                    $end_date_th_stm_i = $range_info_ipd['end_th'];

                    $date_condition_stm_i = " STR_TO_DATE(o.dchdate, '%d/%m/%Y') BETWEEN '$start_date_th_stm_i' AND '$end_date_th_stm_i' ";
                    $months_to_process_ipd = getMonthsInRange($start_date_ipd, $end_date_ipd);
                } else {
                    $has_date_range_ipd = false;
                }
            }

            if (!$has_date_range_ipd) {
                $date_condition_stm_i = " o.monthtxt = '$mn' ";
            }

            // 1. ดึงการตั้งค่า CR Splitting สำหรับ IPD
            $cr_config = get_active_cr_config($conn);
            $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
            $auto_split_ipd = is_cr_auto_split_enabled($cr_config, 'IPD');
            $all_cr_visits_ipd = [];
            $is_cr_effective_ipd = false;

            // 1.1 ดึงการตั้งค่า SSS Splitting สำหรับ IPD
            $sss_config = get_active_sss_config($conn);
            $auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config, 'IPD');
            $all_sss_visits_ipd = [];
            $is_sss_effective_ipd = false;

            foreach ($months_to_process_ipd as $m_stm_i) {
                if (is_cr_effective_for_month($cr_config, $m_stm_i) && $auto_split_ipd) {
                    $is_cr_effective_ipd = true;
                    $an_filter_cr = null;
                    if ($has_date_range_ipd) {
                        $ans_q = mysqli_query($conn, "SELECT an FROM imr_tb_debtor_rights_ipd o WHERE monthtxt = '$m_stm_i' AND $date_condition_stm_i AND accountcode IN ('1102050101.202', '1102050101.217')");
                        $an_filter_cr = [];
                        if ($ans_q) {
                            while ($ar = mysqli_fetch_assoc($ans_q)) {
                                $an_filter_cr[] = $ar['an'];
                            }
                        }
                    }
                    if (!$has_date_range_ipd || !empty($an_filter_cr)) {
                        $s_ipd = get_cr_splitting_summary_for_month($conn, $conn2, $m_stm_i, 'IPD', $cr_config, $my_hospcode, $an_filter_cr);
                        if ($s_ipd && !empty($s_ipd['cr_visits'])) {
                            foreach ($s_ipd['cr_visits'] as $v_k => $v_v) {
                                $all_cr_visits_ipd[$v_k] = $v_v;
                            }
                        }
                    }
                }

                if (is_sss_effective_for_month($sss_config, $m_stm_i) && $auto_split_sss_ipd) {
                    $is_sss_effective_ipd = true;
                    $an_filter_sss = null;
                    if ($has_date_range_ipd) {
                        $ans_s_q = mysqli_query($conn, "SELECT an FROM imr_tb_debtor_rights_ipd o WHERE monthtxt = '$m_stm_i' AND $date_condition_stm_i AND accountcode IN ('1102050101.302', '1102050101.304', '1102050101.310')");
                        $an_filter_sss = [];
                        if ($ans_s_q) {
                            while ($ar = mysqli_fetch_assoc($ans_s_q)) {
                                $an_filter_sss[] = $ar['an'];
                            }
                        }
                    }
                    if (!$has_date_range_ipd || !empty($an_filter_sss)) {
                        $s_sss_ipd = get_sss_splitting_summary_for_month($conn, $conn2, $m_stm_i, 'IPD', $sss_config, $my_hospcode, $an_filter_sss);
                        if ($s_sss_ipd && !empty($s_sss_ipd['sss_visits'])) {
                            foreach ($s_sss_ipd['sss_visits'] as $v_k => $v_v) {
                                $all_sss_visits_ipd[$v_k] = $v_v;
                            }
                        }
                    }
                }
            }

            $is_kidney_click_ipd = (strpos($stmget, '_KIDNEY') !== false);
            $is_cr_target_217 = ($stmget === '1102050101.217' || (strpos($stmget, '1102050101.217') !== false && !$is_kidney_click_ipd));
            $is_sss_target_310 = ($stmget === '1102050101.310' || (strpos($stmget, '1102050101.310') !== false && !$is_kidney_click_ipd));
            $base_stmget = explode('_', $stmget)[0];
            $is_cr_parent_ipd = $is_cr_effective_ipd && $auto_split_ipd && in_array($base_stmget, ['1102050101.202']) && is_cr_parent_account_enabled($cr_config, $base_stmget) && !$is_kidney_click_ipd;
            $is_sss_parent_ipd = $is_sss_effective_ipd && $auto_split_sss_ipd && in_array($base_stmget, ['1102050101.302', '1102050101.304']) && is_sss_parent_account_enabled($sss_config, $base_stmget) && !$is_kidney_click_ipd;

            if ($is_kidney_click_ipd) {
                $base_account = str_replace('_KIDNEY', '', $stmget);
                $condition_ipd = "o.accountcode = '$base_account' AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%')";
            } else {
                if ($is_cr_target_217 && !empty($all_cr_visits_ipd)) {
                    $transfer_ans = array_keys($all_cr_visits_ipd);
                    $transfer_ans_quoted = !empty($transfer_ans) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_ans)) . "'" : "''";
                    $condition_ipd = "((o.accountcode = '1102050101.217') OR (o.an IN ($transfer_ans_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
                } elseif ($is_sss_target_310 && !empty($all_sss_visits_ipd)) {
                    $transfer_sss_ans = array_keys($all_sss_visits_ipd);
                    $transfer_sss_ans_quoted = !empty($transfer_sss_ans) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_sss_ans)) . "'" : "''";
                    $condition_ipd = "((o.accountcode = '1102050101.310') OR (o.an IN ($transfer_sss_ans_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
                } else {
                    $condition_ipd = "o.accountcode = '$stmget' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
                }
            }

            $sql = "SELECT
                        COALESCE(stm.rep, o.bill) AS rep,
                        o.accountcode, o.accountname,
                        o.an, o.hn, o.dchdate, o.cid, o.ptname,
                        o.uc_money, o.debit, o.income, o.admdate,
                        stm.compensated,
                        (o.uc_money - COALESCE(stm.compensated, 0)) as diff,
                        (o.income - COALESCE(o.original_debit, o.debit)) as incomediff,
                        o.billdate, o.mobile, o.pttypename, o.follow_money, o.monthtxt
                    FROM imr_tb_debtor_rights_ipd o
                    LEFT JOIN imr_tb_check_invoice stm on stm.vn = o.an
                    WHERE $condition_ipd AND $date_condition_stm_i
                    GROUP BY o.an
                    ORDER BY o.accountcode ASC, o.dchdate ASC";

            $result = mysqli_query($conn, $sql);
            $raw_rows_ipd = [];
            $ans_to_detect = [];
            if ($result && mysqli_num_rows($result) > 0) {
                while ($r = mysqli_fetch_assoc($result)) {
                    $raw_rows_ipd[] = $r;
                    if (!empty($r['an'])) $ans_to_detect[] = $r['an'];
                }
            }

            $is_cr_target_account_ipd = $is_cr_effective_ipd && $auto_split_ipd && ($is_cr_target_217 || strpos($stmget, '1102050101.217') !== false) && !$is_kidney_click_ipd;
            $is_sss_target_account_ipd = $is_sss_effective_ipd && $auto_split_sss_ipd && ($is_sss_target_310 || strpos($stmget, '1102050101.310') !== false) && !$is_kidney_click_ipd;

            $cr_map_ipd = [];
            $sss_map_ipd = [];
            $subgroup_stats_ipd = [];

            $check_stm_i = !empty($mn) ? $mn : (!empty($months_to_process_ipd) ? $months_to_process_ipd : null);

            if ($is_cr_target_account_ipd || $is_cr_parent_ipd) {
                if (!empty($ans_to_detect) && $conn2) {
                    $cr_map_ipd = detect_cr_types_for_visit_list($conn2, $ans_to_detect, 'IPD', $cr_config, $my_hospcode, $check_stm_i);
                }

                $subgroup_stats_ipd = [
                    'ALL' => [
                        'id' => 'ALL',
                        'title' => 'ทั้งหมด (All CR)',
                        'short_name' => 'ทั้งหมด',
                        'count' => 0,
                        'debit' => 0.0
                    ]
                ];
                $sg_source_ipd = isset($cr_config['subgroups_ipd']) ? $cr_config['subgroups_ipd'] : [];
                if (!empty($sg_source_ipd)) {
                    foreach ($sg_source_ipd as $sg_id => $sg) {
                        if (!is_cr_subgroup_enabled($cr_config, $sg_id, 'IPD', $check_stm_i)) continue;
                        $subgroup_stats_ipd[$sg_id] = [
                            'id' => $sg_id,
                            'title' => $sg['title'] ?? $sg_id,
                            'short_name' => $sg['short_name'] ?? $sg_id,
                            'count' => 0,
                            'debit' => 0.0
                        ];
                    }
                }
            } elseif ($is_sss_target_account_ipd || $is_sss_parent_ipd) {
                if (!empty($ans_to_detect) && $conn2) {
                    $sss_map_ipd = detect_sss_types_for_visit_list($conn2, $ans_to_detect, 'IPD', $sss_config, $my_hospcode, $check_stm_i);
                }

                $subgroup_stats_ipd = [
                    'ALL' => [
                        'id' => 'ALL',
                        'title' => 'ทั้งหมด (All SSS)',
                        'short_name' => 'ทั้งหมด',
                        'count' => 0,
                        'debit' => 0.0
                    ]
                ];
                $sg_source_sss_ipd = isset($sss_config['subgroups_ipd']) ? $sss_config['subgroups_ipd'] : [];
                if (!empty($sg_source_sss_ipd)) {
                    foreach ($sg_source_sss_ipd as $sg_id => $sg) {
                        if (!is_sss_subgroup_enabled($sss_config, $sg_id, 'IPD', $check_stm_i)) continue;
                        $subgroup_stats_ipd[$sg_id] = [
                            'id' => $sg_id,
                            'title' => $sg['title'] ?? $sg_id,
                            'short_name' => $sg['short_name'] ?? $sg_id,
                            'count' => 0,
                            'debit' => 0.0
                        ];
                    }
                }
            }

            $cr_bd_map_i = [];
            $sss_bd_map_i = [];
            if (!empty($ans_to_detect)) {
                $safe_det_ans = "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $ans_to_detect)) . "'";
                $q_cr_bd_i = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_cr_breakdown WHERE visit_type = 'IPD' AND vn IN ($safe_det_ans)");
                if ($q_cr_bd_i) {
                    while ($bd = mysqli_fetch_assoc($q_cr_bd_i)) {
                        $cr_bd_map_i[$bd['vn']] = $bd;
                    }
                }
                $q_sss_bd_i = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_sss_breakdown WHERE visit_type = 'IPD' AND vn IN ($safe_det_ans)");
                if ($q_sss_bd_i) {
                    while ($bd = mysqli_fetch_assoc($q_sss_bd_i)) {
                        $sss_bd_map_i[$bd['vn']] = $bd;
                    }
                }
            }

            $all_display_rows_ipd = [];

            foreach ($raw_rows_ipd as $row) {
                $an = $row['an'];
                $row['income_original'] = floatval(cleanNum($row['income']));
                $row['incomediff_original'] = floatval(cleanNum($row['incomediff']));
                $is_transferred_visit = isset($all_cr_visits_ipd[$an]);
                $is_sss_transferred_visit = isset($all_sss_visits_ipd[$an]);

                if ($is_cr_target_217 && !empty($all_cr_visits_ipd)) {
                    if ($is_transferred_visit) {
                        $vinfo = $all_cr_visits_ipd[$an];
                        if (!empty($vinfo['is_kidney'])) continue;
                        $row['debit'] = $vinfo['cr_amount'];
                        $row['income'] = $vinfo['cr_amount'];
                        $row['uc_money'] = $vinfo['cr_amount'];
                        $row['incomediff'] = 0.0;
                        $row['cr_origin_acc'] = $vinfo['origin_accountcode'] ?? '1102050101.202';
                        $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                        $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];

                        if (isset($cr_bd_map_i[$an])) {
                            $row['follow_money'] = cleanNum($cr_bd_map_i[$an]['compensated']);
                            $row['compensated'] = cleanNum($cr_bd_map_i[$an]['compensated']);
                            $row['bill'] = $cr_bd_map_i[$an]['bill'];
                            $row['billdate'] = $cr_bd_map_i[$an]['billdate'];
                            if (!empty($cr_bd_map_i[$an]['bill'])) $row['bookno'] = $cr_bd_map_i[$an]['bill'];
                        } else {
                            $row['follow_money'] = min(cleanNum($row['follow_money'] ?? 0), floatval($vinfo['cr_amount']));
                            $row['compensated'] = $row['follow_money'];
                        }
                    } else {
                        $row['cr_origin_acc'] = '1102050101.217';
                        $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                        $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];
                        $v_month_i = !empty($row['monthtxt']) ? $row['monthtxt'] : '';
                        if (empty($v_month_i) && !empty($row['dchdate'])) {
                            $p_v = explode('/', $row['dchdate']);
                            if (count($p_v) === 3) {
                                $y = intval($p_v[2]);
                                if ($y > 2400) $y -= 543;
                                $v_month_i = intval($p_v[1]) . '-' . $y;
                            }
                        }
                        if (is_cr_effective_for_month($cr_config, $v_month_i) && isset($cr_bd_map_i[$an])) {
                            $row['follow_money'] = cleanNum($cr_bd_map_i[$an]['compensated']);
                            $row['compensated'] = cleanNum($cr_bd_map_i[$an]['compensated']);
                            $row['bill'] = $cr_bd_map_i[$an]['bill'];
                            $row['billdate'] = $cr_bd_map_i[$an]['billdate'];
                            if (!empty($cr_bd_map_i[$an]['bill'])) $row['bookno'] = $cr_bd_map_i[$an]['bill'];
                        }
                    }
                    $all_display_rows_ipd[] = $row;

                } elseif ($is_sss_target_310 && !empty($all_sss_visits_ipd)) {
                    if ($is_sss_transferred_visit) {
                        $vinfo = $all_sss_visits_ipd[$an];
                        $row['debit'] = $vinfo['sss_amount'];
                        $row['income'] = $vinfo['sss_amount'];
                        $row['uc_money'] = $vinfo['sss_amount'];
                        $row['incomediff'] = 0.0;
                        $row['sss_origin_acc'] = $vinfo['origin_accountcode'] ?? '1102050101.302';
                        $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];

                        if (isset($sss_bd_map_i[$an])) {
                            $row['follow_money'] = cleanNum($sss_bd_map_i[$an]['compensated']);
                            $row['compensated'] = cleanNum($sss_bd_map_i[$an]['compensated']);
                            $row['bill'] = $sss_bd_map_i[$an]['bill'];
                            $row['billdate'] = $sss_bd_map_i[$an]['billdate'];
                            if (!empty($sss_bd_map_i[$an]['bill'])) $row['bookno'] = $sss_bd_map_i[$an]['bill'];
                        } else {
                            $row['follow_money'] = min(cleanNum($row['follow_money'] ?? 0), floatval($vinfo['sss_amount']));
                            $row['compensated'] = $row['follow_money'];
                        }
                    } else {
                        $row['sss_origin_acc'] = '1102050101.310';
                        $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];
                        $v_month_i = !empty($row['monthtxt']) ? $row['monthtxt'] : '';
                        if (empty($v_month_i) && !empty($row['dchdate'])) {
                            $p_v = explode('/', $row['dchdate']);
                            if (count($p_v) === 3) {
                                $y = intval($p_v[2]);
                                if ($y > 2400) $y -= 543;
                                $v_month_i = intval($p_v[1]) . '-' . $y;
                            }
                        }
                        if (is_sss_effective_for_month($sss_config, $v_month_i) && isset($sss_bd_map_i[$an])) {
                            $row['follow_money'] = cleanNum($sss_bd_map_i[$an]['compensated']);
                            $row['compensated'] = cleanNum($sss_bd_map_i[$an]['compensated']);
                            $row['bill'] = $sss_bd_map_i[$an]['bill'];
                            $row['billdate'] = $sss_bd_map_i[$an]['billdate'];
                            if (!empty($sss_bd_map_i[$an]['bill'])) $row['bookno'] = $sss_bd_map_i[$an]['bill'];
                        }
                    }
                    $all_display_rows_ipd[] = $row;

                } elseif (!empty($all_cr_visits_ipd) && !$is_kidney_click_ipd && $is_cr_parent_ipd) {
                    if ($is_transferred_visit) {
                        $vinfo = $all_cr_visits_ipd[$an];
                        if ($vinfo['is_full_transfer']) {
                            continue;
                        } else {
                            $row['debit'] = $vinfo['general_remain_amount'];
                            $row['uc_money'] = $vinfo['general_remain_amount'];
                            $row['cr_transferred_out'] = $vinfo['cr_amount'];
                            $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                            $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];

                            // หักเงินชดเชยที่โอนไปผังลูกออก
                            $cr_comp = isset($cr_bd_map_i[$an]) ? cleanNum($cr_bd_map_i[$an]['compensated']) : min(cleanNum($row['compensated'] ?? $row['follow_money']), floatval($vinfo['cr_amount']));
                            $mother_comp = max(0, cleanNum($row['compensated'] ?? $row['follow_money']) - $cr_comp);
                            $row['follow_money'] = $mother_comp;
                            $row['compensated'] = $mother_comp;

                            if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                                $row['bill'] = '';
                                $row['billdate'] = '';
                                if ($row['bookno'] == ($cr_bd_map_i[$an]['bill'] ?? '') || $row['bookno'] == ($vinfo['bill'] ?? '')) {
                                    $row['bookno'] = '';
                                }
                            }
                            $all_display_rows_ipd[] = $row;
                        }
                    } else {
                        $all_display_rows_ipd[] = $row;
                    }
                } elseif (!empty($all_sss_visits_ipd) && !$is_kidney_click_ipd && $is_sss_parent_ipd) {
                    if ($is_sss_transferred_visit) {
                        $vinfo = $all_sss_visits_ipd[$an];
                        if ($vinfo['is_full_transfer']) {
                            continue;
                        } else {
                            $row['debit'] = $vinfo['general_remain_amount'];
                            $row['uc_money'] = $vinfo['general_remain_amount'];
                            $row['sss_transferred_out'] = $vinfo['sss_amount'];
                            $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                            $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];

                            // หักเงินชดเชยที่โอนไปผังลูกออก
                            $sss_comp = isset($sss_bd_map_i[$an]) ? cleanNum($sss_bd_map_i[$an]['compensated']) : min(cleanNum($row['compensated'] ?? $row['follow_money']), floatval($vinfo['sss_amount']));
                            $mother_comp = max(0, cleanNum($row['compensated'] ?? $row['follow_money']) - $sss_comp);
                            $row['follow_money'] = $mother_comp;
                            $row['compensated'] = $mother_comp;

                            if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                                $row['bill'] = '';
                                $row['billdate'] = '';
                                if ($row['bookno'] == ($sss_bd_map_i[$an]['bill'] ?? '') || $row['bookno'] == ($vinfo['bill'] ?? '')) {
                                    $row['bookno'] = '';
                                }
                            }
                            $all_display_rows_ipd[] = $row;
                        }
                    } else {
                        $all_display_rows_ipd[] = $row;
                    }
                } else {
                    $all_display_rows_ipd[] = $row;
                }
            }

            echo '<div class="table-scroll">';
            echo '<table class="table table-striped table-bordered stmgetovlookup" border="1">
                      <thead>
                        <tr>
                            <th style="font-weight: bold; background-color: #188b13; color: #fff; vertical-align: middle;" colspan="15">
                                📑 ตารางแจกแจงรายการลูกหนี้รายคน
                            </th>
                        </tr>
                        <tr style="font-weight: bold;">
                          <th class="sortable-header" data-column="0">ลำดับ</th>
                          <th class="sortable-header" data-column="1">เลขหนังสือ/บิล</th>
                          <th class="sortable-header" data-column="2">AN</th>
                          <th class="sortable-header" data-column="3">วันที่จำหน่าย</th>
                          <th class="sortable-header" data-column="4">CID</th>
                          <th class="sortable-header" data-column="5">ชื่อผู้ป่วย</th>
                          <th class="sortable-header" data-column="6">สิทธิ Hos</th>
                          <th class="sortable-header" data-column="7">ระยะเวลาหนี้</th>
                          <th class="sortable-header" data-column="8">ค่าใช้จ่าย</th>
                          <th class="sortable-header" data-column="9">ชำระแล้ว</th>
                          <th class="sortable-header" data-column="10">ภาระหนี้</th>
                          <th class="sortable-header" data-column="11">ชดเชย STM</th>
                          <th class="sortable-header" data-column="12">ส่วนต่าง</th>
                          <th class="sortable-header" data-column="13">วันที่นอน รพ.</th>
                          <th class="sortable-header" data-column="14">Hospmain</th>
                        </tr>
                      </thead>
                      <tbody id="data_tabel_listview_ipd" style="font-size: 14px;">';

            $i = 0;
            $srcpt_money = 0;
            $income_money = 0;
            $income_moneyd = 0;
            $stm_total = 0;
            $diff_total = 0;

            $parseThaiDate = function($dateStr) {
                return parseThaiDate($dateStr);
            };

            foreach ($all_display_rows_ipd as $row) {
                $i++;
                $an = $row['an'];
                if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
                if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);

                $is_row_kidney = ($is_kidney_click_ipd || strpos($row["pttypename"], 'ฟอกไต') !== false || strpos($row["pttypename"], 'ไต') !== false);
                $isMissingAll = empty($row["rep"]) && empty($row["compensated"]);
                $style = $isMissingAll ? 'style="color:red;"' : '';
                if ($row["debit"] == '0') {
                    $style = 'style="color:#000;"';
                }
                if ($is_row_kidney) {
                    $style = 'style="color: #cd641f;"';
                }

                // --- ส่วนคำนวณวันค้างชำระ (Aging) ---
                $days_count = 0;
                $status_text = " วัน";
                if (!empty($row["dchdate"])) {
                    $startDate = $parseThaiDate($row["dchdate"]);
                    $endDate = new DateTime();
                    if (!empty($row['billdate'])) {
                        $parsed = $parseThaiDate($row['billdate']);
                        if ($parsed) $endDate = $parsed;
                    } elseif (!empty($row['mobile'])) {
                        $parsed = $parseThaiDate($row['mobile']);
                        if ($parsed) $endDate = $parsed;
                    }
                    if ($startDate) {
                        $diff = $startDate->diff($endDate);
                        $days_count = $diff->days;
                    }
                }

                $stm_compensation_value = !empty($row["follow_money"]) ? cleanNum($row["follow_money"]) : cleanNum($row["compensated"] ?? 0);
                $diff_amount = cleanNum($row["debit"]) - cleanNum($stm_compensation_value);

                // 💡 Hospmain lookup
                $hosp_detail = "-";
                $current_an = mysqli_real_escape_string($conn2, $row["an"]);
                $sql_hosp = "SELECT CONCAT(o.hospmain, ' (', ho.`name`, ')') AS hosp_detail
                                FROM ovst o
                                LEFT JOIN hospcode ho ON ho.hospcode = o.hospmain
                                WHERE o.an = '$current_an'
                                AND o.hospmain IS NOT NULL
                                AND o.hospmain <> ''
                                ORDER BY o.vstdate DESC, o.vsttime DESC
                                LIMIT 1;";
                $result_hosp = mysqli_query($conn2, $sql_hosp);
                if ($result_hosp && mysqli_num_rows($result_hosp) > 0) {
                    $row_hosp = mysqli_fetch_assoc($result_hosp);
                    $hosp_detail = $row_hosp["hosp_detail"];
                }

                $cr_types = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : (isset($row['cr_types']) ? $row['cr_types'] : []);
                $cr_items = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : (isset($row['items']) ? $row['items'] : []);
                $sss_types = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
                $sss_items = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

                $all_subtypes = array_unique(array_merge($cr_types, $sss_types));
                $cr_type_str = implode(',', $all_subtypes);

                // 🌟 คำนวณยอดเงินแยกตามกลุ่มย่อย CR จากรายการ items จริง (IPD)
                $cr_subgroup_amounts_i = [];
                if (!empty($cr_items)) {
                    foreach ($cr_items as $cit) {
                        $ctype = trim((string)($cit['cr_type'] ?? ''));
                        if (!empty($ctype)) {
                            if (!isset($cr_subgroup_amounts_i[$ctype])) {
                                $cr_subgroup_amounts_i[$ctype] = 0.0;
                            }
                            $cr_subgroup_amounts_i[$ctype] += floatval(cleanNum($cit['amount'] ?? 0));
                        }
                    }
                }
                if (!empty($cr_types)) {
                    $allocated_sum_i = array_sum($cr_subgroup_amounts_i);
                    $unallocated_i = max(0, floatval(cleanNum($row['debit'])) - $allocated_sum_i);
                    $zero_types_i = [];
                    foreach ($cr_types as $ct) {
                        if (!isset($cr_subgroup_amounts_i[$ct]) || $cr_subgroup_amounts_i[$ct] <= 0.001) {
                            $zero_types_i[] = $ct;
                        }
                    }
                    if ($unallocated_i > 0 && !empty($zero_types_i)) {
                        $each_amt_i = $unallocated_i / count($zero_types_i);
                        foreach ($zero_types_i as $zt) {
                            $cr_subgroup_amounts_i[$zt] = $each_amt_i;
                        }
                    }
                }

                // คำนวณยอดเงินแยกตามกลุ่มย่อย SSS (IPD)
                $sss_subgroup_amounts_i = [];
                if (!empty($sss_items)) {
                    foreach ($sss_items as $sit) {
                        $stype = trim((string)($sit['cr_type'] ?? ''));
                        if (!empty($stype)) {
                            if (!isset($sss_subgroup_amounts_i[$stype])) {
                                $sss_subgroup_amounts_i[$stype] = 0.0;
                            }
                            $sss_subgroup_amounts_i[$stype] += floatval(cleanNum($sit['amount'] ?? 0));
                        }
                    }
                }
                if (!empty($sss_types)) {
                    $allocated_sum_si = array_sum($sss_subgroup_amounts_i);
                    $unallocated_si = max(0, floatval(cleanNum($row['debit'])) - $allocated_sum_si);
                    $zero_types_si = [];
                    foreach ($sss_types as $st) {
                        if (!isset($sss_subgroup_amounts_i[$st]) || $sss_subgroup_amounts_i[$st] <= 0.001) {
                            $zero_types_si[] = $st;
                        }
                    }
                    if ($unallocated_si > 0 && !empty($zero_types_si)) {
                        $each_amt_si = $unallocated_si / count($zero_types_si);
                        foreach ($zero_types_si as $zs) {
                            $sss_subgroup_amounts_i[$zs] = $each_amt_si;
                        }
                    }
                }

                if ($is_cr_target_account_ipd || $is_cr_parent_ipd) {
                    $subgroup_stats_ipd['ALL']['count']++;
                    $subgroup_stats_ipd['ALL']['debit'] += cleanNum($row['debit']);
                    foreach ($cr_types as $ct) {
                        if (isset($subgroup_stats_ipd[$ct])) {
                            $subgroup_stats_ipd[$ct]['count']++;
                            $sg_amt = isset($cr_subgroup_amounts_i[$ct]) && $cr_subgroup_amounts_i[$ct] > 0 
                                ? $cr_subgroup_amounts_i[$ct] 
                                : cleanNum($row['debit']);
                            $subgroup_stats_ipd[$ct]['debit'] += $sg_amt;
                        }
                    }
                } elseif ($is_sss_target_account_ipd || $is_sss_parent_ipd) {
                    $subgroup_stats_ipd['ALL']['count']++;
                    $subgroup_stats_ipd['ALL']['debit'] += cleanNum($row['debit']);
                    foreach ($sss_types as $st) {
                        if (isset($subgroup_stats_ipd[$st])) {
                            $subgroup_stats_ipd[$st]['count']++;
                            $sg_amt = isset($sss_subgroup_amounts_i[$st]) && $sss_subgroup_amounts_i[$st] > 0 
                                ? $sss_subgroup_amounts_i[$st] 
                                : cleanNum($row['debit']);
                            $subgroup_stats_ipd[$st]['debit'] += $sg_amt;
                        }
                    }
                }

                $cr_badge_html = '';
                if ($is_cr_target_account_ipd || $is_cr_parent_ipd) {
                    $original_debit_display = floatval(cleanNum($row['debit']));
                    $cr_amt_display = floatval(cleanNum($row['debit']));
                    $remain_debit_display = 0.0;

                    if ($is_cr_parent_ipd) {
                        $cr_amt_display = floatval(cleanNum($row['cr_transferred_out'] ?? 0));
                        $original_debit_display = $original_debit_display + $cr_amt_display;
                        $remain_debit_display = floatval(cleanNum($row['debit']));
                    } elseif ($is_cr_target_account_ipd && isset($all_cr_visits_ipd[$an])) {
                        $vinfo = $all_cr_visits_ipd[$an];
                        $original_debit_display = floatval(cleanNum($vinfo['general_remain_amount'] + $vinfo['cr_amount']));
                        $cr_amt_display = floatval(cleanNum($vinfo['cr_amount']));
                        $remain_debit_display = max(0, $original_debit_display - $cr_amt_display);
                    }

                    $cr_payload_data = [
                        'vn' => (string)$row['an'],
                        'hn' => (string)($row['hn'] ?? ''),
                        'ptname' => (string)($row['ptname'] ?? ''),
                        'vstdate' => (string)($row['dchdate'] ?? ''),
                        'pttypename' => (string)($row['pttypename'] ?? ''),
                        'hospmain' => (string)$hosp_detail,
                        'income' => floatval(cleanNum($row['income_original'] ?? $row['income'])),
                        'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                        'debit' => $original_debit_display,
                        'origin_acc' => (string)($row['cr_origin_acc'] ?? ($is_cr_target_account_ipd ? '1102050101.217' : $stmget)),
                        'target_acc' => '1102050101.217',
                        'cr_types' => $cr_types,
                        'cr_amount' => $cr_amt_display,
                        'remain_debit' => $remain_debit_display,
                        'items' => $cr_items
                    ];
                    $payload_json_str = htmlspecialchars(json_encode($cr_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                    foreach ($cr_types as $ct) {
                        $ct_amt = isset($cr_subgroup_amounts_i[$ct]) ? floatval($cr_subgroup_amounts_i[$ct]) : 0;
                        $ct_amt_label = ($ct_amt > 0) ? ' (฿' . number_format($ct_amt, 2) . ')' : '';
                        $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge" data-cr-subgroup="' . htmlspecialchars($ct) . '" data-filter-subgroup="' . htmlspecialchars($ct) . '" style="font-size:11px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'' . htmlspecialchars($ct, ENT_QUOTES) . '\')" title="คลิกดูรายการเฉพาะกลุ่ม ' . htmlspecialchars($ct) . $ct_amt_label . '">' . htmlspecialchars($ct) . '</span>';
                    }

                    if (!empty($row['cr_origin_acc']) && $row['cr_origin_acc'] !== '1102050101.217') {
                        $orig_short = str_replace('1102050101.', '.', $row['cr_origin_acc']);
                        $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'ALL\')" title="โอนมาจากผัง ' . htmlspecialchars($row['cr_origin_acc']) . ' (คลิกเพื่อดูทุกกลุ่มย่อยที่โอนมา)"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                    } elseif (!empty($row['cr_transferred_out']) && $row['cr_transferred_out'] > 0) {
                        $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'ALL\')" title="ตัดยอด CR ออกไปผัง .217 จำนวน ฿' . number_format($row['cr_transferred_out'], 2) . ' (คลิกเพื่อดูรายการ)"><i class="bx bx-cut"></i> ตัด CR -฿' . number_format($row['cr_transferred_out'], 2) . '</span>';
                    }
                } elseif ($is_sss_target_account_ipd || $is_sss_parent_ipd) {
                    $original_debit_display = floatval(cleanNum($row['debit']));
                    $sss_amt_display = floatval(cleanNum($row['debit']));
                    $remain_debit_display = 0.0;

                    if ($is_sss_parent_ipd) {
                        $sss_amt_display = floatval(cleanNum($row['sss_transferred_out'] ?? 0));
                        $original_debit_display = $original_debit_display + $sss_amt_display;
                        $remain_debit_display = floatval(cleanNum($row['debit']));
                    } elseif ($is_sss_target_account_ipd && isset($all_sss_visits_ipd[$an])) {
                        $vinfo = $all_sss_visits_ipd[$an];
                        $original_debit_display = floatval(cleanNum($vinfo['general_remain_amount'] + $vinfo['sss_amount']));
                        $sss_amt_display = floatval(cleanNum($vinfo['sss_amount']));
                        $remain_debit_display = max(0, $original_debit_display - $sss_amt_display);
                    }

                    $sss_payload_data = [
                        'vn' => (string)$row['an'],
                        'hn' => (string)($row['hn'] ?? ''),
                        'ptname' => (string)($row['ptname'] ?? ''),
                        'vstdate' => (string)($row['dchdate'] ?? ''),
                        'pttypename' => (string)($row['pttypename'] ?? ''),
                        'hospmain' => (string)$hosp_detail,
                        'income' => floatval(cleanNum($row['income_original'] ?? $row['income'])),
                        'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                        'debit' => $original_debit_display,
                        'origin_acc' => (string)($row['sss_origin_acc'] ?? ($is_sss_target_account_ipd ? '1102050101.310' : $stmget)),
                        'target_acc' => '1102050101.310',
                        'sss_types' => $sss_types,
                        'sss_amount' => $sss_amt_display,
                        'remain_debit' => $remain_debit_display,
                        'items' => $sss_items
                    ];
                    $payload_json_str = htmlspecialchars(json_encode($sss_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                    foreach ($sss_types as $st) {
                        $st_amt = isset($sss_subgroup_amounts_i[$st]) ? floatval($sss_subgroup_amounts_i[$st]) : 0;
                        $st_amt_label = ($st_amt > 0) ? ' (฿' . number_format($st_amt, 2) . ')' : '';
                        $cr_badge_html .= ' <span class="badge bg-label-success font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge" data-cr-subgroup="' . htmlspecialchars($st) . '" data-filter-subgroup="' . htmlspecialchars($st) . '" style="font-size:11px; cursor:pointer;" data-sss-payload="' . $payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="คลิกดูรายการอุปกรณ์ SSS ' . htmlspecialchars($st) . $st_amt_label . '">' . htmlspecialchars($st) . '</span>';
                    }

                    if (!empty($row['sss_origin_acc']) && $row['sss_origin_acc'] !== '1102050101.310') {
                        $orig_short = str_replace('1102050101.', '.', $row['sss_origin_acc']);
                        $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="โอนมาจากผัง ' . htmlspecialchars($row['sss_origin_acc']) . ' (คลิกเพื่อดูรายการ)"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                    } elseif (!empty($row['sss_transferred_out']) && $row['sss_transferred_out'] > 0) {
                        $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="ตัดยอด SSS ออกไปผัง .310 จำนวน ฿' . number_format($row['sss_transferred_out'], 2) . ' (คลิกเพื่อดูรายการ)"><i class="bx bx-cut"></i> ตัด SSS -฿' . number_format($row['sss_transferred_out'], 2) . '</span>';
                    }
                }

                $all_subgroup_amounts_i = array_merge($cr_subgroup_amounts_i, $sss_subgroup_amounts_i);
                $amounts_json_i = htmlspecialchars(json_encode($all_subgroup_amounts_i, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                echo '<tr class="main-patient-row" data-cr-type="' . htmlspecialchars($cr_type_str) . '" data-cr-amounts="' . $amounts_json_i . '" data-full-debit="' . floatval(cleanNum($row['debit'])) . '">';
                echo "<td class='text-center' $style>" . $i . "</td>";
                echo "<td class='text-center' $style>" . htmlspecialchars($row["rep"] ?? '') . "</td>";
                echo "<td class='text-center fw-semibold' $style>" . htmlspecialchars($row["an"]) . "</td>";
                echo "<td class='text-center' $style>" . htmlspecialchars($row["dchdate"]) . "</td>";
                echo "<td class='text-center' $style>" . htmlspecialchars($row["cid"]) . "</td>";
                echo "<td class='text-start' $style>" . htmlspecialchars($row["ptname"]) . $cr_badge_html . "</td>";
                echo "<td class='text-start' $style>" . htmlspecialchars($row["pttypename"]) . "</td>";
                echo "<td class='text-center' $style>" . number_format($days_count) . $status_text . "</td>";

                echo "<td class='text-end' $style>" . formatMoney($row["income"], 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($row["incomediff"], 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($row["debit"], 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($stm_compensation_value, 2) . "</td>";
                echo "<td class='text-end' $style>" . formatMoney($diff_amount, 2) . "</td>";
                echo "<td class='text-center' $style>" . htmlspecialchars($row["admdate"] ?? '') . "</td>";
                echo "<td class='text-start' $style>" . htmlspecialchars($hosp_detail) . "</td>";
                echo "</tr>";

                $srcpt_money += cleanNum($row["debit"]);
                $income_money += cleanNum($row["income"]);
                $income_moneyd += cleanNum($row["incomediff"]);
                $stm_total += cleanNum($stm_compensation_value);
                $diff_total += cleanNum($diff_amount);
            }

            echo '<span id="total_patient_count" data-count="'.$i.'" style="display:none;"></span>';
            echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            echo '</tbody>';

            if (!empty($all_display_rows_ipd)) {
                echo '<tfoot>
                    <tr class="summary-total-row" style="background: linear-gradient(268deg, #2a3d5d 0%, #1e2b4b 100%) !important; color: #ffffff !important;">
                        <td colspan="8" class="text-center fw-bold" style="background: transparent !important; color: #ffffff !important;">รวมทั้งสิ้น</td>
                        <td class="text-end fw-bold" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($income_money, 2) . '</td>
                        <td class="text-end fw-bold" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($income_moneyd, 2) . '</td>
                        <td class="text-end fw-bold" id="i1" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($srcpt_money, 2) . '</td>
                        <td class="text-end fw-bold" id="i2" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($stm_total, 2) . '</td>
                        <td class="text-end fw-bold" id="i3" style="background: transparent !important; color: #ffffff !important;">' . formatMoney($diff_total, 2) . '</td>
                        <td colspan="2" style="background: transparent !important; color: #ffffff !important;"></td>
                    </tr>
                </tfoot>';
            }
            echo '</table></div>';
            if ($is_cr_target_account_ipd || $is_cr_parent_ipd || $is_sss_target_account_ipd || $is_sss_parent_ipd) {
                echo '<script id="cr_subgroup_summary_json" type="application/json">' . json_encode($subgroup_stats_ipd, JSON_UNESCAPED_UNICODE) . '</script>';
            } else {
                echo '<script id="cr_subgroup_summary_json" type="application/json">{}</script>';
            }
        }

        if(isset($_POST['actionstmvlookup'])){

          $sql = "SELECT stm.yearbudget,o.mobile as mobileo,i.mobile as mobilei,stm.rep,SUM(stm.compensated) as compensated,stm.accountname,stm.fund,count(stm.rep) as ccall,stm.accountcode
          FROM imr_tb_check_invoice stm
          LEFT JOIN  imr_tb_debtor_rights_opd o on o.vn=stm.vn
          LEFT JOIN  imr_tb_debtor_rights_ipd i on i.an=stm.vn
          WHERE compensated != '0'
          GROUP BY stm.rep
          ORDER BY stm.rep desc";

          $result = mysqli_query($conn, $sql);

          echo '<table class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th style="background-color: #f9fafb;">ปีงบ</th>
                      <th style="background-color: #f9fafb;">เลขที่ใบเสร็จ</th>
                      <th style="background-color: #f9fafb;">เลขที่หนังสือ</th>
                      <th style="background-color: #f9fafb;">ชื่อสิทธิ</th>
                      <th style="background-color: #f9fafb;">กองทุน</th>
                      <th style="background-color: #f9fafb;">เงินชดเชย</th>
                      <th style="background-color: #f9fafb;">จำนวนคน</th>
                    </tr>
                  </thead>
                  <tbody id="myTablev">';


        if ($result && mysqli_num_rows($result) > 0) {
          // output data of each row
          $i=1;$san=0;$srcpt_money=0;$ccall=0;
          while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);

            if($row["mobileo"]<>''){
                    if($row["mobileo"]<>'' and $row["mobileo"]<>'-'){
                    echo'<tr>
                          <td style="color: green;">'.$row["yearbudget"].'</td>
                          <td style="color: green;">'.$row["mobileo"].'</td>
                          <td><a style="color: green;" href="#" onclick="stmgeto(\''.$row["rep"].'\')">'.$row["rep"].'</a></td>
                          <td><a style="color: green;" href="#" onclick="stmgeto(\''.$row["rep"].'\')">'.$row["accountname"].'</a></td>
                          <td style="color: green;">'.$row["fund"].'</td>
                          <td style="color: green;">'.formatMoney($row["compensated"], 2).'</td>
                          <td style="color: green;">'.$row["ccall"].'</td>
                        </tr>
                    ';
                    }else{
                      echo'<tr>
                            <td>'.$row["yearbudget"].'</td>
                            <td>'.$row["mobileo"].'</td>
                            <td><a href="#" onclick="stmgeto(\''.$row["rep"].'\')">'.$row["rep"].'</a></td>
                            <td><a href="#" onclick="stmgeto(\''.$row["rep"].'\')">'.$row["accountname"].'</a></td>
                            <td>'.$row["fund"].'</td>
                            <td>'.formatMoney($row["compensated"], 2).'</td>
                            <td>'.$row["ccall"].'</td>
                          </tr>
                      ';
                      }

            }else{

                   if($row["mobilei"]<>'' and $row["mobilei"]<>'-'){
                    echo'<tr>
                          <td style="color: green;">'.$row["yearbudget"].'</td>
                          <td style="color: green;">'.$row["mobilei"].'</td>
                          <td><a style="color: green;" href="#" onclick="stmgeto(\''.$row["rep"].'\')">'.$row["rep"].'</a></td>
                          <td><a style="color: green;" href="#" onclick="stmgeto(\''.$row["rep"].'\')">'.$row["accountname"].'</a></td>
                          <td style="color: green;">'.$row["fund"].'</td>
                          <td style="color: green;">'.formatMoney($row["compensated"], 2).'</td>
                          <td style="color: green;">'.$row["ccall"].'</td>
                        </tr>
                    ';
                    }else{
                      echo'<tr>
                            <td>'.$row["yearbudget"].'</td>
                            <td>'.$row["mobilei"].'</td>
                            <td><a href="#" onclick="stmgeto(\''.$row["rep"].'\')">'.$row["rep"].'</a></td>
                            <td><a href="#" onclick="stmgeto(\''.$row["rep"].'\')">'.$row["accountname"].'</a></td>
                            <td>'.$row["fund"].'</td>
                            <td>'.formatMoney($row["compensated"], 2).'</td>
                            <td>'.$row["ccall"].'</td>
                          </tr>
                      ';
                      }
            }


            $srcpt_money = $srcpt_money + cleanNum($row["compensated"]);
            $ccall = $ccall + cleanNum($row["ccall"]);

            $i++;
            }

            echo'<tr style="background: #cecece;">
                  <td style="text-align:center;color: blue;font-weight: bold;" colspan="5">รวม</td>
                  <td style="color: blue;font-weight: bold;" >'.formatMoney($srcpt_money, 2).'</td>
                  <td style="color: blue;font-weight: bold;" >'.number_format((float)$ccall,0).' คน</td>

                </tr>';

          }

          echo'</tbody>
              </table>';
        }



        if(isset($_POST['actionckdvlookup'])){

          $sql = "
              (
                  -- กรณี LGO/UCS/SSS
                  SELECT
                      rep AS doc_no,
                      SUM(compensated) AS compensated,
                      COUNT(rep) AS ccpayno,
                      o.mobile,
                      CASE dc.fund
                          WHEN 'LGO' THEN 'ฟอกไต LGO-สิทธิอปท.'
                          WHEN 'UCS' THEN 'ฟอกไต UCS-สิทธิบัตรทอง'
                          WHEN 'SSS' THEN 'ฟอกไต SSS-สิทธิประกันสังคม'
                          ELSE 'ฟอกไต - อื่นๆ'
                      END AS source,
                      dc.fund
                  FROM imr_tb_seamless_dckd dc
                  LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = dc.vn
                  WHERE dc.fund IN ('LGO', 'UCS', 'SSS')
                  GROUP BY rep, dc.fund
              )
              UNION ALL
              (
                  -- กรณี OFC แยกตาราง
                  SELECT
                      stm_doc AS doc_no,
                      SUM(amount) AS compensated,
                      COUNT(stm_doc) AS ccpayno,
                      o.mobile,
                      'ฟอกไต OFC-สิทธิข้าราชการ/สิทธิหน่วยงานต้นสังกัด' AS source,
                      'OFC' AS fund
                  FROM imr_tb_seamless_dckd_ofc ofc
                  LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = ofc.vn
                  GROUP BY stm_doc
              )
              ORDER BY source DESC, doc_no DESC
          ";

          $result = mysqli_query($conn, $sql);

          echo '<table class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th style="background-color: #f9fafb;">กองทุน</th>
                      <th style="background-color: #f9fafb;">เลขที่ใบเสร็จ</th>
                      <th style="background-color: #f9fafb;">เลขหนังสือ</th>
                      <th style="background-color: #f9fafb;">เงินชดเชย DCKD</th>
                    </tr>
                  </thead>
                  <tbody>';

          if ($result && mysqli_num_rows($result) > 0) {
              $compensated_total = 0;
              $group_total = 0;
              $current_source = '';
              $first_row = true;

              while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
                  if ($current_source !== $row["source"]) {
                      if (!$first_row) {
                          echo '<tr>
                                  <td colspan="3" style="text-align:right; font-weight:bold; color:green;">รวมชดเชย ' . getSourceShort($current_source) . '</td>
                                  <td style="font-weight:bold; color:green;">' . formatMoney($group_total, 2) . '</td>
                                </tr>';
                      }

                      $current_source = $row["source"];
                      $group_total = 0;

                      echo '<tr>
                              <td colspan="4" style="background-color:#f0f0f0;font-weight:bold;">' . strtoupper($current_source) . '</td>
                            </tr>';

                      $first_row = false;
                  }

                  $mobile_display = ($row["mobile"] != '' && $row["mobile"] != '-') ? '<span style="color:green;">' . $row["mobile"] . '</span>' : $row["mobile"];
                  $doc_link = '<a href="#" onclick="ckdgeto(\'' . $row["doc_no"] . '\')" style="' . (($row["mobile"] != '' && $row["mobile"] != '-') ? 'color:green;' : '') . '">' . $row["doc_no"] . '</a>';
                  $compensated_display = '<span style="' . (($row["mobile"] != '' && $row["mobile"] != '-') ? 'color:green;' : '') . '">' . formatMoney($row["compensated"], 2) . '</span>';

                  echo '<tr>
                          <td>' . $row["fund"] . '</td>
                          <td>' . $mobile_display . '</td>
                          <td>' . $doc_link . '</td>
                          <td>' . $compensated_display . '</td>
                        </tr>';

                  $group_total += $row["compensated"];
                  $compensated_total += $row["compensated"];
              }

              echo '<tr>
                      <td colspan="3" style="text-align:right; font-weight:bold; color:green;">รวมชดเชย ' . getSourceShort($current_source) . '</td>
                      <td style="font-weight:bold; color:green;">' . formatMoney($group_total, 2) . '</td>
                    </tr>';

              echo '<tr>
                      <td colspan="3" style="text-align:center;color: blue;font-weight: bold;">รวมทั้งหมด</td>
                      <td style="color: blue;font-weight: bold;">' . formatMoney($compensated_total, 2) . '</td>
                    </tr>';
          }

          echo '</tbody></table>';

          }







    if(isset($_POST['stmgetovlookup'])){

          // ป้องกัน SQL Injection ก่อนนำไปใช้
          $stmgetovlookup = mysqli_real_escape_string($conn, $_POST['stmgetovlookup']);

          // เพิ่ม o.bill AS billo และ i.bill AS billi ใน SELECT
          $sql = "SELECT stm.yearbudget,stm.rep,o.vn,o.ptname,o.debit,o.follow_money AS follow_money_o,i.an,i.ptname as 'ptnamei',i.debit as 'debiti',i.follow_money AS follow_money_i,stm.compensated,(stm.compensated)-(o.debit) AS 'diff',(stm.compensated)-(i.debit) AS 'diffi',stm.accountname,stm.fund,o.vstdate,i.admdate,i.dchdate,stm.accountcode,i.mobile as 'mobilei',o.mobile as 'mobileo',o.cid as cido,i.cid as cidi, o.bill AS billo, i.bill AS billi
            FROM imr_tb_check_invoice stm
            LEFT JOIN  imr_tb_debtor_rights_opd o on o.vn=stm.vn
            LEFT JOIN  imr_tb_debtor_rights_ipd i on i.an=stm.vn
            WHERE stm.rep = '$stmgetovlookup' and compensated != '0'
            ORDER BY diff desc ";

          $result = mysqli_query($conn, $sql);

          echo '<table class="table table-striped table-bordered stmgetovlookup">
                  <thead>
                    <tr>
                      <th>ลำดับ</th>
                      <th>เลขที่ใบเสร็จ</th>
                      <th>ปีงบ</th>
                      <th>เลขหนังสือ</th>
                      <th>VN/AN</th>
                      <th>cid</th>
                      <th>ชื่อ-สกุล</th>
                      <th>ชื่อสิทธิ</th>
                      <th>วันที่</th>
                      <th>ลูกหนี้สิทธิ</th>
                      <th>เงินชดเชย</th>
                      <th>ส่วนต่าง</th>
                    </tr>
                  </thead>
                  <tbody>';

        // สร้าง Array ไว้เก็บชื่อคนที่มีเลขที่ใบเสร็จแล้ว
        $billed_patients = [];

        if ($result && mysqli_num_rows($result) > 0) {
          $all_rows = [];
          $opd_checks = [];
          $ipd_checks = [];

          while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
              $all_rows[] = $row;
              if (!empty($row["cido"]) && !empty($row["vstdate"])) {
                  $opd_checks[] = "('" . mysqli_real_escape_string($conn, $row["cido"]) . "','" . mysqli_real_escape_string($conn, $row["vstdate"]) . "')";
              }
              if (!empty($row["cidi"]) && !empty($row["dchdate"])) {
                  $ipd_checks[] = "('" . mysqli_real_escape_string($conn, $row["cidi"]) . "','" . mysqli_real_escape_string($conn, $row["dchdate"]) . "')";
              }
          }

          $dup_opd_map = [];
          if (!empty($opd_checks)) {
              $opd_checks_str = implode(",", array_unique($opd_checks));
              $q_opd = "SELECT cid, vstdate, COUNT(vn) as cnt FROM imr_tb_debtor_rights_opd WHERE (cid, vstdate) IN ($opd_checks_str) GROUP BY cid, vstdate HAVING COUNT(vn) > 1";
              $r_opd = mysqli_query($conn, $q_opd);
              if($r_opd) {
                  while ($d = mysqli_fetch_assoc($r_opd)) {
    if (isset($d['tel'])) $d['tel'] = decrypt_data($d['tel']);
                      $dup_opd_map[$d['cid'].'_'.$d['vstdate']] = $d['cnt'];
                  }
              }
          }

          $dup_ipd_map = [];
          if (!empty($ipd_checks)) {
              $ipd_checks_str = implode(",", array_unique($ipd_checks));
              $q_ipd = "SELECT cid, dchdate, COUNT(an) as cnt FROM imr_tb_debtor_rights_ipd WHERE (cid, dchdate) IN ($ipd_checks_str) GROUP BY cid, dchdate HAVING COUNT(an) > 1";
              $r_ipd = mysqli_query($conn, $q_ipd);
              if($r_ipd) {
                  while ($d = mysqli_fetch_assoc($r_ipd)) {
    if (isset($d['tel'])) $d['tel'] = decrypt_data($d['tel']);
                      $dup_ipd_map[$d['cid'].'_'.$d['dchdate']] = $d['cnt'];
                  }
              }
          }

          $i=1;$compensated=0;$diff=0;$debit=0;$debiti=0;$diffi=0;

          foreach ($all_rows as $row) {

            if($row["an"]==""){
                $an=0;

                // เช็คว่า OPD มีเลขที่ใบเสร็จ (bill) หรือยัง
                if(!empty($row["billo"])) {
                    $billed_patients[] = "OPD: " . $row["ptname"] . " (เลขที่ใบเสร็จ: " . $row["billo"] . ")";
                }

                $debit_icon = '';
                if (isset($row["follow_money_o"]) && $row["follow_money_o"] > 0) {
                    $row["debit"] = cleanNum($row["debit"]) - cleanNum($row["follow_money_o"]); // ลูกหนี้สิทธิที่เหลือ
                    $row["diff"] = cleanNum($row["compensated"]) - cleanNum($row["debit"]); // คำนวณส่วนต่างใหม่
                    $debit_icon = ' <iconify-icon icon="fa6-solid:hand-holding-dollar" style="color: #f2a33c; font-size: 15px;" title="แบ่งจ่ายไปแล้ว ' . formatMoney($row["follow_money_o"], 2) . '"></iconify-icon>';
                }

                $dup_badge_o = "";
                $dup_key_o = $row["cido"].'_'.$row["vstdate"];
                if(isset($dup_opd_map[$dup_key_o]) && $dup_opd_map[$dup_key_o] > 1) {
                    $dup_badge_o = ' <span class="badge bg-danger" style="cursor:pointer; font-size: 10px; text-transform: capitalize; vertical-align: text-bottom;" onclick="viewDuplicateVisits(\''.$row["cido"].'\', \''.$row["vstdate"].'\')">'.$dup_opd_map[$dup_key_o].' visit</span>';
                }

                echo'<tr>
                  <td style="color: #212121; text-align: center;">'.$i.'</td>
                  <td style="color: #212121; text-align: center;">'.$row["mobileo"].'</td>
                  <td style="color: #212121; text-align: center;">'.$row["yearbudget"].'</td>
                  <td style="color: #212121; text-align: center;">'.$row["rep"].'</td>
                  <td style="color: #212121; text-align: center;">'.$row["vn"].'</td>
                  <td style="color: #212121; text-align: center;">'.decrypt_data($row["cido"]).'</td>
                  <td style="color: #212121;">'.$row["ptname"].$dup_badge_o.'</td>
                  <td style="color: #212121;">'.$row["accountname"].'</td>
                  <td style="color: #212121; text-align: center;">'.$row["vstdate"].'</td>
                  <td style="color: blue; text-align: right;">'.formatMoney($row["debit"], 2).$debit_icon.'</td>
                  <td style="color: green; text-align: right;">'.formatMoney($row["compensated"], 2).'</td>
                  <td style="color: #ff6d07; text-align: right;">'.formatMoney($row["diff"], 2).'</td>
                </tr>';

            $debit = $debit + cleanNum($row["debit"]);
            $diff = $diff + cleanNum($row["diff"]);

            }else{
              $an=1;

              // เช็คว่า IPD มีเลขที่ใบเสร็จ (bill) หรือยัง
              if(!empty($row["billi"])) {
                  $billed_patients[] = "IPD: " . $row["ptnamei"] . " (เลขที่ใบเสร็จ: " . $row["billi"] . ")";
              }

                $debit_icon_i = '';
                if (isset($row["follow_money_i"]) && $row["follow_money_i"] > 0) {
                    $row["debiti"] = cleanNum($row["debiti"]) - cleanNum($row["follow_money_i"]);
                    $row["diffi"] = cleanNum($row["compensated"]) - cleanNum($row["debiti"]);
                    $debit_icon_i = ' <iconify-icon icon="fa6-solid:hand-holding-dollar" style="color: #f2a33c; font-size: 15px;" title="แบ่งจ่ายไปแล้ว ' . formatMoney($row["follow_money_i"], 2) . '"></iconify-icon>';
                }

                $dup_badge_i = "";
                $dup_key_i = encrypt_data($row["cidi"]).'_'.$row["dchdate"];
                if(isset($dup_ipd_map[$dup_key_i]) && $dup_ipd_map[$dup_key_i] > 1) {
                    $dup_badge_i = ' <span class="badge bg-danger" style="cursor:pointer; font-size: 10px; text-transform: capitalize; vertical-align: text-bottom;" onclick="viewDuplicateVisits(\''.encrypt_data($row["cidi"]).'\', \''.$row["dchdate"].'\')">'.$dup_ipd_map[$dup_key_i].' visit</span>';
                }

                echo'<tr>
                  <td style="color: #212121; text-align: center;">'.$i.'</td>
                  <td style="color: #212121; text-align: center;">'.$row["mobilei"].'</td>
                  <td style="color: #212121; text-align: center;">'.$row["yearbudget"].'</td>
                  <td style="color: #212121; text-align: center;">'.$row["rep"].'</td>
                  <td style="color: #212121; text-align: center;">'.$row["an"].'</td>
                  <td style="color: #212121; text-align: center;">'.decrypt_data($row["cidi"]).'</td>
                  <td style="color: #212121;">'.$row["ptnamei"].$dup_badge_i.'</td>
                  <td style="color: #212121;">'.$row["accountname"].'</td>
                  <td style="color: #212121; text-align: center;">'.$row["dchdate"].'</td>
                  <td style="color: blue; text-align: right;">'.formatMoney($row["debiti"], 2).$debit_icon_i.'</td>
                  <td style="color: green; text-align: right;">'.formatMoney($row["compensated"], 2).'</td>
                  <td style="color: #ff6d07; text-align: right;">'.formatMoney($row["diffi"], 2).'</td>
                </tr>';

            $diffi = cleanNum($row["diffi"]);
            $debiti = $debiti + cleanNum($row["debiti"]);

            }

            $compensated = $compensated + cleanNum($row["compensated"]);
            $i++;

            }

            echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            echo '</tbody>';

            if($an==0){
                echo '<tfoot>
                  <tr class="summary-total-row">
                    <td style="text-align:center;font-weight: bold;" colspan="9">รวม</td>
                    <td style="text-align: right;font-weight: bold;" id="o1">'.formatMoney($debit, 2).'</td>
                    <td style="text-align: right;font-weight: bold;" id="o2">'.formatMoney($compensated, 2).'</td>
                    <td style="text-align: right;font-weight: bold;" id="o3">'.formatMoney($diff, 2).'</td>
                  </tr>
                </tfoot>';
            }else{
                echo '<tfoot>
                  <tr class="summary-total-row">
                    <td style="text-align:center;font-weight: bold;" colspan="9">รวม</td>
                    <td style="text-align: right;font-weight: bold;" id="o1">'.formatMoney($debiti, 2).'</td>
                    <td style="text-align: right;font-weight: bold;" id="o2">'.formatMoney($compensated, 2).'</td>
                    <td style="text-align: right;font-weight: bold;" id="o3">'.formatMoney($diffi, 2).'</td>
                  </tr>
                </tfoot>';
            }

          } else {
            echo '</tbody>';
          }

          echo '</table>';
    }









if (isset($_POST['ckdgetovlookup'])) {
    $ckdgetovlookup = $_POST['ckdgetovlookup'];

    if (strpos($ckdgetovlookup, 'COCD') !== false) {
        // กรณีเป็นโค้ดแบบ 11072_COCDSTM_20250301

        $sql = "SELECT ofc.namepat, ofc.stm_doc, ofc.vn, SUM(ofc.amount) AS compensated,ofc.dttran
                FROM imr_tb_seamless_dckd_ofc ofc
                WHERE ofc.stm_doc = '$ckdgetovlookup'
                GROUP BY ofc.namepat,ofc.vn
                ORDER BY ofc.namepat ASC";

        $result = mysqli_query($conn, $sql);

        echo '<table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>ลำดับ</th>
                    <th>เลขที่ใบเสร็จ</th>
                    <th>เลขหนังสือ</th>
                    <th>VN</th>
                    <th>HN</th>
                    <th>ชื่อ-สกุล</th>
                    <th>สิทธิการเงิน</th>
                    <th>วันที่</th>
                    <th>ลูกหนี้สิทธิ</th>
                    <th>เงินชดเชย</th>
                    <th>ส่วนต่าง</th>
                  </tr>
                </thead>
                <tbody>';

        if ($result && mysqli_num_rows($result) > 0) {
            // ดึง vn ทั้งหมดมาเช็คซ้ำรอบเดียว
            $vns = [];
            while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
                $vns[] = "'" . mysqli_real_escape_string($conn, $row["vn"]) . "'";
            }
            $dup_map = [];
            if(!empty($vns)) {
                $vns_str = implode(',', $vns);
                $q_cid = "SELECT cid, vstdate FROM imr_tb_debtor_rights_opd WHERE vn IN ($vns_str) AND cid IS NOT NULL AND cid != ''";
                $r_cid = mysqli_query($conn, $q_cid);
                $checks = [];
                if($r_cid) {
                    while ($d = mysqli_fetch_assoc($r_cid)) {
    if (isset($d['tel'])) $d['tel'] = decrypt_data($d['tel']);
                        $checks[] = "('" . mysqli_real_escape_string($conn, $d['cid']) . "','" . mysqli_real_escape_string($conn, $d['vstdate']) . "')";
                    }
                }
                if(!empty($checks)) {
                    $checks_str = implode(",", array_unique($checks));
                    $q_dup = "SELECT cid, vstdate, COUNT(vn) as cnt FROM imr_tb_debtor_rights_opd WHERE (cid, vstdate) IN ($checks_str) GROUP BY cid, vstdate HAVING COUNT(vn) > 1";
                    $r_dup = mysqli_query($conn, $q_dup);
                    if($r_dup) {
                        while ($d2 = mysqli_fetch_assoc($r_dup)) {
    if (isset($d2['tel'])) $d2['tel'] = decrypt_data($d2['tel']);
                            $dup_map[$d2['cid'].'_'.$d2['vstdate']] = $d2['cnt'];
                        }
                    }
                }
            }
            // รีเซ็ต pointer กลับไปเริ่มใหม่
            mysqli_data_seek($result, 0);

            // output data of each row
            $i = 1;
            $compensated = 0;
            $diff = 0;
            $debit = 0;
            $last_hn = ''; // เก็บค่า hn ก่อนหน้าเพื่อใช้ในการสลับสี
            $current_color = ''; // เก็บค่าสีพื้นหลังปัจจุบัน

            while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
                $vn = $row["vn"];
                $compensated1 = $row["compensated"];

                $dt = new DateTime($row["dttran"]);
                $dttran = $dt->format('d/m/') . ($dt->format('Y') + 543);

                $sql1 = "SELECT o.hn, o.ptname, o.clinic, o.debit, o.follow_money, o.mobile, o.accountname, o.cid, o.vstdate
                        FROM imr_tb_debtor_rights_opd o WHERE vn ='$vn' AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') ";

                $result1 = mysqli_query($conn, $sql1);

                if (mysqli_num_rows($result1) > 0) {
                    while ($row1 = mysqli_fetch_assoc($result1)) {
    if (isset($row1['cid'])) $row1['cid'] = decrypt_data($row1['cid']);
    if (isset($row1['tel'])) $row1['tel'] = decrypt_data($row1['tel']);
                        $diff1 = cleanNum($compensated1) - cleanNum($row1["debit"]);

                        // ตรวจสอบว่าค่า hn เปลี่ยนแปลงหรือไม่
                        if ($row["namepat"] != $last_hn) {
                            // สลับสีพื้นหลังเมื่อค่า hn เปลี่ยนแปลง
                            $current_color = ($current_color == '#07761e') ? '#0000ff' : '#07761e';
                            $last_hn = $row["namepat"]; // อัพเดตค่า hn
                        }

                        $debit_icon = '';
                        if (isset($row1["follow_money"]) && $row1["follow_money"] > 0) {
                            $row1["debit"] = cleanNum($row1["debit"]) - cleanNum($row1["follow_money"]); // ลูกหนี้สิทธิที่เหลือ
                            $diff1 = cleanNum($compensated1) - cleanNum($row1["debit"]); // คำนวณส่วนต่างใหม่
                            $debit_icon = ' <iconify-icon icon="fa6-solid:hand-holding-dollar" style="color: #f2a33c; font-size: 15px;" title="แบ่งจ่ายไปแล้ว ' . formatMoney($row1["follow_money"], 2) . '"></iconify-icon>';
                        }

                        $dup_badge_ckd1 = "";
                        $dup_key = encrypt_data($row1["cid"]).'_'.$row1["vstdate"];
                        if(isset($dup_map[$dup_key]) && $dup_map[$dup_key] > 1) {
                            $dup_badge_ckd1 = ' <span class="badge bg-danger" style="cursor:pointer; font-size: 10px; text-transform: capitalize; vertical-align: text-bottom;" onclick="viewDuplicateVisits(\''.$row1["cid"].'\', \''.$row1["vstdate"].'\')">'.$dup_map[$dup_key].' visit</span>';
                        }

                        echo '<tr>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $i . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $row1["mobile"] . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $row["stm_doc"] . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $vn . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $row1["hn"] . '</td>
                                <td style="color: ' . $current_color . ';">' . $row["namepat"] . $dup_badge_ckd1 . '</td>
                                <td style="color: ' . $current_color . ';">' . $row1["accountname"] . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $dttran . '</td>
                                <td style="color: ' . $current_color . '; text-align: right;">' . formatMoney($row1["debit"], 2) . $debit_icon . '</td>
                                <td style="color: ' . $current_color . '; text-align: right;">' . formatMoney($compensated1, 2) . '</td>
                                <td style="color: ' . $current_color . '; text-align: right;">' . formatMoney($diff1, 2) . '</td>
                              </tr>';

                        $diff = $diff + $diff1;
                        $debit = $debit + cleanNum($row1["debit"]);
                        $compensated = $compensated + cleanNum($row["compensated"]);
                    }
                }
                $i++;
            }

            echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            echo '</tbody>';
            echo '<tfoot>
                    <tr class="summary-total-row">
                      <td style="text-align:center;font-weight: bold;" colspan="8">รวม</td>
                      <td style="text-align: right;font-weight: bold;" id="o1">' . formatMoney($debit, 2) . '</td>
                      <td style="text-align: right;font-weight: bold;" id="o2">' . formatMoney($compensated, 2) . '</td>
                      <td style="text-align: right;font-weight: bold;" id="o3">' . formatMoney($diff, 2) . '</td>
                    </tr>
                  </tfoot>';
        } else {
            echo '</tbody>';
        }

        echo '</table>';



    } else {
        // กรณีเป็นโค้ดธรรมดา เช่น DCKD6707020007

        $sql = "SELECT dc.ptname, dc.rep, dc.vn, SUM(dc.compensated) AS compensated,dc.admdate
                FROM imr_tb_seamless_dckd dc
                WHERE dc.rep = '$ckdgetovlookup'
                GROUP BY dc.ptname,dc.vn
                ORDER BY dc.ptname,dc.admdate ASC";

        $result = mysqli_query($conn, $sql);

        echo '<table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>ลำดับ</th>
                    <th>เลขที่ใบเสร็จ</th>
                    <th>เลขหนังสือ</th>
                    <th>VN</th>
                    <th>HN</th>
                    <th>ชื่อ-สกุล</th>
                    <th>สิทธิการเงิน</th>
                    <th>วันที่</th>
                    <th>ลูกหนี้สิทธิ</th>
                    <th>เงินชดเชย</th>
                    <th>ส่วนต่าง</th>
                  </tr>
                </thead>
                <tbody>';

        if ($result && mysqli_num_rows($result) > 0) {
            // ดึง vn ทั้งหมดมาเช็คซ้ำรอบเดียว
            $vns = [];
            while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
                $vns[] = "'" . mysqli_real_escape_string($conn, $row["vn"]) . "'";
            }
            $dup_map = [];
            if(!empty($vns)) {
                $vns_str = implode(',', $vns);
                $q_cid = "SELECT cid, vstdate FROM imr_tb_debtor_rights_opd WHERE vn IN ($vns_str) AND cid IS NOT NULL AND cid != ''";
                $r_cid = mysqli_query($conn, $q_cid);
                $checks = [];
                if($r_cid) {
                    while ($d = mysqli_fetch_assoc($r_cid)) {
    if (isset($d['tel'])) $d['tel'] = decrypt_data($d['tel']);
                        $checks[] = "('" . mysqli_real_escape_string($conn, $d['cid']) . "','" . mysqli_real_escape_string($conn, $d['vstdate']) . "')";
                    }
                }
                if(!empty($checks)) {
                    $checks_str = implode(",", array_unique($checks));
                    $q_dup = "SELECT cid, vstdate, COUNT(vn) as cnt FROM imr_tb_debtor_rights_opd WHERE (cid, vstdate) IN ($checks_str) GROUP BY cid, vstdate HAVING COUNT(vn) > 1";
                    $r_dup = mysqli_query($conn, $q_dup);
                    if($r_dup) {
                        while ($d2 = mysqli_fetch_assoc($r_dup)) {
    if (isset($d2['tel'])) $d2['tel'] = decrypt_data($d2['tel']);
                            $dup_map[$d2['cid'].'_'.$d2['vstdate']] = $d2['cnt'];
                        }
                    }
                }
            }
            // รีเซ็ต pointer กลับไปเริ่มใหม่
            mysqli_data_seek($result, 0);

            // output data of each row
            $i = 1;
            $compensated = 0;
            $diff = 0;
            $debit = 0;
            $last_hn = ''; // เก็บค่า hn ก่อนหน้าเพื่อใช้ในการสลับสี
            $current_color = ''; // เก็บค่าสีพื้นหลังปัจจุบัน

            while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
                $vn = $row["vn"];
                $compensated1 = $row["compensated"];

                $sql1 = "SELECT o.hn, o.ptname, o.clinic, o.debit, o.follow_money, o.mobile, o.accountname, o.cid, o.vstdate
                        FROM imr_tb_debtor_rights_opd o WHERE vn ='$vn' AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') ";

                $result1 = mysqli_query($conn, $sql1);

                if (mysqli_num_rows($result1) > 0) {
                    while ($row1 = mysqli_fetch_assoc($result1)) {
    if (isset($row1['cid'])) $row1['cid'] = decrypt_data($row1['cid']);
    if (isset($row1['tel'])) $row1['tel'] = decrypt_data($row1['tel']);
                        $diff1 = cleanNum($compensated1) - cleanNum($row1["debit"]);

                        // ตรวจสอบว่าค่า hn เปลี่ยนแปลงหรือไม่
                        if ($row["ptname"] != $last_hn) {
                            // สลับสีพื้นหลังเมื่อค่า hn เปลี่ยนแปลง
                            $current_color = ($current_color == '#07761e') ? '#0000ff' : '#07761e';
                            $last_hn = $row["ptname"]; // อัพเดตค่า hn
                        }

                        $debit_icon = '';
                        if (isset($row1["follow_money"]) && $row1["follow_money"] > 0) {
                            $row1["debit"] = cleanNum($row1["debit"]) - cleanNum($row1["follow_money"]); // ลูกหนี้สิทธิที่เหลือ
                            $diff1 = cleanNum($compensated1) - cleanNum($row1["debit"]); // คำนวณส่วนต่างใหม่
                            $debit_icon = ' <iconify-icon icon="fa6-solid:hand-holding-dollar" style="color: #f2a33c; font-size: 15px;" title="แบ่งจ่ายไปแล้ว ' . formatMoney($row1["follow_money"], 2) . '"></iconify-icon>';
                        }

                        $dup_badge_ckd2 = "";
                        $dup_key = encrypt_data($row1["cid"]).'_'.$row1["vstdate"];
                        if(isset($dup_map[$dup_key]) && $dup_map[$dup_key] > 1) {
                            $dup_badge_ckd2 = ' <span class="badge bg-danger" style="cursor:pointer; font-size: 10px; text-transform: capitalize; vertical-align: text-bottom;" onclick="viewDuplicateVisits(\''.$row1["cid"].'\', \''.$row1["vstdate"].'\')">'.$dup_map[$dup_key].' visit</span>';
                        }

                        echo '<tr>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $i . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $row1["mobile"] . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $row["rep"] . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $vn . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $row1["hn"] . '</td>
                                <td style="color: ' . $current_color . ';">' . $row["ptname"] . $dup_badge_ckd2 . '</td>
                                <td style="color: ' . $current_color . ';">' . $row1["accountname"] . '</td>
                                <td style="color: ' . $current_color . '; text-align: center;">' . $row["admdate"] . '</td>
                                <td style="color: ' . $current_color . '; text-align: right;">' . formatMoney($row1["debit"], 2) . $debit_icon . '</td>
                                <td style="color: ' . $current_color . '; text-align: right;">' . formatMoney($compensated1, 2) . '</td>
                                <td style="color: ' . $current_color . '; text-align: right;">' . formatMoney($diff1, 2) . '</td>
                              </tr>';

                        $diff = $diff + $diff1;
                        $debit = $debit + cleanNum($row1["debit"]);
                        $compensated = $compensated + cleanNum($row["compensated"]);
                    }
                }
                $i++;
            }

            echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            echo '</tbody>';
            echo '<tfoot>
                    <tr class="summary-total-row">
                      <td style="text-align:center;font-weight: bold;" colspan="8">รวม</td>
                      <td style="text-align: right;font-weight: bold;" id="o1">' . formatMoney($debit, 2) . '</td>
                      <td style="text-align: right;font-weight: bold;" id="o2">' . formatMoney($compensated, 2) . '</td>
                      <td style="text-align: right;font-weight: bold;" id="o3">' . formatMoney($diff, 2) . '</td>
                    </tr>
                  </tfoot>';
        } else {
            echo '</tbody>';
        }

        echo '</table>';
    }
}


// --- ส่วนที่ 1: ดึงใบเสร็จเดิมมาแสดงใน Dropdown ---
if (isset($_POST['action_get_old_receipt'])) {
    $rep = mysqli_real_escape_string($conn, $_POST['action_get_old_receipt']);

    // ดึงข้อมูล mobile (ที่เก็บเลขใบเสร็จ) ที่ไม่ว่าง และไม่ใช่ขีด
    $sql = "SELECT DISTINCT mobile FROM imr_tb_debtor_rights_opd
            JOIN imr_tb_check_invoice stm ON stm.vn = imr_tb_debtor_rights_opd.vn
            WHERE stm.rep = '$rep' AND mobile IS NOT NULL AND mobile != '-' AND mobile != ''
            UNION
            SELECT DISTINCT mobile FROM imr_tb_debtor_rights_ipd
            JOIN imr_tb_check_invoice stm ON stm.vn = imr_tb_debtor_rights_ipd.an
            WHERE stm.rep = '$rep' AND mobile IS NOT NULL AND mobile != '-' AND mobile != ''";

    $result = mysqli_query($conn, $sql);

    echo '<option value="">-- เป็นการบันทึกใหม่ (ไม่มีใบเสร็จเดิม) --</option>';
    while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
        // แยกเอาแค่เลขใบเสร็จมาแสดง (สมมติ format คือ เลขที่-วันที่)
        $val = $row['mobile'];
        echo '<option value="'.$val.'">แก้ไขรายการ: '.$val.'</option>';
    }
    exit;
}


// --- ส่วนที่ 2: บันทึกข้อมูล (แก้ไขให้รองรับการ Edit) ---
if (isset($_POST['reptact'])) {

    $reptact = mysqli_real_escape_string($conn, $_POST['reptact']);
    $payment = mysqli_real_escape_string($conn, $_POST['payment']);
    $paymentdate = mysqli_real_escape_string($conn, $_POST['paymentdate']);
    $old_payment = mysqli_real_escape_string($conn, $_POST['old_payment']); // รับค่าใบเสร็จเดิม

    $textpay = $payment . "-" . $paymentdate;

    // สร้างเงื่อนไข WHERE: ถ้าเลือกใบเสร็จเดิม ให้แก้เฉพาะอันนั้น, ถ้าไม่เลือก ให้แก้พวกที่เป็น '-'
    $where_condition = "";
    if ($old_payment != "") {
        // กรณีแก้ไข: แก้เฉพาะแถวที่มีเลขใบเสร็จเดิมตรงกัน
        $where_condition = " AND mobile = '$old_payment' ";
    } else {
        // กรณีใหม่: แก้เฉพาะแถวที่ยังไม่มีเลขใบเสร็จ
        $where_condition = " AND (mobile = '-' OR mobile = '' OR mobile IS NULL) ";
    }

    // ดึง VN/AN ที่เกี่ยวข้องกับ REP นี้
    $sql = "SELECT o.vn, i.an
            FROM imr_tb_check_invoice stm
            LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = stm.vn
            LEFT JOIN imr_tb_debtor_rights_ipd i ON i.an = stm.vn
            WHERE stm.rep = '$reptact'";

    $result = mysqli_query($conn, $sql);
    $success_count = 0;

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
            $vn = $row["vn"];
            $an = $row["an"];

            if ($vn != '') {
                // อัพเดท OPD
                $sql11 = "UPDATE imr_tb_debtor_rights_opd SET discount_money = 'appeal' WHERE vn='$vn' AND mobile <> '-'";
                // บรรทัดนี้อาจต้องดู Logic พี่อีกทีว่า discount_money ต้องแก้ด้วยไหมเมื่อแก้ไขใบเสร็จ แต่ผมคงไว้ตามเดิมก่อน

                $sql1 = "UPDATE imr_tb_debtor_rights_opd SET mobile = '$textpay' WHERE vn='$vn' $where_condition";
                mysqli_query($conn, $sql1);
            }

            if ($an != '') {
                // อัพเดท IPD
                $sql22 = "UPDATE imr_tb_debtor_rights_ipd SET discount_money = 'appeal' WHERE an='$an' AND mobile <> '-'";

                $sql2 = "UPDATE imr_tb_debtor_rights_ipd SET mobile = '$textpay' WHERE an='$an' $where_condition";
                mysqli_query($conn, $sql2);
            }
        }

        // redirect กลับไป
        header('Location: VlookUP.php');
    } else {
        echo "ไม่พบข้อมูลรายการ REP นี้";
    }
}



// --- ส่วนที่ 3: ดึงใบเสร็จเดิมมาแสดง (สำหรับ CKD) ---
if (isset($_POST['action_get_old_receipt_ckd'])) {
    $rep = mysqli_real_escape_string($conn, $_POST['action_get_old_receipt_ckd']);
    $sql = "";

    // เช็คประเภท REP ว่าเป็น OFC (COCD) หรือ ปกติ
    if (strpos($rep, 'COCD') !== false) {
        // กรณี OFC
        $sql = "SELECT DISTINCT o.mobile
                FROM imr_tb_seamless_dckd_ofc ofc
                LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = ofc.vn
                WHERE ofc.stm_doc = '$rep' AND o.mobile IS NOT NULL AND o.mobile != '-' AND o.mobile != ''";
    } else {
        // กรณีปกติ (UCS, SSS, LGO)
        $sql = "SELECT DISTINCT o.mobile
                FROM imr_tb_seamless_dckd dc
                LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = dc.vn
                WHERE dc.rep = '$rep' AND o.mobile IS NOT NULL AND o.mobile != '-' AND o.mobile != ''";
    }

    $result = mysqli_query($conn, $sql);

    echo '<option value="">-- เป็นการบันทึกใหม่ (ไม่มีใบเสร็จเดิม) --</option>';
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
            $val = $row['mobile'];
            echo '<option value="'.$val.'">แก้ไขรายการ: '.$val.'</option>';
        }
    }
    exit;
}






// --- ส่วนที่ 4: บันทึกข้อมูล CKD (รองรับการแก้ไข) ---
if (isset($_POST['reptactckd'])) {

    $reptact = mysqli_real_escape_string($conn, $_POST['reptactckd']);
    $payment = mysqli_real_escape_string($conn, $_POST['paymentckd']);
    $paymentdate = mysqli_real_escape_string($conn, $_POST['paymentdateckd']);
    $old_payment = mysqli_real_escape_string($conn, $_POST['old_paymentckd']); // รับค่าใบเสร็จเดิม

    $textpay = $payment . "-" . $paymentdate;

    // สร้างเงื่อนไข WHERE สำหรับการ UPDATE
    $where_condition = "";
    if ($old_payment != "") {
        // กรณีแก้ไข: แก้เฉพาะ vn ที่มี mobile ตรงกับอันเก่า
        $where_condition = " AND mobile = '$old_payment' ";
    } else {
        // กรณีใหม่: แก้เฉพาะ vn ที่ยังไม่มีใบเสร็จ
        $where_condition = " AND (mobile = '-' OR mobile = '' OR mobile IS NULL) ";
    }

    if (strpos($reptact, 'COCD') !== false) {
        // --- กรณี OFC ---
        $sql = "SELECT o.vn
                FROM imr_tb_seamless_dckd_ofc ofc
                LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = ofc.vn
                WHERE stm_doc = '$reptact' AND ofc.vn <> ''
                GROUP BY ofc.vn
                ORDER BY o.vstdate ASC";

        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
                $vn = $row["vn"];
                // เพิ่ม $where_condition ต่อท้าย
                $sql1 = "UPDATE imr_tb_debtor_rights_opd SET mobile = '$textpay' WHERE vn='$vn' $where_condition";
                mysqli_query($conn, $sql1);
            }
            header('Location: VlookUP.php');
        } else {
             echo "ไม่พบข้อมูล VN สำหรับรายการนี้";
        }

    } else {
        // --- กรณีปกติ ---
        $sql = "SELECT o.vn
                FROM imr_tb_seamless_dckd dc
                LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = dc.vn
                WHERE rep = '$reptact' AND dc.vn <> ''
                GROUP BY dc.vn
                ORDER BY o.vstdate ASC";

        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
                $vn = $row["vn"];
                // เพิ่ม $where_condition ต่อท้าย
                $sql1 = "UPDATE imr_tb_debtor_rights_opd SET mobile = '$textpay' WHERE vn='$vn' $where_condition";
                mysqli_query($conn, $sql1);
            }
            header('Location: VlookUP.php');
        } else {
            echo "ไม่พบข้อมูล VN สำหรับรายการนี้";
        }
    }
}







if(isset($_POST['actionopdlist'])){

    $actionopdlist = mysqli_real_escape_string($conn, $_POST['actionopdlist']);

    $target_vns_str = "''";
    $target_vns_11504_str = "''";
    $target_vns_14429_str = "''";
    $target_vns_23576_str = "''";
    $vn_q = mysqli_query($conn, "SELECT vn FROM imr_tb_debtor_rights_opd WHERE accountcode = '1102050101.203' AND monthtxt = '$actionopdlist'");
    if($vn_q && mysqli_num_rows($vn_q) > 0) {
        $vn_list = [];
        while ($v = mysqli_fetch_assoc($vn_q)) {
    if (isset($v['cid'])) $v['cid'] = decrypt_data($v['cid']);
    if (isset($v['tel'])) $v['tel'] = decrypt_data($v['tel']);
            $vn_list[] = "'" . mysqli_real_escape_string($conn2, $v['vn']) . "'";
        }
        $in_vns = implode(",", $vn_list);
        $hosp_q = mysqli_query($conn2, "SELECT vn, hospmain FROM ovst WHERE vn IN ($in_vns) AND hospmain IN ('11504', '14429', '23576')");
        if($hosp_q) {
            $target_vns = [];
            $vns_11504 = [];
            $vns_14429 = [];
            $vns_23576 = [];
            while ($h = mysqli_fetch_assoc($hosp_q)) {
    if (isset($h['cid'])) $h['cid'] = decrypt_data($h['cid']);
    if (isset($h['tel'])) $h['tel'] = decrypt_data($h['tel']);
                $target_vns[] = "'" . $h['vn'] . "'";
                if ($h['hospmain'] == '11504') $vns_11504[] = "'" . $h['vn'] . "'";
                elseif ($h['hospmain'] == '14429') $vns_14429[] = "'" . $h['vn'] . "'";
                elseif ($h['hospmain'] == '23576') $vns_23576[] = "'" . $h['vn'] . "'";
            }
            if(count($target_vns) > 0) $target_vns_str = implode(",", $target_vns);
            if(count($vns_11504) > 0) $target_vns_11504_str = implode(",", $vns_11504);
            if(count($vns_14429) > 0) $target_vns_14429_str = implode(",", $vns_14429);
            if(count($vns_23576) > 0) $target_vns_23576_str = implode(",", $vns_23576);
        }
    }

    // แก้ไข SQL: เปลี่ยนมาใช้ JOIN แบบมาตรฐาน เพื่อรองรับ MySQL เวอร์ชั่นเก่า
    $sql = "
        SELECT
            main.pttype_key,
            main.accountname,
            main.accountcode,
            main.visitall, main.visitdiff, main.incomeall, main.incomediff, main.incometotall, main.follow_money,
            main.sort_type,
            ordering.total_cvn AS max_group_visit
        FROM (
            SELECT
                -- 1. ทุกผังบัญชีถ้าเป็นเคสไต ให้พ่วง _KIDNEY ต่อท้ายรหัสผังบัญชีเพื่อแยกแถว
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN CONCAT(o.accountcode, '_KIDNEY')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN CONCAT(o.accountcode, '_HOSP_11504')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN CONCAT(o.accountcode, '_HOSP_14429')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN CONCAT(o.accountcode, '_HOSP_23576')
                    ELSE o.accountcode
                END AS pttype_key,

                -- 2. ดักเปลี่ยนชื่อสิทธิหลักแบบ Dynamic: เปลี่ยน 'ลูกหนี้ค่ารักษา' เป็น 'ผู้ป่วยไตวายเรื้อรัง'
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN
                        REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN
                        REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ รพ.ค่าย เบิก นค.')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN
                        REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 1 วัดเหนือ เบิก นค.')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN
                        REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 2 สระสิม เบิก นค.')
                    ELSE o.accountname
                END AS accountname,

                o.accountcode,
                -- 3. กำหนดค่าน้ำหนัก (แถวปกติ=1 ขึ้นก่อน, แถวไต=2 อยู่ข้างล่างใต้ผังตัวเอง)
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN 2
                    WHEN (o.accountcode = '1102050101.203' AND (o.vn IN ($target_vns_11504_str) OR o.vn IN ($target_vns_14429_str) OR o.vn IN ($target_vns_23576_str))) THEN 2
                    ELSE 1
                END AS sort_type,
                COUNT(o.income) AS visitall,
                COUNT(CASE WHEN o.income <> 0 AND o.debit <> 0 THEN 1 END) AS visitdiff,
                SUM(o.income) AS incomeall,
                SUM(o.income) - SUM(COALESCE(o.original_debit, o.debit)) AS incomediff,
                SUM(o.debit) AS incometotall,
                SUM(
                    IFNULL(o.follow_money, 0)
                  + IFNULL((SELECT SUM(compensated) FROM imr_tb_check_invoice WHERE vn = o.vn), 0)
                  + IFNULL((SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc WHERE vn = o.vn), 0)
                  + IFNULL((SELECT SUM(compensated) FROM imr_tb_seamless_dckd WHERE vn = o.vn), 0)
                ) AS follow_money
            FROM imr_tb_debtor_rights_opd o
            WHERE o.monthtxt = '$actionopdlist'
            GROUP BY
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN CONCAT(o.accountcode, '_KIDNEY')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN CONCAT(o.accountcode, '_HOSP_11504')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN CONCAT(o.accountcode, '_HOSP_14429')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN CONCAT(o.accountcode, '_HOSP_23576')
                    ELSE o.accountcode
                END,
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN
                        REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN
                        REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ รพ.ค่าย เบิก นค.')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN
                        REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 1 วัดเหนือ เบิก นค.')
                    WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN
                        REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 2 สระสิม เบิก นค.')
                    ELSE o.accountname
                END,
                o.accountcode
        ) AS main
        JOIN (
            -- หาผลรวมยอดจำนวนสถิติรายผังบัญชี เพื่อจัดเรียงกลุ่ม (ใช้แทน OVER PARTITION BY)
            SELECT accountcode, COUNT(vn) AS total_cvn
            FROM imr_tb_debtor_rights_opd
            WHERE monthtxt = '$actionopdlist'
            GROUP BY accountcode
        ) AS ordering ON main.accountcode = ordering.accountcode
        ORDER BY ordering.total_cvn DESC, main.accountcode ASC, main.sort_type ASC
    ";

    $result = mysqli_query($conn, $sql);

    // ดัก Error ป้องกันจอดำ
    if ($result) {

        if ($result && mysqli_num_rows($result) > 0) {

            $cr_config = get_active_cr_config($conn);
            $is_cr_effective = is_cr_effective_for_month($cr_config, $actionopdlist);
            $auto_split = is_cr_auto_split_enabled($cr_config, 'OPD');
            $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
            $cr_summary = null;
            if ($is_cr_effective && $auto_split) {
                $cr_summary = get_cr_splitting_summary_for_month($conn, $conn2, $actionopdlist, 'OPD', $cr_config, $my_hospcode);
            }

            $sss_config = get_active_sss_config($conn);
            $is_sss_effective = is_sss_effective_for_month($sss_config, $actionopdlist);
            $auto_split_sss = is_sss_auto_split_enabled($sss_config, 'OPD');
            $sss_summary = null;
            if ($is_sss_effective && $auto_split_sss) {
                $sss_summary = get_sss_splitting_summary_for_month($conn, $conn2, $actionopdlist, 'OPD', $sss_config, $my_hospcode);
            }

            $i=1;$visitall=0;$visitdiff=0;$incomeall=0;$incomediff=0;$incometotall=0;$follow_money=0;$totaldiffa=0;

            while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);

                $accountcode = mysqli_real_escape_string($conn, $row["accountcode"]);

                $is_kidney = (strpos($row["pttype_key"], '_KIDNEY') !== false);
                $is_hosp_11504 = (strpos($row["pttype_key"], '_HOSP_11504') !== false);
                $is_hosp_14429 = (strpos($row["pttype_key"], '_HOSP_14429') !== false);
                $is_hosp_23576 = (strpos($row["pttype_key"], '_HOSP_23576') !== false);
                $is_hospmain = ($is_hosp_11504 || $is_hosp_14429 || $is_hosp_23576);

                // ปรับยอด CR Cross-Account Splitting (ลดผังแม่ .201, .209, .203 และเพิ่มผัง .216)
                if ($cr_summary && !$is_kidney && !$is_hospmain) {
                    if (isset($cr_summary['transfers_by_account'][$accountcode])) {
                        $tr = $cr_summary['transfers_by_account'][$accountcode];
                        $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr['transfer_out']);
                        $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr['transfer_out']);
                        $row["visitall"] = max(0, intval($row["visitall"]) - $tr['full_transfer_cases']);
                        $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr['full_transfer_cases']);
                        if (isset($tr['comp_out'])) {
                            $row["follow_money"] = max(0, cleanNum($row["follow_money"]) - $tr['comp_out']);
                        }
                    } elseif ($accountcode === '1102050101.216') {
                        $row["incometotall"] = $cr_summary['cr_non_kidney_received_total'];
                        $row["incomeall"] = $cr_summary['cr_non_kidney_received_total'];
                        $row["incomediff"] = 0.0;
                        $row["visitall"] = $cr_summary['cr_non_kidney_count'];
                        $row["visitdiff"] = $cr_summary['cr_non_kidney_count'];
                        if (isset($cr_summary['cr_non_kidney_received_comp'])) {
                            $row["follow_money"] = $cr_summary['cr_non_kidney_received_comp'];
                        }
                    }
                }

                // ปรับยอด SSS Instrument Cross-Account Splitting (ลดผังแม่ .301, .303, .307, .308 และเพิ่มผัง .309)
                if ($sss_summary && !$is_kidney && !$is_hospmain) {
                    if (isset($sss_summary['transfers_by_account'][$accountcode])) {
                        $tr_sss = $sss_summary['transfers_by_account'][$accountcode];
                        $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr_sss['transfer_out']);
                        $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr_sss['transfer_out']);
                        $row["visitall"] = max(0, intval($row["visitall"]) - $tr_sss['full_transfer_cases']);
                        $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr_sss['full_transfer_cases']);
                        if (isset($tr_sss['comp_out'])) {
                            $row["follow_money"] = max(0, cleanNum($row["follow_money"]) - $tr_sss['comp_out']);
                        }
                    } elseif ($accountcode === '1102050101.309') {
                        $row["incometotall"] = cleanNum($row["incometotall"]) + cleanNum($sss_summary['sss_received_total']);
                        $row["incomeall"] = cleanNum($row["incomeall"]) + cleanNum($sss_summary['sss_received_total']);
                        $row["incomediff"] = 0.0;
                        $row["visitall"] = $sss_summary['sss_count'];
                        $row["visitdiff"] = $sss_summary['sss_count'];
                        if (isset($sss_summary['sss_received_comp'])) {
                            $row["follow_money"] = cleanNum($row["follow_money"]) + cleanNum($sss_summary['sss_received_comp']);
                        }
                    }
                }

                if ($is_kidney && $accountcode === '1102050101.216' && isset($cr_summary['cr_kidney_received_comp'])) {
                    $row["follow_money"] = $cr_summary['cr_kidney_received_comp'];
                }

                if ($is_kidney) {
                    $sub_condition = "AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%')";
                } elseif ($is_hosp_11504) {
                    $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') AND vn IN ($target_vns_11504_str)";
                } elseif ($is_hosp_14429) {
                    $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') AND vn IN ($target_vns_14429_str)";
                } elseif ($is_hosp_23576) {
                    $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') AND vn IN ($target_vns_23576_str)";
                } else {
                    if ($accountcode == '1102050101.203') {
                        $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') AND vn NOT IN ($target_vns_str)";
                    } else {
                        $sub_condition = "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%')";
                    }
                }

                $sqlzzx = "SELECT
                                COUNT(accountcode) AS qqq,
                                COUNT(bill) AS www,
                                CASE
                                    WHEN COUNT(accountcode) = COUNT(bill) THEN 'Y'
                                    ELSE 'N'
                                END AS status_check
                            FROM imr_tb_debtor_rights_opd
                            WHERE accountcode='$accountcode'
                            AND monthtxt='$actionopdlist' $sub_condition";

                $resultzzx = $conn->query($sqlzzx);

                if ($resultzzx) {
                    $rowzzx = $resultzzx->fetch_assoc();
                    
                    if ($accountcode == '1102050101.201' || $accountcode == '1102050101.209') {
                        $totaldiff = 0;
                    } else {
                        if ($rowzzx['status_check'] == 'Y') {
                            if ($row["follow_money"] > 0) {
                                $totaldiff = cleanNum($row["incometotall"]) - cleanNum($row["follow_money"]);
                            } else {
                                $totaldiff = 0;
                            }
                        } else {
                            $totaldiff = cleanNum($row["incometotall"]) - cleanNum($row["follow_money"]);
                        }
                    }
                } else {
                    // แจ้งเตือนข้อผิดพลาด Sub query ถ้ามีปัญหา
                    echo "Error in sub-query: " . $conn->error;
                }

                $perdebit = (cleanNum($row["incometotall"]) > 0) ? (cleanNum($row["follow_money"]) / cleanNum($row["incometotall"])) * 100 : 0;
                $pertotaldiff = (cleanNum($row["incometotall"]) > 0) ? (cleanNum($totaldiff) / cleanNum($row["incometotall"])) * 100 : 0;

                $display_id = $row["accountcode"];

                $display_name = ($is_kidney || $is_hospmain) ? '&nbsp;&nbsp;&nbsp;&nbsp;' . $row["accountname"] : $row["accountname"];

                $link_style = ($is_kidney || $is_hospmain) ? 'style="color: #cd641f; font-weight: bold;"' : '';

                echo '<tr>
                <td style="width: 5%;">&nbsp;<a href="#" onclick="viewopdlist(\''.$row["pttype_key"].'\')">'.$display_id.'</a></td>

                <td style="width: 32.2%; text-align: left;">&nbsp;<a href="#" '.$link_style.' onclick="viewopdlist(\''.$row["pttype_key"].'\')">'.$display_name.'</a></td>

                <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$row["visitall"],0).'</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$row["visitdiff"],0).'</td>
                <td style="text-align: right;">&nbsp;'.formatMoney($row["incomeall"], 2).'</td>
                <td style="text-align: right;">&nbsp;'.formatMoney($row["incomediff"], 2).'</td>
                <td style="text-align: right;">&nbsp;'.formatMoney($row["incometotall"], 2).'</td>
                <td style="text-align: right;color: #00b215;">&nbsp;'.formatMoney($row["follow_money"], 2).'</td>
                <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($totaldiff, 2).'</td>
                </tr>';

                $visitall = $visitall + cleanNum($row["visitall"]);
                $visitdiff = $visitdiff + cleanNum($row["visitdiff"]);
                $incomeall = $incomeall + cleanNum($row["incomeall"]);
                $incomediff = $incomediff + cleanNum($row["incomediff"]);
                $incometotall = $incometotall + cleanNum($row["incometotall"]);
                $follow_money = $follow_money + cleanNum($row["follow_money"]);
                $totaldiffa = $totaldiffa + cleanNum($totaldiff);
            }

            echo '<tr style="font-weight: bold;">
            <td style="width: 37.2%; height: 18px; text-align: center;" colspan="2">รวม</td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$visitall,0).'</td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$visitdiff,0).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($incomeall, 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($incomediff, 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($incometotall, 2).'</td>
            <td style="text-align: right;color: #00b215; font-weight: bold;">&nbsp;'.formatMoney($follow_money, 2).'</td>
            <td style="text-align: right;color: #cd641f; font-weight: bold;">&nbsp;'.formatMoney($totaldiffa, 2).'</td>
            </tr>';

        } else {
            echo '<tr><td colspan="9" style="text-align:center;">ไม่มีข้อมูล</td></tr>';
        }
    } else {
        // ส่วนนี้จะทำงานเมื่อ SQL รันไม่ผ่าน
        echo '<tr><td colspan="9" style="text-align:center; color:red; font-weight:bold; padding: 20px;">';
        echo 'เกิดข้อผิดพลาดในการดึงข้อมูล SQL: ' . mysqli_error($conn) . '<br><br>';
        echo '<div style="text-align:left; background:#f5f5f5; padding:10px; border-radius:5px; font-weight:normal; color:#333;">';
        echo 'คำสั่ง SQL ที่มีปัญหา: <br>' . htmlspecialchars($sql) . '</div>';
        echo '</td></tr>';
    }
}










if(isset($_POST['actionipdlist'])){

    $actionipdlist = mysqli_real_escape_string($conn, $_POST['actionipdlist']);

    // ปรับ Query ให้ GROUP BY และ SELECT ตาม accountcode พร้อมเรียงจาก visitall มาก -> น้อย
    $sql = "SELECT
                o.accountcode,
                o.accountname,
                COUNT(o.income) AS visitall,
                COUNT(CASE WHEN o.income <> 0 AND o.debit <> 0 THEN 1 END) AS visitdiff,
                SUM(o.income) AS incomeall,
                SUM(o.income) - SUM(COALESCE(o.original_debit, o.debit)) AS incomediff,
                SUM(o.debit) AS incometotall,
                (COALESCE(SUM(o.follow_money), 0) + COALESCE(SUM(stm.compensated), 0)) AS follow_money
            FROM imr_tb_debtor_rights_ipd o
                LEFT JOIN imr_tb_check_invoice stm ON stm.vn = o.an
            WHERE o.monthtxt = '$actionipdlist'
            GROUP BY o.accountcode, o.accountname
            ORDER BY visitall DESC";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {

        $cr_config_ipd = get_active_cr_config($conn);
        $is_cr_effective_ipd = is_cr_effective_for_month($cr_config_ipd, $actionipdlist);
        $auto_split_ipd = is_cr_auto_split_enabled($cr_config_ipd, 'IPD');
        $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
        $cr_summary_ipd = null;
        if ($is_cr_effective_ipd && $auto_split_ipd) {
            $cr_summary_ipd = get_cr_splitting_summary_for_month($conn, $conn2, $actionipdlist, 'IPD', $cr_config_ipd, $my_hospcode);
        }

        $sss_config_ipd = get_active_sss_config($conn);
        $is_sss_effective_ipd = is_sss_effective_for_month($sss_config_ipd, $actionipdlist);
        $auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config_ipd, 'IPD');
        $sss_summary_ipd = null;
        if ($is_sss_effective_ipd && $auto_split_sss_ipd) {
            $sss_summary_ipd = get_sss_splitting_summary_for_month($conn, $conn2, $actionipdlist, 'IPD', $sss_config_ipd, $my_hospcode);
        }

        $i=1;$visitall=0;$visitdiff=0;$incomeall=0;$incomediff=0;$incometotall=0;$follow_money=0;$totaldiffa=0;
        $rendered_217 = false;
        $rendered_310 = false;

        while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);

            // ปรับยอด CR Cross-Account Splitting (ลดผังแม่ .202 และเพิ่มผัง .217)
            if ($cr_summary_ipd) {
                if (isset($cr_summary_ipd['transfers_by_account'][$row['accountcode']])) {
                    $tr_ipd = $cr_summary_ipd['transfers_by_account'][$row['accountcode']];
                    $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr_ipd['transfer_out']);
                    $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr_ipd['transfer_out']);
                    $row["visitall"] = max(0, intval($row["visitall"]) - $tr_ipd['full_transfer_cases']);
                    $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr_ipd['full_transfer_cases']);
                    if (isset($tr_ipd['comp_out'])) {
                        $row["follow_money"] = max(0, cleanNum($row["follow_money"]) - $tr_ipd['comp_out']);
                    }
                } elseif ($row['accountcode'] === '1102050101.217') {
                    $rendered_217 = true;
                    $row["incometotall"] = cleanNum($cr_summary_ipd['cr_received_total']);
                    $row["incomeall"] = cleanNum($cr_summary_ipd['cr_received_total']);
                    $row["incomediff"] = 0.0;
                    $row["visitall"] = count($cr_summary_ipd['cr_visits']);
                    $row["visitdiff"] = count($cr_summary_ipd['cr_visits']);
                    if (isset($cr_summary_ipd['cr_received_comp'])) {
                        $row["follow_money"] = $cr_summary_ipd['cr_received_comp'];
                    }
                }
            }

            // ปรับยอด SSS Instrument Cross-Account Splitting (ลดผังแม่ .302, .304 และเพิ่มผัง .310)
            if ($sss_summary_ipd) {
                if (isset($sss_summary_ipd['transfers_by_account'][$row['accountcode']])) {
                    $tr_sss_ipd = $sss_summary_ipd['transfers_by_account'][$row['accountcode']];
                    $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr_sss_ipd['transfer_out']);
                    $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr_sss_ipd['transfer_out']);
                    $row["visitall"] = max(0, intval($row["visitall"]) - $tr_sss_ipd['full_transfer_cases']);
                    $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr_sss_ipd['full_transfer_cases']);
                    if (isset($tr_sss_ipd['comp_out'])) {
                        $row["follow_money"] = max(0, cleanNum($row["follow_money"]) - $tr_sss_ipd['comp_out']);
                    }
                } elseif ($row['accountcode'] === '1102050101.310') {
                    $rendered_310 = true;
                    $row["incometotall"] = cleanNum($row["incometotall"]) + cleanNum($sss_summary_ipd['sss_received_total']);
                    $row["incomeall"] = cleanNum($row["incomeall"]) + cleanNum($sss_summary_ipd['sss_received_total']);
                    $row["incomediff"] = 0.0;
                    $row["visitall"] = count($sss_summary_ipd['sss_visits']);
                    $row["visitdiff"] = count($sss_summary_ipd['sss_visits']);
                    if (isset($sss_summary_ipd['sss_received_comp'])) {
                        $row["follow_money"] = cleanNum($row["follow_money"]) + cleanNum($sss_summary_ipd['sss_received_comp']);
                    }
                }
            }

            $totaldiff = cleanNum($row["incometotall"]) - cleanNum($row["follow_money"]);

            $perdebit = (cleanNum($row["incometotall"]) > 0) ? (cleanNum($row["follow_money"]) / cleanNum($row["incometotall"])) * 100 : 0;
            $pertotaldiff = (cleanNum($row["incometotall"]) > 0) ? (cleanNum($totaldiff) / cleanNum($row["incometotall"])) * 100 : 0;

            // ส่งค่า accountcode ไปที่ viewipdlist() พร้อมใส่ \' ครอบสตริงไว้ให้ปลอดภัยครับ
            echo '<tr>
            <td style="width: 5%;">&nbsp;<a href="#" onclick="viewipdlist(\''.$row["accountcode"].'\')">'.$row["accountcode"].'</a></td>
            <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" onclick="viewipdlist(\''.$row["accountcode"].'\')">'.$row["accountname"].'</a></td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$row["visitall"],0).'</td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$row["visitdiff"],0).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($row["incomeall"], 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($row["incomediff"], 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($row["incometotall"], 2).'</td>
            <td style="text-align: right;color: #00b215;">&nbsp;'.formatMoney($row["follow_money"], 2).'</td>
            <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($totaldiff, 2).'</td>
            </tr>';

            $visitall = $visitall + cleanNum($row["visitall"]);
            $visitdiff = $visitdiff + cleanNum($row["visitdiff"]);
            $incomeall = $incomeall + cleanNum($row["incomeall"]);
            $incomediff = $incomediff + cleanNum($row["incomediff"]);
            $incometotall = $incometotall + cleanNum($row["incometotall"]);
            $follow_money = $follow_money + cleanNum($row["follow_money"]);
            $totaldiffa = $totaldiffa + cleanNum($totaldiff);
        }

        // 💡 [CR IPD Engine Standard] แสดงแถวผังลูกจำลอง 1102050101.217 อัตโนมัติหากไม่มีในฐานข้อมูลดิบ
        if (!$rendered_217 && $cr_summary_ipd && count($cr_summary_ipd['cr_visits']) > 0) {
            $acc217 = '1102050101.217';
            $accname217 = 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)';
            $cnt217 = count($cr_summary_ipd['cr_visits']);
            $tot217 = $cr_summary_ipd['cr_received_total'];
            $diff217 = $tot217;

            echo '<tr>
            <td style="width: 5%;">&nbsp;<a href="#" onclick="viewipdlist(\''.$acc217.'\')">'.$acc217.'</a></td>
            <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" onclick="viewipdlist(\''.$acc217.'\')">'.$accname217.'</a></td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$cnt217,0).'</td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$cnt217,0).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($tot217, 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney(0, 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($tot217, 2).'</td>
            <td style="text-align: right;color: #00b215;">&nbsp;0.00</td>
            <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($diff217, 2).'</td>
            </tr>';

            $visitall += $cnt217;
            $visitdiff += $cnt217;
            $incomeall += $tot217;
            $incometotall += $tot217;
            $totaldiffa += $diff217;
        }

        // 💡 [SSS IPD Engine Standard] แสดงแถวผังลูกจำลอง 1102050101.310 อัตโนมัติหากไม่มีในฐานข้อมูลดิบ
        if (!$rendered_310 && $sss_summary_ipd && count($sss_summary_ipd['sss_visits']) > 0) {
            $acc310 = '1102050101.310';
            $accname310 = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง IP';
            $cnt310 = count($sss_summary_ipd['sss_visits']);
            $tot310 = $sss_summary_ipd['sss_received_total'];
            $diff310 = $tot310;

            echo '<tr>
            <td style="width: 5%;">&nbsp;<a href="#" onclick="viewipdlist(\''.$acc310.'\')">'.$acc310.'</a></td>
            <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" onclick="viewipdlist(\''.$acc310.'\')">'.$accname310.'</a></td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$cnt310,0).'</td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$cnt310,0).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($tot310, 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney(0, 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($tot310, 2).'</td>
            <td style="text-align: right;color: #00b215;">&nbsp;0.00</td>
            <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($diff310, 2).'</td>
            </tr>';

            $visitall += $cnt310;
            $visitdiff += $cnt310;
            $incomeall += $tot310;
            $incometotall += $tot310;
            $totaldiffa += $diff310;
        }

        $perstm = ($incometotall > 0) ? ($follow_money / $incometotall) * 100 : 0;
        $perdiff = ($incometotall > 0) ? ($totaldiffa / $incometotall) * 100 : 0;

        echo '<tr style="font-weight: bold;">
            <td style="width: 37.2%; height: 18px; text-align: center;" colspan="2">รวม</td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$visitall,0).'</td>
            <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$visitdiff,0).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($incomeall, 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($incomediff, 2).'</td>
            <td style="text-align: right;">&nbsp;'.formatMoney($incometotall, 2).'</td>
            <td style="text-align: right;color: #00b215; font-weight: bold;">&nbsp;'.formatMoney($follow_money, 2).'</td>
            <td style="text-align: right;color: #cd641f; font-weight: bold;">&nbsp;'.formatMoney($totaldiffa, 2).'</td>
        </tr>';

    }else{
        echo 'ไม่มีข้อมูล';
    }
}






if(isset($_POST['actionopdlistonly'])){

  $actionopdlistonly = mysqli_real_escape_string($conn, $_POST['actionopdlistonly']);
  $id = mysqli_real_escape_string($conn, $_POST['oid']); // รับค่าแบบ Dynamic (เช่น '1102050101.401' หรือ '1102050101.401_KIDNEY')

  $_SESSION['monthtxt'] = $actionopdlistonly;
  $_SESSION['oid'] = $id; // บันทึกค่าเต็มลงเซสชันเพื่อให้หน้าพิมพ์รายงานนำไปดักกรองต่อได้อย่างแม่นยำ

    $target_vns_str = "''";
    $target_vns_11504_str = "''";
    $target_vns_14429_str = "''";
    $target_vns_23576_str = "''";
    $vn_q = mysqli_query($conn, "SELECT o.vn FROM imr_tb_debtor_rights_opd o WHERE o.accountcode = '1102050101.203' AND o.monthtxt = '$actionopdlistonly'");
    if($vn_q && mysqli_num_rows($vn_q) > 0) {
        $vn_list = [];
        while ($v = mysqli_fetch_assoc($vn_q)) {
            if (isset($v['cid'])) $v['cid'] = decrypt_data($v['cid']);
            if (isset($v['tel'])) $v['tel'] = decrypt_data($v['tel']);
            $vn_list[] = "'" . mysqli_real_escape_string($conn2, $v['vn']) . "'";
        }
        $in_vns = implode(",", $vn_list);
        $hosp_q = mysqli_query($conn2, "SELECT vn, hospmain FROM ovst WHERE vn IN ($in_vns) AND hospmain IN ('11504', '14429', '23576')");
        if($hosp_q) {
            $target_vns = [];
            $vns_11504 = [];
            $vns_14429 = [];
            $vns_23576 = [];
            while ($h = mysqli_fetch_assoc($hosp_q)) {
                if (isset($h['cid'])) $h['cid'] = decrypt_data($h['cid']);
                if (isset($h['tel'])) $h['tel'] = decrypt_data($h['tel']);
                $target_vns[] = "'" . $h['vn'] . "'";
                if ($h['hospmain'] == '11504') $vns_11504[] = "'" . $h['vn'] . "'";
                elseif ($h['hospmain'] == '14429') $vns_14429[] = "'" . $h['vn'] . "'";
                elseif ($h['hospmain'] == '23576') $vns_23576[] = "'" . $h['vn'] . "'";
            }
            if(count($target_vns) > 0) $target_vns_str = implode(",", $target_vns);
            if(count($vns_11504) > 0) $target_vns_11504_str = implode(",", $vns_11504);
            if(count($vns_14429) > 0) $target_vns_14429_str = implode(",", $vns_14429);
            if(count($vns_23576) > 0) $target_vns_23576_str = implode(",", $vns_23576);
        }
    }

    // 1. ดึงการตั้งค่า CR Splitting สำหรับ OPD
    $cr_config = get_active_cr_config($conn);
    $auto_split_opd = is_cr_auto_split_enabled($cr_config, 'OPD');
    $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
    $is_cr_effective = is_cr_effective_for_month($cr_config, $actionopdlistonly);
    $all_cr_visits = [];

    if ($is_cr_effective && $auto_split_opd) {
        $s = get_cr_splitting_summary_for_month($conn, $conn2, $actionopdlistonly, 'OPD', $cr_config, $my_hospcode);
        if ($s && !empty($s['cr_visits'])) {
            $all_cr_visits = $s['cr_visits'];
        }
    }

    // 1.1 ดึงการตั้งค่า SSS Splitting สำหรับ OPD
    $sss_config = get_active_sss_config($conn);
    $auto_split_sss_opd = is_sss_auto_split_enabled($sss_config, 'OPD');
    $is_sss_effective = is_sss_effective_for_month($sss_config, $actionopdlistonly);
    $all_sss_visits = [];

    if ($is_sss_effective && $auto_split_sss_opd) {
        $s_sss = get_sss_splitting_summary_for_month($conn, $conn2, $actionopdlistonly, 'OPD', $sss_config, $my_hospcode);
        if ($s_sss && !empty($s_sss['sss_visits'])) {
            $all_sss_visits = $s_sss['sss_visits'];
        }
    }

    $is_kidney_click = (strpos($id, '_KIDNEY') !== false);
    $is_target_216 = ($id === '1102050101.216');
    $is_target_309 = ($id === '1102050101.309');
    $is_sss_parent_opd = in_array($id, ['1102050101.301', '1102050101.303', '1102050101.307', '1102050101.308']);

    // [จัดการเงื่อนไขคัดกรองข้อมูลระดับ Dynamic]
    if ($is_kidney_click) {
        $base_account = str_replace('_KIDNEY', '', $id);
        $condition = "AND o.accountcode = '$base_account' AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%')";
    } elseif (strpos($id, '_HOSP_11504') !== false) {
        $base_account = str_replace('_HOSP_11504', '', $id);
        $condition = "AND o.accountcode = '$base_account' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') AND o.vn IN ($target_vns_11504_str)";
    } elseif (strpos($id, '_HOSP_14429') !== false) {
        $base_account = str_replace('_HOSP_14429', '', $id);
        $condition = "AND o.accountcode = '$base_account' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') AND o.vn IN ($target_vns_14429_str)";
    } elseif (strpos($id, '_HOSP_23576') !== false) {
        $base_account = str_replace('_HOSP_23576', '', $id);
        $condition = "AND o.accountcode = '$base_account' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') AND o.vn IN ($target_vns_23576_str)";
    } else {
        if ($is_target_216 && !empty($all_cr_visits)) {
            $transfer_vns = array_keys($all_cr_visits);
            $transfer_vns_quoted = !empty($transfer_vns) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_vns)) . "'" : "''";
            $condition = "AND ((o.accountcode = '1102050101.216') OR (o.vn IN ($transfer_vns_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        } elseif ($is_target_309 && !empty($all_sss_visits)) {
            $transfer_sss_vns = array_keys($all_sss_visits);
            $transfer_sss_vns_quoted = !empty($transfer_sss_vns) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_sss_vns)) . "'" : "''";
            $condition = "AND ((o.accountcode = '1102050101.309') OR (o.vn IN ($transfer_sss_vns_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        } elseif ($id == '1102050101.203') {
            $condition = "AND o.accountcode = '$id' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') AND o.vn NOT IN ($target_vns_str)";
        } else {
            $condition = "AND o.accountcode = '$id' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        }
    }

    $sql = "SELECT
              o.vn, o.hn, o.cid, o.ptname, o.vstdate, o.pttypename,
              (o.income) as income,
              (o.income - COALESCE(o.original_debit, o.debit)) as incomediff,
              (o.debit) as debit,
              o.follow_money, o.bill, o.billdate,
              CASE
                  WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', 'ผู้ป่วยไตวายเรื้อรัง')
                  WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_11504_str)) THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ รพ.ค่าย เบิก นค.')
                  WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_14429_str)) THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 1 วัดเหนือ เบิก นค.')
                  WHEN (o.accountcode = '1102050101.203' AND o.vn IN ($target_vns_23576_str)) THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 2 สระสิม เบิก นค.')
                  ELSE o.accountname
              END AS accountname,
              o.accountcode,
              o.mobile, o.pttypename
            FROM imr_tb_debtor_rights_opd o
            WHERE monthtxt = '$actionopdlistonly' $condition";

    // ดึงข้อมูลชื่อผังบัญชี / สิทธิการเงิน สำหรับแสดงที่หัวตาราง Modal
    $title_acc_name = '';
    $title_acc_code = $id;

    if ($is_target_216 || strpos($id, '1102050101.216') !== false) {
        $title_acc_code = '1102050101.216';
        $title_acc_name = 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
    } elseif ($is_target_309 || strpos($id, '1102050101.309') !== false) {
        $title_acc_code = '1102050101.309';
        $title_acc_name = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP';
    } else {
        $base_code = strpos($id, '_') !== false ? explode('_', $id)[0] : $id;
        $account_info_q = mysqli_query($conn, "SELECT accountcode, accountname FROM imr_tb_debtor_rights_opd WHERE accountcode = '$base_code' LIMIT 1");
        if ($account_info_q && $acc_row = mysqli_fetch_assoc($account_info_q)) {
            $title_acc_name = $acc_row['accountname'];
            $title_acc_code = $acc_row['accountcode'];
        }
    }

    if (strpos($id, '_KIDNEY') !== false) {
        $title_acc_name = str_replace('ลูกหนี้ค่ารักษา', 'ผู้ป่วยไตวายเรื้อรัง', $title_acc_name);
    } elseif (strpos($id, '_HOSP_11504') !== false) {
        $title_acc_name = str_replace('ลูกหนี้ค่ารักษา', '- ลูกหนี้ รพ.ค่าย เบิก นค.', $title_acc_name);
    } elseif (strpos($id, '_HOSP_14429') !== false) {
        $title_acc_name = str_replace('ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 1 วัดเหนือ เบิก นค.', $title_acc_name);
    } elseif (strpos($id, '_HOSP_23576') !== false) {
        $title_acc_name = str_replace('ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 2 สระสิม เบิก นค.', $title_acc_name);
    }

    $title_suffix = '';
    if (!empty($title_acc_name)) {
        $title_suffix = " ($title_acc_name รหัส $title_acc_code)";
    } elseif (!empty($title_acc_code)) {
        $title_suffix = " (รหัส $title_acc_code)";
    }

    $modal_full_title = '📑 รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน' . $title_suffix;

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {

        $raw_rows = [];
        $vns_to_detect = [];
        while ($r = mysqli_fetch_assoc($result)) {
            if (isset($r['cid'])) $r['cid'] = decrypt_data($r['cid']);
            if (isset($r['tel'])) $r['tel'] = decrypt_data($r['tel']);
            $raw_rows[] = $r;
            $vns_to_detect[] = $r['vn'];
        }

        $base_id = explode('_', $id)[0];
        // ตรวจสอบว่าผังนี้เป็นผังเป้าหมาย CR หรือผังแม่ที่เปิดใช้งาน CR หรือไม่
        $is_cr_target_account_opd = $is_cr_effective && $auto_split_opd && !$is_kidney_click && (
            $is_target_216 ||
            (in_array($base_id, ['1102050101.201', '1102050101.209', '1102050101.203']) && is_cr_parent_account_enabled($cr_config, $base_id))
        );

        // ตรวจสอบว่าผังนี้เป็นผังประกันสังคมที่เปิดใช้งาน SSS หรือไม่
        $is_sss_target_account_opd = $is_sss_effective && $auto_split_sss_opd && !$is_kidney_click && (
            $is_target_309 ||
            (in_array($base_id, ['1102050101.301', '1102050101.303', '1102050101.307', '1102050101.308']) && is_sss_parent_account_enabled($sss_config, $base_id))
        );

        $cr_map = [];
        $sss_map = [];
        $subgroup_stats = [];

        if ($is_cr_target_account_opd) {
            if (!empty($vns_to_detect) && $conn2) {
                $cr_map = detect_cr_types_for_visit_list($conn2, $vns_to_detect, 'OPD', $cr_config, $my_hospcode, $actionopdlistonly);
            }

            $subgroup_stats = [
                'ALL' => [
                    'id' => 'ALL',
                    'title' => 'ทั้งหมด (All CR)',
                    'short_name' => 'ทั้งหมด',
                    'count' => 0,
                    'debit' => 0.0
                ]
            ];
            $sg_source_opd = isset($cr_config['subgroups_opd']) ? $cr_config['subgroups_opd'] : (isset($cr_config['subgroups']) ? $cr_config['subgroups'] : []);
            if (!empty($sg_source_opd)) {
                foreach ($sg_source_opd as $sg_id => $sg) {
                    if (!is_cr_subgroup_enabled($cr_config, $sg_id, 'OPD', $actionopdlistonly)) continue;
                    $subgroup_stats[$sg_id] = [
                        'id' => $sg_id,
                        'title' => $sg['title'] ?? $sg_id,
                        'short_name' => $sg['short_name'] ?? $sg_id,
                        'count' => 0,
                        'debit' => 0.0
                    ];
                }
            }
        } elseif ($is_sss_target_account_opd) {
            if (!empty($vns_to_detect) && $conn2) {
                $sss_map = detect_sss_types_for_visit_list($conn2, $vns_to_detect, 'OPD', $sss_config, $my_hospcode, $actionopdlistonly);
            }

            $subgroup_stats = [
                'ALL' => [
                    'id' => 'ALL',
                    'title' => 'ทั้งหมด (All SSS)',
                    'short_name' => 'ทั้งหมด',
                    'count' => 0,
                    'debit' => 0.0
                ]
            ];
            $sg_source_sss_opd = isset($sss_config['subgroups_opd']) ? $sss_config['subgroups_opd'] : [];
            if (!empty($sg_source_sss_opd)) {
                foreach ($sg_source_sss_opd as $sg_id => $sg) {
                    if (!is_sss_subgroup_enabled($sss_config, $sg_id, 'OPD', $actionopdlistonly)) continue;
                    $subgroup_stats[$sg_id] = [
                        'id' => $sg_id,
                        'title' => $sg['title'] ?? $sg_id,
                        'short_name' => $sg['short_name'] ?? $sg_id,
                        'count' => 0,
                        'debit' => 0.0
                    ];
                }
            }
        }

        $cr_bd_map = [];
        $sss_bd_map = [];
        if (!empty($vns_to_detect)) {
            $safe_det_vns = "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $vns_to_detect)) . "'";
            $q_cr_bd = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_cr_breakdown WHERE visit_type = 'OPD' AND vn IN ($safe_det_vns)");
            if ($q_cr_bd) {
                while ($bd = mysqli_fetch_assoc($q_cr_bd)) {
                    $cr_bd_map[$bd['vn']] = $bd;
                }
            }
            $q_sss_bd = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_sss_breakdown WHERE visit_type = 'OPD' AND vn IN ($safe_det_vns)");
            if ($q_sss_bd) {
                while ($bd = mysqli_fetch_assoc($q_sss_bd)) {
                    $sss_bd_map[$bd['vn']] = $bd;
                }
            }
        }

        $all_display_rows = [];

        foreach ($raw_rows as $row) {
            $vn = $row['vn'];
            $row['income_original'] = floatval(cleanNum($row['income']));
            $row['incomediff_original'] = floatval(cleanNum($row['incomediff']));
            $is_transferred_visit = isset($all_cr_visits[$vn]);
            $is_sss_transferred_visit = isset($all_sss_visits[$vn]);

            if ($is_target_216 && !empty($all_cr_visits)) {
                if ($is_transferred_visit) {
                    $vinfo = $all_cr_visits[$vn];
                    if (!empty($vinfo['is_kidney'])) continue;
                    $row['debit'] = $vinfo['cr_amount'];
                    $row['income'] = $vinfo['cr_amount'];
                    $row['incomediff'] = 0.0;
                    $row['cr_origin_acc'] = $vinfo['origin_accountcode'] ?? ($vinfo['origin_account'] ?? '1102050101.201');
                    $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                    $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];

                    if (isset($cr_bd_map[$vn])) {
                        $row['follow_money'] = cleanNum($cr_bd_map[$vn]['compensated']);
                        $row['bill'] = $cr_bd_map[$vn]['bill'];
                        $row['billdate'] = $cr_bd_map[$vn]['billdate'];
                    } else {
                        $row['follow_money'] = min(cleanNum($row['follow_money']), floatval($vinfo['cr_amount']));
                    }
                } else {
                    $row['cr_origin_acc'] = '1102050101.216';
                    $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                    $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];
                    if (isset($cr_bd_map[$vn])) {
                        $row['follow_money'] = cleanNum($cr_bd_map[$vn]['compensated']);
                        $row['bill'] = $cr_bd_map[$vn]['bill'];
                        $row['billdate'] = $cr_bd_map[$vn]['billdate'];
                    }
                }
                $all_display_rows[] = $row;

            } elseif ($is_target_309 && !empty($all_sss_visits)) {
                if ($is_sss_transferred_visit) {
                    $vinfo = $all_sss_visits[$vn];
                    $row['debit'] = $vinfo['sss_amount'];
                    $row['income'] = $vinfo['sss_amount'];
                    $row['incomediff'] = 0.0;
                    $row['sss_origin_acc'] = $vinfo['origin_accountcode'] ?? '1102050101.301';
                    $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                    $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];

                    if (isset($sss_bd_map[$vn])) {
                        $row['follow_money'] = cleanNum($sss_bd_map[$vn]['compensated']);
                        $row['bill'] = $sss_bd_map[$vn]['bill'];
                        $row['billdate'] = $sss_bd_map[$vn]['billdate'];
                    } else {
                        $row['follow_money'] = min(cleanNum($row['follow_money']), floatval($vinfo['sss_amount']));
                    }
                } else {
                    $row['sss_origin_acc'] = '1102050101.309';
                    $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                    $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];
                    if (isset($sss_bd_map[$vn])) {
                        $row['follow_money'] = cleanNum($sss_bd_map[$vn]['compensated']);
                        $row['bill'] = $sss_bd_map[$vn]['bill'];
                        $row['billdate'] = $sss_bd_map[$vn]['billdate'];
                    }
                }
                $all_display_rows[] = $row;

            } elseif (!empty($all_cr_visits) && !$is_kidney_click && in_array($id, ['1102050101.201', '1102050101.209', '1102050101.203'])) {
                if ($is_transferred_visit) {
                    $vinfo = $all_cr_visits[$vn];
                    if ($vinfo['is_full_transfer']) {
                        continue;
                    } else {
                        $row['debit'] = $vinfo['general_remain_amount'];
                        $row['cr_transferred_out'] = $vinfo['cr_amount'];
                        $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                        $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];

                        // หักเงินชดเชยที่โอนไปผังลูกออก
                        $cr_comp = isset($cr_bd_map[$vn]) ? cleanNum($cr_bd_map[$vn]['compensated']) : min(cleanNum($row['follow_money']), floatval($vinfo['cr_amount']));
                        $mother_comp = max(0, cleanNum($row['follow_money']) - $cr_comp);
                        $row['follow_money'] = $mother_comp;

                        // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                        if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                            $row['bill'] = '';
                            $row['billdate'] = '';
                        }
                        $all_display_rows[] = $row;
                    }
                } else {
                    $all_display_rows[] = $row;
                }
            } elseif (!empty($all_sss_visits) && !$is_kidney_click && $is_sss_parent_opd) {
                if ($is_sss_transferred_visit) {
                    $vinfo = $all_sss_visits[$vn];
                    if ($vinfo['is_full_transfer']) {
                        continue;
                    } else {
                        $row['debit'] = $vinfo['general_remain_amount'];
                        $row['sss_transferred_out'] = $vinfo['sss_amount'];
                        $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];

                        // หักเงินชดเชยที่โอนไปผังลูกออก
                        $sss_comp = isset($sss_bd_map[$vn]) ? cleanNum($sss_bd_map[$vn]['compensated']) : min(cleanNum($row['follow_money']), floatval($vinfo['sss_amount']));
                        $mother_comp = max(0, cleanNum($row['follow_money']) - $sss_comp);
                        $row['follow_money'] = $mother_comp;

                        // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                        if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                            $row['bill'] = '';
                            $row['billdate'] = '';
                        }
                        $all_display_rows[] = $row;
                    }
                } else {
                    $all_display_rows[] = $row;
                }
            } else {
                $all_display_rows[] = $row;
            }
        }

        $i=0; $income=0; $incomediff=0; $debit=0; $follow_money=0; $compensatedaa=0;

        $parseThaiDate = function($dateStr) {
            return parseThaiDate($dateStr);
        };

        foreach ($all_display_rows as $row) {
            $i++;
            $vn = $row['vn'];

            $is_row_kidney = ($is_kidney_click || strpos($row["pttypename"], 'ฟอกไต') !== false || strpos($row["pttypename"], 'ไต') !== false);
            $row_color = $is_row_kidney ? 'style="color: #cd641f;"' : '';

            $cr_types = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : (isset($row['cr_types']) ? $row['cr_types'] : []);
            $cr_items = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : (isset($row['items']) ? $row['items'] : []);
            $sss_types = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
            $sss_items = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

            $all_subtypes = array_unique(array_merge($cr_types, $sss_types));
            $cr_type_str = implode(',', $all_subtypes);

            // 🌟 คำนวณยอดเงินแยกตามกลุ่มย่อย CR จากรายการ items จริง เพื่อป้องกันการปนกันของยอดเงิน
            $cr_subgroup_amounts = [];
            if (!empty($cr_items)) {
                foreach ($cr_items as $cit) {
                    $ctype = trim((string)($cit['cr_type'] ?? ''));
                    if (!empty($ctype)) {
                        if (!isset($cr_subgroup_amounts[$ctype])) {
                            $cr_subgroup_amounts[$ctype] = 0.0;
                        }
                        $cr_subgroup_amounts[$ctype] += floatval(cleanNum($cit['amount'] ?? 0));
                    }
                }
            }
            if (!empty($cr_types)) {
                $allocated_sum = array_sum($cr_subgroup_amounts);
                $unallocated = max(0, floatval(cleanNum($row['debit'])) - $allocated_sum);
                $zero_types = [];
                foreach ($cr_types as $ct) {
                    if (!isset($cr_subgroup_amounts[$ct]) || $cr_subgroup_amounts[$ct] <= 0.001) {
                        $zero_types[] = $ct;
                    }
                }
                if ($unallocated > 0 && !empty($zero_types)) {
                    $each_amt = $unallocated / count($zero_types);
                    foreach ($zero_types as $zt) {
                        $cr_subgroup_amounts[$zt] = $each_amt;
                    }
                }
            }

            // คำนวณยอดเงินแยกตามกลุ่มย่อย SSS
            $sss_subgroup_amounts = [];
            if (!empty($sss_items)) {
                foreach ($sss_items as $sit) {
                    $stype = trim((string)($sit['cr_type'] ?? ''));
                    if (!empty($stype)) {
                        if (!isset($sss_subgroup_amounts[$stype])) {
                            $sss_subgroup_amounts[$stype] = 0.0;
                        }
                        $sss_subgroup_amounts[$stype] += floatval(cleanNum($sit['amount'] ?? 0));
                    }
                }
            }
            if (!empty($sss_types)) {
                $allocated_sum_s = array_sum($sss_subgroup_amounts);
                $unallocated_s = max(0, floatval(cleanNum($row['debit'])) - $allocated_sum_s);
                $zero_types_s = [];
                foreach ($sss_types as $st) {
                    if (!isset($sss_subgroup_amounts[$st]) || $sss_subgroup_amounts[$st] <= 0.001) {
                        $zero_types_s[] = $st;
                    }
                }
                if ($unallocated_s > 0 && !empty($zero_types_s)) {
                    $each_amt_s = $unallocated_s / count($zero_types_s);
                    foreach ($zero_types_s as $zs) {
                        $sss_subgroup_amounts[$zs] = $each_amt_s;
                    }
                }
            }

            if ($is_cr_target_account_opd) {
                $subgroup_stats['ALL']['count']++;
                $subgroup_stats['ALL']['debit'] += cleanNum($row['debit']);
                foreach ($cr_types as $ct) {
                    if (isset($subgroup_stats[$ct])) {
                        $subgroup_stats[$ct]['count']++;
                        $sg_amt = isset($cr_subgroup_amounts[$ct]) && $cr_subgroup_amounts[$ct] > 0 
                            ? $cr_subgroup_amounts[$ct] 
                            : cleanNum($row['debit']);
                        $subgroup_stats[$ct]['debit'] += $sg_amt;
                    }
                }
            } elseif ($is_sss_target_account_opd) {
                $subgroup_stats['ALL']['count']++;
                $subgroup_stats['ALL']['debit'] += cleanNum($row['debit']);
                foreach ($sss_types as $st) {
                    if (isset($subgroup_stats[$st])) {
                        $subgroup_stats[$st]['count']++;
                        $sg_amt = isset($sss_subgroup_amounts[$st]) && $sss_subgroup_amounts[$st] > 0 
                            ? $sss_subgroup_amounts[$st] 
                            : cleanNum($row['debit']);
                        $subgroup_stats[$st]['debit'] += $sg_amt;
                    }
                }
            }

            $cr_badge_html = '';
            if ($is_cr_target_account_opd && (!empty($cr_types) || !empty($row['cr_origin_acc']) || !empty($row['cr_transferred_out']))) {
                $original_debit_display = 0.0;
                $cr_amt_display = 0.0;
                $remain_debit_display = 0.0;

                if ($is_target_216) {
                    $cr_amt_display = floatval(cleanNum($row['debit']));
                    if (isset($all_cr_visits[$vn])) {
                        $v_info = $all_cr_visits[$vn];
                        $original_debit_display = floatval($v_info['original_debit'] ?? ($v_info['cr_amount'] + $v_info['general_remain_amount']));
                        $remain_debit_display = floatval($v_info['general_remain_amount'] ?? 0);
                    } else {
                        $original_debit_display = $cr_amt_display;
                        $remain_debit_display = 0.0;
                    }
                } elseif (!empty($row['cr_transferred_out']) && $row['cr_transferred_out'] > 0) {
                    // ฝั่งแม่ที่มีการตัดยอด CR ออกไป
                    $cr_amt_display = floatval($row['cr_transferred_out']);
                    $remain_debit_display = floatval(cleanNum($row['debit']));
                    $original_debit_display = $remain_debit_display + $cr_amt_display;
                } else {
                    $original_debit_display = floatval(cleanNum($row['debit']));
                    $cr_amt_display = floatval(cleanNum($row['debit']));
                    $remain_debit_display = 0.0;
                }

                $cr_payload_data = [
                    'vn' => (string)$row['vn'],
                    'hn' => (string)($row['hn'] ?? ''),
                    'ptname' => (string)($row['ptname'] ?? ''),
                    'vstdate' => (string)($row['vstdate'] ?? ''),
                    'pttypename' => (string)($row['pttypename'] ?? ''),
                    'hospmain' => '',
                    'income' => floatval(cleanNum($row['income_original'] ?? ($row['income'] ?? $original_debit_display))),
                    'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                    'debit' => $original_debit_display,
                    'origin_acc' => (string)($row['cr_origin_acc'] ?? $row['accountcode']),
                    'target_acc' => '1102050101.216',
                    'cr_types' => $cr_types,
                    'cr_amount' => $cr_amt_display,
                    'remain_debit' => $remain_debit_display,
                    'items' => $cr_items
                ];
                $payload_json_str = htmlspecialchars(json_encode($cr_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                foreach ($cr_types as $ct) {
                    $ct_amt = isset($cr_subgroup_amounts[$ct]) ? floatval($cr_subgroup_amounts[$ct]) : 0;
                    $ct_amt_label = ($ct_amt > 0) ? ' (฿' . number_format($ct_amt, 2) . ')' : '';
                    $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge" data-cr-subgroup="' . htmlspecialchars($ct) . '" data-filter-subgroup="' . htmlspecialchars($ct) . '" style="font-size:11px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'' . htmlspecialchars($ct, ENT_QUOTES) . '\')" title="คลิกดูรายการเฉพาะกลุ่ม ' . htmlspecialchars($ct) . $ct_amt_label . '">' . htmlspecialchars($ct) . '</span>';
                }

                if (!empty($row['cr_origin_acc']) && $row['cr_origin_acc'] !== '1102050101.216') {
                    $orig_short = str_replace('1102050101.', '.', $row['cr_origin_acc']);
                    $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'ALL\')" title="โอนมาจากผัง ' . htmlspecialchars($row['cr_origin_acc']) . ' (คลิกเพื่อดูทุกกลุ่มย่อยที่โอนมา)"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                } elseif (!empty($row['cr_transferred_out']) && $row['cr_transferred_out'] > 0) {
                    $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'ALL\')" title="ตัดยอด CR ออกไปผัง .216 จำนวน ฿' . number_format($row['cr_transferred_out'], 2) . ' (คลิกเพื่อดูรายการ)"><i class="bx bx-cut"></i> ตัด CR -฿' . number_format($row['cr_transferred_out'], 2) . '</span>';
                }
            }

            // SSS Badge & Breakdown Payload
            $sss_types = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
            $sss_items = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

            if ($is_sss_target_account_opd && (!empty($sss_types) || !empty($row['sss_origin_acc']) || !empty($row['sss_transferred_out']))) {
                $original_debit_sss = 0.0;
                $sss_amt_display = 0.0;
                $remain_debit_sss = 0.0;

                if ($is_target_309) {
                    $sss_amt_display = floatval(cleanNum($row['debit']));
                    if (isset($all_sss_visits[$vn])) {
                        $v_info = $all_sss_visits[$vn];
                        $original_debit_sss = floatval($v_info['original_debit'] ?? ($v_info['sss_amount'] + $v_info['general_remain_amount']));
                        $remain_debit_sss = floatval($v_info['general_remain_amount'] ?? 0);
                    } else {
                        $original_debit_sss = $sss_amt_display;
                        $remain_debit_sss = 0.0;
                    }
                } elseif (!empty($row['sss_transferred_out']) && $row['sss_transferred_out'] > 0) {
                    $sss_amt_display = floatval($row['sss_transferred_out']);
                    $remain_debit_sss = floatval(cleanNum($row['debit']));
                    $original_debit_sss = $remain_debit_sss + $sss_amt_display;
                } else {
                    $original_debit_sss = floatval(cleanNum($row['debit']));
                    $sss_amt_display = floatval(cleanNum($row['debit']));
                    $remain_debit_sss = 0.0;
                }

                $sss_payload_data = [
                    'vn' => (string)$row['vn'],
                    'hn' => (string)($row['hn'] ?? ''),
                    'ptname' => (string)($row['ptname'] ?? ''),
                    'vstdate' => (string)($row['vstdate'] ?? ''),
                    'pttypename' => (string)($row['pttypename'] ?? ''),
                    'hospmain' => '',
                    'income' => floatval(cleanNum($row['income_original'] ?? ($row['income'] ?? $original_debit_sss))),
                    'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                    'debit' => $original_debit_sss,
                    'origin_acc' => (string)($row['sss_origin_acc'] ?? $row['accountcode']),
                    'target_acc' => '1102050101.309',
                    'sss_types' => $sss_types,
                    'sss_amount' => $sss_amt_display,
                    'remain_debit' => $remain_debit_sss,
                    'items' => $sss_items
                ];
                $sss_payload_json_str = htmlspecialchars(json_encode($sss_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                foreach ($sss_types as $st) {
                    $st_amt = isset($sss_subgroup_amounts[$st]) ? floatval($sss_subgroup_amounts[$st]) : 0;
                    $st_amt_label = ($st_amt > 0) ? ' (฿' . number_format($st_amt, 2) . ')' : '';
                    $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge" data-cr-subgroup="' . htmlspecialchars($st) . '" data-filter-subgroup="' . htmlspecialchars($st) . '" style="font-size:11px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="คลิกดูรายการ Instrument ประกันสังคม ' . htmlspecialchars($st) . $st_amt_label . '">' . htmlspecialchars($st) . '</span>';
                }

                if (!empty($row['sss_origin_acc']) && $row['sss_origin_acc'] !== '1102050101.309') {
                    $orig_short = str_replace('1102050101.', '.', $row['sss_origin_acc']);
                    $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="โอนมาจากผัง ' . htmlspecialchars($row['sss_origin_acc']) . '"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                } elseif (!empty($row['sss_transferred_out']) && $row['sss_transferred_out'] > 0) {
                    $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="ตัดยอด Instrument ออกไปผัง .309 จำนวน ฿' . number_format($row['sss_transferred_out'], 2) . '"><i class="bx bx-cut"></i> ตัด Instrument -฿' . number_format($row['sss_transferred_out'], 2) . '</span>';
                }
            }

            $all_subgroup_amounts = array_merge($cr_subgroup_amounts, $sss_subgroup_amounts);
            $amounts_json = htmlspecialchars(json_encode($all_subgroup_amounts, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

            echo '  <tr class="main-patient-row" data-cr-type="' . htmlspecialchars($cr_type_str) . '" data-cr-amounts="' . $amounts_json . '" data-full-debit="' . floatval(cleanNum($row['debit'])) . '" data-full-income="' . floatval(cleanNum($row['income'])) . '" '.$row_color.'>
                      <td style="text-align: center;"><input type="checkbox" name="select[]" value="'.$row["vn"].'" style=" width: 20px; height: 20px;"></td>';

            // =================================================================
            // CASE 1: มีเลข Bill ในตารางหลักแล้ว (จบการตามหนี้เบื้องต้น)
            // =================================================================
            if($row["bill"]){

                 $days_show = "-";
                 if(!empty($row["vstdate"])){
                     $start = $parseThaiDate($row["vstdate"]);
                     $end = new DateTime();
                     $found_end_date = false;

                     if(!empty($row["billdate"])){
                         $chkDate = $parseThaiDate($row["billdate"]);
                         if($chkDate) {
                             $end = $chkDate;
                             $found_end_date = true;
                         }
                     }

                     if(!$found_end_date && !empty($row["mobile"])){
                         $chkDateMobile = $parseThaiDate($row["mobile"]);
                         if($chkDateMobile) {
                             $end = $chkDateMobile;
                         }
                     }

                     if($start){
                         $diff = $start->diff($end);
                         $days_show = number_format($diff->days) . " วัน";
                     }
                 }

                 $vn_check = $row["vn"];
                 $chk_history = mysqli_query($conn, "SELECT id FROM imr_tb_payment_history WHERE ref_vn_an = '$vn_check' LIMIT 1");
                 $is_partial = (mysqli_num_rows($chk_history) > 0);

                 if ($is_partial) {
// กรณีมีการแบ่งจ่ายชำระหนี้: ให้ตรวจสอบยอดหนี้คงเหลือจริง
                     $balance_check = cleanNum($row["debit"]) - cleanNum($row["follow_money"]);
                     if ($balance_check <= 0.01) {
                         $show_bill_main = $row["bill"];
                         $show_date_main = $row["billdate"];
                         $bg_color_main  = 'background: #e6ffe6;'; // เขียวอ่อน = จ่ายครบแล้ว
                     } else {
                         // จ่ายหนี้ยังไม่ครบ -> ปล่อยว่างบิลและวันที่หลักเพื่อให้แสดงเฉพาะงวดแบ่งจ่าย
                         $show_bill_main = "";
                         $show_date_main = "";
                         $bg_color_main  = 'background: #fff5e7;';
                     }
                 } else {
                     $show_bill_main = $row["bill"];
                     $show_date_main = $row["billdate"];
                     $bg_color_main  = 'background: #fff5e7;';
                 }

                 echo ' <td style="text-align: center;">'.$i.'</td>
                        <td style="text-align: center;">'.$row["vn"].'</td>
                        <td style="text-align: center;">'.$row["cid"].'</td>
                        <td>'.$row["ptname"].$cr_badge_html.'</td>
                        <td>'.$row["pttypename"].'</td>
                        <td style="text-align: center;">'.$row["vstdate"].'</td>
                        <td style="text-align: center;">'.$days_show.'</td>
                        <td style="text-align: right;">'.formatMoney($row["income"], 2).'</td>
                        <td style="text-align: right;">'.formatMoney($row["incomediff"], 2).'</td>
                        <td style="text-align: right;">'.formatMoney($row["debit"], 2).'</td>
                        <td style="text-align: center;padding: 0px;background: #fff5e7;" contenteditable data-data1="'.$row["vn"].'" >'.formatMoney($row["follow_money"], 2).'</td>
                        <td class="mask-bill" style="text-align: center;'.$bg_color_main.'" contenteditable data-data2="'.$row["vn"].'" >'.$show_bill_main.'</td>
                        <td class="mask-date" style="text-align: center;'.$bg_color_main.'" contenteditable data-data3="'.$row["vn"].'" >'.$show_date_main.'</td>
                        <td style="text-align: center;padding: 0px;background: #fff;" ></td>
                        <td style="text-align: center;padding: 0px;background: #fff;" >'.$row["hn"].'</td>
                    </tr>';

            } else {
                // =================================================================
                // CASE 2: ยังไม่มีเลข Bill (ทำการกวาดหาสิทธิ Statement ชดเชยจากตารางเชื่อม)
                // =================================================================
                $vn =$row["vn"];
                $sql1 = "SELECT o.vn,stm.compensated,o.mobile,stm.rep,
                        SUM(dckd.compensated) as compensatedckd,
                        SUM(ckd_ofc.amount) as compensatedckd_ofc,
                        dckd.rep as repckd,
                        ckd_ofc.stm_doc as stm_doc
                        FROM imr_tb_debtor_rights_opd o
                        LEFT JOIN imr_tb_check_invoice stm on stm.vn=o.vn
                        LEFT JOIN imr_tb_seamless_dckd dckd on dckd.vn=o.vn
                        LEFT JOIN imr_tb_seamless_dckd_ofc ckd_ofc on ckd_ofc.vn=o.vn
                        WHERE o.vn='$vn' and o.mobile REGEXP '^[0-9]{2,4}/[0-9]{2,4}-[0-9]{1,2}/[0-9]{1,2}/[0-9]{3,4}$' GROUP BY o.vn ";

                $result1 = mysqli_query($conn, $sql1);

                if (mysqli_num_rows($result1) > 0) {
                    while ($row1 = mysqli_fetch_assoc($result1)) {
                      if (isset($row1['cid'])) $row1['cid'] = decrypt_data($row1['cid']);
                      if (isset($row1['tel'])) $row1['tel'] = decrypt_data($row1['tel']);
                      if($row1["compensated"]){ $compensated=$row1["compensated"]; }
                      else if($row1["compensatedckd"]){ $compensated=$row1["compensatedckd"]; }
                      else{ $compensated=$row1["compensatedckd_ofc"]; }

                      if($row1["rep"]){ $rep=$row1["rep"]; }
                      else if($row1["repckd"]){ $rep=$row1["repckd"]; }
                      else{ $rep=$row1["stm_doc"]; }

                      list($bill1, $billdate1) = explode('-', $row1["mobile"]);

                      $days_show = "-";
                      if(!empty($row["vstdate"])){
                          $start = $parseThaiDate($row["vstdate"]);
                          $end = new DateTime();
                          if(!empty($billdate1)){
                               $chkDate = $parseThaiDate($billdate1);
                               if($chkDate) $end = $chkDate;
                          }
                          if($start){
                              $diff = $start->diff($end);
                              $days_show = number_format($diff->days) . " วัน";
                          }
                      }

                      echo '<td style="text-align: center;">'.$i.'</td>
                            <td style="text-align: center;">'.$row["vn"].'</td>
                            <td style="text-align: center;">'.$row["cid"].'</td>
                            <td>'.$row["ptname"].$cr_badge_html.'</td>
                            <td>'.$row["pttypename"].'</td>
                            <td style="text-align: center;">'.$row["vstdate"].'</td>
                            <td style="text-align: center;">'.$days_show.'</td>
                            <td style="text-align: right;">'.formatMoney($row["income"], 2).'</td>
                            <td style="text-align: right;">'.formatMoney($row["incomediff"], 2).'</td>
                            <td style="text-align: right;">'.formatMoney($row["debit"], 2).'</td>
                            <td style="text-align: center;padding: 0px;" >'.formatMoney($compensated, 2).'</td>
                            <td style="text-align: center;padding: 0px;" >'.$bill1.'</td>
                            <td style="text-align: center;padding: 0px;" >'.$billdate1.'</td>
                            <td style="text-align: center;padding: 0px;" >'.$rep.'</td>
                            <td style="text-align: center;padding: 0px;background: #fff;" >'.$row["hn"].'</td>
                          </tr>';

                      $compensatedaa = $compensatedaa + cleanNum($row1["compensated"]) + cleanNum($row1["compensatedckd"]) + cleanNum($row1["compensatedckd_ofc"]);
                    }
                } else {
                     // =================================================================
                     // CASE 3: ไม่เจอข้อมูลชดเชยใดๆ (คนไข้ค้างชำระ/เป็นหนี้เต็มจำนวน)
                     // =================================================================
                     $days_show = "-";
                     if(!empty($row["vstdate"])){
                         $start = $parseThaiDate($row["vstdate"]);
                         $end = new DateTime();
                         if($start){
                             $diff = $start->diff($end);
                             $days_show = number_format($diff->days) . " วัน";
                         }
                     }

                     // [แก้ไข] ถ้ามีการแบ่งจ่ายและจ่ายครบแล้ว (follow_money >= debit) ให้ดึงเลขบิลงวดล่าสุดมาแสดง
                     $vn_c3       = $row["vn"];
                     $c3_bill     = $row["bill"];     // ค่าเริ่มต้น (อาจว่าง)
                     $c3_billdate = $row["billdate"]; // ค่าเริ่มต้น (อาจว่าง)
                     $c3_bg       = 'background: #fff5e7;';
                     $c3_balance  = cleanNum($row["debit"]) - cleanNum($row["follow_money"]);

                     if (cleanNum($row["follow_money"]) > 0 && $c3_balance <= 0.01) {
                         $sql_c3 = "SELECT bill_no, bill_date FROM imr_tb_payment_history
                                    WHERE ref_vn_an = '$vn_c3' AND bill_no != ''
                                    ORDER BY id DESC LIMIT 1";
                         $res_c3 = mysqli_query($conn, $sql_c3);
                         if ($res_c3 !== false && ($row_c3 = mysqli_fetch_assoc($res_c3))) {
                             $c3_bill = $row_c3['bill_no'];
                             if (!empty($row_c3['bill_date'])) {
                                 $d_c3 = date('d', strtotime($row_c3['bill_date']));
                                 $m_c3 = date('m', strtotime($row_c3['bill_date']));
                                 $y_c3 = date('Y', strtotime($row_c3['bill_date'])) + 543;
                                 $c3_billdate = "$d_c3/$m_c3/$y_c3";
                             }
                             $c3_bg = 'background: #e6ffe6;'; // เขียวอ่อน = จ่ายครบแล้ว
                         }
                     }
                     echo ' <td style="text-align: center;">'.$i.'</td>
                            <td style="text-align: center;">'.$row["vn"].'</td>
                            <td style="text-align: center;">'.$row["cid"].'</td>
                            <td>'.$row["ptname"].$cr_badge_html.'</td>
                            <td>'.$row["pttypename"].'</td>
                            <td style="text-align: center;">'.$row["vstdate"].'</td>
                            <td style="text-align: center; color: red;">'.$days_show.'</td>
                            <td style="text-align: right;">'.formatMoney($row["income"], 2).'</td>
                            <td style="text-align: right;">'.formatMoney($row["incomediff"], 2).'</td>
                            <td style="text-align: right;">'.formatMoney($row["debit"], 2).'</td>
                            <td style="text-align: center;padding: 0px;'.$c3_bg.'" contenteditable data-data1="'.$row["vn"].'" >'.formatMoney($row["follow_money"], 2).'</td>
                            <td class="mask-bill" style="text-align: center;'.$c3_bg.'" contenteditable data-data2="'.$row["vn"].'" >'.$c3_bill.'</td>
                            <td class="mask-date" style="text-align: center;'.$c3_bg.'" contenteditable data-data3="'.$row["vn"].'" >'.$c3_billdate.'</td>
                            <td style="text-align: center;padding: 0px;"> </td>
                            <td style="text-align: center;padding: 0px;background: #fff;" >'.$row["hn"].'</td>
                        </tr>';
                }
            }

            // =================================================================
            // แสดงรายการย่อยแบบแบ่งชำระ (Running Balance) พร้อมคลาสผูกกลุ่มแถวแม่-ลูก
            // =================================================================
            $vn_ref = $row["vn"];
            $sql_sub = "SELECT * FROM imr_tb_payment_history WHERE ref_vn_an = '$vn_ref' ORDER BY id ASC";
            $result_sub = mysqli_query($conn, $sql_sub);

            if (mysqli_num_rows($result_sub) > 0) {
                $count_sub = 1;
                $running_balance = cleanNum($row["debit"]);

                while ($sub = mysqli_fetch_assoc($result_sub)) {
                  if (isset($sub['cid'])) $sub['cid'] = decrypt_data($sub['cid']);
                  if (isset($sub['tel'])) $sub['tel'] = decrypt_data($sub['tel']);
                  $pay_amount = cleanNum($sub['pay_amount']);
                  $running_balance = $running_balance - cleanNum($pay_amount);

                  $sub_date_show = "-";
                  if(!empty($sub['bill_date'])){
                      $d = date('d', strtotime($sub['bill_date']));
                      $m = date('m', strtotime($sub['bill_date']));
                      $y = date('Y', strtotime($sub['bill_date'])) + 543;
                      $sub_date_show = "$d/$m/$y";
                  }

                  $safe_note = htmlspecialchars($sub['note'], ENT_QUOTES, 'UTF-8');

                  // ฝังคลาส sub-payment-row ไว้ที่แถวประวัติการแบ่งจ่าย เพื่อให้ขยับสลับตำแหน่งตามแถวแม่
                  echo '<tr class="sub-payment-row" style="background-color: #f9fcff; font-size: 13px; color: #444;">';
                  echo '<td colspan="9" style="text-align: right; border-right: none; color: #0027e5;background-color: #fff;">
                          <i class="bx bx-subdirectory-right"></i> แบ่งจ่ายงวดที่ '.$count_sub.' <small class="text-muted">'.$sub['note'].'</small>
                        </td>';
                  echo '<td style="text-align: right; font-weight: bold; color: #008a0e;">'.formatMoney($pay_amount, 2).'</td>';
                  echo '<td style="text-align: right; font-weight: bold; color: #d9534f; background-color: #fff0f0;">'.formatMoney($running_balance, 2).'</td>';
                  echo '<td style="text-align: center; color: #0027e5;">'.formatMoney($pay_amount, 2).'</td>';
                  echo '<td style="text-align: center;">'.$sub['bill_no'].'</td>';
                  echo '<td style="text-align: center;">'.$sub_date_show.'</td>';
                  echo '<td style="text-align: center;background-color: #fff;">
                          <a href="javascript:void(0)" class="btn-edit-partial"
                             onclick="editPartial(this)"
                             data-id="'.$sub['id'].'"
                             data-vn="'.$vn_ref.'"
                             data-amount="'.$sub['pay_amount'].'"
                             data-bill="'.$sub['bill_no'].'"
                             data-date="'.$sub_date_show.'"
                             data-note="'.$safe_note.'">
                              <i class="bx bx-edit text-warning" style="cursor: pointer;"></i>
                          </a>
                          &nbsp;
                          <a href="javascript:void(0)" class="btn-delete-partial"
                             onclick="deletePartial(this)"
                             data-id="'.$sub['id'].'"
                             data-vn="'.$vn_ref.'"
                             data-type="OPD">
                              <i class="bx bx-trash text-danger" style="cursor: pointer;"></i>
                          </a>
                        </td>';
                  echo '<td style="background-color: #fff;"></td>';
                  echo '</tr>';

                  $count_sub++;
                }
            }

            $income = $income + cleanNum($row["income"]);
            $incomediff = $incomediff + cleanNum($row["incomediff"]);
            $debit = $debit + cleanNum($row["debit"]);
            $follow_money = $follow_money + cleanNum($row["follow_money"]);
        }

        // คำนวณเปอร์เซ็นต์สรุปปิดท้ายตารางรวม
        $percen = 0;
        if($debit > 0) {
            $percen = (($follow_money+$compensatedaa)/$debit)*100;
        }

        echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
        echo '<tr class="summary-total-row" style="font-weight: bold; background: linear-gradient(268deg, #2a3d5d 0%, #1e2b4b 100%) !important; color: #ffffff !important;">
                <td style="text-align: center; background: transparent !important; color: #ffffff !important;" colspan="8">
                    รวม <span id="o0" style="display:none;">'.$i.'</span>
                    <span id="modal_report_title_data" style="display:none;" data-title="'.htmlspecialchars($modal_full_title, ENT_QUOTES, 'UTF-8').'"></span>
                    <script id="cr_subgroup_summary_json" type="application/json">' . json_encode($subgroup_stats, JSON_UNESCAPED_UNICODE) . '</script>
                </td>
                <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="o1">&nbsp;'.formatMoney($income, 2).'</td>
                <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="o2">&nbsp;'.formatMoney($incomediff, 2).'</td>
                <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="o3">&nbsp;'.formatMoney($debit, 2).'</td>
                <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="o4">&nbsp;'.number_format($follow_money+$compensatedaa,2).' ('.formatMoney($percen, 2).')</td>
                <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="o5">&nbsp;</td>
                <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="o7">&nbsp;</td>
                <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="o6">
                    <input readonly type="text" id="typecc" name="typecc" value="OPD" data-oid="'.$id.'" readonly style="border: none;width: 40px;text-align: center; background-color: transparent; color: #fff;" />
                </td>
                <td style="background: transparent !important; color: #ffffff !important;"> </td>
              </tr>';
    } else {
        echo '<tr class="summary-total-row">
                <td colspan="16" style="text-align: center; padding: 25px; color: #64748b;">
                    <i class="bx bx-info-circle fs-4 me-1"></i> ไม่พบรายการลูกหนี้รายตัวในผังบัญชีนี้
                    <span id="modal_report_title_data" style="display:none;" data-title="'.htmlspecialchars($modal_full_title, ENT_QUOTES, 'UTF-8').'"></span>
                    <script id="cr_subgroup_summary_json" type="application/json">{}</script>
                </td>
              </tr>';
    }
}





if(isset($_POST['actionipdlistonly'])){

  $actionipdlistonly = mysqli_real_escape_string($conn, $_POST['actionipdlistonly']);
  $id = mysqli_real_escape_string($conn, $_POST['iid']); // รับค่าเป็น accountcode จากหน้าตารางสรุป
  $_SESSION['monthtxt'] = $actionipdlistonly;
  $_SESSION['iid'] = $id;

  // 1. ดึงการตั้งค่า CR Splitting สำหรับ IPD
  $cr_config_ipd = get_active_cr_config($conn);
  $auto_split_ipd = is_cr_auto_split_enabled($cr_config_ipd, 'IPD');
  $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
  $is_cr_effective_ipd = is_cr_effective_for_month($cr_config_ipd, $actionipdlistonly);
  $all_cr_visits_ipd = [];

  if ($is_cr_effective_ipd && $auto_split_ipd) {
      $s_ipd = get_cr_splitting_summary_for_month($conn, $conn2, $actionipdlistonly, 'IPD', $cr_config_ipd, $my_hospcode);
      if ($s_ipd && !empty($s_ipd['cr_visits'])) {
          $all_cr_visits_ipd = $s_ipd['cr_visits'];
      }
  }

  // 1.1 ดึงการตั้งค่า SSS Splitting สำหรับ IPD
  $sss_config_ipd = get_active_sss_config($conn);
  $auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config_ipd, 'IPD');
  $is_sss_effective_ipd = is_sss_effective_for_month($sss_config_ipd, $actionipdlistonly);
  $all_sss_visits_ipd = [];

  if ($is_sss_effective_ipd && $auto_split_sss_ipd) {
      $s_sss_ipd = get_sss_splitting_summary_for_month($conn, $conn2, $actionipdlistonly, 'IPD', $sss_config_ipd, $my_hospcode);
      if ($s_sss_ipd && !empty($s_sss_ipd['sss_visits'])) {
          $all_sss_visits_ipd = $s_sss_ipd['sss_visits'];
      }
  }

  $is_kidney_click = (strpos($id, '_KIDNEY') !== false);
  $is_target_217 = ($id === '1102050101.217');
  $is_target_310 = ($id === '1102050101.310');
  $is_sss_parent_ipd = in_array($id, ['1102050101.302', '1102050101.304']);

  if ($is_kidney_click) {
      $base_account = str_replace('_KIDNEY', '', $id);
      $condition = "AND o.accountcode = '$base_account' AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%')";
  } else {
      if ($is_target_217 && !empty($all_cr_visits_ipd)) {
          $transfer_ans = array_keys($all_cr_visits_ipd);
          $transfer_ans_quoted = !empty($transfer_ans) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_ans)) . "'" : "''";
          $condition = "AND ((o.accountcode = '1102050101.217') OR (o.an IN ($transfer_ans_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
      } elseif ($is_target_310 && !empty($all_sss_visits_ipd)) {
          $transfer_sss_ans = array_keys($all_sss_visits_ipd);
          $transfer_sss_ans_quoted = !empty($transfer_sss_ans) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_sss_ans)) . "'" : "''";
          $condition = "AND ((o.accountcode = '1102050101.310') OR (o.an IN ($transfer_sss_ans_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
      } else {
          $condition = "AND o.accountcode = '$id' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
      }
  }

  $sql = "SELECT
            o.an, o.hn, o.cid, o.ptname, o.admdate, o.dchdate,
            (o.income) as income,
            (o.income - COALESCE(o.original_debit, o.debit)) as incomediff,
            (o.debit) as debit,
            o.follow_money, o.bill, o.billdate,
            o.pttype_eclaim_name, o.accountname,
            o.accountcode,
            o.mobile ,o.pttypename
          FROM imr_tb_debtor_rights_ipd o
          WHERE monthtxt = '$actionipdlistonly' $condition";

  // ดึงข้อมูลชื่อผังบัญชี / สิทธิการเงิน สำหรับแสดงที่หัวตาราง Modal IPD
  $title_acc_name = '';
  $title_acc_code = $id;

  if ($is_target_217 || strpos($id, '1102050101.217') !== false) {
      $title_acc_code = '1102050101.217';
      $title_acc_name = 'ลูกหนี้ค่ารักษา UC - IP  บริการเฉพาะ (CR)';
  } elseif ($is_target_310 || strpos($id, '1102050101.310') !== false) {
      $title_acc_code = '1102050101.310';
      $title_acc_name = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง IP';
  } else {
      $base_code = strpos($id, '_') !== false ? explode('_', $id)[0] : $id;
      $account_info_q = mysqli_query($conn, "SELECT accountcode, accountname FROM imr_tb_debtor_rights_ipd WHERE accountcode = '$base_code' LIMIT 1");
      if ($account_info_q && $acc_row = mysqli_fetch_assoc($account_info_q)) {
          $title_acc_name = $acc_row['accountname'];
          $title_acc_code = $acc_row['accountcode'];
      }
  }

  $title_suffix = '';
  if (!empty($title_acc_name)) {
      $title_suffix = " ($title_acc_name รหัส $title_acc_code)";
  } elseif (!empty($title_acc_code)) {
      $title_suffix = " (รหัส $title_acc_code)";
  }

  $modal_full_title = '📑 รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน' . $title_suffix;

  $result = mysqli_query($conn, $sql);

  if ($result && mysqli_num_rows($result) > 0) {

    $raw_rows = [];
    $ans_to_detect = [];
    while ($r = mysqli_fetch_assoc($result)) {
        if (isset($r['cid'])) $r['cid'] = decrypt_data($r['cid']);
        if (isset($r['tel'])) $r['tel'] = decrypt_data($r['tel']);
        $raw_rows[] = $r;
        $ans_to_detect[] = $r['an'];
    }

    $base_id = explode('_', $id)[0];
    // ตรวจสอบว่าผังนี้เป็นผังเป้าหมาย CR หรือผังแม่ที่เปิดใช้งาน CR หรือไม่
    $is_cr_target_account_ipd = $is_cr_effective_ipd && $auto_split_ipd && !$is_kidney_click && (
        $is_target_217 ||
        ($base_id === '1102050101.202' && is_cr_parent_account_enabled($cr_config_ipd, $base_id))
    );

    // ตรวจสอบว่าผังนี้เป็นผังประกันสังคม IPD ที่เปิดใช้งาน SSS หรือไม่
    $is_sss_target_account_ipd = $is_sss_effective_ipd && $auto_split_sss_ipd && !$is_kidney_click && (
        $is_target_310 ||
        (in_array($base_id, ['1102050101.302', '1102050101.304']) && is_sss_parent_account_enabled($sss_config_ipd, $base_id))
    );

    $cr_map_ipd = [];
    $sss_map_ipd = [];
    $subgroup_stats_ipd = [];

    if ($is_cr_target_account_ipd) {
        if (!empty($ans_to_detect) && $conn2) {
            $cr_map_ipd = detect_cr_types_for_visit_list($conn2, $ans_to_detect, 'IPD', $cr_config_ipd, $my_hospcode, $actionipdlistonly);
        }

        $subgroup_stats_ipd = [
            'ALL' => [
                'id' => 'ALL',
                'title' => 'ทั้งหมด (All CR)',
                'short_name' => 'ทั้งหมด',
                'count' => 0,
                'debit' => 0.0
            ]
        ];
        $sg_source_ipd = isset($cr_config_ipd['subgroups_ipd']) ? $cr_config_ipd['subgroups_ipd'] : [];
        if (!empty($sg_source_ipd)) {
            foreach ($sg_source_ipd as $sg_id => $sg) {
                if (!is_cr_subgroup_enabled($cr_config_ipd, $sg_id, 'IPD', $actionipdlistonly)) continue;
                $subgroup_stats_ipd[$sg_id] = [
                    'id' => $sg_id,
                    'title' => $sg['title'] ?? $sg_id,
                    'short_name' => $sg['short_name'] ?? $sg_id,
                    'count' => 0,
                    'debit' => 0.0
                ];
            }
        }
    } elseif ($is_sss_target_account_ipd) {
        if (!empty($ans_to_detect) && $conn2) {
            $sss_map_ipd = detect_sss_types_for_visit_list($conn2, $ans_to_detect, 'IPD', $sss_config_ipd, $my_hospcode, $actionipdlistonly);
        }

        $subgroup_stats_ipd = [
            'ALL' => [
                'id' => 'ALL',
                'title' => 'ทั้งหมด (All SSS IPD)',
                'short_name' => 'ทั้งหมด',
                'count' => 0,
                'debit' => 0.0
            ]
        ];
        $sg_source_sss_ipd = isset($sss_config_ipd['subgroups_ipd']) ? $sss_config_ipd['subgroups_ipd'] : [];
        if (!empty($sg_source_sss_ipd)) {
            foreach ($sg_source_sss_ipd as $sg_id => $sg) {
                if (!is_sss_subgroup_enabled($sss_config_ipd, $sg_id, 'IPD', $actionipdlistonly)) continue;
                $subgroup_stats_ipd[$sg_id] = [
                    'id' => $sg_id,
                    'title' => $sg['title'] ?? $sg_id,
                    'short_name' => $sg['short_name'] ?? $sg_id,
                    'count' => 0,
                    'debit' => 0.0
                ];
            }
        }
    }

    $cr_bd_map_ipd = [];
    $sss_bd_map_ipd = [];
    if (!empty($ans_to_detect)) {
        $safe_det_ans = "'" . implode("','", array_map(function($a) use ($conn) { return mysqli_real_escape_string($conn, $a); }, $ans_to_detect)) . "'";
        $q_cr_bd_i = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_cr_breakdown WHERE visit_type = 'IPD' AND vn IN ($safe_det_ans)");
        if ($q_cr_bd_i) {
            while ($bd = mysqli_fetch_assoc($q_cr_bd_i)) {
                $cr_bd_map_ipd[$bd['vn']] = $bd;
            }
        }
        $q_sss_bd_i = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_sss_breakdown WHERE visit_type = 'IPD' AND vn IN ($safe_det_ans)");
        if ($q_sss_bd_i) {
            while ($bd = mysqli_fetch_assoc($q_sss_bd_i)) {
                $sss_bd_map_ipd[$bd['vn']] = $bd;
            }
        }
    }

    $all_display_rows_ipd = [];

    foreach ($raw_rows as $row) {
        $an = $row['an'];
        $row['income_original'] = floatval(cleanNum($row['income']));
        $row['incomediff_original'] = floatval(cleanNum($row['incomediff']));
        $is_transferred_visit = isset($all_cr_visits_ipd[$an]);
        $is_sss_transferred_visit = isset($all_sss_visits_ipd[$an]);

        if ($is_target_217 && !empty($all_cr_visits_ipd)) {
            if ($is_transferred_visit) {
                $vinfo = $all_cr_visits_ipd[$an];
                if (!empty($vinfo['is_kidney'])) continue;
                $row['debit'] = $vinfo['cr_amount'];
                $row['income'] = $vinfo['cr_amount'];
                $row['incomediff'] = 0.0;
                $row['cr_origin_acc'] = $vinfo['origin_accountcode'] ?? ($vinfo['origin_account'] ?? '1102050101.202');
                $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];

                if (isset($cr_bd_map_ipd[$an])) {
                    $row['follow_money'] = cleanNum($cr_bd_map_ipd[$an]['compensated']);
                    $row['bill'] = $cr_bd_map_ipd[$an]['bill'];
                    $row['billdate'] = $cr_bd_map_ipd[$an]['billdate'];
                } else {
                    $row['follow_money'] = min(cleanNum($row['follow_money']), floatval($vinfo['cr_amount']));
                }
            } else {
                $row['cr_origin_acc'] = '1102050101.217';
                $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];
                if (isset($cr_bd_map_ipd[$an])) {
                    $row['follow_money'] = cleanNum($cr_bd_map_ipd[$an]['compensated']);
                    $row['bill'] = $cr_bd_map_ipd[$an]['bill'];
                    $row['billdate'] = $cr_bd_map_ipd[$an]['billdate'];
                }
            }
            $all_display_rows_ipd[] = $row;

        } elseif ($is_target_310 && !empty($all_sss_visits_ipd)) {
            if ($is_sss_transferred_visit) {
                $vinfo = $all_sss_visits_ipd[$an];
                $row['debit'] = $vinfo['sss_amount'];
                $row['income'] = $vinfo['sss_amount'];
                $row['incomediff'] = 0.0;
                $row['sss_origin_acc'] = $vinfo['origin_accountcode'] ?? '1102050101.302';
                $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];

                if (isset($sss_bd_map_ipd[$an])) {
                    $row['follow_money'] = cleanNum($sss_bd_map_ipd[$an]['compensated']);
                    $row['bill'] = $sss_bd_map_ipd[$an]['bill'];
                    $row['billdate'] = $sss_bd_map_ipd[$an]['billdate'];
                } else {
                    $row['follow_money'] = min(cleanNum($row['follow_money']), floatval($vinfo['sss_amount']));
                }
            } else {
                $row['sss_origin_acc'] = '1102050101.310';
                $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];
                if (isset($sss_bd_map_ipd[$an])) {
                    $row['follow_money'] = cleanNum($sss_bd_map_ipd[$an]['compensated']);
                    $row['bill'] = $sss_bd_map_ipd[$an]['bill'];
                    $row['billdate'] = $sss_bd_map_ipd[$an]['billdate'];
                }
            }
            $all_display_rows_ipd[] = $row;

        } elseif (!empty($all_cr_visits_ipd) && !$is_kidney_click && $id === '1102050101.202') {
            if ($is_transferred_visit) {
                $vinfo = $all_cr_visits_ipd[$an];
                if ($vinfo['is_full_transfer']) {
                    continue;
                } else {
                    $row['debit'] = $vinfo['general_remain_amount'];
                    $row['cr_transferred_out'] = $vinfo['cr_amount'];
                    $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                    $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];

                    // หักเงินชดเชยที่โอนไปผังลูกออก
                    $cr_comp = isset($cr_bd_map_ipd[$an]) ? cleanNum($cr_bd_map_ipd[$an]['compensated']) : min(cleanNum($row['follow_money']), floatval($vinfo['cr_amount']));
                    $mother_comp = max(0, cleanNum($row['follow_money']) - $cr_comp);
                    $row['follow_money'] = $mother_comp;

                    // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                    if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                        $row['bill'] = '';
                        $row['billdate'] = '';
                    }
                    $all_display_rows_ipd[] = $row;
                }
            } else {
                $all_display_rows_ipd[] = $row;
            }
        } elseif (!empty($all_sss_visits_ipd) && !$is_kidney_click && $is_sss_parent_ipd) {
            if ($is_sss_transferred_visit) {
                $vinfo = $all_sss_visits_ipd[$an];
                if ($vinfo['is_full_transfer']) {
                    continue;
                } else {
                    $row['debit'] = $vinfo['general_remain_amount'];
                    $row['sss_transferred_out'] = $vinfo['sss_amount'];
                    $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                    $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];

                    // หักเงินชดเชยที่โอนไปผังลูกออก
                    $sss_comp = isset($sss_bd_map_ipd[$an]) ? cleanNum($sss_bd_map_ipd[$an]['compensated']) : min(cleanNum($row['follow_money']), floatval($vinfo['sss_amount']));
                    $mother_comp = max(0, cleanNum($row['follow_money']) - $sss_comp);
                    $row['follow_money'] = $mother_comp;

                    // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                    if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                        $row['bill'] = '';
                        $row['billdate'] = '';
                    }
                    $all_display_rows_ipd[] = $row;
                }
            } else {
                $all_display_rows_ipd[] = $row;
            }
        } else {
            $all_display_rows_ipd[] = $row;
        }
    }

    $i=0;$income=0;$incomediff=0;$debit=0;$follow_money=0;$compensatedaa=0;

    $parseThaiDate = function($dateStr) {
        return parseThaiDate($dateStr);
    };

    foreach ($all_display_rows_ipd as $row) {
      $i++;
      $an = $row['an'];

      $is_row_kidney = ($is_kidney_click || strpos($row["pttypename"], 'ฟอกไต') !== false || strpos($row["pttypename"], 'ไต') !== false);
      $row_color = $is_row_kidney ? 'style="color: #cd641f;"' : '';

      $cr_types = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : (isset($row['cr_types']) ? $row['cr_types'] : []);
      $cr_items = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : (isset($row['items']) ? $row['items'] : []);
      $sss_types = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
      $sss_items = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

      $all_subtypes_ipd = array_unique(array_merge($cr_types, $sss_types));
      $cr_type_str = implode(',', $all_subtypes_ipd);

      // 🌟 คำนวณยอดเงินแยกตามกลุ่มย่อย CR (IPD) จากรายการ items จริง เพื่อป้องกันการปนกันของยอดเงิน
      $cr_subgroup_amounts_i = [];
      if (!empty($cr_items)) {
          foreach ($cr_items as $cit) {
              $ctype = trim((string)($cit['cr_type'] ?? ''));
              if (!empty($ctype)) {
                  if (!isset($cr_subgroup_amounts_i[$ctype])) {
                      $cr_subgroup_amounts_i[$ctype] = 0.0;
                  }
                  $cr_subgroup_amounts_i[$ctype] += floatval(cleanNum($cit['amount'] ?? 0));
              }
          }
      }
      if (!empty($cr_types)) {
          $allocated_sum_i = array_sum($cr_subgroup_amounts_i);
          $unallocated_i = max(0, floatval(cleanNum($row['debit'])) - $allocated_sum_i);
          $zero_types_i = [];
          foreach ($cr_types as $ct) {
              if (!isset($cr_subgroup_amounts_i[$ct]) || $cr_subgroup_amounts_i[$ct] <= 0.001) {
                  $zero_types_i[] = $ct;
              }
          }
          if ($unallocated_i > 0 && !empty($zero_types_i)) {
              $each_amt_i = $unallocated_i / count($zero_types_i);
              foreach ($zero_types_i as $zt) {
                  $cr_subgroup_amounts_i[$zt] = $each_amt_i;
              }
          }
      }

      // คำนวณยอดเงินแยกตามกลุ่มย่อย SSS (IPD)
      $sss_subgroup_amounts_i = [];
      if (!empty($sss_items)) {
          foreach ($sss_items as $sit) {
              $stype = trim((string)($sit['cr_type'] ?? ''));
              if (!empty($stype)) {
                  if (!isset($sss_subgroup_amounts_i[$stype])) {
                      $sss_subgroup_amounts_i[$stype] = 0.0;
                  }
                  $sss_subgroup_amounts_i[$stype] += floatval(cleanNum($sit['amount'] ?? 0));
              }
          }
      }
      if (!empty($sss_types)) {
          $allocated_sum_si = array_sum($sss_subgroup_amounts_i);
          $unallocated_si = max(0, floatval(cleanNum($row['debit'])) - $allocated_sum_si);
          $zero_types_si = [];
          foreach ($sss_types as $st) {
              if (!isset($sss_subgroup_amounts_i[$st]) || $sss_subgroup_amounts_i[$st] <= 0.001) {
                  $zero_types_si[] = $st;
              }
          }
          if ($unallocated_si > 0 && !empty($zero_types_si)) {
              $each_amt_si = $unallocated_si / count($zero_types_si);
              foreach ($zero_types_si as $zs) {
                  $sss_subgroup_amounts_i[$zs] = $each_amt_si;
              }
          }
      }

      if ($is_cr_target_account_ipd) {
          $subgroup_stats_ipd['ALL']['count']++;
          $subgroup_stats_ipd['ALL']['debit'] += cleanNum($row['debit']);
          foreach ($cr_types as $ct) {
              if (isset($subgroup_stats_ipd[$ct])) {
                  $subgroup_stats_ipd[$ct]['count']++;
                  $sg_amt_i = isset($cr_subgroup_amounts_i[$ct]) && $cr_subgroup_amounts_i[$ct] > 0 
                      ? $cr_subgroup_amounts_i[$ct] 
                      : cleanNum($row['debit']);
                  $subgroup_stats_ipd[$ct]['debit'] += $sg_amt_i;
              }
          }
      } elseif ($is_sss_target_account_ipd) {
          $subgroup_stats_ipd['ALL']['count']++;
          $subgroup_stats_ipd['ALL']['debit'] += cleanNum($row['debit']);
          foreach ($sss_types as $st) {
              if (isset($subgroup_stats_ipd[$st])) {
                  $subgroup_stats_ipd[$st]['count']++;
                  $sg_amt_si = isset($sss_subgroup_amounts_i[$st]) && $sss_subgroup_amounts_i[$st] > 0 
                      ? $sss_subgroup_amounts_i[$st] 
                      : cleanNum($row['debit']);
                  $subgroup_stats_ipd[$st]['debit'] += $sg_amt_si;
              }
          }
      }

      $cr_badge_html = '';
      if ($is_cr_target_account_ipd && (!empty($cr_types) || !empty($row['cr_origin_acc']) || !empty($row['cr_transferred_out']))) {
          $original_debit_display_ipd = 0.0;
          $cr_amt_display_ipd = 0.0;
          $remain_debit_display_ipd = 0.0;

          if ($is_target_217) {
              $cr_amt_display_ipd = floatval(cleanNum($row['debit']));
              if (isset($all_cr_visits_ipd[$an])) {
                  $v_info = $all_cr_visits_ipd[$an];
                  $original_debit_display_ipd = floatval($v_info['original_debit'] ?? ($v_info['cr_amount'] + $v_info['general_remain_amount']));
                  $remain_debit_display_ipd = floatval($v_info['general_remain_amount'] ?? 0);
              } else {
                  $original_debit_display_ipd = $cr_amt_display_ipd;
                  $remain_debit_display_ipd = 0.0;
              }
          } elseif (!empty($row['cr_transferred_out']) && $row['cr_transferred_out'] > 0) {
              // ฝั่งแม่ที่มีการตัดยอด CR ออกไป
              $cr_amt_display_ipd = floatval($row['cr_transferred_out']);
              $remain_debit_display_ipd = floatval(cleanNum($row['debit']));
              $original_debit_display_ipd = $remain_debit_display_ipd + $cr_amt_display_ipd;
          } else {
              $original_debit_display_ipd = floatval(cleanNum($row['debit']));
              $cr_amt_display_ipd = floatval(cleanNum($row['debit']));
              $remain_debit_display_ipd = 0.0;
          }

          $cr_payload_data = [
              'vn' => (string)$row['an'],
              'hn' => (string)($row['hn'] ?? ''),
              'ptname' => (string)($row['ptname'] ?? ''),
              'vstdate' => (string)($row['dchdate'] ?? ''),
              'pttypename' => (string)($row['pttypename'] ?? ''),
              'hospmain' => '',
              'income' => floatval(cleanNum($row['income_original'] ?? ($row['income'] ?? $original_debit_display_ipd))),
              'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
              'debit' => $original_debit_display_ipd,
              'origin_acc' => (string)($row['cr_origin_acc'] ?? $row['accountcode']),
              'target_acc' => '1102050101.217',
              'cr_types' => $cr_types,
              'cr_amount' => $cr_amt_display_ipd,
              'remain_debit' => $remain_debit_display_ipd,
              'items' => $cr_items
          ];
          $payload_json_str = htmlspecialchars(json_encode($cr_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

          foreach ($cr_types as $ct) {
              $ct_amt = isset($cr_subgroup_amounts_i[$ct]) ? floatval($cr_subgroup_amounts_i[$ct]) : 0;
              $ct_amt_label = ($ct_amt > 0) ? ' (฿' . number_format($ct_amt, 2) . ')' : '';
              $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge" data-cr-subgroup="' . htmlspecialchars($ct) . '" data-filter-subgroup="' . htmlspecialchars($ct) . '" style="font-size:11px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'' . htmlspecialchars($ct, ENT_QUOTES) . '\')" title="คลิกดูรายการเฉพาะกลุ่ม ' . htmlspecialchars($ct) . $ct_amt_label . '">' . htmlspecialchars($ct) . '</span>';
          }

          if (!empty($row['cr_origin_acc']) && $row['cr_origin_acc'] !== '1102050101.217') {
              $orig_short = str_replace('1102050101.', '.', $row['cr_origin_acc']);
              $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'ALL\')" title="โอนมาจากผัง ' . htmlspecialchars($row['cr_origin_acc']) . ' (คลิกเพื่อดูทุกกลุ่มย่อยที่โอนมา)"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
          } elseif (!empty($row['cr_transferred_out']) && $row['cr_transferred_out'] > 0) {
              $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 cr-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this, \'ALL\')" title="ตัดยอด CR ออกไปผัง .217 จำนวน ฿' . number_format($row['cr_transferred_out'], 2) . ' (คลิกเพื่อดูรายการ)"><i class="bx bx-cut"></i> ตัด CR -฿' . number_format($row['cr_transferred_out'], 2) . '</span>';
          }
      }

      // SSS Badge & Breakdown Payload (IPD)
      $sss_types = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
      $sss_items = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

      if ($is_sss_target_account_ipd && (!empty($sss_types) || !empty($row['sss_origin_acc']) || !empty($row['sss_transferred_out']))) {
          $original_debit_sss_ipd = 0.0;
          $sss_amt_display_ipd = 0.0;
          $remain_debit_sss_ipd = 0.0;

          if ($is_target_310) {
              $sss_amt_display_ipd = floatval(cleanNum($row['debit']));
              if (isset($all_sss_visits_ipd[$an])) {
                  $v_info = $all_sss_visits_ipd[$an];
                  $original_debit_sss_ipd = floatval($v_info['original_debit'] ?? ($v_info['sss_amount'] + $v_info['general_remain_amount']));
                  $remain_debit_sss_ipd = floatval($v_info['general_remain_amount'] ?? 0);
              } else {
                  $original_debit_sss_ipd = $sss_amt_display_ipd;
                  $remain_debit_sss_ipd = 0.0;
              }
          } elseif (!empty($row['sss_transferred_out']) && $row['sss_transferred_out'] > 0) {
              $sss_amt_display_ipd = floatval($row['sss_transferred_out']);
              $remain_debit_sss_ipd = floatval(cleanNum($row['debit']));
              $original_debit_sss_ipd = $remain_debit_sss_ipd + $sss_amt_display_ipd;
          } else {
              $original_debit_sss_ipd = floatval(cleanNum($row['debit']));
              $sss_amt_display_ipd = floatval(cleanNum($row['debit']));
              $remain_debit_sss_ipd = 0.0;
          }

          $sss_payload_data = [
              'vn' => (string)$row['an'],
              'hn' => (string)($row['hn'] ?? ''),
              'ptname' => (string)($row['ptname'] ?? ''),
              'vstdate' => (string)($row['dchdate'] ?? ($row['admdate'] ?? '')),
              'pttypename' => (string)($row['pttypename'] ?? ($row['pttype_eclaim_name'] ?? '')),
              'hospmain' => '',
              'income' => floatval(cleanNum($row['income_original'] ?? ($row['income'] ?? $original_debit_sss_ipd))),
              'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
              'debit' => $original_debit_sss_ipd,
              'origin_acc' => (string)($row['sss_origin_acc'] ?? $row['accountcode']),
              'target_acc' => '1102050101.310',
              'sss_types' => $sss_types,
              'sss_amount' => $sss_amt_display_ipd,
              'remain_debit' => $remain_debit_sss_ipd,
              'items' => $sss_items
          ];
          $sss_payload_json_str = htmlspecialchars(json_encode($sss_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

          foreach ($sss_types as $st) {
              $st_amt = isset($sss_subgroup_amounts_i[$st]) ? floatval($sss_subgroup_amounts_i[$st]) : 0;
              $st_amt_label = ($st_amt > 0) ? ' (฿' . number_format($st_amt, 2) . ')' : '';
              $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge" data-cr-subgroup="' . htmlspecialchars($st) . '" data-filter-subgroup="' . htmlspecialchars($st) . '" style="font-size:11px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="คลิกดูรายการ Instrument ประกันสังคม ' . htmlspecialchars($st) . $st_amt_label . '">' . htmlspecialchars($st) . '</span>';
          }

          if (!empty($row['sss_origin_acc']) && $row['sss_origin_acc'] !== '1102050101.310') {
              $orig_short = str_replace('1102050101.', '.', $row['sss_origin_acc']);
              $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="โอนมาจากผัง ' . htmlspecialchars($row['sss_origin_acc']) . '"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
          } elseif (!empty($row['sss_transferred_out']) && $row['sss_transferred_out'] > 0) {
              $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 sss-item-badge-click cr-subgroup-badge cr-badge-all" data-filter-subgroup="ALL" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="ตัดยอด Instrument ออกไปผัง .310 จำนวน ฿' . number_format($row['sss_transferred_out'], 2) . '"><i class="bx bx-cut"></i> ตัด Instrument -฿' . number_format($row['sss_transferred_out'], 2) . '</span>';
          }
      }

      $all_subgroup_amounts_i = array_merge($cr_subgroup_amounts_i, $sss_subgroup_amounts_i);
      $amounts_json_i = htmlspecialchars(json_encode($all_subgroup_amounts_i, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

      echo '  <tr class="main-patient-row" data-cr-type="' . htmlspecialchars($cr_type_str) . '" data-cr-amounts="' . $amounts_json_i . '" data-full-debit="' . floatval(cleanNum($row['debit'])) . '" data-full-income="' . floatval(cleanNum($row['income'])) . '" '.$row_color.'><td style="text-align: center;"><input type="checkbox" name="select[]" value="'.$row["an"].'" style=" width: 20px; height: 20px;"></td>';

      // =================================================================
      // CASE 1: มีเลข Bill แล้ว (จบหนี้)
      // =================================================================
      if($row["bill"]){

           $days_show = "-";
           if(!empty($row["dchdate"])){
               $start = $parseThaiDate($row["dchdate"]);
               $end = new DateTime();
               $found_end_date = false;

               if(!empty($row["billdate"])){
                   $chkDate = $parseThaiDate($row["billdate"]);
                   if($chkDate) {
                       $end = $chkDate;
                       $found_end_date = true;
                   }
               }

               if(!$found_end_date && !empty($row["mobile"])){
                   $chkDateMobile = $parseThaiDate($row["mobile"]);
                   if($chkDateMobile) {
                       $end = $chkDateMobile;
                   }
               }

               if($start){
                   $diff = $start->diff($end);
                   $days_show = number_format($diff->days) . " วัน";
               }
           }

           $an_check = $row["an"];
           $chk_history = mysqli_query($conn, "SELECT id FROM imr_tb_payment_history WHERE ref_vn_an = '$an_check' LIMIT 1");
           $is_partial = (mysqli_num_rows($chk_history) > 0);

           if ($is_partial) {
               $balance_check = cleanNum($row["debit"]) - cleanNum($row["follow_money"]);

               if ($balance_check <= 0.01) {
                   $show_bill_main = $row["bill"];
                   $show_date_main = $row["billdate"];
                   $bg_color_main  = 'background: #e6ffe6;'; // เขียวอ่อน = จ่ายครบแล้ว
               } else {
                   $show_bill_main = "";
                   $show_date_main = "";
                   $bg_color_main  = 'background: #fff5e7;';
               }
           } else {
               $show_bill_main = $row["bill"];
               $show_date_main = $row["billdate"];
               $bg_color_main  = 'background: #fff5e7;';
           }

           echo ' <td style="text-align: center;">'.$i.'</td>
                  <td style="text-align: center;">'.$row["an"].'</td>
                  <td style="text-align: center;">'.$row["cid"].'</td>
                  <td>'.$row["ptname"].$cr_badge_html.'</td>
                  <td>'.$row["pttypename"].'</td>
                  <td style="text-align: center;">'.$row["admdate"].'-'.$row["dchdate"].'</td>
                  <td style="text-align: center;">'.$days_show.'</td>
                  <td style="text-align: right;">'.formatMoney($row["income"], 2).'</td>
                  <td style="text-align: right;">'.formatMoney($row["incomediff"], 2).'</td>
                  <td style="text-align: right;">'.formatMoney($row["debit"], 2).'</td>
                  <td style="text-align: center;padding: 0px;background: #fff5e7;" contenteditable data-data1i="'.$row["an"].'" >'.formatMoney($row["follow_money"], 2).'</td>
                  <td class="mask-bill" style="text-align: center;'.$bg_color_main.'" contenteditable data-data2i="'.$row["an"].'" >'.$show_bill_main.'</td>
                  <td class="mask-date" style="text-align: center;'.$bg_color_main.'" contenteditable data-data3i="'.$row["an"].'" >'.$show_date_main.'</td>
                  <td style="text-align: center;padding: 0px;background: #fff;" ></td>
                  <td style="text-align: center;padding: 0px;background: #fff;" >'.$row["hn"].'</td>
              </tr>';

      }else{
          // =================================================================
          // CASE 2: ยังไม่มี Bill แต่มีข้อมูลใน Mobile (Regex)
          // =================================================================
          $an =$row["an"];

          $sql1 = "SELECT o.an,stm.compensated,o.mobile,stm.rep,
                  SUM(dckd.compensated) as compensatedckd,
                  SUM(ckd_ofc.amount) as compensatedckd_ofc,
                  dckd.rep as repckd,
                  ckd_ofc.stm_doc as stm_doc
                  FROM imr_tb_debtor_rights_ipd o
                  LEFT JOIN imr_tb_check_invoice stm on stm.vn=o.an
                  LEFT JOIN imr_tb_seamless_dckd dckd on dckd.vn=o.an
                  LEFT JOIN imr_tb_seamless_dckd_ofc ckd_ofc on ckd_ofc.vn=o.an
                  WHERE o.an='$an' and o.mobile REGEXP '^[0-9]{2,4}/[0-9]{2,4}-[0-9]{1,2}/[0-9]{1,2}/[0-9]{3,4}$' GROUP BY o.an ";

          $result1 = mysqli_query($conn, $sql1);

            if (mysqli_num_rows($result1) > 0) {
              while ($row1 = mysqli_fetch_assoc($result1)) {
                if (isset($row1['cid'])) $row1['cid'] = decrypt_data($row1['cid']);
                if (isset($row1['tel'])) $row1['tel'] = decrypt_data($row1['tel']);

                if($row1["compensated"]){ $compensated=$row1["compensated"]; }
                else if($row1["compensatedckd"]){ $compensated=$row1["compensatedckd"]; }
                else{ $compensated=$row1["compensatedckd_ofc"]; }

                if($row1["rep"]){ $rep=$row1["rep"]; }
                else if($row1["repckd"]){ $rep=$row1["repckd"]; }
                else{ $rep=$row1["stm_doc"]; }

                 list($bill1, $billdate1) = explode('-', $row1["mobile"]);

                 $days_show = "-";
                 if(!empty($row["dchdate"])){
                     $start = $parseThaiDate($row["dchdate"]);
                     $end = new DateTime();

                     if(!empty($billdate1)){
                          $chkDate = $parseThaiDate($billdate1);
                          if($chkDate) $end = $chkDate;
                     }

                     if($start){
                         $diff = $start->diff($end);
                         $days_show = number_format($diff->days) . " วัน";
                     }
                 }

                 echo '<td style="text-align: center;">'.$i.'</td>
                              <td style="text-align: center;">'.$row["an"].'</td>
                              <td style="text-align: center;">'.$row["cid"].'</td>
                              <td>'.$row["ptname"].$cr_badge_html.'</td>
                              <td>'.$row["pttypename"].'</td>
                              <td style="text-align: center;">'.$row["admdate"].'-'.$row["dchdate"].'</td>
                              <td style="text-align: center;">'.$days_show.'</td>
                              <td style="text-align: right;">'.formatMoney($row["income"], 2).'</td>
                              <td style="text-align: right;">'.formatMoney($row["incomediff"], 2).'</td>
                              <td style="text-align: right;">'.formatMoney($row["debit"], 2).'</td>
                              <td style="text-align: center;padding: 0px;" >'.formatMoney($compensated, 2).'</td>
                              <td style="text-align: center;padding: 0px;" >'.$bill1.'</td>
                              <td style="text-align: center;padding: 0px;" >'.$billdate1.'</td>
                              <td style="text-align: center;padding: 0px;" >'.$rep.'</td>
                              <td style="text-align: center;padding: 0px;background: #fff;" >'.$row["hn"].'</td>
                          </tr>';

                $compensatedaa = $compensatedaa + cleanNum($row1["compensated"]) + cleanNum($row1["compensatedckd"]) + cleanNum($row1["compensatedckd_ofc"]);
              }

            }else{
                 // =================================================================
                 // CASE 3: ยังไม่จ่าย (Pending)
                 // =================================================================
                 $days_show = "-";
                 if(!empty($row["dchdate"])){
                     $start = $parseThaiDate($row["dchdate"]);
                     $end = new DateTime();
                     if($start){
                         $diff = $start->diff($end);
                         $days_show = number_format($diff->days) . " วัน";
                     }
                 }

                 // [แก้ไข] IPD: ถ้ามีการแบ่งจ่ายและจ่ายครบแล้ว (follow_money >= debit) ให้ดึงเลขบิลงวดล่าสุดมาแสดง
                 $an_c3       = $row["an"];
                 $c3_bill     = $row["bill"];     // ค่าเริ่มต้น (อาจว่าง)
                 $c3_billdate = $row["billdate"]; // ค่าเริ่มต้น (อาจว่าง)
                 $c3_bg       = 'background: #fff5e7;';
                 $c3_balance  = cleanNum($row["debit"]) - cleanNum($row["follow_money"]);

                 if (cleanNum($row["follow_money"]) > 0 && $c3_balance <= 0.01) {
                     $sql_c3 = "SELECT bill_no, bill_date FROM imr_tb_payment_history
                                WHERE ref_vn_an = '$an_c3' AND bill_no != ''
                                ORDER BY id DESC LIMIT 1";
                     $res_c3 = mysqli_query($conn, $sql_c3);
                     if ($res_c3 !== false && ($row_c3 = mysqli_fetch_assoc($res_c3))) {
                         $c3_bill = $row_c3['bill_no'];
                         if (!empty($row_c3['bill_date'])) {
                             $d_c3 = date('d', strtotime($row_c3['bill_date']));
                             $m_c3 = date('m', strtotime($row_c3['bill_date']));
                             $y_c3 = date('Y', strtotime($row_c3['bill_date'])) + 543;
                             $c3_billdate = "$d_c3/$m_c3/$y_c3";
                         }
                         $c3_bg = 'background: #e6ffe6;'; // เขียวอ่อน = จ่ายครบแล้ว
                     }
                 }
                 echo ' <td style="text-align: center;">'.$i.'</td>
                            <td style="text-align: center;">'.$row["an"].'</td>
                            <td style="text-align: center;">'.$row["cid"].'</td>
                            <td>'.$row["ptname"].$cr_badge_html.'</td>
                            <td>'.$row["pttypename"].'</td>
                            <td style="text-align: center;">'.$row["admdate"].'-'.$row["dchdate"].'</td>
                            <td style="text-align: center; color: red;">'.$days_show.'</td>
                            <td style="text-align: right;">'.formatMoney($row["income"], 2).'</td>
                            <td style="text-align: right;">'.formatMoney($row["incomediff"], 2).'</td>
                            <td style="text-align: right;">'.formatMoney($row["debit"], 2).'</td>
                            <td style="text-align: center;padding: 0px;'.$c3_bg.'" contenteditable data-data1i="'.$row["an"].'" >'.formatMoney($row["follow_money"], 2).'</td>
                            <td class="mask-bill" style="text-align: center;'.$c3_bg.'" contenteditable data-data2i="'.$row["an"].'" >'.$c3_bill.'</td>
                            <td class="mask-date" style="text-align: center;'.$c3_bg.'" contenteditable data-data3i="'.$row["an"].'" >'.$c3_billdate.'</td>
                            <td style="text-align: center;padding: 0px;background: #fff;"> </td>
                            <td style="text-align: center;padding: 0px;background: #fff;" >'.$row["hn"].'</td>
                        </tr>';
            }
      }

      // =================================================================
      // แสดงรายการแบ่งชำระย่อย (Running Balance)
      // =================================================================
      $vn_ref = $row["an"];
      $sql_sub = "SELECT * FROM imr_tb_payment_history WHERE ref_vn_an = '$vn_ref' ORDER BY id ASC";
      $result_sub = mysqli_query($conn, $sql_sub);

      if (mysqli_num_rows($result_sub) > 0) {
          $count_sub = 1;
          $running_balance = cleanNum($row["debit"]);

          while ($sub = mysqli_fetch_assoc($result_sub)) {
            if (isset($sub['cid'])) $sub['cid'] = decrypt_data($sub['cid']);
            if (isset($sub['tel'])) $sub['tel'] = decrypt_data($sub['tel']);
            $pay_amount = $sub['pay_amount'];
            $running_balance = $running_balance - cleanNum($pay_amount);

            $sub_date_show = "-";
            if(!empty($sub['bill_date'])){
                $d = date('d', strtotime($sub['bill_date']));
                $m = date('m', strtotime($sub['bill_date']));
                $y = date('Y', strtotime($sub['bill_date'])) + 543;
                $sub_date_show = "$d/$m/$y";
            }

            $safe_note = htmlspecialchars($sub['note'], ENT_QUOTES, 'UTF-8');

            echo '<tr class="sub-payment-row" style="background-color: #f9fcff; font-size: 13px; color: #444;">';
            echo '<td colspan="9" style="text-align: right; border-right: none; color: #0027e5;">
                    <i class="bx bx-subdirectory-right"></i> แบ่งจ่ายงวดที่ '.$count_sub.' <small class="text-muted">'.$sub['note'].'</small>
                  </td>';
            echo '<td style="text-align: right; font-weight: bold; color: #008a0e;">'.formatMoney($pay_amount, 2).'</td>';
            echo '<td style="text-align: right; font-weight: bold; color: #d9534f; background-color: #fff0f0;">'.formatMoney($running_balance, 2).'</td>';
            echo '<td style="text-align: center; color: #0027e5;">'.formatMoney($pay_amount, 2).'</td>';
            echo '<td style="text-align: center;">'.$sub['bill_no'].'</td>';
            echo '<td style="text-align: center;">'.$sub_date_show.'</td>';
            echo '<td style="text-align: center;">
                    <a href="javascript:void(0)" class="btn-edit-partial"
                       onclick="editPartial(this)"
                       data-id="'.$sub['id'].'"
                       data-vn="'.$vn_ref.'"
                       data-amount="'.$sub['pay_amount'].'"
                       data-bill="'.$sub['bill_no'].'"
                       data-date="'.$sub_date_show.'"
                       data-note="'.$safe_note.'">
                        <i class="bx bx-edit text-warning" style="cursor: pointer;"></i>
                    </a>
                    &nbsp;
                    <a href="javascript:void(0)" class="btn-delete-partial"
                       onclick="deletePartial(this)"
                       data-id="'.$sub['id'].'"
                       data-vn="'.$vn_ref.'"
                       data-type="IPD">
                        <i class="bx bx-trash text-danger" style="cursor: pointer;"></i>
                    </a>
                  </td>';
            echo '<td style="background-color: #fff;"></td>';
            echo '</tr>';

            $count_sub++;
          }
      }

      $income = $income + cleanNum($row["income"]);
      $incomediff = $incomediff + cleanNum($row["incomediff"]);
      $debit = $debit + cleanNum($row["debit"]);
      $follow_money = $follow_money + cleanNum($row["follow_money"]);
    }

    // คำนวณเปอเซ็นต์ยอดชดเชยท้ายตารางรวม
    $percen = 0;
    if($debit > 0) {
        $percen = (($follow_money+$compensatedaa)/$debit)*100;
    }

    echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
    echo '<tr class="summary-total-row" style="font-weight: bold; background: linear-gradient(268deg, #2a3d5d 0%, #1e2b4b 100%) !important; color: #ffffff !important;">
            <td style="text-align: center; background: transparent !important; color: #ffffff !important;" colspan="8">
                รวม <span id="i0" style="display:none;">'.$i.'</span>
                <span id="modal_report_title_data" style="display:none;" data-title="'.htmlspecialchars($modal_full_title, ENT_QUOTES, 'UTF-8').'"></span>
                <script id="cr_subgroup_summary_json" type="application/json">' . json_encode($subgroup_stats_ipd, JSON_UNESCAPED_UNICODE) . '</script>
            </td>
            <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="i1">&nbsp;'.formatMoney($income, 2).'</td>
            <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="i2">&nbsp;'.formatMoney($incomediff, 2).'</td>
            <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="i3">&nbsp;'.formatMoney($debit, 2).'</td>
            <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="i4">&nbsp;'.number_format($follow_money+$compensatedaa,2).' ('.formatMoney($percen, 2).')</td>
            <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="i5">&nbsp;</td>
            <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="i6">&nbsp;</td>
            <td style="text-align: right; background: transparent !important; color: #ffffff !important;" id="i7">
                <input readonly type="text" id="typecc" name="typecc" value="IPD" data-iid="'.$id.'" readonly style="border: none;width: 40px;text-align: center;background-color: transparent;color: #fff;" />
            </td>
            <td style="background: transparent !important; color: #ffffff !important;"></td>
          </tr>';
  } else {
    echo '<tr class="summary-total-row">
            <td colspan="16" style="text-align: center; padding: 25px; color: #64748b;">
                <i class="bx bx-info-circle fs-4 me-1"></i> ไม่พบรายการลูกหนี้รายตัวในผังบัญชีนี้
                <span id="modal_report_title_data" style="display:none;" data-title="'.htmlspecialchars($modal_full_title, ENT_QUOTES, 'UTF-8').'"></span>
                <script id="cr_subgroup_summary_json" type="application/json">{}</script>
            </td>
          </tr>';
  }
}









if(isset($_POST['follow_money'])){

 $vn= mysqli_real_escape_string($conn, $_POST['follow_moneyvn']);
 $data= mysqli_real_escape_string($conn, $_POST['follow_money']);
 $typecc= mysqli_real_escape_string($conn, $_POST['typecc'] ?? 'OPD');
 $target_accountcode = isset($_POST['accountcode']) ? mysqli_real_escape_string($conn, $_POST['accountcode']) : '';

 $_SESSION['typecc'] = ($typecc === "OPD") ? "OPD" : "IPD";

 $is_cr_child = (strpos($target_accountcode, '1102050101.216') !== false || strpos($target_accountcode, '1102050101.217') !== false);
 $is_sss_child = (strpos($target_accountcode, '1102050101.309') !== false || strpos($target_accountcode, '1102050101.310') !== false);

 if ($is_cr_child) {
     $clean_amt = floatval(cleanNum($data));
     $sql1 = "UPDATE imr_tb_debtor_cr_breakdown 
              SET compensated = '$clean_amt',
                  settle_status = (CASE WHEN '$clean_amt' >= item_amount THEN 'SETTLED' WHEN '$clean_amt' > 0 THEN 'PARTIAL' ELSE 'WAIT' END)
              WHERE visit_type = '$typecc' AND vn = '$vn'";
 } elseif ($is_sss_child) {
     $clean_amt = floatval(cleanNum($data));
     $sql1 = "UPDATE imr_tb_debtor_sss_breakdown 
              SET compensated = '$clean_amt',
                  settle_status = (CASE WHEN '$clean_amt' >= item_amount THEN 'SETTLED' WHEN '$clean_amt' > 0 THEN 'PARTIAL' ELSE 'WAIT' END)
              WHERE visit_type = '$typecc' AND vn = '$vn'";
 } else {
     $sql1 = "UPDATE imr_tb_debtor_rights_opd SET follow_money = '$data' WHERE vn='$vn' ";
 }

  if (mysqli_query($conn, $sql1)) {
    echo 'true money = '.$data;
  } else {
    echo "ไม่สามารถทำรายการได้ : " . mysqli_error($conn);
  }

}

if(isset($_POST['bill'])){

 $vn= mysqli_real_escape_string($conn, $_POST['follow_moneyvn']);
 $data= mysqli_real_escape_string($conn, $_POST['bill']);
 $typecc= mysqli_real_escape_string($conn, $_POST['typecc'] ?? 'OPD');
 $target_accountcode = isset($_POST['accountcode']) ? mysqli_real_escape_string($conn, $_POST['accountcode']) : '';

 $_SESSION['typecc'] = ($typecc === "OPD") ? "OPD" : "IPD";

 $is_cr_child = (strpos($target_accountcode, '1102050101.216') !== false || strpos($target_accountcode, '1102050101.217') !== false);
 $is_sss_child = (strpos($target_accountcode, '1102050101.309') !== false || strpos($target_accountcode, '1102050101.310') !== false);

 if ($is_cr_child) {
     $status = (!empty($data)) ? 'SETTLED' : 'WAIT';
     $sql1 = "UPDATE imr_tb_debtor_cr_breakdown 
              SET bill = '$data', settle_status = '$status'
              WHERE visit_type = '$typecc' AND vn = '$vn'";
 } elseif ($is_sss_child) {
     $status = (!empty($data)) ? 'SETTLED' : 'WAIT';
     $sql1 = "UPDATE imr_tb_debtor_sss_breakdown 
              SET bill = '$data', settle_status = '$status'
              WHERE visit_type = '$typecc' AND vn = '$vn'";
 } else {
     $sql1 = "UPDATE imr_tb_debtor_rights_opd SET bill = '$data' WHERE vn='$vn' ";
 }

  if (mysqli_query($conn, $sql1)) {
    echo 'true bill = '.$data;
  } else {
    echo "ไม่สามารถทำรายการได้ : " . mysqli_error($conn);
  }

}

if(isset($_POST['billdate'])){

 $vn= mysqli_real_escape_string($conn, $_POST['follow_moneyvn']);
 $data= mysqli_real_escape_string($conn, $_POST['billdate']);
 $typecc= mysqli_real_escape_string($conn, $_POST['typecc'] ?? 'OPD');
 $target_accountcode = isset($_POST['accountcode']) ? mysqli_real_escape_string($conn, $_POST['accountcode']) : '';

 $_SESSION['typecc'] = ($typecc === "OPD") ? "OPD" : "IPD";

 $is_cr_child = (strpos($target_accountcode, '1102050101.216') !== false || strpos($target_accountcode, '1102050101.217') !== false);
 $is_sss_child = (strpos($target_accountcode, '1102050101.309') !== false || strpos($target_accountcode, '1102050101.310') !== false);

 if ($is_cr_child) {
     $sql1 = "UPDATE imr_tb_debtor_cr_breakdown SET billdate = '$data' WHERE visit_type = '$typecc' AND vn = '$vn'";
 } elseif ($is_sss_child) {
     $sql1 = "UPDATE imr_tb_debtor_sss_breakdown SET billdate = '$data' WHERE visit_type = '$typecc' AND vn = '$vn'";
 } else {
     $sql1 = "UPDATE imr_tb_debtor_rights_opd SET billdate = '$data' WHERE vn='$vn' ";
 }

  if (mysqli_query($conn, $sql1)) {
    echo 'true billdate = '.$data;
  } else {
    echo "ไม่สามารถทำรายการได้ : " . mysqli_error($conn);
  }

}

if(isset($_POST['follow_money_i'])){

 $vn= mysqli_real_escape_string($conn, $_POST['follow_moneyvn']);
 $data= mysqli_real_escape_string($conn, $_POST['follow_money_i']);
 $typecc= mysqli_real_escape_string($conn, $_POST['typecc'] ?? 'IPD');
 $target_accountcode = isset($_POST['accountcode']) ? mysqli_real_escape_string($conn, $_POST['accountcode']) : '';

 $_SESSION['typecc'] = ($typecc === "OPD") ? "OPD" : "IPD";

 $is_cr_child = (strpos($target_accountcode, '1102050101.216') !== false || strpos($target_accountcode, '1102050101.217') !== false);
 $is_sss_child = (strpos($target_accountcode, '1102050101.309') !== false || strpos($target_accountcode, '1102050101.310') !== false);

 if ($is_cr_child) {
     $clean_amt = floatval(cleanNum($data));
     $sql1 = "UPDATE imr_tb_debtor_cr_breakdown 
              SET compensated = '$clean_amt',
                  settle_status = (CASE WHEN '$clean_amt' >= item_amount THEN 'SETTLED' WHEN '$clean_amt' > 0 THEN 'PARTIAL' ELSE 'WAIT' END)
              WHERE visit_type = '$typecc' AND vn = '$vn'";
 } elseif ($is_sss_child) {
     $clean_amt = floatval(cleanNum($data));
     $sql1 = "UPDATE imr_tb_debtor_sss_breakdown 
              SET compensated = '$clean_amt',
                  settle_status = (CASE WHEN '$clean_amt' >= item_amount THEN 'SETTLED' WHEN '$clean_amt' > 0 THEN 'PARTIAL' ELSE 'WAIT' END)
              WHERE visit_type = '$typecc' AND vn = '$vn'";
 } else {
     $sql1 = "UPDATE imr_tb_debtor_rights_ipd SET follow_money = '$data' WHERE an='$vn' ";
 }

  if (mysqli_query($conn, $sql1)) {
    echo 'true money_i = '.$data;
  } else {
    echo "ไม่สามารถทำรายการได้ : " . mysqli_error($conn);
  }

}

if(isset($_POST['bill_i'])){

 $vn= mysqli_real_escape_string($conn, $_POST['follow_moneyvn']);
 $data= mysqli_real_escape_string($conn, $_POST['bill_i']);
 $typecc= mysqli_real_escape_string($conn, $_POST['typecc'] ?? 'IPD');
 $target_accountcode = isset($_POST['accountcode']) ? mysqli_real_escape_string($conn, $_POST['accountcode']) : '';

 $_SESSION['typecc'] = ($typecc === "OPD") ? "OPD" : "IPD";

 $is_cr_child = (strpos($target_accountcode, '1102050101.216') !== false || strpos($target_accountcode, '1102050101.217') !== false);
 $is_sss_child = (strpos($target_accountcode, '1102050101.309') !== false || strpos($target_accountcode, '1102050101.310') !== false);

 if ($is_cr_child) {
     $status = (!empty($data)) ? 'SETTLED' : 'WAIT';
     $sql1 = "UPDATE imr_tb_debtor_cr_breakdown 
              SET bill = '$data', settle_status = '$status'
              WHERE visit_type = '$typecc' AND vn = '$vn'";
 } elseif ($is_sss_child) {
     $status = (!empty($data)) ? 'SETTLED' : 'WAIT';
     $sql1 = "UPDATE imr_tb_debtor_sss_breakdown 
              SET bill = '$data', settle_status = '$status'
              WHERE visit_type = '$typecc' AND vn = '$vn'";
 } else {
     $sql1 = "UPDATE imr_tb_debtor_rights_ipd SET bill = '$data' WHERE an='$vn' ";
 }

  if (mysqli_query($conn, $sql1)) {
    echo 'true bill_i = '.$data;
  } else {
    echo "ไม่สามารถทำรายการได้ : " . mysqli_error($conn);
  }

}

if(isset($_POST['billdate_i'])){

 $vn= mysqli_real_escape_string($conn, $_POST['follow_moneyvn']);
 $data= mysqli_real_escape_string($conn, $_POST['billdate_i']);
 $typecc= mysqli_real_escape_string($conn, $_POST['typecc'] ?? 'IPD');
 $target_accountcode = isset($_POST['accountcode']) ? mysqli_real_escape_string($conn, $_POST['accountcode']) : '';

 $_SESSION['typecc'] = ($typecc === "OPD") ? "OPD" : "IPD";

 $is_cr_child = (strpos($target_accountcode, '1102050101.216') !== false || strpos($target_accountcode, '1102050101.217') !== false);
 $is_sss_child = (strpos($target_accountcode, '1102050101.309') !== false || strpos($target_accountcode, '1102050101.310') !== false);

 if ($is_cr_child) {
     $sql1 = "UPDATE imr_tb_debtor_cr_breakdown SET billdate = '$data' WHERE visit_type = '$typecc' AND vn = '$vn'";
 } elseif ($is_sss_child) {
     $sql1 = "UPDATE imr_tb_debtor_sss_breakdown SET billdate = '$data' WHERE visit_type = '$typecc' AND vn = '$vn'";
 } else {
     $sql1 = "UPDATE imr_tb_debtor_rights_ipd SET billdate = '$data' WHERE an='$vn' ";
 }

  if (mysqli_query($conn, $sql1)) {
    echo 'true billdate_i = '.$data;
  } else {
    echo "ไม่สามารถทำรายการได้ : " . mysqli_error($conn);
  }

}






if(isset($_POST['actionopdlistpp'])){

  $actionopdlists = mysqli_real_escape_string($conn, $_POST['actionopdlists']);
  $actionopdlist = mysqli_real_escape_string($conn, $_POST['actionopdlistpp']);
  $selectTypeOptpp = isset($_POST['selectTypeOptpp']) && $_POST['selectTypeOptpp'] !== '' ? mysqli_real_escape_string($conn, $_POST['selectTypeOptpp']) : '00';

  $partss = explode('-', $actionopdlists);
  $months = $partss[0];
  $years  = $partss[1];

  $parts = explode('-', $actionopdlist);
  $month = $parts[0];
  $year  = $parts[1];

  $date_start = sprintf('%s-%02d-01', $years, $months);
  $date_end_first = sprintf('%s-%02d-01', $year, $month);
  $last_day   = date('t', strtotime($date_end_first));
  $date_end   = sprintf('%s-%02d-%s', $year, $month, $last_day);

  // 1. ดึง VN List ตามประเภทบริการที่เลือก ($selectTypeOptpp)
  $vn_list = [];
  $sql_get_vn = '';
  $connection_for_vn = null;
  $next_day = date('Y-m-d', strtotime($date_end . ' +1 day'));

  if ($selectTypeOptpp == '01') {
      $sql_get_vn = "SELECT vn FROM dental_care WHERE entry_datetime >= '$date_start 00:00:00' AND entry_datetime < '$next_day 00:00:00'";
      $connection_for_vn = $conn2;
  } elseif ($selectTypeOptpp == '02') {
      $sql_get_vn = "SELECT vn FROM physic_main WHERE vstdate BETWEEN '$date_start' AND '$date_end'";
      $connection_for_vn = $conn2;
  } elseif ($selectTypeOptpp == '03') {
      $sql_get_vn = "SELECT vn FROM health_med_service WHERE service_date BETWEEN '$date_start' AND '$date_end'";
      $connection_for_vn = $conn2;
  }

  if (!empty($sql_get_vn) && $connection_for_vn) {
      $result_vn = mysqli_query($connection_for_vn, $sql_get_vn);
      if ($result_vn) {
          while ($row_vn = mysqli_fetch_assoc($result_vn)) {
              if (isset($row_vn['cid'])) $row_vn['cid'] = decrypt_data($row_vn['cid']);
              if (isset($row_vn['tel'])) $row_vn['tel'] = decrypt_data($row_vn['tel']);
              $vn_list[] = mysqli_real_escape_string($conn, $row_vn['vn']);
          }
      }
  }

  if ($selectTypeOptpp != '00' && empty($vn_list)) {
      echo '<tr><td colspan="9" style="text-align:center;">ไม่พบข้อมูลบริการตามประเภทที่เลือกในเดือนนี้</td></tr>';
      return;
  }

  $cr_config = get_active_cr_config($conn);
  $auto_split_opd = is_cr_auto_split_enabled($cr_config, 'OPD');
  $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
  $vn_filter_arg = ($selectTypeOptpp == '00') ? null : $vn_list;

  // 2. รวบรวมข้อมูลสรุปการโอนยอด CR สำหรับทุกเดือนในช่วงวันที่เลือก (กรองเฉพาะ VNs ที่เลือก)
  $all_cr_summaries = [];
  $accumulated_transfers = [];
  $inactive_cr_months = [];
  $total_cr_216_count = 0;
  $total_cr_216_debit = 0.0;
  $total_cr_216_comp = 0.0;

  $cur_time = strtotime($date_start);
  $end_time = strtotime($date_end);
  while ($cur_time <= $end_time) {
      $cur_m = intval(date('n', $cur_time));
      $cur_y = intval(date('Y', $cur_time));
      $m_txt = $cur_m . '-' . $cur_y;
      if (is_cr_effective_for_month($cr_config, $m_txt) && $auto_split_opd) {
          $s = get_cr_splitting_summary_for_month($conn, $conn2, $m_txt, 'OPD', $cr_config, $my_hospcode, $vn_filter_arg);
          if ($s) {
              $all_cr_summaries[$m_txt] = $s;
              $total_cr_216_count += $s['cr_non_kidney_count'];
              $total_cr_216_debit += $s['cr_non_kidney_received_total'];
              $total_cr_216_comp += cleanNum($s['cr_non_kidney_received_comp'] ?? 0);
              foreach ($s['transfers_by_account'] as $acc => $tr) {
                  if (!isset($accumulated_transfers[$acc])) {
                      $accumulated_transfers[$acc] = ['transfer_out' => 0.0, 'comp_out' => 0.0, 'full_transfer_cases' => 0];
                  }
                  $accumulated_transfers[$acc]['transfer_out'] += $tr['transfer_out'];
                  $accumulated_transfers[$acc]['comp_out'] += cleanNum($tr['comp_out'] ?? 0);
                  $accumulated_transfers[$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
              }
          }
      } else {
          $inactive_cr_months[] = $m_txt;
      }
      $cur_time = strtotime('+1 month', $cur_time);
  }

  // 🌟 รวมยอดลูกหนี้เดิมของผัง .216 จากเดือนที่ยังไม่ได้เปิดระบบ CR ในช่วงเวลาที่เลือก
  if (!empty($inactive_cr_months) && !empty($all_cr_summaries)) {
      $in_m_str = "'" . implode("','", array_map(function($m) use ($conn) { return mysqli_real_escape_string($conn, $m); }, $inactive_cr_months)) . "'";
      $q_inact = mysqli_query($conn, "SELECT 
          COUNT(o.vn) as c, 
          COALESCE(SUM(o.debit), 0) as d, 
          COALESCE(SUM(IFNULL(o.follow_money, 0) + IFNULL(stm.compensated, 0) + IFNULL(ckd_ofc.amount, 0) + IFNULL(ckd.compensated, 0)), 0) as comp
          FROM imr_tb_debtor_rights_opd o
          LEFT JOIN imr_tb_check_invoice stm ON stm.vn = o.vn
          LEFT JOIN imr_tb_seamless_dckd ckd ON ckd.vn = o.vn
          LEFT JOIN imr_tb_seamless_dckd_ofc ckd_ofc ON ckd_ofc.vn = o.vn
          WHERE o.accountcode = '1102050101.216' 
            AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') 
            AND o.monthtxt IN ($in_m_str)");
      if ($q_inact && $r_inact = mysqli_fetch_assoc($q_inact)) {
          $total_cr_216_count += intval($r_inact['c']);
          $total_cr_216_debit += floatval($r_inact['d']);
          $total_cr_216_comp += floatval($r_inact['comp']);
      }
  }

  // 2.1 รวบรวมข้อมูลสรุปการโอนยอด SSS Instrument สำหรับทุกเดือนในช่วงวันที่เลือก
  $sss_config = get_active_sss_config($conn);
  $auto_split_sss_opd = is_sss_auto_split_enabled($sss_config, 'OPD');
  $all_sss_summaries = [];
  $accumulated_sss_transfers = [];
  $inactive_sss_months = [];
  $total_sss_309_count = 0;
  $total_sss_309_debit = 0.0;
  $total_sss_309_comp = 0.0;

  $cur_time_sss = strtotime($date_start);
  while ($cur_time_sss <= $end_time) {
      $cur_m = intval(date('n', $cur_time_sss));
      $cur_y = intval(date('Y', $cur_time_sss));
      $m_txt = $cur_m . '-' . $cur_y;
      if (is_sss_effective_for_month($sss_config, $m_txt) && $auto_split_sss_opd) {
          $s_sss = get_sss_splitting_summary_for_month($conn, $conn2, $m_txt, 'OPD', $sss_config, $my_hospcode, $vn_filter_arg);
          if ($s_sss) {
              $all_sss_summaries[$m_txt] = $s_sss;
              $total_sss_309_count += $s_sss['sss_count'];
              $total_sss_309_debit += $s_sss['sss_received_total'];
              $total_sss_309_comp += cleanNum($s_sss['sss_received_comp'] ?? 0);
              foreach ($s_sss['transfers_by_account'] as $acc => $tr) {
                  if (!isset($accumulated_sss_transfers[$acc])) {
                      $accumulated_sss_transfers[$acc] = ['transfer_out' => 0.0, 'comp_out' => 0.0, 'full_transfer_cases' => 0];
                  }
                  $accumulated_sss_transfers[$acc]['transfer_out'] += $tr['transfer_out'];
                  $accumulated_sss_transfers[$acc]['comp_out'] += cleanNum($tr['comp_out'] ?? 0);
                  $accumulated_sss_transfers[$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
              }
          }
      } else {
          $inactive_sss_months[] = $m_txt;
      }
      $cur_time_sss = strtotime('+1 month', $cur_time_sss);
  }

  $vn_in_clause = '';
  $sql = "SELECT
            main.accountcode_key,
            main.accountname,
            main.accountcode,
            main.visitall, main.visitdiff, main.incomeall, main.incomediff, main.incometotall, main.follow_money,
            main.sort_type,
            main.max_group_visit
          FROM (
              SELECT
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN CONCAT(o.accountcode, '_KIDNEY')
                    ELSE o.accountcode
                END AS accountcode_key,
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง')
                    ELSE o.accountname
                END AS accountname,
                o.accountcode,
                COUNT(o.income) AS visitall,
                COUNT(CASE WHEN o.income <> 0 AND o.debit <> 0 THEN 1 END) AS visitdiff,
                SUM(o.income) AS incomeall,
                SUM(o.income) - SUM(COALESCE(o.original_debit, o.debit)) AS incomediff,
                SUM(o.debit) AS incometotall,
                SUM(IFNULL(o.follow_money, 0)) + SUM(IFNULL(stm.compensated, 0)) + SUM(IFNULL(ckd_ofc.amount, 0)) + SUM(IFNULL(ckd.compensated, 0)) AS follow_money,
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN 2
                    ELSE 1
                END AS sort_type,
                MAX(COUNT(o.income)) OVER(PARTITION BY o.accountcode) AS max_group_visit
            FROM imr_tb_debtor_rights_opd o
            LEFT JOIN imr_tb_check_invoice stm ON stm.vn = o.vn
            LEFT JOIN imr_tb_seamless_dckd ckd ON ckd.vn = o.vn
            LEFT JOIN imr_tb_seamless_dckd_ofc ckd_ofc ON ckd_ofc.vn = o.vn
            WHERE STR_TO_DATE(CONCAT('01-', o.monthtxt), '%d-%m-%Y') BETWEEN '$date_start' AND '$date_end' ";

  if ($selectTypeOptpp != '00') {
      $vn_in_clause = "'" . implode("','", $vn_list) . "'";
      $sql .= " AND o.vn IN ($vn_in_clause)";
  }

  $sql .= " GROUP BY
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN CONCAT(o.accountcode, '_KIDNEY')
                    ELSE o.accountcode
                END,
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง')
                    ELSE o.accountname
                END,
                o.accountcode
          ) AS main
          ORDER BY main.max_group_visit DESC, main.accountcode ASC, main.sort_type ASC";

  $result = mysqli_query($conn, $sql);

  if ($result && mysqli_num_rows($result) > 0) {

      $i=1;$visitall=0;$visitdiff=0;$incomeall=0;$incomediff=0;$incometotall=0;$follow_money=0;$totaldiffa=0;
      $rendered_216 = false;
      $rendered_309 = false;

      while ($row = mysqli_fetch_assoc($result)) {
          if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
          if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
          $accountcode = $row["accountcode"];
          $is_kidney = (strpos($row["accountcode_key"], '_KIDNEY') !== false);

          // 🌟 ปรับยอดลูกหนี้ตามระบบ CR Cross-Account Splitting (เฉพาะยอดที่กรองตามบริการ)
          if (!empty($all_cr_summaries) && !$is_kidney) {
              if ($accountcode === '1102050101.216') {
                  $rendered_216 = true;
                  $row["incometotall"] = cleanNum($total_cr_216_debit);
                  $row["incomeall"] = cleanNum($total_cr_216_debit);
                  $row["incomediff"] = 0.0;
                  $row["visitall"] = $total_cr_216_count;
                  $row["visitdiff"] = $total_cr_216_count;
                  if ($total_cr_216_comp > 0) {
                      $row["follow_money"] = $total_cr_216_comp;
                  }
              } elseif (isset($accumulated_transfers[$accountcode])) {
                  $tr = $accumulated_transfers[$accountcode];
                  $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr['transfer_out']);
                  $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr['transfer_out']);
                  $row["visitall"] = max(0, intval($row["visitall"]) - $tr['full_transfer_cases']);
                  $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr['full_transfer_cases']);
                  if (isset($tr['comp_out'])) {
                      $row["follow_money"] = max(0, cleanNum($row["follow_money"]) - $tr['comp_out']);
                  }
              }
          }

          // 🌟 ปรับยอดลูกหนี้ตามระบบ SSS Cross-Account Splitting
          if (!empty($all_sss_summaries) && !$is_kidney) {
              if ($accountcode === '1102050101.309') {
                  $rendered_309 = true;
                  $row["incometotall"] = cleanNum($row["incometotall"]) + cleanNum($total_sss_309_debit);
                  $row["incomeall"] = cleanNum($row["incomeall"]) + cleanNum($total_sss_309_debit);
                  $row["incomediff"] = 0.0;
                  $row["visitall"] = intval($row["visitall"]) + $total_sss_309_count;
                  $row["visitdiff"] = intval($row["visitdiff"]) + $total_sss_309_count;
                  if ($total_sss_309_comp > 0) {
                      $row["follow_money"] = cleanNum($row["follow_money"]) + cleanNum($total_sss_309_comp);
                  }
              } elseif (isset($accumulated_sss_transfers[$accountcode])) {
                  $tr_sss = $accumulated_sss_transfers[$accountcode];
                  $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr_sss['transfer_out']);
                  $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr_sss['transfer_out']);
                  $row["visitall"] = max(0, intval($row["visitall"]) - $tr_sss['full_transfer_cases']);
                  $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr_sss['full_transfer_cases']);
                  if (isset($tr_sss['comp_out'])) {
                      $row["follow_money"] = max(0, cleanNum($row["follow_money"]) - $tr_sss['comp_out']);
                  }
              }
          }

          // 🚨 กฎข้อ 36: รักษาสมดุลบัญชีผังลูกเป้าหมาย ให้ incomediff = 0.00 เสมอ
          if (in_array($accountcode, ['1102050101.216', '1102050101.309'])) {
              $row["incomediff"] = 0.0;
          }

          $sub_condition = $is_kidney
              ? "AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%')"
              : "AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%')";

          if (!empty($vn_in_clause)) {
              $sub_condition .= " AND vn IN ($vn_in_clause)";
          }

          $sqlzzxy = "SELECT
                      COUNT(accountcode) AS qqq,
                      COUNT(bill) AS www,
                      CASE
                          WHEN COUNT(accountcode) = COUNT(bill) THEN 'Y'
                          ELSE 'N'
                      END AS status_check
                  FROM imr_tb_debtor_rights_opd
                  WHERE accountcode='$accountcode'
                  AND STR_TO_DATE(CONCAT('01-', monthtxt), '%d-%m-%Y') BETWEEN '$date_start' AND '$date_end' $sub_condition";

          $resultzzxy = $conn->query($sqlzzxy);

          if ($resultzzxy) {
              $rowzzxy = $resultzzxy->fetch_assoc();
              
              if ($accountcode == '1102050101.201' || $accountcode == '1102050101.209') {
                  $totaldiff = 0;
              } else {
                  if ($rowzzxy['status_check'] == 'Y') {
                      if ($row["follow_money"] > 0) {
                          $totaldiff = cleanNum($row["incometotall"]) - cleanNum($row["follow_money"]);
                      } else {
                          $totaldiff = 0;
                      }
                  } else {
                      $totaldiff = cleanNum($row["incometotall"]) - cleanNum($row["follow_money"]);
                  }
              }
          } else {
              $totaldiff = cleanNum($row["incometotall"]) - cleanNum($row["follow_money"]);
          }

          $link_style = $is_kidney ? 'style="color: #cd641f; font-weight: bold;"' : '';

          echo '<tr>
          <td style="width: 5%;">&nbsp;<a href="#" onclick="viewopdlist(\''.$row["accountcode_key"].'\')">'.$row["accountcode"].'</a></td>
          <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" '.$link_style.' onclick="viewopdlist(\''.$row["accountcode_key"].'\')">'.$row["accountname"].'</a></td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$row["visitall"],0).'</td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$row["visitdiff"],0).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($row["incomeall"], 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($row["incomediff"], 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($row["incometotall"], 2).'</td>
          <td style="text-align: right;color: #00b215;">&nbsp;'.formatMoney($row["follow_money"], 2).'</td>
          <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($totaldiff, 2).'</td>
          </tr>';

          $visitall += cleanNum($row["visitall"]);
          $visitdiff += cleanNum($row["visitdiff"]);
          $incomeall += cleanNum($row["incomeall"]);
          $incomediff += cleanNum($row["incomediff"]);
          $incometotall += cleanNum($row["incometotall"]);
          $follow_money += cleanNum($row["follow_money"]);
          $totaldiffa += cleanNum($totaldiff);
      }

      // 🌟 กรณีเปิด CR แต่ในผลลัพธ์ของบริการนี้ยังไม่มีแถว .216 ปกติ ให้แทรกแถว .216 แสดงผลอัตโนมัติหากมีรายการ CR ในบริการนี้
      if (!$rendered_216 && !empty($all_cr_summaries) && $total_cr_216_count > 0) {
          $acc216 = '1102050101.216';
          $accname216 = 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
          $totaldiff_216 = $total_cr_216_debit;

          echo '<tr>
          <td style="width: 5%;">&nbsp;<a href="#" onclick="viewopdlist(\''.$acc216.'\')">'.$acc216.'</a></td>
          <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" onclick="viewopdlist(\''.$acc216.'\')">'.$accname216.'</a></td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$total_cr_216_count,0).'</td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$total_cr_216_count,0).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($total_cr_216_debit, 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney(0, 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($total_cr_216_debit, 2).'</td>
          <td style="text-align: right;color: #00b215;">&nbsp;'.formatMoney(0, 2).'</td>
          <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($totaldiff_216, 2).'</td>
          </tr>';

          $visitall += $total_cr_216_count;
          $visitdiff += $total_cr_216_count;
          $incomeall += $total_cr_216_debit;
          $incometotall += $total_cr_216_debit;
          $totaldiffa += $totaldiff_216;
      }

      // 🌟 กรณีเปิด SSS แต่ในผลลัพธ์ของบริการนี้ยังไม่มีแถว .309 ปกติ ให้แทรกแถว .309 แสดงผลอัตโนมัติหากมีรายการ SSS ในบริการนี้
      if (!$rendered_309 && !empty($all_sss_summaries) && $total_sss_309_count > 0) {
          $acc309 = '1102050101.309';
          $accname309 = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP';
          $totaldiff_309 = $total_sss_309_debit;

          echo '<tr>
          <td style="width: 5%;">&nbsp;<a href="#" onclick="viewopdlist(\''.$acc309.'\')">'.$acc309.'</a></td>
          <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" onclick="viewopdlist(\''.$acc309.'\')">'.$accname309.'</a></td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$total_sss_309_count,0).'</td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$total_sss_309_count,0).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($total_sss_309_debit, 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney(0, 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($total_sss_309_debit, 2).'</td>
          <td style="text-align: right;color: #00b215;">&nbsp;'.formatMoney(0, 2).'</td>
          <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($totaldiff_309, 2).'</td>
          </tr>';

          $visitall += $total_sss_309_count;
          $visitdiff += $total_sss_309_count;
          $incomeall += $total_sss_309_debit;
          $incometotall += $total_sss_309_debit;
          $totaldiffa += $totaldiff_309;
      }

      echo '<tr style="font-weight: bold;">
      <td style="width: 37.2%; height: 18px; text-align: center;" colspan="2">รวม</td>
      <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$visitall,0).'</td>
      <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$visitdiff,0).'</td>
      <td style="text-align: right;">&nbsp;'.formatMoney($incomeall, 2).'</td>
      <td style="text-align: right;">&nbsp;'.formatMoney($incomediff, 2).'</td>
      <td style="text-align: right;">&nbsp;'.formatMoney($incometotall, 2).'</td>
      <td style="text-align: right;color: #00b215; font-weight: bold;">&nbsp;'.formatMoney($follow_money, 2).'</td>
      <td style="text-align: right;color: #cd641f; font-weight: bold;">&nbsp;'.formatMoney($totaldiffa, 2).'</td>
      </tr>';

  } else {
      echo '<tr><td colspan="9" style="text-align:center;">ไม่พบข้อมูลลูกหนี้ที่ตรงกับเงื่อนไข</td></tr>';
  }
}



if(isset($_POST['actionipdlistpp'])){

  $actionipdlists = mysqli_real_escape_string($conn, $_POST['actionipdlists']);
  $actionipdlist = mysqli_real_escape_string($conn, $_POST['actionipdlistpp']);
  $selectTypeOptpp = isset($_POST['selectTypeOptpp']) && $_POST['selectTypeOptpp'] !== '' ? mysqli_real_escape_string($conn, $_POST['selectTypeOptpp']) : '00';

  $partss = explode('-', $actionipdlists);
  $months = $partss[0];
  $years  = $partss[1];

  $parts = explode('-', $actionipdlist);
  $month = $parts[0];
  $year  = $parts[1];

  $date_start = sprintf('%s-%02d-01', $years, $months);
  $date_end_first = sprintf('%s-%02d-01', $year, $month);
  $last_day   = date('t', strtotime($date_end_first));
  $date_end   = sprintf('%s-%02d-%s', $year, $month, $last_day);

  // 1. ดึง AN List ตามประเภทบริการที่เลือก ($selectTypeOptpp)
  $vn_list = [];
  $sql_get_vn = '';
  $connection_for_vn = null;
  $next_day = date('Y-m-d', strtotime($date_end . ' +1 day'));

  if ($selectTypeOptpp == '01') {
      $sql_get_vn = "SELECT o.an FROM dental_care d JOIN ovst o ON o.vn = d.vn WHERE d.entry_datetime >= '$date_start 00:00:00' AND d.entry_datetime < '$next_day 00:00:00' AND o.an IS NOT NULL AND o.an != ''";
      $connection_for_vn = $conn2;
  } elseif ($selectTypeOptpp == '02') {
      $sql_get_vn = "SELECT an FROM physic_main_ipd WHERE vstdate BETWEEN '$date_start' AND '$date_end' AND an IS NOT NULL AND an != ''";
      $connection_for_vn = $conn2;
  } elseif ($selectTypeOptpp == '03') {
      $sql_get_vn = "SELECT an FROM health_med_service WHERE service_date BETWEEN '$date_start' AND '$date_end' AND an IS NOT NULL AND an != ''";
      $connection_for_vn = $conn2;
  }

  if (!empty($sql_get_vn) && $connection_for_vn) {
      $result_vn = mysqli_query($connection_for_vn, $sql_get_vn);
      if ($result_vn) {
          while ($row_vn = mysqli_fetch_assoc($result_vn)) {
              if (isset($row_vn['cid'])) $row_vn['cid'] = decrypt_data($row_vn['cid']);
              if (isset($row_vn['tel'])) $row_vn['tel'] = decrypt_data($row_vn['tel']);
              $vn_list[] = mysqli_real_escape_string($conn, $row_vn['an']);
          }
      }
  }

  if ($selectTypeOptpp != '00' && empty($vn_list)) {
      echo '<tr><td colspan="9" style="text-align:center;">ไม่พบข้อมูลบริการตามประเภทที่เลือกในเดือนนี้</td></tr>';
      return;
  }

  $cr_config_ipd = get_active_cr_config($conn);
  $auto_split_ipd = is_cr_auto_split_enabled($cr_config_ipd, 'IPD');
  $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
  $an_filter_arg = ($selectTypeOptpp == '00') ? null : $vn_list;

  // 2. รวบรวมข้อมูลสรุปการโอนยอด CR สำหรับผู้ป่วยใน (IPD) (กรองเฉพาะ ANs ที่เลือก)
  $all_cr_summaries_ipd = [];
  $accumulated_transfers_ipd = [];
  $inactive_cr_months_ipd = [];
  $total_cr_217_count = 0;
  $total_cr_217_debit = 0.0;
  $total_cr_217_comp = 0.0;

  $cur_time = strtotime($date_start);
  $end_time = strtotime($date_end);
  while ($cur_time <= $end_time) {
      $cur_m = intval(date('n', $cur_time));
      $cur_y = intval(date('Y', $cur_time));
      $m_txt = $cur_m . '-' . $cur_y;
      if (is_cr_effective_for_month($cr_config_ipd, $m_txt) && $auto_split_ipd) {
          $s = get_cr_splitting_summary_for_month($conn, $conn2, $m_txt, 'IPD', $cr_config_ipd, $my_hospcode, $an_filter_arg);
          if ($s) {
              $all_cr_summaries_ipd[$m_txt] = $s;
              $total_cr_217_count += count($s['cr_visits']);
              $total_cr_217_debit += $s['cr_received_total'];
              $total_cr_217_comp += cleanNum($s['cr_received_comp'] ?? 0);
              foreach ($s['transfers_by_account'] as $acc => $tr) {
                  if (!isset($accumulated_transfers_ipd[$acc])) {
                      $accumulated_transfers_ipd[$acc] = ['transfer_out' => 0.0, 'comp_out' => 0.0, 'full_transfer_cases' => 0];
                  }
                  $accumulated_transfers_ipd[$acc]['transfer_out'] += $tr['transfer_out'];
                  $accumulated_transfers_ipd[$acc]['comp_out'] += cleanNum($tr['comp_out'] ?? 0);
                  $accumulated_transfers_ipd[$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
              }
          }
      } else {
          $inactive_cr_months_ipd[] = $m_txt;
      }
      $cur_time = strtotime('+1 month', $cur_time);
  }

  // 🌟 รวมยอดลูกหนี้เดิมของผัง .217 จากเดือนที่ยังไม่ได้เปิดระบบ CR ในช่วงเวลาที่เลือก
  if (!empty($inactive_cr_months_ipd) && !empty($all_cr_summaries_ipd)) {
      $in_m_str_i = "'" . implode("','", array_map(function($m) use ($conn) { return mysqli_real_escape_string($conn, $m); }, $inactive_cr_months_ipd)) . "'";
      $q_inact_i = mysqli_query($conn, "SELECT 
          COUNT(o.an) as c, 
          COALESCE(SUM(o.debit), 0) as d, 
          COALESCE(SUM(IFNULL(o.follow_money, 0) + IFNULL(stm.compensated, 0)), 0) as comp
          FROM imr_tb_debtor_rights_ipd o
          LEFT JOIN imr_tb_check_invoice stm ON stm.an = o.an
          WHERE o.accountcode = '1102050101.217' 
            AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%') 
            AND o.monthtxt IN ($in_m_str_i)");
      if ($q_inact_i && $r_inact_i = mysqli_fetch_assoc($q_inact_i)) {
          $total_cr_217_count += intval($r_inact_i['c']);
          $total_cr_217_debit += floatval($r_inact_i['d']);
          $total_cr_217_comp += floatval($r_inact_i['comp']);
      }
  }

  // 2.1 รวบรวมข้อมูลสรุปการโอนยอด SSS Instrument IPD สำหรับทุกเดือนในช่วงวันที่เลือก
  $sss_config_ipd = get_active_sss_config($conn);
  $auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config_ipd, 'IPD');
  $all_sss_summaries_ipd = [];
  $accumulated_sss_transfers_ipd = [];
  $inactive_sss_months_ipd = [];
  $total_sss_310_count = 0;
  $total_sss_310_debit = 0.0;
  $total_sss_310_comp = 0.0;

  $cur_time_sss_ipd = strtotime($date_start);
  while ($cur_time_sss_ipd <= $end_time) {
      $cur_m = intval(date('n', $cur_time_sss_ipd));
      $cur_y = intval(date('Y', $cur_time_sss_ipd));
      $m_txt = $cur_m . '-' . $cur_y;
      if (is_sss_effective_for_month($sss_config_ipd, $m_txt) && $auto_split_sss_ipd) {
          $s_sss_ipd = get_sss_splitting_summary_for_month($conn, $conn2, $m_txt, 'IPD', $sss_config_ipd, $my_hospcode, $an_filter_arg);
          if ($s_sss_ipd) {
              $all_sss_summaries_ipd[$m_txt] = $s_sss_ipd;
              $total_sss_310_count += count($s_sss_ipd['sss_visits']);
              $total_sss_310_debit += $s_sss_ipd['sss_received_total'];
              $total_sss_310_comp += cleanNum($s_sss_ipd['sss_received_comp'] ?? 0);
              foreach ($s_sss_ipd['transfers_by_account'] as $acc => $tr) {
                  if (!isset($accumulated_sss_transfers_ipd[$acc])) {
                      $accumulated_sss_transfers_ipd[$acc] = ['transfer_out' => 0.0, 'comp_out' => 0.0, 'full_transfer_cases' => 0];
                  }
                  $accumulated_sss_transfers_ipd[$acc]['transfer_out'] += $tr['transfer_out'];
                  $accumulated_sss_transfers_ipd[$acc]['comp_out'] += cleanNum($tr['comp_out'] ?? 0);
                  $accumulated_sss_transfers_ipd[$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
              }
          }
      } else {
          $inactive_sss_months_ipd[] = $m_txt;
      }
      $cur_time_sss_ipd = strtotime('+1 month', $cur_time_sss_ipd);
  }

  $vn_in_clause = '';
  $sql = "SELECT
            main.accountcode_key,
            main.accountname,
            main.accountcode,
            main.visitall, main.visitdiff, main.incomeall, main.incomediff, main.incometotall, main.follow_money,
            main.sort_type,
            main.max_group_visit
          FROM (
              SELECT
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN CONCAT(o.accountcode, '_KIDNEY')
                    ELSE o.accountcode
                END AS accountcode_key,
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง')
                    ELSE o.accountname
                END AS accountname,
                o.accountcode,
                COUNT(o.income) AS visitall,
                COUNT(CASE WHEN o.income <> 0 AND o.debit <> 0 THEN 1 END) AS visitdiff,
                SUM(o.income) AS incomeall,
                SUM(o.income) - SUM(COALESCE(o.original_debit, o.debit)) AS incomediff,
                SUM(o.debit) AS incometotall,
                SUM(IFNULL(o.follow_money, 0)) + SUM(IFNULL(stm.compensated, 0)) AS follow_money,
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN 2
                    ELSE 1
                END AS sort_type,
                MAX(COUNT(o.income)) OVER(PARTITION BY o.accountcode) AS max_group_visit
            FROM imr_tb_debtor_rights_ipd o
            LEFT JOIN imr_tb_check_invoice stm ON stm.vn = o.an
            WHERE STR_TO_DATE(CONCAT('01-', o.monthtxt), '%d-%m-%Y') BETWEEN '$date_start' AND '$date_end' ";

  if ($selectTypeOptpp != '00') {
      $vn_in_clause = "'" . implode("','", $vn_list) . "'";
      $sql .= " AND o.an IN ($vn_in_clause)";
  }

  $sql .= " GROUP BY
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN CONCAT(o.accountcode, '_KIDNEY')
                    ELSE o.accountcode
                END,
                CASE
                    WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', '- ผู้ป่วยไตวายเรื้อรัง')
                    ELSE o.accountname
                END,
                o.accountcode
          ) AS main
          ORDER BY main.max_group_visit DESC, main.accountcode ASC, main.sort_type ASC";

  $result = mysqli_query($conn, $sql);

  if ($result && mysqli_num_rows($result) > 0) {

      $i=1;$visitall=0;$visitdiff=0;$incomeall=0;$incomediff=0;$incometotall=0;$follow_money=0;$totaldiffa=0;
      $rendered_217 = false;
      $rendered_310 = false;

      while ($row = mysqli_fetch_assoc($result)) {
          if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
          if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
          $accountcode = $row["accountcode"];
          $is_kidney = (strpos($row["accountcode_key"], '_KIDNEY') !== false);

          // 🌟 ปรับยอดลูกหนี้ IPD ตามระบบ CR Cross-Account Splitting (เฉพาะยอดที่กรองตามบริการ)
          if (!empty($all_cr_summaries_ipd) && !$is_kidney) {
              if ($accountcode === '1102050101.217') {
                  $rendered_217 = true;
                  $row["incometotall"] = cleanNum($total_cr_217_debit);
                  $row["incomeall"] = cleanNum($total_cr_217_debit);
                  $row["incomediff"] = 0.0;
                  $row["visitall"] = $total_cr_217_count;
                  $row["visitdiff"] = $total_cr_217_count;
                  if ($total_cr_217_comp > 0) {
                      $row["follow_money"] = $total_cr_217_comp;
                  }
              } elseif (isset($accumulated_transfers_ipd[$accountcode])) {
                  $tr = $accumulated_transfers_ipd[$accountcode];
                  $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr['transfer_out']);
                  $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr['transfer_out']);
                  $row["visitall"] = max(0, intval($row["visitall"]) - $tr['full_transfer_cases']);
                  $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr['full_transfer_cases']);
                  if (isset($tr['comp_out'])) {
                      $row["follow_money"] = max(0, cleanNum($row["follow_money"]) - $tr['comp_out']);
                  }
              }
          }

          // 🌟 ปรับยอดลูกหนี้ IPD ตามระบบ SSS Cross-Account Splitting
          if (!empty($all_sss_summaries_ipd) && !$is_kidney) {
              if ($accountcode === '1102050101.310') {
                  $rendered_310 = true;
                  $row["incometotall"] = cleanNum($row["incometotall"]) + cleanNum($total_sss_310_debit);
                  $row["incomeall"] = cleanNum($row["incomeall"]) + cleanNum($total_sss_310_debit);
                  $row["incomediff"] = 0.0;
                  $row["visitall"] = intval($row["visitall"]) + $total_sss_310_count;
                  $row["visitdiff"] = intval($row["visitdiff"]) + $total_sss_310_count;
                  if ($total_sss_310_comp > 0) {
                      $row["follow_money"] = cleanNum($row["follow_money"]) + cleanNum($total_sss_310_comp);
                  }
              } elseif (isset($accumulated_sss_transfers_ipd[$accountcode])) {
                  $tr_sss_ipd = $accumulated_sss_transfers_ipd[$accountcode];
                  $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr_sss_ipd['transfer_out']);
                  $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr_sss_ipd['transfer_out']);
                  $row["visitall"] = max(0, intval($row["visitall"]) - $tr_sss_ipd['full_transfer_cases']);
                  $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr_sss_ipd['full_transfer_cases']);
                  if (isset($tr_sss_ipd['comp_out'])) {
                      $row["follow_money"] = max(0, cleanNum($row["follow_money"]) - $tr_sss_ipd['comp_out']);
                  }
              }
          }

          // 🚨 กฎข้อ 36: รักษาสมดุลบัญชีผังลูกเป้าหมาย ให้ incomediff = 0.00 เสมอ
          if (in_array($accountcode, ['1102050101.217', '1102050101.310'])) {
              $row["incomediff"] = 0.0;
          }

          if ($row["accountcode"] == '1102050101.201' || $row["accountcode"] == '1102050101.209') {
              $totaldiff = 0;
          } else {
              $totaldiff = cleanNum($row["incometotall"]) - cleanNum($row["follow_money"]);
          }

          $link_style = $is_kidney ? 'style="color: #cd641f; font-weight: bold;"' : '';

          echo '<tr>
          <td style="width: 5%;">&nbsp;<a href="#" onclick="viewipdlist(\''.$row["accountcode_key"].'\')">'.$row["accountcode"].'</a></td>
          <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" '.$link_style.' onclick="viewipdlist(\''.$row["accountcode_key"].'\')">'.$row["accountname"].'</a></td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$row["visitall"],0).'</td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$row["visitdiff"],0).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($row["incomeall"], 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($row["incomediff"], 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($row["incometotall"], 2).'</td>
          <td style="text-align: right;color: #00b215;">&nbsp;'.formatMoney($row["follow_money"], 2).'</td>
          <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($totaldiff, 2).'</td>
          </tr>';

          $visitall = $visitall + cleanNum($row["visitall"]);
          $visitdiff = $visitdiff + cleanNum($row["visitdiff"]);
          $incomeall = $incomeall + cleanNum($row["incomeall"]);
          $incomediff = $incomediff + cleanNum($row["incomediff"]);
          $incometotall = $incometotall + cleanNum($row["incometotall"]);
          $follow_money = $follow_money + cleanNum($row["follow_money"]);
          $totaldiffa = $totaldiffa + cleanNum($totaldiff);
      }

      // 🌟 กรณีเปิด CR แต่ในฐานข้อมูลยังไม่มีแถว .217 ปกติ ให้แทรกแถว .217 แสดงผลอัตโนมัติหากมีรายการ CR ในบริการนี้
      if (!$rendered_217 && !empty($all_cr_summaries_ipd) && $total_cr_217_count > 0) {
          $acc217 = '1102050101.217';
          $accname217 = 'ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ (CR)';
          $totaldiff_217 = $total_cr_217_debit;

          echo '<tr>
          <td style="width: 5%;">&nbsp;<a href="#" onclick="viewipdlist(\''.$acc217.'\')">'.$acc217.'</a></td>
          <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" onclick="viewipdlist(\''.$acc217.'\')">'.$accname217.'</a></td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$total_cr_217_count,0).'</td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$total_cr_217_count,0).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($total_cr_217_debit, 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney(0, 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($total_cr_217_debit, 2).'</td>
          <td style="text-align: right;color: #00b215;">&nbsp;'.formatMoney(0, 2).'</td>
          <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($totaldiff_217, 2).'</td>
          </tr>';

          $visitall += $total_cr_217_count;
          $visitdiff += $total_cr_217_count;
          $incomeall += $total_cr_217_debit;
          $incometotall += $total_cr_217_debit;
          $totaldiffa += $totaldiff_217;
      }

      // 🌟 กรณีเปิด SSS IPD แต่ในฐานข้อมูลยังไม่มีแถว .310 ปกติ ให้แทรกแถว .310 แสดงผลอัตโนมัติหากมีรายการ SSS IPD ในบริการนี้
      if (!$rendered_310 && !empty($all_sss_summaries_ipd) && $total_sss_310_count > 0) {
          $acc310 = '1102050101.310';
          $accname310 = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง IP';
          $totaldiff_310 = $total_sss_310_debit;

          echo '<tr>
          <td style="width: 5%;">&nbsp;<a href="#" onclick="viewipdlist(\''.$acc310.'\')">'.$acc310.'</a></td>
          <td style="width: 32.2%;text-align: left;">&nbsp;<a href="#" onclick="viewipdlist(\''.$acc310.'\')">'.$accname310.'</a></td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$total_sss_310_count,0).'</td>
          <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$total_sss_310_count,0).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($total_sss_310_debit, 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney(0, 2).'</td>
          <td style="text-align: right;">&nbsp;'.formatMoney($total_sss_310_debit, 2).'</td>
          <td style="text-align: right;color: #00b215;">&nbsp;'.formatMoney(0, 2).'</td>
          <td style="text-align: right;color: #cd641f;">&nbsp;'.formatMoney($totaldiff_310, 2).'</td>
          </tr>';

          $visitall += $total_sss_310_count;
          $visitdiff += $total_sss_310_count;
          $incomeall += $total_sss_310_debit;
          $incometotall += $total_sss_310_debit;
          $totaldiffa += $totaldiff_310;
      }

      echo '<tr style="font-weight: bold;">
        <td style="width: 37.2%; height: 18px; text-align: center;" colspan="2">รวม</td>
        <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$visitall,0).'</td>
        <td style="text-align: right;width: 10%;">&nbsp;'.number_format((float)$visitdiff,0).'</td>
        <td style="text-align: right;">&nbsp;'.formatMoney($incomeall, 2).'</td>
        <td style="text-align: right;">&nbsp;'.formatMoney($incomediff, 2).'</td>
        <td style="text-align: right;">&nbsp;'.formatMoney($incometotall, 2).'</td>
        <td style="text-align: right;color: #00b215; font-weight: bold;">&nbsp;'.formatMoney($follow_money, 2).'</td>
        <td style="text-align: right;color: #cd641f; font-weight: bold;">&nbsp;'.formatMoney($totaldiffa, 2).'</td>
      </tr>';

  } else {
      echo '<tr><td colspan="9" style="text-align:center;">ไม่พบข้อมูลลูกหนี้ที่ตรงกับเงื่อนไข</td></tr>';
  }
}






// =========================================================================
// [แก้ไขสมบูรณ์ 100%] ฝั่งผู้ป่วยนอก OPD รายตัวแยกแผนก (actionopdlistonlypp)
// รองรับระบบจำแนกกลุ่มย่อย CR, Badges, และการตัดโอนยอดข้ามผัง
// =========================================================================
if(isset($_POST['actionopdlistonlypp'])){

    $actionopdlistonlys = mysqli_real_escape_string($conn, $_POST['actionopdlistonlys']);
    $actionopdlistonly = mysqli_real_escape_string($conn, $_POST['actionopdlistonlypp']);
    $id = mysqli_real_escape_string($conn, $_POST['oid']);
    $selectTypeOptpp = isset($_POST['selectTypeOptpp']) && $_POST['selectTypeOptpp'] !== '' ? mysqli_real_escape_string($conn, $_POST['selectTypeOptpp']) : '00';

    $_SESSION['monthtxts'] = $actionopdlistonlys;
    $_SESSION['monthtxt'] = $actionopdlistonly;
    $_SESSION['oid'] = $id;

    $partss = explode('-', $actionopdlistonlys);
    $months = $partss[0];
    $years  = $partss[1];

    $parts = explode('-', $actionopdlistonly);
    $month = $parts[0];
    $year  = $parts[1];

    $date_start = sprintf('%s-%02d-01', $years, $months);
    $date_end_first = sprintf('%s-%02d-01', $year, $month);
    $last_day   = date('t', strtotime($date_end_first));
    $date_end   = sprintf('%s-%02d-%s', $year, $month, $last_day);

    $next_day = date('Y-m-d', strtotime($date_end . ' +1 day'));

    // 1. ดึง VN List ตามเงื่อนไข $selectTypeOptpp
    $vn_list = [];
    $sql_get_vn = '';
    $connection_for_vn = null;

    if ($selectTypeOptpp == '01') {
        $sql_get_vn = "SELECT vn FROM dental_care WHERE entry_datetime >= '$date_start 00:00:00' AND entry_datetime < '$next_day 00:00:00'";
        $connection_for_vn = $conn2;
    } elseif ($selectTypeOptpp == '02') {
        $sql_get_vn = "SELECT vn FROM physic_main WHERE vstdate BETWEEN '$date_start' AND '$date_end'";
        $connection_for_vn = $conn2;
    } elseif ($selectTypeOptpp == '03') {
        $sql_get_vn = "SELECT vn FROM health_med_service WHERE service_date BETWEEN '$date_start' AND '$date_end'";
        $connection_for_vn = $conn2;
    }

    if (!empty($sql_get_vn) && $connection_for_vn) {
        $result_vn = mysqli_query($connection_for_vn, $sql_get_vn);
        if ($result_vn) {
            while ($row_vn = mysqli_fetch_assoc($result_vn)) {
                if (isset($row_vn['cid'])) $row_vn['cid'] = decrypt_data($row_vn['cid']);
                if (isset($row_vn['tel'])) $row_vn['tel'] = decrypt_data($row_vn['tel']);
                $vn_list[] = mysqli_real_escape_string($conn, $row_vn['vn']);
            }
        }
    }

    if ($selectTypeOptpp != '00' && empty($vn_list)) {
        echo '<tr><td colspan="15" style="text-align:center;">ไม่พบข้อมูลบริการตามประเภทที่เลือกในเดือนนี้</td></tr>';
        return;
    }

    $cr_config = get_active_cr_config($conn);
    $auto_split_opd = is_cr_auto_split_enabled($cr_config, 'OPD');
    $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
    $vn_filter_arg = ($selectTypeOptpp == '00') ? null : $vn_list;

    // 2. รวบรวมข้อมูลสรุปการโอนยอด CR และ Visits (กรองตาม $vn_filter_arg)
    $all_cr_summaries = [];
    $all_cr_visits = [];
    $all_queried_months_opd = [];

    $cur_time = strtotime($date_start);
    $end_time = strtotime($date_end);
    while ($cur_time <= $end_time) {
        $cur_m = intval(date('n', $cur_time));
        $cur_y = intval(date('Y', $cur_time));
        $m_txt = $cur_m . '-' . $cur_y;
        $all_queried_months_opd[] = $m_txt;
        if (is_cr_effective_for_month($cr_config, $m_txt) && $auto_split_opd) {
            $s = get_cr_splitting_summary_for_month($conn, $conn2, $m_txt, 'OPD', $cr_config, $my_hospcode, $vn_filter_arg);
            if ($s && !empty($s['cr_visits'])) {
                $all_cr_summaries[$m_txt] = $s;
                foreach ($s['cr_visits'] as $vn => $vinfo) {
                    $all_cr_visits[$vn] = $vinfo;
                }
            }
        }
        $cur_time = strtotime('+1 month', $cur_time);
    }

    // 2.1 รวบรวมข้อมูลสรุปการโอนยอด SSS Instrument สำหรับ OPD (กรองตาม $vn_filter_arg)
    $sss_config = get_active_sss_config($conn);
    $auto_split_sss_opd = is_sss_auto_split_enabled($sss_config, 'OPD');
    $all_sss_summaries = [];
    $all_sss_visits = [];

    $cur_time_sss = strtotime($date_start);
    while ($cur_time_sss <= $end_time) {
        $cur_m = intval(date('n', $cur_time_sss));
        $cur_y = intval(date('Y', $cur_time_sss));
        $m_txt = $cur_m . '-' . $cur_y;
        if (is_sss_effective_for_month($sss_config, $m_txt) && $auto_split_sss_opd) {
            $s_sss = get_sss_splitting_summary_for_month($conn, $conn2, $m_txt, 'OPD', $sss_config, $my_hospcode, $vn_filter_arg);
            if ($s_sss && !empty($s_sss['sss_visits'])) {
                $all_sss_summaries[$m_txt] = $s_sss;
                foreach ($s_sss['sss_visits'] as $vn => $vinfo) {
                    $all_sss_visits[$vn] = $vinfo;
                }
            }
        }
        $cur_time_sss = strtotime('+1 month', $cur_time_sss);
    }

    // 3. สร้าง SQL Query หลัก
    $vn_filter_sql = '';
    if ($selectTypeOptpp != '00' && !empty($vn_list)) {
        $vn_in_clause = "'" . implode("','", $vn_list) . "'";
        $vn_filter_sql = " AND o.vn IN ($vn_in_clause) ";
    }
    $should_run_query = true;

    $is_kidney_click = (strpos($id, '_KIDNEY') !== false);
    $is_target_216 = ($id === '1102050101.216');
    $is_target_309 = ($id === '1102050101.309');
    $is_sss_parent_opd = in_array($id, ['1102050101.301', '1102050101.303', '1102050101.307', '1102050101.308']);

    if ($is_kidney_click) {
        $base_account = str_replace('_KIDNEY', '', $id);
        $kidney_condition = "AND o.accountcode = '$base_account' AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%')";
    } else {
        if ($is_target_216 && !empty($all_cr_summaries)) {
            $transfer_vns = array_keys($all_cr_visits);
            $transfer_vns_quoted = !empty($transfer_vns) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_vns)) . "'" : "''";
            $kidney_condition = "AND ((o.accountcode = '1102050101.216') OR (o.vn IN ($transfer_vns_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        } elseif ($is_target_309 && !empty($all_sss_summaries)) {
            $transfer_sss_vns = array_keys($all_sss_visits);
            $transfer_sss_vns_quoted = !empty($transfer_sss_vns) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_sss_vns)) . "'" : "''";
            $kidney_condition = "AND ((o.accountcode = '1102050101.309') OR (o.vn IN ($transfer_sss_vns_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        } else {
            $kidney_condition = "AND o.accountcode = '$id' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        }
    }

    // ดึงข้อมูลชื่อผังบัญชี / สิทธิการเงิน สำหรับแสดงที่หัวตาราง Modal (เงินชดเชย OPD)
    $title_acc_name = '';
    $title_acc_code = $id;

    if ($is_target_216 || strpos($id, '1102050101.216') !== false) {
        $title_acc_code = '1102050101.216';
        $title_acc_name = 'ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ (CR)';
    } elseif ($is_target_309 || strpos($id, '1102050101.309') !== false) {
        $title_acc_code = '1102050101.309';
        $title_acc_name = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP';
    } else {
        $base_code = strpos($id, '_') !== false ? explode('_', $id)[0] : $id;
        $account_info_q = mysqli_query($conn, "SELECT accountcode, accountname FROM imr_tb_debtor_rights_opd WHERE accountcode = '$base_code' LIMIT 1");
        if ($account_info_q && $acc_row = mysqli_fetch_assoc($account_info_q)) {
            $title_acc_name = $acc_row['accountname'];
            $title_acc_code = $acc_row['accountcode'];
        }
    }

    if (strpos($id, '_KIDNEY') !== false) {
        $title_acc_name = str_replace('ลูกหนี้ค่ารักษา', 'ผู้ป่วยไตวายเรื้อรัง', $title_acc_name);
    } elseif (strpos($id, '_HOSP_11504') !== false) {
        $title_acc_name = str_replace('ลูกหนี้ค่ารักษา', '- ลูกหนี้ รพ.ค่าย เบิก นค.', $title_acc_name);
    } elseif (strpos($id, '_HOSP_14429') !== false) {
        $title_acc_name = str_replace('ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 1 วัดเหนือ เบิก นค.', $title_acc_name);
    } elseif (strpos($id, '_HOSP_23576') !== false) {
        $title_acc_name = str_replace('ลูกหนี้ค่ารักษา', '- ลูกหนี้ เทศบาล 2 สระสิม เบิก นค.', $title_acc_name);
    }

    $title_suffix = '';
    if (!empty($title_acc_name)) {
        $title_suffix = " ($title_acc_name รหัส $title_acc_code)";
    } elseif (!empty($title_acc_code)) {
        $title_suffix = " (รหัส $title_acc_code)";
    }

    $modal_full_title = '📑 รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน' . $title_suffix;

    $sql = "SELECT 
                o.vn, o.hn, o.cid, o.ptname, o.vstdate, 
                (o.income) as income, 
                (o.income - COALESCE(o.original_debit, o.debit)) as incomediff, 
                (o.debit) as debit, 
                o.follow_money, o.bill, o.billdate, o.mobile,
                o.pttype_eclaim_name, o.accountname, o.accountcode, o.pttypename,
                SUM(IFNULL(stm.compensated, 0)) as compensated, 
                MAX(stm.rep) as rep,
                SUM(IFNULL(dckd.compensated, 0)) as compensatedckd, 
                MAX(dckd.rep) as repckd,
                SUM(IFNULL(ckd_ofc.amount, 0)) as compensatedckd_ofc, 
                MAX(ckd_ofc.stm_doc) as stm_doc
            FROM imr_tb_debtor_rights_opd o
            LEFT JOIN imr_tb_check_invoice stm on stm.vn=o.vn
            LEFT JOIN imr_tb_seamless_dckd dckd on dckd.vn=o.vn
            LEFT JOIN imr_tb_seamless_dckd_ofc ckd_ofc on ckd_ofc.vn=o.vn
            WHERE STR_TO_DATE(CONCAT('01-', o.monthtxt), '%d-%m-%Y') BETWEEN '$date_start' AND '$date_end'
            $kidney_condition $vn_filter_sql
            GROUP BY o.vn, o.hn, o.cid, o.ptname, o.vstdate, o.income, o.original_debit, o.debit, o.follow_money, o.bill, o.billdate, o.mobile, o.pttype_eclaim_name, o.accountname, o.accountcode, o.pttypename";

    if ($should_run_query) {
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $raw_rows = [];
            $vns_to_detect = [];

            while ($r = mysqli_fetch_assoc($result)) {
                if (isset($r['cid'])) $r['cid'] = decrypt_data($r['cid']);
                if (isset($r['tel'])) $r['tel'] = decrypt_data($r['tel']);
                $raw_rows[] = $r;
                $vns_to_detect[] = $r['vn'];
            }

            $pp_m_opd = !empty($all_queried_months_opd) ? $all_queried_months_opd : ((!empty($date_start)) ? date('n-Y', strtotime($date_start)) : null);

            // จำแนกประเภทกลุ่มย่อย CR
            $cr_map = [];
            if (!empty($vns_to_detect) && $conn2) {
                $cr_map = detect_cr_types_for_visit_list($conn2, $vns_to_detect, 'OPD', $cr_config, $my_hospcode, $pp_m_opd);
            }

            // จำแนกประเภทกลุ่มย่อย SSS
            $sss_map = [];
            if (!empty($vns_to_detect) && $conn2) {
                $sss_map = detect_sss_types_for_visit_list($conn2, $vns_to_detect, 'OPD', $sss_config, $my_hospcode, $pp_m_opd);
            }

            // เตรียมโครงสร้าง Subgroup Stats สำหรับ Dynamic Tabs (รองรับ CR และ SSS)
            $base_id = explode('_', $id)[0];
            $is_cr_effective = !empty($all_cr_summaries);
            $is_sss_effective = !empty($all_sss_summaries);
            $is_cr_acc_opd = $is_cr_effective && $auto_split_opd && !$is_kidney_click && (
                $is_target_216 ||
                (in_array($base_id, ['1102050101.201', '1102050101.209', '1102050101.203']) && is_cr_parent_account_enabled($cr_config, $base_id))
            );
            $is_sss_acc_opd = $is_sss_effective && $auto_split_sss_opd && !$is_kidney_click && (
                $is_target_309 ||
                (in_array($base_id, ['1102050101.301', '1102050101.303', '1102050101.307', '1102050101.308']) && is_sss_parent_account_enabled($sss_config, $base_id))
            );

            $subgroup_stats = [];

            if ($is_sss_acc_opd) {
                $subgroup_stats['ALL'] = [
                    'id' => 'ALL',
                    'title' => 'ทั้งหมด (All SSS)',
                    'short_name' => 'ทั้งหมด',
                    'count' => 0,
                    'debit' => 0.0
                ];
                $sg_source_opd = isset($sss_config['subgroups_opd']) ? $sss_config['subgroups_opd'] : [];
                if (!empty($sg_source_opd)) {
                    foreach ($sg_source_opd as $sg_id => $sg) {
                        if (!is_sss_subgroup_enabled($sss_config, $sg_id, 'OPD', $pp_m_opd)) continue;
                        $subgroup_stats[$sg_id] = [
                            'id' => $sg_id,
                            'title' => $sg['title'] ?? $sg_id,
                            'short_name' => $sg['short_name'] ?? $sg_id,
                            'count' => 0,
                            'debit' => 0.0
                        ];
                    }
                }
            } elseif ($is_cr_acc_opd) {
                $subgroup_stats['ALL'] = [
                    'id' => 'ALL',
                    'title' => 'ทั้งหมด (All CR)',
                    'short_name' => 'ทั้งหมด',
                    'count' => 0,
                    'debit' => 0.0
                ];
                $sg_source_opd = isset($cr_config['subgroups_opd']) ? $cr_config['subgroups_opd'] : (isset($cr_config['subgroups']) ? $cr_config['subgroups'] : []);
                if (!empty($sg_source_opd)) {
                    foreach ($sg_source_opd as $sg_id => $sg) {
                        if (!is_cr_subgroup_enabled($cr_config, $sg_id, 'OPD', $pp_m_opd)) continue;
                        $subgroup_stats[$sg_id] = [
                            'id' => $sg_id,
                            'title' => $sg['title'] ?? $sg_id,
                            'short_name' => $sg['short_name'] ?? $sg_id,
                            'count' => 0,
                            'debit' => 0.0
                        ];
                    }
                }
            }

            $cr_bd_map = [];
            $sss_bd_map = [];
            if (!empty($vns_to_detect)) {
                $safe_det_vns = "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $vns_to_detect)) . "'";
                $q_cr_bd = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_cr_breakdown WHERE visit_type = 'OPD' AND vn IN ($safe_det_vns)");
                if ($q_cr_bd) {
                    while ($bd = mysqli_fetch_assoc($q_cr_bd)) {
                        $cr_bd_map[$bd['vn']] = $bd;
                    }
                }
                $q_sss_bd = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_sss_breakdown WHERE visit_type = 'OPD' AND vn IN ($safe_det_vns)");
                if ($q_sss_bd) {
                    while ($bd = mysqli_fetch_assoc($q_sss_bd)) {
                        $sss_bd_map[$bd['vn']] = $bd;
                    }
                }
            }

            $all_display_rows = [];

            foreach ($raw_rows as $row) {
                $vn = $row['vn'];
                $row['income_original'] = floatval(cleanNum($row['income']));
                $row['incomediff_original'] = floatval(cleanNum($row['incomediff']));
                $is_transferred_visit = isset($all_cr_visits[$vn]);
                $is_sss_transferred_visit = isset($all_sss_visits[$vn]);

                if ($is_target_216 && !empty($all_cr_summaries)) {
                    if ($is_transferred_visit) {
                        $vinfo = $all_cr_visits[$vn];
                        if (!empty($vinfo['is_kidney'])) continue;
                        $row['debit'] = $vinfo['cr_amount'];
                        $row['income'] = $vinfo['cr_amount'];
                        $row['incomediff'] = 0.0;
                        $row['cr_origin_acc'] = $vinfo['origin_accountcode'] ?? ($vinfo['origin_account'] ?? '1102050101.201');
                        $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                        $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];

                        if (isset($cr_bd_map[$vn])) {
                            $row['follow_money'] = cleanNum($cr_bd_map[$vn]['compensated']);
                            $row['compensated'] = cleanNum($cr_bd_map[$vn]['compensated']);
                            $row['bill'] = $cr_bd_map[$vn]['bill'];
                            $row['billdate'] = $cr_bd_map[$vn]['billdate'];
                        } else {
                            $row['follow_money'] = min(cleanNum($row['follow_money']), floatval($vinfo['cr_amount']));
                        }
                    } else {
                        $row['cr_origin_acc'] = '1102050101.216';
                        $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                        $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];
                        $v_month = $row['monthtxt'] ?? '';
                        if (isset($all_cr_summaries[$v_month]) && isset($cr_bd_map[$vn])) {
                            $row['follow_money'] = cleanNum($cr_bd_map[$vn]['compensated']);
                            $row['compensated'] = cleanNum($cr_bd_map[$vn]['compensated']);
                            $row['bill'] = $cr_bd_map[$vn]['bill'];
                            $row['billdate'] = $cr_bd_map[$vn]['billdate'];
                        }
                    }
                    $all_display_rows[] = $row;

                } elseif ($is_target_309 && !empty($all_sss_summaries)) {
                    if ($is_sss_transferred_visit) {
                        $vinfo = $all_sss_visits[$vn];
                        $row['debit'] = $vinfo['sss_amount'];
                        $row['income'] = $vinfo['sss_amount'];
                        $row['incomediff'] = 0.0;
                        $row['sss_origin_acc'] = $vinfo['origin_accountcode'] ?? '1102050101.301';
                        $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];

                        if (isset($sss_bd_map[$vn])) {
                            $row['follow_money'] = cleanNum($sss_bd_map[$vn]['compensated']);
                            $row['compensated'] = cleanNum($sss_bd_map[$vn]['compensated']);
                            $row['bill'] = $sss_bd_map[$vn]['bill'];
                            $row['billdate'] = $sss_bd_map[$vn]['billdate'];
                        } else {
                            $row['follow_money'] = min(cleanNum($row['follow_money']), floatval($vinfo['sss_amount']));
                        }
                    } else {
                        $row['sss_origin_acc'] = '1102050101.309';
                        $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];
                        $v_month = $row['monthtxt'] ?? '';
                        if (isset($all_sss_summaries[$v_month]) && isset($sss_bd_map[$vn])) {
                            $row['follow_money'] = cleanNum($sss_bd_map[$vn]['compensated']);
                            $row['compensated'] = cleanNum($sss_bd_map[$vn]['compensated']);
                            $row['bill'] = $sss_bd_map[$vn]['bill'];
                            $row['billdate'] = $sss_bd_map[$vn]['billdate'];
                        }
                    }
                    $all_display_rows[] = $row;

                } elseif (!empty($all_cr_summaries) && !$is_kidney_click && in_array($id, ['1102050101.201', '1102050101.209', '1102050101.203'])) {
                    if ($is_transferred_visit) {
                        $vinfo = $all_cr_visits[$vn];
                        if ($vinfo['is_full_transfer']) {
                            continue;
                        } else {
                            $row['debit'] = $vinfo['general_remain_amount'];
                            $row['cr_transferred_out'] = $vinfo['cr_amount'];
                            $row['cr_types'] = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : [];
                            $row['items'] = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : [];

                            // หักเงินชดเชยที่โอนไปผังลูกออก
                            $cr_comp = isset($cr_bd_map[$vn]) ? cleanNum($cr_bd_map[$vn]['compensated']) : min(cleanNum($row['follow_money']), floatval($vinfo['cr_amount']));
                            $mother_comp = max(0, cleanNum($row['follow_money']) - $cr_comp);
                            $row['follow_money'] = $mother_comp;
                            $row['compensated'] = 0;

                            // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                            if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                                $row['bill'] = '';
                                $row['billdate'] = '';
                            }
                            $all_display_rows[] = $row;
                        }
                    } else {
                        $all_display_rows[] = $row;
                    }
                } elseif (!empty($all_sss_summaries) && !$is_kidney_click && $is_sss_parent_opd) {
                    if ($is_sss_transferred_visit) {
                        $vinfo = $all_sss_visits[$vn];
                        if ($vinfo['is_full_transfer']) {
                            continue;
                        } else {
                            $row['debit'] = $vinfo['general_remain_amount'];
                            $row['sss_transferred_out'] = $vinfo['sss_amount'];
                            $row['sss_types'] = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : ['SSS-Instrument'];
                            $row['sss_items'] = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : [];

                            // หักเงินชดเชยที่โอนไปผังลูกออก
                            $sss_comp = isset($sss_bd_map[$vn]) ? cleanNum($sss_bd_map[$vn]['compensated']) : min(cleanNum($row['follow_money']), floatval($vinfo['sss_amount']));
                            $mother_comp = max(0, cleanNum($row['follow_money']) - $sss_comp);
                            $row['follow_money'] = $mother_comp;
                            $row['compensated'] = 0;

                            // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                            if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                                $row['bill'] = '';
                                $row['billdate'] = '';
                            }
                            $all_display_rows[] = $row;
                        }
                    } else {
                        $all_display_rows[] = $row;
                    }
                } else {
                    $all_display_rows[] = $row;
                }
            }

            // แสดงผลรายการ
            $i=0;$income=0;$incomediff=0;$debit=0;$total_comp=0;

            foreach ($all_display_rows as $row) {
                $i++;
                $vn = $row['vn'];
                $is_row_kidney = ($is_kidney_click || strpos($row["pttypename"], 'ฟอกไต') !== false || strpos($row["pttypename"], 'ไต') !== false);
                $row_color = $is_row_kidney ? 'style="color: #cd641f;"' : '';

                $comp_base = !empty($row["follow_money"]) ? cleanNum($row["follow_money"]) : cleanNum($row["compensated"]);
                $comp = $comp_base + cleanNum($row["compensatedckd"]) + cleanNum($row["compensatedckd_ofc"]);

                // จัดการข้อมูลเลขที่และวันที่ใบเสร็จ
                $bill_no = '';
                $bill_date = '';
                if (!empty($row["bill"])) {
                    $bill_no = $row["bill"];
                    $bill_date = $row["billdate"] ?? '';
                } elseif (!empty($row["mobile"])) {
                    if (strpos($row["mobile"], '-') !== false) {
                        list($b1, $bdate1) = explode('-', $row["mobile"], 2);
                        $bill_no = $b1;
                        $bill_date = $bdate1;
                    } else {
                        $bill_no = $row["mobile"];
                    }
                }

                // จัดการ Rep.
                $rep = '';
                if (!empty($row["rep"])) {
                    $rep = $row["rep"];
                } elseif (!empty($row["repckd"])) {
                    $rep = $row["repckd"];
                } elseif (!empty($row["stm_doc"])) {
                    $rep = $row["stm_doc"];
                }

                // จัดการ CR / SSS Badges & Subgroups
                $cr_types = isset($cr_map[$vn]['cr_types']) ? $cr_map[$vn]['cr_types'] : (isset($row['cr_types']) ? $row['cr_types'] : []);
                $cr_items = isset($cr_map[$vn]['items']) ? $cr_map[$vn]['items'] : (isset($row['items']) ? $row['items'] : []);
                $sss_types = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
                $sss_items = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

                $all_types = array_unique(array_merge($cr_types, $sss_types));
                $cr_type_str = implode(',', $all_types);
                $cr_badge_html = '';

                if ($is_cr_acc_opd || $is_sss_acc_opd) {
                    $subgroup_stats['ALL']['count']++;
                    $subgroup_stats['ALL']['debit'] += cleanNum($row['debit']);
                    foreach ($all_types as $at) {
                        if (isset($subgroup_stats[$at])) {
                            $subgroup_stats[$at]['count']++;
                            $subgroup_stats[$at]['debit'] += cleanNum($row['debit']);
                        }
                    }
                }

                if ($is_cr_acc_opd && (!empty($cr_types) || !empty($row['cr_origin_acc']) || !empty($row['cr_transferred_out']))) {
                    $cr_payload_data = [
                        'vn' => (string)$row['vn'],
                        'hn' => (string)($row['hn'] ?? ''),
                        'ptname' => (string)($row['ptname'] ?? ''),
                        'vstdate' => (string)($row['vstdate'] ?? ''),
                        'pttypename' => (string)($row['pttypename'] ?? ''),
                        'hospmain' => '',
                        'income' => floatval(cleanNum($row['income_original'] ?? ($row['income'] ?? $row['debit']))),
                        'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                        'debit' => floatval(cleanNum($row['debit'])),
                        'origin_acc' => (string)($row['cr_origin_acc'] ?? $row['accountcode']),
                        'target_acc' => '1102050101.216',
                        'cr_types' => $cr_types,
                        'cr_amount' => floatval(cleanNum($row['debit'])),
                        'remain_debit' => 0.0,
                        'items' => $cr_items
                    ];
                    $payload_json_str = htmlspecialchars(json_encode($cr_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                    foreach ($cr_types as $ct) {
                        $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 cr-item-badge-click" style="font-size:11px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this)" title="คลิกดูรายการที่โอนมา / รายการ CR">' . htmlspecialchars($ct) . '</span>';
                    }

                    if (!empty($row['cr_origin_acc']) && $row['cr_origin_acc'] !== '1102050101.216') {
                        $orig_short = str_replace('1102050101.', '.', $row['cr_origin_acc']);
                        $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 cr-item-badge-click" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this)" title="โอนมาจากผัง ' . htmlspecialchars($row['cr_origin_acc']) . '"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                    } elseif (!empty($row['cr_transferred_out']) && $row['cr_transferred_out'] > 0) {
                        $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 cr-item-badge-click" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this)" title="ตัดยอด CR ออกไปผัง .216 จำนวน ฿' . number_format($row['cr_transferred_out'], 2) . '"><i class="bx bx-cut"></i> ตัด CR -฿' . number_format($row['cr_transferred_out'], 2) . '</span>';
                    }
                }

                // จัดการ SSS Badges & Subgroups
                $sss_types = isset($sss_map[$vn]['sss_types']) ? $sss_map[$vn]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
                $sss_items = isset($sss_map[$vn]['items']) ? $sss_map[$vn]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

                if ($is_sss_acc_opd && (!empty($sss_types) || !empty($row['sss_origin_acc']) || !empty($row['sss_transferred_out']))) {
                    $sss_payload_data = [
                        'vn' => (string)$row['vn'],
                        'hn' => (string)($row['hn'] ?? ''),
                        'ptname' => (string)($row['ptname'] ?? ''),
                        'vstdate' => (string)($row['vstdate'] ?? ''),
                        'pttypename' => (string)($row['pttypename'] ?? ''),
                        'hospmain' => '',
                        'income' => floatval(cleanNum($row['income_original'] ?? ($row['income'] ?? $row['debit']))),
                        'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                        'debit' => floatval(cleanNum($row['debit'])),
                        'origin_acc' => (string)($row['sss_origin_acc'] ?? $row['accountcode']),
                        'target_acc' => '1102050101.309',
                        'sss_types' => $sss_types,
                        'sss_amount' => floatval(cleanNum($row['debit'])),
                        'remain_debit' => 0.0,
                        'items' => $sss_items
                    ];
                    $sss_payload_json_str = htmlspecialchars(json_encode($sss_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                    foreach ($sss_types as $st) {
                        $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 sss-item-badge-click" style="font-size:11px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="คลิกดูรายการ Instrument ประกันสังคม">' . htmlspecialchars($st) . '</span>';
                    }

                    if (!empty($row['sss_origin_acc']) && $row['sss_origin_acc'] !== '1102050101.309') {
                        $orig_short = str_replace('1102050101.', '.', $row['sss_origin_acc']);
                        $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 sss-item-badge-click" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="โอนมาจากผัง ' . htmlspecialchars($row['sss_origin_acc']) . '"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                    } elseif (!empty($row['sss_transferred_out']) && $row['sss_transferred_out'] > 0) {
                        $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 sss-item-badge-click" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="ตัดยอด Instrument ออกไปผัง .309 จำนวน ฿' . number_format($row['sss_transferred_out'], 2) . '"><i class="bx bx-cut"></i> ตัด Instrument -฿' . number_format($row['sss_transferred_out'], 2) . '</span>';
                    }
                }

                echo '<tr class="main-patient-row" data-cr-type="' . htmlspecialchars($cr_type_str) . '" '.$row_color.'>
                        <td style="text-align: center;">'.$i.'</td>
                        <td style="text-align: center;">'.$row["vn"].'</td>
                        <td style="text-align: center;">'.$row["hn"].'</td>
                        <td style="text-align: center;">'.$row["cid"].'</td>
                        <td>'.$row["ptname"].$cr_badge_html.'</td>
                        <td>'.$row["accountname"].'</td>
                        <td>'.$row["pttypename"].'</td>
                        <td style="text-align: center;">'.$row["vstdate"].'</td>
                        <td style="text-align: right;">'.formatMoney($row["income"], 2).'</td>
                        <td style="text-align: right;">'.formatMoney($row["incomediff"], 2).'</td>
                        <td style="text-align: right;">'.formatMoney($row["debit"], 2).'</td>
                        <td style="text-align: right;padding: 0px;" data-data1="'.$row["vn"].'">'.formatMoney($comp, 2).'</td>
                        <td style="text-align: center;padding: 0px;" data-data2="'.$row["vn"].'">'.$bill_no.'</td>
                        <td style="text-align: center;padding: 0px;" data-data3="'.$row["vn"].'">'.$bill_date.'</td>
                        <td style="text-align: center;padding: 0px;">'.$rep.'</td>
                      </tr>';

                $income += cleanNum($row["income"]);
                $incomediff += cleanNum($row["incomediff"]);
                $debit += cleanNum($row["debit"]);
                $total_comp += cleanNum($comp);
            }

            $percen = ($debit != 0) ? (($total_comp)/$debit)*100 : 0;
            echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            echo '<tr class="summary-total-row" style="font-weight: bold;background-color: #13758b; color: #fff;">
                    <td style="text-align: center;" colspan="8">
                        รวม <span id="o0" style="display:none;">'.$i.'</span>
                        <span id="modal_report_title_data" style="display:none;" data-title="'.htmlspecialchars($modal_full_title, ENT_QUOTES, 'UTF-8').'"></span>
                    </td>
                    <td style="text-align: right;" id="o1">&nbsp;'.formatMoney($income, 2).'</td>
                    <td style="text-align: right;" id="o2">&nbsp;'.formatMoney($incomediff, 2).'</td>
                    <td style="text-align: right;" id="o3">&nbsp;'.formatMoney($debit, 2).'</td>
                    <td style="text-align: right;" id="o4">&nbsp;'.number_format($total_comp,2).' ('.formatMoney($percen, 2).')</td>
                    <td style="text-align: right;" id="o5">&nbsp;</td>
                    <td style="text-align: right;" id="o7">&nbsp;</td>
                    <td style="text-align: right;" id="o6"><input readonly type="text" id="typecc" name="typecc" value="OPD" readonly style="border: none;width: 40px;text-align: center;" /></td>
                  </tr>';

            // ส่ง JSON สรุปแท็บกลุ่มย่อย CR กลับไปยังหน้าบ้าน
            echo '<script id="cr_subgroup_summary_json" type="application/json">' . json_encode($subgroup_stats, JSON_UNESCAPED_UNICODE) . '</script>';

        } else {
             echo '<tr><td colspan="15" style="text-align:center;">ไม่พบรายชื่อลูกหนี้ที่ตรงกับเงื่อนไข<span id="modal_report_title_data" style="display:none;" data-title="'.htmlspecialchars($modal_full_title, ENT_QUOTES, 'UTF-8').'"></span></td></tr>';
        }
    }
}



// =========================================================================
// [แก้ไขสมบูรณ์ 100%] ฝั่งผู้ป่วยใน IPD รายตัวแยกแผนก (actionipdlistonlypp)
// รองรับระบบจำแนกกลุ่มย่อย CR, Badges, และการตัดโอนยอดข้ามผัง
// =========================================================================
if(isset($_POST['actionipdlistonlypp'])){

    $actionipdlistonlys = mysqli_real_escape_string($conn, $_POST['actionipdlistonlys']);
    $actionipdlistonly = mysqli_real_escape_string($conn, $_POST['actionipdlistonlypp']);
    $id = mysqli_real_escape_string($conn, $_POST['iid']);
    $selectTypeOptpp = isset($_POST['selectTypeOptpp']) && $_POST['selectTypeOptpp'] !== '' ? mysqli_real_escape_string($conn, $_POST['selectTypeOptpp']) : '00';

    $_SESSION['monthtxts'] = $actionipdlistonlys;
    $_SESSION['monthtxt'] = $actionipdlistonly;
    $_SESSION['iid'] = $id;

    $partss = explode('-', $actionipdlistonlys);
    $months = $partss[0];
    $years  = $partss[1];

    $parts = explode('-', $actionipdlistonly);
    $month = $parts[0];
    $year  = $parts[1];

    $date_start = sprintf('%s-%02d-01', $years, $months);
    $date_end_first = sprintf('%s-%02d-01', $year, $month);
    $last_day   = date('t', strtotime($date_end_first));
    $date_end   = sprintf('%s-%02d-%s', $year, $month, $last_day);

    $next_day = date('Y-m-d', strtotime($date_end . ' +1 day'));

    // 1. ดึง AN List ตามเงื่อนไข $selectTypeOptpp
    $an_list = [];
    $sql_get_an = '';
    $connection_for_an = null;

    if ($selectTypeOptpp == '01') {
        $sql_get_an = "SELECT o.an FROM dental_care d JOIN ovst o ON o.vn = d.vn WHERE d.entry_datetime >= '$date_start 00:00:00' AND d.entry_datetime < '$next_day 00:00:00' AND o.an IS NOT NULL AND o.an != ''";
        $connection_for_an = $conn2;
    } elseif ($selectTypeOptpp == '02') {
        $sql_get_an = "SELECT an FROM physic_main_ipd WHERE vstdate BETWEEN '$date_start' AND '$date_end' AND an IS NOT NULL AND an != ''";
        $connection_for_an = $conn2;
    } elseif ($selectTypeOptpp == '03') {
        $sql_get_an = "SELECT an FROM health_med_service WHERE service_date BETWEEN '$date_start' AND '$date_end' AND an IS NOT NULL AND an != ''";
        $connection_for_an = $conn2;
    }

    if (!empty($sql_get_an) && $connection_for_an) {
        $result_an = mysqli_query($connection_for_an, $sql_get_an);
        if ($result_an) {
            while ($row_an = mysqli_fetch_assoc($result_an)) {
                if (isset($row_an['cid'])) $row_an['cid'] = decrypt_data($row_an['cid']);
                if (isset($row_an['tel'])) $row_an['tel'] = decrypt_data($row_an['tel']);
                $an_list[] = mysqli_real_escape_string($conn, $row_an['an']);
            }
        }
    }

    if ($selectTypeOptpp != '00' && empty($an_list)) {
        echo '<tr><td colspan="15" style="text-align:center;">ไม่พบข้อมูลบริการ (IPD) ตามประเภทที่เลือกในเดือนนี้</td></tr>';
        return;
    }

    $cr_config_ipd = get_active_cr_config($conn);
    $auto_split_ipd = is_cr_auto_split_enabled($cr_config_ipd, 'IPD');
    $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
    $an_filter_arg = ($selectTypeOptpp == '00') ? null : $an_list;

    // 2. รวบรวมข้อมูลสรุปการโอนยอด CR และ Visits (IPD) (กรองตาม $an_filter_arg)
    $all_cr_summaries_ipd = [];
    $all_cr_visits_ipd = [];
    $all_queried_months_ipd = [];

    $cur_time = strtotime($date_start);
    $end_time = strtotime($date_end);
    while ($cur_time <= $end_time) {
        $cur_m = intval(date('n', $cur_time));
        $cur_y = intval(date('Y', $cur_time));
        $m_txt = $cur_m . '-' . $cur_y;
        $all_queried_months_ipd[] = $m_txt;
        if (is_cr_effective_for_month($cr_config_ipd, $m_txt) && $auto_split_ipd) {
            $s = get_cr_splitting_summary_for_month($conn, $conn2, $m_txt, 'IPD', $cr_config_ipd, $my_hospcode, $an_filter_arg);
            if ($s && !empty($s['cr_visits'])) {
                $all_cr_summaries_ipd[$m_txt] = $s;
                foreach ($s['cr_visits'] as $an => $vinfo) {
                    $all_cr_visits_ipd[$an] = $vinfo;
                }
            }
        }
        $cur_time = strtotime('+1 month', $cur_time);
    }

    // 2.1 รวบรวมข้อมูลสรุปการโอนยอด SSS Instrument สำหรับ IPD (กรองตาม $an_filter_arg)
    $sss_config_ipd = get_active_sss_config($conn);
    $auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config_ipd, 'IPD');
    $all_sss_summaries_ipd = [];
    $all_sss_visits_ipd = [];

    $cur_time_sss_ipd = strtotime($date_start);
    while ($cur_time_sss_ipd <= $end_time) {
        $cur_m = intval(date('n', $cur_time_sss_ipd));
        $cur_y = intval(date('Y', $cur_time_sss_ipd));
        $m_txt = $cur_m . '-' . $cur_y;
        if (is_sss_effective_for_month($sss_config_ipd, $m_txt) && $auto_split_sss_ipd) {
            $s_sss_ipd = get_sss_splitting_summary_for_month($conn, $conn2, $m_txt, 'IPD', $sss_config_ipd, $my_hospcode, $an_filter_arg);
            if ($s_sss_ipd && !empty($s_sss_ipd['sss_visits'])) {
                $all_sss_summaries_ipd[$m_txt] = $s_sss_ipd;
                foreach ($s_sss_ipd['sss_visits'] as $an => $vinfo) {
                    $all_sss_visits_ipd[$an] = $vinfo;
                }
            }
        }
        $cur_time_sss_ipd = strtotime('+1 month', $cur_time_sss_ipd);
    }

    // 3. สร้าง SQL Query หลัก
    $an_filter_sql = '';
    if ($selectTypeOptpp != '00' && !empty($an_list)) {
        $an_in_clause = "'" . implode("','", $an_list) . "'";
        $an_filter_sql = " AND o.an IN ($an_in_clause) ";
    }
    $should_run_query = true;

    $is_kidney_click = (strpos($id, '_KIDNEY') !== false);
    $is_target_217 = ($id === '1102050101.217');
    $is_target_310 = ($id === '1102050101.310');
    $is_sss_parent_ipd = in_array($id, ['1102050101.302', '1102050101.304']);

    if ($is_kidney_click) {
        $base_account = str_replace('_KIDNEY', '', $id);
        $kidney_condition = "AND o.accountcode = '$base_account' AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%')";
    } else {
        if ($is_target_217 && !empty($all_cr_summaries_ipd)) {
            $transfer_ans = array_keys($all_cr_visits_ipd);
            $transfer_ans_quoted = !empty($transfer_ans) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_ans)) . "'" : "''";
            $kidney_condition = "AND ((o.accountcode = '1102050101.217') OR (o.an IN ($transfer_ans_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        } elseif ($is_target_310 && !empty($all_sss_summaries_ipd)) {
            $transfer_sss_ans = array_keys($all_sss_visits_ipd);
            $transfer_sss_ans_quoted = !empty($transfer_sss_ans) ? "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $transfer_sss_ans)) . "'" : "''";
            $kidney_condition = "AND ((o.accountcode = '1102050101.310') OR (o.an IN ($transfer_sss_ans_quoted))) AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        } else {
            $kidney_condition = "AND o.accountcode = '$id' AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
        }
    }

    // ดึงข้อมูลชื่อผังบัญชี / สิทธิการเงิน สำหรับแสดงที่หัวตาราง Modal IPD (เงินชดเชย IPD)
    $title_acc_name = '';
    $title_acc_code = $id;

    if ($is_target_217 || strpos($id, '1102050101.217') !== false) {
        $title_acc_code = '1102050101.217';
        $title_acc_name = 'ลูกหนี้ค่ารักษา UC - IP  บริการเฉพาะ (CR)';
    } elseif ($is_target_310 || strpos($id, '1102050101.310') !== false) {
        $title_acc_code = '1102050101.310';
        $title_acc_name = 'ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง IP';
    } else {
        $base_code = strpos($id, '_') !== false ? explode('_', $id)[0] : $id;
        $account_info_q = mysqli_query($conn, "SELECT accountcode, accountname FROM imr_tb_debtor_rights_ipd WHERE accountcode = '$base_code' LIMIT 1");
        if ($account_info_q && $acc_row = mysqli_fetch_assoc($account_info_q)) {
            $title_acc_name = $acc_row['accountname'];
            $title_acc_code = $acc_row['accountcode'];
        }
    }

    $title_suffix = '';
    if (!empty($title_acc_name)) {
        $title_suffix = " ($title_acc_name รหัส $title_acc_code)";
    } elseif (!empty($title_acc_code)) {
        $title_suffix = " (รหัส $title_acc_code)";
    }

    $modal_full_title = '📑 รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน' . $title_suffix;

    if ($should_run_query) {
        $sql = "SELECT
                o.an, o.hn, o.cid, o.ptname, o.dchdate,
                (o.income) as income, (o.income - COALESCE(o.original_debit, o.debit)) as incomediff, (o.debit) as debit,
                o.follow_money, o.bill, o.billdate, o.mobile,
                o.pttype_eclaim_name, o.accountname, o.accountcode, o.pttypename,
                SUM(IFNULL(stm.compensated, 0)) as compensated, 
                MAX(stm.rep) as rep,
                SUM(IFNULL(dckd.compensated, 0)) as compensatedckd, 
                MAX(dckd.rep) as repckd,
                SUM(IFNULL(ckd_ofc.amount, 0)) as compensatedckd_ofc, 
                MAX(ckd_ofc.stm_doc) as stm_doc
                FROM imr_tb_debtor_rights_ipd o
                LEFT JOIN imr_tb_check_invoice stm on stm.vn=o.an
                LEFT JOIN imr_tb_seamless_dckd dckd on dckd.vn=o.an
                LEFT JOIN imr_tb_seamless_dckd_ofc ckd_ofc on ckd_ofc.vn=o.an
                WHERE STR_TO_DATE(CONCAT('01-', o.monthtxt), '%d-%m-%Y') BETWEEN '$date_start' AND '$date_end'
                $kidney_condition $an_filter_sql
                GROUP BY o.an, o.hn, o.cid, o.ptname, o.dchdate, o.income, o.original_debit, o.debit, o.follow_money, o.bill, o.billdate, o.mobile, o.pttype_eclaim_name, o.accountname, o.accountcode, o.pttypename";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $raw_rows = [];
            $ans_to_detect = [];

            while ($r = mysqli_fetch_assoc($result)) {
                if (isset($r['cid'])) $r['cid'] = decrypt_data($r['cid']);
                if (isset($r['tel'])) $r['tel'] = decrypt_data($r['tel']);
                $raw_rows[] = $r;
                $ans_to_detect[] = $r['an'];
            }

            $pp_m_ipd = !empty($all_queried_months_ipd) ? $all_queried_months_ipd : ((!empty($date_start)) ? date('n-Y', strtotime($date_start)) : null);

            // จำแนกประเภทกลุ่มย่อย CR (IPD)
            $cr_map_ipd = [];
            if (!empty($ans_to_detect) && $conn2) {
                $cr_map_ipd = detect_cr_types_for_visit_list($conn2, $ans_to_detect, 'IPD', $cr_config_ipd, $my_hospcode, $pp_m_ipd);
            }

            // จำแนกประเภทกลุ่มย่อย SSS (IPD)
            $sss_map_ipd = [];
            if (!empty($ans_to_detect) && $conn2) {
                $sss_map_ipd = detect_sss_types_for_visit_list($conn2, $ans_to_detect, 'IPD', $sss_config_ipd, $my_hospcode, $pp_m_ipd);
            }

            // เตรียมโครงสร้าง Subgroup Stats สำหรับ Dynamic Tabs (IPD รองรับ CR และ SSS)
            $base_id = explode('_', $id)[0];
            $is_cr_effective_ipd = !empty($all_cr_summaries_ipd);
            $is_sss_effective_ipd = !empty($all_sss_summaries_ipd);
            $is_cr_acc_ipd = $is_cr_effective_ipd && $auto_split_ipd && !$is_kidney_click && (
                $is_target_217 ||
                ($base_id === '1102050101.202' && is_cr_parent_account_enabled($cr_config_ipd, $base_id))
            );
            $is_sss_acc_ipd = $is_sss_effective_ipd && $auto_split_sss_ipd && !$is_kidney_click && (
                $is_target_310 ||
                (in_array($base_id, ['1102050101.302', '1102050101.304']) && is_sss_parent_account_enabled($sss_config_ipd, $base_id))
            );

            $subgroup_stats = [];

            if ($is_sss_acc_ipd) {
                $subgroup_stats['ALL'] = [
                    'id' => 'ALL',
                    'title' => 'ทั้งหมด (All SSS)',
                    'short_name' => 'ทั้งหมด',
                    'count' => 0,
                    'debit' => 0.0
                ];
                $sg_source_ipd = isset($sss_config_ipd['subgroups_ipd']) ? $sss_config_ipd['subgroups_ipd'] : [];
                if (!empty($sg_source_ipd)) {
                    foreach ($sg_source_ipd as $sg_id => $sg) {
                        if (!is_sss_subgroup_enabled($sss_config_ipd, $sg_id, 'IPD', $pp_m_ipd)) continue;
                        $subgroup_stats[$sg_id] = [
                            'id' => $sg_id,
                            'title' => $sg['title'] ?? $sg_id,
                            'short_name' => $sg['short_name'] ?? $sg_id,
                            'count' => 0,
                            'debit' => 0.0
                        ];
                    }
                }
            } elseif ($is_cr_acc_ipd) {
                $subgroup_stats['ALL'] = [
                    'id' => 'ALL',
                    'title' => 'ทั้งหมด (All CR)',
                    'short_name' => 'ทั้งหมด',
                    'count' => 0,
                    'debit' => 0.0
                ];
                $sg_source_ipd = isset($cr_config_ipd['subgroups_ipd']) ? $cr_config_ipd['subgroups_ipd'] : [];
                if (!empty($sg_source_ipd)) {
                    foreach ($sg_source_ipd as $sg_id => $sg) {
                        if (!is_cr_subgroup_enabled($cr_config_ipd, $sg_id, 'IPD', $pp_m_ipd)) continue;
                        $subgroup_stats[$sg_id] = [
                            'id' => $sg_id,
                            'title' => $sg['title'] ?? $sg_id,
                            'short_name' => $sg['short_name'] ?? $sg_id,
                            'count' => 0,
                            'debit' => 0.0
                        ];
                    }
                }
            }

            $cr_bd_map_i = [];
            $sss_bd_map_i = [];
            if (!empty($ans_to_detect)) {
                $safe_det_ans = "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $ans_to_detect)) . "'";
                $q_cr_bd_i = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_cr_breakdown WHERE visit_type = 'IPD' AND vn IN ($safe_det_ans)");
                if ($q_cr_bd_i) {
                    while ($bd = mysqli_fetch_assoc($q_cr_bd_i)) {
                        $cr_bd_map_i[$bd['vn']] = $bd;
                    }
                }
                $q_sss_bd_i = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_sss_breakdown WHERE visit_type = 'IPD' AND vn IN ($safe_det_ans)");
                if ($q_sss_bd_i) {
                    while ($bd = mysqli_fetch_assoc($q_sss_bd_i)) {
                        $sss_bd_map_i[$bd['vn']] = $bd;
                    }
                }
            }

            $all_display_rows = [];

            foreach ($raw_rows as $row) {
                $an = $row['an'];
                $row['income_original'] = floatval(cleanNum($row['income']));
                $row['incomediff_original'] = floatval(cleanNum($row['incomediff']));
                $is_transferred_visit = isset($all_cr_visits_ipd[$an]);
                $is_sss_transferred_visit = isset($all_sss_visits_ipd[$an]);

                if ($is_target_217 && !empty($all_cr_summaries_ipd)) {
                    if ($is_transferred_visit) {
                        $vinfo = $all_cr_visits_ipd[$an];
                        $row['debit'] = $vinfo['cr_amount'];
                        $row['income'] = $vinfo['cr_amount'];
                        $row['incomediff'] = 0.0;
                        $row['cr_origin_acc'] = $vinfo['origin_accountcode'] ?? ($vinfo['origin_account'] ?? '1102050101.202');
                        $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                        $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];

                        if (isset($cr_bd_map_i[$an])) {
                            $row['follow_money'] = cleanNum($cr_bd_map_i[$an]['compensated']);
                            $row['compensated'] = cleanNum($cr_bd_map_i[$an]['compensated']);
                            $row['bill'] = $cr_bd_map_i[$an]['bill'];
                            $row['billdate'] = $cr_bd_map_i[$an]['billdate'];
                        } else {
                            $row['follow_money'] = min(cleanNum($row['follow_money'] ?? 0), floatval($vinfo['cr_amount']));
                            $row['compensated'] = $row['follow_money'];
                        }
                    } else {
                        $row['cr_origin_acc'] = '1102050101.217';
                        $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                        $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];
                        $v_month_i = $row['monthtxt'] ?? '';
                        if (isset($all_cr_summaries_ipd[$v_month_i]) && isset($cr_bd_map_i[$an])) {
                            $row['follow_money'] = cleanNum($cr_bd_map_i[$an]['compensated']);
                            $row['compensated'] = cleanNum($cr_bd_map_i[$an]['compensated']);
                            $row['bill'] = $cr_bd_map_i[$an]['bill'];
                            $row['billdate'] = $cr_bd_map_i[$an]['billdate'];
                        }
                    }
                    $all_display_rows[] = $row;

                } elseif ($is_target_310 && !empty($all_sss_summaries_ipd)) {
                    if ($is_sss_transferred_visit) {
                        $vinfo = $all_sss_visits_ipd[$an];
                        $row['debit'] = $vinfo['sss_amount'];
                        $row['income'] = $vinfo['sss_amount'];
                        $row['incomediff'] = 0.0;
                        $row['sss_origin_acc'] = $vinfo['origin_accountcode'] ?? '1102050101.302';
                        $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];

                        if (isset($sss_bd_map_i[$an])) {
                            $row['follow_money'] = cleanNum($sss_bd_map_i[$an]['compensated']);
                            $row['compensated'] = cleanNum($sss_bd_map_i[$an]['compensated']);
                            $row['bill'] = $sss_bd_map_i[$an]['bill'];
                            $row['billdate'] = $sss_bd_map_i[$an]['billdate'];
                        } else {
                            $row['follow_money'] = min(cleanNum($row['follow_money'] ?? 0), floatval($vinfo['sss_amount']));
                            $row['compensated'] = $row['follow_money'];
                        }
                    } else {
                        $row['sss_origin_acc'] = '1102050101.310';
                        $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                        $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];
                        $v_month_i = $row['monthtxt'] ?? '';
                        if (isset($all_sss_summaries_ipd[$v_month_i]) && isset($sss_bd_map_i[$an])) {
                            $row['follow_money'] = cleanNum($sss_bd_map_i[$an]['compensated']);
                            $row['compensated'] = cleanNum($sss_bd_map_i[$an]['compensated']);
                            $row['bill'] = $sss_bd_map_i[$an]['bill'];
                            $row['billdate'] = $sss_bd_map_i[$an]['billdate'];
                        }
                    }
                    $all_display_rows[] = $row;

                } elseif (!empty($all_cr_summaries_ipd) && !$is_kidney_click && $id === '1102050101.202') {
                    if ($is_transferred_visit) {
                        $vinfo = $all_cr_visits_ipd[$an];
                        if ($vinfo['is_full_transfer']) {
                            continue;
                        } else {
                            $row['debit'] = $vinfo['general_remain_amount'];
                            $row['cr_transferred_out'] = $vinfo['cr_amount'];
                            $row['cr_types'] = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : [];
                            $row['items'] = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : [];

                            // หักเงินชดเชยที่โอนไปผังลูกออก
                            $cr_comp = isset($cr_bd_map_i[$an]) ? cleanNum($cr_bd_map_i[$an]['compensated']) : min(cleanNum($row['compensated'] ?? $row['follow_money']), floatval($vinfo['cr_amount']));
                            $mother_comp = max(0, cleanNum($row['compensated'] ?? $row['follow_money']) - $cr_comp);
                            $row['follow_money'] = $mother_comp;
                            $row['compensated'] = $mother_comp;

                            if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                                $row['bill'] = '';
                                $row['billdate'] = '';
                            }
                            $all_display_rows[] = $row;
                        }
                    } else {
                        $all_display_rows[] = $row;
                    }
                } elseif (!empty($all_sss_summaries_ipd) && !$is_kidney_click && $is_sss_parent_ipd) {
                    if ($is_sss_transferred_visit) {
                        $vinfo = $all_sss_visits_ipd[$an];
                        if ($vinfo['is_full_transfer']) {
                            continue;
                        } else {
                            $row['debit'] = $vinfo['general_remain_amount'];
                            $row['sss_transferred_out'] = $vinfo['sss_amount'];
                            $row['sss_types'] = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : ['SSS-Instrument'];
                            $row['sss_items'] = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : [];

                            // หักเงินชดเชยที่โอนไปผังลูกออก
                            $sss_comp = isset($sss_bd_map_i[$an]) ? cleanNum($sss_bd_map_i[$an]['compensated']) : min(cleanNum($row['compensated'] ?? $row['follow_money']), floatval($vinfo['sss_amount']));
                            $mother_comp = max(0, cleanNum($row['compensated'] ?? $row['follow_money']) - $sss_comp);
                            $row['follow_money'] = $mother_comp;
                            $row['compensated'] = $mother_comp;

                            if ($mother_comp <= 0 || ($row['debit'] - $mother_comp) > 0.01) {
                                $row['bill'] = '';
                                $row['billdate'] = '';
                            }
                            $all_display_rows[] = $row;
                        }
                    } else {
                        $all_display_rows[] = $row;
                    }
                } else {
                    $all_display_rows[] = $row;
                }
            }

            // แสดงผลรายการ IPD
            $i=0;$income=0;$incomediff=0;$debit=0;$total_comp=0;

            foreach ($all_display_rows as $row) {
                $i++;
                $an = $row['an'];
                $is_row_kidney = ($is_kidney_click || strpos($row["pttypename"], 'ฟอกไต') !== false || strpos($row["pttypename"], 'ไต') !== false);
                $row_color = $is_row_kidney ? 'style="color: #cd641f;"' : '';

                $comp_base = !empty($row["follow_money"]) ? cleanNum($row["follow_money"]) : cleanNum($row["compensated"]);
                $comp = $comp_base + cleanNum($row["compensatedckd"]) + cleanNum($row["compensatedckd_ofc"]);

                // จัดการข้อมูลเลขที่และวันที่ใบเสร็จ
                $bill_no = '';
                $bill_date = '';
                if (!empty($row["bill"])) {
                    $bill_no = $row["bill"];
                    $bill_date = $row["billdate"] ?? '';
                } elseif (!empty($row["mobile"])) {
                    if (strpos($row["mobile"], '-') !== false) {
                        list($b1, $bdate1) = explode('-', $row["mobile"], 2);
                        $bill_no = $b1;
                        $bill_date = $bdate1;
                    } else {
                        $bill_no = $row["mobile"];
                    }
                }

                // จัดการ Rep.
                $rep = '';
                if (!empty($row["rep"])) {
                    $rep = $row["rep"];
                } elseif (!empty($row["repckd"])) {
                    $rep = $row["repckd"];
                } elseif (!empty($row["stm_doc"])) {
                    $rep = $row["stm_doc"];
                }

                // จัดการ CR Badges & Subgroups (IPD)
                $cr_types = isset($cr_map_ipd[$an]['cr_types']) ? $cr_map_ipd[$an]['cr_types'] : (isset($row['cr_types']) ? $row['cr_types'] : []);
                $cr_items = isset($cr_map_ipd[$an]['items']) ? $cr_map_ipd[$an]['items'] : (isset($row['items']) ? $row['items'] : []);
                $sss_types = isset($sss_map_ipd[$an]['sss_types']) ? $sss_map_ipd[$an]['sss_types'] : (isset($row['sss_types']) ? $row['sss_types'] : []);
                $sss_items = isset($sss_map_ipd[$an]['items']) ? $sss_map_ipd[$an]['items'] : (isset($row['sss_items']) ? $row['sss_items'] : []);

                $all_types_ipd = array_unique(array_merge($cr_types, $sss_types));
                $cr_type_str = implode(',', $all_types_ipd);
                $cr_badge_html = '';

                if (($is_cr_acc_ipd || $is_sss_acc_ipd) && !empty($subgroup_stats)) {
                    $subgroup_stats['ALL']['count']++;
                    $subgroup_stats['ALL']['debit'] += cleanNum($row['debit']);
                    foreach ($all_types_ipd as $ct) {
                        if (isset($subgroup_stats[$ct])) {
                            $subgroup_stats[$ct]['count']++;
                            $subgroup_stats[$ct]['debit'] += cleanNum($row['debit']);
                        }
                    }
                }

                if ($is_cr_acc_ipd && (!empty($cr_types) || !empty($row['cr_origin_acc']) || !empty($row['cr_transferred_out']))) {
                    $cr_payload_data = [
                        'vn' => (string)$row['an'],
                        'hn' => (string)($row['hn'] ?? ''),
                        'ptname' => (string)($row['ptname'] ?? ''),
                        'vstdate' => (string)($row['dchdate'] ?? ''),
                        'pttypename' => (string)($row['pttypename'] ?? ''),
                        'hospmain' => '',
                        'income' => floatval(cleanNum($row['income_original'] ?? ($row['income'] ?? $row['debit']))),
                        'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                        'debit' => floatval(cleanNum($row['debit'])),
                        'origin_acc' => (string)($row['cr_origin_acc'] ?? $row['accountcode']),
                        'target_acc' => '1102050101.217',
                        'cr_types' => $cr_types,
                        'cr_amount' => floatval(cleanNum($row['debit'])),
                        'remain_debit' => 0.0,
                        'items' => $cr_items
                    ];
                    $payload_json_str = htmlspecialchars(json_encode($cr_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                    foreach ($cr_types as $ct) {
                        $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 cr-item-badge-click" style="font-size:11px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this)" title="คลิกดูรายการที่โอนมา / รายการ CR">' . htmlspecialchars($ct) . '</span>';
                    }

                    if (!empty($row['cr_origin_acc']) && $row['cr_origin_acc'] !== '1102050101.217') {
                        $orig_short = str_replace('1102050101.', '.', $row['cr_origin_acc']);
                        $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 cr-item-badge-click" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this)" title="โอนมาจากผัง ' . htmlspecialchars($row['cr_origin_acc']) . '"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                    } elseif (!empty($row['cr_transferred_out']) && $row['cr_transferred_out'] > 0) {
                        $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 cr-item-badge-click" style="font-size:10px; cursor:pointer;" data-cr-payload="' . $payload_json_str . '" onclick="openCrPatientBreakdownModal(this)" title="ตัดยอด CR ออกไปผัง .217 จำนวน ฿' . number_format($row['cr_transferred_out'], 2) . '"><i class="bx bx-cut"></i> ตัด CR -฿' . number_format($row['cr_transferred_out'], 2) . '</span>';
                    }
                }

                // จัดการ SSS Badges & Subgroups (IPD)
                if ($is_sss_acc_ipd && (!empty($sss_types) || !empty($row['sss_origin_acc']) || !empty($row['sss_transferred_out']))) {
                    $sss_payload_data = [
                        'vn' => (string)$row['an'],
                        'hn' => (string)($row['hn'] ?? ''),
                        'ptname' => (string)($row['ptname'] ?? ''),
                        'vstdate' => (string)($row['dchdate'] ?? ''),
                        'pttypename' => (string)($row['pttypename'] ?? ''),
                        'hospmain' => '',
                        'income' => floatval(cleanNum($row['income_original'] ?? ($row['income'] ?? $row['debit']))),
                        'paid' => floatval(cleanNum($row['incomediff_original'] ?? ($row['incomediff'] ?? 0))),
                        'debit' => floatval(cleanNum($row['debit'])),
                        'origin_acc' => (string)($row['sss_origin_acc'] ?? $row['accountcode']),
                        'target_acc' => '1102050101.310',
                        'sss_types' => $sss_types,
                        'sss_amount' => floatval(cleanNum($row['debit'])),
                        'remain_debit' => 0.0,
                        'items' => $sss_items
                    ];
                    $sss_payload_json_str = htmlspecialchars(json_encode($sss_payload_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                    foreach ($sss_types as $st) {
                        $cr_badge_html .= ' <span class="badge bg-label-primary font-monospace py-0 px-1 sss-item-badge-click" style="font-size:11px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="คลิกดูรายการ Instrument ประกันสังคม">' . htmlspecialchars($st) . '</span>';
                    }

                    if (!empty($row['sss_origin_acc']) && $row['sss_origin_acc'] !== '1102050101.310') {
                        $orig_short = str_replace('1102050101.', '.', $row['sss_origin_acc']);
                        $cr_badge_html .= ' <span class="badge bg-label-info font-monospace py-0 px-1 sss-item-badge-click" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="โอนมาจากผัง ' . htmlspecialchars($row['sss_origin_acc']) . '"><i class="bx bx-transfer-alt"></i> โอนจาก ' . htmlspecialchars($orig_short) . '</span>';
                    } elseif (!empty($row['sss_transferred_out']) && $row['sss_transferred_out'] > 0) {
                        $cr_badge_html .= ' <span class="badge bg-label-warning font-monospace py-0 px-1 sss-item-badge-click" style="font-size:10px; cursor:pointer;" data-sss-payload="' . $sss_payload_json_str . '" onclick="openSssPatientBreakdownModal(this)" title="ตัดยอด Instrument ออกไปผัง .310 จำนวน ฿' . number_format($row['sss_transferred_out'], 2) . '"><i class="bx bx-cut"></i> ตัด Instrument -฿' . number_format($row['sss_transferred_out'], 2) . '</span>';
                    }
                }

                echo '<tr class="main-patient-row" data-cr-type="' . htmlspecialchars($cr_type_str) . '" '.$row_color.'>
                        <td style="text-align: center;">'.$i.'</td>
                        <td style="text-align: center;">'.$row["an"].'</td>
                        <td style="text-align: center;">'.$row["hn"].'</td>
                        <td style="text-align: center;">'.$row["cid"].'</td>
                        <td>'.$row["ptname"].$cr_badge_html.'</td>
                        <td>'.$row["accountname"].'</td>
                        <td>'.$row["pttypename"].'</td>
                        <td style="text-align: center;">'.$row["dchdate"].'</td>
                        <td style="text-align: right;">'.formatMoney($row["income"], 2).'</td>
                        <td style="text-align: right;">'.formatMoney($row["incomediff"], 2).'</td>
                        <td style="text-align: right;">'.formatMoney($row["debit"], 2).'</td>
                        <td style="text-align: right;padding: 0px;" data-data1i="'.$row["an"].'">'.formatMoney($comp, 2).'</td>
                        <td style="text-align: center;padding: 0px;" data-data2i="'.$row["an"].'">'.$bill_no.'</td>
                        <td style="text-align: center;padding: 0px;" data-data3i="'.$row["an"].'">'.$bill_date.'</td>
                        <td style="text-align: center;padding: 0px;">'.$rep.'</td>
                      </tr>';

                $income += cleanNum($row["income"]);
                $incomediff += cleanNum($row["incomediff"]);
                $debit += cleanNum($row["debit"]);
                $total_comp += cleanNum($comp);
            }

            $percen = ($debit != 0) ? (($total_comp)/$debit)*100 : 0;
            echo '<tr class="spacer-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            echo '<tr class="summary-total-row" style="font-weight: bold;background-color: #13758b; color: #fff;">
                  <td style="text-align: center;" colspan="8">
                      รวม <span id="i0" style="display:none;">'.$i.'</span>
                      <span id="modal_report_title_data" style="display:none;" data-title="'.htmlspecialchars($modal_full_title, ENT_QUOTES, 'UTF-8').'"></span>
                  </td>
                  <td style="text-align: right;" id="i1">&nbsp;'.formatMoney($income, 2).'</td>
                  <td style="text-align: right;" id="i2">&nbsp;'.formatMoney($incomediff, 2).'</td>
                  <td style="text-align: right;" id="i3">&nbsp;'.formatMoney($debit, 2).'</td>
                  <td style="text-align: right;" id="i4">&nbsp;'.number_format($total_comp,2).' ('.formatMoney($percen, 2).')</td>
                  <td style="text-align: right;" id="i5">&nbsp;</td>
                  <td style="text-align: right;" id="i6">&nbsp;</td>
                  <td style="text-align: right;" id="i7"><input readonly type="text" id="typecc" name="typecc" value="IPD" readonly style="border: none;width: 40px;text-align: center;" /></td>
                  </tr>';

            // ส่ง JSON สรุปแท็บกลุ่มย่อย CR (IPD) กลับไปยังหน้าบ้าน
            echo '<script id="cr_subgroup_summary_json" type="application/json">' . json_encode($subgroup_stats, JSON_UNESCAPED_UNICODE) . '</script>';

        } else {
             echo '<tr><td colspan="15" style="text-align:center;">ไม่พบรายชื่อลูกหนี้ (IPD) ที่ตรงกับเงื่อนไข<span id="modal_report_title_data" style="display:none;" data-title="'.htmlspecialchars($modal_full_title, ENT_QUOTES, 'UTF-8').'"></span></td></tr>';
        }
    }
}


if (isset($_POST['action_bulk_update_bill'])) {

    $typecc = mysqli_real_escape_string($conn, $_POST['typecc']);
    $bill = mysqli_real_escape_string($conn, $_POST['bulk_bill']);         // รับค่าจากชื่อใหม่
    $billdate = mysqli_real_escape_string($conn, $_POST['bulk_billdate']); // รับค่าจากชื่อใหม่
    $target_accountcode = isset($_POST['accountcode']) ? mysqli_real_escape_string($conn, $_POST['accountcode']) : '';

    // แกะกล่อง JSON string กลับมาเป็น Array ของ PHP
    $ids = json_decode($_POST['ids'], true);

    if(empty($ids) || !is_array($ids)) {
        echo "error_no_data";
        exit;
    }

    // ทำความสะอาดข้อมูลใน Array ป้องกัน SQL Injection
    $safe_ids = array_map(function($id) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $id) . "'";
    }, $ids);

    // แปลง Array เป็น String เช่น '670101','670102'
    $id_string = implode(",", $safe_ids);

    $is_cr_child = (strpos($target_accountcode, '1102050101.216') !== false || strpos($target_accountcode, '1102050101.217') !== false);
    $is_sss_child = (strpos($target_accountcode, '1102050101.309') !== false || strpos($target_accountcode, '1102050101.310') !== false);

    if ($is_cr_child) {
        // อัปเดตใบเสร็จลงตาราง breakdown ของ CR
        mysqli_query($conn, "UPDATE imr_tb_debtor_cr_breakdown 
            SET bill = '$bill', billdate = '$billdate', settle_status = 'SETTLED' 
            WHERE visit_type = '$typecc' AND vn IN ($id_string)");

        // สำหรับเคสที่โอน 100% (ไม่มีหนี้คงเหลือที่ผังแม่) ให้อัปเดตตารางหลักด้วย
        if ($typecc === 'OPD') {
            mysqli_query($conn, "UPDATE imr_tb_debtor_rights_opd o
                JOIN imr_tb_debtor_cr_breakdown b ON b.vn = o.vn AND b.visit_type = 'OPD'
                SET o.bill = '$bill', o.billdate = '$billdate' 
                WHERE o.vn IN ($id_string) AND b.general_remain_amount <= 0");
        } else {
            mysqli_query($conn, "UPDATE imr_tb_debtor_rights_ipd o
                JOIN imr_tb_debtor_cr_breakdown b ON b.vn = o.an AND b.visit_type = 'IPD'
                SET o.bill = '$bill', o.billdate = '$billdate' 
                WHERE o.an IN ($id_string) AND b.general_remain_amount <= 0");
        }
    } elseif ($is_sss_child) {
        // อัปเดตใบเสร็จลงตาราง breakdown ของ SSS
        mysqli_query($conn, "UPDATE imr_tb_debtor_sss_breakdown 
            SET bill = '$bill', billdate = '$billdate', settle_status = 'SETTLED' 
            WHERE visit_type = '$typecc' AND vn IN ($id_string)");

        if ($typecc === 'OPD') {
            mysqli_query($conn, "UPDATE imr_tb_debtor_rights_opd o
                JOIN imr_tb_debtor_sss_breakdown b ON b.vn = o.vn AND b.visit_type = 'OPD'
                SET o.bill = '$bill', o.billdate = '$billdate' 
                WHERE o.vn IN ($id_string) AND b.general_remain_amount <= 0");
        } else {
            mysqli_query($conn, "UPDATE imr_tb_debtor_rights_ipd o
                JOIN imr_tb_debtor_sss_breakdown b ON b.vn = o.an AND b.visit_type = 'IPD'
                SET o.bill = '$bill', o.billdate = '$billdate' 
                WHERE o.an IN ($id_string) AND b.general_remain_amount <= 0");
        }
    } else {
        // ผังแม่ หรือผังทั่วไป
        if ($typecc === 'OPD') {
            $sql = "UPDATE imr_tb_debtor_rights_opd SET bill = '$bill', billdate = '$billdate' WHERE vn IN ($id_string)";
        } else {
            $sql = "UPDATE imr_tb_debtor_rights_ipd SET bill = '$bill', billdate = '$billdate' WHERE an IN ($id_string)";
        }
        mysqli_query($conn, $sql);
    }

    echo "success";
    exit;
}

// =========================================================
// เพิ่มใหม่: สำหรับดึงข้อมูลการรับบริการซ้ำในวันเดียวกัน
// =========================================================
if (isset($_POST['action_get_duplicate_visits'])) {
    $cid = mysqli_real_escape_string($conn, $_POST['cid']);
    $vstdate = mysqli_real_escape_string($conn, $_POST['vstdate']);

    // ดึง OPD
    $sql_opd = "SELECT vn as id, ptname, vstdate as date, accountname, income, debit, (income - debit) as paid, 'OPD' as type
                FROM imr_tb_debtor_rights_opd
                WHERE cid = '$cid' AND vstdate = '$vstdate'";

    // ดึง IPD
    $sql_ipd = "SELECT an as id, ptname, dchdate as date, accountname, income, debit, (income - debit) as paid, 'IPD' as type
                FROM imr_tb_debtor_rights_ipd
                WHERE cid = '$cid' AND (dchdate = '$vstdate' OR admdate = '$vstdate')";

    $sql = "($sql_opd) UNION ALL ($sql_ipd) ORDER BY id";
    $result = mysqli_query($conn, $sql);

    echo '<table class="table table-bordered table-striped table-hover" style="font-size: 14px; margin-bottom: 0;">';
    echo '<thead style="background-color: #f1f1f1;">
            <tr>
                <th style="text-align: center;">ประเภท</th>
                <th style="text-align: center;">VN / AN</th>
                <th>ชื่อ-สกุล</th>
                <th style="text-align: center;">วันที่</th>
                <th>สิทธิ</th>
                <th style="text-align: right;">ค่าใช้จ่าย</th>
                <th style="text-align: right;">ชำระแล้ว</th>
                <th style="text-align: right;">ภาระหนี้</th>
            </tr>
          </thead>
          <tbody>';

    if($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['cid'])) $row['cid'] = decrypt_data($row['cid']);
    if (isset($row['tel'])) $row['tel'] = decrypt_data($row['tel']);
            $income = isset($row['income']) ? (float)$row['income'] : 0;
            $debit = isset($row['debit']) ? (float)$row['debit'] : 0;
            $paid = isset($row['paid']) ? (float)$row['paid'] : 0;

            echo '<tr>';
            echo '<td style="font-weight: bold; color: #555; text-align: center;">'.$row['type'].'</td>';
            echo '<td style="text-align: center;">'.$row['id'].'</td>';
            echo '<td>'.$row['ptname'].'</td>';
            echo '<td style="text-align: center;">'.$row['date'].'</td>';
            echo '<td>'.$row['accountname'].'</td>';
            echo '<td style="text-align: right;">'.formatMoney($income, 2).'</td>';
            echo '<td style="text-align: right;">'.formatMoney($paid, 2).'</td>';
            echo '<td style="color:blue; font-weight: bold; text-align: right;">'.formatMoney($debit, 2).'</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="8" class="text-center" style="color: red;">ไม่พบข้อมูลเพิ่มเติมหรือเกิดข้อผิดพลาด</td></tr>';
    }
    echo '</tbody></table>';
    exit;
}