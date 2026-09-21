<?php


require '../../system/database_config/config.php';

$stmgetovlookup = $_GET['norep'] ?? '';

require_once('tcpdf_include.php');


class MYPDF extends TCPDF {



    //Page header

    public function Header() {

require '../../system/database_config/config.php';

        $stmgetovlookup = $_GET['norep'] ?? '';
        $mobile = '';

        if (strpos($stmgetovlookup, 'COCD') !== false) {
            // กรณีเป็นโค้ดแบบ 11072_COCDSTM_20250301 (มี "_")
            $sqlm = "SELECT dc.stm_doc,dc.vn,o.hn,o.ptname,o.clinic,o.vstdate,o.debit,sum(dc.amount) as compensated,(sum(dc.amount))-o.debit as diff,o.mobile
                  FROM imr_tb_seamless_dckd_ofc dc
                  LEFT JOIN imr_tb_debtor_rights_opd o on o.vn=dc.vn
                  WHERE stm_doc = '$stmgetovlookup' and dc.vn <> ''
                  GROUP BY dc.vn 
                  ORDER BY o.vstdate asc  LIMIT 1";

            $resultm = mysqli_query($conn, $sqlm);        

            if ($resultm && mysqli_num_rows($resultm) > 0) {
                while ($rowm = mysqli_fetch_assoc($resultm)) {
                    $mobile = $rowm["mobile"];
                }
            }

            $sql_rights = "SELECT DISTINCT o.accountname AS acc_name, o.accountcode AS acc_code
                FROM imr_tb_seamless_dckd_ofc dc
                LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = dc.vn
                WHERE dc.stm_doc = '$stmgetovlookup' AND dc.vn <> '' AND o.accountname IS NOT NULL AND o.accountname != ''";

        } else {
            // กรณีเป็นโค้ดธรรมดา เช่น DCKD6707020007
            $sqlm = "SELECT dc.rep,dc.vn,o.hn,o.ptname,o.clinic,o.vstdate,o.debit,sum(dc.compensated) as compensated,(sum(dc.compensated))-o.debit as diff,o.mobile
                  FROM imr_tb_seamless_dckd dc
                  LEFT JOIN imr_tb_debtor_rights_opd o on o.vn=dc.vn
                  WHERE rep = '$stmgetovlookup' and dc.vn <> ''
                  GROUP BY dc.vn 
                  ORDER BY o.vstdate asc  LIMIT 1";

            $resultm = mysqli_query($conn, $sqlm);        

            if ($resultm && mysqli_num_rows($resultm) > 0) {
                while ($rowm = mysqli_fetch_assoc($resultm)) {
                    $mobile = $rowm["mobile"];
                }
            }

            $sql_rights = "SELECT DISTINCT o.accountname AS acc_name, o.accountcode AS acc_code
                FROM imr_tb_seamless_dckd dc
                LEFT JOIN imr_tb_debtor_rights_opd o ON o.vn = dc.vn
                WHERE dc.rep = '$stmgetovlookup' AND dc.vn <> '' AND o.accountname IS NOT NULL AND o.accountname != ''";
        }

        $account_list = [];
        $res_rights = mysqli_query($conn, $sql_rights);
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

        $account_display = implode(', ', $account_list);


        // Logo
        $this->SetFont('thsarabun', '', 14);
        $this->SetY(14);
        $this->SetX(279);
        $this->Cell(20, 0, 'หน้า '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R');

        $this->SetFont('thsarabun', 'B', 18);

        $this->SetY(20);

        $this->SetX(122);

        $this->Cell(0, 15, 'ทะเบียนรายชื่อตัดลูกหนี้ไตเทียม', 0, false, 'L', 0, '', 0, false, 'M', 'M');

        $this->SetFont('thsarabun', '', 14);

        $this->SetY(10);

        $this->SetX(18);

        $this->Cell(0, 15, 'เลขที่ใบเสร็จ '.$mobile.'', 0, false, 'L', 0, '', 0, false, 'M', 'M');

        $this->SetY(10);

        $this->SetX(243);

        $this->Cell(0, 15, 'REP NO. '.$stmgetovlookup .'', 0, false, 'L', 0, '', 0, false, 'M', 'M');

        if (mb_strlen($account_display, 'UTF-8') > 130) {
            $this->SetFont('thsarabun', 'B', 11.5);
        } elseif (mb_strlen($account_display, 'UTF-8') > 90) {
            $this->SetFont('thsarabun', 'B', 13);
        } else {
            $this->SetFont('thsarabun', 'B', 14);
        }

        $this->SetY(27);
        $this->SetX(18);
        $this->Cell(0, 15, $account_display, 0, false, 'L', 0, '', 0, false, 'M', 'M');

    }



    // Page footer

    public function Footer() {


         //$this->SetY(-15);

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

$pdf->SetMargins(PDF_MARGIN_LEFT+3, PDF_MARGIN_TOP+26, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

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

$pdf->SetFont('thsarabun', '', 14);
$pdf->SetY(33);
$pdf->SetX(18);

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
        <th style="width: 20%;">&nbsp;ชื่อ-สกุล</th>
        <th style="width: 10%;">&nbsp;คลินิก</th>
        <th style="width: 30%;">&nbsp;สิทธิการเงิน</th>
        <th style="width: 10%;">&nbsp;วันที่</th>
        <th style="width: 8%;">&nbsp;ลูกหนี้สิทธิ</th>
        <th style="width: 8%;">&nbsp;ชดเชย</th>
        <th style="width: 8%;">&nbsp;ส่วนต่าง</th>
        </tr>
    </thead>
<tbody>
';


    if (strpos($stmgetovlookup, 'COCD') !== false) {
        // กรณีเป็นโค้ดแบบ 11072_COCDSTM_20250301 (มี "_")

        $sql = "SELECT dc.namepat, dc.stm_doc, dc.vn, SUM(dc.amount) AS compensated,dc.dttran
                FROM imr_tb_seamless_dckd_ofc dc 
                WHERE dc.stm_doc = '$stmgetovlookup' 
                GROUP BY dc.namepat,dc.vn
                ORDER BY dc.namepat ASC";

        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
          // output data of each row
          $i=1;$compensated=0;$diff=0;$debit=0;$debiti=0;$diffi=0;

          while ($row = mysqli_fetch_assoc($result)) {
                
                $vn = $row["vn"];
                $compensated1 = $row["compensated"];

                $dt = new DateTime($row["dttran"]);
                $dttran = $dt->format('d/m/') . ($dt->format('Y') + 543);
                
                $sql1 = "SELECT o.hn, o.ptname, o.clinic, o.debit, o.follow_money, o.mobile, o.accountname
                    FROM imr_tb_debtor_rights_opd o WHERE vn ='$vn' ";

                $result1 = mysqli_query($conn, $sql1);

                       if (mysqli_num_rows($result1) > 0) {
                            while ($row1 = mysqli_fetch_assoc($result1)) {
                                if (isset($row1["follow_money"]) && $row1["follow_money"] > 0) {
                                    $row1["debit"] = $row1["debit"] - $row1["follow_money"];
                                }
                                $diff1 = $compensated1-$row1["debit"];

                             
                                 $html .='<tr nobr="true">
                                      <td style="width: 6%;text-align:center;">'.$i.'</td>
                                      <td style="width: 20%;">&nbsp;'.$row["namepat"].'</td>
                                      <td style="width: 10%;">&nbsp;'.$row1["clinic"].'</td>
                                      <td style="width: 30%;">&nbsp;'.$row1["accountname"].'</td>
                                      <td style="width: 10%;">&nbsp;'.$dttran.'</td>              
                                      <td style="width: 8%;">&nbsp;' . number_format($row1["debit"], 2) . '</td>
                                      <td style="width: 8%;">&nbsp;' . number_format($compensated1, 2) . '</td>
                                      <td style="width: 8%;">&nbsp;' . number_format($diff1, 2) . '</td>
                                      </tr>
                                      ';



                                $diff = $diff + $diff1;
                                $debit = $debit + $row1["debit"];
                                $compensated = $compensated + $row["compensated"];
                            }
                        }
                   
                    $i++;

            }

    
                $html .='<tr style="background: #cecece;" nobr="true">
                  <td style="text-align:center;font-weight: bold;" colspan="5">รวม</td>
                  <td style="font-weight: bold;" id="o1">&nbsp;'.number_format($debit,2).'</td>
                  <td style="font-weight: bold;" id="o2">&nbsp;'.number_format($compensated,2).'</td>
                  <td style="font-weight: bold;" id="o3">&nbsp;'.number_format($diff,2).'</td>
                </tr>';

    

          }


    } else {
        // กรณีเป็นโค้ดธรรมดา เช่น DCKD6707020007

        $sqla = "SELECT dc.ptname, dc.rep, dc.vn, SUM(dc.compensated) AS compensated,dc.admdate
                FROM imr_tb_seamless_dckd dc 
                WHERE dc.rep = '$stmgetovlookup' 
                GROUP BY dc.ptname,dc.vn
                ORDER BY dc.ptname,dc.admdate ASC";

        $result = mysqli_query($conn, $sqla);

        if (mysqli_num_rows($result) > 0) {
          // output data of each row
          $i=1;$compensated=0;$diff=0;$debit=0;$debiti=0;$diffi=0;

          while ($row = mysqli_fetch_assoc($result)) {
                
                $vn = $row["vn"];
                $compensated1 = $row["compensated"];
                
                $sql1 = "SELECT o.hn, o.ptname, o.clinic, o.debit, o.follow_money, o.mobile, o.accountname
                    FROM imr_tb_debtor_rights_opd o WHERE vn ='$vn' ";

                $result1 = mysqli_query($conn, $sql1);

                       if (mysqli_num_rows($result1) > 0) {
                            while ($row1 = mysqli_fetch_assoc($result1)) {
                                if (isset($row1["follow_money"]) && $row1["follow_money"] > 0) {
                                    $row1["debit"] = $row1["debit"] - $row1["follow_money"];
                                }
                                $diff1 = $compensated1-$row1["debit"];


                                 $html .='<tr nobr="true">
                                      <td style="width: 6%;text-align:center;">'.$i.'</td>
                                      <td style="width: 20%;">&nbsp;'.$row["ptname"].'</td>
                                      <td style="width: 10%;">&nbsp;'.$row1["clinic"].'</td>
                                      <td style="width: 30%;">&nbsp;'.$row1["accountname"].'</td>
                                      <td style="width: 10%;">&nbsp;'.$row["admdate"].'</td>              
                                      <td style="width: 8%;">&nbsp;' . number_format($row1["debit"], 2) . '</td>
                                      <td style="width: 8%;">&nbsp;' . number_format($compensated1, 2) . '</td>
                                      <td style="width: 8%;">&nbsp;' . number_format($diff1, 2) . '</td>
                                      </tr>
                                      ';



                                $diff = $diff + $diff1;
                                $debit = $debit + $row1["debit"];
                                $compensated = $compensated + $row["compensated"];
                            }
                        }
                   
                    $i++;

            }

    
                $html .='<tr style="background: #cecece;" nobr="true">
                  <td style="text-align:center;font-weight: bold;" colspan="5">รวม</td>
                  <td style="font-weight: bold;" id="o1">&nbsp;'.number_format($debit,2).'</td>
                  <td style="font-weight: bold;" id="o2">&nbsp;'.number_format($compensated,2).'</td>
                  <td style="font-weight: bold;" id="o3">&nbsp;'.number_format($diff,2).'</td>
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
<p></p><p></p>
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

