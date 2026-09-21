<?php
include 'check_auth.php';
include './database_config/config.php';
checkRole(['admin']); // ตรวจสอบว่าเป็น admin เท่านั้น

// อนุมัติผู้ใช้
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_id'])) {
    $approve_id = $_POST['approve_id'];
    $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $approve_id);
    $stmt->execute();
}

// ยกเลิกผู้ใช้
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
}

// เปลี่ยนรหัสผ่านผู้ใช้
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password_id'])) {
    $change_password_id = $_POST['change_password_id'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_password, $change_password_id);
    $stmt->execute();
}

// เปลี่ยนรหัส PIN ผู้ใช้
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_pin_id'])) {
    $change_pin_id = $_POST['change_pin_id'];
    $new_pin = $_POST['new_pin'];
    if (strlen($new_pin) === 6 && is_numeric($new_pin)) {
        $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET pin_code = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_pin, $change_pin_id);
        $stmt->execute();
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการผู้ใช้ - eDHS</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Prompt', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding: 0;
            margin: 0;
        }

        .user-mgmt-header-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #4f46e5 !important;
            padding: 20px 24px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            margin-bottom: 20px;
        }

        .user-header-icon {
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

        .btn-migration {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            padding: 8px 16px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .btn-migration:hover {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            transform: translateY(-1px);
        }

        .user-mgmt-table-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .modern-user-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .modern-user-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 13.5px;
            padding: 14px 16px;
            border-bottom: 1.5px solid #e2e8f0;
            white-space: nowrap;
        }
        .modern-user-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
            vertical-align: middle;
        }
        .modern-user-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .user-avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .user-username-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12.5px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
        }

        .badge-role-admin {
            background: #fef2f2 !important;
            color: #dc2626 !important;
            border: 1px solid #fecaca;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
        }
        .badge-role-insurance {
            background: #eff6ff !important;
            color: #2563eb !important;
            border: 1px solid #bfdbfe;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
        }
        .badge-role-other {
            background: #f8fafc !important;
            color: #64748b !important;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
        }

        .badge-status-approved {
            display: inline-flex;
            align-items: center;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .badge-status-pending {
            display: inline-flex;
            align-items: center;
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* Action Buttons */
        .btn-action-approve {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-action-approve:hover {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        .btn-action-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-action-delete:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        .btn-action-password {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-action-password:hover {
            background: #d97706;
            color: #ffffff;
            border-color: #d97706;
        }

        .btn-action-pin {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-action-pin:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .btn-action-perm {
            background: #f0fdf4;
            color: #0d9488;
            border: 1px solid #99f6e4;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-action-perm:hover {
            background: #0d9488;
            color: #ffffff;
            border-color: #0d9488;
        }

        /* Modal Dialog Enhancements */
        .modal-content {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .modal-header {
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 22px;
            background: #ffffff;
        }
        .modal-body {
            padding: 22px;
            background: #ffffff;
        }
        .modal-footer {
            border-top: 1px solid #f1f5f9;
            padding: 14px 22px;
            background: #f8fafc;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-4">

    <!-- Header Section Card -->
    <div class="user-mgmt-header-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="user-header-icon">
                    <i class="bx bxs-user-account"></i>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">จัดการผู้ใช้</h4>
                    <p class="mb-0 text-muted small">ระบบบริหารจัดการบัญชีผู้ใช้งาน อนุมัติสิทธิ์ และความปลอดภัยระดับบัญชี</p>
                </div>
            </div>
            <div>
                <a href="migration_tool.php" target="_blank" class="btn btn-migration">
                    <i class="bx bx-shield-quarter text-primary"></i> เครื่องมือเข้ารหัสข้อมูล (Migration)
                </a>
            </div>
        </div>
    </div>

    <!-- Table Section Card -->
    <div class="user-mgmt-table-card">
        <div class="table-responsive">
            <table class="table modern-user-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 220px;">ชื่อ-สกุล</th>
                        <th class="text-center" style="min-width: 120px;">ชื่อผู้ใช้</th>
                        <th class="text-center" style="min-width: 120px;">บทบาท</th>
                        <th class="text-center" style="min-width: 110px;">สถานะ</th>
                        <th class="text-center" style="min-width: 320px;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar-circle">
                                        <?= mb_substr($user['fullname'], 0, 1, 'UTF-8'); ?>
                                    </div>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($user['fullname']); ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="user-username-badge"><?= htmlspecialchars($user['username']); ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge-role-admin"><i class='bx bx-crown me-1'></i> admin</span>
                                <?php elseif ($user['role'] === 'insurance'): ?>
                                    <span class="badge-role-insurance"><i class='bx bx-user me-1'></i> insurance</span>
                                <?php else: ?>
                                    <span class="badge-role-other"><?= htmlspecialchars($user['role']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($user['status'] == 'approved'): ?>
                                    <span class="badge-status-approved">
                                        <i class='bx bx-check-circle me-1'></i> อนุมัติแล้ว
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status-pending">
                                        <i class='bx bx-time-five me-1'></i> รออนุมัติ
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center flex-wrap gap-1">
                                    <?php if ($user['status'] == 'pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="approve_id" value="<?= $user['id']; ?>">
                                            <button type="submit" class="btn btn-action-approve" title="อนุมัติผู้ใช้">
                                                <i class="bx bx-check"></i> อนุมัติ
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบผู้ใช้ <?= htmlspecialchars($user['fullname']); ?> หรือไม่?');">
                                        <input type="hidden" name="delete_id" value="<?= $user['id']; ?>">
                                        <button type="submit" class="btn btn-action-delete" title="ลบผู้ใช้">
                                            <i class="bx bx-trash"></i> ลบ
                                        </button>
                                    </form>

                                    <!-- ปุ่มเปิด Modal เปลี่ยนรหัสผ่าน -->
                                    <button type="button" class="btn btn-action-password" data-bs-toggle="modal" data-bs-target="#changePasswordModal<?= $user['id']; ?>" title="เปลี่ยนรหัสผ่าน">
                                        <i class="bx bx-key"></i> เปลี่ยนรหัสผ่าน
                                    </button>
                                    
                                    <!-- ปุ่มเปิด Modal ตั้งค่า PIN -->
                                    <button type="button" class="btn btn-action-pin" data-bs-toggle="modal" data-bs-target="#changePinModal<?= $user['id']; ?>" title="ตั้งรหัส PIN (2FA)">
                                        <i class="bx bx-lock-alt"></i> ตั้งรหัส PIN (2FA)
                                    </button>

                                    <button type="button" class="btn btn-action-perm" onclick="openPermissionModal(<?= $user['id'] ?>, '<?= $user['username'] ?>')" title="กำหนดสิทธิ์การเข้าถึงเมนู">
                                        <i class="bx bx-slider-alt"></i> กำหนดสิทธิ์
                                    </button>
                                </div>

                                <!-- Modal เปลี่ยนรหัสผ่าน -->
                                <div class="modal fade" id="changePasswordModal<?= $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div style="width: 34px; height: 34px; border-radius: 8px; background: #fffbeb; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                                        <i class="bx bx-key"></i>
                                                    </div>
                                                    <h5 class="modal-title mb-0 fw-bold">เปลี่ยนรหัสผ่าน: <?= htmlspecialchars($user['fullname']); ?></h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="change_password_id" value="<?= $user['id']; ?>">
                                                    <div class="mb-3 text-start">
                                                        <label class="form-label fw-semibold text-dark">รหัสผ่านใหม่</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-white"><i class="bx bx-lock"></i></span>
                                                            <input type="password" id="new_password_<?= $user['id']; ?>" name="new_password" class="form-control" placeholder="ระบุรหัสผ่านใหม่..." required>
                                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password_<?= $user['id']; ?>">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 10px;">
                                                        <i class="bx bx-check me-1"></i> บันทึกเปลี่ยนรหัสผ่าน
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- จบ Modal เปลี่ยนรหัสผ่าน -->
                                
                                <!-- Modal ตั้งค่า PIN -->
                                <div class="modal fade" id="changePinModal<?= $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div style="width: 34px; height: 34px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                                        <i class="bx bx-lock-alt"></i>
                                                    </div>
                                                    <h5 class="modal-title mb-0 fw-bold">ตั้งรหัส PIN (6 หลัก): <?= htmlspecialchars($user['fullname']); ?></h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="change_pin_id" value="<?= $user['id']; ?>">
                                                    <div class="mb-3 text-start">
                                                        <label class="form-label fw-semibold text-dark">รหัส PIN ใหม่ (ตัวเลข 6 หลัก)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-white"><i class="bx bx-dialpad"></i></span>
                                                            <input type="password" id="new_pin_<?= $user['id']; ?>" name="new_pin" class="form-control" maxlength="6" pattern="\d{6}" placeholder="ระบุ PIN 6 หลัก..." required>
                                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_pin_<?= $user['id']; ?>">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </button>
                                                        </div>
                                                        <small class="text-muted">ใช้สำหรับยืนยันตัวตนสองขั้นตอน (2FA)</small>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 10px;">
                                                        <i class="bx bx-check me-1"></i> บันทึกรหัส PIN
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- จบ Modal PIN -->
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal กำหนดสิทธิ์ -->
<div class="modal fade" id="permissionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="d-flex align-items-center gap-2">
            <div style="width: 34px; height: 34px; border-radius: 8px; background: #f0fdf4; color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                <i class="bx bx-slider-alt"></i>
            </div>
            <h5 class="modal-title mb-0 fw-bold">กำหนดสิทธิ์การใช้งาน: <span id="permUserName" class="text-primary fw-bold"></span></h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formPermissions">
            <input type="hidden" id="permUserId" name="user_id">
            
            <div id="menuCheckboxes">
                <div class="text-center py-3 text-secondary">กำลังโหลดข้อมูล...</div>
            </div>
        </form>
      </div>
      <div class="modal-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">ปิด</button>
        <button type="button" class="btn btn-success px-4" onclick="savePermissions()" style="border-radius: 8px;">
            <i class="bx bx-check me-1"></i> บันทึกสิทธิ์
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // สคริปต์เปิด/ปิดรูปตาสำหรับฟิลด์รหัสผ่าน
    document.addEventListener('click', function(e) {
        if(e.target.closest('.toggle-password')) {
            const btn = e.target.closest('.toggle-password');
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    });

    // เปิด Modal และดึงสิทธิ์เดิมมาโชว์
    function openPermissionModal(userId, username) {
        $('#permUserName').text(username);
        $('#permUserId').val(userId);
        
        // โชว์สถานะโหลดก่อน
        $('#menuCheckboxes').html('<div class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดสิทธิ์...</div>');
        $('#permissionModal').modal('show');

        $.ajax({
            url: 'get_permissions.php',
            type: 'POST',
            data: { user_id: userId },
            success: function(response) {
                $('#menuCheckboxes').html(response);
            },
            error: function() {
                $('#menuCheckboxes').html('<div class="text-danger text-center">เกิดข้อผิดพลาดในการโหลดข้อมูลสิทธิ์ กรุณาลองใหม่อีกครั้ง</div>');
            }
        });
    }

    // บันทึกสิทธิ์ลงฐานข้อมูล
    function savePermissions() {
        var formData = $('#formPermissions').serialize();
        
        // โชว์ Loading สวยๆ
        Swal.fire({
            title: 'กำลังบันทึก...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'save_permissions.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if(response.trim() === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'เรียบร้อย!',
                        text: 'อัปเดตสิทธิ์การใช้งานให้ผู้ใช้นี้เรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        $('#permissionModal').modal('hide');
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', 'มีบางอย่างผิดพลาด: ' + response, 'error');
                }
            },
            error: function() {
                Swal.fire('ระบบมีปัญหา', 'ไม่สามารถติดต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง', 'error');
            }
        });
    }
</script>
</body>
</html>
