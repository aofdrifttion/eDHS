<?php
header('Content-Type: text/html; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require './database_config/config.php';
require_once './database_config/db_helper.php';
require_once __DIR__ . '/includes/cr_migration_helper.php';
require_once __DIR__ . '/includes/sss_migration_helper.php';

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

if (function_exists('system_log')) {
    $pageName = basename($_SERVER['PHP_SELF']);
    system_log($conn, 'เปิดดูข้อมูล (View)', 'VIEW', "ผู้ใช้เปิดดูหน้ารายงาน/ข้อมูล: " . $pageName);
}


// --- โครงสร้างส่วนบนสุดของไฟล์พิมพ์รายงาน ---
$monthtxt = $_SESSION['monthtxt'];
// เก็บค่า ID เต็มๆ จากหน้าสรุปไว้เช็คเงื่อนไขโรคไต (เช่น '1102050101.401_KIDNEY' หรือ '1102050101.401')
$raw_oid = isset($_SESSION['oid']) ? $_SESSION['oid'] : '';

if(isset($_SESSION['oid'])) {
    $oid = $_SESSION['oid'];

    // ดักจับและตัดสตริงทิ้งเพื่อให้เหลือแค่รหัสผังบัญชีเพียวๆ ไปยิงคำสั่ง SQL prepare
    $oid_parts = explode('_', $oid);
    $oid = $oid_parts[0];
}

if(isset($_SESSION['iid'])) {
    $iid = $_SESSION['iid'];
    $iid_parts = explode('_', $iid);
    $iid = $iid_parts[0];
}

$typecc = isset($_POST['typecc']) ? trim($_POST['typecc']) : (isset($_SESSION['typecc']) ? trim($_SESSION['typecc']) : '');
if (empty($typecc)) {
    $typecc = (!empty($iid) && empty($oid)) ? 'IPD' : 'OPD';
}
$current_code = ($typecc == 'IPD') ? ($iid ?? '') : ($oid ?? '');
$account_name = '';

if (!empty($current_code)) {
    if ($typecc == 'IPD') {
        $stmt_acc = $conn->prepare("SELECT accountname FROM imr_tb_debtor_rights_ipd WHERE accountcode = ? LIMIT 1");
        if ($stmt_acc) {
            $stmt_acc->bind_param("s", $current_code);
            $stmt_acc->execute();
            $res_acc = $stmt_acc->get_result();
            if ($r_acc = $res_acc->fetch_assoc()) {
                $account_name = $r_acc['accountname'];
            }
            $stmt_acc->close();
        }
    } else {
        $stmt_acc = $conn->prepare("SELECT accountname FROM imr_tb_debtor_rights_opd WHERE accountcode = ? LIMIT 1");
        if ($stmt_acc) {
            $stmt_acc->bind_param("s", $current_code);
            $stmt_acc->execute();
            $res_acc = $stmt_acc->get_result();
            if ($r_acc = $res_acc->fetch_assoc()) {
                $account_name = $r_acc['accountname'];
                if (strpos($raw_oid, '_KIDNEY') !== false) {
                    $account_name = str_replace('ลูกหนี้ค่ารักษา', 'ไตวายเรื้อรัง', $account_name);
                }
            }
            $stmt_acc->close();
        }
    }
}

// Fallback: หากยังไม่ได้ชื่อสิทธิ ให้ดึงจากรายการแรกใน $_POST['select']
if (empty($account_name) && !empty($_POST['select']) && is_array($_POST['select'])) {
    $first_val = $_POST['select'][0];
    if ($typecc == 'IPD') {
        $stmt_fb = $conn->prepare("SELECT accountname, accountcode FROM imr_tb_debtor_rights_ipd WHERE an = ? LIMIT 1");
        if ($stmt_fb) {
            $stmt_fb->bind_param("s", $first_val);
            $stmt_fb->execute();
            $res_fb = $stmt_fb->get_result();
            if ($r_fb = $res_fb->fetch_assoc()) {
                $account_name = $r_fb['accountname'];
                if (empty($current_code)) $current_code = $r_fb['accountcode'];
            }
            $stmt_fb->close();
        }
    } else {
        $stmt_fb = $conn->prepare("SELECT accountname, accountcode, pttypename FROM imr_tb_debtor_rights_opd WHERE vn = ? LIMIT 1");
        if ($stmt_fb) {
            $stmt_fb->bind_param("s", $first_val);
            $stmt_fb->execute();
            $res_fb = $stmt_fb->get_result();
            if ($r_fb = $res_fb->fetch_assoc()) {
                $account_name = $r_fb['accountname'];
                if (strpos($raw_oid, '_KIDNEY') !== false || strpos($r_fb['pttypename'], 'ฟอกไต') !== false || strpos($r_fb['pttypename'], 'ไต') !== false) {
                    $account_name = str_replace('ลูกหนี้ค่ารักษา', 'ไตวายเรื้อรัง', $account_name);
                }
                if (empty($current_code)) $current_code = $r_fb['accountcode'];
            }
            $stmt_fb->close();
        }
    }
}

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
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
  <title>ตั้งลูกหนี้ / VlookUP</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

  <style>
      @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap');

      body {
          background-color: #FAFAFA;
          font-family: "Sarabun", sans-serif;
          font-size: 14px;
      }

      .page {
          width: 29.7cm;
          min-height: 21cm;
          padding: 1cm;
          margin: 1cm auto;
          border: 1px #D3D3D3 solid;
          border-radius: 5px;
          background: white;
          box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
          position: relative;
      }

      .header-content {
          display: flex;
          justify-content: space-between;
          align-items: flex-end;
          width: 100%;
          padding-bottom: 10px;
          padding-top: 15px;
      }

      .header-content h3 {
          margin: 0;
          text-align: left;
          font-weight: bold;
          font-size: 16px;
      }

      .header-content p {
          margin: 0;
          text-align: right;
          white-space: nowrap;
      }

      table {
        width: 100%;
        border-collapse: collapse;
      }

      th, td {
          padding: 4px 5px !important;
          vertical-align: middle !important;
          border: 1px solid #000 !important;
          line-height: 1.3;
      }

      thead {
          display: table-header-group;
      }

      tr.report-header th {
          border: none !important;
          background-color: transparent !important;
          padding: 0 !important;
          vertical-align: bottom !important;
      }

      tr.column-header th {
          background-color: #f0f0f0 !important;
          color: #000 !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
          text-align: center;
          font-weight: bold;
          border: 1px solid #000 !important;
      }

      @media print {
          @page {
              size: A4 landscape;
              margin: 1cm;
              margin-top: 1.2cm;
          }

          body {
              margin: 0;
              padding: 0;
              background-color: white;
          }

          body, table, th, td, p, span, div {
              font-size: 12px !important;
              color: #000 !important;
          }

          .header-content h3 {
              font-size: 14px !important;
          }

          .page {
              width: 100% !important;
              margin: 0 !important;
              padding: 0 !important;
              border: none !important;
              box-shadow: none !important;
              min-height: auto !important;
          }

          .no-print { display: none !important; }
          table, th, td { border: 1px solid #000 !important; }
          tr.report-header th { border: none !important; }
          tr { page-break-inside: avoid; }

          .signature-box {
              page-break-inside: avoid;
              break-inside: avoid;
              margin-top: 25px;
              width: 100%;
          }
      }
  </style>
</head>
<body>

    <div class="book">
      <div class="page" id="page">

        <table class="table" style="border: none !important;">
            <thead>
                <tr class="report-header">
                    <th colspan="8">
                        <div class="header-content">
                            <div>
                                <h3><b>ทะเบียนรายชื่อลูกหนี้รายตัวผู้ป่วย <?php echo htmlspecialchars($typecc, ENT_QUOTES, 'UTF-8'); ?></b></h3>
                                <?php if (!empty($account_name) || !empty($current_code)): ?>
                                    <div style="font-size: 13.5px; font-weight: bold; margin-top: 4px; color: #000;">
                                        <?php echo htmlspecialchars($account_name, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($current_code)): ?>
                                            &nbsp;&nbsp;รหัส <?php echo htmlspecialchars($current_code, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p>ข้อมูล ณ วันที่ <?php echo DateThai($strDate); ?></p>
                            </div>
                        </div>
                    </th>
                </tr>

                <tr class="column-header">
                    <th style="width: 5%;">ลำดับ</th>
                    <th style="width: 27%;">ชื่อ-สกุล</th>
                    <th style="width: 11%;">วันที่</th>
                    <th style="width: 11%;">ภาระหนี้</th>
                    <th style="width: 11%;">ชดเชย</th>
                    <th style="width: 11%;">ส่วนต่าง</th>
                    <th style="width: 11%;">เลขที่ใบเสร็จ</th>
                    <th style="width: 13%;">วันที่ใบเสร็จ</th>
                </tr>
            </thead>
            <tbody>
                  <?php
                  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select'])) {
                      $selectedValues = $_POST['select'];
                      $i = 0;
                      $income1 = 0; $incomediff1 = 0; $debit1 = 0; $follow_money1 = 0;

                      // เตรียมข้อมูลสรุปการโอนยอด CR ประจำเดือน
                      $cr_config = get_active_cr_config($conn);
                      $is_cr_effective = is_cr_effective_for_month($cr_config, $monthtxt);
                      $auto_split = is_cr_auto_split_enabled($cr_config, $typecc);
                      $my_hospcode = isset($configData['hospcode']) ? $configData['hospcode'] : ($hospcode ?? '');

                      $cr_visits_map = [];
                      if ($is_cr_effective && $auto_split) {
                          $cr_summary = get_cr_splitting_summary_for_month($conn, $conn2, $monthtxt, $typecc, $cr_config, $my_hospcode);
                          if ($cr_summary && !empty($cr_summary['cr_visits'])) {
                              $cr_visits_map = $cr_summary['cr_visits'];
                          }
                      }

                      // โหลดการตั้งค่า SSS Instrument Cross-Account Splitting
                      $sss_config = get_active_sss_config($conn);
                      $is_sss_effective = is_sss_effective_for_month($sss_config, $monthtxt);
                      $auto_split_sss = is_sss_auto_split_enabled($sss_config, $typecc);

                      $sss_visits_map = [];
                      if ($is_sss_effective && $auto_split_sss) {
                          $sss_summary = get_sss_splitting_summary_for_month($conn, $conn2, $monthtxt, $typecc, $sss_config, $my_hospcode);
                          if ($sss_summary && !empty($sss_summary['sss_visits'])) {
                              $sss_visits_map = $sss_summary['sss_visits'];
                          }
                      }

                      // Pre-load CR & SSS Breakdown Data สำหรับเคสที่เลือกทั้งหมด
                      $safe_selected = "'" . implode("','", array_map(function($v) use ($conn) { return mysqli_real_escape_string($conn, $v); }, $selectedValues)) . "'";
                      $cr_bd_map = [];
                      if (!empty($safe_selected)) {
                          $q_cr_bd = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_cr_breakdown WHERE visit_type = '$typecc' AND vn IN ($safe_selected) ORDER BY CASE WHEN bill IS NOT NULL AND TRIM(bill) != '' AND TRIM(bill) != '-' THEN 1 ELSE 2 END ASC, CASE WHEN compensated IS NOT NULL AND compensated > 0 THEN 1 ELSE 2 END ASC, id DESC");
                          if ($q_cr_bd) {
                              while ($bd = mysqli_fetch_assoc($q_cr_bd)) {
                                  $v = $bd['vn'];
                                  if (!isset($cr_bd_map[$v]) || (!empty($bd['bill']) && empty($cr_bd_map[$v]['bill']))) {
                                      $cr_bd_map[$v] = $bd;
                                  }
                              }
                          }
                      }
                      $sss_bd_map = [];
                      if (!empty($safe_selected)) {
                          $q_sss_bd = mysqli_query($conn, "SELECT vn, item_amount, general_remain_amount, compensated, bill, billdate, settle_status FROM imr_tb_debtor_sss_breakdown WHERE visit_type = '$typecc' AND vn IN ($safe_selected) ORDER BY CASE WHEN bill IS NOT NULL AND TRIM(bill) != '' AND TRIM(bill) != '-' THEN 1 ELSE 2 END ASC, CASE WHEN compensated IS NOT NULL AND compensated > 0 THEN 1 ELSE 2 END ASC, id DESC");
                          if ($q_sss_bd) {
                              while ($bd = mysqli_fetch_assoc($q_sss_bd)) {
                                  $v = $bd['vn'];
                                  if (!isset($sss_bd_map[$v]) || (!empty($bd['bill']) && empty($sss_bd_map[$v]['bill']))) {
                                      $sss_bd_map[$v] = $bd;
                                  }
                              }
                          }
                      }

                      // Helper สำหรับจัดรูปแบบวันที่ใบเสร็จเป็น dd/mm/yyyy (พ.ศ.)
                      $format_report_billdate = function($d_raw) {
                          if (empty($d_raw) || $d_raw === '-') return '';
                          $d_raw = trim((string)$d_raw);
                          if (strpos($d_raw, '/') !== false) {
                              return $d_raw;
                          }
                          if (strpos($d_raw, '-') !== false) {
                              $ts = strtotime($d_raw);
                              if ($ts !== false) {
                                  $d = date('d', $ts);
                                  $m = date('m', $ts);
                                  $y = (int)date('Y', $ts) + 543;
                                  return "$d/$m/$y";
                              }
                          }
                          return $d_raw;
                      };

                      // Helper สำหรับ fallback ดึงใบเสร็จจาก mobile
                      $extract_mobile_bill = function($mobile_val) {
                          $mobile_val = trim((string)$mobile_val);
                          $last_dash = strrpos($mobile_val, '-');
                          $b = $mobile_val;
                          $bd = '';
                          if ($last_dash !== false && $mobile_val !== '-') {
                              $potential_bill = substr($mobile_val, 0, $last_dash);
                              $potential_date = substr($mobile_val, $last_dash + 1);
                              if (strpos($potential_date, '/') !== false) {
                                  $b = $potential_bill;
                                  $bd = trim($potential_date);
                              }
                          }
                          return ['bill' => $b, 'billdate' => $bd];
                      };

                      foreach ($selectedValues as $value) {

                            // ปรับเปลี่ยนเงื่อนไขคำสั่ง SQL ให้ค้นหาด้วย accountcode และรองรับผัง CR และ SSS
                            if ($typecc == "IPD") {
                                if ($iid == "1102050101.217" || $iid == "1102050101.310") {
                                    $sql = "SELECT o.an AS id, o.hn, o.cid, o.ptname, o.dchdate AS date, (o.income) as income, (o.income - COALESCE(o.original_debit, o.debit)) as incomediff, (o.debit) as debit, o.follow_money, o.bill, o.billdate, pttype_eclaim_name,o.rcpt_money,o.mobile,
                                            (SELECT SUM(compensated) FROM imr_tb_check_invoice WHERE vn = o.an) as compensated,
                                            (SELECT SUM(compensated) FROM imr_tb_seamless_dckd WHERE vn = o.an) as ckd_compensated,
                                            (SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc WHERE vn = o.an) as ofc_compensated
                                            FROM imr_tb_debtor_rights_ipd o
                                            WHERE monthtxt = ? AND an = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("ss", $monthtxt, $value);
                                } else {
                                    $sql = "SELECT o.an AS id, o.hn, o.cid, o.ptname, o.dchdate AS date, (o.income) as income, (o.income - COALESCE(o.original_debit, o.debit)) as incomediff, (o.debit) as debit, o.follow_money, o.bill, o.billdate, pttype_eclaim_name,o.rcpt_money,o.mobile,
                                            (SELECT SUM(compensated) FROM imr_tb_check_invoice WHERE vn = o.an) as compensated,
                                            (SELECT SUM(compensated) FROM imr_tb_seamless_dckd WHERE vn = o.an) as ckd_compensated,
                                            (SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc WHERE vn = o.an) as ofc_compensated
                                            FROM imr_tb_debtor_rights_ipd o
                                            WHERE monthtxt = ? AND accountcode = ? AND an = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("sss", $monthtxt, $iid, $value);
                                }
                            } else {

                                // [ฝั่ง OPD] ดักเช็คสิทธิจากตัวแปร $raw_oid ที่ยังไม่ตัดคำ เพื่อแยกประเภทคนไข้โรคไตออกจากคนไข้ปกติของทุกผังบัญชี
                                $extra_condition = "";
                                if (strpos($raw_oid, '_KIDNEY') !== false) {
                                    // ถ้าเป็นแถวไต: ดึงเฉพาะคนไข้ที่มีคำว่า ฟอกไต/ไต ของผังบัญชีนั้นๆ
                                    $extra_condition = " AND (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%')";
                                } else {
                                    // ถ้าเป็นแถวสิทธิปกติ: ต้องคัดคนไข้ที่มีคำว่า ฟอกไต/ไต ออกไปทั้งหมด ไม่ให้ยอดมาปนกัน
                                    $extra_condition = " AND (o.pttypename NOT LIKE '%ฟอกไต%' AND o.pttypename NOT LIKE '%ไต%')";
                                }

                                if ($oid == "1102050101.216" || $oid == "1102050101.309") {
                                    $sql = "SELECT o.vn AS id, o.hn, o.cid, o.ptname, o.vstdate AS date, (o.income) as income, (o.income - COALESCE(o.original_debit, o.debit)) as incomediff, (o.debit) as debit, o.follow_money, o.bill, o.billdate,
                                            CASE
                                                WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', 'ไตวายเรื้อรัง')
                                                ELSE o.accountname
                                            END AS pttype_eclaim_name,
                                            o.rcpt_money,o.mobile,
                                            (SELECT SUM(compensated) FROM imr_tb_check_invoice WHERE vn = o.vn) as compensated,
                                            (SELECT SUM(compensated) FROM imr_tb_seamless_dckd WHERE vn = o.vn) as ckd_compensated,
                                            (SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc WHERE vn = o.vn) as ofc_compensated
                                            FROM imr_tb_debtor_rights_opd o
                                            WHERE monthtxt = ? AND vn = ? $extra_condition";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("ss", $monthtxt, $value);
                                } else {
                                    $sql = "SELECT o.vn AS id, o.hn, o.cid, o.ptname, o.vstdate AS date, (o.income) as income, (o.income - COALESCE(o.original_debit, o.debit)) as incomediff, (o.debit) as debit, o.follow_money, o.bill, o.billdate,
                                            CASE
                                                WHEN (o.pttypename LIKE '%ฟอกไต%' OR o.pttypename LIKE '%ไต%') THEN REPLACE(o.accountname, 'ลูกหนี้ค่ารักษา', 'ไตวายเรื้อรัง')
                                                ELSE o.accountname
                                            END AS pttype_eclaim_name,
                                            o.rcpt_money,o.mobile,
                                            (SELECT SUM(compensated) FROM imr_tb_check_invoice WHERE vn = o.vn) as compensated,
                                            (SELECT SUM(compensated) FROM imr_tb_seamless_dckd WHERE vn = o.vn) as ckd_compensated,
                                            (SELECT SUM(amount) FROM imr_tb_seamless_dckd_ofc WHERE vn = o.vn) as ofc_compensated
                                            FROM imr_tb_debtor_rights_opd o
                                            WHERE monthtxt = ? AND accountcode = ? AND vn = ? $extra_condition";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("sss", $monthtxt, $oid, $value);
                                }
                            }

                          $stmt->execute();
                          $result = $stmt->get_result();

                          if ($result->num_rows > 0) {
                              while ($row = $result->fetch_assoc()) {
                                  $i++;
                                  $dateq = $row["date"];
                                  $ref_id = $row['id'];

                                  // ============================================================
                                  // หาภาระหนี้ และจัดการกรณีตัดแยกค่ารักษา CR และ SSS ข้ามผัง
                                  // ============================================================
                                  $is_cr_child_account = ($typecc == 'IPD') ? ($iid == '1102050101.217') : ($oid == '1102050101.216');
                                  $is_parent_split_acc = ($typecc == 'IPD') ? ($iid == '1102050101.202') : in_array($oid, ['1102050101.201', '1102050101.209', '1102050101.203']);

                                  $is_sss_child_account = ($typecc == 'IPD') ? ($iid == '1102050101.310') : ($oid == '1102050101.309');
                                  $is_sss_parent_split_acc = ($typecc == 'IPD') ? in_array($iid, ['1102050101.302', '1102050101.304', '1102050101.308']) : in_array($oid, ['1102050101.301', '1102050101.303', '1102050101.307']);

                                  $base_debt = (float)cleanNum($row["debit"]);

                                  if ($is_cr_child_account && isset($cr_visits_map[$ref_id])) {
                                      $base_debt = (float)cleanNum($cr_visits_map[$ref_id]['cr_amount']);
                                  } elseif ($is_parent_split_acc && isset($cr_visits_map[$ref_id])) {
                                      $base_debt = (float)cleanNum($cr_visits_map[$ref_id]['general_remain_amount']);
                                  } elseif ($is_sss_child_account && isset($sss_visits_map[$ref_id])) {
                                      $base_debt = (float)cleanNum($sss_visits_map[$ref_id]['sss_amount']);
                                  } elseif ($is_sss_parent_split_acc && isset($sss_visits_map[$ref_id])) {
                                      $base_debt = (float)cleanNum($sss_visits_map[$ref_id]['general_remain_amount']);
                                  }

                                   // จัดการกรณีแบ่งจ่าย (Last Receipt Rule)
                                   $sql_check_part = "SELECT * FROM imr_tb_payment_history WHERE ref_vn_an = '$ref_id' ORDER BY id ASC";
                                   $res_part = $conn->query($sql_check_part);
                                   $total_payments = $res_part ? $res_part->num_rows : 0;

                                   if ($total_payments > 0) {
                                       $all_parts = [];
                                       while ($p = $res_part->fetch_assoc()) {
                                           $all_parts[] = $p;
                                       }

                                       $latest_part = end($all_parts);
                                       $pay_amount_latest = (float)cleanNum($latest_part['pay_amount']);

                                       // สำหรับเคสแบ่งชำระ: ภาระหนี้และยอดชดเชยในใบเสร็จงวดล่าสุดนี้ คือยอดเงินของงวดนั้นๆ
                                       $income = $pay_amount_latest;
                                       $follow_money = $pay_amount_latest;
                                       $incomediff = 0.0;

                                       // กฎใบเสร็จงวดสุดท้าย (Last Receipt Rule)
                                       $bill = $latest_part['bill_no'];
                                       $billdate = "";
                                       if(!empty($latest_part['bill_date'])){
                                            $d = date('d', strtotime($latest_part['bill_date']));
                                            $m = date('m', strtotime($latest_part['bill_date']));
                                            $y = date('Y', strtotime($latest_part['bill_date'])) + 543;
                                            $billdate = "$d/$m/$y";
                                       }

                                   } else {
                                       $income = $base_debt;

                                       // 🌟 ตรวจสอบและดึงข้อมูลยอดชดเชย + เลขที่/วันที่ใบเสร็จ ตามประเภทผังบัญชี (Rule 117)
                                       if ($is_cr_child_account) {
                                           // [ผังลูก CR (.216 / .217)]
                                           if (isset($cr_bd_map[$ref_id])) {
                                               $follow_money = (float)cleanNum($cr_bd_map[$ref_id]['compensated']);
                                               $bill = $cr_bd_map[$ref_id]['bill'] ?? '';
                                               $billdate = $format_report_billdate($cr_bd_map[$ref_id]['billdate'] ?? '');
                                           } else {
                                               $raw_comp = (float)$row["compensated"] + (float)$row["ckd_compensated"] + (float)$row["ofc_compensated"];
                                               if ($raw_comp == 0) $raw_comp = (float)$row["follow_money"];
                                               $follow_money = isset($cr_visits_map[$ref_id]) ? min($raw_comp, (float)$cr_visits_map[$ref_id]['cr_amount']) : $raw_comp;

                                               if (!empty($row["bill"])) {
                                                   $bill = $row["bill"];
                                                   $billdate = $format_report_billdate($row["billdate"]);
                                               } else {
                                                   $mb = $extract_mobile_bill($row["mobile"]);
                                                   $bill = $mb['bill'];
                                                   $billdate = $mb['billdate'];
                                               }
                                           }
                                       } elseif ($is_sss_child_account) {
                                           // [ผังลูก SSS (.309 / .310)]
                                           if (isset($sss_bd_map[$ref_id])) {
                                               $follow_money = (float)cleanNum($sss_bd_map[$ref_id]['compensated']);
                                               $bill = $sss_bd_map[$ref_id]['bill'] ?? '';
                                               $billdate = $format_report_billdate($sss_bd_map[$ref_id]['billdate'] ?? '');
                                           } else {
                                               $raw_comp = (float)$row["compensated"] + (float)$row["ckd_compensated"] + (float)$row["ofc_compensated"];
                                               if ($raw_comp == 0) $raw_comp = (float)$row["follow_money"];
                                               $follow_money = isset($sss_visits_map[$ref_id]) ? min($raw_comp, (float)$sss_visits_map[$ref_id]['sss_amount']) : $raw_comp;

                                               if (!empty($row["bill"])) {
                                                   $bill = $row["bill"];
                                                   $billdate = $format_report_billdate($row["billdate"]);
                                               } else {
                                                   $mb = $extract_mobile_bill($row["mobile"]);
                                                   $bill = $mb['bill'];
                                                   $billdate = $mb['billdate'];
                                               }
                                           }
                                       } elseif ($is_parent_split_acc && isset($cr_visits_map[$ref_id])) {
                                           // [ผังแม่ CR (.201, .209, .203, .202)] หักเงินชดเชยที่โอนไปผังลูก CR ออก
                                           $raw_comp = (float)$row["compensated"] + (float)$row["ckd_compensated"] + (float)$row["ofc_compensated"];
                                           if ($raw_comp == 0) $raw_comp = (float)$row["follow_money"];
                                           $cr_comp = isset($cr_bd_map[$ref_id]) ? (float)cleanNum($cr_bd_map[$ref_id]['compensated']) : min($raw_comp, (float)$cr_visits_map[$ref_id]['cr_amount']);
                                           $mother_comp = max(0, $raw_comp - $cr_comp);
                                           $follow_money = $mother_comp;

                                           // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                                           if ($mother_comp <= 0 || ($income - $mother_comp) > 0.01) {
                                               $bill = '';
                                               $billdate = '';
                                           } else {
                                               if (!empty($row["bill"])) {
                                                   $bill = $row["bill"];
                                                   $billdate = $format_report_billdate($row["billdate"]);
                                               } else {
                                                   $mb = $extract_mobile_bill($row["mobile"]);
                                                   $bill = $mb['bill'];
                                                   $billdate = $mb['billdate'];
                                               }
                                           }
                                       } elseif ($is_sss_parent_split_acc && isset($sss_visits_map[$ref_id])) {
                                           // [ผังแม่ SSS (.301, .302, .303, .304, .307, .308)] หักเงินชดเชยที่โอนไปผังลูก SSS ออก
                                           $raw_comp = (float)$row["compensated"] + (float)$row["ckd_compensated"] + (float)$row["ofc_compensated"];
                                           if ($raw_comp == 0) $raw_comp = (float)$row["follow_money"];
                                           $sss_comp = isset($sss_bd_map[$ref_id]) ? (float)cleanNum($sss_bd_map[$ref_id]['compensated']) : min($raw_comp, (float)$sss_visits_map[$ref_id]['sss_amount']);
                                           $mother_comp = max(0, $raw_comp - $sss_comp);
                                           $follow_money = $mother_comp;

                                           // ถ้าหนี้คงเหลือของผังแม่ยังไม่ได้ตัดใบเสร็จเฉพาะส่วนผังแม่ ให้เคลียร์ bill และ billdate เพื่อรอการตัดหนี้
                                           if ($mother_comp <= 0 || ($income - $mother_comp) > 0.01) {
                                               $bill = '';
                                               $billdate = '';
                                           } else {
                                               if (!empty($row["bill"])) {
                                                   $bill = $row["bill"];
                                                   $billdate = $format_report_billdate($row["billdate"]);
                                               } else {
                                                   $mb = $extract_mobile_bill($row["mobile"]);
                                                   $bill = $mb['bill'];
                                                   $billdate = $mb['billdate'];
                                               }
                                           }
                                       } else {
                                           // ผังบัญชีปกติ
                                           $seamless_dckd_total = (float)$row["ckd_compensated"] + (float)$row["ofc_compensated"];
                                           $follow_money = (float)$row["compensated"] + $seamless_dckd_total;
                                           if ($follow_money == 0) {
                                               $follow_money = (float)$row["follow_money"];
                                           }

                                           if (!empty($row["bill"])) {
                                               $bill = $row["bill"];
                                               $billdate = $format_report_billdate($row["billdate"]);
                                           } else {
                                               $mb = $extract_mobile_bill($row["mobile"]);
                                               $bill = $mb['bill'];
                                               $billdate = $mb['billdate'];
                                           }
                                       }

                                       // จัด Format เลขที่ใบเสร็จ
                                       if (!empty($bill) && function_exists('format_debtor_bill_no')) {
                                           $bill = format_debtor_bill_no($bill);
                                       }

                                       $incomediff = $follow_money - $income;
                                   }

                                  echo '<tr>
                                          <td style="text-align: center;">' . $i . '</td>
                                          <td>' . $row["ptname"] . '</td>
                                          <td style="text-align: center;">' . $dateq . '</td>
                                          <td style="text-align: right;">' . number_format($income, 2) . '</td>
                                          <td style="text-align: right;">' . number_format($follow_money, 2) . '</td>
                                          <td style="text-align: right;">' . number_format($incomediff, 2) . '</td>
                                          <td style="text-align: center;">' . $bill . '</td>
                                          <td style="text-align: center;">' . $billdate . '</td>
                                        </tr>';

                                  $income1 += $income;
                                  $incomediff1 += $incomediff;
                                  $follow_money1 += $follow_money;
                              }
                          }
                      }

                      echo '<tr style="font-weight: bold; background-color: #f9f9f9;">
                          <td style="text-align: center;" colspan="3">รวมทั้งหมด (' . $i . ' รายการ)</td>
                          <td style="text-align: right;">' . number_format($income1, 2) . '</td>
                          <td style="text-align: right;">' . number_format($follow_money1, 2) . '</td>
                          <td style="text-align: right;">' . number_format($incomediff1, 2) . '</td>
                          <td colspan="2"></td>
                        </tr>';

                  } else {
                      echo "<tr><td colspan='8' style='text-align:center; color:red;'>ไม่พบข้อมูลที่เลือก</td></tr>";
                  }
                  ?>
              </tbody>
        </table>

        <?php
        require_once __DIR__ . '/includes/signers_helper.php';
        $signers_cfg = get_signers_config($conn);

        $item_name1 = $signers_cfg['item']['name1'] ?? '';
        $item_name2 = $signers_cfg['item']['name2'] ?? '';
        $item_name3 = $signers_cfg['item']['name3'] ?? '';

        $item_pos1 = $signers_cfg['item']['pos1'] ?? '';
        $item_pos2 = $signers_cfg['item']['pos2'] ?? '';
        $item_pos3 = $signers_cfg['item']['pos3'] ?? '';

        $item_role1 = !empty($signers_cfg['item']['role1']) ? nl2br(htmlspecialchars($signers_cfg['item']['role1'], ENT_QUOTES, 'UTF-8')) : 'ผู้จัดทำรายงานลูกหนี้สิทธิ';
        $item_role2 = !empty($signers_cfg['item']['role2']) ? nl2br(htmlspecialchars($signers_cfg['item']['role2'], ENT_QUOTES, 'UTF-8')) : 'ผู้ตรวจสอบรายงานลูกหนี้สิทธิ';
        $item_role3 = !empty($signers_cfg['item']['role3']) ? nl2br(htmlspecialchars($signers_cfg['item']['role3'], ENT_QUOTES, 'UTF-8')) : 'ผู้บันทึกลูกหนี้สิทธิ';
        ?>
        <div class="signature-box">
            <table style="width:100%; text-align:center; border: none !important; margin-top: 15px;">
                <tr style="border: none !important;">
                    <td style="border: none !important; width: 33%; padding-top: 30px;">ลงชื่อ .......................................................</td>
                    <td style="border: none !important; width: 34%; padding-top: 30px;">ลงชื่อ .......................................................</td>
                    <td style="border: none !important; width: 33%; padding-top: 30px;">ลงชื่อ .......................................................</td>
                </tr>
                <tr style="border: none !important;">
                    <td style="border: none !important;">(<?php echo !empty($item_name1) ? htmlspecialchars($item_name1, ENT_QUOTES, 'UTF-8') : '........................................'; ?>)</td>
                    <td style="border: none !important;">(<?php echo !empty($item_name2) ? htmlspecialchars($item_name2, ENT_QUOTES, 'UTF-8') : '........................................'; ?>)</td>
                    <td style="border: none !important;">(<?php echo !empty($item_name3) ? htmlspecialchars($item_name3, ENT_QUOTES, 'UTF-8') : '........................................'; ?>)</td>
                </tr>
                <tr style="border: none !important; font-size: 13px; color: #555;">
                    <td style="border: none !important;"><?php echo !empty($item_pos1) ? 'ตำแหน่ง ' . htmlspecialchars($item_pos1, ENT_QUOTES, 'UTF-8') : ''; ?></td>
                    <td style="border: none !important;"><?php echo !empty($item_pos2) ? 'ตำแหน่ง ' . htmlspecialchars($item_pos2, ENT_QUOTES, 'UTF-8') : ''; ?></td>
                    <td style="border: none !important;"><?php echo !empty($item_pos3) ? 'ตำแหน่ง ' . htmlspecialchars($item_pos3, ENT_QUOTES, 'UTF-8') : ''; ?></td>
                </tr>
                <tr style="border: none !important;">
                    <td style="border: none !important;"><?php echo $item_role1; ?></td>
                    <td style="border: none !important;"><?php echo $item_role2; ?></td>
                    <td style="border: none !important;"><?php echo $item_role3; ?></td>
                </tr>
            </table>
        </div>

      </div>
    </div>

    <div class="no-print" style="position: fixed; top: 20px; left: 20px; text-align: center; background-color: #fff; width: 200px; border: 1px solid #ddd; border-radius: 9px; padding: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 999;">
        <center><img src="./images/alert.png" width="50px" style="margin-bottom: 10px;" onerror="this.style.display='none'"></center>
        <p style="margin-bottom:15px; font-size: 13px; color: red;"><b>ตรวจสอบข้อมูลก่อนพิมพ์</b></p>
        <button type="button" class="btn btn-success btn-block" id="pdf">
            <i class="glyphicon glyphicon-print"></i> พิมพ์รายงาน
        </button>
    </div>

    <script src="./printThis/printThis.js"></script>
    <script>
        $( document ).ready(function() {
            $("#pdf").click(function(){
              $("#page").printThis({
                  importCSS: true,
                  importStyle: true,
                  pageTitle: "รายงานลูกหนี้",
                  removeInline: false
              });
            });
        });
    </script>
</body>
</html>
