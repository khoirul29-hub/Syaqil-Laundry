@echo off
title Syaqil Laundry - Shutdown Manager
color 0c

echo ========================================================
echo       MENGHENTIKAN SISTEM SYAQIL LAUNDRY EKSPRES        
echo ========================================================
echo.

echo Menghentikan proses PHP artisan serve (Port 8000)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a >nul 2>&1
)

echo Menghentikan proses Frontend Kasir (Port 5173)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5173" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a >nul 2>&1
)

echo Menghentikan proses Frontend Owner (Port 5174)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5174" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a >nul 2>&1
)

echo.
echo ========================================================
echo SEMUA SERVER BERHASIL DIHENTIKAN DENGAN BERSIH.
echo ========================================================
pause
