#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/build-mobile"
mkdir -p "$OUT"

command -v flutter >/dev/null || { echo "Flutter SDK tidak ditemukan"; exit 1; }

build_app() {
  local app="$1"
  local org="$2"
  local app_dir="$OUT/$app"
  rm -rf "$app_dir"
  flutter create "$app_dir" --org "$org" --platforms android
  cp "$ROOT/mobile-shared/pubspec.yaml" "$app_dir/pubspec.yaml"
  rm -rf "$app_dir/lib"
  mkdir -p "$app_dir/lib"
  cp -R "$ROOT/mobile-shared/lib/." "$app_dir/lib/"
  cp "$ROOT/${app%-*}-app/lib/main.dart" "$app_dir/lib/main.dart"
  cp "$ROOT/${app%-*}-app/lib/home_page.dart" "$app_dir/lib/home_page.dart"
  (cd "$app_dir" && flutter pub get)
}

build_app "ias_market_buyer" "id.ias4u.market.buyer"
build_app "ias_market_merchant" "id.ias4u.market.merchant"
build_app "ias_market_courier" "id.ias4u.market.courier"

echo "Tiga aplikasi Flutter dibuat di $OUT"
