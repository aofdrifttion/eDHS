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
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>eDebtor Hospital System (eDHS)</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&family=Poppins:wght@400;600&display=swap"
      rel="stylesheet"
    />
      <!-- โหลดฟอนต์ Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

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

        #container01 {
          width: 100%;
          height: 300px;
          margin: auto;
        }
        #container02 {
          width: 100%;
          height: 300px;
          margin: auto;
        }
        #container03 {
          width: 100%;
          height: 300px;
          margin: auto;
        }
        #container04 {
          width: 100%;
          height: 300px;
          margin: auto;
        }   

        .sidenav {
          height: 100vh;
          width: 0;
          position: fixed;
          z-index: 99999;
          top: 0;
          left: 0;
          background-color: #ffffff;
          overflow: hidden;
          transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          padding: 0 !important;
          margin: 0;
          box-shadow: 0 0 30px rgba(0,0,0,0.25);
        }

        .sidenav a {
          padding: 8px 8px 8px 32px;
          text-decoration: none;
          font-size: 25px;
          color: #818181;
          display: block;
          transition: 0.3s;

        }

        .sidenav a:hover{
          color: #f1f1f1;
        }

        .sidenav .closebtn {
          position: absolute;
          top: 0;
          right: 25px;
          font-size: 36px;
          margin-left: 50px;
        }

        @media screen and (max-height: 450px) {
          .sidenav {padding-top: 15px;}
          .sidenav a {font-size: 18px;}
        }                     
      </style>

      <style>
        /* หน้ากากโลโก้ */
        #loader {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: #ffffff;
          display: flex;
          flex-direction: column; /* เรียงแนวตั้ง */
          justify-content: center;
          align-items: center;
          z-index: 9999;
          transition: opacity 1s ease; /* เฟดนุ่ม */
          font-family: 'Poppins', sans-serif; /* ✅ ใช้ฟอนต์ Poppins */
        }
        #loader img {
          width: 200px;
          height: auto;
          margin-bottom: 10px; /* เว้นห่างระหว่างรูปกับข้อความ */
        }
        #loader p {
          font-size: 14px;
          color: #545ed9;
          margin: 0;
          letter-spacing: 3px; /* ✅ เพิ่มระยะห่างตัวอักษร */
        }
        /* เมื่อซ่อน loader */
        .hide-loader {
          opacity: 0;
          pointer-events: none;
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


        .fx-rings { 
          overflow: visible; 
        }
        .fx-rings::before {
          content: '';
          position: absolute;
          top: 50%; left: 50%;
          transform: translate(-50%, -50%);
          width: 100%; height: 100%;
          border: 2px solid #ab8fea;
          border-radius: inherit;
          opacity: 0;
          z-index: -1;
        }
        .fx-rings:hover::before {
          animation: ring-expand 1s ease-out infinite;
        }
        @keyframes ring-expand {
          0% { width: 100%; height: 100%; opacity: 1; }
          100% { width: 150%; height: 180%; opacity: 0; }
        }


      /* ตกแต่ง Card ให้ขอบมน มีเส้นขอบบางๆ และเงาเนียนขึ้น */
      .custom-card {
        border: 1px solid #f0f2f5; 
        border-radius: 16px;
        box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.03); 
        background-color: #fff;
      }

      /* ... (โค้ดปุ่ม btn-manual คงเดิม) ... */
      .btn-manual {
        color: #696cff;
        border: 1px solid #d9d9fc;
        background-color: #fcfcff;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        cursor: pointer;
      }
      .btn-manual:hover {
        background-color: #f0f0ff;
        border-color: #696cff;
      }

      /* ขยายขนาดวงกลม ปรับสี และเพิ่มเงาให้ฟุ้ง (Soft Glow) */
      .icon-bg {
        width: 85px; /* ขยายขนาดวงกลม */
        height: 85px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px; /* ขยายขนาดไอคอน */
      }
      .icon-purple {
        background: linear-gradient(135deg, #f4efff 0%, #e9dfff 100%);
        color: #8c57ff;
      }
      .icon-orange {
        background: linear-gradient(135deg, #fff2e8 0%, #ffe1cc 100%);
        color: #ff914d;
      }
      .icon-blue {
        background: linear-gradient(135deg, #fff2cc 0%, #fff4d2 100%);
        color: #eab308;
      }

      .icon-green {
        background: linear-gradient(135deg, #e8fce8 0%, #ccffcc 100%);
        color: #22c55e;
      }

      /* ปรับปรุงตัวหนังสือและแคปซูลเปอร์เซ็นต์ */
      .stat-title { font-size: 16px; font-weight: 500; color: #3b4256; margin-bottom: 4px; }
      .stat-sub   { font-size: 13px; color: #8a92a6; margin-bottom: 12px; }
      .stat-value { font-size: 26px; font-weight: 600; color: #111827; margin-bottom: 8px; }

      .stat-diff-badge {
        display: inline-flex;
        align-items: center;
        background-color: #e8fce8; /* สีพื้นหลังแคปซูลเขียว */
        color: #22c55e;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
      }
      .stat-diff-badge.danger {
        background-color: #ffeaea; /* สีพื้นหลังแคปซูลแดง */
        color: #ef4444;
      }
      .text-muted-diff { color: #8a92a6; font-size: 13px; font-weight: normal; margin-left: 8px; }

      .icon-bg i {
        font-size: 40px !important; /* ปรับตัวเลขตรงนี้ให้ใหญ่ได้ตามต้องการเลยครับ */
      }

      /* ลายคลื่นสีม่วงฟุ้งๆ สำหรับ Banner ตัวบนสุด */
      .banner-wave-bg {
        /* เติม preserveAspectRatio='none' เข้าไปแล้วครับ บังคับยืดเต็ม 100% แน่นอน */
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320' preserveAspectRatio='none'%3E%3Cpath fill='%23f4efff' fill-opacity='0.8' d='M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,261.3C960,256,1056,224,1152,213.3C1248,203,1344,213,1392,218.7L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3Cpath fill='%23e9dfff' fill-opacity='0.9' d='M0,160L48,176C96,192,192,224,288,229.3C384,235,480,213,576,192C672,171,768,149,864,160C960,171,1056,213,1152,218.7C1248,224,1344,192,1392,176L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
        background-position: bottom; 
        background-repeat: no-repeat; 
        background-size: 100% 120px; 
        position: relative;
        z-index: 1;
      }

      /* ทำให้ข้อความและปุ่มใน Banner ลอยอยู่เหนือคลื่น */
      .banner-wave-bg * {
        position: relative;
        z-index: 2;
      }


      /* ตกแต่งไอคอนวงกลมขนาดเล็ก */
      .icon-sm-circle {
        width: 36px; 
        height: 36px;
        border-radius: 50%;
        display: flex; 
        align-items: center; 
        justify-content: center;
        font-size: 20px; 
        margin-right: 12px;
        flex-shrink: 0;
      }
      .icon-purple-light { background-color: #f4efff; color: #8c57ff; border: 1px solid #e9dfff; }
      .icon-orange-light { background-color: #fff2e8; color: #ff914d; border: 1px solid #ffe1cc; }

      /* พื้นหลังลายคลื่นจางๆ ทางขวาของกล่อง */
      .card-wave-right {
        position: relative;
        overflow: hidden;
      }
      .card-wave-right::before {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        width: 50%;
        height: 100%;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 150' preserveAspectRatio='none'%3E%3Cpath fill='%23f4efff' fill-opacity='0.6' d='M100,150 C250,150 250,0 400,0 L400,150 Z'%3E%3C/path%3E%3C/svg%3E");
        background-size: cover;
        background-position: right bottom;
        z-index: 0;
      }
      .card-wave-right > div {
        position: relative;
        z-index: 1; /* ดันให้เนื้อหาอยู่เหนือคลื่น */
      }

      /* กล่อง eDHS Premium ด้านล่างซ้ายของ Sidebar */
      .sidebar-premium-box {
        margin: auto 15px 20px 15px; /* คำว่า auto จะดันกล่องนี้ให้ชิดขอบล่างเสมอครับ */
        padding: 12px 15px;
        background-color: #fcfcff;
        border: 1px solid #e9ddff;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 2px 10px rgb(228 221 235 / 72%);
        z-index: 10;
        width: 87%;
      }
      .sidebar-premium-icon {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #f4efff 0%, #e9dfff 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      /* ใส่สีให้ไอคอนในกล่อง (ถ้าพี่ใช้ไอคอน) */
      .sidebar-premium-icon i {
        color: #8c57ff;
        font-size: 18px;
      }
      .sidebar-premium-text h6 {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: #4a55c3; /* สีน้ำเงินอมม่วงตามรูป */
      }
      .sidebar-premium-text small {
        font-size: 11px;
        color: #8a92a6;
      }

      /* ตกแต่งกล่องเวลาไม่มีข้อมูล (Empty State) */
                          .empty-chart-state {
                            height: 280px; /* ตั้งให้สูงเท่ากับกราฟพอดีเป๊ะ เวลากล่องสลับไปมา หน้าเว็บจะได้ไม่กระโดดครับ */
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            color: #a1acb8;
                            text-align: center;
                          }
                          .empty-chart-state i {
                            font-size: 55px; /* ไอคอนใหญ่ๆ จางๆ */
                            color: #eaedf1;
                            margin-bottom: 12px;
                          }
                          .empty-chart-state p {
                            margin: 0;
                            font-size: 14px;
                            font-weight: 500;
                            color: #8a92a6;
                          }


                        </style>

                          <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  </head>

                    <body > 

                    <div id="loader">
                      <img src="./images/logo.gif" alt="Loading...">
                      <p>Loading...</p> <!-- ✅ เพิ่มข้อความตรงนี้ -->
                    </div>

                    <div id="mySidenav" class="sidenav">
                      <iframe src="../manual/index.html" style="width: 100vw; height: 100vh; border: none; display: block;"></iframe>
                    </div>

                      <script>
                      function openNav() {
                        document.getElementById("mySidenav").style.width = "100vw";
                      }

                      function closeNav() {
                        document.getElementById("mySidenav").style.width = "0";
                      }
                      </script>

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
                                  <li class="menu-item active">
                                    <a href="./index.php" class="menu-link">
                                      <img src="./images/el--home-alt.svg" style="width: 22px; margin-right: 10px;">
                                      <div data-i18n="Analytics">Dashboard</div>
                                    </a>
                                  </li>

                                  <?php if (empty($_SESSION['role'])) { ?>
                                    <li class="menu-item" style="margin-top: 10px;">
                                      <a  class="menu-link btn fx-rings" style="border: 1px solid #e4e4e4;" href="javascript:void(0);" onclick="openModallogin();">  
                                        <img src="./images/lucide--book-key.svg" style="width: 22px; margin-right: 10px;">
                                        <div data-i18n="Analytics">เข้าสู่ระบบ</div>
                                      </a>
                                    </li>
                                    <!--<li class="menu-item" style="margin-top: 10px;">
                                      <a  class="menu-link btn fx-rings" style="border: 1px solid #e4e4e4;" href="javascript:void(0);" onclick="openModalContact();">  
                                        <img src="./images/heroicons-outline--chat.svg" style="width: 22px; margin-right: 10px;">
                                        <div data-i18n="Analytics">สอบถามปัญหา</div>
                                      </a>
                                    </li>-->
                                    <li class="menu-item" style="margin-top: 10px;">
                                      <a  class="menu-link btn fx-rings" style="border: 1px solid #e4e4e4;" href="javascript:void(0);" onclick="openNav();">  
                                        <img src="./images/line-md--text-box-to-text-box-multiple-transition.svg" style="width: 22px; margin-right: 10px;">
                                        <div data-i18n="Analytics">แนะนำระบบ</div>
                                      </a>
                                    </li>

                                    <div class="sidebar-premium-box">
                                      <div class="sidebar-premium-icon">
                                        <i class='bx bxl-stripe'></i> 
                                      </div>
                                      <div class="sidebar-premium-text">
                                        <h6><?php echo $hospital; ?></h6>
                                        <small>eDHS v.11.07.69</small>
                                      </div>
                                    </div>

                                  <?php } ?>

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

                              <nav style="z-index: 199;margin-bottom: 20px;" 
                                class="layout-navbar container-fluid navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                                id="layout-navbar" 
                              >
                                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                                  <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                                    <i class="bx bx-menu bx-sm"></i>
                                  </a>
                                </div>

                                <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                                  <!-- Search → เปิด Quick Search Modal -->
                                  <div class="navbar-nav align-items-center">
             
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
                                        <li>
                                          <div class="dropdown-divider"></div>
                                        </li>
                                        <li>
                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="openRegisterModal2()">
                                                    <i class="bx bx-cog me-2"></i>
                                                    <span class="align-middle">จัดการผู้ใช้</span>
                                                </a>
                                                <li>
                                                  <div class="dropdown-divider"></div>
                                                </li>
                                            <?php endif; ?>
                                        
                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                                <a class="dropdown-item" href="#" onclick="connmodal()">
                                                    <i class="bx bx-cog me-2"></i>
                                                    <span class="align-middle">ตั้งค่าการเชื่อมต่อ</span>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                                <a class="dropdown-item" href="#" onclick="openDebtorSettingModal();">
                                                    <i class="bx bx-cog me-2"></i>
                                                    <span class="align-middle">ตั้งค่าระบบลูกหนี้</span>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="openCrSubgroupSettingModal();">
                                                    <i class="bx bx-slider-alt me-2"></i>
                                                    <span class="align-middle">ตั้งค่าการแยกกลุ่มย่อย CR</span>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="openSssSubgroupSettingModal();">
                                                    <i class="bx bx-slider-alt me-2"></i>
                                                    <span class="align-middle">ตั้งค่าการแยกกลุ่มย่อย SSS</span>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                                <a class="dropdown-item" href="./backup/">
                                                    <i class="bx bx-cog me-2"></i>
                                                    <span class="align-middle">สำรองข้อมูล</span>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                                <a class="dropdown-item" href="report_logs.php" target="_blank">
                                                    <i class="bx bx-history me-2"></i>
                                                    <span class="align-middle">รายงานประวัติการใช้งาน</span>
                                                </a>
                                            <?php endif; ?>

                                            <li>
                                              <a class="dropdown-item" href="javascript:void(0);" onclick="openChangelogModal()">
                                                <i class="bx bx-gift me-2"></i>
                                                <span class="align-middle">ประวัติการอัปเดตระบบ</span>
                                                <span class="badge bg-label-primary rounded-pill ms-auto px-2" style="font-size: 11px;">v<?= get_system_version(); ?></span>
                                              </a>
                                            </li>

                                        </li>


                                        <li>
                                          <a class="dropdown-item" href="javascript:void(0);" onclick="openEditProfileModal()">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">ข้อมูลส่วนตัว</span>
                                          </a>
                                        </li>
                                        <li>
                                          <div class="dropdown-divider"></div>
                                        </li>

                                        <li>
                                          <a class="dropdown-item" href="javascript:void(0);" onclick="openRegisterModal()">
                                            <i class="bx bx-happy-beaming me-2"></i>
                                            <span class="align-middle">สมาชิก</span>
                                          </a>
                                        </li>


                                        <li>
                                          <a class="dropdown-item" href="./logout.php">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span class="align-middle">ออกจากระบบ</span>
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

                                    <!-- Banner Section -->
                                    <div class="col-lg-12 mb-4 order-0">
                                      <!-- ตรงนี้แหละครับที่เพิ่มคลาสเข้าไป -->
                                      <div class="card custom-card banner-wave-bg"> 
                                        <div class="d-flex align-items-center row m-0" style="min-height: 140px;">
                                          <!-- โค้ดด้านในเหมือนเดิม... -->
                                          <div class="col-md-8 p-4">
                                            <h5 class="mb-2" style="font-weight: 600; font-size: 1.3rem;color: #6715f9;">ศูนย์จัดเก็บรายได้และคณะกรรมการบริหารการเงินการคลัง (CFO)</h5>
                                            <p style="color: #697a8d; font-size: 16px; line-height: 1.6; margin-bottom: 16px;">
                                              "ยุทธศาสตร์การพัฒนาระบบบริหารจัดการด้านการเงินการคลัง เพื่อเพิ่มประสิทธิภาพในการบริหารการเงินของหน่วยบริการในพื้นที่ โดยมุ่งเน้นให้การดำเนินงานตามยุทธศาสตร์เป็นไปอย่างต่อเนื่อง เรียบร้อย และเกิดประสิทธิภาพสูงสุด ผ่านการนำระบบบริหารลูกหนี้โรงพยาบาลอิเล็กทรอนิกส์ (eDebtor Hospital System: eDHS) ซึ่งพัฒนาขึ้นเอง มาใช้ในการดำเนินงาน"
                                            </p>
                                            <button class="btn-manual" onclick="openNav()" style="font-size: 16px;">
                                              <i class="bx bx-file"></i> คู่มือการใช้งานระบบ
                                            </button>
                                          </div>
                                          <div class="col-md-4 text-center p-4 d-flex justify-content-end align-items-center">
                                            <!-- รูปลงตรงนี้ ถ้าโลโก้พี่เป็นชื่ออื่นแก้ตรง src ได้เลยครับ -->
                                            <img src="./images/logo.gif" alt="eDHS Logo" style="max-height: 100px; object-fit: contain;margin-bottom: 50px;" />
                                          </div>
                                        </div>
                                      </div>
                                    </div>


                    <?php
                    // --- สร้างตัวแปรเริ่มต้น ---
                    $total_compensated = $total_op = $total_ip = $total_ckd = 0; // ยอดรวมทั้งหมด (All time)
                    $curr_comp = $curr_op = $curr_ip = $curr_ckd = 0; // ยอดเดือนล่าสุด
                    $prev_comp = $prev_op = $prev_ip = $prev_ckd = 0; // ยอดเดือนก่อนหน้า
                    $perc_comp = $perc_op = $perc_ip = $perc_ckd = 0; // เปอร์เซ็นต์เพิ่ม/ลด

                    // ==========================================
                    // ส่วนที่ 1: ดึงยอดรวมทั้งหมด (All Time) สำหรับโชว์ตัวเลขใหญ่
                    // ==========================================
                    $sql_total = "SELECT SUM(compensated) AS comp, SUM(op) AS op, SUM(ip) AS ip FROM imr_tb_check_invoice";
                    $res_total = mysqli_query($conn, $sql_total);
                    if ($res_total && $row = mysqli_fetch_assoc($res_total)) {
                        $total_compensated = floatval($row['comp']);
                        $total_op = floatval($row['op']);
                        $total_ip = floatval($row['ip']);
                    }

                    $sql_total_ckd = "SELECT SUM(comp) AS total_ckd FROM (
                        SELECT SUM(compensated) AS comp FROM imr_tb_seamless_dckd
                        UNION ALL
                        SELECT SUM(amount) AS comp FROM imr_tb_seamless_dckd_ofc
                    ) AS combined";
                    $res_total_ckd = mysqli_query($conn, $sql_total_ckd);
                    if ($res_total_ckd && $row = mysqli_fetch_assoc($res_total_ckd)) {
                        $total_ckd = floatval($row['total_ckd']);
                    }

                    // ==========================================
                    // ส่วนที่ 2: ดึงข้อมูลเดือนล่าสุดและเดือนก่อนหน้า เพื่อหา %
                    // ==========================================
                    $sql_max = "SELECT MAX(LEFT(rep, 4)) AS latest_yymm FROM imr_tb_check_invoice WHERE rep IS NOT NULL AND rep != ''";
                    $res_max = mysqli_query($conn, $sql_max);

                    if ($res_max && $row_max = mysqli_fetch_assoc($res_max)) {
                        $latest_yymm = $row_max['latest_yymm']; 

                        if (!empty($latest_yymm)) {
                            // --- ดึงยอดเดือนล่าสุด ---
                            $sql_curr = "SELECT SUM(compensated) AS comp, SUM(op) AS op, SUM(ip) AS ip FROM imr_tb_check_invoice WHERE LEFT(rep, 4) = '$latest_yymm'";
                            $res_curr = mysqli_query($conn, $sql_curr);
                            if ($res_curr && $row = mysqli_fetch_assoc($res_curr)) {
                                $curr_comp = floatval($row['comp']);
                                $curr_op = floatval($row['op']);
                                $curr_ip = floatval($row['ip']);
                            }
                            
                            $sql_curr_ckd = "SELECT SUM(comp) AS total_ckd FROM (
                                SELECT SUM(compensated) AS comp FROM imr_tb_seamless_dckd WHERE LEFT(rep, 4) = '$latest_yymm'
                                UNION ALL
                                SELECT SUM(amount) AS comp FROM imr_tb_seamless_dckd_ofc WHERE LEFT(stm_doc, 4) = '$latest_yymm'
                            ) AS combined";
                            $res_curr_ckd = mysqli_query($conn, $sql_curr_ckd);
                            if ($res_curr_ckd && $row = mysqli_fetch_assoc($res_curr_ckd)) {
                                $curr_ckd = floatval($row['total_ckd']);
                            }

                            // --- คำนวณหาเดือนก่อนหน้า ---
                            $curr_y_th = substr($latest_yymm, 0, 2);
                            $curr_m = substr($latest_yymm, 2, 2);
                            $curr_y_eng = (int)$curr_y_th + 2500 - 543;
                            $curr_date_str = $curr_y_eng . '-' . $curr_m . '-01'; 
                            
                            $last_month_time = strtotime('-1 month', strtotime($curr_date_str));
                            $last_yymm = date('y', strtotime('+543 years', $last_month_time)) . date('m', $last_month_time);

                            // --- ดึงยอดเดือนก่อนหน้า ---
                            $sql_prev = "SELECT SUM(compensated) AS comp, SUM(op) AS op, SUM(ip) AS ip FROM imr_tb_check_invoice WHERE LEFT(rep, 4) = '$last_yymm'";
                            $res_prev = mysqli_query($conn, $sql_prev);
                            if ($res_prev && $row = mysqli_fetch_assoc($res_prev)) {
                                $prev_comp = floatval($row['comp']);
                                $prev_op = floatval($row['op']);
                                $prev_ip = floatval($row['ip']);
                            }
                            
                            $sql_prev_ckd = "SELECT SUM(comp) AS total_ckd FROM (
                                SELECT SUM(compensated) AS comp FROM imr_tb_seamless_dckd WHERE LEFT(rep, 4) = '$last_yymm'
                                UNION ALL
                                SELECT SUM(amount) AS comp FROM imr_tb_seamless_dckd_ofc WHERE LEFT(stm_doc, 4) = '$last_yymm'
                            ) AS combined";
                            $res_prev_ckd = mysqli_query($conn, $sql_prev_ckd);
                            if ($res_prev_ckd && $row = mysqli_fetch_assoc($res_prev_ckd)) {
                                $prev_ckd = floatval($row['total_ckd']);
                            }

                            // --- คำนวณ % การเติบโต (ดักจับ Error หารด้วยศูนย์) ---
                            $perc_comp = ($prev_comp != 0) ? (($curr_comp - $prev_comp) / $prev_comp) * 100 : (($curr_comp > 0) ? 100 : 0);
                            $perc_op = ($prev_op != 0) ? (($curr_op - $prev_op) / $prev_op) * 100 : (($curr_op > 0) ? 100 : 0);
                            $perc_ip = ($prev_ip != 0) ? (($curr_ip - $prev_ip) / $prev_ip) * 100 : (($curr_ip > 0) ? 100 : 0);
                            $perc_ckd = ($prev_ckd != 0) ? (($curr_ckd - $prev_ckd) / $prev_ckd) * 100 : (($curr_ckd > 0) ? 100 : 0);
                        }
                    }

                    // ==========================================
                    // ฟังก์ชันสร้างหน้าตาแคปซูลเปอร์เซ็นต์ (เขียว/แดง/เทา อัตโนมัติ)
                    // ==========================================
                    function getPercentBadgeHTML($percent) {
                        $val = round($percent, 2);
                        if ($val > 0) {
                            return '<span class="stat-diff-badge"><i class="bx bx-chevron-up"></i> ' . $val . '%</span>';
                        } elseif ($val < 0) {
                            return '<span class="stat-diff-badge danger"><i class="bx bx-chevron-down"></i> ลดลง ' . abs($val) . '%</span>';
                        } else {
                            return '<span class="stat-diff-badge" style="background-color: #f0f2f5; color: #697a8d;"><i class="bx bx-minus"></i> 0%</span>';
                        }
                    }
                    ?>

                    <div class="col-lg-3 col-md-6 mb-4">
                      <div class="card custom-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                          <div class="d-flex flex-column">
                            <span class="stat-title">Invoice statement</span>
                            <span class="stat-sub">เงินเรียกเก็บ</span>
                            <span class="stat-value"><?php echo number_format($total_compensated, 2); ?> ฿</span>
                            <div class="d-flex align-items-center mt-1">
                              <?php echo getPercentBadgeHTML($perc_comp); ?>
                              <span class="text-muted-diff">จากเดือนที่แล้ว</span>
                            </div>
                          </div>
                          <div class="icon-bg icon-purple">
                            <i class="bx bxs-file"></i>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                      <div class="card custom-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                          <div class="d-flex flex-column">
                            <span class="stat-title">OPD</span>
                            <span class="stat-sub">เงินเรียกเก็บ</span>
                            <span class="stat-value"><?php echo number_format($total_op, 2); ?> ฿</span>
                            <div class="d-flex align-items-center mt-1">
                              <?php echo getPercentBadgeHTML($perc_op); ?>
                              <span class="text-muted-diff">จากเดือนที่แล้ว</span>
                            </div>
                          </div>
                          <div class="icon-bg icon-orange">
                            <i class="bx bxs-group"></i>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                      <div class="card custom-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                          <div class="d-flex flex-column">
                            <span class="stat-title">IPD</span>
                            <span class="stat-sub">เงินเรียกเก็บ</span>
                            <span class="stat-value"><?php echo number_format($total_ip, 2); ?> ฿</span>
                            <div class="d-flex align-items-center mt-1">
                              <?php echo getPercentBadgeHTML($perc_ip); ?>
                              <span class="text-muted-diff">จากเดือนที่แล้ว</span>
                            </div>
                          </div>
                          <div class="icon-bg icon-blue">
                            <i class="bx bxs-bed"></i>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                      <div class="card custom-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                          <div class="d-flex flex-column">
                            <span class="stat-title">CKD</span>
                            <span class="stat-sub">เงินชดเชย</span>
                            <span class="stat-value"><?php echo number_format($total_ckd, 2); ?> ฿</span>
                            <div class="d-flex align-items-center mt-1">
                              <?php echo getPercentBadgeHTML($perc_ckd); ?>
                              <span class="text-muted-diff">จากเดือนที่แล้ว</span>
                            </div>
                          </div>
                          <div class="icon-bg icon-green">
                            <i class="bx bxs-coin-stack"></i>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3 mb-4">
                      <div class="card custom-card h-100">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-title" style="font-size: 14px;">เปรียบเทียบตามสิทธิการรักษา</span>
                            <a href="#" style="font-size: 12px; color: #8c57ff; text-decoration: none;">ดูทั้งหมด &rarr;</a>
                          </div>
                          <div id="container01"></div>
                          <table id="datatable" style="display:none;">
                            <thead>
                              <tr><th></th><th>จำนวนเงิน (บาท)</th></tr>
                            </thead>
                            <tbody>
                              <?php
                              $sql = "SELECT IFNULL(fund, 'ไม่ระบุสิทธิ') AS fund, SUM(compensated) AS total FROM imr_tb_check_invoice GROUP BY fund ORDER BY total DESC";
                              $result = $conn->query($sql);
                              if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                  echo "<tr><td>" . htmlspecialchars($row["fund"]) . "</td><td>" . ($row["total"]) . "</td></tr>";
                                }
                              } else { echo "<tr><td colspan='2'>No data found</td></tr>"; }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3 mb-4">
                      <div class="card custom-card h-100">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-title" style="font-size: 14px;">เปรียบเทียบ OPD ตามสิทธิ</span>
                            <a href="#" style="font-size: 12px; color: #8c57ff; text-decoration: none;">ดูทั้งหมด &rarr;</a>
                          </div>
                          <div id="container02"></div>
                          <table id="datatable02" style="display:none;">
                            <thead>
                              <tr><th></th><th>จำนวนเงิน (บาท)</th></tr>
                            </thead>
                            <tbody>
                              <?php
                              $sql = "SELECT IFNULL(fund, 'ไม่ระบุสิทธิ') AS fund, SUM(op) AS total FROM imr_tb_check_invoice GROUP BY fund ORDER BY total DESC";
                              $result = $conn->query($sql);
                              if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                  echo "<tr><td>" . htmlspecialchars($row["fund"]) . "</td><td>" . ($row["total"]) . "</td></tr>";
                                }
                              } else { echo "<tr><td colspan='2'>No data found</td></tr>"; }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3 mb-4">
                      <div class="card custom-card h-100">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-title" style="font-size: 14px;">เปรียบเทียบ IPD ตามสิทธิ</span>
                            <a href="#" style="font-size: 12px; color: #8c57ff; text-decoration: none;">ดูทั้งหมด &rarr;</a>
                          </div>
                          <div id="container03"></div>
                          <table id="datatable03" style="display:none;">
                            <thead>
                              <tr><th></th><th>จำนวนเงิน (บาท)</th></tr>
                            </thead>
                            <tbody>
                              <?php
                              $sql = "SELECT IFNULL(fund, 'ไม่ระบุสิทธิ') AS fund, SUM(ip) AS total FROM imr_tb_check_invoice GROUP BY fund ORDER BY total DESC";
                              $result = $conn->query($sql);
                              if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                  echo "<tr><td>" . htmlspecialchars($row["fund"]) . "</td><td>" . ($row["total"]) . "</td></tr>";
                                }
                              } else { echo "<tr><td colspan='2'>No data found</td></tr>"; }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3 mb-4">
                      <div class="card custom-card h-100">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-title" style="font-size: 14px;">เปรียบเทียบ CKD ตามสิทธิ</span>
                            <a href="#" style="font-size: 12px; color: #8c57ff; text-decoration: none;">ดูทั้งหมด &rarr;</a>
                          </div>

                          <?php
                          // 1. ดึงข้อมูลขึ้นมาก่อนเพื่อเช็คว่ามีไหม
                          $sql_graph4 = "SELECT source, SUM(compensated) AS total_compensated FROM ( SELECT 'UCS' AS source, SUM(compensated) AS compensated FROM imr_tb_seamless_dckd GROUP BY rep UNION ALL SELECT 'OFC' AS source, SUM(amount) AS compensated FROM imr_tb_seamless_dckd_ofc GROUP BY stm_doc ) AS combined GROUP BY source ORDER BY total_compensated DESC";
                          $result_graph4 = $conn->query($sql_graph4);

                          // 2. ถ้ามีข้อมูล ให้โชว์โครงสร้างกราฟ Highcharts ตามปกติ
                          if ($result_graph4 && $result_graph4->num_rows > 0) {
                          ?>
                              <div id="container04"></div>
                              <table id="datatable04" style="display:none;">
                                <thead>
                                  <tr><th></th><th>จำนวนเงิน (บาท)</th></tr>
                                </thead>
                                <tbody>
                                  <?php
                                  while ($row = $result_graph4->fetch_assoc()) {
                                    echo "<tr><td>" . htmlspecialchars($row["source"]) . "</td><td>" . ($row["total_compensated"]) . "</td></tr>";
                                  }
                                  ?>
                                </tbody>
                              </table>

                          <?php 
                          // 3. แต่ถ้า "ไม่มีข้อมูล" ให้โชว์กล่อง Empty State สวยๆ แทน
                          } else { 
                          ?>
                              <div class="empty-chart-state">
                                <i class="bx bx-folder-open"></i>
                                <p>ยังไม่มีข้อมูลในระบบ</p>
                              </div>
                          <?php } ?>

                        </div>
                      </div>
                    </div>

                    <div class="col-md-6 mb-4">
                      <div class="card custom-card h-100 card-wave-right">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                          <div class="d-flex align-items-start">
                            <div class="icon-sm-circle icon-purple-light">
                              <i class="bx bx-info-circle"></i>
                            </div>
                            <div>
                              <h6 class="mb-1" style="color: #3b4256; font-weight: 600; font-size: 15px;">ข้อมูลสรุปภาพรวม</h6>
                              <div class="mt-1">
                                <?php echo render_version_badge(true); ?>
                              </div>
                            </div>
                          </div>
                          <img src="./images/3d-chart.png" alt="chart" style="max-height: 90px; object-fit: contain;">
                        </div>
                      </div>
                    </div>

                    <div class="col-md-6 mb-4">
                      <div class="card custom-card h-100 card-wave-right">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                          <div class="d-flex align-items-start">
                            <div class="icon-sm-circle icon-orange-light">
                              <i class="bx bx-bell"></i>
                            </div>
                            <div>
                              <h6 class="mb-1" style="color: #3b4256; font-weight: 600; font-size: 15px;">ประกาศ / ข่าวสาร</h6>
                              <small class="d-block mb-1" style="color: #566a7f; font-size: 13px;">eDHS : ระบบบริหารจัดการลูกหนี้ พร้อมฟีเจอร์รายงานที่ชาญฉลาดและตอบโจทย์การทำงานยิ่งขึ้น</small>
                            </div>
                          </div>
                          <img src="./images/3d-speaker.png" alt="news" style="max-height: 90px; object-fit: contain;">
                        </div>
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




    <!-- การเชื่อมต่อ -->
                      <div class="modal fade" id="connModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                          <div class="modal-content" style="height: 88vh; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25); border: 1px solid #cbd5e1;">
                            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                              <div class="d-flex align-items-center gap-3">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);">
                                  <i class='bx bx-server'></i>
                                </div>
                                <div>
                                  <h5 class="modal-title mb-0 fw-bold text-dark" style="font-size: 1.15rem;">ตั้งค่าการเชื่อมต่อฐานข้อมูล</h5>
                                  <small class="text-muted">กำหนดค่าการเชื่อมต่อฐานข้อมูล eDHS (im_report) และ HOSxP (hos)</small>
                                </div>
                              </div>
                              <button type="button" class="btn btn-sm btn-icon rounded-circle shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-size: 20px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5'; this.style.transform='scale(1.08)';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'; this.style.borderColor='#cbd5e1'; this.style.transform='scale(1)';">
                                <i class='bx bx-x' style="font-size: 22px; font-weight: bold;"></i>
                              </button>
                            </div>
                            <div class="modal-body p-0" style="background: #f8fafc;">
                              <iframe src="./database_config/" width="100%" height="100%" style="border: none; display: block;"></iframe>
                            </div>
                          </div>
                        </div>
                      </div>


                      <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                          <div class="modal-content" style="height: 88vh; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25); border: 1px solid #cbd5e1;">
                            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                              <div class="d-flex align-items-center gap-3">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);">
                                  <i class='bx bxs-user-detail'></i>
                                </div>
                                <div>
                                  <h5 class="modal-title mb-0 fw-bold text-dark" style="font-size: 1.15rem;">จัดการข้อมูลสมาชิก</h5>
                                  <small class="text-muted">ระบบจัดการผู้ใช้งานและการลงทะเบียน</small>
                                </div>
                              </div>
                              <button type="button" class="btn btn-sm btn-icon rounded-circle shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-size: 20px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5'; this.style.transform='scale(1.08)';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'; this.style.borderColor='#cbd5e1'; this.style.transform='scale(1)';">
                                <i class='bx bx-x' style="font-size: 22px; font-weight: bold;"></i>
                              </button>
                            </div>
                            <div class="modal-body p-0" style="background: #f8fafc;">
                              <iframe src="./register.php" width="100%" height="100%" style="border: none; display: block;"></iframe>
                            </div>
                          </div>
                        </div>
                      </div>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                      <div class="modal fade" id="registerModal2" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" style="max-width: 90vw; width: 90vw; margin: 1.5rem auto;" role="document">
                          <div class="modal-content" style="height: 90vh; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25); border: 1px solid #cbd5e1;">
                            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                              <div class="d-flex align-items-center gap-3">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);">
                                  <i class='bx bxs-user-detail'></i>
                                </div>
                                <div>
                                  <h5 class="modal-title mb-0 fw-bold text-dark" style="font-size: 1.15rem;">จัดการข้อมูลสมาชิกและสิทธิ์การใช้งาน</h5>
                                  <small class="text-muted">ระบบบริหารจัดการบัญชีผู้ใช้งานและการเข้าถึงข้อมูล</small>
                                </div>
                              </div>
                              <button type="button" class="btn btn-sm btn-icon rounded-circle shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-size: 20px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5'; this.style.transform='scale(1.08)';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'; this.style.borderColor='#cbd5e1'; this.style.transform='scale(1)';">
                                <i class='bx bx-x' style="font-size: 24px; font-weight: bold;"></i>
                              </button>
                            </div>
                            <div class="modal-body p-0" style="background: #f8fafc;">
                              <div class="w-100 h-100">
                                <iframe src="./manage_users.php" width="100%" height="100%" style="border: none; display: block;"></iframe>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
