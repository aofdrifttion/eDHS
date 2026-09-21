<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
mysqli_set_charset($conn, 'utf8mb4');

// คำนวณปีงบประมาณปัจจุบัน
$currentMonth = (int)date('n');
$currentYear = (int)date('Y');

if ($currentMonth >= 10) {
    // ต.ค. - ธ.ค. : ปีงบประมาณถัดไป
    $startYear = $currentYear;
    $endYear = $currentYear + 1;
} else {
    // ม.ค. - ก.ย. : ปีงบประมาณปีนี้
    $startYear = $currentYear - 1;
    $endYear = $currentYear;
}

$defaultStartDate = "";
$defaultEndDate = "";
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <title>รายงานลูกหนี้ตามใบเสร็จ - eDHS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>

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

        /* Filter Card */
        .receipt-filter-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #4f46e5 !important;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            margin-bottom: 24px;
            padding: 24px;
        }

        .receipt-header-icon {
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

        .receipt-pill-badge {
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

        .receipt-form-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .receipt-input-group {
            border-radius: 10px;
            overflow: hidden;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s ease;
            background: #ffffff;
        }
        .receipt-input-group:focus-within {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }
        .receipt-input-group .form-control,
        .receipt-input-group .form-select {
            border: none !important;
            box-shadow: none !important;
            font-size: 13.5px;
            color: #1e293b;
            padding: 9px 12px;
            height: 42px;
        }

        .btn-search-receipt {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            padding: 10px 20px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 42px;
        }
        .btn-search-receipt:hover {
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
            transform: translateY(-1px);
        }

        /* Table Card */
        .receipt-table-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }

        #myTable {
            border-collapse: separate;
            border-spacing: 0;
            width: 100% !important;
            font-size: 13.5px;
        }
        #myTable thead th {
            background: #f8fafc !important;
            color: #475569 !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            padding: 14px 12px !important;
            border-bottom: 1.5px solid #e2e8f0 !important;
            text-align: center !important;
            vertical-align: middle !important;
        }
        #myTable tbody td {
            padding: 12px 14px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            font-size: 13px !important;
            vertical-align: middle !important;
        }
        #myTable tfoot td {
            padding: 14px 14px !important;
            border-top: 2px solid #cbd5e1 !important;
            font-size: 13.5px !important;
            font-weight: 700 !important;
        }

        .clickable-row {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .clickable-row:hover {
            background-color: #f8fafc !important;
        }

        .text-paid { color: #059669 !important; font-weight: 600; }
        .text-debt { color: #dc2626 !important; font-weight: 600; }
        .text-compensate { color: #d97706 !important; font-weight: 600; }

        .badge-bill-type {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
        }
        .badge-bill-single {
            background: #ecfeff;
            color: #0891b2;
            border: 1px solid #a5f3fc;
        }
        .badge-bill-stm {
            background: #f5f3ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
        }
        .badge-item-count {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 3px 8px;
            font-weight: 600;
            font-size: 12px;
        }

        .btn-view-detail {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-view-detail:hover {
            background: #f59e0b;
            color: #ffffff;
            border-color: #f59e0b;
        }

        /* Loading Overlay */
        .loading-overlay, .modal-loading-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.88);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(2px);
            border-radius: 16px;
        }

        /* Detail Modal */
        #detailModal .modal-content {
            border-radius: 20px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
            overflow: hidden;
        }
        #detailModal .modal-header {
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            padding: 18px 24px;
        }
        #detailModal .modal-body {
            padding: 24px;
            background: #ffffff;
        }
        #detailModal .modal-footer {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 14px 24px;
        }
    </style>
</head>

