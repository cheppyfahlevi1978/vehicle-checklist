# WA-BOT Mobile

Mobile memakai pola IAS ERP:

- alamat server dapat diubah dari login;
- login memakai email/username dan password backend yang sama;
- token dibaca dari `token`, `access_token`, `data.token`, atau `data.access_token`;
- token disimpan dengan `flutter_secure_storage`;
- semua request berikutnya otomatis membawa `Authorization: Bearer ...`;
- endpoint default `https://wa.ias4u.my.id/api/mobile/v1`.

Gunakan `scripts/build-mobile.sh` dari root paket untuk menghasilkan proyek Flutter lengkap.
