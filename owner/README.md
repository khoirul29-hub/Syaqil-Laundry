# Syaqil Laundry Owner: prototipe UI/UX

Prototipe **frontend UI/UX dengan data mock**, terpisah dari aplikasi kasir. Tidak ada backend, API, database, atau autentikasi Owner. Perubahan pelanggan dan aktivitas hanya bertahan selama sesi halaman; refresh mengembalikan fixture awal.

## Membuka preview Owner

Dari folder utama repo (yang berisi `package.json`):

```sh
npm ci
npm run owner:dev
```

Buka **http://127.0.0.1:5174/#login** di browser. Isi email/username dan kata sandi apa pun yang tidak kosong, lalu klik **Masuk**. Ini hanya simulasi login UI; tidak ada akun Owner atau autentikasi nyata. Biarkan server tetap berjalan selama preview dibuka.

Untuk build dan tes, jalankan secara terpisah dari folder utama repo:

```sh
npm run owner:build
node --test owner/tests/data.test.mjs owner/tests/management.test.mjs owner/tests/finance.test.mjs owner/tests/settings.test.mjs
```

- Dashboard: http://127.0.0.1:5174/#dashboard
- Data Pelanggan: http://127.0.0.1:5174/#pelanggan
- Riwayat Nota: http://127.0.0.1:5174/#riwayat-nota
- Karyawan: http://127.0.0.1:5174/#karyawan
- Layanan & Harga: http://127.0.0.1:5174/#layanan-harga
- Koreksi Transaksi: http://127.0.0.1:5174/#koreksi-transaksi
- Pengeluaran: http://127.0.0.1:5174/#pengeluaran
- Laporan & Analitik: http://127.0.0.1:5174/#laporan-keuangan (rute lama `#analytics` membuka tab Analitik)
- Pengaturan: http://127.0.0.1:5174/#pengaturan

Build disimpan di `dist-owner/`.

## Layar yang bisa dicoba

- **Dashboard**: periode ringkasan, empat KPI, status pesanan, koreksi yang menunggu keputusan, aktivitas pengeluaran/nota terbaru, ringkasan harian, dan shortcut. Grafik analisis mendalam berada di Laporan & Analitik.
- **Pelanggan**: kategori, pencarian nama/nomor/email/alamat, pengurutan, pagination, seleksi untuk ekspor Excel, detail dengan tiga tab, copy informasi, tambah/edit/hapus dan catatan. Impor CSV melalui file atau teks memiliki preview, validasi format, dan pemeriksaan nomor WhatsApp duplikat.
- **Riwayat Nota**: filter tanggal, metode, status, pencarian, pagination, pengurutan, preview struk HTML, dialog detail, ekspor PDF, area cetak 80 mm, dan aktivitas dalam sesi. PDF serta struk diberi label nota demo. Permintaan cetak tidak mengklaim printer fisik berhasil mencetak.

- **Karyawan**: filter role/status, pencarian, pagination, tambah/edit, aktif/nonaktif, simulasi reset password, dan log aktivitas. Username divalidasi tanpa duplikasi, nomor WhatsApp dinormalisasi, dan Owner aktif terakhir tidak dapat dinonaktifkan/demote. Tidak ada password atau akun login nyata yang dibuat.
- **Layanan & Harga**: kartu layanan, cari/filter/urutkan, tambah/edit, satuan, durasi jam, aktif/nonaktif, serta modal pengaturan harga dengan alasan dan histori. Nota contoh lama tidak berubah saat master harga diedit.
- **Koreksi Transaksi**: filter periode/jenis/status/kata kunci, seleksi dan ekspor Excel, panel detail, persetujuan/penolakan dengan catatan wajib, serta ringkasan total sebelum/sesudah. Satu koreksi yang diproses dapat disetujui atau ditolak; refresh mengembalikan fixture untuk mencoba cabang lain. Total efektif pembatalan yang telah disetujui adalah nol. Dataset koreksi berdiri sendiri pada fase UI/UX ini; belum mengubah laporan, nota, atau transaksi kasir nyata. Angka 124 adalah konteks transaksi demo pada referensi; jumlah koreksi/status dihitung dari delapan baris yang tersedia.

