# Deployment Laravel di Domainesia

1. Jalankan `scripts/build-backend.sh wa-bot-backend` pada komputer atau server yang memiliki Composer.
2. Upload hasilnya ke `/home/iasumyid/wa.ias4u.my.id`.
3. Set Document Root ke `/home/iasumyid/wa.ias4u.my.id/public`.
4. Gunakan PHP 8.3+ dan aktifkan PDO MySQL, Mbstring, OpenSSL, Fileinfo, Ctype, Tokenizer, XML.
5. Buat database MySQL dan sesuaikan `.env`.
6. Jalankan:

```bash
cd /home/iasumyid/wa.ias4u.my.id
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
chmod -R 775 storage bootstrap/cache
```

Scheduler cPanel:

```text
* * * * * cd /home/iasumyid/wa.ias4u.my.id && php artisan schedule:run >> /dev/null 2>&1
```

Queue sebaiknya dijalankan melalui cron atau Supervisor yang tersedia pada paket hosting.
