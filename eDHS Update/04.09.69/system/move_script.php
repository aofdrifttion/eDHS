<?php
$file = 'c:\\xampp8\\htdocs\\eDHS\\system\\report_debtor_receipt.php';
$content = file_get_contents($file);

$startMarker = '<!-- DataTables JS & Flatpickr JS will be moved to the bottom -->';
$startIndex = strpos($content, '<script>', strpos($content, $startMarker));
$endIndex = strpos($content, '</script>', $startIndex) + 9;

$scriptBlock = substr($content, $startIndex, $endIndex - $startIndex);
$newContent = substr_replace($content, '', $startIndex, $endIndex - $startIndex);

$insertIndex = strpos($newContent, '</body>');
$newContent = substr_replace($newContent, $scriptBlock . "\n", $insertIndex, 0);

file_put_contents($file, $newContent);
echo "Done";
