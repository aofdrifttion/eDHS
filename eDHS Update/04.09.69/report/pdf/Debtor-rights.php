<?php


require '../../system/database_config/config.php';

$stmgetovlookup = $_GET['norep'] ?? '';

require_once('tcpdf_include.php');


class MYPDF extends TCPDF {



    //Page header

    public function Header() {


     require '../../system/database_config/config.php';

        $stmgetovlookup = $_GET['norep'] ?? '';
        $mobileq = '';

        // ดึงเลขที่ใบเสร็จ
        $sqlm = "SELECT i.mobile as 'mobilei', o.mobile as 'mobileo', o.discount_money as 'discount_moneyo', i.discount_money as 'discount_moneyi'  
            FROM imr_tb_check_invoice stm
            LEFT JOIN imr_tb_debtor_rights_opd o on o.vn=stm.vn
            LEFT JOIN imr_tb_debtor_rights_ipd i on i.an=stm.vn
            WHERE stm.rep = '$stmgetovlookup'
            GROUP BY mobilei, mobileo
            ORDER BY o.vstdate asc ";

        $resultm = mysqli_query($conn, $sqlm);        

        if (mysqli_num_rows($resultm) > 0) {
            while ($rowm = mysqli_fetch_assoc($resultm)) {
                if ($rowm["mobilei"] != '') {
                    if ($rowm["discount_moneyi"] != "appeal") {
                        $mobileq = $rowm["mobilei"];
                    }
                } else {
                    if ($rowm["discount_moneyo"] != "appeal") {
                        $mobileq = $rowm["mobileo"];
                    }
                }
            }
        }

        // ดึงสิทธิการเงินและรหัสทั้งหมดที่อยู่ใน Statement นี้
        $sql_rights = "SELECT 
                COALESCE(NULLIF(o.accountname, ''), NULLIF(i.accountname, ''), stm.accountname) AS acc_name,
                COALESCE(NULLIF(o.accountcode, ''), NULLIF(i.accountcode, ''), stm.accountcode) AS acc_code
            FROM imr_tb_check_invoice stm
            LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = stm.vn
            LEFT JOIN imr_tb_debtor_rights_ipd i ON i.an = stm.vn
            WHERE stm.rep = '$stmgetovlookup' AND stm.compensated != '0'
            ORDER BY o.vstdate ASC";

        $res_rights = mysqli_query($conn, $sql_rights);
        $account_list = [];
        if ($res_rights && mysqli_num_rows($res_rights) > 0) {
            while ($r_acc = mysqli_fetch_assoc($res_rights)) {
                $a_name = trim($r_acc['acc_name'] ?? '');
                $a_code = trim($r_acc['acc_code'] ?? '');
                if (!empty($a_name)) {
                    $item = $a_name . (!empty($a_code) ? ' รหัส ' . $a_code : '');
                    if (!in_array($item, $account_list)) {
                        $account_list[] = $item;
                    }
                }
            }
        }

        // Fallback: ถ้ายังไม่มีรายการ ให้ค้นหาโดยไม่ตัด compensated != 0
        if (empty($account_list)) {
            $sql_rights_fallback = "SELECT 
                    COALESCE(NULLIF(o.accountname, ''), NULLIF(i.accountname, ''), stm.accountname) AS acc_name,
                    COALESCE(NULLIF(o.accountcode, ''), NULLIF(i.accountcode, ''), stm.accountcode) AS acc_code
                FROM imr_tb_check_invoice stm
                LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = stm.vn
                LEFT JOIN imr_tb_debtor_rights_ipd i ON i.an = stm.vn
                WHERE stm.rep = '$stmgetovlookup'
                ORDER BY o.vstdate ASC";
            $res_fb = mysqli_query($conn, $sql_rights_fallback);
            if ($res_fb && mysqli_num_rows($res_fb) > 0) {
                while ($r_acc = mysqli_fetch_assoc($res_fb)) {
                    $a_name = trim($r_acc['acc_name'] ?? '');
                    $a_code = trim($r_acc['acc_code'] ?? '');
                    if (!empty($a_name)) {
                        $item = $a_name . (!empty($a_code) ? ' รหัส ' . $a_code : '');
                        if (!in_array($item, $account_list)) {
                            $account_list[] = $item;
                        }
                    }
                }
            }
        }

        $account_display = implode(', ', $account_list);


        
        // Logo
        $this->SetFont('thsarabun', '', 14);
        $this->SetY(14);
        $this->SetX(286);
        $this->Cell(20, 0, 'หน้า '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R');

        $this->SetFont('thsarabun', 'B', 18);

        $this->SetY(20);

        $this->SetX(127);

        $this->Cell(0, 15, 'ทะเบียนรายชื่อตัดลูกหนี้', 0, false, 'L', 0, '', 0, false, 'M', 'M');

        $this->SetFont('thsarabun', '', 14);

        $this->SetY(10);

        $this->SetX(10);

        $this->Cell(0, 15, 'เลขที่ใบเสร็จ '.$mobileq .'', 0, false, 'L', 0, '', 0, false, 'M', 'M');

        $this->SetY(10);

        $this->SetX(259);

        $this->Cell(0, 15, 'REP No. '.$stmgetovlookup .'', 0, false, 'L', 0, '', 0, false, 'M', 'M');

        if (mb_strlen($account_display, 'UTF-8') > 130) {
            $this->SetFont('thsarabun', 'B', 11.5);
        } elseif (mb_strlen($account_display, 'UTF-8') > 90) {
            $this->SetFont('thsarabun', 'B', 13);
        } else {
            $this->SetFont('thsarabun', 'B', 14);
        }

        $this->SetY(27);

        $this->SetX(10);

        $this->Cell(0, 15, $account_display, 0, false, 'L', 0, '', 0, false, 'M', 'M');


    }



    // Page footer

    public function Footer() {


         //$this->SetY(50);

         //$this->SetFont('thsarabun', '', 14);

         //$this->Cell(0, 10, 'หน้า '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
 

    }



}



// create new PDF document

$pdf = new MYPDF("P", PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set default header data

$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);



// set header and footer fonts

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));



