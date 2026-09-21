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
$strDate = date("Y/m/d");
$yyy = date("Y")+543;
?>


<!DOCTYPE html>

<!-- =========================================================
* Sneat - Bootstrap 5 HTML Admin Template - Pro | v1.0.0
==============================================================

* Product Page: https://themeselection.com/products/sneat-bootstrap-html-admin-template/
* Created by: ThemeSelection
* License: You must have a valid license purchased in order to legally use the theme for your project.
* Copyright ThemeSelection (https://themeselection.com)

=========================================================
 -->
<!-- beautify ignore:start -->
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


    <title>eDebtor Hospital System (eDHS)</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

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
  .debt-title, .debt-subtitle, .debt-badge,
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

/* Filter Card */
.debt-filter-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    border-left: 5px solid #4f46e5 !important;
    box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
    padding: 24px 28px;
    margin-bottom: 24px;
}

.debt-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 20px;
}

.debt-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.debt-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #4338ca;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.18);
    flex-shrink: 0;
}

.debt-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 2px;
}

.debt-subtitle {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 0;
}

.debt-status-pill {
    background: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
    font-size: 12.5px;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Segmented Mode Switcher */
.debt-segmented-control {
    background: #f1f5f9;
    border-radius: 12px;
    padding: 4px;
    display: inline-flex;
    gap: 4px;
    border: 1px solid #e2e8f0;
}

.debt-segment-btn {
    border-radius: 8px;
    padding: 8px 20px;
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-check:checked + .debt-segment-btn {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: #ffffff !important;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
}

/* Signature Switch Container */
.debt-switch-container {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 8px 18px;
    display: inline-flex;
    align-items: center;
}

.debt-switch-input {
    width: 2.4em !important;
    height: 1.25em !important;
    cursor: pointer;
}

.debt-switch-input:checked {
    background-color: #4f46e5 !important;
    border-color: #4f46e5 !important;
}

.debt-switch-label {
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 0;
    margin-left: 8px;
    transition: color 0.2s ease;
}

/* Form Inputs */
.debt-form-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
}

.debt-select-input,
.debt-datepicker-input {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    height: 46px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    color: #1e293b !important;
    background-color: #f8fafc !important;
    padding: 8px 14px !important;
    transition: all 0.2s ease !important;
}

.debt-select-input:focus,
.debt-datepicker-input:focus {
    background-color: #ffffff !important;
    border-color: #4f46e5 !important;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
    outline: none !important;
}

.debt-dropdown-trigger {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    height: 46px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    color: #1e293b !important;
    background-color: #f8fafc !important;
    padding: 0 16px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    transition: all 0.2s ease !important;
    text-align: left !important;
}

.debt-dropdown-trigger:hover,
.debt-dropdown-trigger:focus,
.show > .debt-dropdown-trigger {
    background-color: #ffffff !important;
    border-color: #4f46e5 !important;
    color: #1e293b !important;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
}

.debt-dropdown-menu {
    border-radius: 14px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    padding: 14px !important;
}

.btn-search-debt {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    border: none;
    color: #ffffff;
    font-weight: 600;
    font-size: 14.5px;
    border-radius: 10px;
    height: 46px;
    padding: 0 28px;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-search-debt:hover {
    background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.38);
}

/* Report Card & Tab Navigation */
.debt-report-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    margin-bottom: 24px;
}

.debt-tab-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 20px 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.debt-nav-tabs {
    border-bottom: none !important;
    gap: 8px;
    display: flex;
    margin-bottom: -1px;
}

.debt-nav-tabs .nav-link {
    border: 1px solid transparent !important;
    border-bottom: none !important;
    border-radius: 12px 12px 0 0 !important;
    font-size: 14.5px !important;
    font-weight: 600 !important;
    color: #64748b !important;
    padding: 10px 22px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    background: transparent !important;
    transition: all 0.2s ease !important;
}

.debt-nav-tabs .nav-link:hover {
    color: #4f46e5 !important;
    background: rgba(79, 70, 229, 0.05) !important;
}

.debt-nav-tabs .nav-link.active {
    color: #4f46e5 !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-bottom-color: #ffffff !important;
    box-shadow: 0 -3px 12px rgba(15, 23, 42, 0.04) !important;
}

