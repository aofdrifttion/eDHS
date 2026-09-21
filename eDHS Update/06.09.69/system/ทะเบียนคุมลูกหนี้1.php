<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
if (function_exists('system_log')) {
    $pageName = basename($_SERVER['PHP_SELF']);
    system_log($conn, 'เปิดดูข้อมูล (View)', 'VIEW', "ผู้ใช้เปิดดูหน้ารายงาน/ข้อมูล: " . $pageName);
}

include 'check_auth.php';
checkRole(['admin', 'finance', 'accounting']);


function DateThai($strDate)
{
    $strYear = date("Y",strtotime($strDate))+543;
    $strMonth= date("n",strtotime($strDate));
    $strDay= date("j",strtotime($strDate));

    $strMonthCut = Array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
    $strMonthThai=$strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear";
}
$strDate = date("Y/m/d");
$yyy = date("Y")+543;


if (isset($_POST['month'])) {

    $month = $_POST['month']; // รับค่าจาก AJAX 
    $monthlast = $_POST['monthlast']; // รับค่าจาก AJAX 

    $datetime = DateTime::createFromFormat('m-Y', $month); // แปลงเป็น DateTime object
    $datetime->modify('-1 month'); // ลบ 1 เดือน
    $ffff = $datetime->format('m-Y'); // แปลงกลับเป็นรูปแบบ m-Y

    // 🌟 โหลดและเตรียมการตัดโอน CR & SSS Cross-Account Splitting ให้ตรงกับเอกสาร PDF
    require_once __DIR__ . '/includes/cr_migration_helper.php';
    require_once __DIR__ . '/includes/sss_migration_helper.php';

    $cr_config = get_active_cr_config($conn);
    $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';
    $auto_split_opd = is_cr_auto_split_enabled($cr_config, 'OPD');
    $auto_split_ipd = is_cr_auto_split_enabled($cr_config, 'IPD');

    $cr_opd = (is_cr_effective_for_month($cr_config, $month) && $auto_split_opd)
        ? get_cr_splitting_summary_for_month($conn, $conn2 ?? null, $month, 'OPD', $cr_config, $my_hospcode) : null;
    $cr_ipd = (is_cr_effective_for_month($cr_config, $month) && $auto_split_ipd)
        ? get_cr_splitting_summary_for_month($conn, $conn2 ?? null, $month, 'IPD', $cr_config, $my_hospcode) : null;

    $sss_config = get_active_sss_config($conn);
    $auto_split_sss_opd = is_sss_auto_split_enabled($sss_config, 'OPD');
    $auto_split_sss_ipd = is_sss_auto_split_enabled($sss_config, 'IPD');

    $sss_opd = (is_sss_effective_for_month($sss_config, $month) && $auto_split_sss_opd)
        ? get_sss_splitting_summary_for_month($conn, $conn2 ?? null, $month, 'OPD', $sss_config, $my_hospcode) : null;
    $sss_ipd = (is_sss_effective_for_month($sss_config, $month) && $auto_split_sss_ipd)
        ? get_sss_splitting_summary_for_month($conn, $conn2 ?? null, $month, 'IPD', $sss_config, $my_hospcode) : null;




    // 🛡️ [CR IPD Engine Safeguard] หากระบบ CR IPD ปิดอยู่ ให้ยุบผังลูก .217 รวมเข้าผังแม่ .202
    $ipd_sub_acc = (!$auto_split_ipd) ? "IF(accountcode = '1102050101.217', '1102050101.202', accountcode)" : "accountcode";
    $ipd_sub_name = (!$auto_split_ipd) ? "MAX(IF(accountcode = '1102050101.217', 'ลูกหนี้ค่ารักษา UC - IP', accountname))" : "MAX(accountname)";

    $sql = "SELECT 
    main_query.accountcode,
    MAX(main_query.accountname) AS accountname,
    SUM(main_query.total_debitlast) AS total_debitlast,
    SUM(main_query.total_cchnlast) AS total_cchnlast,
    SUM(main_query.total_debit) AS total_debit,
    SUM(main_query.total_update_count) AS total_update_count,
    CASE 
        WHEN imr_tb_debtor_result.column6 = 0.00 THEN '0.00' 
        WHEN imr_tb_debtor_result.column6 IS NULL THEN '' 
        ELSE imr_tb_debtor_result.column6 
    END AS column6,
    CASE 
        WHEN imr_tb_debtor_result.column7 = 0.00 OR imr_tb_debtor_result.column7 IS NULL 
        THEN '' ELSE imr_tb_debtor_result.column7 
    END AS column7,
    CASE 
        WHEN imr_tb_debtor_result.column8 = 0.00 THEN '0.00' 
        WHEN imr_tb_debtor_result.column8 IS NULL THEN '' 
        ELSE imr_tb_debtor_result.column8 
    END AS column8,
    CASE 
        WHEN imr_tb_debtor_result.column9 = 0.00 THEN '0.00' 
        WHEN imr_tb_debtor_result.column9 IS NULL THEN '' 
        ELSE imr_tb_debtor_result.column9 
    END AS column9,
    CASE 
        WHEN imr_tb_debtor_result.column10 = 0.00 THEN '0.00' 
        WHEN imr_tb_debtor_result.column10 IS NULL THEN '' 
        ELSE imr_tb_debtor_result.column10 
    END AS column10,
    CASE 
        WHEN imr_tb_debtor_result.column11 = 0.00 OR imr_tb_debtor_result.column11 IS NULL 
        THEN '' ELSE imr_tb_debtor_result.column11 
    END AS column11,
    CASE 
        WHEN imr_tb_debtor_result.column10 > 0 AND imr_tb_debtor_result.column12 = 0.00 THEN '0.00'
        WHEN imr_tb_debtor_result.column9 > 0 AND imr_tb_debtor_result.column10 = 0.00 THEN 'รอยืนยันจากบัญชี'
        WHEN imr_tb_debtor_result.column12 IS NULL THEN ''
        ELSE imr_tb_debtor_result.column12 
    END AS column12,
    CASE 
        WHEN imr_tb_debtor_result.column13 = 0.00 OR imr_tb_debtor_result.column13 IS NULL 
        THEN '' ELSE imr_tb_debtor_result.column13 
    END AS column13,
    CASE 
        WHEN imr_tb_debtor_result.column14 = 0.00 OR imr_tb_debtor_result.column14 IS NULL 
        THEN '' ELSE imr_tb_debtor_result.column14
    END AS column14,
    CASE 
        WHEN imr_tb_debtor_result.column15 = 0.00 OR imr_tb_debtor_result.column15 IS NULL 
        THEN '' ELSE imr_tb_debtor_result.column15 
    END AS column15,
    imr_tb_debtor_result.column16,
    imr_tb_debtor_result.dupdate,
    -- เพิ่ม column10last ที่ได้จาก month = '10-2024'
    column10_last_query.column10last AS column10last
FROM (
    SELECT 
        accountcode,
        MAX(accountname) AS accountname,
        SUM(debitlast) AS total_debitlast,
        SUM(cchnlast) AS total_cchnlast,
        SUM(debit) AS total_debit,
        SUM(update_count) AS total_update_count,
        '$month' AS monthtxt
    FROM (
        SELECT 
            accountcode, 
            MAX(accountname) AS accountname, 
            SUM(IF(monthtxt = '$monthlast', debit, 0)) AS debitlast,
            COUNT(CASE WHEN monthtxt = '$month' THEN hn ELSE NULL END) AS cchnlast, 
            SUM(IF(monthtxt = '$month', debit, 0)) AS debit,
            SUM(IF(monthtxt = '$month' AND rcpno LIKE 'uploads/%', 1, 0)) AS update_count
        FROM imr_tb_debtor_rights_opd
        GROUP BY accountcode

        UNION ALL

        SELECT 
            $ipd_sub_acc AS accountcode, 
            $ipd_sub_name AS accountname, 
            SUM(IF(monthtxt = '$monthlast', debit, 0)) AS debitlast,
            COUNT(CASE WHEN monthtxt = '$month' THEN hn ELSE NULL END) AS cchnlast, 
            SUM(IF(monthtxt = '$month', debit, 0)) AS debit,
            SUM(IF(monthtxt = '$month' AND rcpno LIKE 'uploads/%', 1, 0)) AS update_count
        FROM imr_tb_debtor_rights_ipd
        GROUP BY $ipd_sub_acc
    ) AS subquery
    GROUP BY accountcode
) AS main_query
-- JOIN หลัก
LEFT OUTER JOIN imr_tb_debtor_result 
    ON main_query.accountcode = imr_tb_debtor_result.code 
    AND imr_tb_debtor_result.month = main_query.monthtxt
-- JOIN ใหม่สำหรับ column10last
LEFT OUTER JOIN (
    SELECT 
        code, 
        column12 AS column10last 
    FROM imr_tb_debtor_result
    WHERE month = '$monthlast'
) AS column10_last_query
    ON main_query.accountcode = column10_last_query.code
GROUP BY 
    main_query.accountcode, 
    imr_tb_debtor_result.column6,
    imr_tb_debtor_result.column7,
    imr_tb_debtor_result.column8,
    imr_tb_debtor_result.column9,
    imr_tb_debtor_result.column10,
    imr_tb_debtor_result.column11,
    imr_tb_debtor_result.column12,
    imr_tb_debtor_result.column13,
    imr_tb_debtor_result.column14,    
    imr_tb_debtor_result.column15,
    imr_tb_debtor_result.column16,
    imr_tb_debtor_result.dupdate,
    column10_last_query.column10last";



    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {

      $adebitlast = 0;$acchnlast = 0;$adebit = 0; $acolumn6 =0; $acolumn7 =0; $acolumn8 =0; $acolumn9 =0; $acolumn10 =0; $acolumn11 =0; $acolumn12 =0;$acolumn10last =0;

      
      while ($row = mysqli_fetch_assoc($result)) {
        $acc = $row["accountcode"];

        // 🌟 ปรับยอด CR Cross-Account Splitting (OPD)
        if ($cr_opd) {
            if ($acc === '1102050101.216') {
                $row["total_debit"] = $cr_opd['cr_non_kidney_received_total'] + $cr_opd['cr_kidney_received_total'];
                $row["total_cchnlast"] = $cr_opd['cr_non_kidney_count'] + $cr_opd['cr_kidney_count'];
            } elseif (isset($cr_opd['transfers_by_account'][$acc])) {
                $tr = $cr_opd['transfers_by_account'][$acc];
                $row["total_debit"] = max(0, floatval($row["total_debit"]) - $tr['transfer_out']);
                $row["total_cchnlast"] = max(0, intval($row["total_cchnlast"]) - $tr['full_transfer_cases']);
            }
        }

        // 🌟 ปรับยอด CR Cross-Account Splitting (IPD)
        if ($cr_ipd) {
            if ($acc === '1102050101.217') {
                $row["total_debit"] = $cr_ipd['cr_received_total'] ?? 0;
                $row["total_cchnlast"] = count($cr_ipd['cr_visits'] ?? []);
            } elseif (isset($cr_ipd['transfers_by_account'][$acc])) {
                $tr = $cr_ipd['transfers_by_account'][$acc];
                $row["total_debit"] = max(0, floatval($row["total_debit"]) - $tr['transfer_out']);
                $row["total_cchnlast"] = max(0, intval($row["total_cchnlast"]) - ($tr['full_transfer_cases'] ?? 0));
            }
        }

        // 🌟 ปรับยอด SSS Cross-Account Splitting (OPD)
        if ($sss_opd) {
            if ($acc === '1102050101.309') {
                $row["total_debit"] = floatval($row["total_debit"]) + $sss_opd['sss_received_total'];
                $row["total_cchnlast"] = $sss_opd['sss_count'];
            } elseif (isset($sss_opd['transfers_by_account'][$acc])) {
                $tr_sss = $sss_opd['transfers_by_account'][$acc];
                $row["total_debit"] = max(0, floatval($row["total_debit"]) - $tr_sss['transfer_out']);
                $row["total_cchnlast"] = max(0, intval($row["total_cchnlast"]) - $tr_sss['full_transfer_cases']);
            }
        }

        // 🌟 ปรับยอด SSS Cross-Account Splitting (IPD)
        if ($sss_ipd) {
            if ($acc === '1102050101.310') {
                $row["total_debit"] = floatval($row["total_debit"]) + ($sss_ipd['sss_received_total'] ?? 0);
                $row["total_cchnlast"] = count($sss_ipd['sss_visits'] ?? []);
            } elseif (isset($sss_ipd['transfers_by_account'][$acc])) {
                $tr_sss = $sss_ipd['transfers_by_account'][$acc];
                $row["total_debit"] = max(0, floatval($row["total_debit"]) - $tr_sss['transfer_out']);
                $row["total_cchnlast"] = max(0, intval($row["total_cchnlast"]) - ($tr_sss['full_transfer_cases'] ?? 0));
            }
        }

        // คำนวณส่วนต่าง Column 7 ใหม่ (Column 5 - Column 6)
        $col6_val = !empty($row["column6"]) ? floatval(str_replace(',', '', $row["column6"])) : 0.0;
        $diff_col7 = floatval($row["total_debit"]) - $col6_val;
        $row["column7"] = (abs($diff_col7) >= 0.01) ? number_format($diff_col7, 2) : '';

        $adebitlast = $adebitlast+$row["total_debitlast"];
        $acchnlast = $acchnlast+$row["total_cchnlast"];
        $adebit = $adebit+$row["total_debit"];


        if (is_numeric($row["column6"])) {
          $acolumn6 += $row["column6"];
        };
        $valueColumn7 = preg_replace('/[^0-9.-]/', '', $row["column7"]);
        if (is_numeric($valueColumn7)) {
          $acolumn7 += $valueColumn7;
        }
        if (is_numeric($row["column8"])) {
          $acolumn8 += $row["column8"];
        };
        if (is_numeric($row["column9"])) {
          $acolumn9 += $row["column9"];
        };

        if (is_numeric($row["column10"])) {
          $acolumn10 += $row["column10"];
        };
        $valueColumn11 = preg_replace('/[^0-9.-]/', '', $row["column11"]);
        if (is_numeric($valueColumn11)) {
          $acolumn11 += $valueColumn11;
        }

        $valueColumn12 = preg_replace('/[^0-9.-]/', '', $row["column12"]);
        if (is_numeric($valueColumn12)) {
          $acolumn12 += $valueColumn12;
        }

        $valueColumn10last = preg_replace('/[^0-9.-]/', '', $row["column10last"]);
        if (is_numeric($valueColumn10last)) {
          $acolumn10last += $valueColumn10last;
        }


            $role = $_SESSION['role'];

            // ตรวจสอบวันที่ตัดสิทธิ์ (วันที่ 10 ของเดือนถัดไป)
            list($m, $y) = explode('-', $month);
            $cutoff_date = date('Y-m-d', strtotime("$y-$m-01 +1 month +9 days")); // วันที่ 10
            $today = date('Y-m-d');
            $isLocked = ($today > $cutoff_date);

            // ✨ เงื่อนไขใหม่: ถ้า admin => ไม่ล็อกแม้เลยวันที่
            $isAdmin = ($role === 'admin');

            // column6 และ column10: accounting, admin
            if ((in_array($role, ['accounting']) && !$isLocked) || $isAdmin) {
                $contenteditable_col6 = 'contenteditable="true" class="editable column6 numeric"';
                $contenteditable_col10 = 'contenteditable="true" class="editable column10 numeric"';
            } else {
                $contenteditable_col6 = 'contenteditable="false" class="editable column6 numericcolor locked-cell"';
                $contenteditable_col10 = 'contenteditable="false" class="editable column10 numericcolor locked-cell"';
            }

            // column8 และ column9: finance, admin
            if ((in_array($role, ['finance']) && !$isLocked) || $isAdmin) {
                $contenteditable_col8 = 'contenteditable="true" class="editable column8 numeric"';
                $contenteditable_col9 = 'contenteditable="true" class="editable column9 numeric"';
            } else {
                $contenteditable_col8 = 'contenteditable="false" class="editable column8 numericcolor locked-cell"';
                $contenteditable_col9 = 'contenteditable="false" class="editable column9 numericcolor locked-cell"';
            }

if($row["column10last"]<>'0'){

    
          echo '<tr data-code="'.$row["accountcode"].'" data-month="'.$month.'">
          <td style="text-align: center;">' . $row["accountcode"] . '</td>
          <td style="text-align: left; color: #1e293b; font-weight: normal;" class="recon-accname-cell">' . $row["accountname"] . '</td>
          <td style="text-align: right;" class="column3"><a  href="debtor_excel.php?acc=' . $row["accountcode"] . '&month='.$monthlast.'" >' . $row["column10last"] . '</a></td>
          <td style="text-align: right;" class="numericcolor">' . number_format($row["total_cchnlast"], 0) . '</td>
          <td style="text-align: right;" class="numericcolor column5">' . number_format($row["total_debit"], 2) . '</td>

          <td style="text-align: right;background: #fff3d7;" '.$contenteditable_col6.' >' . number_format(!empty($row["column6"]) ? $row["column6"] : 0, 2) . '</td> 
          <td style="text-align: right;" class="numericcolor column7">' . $row["column7"] . '</td>
          <td style="text-align: right;background: #f2ffd7;" '.$contenteditable_col8.' >' . number_format(!empty($row["column8"]) ? $row["column8"] : 0, 2) . '</td>
          <td style="text-align: right;background: #f2ffd7;" '.$contenteditable_col9.' >' . number_format(!empty($row["column9"]) ? $row["column9"] : 0, 2) . '</td>

          <td style="text-align: right;background: #d7ffda;" '.$contenteditable_col10.' >' . number_format(!empty($row["column10"]) ? $row["column10"] : 0, 2) . '</td>
          <td style="text-align: right;background: #d7ffda;" class="numericcolor column11">' . $row["column11"] . '</td>

          <td style="text-align: right;" class="numericcolor column12" data-code="' . $row["accountcode"] . '"><a  href="debtor_excel.php?acc=' . $row["accountcode"] . '&month='.$month.'" >' . $row["column12"] . '</a></td>
          <td style="text-align: right;" contenteditable="true" class="editable numeric column13">' . $row["column13"] . '</td>
          <td style="text-align: right;" contenteditable="true" class="editable numeric column14">' . $row["column14"] . '</td>
          <td style="text-align: right;" contenteditable="true" class="editable numeric column15">' . $row["column15"] . '</td>
          <td style="text-align: right;" contenteditable="true" class="editable">' . $row["column16"] . '</td>
          <td style="text-align: center;">' . ($row["total_update_count"] > 0 ? '<button type="button" class="btn btn-sm btn-info" onclick="viewUpdates(\''.$row["accountcode"].'\', \''.$month.'\')"><i class="bx bx-search"></i> ดูรายการ</button>' : '-') . '</td>
          </tr>';


}else{

          echo '<tr data-code="'.$row["accountcode"].'" data-month="'.$month.'">
          <td style="text-align: center;">' . $row["accountcode"] . '</td>
          <td style="text-align: left; color: #1e293b; font-weight: normal;" class="recon-accname-cell">' . $row["accountname"] . '</td>
          <td style="text-align: right;color: #0004a1;" class="column3">' . $row["column10last"] . '</td>
          <td style="text-align: right;" class="numericcolor">' . number_format($row["total_cchnlast"], 0) . '</td>
          <td style="text-align: right;" class="numericcolor column5">' . number_format($row["total_debit"], 2) . '</td>

          <td style="text-align: right;background: #fff3d7;" '.$contenteditable_col6.' >' . number_format(!empty($row["column6"]) ? $row["column6"] : 0, 2) . '</td>
          <td style="text-align: right;" class="numericcolor column7">' . $row["column7"] . '</td>
          <td style="text-align: right;background: #f2ffd7;" '.$contenteditable_col8.' >' . number_format(!empty($row["column8"]) ? $row["column8"] : 0, 2) . '</td>
          <td style="text-align: right;background: #f2ffd7;" '.$contenteditable_col9.' >' . number_format(!empty($row["column9"]) ? $row["column9"] : 0, 2) . '</td>

          <td style="text-align: right;background: #d7ffda;" '.$contenteditable_col10.' >' . number_format(!empty($row["column10"]) ? $row["column10"] : 0, 2) . '</td>
          <td style="text-align: right;background: #d7ffda;" class="numericcolor column11">' . $row["column11"] . '</td>

          <td style="text-align: right;color: #0004a1;" class="numericcolor column12" data-code="' . $row["accountcode"] . '"><a  href="debtor_excel.php?acc=' . $row["accountcode"] . '&month='.$month.'" >' . $row["column12"] . '</a></td>
          <td style="text-align: right;" contenteditable="true" class="editable numeric column13">' . $row["column13"] . '</td>
          <td style="text-align: right;" contenteditable="true" class="editable numeric column14">' . $row["column14"] . '</td>
          <td style="text-align: right;" contenteditable="true" class="editable numeric column15">' . $row["column15"] . '</td>
          <td style="text-align: right;" contenteditable="true" class="editable">' . $row["column16"] . '</td>
          <td style="text-align: center;">' . ($row["total_update_count"] > 0 ? '<button type="button" class="btn btn-sm btn-info" onclick="viewUpdates(\''.$row["accountcode"].'\', \''.$month.'\')"><i class="bx bx-search"></i> ดูรายการ</button>' : '-') . '</td>
          </tr>';


}







      }
          echo '<tr>
          <td colspan="2" style="text-align: center;background: #f4f4f4;height: 34px;">รวมทั้งหมด</td>
          <td style="text-align: right;background: #f4f4f4;color: #0004a1;" id="avartxt1">'.number_format($acolumn10last,2).'</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt2">'.number_format($acchnlast,0).'</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt3">'.number_format($adebit,2).'</td>
          <td style="text-align: right;background: #fff3d7;" id="avartxt6">'.number_format($acolumn6,2).'</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt7">'.number_format($acolumn7,2).'</td>
          <td style="text-align: right;background: #f2ffd7;" id="avartxt8">'.number_format($acolumn8,2).'</td>
          <td style="text-align: right;background: #f2ffd7;" id="avartxt9">'.number_format($acolumn9,2).'</td>
          <td style="text-align: right;background: #d7ffda;" id="avartxt10">'.number_format($acolumn10,2).'</td>
          <td style="text-align: right;background: #d7ffda;" id="avartxt11">'.number_format($acolumn11,2).'</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt12">'.number_format($acolumn12,2).'</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt13">-</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt14">-</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt15">-</td>
          <td style="text-align: right;background: #f4f4f4;">-</td>
          <td style="text-align: right;background: #f4f4f4;">-</td>
          </tr>';

    } else {
      echo '<tr><td colspan="16" style="text-align: center;">No results</td></tr>';
    }

    echo'<tr class="hoverable-row">
    <td colspan="14" style="text-align: left;font-weight: bold;border: none;height: 43px; vertical-align: bottom;"> รายงานสรุปลูกหนี้สิทธิรายงานสรุปลูกหนี้สิทธิ</td>
    </tr>
    <tr class="hoverable-row">
    <td colspan="2" style="text-align: left;">1. ลูกหนี้สิทธิยกมาเดือน <span id="vtxt5"></span></td>
    <td colspan="1" style="text-align: right; " id="vartxt1">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row">
    <td colspan="2" style="text-align: left;">2. จำนวนลูกหนี้/ราย เดือน <span id="vtxt6"></span></td>
    <td colspan="1" style="text-align: right; " id="vartxt2">-</td>
    <td colspan="1" style="text-align: right;"> ราย</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row">
    <td colspan="2" style="text-align: left;">3. ลูกหนี้สิทธิ กลุ่มงานประกันสุขภาพ/บาท</td>
    <td colspan="1" style="text-align: right; " id="vartxt3">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row">
    <td colspan="2" style="text-align: left;">4. งบทดลอง กลุ่มงานบัญชี/บาท</td>
    <td colspan="1" style="text-align: right; " id="vartxt4">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row">
    <td colspan="2" style="text-align: left;">5. ยอดส่วนต่าง</td>
    <td colspan="1" style="text-align: right; " id="vartxt5">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row">
    <td colspan="2" style="text-align: left;">6. ตัดลูกหนี้/บาท</td>
    <td colspan="1" style="text-align: right; " id="vartxt6">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row">
    <td colspan="2" style="text-align: left;">7. เงินโอนในเดือนนี้</td>
    <td colspan="1" style="text-align: right; " id="vartxt7">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row">
    <td colspan="2" style="text-align: left;">8. ลูกหนี้สิทธิยกไป</td>
    <td colspan="1" style="text-align: right; " id="vartxt8">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>';

    $sql_sig = "SELECT * FROM summary_responsibles ORDER BY id DESC LIMIT 1";
    $res_sig = $conn->query($sql_sig);
    $data_sig = $res_sig ? $res_sig->fetch_assoc() : [];

    $s1 = !empty($data_sig['name1']) ? '('.$data_sig['name1'].')' : '';
    $p1 = !empty($data_sig['position1']) ? 'ตำแหน่ง '.$data_sig['position1'] : '';
    $s2 = !empty($data_sig['name2']) ? '('.$data_sig['name2'].')' : '';
    $p2 = !empty($data_sig['position2']) ? 'ตำแหน่ง '.$data_sig['position2'] : '';
    $s3 = !empty($data_sig['name3']) ? '('.$data_sig['name3'].')' : '';
    $p3 = !empty($data_sig['position3']) ? 'ตำแหน่ง '.$data_sig['position3'] : '';
    $s4 = !empty($data_sig['name4']) ? '('.$data_sig['name4'].')' : '';
    $p4 = !empty($data_sig['position4']) ? 'ตำแหน่ง '.$data_sig['position4'] : '';
    $s5 = !empty($data_sig['name5']) ? '('.$data_sig['name5'].')' : '';
    $p5 = !empty($data_sig['position5']) ? 'ตำแหน่ง '.$data_sig['position5'] : 'ตำแหน่ง ผู้อำนวยการโรงพยาบาล';

    $echo_sig = '

    <tr class="hoverable-row">
    <td colspan="14" style="bold;border: none;height: 135px; "></td>
    </tr>

    <tr class="hoverable-row" style="vertical-align: baseline;">
    <td colspan="2" style="text-align: center;border: none;font-size: 14px;">
    <p style="margin: 2px;" id="s11txt">'.$s1.'</p>
    <p style="margin: 2px;" id="p11txt">'.$p1.'</p> ผู้จัดทำรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ</td>
    <td colspan="4" style="text-align: center;border: none;font-size: 14px;">
    <p style="margin: 2px;" id="s22txt">'.$s2.'</p>
    <p style="margin: 2px;" id="p22txt">'.$p2.'</p>ผู้ตรวจสอบรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ</td>
    <td colspan="4" style="text-align: center;border: none;font-size: 14px;">                                    
    <p style="margin: 2px;" id="s33txt">'.$s3.'</p>
    <p style="margin: 2px;" id="p33txt">'.$p3.'</p>ผู้บันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี</td>
    <td colspan="4" style="text-align: center;border: none;font-size: 14px;">
    <p style="margin: 2px;" id="s44txt">'.$s4.'</p>
    <p style="margin: 2px;" id="p44txt">'.$p4.'</p>ผู้ตรวจสอบการบันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี</td>
    </tr>

    <tr class="hoverable-row">
    <td colspan="14" style="bold;border: none;height: 85px; "></td>
    </tr>

    <tr class="hoverable-row">
    <td colspan="10" style="border: none;"></td>
    <td colspan="4" style="text-align: center;border: none;font-size: 14px;">
    <p style="margin: 2px;" id="s55txt">'.$s5.'</p>
    <p style="margin: 2px;" id="p55txt">'.$p5.'</p>ผู้อำนวยการโรงพยาบาล<br>หัวหน้าหน่วยงาน</td>
    </tr>

    ';

    echo $echo_sig;

}




