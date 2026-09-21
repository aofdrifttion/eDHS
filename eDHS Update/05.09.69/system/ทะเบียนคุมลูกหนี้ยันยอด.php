<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
if (function_exists('system_log')) {
    $pageName = basename($_SERVER['PHP_SELF']);
    system_log($conn, 'เปิดดูข้อมูล (View)', 'VIEW', "ผู้ใช้เปิดดูหน้ารายงาน/ข้อมูล: " . $pageName);
}
mysqli_set_charset($conn, 'utf8mb4');


function DateThai($strDate)
{
    $strYear = date("Y",strtotime($strDate))+543;
    $strMonth= date("n",strtotime($strDate));
    $strDay= date("j",strtotime($strDate));

    $strMonthCut = Array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
    $strMonthThai=$strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear";
}

function DateThaiM($monthYear)
{
    // แยกค่าเดือนและปีจากรูปแบบ 'เดือน-ปี'
    $parts = explode('-', $monthYear);

    // ตรวจสอบรูปแบบว่ามี 2 ส่วน (เดือนและปี)
    if (count($parts) !== 2) {
        return "Invalid input format.";
    }

    // แปลงค่าเดือนและปีเป็นตัวเลข
    $month = intval($parts[0]);
    $year = intval($parts[1]);

    // ตรวจสอบว่าเดือนอยู่ในช่วงที่ถูกต้อง
    if ($month < 1 || $month > 12) {
        return "Invalid month.";
    }

    // แปลงปีเป็น พ.ศ.
    $strYear = $year + 543;

    // ชื่อเดือนภาษาไทย
    $strMonthCut = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
    $strMonthThai = $strMonthCut[$month];

    // คำนวณปีงบประมาณ
    // ปีงบประมาณเริ่มต้นเดือนตุลาคมของปีปัจจุบันถึงเดือนกันยายนของปีถัดไป
    $fiscalYear = ($month >= 10) ? $year + 1 : $year;

    // แปลงปีงบประมาณเป็น พ.ศ.
    $fiscalYearThai = $fiscalYear + 543;

    return [
        'fullDate' => "$strMonthThai $strYear", // วันที่แบบเต็ม
        'fullDate1' => "$strMonthThai", // วันที่แบบเต็ม
        'fiscalYear' => "ปีงบประมาณ $fiscalYearThai" // ปีงบประมาณ
    ];
}




function DateThaiMlast($monthYear)
{
    // [แก้] กำหนดค่า Default เป็น Array ไว้ก่อน กัน Error เวลาค่าส่งมาว่าง
    $defaultResult = [
        'fullDate' => '-',
        'fiscalYear' => '-'
    ];

    // เช็คว่ามีค่าส่งมาไหม หรือรูปแบบผิดไหม ถ้าผิดให้คืนค่า Default ไปเลย ไม่คืนเป็น String
    if (empty($monthYear)) return $defaultResult;
    
    $parts = explode('-', $monthYear);
    if (count($parts) !== 2) return $defaultResult;

    $month = intval($parts[0]);
    $year = intval($parts[1]);

    if ($month < 1 || $month > 12) return $defaultResult;

    // ส่วนคำนวณเดิม
    $strYear = $year + 543;
    $strMonthCut = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
    $strMonthThai = $strMonthCut[$month];

    $fiscalYear = ($month >= 10) ? $year + 1 : $year;
    $fiscalYearThai = $fiscalYear + 543;

    return [
        'fullDate' => "$strMonthThai", 
        'fiscalYear' => "ปีงบประมาณ $fiscalYearThai" 
    ];
}


$strDate = date("Y/m/d");
$yyy = date("Y")+543;
?>

<!DOCTYPE html>

<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>ข้อมูลประชากรและสิทธิ | รายงานจัดเก็บรายได้/รายงานบัญชีการเงิน</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../assets/js/config.js"></script>
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>

    <!-- <script src="../assets/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/dist/sweetalert.css"> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <style>
      /* ==========================================================================
         eDHS System Font: Noto Sans Thai
         ========================================================================== */
      body, 
      .layout-wrapper, 
      .layout-container, 
      .layout-page, 
      .content-wrapper, 
      #layout-menu, 
      #layout-menu *:not([class*="bx-"]):not([class*="fa-"]):not(.bx):not(.fa), 
      .bg-menu-theme, 
      .bg-menu-theme *:not([class*="bx-"]):not([class*="fa-"]):not(.bx):not(.fa), 
      .app-brand-text.demo, 
      .menu-link, 
      .menu-header-text, 
      .card, 
      .card-header, 
      .card-body, 
      .card-footer, 
      table, th, td, 
      input, button, select, textarea, 
      label, h1, h2, h3, h4, h5, h6, 
      p, a, span, small, strong, b, 
      .modal, .modal *, 
      .dropdown-menu, .dropdown-item, 
      .badge, .nav, .nav-link, 
      .swal2-popup, .swal2-popup *,
      .select2, .select2 * {
        font-family: 'Noto Sans Thai', sans-serif !important;
      }

      /* ป้องกันฟอนต์ไอคอน Boxicons, Font Awesome และ Iconify ไม่ให้ถูกทับ */
      .bx, .bxs, .bxl, [class^="bx-"], [class*=" bx-"], 
      .bx:before, .bxs:before, .bxl:before, 
      i[class*="bx-"], i[class*="bx-"]:before {
        font-family: 'boxicons' !important;
      }

      .fa, .fas, .far, .fal, .fad, .fab, 
      .fa:before, .fas:before, .far:before, .fal:before, .fad:before, .fab:before, 
      i[class*="fa-"], i[class*="fa-"]:before {
        font-family: 'Font Awesome 5 Free' !important;
      }
      .fab, .fab:before {
        font-family: 'Font Awesome 5 Brands' !important;
      }
      iconify-icon {
        font-family: unset;
      }

      /* ทำให้ตาราง responsive */
      .responsive-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }

      table {
        border-collapse: collapse;
        width: 100%;
      }

      th, td {
        text-align: center;
        padding: 4px;
        border: 1px solid #ddd;
        font-size: 13px;
      }

      th {
        background-color: #f4f4f4;
        font-weight: 300;
      }

      .numericcolor {  cursor: no-drop;}
      .numeric { cursor: pointer;}
      
      .error {
        background-color: #ffaaaa;
        border: 1px solid #fff;
      }

      .locked-cell {
          cursor: not-allowed;
      }

      /* สีเมื่อ hover บนแถว */
      table tr:hover {
        background-color: #f4f4f4; /* เปลี่ยนเป็นสีที่ต้องการ */
        
      }

      table tr.hoverable-row:hover {
        background-color: #fff; /* เปลี่ยนเป็นสีที่ต้องการ */
      }

      .floating-logout-btn {
        position: fixed;
          bottom: 20px;
          right: 20px;
          z-index: 9999;
          background-color: #8a4c9d;
          color: white;
          padding: 10px 20px;
          border-radius: 30px;
          box-shadow: 0 0 12px rgb(169 78 255 / 60%), 0 0 20px rgb(234 190 255 / 40%);
          font-size: 14px;
          text-decoration: none;
          font-weight: bold;
          display: inline-flex;
          align-items: center;
          transition: all 0.3s ease;
      }

      .floating-logout-btn:hover {
          background-color: #6a2f7b;
          transform: scale(1.05);
          box-shadow: 0 0 12px rgb(169 78 255 / 60%), 0 0 20px rgb(234 190 255 / 40%);
          color:#fff ;
      }

      /* ซ่อนปุ่ม Logout ลอยตัวเมื่อเปิด Modal ป้องกันการลอยทับปุ่มควบคุมใน Modal */
      body.modal-open .floating-logout-btn {
          display: none !important;
      }


          /* บังคับให้หัวตาราง (thead) ถูกย้ำทุกครั้งที่ขึ้นหน้าใหม่ */
          thead {
              display: table-header-group;
          }
          
          /* ป้องกันแถว (tr) ถูกตัดครึ่งกลางบรรทัด */
          tr {
              page-break-inside: avoid;
              page-break-after: auto;
          }

          /* Class สำหรับกลุ่มข้อมูลที่ห้ามแยกจากกัน (เช่น ลายเซ็นท้ายกระดาษ) */
          .avoid-break {
              page-break-inside: avoid;
          }

          .swal2-container {
            z-index: 999999 !important;
          }

/* =========================================================================
   🌟 Modern Debtor Reconciliation UI (ทะเบียนคุมลูกหนี้ยันยอด)
   ========================================================================= */
.recon-filter-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #0d9488 !important;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px 28px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
  width: 100%;
}
.recon-filter-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.recon-table-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px 28px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
  width: 100%;
}

/* Reconciliation Mismatch Row Highlight */
.table-recon-mismatch-row {
  background-color: #fef2f2 !important;
}
.table-recon-mismatch-row:hover {
  background-color: #fee2e2 !important;
}
.table-recon-mismatch-row td.recon-accname-cell {
  color: #dc2626 !important;
  font-weight: 600 !important;
}
.recon-accname-cell {
  color: #1e293b;
  font-weight: normal;
}

.recon-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 16px;
  margin-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
}

.recon-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.recon-header-icon-filter {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
  color: #0f766e;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
  flex-shrink: 0;
}

.recon-header-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.recon-header-subtitle {
  font-size: 0.82rem;
  color: #64748b;
  margin: 2px 0 0 0;
}

.recon-pill-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: #f8fafc;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.recon-field-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.recon-select {
  border-radius: 10px !important;
  border: 1px solid #cbd5e1 !important;
  padding: 10px 14px !important;
  font-size: 13.5px !important;
  color: #1e293b !important;
  background-color: #ffffff !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.2s ease !important;
}

.recon-select:focus {
  border-color: #0d9488 !important;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.16) !important;
  background-color: #ffffff !important;
}

