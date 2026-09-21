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



    $sql = "SELECT 
    main_query.accountcode,
    MAX(main_query.accountname) AS accountname,
    SUM(main_query.total_debitlast) AS total_debitlast,
    SUM(main_query.total_cchnlast) AS total_cchnlast,
    SUM(main_query.total_debit) AS total_debit,
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
        '$month' AS monthtxt
    FROM (
        SELECT 
            accountcode, 
            MAX(accountname) AS accountname, 
            SUM(IF(monthtxt = '$monthlast', debit, 0)) AS debitlast,
            COUNT(CASE WHEN monthtxt = '$month' THEN hn ELSE NULL END) AS cchnlast, 
            SUM(IF(monthtxt = '$month', debit, 0)) AS debit
        FROM imr_tb_debtor_rights_opd
        GROUP BY accountcode

        UNION ALL

        SELECT 
            accountcode, 
            MAX(accountname) AS accountname, 
            SUM(IF(monthtxt = '$monthlast', debit, 0)) AS debitlast,
            COUNT(CASE WHEN monthtxt = '$month' THEN hn ELSE NULL END) AS cchnlast, 
            SUM(IF(monthtxt = '$month', debit, 0)) AS debit
        FROM imr_tb_debtor_rights_ipd
        GROUP BY accountcode
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

            $acolumn122 = floatval(str_replace(",", "", $acolumn12));

            if ($acolumn122 > 0) {
                $contenteditable_col12 = 'contenteditable="false" class="editable column12 numericcolor locked-cell"';
            } elseif ((in_array($role, ['finance']) && !$isLocked) || $isAdmin) {
                $contenteditable_col12 = 'contenteditable="true" class="editable column12 numeric"';
            } else {
                $contenteditable_col12 = 'contenteditable="false" class="editable column12 numericcolor locked-cell"';
            }



          echo '<tr data-code="'.$row["accountcode"].'" data-month="'.$month.'">
          <td style="text-align: center;" class="numericcolor">' . $row["accountcode"] . '</td>
          <td style="text-align: left;" class="numericcolor">' . $row["accountname"] . '</td>
          <td style="text-align: right;" class="numericcolor">' . number_format($row["total_cchnlast"], 0) . '</td>
          <td style="text-align: right;" class="numericcolor column5">' . number_format($row["total_debit"], 2) . '</td>
          <td style="text-align: right;background: #f2ffd7;" '.$contenteditable_col12.' >' . $row["column12"] . '</td>
          </tr>';
      }
          echo '<tr>
          <td colspan="2" style="text-align: center;background: #f4f4f4;height: 34px;">รวมทั้งหมด</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt2">'.number_format($acchnlast,0).'</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt3">'.number_format($adebit,2).'</td>
          <td style="text-align: right;background: #f4f4f4;" id="avartxt122">'.number_format($acolumn12,2).'</td>

          </tr>';

    } else {
      echo '<tr><td colspan="5" style="text-align: center;">No results</td></tr>';
    }



}




mysqli_close($conn);
?>









?>