- **Pengeluaran**: periode, kategori, kata kunci, seleksi, pagination, ekspor Excel, tambah/edit/hapus, validasi tanggal/rupiah, grafik tahunan, dan komposisi biaya. Pada tablet/ponsel form menjadi drawer.
- **Laporan & Analitik**: satu menu dengan delapan tab Ringkasan/Analitik/Penjualan/Layanan/Pembayaran/Pelanggan/Karyawan/Pengeluaran. Tab Analitik berisi grafik harian/mingguan/bulanan, distribusi dan pendapatan layanan, pelanggan teraktif, serta heatmap jam sibuk. Tab Ringkasan menyajikan KPI dan tren 3/6/10 bulan; tab lain berisi rincian dan ekspor Excel sesuai periode. Perubahan biaya langsung memengaruhi total pengeluaran serta laba bersih dalam sesi.

- **Pengaturan**: enam tab Profil Usaha/Akun & Akses/Nota & Struk/Sistem/Notifikasi/Backup & Data. Edit profil dan logo, jadwal mingguan, preview nota langsung, format tanggal/jam/desimal, zona waktu, preferensi demo, serta unduh/pulihkan pengaturan JSON dengan validasi dan konfirmasi.

Sidebar mengikuti referensi pengguna. Rute Kasir dan Pesanan tetap bisa diakses dari menu profil atau pencarian, dengan placeholder fase berikutnya.

## Komponen

- `src/tokens.css`, `src/owner.css`, `src/ui.jsx`: fondasi dari Phase 00, termasuk tombol, input, kartu, tabel, badge, modal, drawer, empty/loading states.
- `src/OwnerApp.jsx`: shell responsif, pencarian lintas contoh data, navigasi, state sesi, dan feedback.
- `src/OwnerComponents.jsx`: banner, potongan artwork, kartu metrik, avatar, kategori, pencarian, panel, pagination.
- `src/OwnerDashboard.jsx`, `src/OwnerCustomers.jsx`, `src/OwnerReceipts.jsx`: tiga layar baru.
- `src/mockData.js`: fixture eksplisit dan validasi pelanggan/CSV. Snapshot Dashboard dan daftar contoh nota adalah dataset presentasi terpisah, bukan laporan keuangan yang telah direkonsiliasi.
- `src/screens.css`: komposisi sesuai referensi serta breakpoint desktop/tablet/ponsel.
- `src/OwnerEmployees.jsx`, `src/OwnerServices.jsx`, `src/OwnerCorrections.jsx`: layar manajemen.
- `src/ManagementUI.jsx`, `src/managementData.js`, `src/management.css`: komponen bersama, validasi/fixture dan komposisi manajemen.
- `src/OwnerExpenses.jsx`, `src/OwnerReports.jsx`: dua halaman keuangan; `src/OwnerAnalytics.jsx` menyediakan tab Analitik di halaman Laporan.
- `src/FinanceComponents.jsx`, `src/financeData.js`, `src/finance.css`: grafik HTML/SVG, periode, panel/tabel, agregasi, validasi dan fixture bersama.
- `src/OwnerSettings.jsx`, `src/settingsData.js`, `src/settings.css`: enam tab pengaturan, validasi profil/jadwal/backup, preview nota dan tata letak sesuai referensi.

Utilitas Excel dan PDF menggunakan helper frontend yang sudah ada. Tidak ada permintaan ke API kasir atau perubahan source backend.

## Arah visual dan sumber aset

ENERGY 3 / RHYTHM 2 / MOTION 1. Branding biru/lime dan kolase berasal dari gambar pengguna. Area transaksi menggunakan tipografi Inter, garis tabel tipis, dan fokus keyboard yang jelas. Warna tambahan pada metrik menyatakan kategori; tren biaya naik berwarna merah, pertumbuhan pendapatan berwarna hijau. Grafik memakai data contoh dan ringkasan status menjumlah tepat ke jumlah transaksi tiap periode.

Gambar sumber disalin tanpa modifikasi ke `public/assets/owner-*-reference.png` (1448 × 1086, kecuali koreksi 1536 × 1024). CSS menampilkan wilayah banner, sidebar bawah, dan ilustrasi pendukung; tabel, angka, formulir, grafik, serta struk merupakan elemen web. Ketajaman artwork tetap dibatasi resolusi sumber. Tidak ada klaim bahwa screenshot ini telah diubah menjadi aset HD baru.

