<?php
header('Content-Type: text/html; charset=utf-8');
// เช็ค Path ให้ดีนะครับพี่
require './database_config/config.php';
if (function_exists('system_log')) {
    $pageName = basename($_SERVER['PHP_SELF']);
    system_log($conn, 'เปิดดูข้อมูล (View)', 'VIEW', "ผู้ใช้เปิดดูหน้ารายงาน/ข้อมูล: " . $pageName);
} 

// ฟังก์ชั่นแปลงวันที่ (คงเดิมไว้)
function DateThai($strDate)
{
    $strYear = date("Y", strtotime($strDate)) + 543;
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    $strMonthCut = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
    $strMonthThai = $strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear";
}

$strDate = date("Y/m/d");
$yyy = date("Y") + 543;

// ตรวจสอบว่ามีค่าส่งมาหรือไม่
if (isset($_POST['month']) && isset($_POST['monthlast'])) {

    $month = $_POST['month'];         // เดือนปัจจุบันที่เลือก (เช่น 8-2025)
    $monthlast = $_POST['monthlast']; // เดือนก่อนหน้า (ใช้สำหรับคำนวณยอด ยกมา)

    // คำนวณเดือนย้อนหลัง 4 เดือน (เพื่อหา session variable ตาม logic เดิมพี่)
    list($m, $y) = explode('-', $month);
    $months = [];
    for ($i = 4; $i >= 0; $i--) {
        $mm = $m - $i;
        $yy = $y;
        if ($mm <= 0) {
            $mm += 12;
            $yy--;
        }
        $months[] = $mm . '-' . $yy;
    }
    // $months[3] = 4 เดือนที่แล้ว, $months[2] = 3 เดือนที่แล้ว, ... $months[0] = เดือนปัจจุบัน (ไม่ใช่สิ array พี่เรียง 0-4)
    // logic เดิมพี่:
    // Query 1 ใช้ $months[3] (เดือน 8-4 = 4)
    // Query 2 ใช้ $months[2]
    // Query 3 ใช้ $months[1]
    // Query 4 (Main) ใช้ $month (หรือ $months[4])

    $_SESSION['dmonth'] = $month;
    $_SESSION['dmonthlast'] = $monthlast;

    // เตรียม SQL Template (ใช้ ? แทนค่าตัวแปร เพื่อความปลอดภัย)
    // สังเกตว่าผมรวม SQL ที่ซ้ำๆ กันมาไว้ในตัวแปรเดียวครับ
    $sql_template = "SELECT 
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
        column10_last_query.column10last AS column10last
        FROM (
            SELECT 
                accountcode,
                MAX(accountname) AS accountname,
                SUM(debitlast) AS total_debitlast,
                SUM(cchnlast) AS total_cchnlast,
                SUM(debit) AS total_debit,
                ? AS monthtxt  -- Parameter 1: Month Target
            FROM (
                SELECT 
                    accountcode, 
                    MAX(accountname) AS accountname, 
                    SUM(IF(monthtxt = ?, debit, 0)) AS debitlast, -- Parameter 2: Month Last
                    COUNT(CASE WHEN monthtxt = ? THEN hn ELSE NULL END) AS cchnlast, -- Parameter 3: Month Target
                    SUM(IF(monthtxt = ?, debit, 0)) AS debit -- Parameter 4: Month Target
                FROM imr_tb_debtor_rights_opd
                GROUP BY accountcode

                UNION ALL

                SELECT 
                    accountcode, 
                    MAX(accountname) AS accountname, 
                    SUM(IF(monthtxt = ?, debit, 0)) AS debitlast, -- Parameter 5: Month Last
                    COUNT(CASE WHEN monthtxt = ? THEN hn ELSE NULL END) AS cchnlast, -- Parameter 6: Month Target
                    SUM(IF(monthtxt = ?, debit, 0)) AS debit -- Parameter 7: Month Target
                FROM imr_tb_debtor_rights_ipd
                GROUP BY accountcode
            ) AS subquery
            GROUP BY accountcode
        ) AS main_query
        LEFT OUTER JOIN imr_tb_debtor_result 
            ON main_query.accountcode = imr_tb_debtor_result.code 
            AND imr_tb_debtor_result.month = main_query.monthtxt
        LEFT OUTER JOIN (
            SELECT 
                code, 
                column12 AS column10last 
            FROM imr_tb_debtor_result
            WHERE month = ? -- Parameter 8: Month Last
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

    // ฟังก์ชั่นช่วยดึงข้อมูลเพื่อลดโค้ดซ้ำ
    function getDataByMonth($conn, $sql_template, $target_month, $last_month) {
        $stmt = mysqli_prepare($conn, $sql_template);
        if ($stmt === false) {
            die('MySQL prepare error: ' . mysqli_error($conn));
        }
        // Bind Parameter: s=string (เรียงตามเครื่องหมาย ? ใน SQL)
        mysqli_stmt_bind_param($stmt, "ssssssss", 
            $target_month, // 1
            $last_month,   // 2
            $target_month, // 3
            $target_month, // 4
            $last_month,   // 5
            $target_month, // 6
            $target_month, // 7
            $last_month    // 8
        );
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $data = [];
        $sums = [
            'col8' => 0, 'col9' => 0, 'col12' => 0, 
            'debitlast' => 0, 'cchnlast' => 0, 'debit' => 0,
            'col6' => 0, 'col7' => 0, 'col10' => 0, 'col11' => 0, 'col10last' => 0
        ];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
            
            // Logic การบวกเลข (ทำเหมือนเดิมเป๊ะ)
            $v8 = preg_replace('/[^0-9.-]/', '', $row["column8"]);
            if (is_numeric($v8)) $sums['col8'] += $v8;

            $v9 = preg_replace('/[^0-9.-]/', '', $row["column9"]);
            if (is_numeric($v9)) $sums['col9'] += $v9;

            $v12 = preg_replace('/[^0-9.-]/', '', $row["column12"]);
            if (is_numeric($v12)) $sums['col12'] += $v12;
            
            // บวกค่าอื่นๆ สำหรับรอบ Main Query
            $sums['debitlast'] += $row["total_debitlast"];
            $sums['cchnlast'] += $row["total_cchnlast"];
            $sums['debit'] += $row["total_debit"];
            
            if (is_numeric($row["column6"])) $sums['col6'] += $row["column6"];
            
            $v7 = preg_replace('/[^0-9.-]/', '', $row["column7"]);
            if (is_numeric($v7)) $sums['col7'] += $v7;

            if (is_numeric($row["column10"])) $sums['col10'] += $row["column10"];
            
            $v11 = preg_replace('/[^0-9.-]/', '', $row["column11"]);
            if (is_numeric($v11)) $sums['col11'] += $v11;

            $v10l = preg_replace('/[^0-9.-]/', '', $row["column10last"]);
            if (is_numeric($v10l)) $sums['col10last'] += $v10l;
        }
        mysqli_stmt_close($stmt);
        return ['rows' => $data, 'sums' => $sums];
    }

    // --- เริ่มประมวลผล ---

    // 1. ดึงข้อมูลย้อนหลัง (เหมือน $sql1, $sql2, $sql3 เดิม)
    // $months[3] = SQL1
    $res1 = getDataByMonth($conn, $sql_template, $months[3], $monthlast);
    $_SESSION['adpavartxt10'] = number_format($res1['sums']['col12'], 2);
    $_SESSION['adpavartxt6'] = number_format($res1['sums']['col8'], 2);
    $_SESSION['adpavartxt7'] = number_format($res1['sums']['col9'], 2);

    // $months[2] = SQL2
    $res2 = getDataByMonth($conn, $sql_template, $months[2], $monthlast);
    $_SESSION['bdpavartxt10'] = number_format($res2['sums']['col12'], 2);
    $_SESSION['bdpavartxt6'] = number_format($res2['sums']['col8'], 2);
    $_SESSION['bdpavartxt7'] = number_format($res2['sums']['col9'], 2);

    // $months[1] = SQL3
    $res3 = getDataByMonth($conn, $sql_template, $months[1], $monthlast);
    $_SESSION['cdpavartxt10'] = number_format($res3['sums']['col12'], 2);
    $_SESSION['cdpavartxt6'] = number_format($res3['sums']['col8'], 2);
    $_SESSION['cdpavartxt7'] = number_format($res3['sums']['col9'], 2);

    // $months[0] = SQL4 (ไม่ได้ใช้แสดงผล แต่โค้ดเดิมรันไว้เพื่อเก็บ session)
    $res4 = getDataByMonth($conn, $sql_template, $months[0], $monthlast); // น่าจะเป็น array index 0 ตาม logic loop
    $_SESSION['ddpavartxt10'] = number_format($res4['sums']['col12'], 2);
    $_SESSION['ddpavartxt6'] = number_format($res4['sums']['col8'], 2);
    $_SESSION['ddpavartxt7'] = number_format($res4['sums']['col9'], 2);


    // 2. ดึงข้อมูลเดือนปัจจุบัน (Main Query ที่จะเอามาแสดงผล)
    // ใช้ตัวแปร $month ที่รับมาจาก POST
    $mainResult = getDataByMonth($conn, $sql_template, $month, $monthlast);
    $rows = $mainResult['rows'];
    $sums = $mainResult['sums'];

    // แสดงผลตาราง (HTML)
    if (count($rows) > 0) {
        foreach ($rows as $row) {
            echo '<tr data-code="' . $row["accountcode"] . '" data-month="' . $month . '">
            <td style="text-align: left;">' . $row["accountname"] . '</td>
            <td style="text-align: right;" class=" column3">' . $row["column10last"] . '</td>
            <td style="text-align: right;" class="column5">' . number_format($row["total_debit"], 2) . '</td>
            <td style="text-align: right;" class="editable column6">' . number_format(!empty($row["column6"]) ? $row["column6"] : 0, 2) . '</td>
            <td style="text-align: right;" class="column7">' . $row["column7"] . '</td>
            <td style="text-align: right;" class="editable column8">' . number_format(!empty($row["column8"]) ? $row["column8"] : 0, 2) . '</td>
            <td style="text-align: right;" class="editable column9">' . number_format(!empty($row["column9"]) ? $row["column9"] : 0, 2) . '</td>

            <td style="text-align: right;" class="editable column10">' . number_format(!empty($row["column10"]) ? $row["column10"] : 0, 2) . '</td>
            <td style="text-align: right;" class="column11">' . $row["column11"] . '</td>

            <td style="text-align: right;" class="column12">' . $row["column12"] . '</td>
            <td style="text-align: right;" class="editable column13">' . $row["column13"] . '</td>
            <td style="text-align: right;" class="editable column14">' . $row["column14"] . '</td>
            <td style="text-align: right;" class="editable column15">' . $row["column15"] . '</td>
            </tr>';
        }

        // ส่วนสรุปยอดท้ายตาราง
        echo '<tr>
          <td colspan="1" style="text-align: center;height: 34px;">รวมทั้งหมด</td>
          <td style="text-align: right;" id="pavartxt1">' . number_format($sums['col10last'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt3">' . number_format($sums['debit'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt6">' . number_format($sums['col6'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt7">' . number_format($sums['col7'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt8">' . number_format($sums['col8'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt9">' . number_format($sums['col9'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt10">' . number_format($sums['col10'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt11">' . number_format($sums['col11'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt12">' . number_format($sums['col12'], 2) . '</td>
          <td style="text-align: right;" id="pavartxt13">-</td>
          <td style="text-align: right;" id="pavartxt14">-</td>
          <td style="text-align: right;" id="pavartxt15">-</td>
          </tr>';

        // บันทึก Session สำหรับเดือนปัจจุบัน
        $_SESSION['dpavartxt1'] = number_format($sums['col10last'], 2);
        $_SESSION['dpavartxt2'] = number_format($sums['cchnlast'], 0);
        $_SESSION['dpavartxt3'] = number_format($sums['debit'], 2);
        $_SESSION['dpavartxt4'] = number_format($sums['col6'], 2);
        $_SESSION['dpavartxt5'] = number_format($sums['col7'], 2);
        $_SESSION['dpavartxt6'] = number_format($sums['col8'], 2);
        $_SESSION['dpavartxt7'] = number_format($sums['col9'], 2);
        $_SESSION['dpavartxt8'] = number_format($sums['col10'], 2);
        $_SESSION['dpavartxt9'] = number_format($sums['col11'], 2);
        $_SESSION['dpavartxt10'] = number_format($sums['col12'], 2);

    } else {
        echo '<tr><td colspan="12" style="text-align: center;">No results</td></tr>';
    }

    // --- ส่วนรายงานสรุปด้านล่าง ---
    // เพิ่ม class="avoid-break" ไว้ให้ด้วยครับ เพื่อให้ตอนปริ้น PDF ไม่โดนตัด
    echo '<tr class="hoverable-row avoid-break">
    <td colspan="12" style="text-align: left;font-weight: bold;border: none;height: 43px; vertical-align: bottom;"> รายงานสรุปลูกหนี้สิทธิรายงานสรุปลูกหนี้สิทธิ</td>
    </tr>
    <tr class="hoverable-row avoid-break">
    <td colspan="2" style="text-align: left;">1. ลูกหนี้สิทธิยกมาเดือน <span id="vtxt5"></span></td>
    <td colspan="1" style="text-align: right; " id="pvartxt1">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>

    <tr class="hoverable-row avoid-break">
    <td colspan="2" style="text-align: left;">2. ลูกหนี้สิทธิ กลุ่มงานประกันสุขภาพ/บาท</td>
    <td colspan="1" style="text-align: right; " id="pvartxt3">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row avoid-break">
    <td colspan="2" style="text-align: left;">3. งบทดลอง กลุ่มงานบัญชี/บาท</td>
    <td colspan="1" style="text-align: right; " id="pvartxt4">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row avoid-break">
    <td colspan="2" style="text-align: left;">4. ยอดส่วนต่าง</td>
    <td colspan="1" style="text-align: right; " id="pvartxt5">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row avoid-break">
    <td colspan="2" style="text-align: left;">5. ตัดลูกหนี้/บาท</td>
    <td colspan="1" style="text-align: right; " id="pvartxt6">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row avoid-break">
    <td colspan="2" style="text-align: left;">6. เงินโอนในเดือนนี้</td>
    <td colspan="1" style="text-align: right; " id="pvartxt7">-</td>
    <td colspan="1" style="text-align: right;"> บาท</td>
    <td colspan="10" style="border: none; "></td>
    </tr>
    <tr class="hoverable-row avoid-break">
    <td colspan="2" style="text-align: left;">7. ลูกหนี้สิทธิยกไป</td>
    <td colspan="1" style="text-align: right; " id="pvartxt8">-</td>
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

    <tr class="hoverable-row avoid-break">
    <td colspan="12" style="bold;border: none;height: 65px; "></td>
    </tr>

    <tr class="hoverable-row avoid-break" style="vertical-align: baseline;">
    <td colspan="2" style="text-align: center;border: none;font-size: 14px;">
    <p style="margin: 2px;" id="ps11txt">'.$s1.'</p>
    <p style="margin: 2px;" id="pp11txt">'.$p1.'</p> ผู้จัดทำรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ</td>
    <td colspan="4" style="text-align: center;border: none;font-size: 14px;">
    <p style="margin: 2px;" id="ps22txt">'.$s2.'</p>
    <p style="margin: 2px;" id="pp22txt">'.$p2.'</p>ผู้ตรวจสอบรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ</td>
    <td colspan="4" style="text-align: center;border: none;font-size: 14px;">                                    
    <p style="margin: 2px;" id="ps33txt">'.$s3.'</p>
    <p style="margin: 2px;" id="pp33txt">'.$p3.'</p>ผู้บันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี</td>
    <td colspan="4" style="text-align: center;border: none;font-size: 14px;">
    <p style="margin: 2px;" id="ps44txt">'.$s4.'</p>
    <p style="margin: 2px;" id="pp44txt">'.$p4.'</p>ผู้ตรวจสอบการบันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี</td>
    </tr>

    <tr class="hoverable-row avoid-break">
    <td colspan="12" style="bold;border: none;height: 55px; "></td>
    </tr>

    <tr class="hoverable-row avoid-break">
    <td colspan="10" style="border: none;"></td>
    <td colspan="4" style="text-align: center;border: none;font-size: 14px;">
    <p style="margin: 2px;" id="ps55txt">'.$s5.'</p>
    <p style="margin: 2px;" id="pp55txt">'.$p5.'</p>ผู้อำนวยการโรงพยาบาล<br>หัวหน้าหน่วยงาน</td>
    </tr>';

    echo $echo_sig;

}

mysqli_close($conn);
?>