<?php
require '../../system/database_config/config.php';
require_once __DIR__ . '/../../system/database_config/db_helper.php';
require_once __DIR__ . '/../../system/includes/cr_migration_helper.php';
require_once __DIR__ . '/../../system/includes/sss_migration_helper.php';
require_once('tcpdf_include.php');

// ==========================================================================================
// Function Helper ต่างๆ
// ==========================================================================================

if (!function_exists('DateThai')) {
    function DateThai($strDate)
    {
        if($strDate == "") return "-";
        $parts = explode('/', $strDate);
        if(count($parts) != 3) return "-";
        $day = (int)$parts[0];
        $month = (int)$parts[1];
        $year = (int)$parts[2];
        if ($year < 2400) { $year += 543; }
        $strMonthCut = Array("","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค.");
        $strMonthThai = isset($strMonthCut[$month]) ? $strMonthCut[$month] : "-";
        return "$day $strMonthThai $year";
    }
}

if (!function_exists('dateThaiLong')) {
    function dateThaiLong($strDate, $style = 0) {
        $strYear = date("Y", strtotime($strDate)) + 543;
        $strMonth = date("n", strtotime($strDate));
        $strDay = date("d", strtotime($strDate));
        $strMonthCut = Array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฏาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
        $strMonthThai = $strMonthCut[$strMonth];
        if ($style == 0) {
            return "$strMonthThai $strYear";
        } else {
            return "$strMonthThai พ.ศ.$strYear";
        }
    }
}

if (!function_exists('thai_date_short')) {
    function thai_date_short($time){   
        $d = substr($time,0,2);
        $m = substr($time,3,2);
        if($m == '01'){ $mtxt = 'ม.ค.'; }
        elseif ($m == '02') { $mtxt = 'ก.พ.'; }
        elseif ($m == '03') { $mtxt = 'มี.ค.'; }
        elseif ($m == '04') { $mtxt = 'เม.ย.'; }
        elseif ($m == '05') { $mtxt = 'พ.ค.'; }
        elseif ($m == '06') { $mtxt = 'มิ.ย.'; }
        elseif ($m == '07') { $mtxt = 'ก.ค.'; }
        elseif ($m == '08') { $mtxt = 'ส.ค.'; }
        elseif ($m == '09') { $mtxt = 'ก.ย.'; }
        elseif ($m == '10') { $mtxt = 'ต.ค.'; }
        elseif ($m == '11') { $mtxt = 'พ.ย.'; }
        elseif ($m == '12') { $mtxt = 'ธ.ค.'; }
        $y = substr($time,6,4);
        return $d.' '.$mtxt.' '.$y; 
    }
}