<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        
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

        <!-- Layout page -->
        <div class="layout-page">
            <nav class="container-fluid" id="layout-navbar" style="margin-bottom: 20px;">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)"><i class="bx bx-menu bx-sm"></i></a>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        
                        <!-- Search Form Card -->
                        <div class="receipt-filter-card">
                            <!-- Header with Icon and Badge -->
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="receipt-header-icon">
                                        <i class="bx bx-receipt"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 fw-bold text-dark">รายงานลูกหนี้ตามใบเสร็จ</h4>
                                        <p class="mb-0 text-muted small">สืบค้นและตรวจสอบรายการลูกหนี้ตามเลขที่ใบเสร็จ ยอดภาระหนี้ ยอดชดเชย และส่วนต่าง</p>
                                    </div>
                                </div>
                                <div>
                                    <span class="receipt-pill-badge">
                                        <i class="bx bx-shield-quarter text-primary"></i> ระบบสืบค้นใบเสร็จรับเงิน
                                    </span>
                                </div>
                            </div>

                            <!-- Search Form Fields -->
                            <form id="searchForm" onsubmit="event.preventDefault(); loadData();">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-2">
                                        <label class="receipt-form-label"><i class='bx bx-category text-primary'></i> ประเภทใบเสร็จ</label>
                                        <div class="receipt-input-group input-group">
                                            <select id="receiptType" class="form-select" onchange="loadData()">
                                                <option value="all">ทั้งหมด</option>
                                                <option value="bill">ตัดรายตัว (bill)</option>
                                                <option value="mobile">STM (bill)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="receipt-form-label"><i class='bx bx-barcode text-primary'></i> เลขที่ใบเสร็จ</label>
                                        <div class="receipt-input-group input-group">
                                            <input type="text" id="receiptNo" class="form-control" placeholder="ค้นหาใบเสร็จ (เว้นว่างดูทั้งหมด)">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="receipt-form-label"><i class='bx bx-calendar text-primary'></i> ตั้งแต่วันที่</label>
                                        <div class="receipt-input-group input-group">
                                            <input type="text" id="startDate" class="form-control thai-datepicker bg-white" value="<?= $defaultStartDate ?>" placeholder="เลือกวันที่...">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="receipt-form-label"><i class='bx bx-calendar-event text-primary'></i> ถึงวันที่</label>
                                        <div class="receipt-input-group input-group">
                                            <input type="text" id="endDate" class="form-control thai-datepicker bg-white" value="<?= $defaultEndDate ?>" placeholder="เลือกวันที่...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-search-receipt w-100">
                                            <i class="bx bx-search-alt-2"></i> ค้นหาข้อมูลใบเสร็จ
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Result Table Card -->
                        <div class="receipt-table-card position-relative">
                            <div class="loading-overlay" id="loadingOverlay">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem; border-width: 0.25em;">
                                      <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="fw-semibold text-dark small">กำลังโหลดข้อมูลใบเสร็จ...</span>
                                </div>
                            </div>
                            <div class="p-3">
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th width="4%">ลำดับ</th>
                                                <th width="12%" class="text-nowrap">เลขที่ใบเสร็จ</th>
                                                <th width="10%">ประเภท</th>
                                                <th width="10%">วันที่ออกใบเสร็จ</th>
                                                <th width="8%">จำนวนรายการ</th>
                                                <th width="9%">ค่าใช้จ่าย</th>
                                                <th width="9%">ชำระแล้ว</th>
                                                <th width="9%">ภาระหนี้</th>
                                                <th width="9%">ชดเชย</th>
                                                <th width="10%">ส่วนต่าง</th>
                                                <th width="10%" style="white-space: nowrap;">ดูรายละเอียด</th>
                                            </tr>
                                        </thead>
                                        <tbody id="resultBody">
                                            <tr><td colspan="11" class="text-center text-muted py-4">กำลังโหลดข้อมูล...</td></tr>
                                        </tbody>
                                        <tfoot id="resultFoot" style="display:none; background-color: #f8fafc; font-weight: bold;">
                                            <tr>
                                                <td colspan="5" class="text-end fw-bold">รวมทั้งหมด :</td>
                                                <td class="text-end fw-bold" id="totalDebit">0.00</td>
                                                <td class="text-end text-paid fw-bold" id="totalPaid">0.00</td>
                                                <td class="text-end text-debt fw-bold" id="totalBalance">0.00</td>
                                                <td class="text-end text-compensate fw-bold" id="totalCompensate">0.00</td>
                                                <td class="text-end text-primary fw-bold" id="totalDiff">0.00</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
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
    </div>
</div>