Layar manajemen memakai ENERGY 3 / RHYTHM 2 / MOTION 1: kolase lime memberi identitas pada Karyawan/Layanan; area koreksi menggunakan kanvas biru pucat tanpa banner sesuai referensi ketiga. Tipografi dan komponen operasional diwarisi agar konsisten. Kartu membedakan layanan, badge menyatakan role/status, warna merah/amber menandai pembatalan atau penyesuaian. Animasi hanya untuk respons kontrol. Panel detail berubah menjadi drawer di bawah 1100 px untuk menjaga area tabel dan target sentuh.

## Verifikasi, 7 Oktober 2026

- Build produksi Owner berhasil. Lima tes Node lulus untuk konsistensi fixture, agregasi status/layanan, normalisasi dan duplikasi WhatsApp, CSV dengan koma/kutip/baris baru, serta penolakan CSV kosong/rusak.
- Browser: filter Member menampilkan 38 pelanggan; pagination berikutnya menampilkan baris 11–20. Form kosong menampilkan validasi. Tambah, edit, simpan catatan, batal hapus, dan hapus pelanggan demo berhasil.
- Import menolak nomor yang telah dipakai dan menerima satu baris valid setelah preview. Ekspor pilihan menghasilkan XLSX dengan header dan satu baris data.
- Kombinasi metode QRIS + Tersimpan menampilkan baris yang sesuai. Kata kunci tanpa hasil menampilkan empty state. Rentang 6 Oktober 2026 menampilkan 24 nota. Tanggal akhir sebelum awal menampilkan error yang dapat dibaca.
- PDF `NT-20261006-001.pdf` berhasil diunduh dan mempunyai header PDF valid. Dialog detail serta aktivitas dapat dibuka dan ditutup melalui Escape. Printer fisik tidak diuji.
- Pada ponsel, sidebar dapat dibuka dan detail pelanggan menjadi drawer. Escape pada dialog edit menutup dialog teratas sambil mempertahankan drawer, lalu Escape berikutnya menutup drawer.
- Tidak ada overflow horizontal halaman pada lebar CSS 388, 836, dan 1448 px. Tabel yang lebih lebar memakai scroll internal. Banner, grid Dashboard, panel detail pelanggan, tabel nota, dan preview struk diperiksa di browser.

## Gate desain

- Hard Gate PASS: data dilabeli demo; kontrol utama diuji; keadaan kosong, validasi, loading dan feedback tersedia. Kontrol kritis memiliki fokus keyboard. Kontras teks yang diperiksa 4,68:1 sampai 9,66:1; tombol hapus modal digelapkan setelah pengukuran awal.
- Purpose Gate PASS: aset dan komposisi mengikuti referensi pengguna, simbol sesuai fungsi, warna metrik membedakan informasi, bayangan struk menyatakan bentuk kertas.
- Liveliness PASS: dial 3/2/1, banner sebagai fokus, motif kolase laundry dan aksen lime konsisten; ruang tabel tetap tenang.
- Craftsmanship PASS: tiga komposisi berbeda memakai fondasi yang sama; responsif serta alur form diuji; bagian yang belum dibangun diberi placeholder. Klaim kesamaan pixel absolut dan integrasi data nyata tidak dibuat.

## Verifikasi tambahan layar manajemen, 7 Oktober 2026

