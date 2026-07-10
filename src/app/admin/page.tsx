import { AppHeader } from "@/components/app-header";
import { StatusPill } from "@/components/status-pill";
import { mockSessions } from "@/lib/mock-data";

function formatTime(iso: string) {
  return new Date(iso).toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

export default function AdminDashboard() {
  const activeCount = mockSessions.filter(
    (s) => s.status !== "checked_out",
  ).length;
  const incidentCount = mockSessions.filter(
    (s) => s.status === "incident",
  ).length;
  const avgMinutes = 6;

  const stats = [
    { label: "Kendaraan aktif", value: activeCount },
    { label: "Insiden terbuka", value: incidentCount, tone: "danger" },
    { label: "Rata-rata inspeksi", value: `${avgMinutes} mnt` },
    { label: "Selesai hari ini", value: 12 },
  ] as const;

  return (
    <div className="flex min-h-screen flex-col bg-background">
      <AppHeader role="Duty Manager" />
      <main className="mx-auto w-full max-w-5xl flex-1 px-6 py-10">
        <h1 className="font-serif text-2xl text-foreground">
          Ringkasan operasional
        </h1>
        <p className="mt-1 text-sm text-foreground-soft">
          Sabtu, 10 Juli 2026
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
                {mockSessions.map((s) => (
                  <tr key={s.id}>
                    <td className="px-5 py-3 font-mono">{s.plate}</td>
                    <td className="px-5 py-3">{s.guestName}</td>
                    <td className="px-5 py-3 text-foreground-soft">
                      {s.valetName}
                    </td>
                    <td className="px-5 py-3 font-mono text-foreground-soft tabular-nums">
                      {formatTime(s.checkedInAt)}
                    </td>
                    <td className="px-5 py-3">
                      <StatusPill status={s.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  );
}