/* Reconciliation Audit Badges & Toolbar Styles */
.badge-recon-pill {
  transition: all 0.2s ease-in-out;
  border-radius: 20px;
  font-weight: 500;
  letter-spacing: 0.2px;
}
.badge-recon-pill:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
}
.recon-audit-toolbar-card {
  transition: all 0.25s ease;
  border: 1px solid #e2e8f0;
}
.recon-audit-toolbar-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
    </style>

      <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  </head>

  <body>

    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="index.php" class="app-brand-link">
              <span class="app-brand-logo demo">
                <svg
                  width="25"
                  viewBox="0 0 25 42"
                  version="1.1"
                  xmlns="http://www.w3.org/2000/svg"
                  xmlns:xlink="http://www.w3.org/1999/xlink"
                >
                  <defs>
                    <path
                      d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                      id="path-1"
                    ></path>
                    <path
                      d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                      id="path-3"
                    ></path>
                    <path
                      d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                      id="path-4"
                    ></path>
                    <path
                      d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                      id="path-5"
                    ></path>
                  </defs>
                  <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                      <g id="Icon" transform="translate(27.000000, 15.000000)">
                        <g id="Mask" transform="translate(0.000000, 8.000000)">
                          <mask id="mask-2" fill="white">
                            <use xlink:href="#path-1"></use>
                          </mask>
                          <use fill="#696cff" xlink:href="#path-1"></use>
                          <g id="Path-3" mask="url(#mask-2)">
                            <use fill="#696cff" xlink:href="#path-3"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                          </g>
                          <g id="Path-4" mask="url(#mask-2)">
                            <use fill="#696cff" xlink:href="#path-4"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                          </g>
                        </g>
                        <g
                          id="Triangle"
                          transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) "
                        >
                          <use fill="#696cff" xlink:href="#path-5"></use>
                          <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                        </g>
                      </g>
                    </g>
                  </g>
                </svg>
              </span>
              <span class="app-brand-text demo menu-text fw-bolder ms-2">eDHS</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <!-- Dashboard -->
            <li class="menu-item">
              <a href="index.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-alt"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>
               <?php
              $current_page = basename($_SERVER['PHP_SELF']);
              $user_id = $_SESSION['user_id'] ?? 0; 
              $user_role = $_SESSION['role'] ?? '';

              if($user_role == 'admin') {
                  $sql_menu = "SELECT * FROM tb_menus ORDER BY sort_order ASC";
              } else {
                  $sql_menu = "
                      SELECT m.* FROM tb_menus m 
                      JOIN tb_user_permissions p ON m.id = p.menu_id 
                      WHERE p.user_id = '$user_id' 
                      ORDER BY m.sort_order ASC
                  ";
              }
              
              $result_menu = mysqli_query($conn, $sql_menu);
              $current_group = '';

              if ($result_menu && mysqli_num_rows($result_menu) > 0) {
                  while ($menu = mysqli_fetch_assoc($result_menu)) {
                      // 1. เช็คเปลี่ยนกลุ่มเมนู (โค้ดเดิม)
                      if ($current_group != $menu['menu_group']) {
                          $current_group = $menu['menu_group'];
                          echo '<li class="menu-header small"><span class="menu-header-text">'.$current_group.'</span></li>';
                      }
                      
                      $is_active = ($current_page == $menu['menu_link']) ? 'active' : '';
                      ?>
                      
                      <li class="menu-item <?= $is_active ?>">
                        <a href="<?= $menu['menu_link'] ?>" class="menu-link">
                          <i class="menu-icon" style="font-style: normal;"><?= $menu['menu_icon'] ?></i>
                          <div data-i18n="Form Elements"><?= $menu['menu_name'] ?></div>
                        </a>
                      </li>
                      
                      <?php
                  }
              }
              ?>
            
          </ul>
        </aside>
        <!-- / Menu -->

        
        <!-- Layout container -->
        <div class="layout-page">
          
           <nav  class="container-fluid" id="layout-navbar" style="margin-bottom: 20px;">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>
          </nav>

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-fluid">
              <div class="row">

                <!-- Total Revenue -->
                <div class="col-12 mb-4">

                  <!-- Card ตัวกรองสรุปลูกหนี้สิทธิประจำเดือน -->
                  <div class="recon-filter-card">
                    <div class="recon-card-header">
                      <div class="recon-header-left">
                        <div class="recon-header-icon-filter">
                          <i class='bx bx-check-shield'></i>
                        </div>
                        <div>
                          <h5 class="recon-header-title">สรุปลูกหนี้สิทธิประจำเดือน</h5>
                          <p class="recon-header-subtitle">ทะเบียนคุมลูกหนี้สิทธิและการยันยอดลูกหนี้ระหว่างกลุ่มงานประกันและกลุ่มงานบัญชี</p>
                        </div>
                      </div>
                      <div>
                        <span class="recon-pill-badge">
                          <i class='bx bx-calendar-event text-success'></i> ข้อมูลประจำเดือน
                        </span>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <label class="recon-field-label"><i class='bx bx-calendar text-success'></i> ข้อมูลประจำเดือน</label>
                        <select id="selectTypeOpt" name="selectTypeOpt" class="form-select recon-select">
                          <?php
                          // เดือนปัจจุบัน
                          $gmm = date('n-Y'); // เช่น "3-2025"
                          $gmm2 = '';         // เดือนก่อนหน้า $latestMonth
                          $latestMonth = '';

                          // ดึงเดือนล่าสุดจากฐานข้อมูล
                          $sqlLatest = "SELECT monthtxt
                                        FROM imr_tb_debtor_rights_opd
                                        GROUP BY monthtxt
                                        ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC
                                        LIMIT 1";
                          $resultLatest = mysqli_query($conn, $sqlLatest);
                          if ($rowLatest = mysqli_fetch_assoc($resultLatest)) {
                            $latestMonth = $rowLatest['monthtxt'];

                            // แปลงเป็นวันที่แล้วลบ 1 เดือน
                            $dateObj = DateTime::createFromFormat('n-Y', $latestMonth);
                            if (!$dateObj) {
                              $dateObj = DateTime::createFromFormat('m-Y', $latestMonth); // รองรับ "03-2025"
                            }
                            if ($dateObj) {
                              $dateObj->modify('-1 month');
                              $gmm2 = $dateObj->format('n-Y'); // เช่น จาก "1-2025" จะได้ "12-2024"
                            }
                          }

                          $sqlzm = "SELECT monthtxt
                                    FROM imr_tb_debtor_rights_opd
                                    GROUP BY monthtxt
                                    ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC";
                          $resultzm = mysqli_query($conn, $sqlzm);
                          if (mysqli_num_rows($resultzm) > 0) {
                            while ($rowzm = mysqli_fetch_assoc($resultzm)) {
                              $month = $rowzm['monthtxt'];
                              $selected = ($month == $latestMonth) ? 'selected' : '';
                              echo '<option value="'.$month.'" '.$selected.'>ข้อมูลเดือน '.$month.'</option>';
                            }
                          } else {
                            echo '<option value="0-0000" selected>-ไม่มีข้อมูล-</option>';
                          }
                          ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- Toolbar การตรวจสอบยันยอดบัญชี (Reconciliation Audit Toolbar) -->
                  <div class="card mb-3 border-0 shadow-sm recon-audit-toolbar-card position-relative overflow-hidden" style="border-radius: 14px; background: #ffffff;">
                    <!-- แถบ Progress Bar เมื่อกำลังประมวลผลข้อมูล -->
                    <div id="toolbarAuditLoadingBar" class="progress position-absolute top-0 start-0 w-100" style="height: 3px; border-radius: 14px 14px 0 0; display: none; z-index: 10;">
                      <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%;"></div>
                    </div>
                    <div class="card-body py-3 px-4">
                      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                          <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.2);">
                            <i class='bx bx-check-shield'></i>
                          </div>
                          <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                              <span class="fw-bold text-dark fs-6">สถานะยันยอดบัญชี (Reconciliation Status)</span>
                              <span class="badge bg-label-primary rounded-pill small" id="toolbarAuditMonthBadge">-</span>
                              <span class="badge bg-label-warning rounded-pill small" id="toolbarAuditProcessingBadge" style="display: none;"><i class='bx bx-loader-alt bx-spin me-1'></i>กำลังประมวลผลข้อมูล...</span>
                              <span class="badge rounded-pill small" id="toolbarAuditClosingBadge" style="display: none;">-</span>
                            </div>
                            <div class="d-flex align-items-center gap-3 mt-1 flex-wrap">
                              <span class="small text-muted">ผังทั้งหมด: <strong id="toolbarAuditTotal" class="text-dark">0</strong></span>
                              <span class="small text-success"><i class='bx bx-check-circle me-1'></i>ตรงกัน: <strong id="toolbarAuditMatched">0</strong></span>
                              <span class="small text-danger" id="toolbarMismatchWrap"><i class='bx bx-error-circle me-1'></i>มียอดต่าง: <strong id="toolbarAuditMismatched">0</strong></span>
                              <span class="small text-warning" id="toolbarPendingWrap"><i class='bx bx-time me-1'></i>รอดำเนินการ: <strong id="toolbarAuditPending">0</strong></span>
                              <span class="small text-muted" id="toolbarVarianceWrap">ผลต่างรวม: <strong id="toolbarAuditVariance" class="text-danger">฿0.00</strong></span>
                              <span class="small text-secondary" id="toolbarClosingInfoWrap"><i class='bx bx-calendar-check me-1 text-primary'></i>กำหนดปิดงบ: <strong id="toolbarClosingDateText" class="text-dark">-</strong></span>
                            </div>
                          </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                          <button type="button" class="btn btn-primary rounded-pill btn-sm px-3 shadow-sm" onclick="openReconAuditModal()" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); border: none;">
                            <i class='bx bx-check-shield me-1'></i> ตรวจสอบยันยอดบัญชี (Audit Center)
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Card แสดงตารางรายงานทะเบียนคุมลูกหนี้ยันยอด -->
                  <div class="card recon-table-card">
                    <div class="row row-bordered g-0">
                      <div class="col-md-12" style="text-align: center;">
                        <div style="padding: 20px;">
                          <div class="table-responsive">
                            <table class="responsive-table" id="tbbleone">
                              <thead>
                                <tr>
                                  <th colspan="17" style="text-align: center;font-size: 16px; background-color: #fff;border: none;"><?php echo $hospital; ?> ทะเบียนคุมลูกหนี้สิทธิ <span id="vtxt3"><?php $result1 = DateThaiM($gmm);echo $result1['fiscalYear']; ?></span><br>เดือน <span id="vtxt0"><?php $result1 = DateThaiM($gmm); echo $result1['fullDate']; ?></span></th>
                                </tr>
                                <tr>
                                  <th rowspan="2" style="text-align: center;width: 9%;">รหัสผังบัญชี</th>
                                  <th rowspan="2" style="text-align: center;width: 50%;">ชื่อผังบัญชี</th>
                                  <th rowspan="2" style="text-align: center;width: 9%;color: #0004a1;" >ลูกหนี้สิทธิยกมา<br>เดือน <span id="vtxt4"><?php $result1 = DateThaiMlast($gmm2); echo $result1['fullDate']; ?></span></th>
                                  <th rowspan="2" style="text-align: center;width: 7%;">จำนวนราย</th>
                                  <th rowspan="2" style="text-align: center;width: 9%;">ลูกหนี้สิทธิ /บาท<br>กลุ่มงานประกันฯ</th>
                                  <th rowspan="2" style="text-align: center;width: 9%;background: #fff3d7;">งบทดลอง /บาท<br>กลุ่มงานบัญชี</th>
                                  <th rowspan="2" style="text-align: center;width: 7%;">ส่วนต่าง</th>
                                  <th rowspan="2" style="text-align: center;width: 12%;background: #f2ffd7;">เงินโอน/บาท<br>เดือน <span id="vtxt1"><?php $result1 = DateThaiM($gmm); echo $result1['fullDate1']; ?></span></th>
                                  <th rowspan="2" style="text-align: center;width: 12%;background: #f2ffd7;">ตัดลูกหนี้/บาท<br>เดือน <span id="vtxt2"><?php $result1 = DateThaiM($gmm); echo $result1['fullDate1']; ?></span></th>

                                  <th rowspan="2" style="text-align: center;width: 12%;background: #d7ffda;">งบทดลอง<br>บัญชี <span id="vtxt15"></span></th>
                                  <th rowspan="2" style="text-align: center;width: 12%;background: #d7ffda;">ส่วนต่าง<span id="vtxt16"></span></th>

                                  <th rowspan="2" style="text-align: center;width: 12%;">ลูกหนี้คงเหลือ<br>ยกไปเดือนถัดไป</th>
                                  <th colspan="5">หมายเหตุ</th>
                                </tr>
                                <tr>
                                  <th style="text-align: center;width: 12%;">บันทึกลูกหนี้ต่ำไป</th>
                                  <th style="text-align: center;width: 12%;">บันทึกลูกหนี้สูงไป</th>
                                  <th style="text-align: center;width: 12%;">ปรับปรุงลูกหนี้<br>เดือนที่ผ่านมา</th>
                                  <th style="text-align: center;width: 12%;">หมายเหตุ</th>
                                  <th style="text-align: center;width: 8%;">รายละเอียด<br>ปรับปรุง</th>
                                </tr>
                              </thead>
                              <tbody id="tt01">



                              </tbody>
                            </table>
                          </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end flex-wrap gap-2 mb-4 me-3">
                          <button id="save" class="btn rounded-pill btn-success" style="background-color: #00831b;"><span class="tf-icons bx bx-edit"></span> บันทึกข้อมูล</button>
                          <button onclick="exportToPDF()" class="btn rounded-pill btn-primary"><span class="tf-icons bx bx-printer"></span> พิมพ์รายงาน</button>
                          <button onclick="settingexportToPDF()" class="btn rounded-pill btn-danger"><span class="tf-icons bx bx-brightness"></span> ตั้งค่าออกรายงาน</button>
                          <?php if (in_array($_SESSION['role'], ['finance', 'admin'])): ?>
                          <button onclick="settinglist()" class="btn rounded-pill btn-danger"><span class="tf-icons bx bx-brightness"></span> ตั้งค่าทะเบียนคุม</button>
                          <?php endif; ?>
                        </div>

                      </div>
                    </div>
                  </div>



                  <div class="card" style="display: none;">
                    <div class="row row-bordered g-0">
                      <div class="col-md-12" style=" text-align: center;">
                       
                          <div style="padding: 20px;" >
                            <table  class="responsive-table" id="tableToPDF">
                              <thead>
                                <tr>
                                  <th colspan="14" style="text-align: center;font-size: 16px; background-color: #fff;border: none;"><?php echo $hospital; ?> ทะเบียนคุมลูกหนี้สิทธิ <span id="pvtxt3"><?php $result1 = DateThaiM($gmm);echo $result1['fiscalYear']; ?></span><br>เดือน <span id="pvtxt0"><?php $result1 = DateThaiM($gmm); echo $result1['fullDate']; ?></span></th>
                                </tr>
                                <tr>
                                  <th rowspan="2" style="text-align: center;width: 50%;">ชื่อผังบัญชี</th>
                                  <th rowspan="2" style="text-align: center;width: 9%;" >ลูกหนี้สิทธิยกมา<br>เดือน <span id="pvtxt4"><?php $result1 = DateThaiMlast($gmm2); echo $result1['fullDate']; ?></span></th>
                                  <th rowspan="2" style="text-align: center;width: 9%;">ลูกหนี้สิทธิ /บาท<br>กลุ่มงานประกันฯ</th>
                                  <th rowspan="2" style="text-align: center;width: 9%;">งบทดลอง /บาท<br>กลุ่มงานบัญชี</th>
                                  <th rowspan="2" style="text-align: center;width: 7%;">ส่วนต่าง</th>
                                  <th rowspan="2" style="text-align: center;width: 12%;">เงินโอน/บาท<br>เดือน <span id="pvtxt1"><?php $result1 = DateThaiM($gmm); echo $result1['fullDate1']; ?></span></th>
                                  <th rowspan="2" style="text-align: center;width: 12%;">ตัดลูกหนี้/บาท<br>เดือน <span id="pvtxt2"><?php $result1 = DateThaiM($gmm); echo $result1['fullDate1']; ?></span></th>

                                  <th rowspan="2" style="text-align: center;width: 12%;">งบทดลอง<br>บัญชี <span id="pvtxt15"></span></th>
                                  <th rowspan="2" style="text-align: center;width: 12%;">ส่วนต่าง<span id="pvtxt16"></span></th>

                                  <th rowspan="2" style="text-align: center;width: 12%;">ลูกหนี้คงเหลือ<br>ยกไปเดือนถัดไป</th>
                                  <th colspan="3">หมายเหตุ</th>
                                </tr>
                                <tr>
                                  <th style="text-align: center;width: 12%;">บันทึกลูกหนี้ต่ำไป</th>
                                  <th style="text-align: center;width: 12%;">บันทึกลูกหนี้สูงไป</th>
                                  <th style="text-align: center;width: 12%;">ปรับปรุงลูกหนี้<br>เดือนที่ผ่านมา</th>

                                </tr>
                              </thead>
                              <tbody id="tt011">



                              </tbody>
                            </table>
                          </div>


                      </div>
                    </div>
                  </div>



                </div>
        
              </div>
            </div>
            <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-fluid">
                <div class="mb-2 mb-md-0">
                  ระบบบริหารลูกหนี้โรงพยาบาลอิเล็กทรอนิกส์ eDebtor Hospital System (eDHS)<br>
                  <small>© 2024 Project Application & Code Application by นายจิรันธนิน ประสารกุลนันท์ นักวิชาการคอมพิวเตอร์</small>
                </div>

              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <div class="buy-now" style="display:none;">
      <a
        href="http://phonsai-hos.com/"
        target="_blank"
        class="btn btn-danger btn-buy-now"
        >www.phonsai-hos.com</a
      >
    </div>


    <div class="modal fade" id="summary-report101" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog" role="document" style="max-width: 90%; width: 90%; margin-top: 2vh;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel4">บันทึกข้อความสรุปรายงานสถานะลูกหนี้</h5>
            <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
            ></button>
          </div>
          <div class="modal-body" style="padding: 10px 20px;">
            <div class="row">
              <div class="col mb-12">
                <div style="text-align: center;">
                  <iframe align="middle" frameborder="0" style="height: 85vh;" id="pdfview" 
                  scrolling="yes"
                  src=""
                  width="100%">
                  </iframe>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer" style="padding-top: 0;">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              ปิดหน้าต่าง
            </button>
          </div>
        </div>
      </div>
    </div>



    <!-- Extra Large Modal -->
    <div class="modal fade" id="summary-report" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel4">รายงานสรุปลูกหนี้สิทธิ</h5>
            <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col mb-12">
                <div style="text-align: center;">
                  <iframe align="middle" frameborder="1" height="970" id="pdfview" 
                  scrolling="yes"
                  src=""
                  width="100%">
                </iframe>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>




  <!-- Modal for Updated Debtors -->
  <div class="modal fade" id="updated-debtors-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">รายการลูกหนี้ที่มีการปรับปรุง</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead class="table-light">
                <tr>
                  <th class="text-center">VN/AN</th>
                  <th class="text-center">ชื่อ-สกุล</th>
                  <th class="text-center">ค่ารักษาเดิม (บาท)</th>
                  <th class="text-center">ค่ารักษาใหม่ (บาท)</th>
                  <th class="text-center">เจ้าหน้าที่ผู้ปรับปรุง</th>
                  <th class="text-center">ไฟล์หลักฐาน</th>
                </tr>
              </thead>
              <tbody id="updated-debtors-tbody">
                <!-- Data injected via AJAX -->
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <a id="btn-export-excel" href="#" class="btn btn-success"><i class="bx bx-export"></i> ส่งออก Excel</a>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal ตั้งค่ารายชื่อและหน้าที่ผู้ลงนามรายงานสรุปลูกหนี้สิทธิ -->
  <?php include './includes/modal_summary_responsibles.php'; ?>
  <!-- Modal ศูนย์ตรวจสอบยันยอดบัญชีและการวินิจฉัย 3 มิติ -->
  <?php include './includes/modal_recon_audit.php'; ?>


    <!-- Extra Large Modal -->
    <div class="modal fade" id="setting_list" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel4">เลือกเดือนในการตั้งลูกหนี้ยกไป</h5>
            <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <div class="row">
                <label class="recon-field-label mb-2"><i class='bx bx-calendar-plus text-success'></i> เลือกเดือนในการตั้งลูกหนี้ยกไป</label>
                <select id="selectTypeOptlist" name="selectTypeOptlist" class="form-select recon-select">
                <option value="0-0000" selected disabled>-เลือกเดือนในการตั้งลูกหนี้ยกไป-</option>
                <?php
                        // เดือนปัจจุบัน
                        $gmm = date('n-Y'); // เช่น "3-2025"
                        $gmm2 = '';         // เดือนก่อนหน้า $latestMonth
                        $latestMonth = '';

                        // ดึงเดือนล่าสุดจากฐานข้อมูล
                        $sqlLatest = "SELECT monthtxt
                        FROM imr_tb_debtor_rights_opd
                        GROUP BY monthtxt
                        ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC
                        LIMIT 1";
                        $resultLatest = mysqli_query($conn, $sqlLatest);
                        if ($rowLatest = mysqli_fetch_assoc($resultLatest)) {
                          $latestMonth = $rowLatest['monthtxt'];

                          // แปลงเป็นวันที่แล้วลบ 1 เดือน
                          $dateObj = DateTime::createFromFormat('n-Y', $latestMonth);
                          if (!$dateObj) {
                            $dateObj = DateTime::createFromFormat('m-Y', $latestMonth); // รองรับ "03-2025"
                          }
                          if ($dateObj) {
                            $dateObj->modify('-1 month');
                            $gmm2 = $dateObj->format('n-Y'); // เช่น จาก "1-2025" จะได้ "12-2024"
                          }
                        }

                        $sqlzm = "SELECT monthtxt
                        FROM imr_tb_debtor_rights_opd
                        GROUP BY monthtxt
                        ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC";
                        $resultzm = mysqli_query($conn, $sqlzm);
                        if (mysqli_num_rows($resultzm) > 0) {
                          while ($rowzm = mysqli_fetch_assoc($resultzm)) {
                            $month = $rowzm['monthtxt'];
                            
                            echo '<option value="'.$month.'" >ข้อมูลเดือน '.$month.'</option>';
                          }
                        } else {
                          echo '<option value="0-0000" selected>-ไม่มีข้อมูล-</option>';
                        }
                        ?>


                      </select>

                          <div style="padding: 20px;" >
                            <p id="textalertlist" style="text-align: center; color: red;"></p>
                            <table  class="responsive-table" id="tbbleone1">
                              <thead>
                                <tr>
                                  <th colspan="5" style="text-align: center;font-size: 16px; background-color: #fff;border: none;"><?php echo $hospital; ?> ทะเบียนคุมลูกหนี้สิทธิ 
                                    <span id="vtxt3"><?php $result1 = DateThaiM($gmm); ?></span> (ตั้งลูกหนี้ยกไป)</span></th>
                                </tr>
                                <tr>
                                  <th rowspan="2" style="text-align: center;width: 9%;">รหัสผังบัญชี</th>
                                  <th rowspan="2" style="text-align: center;width: 50%;">ชื่อผังบัญชี</th>
                                  <th rowspan="2" style="text-align: center;width: 7%;">จำนวนราย</th>
                                  <th rowspan="2" style="text-align: center;width: 9%;">ลูกหนี้สิทธิ /บาท<br>กลุ่มงานประกันฯ</th>
                                  <th rowspan="2" style="text-align: center;width: 12%;">ลูกหนี้คงเหลือ<br>ยกไปเดือนถัดไป</th>
                                </tr>
                              </thead>
                              <tbody id="ttlist">


                              </tbody>
                            </table>
                          </div>



                    </div>
                  </div>

                  <div class="modal-footer border-top-0 pt-0 pb-3 px-4 d-flex justify-content-end gap-2">
                    <button id="savelist" class="btn rounded-pill btn-success" style="background-color: #00831b; display: none;"><span class="tf-icons bx bx-edit"></span> ยืนยันข้อมูล</button>
                    <a class="btn rounded-pill btn-primary px-4" href="javascript:void(0);" onclick="openModaladd();"><span class="tf-icons bx bx-edit"></span> เพิ่มผังบัญชี</a>
                  </div>

        </div>
      </div>
    </div>