// set default monospaced font

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);



// set margins

$pdf->SetMargins(PDF_MARGIN_LEFT-5, PDF_MARGIN_TOP+26, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(20);
//$pdf->SetFooterMargin(PDF_MARGIN_FOOTER-5);

// set auto page breaks

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM-13);



// set image scale factor

$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);



// set some language-dependent strings (optional)

if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {

    require_once(dirname(__FILE__) . '/lang/eng.php');

    $pdf->setLanguageArray($l);

}



// ---------------------------------------------------------

// set font

$pdf->SetFont('thsarabun', 'B', 14);



// add a page

//$pdf->AddPage();
$pdf->AddPage('L'); // เพิ่มหน้าใหม่แบบแนวนอน
//Header

//$image_file = K_PATH_IMAGES . 'logo_ric.jpg';

//$pdf->Image($image_file, 25, 10, 22, '', 'JPG', '', 'T', false, 400, '', false, false, 0, false, false, false);



$pdf->SetFont('thsarabun', '', 14);
$pdf->SetY(33);
$pdf->SetX(10);

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
        <tr>
        <th style="width: 6%;text-align:center;">ลำดับ</th>
        <th style="width: 6%;text-align:center;">ปีงบ</th>
        <th style="width: 20%;">&nbsp;ชื่อ-สกุล</th>
        <th style="width: 32%;">&nbsp;สิทธิการเงิน</th>
        <th style="width: 6%;">&nbsp;กองทุน</th>
        <th style="width: 8%;">&nbsp;วันที่</th>
        <th style="width: 8%;">&nbsp;ค่าใช้จ่าย</th>
        <th style="width: 8%;">&nbsp;ชดเชย</th>
        <th style="width: 8%;">&nbsp;ส่วนต่าง</th>
        </tr>
    </thead>
