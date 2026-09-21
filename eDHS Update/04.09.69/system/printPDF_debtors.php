<?php
// printPDF_debtors.php

// 1. เปิดแสดง Error ชั่วคราว (เพื่อหาสาเหตุถ้ายังพัง)
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: text/html; charset=utf-8');

// 2. ระบบเรียกไฟล์ (Auto Path)
$autoloadPath = '';
if (file_exists(__DIR__ . '/vendor_debtors/autoload.php')) {
    $autoloadPath = __DIR__ . '/vendor_debtors/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor_debtors/autoload.php')) {
    $autoloadPath = __DIR__ . '/../vendor_debtors/autoload.php';
} else {
    die("Error: ไม่พบโฟลเดอร์ vendor (mPDF) กรุณาตรวจสอบว่าได้ติดตั้ง mPDF หรือยัง");
}
require_once $autoloadPath;

// ระบบเรียก Config
if (file_exists(__DIR__ . '/database_config/config.php')) {
    require_once __DIR__ . '/database_config/config.php';
} elseif (file_exists(__DIR__ . '/../database_config/config.php')) {
    require_once __DIR__ . '/../database_config/config.php';
} else {
    // path สำรอง
    @include './database_config/config.php';
}

// 3. รับค่า Parameters
$mode = $_REQUEST['mode'] ?? 'letter';
$name = $_REQUEST['name'] ?? '';
$totalpay = $_REQUEST['totalpay'] ?? '0';
$date_serv = $_REQUEST['date_serv'] ?? date('Y-m-d');
$hn = $_REQUEST['hn'] ?? '';
$pttype_name = $_REQUEST['pttype_name'] ?? '';
$run_number = $_REQUEST['run_number'] ?? '..........';
$doc_date = $_REQUEST['doc_date'] ?? date('Y-m-d');

// รับค่า Setting (ค่าเริ่มต้น)
$headerPrefix = $_REQUEST['header_prefix'] ?? 'ที่ รอ.๐๐๓๓.๓๐๔ /';
$directorName = $_REQUEST['director_name'] ?? 'นายเกรียงไกร ศรีวิลัย';
$directorPosition = $_REQUEST['director_position'] ?? 'ผู้อำนวยการโรงพยาบาลโพนทราย';
// ค่าเริ่มต้นที่อยู่ (ใช้ \n เพื่อให้ nl2br ทำงาน)
$defaultAddr = "โรงพยาบาลโพนทราย\n๑๐๔ หมู่ ๙ ตำบลโพนทราย\nอำเภอโพนทราย\nจังหวัดร้อยเอ็ด ๔๕๒๕๐";
$hospitalAddr = $_REQUEST['hospital_addr'] ?? $defaultAddr;
$subject = $_REQUEST['subject'] ?? "ขอให้ชำระค่ารักษาพยาบาล";
$template_type = $_REQUEST['template_type'] ?? 'default';
$body2 = $_REQUEST['body2'] ?? "โรงพยาบาลโพนทราย จึงขอแจ้งให้ท่านนำเงินจำนวนดังกล่าว...";
$body3 = $_REQUEST['body3'] ?? "จึงเรียนมาเพื่อโปรดทราบ...";
$footerContact = $_REQUEST['footer_contact'] ?? "ฝ่ายบริหารงานทั่วไป...";

// แปลง Newline เป็น <br>
$directorPosition = nl2br($directorPosition);
$hospitalAddr = nl2br($hospitalAddr);
$body2 = nl2br($body2);
$body3 = nl2br($body3);
$footerContact = nl2br($footerContact);

// 4. จัดการรูปภาพ (Safe Mode)

$localImgPath = __DIR__ . '/images/kk.png'; // ตรวจสอบว่าไฟล์นี้มีอยู่จริงไหม
$imgTag = '';

if (file_exists($localImgPath)) {
    $imgTag = '<img src="' . $localImgPath . '" />';
} else {
    $imgTag = ''; // ปล่อยว่างไปเลยสวยกว่า
}

