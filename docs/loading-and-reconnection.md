# Loading screen dan pemulihan koneksi

## Desain

Mengikuti dua gambar referensi pengguna tanggal 6 Oktober 2026: bingkai ungu, panel putih, ilustrasi laundry di tepi, logo dan kontrol di tengah. Ilustrasi menggunakan PNG asli pengguna dengan pemotongan CSS, bukan gambar ulang. Logo, judul, ikon Wi-Fi, progress, tahapan, dan tombol adalah elemen HTML/SVG agar tetap tajam. Pada ponsel, dekorasi diperkecil dan tombol ditumpuk.

Panduan visual: ENERGY 3 / RHYTHM 2 / MOTION 1. Warna dan ilustrasi mengikuti referensi; animasi hanya menandakan pekerjaan yang sedang berjalan. Preferensi reduced motion dihormati. Fokus layar adalah logo saat loading dan status jaringan saat pemulihan.

## Perilaku

- HTML awal langsung menampilkan splash sebelum JavaScript selesai dimuat.
- Progress menunjukkan tahap persiapan (20% pemeriksaan sesi, 65% pemuatan data), bukan persentase byte unduhan. Sesuai permintaan pengguna, loading tampil minimal 1,5 detik termasuk 300 ms pada 100% sebelum halaman dibuka. Jika data lebih lambat, loading menunggu data selesai; sambung ulang dan kesalahan tidak diberi jeda tambahan.
- Gangguan jaringan, timeout 15 detik, respons rusak, atau HTTP 5xx memunculkan pemulihan. Kesalahan validasi 4xx tetap ditampilkan di formulir.
- Event offline/online ditangani langsung. Server diperiksa setiap 30 detik dengan GET /session saat aplikasi siap.
- Pemulihan hanya membaca /session dan /bootstrap. Jeda percobaan 2, 5, 10, 20, lalu 30 detik; permintaan bersamaan digabung.
- Data yang sudah dimuat dan komponen halaman dipertahankan saat terputus, disembunyikan dan tidak dapat difokuskan. Draft kasir tetap memakai penyimpanan lokal yang sudah ada.
- Tidak ada pengiriman ulang otomatis untuk pembayaran atau perubahan data. Pembayaran yang hasilnya belum diketahui tetap menggunakan request key yang sama ketika kasir mencoba ulang.
- Coba Lagi memeriksa koneksi saat itu juga. Kembali ke Dashboard memeriksa dan memuat data dahulu, lalu membuka dashboard setelah berhasil.
- Sesi yang berakhir mengembalikan pengguna ke login. Layar ini bukan dukungan menjalankan transaksi secara offline atau membuka aplikasi pertama kali tanpa jaringan.

## Implementasi dan verifikasi

1. `src/lib.js`: klasifikasi gangguan dan batas waktu permintaan; diuji dalam `tests/connection.mjs`.
2. `src/connectionController.js`: retry, heartbeat, commit data utuh, deduplikasi, cleanup; diuji dalam `tests/reconnection.mjs`.
3. `src/useConnection.js` dan `src/App.jsx`: event browser dan integrasi state.
4. `src/ConnectionScreen.jsx`, `public/connection.css`, `index.html`: tampilan serta fallback HTML awal.

Jalankan `node tests/connection.mjs`, `node tests/reconnection.mjs`, `npm test`, dan `npm run build`.

Hasil browser: gangguan API HTTP 503 menampilkan layar pemulihan; tombol Coba Lagi dan Kembali ke Dashboard memeriksa ulang server. Saat server kembali normal, aplikasi memulihkan halaman otomatis. Berat 7,25 kg, catatan, dan diskon 12,5% tetap sama sebelum dan sesudah gangguan. Pengujian memakai server khusus port 8082 tanpa menulis transaksi. Lebar dokumen ponsel 389 px sama dengan viewport, tanpa overflow horizontal. Loading 20% terlihat saat pemeriksaan sesi sengaja diperlambat.

Pemeriksaan visual: PASS identitas sesuai referensi dan gambar asli; PASS teks status/tombol berupa HTML; PASS tombol, fallback loading, error, dan retry aktif; PASS fokus keyboard dan reduced motion tersedia; PASS draft bertahan dalam simulasi; PASS build dan tes pemulihan. Huruf tulisan tangan menggunakan Caveat lokal, bukan gambar teks.
