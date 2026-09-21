<?php
date_default_timezone_set('Asia/Bangkok');
class BackupEngine {
    private $host;
    private $username;
    private $password;
    private $database;
    private $conn;
    private $backupDir;

    public function __construct($host, $username, $password, $database) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->backupDir = __DIR__ . '/../backups_data/';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    private function connect() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
    }

    public function generateBackup() {
        $this->connect();
        
        $tables = [];
        $result = $this->conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }

        $dateStr = date('Ymd_His');
        $sqlFilename = "backup_{$this->database}_{$dateStr}.sql";
        $zipFilename = "backup_{$this->database}_{$dateStr}.zip";
        
        $sqlPath = $this->backupDir . $sqlFilename;
        $zipPath = $this->backupDir . $zipFilename;

        $fileHandler = fopen($sqlPath, 'w');
        if (!$fileHandler) {
            throw new Exception("Cannot create temporary SQL file.");
        }

        fwrite($fileHandler, "-- Database Backup for: " . $this->database . "\n");
        fwrite($fileHandler, "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n");

        foreach ($tables as $table) {
            fwrite($fileHandler, "-- Table structure for: $table\n");
            $result = $this->conn->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch_row();
            fwrite($fileHandler, "\nDROP TABLE IF EXISTS `$table`;\n");
            fwrite($fileHandler, $row[1] . ";\n\n");

            // ใช้ MYSQLI_USE_RESULT เพื่อลดการใช้หน่วยความจำ (ดึงข้อมูลทีละบรรทัด)
            $result = $this->conn->query("SELECT * FROM `$table`", MYSQLI_USE_RESULT);
            
            if ($result) {
                $columnCount = $result->field_count;
                $hasData = false;
                
                while ($row = $result->fetch_row()) {
                    if (!$hasData) {
                        fwrite($fileHandler, "-- Data for table: $table\n");
                        $hasData = true;
                    }
                    $line = "INSERT INTO `$table` VALUES(";
                    for ($j = 0; $j < $columnCount; $j++) {
                        if (isset($row[$j])) {
                            $escaped = $this->conn->real_escape_string($row[$j]);
                            $line .= "'" . $escaped . "'";
                        } else {
                            $line .= "NULL";
                        }
                        if ($j < ($columnCount - 1)) {
                            $line .= ",";
                        }
                    }
                    $line .= ");\n";
                    fwrite($fileHandler, $line);
                }
                
                if ($hasData) {
                    fwrite($fileHandler, "\n");
                }
                $result->free();
            }
        }
        $this->conn->close();
        fclose($fileHandler);

        // Create Zip
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            if (file_exists($sqlPath)) unlink($sqlPath);
            throw new Exception("Cannot create zip file.");
        }
        // เพิ่มไฟล์เข้าไปใน Zip โดยตรงแทนการใช้ Memory String
        $zip->addFile($sqlPath, $sqlFilename);
        $zip->close();

        // ลบไฟล์ .sql ชั่วคราวออก
        if (file_exists($sqlPath)) {
            unlink($sqlPath);
        }

        return $zipFilename;
    }
    
    public function listBackups() {
        $files = array_diff(scandir($this->backupDir), array('.', '..', '.htaccess'));
        $backups = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $filePath = $this->backupDir . $file;
                $backups[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'date' => filemtime($filePath)
                ];
            }
        }
        usort($backups, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        return $backups;
    }

    public function deleteBackup($filename) {
        $filePath = $this->backupDir . basename($filename);
        if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') {
            unlink($filePath);
            return true;
        }
        return false;
    }
}
?>
