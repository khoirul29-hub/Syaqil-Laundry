# Backend Syaqil Laundry (Laravel 10 + Sanctum)

Backend resmi terpadu untuk sistem **LaundryFlow – Syaqil Laundry Ekspres**, melayani modul **Kasir** (`http://127.0.0.1:5173`) dan modul **Owner** (`http://127.0.0.1:5174`) dengan satu basis data MySQL (`syaqil_laundry`).

---

## 🚀 Arsitektur & Modul

1. **Modul 1: Fondasi Arsitektur & Database**
   - Framework: **Laravel 10** + PHP 8.1 / 8.2+.
   - Database: MySQL 5.7 / 8.0 (`syaqil_laundry`).
   - Tabel: `users`, `customers`, `services`, `orders`, `order_items`, `status_history`, `expenses`, `price_histories`, `order_corrections`, `account_activities`, `business_settings`, `whatsapp_logs`, `notifications`.

2. **Modul 2: Autentikasi & Role-Based Access Control (RBAC)**
   - Autentikasi stateful berbasis session Laravel Sanctum dengan proteksi CSRF.
   - Pembatasan tingkat percobaan (Rate Limiting 5x per menit) untuk keamanan login.
   - Middleware `role:cashier,owner` memisahkan hak akses kasir dan owner secara ketat.

3. **Modul 3: Operasional Kasir & Perhitungan Otoritatif**
   - Seluruh kalkulasi subtotal, diskon (integer sen/basis poin), total, dan kembalian dihitung secara otoritatif oleh backend (`OrderCalculationService`).
   - Mencegah manipulasi harga di sisi klien (`409 Conflict`).
   - Penomoran nota berurutan: `SYQ-YYYYMMDD-XXXX`.
   - Transisi status berurutan: `Diterima` ➔ `Diproses` ➔ `Selesai` ➔ `Diambil`.

4. **Modul 4: Manajemen Owner, Pengeluaran & Laporan Keuangan**
   - Agregasi real-time dari database: pendapatan kotor, pengeluaran, laba bersih, filter periode (Hari ini, 7 hari, 30 hari, kustom).
   - CRUD Pengeluaran operasional (`expenses`).
   - Manajemen Layanan & Tarif dengan riwayat perubahan otomatis ke `price_histories`.
   - Manajemen Karyawan (tambah kasir baru, reset kata sandi, blokir penonaktifan owner tunggal).
   - Audit trail koreksi transaksi (`order_corrections`).

5. **Modul 5: Resiliensi WhatsApp & Integrasi Struk Nota**
   - Gateway WhatsApp non-blocking: order dan perubahan status tidak pernah gagal walau gateway down/mati.
   - Status log jujur: `sent`, `not_configured`, atau `failed`.
   - Otomatis menghasilkan URL cadangan `wa.me` langsung ke pelanggan.
   - Endpoint nota struk kasir terstruktur (`GET /api/orders/{id}/receipt`) siap cetak ke printer thermal 58mm/80mm dan PDF.
   - Pemicu otomatis notifikasi WhatsApp siap diambil saat status berubah ke `Selesai`.

---

6. **Modul 6: Verifikasi Pembayaran & Uji End-to-End**
   - Validasi ketat non-tunai di backend: QRIS dan Transfer Bank memerlukan konfigurasi merchant serta konfirmasi kasir.
   - Penanganan aman `discount_percent` null/omitted.
   - Pengujian siklus transaksi penuh dari order ➔ status ➔ struk nota ➔ laporan owner real-time.

---

## 🔑 Kredensial Pengguna Awal

| Peran | Username | Kata Sandi | Hak Akses |
|---|---|---|---|
| **Owner** | `owner` | `OwnerSyaqil!2026` | Penuh (Dashboard Owner, Laporan, Tarif, Kasir, Pengeluaran) |
| **Kasir** | `kasir` | `KasirSyaqil!2026` | Operasional Kasir (Transaksi, Order, Pelanggan, Nota, Struk) |

---

## 🧪 Menjalankan Test Suite (100% Pass / 91 Pengujian)

Jalankan pengujian terisolasi untuk tiap modul dari folder `backend/`:

```sh
# Modul 2: Autentikasi & RBAC (12 Pengujian)
php test_auth_rbac.php

# Modul 3: Operasional Kasir & Kalkulasi Backend (18 Pengujian)
php test_cashier_orders.php

# Modul 4: Manajemen Owner & Laporan Keuangan (19 Pengujian)
php test_owner_management.php

# Modul 5: Resiliensi WhatsApp & Integrasi Struk (27 Pengujian)
php test_whatsapp_receipts.php

# Modul 6: Verifikasi Pembayaran & Uji End-to-End (15 Pengujian)
php test_payments_e2e.php
```

---

## 🛠️ Menjalankan Server Lokal

```sh
# Jalankan MySQL (Laragon atau daemon mandiri)
# Jalankan backend Laravel
php artisan serve --host=127.0.0.1 --port=8000
```
