<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
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
  color: #fff;
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
#myModalget.modal,
#myModalgetckd.modal {
  padding: 0 !important;
  overflow: hidden !important;
  height: 100vh !important;
  width: 100vw !important;
}

#myModalget .modal-dialog,
#myModalget .modal-dialog.modal-fullscreen,
#myModalgetckd .modal-dialog,
#myModalgetckd .modal-dialog.modal-fullscreen {
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

#myModalget .modal-content,
#myModalgetckd .modal-content {
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

#myModalget .modal-header-luxury,
#myModalgetckd .modal-header-luxury {
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

.modal-header-luxury .brand-badge.badge-ckd {
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35);
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
  gap: 6px;
  padding: 8px 16px;
  border-radius: 9px;
  font-size: 13.5px;
  font-weight: 600;
  color: #ffffff !important;
  border: none;
  text-decoration: none;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  cursor: pointer;
}
.header-action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  color: #ffffff !important;
}
.btn-receipt-gradient {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
}
.btn-print-gradient {
  background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
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

#myModalget .modal-body,
#myModalgetckd .modal-body {
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
  grid-template-columns: repeat(4, 1fr);
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
.kpi-card.kpi-debit::before { background: #0027e5; }
.kpi-card.kpi-stm::before { background: #34b300; }
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
.kpi-debit .kpi-icon-box { background: #eff6ff; color: #0027e5; }
.kpi-stm .kpi-icon-box { background: #ecfdf5; color: #34b300; }
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

.search-input-group input.modal-search-input {
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

.search-input-group input.modal-search-input:focus {
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
#myModalget #data_tabel_stmvlookup,
#myModalgetckd #data_tabel_ckdvlookup,
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

#myModalget #data_tabel_stmvlookup::-webkit-scrollbar,
#myModalgetckd #data_tabel_ckdvlookup::-webkit-scrollbar,
.table-scroll-modal::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
#myModalget #data_tabel_stmvlookup::-webkit-scrollbar-track,
#myModalgetckd #data_tabel_ckdvlookup::-webkit-scrollbar-track,
.table-scroll-modal::-webkit-scrollbar-track {
  background: #f1f5f9;
}
#myModalget #data_tabel_stmvlookup::-webkit-scrollbar-thumb,
#myModalgetckd #data_tabel_ckdvlookup::-webkit-scrollbar-thumb,
.table-scroll-modal::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
#myModalget #data_tabel_stmvlookup::-webkit-scrollbar-thumb:hover,
#myModalgetckd #data_tabel_ckdvlookup::-webkit-scrollbar-thumb:hover,
.table-scroll-modal::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

#myModalget #data_tabel_stmvlookup table,
#myModalgetckd #data_tabel_ckdvlookup table,
.table-scroll-modal table,
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

#myModalget #data_tabel_stmvlookup thead th,
.table-scroll-modal thead th,
.stmgetovlookup thead th {
  position: sticky !important;
  top: 0px !important;
  background: linear-gradient(268deg, #2a3d5d 0%, #2a3d5d 100%) !important;
  color: #ffffff !important;
  z-index: 20 !important;
  padding: 11px 16px !important;
  font-weight: 600;
  font-size: 13.5px;
  border: none !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
  white-space: nowrap !important;
  text-align: center !important;
}

/* DCKD Modal Header: #115e59 */
#myModalgetckd #data_tabel_ckdvlookup thead th {
  position: sticky !important;
  top: 0px !important;
  background: #115e59 !important;
  background: linear-gradient(268deg, #115e59 0%, #115e59 100%) !important;
  color: #ffffff !important;
  z-index: 20 !important;
  padding: 11px 16px !important;
  font-weight: 600;
  font-size: 13.5px;
  border: none !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
  white-space: nowrap !important;
  text-align: center !important;
}

#myModalget #data_tabel_stmvlookup th.sortable-header,
.table-scroll-modal th.sortable-header,
.stmgetovlookup th.sortable-header {
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}
#myModalget #data_tabel_stmvlookup th.sortable-header:hover,
.table-scroll-modal th.sortable-header:hover,
.stmgetovlookup th.sortable-header:hover {
  background: linear-gradient(268deg, #2a3d5d 0%, #2a3d5d 100%) !important;
  color: #93c5fd !important;
}

#myModalgetckd #data_tabel_ckdvlookup th.sortable-header {
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}
#myModalgetckd #data_tabel_ckdvlookup th.sortable-header:hover {
  background: linear-gradient(268deg, #115e59 0%, #115e59 100%) !important;
  color: #99f6e4 !important;
}
#myModalget #data_tabel_stmvlookup th.sortable-header::after,
#myModalgetckd #data_tabel_ckdvlookup th.sortable-header::after,
.table-scroll-modal th.sortable-header::after,
.stmgetovlookup th.sortable-header::after {
  content: ' ⇅';
  font-size: 11px;
  opacity: 0.4;
  margin-left: 4px;
}
#myModalget #data_tabel_stmvlookup th.sortable-header.sort-asc::after,
#myModalgetckd #data_tabel_ckdvlookup th.sortable-header.sort-asc::after,
.table-scroll-modal th.sortable-header.sort-asc::after,
.stmgetovlookup th.sortable-header.sort-asc::after {
  content: ' ▲';
  opacity: 1;
  color: #93c5fd;
}
#myModalget #data_tabel_stmvlookup th.sortable-header.sort-desc::after,
#myModalgetckd #data_tabel_ckdvlookup th.sortable-header.sort-desc::after,
.table-scroll-modal th.sortable-header.sort-desc::after,
.stmgetovlookup th.sortable-header.sort-desc::after {
  content: ' ▼';
  opacity: 1;
  color: #93c5fd;
}

#myModalget #data_tabel_stmvlookup tbody tr,
#myModalgetckd #data_tabel_ckdvlookup tbody tr,
.table-scroll-modal tbody tr,
.stmgetovlookup tbody tr {
  transition: background-color 0.15s ease;
  height: 1px !important;
}

#myModalget #data_tabel_stmvlookup tbody tr.spacer-row,
#myModalgetckd #data_tabel_ckdvlookup tbody tr.spacer-row,
.table-scroll-modal tbody tr.spacer-row,
.stmgetovlookup tbody tr.spacer-row {
  height: auto !important;
  background: transparent !important;
}

#myModalget #data_tabel_stmvlookup tbody tr.spacer-row td,
#myModalgetckd #data_tabel_ckdvlookup tbody tr.spacer-row td,
.table-scroll-modal tbody tr.spacer-row td,
.stmgetovlookup tbody tr.spacer-row td {
  border-top: none !important;
  border-bottom: none !important;
  border-right: 1px solid #f1f5f9 !important;
  padding: 0 !important;
  background: transparent !important;
  height: auto !important;
  pointer-events: none;
}

#myModalget #data_tabel_stmvlookup tbody tr.spacer-row td:first-child,
#myModalgetckd #data_tabel_ckdvlookup tbody tr.spacer-row td:first-child,
.table-scroll-modal tbody tr.spacer-row td:first-child,
.stmgetovlookup tbody tr.spacer-row td:first-child {
  border-left: 1px solid #f1f5f9 !important;
}

#myModalget #data_tabel_stmvlookup tbody tr:not(.spacer-row):hover,
#myModalgetckd #data_tabel_ckdvlookup tbody tr:not(.spacer-row):hover,
.table-scroll-modal tbody tr:not(.spacer-row):hover,
.stmgetovlookup tbody tr:not(.spacer-row):hover {
  background-color: #f0f7ff !important;
}

#myModalget #data_tabel_stmvlookup tbody td,
#myModalgetckd #data_tabel_ckdvlookup tbody td,
.table-scroll-modal tbody td,
.stmgetovlookup tbody td {
  padding: 9px 14px !important;
  vertical-align: middle !important;
  border-bottom: 1px solid #f1f5f9 !important;
  border-right: 1px solid #f1f5f9 !important;
  color: #1e293b;
  font-variant-numeric: tabular-nums;
  white-space: nowrap !important;
}

/* Footer summary sticky row: STM (#myModalget) */
#myModalget #data_tabel_stmvlookup tfoot,
.table-scroll-modal tfoot,
.stmgetovlookup tfoot {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 25 !important;
  background: #2a3d5d !important;
}

#myModalget #data_tabel_stmvlookup tfoot tr,
#myModalget #data_tabel_stmvlookup tfoot tr.summary-total-row,
.table-scroll-modal tfoot tr,
.stmgetovlookup tfoot tr {
  height: 45px !important;
  background: #2a3d5d !important;
  color: #ffffff !important;
}

#myModalget #data_tabel_stmvlookup tfoot td,
#myModalget #data_tabel_stmvlookup tfoot tr td,
.table-scroll-modal tfoot td,
.stmgetovlookup tfoot td {
  background: #2a3d5d !important;
  background: linear-gradient(268deg, #2a3d5d 0%, #1e2b4b 100%) !important;
  color: #ffffff !important;
  border-top: 2px solid #3b82f6 !important;
  border-bottom: none !important;
  border-right: 1px solid rgba(255, 255, 255, 0.12) !important;
  padding: 10px 14px !important;
  font-size: 14px !important;
  font-weight: 700 !important;
  height: 45px !important;
  vertical-align: middle !important;
  white-space: nowrap !important;
  box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.3) !important;
}

/* Footer summary sticky row: DCKD (#myModalgetckd) -> #115e59 */
#myModalgetckd #data_tabel_ckdvlookup tfoot {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 25 !important;
  background: #115e59 !important;
}

#myModalgetckd #data_tabel_ckdvlookup tfoot tr,
#myModalgetckd #data_tabel_ckdvlookup tfoot tr.summary-total-row {
  height: 45px !important;
  background: #115e59 !important;
  color: #ffffff !important;
}

#myModalgetckd #data_tabel_ckdvlookup tfoot td,
#myModalgetckd #data_tabel_ckdvlookup tfoot tr td {
  background: #115e59 !important;
  background: linear-gradient(268deg, #134e4a 0%, #115e59 100%) !important;
  color: #ffffff !important;
  border-top: 2px solid #2dd4bf !important;
  border-bottom: none !important;
  border-right: 1px solid rgba(255, 255, 255, 0.15) !important;
  padding: 10px 14px !important;
  font-size: 14px !important;
  font-weight: 700 !important;
  height: 45px !important;
  vertical-align: middle !important;
  white-space: nowrap !important;
  box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.3) !important;
}

#myModalget #data_tabel_stmvlookup tfoot td:first-child,
#myModalgetckd #data_tabel_ckdvlookup tfoot td:first-child {
  color: #ffffff !important;
  font-size: 14px !important;
  letter-spacing: 0.5px;
}

#myModalget #data_tabel_stmvlookup tfoot td *,
#myModalgetckd #data_tabel_ckdvlookup tfoot td *,
.table-scroll-modal tfoot td *,
.stmgetovlookup tfoot td * {
  color: #ffffff !important;
}

#myModalget #data_tabel_stmvlookup tfoot td#o1 {
  color: #93c5fd !important;
}
#myModalget #data_tabel_stmvlookup tfoot td#o2 {
  color: #86efac !important;
}
#myModalget #data_tabel_stmvlookup tfoot td#o3 {
  color: #fdba74 !important;
}

#myModalgetckd #data_tabel_ckdvlookup tfoot td#o1 {
  color: #99f6e4 !important; /* ฟ้าอมเขียวสว่าง */
}
#myModalgetckd #data_tabel_ckdvlookup tfoot td#o2 {
  color: #86efac !important; /* เขียวสว่าง */
}
#myModalgetckd #data_tabel_ckdvlookup tfoot td#o3 {
  color: #fde047 !important; /* เหลืองสว่าง */
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

