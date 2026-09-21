<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
if (function_exists('system_log')) {
    $pageName = basename($_SERVER['PHP_SELF']);
    system_log($conn, 'เปิดดูข้อมูล (View)', 'VIEW', "ผู้ใช้เปิดดูหน้ารายงาน/ข้อมูล: " . $pageName);
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบติดตามหนี้ค่ารักษาพยาบาล</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>

    <style>
        /* ==========================================================================
           eDHS System Font: Noto Sans Thai
           ========================================================================== */
        body,
        nav,
        .container,
        table, th, td,
        input, button, select, textarea,
        label, h1, h2, h3, h4, h5, h6,
        p, a, span, small, strong, b,
        .tab-btn,
        .modal, .modal * {
            font-family: 'Noto Sans Thai', sans-serif !important;
        }

        /* ป้องกันฟอนต์ไอคอน Font Awesome ไม่ให้ถูกทับ */
        .fa, .fas, .far, .fal, .fad, .fab,
        .fa:before, .fas:before, .far:before, .fal:before, .fad:before, .fab:before,
        i[class*="fa-"], i[class*="fa-"]:before {
            font-family: 'Font Awesome 6 Free' !important;
        }
        .fab, .fab:before {
            font-family: 'Font Awesome 6 Brands' !important;
        }

        /* เอกสารราชการพิมพ์จดหมาย A4 คงฟอนต์สารบรรณตามมาตรฐานราชการ */
        .a4-page, .a4-page *,
        .doc-preview, .doc-preview * {
            font-family: 'Sarabun', sans-serif !important;
        }

        body { background-color: #fdfdfd; }
        .tab-active { border-bottom: 2px solid #2563eb; color: #2563eb; font-weight: 600; }
        .tab-inactive { color: #6b7280; border-bottom: 2px solid transparent; }
        
        /* --- ตั้งค่าการพิมพ์ (แก้ปัญหาหน้าขาว) --- */
        @media print {
            body * { visibility: hidden; } /* ซ่อนทุกอย่าง */
            .no-print { display: none !important; }
            
            /* บังคับให้พื้นที่พิมพ์แสดงผล และจัดตำแหน่ง */
            #print-area, #print-area * { 
                visibility: visible !important; 
                display: block !important; 
            }
            #print-area { 
                position: absolute; 
                left: 0; 
                top: 0; 
                width: 100%; 
                margin: 0; 
                padding: 0;
                z-index: 9999;
                background-color: white;
            }
            
            @page { size: A4; margin: 0; }
            .a4-page {
                width: 210mm;
                height: 296mm; /* ลดลงนิดนึงกันล้น */
                padding: 2cm 2.5cm;
                margin: 0;
                background: white;
                font-family: 'Sarabun', sans-serif;
                font-size: 16pt;
                line-height: 1.6;
                color: #000;
                page-break-after: always;
            }
            
            /* จัดซองจดหมายตอนพิมพ์ */
            .envelope {
                width: 220mm !important;
                height: 110mm !important;
                border: none !important; /* ตอนพิมพ์จริงไม่ต้องมีเส้นขอบ */
            }
        }

        .doc-preview { 
            background: white; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            padding: 2cm 2.5cm; 
            width: 210mm; 
            min-height: 297mm; 
            margin: 20px auto; 
            font-family: 'Sarabun', sans-serif;
            font-size: 16pt;
            line-height: 1.6;
            color: #000;
        }

        .garuda { width: 3cm; height: auto; display: block; margin: 0 auto 0 auto; }
        .doc-indent { text-indent: 2.5cm; text-align: justify; }
        #settings-panel { transition: all 0.3s ease-in-out; }
    </style>
</head>
<body class="text-gray-800">

    <nav class="bg-blue-600 text-white shadow-lg no-print">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fas fa-hospital-alt text-2xl"></i>
                <div>
                    <h1 class="font-bold text-xl">ติดตามหนี้ค่ารักษาพยาบาล</h1>
                    <p class="text-xs text-blue-200"><?php echo $hospital; ?> (Online)</p>
                </div>
            </div>
            <div class="text-sm">ผู้ใช้งาน: <span class="font-bold">เจ้าหน้าที่การเงิน/เจ้าหน้าที่งานประกันฯ</span></div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6 no-print">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500"><p class="text-gray-500 text-sm">จำนวนราย</p><h2 class="text-2xl font-bold" id="dash-cases">0 ราย</h2></div>
            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500"><p class="text-gray-500 text-sm">ยอดหนี้คงค้างรวม</p><h2 class="text-2xl font-bold text-red-600" id="dash-debt">0.00</h2></div>
            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500"><p class="text-gray-500 text-sm">ยอดที่ได้รับชำระแล้ว</p><h2 class="text-2xl font-bold text-green-600" id="dash-paid">0.00</h2></div>
        </div>

        <div class="bg-white rounded-t-lg shadow-sm border-b mb-0 px-4 pt-2 flex gap-6">
            <button onclick="switchTab('opd')" id="tab-opd" class="tab-btn tab-active py-3 px-2 font-medium transition-colors"><i class="fas fa-walking mr-2"></i>ผู้ป่วยนอก (OPD)</button>
            <button onclick="switchTab('ipd')" id="tab-ipd" class="tab-btn tab-inactive py-3 px-2 font-medium transition-colors"><i class="fas fa-bed mr-2"></i>ผู้ป่วยใน (IPD)</button>
        </div>

        <div id="loadingSpinner" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white p-4 rounded-lg shadow-lg flex items-center gap-3"><i class="fas fa-spinner fa-spin text-blue-600 text-2xl"></i><span>กำลังโหลด...</span></div>
        </div>

        <div class="bg-white p-4 shadow mb-6 rounded-b-lg border-t-0">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-auto"><label class="block text-sm text-gray-600 mb-1">ตั้งแต่วันที่</label><div class="relative"><input type="text" id="startDate" class="thai-datepicker border rounded px-3 py-2 text-sm w-full md:w-40 pl-10"><i class="fas fa-calendar-alt absolute left-3 top-2.5 text-gray-400"></i></div></div>
                <div class="w-full md:w-auto"><label class="block text-sm text-gray-600 mb-1">ถึงวันที่</label><div class="relative"><input type="text" id="endDate" class="thai-datepicker border rounded px-3 py-2 text-sm w-full md:w-40 pl-10"><i class="fas fa-calendar-alt absolute left-3 top-2.5 text-gray-400"></i></div></div>
                <div class="flex gap-2 w-full md:w-auto"><button onclick="loadData(1)" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 shadow"><i class="fas fa-filter"></i></button><button onclick="clearDates()" class="bg-gray-200 text-gray-700 px-3 py-2 rounded text-sm hover:bg-gray-300">ล้าง</button></div>
                <div class="w-full md:flex-1"><label class="block text-sm text-gray-600 mb-1">คำค้นหา</label><div class="relative"><i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i><input type="text" id="searchInput" placeholder="ค้นหาชื่อ, HN..." class="w-full pl-10 pr-4 py-2 border rounded text-sm"></div></div>
                <div class="w-full md:w-auto"><label class="block text-sm text-gray-600 mb-1">สถานะ</label><select id="statusFilter" class="w-full border rounded px-4 py-2 bg-white text-sm"><option value="all">ทั้งหมด</option><option value="unpaid">ค้างชำระ</option><option value="paid">ชำระครบ</option></select></div>
                <div class="flex gap-2 w-full md:w-auto"><button onclick="exportToExcel()" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700 shadow flex items-center gap-1"><i class="fas fa-file-excel"></i> Excel</button></div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-sm" style="color: #fff; background-color: #3963bf;">
                        <tr><th class="p-4 border-b text-center w-16">ลำดับ</th><th class="p-4 border-b">ประเภท</th><th class="p-4 border-b">วันที่</th><th class="p-4 border-b">HN / ชื่อ-สกุล</th><th class="p-4 border-b">สิทธิ</th><th class="p-4 border-b text-right">ยอดหนี้</th><th class="p-4 border-b text-right">ชำระแล้ว</th><th class="p-4 border-b text-right">คงเหลือ</th><th class="p-4 border-b text-center" style="width: 8%;">สถานะ</th><th class="p-4 border-b text-center"style="width: 12%;">จัดการ</th></tr>
                    </thead>
                    <tbody id="debtTableBody" class="text-gray-600"></tbody>
                </table>
            </div>
            <div class="p-4 border-t flex justify-between items-center text-sm text-gray-500">
                <span id="tableInfo">รอโหลด...</span><div id="paginationContainer" class="flex gap-2"></div>
            </div>
        </div>
    </div>

    <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 no-print">
        <div class="bg-white rounded-lg w-full max-w-md p-6 shadow-xl relative">
            <button onclick="closeModal('uploadModal')" class="absolute top-4 right-4 text-gray-400"><i class="fas fa-times"></i></button>
            <h3 class="text-xl font-bold mb-4 border-b pb-2">แนบเอกสาร</h3>
            <form id="uploadForm" onsubmit="handleUpload(event)">
                <input type="hidden" id="up_id"><input type="hidden" id="up_type">
                <div class="mb-4"><label>ไฟล์</label><input type="file" id="up_file" class="w-full border rounded p-2" accept=".pdf,.jpg,.jpeg,.png" required></div>
                <div class="flex justify-end gap-3"><button type="button" onclick="closeModal('uploadModal')" class="px-4 py-2 bg-gray-200 rounded">ยกเลิก</button><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">อัปโหลด</button></div>
            </form>
        </div>
    </div>

    <div id="docModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto z-50 no-print">
        <div class="min-h-screen px-4 text-center">
            <span class="inline-block h-screen align-middle">&#8203;</span>
            <div class="inline-block w-[95%] max-w-[95%] p-6 my-8 text-left align-middle bg-gray-100 shadow-xl rounded-lg">
                <!-- Header with Close Button -->
                <div class="flex justify-between items-center mb-4 border-b border-gray-300 pb-3">
                    <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-file-pdf text-red-500 mr-2"></i> พิมพ์หนังสือติดตามหนี้</h2>
                    <button onclick="closeModal('docModal')" class="text-gray-400 hover:text-red-600 transition-colors"><i class="fas fa-times fa-lg"></i></button>
                </div>

                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Left Column: Controls & Settings -->
                    <div id="doc-controls" class="w-full lg:w-4/12 bg-white p-5 rounded shadow border border-gray-200 max-h-[85vh] overflow-y-auto">
                        <div class="flex gap-2 mb-4">
                            <button onclick="switchDocType('letter')" id="btn-letter" class="flex-1 px-4 py-2 rounded font-bold bg-blue-600 text-white">หนังสือทวงถาม</button>
                            <button onclick="switchDocType('envelope')" id="btn-envelope" class="flex-1 px-4 py-2 rounded font-bold bg-gray-200 text-gray-700">ซองจดหมาย</button>
                        </div>
                        
                        <div id="letter-form" class="flex flex-col gap-4 mb-4 border-b pb-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">เลขที่หนังสือ</label>
                                <div class="flex items-center">
                                    <span class="text-gray-500 mr-2 bg-gray-100 px-2 py-2 rounded-l border" id="preview-prefix-label">ที่ รอ.๐๐๓๓.๓๐๔ /</span>
                                    <input style="width: 56%;"  type="text" id="docRunNumber" class="border-y border-r rounded-r px-3 py-2 w-full" placeholder="ระบุเลข..." onkeyup="updateDocPreview()">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">ลงวันที่</label>
                                <input style="width: 56%;" type="text" id="docDateInput" class="thai-datepicker border rounded px-3 py-2 w-full" placeholder="เลือกวันที่..." onchange="updateDocPreview()">
                            </div>
                            <button onclick="markAsPrinted()" class="w-full px-6 py-3 bg-green-600 text-white rounded hover:bg-green-700 shadow font-bold text-base"><i class="fas fa-print"></i> พิมพ์เอกสาร</button>
                        </div>

                        <div id="envelope-form" class="hidden flex-col mb-4 border-b pb-4 bg-gray-50 p-4 rounded border border-gray-200">
                            <h3 class="font-bold text-gray-700 text-base mb-3"><i class="fas fa-envelope text-blue-600 mr-2"></i>แก้ไขข้อมูลหน้าซองจดหมาย</h3>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">ชื่อผู้รับ <span class="text-xs font-normal text-red-500">(ถ้าไม่กรอก จะใช้ชื่อตามระบบ)</span></label>
                                    <input type="text" id="conf-env-name" class="w-full border rounded px-2 py-1" oninput="updateDocPreview()">
                                </div>
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">ที่อยู่ผู้รับ <span class="text-xs font-normal text-red-500">(ถ้าไม่กรอก จะดึงจากฐานข้อมูลผู้ป่วย)</span></label>
                                    <textarea id="conf-env-addr" rows="4" class="w-full border rounded px-2 py-1" oninput="updateDocPreview()"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Panel (Always visible in Left Column for Letter) -->
                        <div id="settings-panel" class="bg-yellow-50 p-4 rounded border border-yellow-200 text-sm">
                            <div class="flex items-center gap-2 mb-3 border-b border-yellow-200 pb-2">
                                <i class="fas fa-cog text-yellow-600"></i>
                                <h3 class="font-bold text-gray-700 text-base">ตั้งค่าเนื้อหา</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">ส่วนหัว (เลขที่หนังสือ)</label>
                                    <input type="text" id="conf-header-prefix" class="w-full border rounded px-2 py-1" oninput="saveConfig()">
                                </div>
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">ชื่อผู้อำนวยการหรือรักษาการแทน</label>
                                    <input type="text" id="conf-director" class="w-full border rounded px-2 py-1" oninput="saveConfig()">
                                </div>
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">ตำแหน่งผู้อำนวยการหรือรักษาการแทน</label>
                                    <textarea id="conf-director-position" rows="2" class="w-full border rounded px-2 py-1" oninput="saveConfig()"></textarea>
                                </div>
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">ที่อยู่โรงพยาบาล (ส่วนหัวขวา)</label>
                                    <textarea id="conf-hospital-addr" rows="4" class="w-full border rounded px-2 py-1" oninput="saveConfig()"></textarea>
                                </div>
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">เรื่อง</label>
                                    <input type="text" id="conf-subject" class="w-full border rounded px-2 py-1" oninput="saveConfig()">
                                </div>
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">รูปแบบเนื้อหาจดหมาย <b style=" color: #ad5900;">(เลือกตามสิทธิ)</b></label>
                                    <select id="conf-template-type" class="w-full border rounded px-2 py-1" onchange="changeTemplateType()">
                                        <option value="default">รูปแบบเดิม (กำหนดเนื้อหาเอง)</option>
                                        <option value="prb">สิทธิ พ.ร.บ. (ประสบภัยจากรถ)</option>
                                        <option value="self_pay">สิทธิชำระเงินเอง</option>
                                    </select>
                                </div>
                                <div class="bg-blue-50 p-3 rounded border border-blue-200">
                                    <label class="block text-blue-800 mb-1 font-bold">เนื้อหาย่อหน้า 1 (หลัก)</label>
                                    <textarea id="conf-body-main" rows="12" class="w-full border rounded px-2 py-1" oninput="saveConfig()"></textarea>
                                </div>
                                <div id="div-body-2">
                                    <label class="block text-gray-600 mb-1 font-bold">เนื้อหาย่อหน้า 2 (การชำระเงิน)</label>
                                    <textarea id="conf-body-2" rows="5" class="w-full border rounded px-2 py-1" oninput="saveConfig()"></textarea>
                                </div>
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">เนื้อหาย่อหน้า 3 (คำลงท้าย)</label>
                                    <textarea id="conf-body-3" rows="3" class="w-full border rounded px-2 py-1" oninput="saveConfig()"></textarea>
                                </div>
                                <div>
                                    <label class="block text-gray-600 mb-1 font-bold">ส่วนท้าย (ข้อมูลติดต่อ)</label>
                                    <textarea id="conf-footer" rows="3" class="w-full border rounded px-2 py-1" oninput="saveConfig()"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: PDF Preview -->
                    <div class="w-full lg:w-8/12 bg-white rounded shadow border border-gray-200 flex flex-col">
                        <div id="preview-container" class="flex-grow min-h-[85vh]"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="print-area" style="display:none;"></div>

    <script>


    let docConfig = {
        headerPrefix: "ที่ รอ.๐๐๓๓.๓๐๔ /",
        director: "นายเกรียงไกร ศรีวิลัย",
        directorPosition: "ผู้อำนวยการโรงพยาบาลโพนทราย",
        hospitalAddr: "โรงพยาบาลโพนทราย\n๑๐๔ หมู่ ๙ ตำบลโพนทราย\nอำเภอโพนทราย\nจังหวัดร้อยเอ็ด ๔๕๒๔๐", // [ใหม่]
        subject: "ขอให้ชำระค่ารักษาพยาบาล",
        templateType: "default",
        tpl_default: "ด้วย [ชื่อผู้ป่วย] ได้เข้ารับการรักษาพยาบาลที่[ชื่อโรงพยาบาล] ตั้งแต่วันที่ [วันที่รับบริการ] ได้ค้างชำระค่าบริการรักษาพยาบาลเป็นจำนวนเงินทั้งสิ้น [ยอดเงิน] บาท ([ตัวอักษรยอดเงิน])",
        tpl_prb: "ตามที่ท่านได้เข้ารับการรักษาพยาบาลที่[ชื่อโรงพยาบาล] เมื่อวันที่ [วันที่รับบริการ] เนื่องจากเป็นผู้ประสบภัยจากรถ และตามกฎหมาย พ.ร.บ. คุ้มครองผู้ประสบภัยจากรถ ในกรณีที่รถไม่มี พ.ร.บ. ท่านต้องชำระค่ารักษาพยาบาลเอง โดยมีค่าใช้จ่ายที่ต้องชำระ [ยอดเงิน] บาท ([ตัวอักษรยอดเงิน])\n\nจึงขอให้ท่านได้ชำระค่ารักษาพยาบาลที่ยังค้างจ่ายดังกล่าว หากท่านได้ชำระแล้ว กรุณาติดต่อและแสดงหลักฐานการชำระเงิน ที่งานประกันสุขภาพฯ [ชื่อโรงพยาบาล] ในวันและเวลาราชการ เพื่อปรับปรุงข้อมูลลูกหนี้ค้างชำระต่อไป",
        tpl_self_pay: "ตามที่ท่านได้เข้ารับการรักษาพยาบาลที่[ชื่อโรงพยาบาล] เมื่อวันที่ [วันที่รับบริการ] มีค่ารักษาพยาบาลค้างชำระ [ยอดเงิน] บาท ([ตัวอักษรยอดเงิน])\n\nจึงขอให้ท่านได้ชำระค่าบริการรักษาพยาบาลที่ยังค้างจ่าย หากท่านได้ชำระเงินค่ารักษาพยาบาลจำนวนดังกล่าวแล้ว กรุณาติดต่อและแสดงหลักฐานการชำระเงิน ที่งานประกันสุขภาพฯ [ชื่อโรงพยาบาล] ในวันและเวลาราชการ เพื่อปรับปรุงข้อมูลลูกหนี้ค้างชำระต่อไป",
        body2: "เวลาได้ล่วงเลยมานานแล้วจึงขอความอนุเคราะห์มายังท่านให้มาชำระเงินที่ค้างชำระดังกล่าว เมื่อท่านได้รับหนังสือฉบับดังกล่าวนี้ ขอให้ท่านมาติดต่อห้องจ่ายเงินโรงพยาบาลโพนทรายในเวลาราชการ เวลา ๐๘.๓๐ – ๑๖.๐๐ น",
        body3: "จึงเรียนมาเพื่อโปรดทราบ และหวังเป็นอย่างยิ่งว่าจะได้รับความร่วมมือจากท่านเป็นอย่างดี",
        footer: "ฝ่ายบริหารงานทั่วไป (การเงินฯ)\nโทร. ๐๔๓-๕๙๕๐๗๓ ต่อ ๑๐๘\nโทรสาร ๐๔๓-๕๙๕๐๗๓ ต่อ ๑๓๐"
    };

    if (localStorage.getItem('docConfig')) {
        try { 
            const storedConfig = JSON.parse(localStorage.getItem('docConfig'));
            if (!storedConfig.tpl_default) storedConfig.tpl_default = docConfig.tpl_default;
            if (!storedConfig.tpl_prb) storedConfig.tpl_prb = docConfig.tpl_prb;
            if (!storedConfig.tpl_self_pay) storedConfig.tpl_self_pay = docConfig.tpl_self_pay;
            docConfig = { ...docConfig, ...storedConfig }; 
        } catch(e) {}
    }

    let currentPage = 1, currentType = 'opd', currentStatus = 'all', globalCurrentData = [], currentSelectedId, currentSelectedType, currentDocType = 'letter';

    $(document).ready(function() {
        loadData(1);
        $('#conf-header-prefix').val(docConfig.headerPrefix); 
        $('#conf-director').val(docConfig.director);
        $('#conf-director-position').val(docConfig.directorPosition || "ผู้อำนวยการโรงพยาบาลโพนทราย");
        $('#conf-subject').val(docConfig.subject);
        $('#conf-template-type').val(docConfig.templateType || 'default');
        
        // Initial body main
        if (docConfig.templateType === 'prb') $('#conf-body-main').val(docConfig.tpl_prb);
        else if (docConfig.templateType === 'self_pay') $('#conf-body-main').val(docConfig.tpl_self_pay);
        else $('#conf-body-main').val(docConfig.tpl_default);

        $('#conf-body-2').val(docConfig.body2); $('#conf-body-3').val(docConfig.body3); $('#conf-footer').val(docConfig.footer);
        $('#conf-hospital-addr').val(docConfig.hospitalAddr); 
        $('#preview-prefix-label').text(docConfig.headerPrefix);
        toggleBody2Display();
        $('#searchInput').on('keyup', function(){setTimeout(()=>{currentPage=1;loadData(1);},500);});
        $('#statusFilter').on('change', function(){currentStatus=$(this).val();currentPage=1;loadData(1);});
        initFlatpickr();
    });

    function initFlatpickr(){flatpickr(".thai-datepicker",{locale:"th",dateFormat:"Y-m-d",altInput:true,altFormat:"j F Y",disableMobile:true,onReady:(d,s,i)=>replaceYearToThai(i),onValueUpdate:(d,s,i)=>{replaceYearToThai(i);if(d[0])i.altInput.value=`${d[0].getDate()} ${i.l10n.months.longhand[d[0].getMonth()]} ${d[0].getFullYear()+543}`;},onMonthChange:(d,s,i)=>replaceYearToThai(i),onYearChange:(d,s,i)=>replaceYearToThai(i)});}
    function replaceYearToThai(i){if(i.currentYearElement)i.currentYearElement.value=i.currentYear+543;}

    function loadData(page){currentPage=page;$('#loadingSpinner').removeClass('hidden').addClass('flex');
        $.ajax({url:'api_get_debtors.php',type:'GET',data:{page,type:currentType,status:currentStatus,search:$('#searchInput').val(),start_date:$('#startDate').val(),end_date:$('#endDate').val()},dataType:'json',success:function(r){$('#loadingSpinner').removeClass('flex').addClass('hidden');globalCurrentData=r.data;renderTableAJAX(r.data);renderPagination(r.currentPage,r.totalPages,r.totalRows);if(r.summary){$('#dash-cases').text(r.summary.total_cases+" ราย");$('#dash-debt').text(formatMoney(r.summary.total_debt));$('#dash-paid').text(formatMoney(r.summary.total_paid));}},error:function(){$('#loadingSpinner').removeClass('flex').addClass('hidden');}});}

    function renderTableAJAX(data){const t=$('#debtTableBody');t.empty();if(data.length===0){t.html('<tr><td colspan="10" class="text-center p-4">ไม่พบข้อมูล</td></tr>');return;}
        data.forEach((item,i)=>{
            const bal=parseFloat(item.balance), rn=((currentPage-1)*50)+(i+1);
            const badge=bal>0.01?'<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">ค้างชำระ</span>':'<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">ชำระครบ</span>';
            const prt=item.print_date?`<span class="inline-flex items-center text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded border border-blue-200 mr-1"><i class="fas fa-print mr-1"></i> พิมพ์แล้ว</span>`:'';
            const fileBtn=item.doc_url?`<a href="${item.doc_url}" target="_blank" class="text-indigo-600 p-1"><i class="fas fa-file-alt fa-lg"></i></a><button onclick="openUploadModal('${item.id}','${item.type}')" class="text-yellow-600 p-1"><i class="fas fa-edit fa-lg"></i></button><button onclick="deleteDocument('${item.id}','${item.type}')" class="text-red-600 p-1"><i class="fas fa-trash-alt fa-lg"></i></button>`:`<button onclick="openUploadModal('${item.id}','${item.type}')" class="text-gray-500 p-1"><i class="fas fa-paperclip fa-lg"></i></button>`;
            const docSt=item.doc_url?`<span class="inline-flex items-center text-xs text-green-600 bg-green-50 px-2 py-1 rounded border border-green-200"><i class="fas fa-check-circle mr-1"></i> ตอบรับแล้ว</span>`:'';
            t.append(`<tr class="border-b hover:bg-gray-50"><td class="p-4 text-center text-gray-500">${rn}</td><td class="p-4 font-bold text-blue-600">${item.type}</td><td class="p-4">${formatThaiDate(item.date_serv)}</td><td class="p-4"><div class="font-bold">${item.ptname}</div><div class="text-xs text-gray-500">HN: ${item.hn}</div><div class="mt-1">${prt} ${docSt}</div></td><td class="p-4 text-sm text-gray-600">${item.pttypename||'-'}</td><td class="p-4 text-right">${formatMoney(item.income)}</td><td class="p-4 text-right text-green-600">${formatMoney(item.rcpt_money)}</td><td class="p-4 text-right font-bold ${bal>0?'text-red-600':'text-gray-400'}">${formatMoney(bal)}</td><td class="p-4 text-center">${badge}</td><td class="p-4 text-center gap-2"><button onclick="openDocModal('${item.id}','${item.type}')" class="text-blue-600 p-1"><i class="fas fa-print fa-lg"></i></button>${fileBtn}</td></tr>`);
        });}

    function openDocModal(id,type){currentSelectedId=id;currentSelectedType=type;$('#docRunNumber').val('');$('#conf-env-name').val('');$('#conf-env-addr').val('');const t=new Date();const dp=document.querySelector("#docDateInput")._flatpickr;if(dp)dp.setDate(t,true);switchDocType('letter');$('#docModal').removeClass('hidden').addClass('block');}
    function switchDocType(t){currentDocType=t;if(t==='letter'){$('#btn-letter').addClass('bg-blue-600 text-white').removeClass('bg-gray-200 text-gray-700');$('#btn-envelope').removeClass('bg-blue-600 text-white').addClass('bg-gray-200 text-gray-700');$('#letter-form').removeClass('hidden').addClass('flex flex-col');$('#envelope-form').addClass('hidden');$('#settings-panel').removeClass('hidden');}else{$('#btn-envelope').addClass('bg-blue-600 text-white').removeClass('bg-gray-200 text-gray-700');$('#btn-letter').removeClass('bg-blue-600 text-white').addClass('bg-gray-200 text-gray-700');$('#letter-form').addClass('hidden').removeClass('flex flex-col');$('#envelope-form').removeClass('hidden');$('#settings-panel').addClass('hidden');}generateDocumentPreview();}
    
    function generateDocumentPreview() {
        const item = globalCurrentData.find(d => d.id == currentSelectedId && d.type == currentSelectedType);
        if(!item) return;

        const balance = item.balance;
        const runNumber = $('#docRunNumber').val() || '';
        
        let docDateVal = "";
        const dp = document.querySelector("#docDateInput")._flatpickr;
        if(dp && dp.selectedDates[0]) {
             const d = dp.selectedDates[0];
             const year = d.getFullYear();
             const month = String(d.getMonth() + 1).padStart(2, '0');
             const day = String(d.getDate()).padStart(2, '0');
             docDateVal = `${year}-${month}-${day}`;
        } else {
             docDateVal = new Date().toISOString().split('T')[0];
        }

        // [สำคัญ] รวมค่า Config เข้าไปใน Params
        let bodyMain = "";
        if (docConfig.templateType === 'default') bodyMain = docConfig.tpl_default;
        else if (docConfig.templateType === 'prb') bodyMain = docConfig.tpl_prb;
        else if (docConfig.templateType === 'self_pay') bodyMain = docConfig.tpl_self_pay;

        const formData = {
            mode: currentDocType,
            name: item.ptname,
            totalpay: balance,
            date_serv: item.date_serv,
            hn: item.hn,
            pttype_name: item.pttypename,
            run_number: runNumber,
            doc_date: docDateVal,
            header_prefix: docConfig.headerPrefix,
            director_name: docConfig.director,
            director_position: docConfig.directorPosition || "ผู้อำนวยการโรงพยาบาลโพนทราย",
            hospital_addr: docConfig.hospitalAddr,
            subject: docConfig.subject,
            template_type: docConfig.templateType || 'default',
            body_main: bodyMain,
            body2: docConfig.body2,
            body3: docConfig.body3,
            footer_contact: docConfig.footer,
            env_name: $('#conf-env-name').val(),
            env_addr: $('#conf-env-addr').val()
        };

        $('#preview-container').html(`
            <iframe id="pdf-frame" name="pdf-frame" style="width: 100%; height: 100%; min-height: 85vh; border: none; background: #525659; border-radius: 0.25rem;"></iframe>
        `);

        let form = $('<form></form>', {
            action: 'printPDF_debtors.php',
            target: 'pdf-frame',
            method: 'POST',
            style: 'display: none;'
        });

        $.each(formData, function(name, value) {
            form.append($('<input>', {
                type: 'hidden',
                name: name,
                value: value
            }));
        });

        form.appendTo('body').submit().remove();
    }

    function updateDocPreview(){generateDocumentPreview();}
    function markAsPrinted() {
        // 1. บันทึกสถานะลงฐานข้อมูล
        $.post('api_document_action_debtors.php', { 
            action: 'mark_printed', 
            id: currentSelectedId, 
            type: currentSelectedType 
        }, function() {
            loadData(currentPage); // รีเฟรชตารางหลังบ้าน
        });

        // 2. สั่งพิมพ์จาก iframe
        const iframe = document.getElementById('pdf-frame');
        if (iframe) {
            iframe.contentWindow.print();
        }
    }
    function toggleSettings(){$('#settings-panel').toggleClass('hidden');}
    function toggleBody2Display() {
        if ($('#conf-template-type').val() === 'default') {
            $('#div-body-2').show();
        } else {
            $('#div-body-2').hide();
        }
    }
    function changeTemplateType() {
        const type = $('#conf-template-type').val();
        docConfig.templateType = type;
        if (type === 'default') $('#conf-body-main').val(docConfig.tpl_default);
        else if (type === 'prb') $('#conf-body-main').val(docConfig.tpl_prb);
        else if (type === 'self_pay') $('#conf-body-main').val(docConfig.tpl_self_pay);
        saveConfig();
    }
    function saveConfig() {
        docConfig.headerPrefix = $('#conf-header-prefix').val();
        docConfig.director = $('#conf-director').val();
        docConfig.directorPosition = $('#conf-director-position').val();
        docConfig.hospitalAddr = $('#conf-hospital-addr').val(); // [ใหม่]
        docConfig.subject = $('#conf-subject').val();
        
        const type = $('#conf-template-type').val();
        docConfig.templateType = type;
        if (type === 'default') docConfig.tpl_default = $('#conf-body-main').val();
        else if (type === 'prb') docConfig.tpl_prb = $('#conf-body-main').val();
        else if (type === 'self_pay') docConfig.tpl_self_pay = $('#conf-body-main').val();

        docConfig.body2 = $('#conf-body-2').val();
        docConfig.body3 = $('#conf-body-3').val();
        docConfig.footer = $('#conf-footer').val();
        
        localStorage.setItem('docConfig', JSON.stringify(docConfig));
        $('#preview-prefix-label').text(docConfig.headerPrefix);
        toggleBody2Display();
        updateDocPreview();
    }
    // Helpers
    function switchTab(t){currentType=t;currentPage=1;$('.tab-btn').removeClass('tab-active').addClass('tab-inactive');$(`#tab-${t}`).removeClass('tab-inactive').addClass('tab-active');loadData(1);}
    function clearDates(){$('#startDate').val('');$('#endDate').val('');loadData(1);}
    function exportToExcel(){window.location.href=`export_excel_debtors.php?type=${currentType}&status=${currentStatus}&search=${$('#searchInput').val()}&start_date=${$('#startDate').val()}&end_date=${$('#endDate').val()}`;}
    function closeModal(id){$(`#${id}`).removeClass('flex block').addClass('hidden');}
    function openUploadModal(id,type){$('#up_id').val(id);$('#up_type').val(type);$('#up_file').val('');$('#uploadModal').removeClass('hidden').addClass('flex');}
    function handleUpload(e){e.preventDefault();const fd=new FormData();fd.append('action','upload');fd.append('id',$('#up_id').val());fd.append('type',$('#up_type').val());fd.append('file',$('#up_file')[0].files[0]);$('#loadingSpinner').removeClass('hidden').addClass('flex');$.ajax({url:'api_document_action_debtors.php',type:'POST',data:fd,contentType:false,processData:false,success:function(r){$('#loadingSpinner').removeClass('flex').addClass('hidden');if(r.status==='success'){closeModal('uploadModal');loadData(currentPage);}else alert(r.message);},error:function(){$('#loadingSpinner').removeClass('flex').addClass('hidden');}});}
    function deleteDocument(id,type){if(!confirm('ลบไฟล์?'))return;$.post('api_document_action_debtors.php',{action:'delete_file',id,type},function(r){if(r.status==='success')loadData(currentPage);});}
    function toThaiNum(n){if(n==null)return'';return n.toString().replace(/[0-9]/g,d=>'๐๑๒๓๔๕๖๗๘๙'[d]);}
    const formatMoney=(n)=>parseFloat(n).toLocaleString('th-TH',{minimumFractionDigits:2,maximumFractionDigits:2});
    function renderPagination(c,tp,tr){$('#tableInfo').text(`หน้า ${c}/${tp} (${tr} ราย)`);let b='';b+=`<button onclick="loadData(${c-1})" class="px-3 py-1 border rounded hover:bg-gray-100" ${c===1?'disabled':''}>&lt;</button>`;b+=`<button class="px-3 py-1 border rounded bg-blue-600 text-white">${c}</button>`;b+=`<button onclick="loadData(${c+1})" class="px-3 py-1 border rounded hover:bg-gray-100" ${c===tp?'disabled':''}>&gt;</button>`;$('#paginationContainer').html(b);}
    
    // Date Formatters (Improved)
    const formatThaiDate = (s) => {
        if(!s || s==='NaN' || s==='undefined') return "-";
        if(s.includes('/')) return s; // ถ้ามาเป็นไทยอยู่แล้ว
        const d = new Date(s);
        if(isNaN(d.getTime())) return s; // ถ้าแปลงไม่ได้ คืนค่าเดิม
        return `${d.getDate()} ${["ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."][d.getMonth()]} ${d.getFullYear()+543}`;
    };
    
    // [แก้ไขใหม่] แก้บั๊ก NaN ในจดหมาย
    const formatThaiDateFull = (s) => {
        if(!s || s === 'NaN' || s === 'undefined') return ".........";
        // ถ้ามาเป็น DD/MM/YYYY (พ.ศ.)
        if(s.includes('/')) {
            const p=s.split('/');
            if(p.length===3) return `${parseInt(p[0])} ${["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"][parseInt(p[1])-1]} ${p[2]}`;
        }
        // ถ้ามาเป็น YYYY-MM-DD (ค.ศ.)
        const d = new Date(s);
        if(isNaN(d.getTime())) return s;
        return `${d.getDate()} ${["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"][d.getMonth()]} ${d.getFullYear()+543}`;
    };

    function ArabicNumberToText(n){n=parseFloat(String(n).replace(/,/g,''));if(isNaN(n))return"";if(n>9999999.9999)return"เกินขอบเขต";const s=String(n).split('.');let b="";const num=["ศูนย์","หนึ่ง","สอง","สาม","สี่","ห้า","หก","เจ็ด","แปด","เก้า","สิบ"];const dig=["","สิบ","ร้อย","พัน","หมื่น","แสน","ล้าน"];const len=s[0].length;for(let i=0;i<len;i++){const t=s[0][i]-0;if(t!=0){if(i==len-1&&t==1&&len>1)b+="เอ็ด";else if(i==len-2&&t==2)b+="ยี่";else if(i==len-2&&t==1)b+="";else b+=num[t];b+=dig[len-i-1];}}b+="บาท";if(!s[1]||s[1]=="0"||s[1]=="00")b+="ถ้วน";else{const dLen=s[1].length;for(let i=0;i<dLen;i++){const t=s[1][i]-0;if(t!=0){if(i==dLen-1&&t==1&&dLen>1)b+="เอ็ด";else if(i==dLen-2&&t==2)b+="ยี่";else if(i==dLen-2&&t==1)b+="";else b+=num[t];b+=dig[dLen-i-1];}}b+="สตางค์";}return b;}
    </script>
</body>
</html>