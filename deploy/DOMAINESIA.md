# Deployment Laravel di Domainesia

1. Buat subdomain `market.ias4u.my.id`.
2. Jalankan generator backend pada komputer/Laragon atau server yang memiliki Composer.
3. Upload hasil ke `/home/iasumyid/market.ias4u.my.id`.
4. Set Document Root ke `/home/iasumyid/market.ias4u.my.id/public`.
5. Gunakan PHP 8.3+ dan MySQL/MariaDB.
6. Salin `.env.example` menjadi `.env` lalu isi database dan password admin.
7. Jalankan:

```bash
cd /home/iasumyid/market.ias4u.my.id
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --seed --force
php artisan optimize:clear
php artisan config:cache
chmod -R 775 storage bootstrap/cache
```

Jangan memakai APP_KEY proyek lain. Buat APP_KEY baru dengan `php artisan key:generate --force`.
