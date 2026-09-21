<?php
include 'check_auth.php';
include './database_config/config.php';

// Check if user is admin
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($role !== 'admin') {
    die("Access Denied: You do not have permission to view this page.");
}

// Handle toggle update
$configFile = __DIR__ . "/database_config/config.json";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_audit_log'])) {
    $configData = json_decode(file_get_contents($configFile), true);
    $configData['audit_log_enable'] = $_POST['audit_log_enable'];
    file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Redirect to prevent form resubmission
    header("Location: report_logs.php");
    exit();
}

$configData = json_decode(file_get_contents($configFile), true);
$audit_enabled = isset($configData['audit_log_enable']) ? $configData['audit_log_enable'] : "0";

// Fetch filter options
$users = $conn->query("SELECT id, fullname FROM users ORDER BY fullname ASC");
$modules = $conn->query("SELECT DISTINCT module FROM system_logs ORDER BY module ASC");

// Build query for logs
$where = [];
$params = [];
$types = "";

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_user = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$filter_module = isset($_GET['module']) ? $_GET['module'] : '';

if (!empty($start_date)) {
    $where[] = "DATE(l.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}
if (!empty($end_date)) {
    $where[] = "DATE(l.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}
if (!empty($filter_user)) {
    $where[] = "l.user_id = ?";
    $params[] = $filter_user;
    $types .= "i";
}
if (!empty($filter_module)) {
    $where[] = "l.module = ?";
    $params[] = $filter_module;
    $types .= "s";
}

$sql = "SELECT l.*, u.fullname FROM system_logs l LEFT JOIN users u ON l.user_id = u.id";
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY l.created_at DESC LIMIT 1000";

$stmt = $conn->prepare($sql);
if ($stmt && count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายงานประวัติการใช้งานระบบ (Audit Log)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap');
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen p-4 md:p-8">
<div class="max-w-7xl mx-auto">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-semibold text-slate-800 flex items-center">
            <i class="fas fa-history text-indigo-600 mr-3 text-3xl"></i> 
            รายงานประวัติการใช้งานระบบ
        </h2>
        <a href="index.php" class="bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 px-4 py-2 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> กลับหน้าหลัก
        </a>
    </div>

    <!-- Settings Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 p-5">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h5 class="text-lg font-medium text-slate-800 mb-1">สถานะระบบเก็บประวัติ (Log Status)</h5>
                <p class="text-sm text-slate-500">หากปิดระบบ จะไม่มีการบันทึกการกระทำใดๆ ของผู้ใช้ลงฐานข้อมูล</p>
            </div>
            <form method="POST" class="flex items-center m-0 gap-3">
                <input type="hidden" name="toggle_audit_log" value="1">
                <div class="relative">
                    <select name="audit_log_enable" class="appearance-none bg-slate-50 border border-slate-300 text-slate-700 py-2 pl-4 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer shadow-sm transition" onchange="this.form.submit()">
                        <option value="1" <?= $audit_enabled === "1" ? 'selected' : '' ?>>เปิดใช้งาน (Enabled)</option>
                        <option value="0" <?= $audit_enabled === "0" ? 'selected' : '' ?>>ปิดใช้งาน (Disabled)</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                        <i class="fas fa-chevron-down text-sm"></i>
                    </div>
                </div>
                <?php if ($audit_enabled === "1"): ?>
                    <span class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full text-sm font-medium flex items-center shadow-sm">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></div> กำลังทำงาน
                    </span>
                <?php else: ?>
                    <span class="bg-rose-100 text-rose-700 border border-rose-200 px-3 py-1 rounded-full text-sm font-medium flex items-center shadow-sm">
                        <div class="w-2 h-2 rounded-full bg-rose-500 mr-2"></div> หยุดทำงาน
                    </span>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 p-5">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ตั้งแต่วันที่</label>
                <input type="date" name="start_date" class="w-full bg-slate-50 border border-slate-300 text-slate-700 py-2 px-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ถึงวันที่</label>
                <input type="date" name="end_date" class="w-full bg-slate-50 border border-slate-300 text-slate-700 py-2 px-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ผู้ใช้งาน</label>
                <select name="user_id" class="w-full bg-slate-50 border border-slate-300 text-slate-700 py-2 px-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm">
                    <option value="">-- ทั้งหมด --</option>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['fullname']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ส่วนของระบบ (Module)</label>
                <select name="module" class="w-full bg-slate-50 border border-slate-300 text-slate-700 py-2 px-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm">
                    <option value="">-- ทั้งหมด --</option>
                    <?php while ($m = $modules->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($m['module']) ?>" <?= $filter_module == $m['module'] ? 'selected' : '' ?>><?= htmlspecialchars($m['module']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition duration-200 flex justify-center items-center">
                    <i class="fas fa-search mr-2"></i> ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800 text-slate-100 text-sm">
                        <th class="py-3 px-4 font-medium border-b border-slate-700">วัน-เวลา</th>
                        <th class="py-3 px-4 font-medium border-b border-slate-700">ผู้ใช้งาน</th>
                        <th class="py-3 px-4 font-medium border-b border-slate-700">ส่วนระบบ (Module)</th>
                        <th class="py-3 px-4 font-medium border-b border-slate-700">คำสั่ง (Action)</th>
                        <th class="py-3 px-4 font-medium border-b border-slate-700">รายละเอียด (Details)</th>
                        <th class="py-3 px-4 font-medium border-b border-slate-700">IP Address</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-200">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition duration-150">
                                <td class="py-3 px-4 whitespace-nowrap text-slate-600"><?= date('d/m/Y H:i:s', strtotime($row['created_at'])) ?></td>
                                <td class="py-3 px-4 font-medium text-slate-800"><?= htmlspecialchars($row['fullname'] ?: 'Unknown (ID:'.$row['user_id'].')') ?></td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                        <?= htmlspecialchars($row['module']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php 
                                        $action = htmlspecialchars($row['action']);
                                        $badge_class = 'bg-indigo-100 text-indigo-800 border-indigo-200';
                                        if (strtoupper($action) == 'UPDATE') $badge_class = 'bg-amber-100 text-amber-800 border-amber-200';
                                        else if (strtoupper($action) == 'DELETE') $badge_class = 'bg-rose-100 text-rose-800 border-rose-200';
                                        else if (strtoupper($action) == 'CREATE' || strtoupper($action) == 'INSERT') $badge_class = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?= $badge_class ?>">
                                        <?= $action ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 break-words min-w-[250px]"><?= htmlspecialchars($row['details']) ?></td>
                                <td class="py-3 px-4 text-slate-500 font-mono text-xs"><?= htmlspecialchars($row['ip_address']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-slate-300"></i>
                                <p>ไม่พบประวัติการใช้งานตามเงื่อนไขที่ค้นหา</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($result && $result->num_rows > 0): ?>
        <div class="bg-slate-50 px-4 py-3 border-t border-slate-200 text-sm text-slate-500 flex justify-between items-center">
            <span>แสดงข้อมูลล่าสุด (สูงสุด 1,000 รายการ)</span>
            <span>พบทั้งหมด <?= $result->num_rows ?> รายการ</span>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
