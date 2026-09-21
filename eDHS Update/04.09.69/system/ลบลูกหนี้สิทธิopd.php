<?php
header('Content-Type: text/html; charset=utf-8');
header('Content-Type: application/json');
session_start();
ignore_user_abort(true);
set_time_limit(0); // No time limit
require './database_config/config.php';


// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['user_id']) || !isset($_SESSION['fullname'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ']);
    exit;
}

// รับข้อมูลจาก ajax
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_opd') {
    
    if (empty($_POST['month']) || empty($_POST['year']) || $_POST['month'] == "00" || $_POST['year'] == "00") {
        echo json_encode(["status" => "error", "message" => "กรุณาเลือกเดือนและปีให้ถูกต้อง"]);
        exit;
    }

    $monthtxt = $_POST['month'] . '-' . $_POST['year'];

    $password = $_POST['password'];
    $user_id = $_SESSION['user_id'];

    // ดึงรหัสผ่านจริงของผู้ใช้งานจากฐานข้อมูล
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้ใช้']);
        exit;
    }

    $user = $result->fetch_assoc();

    // ตรวจสอบรหัสผ่าน
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านไม่ถูกต้อง']);
        exit;
    }

    // ตรวจสอบว่าในเดือนนี้มีรายการที่ออกใบเสร็จไปแล้วหรือไม่
    $sql_check = "SELECT COUNT(*) as count FROM imr_tb_debtor_rights_opd WHERE monthtxt = ? AND bill IS NOT NULL AND bill != ''";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $monthtxt);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    
    if ($row_check['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีลูกหนี้ในเดือนนี้ถูกออกใบเสร็จไปแล้ว จำนวน ' . $row_check['count'] . ' รายการ']);
        exit;
    }

    // ดำเนินการลบข้อมูล (ตัวอย่าง SQL: ต้องแก้ตามโครงสร้างตารางจริงของคุณ)
    $sql = "DELETE FROM imr_tb_debtor_rights_opd WHERE monthtxt = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $monthtxt);

    if ($stmt->execute()) {
        if (function_exists('system_log')) {
            system_log($conn, 'ระบบลูกหนี้ (Delete)', 'DELETE', "ลบข้อมูลลูกหนี้สิทธิ OPD เดือน $monthtxt เรียบร้อยแล้ว");
        }
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);

    date_default_timezone_set('Asia/Bangkok');
    ///ส่งแจ้งเตือนไลน์กลุ่ม
    $summaryText = "🗑️ ลบข้อมูลลูกหนี้ OPD เดือน $monthtxt";

    $thaiMonths = [
        "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน",
        "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม",
        "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];

    $day   = date('d');
    $month = $thaiMonths[(int)date('m')];
    $year  = date('Y') + 543;
    $time  = date('H:i');

    $currentDateTime = "$day $month $year เวลา $time น.";

    $noteText = "ระบบลบข้อมูลลูกหนี้ OPD สำเร็จ";

    $flexMessage = [
        "type" => "flex",
        "altText" => "แจ้งเตือน: eDHS",
        "contents" => [
            "type" => "bubble",
            "size" => "giga",
            "body" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "sm",
                "contents" => [
                    [
                        "type" => "image",
                        "url" => "https://phonsai-hos.com/main/web/img/eDHS0.png",
                        "size" => "sm",
                        "align" => "center",
                        "margin" => "none"
                    ],
                    [
                        "type" => "text",
                        "text" => "eDebtor Hospital System: eDHS",
                        "weight" => "bold",
                        "size" => "md",
                        "align" => "center",
                        "wrap" => true
                    ],
                    [
                        "type" => "separator",
                        "margin" => "md"
                    ],
                    [
                        "type" => "text",
                        "text" => "รายละเอียด : " . $summaryText,
                        "size" => "sm",
                        "wrap" => true
                    ],
                    [
                        "type" => "text",
                        "text" => "ลบข้อมูล : "  . $_SESSION['fullname'],   
                        "size" => "sm",
                        "wrap" => true
                    ],
                    [
                        "type" => "text",
                        "text" => "หมายเหตุ : " . $noteText,  
                        "size" => "sm",
                        "wrap" => true
                    ],
                    
                    [
                        "type" => "text",
                        "text" => "ระบบ : eDHS",
                        "size" => "sm"
                    ],
                    [
                        "type" => "separator",
                        "margin" => "md"
                    ],
                    [
                        "type" => "text",
                        "text" => "วันที่ : " . $currentDateTime,
                        "size" => "sm",
                        "align" => "center",
                        "color" => "#666666"
                    ]
                ]
            ],
            "footer" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "sm",
                "contents" => [
                    [
                        "type" => "text",
                        "text" => "ระบบแจ้งเตือนอัตโนมัติ",
                        "size" => "xs",
                        "align" => "center",
                        "color" => "#999999"
                    ]
                ]
            ]
        ]
    ];

    $payload = [
        "cid" => ["1234567891234"], 
        "messages" => [$flexMessage],
        "message_title" => " ",
        "message_html" => " ",
        "message_text" => " ",
        "message_type" => "HPT"
    ];

    sendNotify($payload, $Client_ID, $Secret);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดขณะลบข้อมูล']);
    }
}



function sendNotify($payload, $Client_ID, $Secret) {
    $config_file = __DIR__ . '/database_config/config.json';
    if (file_exists($config_file)) {
        $conf = json_decode(file_get_contents($config_file), true);
        if (isset($conf['notify_enable']) && $conf['notify_enable'] === '0') {
            return; // แจ้งเตือนถูกปิดไว้
        }
    }

    $url = "https://morpromt2f.moph.go.th/api/notify/send";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "client-key: $Client_ID",
            "secret-key: $Secret"
        ]
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('Notify Error: ' . curl_error($ch));
    } else {
        error_log('Notify Response: ' . $response);
    }

    curl_close($ch);
}


?>
