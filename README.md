# IAS Marketplace Suite v1.0

Starter kit marketplace umum multi-vendor yang menghubungkan pembeli, penjual, dan kurir dengan pola autentikasi seperti IAS ERP.

## Arsitektur

```text
Buyer App ───────┐
Merchant App ────┼── Laravel REST API + Sanctum ── MySQL
Courier App ─────┘                 │
                                   ├── Produk & toko
                                   ├── Order workflow
                                   ├── Ongkir pilot
                                   ├── Pembayaran pilot
                                   └── Audit transaksi
```

Domain default:

```text
https://market.ias4u.my.id
```

## Isi paket

```text
backend-overlay/    Modul Laravel marketplace
mobile-shared/      Core Flutter bersama
buyer-app/          Halaman khusus pembeli
merchant-app/       Halaman khusus penjual
courier-app/        Halaman khusus kurir
scripts/            Generator backend dan 3 aplikasi Flutter
deploy/             Panduan hosting, build APK, dan DNS
postman/             Koleksi endpoint pilot
```

## Fitur pilot

- Login email/username dan Bearer Token Laravel Sanctum
- Respons token kompatibel dengan pola IAS ERP
- Role: admin, buyer, merchant, courier
- Toko multi-vendor
- Katalog produk umum
- Keranjang dan pembuatan order
- Alur merchant: terima, siapkan, siap diambil
- Alur kurir: klaim, ambil, antar, selesai
- Riwayat order pembeli
- Stok produk
- Ongkir dan biaya layanan pilot
- COD dan pembayaran non-tunai berstatus manual
- Tiga source aplikasi Flutter terpisah

## Buat backend

```bash
chmod +x scripts/build-backend.sh
./scripts/build-backend.sh ias-marketplace-backend
```

Upload ke:

```text
/home/iasumyid/market.ias4u.my.id
```

Document Root:

```text
/home/iasumyid/market.ias4u.my.id/public
```

## Buat tiga aplikasi Android

```bash
chmod +x scripts/build-mobile-apps.sh
./scripts/build-mobile-apps.sh
```

Hasil proyek:

```text
build-mobile/ias_market_buyer
build-mobile/ias_market_merchant
build-mobile/ias_market_courier
```

Build APK:

```bash
cd build-mobile/ias_market_buyer && flutter build apk --release
cd ../ias_market_merchant && flutter build apk --release
cd ../ias_market_courier && flutter build apk --release
```

## Login API

```text
POST https://market.ias4u.my.id/api/mobile/v1/login
```

Token tersedia pada `token`, `access_token`, `data.token`, dan `data.access_token`.

> Paket ini adalah source pilot. Payment gateway, Google Maps, push notification, verifikasi identitas, dan tracking GPS realtime perlu kredensial layanan masing-masing sebelum produksi.