.debt-iframe-box {
    width: 100%;
    height: 850px;
    border: none;
    display: block;
    background: #525659;
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
    color: #fff;
}
</style>
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

           <nav class="container-fluid" id="layout-navbar" style="margin-bottom: 20px;">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>
          </nav>

          <!-- Filter & Report Container -->
          <div class="container-fluid">
            <!-- Modern Filter Card -->
            <div class="debt-filter-card">
              <!-- Header Bar -->
              <div class="debt-header-bar">
                <div class="debt-header-left">
                  <div class="debt-header-icon">
                    <i class="bx bx-receipt"></i>
                  </div>
                  <div>
                    <h5 class="debt-title">ตั้งลูกหนี้ค่ารักษาพยาบาล (Debtor Setup & Report)</h5>
                    <p class="debt-subtitle">พิมพ์รายงานลูกหนี้สิทธิแยก OPD / IPD ตามเดือนหรือช่วงวันที่ พร้อมตรวจสอบข้อมูล</p>
                  </div>
                </div>
                <div>
                  <span class="debt-status-pill">
                    <i class="bx bx-check-shield text-primary"></i> พร้อมออกรายงานลูกหนี้
                  </span>
                </div>
              </div>

              <!-- Mode Switcher & Signature Row -->
              <div class="row mb-3 align-items-center">
                <div class="col-md-6 mb-2 mb-md-0">
                  <div class="debt-segmented-control" role="group" aria-label="โหมดการออกรายงาน">
                    <input type="radio" class="btn-check" name="reportMode" id="modeMonth" value="month" checked autocomplete="off">
                    <label class="debt-segment-btn" for="modeMonth">
                      <i class="bx bx-calendar"></i> รายเดือน
                    </label>

                    <input type="radio" class="btn-check" name="reportMode" id="modeRange" value="range" autocomplete="off">
                    <label class="debt-segment-btn" for="modeRange">
                      <i class="bx bx-calendar-week"></i> ช่วงวันที่
                    </label>
                  </div>
                </div>

                <div class="col-md-6 text-md-end">
                  <div class="debt-switch-container">
                    <div class="form-check form-switch m-0 d-inline-flex align-items-center">
                      <input class="form-check-input debt-switch-input" type="checkbox" id="showSignature" checked onchange="toggleSignatureText(); triggerLoad();">
                      <label class="form-check-label debt-switch-label" for="showSignature" id="showSignatureLabel" style="color: #4f46e5;">
                        <i class="bx bx-edit-alt me-1"></i> แสดงรายชื่อผู้จัดทำ (แสดงทุกหน้า)
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Form Inputs (Row เดียวกัน: เลือกเดือน/ช่วงวันที่ + เลือกสิทธิลูกหนี้ + ปุ่มตกลง) -->
              <div class="row g-3 align-items-end">
                <!-- Month Selection -->
                <div class="col-lg-4 col-md-4 col-12" id="boxMonth">
                  <label class="debt-form-label" for="selectTypeOpt">
                    <i class="bx bx-calendar-event me-1 text-primary"></i> เลือกเดือน
                  </label>
                  <select id="selectTypeOpt" name="selectTypeOpt" class="form-select debt-select-input">
                    <?php 
                    $latestMonth = '';
                    $sqlLatest = "SELECT monthtxt FROM imr_tb_debtor_rights_opd GROUP BY monthtxt ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC LIMIT 1";
                    $resultLatest = mysqli_query($conn, $sqlLatest);
                    if ($rowLatest = mysqli_fetch_assoc($resultLatest)) { $latestMonth = $rowLatest['monthtxt']; }

                    $sqlzm = "SELECT monthtxt FROM imr_tb_debtor_rights_opd GROUP BY monthtxt ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC";
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

                <!-- Range Selection -->
                <div class="col-lg-4 col-md-4 col-12" id="boxRange" style="display:none;">
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="debt-form-label" for="startDate">
                        <i class="bx bx-calendar-check me-1 text-primary"></i> วันที่เริ่มต้น
                      </label>
                      <input type="text" id="startDate" class="form-control datepicker debt-datepicker-input" placeholder="วว/ดด/ปปปป" autocomplete="off">
                    </div>
                    <div class="col-6">
                      <label class="debt-form-label" for="endDate">
                        <i class="bx bx-calendar-x me-1 text-primary"></i> วันที่สิ้นสุด
                      </label>
                      <input type="text" id="endDate" class="form-control datepicker debt-datepicker-input" placeholder="วว/ดด/ปปปป" autocomplete="off">
                    </div>
                  </div>
                </div>

                <!-- Rights Selection -->
                <div class="col-lg-6 col-md-5 col-12">
                  <label class="debt-form-label">
                    <i class="bx bx-id-card me-1 text-primary"></i> เลือกสิทธิลูกหนี้
                  </label>
                  <div class="dropdown w-100">
                    <button class="btn w-100 debt-dropdown-trigger dropdown-toggle" type="button" id="dropdownRightsButton" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                      <span><i class="bx bx-list-check me-2 text-primary"></i> กำลังโหลดสิทธิ...</span>
                    </button>
                    <ul class="dropdown-menu w-100 debt-dropdown-menu" aria-labelledby="dropdownRightsButton" id="rightsDropdownList" style="max-height: 400px; overflow-y: auto;">
                      <!-- โหลดข้อมูลจาก AJAX -->
                    </ul>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="col-lg-2 col-md-3 col-12">
                  <button type="button" class="btn btn-search-debt w-100" onclick="triggerLoad()">
                    <i class="bx bx-search-alt"></i> ตกลง
                  </button>
                </div>
              </div>
            </div>
            <!-- / Modern Filter Card -->

            <!-- Modern Report Tabs Card -->
            <div class="debt-report-card">
              <div class="debt-tab-header">
                <ul class="nav debt-nav-tabs" role="tablist">
                  <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-opd" aria-controls="navs-top-opd" aria-selected="true">
                      <i class="bx bx-user"></i> ลูกหนี้ผู้ป่วยนอก (OPD)
                    </button>
                  </li>
                  <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-ipd" aria-controls="navs-top-ipd" aria-selected="false">
                      <i class="bx bx-bed"></i> ลูกหนี้ผู้ป่วยใน (IPD)
                    </button>
                  </li>
                </ul>
                <div class="d-flex align-items-center gap-2 pb-2 pb-md-0">
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="openCurrentPdf()">
                    <i class="bx bx-fullscreen me-1"></i> เปิดดูเต็มหน้า
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="triggerLoad()">
                    <i class="bx bx-refresh me-1"></i> โหลดใหม่
                  </button>
                </div>
              </div>

              <div class="tab-content p-0">
                <div class="tab-pane fade show active" id="navs-top-opd" role="tabpanel">
                  <iframe id="pdfview" class="debt-iframe-box" src=""></iframe>
                </div>
                <div class="tab-pane fade" id="navs-top-ipd" role="tabpanel">
                  <iframe id="pdfviewi" class="debt-iframe-box" src=""></iframe>
                </div>
              </div>
            </div>
            <!-- / Modern Report Tabs Card -->
          </div>

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



