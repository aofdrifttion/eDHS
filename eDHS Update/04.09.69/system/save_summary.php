<?php
// save_summary.php
// บันทึกการตั้งค่ารายชื่อและหน้าที่ผู้ลงนามรายงานสรุปลูกหนี้สิทธิ
// จัดเก็บในไฟล์ JSON database_config/report_signers.json (Zero-Migration)
// และอัปเดต 10 คอลัมน์เดิมในตาราง summary_responsibles เพื่อความเข้ากันได้ย้อนหลัง 100%

session_start();
header('Content-Type: text/html; charset=utf-8');
require './database_config/config.php';
require_once './includes/signers_helper.php';

// รับค่าภาพรวม 5 ท่าน
$p1 = trim($_POST['p1'] ?? '');
$s1 = trim($_POST['s1'] ?? '');
$p2 = trim($_POST['p2'] ?? '');
$s2 = trim($_POST['s2'] ?? '');
$p3 = trim($_POST['p3'] ?? '');
$s3 = trim($_POST['s3'] ?? '');
$p4 = trim($_POST['p4'] ?? '');
$s4 = trim($_POST['s4'] ?? '');
$p5 = trim($_POST['p5'] ?? '');
$s5 = trim($_POST['s5'] ?? '');

// ตรวจสอบค่าว่างของ 5 บทบาทหลัก
if ($p1 == "" || $s1 == "" || $p2 == "" || $s2 == "" || $p3 == "" || $s3 == "" || $p4 == "" || $s4 == "" || $p5 == "" || $s5 == "") {
    echo "กรุณากรอกข้อมูลผู้รับผิดชอบหลักให้ครบทุกช่อง";
    exit;
}

// 1. ส่วนการตั้งลูกหนี้ (งานประกันสุขภาพ - ตั้งลูกหนี้.php)
$setup_name1 = trim($_POST['setup_name1'] ?? $s1);
$setup_pos1  = trim($_POST['setup_pos1'] ?? $p1);
$setup_role1 = trim($_POST['setup_role1'] ?? "ผู้จัดทำรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ");

$setup_name2 = trim($_POST['setup_name2'] ?? $s2);
$setup_pos2  = trim($_POST['setup_pos2'] ?? $p2);
$setup_role2 = trim($_POST['setup_role2'] ?? "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ");

$setup_name3 = trim($_POST['setup_name3'] ?? $s3);
$setup_pos3  = trim($_POST['setup_pos3'] ?? $p3);
$setup_role3 = trim($_POST['setup_role3'] ?? "หัวหน้ากลุ่มงานประกันสุขภาพ");

// 2. ส่วนของการตัดลูกหนี้ (Debtor-rights.php)
$cut_name1 = trim($_POST['cut_name1'] ?? $s1);
$cut_pos1  = trim($_POST['cut_pos1'] ?? $p1);
$cut_role1 = trim($_POST['cut_role1'] ?? "ผู้จัดทำรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ");

$cut_name2 = trim($_POST['cut_name2'] ?? $s2);
$cut_pos2  = trim($_POST['cut_pos2'] ?? $p2);
$cut_role2 = trim($_POST['cut_role2'] ?? "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ");

$cut_name3 = trim($_POST['cut_name3'] ?? $s3);
$cut_pos3  = trim($_POST['cut_pos3'] ?? $p3);
$cut_role3 = trim($_POST['cut_role3'] ?? "ผู้บันทึกลูกหนี้สิทธิ\nกลุ่มงานบัญชี");

// 3. ส่วนการตัดลูกหนี้รายตัว (ลูกหนี้รายตัว2.php)
$item_name1 = trim($_POST['item_name1'] ?? $s1);
$item_pos1  = trim($_POST['item_pos1'] ?? $p1);
$item_role1 = trim($_POST['item_role1'] ?? "ผู้จัดทำรายงานลูกหนี้สิทธิ");

$item_name2 = trim($_POST['item_name2'] ?? $s2);
$item_pos2  = trim($_POST['item_pos2'] ?? $p2);
$item_role2 = trim($_POST['item_role2'] ?? "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ");

$item_name3 = trim($_POST['item_name3'] ?? $s3);
$item_pos3  = trim($_POST['item_pos3'] ?? $p3);
$item_role3 = trim($_POST['item_role3'] ?? "ผู้บันทึกลูกหนี้สิทธิ");

// ข้อมูล Roster บุคลากร
$officers_list = [];
if (!empty($_POST['officers_json'])) {
    $decoded = json_decode($_POST['officers_json'], true);
    if (is_array($decoded)) {
        $officers_list = $decoded;
    }
}

if (empty($officers_list)) {
    $officers_list = [
        ['name' => $s1, 'pos' => $p1],
        ['name' => $s2, 'pos' => $p2],
        ['name' => $s3, 'pos' => $p3],
        ['name' => $s4, 'pos' => $p4],
        ['name' => $s5, 'pos' => $p5],
    ];
}

$config_data = [
    'officers_list' => $officers_list,
    'setup' => [
        'name1' => $setup_name1, 'pos1' => $setup_pos1, 'role1' => $setup_role1,
        'name2' => $setup_name2, 'pos2' => $setup_pos2, 'role2' => $setup_role2,
        'name3' => $setup_name3, 'pos3' => $setup_pos3, 'role3' => $setup_role3,
    ],
    'cut' => [
        'name1' => $cut_name1, 'pos1' => $cut_pos1, 'role1' => $cut_role1,
        'name2' => $cut_name2, 'pos2' => $cut_pos2, 'role2' => $cut_role2,
        'name3' => $cut_name3, 'pos3' => $cut_pos3, 'role3' => $cut_role3,
    ],
    'item' => [
        'name1' => $item_name1, 'pos1' => $item_pos1, 'role1' => $item_role1,
        'name2' => $item_name2, 'pos2' => $item_pos2, 'role2' => $item_role2,
        'name3' => $item_name3, 'pos3' => $item_pos3, 'role3' => $item_role3,
    ],
    'overview' => [
        'name1' => $s1, 'pos1' => $p1,
        'name2' => $s2, 'pos2' => $p2,
        'name3' => $s3, 'pos3' => $p3,
        'name4' => $s4, 'pos4' => $p4,
        'name5' => $s5, 'pos5' => $p5,
    ]
];

$success = save_signers_config($config_data, $conn);

if ($success) {
    if (function_exists('system_log')) {
        system_log($conn, 'ตั้งค่ารายงาน (Settings)', 'UPDATE', "ผู้ใช้บันทึกการตั้งค่ารายชื่อผู้รับผิดชอบรายงาน (Summary Responsibles แยก 3 ส่วน)");
    }
    echo "บันทึกข้อมูลสำเร็จ";
} else {
    echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
}

if ($conn) {
    $conn->close();
}
