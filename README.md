# SIYADI — Sistem Informasi Yayasan Dejiaohui Indonesia

Versi pilot untuk uji coba satu cabang dengan arsitektur:

- satu source code;
- database pusat terpisah;
- setiap cabang memakai database sendiri;
- portal cabang dan portal nasional;
- PWA yang dapat dipasang di layar utama Android;
- ekspor CSV dan audit log dasar.

## Modul pilot

Dashboard, anggota, donatur, donasi, penerima manfaat, program sosial, penyaluran bantuan, keuangan, relawan, pendidikan moral, kegiatan budaya, inventaris, dokumen, laporan, dan manajemen cabang nasional.

## Persyaratan

- PHP 8.1 atau lebih baru
- Ekstensi `pdo_sqlite` dan `sqlite3`
- Apache/LiteSpeed dengan `.htaccess`
- HTTPS untuk pemasangan PWA

## Instalasi cepat di cPanel/Domainesia

1. Upload seluruh isi ZIP ke folder subdomain, misalnya `/home/USERNAME/yayasan.ias4u.my.id`.
2. Pastikan PHP menggunakan versi 8.1+ dan ekstensi SQLite aktif.
3. Buka `https://SUBDOMAIN/install.php`.
4. Klik **Pasang Aplikasi Pilot**.
5. Setelah berhasil, buka halaman login.
6. Hapus atau ubah nama `install.php` setelah pengujian selesai.

Aplikasi otomatis membuat:

- `data/central.sqlite` untuk portal nasional;
- `data/branches/SBY.sqlite` untuk cabang Surabaya;
- data contoh untuk menguji alur aplikasi.

## Akun demo

### Cabang Surabaya

- URL: `/index.php?page=login`
- Email: `admin.sby@dejiaohui.id`
- Password: `Cabang123!`

### Portal nasional

- URL: `/index.php?page=national-login`
- Email: `admin@dejiaohui.id`
- Password: `Admin123!`

Ganti password demo sebelum digunakan untuk data nyata.

## Memilih cabang

Cabang pilot dapat dibuka dengan:

```text
/index.php?branch=SBY
```

Kode cabang disimpan dalam sesi. Portal nasional dapat menambahkan cabang baru dan menunjuk file database tersendiri. Pada tahap produksi, mekanisme ini akan disesuaikan ke database MySQL/MariaDB terpisah per cabang.

## Catatan status

Paket ini adalah MVP fungsional untuk memvalidasi alur operasional cabang. Fitur lanjutan yang belum termasuk: unggah dokumen/foto, persetujuan berlapis, kuitansi PDF, sinkronisasi rekap otomatis, notifikasi email/Telegram, backup terjadwal, dan pembungkus Android APK native.

Versi: `0.1.0-pilot`
