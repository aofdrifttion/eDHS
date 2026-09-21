<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title></title>
	<script src="../assets/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/dist/sweetalert.css">
</head>
<body>

</body>
</html>

<?php
require './database_config/config.php';
require_once './database_config/db_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vn_original = $_POST['vn'] ?? ''; // เก็บค่า vn ดั้งเดิมที่ส่งมาจากฟอร์ม

    // ตรวจสอบว่า VN ว่างหรือไม่ ถ้าว่างห้าม insert
    if (empty(trim($vn_original))) {
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "ไม่สามารถบันทึกได้!",
                text: "ไม่สามารถบันทึกได้! ทำรายการใหม่อีกครั้ง",
                confirmButtonText: "ตกลง"
            }).then(() => {
                window.location.href = "นำเข้าลูกหนี้EMS.php";
            });
        </script>';
        exit();
    }

    $hn         = $_POST['hn'];
    $cid        = encrypt_data($_POST['cid']);
    $ptname     = $_POST['ptname'];
    $sex        = $_POST['sex'];
    $age        = $_POST['age'];
    $vstdate    = $_POST['vstdate'];
    $vsttime    = $_POST['vsttime'];
    $accountcode = $_POST['accountcode'];
    $accountname = $_POST['accountname'];
    $income     = $_POST['income'];
    $monthtxt   = $_POST['monthtxt'];

    $uc_money   = $income;
    $debit      = $income;
    $totalall   = $income;
    $hospcode   = $hospcode; 
    $pttype_eclaim_id   = '99';
    $pttype_eclaim_name   = 'ระบบปฏิบัติการฉุกเฉิน EMS';
    $pttypename   = 'ระบบปฏิบัติการฉุกเฉิน EMS';
    $no   = 1;

    // --- [ขั้นตอนที่ 1] เช็คก่อนว่า VN แรกเริ่มซ้ำหรือไม่ ---
    $check_sql = "SELECT COUNT(*) FROM imr_tb_debtor_rights_opd WHERE vn = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $vn_original);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    $vn = $vn_original; // ตั้งค่าเริ่มต้นให้ตัวแปรที่จะใช้ Insert

    // --- [ขั้นตอนที่ 2] ถ้าซ้ำ ค่อยเข้ากระบวนการสุ่มเปลี่ยน 3 หลักท้าย ---
    if ($count > 0) {
        $is_duplicate = true;

        while ($is_duplicate) {
            // สุ่มเลข 3 หลักท้ายใหม่ (เช่น 000-999) และต่อท้ายแทนที่ 3 ตัวเดิม
            $vn_prefix = substr($vn_original, 0, -3); 
            $random_suffix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT); 
            $vn = $vn_prefix . $random_suffix;

            // นำ VN ตัวที่สุ่มใหม่ไปเช็คใน DB อีกรอบว่ายังซ้ำอยู่อีกไหม
            $recheck_sql = "SELECT COUNT(*) FROM imr_tb_debtor_rights_opd WHERE vn = ?";
            $recheck_stmt = $conn->prepare($recheck_sql);
            $recheck_stmt->bind_param("s", $vn);
            $recheck_stmt->execute();
            $recheck_stmt->bind_result($recount);
            $recheck_stmt->fetch();
            $recheck_stmt->close();

            // ถ้าไม่ซ้ำแล้ว ให้หลุดออกจาก Loop เพื่อเตรียมนำไปใช้งาน
            if ($recount == 0) {
                $is_duplicate = false;
            }
        }
    } 
    // หมายเหตุ: ถ้า $count == 0 (ไม่ซ้ำแต่แรก) จะข้าม if นี้ไปเลย และใช้ $vn ตัวเดิมรันต่อด้านล่างครับ

    // --- [ขั้นตอนที่ 3] ทำการ บันทึกข้อมูล (INSERT) ---
    $sql = "INSERT INTO imr_tb_debtor_rights_opd 
    (no, vn, hn, cid, ptname, sex, age, vstdate, vsttime, pttype_eclaim_id, pttype_eclaim_name, pttypename, accountcode, accountname, income, uc_money, debit, totalall, hospcode, monthtxt, mobile)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '-')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssssssssssssss", 
        $no, $vn, $hn, $cid, $ptname, $sex, $age, $vstdate, $vsttime, $pttype_eclaim_id, $pttype_eclaim_name, $pttypename, $accountcode, $accountname, $income, $uc_money, $debit, $totalall, $hospcode, $monthtxt );

    if ($stmt->execute()) {
        if (function_exists('system_log')) {
            system_log($conn, 'นำเข้าลูกหนี้ EMS', 'INSERT', "เพิ่มข้อมูลลูกหนี้ EMS HN: $hn (VN: $vn)");
        }
        if ($vn === $vn_original) {
		    $msg_text = "ข้อมูลถูกบันทึกเรียบร้อยแล้ว";
		} else {
		    $msg_text = "ข้อมูลถูกบันทึกเรียบร้อยแล้ว";
		}
        
        echo '<script>
            Swal.fire({
                icon: "success",
                title: "บันทึกสำเร็จ!",
                text: "' . $msg_text . '",
                confirmButtonText: "ตกลง"
            }).then(() => {
                window.location.href = "นำเข้าลูกหนี้EMS.php";
            });
        </script>';
        exit();
    } else {
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "เกิดข้อผิดพลาด!",
                text: "ไม่สามารถบันทึกข้อมูลได้: ' . addslashes($conn->error) . '",
                confirmButtonText: "ตกลง"
            }).then(() => {
                window.location.href = "นำเข้าลูกหนี้EMS.php";
            });
        </script>';
    }

    $stmt->close();
    $conn->close();
}
?>