<div class="modal fade" id="loginModaladd" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document"> 
    <div class="modal-content" style="border-radius: 10px; overflow: hidden; background-color: #f5f5f9;">
      
      <div class="modal-header border-bottom pb-3">
        <h5 class="modal-title" style="color: #0004a1; font-weight: 600;">
          <i class="bx bx-edit me-2"></i> เพิ่มผังบัญชี
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="margin-top: 1px; margin-right: 1px;"></button>
      </div>
      
      <div class="modal-body" style="padding: 25px;">
        <form id="debtorForm">
          
          <div class="row mb-4 bg-light p-3 rounded" style="border: 1px dashed #696cff; margin-left: 0; margin-right: 0;">
            <div class="col-md-12">
              <label for="search_account" class="form-label text-primary fw-bold">🔍 ค้นหาผังบัญชี (พิมพ์ รหัส หรือ ชื่อ)</label>
              <select id="search_account" class="form-select select2" style="width: 100%;">
                <option value="">-- พิมพ์ค้นหา รหัส หรือ ชื่อบัญชี --</option>
                <?php
                // ดึงข้อมูลจากตาราง tb_code
                $sql_code = "SELECT Code, Name FROM tb_code ORDER BY Code ASC";
                $query_code = mysqli_query($conn, $sql_code);
                
                if ($query_code) {
                    while ($row = mysqli_fetch_assoc($query_code)) {
                        echo '<option value="' . $row['Code'] . '" data-name="' . $row['Name'] . '">' . $row['Code'] . ' - ' . $row['Name'] . '</option>';
                    }
                }
                ?>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="patient_type" class="form-label">ประเภทผู้ป่วย</label>
              <select id="patient_type" name="patient_type" class="form-select">
                <option value="OPD">OPD</option>
                <option value="IPD">IPD</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="accountcode" class="form-label">Account Code</label>
              <input type="text" id="accountcode" name="accountcode" class="form-control" placeholder="เช่น 1102050101.201">
            </div>
            <div class="col-md-4 mb-3">
              <label for="accountname" class="form-label">Account Name</label>
              <input type="text" id="accountname" name="accountname" class="form-control" placeholder="ชื่อบัญชี เช่น ลูกหนี้ค่ารักษา UC- OP">
            </div>
          </div>

          <hr class="my-3">

          <div class="row">
            <div class="col-md-4 mb-4">
              <label for="hospcode" class="form-label">รหัสสถานพยาบาล (Hospcode)</label>
              <input type="text" id="hospcode" name="hospcode" class="form-control" placeholder="เช่น 11072">
            </div>
            <div class="col-md-4 mb-4">
              <label for="monthtxt" class="form-label">เดือน-ปี (Monthtxt)</label>
              <input type="text" id="monthtxt" name="monthtxt" class="form-control" placeholder="เช่น 1-2025 หรือ 11-2026" maxlength="7">
            </div>
            <div class="col-md-4 mb-4">
              <label for="totalall" class="form-label">Total All</label>
              <input type="number" step="0.01" id="totalall" name="totalall" class="form-control" placeholder="0.00">
            </div>
          </div>

          <div class="row" style="display:none;">
            <div class="col-md-6 mb-3">
              <label for="vn" class="form-label">VN</label>
              <input type="text" id="vn" name="vn" class="form-control" placeholder="ระบุ VN" value="" readonly>
            </div>
            <div class="col-md-6 mb-3">
              <label for="hn" class="form-label">HN</label>
              <input type="text" id="hn" name="hn" class="form-control" placeholder="ระบุ HN" value="999999999" readonly>
            </div>
          </div>

          <div class="row" style="display:none;">
            <div class="col-md-6 mb-3">
              <label for="cid" class="form-label">เลขบัตรประชาชน (CID)</label>
              <input type="text" id="cid" name="cid" class="form-control" placeholder="ระบุเลข 13 หลัก" maxlength="13" value="9999999999999" readonly>
            </div>
            <div class="col-md-6 mb-3">
              <label for="mobile" class="form-label">เบอร์โทรศัพท์ (Mobile)</label>
              <input type="text" id="mobile" name="mobile" class="form-control" placeholder="ระบุเบอร์โทรศัพท์" value="-" readonly>
            </div>
          </div>

        </form>
      </div>
      
      <div class="modal-footer border-top pt-3">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" id="btnSaveDebtor">
          <i class="bx bx-save me-1"></i> บันทึกข้อมูล
        </button>
      </div>
      
    </div>
  </div>
