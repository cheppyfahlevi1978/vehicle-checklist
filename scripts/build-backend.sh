#!/usr/bin/env bash
set -euo pipefail

TARGET="${1:-e-arsip-backend}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

command -v php >/dev/null || { echo "PHP tidak ditemukan"; exit 1; }
command -v composer >/dev/null || { echo "Composer tidak ditemukan"; exit 1; }

composer create-project laravel/laravel:^13.0 "$TARGET"
cd "$TARGET"
php artisan install:api
composer require guzzlehttp/guzzle
cp -R "$ROOT/backend-overlay/." .
cp .env.example .env
php artisan key:generate
php artisan optimize:clear

echo "Backend eArsip dibuat di: $TARGET"
echo "Selanjutnya isi database pada .env, lalu jalankan: php artisan migrate --seed"