/* Modal แนบใบเสร็จ Modern Design */
.receipt-modal-content {
  border: none !important;
  border-radius: 16px !important;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0,0,0,0.25);
  background: #ffffff;
}
.receipt-modal-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  color: #ffffff;
  padding: 16px 22px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.receipt-modal-header .modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 8px;
}
.receipt-modal-body {
  padding: 24px;
}
.receipt-form-group {
  margin-bottom: 16px;
}
.receipt-form-group label {
  font-weight: 600;
  font-size: 13.5px;
  color: #334155;
  margin-bottom: 6px;
  display: block;
}
.receipt-form-group .form-control {
  border-radius: 8px;
  border: 1.5px solid #cbd5e1;
  padding: 8px 14px;
  font-size: 14px;
}
.receipt-form-group .form-control:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* =========================================================================
   🏢 Main Page Executive Healthcare / Technology Design System
   ========================================================================= */

.luxury-main-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e1b4b 100%);
  border-radius: 16px;
  padding: 22px 28px;
  margin-bottom: 24px;
  color: #ffffff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  overflow: hidden;
}

.luxury-main-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 350px;
  height: 350px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%);
  pointer-events: none;
}

.luxury-main-header .header-brand-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(139, 92, 246, 0.3) 100%);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  color: #a5b4fc;
}

.luxury-main-header .header-title-text h3 {
  color: #ffffff;
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 4px;
  letter-spacing: -0.2px;
}

.luxury-main-header .header-title-text p {
  color: #94a3b8;
  font-size: 13.5px;
  margin-bottom: 0;
}

.luxury-data-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
  padding: 24px 28px;
  margin-bottom: 28px;
  transition: all 0.25s ease;
}

.luxury-data-card:hover {
  box-shadow: 0 10px 30px -4px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
  border-color: #cbd5e1;
}

.luxury-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 18px;
  padding-bottom: 18px;
  border-bottom: 1px solid #f1f5f9;
}

.card-title-group {
  display: flex;
  align-items: center;
  gap: 14px;
}

.card-brand-badge {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #ffffff;
  flex-shrink: 0;
}

.card-brand-badge.badge-stm {
  background: linear-gradient(135deg, #0d9488 0%, #115e59 100%);
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
}

.card-brand-badge.badge-ckd {
  background: linear-gradient(135deg, #0d9488 0%, #115e59 100%);
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
}

.card-title-text h4 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 3px;
  letter-spacing: -0.2px;
}

.card-subtitle-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: #f1f5f9;
  color: #475569;
}

.card-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.main-search-input-group {
  position: relative;
  min-width: 280px;
}

.main-search-input-group .search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 18px;
  pointer-events: none;
}

.main-search-input {
  width: 100%;
  height: 40px;
  padding: 8px 36px 8px 38px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  font-size: 13.5px;
  color: #1e293b;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.main-search-input:focus {
  border-color: #6366f1;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
  outline: none;
}

.main-search-clear {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  font-size: 16px;
  display: none;
}

.main-search-clear:hover {
  color: #ef4444;
}

.main-filter-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
}

.btn-main-action {
  height: 40px;
  padding: 8px 16px;
  border-radius: 9px;
  font-size: 13.5px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-main-refresh {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #475569;
}
.btn-main-refresh:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}

