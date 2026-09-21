<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
mysqli_set_charset($conn, 'utf8mb4');

if(isset($_SESSION['fullname']) ){
  $_SESSION['fullname'] = $_SESSION['fullname']; 
}else{
  $_SESSION['fullname'] = ""; 
}
if(isset($_SESSION['role']) ){
  $_SESSION['role'] = $_SESSION['role'];
}else{
  $_SESSION['role'] = ""; 
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
      href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
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
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../assets/js/config.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>


    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- ภาษาไทย -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr@4.6.13/dist/l10n/th.js"></script>

    
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
.d-none {
  display: none;
}

.d-block {
  display: block;
}

/* Absolute Center Spinner */
.loading {
  position: fixed;
  z-index: 999;
  height: 2em;
  width: 2em;
  overflow: visible;
  margin: auto;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
}

/* Transparent Overlay */
.loading:before {
  content: "";
  display: block;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.3);
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

      /* ซ่อนปุ่ม Floating Logout อัตโนมัติเมื่อเปิด Modal */
      body.modal-open .floating-logout-btn,
      .modal.show ~ .floating-logout-btn,
      .modal.in ~ .floating-logout-btn {
          display: none !important;
      }

/* ดัน SweetAlert ให้แสดงผลทับทุกอย่าง */
.swal2-container {
  z-index: 20000 !important; 
}

/* =========================================================================
   🏢 Enterprise Healthcare / SaaS Modal & Data Grid Design System
   ========================================================================= */
:root {
  --edhs-primary: #1e3a8a;
  --edhs-primary-dark: #0f172a;
  --edhs-accent: #2563eb;
  --edhs-accent-light: #eff6ff;
  --edhs-teal: #0d9488;
  --edhs-success: #059669;
  --edhs-warning: #d97706;
  --edhs-danger: #dc2626;
  --edhs-slate-50: #f8fafc;
  --edhs-slate-100: #f1f5f9;
  --edhs-slate-200: #e2e8f0;
  --edhs-slate-300: #cbd5e1;
  --edhs-slate-600: #475569;
  --edhs-slate-700: #334155;
  --edhs-slate-800: #1e293b;
  --edhs-slate-900: #0f172a;
}

/* Modal Frame & Backdrop - Force True Fullscreen 100vh × 100vw */
#myModalget.modal {
  padding: 0 !important;
  overflow: hidden !important;
  height: 100vh !important;
  width: 100vw !important;
}

#myModalget .modal-dialog,
#myModalget .modal-dialog.modal-fullscreen {
  width: 100vw !important;
  max-width: 100vw !important;
  height: 100vh !important;
  min-height: 100vh !important;
  max-height: 100vh !important;
  margin: 0 !important;
  padding: 0 !important;
  display: flex !important;
  flex-direction: column !important;
  flex: 1 1 100% !important;
}

#myModalget .modal-content {
  border: none !important;
  border-radius: 0 !important;
  background-color: #f8fafc !important;
  display: flex !important;
  flex-direction: column !important;
  height: 100vh !important;
  min-height: 100vh !important;
  max-height: 100vh !important;
  width: 100vw !important;
  overflow: hidden !important;
}

#myModalget .modal-header-luxury {
  flex-shrink: 0 !important;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e3a8a 100%);
  color: #ffffff;
  padding: 14px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.25);
  position: relative;
  z-index: 100;
}

.modal-header-luxury .brand-badge {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 24px;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
  flex-shrink: 0;
}

.modal-header-luxury .title-group {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.modal-header-luxury .title-group h5 {
  margin: 0;
  font-weight: 700;
  font-size: 1.15rem;
  letter-spacing: -0.2px;
  color: #ffffff;
  white-space: nowrap;
}

.modal-header-luxury .title-group .subtitle-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #93c5fd;
  font-size: 13px;
  padding: 5px 14px;
  border-radius: 20px;
  margin-top: 0;
  font-weight: 600;
  white-space: nowrap;
}

.btn-close-luxury {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 20px;
}
.btn-close-luxury:hover {
  background: rgba(239, 68, 68, 0.85);
  border-color: rgba(239, 68, 68, 0.85);
  transform: rotate(90deg);
  color: #fff;
}

#myModalget .modal-body {
  flex: 1 1 auto !important;
  display: flex !important;
  flex-direction: column !important;
  overflow: hidden !important;
  min-height: 0 !important;
  padding: 12px 18px !important;
}

/* Executive KPI Cards Bar */
.modal-kpi-bar {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px;
  margin-bottom: 12px;
  flex-shrink: 0 !important;
}

@media (max-width: 1400px) {
  .modal-kpi-bar {
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  }
}

.kpi-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

.kpi-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  border-radius: 12px 0 0 12px;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
}