<!-- Modal for details -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 90vw; width: 90vw; margin: 1.5rem auto;">
    <div class="modal-content position-relative">
      <div class="modal-loading-overlay" id="modalLoadingOverlay">
          <div class="d-flex flex-column align-items-center gap-2">
              <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                  <span class="visually-hidden">Loading...</span>
              </div>
              <span class="fw-semibold text-dark small">กำลังโหลดรายละเอียดลูกหนี้...</span>
          </div>
      </div>
      <div class="modal-header d-flex align-items-center justify-content-between">
        <div id="modalTitle">
          <h5 class="modal-title text-dark fw-bold"><i class="bx bx-list-ul me-2 text-primary"></i> รายละเอียดลูกหนี้</h5>
        </div>
        <button type="button" class="btn btn-sm btn-icon rounded-circle shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-size: 18px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'; this.style.borderColor='#cbd5e1';">
          <i class='bx bx-x' style="font-size: 22px; font-weight: bold;"></i>
        </button>
      </div>
      <div class="modal-body pt-3">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" style="border-radius: 12px; overflow: hidden;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th width="5%">ลำดับ</th>
                        <th width="10%">วันที่รับบริการ</th>
                        <th width="17%" class="text-nowrap">ชื่อ-สกุล</th>
                        <th width="21%" class="text-nowrap">สิทธิการเงิน</th>
                        <th width="9%">ภาระหนี้</th>
                        <th width="9%">ชดเชย</th>
                        <th width="9%">ส่วนต่าง</th>
                        <th width="12%" class="text-nowrap">เลขที่ใบเสร็จ</th>
                        <th width="8%">วันที่ออกใบเสร็จ</th>
                    </tr>
                </thead>
                <tbody id="modalBodyContent">
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #f8fafc;">
                        <td colspan="4" class="text-end">รวมเป็นเงิน :</td>
                        <td class="text-end text-debt" id="modalTotalBalance">0.00</td>
                        <td class="text-end text-compensate" id="modalTotalCompensate">0.00</td>
                        <td class="text-end text-primary" id="modalTotalDiff">0.00</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">ปิด</button>
      </div>
    </div>
  </div>
</div>