- Sepuluh tes Node lulus: lima tes fondasi ditambah normalisasi/duplikasi username, perlindungan Owner aktif terakhir, batas harga/durasi, snapshot persetujuan, penolakan dan pembatalan. Build produksi Owner dan `git diff --check` berhasil.
- Karyawan: filter Kasir + Nonaktif menampilkan dua baris; kata kunci tanpa hasil membuka empty state; reset filter dan pagination ke baris 7–8 bekerja. Form kosong menampilkan tiga validasi, username DESI ditolak sebagai duplikat, nomor +62 dinormalisasi, tambah/edit akun demo berhasil. Nonaktifkan memperbarui badge dan ringkasan; simulasi reset masuk riwayat aktivitas. Menonaktifkan Owner terakhir menampilkan penolakan yang jelas. Tombol reset form memulihkan nilai tersimpan.
- Layanan: filter Sepatu tanpa data membuka empty state; reset mengembalikan tiga layanan. Harga 0 ditolak; tarif Reguler 6.000 menjadi 6.500 dengan alasan masuk riwayat. Durasi 0 ditolak, 8 jam tersimpan dan tampil pada kartu. Tambah layanan satuan pasang berhasil, nonaktifkan memfilter layanan sesuai status. Pengurutan nama, rincian tambahan, perubahan deskripsi/status, modal harga dan riwayat dapat digunakan.
- Koreksi: approve tanpa catatan ditolak; persetujuan mengubah total efektif dari 30.000 menjadi 50.000. Pada sesi fixture baru, penolakan mempertahankan 30.000 dan status Ditolak dapat difilter. Pembatalan yang disetujui menampilkan total 0. Tutup/buka detail, filter jenis, pencarian kosong, reset filter, periode harian dan rentang satu hari bekerja. Rentang terbalik menampilkan error; kontrol tanggal mendengarkan input agar perubahan langsung tercermin.
- Checkbox memilih satu nota; ekspor menghasilkan `Koreksi-Transaksi-Owner-Demo.xlsx` yang valid dengan header dan satu baris `SYQ-20261004-0004`. Ekspor tidak mengirim data ke layanan eksternal.
- Responsif: lebar CSS 388, 836, 1448 dan 1535 px diperiksa. Dokumen tidak melebar; tabel memiliki scroll internal. Sidebar ringkas 72 px pada tablet. Menu ponsel, drawer detail ketiga modul, dan dialog bertingkat bekerja. Escape menutup dialog teratas sebelum drawer.
- Pencarian global `ari123` menemukan Ari Pratama dan memfilter halaman Karyawan. Console browser tidak mencatat error/warning pada alur yang diuji.

Gate tambahan: **Hard Gate PASS** (data demo, validasi, keyboard, empty states, breakpoint dan alur di atas); **Purpose Gate PASS** (banner/kartu/panel mengikuti fungsi dan gambar pengguna); **Liveliness PASS** (dial 3/2/1, branding kolase, koreksi memakai area operasional bersih); **Craftsmanship PASS** (kontrol diuji, snapshot tidak dimutasi, state sesi terpisah dari kasir). Kontras delapan pasangan teks baru diukur antara 4,69:1 dan 6,83:1. Pengujian ini bukan pengujian autentikasi, API, akuntansi terpadu, atau browser produksi lintas perangkat.

## Layar keuangan — 9 Oktober 2026

Referensi: `codex-clipboard-4677bcd0-4468-45b0-b965-d6c855ba23c1.png`, `codex-clipboard-82b9c3f7-fbdb-4aba-ae95-60f575eb7a9b.png`, dan `codex-clipboard-b4d29d7c-bbd6-41b6-89b5-3b5c0b6797d9.png`. Komposisi memakai sidebar biru 245 px, workspace biru pucat, KPI ringkas, panel putih dan ikon biru. Grafik, teks, angka, tabel dan form adalah HTML/SVG sehingga tetap tajam saat ukuran layar berubah. Artwork handuk menggunakan aset referensi koreksi yang sudah tersedia.

Dataset keuangan adalah ledger demo frontend tersendiri untuk ketiga halaman ini, bukan data kasir/nota riil. Oktober memuat 186 transaksi dengan pendapatan Rp7.850.000. Sembilan baris biaya berjumlah Rp2.540.000 sehingga laba bersih Rp5.310.000. Enam baris pertama mengikuti gambar; tiga baris tambahan merekonsiliasi total KPI. Persentase dihitung dari data, bukan menyalin persentase yang tidak cocok di gambar. Ada 124 pelanggan terdaftar dan 95 pelanggan bertransaksi pada periode Oktober; kedua ukuran diberi label berbeda. `Lainnya` di grafik layanan menggabungkan Cuci + Setrika serta Setrika Saja, sementara tabel lima layanan memisahkannya. Jam/tanggal dan angka historis adalah fixture, bukan waktu atau transaksi nyata. Refresh mengembalikan semua perubahan sesi.

