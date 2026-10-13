# DOKUMEN PENJELASAN ALUR SISTEM SYAQIL LAUNDRY
**Dokumen Referensi Perancangan Diagram Sistem (Use Case, Activity, Flowchart, Sequence, ERD/Class Diagram)**

---

## 1. PENDAHULUAN & ARSITEKTUR SISTEM

Sistem Informasi **Syaqil Laundry** merupakan aplikasi berbasis web terintegrasi yang melayani dua fungsi utama:
1. **Sistem Operasional Kasir (Front-Office):** Menangani pencatatan antrean cucian, penimbangan, kalkulasi diskon & pembayaran, penerbitan struk nota kasir 80mm, pembaruan status pengerjaan, hingga pemicu notifikasi WhatsApp pelanggan.
2. **Sistem Manajemen Owner (Back-Office):** Menangani pemantauan omzet, pencatatan biaya pengeluaran operasional, pembukuan laba-rugi, master data layanan & riwayat harga, pengelolaan kasir, audit koreksi/pembatalan nota, dan ekspor laporan keuangan.

### Arsitektur Teknis Sistem
- **Frontend Kasir:** React SPA (`src/`), berjalan di port `5173`.
- **Frontend Owner:** React SPA (`owner/`), berjalan di port `5174`.
- **Backend API:** Laravel 10 REST API terpusat (`backend/routes/api.php`), berjalan di port `8000` / `8080`.
- **Basis Data:** MySQL / MariaDB (`syaqil_laundry`).
- **Autentikasi & Keamanan:** Laravel Sanctum Cookie Session (HttpOnly), CSRF Protection, Password Hashing (Bcrypt), dan Role-Based Access Control (RBAC).

---

## 2. IDENTIFIKASI AKTOR & PERAN (ROLES)

Dalam pemodelan Use Case dan pemetaan Swimlane, sistem memiliki 4 aktor:

| No | Aktor | Kategori | Deskripsi Peran & Tanggung Jawab |
|---|---|---|---|
| 1 | **Petugas Kasir** | Internal (User) | Melakukan login, mencatat pesanan baru, menimbang cucian, memproses pembayaran (Tunai/QRIS/Transfer), mencetak nota struk, memperbarui status cucian bertahap, dan memicu notifikasi WhatsApp. |
| 2 | **Owner / Manajer** | Internal (User) | Memantau dashboard performa keuangan, mencatat pengeluaran kas, mengelola master tarif & layanan, melakukan audit/koreksi nota salah, mengelola staf kasir, dan mengunduh laporan. |
| 3 | **Administrator** | Internal (Sistem/Teknis) | Mengonfigurasi lingkungan sistem, migrasi database, dan pemeliharaan server. |
| 4 | **Pelanggan** | Eksternal | Menyerahkan pakaian, menerima rincian biaya, melakukan pembayaran, menerima struk nota, menerima notifikasi WhatsApp, dan mengambil pakaian bersih. |

---

## 3. PENJELASAN USE CASE DIAGRAM

Diagram Use Case memetakan fungsionalitas sistem berdasarkan hak akses aktor.

### 3.1 Daftar Use Case Petugas Kasir
1. **UC-01: Login ke Sistem Kasir**
   - Kasir memasukkan username dan password.
   - Sistem memverifikasi kredensial dan mencatat `last_login_at` serta log aktivitas.
2. **UC-02: Memantau Antrean & Dashboard Kasir**
   - Menampilkan total pesanan hari ini, pesanan aktif, pesanan siap ambil, dan ringkasan pendapatan kasir.
3. **UC-03: Mengelola Data Pelanggan**
   - Mencari pelanggan berdasarkan nomor telepon/nama, mendaftarkan pelanggan baru, atau memperbarui alamat & catatan khusus.
4. **UC-04: Menginput Transaksi Laundry**
   - Memilih pelanggan, memilih jenis layanan (Reguler, Ekspres 1 Hari, Ekspres Jam), memasukkan berat timbangan (kg) desimal, dan input catatan pakaian.
