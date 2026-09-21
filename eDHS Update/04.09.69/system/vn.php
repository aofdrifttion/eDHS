<?php
error_reporting(E_ALL);
require './database_config/config.php';
require_once './database_config/db_helper.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// [ปรับปรุง 1] แยกตัวแปร: ตัวนึงเก็บค่าเดิม(raw) อีกตัวเก็บค่าเติมศูนย์(padded)
$search_raw = $search; 
$search_padded = $search; 

// ถ้าเป็นตัวเลขและไม่ถึง 9 หลัก ให้สร้างตัวแปร padded ไว้ค้นหา HN แบบ 000000xxx
if (!empty($search) && strlen($search) < 9 && is_numeric($search)) {
    $search_padded = str_pad($search, 9, "0", STR_PAD_LEFT);
}


$ptname = '';
$hn_number = '';
$cid = '';
$result = null;

function formatDateThai($date) {
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year_thai = $matches[3];
        $year = $year_thai - 543;
        $date = "$year-$month-$day";
    }

    $months = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $year = date("Y", strtotime($date)) + 543;
    $month = $months[date("n", strtotime($date))];
    $day = date("j", strtotime($date));
    return "$day $month $year";
}

// คำสั่ง SQL สำหรับการค้นหาผู้ป่วย
if (!empty($search)) {
    // [ปรับปรุง 2] เพิ่มเงื่อนไข OR v.hn LIKE ? เข้าไปอีกตัว เพื่อรองรับทั้งแบบเติม 0 และไม่เติม
    // Logic: (HN เหมือนแบบเติม0) หรือ (HN เหมือนแบบพิมพ์มาเอง) หรือ (CID เหมือนแบบพิมพ์มาเอง)
    $sql = "
        (SELECT v.vn, v.hn, v.vstdate AS date, v.ptname, v.cid, v.income, v.pttypename, v.pttype, 'opd' AS visit_type, 
                v.billdate, v.follow_money , v.rcpnodate
        FROM imr_tb_debtor_rights_opd v
        WHERE (v.hn LIKE ? OR v.hn LIKE ? OR v.cid = ?))

        UNION ALL

        (SELECT i.an AS vn, i.hn, i.admdate AS date, i.ptname, i.cid, i.income, i.pttypename, i.pttype, 'ipd' AS visit_type, 
                i.billdate, i.follow_money , i.rcpnodate
        FROM imr_tb_debtor_rights_ipd i
        WHERE (i.hn LIKE ? OR i.hn LIKE ? OR i.cid = ?))
        
        ORDER BY STR_TO_DATE(CONCAT(
            SUBSTRING(date, 1, 6),
            SUBSTRING(date, 7, 4) - 543
        ), '%d/%m/%Y') DESC";

    // [ปรับปรุง 3] เตรียม Parameter สำหรับ bind
    // ใส่เครื่องหมาย % เพื่อให้ค้นหาแบบ "ขึ้นต้นด้วย..." (StartsWith) หรือจะใส่ข้างหน้าด้วยถ้าอยากหาแบบ "มีคำว่า" (Contains)
    $p_padded = "$search_padded%"; // สำหรับค้นหา HN แบบเต็ม (เช่น 000000055)
    $p_raw    = "$search_raw%";    // สำหรับค้นหา HN สั้นๆ (เช่น 55)
    $p_cid_encrypted = encrypt_data($search_raw); // เข้ารหัสคำค้นหาสำหรับการค้นหา CID แบบตรงตัว

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo "Error preparing the SQL query: " . $conn->error;
        exit;
    }

    // แก้ bind_param เป็น 6 ตัว (ssssss) ตามจำนวนเครื่องหมาย ? ใน SQL
    $stmt->bind_param("ssssss", 
        $p_padded, $p_raw, $p_cid_encrypted,   // ชุดของ OPD
        $p_padded, $p_raw, $p_cid_encrypted    // ชุดของ IPD
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // ดึงข้อมูลแถวแรกมาแสดงรายละเอียด (ถ้าเจอ)
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $ptname = $row['ptname'];
        $hn_number = $row['hn'];
        $cid = decrypt_data($row['cid']);
        $result->data_seek(0); // เลื่อน pointer กลับไปที่เริ่มเพื่อวนลูปแสดงตาราง
    }
    $stmt->close();
}


