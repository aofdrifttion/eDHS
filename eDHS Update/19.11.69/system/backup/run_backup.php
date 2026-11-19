<?php
// CLI ONLY
if (php_sapi_name() !== 'cli') {
    die("CLI execution only");
}

require_once __DIR__ . '/backup_engine.php';

$configFile = __DIR__ . "/../database_config/config.json";
if (!file_exists($configFile)) {
    die("Error: config.json not found.\n");
}
$configData = json_decode(file_get_contents($configFile), true);
$dbConfig = $configData['db1'];

echo "Starting automated backup for database: " . $dbConfig['dbname'] . "...\n";

try {
    $backupEngine = new BackupEngine($dbConfig['servername'], $dbConfig['username'], $dbConfig['password'], $dbConfig['dbname']);
    $filename = $backupEngine->generateBackup();
    
    echo "Backup completed successfully.\n";
    echo "Saved as: " . $filename . "\n";
    
    // Optional: Retention policy - Delete backups older than 7 days
    $backupDir = __DIR__ . '/../backups_data/';
    $files = glob($backupDir . "*.zip");
    $now   = time();
    $deletedCount = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * 7) { // 7 days
                unlink($file);
                $deletedCount++;
            }
        }
    }
    
    if ($deletedCount > 0) {
        echo "Deleted " . $deletedCount . " old backup(s) based on 7-day retention policy.\n";
    }

} catch (Exception $e) {
    echo "Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
