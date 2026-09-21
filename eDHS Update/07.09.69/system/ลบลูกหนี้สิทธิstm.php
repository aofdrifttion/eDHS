<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
ignore_user_abort(true);
set_time_limit(0); 
require './database_config/config.php';
require_once './database_config/db_helper.php';
require_once __DIR__ . '/apistm/stm_helper.php';

// รับประกันโครงสร้างตาราง imr_tb_check_invoice ให้พร้อมใช้งานเสมอ (Auto-Migration)
ensure_stm_columns($conn);

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || !isset($_SESSION['fullname'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_stm') {
    
    if (empty($_POST['deletestm']) || empty($_POST['password'])) {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
        exit;
    }

    $deletestm = $_POST['deletestm'];
    $password = $_POST['password'];
    $user_id = $_SESSION['user_id'];
    $fullname = $_SESSION['fullname'];

    // 2. ตรวจสอบรหัสผ่าน
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านไม่ถูกต้อง']);
        exit;
    }

    $is_doc_level = false;
    $clean_target = $deletestm;
    if (strpos($deletestm, 'DOC:') === 0) {
        $is_doc_level = true;
        $clean_target = substr($deletestm, 4);
    } elseif (strpos($deletestm, 'REP:') === 0) {
        $clean_target = substr($deletestm, 4);
    }

    $conn->begin_transaction();

    try {
        // 3. ดึงเลข vn ทั้งหมดภายใต้เอกสารหรือ rep นี้ออกมาเพื่อไปอัปเดตตารางอื่น
        $sql_select = $is_doc_level ? 
            "SELECT vn FROM imr_tb_check_invoice WHERE docno = ?" : 
            "SELECT vn FROM imr_tb_check_invoice WHERE rep = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("s", $clean_target);
        $stmt_select->execute();
        $res_select = $stmt_select->get_result();

        // เตรียม Statement สำหรับ Update (เตรียมครั้งเดียวใช้ซ้ำใน Loop เพื่อความเร็ว)
        $upd_opd = $conn->prepare("UPDATE imr_tb_debtor_rights_opd SET mobile='-' WHERE vn = ?");
        $upd_ipd = $conn->prepare("UPDATE imr_tb_debtor_rights_ipd SET mobile='-' WHERE an = ?");

        while ($row = $res_select->fetch_assoc()) {
            $current_vn = $row['vn'];
            if (!empty($current_vn)) {
                // อัปเดตฝั่ง OPD โดยใช้ vn
                $upd_opd->bind_param("s", $current_vn);
                $upd_opd->execute();

                // อัปเดตฝั่ง IPD โดยใช้ vn ไปแมตช์กับ an 
                $upd_ipd->bind_param("s", $current_vn);
                $upd_ipd->execute();
            }
        }
        $stmt_select->close();
        $upd_opd->close();
        $upd_ipd->close();

        // 4. เมื่ออัปเดตตารางที่เกี่ยวข้องเสร็จแล้ว ค่อยลบข้อมูลใน imr_tb_check_invoice
        $sql_del = $is_doc_level ? 
            "DELETE FROM imr_tb_check_invoice WHERE docno = ?" : 
            "DELETE FROM imr_tb_check_invoice WHERE rep = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("s", $clean_target);
        $stmt_del->execute();
        $deleted_rows = $stmt_del->affected_rows;
        $stmt_del->close();

        // ถ้ามาถึงตรงนี้โดยไม่มี Error ให้ยืนยันการเปลี่ยนแปลงทั้งหมด
        $conn->commit();
        if (function_exists('system_log')) {
            $log_tag = $is_doc_level ? "เอกสาร docno: $clean_target" : "Rep: $clean_target";
            system_log($conn, 'นำเข้า STM & ตัดลูกหนี้ตาม STM', 'DELETE', "ลบข้อมูล STM ($log_tag, จำนวน $deleted_rows รายการ)");
        }
        echo json_encode(['status' => 'success', 'message' => "ลบข้อมูล STM เรียบร้อยแล้ว (จำนวน $deleted_rows รายการ)"]);


            date_default_timezone_set('Asia/Bangkok');
            ///ส่งแจ้งเตือนไลน์กลุ่ม
            $summaryText = "🗑️ ลบข้อมูล STM เลขหนังสือที่ $deletestm";

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

            $noteText = "ระบบลบข้อมูล STM สำเร็จ";

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
                                "text" => "ลบข้อมูลโดย : "  . $fullname,   
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



    } catch (Exception $e) {
        // หากมีข้อผิดพลาด ให้ยกเลิกทุกอย่างที่ทำมา (Rollback)
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
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