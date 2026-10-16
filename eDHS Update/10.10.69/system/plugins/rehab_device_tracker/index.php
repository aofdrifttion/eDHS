<?php
/**
 * Rehab & Medical Device Compensation Tracker Plugin
 * Main Responsive Interface & Analytics Controller
 * eDebtor Hospital System (eDHS) - Phonsai Hospital (11065)
 */

session_start();
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/plugin_config.php';

$fullname = $_SESSION['fullname'] ?? 'ผู้ใช้งานระบบ';
$user_role = $_SESSION['role'] ?? 'user';
$user_id   = $_SESSION['user_id'] ?? 0;

$hospital_name = $configData['hospital'] ?? 'โรงพยาบาลโพนทราย';
$hospcode      = $configData['hospcode'] ?? '11065';

// Dynamic Thai Fiscal Year Calculation (Oct 1 - Sep 30)
$cur_m = (int)date('n');
$cur_y = (int)date('Y');
$cur_be = $cur_y + 543;
$default_fiscal_year = ($cur_m >= 10) ? ($cur_be + 1) : $cur_be;
$default_fy_ce = $default_fiscal_year - 543;
$default_start_date = ($default_fy_ce - 1) . '-10-01';
$default_end_date = date('Y-m-d');

$fy_initial_cache = __DIR__ . "/rehab_5years_cache_{$default_fiscal_year}.json";
if (!file_exists($fy_initial_cache)) {
    $fy_initial_cache = __DIR__ . "/rehab_5years_cache.json";
}
$initial_5years_json = file_exists($fy_initial_cache) ? file_get_contents($fy_initial_cache) : '{}';
?>
<!DOCTYPE html>
<html lang="th" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../../../assets/">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>ติดตามการเบิกชดเชยอุปกรณ์และเวชกรรมฟื้นฟู | eDHS <?= htmlspecialchars($hospital_name) ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../../../assets/img/favicon/favicon.ico" />

  <!-- Google Fonts: Noto Sans Thai & Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Icons: Boxicons -->
  <link rel="stylesheet" href="../../../assets/vendor/fonts/boxicons.css" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="../../../assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="../../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="../../../assets/css/demo.css" />

  <!-- Vendors CSS -->
  <link rel="stylesheet" href="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../../../assets/vendor/libs/apex-charts/apex-charts.css" />

  <!-- Flatpickr CSS & Thai Localization -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    /* System Typography & Premium High-Contrast Black Aesthetic */
    body, .layout-wrapper, .card, table, input, button, select, textarea, .modal, .badge {
      font-family: 'Noto Sans Thai', 'Poppins', sans-serif !important;
    }
    .bx, [class^="bx-"], [class*=" bx-"] {
      font-family: 'boxicons' !important;
    }

    /* High-Contrast Clear Black Typography - ไม่เอาสีเทา */
    body, .layout-wrapper, .content-wrapper, .layout-page {
      color: #0f172a !important;
    }

    /* All muted/secondary texts converted to crisp dark readable black */
    .text-muted,
    .text-secondary,
    small.text-muted,
    span.text-muted,
    label.text-muted,
    p.text-muted,
    div.text-muted,
    td.text-muted,
    th.text-muted {
      color: #111827 !important;
      font-weight: 500;
    }

    /* Form labels & titles */
    .form-label, label {
      color: #000000 !important;
      font-weight: 700 !important;
    }

    /* KPI summary card labels & subtitles */
    .kpi-card small,
    .kpi-card .text-muted,
    .kpi-card span.text-muted {
      color: #000000 !important;
      font-weight: 600 !important;
    }
    .kpi-card h4 {
      font-weight: 800 !important;
    }

    /* Top 3 rank cards text */
    .rank-card .text-muted,
    .rank-card small {
      color: #000000 !important;
      font-weight: 600 !important;
    }
    .rank-card h6 {
      color: #000000 !important;
      font-weight: 700 !important;
    }

    /* Tables headers and cells */
    .table thead th,
    .table-rehab thead th {
      background-color: #f1f5f9 !important;
      color: #000000 !important;
      font-weight: 700 !important;
      font-size: 0.86rem;
      border-bottom: 2px solid #94a3b8 !important;
    }

    .table tbody td,
    .table-rehab tbody td {
      color: #0f172a !important;
      font-weight: 500;
      font-size: 0.9rem;
    }

    /* Gradient Hero Cards */
    .bg-gradient-primary-glow {
      background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
      color: #ffffff;
    }
    .bg-gradient-success-glow {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      color: #ffffff;
    }
    .bg-gradient-warning-glow {
      background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
      color: #ffffff;
    }
    .bg-gradient-danger-glow {
      background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
      color: #ffffff;
    }
    .bg-gradient-purple-glow {
      background: linear-gradient(135deg, #654ea3 0%, #eaafc8 100%);
      color: #ffffff;
    }
    .bg-gradient-teal-glow {
      background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%);
      color: #ffffff;
    }

    .kpi-card {
      transition: all 0.25s ease-in-out;
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .kpi-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .rank-card {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #cbd5e1;
      transition: all 0.25s ease-in-out;
    }
    .rank-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .rank-badge-icon {
      position: absolute;
      top: -12px;
      right: 15px;
      font-size: 2.5rem;
      opacity: 0.85;
    }

    /* Custom Navigation Tabs */
    .nav-tabs .nav-link {
      font-weight: 700;
      font-size: 0.95rem;
      color: #1e293b;
      border: none;
      border-bottom: 3px solid transparent;
      padding: 0.75rem 1.25rem;
      transition: all 0.2s;
    }
    .nav-tabs .nav-link.active {
      color: #696cff;
      border-bottom: 3px solid #696cff;
      background: transparent;
    }
    .nav-tabs .nav-link:hover {
      border-bottom-color: #696cff;
    }

    /* Filter Bar Styling */
    .filter-panel {
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid #cbd5e1;
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }

    /* Status Pill Badges */
    .badge-status {
      padding: 0.4rem 0.75rem;
      font-size: 0.78rem;
      border-radius: 20px;
      font-weight: 700;
    }

    /* Interactive row hover for device summary table */
    .device-summary-row {
      cursor: pointer;
      transition: background-color 0.15s ease-in-out;
    }
    .device-summary-row:hover {
      background-color: #f0f7ff !important;
    }

    .hover-elevate {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-elevate:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
    }

    .presentation-canvas:fullscreen {
      padding: 3rem !important;
      background: #ffffff !important;
      overflow-y: auto;
    }

    /* Flatpickr Thai Calendar Custom Styling - คมชัด เห็นเด่นชัด เลือกง่าย */
    .flatpickr-calendar {
      font-family: 'Noto Sans Thai', 'Poppins', sans-serif !important;
      border-radius: 16px !important;
      box-shadow: 0 16px 40px rgba(15, 23, 42, 0.22), 0 4px 12px rgba(0,0,0,0.06) !important;
      border: 1.5px solid #94a3b8 !important;
      overflow: hidden !important;
      width: 326px !important;
      background: #ffffff !important;
      padding-bottom: 6px !important;
    }
    .flatpickr-calendar::before,
    .flatpickr-calendar::after {
      border-bottom-color: #1e3a8a !important;
    }

    /* Calendar Header - Vibrant Royal Blue Gradient */
    .flatpickr-months {
      background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;
      padding: 6px 4px !important;
      position: relative;
    }
    .flatpickr-months .flatpickr-month {
      background: transparent !important;
      color: #ffffff !important;
      height: 48px !important;
    }
    .flatpickr-current-month {
      padding-top: 6px !important;
      font-size: 1.15rem !important;
      font-weight: 700 !important;
      color: #ffffff !important;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 6px;
    }
    .flatpickr-current-month .cur-month {
      font-weight: 800 !important;
      color: #ffffff !important;
      margin-right: 4px;
    }
    .flatpickr-current-month .cur-month:hover {
      background: rgba(255, 255, 255, 0.2) !important;
      border-radius: 6px;
    }
    .flatpickr-current-month input.cur-year {
      font-weight: 800 !important;
      color: #ffffff !important;
      background: rgba(255, 255, 255, 0.2) !important;
      border: 1px solid rgba(255, 255, 255, 0.4) !important;
      border-radius: 6px !important;
      padding: 2px 6px !important;
    }
    .flatpickr-current-month select.flatpickr-monthDropdown-months {
      background: rgba(255, 255, 255, 0.2) !important;
      color: #ffffff !important;
      font-weight: 800 !important;
      border: 1px solid rgba(255, 255, 255, 0.4) !important;
      border-radius: 6px !important;
      padding: 2px 6px !important;
    }
    .flatpickr-current-month select.flatpickr-monthDropdown-months option {
      background: #1e3a8a !important;
      color: #ffffff !important;
      font-weight: 600 !important;
    }
    .flatpickr-current-month .numInputWrapper span.arrowUp:after {
      border-bottom-color: #ffffff !important;
    }
    .flatpickr-current-month .numInputWrapper span.arrowDown:after {
      border-top-color: #ffffff !important;
    }

    /* Month Navigation Arrows - ชัดเจน กดง่าย */
    .flatpickr-months .flatpickr-prev-month,
    .flatpickr-months .flatpickr-next-month {
      color: #ffffff !important;
      fill: #ffffff !important;
      padding: 6px !important;
      height: 34px !important;
      width: 34px !important;
      top: 10px !important;
      border-radius: 50% !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      transition: all 0.2s ease !important;
      opacity: 0.95 !important;
    }
    .flatpickr-months .flatpickr-prev-month:hover,
    .flatpickr-months .flatpickr-next-month:hover {
      background: rgba(255, 255, 255, 0.3) !important;
      transform: scale(1.15) !important;
      opacity: 1 !important;
    }
    .flatpickr-months .flatpickr-prev-month svg,
    .flatpickr-months .flatpickr-next-month svg {
      fill: #ffffff !important;
      stroke: #ffffff !important;
      stroke-width: 1px;
      width: 16px !important;
      height: 16px !important;
    }

    /* Weekday Headers - แยกวันธรรมดาและวันหยุดชัดเจน */
    .flatpickr-weekdays {
      background: #f1f5f9 !important;
      border-bottom: 1.5px solid #cbd5e1 !important;
      height: 36px !important;
    }
    span.flatpickr-weekday {
      color: #1e293b !important;
      font-weight: 700 !important;
      font-size: 0.88rem !important;
      line-height: 36px !important;
    }
    /* วันเสาร์ (ส) - สีม่วง */
    span.flatpickr-weekday:nth-child(6) {
      color: #7c3aed !important;
      font-weight: 800 !important;
    }
    /* วันอาทิตย์ (อา) - สีแดง */
    span.flatpickr-weekday:nth-child(7) {
      color: #dc2626 !important;
      font-weight: 800 !important;
    }

    /* Days Grid Layout & High Contrast Numbers */
    .flatpickr-days {
      width: 326px !important;
    }
    .dayContainer {
      width: 326px !important;
      min-width: 326px !important;
      max-width: 326px !important;
      padding: 8px 6px !important;
      box-sizing: border-box;
    }
    .flatpickr-day {
      width: 40px !important;
      height: 38px !important;
      line-height: 38px !important;
      max-width: 40px !important;
      margin: 2px 2px !important;
      border-radius: 10px !important;
      font-size: 0.98rem !important;
      font-weight: 700 !important;
      color: #0f172a !important;
      border: 1.5px solid transparent !important;
      transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
      box-sizing: border-box;
    }

    /* เสาร์-อาทิตย์ คมชัด */
    .flatpickr-day:nth-child(7n-1):not(.selected):not(.today) {
      color: #6d28d9 !important;
      font-weight: 700 !important;
    }
    .flatpickr-day:nth-child(7n):not(.selected):not(.today) {
      color: #dc2626 !important;
      font-weight: 700 !important;
    }

    /* วันของเดือนก่อน/ถัดไป อ่านออกแต่แยกแยะได้ */
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay {
      color: #94a3b8 !important;
      font-weight: 500 !important;
      opacity: 0.65;
    }

    /* Hover State - นุ่มนวล ชัดเจน เลือกง่าย */
    .flatpickr-day:hover:not(.selected) {
      background: #eff6ff !important;
      border-color: #93c5fd !important;
      color: #1d4ed8 !important;
      font-weight: 800 !important;
      transform: translateY(-2px) scale(1.06);
      box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2) !important;
    }

    /* Selected Day - สีน้ำเงินเด่นชัด มีมิติ */
    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
      border-color: #1d4ed8 !important;
      color: #ffffff !important;
      font-weight: 800 !important;
      transform: scale(1.06);
      box-shadow: 0 6px 14px rgba(37, 99, 235, 0.45) !important;
    }

    /* วันนี้ (Today) - ล้อมกรอบน้ำเงินเด่นชัด */
    .flatpickr-day.today:not(.selected) {
      border: 2px solid #2563eb !important;
      background: #dbeafe !important;
      color: #1e40af !important;
      font-weight: 800 !important;
    }

    /* Disabled */
    .flatpickr-day.flatpickr-disabled,
    .flatpickr-day.flatpickr-disabled:hover {
      color: #cbd5e1 !important;
      opacity: 0.35 !important;
      cursor: not-allowed !important;
      transform: none !important;
      box-shadow: none !important;
      background: transparent !important;
      border-color: transparent !important;
    }

    /* Datepicker Input Focus Effect */
    .thai-datepicker {
      cursor: pointer !important;
      transition: all 0.2s ease !important;
      border: 1.5px solid #cbd5e1 !important;
    }
    .thai-datepicker:hover,
    .thai-datepicker:focus {
      border-color: #3b82f6 !important;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }

    /* Loader */
    .spinner-overlay {
      display: none;
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(255, 255, 255, 0.7);
      z-index: 10;
      justify-content: center;
      align-items: center;
      border-radius: 12px;
    }
  </style>
</head>

<body>
  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

      <!-- Menu (Sidebar) -->
      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="../../" class="app-brand-link">
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
              <a href="../../" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-alt"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>

          <!-- Dynamic Sidebar Menu from tb_menus -->
          <?php
          if ($user_role === 'admin') {
              $sql_menu = "SELECT * FROM tb_menus ORDER BY sort_order ASC";
          } else {
              $sql_menu = "SELECT m.* FROM tb_menus m 
                           JOIN tb_user_permissions p ON m.id = p.menu_id 
                           WHERE p.user_id = '$user_id' 
                           ORDER BY m.sort_order ASC";
          }
          $result_menu = mysqli_query($conn, $sql_menu);
          $curr_group = '';

          if ($result_menu && mysqli_num_rows($result_menu) > 0) {
              while ($menu = mysqli_fetch_assoc($result_menu)) {
                  if ($curr_group !== $menu['menu_group']) {
                      $curr_group = $menu['menu_group'];
                      echo '<li class="menu-header small text-uppercase"><span class="menu-header-text">'.htmlspecialchars($curr_group).'</span></li>';
                  }

                  // Determine Active state
                  $is_current_plugin = (strpos($menu['menu_link'], 'rehab_device_tracker') !== false);
                  $active_cls = $is_current_plugin ? 'active' : '';

                  // Link path resolver:
                  // If link starts with 'plugins/rehab_device_tracker', stay in index.php
                  // Otherwise, link back to system root '../../'
                  if ($is_current_plugin) {
                      $link_href = "index.php";
                  } else {
                      $link_href = "../../" . ltrim($menu['menu_link'], '/');
                  }
                  ?>
                  <li class="menu-item <?= $active_cls ?>">
                    <a href="<?= $link_href ?>" class="menu-link">
                      <i class="menu-icon" style="font-style: normal;"><?= $menu['menu_icon'] ?></i>
                      <div><?= htmlspecialchars($menu['menu_name']) ?></div>
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

        <!-- Navbar (Topbar) -->
        <nav class="layout-navbar container-fluid navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
          <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
              <i class="bx bx-menu bx-sm"></i>
            </a>
          </div>

          <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
            <div class="navbar-nav align-items-center">
              <div class="nav-item d-flex align-items-center">
                <i class="bx bx-accessibility text-primary fs-4 me-2"></i>
                <span class="fw-bold text-dark fs-6 d-none d-md-inline"> ปลั๊กอิน "ติดตามการเบิกชดเชยอุปกรณ์และเวชกรรมฟื้นฟู" (Rehab & Medical Device Tracker) พัฒนาขึ้นมาเพื่อเป็น ศูนย์กลางควบคุม ตรวจสอบ และบริหารจัดการรายได้ ของโรงพยาบาล</span>
              </div>
            </div>

          </div>
        </nav>
        <!-- / Navbar -->

        <!-- Content wrapper -->
        <div class="content-wrapper">
          <div class="container-fluid flex-grow-1 container-p-y">

            <!-- Top Action & Filter Panel -->
            <div class="filter-panel p-3 mb-4">
              <div class="row g-2 align-items-center">
                <!-- Year Filter -->
                <div class="col-6 col-sm-4 col-md-2">
                  <label class="form-label small fw-bold text-dark mb-1"><i class="bx bx-calendar-star me-1 text-primary"></i>ปีงบประมาณ</label>
                  <select class="form-select form-select-sm text-dark fw-bold" id="filter_fiscal_year">
                    <option value="ALL">ข้อมูลทั้งหมด (All Time)</option>
                    <?php for ($fy = $default_fiscal_year; $fy >= $default_fiscal_year - 6; $fy--): ?>
                      <option value="<?= $fy ?>" <?= ($fy == $default_fiscal_year ? 'selected' : '') ?>>
                        ปีงบประมาณ <?= $fy ?> <?= ($fy == $default_fiscal_year ? '(ปีปัจจุบัน)' : '') ?>
                      </option>
                    <?php endfor; ?>
                  </select>
                </div>

                <!-- Fund Filter -->
                <div class="col-6 col-sm-4 col-md-2">
                  <label class="form-label small fw-bold text-dark mb-1"><i class="bx bx-shield me-1 text-primary"></i>สิทธิ / กองทุน</label>
                  <select class="form-select form-select-sm text-dark fw-bold" id="filter_fund">
                    <option value="ALL">ทุกสิทธิ / ทุกกองทุน</option>
                    <option value="UC">สปสช. (บัตรทอง / ท.74)</option>
                    <option value="OFC">เบิกจ่ายตรง (ข้าราชการ)</option>
                    <option value="LGO">เบิกจ่ายตรง (อปท.)</option>
                    <option value="SSS">ประกันสังคม</option>
                    <option value="OTHER">พรบ. / อื่นๆ</option>
                  </select>
                </div>

                <!-- Department Filter (New) -->
                <div class="col-6 col-sm-4 col-md-2">
                  <label class="form-label small fw-bold text-dark mb-1"><i class="bx bx-buildings me-1 text-info"></i>กลุ่มงาน / แผนก</label>
                  <select class="form-select form-select-sm text-dark fw-bold" id="filter_department">
                    <option value="ALL">ทุกกลุ่มงาน / แผนก</option>
                    <option value="กายภาพบำบัด">กายภาพบำบัด</option>
                    <option value="ผู้ป่วยนอก (OPD)">ผู้ป่วยนอก (OPD)</option>
                    <option value="ทันตกรรม">ทันตกรรม</option>
                    <option value="อุบัติเหตุและฉุกเฉิน (ER)">ฉุกเฉิน (ER)</option>
                    <option value="ผู้ป่วยใน (IPD)">ผู้ป่วยใน (IPD)</option>
                    <option value="แพทย์แผนไทย">แพทย์แผนไทย</option>
                    <option value="อื่นๆ">อื่นๆ / แผนกสนับสนุน</option>
                  </select>
                </div>

                <!-- Date Range (Thai Calendar) -->
                <div class="col-6 col-sm-6 col-md-2">
                  <label class="form-label small fw-bold text-dark mb-1"><i class="bx bx-calendar me-1 text-primary"></i>ตั้งแต่วันที่</label>
                  <input type="text" class="form-control form-control-sm thai-datepicker text-dark fw-bold bg-white" id="filter_start_date" value="<?= $default_start_date ?>" placeholder="วว/ดด/ปปปป" readonly>
                </div>
                <div class="col-6 col-sm-6 col-md-2">
                  <label class="form-label small fw-bold text-dark mb-1"><i class="bx bx-calendar me-1 text-primary"></i>ถึงวันที่</label>
                  <input type="text" class="form-control form-control-sm thai-datepicker text-dark fw-bold bg-white" id="filter_end_date" value="<?= $default_end_date ?>" placeholder="วว/ดด/ปปปป" readonly>
                </div>

                <!-- Action Buttons -->
                <div class="col-12 col-md-2 text-md-end mt-2 mt-md-auto d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                  <button type="button" class="btn btn-sm btn-info shadow-sm text-white" id="btnOpenDataAuditModal" onclick="openDataAuditModal()" title="เปิดหน้าต่างตรวจสอบความถูกต้อง Data Audit Checklist">
                    <i class="bx bx-check-shield me-1"></i> Data Audit
                  </button>
                  <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btnAutoAudit" title="ดึงข้อมูลจาก HOSxP และ Audit ชนยอด Statement ใน eDHS ทันที">
                    <i class="bx bx-refresh me-1"></i> ดึง & Audit
                  </button>
                  <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="เครื่องมือเพิ่มเติม">
                      <i class="bx bx-cog me-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="openHistoricalAuditModal()">
                          <i class="bx bx-history me-2 text-primary"></i> รัน Audit ย้อนหลังตามปีงบประมาณ
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);" id="btnExportDeptExcelDrop">
                          <i class="bx bx-buildings me-2 text-info"></i> ส่งออก Excel รายงานแยกกลุ่มงาน
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);" id="btnExportRightsExcelDrop">
                          <i class="bx bx-spreadsheet me-2 text-success"></i> ส่งออก Excel รายงานตามสิทธิ
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);" id="btnOpenDictionaryModal">
                          <i class="bx bx-book-content me-2 text-info"></i> พจนานุกรมรหัสปฏิเสธ (Denial Dict)
                        </a>
                      </li>
                    </ul>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-success" id="btnExportExcel" title="ส่งออกรายงานเป็น Excel">
                    <i class="bx bx-download me-1"></i> Excel
                  </button>
                </div>
              </div>

              <!-- Automated Sync & Audit Status Banner (Auto-Sync on Load) -->
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 pt-2 border-top border-1 border-light">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-label-primary px-3 py-2 d-inline-flex align-items-center gap-1 rounded-pill" id="badgeSyncStatus">
                    <i class="bx bx-loader-alt bx-spin" id="iconSyncStatus"></i>
                    <span id="textSyncStatus">กำลังเตรียมข้อมูลและ Audit อัตโนมัติในพื้นหลัง...</span>
                  </span>
                  <small class="text-muted d-none d-md-inline" id="textSyncDetail">สแกน HOSxP & ตรวจสอบชนยอด Statement eDHS</small>
                  <button type="button" class="btn btn-xs btn-outline-primary d-none ms-1" id="btnBannerViewAudit" onclick="openDataAuditModal()">
                    <i class="bx bx-check-shield me-1"></i> ดูผล Audit
                  </button>
                </div>
                <div class="text-muted small d-flex align-items-center gap-2">
                  <span class="badge bg-label-info"><i class="bx bx-check-shield text-info me-1"></i>Auto-Audit Active</span>
                  <span class="text-secondary small d-none d-lg-inline">โหลดเฉพาะปีงบประมาณปัจจุบันเพื่อความรวดเร็ว</span>
                </div>
              </div>
            </div>

            <!-- Main Tabs Header (4 Tabs) -->
            <ul class="nav nav-tabs mb-4" id="rehabTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-dashboard-btn" data-bs-toggle="tab" data-bs-target="#tab-dashboard" type="button" role="tab">
                  <i class="bx bx-bar-chart-alt-2 me-1"></i> แดชบอร์ดภาพรวมและกราฟ
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-claims-btn" data-bs-toggle="tab" data-bs-target="#tab-claims" type="button" role="tab">
                  <i class="bx bx-list-check me-1"></i> สถานะการเบิกและรายชื่อผู้ป่วย
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-rights-btn" data-bs-toggle="tab" data-bs-target="#tab-rights" type="button" role="tab">
                  <i class="bx bx-spreadsheet me-1"></i> รายงานสรุปแยกตามสิทธิการรักษา
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-department-btn" data-bs-toggle="tab" data-bs-target="#tab-department" type="button" role="tab">
                  <i class="bx bx-buildings me-1 text-info"></i> รายงานแยกกลุ่มงาน/แผนก
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-presentation-btn" data-bs-toggle="tab" data-bs-target="#tab-presentation" type="button" role="tab">
                  <i class="bx bx-slideshow me-1 text-danger"></i> สรุปภาพรวม
                </button>
              </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content p-0" id="rehabTabsContent">

              <!-- ========================================== -->
              <!-- TAB 1: EXECUTIVE DASHBOARD & ANALYTICS     -->
              <!-- ========================================== -->
              <div class="tab-pane fade show active" id="tab-dashboard" role="tabpanel">

                <!-- Row 1: KPI Summary Cards (สอดคล้องกับภาพ S__126656549_0.jpg) -->
                <div class="row g-3 mb-4">
                  <!-- Card: จำนวนเคส / คน -->
                  <div class="col-6 col-md-4 col-xl-2">
                    <div class="card kpi-card bg-white p-3 h-100">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-dark small fw-bold">ผู้ป่วยรับอุปกรณ์</span>
                        <div class="avatar avatar-sm bg-label-primary rounded"><i class="bx bx-user fs-4 text-primary"></i></div>
                      </div>
                      <h4 class="mb-0 fw-bold text-dark" id="kpi_total_patients">0 คน</h4>
                      <small class="text-dark fw-bold"><span id="kpi_total_cases" class="fw-bold">0</span> ครั้งการจ่าย</small>
                    </div>
                  </div>

                  <!-- Card: เคลมได้แล้ว -->
                  <div class="col-6 col-md-4 col-xl-2">
                    <div class="card kpi-card bg-white p-3 h-100">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-success small fw-bold">เคลมได้แล้ว</span>
                        <div class="avatar avatar-sm bg-label-success rounded"><i class="bx bx-check-circle fs-4 text-success"></i></div>
                      </div>
                      <h4 class="mb-0 fw-bold text-success" id="kpi_paid_amount">฿0</h4>
                      <small class="text-dark fw-bold"><span id="kpi_paid_count" class="fw-bold">0</span> ครั้ง</small>
                    </div>
                  </div>

                  <!-- Card: รอผล STM -->
                  <div class="col-6 col-md-4 col-xl-2">
                    <div class="card kpi-card bg-white p-3 h-100">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-dark small fw-bold">รอผล Statement</span>
                        <div class="avatar avatar-sm bg-label-warning rounded"><i class="bx bx-time fs-4 text-warning"></i></div>
                      </div>
                      <h4 class="mb-0 fw-bold text-warning" id="kpi_wait_stm_amount">฿0</h4>
                      <small class="text-dark fw-bold"><span id="kpi_wait_stm_count" class="fw-bold">0</span> ครั้ง</small>
                    </div>
                  </div>

                  <!-- Card: เคลมไม่ผ่าน -->
                  <div class="col-6 col-md-4 col-xl-2">
                    <div class="card kpi-card bg-white p-3 h-100">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-danger small fw-bold">เคลมไม่ผ่าน</span>
                        <div class="avatar avatar-sm bg-label-danger rounded"><i class="bx bx-x-circle fs-4 text-danger"></i></div>
                      </div>
                      <h4 class="mb-0 fw-bold text-danger" id="kpi_rejected_amount">฿0</h4>
                      <small class="text-dark fw-bold"><span id="kpi_rejected_count" class="fw-bold">0</span> ครั้ง</small>
                    </div>
                  </div>

                  <!-- Card: ยังไม่เคลม / รอผลตรวจสอบ -->
                  <div class="col-6 col-md-4 col-xl-2">
                    <div class="card kpi-card bg-white p-3 h-100">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-dark small fw-bold">รอผลตรวจสอบ</span>
                        <div class="avatar avatar-sm bg-label-secondary rounded"><i class="bx bx-minus-circle fs-4 text-dark"></i></div>
                      </div>
                      <h4 class="mb-0 fw-bold text-dark" id="kpi_unclaimed_amount">฿0</h4>
                      <small class="text-dark fw-bold"><span id="kpi_unclaimed_count" class="fw-bold">0</span> ครั้ง</small>
                    </div>
                  </div>

                  <!-- Card: ต้องตามเร่งด่วน -->
                  <div class="col-6 col-md-4 col-xl-2">
                    <div class="card kpi-card bg-white p-3 h-100 border-danger-subtle">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-danger small fw-bold">ยอดตามเร่งด่วน</span>
                        <div class="avatar avatar-sm bg-danger text-white rounded"><i class="bx bx-bell fs-4"></i></div>
                      </div>
                      <h4 class="mb-0 fw-bold text-danger" id="kpi_urgent_amount">฿0</h4>
                      <small class="text-dark fw-bold"><span id="kpi_urgent_count" class="fw-bold">0</span> รายการที่ติดค้าง</small>
                    </div>
                  </div>
                </div>

                <!-- Row 1.5: Success Rate Metrics Banner -->
                <div class="card bg-white border p-3 mb-4 shadow-sm">
                  <div class="row align-items-center g-3">
                    <div class="col-md-6">
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-md bg-label-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                          <i class="bx bx-line-chart fs-3 text-primary"></i>
                        </div>
                        <div>
                          <h6 class="mb-0 fw-bold text-dark">ประสิทธิภาพการชดเชยอุปกรณ์การแพทย์ (Compensation Success Rates)</h6>
                          <small class="text-dark fw-bold">ยอดเรียกเก็บรวมทั้งสิ้น: <strong id="kpi_total_claim_val" class="text-dark">฿0</strong></small>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="row g-2 text-center text-md-end">
                        <div class="col-6">
                          <small class="text-dark fw-bold d-block">% เคลมสำเร็จ (ตามจำนวนครั้ง)</small>
                          <span class="fs-4 fw-bold text-success" id="kpi_rate_count">0%</span>
                        </div>
                        <div class="col-6">
                          <small class="text-dark fw-bold d-block">% เคลมสำเร็จ (ตามจำนวนเงิน)</small>
                          <span class="fs-4 fw-bold text-primary" id="kpi_rate_amount">0%</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Row 2: Top 3 Highest Value Equipment Cards (สอดคล้องกับภาพ S__126656600.jpg) -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <h6 class="fw-bold text-dark mb-0"><i class="bx bx-trophy text-warning me-1"></i> 3 อันดับอุปกรณ์ที่มีมูลค่าการเรียกเก็บสูงสุด (Top 3 Medical Devices)</h6>
                  <small class="text-dark fw-bold">ข้อมูล <?= htmlspecialchars($hospital_name) ?></small>
                </div>
                <div class="row g-3 mb-4" id="top3_container">
                  <!-- Dynamic Hero Cards will be rendered by JS -->
                  <div class="col-12 text-center py-3 text-dark fw-bold">กำลังโหลดข้อมูล...</div>
                </div>

                <!-- Row 3: Visual Analytics Charts (สอดคล้องกับภาพ S__126656550_0.jpg) -->
                <div class="row g-3 mb-4">
                  <!-- Donut Chart: สัดส่วนสถานะการเคลม -->
                  <div class="col-12 col-lg-5">
                    <div class="card h-100 p-3 border shadow-sm">
                      <h6 class="card-title fw-bold text-dark mb-3"><i class="bx bx-pie-chart-alt-2 me-1 text-primary"></i> สัดส่วนสถานะการเคลม (Status Distribution)</h6>
                      <div id="chartStatusDonut" style="min-height: 280px;"></div>
                    </div>
                  </div>

                  <!-- Aging Bar: ระยะเวลารอ Statement -->
                  <div class="col-12 col-lg-7">
                    <div class="card h-100 p-3 border shadow-sm">
                      <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="card-title fw-bold text-dark mb-0"><i class="bx bx-time-five me-1 text-warning"></i> วิเคราะห์ระยะเวลารอ STM (Aging Duration)</h6>
                        <small class="text-muted">นับจากวันจ่ายอุปกรณ์ถึงปัจจุบัน</small>
                      </div>
                      <div id="chartAgingBar" style="min-height: 280px;"></div>
                    </div>
                  </div>
                </div>

                <!-- Row 4: Monthly Trend Stacked Bar Chart -->
                <div class="card p-3 border mb-4 shadow-sm">
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="card-title fw-bold text-dark mb-0"><i class="bx bx-bar-chart-alt me-1 text-info"></i> แนวโน้มการจ่ายอุปกรณ์และผลการชดเชยรายเดือน (Monthly Ingestion & Claim Trends)</h6>
                    <small class="text-muted">ย้อนหลัง 12 รอบเดือน</small>
                  </div>
                  <div id="chartMonthlyTrend" style="min-height: 300px;"></div>
                </div>

                <!-- Row 5: All Devices Summary Table -->
                <div class="card border shadow-sm">
                  <div class="card-header py-3 px-3 d-flex align-items-center justify-content-between border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="bx bx-table me-1 text-primary"></i> รายการสรุปยอดชดเชยแยกตามอุปกรณ์ทางการแพทย์ทั้งหมด (All Equipment Summary)</h6>
                    <button type="button" class="btn btn-xs btn-outline-success" id="btnExportDeviceSummary">
                      <i class="bx bx-download me-1"></i> ดาวน์โหลดตารางนี้
                    </button>
                  </div>
                  <div class="table-responsive text-nowrap">
                    <table class="table table-hover table-rehab mb-0" id="tableDeviceSummary">
                      <thead>
                        <tr>
                          <th>รหัส ADP</th>
                          <th>ชื่ออุปกรณ์ทางการแพทย์</th>
                          <th class="text-end">จำนวน (ครั้ง)</th>
                          <th class="text-end">มูลค่าเรียกเก็บ (บาท)</th>
                          <th class="text-end">ชดเชยแล้ว (บาท)</th>
                          <th class="text-end">ส่วนต่างค้างรับ</th>
                          <th class="text-end">% ความสำเร็จ</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyDeviceSummary">
                        <tr><td colspan="7" class="text-center py-4 text-muted">กำลังโหลดข้อมูลอุปกรณ์...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>

              <!-- ========================================== -->
              <!-- TAB 2: CLAIM STATUS & PATIENT DRILL-DOWN   -->
              <!-- ========================================== -->
              <div class="tab-pane fade" id="tab-claims" role="tabpanel">
                <div class="card border shadow-sm">
                  <div class="card-header p-3 border-bottom">
                    <div class="row g-2 align-items-center">
                      <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                          <span class="input-group-text"><i class="bx bx-search"></i></span>
                          <input type="text" class="form-control" id="search_claims" placeholder="ค้นหา HN, CID, ชื่อผู้ป่วย, หรือ VN...">
                        </div>
                      </div>
                      <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm" id="filter_claim_status">
                          <option value="ALL">ทุกสถานะ</option>
                          <option value="PAID">เคลมผ่าน / ชดเชยแล้ว</option>
                          <option value="WAIT_STM">รอผล Statement</option>
                          <option value="REJECTED">ปฏิเสธ / เคลมไม่ผ่าน</option>
                          <option value="UNCLAIMED">รอผลตรวจสอบ</option>
                        </select>
                      </div>
                      <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm" id="filter_device_type">
                          <option value="ALL">ทุกชนิดอุปกรณ์</option>
                        </select>
                      </div>
                      <div class="col-12 col-md-2 text-md-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btnRefreshClaims">
                          <i class="bx bx-refresh me-1"></i> โหลดข้อมูล
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="table-responsive text-nowrap" style="min-height: 380px;">
                    <table class="table table-hover table-rehab mb-0" id="tableClaimsList">
                      <thead>
                        <tr>
                          <th>วันที่</th>
                          <th>HN</th>
                          <th>ชื่อ-สกุล ผู้ป่วย</th>
                          <th>สิทธิ/กองทุน</th>
                          <th>อุปกรณ์ที่ได้รับ</th>
                          <th class="text-end">จำนวน</th>
                          <th class="text-end">ยอดเรียกเก็บ</th>
                          <th class="text-end">ชดเชยแล้ว</th>
                          <th class="text-end">ส่วนต่าง</th>
                          <th class="text-center">สถานะ</th>
                          <th class="text-center">จัดการ</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyClaimsList">
                        <tr><td colspan="11" class="text-center py-5 text-muted">กำลังโหลดรายชื่อผู้ป่วย...</td></tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="card-footer py-2 px-3 d-flex align-items-center justify-content-between border-top bg-light">
                    <small class="text-muted" id="claims_count_label">แสดง 0 รายการ</small>
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-secondary" id="btnClaimsPrev" disabled><i class="bx bx-chevron-left"></i> ก่อนหน้า</button>
                      <button type="button" class="btn btn-outline-secondary" id="btnClaimsNext">ถัดไป <i class="bx bx-chevron-right"></i></button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================== -->
              <!-- TAB: RIGHTS & FUNDS REPORT (รายงานตามสิทธิ) -->
              <!-- ========================================== -->
              <div class="tab-pane fade" id="tab-rights" role="tabpanel">

                <!-- Header Actions Card -->
                <div class="card border shadow-sm mb-4">
                  <div class="card-header py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom bg-light">
                    <div>
                      <h6 class="fw-bold text-dark mb-0"><i class="bx bx-spreadsheet me-1 text-primary"></i> รายงานสรุปการเบิกชดเชยอุปกรณ์และเวชกรรมฟื้นฟู แยกตามสิทธิการรักษา</h6>
                      <small class="text-muted">จำแนกตาม 5 กลุ่มสิทธิหลัก (สปสช. บัตรทอง/ท.74 คนพิการ, OFC ข้าราชการ, LGO อปท., SSS ประกันสังคม, พรบ./อื่นๆ)</small>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-sm btn-success" id="btnExportRightsExcel">
                        <i class="bx bx-download me-1"></i> ส่งออก Excel รายงานตามสิทธิ
                      </button>
                    </div>
                  </div>

                  <!-- Section 1: Summary by 6 Primary Rights Groups -->
                  <div class="p-3 border-bottom">
                    <h6 class="fw-bold text-primary mb-2"><i class="bx bx-pie-chart-alt-2 me-1"></i> 1. สรุปภาพรวมตามกลุ่มสิทธิการรักษาหลัก (Primary Rights Summary)</h6>
                    <div class="table-responsive text-nowrap">
                      <table class="table table-sm table-bordered table-hover mb-0" id="tableRightsGroups">
                        <thead class="table-primary text-center">
                          <tr>
                            <th style="width: 5%;">ลำดับ</th>
                            <th style="width: 25%;" class="text-start">กลุ่มสิทธิการรักษา</th>
                            <th style="width: 10%;" class="text-end">ผู้ป่วย (คน)</th>
                            <th style="width: 10%;" class="text-end">จำนวนครั้ง</th>
                            <th style="width: 10%;" class="text-end">จำนวนชิ้น</th>
                            <th style="width: 12%;" class="text-end">ยอดเรียกเก็บ (บาท)</th>
                            <th style="width: 12%;" class="text-end">ชดเชยแล้วจริง (บาท)</th>
                            <th style="width: 12%;" class="text-end">ค้างชดเชย (บาท)</th>
                            <th style="width: 8%;" class="text-end">% สำเร็จ</th>
                            <th style="width: 8%;" class="text-center">ดูรายชื่อ</th>
                          </tr>
                        </thead>
                        <tbody id="tbodyRightsGroups">
                          <tr><td colspan="10" class="text-center py-3 text-muted">กำลังประมวลผลข้อมูลสิทธิการรักษา...</td></tr>
                        </tbody>
                        <tfoot class="table-light fw-bold" id="tfootRightsGroups"></tfoot>
                      </table>
                    </div>
                  </div>

                  <!-- Section 2: Detailed Breakdown by Pttype -->
                  <div class="p-3">
                    <h6 class="fw-bold text-dark mb-2"><i class="bx bx-list-ul me-1"></i> 2. แจกแจงรายละเอียดรายรหัสสิทธิการรักษาในระบบ HOSxP (Sub-Rights Breakdown)</h6>
                    <div class="table-responsive text-nowrap" style="max-height: 400px;">
                      <table class="table table-sm table-hover table-bordered mb-0" id="tableRightsDetail">
                        <thead class="table-light text-center">
                          <tr>
                            <th>ลำดับ</th>
                            <th>กลุ่มหลัก</th>
                            <th>รหัสสิทธิ</th>
                            <th class="text-start">ชื่อสิทธิการรักษา (HOSxP)</th>
                            <th class="text-end">ผู้ป่วย (คน)</th>
                            <th class="text-end">จำนวนครั้ง</th>
                            <th class="text-end">จำนวนชิ้น</th>
                            <th class="text-end">ยอดเรียกเก็บ (บาท)</th>
                            <th class="text-end">ชดเชยแล้ว (บาท)</th>
                            <th class="text-end">ค้างชดเชย (บาท)</th>
                            <th class="text-center">เจาะลึก</th>
                          </tr>
                        </thead>
                        <tbody id="tbodyRightsDetail">
                          <tr><td colspan="11" class="text-center py-3 text-muted">กำลังโหลดรายละเอียดสิทธิ...</td></tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>

              </div>

              <!-- ========================================== -->
              <!-- TAB: DEPARTMENT REPORT (รายงานแยกกลุ่มงาน/แผนก) -->
              <!-- ========================================== -->
              <?php include __DIR__ . '/tab_department_report.php'; ?>

              <!-- ========================================== -->
              <!-- TAB: EXECUTIVE PRESENTATION SLIDES         -->
              <!-- ========================================== -->
              <?php include __DIR__ . '/tab_presentation_slides.php'; ?>

            </div>
            <!-- / Tabs Content -->

          </div>
          <!-- / Container -->

          <!-- Footer -->
          <footer class="content-footer footer bg-footer-theme">
            <div class="container-fluid d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
              <div class="mb-2 mb-md-0 text-muted small">
                © <?= date('Y') ?> eDebtor Hospital System (eDHS) • ปลั๊กอินติดตามการเบิกชดเชยอุปกรณ์และเวชกรรมฟื้นฟู v1.0 • <?= htmlspecialchars($hospital_name) ?>
              </div>
            </div>
          </footer>
        </div>
        <!-- / Content wrapper -->

      </div>
      <!-- / Layout container -->
    </div>
  </div>
  <!-- / Layout wrapper -->

  <?php include __DIR__ . '/modal_claim_detail.php'; ?>
  <?php include __DIR__ . '/modal_denial_guide.php'; ?>
  <?php include __DIR__ . '/modal_device_patients.php'; ?>
  <?php include __DIR__ . '/modal_data_audit.php'; ?>

  <!-- Modal: Import STM / REP File -->
  <div class="modal fade" id="modalImportStm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-primary text-white py-3">
          <h5 class="modal-title text-white"><i class="bx bx-upload me-2"></i> นำเข้าไฟล์ Statement (STM/REP)</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form id="formImportStm" enctype="multipart/form-data">
          <div class="modal-body p-4">
            <p class="text-muted small mb-3">
              รองรับไฟล์ Statement (.xlsx, .xls, .csv) จาก 4 กองทุน: สปสช. (UC), เบิกจ่ายตรง (CSMBS), อปท. (LGO), และ ประกันสังคม (SSS)
            </p>
            <div class="mb-3">
              <label for="stm_file" class="form-label fw-bold small text-dark">เลือกไฟล์ Statement:</label>
              <input class="form-control" type="file" id="stm_file" name="stm_file" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="alert alert-info py-2 small mb-0">
              <i class="bx bx-info-circle me-1"></i> ระบบจะทำการอ่านเลข VN, ยอดชดเชย, และรหัสข้อผิดพลาดเพื่อจับคู่กับรายการในระบบให้อัตโนมัติ
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary" id="btnSubmitStm">
              <i class="bx bx-cloud-upload me-1"></i> นำเข้าและประมวลผล
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Denial Dictionary Admin -->
  <div class="modal fade" id="modalDictionary" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-dark text-white py-3">
          <h5 class="modal-title text-white d-flex align-items-center">
            <i class="bx bx-book-bookmark me-2 fs-4 text-warning"></i> 
            พจนานุกรมรหัส สปสช. (C / Deny / Verify 933 รหัส) & คู่มือแนวทางแก้ไข
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">

          <!-- Search & Filter Controls -->
          <div class="card border mb-3 shadow-sm">
            <div class="card-body p-3 bg-light">
              <div class="row g-2 align-items-center">
                <div class="col-md-5">
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bx bx-search text-primary"></i></span>
                    <input type="text" class="form-control text-dark fw-bold" id="dict_search_input" placeholder="พิมพ์ค้นหารหัส (เช่น 246, 247, V048, 300) หรือค้นหาคำอธิบาย...">
                  </div>
                </div>
                <div class="col-md-4">
                  <select class="form-select text-dark fw-bold" id="dict_type_filter">
                    <option value="ALL">ทุกประเภท (แสดงทั้งหมด 942 รหัส)</option>
                    <option value="Corrective">Corrective (ติด C - แก้ไขเวชระเบียนส่งใหม่ได้ 641 รหัส)</option>
                    <option value="Verify">Verify (ติด V - รอส่งเอกสารหลักฐาน 117 รหัส)</option>
                    <option value="Deny">Deny (ติด D - ปฏิเสธจ่ายชดเชย 175 รหัส)</option>
                  </select>
                </div>
                <div class="col-md-3 text-md-end">
                  <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm" id="dict_total_badge">942 รายการ</span>
                  <button type="button" class="btn btn-sm btn-outline-secondary ms-1" data-bs-toggle="collapse" data-bs-target="#collapseAddDict" title="เพิ่มรหัสใหม่">
                    <i class="bx bx-plus"></i> เพิ่มรหัส
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Add / Edit Form (Collapsible) -->
          <div class="collapse mb-3" id="collapseAddDict">
            <div class="card border bg-white shadow-sm">
              <div class="card-header py-2 px-3 fw-bold small text-dark border-bottom bg-light">
                <i class="bx bx-plus-circle me-1 text-primary"></i> เพิ่ม / แก้ไข รหัสในพจนานุกรม
              </div>
              <div class="card-body p-3">
                <form id="formDictionaryItem">
                  <div class="row g-2 mb-2">
                    <div class="col-md-2">
                      <label class="form-label small fw-bold text-dark mb-1">รหัส (เช่น 246, V048)</label>
                      <input type="text" class="form-control form-control-sm text-dark fw-bold" name="denial_code" id="dict_denial_code" required>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small fw-bold text-dark mb-1">ประเภท</label>
                      <select class="form-select form-select-sm text-dark fw-bold" name="deny_type" id="dict_deny_type">
                        <option value="Corrective">Corrective (แก้ไขได้)</option>
                        <option value="Verify">Verify (ขอหลักฐาน)</option>
                        <option value="Deny">Deny (ปฏิเสธ)</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small fw-bold text-dark mb-1">กองทุน</label>
                      <select class="form-select form-select-sm text-dark fw-bold" name="fund_type" id="dict_fund_type">
                        <option value="ALL">ทุกกองทุน (ALL)</option>
                        <option value="UC">สปสช. (UC)</option>
                        <option value="OFC">เบิกจ่ายตรง (OFC)</option>
                        <option value="LGO">อปท. (LGO)</option>
                        <option value="SSS">ประกันสังคม (SSS)</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small fw-bold text-dark mb-1">ระดับการแก้ไข</label>
                      <select class="form-select form-select-sm text-dark fw-bold" name="severity" id="dict_severity">
                        <option value="RETRY">แก้ไขส่งใหม่ได้ (RETRY)</option>
                        <option value="APPEAL">ต้องยื่นอุทธรณ์ (APPEAL)</option>
                        <option value="UNCOLLECTIBLE">ตัดสูญ/ไม่สามารถเรียกเก็บได้</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-bold text-dark mb-1">คำอธิบายภาษาไทย</label>
                      <input type="text" class="form-control form-control-sm text-dark fw-bold" name="description_th" id="dict_description_th" required>
                    </div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-md-6">
                      <label class="form-label small fw-bold text-dark mb-1">สาเหตุที่พบบ่อย (Root causes)</label>
                      <textarea class="form-control form-control-sm text-dark" name="common_causes" id="dict_common_causes" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-bold text-dark mb-1">ขั้นตอนการแก้ไขเพื่อส่งใหม่ (Actionable Guide)</label>
                      <textarea class="form-control form-control-sm text-dark" name="action_guide" id="dict_action_guide" rows="2"></textarea>
                    </div>
                  </div>
                  <div class="text-end">
                    <button type="submit" class="btn btn-sm btn-primary">
                      <i class="bx bx-save me-1"></i> บันทึกเข้าพจนานุกรม
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- Dictionary Table -->
          <div class="table-responsive text-nowrap border rounded" style="max-height: 480px; overflow-y: auto;">
            <table class="table table-sm table-hover table-bordered mb-0" id="tableDictionaryList">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width: 10%;">ประเภท</th>
                  <th style="width: 10%;">รหัส</th>
                  <th style="width: 35%;">คำอธิบายข้อผิดพลาด (สปสช.)</th>
                  <th style="width: 35%;">แนวทางแก้ไข (Action Guide)</th>
                  <th style="width: 10%;" class="text-center">จัดการ</th>
                </tr>
              </thead>
              <tbody id="tbodyDictionaryList">
                <tr><td colspan="5" class="text-center py-4 text-dark fw-bold"><i class="bx bx-loader-alt bx-spin me-2"></i> กำลังโหลดพจนานุกรม 942 รหัส...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Core JS Scripts -->
  <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../../../assets/vendor/js/bootstrap.js"></script>
  <script src="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="../../../assets/vendor/libs/apex-charts/apexcharts.js"></script>

  <!-- Plugin Controller Logic -->
  <script>
    // State Variables
    let currentKpi = {};
    let statusChartInstance = null;
    let agingChartInstance = null;
    let trendChartInstance = null;
    let denialChartInstance = null;
    let currentDenialCategory = 'ALL';
    let currentRightGroup = 'ALL';
    let currentPttype = 'ALL';
    let currentDepartmentGroup = 'ALL';
    let currentDeptReportRaw = null;
    let deptDonutChartInstance = null;
    let deptBarChartInstance = null;
    let claimsPage = 0;
    const claimsPageSize = 50;

    // Thai Datepicker Instances & Helpers
    let startDatePicker = null;
    let endDatePicker = null;
    let currentDevicePatientsRaw = [];

    function replaceYearToThai(fpInstance) {
      if (fpInstance && fpInstance.currentYearElement) {
        fpInstance.currentYearElement.value = fpInstance.currentYear + 543;
      }
    }

    function formatThaiDate(dateStr) {
      if (!dateStr || dateStr === '0000-00-00') return '-';
      const parts = String(dateStr).split('-');
      if (parts.length !== 3) return dateStr;
      const y = parseInt(parts[0], 10);
      const m = parseInt(parts[1], 10);
      const d = parseInt(parts[2], 10);
      if (isNaN(y) || isNaN(m) || isNaN(d)) return dateStr;
      const thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
      const beYear = y + 543;
      return `${d} ${thaiMonths[m] || ''} ${beYear}`;
    }

    function initThaiDatepickers() {
      if (typeof flatpickr === 'undefined') return;

      const commonConfig = {
        locale: 'th',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'j F Y',
        disableMobile: true,
        onReady: function(selectedDates, dateStr, instance) {
          replaceYearToThai(instance);
          if (instance.altInput) {
            instance.altInput.setAttribute("type", "search");
            instance.altInput.setAttribute("autocomplete", "new-password");
            instance.altInput.setAttribute("data-lpignore", "true");
          }
          if (selectedDates[0] && instance.altInput) {
            instance.altInput.value = `${selectedDates[0].getDate()} ${instance.l10n.months.longhand[selectedDates[0].getMonth()]} ${selectedDates[0].getFullYear() + 543}`;
          }
        },
        onValueUpdate: function(selectedDates, dateStr, instance) {
          replaceYearToThai(instance);
          if (selectedDates[0] && instance.altInput) {
            instance.altInput.value = `${selectedDates[0].getDate()} ${instance.l10n.months.longhand[selectedDates[0].getMonth()]} ${selectedDates[0].getFullYear() + 543}`;
          }
        },
        onMonthChange: function(selectedDates, dateStr, instance) {
          replaceYearToThai(instance);
        },
        onYearChange: function(selectedDates, dateStr, instance) {
          replaceYearToThai(instance);
        },
        onOpen: function(selectedDates, dateStr, instance) {
          replaceYearToThai(instance);
        }
      };

      startDatePicker = flatpickr("#filter_start_date", Object.assign({}, commonConfig, {
        defaultDate: $("#filter_start_date").val() || '2025-10-01',
        onChange: function(selectedDates, dateStr, instance) {
          replaceYearToThai(instance);
          if (selectedDates[0] && instance.altInput) {
            instance.altInput.value = `${selectedDates[0].getDate()} ${instance.l10n.months.longhand[selectedDates[0].getMonth()]} ${selectedDates[0].getFullYear() + 543}`;
          }
          if (endDatePicker && selectedDates.length > 0) {
            endDatePicker.set('minDate', selectedDates[0]);
          }
          currentRightGroup = 'ALL';
          currentPttype = 'ALL';
          loadAllStats();
          loadClaimsList();
          loadRejectedClaims();
          queueBackgroundAutoAudit(600);
        }
      }));

      endDatePicker = flatpickr("#filter_end_date", Object.assign({}, commonConfig, {
        defaultDate: $("#filter_end_date").val() || new Date().toISOString().split('T')[0],
        onChange: function(selectedDates, dateStr, instance) {
          replaceYearToThai(instance);
          if (selectedDates[0] && instance.altInput) {
            instance.altInput.value = `${selectedDates[0].getDate()} ${instance.l10n.months.longhand[selectedDates[0].getMonth()]} ${selectedDates[0].getFullYear() + 543}`;
          }
          if (startDatePicker && selectedDates.length > 0) {
            startDatePicker.set('maxDate', selectedDates[0]);
          }
          currentRightGroup = 'ALL';
          currentPttype = 'ALL';
          loadAllStats();
          loadClaimsList();
          loadRejectedClaims();
          queueBackgroundAutoAudit(600);
        }
      }));
    }

    // Automated Background Sync & Audit State
    let isAuditSyncing = false;
    let auditDebounceTimer = null;

    function queueBackgroundAutoAudit(delayMs = 600) {
      if (auditDebounceTimer) clearTimeout(auditDebounceTimer);
      auditDebounceTimer = setTimeout(function() {
        triggerBackgroundAutoAudit(true);
      }, delayMs);
    }

    function triggerBackgroundAutoAudit(isSilent = true) {
      if (isAuditSyncing) return;
      isAuditSyncing = true;

      const s = $('#filter_start_date').val();
      const e = $('#filter_end_date').val();

      // Update status badge to loading state
      $('#badgeSyncStatus').removeClass('bg-label-success bg-label-danger bg-label-warning bg-label-secondary').addClass('bg-label-primary');
      $('#iconSyncStatus').attr('class', 'bx bx-loader-alt bx-spin');
      $('#textSyncStatus').text('กำลังเตรียมข้อมูลและ Audit อัตโนมัติในพื้นหลัง...');
      $('#textSyncDetail').html('<span class="spinner-border spinner-border-sm text-primary me-1" style="width: 12px; height: 12px;"></span>เชื่อมต่อ HOSxP & ตรวจสอบชนยอด Statement eDHS');

      if (!isSilent) {
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true
        });
        Toast.fire({
          icon: 'info',
          title: 'กำลังเชื่อมต่อ HOSxP และ Audit ชนยอด Statement ในเบื้องหลัง...'
        });
      }

      $.ajax({
        url: 'api_rehab_hos_pull.php',
        method: 'POST',
        data: { start_date: s, end_date: e, auto_match: 1 },
        dataType: 'json',
        success: function(res) {
          isAuditSyncing = false;
          if (res.status === 'success') {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });

            $('#badgeSyncStatus').removeClass('bg-label-primary bg-label-danger bg-label-warning bg-label-secondary').addClass('bg-label-success');
            $('#iconSyncStatus').attr('class', 'bx bx-check-circle text-success');
            $('#textSyncStatus').text(`ข้อมูลล่าสุดพร้อมใช้งานแล้ว (อัปเดต ${timeStr} น.)`);
            $('#textSyncDetail').html(`ดึง HOSxP <b>${Number(res.pulled_count || 0).toLocaleString()}</b> รายการ • บันทึก/อัปเดต <b>${Number(res.inserted_count || 0).toLocaleString()}</b> รายการ • ชนยอด Statement <b>${Number(res.matched_stm_count || 0).toLocaleString()}</b> รายการ • ตรวจสอบใบเสร็จ <b>${Number(res.matched_debtor_count || 0).toLocaleString()}</b> รายการ`);
            $('#btnBannerViewAudit').removeClass('d-none');

            // Silently and smoothly refresh all stats, charts, and tables with newly audited data
            loadAllStats();
            loadClaimsList();
            loadRejectedClaims();

            if (!isSilent) {
              const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
              });
              Toast.fire({
                icon: 'success',
                title: `ประมวลผล Audit สำเร็จ (${Number(res.pulled_count || 0).toLocaleString()} รายการ, ชนยอด ${Number(res.matched_stm_count || 0).toLocaleString()} รายการ)`
              });
            }
          } else {
            $('#badgeSyncStatus').removeClass('bg-label-primary bg-label-success').addClass('bg-label-warning');
            $('#iconSyncStatus').attr('class', 'bx bx-error text-warning');
            $('#textSyncStatus').text('การเตรียมข้อมูลเบื้องหลังไม่สมบูรณ์');
            $('#textSyncDetail').text(res.message || 'กรุณาลองใหม่อีกครั้ง');
          }
        },
        error: function(err) {
          isAuditSyncing = false;
          $('#badgeSyncStatus').removeClass('bg-label-primary bg-label-success').addClass('bg-label-secondary');
          $('#iconSyncStatus').attr('class', 'bx bx-info-circle text-muted');
          $('#textSyncStatus').text('ใช้ข้อมูลในระบบ eDHS ปัจจุบัน');
          $('#textSyncDetail').text('ไม่สามารถเชื่อมต่อ HOSxP ชั่วคราว');
        }
      });
    }

    $(document).ready(function() {
      // 1. Initial Thai Datepicker & Fast Local Data Render
      initThaiDatepickers();
      populateHistoricalAuditYears();
      loadAllStats();
      loadClaimsList();
      loadRejectedClaims();
      load5YearsTrendData(false);

      // 2. Automated Background Audit & Data Preparation on Load (No manual click required)
      setTimeout(function() {
        triggerBackgroundAutoAudit(true);
      }, 400);

      // 3. Filter Event Listeners
      $('#filter_fiscal_year').on('change', function() {
        const fy = $(this).val();
        let sDate, eDate;
        if (fy === 'ALL') {
          sDate = '2024-01-01';
          eDate = new Date().toISOString().split('T')[0];
        } else {
          const ce = parseInt(fy) - 543;
          sDate = (ce - 1) + '-10-01';
          eDate = ce + '-09-30';
        }
        if (startDatePicker && endDatePicker) {
          startDatePicker.setDate(sDate, true);
          endDatePicker.setDate(eDate, true);
        } else {
          $('#filter_start_date').val(sDate);
          $('#filter_end_date').val(eDate);
        }
        loadAllStats();
        loadClaimsList();
        loadRejectedClaims();
        load5YearsTrendData(false);
        queueBackgroundAutoAudit(300);
      });

      $('#filter_fund').on('change', function() {
        currentRightGroup = 'ALL';
        currentPttype = 'ALL';
        loadAllStats();
        loadClaimsList();
        loadRejectedClaims();
      });

      $('#filter_department').on('change', function() {
        currentDepartmentGroup = $(this).val();
        loadAllStats();
        loadClaimsList();
      });

      $('#sel_dept_breakdown_filter').on('change', function() {
        renderDeptItemsFiltered();
      });

      // Handle Tab: Department Report activation to ensure ApexCharts resize perfectly
      $('button#tab-department-btn').on('shown.bs.tab', function() {
        setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
        }, 50);
        if (currentDeptReportRaw) {
          renderDepartmentReport(currentDeptReportRaw);
        }
      });

      // Device Patients Modal Toolbar Listeners
      $('#mdp_search_input').on('keyup', function() {
        renderDevicePatientsTableFiltered();
      });
      $('#mdp_status_filter').on('change', function() {
        renderDevicePatientsTableFiltered();
      });

      $('#btnExportDevicePatientsExcel').on('click', function() {
        const devCode = $('#mdp_target_device_code').val();
        const fy = $('#filter_fiscal_year').val();
        const f  = $('#filter_fund').val();
        const s  = $('#filter_start_date').val();
        const e  = $('#filter_end_date').val();
        window.location.href = `export_rehab_excel.php?type=claims_list&device_code=${encodeURIComponent(devCode)}&fiscal_year=${fy}&fund_type=${f}&start_date=${s}&end_date=${e}`;
      });

      $('#btnJumpToClaimsTab').on('click', function() {
        const devCode = $('#mdp_target_device_code').val();
        $('#modalDevicePatients').modal('hide');
        $('#filter_device_type').val(devCode);
        $('#tab-claims-btn').tab('show');
        claimsPage = 0;
        loadClaimsList();
      });

      // Denial Category Filter Pills in Tab 3
      $('.btn-cat-filter').on('click', function() {
        $('.btn-cat-filter').removeClass('active btn-primary btn-warning btn-danger').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary btn-outline-warning btn-outline-danger');
        
        currentDenialCategory = $(this).data('cat');
        if (currentDenialCategory === 'C_CODE') {
          $(this).addClass('active btn-warning text-dark');
        } else if (currentDenialCategory === 'D_CODE') {
          $(this).addClass('active btn-danger');
        } else {
          $(this).addClass('active btn-primary');
        }
        loadRejectedClaims();
      });

      // 3. Tab 2 Filters
      $('#search_claims').on('keyup', function(e) {
        if (e.key === 'Enter' || $(this).val().length >= 3 || $(this).val().length === 0) {
          claimsPage = 0;
          loadClaimsList();
        }
      });
      $('#filter_claim_status, #filter_device_type').on('change', function() {
        claimsPage = 0;
        loadClaimsList();
      });
      $('#btnRefreshClaims').on('click', function() {
        loadClaimsList();
      });
      $('#btnClaimsPrev').on('click', function() {
        if (claimsPage > 0) {
          claimsPage--;
          loadClaimsList();
        }
      });
      $('#btnClaimsNext').on('click', function() {
        claimsPage++;
        loadClaimsList();
      });

      // 4. Tab 3 Toggle
      $('#chk_hide_resubmitted').on('change', function() {
        loadRejectedClaims();
      });

      // 5. Audit & Match Manual Trigger (No Modal Popup, Direct Toast & Smooth Refresh)
      $('#btnAutoAudit').on('click', function() {
        if (isAuditSyncing) {
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
          });
          Toast.fire({
            icon: 'info',
            title: 'ระบบกำลังดำเนินการประมวลผล Audit อยู่แล้วในเบื้องหลัง...'
          });
          return;
        }
        triggerBackgroundAutoAudit(false);
      });

      // Alias for legacy trigger
      $('#btnSyncHos').on('click', function() {
        $('#btnAutoAudit').trigger('click');
      });

      // 6. Open Import STM Modal
      $('#btnOpenImportStm').on('click', function() {
        $('#modalImportStm').modal('show');
      });

      // 7. Form Import STM
      $('#formImportStm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'upload_stm_file');

        Swal.fire({
          title: 'กำลังอัปโหลดไฟล์ Statement...',
          html: 'ระบบกำลังอ่านข้อมูลรอบชดเชยและจับคู่เคส',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        $.ajax({
          url: 'api_rehab_stm_match.php',
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(res) {
            if (res.status === 'success') {
              $('#modalImportStm').modal('hide');
              $('#formImportStm')[0].reset();
              Swal.fire('นำเข้าสำเร็จ!', res.message, 'success');
              loadAllStats();
              loadClaimsList();
              loadRejectedClaims();
            } else {
              Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
            }
          },
          error: function() {
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถอัปโหลดไฟล์ได้', 'error');
          }
        });
      });

      // 8. Export Excel
      $('#btnExportExcel').on('click', function() {
        const fy = $('#filter_fiscal_year').val();
        const f  = $('#filter_fund').val();
        const s  = $('#filter_start_date').val();
        const e  = $('#filter_end_date').val();
        window.location.href = `export_rehab_excel.php?type=claims_list&fiscal_year=${fy}&fund_type=${f}&start_date=${s}&end_date=${e}`;
      });
      $('#btnExportDeviceSummary').on('click', function() {
        const fy = $('#filter_fiscal_year').val();
        const f  = $('#filter_fund').val();
        const s  = $('#filter_start_date').val();
        const e  = $('#filter_end_date').val();
        window.location.href = `export_rehab_excel.php?type=summary_devices&fiscal_year=${fy}&fund_type=${f}&start_date=${s}&end_date=${e}`;
      });

      // Export Rights Report (รายงานแยกตามสิทธิการรักษา)
      $('#btnExportRightsExcel, #btnExportRightsExcelDrop').on('click', function() {
        const fy = $('#filter_fiscal_year').val();
        const f  = $('#filter_fund').val();
        const s  = $('#filter_start_date').val();
        const e  = $('#filter_end_date').val();
        window.location.href = `export_rehab_excel.php?type=rights_report&fiscal_year=${fy}&fund_type=${f}&start_date=${s}&end_date=${e}`;
      });

      // Export Department Report (รายงานแยกตามกลุ่มงาน/แผนก)
      $('#btnExportDeptExcel, #btnExportDeptExcelDrop').on('click', function() {
        const fy = $('#filter_fiscal_year').val();
        const f  = $('#filter_fund').val();
        const d  = $('#filter_department').val();
        const s  = $('#filter_start_date').val();
        const e  = $('#filter_end_date').val();
        window.location.href = `export_rehab_excel.php?type=department_report&fiscal_year=${fy}&fund_type=${f}&department_group=${encodeURIComponent(d)}&start_date=${s}&end_date=${e}`;
      });

      // 9. Open Dictionary Modal
      $(document).on('click', '#btnOpenDictionaryModal', function() {
        loadDictionaryList();
        $('#modalDictionary').modal('show');
      });

      // Search Dictionary Debounced
      let dictSearchTimer;
      $('#dict_search_input').on('keyup input', function() {
        clearTimeout(dictSearchTimer);
        dictSearchTimer = setTimeout(function() {
          loadDictionaryList();
        }, 250);
      });

      $('#dict_type_filter').on('change', function() {
        loadDictionaryList();
      });

      // 10. Save Dictionary Form
      $('#formDictionaryItem').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=save_dictionary_item';
        $.ajax({
          url: 'api_rehab_denial.php',
          method: 'POST',
          data: formData,
          dataType: 'json',
          success: function(res) {
            if (res.status === 'success') {
              Swal.fire('สำเร็จ', res.message, 'success');
              $('#formDictionaryItem')[0].reset();
              loadDictionaryList();
              loadAllStats();
            } else {
              Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
          }
        });
      });

      // 11. Save Note from Claim Detail Modal
      $('#btnSaveModalNote').on('click', function() {
        const claimId = $('#modal_claim_id').val();
        const isResub = $('#modal_is_resubmitted').is(':checked') ? 1 : 0;
        const note = $('#modal_officer_note').val();

        $.ajax({
          url: 'api_rehab_denial.php',
          method: 'POST',
          data: { action: 'mark_resubmitted', claim_id: claimId, is_resubmitted: isResub, note: note },
          dataType: 'json',
          success: function(res) {
            if (res.status === 'success') {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: res.message,
                showConfirmButton: false,
                timer: 2000
              });
              loadClaimsList();
              loadRejectedClaims();
              $('#modalClaimDetail').modal('hide');
            } else {
              Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
          }
        });
      });

      // 12. Guide Mark Resubmitted from Guide Modal
      $('#btnGuideMarkResubmitted').on('click', function() {
        const claimId = $('#guide_target_claim_id').val();
        if (!claimId) return;

        $.ajax({
          url: 'api_rehab_denial.php',
          method: 'POST',
          data: { action: 'mark_resubmitted', claim_id: claimId, is_resubmitted: 1, note: 'บันทึกส่งเบิกซ้ำตามคำแนะนำ Action Guide' },
          dataType: 'json',
          success: function(res) {
            if (res.status === 'success') {
              Swal.fire('สำเร็จ', 'บันทึกสถานะส่งเบิกซ้ำเรียบร้อย', 'success');
              $('#modalDenialGuide').modal('hide');
              loadClaimsList();
              loadRejectedClaims();
              loadAllStats();
            }
          }
        });
      });

      // 13. Open Guide from Modal Detail
      $('#btnOpenDenialGuideFromModal').on('click', function() {
        const code = $('#modal_denial_code').text();
        const claimId = $('#modal_claim_id').val();
        $('#modalClaimDetail').modal('hide');
        openDenialGuideModal(code, claimId);
      });
    });

    // ========================================================
    // DEVICE PATIENTS DRILL-DOWN MODAL FUNCTIONS
    // ========================================================

    function openDevicePatientsModal(deviceCode, rawDeviceName) {
      const deviceName = decodeURIComponent(rawDeviceName || deviceCode);
      $('#mdp_target_device_code').val(deviceCode);
      $('#mdp_target_device_name').val(deviceName);
      $('#mdp_device_badge').text(deviceCode);
      $('#mdp_device_name').text(deviceName);
      
      const s = $('#filter_start_date').val();
      const e = $('#filter_end_date').val();
      const fy = $('#filter_fiscal_year').val();
      const fyText = fy === 'ALL' ? 'ข้อมูลทั้งหมด' : 'ปีงบฯ ' + fy;
      $('#mdp_filter_subtitle').html(`<i class="bx bx-calendar-event me-1"></i> ${fyText} (${formatThaiDate(s)} - ${formatThaiDate(e)})`);

      $('#mdp_search_input').val('');
      $('#mdp_status_filter').val('ALL');
      $('#modalDevicePatients').modal('show');

      loadDevicePatientsData(deviceCode);
    }

    function loadDevicePatientsData(deviceCode) {
      const fy = $('#filter_fiscal_year').val();
      const f  = $('#filter_fund').val();
      const s  = $('#filter_start_date').val();
      const e  = $('#filter_end_date').val();

      $('#tbodyDevicePatients').html('<tr><td colspan="12" class="text-center py-5 text-dark fw-bold"><div class="spinner-border text-primary me-2" role="status"></div> กำลังโหลดรายชื่อผู้ป่วยที่ได้รับอุปกรณ์...</td></tr>');

      $.ajax({
        url: 'api_rehab_stats.php',
        method: 'GET',
        data: {
          mode: 'claims_table',
          dev_code: deviceCode,
          fiscal_year: fy,
          fund_type: f,
          start_date: s,
          end_date: e,
          offset: 0,
          limit: 1000
        },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            currentDevicePatientsRaw = res.items || [];
            updateDeviceKpiBadges(currentDevicePatientsRaw);
            renderDevicePatientsTableFiltered();
          } else {
            $('#tbodyDevicePatients').html('<tr><td colspan="12" class="text-center py-4 text-danger fw-bold">เกิดข้อผิดพลาด: ' + (res.message || 'ไม่สามารถโหลดข้อมูลได้') + '</td></tr>');
          }
        },
        error: function() {
          $('#tbodyDevicePatients').html('<tr><td colspan="12" class="text-center py-4 text-danger fw-bold">ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้</td></tr>');
        }
      });
    }

    function updateDeviceKpiBadges(items) {
      const distinctHn = new Set(items.map(i => i.hn)).size;
      const totalQty = items.reduce((acc, i) => acc + (Number(i.qty) || 0), 0);
      const totalClaim = items.reduce((acc, i) => acc + (parseFloat(i.claim_amount) || 0), 0);
      const totalComp = items.reduce((acc, i) => acc + (parseFloat(i.compensated_amount) || 0), 0);
      const totalDiff = items.reduce((acc, i) => acc + (parseFloat(i.diff_amount) || 0), 0);

      $('#mdp_kpi_patients').text(distinctHn.toLocaleString() + ' คน');
      $('#mdp_kpi_qty').text(totalQty.toLocaleString() + ' ชิ้น');
      $('#mdp_kpi_claim').text('฿' + totalClaim.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#mdp_kpi_comp').text('฿' + totalComp.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#mdp_kpi_diff').text('฿' + totalDiff.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    function renderDevicePatientsTableFiltered() {
      const keyword = ($('#mdp_search_input').val() || '').trim().toLowerCase();
      const statusFilter = $('#mdp_status_filter').val();

      let filtered = currentDevicePatientsRaw.filter(r => {
        if (statusFilter !== 'ALL' && r.claim_status !== statusFilter) return false;
        if (keyword) {
          const matchHn = (r.hn || '').toLowerCase().includes(keyword);
          const matchPt = (r.ptname || '').toLowerCase().includes(keyword);
          const matchCid = (r.cid || '').toLowerCase().includes(keyword);
          const matchVn = (r.vn || '').toLowerCase().includes(keyword);
          const matchPttype = (r.pttype_name || '').toLowerCase().includes(keyword);
          if (!matchHn && !matchPt && !matchCid && !matchVn && !matchPttype) return false;
        }
        return true;
      });

      if (filtered.length === 0) {
        $('#tbodyDevicePatients').html('<tr><td colspan="12" class="text-center py-4 text-dark fw-bold">ไม่พบรายชื่อผู้ป่วยตามเงื่อนไขที่ค้นหา</td></tr>');
        $('#mdp_count_label').text('แสดง 0 รายการ (จากทั้งหมด ' + currentDevicePatientsRaw.length + ' รายการ)');
        return;
      }

      let html = '';
      filtered.forEach((r, idx) => {
        const statusBadge = getStatusBadgeHtml(r.claim_status);
        const fundBadge = getFundBadgeHtml(r.fund_type);
        html += `
          <tr>
            <td class="text-center fw-bold text-dark">${idx + 1}</td>
            <td class="text-center fw-bold text-dark">${formatThaiDate(r.vstdate)}</td>
            <td class="text-center"><strong class="text-primary">${r.hn}</strong></td>
            <td><strong class="text-dark">${r.ptname}</strong></td>
            <td class="text-center text-dark fw-semibold"><small>${r.cid || '-'}</small></td>
            <td>
              <div class="text-truncate" style="max-width: 170px;" title="${r.pttype_name}">
                ${fundBadge} <small class="text-dark fw-bold">${r.pttype_name}</small>
              </div>
            </td>
            <td class="text-end fw-bold text-dark">${r.qty}</td>
            <td class="text-end fw-bold text-dark">฿${Number(r.claim_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold text-success">฿${Number(r.compensated_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold text-danger">฿${Number(r.diff_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="text-center">
              <button type="button" class="btn btn-xs btn-outline-primary btn-mdp-view" data-id="${r.id}" title="ดูรายละเอียดข้อมูลผู้ป่วย">
                <i class="bx bx-file"></i>
              </button>
            </td>
          </tr>
        `;
      });

      $('#tbodyDevicePatients').html(html);
      $('#mdp_count_label').text(`แสดง ${filtered.length} รายการ (จากทั้งหมด ${currentDevicePatientsRaw.length} รายการ)`);

      // Bind row details
      $('.btn-mdp-view').on('click', function() {
        const id = $(this).data('id');
        openClaimDetailModal(id);
      });
    }

    // ========================================================
    // DATA LOADER FUNCTIONS
    // ========================================================

    function loadAllStats() {
      const fy = $('#filter_fiscal_year').val();
      const f  = $('#filter_fund').val();
      const s  = $('#filter_start_date').val();
      const e  = $('#filter_end_date').val();

      $.ajax({
        url: 'api_rehab_stats.php',
        method: 'GET',
        data: { 
          fiscal_year: fy, 
          fund_type: f, 
          department_group: $('#filter_department').val(),
          start_date: s, 
          end_date: e 
        },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            currentKpi = res.kpi;
            currentTop10Devices = res.top10_devices || [];
            currentDenialReasons = res.denial_reasons || [];
            renderKpiCards(res.kpi);
            renderTop3Devices(res.top3_devices);
            renderStatusDonut(res.status_data);
            renderAgingBar(res.aging_data);
            renderMonthlyTrend(res.trend_data);
            renderDeviceSummaryTable(res.all_devices);
            renderDenialReasonsBar(res.denial_reasons);

            // Render Tab 4: Rights & Funds Report
            if (res.rights_report) {
              currentRightsData = res.rights_report.right_groups;
              renderRightsReport(res.rights_report);
              if ($('#tab-presentation').hasClass('active')) {
                switchPresentationSlide(currentSlideIndex || 1);
              }
            }

            // Render Tab 5: Department & Clinic Report
            if (res.department_report) {
              renderDepartmentReport(res.department_report);
            }

            // Update Device Filter Options in Tab 2
            let devOptHtml = '<option value="ALL">ทุกชนิดอุปกรณ์</option>';
            res.all_devices.forEach(d => {
              devOptHtml += `<option value="${d.device_code}">${d.device_name} (${d.device_code})</option>`;
            });
            $('#filter_device_type').html(devOptHtml);
          }
        }
      });
    }

    function renderKpiCards(kpi) {
      $('#kpi_total_patients').text(Number(kpi.total_patients).toLocaleString() + ' คน');
      $('#kpi_total_cases').text(Number(kpi.total_cases).toLocaleString());
      $('#kpi_paid_amount').text('฿' + Number(kpi.paid_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#kpi_paid_count').text(Number(kpi.paid_count).toLocaleString());
      $('#kpi_wait_stm_amount').text('฿' + Number(kpi.wait_stm_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#kpi_wait_stm_count').text(Number(kpi.wait_stm_count).toLocaleString());
      $('#kpi_rejected_amount').text('฿' + Number(kpi.rejected_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#kpi_rejected_count').text(Number(kpi.rejected_count).toLocaleString());
      $('#kpi_unclaimed_amount').text('฿' + Number(kpi.unclaimed_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#kpi_unclaimed_count').text(Number(kpi.unclaimed_count).toLocaleString());
      $('#kpi_urgent_amount').text('฿' + Number(kpi.urgent_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#kpi_urgent_count').text(Number(kpi.urgent_count).toLocaleString());

      $('#kpi_total_claim_val').text('฿' + Number(kpi.total_claim_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#kpi_rate_count').text(kpi.success_rate_count + '%');
      $('#kpi_rate_amount').text(kpi.success_rate_amount + '%');

      // Sync Presentation Slide Highlights (Comment Infographics)
      if ($('#slide1_kpi_comp').length) {
        $('#slide1_kpi_comp').text('฿' + (Number(kpi.paid_amount) / 1000000).toFixed(2) + 'M');
        $('#slide1_kpi_devices').text(Number(kpi.total_cases).toLocaleString() + ' ชิ้น');
      }
      updateSlide3Metrics();
    }

    function renderTop3Devices(top3) {
      if (!top3 || top3.length === 0) {
        $('#top3_container').html('<div class="col-12 text-center text-dark fw-bold py-3">ยังไม่มีข้อมูลอุปกรณ์</div>');
        return;
      }

      const rankMedals = ['🥇 อันดับ 1', '🥈 อันดับ 2', '🥉 อันดับ 3'];
      const rankGradients = ['bg-gradient-primary-glow', 'bg-gradient-success-glow', 'bg-gradient-warning-glow'];
      const cardColors = ['border-primary', 'border-success', 'border-warning'];

      let html = '';
      top3.forEach((d, idx) => {
        const medal = rankMedals[idx] || `อันดับ ${idx + 1}`;
        const gradient = rankGradients[idx] || 'bg-gradient-primary-glow';
        const colorBorder = cardColors[idx] || 'border-primary';

        html += `
          <div class="col-12 col-md-4">
            <div class="card rank-card h-100 ${colorBorder} shadow-sm" style="cursor: pointer;" onclick="openDevicePatientsModal('${d.device_code}', '${encodeURIComponent(d.device_name)}')" title="คลิกเพื่อดูรายชื่อผู้ป่วยที่ได้รับอุปกรณ์นี้ (${d.device_name})">
              <div class="card-header py-2 px-3 ${gradient} d-flex justify-content-between align-items-center">
                <span class="fw-bold small text-white">${medal}</span>
                <span class="badge bg-white text-dark small fw-bold">รหัส ADP: ${d.device_code}</span>
              </div>
              <div class="card-body p-3">
                <h6 class="fw-bold text-dark mb-2 text-truncate" title="${d.device_name}">${d.device_name}</h6>
                <div class="d-flex justify-content-between mb-2 small">
                  <span class="text-dark fw-bold">จำนวนที่จ่าย:</span>
                  <span class="fw-bold text-dark">${Number(d.total_qty).toLocaleString()} ชิ้น</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                  <span class="text-dark fw-bold">มูลค่าเรียกเก็บ:</span>
                  <span class="fw-bold text-primary">฿${Number(d.sum_claim).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                  <span class="text-dark fw-bold">ชดเชยแล้วจริง:</span>
                  <span class="fw-bold text-success">฿${Number(d.sum_comp).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: ${Math.min(d.success_pct, 100)}%"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center small pt-2 border-top">
                  <span class="text-primary fw-bold"><i class="bx bx-group me-1"></i> คลิกดูรายชื่อผู้ป่วย</span>
                  <span class="text-dark fw-bold">อัตราสำเร็จ: <strong class="text-success">${d.success_pct}%</strong></span>
                </div>
              </div>
            </div>
          </div>
        `;
      });

      $('#top3_container').html(html);
    }

    function renderStatusDonut(statusData) {
      const labels = [];
      const series = [];
      const colors = ['#28a745', '#ffab00', '#ff3e1d', '#233446', '#8592a3'];

      for (const [key, obj] of Object.entries(statusData)) {
        labels.push(obj.label);
        series.push(obj.count);
      }

      const options = {
        chart: {
          type: 'donut',
          height: 280,
          fontFamily: 'Noto Sans Thai, sans-serif'
        },
        labels: labels,
        series: series,
        colors: colors,
        legend: { position: 'bottom' },
        plotOptions: {
          pie: {
            donut: {
              size: '65%',
              labels: {
                show: true,
                total: {
                  show: true,
                  label: 'รวมทั้งหมด',
                  formatter: () => series.reduce((a, b) => a + b, 0).toLocaleString() + ' ครั้ง'
                }
              }
            }
          }
        },
        dataLabels: { enabled: true }
      };

      if (statusChartInstance) statusChartInstance.destroy();
      statusChartInstance = new ApexCharts(document.querySelector("#chartStatusDonut"), options);
      statusChartInstance.render();
    }

    function renderAgingBar(agingData) {
      const categories = ['< 30 วัน', '31 - 60 วัน', '> 60 วัน'];
      const counts = [
        Number(agingData.age_under_30 || 0),
        Number(agingData.age_31_60 || 0),
        Number(agingData.age_over_60 || 0)
      ];
      const amounts = [
        Number(agingData.amt_under_30 || 0),
        Number(agingData.amt_31_60 || 0),
        Number(agingData.amt_over_60 || 0)
      ];

      const options = {
        chart: {
          type: 'bar',
          height: 280,
          fontFamily: 'Noto Sans Thai, sans-serif',
          toolbar: { show: false }
        },
        series: [{
          name: 'จำนวนรายการ (ครั้ง)',
          data: counts
        }],
        xaxis: { categories: categories },
        plotOptions: {
          bar: {
            borderRadius: 4,
            columnWidth: '45%',
            distributed: true,
            dataLabels: { position: 'top' }
          }
        },
        colors: ['#28a745', '#ffab00', '#ff3e1d'],
        dataLabels: {
          enabled: true,
          formatter: (val) => val + ' ครั้ง',
          offsetY: -20,
          style: { fontSize: '12px', colors: ["#304758"] }
        },
        tooltip: {
          y: {
            formatter: (val, opt) => val + ' ครั้ง (มูลค่า ฿' + amounts[opt.dataPointIndex].toLocaleString() + ')'
          }
        }
      };

      if (agingChartInstance) agingChartInstance.destroy();
      agingChartInstance = new ApexCharts(document.querySelector("#chartAgingBar"), options);
      agingChartInstance.render();
    }

    function renderMonthlyTrend(trendData) {
      const categories = trendData.map(t => t.period_key);
      const paidSeries = trendData.map(t => Number(t.paid));
      const waitSeries = trendData.map(t => Number(t.wait_stm));
      const rejSeries  = trendData.map(t => Number(t.rejected));
      const unclaimSeries = trendData.map(t => Number(t.unclaimed));

      const options = {
        chart: {
          type: 'bar',
          height: 300,
          stacked: true,
          fontFamily: 'Noto Sans Thai, sans-serif',
          toolbar: { show: false }
        },
        series: [
          { name: 'เคลมผ่าน/ชดเชยแล้ว', data: paidSeries },
          { name: 'รอผล Statement', data: waitSeries },
          { name: 'เคลมไม่ผ่าน/ปฏิเสธ', data: rejSeries },
          { name: 'รอผลตรวจสอบ', data: unclaimSeries }
        ],
        colors: ['#28a745', '#ffab00', '#ff3e1d', '#8592a3'],
        xaxis: { categories: categories },
        legend: { position: 'top' },
        plotOptions: {
          bar: { borderRadius: 4, columnWidth: '50%' }
        }
      };

      if (trendChartInstance) trendChartInstance.destroy();
      trendChartInstance = new ApexCharts(document.querySelector("#chartMonthlyTrend"), options);
      trendChartInstance.render();
    }

    function renderDeviceSummaryTable(allDevices) {
      if (!allDevices || allDevices.length === 0) {
        $('#tbodyDeviceSummary').html('<tr><td colspan="7" class="text-center py-3 text-dark fw-bold">ไม่พบข้อมูลอุปกรณ์</td></tr>');
        return;
      }

      let html = '';
      allDevices.forEach(d => {
        html += `
          <tr class="device-summary-row" onclick="openDevicePatientsModal('${d.device_code}', '${encodeURIComponent(d.device_name)}')" title="คลิกเพื่อดูรายชื่อผู้ป่วยที่ได้รับอุปกรณ์นี้ (${d.device_name})">
            <td><strong class="text-primary fs-6">${d.device_code}</strong></td>
            <td>
              <span class="fw-bold text-dark">${d.device_name}</span>
              <span class="badge bg-label-primary ms-2"><i class="bx bx-user me-1"></i> ดูรายชื่อ</span>
            </td>
            <td class="text-end fw-bold text-dark">${Number(d.total_qty).toLocaleString()}</td>
            <td class="text-end fw-bold text-primary">฿${Number(d.sum_claim).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end text-success fw-bold">฿${Number(d.sum_comp).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end text-danger fw-bold">฿${Number(d.sum_diff).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end">
              <span class="badge ${d.success_pct >= 80 ? 'bg-label-success' : (d.success_pct >= 40 ? 'bg-label-warning' : 'bg-label-danger')} fw-bold">
                ${d.success_pct}%
              </span>
              <button type="button" class="btn btn-xs btn-outline-primary ms-1" onclick="event.stopPropagation(); openDevicePatientsModal('${d.device_code}', '${encodeURIComponent(d.device_name)}')" title="คลิกเพื่อดูรายชื่อผู้ป่วย">
                <i class="bx bx-list-ul"></i>
              </button>
            </td>
          </tr>
        `;
      });
      $('#tbodyDeviceSummary').html(html);
    }

    function renderDenialReasonsBar(reasons) {
      if (!$('#chartDenialReasons').length) return;
      if (!reasons || reasons.length === 0) {
        $('#chartDenialReasons').html('<div class="text-center text-muted py-5">ยอดเยี่ยม! ไม่พบรายการที่ถูกปฏิเสธการจ่ายในช่วงเวลานี้</div>');
        return;
      }

      const categories = reasons.map(r => (r.denial_code.startsWith('D') ? r.denial_code : 'D' + r.denial_code));
      const counts = reasons.map(r => Number(r.reject_count));
      const amounts = reasons.map(r => Number(r.reject_amount));

      const options = {
        chart: {
          type: 'bar',
          height: 260,
          fontFamily: 'Noto Sans Thai, sans-serif',
          toolbar: { show: false }
        },
        series: [{
          name: 'จำนวนครั้งที่ถูกปฏิเสธ',
          data: counts
        }],
        colors: ['#ff3e1d'],
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 4,
            barHeight: '60%'
          }
        },
        xaxis: { categories: categories },
        tooltip: {
          y: {
            formatter: (val, opt) => {
              const idx = opt.dataPointIndex;
              return `${val} ครั้ง (มูลค่า ฿${amounts[idx].toLocaleString()})<br>${reasons[idx].description_th}`;
            }
          }
        }
      };

      if (denialChartInstance) denialChartInstance.destroy();
      denialChartInstance = new ApexCharts(document.querySelector("#chartDenialReasons"), options);
      denialChartInstance.render();
    }

    // ========================================================
    // TAB 2: CLAIMS LIST LOADER & DRILL-DOWN
    // ========================================================

    function loadClaimsList() {
      const search = $('#search_claims').val();
      const status = $('#filter_claim_status').val();
      const devCode = $('#filter_device_type').val();
      const fund = $('#filter_fund').val();
      const s = $('#filter_start_date').val();
      const e = $('#filter_end_date').val();

      $('#tbodyClaimsList').html('<tr><td colspan="11" class="text-center py-4 text-muted"><i class="bx bx-loader-alt bx-spin me-2"></i> กำลังโหลดข้อมูล...</td></tr>');

      fetchFullClaimsData(search, status, devCode, fund, s, e);
    }

    function fetchFullClaimsData(search, status, devCode, fund, s, e) {
      // Query specific claims endpoint
      $.ajax({
        url: 'api_rehab_stats.php', // reuse stats or detail
        method: 'GET',
        data: {
          mode: 'claims_table',
          fiscal_year: $('#filter_fiscal_year').val(),
          search: search,
          status: status,
          dev_code: devCode,
          fund_type: fund,
          department_group: currentDepartmentGroup || $('#filter_department').val(),
          right_group: currentRightGroup,
          pttype: currentPttype,
          start_date: s,
          end_date: e,
          offset: claimsPage * claimsPageSize,
          limit: claimsPageSize
        },
        dataType: 'json',
        success: function(res) {
          // Fallback if mode not handled in api_rehab_stats, fetch via direct query or detail
          renderClaimsRows(res.items || [], res.total || 0);
        },
        error: function() {
          // Fallback direct load
          loadRejectedClaims();
        }
      });
    }

    function renderClaimsRows(items, total) {
      if (!items || items.length === 0) {
        $('#tbodyClaimsList').html('<tr><td colspan="11" class="text-center py-4 text-dark fw-bold">ไม่พบรายการผู้ป่วยตามเงื่อนไขที่เลือก</td></tr>');
        $('#claims_count_label').text('แสดง 0 รายการ');
        return;
      }

      let html = '';
      items.forEach(r => {
        let statusBadge = getStatusBadgeHtml(r.claim_status);
        let receiptBadge = '';
        let cleanRcpt = (r.receipt_no || '').replace(/["']/g, '').trim();
        if (cleanRcpt && cleanRcpt !== '-') {
          let rDateTitle = r.receipt_date ? ` (${r.receipt_date})` : '';
          let rSrcTitle = r.receipt_source ? ` [${r.receipt_source}]` : '';
          receiptBadge = `<br><span class="badge bg-label-info text-dark fw-bold mt-1" style="font-size:0.75rem;" title="เลขที่ใบเสร็จ: ${cleanRcpt}${rDateTitle}${rSrcTitle}"><i class="bx bx-receipt me-1 text-info"></i>${cleanRcpt}</span>`;
        }
        html += `
          <tr>
            <td>
              <strong class="text-dark">${formatThaiDate(r.vstdate)}</strong>
              ${r.vsttime ? `<br><small class="badge bg-label-primary fw-bold">${r.vsttime.substring(0,5)} น.</small>` : ''}
            </td>
            <td><strong class="text-primary">${r.hn}</strong></td>
            <td>
              <span class="fw-bold text-dark">${r.ptname}</span>
              ${r.age ? `<span class="badge bg-label-secondary ms-1 fw-bold">${r.age} ปี</span>` : ''}
              ${r.diag_code ? `<br><small class="text-dark fw-bold" title="${r.diag_name || ''}"><i class="bx bx-pulse text-danger me-1"></i>${r.diag_code} ${r.diag_name ? '<span class="text-truncate d-inline-block" style="max-width: 140px; vertical-align: bottom;">' + r.diag_name + '</span>' : ''}</small>` : ''}
            </td>
            <td>${getFundBadgeHtml(r.fund_type)}</td>
            <td>
              <span class="text-truncate d-inline-block" style="max-width: 220px;" title="${r.device_name}">
                <small class="text-primary fw-bold">[${r.device_code}]</small> <span class="text-dark fw-semibold">${r.device_name}</span>
              </span>
              ${r.department_group ? `<br><small class="badge bg-label-info fw-bold" style="font-size:0.7rem;" title="จุดสั่งใช้: ${r.department_name || r.department_group}"><i class="bx bx-buildings me-1"></i>${r.department_group}</small>` : ''}
            </td>
            <td class="text-end fw-bold text-dark">${r.qty}</td>
            <td class="text-end fw-bold text-dark">฿${Number(r.claim_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end text-success fw-bold">฿${Number(r.compensated_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end text-danger fw-bold">฿${Number(r.diff_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-center">${statusBadge}${receiptBadge}</td>
            <td class="text-center">
              <button type="button" class="btn btn-xs btn-outline-primary btn-view-claim" data-id="${r.id}" title="ดูรายละเอียด">
                <i class="bx bx-file"></i>
              </button>
              ${r.claim_status === 'REJECTED' || (r.denial_code && r.denial_code !== '0') ? `
                <button type="button" class="btn btn-xs btn-outline-danger btn-view-guide ms-1" data-code="${r.denial_code}" data-id="${r.id}" title="ดูแนวทางแก้ไข">
                  <i class="bx bx-bulb"></i>
                </button>
              ` : ''}
            </td>
          </tr>
        `;
      });
      $('#tbodyClaimsList').html(html);
      $('#claims_count_label').text(`แสดง ${items.length} รายการ (จากทั้งหมด ${total || items.length} รายการ)`);
      $('#btnClaimsPrev').prop('disabled', claimsPage === 0);
      $('#btnClaimsNext').prop('disabled', (claimsPage + 1) * claimsPageSize >= total);

      // Bind Row Buttons
      $('.btn-view-claim').on('click', function() {
        const id = $(this).data('id');
        openClaimDetailModal(id);
      });
      $('.btn-view-guide').on('click', function() {
        const code = $(this).data('code');
        const id = $(this).data('id');
        openDenialGuideModal(code, id);
      });
    }

    // ========================================================
    // TAB: REJECTED CLAIMS (REMOVED PER USER REQUEST)
    // ========================================================
    function loadRejectedClaims() {
      // Tab 3 (e-Claim Audit) removed per user request
    }

    // ========================================================
    // TAB 4: RIGHTS & FUNDS REPORT (รายงานแยกตามสิทธิการรักษา)
    // ========================================================

    function renderRightsReport(reportData) {
      if (!reportData) return;

      // 1. Primary Groups Table
      const groups = reportData.groups || [];
      if (groups.length === 0) {
        $('#tbodyRightsGroups').html('<tr><td colspan="10" class="text-center py-4 text-muted">ไม่พบข้อมูลสิทธิการรักษาในช่วงเวลานี้</td></tr>');
        $('#tfootRightsGroups').html('');
      } else {
        let gHtml = '';
        let tPts = 0, tCases = 0, tQty = 0, tClaim = 0, tComp = 0, tDiff = 0;

        groups.forEach((g, idx) => {
          tPts += Number(g.patient_count);
          tCases += Number(g.case_count);
          tQty += Number(g.total_qty);
          tClaim += Number(g.sum_claim);
          tComp += Number(g.sum_comp);
          tDiff += Number(g.sum_diff);

          const successBadge = g.success_pct >= 80 ? 'bg-label-success' : (g.success_pct >= 40 ? 'bg-label-warning' : 'bg-label-danger');

          gHtml += `
            <tr>
              <td class="text-center fw-bold text-dark">${idx + 1}</td>
              <td>
                <span class="fw-bold text-dark d-block">${g.right_group}</span>
                <small class="text-dark fw-bold">${g.group_code}</small>
              </td>
              <td class="text-end fw-bold text-dark">${Number(g.patient_count).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">${Number(g.case_count).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">${Number(g.total_qty).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">฿${Number(g.sum_claim).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-end fw-bold text-success">฿${Number(g.sum_comp).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-end fw-bold text-danger">฿${Number(g.sum_diff).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-end"><span class="badge ${successBadge} fw-bold">${g.success_pct}%</span></td>
              <td class="text-center">
                <button type="button" class="btn btn-xs btn-outline-primary btn-drill-group" data-group="${g.group_code}" title="ดูรายชื่อผู้ป่วยในสิทธินี้">
                  <i class="bx bx-show me-1"></i> ดูรายชื่อ
                </button>
              </td>
            </tr>
          `;
        });
        $('#tbodyRightsGroups').html(gHtml);

        const totalPct = tClaim > 0 ? (tComp / tClaim * 100).toFixed(1) : 0;
        const footHtml = `
          <tr class="fw-bold text-dark">
            <td colspan="2" class="text-center fw-bold text-dark">รวมทั้งสิ้น</td>
            <td class="text-end fw-bold text-dark">${tPts.toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">${tCases.toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">${tQty.toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">฿${tClaim.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold text-success">฿${tComp.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold text-danger">฿${tDiff.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end"><span class="badge bg-primary fw-bold">${totalPct}%</span></td>
            <td></td>
          </tr>
        `;
        $('#tfootRightsGroups').html(footHtml);

        $('.btn-drill-group').on('click', function() {
          const grp = $(this).data('group');
          currentRightGroup = grp;
          currentPttype = 'ALL';
          claimsPage = 0;
          $('#rehabTabs button#tab-claims-btn').tab('show');
          loadClaimsList();
        });
      }

      // 2. Sub-Rights Detail Table
      const details = reportData.detail || [];
      if (details.length === 0) {
        $('#tbodyRightsDetail').html('<tr><td colspan="11" class="text-center py-3 text-dark fw-bold">ไม่พบข้อมูลแจกแจงรายสิทธิ</td></tr>');
      } else {
        let dHtml = '';
        details.forEach((d, idx) => {
          dHtml += `
            <tr>
              <td class="text-center fw-bold text-dark">${idx + 1}</td>
              <td><span class="badge bg-label-info fw-bold">${d.parent_group}</span></td>
              <td class="text-center fw-bold text-primary">${d.pttype}</td>
              <td><span class="text-dark fw-bold">${d.pttype_name}</span></td>
              <td class="text-end fw-bold text-dark">${Number(d.patient_count).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">${Number(d.case_count).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">${Number(d.total_qty).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">฿${Number(d.sum_claim).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-end text-success fw-bold">฿${Number(d.sum_comp).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-end text-danger fw-bold">฿${Number(d.sum_diff).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-center">
                <button type="button" class="btn btn-xs btn-outline-secondary btn-drill-pttype" data-pttype="${d.pttype}" title="ดูเคสสิทธิ ${d.pttype}">
                  <i class="bx bx-search"></i>
                </button>
              </td>
            </tr>
          `;
        });
        $('#tbodyRightsDetail').html(dHtml);

        $('.btn-drill-pttype').on('click', function() {
          const pt = $(this).data('pttype');
          currentRightGroup = 'ALL';
          currentPttype = pt;
          claimsPage = 0;
          $('#rehabTabs button#tab-claims-btn').tab('show');
          loadClaimsList();
        });
      }
    }

    // ========================================================
    // TAB: DEPARTMENT & CLINIC REPORT RENDERER
    // ========================================================

    function renderDepartmentReport(deptData) {
      if (!deptData) return;
      currentDeptReportRaw = deptData;

      const groups = deptData.groups || [];
      const items  = deptData.items || [];

      // 1. Update Department KPI Cards
      const deptMap = {
        'กายภาพบำบัด': { amt: '#kpi_dept_rehab_amt', cases: '#kpi_dept_rehab_cases', comp: '#kpi_dept_rehab_comp' },
        'ผู้ป่วยนอก (OPD)': { amt: '#kpi_dept_opd_amt', cases: '#kpi_dept_opd_cases', comp: '#kpi_dept_opd_comp' },
        'ทันตกรรม': { amt: '#kpi_dept_dental_amt', cases: '#kpi_dept_dental_cases', comp: '#kpi_dept_dental_comp' },
        'อุบัติเหตุและฉุกเฉิน (ER)': { amt: '#kpi_dept_er_amt', cases: '#kpi_dept_er_cases', comp: '#kpi_dept_er_comp' },
        'ผู้ป่วยใน (IPD)': { amt: '#kpi_dept_ipd_amt', cases: '#kpi_dept_ipd_cases', comp: '#kpi_dept_ipd_comp' },
        'แพทย์แผนไทย': { amt: '#kpi_dept_ttm_amt', cases: '#kpi_dept_ttm_cases', comp: '#kpi_dept_ttm_comp' }
      };

      Object.values(deptMap).forEach(ids => {
        $(ids.amt).text('฿0');
        $(ids.cases).text('0');
        $(ids.comp).text('฿0');
      });

      groups.forEach(g => {
        const target = deptMap[g.department_group];
        if (target) {
          $(target.amt).text('฿' + Number(g.sum_claim).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}));
          $(target.cases).text(Number(g.case_count).toLocaleString());
          $(target.comp).text('ชดเชย ฿' + Number(g.sum_comp).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}));
        }
      });

      // 2. Render Donut Chart (% Share of Claim Amount)
      if ($('#chart_dept_donut').length) {
        if (groups.length === 0) {
          $('#chart_dept_donut').html('<div class="text-center text-muted py-5">ไม่มีข้อมูลสำหรับแสดงแผนภูมิ</div>');
        } else {
          const donutLabels = groups.map(g => g.department_group);
          const donutSeries = groups.map(g => Math.round(Number(g.sum_claim)));
          const deptColors = ['#696cff', '#71dd37', '#03c3ec', '#ff3e1d', '#ffab00', '#20c997', '#8592a3'];

          const donutOptions = {
            chart: {
              type: 'donut',
              height: 290,
              fontFamily: 'Noto Sans Thai, Poppins, sans-serif'
            },
            labels: donutLabels,
            series: donutSeries,
            colors: deptColors.slice(0, donutLabels.length),
            legend: { position: 'bottom', horizontalAlign: 'center', fontSize: '12px' },
            tooltip: {
              y: {
                formatter: (val) => '฿' + Number(val).toLocaleString() + ' บาท'
              }
            },
            plotOptions: {
              pie: {
                donut: {
                  size: '65%',
                  labels: {
                    show: true,
                    total: {
                      show: true,
                      label: 'ยอดเรียกเก็บรวม',
                      formatter: () => '฿' + Number(donutSeries.reduce((a, b) => a + b, 0)).toLocaleString()
                    }
                  }
                }
              }
            },
            dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + '%' }
          };

          if (deptDonutChartInstance) deptDonutChartInstance.destroy();
          deptDonutChartInstance = new ApexCharts(document.querySelector("#chart_dept_donut"), donutOptions);
          deptDonutChartInstance.render();
        }
      }

      // 3. Render Bar Chart (Clustered: Claimed vs Compensated vs Diff)
      if ($('#chart_dept_bar').length) {
        if (groups.length === 0) {
          $('#chart_dept_bar').html('<div class="text-center text-muted py-5">ไม่มีข้อมูลสำหรับแสดงแผนภูมิ</div>');
        } else {
          const barCategories = groups.map(g => g.department_group);
          const seriesClaim = groups.map(g => Math.round(Number(g.sum_claim)));
          const seriesComp  = groups.map(g => Math.round(Number(g.sum_comp)));
          const seriesDiff  = groups.map(g => Math.max(0, Math.round(Number(g.sum_diff))));

          const barOptions = {
            chart: {
              type: 'bar',
              height: 290,
              fontFamily: 'Noto Sans Thai, Poppins, sans-serif',
              toolbar: { show: false }
            },
            series: [
              { name: 'ยอดเรียกเก็บ (บาท)', data: seriesClaim },
              { name: 'ชดเชยแล้วจริง (บาท)', data: seriesComp },
              { name: 'ค้างชดเชย (บาท)', data: seriesDiff }
            ],
            colors: ['#696cff', '#71dd37', '#ffab00'],
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4
              }
            },
            dataLabels: { enabled: false },
            xaxis: {
              categories: barCategories,
              labels: { style: { fontSize: '11px', fontWeight: 600 } }
            },
            yaxis: {
              labels: {
                formatter: (val) => '฿' + (val >= 1000000 ? (val / 1000000).toFixed(1) + 'M' : (val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val))
              }
            },
            legend: { position: 'top', horizontalAlign: 'right' },
            tooltip: {
              y: {
                formatter: (val) => '฿' + Number(val).toLocaleString() + ' บาท'
              }
            }
          };

          if (deptBarChartInstance) deptBarChartInstance.destroy();
          deptBarChartInstance = new ApexCharts(document.querySelector("#chart_dept_bar"), barOptions);
          deptBarChartInstance.render();
        }
      }

      // 4. Render Table 1: Groups Summary Table
      if (groups.length === 0) {
        $('#tbodyDeptGroups').html('<tr><td colspan="10" class="text-center py-4 text-muted">ไม่พบข้อมูลกลุ่มงานในช่วงเวลาที่เลือก</td></tr>');
        $('#tfootDeptGroups').html('');
      } else {
        let gHtml = '';
        let tPts = 0, tCases = 0, tQty = 0, tClaim = 0, tComp = 0, tDiff = 0;

        groups.forEach((g, idx) => {
          tPts += Number(g.patient_count);
          tCases += Number(g.case_count);
          tQty += Number(g.total_qty);
          tClaim += Number(g.sum_claim);
          tComp += Number(g.sum_comp);
          tDiff += Number(g.sum_diff);

          const successBadge = g.success_pct >= 80 ? 'bg-label-success' : (g.success_pct >= 40 ? 'bg-label-warning' : 'bg-label-danger');

          let iconHtml = '<i class="bx bx-buildings text-primary me-1"></i>';
          if (g.department_group.includes('กายภาพ')) iconHtml = '<i class="bx bx-accessibility text-primary me-1"></i>';
          else if (g.department_group.includes('OPD')) iconHtml = '<i class="bx bx-clinic text-success me-1"></i>';
          else if (g.department_group.includes('ทันตกรรม')) iconHtml = '<i class="bx bx-smile text-info me-1"></i>';
          else if (g.department_group.includes('ER')) iconHtml = '<i class="bx bx-plus-medical text-danger me-1"></i>';
          else if (g.department_group.includes('IPD')) iconHtml = '<i class="bx bx-bed text-warning me-1"></i>';
          else if (g.department_group.includes('แผนไทย')) iconHtml = '<i class="bx bx-spa text-secondary me-1"></i>';

          gHtml += `
            <tr>
              <td class="text-center fw-bold text-dark">${idx + 1}</td>
              <td>
                <span class="fw-bold text-dark d-block">${iconHtml} ${g.department_group}</span>
                <small class="text-muted">เคลมผ่าน: ${Number(g.paid_count || 0).toLocaleString()} • รอผล: ${Number(g.wait_stm_count || 0).toLocaleString()} • ปฏิเสธ: ${Number(g.rejected_count || 0).toLocaleString()}</small>
              </td>
              <td class="text-end fw-bold text-dark">${Number(g.patient_count).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">${Number(g.case_count).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">${Number(g.total_qty).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">฿${Number(g.sum_claim).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-end fw-bold text-success">฿${Number(g.sum_comp).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-end fw-bold text-danger">฿${Number(g.sum_diff).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td class="text-end"><span class="badge ${successBadge} fw-bold">${g.success_pct}%</span></td>
              <td class="text-center">
                <button type="button" class="btn btn-xs btn-outline-primary btn-drill-dept" data-group="${g.department_group}" title="คลิกดูรายชื่อผู้ป่วยที่สั่งจ่ายจาก ${g.department_group}">
                  <i class="bx bx-search-alt me-1"></i> เจาะลึก
                </button>
              </td>
            </tr>
          `;
        });
        $('#tbodyDeptGroups').html(gHtml);

        const totalPct = tClaim > 0 ? (tComp / tClaim * 100).toFixed(1) : 0;
        const footHtml = `
          <tr class="fw-bold text-dark bg-light">
            <td colspan="2" class="text-center fw-bold text-dark">รวมทุกกลุ่มงานทั้งสิ้น</td>
            <td class="text-end fw-bold text-dark">${tPts.toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">${tCases.toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">${tQty.toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">฿${tClaim.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold text-success">฿${tComp.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold text-danger">฿${tDiff.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end"><span class="badge bg-primary fw-bold">${totalPct}%</span></td>
            <td></td>
          </tr>
        `;
        $('#tfootDeptGroups').html(footHtml);

        // Bind drill-down button to switch to claims tab filtered by department
        $('.btn-drill-dept').on('click', function() {
          const deptGrp = $(this).data('group');
          currentDepartmentGroup = deptGrp;
          $('#filter_department').val(deptGrp);
          claimsPage = 0;
          $('#rehabTabs button#tab-claims-btn').tab('show');
          loadClaimsList();
        });
      }

      // 5. Render Table 2: Detailed Items
      renderDeptItemsFiltered();
    }

    function renderDeptItemsFiltered() {
      if (!currentDeptReportRaw || !currentDeptReportRaw.items) return;
      const filterGroup = $('#sel_dept_breakdown_filter').val() || 'ALL';
      let items = currentDeptReportRaw.items;

      if (filterGroup !== 'ALL') {
        items = items.filter(i => i.department_group === filterGroup);
      }

      if (items.length === 0) {
        $('#tbodyDeptItems').html('<tr><td colspan="10" class="text-center py-4 text-muted">ไม่พบข้อมูลอุปกรณ์ตามกลุ่มงานที่เลือก</td></tr>');
        return;
      }

      let html = '';
      items.forEach((it, idx) => {
        const pctBadge = it.success_pct >= 80 ? 'bg-label-success' : (it.success_pct >= 40 ? 'bg-label-warning' : 'bg-label-danger');
        html += `
          <tr>
            <td class="text-center fw-bold text-dark">${idx + 1}</td>
            <td><span class="badge bg-label-info fw-bold">${it.department_group}</span></td>
            <td class="text-center fw-bold text-primary">${it.device_code}</td>
            <td>
              <span class="fw-bold text-dark">${it.device_name}</span>
            </td>
            <td class="text-end fw-bold text-dark">${Number(it.patient_count).toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">${Number(it.order_count).toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">${Number(it.total_qty).toLocaleString()}</td>
            <td class="text-end fw-bold text-dark">฿${Number(it.sum_claim).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold text-success">฿${Number(it.sum_comp).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end"><span class="badge ${pctBadge} fw-bold">${it.success_pct}%</span></td>
          </tr>
        `;
      });
      $('#tbodyDeptItems').html(html);
    }

    // ========================================================
    // MODAL DRILL-DOWN LOGIC
    // ========================================================

    function openClaimDetailModal(claimId) {
      $.ajax({
        url: 'api_rehab_denial.php',
        method: 'GET',
        data: { action: 'get_claim_detail', claim_id: claimId },
        dataType: 'json',
        success: function(res) {
          if (res.status !== 'success' || !res.data) {
            Swal.fire('ข้อผิดพลาด', res.message || 'ไม่พบข้อมูลรายการเคลม', 'error');
            return;
          }
          const item = res.data;

          $('#modal_claim_id').val(item.id);
          $('#modal_ptname').text(item.ptname);
          $('#modal_hn').text(item.hn);
          $('#modal_age').text(item.age ? item.age : '-');
          $('#modal_cid').text(item.cid || '-');
          $('#modal_vn').text(item.vn);
          $('#modal_status_badge').html(getStatusBadgeHtml(item.claim_status));
          $('#modal_vstdate').text(item.vstdate_th || item.vstdate);
          $('#modal_vsttime').text(item.vsttime ? item.vsttime.substring(0, 5) + ' น.' : '');
          $('#modal_pttype_name').text(item.pttype_name || '-');
          $('#modal_fund_type').html(getFundBadgeHtml(item.fund_type));
          $('#modal_doctor').text(item.doctor_name || '-');
          $('#modal_dept_group').text(item.department_group || '-');
          $('#modal_dept_name').text(item.department_name ? `(${item.department_name})` : '');
          $('#modal_diag').text(item.diag_code || '-');
          $('#modal_diag_name').text(item.diag_name || '');
          $('#modal_informaddr').text(item.informaddr || '-');
          $('#modal_device_code').text(item.device_code);
          $('#modal_device_name').text(item.device_name);
          $('#modal_qty_price').text(`${item.qty} ชิ้น x ฿${Number(item.unit_price).toLocaleString()}`);
          $('#modal_claim_amount').text('฿' + Number(item.claim_amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
          $('#modal_compensated_amount').text('฿' + Number(item.compensated_amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
          $('#modal_diff_amount').text('฿' + Number(item.diff_amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
          let cleanRep = (item.rep_no || '').replace(/["']/g, '').trim();
          let cleanRcptModal = (item.receipt_no || '').replace(/["']/g, '').trim();
          $('#modal_rep_no').text((cleanRep && cleanRep !== '-') ? cleanRep : '-');
          $('#modal_stm_docno').text(item.stm_docno || '-');
          $('#modal_receipt_no').text((cleanRcptModal && cleanRcptModal !== '-') ? cleanRcptModal : '-');
          let rDateSource = [];
          if (item.receipt_date) rDateSource.push(`วันที่: ${item.receipt_date}`);
          if (item.receipt_source) rDateSource.push(`[${item.receipt_source}]`);
          $('#modal_receipt_date_source').text(rDateSource.length > 0 ? rDateSource.join(' ') : '-');

          // Denial section
          if (item.denial_code && item.denial_code !== '0') {
            $('#modal_denial_code').text(item.denial_code);
            $('#modal_denial_desc').text(item.description_th || '-');
            $('#modal_denial_action').html(item.action_guide || 'ตรวจสอบเอกสารและส่งเบิกซ้ำ');

            const dt = (item.deny_type || '').toLowerCase();
            if (dt === 'verify') {
              $('#modal_denial_type_badge').attr('class', 'badge bg-info text-white fw-bold').text('Verify (ขอหลักฐาน)');
            } else if (dt === 'corrective') {
              $('#modal_denial_type_badge').attr('class', 'badge bg-warning text-dark fw-bold').text('Corrective (แก้ไขได้)');
            } else {
              $('#modal_denial_type_badge').attr('class', 'badge bg-danger text-white fw-bold').text('Deny (ปฏิเสธ)');
            }

            if (item.error_codes) {
              $('#modal_error_codes_wrap').html(`รหัสผิดพลาด: <span class="badge bg-label-danger">${item.error_codes}</span>`);
            } else {
              $('#modal_error_codes_wrap').empty();
            }

            $('#modal_denial_section').show();
          } else {
            $('#modal_denial_section').hide();
          }

          $('#modal_is_resubmitted').prop('checked', item.is_resubmitted == 1);
          $('#modal_officer_note').val(item.note || '');

          $('#modalClaimDetail').modal('show');
        }
      });
    }

    function openDenialGuideModal(code, claimId) {
      if (!code) code = 'D179';
      $('#guide_target_claim_id').val(claimId || '');

      $.ajax({
        url: 'api_rehab_denial.php',
        method: 'GET',
        data: { action: 'get_denial_guide', denial_code: code },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            const g = res.data;
            const fullCode = g.denial_code;

            $('#guide_code_badge').text(fullCode);
            $('#guide_desc_th').text(g.description_th);
            $('#guide_common_causes').html(g.common_causes || 'ข้อมูลเอกสารหรือรหัสไม่สมบูรณ์');
            $('#guide_action_steps').html(g.action_guide || 'ประสานงานแก้ไขรหัสและส่งเบิกซ้ำ');

            if (g.severity === 'RETRY') {
              $('#guide_severity_badge').attr('class', 'badge bg-success').text('แก้ไขส่งใหม่ได้ (RETRY)');
            } else if (g.severity === 'APPEAL') {
              $('#guide_severity_badge').attr('class', 'badge bg-warning text-dark').text('ต้องยื่นอุทธรณ์ (APPEAL)');
            } else {
              $('#guide_severity_badge').attr('class', 'badge bg-dark').text('ตัดสูญ (UNCOLLECTIBLE)');
            }

            $('#modalDenialGuide').modal('show');
          }
        }
      });
    }

    function loadDictionaryList() {
      const q = $('#dict_search_input').val() || '';
      const dtype = $('#dict_type_filter').val() || 'ALL';

      $('#tbodyDictionaryList').html('<tr><td colspan="5" class="text-center py-4 text-dark fw-bold"><i class="bx bx-loader-alt bx-spin me-2"></i> กำลังค้นหาข้อมูล...</td></tr>');

      $.ajax({
        url: 'api_rehab_denial.php',
        method: 'GET',
        data: { action: 'list_dictionary', search: q, deny_type: dtype },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            $('#dict_total_badge').text(`${res.total || res.items.length} รายการ`);

            if (!res.items || res.items.length === 0) {
              $('#tbodyDictionaryList').html('<tr><td colspan="5" class="text-center py-4 text-dark fw-bold">ไม่พบรหัสที่ตรงกับเงื่อนไขการค้นหา</td></tr>');
              return;
            }

            let html = '';
            res.items.forEach(d => {
              const dt = (d.deny_type || '').toLowerCase();
              let badgeHtml = '<span class="badge bg-warning text-dark fw-bold">Corrective</span>';
              if (dt === 'verify') {
                badgeHtml = '<span class="badge bg-info text-white fw-bold">Verify</span>';
              } else if (dt === 'deny') {
                badgeHtml = '<span class="badge bg-danger text-white fw-bold">Deny</span>';
              }

              html += `
                <tr>
                  <td>${badgeHtml}</td>
                  <td><strong class="text-primary fs-6">${d.denial_code}</strong></td>
                  <td>
                    <span class="fw-bold text-dark d-block" style="white-space: normal;">${d.description_th}</span>
                    ${d.common_causes ? `<small class="text-muted d-block mt-1"><i class="bx bx-info-circle me-1"></i>${d.common_causes}</small>` : ''}
                  </td>
                  <td>
                    <div class="small text-dark fw-semibold" style="white-space: normal; line-height: 1.4;">${d.action_guide || '-'}</div>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-xs btn-outline-primary btn-edit-dict" 
                      data-code="${d.denial_code}" 
                      data-type="${d.deny_type || 'Corrective'}"
                      data-fund="${d.fund_type}"
                      data-desc="${escapeHtml(d.description_th)}"
                      data-sev="${d.severity}"
                      data-guide="${escapeHtml(d.action_guide)}"
                      data-cause="${escapeHtml(d.common_causes)}">
                      <i class="bx bx-edit"></i>
                    </button>
                  </td>
                </tr>
              `;
            });
            $('#tbodyDictionaryList').html(html);

            $('.btn-edit-dict').on('click', function() {
              $('#dict_denial_code').val($(this).data('code'));
              $('#dict_deny_type').val($(this).data('type'));
              $('#dict_fund_type').val($(this).data('fund'));
              $('#dict_description_th').val(unescapeHtml($(this).data('desc')));
              $('#dict_severity').val($(this).data('sev'));
              $('#dict_action_guide').val(unescapeHtml($(this).data('guide')));
              $('#dict_common_causes').val(unescapeHtml($(this).data('cause')));
              new bootstrap.Collapse($('#collapseAddDict')[0], { show: true });
            });
          }
        }
      });
    }

    // Helper Badges
    function getStatusBadgeHtml(status) {
      switch ((status || '').toUpperCase()) {
        case 'PAID':
          return '<span class="badge bg-success badge-status"><i class="bx bx-check me-1"></i>ชดเชยแล้ว</span>';
        case 'WAIT_STM':
          return '<span class="badge bg-warning text-dark badge-status"><i class="bx bx-time me-1"></i>รอผล STM</span>';
        case 'REJECTED':
          return '<span class="badge bg-danger badge-status"><i class="bx bx-x me-1"></i>ปฏิเสธการจ่าย</span>';
        case 'RECALLED':
          return '<span class="badge bg-dark badge-status"><i class="bx bx-undo me-1"></i>ถูกดึงคืน</span>';
        case 'PARTIAL':
          return '<span class="badge bg-info badge-status"><i class="bx bx-pie-chart me-1"></i>ชดเชยบางส่วน</span>';
        default:
          return '<span class="badge bg-secondary badge-status"><i class="bx bx-minus me-1"></i>รอผลตรวจสอบ</span>';
      }
    }

    function getFundBadgeHtml(fund) {
      switch ((fund || '').toUpperCase()) {
        case 'UC': return '<span class="badge bg-label-success">สปสช. (UC)</span>';
        case 'OFC': return '<span class="badge bg-label-primary">เบิกจ่ายตรง (ข้าราชการ)</span>';
        case 'LGO': return '<span class="badge bg-label-info">อปท.</span>';
        case 'SSS': return '<span class="badge bg-label-warning">ประกันสังคม</span>';
        default: return '<span class="badge bg-label-secondary">อื่นๆ</span>';
      }
    }

    function escapeHtml(text) {
      if (!text) return '';
      return text.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function unescapeHtml(text) {
      if (!text) return '';
      return text.replace(/&quot;/g, '"').replace(/&#039;/g, "'");
    }

    // ========================================================
    // EXECUTIVE PRESENTATION SLIDES CONTROLLER (3 FOCUSED SLIDES)
    // ========================================================
    let currentSlideIndex = 1;
    let slide1VolumeChartInstance = null;
    let slide1RevenueChartInstance = null;
    let slide2RightsChartInstance = null;
    let currentRightsData = null;
    let currentTop10Devices = null;
    let currentDenialReasons = null;

    function switchPresentationSlide(slideId) {
      $('.slide-content-view').hide();
      $('#btnSlide1, #btnSlide2, #btnSlide3').removeClass('active');

      currentSlideIndex = parseInt(slideId);
      if (isNaN(currentSlideIndex) || currentSlideIndex < 1 || currentSlideIndex > 3) {
        currentSlideIndex = 1;
      }

      $(`#slide-section-${currentSlideIndex}`).fadeIn(200);
      $(`#btnSlide${currentSlideIndex}`).addClass('active');

      if (currentSlideIndex === 1) {
        // Instant Render without waiting for AJAX to prevent blank white charts
        renderSlide1VolumeChart(current5YearsTrendData || default5YearsData);
        renderSlide1RevenueChart(current5YearsTrendData || default5YearsData);
        renderSlide1Table(current5YearsTrendData || default5YearsData);
        setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
        }, 50);

        if (!current5YearsTrendData) {
          load5YearsTrendData(false);
        }
      } else if (currentSlideIndex === 2) {
        setTimeout(function() {
          renderSlide2RightsTableAndChart();
          renderSlide2Top10Devices(currentTop10Devices);
        }, 50);
      } else if (currentSlideIndex === 3) {
        updateSlide3Metrics();
      }
    }

    function prevPresentationSlide() {
      if (currentSlideIndex > 1) {
        switchPresentationSlide(currentSlideIndex - 1);
      }
    }

    function nextPresentationSlide() {
      if (currentSlideIndex < 3) {
        switchPresentationSlide(currentSlideIndex + 1);
      }
    }

    function isFullscreenActive() {
      return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
    }

    function toggleFullscreenPresentation() {
      const elem = document.getElementById("presentationWrapper") || document.getElementById("presentationCanvas");
      if (!elem) return;

      if (!isFullscreenActive() && !$('#presentationWrapper').hasClass('pseudo-fullscreen')) {
        if (elem.requestFullscreen) {
          elem.requestFullscreen().catch(err => {
            console.warn('Native fullscreen blocked, fallback to widescreen overlay:', err);
            fallbackFullscreenMode();
          });
        } else if (elem.webkitRequestFullscreen) {
          elem.webkitRequestFullscreen();
        } else if (elem.mozRequestFullScreen) {
          elem.mozRequestFullScreen();
        } else if (elem.msRequestFullscreen) {
          elem.msRequestFullscreen();
        } else {
          fallbackFullscreenMode();
        }
      } else {
        if ($('#presentationWrapper').hasClass('pseudo-fullscreen')) {
          $('#presentationWrapper').removeClass('pseudo-fullscreen');
          handleFullscreenChange();
        } else {
          if (document.exitFullscreen) {
            document.exitFullscreen().catch(err => console.warn(err));
          } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
          } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
          } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
          }
        }
      }
    }

    function fallbackFullscreenMode() {
      $('#presentationWrapper').toggleClass('pseudo-fullscreen');
      handleFullscreenChange();
    }

    function handleFullscreenChange() {
      const isFs = isFullscreenActive() || $('#presentationWrapper').hasClass('pseudo-fullscreen');
      if (isFs) {
        $('#btnToggleFs').removeClass('btn-dark').addClass('btn-danger').html('<i class="bx bx-exit-fullscreen me-1"></i> ออกจากเต็มหน้าจอ (Esc)');
      } else {
        $('#btnToggleFs').removeClass('btn-danger').addClass('btn-dark').html('<i class="bx bx-fullscreen me-1"></i> เต็มหน้าจอ (Fullscreen)');
      }

      // Re-trigger layout resize so charts expand to 100% full width
      setTimeout(function() {
        window.dispatchEvent(new Event('resize'));
        if (currentSlideIndex === 1) {
          renderSlide1VolumeChart();
          renderSlide1RevenueChart();
        } else if (currentSlideIndex === 2) {
          renderSlide2RightsTableAndChart();
        }
      }, 150);
    }

    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);


    // ========================================================
    // SLIDE 1: 5-YEAR COMPREHENSIVE DEPARTMENT BREAKDOWN
    // ========================================================
    const default5YearsData = <?= !empty($initial_5years_json) ? $initial_5years_json : 'null' ?>;
    let current5YearsTrendData = default5YearsData;

    function extractArrayForYears(valSource, years) {
      const yrs = (years || (current5YearsTrendData && current5YearsTrendData.years) || ['2565', '2566', '2567', '2568', '2569']).map(String);
      if (!valSource) return yrs.map(() => 0);
      if (typeof valSource === 'object' && !Array.isArray(valSource)) {
        return yrs.map(y => Number(valSource[y] || 0));
      }
      if (Array.isArray(valSource)) {
        return valSource.slice(0, yrs.length);
      }
      return yrs.map(() => 0);
    }

    // SLIDE 1 CHART 1: Multi-department Patient Volume Trend (5 Years)
    function renderSlide1VolumeChart(dataset) {
      const chartEl = document.querySelector("#slide1_volume_chart");
      if (!chartEl) return;

      if (slide1VolumeChartInstance) {
        slide1VolumeChartInstance.destroy();
      }

      const d = dataset || current5YearsTrendData || default5YearsData;
      const yrs = d.years || ['2565', '2566', '2567', '2568', '2569'];
      const pts = d.dept_patients || default5YearsData.dept_patients;

      const series = [
        { name: 'กายภาพบำบัด', data: extractArrayForYears(pts['กายภาพบำบัด'], yrs) },
        { name: 'ทันตกรรม', data: extractArrayForYears(pts['ทันตกรรม'], yrs) },
        { name: 'ผู้ป่วยนอก (OPD)', data: extractArrayForYears(pts['ผู้ป่วยนอก (OPD)'], yrs) },
        { name: 'ฉุกเฉิน (ER)', data: extractArrayForYears(pts['อุบัติเหตุและฉุกเฉิน (ER)'], yrs) },
        { name: 'ผู้ป่วยใน (IPD)', data: extractArrayForYears(pts['ผู้ป่วยใน (IPD)'], yrs) },
        { name: 'แพทย์แผนไทย', data: extractArrayForYears(pts['แพทย์แผนไทย'], yrs) }
      ];

      const options = {
        series: series,
        chart: {
          type: 'bar',
          height: 330,
          fontFamily: 'Noto Sans Thai, Poppins, sans-serif',
          toolbar: { show: false }
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '65%',
            borderRadius: 4
          }
        },
        dataLabels: {
          enabled: false
        },
        colors: ['#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00', '#8592a3'],
        xaxis: {
          categories: yrs.map(y => 'ปี ' + y),
          labels: { style: { fontSize: '12px', fontWeight: 700, colors: '#0f172a' } }
        },
        yaxis: {
          title: { text: 'จำนวนผู้รับบริการอุปกรณ์ (คน)', style: { fontWeight: 700, color: '#0f172a' } },
          labels: { formatter: function(val) { return Number(val).toLocaleString(); } }
        },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          fontWeight: 700,
          fontSize: '11px',
          markers: { radius: 12 }
        },
        tooltip: {
          shared: true,
          intersect: false,
          y: { formatter: function (val) { return Number(val).toLocaleString() + " คน"; } }
        }
      };

      slide1VolumeChartInstance = new ApexCharts(chartEl, options);
      slide1VolumeChartInstance.render();
    }

    // SLIDE 1 CHART 2: 5-Year Medical Device Claims Revenue Trend by Department
    function renderSlide1RevenueChart(dataset) {
      const chartEl = document.querySelector("#slide1_revenue_chart");
      if (!chartEl) return;

      if (slide1RevenueChartInstance) {
        slide1RevenueChartInstance.destroy();
      }

      const d = dataset || current5YearsTrendData || default5YearsData;
      const yrs = d.years || ['2565', '2566', '2567', '2568', '2569'];
      const rev = d.dept_revenue || default5YearsData.dept_revenue;
      const tot = d.total_devices || default5YearsData.total_devices;

      const series = [
        { name: 'กายภาพบำบัด', data: extractArrayForYears(rev['กายภาพบำบัด'], yrs) },
        { name: 'ทันตกรรม', data: extractArrayForYears(rev['ทันตกรรม'], yrs) },
        { name: 'ผู้ป่วยนอก (OPD)', data: extractArrayForYears(rev['ผู้ป่วยนอก (OPD)'], yrs) },
        { name: 'ฉุกเฉิน (ER)', data: extractArrayForYears(rev['อุบัติเหตุและฉุกเฉิน (ER)'], yrs) },
        { name: 'ผู้ป่วยใน (IPD)', data: extractArrayForYears(rev['ผู้ป่วยใน (IPD)'], yrs) },
        { name: 'รวมอุปกรณ์ รพ. ทั้งสิ้น', data: extractArrayForYears(tot, yrs) }
      ];

      const options = {
        series: series,
        chart: {
          type: 'line',
          height: 330,
          fontFamily: 'Noto Sans Thai, Poppins, sans-serif',
          toolbar: { show: false }
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth',
          width: [2.5, 2.5, 2, 2, 2, 4],
          dashArray: [0, 0, 0, 0, 0, 0]
        },
        colors: ['#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00', '#10b981'],
        xaxis: {
          categories: yrs.map(y => 'ปี ' + y),
          labels: { style: { fontSize: '12px', fontWeight: 700, colors: '#0f172a' } }
        },
        yaxis: {
          title: { text: 'มูลค่าเรียกเก็บอุปกรณ์ (บาท)', style: { fontWeight: 700, color: '#0f172a' } },
          labels: {
            formatter: function(val) {
              if (val >= 1000000) return '฿' + (val / 1000000).toFixed(2) + 'M';
              if (val >= 1000) return '฿' + (val / 1000).toFixed(0) + 'k';
              return '฿' + Number(val).toLocaleString();
            }
          }
        },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          fontWeight: 700,
          fontSize: '11px',
          markers: { radius: 12 }
        },
        tooltip: {
          shared: true,
          intersect: false,
          y: {
            formatter: function (val) {
              return '฿' + Number(val).toLocaleString() + ' บาท';
            }
          }
        }
      };

      slide1RevenueChartInstance = new ApexCharts(chartEl, options);
      slide1RevenueChartInstance.render();
    }

    // SLIDE 1 TABLE: Dynamic 5-Year Comprehensive Department Breakdown Table & Highlight Cards
    function renderSlide1Table(dataset) {
      const d = dataset || current5YearsTrendData || default5YearsData;
      if (!d) return;

      const yrs = (d.years || ['2565', '2566', '2567', '2568', '2569']).map(String);
      const yFirst = yrs[0];
      const yLast  = yrs[yrs.length - 1];

      // Update Subtitles & Labels
      if ($('#slide1_subtitle').length) {
        $('#slide1_subtitle').text(`จำแนกตาม 6 กลุ่มงานสั่งใช้และเบิกชดเชยอุปกรณ์การแพทย์ (ปีงบประมาณ ${yFirst} – ${yLast}) • ข้อมูลจริงจาก HOSxP โรงพยาบาลโพนทราย`);
      }
      if ($('#slide1_chart1_sub').length) {
        $('#slide1_chart1_sub').text(`ปีงบ ${yFirst} - ${yLast}`);
      }
      if ($('#slide1_chart2_sub').length) {
        $('#slide1_chart2_sub').text(`ปีงบ ${yFirst} - ${yLast}`);
      }

      function calcGrowth(firstVal, lastVal) {
        const f = Number(firstVal || 0);
        const l = Number(lastVal || 0);
        if (f === 0 && l === 0) return { pctStr: '-', isPos: true };
        if (f === 0) return { pctStr: '+100%', isPos: true };
        const p = ((l - f) / f) * 100;
        return {
          pctStr: (p >= 0 ? '+' : '') + p.toFixed(1) + '%',
          isPos: p >= 0
        };
      }

      function growthBadge(firstVal, lastVal) {
        const g = calcGrowth(firstVal, lastVal);
        if (g.pctStr === '-') return '<span class="fw-bold text-muted">-</span>';
        if (g.isPos) {
          return `<span class="fw-bold text-success"><i class="bx bx-up-arrow-alt"></i> ${g.pctStr}</span>`;
        } else {
          return `<span class="fw-bold text-danger"><i class="bx bx-down-arrow-alt"></i> ${g.pctStr}</span>`;
        }
      }

      // 1. Dynamic Table Header
      let theadHtml = `
        <tr>
          <th class="text-dark fw-bold text-start" style="width: 32%;">ตัวชี้วัดสำคัญ (Key Indicators) - จำแนกครบทุกกลุ่มงาน</th>
      `;
      yrs.forEach((y, idx) => {
        const isLast = (idx === yrs.length - 1);
        const cls = isLast ? 'text-dark fw-bold text-primary' : 'text-dark fw-bold';
        theadHtml += `<th class="${cls}" style="width: ${isLast ? '12%' : '11%'};">ปี ${y}</th>`;
      });
      theadHtml += `
          <th class="text-dark fw-bold text-success" style="width: 12%;">อัตราเติบโต (%)</th>
        </tr>
      `;
      $('#tableSlide1Summary thead').html(theadHtml);

      // 2. Data Extraction
      const rev = d.dept_revenue || {};
      const pts = d.dept_patients || {};
      const orders = d.dept_orders || {};
      const totDev = d.total_devices || {};
      const totDevPts = d.total_devices_patients || {};
      const totDevOrders = d.total_devices_orders || {};
      const physio = d.physio_by_dept || {};

      // Helper for secondary growth format (ครั้ง)
      function growthVisBadge(firstVal, lastVal) {
        const g = calcGrowth(firstVal, lastVal);
        if (g.pctStr === '-') return '<small class="fw-bold text-muted">- (ครั้ง)</small>';
        const colorCls = g.isPos ? 'text-success' : 'text-danger';
        const iconCls = g.isPos ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt';
        return `<small class="fw-bold ${colorCls}"><i class="bx ${iconCls}"></i> ${g.pctStr} (ครั้ง)</small>`;
      }

      // Build Department Rows (1 - 6)
      const deptConfigs = [
        { num: '1', key: 'กายภาพบำบัด', icon: 'bx-accessibility', color: 'text-primary' },
        { num: '2', key: 'ทันตกรรม', icon: 'bx-smile', color: 'text-info' },
        { num: '3', key: 'ผู้ป่วยนอก (OPD)', icon: 'bx-clinic', color: 'text-success' },
        { num: '4', key: 'อุบัติเหตุและฉุกเฉิน (ER)', icon: 'bx-plus-medical', color: 'text-danger' },
        { num: '5', key: 'ผู้ป่วยใน (IPD)', icon: 'bx-bed', color: 'text-warning' },
        { num: '6', key: 'แพทย์แผนไทย', icon: 'bx-spa', color: 'text-secondary' },
      ];

      let tbodyHtml = '';
      deptConfigs.forEach(dc => {
        const vals = extractArrayForYears(rev[dc.key], yrs);
        const ptVals = extractArrayForYears(pts[dc.key], yrs);
        const ordVals = extractArrayForYears(orders[dc.key], yrs);

        const firstV = vals[0] || 0;
        const lastV = vals[vals.length - 1] || 0;
        const firstOrd = ordVals[0] || 0;
        const lastOrd = ordVals[ordVals.length - 1] || 0;

        tbodyHtml += `
          <tr>
            <td class="fw-bold text-start ${dc.color}"><i class="bx ${dc.icon} me-1"></i> ${dc.num}. ${dc.key} (มูลค่าอุปกรณ์)</td>
        `;
        vals.forEach((v, idx) => {
          const isLast = (idx === vals.length - 1);
          const ptCount = ptVals[idx] || 0;
          const ordCount = ordVals[idx] || 0;
          const cls = isLast ? `fw-bold ${dc.color} fs-6` : 'fw-semibold text-dark';
          tbodyHtml += `
            <td class="${cls}">
              <div>฿${Math.round(v).toLocaleString()}</div>
              <small class="badge bg-light ${dc.color} border" style="font-size:0.75rem;">${ptCount.toLocaleString()} คน / ${ordCount.toLocaleString()} ครั้ง</small>
            </td>
          `;
        });
        tbodyHtml += `
            <td>
              <div class="fw-bold ${dc.color}">${growthBadge(firstV, lastV)} (เงิน)</div>
              ${growthVisBadge(firstOrd, lastOrd)}
            </td>
          </tr>
        `;
      });

      // Row 7: Total Devices
      const totVals = extractArrayForYears(totDev, yrs);
      let totPtVals = extractArrayForYears(totDevPts, yrs);
      let totOrdVals = extractArrayForYears(totDevOrders, yrs);

      // Fallback if totDevPts / totDevOrders not present in cache
      if (!totPtVals || totPtVals.every(x => x === 0)) {
        totPtVals = yrs.map((y, idx) => {
          let s = 0;
          deptConfigs.forEach(dc => { s += (extractArrayForYears(pts[dc.key], yrs)[idx] || 0); });
          return s;
        });
      }
      if (!totOrdVals || totOrdVals.every(x => x === 0)) {
        totOrdVals = yrs.map((y, idx) => {
          let s = 0;
          deptConfigs.forEach(dc => { s += (extractArrayForYears(orders[dc.key], yrs)[idx] || 0); });
          return s;
        });
      }

      const totFirst = totVals[0] || 0;
      const totLast = totVals[totVals.length - 1] || 0;
      const totFirstOrd = totOrdVals[0] || 0;
      const totLastOrd = totOrdVals[totOrdVals.length - 1] || 0;

      tbodyHtml += `
        <tr style="background-color: #f0fdf4;" class="border-top border-2">
          <td class="fw-bolder text-start text-dark"><i class="bx bx-check-shield me-1 text-success"></i> 7. รวมยอดเรียกเก็บอุปกรณ์การแพทย์ทั้งสิ้น (บาท)</td>
      `;
      totVals.forEach((tv, idx) => {
        const isLast = (idx === totVals.length - 1);
        const ptCount = totPtVals[idx] || 0;
        const ordCount = totOrdVals[idx] || 0;
        const cls = isLast ? 'fw-bolder text-success fs-5' : 'fw-bold text-dark';
        tbodyHtml += `
          <td class="${cls}">
            <div>฿${Math.round(tv).toLocaleString()}</div>
            <small class="badge bg-success text-white" style="font-size:0.75rem;">${ptCount.toLocaleString()} คน / ${ordCount.toLocaleString()} ครั้ง</small>
          </td>
        `;
      });
      tbodyHtml += `
          <td>
            <div class="fw-bolder text-success fs-6">${growthBadge(totFirst, totLast)} (เงิน)</div>
            ${growthVisBadge(totFirstOrd, totLastOrd)}
          </td>
        </tr>
      `;

      // Section Header: Income 14
      tbodyHtml += `
        <tr style="background-color: #f1f5f9;" class="border-top border-2">
          <td colspan="7" class="text-start fw-bolder text-dark py-2">
            <i class="bx bx-donate-heart me-1 text-primary"></i> <b>ค่าบริการทางกายภาพบำบัดและเวชศาสตร์ฟื้นฟู (HOSxP หมวด 14) - แยกรายกลุ่มงาน (ยอดเงิน & จำนวนคน / ครั้ง)</b>
          </td>
        </tr>
      `;

      // Rows 8 - 11: Physio by department
      const physioDepts = [
        { num: '8', key: 'กายภาพบำบัด', icon: 'bx-accessibility', color: 'text-primary' },
        { num: '9', key: 'ผู้ป่วยนอก (OPD)', icon: 'bx-clinic', color: 'text-success' },
        { num: '10', key: 'ผู้ป่วยใน (IPD)', icon: 'bx-bed', color: 'text-warning' },
        { num: '11', key: 'อุบัติเหตุและฉุกเฉิน (ER)', icon: 'bx-plus-medical', color: 'text-danger' }
      ];

      physioDepts.forEach(pd => {
        const pData = physio[pd.key] || {};
        const revs = extractArrayForYears(pData.revenue, yrs);
        const pts  = extractArrayForYears(pData.patients, yrs);
        const vis  = extractArrayForYears(pData.visits, yrs);

        const fRev = revs[0] || 0;
        const lRev = revs[revs.length - 1] || 0;
        const fVis = vis[0] || 0;
        const lVis = vis[vis.length - 1] || 0;

        tbodyHtml += `
          <tr>
            <td class="fw-bold text-start ${pd.color}">
              <i class="bx ${pd.icon} me-1"></i> ${pd.num}. ${pd.key} (ค่าบริการหมวด 14)
            </td>
        `;
        revs.forEach((rv, idx) => {
          const isLast = (idx === revs.length - 1);
          const ptCount = pts[idx] || 0;
          const viCount = vis[idx] || 0;
          const cls = isLast ? `fw-bold ${pd.color} fs-6` : 'fw-semibold text-dark';

          tbodyHtml += `
            <td class="${cls}">
              <div>฿${Math.round(rv).toLocaleString()}</div>
              <small class="badge bg-light text-primary border" style="font-size:0.75rem;">${ptCount.toLocaleString()} คน / ${viCount.toLocaleString()} ครั้ง</small>
            </td>
          `;
        });

        tbodyHtml += `
            <td>
              <div class="fw-bold ${pd.color}">${growthBadge(fRev, lRev)} (เงิน)</div>
              ${growthVisBadge(fVis, lVis)}
            </td>
          </tr>
        `;
      });

      // Row 12: Total Hospital Physio
      const totPData = physio['รวมทั้งโรงพยาบาล'] || {};
      const totPRevs = extractArrayForYears(totPData.revenue, yrs);
      const totPPts  = extractArrayForYears(totPData.patients, yrs);
      const totPVis  = extractArrayForYears(totPData.visits, yrs);

      const fPRev = totPRevs[0] || 0;
      const lPRev = totPRevs[totPRevs.length - 1] || 0;
      const fPVis = totPVis[0] || 0;
      const lPVis = totPVis[totPVis.length - 1] || 0;

      tbodyHtml += `
        <tr style="background-color: #e0e7ff;" class="border-top border-2">
          <td class="fw-bolder text-start text-dark">
            <i class="bx bx-check-circle me-1 text-primary"></i> 12. รวมค่าบริการกายภาพบำบัดทั้งสิ้น (HOSxP หมวด 14)
          </td>
      `;
      totPRevs.forEach((rv, idx) => {
        const isLast = (idx === totPRevs.length - 1);
        const ptCount = totPPts[idx] || 0;
        const viCount = totPVis[idx] || 0;
        const cls = isLast ? 'fw-bolder text-primary fs-5' : 'fw-bold text-dark';

        tbodyHtml += `
          <td class="${cls}">
            <div>฿${Math.round(rv).toLocaleString()}</div>
            <small class="badge bg-primary text-white" style="font-size:0.75rem;">${ptCount.toLocaleString()} คน / ${viCount.toLocaleString()} ครั้ง</small>
          </td>
        `;
      });
      tbodyHtml += `
          <td>
            <div class="fw-bolder text-primary fs-6">${growthBadge(fPRev, lPRev)} (เงิน)</div>
            ${growthVisBadge(fPVis, lPVis)}
          </td>
        </tr>
      `;

      $('#tbodySlide1Rows').html(tbodyHtml);

      // 3. Update 3 Highlight Cards
      // Card 1: Rehab
      const rehabRevs = extractArrayForYears(rev['กายภาพบำบัด'], yrs);
      const rehabPts  = extractArrayForYears(pts['กายภาพบำบัด'], yrs);
      const rFRev = rehabRevs[0] || 0, rLRev = rehabRevs[rehabRevs.length - 1] || 0;
      const rFPts = rehabPts[0] || 0,  rLPts = rehabPts[rehabPts.length - 1] || 0;
      $('#slide1_card1_span1').text(`ปี ${yFirst}: ฿${Math.round(rFRev).toLocaleString()} (${rFPts.toLocaleString()} คน)`);
      $('#slide1_card1_span2').text(`ปี ${yLast}: ฿${Math.round(rLRev).toLocaleString()} (${rLPts.toLocaleString()} คน)`);
      $('#slide1_card1_growth').html(`<i class="bx bx-trending-up"></i> ${calcGrowth(rFRev, rLRev).pctStr}`);

      // Card 2: Dental
      const dentalRevs = extractArrayForYears(rev['ทันตกรรม'], yrs);
      const dentalPts  = extractArrayForYears(pts['ทันตกรรม'], yrs);
      const dFRev = dentalRevs[0] || 0, dLRev = dentalRevs[dentalRevs.length - 1] || 0;
      const dFPts = dentalPts[0] || 0;
      $('#slide1_card2_span1').text(`ปี ${yFirst}: ฿${Math.round(dFRev).toLocaleString()} (${dFPts.toLocaleString()} คน)`);
      $('#slide1_card2_span2').text(`ปี ${yLast}: ฿${Math.round(dLRev).toLocaleString()}`);
      $('#slide1_card2_growth').html(`<i class="bx bx-trending-up"></i> ${calcGrowth(dFRev, dLRev).pctStr}`);

      // Card 3: Total Devices
      const tFRev = totVals[0] || 0, tLRev = totVals[totVals.length - 1] || 0;
      $('#slide1_card3_span1').text(`ปี ${yFirst}: ฿${Math.round(tFRev).toLocaleString()}`);
      $('#slide1_card3_span2').text(`ปี ${yLast}: ฿${Math.round(tLRev).toLocaleString()}`);
      $('#slide1_card3_growth').html(`<i class="bx bx-trending-up"></i> ${calcGrowth(tFRev, tLRev).pctStr}`);
    }

    // Live Fetcher & Cache Loader for Slide 1
    function load5YearsTrendData(isRefresh) {
      const $btn = $('#btnRefresh5Years');
      if (isRefresh) {
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> กำลังอัปเดต HOSxP...');
      }

      const fy = $('#filter_fiscal_year').val();

      $.ajax({
        url: 'api_rehab_stats.php',
        method: 'GET',
        data: {
          mode: '5years_trend',
          fiscal_year: fy,
          refresh: isRefresh ? 1 : 0
        },
        dataType: 'json',
        success: function(res) {
          if (res && res.status === 'success' && res.data) {
            current5YearsTrendData = res.data;
            renderSlide1VolumeChart(current5YearsTrendData);
            renderSlide1RevenueChart(current5YearsTrendData);
            renderSlide1Table(current5YearsTrendData);
          } else {
            renderSlide1VolumeChart(default5YearsData);
            renderSlide1RevenueChart(default5YearsData);
            renderSlide1Table(default5YearsData);
          }
        },
        error: function(err) {
          console.warn('5years trend API error, using default cached data', err);
          renderSlide1VolumeChart(default5YearsData);
          renderSlide1RevenueChart(default5YearsData);
          renderSlide1Table(default5YearsData);
        },
        complete: function() {
          if (isRefresh) {
            $btn.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i> อัปเดตสด');
          }
        }
      });
    }

    // SLIDE 2: Rights Compensation Table & Visual Chart
    function renderSlide2RightsTableAndChart() {
      if (currentRightsData && currentRightsData.length > 0) {
        let rowsHtml = '';
        const chartCategories = [];
        const seriesClaim = [];
        const seriesComp = [];

        currentRightsData.forEach(function(item) {
          const sClaim = Number(item.sum_claim || 0);
          const sComp = Number(item.sum_comp || 0);
          const sDiff = Number(item.sum_diff || 0);
          const sRate = item.success_pct || 0;
          const badgeCls = sRate >= 80 ? 'bg-success' : (sRate > 0 ? 'bg-primary' : 'bg-secondary');

          rowsHtml += `
            <tr>
              <td class="text-start fw-bold text-dark"><i class="bx bx-shield-quarter me-1 text-primary"></i> ${item.right_group}</td>
              <td class="text-end fw-semibold text-dark">${Number(item.patient_count).toLocaleString()}</td>
              <td class="text-end fw-bold text-dark">฿${sClaim.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
              <td class="text-end fw-bold text-success">฿${sComp.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
              <td class="text-end fw-bold text-danger">฿${sDiff.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
              <td class="text-center"><span class="badge ${badgeCls}">${sRate}%</span></td>
            </tr>
          `;

          let shortName = item.right_group;
          if (shortName.indexOf('(') !== -1) {
            shortName = shortName.split('(')[0].trim();
          }
          chartCategories.push(shortName);
          seriesClaim.push(Math.round(sClaim));
          seriesComp.push(Math.round(sComp));
        });

        $('#slide2_tbody_rights').html(rowsHtml);

        const chartEl = document.querySelector("#slide2_rights_chart");
        if (chartEl) {
          if (slide2RightsChartInstance) {
            slide2RightsChartInstance.destroy();
          }

          const optRights = {
            series: [{
              name: 'ยอดเรียกเก็บ (บาท)',
              data: seriesClaim
            }, {
              name: 'ชดเชยแล้วจริง (บาท)',
              data: seriesComp
            }],
            chart: {
              type: 'bar',
              height: 220,
              fontFamily: 'Noto Sans Thai, Poppins, sans-serif',
              toolbar: { show: false }
            },
            plotOptions: {
              bar: {
                horizontal: true,
                barHeight: '65%',
                borderRadius: 4
              }
            },
            colors: ['#3b82f6', '#10b981'],
            dataLabels: { enabled: false },
            xaxis: {
              categories: chartCategories,
              labels: {
                formatter: function (val) {
                  return '฿' + (val / 1000).toFixed(0) + 'k';
                }
              }
            },
            legend: { position: 'top', horizontalAlign: 'right', fontWeight: 700 }
          };

          slide2RightsChartInstance = new ApexCharts(chartEl, optRights);
          slide2RightsChartInstance.render();
        }
      }
    }

    // SLIDE 2: TOP 10 Equipment List Rendering
    function renderSlide2Top10Devices(devices) {
      if (!devices || devices.length === 0) return;
      let html = '';
      devices.forEach(function(d, index) {
        const rank = index + 1;
        let borderLeft = 'border-left: 5px solid #cbd5e1 !important;';
        let badgeRank = 'bg-secondary';
        if (rank === 1) {
          borderLeft = 'border-left: 5px solid #f43f5e !important;';
          badgeRank = 'bg-danger';
        } else if (rank === 2) {
          borderLeft = 'border-left: 5px solid #3b82f6 !important;';
          badgeRank = 'bg-primary';
        } else if (rank === 3) {
          borderLeft = 'border-left: 5px solid #eab308 !important;';
          badgeRank = 'bg-warning text-dark';
        }

        const sClaim = Number(d.sum_claim || 0);
        const sComp = Number(d.sum_comp || 0);
        const sPct = d.success_pct || 0;
        const totalQty = Number(d.total_qty || 0);
        const ptCount = Number(d.patient_count || 0);
        const safeName = (d.device_name || '').replace(/"/g, '&quot;');
        const encName = encodeURIComponent(d.device_name || '');

        html += `
          <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate position-relative" style="cursor: pointer; ${borderLeft}" onclick="openDevicePatientsModal('${d.device_code}', '${encName}')" title="คลิกดูรายชื่อผู้ป่วยที่ได้รับอุปกรณ์ ${safeName}">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                <span class="badge ${badgeRank} rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">${rank}</span>
                <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: ${d.device_code}</span>
                <strong class="text-dark text-truncate small" title="${safeName}">${safeName}</strong>
              </div>
              <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย ${sPct}%</span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
              <span class="text-dark">จ่าย: <strong class="text-dark">${totalQty.toLocaleString()} ชิ้น (${ptCount.toLocaleString()} คน)</strong> | ชดเชย: <strong class="text-success">฿${Math.round(sComp).toLocaleString()}</strong></span>
              <strong class="text-primary">฿${Math.round(sClaim).toLocaleString()}</strong>
            </div>
          </div>
        `;
      });
      $('#slide2_top10_container').html(html);
    }

    // SLIDE 3: Executive Financial Risk & Opportunities
    function updateSlide3Metrics() {
      if (currentKpi) {
        const unclaimed = Number(currentKpi.unclaimed_amount || 0);
        const waitStm   = Number(currentKpi.wait_stm_amount || 0);
        const rejected  = Number(currentKpi.rejected_amount || 0);
        const opportunity = unclaimed + rejected;

        $('#slide3_unclaimed_val').text('฿' + unclaimed.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}));
        $('#slide3_wait_val').text('฿' + waitStm.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}));
        $('#slide3_rejected_val').text('฿' + rejected.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}));
        if ($('#slide3_opportunity_badge').length) {
          $('#slide3_opportunity_badge').html(`<i class="bx bx-bell me-1"></i> โอกาสดึงเงินกลับคืน รพ. กว่า ฿${Math.round(opportunity).toLocaleString()}`);
        }

        renderSlide3DenialCauses(currentDenialReasons, rejected);
      }
    }

    function renderSlide3DenialCauses(denialReasons, rejectedAmount) {
      const $container = $('#slide3_denial_causes_container');
      if (!$container.length) return;

      const reasons = (denialReasons && denialReasons.length > 0) ? denialReasons : [];
      const hasRejections = (Number(rejectedAmount || 0) > 0 || reasons.length > 0);

      if (!hasRejections) {
        // Zero rejections: Show clean success card + proactive compliance checklist
        const html = `
          <div class="p-3 mb-3 border border-success rounded-3 bg-label-success text-center py-4">
            <div class="avatar avatar-md bg-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm">
              <i class="bx bx-check-double text-success fs-2"></i>
            </div>
            <h6 class="fw-bold text-success mb-1">ยอดเยี่ยม! ไม่พบรายการที่ถูกปฏิเสธการจ่ายในช่วงเวลานี้ (0 รายการ)</h6>
            <p class="text-muted small mb-0">อัตราการชดเชยสมบูรณ์แบบ ข้อมูลการส่งเบิกผ่านเกณฑ์การตรวจสอบของกองทุน 100%</p>
          </div>
          <div class="p-3 border rounded-3 bg-light small text-dark">
            <strong class="text-primary d-block mb-2"><i class="bx bx-bulb me-1"></i> ข้อพึงระวังเชิงยุทธศาสตร์เพื่อรักษามาตรฐานและป้องกันการติด Error ล่วงหน้า:</strong>
            <div class="mb-2 pb-2 border-bottom">
              <strong class="text-dark d-block">1. การอัปเดตรหัสอุปกรณ์ (ADP หมวด 2):</strong>
              <span class="text-muted">ตรวจสอบให้รหัสอุปกรณ์ใน HOSxP ตรงกับประกาศ สปสช. ล่าสุดอยู่เสมอ ป้องกันข้อผิดพลาด D179</span>
            </div>
            <div class="mb-2 pb-2 border-bottom">
              <strong class="text-dark d-block">2. ความสมบูรณ์ของรหัสโรคหลัก (ICD-10 PDX):</strong>
              <span class="text-muted">ระบุรหัสโรคเฉพาะทางที่สอดคล้องกับข้อบ่งชี้การใช้อุปกรณ์ (เช่น M54.5, G81) หลีกเลี่ยง Z-code ทั่วไป</span>
            </div>
            <div>
              <strong class="text-dark d-block">3. รอบระยะเวลาการส่งข้อมูล (Fast-Track 30 วัน):</strong>
              <span class="text-muted">ส่งออกข้อมูล e-Claim เป็นประจำทุกสัปดาห์ ไม่ปล่อยให้ค้างเกิน 30 วันเพื่อป้องกันปัญหาหมดอายุความ D210</span>
            </div>
          </div>
        `;
        $container.html(html);
      } else {
        // Real rejections exist: Display top actual denial reasons from database
        let html = '';
        reasons.slice(0, 3).forEach((r, idx) => {
          const code = r.denial_code ? (r.denial_code.startsWith('D') ? r.denial_code : ('D' + r.denial_code)) : 'DENIAL';
          const sev = r.severity || 'RETRY';
          const badgeClass = sev === 'RETRY' ? 'bg-success' : (sev === 'APPEAL' ? 'bg-warning text-dark' : 'bg-danger');
          const badgeText = sev === 'RETRY' ? 'โอกาสผ่านสูง (RETRY)' : (sev === 'APPEAL' ? 'ต้องยื่นอุทธรณ์ (APPEAL)' : 'ตรวจสอบเอกสาร');
          const amt = Number(r.reject_amount || 0);
          const cnt = Number(r.reject_count || 0);
          const desc = r.description_th || 'ไม่ระบุคำอธิบาย';

          let actionGuide = 'ปรับปรุงรหัส ADP ในระบบ HOSxP และแนบใบประเมินส่งซ้ำผ่านระบบ e-Claim';
          if (sev === 'APPEAL' || code.includes('210') || code.includes('C02')) {
            actionGuide = 'จัดทำหนังสือขออุทธรณ์ชี้แจงเหตุผลความจำเป็นลงนามโดย ผอ.รพ. เพื่อขอเบิกจ่ายกรณีพิเศษ';
          } else if (code.includes('349') || code.includes('E01')) {
            actionGuide = 'ประสานงานแพทย์ผู้ตรวจปรับปรุงรหัสการวินิจฉัยโรค PDX ให้ครอบคลุมตามเกณฑ์';
          }

          html += `
            <div class="p-3 mb-2 border rounded-3 bg-light">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="text-danger"><i class="bx bx-error me-1"></i> ${idx + 1}. รหัส ${code} : ${desc}</strong>
                <span class="badge ${badgeClass}">${badgeText}</span>
              </div>
              <small class="text-dark d-block">
                <strong>สถิติที่พบ:</strong> ถูกปฏิเสธ <b>${cnt.toLocaleString()}</b> ครั้ง รวมมูลค่า <b class="text-danger">฿${amt.toLocaleString()}</b><br>
                <strong class="text-primary">แนวทางแก้ไข:</strong> ${actionGuide}
              </small>
            </div>
          `;
        });
        $container.html(html);
      }
    }

    // Auto-trigger slide render when Tab 5 is shown
    $(document).on('shown.bs.tab', '#tab-presentation-btn', function () {
      switchPresentationSlide(currentSlideIndex || 1);
    });

    // ========================================================
    // DATA AUDIT & HISTORICAL AUDIT CONTROLLER
    // ========================================================
    function populateHistoricalAuditYears() {
      const $sel = $('#historical_audit_year');
      if (!$sel.length) return;
      const curYear = <?= $default_fiscal_year ?>;
      let opts = '';
      for (let y = curYear; y >= curYear - 6; y--) {
        const isCur = (y === curYear) ? ' (ปีปัจจุบัน)' : '';
        opts += `<option value="${y}" ${y === curYear ? 'selected' : ''}>ปีงบประมาณ ${y}${isCur}</option>`;
      }
      $sel.html(opts);
    }

    function openDataAuditModal() {
      $('#modalDataAudit').modal('show');
      runDataAuditCheck(false);
    }

    function openHistoricalAuditModal() {
      $('#modalDataAudit').modal('show');
      $('#collapseHistoricalAudit').collapse('show');
      runDataAuditCheck(false);
    }

    function runDataAuditCheck(isFresh) {
      const $btn = $('#btnReRunAudit');
      if (isFresh) {
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> กำลังตรวจสอบ...');
      }

      const s = $('#filter_start_date').val();
      const e = $('#filter_end_date').val();
      const fy = $('#filter_fiscal_year').val();

      $.ajax({
        url: 'api_rehab_stats.php',
        method: 'GET',
        data: {
          mode: 'data_audit',
          start_date: s,
          end_date: e,
          fiscal_year: fy,
          fresh: isFresh ? 1 : 0
        },
        dataType: 'json',
        success: function(res) {
          if (res && res.status === 'success' && res.data) {
            renderDataAuditResults(res.data);
          }
        },
        error: function(err) {
          console.warn('Data Audit request error:', err);
        },
        complete: function() {
          if (isFresh) {
            $btn.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i> ตรวจสอบสดใหม่');
          }
        }
      });
    }

    function renderDataAuditResults(d) {
      const score = Number(d.health_score || 0);
      $('#auditHealthScoreText').text(score + '%');
      
      let scoreGrad = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
      let scoreText = 'ข้อมูลผ่านการตรวจสอบเทียบกับ HOSxP และ Statement ครบถ้วน';
      if (score < 80 && score >= 50) {
        scoreGrad = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
        scoreText = 'พบประเด็นที่ควรตรวจสอบ เช่น รหัสโรคไม่สมบูรณ์ หรือค้างรอส่งเคลม';
      } else if (score < 50) {
        scoreGrad = 'linear-gradient(135deg, #ef4444 0%, #b91c1c 100%)';
        scoreText = 'จำเป็นต้องแก้ไขข้อมูลเคสปฏิเสธ และดึงรายการจาก HOSxP เพิ่มเติม';
      }
      $('#auditScoreCircle').css('background', scoreGrad);
      $('#auditScoreDesc').text(scoreText);

      if (d.period && d.period.start_date && d.period.end_date) {
        $('#auditPeriodBadge').text(`ช่วงเวลา: ${d.period.start_date} ถึง ${d.period.end_date}`);
      }

      // Checklist 1: HOSxP Completeness
      const edhsCnt = Number(d.summary ? d.summary.total_claims : 0);
      const hosCnt = (d.hosxp_check && d.hosxp_check.hosxp_count !== null) ? Number(d.hosxp_check.hosxp_count) : edhsCnt;
      const gapCnt = (d.hosxp_check && d.hosxp_check.sync_gap !== null) ? Number(d.hosxp_check.sync_gap) : 0;
      $('#chk1_edhs_cnt').text(edhsCnt.toLocaleString() + ' รายการ');
      $('#chk1_hos_cnt').text(hosCnt.toLocaleString() + ' รายการ');
      $('#chk1_gap_cnt').text(gapCnt.toLocaleString() + ' รายการ');
      if (gapCnt === 0) {
        $('#chk1_badge').removeClass('bg-danger bg-warning').addClass('bg-success').html('<i class="bx bx-check me-1"></i>สมบูรณ์ 100%');
      } else {
        $('#chk1_badge').removeClass('bg-success bg-warning').addClass('bg-danger').html(`<i class="bx bx-error me-1"></i>ค้างดึง ${gapCnt} รายการ`);
      }

      // Checklist 2: STM Statement
      const stm = d.stm_reconciliation || {};
      const paidCnt = Number(stm.paid_cnt || 0);
      const partialCnt = Number(stm.partial_cnt || 0);
      const waitCnt = Number(stm.wait_stm_cnt || 0);
      const rejectCnt = Number(stm.rejected_cnt || 0);
      $('#chk2_paid_cnt').text(paidCnt.toLocaleString() + ' รายการ');
      $('#chk2_partial_cnt').text(partialCnt.toLocaleString() + ' รายการ');
      $('#chk2_wait_cnt').text(waitCnt.toLocaleString() + ' รายการ');
      $('#chk2_reject_cnt').text(rejectCnt.toLocaleString() + ' รายการ');
      if (rejectCnt === 0) {
        $('#chk2_badge').removeClass('bg-danger bg-warning').addClass('bg-success').html('<i class="bx bx-check me-1"></i>ไม่มีเคสปฏิเสธ');
      } else {
        $('#chk2_badge').removeClass('bg-success bg-warning').addClass('bg-danger').html(`<i class="bx bx-x me-1"></i>ปฏิเสธ ${rejectCnt} เคส`);
      }

      // Checklist 3: Pre-claim Quality
      const qual = d.quality_audit || {};
      const cidCnt = Number(qual.missing_cid_cnt || 0);
      const diagCnt = Number(qual.missing_diag_cnt || 0);
      const invCnt = Number(qual.total_invalid_cnt || 0);
      $('#chk3_cid_cnt').text(cidCnt.toLocaleString() + ' รายการ');
      $('#chk3_diag_cnt').text(diagCnt.toLocaleString() + ' รายการ');
      $('#chk3_invalid_cnt').text(invCnt.toLocaleString() + ' รายการ');
      if (invCnt === 0) {
        $('#chk3_badge').removeClass('bg-danger bg-warning').addClass('bg-success').html('<i class="bx bx-check me-1"></i>ผ่านเกณฑ์ 100%');
      } else {
        $('#chk3_badge').removeClass('bg-success bg-danger').addClass('bg-warning text-dark').html(`<i class="bx bx-error me-1"></i>พบ ${invCnt} รายการต้องระวัง`);
      }

      // Checklist 4: Aging & Overdue
      const ag = d.aging_audit || {};
      const agingCnt = Number(ag.aging_31_90d_cnt || 0);
      const overdueCnt = Number(ag.overdue_90d_cnt || 0);
      const overdueAmt = Number(ag.overdue_90d_amt || 0);
      $('#chk4_aging_cnt').text(agingCnt.toLocaleString() + ' รายการ');
      $('#chk4_overdue_cnt').text(overdueCnt.toLocaleString() + ' รายการ');
      $('#chk4_overdue_amt').text('฿' + overdueAmt.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}));
      if (overdueCnt === 0) {
        $('#chk4_badge').removeClass('bg-danger bg-warning').addClass('bg-success').html('<i class="bx bx-check me-1"></i>ไม่มีเคสเกิน 90 วัน');
      } else {
        $('#chk4_badge').removeClass('bg-success bg-warning').addClass('bg-danger').html(`<i class="bx bx-time me-1"></i>เกิน 90 วัน ${overdueCnt} เคส`);
      }
    }

    function filterAuditRejectedCases() {
      $('#modalDataAudit').modal('hide');
      $('#tab-claims-btn').tab('show');
      $('#filter_status').val('REJECTED');
      loadClaimsList();
    }

    function filterAuditQualityCases() {
      $('#modalDataAudit').modal('hide');
      $('#tab-claims-btn').tab('show');
      $('#filter_search').val('');
      $('#filter_status').val('ALL');
      loadClaimsList();
    }

    function filterAuditUnclaimedCases() {
      $('#modalDataAudit').modal('hide');
      $('#tab-claims-btn').tab('show');
      $('#filter_status').val('UNCLAIMED');
      loadClaimsList();
    }

    function executeHistoricalAudit() {
      const fy = parseInt($('#historical_audit_year').val());
      if (!fy) return;
      const ce = fy - 543;
      const sDate = (ce - 1) + '-10-01';
      const eDate = ce + '-09-30';

      $('#btnExecuteHistoricalAudit').prop('disabled', true);
      $('#historicalAuditProgress').slideDown(150);
      $('#historicalAuditProgressText').text(`กำลังดึงข้อมูล HOSxP และ Audit ชนยอด Statement ปีงบประมาณ ${fy}...`);

      $.ajax({
        url: 'api_rehab_hos_pull.php',
        method: 'POST',
        data: {
          start_date: sDate,
          end_date: eDate,
          auto_match: 1
        },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: `Audit ปีงบประมาณ ${fy} เรียบร้อย`,
              text: `ดึงข้อมูลสำเร็จ ${Number(res.pulled_count || 0).toLocaleString()} รายการ, ชนยอด ${Number(res.matched_stm_count || 0).toLocaleString()} รายการ`,
              timer: 3000,
              showConfirmButton: true
            });
            runDataAuditCheck(true);
            loadAllStats();
            loadClaimsList();
          } else {
            Swal.fire({
              icon: 'warning',
              title: 'การ Audit ไม่สมบูรณ์',
              text: res.message || 'โปรดตรวจสอบการเชื่อมต่อ HOSxP'
            });
          }
        },
        error: function(err) {
          Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถเชื่อมต่อ HOSxP ได้'
          });
        },
        complete: function() {
          $('#btnExecuteHistoricalAudit').prop('disabled', false);
          $('#historicalAuditProgress').slideUp(150);
        }
      });
    }
  </script>
</body>
</html>
