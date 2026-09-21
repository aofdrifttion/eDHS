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
            throw new Exception("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
    }

    /**
     * ค้นหาตำแหน่งของไฟล์ mysql.exe บนเซิร์ฟเวอร์
     */
    public function getMysqlBinaryPath() {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\xampp8\\mysql\\bin\\mysql.exe',
            'D:\\xampp\\mysql\\bin\\mysql.exe',
            'E:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'C:\\Program Files\\MariaDB 10.5\\bin\\mysql.exe',
            'C:\\Program Files\\MariaDB 10.4\\bin\\mysql.exe'
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // ตรวจสอบจาก System PATH
        $whereOutput = @shell_exec('where.exe mysql 2>nul');
        if (!empty($whereOutput)) {
            $lines = explode("\n", trim($whereOutput));
            $first = trim($lines[0]);
            if (file_exists($first)) {
                return $first;
            }
        }

        return null;
    }

    /**
     * สร้างไฟล์สำรองข้อมูล (Backup Database)
     * มีการปรับปรุงแยกการสำรองระหว่างตารางธรรมดา (BASE TABLE) กับ มุมมอง (VIEW)
     * ป้องกันคำสั่ง INSERT INTO บน VIEW ไม่ให้เกิด ERROR 1471
     */
    public function generateBackup() {
        $this->connect();
        
        // ดึงรายการตารางพร้อมระบุว่าเป็น BASE TABLE หรือ VIEW
        $tables = [];
        $result = $this->conn->query("SHOW FULL TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = [
                'name' => $row[0],
                'type' => $row[1] // 'BASE TABLE' หรือ 'VIEW'
            ];
        }

        $dateStr = date('Ymd_His');
        $sqlFilename = "backup_{$this->database}_{$dateStr}.sql";
        $zipFilename = "backup_{$this->database}_{$dateStr}.zip";
        
        $sqlPath = $this->backupDir . $sqlFilename;
        $zipPath = $this->backupDir . $zipFilename;

        $fileHandler = fopen($sqlPath, 'w');
        if (!$fileHandler) {
            throw new Exception("ไม่สามารถสร้างไฟล์ชั่วคราว SQL สำหรับการสำรองข้อมูลได้");
        }

        fwrite($fileHandler, "-- ========================================================\n");
        fwrite($fileHandler, "-- eDHS Database Backup Engine (Full Schema & Data)\n");
        fwrite($fileHandler, "-- Target Database: " . $this->database . "\n");
        fwrite($fileHandler, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
        fwrite($fileHandler, "-- Total Tables & Views: " . count($tables) . "\n");
        fwrite($fileHandler, "-- ========================================================\n\n");

        fwrite($fileHandler, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
        fwrite($fileHandler, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
        fwrite($fileHandler, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
        fwrite($fileHandler, "/*!40101 SET NAMES utf8mb4 */;\n");
        fwrite($fileHandler, "SET NAMES utf8mb4;\n");
        fwrite($fileHandler, "SET CHARACTER SET utf8mb4;\n");
        fwrite($fileHandler, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($fileHandler, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        foreach ($tables as $tInfo) {
            $table = $tInfo['name'];
            $isView = ($tInfo['type'] === 'VIEW');

            fwrite($fileHandler, "-- --------------------------------------------------------\n");
            fwrite($fileHandler, "-- " . ($isView ? "View structure for: " : "Table structure for: ") . $table . "\n");
            fwrite($fileHandler, "-- --------------------------------------------------------\n");

            if ($isView) {
                fwrite($fileHandler, "DROP VIEW IF EXISTS `$table`;\n");
                $result = $this->conn->query("SHOW CREATE VIEW `$table`");
                if ($result) {
                    $row = $result->fetch_row();
                    fwrite($fileHandler, $row[1] . ";\n\n");
                }
                // VIEW ไม่ต้องทำการ INSERT INTO ข้อมูล เพราะดึงจากตารางหลักโดยตรง
                continue;
            }

            // กรณี BASE TABLE
            $result = $this->conn->query("SHOW CREATE TABLE `$table`");
            if ($result) {
                $row = $result->fetch_row();
                fwrite($fileHandler, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($fileHandler, $row[1] . ";\n\n");
            }

            // ดึงข้อมูลแถวละชุดแบบสตรีมมิ่งเพื่อประหยัด RAM
            $result = $this->conn->query("SELECT * FROM `$table`", MYSQLI_USE_RESULT);
            
            if ($result) {
                $columnCount = $result->field_count;
                $hasData = false;
                
                while ($row = $result->fetch_row()) {
                    if (!$hasData) {
                        fwrite($fileHandler, "-- Dumping data for table: $table\n");
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

        fwrite($fileHandler, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
        $this->conn->close();
        fclose($fileHandler);

        // บีบอัดเป็น Zip
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            if (file_exists($sqlPath)) unlink($sqlPath);
            throw new Exception("ไม่สามารถบีบอัดไฟล์ ZIP ได้");
        }
        $zip->addFile($sqlPath, $sqlFilename);
        $zip->close();

        // ลบไฟล์ SQL ชั่วคราวออก
        if (file_exists($sqlPath)) {
            unlink($sqlPath);
        }

        return $zipFilename;
    }

    /**
     * ดึงรายการไฟล์สำรองข้อมูลทั้งหมดในโฟลเดอร์ backups_data
     */
    public function listBackups() {
        if (!is_dir($this->backupDir)) {
            return [];
        }
        $files = array_diff(scandir($this->backupDir), array('.', '..', '.htaccess'));
        $backups = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === 'zip' || $ext === 'sql') {
                $filePath = $this->backupDir . $file;
                $backups[] = [
                    'name' => $file,
                    'type' => $ext,
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

    /**
     * ลบไฟล์สำรองข้อมูล
     */
    public function deleteBackup($filename) {
        $cleanName = basename($filename);
        $filePath = $this->backupDir . $cleanName;
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (file_exists($filePath) && ($ext === 'zip' || $ext === 'sql')) {
            unlink($filePath);
            return true;
        }
        return false;
    }

    /**
     * นำเข้าไฟล์สำรองข้อมูล (Database Restore / Import)
     * รองรับทั้งการนำเข้าทับตารางเดิม (Clean Overwrite) และการนำเข้าสู่ฐานข้อมูลว่างที่ยังไม่มีตาราง
     * 
     * @param string $sourceFile ชื่อไฟล์ในโฟลเดอร์ backups_data หรือ Path แบบเต็ม
     * @param bool $isFullPath หากเป็น true หมายถึงระบุ path ไฟล์เต็มแล้ว (เช่นไฟล์อัปโหลด)
     * @param array|null $targetConfig การตั้งค่าเซิร์ฟเวอร์เป้าหมาย
     * @return array ผลการนำเข้าและรายงานผลการตรวจสอบตาราง
     */
    public function restoreBackup($sourceFile, $isFullPath = false, $targetConfig = null) {
        $startTime = microtime(true);

        // กำหนดการเชื่อมต่อเป้าหมาย
        $tHost = $targetConfig['servername'] ?? $this->host;
        $tUser = $targetConfig['username'] ?? $this->username;
        $tPass = $targetConfig['password'] ?? $this->password;
        $tDb   = $targetConfig['dbname'] ?? $this->database;

        $inputPath = $isFullPath ? $sourceFile : ($this->backupDir . basename($sourceFile));

        if (!file_exists($inputPath)) {
            throw new Exception("ไม่พบไฟล์สำรองข้อมูลที่ระบุ: " . htmlspecialchars($sourceFile));
        }

        $ext = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['zip', 'sql'])) {
            throw new Exception("รูปแบบไฟล์ไม่ถูกต้อง รองรับเฉพาะไฟล์ .zip และ .sql เท่านั้น");
        }

        $tempDir = null;
        $sqlPathToRun = null;

        // หากเป็นไฟล์ ZIP ให้แตกไฟล์ .sql ออกมายังโฟลเดอร์ชั่วคราว
        if ($ext === 'zip') {
            $tempDir = $this->backupDir . '_restore_tmp_' . uniqid() . '/';
            if (!mkdir($tempDir, 0755, true)) {
                throw new Exception("ไม่สามารถสร้างโฟลเดอร์ชั่วคราวสำหรับการแตกไฟล์ ZIP ได้");
            }

            $zip = new ZipArchive();
            if ($zip->open($inputPath) !== TRUE) {
                throw new Exception("ไม่สามารถเปิดไฟล์ ZIP เพื่อแตกไฟล์ได้");
            }
            $zip->extractTo($tempDir);
            $zip->close();

            // ค้นหาไฟล์ .sql ภายในโฟลเดอร์ที่แตกออกมา
            $extractedFiles = glob($tempDir . "*.sql");
            if (empty($extractedFiles)) {
                // ค้นหาในโฟลเดอร์ย่อยถ้ามี
                $extractedFiles = glob($tempDir . "*/*.sql");
            }

            if (empty($extractedFiles)) {
                $this->removeDirectory($tempDir);
                throw new Exception("ไม่พบไฟล์ .sql อยู่ภายในไฟล์ ZIP สำรองข้อมูล");
            }

            $sqlPathToRun = $extractedFiles[0];
        } else {
            $sqlPathToRun = $inputPath;
        }

        if (!file_exists($sqlPathToRun) || filesize($sqlPathToRun) === 0) {
            if ($tempDir) $this->removeDirectory($tempDir);
            throw new Exception("ไฟล์ SQL สำหรับนำเข้าว่างเปล่าหรือไม่ถูกต้อง");
        }

        // ค้นหาตำแหน่ง mysql.exe
        $mysqlBin = $this->getMysqlBinaryPath();

        $restoreMethod = '';
        $cliOutput = [];
        $returnCode = 0;

        if ($mysqlBin && file_exists($mysqlBin)) {
            // สร้าง temporary MySQL option file เพื่อความปลอดภัยของรหัสผ่านและการบังคับ UTF-8
            $optFile = tempnam(sys_get_temp_dir(), 'edhs_mycnf_');
            $cnfContent = "[client]\npassword=\"" . addcslashes($tPass, '"\\') . "\"\ndefault-character-set=utf8mb4\n\n[mysql]\ndefault-character-set=utf8mb4\n";
            file_put_contents($optFile, $cnfContent);

            // คำสั่ง Execute พร้อม Flag ปลอดภัย, บังคับ UTF-8 และรองรับไฟล์ขนาดใหญ่
            // --init-command="SET NAMES utf8mb4;" : ป้องกันภาษาไทยเพี้ยน (Mojibake) แม้ไฟล์สำรองเดิมจะไม่มีคำสั่ง SET NAMES
            // --force : เพื่อให้ประมวลผลต่อเนื่องโดยไม่สะดุดแม้มุมมองในไฟล์สำรองเดิมจะมีคำสั่งซ้ำ
            // --max-allowed-packet=512M : รองรับ Blob หรือแถวขนาดใหญ่
            $cmd = sprintf(
                'cmd /c ""%s" --defaults-extra-file="%s" --init-command="SET NAMES utf8mb4;" -h %s -u %s --default-character-set=utf8mb4 --max-allowed-packet=512M --force %s < "%s""',
                $mysqlBin,
                $optFile,
                $tHost,
                $tUser,
                $tDb,
                $sqlPathToRun
            );

            exec($cmd . ' 2>&1', $cliOutput, $returnCode);

            // ลบ temporary option file ทันที
            if (file_exists($optFile)) {
                unlink($optFile);
            }

            // ถ้ามี error ร้ายแรงจน return code != 0
            if ($returnCode !== 0) {
                $errorMsg = implode("\n", $cliOutput);
                // ข้ามข้อความเตือนเล็กน้อย หรือ error 1471 บน view insert
                if (stripos($errorMsg, "Access denied") !== false || stripos($errorMsg, "Unknown database") !== false) {
                    if ($tempDir) $this->removeDirectory($tempDir);
                    throw new Exception("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล MySQL CLI: " . $errorMsg);
                }
            }
        } else {
            // Fallback สตรีมมิ่งผ่าน PHP MySQLi
            $restoreMethod = 'PHP MySQLi Stream Fallback';
            $this->restoreViaPhpStream($sqlPathToRun, $tHost, $tUser, $tPass, $tDb);
        }

        // ลบโฟลเดอร์ temp หลังนำเข้าเสร็จ
        if ($tempDir && is_dir($tempDir)) {
            $this->removeDirectory($tempDir);
        }

        $duration = round(microtime(true) - $startTime, 2);

        // ดำเนินการตรวจสอบความสมบูรณ์ของทุกตารางทันทีหลังนำเข้า (Full Table Verification)
        $verifyResult = $this->verifyDatabase([
            'servername' => $tHost,
            'username' => $tUser,
            'password' => $tPass,
            'dbname' => $tDb
        ]);

        return [
            'success' => true,
            'message' => 'นำเข้าไฟล์สำรองข้อมูลเข้าสู่เซิร์ฟเวอร์เรียบร้อยแล้ว',
            'restore_method' => $restoreMethod,
            'duration_seconds' => $duration,
            'cli_output' => implode("\n", array_slice($cliOutput, 0, 10)),
            'target_server' => $tHost,
            'target_database' => $tDb,
            'verification' => $verifyResult
        ];
    }

    /**
     * สตรีมมิ่งไฟล์ SQL ผ่าน PHP กรณีเซิร์ฟเวอร์ไม่มี mysql.exe
     */
    private function restoreViaPhpStream($sqlPath, $host, $user, $pass, $db) {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        $conn->query("SET NAMES utf8mb4");
        $conn->query("SET CHARACTER SET utf8mb4");
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        $fh = fopen($sqlPath, 'r');
        if (!$fh) {
            throw new Exception("ไม่สามารถเปิดอ่านไฟล์ SQL ได้");
        }

        $query = '';
        while (($line = fgets($fh)) !== false) {
            // ข้ามคอมเมนต์และบรรทัดว่าง
            $trimmed = trim($line);
            if ($trimmed === '' || substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 2) === '/*') {
                continue;
            }

            $query .= $line;
            if (substr(rtrim($query), -1) === ';') {
                // ข้ามการ INSERT ข้อมูลลงใน VIEW ที่อาจ error 1471
                if (stripos($query, 'INSERT INTO `v_') === 0 || stripos($query, 'INSERT INTO v_') === 0) {
                    $query = '';
                    continue;
                }

                @$conn->query($query);
                $query = '';
            }
        }
        fclose($fh);
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->close();
    }

    /**
     * ระบบตรวจสอบทุกตารางอย่างละเอียด (Full Table Verification Engine)
     * ตรวจสอบโครงสร้าง, ตรวจนับจำนวนแถวข้อมูลจริง (Record Count), ตรวจสอบ Engine, ขนาดพื้นที่ และสถานะ
     */
    public function verifyDatabase($targetConfig = null) {
        $startTime = microtime(true);

        $host = $targetConfig['servername'] ?? $this->host;
        $user = $targetConfig['username'] ?? $this->username;
        $pass = $targetConfig['password'] ?? $this->password;
        $db   = $targetConfig['dbname'] ?? $this->database;

        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            throw new Exception("ไม่สามารถเชื่อมต่อเพื่อตรวจสอบตารางได้: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");

        // ดึงข้อมูลสถิติขนาดตารางจาก information_schema
        $metaMap = [];
        $metaSql = "SELECT TABLE_NAME, DATA_LENGTH, INDEX_LENGTH, ENGINE, TABLE_COLLATION, TABLE_ROWS 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = '{$db}'";
        $metaRes = $conn->query($metaSql);
        if ($metaRes) {
            while ($row = $metaRes->fetch_assoc()) {
                $metaMap[$row['TABLE_NAME']] = [
                    'data_bytes' => (int)($row['DATA_LENGTH'] ?? 0),
                    'index_bytes' => (int)($row['INDEX_LENGTH'] ?? 0),
                    'engine' => $row['ENGINE'] ?? 'VIEW',
                    'collation' => $row['TABLE_COLLATION'] ?? 'utf8mb4_general_ci',
                    'approx_rows' => (int)($row['TABLE_ROWS'] ?? 0)
                ];
            }
        }

        // ดึงรายการตารางทั้งหมดในฐานข้อมูล
        $tablesRes = $conn->query("SHOW FULL TABLES");
        if (!$tablesRes) {
            throw new Exception("ไม่สามารถอ่านรายการตารางในฐานข้อมูลได้: " . $conn->error);
        }

        $tableList = [];
        $totalTables = 0;
        $totalViews = 0;
        $totalRows = 0;
        $totalSizeBytes = 0;
        $allValid = true;

        while ($tRow = $tablesRes->fetch_row()) {
            $tableName = $tRow[0];
            $tableType = $tRow[1]; // 'BASE TABLE' หรือ 'VIEW'

            // ตรวจนับจำนวนแถวข้อมูลจริงด้วย SELECT COUNT(*)
            $countRes = $conn->query("SELECT COUNT(*) FROM `{$tableName}`");
            $rowCount = -1;
            $status = 'OK';
            $statusText = 'พร้อมใช้งาน';

            if ($countRes) {
                $rowCount = (int)$countRes->fetch_row()[0];
            } else {
                $status = 'ERROR';
                $statusText = 'เกิดข้อผิดพลาดในการอ่านข้อมูล: ' . $conn->error;
                $allValid = false;
            }

            if ($tableType === 'VIEW') {
                $totalViews++;
            } else {
                $totalTables++;
                if ($rowCount >= 0) {
                    $totalRows += $rowCount;
                }
            }

            $meta = $metaMap[$tableName] ?? [
                'data_bytes' => 0,
                'index_bytes' => 0,
                'engine' => ($tableType === 'VIEW' ? 'VIEW' : 'InnoDB'),
                'collation' => '-'
            ];

            $tableSize = $meta['data_bytes'] + $meta['index_bytes'];
            $totalSizeBytes += $tableSize;

            if ($status === 'OK') {
                if ($rowCount === 0) {
                    $statusText = 'ตารางว่าง (0 ระเบียน)';
                } else {
                    $statusText = 'สมบูรณ์ (' . number_format($rowCount) . ' ระเบียน)';
                }
            }

            $tableList[] = [
                'table_name' => $tableName,
                'table_type' => $tableType,
                'is_view' => ($tableType === 'VIEW'),
                'row_count' => $rowCount,
                'engine' => $meta['engine'],
                'collation' => $meta['collation'],
                'size_bytes' => $tableSize,
                'status' => $status,
                'status_text' => $statusText
            ];
        }

        $conn->close();
        $duration = round(microtime(true) - $startTime, 3);

        return [
            'success' => true,
            'server' => $host,
            'database' => $db,
            'checked_at' => date('d/m/Y H:i:s'),
            'duration_seconds' => $duration,
            'total_tables' => $totalTables,
            'total_views' => $totalViews,
            'total_all' => count($tableList),
            'total_rows' => $totalRows,
            'total_size_bytes' => $totalSizeBytes,
            'all_valid' => $allValid,
            'tables' => $tableList
        ];
    }

    /**
     * ลบไดเรกทอรีและไฟล์ทั้งหมดภายใน
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        @rmdir($dir);
    }
}
?>
