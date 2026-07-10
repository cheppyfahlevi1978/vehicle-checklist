import { notFound } from "next/navigation";
import { StatusPill } from "@/components/status-pill";
import { mockSessions } from "@/lib/mock-data";

export default async function GuestPortal({
  params,
}: {
  params: Promise<{ sessionId: string }>;
}) {
  const { sessionId } = await params;
  const session = mockSessions.find((s) => s.id === sessionId);
  if (!session) notFound();

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
          {session.guestName} · Kamar {session.roomNumber}
        </p>

        <div className="mt-8 grid grid-cols-2 gap-3 text-sm">
          <div className="rounded-sm border border-line bg-card p-4">
            <div className="text-xs text-foreground-soft">Level BBM</div>
            <div className="mt-1 font-serif text-xl text-foreground">
              {session.fuelLevel
                ? `${Math.round(session.fuelLevel * 100)}%`
                : "—"}
            </div>
          </div>
          <div className="rounded-sm border border-line bg-card p-4">
            <div className="text-xs text-foreground-soft">Odometer</div>
            <div className="mt-1 font-serif text-xl tabular-nums text-foreground">
              {session.odometer?.toLocaleString("id-ID")} km
            </div>
          </div>
        </div>

        <div className="mt-8">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            Foto saat mobil diterima
          </h2>
          <div className="mt-3 grid grid-cols-2 gap-2">
            {["Depan", "Belakang", "Sisi kiri", "Sisi kanan"].map((label) => (
              <div
                key={label}
                className="flex aspect-[4/3] items-center justify-center rounded-sm border border-line bg-card text-xs text-foreground-soft"
              >
                {label}
              </div>
            ))}
          </div>
        </div>

        {session.damageMarks.length > 0 ? (
          <div className="mt-8 rounded-sm border border-danger/30 bg-danger/5 p-4">
            <h2 className="font-mono text-xs uppercase tracking-wider text-danger">
              Catatan kondisi
            </h2>
            <ul className="mt-3 space-y-2 text-sm text-foreground">
              {session.damageMarks.map((d) => (
                <li key={d.id}>
                  <span className="font-medium">{d.point}</span>
                  {d.note ? ` — ${d.note}` : ""}
                </li>
              ))}
            </ul>
            <div className="mt-4 flex gap-3">
              <button className="flex-1 rounded-sm bg-accent px-4 py-2.5 text-xs font-medium text-white hover:bg-accent-deep">
                Setujui laporan
              </button>
              <button className="flex-1 rounded-sm border border-line px-4 py-2.5 text-xs font-medium text-foreground hover:border-accent-soft">
                Ajukan keberatan
              </button>
            </div>
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
