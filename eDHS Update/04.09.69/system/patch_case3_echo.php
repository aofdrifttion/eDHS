<?php
$file = 'c:\xampp8\htdocs\eDHS\system\datatimestamp-insert2.php';
$lines = file($file);

$output = [];
foreach ($lines as $i => $line) {
    $output[] = rtrim($line);
}

// ---------------------------------------------------------
// แก้ไข OPD CASE 3 Echo
// ---------------------------------------------------------
$patched_opd = false;
for ($i = 0; $i < count($output); $i++) {
    if (strpos($output[$i], 'CASE 3: ไม่เจอข้อมูลชดเชยใดๆ') !== false) {
        for ($j = $i; $j < $i + 60; $j++) {
            if (strpos($output[$j], '<td class="mask-bill"') !== false && strpos($output[$j], '$row["bill"]') !== false) {
                // ใช้ single quote และ escape \$ ให้ถูกต้อง เพื่อไม่ให้ PHP แปลงค่าตัวแปร
                $output[$j] = '                       <td class="mask-bill" style="text-align: center;\''.$c3_bg.'\'" contenteditable data-data2="\'.$row["vn"].\'" >\'.$c3_bill.\'</td>';
                // แก้ให้เป็น syntax PHP ที่ถูกต้องในโค้ด
                $output[$j] = '                       <td class="mask-bill" style="text-align: center;".$c3_bg."" contenteditable data-data2="'.$row["vn"].'" >".$c3_bill."</td>';
                $output[$j] = '                       <td class="mask-bill" style="text-align: center;".$c3_bg."\" contenteditable data-data2=\"".$row["vn"]."\" >".$c3_bill."</td>';
                
                // วิธีที่ปลอดภัยที่สุดคือการ string concat ใน PHP file:
                $output[$j] = '                       <td class="mask-bill" style="text-align: center;".$c3_bg."\" contenteditable data-data2=\"".$row["vn"]."\" >".$c3_bill."</td>';
                
                // Wait, it is inside an echo ' ... ';
                $output[$j] = '                       <td class="mask-bill" style="text-align: center;\''.$c3_bg.'\'" contenteditable data-data2="\'.$row["vn"].\'" >\'.$c3_bill.\'</td>';
                // No, the original is:
                // echo ' ... <td class="mask-bill" style="text-align: center;background: #fff5e7;" contenteditable data-data2="'.$row["vn"].'" >'.$row["bill"].'</td> ... ';
                // So it should be:
                $output[$j] = '                       <td class="mask-bill" style="text-align: center;\''.$c3_bg.'\'" contenteditable data-data2="\'.$row["vn"].\'" >\'.$c3_bill.\'</td>';
                
                // Let's just use Nowdoc or escape correctly:
                $output[$j] = '                       <td class="mask-bill" style="text-align: center;\'.$c3_bg.\'" contenteditable data-data2="\'.$row["vn"].\'" >\'.$c3_bill.\'</td>';
                $output[$j+1] = '                       <td class="mask-date" style="text-align: center;\'.$c3_bg.\'" contenteditable data-data3="\'.$row["vn"].\'" >\'.$c3_billdate.\'</td>';
                $patched_opd = true;
                break;
            }
        }
        break;
    }
}
if ($patched_opd) echo "OPD Echo patched.\n";

// ---------------------------------------------------------
// แก้ไข IPD CASE 3 Echo
// ---------------------------------------------------------
$patched_ipd = false;
for ($i = 0; $i < count($output); $i++) {
    if (strpos($output[$i], 'CASE 3: ยังไม่จ่าย (Pending)') !== false || (strpos($output[$i], 'CASE 3:') !== false && $i > count($output)/2)) {
        for ($j = $i; $j < $i + 60; $j++) {
            if (strpos($output[$j], '<td class="mask-bill"') !== false && strpos($output[$j], '$row["bill"]') !== false) {
                $output[$j] = '                            <td class="mask-bill" style="text-align: center;\'.$c3_bg.\'" contenteditable data-data2i="\'.$row["an"].\'" >\'.$c3_bill.\'</td>';
                $output[$j+1] = '                            <td class="mask-date" style="text-align: center;\'.$c3_bg.\'" contenteditable data-data3i="\'.$row["an"].\'" >\'.$c3_billdate.\'</td>';
                $patched_ipd = true;
                break;
            }
        }
        break;
    }
}
if ($patched_ipd) echo "IPD Echo patched.\n";

file_put_contents($file, implode("\n", $output));
echo "Done.";