// 5. ฟังก์ชันช่วยเหลือ
function convertAmountToLetter($number) {
    if (empty($number)) return "";
    $number = strval($number);
    $txtnum1 = array('ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า', 'สิบ');
    $txtnum2 = array('', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน');
    $number = str_replace(",", "", $number);
    $number = str_replace(" ", "", $number);
    $number = str_replace("บาท", "", $number);
    $number = explode(".", $number);
    if (sizeof($number) > 2) { return ''; }
    $strlen = strlen($number[0]);
    $convert = '';
    for ($i = 0; $i < $strlen; $i++) {
        $n = substr($number[0], $i, 1);
        if ($n != 0) {
            if ($i == ($strlen-1) && $n == 1) { $convert .= 'เอ็ด'; }
            elseif ($i == ($strlen - 2) && $n == 2) { $convert .= 'ยี่'; }
            elseif ($i == ($strlen - 2) && $n == 1) { $convert .= ''; }
            else { $convert .= $txtnum1[$n]; }
            $convert .= $txtnum2[$strlen - $i - 1];
        }
    }
    $convert .= 'บาท';
    if (sizeof($number) == 1) {
        $convert .= 'ถ้วน';
    } else {
        if ($number[1] == '0' || $number[1] == '00' || $number[1] == '') {
            $convert .= 'ถ้วน';
        } else {
            $number[1] = substr($number[1], 0, 2);
            $strlen = strlen($number[1]);
            for ($i = 0; $i < $strlen; $i++) {
                $n = substr($number[1], $i, 1);
                if ($n != 0) {
                    if ($i > 0 && $n == 1 ) { $convert.= 'เอ็ด'; }
                    elseif ($i == 0 && $n == 2) { $convert .= 'ยี่'; }
                    elseif ($i == 0 && $n == 1) { $convert .= ''; }
                    else { $convert .= $txtnum1[$n]; }
                    $convert .= $i==0 ? $txtnum2[1] : '';
                }
            }
            $convert .= 'สตางค์';
        }
    }
    return $convert;
}

function thainumDigit($num){
    return str_replace(array( '0' , '1' , '2' , '3' , '4' , '5' , '6' ,'7' , '8' , '9' ),
    array( "๐" , "๑" , "๒" , "๓" , "๔" , "๕" , "๖" , "๗" , "๘" , "๙" ), $num);
}



// [แก้ไขใหม่] ฟังก์ชันแปลงวันที่แบบฉลาด (รองรับทั้ง ค.ศ. และ พ.ศ. / และ -)
function DateThai($strDate) {
    if(!$strDate || $strDate == '0000-00-00') return "-";
    
    $day = 0; $month = 0; $year = 0;

    // กรณีที่ 1: มาเป็นแบบมีเครื่องหมาย / (เช่น 08/04/2561)
    if(strpos($strDate, '/') !== false) {
        $parts = explode('/', $strDate);
        if(count($parts) >= 3) {
            $day = (int)$parts[0];
            $month = (int)$parts[1];
            $year = (int)$parts[2];
        }
    } 
    // กรณีที่ 2: มาเป็นแบบมาตรฐาน (เช่น 2018-04-08)
    elseif (strpos($strDate, '-') !== false) {
        $timestamp = strtotime($strDate);
        if($timestamp !== false) {
            $day = (int)date('j', $timestamp);
            $month = (int)date('n', $timestamp);
            $year = (int)date('Y', $timestamp);
        }
    }

    // ถ้าแปลงไม่ได้จริงๆ ให้คืนค่าเดิมกลับไป
    if ($day == 0) return $strDate; 

    // เช็คปี: ถ้าปีน้อยกว่า 2400 แสดงว่าเป็น ค.ศ. ให้บวก 543
    // แต่ถ้าปีมากกว่า 2400 (เช่น 2561) แสดงว่าเป็น พ.ศ. แล้ว ไม่ต้องบวกเพิ่ม
    if ($year < 2400) {
        $year += 543;
    }
    
    $strMonthCut = Array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
    $strMonthThai = isset($strMonthCut[$month]) ? $strMonthCut[$month] : "-";
    
    return "$day $strMonthThai $year";
}

$DateThai = DateThai($doc_date); 
$DateServThai = DateThai($date_serv); // ใช้วันที่รับบริการที่ผ่านฟังก์ชันใหม่นี้


// --- 6. สร้าง PDF (ใส่ Try-Catch ป้องกันหน้าขาว) ---
try {
    // Config mPDF
    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];
    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $fontPath = __DIR__ . '/font';
    if(!is_dir($fontPath)) $fontPath = __DIR__ . '/../font';

    // ตั้งค่าหน้ากระดาษเป็นแนวตั้ง (Portrait) ขนาด 110x220
    $orientation = 'P';
    $marginLeft = ($mode == 'envelope') ? 0 : 30;
    $marginRight = ($mode == 'envelope') ? 0 : 20;
    $marginTop = ($mode == 'envelope') ? 0 : 15; 
    $marginBottom = ($mode == 'envelope') ? 0 : 20;

    $mpdf = new \Mpdf\Mpdf([
        'fontDir' => array_merge($fontDirs, [ $fontPath ]),
        'fontdata' => $fontData + [
            'sarabun' => [
                'R' => 'THSarabunNew.ttf',
                'I' => 'THSarabunNew Italic.ttf',
                'B' => 'THSarabunNew Bold.ttf',
                'BI' => 'THSarabunNew BoldItalic.ttf',
            ]
        ],
        'default_font' => 'sarabun',
        'default_font_size' => 16,
        'format' => 'A4',
        'orientation' => $orientation,     'margin_left' => $marginLeft, 
        'margin_right' => $marginRight, 
        'margin_top' => $marginTop, 
        'margin_bottom' => $marginBottom
    ]);

    // CSS
    $style = '
    <style>
        body { font-family: "sarabun"; line-height: 1.2; }
        .garuda { text-align: center; margin-bottom: 0px; }
        .garuda img { height: 30mm; } 
        .header-table { width: 100%; border-collapse: collapse; margin-top: -30px; }
        .header-left { text-align: left; vertical-align: top; width: 50%; padding-top: 20px; }
        .header-right { 
            text-align: left; /* เปลี่ยนจาก right เป็น left เพื่อให้ตัวหนังสือชิดซ้าย */
            vertical-align: top; 
            width: 50%; 
            padding-top: 20px; 
            line-height: 1.1; 
            padding-left: 23%; /* ดันก้อนข้อความไปทางขวา (ปรับเลขนี้ได้ถ้าอยากให้ขยับอีก) */
        }
        .content { margin-top: 15px; text-align: left; line-height: 1.4; }
        .indent { text-indent: 2.5cm; }
        .footer-contact {
            position: absolute;
            bottom: 120px;
            left: 0;
            width: 100%;
            font-size: 16pt;
            padding-left: 2.5cm; 
            box-sizing: border-box;
        }
        /* ซองจดหมาย (ขนาด 110x220 วางกึ่งกลาง A4) */
        .envelope-container { 
            position: absolute;
            /* 1. เลื่อน "ทั้งซอง" ขึ้นหรือลง บนหน้ากระดาษ A4 
               - ถ้ารู้สึกว่าเครื่องพิมพ์ดึงกระดาษแล้วพิมพ์ต่ำเกินไป ให้ลดเลข 55mm ลง (เช่น 40mm, 30mm)
               - ถ้ายกขึ้นจนชิดขอบแล้วยังกว้างไป ให้ไปปรับข้อ 2. */
            top: -2mm;
            
            left: 10mm; /* ขยับจากซ้ายมา 50mm (105-110 = -5) เพื่อให้อยู่กึ่งกลาง A4 พอดี */
            width: 220mm;
            height: 110mm;
            rotate: 90;
            
            /* 2. ระยะขอบ "ภายในซอง" (หมุน 90 องศา ขอบซ้ายจึงกลายเป็นหัวกระดาษ)
               - ถ้าต้องการให้ตัวหนังสือชิดขอบซองด้านบนมากขึ้น ให้ลดเลข 15mm ลง (เช่น 5mm, 0mm) */
            padding-top: 10mm;
            padding-bottom: 10mm;
            padding-left: 15mm; 
            padding-right: 10mm;
            
            box-sizing: border-box;
        }
    </style>
    ';

    if ($mode == 'letter') {
        // --- เนื้อหาจดหมาย ---
        $content = $style . '
        <div class="garuda">'.$imgTag.'</div>
        <table class="header-table">
            <tr>
                <td class="header-left">'.thainumDigit($headerPrefix).' '.thainumDigit($run_number).'</td>
                <td class="header-right">'.$hospitalAddr.'</td>
            </tr>
        </table>
        <div style="margin-top: 10px; padding-left:310px;">'.thainumDigit($DateThai).'</div>
        <table style="width: 100%; margin-top: 15px; border-collapse: collapse; line-height: 1.4;">
            <tr>
                <td style="width: 50px; vertical-align: top;"><b>เรื่อง</b></td>
                <td style="vertical-align: top;">'.$subject.'</td>
            </tr>
            <tr>
                <td style="width: 50px; vertical-align: top;"><b>เรียน</b></td>
                <td style="vertical-align: top;">'.$name.' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; HN &nbsp;&nbsp;'.thainumDigit($hn).'</td>
            </tr>
        </table>
        <div class="content">';

            $bodyMain = $_REQUEST['body_main'] ?? '';
            // แทนที่ตัวแปรแบบอัตโนมัติ
            $bodyMain = str_replace('[ชื่อผู้ป่วย]', $name, $bodyMain);
            // $hospital จะถูกดึงมาจาก config.php ของระบบ eDHS 
            global $hospital; 
            $bodyMain = str_replace('[ชื่อโรงพยาบาล]', $hospital, $bodyMain);
            $bodyMain = str_replace('[วันที่รับบริการ]', thainumDigit($DateServThai), $bodyMain);
            $bodyMain = str_replace('[ยอดเงิน]', thainumDigit(number_format($totalpay, 2)), $bodyMain);
            $bodyMain = str_replace('[ตัวอักษรยอดเงิน]', convertAmountToLetter(number_format($totalpay, 2)), $bodyMain);

            // จัดการบรรทัดใหม่ (เคาะ Enter)
            $paragraphs = explode("\n", $bodyMain);
            foreach ($paragraphs as $p) {
                $p = rtrim($p);
                if ($p !== '') {
                    // เปลี่ยน Space นำหน้าให้เป็น &nbsp; เพื่อให้ mPDF ดันข้อความตามที่เคาะเว้นวรรคมา
                    $p = preg_replace_callback('/^[ \t]+/', function($m) {
                        return str_replace([' ', "\t"], ['&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;'], $m[0]);
                    }, $p);
                    $content .= '<div style="margin-bottom: 8px;">'.$p.'</div>';
                }
            }

            // ถ้าเป็นรูปแบบเดิม ยังคงแสดงย่อหน้า 2 (ถ้ามีกรอกไว้)
            if ($template_type == 'default' && !empty($body2)) {
                $body2_lines = explode("\n", strip_tags($body2));
                $body2_html = '';
                foreach ($body2_lines as $l) {
                    $l = rtrim($l);
                    if ($l !== '') {
                        $l = preg_replace_callback('/^[ \t]+/', function($m) {
                            return str_replace([' ', "\t"], ['&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;'], $m[0]);
                        }, $l);
                        $body2_html .= '<div>'.$l.'</div>';
                    }
                }
                $content .= '<div style="margin-top: 10px;">'.$body2_html.'</div>';
            }

            $body3_lines = explode("\n", strip_tags($body3));
            $body3_html = '';
            foreach ($body3_lines as $l) {
                $l = rtrim($l);
                if ($l !== '') {
                    $l = preg_replace_callback('/^[ \t]+/', function($m) {
                        return str_replace([' ', "\t"], ['&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;'], $m[0]);
                    }, $l);
                    $body3_html .= '<div>'.$l.'</div>';
                }
            }

            $content .= '
            <div style="margin-top: 10px;">'.$body3_html.'</div>
        </div>
        <table style="width: 100%; margin-top: 50px;">
            <tr>
                <td width="30%"></td>
                <td width="70%" align="center">
                    ขอแสดงความนับถือ<br><br><br><br>
                    ('.$directorName.')<br>
                    '.$directorPosition.'
                </td>
            </tr>
        </table>
        
        <div class="footer-contact">'.$footerContact.'</div>
        ';
        $mpdf->WriteHTML($content);

    } else {
        // --- เนื้อหาซองจดหมาย ---
        $address = ""; 
        $po_code = "";
        
        $env_name = $_REQUEST['env_name'] ?? '';
        $env_addr = $_REQUEST['env_addr'] ?? '';
        $receiver_name = !empty($env_name) ? $env_name : $name;

        if (!empty($env_addr)) {
            $address = nl2br($env_addr);
        } else if (!empty($hn) && isset($conn2)) {
            try {
                $sql = "SELECT informaddr, po_code FROM patient WHERE hn = '$hn' LIMIT 1"; 
                $result = $conn2->query($sql);
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $address = $row['informaddr'];
                    $po_code = "ไปรษณีย์  " . $row['po_code'];
                }
            } catch (Exception $e) { /* เงียบไว้ถ้าต่อ DB ไม่ติด */ }
        }

        /* 3. ขนาดของ "ตราครุฑ" ที่เว้นว่างไว้ 
           - ถ้ารู้สึกว่ามันเว้นที่ว่างด้านบนข้อความ (ที่ รอ...) เยอะเกินไป 
           - ให้ลดตัวเลข height ลง เช่น height: 10mm; หรือ 0mm; */
        $envImgTag = '<div style="height: 20mm; width: 20mm;"></div>';

        $docNo = thainumDigit($headerPrefix).' '.thainumDigit($run_number);

        $content = $style . '
        <div class="envelope-container">
            <table style="width: 100%; border-collapse: collapse; border: 0;">
                <tr>
                    <!-- 4. ลดช่องว่างตรงกลาง (กรอบแดง) ระหว่างผู้ส่งและผู้รับ 
                         - ให้ลดเลข width: 50% ด้านล่างนี้ให้เล็กลง เช่น 35% หรือ 40% 
                         - เพื่อดึงข้อความ "กรุณาส่ง..." ให้ขยับเข้าใกล้ฝั่งซ้ายมากขึ้น -->
                    <td style="width: 30%; vertical-align: top; text-align: left;">
                        <div style="margin-bottom: 5px;">'.$envImgTag.'</div>
                        <div style="font-size: 14pt; margin-bottom: 5px;">'.$docNo.'</div>
                        <div style="font-size: 14pt; line-height: 1.2;">'.$hospitalAddr.'</div>
                    </td>
                    <td style="width: 70%; vertical-align: bottom; padding-top: 25mm; padding-left: 10mm;">
                        <div style="font-size: 20pt; font-weight: bold; line-height: 1.4;">
                            กรุณาส่ง<br>
                            '.$receiver_name.'<br>
                            <span style="font-size: 16pt; font-weight: normal;">
                            '.$address.'<br>'.$po_code.'
                            </span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        ';
        $mpdf->WriteHTML($content);
    }

    $mpdf->Output();

} catch (\Mpdf\MpdfException $e) {
    // ถ้า mPDF พัง ให้แสดง Error ชัดๆ
    echo "<h3>เกิดข้อผิดพลาดในการสร้าง PDF:</h3>";
    echo $e->getMessage();
} catch (Exception $e) {
    echo "<h3>เกิดข้อผิดพลาดทั่วไป:</h3>";
    echo $e->getMessage();
}
?>