# Pesanan kasir: pembaruan referensi

## Lingkup dan arah visual

Halaman `#pesanan` mengikuti gambar pengguna `codex-clipboard-24d7f65f-69f6-4618-8e9a-0682e2b2982f.png`. Banner lime, tab status, tabel lebar, ringkasan empat status, panduan tahapan dan ilustrasi kanan mengikuti komposisi tersebut. ENERGY 3 / RHYTHM 2 / MOTION 1: kolase ekspresif pada branding; ruang transaksi tenang tanpa animasi dekoratif.

Komponen `CashierOrders` menerima data serta callback detail, cetak dan navigasi yang sudah digunakan aplikasi. Riwayat Nota tetap memakai komponen sebelumnya. CSS perubahan shell hanya aktif ketika halaman Pesanan kasir terbuka; direktori Owner tidak diubah.

## Aset dan data

- `public/assets/orders-layout-reference.png` adalah salinan gambar pengguna tanpa perubahan. CSS menampilkan area banner (x259, y105, 1150 × 189) dan ilustrasi kanan (x1098, y820, 312 × 219). Resolusi sumber 1448 × 1086; tidak diklaim sebagai aset HD baru.
- Tabel, tab, filter, tombol dan ringkasan adalah elemen HTML/React dengan data aplikasi. Gambar referensi tidak digunakan untuk menggantikan kontrol tersebut.
- Ringkasan default memakai periode Semua agar jumlah enam pesanan sesuai dengan data lintas tanggal. Pengguna dapat memilih Hari Ini atau 7 Hari. Grafik mini memakai aktivitas pesanan pada periode tersebut.
- Warna status membedakan tahapan; ikon dokumen, paket, jam, centang, pencarian dan printer menjelaskan aksi atau status. Gradien tipis pada ringkasan mengikuti warna status. Bayangan menu menunjukkan lapisan interaksi. Inter menjaga keterbacaan; Anton mengikuti angka tebal pada referensi.

## Bukti verifikasi, 6–7 Oktober 2026

- Build produksi Vite berhasil setelah perbaikan input tanggal; `git diff --check` bersih.
- Enam pesanan tersimpan tampil dengan jumlah status 1 Diterima, 1 Diproses, 2 Selesai dan 2 Diambil.
- Filter Selesai menampilkan dua pesanan; pencarian tanpa kecocokan menampilkan keadaan kosong. Urutan terbaru/terlama berfungsi.
- Rentang 4 Oktober 2026 sampai 4 Oktober 2026 menampilkan lima pesanan. Tanggal tetap tersimpan setelah pengurutan. Rentang terbalik menampilkan validasi dan tidak menampilkan hasil yang keliru.
- Menu opsi membuka detail. Escape menutup menu dan mengembalikan fokus ke tombol asal. Tambah Pesanan menuju formulir kasir dan mempertahankan draft.
- Ekspor Excel menghasilkan tujuh baris termasuk header; PDF nota `SYQ-20261006-0006` terunduh dengan format PDF yang valid. Tombol cetak tetap menggunakan callback cetak yang sudah ada; printer fisik tidak diuji ulang pada perubahan visual ini.
- Navigasi Riwayat Nota lalu kembali ke Pesanan berhasil. Konsol browser tidak berisi error atau warning pada pemeriksaan akhir.
- Lebar CSS 389, 836 dan 1448 px diperiksa tanpa overflow dokumen. Tabel bergeser di wadahnya pada layar sempit; ringkasan tersusun ulang. Pemeriksaan akhir pada 1910 px juga tidak menunjukkan overflow horizontal.

## Gate desain

- Hard Gate PASS: kontrol tersambung ke aksi, angka berasal dari pesanan, keadaan kosong/error/loading tersedia, fokus terlihat, dan viewport kecil diperiksa. Kontras teks status Selesai diperbaiki; kombinasi teks operasional yang diperiksa memenuhi 4,5:1.
- Purpose Gate PASS: branding dan komposisi mengikuti referensi eksplisit; warna kartu, ikon, font dan bayangan memiliki tujuan yang dicatat di atas.
- Liveliness PASS: dial 3/2/1 diterapkan melalui banner sebagai fokus, motif kolase laundry, aksen lime dan pemisahan tabel dari ringkasan.
- Craftsmanship PASS: layout responsif dan rentang tanggal diperiksa langsung; navigasi/detail/ekspor tetap berfungsi; tidak ada angka statistik baru yang dibuat untuk meniru gambar.
