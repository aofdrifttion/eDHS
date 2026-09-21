@echo off
chcp 65001 > nul
title eDHS System Auto-Updater (Windows)
color 0B

echo ==============================================================================
echo              eDebtor Hospital System (eDHS) - ระบบอัปเดตอัตโนมัติ
echo ==============================================================================
echo.

:: 1. ดึงไฟล์ล่าสุดจาก GitHub (Git Pull)
echo [1/3] กำลังตรวจสอบและดึงไฟล์อัปเดตจาก GitHub...
where git >nul 2>nul
if %errorlevel% equ 0 (
    git pull origin master
    if %errorlevel% neq 0 (
        echo [คำเตือน] การดึงข้อมูลจาก GitHub ไม่สำเร็จ จะดำเนินการอัปเดตจากไฟล์ในเครื่องต่อ...
    ) else (
        echo [สำเร็จ] ดึงไฟล์อัปเดตล่าสุดจาก GitHub เรียบร้อยแล้ว
    )
) else (
    echo [แจ้งเตือน] ไม่พบคำสั่ง git ในระบบ ข้ามขั้นตอน git pull และใช้ไฟล์อัปเดตในเครื่อง...
)

echo.
:: 2. ค้นหาโปรแกรม PHP ในเครื่อง
echo [2/3] กำลังตรวจสอบสภาพแวดล้อม PHP...
set PHP_BIN=
where php >nul 2>nul
if %errorlevel% equ 0 (
    set PHP_BIN=php
) else if exist "C:\xampp8\php\php.exe" (
    set PHP_BIN="C:\xampp8\php\php.exe"
) else if exist "C:\xampp\php\php.exe" (
    set PHP_BIN="C:\xampp\php\php.exe"
) else if exist "D:\xampp8\php\php.exe" (
    set PHP_BIN="D:\xampp8\php\php.exe"
) else if exist "D:\xampp\php\php.exe" (
    set PHP_BIN="D:\xampp\php\php.exe"
)

if "%PHP_BIN%"=="" (
    color 0C
    echo.
    echo [ข้อผิดพลาด] ไม่พบโปรแกรม php.exe ในระบบ หรือใน C:\xampp8\php
    echo กรุณาตรวจสอบว่าได้ติดตั้ง XAMPP หรือระบุตำแหน่ง PHP ถูกต้องหรือไม่
    echo.
    pause
    exit /b 1
)

echo [สำเร็จ] พบ PHP ที่: %PHP_BIN%

echo.
:: 3. สั่งรันตัวประมวลผลอัปเดตแกนกลาง
echo [3/3] กำลังดำเนินการติดตั้งไฟล์แพตช์และอัปเดตระบบ...
echo ------------------------------------------------------------------------------
%PHP_BIN% update_core.php --cli
echo ------------------------------------------------------------------------------

echo.
echo ==============================================================================
echo  การดำเนินการเสร็จสิ้นเรียบร้อยแล้ว กดปุ่มใดๆ เพื่อปิดหน้าต่างนี้
echo ==============================================================================
pause > nul
