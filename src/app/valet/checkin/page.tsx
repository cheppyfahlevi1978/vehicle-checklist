"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { AppHeader } from "@/components/app-header";
import { createClient } from "@/lib/supabase/client";

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

const fuelLevels = [
  { label: "Penuh", value: 1 },
  { label: "¾", value: 0.75 },
  { label: "½", value: 0.5 },
  { label: "¼", value: 0.25 },
  { label: "Hampir kosong", value: 0.1 },
];

export default function CheckinPage() {
  const router = useRouter();
  const supabase = createClient();

  const [userEmail, setUserEmail] = useState<string | undefined>();
  const [plate, setPlate] = useState("");
  const [roomNumber, setRoomNumber] = useState("");
  const [guestName, setGuestName] = useState("");
  const [fuelLevel, setFuelLevel] = useState(fuelLevels[0].value);
  const [odometer, setOdometer] = useState("");
  const [markedDamage, setMarkedDamage] = useState<Set<string>>(new Set());
  const [photos, setPhotos] = useState<Record<string, File>>({});
  const [signed, setSigned] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    supabase.auth.getUser().then(({ data }) => setUserEmail(data.user?.email));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  function toggleDamage(point: string) {
    setMarkedDamage((prev) => {
      const next = new Set(prev);
      if (next.has(point)) next.delete(point);
      else next.add(point);
      return next;
    });
  }

  function capture(label: string, file: File | undefined) {
    if (!file) return;
    setPhotos((prev) => ({ ...prev, [label]: file }));
  }

  const allPhotosCaptured = requiredPhotos.every((p) => photos[p]);
  const canSubmit =
    plate.trim() &&
    roomNumber.trim() &&
    guestName.trim() &&
    allPhotosCaptured &&
    signed &&
    !submitting;

  async function handleSubmit() {
    setSubmitting(true);
    setError(null);

    const valetName = userEmail ? userEmail.split("@")[0] : "Valet";

    const { data: session, error: insertError } = await supabase
      .from("guest_vehicle_sessions")
      .insert({
        plate: plate.trim().toUpperCase(),
        guest_name: guestName.trim(),
        room_number: roomNumber.trim(),
        valet_name: valetName,
        fuel_level: fuelLevel,
        odometer: odometer ? Number(odometer) : null,
        status: "checked_in",
        checkin_signed_at: new Date().toISOString(),
      })
      .select("id")
      .single();

    if (insertError || !session) {
      setError("Gagal menyimpan sesi. Coba lagi.");
      setSubmitting(false);
      return;
    }

    if (markedDamage.size > 0) {
      await supabase.from("guest_vehicle_damage_marks").insert(
        Array.from(markedDamage).map((point) => ({
          session_id: session.id,
          point,
          severity: "minor" as const,
          found_at_stage: "checkin" as const,
        })),
      );
    }

    for (const [label, file] of Object.entries(photos)) {
      const path = `${session.id}/checkin-${label}.jpg`;
      const { error: uploadError } = await supabase.storage
        .from("vehicle-inspection-photos")
        .upload(path, file, { upsert: true });

      if (!uploadError) {
        await supabase.from("guest_vehicle_photos").insert({
          session_id: session.id,
          stage: "checkin",
          label,
          storage_path: path,
        });
      }
    }

    router.push("/valet");
    router.refresh();
  }

  return (
    <div className="flex min-h-screen flex-col bg-background">
      <AppHeader role="Valet · Check-in" email={userEmail} />
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
                value={plate}
                onChange={(e) => setPlate(e.target.value)}
                className="rounded-sm border border-line bg-card px-3 py-2 font-mono text-sm outline-none focus:border-accent"
                placeholder="B 1234 XYZ"
              />
            </label>
            <label className="flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Nomor kamar</span>
              <input
                value={roomNumber}
                onChange={(e) => setRoomNumber(e.target.value)}
                className="rounded-sm border border-line bg-card px-3 py-2 text-sm outline-none focus:border-accent"
                placeholder="1204"
              />
            </label>
            <label className="col-span-2 flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Nama tamu</span>
              <input
                value={guestName}
                onChange={(e) => setGuestName(e.target.value)}
                className="rounded-sm border border-line bg-card px-3 py-2 text-sm outline-none focus:border-accent"
                placeholder="Nama sesuai reservasi"
              />
            </label>
            <label className="flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Level BBM</span>
              <select
                value={fuelLevel}
                onChange={(e) => setFuelLevel(Number(e.target.value))}
                className="rounded-sm border border-line bg-card px-3 py-2 text-sm outline-none focus:border-accent"
              >
                {fuelLevels.map((f) => (
                  <option key={f.label} value={f.value}>
                    {f.label}
                  </option>
                ))}
              </select>
            </label>
            <label className="flex flex-col gap-1.5 text-sm">
              <span className="text-foreground-soft">Odometer (km)</span>
              <input
                type="number"
                value={odometer}
                onChange={(e) => setOdometer(e.target.value)}
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
              {markedDamage.size} titik ditandai — pastikan foto close-up
              tersimpan untuk tiap titik.
            </p>
          )}
        </section>

        <section className="mt-10 space-y-4">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            03 · Foto wajib
          </h2>
          <div className="grid grid-cols-3 gap-2">
            {requiredPhotos.map((label) => {
              const captured = Boolean(photos[label]);
              return (
                <label
                  key={label}
                  className={`flex aspect-[4/3] cursor-pointer flex-col items-center justify-center gap-1 rounded-sm border text-xs transition-colors ${
                    captured
                      ? "border-success bg-success/10 text-success"
                      : "border-dashed border-line bg-card text-foreground-soft hover:border-accent-soft"
                  }`}
                >
                  <input
                    type="file"
                    accept="image/*"
                    capture="environment"
                    className="hidden"
                    onChange={(e) => capture(label, e.target.files?.[0])}
                  />
                  <span>{captured ? "✓ Tersimpan" : "Ambil foto"}</span>
                  <span>{label}</span>
                </label>
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

        {error && <p className="mt-4 text-sm text-danger">{error}</p>}

        <button
          type="button"
          disabled={!canSubmit}
          onClick={handleSubmit}
          className="mt-10 w-full rounded-sm bg-accent px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-accent-deep disabled:cursor-not-allowed disabled:bg-line disabled:text-foreground-soft"
        >
          {submitting ? "Menyimpan…" : "Simpan & pindahkan ke parkir"}
        </button>
      </main>
    </div>
  );
}
