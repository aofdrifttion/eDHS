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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_ipd') {
    
    if (empty($_POST['monthi']) || empty($_POST['yeari']) || $_POST['monthi'] == "00" || $_POST['yeari'] == "00") {
        echo json_encode(["status" => "error", "message" => "กรุณาเลือกเดือนและปีให้ถูกต้อง"]);
        exit;
    }

    $monthtxt = $_POST['monthi'] . '-' . $_POST['yeari'];

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

    // ดำเนินการลบข้อมูล (ตัวอย่าง SQL: ต้องแก้ตามโครงสร้างตารางจริงของคุณ)

    // ตรวจสอบความปลอดภัยทางการเงิน: ตรวจสอบว่าในเดือนนี้มีรายการที่ออกใบเสร็จหรือได้รับเงินชดเชยแล้วหรือไม่ (ทั้งผังหลัก, CR และ SSS)
    $sql_check = "SELECT COUNT(*) as count FROM imr_tb_debtor_rights_ipd WHERE monthtxt = ? AND ((bill IS NOT NULL AND TRIM(bill) != '') OR (follow_money IS NOT NULL AND CAST(follow_money AS DECIMAL(15,2)) > 0))";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $monthtxt);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();
    $billed_main = intval($row_check['count'] ?? 0);

    // ตรวจสอบในตารางกลุ่มย่อย CR
    $billed_cr = 0;
    $chk_tbl_cr = $conn->query("SHOW TABLES LIKE 'imr_tb_debtor_cr_breakdown'");
    if ($chk_tbl_cr && $chk_tbl_cr->num_rows > 0) {
        $stmt_check_cr = $conn->prepare("SELECT COUNT(*) as count FROM imr_tb_debtor_cr_breakdown WHERE visit_type = 'IPD' AND monthtxt = ? AND ((bill IS NOT NULL AND TRIM(bill) != '') OR (compensated IS NOT NULL AND compensated > 0))");
        if ($stmt_check_cr) {
            $stmt_check_cr->bind_param("s", $monthtxt);
            $stmt_check_cr->execute();
            $res_cr = $stmt_check_cr->get_result();
            $r_cr = $res_cr->fetch_assoc();
            $billed_cr = intval($r_cr['count'] ?? 0);
            $stmt_check_cr->close();
        }
    }

    // ตรวจสอบในตารางกลุ่มย่อย ประกันสังคม SSS
    $billed_sss = 0;
    $chk_tbl_sss = $conn->query("SHOW TABLES LIKE 'imr_tb_debtor_sss_breakdown'");
    if ($chk_tbl_sss && $chk_tbl_sss->num_rows > 0) {
        $stmt_check_sss = $conn->prepare("SELECT COUNT(*) as count FROM imr_tb_debtor_sss_breakdown WHERE visit_type = 'IPD' AND monthtxt = ? AND ((bill IS NOT NULL AND TRIM(bill) != '') OR (compensated IS NOT NULL AND compensated > 0))");
        if ($stmt_check_sss) {
            $stmt_check_sss->bind_param("s", $monthtxt);
            $stmt_check_sss->execute();
            $res_sss = $stmt_check_sss->get_result();
            $r_sss = $res_sss->fetch_assoc();
            $billed_sss = intval($r_sss['count'] ?? 0);
            $stmt_check_sss->close();
        }
    }

    if ($billed_main > 0 || $billed_cr > 0 || $billed_sss > 0) {
        $block_reasons = [];
        if ($billed_main > 0) $block_reasons[] = "ลูกหนี้หลัก $billed_main รายการ";
        if ($billed_cr > 0) $block_reasons[] = "กลุ่มย่อย CR $billed_cr รายการ";
        if ($billed_sss > 0) $block_reasons[] = "กลุ่มย่อย ประกันสังคม $billed_sss รายการ";
        echo json_encode([
            'status' => 'error', 
            'message' => 'ไม่สามารถลบได้ เนื่องจากมีลูกหนี้ในเดือนนี้ถูกออกใบเสร็จหรือได้รับเงินชดเชยแล้ว (' . implode(', ', $block_reasons) . ')'
        ]);
        exit;
    }

    // ดำเนินการลบข้อมูล (ใช้ Transaction ลบทั้งตารางหลัก และล้างตารางแจกแจงกลุ่มย่อย CR/SSS เพื่อป้องกันข้อมูลตกค้าง)
    $conn->begin_transaction();
    try {
        // 1. ลบจากตารางหลัก imr_tb_debtor_rights_ipd
        $stmt_main = $conn->prepare("DELETE FROM imr_tb_debtor_rights_ipd WHERE monthtxt = ?");
        $stmt_main->bind_param("s", $monthtxt);
        $stmt_main->execute();
        $stmt_main->close();

        // 2. ล้างตารางแจกแจงกลุ่มย่อย CR
        if ($chk_tbl_cr && $chk_tbl_cr->num_rows > 0) {
            $stmt_del_cr = $conn->prepare("DELETE FROM imr_tb_debtor_cr_breakdown WHERE visit_type = 'IPD' AND monthtxt = ?");
            if ($stmt_del_cr) {
                $stmt_del_cr->bind_param("s", $monthtxt);
                $stmt_del_cr->execute();
                $stmt_del_cr->close();
            }
        }

        // 3. ล้างตารางแจกแจงกลุ่มย่อย ประกันสังคม SSS
        if ($chk_tbl_sss && $chk_tbl_sss->num_rows > 0) {
            $stmt_del_sss = $conn->prepare("DELETE FROM imr_tb_debtor_sss_breakdown WHERE visit_type = 'IPD' AND monthtxt = ?");
            if ($stmt_del_sss) {
                $stmt_del_sss->bind_param("s", $monthtxt);
                $stmt_del_sss->execute();
                $stmt_del_sss->close();
            }
        }

        $conn->commit();

        if (function_exists('system_log')) {
            system_log($conn, 'ระบบลูกหนี้ (Delete)', 'DELETE', "ลบข้อมูลลูกหนี้สิทธิ IPD เดือน $monthtxt พร้อมล้างข้อมูลกลุ่มย่อย CR และ ประกันสังคม เรียบร้อยแล้ว");
        }
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } catch (\Throwable $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()]);
        exit;
    }

    date_default_timezone_set('Asia/Bangkok');
    ///ส่งแจ้งเตือนไลน์กลุ่ม
    $summaryText = "🗑️ ลบข้อมูลลูกหนี้ IPD เดือน $monthtxt";

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

    $noteText = "ระบบลบข้อมูลลูกหนี้ IPD สำเร็จ";

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
