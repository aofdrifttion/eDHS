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

    <script src="../assets/js/config.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

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
          bottom: 5px;
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

/* Summary Footer Row (รวมทั้งสิ้น) - Fixed Dark Theme Matches Table Header */
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
  background: #64748b;
  color: #ffffff;
  padding: 9px 22px;
  font-size: 14px;
  font-weight: 600;
  border-radius: 9px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-modal-dismiss:hover {
  background: #475569;
  color: #ffffff;
}



/* =========================================================================
   🌟 Modal เลือกสิทธิการเงินก่อนนำเข้า (Select Debtor Rights Modal)
   ========================================================================= */
#modalSelectRights .modal-content {
  border: none;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

#modalSelectRights .modal-header {
  background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
  color: #fff;
  padding: 16px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

#modalSelectRights .modal-header .modal-title {
  color: #fff;
  font-weight: 700;
  font-size: 1.15rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

#modalSelectRights .modal-body {
  padding: 20px 24px;
  background-color: #f8fafc;
}

.rights-kpi-bar {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.rights-kpi-item {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 14px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.rights-kpi-item .kpi-num {
  font-size: 1.3rem;
  font-weight: 700;
  color: #1e3a8a;
  line-height: 1.2;
}

.rights-kpi-item .kpi-txt {
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
  margin-top: 2px;
}

.rights-table-wrapper {
  max-height: 380px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);
}

.rights-table-wrapper table {
  margin-bottom: 0;
  width: 100%;
}

.rights-table-wrapper thead th {
  position: sticky;
  top: 0;
  background: #f1f5f9;
  z-index: 10;
  border-bottom: 2px solid #cbd5e1;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  padding: 10px 12px;
}

.rights-table-wrapper tbody td {
  padding: 10px 12px;
  font-size: 13.5px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.rights-table-wrapper tbody tr:hover {
  background-color: #f0fdf4 !important;
}

.rights-table-wrapper tbody tr.row-deselected {
  opacity: 0.45;
  background-color: #f8fafc;
}

.rights-selected-summary-bar {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  padding: 12px 16px;
  margin-top: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
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
   🌟 Modern Month Filter Card & Import Section UI (OPD & IPD)
   ========================================================================= */
.import-filter-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #0004a1 !important;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 22px 26px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}
.import-filter-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.import-card-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 14px;
  margin-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
}

.import-header-icon-filter {
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

.import-filter-select {
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

.import-filter-select:focus {
  background-color: #ffffff !important;
  border-color: #0004a1 !important;
  box-shadow: 0 0 0 3px rgba(0, 4, 161, 0.12) !important;
  outline: none !important;
}

.import-section-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px;
  margin-bottom: 26px;
  transition: box-shadow 0.25s ease;
}
.import-section-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

/* OPD Import Box */
.import-box-opd {
  background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 50%, #f7fef9 100%);
  border: 1px solid #bbf7d0;
  border-left: 5px solid #10b981 !important;
  border-radius: 14px;
  padding: 22px 24px;
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.06);
  position: relative;
  overflow: hidden;
}

/* IPD Import Box */
.import-box-ipd {
  background: linear-gradient(135deg, #eff6ff 0%, #ffffff 50%, #f8faff 100%);
  border: 1px solid #bfdbfe;
  border-left: 5px solid #2563eb !important;
  border-radius: 14px;
  padding: 22px 24px;
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.06);
  position: relative;
  overflow: hidden;
}

/* Header inside import box */
.import-box-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 16px;
  margin-bottom: 18px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.import-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.import-header-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.import-box-opd .import-header-icon {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #059669;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
}

.import-box-ipd .import-header-icon {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1d4ed8;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
}

.import-header-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.import-header-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  margin: 0;
}

.import-pill-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.import-box-opd .import-pill-badge {
  color: #047857;
  border: 1px solid #86efac;
}

.import-box-ipd .import-pill-badge {
  color: #1d4ed8;
  border: 1px solid #93c5fd;
}

/* Field labels and inputs */
.import-field-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.import-select {
  border-radius: 10px !important;
  border: 1px solid #cbd5e1 !important;
  padding: 9px 14px !important;
  font-size: 13.5px !important;
  color: #1e293b !important;
  background-color: #ffffff !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.2s ease !important;
}

.import-box-opd .import-select:focus {
  border-color: #10b981 !important;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
}

.import-box-ipd .import-select:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
}

.import-file-control {
  border-radius: 10px !important;
  border: 1.5px dashed #cbd5e1 !important;
  padding: 10px 14px !important;
  font-size: 13.5px !important;
  color: #334155 !important;
  background-color: #ffffff !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.2s ease !important;
  cursor: pointer;
}

.import-file-control:hover {
  background-color: #f8fafc !important;
}

.import-box-opd .import-file-control:hover,
.import-box-opd .import-file-control:focus {
  border-color: #10b981 !important;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12) !important;
}

.import-box-ipd .import-file-control:hover,
.import-box-ipd .import-file-control:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
}

/* Action buttons */
.import-action-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px dashed rgba(0, 0, 0, 0.08);
}

.btn-import-submit-opd {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
  color: #ffffff !important;
  font-weight: 600 !important;
  font-size: 14px !important;
  padding: 9px 22px !important;
  border-radius: 10px !important;
  border: none !important;
  box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3) !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.btn-import-submit-opd:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
  transform: translateY(-1px);
  box-shadow: 0 5px 14px rgba(16, 185, 129, 0.4) !important;
  color: #ffffff !important;
}

.btn-import-submit-ipd {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
  color: #ffffff !important;
  font-weight: 600 !important;
  font-size: 14px !important;
  padding: 9px 22px !important;
  border-radius: 10px !important;
  border: none !important;
  box-shadow: 0 3px 10px rgba(37, 99, 235, 0.3) !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.btn-import-submit-ipd:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%) !important;
  transform: translateY(-1px);
  box-shadow: 0 5px 14px rgba(37, 99, 235, 0.4) !important;
  color: #ffffff !important;
}

.btn-import-delete {
  background: #ffffff !important;
  color: #dc2626 !important;
  border: 1px solid #fca5a5 !important;
  font-weight: 600 !important;
  font-size: 13.5px !important;
  padding: 8px 18px !important;
  border-radius: 10px !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  transition: all 0.2s ease !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03) !important;
}
.btn-import-delete:hover {
  background: #fef2f2 !important;
  border-color: #ef4444 !important;
  color: #b91c1c !important;
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(239, 68, 68, 0.15) !important;
}

/* Date range filter toolbar */
.import-filter-toolbar {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 20px;
  margin: 18px 0 20px 0;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
}

