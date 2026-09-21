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

    <meta name="description" content="ระบบเงินชดเชยและลูกหนี้รายตัวโรงพยาบาล" />

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

    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

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
#myModalgetlistview.modal {
  padding: 0 !important;
  overflow: hidden !important;
  height: 100vh !important;
  width: 100vw !important;
}

#myModalgetlistview .modal-dialog,
#myModalgetlistview .modal-dialog.modal-fullscreen {
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

#myModalgetlistview .modal-content {
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

#myModalgetlistview .modal-header-luxury {
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

#myModalgetlistview .modal-body {
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
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
  margin-bottom: 12px;
  flex-shrink: 0 !important;
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
.kpi-card.kpi-expense::before { background: #0284c7; }
.kpi-card.kpi-paid::before { background: #059669; }
.kpi-card.kpi-debit::before { background: #ea580c; }
.kpi-card.kpi-comp::before { background: #9333ea; }

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
.kpi-expense .kpi-icon-box { background: #f0f9ff; color: #0284c7; }
.kpi-paid .kpi-icon-box { background: #ecfdf5; color: #059669; }
.kpi-debit .kpi-icon-box { background: #fff7ed; color: #ea580c; }
.kpi-comp .kpi-icon-box { background: #faf5ff; color: #9333ea; }

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
.kpi-expense .kpi-value { color: #0284c7 !important; }
.kpi-paid .kpi-value { color: #059669 !important; }
.kpi-debit .kpi-value { color: #ea580c !important; }
.kpi-comp .kpi-value { color: #9333ea !important; }

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

.search-input-group input {
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

.search-input-group input:focus {
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

/* Modal Table Container & Smooth Scrolling */
#myModalgetlistview .table-scroll,
.table-scroll-modal {
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

#myModalgetlistview .table-scroll::-webkit-scrollbar,
.table-scroll-modal::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
#myModalgetlistview .table-scroll::-webkit-scrollbar-track,
.table-scroll-modal::-webkit-scrollbar-track {
  background: #f1f5f9;
}
#myModalgetlistview .table-scroll::-webkit-scrollbar-thumb,
.table-scroll-modal::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
#myModalgetlistview .table-scroll::-webkit-scrollbar-thumb:hover,
.table-scroll-modal::-webkit-scrollbar-thumb:hover {
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

.stmgetovlookup tbody tr:not(.spacer-row):hover {
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
.stmgetovlookup tfoot {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 25 !important;
  background: #293b5b !important;
}

.stmgetovlookup tfoot tr,
.stmgetovlookup tfoot tr.summary-total-row {
  height: 45px !important;
  background: #293b5b !important;
  color: #ffffff !important;
}

.stmgetovlookup tfoot td,
.stmgetovlookup tfoot tr td {
  background: #293b5b !important;
  color: #ffffff !important;
  border-top: 1px solid rgba(255, 255, 255, 0.2) !important;
  border-bottom: none !important;
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
  padding: 10px 14px !important;
  font-size: 14px !important;
  font-weight: 700 !important;
  height: 45px !important;
  vertical-align: middle !important;
  white-space: nowrap !important;
  box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.25) !important;
}

.stmgetovlookup tfoot td:first-child {
  color: #ffffff !important;
  font-size: 14px !important;
  letter-spacing: 0.5px;
}

.stmgetovlookup tfoot td * {
  color: #ffffff !important;
}
.stmgetovlookup tfoot td#o1,
.stmgetovlookup tfoot td#i1 {
  color: #7dd3fc !important;
}
.stmgetovlookup tfoot td#o2,
.stmgetovlookup tfoot td#i2 {
  color: #86efac !important;
}
.stmgetovlookup tfoot td#o3,
.stmgetovlookup tfoot td#i3 {
  color: #fdba74 !important;
}
.stmgetovlookup tfoot td#o4,
.stmgetovlookup tfoot td#i4 {
  color: #f472b6 !important;
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

.btn-print-report {
  background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
  color: #ffffff !important;
}
.btn-print-report:hover {
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
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
  background: rgba(255, 255, 255, 0.25) !important;
  color: #ffffff !important;
}

/* =========================================================================
   🌟 Modern Compensation Page UI (เงินชดเชยประจำเดือน OPD & IPD)
   ========================================================================= */
.comp-filter-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #06b6d4 !important;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px 28px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}
.comp-filter-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.comp-table-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px 28px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}
.comp-table-card.opd-card {
  border-left: 5px solid #10b981 !important;
}
.comp-table-card.ipd-card {
  border-left: 5px solid #2563eb !important;
}
.comp-table-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.comp-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 16px;
  margin-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
}

.comp-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.comp-header-icon-filter {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
  color: #0891b2;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 2px 8px rgba(6, 182, 212, 0.25);
  flex-shrink: 0;
}

.comp-header-icon-opd {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #15803d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
  flex-shrink: 0;
}

.comp-header-icon-ipd {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1d4ed8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
  flex-shrink: 0;
}

.comp-header-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.comp-header-subtitle {
  font-size: 0.82rem;
  color: #64748b;
  margin: 2px 0 0 0;
}

.comp-pill-badge {
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

.comp-field-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.comp-select {
  border-radius: 10px !important;
  border: 1px solid #cbd5e1 !important;
  padding: 10px 14px !important;
  font-size: 13.5px !important;
  color: #1e293b !important;
  background-color: #ffffff !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.2s ease !important;
}

.comp-select:focus {
  border-color: #06b6d4 !important;
  box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.16) !important;
  background-color: #ffffff !important;
}

.comp-report-table {
  border-collapse: separate !important;
  border-spacing: 0 !important;
  border-radius: 10px !important;
  overflow: hidden !important;
  border: 1px solid #e2e8f0 !important;
}
.comp-report-table thead tr:first-child td {
  background: #f8fafc !important;
  color: #0f172a !important;
  font-size: 14.5px !important;
  padding: 12px !important;
  border-bottom: 2px solid #cbd5e1 !important;
}
.comp-report-table thead tr:nth-child(2) td, .comp-report-table thead tr:nth-child(3) td {
  background: #f1f5f9 !important;
  color: #334155 !important;
  font-size: 13px !important;
  padding: 9px 8px !important;
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

          <!-- Navbar -->
          <div class="container-fluid">
            <div class="row">

              <div class="col-12">

                <!-- Card ตัวกรองเงินชดเชยประจำเดือน -->
                <div class="comp-filter-card">
                  <div class="comp-card-header">
                    <div class="comp-header-left">
                      <div class="comp-header-icon-filter">
                        <i class='bx bx-filter-alt'></i>
                      </div>
                      <div>
                        <h5 class="comp-header-title">เงินชดเชยประจำเดือน</h5>
                        <p class="comp-header-subtitle">กรองข้อมูลรายงานสรุปเงินชดเชยตามช่วงเดือนและกลุ่มงานบริการเฉพาะ</p>
                      </div>
                    </div>
                    <div>
                      <span class="comp-pill-badge">
                        <i class='bx bx-calendar-event text-primary'></i> ข้อมูลรายเดือน
                      </span>
                    </div>
                  </div>

                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="comp-field-label"><i class='bx bx-calendar-play text-primary'></i> เริ่มเดือน</label>
                      <select id="selectTypeOpts" name="selectTypeOpts" class="form-select comp-select">
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
                            echo '<option value="'.$month.'" '.$selected.'>เริ่มเดือน '.$month.'</option>';
                          }
                        } else {
                          echo '<option value="0-0000" selected>-ไม่มีข้อมูล-</option>';
                        }
                        ?>
                      </select>
                    </div>

                    <div class="col-md-4">
                      <label class="comp-field-label"><i class='bx bx-calendar-check text-primary'></i> ถึงเดือน</label>
                      <select id="selectTypeOpte" name="selectTypeOpte" class="form-select comp-select">
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
                            echo '<option value="'.$month.'" '.$selected.'>ถึงเดือน '.$month.'</option>';
                          }
                        } else {
                          echo '<option value="0-0000" selected>-ไม่มีข้อมูล-</option>';
                        }
                        ?>
                      </select>
                    </div>

                    <div class="col-md-4">
                      <label class="comp-field-label"><i class='bx bx-category text-warning'></i> แผนก / กลุ่มบริการเฉพาะ</label>
                      <select id="selectTypeOptpp" name="selectTypeOptpp" class="form-select comp-select">
                        <option value="00" selected>ทั้งหมด</option>
                        <option value="01">ทันตกรรม</option>
                        <option value="02">กายภาพบำบัด</option>
                        <option value="03">แพทย์แผนไทย</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Card เงินชดเชยตามสิทธิการเงิน OPD -->
                <div class="comp-table-card opd-card">
                  <div class="comp-card-header">
                    <div class="comp-header-left">
                      <div class="comp-header-icon-opd">
                        <i class='bx bx-plus-medical'></i>
                      </div>
                      <div>
                        <h5 class="comp-header-title">เงินชดเชยตามสิทธิการเงิน OPD</h5>
                        <p class="comp-header-subtitle">รายงานสรุปลูกหนี้ ตามสิทธิการเงิน ผู้ป่วยนอก ประจำเดือน</p>
                      </div>
                    </div>
                    <div>
                      <span class="badge bg-label-success" style="font-size: 12.5px; padding: 6px 12px; border-radius: 20px;">
                        <i class='bx bx-user me-1'></i> ผู้ป่วยนอก (OPD)
                      </span>
                    </div>
                  </div>

                  <div>
                    <h5 class="modal-title" id="tbopdload" style="text-align: center; margin: 20px; display: none; color: #0f172a; font-weight: 600;">
                      <span class="spinner-border spinner-border-sm text-success me-2" role="status"></span> รอสักครู่กำลังโหลดข้อมูลลูกหนี้ OPD... <img src="./images/load.gif" alt="" style="width:24px;" />
                    </h5>
                    
                    <div class="table-responsive">
                      <table class="table comp-report-table table-hover align-middle">
                        <thead>
                          <tr>
                            <td style="text-align: center; font-weight: 700;" colspan="9">รายงานสรุปลูกหนี้ ตามสิทธิการเงิน ผู้ป่วยนอก ประจำเดือน</td>
                          </tr>
                          <tr>
                            <td style="text-align: center; font-weight: 600; width: 5%; vertical-align: middle;" rowspan="2">รหัส</td>
                            <td style="text-align: center; font-weight: 600; width: 32.2%; vertical-align: middle;" rowspan="2">สิทธิการเงิน</td>
                            <td style="text-align: center; font-weight: 600; width: 20%;" colspan="2">ลูกหนี้</td>
                            <td style="text-align: center; font-weight: 600;" colspan="2">รายการ HOSxP</td>
                            <td style="text-align: center; font-weight: 600; vertical-align: middle;" rowspan="2">ภาระหนี้</td>
                            <td style="text-align: center; font-weight: 600; vertical-align: middle;" rowspan="2">เงินชดเชย</td>
                            <td style="text-align: center; font-weight: 600; vertical-align: middle;" rowspan="2">ส่วนต่าง</td>
                          </tr>
                          <tr>
                            <td style="text-align: center; font-weight: 600; width: 10%;">ทั้งหมด</td>
                            <td style="text-align: center; font-weight: 600; width: 10%;">คงเหลือ</td>
                            <td style="text-align: center; font-weight: 600;">ค่าใช้จ่าย</td>
                            <td style="text-align: center; font-weight: 600;">ชำระแล้ว</td>
                          </tr>
                        </thead>
                        <tbody id="pdfview" style="font-size: 13.5px;">

                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Card เงินชดเชยตามสิทธิการเงิน IPD -->
                <div class="comp-table-card ipd-card">
                  <div class="comp-card-header">
                    <div class="comp-header-left">
                      <div class="comp-header-icon-ipd">
                        <i class='bx bx-hotel'></i>
                      </div>
                      <div>
                        <h5 class="comp-header-title">เงินชดเชยตามสิทธิการเงิน IPD</h5>
                        <p class="comp-header-subtitle">รายงานสรุปลูกหนี้ ตามสิทธิการเงิน ผู้ป่วยใน ประจำเดือน</p>
                      </div>
                    </div>
                    <div>
                      <span class="badge bg-label-primary" style="font-size: 12.5px; padding: 6px 12px; border-radius: 20px;">
                        <i class='bx bx-bed me-1'></i> ผู้ป่วยใน (IPD)
                      </span>
                    </div>
                  </div>

                  <div>
                    <h5 class="modal-title" id="tbipdload" style="text-align: center; margin: 20px; display: none; color: #0f172a; font-weight: 600;">
                      <span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span> รอสักครู่กำลังโหลดข้อมูลลูกหนี้ IPD... <img src="./images/load.gif" alt="" style="width:24px;" />
                    </h5>

                    <div class="table-responsive">
                      <table class="table comp-report-table table-hover align-middle">
                        <thead>
                          <tr>
                            <td style="text-align: center; font-weight: 700;" colspan="9">รายงานสรุปลูกหนี้ ตามสิทธิการเงิน ผู้ป่วยใน ประจำเดือน</td>
                          </tr>
                          <tr>
                            <td style="text-align: center; font-weight: 600; width: 5%; vertical-align: middle;" rowspan="2">รหัส</td>
                            <td style="text-align: center; font-weight: 600; width: 32.2%; vertical-align: middle;" rowspan="2">สิทธิการเงิน</td>
                            <td style="text-align: center; font-weight: 600; width: 20%;" colspan="2">ลูกหนี้</td>
                            <td style="text-align: center; font-weight: 600;" colspan="2">รายการ HOSxP</td>
                            <td style="text-align: center; font-weight: 600; vertical-align: middle;" rowspan="2">ภาระหนี้</td>
                            <td style="text-align: center; font-weight: 600; vertical-align: middle;" rowspan="2">เงินชดเชย</td>
                            <td style="text-align: center; font-weight: 600; vertical-align: middle;" rowspan="2">ส่วนต่าง</td>
                          </tr>
                          <tr>
                            <td style="text-align: center; font-weight: 600; width: 10%;">ทั้งหมด</td>
                            <td style="text-align: center; font-weight: 600; width: 10%;">คงเหลือ</td>
                            <td style="text-align: center; font-weight: 600;">ค่าใช้จ่าย</td>
                            <td style="text-align: center; font-weight: 600;">ชำระแล้ว</td>
                          </tr>
                        </thead>
                        <tbody id="pdfviewi" style="font-size: 13.5px;">

                        </tbody>
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
        <!-- Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
  </div>
  <!-- / Layout wrapper -->

  <!-- Modal Loading Structure -->
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

  <!-- Modal Example Load -->
  <div class="modal fade" id="exampleModalload" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="margin-top: 18%;">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-body" style="text-align:center;">
          Load...
        </div>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       🏢 The Modal: ข้อมูลลูกหนี้รายตัว ตามสิทธิการเงิน (Enterprise Healthcare Edition)
       ========================================================================= -->
  <div class="modal fade" id="myModalgetlistview" tabindex="-1" aria-hidden="true">
    <form action="ลูกหนี้รายตัว2.php" method="post" target="_blank" id="myForm" style="display:contents;">
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">

          <!-- Modal Header (Luxury Dark Gradient) -->
          <div class="modal-header-luxury">
            <div class="d-flex align-items-center gap-3">
              <div class="brand-badge">
                <i class='bx bx-id-card'></i>
              </div>
              <div class="title-group">
                <h5 id="modal_dept_heading">รายละเอียดลูกหนี้รายตัว</h5>
                <div class="subtitle-pill" id="titlestmckd">
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

              <div class="kpi-card kpi-expense">
                <div class="kpi-icon-box">
                  <i class='bx bx-receipt'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ค่าใช้จ่ายรวม (Income)</div>
                  <div class="kpi-value" id="txtq1">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-paid">
                <div class="kpi-icon-box">
                  <i class='bx bx-check-circle'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ชำระแล้ว (Paid)</div>
                  <div class="kpi-value" id="txtq2">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-debit">
                <div class="kpi-icon-box">
                  <i class='bx bx-error-circle'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ภาระหนี้คงเหลือ (Debit)</div>
                  <div class="kpi-value" id="txtq3">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-comp">
                <div class="kpi-icon-box">
                  <i class='bx bx-shield-quarter'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">เงินชดเชย / STM รวม</div>
                  <div class="kpi-value" id="txtq4">0.00</div>
                </div>
              </div>
            </div>

            <!-- Search & Filter Toolbar -->
            <div class="modal-search-toolbar">
              <div class="search-input-group">
                <i class='bx bx-search search-icon'></i>
                <input id="myInputModal" type="text" placeholder="ค้นหาด้วย ชื่อ-สกุล, CID, HN, VN/AN, เลขที่ใบเสร็จ..." autocomplete="off">
                <button type="button" class="search-clear-btn" id="clearSearchModalBtn" onclick="$('#myInputModal').val('').trigger('keyup').focus();">
                  <i class='bx bx-x'></i>
                </button>
              </div>

              <div class="d-flex align-items-center gap-2">
                <div class="filter-badge-info">
                  <i class='bx bx-list-ul text-primary'></i>
                  <span id="filtered_row_count">กำลังแสดงทุกรายการ</span>
                </div>
                <button type="button" id="exportstmgetovlookup" class="footer-btn btn-export-excel" style="height: 38px; padding: 6px 16px; font-size: 13.5px;">
                  <i class='bx bxs-file-export'></i> ส่งออก Excel (.xlsx)
                </button>
                <button type="submit" name="submit" value="submit" onclick="return confirm('ยืนยันการทำรายการพิมพ์ลูกหนี้ชดเชย')" class="footer-btn btn-print-report" style="height: 38px; padding: 6px 16px; font-size: 13.5px;">
                  <i class='bx bx-printer'></i> พิมพ์ลูกหนี้ชดเชย
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
            <div class="table-scroll-modal">
              <table class="table table-bordered table-hover stmgetovlookup" >
                <thead>
                  <tr>
                    <th style="text-align: left; font-weight: bold; background-color: #13758b; color: #fff;" colspan="15">
                      <i class='bx bx-spreadsheet text-info'></i> รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน
                    </th>
                  </tr>
                  <tr style="font-weight: bold;">
                    <th class="sortable-header" data-column="1" style="text-align: center;">ที่</th>
                    <th class="sortable-header" data-column="2" style="text-align: center;">VN/AN</th>
                    <th class="sortable-header" data-column="3" style="text-align: center;">HN</th>
                    <th class="sortable-header" data-column="4" style="text-align: center;">CID</th>
                    <th class="sortable-header" data-column="5">ชื่อผู้ป่วย</th>
                    <th class="sortable-header" data-column="6">สิทธิการเงิน</th>
                    <th class="sortable-header" data-column="7">สิทธิ Hos</th>
                    <th class="sortable-header" data-column="8" style="text-align: center;">วันที่ตรวจ/วันที่ DC</th>
                    <th class="sortable-header" data-column="9" style="text-align: right;">ค่าใช้จ่าย</th>
                    <th class="sortable-header" data-column="10" style="text-align: right;">ชำระแล้ว</th>
                    <th class="sortable-header" data-column="11" style="text-align: right;">ภาระหนี้</th>
                    <th class="sortable-header" data-column="12" style="text-align: right;">ชดเชย</th>
                    <th class="sortable-header" data-column="13" style="text-align: center;">เลขที่ใบเสร็จ</th>
                    <th class="sortable-header" data-column="14" style="text-align: center;">วันที่ออกใบเสร็จ</th>
                    <th class="sortable-header" data-column="15" style="text-align: center;">Rep.</th>
                  </tr>
                </thead>
                <tbody id="data_tabel_listview" style="font-size: 13.5px;">
                  
                </tbody>
                <tfoot id="data_tabel_tfoot">
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Modal footer -->
          <div class="modal-footer-luxury">
            <div class="d-flex align-items-center gap-2 text-muted small">
              <span><i class='bx bx-info-circle text-primary me-1'></i> ข้อมูลลูกหนี้รายตัวทั้งหมดตามสิทธิการเงิน (คลิกหัวตารางเพื่อจัดเรียง)</span>
            </div>
          </div>

        </div>
      </div>
    </form>
  </div>

  <!-- Floating Logout Button -->
  <a href="logout.php" class="floating-logout-btn" style="margin-bottom: 12px;" title="ออกจากระบบ">
    <iconify-icon icon="solar:logout-2-bold" width="22" style="vertical-align: middle; margin-right: 8px;"></iconify-icon> 
  </a>

  <!-- Iconify CDN -->
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

  <!-- Core JS -->
  <script src="../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../assets/vendor/libs/popper/popper.js"></script>
  <script src="../assets/vendor/js/bootstrap.js"></script>
  <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="../assets/vendor/js/menu.js"></script>

  <!-- Vendors JS -->
  <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>

  <!-- Main JS -->
  <script src="../assets/js/main.js"></script>
  <script src="../assets/js/dashboards-analytics.js"></script>

  <script src="./chart/highcharts.js"></script>
  <script src="./chart/data.min.js"></script>
  <script src="./chart/exporting.min.js"></script>
  <script src="./chart/accessibility.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectStartMonth = document.getElementById('selectTypeOpts');
        const selectEndMonth = document.getElementById('selectTypeOpte');
        
        if (selectStartMonth && selectEndMonth) {
            selectStartMonth.addEventListener('change', validateMonths);
            selectEndMonth.addEventListener('change', validateMonths);

            function validateMonths() {
                const startMonth = selectStartMonth.value;
                const endMonth = selectEndMonth.value;
                
                if (startMonth === '0-0000' || endMonth === '0-0000') {
                    return;
                }

                const [startM, startY] = startMonth.split('-');
                const [endM, endY] = endMonth.split('-');

                const startDate = new Date(startY, startM - 1, 1);
                const endDate = new Date(endY, endM - 1, 1);

                if (endDate < startDate) {
                   Swal.fire({
                        icon: 'warning', 
                        title: 'เดือนไม่ถูกต้อง!',
                        text: 'เดือนสิ้นสุดต้องเป็นเดือนที่ใหม่กว่าหรือเท่ากับเดือนเริ่มต้น',
                        confirmButtonText: 'รับทราบ',
                        confirmButtonColor: '#b28bff' 
                    });                  
        
                    selectEndMonth.value = startMonth; 
                }
            }
        }
    });

    $(document).ready(function(){
      var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

      if (!isLoggedIn) {
          location.href = './login.php';
      } else {
        fetch_data_pdf();
        fetch_data_pdfi();
      }
    });

    $('#selectTypeOpts, #selectTypeOpte, #selectTypeOptpp').change(function() {
        fetch_data_pdf();
        fetch_data_pdfi();
    });

    function fetch_data_pdf() { 
        $('#progressBar').css('width', '0%').attr('aria-valuenow', 0);
        $('#tbopdload').show();

        var interval = setInterval(function() {
          var currentWidth = parseInt($('#progressBar').attr('aria-valuenow'));
          if (currentWidth < 90) {
            currentWidth += 10;
            $('#progressBar').css('width', currentWidth + '%').attr('aria-valuenow', currentWidth);
          } else {
            clearInterval(interval);
          }
        }, 500);

        var actionopdlists = $("#selectTypeOpts").val();  
        var actionopdlist = $("#selectTypeOpte").val();  
        var selectTypeOptpp = $("#selectTypeOptpp").val();  

        $.ajax({
          url:"datatimestamp-insert2.php",
          method:"POST",
          data:{actionopdlistpp:actionopdlist,selectTypeOptpp:selectTypeOptpp,actionopdlists:actionopdlists},
          success:function(data) {
            $('#progressBar').css('width', '100%').attr('aria-valuenow', 100);
            setTimeout(function() {
              $('#progressBar').css('width', '0%').attr('aria-valuenow', 0);
            }, 1000); 
            $('#tbopdload').hide();
            $('#pdfview').html(data);
          }
        });
    }

    function fetch_data_pdfi() { 
        $('#tbipdload').show();
        var actionipdlists = $("#selectTypeOpts").val();  
        var actionipdlist = $("#selectTypeOpte").val();  
        var selectTypeOptpp = $("#selectTypeOptpp").val();  

        $.ajax({
          url:"datatimestamp-insert2.php",
          method:"POST",
          data:{actionipdlistpp:actionipdlist,selectTypeOptpp:selectTypeOptpp,actionipdlists:actionipdlists},
          success:function(data) {
            $('#tbipdload').hide();
            $('#pdfviewi').html(data);
          }
        });
    }

    function viewopdlist(e) { 
       Swal.fire({
          title: 'กำลังดึงข้อมูลลูกหนี้ OPD...',
          html: 'รอสักครู่ ระบบกำลังประมวลผลข้อมูล',
          allowOutsideClick: false,
          scrollbarPadding: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });

      var actionopdlistonlys = $("#selectTypeOpts").val();  
      var actionopdlistonly = $("#selectTypeOpte").val();  
      var selectTypeOptpp = $("#selectTypeOptpp").val(); 
      var oid = e; 

      $.ajax({
        url:"datatimestamp-insert2.php",
        method:"POST",
        data:{actionopdlistonlypp:actionopdlistonly,oid:oid,selectTypeOptpp:selectTypeOptpp,actionopdlistonlys:actionopdlistonlys},
        success:function(data) {
          Swal.close();

          $('#modal_dept_heading').text('รายละเอียดลูกหนี้รายตัว ผู้ป่วยนอก (OPD)');

          $('#data_tabel_listview').html(data);
          var sumRow = $('#data_tabel_listview tr.summary-total-row');
          if (sumRow.length > 0) {
              $('#data_tabel_tfoot').html(sumRow);
          } else {
              $('#data_tabel_tfoot').empty();
          }

          var titleData = $('#modal_report_title_data').data('title');
          if (titleData) {
              $('#titlestmckd').html(titleData);
          } else {
              $('#titlestmckd').html('📑 สิทธิการเงิน: ' + oid);
          }

          var o0 = $('#o0').text();
          $('#txtq0').text((o0 ? o0 : '0') + ' คน'); 

          var o1 = $('#o1').text();
          $('#txtq1').text(o1 ? o1 : '0.00');

          var o2 = $('#o2').text();
          $('#txtq2').text(o2 ? o2 : '0.00');

          var o3 = $('#o3').text();
          $('#txtq3').text(o3 ? o3 : '0.00');

          var o4 = $('#o4').text();
          $('#txtq4').text(o4 ? o4 : '0.00');

          $('#myInputModal').val('');
          $('#clearSearchModalBtn').hide();
          
          var totalRows = $('#data_tabel_listview tr').not('.spacer-row').length;
          $('#filtered_row_count').text('กำลังแสดงทุกรายการ (' + totalRows + ' รายการ)');

          $('#myModalgetlistview').modal('show');

          // 🌟 สร้างแท็บจำแนกกลุ่มย่อย CR แบบ Dynamic
          buildCrSubgroupTabs();
        }
      });
    }

    function viewipdlist(e) { 
      Swal.fire({
          title: 'กำลังดึงข้อมูลลูกหนี้ IPD...',
          html: 'รอสักครู่ ระบบกำลังประมวลผลข้อมูล',
          allowOutsideClick: false,
          scrollbarPadding: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });

      var actionipdlistonlys = $("#selectTypeOpts").val();  
      var actionipdlistonly = $("#selectTypeOpte").val(); 
      var selectTypeOptpp = $("#selectTypeOptpp").val(); 
      var iid = e; 

      $.ajax({
        url:"datatimestamp-insert2.php",
        method:"POST",
        data:{actionipdlistonlypp:actionipdlistonly,iid:iid,selectTypeOptpp:selectTypeOptpp,actionipdlistonlys:actionipdlistonlys},
        success:function(data) {
          Swal.close();

          $('#modal_dept_heading').text('รายละเอียดลูกหนี้รายตัว ผู้ป่วยใน (IPD)');

          $('#data_tabel_listview').html(data);
          var sumRow = $('#data_tabel_listview tr.summary-total-row');
          if (sumRow.length > 0) {
              $('#data_tabel_tfoot').html(sumRow);
          } else {
              $('#data_tabel_tfoot').empty();
          }

          var titleData = $('#modal_report_title_data').data('title');
          if (titleData) {
              $('#titlestmckd').html(titleData);
          } else {
              $('#titlestmckd').html('📑 สิทธิการเงิน: ' + iid);
          }
          
          var i0 = $('#i0').text();
          $('#txtq0').text((i0 ? i0 : '0') + ' คน');  
          var i1 = $('#i1').text();
          $('#txtq1').text(i1 ? i1 : '0.00');
          var i2 = $('#i2').text();
          $('#txtq2').text(i2 ? i2 : '0.00');
          var i3 = $('#i3').text();
          $('#txtq3').text(i3 ? i3 : '0.00');
          var i4 = $('#i4').text();
          $('#txtq4').text(i4 ? i4 : '0.00');

          $('#myInputModal').val('');
          $('#clearSearchModalBtn').hide();

          var totalRows = $('#data_tabel_listview tr').not('.spacer-row, .summary-total-row').length;
          $('#filtered_row_count').text('กำลังแสดงทุกรายการ (' + totalRows + ' รายการ)');

          $('#myModalgetlistview').modal('show');

          // 🌟 สร้างแท็บจำแนกกลุ่มย่อย CR แบบ Dynamic (IPD)
          buildCrSubgroupTabs();
        }
      });
    }

    // Cleanup on modal close
    $('#myModalgetlistview').on('hidden.bs.modal', function () {
        $('body').css('padding-right', '');
        $('body').removeClass('modal-open');
        $('#myInputModal').val('').trigger('keyup');
    });

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
        var rows = $("#data_tabel_listview tr").not('.spacer-row, .summary-total-row');
        var totalMainRows = rows.length;

        rows.each(function () {
            var tr = $(this);
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
        });

        if (searchVal === '' && filter === 'ALL') {
            $('#clearSearchModalBtn').hide();
            $('#filtered_row_count').text('กำลังแสดงทุกรายการ (' + totalMainRows + ' รายการ)');
        } else {
            if (searchVal !== '') $('#clearSearchModalBtn').css('display', 'inline-flex');
            $('#filtered_row_count').html('พบ <b>' + visibleCount + '</b> จาก ' + totalMainRows + ' รายการ');
        }

        recalculateModalSummary();
    }

    function formatMoneyJS(num) {
        return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalculateModalSummary() {
        var dataRows = $("#data_tabel_listview tr").not('.spacer-row, .summary-total-row');
        var visibleRows = dataRows.filter(':visible');
        var visibleCount = visibleRows.length;

        var sum_income = 0, sum_paid = 0, sum_debit = 0, sum_comp = 0;

        visibleRows.each(function () {
            var tr = $(this);
            sum_income += parseFloat(tr.find('td').eq(8).text().replace(/,/g, '')) || 0;
            sum_paid   += parseFloat(tr.find('td').eq(9).text().replace(/,/g, '')) || 0;
            sum_debit  += parseFloat(tr.find('td').eq(10).text().replace(/,/g, '')) || 0;
            sum_comp   += parseFloat(tr.find('td').eq(11).text().replace(/,/g, '')) || 0;
        });

        var percent = (sum_debit > 0) ? (sum_comp / sum_debit) * 100 : 0;
        var compText = formatMoneyJS(sum_comp) + ' (' + formatMoneyJS(percent) + '%)';

        var tfootRow = $("#data_tabel_tfoot tr.summary-total-row, #data_tabel_tfoot tr");
        if (tfootRow.find('#o1, #i1').length > 0) {
            tfootRow.find('#o1, #i1').html('&nbsp;' + formatMoneyJS(sum_income));
            tfootRow.find('#o2, #i2').html('&nbsp;' + formatMoneyJS(sum_paid));
            tfootRow.find('#o3, #i3').html('&nbsp;' + formatMoneyJS(sum_debit));
            tfootRow.find('#o4, #i4').html('&nbsp;' + compText);
        }

        // อัปเดตการ์ด KPI ด้านบน
        $('#txtq0').text(visibleCount + ' คน');
        $('#txtq1').text(formatMoneyJS(sum_income));
        $('#txtq2').text(formatMoneyJS(sum_paid));
        $('#txtq3').text(formatMoneyJS(sum_debit));
        $('#txtq4').text(compText);
    }

    // Realtime Search inside modal
    $(document).on("keyup input", "#myInputModal", function () {
      filterTableByCrSubgroup();
    });

    // Sorting handler
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
            if (tr.hasClass('spacer-row') || tr.hasClass('summary-total-row')) {
                return;
            }
            if (tr.hasClass('main-patient-row') || (!tr.hasClass('main-patient-row') && !tr.hasClass('sub-payment-row'))) {
                if (currentGroup) {
                    rowsGroup.push(currentGroup);
                }
                currentGroup = {
                    mainRow: tr,
                    subRows: [],
                    sortValue: tr.find('td').eq(columnIndex - 1).text().trim() || tr.find('td').eq(columnIndex).text().trim()
                };
            } else if (tr.hasClass('sub-payment-row') && currentGroup) {
                currentGroup.subRows.push(tr);
            } else {
                rowsGroup.push({
                    mainRow: tr,
                    subRows: [],
                    sortValue: tr.find('td').eq(columnIndex - 1).text().trim() || tr.find('td').eq(columnIndex).text().trim()
                });
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

    // Export SheetJS Excel
    document.getElementById('exportstmgetovlookup').addEventListener('click', function (e) {
        e.preventDefault();
        let table = document.querySelector('.stmgetovlookup');
        let workbook = XLSX.utils.book_new();
        let worksheet = XLSX.utils.table_to_sheet(table, { raw: true });

        XLSX.utils.book_append_sheet(workbook, worksheet, "DebtorDetails");
        XLSX.writeFile(workbook, 'Debtor_Compensation_Report_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    });
  </script>

  <?php if (!empty($_SESSION['role'])): ?>
    <?php include './includes/quick_search_ui.html'; ?>
  <?php endif; ?>

  <!-- 🌟 Modal แสดงรายละเอียดรายการ CR ของคนไข้รายตัว -->
  <?php include_once './includes/modal_cr_patient_breakdown.php'; ?>
  <!-- 🌟 Modal แสดงรายละเอียดรายการ SSS ของคนไข้รายตัว -->
  <?php include_once './includes/modal_sss_patient_breakdown.php'; ?>

  </body>
</html>