// ดึงรายการสิทธิการรักษาเพื่อใช้ใน dropdown
$pttype_sql = "SELECT pttype_eclaim_id,pttype_eclaim_name,pttype, pttypename,accountcode,accountname 
FROM imr_tb_debtor_rights_opd WHERE pttype_eclaim_name<>'' GROUP BY pttype_eclaim_name, pttypename ORDER BY pttypename ASC";
$pttype_result = $conn->query($pttype_sql);
$pttypes = [];
if ($pttype_result) {
    while ($row = $pttype_result->fetch_assoc()) {
        $pttypes[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ค้นหาข้อมูลคนไข้</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <style>
        /* ==========================================================================
           eDHS System Font: Noto Sans Thai
           ========================================================================== */
        body, 
        .layout-wrapper, 
        .layout-container, 
        .layout-page, 
        .content-wrapper, 
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

        .copy-alert {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            display: none;
            font-size: 16px;
        }
        .modal-body .row {
            margin-bottom: 15px;
        }
        .file-upload-container {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            border-radius: 8px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">🔎 ค้นหาข้อมูลคนไข้ (เฉพาะคนไข้ที่ตั้งลูกหนี้)</h2>
    <br>
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input style="box-shadow: 0 0 12px rgb(78 160 255 / 60%), 0 0 20px rgb(181 193 255 / 40%);" type="text" name="search" class="form-control" placeholder="กรอก HN หรือ CID..." value="<?php echo htmlspecialchars($search); ?>">
            <button style="box-shadow: 0 0 12px rgb(78 160 255 / 60%), 0 0 20px rgb(181 193 255 / 40%);" type="submit" class="btn btn-primary">ค้นหา</button>
        </div>
    </form>

    <?php if (!empty($ptname)): ?>
        <div class="alert alert-info text-center">
            🆔 HN: <strong><?php echo htmlspecialchars($hn_number); ?></strong> |
            👤 คนไข้: <strong><?php echo htmlspecialchars($ptname); ?></strong> |
            📋 CID: <strong><?php echo htmlspecialchars($cid); ?></strong>
        </div>
    <?php elseif (!empty($search)): ?>
        <div class="alert alert-danger text-center">❌ ไม่พบข้อมูลสำหรับ: <?php echo htmlspecialchars($search); ?></div>
    <?php endif; ?>

    <?php if (!empty($search) && $result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-striped" style="font-size: 14px;">
            <thead class="table-dark">
                <tr style="font-size: 13px;">
                    <th style=" text-align: center;">VN/AN</th>
                    <th style=" text-align: center;">ประเภท</th>
                    <th style="width: 13%; text-align: center;">วันที่มา</th>
                    <th>สิทธิรักษา</th>
                    <th style=" text-align: center;">ค่าใช้จ่าย (บาท)</th>
                    <th style=" text-align: center;">ชดเชย (บาท)</th>
                    <th style="width: 11%; text-align: center;">คัดลอก VN</th>
                    <th style="width: 15%; text-align: center;">ปรับปรุงลูกหนี้สิทธิ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        // Get VN
                        $vn = $row['vn'];
                        $compensated_amount = 0;

                        // [ปรับปรุง 2] เช็ค billdate จากตารางหลักก่อนเลย (เร็วและชัวร์กว่า)
                        // ถ้ามี billdate (และไม่ใช่ค่าว่าง) -> ให้ใช้ยอด follow_money
                        if (!empty($row['billdate']) && $row['billdate'] != '0000-00-00') {
                            $compensated_amount = (float)($row['follow_money'] ?? 0);
                        } 
                        else {
                            // ถ้าไม่มี billdate -> ค่อยไปดึงยอด compensated จาก 3 ตารางเดิม (เป็นทางเลือกสำรอง)
                            // ตรงนี้ดึงแค่ compensated กับ amount พอครับ เพราะ follow_money เราดูไปแล้ว
                            $sql_compensated = "
                                (SELECT compensated FROM imr_tb_check_invoice WHERE vn = ?)
                                UNION ALL
                                (SELECT compensated FROM imr_tb_seamless_dckd WHERE vn = ?)
                                UNION ALL
                                (SELECT amount AS compensated FROM imr_tb_seamless_dckd_ofc WHERE vn = ?)
                            ";

                            $stmt_compensated = $conn->prepare($sql_compensated);
                            if ($stmt_compensated) {
                                $stmt_compensated->bind_param("sss", $vn, $vn, $vn);
                                $stmt_compensated->execute();
                                $result_compensated = $stmt_compensated->get_result();
                                
                                if ($result_compensated && $result_compensated->num_rows > 0) {
                                    $comp_row = $result_compensated->fetch_assoc();
                                    $compensated_amount = (float)($comp_row['compensated'] ?? 0);
                                }
                                $stmt_compensated->close();
                            }
                        }

                        $row_style = "";
                        if ($row['visit_type'] == 'ipd') {
                            $row_style = "background-color: #ffecb3 !important;"; 
                        }
                    ?>
                    
                    <tr style="<?php echo $row_style; ?>">
                        <td style="font-weight: bold;text-align: center;"><?php echo htmlspecialchars($row['vn']); ?></td>
                        
                        <td style="text-align: center; <?php echo ($row['visit_type'] == 'ipd') ? 'color: red; font-weight: bold;' : ''; ?>">
                            <?php echo strtoupper(htmlspecialchars($row['visit_type'])); ?>
                        </td>

                        <td style="text-align: right;"><?php echo formatDateThai($row['date']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($row['pttypename']); ?>
                            
                            <?php 
                                // เช็คว่า rcpnodate มีค่ามากกว่า 0 หรือไม่ (แปลว่าเคยมีการดึงค่าเดิมมาเก็บไว้ = เคยปรับปรุงแล้ว)
                                if (!empty($row['rcpnodate']) && is_numeric($row['rcpnodate']) && floatval($row['rcpnodate']) > 0) { 
                            ?>
                                <br>
                                <span class="badge bg-warning text-dark" style="font-size: 11px; margin-top: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                    📝 มีการปรับปรุงค่าใช้จ่าย
                                </span>
                            <?php 
                                } 
                            ?>
                        </td>
                        <td style="text-align: right;"><?php echo number_format((float)($row['income'] ?? 0), 2); ?></td>
                        
                        <td style="text-align: right;"><?php echo number_format((float)($compensated_amount ?? 0), 2); ?></td>
                        
                        <td style="text-align: center;" class="text-center">
                            <button class="btn btn-sm btn-success" onclick="copyVN('<?php echo htmlspecialchars($row['vn']); ?>')">📋 คัดลอก</button>
                        </td>
                        <td style="text-align: center;" class="text-center">
                            <button class="btn btn-sm btn-primary update-btn" data-vn="<?php echo htmlspecialchars($row['vn']); ?>">📋 เลือก</button>
                            <?php if ($row['pttypename'] == 'ระบบปฏิบัติการฉุกเฉิน EMS') { ?>
                                <button class="btn btn-sm btn-danger delete-btn"
                                        data-vn="<?php echo htmlspecialchars($row['vn']); ?>">
                                    🗑️ ลบ
                                </button>
                            <?php } else { ?>
                                <button class="btn btn-sm btn-secondary"
                                        style="background-color:#dfdfdf; cursor:not-allowed;color: #000;"
                                        disabled>
                                    🗑️ ลบ
                                </button>
                            <?php } ?>

                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="modal fade" id="updateDebtorModal" tabindex="-1" aria-labelledby="updateDebtorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="--bs-modal-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="updateDebtorModalLabel">ปรับปรุงข้อมูลลูกหนี้สิทธิ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateDebtorForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6" style="padding: 20px;">
                            <h4>ข้อมูลเดิม</h4>
                            <hr>
                            <p><strong>VN:</strong> <span id="old_vn"></span><strong> HN:</strong> <span id="old_hn"></span></p>
                            <p><strong>ชื่อ-สกุล:</strong> <span id="old_ptname"></span></p>
                            <p><strong>วันที่มา:</strong> <span id="old_vstdate"></span></p>
                            <p><strong>สิทธิการรักษา:</strong> <span id="old_pttype"></span></p>
                            <p><strong>ค่าใช้จ่าย:</strong> <span id="old_income"></span> บาท</p>

                            <div class="mb-3" style="color: #bb0707;">
                                <p><strong><u>หมายเหตุ</u>**</strong><span>&nbsp;&nbsp;&nbsp;การปรับปรุงข้อมูลลูกหนี้สิทธิการรักษาและค่าใช้จ่ายในระบบนี้ จะต้องดำเนินการหลังจากที่ได้ตรวจสอบความถูกต้องของเอกสารหลักฐาน เช่น หนังสือแจ้งการเปลี่ยนแปลง หรือบันทึกข้อความจากผู้มีอำนาจที่เกี่ยวข้องอย่างถี่ถ้วนแล้วเท่านั้น</span> </p>
                                <p><strong><u>ข้อควรทราบ</u></strong><span>&nbsp;&nbsp;&nbsp;การแก้ไขข้อมูลสิทธิหรือค่าใช้จ่ายในระบบมีผลต่อยอดลูกหนี้โดยตรง โปรดตรวจสอบความถูกต้องและแนบเอกสารหลักฐานที่ได้รับอนุมัติแล้วทุกครั้ง เพื่อป้องกันข้อผิดพลาดที่อาจเกิดขึ้นในอนาคต</span> </p>
                            </div>

                            <div class="mt-4" id="history_table_container" style="display: none;">
                                <h5 class="text-secondary">📊 ประวัติการปรับปรุงค่ารักษาล่าสุด</h5>
                                <table class="table table-sm table-bordered table-striped" style="font-size: 13px;">
                                    <thead class="table-secondary text-center">
                                        <tr>
                                            <th>ค่ารักษาเดิม (บาท)</th>
                                            <th>ค่ารักษาใหม่ (บาท)</th>
                                            <th>เจ้าหน้าที่ผู้ปรับปรุง</th>
                                            <th>ไฟล์หลักฐาน</th> </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="history_row">
                                            <td style="text-align: right; font-weight: bold; color: #bb0707; vertical-align: middle;" id="hist_old_income"></td>
                                            <td style="text-align: right; font-weight: bold; color: #28a745; vertical-align: middle;" id="hist_new_income"></td>
                                            <td style="text-align: center; vertical-align: middle;" id="hist_staff_name"></td>
                                            <td style="text-align: center; vertical-align: middle;" id="hist_file_link"></td> </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>




                        <div class="col-md-6" style="background-color: #ffe6e6; padding: 20px;">
                            <h4>ข้อมูลที่ต้องการเปลี่ยน</h4>
                            <hr>
                            <div class="mb-3">
                                <label for="new_pttype_select" class="form-label">สิทธิการรักษาใหม่:</label>
                                <select class="form-select" id="new_pttype_select" required>
                                    <option value="" data-pttypename="" data-eclaimid="" data-eclaimname="" data-accountcode="" data-accountname="" selected>เลือกสิทธิ์การรักษาใหม่</option>
                                    <?php foreach ($pttypes as $pttype): ?>
                                        <option value="<?php echo htmlspecialchars($pttype['pttypename']); ?>" 
                                                data-pttype="<?php echo htmlspecialchars($pttype['pttype']); ?>"
                                                data-pttypename="<?php echo htmlspecialchars($pttype['pttypename']); ?>"
                                                data-eclaimid="<?php echo htmlspecialchars($pttype['pttype_eclaim_id']); ?>"
                                                data-eclaimname="<?php echo htmlspecialchars($pttype['pttype_eclaim_name']); ?>"
                                                data-accountcode="<?php echo htmlspecialchars($pttype['accountcode']); ?>"
                                                data-accountname="<?php echo htmlspecialchars($pttype['accountname']); ?>">
                                            <?php echo htmlspecialchars($pttype['pttypename']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="card card-body" style="margin-top: 10px;background-color: #ebebeb;">

                                    <small class="font-weight-bold text-primary">สิทธิ HOS</small>
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm"
                                                   id="new_pttype_code" name="new_pttype_code" placeholder="รหัสสิทธิ" readonly style="background-color: #f4f4f4;">
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control form-control-sm"
                                                   id="new_pttype_name" name="new_pttype_name" placeholder="ชื่อสิทธิ" readonly style="background-color: #f4f4f4;">
                                        </div>
                                    </div>

                                    <small class="font-weight-bold text-success">สิทธิการเงิน</small>
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm"
                                                   id="new_pttype_eclaim_id" name="new_pttype_eclaim_id" placeholder="e-Claim ID" readonly style="background-color: #f4f4f4;">
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control form-control-sm"
                                                   id="new_pttype_eclaim_name" name="new_pttype_eclaim_name" placeholder="ชื่อ e-Claim" readonly style="background-color: #f4f4f4;">
                                        </div>
                                    </div>

                                    <small class="font-weight-bold text-danger">ผังบัญชี</small>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm"
                                                   id="new_accountcode" name="new_accountcode" placeholder="รหัสบัญชี" readonly style="background-color: #f4f4f4;">
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control form-control-sm"
                                                   id="new_accountname" name="new_accountname" placeholder="ชื่อบัญชี" readonly style="background-color: #f4f4f4;">
                                        </div>
                                    </div>

                                </div>                   
                            </div>

                            <div class="mb-3">
                                <label for="new_income" class="form-label">ค่าใช้จ่ายใหม่:</label>
                                <input type="number" step="0.01" class="form-control" id="new_income" name="new_income" required 
                                    style="font-weight: bold;
                                    color: #0067ec;
                                    font-size: 20px;
                                    text-align: center;">
                            </div>
                            <div class="mb-3">
                                <label for="rcpno" class="form-label">แนบไฟล์หลักฐาน (PDF เท่านั้น):</label>
                                <div class="file-upload-container" style="background-color: #fff">
                                    <input type="file" id="rcpno" name="rcpno" accept="application/pdf" style="display: none;">
                                    <p class="mb-0 text-muted">คลิกเพื่อเลือกไฟล์ หรือลากและวางที่นี่</p>
                                    <p id="file-name" class="text-primary mt-1"></p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="vn" id="modal_vn">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-success" id="confirmSaveBtn">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // ฟังก์ชันคัดลอก VN
    function copyVN(vn) {
        navigator.clipboard.writeText(vn).then(() => {
            let alertBox = document.createElement("div");
            alertBox.className = "copy-alert";
            alertBox.innerText = "📋 คัดลอก VN: " + vn;
            document.body.appendChild(alertBox);
            alertBox.style.display = "block";

            setTimeout(() => {
                alertBox.style.opacity = "0";
                setTimeout(() => alertBox.remove(), 1000);
            }, 2000);
        });
    }

    $(document).ready(function() {
        // เมื่อคลิกปุ่ม 'เลือก'
        $('.update-btn').on('click', function() {
            var vn = $(this).data('vn');
            $('#modal_vn').val(vn);

            // ดึงข้อมูลเดิมจากฐานข้อมูลด้วย Ajax
            $.ajax({
                url: 'get_debtor_data.php',
                type: 'GET',
                data: {
                    vn: vn
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        // --- โค้ดเดิมของพี่ ---
                        $('#old_vn').text(data.data.vn);
                        $('#old_hn').text(data.data.hn);
                        $('#old_ptname').text(data.data.ptname);
                        $('#old_vstdate').text(data.data.vstdate);
                        $('#old_pttype').text(data.data.pttypename);
                        $('#old_income').text(parseFloat(data.data.income).toFixed(2));
                        $('#new_income').val(parseFloat(data.data.income).toFixed(2));
                        $('#new_pttype_select').trigger('change');

                        // --- 🛠️ ส่วนตรวจสอบและแสดงตารางประวัติ ---
                        // เช็คว่า rcpnodate มีค่าอยู่จริง เป็นตัวเลขจริงๆ และมากกว่า 0 หรือไม่
                        if (data.data.rcpnodate && !isNaN(data.data.rcpnodate) && parseFloat(data.data.rcpnodate) > 0) {
                            
                            // 1. นำยอดเงินเดิม และ ยอดเงินปัจจุบันมาแสดง
                            $('#hist_old_income').text(parseFloat(data.data.rcpnodate).toFixed(2));
                            $('#hist_new_income').text(parseFloat(data.data.income).toFixed(2));

                            // 2. แกะชื่อเจ้าหน้าที่ และสร้างปุ่มดูไฟล์
                            var staffName = "ไม่ระบุชื่อเจ้าหน้าที่";
                            var fileButtonHtml = '<span class="text-muted">- ไม่มีไฟล์ -</span>'; // ค่าเริ่มต้นกรณีหาไฟล์ไม่เจอ

                            if (data.data.rcpno && data.data.rcpno !== '') {
                                // แกะชื่อจากชื่อไฟล์
                                var filename = data.data.rcpno.split('/').pop(); 
                                var nameParts = filename.split('_'); 
                                
                                if (nameParts.length >= 4) {
                                    var fullnameParts = nameParts.slice(2, nameParts.length - 1);
                                    staffName = fullnameParts.join(' ').replace(/_/g, ' '); 
                                }

                                // สร้างปุ่มดูไฟล์ (ใช้ค่า rcpno ที่เก็บพาธไฟล์ไว้ เช่น 'uploads/ชื่อไฟล์.pdf')
                                fileButtonHtml = '<a href="' + data.data.rcpno + '" target="_blank" class="btn btn-sm btn-primary text-white" style="font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">📄 ดูไฟล์</a>';
                            }

                            $('#hist_staff_name').text(staffName);
                            $('#hist_file_link').html(fileButtonHtml); // แทรกปุ่มลงไปในคอลัมน์

                            // เปิดแสดงกล่องตารางทั้งหมด
                            $('#history_table_container').show();

                        } else {
                            // ถ้ายังไม่มีประวัติ ให้ซ่อนกล่องตารางไปเลย
                            $('#history_table_container').hide();
                        } 

                        var updateDebtorModal = new bootstrap.Modal(document.getElementById('updateDebtorModal'));
                        updateDebtorModal.show();
                    } else {

                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่พบข้อมูลสำหรับ VN: ' + vn,
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    console.log("Response text:", xhr.responseText);
                    // ใช้ Swal.fire สำหรับแจ้งเตือนข้อผิดพลาด
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'เกิดข้อผิดพลาดในการดึงข้อมูล',
                    });
                }
            });
        });

        $('.delete-btn').on('click', function () {
            let vn = $(this).data('vn');

            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: 'ข้อมูลนี้จะไม่สามารถกู้คืนได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {

                    $.ajax({
                        url: 'get_debtor_data.php', // หรือ delete_debtor.php (แนะนำ)
                        type: 'POST',
                        data: { vndelete: vn },
                        dataType: 'json',
                        success: function (res) {

                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ',
                                    text: 'ลบข้อมูลเรียบร้อยแล้ว'
                                }).then(() => {
                                    location.reload(); // หรือ remove row
                                });

                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ลบไม่สำเร็จ',
                                    text: res.message
                                });
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'ระบบขัดข้อง',
                                text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
                            });
                            console.error(xhr.responseText);
                        }
                    });

                }
            });
        });


        // เมื่อมีการเปลี่ยนค่าใน dropdown
        $('#new_pttype_select').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            $('#new_pttype_code').val(selectedOption.data('pttype'));
            $('#new_pttype_name').val(selectedOption.data('pttypename'));
            $('#new_pttype_eclaim_id').val(selectedOption.data('eclaimid'));
            $('#new_pttype_eclaim_name').val(selectedOption.data('eclaimname'));
            $('#new_accountcode').val(selectedOption.data('accountcode'));
            $('#new_accountname').val(selectedOption.data('accountname'));
        });

        // จัดการการอัปโหลดไฟล์
        const fileInput = document.getElementById('rcpno');
        const fileContainer = document.querySelector('.file-upload-container');
        const fileNameDisplay = document.getElementById('file-name');

        fileContainer.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                fileNameDisplay.textContent = e.target.files[0].name;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
        fileContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileContainer.style.borderColor = '#007bff';
        });
        fileContainer.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileContainer.style.borderColor = '#ccc';
        });
        fileContainer.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileContainer.style.borderColor = '#ccc';
            fileInput.files = e.dataTransfer.files;
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = fileInput.files[0].name;
            }
        });



        $('#confirmSaveBtn').on('click', function(e) {
            e.preventDefault();

            // --- เช็คว่าเลือกสิทธิการรักษาใหม่หรือยัง ---
            var newPttype = $('#new_pttype_select').val();
            if (!newPttype || newPttype.trim() === '') {
                Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาเลือกสิทธิการรักษาใหม่ด้วยครับ' });
                return false; // หยุดการทำงานทันที
            }

            // --- เริ่มดักจับไฟล์ก่อนเลย ---
            const fileInput = document.getElementById('rcpno');
            
            // 1. เช็คว่าได้เลือกไฟล์หรือยัง
            if (fileInput.files.length === 0) {
                Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาแนบไฟล์หลักฐาน (PDF) ด้วยครับ' });
                return false; // หยุดการทำงานทันที
            }

            const file = fileInput.files[0];

            // 2. เช็คว่าเป็นไฟล์ PDF หรือไม่
            if (file.type !== 'application/pdf') {
                Swal.fire({ icon: 'warning', title: 'ผิดรูปแบบ', text: 'ไฟล์ที่แนบต้องเป็นนามสกุล .pdf เท่านั้นครับ' });
                // ล้างค่าที่เลือกผิดออก
                fileInput.value = ''; 
                document.getElementById('file-name').textContent = '';
                return false;
            }

            // 3. เช็คขนาดไฟล์ (10MB = 10 * 1024 * 1024 bytes)
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                Swal.fire({ icon: 'warning', title: 'ไฟล์ใหญ่เกินไป', text: 'ขนาดไฟล์ต้องไม่เกิน 10MB ครับ' });
                // ล้างค่าที่เลือกผิดออก
                fileInput.value = ''; 
                document.getElementById('file-name').textContent = '';
                return false;
            }
            // --- จบการดักจับ ---

            Swal.fire({
                title: 'ยืนยันการบันทึก',
                text: "คุณต้องการบันทึกการเปลี่ยนแปลงข้อมูลใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, บันทึกเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#updateDebtorForm').submit();
                }
            });
        });


        // **เปลี่ยนจาก alert() เป็น Swal.fire()**
        $('#updateDebtorForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                url: 'update_debtor.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'text', 
               success: function(response) {
                    // ตรวจสอบค่าที่ส่งกลับมาเป็นข้อความ 'true'
                    if (response.trim() === 'true') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: '✅ บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว',
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: '❌ ไม่สามารถบันทึกการเปลี่ยนแปลงได้',
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    console.log("Response text:", xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'เกิดข้อผิดพลาดในการบันทึกข้อมูล',
                    });
                }
            });
        });
    });
</script>
</body>
</html>