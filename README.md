# WA-BOT Suite v1.0

Starter kit WA-BOT dengan pola autentikasi mobile yang sama seperti IAS ERP:

- Laravel 13 + Sanctum pada `https://wa.ias4u.my.id`
- Flutter Android memakai Bearer token
- Gateway WhatsApp Web tidak resmi berbasis WPPConnect di VPS
- Kontrak login kompatibel: `token`, `access_token`, `data.token`, dan `data.access_token`
- QR/linked-device, status perangkat, kirim pesan, webhook pesan masuk, dashboard, kontak, kampanye, dan audit dasar

## Struktur

```text
backend-overlay/   File khusus WA-BOT yang ditimpa ke Laravel baru
gateway/           Node.js + WPPConnect untuk VPS
mobile/            Source Flutter Android
scripts/           Generator backend dan mobile
deploy/            Panduan Domainesia, VPS, DNS dan SSL
```

## Instalasi ringkas

### Backend Laravel

```bash
chmod +x scripts/build-backend.sh
./scripts/build-backend.sh wa-bot-backend
```

Upload folder hasil `wa-bot-backend` ke:

```text
/home/iasumyid/wa.ias4u.my.id
```

Document Root:

```text
/home/iasumyid/wa.ias4u.my.id/public
```

### Gateway VPS

```bash
cd gateway
cp .env.example .env
npm install
npm run start
```

### Mobile Flutter

```bash
chmod +x scripts/build-mobile.sh
./scripts/build-mobile.sh wa_bot_mobile
```

APK debug:

```bash
cd wa_bot_mobile
flutter build apk --debug
```

## Endpoint login

```text
POST https://wa.ias4u.my.id/api/mobile/v1/login
```

Request:

```json
{
  "login": "admin@ias4u.my.id",
  "password": "password",
  "device_name": "WA-BOT Android"
}
```

Respons mengirim token di posisi top-level dan nested agar kompatibel dengan parser IAS ERP.

## Peringatan

Gateway memakai otomasi WhatsApp Web tidak resmi. Gunakan nomor operasional khusus, hanya kirim ke kontak yang menyetujui komunikasi, terapkan jeda/antrean, dan jangan digunakan untuk spam. Perubahan WhatsApp Web dapat memutus sesi atau menyebabkan nomor dibatasi.
