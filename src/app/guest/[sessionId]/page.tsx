import { notFound } from "next/navigation";
import { StatusPill } from "@/components/status-pill";
import { createClient } from "@/lib/supabase/server";
import { DamageMark, InspectionSession, VehiclePhoto } from "@/lib/types";

export default async function GuestPortal({
  params,
}: {
  params: Promise<{ sessionId: string }>;
}) {
  const { sessionId } = await params;
  const supabase = await createClient();

  const { data: session } = await supabase
    .from("guest_vehicle_sessions")
    .select("*")
    .eq("id", sessionId)
    .single<InspectionSession>();

  if (!session) notFound();

  const { data: photos } = await supabase
    .from("guest_vehicle_photos")
    .select("*")
    .eq("session_id", sessionId)
    .eq("stage", "checkin")
    .returns<VehiclePhoto[]>();

  const { data: damageMarks } = await supabase
    .from("guest_vehicle_damage_marks")
    .select("*")
    .eq("session_id", sessionId)
    .returns<DamageMark[]>();

  const checkinPhotos = photos ?? [];
  const marks = damageMarks ?? [];

  return (
    <div className="flex min-h-screen flex-col bg-background">
      <header className="border-b border-line bg-card">
        <div className="mx-auto max-w-lg px-6 py-5">
          <span className="font-serif text-lg text-foreground">
            Vehicle Inspection <em className="not-italic text-accent">Car</em>
          </span>
        </div>
      </header>
      <main className="mx-auto w-full max-w-lg flex-1 px-6 py-10">
        <div className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
          Laporan kondisi kendaraan
        </div>
        <div className="mt-2 flex items-center justify-between">
          <h1 className="font-mono text-2xl text-foreground">
            {session.plate}
          </h1>
          <StatusPill status={session.status} />
        </div>
        <p className="mt-1 text-sm text-foreground-soft">
          {session.guest_name} · Kamar {session.room_number}
        </p>

        <div className="mt-8 grid grid-cols-2 gap-3 text-sm">
          <div className="rounded-sm border border-line bg-card p-4">
            <div className="text-xs text-foreground-soft">Level BBM</div>
            <div className="mt-1 font-serif text-xl text-foreground">
              {session.fuel_level != null
                ? `${Math.round(session.fuel_level * 100)}%`
                : "—"}
            </div>
          </div>
          <div className="rounded-sm border border-line bg-card p-4">
            <div className="text-xs text-foreground-soft">Odometer</div>
            <div className="mt-1 font-serif text-xl tabular-nums text-foreground">
              {session.odometer != null
                ? `${session.odometer.toLocaleString("id-ID")} km`
                : "—"}
            </div>
          </div>
        </div>

        <div className="mt-8">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            Foto saat mobil diterima
          </h2>
          {checkinPhotos.length === 0 ? (
            <p className="mt-3 text-sm text-foreground-soft">
              Belum ada foto tersimpan.
            </p>
          ) : (
            <div className="mt-3 grid grid-cols-2 gap-2">
              {checkinPhotos.map((photo) => (
                <img
                  key={photo.id}
                  src={
                    supabase.storage
                      .from("vehicle-inspection-photos")
                      .getPublicUrl(photo.storage_path).data.publicUrl
                  }
                  alt={photo.label}
                  className="aspect-[4/3] w-full rounded-sm border border-line object-cover"
                />
              ))}
            </div>
          )}
        </div>

        {marks.length > 0 ? (
          <div className="mt-8 rounded-sm border border-danger/30 bg-danger/5 p-4">
            <h2 className="font-mono text-xs uppercase tracking-wider text-danger">
              Catatan kondisi
            </h2>
            <ul className="mt-3 space-y-2 text-sm text-foreground">
              {marks.map((d) => (
                <li key={d.id}>
                  <span className="font-medium">{d.point}</span>
                  {d.note ? ` — ${d.note}` : ""}
                </li>
              ))}
            </ul>
          </div>
        ) : (
          <div className="mt-8 rounded-sm border border-success/30 bg-success/5 p-4 text-sm text-success">
            Tidak ada catatan kerusakan pada kendaraan ini.
          </div>
        )}
      </main>
    </div>
  );
}
