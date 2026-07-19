# Instalasi eArsip di Domainesia

## DNS

Buat record Cloudflare:

```text
A  arsip  103.147.154.179  Proxied
```

## Folder dan document root

```text
Folder        : /home/iasumyid/arsip.ias4u.my.id
Document Root : /home/iasumyid/arsip.ias4u.my.id/public
```

## Perintah instalasi

```bash
cd /home/iasumyid/arsip.ias4u.my.id
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# isi konfigurasi database dan admin pada .env
php artisan migrate --seed --force
php artisan optimize:clear
php artisan config:cache
chmod -R 775 storage bootstrap/cache
```

Dokumen disimpan pada `storage/app/private/archives`. Folder ini tidak boleh diarahkan ke public. Endpoint download Laravel melakukan pemeriksaan token dan unit pengguna sebelum mengirim file.

## Cron scheduler

```text
* * * * * cd /home/iasumyid/arsip.ias4u.my.id && php artisan schedule:run >> /dev/null 2>&1
```

Backup minimal mencakup database MySQL dan folder `storage/app/private`.
