# Vehicle Inspection Car

Sistem inspeksi kendaraan digital untuk hotel bintang 4 & 5 — mengganti
formulir kertas di pos valet dengan pemeriksaan terverifikasi, berfoto, dan
bisa dilacak, dari mobil tamu masuk gerbang sampai kunci diserahkan kembali.

## Stack

- Next.js (App Router) + TypeScript
- Tailwind CSS v4
- Data saat ini masih mock (`src/lib/mock-data.ts`) — integrasi Supabase
  (auth, database, storage foto) menyusul di fase berikutnya.

## Struktur peran

- `/` — pemilihan peran
- `/valet` — daftar sesi aktif, check-in, check-out kendaraan
- `/admin` — dashboard duty manager: statistik, insiden, riwayat kendaraan
- `/guest/[sessionId]` — portal tamu untuk melihat laporan kondisi mobil

## Menjalankan secara lokal

```bash
npm install
npm run dev
```

Buka [http://localhost:3000](http://localhost:3000).
