#!/usr/bin/env bash
set -euo pipefail

TARGET="${1:-e_arsip_mobile}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

command -v flutter >/dev/null || { echo "Flutter SDK tidak ditemukan"; exit 1; }

flutter create "$TARGET" --org id.ias4u --platforms android
cp "$ROOT/mobile/pubspec.yaml" "$TARGET/pubspec.yaml"
rm -rf "$TARGET/lib"
cp -R "$ROOT/mobile/lib" "$TARGET/lib"
cp "$ROOT/mobile-overlay/android/app/src/main/AndroidManifest.xml" "$TARGET/android/app/src/main/AndroidManifest.xml"
cd "$TARGET"
flutter pub get

echo "Source Android dibuat di: $TARGET"
echo "Build APK: cd $TARGET && flutter build apk --release"