<tbody>
';



          $sql = "SELECT stm.yearbudget,stm.rep,o.vn,o.ptname,o.debit,o.follow_money AS follow_money_o,i.an,i.ptname as 'ptnamei',i.debit as 'debiti',i.follow_money AS follow_money_i,stm.compensated,(stm.compensated)-(o.debit) AS 'diff',(stm.compensated)-(i.debit) AS 'diffi',stm.fund,o.vstdate,i.admdate,stm.accountcode,i.mobile as 'mobilei',o.mobile as 'mobileo',o.accountname as 'accountnameo',i.accountname as 'accountnamei',o.discount_money as 'discount_moneyo',i.discount_money as 'discount_moneyi'  
            FROM imr_tb_check_invoice stm
            LEFT JOIN  imr_tb_debtor_rights_opd o on o.vn=stm.vn
            LEFT JOIN  imr_tb_debtor_rights_ipd i on i.an=stm.vn
            WHERE stm.rep = '$stmgetovlookup' and stm.compensated != '0'
            ORDER BY o.vstdate asc ";


          $result = mysqli_query($conn, $sql);


            

        if (mysqli_num_rows($result) > 0) {
          // output data of each row
          $i=1;$compensated=0;$diff=0;$debit=0;$debiti=0;$diffi=0;$debitcko=0;$debitcki=0; $diffcko=0;$diffcki=0; 

          while ($row = mysqli_fetch_assoc($result)) {

            if($row["an"]==""){
               $an=0;

               if($row["discount_moneyo"]=="appeal"){
                 $debitcko = 0;
                 $diffcko = 0;
               }else{
                 $debitcko = $row["debit"];
                 if(isset($row["follow_money_o"]) && $row["follow_money_o"] > 0){
                     $debitcko = $debitcko - $row["follow_money_o"];
                     $row["diff"] = $row["compensated"] - $debitcko;
                 }
                 $diffcko = $row["diff"];
                
               
                $html .='<tr nobr="true">
                  <td style="width: 6%;text-align:center;">'.$i.'</td>
                  <td style="width: 6%;text-align:center;">'.$row["yearbudget"].'</td>
                  <td style="width: 20%;">&nbsp;'.$row["ptname"].'</td>
                  <td style="width: 32%;">&nbsp;'.$row["accountnameo"].'</td>
                  <td style="width: 6%;">&nbsp;'.$row["fund"].'</td>
                  <td style="width: 8%;">&nbsp;'.$row["vstdate"].'</td>              
                  <td style="width: 8%;">&nbsp;'.number_format($debitcko,2).'</td>
                  <td style="width: 8%;">&nbsp;'.number_format($row["compensated"],2).'</td>
                  <td style="width: 8%;">&nbsp;'.number_format($diffcko,2).'</td>
                </tr> ';


                $debit = $debit+$debitcko;
                $diff = $diff+$diffcko;
                $compensated = $compensated+$row["compensated"];


               }





            }else{
              $an=1;

               if($row["discount_moneyi"]=="appeal"){
                 $debitcki = 0;
                 $diffcki = 0;
               }else{
                 $debitcki = $row["debiti"];
                 if(isset($row["follow_money_i"]) && $row["follow_money_i"] > 0){
                     $debitcki = $debitcki - $row["follow_money_i"];
                     $row["diffi"] = $row["compensated"] - $debitcki;
                 }
                 $diffcki = $row["diffi"];

          
                $html .='<tr nobr="true">
                  <td style="width: 6%;text-align:center;">'.$i.'</td>
                  <td style="width: 6%;text-align:center;">'.$row["yearbudget"].'</td>
                  <td style="width: 20%;">&nbsp;'.$row["ptnamei"].'</td>
                  <td style="width: 32%;">&nbsp;'.$row["accountnamei"].'</td>
                  <td style="width: 6%;">&nbsp;'.$row["fund"].'</td>
                  <td style="width: 8%;">&nbsp;'.$row["admdate"].'</td>              
                  <td style="width: 8%;">&nbsp;'.number_format($debitcki,2).'</td>
                  <td style="width: 8%;">&nbsp;'.number_format($row["compensated"],2).'</td>
                  <td style="width: 8%;">&nbsp;'.number_format($diffcki,2).'</td>
                </tr> ';

                $diffi = $diffi+$diffcki;
                $debiti = $debiti+$debitcki;
                $compensated = $compensated+$row["compensated"];

                
               }





            }

            $i++;

            }

            if($an==0){
                $html .='<tr style="background: #cecece;" nobr="true">
                  <td style="text-align:center;font-weight: bold;" colspan="6">รวม</td>
                  <td style="font-weight: bold;" id="o1">&nbsp;'.number_format($debit,2).'</td>
                  <td style="font-weight: bold;" id="o2">&nbsp;'.number_format($compensated,2).'</td>
                  <td style="font-weight: bold;" id="o3">&nbsp;'.number_format($diff,2).'</td>
                </tr>';
            }else{
              $html .='<tr style="background: #cecece;" nobr="true">
                  <td style="text-align:center;font-weight: bold;" colspan="6">รวม</td>
                  <td style="font-weight: bold;" id="o1">&nbsp;'.number_format($debiti,2).'</td>
                  <td style="font-weight: bold;" id="o2">&nbsp;'.number_format($compensated,2).'</td>
                  <td style="font-weight: bold;" id="o3">&nbsp;'.number_format($diffi,2).'</td>
                </tr>';
            }

          }

          
       // }


      


