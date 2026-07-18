# SIYADI — Yayasan Dejiaohui Indonesia

Versi pilot ini mengikuti pola operasional aplikasi RESTO:

```text
Aplikasi web di hosting + database terpusat/terpisah + APK Android sebagai klien
```

## Arsitektur

- Satu source aplikasi pada hosting.
- Satu database pusat untuk daftar cabang dan akun nasional.
- Setiap cabang memakai file database SQLite tersendiri pada versi pilot.
- Satu APK Android membuka aplikasi melalui HTTPS.
- Data cabang tidak bercampur.
- Portal nasional dapat menambahkan cabang baru dan otomatis membuat database cabangnya.

## Modul pilot

Dashboard, anggota, donatur, donasi, penerima manfaat, program sosial, penyaluran bantuan, keuangan, relawan, pendidikan moral, kegiatan budaya, inventaris, dokumen, laporan cabang, portal nasional, dan manajemen cabang.

## Persyaratan hosting

- PHP 8.1 atau lebih baru.
- Ekstensi PDO SQLite dan SQLite3.
- HTTPS aktif.
- Document Root diarahkan ke folder `server/public`.

## Instalasi di Domainesia

1. Ekstrak ZIP ke:

```text
/home/iasumyid/yayasan.ias4u.my.id
```

2. Atur Document Root subdomain menjadi:

```text
/home/iasumyid/yayasan.ias4u.my.id/server/public
```

3. Buat folder dan permission dari Terminal cPanel:

```bash
cd /home/iasumyid/yayasan.ias4u.my.id
mkdir -p server/database/branches server/storage
chmod -R 775 server/database server/storage
```

4. Buka:

```text
https://yayasan.ias4u.my.id/install
```

5. Tekan **Pasang Data Pilot**.

## Akun pilot

### Cabang Surabaya

```text
Email    : admin.sby@dejiaohui.id
Password : Cabang123!
```

### Portal nasional

```text
URL      : https://yayasan.ias4u.my.id/national/login
Email    : admin@dejiaohui.id
Password : Admin123!
```

Ganti password sebelum pemakaian nyata.

## APK Android

Source Android tersedia di folder `android`. Alamat server sudah diarahkan ke:

```text
https://yayasan.ias4u.my.id
```

GitHub Actions pada branch ini membangun APK debug secara otomatis. APK hasil build tersedia pada menu **Actions → Build Dejiaohui APK → Artifacts**.

## Catatan pilot

- SQLite dipakai agar cabang pertama dapat diuji tanpa membuat banyak database MySQL.
- Pada produksi nasional, database cabang dapat dimigrasikan ke MySQL/MariaDB terpisah.
- Unggah berkas, kuitansi PDF, approval berlapis, sinkronisasi rekap nasional otomatis, dan notifikasi belum dimasukkan ke pilot ini.
- Password SMTP tidak disimpan di source code.
