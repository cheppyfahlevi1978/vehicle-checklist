"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { createClient } from "@/lib/supabase/client";
import { DamageMark, InspectionSession, VehiclePhoto } from "@/lib/types";

export function CheckoutClient({
  session,
  checkinPhotos,
  damageMarks,
}: {
  session: InspectionSession;
  checkinPhotos: VehiclePhoto[];
  damageMarks: DamageMark[];
}) {
  const router = useRouter();
  const supabase = createClient();

  const [checkoutPhoto, setCheckoutPhoto] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [busy, setBusy] = useState(false);

  async function handleCapture(file: File | undefined) {
    if (!file) return;
    setCheckoutPhoto(file);
    setUploading(true);

    const path = `${session.id}/checkout-overview.jpg`;
    const { error } = await supabase.storage
      .from("vehicle-inspection-photos")
      .upload(path, file, { upsert: true });

    if (!error) {
      await supabase.from("guest_vehicle_photos").insert({
        session_id: session.id,
        stage: "checkout",
        label: "Overview",
        storage_path: path,
      });
    }
    setUploading(false);
  }

  async function resolveSession(status: "checked_out" | "incident") {
    setBusy(true);
    await supabase
      .from("guest_vehicle_sessions")
      .update({
        status,
        checked_out_at: new Date().toISOString(),
        checkout_signed_at: new Date().toISOString(),
      })
      .eq("id", session.id);
    router.push("/valet");
    router.refresh();
  }

  const referencePhoto = checkinPhotos[0];

  return (
    <>
      <section className="mt-8 space-y-4">
        <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
          Bandingkan kondisi
        </h2>
        <div className="grid grid-cols-2 gap-3">
          <div className="rounded-sm border border-line bg-card p-4">
            <div className="font-mono text-[11px] uppercase tracking-wider text-foreground-soft">
              Saat check-in
            </div>
            {referencePhoto ? (
              <img
                src={
                  supabase.storage
                    .from("vehicle-inspection-photos")
                    .getPublicUrl(referencePhoto.storage_path).data.publicUrl
                }
                alt={referencePhoto.label}
                className="mt-3 aspect-video w-full rounded-sm border border-line object-cover"
              />
            ) : (
              <div className="mt-3 flex aspect-video items-center justify-center rounded-sm border border-dashed border-line text-xs text-foreground-soft">
                Tidak ada foto check-in
              </div>
            )}
          </div>
          <div className="rounded-sm border border-line bg-card p-4">
            <div className="font-mono text-[11px] uppercase tracking-wider text-foreground-soft">
              Sekarang
            </div>
            <label className="mt-3 flex aspect-video w-full cursor-pointer items-center justify-center rounded-sm border border-dashed border-accent-soft text-xs text-accent-deep hover:bg-accent/10">
              <input
                type="file"
                accept="image/*"
                capture="environment"
                className="hidden"
                onChange={(e) => handleCapture(e.target.files?.[0])}
              />
              {uploading
                ? "Mengunggah…"
                : checkoutPhoto
                  ? "✓ Foto check-out tersimpan"
                  : "Ambil foto check-out"}
            </label>
          </div>
        </div>
      </section>

      {damageMarks.length > 0 && (
        <section className="mt-8 rounded-sm border border-danger/30 bg-danger/5 p-4">
          <h2 className="font-mono text-xs uppercase tracking-wider text-danger">
            Perbedaan kondisi ditemukan
          </h2>
          <ul className="mt-3 space-y-2 text-sm text-foreground">
            {damageMarks.map((d) => (
              <li key={d.id} className="flex items-start gap-2">
                <span className="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-danger" />
                <span>
                  <span className="font-medium">{d.point}</span>
                  {d.note ? ` — ${d.note}` : ""}
                </span>
              </li>
            ))}
          </ul>
        </section>
      )}

      <section className="mt-8 space-y-4">
        <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
          Persetujuan tamu
        </h2>
        <div className="flex gap-3">
          <button
            disabled={busy}
            onClick={() => resolveSession("checked_out")}
            className="flex-1 rounded-sm border border-success/40 bg-success/10 px-4 py-3 text-sm font-medium text-success hover:bg-success/20 disabled:opacity-60"
          >
            Kondisi sesuai
          </button>
          <button
            disabled={busy}
            onClick={() => resolveSession("incident")}
            className="flex-1 rounded-sm border border-danger/40 bg-danger/10 px-4 py-3 text-sm font-medium text-danger hover:bg-danger/20 disabled:opacity-60"
          >
            Ajukan keberatan
          </button>
        </div>
      </section>
    </>
  );
}