$html .='</tbody>
</table>';


// ส่วนท้ายเซ็นชื่อ (การตัดลูกหนี้)
require_once __DIR__ . '/../../system/includes/signers_helper.php';
$signers_cfg = get_signers_config($conn);
$name1 = $signers_cfg['cut']['name1'] ?? '';
$name2 = $signers_cfg['cut']['name2'] ?? '';
$name3 = $signers_cfg['cut']['name3'] ?? '';
$pos1  = $signers_cfg['cut']['pos1'] ?? '';
$pos2  = $signers_cfg['cut']['pos2'] ?? '';
$pos3  = $signers_cfg['cut']['pos3'] ?? '';

$role1 = !empty($signers_cfg['cut']['role1']) ? nl2br($signers_cfg['cut']['role1']) : 'ผู้จัดทำรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ';
$role2 = !empty($signers_cfg['cut']['role2']) ? nl2br($signers_cfg['cut']['role2']) : 'ผู้ตรวจสอบรายงานลูกหนี้สิทธิ<br>กลุ่มงานประกันสุขภาพ';
$role3 = !empty($signers_cfg['cut']['role3']) ? nl2br($signers_cfg['cut']['role3']) : 'ผู้บันทึกลูกหนี้สิทธิ<br>กลุ่มงานบัญชี';

$html .='
<br>
<table style="width:100%;text-align:center;" nobr="true">
  <tr nobr="true">
    <td style="width:33.33%;"><br><br><br>('.$name1.')<br>'.(!empty($pos1) ? 'ตำแหน่ง '.$pos1.'<br>' : '').$role1.'</td>
    <td style="width:33.33%;"><br><br><br>('.$name2.')<br>'.(!empty($pos2) ? 'ตำแหน่ง '.$pos2.'<br>' : '').$role2.'</td>
    <td style="width:33.33%;"><br><br><br>('.$name3.')<br>'.(!empty($pos3) ? 'ตำแหน่ง '.$pos3.'<br>' : '').$role3.'</td>
  </tr>
</table>';


$pdf->writeHTML($html, true, false, false, false, '');

//Close and output PDF document

$pdf->Output('Debtor-rights.pdf', 'I');



//============================================================+

// END OF FILE

//============================================================+

//แปลงค่าเงินเป็นตัวหนังสือ
function thai_date_short($time){   // 19  ธ.ค. 2556a
                      
                      $d = substr($time,0,2);
                      $m = substr($time,3,2);
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
                      }

                      $y = substr($time,6,4);

                        $thai_date_return = $d.' '.$mtxt.' '.$y; 
                        return $thai_date_return;   
                    }

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

function dateThaiLong($strDate, $style = 0) {

        $strYear = date("Y", strtotime($strDate)) + 543;

        $strMonth = date("n", strtotime($strDate));

        $strDay = date("d", strtotime($strDate));

        $strMonthCut = Array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฏาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");

        $strMonthThai = $strMonthCut[$strMonth];



        if ($style == 0) {

            return "$strDay $strMonthThai $strYear";

        } else {

            return "$strMonthThai พ.ศ.$strYear";

        }

    }

