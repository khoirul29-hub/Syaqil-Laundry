# Pemeriksaan penyegaran halaman, 5 Oktober 2026

Lingkup: komponen Dashboard, Pelanggan, Pesanan, dan Riwayat Nota yang ditambahkan pada pembaruan ini.

- PASS / Fungsi: keempat kartu tahapan menampilkan baris Diterima (1), Diproses (1), Siap diambil (2), Diambil (1) sesuai database demo. Tombol semua mengembalikan daftar lengkap.
- PASS / Pelanggan: filter pelanggan kembali menampilkan Andi (2 transaksi); filter belum bertransaksi menampilkan keadaan kosong yang jelas. Sorotan pelanggan membuka detail melalui Enter dan dapat ditutup melalui Escape.
- PASS / Arsip: hari ini menampilkan 0 nota, tujuh hari menampilkan 5 nota; filter QRIS/transfer menampilkan 0, tunai/semua menampilkan 5. Pencarian Andi menghasilkan 2 nota dengan nilai total Rp78.000.
- PASS / Tindakan: panel siap diambil membuka pesanan terpilih, detail nota terbaru terbuka, dan tombol cetak nota terbaru mengisi area cetak dengan SYQ-20261004-0005. Printer fisik tidak termasuk pengujian.
- PASS / Data: statistik memakai data API, tanpa angka buatan. Tes tanggal Jakarta, batas tujuh hari/bulan, pengecualian masa depan, data kosong, dan kunjungan pelanggan lulus melalui tests/insights.mjs.
- PASS / Responsif: empat halaman diperiksa pada lebar CSS 389 px tanpa pelebaran halaman; kartu tahapan menjadi dua kolom, grafik/panel dan nota menjadi satu kolom, tabel tetap memiliki scroll internal. Arsip juga diperiksa pada 1.100 px dan tampilan desktop pada 1.910 px.
- PASS / Akses: kontrol memakai button/select/input berlabel, status pilihan aria-pressed, data grafik mempunyai label tanggal dan jumlah; fokus keyboard dan Escape diuji. Warna teks inti diuji minimal 4,5:1 dan batang grafik minimal 3:1 terhadap latarnya.
- PASS / Identitas: ENERGY 3 / RHYTHM 2 / MOTION 1; warna biru dan lime, huruf poster, inisial pelanggan asli, serta motif kertas nota mengikuti arah referensi. Alasan komposisi, warna, dan ikon dicatat di page-refresh.md.
- PASS / Integritas: tidak menambah aset gambar buatan, navigasi palsu, testimoni, atau klaim statistik. Keadaan kosong, loading, error, tema terang sesuai referensi, dan reduced motion tetap didukung.
- PASS / Kode: komponen dan stylesheet ditulis langsung dalam source. Build Vite lulus; tidak ada error/warning JavaScript pada sesi uji komponen baru.
