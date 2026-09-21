<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
require_once './database_config/db_helper.php';
$decrypted_q = '';
if (!empty($_GET['q'])) {
    $decrypted_q = decrypt_data(trim($_GET['q']));
}
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

  /* 🌟 สไตล์ช่องสีเหลืองเมื่อถูกล็อกในมุมมองกลุ่มย่อย */
  .subgroup-locked-cell {
    cursor: not-allowed !important;
    opacity: 0.82 !important;
    outline: none !important;
    user-select: none !important;
    position: relative;
    transition: all 0.2s ease;
  }
  .subgroup-locked-cell:hover {
    filter: brightness(0.96);
  }

* {
  box-sizing: border-box;
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
#myModalgetlistview.modal {
  padding: 0 !important;
  overflow: hidden !important;
  height: 100vh !important;
  width: 100vw !important;
}

#myModalgetlistview form#myForm {
  display: flex !important;
  flex-direction: column !important;
  height: 100vh !important;
  width: 100vw !important;
  margin: 0 !important;
  padding: 0 !important;
  overflow: hidden !important;
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
}

#myModalgetlistview .modal-body {
  flex: 1 1 0 !important;
  display: flex !important;
  flex-direction: column !important;
  overflow: hidden !important;
  min-height: 0 !important;
  height: 100% !important;
  padding: 12px 18px 10px 18px !important;
}

#myModalgetlistview .modal-kpi-bar {
  flex-shrink: 0 !important;
}

#myModalgetlistview .modal-search-toolbar {
  flex-shrink: 0 !important;
}

#myModalgetlistview .modal-footer-luxury {
  flex-shrink: 0 !important;
}

/* Top Header Bar */
.modal-header-luxury {
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

.header-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  font-size: 13.5px;
  font-weight: 600;
  border-radius: 9px;
  border: none;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.header-action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
  color: #fff;
}

.header-action-btn:active {
  transform: translateY(0);
}

.btn-partial-gradient {
  background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
  border: 1px solid rgba(255, 255, 255, 0.15);
}
.btn-partial-gradient:hover {
  background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
}

