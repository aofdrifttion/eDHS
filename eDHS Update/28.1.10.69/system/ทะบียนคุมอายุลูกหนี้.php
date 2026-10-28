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
    // แยกค่าเดือนและปีจากรูปแบบ 'เดือน-ปี'
    $parts = explode('-', $monthYear);

    if (count($parts) !== 2) {
        return [
            'fullDate' => 'ไม่สามารถแสดงเดือน',
            'fiscalYear' => ''
        ];
    }

    $month = intval(ltrim($parts[0], '0')); // เอา 0 หน้าออก และแปลงเป็น int
    $year = intval($parts[1]);

    // ตรวจสอบว่าค่าอยู่ในช่วงที่ถูกต้อง
    if ($month < 1 || $month > 12) {
        return [
            'fullDate' => 'เดือนผิดพลาด',
            'fiscalYear' => ''
        ];
    }

    $strYear = $year + 543;

    $strMonthCut = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", 
                              "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
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

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../assets/js/config.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>

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
      .swal2-popup, .swal2-popup * {
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



      .numericcolor {cursor: no-drop;}
      .numeric {cursor: no-drop;}
      
      .error {
        background-color: #ffaaaa;
        border: 1px solid #fff;
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

/* =========================================================================
   🌟 Modern Aging Debtor Registry UI (ทะเบียนคุมอายุลูกหนี้)
   ========================================================================= */
.aging-filter-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #0d9488 !important;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px 28px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}
.aging-filter-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.aging-table-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px 28px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}

.aging-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 16px;
  margin-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
}

