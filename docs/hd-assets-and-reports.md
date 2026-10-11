# Aset HD dan laporan usaha — 6 Oktober 2026

Mode gambar: built-in imagegen, edit berdasarkan referensi pengguna. Semua aset final berada di `public/assets/`. Gambar referensi asli tetap disimpan. Banner direstorasi secara generatif; rincian ilustrasi dapat berbeda dari sumber. UI, teks navigasi, tabel, angka, dan grafik tetap komponen HTML/SVG/CSS.

## Aset final

| File | Resolusi kanvas |
| --- | --- |
| cashier-banner-hd.png | 2172 × 724 |
| customers-banner-hd.png | 2172 × 724 |
| dashboard-banner-hd.png | 2172 × 724 |
| dashboard-promo-hd.png | 1738 × 905 |
| history-banner-hd.png | 2172 × 724 |
| orders-art-hd.png | 989 × 1591 |
| report-banner-hd.png | 2172 × 724 |
| report-growth-hd.png | 1290 × 1219 |
| sidebar-art-hd.png | 1043 × 1508 |

Banner memakai wilayah tengah kanvas. Padding generasi tidak ditampilkan oleh CSS. Resolusi kanvas bukan resolusi wilayah banner; lebar banner tetap 2172 piksel. Logo sidebar memakai logo asli dan teks HTML.

## Spesifikasi prompt final

Instruksi bersama: precise-object-edit; faithful HD restoration, not redesign; preserve original composition, palette, lettering, and logo; crisp clean outlines, no blur; extract only the requested artwork, no surrounding interface.

- Kasir: panel 02 pada reference-pages.png, x880 y55 w635 h92. Lime kiri dengan logo/nama SYAQIL LAUNDRY, BERSIH • CEPAT • PRAKTIS, burst EXPRESS 1 HARI!, washer dan tanda SELESAI CEPAT KUALITAS TETAP JUARA. Canvas 3:1, strip tengah sempit sekitar 43.5% tinggi.
- Dashboard: panel 01, x126 y56 w617 h90. Pale blue left 37% without greeting text; laundry basket blue/white/yellow, crowns/smiles, GOOD CLOTHES BRIGHTER DAYS sticker. Canvas 3:1, strip tengah sempit, padding magenta hanya di luar wilayah terlihat.
- Promo dashboard: x564 y324 w180 h94. LAUNDRY HARI INI LEBIH RAPI!, BERSIH • CEPAT • PRAKTIS, basket kanan.
- Pesanan: x664 y507 w90 h145. Basket biru/putih, smile, Pantau setiap pesanan dengan mudah!
- Sidebar: reference-customers.png x35 y670 w217 h314. Cobalt background, cyan waves, black/lime BERSIH / LEBIH CEPAT / LEBIH PRAKTIS sticker, white smile and SYAQIL LAUNDRY strip.
- Pelanggan: reference-customers.png x267 y111 w1230 h196. PELANGGAN SETIA PRIORITAS KAMI!, BERSIH RAPI WANGI SELALU UNTUKMU!, komposisi logo dan washer asli. Canvas 3:1, strip tengah 48%, padding magenta di luar crop.
- Riwayat: reference-history.png x270 y100 w1227 h200. SETIAP NOTA ADA CERITA!, TERIMA KASIH SUDAH PERCAYA SYAQIL LAUNDRY! Canvas 3:1, strip tengah 48%, padding di luar crop.
- Banner laporan: reference-reports.png x255 y79 w1237 h165. Laporan Usaha; Pantau perkembangan Syaqil Laundry dalam satu tampilan.; DATA HARI INI UNTUK MASA DEPAN YANG LEBIH BAIK!; washer dan Bersih Pakaian Cerahkan Harimu!
- Ilustrasi laporan: x1168 y678 w329 h311. PERTUMBUHAN YANG KONSISTEN, grafik naik, checklist Pendapatan Naik / Pelanggan Bertambah / Layanan Semakin Dikenal, blue LAUNDRY HARI INI LEBIH BAIK DARI KEMARIN!

## Laporan

Susunan mengikuti referensi terbaru: banner, filter, empat KPI, grafik pendapatan, layanan terlaris, metode pembayaran, ringkasan harian, status pesanan, ilustrasi pertumbuhan. Filter disunting sebagai draft dan baru diterapkan setelah tombol Terapkan Filter. Perbandingan tersedia untuk periode sebelumnya/tahun sebelumnya; pembanding nol ditampilkan sebagai Baru. Angka bukan salinan angka contoh gambar.

Pendapatan memakai total nota setelah diskon. Berat hanya item kg. Peringkat layanan menghitung satu nota satu kali per layanan. Ringkasan harian mencakup tanggal kosong dengan nilai nol, tujuh tanggal per halaman. Excel memakai transaksi hasil filter.

## Verifikasi

Build Vite lulus. Tes laporan mencakup tanggal inklusif, total setelah diskon, berat kg, deduplikasi layanan per nota, total harian, pembayaran tak dikenal, periode sebelumnya, tahun kabisat, baseline nol dan data kosong. Tes domain 20 kasus serta integritas XLSX/PDF juga lulus.

Browser memverifikasi draft filter belum mengubah angka sebelum Apply, filter hari kosong, perbandingan periode, jenis pembayaran, pagination harian bulanan, serta layar mobile CSS 389px tanpa overflow dokumen. Ekspor laporan hasil browser berisi 5 transaksi, Rp196.000, dan 25kg sesuai UI. Konsol sesi akhir tidak memuat error/warning.

## Preview lokal

Server berjalan pada 127.0.0.1:8080 dan database proyek pada 127.0.0.1:3307, melalui unit transient user `syaqil-preview-web` dan `syaqil-preview-db`. Tidak diaktifkan saat boot. `.env` pengguna tidak diubah. Untuk menghentikan preview: `systemctl --user stop syaqil-preview-web syaqil-preview-db`.
