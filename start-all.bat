@echo off
title Syaqil Laundry - Satu Aplikasi Terpadu (Port 8000)
color 0b

echo ========================================================
echo       MENYALAKAN SISTEM SYAQIL LAUNDRY TERPADU           
echo               (Kasir ^& Owner - Port 8000)                
echo ========================================================
echo.

set "ROOT_DIR=%~dp0"
set "PHP_BIN=C:\laragon\bin\php\php-8.1.34-Win32-vs16-x64\php.exe"
if not exist "%PHP_BIN%" set "PHP_BIN=php"

echo [1/1] Menjalankan Server Web Terpadu Laravel (Port 8000)...
start "Syaqil Laundry - Server Terpadu" cmd /k "cd /d %ROOT_DIR% && ^"%PHP_BIN%^" artisan serve --host=127.0.0.1 --port=8000"

echo.
echo Menunggu 2 detik untuk inisialisasi server...
timeout /t 2 /nobreak >nul

echo Membuka aplikasi web terpadu di browser...
start http://127.0.0.1:8000/

echo.
echo ========================================================
echo APLIKASI WEB TERPADU BERHASIL DIJALANKAN!
echo.
echo URL Utama: http://127.0.0.1:8000/
echo.
echo Akun Login:
echo - Kasir : username 'kasir' / password 'KasirSyaqil!2026'
echo - Owner : username 'owner' / password 'OwnerSyaqil!2026'
echo.
echo Biarkan jendela terminal server tetap berjalan saat memakai aplikasi.
echo Tekan tombol apa saja untuk menutup jendela peluncur ini.
echo ========================================================
pause >nul
