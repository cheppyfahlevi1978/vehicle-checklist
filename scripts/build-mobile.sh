#!/usr/bin/env bash
set -euo pipefail

TARGET="${1:-wa_bot_mobile}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

command -v flutter >/dev/null || { echo "Flutter SDK tidak ditemukan"; exit 1; }

flutter create "$TARGET" --org id.ias4u --platforms android
cp "$ROOT/mobile/pubspec.yaml" "$TARGET/pubspec.yaml"
rm -rf "$TARGET/lib"
cp -R "$ROOT/mobile/lib" "$TARGET/lib"
cd "$TARGET"
flutter pub get

echo "Mobile dibuat di: $TARGET"
echo "Build APK: cd $TARGET && flutter build apk --release"
