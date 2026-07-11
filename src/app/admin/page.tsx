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

function formatDate(date: Date) {
  return date.toLocaleDateString("id-ID", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

export default async function AdminDashboard() {
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
  const activeCount = sessions.filter(
    (s) => s.status !== "checked_out",
  ).length;
  const incidentCount = sessions.filter((s) => s.status === "incident").length;
  const doneCount = sessions.filter((s) => s.status === "checked_out").length;

  const durations = sessions
    .filter((s) => s.checked_out_at)
    .map(
      (s) =>
        (new Date(s.checked_out_at!).getTime() -
          new Date(s.checked_in_at).getTime()) /
        60000,
    );
  const avgMinutes = durations.length
    ? Math.round(durations.reduce((a, b) => a + b, 0) / durations.length)
    : null;

  const stats = [
    { label: "Kendaraan aktif", value: activeCount },
    { label: "Insiden terbuka", value: incidentCount, tone: "danger" },
    { label: "Rata-rata inspeksi", value: avgMinutes ? `${avgMinutes} mnt` : "—" },
    { label: "Selesai hari ini", value: doneCount },
  ] as const;

  return (
    <div className="flex min-h-screen flex-col bg-background">
      <AppHeader role="Duty Manager" email={user?.email ?? undefined} />
      <main className="mx-auto w-full max-w-5xl flex-1 px-6 py-10">
        <h1 className="font-serif text-2xl text-foreground">
          Ringkasan operasional
        </h1>
        <p className="mt-1 text-sm text-foreground-soft">
          {formatDate(new Date())}
        </p>

        <div className="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-sm border border-line bg-line sm:grid-cols-4">
          {stats.map((stat) => (
            <div key={stat.label} className="bg-card p-5">
              <div className="font-mono text-[11px] uppercase tracking-wider text-foreground-soft">
                {stat.label}
              </div>
              <div
                className={`mt-2 font-serif text-3xl tabular-nums ${
                  "tone" in stat && stat.tone === "danger"
                    ? "text-danger"
                    : "text-foreground"
                }`}
              >
                {stat.value}
              </div>
            </div>
          ))}
        </div>

        <div className="mt-10">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            Aktivitas kendaraan
          </h2>
          {sessions.length === 0 ? (
            <p className="mt-3 rounded-sm border border-dashed border-line bg-card px-5 py-8 text-center text-sm text-foreground-soft">
              Belum ada aktivitas kendaraan.
            </p>
          ) : (
            <div className="mt-3 overflow-x-auto rounded-sm border border-line bg-card">
              <table className="w-full min-w-[640px] text-left text-sm">
                <thead>
                  <tr className="border-b border-line text-xs uppercase tracking-wider text-foreground-soft">
                    <th className="px-5 py-3 font-normal">Plat</th>
                    <th className="px-5 py-3 font-normal">Tamu</th>
                    <th className="px-5 py-3 font-normal">Valet</th>
                    <th className="px-5 py-3 font-normal">Masuk</th>
                    <th className="px-5 py-3 font-normal">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-line">
                  {sessions.map((s) => (
                    <tr key={s.id}>
                      <td className="px-5 py-3 font-mono">{s.plate}</td>
                      <td className="px-5 py-3">{s.guest_name}</td>
                      <td className="px-5 py-3 text-foreground-soft">
                        {s.valet_name}
                      </td>
                      <td className="px-5 py-3 font-mono text-foreground-soft tabular-nums">
                        {formatTime(s.checked_in_at)}
                      </td>
                      <td className="px-5 py-3">
                        <StatusPill status={s.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
