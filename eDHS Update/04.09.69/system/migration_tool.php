<?php
// เริ่มต้นระบบและตรวจสอบสิทธิ์
require_once 'check_auth.php';
require_once './database_config/config.php';
// ตรวจสอบสิทธิ์ว่าต้องเป็น admin เท่านั้นถึงจะใช้งานหน้านี้ได้
checkRole(['admin']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Encryption Migration Tool</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    
    <!-- CSS / Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f5f5f9;
            color: #566a7f;
        }
        .migration-card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.25rem 1rem rgba(161, 172, 184, 0.45);
            background-color: #fff;
            padding: 2rem;
            margin-top: 3rem;
        }
        .icon-header {
            font-size: 3rem;
            color: #696cff;
            margin-bottom: 1rem;
        }
        .form-label {
            font-weight: 500;
            color: #435971;
        }
        .btn-primary {
            background-color: #696cff;
            border-color: #696cff;
        }
        .btn-primary:hover {
            background-color: #5f61e6;
            border-color: #5f61e6;
        }
        .info-box {
            background-color: #e7e7ff;
            color: #696cff;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card migration-card">
                <div class="text-center">
                    <i class="fa-solid fa-shield-halved icon-header"></i>
                    <h3 class="mb-2" style="font-weight: 600; color: #32475c;">Data Encryption Migration Tool</h3>
                    <p class="text-muted mb-4">เครื่องมือโอนย้ายข้อมูลเดิมในฐานข้อมูล ให้กลายเป็นข้อมูลเข้ารหัส (AES-256)</p>
                </div>

                <div class="info-box">
                    <strong><i class="fa-solid fa-circle-info"></i> คำแนะนำ:</strong>
                    <ul class="mb-0 mt-1">
                        <li>กรุณาระบุชื่อตารางและคอลัมน์ให้ถูกต้องทุกตัวอักษร</li>
                        <li>ระบบจะตรวจสอบข้อมูลที่ยังไม่ถูกเข้ารหัส และทำการเข้ารหัสให้อัตโนมัติ</li>
                        <li>แนะนำให้ทำการสำรอง (Backup) ฐานข้อมูลก่อนกดรัน เพื่อความปลอดภัยสูงสุด</li>
                    </ul>
                </div>

                <form id="migrationForm">
                    <div class="mb-3">
                        <label for="tableName" class="form-label"><i class="fa-solid fa-table border-end pe-2 me-2"></i>ชื่อตารางเป้าหมาย (Table Name)</label>
                        <input type="text" class="form-control" id="tableName" name="table_name" placeholder="เช่น imr_tb_debtor_rights_opd" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="primaryKey" class="form-label"><i class="fa-solid fa-key border-end pe-2 me-2 text-warning"></i>คอลัมน์ Primary Key</label>
                            <input type="text" class="form-control" id="primaryKey" name="primary_key" placeholder="เช่น no, id หรือ vn" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="targetColumn" class="form-label"><i class="fa-solid fa-lock border-end pe-2 me-2 text-danger"></i>คอลัมน์ที่ต้องการเข้ารหัส</label>
                            <input type="text" class="form-control" id="targetColumn" name="target_column" placeholder="เช่น cid, pid" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
                            <i class="fa-solid fa-play me-2"></i> เริ่มดำเนินการเข้ารหัสข้อมูล
                        </button>
                    </div>
                </form>

                <div id="progressArea" class="mt-4" style="display: none;">
                    <div class="progress" style="height: 25px;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div class="text-center mt-2" id="progressText">
                        กำลังเตรียมข้อมูล...
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#migrationForm').on('submit', function(e) {
        e.preventDefault();
        
        let tableName = $('#tableName').val().trim();
        let primaryKey = $('#primaryKey').val().trim();
        let targetColumn = $('#targetColumn').val().trim();

        if(!tableName || !primaryKey || !targetColumn) {
            Swal.fire('ข้อผิดพลาด', 'กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
            return;
        }

        Swal.fire({
            title: 'ยืนยันการเข้ารหัส?',
            text: "ข้อมูลเก่าจะถูกแก้ไขเป็นข้อความเข้ารหัสอย่างถาวร!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'ใช่, ดำเนินการเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                startMigration(tableName, primaryKey, targetColumn);
            }
        });
    });
});

function startMigration(tableName, primaryKey, targetColumn) {
    // ปิดปุ่มและโชว์ Progress
    $('#btnSubmit').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> กำลังประมวลผล...');
    
    // แสดง UI โหลด แต่เราจะไม่ทำ progress bar จริงจังเพราะมันทำงานฝั่ง Server นาน
    Swal.fire({
        title: 'กำลังประมวลผลการเข้ารหัส...',
        html: 'โปรดอย่าปิดหน้าต่างนี้ จนกว่าระบบจะแจ้งเตือนว่าเสร็จสิ้น<br>อาจใช้เวลาสักครู่ขึ้นอยู่กับปริมาณข้อมูล',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'action_migrate.php',
        type: 'POST',
        data: {
            table_name: tableName,
            primary_key: primaryKey,
            target_column: targetColumn
        },
        dataType: 'json',
        success: function(response) {
            $('#btnSubmit').prop('disabled', false).html('<i class="fa-solid fa-play me-2"></i> เริ่มดำเนินการเข้ารหัสข้อมูล');
            
            if(response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'ดำเนินการเสร็จสิ้น!',
                    text: response.message,
                    confirmButtonColor: '#696cff'
                }).then(() => {
                    // ล้างฟอร์ม
                    $('#targetColumn').val('');
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: response.message,
                    confirmButtonColor: '#696cff'
                });
            }
        },
        error: function(xhr, status, error) {
            $('#btnSubmit').prop('disabled', false).html('<i class="fa-solid fa-play me-2"></i> เริ่มดำเนินการเข้ารหัสข้อมูล');
            console.error(xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'ระบบขัดข้อง',
                text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ โปรดตรวจสอบการเชื่อมต่อและชื่อตารางอีกครั้ง (' + error + ')',
                confirmButtonColor: '#696cff'
            });
        }
    });
}
</script>

</body>
</html>
