<?php
// system/includes/signers_helper.php
// Helper กลางสำหรับจัดการรายชื่อและหน้าที่ผู้ลงนามรายงานสรุปลูกหนี้สิทธิ
// จัดเก็บในไฟล์ JSON (Zero-Migration) พร้อม Fallback จากตาราง summary_responsibles เดิม

if (!function_exists('get_signers_config_file_path')) {
    function get_signers_config_file_path() {
        return __DIR__ . '/../database_config/report_signers.json';
    }
}

if (!function_exists('get_signers_config')) {
    /**
     * ดึงข้อมูลการตั้งค่าผู้ลงนามรายงานทั้งหมด
     * @param mysqli|null $conn
     * @return array
     */
    function get_signers_config($conn = null) {
        $json_file = get_signers_config_file_path();
        
        // 1. หากมีไฟล์ JSON ให้โหลดจากไฟล์ JSON เป็นหลัก
        if (file_exists($json_file)) {
            $content = @file_get_contents($json_file);
            if (!empty($content)) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return normalize_signers_config($decoded);
                }
            }
        }

        // 2. หากยังไม่มีไฟล์ JSON หรืออ่านไม่สำเร็จ ให้ fallback ไปดึงจากฐานข้อมูลเดิม summary_responsibles
        $data_resp = [];
        if ($conn) {
            $sql = "SELECT * FROM summary_responsibles ORDER BY id DESC LIMIT 1";
            $res = @$conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $data_resp = $res->fetch_assoc();
            }
        }

        // สร้าง Officers Roster จากตารางเดิม 5 ท่าน
        $officers_list = [];
        for ($k = 1; $k <= 5; $k++) {
            $n = trim($data_resp["name{$k}"] ?? '');
            $p = trim($data_resp["position{$k}"] ?? '');
            if (!empty($n) || !empty($p)) {
                $officers_list[] = ['name' => $n, 'pos' => $p];
            }
        }

        if (empty($officers_list)) {
            $officers_list = [
                ['name' => 'นายจิรันธนิน ประสารกุลนันท์', 'pos' => 'นักวิชาการคอมพิวเตอร์'],
                ['name' => 'นายวริทธิกันต์ บัวลาด', 'pos' => 'นักสาธารณสุขชำนาญการ'],
                ['name' => 'นางสาวปิยะพร กรมไทยสงค์', 'pos' => 'นักการเงินและบัญชี'],
                ['name' => 'นางสาวพิทยาภรณ์ วิรัตน์', 'pos' => 'นักการเงินและบัญชี'],
                ['name' => 'นายเกรียงไกร ศรีวิลัย', 'pos' => 'ผู้อำนวยการโรงพยาบาล']
            ];
        }

        $default_config = [
            'officers_list' => $officers_list,
            'setup' => [
                'name1' => $data_resp['name1'] ?? ($officers_list[0]['name'] ?? ''),
                'pos1'  => $data_resp['position1'] ?? ($officers_list[0]['pos'] ?? ''),
                'role1' => "ผู้จัดทำรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ",
                'name2' => $data_resp['name2'] ?? ($officers_list[1]['name'] ?? ''),
                'pos2'  => $data_resp['position2'] ?? ($officers_list[1]['pos'] ?? ''),
                'role2' => "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ",
                'name3' => $data_resp['name2'] ?? ($officers_list[1]['name'] ?? ''),
                'pos3'  => $data_resp['position2'] ?? ($officers_list[1]['pos'] ?? ''),
                'role3' => "หัวหน้ากลุ่มงานประกันสุขภาพ",
            ],
            'cut' => [
                'name1' => $data_resp['name1'] ?? ($officers_list[0]['name'] ?? ''),
                'pos1'  => $data_resp['position1'] ?? ($officers_list[0]['pos'] ?? ''),
                'role1' => "ผู้จัดทำรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ",
                'name2' => $data_resp['name2'] ?? ($officers_list[1]['name'] ?? ''),
                'pos2'  => $data_resp['position2'] ?? ($officers_list[1]['pos'] ?? ''),
                'role2' => "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ",
                'name3' => $data_resp['name3'] ?? ($officers_list[2]['name'] ?? ''),
                'pos3'  => $data_resp['position3'] ?? ($officers_list[2]['pos'] ?? ''),
                'role3' => "ผู้บันทึกลูกหนี้สิทธิ\nกลุ่มงานบัญชี",
            ],
            'item' => [
                'name1' => $data_resp['name1'] ?? ($officers_list[0]['name'] ?? ''),
                'pos1'  => $data_resp['position1'] ?? ($officers_list[0]['pos'] ?? ''),
                'role1' => "ผู้จัดทำรายงานลูกหนี้สิทธิ",
                'name2' => $data_resp['name2'] ?? ($officers_list[1]['name'] ?? ''),
                'pos2'  => $data_resp['position2'] ?? ($officers_list[1]['pos'] ?? ''),
                'role2' => "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ",
                'name3' => $data_resp['name3'] ?? ($officers_list[2]['name'] ?? ''),
                'pos3'  => $data_resp['position3'] ?? ($officers_list[2]['pos'] ?? ''),
                'role3' => "ผู้บันทึกลูกหนี้สิทธิ",
            ],
            'overview' => [
                'name1' => $data_resp['name1'] ?? ($officers_list[0]['name'] ?? ''),
                'pos1'  => $data_resp['position1'] ?? ($officers_list[0]['pos'] ?? ''),
                'name2' => $data_resp['name2'] ?? ($officers_list[1]['name'] ?? ''),
                'pos2'  => $data_resp['position2'] ?? ($officers_list[1]['pos'] ?? ''),
                'name3' => $data_resp['name3'] ?? ($officers_list[2]['name'] ?? ''),
                'pos3'  => $data_resp['position3'] ?? ($officers_list[2]['pos'] ?? ''),
                'name4' => $data_resp['name4'] ?? ($officers_list[3]['name'] ?? ''),
                'pos4'  => $data_resp['position4'] ?? ($officers_list[3]['pos'] ?? ''),
                'name5' => $data_resp['name5'] ?? ($officers_list[4]['name'] ?? ''),
                'pos5'  => $data_resp['position5'] ?? ($officers_list[4]['pos'] ?? 'ผู้อำนวยการโรงพยาบาล'),
            ]
        ];

        return $default_config;
    }
}

