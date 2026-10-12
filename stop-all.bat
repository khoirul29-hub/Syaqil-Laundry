@echo off
title Syaqil Laundry - Shutdown Manager
color 0c

echo ========================================================
echo       MENGHENTIKAN SISTEM SYAQIL LAUNDRY TERPADU        
echo ========================================================
echo.

echo Menghentikan server web terpadu (Port 8000)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a >nul 2>&1
)

echo.
echo ========================================================
echo SERVER WEB TERPADU BERHASIL DIHENTIKAN DENGAN BERSIH.
echo ========================================================
pause
