<?php
$file = 'c:\xampp8\htdocs\eDHS\system\datatimestamp-insert2.php';
$lines = file($file);

$output = [];
foreach ($lines as $i => $line) {
    $output[] = rtrim($line);
}

// ---------------------------------------------------------
// แก้ไข OPD CASE 3
// ---------------------------------------------------------
$opd_insert_idx = -1;
for ($i = 0; $i < count($output); $i++) {
    if (strpos($output[$i], 'CASE 3: ไม่เจอข้อมูลชดเชยใดๆ') !== false) {
        for ($j = $i; $j < $i + 20; $j++) {
            if (strpos($output[$j], 'echo \' <td style="text-align: center;">\'.$i.\'</td>') !== false) {
                $opd_insert_idx = $j;
                break;
            }
        }
        break;
    }
}

if ($opd_insert_idx !== -1) {
    $opd_code = <<<'EOD'
               // [แก้ไข] ถ้ามีการแบ่งจ่ายและจ่ายครบแล้ว (follow_money >= debit) ให้ดึงเลขบิลงวดล่าสุดมาแสดง
               $vn_c3       = $row["vn"];
               $c3_bill     = $row["bill"];     // ค่าเริ่มต้น (อาจว่าง)
               $c3_billdate = $row["billdate"]; // ค่าเริ่มต้น (อาจว่าง)
               $c3_bg       = 'background: #fff5e7;';
               $c3_balance  = $row["debit"] - $row["follow_money"];

               if ($row["follow_money"] > 0 && $c3_balance <= 0.01) {
                   $sql_c3 = "SELECT bill_no, bill_date FROM imr_tb_payment_history 
                              WHERE ref_vn_an = '$vn_c3' AND bill_no != '' 
                              ORDER BY id DESC LIMIT 1";
                   $res_c3 = mysqli_query($conn, $sql_c3);
                   if ($res_c3 !== false && ($row_c3 = mysqli_fetch_assoc($res_c3))) {
                       $c3_bill = $row_c3['bill_no'];
                       if (!empty($row_c3['bill_date'])) {
                           $d_c3 = date('d', strtotime($row_c3['bill_date']));
                           $m_c3 = date('m', strtotime($row_c3['bill_date']));
                           $y_c3 = date('Y', strtotime($row_c3['bill_date'])) + 543;
                           $c3_billdate = "$d_c3/$m_c3/$y_c3";
                       }
                       $c3_bg = 'background: #e6ffe6;'; // เขียวอ่อน = จ่ายครบแล้ว
                   }
               }
EOD;
    array_splice($output, $opd_insert_idx, 0, explode("\n", $opd_code));
    
    for ($i = $opd_insert_idx; $i < $opd_insert_idx + 20; $i++) {
        if (strpos($output[$i], '<td class="mask-bill"') !== false && strpos($output[$i], '$row["bill"]') !== false) {
            $output[$i] = '                       <td class="mask-bill" style="text-align: center;'.$c3_bg.'" contenteditable data-data2="'.$row["vn"].'" >'.$c3_bill.'</td>';
            $output[$i+1] = '                       <td class="mask-date" style="text-align: center;'.$c3_bg.'" contenteditable data-data3="'.$row["vn"].'" >'.$c3_billdate.'</td>';
            break;
        }
    }
    echo "OPD patched.\n";
}

// ---------------------------------------------------------
// แก้ไข IPD CASE 3
// ---------------------------------------------------------
$ipd_insert_idx = -1;
for ($i = $opd_insert_idx + 30; $i < count($output); $i++) {
    if (strpos($output[$i], 'CASE 3: ยังไม่จ่าย (Pending)') !== false || strpos($output[$i], 'CASE 3:') !== false) {
        for ($j = $i; $j < $i + 20; $j++) {
            if (strpos($output[$j], 'echo \' <td style="text-align: center;">\'.$i.\'</td>') !== false) {
                $ipd_insert_idx = $j;
                break;
            }
        }
        break;
    }
}

if ($ipd_insert_idx !== -1) {
    $ipd_code = <<<'EOD'
                 // [แก้ไข] IPD: ถ้ามีการแบ่งจ่ายและจ่ายครบแล้ว (follow_money >= debit) ให้ดึงเลขบิลงวดล่าสุดมาแสดง
                 $an_c3       = $row["an"];
                 $c3_bill     = $row["bill"];     // ค่าเริ่มต้น (อาจว่าง)
                 $c3_billdate = $row["billdate"]; // ค่าเริ่มต้น (อาจว่าง)
                 $c3_bg       = 'background: #fff5e7;';
                 $c3_balance  = $row["debit"] - $row["follow_money"];

                 if ($row["follow_money"] > 0 && $c3_balance <= 0.01) {
                     $sql_c3 = "SELECT bill_no, bill_date FROM imr_tb_payment_history 
                                WHERE ref_vn_an = '$an_c3' AND bill_no != '' 
                                ORDER BY id DESC LIMIT 1";
                     $res_c3 = mysqli_query($conn, $sql_c3);
                     if ($res_c3 !== false && ($row_c3 = mysqli_fetch_assoc($res_c3))) {
                         $c3_bill = $row_c3['bill_no'];
                         if (!empty($row_c3['bill_date'])) {
                             $d_c3 = date('d', strtotime($row_c3['bill_date']));
                             $m_c3 = date('m', strtotime($row_c3['bill_date']));
                             $y_c3 = date('Y', strtotime($row_c3['bill_date'])) + 543;
                             $c3_billdate = "$d_c3/$m_c3/$y_c3";
                         }
                         $c3_bg = 'background: #e6ffe6;'; // เขียวอ่อน = จ่ายครบแล้ว
                     }
                 }
EOD;
    array_splice($output, $ipd_insert_idx, 0, explode("\n", $ipd_code));
    
    for ($i = $ipd_insert_idx; $i < $ipd_insert_idx + 20; $i++) {
        if (strpos($output[$i], '<td class="mask-bill"') !== false && strpos($output[$i], '$row["bill"]') !== false) {
            $output[$i] = '                            <td class="mask-bill" style="text-align: center;'.$c3_bg.'" contenteditable data-data2i="'.$row["an"].'" >'.$c3_bill.'</td>';
            $output[$i+1] = '                            <td class="mask-date" style="text-align: center;'.$c3_bg.'" contenteditable data-data3i="'.$row["an"].'" >'.$c3_billdate.'</td>';
            break;
        }
    }
    echo "IPD patched.\n";
}

file_put_contents($file, implode("\n", $output));
echo "Done.";