Verifikasi:

- Empat tes keuangan ditambahkan untuk rekonsiliasi layanan/pembayaran/hari/bulan; dampak tambah/edit/pindah tanggal biaya pada laba; tanggal mustahil/terbalik/periode kosong/batas inklusif; serta validasi rupiah/kategori/metode/deskripsi. Total 14 tes bersama fondasi/manajemen.
- Browser desktop 1535 px: tambah biaya Rp100.000 mengubah pengeluaran menjadi Rp2.640.000 dan laba di Laporan menjadi Rp5.210.000. Edit menjadi Rp150.000 memperbarui biaya menjadi Rp2.690.000; hapus data uji mengembalikan Rp2.540.000. Form kosong menampilkan empat pesan validasi.
- Filter Karyawan menghasilkan dua baris. Rentang tanggal terbalik ditolak; September menampilkan biaya Rp2.260.000. Analytics harian menampilkan 31 kelompok, mingguan lima kelompok. Januari 2025 menampilkan total pendapatan/transaksi nol dengan empty state.
- Seluruh tab laporan berpindah ke grafik dan tabel terkait. Ekspor Pengeluaran terpilih menghasilkan XLSX valid dengan header dan satu baris; ekspor tab Pengeluaran menghasilkan header dan sepuluh baris termasuk satu biaya uji. File ZIP/XML diperiksa, data numerik tetap numerik.
- Lebar CSS 388, 836 dan 1535 px diperiksa tanpa overflow horizontal dokumen/panel. Tabel memakai scroll internal. Drawer form tablet, menu ponsel, dialog periode, serta Escape untuk menutup dialog bekerja. Console tidak mencatat error/warning pada alur yang diuji.

Gate desain keuangan: **Hard Gate PASS** untuk validasi, filter, empty states, fokus keyboard, ekspor, responsif dan state demo yang diuji. **Purpose Gate PASS**: panel menunjukkan kategori/rekap yang sesuai; grafik berasal dari agregasi yang sama. **Liveliness PASS**: dial 3/2/1 untuk identitas aplikasi, dengan area keuangan tenang sesuai gambar; warna dipakai untuk kategori/tren. **Craftsmanship PASS**: tiga komposisi mengacu pada screenshot, tooltip/legenda dan angka tetap elemen web, form responsif dan ringkasan direkonsiliasi. Kontras teks tren hijau/merah 4,70:1 dan 4,98:1; warna amber kategori digelapkan setelah pengukuran awal. Belum ada autentikasi, backend, integrasi kasir, atau klaim kesamaan setiap piksel.

## Pengaturan Owner — 9 Oktober 2026

Referensi: `codex-clipboard-11f4eae4-61a4-4e45-bcb3-a3641a0ecf9c.png`. Profil Usaha mengikuti grid pada gambar: kartu identitas dua kolom, jam operasional dan preferensi di bawah, preview nota serta informasi sistem di kanan. Pada viewport desktop 1535 px, kartu identitas berada di x279/y254 dengan lebar 876 px; preview di x1170/y254 dengan lebar 343 px. Kartu preview setinggi 416 px, dua kartu bawah setinggi 348 px. Semua teks, ikon, form dan kertas nota merupakan HTML/SVG/CSS; logo memakai aset asli 1254 px.

Enam tab menggunakan state sesi bersama. Profil yang disimpan tetap tersedia saat berpindah menu, sementara preview mengikuti draft sebelum disimpan. Nota & Struk menyediakan ukuran 58/80 mm, awalan nomor, informasi yang ditampilkan dan pesan penutup. Sistem menyediakan format tanggal/jam/desimal, zona waktu dan contoh bunyi. Akun & Akses menampilkan contoh peran serta tautan ke Karyawan. Notifikasi memiliki preferensi dan preview template tanpa mengirim pesan.

