<?php
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
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="../assets/js/config.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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

      /* Executive Filter Card */
      .exec-filter-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        border-left: 5px solid #4f46e5 !important;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        margin-bottom: 24px;
        padding: 20px 24px;
      }

      .exec-header-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #4338ca;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
        flex-shrink: 0;
      }

      .exec-pill-badge {
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }

      .exec-input-group {
        border-radius: 10px;
        overflow: hidden;
        border: 1.5px solid #cbd5e1;
        transition: all 0.2s ease;
        background: #ffffff;
      }
      .exec-input-group:focus-within {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
      }
      .exec-input-group .input-group-text {
        background: #f8fafc;
        border: none;
        color: #4f46e5;
        font-size: 16px;
        padding-left: 14px;
        padding-right: 10px;
      }
      .exec-input-group select {
        border: none !important;
        box-shadow: none !important;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        padding: 9px 14px;
        cursor: pointer;
      }

      /* Executive Dashboard Card */
      .exec-dashboard-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 40px -4px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        margin-bottom: 30px;
      }

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
            <li class="menu-item <?= ($current_page == 'index.php') ? 'active' : '' ?>">
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
                      // 1. เช็คเปลี่ยนกลุ่มเมนู
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

           <nav  class="container-fluid" id="layout-navbar" style="margin-bottom: 20px;">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-fluid">
              <div class="row">


                <!-- Total Revenue -->
                <div class="col-12 mb-4">
                  <!-- Executive Header & Month Filter Card -->
                  <div class="exec-filter-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                      <!-- Left: Icon & Titles -->
                      <div class="d-flex align-items-center gap-3">
                        <div class="exec-header-icon">
                          <i class='bx bx-line-chart'></i>
                        </div>
                        <div>
                          <h4 class="mb-0 fw-bold text-dark">รายงานเสนอผู้บริหาร</h4>
                          <p class="mb-0 text-muted small">สรุปผลการบริหารลูกหนี้ การรับรู้รายได้ และสถานะทางการเงิน (Executive Dashboard)</p>
                        </div>
                      </div>

                      <!-- Right: Month Selector & Badge -->
                      <div class="d-flex align-items-center flex-wrap gap-3">
                        <span class="exec-pill-badge">
                          <i class='bx bx-briefcase text-primary'></i> ผู้บริหารระดับสูง
                        </span>
                        <div style="min-width: 280px;">
                          <div class="exec-input-group input-group">
                            <span class="input-group-text"><i class='bx bx-calendar-check'></i></span>
                            <select id="selectTypeOpt" name="selectTypeOpt" class="form-select">
                              <?php 
                              $latestMonth = '';
                              $sqlLatest = "SELECT monthtxt
                                            FROM imr_tb_debtor_rights_opd
                                            GROUP BY monthtxt
                                            ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC
                                            LIMIT 1";
                              $resultLatest = mysqli_query($conn, $sqlLatest);
                              if ($rowLatest = mysqli_fetch_assoc($resultLatest)) {
                                $latestMonth = $rowLatest['monthtxt'];
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
                    </div>
                  </div>

                  <!-- Hidden Data Calculation Elements (Required by JS) -->
                  <div style="display: none;">
                    <span id="vtxt0"></span>
                    <span id="vtxt1"></span>
                    <span id="vtxt2"></span>
                    <span id="vtxt3"></span>
                    <span id="vtxt4"></span>
                    <span id="vtxt6"></span>
                    <span id="pvtxt1"></span>
                    <span id="pvtxt2"></span>
                    <span id="pvtxt4"></span>
                    <span id="pvtxt15"></span>
                    <span id="pvtxt16"></span>
                    <table class="responsive-table"><tbody id="tt01"></tbody></table>
                  </div>

                  <!-- Modern Dashboard Frame Card -->
                  <div class="exec-dashboard-card position-relative">
                    <iframe id="dashframe" src="" style="width: 100%; height: 2150px; min-height: 100vh; border: none; display: block; border-radius: 20px;"></iframe>
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


<a href="logout.php" class="floating-logout-btn">
    <iconify-icon icon="solar:logout-2-bold" width="22" style="vertical-align: middle; margin-right: 8px;"></iconify-icon> 
</a>

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

      $(document).ready(function(){
        var yyy = '<?php echo $yyy; ?>';
      

           var yyy = '<?php echo $yyy; ?>';


            var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

            if (!isLoggedIn) {
              location.href = './login.php';
            }else{
             fetch_text();
             //setdas();
             //settingtxt();
             // เรียกให้ทำงานอัตโนมัติเมื่อโหลดหน้า
             $('#selectTypeOpt').trigger('change');

           }

      });



      $('#selectTypeOpt').change(function() {

        Swal.fire({
            title: 'กำลังประมวลผล',
            text: 'กำลังโหลดข้อมูล Dashboard...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => { Swal.showLoading(); }
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


        let partss = selectedMonth.split('-'); // แยกเดือน-ปี
        let years = parseInt(partss[1], 10);
        let monthValues = parseInt(partss[0], 10) - 2; // ลบ 3 เดือน

        // ถ้าเดือนติดลบหรือเป็น 0 ให้วนกลับไปปีที่แล้ว
        while (monthValues <= 0) {
          monthValues += 12;
          years--;
        }

        
        let monthlasts = (monthValues < 10 ? '' : '') + monthValues + '-' + years;

        console.log('Selected Month:', selectedMonth); // แสดงเดือนที่เลือก
        console.log('Previous Month:', monthlasts); // แสดงเดือนก่อนหน้า

        // ใช้ AJAX เพื่อดึงข้อมูลใหม่
        // gprintPDF(selectedMonth);

        $.ajax({
              url: 'ทะเบียนคุมลูกหนี้1print.php',
              type: 'POST',
              data: { month: selectedMonth, monthlast: monthlast , monthlasts: monthlasts},
              success: function(response) {
                  $('#tt01').html(response);
                  fetch_text();

                  // เปลี่ยน src ของ iframe
                  const newSrc = "./dashboard.php";
                  $('#dashframe').attr('src', newSrc);

                  Swal.close();

              },
              error: function(xhr, status, error) {
                  Swal.fire('Error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
              }
          });
      });

        function fetch_data_nhso02(year)
        { 

            
          var action2 = year;
          $.ajax({
            url:"ข้อมูลประชากรและสิทธิ-insert.php",
            method:"POST",
            data:{action2:action2},
            success:function(data)
            {
              $('#tableaj01').html(data);
              get_stat_all_02()
            }
          })
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


        function settingtxt() {
              $('#s11txt').text('('+$('#s1txt').val()+')');
              $('#p11txt').text('ตำแหน่ง'+$('#p1txt').val());

              $('#s22txt').text('('+$('#s2txt').val()+')');
              $('#p22txt').text('ตำแหน่ง'+$('#p2txt').val());
              
              $('#s33txt').text('('+$('#s3txt').val()+')');
              $('#p33txt').text('ตำแหน่ง'+$('#p3txt').val());
              
              $('#s44txt').text('('+$('#s4txt').val()+')');
              $('#p44txt').text('ตำแหน่ง'+$('#p4txt').val());
              
              $('#s55txt').text('('+$('#s5txt').val()+')');
              $('#p55txt').text('ตำแหน่ง'+$('#p5txt').val());


              $('#ps11txt').text('('+$('#s1txt').val()+')');
              $('#pp11txt').text('ตำแหน่ง'+$('#p1txt').val());

              $('#ps22txt').text('('+$('#s2txt').val()+')');
              $('#pp22txt').text('ตำแหน่ง'+$('#p2txt').val());
              
              $('#ps33txt').text('('+$('#s3txt').val()+')');
              $('#pp33txt').text('ตำแหน่ง'+$('#p3txt').val());
              
              $('#ps44txt').text('('+$('#s4txt').val()+')');
              $('#pp44txt').text('ตำแหน่ง'+$('#p4txt').val());
              
              $('#ps55txt').text('('+$('#s5txt').val()+')');
              $('#pp55txt').text('ตำแหน่ง'+$('#p5txt').val());


        }


      function setdas() { 

        
        

      }


      function get_stat_all_02(){
          

            Highcharts.setOptions({
                lang: {
                    decimalPoint: '.',
                    thousandsSep: ','
                }
            });

            Highcharts.chart('container02', {

              data: {
                    table: 'datatable',
                },
                chart: {
                    type: 'column'
                    
                },
                title: {
                    text: 'ความครอบคลุมสิทธิการรักษาพยาบาล'
                },
                credits: {
                  enabled: false
                },
                subtitle: {
                    text:
                        ' '
                },
                xAxis: {
                    type: 'category'
                },
                yAxis: {

                    allowDecimals: false,
                    title: {
                        text: 'จำนวน'
                    }
                },
                  plotOptions: {
                    series: {
                      borderWidth: 0,
                      dataLabels: {
                        enabled: true,
                        format: '{point.y:,.0f}'
                      }
                    }
                  },
                  legend: {
                    itemDistance: 30,
                    borderColor:'#ddd',
                    borderWidth: 0.8,
                    borderRadius: 15
                  },
                  tooltip: {
                    headerFormat: '<span style="font-size:12px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                    '<td style="padding:0"><b>{point.y:,.2f}</b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                  }
            });
      }


    </script>



  </body>
</html>
