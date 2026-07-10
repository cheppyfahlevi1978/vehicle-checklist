"use client";

import { useState } from "react";
import { AppHeader } from "@/components/app-header";

const bodyPoints = [
  "Bumper depan",
  "Kap mesin",
  "Kaca depan",
  "Pintu depan kiri",
  "Pintu belakang kiri",
  "Pintu depan kanan",
  "Pintu belakang kanan",
  "Bagasi / bumper belakang",
  "Atap",
  "Velg & ban",
];

const requiredPhotos = [
  "Depan",
  "Belakang",
  "Sisi kiri",
  "Sisi kanan",
  "Dashboard",
  "Odometer",
];

export default function CheckinPage() {
  const [markedDamage, setMarkedDamage] = useState<Set<string>>(new Set());
  const [capturedPhotos, setCapturedPhotos] = useState<Set<string>>(
    new Set(),
  );
  const [signed, setSigned] = useState(false);

  function toggleDamage(point: string) {
    setMarkedDamage((prev) => {
      const next = new Set(prev);
      if (next.has(point)) next.delete(point);
      else next.add(point);
      return next;
    });
  }

  function capture(label: string) {
    setCapturedPhotos((prev) => new Set(prev).add(label));
  }

  const allPhotosCaptured = requiredPhotos.every((p) =>
    capturedPhotos.has(p),
  );

  return (
    <div className="flex min-h-screen flex-col bg-background">
      <AppHeader role="Valet · Check-in" />
      <main className="mx-auto w-full max-w-2xl flex-1 px-6 py-10">
        <h1 className="font-serif text-2xl text-foreground">
          Check-in kendaraan baru
        </h1>
        <p className="mt-1 text-sm text-foreground-soft">
          Lengkapi kondisi awal sebelum kendaraan dipindahkan ke area parkir.
        </p>

        <section className="mt-8 space-y-4">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            01 · Data kendaraan &amp; tamu
          </h2>
          <div className="grid grid-cols-2 gap-4">
            <label className="flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Nomor polisi</span>
              <input
                className="rounded-sm border border-line bg-card px-3 py-2 font-mono text-sm outline-none focus:border-accent"
                placeholder="B 1234 XYZ"
              />
            </label>
            <label className="flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Nomor kamar</span>
              <input
                className="rounded-sm border border-line bg-card px-3 py-2 text-sm outline-none focus:border-accent"
                placeholder="1204"
              />
            </label>
            <label className="col-span-2 flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Nama tamu</span>
              <input
                className="rounded-sm border border-line bg-card px-3 py-2 text-sm outline-none focus:border-accent"
                placeholder="Nama sesuai reservasi"
              />
            </label>
            <label className="flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Level BBM</span>
              <select className="rounded-sm border border-line bg-card px-3 py-2 text-sm outline-none focus:border-accent">
                <option>Penuh</option>
                <option>¾</option>
                <option>½</option>
                <option>¼</option>
                <option>Hampir kosong</option>
              </select>
            </label>
            <label className="flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Odometer (km)</span>
              <input
                type="number"
                className="rounded-sm border border-line bg-card px-3 py-2 font-mono text-sm outline-none focus:border-accent"
                placeholder="34120"
              />
            </label>
          </div>
        </section>

        <section className="mt-10 space-y-4">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            02 · Titik kondisi bodi — tandai jika ada cacat
          </h2>
          <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
            {bodyPoints.map((point) => {
              const active = markedDamage.has(point);
              return (
                <button
                  key={point}
                  type="button"
                  onClick={() => toggleDamage(point)}
                  className={`rounded-sm border px-3 py-2.5 text-left text-sm transition-colors ${
                    active
                      ? "border-warning bg-warning/10 text-warning"
                      : "border-line bg-card text-foreground hover:border-accent-soft"
                  }`}
                >
                  {point}
                </button>
              );
            })}
          </div>
          {markedDamage.size > 0 && (
            <p className="text-xs text-warning">
              {markedDamage.size} titik ditandai — tambahkan foto close-up
              untuk tiap titik sebelum menyimpan.
            </p>
          )}
        </section>

        <section className="mt-10 space-y-4">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            03 · Foto wajib
          </h2>
          <div className="grid grid-cols-3 gap-2">
            {requiredPhotos.map((label) => {
              const captured = capturedPhotos.has(label);
              return (
                <button
                  key={label}
                  type="button"
                  onClick={() => capture(label)}
                  className={`flex aspect-[4/3] flex-col items-center justify-center gap-1 rounded-sm border text-xs transition-colors ${
                    captured
                      ? "border-success bg-success/10 text-success"
                      : "border-dashed border-line bg-card text-foreground-soft hover:border-accent-soft"
                  }`}
                >
                  <span>{captured ? "✓ Tersimpan" : "Ambil foto"}</span>
                  <span>{label}</span>
                </button>
              );
            })}
          </div>
        </section>

        <section className="mt-10 space-y-4">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            04 · Persetujuan tamu
          </h2>
          <button
            type="button"
            onClick={() => setSigned(true)}
            className={`flex h-28 w-full items-center justify-center rounded-sm border text-sm transition-colors ${
              signed
                ? "border-success bg-success/10 text-success"
                : "border-dashed border-line bg-card text-foreground-soft hover:border-accent-soft"
            }`}
          >
            {signed
              ? "✓ Ditandatangani oleh tamu"
              : "Ketuk untuk tanda tangan digital tamu"}
          </button>
        </section>

        <button
          type="button"
          disabled={!allPhotosCaptured || !signed}
          className="mt-10 w-full rounded-sm bg-accent px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-accent-deep disabled:cursor-not-allowed disabled:bg-line disabled:text-foreground-soft"
        >
          Simpan &amp; pindahkan ke parkir
        </button>
      </main>
    </div>
  );
}