5. **UC-05: Menghitung Biaya & Diskon Otomatis**
   - Sistem otomatis menghitung subtotal = berat × tarif layanan.
   - Menerapkan diskon persentase (0–100%) dengan pembulatan rupiah yang presisi.
6. **UC-06: Memproses Pembayaran (Checkout)**
   - Mendukung metode Tunai (dengan kalkulasi kembalian otomatis), QRIS, atau Transfer Bank.
   - Menggunakan mekanisme idempotency (`request_key`) agar transaksi tidak terduplikasi saat koneksi lambat.
7. **UC-07: Mencetak Struk Nota Pembayaran**
   - Menerbitkan nota kasir format standar 80mm yang berisi nomor nota unik, nama pelanggan, rincian pakaian, tarif, dan estimasi waktu selesai.
8. **UC-08: Memperbarui Status Pengerjaan Cucian**
   - Mengubah status secara linier: `Diterima` ➔ `Diproses` ➔ `Selesai` ➔ `Diambil`.
9. **UC-09: Mengirim Notifikasi WhatsApp**
   - Mengirim rincian nota atau pesan pemberitahuan bahwa cucian telah `Selesai` ke nomor WhatsApp pelanggan.

### 3.2 Daftar Use Case Owner / Manajer
1. **UC-10: Login ke Panel Owner**
   - Autentikasi khusus akun dengan role `owner`.
2. **UC-11: Memantau Analisis Bisnis & Grafik Laba-Rugi**
   - Melihat visualisasi pendapatan kotor, pengeluaran, laba bersih, tren bulanan, dan layanan paling diminati.
3. **UC-12: Mengelola Pengeluaran Kas (Expenses)**
   - Mencatat biaya operasional (deterjen, listrik, sewa, gaji), mengedit, dan menghapus pengeluaran.
4. **UC-13: Mengelola Master Layanan & Tarif**
   - Menambah layanan baru, mengubah harga per kg, mengubah durasi estimasi jam, atau menonaktifkan layanan.
   - *Include:* Sistem secara otomatis mencatat ke riwayat harga (`price_histories`).
5. **UC-14: Melakukan Koreksi & Pembatalan Transaksi**
   - Melakukan pembatalan transaksi salah atau penyesuaian timbangan/harga.
   - *Include:* Mencatat audit trail perubahan ke `order_corrections` untuk akuntabilitas.
6. **UC-15: Mengelola Data Karyawan Kasir**
   - Mendaftarkan kasir baru, mengatur jam shift kerja, mengaktifkan/menonaktifkan akun kasir, dan mereset password kasir.
7. **UC-16: Mengelola Pengaturan Toko**
   - Mengatur nama outlet, alamat toko, nomor telepon resmi, dan rekening/QRIS pembayaran.
8. **UC-17: Menghasilkan & Mengekspor Laporan**
   - Menyaring data berdasarkan rentang tanggal tertentu dan mengekspor laporan ke format Excel atau PDF.

### 3.3 Relasi Antar Use Case (Include & Extend)
- **`Checkout Pesanan` <<include>> `Validasi & Kalkulasi Tarif Otomatis`** (Setiap pesanan wajib dikalkulasi di backend).
- **`Checkout Pesanan` <<include>> `Cetak Struk Nota`** (Penerbitan nota terjadi otomatis setelah pembayaran tersimpan).
- **`Pembaruan Status Cucian (ke Selesai)` <<extend>> `Kirim Notifikasi WhatsApp`** (Notifikasi dipicu ketika cucian siap diambil).
- **`Ubah Tarif Layanan` <<include>> `Catat Riwayat Perubahan Harga`** (Perubahan harga selalu dicatat ke log riwayat).
- **`Koreksi Transaksi` <<include>> `Catat Log Audit Koreksi`** (Alasan dan selisih angka dicatat ke riwayat koreksi).

---

## 4. PENJELASAN ALUR PROSES BISNIS & FLOWCHART (KASIR)