if (!function_exists('normalize_signers_config')) {
    /**
     * เติมโครงสร้างและค่าเริ่มต้นให้สมบูรณ์
     */
    function normalize_signers_config($config) {
        if (!isset($config['officers_list']) || !is_array($config['officers_list'])) {
            $config['officers_list'] = [];
        }

        $sections = ['setup', 'cut', 'item'];
        foreach ($sections as $sec) {
            if (!isset($config[$sec]) || !is_array($config[$sec])) {
                $config[$sec] = [];
            }
            for ($i = 1; $i <= 3; $i++) {
                if (!isset($config[$sec]["name{$i}"])) $config[$sec]["name{$i}"] = '';
                if (!isset($config[$sec]["pos{$i}"]))  $config[$sec]["pos{$i}"]  = '';
                if (!isset($config[$sec]["role{$i}"])) $config[$sec]["role{$i}"] = '';
            }
        }

        if (!isset($config['overview']) || !is_array($config['overview'])) {
            $config['overview'] = [];
        }
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($config['overview']["name{$i}"])) $config['overview']["name{$i}"] = '';
            if (!isset($config['overview']["pos{$i}"]))  $config['overview']["pos{$i}"]  = '';
        }

        return $config;
    }
}

if (!function_exists('save_signers_config')) {
    /**
     * บันทึกการตั้งค่าลงไฟล์ JSON และอัปเดต 10 คอลัมน์เดิมใน summary_responsibles
     * @param array $data
     * @param mysqli|null $conn
     * @return bool
     */
    function save_signers_config($data, $conn = null) {
        $json_file = get_signers_config_file_path();
        $dir = dirname($json_file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $config_to_save = normalize_signers_config($data);
        $json_str = json_encode($config_to_save, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $write_ok = @file_put_contents($json_file, $json_str);

        // อัปเดตเฉพาะ 10 คอลัมน์เดิมในตาราง summary_responsibles (หากมีการเชื่อมต่อ DB)
        if ($conn) {
            $s1 = trim($config_to_save['overview']['name1'] ?? '');
            $p1 = trim($config_to_save['overview']['pos1'] ?? '');
            $s2 = trim($config_to_save['overview']['name2'] ?? '');
            $p2 = trim($config_to_save['overview']['pos2'] ?? '');
            $s3 = trim($config_to_save['overview']['name3'] ?? '');
            $p3 = trim($config_to_save['overview']['pos3'] ?? '');
            $s4 = trim($config_to_save['overview']['name4'] ?? '');
            $p4 = trim($config_to_save['overview']['pos4'] ?? '');
            $s5 = trim($config_to_save['overview']['name5'] ?? '');
            $p5 = trim($config_to_save['overview']['pos5'] ?? '');

            $chk = @$conn->query("SELECT id FROM summary_responsibles ORDER BY id DESC LIMIT 1");
            if ($chk && $chk->num_rows > 0) {
                $r = $chk->fetch_assoc();
                $id = $r['id'];
                $stmt = $conn->prepare("UPDATE summary_responsibles SET position1=?, name1=?, position2=?, name2=?, position3=?, name3=?, position4=?, name4=?, position5=?, name5=? WHERE id=?");
                if ($stmt) {
                    $stmt->bind_param("ssssssssssi", $p1, $s1, $p2, $s2, $p3, $s3, $p4, $s4, $p5, $s5, $id);
                    @$stmt->execute();
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO summary_responsibles (position1, name1, position2, name2, position3, name3, position4, name4, position5, name5) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssssssssss", $p1, $s1, $p2, $s2, $p3, $s3, $p4, $s4, $p5, $s5);
                    @$stmt->execute();
                    $stmt->close();
                }
            }
        }

        return ($write_ok !== false);
    }
}
