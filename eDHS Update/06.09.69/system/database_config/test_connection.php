<?php
require_once __DIR__ . "/auth_check.php";
$access = check_database_config_access();
if (!$access['allowed']) {
    die("<div class='alert alert-danger mb-0 d-flex align-items-center gap-2'>
            <i class='bx bx-shield-x fs-4'></i>
            <div><strong>Access Denied:</strong> กรุณาเข้าสู่ระบบด้วยสิทธิ์ Admin ก่อนทำการทดสอบการเชื่อมต่อ</div>
         </div>");
}

$configFile = __DIR__ . "/config.json";
$configData = file_exists($configFile) ? @json_decode(file_get_contents($configFile), true) : [];

// รองรับทั้งค่าที่ส่งมาจากฟอร์ม (POST) หรือค่าเดิมใน config.json
$db1_cfg = [
    "servername" => $_POST["db1_servername"] ?? ($configData["db1"]["servername"] ?? ''),
    "username"   => $_POST["db1_username"] ?? ($configData["db1"]["username"] ?? ''),
    "password"   => $_POST["db1_password"] ?? ($configData["db1"]["password"] ?? ''),
    "dbname"     => $_POST["db1_dbname"] ?? ($configData["db1"]["dbname"] ?? '')
];

$db2_cfg = [
    "servername" => $_POST["db2_servername"] ?? ($configData["db2"]["servername"] ?? ''),
    "username"   => $_POST["db2_username"] ?? ($configData["db2"]["username"] ?? ''),
    "password"   => $_POST["db2_password"] ?? ($configData["db2"]["password"] ?? ''),
    "dbname"     => $_POST["db2_dbname"] ?? ($configData["db2"]["dbname"] ?? '')
];
?>

<div class="row g-3">
    <!-- ผลการทดสอบ DB 1 -->
    <div class="col-md-6">
        <div class="p-3 rounded-3 border" style="background: #f8fafc;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark" style="font-size: 14px;">
                    <i class="bx bx-data text-success me-1"></i> ฐานข้อมูลที่ 1 (eDHS)
                </span>
                <span class="badge bg-light text-muted border" style="font-size: 11px;">
                    <?= htmlspecialchars($db1_cfg["dbname"] ?? ''); ?>
                </span>
            </div>
            <div>
                <?php
                try {
                    $start_time = microtime(true);
                    $db1_conn = @new mysqli(
                        $db1_cfg["servername"],
                        $db1_cfg["username"],
                        $db1_cfg["password"],
                        $db1_cfg["dbname"]
                    );
                    $latency = round((microtime(true) - $start_time) * 1000, 1);

                    if ($db1_conn->connect_error) {
                        echo "<div class='text-danger fw-semibold d-flex align-items-center gap-1' style='font-size: 13.5px;'>
                                <i class='bx bx-x-circle fs-5'></i> การเชื่อมต่อล้มเหลว: " . htmlspecialchars($db1_conn->connect_error) . "
                              </div>";
                    } else {
                        echo "<div class='text-success fw-semibold d-flex align-items-center justify-content-between' style='font-size: 13.5px;'>
                                <span><i class='bx bx-check-circle fs-5'></i> เชื่อมต่อสำเร็จ! (Connected)</span>
                                <span class='badge bg-success-subtle text-success border border-success' style='font-size: 11px;'>${latency} ms</span>
                              </div>";
                        $db1_conn->close();
                    }
                } catch (Exception $e) {
                    echo "<div class='text-danger fw-semibold d-flex align-items-center gap-1' style='font-size: 13.5px;'>
                            <i class='bx bx-x-circle fs-5'></i> การเชื่อมต่อล้มเหลว: " . htmlspecialchars($e->getMessage()) . "
                          </div>";
                }
                ?>
            </div>
            <div class="mt-2 text-muted" style="font-size: 11.5px;">
                Host: <?= htmlspecialchars($db1_cfg["servername"] ?? ''); ?> | User: <?= htmlspecialchars($db1_cfg["username"] ?? ''); ?>
            </div>
        </div>
    </div>

    <!-- ผลการทดสอบ DB 2 -->
    <div class="col-md-6">
        <div class="p-3 rounded-3 border" style="background: #f8fafc;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark" style="font-size: 14px;">
                    <i class="bx bx-pulse text-primary me-1"></i> ฐานข้อมูลที่ 2 (HOSxP)
                </span>
                <span class="badge bg-light text-muted border" style="font-size: 11px;">
                    <?= htmlspecialchars($db2_cfg["dbname"] ?? ''); ?>
                </span>
            </div>
            <div>
                <?php
                try {
                    $start_time = microtime(true);
                    $db2_conn = @new mysqli(
                        $db2_cfg["servername"],
                        $db2_cfg["username"],
                        $db2_cfg["password"],
                        $db2_cfg["dbname"]
                    );
                    $latency = round((microtime(true) - $start_time) * 1000, 1);

                    if ($db2_conn->connect_error) {
                        echo "<div class='text-danger fw-semibold d-flex align-items-center gap-1' style='font-size: 13.5px;'>
                                <i class='bx bx-x-circle fs-5'></i> การเชื่อมต่อล้มเหลว: " . htmlspecialchars($db2_conn->connect_error) . "
                              </div>";
                    } else {
                        echo "<div class='text-success fw-semibold d-flex align-items-center justify-content-between' style='font-size: 13.5px;'>
                                <span><i class='bx bx-check-circle fs-5'></i> เชื่อมต่อสำเร็จ! (Connected)</span>
                                <span class='badge bg-primary-subtle text-primary border border-primary' style='font-size: 11px;'>${latency} ms</span>
                              </div>";
                        $db2_conn->close();
                    }
                } catch (Exception $e) {
                    echo "<div class='text-danger fw-semibold d-flex align-items-center gap-1' style='font-size: 13.5px;'>
                            <i class='bx bx-x-circle fs-5'></i> การเชื่อมต่อล้มเหลว: " . htmlspecialchars($e->getMessage()) . "
                          </div>";
                }
                ?>
            </div>
            <div class="mt-2 text-muted" style="font-size: 11.5px;">
                Host: <?= htmlspecialchars($db2_cfg["servername"] ?? ''); ?> | User: <?= htmlspecialchars($db2_cfg["username"] ?? ''); ?>
            </div>
        </div>
    </div>
</div>