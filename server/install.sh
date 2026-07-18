#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

mkdir -p database/branches storage
chmod -R 775 database storage

echo "Folder database dan storage siap."
echo "Atur Document Root subdomain ke: $ROOT/public"
echo "Lalu buka: https://yayasan.ias4u.my.id/install"