Alur transaksi kasir digambarkan dalam 3 swimlane utama: **Petugas Kasir**, **Sistem Pengolah Data**, dan **Bukti Nota & Pelanggan**.

```
[KASIR] Mulai 
  │
  ├─► Buka Menu Kasir
  │
  ├─► Input/Cari Data Pelanggan ──► [SISTEM] Cek Data di Database
  │                                    ├─ Ada: Muat info pelanggan
  │                                    └─ Tidak ada: Tambah pelanggan baru
  │
  ├─► Timbang Cucian (Kg) & Pilih Layanan (Reguler / Ekspres 1 Hari / Ekspres Jam)
  │
  ├─► Masukkan Diskon (Jika Ada: 0 - 100%)
  │
  ├─► [SISTEM] Kalkulasi Total Biaya & Tanggal Selesai (Due Date)
  │
  ├─► Pilih Metode Pembayaran (Tunai / QRIS / Transfer)
  │     ├─ Tunai: Kasir input uang diterima ──► [SISTEM] Hitung Kembalian
  │     └─ Non-Tunai: Kasir konfirmasi bukti transfer / scan QRIS
  │
  ├─► Klik Tombol "Bayar / Checkout"
  │
  ├─► [SISTEM] Transaksi Basis Data (DB Transaction Atomik):
  │     1. Simpan tabel orders (Nomor Nota Unik)
  │     2. Simpan tabel order_items (Detail item, berat, harga satuan)
  │     3. Simpan tabel status_history (Status awal: Diterima)
  │     4. Catat notifikasi siap kirim
  │
  └─► [SISTEM & NOTA] Terbitkan Struk Kasir (80mm) ──► Serahkan ke Pelanggan
```

---

## 5. PENJELASAN ACTIVITY DIAGRAM (MANAJEMEN PESANAN LENGKAP)

Activity Diagram mencakup seluruh siklus hidup pesanan (Create, Read, Update Status, Payment).

### Tahap 1: Inisialisasi & Pengambilan Data Awal (Bootstrap)
1. Kasir membuka aplikasi kasir.
2. Frontend memanggil `GET /api/bootstrap`.
3. Backend mengembalikan daftar master layanan aktif, riwayat pelanggan terbaru, dan antrean cucian aktif.

### Tahap 2: Input & Validasi Pesanan
1. Kasir memasukkan item cucian dengan bobot desimal.
2. Frontend memvalidasi kuantitas > 0 dan tarif sesuai master data.
3. Kasir menentukan diskon dan metode pembayaran.

### Tahap 3: Simpan Transaksi (Atomic Save)
1. Frontend mengirim payload JSON ke `POST /api/orders` dengan menyertakan `request_key` (UUID unik).
2. Middleware memeriksa otentikasi Sanctum dan peran kasir/owner.
3. Backend memeriksa apakah `request_key` sudah pernah diproses:
   - Jika sudah ada: Kembalikan data pesanan yang sudah ada (mencegah double input).
   - Jika belum ada: Mulai transaksi database (`DB::beginTransaction()`).
4. Generate nomor nota unik (Format: `SYQ-YYYYMMDD-XXXX`).
5. Simpan record pesanan, record rincian item, dan riwayat status pertama.
6. Commit transaksi dan kirimkan response HTTP 201 Created.

### Tahap 4: Siklus Status Cucian (Linear Progress Flow)
Sistem menerapkan State Transition terstruktur:
1. **Diterima:** Pakaian kotor telah ditimbang dan nota tercetak.
2. **Diproses:** Pakaian sedang dicuci, dikeringkan, atau disetrika di ruang produksi.
3. **Selesai:** Pakaian telah dilipat rapi, dipacking wangi, dan siap diambil. Pemicu pesan WhatsApp ke nomor telepon pelanggan.
4. **Diambil:** Pelanggan datang membawa nota fisik / nomor telepon dan mengambil cucian. Transaksi selesai sepenuhnya.

---

## 6. PENJELASAN SEQUENCE DIAGRAM (TRANSAKSI CHECKOUT & CETAK NOTA)

