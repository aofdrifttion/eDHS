@echo off
REM ========================================================
REM Automated Backup Script for eDHS
REM Run this using Windows Task Scheduler (e.g. daily at 2AM)
REM ========================================================

REM อ้างอิง Path ของ PHP และ Script แบบ Dynamic (ไม่ว่าจะเป็น xampp หรือ xampp8 หรืออยู่ไดรฟ์ไหน)
set SCRIPT_PATH=%~dp0run_backup.php
set PHP_PATH=%~dp0..\..\..\..\php\php.exe

REM แปลง Path ให้เป็นแบบสมบูรณ์ (Absolute Path)
for %%i in ("%PHP_PATH%") do set "PHP_PATH=%%~fi"

echo [%date% %time%] Starting Backup...
"%PHP_PATH%" "%SCRIPT_PATH%"
echo [%date% %time%] Backup Process Finished.
