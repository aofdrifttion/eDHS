<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
mysqli_set_charset($conn, 'utf8mb4');

// ตรวจสอบและสร้างโครงสร้างคอลัมน์ STM อัตโนมัติ (Zero-Configuration Auto-Migration)
require_once __DIR__ . '/apistm/stm_helper.php';
ensure_stm_columns($conn);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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

    <script src="../assets/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/dist/sweetalert.css">



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

* {
  box-sizing: border-box;
}

#myInput {
  background-image: url('./files/search-icon-png-18.png');
  background-position: 10px 10px;
  background-repeat: no-repeat;
  width: 100%;
  font-size: 16px;
  padding: 12px 20px 12px 40px;
  border: 1px solid #ddd;
  margin-bottom: 12px;
}

#myTable {
  border-collapse: collapse;
  width: 100%;
  border: 1px solid #ddd;
  font-size: 18px;
}

#myTable th, #myTable td {
  text-align: left;
  padding: 12px;
}

#myTable tr {
  border-bottom: 1px solid #ddd;
}

#myTable tr.header, #myTable tr:hover {
  background-color: #f1f1f1;
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

      /* =========================================================================
         🌟 Modern STM & CKD Import UI (Statement of Account Reconcile)
         ========================================================================= */
      .stm-section-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
        padding: 24px;
        margin-bottom: 24px;
        transition: box-shadow 0.25s ease;
      }
      .stm-section-card:hover {
        box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
      }

      /* Box: General STM */
      .stm-box-general {
        background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 50%, #f7fee7 100%);
        border: 1px solid #ccfbf1;
        border-left: 5px solid #0d9488 !important;
        border-radius: 14px;
        padding: 22px 26px;
        box-shadow: 0 4px 16px rgba(13, 148, 136, 0.07);
        position: relative;
        overflow: hidden;
      }

      /* Box: CKD Dialysis STM */
      .stm-box-ckd {
        background: linear-gradient(135deg, #ecfeff 0%, #ffffff 50%, #f0fdf4 100%);
        border: 1px solid #cffafe;
        border-left: 5px solid #0891b2 !important;
        border-radius: 14px;
        padding: 22px 26px;
        box-shadow: 0 4px 16px rgba(8, 145, 178, 0.07);
        position: relative;
        overflow: hidden;
      }

      .stm-box-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding-bottom: 16px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
      }

      .stm-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
      }

      .stm-header-icon-general {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
        color: #0f766e;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
        flex-shrink: 0;
      }

      .stm-header-icon-ckd {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
        color: #0e7490;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 2px 8px rgba(8, 145, 178, 0.25);
        flex-shrink: 0;
      }

      .stm-header-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
      }

      .stm-header-subtitle {
        font-size: 0.82rem;
        color: #64748b;
        margin: 2px 0 0 0;
      }

      .stm-pill-badge-teal {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: #f0fdfa;
        color: #0f766e;
        border: 1px solid #99f6e4;
      }

      .stm-pill-badge-cyan {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: #ecfeff;
        color: #0e7490;
        border: 1px solid #a5f3fc;
      }

      .stm-field-label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 5px;
      }

      .stm-select, .stm-file-control {
        border-radius: 10px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 9px 14px !important;
        font-size: 13.5px !important;
        color: #1e293b !important;
        background-color: #ffffff !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.2s ease !important;
      }

      .stm-select:focus, .stm-file-control:focus {
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.18) !important;
        background-color: #ffffff !important;
      }

      .stm-action-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px dashed rgba(15, 23, 42, 0.12);
      }

      .btn-stm-submit {
        background: linear-gradient(135deg, #0d9488 0%, #059669 100%) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        padding: 10px 24px !important;
        border-radius: 10px !important;
        border: none !important;
        box-shadow: 0 3px 12px rgba(13, 148, 136, 0.35) !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer;
      }
      .btn-stm-submit:hover:not(:disabled) {
        background: linear-gradient(135deg, #0f766e 0%, #047857 100%) !important;
        transform: translateY(-1px);
        box-shadow: 0 5px 16px rgba(13, 148, 136, 0.45) !important;
        color: #ffffff !important;
      }
      .btn-stm-submit:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
      }

      .btn-ckd-submit {
        background: linear-gradient(135deg, #0891b2 0%, #0284c7 100%) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        padding: 10px 24px !important;
        border-radius: 10px !important;
        border: none !important;
        box-shadow: 0 3px 12px rgba(8, 145, 178, 0.35) !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer;
      }
      .btn-ckd-submit:hover:not(:disabled) {
        background: linear-gradient(135deg, #0e7490 0%, #0369a1 100%) !important;
        transform: translateY(-1px);
        box-shadow: 0 5px 16px rgba(8, 145, 178, 0.45) !important;
        color: #ffffff !important;
      }
      .btn-ckd-submit:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
      }

      .btn-stm-delete {
        background: #fff1f2 !important;
        color: #e11d48 !important;
        border: 1px solid #fecdd3 !important;
        font-weight: 600 !important;
        font-size: 13.5px !important;
        padding: 9px 20px !important;
        border-radius: 10px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: all 0.2s ease !important;
      }
      .btn-stm-delete:hover:not(.disabled) {
        background: #ffe4e6 !important;
        color: #be123c !important;
        border-color: #fda4af !important;
      }
      .btn-stm-delete.disabled {
        opacity: 0.55 !important;
        pointer-events: none !important;
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
          <!-- Navbar -->
          <nav
            class="layout-navbar container-fluid navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar" style="margin-bottom: 20px;">

            
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <!-- Search → เปิด Quick Search Modal -->
              <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center" style="cursor:pointer;" onclick="qs_open()" title="Quick Search (Ctrl+K)">
                  <i class="bx bx-search fs-4 lh-0" style="color:#696cff;"></i>
                  <input
                    type="text"
                    class="form-control border-0 shadow-none"
                    placeholder="ค้นหาด่วน... (Ctrl+K)"
                    aria-label="Quick Search"
                    readonly
                    onclick="qs_open()"
                    style="cursor:pointer; caret-color:transparent;background-color: #ffffff;"
                  />
                </div>
              </div>
              <!-- /Search -->

              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- Place this tag where you want the button to render. -->


                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block">ยินดีต้อนรับ</span>
                            <small class="text-muted"><?= $_SESSION['fullname']; ?></small>
                          </div>
                        </div>
                      </a>
                    </li>
                    
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>


          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-fluid">
              <div class="row">
                  
                <div class="col-12">

                  <!-- Card นำเข้า STM -->
                  <div class="stm-section-card">
                    <form id="upload-formSTM" enctype="multipart/form-data" class="stm-box-general">
                      
                      <!-- Header -->
                      <div class="stm-box-header">
                        <div class="stm-header-left">
                          <div class="stm-header-icon-general">
                            <i class='bx bx-spreadsheet'></i>
                          </div>
                          <div>
                            <h5 class="stm-header-title">นำเข้า STM (นำเข้าข้อมูลไฟล์ Excel)</h5>
                            <p class="stm-header-subtitle">นำเข้าไฟล์ Statement of Account (STM) เพื่อตัดยอดลูกหนี้และกระทบยอดชดเชย</p>
                          </div>
                        </div>
                        <div>
                          <span class="stm-pill-badge-teal">
                            <i class='bx bxs-file-blank text-success'></i> รองรับไฟล์ Excel (.xlsx, .xls)
                          </span>
                        </div>
                      </div>

                      <!-- Row 1: เลือกลูกหนี้สิทธิ + ไฟล์ -->
                      <div class="row g-3 mb-3">
                        <div class="col-md-4">
                          <label class="stm-field-label"><i class='bx bx-check-circle text-success'></i> เลือกลูกหนี้สิทธิ <span class="text-danger">*</span></label>
                          <select name="fund" id="fund_selectstm" class="form-select stm-select" required>
                            <option value="00" selected>--เลือกลูกหนี้สิทธิ--</option>
                            <option value="UCS">UCS-สิทธิบัตรทอง</option>
                            <option value="OFC">OFC-สิทธิข้าราชการ/สิทธิหน่วยงานต้นสังกัด</option>
                            <option value="LGO">LGO-สิทธิ อปท.</option>
                            <option value="BKK">BKK-สิทธิของกรุงเทพมหานคร</option>
                            <option value="SSS">SSS-สิทธิประกันสังคม</option>
                            <option value="BMT">BMT-ขสมก</option>
                            <option value="SRT">SRT-จ่ายตรงหน่วยงานอื่น OP</option>
                          </select>
                        </div>

                        <div class="col-md-8">
                          <label class="stm-field-label"><i class='bx bx-cloud-upload text-success'></i> ไฟล์ Excel ข้อมูล STM สำหรับนำเข้า <span class="text-danger">*</span></label>
                          <input class="form-control stm-file-control" name="excel_file" type="file" id="formFile" required />
                        </div>
                      </div>

                      <!-- Row 2: ตรวจสอบเลขที่หนังสือ + Action Bar -->
                      <div class="row g-3 align-items-end">
                        <div class="col-md-7">
                          <label class="stm-field-label"><i class='bx bx-bookmark-alt text-success'></i> ตรวจสอบเลขที่เอกสาร / หนังสือที่นำเข้าตัดลูกหนี้</label>
                          <select class="form-select stm-select" id="deletestm" name="deletestm">
                            <option value="00" selected disabled>-- เลือกรายการที่ต้องการลบ (ตามชุดเอกสาร หรือ REP) --</option>
                            <?php 
                            // 1. รายการเอกสาร STM ทั้งฉบับ (docno)
                            $sql_doc = "SELECT docno, COUNT(*) AS cnt, SUM(compensated) AS sum_comp 
                                        FROM imr_tb_check_invoice 
                                        WHERE docno IS NOT NULL AND docno != '' 
                                        GROUP BY docno 
                                        ORDER BY docno DESC";
                            $res_doc = mysqli_query($conn, $sql_doc);
                            if ($res_doc && mysqli_num_rows($res_doc) > 0) {
                              echo '<optgroup label="📑 รายการเอกสาร STM ทั้งฉบับ (ลบทั้งชุด 1 คลิก)">';
                              while ($rd = mysqli_fetch_assoc($res_doc)) {
                                $dno = htmlspecialchars($rd['docno']);
                                $cnt = number_format($rd['cnt']);
                                $sum_c = number_format((float)$rd['sum_comp'], 2);
                                echo '<option value="DOC:'.$dno.'">📄 [ทั้งฉบับ] '.$dno.' ('.$cnt.' รายการ | ฿'.$sum_c.')</option>';
                              }
                              echo '</optgroup>';
                            }

                            // 2. รายการตามเลขหนังสือ REP แยกรายชุด
                            $sqlzm = "SELECT rep, COUNT(*) AS cnt, SUM(compensated) AS sum_comp 
                                      FROM imr_tb_check_invoice 
                                      GROUP BY rep 
                                      ORDER BY rep DESC";
                            $resultzm = mysqli_query($conn, $sqlzm);
                            if ($resultzm && mysqli_num_rows($resultzm) > 0) {
                              echo '<optgroup label="📋 รายการตามเลขที่หนังสือ (Individual REPs)">';
                              while ($rowzm = mysqli_fetch_assoc($resultzm)) {
                                $gmm = htmlspecialchars($rowzm['rep']);
                                $cnt = number_format($rowzm['cnt']);
                                $sum_c = number_format((float)$rowzm['sum_comp'], 2);
                                echo '<option value="REP:'.$gmm.'">🔖 STM หนังสือที่ '.$gmm.' ('.$cnt.' รายการ | ฿'.$sum_c.')</option>';
                              }
                              echo '</optgroup>';
                            }
                            ?>
                          </select>
                        </div>

                        <div class="col-md-5">
                          <div class="d-flex align-items-center justify-content-end gap-2">
                            <button id="submit_stm" disabled type="submit" class="btn btn-stm-submit" data-bs-placement="bottom" data-bs-toggle="tooltip" title="ตรวจสอบและดูตัวอย่างข้อมูล STM ก่อนนำเข้า">
                              <i class="bx bx-search-alt"></i> ตรวจสอบและดูตัวอย่าง STM
                            </button>

                            <a id="delete_btnstm" class="btn btn-stm-delete disabled" data-bs-placement="bottom" data-bs-toggle="tooltip" title="ลบข้อมูล STM ตามเลขที่หนังสือ" href="#" onclick="return false;">
                              <i class="bx bx-trash"></i> ลบ STM
                            </a>
                          </div>
                        </div>
                      </div>

                      <!-- Status feedback -->
                      <div class="mt-3">
                        <h5 class="modal-title m-0" id="statusload" style="text-align: left; display: none; color: #0f766e; font-size: 14px;">
                          <span class="spinner-border spinner-border-sm text-success me-2" role="status"></span> รอสักครู่กำลังนำเข้าข้อมูล STM... <img src="./images/load.gif" alt="" style="width:24px;" />
                        </h5>
                        <p id="status" class="m-0 fw-semibold" style="text-align: left; color: #0022a5; font-size: 13.5px;"></p>
                      </div>

                    </form>
                    <hr class="my-4">
                    <div id="data_tabel_stm"></div>
                  </div>

                </div>
       
               
              </div>
              
            </div>

            <div class="container-fluid">
              <div class="row">
                  
                <div class="col-12">

                  <!-- Card นำเข้า STM ฟอกไต -->
                  <div class="stm-section-card">
                    <form id="upload-formCKD" enctype="multipart/form-data" class="stm-box-ckd">
                      
                      <!-- Header -->
                      <div class="stm-box-header">
                        <div class="stm-header-left">
                          <div class="stm-header-icon-ckd">
                            <i class='bx bx-pulse'></i>
                          </div>
                          <div>
                            <h5 class="stm-header-title">นำเข้า STM ฟอกไต (นำเข้าข้อมูลไฟล์ Excel / XML / ZIP)</h5>
                            <p class="stm-header-subtitle">นำเข้าข้อมูลชดเชยค่าบริการฟอกเลือดและล้างไตทางช่องท้อง (DCKD) รายกองทุน</p>
                          </div>
                        </div>
                        <div>
                          <span class="stm-pill-badge-cyan">
                            <i class='bx bx-archive text-info'></i> รองรับไฟล์ .xlsx, .xml, .zip (หลายไฟล์พร้อมกัน)
                          </span>
                        </div>
                      </div>

                      <!-- Row 1: เลือกลูกหนี้สิทธิ + ไฟล์ -->
                      <div class="row g-3 mb-3">
                        <div class="col-md-4">
                          <label class="stm-field-label"><i class='bx bx-check-circle text-info'></i> เลือกลูกหนี้สิทธิ <span class="text-danger">*</span></label>
                          <select name="ckd_select" id="ckd_select" class="form-select stm-select" required>
                            <option value="00" selected>--เลือกลูกหนี้สิทธิ--</option>
                            <option value="UCS">UCS-สิทธิบัตรทอง</option>
                            <option value="OFC">OFC-สิทธิข้าราชการ/สิทธิหน่วยงานต้นสังกัด</option>
                            <option value="SSS">SSS-สิทธิประกันสังคม</option>
                            <option value="LGO">LGO-สิทธิ อปท.</option>                                     
                          </select>
                        </div>

                        <div class="col-md-8">
                          <label class="stm-field-label"><i class='bx bx-cloud-upload text-info'></i> ไฟล์ข้อมูล STM ฟอกไต สำหรับนำเข้า <span class="text-danger">*</span></label>
                          <input type="file" name="excel_file[]" class="form-control stm-file-control" id="formFile2" accept=".xlsx,.xml,.zip" multiple required>
                        </div>
                      </div>

                      <!-- Row 2: ตรวจสอบเลขที่หนังสือ + Action Bar -->
                      <div class="row g-3 align-items-end">
                        <div class="col-md-7">
                          <label class="stm-field-label"><i class='bx bx-bookmark-alt text-info'></i> เลือก DCKD เลขที่หนังสือ</label>
                          <select class="form-select stm-select" id="deleteckd" name="deleteckd">
                            <option value="00" selected disabled>เลือก DCKD เลขที่หนังสือ</option>

                            <!-- กลุ่มจาก payno -->
                            <optgroup label="UCS-สิทธิบัตรทอง">
                              <?php
                              $sqlPayno = "SELECT rep FROM imr_tb_seamless_dckd WHERE fund = 'UCS' GROUP BY rep ORDER BY rep DESC";
                              $resultPayno = mysqli_query($conn, $sqlPayno);
                              if (mysqli_num_rows($resultPayno) > 0) {
                                while ($row = mysqli_fetch_assoc($resultPayno)) {
                                  $rep = $row['rep'];
                                  echo '<option value="' . $rep . '">เลขที่หนังสือที่ ' . $rep . '</option>';
                                }
                              }
                              ?>
                            </optgroup>

                            <!-- กลุ่มจาก payno -->
                            <optgroup label="SSS-สิทธิประกันสังคม">
                              <?php
                              $sqlPayno = "SELECT rep FROM imr_tb_seamless_dckd WHERE fund = 'SSS' GROUP BY rep ORDER BY rep DESC";
                              $resultPayno = mysqli_query($conn, $sqlPayno);
                              if (mysqli_num_rows($resultPayno) > 0) {
                                while ($row = mysqli_fetch_assoc($resultPayno)) {
                                  $rep = $row['rep'];
                                  echo '<option value="' . $rep . '">เลขที่หนังสือที่ ' . $rep . '</option>';
                                }
                              }
                              ?>
                            </optgroup>

                            <!-- กลุ่มจาก stm_doc -->
                            <optgroup label="OFC-สิทธิข้าราชการ/สิทธิหน่วยงานต้นสังกัด">
                              <?php
                              $sqlSTM = "SELECT stm_doc FROM imr_tb_seamless_dckd_summary_ofc GROUP BY stm_doc ORDER BY stm_doc DESC";
                              $resultSTM = mysqli_query($conn, $sqlSTM);
                              if (mysqli_num_rows($resultSTM) > 0) {
                                while ($row = mysqli_fetch_assoc($resultSTM)) {
                                  $stm_doc = $row['stm_doc'];
                                  echo '<option value="' . $stm_doc . '">เลขที่หนังสือที่ ' . $stm_doc . '</option>';
                                }
                              }
                              ?>
                            </optgroup>

                            <!-- กลุ่มจาก payno -->
                            <optgroup label="LGO-สิทธิ อปท.">
                              <?php
                              $sqlPayno = "SELECT rep FROM imr_tb_seamless_dckd WHERE fund = 'LGO' GROUP BY rep ORDER BY rep DESC";
                              $resultPayno = mysqli_query($conn, $sqlPayno);
                              if (mysqli_num_rows($resultPayno) > 0) {
                                while ($row = mysqli_fetch_assoc($resultPayno)) {
                                  $rep = $row['rep'];
                                  echo '<option value="' . $rep . '">เลขที่หนังสือที่ ' . $rep . '</option>';
                                }
                              }
                              ?>
                            </optgroup>

                          </select>
                        </div>

                        <div class="col-md-5">
                          <div class="d-flex align-items-center justify-content-end gap-2">
                            <button id="submit_ckd" disabled type="submit" class="btn btn-ckd-submit" data-bs-placement="bottom" data-bs-toggle="tooltip" title="นำเข้าข้อมูล DCKD">
                              <i class="bx bx-cloud-upload"></i> นำเข้าข้อมูล DCKD
                            </button>

                            <a id="delete_btnckd" class="btn btn-stm-delete disabled" data-bs-placement="bottom" data-bs-toggle="tooltip" title="ลบข้อมูล DCKD ตามเลขที่หนังสือ" href="#" onclick="return false;">
                              <i class="bx bx-trash"></i> ลบ CKD
                            </a>
                          </div>
                        </div>
                      </div>

                      <!-- Status feedback -->
                      <div class="mt-3">
                        <h5 class="modal-title m-0" id="statusloadc" style="text-align: left; display: none; color: #0891b2; font-size: 14px;">
                          <span class="spinner-border spinner-border-sm text-info me-2" role="status"></span> รอสักครู่กำลังนำเข้าข้อมูล DCKD... <img src="./images/load.gif" alt="" style="width:24px;" />
                        </h5>
                        <p id="statusc" class="m-0 fw-semibold" style="text-align: left; color: #0022a5; font-size: 13.5px;"></p>
                      </div>

                    </form>
                    <hr class="my-4">
                    <div id="data_tabel_ckd" style="height: 600px; overflow: scroll;"></div>
                  </div>

                </div>
       
               
              </div>
              
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


<!-- Modal STM Pre-Import Preview & Audit Center -->
<?php include __DIR__ . '/apistm/modal_stm_preview.php'; ?>

<!-- Modal สำหรับกรอกรหัสผ่าน -->
<div class="modal fade" id="passwordModals" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ยืนยันรหัสผ่านก่อนลบข้อมูล STM</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body">
        <input type="password" id="delete_passwords" class="form-control" placeholder="กรอกรหัสผ่าน" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-danger" id="confirm_deletes">ยืนยันลบ</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal สำหรับกรอกรหัสผ่าน -->
<div class="modal fade" id="passwordModalc" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ยืนยันรหัสผ่านก่อนลบข้อมูล CKD</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body">
        <input type="password" id="delete_passwordc" class="form-control" placeholder="กรอกรหัสผ่าน" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-danger" id="confirm_deletec">ยืนยันลบ</button>
      </div>
    </div>
  </div>
</div>

    <!-- การเชื่อมต่อ -->
                      <div class="modal fade" id="connModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                          <div class="modal-content" style="height: 845px;">
                            <div class="modal-header">
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
                                  <iframe src="./database_config/" width="100%" height="745"></iframe>
                                </div>
                              </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>


      function connmodal(){
        $('#connModal').modal('show');  
      }
      
      $(document).ready(function(){
       // fetch_data_nhso01();

        var yyy = '<?php echo $yyy; ?>';
        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        if (!isLoggedIn) {
            location.href = './login.php';
        }else{
          fetch_data_stm();
          fetch_data_ckd();
        }

      
        $("#statusload").hide();


        $('#fund_selectstm').on('change', function() {
          if ($(this).val() !== '00') {
            $('#submit_stm').prop('disabled', false);
          } else {
            $('#submit_stm').prop('disabled', true);
          }
        });

        $('#ckd_select').on('change', function() {
          if ($(this).val() !== '00') {
            $('#submit_ckd').prop('disabled', false);
          } else {
            $('#submit_ckd').prop('disabled', true);
          }
        });



        $(function() {
          $('#deletestm').on('change', function() {
            if ($(this).val() !== '00') {
              $('#delete_btnstm')
                .removeClass('disabled')
                .css({'pointer-events': 'auto', 'opacity': '1'})
                .attr('onclick', 'delete_stm()');
            } else {
              $('#delete_btnstm')
                .addClass('disabled')
                .css({'pointer-events': 'none', 'opacity': '0.6'})
                .attr('onclick', 'return false;');
            }
          });
        });


        $(function() {
          $('#deleteckd').on('change', function() {
            if ($(this).val() !== '00') {
              $('#delete_btnckd')
                .removeClass('disabled')
                .css({'pointer-events': 'auto', 'opacity': '1'})
                .attr('onclick', 'delete_ckd()');
            } else {
              $('#delete_btnckd')
                .addClass('disabled')
                .css({'pointer-events': 'none', 'opacity': '0.6'})
                .attr('onclick', 'return false;');
            }
          });
        });


      });



      // ผูก Submit Handler ตรงเข้ากับ Form เพื่อความปลอดภัยและป้องกันการผูก Listener ซ้ำซ้อน
      $("#upload-formSTM").on('submit', function(e) {
            e.preventDefault(); // ป้องกัน submit ปกติ

            var formData = new FormData(this);
            var fund = $("select[name='fund']").val();

            if (fund === "00") {
              Swal.fire({
                icon: 'error',
                title: 'กรุณาเลือกลูกหนี้สิทธิ',
                text: 'โปรดเลือกลูกหนี้สิทธิที่ถูกต้องก่อนดำเนินการนำเข้าข้อมูล',
                confirmButtonText: 'ตกลง'
              });
              return false;
            }

            var fileInput = document.getElementById('formFile');
            if (!fileInput || !fileInput.files || !fileInput.files.length) {
              Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกไฟล์ Excel',
                text: 'โปรดเลือกไฟล์ Statement (STM) ก่อนดำเนินการ',
                confirmButtonText: 'ตกลง'
              });
              return false;
            }

            var fileName = fileInput.files[0].name.toUpperCase();
            var isUcsFlow = (fund === 'UCS') || (fileName.indexOf('OPUCS') !== -1) || (fileName.indexOf('IPUCS') !== -1);

            // หากเป็นสิทธิ UCS หรือตรวจพบรูปแบบไฟล์ STM สปสช. ให้เข้าสู่ระบบพรีวิวและตรวจสอบความถูกต้องก่อนนำเข้า
            if (isUcsFlow) {
              formData.append('action', 'preview_stm');
              $("#statusload").show();
              $('#submit_stm').prop('disabled', true);

              Swal.fire({
                title: 'กำลังตรวจสอบไฟล์ STM...',
                html: '<p class="mb-2">ระบบกำลังอ่านข้อมูลและตรวจสอบโครงสร้างไฟล์ Statement สปสช.</p><p class="text-muted small">จำแนก 8 หมวดเงินชดเชย และจำลองจับคู่ผังลูกหนี้ในหน่วยความจำ...</p>',
                allowOutsideClick: false,
                didOpen: function() {
                  Swal.showLoading();
                }
              });

              $.ajax({
                url: "apistm/api_stm_ucs.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                timeout: 300000,
                success: function(response) {
                  $("#statusload").hide();
                  $('#submit_stm').prop('disabled', false);
                  Swal.close();

                  var res = typeof response === 'string' ? JSON.parse(response) : response;
                  if (res.status === 'success') {
                    // แสดงหน้าต่าง Modal พรีวิวและตรวจสอบความถูกต้องก่อนยืนยันนำเข้า
                    populateStmPreviewModal(res);
                  } else {
                    Swal.fire({
                      icon: 'error',
                      title: 'การตรวจสอบไฟล์ไม่ผ่าน',
                      text: res.message || 'โครงสร้างไฟล์ไม่ถูกต้อง หรือข้อมูลไม่ตรงกับเงื่อนไข',
                      confirmButtonText: 'รับทราบ'
                    });
                  }
                },
                error: function(xhr, status, error) {
                  $("#statusload").hide();
                  $('#submit_stm').prop('disabled', false);
                  Swal.close();
                  Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถติดต่อกับเซิร์ฟเวอร์ได้: ' + (error || 'Network Error'),
                    confirmButtonText: 'ตกลง'
                  });
                }
              });
              return false;
            }

            $("#statusload").show();

            $.ajax({
              url: "ลูกหนี้สิทธิ-insert-stm.php",
              type: "POST",
              data: formData,
              processData: false,
              contentType: false,
              timeout: 600000,
              success: function(response) {
                try {


                  var res = typeof response === 'string' ? JSON.parse(response) : response;

                  if (res.status === "success") {
                    $("#statusload").hide();
                    $("#status").text(res.message);

                    Swal.fire({
                      title: "นำเข้าข้อมูลเสร็จสิ้น!",
                      text: "นำเข้าสำเร็จ: " + res.success_count + " รายการ\nเกิดข้อผิดพลาด: " + res.error_count + " รายการ",
                      icon: "success",
                      confirmButtonText: "ตกลง"
                    }).then(() => {
                      $("#status").hide();
                      $("#upload-formSTM").trigger("reset");
                      location.reload();
                    });

                  } else {
                    $("#statusload").hide();
                    $("#status").text("เกิดข้อผิดพลาด: " + res.message);

                    Swal.fire({
                      title: "ข้อผิดพลาด!",
                      text: res.message,
                      icon: "error",
                      confirmButtonText: "ตกลง"
                    });
                  }
                } catch (err) {
                  $("#statusload").hide();
                  Swal.fire({
                    title: "ข้อผิดพลาด!",
                    text: "ไม่สามารถประมวลผลข้อมูลที่ส่งกลับได้:\n" + err.message,
                    icon: "error",
                    confirmButtonText: "ตกลง"
                  });
                  console.error("Response error:", response);
                }
              },
              error: function(xhr, status, error) {
                $("#statusload").hide();
                Swal.fire({
                  title: "เกิดข้อผิดพลาด!",
                  text: "ไม่สามารถติดต่อกับเซิร์ฟเวอร์ได้: " + error,
                  icon: "error",
                  confirmButtonText: "ตกลง"
                });
                console.error("AJAX error:", xhr.responseText);
              }
            });
          });

      $("#upload-formCKD").on('submit', function(e) {
            e.preventDefault(); // ป้องกัน submit ปกติ

            var formData = new FormData(this);

            const ckd_select = $('select[name="ckd_select"]').val();

            if (ckd_select === 'UCS' || ckd_select === 'SSS') {

              $("#statusloadc").show();

              $.ajax({
                url: "ลูกหนี้สิทธิ-insert-CKD.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                timeout: 600000,
                success: function(response) {
                  try {


                    var res = typeof response === 'string' ? JSON.parse(response) : response;

                    if (res.status === "success") {
                      $("#statusloadc").hide();
                      $("#statusc").text(res.message);

                      Swal.fire({
                        title: "นำเข้าข้อมูลเสร็จสิ้น!",
                        text: "นำเข้าสำเร็จ: " + res.success_count + " รายการ\nเกิดข้อผิดพลาด: " + res.error_count + " รายการ",
                        icon: "success",
                        confirmButtonText: "ตกลง"
                      }).then(() => {
                        $("#statusc").hide();
                        $("#upload-formCKD").trigger("reset");
                        location.reload();
                      });

                    } else {
                      $("#statusloadc").hide();
                      $("#statusc").text("เกิดข้อผิดพลาด: " + res.message);

                      Swal.fire({
                        title: "ข้อผิดพลาด!",
                        text: res.message,
                        icon: "error",
                        confirmButtonText: "ตกลง"
                      });
                    }
                  } catch (err) {
                    $("#statusloadc").hide();
                    Swal.fire({
                      title: "ข้อผิดพลาด!",
                      text: "ไม่สามารถประมวลผลข้อมูลที่ส่งกลับได้:\n" + err.message,
                      icon: "error",
                      confirmButtonText: "ตกลง"
                    });
                    console.error("Response error:", response);
                  }
                },
                error: function(xhr, status, error) {
                  $("#statusloadc").hide();
                  Swal.fire({
                    title: "เกิดข้อผิดพลาด!",
                    text: "ไม่สามารถติดต่อกับเซิร์ฟเวอร์ได้: " + error,
                    icon: "error",
                    confirmButtonText: "ตกลง"
                  });
                  console.error("AJAX error:", xhr.responseText);
                }
              });

            }     



            if (ckd_select === 'LGO') {

              $("#statusloadc").show();

              $.ajax({
                url: "ลูกหนี้สิทธิ-insert-CKDLGO.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                timeout: 600000,
                success: function(response) {
                  try {


                    var res = typeof response === 'string' ? JSON.parse(response) : response;

                    if (res.status === "success") {
                      $("#statusloadc").hide();
                      $("#statusc").text(res.message);

                      Swal.fire({
                        title: "นำเข้าข้อมูลเสร็จสิ้น!",
                        text: "นำเข้าสำเร็จ: " + res.success_count + " รายการ",
                        icon: "success",
                        confirmButtonText: "ตกลง"
                      }).then(() => {
                        $("#statusc").hide();
                        $("#upload-formCKD").trigger("reset");
                        location.reload();
                      });

                    } else {
                      $("#statusloadc").hide();
                      $("#statusc").text("เกิดข้อผิดพลาด: " + res.message);

                      Swal.fire({
                        title: "ข้อผิดพลาด!",
                        text: res.message,
                        icon: "error",
                        confirmButtonText: "ตกลง"
                      });
                    }
                  } catch (err) {
                    $("#statusloadc").hide();
                    Swal.fire({
                      title: "ข้อผิดพลาด!",
                      text: "ไม่สามารถประมวลผลข้อมูลที่ส่งกลับได้:\n" + err.message,
                      icon: "error",
                      confirmButtonText: "ตกลง"
                    });
                    console.error("Response error:", response);
                  }
                },
                error: function(xhr, status, error) {
                  $("#statusloadc").hide();
                  Swal.fire({
                    title: "เกิดข้อผิดพลาด!",
                    text: "ไม่สามารถติดต่อกับเซิร์ฟเวอร์ได้: " + error,
                    icon: "error",
                    confirmButtonText: "ตกลง"
                  });
                  console.error("AJAX error:", xhr.responseText);
                }
              });

            }


            if (ckd_select === 'OFC') {

                  $.ajax({
                    url: 'ลูกหนี้สิทธิ-insert-CKDofc.php', // เช่น upload_cocd.php
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function () {
                      Swal.fire({
                        title: 'กำลังอัปโหลด...',
                        text: 'กรุณารอสักครู่',
                        allowOutsideClick: false,
                        didOpen: () => {
                          Swal.showLoading();
                        }
                      });
                    },
                    success: function(response) {
                      Swal.close();

                      try {
                        const res = JSON.parse(response);

                        Swal.fire({
                          icon: res.status === 'success' ? 'success' : 'error',
                          title: res.status === 'success' ? 'สำเร็จ!' : 'เกิดข้อผิดพลาด!',
                          text: res.message,
                          confirmButtonText: 'ตกลง'
                        }).then(() => {
                          if (res.redirect) {
                            window.location.href = res.redirect;
                          }
                        });

                      } catch (e) {
                        Swal.fire({
                          icon: 'error',
                          title: 'ผิดพลาด',
                          text: 'ไม่สามารถนำเข้าข้อมูลได้ หรือมีข้อมูลอยู่แล้ว!'
                        }).then(() => {    
                             location.reload();
                        });
                      }
                    },
                    error: function () {
                      Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
                      });
                    }
                  });


            } 

          });




        function fetch_data_stm()
        { 
          var actionstm = "fetch";
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionstm:actionstm},
            success:function(data)
            {
              $('#data_tabel_stm').html(data);
            }
          })
        }


        function fetch_data_rep()
        { 
          var actionrep = "fetch";
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionrep:actionrep},
            success:function(data)
            {
              $('#data_tabel_rep').html(data);
            }
          })
        }

        function fetch_data_ckd()
        { 
          var actionckd = "fetch";
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionckd:actionckd},
            success:function(data)
            {
              $('#data_tabel_ckd').html(data);
            }
          })
        }

