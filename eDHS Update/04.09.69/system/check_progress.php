<?php
$task_id = isset($_GET['task_id']) ? $_GET['task_id'] : '';

// 🔒 Security: ตรวจสอบว่า task_id เป็นตัวเลขและตัวอักษรเท่านั้น ป้องกัน Path Traversal
if (preg_match('/^[a-zA-Z0-9]+$/', $task_id)) {
    $file_path = 'progress_' . $task_id . '.txt';
    
    if (file_exists($file_path)) {
        echo file_get_contents($file_path);
    } else {
        echo "0";
    }
} else {
    echo "0";
}
?>