<script src="../assets/vendor/libs/jquery/jquery.js"></script>
<script src="../assets/vendor/libs/popper/popper.js"></script>
<script src="../assets/vendor/js/bootstrap.js"></script>
<script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="../assets/vendor/js/menu.js"></script>
<script src="../assets/js/main.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        initFlatpickr();
        loadData();
    });

    function initFlatpickr(){
        flatpickr(".thai-datepicker",{
            locale:"th",
            dateFormat:"Y-m-d",
            altInput:true,
            altFormat:"j F Y",
            disableMobile:true,
            onReady:(d,s,i)=>replaceYearToThai(i),
            onValueUpdate:(d,s,i)=>{
                replaceYearToThai(i);
                if(d[0]) i.altInput.value=`${d[0].getDate()} ${i.l10n.months.longhand[d[0].getMonth()]} ${d[0].getFullYear()+543}`;
            },
            onMonthChange:(d,s,i)=>replaceYearToThai(i),
            onYearChange:(d,s,i)=>replaceYearToThai(i)
        });
    }
    function replaceYearToThai(i){
        if(i.currentYearElement) i.currentYearElement.value=i.currentYear+543;
    }

    function loadData() {
        let receiptNo = $('#receiptNo').val().trim();
        let startDate = $('#startDate').val();
        let endDate = $('#endDate').val();
        let receiptType = $('#receiptType').val();

        $('#loadingOverlay').css('display', 'flex');

        $.ajax({
            url: 'ajax_get_receipt_report.php',
            type: 'POST',
            data: {
                action: 'summary',
                receiptNo: receiptNo,
                startDate: startDate,
                endDate: endDate,
                receiptType: receiptType
            },
            dataType: 'json',
            success: function(res) {
                $('#loadingOverlay').hide();
                let tbody = $('#resultBody');
                
                if ($.fn.DataTable.isDataTable('#myTable')) {
                    $('#myTable').DataTable().destroy();
                }
                
                tbody.empty();

                if(res.status === 'success' && res.data.length > 0) {
                    let html = '';
                    let sumDebit = 0, sumPaid = 0, sumCompensate = 0, sumBalance = 0, sumDiff = 0;
                    $.each(res.data, function(i, item) {
                        let billBadge = item.receipt_type === 'ตัดรายตัว (bill)' 
                            ? `<span class="badge-bill-type badge-bill-single"><i class='bx bx-user me-1'></i>${item.receipt_type}</span>`
                            : `<span class="badge-bill-type badge-bill-stm"><i class='bx bx-layer me-1'></i>${item.receipt_type}</span>`;

                        html += `<tr class="clickable-row" onclick="viewDetail('${item.receipt_no}', '${item.receipt_type}')">
                            <td class="text-center text-muted">${i+1}</td>
                            <td class="text-center fw-bold text-nowrap"><code style="background:#eef2ff; color:#4338ca; padding:4px 8px; border-radius:6px; font-weight:700; font-size:12.5px;">${item.receipt_no}</code></td>
                            <td class="text-center">${billBadge}</td>
                            <td class="text-center">${item.receipt_date}</td>
                            <td class="text-center"><span class="badge-item-count">${item.item_count}</span></td>
                            <td class="text-end fw-semibold">${parseFloat(item.sum_debit).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-paid">${parseFloat(item.sum_paid).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-debt">${parseFloat(item.sum_balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-compensate">${parseFloat(item.sum_compensate).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-primary fw-bold">${parseFloat(item.sum_diff).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-view-detail" onclick="event.stopPropagation(); viewDetail('${item.receipt_no}', '${item.receipt_type}', ${item.item_count})">
                                    <i class="bx bx-search-alt-2"></i> ดูรายคน
                                </button>
                            </td>
                        </tr>`;
                        sumDebit += parseFloat(item.sum_debit);
                        sumPaid += parseFloat(item.sum_paid);
                        sumBalance += parseFloat(item.sum_balance);
                        sumCompensate += parseFloat(item.sum_compensate);
                        sumDiff += parseFloat(item.sum_diff);
                    });
                    tbody.html(html);

                    $('#totalDebit').text(sumDebit.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#totalPaid').text(sumPaid.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#totalBalance').text(sumBalance.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#totalCompensate').text(sumCompensate.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#totalDiff').text(sumDiff.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#resultFoot').show();
                    
                    $('#myTable').DataTable({
                        "language": {
                            "sProcessing": "กำลังดำเนินการ...",
                            "sLengthMenu": "แสดง _MENU_ แถว",
                            "sZeroRecords": "ไม่พบข้อมูล",
                            "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ แถว",
                            "sInfoEmpty": "แสดง 0 ถึง 0 จาก 0 แถว",
                            "sInfoFiltered": "(กรองข้อมูล _MAX_ ทุกแถว)",
                            "sInfoPostFix": "",
                            "sSearch": "ค้นหา:",
                            "sUrl": "",
                            "oPaginate": {
                                "sFirst": "เริ่มต้น",
                                "sPrevious": "ก่อนหน้า",
                                "sNext": "ถัดไป",
                                "sLast": "สุดท้าย"
                            }
                        },
                        "pageLength": 20,
                        "lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "ทั้งหมด"]],
                        "destroy": true,
                        "ordering": false
                    });
                } else {
                    tbody.html(`<tr><td colspan="10" class="text-center text-muted py-4">ไม่พบข้อมูลใบเสร็จในช่วงเวลาที่เลือก</td></tr>`);
                    $('#resultFoot').hide();
                }
            },
            error: function(err) {
                $('#loadingOverlay').hide();
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
                console.error(err);
            }
        });
    }

    function viewDetail(receiptNo, receiptTypeDetail, itemCount = 0) {
        if(itemCount > 2000) {
            Swal.fire({
                title: 'คำเตือน: ข้อมูลจำนวนมาก!',
                text: `บิลนี้มีรายการทั้งหมด ${itemCount.toLocaleString()} รายการ การโหลดและแสดงผลอาจทำให้หน้าเว็บทำงานช้าหรือค้างได้ คุณแน่ใจหรือไม่ว่าต้องการเปิดดูรายละเอียด?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ยืนยันโหลดข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    processViewDetail(receiptNo, receiptTypeDetail);
                }
            });
        } else {
            processViewDetail(receiptNo, receiptTypeDetail);
        }
    }

    function processViewDetail(receiptNo, receiptTypeDetail) {
        if(receiptNo === '-') {
            $('#modalTitle').html(`<div class="d-flex align-items-center gap-3"><div style="width:40px;height:40px;border-radius:10px;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:22px;"><i class="bx bx-list-ul"></i></div><div><h5 class="modal-title mb-0 fw-bold text-dark">รายละเอียดลูกหนี้ (ยังไม่มีใบเสร็จ)</h5><span class="badge-bill-type badge-bill-single mt-1">${receiptTypeDetail}</span></div></div>`);
        } else {
            $('#modalTitle').html(`<div class="d-flex align-items-center gap-3"><div style="width:40px;height:40px;border-radius:10px;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:22px;"><i class="bx bx-list-ul"></i></div><div><h5 class="modal-title mb-0 fw-bold text-dark">รายละเอียดลูกหนี้ ใบเสร็จเลขที่: <span class="text-primary">${receiptNo}</span></h5><span class="badge-bill-type badge-bill-stm mt-1">${receiptTypeDetail}</span></div></div>`);
        }
        
        let startDate = $('#startDate').val();
        let endDate = $('#endDate').val();
        let receiptType = $('#receiptType').val();
        
        $('#detailModal').modal('show');
        $('#modalLoadingOverlay').css('display', 'flex');
        $('#modalBodyContent').empty();
        $('#modalTotalDebit').text('0.00');
        $('#modalTotalPaid').text('0.00');
        $('#modalTotalBalance').text('0.00');
        $('#modalTotalCompensate').text('0.00');
        $('#modalTotalDiff').text('0.00');

        $.ajax({
            url: 'ajax_get_receipt_report.php',
            type: 'POST',
            data: {
                action: 'detail',
                receiptNo: receiptNo,
                startDate: startDate,
                endDate: endDate,
                receiptType: receiptType,
                receiptTypeDetail: receiptTypeDetail
            },
            dataType: 'json',
            success: function(res) {
                $('#modalLoadingOverlay').hide();
                let tbody = $('#modalBodyContent');

                if(res.status === 'success' && res.data.length > 0) {
                    let html = '';
                    let sumDebit = 0, sumPaid = 0, sumCompensate = 0, sumBalance = 0, sumDiff = 0;
                    $.each(res.data, function(i, item) {
                        html += `<tr>
                            <td class="text-center">${i+1}</td>
                            <td class="text-center">${item.srv_date}</td>
                            <td class="text-nowrap">${item.ptname}</td>
                            <td class="text-nowrap">${item.accountname}</td>
                            <td class="text-end text-debt">${parseFloat(item.debit_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-compensate">${parseFloat(item.compensate_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-primary fw-bold">${parseFloat(item.compensate_amount - item.debit_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-center text-nowrap">${item.receipt_no}</td>
                            <td class="text-center">${item.receipt_date}</td>
                        </tr>`;
                        sumDebit += parseFloat(item.debit_amount);
                        sumPaid += parseFloat(item.paid_amount);
                        sumBalance += parseFloat(item.balance_amount);
                        sumCompensate += parseFloat(item.compensate_amount);
                        sumDiff += parseFloat(item.compensate_amount - item.debit_amount);
                    });
                    tbody.html(html);
                    $('#modalTotalDebit').text(sumDebit.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#modalTotalPaid').text(sumPaid.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#modalTotalBalance').text(sumDebit.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#modalTotalCompensate').text(sumCompensate.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#modalTotalDiff').text(sumDiff.toLocaleString('en-US', {minimumFractionDigits: 2}));
                } else {
                    tbody.html(`<tr><td colspan="11" class="text-center text-muted py-4">ไม่พบข้อมูล</td></tr>`);
                }
            },
            error: function(err) {
                $('#modalLoadingOverlay').hide();
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการดึงข้อมูลรายละเอียด', 'error');
                console.error(err);
            }
        });
    }
</script>
</body>
</html>