//DELETE stm

      let deleteInProgress = false; // ป้องกันการกดซ้ำ
        // กดปุ่มลบ → เปิด modal
      function delete_stm(){
          $('#delete_passwords').val(''); // เคลียร์รหัสผ่านก่อนแสดง modal
          $('#passwordModals').modal('show');
      }

        // กดยืนยันลบ
      $('#confirm_deletes').on('click', function () {
            const password = $('#delete_passwords').val().trim();
            const deletestm = $('select[name="deletestm"]').val();


            if (!password) {
              $("#upload-formSTM").trigger("reset");
              $('#passwordModals').modal('hide');
              Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสผ่านก่อนยืนยันการลบ', 'warning');
              return;
            }

            if (deleteInProgress) return;
            deleteInProgress = true;

          if (deletestm === '00' || !deletestm) {
            $("#upload-formSTM").trigger("reset");
            $('#passwordModals').modal('hide');
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการเอกสารหรือหนังสือที่ต้องการลบก่อนยืนยัน', 'warning');
            return;
          }else{

              $.ajax({
                url: 'ลบลูกหนี้สิทธิstm.php',
                type: 'POST',
                dataType: 'json',
                data: {
                  action: 'delete_stm',
                  password: password,
                  deletestm: deletestm
                },
                beforeSend: function () {
                  // อาจแสดง loading spinner ได้ตรงนี้
                  $('#confirm_deletes').prop('disabled', true).text('กำลังลบ...');
                },
                success: function (response) {

                  $('#passwordModals').modal('hide');

                  if (response.status === 'success') {
                    Swal.fire({
                      icon: 'success',
                      title: 'สำเร็จ',
                      text: response.message,
                      confirmButtonText: 'ตกลง'
                    }).then(() => {
                      
                      location.reload();
                    });
                  } else {
                    Swal.fire('ผิดพลาด', response.message, 'error');
                  }
                },
                error: function () {
                  $('#passwordModals').modal('hide');
                  Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
                },
                complete: function () {
                  deleteInProgress = false;
                  $('#confirm_deletes').prop('disabled', false).text('ยืนยันลบ');
                }
              });

            }

      });
 

 
      // เคลียร์รหัสเมื่อปิด modal
      $('#passwordModals').on('hidden.bs.modal', function () {
          $('#delete_passwords').val('');
          $('#confirm_deletes').prop('disabled', false).text('ยืนยันลบ');
          deleteInProgress = false;
      });

 //DELETE CKD

        let deleteInProgressc = false; // ป้องกันการกดซ้ำ
        // กดปุ่มลบ → เปิด modal
        function delete_ckd(){
          $('#delete_passwordc').val(''); // เคลียร์รหัสผ่านก่อนแสดง modal
          $('#passwordModalc').modal('show');
        }

        // กดยืนยันลบ
      $('#confirm_deletec').on('click', function () {
            const password = $('#delete_passwordc').val().trim();
            const deleteckd = $('select[name="deleteckd"]').val();


            if (!password) {
              $("#upload-formCKD").trigger("reset");
              $('#passwordModalc').modal('hide');
              Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสผ่านก่อนยืนยันการลบ', 'warning');
              return;
            }

            if (deleteInProgressc) return;
            deleteInProgressc = true;

          if (deleteckd === '00') {
            $("#upload-formCKD").trigger("reset");
            $('#passwordModalc').modal('hide');
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกเลขหนังสือ payno ก่อนลบข้อมูล', 'warning');
            return;
          }else{

              $.ajax({
                url: 'ลบลูกหนี้สิทธิckd.php',
                type: 'POST',
                dataType: 'json',
                data: {
                  action: 'delete_ckd',
                  password: password,
                  deleteckd: deleteckd
                },
                beforeSend: function () {
                  // อาจแสดง loading spinner ได้ตรงนี้
                  $('#confirm_deletec').prop('disabled', true).text('กำลังลบ...');
                },
                success: function (response) {

                  $('#passwordModalc').modal('hide');

                  if (response.status === 'success') {
                    Swal.fire({
                      icon: 'success',
                      title: 'สำเร็จ',
                      text: response.message,
                      confirmButtonText: 'ตกลง'
                    }).then(() => {
                      
                      location.reload();
                    });
                  } else {
                    Swal.fire('ผิดพลาด', response.message, 'error');
                  }
                },
                error: function () {
                  $('#passwordModalc').modal('hide');
                  Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
                },
                complete: function () {
                  deleteInProgressc = false;
                  $('#confirm_deletec').prop('disabled', false).text('ยืนยันลบ');
                }
              });

            }

        });

            // เคลียร์รหัสเมื่อปิด modal
            $('#passwordModalc').on('hidden.bs.modal', function () {
              $('#delete_passwordc').val('');
              $('#confirm_deletec').prop('disabled', false).text('ยืนยันลบ');
              deleteInProgressc = false;
      });



 
    </script>

  <?php if (!empty($_SESSION['role'])): ?>
  <!-- ✅ Quick Search (Ctrl+K) -->
  <?php include './includes/quick_search_ui.html'; ?>
  <?php endif; ?>

  </body>
</html>