<?php endif; ?>

                      <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;" role="document">
                          
                          <div class="modal-content" style="border-radius: 50px; overflow: hidden; background-color: #f5f5f9;">
                            
                            <div class="modal-body" style="padding: 0px; height: 700px;">
                              
                              <iframe src="./login.php" width="100%" height="100%" style="border: none;" scrolling="no"></iframe>
                              
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Modal แก้ไขข้อมูลส่วนตัว -->
                      <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;" role="document">
                          <div class="modal-content" style="border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.2); border: 1px solid #e2e8f0;">
                            
                            <!-- Modal Header -->
                            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                              <div class="d-flex align-items-center gap-3">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);">
                                  <i class='bx bxs-user-pin'></i>
                                </div>
                                <div>
                                  <h5 class="modal-title mb-0 fw-bold text-dark" style="font-size: 1.15rem;">แก้ไขข้อมูลส่วนตัว</h5>
                                  <small class="text-muted">ปรับปรุงข้อมูลชื่อ-นามสกุล รหัสผ่าน และรหัส PIN 6 หลัก</small>
                                </div>
                              </div>
                              <button type="button" class="btn btn-sm btn-icon rounded-circle shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-size: 20px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5'; this.style.transform='scale(1.08)';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'; this.style.borderColor='#cbd5e1'; this.style.transform='scale(1)';">
                                <i class='bx bx-x' style="font-size: 22px; font-weight: bold;"></i>
                              </button>
                            </div>

                            <!-- Modal Body -->
                            <div class="modal-body p-4" style="background: #f8fafc;">
                              <form id="formEditProfile">
                                
                                <!-- ข้อมูลทั่วไป -->
                                <div class="mb-3 bg-white p-3 rounded-3 border" style="box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                                  <label class="form-label fw-bold text-dark mb-2" style="font-size: 13.5px;">
                                    <i class='bx bx-user-circle text-primary me-1'></i> ชื่อ-นามสกุล
                                  </label>
                                  <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px;"><i class='bx bx-id-card'></i></span>
                                    <input type="text" id="edit_fullname" name="fullname" class="form-control" style="border-radius: 0 10px 10px 0;" value="<?php echo htmlspecialchars($_SESSION['fullname']); ?>" placeholder="ระบุชื่อและนามสกุล" required>
                                  </div>
                                </div>

                                <!-- เปลี่ยนรหัสผ่าน -->
                                <div class="mb-3 bg-white p-3 rounded-3 border" style="box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                                  <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-bold text-dark m-0" style="font-size: 13.5px;">
                                      <i class='bx bx-lock-alt text-primary me-1'></i> เปลี่ยนรหัสผ่านเข้าสู่ระบบ
                                    </label>
                                    <span class="badge bg-light text-muted border" style="font-size: 11px;">เว้นว่างได้</span>
                                  </div>
                                  <div class="row g-2">
                                    <div class="col-12 mb-2">
                                      <label class="form-label small text-muted mb-1">รหัสผ่านใหม่</label>
                                      <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px;"><i class='bx bx-key'></i></span>
                                        <input type="password" id="edit_password" name="new_password" class="form-control border-end-0" placeholder="กรอกรหัสผ่านใหม่ (เว้นว่างไว้ถ้าไม่เปลี่ยน)">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVisibility('edit_password', this)" title="แสดง/ซ่อนรหัสผ่าน" style="border-radius: 0 10px 10px 0; border-color: #d9dee3;"><i class="bx bx-hide"></i></button>
                                      </div>
                                    </div>
                                    <div class="col-12">
                                      <label class="form-label small text-muted mb-1">ยืนยันรหัสผ่านใหม่</label>
                                      <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px;"><i class='bx bx-check-shield'></i></span>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control border-end-0" placeholder="กรอกยืนยันรหัสผ่านใหม่อีกครั้ง">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVisibility('confirm_password', this)" title="แสดง/ซ่อนรหัสผ่าน" style="border-radius: 0 10px 10px 0; border-color: #d9dee3;"><i class="bx bx-hide"></i></button>
                                      </div>
                                    </div>
                                  </div>
                                </div>

                                <!-- เปลี่ยนรหัส PIN 6 หลัก -->
                                <div class="bg-white p-3 rounded-3 border" style="box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                                  <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-bold text-dark m-0" style="font-size: 13.5px;">
                                      <i class='bx bx-dialpad text-warning me-1'></i> เปลี่ยนรหัส PIN 6 หลัก
                                    </label>
                                    <span class="badge bg-light text-muted border" style="font-size: 11px;">เว้นว่างได้</span>
                                  </div>
                                  <div class="row g-2">
                                    <div class="col-12 mb-2">
                                      <label class="form-label small text-muted mb-1">รหัส PIN 6 หลักใหม่</label>
                                      <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px;"><i class='bx bx-hash'></i></span>
                                        <input type="password" id="edit_pin" name="new_pin" class="form-control border-end-0" maxlength="6" pattern="\d{6}" placeholder="ตัวเลข 6 หลัก (เว้นว่างไว้ถ้าไม่เปลี่ยน)">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVisibility('edit_pin', this)" title="แสดง/ซ่อน PIN" style="border-radius: 0 10px 10px 0; border-color: #d9dee3;"><i class="bx bx-hide"></i></button>
                                      </div>
                                    </div>
                                    <div class="col-12">
                                      <label class="form-label small text-muted mb-1">ยืนยันรหัส PIN 6 หลักใหม่</label>
                                      <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px;"><i class='bx bx-check-shield'></i></span>
                                        <input type="password" id="confirm_pin" name="confirm_pin" class="form-control border-end-0" maxlength="6" pattern="\d{6}" placeholder="กรอกยืนยันตัวเลข PIN 6 หลัก">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVisibility('confirm_pin', this)" title="แสดง/ซ่อน PIN" style="border-radius: 0 10px 10px 0; border-color: #d9dee3;"><i class="bx bx-hide"></i></button>
                                      </div>
                                    </div>
                                  </div>
                                </div>

                              </form>
                            </div>

                            <!-- Modal Footer -->
                            <div class="modal-footer py-3 px-4 bg-white border-top d-flex align-items-center justify-content-between">
                              <button type="button" class="btn btn-light border px-4 py-2 rounded-3 fw-semibold text-secondary" data-bs-dismiss="modal" style="font-size: 13.5px;">
                                <i class='bx bx-x me-1'></i> ยกเลิก
                              </button>
                              <button type="button" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm" onclick="saveProfileData()" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; font-size: 13.5px;">
                                <i class='bx bx-save me-1'></i> บันทึกข้อมูล
                              </button>
                            </div>

                          </div>
                        </div>
                      </div>
                      <!-- จบ Modal แก้ไขข้อมูลส่วนตัว -->

                      <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;" role="document">
                          <div class="modal-content" style="border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.2); border: 1px solid #e2e8f0;">
                            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                              <div class="d-flex align-items-center gap-3">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);">
                                  <i class='bx bxs-message-square-detail'></i>
                                </div>
                                <div>
                                  <h5 class="modal-title mb-0 fw-bold text-dark" style="font-size: 1.15rem;">สอบถามปัญหา / ติดต่อสนับสนุน</h5>
                                  <small class="text-muted">ส่งข้อความถึงผู้ดูแลระบบ eDHS</small>
                                </div>
                              </div>
                              <button type="button" class="btn btn-sm btn-icon rounded-circle shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-size: 20px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5'; this.style.transform='scale(1.08)';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'; this.style.borderColor='#cbd5e1'; this.style.transform='scale(1)';">
                                <i class='bx bx-x' style="font-size: 22px; font-weight: bold;"></i>
                              </button>
                            </div>
                            <div class="modal-body p-4" style="background: #f8fafc;">
                              <form onsubmit="event.preventDefault(); Swal.fire('สำเร็จ', 'ส่งข้อความสอบถามเรียบร้อยแล้ว เจ้าหน้าที่จะติดต่อกลับโดยเร็ว', 'success'); $('#contactModal').modal('hide');">
                                <div class="mb-3">
                                  <label class="form-label fw-bold text-dark mb-1" style="font-size: 13px;"><i class='bx bx-user text-primary me-1'></i> ชื่อของคุณ</label>
                                  <input type="text" class="form-control rounded-3" placeholder="ระบุชื่อของคุณ" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-bold text-dark mb-1" style="font-size: 13px;"><i class='bx bx-envelope text-primary me-1'></i> อีเมลสำหรับติดต่อกลับ</label>
                                  <input type="email" class="form-control rounded-3" placeholder="name@example.com" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-bold text-dark mb-1" style="font-size: 13px;"><i class='bx bx-edit text-primary me-1'></i> คำถามหรือข้อความ</label>
                                  <textarea class="form-control rounded-3" rows="4" placeholder="พิมพ์ข้อความคำถามหรือปัญหาที่พบ..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;">
                                  <i class='bx bx-paper-plane me-1'></i> ส่งข้อความ
                                </button>
                              </form>
                            </div>
                          </div>
                        </div>
                      </div>

                      <?php echo render_changelog_modal(); ?>

