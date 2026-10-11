# Syaqil Laundry — Kasir dan Owner

Repo ini berisi aplikasi Kasir dan prototipe UI/UX Owner yang terpisah. Kasir menggunakan frontend React dan API PHP dengan MySQL/MariaDB. Owner berada di folder `owner/` dan saat ini memakai data demo tanpa backend atau autentikasi nyata.

## Membuka halaman Owner

Jalankan dari **folder utama repo** (folder yang berisi `package.json`), bukan dari folder `owner/`:

```sh
git clone https://github.com/BUZZCAT54/syaqil-laundry-terbaru.git
cd syaqil-laundry-terbaru
npm ci
npm run owner:dev
```

Jika repo sudah ada di komputer, lewati `git clone` dan masuk ke folder repo tersebut. Setelah server siap, buka **http://127.0.0.1:5174/#login** di browser. Isi email/username dan kata sandi apa pun yang tidak kosong, lalu klik **Masuk** untuk melihat Dashboard Owner. Login ini hanya simulasi tampilan; tidak memeriksa akun sungguhan. Biarkan terminal server tetap berjalan saat menggunakan halaman Owner. Petunjuk dan daftar halaman lain ada di [README Owner](owner/README.md).

Halaman GitHub menampilkan source code, bukan aplikasi yang langsung bisa dibuka. Preview Owner dijalankan secara lokal dengan perintah di atas.

## Mencoba preview yang sedang berjalan

Buka **http://127.0.0.1:8080/#kasir** pada komputer ini. Jika halaman login tampil dan mode demo aktif, pilih **Buka demo kasir**. Di halaman kasir, **Isi contoh transaksi** mengisi draft contoh tanpa menyimpan transaksi.

Akun demo bawaan saat menyiapkan database demo baru:

- Username: `kasir`
- Password: `KasirDemo!2026`

Gunakan akun yang sudah dikonfigurasi apabila kredensial instalasi ini telah diubah. Preview hanya tersedia selama server lokal dan database berjalan.

## Fitur

- Login dan logout kasir dengan password hash, sesi HttpOnly, token CSRF, dan pembatasan percobaan login.
- Dashboard dengan banner referensi, empat ringkasan, grafik pendapatan, distribusi status, layanan terpopuler, dan pesanan terbaru.
- Pesanan: filter status/tanggal, pencarian, urutan, pagination, detail, cetak, serta ekspor Excel.
- Pelanggan: empat ringkasan, tabel dan panel detail, riwayat transaksi, catatan, alamat, serta kategori pelanggan setia berdasarkan minimal lima transaksi. Tambah/edit profil tersimpan di database; impor CSV memiliki preview dan validasi per baris.
- Riwayat Nota: tabel dan detail kanan, ringkasan mengikuti filter, pencarian, tanggal/metode pembayaran, pagination, cetak ulang, ekspor Excel, dan unduh PDF.
- Laporan: filter periode, pendapatan, jumlah pesanan, metode pembayaran, distribusi layanan, dan ekspor Excel dari data yang tersimpan.
- Enam menu sesuai revisi: Dashboard, Kasir, Pesanan, Pelanggan, Riwayat Nota, Laporan. Halaman Layanan dan Pengaturan dihapus atas permintaan pengguna.
- Kasir: Reguler Rp6.000/kg, Ekspres 1 Hari Rp10.000/kg, Ekspres Jam Rp14.000/kg; tarif dibaca dari database. Tiga pilihan layanan, berat, catatan dan tombol tambah pada satu alur.
- Beberapa baris layanan, berat desimal, hapus item, diskon persen 0–100% (maksimal dua desimal), pembayaran tunai dan kembalian otomatis. Draft lama tetap mempertahankan label jenis cucian yang sudah tersimpan; input baru menggunakan Kiloan.
- Harga, satuan, nama layanan, dan pelanggan disalin ke transaksi agar nota lama tidak ikut berubah saat master diperbarui.
- Checkout atomik dengan nomor nota unik. Retry identik tidak menggandakan transaksi. Draft berbeda dengan identitas checkout yang sudah terpakai ditolak dan dipertahankan untuk diperiksa.
- Potongan dihitung dari subtotal dan dibulatkan ke rupiah terdekat. Input diskon kosong berarti 0%.
- Draft kasir disimpan di browser. Kegagalan menyimpan transaksi tidak menghapus isian.
- Preview struk, nota setelah pembayaran, pencarian riwayat nota, dan cetak melalui dialog browser dengan layout kertas 80 mm.
- Status berurutan: Diterima → Diproses → Selesai → Diambil, beserta catatan waktu dan kasir yang memperbarui.
- Semua riwayat tetap bisa dicari, termasuk pesanan yang lebih lama dari 1.000 transaksi. Pengambilan data menggunakan query berkelompok, bukan query per pesanan.
- Navigasi mobile, tabel yang dapat digeser di layar kecil, dialog keyboard/Escape, serta focus indicator.

