# แผนยุทธศาสตร์การใช้งาน Git Pull และแนวทางการอัปเดตระบบ eDHS
## สำหรับโรงพยาบาลเครือข่าย (Windows XAMPP & Linux Server)

> **เอกสาร:** แผนปฏิบัติการการจัดการเวอร์ชัน (Version Control) และการกระจายแพตช์อัปเดตระบบ eDebtor Hospital System (eDHS)  
> **เป้าหมาย:** ให้ รพ. แม่ข่ายและ รพ. ลูกข่าย สามารถอัปเดตโค้ดได้อย่างปลอดภัย ไม่กระทบไฟล์คอนฟิกเฉพาะแห่ง ไม่ทำให้ฐานข้อมูลเสียหาย และรองรับทั้งเครื่องที่ต่อเน็ตและเครือข่ายปิด (Intranet)

---

## 1. สถานะระบบปัจจุบัน & ปัญหาที่พบ (Current State Analysis)

1. **สถานะโฟลเดอร์ปัจจุบัน (`c:\xampp8\htdocs\eDHS`):**
   - ยังไม่มีการติดตั้ง Git Client ใน System PATH และยังไม่มีโฟลเดอร์ `.git` (ยังไม่ได้เชื่อมต่อ Remote Repository)
   - ปัจจุบันใช้วิธีแจกจ่ายไฟล์อัปเดตแบบ Manual ผ่านโฟลเดอร์ `eDHS Update/XX.XX.XX/`
2. **ปัญหาและความเสี่ยงเมื่อ รพ. อื่นต้องการอัปเดต:**
   - **ความเสี่ยงไฟล์คอนฟิกทับกัน:** ไฟล์ `system/database_config/config.php` และ `config.json` มีการระบุ IP, User/Pass ของ MySQL และ HOSxP เฉพาะของแต่ละ รพ. หากทำ `git pull` ทับตรงๆ จะทำให้ระบบ รพ. นั้นเชื่อมต่อฐานข้อมูลไม่ได้ทันที
   - **ความแตกต่างของสภาพแวดล้อม (OS):**
     - **Windows + XAMPP:** มักรันด้วยสิทธิ์ Administrator / Local User ปัญหาหลักคือ Git Conflict และ Path ตัวพิมพ์เล็ก/ใหญ่ (Case-insensitive)
     - **Linux (Ubuntu/Debian/CentOS):** มีเรื่อง File Permissions (`www-data`, `apache`, `chmod 755/644`) และ Case-sensitive ของชื่อไฟล์ภาษาไทย/อังกฤษ

---

## 2. การเตรียมการเบื้องต้น (Prerequisites & Git Setup)