Sequence Diagram menggambarkan pesan berurutan antar-objek perangkat lunak:
- **Aktor:** Kasir
- **Boundary UI:** `CashierApp.jsx` (React Frontend)
- **Middleware:** `Sanctum Auth & RBAC Middleware`
- **Controller:** `OrderController.php` (Laravel)
- **Database:** `MySQL (syaqil_laundry)`

### Urutan Pesan (Step-by-Step Message Flow):
1. **Kasir ➔ React UI:** Mengisi formulir transaksi (Pelanggan, Layanan, Berat, Diskon, Uang Diterima).
2. **React UI ➔ React UI:** Validasi lokal formulir dan hitung draft kembalian.
3. **Kasir ➔ React UI:** Menekan tombol `Proses Pembayaran`.
4. **React UI ➔ Sanctum Auth:** Mengirim HTTP POST `/api/orders` + Bearer/Cookie Session + Payload JSON.
5. **Sanctum Auth ➔ OrderController:** Validasi hak akses role `cashier` atau `owner` (Lolos verifikasi).
6. **OrderController ➔ Database:** Query cek idempotency `request_key` pada tabel `orders`.
7. **Database ➔ OrderController:** Record belum ada (Belum pernah tersimpan).
8. **OrderController ➔ Database:** `DB::beginTransaction()`.
9. **OrderController ➔ Database:** `INSERT INTO orders (...)` (Nomor nota unik di-generate).
10. **OrderController ➔ Database:** `INSERT INTO order_items (...)` (Item pesanan & subtotal per baris).
11. **OrderController ➔ Database:** `INSERT INTO status_history (...)` (Status: 'Diterima', user_id kasir).
12. **OrderController ➔ Database:** `DB::commit()`.
13. **OrderController ➔ React UI:** Response HTTP 201 JSON `{ ok: true, order: { ... } }`.
14. **React UI ➔ React UI:** Buka komponen Modal Preview Struk Nota 80mm.
15. **React UI ➔ Kasir:** Tampilkan dialog cetak browser (`window.print()`).
16. **Kasir ➔ React UI:** Dialog cetak selesai, formulir kasir di-reset siap untuk antrean berikutnya.

---

## 7. STRUKTUR DATA & ENTITAS (UNTUK ERD / CLASS DIAGRAM)

Berikut adalah relasi entitas inti yang ada di dalam database `syaqil_laundry`:

### 1. `users`
Menyimpan data akun petugas kasir dan owner.
- `id` (PK, BigInt)
- `name` (Varchar 100)
- `username` (Varchar 60, Unique)
- `password_hash` (Varchar 255)
- `role` (Enum: 'cashier', 'owner')
- `phone` (Varchar 20)
- `shift` (Varchar 30: 'Pagi', 'Sore', 'Full')
- `active` (Boolean)

### 2. `customers`
Menyimpan master data pelanggan laundry.
- `id` (PK, BigInt)
- `name` (Varchar 100)
- `phone` (Varchar 20, Unique)
- `address` (Varchar 300)
- `notes` (Varchar 1000)

### 3. `services`
Menyimpan master jenis layanan laundry dan tarifnya.
- `id` (PK, BigInt)
- `name` (Varchar 80)
- `price` (Unsigned Integer)
- `unit` (Varchar 16: 'kg', 'pcs')
- `duration_hours` (Unsigned Integer, estimasi pengerjaan)
- `active` (Boolean)

### 4. `orders`
Menyimpan transaksi nota pesanan laundry.
- `id` (PK, BigInt)
- `number` (Varchar 40, Unique) — Nomor nota resmi
- `request_key` (Varchar 80, Unique) — Kunci idempotency
- `customer_id` (FK -> `customers.id`)
- `cashier_id` (FK -> `users.id`)
- `subtotal` (Unsigned Integer)
- `discount_percent` (Decimal 5,2)
- `discount` (Unsigned Integer)
- `total` (Unsigned Integer)
- `method` (Enum: 'cash', 'qris', 'transfer')
- `paid` (Unsigned Integer)
- `change_amount` (Unsigned Integer)
- `status` (Enum: 'Diterima', 'Diproses', 'Selesai', 'Diambil')
- `due_at` (DateTime) — Estimasi selesai
- `created_at` (DateTime)