.btn-main-export {
  background: linear-gradient(135deg, #0d9488 0%, #115e59 100%);
  color: #ffffff !important;
  box-shadow: 0 2px 6px rgba(5, 150, 105, 0.2);
}
.btn-main-export:hover {
  background: linear-gradient(135deg, #0d9488 0%, #115e59 100%);
  color: #ffffff !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

/* Main Table Container & Scroll System */
.table-scroll-main {
  height: 520px;
  max-height: 520px;
  overflow-y: auto !important;
  overflow-x: auto !important;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  position: relative;
  box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

.table-scroll-main::-webkit-scrollbar {
  width: 7px;
  height: 7px;
}
.table-scroll-main::-webkit-scrollbar-track {
  background: #f8fafc;
}
.table-scroll-main::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.table-scroll-main::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Styling for AJAX loaded table inside #data_tabel_stm and #data_tabel_ckd */
#data_tabel_stm table,
#data_tabel_ckd table {
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
  margin-bottom: 0 !important;
  font-size: 13.5px;
}

#data_tabel_stm table thead th {
  position: sticky !important;
  top: 0px !important;
  z-index: 10 !important;
  background: #115e59 !important;
  color: #ffffff !important;
  padding: 12px 16px !important;
  font-weight: 600 !important;
  font-size: 13px !important;
  text-align: center !important;
  border: none !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
  white-space: nowrap !important;
  letter-spacing: 0.3px;
}

/* DCKD Main Page Header: #115e59 */
#data_tabel_ckd table thead th {
  position: sticky !important;
  top: 0px !important;
  z-index: 10 !important;
  background: #508b70 !important;
  color: #ffffff !important;
  padding: 12px 16px !important;
  font-weight: 600 !important;
  font-size: 13px !important;
  text-align: center !important;
  border: none !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
  white-space: nowrap !important;
  letter-spacing: 0.3px;
}

#data_tabel_stm table tbody tr,
#data_tabel_ckd table tbody tr {
  transition: background-color 0.15s ease;
}

#data_tabel_stm table tbody tr:hover,
#data_tabel_ckd table tbody tr:hover {
  background-color: #f8fafc !important;
}

#data_tabel_stm table tbody td,
#data_tabel_ckd table tbody td {
  padding: 10px 16px !important;
  vertical-align: middle !important;
  border-bottom: 1px solid #f1f5f9 !important;
  border-right: 1px solid #f8fafc !important;
  color: #334155;
  font-variant-numeric: tabular-nums;
}

/* Links inside tables (REP links) */
#data_tabel_stm table tbody td a,
#data_tabel_ckd table tbody td a {
  color: #4f46e5 !important;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: 6px;
  background: #eef2ff;
  border: 1px solid #e0e7ff;
  transition: all 0.15s ease;
}

#data_tabel_stm table tbody td a:hover,
#data_tabel_ckd table tbody td a:hover {
  background: #4f46e5;
  color: #ffffff !important;
  border-color: #4f46e5;
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
}

/* Style for rows with green/attached receipt status */
#data_tabel_stm table tbody td[style*="color: green"],
#data_tabel_ckd table tbody td[style*="color: green"],
#data_tabel_stm table tbody td a[style*="color: green"],
#data_tabel_ckd table tbody td a[style*="color: green"] {
  color: #059669 !important;
  font-weight: 600;
}

#data_tabel_stm table tbody td a[style*="color: green"],
#data_tabel_ckd table tbody td a[style*="color: green"] {
  background: #ecfdf5 !important;
  border-color: #a7f3d0 !important;
}

#data_tabel_stm table tbody td a[style*="color: green"]:hover,
#data_tabel_ckd table tbody td a[style*="color: green"]:hover {
  background: #059669 !important;
  color: #ffffff !important;
  border-color: #059669 !important;
}

/* Section Header in DCKD Table: #115e59 Accent */
#data_tabel_ckd table tbody tr td[colspan="4"] {
  background: linear-gradient(90deg, #f0fdfa 0%, #ccfbf1 100%) !important;
  color: #115e59 !important;
  font-weight: 700 !important;
  font-size: 13.5px !important;
  padding: 11px 18px !important;
  border-left: 4px solid #115e59 !important;
  letter-spacing: 0.4px;
}

/* Sticky Total Summary Row on Main Page: STM */
#data_tabel_stm table tbody tr[style*="background: #cecece"] {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 10 !important;
  background: #0f172a !important;
}

#data_tabel_stm table tbody tr[style*="background: #cecece"] td {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 10 !important;
  background: #115e59 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  border-top: 2px solid #115e59  !important;
  padding: 12px 16px !important;
  font-size: 14.5px !important;
  box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.25) !important;
}

#data_tabel_stm table tbody tr[style*="background: #cecece"] td * {
  color: #ffffff !important;
}

/* Sticky Total Summary Row on Main Page: DCKD -> #115e59 */
#data_tabel_ckd table tbody tr:last-child,
#data_tabel_ckd table tbody tr[style*="background: #cecece"] {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 10 !important;
  background: #115e59 !important;
}

#data_tabel_ckd table tbody tr:last-child td,
#data_tabel_ckd table tbody tr[style*="background: #cecece"] td {
  position: sticky !important;
  bottom: 0px !important;
  z-index: 10 !important;
  background: #508b70 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  border-top: 2px solid #2dd4bf !important;
  padding: 12px 16px !important;
  font-size: 14.5px !important;
  box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.25) !important;
}

