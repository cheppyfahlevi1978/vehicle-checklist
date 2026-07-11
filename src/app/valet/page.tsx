import Link from "next/link";
import { AppHeader } from "@/components/app-header";
import { StatusPill } from "@/components/status-pill";
import { createClient } from "@/lib/supabase/server";
import { InspectionSession } from "@/lib/types";

function formatTime(iso: string) {
  return new Date(iso).toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

export default async function ValetHome() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { data } = await supabase
    .from("guest_vehicle_sessions")
    .select("*")
    .order("checked_in_at", { ascending: false })
    .returns<InspectionSession[]>();

  const sessions = data ?? [];
  const active = sessions.filter((s) => s.status !== "checked_out");
  const done = sessions.filter((s) => s.status === "checked_out");

  return (
    <div className="flex min-h-screen flex-col bg-background">
      <AppHeader role="Valet" email={user?.email ?? undefined} />
      <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
        <div className="flex items-center justify-between gap-4">
          <div>
            <h1 className="font-serif text-2xl text-foreground">
              Sesi aktif
            </h1>
            <p className="mt-1 text-sm text-foreground-soft">
              {active.length} kendaraan sedang ditangani hari ini.
            </p>
          </div>
          <Link
            href="/valet/checkin"
            className="whitespace-nowrap rounded-sm bg-accent px-4 py-2.5 text-sm font-medium text-white hover:bg-accent-deep"
          >
            + Check-in kendaraan baru
          </Link>
        </div>

        {active.length === 0 ? (
          <p className="mt-8 rounded-sm border border-dashed border-line bg-card px-5 py-8 text-center text-sm text-foreground-soft">
            Belum ada kendaraan aktif. Mulai dengan check-in kendaraan baru.
          </p>
        ) : (
          <div className="mt-8 divide-y divide-line rounded-sm border border-line bg-card">
            {active.map((s) => (
              <Link
                key={s.id}
                href={`/valet/${s.id}/checkout`}
                className="flex items-center justify-between gap-4 px-5 py-4 hover:bg-background-2"
              >
                <div className="flex items-center gap-4">
                  <div className="font-mono text-sm text-foreground">
                    {s.plate}
                  </div>
                  <div className="text-sm text-foreground-soft">
                    {s.guest_name} · Kamar {s.room_number}
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <span className="hidden font-mono text-xs text-foreground-soft sm:inline">
                    {s.parking_zone ?? formatTime(s.checked_in_at)}
                  </span>
                  <StatusPill status={s.status} />
                </div>
              </Link>
            ))}
          </div>
        )}

        {done.length > 0 && (
          <div className="mt-10">
            <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
              Selesai hari ini
            </h2>
            <div className="mt-3 divide-y divide-line rounded-sm border border-line bg-card opacity-70">
              {done.map((s) => (
                <div
                  key={s.id}
                  className="flex items-center justify-between gap-4 px-5 py-3"
                >
                  <div className="font-mono text-sm">{s.plate}</div>
                  <div className="text-sm text-foreground-soft">
                    {s.guest_name}
                  </div>
                  <StatusPill status={s.status} />
                </div>
              ))}
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
