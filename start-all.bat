@echo off
title Syaqil Laundry - Startup Manager
color 0b

echo ========================================================
echo       MENYALAKAN SISTEM SYAQIL LAUNDRY EKSPRES          
echo ========================================================
echo.

set "ROOT_DIR=%~dp0"
set "PHP_BIN=C:\laragon\bin\php\php-8.1.34-Win32-vs16-x64\php.exe"
set "NODE_DIR=C:\Program Files\nodejs"

if exist "%NODE_DIR%" (
    set "PATH=%NODE_DIR%;%PATH%"
)

echo [1/3] Menjalankan Backend Laravel API (Port 8000)...
start "Syaqil - Backend Laravel API" cmd /k "cd /d %ROOT_DIR%backend && ^"%PHP_BIN%^" artisan serve --host=127.0.0.1 --port=8000"

echo [2/3] Menjalankan Frontend Kasir (Port 5173)...
start "Syaqil - Frontend Kasir" cmd /k "cd /d %ROOT_DIR% && npm run dev"

echo [3/3] Menjalankan Frontend Owner (Port 5174)...
start "Syaqil - Frontend Owner" cmd /k "cd /d %ROOT_DIR% && npm run owner:dev"

echo.
echo Menunggu 4 detik untuk inisialisasi server...
timeout /t 4 /nobreak >nul

echo Membuka halaman aplikasi di browser...
start http://127.0.0.1:5173/#login
start http://127.0.0.1:5174/#login

echo.
echo ========================================================
echo SEMUA LAYANAN BERHASIL DIJALANKAN!
echo.
echo - Kasir  : http://127.0.0.1:5173/#login (kasir / KasirSyaqil!2026)
echo - Owner  : http://127.0.0.1:5174/#login (owner / OwnerSyaqil!2026)
echo - Backend: http://127.0.0.1:8000
echo.
echo Jangan menutup jendela terminal yang terbuka selama memakai aplikasi.
echo Tekan tombol apa saja untuk menutup jendela peluncur ini.
echo ========================================================
pause >nul