</div>




<!-- ใส่ใน <body> ท้ายสุด -->
<a href="logout.php" class="floating-logout-btn">
    <iconify-icon icon="solar:logout-2-bold" width="22" style="vertical-align: middle; margin-right: 8px;"></iconify-icon> 
</a>

<!-- โหลด Iconify (CDN) -->
<script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>



    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="../assets/vendor/js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="../assets/js/dashboards-analytics.js"></script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="./chart/highcharts.js"></script>
    <script src="./chart/data.min.js"></script>
    <script src="./chart/exporting.min.js"></script>
    <script src="./chart/accessibility.min.js"></script>

    <script>

      // =================================================================
      // 🌟 ออโตเมชั่นสเต็ป: กดพิมพ์ -> แอบบันทึกตารางลงโฟลเดอร์ -> โชว์ Modal พรีวิว
      // =================================================================
      function exportToPDF() {
          // 1. ดึงค่าเดือนที่เลือก
          const selectedMonth = $('#selectTypeOpt').val();
          
          // 2. เช็คความถูกต้องของข้อมูล
          if (!selectedMonth || selectedMonth === '0-0000') {
              Swal.fire('แจ้งเตือน', 'กรุณาเลือกเดือนก่อนพิมพ์รายงาน', 'warning');
              return;
          }

          // 3. แสดงหน้าต่าง Loading เพื่อล็อกหน้าจอไว้ชั่วคราว ป้องกันการกดซ้ำซ้อน
          Swal.fire({
              title: 'ระบบกำลังทำงาน...',
              html: '📊 กรุณารอสักครู่ ระบบจะเปิดหน้าต่างพรีวิวให้อัตโนมัติ</small>',
              allowOutsideClick: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          // 4. เริ่มกลไกการโคลนและจัดฟอร์แมตตาราง (ลอจิกเดิมของพี่เป๊ะๆ)
          const originalTable = document.getElementById("tableToPDF");
          const clonedTable = originalTable.cloneNode(true);

          // ลบสีพื้นหลัง เปลี่ยนเป็นขาวดำเพื่อความสะอาดและประหยัดหมึกพิมพ์
          clonedTable.querySelectorAll('*').forEach(el => {
              el.style.color = '#000000';
              el.style.borderColor = '#000000';
              if (el.style.backgroundColor && el.style.backgroundColor !== 'rgb(255, 255, 255)' && el.style.backgroundColor !== 'transparent') {
                  el.style.backgroundColor = 'transparent';
              }
          });

          const wrapper = document.createElement("div");
          wrapper.appendChild(clonedTable);
          wrapper.style.color = "#000";

          const opt = {
              margin: [10, 10, 10, 10], 
              filename: '2_ทะเบียนคุม_' + selectedMonth + '.pdf',
              image: { type: 'jpeg', quality: 0.98 },
              html2canvas: { 
                  scale: 2, 
                  useCORS: true,
                  scrollY: 0 
              },
              jsPDF: { unit: 'mm', format: 'a3', orientation: 'landscape' }, 
              pagebreak: { 
                  mode: ['avoid-all', 'css', 'legacy'],
                  before: '.page-break-before', 
                  avoid: '.avoid-break'       
              }
          };

          // 5. เปลี่ยนจากคำสั่ง .save() ดาวน์โหลดลงเครื่อง มาเป็นส่งออกแบบ .outputPdf('blob') เพื่อส่งเข้าเซิร์ฟเวอร์
          html2pdf().set(opt).from(wrapper).outputPdf('blob').then(function (tableBlob) {
              
              // แพ็กไฟล์ยัดใส่ FormData เตรียมยิง AJAX เข้าสถาปัตยกรรมความปลอดภัย PHP ที่เราเขียนไว้
              let formData = new FormData();
              formData.append('pdf_file', tableBlob);
              formData.append('month', selectedMonth);
              formData.append('file_type', 'table'); // ระบุปลายทางว่าเป็นไฟล์ตารางทะเบียนคุม

              // ใช้ Fetch API (หรือพี่จะเปลี่ยนเป็น $.ajax ของ jQuery ที่พี่ถนัดก็ได้ครับ ทำงานได้เหมือนกัน)
              fetch('save_pdf_automation.php', {
                  method: 'POST',
                  body: formData
              })
              .then(response => response.json())
              .then(res => {
                  if (res.status === 'success') {
                      // บันทึกไฟล์ที่เซิร์ฟเวอร์เรียบร้อย -> ปิดหน้าต่างโหลดติ้วๆ ออกไป
                      Swal.close();

                      // 6. โชว์มอดอลและส่งค่าข้ามไปที่ไฟล์พรีวิวทันทีตามเป้าหมายหลัก
                      $('#exampleModalLabel4').text('บันทึกข้อความสรุปรายงานสถานะลูกหนี้');
                      
                      // โหลดหน้าต่าง memo_preview.php เข้ามาแสดงใน iframe พร้อมแนบตัวแปรเดือน
                      $("#pdfview").attr("src", "memo_preview.php?dmonth=" + selectedMonth);
                      
                      // สั่งเปิด Modal ขยายใหญ่เต็มจอที่เราตั้งค่ากันไว้
                      $("#summary-report101").modal('show');

                  } else {
                      Swal.fire('ระบบบันทึกติดขัด', 'เซิร์ฟเวอร์ปฏิเสธการเซฟไฟล์ตาราง: ' + res.message, 'error');
                  }
              })
              .catch(err => {
                  Swal.fire('ข้อผิดพลาดทางเทคนิค', 'ไม่สามารถเชื่อมต่อไปยังไฟล์ save_pdf_automation.php ได้ กรุณาเช็คความถูกต้องของระบบเน็ตเวิร์ก: ' + err.message, 'error');
              });
          });
      }





        function settingexportToPDF() {
              $('#setting_summary-report').modal('show');
        }

        function settinglist() {
              $('#setting_list').modal('show');
        }



        function settingtxt() {
              var s1 = $('#s1txt').val() || '';
              var p1 = $('#p1txt').val() || '';
              var s2 = $('#s2txt').val() || '';
              var p2 = $('#p2txt').val() || '';
              var s3 = $('#s3txt').val() || '';
              var p3 = $('#p3txt').val() || '';
              var s4 = $('#s4txt').val() || '';
              var p4 = $('#p4txt').val() || '';
              var s5 = $('#s5txt').val() || '';
              var p5 = $('#p5txt').val() || '';

              $('#s11txt').text(s1 ? '(' + s1 + ')' : '');
              $('#p11txt').text(p1 ? 'ตำแหน่ง ' + p1 : '');

              $('#s22txt').text(s2 ? '(' + s2 + ')' : '');
              $('#p22txt').text(p2 ? 'ตำแหน่ง ' + p2 : '');
              
              $('#s33txt').text(s3 ? '(' + s3 + ')' : '');
              $('#p33txt').text(p3 ? 'ตำแหน่ง ' + p3 : '');
              
              $('#s44txt').text(s4 ? '(' + s4 + ')' : '');
              $('#p44txt').text(p4 ? 'ตำแหน่ง ' + p4 : '');
              
              $('#s55txt').text(s5 ? '(' + s5 + ')' : '');
              $('#p55txt').text(p5 ? 'ตำแหน่ง ' + p5 : '');

              $('#ps11txt').text(s1 ? '(' + s1 + ')' : '');
              $('#pp11txt').text(p1 ? 'ตำแหน่ง ' + p1 : '');

              $('#ps22txt').text(s2 ? '(' + s2 + ')' : '');
              $('#pp22txt').text(p2 ? 'ตำแหน่ง ' + p2 : '');
              
              $('#ps33txt').text(s3 ? '(' + s3 + ')' : '');
              $('#pp33txt').text(p3 ? 'ตำแหน่ง ' + p3 : '');
              
              $('#ps44txt').text(s4 ? '(' + s4 + ')' : '');
              $('#pp44txt').text(p4 ? 'ตำแหน่ง ' + p4 : '');
              
              $('#ps55txt').text(s5 ? '(' + s5 + ')' : '');
              $('#pp55txt').text(p5 ? 'ตำแหน่ง ' + p5 : '');
        }

    </script>

        <script>


          function openModaladd() {
              $('#loginModaladd').modal('show');
              $('#setting_list').modal('hide');  
          }


          $(document).ready(function() {
              $('#btnSaveDebtor').click(function(e) {
                  e.preventDefault();

                  // เช็คข้อมูลเบื้องต้นก่อนว่ากรอกครบไหม
                  var accountcode = $('#accountcode').val();
                  var monthtxt = $('#monthtxt').val();
                  
                  if(accountcode === '' || monthtxt === '') {
                       Swal.fire('แจ้งเตือน', 'กรุณากรอก Account Code และ เดือน-ปี ให้ครบถ้วน', 'warning');
                       return;
                  }

                  // ดึงข้อมูลทั้งหมดจาก Form และเติม action flag ลงไป
                  var formData = $('#debtorForm').serialize() + '&action=save_new_debtor';

                  $.ajax({
                      url: 'datatimestamp-insert2.php',
                      type: 'POST',
                      data: formData,
                      dataType: 'json', // ให้รับค่ากลับมาเป็น JSON
                      beforeSend: function() {
                          // เปลี่ยนปุ่มเป็นสถานะกำลังโหลด
                          $('#btnSaveDebtor').prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i> กำลังบันทึก...');
                      },
                      success: function(response) {

                        $('#loginModaladd').modal('hide');

                          if(response.status === 'success') {
                              Swal.fire({
                                  icon: 'success',
                                  title: 'สำเร็จ!',
                                  text: response.message,
                                  confirmButtonText: 'ตกลง'
                              }).then(() => {
                                  // ซ่อน Modal, ล้างฟอร์ม และรีโหลดหน้าเพื่อแสดงข้อมูลใหม่
                                  $('#loginModaladd').modal('hide');
                                  $('#debtorForm')[0].reset();
                                  location.reload(); 
                              });
                          } else {
                              Swal.fire('เกิดข้อผิดพลาด', response.message, 'error');
                          }
                      },
                      error: function(xhr, status, error) {
                          Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: ' + error, 'error');
                      },
                      complete: function() {
                          // คืนค่าปุ่มกลับมาเหมือนเดิม
                          $('#btnSaveDebtor').prop('disabled', false).html('<i class="bx bx-save me-1"></i> บันทึกข้อมูล');
                      }
                  });
              });
          });



          $(document).ready(function() {
              // ดักจับเหตุการณ์ตอนที่ Modal กำลังจะถูกเปิดขึ้นมา
              $('#loginModaladd').on('show.bs.modal', function () {
                  
                  let randomVN = '';
                  // ลูปสุ่มตัวเลข 0-9 จำนวน 12 ครั้ง
                  for (let i = 0; i < 12; i++) {
                      randomVN += Math.floor(Math.random() * 10);
                  }
                  
                  // เอาเลขที่สุ่มได้ไปใส่ใน input ที่ id="vn"
                  $('#vn').val(randomVN);
              });


              // 1. ดักไม่ให้พิมพ์ตัวอักษร ให้พิมพ์ได้เฉพาะตัวเลข 0-9 และเครื่องหมายขีด (-)
              $('#monthtxt').on('input', function() {
                  var value = $(this).val().replace(/[^0-9\-]/g, '');
                  $(this).val(value);
              });

              // 2. ตรวจสอบฟอร์แมตตอนพิมพ์เสร็จ (เมื่อคลิกออกนอกช่อง)
              $('#monthtxt').on('blur', function() {
                  var value = $(this).val();
                  
                  // Regex อธิบาย: 
                  // ^(1[0-2]|[1-9]) = เลขเดือน 1 ถึง 12 (ห้ามพิมพ์ 01 ให้พิมพ์ 1)
                  // - = เครื่องหมายขีดกลาง
                  // [0-9]{4}$ = ปีต้องเป็นตัวเลข 4 หลัก
                  var regex = /^(1[0-2]|[1-9])-[0-9]{4}$/;
                  
                  if(value !== '' && !regex.test(value)) {
                      // ถ้าพิมพ์ผิดฟอร์แมต ให้แจ้งเตือนและล้างช่องให้กรอกใหม่
                      Swal.fire({
                          icon: 'warning',
                          title: 'รูปแบบไม่ถูกต้อง',
                          text: 'กรุณาระบุ เดือน-ปี ให้ถูกต้อง เช่น 1-2025, 11-2026 (ไม่ต้องเติมเลข 0 หน้าเดือน)',
                          confirmButtonText: 'ตกลง'
                      });
                      $(this).val(''); // ล้างค่าทิ้งให้กรอกใหม่
                  }
              });


          });

          $(document).ready(function() {
              // 1. เปิดใช้งาน Select2 ให้ค้นหาได้
              // *ข้อควรระวัง: เวลาใช้ Select2 ใน Modal ต้องกำหนด dropdownParent เสมอ ไม่งั้นจะคลิกพิมพ์ไม่ได้ครับ
              $('#search_account').select2({
                  dropdownParent: $('#loginModaladd'),
                  placeholder: "-- พิมพ์ค้นหา รหัส หรือ ชื่อบัญชี --",
                  allowClear: true
              });

              // 2. เมื่อพี่คลิกเลือกบัญชี ให้ดึงค่าไปเติมในช่องอัตโนมัติ
              $('#search_account').on('change', function() {
                  var selected = $(this).find('option:selected');
                  var code = selected.val();               // ดึงค่า Code จาก value
                  var name = selected.data('name');        // ดึงค่า Name จาก data-name

                  // เอาไปหยอดใส่ input ที่พี่ทำไว้
                  $('#accountcode').val(code);
                  $('#accountname').val(name);
              });
          });

          $(document).ready(function () {

            var yyy = '<?php echo $yyy; ?>';


            var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

            if (!isLoggedIn) {
              location.href = './login.php';
            }else{
             fetch_text();
             settingtxt();
            // เรียกให้ทำงานอัตโนมัติเมื่อโหลดหน้า
             $('#selectTypeOpt').trigger('change');

           }
           

          function cleanNumber(val) {
              if (!val || val.trim() === '') return null;
              return val.replace(/,/g, '');
          }


          $('#save').click(function () {
            let data = [];

            console.log("ข้อมูลที่ถูกส่งไปยัง PHP:", data);


            $('#tbbleone tr[data-code]').each(function () {
            let row = $(this); // อ้างถึงแถวปัจจุบัน
            let code = row.data('code'); // รหัสผังบัญชี
            let month = row.data('month'); // วันที่ปัจจุบัน

            let column6  = cleanNumber(row.find('td:nth-child(6)').text());
            let column8  = cleanNumber(row.find('td:nth-child(8)').text());
            let column9  = cleanNumber(row.find('td:nth-child(9)').text());
            let column10  = cleanNumber(row.find('td:nth-child(10)').text());
            let column7 = row.find('td:nth-child(7)').text();
            let column11 = row.find('td:nth-child(11)').text();
            let column12 = row.find('td:nth-child(12)').text();
            let column13  = cleanNumber(row.find('td:nth-child(13)').text());
            let column14  = cleanNumber(row.find('td:nth-child(14)').text());
            let column15  = cleanNumber(row.find('td:nth-child(15)').text());
            let column16 = row.find('td:nth-child(16)').text();

              let rowData = {
                code: code,
                month: month,
                column6: column6,
                column7: column7,
                column8: column8,
                column9: column9,
                column10: column10,
                column11: column11,
                column12: column12,
                column13: column13,
                column14: column14,
                column15: column15,
                column16: column16,
              };

              data.push(rowData);
            });

            const selectedMonth = $('#selectTypeOpt').val(); 


              //console.log("ข้อมูลที่ถูกเก็บ:", data);
            

              if (confirm("คุณต้องการบันทึกข้อมูลหรือไม่?")) {
                  $.ajax({
                      url: 'ทะเบียนคุมลูกหนี้1.php',
                      method: 'POST',
                      data: { records: JSON.stringify(data) },
                      success: function (response) {
                    
                          alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                           // 👉 เลื่อนขึ้นบนสุดแบบนุ่ม ๆ
                          $('html, body').animate({ scrollTop: 0 }, 500);
                          gprintPDF(selectedMonth);
                      },
                      error: function () {
                         alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                      }
                  });
              } else {
                  alert("การบันทึกข้อมูลถูกยกเลิก");
              }

            });

           


          $('#savelist').click(function () {
            let data = [];

            //console.log("ข้อมูลที่ถูกส่งไปยัง PHP:", data);


            $('#tbbleone1 tr[data-code]').each(function () {
            let row = $(this); // อ้างถึงแถวปัจจุบัน
            let code = row.data('code'); // รหัสผังบัญชี
            let month = row.data('month'); // วันที่ปัจจุบัน
            let column12 = row.find('td:nth-child(5)').text();

            let rowData = {
              code: code,
              month: month,
              column12: column12,
            };

            data.push(rowData);
          });

                //console.log("ข้อมูลที่ถูกเก็บ:", data);
            
              if (confirm("คุณต้องการตั้งลูกหนี้ยกไปหรือไม่?")) {
                  $.ajax({
                      url: 'ทะเบียนคุมลูกหนี้1.php',
                      method: 'POST',
                      data: { records: JSON.stringify(data) },
                      success: function (response) {
                    
                          alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                          $('#setting_list').modal('hide');
                          
                      },
                      error: function () {
                         alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                      }
                  });
              } else {
                  alert("การบันทึกข้อมูลถูกยกเลิก");
              }

          });




            $('#print').click(function () {
                // สถานะเพื่อตรวจสอบว่ามีข้อผิดพลาดหรือไม่
                let hasError = false;

                // ลูปตรวจสอบ input text ที่มีคลาส puttxt
                $(".puttxt").each(function () {
                    // ดึงค่าของ input text
                    var value = $(this).val().trim();

                    // ตรวจสอบว่าค่าว่างหรือ "ระบุ..."
                    if (value === "" || value === "ระบุ...") {
                        // เพิ่มคลาสแสดงข้อผิดพลาด
                        $(this).addClass("error");

                        // ตั้งสถานะว่ามีข้อผิดพลาด
                        hasError = true;
                    } else {
                        // ลบคลาสข้อผิดพลาด หากกรอกข้อมูลถูกต้อง
                        $(this).removeClass("error");
                    }
                });

                // หากมีข้อผิดพลาด ให้แสดงข้อความแจ้งเตือน
                if (hasError) {
                    alert("กรุณากรอกข้อมูลให้ครบถ้วน ตำแหน่ง, ผู้รับผิดชอบ");
                } else {
                    // แสดง Modal และเรียกฟังก์ชัน หากไม่มีข้อผิดพลาด
                    $("#summary-report").modal('show');
                    fetch_data_pdf();
                }
            });

          
            $(document).on('input', '.puttxt', function () {
                $(this).removeClass("error");
            });

            // ตรวจจับการป้อนข้อมูล
            $(document).on('input', '.numeric', function () {
                let inputVal = $(this).text();
                
                if (!/^\d*\.?\d*$/.test(inputVal)) { 
                    $(this).text(inputVal.replace(/[^0-9.]/g, ''));
                }
            });

            // ป้องกันการวางข้อความ
            $(document).on('paste', '.numeric', function (e) {
                e.preventDefault();
                let pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');

                if (/^\d*\.?\d*$/.test(pastedData)) {
                    document.execCommand("insertText", false, pastedData);
                }
            });



            $(document).on('input', '.column6', function () {
                let $row = $(this).closest('tr'); // ค้นหาแถวปัจจุบัน

                // ดึงค่า Column5 และลบเครื่องหมายจุลภาคออก
                let column5Value = parseFloat($row.find('.column5').text().replace(/,/g, '')) || 0;

                console.log("ค่าที่ได้จาก Column5 (หลังแปลง):", column5Value); // ตรวจสอบค่าใน Console

                // ดึงค่า Column6
                let column6Value = parseFloat($(this).text().replace(/,/g, '')) || 0;

                // คำนวณ Column7
                let result = column5Value - column6Value;

                //console.log("ผลลัพธ์ Column5 - Column6:", result);

                // แสดงผลลัพธ์ใน Column7
                $row.find('.column7').text(result.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                
                let totalColumn6 = 0;

                // คำนวณผลรวม Column6
                $('.column6').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn6 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt6').text(totalColumn6.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#vartxt4').text($('#avartxt6').text());

                let totalColumn7 = 0;
                // คำนวณผลรวม Column7
                $('.column7').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn7 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt7').text(totalColumn7.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#vartxt5').text($('#avartxt7').text());

            });

            $(document).on('input', '.column8', function () {
                
                let totalColumn8 = 0;

                // คำนวณผลรวม Column6
                $('.column8').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn8 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt8').text(totalColumn8.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#vartxt6').text($('#avartxt8').text());
              
            });



            $(document).on('input', '.column9', function () {
                let $row = $(this).closest('tr'); // ค้นหาแถวปัจจุบัน

                // ดึงค่า Column3, Column6, และ Column10 และลบจุลภาคออก
                let column3Value = parseFloat($row.find('.column3').text().replace(/,/g, '')) || 0;
                let column6Value = parseFloat($row.find('.column6').text().replace(/,/g, '')) || 0;
                let column10Text = parseFloat($row.find('.column10').text().replace(/,/g, '')) || 0;


                // ตรวจสอบว่า column10 มีค่าหรือไม่
                if (column10Text === '' || isNaN(column10Text)) {
                    $row.find('.column12').text('รอยืนยันจากบัญชี');
                    return;
                }

                let column10Value = parseFloat(column10Text);

                // คำนวณ
                let result = (column3Value + column6Value) - column10Value;

                // แสดงผลลัพธ์ใน Column12 พร้อมจัดรูปแบบ
                $row.find('.column12').text(result.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                let totalColumn9 = 0;
                $('.column9').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn9 += value;
                });

                $('#avartxt9').text(totalColumn9.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#vartxt7').text($('#avartxt9').text());

                let totalColumn12 = 0;
                $('.column12').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn12 += value;
                });

                $('#avartxt12').text(totalColumn12.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#vartxt8').text($('#avartxt12').text());



                //console.log("ค่าที่ได้จาก Column9 (หลังแปลง):", column9Value); // ตรวจสอบค่าใน Console

                // ดึงค่า Column9
                let column9Value = parseFloat($(this).text().replace(/,/g, '')) || 0;

                // คำนวณ Column11
                let result1 = column9Value - column10Value;

                //console.log("ผลลัพธ์ Column9 - Column10:", result);

                // แสดงผลลัพธ์ใน Column11
                $row.find('.column11').text(result1.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                
                let totalColumn11 = 0;

                // คำนวณผลรวม Column11
                $('.column11').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn11 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt11').text(totalColumn11.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));




            });




            $(document).on('input', '.column10', function () {
                let $row = $(this).closest('tr'); // ค้นหาแถวปัจจุบัน

                // ดึงค่า Column9 และลบเครื่องหมายจุลภาคออก
                let column9Value = parseFloat($row.find('.column9').text().replace(/,/g, '')) || 0;

                //console.log("ค่าที่ได้จาก Column9 (หลังแปลง):", column9Value); // ตรวจสอบค่าใน Console

                // ดึงค่า Column10
                let column10Value = parseFloat($(this).text().replace(/,/g, '')) || 0;

                // คำนวณ Column11
                let result = column9Value - column10Value;

                //console.log("ผลลัพธ์ Column9 - Column10:", result);

                // แสดงผลลัพธ์ใน Column11
                $row.find('.column11').text(result.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                
                let totalColumn11 = 0;

                // คำนวณผลรวม Column11
                $('.column11').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn11 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt11').text(totalColumn11.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));


                // ดึงค่า Column3, Column6, และ Column10 และลบจุลภาคออก
                let column3Value = parseFloat($row.find('.column3').text().replace(/,/g, '')) || 0;
                let column6Value = parseFloat($row.find('.column6').text().replace(/,/g, '')) || 0;


                // คำนวณ
                let result1 = (column3Value + column6Value) - column10Value;

                // แสดงผลลัพธ์ใน Column12 พร้อมจัดรูปแบบ
                $row.find('.column12').text(result1.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            });


            $(document).on('input', '.column13', function () {
                
                let totalColumn13 = 0;

                // คำนวณผลรวม Column6
                $('.column13').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn13 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt13').text(totalColumn13.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
               
            });

            $(document).on('input', '.column14', function () {
                
                let totalColumn14 = 0;

                // คำนวณผลรวม Column6
                $('.column14').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn14 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt14').text(totalColumn14.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
               
            });

            $(document).on('input', '.column15', function () {
                
                let totalColumn15 = 0;

                // คำนวณผลรวม Column6
                $('.column13').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn15 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt15').text(totalColumn15.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
               
            });






          });



      $('#selectTypeOpt').change(function() {

        Swal.fire({
            title: 'กำลังประมวลผล...',
            text: 'กรุณารอสักครู่ ระบบกำลังดึงข้อมูล',
            allowOutsideClick: false, // สำคัญ! ห้ามคลิกนอกกรอบ
            allowEscapeKey: false,    // ห้ามกด ESC
            didOpen: () => {
                Swal.showLoading(); // สั่งให้หมุนติ้วๆ
            }
        });


        const selectedMonth = $(this).val(); // ค่าเดือนที่เลือก

      
        const convertedTexth = convertMonthYearToTexthead(selectedMonth);
        $('#vtxt0').text(convertedTexth);

        const convertedText = convertMonthYearToText(selectedMonth);
        $('#vtxt1').text(convertedText);
        $('#vtxt2').text(convertedText);
        $('#vtxt6').text(convertedText);

        const convertedTextlast = convertMonthYearToTextlast(selectedMonth);
        $('#vtxt4').text(convertedTextlast);

        const fiscalYearText = getFiscalYear(selectedMonth);
        $('#vtxt3').text(fiscalYearText);

        if (!selectedMonth) {
          console.error('No month selected');
          return;
        }

        let parts = selectedMonth.split('-'); // แยกส่วนเดือนและปี
        let year = parseInt(parts[1], 10); // ปี
        let monthValue = parseInt(parts[0], 10) - 1; // เดือน (ลบ 1)

        // จัดการกรณีเดือนเป็น 0 (มกราคม - 1 = ธันวาคมของปีก่อน)
        if (monthValue === 0) {
          monthValue = 12;
          year--;
        }

        let monthlast = monthValue + '-' + year;

        //console.log('Selected Month:', selectedMonth); // แสดงเดือนที่เลือก
        //console.log('Previous Month:', monthlast); // แสดงเดือนก่อนหน้า

        // ใช้ AJAX เพื่อดึงข้อมูลใหม่
        gprintPDF(selectedMonth);

        $.ajax({
            url: 'ทะเบียนคุมลูกหนี้1.php', // ไฟล์ PHP ที่จะดึงข้อมูล
            type: 'POST',
            data: { month: selectedMonth, monthlast: monthlast }, // ส่งเดือนที่เลือกไปยัง PHP
            success: function(response) {

              Swal.close();

                // อัปเดตเนื้อหาของตาราง
              $('#tt01').html(response);
              fetch_text();
              settingtxt();

              $('#vtxt5').text($('#vtxt4').text());
              $('#vtxt6').text($('#vtxt1').text());

              // เรียกประมวลผลการตรวจสอบยันยอดบัญชี
              loadReconAudit(selectedMonth);

            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                
                // ----------------------------------------------------
                // 3. เมื่อ Error แจ้งเตือนผู้ใช้ (สำคัญมาก ห้ามปล่อยให้ค้าง)
                // ----------------------------------------------------
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถดึงข้อมูลได้ กรุณาลองใหม่',
                    confirmButtonText: 'ตกลง'
                });
            }
          });
      });



      $('#selectTypeOptlist').change(function() {
        const selectedMonth = $(this).val(); // ค่าเดือนที่เลือก


        if (!selectedMonth) {
          console.error('No month selected');
          return;
        }

        let parts = selectedMonth.split('-'); // แยกส่วนเดือนและปี
        let year = parseInt(parts[1], 10); // ปี
        let monthValue = parseInt(parts[0], 10) - 1; // เดือน (ลบ 1)

        // จัดการกรณีเดือนเป็น 0 (มกราคม - 1 = ธันวาคมของปีก่อน)
        if (monthValue === 0) {
          monthValue = 12;
          year--;
        }

        let monthlast = monthValue + '-' + year;

        //console.log('Selected Month:', selectedMonth); // แสดงเดือนที่เลือก


        $.ajax({
            url: 'ตั้งลูกหนี้ยกไป.php', // ไฟล์ PHP ที่จะดึงข้อมูล
            type: 'POST',
            data: { month: selectedMonth, monthlast: monthlast }, // ส่งเดือนที่เลือกไปยัง PHP
            success: function(response) {
                // อัปเดตเนื้อหาของตาราง
              $('#ttlist').html(response);

              var avartxt12 = $('#avartxt122').text().replace(/,/g, ''); // ลบ comma
              var value = parseFloat(avartxt12); // หรือใช้ parseInt(avartxt12) ถ้าต้องการจำนวนเต็ม

              if (value > 0) {
                $('#savelist').hide();
                $('#textalertlist').show();
                $('#textalertlist').text('ไม่สามารถตั้งลูกหนี้ยกไปได้! มีลูกหนี้คงเหลือยกไปแล้ว');
              } else {
                $('#savelist').show();
                $('#textalertlist').text('');
                $('#textalertlist').hide();
              }


            },
            error: function(xhr, status, error) {
              console.error('Error:', error);
            }
          });
      });





      function gprintPDF(selectedMonth){
       
        const convertedTexth = convertMonthYearToTexthead(selectedMonth);
        $('#pvtxt0').text(convertedTexth);

        const convertedText = convertMonthYearToText(selectedMonth);
        $('#pvtxt1').text(convertedText);
        $('#pvtxt2').text(convertedText);
        $('#pvtxt6').text(convertedText);

        const convertedTextlast = convertMonthYearToTextlast(selectedMonth);
        $('#pvtxt4').text(convertedTextlast);
        $('#pvtxt5').text(convertedTextlast);

        const fiscalYearText = getFiscalYear(selectedMonth);
        $('#pvtxt3').text(fiscalYearText);

        if (!selectedMonth) {
          console.error('No month selected');
          return;
        }

        let parts = selectedMonth.split('-'); // แยกส่วนเดือนและปี
        let year = parseInt(parts[1], 10); // ปี
        let monthValue = parseInt(parts[0], 10) - 1; // เดือน (ลบ 1)

        // จัดการกรณีเดือนเป็น 0 (มกราคม - 1 = ธันวาคมของปีก่อน)
        if (monthValue === 0) {
          monthValue = 12;
          year--;
        }

        let monthlast = monthValue + '-' + year;

        $.ajax({
            url: 'ทะเบียนคุมลูกหนี้1print.php', // ไฟล์ PHP ที่จะดึงข้อมูล
            type: 'POST',
            data: { month: selectedMonth, monthlast: monthlast }, // ส่งเดือนที่เลือกไปยัง PHP
            success: function(response) {
                // อัปเดตเนื้อหาของตาราง
              $('#tt011').html(response);
              fetch_text();
              settingtxt();


            },
            error: function(xhr, status, error) {
              console.error('Error:', error);
            }
          });
      }

      function convertMonthYearToTexthead(value) {
          // แยกส่วนเดือนและปี
          const parts = value.split('-');
          if (parts.length !== 2) return 'ข้อมูลไม่ถูกต้อง';

          const month = parseInt(parts[0], 10); // เดือน
          const year = parseInt(parts[1], 10); // ปี

          // ตรวจสอบความถูกต้องของเดือน
          if (month < 1 || month > 12) return 'เดือนผิดพลาด';

          // ชื่อเดือนภาษาไทย
          const monthNamesThai = [
              '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
              'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
          ];

          // แปลงปีและเลือกชื่อเดือน
          const thaiYear = year + 543;
          const thaiMonth = monthNamesThai[month];

          // คืนค่าผลลัพธ์
          return `${thaiMonth} ${thaiYear}`;
      }


      function convertMonthYearToText(value) {
          // แยกส่วนเดือนและปี
          const parts = value.split('-');
          if (parts.length !== 2) return 'ข้อมูลไม่ถูกต้อง';

          const month = parseInt(parts[0], 10); // เดือน
          const year = parseInt(parts[1], 10); // ปี

          // ตรวจสอบความถูกต้องของเดือน
          if (month < 1 || month > 12) return 'เดือนผิดพลาด';

          // ชื่อเดือนภาษาไทย
          const monthNamesThai = [
              '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
              'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
          ];

          const thaiMonth = monthNamesThai[month];

          // คืนค่าผลลัพธ์
          return `${thaiMonth}`;
      }

      function convertMonthYearToTextlast(value) {
          // แยกส่วนเดือนและปี

          const parts = value.split('-');
          if (parts.length !== 2) return 'ข้อมูลไม่ถูกต้อง';

          let  month = parseInt(parts[0], 10)-1; // เดือน 

          if (month === 0) {
              month = 12;
          }

          // ตรวจสอบความถูกต้องของเดือน
          if (month < 1 || month > 12) return 'เดือนผิดพลาด';

          // ชื่อเดือนภาษาไทย
          const monthNamesThai = [
              '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
              'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
          ];


          const thaiMonth = monthNamesThai[month];

          // คืนค่าผลลัพธ์
          return `${thaiMonth}`;
      }

      // ฟังก์ชันสำหรับตรวจสอบปีงบประมาณ
      function getFiscalYear(value) {
          // แยกเดือนและปี
          const parts = value.split('-');
          if (parts.length !== 2) return 'ข้อมูลไม่ถูกต้อง';

          const month = parseInt(parts[0], 10); // เดือน
          const year = parseInt(parts[1], 10); // ปี

          // คำนวณปีงบประมาณ
          const fiscalYear = (month >= 10) ? year + 1 : year;

          // คืนค่าปีงบประมาณ
          return `ปีงบประมาณ ${fiscalYear + 543}`;
      }

      function fetch_text() { 

        $('#vartxt1').text($('#avartxt1').text());
        $('#vartxt2').text($('#avartxt2').text());
        $('#vartxt3').text($('#avartxt3').text());
        $('#vartxt4').text($('#avartxt6').text());
        $('#vartxt5').text($('#avartxt7').text());
        $('#vartxt6').text($('#avartxt9').text());
        $('#vartxt7').text($('#avartxt8').text());
        $('#vartxt8').text($('#avartxt12').text());

        $('#pvartxt1').text($('#pavartxt1').text());
        $('#pvartxt2').text($('#pavartxt2').text());
        $('#pvartxt3').text($('#pavartxt3').text());
        $('#pvartxt4').text($('#pavartxt6').text());
        $('#pvartxt5').text($('#pavartxt7').text());
        $('#pvartxt6').text($('#pavartxt9').text());
        $('#pvartxt7').text($('#pavartxt8').text());
        $('#pvartxt8').text($('#pavartxt12').text());

        let totalColumn13 = 0;
        let totalColumn14 = 0;
        let totalColumn15 = 0;

        // คำนวณผลรวมของ .column13
        $('.column13').each(function() {
          totalColumn13 += parseFloat($(this).text()) || 0; // แปลงข้อความเป็นตัวเลขและบวก
        });
        $('#avartxt13').text(totalColumn13.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        // คำนวณผลรวมของ .column14
        $('.column14').each(function() {
          totalColumn14 += parseFloat($(this).text()) || 0;
        });
        $('#avartxt14').text(totalColumn14.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        // คำนวณผลรวมของ .column15
        $('.column15').each(function() {
          totalColumn15 += parseFloat($(this).text()) || 0;
        });
        $('#avartxt15').text(totalColumn15.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));



      }


        function fetch_data_pdf()
        { 
          var data = $("#selectTypeOpt").val();  
         // var data = '2-2024' ;
          //alert(data);
          $("#pdfview").attr("src", "../report/pdf/summary-report.php?datepost="+data);
        }



        function select_save()
        {
          let data = [];

          $('tr[data-code]').each(function () {
            let row = $(this); // อ้างถึงแถวปัจจุบัน
            let code = row.data('code'); // รหัสผังบัญชี
            let month = row.data('month'); // วันที่ปัจจุบัน
            let column6 = row.find('td:nth-child(6)').text();
            let column7 = row.find('td:nth-child(7)').text();
            let column8 = row.find('td:nth-child(8)').text();
            let column9 = row.find('td:nth-child(9)').text();
            let column10 = row.find('td:nth-child(10)').text();
            let column11 = row.find('td:nth-child(11)').text();
            let column12 = row.find('td:nth-child(12)').text();
            let column13 = row.find('td:nth-child(13)').text();
            let column14 = row.find('td:nth-child(14)').text();
            let column15 = row.find('td:nth-child(15)').text();
            let column16 = row.find('td:nth-child(16)').text();

            let rowData = {
              code: code,
              month: month,
              column6: column6,
              column7: column7,
              column8: column8,
              column9: column9,
              column10: column10,
              column11: column11,
              column12: column12,
              column13: column13,
              column14: column14,
              column15: column15,
              column16: column16,
            };

            data.push(rowData);
          });

          $.ajax({
            url: 'ทะเบียนคุมลูกหนี้1.php',
            method: 'POST',
            data: { records: JSON.stringify(data) },
            success: function (response) {
              //alert('บันทึกข้อมูลเรียบร้อยแล้ว');
              //console.log("การตอบกลับจาก PHP:", response);
            },
            error: function () {
              //console.log("เกิดข้อผิดพลาดในการบันทึกข้อมูล:", response);
            }
          });

        }







      document.addEventListener("mousedown", function(e) {
          const cell = e.target.closest(".locked-cell");

          if (cell) {
              e.preventDefault(); // ห้ามคลิก/ห้าม focus
              e.stopPropagation(); // ห้าม event ทะลุไปตัวอื่น (ปลอดภัยขึ้น)
              
              Swal.fire({
                  icon: 'warning',
                  title: 'ไม่สามารถแก้ไขได้',
                  text: 'ระบบได้ปิดการแก้ไขข้อมูลเดือนนี้แล้ว',
                  confirmButtonText: 'ตกลง',
              });
          }
      });



        function formatNumber(value) {
            if (!value) return '';
            value = value.replace(/,/g, '');
            return Number(value).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function unformatNumber(value) {
            return value.replace(/,/g, '');
        }

        // 👉 ตอนออกจากช่อง ค่อย format
        $(document).on('blur', '[contenteditable=true]', function () {
            let value = $(this).text().trim();
            let clean = unformatNumber(value);

            if (clean !== '' && !isNaN(clean)) {
                $(this).text(formatNumber(clean));
            } else {
                $(this).text(''); // หรือจะใส่ 0.00 ก็ได้แล้วแต่ระบบ
            }
        });


        function viewUpdates(accountcode, month) {
            $('#updated-debtors-tbody').html('<tr><td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td></tr>');
            
            // Set the export URL
            $('#btn-export-excel').attr('href', 'export_updated_debtors_excel.php?accountcode=' + encodeURIComponent(accountcode) + '&month=' + encodeURIComponent(month));
            
            $('#updated-debtors-modal').modal('show');
            $.ajax({
                url: 'get_updated_debtors.php',
                type: 'POST',
                data: { accountcode: accountcode, month: month },
                success: function(response) {
                    $('#updated-debtors-tbody').html(response);
                },
                error: function() {
                    $('#updated-debtors-tbody').html('<tr><td colspan="5" class="text-center text-danger">เกิดข้อผิดพลาดในการดึงข้อมูล</td></tr>');
                }
            });
        }

        // =================================================================
        // 🛡️ RECONCILIATION AUDIT SYSTEM (ระบบตรวจสอบยันยอดบัญชี)
        // =================================================================
        window.currentReconData = null;
        let currentAuditFilter = 'ALL';

        function loadReconAudit(month) {
            if (!month || month === '0-0000') return;

            // แสดงผลสถานะเมื่อกำลังประมวลผลข้อมูล (Loading Feedback)
            $('#toolbarAuditLoadingBar').show();
            $('#toolbarAuditProcessingBadge').show();
            $('#toolbarAuditClosingBadge').hide();
            $('#reconAuditAlertBox').removeClass('d-flex').addClass('d-none').hide();
            $('#toolbarAuditMonthBadge').html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + 'เดือน ' + month);
            $('#reconAuditMonthBadge').html('<i class="bx bx-calendar me-1"></i>' + 'เดือน ' + month);

            // แสดงสถานะกำลังคำนวณในตัวเลขสถิติบน Toolbar
            const spinnerSmall = '<span class="spinner-border spinner-border-sm text-secondary" style="width: 12px; height: 12px;" role="status"></span>';
            $('#toolbarAuditTotal, #toolbarAuditMatched, #toolbarAuditMismatched, #toolbarAuditPending').html(spinnerSmall);
            $('#toolbarAuditVariance').html(spinnerSmall);
            $('#toolbarClosingDateText').html('<span class="text-muted"><i class="bx bx-loader-alt bx-spin me-1"></i>กำลังคำนวณ...</span>');

            $.ajax({
                url: 'api_recon_audit.php',
                type: 'GET',
                data: { action: 'get_month_audit_summary', month: month },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        window.currentReconData = res;
                        updateReconToolbar(res.summary);
                        // แสดงสีของรายการ มียอดต่าง ส่วนรายการอื่น แสดงสีดำ เอาตัวหนาออก
                        applyTableReconMismatchStyling(res.accounts);
                        renderAuditModalTable(res.accounts, currentAuditFilter);
                    } else {
                        console.warn('Recon Audit Warning:', res.message);
                        $('#toolbarAuditProcessingBadge')
                            .removeClass('bg-label-warning')
                            .addClass('bg-label-danger')
                            .html('<i class="bx bx-error me-1"></i>เกิดข้อผิดพลาดในการคำนวณ')
                            .show();
                    }
                },
                error: function(err) {
                    console.error('Recon Audit Network Error:', err);
                    $('#toolbarAuditProcessingBadge')
                        .removeClass('bg-label-warning')
                        .addClass('bg-label-danger')
                        .html('<i class="bx bx-error me-1"></i>การเชื่อมต่อล้มเหลว')
                        .show();
                },
                complete: function() {
                    // ปิดสถานะประมวลผลเมื่อดำเนินการเสร็จสิ้น
                    $('#toolbarAuditLoadingBar').hide();
                    $('#toolbarAuditProcessingBadge').hide();
                    $('#toolbarAuditMonthBadge').text('เดือน ' + month);
                }
            });
        }

        function applyTableReconMismatchStyling(accounts) {
            if (!accounts || !accounts.length) return;

            // 1. คืนค่าแถวและชื่อผังบัญชีทั้งหมดในตารางหลักให้เป็นสีดำปกติ ตัวหนังสือไม่หนา
            $('#tbbleone tbody tr').each(function() {
                const $tr = $(this);
                $tr.removeClass('table-recon-mismatch-row');
                const $tdName = $tr.find('td.recon-accname-cell');
                if ($tdName.length) {
                    $tdName.css({
                        'color': '#1e293b',
                        'font-weight': 'normal'
                    });
                    $tdName.find('.recon-mismatch-pill').remove();
                }
            });

            // 2. ไฮไลต์สีแดงและป้ายกำกับเฉพาะรายการที่ 'มียอดต่าง' (MISMATCH)
            accounts.forEach(function(item) {
                if (item.status === 'MISMATCH') {
                    const $tr = $('#tbbleone tbody tr[data-code="' + item.accountcode + '"]');
                    if ($tr.length) {
                        $tr.addClass('table-recon-mismatch-row');
                        const $tdName = $tr.find('td.recon-accname-cell');
                        if ($tdName.length) {
                            $tdName.css({
                                'color': '#dc2626',
                                'font-weight': '600'
                            });
                            if ($tdName.find('.recon-mismatch-pill').length === 0) {
                                $tdName.append('<span class="badge bg-label-danger py-0 px-2 ms-1 recon-mismatch-pill" style="font-size: 11px; font-weight: 600;"><i class="bx bx-error-circle me-1"></i>มียอดต่าง ฿' + item.diff_fmt + '</span>');
                            }
                        }
                    }
                }
            });
        }

        function updateReconToolbar(summary) {
            if (!summary) return;
            $('#toolbarAuditTotal').text(summary.total_accounts);
            $('#toolbarAuditMatched').text(summary.matched_count);
            $('#toolbarAuditMismatched').text(summary.mismatched_count);
            $('#toolbarAuditPending').text(summary.pending_count);
            $('#toolbarAuditVariance').text('฿' + Number(summary.total_variance_abs).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            // กำหนดวันที่ปิดงบการเงิน
            if (summary.closing_info) {
                $('#toolbarAuditClosingBadge')
                    .removeClass('bg-label-secondary bg-label-success')
                    .addClass(summary.closing_info.badge_class)
                    .html("<i class='bx " + summary.closing_info.badge_icon + " me-1'></i>" + summary.closing_info.status_text)
                    .show();
                $('#toolbarClosingDateText').text(summary.closing_info.cutoff_date_short + ' (' + summary.closing_info.status_text + ')');

                $('#reconAuditClosingBadge')
                    .removeClass('bg-label-secondary bg-label-success')
                    .addClass(summary.closing_info.badge_class)
                    .html("<i class='bx " + summary.closing_info.badge_icon + " me-1'></i>กำหนดปิดงบ: " + summary.closing_info.cutoff_date_thai + " (" + summary.closing_info.status_text + ")")
                    .show();
            }

            // Modal KPIs
            $('#kpiAuditTotal').text(summary.total_accounts);
            $('#kpiAuditMatched').text(summary.matched_count);
            $('#kpiAuditMismatched').text(summary.mismatched_count);
            $('#kpiAuditPending').text(summary.pending_count);

            $('#tabCntAll').text(summary.total_accounts);
            $('#tabCntMatch').text(summary.matched_count);
            $('#tabCntMismatch').text(summary.mismatched_count);
            $('#tabCntPending').text(summary.pending_count);

            if (Number(summary.mismatched_count) > 0) {
                $('#alertMismatchCount').text(summary.mismatched_count);
                $('#alertTotalVariance').text('฿' + Number(summary.total_variance_abs).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#reconAuditAlertBox').removeClass('d-none').addClass('d-flex').show();
            } else {
                $('#alertMismatchCount').text('0');
                $('#alertTotalVariance').text('฿0.00');
                $('#reconAuditAlertBox').removeClass('d-flex').addClass('d-none').hide();
            }
        }

        function renderInTableBadges(accounts, month) {
            // เอาป้าย tag ออกจากแต่ละรายการในตารางหลัก ให้ตารางคงความสะอาด เรียบร้อย
            // ตรวจสอบยันยอดและสถานะอย่างละเอียดผ่าน Audit Center Modal
            $('.recon-status-slot').empty();
        }

        function openReconAuditModal() {
            const currentMonth = $('#selectTypeOpt').val();
            if (!currentMonth || currentMonth === '0-0000') {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกเดือนที่ต้องการตรวจสอบก่อน', 'warning');
                return;
            }

            // ล้าง backdrop ตกค้างเดิม (ถ้ามี) เพื่อความสะอาดและป้องกันปัญหาฉากหลังซ้อนทับ
            $('.modal-backdrop').remove();

            if (!window.currentReconData || window.currentReconData.month !== currentMonth) {
                loadReconAudit(currentMonth);
            } else {
                renderAuditModalTable(window.currentReconData.accounts, currentAuditFilter);
            }
            $('#modalReconAudit').modal('show');
        }

        function filterAuditTable(status, elem) {
            currentAuditFilter = status;
            if (elem) {
                $('#reconAuditTabs .nav-link').removeClass('active');
                $(elem).addClass('active');
            }
            if (window.currentReconData && window.currentReconData.accounts) {
                renderAuditModalTable(window.currentReconData.accounts, currentAuditFilter);
            }
        }

        function searchAuditTable() {
            const term = $('#reconAuditSearchInput').val().toLowerCase().trim();
            if (!window.currentReconData || !window.currentReconData.accounts) return;

            const filtered = window.currentReconData.accounts.filter(function(item) {
                const matchFilter = (currentAuditFilter === 'ALL') || (item.status === currentAuditFilter);
                const matchTerm = (!term) || (item.accountcode.toLowerCase().includes(term) || item.accountname.toLowerCase().includes(term));
                return matchFilter && matchTerm;
            });

            renderAuditModalRows(filtered);
        }

        function renderAuditModalTable(accounts, filter) {
            if (!accounts) return;
            const term = $('#reconAuditSearchInput').val().toLowerCase().trim();
            const filtered = accounts.filter(function(item) {
                const matchFilter = (filter === 'ALL') || (item.status === filter);
                const matchTerm = (!term) || (item.accountcode.toLowerCase().includes(term) || item.accountname.toLowerCase().includes(term));
                return matchFilter && matchTerm;
            });
            renderAuditModalRows(filtered);
        }

        function renderAuditModalRows(accounts) {
            const tbody = $('#tbodyReconAudit');
            tbody.empty();

            if (!accounts || accounts.length === 0) {
                tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูลผังบัญชีตามเงื่อนไขที่เลือก</td></tr>');
                return;
            }

            const currentMonth = $('#selectTypeOpt').val();

            accounts.forEach(function(item) {
                let badgeHtml = '';
                let rowBg = '';
                let subtext = `<small class="text-muted">ส่งเบิกเดือนนี้: ฿${item.total_debit_fmt}</small>`;

                if (item.status === 'MATCH') {
                    if (item.is_carried_static) {
                        badgeHtml = '<span class="badge bg-label-info"><i class="bx bx-history me-1"></i>' + (item.status_label || 'ยอดยกมาคงเดิม (ตรงกัน 100%)') + '</span>';
                        subtext = '<small class="text-info"><i class="bx bx-history me-1"></i>ยอดยกมาจากเดือนก่อนหน้า ไม่มียอดเคลื่อนไหวเดือนนี้</small>';
                    } else {
                        badgeHtml = '<span class="badge bg-label-success"><i class="bx bx-check-circle me-1"></i>ตรงกัน 100%</span>';
                    }
                } else if (item.status === 'MISMATCH') {
                    badgeHtml = '<span class="badge bg-label-danger"><i class="bx bx-error-circle me-1"></i>' + (item.status_label || ('ต่าง ฿' + item.diff_fmt)) + '</span>';
                    rowBg = 'style="background-color: #fff5f5;"';
                } else if (item.status === 'PENDING') {
                    badgeHtml = '<span class="badge bg-label-warning"><i class="bx bx-time me-1"></i>' + (item.status_label || item.pending_reason || 'รอดำเนินการ') + '</span>';
                } else {
                    badgeHtml = '<span class="badge bg-label-secondary">ไม่มีความเคลื่อนไหว</span>';
                }

                let tr = `
                <tr ${rowBg}>
                    <td class="text-center font-monospace fw-bold">${item.accountcode}</td>
                    <td class="text-start">
                        <div class="fw-semibold text-dark">${item.accountname}</div>
                        ${subtext}
                    </td>
                    <td class="text-end fw-semibold">${item.col12_fmt}</td>
                    <td class="text-end fw-semibold text-primary">
                        <a href="debtor_excel.php?acc=${item.accountcode}&month=${currentMonth}" target="_blank" title="คลิกเพื่อส่งออก Excel">
                            ฿${item.excel_total_fmt}
                        </a>
                        <div class="small text-muted">(${item.excel_count} ราย)</div>
                    </td>
                    <td class="text-end fw-bold ${item.diff !== 0 ? 'text-danger' : 'text-success'}">
                        ${item.diff !== 0 ? '฿' + item.diff_fmt : '฿0.00'}
                    </td>
                    <td class="text-center">${badgeHtml}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill" onclick="openReconDetail('${item.accountcode}', '${currentMonth}')" title="ตรวจหาสาเหตุ 3 มิติ">
                            <i class='bx bx-search-alt-2 me-1'></i>ตรวจ
                        </button>
                    </td>
                </tr>
                `;
                tbody.append(tr);
            });
        }

        function openReconDetail(accountcode, month) {
            Swal.fire({
                title: 'กำลังวินิจฉัย...',
                text: 'กำลังประมวลผลวิเคราะห์ส่วนต่าง 3 มิติ...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: 'api_recon_audit.php',
                type: 'GET',
                data: { action: 'get_account_audit_detail', accountcode: accountcode, month: month },
                dataType: 'json',
                success: function(res) {
                    Swal.close();
                    if (res.status === 'success') {
                        $('#detailModalTitle').text(res.accountcode + ' - ' + res.accountname);
                        $('#detailModalSubtitle').text('วินิจฉัยข้อมูลประจำเดือน ' + res.month);
                        
                        $('#statDetailCol12').text('฿' + res.col12_fmt);
                        $('#statDetailExcel').text('฿' + res.excel_total_fmt);
                        $('#statDetailCases').text('(' + res.excel_count + ' ราย)');
                        
                        let diffElem = $('#statDetailDiff');
                        let diffCompElem = $('#statDetailDiffComp');
                        diffElem.text('฿' + res.overall_diff_fmt);
                        if (res.overall_dir === 'GREATER') {
                            diffElem.removeClass('text-success').addClass('text-danger');
                            diffCompElem.html('<span class="badge bg-label-danger py-1 px-2" style="font-size: 11px;"><i class="bx bx-up-arrow-alt"></i> ลูกหนี้รายตัว มากกว่า</span>');
                        } else if (res.overall_dir === 'LESS') {
                            diffElem.removeClass('text-success').addClass('text-danger');
                            diffCompElem.html('<span class="badge bg-label-danger py-1 px-2" style="font-size: 11px;"><i class="bx bx-down-arrow-alt"></i> ลูกหนี้รายตัว น้อยกว่า</span>');
                        } else {
                            diffElem.removeClass('text-danger').addClass('text-success');
                            diffCompElem.html('<span class="badge bg-label-success py-1 px-2" style="font-size: 11px;"><i class="bx bx-check"></i> ตรงกัน</span>');
                        }

                        // Dimension 1
                        const d1 = res.dimensions.dim1_setup;
                        $('#dim1Col5').text('฿' + Number(d1.col5_insurance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#dim1Col6').text('฿' + Number(d1.col6_accounting).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        if (d1.status === 'MATCH') {
                            $('#dim1Diff').html('<span class="text-success">฿0.00</span>');
                            $('#badgeDim1').attr('class', 'badge bg-label-success').text('ตรงกัน');
                        } else if (d1.status === 'PENDING') {
                            $('#dim1Diff').html('<span class="text-warning">฿' + d1.diff_fmt + '</span>');
                            $('#badgeDim1').attr('class', 'badge bg-label-warning').text('รอดำเนินการ');
                        } else {
                            $('#dim1Diff').html('<span class="text-danger">฿' + d1.diff_fmt + ' <span class="badge bg-label-danger ms-1" style="font-size: 11px; font-weight: 500;">' + (d1.comparison || '') + '</span></span>');
                            $('#badgeDim1').attr('class', 'badge bg-label-danger').text('มียอดต่าง');
                        }
                        $('#descDim1').text(d1.description);

                        // Dimension 2
                        const d2 = res.dimensions.dim2_settlement;
                        $('#dim2Col10').text('฿' + Number(d2.col10_accounting).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#dim2Receipts').text('฿' + Number(d2.db_receipts).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        if (d2.status === 'MATCH') {
                            $('#dim2Diff').html('<span class="text-success">฿0.00</span>');
                            $('#badgeDim2').attr('class', 'badge bg-label-success').text('ตรงกัน');
                        } else if (d2.status === 'PENDING') {
                            $('#dim2Diff').html('<span class="text-warning">฿' + d2.diff_fmt + '</span>');
                            $('#badgeDim2').attr('class', 'badge bg-label-warning').text('รอดำเนินการ');
                        } else {
                            $('#dim2Diff').html('<span class="text-danger">฿' + d2.diff_fmt + ' <span class="badge bg-label-danger ms-1" style="font-size: 11px; font-weight: 500;">' + (d2.comparison || '') + '</span></span>');
                            $('#badgeDim2').attr('class', 'badge bg-label-danger').text('มียอดต่าง');
                        }
                        $('#descDim2').text(d2.description);

                        // Dimension 3
                        const d3 = res.dimensions.dim3_historical;
                        $('#dim3Col3').text('฿' + Number(d3.col3_beginning).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#dim3LastExcel').text('฿' + Number(d3.excel_last_total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        if (d3.status === 'MATCH') {
                            $('#dim3Diff').html('<span class="text-success">฿0.00</span>');
                            $('#badgeDim3').attr('class', 'badge bg-label-success').text('ตรงกัน');
                        } else {
                            $('#dim3Diff').html('<span class="text-warning">฿' + d3.diff_fmt + ' <span class="badge bg-label-warning ms-1" style="font-size: 11px; font-weight: 500;">' + (d3.comparison || '') + '</span></span>');
                            $('#badgeDim3').attr('class', 'badge bg-label-warning').text('มียอดต่างสะสม');
                        }
                        $('#descDim3').text(d3.description);

                        // Action Guides
                        const insList = $('#guideInsuranceList');
                        insList.empty();
                        res.action_guide.insurance.forEach(function(txt) {
                            insList.append('<li>' + txt + '</li>');
                        });

                        const accList = $('#guideAccountingList');
                        accList.empty();
                        res.action_guide.accounting.forEach(function(txt) {
                            accList.append('<li>' + txt + '</li>');
                        });

                        $('#btnExportDetailExcel').attr('href', 'debtor_excel.php?acc=' + encodeURIComponent(accountcode) + '&month=' + encodeURIComponent(month));

                        setTimeout(function() {
                            $('#modalReconAuditDetail').modal('show');
                        }, 50);
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message || 'ไม่สามารถดึงข้อมูลวินิจฉัยได้', 'error');
                    }
                },
                error: function(err) {
                    Swal.close();
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: ' + err.statusText, 'error');
                }
            });
        }

        // การจัดการลำดับการแสดงผลของ Modal ซ้อน (Modal Layering & Lifecycle Management)
        // Sub-modal (#modalReconAuditDetail) ใช้ data-bs-backdrop="false" และมี overlay มืดในตัว
        // จึงไม่มีการสร้าง .modal-backdrop ซ้อนใน body ป้องกันปัญหา backdrop แย่ง z-index ได้ 100%
        $(document).on('click', '#modalReconAuditDetail', function (e) {
            // เมื่อคลิกที่พื้นหลังมืดภายนอก dialog ให้ปิด Sub-modal กลับสู่ Modal หลัก
            if ($(e.target).is('#modalReconAuditDetail')) {
                $('#modalReconAuditDetail').modal('hide');
            }
        });

        $(document).on('hidden.bs.modal', '#modalReconAuditDetail', function () {
            // เมื่อปิด Sub-modal ให้คงคลาส modal-open บน body เพื่อให้เลื่อนดู Modal หลักได้ต่อเนื่อง
            if ($('#modalReconAudit').hasClass('show')) {
                $('body').addClass('modal-open');
                $('#modalReconAudit').focus();
            }
        });

        $(document).on('hidden.bs.modal', '#modalReconAudit', function () {
            // เมื่อปิดหน้าต่างศูนย์ตรวจยันยอดหลัก ทำความสะอาด backdrop และสถานะ body ทั้งหมดอย่างหมดจด
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        });

        function refreshReconAuditData() {
            const currentMonth = $('#selectTypeOpt').val();
            loadReconAudit(currentMonth);
        }

      </script>

  <?php if (!empty($_SESSION['role'])): ?>
  <!-- ✅ Quick Search (Ctrl+K) -->
  <?php include './includes/quick_search_ui.html'; ?>
  <?php endif; ?>

  </body>
</html>