### 5. `order_items`
Menyimpan rincian baris layanan yang dipesan per nota.
- `id` (PK, BigInt)
- `order_id` (FK -> `orders.id`)
- `service_id` (FK -> `services.id`)
- `service_name` (Varchar 80) — Salinan nama layanan
- `price` (Unsigned Integer) — Salinan harga saat transaksi dibuat
- `quantity` (Decimal 6,2) — Berat dalam kg / jumlah unit
- `subtotal` (Unsigned Integer)

### 6. `status_history`
Menyimpan rekam jejak linimasa status pengerjaan cucian.
- `id` (PK, BigInt)
- `order_id` (FK -> `orders.id`)
- `status` (Varchar 20)
- `user_id` (FK -> `users.id`)
- `created_at` (DateTime)

### 7. `expenses`
Menyimpan catatan pengeluaran kas operasional oleh Owner.
- `id` (PK, BigInt)
- `user_id` (FK -> `users.id`)
- `date` (Date)
- `category` (Varchar 50: Operasional, Karyawan, Peralatan, Lainnya)
- `description` (Varchar 255)
- `amount` (Unsigned Integer)
- `method` (Varchar 30)
- `actor` (Varchar 100)

### 8. `price_histories`
Audit log perubahan tarif layanan laundry.
- `id` (PK, BigInt)
- `service_id` (FK -> `services.id`)
- `old_price` (Unsigned Integer)
- `new_price` (Unsigned Integer)
- `actor` (Varchar 100)
- `reason` (Varchar 255)

### 9. `order_corrections`
Audit log ketika Owner membatalkan atau mengoreksi pesanan.
- `id` (PK, BigInt)
- `order_id` (FK -> `orders.id`)
- `user_id` (FK -> `users.id`)
- `type` (Varchar 50: Pembatalan, Koreksi Berat, Penyesuaian Harga)
- `reason` (Varchar 255)
- `old_values` (JSON)
- `new_values` (JSON)

### 10. `whatsapp_logs`
Catatan antrean & status pengiriman notifikasi WhatsApp ke pelanggan.
- `id` (PK, BigInt)
- `order_id` (FK -> `orders.id`)
- `phone` (Varchar 25)
- `type` (Varchar 40: receipt, status_ready)
- `status` (Varchar 30: pending, sent, failed)

---

## 8. HUBUNGAN DENGAN BERKAS DIAGRAM (.DRAWIO) DALAM PROYEK

Berkas diagram siap pakai telah tersedia di folder `docs/diagrams/` yang dapat dibuka langsung di Draw.io / diagrams.net / ekstensi Draw.io VSCode:

1. **`use_case_syaqil_laundry.drawio`**
   - Berisi diagram Use Case lengkap yang memetakan interaksi Aktor (Kasir, Owner, Administrator, Pelanggan) dengan sistem.
2. **`flowchart_transaksi_kasir.drawio`**
   - Berisi diagram alir (Flowchart) 3 swimlane proses transaksi penerimaan cucian oleh kasir.
3. **`activity_diagram_transaksi.drawio`**
   - Berisi peta aktivitas CRUD lengkap, percabangan pemilihan menu, penghitungan pembayaran, dan update status.
4. **`sequence_diagram_transaksi.drawio`**
   - Berisi urutan interaksi objek (Kasir -> React Frontend -> Sanctum Auth -> Laravel OrderController -> MySQL Database).
5. **`syaqil_laundry_semua_diagram.drawio`**
   - Berisi kumpulan gabungan seluruh lembar diagram (multi-page draw.io).

---
*Dokumen ini disusun untuk melengkapi pelaporan perancangan sistem, dokumen skripsi/tugas akhir, dan referensi pemodelan UML rekayasa perangkat lunak Syaqil Laundry.*
