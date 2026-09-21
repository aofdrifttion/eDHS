<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<div class='alert alert-danger mb-0'>❌ Access Denied: คุณไม่มีสิทธิ์เข้าถึงส่วนนี้</div>");
}
$configFile = __DIR__ . "/config.json";

if (!file_exists($configFile)) {
    die("<div class='alert alert-warning mb-0'>⚠️ ไม่พบไฟล์การตั้งค่า (config.json) กรุณากดบันทึกการตั้งค่าก่อน</div>");
}

$configData = json_decode(file_get_contents($configFile), true);
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
                    <?= htmlspecialchars($configData["db1"]["dbname"] ?? ''); ?>
                </span>
            </div>
            <div>
                <?php
                try {
                    $start_time = microtime(true);
                    $db1_conn = @new mysqli(
                        $configData["db1"]["servername"],
                        $configData["db1"]["username"],
                        $configData["db1"]["password"],
                        $configData["db1"]["dbname"]
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
                Host: <?= htmlspecialchars($configData["db1"]["servername"] ?? ''); ?> | User: <?= htmlspecialchars($configData["db1"]["username"] ?? ''); ?>
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
                    <?= htmlspecialchars($configData["db2"]["dbname"] ?? ''); ?>
                </span>
            </div>
            <div>
                <?php
                try {
                    $start_time = microtime(true);
                    $db2_conn = @new mysqli(
                        $configData["db2"]["servername"],
                        $configData["db2"]["username"],
                        $configData["db2"]["password"],
                        $configData["db2"]["dbname"]
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
                Host: <?= htmlspecialchars($configData["db2"]["servername"] ?? ''); ?> | User: <?= htmlspecialchars($configData["db2"]["username"] ?? ''); ?>
            </div>
        </div>
    </div>
</div>