#data_tabel_ckd table tbody tr:last-child td *,
#data_tabel_ckd table tbody tr[style*="background: #cecece"] td * {
  color: #ffffff !important;
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



          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <!-- Card 1: ตัดลูกหนี้ตามยอด Statement REP NO -->
            <div class="container-fluid">
              <div class="row">
                <div class="col-12">
                  <div class="luxury-data-card">
                    
                    <div class="luxury-card-header">
                      <div class="card-title-group">
                        <div class="card-brand-badge badge-stm">
                          <i class='bx bx-file-find'></i>
                        </div>
                        <div class="card-title-text">
                          <h4>ตัดลูกหนี้ตามยอด Statement REP NO</h4>
                          <span class="card-subtitle-pill">
                            <i class='bx bx-check-circle' style="color: #0d9488;"></i> Statement Reconciliation Data Grid
                          </span>
                        </div>
                      </div>

                      <div class="card-header-actions">
                        <div class="main-search-input-group">
                          <i class='bx bx-search search-icon'></i>
                          <input type="text" id="myInput" class="main-search-input" placeholder="ค้นหา Statement REP NO, เลขที่ใบเสร็จ, สิทธิ..." autocomplete="off">
                          <button type="button" class="main-search-clear" id="clearSearchStmBtn" onclick="$('#myInput').val('').trigger('keyup').focus();">
                            <i class='bx bx-x'></i>
                          </button>
                        </div>

                        <div class="main-filter-badge" id="badge_stm_counter">
                          <i class='bx bx-list-ul' style="color: #0d9488;"></i>
                          <span id="txt_stm_count">กำลังโหลดข้อมูล...</span>
                        </div>

                        <button type="button" class="btn-main-action btn-main-export" id="exportMainStmBtn" title="ส่งออกตาราง Statement เป็น Excel">
                          <i class='bx bxs-file-export'></i> ส่งออก Excel
                        </button>

                        <button type="button" class="btn-main-action btn-main-refresh" onclick="fetch_data_stm()" title="รีเฟรชข้อมูล Statement">
                          <i class='bx bx-refresh'></i>
                        </button>
                      </div>
                    </div>

                    <!-- Table Scroll Container -->
                    <div id="data_tabel_stm" class="table-scroll-main">
                      <div class="p-5 text-center text-muted">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div>กำลังโหลดข้อมูล Statement REP NO...</div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Card 2: ตัดลูกหนี้ตามยอด Statement ผู้ป่วยไต DCKD -->
            <div class="container-fluid">
              <div class="row">
                <div class="col-12">
                  <div class="luxury-data-card">

                    <div class="luxury-card-header">
                      <div class="card-title-group">
                        <div class="card-brand-badge badge-ckd">
                          <i class='bx bx-pulse'></i>
                        </div>
                        <div class="card-title-text">
                          <h4>ตัดลูกหนี้ตามยอด Statement ผู้ป่วยไต DCKD</h4>
                          <span class="card-subtitle-pill">
                            <i class='bx bx-pulse text-teal' style="color: #0d9488;"></i> DCKD Dialysis Matching Engine
                          </span>
                        </div>
                      </div>

                      <div class="card-header-actions">
                        <div class="main-search-input-group">
                          <i class='bx bx-search search-icon'></i>
                          <input id="myInputCkd" type="text" class="main-search-input" placeholder="ค้นหา กองทุน, เลขหนังสือ, เลขที่ใบเสร็จ..." autocomplete="off">
                          <button type="button" class="main-search-clear" id="clearSearchCkdBtn" onclick="$('#myInputCkd').val('').trigger('keyup').focus();">
                            <i class='bx bx-x'></i>
                          </button>
                        </div>

                        <div class="main-filter-badge" id="badge_ckd_counter">
                          <i class='bx bx-list-ul text-teal' style="color: #0d9488;"></i>
                          <span id="txt_ckd_count">กำลังโหลดข้อมูล...</span>
                        </div>

                        <button type="button" class="btn-main-action btn-main-export" id="exportMainCkdBtn" style="background: linear-gradient(135deg, #0d9488 0%, #115e59 100%);" title="ส่งออกตาราง DCKD เป็น Excel">
                          <i class='bx bxs-file-export'></i> ส่งออก Excel
                        </button>

                        <button type="button" class="btn-main-action btn-main-refresh" onclick="fetch_data_ckd()" title="รีเฟรชข้อมูล DCKD">
                          <i class='bx bx-refresh'></i>
                        </button>
                      </div>
                    </div>

                    <!-- Table Scroll Container -->
                    <div id="data_tabel_ckd" class="table-scroll-main">
                      <div class="p-5 text-center text-muted">
                        <div class="spinner-border text-teal mb-2" style="color: #0d9488;" role="status"></div>
                        <div>กำลังโหลดข้อมูลผู้ป่วยไต DCKD...</div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>


    <!-- =========================================================================
         🏢 The Modal: ตัดลูกหนี้ตามยอด Statement REP NO (Enterprise Edition)
         ========================================================================= -->
    <div class="modal fade" id="myModalget" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">

          <!-- Modal Header -->
          <div class="modal-header-luxury">
            <div class="d-flex align-items-center gap-3">
              <div class="brand-badge">
                <i class='bx bx-file-find'></i>
              </div>
              <div class="title-group">
                <h5 id="modal_dept_heading">ตัดลูกหนี้ตามยอด Statement REP NO</h5>
                <div class="subtitle-pill" id="titlestm">
                  📑 รายการลูกหนี้ตามยอด Statement
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button type="button" class="header-action-btn btn-receipt-gradient" onclick="fetch_แนบใบเสร็จ()">
                <i class='bx bx-receipt'></i> แนบใบเสร็จ
              </button>
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
                <input id="myInputModal" type="text" class="modal-search-input" placeholder="ค้นหาด้วย ชื่อ-สกุล, CID, VN/AN, เลขหนังสือ/บิล..." autocomplete="off">
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
                <a href="#" id="Debtor_rights" target="_blank" class="footer-btn btn-print-gradient" style="height: 38px; padding: 6px 16px; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; color: #ffffff">
                  <i class='bx bx-printer'></i> พิมพ์ลูกหนี้ชดเชย
                </a>
              </div>
            </div>

            <!-- Data Table Container -->
            <div id="data_tabel_stmvlookup" class="table-scroll-modal">

            </div>
          </div>

          <!-- Modal footer -->
          <div class="modal-footer-luxury">
            <div class="d-flex align-items-center gap-2 text-muted small">
              <span><i class='bx bx-info-circle text-primary me-1'></i> ข้อมูลลูกหนี้ตามยอด Statement REP NO</span>
            </div>
          </div>

        </div>
      </div>
    </div>


    <!-- =========================================================================
         🏢 The Modal: ระบุเลขที่ใบเสร็จ (Statement REP)
         ========================================================================= -->
    <div class="modal fade" id="myModalแนนใบ" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content receipt-modal-content">       
          <div class="receipt-modal-header">
            <div class="modal-title">
              <i class='bx bx-receipt text-warning'></i> ระบุเลขที่ใบเสร็จ (Statement REP)
            </div>
            <button type="button" class="btn-close-luxury" data-bs-dismiss="modal" title="ปิดหน้าต่าง">
              <i class='bx bx-x'></i>
            </button>
          </div>

          <div class="receipt-modal-body">
            <form action="datatimestamp-insert2.php" method="POST" enctype="multipart/form-data" id="myForm">
              <div class="receipt-form-group">
                <label for="reptact"><i class='bx bx-file text-primary me-1'></i> REP (เลขหนังสือ)</label>
                <input type="text" id="reptact" class="form-control" name="reptact" placeholder="REP..." readonly style="background-color: #f1f5f9;">
              </div>

              <div class="receipt-form-group" id="div_old_payment" style="display:none;">
                <label for="old_payment" style="color: #dc2626; font-weight: bold;">
                  <i class='bx bx-edit text-danger me-1'></i> ต้องการแก้ไขใบเสร็จเดิม (เลือกรายการ)
                </label>
                <select class="form-control" id="old_payment" name="old_payment">
                </select>
                <small class="text-muted">* หากเป็นการบันทึกครั้งแรก ไม่ต้องเลือกช่องนี้</small>
              </div>

              <div class="receipt-form-group">
                <label for="payment" style="color: #059669; font-weight: bold;">
                  <i class='bx bx-barcode text-success me-1'></i> เลขที่ใบเสร็จ
                </label>
                <div style="display: flex; gap: 8px; align-items: center;">
                  <input type="text"
                         class="form-control text-center fw-bold"
                         name="payment_front"
                         placeholder="0000"
                         maxlength="4"
                         style="width: 110px;"
                         required tabindex="1">
                  <span class="fw-bold fs-5 text-muted">/</span>
                  <input type="text"
                         class="form-control text-center fw-bold"
                         name="payment_back"
                         placeholder="0000"
                         maxlength="4"
                         style="width: 110px;"
                         required tabindex="2"> 
                </div>
                <!-- ค่าจริงที่ส่ง -->
                <input type="hidden" name="payment">
              </div>

              <div class="receipt-form-group">
                <label for="paymentdate"><i class='bx bx-calendar text-primary me-1'></i> วันที่ใบเสร็จ</label>
                <input type="text" 
                       class="form-control" 
                       name="paymentdate" 
                       placeholder="วว/ดด/ปปปป"
                       pattern="\d{2}/\d{2}/\d{4}"
                       maxlength="10"
                       title="กรุณาระบุในรูปแบบ 01/01/2569"
                       required tabindex="3">
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                  <i class='bx bx-x'></i> ปิด
                </button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                  <i class='bx bx-save me-1'></i> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>


    <!-- =========================================================================
         🏢 The Modal: ตัดลูกหนี้ตามยอด Statement ผู้ป่วยไต DCKD (Enterprise Edition)
         ========================================================================= -->
    <div class="modal fade" id="myModalgetckd" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">

          <!-- Modal Header -->
          <div class="modal-header-luxury">
            <div class="d-flex align-items-center gap-3">
              <div class="brand-badge badge-ckd">
                <i class='bx bx-pulse'></i>
              </div>
              <div class="title-group">
                <h5 id="modal_dept_heading_ckd">ตัดลูกหนี้ตามยอด Statement ผู้ป่วยไต DCKD</h5>
                <div class="subtitle-pill" id="titlestmckd">
                  📑 รายการลูกหนี้ฟอกไต DCKD
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button type="button" class="header-action-btn btn-receipt-gradient" onclick="fetch_แนบใบเสร็จckd()">
                <i class='bx bx-receipt'></i> แนบใบเสร็จ
              </button>
              <button type="button" class="btn-close-luxury ms-2" data-bs-dismiss="modal" onclick="$('#myInputModalCkd').val('').trigger('keyup');" title="ปิดหน้าต่าง">
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
                  <div class="kpi-value" id="txtq0c">0 คน</div>
                </div>
              </div>

              <div class="kpi-card kpi-debit">
                <div class="kpi-icon-box">
                  <i class='bx bx-coin-stack'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ภาระหนี้รวม (Debit)</div>
                  <div class="kpi-value" id="txtq1c">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-stm">
                <div class="kpi-icon-box">
                  <i class='bx bx-check-shield'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ชดเชย DCKD รวม</div>
                  <div class="kpi-value" id="txtq2c">0.00</div>
                </div>
              </div>

              <div class="kpi-card kpi-diff">
                <div class="kpi-icon-box">
                  <i class='bx bx-error-alt'></i>
                </div>
                <div class="kpi-info">
                  <div class="kpi-label">ส่วนต่างคงเหลือ</div>
                  <div class="kpi-value" id="txtq3c">0.00</div>
                </div>
              </div>
            </div>

            <!-- Search & Filter Toolbar -->
            <div class="modal-search-toolbar">
              <div class="search-input-group">
                <i class='bx bx-search search-icon'></i>
                <input id="myInputModalCkd" type="text" class="modal-search-input" placeholder="ค้นหาด้วย ชื่อ-สกุล, HN, VN, เลขหนังสือ/บิล..." autocomplete="off">
                <button type="button" class="search-clear-btn" id="clearSearchModalCkdBtn" onclick="$('#myInputModalCkd').val('').trigger('keyup').focus();">
                  <i class='bx bx-x'></i>
                </button>
              </div>

              <div class="d-flex align-items-center gap-2">
                <div class="filter-badge-info">
                  <i class='bx bx-list-ul text-primary'></i>
                  <span id="filtered_row_count_ckd">กำลังแสดงทุกรายการ</span>
                </div>
                <button id="exportckdgetovlookup" class="footer-btn btn-export-excel" style="height: 38px; padding: 6px 16px; font-size: 13.5px;">
                  <i class='bx bxs-file-export'></i> ส่งออก Excel (.xlsx)
                </button>
                <a href="#" id="Debtor_rightsckd" target="_blank" class="footer-btn btn-print-gradient" style="height: 38px; padding: 6px 16px; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; color: #ffffff">
                  <i class='bx bx-printer'></i> พิมพ์ลูกหนี้ชดเชย
                </a>
              </div>
            </div>

            <!-- Data Table Container -->
            <div id="data_tabel_ckdvlookup" class="table-scroll-modal">

            </div>
          </div>

          <!-- Modal footer -->
          <div class="modal-footer-luxury">
            <div class="d-flex align-items-center gap-2 text-muted small">
              <span><i class='bx bx-info-circle text-primary me-1'></i> ข้อมูลลูกหนี้ตามยอด Statement ผู้ป่วยไต DCKD</span>
            </div>
          </div>

        </div>
      </div>
    </div>


    <!-- =========================================================================
         🏢 The Modal: ระบุเลขที่ใบเสร็จ (Statement ผู้ป่วยไต DCKD)
         ========================================================================= -->
    <div class="modal fade" id="myModalแนนใบckd" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content receipt-modal-content">       
          <div class="receipt-modal-header" style="background: linear-gradient(135deg, #0f172a 0%, #134e4a 100%);">
            <div class="modal-title">
              <i class='bx bx-receipt text-warning'></i> ระบุเลขที่ใบเสร็จ (ผู้ป่วยไต DCKD)
            </div>
            <button type="button" class="btn-close-luxury" data-bs-dismiss="modal" title="ปิดหน้าต่าง">
              <i class='bx bx-x'></i>
            </button>
          </div>

          <div class="receipt-modal-body">
            <form action="datatimestamp-insert2.php" method="POST" enctype="multipart/form-data" id="myFormckd">
              <div class="receipt-form-group">
                <label for="reptactckd"><i class='bx bx-file text-primary me-1'></i> REP (เลขหนังสือ)</label>
                <input type="text" id="reptactckd" class="form-control" name="reptactckd" placeholder="REP..." readonly style="background-color: #f1f5f9;">
              </div>

              <div class="receipt-form-group" id="div_old_paymentckd" style="display:none;">
                <label for="old_paymentckd" style="color: #dc2626; font-weight: bold;">
                  <i class='bx bx-edit text-danger me-1'></i> ต้องการแก้ไขใบเสร็จเดิม (เลือกรายการ)
                </label>
                <select class="form-control" id="old_paymentckd" name="old_paymentckd">
                </select>
                <small class="text-muted">* หากเป็นการบันทึกครั้งแรก ไม่ต้องเลือกช่องนี้</small>
              </div>

              <div class="receipt-form-group">
                <label for="paymentckd" style="color: #059669; font-weight: bold;">
                  <i class='bx bx-barcode text-success me-1'></i> เลขที่ใบเสร็จ
                </label>
                <div style="display: flex; gap: 8px; align-items: center;">
                  <input type="text"
                         class="form-control text-center fw-bold"
                         name="paymentckd_front"
                         placeholder="0000"
                         maxlength="4"
                         style="width: 110px;"
                         required tabindex="1">
                  <span class="fw-bold fs-5 text-muted">/</span>
                  <input type="text"
                         class="form-control text-center fw-bold"
                         name="paymentckd_back"
                         placeholder="0000"
                         maxlength="4"
                         style="width: 110px;"
                         required tabindex="2"> 
                </div>
                <!-- ค่าจริงที่ส่ง -->
                <input type="hidden" name="paymentckd">
              </div>

              <div class="receipt-form-group">
                <label for="paymentdateckd"><i class='bx bx-calendar text-primary me-1'></i> วันที่</label>
                <input type="text" 
                       class="form-control" 
                       name="paymentdateckd" 
                       placeholder="วว/ดด/ปปปป"
                       pattern="\d{2}/\d{2}/\d{4}"
                       maxlength="10"
                       title="กรุณาระบุในรูปแบบ 01/01/2569"
                       required tabindex="3">
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                  <i class='bx bx-x'></i> ปิด
                </button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                  <i class='bx bx-save me-1'></i> บันทึก
                </button>
              </div>
            </form>
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

    <script>
      $(document).ready(function(){
        var yyy = '<?php echo $yyy; ?>';
        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        if (!isLoggedIn) {
            location.href = './login.php';
        } else {
            fetch_data_stm();
            fetch_data_ckd();
        }

        // ค้นหาตาราง Statement บนหน้าหลัก
        $(document).on("keyup", "#myInput", function() {
          var value = $(this).val().toLowerCase().trim();
          if (value !== '') {
              $('#clearSearchStmBtn').show();
          } else {
              $('#clearSearchStmBtn').hide();
          }

          var visibleCount = 0;
          var totalRows = 0;

          $("#myTablev tr").each(function() {
              var tr = $(this);
              if (tr.is(':last-child') || tr.text().indexOf(' รวม') > -1) return;

              totalRows++;
              var text = tr.text().toLowerCase();
              var matched = text.indexOf(value) > -1;
              tr.toggle(matched);
              if (matched) visibleCount++;
          });

          if (value === '') {
              $('#txt_stm_count').text('ทั้งหมด ' + totalRows + ' รายการ');
          } else {
              $('#txt_stm_count').text('พบ ' + visibleCount + ' จาก ' + totalRows + ' รายการ');
          }
        });

        // ค้นหาตาราง CKD บนหน้าหลัก
        $(document).on("keyup", "#myInputCkd", function() {
          var value = $(this).val().toLowerCase().trim();
          if (value !== '') {
              $('#clearSearchCkdBtn').show();
          } else {
              $('#clearSearchCkdBtn').hide();
          }

          var visibleCount = 0;
          var totalRows = 0;

          $("#data_tabel_ckd tbody tr").each(function() {
              var tr = $(this);
              if (tr.find('td[colspan]').length > 0) return; // skip header/summary rows

              totalRows++;
              var text = tr.text().toLowerCase();
              var matched = text.indexOf(value) > -1;
              tr.toggle(matched);
              if (matched) visibleCount++;
          });

          if (value === '') {
              $('#txt_ckd_count').text('ทั้งหมด ' + totalRows + ' รายการ');
          } else {
              $('#txt_ckd_count').text('พบ ' + visibleCount + ' จาก ' + totalRows + ' รายการ');
          }
        });

        // Export Excel สำหรับตารางหน้าหลัก STM
        $(document).on('click', '#exportMainStmBtn', function() {
            let table = document.querySelector('#data_tabel_stm table');
            if (!table) return;
            let workbook = XLSX.utils.book_new();
            let worksheet = XLSX.utils.table_to_sheet(table, { raw: true });
            XLSX.utils.book_append_sheet(workbook, worksheet, "Statement_REP");
            XLSX.writeFile(workbook, 'Statement_REP_Overview.xlsx');
        });

        // Export Excel สำหรับตารางหน้าหลัก CKD
        $(document).on('click', '#exportMainCkdBtn', function() {
            let table = document.querySelector('#data_tabel_ckd table');
            if (!table) return;
            let workbook = XLSX.utils.book_new();
            let worksheet = XLSX.utils.table_to_sheet(table, { raw: true });
            XLSX.utils.book_append_sheet(workbook, worksheet, "DCKD_Overview");
            XLSX.writeFile(workbook, 'DCKD_Overview.xlsx');
        });

      });

        function fetch_data_stm()
        { 
          var actionstmvlookup = "fetch";
          $('#data_tabel_stm').html('<div class="p-5 text-center text-muted"><div class="spinner-border text-primary mb-2" role="status"></div><div>กำลังโหลดข้อมูล Statement REP NO...</div></div>');
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionstmvlookup:actionstmvlookup},
            success:function(data)
            {
              $('#data_tabel_stm').html(data);
              var totalRows = $('#myTablev tr').not(':last').length;
              $('#txt_stm_count').text('ทั้งหมด ' + totalRows + ' รายการ');
              $('#myInput').val('');
              $('#clearSearchStmBtn').hide();
            },
            error: function() {
              $('#data_tabel_stm').html('<div class="p-4 text-center text-danger"><i class="bx bx-error fs-3 mb-2"></i><div>เกิดข้อผิดพลาดในการโหลดข้อมูล</div></div>');
            }
          });
        }


        function fetch_data_ckd()
        { 
          var actionckdvlookup = "fetch";
          $('#data_tabel_ckd').html('<div class="p-5 text-center text-muted"><div class="spinner-border text-teal mb-2" style="color:#0d9488;" role="status"></div><div>กำลังโหลดข้อมูลผู้ป่วยไต DCKD...</div></div>');
          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{actionckdvlookup:actionckdvlookup},
            success:function(data)
            {
              $('#data_tabel_ckd').html(data);
              var totalRows = $('#data_tabel_ckd tbody tr').not(':last').filter(function() {
                  return $(this).find('td[colspan]').length === 0;
              }).length;
              $('#txt_ckd_count').text('ทั้งหมด ' + totalRows + ' รายการ');
              $('#myInputCkd').val('');
              $('#clearSearchCkdBtn').hide();
            },
            error: function() {
              $('#data_tabel_ckd').html('<div class="p-4 text-center text-danger"><i class="bx bx-error fs-3 mb-2"></i><div>เกิดข้อผิดพลาดในการโหลดข้อมูล</div></div>');
            }
          });
        }


        function stmgeto(data)
        { 
          // แสดง SweetAlert กำลังโหลด
          Swal.fire({
              title: 'กำลังดึงข้อมูล...',
              html: 'รอสักครู่...',
              allowOutsideClick: false,
              scrollbarPadding: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          var stmgetovlookup = data;
          $('#modal_dept_heading').text('ตัดลูกหนี้ตามยอด Statement REP NO');
          $('#titlestm').html('🏷️ เลขที่หนังสือ REP ' + data);
          $('#reptact').val(data);

          $("#Debtor_rights").attr("href", "../report/pdf/Debtor-rights.php?norep="+data);

          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{stmgetovlookup:stmgetovlookup},
            success:function(data)
            {
              Swal.close();
              $('#data_tabel_stmvlookup').html(data);

              // นับจำนวนแถวผู้ป่วย (ไม่รวม spacer-row)
              var rows = $('#data_tabel_stmvlookup tbody tr').not('.spacer-row');
              var rowCount = rows.length;
              $('#txtq0').text(rowCount + ' คน');

              var o1 = $('#data_tabel_stmvlookup tfoot #o1').text().trim();
              $('#txtq1').text(o1 ? o1 : '0.00');

              var o2 = $('#data_tabel_stmvlookup tfoot #o2').text().trim();
              $('#txtq2').text(o2 ? o2 : '0.00');

              var o3 = $('#data_tabel_stmvlookup tfoot #o3').text().trim();
              $('#txtq3').text(o3 ? o3 : '0.00');

              $('#myInputModal').val('');
              $('#clearSearchModalBtn').hide();
              $('#filtered_row_count_stm').text('กำลังแสดงทุกรายการ (' + rowCount + ' รายการ)');

              $('#data_tabel_stmvlookup thead th').each(function(index) {
                  $(this).addClass('sortable-header').attr('data-column', index);
              });

              $('#myModalget').modal('show');
            },
            error: function() {
              Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
          });

        }


        function ckdgeto(data)
        { 
          // แสดง SweetAlert กำลังโหลด
          Swal.fire({
              title: 'กำลังดึงข้อมูล...',
              html: 'รอสักครู่...',
              allowOutsideClick: false,
              scrollbarPadding: false,
              didOpen: () => {
                  Swal.showLoading();
              }
          });

          var ckdgetovlookup = data;

          $('#modal_dept_heading_ckd').text('ตัดลูกหนี้ตามยอด Statement ผู้ป่วยไต DCKD');
          $('#titlestmckd').html('🏷️ เลขที่หนังสือ REP ' + data);
          $('#reptactckd').val(data);
          $("#Debtor_rightsckd").attr("href", "../report/pdf/Debtor-rightsckd.php?norep="+data);

          $.ajax({
            url:"datatimestamp-insert2.php",
            method:"POST",
            data:{ckdgetovlookup:ckdgetovlookup},
            success:function(data)
            {
              Swal.close();
              $('#data_tabel_ckdvlookup').html(data);

              var rows = $('#data_tabel_ckdvlookup tbody tr').not('.spacer-row');
              var rowCount = rows.length;
              $('#txtq0c').text(rowCount + ' คน');

              var o1 = $('#data_tabel_ckdvlookup tfoot #o1').text().trim();
              $('#txtq1c').text(o1 ? o1 : '0.00');

              var o2 = $('#data_tabel_ckdvlookup tfoot #o2').text().trim();
              $('#txtq2c').text(o2 ? o2 : '0.00');

              var o3 = $('#data_tabel_ckdvlookup tfoot #o3').text().trim();
              $('#txtq3c').text(o3 ? o3 : '0.00');

              $('#myInputModalCkd').val('');
              $('#clearSearchModalCkdBtn').hide();
              $('#filtered_row_count_ckd').text('กำลังแสดงทุกรายการ (' + rowCount + ' รายการ)');

              $('#data_tabel_ckdvlookup thead th').each(function(index) {
                  $(this).addClass('sortable-header').attr('data-column', index);
              });

              $('#myModalgetckd').modal('show');
            },
            error: function() {
              Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
          });

        }



        function fetch_แนบใบเสร็จ() { 
            var rep_no = $('#reptact').val();

            if (!rep_no) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการ REP ก่อนครับพี่', 'warning');
                return;
            }

            $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { action_get_old_receipt: rep_no },
                success: function(data) {
                    $('#old_payment').html(data); // เอา Option ใส่เข้าไปก่อน

                    // เช็คจำนวน Option ครับพี่
                    // ถ้ามีมากกว่า 1 ตัว (แปลว่ามีของเก่าให้แก้) -> ให้สั่ง .show()
                    if ($('#old_payment option').length > 1) {
                        $('#div_old_payment').show();
                    } else {
                        // ถ้ามีแค่ 1 ตัว (คือตัวเลือก default) -> ให้ซ่อน .hide() เหมือนเดิม
                        $('#div_old_payment').hide();
                        $('#old_payment').val(""); // เคลียร์ค่าเผื่อไว้
                    }

                    $('#myModalแนนใบ').modal('show');
                }
            });
        }

        function Debtor_rights(stmgetovlookup)
        { 

          $.ajax({
            url:"Debtor-rights.php",
            method:"POST",
            data:{stmgetovlookup:stmgetovlookup},
            success:function(data)
            {
              console.log("");
            }
          })

        }




        // ใน VlookUP.php

        function fetch_แนบใบเสร็จckd() { 
            var rep_no = $('#reptactckd').val();

            if (!rep_no) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการ REP ก่อนครับพี่', 'warning');
                return;
            }

            // เรียก AJAX ไปดึงรายการใบเสร็จเดิมของ CKD
            $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { action_get_old_receipt_ckd: rep_no }, // ส่ง action ใหม่ไป
                success: function(data) {
                    $('#old_paymentckd').html(data); // ใส่ Option

                    // เช็คว่ามีของเก่าไหม
                    if ($('#old_paymentckd option').length > 1) {
                        $('#div_old_paymentckd').show(); // มี -> โชว์
                    } else {
                        $('#div_old_paymentckd').hide(); // ไม่มี -> ซ่อน
                        $('#old_paymentckd').val(""); 
                    }

                    $('#myModalแนนใบckd').modal('show');
                }
            });
        }


        

        function Debtor_rightsckd(ckdgetovlookup)
        { 

          $.ajax({
            url:"Debtor-rightsckd.php",
            method:"POST",
            data:{ckdgetovlookup:ckdgetovlookup},
            success:function(data)
            {
              console.log("");
            }
          })

        }


      $("#search").keyup(function () {
          var value = this.value.toLowerCase().trim();

          $("table tr").each(function (index) {
              if (!index) return;
              $(this).find("td").each(function () {
                  var id = $(this).text().toLowerCase().trim();
                  var not_found = (id.indexOf(value) == -1);
                  $(this).closest('tr').toggle(!not_found);
                  return not_found;
              });
          });
      });






      $('#myForm input').on('keydown', function(e) {
          if (e.key === 'Enter') {
              e.preventDefault();

              let next = $('[tabindex="' + (this.tabIndex + 1) + '"]');

              if (next.length) {
                  next.focus();
              } 
          }
      });

      $('#myModalแนนใบ').on('shown.bs.modal', function () {
          $('input[name="payment_front"]').focus();
      });


      function updatePaymentPreview() {
          let front = $('input[name="payment_front"]').val().replace(/\D/g, '');
          let back  = $('input[name="payment_back"]').val().replace(/\D/g, '');

          // ===== แสดงผล realtime =====
          let showFront = front.padStart(4, '0'); // เติมขั้นต่ำ 4 หลัก
          let showBack  = back.padStart(4, '0');  // หลังต้อง 4 หลัก

          if (front.length === 0) {
              $('input[name="payment"]').val('');
              return;
          }

          // ===== logic ตอนบันทึก =====
          let saveFront = front;

          if (front.startsWith('0')) {
              saveFront = front.slice(-4).padStart(4, '0'); // 
          }

          let payment = saveFront + '/' + showBack;
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






      $('#myFormckd input').on('keydown', function(e) {
          if (e.key === 'Enter') {
              e.preventDefault();

              let next = $('[tabindex="' + (this.tabIndex + 1) + '"]');

              if (next.length) {
                  next.focus();
              } 
          }
      });

      $('#myModalแนนใบckd').on('shown.bs.modal', function () {
          $('input[name="paymentckd_front"]').focus();
      });


      function updatePaymentPreview1() {
          let front = $('input[name="paymentckd_front"]').val().replace(/\D/g, '');
          let back  = $('input[name="paymentckd_back"]').val().replace(/\D/g, '');

          // ===== แสดงผล realtime =====
          let showFront = front.padStart(4, '0'); // เติมขั้นต่ำ 4 หลัก
          let showBack  = back.padStart(4, '0');  // หลังต้อง 4 หลัก

          if (front.length === 0) {
              $('input[name="paymentckd"]').val('');
              return;
          }

          // ===== logic ตอนบันทึก =====
          let saveFront = front;

          if (front.startsWith('0')) {
              saveFront = front.slice(-4).padStart(4, '0'); // 
          }

          let payment = saveFront + '/' + showBack;
          $('input[name="paymentckd"]').val(payment);
      }

      // ฟัง input (ไม่ pad ใส่ช่องจริง)
      $('input[name="paymentckd_front"], input[name="paymentckd_back"]').on('input', function () {
          this.value = this.value.replace(/\D/g, '');
          updatePaymentPreview1();
      });

      // ช่องหน้า
      $('input[name="paymentckd_front"]').on('blur', function () {
          let val = $(this).val().replace(/\D/g, '');
          $(this).val(val.padStart(4, '0'));
           updatePaymentPreview1();
      });
      // ช่องหลัง
      $('input[name="paymentckd_back"]').on('blur', function () {
          let val = $(this).val().replace(/\D/g, '');
          $(this).val(val.padStart(4, '0'));
           updatePaymentPreview1();
      });


      document.querySelectorAll('input[name="paymentdateckd"]').forEach(input => {
          input.addEventListener('input', function (e) {
              let value = e.target.value.replace(/\D/g, ''); // ดึงเฉพาะตัวเลข
              let name = e.target.name;
              
              if (name === 'paymentdateckd') {
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


        // ส่งออก Excel STM
        document.getElementById('exportstmgetovlookup').addEventListener('click', function () {
            let table = document.querySelector('#data_tabel_stmvlookup table');
            if (!table) return;
            let workbook = XLSX.utils.book_new();
            let worksheet = XLSX.utils.table_to_sheet(table, { raw: true });
            XLSX.utils.book_append_sheet(workbook, worksheet, "STM_Data");
            XLSX.writeFile(workbook, 'Exported_STM_Data.xlsx');
        });

        // ส่งออก Excel CKD
        document.getElementById('exportckdgetovlookup').addEventListener('click', function () {
            let table = document.querySelector('#data_tabel_ckdvlookup table');
            if (!table) return;
            let workbook = XLSX.utils.book_new();
            let worksheet = XLSX.utils.table_to_sheet(table, { raw: true });
            XLSX.utils.book_append_sheet(workbook, worksheet, "DCKD_Data");
            XLSX.writeFile(workbook, 'Exported_DCKD_Data.xlsx');
        });

        // เคลียร์ padding ที่อาจค้างตอนปิด Modal
        $('#myModalget, #myModalgetckd').on('hidden.bs.modal', function () {
            $('body').css('padding-right', '');
            $('body').removeClass('modal-open');
            $('#myInputModal').val('').trigger('keyup');
            $('#myInputModalCkd').val('').trigger('keyup');
        });

        function formatMoneyJS(num) {
            return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function recalculateStmSummary() {
            var dataRows = $("#data_tabel_stmvlookup tbody tr").not('.spacer-row');
            var visibleRows = dataRows.filter(function() {
                return this.style.display !== 'none';
            });
            var visibleCount = visibleRows.length;
            var totalCount = dataRows.length;

            var sum_debit = 0, sum_comp = 0, sum_diff = 0;

            visibleRows.each(function () {
                var tr = $(this);
                sum_debit += parseFloat(tr.find('td').eq(9).text().replace(/,/g, '')) || 0;
                sum_comp  += parseFloat(tr.find('td').eq(10).text().replace(/,/g, '')) || 0;
                sum_diff  += parseFloat(tr.find('td').eq(11).text().replace(/,/g, '')) || 0;
            });

            var tfootRow = $("#data_tabel_stmvlookup tfoot tr.summary-total-row, #data_tabel_stmvlookup tfoot tr");
            if (tfootRow.find('#o1').length > 0) {
                tfootRow.find('#o1').html('&nbsp;' + formatMoneyJS(sum_debit));
                tfootRow.find('#o2').html('&nbsp;' + formatMoneyJS(sum_comp));
                tfootRow.find('#o3').html('&nbsp;' + formatMoneyJS(sum_diff));
            }

            // อัปเดตการ์ด KPI ด้านบน
            $('#txtq0').text(visibleCount + ' คน');
            $('#txtq1').text(formatMoneyJS(sum_debit));
            $('#txtq2').text(formatMoneyJS(sum_comp));
            $('#txtq3').text(formatMoneyJS(sum_diff));
        }

        function recalculateCkdSummary() {
            var dataRows = $("#data_tabel_ckdvlookup tbody tr").not('.spacer-row');
            var visibleRows = dataRows.filter(function() {
                return this.style.display !== 'none';
            });
            var visibleCount = visibleRows.length;
            var totalCount = dataRows.length;

            var sum_debit = 0, sum_comp = 0, sum_diff = 0;

            visibleRows.each(function () {
                var tr = $(this);
                sum_debit += parseFloat(tr.find('td').eq(8).text().replace(/,/g, '')) || 0;
                sum_comp  += parseFloat(tr.find('td').eq(9).text().replace(/,/g, '')) || 0;
                sum_diff  += parseFloat(tr.find('td').eq(10).text().replace(/,/g, '')) || 0;
            });

            var tfootRow = $("#data_tabel_ckdvlookup tfoot tr.summary-total-row, #data_tabel_ckdvlookup tfoot tr");
            if (tfootRow.find('#o1').length > 0) {
                tfootRow.find('#o1').html('&nbsp;' + formatMoneyJS(sum_debit));
                tfootRow.find('#o2').html('&nbsp;' + formatMoneyJS(sum_comp));
                tfootRow.find('#o3').html('&nbsp;' + formatMoneyJS(sum_diff));
            }

            // อัปเดตการ์ด KPI ด้านบน
            $('#txtq0c').text(visibleCount + ' คน');
            $('#txtq1c').text(formatMoneyJS(sum_debit));
            $('#txtq2c').text(formatMoneyJS(sum_comp));
            $('#txtq3c').text(formatMoneyJS(sum_diff));
        }

        // ฟังก์ชันกรองค้นหาข้อมูลในตาราง Modal STM Realtime
        $(document).on("keyup input", "#myInputModal", function() {
            var value = $(this).val().toLowerCase().trim();
            var dataRows = $("#data_tabel_stmvlookup tbody tr").not('.spacer-row');
            var totalCount = dataRows.length;
            
            if (value !== '') {
                $('#clearSearchModalBtn').css('display', 'inline-flex');
            } else {
                $('#clearSearchModalBtn').hide();
            }

            var visibleCount = 0;
            dataRows.each(function() {
                var tr = $(this);
                var text = tr.text().toLowerCase();
                var matched = text.indexOf(value) > -1;
                tr.toggle(matched);
                if (matched) visibleCount++;
            });

            // Show spacer row always
            $("#data_tabel_stmvlookup tbody tr.spacer-row").show();

            if (value === '') {
                $('#filtered_row_count_stm').text('กำลังแสดงทุกรายการ (' + totalCount + ' รายการ)');
            } else {
                $('#filtered_row_count_stm').html('พบ <b>' + visibleCount + '</b> จาก ' + totalCount + ' รายการ');
            }

            recalculateStmSummary();
        });

        // ฟังก์ชันกรองค้นหาข้อมูลในตาราง Modal CKD Realtime
        $(document).on("keyup input", "#myInputModalCkd", function() {
            var value = $(this).val().toLowerCase().trim();
            var dataRows = $("#data_tabel_ckdvlookup tbody tr").not('.spacer-row');
            var totalCount = dataRows.length;
            
            if (value !== '') {
                $('#clearSearchModalCkdBtn').css('display', 'inline-flex');
            } else {
                $('#clearSearchModalCkdBtn').hide();
            }

            var visibleCount = 0;
            dataRows.each(function() {
                var tr = $(this);
                var text = tr.text().toLowerCase();
                var matched = text.indexOf(value) > -1;
                tr.toggle(matched);
                if (matched) visibleCount++;
            });

            // Show spacer row always
            $("#data_tabel_ckdvlookup tbody tr.spacer-row").show();

            if (value === '') {
                $('#filtered_row_count_ckd').text('กำลังแสดงทุกรายการ (' + totalCount + ' รายการ)');
            } else {
                $('#filtered_row_count_ckd').html('พบ <b>' + visibleCount + '</b> จาก ' + totalCount + ' รายการ');
            }

            recalculateCkdSummary();
        });

        // เรียงลำดับข้อมูลเมื่อคลิกหัวคอลัมน์
        $(document).on('click', '.table-scroll-modal th.sortable-header', function() {
            var table = $(this).closest('table');
            var tbody = table.find('tbody');
            var th = $(this);
            var columnIndex = parseInt(th.data('column'));
            
            var isAscending = !th.hasClass('sort-desc');
            table.find('th.sortable-header').removeClass('sort-asc sort-desc');
            
            if (isAscending) {
                th.addClass('sort-desc');
            } else {
                th.addClass('sort-asc');
            }

            var rows = [];

            tbody.find('tr').not('.spacer-row, .summary-total-row').each(function() {
                var tr = $(this);
                rows.push({
                    row: tr,
                    sortValue: tr.find('td').eq(columnIndex).text().trim()
                });
            });

            rows.sort(function(a, b) {
                var valA = a.sortValue.replace(/,/g, '').replace(/วัน/g, '').trim();
                var valB = b.sortValue.replace(/,/g, '').replace(/วัน/g, '').trim();

                if (!isNaN(valA) && !isNaN(valB) && valA !== '' && valB !== '') {
                    return isAscending ? parseFloat(valB) - parseFloat(valA) : parseFloat(valA) - parseFloat(valB);
                } else {
                    return isAscending ? valB.localeCompare(valA, 'th') : valA.localeCompare(valB, 'th');
                }
            });

            tbody.empty();
            
            $.each(rows, function(index, item) {
                item.row.find('td').eq(0).text(index + 1);
                tbody.append(item.row);
            });

            var colCount = table.find('thead tr:last th').length || 12;
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

        // Function for viewing duplicate visits
        function viewDuplicateVisits(cid, vstdate) {
            if(!cid || !vstdate) return;
            
            $.ajax({
                url: "datatimestamp-insert2.php",
                method: "POST",
                data: { 
                    action_get_duplicate_visits: 'true',
                    cid: cid,
                    vstdate: vstdate
                },
                success: function(data) {
                    Swal.fire({
                        title: '<span style="font-size: 24px;">รายชื่อผู้ป่วยที่มารับบริการซ้ำภายในวันเดียวกัน</span>',
                        html: data,
                        width: '65%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        customClass: {
                            container: 'high-zindex-swal'
                        }
                    });
                    
                    // แทรก Style ชั่วคราวเพื่อให้ Swal อยู่เหนือ Bootstrap Modal อย่างเด็ดขาด
                    if($('#swal-zindex-fix').length === 0) {
                        $('head').append('<style id="swal-zindex-fix">.swal2-container.high-zindex-swal { z-index: 999999 !important; }</style>');
                    }
                },
                error: function() {
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
                }
            });
        }

    </script>

  <?php if (!empty($_SESSION['role'])): ?>
  <!-- ✅ Quick Search (Ctrl+K) -->
  <?php include './includes/quick_search_ui.html'; ?>
  <?php endif; ?>

  </body>
</html>