### 2.1 ติดตั้ง Git Client
- **บน Windows:**
  1. ดาวน์โหลด [Git for Windows](https://git-scm.com/download/win) (ติดตั้งแบบค่าเริ่มต้น โดยติ๊กเลือก *Add to PATH*)
  2. เปิด Terminal ตรวจสอบด้วยคำสั่ง: `git --version`
- **บน Linux (Ubuntu/Debian):**
  ```bash
  sudo apt update && sudo apt install -y git
  ```
- **บน Linux (CentOS/RHEL/Rocky):**
  ```bash
  sudo dnf install -y git
  ```

---

### 2.2 การสร้างไฟล์ `.gitignore` ที่ถูกต้อง (สำคัญที่สุด)
เพื่อป้องกันไม่ให้ไฟล์คอนฟิกเฉพาะ รพ., ข้อมูลสำรอง, และ Log ถูกดึงไปทับกัน ต้องมีไฟล์ `.gitignore` วางไว้ที่ Root Directory:

```gitignore
# ==========================================
# eDHS - Git Ignore Rules
# ==========================================

# 1. ไฟล์คอนฟิกเฉพาะโรงพยาบาล (ห้ามทับเด็ดขาด)
system/database_config/config.php
system/database_config/config.json
system/database_config/db_credentials.local.php

# 2. ไฟล์สำรองฐานข้อมูลและ SQL Dumps
system/backup/*.sql
system/backup/*.gz
system/backup/*.zip
backup_*.sql

# 3. ไฟล์ Logs และ Error Dumps
system/logs/*.log
system/logs/*.txt
*.log

# 4. ไฟล์ชั่วคราวและไฟล์อัปโหลด
system/temp_uploads/*
!system/temp_uploads/.gitkeep
system/progress_*.txt
system/scratch/*

# 5. ไฟล์การตั้งค่า IDE และระบบปฏิบัติการ
.vscode/
.idea/
.DS_Store
Thumbs.db
```

> **ข้อแนะนำ:** ควรสร้างไฟล์ตัวอย่างคอนฟิก เช่น `system/database_config/config.example.php` เพื่อให้ รพ. ใหม่นำไปตั้งค่าเริ่มต้นได้โดยไม่ต้องแก้ไขไฟล์หลัก

---

## 3. ขั้นตอนการ Pull Git สำหรับเครื่องหลัก / รพ. ปลายทาง

### 3.1 การเชื่อมต่อครั้งแรก (First-time Git Link)
หากเครื่องปลายทางมีโฟลเดอร์ `eDHS` อยู่แล้ว ให้ทำตามขั้นตอนนี้:

```bash
# 1. เข้าไปยังโฟลเดอร์ระบบ
cd c:/xampp8/htdocs/eDHS   # (หรือ /var/www/html/eDHS บน Linux)

# 2. เริ่มต้น Git และผูก Remote Repository
git init
git remote add origin https://github.com/your-org/eDHS.git

# 3. สำรองไฟล์คอนฟิกเดิมไว้ก่อนเสมอ
cp system/database_config/config.php system/database_config/config.php.bak
cp system/database_config/config.json system/database_config/config.json.bak

# 4. ดึงข้อมูลโค้ดล่าสุด
git fetch origin main
git checkout -f main
```

---

### 3.2 คำสั่งอัปเดตตามรอบปกติ (Routine Safe Git Pull)
เมื่อมีการปรับปรุงโค้ดใหม่ ให้ใช้คำสั่งที่ปลอดภัยดังนี้:

```bash
# 1. เก็บการเปลี่ยนแปลงเฉพาะเครื่องไว้ชั่วคราว (Stash)
git stash

# 2. ดึงโค้ดล่าสุดจาก Branch หลัก
git pull origin main

# 3. นำการเปลี่ยนแปลงเฉพาะเครื่องกลับมา
git stash pop
```

---

## 4. แผนการอัปเดตสำหรับโรงพยาบาลอื่น (Multi-Hospital Deployment)

### 🏥 รูปแบบที่ 1: เครื่อง Windows + XAMPP (สคริปต์คลิกเดียว `update.bat`)

สร้างไฟล์ `update.bat` ไว้ที่หน้าโฟลเดอร์หลักของ eDHS เพื่อให้ Admin ของ รพ. เพียงแค่ดับเบิลคลิกเพื่ออัปเดต:

```bat
@echo off
chcp 65001 >nul
echo ===================================================
echo     eDebtor Hospital System (eDHS) - Updater
echo ===================================================
echo.

:: 1. สำรองไฟล์คอนฟิก
echo [*] กำลังสำรองไฟล์คอนฟิก...
if not exist "system\database_config\backups" mkdir "system\database_config\backups"
copy /Y "system\database_config\config.php" "system\database_config\backups\config_%date:~-4,4%%date:~-7,2%%date:~-10,2%.php" >nul
copy /Y "system\database_config\config.json" "system\database_config\backups\config_%date:~-4,4%%date:~-7,2%%date:~-10,2%.json" >nul
echo [OK] สำรองไฟล์คอนฟิกเรียบร้อย

:: 2. ดึงโค้ดล่าสุดผ่าน Git
echo [*] กำลังตรวจสอบและดึงอัปเดตจาก Git...
git stash >nul 2>&1
git pull origin main
if %ERRORLEVEL% NEQ 0 (
    echo [!] เกิดข้อผิดพลาดในการ Git Pull กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต
    pause
    exit /b %ERRORLEVEL%
)
echo [OK] ดึงโค้ดล่าสุดสำเร็จ

:: 3. รัน Database Auto-Migration (ถ้ามี)
echo [*] ตรวจสอบโครงสร้างฐานข้อมูล...
php -f "system/migration_tool.php" >nul 2>&1
echo [OK] อัปเดตโครงสร้างฐานข้อมูลเรียบร้อย

echo.
echo ===================================================
echo   อัปเดตระบบ eDHS เสร็จสมบูรณ์ พร้อมใช้งานแล้ว
echo ===================================================
pause
```

---

### 🐧 รูปแบบที่ 2: เครื่อง Linux Server (สคริปต์ Bash `update.sh`)

สำหรับ รพ. ที่รันบน Ubuntu / Debian / CentOS ให้สร้างไฟล์ `update.sh`:

```bash
#!/bin/bash
# ===================================================
# eDHS Linux Safe Update Script
# ===================================================
set -e

APP_DIR="/var/www/html/eDHS"
WEB_USER="www-data"   # หรือ apache สำหรับ CentOS

echo "==================================================="
echo "   Starting eDHS Update on Linux Server..."
echo "==================================================="

cd "$APP_DIR"

# 1. สำรองไฟล์คอนฟิก
echo "[1/4] Backing up local configuration..."
mkdir -p system/database_config/backups
cp system/database_config/config.php system/database_config/backups/config_$(date +%Y%m%d_%H%M%S).php
cp system/database_config/config.json system/database_config/backups/config_$(date +%Y%m%d_%H%M%S).json 2>/dev/null || true

# 2. ดึงโค้ดล่าสุด
echo "[2/4] Pulling latest code from Git..."
git stash
git pull origin main

# 3. จัดการสิทธิ์ของไฟล์ (Permissions)
echo "[3/4] Resetting web permissions..."
chown -R $WEB_USER:$WEB_USER "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod +x "$APP_DIR/update.sh"

# 4. รัน Database Migration & Reload Web Server
echo "[4/4] Executing Database Migration & Reloading Services..."
php -f "$APP_DIR/system/migration_tool.php" || true

if systemctl is-active --quiet apache2; then
    systemctl reload apache2
elif systemctl is-active --quiet httpd; then
    systemctl reload httpd
fi

if systemctl is-active --quiet php-fpm; then
    systemctl reload php-fpm
fi

echo "==================================================="
echo "   eDHS Update Completed Successfully!"
echo "==================================================="
```

---

### 📦 รูปแบบที่ 3: กรณี รพ. อยู่ในเครือข่ายปิด (Offline / Intranet Patching)

หาก รพ. ไม่สามารถเชื่อมต่ออินเทอร์เน็ตออกภายนอกเพื่อใช้ `git pull` ได้:
1. **ผู้พัฒนา:** เตรียมโฟลเดอร์แพตช์ เช่น `eDHS Update/21.09.69/` พร้อมสคริปต์ `apply_patch.bat`
2. **รพ. ปลายทาง:**
   - คัดลอกโฟลเดอร์แพตช์ไปวางทับในเครื่อง รพ.
   - รัน `apply_patch.bat` เพื่อคัดลอกเฉพาะไฟล์ที่เปลี่ยนแปลงเข้าสู่ระบบโดยอัตโนมัติ

---

## 5. ระบบตรวจสอบและป้องกันฐานข้อมูลเสียหาย (Database Safety & Auto-Migration)

ทุกครั้งที่มีการอัปเดตเวอร์ชันใหม่ ระบบ eDHS มีกลไกป้องกันฐานข้อมูลและปรับปรุงโครงสร้างอัตโนมัติ (Zero-Configuration Auto-Migration):
1. **Auto-Migration ในตัว:**
   - **ตาราง CR Breakdown (`imr_tb_debtor_cr_breakdown`) และ SSS Breakdown (`imr_tb_debtor_sss_breakdown`):** มีฟังก์ชัน `check_and_migrate_cr_tables()` และ `check_and_migrate_sss_tables()` ที่จะสร้างตารางและดัชนีให้อัตโนมัติเมื่อเปิดหน้าแรก
   - **ตารางประวัติการแบ่งจ่าย (`imr_tb_payment_history` - v21.2.09.69):** มีฟังก์ชัน `ensure_payment_history_table($conn)` อยู่ใน `system/database_config/db_helper.php` รันอัตโนมัติเมื่อมีการตัดชำระหนี้แบบแบ่งจ่าย (`datatimestamp-insert2.php`)
   - **การตรวจสอบและเติมคอลัมน์อัตโนมัติแบบ Defensive:** หากมีตารางเดิมอยู่แล้วแต่ยังไม่มีคอลัมน์ `bill_date` ฟังก์ชันจะใช้ `SHOW COLUMNS LIKE 'bill_date'` ตรวจจับ และรัน `ALTER TABLE ... ADD COLUMN bill_date DATE DEFAULT NULL AFTER bill_no` ให้อัตโนมัติโดยไม่ทำลายข้อมูลเดิม
2. **การรองรับ MySQL 8.0 Strict Mode & PHP 8.3:**
   - ฟังก์ชัน `parse_bill_date_to_sql($dateStr)` แปลงวันที่ภาษาไทย (พ.ศ.) และ ค.ศ. ทุกรูปแบบสู่ ISO `YYYY-MM-DD` และคืนค่า SQL `NULL` ทันทีเมื่อว่าง เพื่อป้องกันปัญหา `Incorrect date value: '' for column 'bill_date'` บน Ubuntu Linux
   - กำหนด `@mysqli_report(MYSQLI_REPORT_OFF);` ที่ส่วนหัวของ `system/database_config/db_helper.php` เพื่อป้องกัน Fatal Uncaught Exception บน PHP 8.1 - 8.3 ของเครื่อง รพ. ปลายทาง โดยที่ รพ. ไม่จำเป็นต้องแก้ไขไฟล์คอนฟิกเฉพาะที่ (`config.php`)

---

## 6. ลำดับขั้นตอนการนำแผนไปปฏิบัติ (Implementation Status)

| ขั้นตอน | งานที่ต้องทำ | ผู้รับผิดชอบ | สถานะ |
| :---: | :--- | :---: | :---: |
| **Step 1** | ติดตั้ง Git Client บนเครื่องแม่ข่าย และกำหนดค่า `.gitignore` แบบ Patch Mode | ผู้พัฒนา / Admin | ✅ เสร็จสิ้น |
| **Step 2** | สร้าง Git Repository (`https://github.com/aofdrifttion/eDHS.git`) และ Push ไฟล์แพตช์ขึ้นระบบ | ผู้พัฒนา / Admin | ✅ เสร็จสิ้น |
| **Step 3** | สร้างไฟล์ระบุเวอร์ชัน `version.json` และกลไกตรวจจับอัปเดตอัตโนมัติ | ผู้พัฒนา | ✅ เสร็จสิ้น |
| **Step 4** | สร้างสคริปต์ `update.bat` (Windows), `update.sh` (Linux) และตัวประมวลผลแกนกลาง `update_core.php` | ผู้พัฒนา | ✅ เสร็จสิ้น |
| **Step 5** | สร้าง Web UI Notification Banner และ One-Click Update Modal ใน eDHS (`system/api_update.php`) | ผู้พัฒนา | ✅ เสร็จสิ้น |
| **Step 6** | ระบบแสดงรายการปรับปรุงล่วงหน้า (Changelog Preview) และระบบบังคับอัปเดต (Mandatory Update Gate) | ผู้พัฒนา | ✅ เสร็จสิ้น (v21.2.09.69) |
| **Step 7** | รพ. ปลายทาง ดึงอัปเดตผ่าน `update.bat` / `update.sh` หรือกดปุ่ม 1-Click Update บนหน้าเว็บ eDHS | รพ. ปลายทาง | พร้อมใช้งาน 🚀 |

---

## 7. สถาปัตยกรรมระบบบังคับอัปเดตและแสดงรายการปรับปรุงล่วงหน้า (v21.2.09.69)

เพื่อให้มั่นใจว่าโรงพยาบาลเครือข่ายทุกแห่งใช้งานสูตรคำนวณลูกหนี้ สิทธิ์ และมาตรการความปลอดภัยเดียวกันเสมอ ระบบได้ติดตั้งกลไกความปลอดภัยระดับระบบ:

1. **ระบบแสดงรายการปรับปรุงล่วงหน้า (Changelog Preview Before Update):**
   - เมื่อมีเวอร์ชันใหม่ออกมา ผู้ใช้สามารถคลิกปุ่ม **"ดูรายละเอียด"** ที่แถบแจ้งเตือนด้านบน
   - ระบบจะดึงรายการเปลี่ยนแปลงของเวอร์ชันใหม่ขึ้นมาแสดงผลเป็นรายการแรกสุด (Index 0) พร้อมแถบสีส้มทอง และป้าย `(เวอร์ชั่นใหม่ที่จะปรับปรุง)` ให้ผู้ใช้เห็นรายละเอียดที่กำลังจะได้รับการแก้ไขอย่างโปร่งใส
2. **ระบบบังคับอัปเดตก่อนเข้าใช้งาน (Mandatory System Update Gate):**
   - ฟังก์ชัน `enforce_mandatory_system_update()` ถูกรันผ่าน `config.php` ทุกหน้าในระบบ
   - **กรณีเปิดผ่านเบราว์เซอร์:** ล็อกหน้าจอทุกโมดูลด้วย UI พอร์ทัลเต็มจอ (Full-screen Dark Glassmorphism) พร้อมแสดงรายการสิ่งที่ปรับปรุง และปุ่มกดอัปเดตทันที (ไม่สามารถกดปิดหรือข้ามได้)
   - **กรณีเบื้องหลัง (AJAX / POST API):** ปฏิเสธการทำงานและส่งสถานะ `HTTP 426 Upgrade Required` เพื่อป้องกันความไม่สอดคล้องของข้อมูล
   - **ข้อยกเว้นความปลอดภัย:** ยกเว้นหน้า `login.php`, `login_2fa.php`, `logout.php`, `register.php`, `api_update.php`, `update_core.php` และหน้าตั้งค่าระบบในโฟลเดอร์ `database_config/` เพื่อให้สามารถเข้าสู่ระบบและอัปเดตได้ราบรื่น
   - **สิทธิ์การกดอัปเดต:** อนุญาตให้ผู้ใช้ที่ล็อกอินเข้าสู่ระบบทุกคน (`isset($_SESSION['user_id'])`) สามารถกดปุ่มคลิกเดียวอัปเดตระบบได้ทันที ป้องกันปัญหาเจ้าหน้าที่ติดล็อกหน้าจอเมื่อแอดมินไม่อยู่