<?php if (!empty($_SESSION['role'])) { ?>
<a href="logout.php" class="floating-logout-btn">
    <iconify-icon icon="solar:logout-2-bold" width="22" style="vertical-align: middle; margin-right: 8px;"></iconify-icon> 
</a>
<?php } ?>    <!-- Core JS -->
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
<!--     <script src="./chart/exporting.min.js"></script> -->
    <script src="./chart/accessibility.min.js"></script>



  <script>

      $(document).ready(function(){
        var yyy = '<?php echo $yyy; ?>';
        //fetch_data_nhso02(yyy);
        container01();
        container02();
        container03();
        container04();
      });

      function connmodal(){
        $('#connModal').modal('show');  
      }

      function openRegisterModal() {
          $('#registerModal').modal('show');
      }

      function openRegisterModal2() {
          $('#registerModal2').modal('show');
      }

      function openModallogin() {
          $('#loginModal').modal('show');
      }

      function openEditProfileModal() {
          $('#editProfileModal').modal('show');
      }

      function openModalContact() {
          $('#contactModal').modal('show');
      }




// ฟังก์ชันกลางสำหรับตั้งค่ากราฟ เพื่อลดความซ้ำซ้อนของโค้ด
function getChartConfig(tableId, gradientStart, gradientEnd) {
  return {
    data: { table: tableId },
    chart: { type: 'bar', height: 280, style: { fontFamily: 'Poppins, sans-serif' } },
    title: { text: '' },
    credits: { enabled: false },
    xAxis: {
      type: 'category',
      lineWidth: 0, // เอาเส้นแกนแนวตั้งออก
      tickWidth: 0, // เอาขีดๆ ออก
      labels: { style: { fontSize: '12px', color: '#566a7f' } }
    },
    yAxis: {
      allowDecimals: false,
      title: { text: '' },
      gridLineColor: '#f0f2f5', // สีเส้นกริดจางๆ
      labels: {
        formatter: function () {
          // แปลงตัวเลขยาวๆ เป็น M (ล้าน) แบบในรูป
          if (this.value >= 1000000) return (this.value / 1000000) + 'M';
          return this.value;
        },
        style: { fontSize: '11px', color: '#a1acb8' }
      }
    },
    plotOptions: {
      bar: { // ใช้ bar แทน series เพื่อเน้นเฉพาะกราฟแท่ง
        borderRadius: 4, // ขอบมน
        borderWidth: 0,
        colorByPoint: false, // ปิดการสุ่มสีแต่ละแท่ง
        dataLabels: {
          enabled: true,
          align: 'left',
          format: '{point.y:,.0f}',
          style: { color: '#32415d', fontSize: '11px', fontWeight: 'bold', textOutline: 'none' }
        }
      }
    },
    colors: [{
      // เปลี่ยนตัวเลขตรงนี้ครับ: ให้แกน Y ไล่จาก 1 ไป 0 แทน
      linearGradient: { x1: 0, y1: 1, x2: 0, y2: 0 }, 
      stops: [
        [0, gradientStart], // สีจะเริ่มฝั่งซ้าย
        [1, gradientEnd]    // แล้วค่อยๆ อ่อนลงไปฝั่งขวา
      ]
    }],
    legend: {
      enabled: true,
      verticalAlign: 'bottom',
      symbolRadius: 2, // สัญลักษณ์เป็นสี่เหลี่ยมขอบมนนิดๆ
      itemStyle: { color: '#697a8d', fontWeight: 'normal', fontSize: '12px' }
    },
    tooltip: {
      headerFormat: '<span style="font-size:12px">{point.key}</span><table>',
      pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' + '<td style="padding:0"><b>{point.y:,.2f}</b></td></tr>',
      footerFormat: '</table>',
      shared: true,
      useHTML: true
    }
  };
}