## Yang perlu dikonfigurasi untuk operasional

**Pembayaran non-tunai:** QRIS dan transfer belum memiliki data merchant. Keduanya ditampilkan sebagai belum diatur dan tidak dapat mencatat sukses. Isi `QRIS_IMAGE` dengan URL/path gambar QRIS merchant yang benar, atau isi `BANK_NAME`, `BANK_ACCOUNT`, `BANK_HOLDER` di `.env`. Setelah diatur, kasir harus memeriksa dana masuk dan melakukan konfirmasi manual. Ini pencatatan pembayaran, bukan integrasi payment gateway atau verifikasi otomatis bank.

**WhatsApp:** adapter provider WhatsApp belum terhubung. Perubahan menjadi Selesai tetap disimpan, sementara status notifikasi dicatat sebagai `not_configured` dan ditampilkan kepada kasir. Tidak ada pesan sungguhan yang dikirim, tidak ada klaim terkirim, dan belum ada retry otomatis. Pemilihan provider, kredensial dan integrasi pengiriman diperlukan untuk mengaktifkan notifikasi otomatis.

**Durasi:** Ekspres 1 Hari menggunakan 24 jam. Durasi Reguler dan Ekspres Jam dibiarkan kosong karena belum ditentukan pada PRD. Estimasi ditampilkan sebagai belum ditentukan; tidak diasumsikan 6 jam. Owner/administrator database dapat mengisi `services.duration_hours`. Pesanan campuran dengan salah satu durasi belum ditentukan juga menampilkan estimasi belum ditentukan.

**Identitas usaha:** isi `BUSINESS_ADDRESS` dan `BUSINESS_PHONE` untuk mencantumkannya pada nota.

**Owner:** halaman manajemen dan keuangan tersedia sebagai prototipe UI/UX terpisah dengan data demo. Halaman tersebut belum terhubung ke API atau database kasir. Tidak ada endpoint pengubahan master atau pembatalan transaksi yang dibuka untuk kasir.

## Menjalankan dari source

Kebutuhan: Node.js 20.19+ atau 22.12+, npm, PHP 8.2+ dengan ekstensi `pdo_mysql`, `mbstring`, dan MySQL 8+/MariaDB 10.6+. PHP 8.5 dan MariaDB pada komputer ini sudah digunakan untuk pengujian.

1. Siapkan database kosong `syaqil_laundry` ber-charset `utf8mb4` dan user database khusus aplikasi. Berikan hak skema saat setup; gunakan hak operasional terbatas setelahnya.
2. Salin `.env.example` menjadi `.env`, lalu isi koneksi database dan kredensial akun awal. Jangan menimpa `.env` instalasi yang sudah ada.
3. Untuk database demo kosong, set `APP_DEMO="1"` dan gunakan kredensial demo yang disebutkan di atas. Untuk operasional, gunakan **database baru tanpa data demo**, `APP_DEMO="0"`, serta `INITIAL_USERNAME` dan `INITIAL_PASSWORD` unik (minimal 12 karakter). Mematikan mode demo pada database demo tidak otomatis mengganti password atau menghapus data contoh.
4. Jalankan dari folder aplikasi:

```sh
npm ci
php scripts/setup.php
npm run build
php -S 127.0.0.1:8080 -t public public/index.php
```

Jika modul PDO MySQL terpasang tetapi belum aktif pada PHP CLI, gunakan `php -d extension=pdo_mysql` untuk perintah PHP. Pada instalasi lain, aktifkan ekstensi tersebut pada konfigurasi PHP yang sesuai.

`scripts/setup.php` membuat tabel, memasukkan tiga layanan awal, dan membuat akun awal jika username belum ada. Script tidak mengganti password akun yang sudah ada. Migrasi kolom hash checkout, persentase diskon, dan catatan pelanggan berjalan tanpa menghapus transaksi. Nominal diskon pada nota lama tetap dipertahankan; nota baru mencatat persentase beserta nominal potongannya.

Untuk pengembangan UI, jalankan API pada port 8080, lalu `npm run dev`. Vite mem-proxy `/api` ke server PHP. Build statis saja tanpa API PHP dan database tidak cukup untuk menjalankan aplikasi.