.aging-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.aging-header-icon-filter {
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

.aging-header-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.aging-header-subtitle {
  font-size: 0.82rem;
  color: #64748b;
  margin: 2px 0 0 0;
}

.aging-pill-badge {
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

.aging-field-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.aging-select {
  border-radius: 10px !important;
  border: 1px solid #cbd5e1 !important;
  padding: 10px 14px !important;
  font-size: 13.5px !important;
  color: #1e293b !important;
  background-color: #ffffff !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.2s ease !important;
}

.aging-select:focus {
  border-color: #0d9488 !important;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.16) !important;
  background-color: #ffffff !important;
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
                <div class="col-12 col-lg-12 order-2 order-md-3 order-lg-2 mb-4">

                  <!-- Card ตัวกรองสรุปลูกหนี้สิทธิประจำเดือน -->
                  <div class="aging-filter-card">
                    <div class="aging-card-header">
                      <div class="aging-header-left">
                        <div class="aging-header-icon-filter">
                          <i class='bx bx-time-five'></i>
                        </div>
                        <div>
                          <h5 class="aging-header-title">สรุปลูกหนี้สิทธิประจำเดือน</h5>
                          <p class="aging-header-subtitle">ทะเบียนคุมอายุลูกหนี้ (Aging Report) และจำแนกตามช่วงระยะเวลาค้างชำระ</p>
                        </div>
                      </div>
                      <div>
                        <span class="aging-pill-badge">
                          <i class='bx bx-calendar-event text-success'></i> ข้อมูลประจำเดือน
                        </span>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <label class="aging-field-label"><i class='bx bx-calendar text-success'></i> ข้อมูลประจำเดือน</label>
                        <select id="selectTypeOpt" name="selectTypeOpt" class="form-select aging-select">
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

                  <!-- Card แสดงตารางรายงานทะเบียนคุมอายุลูกหนี้ -->
                  <div class="aging-table-card">
                    <div style="padding: 10px 0;">
                      <div id="tt01"></div>
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




<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl"> <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailModalLabel">รายละเอียดลูกหนี้</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="loadingSpinner" class="text-center my-4" style="display:none;">
            <span>กำลังโหลดข้อมูล...</span>
        </div>
        <div id="detailContent"></div>
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

        function exportToPDF() {
            const originalTable = document.getElementById("tableToPDF");
            const clonedTable = originalTable.cloneNode(true);

            // ลบสีพื้นหลัง เปลี่ยนเป็นขาวดำ (โค้ดเดิมพี่)
            clonedTable.querySelectorAll('*').forEach(el => {
                el.style.color = '#000000';
                el.style.borderColor = '#000000';
            });

            const wrapper = document.createElement("div");
            wrapper.appendChild(clonedTable);
            // wrapper.style.padding = "10mm"; // แนะนำให้เอาออก แล้วไปตั้ง margin ใน opt แทน จะแม่นยำกว่าครับ
            wrapper.style.color = "#000";

            const opt = {
                margin: [10, 10, 10, 10], // บน, ซ้าย, ล่าง, ขวา (mm) ปรับให้พอดีขอบกระดาษ
                filename: 'ทะเบียนคุมลูกหนี้สิทธิ.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2, // ลด scale ลงหน่อยถ้าข้อมูลเยอะ เครื่องจะค้างได้ (2 กำลังดีครับ)
                    useCORS: true,
                    scrollY: 0 
                },
                jsPDF: { unit: 'mm', format: 'a3', orientation: 'landscape' },
                // *** เพิ่มส่วนนี้สำคัญมากครับพี่ ***
                pagebreak: { 
                    mode: ['avoid-all', 'css', 'legacy'],
                    before: '.page-break-before', // ถ้าอยากบังคับขึ้นหน้าใหม่ให้ใส่ class นี้
                    avoid: '.avoid-break'       // ห้ามตัดหน้าระหว่าง element ที่มี class นี้
                }
            };

            // ใช้ wait เล็กน้อยเพื่อให้ render ทัน
            html2pdf().set(opt).from(wrapper).toPdf().get('pdf').then(function (pdf) {
                // สามารถเพิ่มเลขหน้าตรงนี้ได้ถ้าต้องการ (Option เสริม)
            }).save();
        }



        // จัดการตอนคลิกที่ตัวเลข
        $(document).on('click', '.view-detail', function() {
            var accCode = $(this).data('acc');
            var accName = $(this).data('accname');
            var range = $(this).data('range');
            var month = $(this).data('month');

            // เปลี่ยนหัว Modal ให้รู้ว่ากำลังดูอะไรอยู่
            var rangeText = range === 'lt_3' ? '< 3 เดือน' : (range === '3_12' ? '> 3 เดือน < 12 เดือน' : (range === 'gt_12' ? '> 12 เดือน' : 'รวมทั้งหมด'));
            $('#detailModalLabel').text('รหัส: ' + accCode + ' (' + accName + ') | อายุหนี้: ' + rangeText);
            
            // เปิด Modal โชว์ loading
            $('#detailContent').html('');
            $('#loadingSpinner').show();
            $('#detailModal').modal('show');

            // ยิง AJAX ไปขอข้อมูลรายคน
            $.ajax({
                url: 'get_debtor_detail.php', // ไฟล์ที่เราจะสร้างในสเต็ป 2
                type: 'POST',
                data: {
                    accountcode: accCode,
                    month: month,
                    range: range
                },
                success: function(response) {
                    $('#loadingSpinner').hide();
                    $('#detailContent').html(response);
                },
                error: function() {
                    $('#loadingSpinner').hide();
                    $('#detailContent').html('<div class="alert alert-danger">เกิดข้อผิดพลาดในการดึงข้อมูลครับพี่ ลองใหม่อีกทีนะ</div>');
                }
            });
        });


          $(document).ready(function () {

            var yyy = '<?php echo $yyy; ?>';


            var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

            if (!isLoggedIn) {
              location.href = './login.php';
            }else{
             fetch_text();
   
             // เรียกให้ทำงานอัตโนมัติเมื่อโหลดหน้า
             $('#selectTypeOpt').trigger('change');

           }
           


            $('#save').click(function () {
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

              //console.log("ข้อมูลที่ถูกเก็บ:", data);

              if (confirm("คุณต้องการบันทึกข้อมูลหรือไม่?")) {
                  $.ajax({
                      url: 'ทะเบียนคุมลูกหนี้2.php',
                      method: 'POST',
                      data: { records: JSON.stringify(data) },
                      success: function (response) {
                          alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                          console.log("การตอบกลับจาก PHP:", response);
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

                // ดึงค่า Column3, Column5, และ Column9 และลบจุลภาคออก
                let column3Value = parseFloat($row.find('.column3').text().replace(/,/g, '')) || 0;
                let column5Value = parseFloat($row.find('.column5').text().replace(/,/g, '')) || 0;
                let column9Value = parseFloat($(this).text().replace(/,/g, '')) || 0;

                //console.log("Column3 Value:", column3Value);
                //console.log("Column5 Value:", column5Value);
                //console.log("Column9 Value:", column9Value);

                // คำนวณ (Column3 + Column5) - Column9
                let result = (column3Value + column5Value) - column9Value;

                //console.log("ผลลัพธ์ (Column3 + Column5) - Column9:", result);

                // แสดงผลลัพธ์ใน Column12 พร้อมจัดรูปแบบ
                $row.find('.column12').text(result.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                let totalColumn9 = 0;
                // คำนวณผลรวม Column6
                $('.column9').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn9 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt9').text(totalColumn9.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#vartxt7').text($('#avartxt9').text());

                let totalColumn12 = 0;
                // คำนวณผลรวม Column7
                $('.column12').each(function() {
                    let value = parseFloat($(this).text().replace(/,/g, '')) || 0;
                    totalColumn12 += value;
                });

                // แสดงผลรวมใน id="avartxt6"
                $('#avartxt12').text(totalColumn12.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#vartxt8').text($('#avartxt12').text());

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
                //$('#vartxt').text($('#avartxt11').text());

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
        if (monthValue === 0) {
          monthValue = 12;
          year--;
        }
        let monthlast = monthValue + '-' + year;
        let partss = selectedMonth.split('-'); // แยกเดือน-ปี
        let years = parseInt(partss[1], 10);
        let monthValues = parseInt(partss[0], 10) - 2; // ลบ 3 เดือน
        while (monthValues <= 0) {
          monthValues += 12;
          years--;
        }     
        let monthlasts = (monthValues < 10 ? '' : '') + monthValues + '-' + years;
        console.log('Selected Month:', selectedMonth); // แสดงเดือนที่เลือก
        console.log('Previous Month:', monthlasts); // แสดงเดือนก่อนหน้า

        $.ajax({
            url: 'ทะบียนคุมอายุลูกหนี้1.php', // ไฟล์ PHP ที่จะดึงข้อมูล
            type: 'POST',
            data: { month: selectedMonth, monthlast: monthlast , monthlasts: monthlasts}, // ส่งเดือนที่เลือกไปยัง PHP
            success: function(response) {

              Swal.close();

                // อัปเดตเนื้อหาของตาราง
              $('#tt01').html(response);
              fetch_text();
             

              $('#vtxt5').text($('#vtxt4').text());
              $('#vtxt6').text($('#vtxt1').text());

            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถดึงข้อมูลได้ กรุณาลองใหม่',
                    confirmButtonText: 'ตกลง'
                });
            }
          });
      });




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




    </script>

      <script>

     $('#saveSummaryBtn').on('click', function () {
        const data = {
          p1: $('#p1txt').val(),
          s1: $('#s1txt').val(),
          p2: $('#p2txt').val(),
          s2: $('#s2txt').val(),
          p3: $('#p3txt').val(),
          s3: $('#s3txt').val(),
          p4: $('#p4txt').val(),
          s4: $('#s4txt').val(),
          p5: $('#p5txt').val(),
          s5: $('#s5txt').val()
        };

        $.ajax({
          url: 'save_summary.php',
          method: 'POST',
          data: data,
          success: function (response) {
            alert(response); // หรือเปลี่ยนเป็น toast แจ้งเตือน
          },
          error: function () {
            alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
          }
        });
      });



      </script>
  <?php if (!empty($_SESSION['role'])): ?>
  <!-- ✅ Quick Search (Ctrl+K) -->
  <?php include './includes/quick_search_ui.html'; ?>
  <?php endif; ?>
  </body>
</html>