function container01() {
  Highcharts.chart('container01', getChartConfig('datatable', '#8c57ff', '#c4a5ff')); // สีม่วง
}

function container02() {
  Highcharts.chart('container02', getChartConfig('datatable02', '#ff914d', '#ffc299')); // สีส้ม
}

function container03() {
  Highcharts.chart('container03', getChartConfig('datatable03', '#eab308', '#fde047')); // สีเหลือง
}

function container04() {
  // เช็คก่อนว่ามีกล่อง id="container04" ให้วาดไหม ถ้ามีค่อยวาด
  if (document.getElementById('container04')) {
    Highcharts.chart('container04', getChartConfig('datatable04', '#3b82f6', '#93c5fd'));
  }
}


    </script>

    <script>
        // ฟังก์ชันช่วยอ่านค่าจาก URL
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

        function saveDebtorSetting() {
            var enabled = document.getElementById('debtorSettingEnabled').checked ? '1' : '0';
            var amount = document.getElementById('debtorSettingAmount').value;

            if(!amount || amount <= 0) {
                Swal.fire('ข้อผิดพลาด', 'กรุณาระบุจำนวนเงินให้ถูกต้อง', 'warning');
                return;
            }

            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: "save_debtor_setting.php",
                method: "POST",
                data: { enabled: enabled, amount: amount },
                dataType: "json",
                success: function(res) {
                    if(res.status === 'success') {
                        $('#modalDebtorSetting').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
                }
            });
        }
        
        function toggleVisibility(inputId, btn) {
            var input = document.getElementById(inputId);
            var icon = btn.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("bx-hide");
                icon.classList.add("bx-show");
            } else {
                input.type = "password";
                icon.classList.remove("bx-show");
                icon.classList.add("bx-hide");
            }
        }

        function saveProfileData() {
            var password = $('#edit_password').val();
            var confirmPassword = $('#confirm_password').val();
            var pin = $('#edit_pin').val();
            var confirmPin = $('#confirm_pin').val();
            
            if (password !== confirmPassword) {
                Swal.fire('ข้อผิดพลาด', 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน', 'error');
                return;
            }
            if (pin !== confirmPin) {
                Swal.fire('ข้อผิดพลาด', 'รหัส PIN ใหม่และการยืนยันรหัส PIN ไม่ตรงกัน', 'error');
                return;
            }

            var formData = $('#formEditProfile').serialize();
            
            Swal.fire({
                title: 'กำลังบันทึกข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: "update_profile.php",
                method: "POST",
                data: formData,
                dataType: "json",
                success: function(res) {
                    if(res.status === 'success') {
                        $('#editProfileModal').modal('hide');
                        
                        // อัปเดตชื่อที่มุมขวาบนให้ตรงกับชื่อใหม่ (ลบช่องว่างเพื่ออัปเดต span)
                        var newName = $('#edit_fullname').val();
                        $('.fw-semibold.d-block').text(newName);

                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // ถ้ามีการเปลี่ยนรหัสผ่าน สามารถเคลียร์ฟิลด์รหัสออก
                        $('#edit_password').val('');
                        $('#edit_pin').val('');
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
                }
            });
        }

        // ถ้ามีพารามิเตอร์ logout=success
        if (getQueryParam('logout') === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'ออกจากระบบสำเร็จ',
                showConfirmButton: false,
                timer: 2000
            });

            // ลบ query ออกจาก URL เพื่อไม่ให้ขึ้นซ้ำเมื่อ refresh
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('logout');
                window.history.replaceState({}, document.title, url.pathname);
            }
        }
    </script>

    <script>
      setTimeout(function() {
        document.getElementById('loader').classList.add('hide-loader');
      }, 10); 
    </script>



    <?php
    // กำหนดช่วงเวลาแสดงผล (25 ธ.ค. - 5 ม.ค.)
    $c_month = date('m');
    $c_day   = date('d');
    $show_confetti = false;

    if ( ($c_month == 12 && $c_day >= 31) || ($c_month == 1 && $c_day <= 9) ) {
        $show_confetti = true;
    }

    if ($show_confetti): 
        $thai_year = date("Y") + 543;
    ?>
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // สร้าง Key สำหรับจำค่ารายวัน เช่น seen_hny_2025-01-01
            var todayStr = new Date().toISOString().split('T')[0];
            var storageKey = 'seen_hny_' + todayStr;

            // เช็คว่าวันนี้เคยแสดงไปหรือยัง
            if (!localStorage.getItem(storageKey)) {
                
                // 1. สั่งยิงพลุกระดาษ (Confetti)
                var duration = 5 * 1000; // แสดงผล 5 วินาที
                var animationEnd = Date.now() + duration;
                var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

                function randomInRange(min, max) {
                  return Math.random() * (max - min) + min;
                }

                var interval = setInterval(function() {
                  var timeLeft = animationEnd - Date.now();

                  if (timeLeft <= 0) {
                    return clearInterval(interval);
                  }

                  var particleCount = 50 * (timeLeft / duration);
                  // ยิงจากซ้ายและขวาเข้ามา
                  confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                  confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
                }, 250);

                // 2. แสดง Toast เล็กๆ มุมขวาบน (ใช้ SweetAlert2 ที่พี่มีอยู่แล้ว)
                Swal.fire({
                    title: '<span style="font-size: 20px;">🎉 สวัสดีปีใหม่ '+ '<?php echo $thai_year; ?>' +'</span>',
                    html: '<span style="font-size: 15px;">ขอให้พี่มีความสุข รวยๆ เฮงๆ นะครับ</span>',
                    icon: 'success',
                    iconColor: '#ffffff', // ไอคอนสีขาว
                    background: 'linear-gradient(to right, #ff512f, #dd2476)', // พื้นหลังไล่สี แดง->ชมพูเข้ม
                    color: '#ffffff', // ตัวหนังสือสีขาว
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000, // โชว์นานขึ้นนิดนึง (5วิ)
                    timerProgressBar: true,
                    customClass: {
                        popup: 'colored-toast' // เผื่ออยากแต่ง CSS เพิ่ม
                    },
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                        // ปรับสี Progress Bar ให้เป็นสีขาวจางๆ
                        const progressBar = toast.querySelector('.swal2-timer-progress-bar');
                        if (progressBar) {
                            progressBar.style.backgroundColor = 'rgba(255, 255, 255, 0.5)';
                        }
                    }
                });

                // 3. บันทึกว่าวันนี้แสดงแล้ว
                localStorage.setItem(storageKey, 'true');
            }
        });
        </script>
    <?php endif; ?>


    <?php if($user_role == 'admin') { ?>
    <!-- Debtor Setting Modal -->
    <div class="modal fade" id="modalDebtorSetting" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;" role="document">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.2); border: 1px solid #e2e8f0;">
          <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.2);">
                <i class='bx bx-cog'></i>
              </div>
              <div>
                <h5 class="modal-title mb-0 fw-bold text-dark" style="font-size: 1.15rem;" id="modalDebtorSettingTitle">ตั้งค่าระบบลูกหนี้</h5>
                <small class="text-muted">กำหนดค่าภาระหนี้เริ่มต้นต่อ 1 Visit</small>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-icon rounded-circle shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-size: 20px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5'; this.style.transform='scale(1.08)';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'; this.style.borderColor='#cbd5e1'; this.style.transform='scale(1)';">
              <i class='bx bx-x' style="font-size: 22px; font-weight: bold;"></i>
            </button>
          </div>
          <div class="modal-body p-4" style="background: #f8fafc;">
            <div class="bg-white p-3 rounded-3 border mb-3" style="box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <label class="form-label fw-bold text-dark m-0" style="font-size: 13.5px;"><i class='bx bx-slider-alt text-primary me-1'></i> เปิดใช้งานตั้งค่าภาระหนี้</label>
                  <div class="small text-muted">เปิดหรือปิดการคำนวณภาระหนี้อัตโนมัติ</div>
                </div>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" id="debtorSettingEnabled" <?= $debtor_setting_enabled == "1" ? "checked" : "" ?> style="width: 2.75rem; height: 1.5rem; cursor: pointer;">
                </div>
              </div>
            </div>
            <div class="bg-white p-3 rounded-3 border" style="box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
              <label for="debtorSettingAmount" class="form-label fw-bold text-dark mb-2" style="font-size: 13.5px;">
                <i class='bx bx-money text-success me-1'></i> ค่าภาระหนี้ (ต่อ 1 Visit)
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px;"><i class='bx bx-dollar'></i></span>
                <input type="number" id="debtorSettingAmount" class="form-control" style="border-radius: 0 10px 10px 0;" value="<?= htmlspecialchars($debtor_setting_amount) ?>" placeholder="ระบุจำนวนเงิน (บาท)">
              </div>
            </div>
          </div>
          <div class="modal-footer py-3 px-4 bg-white border-top d-flex align-items-center justify-content-between">
            <button type="button" class="btn btn-light border px-4 py-2 rounded-3 fw-semibold text-secondary" data-bs-dismiss="modal" style="font-size: 13.5px;">
              <i class='bx bx-x me-1'></i> ยกเลิก
            </button>
            <button type="button" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm" onclick="saveDebtorSetting()" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; font-size: 13.5px;">
              <i class='bx bx-save me-1'></i> บันทึกการตั้งค่า
            </button>
          </div>
        </div>
      </div>
    </div>

    <script>
    function openDebtorSettingModal() {
        var myModal = new bootstrap.Modal(document.getElementById('modalDebtorSetting'));
        myModal.show();
    }

    function saveDebtorSetting() {
        var enabled = document.getElementById('debtorSettingEnabled').checked ? '1' : '0';
        var amount = document.getElementById('debtorSettingAmount').value;

        if(!amount || amount <= 0) {
            Swal.fire('ข้อผิดพลาด', 'กรุณาระบุจำนวนเงินให้ถูกต้อง', 'warning');
            return;
        }

        Swal.fire({
            title: 'กำลังบันทึก...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "save_debtor_setting.php",
            method: "POST",
            data: { enabled: enabled, amount: amount },
            dataType: "json",
            success: function(res) {
                if(res.status === 'success') {
                    $('#modalDebtorSetting').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกการตั้งค่าสำเร็จ',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // รีโหลดเพื่ออัปเดตหน้า Dashboard
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    }
    </script>
    <?php } ?>
    <?php if(!empty($_SESSION['role'])) { 
        include_once './modal_cr_settings.php';
        include_once './includes/modal_cr_patient_breakdown.php';
        include_once './modal_sss_settings.php';
        include_once './includes/modal_sss_patient_breakdown.php';
    } ?>

  </body>
</html>
