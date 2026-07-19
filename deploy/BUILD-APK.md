# Build APK

Persyaratan:

- Flutter stable
- Android SDK
- JDK yang sesuai dengan Flutter

Buat source tiga aplikasi:

```bash
chmod +x scripts/build-mobile-apps.sh
./scripts/build-mobile-apps.sh
```

Build APK release:

```bash
cd build-mobile/ias_market_buyer
flutter build apk --release

cd ../ias_market_merchant
flutter build apk --release

cd ../ias_market_courier
flutter build apk --release
```

Lokasi APK setiap aplikasi:

```text
build/app/outputs/flutter-apk/app-release.apk
```

Untuk distribusi publik, buat signing key sendiri dan jangan menyimpan password keystore di source repository.