.btn-receipt-gradient {
  background: linear-gradient(135deg, #0d9488 0%, #115e59 100%);
  border: 1px solid rgba(255, 255, 255, 0.15);
}
.btn-receipt-gradient:hover {
  background: linear-gradient(135deg, #14b8a6 0%, #0f766e 100%);
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

/* Executive KPI Cards Bar */
.modal-kpi-bar {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
  margin-bottom: 14px;
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

.kpi-card.kpi-total::before { background: #3b82f6; }
.kpi-card.kpi-expense::before { background: #0284c7; }
.kpi-card.kpi-paid::before { background: #10b981; }
.kpi-card.kpi-debt::before { background: #f59e0b; }
.kpi-card.kpi-comp::before { background: #8b5cf6; }

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
.kpi-debt .kpi-icon-box { background: #fffbeb; color: #d97706; }
.kpi-comp .kpi-icon-box { background: #f5f3ff; color: #7c3aed; }

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
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}

.kpi-paid .kpi-value { color: #059669; }
.kpi-debt .kpi-value { color: #d97706; }
.kpi-comp .kpi-value { color: #7c3aed; }
.kpi-expense .kpi-value { color: #0284c7; }

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

.search-input-group input#myInput {
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

.search-input-group input#myInput:focus {
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

/* Modern Data Table Container */
.table-scroll {
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

.table-scroll::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
.table-scroll::-webkit-scrollbar-track {
  background: #f1f5f9;
}
.table-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.table-scroll::-webkit-scrollbar-thumb:hover {
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

/* Sub-Payment (Partial Payment) Rows */
.stmgetovlookup tbody tr.sub-payment-row {
  background-color: #fafcfe !important;
}
.stmgetovlookup tbody tr.sub-payment-row:hover {
  background-color: #f1f8ff !important;
}
.stmgetovlookup tbody tr.sub-payment-row td {
  border-bottom: 1px dashed #e2e8f0 !important;
  padding: 7px 14px !important;
  white-space: nowrap !important;
}

/* Editable cells */
.stmgetovlookup td[contenteditable="true"] {
  outline: none;
  cursor: text;
  transition: all 0.2s ease;
  position: relative;
}
.stmgetovlookup td[contenteditable="true"]:hover {
  box-shadow: inset 0 0 0 1.5px #93c5fd;
  background-color: #eff6ff !important;
}
.stmgetovlookup td[contenteditable="true"]:focus {
  box-shadow: inset 0 0 0 2px #2563eb;
  background-color: #ffffff !important;
}

/* Summary Footer Row (รวมทั้งสิ้น) */
.stmgetovlookup tfoot,
.stmgetovlookup tfoot tr,
.stmgetovlookup tfoot tr.summary-total-row,
.stmgetovlookup tr.summary-total-row,
.stmgetovlookup tbody tr.summary-total-row,
.stmgetovlookup tbody tr:last-child.summary-total-row,
.stmgetovlookup tbody tr:last-child {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 25 !important;
  background: linear-gradient(268deg, #2a3d5d 0%, #1e2b4b 100%) !important;
  box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.25) !important;
  height: 45px !important;
}

.stmgetovlookup tfoot td,
.stmgetovlookup tfoot tr td,
.stmgetovlookup tr.summary-total-row td,
.stmgetovlookup tbody tr.summary-total-row td,
.stmgetovlookup tbody tr:last-child td {
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
.stmgetovlookup tbody tr.summary-total-row td:first-child,
.stmgetovlookup tbody tr:last-child td:first-child {
  color: #ffffff !important;
  font-size: 15px !important;
  letter-spacing: 0.5px;
}

.stmgetovlookup tfoot td *,
.stmgetovlookup tr.summary-total-row td *,
.stmgetovlookup tbody tr:last-child td * {
  color: #ffffff !important;
}

/* Modal Bottom Footer */
.modal-footer-luxury {
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.03);
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

.selected-count-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 20px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  font-size: 13px;
  font-weight: 600;
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

/* Custom Checkbox */
input[type="checkbox"] {
  accent-color: #2563eb;
  cursor: pointer;
}

/* Enterprise Sub-Modals (#addAllbill & #modalPartialPayment) */
.modal-luxury-dialog .modal-content {
  border: none;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  background: #ffffff;
}

.modal-luxury-dialog .modal-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
  color: #ffffff;
  padding: 16px 20px;
  border-bottom: none;
}
.modal-luxury-dialog .modal-header .modal-title {
  color: #ffffff;
  font-weight: 700;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}
.modal-luxury-dialog .modal-body {
  padding: 24px;
  background: #f8fafc;
}
.modal-luxury-dialog .modal-footer {
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
  padding: 14px 20px;
}

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
   🌟 Modern Debtor Page UI (ลูกหนี้ประจำเดือน OPD & IPD)
   ========================================================================= */
.debtor-filter-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #2563eb !important;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px 28px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}
.debtor-filter-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.debtor-table-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
  padding: 24px 28px;
  margin-bottom: 24px;
  transition: box-shadow 0.25s ease;
}
.debtor-table-card.opd-card {
  border-left: 5px solid #10b981 !important;
}
.debtor-table-card.ipd-card {
  border-left: 5px solid #2563eb !important;
}
.debtor-table-card:hover {
  box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
}

.debtor-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 16px;
  margin-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
}

.debtor-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.debtor-header-icon-filter {
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

.debtor-header-icon-opd {
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

.debtor-header-icon-ipd {
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

.debtor-header-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.debtor-header-subtitle {
  font-size: 0.82rem;
  color: #64748b;
  margin: 2px 0 0 0;
}

.debtor-pill-badge {
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

.debtor-field-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.debtor-select {
  border-radius: 10px !important;
  border: 1px solid #cbd5e1 !important;
  padding: 10px 14px !important;
  font-size: 13.5px !important;
  color: #1e293b !important;
  background-color: #ffffff !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.2s ease !important;
}

.debtor-select:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16) !important;
  background-color: #ffffff !important;
}

.debtor-report-table {
  border-collapse: separate !important;
  border-spacing: 0 !important;
  border-radius: 10px !important;
  overflow: hidden !important;
  border: 1px solid #e2e8f0 !important;
}
.debtor-report-table thead tr:first-child td {
  background: #f8fafc !important;
  color: #0f172a !important;
  font-size: 14.5px !important;
  padding: 12px !important;
  border-bottom: 2px solid #cbd5e1 !important;
}
.debtor-report-table thead tr:nth-child(2) td, .debtor-report-table thead tr:nth-child(3) td {
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

          <!-- Navbar -->
            <div class="container-fluid">
              <div class="row">

                <div class="col-12">

                  <!-- Card ตัวกรองลูกหนี้ประจำเดือน -->
                  <div class="debtor-filter-card">
                    <div class="debtor-card-header">
                      <div class="debtor-header-left">
                        <div class="debtor-header-icon-filter">
                          <i class='bx bx-calendar-check'></i>
                        </div>
                        <div>
                          <h5 class="debtor-header-title">ลูกหนี้ประจำเดือน</h5>
                          <p class="debtor-header-subtitle">เลือกข้อมูลรอบเดือนเพื่อตรวจสอบรายละเอียดและกระทบยอดลูกหนี้รายตัว</p>
                        </div>
                      </div>
                      <div>
                        <span class="debtor-pill-badge">
                          <i class='bx bx-time-five text-primary'></i> เลือกรอบเดือน
                        </span>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <label class="debtor-field-label"><i class='bx bx-calendar text-primary'></i> ข้อมูลประจำเดือน</label>
                        <select id="selectTypeOpt" name="selectTypeOpt" class="form-select debtor-select">
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

                  <!-- Card ลูกหนี้รายตัว OPD -->
                  <div class="debtor-table-card opd-card">
                    <div class="debtor-card-header">
                      <div class="debtor-header-left">
                        <div class="debtor-header-icon-opd">
                          <i class='bx bx-plus-medical'></i>
                        </div>
                        <div>
                          <h5 class="debtor-header-title">ลูกหนี้รายตัว OPD</h5>
                          <p class="debtor-header-subtitle">รายงานสรุปลูกหนี้ ตามสิทธิการเงิน ผู้ป่วยนอก ประจำเดือน</p>
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
                        <table class="table debtor-report-table table-hover align-middle">
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

                  <!-- Card ลูกหนี้รายตัว IPD -->
                  <div class="debtor-table-card ipd-card">
                    <div class="debtor-card-header">
                      <div class="debtor-header-left">
                        <div class="debtor-header-icon-ipd">
                          <i class='bx bx-hotel'></i>
                        </div>
                        <div>
                          <h5 class="debtor-header-title">ลูกหนี้รายตัว IPD</h5>
                          <p class="debtor-header-subtitle">รายงานสรุปลูกหนี้ ตามสิทธิการเงิน ผู้ป่วยใน ประจำเดือน</p>
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
                        <table class="table debtor-report-table table-hover align-middle">
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


          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->



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

    <!-- =========================================================================
         🏢 The Modal: ข้อมูลลูกหนี้รายตัว ตามสิทธิการเงิน (Enterprise Hospital Edition)
         ========================================================================= -->
    <div class="modal fade" id="myModalgetlistview" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen">
        <form action="ลูกหนี้รายตัว2.php" method="post" target="_blank" id="myForm" class="modal-content">
          <!-- Modal Header -->
          <div class="modal-header-luxury">
            <div class="d-flex align-items-center gap-3">
              <div class="brand-badge">
                <i class='bx bx-id-card'></i>
              </div>
              <div class="title-group">
                <h5 id="modal_dept_heading">รายละเอียดข้อมูลลูกหนี้รายตัว</h5>
                <div class="subtitle-pill" id="modal_report_title_text">
                  📑 รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button type="button" class="header-action-btn btn-partial-gradient" onclick="charge_off()">
                <i class='bx bx-coin-stack'></i> การชำระหนี้โดยแบ่งจ่าย
              </button>
              <button type="button" class="header-action-btn btn-receipt-gradient" onclick="checkAllbill(true)">
                <i class='bx bx-receipt'></i> แนบใบเสร็จทั้งหมด
              </button>
              <button type="button" class="btn-close-luxury ms-2" data-bs-dismiss="modal" onclick="$('#myInput').val('').trigger('keyup');" title="ปิดหน้าต่าง">
                <i class='bx bx-x'></i>
              </button>
            </div>
          </div>

          <!-- Modal body -->
          <div class="modal-body p-3">
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
                  <i class='bx bx-file-blank'></i>
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

              <div class="kpi-card kpi-debt">
                <div class="kpi-icon-box">
                  <i class='bx bx-error-alt'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ภาระหนี้คงเหลือ (Debit)</div>
                  <div class="kpi-value" id="txtq3">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-comp">
                <div class="kpi-icon-box">
                  <i class='bx bx-wallet-alt'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ชดเชย / STM รวม</div>
                  <div class="kpi-value" id="txtq4">0.00</div>
                </div>
              </div>
            </div>

            <!-- Search & Filter Toolbar -->
            <div class="modal-search-toolbar">
              <div class="search-input-group">
                <i class='bx bx-search search-icon'></i>
                <input id="myInput" type="text" placeholder="ค้นหาด้วย ชื่อ-สกุล, CID, HN, VN/AN, เลขที่ใบเสร็จ..." autocomplete="off">
                <button type="button" class="search-clear-btn" id="clearSearchBtn" onclick="$('#myInput').val('').trigger('keyup').focus();">
                  <i class='bx bx-x'></i>
                </button>
              </div>

              <div class="d-flex align-items-center gap-2">
                <div class="filter-badge-info">
                  <i class='bx bx-list-ul text-primary'></i>
                  <span id="filtered_row_count">กำลังแสดงทุกรายการ</span>
                </div>
                <button type="button" id="btnCheckAllTop" class="btn btn-sm btn-outline-primary px-2.5 py-1.5" onclick="$('#checkAll').click();" style="border-radius: 8px; height: 38px; display: inline-flex; align-items: center; gap: 4px;">
                  <i class='bx bx-check-double'></i> ติ๊กเลือกทั้งหมด
                </button>
                <a href="#" id="exportstmgetovlookup" class="footer-btn btn-export-excel" style="height: 38px; padding: 6px 16px; font-size: 13.5px;">
                  <i class='bx bxs-file-export'></i> ส่งออก Excel (.xlsx)
                </a>
                <button type="submit" name="submit" value="submit" onclick="return confirm('ยืนยันการทำรายการพิมพ์ลูกหนี้ชดเชย')" class="footer-btn btn-print-report" style="height: 38px; padding: 6px 16px; font-size: 13.5px;">
                  <i class='bx bx-printer'></i> พิมพ์ลูกหนี้ชดเชย
                </button>
              </div>
            </div>

            <!-- 🌟 CR Subgroup Dynamic Navigation Tabs Container (Placed below Search Toolbar) -->
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

            <!-- Data Table Grid -->
            <div class="table-scroll">
              <table class="table table-bordered table-hover stmgetovlookup">
                <thead>
                  <tr>
                    <th style="text-align: left; vertical-align: middle;" colspan="16">
                        <div class="d-flex align-items-center justify-content-between">
                          <span class="d-flex align-items-center gap-2">
                            <i class='bx bx-spreadsheet text-info'></i> 
                            <span id="table_inner_title">ตารางแจกแจงรายการลูกหนี้รายคน</span>
                          </span>
                          <span class="text-white-50 small fw-normal">
                            <i class='bx bx-info-circle'></i> คลิกช่องสีส้มเพื่อแก้ไขเลขที่บิล/วันที่/ยอดชดเชย
                          </span>
                        </div>
                    </th>
                  </tr>
                  
                  <tr>
                    <th style="text-align: center; width: 45px;"><input type="checkbox" id="checkAll" style="width: 18px; height: 18px;"></th>
                    <th class="sortable-header" data-column="1" style="text-align: center;">ลำดับ</th>
                    <th class="sortable-header" data-column="2" style="text-align: center;">VN / AN</th>
                    <th class="sortable-header" data-column="3" style="text-align: center;">เลขบัตรประชาชน (CID)</th>
                    <th class="sortable-header" data-column="4">ชื่อ - สกุลผู้ป่วย</th>
                    <th class="sortable-header" data-column="5">สิทธิการรักษา (HOSxP)</th>
                    <th class="sortable-header" data-column="6" style="text-align: center;">วันที่รับบริการ / วันที่ DC</th>  
                    <th class="sortable-header" data-column="7" style="text-align: center;">ระยะเวลาหนี้</th>
                    <th class="sortable-header" data-column="8" style="text-align: right;">ค่าใช้จ่าย</th>
                    <th class="sortable-header" data-column="9" style="text-align: right;">ชำระแล้ว</th>
                    <th class="sortable-header" data-column="10" style="text-align: right;">ภาระหนี้</th>
                    <th class="sortable-header" data-column="11" style="text-align: right;">ชดเชย</th>
                    <th class="sortable-header" data-column="12" style="text-align: center;">เลขที่ใบเสร็จ</th>
                    <th class="sortable-header" data-column="13" style="text-align: center;">วันที่ออกใบเสร็จ</th>
                    <th class="sortable-header" data-column="14">หมายเหตุ</th>
                    <th class="sortable-header" data-column="15" style="text-align: center;">HN</th>
                  </tr>
                </thead>
                <tbody id="data_tabel_listview">
                  
                </tbody>
              </table>
            </div>
          </div>

          <!-- Modal footer -->
          <div class="modal-footer-luxury">
            <div class="d-flex align-items-center gap-2 text-muted small">
              <span><i class='bx bx-info-circle text-primary me-1'></i> ข้อมูลลูกหนี้รายตัวทั้งหมด</span>
            </div>

            <div class="d-flex align-items-center gap-3">
              <div class="selected-count-badge" id="selectedCountBadge">
                <i class='bx bx-check-square'></i> ติ๊กเลือกแล้ว <span id="selected_count_num">0</span> รายการ
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>



    <!-- =========================================================================
         🏢 Modal: แนบเลขที่ใบเสร็จแบบ Bulk (Enterprise Edition)
         ========================================================================= -->
    <div class="modal fade modal-luxury-dialog" id="addAllbill" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          
          <div class="modal-header">
            <h5 class="modal-title" id="partialPayLabel">
                <i class='bx bx-receipt'></i> บันทึกแนบเลขที่ใบเสร็จ
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" tabindex="0"></button>
          </div>

          <div class="modal-body">
              <form action="ลูกหนี้รายตัว3.php" method="post" target="_blank" id="myFormBulkBill">
                <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">ประเภทแผนก</label>
                  <input type="text" class="form-control bg-light" id="typeccbill" name="typeccbill" readonly style="font-weight: 600;">
                </div>

                <div class="mb-3">
                  <label class="form-label fw-bold text-dark">เลขที่ใบเสร็จ</label>
                  <div class="d-flex gap-2 align-items-center">
                    <input type="text"
                           class="form-control text-center fw-bold"
                           name="payment_front"
                           placeholder="เล่มที่"
                           maxlength="4"
                           style="width: 120px; font-size: 15px;"
                           required tabindex="1">
                    <span class="fw-bold text-muted" style="font-size: 18px;">/</span>
                    <input type="text"
                           class="form-control text-center fw-bold"
                           name="payment_back"
                           placeholder="เลขที่"
                           maxlength="4"
                           style="width: 120px; font-size: 15px;"
                           required tabindex="2"> 
                  </div>
                  <!-- ค่าจริงที่ส่ง -->
                  <input type="hidden" name="payment">
                </div>

                <div class="mb-2">
                  <label class="form-label fw-bold text-dark">วันที่ออกใบเสร็จ</label>
                  <input type="text" 
                         class="form-control" 
                         name="paymentdate" 
                         placeholder="วว/ดด/ปปปป (เช่น <?php echo date('d/m/').(date('Y')+543); ?>)"
                         pattern="\d{2}/\d{2}/\d{4}"
                         maxlength="10"
                         title="กรุณาระบุในรูปแบบ วว/ดด/ปปปป เช่น 01/01/2569"
                         required tabindex="3">
                </div>
              </form>
          </div>

          <div class="modal-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" onclick="checkAllbilladd(false)">
              <i class='bx bx-x'></i> ยกเลิก
            </button>
            <button type="button" class="btn btn-primary px-4" onclick="checkAllbilladd(true)" tabindex="4" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
              <i class='bx bx-save'></i> บันทึกเลขที่ใบเสร็จ
            </button>
          </div>

        </div>
      </div>
    </div>



    <!-- =========================================================================
         🏢 Modal: บันทึกการชำระหนี้แบบแบ่งจ่าย (Enterprise Edition)
         ========================================================================= -->
    <div class="modal fade modal-luxury-dialog" id="modalPartialPayment" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          
          <div class="modal-header">
            <h5 class="modal-title">
                <i class='bx bx-coin-stack'></i> บันทึกการชำระหนี้แบบแบ่งจ่าย
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" tabindex="0"></button>
          </div>

          <div class="modal-body">
            <form id="formPartialPay">
                <input type="hidden" id="pp_ref_id" name="ref_id"> 
                <input type="hidden" id="pp_patient_type" name="patient_type">

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold text-muted small">ชื่อผู้ป่วย</label>
                        <input type="text" id="pp_ptname" class="form-control bg-light fw-bold text-dark" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold text-muted small">ยอดภาระหนี้รวม (Debit)</label>
                        <input type="text" id="pp_total_debit" class="form-control bg-light text-end fw-bold text-primary" readonly>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold text-muted small">ชำระไปแล้ว (เดิม)</label>
                        <input type="text" id="pp_paid_old" class="form-control bg-light text-end fw-bold text-success" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold text-muted small">คงเหลือที่ต้องจ่าย</label>
                        <input type="text" id="pp_balance" class="form-control text-end fw-bold" readonly style="background-color: #fef2f2; color: #dc2626; border-color: #fecaca;">
                    </div>
                </div>

                <div class="card p-3 mb-3" style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 10px;">
                    <label class="form-label fw-bold text-primary mb-1" style="font-size: 14px;">
                      💰 ยอดที่ชำระครั้งนี้ (บาท)
                    </label>
                    <input type="number" step="0.01" class="form-control text-end fw-bold text-primary" id="pp_amount" name="amount" placeholder="0.00" style="font-size: 1.3rem; border-color: #93c5fd;" tabindex="1">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                      <label class="form-label fw-bold text-dark small">เลขที่ใบเสร็จ</label>
                      <div class="d-flex gap-1 align-items-center">
                        <input type="text"
                               class="form-control text-center fw-bold"
                               name="bill_no_front"
                               placeholder="เล่มที่"
                               maxlength="4"
                               style="width: 75px;"
                               required tabindex="2">
                        <span class="fw-bold text-muted">/</span>
                        <input type="text"
                               class="form-control text-center fw-bold"
                               name="bill_no_back"
                               placeholder="เลขที่"
                               maxlength="4"
                               style="width: 75px;"
                               required tabindex="3"> 
                      </div>
                      <!-- ค่าจริงที่ส่ง -->
                      <input type="hidden" name="bill_no" id="pp_bill_no">
                    </div>

                    <div class="col-6">
                        <label class="form-label fw-bold text-dark small">วันที่ชำระ</label>
                        <input type="text" 
                               class="form-control" 
                               id="pp_bill_date" 
                               name="bill_date" 
                               value="<?php echo date('d/m/').(date('Y')+543); ?>"
                               placeholder="วว/ดด/ปปปป"
                               pattern="\d{2}/\d{2}/\d{4}"
                               maxlength="10"
                               title="กรุณาระบุในรูปแบบ 01/01/2569"
                               required tabindex="4">
                    </div>
                </div>
                
                <div class="mb-2">
                     <label class="form-label fw-bold text-muted small">หมายเหตุ (ถ้ามี)</label>
                     <textarea class="form-control" id="pp_note" rows="2" placeholder="ระบุเหตุผลหรือรายละเอียดการแบ่งจ่าย..." tabindex="5"></textarea>
                </div>

                <input type="hidden" id="pp_history_id">

            </form>
          </div>

          <div class="modal-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
              <i class='bx bx-x'></i> ยกเลิก
            </button>
            <button type="button" class="btn btn-primary px-4" onclick="savePartialPayment()" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
              <i class='bx bx-save'></i> บันทึกการแบ่งจ่าย
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

      var current_oid = ''; // ตัวแปรจำค่า OID ล่าสุด
      var current_iid = ''; // ตัวแปรจำค่า IID ล่าสุด

      $(document).ready(function(){
          //$('#exampleModalload').modal('show');

        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        if (!isLoggedIn) {
            location.href = './login.php';
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            const accountcodeParam = urlParams.get('accountcode');
            const monthParam = urlParams.get('month');
            const typeParam = urlParams.get('type');

            if (monthParam) {
                $('#selectTypeOpt').val(monthParam);
            }

            fetch_data_pdf();
            fetch_data_pdfi();

            if (accountcodeParam) {
                setTimeout(function() {
                    if (typeParam === 'IPD') {
                        if(typeof viewipdlist === 'function') viewipdlist(accountcodeParam);
                    } else {
                        if(typeof viewopdlist === 'function') viewopdlist(accountcodeParam);
                    }
                }, 800); // ดีเลย์นิดหน่อยรอให้ตารางหลักโหลดเสร็จก่อน
            }
        }

          

      });

        $('#selectTypeOpt').change(function() {

            fetch_data_pdf();
            fetch_data_pdfi();

        });


        function fetch_data_pdf()
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

          var actionopdlist = $("#selectTypeOpt").val();  

          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionopdlist:actionopdlist},
            success:function(data)
            {


              $('#progressBar').css('width', '100%').attr('aria-valuenow', 100);
              $('#data_tabel_listview').html(data);
              setTimeout(function() {
                $('#progressBar').css('width', '0%').attr('aria-valuenow', 0);
              }, 1000); 
              $('#tbopdload').hide();

              $('#pdfview').html(data);
            }
          })
        }

        function fetch_data_pdfi()
        { 

/*          $(".loading")
            .removeClass("d-none")
            .addClass("d-block");*/

          $('#tbipdload').show();
          var actionipdlist = $("#selectTypeOpt").val();  
         // var data = '2-2024' ;
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionipdlist:actionipdlist},
            success:function(data)
            {

/*              $(".loading")
                .removeClass("d-block")
                .addClass("d-none");*/
              $('#tbipdload').hide();
              $('#pdfviewi').html(data);
            }
          })
        }



      function viewopdlist(e)
        { 
          current_oid = e;

          // 1. เรียกใช้ SweetAlert โชว์สถานะกำลังโหลด
          Swal.fire({
              title: 'กำลังดึงข้อมูล...',
              html: 'รอสักครู่...',
              allowOutsideClick: false,
              scrollbarPadding: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          var actionopdlistonly = $("#selectTypeOpt").val();  
          var oid = e; 
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionopdlistonly:actionopdlistonly,oid:oid},
            success:function(data)
            {
              // 2. ปิด SweetAlert เมื่อข้อมูลโหลดเสร็จ
              Swal.close();

              $('#data_tabel_listview').html(data);

              // 🌟 สร้างปุ่มแท็บกลุ่มย่อย CR แบบ Dynamic
              buildCrSubgroupTabs();

              $('#modal_dept_heading').text('รายละเอียดข้อมูลลูกหนี้รายตัว (ผู้ป่วยนอก)');
              var titleData = $('#modal_report_title_data').data('title');
              if (titleData) {
                  $('#modal_report_title_text').html(titleData);
              } else {
                  $('#modal_report_title_text').html('📑 รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน');
              }

              var o0 = $('#o0').text().trim();
              $('#txtq0').text((o0 ? o0 : '0') + ' คน'); 

              var o1 = $('#o1').text().trim();
              $('#txtq1').text(o1 ? o1 : '0.00');

              var o2 = $('#o2').text().trim();
              $('#txtq2').text(o2 ? o2 : '0.00');

              var o3 = $('#o3').text().trim();
              $('#txtq3').text(o3 ? o3 : '0.00');

              var o4 = $('#o4').text().trim();
              $('#txtq4').text(o4 ? o4 : '0.00');

              $('#selected_count_num').text('0');
              $('#checkAll').prop('checked', false);

              $('#myModalgetlistview').modal('show');

              const urlParams = new URLSearchParams(window.location.search);
              const initialDecryptedQ = <?php echo json_encode($decrypted_q); ?>;
              const q = initialDecryptedQ || urlParams.get('q');
              if (q) {
                  $('#myInput').val(q).trigger('keyup');
                  const newUrl = new URL(window.location);
                  newUrl.searchParams.delete('q');
                  window.history.replaceState({}, '', newUrl);
              } else {
                  $('#myInput').trigger('keyup');
              }

            },
            error: function() {
              Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
          })
        }

        function viewipdlist(e)
        { 
          current_iid = e;

          // 1. เรียกใช้ SweetAlert โชว์สถานะกำลังโหลด
          Swal.fire({
              title: 'กำลังดึงข้อมูล...',
              html: 'รอสักครู่...',
              allowOutsideClick: false,
              scrollbarPadding: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          var actionipdlistonly = $("#selectTypeOpt").val();  
          var iid = e; 
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionipdlistonly:actionipdlistonly,iid:iid},
            success:function(data)
            {
              // 2. ปิด SweetAlert เมื่อข้อมูลโหลดเสร็จ
              Swal.close();
          
              $('#data_tabel_listview').html(data);
              
              // 🌟 สร้างปุ่มแท็บกลุ่มย่อย CR แบบ Dynamic
              buildCrSubgroupTabs();

              $('#modal_dept_heading').text('รายละเอียดข้อมูลลูกหนี้รายตัว (ผู้ป่วยใน)');
              var titleData = $('#modal_report_title_data').data('title');
              if (titleData) {
                  $('#modal_report_title_text').html(titleData);
              } else {
                  $('#modal_report_title_text').html('📑 รายงานสรุปลูกหนี้รายตัว ตามสิทธิการเงิน');
              }

              var o0 = $('#i0').text().trim();
              $('#txtq0').text((o0 ? o0 : '0') + ' คน');  
              var o1 = $('#i1').text().trim();
              $('#txtq1').text(o1 ? o1 : '0.00');
              var o2 = $('#i2').text().trim();
              $('#txtq2').text(o2 ? o2 : '0.00');
              var o3 = $('#i3').text().trim();
              $('#txtq3').text(o3 ? o3 : '0.00');
              var o4 = $('#i4').text().trim();
              $('#txtq4').text(o4 ? o4 : '0.00');

              $('#selected_count_num').text('0');
              $('#checkAll').prop('checked', false);

              $('#myModalgetlistview').modal('show');

              const urlParams = new URLSearchParams(window.location.search);
              const initialDecryptedQ = <?php echo json_encode($decrypted_q); ?>;
              const q = initialDecryptedQ || urlParams.get('q');
              if (q) {
                  $('#myInput').val(q).trigger('keyup');
                  const newUrl = new URL(window.location);
                  newUrl.searchParams.delete('q');
                  window.history.replaceState({}, '', newUrl);
              } else {
                  $('#myInput').trigger('keyup');
              }

            },
            error: function() {
              Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
          })
        }



        // เคลียร์ padding ที่อาจค้างตอนปิด Modal
        $('#myModalgetlistview').on('hidden.bs.modal', function () {
            $('body').css('padding-right', '');
            $('body').removeClass('modal-open');
            // คืนค่าช่องค้นหาและตาราง
            $('#myInput').val('').trigger('keyup');
        });

        $(document).on('focus', 'td', function() {
            if (typeof activeCrSubgroupFilter !== 'undefined' && activeCrSubgroupFilter !== 'ALL') {
                $(this).blur();
                return;
            }
            // เก็บค่าก่อนแก้ไว้เปรียบเทียบ (ป้องกันคนกดผ่านเฉยๆ แล้วมันอัปเดต)
            $(this).data('old-value', $(this).text().trim());
        });

        // 🌟 แจ้งเตือนเมื่อคลิกช่องที่ถูกล็อกในมุมมองกลุ่มย่อย
        $(document).on('click', '.subgroup-locked-cell, td[data-subgroup-locked="true"]', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'warning',
                title: 'อนุญาตให้ลงข้อมูลได้เฉพาะแท็บ "ทั้งหมด" เท่านั้น'
            });
        });

        // 🌟 แจ้งเตือนเมื่อคลิก Checkbox หรือปุ่มเลือกทั้งหมดในมุมมองกลุ่มย่อย
        $(document).on('click', '#checkAll:disabled, input[name="select[]"]:disabled, #btnCheckAllTop[disabled]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'warning',
                title: 'อนุญาตให้เลือกรายการได้เฉพาะแท็บ "ทั้งหมด" เท่านั้น'
            });
        });

        // 🌟 จัดรูปแบบเลขที่ใบเสร็จเป็นมาตรฐาน 4 หลัก / 4 หลัก (0000/0000)
        function formatBillNo(val) {
            if (val === null || val === undefined) return '';
            val = String(val).trim();
            if (!val) return '';
            if (val.includes('/')) {
                let parts = val.split('/');
                let front = (parts[0] || '').replace(/\D/g, '');
                let back = (parts[1] || '').replace(/\D/g, '');
                if (front === '' && back === '') return '';
                let frontPadded = front.padStart(4, '0');
                let backPadded = back.padStart(4, '0');
                return frontPadded + '/' + backPadded;
            } else {
                let digits = val.replace(/\D/g, '');
                if (digits === '') return val;
                if (digits.length === 8) {
                    return digits.substring(0, 4) + '/' + digits.substring(4, 8);
                }
                if (digits.length <= 4) {
                    return digits.padStart(4, '0') + '/0000';
                }
                return digits.substring(0, digits.length - 4).padStart(4, '0') + '/' + digits.slice(-4).padStart(4, '0');
            }
        }

        $(document).on('keydown', 'td[contenteditable="true"]', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                $(this).blur();
            }
        });

        $(document).on('blur', 'td', function(event) {
            if (typeof activeCrSubgroupFilter !== 'undefined' && activeCrSubgroupFilter !== 'ALL') {
                return; // ป้องกันการบันทึกข้อมูลเมื่ออยู่ในมุมมองกลุ่มย่อย
            }
            var targetElement = event.target;
            var $td = $(this);
            var oldValue = $td.data('old-value');

            setTimeout(function() {
                var data = targetElement.innerText.trim();

                // 🌟 Auto-format เลขที่ใบเสร็จเป็น 0000/0000 ทันทีเมื่อ blur
                var isBillCell = $td.data("data2") || $td.data("data2i") || $td.hasClass("mask-bill");
                if (isBillCell && data) {
                    data = formatBillNo(data);
                    $td.text(data);
                }

                // ถ้าค่าเหมือนเดิม ไม่ต้องทำอะไรพี่ เปลืองทรัพยากร
                if (data === oldValue) return;
                $td.data('old-value', data);

                // เตรียมตัวแปร vn
                var vn = $td.data("data1") || $td.data("data2") || $td.data("data3") || 
                         $td.data("data1i") || $td.data("data2i") || $td.data("data3i");

                if (!vn) return;

                // แจ้งเตือนแบบ Toast (มุมขวาบน) ไม่กวนสายตา
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1000,
                    timerProgressBar: true
                });

                Toast.fire({
                    icon: 'info',
                    title: 'กำลังบันทึก...'
                });

                // เช็กเงื่อนไขและส่งฟังก์ชันตาม data-attribute
                // พี่ใช้การ return $.ajax จากฟังก์ชันลูกมาเช็กความสำเร็จได้เลย
                let request;

                if ($td.data("data1"))  request = sentdata_a(vn, data);
                if ($td.data("data2"))  request = sentdata_b(vn, data);
                if ($td.data("data3"))  request = sentdata_c(vn, data);
                if ($td.data("data1i")) request = sentdata_ai(vn, data);
                if ($td.data("data2i")) request = sentdata_bi(vn, data);
                if ($td.data("data3i")) request = sentdata_ci(vn, data);

                // เมื่อส่งเสร็จ เปลี่ยน Toast เป็นสีเขียว
                if (request) {
                    request.done(function() {
                        Toast.fire({
                            icon: 'success',
                            title: 'บันทึกเรียบร้อย'
                        });
                    }).fail(function() {
                        Toast.fire({
                            icon: 'error',
                            title: 'บันทึกไม่สำเร็จ!'
                        });
                    });
                }
            }, 100);
        });




        function sentdata_a(vn, data) {
            var typecc = $('#typecc').val();
            var acc = (typecc === 'OPD' ? (typeof current_oid !== 'undefined' ? current_oid : '') : (typeof current_iid !== 'undefined' ? current_iid : ''));
            return $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { follow_moneyvn: vn, follow_money: data, typecc: typecc, accountcode: acc },
                success: function(res) { console.log("A Success:", res); }
            });
        }

        function sentdata_b(vn, data) {
            data = formatBillNo(data);
            var typecc = $('#typecc').val();
            var acc = (typecc === 'OPD' ? (typeof current_oid !== 'undefined' ? current_oid : '') : (typeof current_iid !== 'undefined' ? current_iid : ''));
            return $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { follow_moneyvn: vn, bill: data, typecc: typecc, accountcode: acc },
                success: function(res) { console.log("B Success:", res); }
            });
        }

        function sentdata_c(vn, data) {
            var typecc = $('#typecc').val();
            var acc = (typecc === 'OPD' ? (typeof current_oid !== 'undefined' ? current_oid : '') : (typeof current_iid !== 'undefined' ? current_iid : ''));
            return $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { follow_moneyvn: vn, billdate: data, typecc: typecc, accountcode: acc },
                success: function(res) { console.log("C Success:", res); }
            });
        }

        function sentdata_ai(vn, data) {
            var typecc = $('#typecc').val();
            var acc = (typecc === 'OPD' ? (typeof current_oid !== 'undefined' ? current_oid : '') : (typeof current_iid !== 'undefined' ? current_iid : ''));
            return $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { follow_moneyvn: vn, follow_money_i: data, typecc: typecc, accountcode: acc },
                success: function(res) { console.log("AI Success:", res); }
            });
        }

        function sentdata_bi(vn, data) {
            data = formatBillNo(data);
            var typecc = $('#typecc').val();
            var acc = (typecc === 'OPD' ? (typeof current_oid !== 'undefined' ? current_oid : '') : (typeof current_iid !== 'undefined' ? current_iid : ''));
            return $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { follow_moneyvn: vn, bill_i: data, typecc: typecc, accountcode: acc },
                success: function(res) { console.log("BI Success:", res); }
            });
        }

        function sentdata_ci(vn, data) {
            var typecc = $('#typecc').val();
            var acc = (typecc === 'OPD' ? (typeof current_oid !== 'undefined' ? current_oid : '') : (typeof current_iid !== 'undefined' ? current_iid : ''));
            return $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { follow_moneyvn: vn, billdate_i: data, typecc: typecc, accountcode: acc },
                success: function(res) { console.log("CI Success:", res); }
            });
        }

        function checkAllbill(status) {
            if (typeof activeCrSubgroupFilter !== 'undefined' && activeCrSubgroupFilter !== 'ALL') {
                Swal.fire({
                    icon: 'warning',
                    title: 'แจ้งเตือน',
                    text: 'อนุญาตให้แนบใบเสร็จได้เฉพาะแท็บ "ทั้งหมด" เท่านั้น',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            // 1. ตรวจสอบว่ามีการติ๊กเลือกรายการหรือไม่
            var selected = [];
            $('input[name="select[]"]:checked').each(function() {
                selected.push($(this).val());
            });

            // เช็ค: ถ้าไม่ได้เลือกอะไรเลย
            if (selected.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'แจ้งเตือน',
                    text: 'กรุณาติ๊กเลือกรายการที่ต้องการ',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            $('#addAllbill').modal('show');
            $('#typeccbill').val($('#typecc').val());
        }


        function checkAllbilladd(status) {
            if (!status) {
                $('#addAllbill').modal('hide');
                return;
            }

            let payment = $('input[name="payment"]').val();
            payment = formatBillNo(payment);
            const paymentdate = $('input[name="paymentdate"]').val();
            const type = $('#typecc').val();

            // 1. ดึง Checkbox ที่ถูกเลือกทั้งหมด
            let selectedRows = $('input[name="select[]"]:checked');
            let ids = [];

            // 2. เอาค่า VN/AN ยัดใส่ Array
            selectedRows.each(function () {
                ids.push($(this).val());
            });

            if (ids.length === 0) {
                Swal.fire('แจ้งเตือน', 'ไม่พบรายการที่เลือก', 'warning');
                return;
            }

            $('#addAllbill').modal('hide');

            // โชว์หน้าโหลด
            Swal.fire({
                title: 'กำลังปรับปรุงข้อมูล...',
                html: 'อัปเดตข้อมูลจำนวน <b>' + ids.length + '</b> รายการ',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            // 3. ยิง AJAX แค่ 1 ครั้ง ส่ง Array ไปให้ PHP จัดการ
            $.ajax({
              url: "datatimestamp-insert2.php",
              method: "POST",
              data: {
                  action_bulk_update_bill: true,
                  typecc: type,
                  bulk_bill: payment,          // เปลี่ยนชื่อเพื่อไม่ให้ซ้ำกับของเดิม
                  bulk_billdate: paymentdate,  // เปลี่ยนชื่อเพื่อไม่ให้ซ้ำกับของเดิม
                  ids: JSON.stringify(ids),     // มัดรวม array เป็น string ก้อนเดียว
                  accountcode: (type === 'OPD' ? (typeof current_oid !== 'undefined' ? current_oid : '') : (typeof current_iid !== 'undefined' ? current_iid : ''))
              },
              success: function(res) {
                    if(res.trim() === 'success') {
                        
                        // 4. อัปเดตข้อความบนหน้าจอ (ตาราง) ให้ตรงกับที่เพิ่งบันทึกไป จะได้ไม่ต้องโหลดหน้าเว็บใหม่
                        selectedRows.each(function () {
                            const row = $(this).closest('tr');
                            if (type === 'OPD') {
                                row.find('td[data-data2]').text(payment);
                                row.find('td[data-data3]').text(paymentdate);
                            } else {
                                row.find('td[data-data2i]').text(payment);
                                row.find('td[data-data3i]').text(paymentdate);
                            }
                        });

                        Swal.fire({
                            icon: 'success',
                            title: 'ปรับปรุงข้อมูลสำเร็จ',
                            text: 'ดำเนินการครบ ' + ids.length + ' รายการแล้ว',
                            timer: 2000,
                            showConfirmButton: false
                        });

                    } else {
                        Swal.fire('ข้อผิดพลาด', 'ระบบตอบกลับมาว่า: ' + res, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้: ' + error, 'error');
                }
            });
        }


         document.getElementById('myForm').addEventListener('submit', function(event) {
          if (typeof activeCrSubgroupFilter !== 'undefined' && activeCrSubgroupFilter !== 'ALL') {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'แจ้งเตือน',
                text: 'อนุญาตให้ทำรายการได้เฉพาะแท็บ "ทั้งหมด" เท่านั้น',
                confirmButtonColor: '#3085d6'
            });
            return;
          }
          var checkedValues = [];
          var checkboxes = document.querySelectorAll('input[name="select[]"]:checked');

          checkboxes.forEach(function(checkbox) {
            checkedValues.push(checkbox.value);
          });

          if (checkedValues.length === 0) {
            event.preventDefault();
            alert('กรุณาเลือกรายการที่ต้องการ!!!!');
          }
        });

         document.getElementById('exportstmgetovlookup').addEventListener('click', function () {
          let table = document.querySelector('.stmgetovlookup');
          let workbook = XLSX.utils.book_new();

          // ✅ ใช้ raw: true เพื่อให้เก็บค่าจาก HTML เป็น string ตามที่แสดง
          let worksheet = XLSX.utils.table_to_sheet(table, { raw: true });

          XLSX.utils.book_append_sheet(workbook, worksheet, "Data");
          XLSX.writeFile(workbook, 'ExportedData.xlsx');
        });


        // =========================================================================
        // 🌟 ฟังก์ชันจัดการแท็บกลุ่มย่อย CR บน Modal แบบ Dynamic (รองรับ OPD & IPD)
        // =========================================================================
        var activeCrSubgroupFilter = 'ALL';

        window.buildCrSubgroupTabs = function() {
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

                // วนลูปสร้างปุ่มแท็บ
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
        };

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

        window.filterTableByCrSubgroup = function() {
            var filter = activeCrSubgroupFilter;
            var searchVal = $('#myInput').val().toLowerCase().trim();
            var visibleCount = 0;
            var rows = $("#data_tabel_listview tr");
            var mainRows = $("#data_tabel_listview tr.main-patient-row");
            if (mainRows.length === 0) {
                mainRows = $("#data_tabel_listview tr:not(.summary-total-row):not(.sub-payment-row):not(.spacer-row)");
            }
            var totalMainRows = mainRows.length;

            var total_income = 0;
            var total_incomediff = 0;
            var total_debit = 0;
            var total_follow = 0;

            rows.each(function () {
                var tr = $(this);
                if (tr.hasClass('summary-total-row') || tr.closest('tfoot').length > 0) {
                    return;
                }
                if (tr.hasClass('spacer-row')) {
                    tr.show();
                    return;
                }

                if (tr.hasClass('main-patient-row') || (!tr.hasClass('main-patient-row') && !tr.hasClass('sub-payment-row'))) {
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
                        tr.find('td').eq(1).text(visibleCount); // รันเลขลำดับ 1, 2, 3 ใหม่

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

                        // คำนวณยอดรวม Realtime
                        var inc = parseFloat(tr.find('td').eq(8).text().replace(/,/g, '')) || 0;
                        var indiff = parseFloat(tr.find('td').eq(9).text().replace(/,/g, '')) || 0;
                        var deb = parseFloat(tr.find('td').eq(10).text().replace(/,/g, '')) || 0;
                        var fol = parseFloat(tr.find('td').eq(11).text().replace(/,/g, '')) || 0;
                        total_income += inc;
                        total_incomediff += indiff;
                        total_debit += deb;
                        total_follow += fol;
                    }
                } else if (tr.hasClass('sub-payment-row')) {
                    var prevMain = tr.prevAll('.main-patient-row:first');
                    if (prevMain.length > 0) {
                        tr.toggle(prevMain.is(':visible'));
                    } else {
                        var prevTr = tr.prev('tr:not(.sub-payment-row)');
                        tr.toggle(prevTr.is(':visible'));
                    }
                }
            });

            if (searchVal === '' && filter === 'ALL') {
                $('#clearSearchBtn').hide();
                $('#filtered_row_count').text('กำลังแสดงทุกรายการ (' + totalMainRows + ' รายการ)');
            } else {
                if (searchVal !== '') $('#clearSearchBtn').css('display', 'inline-flex');
                $('#filtered_row_count').html('พบ <b>' + visibleCount + '</b> จาก ' + totalMainRows + ' รายการ');
            }

            // อัปเดต KPI Cards ด้านบน Modal
            $('#txtq0').text(visibleCount + ' คน');
            $('#txtq1').text(total_income.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#txtq2').text(total_incomediff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#txtq3').text(total_debit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#txtq4').text(total_follow.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            // อัปเดตแถวสรุปท้ายตารางด้วย
            var sumRow = $("#data_tabel_listview tr.summary-total-row");
            if (sumRow.length > 0) {
                var percen = (total_debit > 0) ? (total_follow / total_debit) * 100 : 0;
                sumRow.find('#o0, #i0').text(visibleCount);
                sumRow.find('#o1, #i1').html('&nbsp;' + total_income.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                sumRow.find('#o2, #i2').html('&nbsp;' + total_incomediff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                sumRow.find('#o3, #i3').html('&nbsp;' + total_debit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                sumRow.find('#o4, #i4').html('&nbsp;' + total_follow.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (' + percen.toFixed(2) + '%)');
            }

            // 🌟 ล็อกไม่ให้กรอกข้อมูลในช่องสีเหลือง และปิด Checkbox เมื่อเลือกกลุ่มย่อย (อนุญาตให้ลงข้อมูลเฉพาะแท็บ ทั้งหมด)
            var isAllTab = (filter === 'ALL');
            var editableCells = $("#data_tabel_listview td[data-data1], #data_tabel_listview td[data-data2], #data_tabel_listview td[data-data3], #data_tabel_listview td[data-data1i], #data_tabel_listview td[data-data2i], #data_tabel_listview td[data-data3i], #data_tabel_listview td[contenteditable]");
            var allCheckboxes = $("#checkAll, #data_tabel_listview input[name='select[]']");
            var btnCheckTop = $("#btnCheckAllTop");

            if (!isAllTab) {
                // 1. ปิด Checkbox ทั้งหมด และยกเลิกการเลือก
                allCheckboxes.prop('checked', false).prop('disabled', true).css({'cursor': 'not-allowed', 'opacity': '0.45'});
                $('#checkAll').attr('title', 'ปิดการเลือกในมุมมองกลุ่มย่อย (กรุณาเลือกแท็บ ทั้งหมด เพื่อเลือกรายการ)');
                $("#data_tabel_listview input[name='select[]']").attr('title', 'ปิดการเลือกในมุมมองกลุ่มย่อย');
                btnCheckTop.prop('disabled', true).css({'pointer-events': 'none', 'opacity': '0.5'}).attr('title', 'ปิดการเลือกในมุมมองกลุ่มย่อย');

                // 2. ปิดการกรอกข้อมูลในช่องสีเหลือง (contenteditable = false)
                editableCells.each(function() {
                    var cell = $(this);
                    cell.attr('contenteditable', 'false')
                        .attr('data-subgroup-locked', 'true')
                        .addClass('subgroup-locked-cell')
                        .attr('title', 'อนุญาตให้ลงข้อมูลได้เฉพาะแท็บ "ทั้งหมด" เท่านั้น');
                });
            } else {
                // 1. เปิด Checkbox ทั้งหมด
                allCheckboxes.prop('disabled', false).css({'cursor': 'pointer', 'opacity': '1'}).removeAttr('title');
                btnCheckTop.prop('disabled', false).css({'pointer-events': 'auto', 'opacity': '1'}).removeAttr('title');

                // 2. เปิดให้กรอกข้อมูลในช่องสีเหลือง (contenteditable = true)
                editableCells.each(function() {
                    var cell = $(this);
                    cell.attr('contenteditable', 'true')
                        .removeAttr('data-subgroup-locked')
                        .removeClass('subgroup-locked-cell')
                        .attr('title', 'คลิกเพื่อแก้ไขข้อมูล');
                });
            }

            $("#checkAll").prop('checked', false);
            if (typeof updateSelectedCount === 'function') updateSelectedCount();
        };

        // ฟังก์ชันอัปเดตจำนวนที่เลือก (Checkbox selection counter)
        function updateSelectedCount() {
          var count = $("#data_tabel_listview input[name='select[]']:checked").length;
          $("#selected_count_num").text(count);
          if (count > 0) {
            $("#selectedCountBadge").css({"background": "#dbeafe", "border-color": "#3b82f6", "color": "#1d4ed8"});
          } else {
            $("#selectedCountBadge").css({"background": "#eff6ff", "border-color": "#bfdbfe", "color": "#1d4ed8"});
          }
        }

        // ฟังก์ชันอัปเดตจำนวนแถวที่กรองค้นหา (Search row counter)
        function updateFilterCount() {
          filterTableByCrSubgroup();
        }

        $(document).ready(function () {
            // การค้นหาอัจฉริยะ (Smart Filter)
            $("#myInput").on("keyup input", function () {
              filterTableByCrSubgroup();
            });

            // Check All
            $("#checkAll").click(function () {
                var isChecked = $(this).prop('checked');
                $("#data_tabel_listview tr:visible").find("input[name='select[]']").prop('checked', isChecked);
                updateSelectedCount();
            });

            // Individual Checkbox change
            $(document).on("change", "#data_tabel_listview input[name='select[]']", function () {
                updateSelectedCount();
            });

            // รีเซ็ตสถานะเมื่อ Modal เปิด
            $('#myModalgetlistview').on('shown.bs.modal', function () {
                updateFilterCount();
                updateSelectedCount();
            });
        });


      // ไฟล์: ลูกหนี้รายตัว.php

      function charge_off() {
          if (typeof activeCrSubgroupFilter !== 'undefined' && activeCrSubgroupFilter !== 'ALL') {
              Swal.fire({
                  icon: 'warning',
                  title: 'แจ้งเตือน',
                  text: 'อนุญาตให้ทำรายการแบ่งจ่ายได้เฉพาะแท็บ "ทั้งหมด" เท่านั้น',
                  confirmButtonColor: '#3085d6'
              });
              return;
          }
          var selected = [];
          $('input[name="select[]"]:checked').each(function() {
              selected.push($(this).val());
          });

          if (selected.length === 0) {
              Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาติ๊กเลือกรายการที่ต้องการแบ่งจ่าย', confirmButtonColor: '#3085d6' });
              return;
          }

          if (selected.length > 1) {
              Swal.fire({ icon: 'warning', title: 'ทำรายการได้ทีละ 1 คน', text: 'เพื่อป้องกันความผิดพลาด กรุณาเลือกรายการเพียงคนเดียว', confirmButtonColor: '#d33' });
              return;
          }

          try {
              var vn_an = selected[0]; 
              var row = $('input[value="' + vn_an + '"]').closest('tr'); 

              // --- [แก้ไข] ระบุตำแหน่งคอลัมน์ให้แม่นยำ ---
              // eq(5)  = ชื่อผู้ป่วย
              // eq(11) = ภาระหนี้ (Debit)  <-- แก้ตรงนี้
              // eq(12) = ชดเชยแล้ว (Follow money) <-- แก้ตรงนี้
              
              var ptname = row.find('td').eq(4).text().trim(); 
              var debit_str = row.find('td').eq(10).text().trim().replace(/,/g, ''); 
              var paid_str  = row.find('td').eq(11).text().trim().replace(/,/g, ''); 

              var debit = parseFloat(debit_str) || 0;
              var paid  = parseFloat(paid_str) || 0;
              var balance = debit - paid; // ยอดคงเหลือที่แท้จริง

              var typecc = $('#typecc').val(); 

              // ใส่ค่าลง Modal
              $('#pp_ref_id').val(vn_an);
              $('#pp_patient_type').val(typecc);
              $('#pp_history_id').val(''); // เคลียร์ ID เพื่อบอกว่าเป็นรายการใหม่

              $('#pp_ptname').val(ptname);
              $('#pp_total_debit').val(debit.toLocaleString('en-US', {minimumFractionDigits: 2}));
              $('#pp_paid_old').val(paid.toLocaleString('en-US', {minimumFractionDigits: 2}));
              
              // แสดงยอดคงเหลือในช่องสีแดง
              $('#pp_balance').val(balance.toLocaleString('en-US', {minimumFractionDigits: 2}));
              
              // เคลียร์ช่องกรอกเงิน และใส่วันที่ปัจจุบัน
              $('#pp_amount').val(''); 
              $('#pp_bill_no').val('');
              $('#pp_note').val('');
              $('#pp_bill_date').val('<?php echo date("d/m/").(date("Y")+543); ?>');

              var myModal = new bootstrap.Modal(document.getElementById('modalPartialPayment'), { keyboard: false });
              myModal.show();
              
          } catch (err) {
              console.error("Error opening modal:", err);
              alert("เกิดข้อผิดพลาดในการดึงข้อมูล: " + err.message);
          }
      }

      // 1. ฟังก์ชันเมื่อกดแก้ไข (รับค่าจาก this)
      function editPartial(element) {
          // ดึงค่าจาก data-attribute ของปุ่มที่กด
          var id = $(element).data('id');
          var amount = $(element).data('amount');
          var bill_no = $(element).data('bill');
          var bill_date = $(element).data('date');
          var note = $(element).data('note');
          var vn = $(element).data('vn');

          // ตรวจสอบว่าติ๊ก checkbox แถวหลักหรือยัง
          if (vn && $('input[name="select[]"][value="'+vn+'"]').length > 0) {
              if (!$('input[name="select[]"][value="'+vn+'"]').is(':checked')) {
                  Swal.fire('แจ้งเตือน', 'กรุณาติ๊กเลือกรายการ (Checkbox) ที่แถวหลักก่อนทำการแก้ไขงวดนี้', 'warning');
                  return;
              }
          }

          // ใส่ค่าลงใน Modal
          $('#pp_history_id').val(id);
          $('#pp_amount').val(amount);
          $('#pp_bill_no').val(bill_no);
          $('#pp_bill_date').val(bill_date);
          $('#pp_note').val(note);

          // แยก bill_no ลง input หน้า/หลัง
          if (bill_no) {
              // เนื่องจากข้อมูลเป็น string หรือ integer เราต้องแปลงเป็น string ก่อน split
              var bill_str = bill_no.toString();
              var parts = bill_str.split('/');
              if (parts.length === 2) {
                  $('input[name="bill_no_front"]').val(parts[0]);
                  $('input[name="bill_no_back"]').val(parts[1]);
              } else {
                  $('input[name="bill_no_front"]').val(bill_str);
                  $('input[name="bill_no_back"]').val('');
              }
          } else {
              $('input[name="bill_no_front"]').val('');
              $('input[name="bill_no_back"]').val('');
          }

          // เปิด Modal
          var myModal = new bootstrap.Modal(document.getElementById('modalPartialPayment'));
          myModal.show();
      }

      // --- แก้ไขไฟล์ ลูกหนี้รายตัว.php (ส่วน Script ล่างสุด) ---

      function deletePartial(arg1, arg2, arg3) {
            var id, vn, type;

            // 1. ตรวจสอบว่าส่งมาแบบ "ปุ่ม" (this) หรือแบบ "ค่า" (id, vn, type)
            if (typeof arg1 === 'object') {
                // แบบใหม่: ดึงจาก data-attribute ของปุ่ม
                id = $(arg1).data('id');
                vn = $(arg1).data('vn');
                type = $(arg1).data('type');
            } else {
                // แบบเก่า: รับค่าตรงๆ
                id = arg1;
                vn = arg2;
                type = arg3;
            }


            if (!id) {
                Swal.fire('Error', 'ไม่พบรหัสรายการที่จะลบ (ID Missing)', 'error');
                return;
            }

            // ตรวจสอบว่าติ๊ก checkbox แถวหลักหรือยัง
            if (vn && $('input[name="select[]"][value="'+vn+'"]').length > 0) {
                if (!$('input[name="select[]"][value="'+vn+'"]').is(':checked')) {
                    Swal.fire('แจ้งเตือน', 'กรุณาติ๊กเลือกรายการ (Checkbox) ที่แถวหลักก่อนทำการลบงวดนี้', 'warning');
                    return;
                }
            }

            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "ยอดเงินจะถูกคำนวณใหม่ทันที",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'ลบรายการ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.value) {
                    
                    // ดึงค่ากลุ่มสิทธิปัจจุบันมาไว้รีเฟรช
                    var active_oid = $('#typecc').data('oid');
                    var active_iid = $('#typecc').data('iid');

                    $.ajax({
                        url: "datatimestamp-insert2.php",
                        method: "POST",
                        data: { action_delete_partial: true, id: id, vn: vn, type: type },
                        success: function(res) {

                            if (res.trim() === 'deleted') {
                                Swal.fire('ลบสำเร็จ', '', 'success');
                                
                                // รีเฟรชข้อมูล
                                if(type == 'OPD') {
                                    if(active_oid) viewopdlist(active_oid);
                                    else if(current_oid) viewopdlist(current_oid);
                                } else {
                                    if(active_iid) viewipdlist(active_iid);
                                    else if(current_iid) viewipdlist(current_iid);
                                }
                            } else {
                                // ถ้าลบไม่ได้ ให้แจ้งเตือน Error ที่ส่งกลับมา
                                Swal.fire('แจ้งเตือน', 'ระบบตอบกลับมาว่า: ' + res, 'warning');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                            Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error, 'error');
                        }
                    });
                }
            })
      }

      // 3. ฟังก์ชันบันทึก (แก้ส่วนรีเฟรชตอนจบ)
      function savePartialPayment() {
          var vn = $('#pp_ref_id').val();
          var amount = $('#pp_amount').val();
          var bill_no = $('#pp_bill_no').val();
          var bill_date = $('#pp_bill_date').val();
          var type = $('#pp_patient_type').val(); 
          var note = $('#pp_note').val(); 
          var history_id = $('#pp_history_id').val(); 

          if (!amount || parseFloat(amount) <= 0) {
              Swal.fire('แจ้งเตือน', 'กรุณาระบุยอดเงินให้ถูกต้อง', 'warning');
              return;
          }

          $.ajax({
              url: "datatimestamp-insert2.php",
              method: "POST",
              data: {
                  action_save_partial: true,
                  vn: vn,
                  amount: amount,
                  bill_no: bill_no,
                  bill_date: bill_date,
                  type: type,
                  note: note,
                  history_id: history_id,
                  accountcode: (type === 'OPD' ? (typeof current_oid !== 'undefined' ? current_oid : '') : (typeof current_iid !== 'undefined' ? current_iid : ''))
              },
              success: function(response) {
                  $('#modalPartialPayment').modal('hide');
                  Swal.fire({
                      icon: 'success',
                      title: 'บันทึกสำเร็จ',
                      text: 'ดำเนินการเรียบร้อยแล้ว',
                      timer: 1500
                  }).then(() => {
                      // รีเฟรชข้อมูล (ใช้ตัวแปร Global ชัวร์กว่าการดึงจากตาราง)
                      if(type == 'OPD') {
                          if(current_oid) viewopdlist(current_oid);
                      } else {
                          if(current_iid) viewipdlist(current_iid);
                      }
                  });
              },
              error: function() {
                  Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
              }
          });
      }







      $('#myForm input').on('keydown', function(e) {
          if (e.key === 'Enter') {
              e.preventDefault();

              let next = $('[tabindex="' + (this.tabIndex + 1) + '"]');

              if (next.length) {
                  next.focus();
              } else {
                  // ถ้าเป็นตัวสุดท้าย → กดบันทึก
                  $('.modal-footer .btn-primary').click();
              }
          }
      });

      $('#addAllbill').on('shown.bs.modal', function () {
          $('input[name="payment_front"]').focus();
      });


      function updatePaymentPreview() {
          let front = $('input[name="payment_front"]').val().replace(/\D/g, '');
          let back  = $('input[name="payment_back"]').val().replace(/\D/g, '');

          if (front.length === 0 && back.length === 0) {
              $('input[name="payment"]').val('');
              return;
          }

          let showFront = front.padStart(4, '0');
          let showBack  = back.padStart(4, '0');

          let payment = showFront + '/' + showBack;
          $('input[name="payment"]').val(payment);
      }

      // ฟัง input (ไม่ pad ใส่ช่องจริง)
      $('input[name="payment_front"], input[name="payment_back"]').on('input', function () {
          this.value = this.value.replace(/\D/g, '');
          updatePaymentPreview();
      });

      // ช่องหน้า
      $('input[name="payment_front"]').on('blur', function () {
          let val = $(this).val().replace(/\D/g, '');
          $(this).val(val.padStart(4, '0'));
           updatePaymentPreview();
      });
      // ช่องหลัง
      $('input[name="payment_back"]').on('blur', function () {
          let val = $(this).val().replace(/\D/g, '');
          $(this).val(val.padStart(4, '0'));
           updatePaymentPreview();
      });

      document.querySelectorAll('input[name="paymentdate"]').forEach(input => {
          input.addEventListener('input', function (e) {
              let value = e.target.value.replace(/\D/g, ''); // ดึงเฉพาะตัวเลข
              let name = e.target.name;
              
          if (name === 'paymentdate') {
                  // จัดการ format 01/01/2569
                  if (value.length > 2 && value.length <= 4) {
                      value = value.substring(0, 2) + '/' + value.substring(2);
                  } else if (value.length > 4) {
                      value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4, 8);
                  }
              }
              e.target.value = value;
          });
      });






      $('#formPartialPay input').on('keydown', function(e) {
          if (e.key === 'Enter') {
              e.preventDefault();

              let next = $('[tabindex="' + (this.tabIndex + 1) + '"]');

              if (next.length) {
                  next.focus();
              } else {
                  // ถ้าเป็นตัวสุดท้าย → กดบันทึก
                  $('.modal-footer .btn-primary').click();
              }
          }
      });

      $('#modalPartialPayment').on('shown.bs.modal', function () {
          $('input[name="amount"]').focus();
      });


      function updatePaymentPreview1() {
          let front = $('input[name="bill_no_front"]').val().replace(/\D/g, '');
          let back  = $('input[name="bill_no_back"]').val().replace(/\D/g, '');

          if (front.length === 0 && back.length === 0) {
              $('input[name="bill_no"]').val('');
              $('#pp_bill_no').val(''); // เคลียร์ pp_bill_no ด้วย
              return;
          }

          let showFront = front.padStart(4, '0');
          let showBack  = back.padStart(4, '0');

          let payment = showFront + '/' + showBack;
          $('input[name="bill_no"]').val(payment);
          $('#pp_bill_no').val(payment); // อัปเดต hidden field ใน modalPartialPayment ด้วย
      }

      // ฟัง input (ไม่ pad ใส่ช่องจริง)
      $('input[name="bill_no_front"], input[name="bill_no_back"]').on('input', function () {
          this.value = this.value.replace(/\D/g, '');
          updatePaymentPreview1();
      });

      // ช่องหน้า
      $('input[name="bill_no_front"]').on('blur', function () {
          let val = $(this).val().replace(/\D/g, '');
          $(this).val(val.padStart(4, '0'));
           updatePaymentPreview1();
      });
      // ช่องหลัง
      $('input[name="bill_no_back"]').on('blur', function () {
          let val = $(this).val().replace(/\D/g, '');
          $(this).val(val.padStart(4, '0'));
           updatePaymentPreview1();
      });


      document.querySelectorAll('input[name="bill_date"]').forEach(input => {
          input.addEventListener('input', function (e) {
              let value = e.target.value.replace(/\D/g, ''); // ดึงเฉพาะตัวเลข
              let name = e.target.name;
              
          if (name === 'bill_date') {
                  // จัดการ format 01/01/2569
                  if (value.length > 2 && value.length <= 4) {
                      value = value.substring(0, 2) + '/' + value.substring(2);
                  } else if (value.length > 4) {
                      value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4, 8);
                  }
              }
              e.target.value = value;
          });
      });



      $(document).on('input', '.mask-bill', function() {
          let raw = $(this).text();

          let parts = raw.split('/');

          let front = (parts[0] || '').replace(/\D/g, '');
          let back  = (parts[1] || '').replace(/\D/g, '');

          front = front.substring(0, 4);
          back  = back.substring(0, 4);

          let display = front;

          // 👉 มี / ค่อยแสดง /
          if (raw.includes('/')) {
              display = front + '/' + back; // ❌ ไม่ pad
          }

          $(this).text(display);
          placeCaretAtEnd(this);
      });


      $(document).on('blur', '.mask-bill', function() {
          let raw = $(this).text().trim();
          if (!raw) return;
          let formatted = formatBillNo(raw);
          $(this).text(formatted);
      });




      $(document).on('input', '.mask-date', function() {
          let text = $(this).text().replace(/\D/g, ''); // เอาเฉพาะตัวเลข
          if (text.length > 2 && text.length <= 4) {
              text = text.substring(0, 2) + '/' + text.substring(2);
          } else if (text.length > 4) {
              text = text.substring(0, 2) + '/' + text.substring(2, 4) + '/' + text.substring(4, 8);
          }
          $(this).text(text);
          placeCaretAtEnd(this); // เลื่อนเคอร์เซอร์ไปท้ายสุด
      });




      // ฟังก์ชันช่วยเลื่อนเคอร์เซอร์ไปท้ายสุดเวลาพิมพ์ (เพราะ .text() จะทำให้เคอร์เซอร์เด้งไปหน้าสุด)
      function placeCaretAtEnd(el) {
          el.focus();
          if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined") {
              var range = document.createRange();
              range.selectNodeContents(el);
              range.collapse(false);
              var sel = window.getSelection();
              sel.removeAllRanges();
              sel.addRange(range);
          }
      }



// =========================================================================
// ฟังก์ชัน Sortable Table สำหรับเรียงลำดับข้อมูลใน Modal แบบอัจฉริยะ (ยกก้อนแม่-ลูก)
// =========================================================================
$(document).on('click', '.stmgetovlookup th.sortable-header', function() {
    var table = $(this).closest('table');
    var tbody = table.find('#data_tabel_listview');
    var th = $(this);
    var columnIndex = parseInt(th.data('column'));
    
    // ดึงทิศทางการเรียงลำดับปัจุบัน (สลับ ASC / DESC)
    var isAscending = !th.hasClass('sort-desc');
    table.find('th.sortable-header').removeClass('sort-asc sort-desc');
    
    if (isAscending) {
        th.addClass('sort-desc');
    } else {
        th.addClass('sort-asc');
    }

    // มัดก้อนข้อมูล: รวมกลุ่มแถวคนไข้หลัก (main) และแถวแบ่งจ่ายย่อย (sub) ที่อยู่ติดกันไว้ด้วยกัน
    var rowsGroup = [];
    var currentGroup = null;

    tbody.find('tr').each(function() {
        var tr = $(this);
        if (tr.hasClass('summary-total-row') || tr.closest('tfoot').length > 0 || (tr.is(':last-child') && (tr.find('#o0, #i0').length > 0 || tr.find('td:first').text().indexOf('รวม') > -1))) return;
        if (tr.hasClass('main-patient-row') || (!tr.hasClass('main-patient-row') && !tr.hasClass('sub-payment-row'))) {
            // ถ้าเจอแถวหลักคนไข้ใหม่ ให้สร้างกลุ่มใหม่
            if (currentGroup) {
                rowsGroup.push(currentGroup);
            }
            currentGroup = {
                mainRow: tr,
                subRows: [],
                // ดึงค่าข้อความในคอลัมน์ที่เลือกเพื่อนำไปเปรียบเทียบคำนวณ
                sortValue: tr.find('td').eq(columnIndex).text().trim()
            };
        } else if (tr.hasClass('sub-payment-row') && currentGroup) {
            // ถ้าเป็นแถวแบ่งจ่ายย่อย ให้จับยัดเข้ากลุ่มของแถวหลักปัจจุบัน
            currentGroup.subRows.push(tr);
        }
    });
    // เก็บกลุ่มสุดท้ายเข้าคลัง
    if (currentGroup) rowsGroup.push(currentGroup);

    // เริ่มต้นกระบวนการ Sorting เรียงลำดับข้อมูลใน Array
    rowsGroup.sort(function(a, b) {
        // คลีนตัวเลข (เอาลูกน้ำ, ช่องว่าง และคำว่า " วัน" ออกเพื่อให้แปลงค่าคำนวณได้เที่ยงตรง)
        var valA = a.sortValue.replace(/,/g, '').replace(/วัน/g, '').trim();
        var valB = b.sortValue.replace(/,/g, '').replace(/วัน/g, '').trim();

        // ตรวจสอบว่าเป็นตัวเลขหรือไม่ ถ้าใช่ให้เทียบแบบตัวเลข ถ้าไม่ใช่ให้เทียบแบบสตริงข้อความ
        if (!isNaN(valA) && !isNaN(valB) && valA !== '' && valB !== '') {
            return isAscending ? parseFloat(valB) - parseFloat(valA) : parseFloat(valA) - parseFloat(valB);
        } else {
            return isAscending ? valB.localeCompare(valA, 'th') : valA.localeCompare(valB, 'th');
        }
    });

    // สั่งวาดแถวลงตารางใหม่ (Re-render DOM) ตามลำดับที่เรียงเสร็จแล้ว
    var summaryRow = tbody.find('tr.summary-total-row');
    tbody.empty(); // เคลียร์ตารางเดิม
    
    $.each(rowsGroup, function(index, group) {
        // แก้ไขเลขคอลัมน์ "ที่" (ลำดับ) แถวหลักให้รันใหม่จาก 1 ไปหาช่องสุดท้ายให้สวยงามถูกต้อง
        group.mainRow.find('td').eq(1).text(index + 1);
        
        tbody.append(group.mainRow); // วางแถวแม่
        $.each(group.subRows, function(i, subRow) {
            tbody.append(subRow); // วางแถวลูก (งวดแบ่งจ่าย) ตามลงมาติดๆ ไม่ให้หลุดกลุ่ม
        });
    });

    if (summaryRow.length > 0) {
        tbody.append(summaryRow); // นำแถวสรุปยอดรวมกลับมาต่อท้ายตารางเสมอ
    }
    
    // ปลดบล็อก CheckAll เผื่อผู้ใช้สับสนหลังจากเรียงแถวใหม่
    $("#checkAll").prop('checked', false);
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

  <!-- 🌟 Modal แสดงรายละเอียดรายการ CR ของคนไข้รายตัว -->
  <?php include_once './includes/modal_cr_patient_breakdown.php'; ?>
  <!-- 🌟 Modal แสดงรายละเอียดรายการ SSS ของคนไข้รายตัว -->
  <?php include_once './includes/modal_sss_patient_breakdown.php'; ?>

  </body>
</html>