if (isset($_POST['records'])) {
    $records = json_decode($_POST['records'], true);

    foreach ($records as $record) {
        // ป้องกัน SQL Injection
        $code = isset($record['code']) ? $conn->real_escape_string($record['code']) : '';
        $month = isset($record['month']) ? $conn->real_escape_string($record['month']) : '';

        // แปลงค่าตัวเลขให้ถูกต้อง
        $column6 = isset($record['column6']) ? floatval($record['column6']) : 0;
        $column7 = isset($record['column7']) ? $conn->real_escape_string($record['column7']) : '';
        $column8 = isset($record['column8']) ? floatval($record['column8']) : 0;
        $column9 = isset($record['column9']) ? floatval($record['column9']) : 0;
        $column10 = isset($record['column10']) ? floatval($record['column10']) : 0;
        $column11 = isset($record['column11']) ? $conn->real_escape_string($record['column11']) : '';
        $column12 = isset($record['column12']) ? $conn->real_escape_string($record['column12']) : '';
        $column13 = isset($record['column13']) ? floatval($record['column13']) : 0;
        $column14 = isset($record['column14']) ? floatval($record['column14']) : 0;
        $column15 = isset($record['column15']) ? floatval($record['column15']) : 0;
        $column16 = isset($record['column16']) ? $conn->real_escape_string($record['column16']) : '';

        $dupdate = date("Y-m-d");




        // เตรียม SQL Insert
        $sql = "INSERT INTO imr_tb_debtor_result 
                (code, month, column6, column7, column8, column9, column10, column11, column12, column13, column14, column15, column16, dupdate) 
                VALUES ('$code', '$month', 
                " . ($column6 === 0 ? 'NULL' : "'$column6'") . ",
                " . (empty($column7) ? 'NULL' : "'$column7'") . ",
                " . ($column8 === 0 ? 'NULL' : "'$column8'") . ",
                " . ($column9 === 0 ? 'NULL' : "'$column9'") . ",
                " . ($column10 === 0 ? 'NULL' : "'$column10'") . ",
                " . (empty($column11) ? 'NULL' : "'$column11'") . ",
                " . (empty($column12) ? 'NULL' : "'$column12'") . ",
                " . ($column13 === 0 ? 'NULL' : "'$column13'") . ",
                " . ($column14 === 0 ? 'NULL' : "'$column14'") . ",
                " . ($column15 === 0 ? 'NULL' : "'$column15'") . ",
                " . (empty($column16) ? 'NULL' : "'$column16'") . ",
                '$dupdate')
                ON DUPLICATE KEY UPDATE
                column6 = VALUES(column6),
                column7 = VALUES(column7),
                column8 = VALUES(column8),
                column9 = VALUES(column9),
                column10 = VALUES(column10),
                column11 = VALUES(column11),
                column12 = VALUES(column12),
                column13 = VALUES(column13),                
                column14 = VALUES(column14),
                column15 = VALUES(column15),
                column16 = VALUES(column16)";


        if (!$conn->query($sql)) {
            echo "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }

    echo "บันทึกข้อมูลเรียบร้อยแล้ว";
}


mysqli_close($conn);
?>









?>
