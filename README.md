# eArsip Suite v1.0

Starter kit aplikasi arsip digital dengan arsitektur seperti IAS ERP:

- Backend Laravel 13 + Laravel Sanctum
- API JSON dengan Bearer Token
- Flutter Android dengan penyimpanan token aman
- Dokumen tersimpan secara privat, bukan URL publik
- Satu database MySQL untuk banyak unit kerja
- Arsip masuk, arsip keluar, arsip umum, disposisi, peminjaman, lokasi fisik, QR, retensi, dan audit log
- Alamat produksi: `https://arsip.ias4u.my.id`

## Struktur paket

```text
backend-overlay/     File khusus eArsip yang ditimpa ke Laravel baru
mobile/              Source Flutter eArsip
mobile-overlay/      Manifest Android dan konfigurasi tambahan
scripts/             Generator backend dan mobile
deploy/              Panduan Domainesia, DNS, dan cron
```

## Buat backend lengkap

```bash
chmod +x scripts/build-backend.sh
./scripts/build-backend.sh e-arsip-backend
```

Upload hasilnya ke:

```text
/home/iasumyid/arsip.ias4u.my.id
```

Document Root:

```text
/home/iasumyid/arsip.ias4u.my.id/public
```

## Buat aplikasi Android

```bash
chmod +x scripts/build-mobile.sh
./scripts/build-mobile.sh e_arsip_mobile
cd e_arsip_mobile
flutter build apk --release
```

APK berada di:

```text
build/app/outputs/flutter-apk/app-release.apk
```

## Login API

```text
POST https://arsip.ias4u.my.id/api/mobile/v1/login
```

Respons token sengaja kompatibel dengan pola IAS ERP pada `token`, `access_token`, `data.token`, dan `data.access_token`.

## Catatan

Paket ini adalah source pilot. Ganti kredensial admin, database, dan APP_KEY sebelum digunakan. Aktifkan HTTPS dan backup database serta folder `storage/app/private` secara rutin.