.import-filter-toolbar .form-label {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.import-filter-toolbar .input-group-text {
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-right: none;
  color: #0284c7;
  border-radius: 9px 0 0 9px;
}

.import-filter-toolbar .form-control {
  border: 1px solid #cbd5e1;
  border-left: none;
  border-radius: 0 9px 9px 0;
  font-size: 13.5px;
  font-weight: 500;
  color: #1e293b;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}
.import-filter-toolbar .form-control:focus {
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
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
                  <!-- Card ตัวกรองข้อมูลลูกหนี้ประจำเดือน -->
                  <div class="import-filter-card">
                    <div class="import-card-header-bar">
                      <div class="import-header-left">
                        <div class="import-header-icon-filter">
                          <i class='bx bx-calendar-check'></i>
                        </div>
                        <div>
                          <h5 class="import-header-title">ข้อมูลประจำเดือน</h5>
                          <p class="import-header-subtitle">เลือกข้อมูลรอบเดือนเพื่อตรวจสอบรายการและนำเข้าลูกหนี้สิทธิ OPD / IPD</p>
                        </div>
                      </div>
                      <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm fw-semibold" 
                          id="btnOpenDirectHosPull" 
                          data-bs-toggle="modal" 
                          data-bs-target="#modalDirectDebtorPull" 
                          data-toggle="modal" 
                          data-target="#modalDirectDebtorPull" 
                          onclick="openDirectHosPullModal(); return false;" 
                          style="border-radius: 8px; padding: 0.42rem 0.95rem; font-size: 13.5px; cursor: pointer; z-index: 10;">
                          <i class='bx bx-cloud-download fs-5'></i> <span>ดึงข้อมูลตรงจาก HOSxP</span>
                        </button>
                        <span class="import-pill-badge" style="color: #0004a1; border: 1px solid #c7d2fe; background: #eef2ff;">
                          <i class='bx bx-time-five' style="color: #0004a1;"></i> เลือกรอบเดือน
                        </span>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <label class="import-field-label"><i class='bx bx-calendar' style="color: #0004a1;"></i> ข้อมูลประจำเดือน</label>
                        <select id="selectTypeOpt" name="selectTypeOpt" class="form-select import-filter-select">
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

                  <!-- Card นำเข้าลูกหนี้สิทธิ OPD -->
                  <div class="import-section-card">
                    <form id="upload-formOPD" enctype="multipart/form-data" class="import-box-opd">
                      
                      <!-- Header -->
                      <div class="import-box-header">
                        <div class="import-header-left">
                          <div class="import-header-icon">
                            <i class='bx bx-plus-medical'></i>
                          </div>
                          <div>
                            <h5 class="import-header-title">นำเข้าลูกหนี้สิทธิ OPD</h5>
                            <p class="import-header-subtitle">นำเข้าข้อมูลลูกหนี้ผู้ป่วยนอกรายเดือนจากไฟล์ Excel เข้าสู่ระบบ</p>
                          </div>
                        </div>
                        <div>
                          <span class="import-pill-badge">
                            <i class='bx bxs-file-blank text-success'></i> รองรับไฟล์ Excel (.xlsx, .xls)
                          </span>
                        </div>
                      </div>

                      <div class="row g-3 mb-3">
                          <div class="col-md-6">
                              <label class="import-field-label"><i class='bx bx-calendar text-success'></i> ประจำเดือน</label>
                              <select name="month" class="form-select import-select" required>
                                  <option value="00" selected>-เลือกเดือน-</option>
                                  <option value="1">มกราคม</option>
                                  <option value="2">กุมภาพันธ์</option>
                                  <option value="3">มีนาคม</option>
                                  <option value="4">เมษายน</option>
                                  <option value="5">พฤษภาคม</option>
                                  <option value="6">มิถุนายน</option>
                                  <option value="7">กรกฎาคม</option>
                                  <option value="8">สิงหาคม</option>
                                  <option value="9">กันยายน</option>
                                  <option value="10">ตุลาคม</option>
                                  <option value="11">พฤศจิกายน</option>
                                  <option value="12">ธันวาคม</option>
                              </select>
                          </div>
                          <div class="col-md-6">
                              <label class="import-field-label"><i class='bx bx-calendar-event text-success'></i> ปี พ.ศ. / ค.ศ.</label>
                              <select name="year" class="form-select import-select" required>
                                  <option value="00" selected>-เลือกปี-</option>
                                  <?php
                                  $currentYear = date("Y");
                                  for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
                                      $thaiYear = $y + 543;
                                  ?>
                                      <option value="<?= $y ?>">
                                          <?= $y ?> (พ.ศ. <?= $thaiYear ?>)
                                      </option>
                                  <?php } ?>
                              </select>
                          </div>
                      </div>

                      <div class="mb-3">
                        <label class="import-field-label"><i class='bx bx-upload text-success'></i> ไฟล์ข้อมูล Excel สำหรับนำเข้า</label>
                        <input class="form-control import-file-control" name="excel_file" type="file" required />
                      </div>
                      
                      <!-- ซ่อนไว้ก่อน จะแสดงตอนเริ่มกดอัปโหลด -->
                      <div id="progress-container" style="display: none; width: 100%; background-color: #e2e8f0; border-radius: 8px; margin-top: 15px; margin-bottom: 15px; overflow: hidden;">
                          <div id="progress-bar" style="width: 0%; height: 24px; background-color: #10b981; text-align: center; color: white; line-height: 24px; font-weight: bold; transition: width 0.3s ease;">
                              0%
                          </div>
                      </div>

                      <!-- Action Bar -->
                      <div class="import-action-bar">
                        <div>
                          <p id="statuso" class="m-0 fw-semibold text-primary" style="font-size: 13.5px;"></p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                          <a class="btn btn-import-delete" data-bs-placement="bottom" data-bs-toggle="tooltip" title="ลบข้อมูลลูกหนี้ OPD ประจำเดือน" href="javascript:void(0)" onclick="delete_opd()">
                              <i class="bx bx-trash"></i> ล้างข้อมูล OPD
                          </a>

                          <button type="submit" class="btn btn-import-submit-opd" data-bs-placement="bottom" data-bs-toggle="tooltip" title="อัปโหลดและประมวลผลไฟล์" id="submit_opd">
                              <i class="bx bx-cloud-upload"></i> นำเข้าข้อมูล OPD
                          </button>
                        </div>
                      </div>

                    </form>

                    <!-- Filter Toolbar -->
                    <div class="import-filter-toolbar">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="opd_start_date" class="form-label"><i class="bx bx-calendar-check text-primary"></i> ตั้งแต่วันที่ (ตาม vstdate)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                    <input type="search" id="opd_start_date" class="form-control bg-white" autocomplete="new-password" placeholder="เลือกวันที่เริ่มต้น...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="opd_end_date" class="form-label"><i class="bx bx-calendar-check text-primary"></i> ถึงวันที่</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                    <input type="search" id="opd_end_date" class="form-control bg-white" autocomplete="new-password" placeholder="เลือกวันที่สิ้นสุด...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="modal-title" id="tbopdload" style="text-align: center; margin: 20px; display: none; color: #0f172a; font-weight: 600;">
                        <span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span> รอสักครู่กำลังโหลดข้อมูลลูกหนี้ OPD...
                    </h5>
                    <div id="data_tabel_opd"></div>
                  </div>

                  <!-- Card นำเข้าลูกหนี้สิทธิ IPD -->
                  <div class="import-section-card">
                    <form id="upload-formIPD" enctype="multipart/form-data" class="import-box-ipd">
                      
                      <!-- Header -->
                      <div class="import-box-header">
                        <div class="import-header-left">
                          <div class="import-header-icon">
                            <i class='bx bx-hotel'></i>
                          </div>
                          <div>
                            <h5 class="import-header-title">นำเข้าลูกหนี้สิทธิ IPD</h5>
                            <p class="import-header-subtitle">นำเข้าข้อมูลลูกหนี้ผู้ป่วยในรายเดือนจากไฟล์ Excel เข้าสู่ระบบ</p>
                          </div>
                        </div>
                        <div>
                          <span class="import-pill-badge">
                            <i class='bx bxs-file-blank text-primary'></i> รองรับไฟล์ Excel (.xlsx, .xls)
                          </span>
                        </div>
                      </div>

                      <div class="row g-3 mb-3">
                          <div class="col-md-6">
                              <label class="import-field-label"><i class='bx bx-calendar text-primary'></i> ประจำเดือน</label>
                              <select name="monthi" class="form-select import-select" required>
                                  <option value="00" selected>-เลือกเดือน-</option>
                                  <option value="1">มกราคม</option>
                                  <option value="2">กุมภาพันธ์</option>
                                  <option value="3">มีนาคม</option>
                                  <option value="4">เมษายน</option>
                                  <option value="5">พฤษภาคม</option>
                                  <option value="6">มิถุนายน</option>
                                  <option value="7">กรกฎาคม</option>
                                  <option value="8">สิงหาคม</option>
                                  <option value="9">กันยายน</option>
                                  <option value="10">ตุลาคม</option>
                                  <option value="11">พฤศจิกายน</option>
                                  <option value="12">ธันวาคม</option>
                              </select>
                          </div>
                          <div class="col-md-6">
                              <label class="import-field-label"><i class='bx bx-calendar-event text-primary'></i> ปี พ.ศ. / ค.ศ.</label>
                              <select name="yeari" class="form-select import-select" required>
                                  <option value="00" selected>-เลือกปี-</option>
                                  <?php
                                  $currentYear = date("Y"); 
                                  for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
                                      $thaiYear = $y + 543;
                                  ?>
                                      <option value="<?= $y ?>">
                                          <?= $y ?> (พ.ศ. <?= $thaiYear ?>)
                                      </option>
                                  <?php } ?>
                              </select>
                          </div>
                      </div>

                      <div class="mb-3">
                        <label class="import-field-label"><i class='bx bx-upload text-primary'></i> ไฟล์ข้อมูล Excel สำหรับนำเข้า</label>
                        <input class="form-control import-file-control" name="excel_file" type="file" required />
                      </div>
                    
                      <!-- Progress Bar สำหรับ IPD -->
                      <div id="progress-container-ipd" style="display: none; width: 100%; background-color: #e2e8f0; border-radius: 8px; margin-top: 15px; margin-bottom: 15px; overflow: hidden;">
                          <div id="progress-bar-ipd" style="width: 0%; height: 24px; background-color: #2563eb; text-align: center; color: white; line-height: 24px; font-weight: bold; transition: width 0.3s ease;">
                              0%
                          </div>
                      </div>

                      <!-- Action Bar -->
                      <div class="import-action-bar">
                        <div>
                          <p id="statusi" class="m-0 fw-semibold text-primary" style="font-size: 13.5px;"></p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                          <a class="btn btn-import-delete" data-bs-placement="bottom" data-bs-toggle="tooltip" title="ลบข้อมูลลูกหนี้ IPD ประจำเดือน" href="javascript:void(0)" onclick="delete_ipd()">
                              <i class="bx bx-trash"></i> ล้างข้อมูล IPD
                          </a>

                          <button type="submit" class="btn btn-import-submit-ipd" data-bs-placement="bottom" data-bs-toggle="tooltip" title="อัปโหลดและประมวลผลไฟล์" id="submit_ipd">
                              <i class="bx bx-cloud-upload"></i> นำเข้าข้อมูล IPD
                          </button>
                        </div>
                      </div>

                    </form>

                    <!-- Filter Toolbar -->
                    <div class="import-filter-toolbar">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="ipd_start_date" class="form-label"><i class="bx bx-calendar-check text-primary"></i> ตั้งแต่วันที่ (ตาม dchdate)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                    <input type="search" id="ipd_start_date" class="form-control bg-white" autocomplete="new-password" placeholder="เลือกวันที่เริ่มต้น...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="ipd_end_date" class="form-label"><i class="bx bx-calendar-check text-primary"></i> ถึงวันที่</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                    <input type="search" id="ipd_end_date" class="form-control bg-white" autocomplete="new-password" placeholder="เลือกวันที่สิ้นสุด...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="modal-title" id="tbipdload" style="text-align: center; margin: 20px; display: none; color: #0f172a; font-weight: 600;">
                        <span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span> รอสักครู่กำลังโหลดข้อมูลลูกหนี้ IPD...
                    </h5>
                    <div id="data_tabel_ipd"></div>
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


<!-- Modal สำหรับกรอกรหัสผ่าน OPD -->
<div class="modal fade" id="passwordModalOPD" tabindex="-1" aria-labelledby="passwordModalOPDLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ยืนยันรหัสผ่านก่อนลบข้อมูลลูกหนี้สิทธิ OPD</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body">
        <input type="password" id="delete_password_opd" class="form-control" placeholder="กรอกรหัสผ่าน" autocomplete="current-password" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-danger" id="confirm_delete_opd">ยืนยันลบ OPD</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal สำหรับกรอกรหัสผ่าน IPD -->
<div class="modal fade" id="passwordModalIPD" tabindex="-1" aria-labelledby="passwordModalIPDLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ยืนยันรหัสผ่านก่อนลบข้อมูลลูกหนี้สิทธิ IPD</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body">
        <input type="password" id="delete_password_ipd" class="form-control" placeholder="กรอกรหัสผ่าน" autocomplete="current-password" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-danger" id="confirm_delete_ipd">ยืนยันลบ IPD</button>
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

<!-- Modal สำหรับเลือกสิทธิการเงินก่อนนำเข้า -->
<div class="modal fade" id="modalSelectRights" tabindex="-1" aria-labelledby="modalSelectRightsLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSelectRightsLabel">
          <i class='bx bx-check-shield fs-4 text-warning'></i>
          <span>สรุปสิทธิการเงินที่พบในไฟล์ Excel</span>
        </h5>
      </div>
      <div class="modal-body">
        
        <!-- KPI Summary Cards -->
        <div class="rights-kpi-bar">
          <div class="rights-kpi-item">
            <div class="kpi-num text-primary" id="kpiTotalRightsCount">0</div>
            <div class="kpi-txt">สิทธิการเงินทั้งหมดที่พบ</div>
          </div>
          <div class="rights-kpi-item">
            <div class="kpi-num text-success" id="kpiTotalRecordsCount">0</div>
            <div class="kpi-txt">จำนวนรายการทั้งหมด (เคส)</div>
          </div>
          <div class="rights-kpi-item">
            <div class="kpi-num text-info" id="kpiTotalDebitAmount">0.00</div>
            <div class="kpi-txt">ยอดเงินลูกหนี้รวม (บาท)</div>
          </div>
        </div>

        <!-- Action Toolbar -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllRights">
              <i class='bx bx-check-double'></i> เลือกทั้งหมด
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAllRights">
              <i class='bx bx-x'></i> ยกเลิกทั้งหมด
            </button>
          </div>
          <div style="min-width: 250px;">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class='bx bx-search'></i></span>
              <input type="text" id="searchRightsInput" class="form-control" placeholder="ค้นหารหัส / ชื่อสิทธิ...">
            </div>
          </div>
        </div>

        <!-- Rights Table -->
        <div class="rights-table-wrapper">
          <table class="table table-hover table-striped" id="tableRightsSelection">
            <thead>
              <tr>
                <th style="width: 50px; text-align: center;">
                  <input type="checkbox" class="form-check-input" id="checkAllRightsHeader" checked>
                </th>
                <th style="width: 190px;">รหัสผังบัญชี (accountcode)</th>
                <th>ชื่อสิทธิการเงิน (accountname)</th>
                <th style="width: 140px; text-align: right;">จำนวนรายการ</th>
                <th style="width: 160px; text-align: right;">ยอดลูกหนี้ (บาท)</th>
              </tr>
            </thead>
            <tbody id="tbodyRightsList">
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>

        <!-- Summary Bar of Selected Items -->
        <div class="rights-selected-summary-bar">
          <div>
            <span class="fw-bold text-primary"><i class='bx bx-check-circle'></i> สิทธิที่เลือกนำเข้า:</span>
            <span id="txtSelectedRightsSummary" class="ms-2 fw-semibold text-dark">0 สิทธิ (0 รายการ)</span>
          </div>
          <div>
            <span class="text-muted me-1">ยอดเงินลูกหนี้ที่เลือก:</span>
            <span class="fw-bold text-success fs-6" id="txtSelectedDebitSummary">0.00 บาท</span>
          </div>
        </div>

      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
          <i class='bx bx-x me-1'></i> ยกเลิก
        </button>
        <button type="button" class="btn btn-success px-4" id="btnConfirmImportRights">
          <i class='bx bx-cloud-upload me-1'></i> ยืนยันนำเข้าข้อมูลที่เลือก
        </button>
      </div>
    </div>
  </div>
</div>


<!-- ใส่ใน <body> ท้ายสุด -->
<a href="logout.php" class="floating-logout-btn" style="margin-bottom: 7px;">
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
        document.getElementById('exportstmgetovlookup').addEventListener('click', function () {
            let table = document.querySelector('.stmgetovlookup');
            if (!table) return;
            let titleText = $('#titlestm').text().replace(/🏷️\s*รหัสผังบัญชี\s*/g, '').replace(/\|/g, '_').trim();
            if (typeof activeCrSubgroupFilter !== 'undefined' && activeCrSubgroupFilter && activeCrSubgroupFilter !== 'ALL') {
                titleText += '_' + activeCrSubgroupFilter;
            }
            let fileName = titleText ? 'ลูกหนี้_' + titleText.replace(/[\\/:*?"<>|]/g, '_') + '.xlsx' : 'ExportedData.xlsx';

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
       // fetch_data_nhso01();

        var yyy = '<?php echo $yyy; ?>';      
        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        if (!isLoggedIn) {
            location.href = './login.php';
        }else{
          initFlatpickr();
          fetch_data_opd();
          fetch_data_ipd();
        }

      });

      var startDatePicker, endDatePicker;
      var startDatePickerIpd, endDatePickerIpd;
      function initFlatpickr(){
          endDatePicker = flatpickr("#opd_end_date",{
              locale:"th",
              dateFormat:"Y-m-d",
              altInput:true,
              altFormat:"j F Y",
              disableMobile:true,
              onChange: function(selectedDates, dateStr, instance) {
                  if (startDatePicker) {
                      if (selectedDates.length > 0) {
                          startDatePicker.set('maxDate', selectedDates[0]);
                          if (startDatePicker.selectedDates.length > 0) {
                              fetch_data_opd();
                          }
                      } else {
                          startDatePicker.set('maxDate', null);
                          fetch_data_opd();
                      }
                  }
              },
              onReady:(d,s,i)=>{
                  replaceYearToThai(i);
                  if(i.altInput) {
                      i.altInput.setAttribute("type", "search");
                      i.altInput.setAttribute("autocomplete", "new-password");
                      i.altInput.setAttribute("data-lpignore", "true");
                      // เพิ่ม name เปล่าๆ เพื่อไม่ให้เบราว์เซอร์เดาว่าเป็นฟิลด์สำคัญ
                      i.altInput.setAttribute("name", "dummy_date_" + Math.random());
                  }
              },
              onValueUpdate:(d,s,i)=>{
                  replaceYearToThai(i);
                  if(d[0]) i.altInput.value=`${d[0].getDate()} ${i.l10n.months.longhand[d[0].getMonth()]} ${d[0].getFullYear()+543}`;
              },
              onMonthChange:(d,s,i)=>replaceYearToThai(i),
              onYearChange:(d,s,i)=>replaceYearToThai(i)
          });

          startDatePicker = flatpickr("#opd_start_date",{
              locale:"th",
              dateFormat:"Y-m-d",
              altInput:true,
              altFormat:"j F Y",
              disableMobile:true,
              onChange: function(selectedDates, dateStr, instance) {
                  if (endDatePicker) {
                      if (selectedDates.length > 0) {
                          endDatePicker.set('minDate', selectedDates[0]);
                          if (endDatePicker.selectedDates.length > 0) {
                              fetch_data_opd();
                          }
                      } else {
                          endDatePicker.set('minDate', null);
                          if (endDatePicker.selectedDates.length > 0) {
                              fetch_data_opd();
                          }
                      }
                  }
              },
              onReady:(d,s,i)=>{
                  replaceYearToThai(i);
                  if(i.altInput) {
                      i.altInput.setAttribute("type", "search");
                      i.altInput.setAttribute("autocomplete", "new-password");
                      i.altInput.setAttribute("data-lpignore", "true");
                      // เพิ่ม name เปล่าๆ เพื่อไม่ให้เบราว์เซอร์เดาว่าเป็นฟิลด์สำคัญ
                      i.altInput.setAttribute("name", "dummy_date_" + Math.random());
                  }
              },
              onValueUpdate:(d,s,i)=>{
                  replaceYearToThai(i);
                  if(d[0]) i.altInput.value=`${d[0].getDate()} ${i.l10n.months.longhand[d[0].getMonth()]} ${d[0].getFullYear()+543}`;
              },
              onMonthChange:(d,s,i)=>replaceYearToThai(i),
              onYearChange:(d,s,i)=>replaceYearToThai(i)
          });

          endDatePickerIpd = flatpickr("#ipd_end_date",{
              locale:"th",
              dateFormat:"Y-m-d",
              altInput:true,
              altFormat:"j F Y",
              disableMobile:true,
              onChange: function(selectedDates, dateStr, instance) {
                  if (startDatePickerIpd) {
                      if (selectedDates.length > 0) {
                          startDatePickerIpd.set('maxDate', selectedDates[0]);
                          if (startDatePickerIpd.selectedDates.length > 0) {
                              fetch_data_ipd();
                          }
                      } else {
                          startDatePickerIpd.set('maxDate', null);
                          fetch_data_ipd();
                      }
                  }
              },
              onReady:(d,s,i)=>{
                  replaceYearToThai(i);
                  if(i.altInput) {
                      i.altInput.setAttribute("type", "search");
                      i.altInput.setAttribute("autocomplete", "new-password");
                      i.altInput.setAttribute("data-lpignore", "true");
                      i.altInput.setAttribute("name", "dummy_date_ipd_" + Math.random());
                  }
              },
              onValueUpdate:(d,s,i)=>{
                  replaceYearToThai(i);
                  if(d[0]) i.altInput.value=`${d[0].getDate()} ${i.l10n.months.longhand[d[0].getMonth()]} ${d[0].getFullYear()+543}`;
              },
              onMonthChange:(d,s,i)=>replaceYearToThai(i),
              onYearChange:(d,s,i)=>replaceYearToThai(i)
          });

          startDatePickerIpd = flatpickr("#ipd_start_date",{
              locale:"th",
              dateFormat:"Y-m-d",
              altInput:true,
              altFormat:"j F Y",
              disableMobile:true,
              onChange: function(selectedDates, dateStr, instance) {
                  if (endDatePickerIpd) {
                      if (selectedDates.length > 0) {
                          endDatePickerIpd.set('minDate', selectedDates[0]);
                          if (endDatePickerIpd.selectedDates.length > 0) {
                              fetch_data_ipd();
                          }
                      } else {
                          endDatePickerIpd.set('minDate', null);
                          if (endDatePickerIpd.selectedDates.length > 0) {
                              fetch_data_ipd();
                          }
                      }
                  }
              },
              onReady:(d,s,i)=>{
                  replaceYearToThai(i);
                  if(i.altInput) {
                      i.altInput.setAttribute("type", "search");
                      i.altInput.setAttribute("autocomplete", "new-password");
                      i.altInput.setAttribute("data-lpignore", "true");
                      i.altInput.setAttribute("name", "dummy_date_ipd_" + Math.random());
                  }
              },
              onValueUpdate:(d,s,i)=>{
                  replaceYearToThai(i);
                  if(d[0]) i.altInput.value=`${d[0].getDate()} ${i.l10n.months.longhand[d[0].getMonth()]} ${d[0].getFullYear()+543}`;
              },
              onMonthChange:(d,s,i)=>replaceYearToThai(i),
              onYearChange:(d,s,i)=>replaceYearToThai(i)
          });
      }
      
      // =========================================================================
      // 🌟 ระบบคัดกรองและนำเข้าลูกหนี้สิทธิ (Preview & Selective Import)
      // =========================================================================

      var currentImportContext = null;

      function recalculateRightsSummary() {
          var totalSelected = 0;
          var totalRows = 0;
          var totalDebit = 0.0;
          var allChecked = true;
          var anyPresent = false;

          $('.right-checkbox').each(function() {
              anyPresent = true;
              var row = $(this).closest('tr');
              if ($(this).is(':checked')) {
                  totalSelected++;
                  totalRows += parseInt($(this).data('count')) || 0;
                  totalDebit += parseFloat($(this).data('debit')) || 0;
                  row.removeClass('row-deselected');
              } else {
                  allChecked = false;
                  row.addClass('row-deselected');
              }
          });

          if (anyPresent) {
              $('#checkAllRightsHeader').prop('checked', allChecked);
          }

          $('#txtSelectedRightsSummary').html(`<b class="text-primary">${totalSelected}</b> สิทธิ (<b class="text-dark">${totalRows.toLocaleString()}</b> รายการ)`);
          $('#txtSelectedDebitSummary').html(`${totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท`);

          if (totalSelected === 0) {
              $('#btnConfirmImportRights').prop('disabled', true);
          } else {
              $('#btnConfirmImportRights').prop('disabled', false);
          }
      }

      $(document).on('click', '#btnSelectAllRights', function() {
          $('.right-checkbox').prop('checked', true);
          $('#checkAllRightsHeader').prop('checked', true);
          recalculateRightsSummary();
      });

      $(document).on('click', '#btnDeselectAllRights', function() {
          $('.right-checkbox').prop('checked', false);
          $('#checkAllRightsHeader').prop('checked', false);
          recalculateRightsSummary();
      });

      $(document).on('change', '#checkAllRightsHeader', function() {
          var isChecked = $(this).is(':checked');
          $('.right-checkbox').prop('checked', isChecked);
          recalculateRightsSummary();
      });

      $(document).on('change', '.right-checkbox', function() {
          recalculateRightsSummary();
      });

      $(document).on('keyup', '#searchRightsInput', function() {
          var val = $(this).val().toLowerCase();
          $('#tbodyRightsList tr').filter(function() {
              $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
          });
      });

      function showRightsPreviewModal(res, type) {
          currentImportContext = {
              type: type,
              temp_token: res.temp_token,
              month: res.month,
              year: res.year,
              rights: res.rights
          };

          $('#modalSelectRightsLabel span').text(`สรุปสิทธิการเงิน: นำเข้าลูกหนี้ ${type} (เดือน ${res.month}/${res.year})`);
          $('#kpiTotalRightsCount').text((res.rights_count || 0) + ' สิทธิ');
          $('#kpiTotalRecordsCount').text((res.total_rows || 0).toLocaleString() + ' รายการ');
          $('#kpiTotalDebitAmount').text((res.total_debit || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

          $('#searchRightsInput').val('');
          var tbody = $('#tbodyRightsList');
          tbody.empty();

          if (res.rights && res.rights.length > 0) {
              res.rights.forEach(function(item) {
                  var debitStr = (parseFloat(item.total_debit) || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                  var countStr = (parseInt(item.count) || 0).toLocaleString();
                  var tr = `
                      <tr>
                          <td style="text-align: center;">
                              <input type="checkbox" class="form-check-input right-checkbox" value="${item.accountcode}" checked data-count="${item.count}" data-debit="${item.total_debit}">
                          </td>
                          <td>
                              <span class="badge bg-label-primary font-monospace" style="font-size: 13px;">${item.accountcode}</span>
                          </td>
                          <td class="fw-semibold text-dark">${item.accountname}</td>
                          <td style="text-align: right;">
                              <span class="badge bg-label-secondary rounded-pill" style="font-size: 13px;">${countStr}</span>
                          </td>
                          <td style="text-align: right;" class="fw-bold text-dark">${debitStr}</td>
                      </tr>
                  `;
                  tbody.append(tr);
              });
          } else {
              tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">ไม่พบรายการสิทธิการเงิน</td></tr>');
          }

          recalculateRightsSummary();
          $('#modalSelectRights').modal('show');
      }

      $(document).on('click', '#btnConfirmImportRights', function() {
          if (!currentImportContext) return;

          var selectedRights = [];
          $('.right-checkbox:checked').each(function() {
              selectedRights.push($(this).val());
          });

          if (selectedRights.length === 0) {
              Swal.fire({
                  icon: 'warning',
                  title: 'แจ้งเตือน',
                  text: 'กรุณาเลือกอย่างน้อย 1 สิทธิการเงินเพื่อนำเข้า',
                  confirmButtonText: 'ตกลง'
              });
              return;
          }

          $('#modalSelectRights').modal('hide');

          if (currentImportContext.type === 'OPD') {
              executeImportOPD(currentImportContext, selectedRights);
          } else {
              executeImportIPD(currentImportContext, selectedRights);
          }
      });

      $("#upload-formOPD").on('submit', function(e) {
          e.preventDefault();

          var month = $("select[name='month']").val();
          var year = $("select[name='year']").val();

          if (month === "00" || year === "00") {
              Swal.fire({
                  icon: 'error',
                  title: 'กรุณาเลือกเดือนและปีให้ถูกต้อง',
                  confirmButtonText: 'ตกลง'
              });
              return false;
          }

          var formData = new FormData(this);
          formData.append('type', 'OPD');

          $("#submit_opd").prop("disabled", true);
          Swal.fire({
              title: 'กำลังตรวจสอบไฟล์ Excel...',
              text: 'ระบบกำลังอ่านและสรุปสิทธิการเงิน กรุณารอสักครู่',
              allowOutsideClick: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          $.ajax({
              url: "ลูกหนี้สิทธิ-preview.php",
              type: "POST",
              data: formData,
              processData: false,
              contentType: false,
              dataType: 'json',
              timeout: 600000,
              success: function(res) {
                  $("#submit_opd").prop("disabled", false);
                  Swal.close();

                  if (res.status === "success") {
                      showRightsPreviewModal(res, 'OPD');
                  } else {
                      Swal.fire({
                          title: "เกิดข้อผิดพลาด!",
                          text: res.message || "ไม่สามารถอ่านข้อมูลสิทธิการเงินจากไฟล์ได้",
                          icon: "error",
                          confirmButtonText: "ตกลง"
                      });
                  }
              },
              error: function(xhr, status, error) {
                  $("#submit_opd").prop("disabled", false);
                  Swal.close();
                  Swal.fire({
                      title: "เกิดข้อผิดพลาดในการเชื่อมต่อ!",
                      text: error,
                      icon: "error",
                      confirmButtonText: "ตกลง"
                  });
              }
          });
      });

      function updateProgressUI(barId, responseText) {
          var bar = $(barId);
          if (!responseText) return;

          if (responseText.startsWith("R_")) {
              var readPercent = parseInt(responseText.replace("R_", ""));
              if (!isNaN(readPercent)) {
                  bar.css({"width": readPercent + "%", "background-color": "#8b5cf6"}).text("กำลังอ่านไฟล์ Excel... " + readPercent + "%");
              }
          } else if (responseText === "STAGE_PREP") {
              bar.css({"width": "100%", "background-color": "#0284c7"}).text("กำลังแปลงชุดข้อมูลและเตรียมบันทึก...");
          } else if (responseText.startsWith("DB_")) {
              var parts = responseText.split("_");
              var percent = parseInt(parts[1]) || 0;
              var current = parseInt(parts[2]) || 0;
              var total = parseInt(parts[3]) || 0;
              var txt = "กำลังนำเข้าฐานข้อมูล " + percent + "%";
              if (total > 0) {
                  txt += " (" + current.toLocaleString() + " / " + total.toLocaleString() + " รายการ)";
              }
              bar.css({"width": Math.max(percent, 5) + "%", "background-color": "#f59e0b"}).text(txt);
          } else {
              var insertPercent = parseInt(responseText);
              if (!isNaN(insertPercent) && insertPercent >= 0 && insertPercent <= 100) {
                  if (insertPercent === 100) {
                      bar.css({"width": "100%", "background-color": "#22c55e"}).text("100% (ประมวลผลเสร็จสิ้น)");
                  } else {
                      bar.css({"width": insertPercent + "%", "background-color": "#f59e0b"}).text("กำลังนำเข้าฐานข้อมูล " + insertPercent + "%");
                  }
              }
          }
      }

      function executeImportOPD(context, selectedRights) {
          var taskId = Date.now().toString();

          $("#progress-container").show();
          $("#progress-bar").css({"width": "0%", "background-color": "#3b82f6"}).text("0% (กำลังเตรียมบันทึกข้อมูล...)");
          $("#submit_opd").prop("disabled", true);

          var progressInterval = setInterval(function() {
              $.get("check_progress.php?task_id=" + taskId, function(data) {
                  updateProgressUI("#progress-bar", data.trim());
              });
          }, 800);

          $.ajax({
              url: "ลูกหนี้สิทธิ-insert-OPD.php",
              type: "POST",
              dataType: "json",
              data: {
                  task_id: taskId,
                  temp_token: context.temp_token,
                  month: context.month,
                  year: context.year,
                  selected_rights: JSON.stringify(selectedRights)
              },
              timeout: 600000,
              success: function(res) {
                  clearInterval(progressInterval);
                  $("#progress-container").hide();
                  $("#submit_opd").prop("disabled", false);

                  if (res.status === "success") {
                      Swal.fire({
                          title: "นำเข้าข้อมูลเสร็จสิ้น!",
                          icon: "success",
                          html: `
                              <div style="text-align:left; display:inline-block; font-size:16px;">
                                  <div style="display:flex; align-items:center; margin-bottom:8px;">
                                      <span style="color:#22c55e; margin-right:8px;">✔</span>
                                      <span style="width:140px;">นำเข้าสำเร็จ</span>
                                      <span>: ${res.insert} รายการ</span>
                                  </div>
                                  <div style="display:flex; align-items:center; margin-bottom:8px;">
                                      <span style="color:#3b82f6; margin-right:8px;">🔄</span>
                                      <span style="width:140px;">อัปเดต</span>
                                      <span>: ${res.update} รายการ</span>
                                  </div>
                                  <div style="display:flex; align-items:center; margin-bottom:8px;">
                                      <span style="color:#f59e0b; margin-right:8px;">⏭️</span>
                                      <span style="width:140px;">ข้ามสิทธิที่ไม่เลือก</span>
                                      <span>: ${res.skipped || 0} รายการ</span>
                                  </div>
                                  <div style="display:flex; align-items:center;">
                                      <span style="color:#ef4444; margin-right:8px;">✖</span>
                                      <span style="width:140px;">ผิดพลาด</span>
                                      <span>: ${res.error || 0} รายการ</span>
                                  </div>
                              </div>
                          `,
                          confirmButtonText: "ตกลง"
                      }).then(() => {
                          $("#upload-formOPD").trigger("reset");
                          location.reload();
                      });
                  } else {
                      Swal.fire({
                          title: "ข้อผิดพลาด!",
                          text: res.message || "เกิดข้อผิดพลาดจากฝั่งเซิร์ฟเวอร์",
                          icon: "error",
                          confirmButtonText: "ตกลง"
                      });
                  }
              },
              error: function(xhr, status, error) {
                  clearInterval(progressInterval);
                  $("#progress-container").hide();
                  $("#submit_opd").prop("disabled", false);
                  Swal.fire({
                      title: "เกิดข้อผิดพลาดในการเชื่อมต่อ!",
                      text: error,
                      icon: "error",
                      confirmButtonText: "ตกลง"
                  });
              }
          });
      }

      $("#upload-formIPD").on('submit', function (e) {
          e.preventDefault();

          var monthi = $("select[name='monthi']").val();
          var yeari = $("select[name='yeari']").val();

          if (monthi === "00" || yeari === "00") {
              Swal.fire({
                  icon: 'error',
                  title: 'กรุณาเลือกเดือนและปีให้ถูกต้อง',
                  confirmButtonText: 'ตกลง'
              });
              return false;
          }

          var formData = new FormData(this);
          formData.append('type', 'IPD');

          $("#submit_ipd").prop("disabled", true);
          Swal.fire({
              title: 'กำลังตรวจสอบไฟล์ Excel...',
              text: 'ระบบกำลังอ่านและสรุปสิทธิการเงิน กรุณารอสักครู่',
              allowOutsideClick: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          $.ajax({
              url: "ลูกหนี้สิทธิ-preview.php",
              type: "POST",
              data: formData,
              processData: false,
              contentType: false,
              dataType: 'json',
              timeout: 600000,
              success: function(res) {
                  $("#submit_ipd").prop("disabled", false);
                  Swal.close();

                  if (res.status === "success") {
                      showRightsPreviewModal(res, 'IPD');
                  } else {
                      Swal.fire({
                          title: "เกิดข้อผิดพลาด!",
                          text: res.message || "ไม่สามารถอ่านข้อมูลสิทธิการเงินจากไฟล์ได้",
                          icon: "error",
                          confirmButtonText: "ตกลง"
                      });
                  }
              },
              error: function(xhr, status, error) {
                  $("#submit_ipd").prop("disabled", false);
                  Swal.close();
                  Swal.fire({
                      title: "เกิดข้อผิดพลาดในการเชื่อมต่อ!",
                      text: error,
                      icon: "error",
                      confirmButtonText: "ตกลง"
                  });
              }
          });
      });

      function executeImportIPD(context, selectedRights) {
          var taskId = Date.now().toString();

          $("#progress-container-ipd").show();
          $("#progress-bar-ipd").css({"width": "0%", "background-color": "#2563eb"}).text("0% (กำลังเตรียมบันทึกข้อมูล...)");
          $("#submit_ipd").prop("disabled", true);

          var progressInterval = setInterval(function() {
              $.get("check_progress.php?task_id=" + taskId, function(data) {
                  updateProgressUI("#progress-bar-ipd", data.trim());
              });
          }, 800);

          $.ajax({
              url: "ลูกหนี้สิทธิ-insert-IPD.php",
              type: "POST",
              dataType: "json",
              data: {
                  task_id: taskId,
                  temp_token: context.temp_token,
                  monthi: context.month,
                  yeari: context.year,
                  selected_rights: JSON.stringify(selectedRights)
              },
              timeout: 600000,
              success: function(res) {
                  clearInterval(progressInterval);
                  $("#progress-container-ipd").hide();
                  $("#submit_ipd").prop("disabled", false);

                  if (res.status === "success") {
                      Swal.fire({
                          title: "นำเข้าข้อมูลเสร็จสิ้น!",
                          icon: "success",
                          html: `
                              <div style="text-align:left; display:inline-block; font-size:16px;">
                                  <div style="display:flex; align-items:center; margin-bottom:8px;">
                                      <span style="color:#22c55e; margin-right:8px;">✔</span>
                                      <span style="width:140px;">นำเข้าสำเร็จ</span>
                                      <span>: ${res.insert} รายการ</span>
                                  </div>
                                  <div style="display:flex; align-items:center; margin-bottom:8px;">
                                      <span style="color:#3b82f6; margin-right:8px;">🔄</span>
                                      <span style="width:140px;">อัปเดต</span>
                                      <span>: ${res.update} รายการ</span>
                                  </div>
                                  <div style="display:flex; align-items:center; margin-bottom:8px;">
                                      <span style="color:#f59e0b; margin-right:8px;">⏭️</span>
                                      <span style="width:140px;">ข้ามสิทธิที่ไม่เลือก</span>
                                      <span>: ${res.skipped || 0} รายการ</span>
                                  </div>
                                  <div style="display:flex; align-items:center;">
                                      <span style="color:#ef4444; margin-right:8px;">✖</span>
                                      <span style="width:140px;">ผิดพลาด</span>
                                      <span>: ${res.error || 0} รายการ</span>
                                  </div>
                              </div>
                          `,
                          confirmButtonText: "ตกลง"
                      }).then(() => {
                          $("#upload-formIPD").trigger("reset");
                          location.reload();
                      });
                  } else {
                      Swal.fire({
                          title: "ข้อผิดพลาด!",
                          text: res.message || "เกิดข้อผิดพลาดจากฝั่งเซิร์ฟเวอร์",
                          icon: "error",
                          confirmButtonText: "ตกลง"
                      });
                  }
              },
              error: function(xhr, status, error) {
                  clearInterval(progressInterval);
                  $("#progress-container-ipd").hide();
                  $("#submit_ipd").prop("disabled", false);
                  Swal.fire({
                      title: "เกิดข้อผิดพลาดในการเชื่อมต่อ!",
                      text: error,
                      icon: "error",
                      confirmButtonText: "ตกลง"
                  });
              }
          });
      }

      function replaceYearToThai(i){
          if(i.currentYearElement) i.currentYearElement.value=i.currentYear+543;
      }

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

// ==================== DELETE OPD ====================
        let deleteInProgressOPD = false; // ป้องกันการกดซ้ำ
        // กดปุ่มลบ → ตรวจสอบเดือนปีก่อนเปิด modal
        function delete_opd(){
          const month = $('select[name="month"]').val();
          const year = $('select[name="year"]').val();

          if (month === '00' || year === '00' || !month || !year) {
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกเดือนและปีของ OPD ให้ครบถ้วนก่อนลบข้อมูล', 'warning');
            return;
          }

          $('#delete_password_opd').val(''); // เคลียร์รหัสผ่านก่อนแสดง modal
          $('#passwordModalOPD').modal('show');
        }

        // กดยืนยันลบ OPD
        $('#confirm_delete_opd').on('click', function () {
          const password = $('#delete_password_opd').val().trim();
          const month = $('select[name="month"]').val();
          const year = $('select[name="year"]').val();

          if (!password) {
            Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสผ่านก่อนยืนยันการลบ', 'warning');
            return;
          }

          if (month === '00' || year === '00' || !month || !year) {
            $('#passwordModalOPD').modal('hide');
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกเดือนและปีของ OPD ให้ครบถ้วนก่อนลบข้อมูล', 'warning');
            return;
          }

          if (deleteInProgressOPD) return;
          deleteInProgressOPD = true;

          $.ajax({
            url: 'ลบลูกหนี้สิทธิopd.php',
            type: 'POST',
            dataType: 'json',
            data: {
              action: 'delete_opd',
              password: password,
              month: month,
              year: year
            },
            beforeSend: function () {
              $('#confirm_delete_opd').prop('disabled', true).text('กำลังลบ...');
            },
            success: function (response) {
              $('#passwordModalOPD').modal('hide');

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
              Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
            },
            complete: function () {
              deleteInProgressOPD = false;
              $('#confirm_delete_opd').prop('disabled', false).text('ยืนยันลบ OPD');
            }
          });
        });

        // เคลียร์รหัสเมื่อปิด modal OPD
        $('#passwordModalOPD').on('hidden.bs.modal', function () {
          $('#delete_password_opd').val('');
          $('#confirm_delete_opd').prop('disabled', false).text('ยืนยันลบ OPD');
          deleteInProgressOPD = false;
        });


// ==================== DELETE IPD ====================
        let deleteInProgressIPD = false; // ป้องกันการกดซ้ำ
        // กดปุ่มลบ → ตรวจสอบเดือนปีก่อนเปิด modal
        function delete_ipd(){
          const monthi = $('select[name="monthi"]').val();
          const yeari = $('select[name="yeari"]').val();

          if (monthi === '00' || yeari === '00' || !monthi || !yeari) {
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกเดือนและปีของ IPD ให้ครบถ้วนก่อนลบข้อมูล', 'warning');
            return;
          }

          $('#delete_password_ipd').val(''); // เคลียร์รหัสผ่านก่อนแสดง modal
          $('#passwordModalIPD').modal('show');
        }

        // กดยืนยันลบ IPD
        $('#confirm_delete_ipd').on('click', function () {
          const passwordi = $('#delete_password_ipd').val().trim();
          const monthi = $('select[name="monthi"]').val();
          const yeari = $('select[name="yeari"]').val();

          if (!passwordi) {
            Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสผ่านก่อนยืนยันการลบ', 'warning');
            return;
          }

          if (monthi === '00' || yeari === '00' || !monthi || !yeari) {
            $('#passwordModalIPD').modal('hide');
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกเดือนและปีของ IPD ให้ครบถ้วนก่อนลบข้อมูล', 'warning');
            return;
          }

          if (deleteInProgressIPD) return;
          deleteInProgressIPD = true;

          $.ajax({
            url: 'ลบลูกหนี้สิทธิipd.php',
            type: 'POST',
            dataType: 'json',
            data: {
              action: 'delete_ipd',
              password: passwordi,
              monthi: monthi,
              yeari: yeari
            },
            beforeSend: function () {
              $('#confirm_delete_ipd').prop('disabled', true).text('กำลังลบ...');
            },
            success: function (response) {
              $('#passwordModalIPD').modal('hide');

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
              Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
            },
            complete: function () {
              deleteInProgressIPD = false;
              $('#confirm_delete_ipd').prop('disabled', false).text('ยืนยันลบ IPD');
            }
          });
        });

        // เคลียร์รหัสเมื่อปิด modal IPD
        $('#passwordModalIPD').on('hidden.bs.modal', function () {
          $('#delete_password_ipd').val('');
          $('#confirm_delete_ipd').prop('disabled', false).text('ยืนยันลบ IPD');
          deleteInProgressIPD = false;
        });






        function fetch_data_opd()
        { 

            $('#progressBar').css('width', '0%').attr('aria-valuenow', 0);
            $('#tbopdload').show();
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
          var start_date = $('#opd_start_date').val();
          var end_date = $('#opd_end_date').val();
         // console.log(action);
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{action:action, start_date_opd: start_date, end_date_opd: end_date},
            success:function(data)
            {
              
              $('#progressBar').css('width', '100%').attr('aria-valuenow', 100);
              $('#data_tabel_listview').html(data);
              setTimeout(function() {
                $('#progressBar').css('width', '0%').attr('aria-valuenow', 0);
              }, 1000); 
              $('#tbopdload').hide();


              $('#data_tabel_opd').html(data);
            }
          })
        }

        function fetch_data_ipd()
        { 
          $('#tbipdload').show();
          var action2 = $('#selectTypeOpt').val();
          var start_date = $('#ipd_start_date').val();
          var end_date = $('#ipd_end_date').val();
          
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{action2:action2, start_date_ipd: start_date, end_date_ipd: end_date},
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
              html: 'รอสักครู่นะ...',
              allowOutsideClick: false,
              scrollbarPadding: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          var stmgeto = data;
          var yyy = $('#selectTypeOpt').val();
          var start_date = $('#opd_start_date').val();
          var end_date = $('#opd_end_date').val();

          $('#modal_dept_heading').text('รายละเอียดข้อมูลลูกหนี้รายตัว (ผู้ป่วยนอก)');
          $('#titlestm').html('🏷️ รหัสผังบัญชี ' + data + ' | ' + dt);

          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{stmgeto:stmgeto,yyy:yyy, start_date_opd: start_date, end_date_opd: end_date},
            success:function(data)
            {
              // ปิด SweetAlert เมื่อสำเร็จ
              Swal.close();

              $('#data_tabel_stm').html(data);

              // 🌟 สร้างแท็บจำแนกกลุ่มย่อย CR แบบ Dynamic
              buildCrSubgroupTabs();

              var rowCount = $('#data_tabel_listview tr.main-patient-row').length;
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
          var start_date = $('#ipd_start_date').val();
          var end_date = $('#ipd_end_date').val();

          $('#modal_dept_heading').text('รายละเอียดข้อมูลลูกหนี้รายตัว (ผู้ป่วยใน)');
          $('#titlestm').html('🏷️ รหัสผังบัญชี ' + data + ' | ' + dt);

          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{stmgeti:stmgeti, yyy:yyy, start_date_ipd: start_date, end_date_ipd: end_date},
            success:function(data)
            {
              // ปิด SweetAlert เมื่อสำเร็จ
              Swal.close();
                
              $('#data_tabel_stm').html(data);

              // 🌟 สร้างแท็บจำแนกกลุ่มย่อย CR แบบ Dynamic (IPD)
              buildCrSubgroupTabs();

              var rowCount = $('#data_tabel_listview tr.main-patient-row, #data_tabel_listview_ipd tr.main-patient-row').length;
              $('#txtq0').text(rowCount + ' คน');

              var i_inc = $('#data_tabel_listview_ipd').closest('table').find('tfoot tr.summary-total-row td').eq(1).text().trim();
              var i_paid = $('#data_tabel_listview_ipd').closest('table').find('tfoot tr.summary-total-row td').eq(2).text().trim();
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

            rows.each(function () {
                var tr = $(this);
                if (tr.hasClass('main-patient-row')) {
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

            // คำนวณยอดรวมใหม่แบบ Realtime
            recalculateModalSummary();
        }

        function formatMoneyJS(num) {
            return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function recalculateModalSummary() {
            var totalMainRows = $("#data_tabel_listview tr.main-patient-row, #data_tabel_listview_ipd tr.main-patient-row").length;
            var visibleMainRows = $("#data_tabel_listview tr.main-patient-row:visible, #data_tabel_listview_ipd tr.main-patient-row:visible").length;
            
            var isIpd = $('#data_tabel_listview_ipd').length > 0 || $('#data_tabel_listview').closest('table').find('thead th:contains("AN")').length > 0;
            var currentTable = $('#data_tabel_listview, #data_tabel_listview_ipd').closest('table');
            var tfoot = currentTable.find('tfoot tr.summary-total-row');

            var sum_income = 0, sum_paid = 0, sum_debit = 0, sum_stm = 0, sum_dckd = 0, sum_diff = 0;

            $("#data_tabel_listview tr.main-patient-row:visible, #data_tabel_listview_ipd tr.main-patient-row:visible").each(function () {
                var tr = $(this);
                if (!isIpd) {
                    sum_income += parseFloat(tr.find('td').eq(8).text().replace(/,/g, '')) || 0;
                    sum_paid   += parseFloat(tr.find('td').eq(9).text().replace(/,/g, '')) || 0;
                    sum_debit  += parseFloat(tr.find('td').eq(10).text().replace(/,/g, '')) || 0;
                    sum_stm    += parseFloat(tr.find('td').eq(11).text().replace(/,/g, '')) || 0;
                    sum_dckd   += parseFloat(tr.find('td').eq(12).text().replace(/,/g, '')) || 0;
                    sum_diff   += parseFloat(tr.find('td').eq(13).text().replace(/,/g, '')) || 0;
                } else {
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

        // ระบบค้นหาข้อมูลในตาราง Modal (ผสานร่วมกับการกรองแท็บ CR)
        $(document).on("keyup input", "#myInputModal", function () {
            filterTableByCrSubgroup();
        });

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
        
        // รีเซ็ตการเลือกช่วงเวลา OPD โดยไม่ให้ trigger event onChange ซ้ำซ้อน
        var sdp = document.querySelector("#opd_start_date")._flatpickr;
        var edp = document.querySelector("#opd_end_date")._flatpickr;
        if (sdp) {
            sdp.clear(false);
            sdp.set('maxDate', null);
        }
        if (edp) {
            edp.clear(false);
            edp.set('minDate', null);
        }

        // รีเซ็ตการเลือกช่วงเวลา IPD โดยไม่ให้ trigger event onChange ซ้ำซ้อน
        var sdp_ipd = document.querySelector("#ipd_start_date")._flatpickr;
        var edp_ipd = document.querySelector("#ipd_end_date")._flatpickr;
        if (sdp_ipd) {
            sdp_ipd.clear(false);
            sdp_ipd.set('maxDate', null);
        }
        if (edp_ipd) {
            edp_ipd.clear(false);
            edp_ipd.set('minDate', null);
        }

        fetch_data_opd(yyy);
        fetch_data_ipd(yyy);
        
      });


      function get_stat_all_02(){

            $("#dataname1").text($("#tdata-y1").text());
            $("#dataname2").text($("#tdata-y2").text());
            $("#dataname3").text($("#tdata-y3").text());
            $("#dataname4").text($("#tdata-y4").text());
            $("#dataname5").text($("#tdata-y5").text());

            $("#datacc1").text($("#n_service-y1").text());
            $("#datacc2").text($("#n_service-y2").text());
            $("#datacc3").text($("#n_service-y3").text());
            $("#datacc4").text($("#n_service-y4").text());
            $("#datacc5").text($("#n_service-y5").text());
            


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
                    text: 'จำนวนการให้สิทธิการรักษาไม่ถูกต้อง ตามลำดับ'
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
                        text: 'จำนวนครั้ง'
                    }
                },
                  plotOptions: {
                    series: {
                      borderWidth: 0,
                      color: '#0a6a05',
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

  <?php if (!empty($_SESSION['role'])): ?>
  <!-- ✅ Quick Search (Ctrl+K) -->
  <?php include './includes/quick_search_ui.html'; ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['role'])): ?>
  <!-- ⚙️ Modal ตั้งค่ากลุ่มย่อย CR -->
  <?php include_once './modal_cr_settings.php'; ?>
  <!-- ⚙️ Modal ตั้งค่ากลุ่มย่อย ประกันสังคม SSS -->
  <?php include_once './modal_sss_settings.php'; ?>
  <?php endif; ?>

  <!-- 🌟 Modal แสดงรายละเอียดรายการ CR ของคนไข้รายตัว -->
  <?php include_once './includes/modal_cr_patient_breakdown.php'; ?>
  <!-- 🌟 Modal แสดงรายละเอียดรายการ Instrument ประกันสังคม ของคนไข้รายตัว -->
  <?php include_once './includes/modal_sss_patient_breakdown.php'; ?>

  <!-- 🔄 Modal ดึงข้อมูลลูกหนี้ตรงจาก HOSxP (Direct HOSxP Debtor Pull) -->
  <?php include_once __DIR__ . '/apihos/modal_direct_debtor_pull.php'; ?>

  </body>
</html>
