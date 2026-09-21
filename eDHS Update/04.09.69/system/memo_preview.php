<?php
session_start();
require_once './database_config/config.php';

// 1. รับค่าเดือนที่ส่งมา (รองรับทั้งผ่าน URL ?dmonth=4-2026 หรือ Session)
$dmonth = $_GET['dmonth'] ?? $_SESSION['dmonth'] ?? date('n-Y');

// แยกเดือนและปี
list($m_num, $y_num) = explode('-', $dmonth);

// 2. หาเดือนก่อนหน้า เพื่อใช้ดึง "ยอดยกมา"
$m_prev = $m_num - 1;
$y_prev = $y_num;
if ($m_prev == 0) {
    $m_prev = 12;
    $y_prev--;
}
$dmonthlast = $m_prev . '-' . $y_prev;

// ฟอร์แมตเพื่อใช้ใน Query ของตารางสิทธิ (เช่น 2026-04)
$target_ym = sprintf('%s-%02d', $y_num, $m_num);

// 3. คำนวณชื่อเดือนภาษาไทย และ ปีงบประมาณ (พ.ศ.)
$thaiMonths = [1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'];
$monthNow = $thaiMonths[(int)$m_num] ?? '';
$monthLast = $thaiMonths[(int)$m_prev] ?? ''; 
$fiscalYearThai = ((int)$m_num >= 10 ? $y_num + 1 : $y_num) + 543;

// =================================================================
// 🌟 เริ่มดึงข้อมูลจากฐานข้อมูล (ลอจิกเดียวกับ Dashboard)
// =================================================================

// [1] หาผลรวม "ลูกหนี้ยกมา" (ถอยหลังไปดึง column12 ของเดือนก่อนหน้า)
$sql_prev_totals = "
    SELECT 
        SUM(CASE WHEN opd.accountcode IS NOT NULL THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS opd_txt1,
        SUM(CASE WHEN ipd.accountcode IS NOT NULL AND opd.accountcode IS NULL THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS ipd_txt1
    FROM imr_tb_debtor_result r
    LEFT JOIN (
        SELECT DISTINCT accountcode FROM imr_tb_debtor_rights_opd WHERE accountcode IS NOT NULL AND accountcode != ''
    ) opd ON r.code = opd.accountcode
    LEFT JOIN (
        SELECT DISTINCT accountcode FROM imr_tb_debtor_rights_ipd WHERE accountcode IS NOT NULL AND accountcode != ''
    ) ipd ON r.code = ipd.accountcode
    WHERE r.month = '$dmonthlast'
";
$res_prev = $conn->query($sql_prev_totals);
$row_prev = $res_prev->fetch_assoc();
$val_opd1 = $row_prev['opd_txt1'] ?? 0;
$val_ipd1 = $row_prev['ipd_txt1'] ?? 0;

// [2] หาผลรวม "ลูกหนี้สิทธิ" (ตั้งหนี้ใหม่เดือนนี้) จากตาราง OPD / IPD โดยตรง
$sql_opd_debit = "SELECT SUM(IFNULL(debit, 0)) AS total FROM imr_tb_debtor_rights_opd WHERE CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1), '-', LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) = '$target_ym'";
$res_opd_debit = $conn->query($sql_opd_debit);
$val_opd3 = $res_opd_debit->fetch_assoc()['total'] ?? 0;

$sql_ipd_debit = "SELECT SUM(IFNULL(debit, 0)) AS total FROM imr_tb_debtor_rights_ipd WHERE CONCAT(SUBSTRING_INDEX(monthtxt,'-',-1), '-', LPAD(SUBSTRING_INDEX(monthtxt,'-',1),2,'0')) = '$target_ym'";
$res_ipd_debit = $conn->query($sql_ipd_debit);
$val_ipd3 = $res_ipd_debit->fetch_assoc()['total'] ?? 0;

// [3] หาผลรวม "ตัดลูกหนี้" (column10) และ "ยกไป" (column12) ของเดือนปัจจุบัน
$sql_curr_totals = "
    SELECT 
        SUM(CASE WHEN opd.accountcode IS NOT NULL THEN CAST(REPLACE(IFNULL(r.column10, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS opd_txt7,
        SUM(CASE WHEN ipd.accountcode IS NOT NULL AND opd.accountcode IS NULL THEN CAST(REPLACE(IFNULL(r.column10, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS ipd_txt7,
        SUM(CASE WHEN opd.accountcode IS NOT NULL THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS opd_txt10,
        SUM(CASE WHEN ipd.accountcode IS NOT NULL AND opd.accountcode IS NULL THEN CAST(REPLACE(IFNULL(r.column12, '0'), ',', '') AS DECIMAL(15,2)) ELSE 0 END) AS ipd_txt10
    FROM imr_tb_debtor_result r
    LEFT JOIN (
        SELECT DISTINCT accountcode FROM imr_tb_debtor_rights_opd WHERE accountcode IS NOT NULL AND accountcode != ''
    ) opd ON r.code = opd.accountcode
    LEFT JOIN (
        SELECT DISTINCT accountcode FROM imr_tb_debtor_rights_ipd WHERE accountcode IS NOT NULL AND accountcode != ''
    ) ipd ON r.code = ipd.accountcode
    WHERE r.month = '$dmonth'
";
$res_curr = $conn->query($sql_curr_totals);
$row_curr = $res_curr->fetch_assoc();
$val_opd7 = $row_curr['opd_txt7'] ?? 0;
$val_ipd7 = $row_curr['ipd_txt7'] ?? 0;
$val_opd10 = $row_curr['val_opd10'] ?? $row_curr['opd_txt10'] ?? 0;
$val_ipd10 = $row_curr['val_ipd10'] ?? $row_curr['ipd_txt10'] ?? 0;

// แปลงฟอร์แมตตัวเลขเตรียมส่งให้ HTML
$opd_txt1  = number_format($val_opd1, 2);
$ipd_txt1  = number_format($val_ipd1, 2);
$dpavartxt1 = number_format($val_opd1 + $val_ipd1, 2);

$opd_txt3  = number_format($val_opd3, 2);
$ipd_txt3  = number_format($val_ipd3, 2);
$dpavartxt3 = number_format($val_opd3 + $val_ipd3, 2);

$opd_txt7  = number_format($val_opd7, 2);
$ipd_txt7  = number_format($val_ipd7, 2);
$dpavartxt7 = number_format($val_opd7 + $val_ipd7, 2);

$opd_txt10 = number_format($val_opd10, 2);
$ipd_txt10 = number_format($val_ipd10, 2);
$dpavartxt10 = number_format($val_opd10 + $val_ipd10, 2);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>บันทึกข้อความ – รายงานลูกหนี้</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
<script src="../assets/dist/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="../assets/dist/sweetalert.css">
<style>

  /* ประกาศฟอนต์ TH Sarabun New ตัวปกติ */
  @font-face {
      font-family: 'TH Sarabun New';
      src: url('./font/THSarabunNew.ttf') format('truetype');
      font-weight: normal;
      font-style: normal;
  }

  /* ประกาศฟอนต์ TH Sarabun New ตัวหนา (Bold) */
  @font-face {
      font-family: 'TH Sarabun New';
      src: url('./font/THSarabunNew Bold.ttf') format('truetype');
      font-weight: bold;
      font-style: normal;
  }

  /* ประกาศฟอนต์ TH Sarabun New ตัวเอียง (Italic) */
  @font-face {
      font-family: 'TH Sarabun New';
      src: url('./font/THSarabunNew Italic.ttf') format('truetype');
      font-weight: normal;
      font-style: italic;
  }

  /* ประกาศฟอนต์ TH Sarabun New ตัวหนาเอียง (Bold Italic) */
  @font-face {
      font-family: 'TH Sarabun New';
      src: url('./font/THSarabunNew BoldItalic.ttf') format('truetype');
      font-weight: bold;
      font-style: italic;
  }

  /* ======= UI Shell ======= */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Sarabun', 'TH Sarabun New', sans-serif;
         background: #f3f4f6; display: flex; height: 100vh; overflow: hidden; }

  /* ---- Sidebar (Light Theme ตาม eDHS) ---- */
  #sidebar {
    width: 400px; min-width: 400px; background: #ffffff;
    color: #334155; display: flex; flex-direction: column;
    overflow-y: auto; flex-shrink: 0;
    border-right: 1px solid #e2e8f0;
    box-shadow: 4px 0 15px rgba(0,0,0,0.03);
    z-index: 10;
  }
  #sidebar h2 {
    font-size: 20px; font-weight: 700; letter-spacing: .05em;
    color: #5b5ce6;
    padding: 8px; border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    margin-bottom: 8px;
  }
  .section-label {
    font-size: 16px; font-weight: 700;
    color: #64748b;
    padding: 4px 16px 4px;
    margin-top: 4px;
  }
  .field { padding: 4px 16px 4px; }
  .field label { 
    font-size: 16px; color: #475569; display: block; 
    margin-bottom: 6px; font-weight: 600; 
  }
  .field input, .field textarea, .field select {
    width: 100%; background: #f8fafc; border: 1px solid #cbd5e1;
    color: #0f172a; border-radius: 8px; padding: 4px 12px;
    font-size: 18px; font-family: inherit;
    transition: all .2s ease;
  }
  .field input:focus, .field textarea:focus {
    outline: none; border-color: #5b5ce6;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(91, 92, 230, 0.15);
  }
  .field textarea { resize: vertical; min-height: 48px; }

  /* Buttons */
  #sidebar-footer {
    padding: 16px; border-top: 1px solid #e2e8f0;
    display: flex; gap: 8px; flex-wrap: wrap;
    background: #f8fafc;
    margin-top: auto;
  }
  .btn {
    flex: 1; padding: 10px 12px; border: none; border-radius: 8px;
    font-size: 16px; font-family: inherit; font-weight: 600;
    cursor: pointer; transition: all .2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }
  .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
  .btn:active { transform: translateY(0); }
  
  .btn-primary { background: #5b5ce6; color: #fff; }
  .btn-primary:hover { background: #4f46e5; }
  
  .btn-warn { background: #f59e0b; color: #fff; }
  .btn-warn:hover { background: #d97706; }
  
  .btn-danger { background: #fee2e2; color: #ef4444; border: 1px solid #f87171; box-shadow: none; }
  .btn-danger:hover { background: #fef2f2; color: #dc2626; border-color: #ef4444; }

  /* ปรับแต่ง Scrollbar */
  #sidebar::-webkit-scrollbar { width: 6px; }
  #sidebar::-webkit-scrollbar-track { background: transparent; }
  #sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
  #sidebar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

  /* ---- Preview Area ---- */
  #preview-wrap {
    flex: 1; overflow-y: auto; padding: 30px;
    display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: 25px;
  }

  /* ======= กระดาษ A4 ======= */
  #paper {
    background: #fff;
    width: 210mm;
    min-height: 297mm;
    padding: 12mm 18mm 12mm 25mm;
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
    font-family: 'TH Sarabun New', 'Sarabun', serif;
    font-size: 16pt;
    line-height: 1.4;
    color: #000;
    position: relative;
    border-radius: 4px;
  }

  /* ======= กล่องพรีวิวไฟล์ตารางแนบท้าย (ย้ายไปตำแหน่ง saved_reports/) ======= */
  #pdf-preview-container {
    width: 270mm; 
    background: #ffffff;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
    border-radius: 4px;
    border: 1px solid #e2e8f0;
  }

  /* ======= หัวเอกสาร ======= */
  .top-bar { width: 100%; border-collapse: collapse;  }
  .top-bar td { vertical-align: bottom; padding: 0; }
  .td-garuda { width: 30mm; }
  .td-garuda img { height: 18mm; width: auto; max-width: 100%; }
  .td-title { text-align: center; vertical-align: bottom; padding-bottom: 10px; font-size: 29pt; font-weight: bold; }

  .memo-row {
    display: flex;
    align-items: flex-end; 
    margin-bottom: 12px;
  }
  .memo-label {
    font-size: 18pt;
    font-weight: bold;
    margin-right: 12px;
    white-space: nowrap;
    line-height: 0.85; 
  }
  .memo-value {
    flex: 1;
    padding-left: 10px;
    line-height: 0.85; 
    font-size: 16pt;
  }

  /* ======= เนื้อหา ======= */
  .para   { text-align: justify; text-justify: inter-word; word-break: break-word; line-height: 1.3; margin-top: 1px; }
  .indent { text-indent: 1.3cm; }

  /* ======= ตารางลูกหนี้ ======= */
  .debt-table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 15pt; }
  .debt-table th {
    border: 1pt solid #333; padding: 2px;
    text-align: center; font-weight: bold;  }
  .debt-table td { border: 1pt solid #333; padding: 0px 6px; }
  .debt-table td.item { text-align: left; }
  .debt-table td.num  { text-align: right; }
  .debt-table tr.last td { font-weight: bold; }

  /* ======= ลายเซ็น ======= */
  .sign-wrap { width: 100%; border-collapse: collapse; margin-top: 10px; }
  .col-left  { width: 70%; text-align: center; vertical-align: top; }
  .col-right { width: 30%; vertical-align: top; }
  .sign-block { line-height: 1.3; margin-left: 135px;}

  /* checkbox */
  .cb-tbl { border-collapse: collapse; font-size: 15pt; margin-left: 90px; }
  .cb-tbl td { vertical-align: middle; }
  .cb-box {
    width: 14px; height: 14px;
    border: 1pt solid #000;
    display: inline-block; vertical-align: middle;
  }
  .cb-lbl { padding: 0 14px 0 5px; white-space: nowrap; }
  .dir-sign { text-align: center; line-height: 1.3;margin-left: 25px; }

  /* ======= Footer ======= */
  .footer-line {
    margin-top: 15px;
    padding-top: 5px; font-size: 15pt; line-height: 1.2;
  }
</style>
</head>
<body>

<div id="sidebar">
  <h2>✏️ ข้อมูลเอกสาร</h2>

  <div class="section-label">📋 ส่วนหัวและเลขที่เอกสาร</div>
  <div class="field"><label>ชื่อโรงพยาบาล</label>
    <input id="f_hospital" value="<?php echo isset($hospital) ? $hospital : 'โรงพยาบาลโพนทราย'; ?>"></div>
  <div class="field"><label>กลุ่มงาน (สั้น)</label>
    <input id="f_dept_short" value="กลุ่มงานประกันสุขภาพยุทธศาสตร์ฯ"></div>
  <div class="field"><label>เบอร์โทร (สั้น)</label>
    <input id="f_dept_tel" value="โทร ๐ ๔๓๕๙ ๕๐๗๓ ต่อ ๑๑๖"></div>
  <div class="field"><label>เลขที่หนังสือ (prefix)</label>
    <input id="f_prefix" value="รอ ๐๐๓๓.๓๑๔.๐๙/"></div>
  <div class="field"><label>เลขที่ (ตัวเลข)</label>
    <input id="f_runno" value="๐๐๑"></div>
  <div class="field"><label>วันที่</label>
    <input id="f_date" type="date" value="<?php echo date('Y-m-d'); ?>"></div> 

  <div class="section-label">✍️ ผู้ลงนาม</div>
    <div class="field"><label>ชื่อผู้รายงาน</label>
    <input id="f_signer1" value="นายจิรันธนิน ประสารกุลนันท์" style="margin-bottom: 8px;">
    <input id="f_signer2" value="นักวิชาการคอมพิวเตอร์"></div>
  <div class="field"><label>ชื่อผู้รับรอง</label>
    <input id="f_signer" value="นายวริทธิกันต์  บัวลาด"></div>

  <div class="field"><label>ชื่อหัวหน้าบริหาร</label>
    <input id="f_signer3" value="(นายชัชชัย วันทอง)"></div>
  <div class="field"><label>ตำแหน่ง</label>
    <input id="f_signer4" value="หัวหน้ากลุ่มงานบริหารทั่วไป"></div>

  <div class="field"><label>ชื่อผู้อำนวยการ</label>
    <input id="f_director" value="นายธนพล วิมลวรรณ"></div>

  <div class="section-label">📞 ส่วนท้ายเอกสาร</div>
  <div class="field"><label>กลุ่มงาน (เต็ม)</label>
    <textarea id="f_dept_full">กลุ่มงานประกันสุขภาพ ยุทธศาสตร์และสารสนเทศทางการแพทย์</textarea></div>
  <div class="field"><label>เบอร์โทร</label>
    <input id="f_phone" value="โทร.๐ ๔๓๕๙ ๕๐๗๓ ต่อ ๑๑๖"></div>
  <div class="field"><label>ผู้ประสานงาน</label>
    <input id="f_coord" value="ผู้ประสานงานนายจิรันธนิน ประสารกุลนันท์  มือถือ ๐๘ ๗๘๕๔ ๘๖๘๗"></div>

  <div id="sidebar-footer">
    <button class="btn btn-primary" onclick="render()">🔄 รีเฟรชเอกสาร</button>
    <button class="btn btn-warn" onclick="printDoc()">🖨️ พิมพ์เอกสาร</button>
    <button class="btn btn-danger" onclick="clearSavedData()" style="flex: 1 1 100%; margin-top: 8px;">🗑️ ล้างค่าที่จำไว้</button>
  </div>
</div>

<div id="preview-wrap">
  <div id="paper"></div>

  <div id="pdf-preview-container">
    <h3 style="font-size: 18px; font-weight: 700; color: #000; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
      📊 เอกสารแนบท้าย: ทะเบียนคุมลูกหนี้สิทธิ
    </h3>
    <iframe src="saved_reports/ทะเบียนคุมลูกหนี้สิทธิ_<?php echo $dmonth; ?>.pdf?v=<?php echo time(); ?>" width="100%" height="550px" style="border: 1px solid #cbd5e1; border-radius: 8px; background: #f1f5f9;"></iframe>
  </div>
</div>

<script>
// =================================================================
// ส่วนระบบความจำ (Local Storage)
// =================================================================
const saveableFields = [
  'f_hospital', 'f_dept_short', 'f_dept_tel', 'f_prefix', 
  'f_runno', 'f_signer1', 'f_signer2', 'f_signer3', 'f_signer4', 'f_signer', 'f_director', 'f_dept_full', 'f_phone', 'f_coord'
];

function loadSavedData() {
  saveableFields.forEach(id => {
    const savedVal = localStorage.getItem('memo_report_' + id);
    if (savedVal !== null) {
      document.getElementById(id).value = savedVal;
    }
  });
}

function saveDataOnChange(e) {
  const id = e.target.id;
  if (saveableFields.includes(id)) {
    localStorage.setItem('memo_report_' + id, e.target.value);
  }
}

function clearSavedData() {
  if(confirm('ต้องการล้างข้อมูลที่จำไว้ทั้งหมดและกลับไปใช้ค่าเริ่มต้นหรือไม่?')) {
    saveableFields.forEach(id => {
      localStorage.removeItem('memo_report_' + id);
    });
    location.reload(); 
  }
}

// =================================================================
// ส่วนตัวช่วยจัดการข้อมูล
// =================================================================
function thai(n) {
  return String(n).replace(/[0-9]/g, d => '๐๑๒๓๔๕๖๗๘๙'[d]);
}

function dateThai(str) {
  if (!str) return '';
  const d = new Date(str);
  const months = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                  'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
  return thai(d.getDate()) + '  ' + months[d.getMonth()+1] + '  ' + thai(d.getFullYear()+543);
}

function cbRow(l1, l2) {
  const box = `<span class="cb-box"></span>`;
  return `<tr>
    <td>${box}</td><td class="cb-lbl">${l1}</td>
    <td>${box}</td><td class="cb-lbl">${l2}</td>
  </tr>`;
}

const g = id => document.getElementById(id).value.trim();

// =================================================================
// ส่วนสร้างหน้ากระดาษ
// =================================================================
function render() {
  const hospital   = g('f_hospital');
  const deptShort  = g('f_dept_short');
  const deptTel    = g('f_dept_tel');
  const prefix     = g('f_prefix');
  const runno      = g('f_runno');
  const dateStr    = g('f_date');
  const signer1    = g('f_signer1');
  const signer2    = g('f_signer2');
  const signer3    = g('f_signer3');
  const signer4    = g('f_signer4');
  const signer     = g('f_signer');
  const director   = g('f_director');
  const deptFull   = g('f_dept_full');
  const phone      = g('f_phone');
  const coord      = g('f_coord');

  const month      = "<?php echo $monthNow ?? ''; ?>";
  const year       = "<?php echo $fiscalYearThai ?? ''; ?>";
  
  const val_ci_opd = "<?php echo $opd_txt1 ?? '0.00'; ?>";
  const val_ci_ipd = "<?php echo $ipd_txt1 ?? '0.00'; ?>";
  const val_ci_tot = "<?php echo $dpavartxt1 ?? '0.00'; ?>";
  
  const val_nw_opd = "<?php echo $opd_txt3 ?? '0.00'; ?>";
  const val_nw_ipd = "<?php echo $ipd_txt3 ?? '0.00'; ?>";
  const val_nw_tot = "<?php echo $dpavartxt3 ?? '0.00'; ?>";
  
  const val_pd_opd = "<?php echo $opd_txt7 ?? '0.00'; ?>";
  const val_pd_ipd = "<?php echo $ipd_txt7 ?? '0.00'; ?>";
  const val_pd_tot = "<?php echo $dpavartxt7 ?? '0.00'; ?>";
  
  const val_co_opd = "<?php echo $opd_txt10 ?? '0.00'; ?>";
  const val_co_ipd = "<?php echo $ipd_txt10 ?? '0.00'; ?>";
  const val_co_tot = "<?php echo $dpavartxt10 ?? '0.00'; ?>";

  document.getElementById('paper').innerHTML = `
<table class="top-bar">
  <tr>
    <td class="td-garuda">
      <img src="images/kk.png" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <span style="display:none;font-size:20mm;">🦅</span>
    </td>
    <td class="td-title">บันทึกข้อความ</td>
    <td style="width:30mm;"></td> </tr>
</table>

<div class="memo-row">
  <span class="memo-label">ส่วนราชการ</span>
  <span class="memo-value">${hospital} ${deptShort} ${deptTel}</span>
</div>
<div class="memo-row">
  <span class="memo-label">ที่</span>
  <span class="memo-value" style="flex: 0.6;">${prefix}${runno}</span>
  <span class="memo-label" style="margin-left: 20px;">วันที่</span>
  <span class="memo-value" style="text-align: center;">${dateThai(dateStr)}</span>
</div>
<div class="memo-row">
  <span class="memo-label">เรื่อง</span>
  <span class="memo-value">สรุปรายงานสถานะลูกหนี้ค่ารักษาพยาบาลสิทธิประจำเดือน ${month}&nbsp;&nbsp;ปีงบประมาณ ${thai(year)}</span>
</div>
<div class="memo-row" style="margin-bottom: 5px;">
  <span class="memo-label">เรียน</span>
  <span class="memo-value" style="border-bottom: 1pt solid #ffffff;">ผู้อำนวยการ${hospital}</span>
</div>

<div class="para indent" style="margin-top: 15px;">
  ศูนย์จัดเก็บรายได้ กลุ่มงานประกันสุขภาพฯ ${hospital} ได้ดำเนินการจัดทำทะเบียนคุมและบริหารจัดการลูกหนี้สิทธิค่ารักษาพยาบาล เพื่อให้การจัดเก็บรายได้ของโรงพยาบาลเป็นไปด้วยความถูกต้อง รัดกุม และมีประสิทธิภาพสูงสุดตามระเบียบระบบบัญชีของกระทรวงสาธารณสุข นั้น
</div>

<div class="para indent">
  ในการนี้ ศูนย์จัดเก็บรายได้ กลุ่มงานประกันสุขภาพฯ ได้ดำเนินการประมวลผลข้อมูลและสรุปรายงานอายุหนี้ ประจำเดือน ${month} ${thai(year)} เสร็จสิ้นเป็นที่เรียบร้อยแล้ว จึงขอรายงานสรุปสถานะลูกหนี้ค่ารักษาพยาบาล โดยจำแนกรายละเอียดแยกรายการผู้ป่วยนอก (OPD) และผู้ป่วยใน (IPD) เพื่อประกอบการพิจารณา ดังต่อไปนี้
</div>

<table class="debt-table">
  <thead>
    <tr>
      <th style="width:46%;">รายการสถานะลูกหนี้สิทธิ</th>
      <th style="width:18%;">ผู้ป่วยนอก (OPD)</th>
      <th style="width:18%;">ผู้ป่วยใน (IPD)</th>
      <th style="width:18%;">ยอดรวมทั้งหมด</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td class="item">๑. ลูกหนี้สิทธิยกมา</td>
      <td class="num">${thai(val_ci_opd)}</td>
      <td class="num">${thai(val_ci_ipd)}</td>
      <td class="num">${thai(val_ci_tot)}</td>
    </tr>
    <tr>
      <td class="item">๒. ลูกหนี้สิทธิ</td>
      <td class="num">${thai(val_nw_opd)}</td>
      <td class="num">${thai(val_nw_ipd)}</td>
      <td class="num">${thai(val_nw_tot)}</td>
    </tr>
    <tr>
      <td class="item">๓. ตัดลูกหนี้</td>
      <td class="num">${thai(val_pd_opd)}</td>
      <td class="num">${thai(val_pd_ipd)}</td>
      <td class="num">${thai(val_pd_tot)}</td>
    </tr>
    <tr>
      <td class="item">๔. ลูกหนี้สิทธิยกไป</td>
      <td class="num">${thai(val_co_opd)}</td>
      <td class="num">${thai(val_co_ipd)}</td>
      <td class="num">${thai(val_co_tot)}</td>
    </tr>
  </tbody>
</table>

<div class="para indent">จึงเรียนมาเพื่อโปรดทราบ</div>

<table class="sign-wrap">
  <tr>
    <td class="col-left">
      <div class="sign-block">
        <br>
        (${signer1})<br>
        ${signer2}<br>
        ผู้จัดทำรายงาน
      </div>
      
      <div class="sign-block" style="margin-top: 60px;margin-right:70px;">
        <br>
        ${signer3}<br>
        ${signer4}<br>
      </div>
    </td>

    <td class="col-right">
      <div class="dir-sign">
        <br>
        (${signer})<br>
        หัวหน้ากลุ่มงานประกันสุขภาพ<br>
        ยุทธศาสตร์และสารสนเทศทางการแพทย์
      </div>

      <table class="cb-tbl" style="margin-top: 15px;">
        ${cbRow('ทราบ','อนุมัติ')}
        ${cbRow('ดำเนินการ','อนุญาต')} 
        ${cbRow('ลงนามแล้ว','เห็นชอบ')}
      </table>

      <div class="dir-sign" style="margin-top: 25px;">
        <br>
        (${director})<br>
        ผู้อำนวยการ${hospital}
      </div>

    </td>

  </tr>
</table>

<div class="footer-line">
  ${deptFull}<br>
  ${phone}<br>
  ${coord}
</div>
  `;
}

// ผูก Event ให้ทำงานตอนมีการพิมพ์
document.querySelectorAll('#sidebar input, #sidebar textarea, #sidebar select')
  .forEach(el => {
    el.addEventListener('input', function(e) {
      saveDataOnChange(e); 
      render();            
    });
  });


function printDoc() { 
    // 1. สั่งพิมพ์แผ่นที่ 1 (บันทึกข้อความ A4 แนวตั้ง)
    window.print(); 
    
    // 2. แสดงกล่องแจ้งเตือนด้วย SweetAlert ทันทีหลังปิดหน้าต่างพิมพ์
    Swal.fire({
        icon: 'warning',
        title: '💡 อย่าลืมปริ้นเอกสารแนบให้ครบถ้วน!',
        html: 'เมื่อพิมพ์ใบปะหน้าบันทึกข้อความเสร็จแล้ว ให้เลื่อนลงไปที่ <b>"กรอบพรีวิวตารางแนบท้าย"</b> ด้านล่างนี้ แล้วกดปุ่มเครื่องพิมพ์จากในกรอบนั้น เพื่อสั่งพิมพ์ทะเบียนคุมต่อได้เลย',
        confirmButtonColor: '#5b5ce6',
        confirmButtonText: 'รับทราบ!'
    });
}

// =================================================================
// CSS สำหรับตอนปริ้นท์ (ล้างขอบเงา, ซ่อนแถบข้าง และซ่อนกรอบพรีวิว PDF)
// =================================================================
const ps = document.createElement('style');
ps.media = 'print';
ps.textContent = `
  @page { size: A4 portrait; margin: 0 !important; }
  html, body { background-color: #ffffff !important; margin: 0 !important; padding: 0 !important; width: 210mm !important; height: 297mm !important; overflow: visible !important; }
  #sidebar { display: none !important; }
  #preview-wrap { padding: 0 !important; margin: 0 !important; background-color: #ffffff !important; display: block !important; overflow: visible !important; height: auto !important; }
  #paper { box-shadow: none !important; border-radius: 0 !important; border: none !important; margin: 0 !important; width: 210mm !important; height: 297mm !important; page-break-after: avoid !important; page-break-inside: avoid !important; }
  
  /* ซ่อนกรอบพรีวิวตาราง PDF ด้านล่าง ไม่ให้ติดออกเครื่องปริ้นท์ตอนพิมพ์แผ่นแรก */
  #pdf-preview-container { display: none !important; }
`;
document.head.appendChild(ps);

// เริ่มการทำงาน
loadSavedData();
render();
</script>
</body>
</html>