Backup adalah file **pengaturan demo saja**, berisi profil/logo, tujuh hari operasional, format nota, serta preferensi. Tidak berisi akun, pelanggan atau transaksi. File JSON divalidasi sebelum dialog pemulihan; format/versi, jadwal, enum, template, nomor telepon, serta logo dibatasi. Logo harus PNG/JPG maksimal 2 MB dan berhasil dibaca sebagai gambar. Backup maksimal 3 MB. Status database ditulis **Mode demo**, ukuran data dihitung dari pengaturan sesi, dan tanggal backup hanya diisi setelah unduhan. Preferensi belum diterapkan ke aplikasi kasir atau nota historis. Refresh mengembalikan contoh awal; file backup dapat dipakai untuk memulihkan pengaturan.

Verifikasi:

- Build produksi Owner berhasil. Enam tes pengaturan ditambah ke 14 tes sebelumnya; total **20 tes lulus**. Cakupan tambahan: normalisasi telepon, field wajib, jadwal tutup/terbalik/rusak, variabel pesan, pembatasan logo, backup bolak-balik, JSON rusak/versi salah/ukuran berlebih, serta format waktu menurut zona.
- Browser: edit nama langsung mengubah preview, simpan dan pindah ke Analytics lalu kembali mempertahankan profil. Nama kosong, email salah, dan jam tutup 06:00 untuk jam buka 07:00 ditolak. Batalkan memulihkan nilai tersimpan; hari Minggu dapat diaktifkan.
- Nota: awalan TST dan kertas 58 mm tampil; sakelar nama pemilik/kasir menghapus informasi terkait dari preview. Sistem: ISO, 12 jam, WIT dan dua desimal menghasilkan `2026-10-04 · 12:30 PM` dan `Rp45.000,00`. Template pelanggan mengganti variabel pada preview dialog. Escape menutup dialog, tombol End memindahkan fokus tab terakhir, dan pencarian global menemukan Nota & Struk.
- Logo PNG berhasil dibaca dengan resolusi asli 1254 px; file JSON ditolak sebagai logo. Hapus dan batalkan logo bekerja. Unduhan `Syaqil-Pengaturan-Demo-2026-10-09.json` diperiksa sebagai JSON valid dengan lima bagian pengaturan; pemulihan melalui dialog berhasil. File JSON aplikasi lain ditolak. Reset memakai konfirmasi dan mengembalikan contoh awal.
- Layout diperiksa pada lebar CSS 1535, 836, dan 388 px. Tidak ada overflow horizontal dokumen/kartu; seluruh enam tab diperiksa pada ponsel. Sidebar tablet 72 px, form tablet minimal 44 px, menu ponsel dan Escape bekerja. Console tidak mencatat error/warning pada alur yang diuji.

Gate desain: **Hard Gate PASS** untuk fungsi, validasi, fokus, responsive dan label demo yang diperiksa; lima pasangan warna teks diukur **5,16:1–5,96:1**. **Purpose Gate PASS**: panel mengelompokkan identitas/jadwal/preferensi; preview menampilkan dampak perubahan. **Liveliness PASS**: identitas biru/lime diwarisi, pengaturan memakai workspace tenang sesuai gambar dengan motion minimal. **Craftsmanship PASS**: komposisi diukur terhadap referensi, logo asli dan elemen web menjaga ketajaman, layout ponsel tidak memotong kontrol. Ini tetap prototipe frontend, bukan backup database, autentikasi, atau pengiriman notifikasi nyata.

## Koreksi gambar sidebar — 10 Oktober 2026

Sidebar memakai crop dari referensi masing-masing: Dashboard, Pelanggan, Riwayat Nota, Karyawan, dan Layanan & Harga. Halaman Koreksi, Pengeluaran, Analytics, Laporan Keuangan, dan Pengaturan memakai kartu handuk dari referensi operasional. Riwayat Nota kini mempunyai crop sendiri, bukan gambar Pelanggan. `SidebarArt` memakai viewport SVG dan clip path sehingga gambar tetap proporsional saat tinggi dibatasi. Footer memperoleh ruang sendiri; stiker, senyum, label usaha, dan gelombang tidak lagi dipotong oleh area navigasi. Logo sidebar menampilkan bentuk aset asli 1254 px dalam warna putih, tanpa bingkai lingkaran.