if (!function_exists('num2wordsThai')) {
    function num2wordsThai($num) {
        $num = str_replace(",", "", $num);
        $num_decimal = explode(".", $num);
        $num = $num_decimal[0];
        $returnNumWord = '';
        $lenNumber = strlen($num);
        $lenNumber2 = $lenNumber - 1;
        $kaGroup = array("", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน");
        $kaDigit = array("", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ต", "แปด", "เก้า");
        $kaDigitDecimal = array("ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ต", "แปด", "เก้า");
        $ii = 0;
        for ($i = $lenNumber2; $i >= 0; $i--) {
            $kaNumWord[$i] = substr($num, $ii, 1);
            $ii++;
        }
        $ii = 0;
        for ($i = $lenNumber2; $i >= 0; $i--) {
            if (($kaNumWord[$i] == 2 && $i == 1) || ($kaNumWord[$i] == 2 && $i == 7)) {
                $kaDigit[$kaNumWord[$i]] = "ยี่";
            } else {
                if ($kaNumWord[$i] == 2) {
                    $kaDigit[$kaNumWord[$i]] = "สอง";
                }
                if (($kaNumWord[$i] == 1 && $i <= 2 && $i == 0) || ($kaNumWord[$i] == 1 && $lenNumber > 6 && $i == 6)) {
                    if ($kaNumWord[$i + 1] == 0) {
                        $kaDigit[$kaNumWord[$i]] = "หนึ่ง";
                    } else {
                        $kaDigit[$kaNumWord[$i]] = "เอ็ด";
                    }
                } elseif (($kaNumWord[$i] == 1 && $i <= 2 && $i == 1) || ($kaNumWord[$i] == 1 && $lenNumber > 6 && $i == 7)) {
                    $kaDigit[$kaNumWord[$i]] = "";
                } else {
                    if ($kaNumWord[$i] == 1) {
                        $kaDigit[$kaNumWord[$i]] = "หนึ่ง";
                    }
                }
            }
            if ($kaNumWord[$i] == 0) {
                if ($i != 6) {
                    $kaGroup[$i] = "";
                }
            }
            $kaNumWord[$i] = substr($num, $ii, 1);
            $ii++;
            $returnNumWord.=$kaDigit[$kaNumWord[$i]] . $kaGroup[$i];
        }
        if (isset($num_decimal[1])) {
            $returnNumWord.="จุด";
            for ($i = 0; $i < strlen($num_decimal[1]); $i++) {
                $returnNumWord.=$kaDigitDecimal[substr($num_decimal[1], $i, 1)];
            }
        }
        return $returnNumWord;
    }
}

// ==========================================================================================
// ส่วนที่ 1 : เตรียมตัวแปรและสร้างเงื่อนไข SQL (Logic หัวใจหลัก)
// ==========================================================================================

// รับค่าโหมด (ถ้าไม่ส่งมา ตีต่างว่าเป็น month เพื่อรองรับลิงก์เก่า)
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'month'; 
$show_sig = isset($_GET['show_sig']) ? $_GET['show_sig'] : 1;
$whereSQL = "";
$headerText = "";


if ($mode == 'range') {
    // --- โหมดช่วงวันที่ ---
    $startInput = $_GET['start'] ?? ''; // รับค่ามา (อาจเป็น 15/11/2025 หรือ 15/11/2568)
    $endInput = $_GET['end'] ?? '';

    if(empty($startInput) || empty($endInput)){
        echo "กรุณาระบุวันที่ให้ครบถ้วน";
        die();
    }
    
    // ฟังก์ชันช่วยแปลงวันที่ให้เป็นรูปแบบ พ.ศ. (YYYYMMDD) เพื่อใช้เทียบกับ Text ใน DB
    if (!function_exists('convertToThaiYearCompare')) {
        function convertToThaiYearCompare($dateInput) {
            list($d, $m, $y) = explode('/', $dateInput);
            if ((int)$y < 2400) {
                $y = (int)$y + 543;
            }
            return $y . $m . $d;
        }
    }

    // แปลงวันที่ทั้งเริ่มต้นและสิ้นสุด
    $start_compare = convertToThaiYearCompare($startInput);
    $end_compare = convertToThaiYearCompare($endInput);

    // SQL Condition (ใช้สูตรเดิมแต่เทียบกับค่าที่แปลงเป็น พ.ศ. แล้ว)
    // หมายเหตุ: OPD ใช้ vstdate, IPD ใช้ dchdate 
    // (Script นี้จะเช็คให้อัตโนมัติว่าถ้าเป็นไฟล์ไหนให้ใช้ column นั้น)
    
    $colDate = 'vstdate'; // ค่า Default ของ OPD
    // ถ้าไฟล์นี้คือ reporti.php (IPD) ให้เปลี่ยนเป็น dchdate
    if(strpos($_SERVER['PHP_SELF'], 'reporti.php') !== false){
        $colDate = 'dchdate';
    }

    $whereSQL = "
    CONCAT(
        SUBSTRING_INDEX($colDate, '/', -1), 
        SUBSTRING_INDEX(SUBSTRING_INDEX($colDate, '/', 2), '/', -1), 
        LPAD(SUBSTRING_INDEX($colDate, '/', 1), 2, '0')
    ) BETWEEN '$start_compare' AND '$end_compare'
    ";
    
    // ข้อความหัวรายงาน
    // (ใช้ $startInput เดิมแสดงผล เพราะฟังก์ชัน DateThai จะแปลงเป็น พ.ศ. ให้อีกทีตอนแสดง)
    $headerText = "ระหว่างวันที่ ".DateThai($startInput)." ถึง ".DateThai($endInput);

} else {
    // --- โหมดรายเดือน (Logic เดิม) ---
    $stmgetovlookup = isset($_GET['datepost']) ? $_GET['datepost'] : '';
    
    if($stmgetovlookup == "0-0000" || empty($stmgetovlookup)){
       echo "ไม่มีข้อมูลลูกหนี้";
       die();
    }

    // แปลงค่าเดือน
    $parts = explode('-', $stmgetovlookup);
    $strDate = $parts[1] . '-' . $parts[0]; // Y-m
    
    // Condition SQL
    $whereSQL = "monthtxt = '$stmgetovlookup'";
    
    // ข้อความหัวรายงาน
    $headerText = "ประจำเดือน ".dateThaiLong($strDate, $style = 0);
}

// เพิ่มเงื่อนไขสิทธิ
$rights = isset($_GET['rights']) ? $_GET['rights'] : '';
$rights_arr = [];
if ($rights === 'none') {
    $whereSQL .= " AND 1=0 ";
} elseif (!empty($rights) && $rights !== 'all') {
    $rights_arr = explode(',', $rights);
    $rights_quoted = [];
    foreach ($rights_arr as $r) {
        $rights_quoted[] = "'" . mysqli_real_escape_string($conn, $r) . "'";
    }
    if(count($rights_quoted) > 0) {
        $rights_in = implode(',', $rights_quoted);
        $whereSQL .= " AND accountcode IN ($rights_in) ";
    }
}

$is_rights_filtered = (!empty($rights) && $rights !== 'all');
$is_216_allowed = (!$is_rights_filtered || in_array('1102050101.216', $rights_arr) || in_array('1102050101.201', $rights_arr) || in_array('1102050101.209', $rights_arr) || in_array('1102050101.203', $rights_arr));
$is_309_allowed = (!$is_rights_filtered || in_array('1102050101.309', $rights_arr) || in_array('1102050101.301', $rights_arr) || in_array('1102050101.303', $rights_arr) || in_array('1102050101.307', $rights_arr));

if (!function_exists('getMonthsFromThaiDates')) {
    function getMonthsFromThaiDates($startStr, $endStr) {
        $p1 = explode('/', $startStr);
        $p2 = explode('/', $endStr);
        if (count($p1) == 3 && count($p2) == 3) {
            $y1 = (int)$p1[2]; if ($y1 > 2400) $y1 -= 543;
            $y2 = (int)$p2[2]; if ($y2 > 2400) $y2 -= 543;
            $ts_curr = strtotime(sprintf('%04d-%02d-01', $y1, (int)$p1[1]));
            $ts_end = strtotime(sprintf('%04d-%02d-01', $y2, (int)$p2[1]));
            $months = [];
            while ($ts_curr <= $ts_end) {
                $months[] = intval(date('n', $ts_curr)) . '-' . date('Y', $ts_curr);
                $ts_curr = strtotime('+1 month', $ts_curr);
            }
            return $months;
        }
        return [];
    }
}

$months_to_process = [];
if ($mode == 'range') {
    $months_to_process = getMonthsFromThaiDates($startInput, $endInput);
} elseif (!empty($stmgetovlookup)) {
    $months_to_process = [$stmgetovlookup];
}

// โหลดการตั้งค่า CR Cross-Account Splitting
$cr_config = get_active_cr_config($conn);
$auto_split_opd = is_cr_auto_split_enabled($cr_config, 'OPD');
$my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : '';

$cr_summary = null;
$inactive_cr_months = [];
$has_any_cr = false;
$cr_summary_accum = [
    'is_effective' => false,
    'cr_received_total' => 0.0,
    'cr_non_kidney_received_total' => 0.0,
    'cr_non_kidney_count' => 0,
    'cr_kidney_count' => 0,
    'cr_kidney_received_total' => 0.0,
    'transfers_by_account' => [],
    'cr_visits' => []
];

foreach ($months_to_process as $m_txt) {
    if (is_cr_effective_for_month($cr_config, $m_txt) && $auto_split_opd) {
        $s = get_cr_splitting_summary_for_month($conn, $conn2, $m_txt, 'OPD', $cr_config, $my_hospcode);
        if ($s) {
            $has_any_cr = true;
            $cr_summary_accum['is_effective'] = true;
            $cr_summary_accum['cr_received_total'] += $s['cr_received_total'];
            $cr_summary_accum['cr_non_kidney_received_total'] += $s['cr_non_kidney_received_total'];
            $cr_summary_accum['cr_non_kidney_count'] += $s['cr_non_kidney_count'];
            $cr_summary_accum['cr_kidney_count'] += $s['cr_kidney_count'];
            $cr_summary_accum['cr_kidney_received_total'] += $s['cr_kidney_received_total'];
            foreach ($s['cr_visits'] as $k => $v) {
                $cr_summary_accum['cr_visits'][$k] = $v;
            }
            foreach ($s['transfers_by_account'] as $acc => $tr) {
                if (!isset($cr_summary_accum['transfers_by_account'][$acc])) {
                    $cr_summary_accum['transfers_by_account'][$acc] = [
                        'original_debit' => 0.0,
                        'transfer_out' => 0.0,
                        'remain_debit' => 0.0,
                        'cases' => 0,
                        'cr_cases' => 0,
                        'full_transfer_cases' => 0,
                        'partial_transfer_cases' => 0
                    ];
                }
                $cr_summary_accum['transfers_by_account'][$acc]['original_debit'] += $tr['original_debit'];
                $cr_summary_accum['transfers_by_account'][$acc]['transfer_out'] += $tr['transfer_out'];
                $cr_summary_accum['transfers_by_account'][$acc]['remain_debit'] += $tr['remain_debit'];
                $cr_summary_accum['transfers_by_account'][$acc]['cases'] += $tr['cases'];
                $cr_summary_accum['transfers_by_account'][$acc]['cr_cases'] += $tr['cr_cases'];
                $cr_summary_accum['transfers_by_account'][$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
                $cr_summary_accum['transfers_by_account'][$acc]['partial_transfer_cases'] += $tr['partial_transfer_cases'];
            }
        }
    } else {
        $inactive_cr_months[] = $m_txt;
    }
}

if (!empty($inactive_cr_months) && $has_any_cr) {
    $in_m_str = "'" . implode("','", array_map(function($m) use ($conn) { return mysqli_real_escape_string($conn, $m); }, $inactive_cr_months)) . "'";
    $q_inact = mysqli_query($conn, "SELECT COUNT(vn) as c, COALESCE(SUM(debit), 0) as d 
                                     FROM imr_tb_debtor_rights_opd 
                                     WHERE accountcode = '1102050101.216' 
                                     AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') 
                                     AND monthtxt IN ($in_m_str) AND ($whereSQL)");
    if ($q_inact && $r_inact = mysqli_fetch_assoc($q_inact)) {
        $cr_summary_accum['cr_non_kidney_count'] += intval($r_inact['c']);
        $cr_summary_accum['cr_non_kidney_received_total'] += floatval($r_inact['d']);
        $cr_summary_accum['cr_received_total'] += floatval($r_inact['d']);
    }

    $q_inact_k = mysqli_query($conn, "SELECT COUNT(vn) as c, COALESCE(SUM(debit), 0) as d 
                                       FROM imr_tb_debtor_rights_opd 
                                       WHERE accountcode = '1102050101.216' 
                                       AND (pttypename LIKE '%ฟอกไต%' OR pttypename LIKE '%ไต%') 
                                       AND monthtxt IN ($in_m_str) AND ($whereSQL)");
    if ($q_inact_k && $r_inact_k = mysqli_fetch_assoc($q_inact_k)) {
        $cr_summary_accum['cr_kidney_count'] += intval($r_inact_k['c']);
        $cr_summary_accum['cr_kidney_received_total'] += floatval($r_inact_k['d']);
    }
}

if ($has_any_cr) {
    $cr_summary = $cr_summary_accum;
}

// โหลดการตั้งค่า SSS Instrument Cross-Account Splitting
$sss_config = get_active_sss_config($conn);
$auto_split_sss_opd = is_sss_auto_split_enabled($sss_config, 'OPD');

$sss_summary = null;
$inactive_sss_months = [];
$has_any_sss = false;
$sss_summary_accum = [
    'is_effective' => false,
    'sss_received_total' => 0.0,
    'sss_count' => 0,
    'transfers_by_account' => [],
    'sss_visits' => []
];

foreach ($months_to_process as $m_txt) {
    if (is_sss_effective_for_month($sss_config, $m_txt) && $auto_split_sss_opd) {
        $s_sss = get_sss_splitting_summary_for_month($conn, $conn2, $m_txt, 'OPD', $sss_config, $my_hospcode);
        if ($s_sss) {
            $has_any_sss = true;
            $sss_summary_accum['is_effective'] = true;
            $sss_summary_accum['sss_received_total'] += $s_sss['sss_received_total'];
            $sss_summary_accum['sss_count'] += $s_sss['sss_count'];
            foreach ($s_sss['sss_visits'] as $k => $v) {
                $sss_summary_accum['sss_visits'][$k] = $v;
            }
            foreach ($s_sss['transfers_by_account'] as $acc => $tr) {
                if (!isset($sss_summary_accum['transfers_by_account'][$acc])) {
                    $sss_summary_accum['transfers_by_account'][$acc] = [
                        'original_debit' => 0.0,
                        'transfer_out' => 0.0,
                        'remain_debit' => 0.0,
                        'cases' => 0,
                        'sss_cases' => 0,
                        'full_transfer_cases' => 0,
                        'partial_transfer_cases' => 0
                    ];
                }
                $sss_summary_accum['transfers_by_account'][$acc]['original_debit'] += $tr['original_debit'];
                $sss_summary_accum['transfers_by_account'][$acc]['transfer_out'] += $tr['transfer_out'];
                $sss_summary_accum['transfers_by_account'][$acc]['remain_debit'] += $tr['remain_debit'];
                $sss_summary_accum['transfers_by_account'][$acc]['cases'] += $tr['cases'];
                $sss_summary_accum['transfers_by_account'][$acc]['sss_cases'] += $tr['sss_cases'];
                $sss_summary_accum['transfers_by_account'][$acc]['full_transfer_cases'] += $tr['full_transfer_cases'];
                $sss_summary_accum['transfers_by_account'][$acc]['partial_transfer_cases'] += $tr['partial_transfer_cases'];
            }
        }
    } else {
        $inactive_sss_months[] = $m_txt;
    }
}

if (!empty($inactive_sss_months) && $has_any_sss) {
    $in_m_str_s = "'" . implode("','", array_map(function($m) use ($conn) { return mysqli_real_escape_string($conn, $m); }, $inactive_sss_months)) . "'";
    $q_inact_s = mysqli_query($conn, "SELECT COUNT(vn) as c, COALESCE(SUM(debit), 0) as d 
                                       FROM imr_tb_debtor_rights_opd 
                                       WHERE accountcode = '1102050101.309' 
                                       AND (pttypename NOT LIKE '%ฟอกไต%' AND pttypename NOT LIKE '%ไต%') 
                                       AND monthtxt IN ($in_m_str_s) AND ($whereSQL)");
    if ($q_inact_s && $r_inact_s = mysqli_fetch_assoc($q_inact_s)) {
        $sss_summary_accum['sss_count'] += intval($r_inact_s['c']);
        $sss_summary_accum['sss_received_total'] += floatval($r_inact_s['d']);
    }
}

if ($has_any_sss) {
    $sss_summary = $sss_summary_accum;
}


// ==========================================================================================
// ส่วนที่ 2 : ตั้งค่า PDF Class
// ==========================================================================================

class MYPDF extends TCPDF {
    
    // เพิ่มตัวแปร public เพื่อรับข้อความหัวกระดาษจากภายนอก
    public $customHeadertext = ''; 

    //Page header
public function Header() {
        // ใช้ตัวแปรที่ส่งเข้ามาแทนการ Query ซ้ำ
        $this->SetFont('thsarabun', 'B', 16);
        $this->SetY(13);
        
        $this->Cell(0, 15, 'รายงานสรุปลูกหนี้ ตามสิทธิการเงิน OPD ' . $this->customHeadertext, 0, false, 'C', 0, '', 0, false, 'M', 'M');

        $this->SetFont('thsarabun', '', 14);
        $this->SetY(22);
        $this->SetX(15);
        
        // อันนี้ถ้าอยากให้กลางด้วย ก็ทำเหมือนกันครับ (ลบ SetX และเปลี่ยน L เป็น C)
        // แต่ถ้าอันนี้อยากให้ชิดซ้ายเหมือนเดิม ก็ปล่อยไว้ครับ
        $this->Cell(0, 15, 'รายงานสรุปลูกหนี้ ตามสิทธิการเงิน OPD ผู้ป่วยนอก', 0, false, 'L', 0, '', 0, false, 'M', 'M');
    }

    // Page footer
    public function Footer() {
         //$this->SetY(50);
         //$this->SetFont('thsarabun', '', 14);
         //$this->Cell(0, 10, 'หน้า '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// create new PDF document
$pdf = new MYPDF("L", PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// ส่งค่า Header text เข้าไปใน Class
$pdf->customHeadertext = $headerText;

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT-5, PDF_MARGIN_TOP+5, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(3);
$pdf->SetAutoPageBreak(TRUE, 3);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set language
if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
    require_once(dirname(__FILE__) . '/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------
// เริ่มสร้างหน้าแรก (สรุปยอดรวม OPD)
// ---------------------------------------------------------

$pdf->SetFont('thsarabun', 'B', 14);
$pdf->AddPage(); 

$pdf->SetFont('thsarabun', '', 14);
$pdf->SetY(28);
$pdf->SetX(15);

$html='';
$html .= '
<style>
    table.report-table {
        border-collapse: collapse;
        width: 100%;
    }
    table.report-table td, table.report-table th {
        border: 0.1px solid #000000;
    }
</style>
<table class="report-table" cellpadding="2" cellspacing="0">
    <thead>
        <tr style="height: 39px;">
            <td style=" text-align: center;" colspan="7">รายงานสรุปลูกหนี้ ตามสิทธิการเงิน ผู้ป่วยนอก '.$headerText.'</td>
        </tr>
        <tr style="">
            <td style="text-align: center;width: 11%;" rowspan="2">รหัส</td>
            <td style="text-align: center;width: 30.7%;" rowspan="2">ชื่อ</td>
            <td style="text-align: center;width: 20%;" colspan="2">ลูกหนี้</td>
            <td style="text-align: center;width: 24%;" colspan="2">รายการ HOSxP</td>
            <td style="text-align: center;width: 14.3%;" rowspan="2">ภาระหนี้</td>
        </tr>
        <tr style="">
            <td style="text-align: center;width: 10%;">ทั้งหมด</td>
            <td style="text-align: center;width: 10%;">คงเหลือ</td>
            <td style="text-align: center;width: 12%;">ค่าใช้จ่าย</td>
            <td style="text-align: center;width: 12%;">ชำระแล้ว</td>
        </tr>
    </thead>
<tbody>
';
$sql = "SELECT MAX(pttype_eclaim_id) as pttype_eclaim_id, MAX(pttype_eclaim_name) as pttype_eclaim_name, accountcode, MAX(accountname) as accountname,
    count(income) as visitall,
    COUNT(CASE WHEN income <> 0 AND debit <> 0 THEN 1 END) AS visitdiff,
    sum(income) as incomeall,
    sum(income)-sum(COALESCE(original_debit, debit)) as incomediff,
    SUM(debit) AS incometotall
    FROM imr_tb_debtor_rights_opd
    WHERE $whereSQL 
    GROUP BY accountcode
    ORDER BY accountcode ASC";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $visitall=0;$visitdiff=0;$incomeall=0;$incomediff=0;$incometotall=0;
    $rendered_216 = false;
    $rendered_309 = false;

    while ($row = mysqli_fetch_assoc($result)) {

        // ปรับยอด CR Cross-Account Splitting
        if ($cr_summary) {
            $acc = $row["accountcode"];
            if ($acc === '1102050101.216') {
                $rendered_216 = true;
                $total_216_count = $cr_summary['cr_non_kidney_count'] + $cr_summary['cr_kidney_count'];
                $total_216_diff_count = $cr_summary['cr_non_kidney_count'] + ($cr_summary['cr_kidney_diff_count'] ?? $cr_summary['cr_kidney_count']);
                $total_216_debit = $cr_summary['cr_non_kidney_received_total'] + $cr_summary['cr_kidney_received_total'];
                $row["incometotall"] = $total_216_debit;
                $row["incomeall"] = $total_216_debit;
                $row["incomediff"] = 0.0;
                $row["visitall"] = $total_216_count;
                $row["visitdiff"] = $total_216_diff_count;
            } elseif (isset($cr_summary['transfers_by_account'][$acc])) {
                if ($is_216_allowed) {
                    $tr = $cr_summary['transfers_by_account'][$acc];
                    $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr['transfer_out']);
                    $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr['transfer_out']);
                    $row["visitall"] = max(0, intval($row["visitall"]) - $tr['full_transfer_cases']);
                    $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr['full_transfer_cases']);
                }
            }
        }

        // ปรับยอด SSS Cross-Account Splitting
        if ($sss_summary) {
            $acc = $row["accountcode"];
            if ($acc === '1102050101.309') {
                $rendered_309 = true;
                $row["incometotall"] = cleanNum($row["incometotall"]) + $sss_summary['sss_received_total'];
                $row["incomeall"] = cleanNum($row["incomeall"]) + $sss_summary['sss_received_total'];
                $row["incomediff"] = 0.0;
                $row["visitall"] = $sss_summary['sss_count'];
                $row["visitdiff"] = $sss_summary['sss_count'];
            } elseif (isset($sss_summary['transfers_by_account'][$acc])) {
                if ($is_309_allowed) {
                    $tr_sss = $sss_summary['transfers_by_account'][$acc];
                    $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr_sss['transfer_out']);
                    $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr_sss['transfer_out']);
                    $row["visitall"] = max(0, intval($row["visitall"]) - $tr_sss['full_transfer_cases']);
                    $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr_sss['full_transfer_cases']);
                }
            }
        }

        // 💡 [Clean UI Standard] ข้ามแถวที่ยอดคงเหลือเป็น 0 ทั้งจำนวนเคสและยอดภาระหนี้ เพื่อไม่ให้แสดงแถวว่างเปล่าในตาราง
        if (intval($row["visitall"]) <= 0 && cleanNum($row["incometotall"]) <= 0) {
            continue;
        }

        //ค้นหาคำว่า 'ค่ารักษา' ในชื่อบัญชี แล้วแทนที่ด้วยช่องว่าง ' ' ทันทีตามที่พี่ต้องการ
        $clean_accountname = str_replace('ค่ารักษา', ' ', (string)$row["accountname"]);

        $html .='<tr style="">
                <td style="width: 11%;">&nbsp;'.$row["accountcode"].'</td>
                <td style="text-overflow: ellipsis;width: 30.7%;font-size: 17px;">&nbsp;'.$clean_accountname.'</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format($row["visitall"],0).'</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format($row["visitdiff"],0).'</td>
                <td style="text-align: right;width: 12%;">&nbsp;'.number_format($row["incomeall"],2).'</td>
                <td style="text-align: right;width: 12%;">&nbsp;'.number_format($row["incomediff"],2).'</td>
                <td style="text-align: right;">&nbsp;'.number_format($row["incometotall"],2).'</td>
            </tr>';

            $visitall += $row["visitall"];
            $visitdiff += $row["visitdiff"];
            $incomeall += $row["incomeall"];
            $incomediff += $row["incomediff"];
            $incometotall += $row["incometotall"];
    }

    if (!$rendered_216 && $cr_summary && ($cr_summary['cr_non_kidney_count'] > 0 || $cr_summary['cr_kidney_count'] > 0) && $is_216_allowed) {
        $acc216 = '1102050101.216';
        $accname216 = 'ลูกหนี้  UC - OP บริการเฉพาะ (CR)';
        $total_216_count = $cr_summary['cr_non_kidney_count'] + $cr_summary['cr_kidney_count'];
        $total_216_diff_count = $cr_summary['cr_non_kidney_count'] + ($cr_summary['cr_kidney_diff_count'] ?? $cr_summary['cr_kidney_count']);
        $total_216_debit = $cr_summary['cr_non_kidney_received_total'] + $cr_summary['cr_kidney_received_total'];
        $html .='<tr style="">
                <td style="width: 11%;">&nbsp;'.$acc216.'</td>
                <td style="text-overflow: ellipsis;width: 30.7%;font-size: 17px;">&nbsp;'.$accname216.'</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format($total_216_count,0).'</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format($total_216_diff_count,0).'</td>
                <td style="text-align: right;width: 12%;">&nbsp;'.number_format($total_216_debit,2).'</td>
                <td style="text-align: right;width: 12%;">&nbsp;'.number_format(0,2).'</td>
                <td style="text-align: right;">&nbsp;'.number_format($total_216_debit,2).'</td>
            </tr>';
        $visitall += $total_216_count;
        $visitdiff += $total_216_diff_count;
        $incomeall += $total_216_debit;
        $incometotall += $total_216_debit;
    }

    if (!$rendered_309 && $sss_summary && $sss_summary['sss_count'] > 0 && $is_309_allowed) {
        $acc309 = '1102050101.309';
        $accname309 = 'ลูกหนี้  ประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP';
        $html .='<tr style="">
                <td style="width: 11%;">&nbsp;'.$acc309.'</td>
                <td style="text-overflow: ellipsis;width: 30.7%;font-size: 17px;">&nbsp;'.$accname309.'</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format($sss_summary['sss_count'],0).'</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format($sss_summary['sss_count'],0).'</td>
                <td style="text-align: right;width: 12%;">&nbsp;'.number_format($sss_summary['sss_received_total'],2).'</td>
                <td style="text-align: right;width: 12%;">&nbsp;'.number_format(0,2).'</td>
                <td style="text-align: right;">&nbsp;'.number_format($sss_summary['sss_received_total'],2).'</td>
            </tr>';
        $visitall += $sss_summary['sss_count'];
        $visitdiff += $sss_summary['sss_count'];
        $incomeall += $sss_summary['sss_received_total'];
        $incometotall += $sss_summary['sss_received_total'];
    }

    $html .='<tr style="">
        <td style="width: 41.7%; height: 18px; text-align: center;" colspan="2">รวม</td>
        <td style="text-align: right;width: 10%;">&nbsp;'.number_format($visitall,0).'</td>
        <td style="text-align: right;width: 10%;">&nbsp;'.number_format($visitdiff,0).'</td>
        <td style="text-align: right;width: 12%;">&nbsp;'.number_format($incomeall,2).'</td>
        <td style="text-align: right;width: 12%;">&nbsp;'.number_format($incomediff,2).'</td>
        <td style="text-align: right;">&nbsp;'.number_format($incometotall,2).'</td>
    </tr>';
}

$html .='</tbody>
</table>';

// ส่วนท้ายเซ็นชื่อ (การตั้งลูกหนี้ - งานประกันสุขภาพ)
require_once __DIR__ . '/../../system/includes/signers_helper.php';
$signers_cfg = get_signers_config($conn);
$name1 = $signers_cfg['setup']['name1'] ?? '';
$name2 = $signers_cfg['setup']['name2'] ?? '';
$name3 = $signers_cfg['setup']['name3'] ?? '';
$pos1  = $signers_cfg['setup']['pos1'] ?? '';
$pos2  = $signers_cfg['setup']['pos2'] ?? '';
$pos3  = $signers_cfg['setup']['pos3'] ?? '';

$role1 = !empty($signers_cfg['setup']['role1']) ? nl2br($signers_cfg['setup']['role1']) : 'ผู้จัดทำรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ';
$role2 = !empty($signers_cfg['setup']['role2']) ? nl2br($signers_cfg['setup']['role2']) : 'ผู้ตรวจสอบรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ';
$role3 = !empty($signers_cfg['setup']['role3']) ? nl2br($signers_cfg['setup']['role3']) : 'หัวหน้ากลุ่มงานประกันสุขภาพ';

$html_sig = '';
$html_sig .='
<br>
<table style="width:100%;text-align:center;" nobr="true">
  <tr nobr="true">
    <td style="width:33.33%;"><br><br><br>('.$name1.')<br>'.(!empty($pos1) ? 'ตำแหน่ง '.$pos1.'<br>' : '').$role1.'</td>
    <td style="width:33.33%;"><br><br><br>('.$name2.')<br>'.(!empty($pos2) ? 'ตำแหน่ง '.$pos2.'<br>' : '').$role2.'</td>
    <td style="width:33.33%;"><br><br><br>('.$name3.')<br>'.(!empty($pos3) ? 'ตำแหน่ง '.$pos3.'<br>' : '').$role3.'</td>
  </tr>
</table>';

$html .= $html_sig;

$pdf->writeHTML($html, true, false, false, false, '');


// ---------------------------------------------------------
// ส่วนที่ 3 : วนลูปสร้างหน้ารายวัน (Logic ใหม่)
// ---------------------------------------------------------

// 1. ดึงวันที่ทั้งหมดที่มีข้อมูลตามเงื่อนไขออกมา (Group by vstdate)
// ใช้ STR_TO_DATE ใน ORDER BY เพื่อเรียงวันที่ให้ถูกต้องตามปฏิทิน ไม่ใช่ตามตัวอักษร
$sql_dates = "SELECT vstdate 
              FROM imr_tb_debtor_rights_opd 
              WHERE $whereSQL 
              GROUP BY vstdate 
              ORDER BY STR_TO_DATE(vstdate, '%d/%m/%Y') ASC";

$result_dates = mysqli_query($conn, $sql_dates);

// 2. Loop สร้างหน้าตามวันที่ที่มีจริง
if (mysqli_num_rows($result_dates) > 0) {

    while ($row_date = mysqli_fetch_assoc($result_dates)) {
        
        $current_vstdate = $row_date['vstdate']; // ได้ค่าวันที่เช่น 05/02/2568

        // คืนค่าขอบล่างให้เป็นค่าปกติสำหรับหน้ารายวัน
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // เพิ่มหน้าใหม่
        $pdf->SetFont('thsarabun', 'B', 14);
        $pdf->AddPage(); 
        $pdf->SetFont('thsarabun', '', 14);
        $pdf->SetY(28);
        $pdf->SetX(15);
        
        $html1 = '';
        $html1 .= '
        <style>
            table.report-table {
                border-collapse: collapse;
                width: 100%;
            }
            table.report-table td, table.report-table th {
                border: 0.1px solid #000000;
            }
        </style>
        <table class="report-table" cellpadding="2" cellspacing="0">
            <thead>
                <tr style="height: 39px;">
                    <td style=" text-align: center;" colspan="7">รายงานสรุปลูกหนี้ ตามสิทธิการเงิน ผู้ป่วยนอก วันที่ '.$current_vstdate.'</td>
                </tr>
                <tr style="">
                    <td style="text-align: center;width: 11%;" rowspan="2">รหัส</td>
                    <td style="text-align: center;width: 30.7%;" rowspan="2">ชื่อ</td>
                    <td style="text-align: center;width: 20%;" colspan="2">ลูกหนี้</td>
                    <td style="text-align: center;width: 24%;" colspan="2">รายการ HOSxP</td>
                    <td style="text-align: center;width: 14.3%;" rowspan="2">ภาระหนี้</td>
                </tr>
                <tr style="">
                    <td style="text-align: center;width: 10%;">ทั้งหมด</td>
                    <td style="text-align: center;width: 10%;">คงเหลือ</td>
                    <td style="text-align: center;width: 12%;">ค่าใช้จ่าย</td>
                    <td style="text-align: center;width: 12%;">ชำระแล้ว</td>
                </tr>
            </thead>
        <tbody>
        ';

        // Query ข้อมูลรายวัน
        $sql1 = "SELECT MAX(pttype_eclaim_id) as pttype_eclaim_id, MAX(pttype_eclaim_name) as pttype_eclaim_name, accountcode, MAX(accountname) as accountname,
            count(income) as visitall,
            COUNT(CASE WHEN income <> 0 AND debit <> 0 THEN 1 END) AS visitdiff,
            sum(income) as incomeall,
            sum(income)-sum(COALESCE(original_debit, debit)) as incomediff,
            SUM(debit) AS incometotall
            FROM imr_tb_debtor_rights_opd
            WHERE vstdate = '$current_vstdate' AND ($whereSQL)
            GROUP BY accountcode
            ORDER BY accountcode ASC";

        $result1 = mysqli_query($conn, $sql1);

        // คำนวณยอด CR ประจำวัน $current_vstdate
        $daily_transfers = [];
        $daily_216_count = 0;
        $daily_216_total = 0.0;
        $daily_216_kidney_count = 0;
        $daily_216_kidney_diff_count = 0;
        $daily_216_kidney_total = 0.0;
        if ($cr_summary && !empty($cr_summary['cr_visits'])) {
            foreach ($cr_summary['cr_visits'] as $vn => $cv) {
                if (!empty($cv['vstdate']) && $cv['vstdate'] == $current_vstdate) {
                    if (!empty($cv['is_kidney'])) {
                        $daily_216_kidney_count++;
                        if (cleanNum($cv['cr_amount']) > 0) {
                            $daily_216_kidney_diff_count++;
                        }
                        $daily_216_kidney_total += cleanNum($cv['cr_amount']);
                    } else {
                        $orig = $cv['origin_accountcode'];
                        if ($orig !== '1102050101.216') {
                            if (!isset($daily_transfers[$orig])) {
                                $daily_transfers[$orig] = ['transfer_out' => 0.0, 'full_transfer_cases' => 0];
                            }
                            $daily_transfers[$orig]['transfer_out'] += isset($cv['transfer_out']) ? cleanNum($cv['transfer_out']) : cleanNum($cv['cr_amount']);
                            if (!empty($cv['is_full_transfer'])) {
                                $daily_transfers[$orig]['full_transfer_cases']++;
                            }
                        }
                        $daily_216_count++;
                        $daily_216_total += cleanNum($cv['cr_amount']);
                    }
                }
            }
        }

        // ดึงยอดเคสไตของผัง .216 จากเดือนที่ยังไม่ได้เปิดระบบในวันที่เลือก (ถ้ามี)
        if ($cr_summary && empty($daily_216_kidney_count)) {
            $q_inact_dk = mysqli_query($conn, "SELECT COUNT(vn) as c, COUNT(CASE WHEN income <> 0 AND debit <> 0 THEN 1 END) as diff_c, COALESCE(SUM(debit), 0) as d 
                                                FROM imr_tb_debtor_rights_opd 
                                                WHERE vstdate = '$current_vstdate' 
                                                AND accountcode = '1102050101.216' 
                                                AND (pttypename LIKE '%ไต%' OR pttypename LIKE '%ฟอกไต%')");
            if ($q_inact_dk && $r_inact_dk = mysqli_fetch_assoc($q_inact_dk)) {
                $daily_216_kidney_count = intval($r_inact_dk['c']);
                $daily_216_kidney_diff_count = intval($r_inact_dk['diff_c']);
                $daily_216_kidney_total = floatval($r_inact_dk['d']);
            }
        }

        // คำนวณยอด SSS Instrument ประจำวัน $current_vstdate
        $daily_sss_transfers = [];
        $daily_309_count = 0;
        $daily_309_total = 0.0;
        if ($sss_summary && !empty($sss_summary['sss_visits'])) {
            foreach ($sss_summary['sss_visits'] as $vn => $sv) {
                if (!empty($sv['vstdate']) && $sv['vstdate'] == $current_vstdate) {
                    $orig = $sv['origin_accountcode'];
                    if ($orig !== '1102050101.309') {
                        if (!isset($daily_sss_transfers[$orig])) {
                            $daily_sss_transfers[$orig] = ['transfer_out' => 0.0, 'full_transfer_cases' => 0];
                        }
                        $daily_sss_transfers[$orig]['transfer_out'] += isset($sv['transfer_out']) ? cleanNum($sv['transfer_out']) : cleanNum($sv['sss_amount']);
                        if (!empty($sv['is_full_transfer'])) {
                            $daily_sss_transfers[$orig]['full_transfer_cases']++;
                        }
                    }
                    $daily_309_count++;
                    $daily_309_total += cleanNum($sv['sss_amount']);
                }
            }
        }

        if ($result1 && mysqli_num_rows($result1) > 0) {
            $visitall=0;$visitdiff=0;$incomeall=0;$incomediff=0;$incometotall=0;
            $rendered_daily_216 = false;
            $rendered_daily_309 = false;

            while ($row = mysqli_fetch_assoc($result1)) {

                // ปรับยอด CR ประจำวัน
                if ($cr_summary) {
                    $acc = $row["accountcode"];
                    if ($acc === '1102050101.216') {
                        $rendered_daily_216 = true;
                        $combined_daily_216_total = $daily_216_total + $daily_216_kidney_total;
                        $combined_daily_216_count = $daily_216_count + $daily_216_kidney_count;
                        $combined_daily_216_diff_count = $daily_216_count + $daily_216_kidney_diff_count;
                        $row["incometotall"] = $combined_daily_216_total;
                        $row["incomeall"] = $combined_daily_216_total;
                        $row["incomediff"] = 0.0;
                        $row["visitall"] = $combined_daily_216_count;
                        $row["visitdiff"] = $combined_daily_216_diff_count;
                    } elseif (isset($daily_transfers[$acc])) {
                        if ($is_216_allowed) {
                            $tr_d = $daily_transfers[$acc];
                            $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr_d['transfer_out']);
                            $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr_d['transfer_out']);
                            $row["visitall"] = max(0, intval($row["visitall"]) - $tr_d['full_transfer_cases']);
                            $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr_d['full_transfer_cases']);
                        }
                    }
                }

                // ปรับยอด SSS ประจำวัน
                if ($sss_summary) {
                    $acc = $row["accountcode"];
                    if ($acc === '1102050101.309') {
                        $rendered_daily_309 = true;
                        $row["incometotall"] = $daily_309_total;
                        $row["incomeall"] = $daily_309_total;
                        $row["incomediff"] = 0.0;
                        $row["visitall"] = $daily_309_count;
                        $row["visitdiff"] = $daily_309_count;
                    } elseif (isset($daily_sss_transfers[$acc])) {
                        if ($is_309_allowed) {
                            $tr_d_sss = $daily_sss_transfers[$acc];
                            $row["incometotall"] = max(0, cleanNum($row["incometotall"]) - $tr_d_sss['transfer_out']);
                            $row["incomeall"] = max(0, cleanNum($row["incomeall"]) - $tr_d_sss['transfer_out']);
                            $row["visitall"] = max(0, intval($row["visitall"]) - $tr_d_sss['full_transfer_cases']);
                            $row["visitdiff"] = max(0, intval($row["visitdiff"]) - $tr_d_sss['full_transfer_cases']);
                        }
                    }
                }

                // 💡 [Clean UI Standard] ข้ามแถวที่ยอดคงเหลือเป็น 0 ทั้งจำนวนเคสและยอดภาระหนี้/ค่าใช้จ่าย เพื่อไม่ให้แสดงแถวว่างเปล่าในรายงานประจำวัน
                if (intval($row["visitall"]) <= 0 && cleanNum($row["incometotall"]) <= 0 && cleanNum($row["incomeall"]) <= 0) {
                    continue;
                }

                // ดักตัดคำว่า 'ค่ารักษา' ออกในหน้าเอกสารรายวันด้วยเช่นกันครับพี่
                $clean_accountname_daily = str_replace('ค่ารักษา', ' ', (string)$row["accountname"]);

                $html1 .='<tr style="">
                        <td style="width: 11%;">&nbsp;'.$row["accountcode"].'</td>
                        <td style="width: 30.7%;text-overflow: ellipsis;font-size: 17px;">&nbsp;'.$clean_accountname_daily.'</td>
                        <td style="text-align: right;width: 10%;">&nbsp;'.number_format($row["visitall"],0).'</td>
                        <td style="text-align: right;width: 10%;">&nbsp;'.number_format($row["visitdiff"],0).'</td>
                        <td style="text-align: right;width: 12%;">&nbsp;'.number_format($row["incomeall"],2).'</td>
                        <td style="text-align: right;width: 12%;">&nbsp;'.number_format($row["incomediff"],2).'</td>
                        <td style="text-align: right;">&nbsp;'.number_format($row["incometotall"],2).'</td>
                    </tr>';

                    $visitall += $row["visitall"];
                    $visitdiff += $row["visitdiff"];
                    $incomeall += $row["incomeall"];
                    $incomediff += $row["incomediff"];
                    $incometotall += $row["incometotall"];
            }

            if (!$rendered_daily_216 && $cr_summary && ($daily_216_count > 0 || $daily_216_kidney_count > 0) && $is_216_allowed) {
                $acc216 = '1102050101.216';
                $accname216 = 'ลูกหนี้  UC - OP บริการเฉพาะ (CR)';
                $combined_daily_216_total = $daily_216_total + $daily_216_kidney_total;
                $combined_daily_216_count = $daily_216_count + $daily_216_kidney_count;
                $combined_daily_216_diff_count = $daily_216_count + $daily_216_kidney_diff_count;
                $html1 .='<tr style="">
                        <td style="width: 11%;">&nbsp;'.$acc216.'</td>
                        <td style="width: 30.7%;text-overflow: ellipsis;font-size: 17px;">&nbsp;'.$accname216.'</td>
                        <td style="text-align: right;width: 10%;">&nbsp;'.number_format($combined_daily_216_count,0).'</td>
                        <td style="text-align: right;width: 10%;">&nbsp;'.number_format($combined_daily_216_diff_count,0).'</td>
                        <td style="text-align: right;width: 12%;">&nbsp;'.number_format($combined_daily_216_total,2).'</td>
                        <td style="text-align: right;width: 12%;">&nbsp;'.number_format(0,2).'</td>
                        <td style="text-align: right;">&nbsp;'.number_format($combined_daily_216_total,2).'</td>
                    </tr>';
                $visitall += $combined_daily_216_count;
                $visitdiff += $combined_daily_216_diff_count;
                $incomeall += $combined_daily_216_total;
                $incometotall += $combined_daily_216_total;
            }

            if (!$rendered_daily_309 && $sss_summary && $daily_309_count > 0 && $is_309_allowed) {
                $acc309 = '1102050101.309';
                $accname309 = 'ลูกหนี้  ประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP';
                $html1 .='<tr style="">
                        <td style="width: 11%;">&nbsp;'.$acc309.'</td>
                        <td style="width: 30.7%;text-overflow: ellipsis;font-size: 17px;">&nbsp;'.$accname309.'</td>
                        <td style="text-align: right;width: 10%;">&nbsp;'.number_format($daily_309_count,0).'</td>
                        <td style="text-align: right;width: 10%;">&nbsp;'.number_format($daily_309_count,0).'</td>
                        <td style="text-align: right;width: 12%;">&nbsp;'.number_format($daily_309_total,2).'</td>
                        <td style="text-align: right;width: 12%;">&nbsp;'.number_format(0,2).'</td>
                        <td style="text-align: right;">&nbsp;'.number_format($daily_309_total,2).'</td>
                    </tr>';
                $visitall += $daily_309_count;
                $visitdiff += $daily_309_count;
                $incomeall += $daily_309_total;
                $incometotall += $daily_309_total;
            }

            $html1 .='<tr style="">
                <td style="width: 41.7%; height: 18px; text-align: center;" colspan="2">รวม</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format($visitall,0).'</td>
                <td style="text-align: right;width: 10%;">&nbsp;'.number_format($visitdiff,0).'</td>
                <td style="text-align: right;width: 12%;">&nbsp;'.number_format($incomeall,2).'</td>
                <td style="text-align: right;width: 12%;">&nbsp;'.number_format($incomediff,2).'</td>
                <td style="text-align: right;">&nbsp;'.number_format($incometotall,2).'</td>
            </tr>';
        }
    
        $html1 .='</tbody> </table>';
        if ($show_sig == 1) {
            $html1 .= $html_sig;
        }
        $pdf->writeHTML($html1, true, false, false, false, '');

    } // End While Loop Date
}


if (function_exists('system_log')) {
    system_log($conn, 'ระบบลูกหนี้ (Print/PDF)', 'PRINT', "พิมพ์รายงานสรุปลูกหนี้ ตามสิทธิการเงิน OPD ผู้ป่วยนอก");
}

//Close and output PDF document
$pdf->Output('ตั้งลูกหนี้_OPD.pdf', 'I');
?>