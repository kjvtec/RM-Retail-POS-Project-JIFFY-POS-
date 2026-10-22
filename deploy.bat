@echo off
chcp 65001 >nul
echo ===================================================================
echo   RM-Retail POS Project (JIFFY POS) - One-Click Deployment Helper
echo ===================================================================
echo.
echo เลือกลำดับงานที่ต้องการ:
echo [1] รันระบบทดสอบในเครื่องทันที (Local Server: http://localhost:3000)
echo [2] ตรวจสอบการเชื่อมต่อฐานข้อมูล Supabase (Verify Supabase Connection)
echo [3] Deploy ขึ้น Vercel ทันที (Vercel CLI Deploy)
echo [4] ออกจากโปรแกรม
echo.
set /p choice="กรุณาเลือกหมายเลข (1-4): "

if "%choice%"=="1" (
    echo.
    echo 🚀 กำลังเริ่มเซิร์ฟเวอร์ทดสอบ...
    npm start
)
if "%choice%"=="2" (
    echo.
    echo 🔍 กำลังตรวจสอบการเชื่อมต่อฐานข้อมูล Supabase...
    node verify_supabase.js
    pause
)
if "%choice%"=="3" (
    echo.
    echo ⚡ กำลังสั่ง Deploy ขึ้น Vercel...
    npx vercel --prod
    pause
)
if "%choice%"=="4" (
    exit
)