Untuk Apache, arahkan DocumentRoot/virtual host ke folder `public`, aktifkan `mod_rewrite` dan `.htaccess`. `public/index.php` meneruskan request ke router; aset hasil build di `dist` dilayani oleh router. Gunakan root virtual host, bukan subfolder URL. Untuk produksi gunakan HTTPS dan PHP-FPM/Apache, bukan server development `php -S`. File `.env`, database, source `server`, dan `scripts` tidak boleh menjadi document root publik.

## Struktur

```text
src/                 UI kasir, dashboard, pesanan, pelanggan, nota
owner/               Prototipe frontend Owner, aset, dan tesnya
server/api.php       API autentikasi dan operasi kasir
server/domain.php    Validasi, perhitungan, identitas checkout
server/schema.sql    Skema MySQL/MariaDB
server/config.php    Koneksi dan pengaturan dari environment
server/router.php    Routing API dan aset build
scripts/setup.php    Setup dan migrasi awal
public/              Front controller Apache dan aset sumber
dist/                Hasil build frontend
dist-owner/          Hasil build frontend Owner
tests/               Pengujian domain dan integrasi API
```

## Verifikasi, 5 Oktober 2026

- Build Vite berhasil; seluruh berkas PHP lolos pemeriksaan sintaks.
- 20 tes aturan domain lulus: berat desimal/negatif/nol/presisi, diskon, pembayaran kurang, kembalian, status, nomor WhatsApp.
- 37 pemeriksaan API lulus pada database terpisah: autentikasi/CSRF, pelanggan, harga resmi, penyimpanan, pencegahan transaksi ganda dan konflik draft, status, izin kasir, logout, serta data setelah login ulang.
- Uji 1.002 fixture tambahan: nota paling lama dan paling baru sama-sama tetap dapat diambil. Fixture uji dibersihkan setelah pengujian; database preview tidak digunakan untuk tes ini.
- Pengujian frontend diskon mencakup 0%, 10%, 100%, perubahan subtotal, koma desimal, pembulatan rupiah, input kosong, dan penolakan nilai tidak valid.
- Browser: Rp78.000 − 10% (Rp7.800) = Rp70.200; tunai Rp100.000 → kembalian Rp29.800. Preview dan nota tersimpan menampilkan Diskon (10%). Diskon 101% memblokir pembayaran; 12,5% dihitung Rp9.750.
- Browser: input nomor WhatsApp salah → pesan validasi; tambah pelanggan benar → pelanggan otomatis dipilih.
- Browser: 4,25 kg Ekspres 1 Hari → Rp42.500; tunai Rp50.000 → kembalian Rp7.500; checkout → nota unik dan status Lunas.
- Browser: pesanan dapat ditemukan kembali, diperbarui sampai Diambil, dilihat nota digitalnya, dan dicari dari riwayat.
- Browser: QRIS/transfer tanpa konfigurasi → informasi yang jelas dan pembayaran terblokir; pembayaran tunai kurang → validasi, draft tetap ada.
- Browser: tombol cetak mengisi area cetak dengan nota yang dipilih tanpa error konsol. Cetak ke printer fisik belum diuji.
- Browser: Escape menutup dialog dan menu mobile berfungsi. Layout menjadi satu kolom pada lebar CSS sekitar 389 px; halaman tidak melebar horizontal, tabel mempunyai scroll internal. Desktop juga diperiksa.
- Tidak ditemukan error/warning JavaScript pada sesi uji akhir.
- Penyegaran empat halaman: klik tahapan menghasilkan baris dengan status yang sesuai; kelompok pelanggan dan detail dapat dibuka dengan keyboard; filter tanggal/metode/pencarian nota memperbarui ringkasan; cetak nota terbaru menyiapkan nota yang tepat.
- Pengujian agregasi mencakup pergantian hari Jakarta, rentang 7 hari melewati bulan, tanggal masa depan, data kosong, serta jumlah kunjungan dan transaksi terakhir pelanggan. Layout halaman baru diperiksa pada lebar CSS 389, 1.100, dan 1.910 px.

Menjalankan ulang tes:

```sh
php tests/domain.php
node tests/discount.mjs
node tests/insights.mjs
node tests/reference-utils.mjs
node tests/reports.mjs
python3 tests/customer-profile.py http://127.0.0.1:8081
python3 tests/api.py http://127.0.0.1:8081
```

Tes API menambah fixture; jalankan hanya terhadap server yang menggunakan database uji terpisah. `tests/history.php` menolak berjalan kecuali nama database adalah `syaqil_laundry_test`.

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