.kpi-card.kpi-total::before { background: #2563eb; }
.kpi-card.kpi-income::before { background: #6366f1; }
.kpi-card.kpi-paid::before { background: #64748b; }
.kpi-card.kpi-debit::before { background: #0027e5; }
.kpi-card.kpi-stm::before { background: #34b300; }
.kpi-card.kpi-dckd::before { background: #34b300; }
.kpi-card.kpi-diff::before { background: #ff6d07; }

.kpi-icon-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.kpi-total .kpi-icon-box { background: #eff6ff; color: #2563eb; }
.kpi-income .kpi-icon-box { background: #eef2ff; color: #6366f1; }
.kpi-paid .kpi-icon-box { background: #f1f5f9; color: #64748b; }
.kpi-debit .kpi-icon-box { background: #eff6ff; color: #0027e5; }
.kpi-stm .kpi-icon-box { background: #ecfdf5; color: #34b300; }
.kpi-dckd .kpi-icon-box { background: #ecfdf5; color: #34b300; }
.kpi-diff .kpi-icon-box { background: #fff7ed; color: #ff6d07; }

.kpi-info {
  flex-grow: 1;
  min-width: 0;
}

.kpi-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.kpi-value {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}

.kpi-total .kpi-value { color: #2563eb !important; }
.kpi-debit .kpi-value { color: #0027e5 !important; }
.kpi-stm .kpi-value { color: #34b300 !important; }
.kpi-dckd .kpi-value { color: #34b300 !important; }
.kpi-diff .kpi-value { color: #ff6d07 !important; }

/* Search & Filter Toolbar */
.modal-search-toolbar {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 14px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  flex-shrink: 0 !important;
}

.search-input-group {
  position: relative;
  flex-grow: 1;
  max-width: 550px;
}

.search-input-group i.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 18px;
  pointer-events: none;
}

.search-input-group input#myInputModal {
  width: 100%;
  height: 40px;
  padding: 8px 38px 8px 40px;
  border: 1.5px solid #cbd5e1;
  border-radius: 9px;
  font-size: 14px;
  background-color: #f8fafc;
  color: #1e293b;
  transition: all 0.2s ease;
  margin-bottom: 0;
  background-image: none !important;
}

.search-input-group input#myInputModal:focus {
  background-color: #ffffff;
  border-color: #3b82f6;
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.search-clear-btn {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: #cbd5e1;
  border: none;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: none;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: #475569;
  cursor: pointer;
}
.search-clear-btn:hover {
  background: #94a3b8;
  color: #fff;
}

.filter-badge-info {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
  background: #f1f5f9;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

/* Modal Table Container */
#myModalget #data_tabel_stm {
  flex: 1 1 0 !important;
  display: flex !important;
  flex-direction: column !important;
  min-height: 0 !important;
  height: 100% !important;
  overflow: hidden !important;
}

#myModalget .table-scroll {
  flex: 1 1 0 !important;
  min-height: 0 !important;
  height: 100% !important;
  max-height: 100% !important;
  overflow-y: auto !important;
  overflow-x: auto !important;
  display: block !important;
  width: 100% !important;
  position: relative !important;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

#myModalget .table-scroll::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
#myModalget .table-scroll::-webkit-scrollbar-track {
  background: #f1f5f9;
}
#myModalget .table-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
#myModalget .table-scroll::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.stmgetovlookup {
  width: max-content !important;
  min-width: 100% !important;
  min-height: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
  margin-bottom: 0 !important;
  font-size: 13.5px;
  table-layout: auto !important;
}

/* Header Row 1 (Super Header) */
.stmgetovlookup thead tr:nth-child(1) th {
  position: sticky !important;
  top: 0px !important;
  background: linear-gradient(268deg, #2a3d5d 0%, #1e2b4b 100%) !important;
  color: #ffffff !important;
  z-index: 20 !important;
  padding: 10px 16px !important;
  font-weight: 600;
  font-size: 14px;
  border: none !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
  white-space: nowrap !important;
}

/* Header Row 2 (Column Headers) */
.stmgetovlookup thead tr:nth-child(2) th {
  position: sticky !important;
  top: 41px !important;
  background: #f8fafc !important;
  color: #334155 !important;
  font-weight: 700 !important;
  font-size: 12.5px !important;
  letter-spacing: 0.2px;
  padding: 10px 14px !important;
  z-index: 19 !important;
  border-top: none !important;
  border-bottom: 2px solid #cbd5e1 !important;
  border-right: 1px solid #e2e8f0 !important;
  white-space: nowrap !important;
  text-align: center !important;
}

.stmgetovlookup th.sortable-header {
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}
.stmgetovlookup th.sortable-header:hover {
  background-color: #e2e8f0 !important;
  color: #1e40af !important;
}

.stmgetovlookup th.sortable-header::after {
  content: ' ⇅';
  font-size: 11px;
  opacity: 0.4;
  margin-left: 4px;
}
.stmgetovlookup th.sortable-header.sort-asc::after {
  content: ' ▲';
  opacity: 1;
  color: #2563eb;
}
.stmgetovlookup th.sortable-header.sort-desc::after {
  content: ' ▼';
  opacity: 1;
  color: #2563eb;
}

/* Table Body Rows */
.stmgetovlookup tbody tr {
  transition: background-color 0.15s ease;
}

.stmgetovlookup tbody tr.main-patient-row,
.stmgetovlookup tbody tr.sub-payment-row {
  height: 1px !important;
}

.stmgetovlookup tbody tr.spacer-row {
  height: auto !important;
  background: transparent !important;
}
.stmgetovlookup tbody tr.spacer-row td {
  border-top: none !important;
  border-bottom: none !important;
  border-right: 1px solid #f1f5f9 !important;
  padding: 0 !important;
  background: transparent !important;
  height: auto !important;
  pointer-events: none;
}
.stmgetovlookup tbody tr.spacer-row td:first-child {
  border-left: 1px solid #f1f5f9 !important;
}

.stmgetovlookup tbody tr.main-patient-row:hover {
  background-color: #f0f7ff !important;
}

.stmgetovlookup tbody td {
  padding: 9px 14px !important;
  vertical-align: middle !important;
  border-bottom: 1px solid #f1f5f9 !important;
  border-right: 1px solid #f1f5f9 !important;
  color: #1e293b;
  font-variant-numeric: tabular-nums;
  white-space: nowrap !important;
}

/* Summary Footer Row (รวมทั้งสิ้น) */
.stmgetovlookup tfoot,
.stmgetovlookup tfoot tr,
.stmgetovlookup tfoot tr.summary-total-row,
.stmgetovlookup tr.summary-total-row,
.stmgetovlookup tbody tr.summary-total-row,
.stmgetovlookup tbody tr:last-child.summary-total-row {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 25 !important;
  background: linear-gradient(268deg, #2a3d5d 0%, #1e2b4b 100%) !important;
  box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.25) !important;
}

.stmgetovlookup tfoot td,
.stmgetovlookup tfoot tr td,
.stmgetovlookup tr.summary-total-row td,
.stmgetovlookup tbody tr.summary-total-row td,
.stmgetovlookup tbody tr:last-child.summary-total-row td {
  background: transparent !important;
  color: #ffffff !important;
  border-top: 1px solid rgba(255, 255, 255, 0.2) !important;
  border-bottom: none !important;
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
  padding: 12px 14px !important;
  font-size: 14.5px !important;
  font-weight: 700 !important;
  vertical-align: middle !important;
  white-space: nowrap !important;
}

.stmgetovlookup tfoot td:first-child,
.stmgetovlookup tr.summary-total-row td:first-child,
.stmgetovlookup tbody tr.summary-total-row td:first-child {
  color: #ffffff !important;
  font-size: 15px !important;
  letter-spacing: 0.5px;
}

.stmgetovlookup tfoot td *,
.stmgetovlookup tr.summary-total-row td * {
  color: #ffffff !important;
}

/* Modal Bottom Footer */
.modal-footer-luxury {
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  padding: 10px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.03);
  flex-shrink: 0 !important;
}

.footer-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 20px;
  font-size: 14px;
  font-weight: 600;
  border-radius: 9px;
  border: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  text-decoration: none;
  cursor: pointer;
}
.footer-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-export-excel {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #ffffff !important;
}
.btn-export-excel:hover {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.btn-modal-dismiss {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
  padding: 9px 20px;
  font-size: 14px;
  font-weight: 600;
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
  cursor: pointer;
}
.btn-modal-dismiss:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  color: #0f172a;
}

/* =========================================================
   🌟 CR Subgroup Dynamic Tabs Styling
   ========================================================= */
.cr-tabs-container {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 12px 16px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}

.cr-subgroup-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0;
  padding: 0;
}

.cr-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 20px;
  border: 1px solid #cbd5e1;
  background: #f8fafc;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  user-select: none;
}

.cr-tab-btn:hover {
  background: #e2e8f0;
  border-color: #94a3b8;
  color: #1e293b;
  transform: translateY(-1px);
}

.cr-tab-btn.active {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%) !important;
  color: #ffffff !important;
  border-color: #1e40af !important;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
}

.cr-tab-btn .badge-pill-count {
  font-size: 11.5px;
  padding: 2px 7px;
  border-radius: 10px;
  background: #e2e8f0;
  color: #334155;
  font-weight: 700;
}

.cr-tab-btn.active .badge-pill-count {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

/* =========================================================================
   🌟 Modern Month Filter Card for EMS & Form UI
   ========================================================================= */
.ems-filter-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #0004a1 !important;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 22px 26px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}
.ems-filter-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.ems-card-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 14px;
  margin-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
}

.ems-header-icon-filter {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
  color: #0004a1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 2px 8px rgba(0, 4, 161, 0.2);
  flex-shrink: 0;
}

.ems-filter-select {
  border-radius: 10px !important;
  border: 1.5px solid #cbd5e1 !important;
  padding: 11px 16px !important;
  font-size: 14.5px !important;
  font-weight: 600 !important;
  color: #1e293b !important;
  background-color: #f8fafc !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
  transition: all 0.2s ease !important;
  font-family: 'Noto Sans Thai', sans-serif !important;
}

.ems-filter-select:focus {
  background-color: #ffffff !important;
  border-color: #0004a1 !important;
  box-shadow: 0 0 0 3px rgba(0, 4, 161, 0.12) !important;
  outline: none !important;
}

.ems-section-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}
.ems-section-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.ems-form-box {
  background: linear-gradient(135deg, #fffdf7 0%, #ffffff 50%, #fffbeb 100%);
  border: 1px solid #fde68a;
  border-left: 5px solid #f59e0b !important;
  border-radius: 14px;
  padding: 24px 28px;
  box-shadow: 0 4px 16px rgba(245, 158, 11, 0.07);
  position: relative;
  overflow: hidden;
}

.ems-box-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 16px;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(245, 158, 11, 0.15);
}

.ems-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.ems-header-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #b45309;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
  flex-shrink: 0;
}

.ems-header-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.ems-header-subtitle {
  font-size: 0.82rem;
  color: #64748b;
  margin: 2px 0 0 0;
}

.ems-pill-badge-warning {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: #fffbeb;
  color: #92400e;
  border: 1px solid #fcd34d;
}

.ems-pill-badge-info {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: #ffffff;
  color: #1e40af;
  border: 1px solid #bfdbfe;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.ems-field-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.ems-input, .ems-select {
  border-radius: 10px !important;
  border: 1px solid #cbd5e1 !important;
  padding: 9px 14px !important;
  font-size: 13.5px !important;
  color: #1e293b !important;
  background-color: #ffffff !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.2s ease !important;
}

.ems-input:focus, .ems-select:focus {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18) !important;
  background-color: #ffffff !important;
}

.ems-input[readonly] {
  background-color: #f8fafc !important;
  color: #64748b !important;
}

.ems-action-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px dashed rgba(245, 158, 11, 0.2);
}

.btn-ems-submit {
  background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%) !important;
  color: #ffffff !important;
  font-weight: 600 !important;
  font-size: 14px !important;
  padding: 10px 26px !important;
  border-radius: 10px !important;
  border: none !important;
  box-shadow: 0 3px 12px rgba(30, 58, 138, 0.35) !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
  cursor: pointer;
}
.btn-ems-submit:hover {
  background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%) !important;
  transform: translateY(-1px);
  box-shadow: 0 5px 16px rgba(30, 58, 138, 0.45) !important;
  color: #ffffff !important;
}

.btn-ems-reset {
  background: #ffffff !important;
  color: #64748b !important;
  border: 1px solid #cbd5e1 !important;
  font-weight: 600 !important;
  font-size: 13.5px !important;
  padding: 9px 20px !important;
  border-radius: 10px !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  transition: all 0.2s ease !important;
}
.btn-ems-reset:hover {
  background: #f1f5f9 !important;
  color: #334155 !important;
  border-color: #94a3b8 !important;
}

/* jQuery UI Autocomplete dropdown styling */
.ui-autocomplete {
  border-radius: 10px !important;
  box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.12), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
  border: 1px solid #e2e8f0 !important;
  padding: 6px !important;
  background: #ffffff !important;
  z-index: 1055 !important;
}
.ui-menu-item-wrapper {
  padding: 8px 12px !important;
  font-size: 13.5px !important;
  border-radius: 8px !important;
  transition: all 0.15s ease !important;
}
.ui-menu-item-wrapper.ui-state-active {
  background: #fef3c7 !important;
  color: #92400e !important;
  border: none !important;
  font-weight: 600 !important;
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
                <div class="col-12">
                  <!-- Card ตัวกรองข้อมูลลูกหนี้ประจำเดือน -->
                  <div class="ems-filter-card">
                    <div class="ems-card-header-bar">
                      <div class="ems-header-left">
                        <div class="ems-header-icon-filter">
                          <i class='bx bx-calendar-check'></i>
                        </div>
                        <div>
                          <h5 class="ems-header-title" style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.3;">ข้อมูลประจำเดือน</h5>
                          <p class="ems-header-subtitle" style="font-size: 0.8rem; color: #64748b; margin: 0;">เลือกข้อมูลรอบเดือนเพื่อตรวจสอบรายการและนำเข้าลูกหนี้ EMS</p>
                        </div>
                      </div>
                      <div>
                        <span class="ems-pill-badge" style="color: #0004a1; border: 1px solid #c7d2fe; background: #eef2ff; display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                          <i class='bx bx-time-five' style="color: #0004a1;"></i> เลือกรอบเดือน
                        </span>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <label class="ems-field-label" style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; display: flex; align-items: center; gap: 5px;"><i class='bx bx-calendar' style="color: #0004a1;"></i> ข้อมูลประจำเดือน</label>
                        <select id="selectTypeOpt" name="selectTypeOpt" class="form-select ems-filter-select">
                          <?php 
                          $latestMonth = '';

                          // ดึงเดือนล่าสุดมาก่อน
                          $sqlLatest = "SELECT monthtxt
                                        FROM imr_tb_debtor_rights_opd
                                        GROUP BY monthtxt
                                        ORDER BY STR_TO_DATE(monthtxt, '%m-%Y') DESC
                                        LIMIT 1";
                          $resultLatest = mysqli_query($conn, $sqlLatest);
                          if ($rowLatest = mysqli_fetch_assoc($resultLatest)) {
                            $latestMonth = $rowLatest['monthtxt'];
                          }

                          // ดึงรายการเดือนทั้งหมด
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

                    <div class="col-12">


                      <!-- Card ฟอร์มเพิ่มข้อมูลผู้ป่วยนอก ลูกหนี้ EMS -->
                      <div class="ems-section-card">
                        <div class="ems-form-box">
                          
                          <!-- Header -->
                          <div class="ems-box-header">
                            <div class="ems-header-left">
                              <div class="ems-header-icon">
                                <i class='bx bxs-ambulance'></i>
                              </div>
                              <div>
                                <h5 class="ems-header-title">ฟอร์มเพิ่มข้อมูลผู้ป่วยนอก ลูกหนี้ - ระบบปฏิบัติการฉุกเฉิน EMS</h5>
                                <p class="ems-header-subtitle">บันทึกข้อมูลบริการการแพทย์ฉุกเฉินและส่งยอดตั้งหนี้เข้าระบบ (รหัสผังบัญชี 1102050101.109)</p>
                              </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                              <span class="ems-pill-badge-warning">
                                <i class='bx bx-pulse text-danger me-1'></i> EMS Emergency Service
                              </span>
                              <span class="ems-pill-badge-info">
                                <i class='bx bx-search-alt text-primary me-1'></i> ค้นหาประวัติอัตโนมัติ (HN / CID / ชื่อ)
                              </span>
                            </div>
                          </div>

                          <form action="insert_ems.php" method="POST" id="ems_insert_form">

                            <div class="row g-3 mb-3">
                              <div class="col-md-3">
                                <label class="ems-field-label"><i class='bx bx-calendar text-warning'></i> วันที่รับบริการ <span class="text-danger">*</span></label>
                                <input type="text" name="vstdate" id="vstdate" class="form-control ems-input" required placeholder="เลือกวันที่..."> 
                              </div>
                              <div class="col-md-3">
                                <label class="ems-field-label"><i class='bx bx-time-five text-warning'></i> เวลารับบริการ <span class="text-danger">*</span></label>
                                <input type="time" name="vsttime" id="vsttime" class="form-control ems-input" required readonly> 
                              </div>

                              <div class="col-md-3">
                                <label class="ems-field-label"><i class='bx bx-user text-warning'></i> ชื่อ-สกุล <span class="text-danger">*</span></label>
                                <input type="text" id="ptname" name="ptname" class="form-control ems-input" autocomplete="off" required placeholder="พิมพ์ชื่อเพื่อค้นหา...">
                              </div>

                              <div class="col-md-3">
                                <label class="ems-field-label"><i class='bx bx-id-card text-warning'></i> HN ผู้ป่วย <span class="text-danger">*</span></label>
                                <input type="text" name="hn" class="form-control ems-input" required placeholder="พิมพ์ HN เพื่อค้นหา...">
                              </div>

                              <div class="col-md-3">
                                <label class="ems-field-label"><i class='bx bx-credit-card-front text-warning'></i> เลขบัตร ปชช. (CID) <span class="text-danger">*</span></label>
                                <input type="text" name="cid" class="form-control ems-input" required placeholder="เลขบัตร 13 หลัก...">
                              </div>
                             
                              <div class="col-md-3">
                                <label class="ems-field-label"><i class='bx bx-male-female text-warning'></i> เพศ <span class="text-danger">*</span></label>
                                <select name="sex" class="form-select ems-select" required>
                                  <option value="">-- กรุณาเลือกเพศ --</option>
                                  <option value="ชาย">ชาย</option>
                                  <option value="หญิง">หญิง</option>
                                </select>
                              </div>

                              <div class="col-md-3">
                                <label class="ems-field-label"><i class='bx bx-cake text-warning'></i> อายุ (ปี) <span class="text-danger">*</span></label>
                                <input type="text" name="age" class="form-control ems-input" required placeholder="ระบุอายุ...">
                              </div>

                              <div class="col-md-3">
                                <label class="ems-field-label"><i class='bx bx-money text-warning'></i> ค่าใช้จ่าย (บาท) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="income" class="form-control ems-input" required placeholder="0.00">
                              </div>

                              <div class="col-md-3" style="display:none;">
                                <label>VN</label>
                                <input type="text" id="vn_display" class="form-control" readonly>
                              </div>
                              <div class="col-md-3" style="display:none;">
                                <label>เดือน (monthtxt)</label>
                                <input type="text" id="monthtxt_display" class="form-control" readonly>
                              </div>
                            </div>

                            <!-- Hidden fields -->
                            <input type="hidden" name="vn" id="vn">
                            <input type="hidden" name="monthtxt" id="monthtxt">
                            <input type="hidden" name="accountcode" value="1102050101.109">
                            <input type="hidden" name="accountname" value="ลูกหนี้ - ระบบปฏิบัติการฉุกเฉิน">

                            <!-- Action Bar -->
                            <div class="ems-action-bar">
                              <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 13px;">
                                <i class='bx bx-check-shield text-success' style="font-size: 18px;"></i>
                                <span>บันทึกตั้งหนี้อัตโนมัติเข้าผังบัญชี <strong>1102050101.109</strong> (ลูกหนี้ - ระบบปฏิบัติการฉุกเฉิน)</span>
                              </div>
                              <div class="d-flex align-items-center gap-2">
                                <button type="reset" class="btn btn-ems-reset">
                                  <i class='bx bx-refresh'></i> ล้างฟอร์ม
                                </button>
                                <button type="submit" class="btn btn-ems-submit">
                                  <i class='bx bx-save'></i> บันทึกข้อมูล EMS
                                </button>
                              </div>
                            </div>

                          </form>
                        </div>
                        <script>
                          
                        // คำนวณวันที่ย้อนหลัง 1 เดือน
                        var prevMonthDate = new Date();
                        prevMonthDate.setMonth(prevMonthDate.getMonth() - 1);

                        flatpickr("#vstdate", {
                          dateFormat: "d/m/Y",
                          locale: "th",
                          defaultDate: prevMonthDate,
                          onReady(selectedDates, dateStr, instance) {
                            adjustBuddhistYear(instance);
                          },
                          onChange(selectedDates, dateStr, instance) {
                            adjustBuddhistYear(instance);
                          }
                        });

                        function adjustBuddhistYear(instance) {
                          const selectedDate = instance.selectedDates[0];
                          if (selectedDate) {
                            const buddhistYear = selectedDate.getFullYear() + 543;
                            const day = selectedDate.getDate().toString().padStart(2, '0');
                            const month = (selectedDate.getMonth() + 1).toString().padStart(2, '0');
                            instance._input.value = `${day}/${month}/${buddhistYear}`;
                          }
                        }

                        document.addEventListener("DOMContentLoaded", function () {
                          const cidInput = document.querySelector("[name='cid']");
                          if (cidInput) {
                            cidInput.addEventListener("input", function () {
                              this.value = this.value.replace(/-/g, "");
                            });
                          }
                        });


                        document.getElementById("vstdate").addEventListener("change", function () {
                          const vstdateRaw = this.value; // เช่น "01/08/2568"

                          if (vstdateRaw) {
                            const [day, month, buddhistYear] = vstdateRaw.split("/");
                            const adYear = parseInt(buddhistYear) - 543;
                            const vstdateISO = `${adYear}-${month}-${day}`;
                            const dateObj = new Date(vstdateISO);

                            // เวลาปัจจุบัน
                            const now = new Date();
                            const hh = String(now.getHours()).padStart(2, '0');
                            const mm = String(now.getMinutes()).padStart(2, '0');
                            const ss = String(now.getSeconds()).padStart(2, '0');
                            const vsttime = `${hh}:${mm}:${ss}`;
                            document.getElementById("vsttime").value = vsttime;

                            // VN: 2 หลักท้ายของ พ.ศ. + MM + DD + HHMMSS
                            const buddhistYearShort = String(parseInt(buddhistYear)).slice(-2);
                            const monthStr = month.padStart(2, '0');
                            const dayStr = day.padStart(2, '0');
                            const timePart = vsttime.replace(/:/g, '');
                            const vn = buddhistYearShort + monthStr + dayStr + timePart;

                            // monthtxt = MM-YYYY (ค.ศ.)
                            const monthtxt = `${parseInt(month)}-${adYear}`;

                            // แสดงผล
                            document.getElementById("vn_display").value = vn;
                            document.getElementById("monthtxt_display").value = monthtxt;
                            document.getElementById("vn").value = vn;
                            document.getElementById("monthtxt").value = monthtxt;
                          }
                        });
                        </script>

                    <!-- Modern Loading State for Debtor Table -->
                    <div id="tbopdload" class="card shadow-sm border-0 mt-4 mb-4" style="border-radius: 16px; background: #ffffff; border-left: 5px solid #0d9488 !important; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06);">
                      <div class="card-body p-4 text-center">
                        <div class="d-flex align-items-center justify-content-center gap-3 py-3">
                          <div class="spinner-border text-primary" role="status" style="width: 2.4rem; height: 2.4rem; border-width: 0.25em;">
                            <span class="visually-hidden">กำลังโหลด...</span>
                          </div>
                          <div class="text-start">
                            <h5 class="mb-1 fw-bold text-dark" style="font-size: 1.05rem;">กำลังโหลดข้อมูลตารางลูกหนี้...</h5>
                            <p class="mb-0 text-muted" style="font-size: 0.85rem;">กรุณารอสักครู่ ระบบกำลังประมวลผลข้อมูลและคำนวณยอดชดเชย</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div id="data_tabel_opd" class="mt-4"></div>
                    <div id="data_tabel_ipd" class="mt-4" style="display: none;"></div>
                  </div>
                </div>


              </div>
            

            <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-fluid" style="padding: 0px;">
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



<!-- Modal Structure -->
<div class="modal fade" id="proGLoading" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" style="margin-top: 18%;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="myModalLabel">รอสักครู่กำลังโหลดข้อมูล <img src="./images/load.gif" alt="" style="width:10%;" /></h5>
      </div>
      <div class="modal-body">
        <!-- Progress Bar -->
        <div class="progress">
          <div id="progressBar" class="progress-bar progress-bar-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

      </div>
    </div>
  </div>
</div>



    <!-- =========================================================================
         🏢 The Modal: ข้อมูลลูกหนี้รายตัว ตามสิทธิการเงิน (Enterprise Hospital Edition)
         ========================================================================= -->
    <div class="modal fade" id="myModalget" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">

          <!-- Modal Header -->
          <div class="modal-header-luxury">
            <div class="d-flex align-items-center gap-3">
              <div class="brand-badge">
                <i class='bx bx-id-card'></i>
              </div>
              <div class="title-group">
                <h5 id="modal_dept_heading">รายละเอียดลูกหนี้รายตัว</h5>
                <div class="subtitle-pill" id="titlestm">
                  📑 รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn-close-luxury ms-2" data-bs-dismiss="modal" onclick="$('#myInputModal').val('').trigger('keyup');" title="ปิดหน้าต่าง">
                <i class='bx bx-x'></i>
              </button>
            </div>
          </div>

          <!-- Modal body -->
          <div class="modal-body">
            <!-- Executive KPI Summary Cards Banner -->
            <div class="modal-kpi-bar">
              <div class="kpi-card kpi-total">
                <div class="kpi-icon-box">
                  <i class='bx bx-user-pin'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">จำนวนลูกหนี้ทั้งหมด</div>
                  <div class="kpi-value" id="txtq0">0 คน</div>
                </div>
              </div>

              <div class="kpi-card kpi-income">
                <div class="kpi-icon-box">
                  <i class='bx bx-receipt'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ค่าใช้จ่ายรวม</div>
                  <div class="kpi-value" id="txtq_income">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-paid">
                <div class="kpi-icon-box">
                  <i class='bx bx-check-circle'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ชำระแล้ว</div>
                  <div class="kpi-value" id="txtq_paid">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-debit">
                <div class="kpi-icon-box">
                  <i class='bx bx-coin-stack'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ภาระหนี้รวม (Debit)</div>
                  <div class="kpi-value" id="txtq1">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-stm">
                <div class="kpi-icon-box">
                  <i class='bx bx-check-shield'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ชดเชย STM รวม</div>
                  <div class="kpi-value" id="txtq2">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-dckd">
                <div class="kpi-icon-box">
                  <i class='bx bx-wallet-alt'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ชดเชย DCKD รวม</div>
                  <div class="kpi-value" id="txtq4">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-diff">
                <div class="kpi-icon-box">
                  <i class='bx bx-error-alt'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ส่วนต่างคงเหลือ</div>
                  <div class="kpi-value" id="txtq3">0.00</div>
                </div>
              </div>
            </div>

            <!-- Search & Filter Toolbar -->
            <div class="modal-search-toolbar">
              <div class="search-input-group">
                <i class='bx bx-search search-icon'></i>
                <input id="myInputModal" type="text" placeholder="ค้นหาด้วย ชื่อ-สกุล, CID, VN/AN, เลขหนังสือ/บิล..." autocomplete="off">
                <button type="button" class="search-clear-btn" id="clearSearchModalBtn" onclick="$('#myInputModal').val('').trigger('keyup').focus();">
                  <i class='bx bx-x'></i>
                </button>
              </div>

              <div class="d-flex align-items-center gap-2">
                <div class="filter-badge-info">
                  <i class='bx bx-list-ul text-primary'></i>
                  <span id="filtered_row_count_stm">กำลังแสดงทุกรายการ</span>
                </div>
                <button id="exportstmgetovlookup" class="footer-btn btn-export-excel" style="height: 38px; padding: 6px 16px; font-size: 13.5px;">
                  <i class='bx bxs-file-export'></i> ส่งออก Excel (.xlsx)
                </button>
              </div>
            </div>

            <!-- 🌟 CR Subgroup Dynamic Navigation Tabs Container -->
            <div id="cr_subgroup_tabs_wrapper" class="cr-tabs-container mb-3" style="display: none;">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-primary px-2 py-1" id="cr_tabs_badge_title" style="font-size: 12.5px;"><i class='bx bx-category-alt me-1'></i> จำแนกกลุ่มย่อย CR</span>
                  <span class="text-muted small" id="cr_tabs_desc">คลิกเลือกแท็บเพื่อกรองดูรายการลูกหนี้เฉพาะกลุ่มย่อย</span>
                </div>
                <div class="text-muted small" id="cr_active_tab_info">
                  กำลังแสดง: <b class="text-primary" id="cr_current_tab_title">ทั้งหมด</b>
                </div>
              </div>
              <div class="cr-subgroup-nav" id="crSubgroupNav">
                <!-- Dynamically generated via JS -->
              </div>
            </div>

            <!-- Data Table Container -->
            <div id="data_tabel_stm">

            </div>
          </div>

          <!-- Modal footer -->
          <div class="modal-footer-luxury">
            <div class="d-flex align-items-center gap-2 text-muted small">
              <span><i class='bx bx-info-circle text-primary me-1'></i> ข้อมูลลูกหนี้รายตัวทั้งหมด</span>
            </div>
          </div>

        </div>
      </div>
    </div>


<!-- Modal สำหรับกรอกรหัสผ่าน -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ยืนยันรหัสผ่านก่อนลบข้อมูล</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body">
        <input type="password" id="delete_password" class="form-control" placeholder="กรอกรหัสผ่าน" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-danger" id="confirm_delete">ยืนยันลบ</button>
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
<a href="logout.php" class="floating-logout-btn" style="margin-bottom: 12px;">
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

    <!-- ต้องมี jQuery และ jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
        $(function() {
            // 💡 1. ฟังก์ชันตัวจัดการระบบดักจับ (สร้างเป็นฟังก์ชันสากลเพื่อเรียกซ้ำได้)
            function applyPatientAutocomplete(elementSelector) {
                $(elementSelector).autocomplete({
                  source: function(request, response) {
                    $.ajax({
                      url: "search_patient_ems.php",
                      method: "POST",
                      dataType: "json",
                      data: { term: request.term },
                      success: function(data) {
                        // ป้องกันข้อมูลซ้ำซ้อนระดับ Client-side (Deduplication)
                        var uniqueList = [];
                        var seenHn = {};
                        if (Array.isArray(data)) {
                          $.each(data, function(idx, item) {
                            if (item.hn && !seenHn[item.hn]) {
                              seenHn[item.hn] = true;
                              uniqueList.push(item);
                            }
                          });
                        }
                        response($.map(uniqueList, function(item) {
                          // กำหนดให้กล่องลิสต์แสดงผลเป็น "HN - ชื่อคนไข้"
                          return {
                            label: item.hn + " - " + item.ptname,
                            value: (elementSelector === "input[name='hn']") ? item.hn : ((elementSelector === "input[name='cid']") ? item.cid : item.ptname),
                            data: item
                          };
                        }));
                      }
                    });
                  },
                  minLength: 2,
                  focus: function(event, ui) {
                    return false; // ป้องกันการ overwrite ค่าในช่อง input ขณะใช้ลูกศรเลื่อนดูตัวเลือก
                  },
                  select: function(event, ui) {
                    const d = ui.item.data;
                    // กระจายข้อมูลลงช่องฟอร์มทั้งหมดโดยอัตโนมัติเมื่อกดเลือก
                    $("input[name='hn']").val(d.hn);
                    $("input[name='cid']").val(d.cid);
                    $("input[name='ptname']").val(d.ptname);
                    $("select[name='sex']").val(d.sex);
                    $("input[name='age']").val(d.age);
                    
                    if(elementSelector === "input[name='hn']") {
                        this.value = d.hn;
                    } else if(elementSelector === "input[name='cid']") {
                        this.value = d.cid;
                    } else if(elementSelector === "#ptname") {
                        this.value = d.ptname;
                    }
                    return false;
                  }
                });
            }

            // 💡 2. สั่งรันคำสั่ง Autocomplete ให้ทำงานพร้อมกันทั้ง 3 ช่องกรอกข้อมูล
            applyPatientAutocomplete("#ptname");          // ช่องชื่อผู้ป่วย
            applyPatientAutocomplete("input[name='hn']");  // ช่อง HN
            applyPatientAutocomplete("input[name='cid']"); // ช่องเลขบัตรประชาชน CID
          });
    </script>


    <script>
        document.getElementById('exportstmgetovlookup').addEventListener('click', function () {
            let table = document.querySelector('.stmgetovlookup');
            if (!table) return;
            let titleText = $('#titlestm').text().replace(/🏷️\s*รหัสผังบัญชี\s*/g, '').replace(/\|/g, '_').trim();
            if (typeof activeCrSubgroupFilter !== 'undefined' && activeCrSubgroupFilter && activeCrSubgroupFilter !== 'ALL') {
                titleText += '_' + activeCrSubgroupFilter;
            }
            let fileName = titleText ? 'ลูกหนี้_EMS_' + titleText.replace(/[\\/:*?"<>|]/g, '_') + '.xlsx' : 'ExportedData.xlsx';

            // Clone table และลบแถวที่ซ่อนอยู่ออกก่อนส่งออก Excel
            let cloneTable = table.cloneNode(true);
            let trs = cloneTable.querySelectorAll('tbody tr');
            trs.forEach(function(tr) {
                if (tr.style.display === 'none' || tr.classList.contains('spacer-row')) {
                    if (tr.parentNode) tr.parentNode.removeChild(tr);
                }
            });

            let workbook = XLSX.utils.book_new();
            let worksheet = XLSX.utils.table_to_sheet(cloneTable, { raw: true });
            XLSX.utils.book_append_sheet(workbook, worksheet, "Data");
            XLSX.writeFile(workbook, fileName);
        });
    </script>


    <script>

      $(document).ready(function(){
        var yyy = '<?php echo $yyy; ?>';      
        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        if (!isLoggedIn) {
            location.href = './login.php';
        }else{
          fetch_data_opd();
          fetch_data_ipd();
        }

      });


      function connmodal(){
        $('#connModal').modal('show');  
      }

      $(document).ready(function(){
        $("#myInput").on("keyup", function() {
          var value = $(this).val().toLowerCase();
          $("#data_tabel_nhso02 tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
          });
        });

        $("#statusoload").hide();
        $("#statusiload").hide();

      });




        function fetch_data_opd()
        { 
            $('#tbopdload').show();
            $('#data_tabel_opd').hide();

            $('#progressBar').css('width', '0%').attr('aria-valuenow', 0);
            var interval = setInterval(function() {
              var currentWidth = parseInt($('#progressBar').attr('aria-valuenow'));
              if (currentWidth < 90) { // Stop at 90% and let AJAX handle completion
                currentWidth += 10;
                $('#progressBar').css('width', currentWidth + '%').attr('aria-valuenow', currentWidth);
              } else {
                clearInterval(interval);
              }
            }, 500);

            
          var action = $('#selectTypeOpt').val();
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{action:action},
            success:function(data)
            {
              $('#progressBar').css('width', '100%').attr('aria-valuenow', 100);
              $('#data_tabel_listview').html(data);
              setTimeout(function() {
                $('#progressBar').css('width', '0%').attr('aria-valuenow', 0);
              }, 1000); 
              $('#tbopdload').hide();

              $('#data_tabel_opd').html(data).fadeIn(200);
            },
            error:function()
            {
              $('#tbopdload').html(`
                <div class="card-body p-4 text-center text-danger">
                  <i class='bx bx-error-circle' style="font-size: 2.2rem;"></i>
                  <h6 class="mt-2 text-danger fw-bold">เกิดข้อผิดพลาดในการโหลดข้อมูลตารางลูกหนี้</h6>
                  <small class="text-muted">โปรดตรวจสอบการเชื่อมต่อฐานข้อมูลหรือรีเฟรชหน้าใหม่อีกครั้ง</small>
                </div>
              `).show();
            }
          })
        }

        function fetch_data_ipd()
        { 
          $('#tbipdload').show();
          var action2 = $('#selectTypeOpt').val();
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{action2:action2},
            success:function(data)
            {
              $('#tbipdload').hide();
              $('#data_tabel_ipd').html(data);
            }
          })
        }


      function stmgeto(data,dt)
        { 
          // ใช้ SweetAlert แสดงสถานะกำลังโหลด
          Swal.fire({
              title: 'กำลังดึงข้อมูล...',
              html: 'รอสักครู่...',
              allowOutsideClick: false,
              scrollbarPadding: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          var stmgeto = data;
          var yyy = $('#selectTypeOpt').val();
          
          $('#modal_dept_heading').text('รายละเอียดข้อมูลลูกหนี้รายตัว (ผู้ป่วยนอก EMS)');
          $('#titlestm').html('🏷️ รหัสผังบัญชี ' + data + ' | ' + dt);

          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{stmgeto:stmgeto,yyy:yyy},
            success:function(data)
            {
              // ปิด SweetAlert เมื่อสำเร็จ
              Swal.close();

              $('#data_tabel_stm').html(data);

              // 🌟 สร้างแท็บจำแนกกลุ่มย่อย CR แบบ Dynamic (OPD)
              buildCrSubgroupTabs();

              var rowCount = $('#data_tabel_listview tr.main-patient-row, #data_tabel_listview_ipd tr.main-patient-row').length;
              if (rowCount === 0) {
                  rowCount = $('#data_tabel_listview tr, #data_tabel_listview_ipd tr').not('.summary-total-row, .sub-payment-row').length;
              }
              $('#txtq0').text(rowCount + ' คน');

              var o_inc = $('#data_tabel_listview').closest('table').find('tfoot tr.summary-total-row td').eq(1).text().trim();
              var o_paid = $('#data_tabel_listview').closest('table').find('tfoot tr.summary-total-row td').eq(2).text().trim();
              $('#txtq_income').text(o_inc ? o_inc : '0.00');
              $('#txtq_paid').text(o_paid ? o_paid : '0.00');

              var o1 = $('#o1').text().trim();
              $('#txtq1').text(o1 ? o1 : '0.00');

              var o2 = $('#o2').text().trim();
              $('#txtq2').text(o2 ? o2 : '0.00');

              var o4 = $('#o4').text().trim();
              $('#txtq4').text(o4 ? o4 : '0.00');

              var o3 = $('#o3').text().trim();
              $('#txtq3').text(o3 ? o3 : '0.00');

              $('#myInputModal').val('');
              $('#clearSearchModalBtn').hide();
              $('#filtered_row_count_stm').text('กำลังแสดงทุกรายการ (' + rowCount + ' รายการ)');

              $('#myModalget').modal('show');
            },
            error: function() {
              Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
          })
        }


      function stmgeti(data,dt)
        { 
          // ใช้ SweetAlert แสดงสถานะกำลังโหลด
          Swal.fire({
              title: 'กำลังดึงข้อมูล...',
              html: 'รอสักครู่...',
              allowOutsideClick: false,
              scrollbarPadding: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          var stmgeti = data;
          var yyy = $('#selectTypeOpt').val();

          $('#modal_dept_heading').text('รายละเอียดข้อมูลลูกหนี้รายตัว (ผู้ป่วยใน EMS)');
          $('#titlestm').html('🏷️ รหัสผังบัญชี ' + data + ' | ' + dt);

          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{stmgeti:stmgeti,yyy:yyy},
            success:function(data)
            {
              // ปิด SweetAlert เมื่อสำเร็จ
              Swal.close();
                
              $('#data_tabel_stm').html(data);

              // 🌟 สร้างแท็บจำแนกกลุ่มย่อย CR แบบ Dynamic (IPD)
              buildCrSubgroupTabs();

              var rowCount = $('#data_tabel_listview tr.main-patient-row, #data_tabel_listview_ipd tr.main-patient-row').length;
              if (rowCount === 0) {
                  rowCount = $('#data_tabel_listview tr, #data_tabel_listview_ipd tr').not('.summary-total-row, .sub-payment-row').length;
              }
              $('#txtq0').text(rowCount + ' คน');

              var i_inc = $('#data_tabel_listview_ipd, #data_tabel_listview').closest('table').find('tfoot tr.summary-total-row td').eq(1).text().trim();
              var i_paid = $('#data_tabel_listview_ipd, #data_tabel_listview').closest('table').find('tfoot tr.summary-total-row td').eq(2).text().trim();
              $('#txtq_income').text(i_inc ? i_inc : '0.00');
              $('#txtq_paid').text(i_paid ? i_paid : '0.00');

              var i1 = $('#i1').text().trim();
              $('#txtq1').text(i1 ? i1 : '0.00');

              var i2 = $('#i2').text().trim();
              $('#txtq2').text(i2 ? i2 : '0.00');

              $('#txtq4').text('0.00');

              var i3 = $('#i3').text().trim();
              $('#txtq3').text(i3 ? i3 : '0.00');

              $('#myInputModal').val('');
              $('#clearSearchModalBtn').hide();
              $('#filtered_row_count_stm').text('กำลังแสดงทุกรายการ (' + rowCount + ' รายการ)');

              $('#myModalget').modal('show');
            },
            error: function() {
              Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
          })
        }

        // =========================================================================
        // 🌟 ฟังก์ชันจัดการแท็บกลุ่มย่อย CR บน Modal แบบ Dynamic (รองรับ OPD & IPD)
        // =========================================================================
        var activeCrSubgroupFilter = 'ALL';

        function buildCrSubgroupTabs() {
            var summaryScript = document.getElementById('cr_subgroup_summary_json');
            var tabsWrapper = $('#cr_subgroup_tabs_wrapper');
            var tabsNav = $('#crSubgroupNav');
            tabsNav.empty();
            activeCrSubgroupFilter = 'ALL';
            $('#cr_current_tab_title').text('ทั้งหมด');

            if (!summaryScript) {
                tabsWrapper.hide();
                return;
            }

            try {
                var stats = JSON.parse(summaryScript.textContent || '{}');
                var keys = Object.keys(stats);
                if (keys.length === 0) {
                    tabsWrapper.hide();
                    return;
                }

                tabsWrapper.show();

                // ปรับป้ายกำกับ Badge Title ให้รองรับทั้ง CR และ SSS
                var isSss = false;
                keys.forEach(function(k) {
                    if (k.indexOf('SSS') > -1 || (stats[k].title && stats[k].title.indexOf('SSS') > -1)) isSss = true;
                });
                if (isSss) {
                    $('#cr_tabs_badge_title').html("<i class='bx bx-category-alt me-1'></i> จำแนกกลุ่มย่อย SSS");
                } else {
                    $('#cr_tabs_badge_title').html("<i class='bx bx-category-alt me-1'></i> จำแนกกลุ่มย่อย CR");
                }

                // วนลูปสร้างปุ่มแท็บ (Clean Text - ไม่มี emoji)
                keys.forEach(function(k) {
                    var item = stats[k];
                    if (item && item.enabled === false) return;
                    var isActive = (k === 'ALL') ? 'active' : '';
                    var badgeCount = item.count || 0;
                    var btnHtml = `
                        <button type="button" class="cr-tab-btn ${isActive}" data-cr-id="${item.id}" title="${item.title}">
                            <span>${item.short_name || item.id}</span>
                            <span class="badge-pill-count">${badgeCount}</span>
                        </button>
                    `;
                    tabsNav.append(btnHtml);
                });

            } catch(e) {
                console.error("Error parsing CR subgroup JSON:", e);
                tabsWrapper.hide();
            }
        }

        // Event เมื่อคลิกเลือกแท็บกลุ่มย่อย CR
        $(document).on('click', '.cr-tab-btn', function() {
            var btn = $(this);
            var crId = btn.data('cr-id');
            activeCrSubgroupFilter = crId;

            $('.cr-tab-btn').removeClass('active');
            btn.addClass('active');

            $('#cr_current_tab_title').text(btn.find('span').eq(0).text());

            filterTableByCrSubgroup();
        });

        function filterTableByCrSubgroup() {
            var filter = activeCrSubgroupFilter;
            var searchVal = $('#myInputModal').val().toLowerCase().trim();
            var visibleCount = 0;
            var rows = $("#data_tabel_listview tr, #data_tabel_listview_ipd tr");
            var totalMainRows = $("#data_tabel_listview tr.main-patient-row, #data_tabel_listview_ipd tr.main-patient-row").length;
            if (totalMainRows === 0) {
                totalMainRows = rows.not('.summary-total-row, .sub-payment-row, .spacer-row').length;
            }

            rows.each(function () {
                var tr = $(this);
                if (tr.hasClass('main-patient-row') || (!tr.hasClass('main-patient-row') && !tr.hasClass('sub-payment-row') && !tr.hasClass('spacer-row') && !tr.hasClass('summary-total-row') && tr.closest('tfoot').length === 0)) {
                    var crTypeAttr = (tr.attr('data-cr-type') || '').toLowerCase();
                    var text = tr.text().toLowerCase();

                    var matchFilter = false;
                    if (filter === 'ALL') {
                        matchFilter = true;
                    } else {
                        var filterLower = filter.toLowerCase();
                        matchFilter = crTypeAttr.indexOf(filterLower) > -1;
                    }

                    var matchSearch = (searchVal === '') || (text.indexOf(searchVal) > -1);
                    var isVisible = matchFilter && matchSearch;
                    tr.toggle(isVisible);
                    if (isVisible) {
                        visibleCount++;
                        tr.find('td').eq(0).text(visibleCount); // รันเลขลำดับ 1, 2, 3 ใหม่

                        // 🌟 จัดการยอดค่าใช้จ่าย (td 8) และภาระหนี้ (td 10) พร้อมแยก Badge กลุ่มย่อย ไม่ให้ปนกัน
                        if (!tr.data('original-income-val')) {
                            var fullIncomeAttr = tr.attr('data-full-income');
                            var initialInc = fullIncomeAttr ? parseFloat(fullIncomeAttr) : (parseFloat(tr.find('td').eq(8).text().replace(/,/g, '')) || 0);
                            tr.data('original-income-val', initialInc);
                        }
                        var originalInc = tr.data('original-income-val');

                        if (!tr.data('original-debit-val')) {
                            var fullDebitAttr = tr.attr('data-full-debit');
                            var initialDeb = fullDebitAttr ? parseFloat(fullDebitAttr) : (parseFloat(tr.find('td').eq(10).text().replace(/,/g, '')) || 0);
                            tr.data('original-debit-val', initialDeb);
                        }
                        var originalDeb = tr.data('original-debit-val');

                        if (filter === 'ALL') {
                            // แถบรวมทั้งหมด: คืนค่ายอดเดิมเต็มจำนวน และแสดง Badge ครบทุกกลุ่ม
                            tr.find('td').eq(8).text(originalInc.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            tr.find('td').eq(10).text(originalDeb.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            tr.find('.cr-subgroup-badge, span.badge').show();
                        } else {
                            // แถบเฉพาะกลุ่มย่อย: แสดงเฉพาะค่าบริการกลุ่มย่อยของตัวเอง
                            var amountsRaw = tr.attr('data-cr-amounts');
                            var amounts = {};
                            if (amountsRaw) {
                                try { amounts = JSON.parse(amountsRaw); } catch(e) {}
                            }
                            if ($.isEmptyObject(amounts)) {
                                var payloadEl = tr.find('[data-cr-payload]').first();
                                if (payloadEl.length) {
                                    try {
                                        var pData = JSON.parse(payloadEl.attr('data-cr-payload'));
                                        if (pData && pData.items && pData.items.length) {
                                            pData.items.forEach(function(it) {
                                                var ct = it.cr_type || '';
                                                if (ct) {
                                                    amounts[ct] = (amounts[ct] || 0) + (parseFloat(it.amount) || 0);
                                                }
                                            });
                                        }
                                    } catch(e) {}
                                }
                            }

                            var matchedAmt = null;
                            var fLower = filter.toLowerCase().trim();
                            for (var k in amounts) {
                                var kLower = k.toLowerCase().trim();
                                if (kLower === fLower || kLower.indexOf(fLower) > -1 || fLower.indexOf(kLower) > -1) {
                                    matchedAmt = parseFloat(amounts[k]);
                                    break;
                                }
                            }

                            if (matchedAmt !== null && matchedAmt > 0) {
                                tr.find('td').eq(8).text(matchedAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                tr.find('td').eq(10).text(matchedAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            } else {
                                tr.find('td').eq(8).text(originalInc.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                tr.find('td').eq(10).text(originalDeb.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            }

                            // ซ่อน Badge กลุ่มย่อยอื่น ไม่ให้ปนกัน (แสดงเฉพาะ Badge ของกลุ่มปัจจุบัน และป้ายโอนจากผังเดิม)
                            tr.find('.cr-subgroup-badge, span.badge').each(function() {
                                var badge = $(this);
                                var bText = badge.text().trim().toLowerCase();
                                var bSg = (badge.attr('data-cr-subgroup') || badge.attr('data-filter-subgroup') || bText).toLowerCase().trim();
                                var isTransferBadge = badge.hasClass('cr-badge-all') || bText.indexOf('โอน') > -1 || bText.indexOf('ตัด') > -1;
                                var isMatchSubgroup = (bSg === fLower || bSg.indexOf(fLower) > -1 || fLower.indexOf(bSg) > -1);
                                if (isTransferBadge || isMatchSubgroup) {
                                    badge.show();
                                } else if (bSg.indexOf('cr-') > -1 || bSg.indexOf('pp-') > -1 || bSg.indexOf('sss-') > -1) {
                                    badge.hide();
                                }
                            });
                        }
                    }
                } else if (tr.hasClass('sub-payment-row')) {
                    var prevMain = tr.prevAll('.main-patient-row:first');
                    tr.toggle(prevMain.is(':visible'));
                } else if (tr.hasClass('spacer-row')) {
                    tr.show();
                }
            });

            if (searchVal === '' && filter === 'ALL') {
                $('#clearSearchModalBtn').hide();
                $('#filtered_row_count_stm').text('กำลังแสดงทุกรายการ (' + totalMainRows + ' รายการ)');
            } else {
                if (searchVal !== '') $('#clearSearchModalBtn').css('display', 'inline-flex');
                $('#filtered_row_count_stm').html('พบ <b>' + visibleCount + '</b> จาก ' + totalMainRows + ' รายการ');
            }

            recalculateModalSummary();
        }


        // เคลียร์ padding ที่อาจค้างตอนปิด Modal
        $('#myModalget').on('hidden.bs.modal', function () {
            $('body').css('padding-right', '');
            $('body').removeClass('modal-open');
            $('#myInputModal').val('').trigger('keyup');
        });

      function btn_close(){ 
        $('#btn-save-yearedit').hide();
        $('#btn-close').hide();

        $('#inp-data-y').val('');
        $('#inp-data-amo').val('');
        $('#inp-data-id').val('');

        $('#bg_data01').css('background-color', '#fff');
        $('#btn-save-year').show();

      }


      $("#selectTypeOpt").change(function(){
        var yyy = $('#selectTypeOpt').val();
        fetch_data_opd(yyy);
        fetch_data_ipd(yyy);
        
      });

      function formatMoneyJS(num) {
          return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }

      function recalculateModalSummary() {
          var totalMainRows = $("#data_tabel_listview tr.main-patient-row").length;
          var visibleMainRows = $("#data_tabel_listview tr.main-patient-row:visible").length;
          
          var isIpd = $('#data_tabel_listview').closest('table').find('thead th:contains("AN")').length > 0;
          var tfoot = $('#data_tabel_listview').closest('table').find('tfoot tr.summary-total-row');

          var sum_income = 0, sum_paid = 0, sum_debit = 0, sum_stm = 0, sum_dckd = 0, sum_diff = 0;

          $("#data_tabel_listview tr.main-patient-row:visible").each(function () {
              var tr = $(this);
              if (!isIpd) {
                  // OPD Columns:
                  // td:eq(8) = ค่าใช้จ่าย (income)
                  // td:eq(9) = ชำระแล้ว (incomediff)
                  // td:eq(10) = ภาระหนี้ (debit)
                  // td:eq(11) = ชดเชย STM (stm_compensation_value)
                  // td:eq(12) = ชดเชย DCKD (seamless_dckd_total)
                  // td:eq(13) = ส่วนต่าง (diff_amount)
                  sum_income += parseFloat(tr.find('td').eq(8).text().replace(/,/g, '')) || 0;
                  sum_paid   += parseFloat(tr.find('td').eq(9).text().replace(/,/g, '')) || 0;
                  sum_debit  += parseFloat(tr.find('td').eq(10).text().replace(/,/g, '')) || 0;
                  sum_stm    += parseFloat(tr.find('td').eq(11).text().replace(/,/g, '')) || 0;
                  sum_dckd   += parseFloat(tr.find('td').eq(12).text().replace(/,/g, '')) || 0;
                  sum_diff   += parseFloat(tr.find('td').eq(13).text().replace(/,/g, '')) || 0;
              } else {
                  // IPD Columns:
                  // td:eq(8) = ค่าใช้จ่าย (income)
                  // td:eq(9) = ชำระแล้ว (incomediff)
                  // td:eq(10) = ภาระหนี้ (debit)
                  // td:eq(11) = ชดเชย STM (stm_compensation_value)
                  // td:eq(12) = ส่วนต่าง (diff_amount)
                  sum_income += parseFloat(tr.find('td').eq(8).text().replace(/,/g, '')) || 0;
                  sum_paid   += parseFloat(tr.find('td').eq(9).text().replace(/,/g, '')) || 0;
                  sum_debit  += parseFloat(tr.find('td').eq(10).text().replace(/,/g, '')) || 0;
                  sum_stm    += parseFloat(tr.find('td').eq(11).text().replace(/,/g, '')) || 0;
                  sum_diff   += parseFloat(tr.find('td').eq(12).text().replace(/,/g, '')) || 0;
              }
          });

          // อัปเดตยอดรวมใน tfoot
          if (!isIpd) {
              tfoot.find('td').eq(1).text(formatMoneyJS(sum_income));
              tfoot.find('td').eq(2).text(formatMoneyJS(sum_paid));
              tfoot.find('#o1').text(formatMoneyJS(sum_debit));
              tfoot.find('#o2').text(formatMoneyJS(sum_stm));
              tfoot.find('#o4').text(formatMoneyJS(sum_dckd));
              tfoot.find('#o3').text(formatMoneyJS(sum_diff));

              // อัปเดตการ์ด KPI ด้านบน
              $('#txtq0').text(visibleMainRows + ' คน');
              $('#txtq_income').text(formatMoneyJS(sum_income));
              $('#txtq_paid').text(formatMoneyJS(sum_paid));
              $('#txtq1').text(formatMoneyJS(sum_debit));
              $('#txtq2').text(formatMoneyJS(sum_stm));
              $('#txtq4').text(formatMoneyJS(sum_dckd));
              $('#txtq3').text(formatMoneyJS(sum_diff));
          } else {
              tfoot.find('td').eq(1).text(formatMoneyJS(sum_income));
              tfoot.find('td').eq(2).text(formatMoneyJS(sum_paid));
              tfoot.find('#i1').text(formatMoneyJS(sum_debit));
              tfoot.find('#i2').text(formatMoneyJS(sum_stm));
              tfoot.find('#i3').text(formatMoneyJS(sum_diff));

              // อัปเดตการ์ด KPI ด้านบน
              $('#txtq0').text(visibleMainRows + ' คน');
              $('#txtq_income').text(formatMoneyJS(sum_income));
              $('#txtq_paid').text(formatMoneyJS(sum_paid));
              $('#txtq1').text(formatMoneyJS(sum_debit));
              $('#txtq2').text(formatMoneyJS(sum_stm));
              $('#txtq4').text('0.00');
              $('#txtq3').text(formatMoneyJS(sum_diff));
          }
      }

      // ฟังก์ชันกรองค้นหาข้อมูลในตาราง Modal Realtime
      $(document).on("keyup input", "#myInputModal", function() {
          filterTableByCrSubgroup();
      });


    $(document).on('click', '.stmgetovlookup th.sortable-header', function() {
          var table = $(this).closest('table');
          var tbody = table.find('#data_tabel_listview');
          var th = $(this);
          var columnIndex = parseInt(th.data('column'));
          
          var isAscending = !th.hasClass('sort-desc');
          table.find('th.sortable-header').removeClass('sort-asc sort-desc');
          
          if (isAscending) {
              th.addClass('sort-desc');
          } else {
              th.addClass('sort-asc');
          }

          var rowsGroup = [];
          var currentGroup = null;

          tbody.find('tr').each(function() {
              var tr = $(this);
              if (tr.hasClass('summary-total-row') || tr.hasClass('spacer-row') || tr.closest('tfoot').length > 0) return;
              if (tr.hasClass('main-patient-row') || (!tr.hasClass('main-patient-row') && !tr.hasClass('sub-payment-row'))) {
                  if (currentGroup) {
                      rowsGroup.push(currentGroup);
                  }
                  currentGroup = {
                      mainRow: tr,
                      subRows: [],
                      sortValue: tr.find('td').eq(columnIndex).text().trim()
                  };
              } else if (tr.hasClass('sub-payment-row') && currentGroup) {
                  currentGroup.subRows.push(tr);
              }
          });
          if (currentGroup) rowsGroup.push(currentGroup);

          rowsGroup.sort(function(a, b) {
              var valA = a.sortValue.replace(/,/g, '').replace(/วัน/g, '').trim();
              var valB = b.sortValue.replace(/,/g, '').replace(/วัน/g, '').trim();

              if (!isNaN(valA) && !isNaN(valB) && valA !== '' && valB !== '') {
                  return isAscending ? parseFloat(valB) - parseFloat(valA) : parseFloat(valA) - parseFloat(valB);
              } else {
                  return isAscending ? valB.localeCompare(valA, 'th') : valA.localeCompare(valB, 'th');
              }
          });

          tbody.empty();
          
          $.each(rowsGroup, function(index, group) {
              // อัปเดตเลขลำดับคอลัมน์แรกให้รัน 1, 2, 3 ใหม่หลังเรียงเสร็จ
              group.mainRow.find('td').eq(0).text(index + 1);
              
              tbody.append(group.mainRow);
              $.each(group.subRows, function(i, subRow) {
                  tbody.append(subRow);
              });
          });

          var colCount = table.find('thead tr:last th').length || 15;
          var spacerTds = '';
          for (var c = 0; c < colCount; c++) {
              spacerTds += '<td></td>';
          }
          tbody.append('<tr class="spacer-row">' + spacerTds + '</tr>');
      });

      // ซ่อน/แสดง Floating Logout เมื่อเปิด/ปิด Modal ทุกตัว
      $(document).on('show.bs.modal', '.modal', function () {
          $('.floating-logout-btn').hide();
      });
      $(document).on('hidden.bs.modal', '.modal', function () {
          if (!$('.modal.show, .modal.in').length) {
              $('.floating-logout-btn').show();
          }
      });



    </script>

  <?php include_once __DIR__ . '/includes/modal_cr_patient_breakdown.php'; ?>
  <?php include_once __DIR__ . '/includes/modal_sss_patient_breakdown.php'; ?>

  <?php if (!empty($_SESSION['role'])): ?>
  <!-- ✅ Quick Search (Ctrl+K) -->
  <?php include './includes/quick_search_ui.html'; ?>
  <?php endif; ?>

  </body>
</html>