Verifikasi browser: pemetaan dan batas gambar diperiksa pada sepuluh halaman; screenshot Dashboard, Karyawan, Riwayat Nota, dan Pengaturan dibandingkan dengan sumber. Pada 1448 × 1086, menu sampai Pengaturan dan gambar Riwayat Nota tampil penuh tanpa scroll navigasi. Pada 1367 × 769 gambar tetap utuh dan menu yang lebih panjang dapat digulir. Mode ringkas/perluas tablet 836 px serta drawer ponsel 388 px bekerja. Tidak ada error/warning console pada pemeriksaan ini. Build produksi Owner dan pemeriksaan whitespace lulus. Perubahan hanya pada presentasi sidebar Owner.

## Preview Nota dan kerapatan aktivitas — 10 Oktober 2026

Atas koreksi pengguna, area preview kini memakai panel dokumen bersih dengan latar netral, identitas usaha, metadata nota, rincian layanan, total, metode pembayaran, jumlah dibayar, dan kembalian. Kolase di belakang kertas serta banner promosi bawah dihapus. Tombol PDF dan cetak berdampingan dengan tinggi 44 px; tautan detail terpisah di bawah. Komponen `ReceiptPaper` digunakan bersama oleh preview, dialog detail, dan area cetak. Generator PDF tetap memakai template unduhan yang sudah ada.

Tabel dan aktivitas ditempatkan dalam satu kolom dengan jarak 12 px, sehingga tinggi preview tidak lagi menciptakan celah vertikal di antara keduanya. Tinggi panel aktivitas mengikuti isi; teks aktivitas diperbesar. Aturan CSS nota lama yang tidak dipakai dihapus.

Verifikasi: pemilihan nota Siska Melani memperbarui dokumen menjadi Rp30.000, QRIS, dan kembalian Rp0; detail memuat data yang sama, Escape menutup dialog, dan unduhan PDF menampilkan konfirmasi serta aktivitas download. Pencarian tanpa hasil menampilkan empty state; Reset Filter memulihkan nota. Lebar desktop 1448 px, tablet 836 px, dan ponsel 388 px diperiksa tanpa overflow dokumen, kertas, atau tombol. Jarak tabel–aktivitas terukur 12 px pada desktop. Teks sekunder dokumen memiliki kontras minimal 4,67:1 terhadap putih. Perubahan tetap frontend demo.

## Navigasi dan ruang kerja yang disederhanakan — 10 Oktober 2026

Menu Analytics dan Laporan Keuangan disatukan menjadi **Laporan & Analitik**. Tab Analitik memuat tren pendapatan, distribusi layanan, pendapatan per layanan, jam sibuk, dan pelanggan teraktif. URL lama `#analytics` tetap membuka tab Analitik pada halaman gabungan. Dashboard berfokus pada ringkasan usaha, status pesanan, aktivitas operasional terbaru, dan aksi cepat; grafik serta daftar layanan berulang dipindahkan dari Dashboard.

Kartu ilustrasi promosi pada Karyawan, Layanan & Harga, Dashboard, serta gambar stiker di kaki sidebar tidak lagi ditampilkan. Ruang bekas kartu Karyawan dan Layanan dipakai untuk riwayat aktivitas/perubahan harga yang lebih lebar; ruang Dashboard dipakai untuk aktivitas operasional. Logo dan banner judul halaman tetap menjadi identitas aplikasi. Catatan koreksi gambar sidebar di atas menggambarkan versi sebelumnya, sebelum penyederhanaan ini.

## Login Owner (prototipe) — 10 Oktober 2026

Halaman `#login` mengikuti referensi dua panel: identitas biru bergelombang di kiri dan formulir putih di kanan. Ruang di sekitar formulir diberi ilustrasi gelombang biru kedua agar kontras tetap terasa saat layar lebar. Tersedia isian email/username dan kata sandi, lihat kata sandi, Ingat saya, informasi lupa kata sandi, dan tombol keluar dari menu profil.

Ini **simulasi UI**, belum autentikasi. Isian apa pun yang tidak kosong membuka Dashboard demo. Kata sandi tidak disimpan; hanya penanda sesi demo yang disimpan di sesi browser, atau di penyimpanan lokal saat Ingat saya dicentang. Jangan gunakan alur ini untuk melindungi data nyata. Backend, verifikasi akun, dan pemulihan kata sandi masih di luar fase frontend ini.
