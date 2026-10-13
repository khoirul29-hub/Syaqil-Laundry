# Syaqil Laundry — Kasir dan Owner (Unified Monolith Architecture)

Repo ini berisi aplikasi sistem kasir dan manajemen owner terpadu untuk **Syaqil Laundry Ekspres**. Frontend React SPA dan backend **Laravel 10** kini tergabung dalam satu proyek utuh (monolith terpadu) dengan satu basis data MySQL (`syaqil_laundry`), autentikasi Sanctum RBAC, perhitungan transaksi otoritatif, pencatatan keuangan real-time, cetak struk nota, dan notifikasi WhatsApp yang resilien.

---

## 🔑 Kredensial Akun Resmi

- **URL Aplikasi Terpadu (Port 8000):** **http://127.0.0.1:8000/**
- **Owner:**
  - Username: `owner`
  - Kata Sandi: `OwnerSyaqil!2026`
- **Kasir:**
  - Username: `kasir`
  - Kata Sandi: `KasirSyaqil!2026`

---

## 🚀 Menjalankan Aplikasi Secara Lokal

1. **Jalankan Database MySQL (Laragon atau daemon):** Pastikan port `3306` aktif dan database `syaqil_laundry` tersedia.
2. **Jalankan Aplikasi Web Terpadu (Port 8000):**
   ```sh
   php artisan serve --host=127.0.0.1 --port=8000
   ```
   *Atau cukup klik dua kali file:* `start-all.bat`
3. **Membangun Aset Frontend (Bila Ada Perubahan Kode):**
   ```sh
   npm run build
   ```

---

## 🧪 Pengujian Otomatis Seluruh Modul (100% Pass / 112 Pengujian)

Untuk memverifikasi keandalan sistem langsung dari terminal proyek:
- `npm test` atau `php tests/suites/test_cashier_orders.php` (Modul 3 — 18 Pengujian)
- `npm run test:all` (Menjalankan seluruh 6 test suites integrasi backend)
- `php tests/suites/test_auth_rbac.php` (Modul 2 — 12 Pengujian)
- `php tests/suites/test_cashier_orders.php` (Modul 3 — 18 Pengujian)
- `php tests/suites/test_owner_management.php` (Modul 4 — 19 Pengujian)
- `php tests/suites/test_whatsapp_receipts.php` (Modul 5 — 27 Pengujian)
- `php tests/suites/test_payments_e2e.php` (Modul 6 — 15 Pengujian)
- `php tests/suites/test_public_receipt_internet.php` (Modul 7 — 21 Pengujian)

Pengujian Unit Frontend (JavaScript):
- `node tests/js/reports.mjs`
- `node tests/js/insights.mjs`
- `node tests/js/connection.mjs`
- `node tests/js/reconnection.mjs`
- `node tests/js/reference-utils.mjs`

## Fitur

- Login dan logout kasir & owner dengan password hash, sesi HttpOnly Sanctum, token CSRF, dan rate limiting.
- Dashboard dengan ringkasan transaksi, grafik pendapatan, distribusi status, layanan terpopuler, dan pesanan terbaru.
- Pesanan: filter status/tanggal, pencarian, pagination, detail status berurutan (Diterima -> Diproses -> Selesai -> Diambil).
- Pelanggan: pendaftaran & manajemen data pelanggan dengan normalisasi nomor WhatsApp 62xxx.
- Riwayat Nota: pencarian nota, cetak struk (layout 80mm), ekspor Excel, dan unduh PDF.
- Tautan Nota Publik & WhatsApp: nota digital yang dapat dibuka secara aman via tautan unik oleh pelanggan dari internet tanpa login kasir.
- Status Pembayaran: pemantauan transaksi Lunas & Belum Bayar secara konsisten di kasir maupun panel owner.

---

## 📁 Struktur Direktori Bersih

```text
app/                 Backend Laravel (Controllers, Models, Middleware, Services)
bootstrap/           Bootstrap kernel Laravel
config/              Konfigurasi Laravel (auth, cors, database, sanctum, dll)
database/            Migrasi dan seeder database MySQL
public/              Web root Laravel & aset bundle frontend Vite (index.html, assets)
routes/              Rute API (/api) dan Web Laravel
src/                 Frontend React SPA (Kasir & terpadu Owner di src/owner/)
tests/suites/        Suite pengujian integrasi otomatis backend Laravel
tests/js/            Pengujian unit JavaScript untuk utilitas frontend
start-all.bat        Skrip cepat menjalankan aplikasi port 8000
stop-all.bat         Skrip menghentikan daemon server lokal
```


## Backup

Gunakan backup database harian dan sebelum migrasi. Contoh berikut meminta password secara interaktif agar tidak tercatat di command history:

```sh
mysqldump -u syaqil -p --single-transaction --default-character-set=utf8mb4 syaqil_laundry > backup-syaqil.sql
```

Simpan backup serta salinan `.env` secara terpisah dari folder web publik. Uji pemulihan ke database kosong sebelum mengandalkan prosedur backup. Data transaksi tidak termasuk dalam arsip source code.

## Keputusan desain

Biru electric dan lime mengikuti gambar pengguna: biru untuk navigasi/pilihan/aksi, lime untuk total. Inter mengikuti dokumen desain untuk keterbacaan form; Archivo Black/Anton digunakan pada headline dan stiker. Form putih dan tabel ringan menjaga fokus input. Dekorasi hanya ditempatkan pada banner/sidebar/area nota. Aset logo dan potongan visual banner berasal dari gambar pengguna. Tampilan mengikuti referensi dengan nama, harga, layanan, dan menu yang disesuaikan ke Syaqil Laundry.

## Pembaruan referensi, 5 Oktober 2026

Komposisi halaman disusun mengikuti gambar terbaru berukuran 1536 × 1024. Banner, identitas sidebar, stiker, dan ilustrasi menggunakan wilayah gambar asli. Tabel, formulir, grafik, panel detail, dan seluruh kontrol adalah komponen aplikasi dengan data dari API. Nama akun, jumlah pelanggan, nominal dan tanggal mengikuti data yang benar-benar tersedia.

Pemeriksaan terbaru: 16 pemeriksaan profil pelanggan, 37 pemeriksaan API transaksi, 20 pemeriksaan domain, tes diskon/frontend, agregasi tanggal, serta integritas Excel dan pembuatan PDF lulus. Browser memverifikasi transaksi uji Rp45.000 − 12,5% = Rp39.375, pembayaran Rp50.000 dan kembalian Rp10.625; nota ditemukan kembali dan diunduh ke PDF/Excel dengan persentase yang sama. Navigasi enam halaman diperiksa pada lebar CSS 389 px tanpa overflow halaman; tabel bergeser di dalam wadahnya.

Lingkungan preview sesi ini memakai database proyek pada port 3307 (socket `/tmp/syaqil-mysql.sock`) karena port 3306 sudah dipakai database lokal lain. Server PHP preview menerima koneksi melalui environment saat dijalankan; `.env` pengguna tidak diubah.

## Laporan Usaha dan aset HD, 6 Oktober 2026

Laporan mengikuti referensi terbaru dengan empat ringkasan, grafik pendapatan, layanan terlaris, distribusi pembayaran/status, ringkasan harian, filter periode dan perbandingan. Export Excel mengikuti hasil filter. Banner dan ilustrasi yang semula dipotong dari gambar gabungan diganti sembilan aset HD terpisah. Detail aset, spesifikasi prompt, dan bukti verifikasi berada di [catatan aset HD dan laporan](docs/hd-assets-and-reports.md). Menu Layanan dan Pengaturan tetap dihapus.