<!-- Modal -->
<div class="modal fade" id="exampleModalload" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="margin-top: 18%;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body" style="text-align:center;">
        Load...
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.th.min.js"></script>

    <script>
    $(document).ready(function(){
      $("#myInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#myTablev tr").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
      });
    });




    $(document).ready(function () {
        // ตั้งค่าปฏิทินเป็นภาษาไทย
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',  // เก็บค่าเป็น วว/ดด/ปปปป (ปี พ.ศ.)
            todayBtn: "linked",
            language: "th",        // เรียกใช้ภาษาไทย (จะแปลง ค.ศ. เป็น พ.ศ. ให้เอง)
            autoclose: true,       // เลือกเสร็จแล้วปิดปฏิทินเลย
            todayHighlight: true
        }).on('changeDate', function() {
            if($('input[name="reportMode"]:checked').val() == 'range'){
                loadRights();
            }
        });

        // กำหนดค่าเริ่มต้นให้เป็นวันปัจจุบัน (แบบ พ.ศ.)
        // สูตร: ปี ค.ศ. + 543
        var d = new Date();
        var day = d.getDate();
        var month = d.getMonth() + 1;
        var year = d.getFullYear() + 543; // แปลงเป็น พ.ศ. ตรงนี้

        // จัด format ให้มีเลข 0 นำหน้า ถ้าต่ำกว่า 10 (เช่น 01/05/2568)
        if (day < 10) day = "0" + day;
        if (month < 10) month = "0" + month;
        
        var todayThai = day + '/' + month + '/' + year;

        // ใส่ค่าลงในช่อง Input
        if ($('#startDate').val() == '') $('#startDate').val(todayThai);
        if ($('#endDate').val() == '') $('#endDate').val(todayThai);
    });



    $(document).ready(function(){
        // ตรวจสอบ Login
        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        if (!isLoggedIn) {
            location.href = './login.php';
        } else {
            toggleSignatureText(); // เซ็ตข้อความสวิตช์ตอนโหลดครั้งแรก
            loadRights(true); // โหลด rights ก่อนแล้วค่อยโหลด PDF
        }

        // สลับโหมด รายเดือน / ช่วงวันที่
        $('input[name="reportMode"]').change(function(){
            if(this.value == 'month'){
                $('#boxMonth').show();
                $('#boxRange').hide();
            } else {
                $('#boxMonth').hide();
                $('#boxRange').show();
            }
            loadRights();
        });

        // เมื่อเปลี่ยนเดือนให้โหลด rights ใหม่
        $('#selectTypeOpt').change(function() {
            if($('input[name="reportMode"]:checked').val() == 'month'){
                loadRights();
            }
        });
    });

    function loadRights(isInit = false) {
        var mode = $('input[name="reportMode"]:checked').val();
        var data = { mode: mode };

        if(mode == 'month'){
            var monthVal = $("#selectTypeOpt").val();
            if(monthVal == "0-0000" || monthVal == "") { return; }
            data.datepost = monthVal;
        } else {
            var start = $("#startDate").val();
            var end = $("#endDate").val();
            if(start == "" || end == "") { return; }
            data.start = start;
            data.end = end;
        }

        $('#dropdownRightsButton').html('<span><i class="bx bx-loader-alt bx-spin me-2 text-primary"></i> กำลังโหลดสิทธิ...</span>');

        $.ajax({
            url: 'ajax_get_rights.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                $('#rightsDropdownList').html(res.html);
                if (res.count > 0) {
                    $('#dropdownRightsButton').html('<span><i class="bx bx-list-check me-2 text-primary"></i> เลือกสิทธิ (เลือกทั้งหมด)</span>');
                } else {
                    $('#dropdownRightsButton').html('<span><i class="bx bx-error-circle me-2 text-warning"></i> ไม่มีสิทธิในเดือนนี้</span>');
                }
                bindRightsEvents();
                if (isInit) {
                    triggerLoad();
                }
            }
        });
    }

    function bindRightsEvents() {
        // ควบคุม checkbox เลือกสิทธิ
        $('#selectAllRights').off('change').on('change', function() {
            $('.right-checkbox').prop('checked', $(this).prop('checked'));
            updateRightsButtonText();
        });

        $('.right-checkbox').off('change').on('change', function() {
            var total = $('.right-checkbox').length;
            var checked = $('.right-checkbox:checked').length;
            $('#selectAllRights').prop('checked', total === checked);
            updateRightsButtonText();
        });
    }

    

    

        function openCurrentPdf() {
        var activeTab = $('.debt-nav-tabs .nav-link.active').attr('data-bs-target');
        var iframeId = (activeTab === '#navs-top-ipd') ? '#pdfviewi' : '#pdfview';
        var src = $(iframeId).attr('src');
        if (src && src !== '') {
            window.open(src, '_blank');
        } else {
            Swal.fire('แจ้งเตือน', 'ยังไม่มีไฟล์ PDF ที่เปิดอยู่', 'info');
        }
    }

    function toggleSignatureText() {
        var isChecked = $('#showSignature').is(':checked');
        if (isChecked) {
            $('#showSignatureLabel').html('<i class="bx bx-edit-alt me-1"></i> แสดงรายชื่อผู้จัดทำ (แสดงทุกหน้า)').css('color', '#4f46e5');
        } else {
            $('#showSignatureLabel').html('<i class="bx bx-edit-alt me-1"></i> แสดงรายชื่อผู้จัดทำ (แสดงเฉพาะหน้าแรก)').css('color', '#d97706');
        }
    }

    function updateRightsButtonText() {
        var total = $('.right-checkbox').length;
        var checked = $('.right-checkbox:checked').length;
        if (checked === total) {
            $('#dropdownRightsButton').html('<span><i class="bx bx-list-check me-2 text-primary"></i> เลือกสิทธิ (เลือกทั้งหมด)</span>');
        } else if (checked === 0) {
            $('#dropdownRightsButton').html('<span><i class="bx bx-x me-2 text-danger"></i> เลือกสิทธิ (ไม่ได้เลือก)</span>');
        } else {
            $('#dropdownRightsButton').html('<span><i class="bx bx-check me-2 text-success"></i> เลือกสิทธิ (' + checked + ' รายการ)</span>');
        }
    }

    function triggerLoad(){
        var mode = $('input[name="reportMode"]:checked').val();
        var show_sig = $('#showSignature').is(':checked') ? 1 : 0;
        
        var selectedRights = [];
        $('.right-checkbox:checked').each(function() {
            selectedRights.push($(this).val());
        });
        var totalRights = $('.right-checkbox').length;
        var rights_param = (selectedRights.length === totalRights && totalRights > 0) ? 'all' : (selectedRights.length > 0 ? selectedRights.join(',') : 'none');
        
        var param = "";

        // --- 1. เตรียมค่า Parameter ---
        if(mode == 'month'){
            var monthVal = $("#selectTypeOpt").val();
            // ถ้าเลือกเดือน แต่ไม่มีข้อมูล หรือเป็นค่า default
            if(monthVal == "0-0000" || monthVal == "") { return; } 
            
            param = "?mode=month&datepost=" + monthVal + "&show_sig=" + show_sig + "&rights=" + encodeURIComponent(rights_param);
        } else {
            var start = $("#startDate").val();
            var end = $("#endDate").val();
            // เช็คว่าใส่วันที่ครบไหม
            if(start == "" || end == "") { 
                Swal.fire('แจ้งเตือน', 'กรุณาระบุวันที่เริ่มต้นและสิ้นสุดให้ครบถ้วน', 'warning');
                return; 
            } 
            
            param = "?mode=range&start=" + start + "&end=" + end + "&show_sig=" + show_sig + "&rights=" + encodeURIComponent(rights_param);
        }

        // --- 2. แสดง Loading (หมุนติ้วๆ) ---
        Swal.fire({
            title: 'กำลังประมวลผล...',
            html: 'กรุณารอสักครู่ ระบบกำลังดึงข้อมูลและสร้างไฟล์ PDF<br><small>อาจใช้เวลาสักครู่ขึ้นอยู่กับปริมาณข้อมูล</small>',
            allowOutsideClick: false, // ห้ามคลิกข้างนอกเพื่อปิด
            didOpen: () => {
                Swal.showLoading(); // สั่งให้หมุน
            }
        });

        // --- 3. ตัวเช็คว่าโหลดเสร็จหรือยัง (ต้องเสร็จทั้ง 2 ไฟล์) ---
        var countLoaded = 0;
        var totalFrames = 2; // เพราะเรามี OPD และ IPD

        function checkClose() {
            countLoaded++;
            // ถ้าโหลดครบทั้ง 2 อันแล้ว
            if(countLoaded >= totalFrames){
                // เปลี่ยนจากหมุนๆ เป็น เครื่องหมายถูก (Success)
                Swal.fire({
                    icon: 'success',
                    title: 'เสร็จสิ้น!',
                    text: 'โหลดรายงานเรียบร้อยแล้วครับ',
                    timer: 2000, // แสดง 2 วินาทีแล้วหายไปเอง
                    showConfirmButton: false
                });
            }
        }

        // เคลียร์ Event เก่าออกก่อน (กันมันนับซ้ำถ้าพี่กดค้นหาหลายรอบ)
        $("#pdfview").off('load');
        $("#pdfviewi").off('load');

        // ผูก Event เมื่อโหลดเสร็จให้เรียกฟังก์ชัน checkClose
        $("#pdfview").on('load', checkClose);
        $("#pdfviewi").on('load', checkClose);

        // --- 4. ส่งค่าไปเริ่มโหลด PDF ---
        $("#pdfview").attr("src", "../report/pdf/Debtor-rightsr-report.php" + param);
        $("#pdfviewi").attr("src", "../report/pdf/Debtor-rightsr-reporti.php" + param);
    }
 


    </script>
  <?php if (!empty($_SESSION['role'])): ?>
  <!-- ✅ Quick Search (Ctrl+K) -->
  <?php include './includes/quick_search_ui.html'; ?>
  <?php endif; ?>
  </body>
</html>
