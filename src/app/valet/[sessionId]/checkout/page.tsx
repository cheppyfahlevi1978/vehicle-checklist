import { notFound } from "next/navigation";
import { AppHeader } from "@/components/app-header";
import { StatusPill } from "@/components/status-pill";
import { mockSessions } from "@/lib/mock-data";

export default async function CheckoutPage({
  params,
}: {
  params: Promise<{ sessionId: string }>;
}) {
  const { sessionId } = await params;
  const session = mockSessions.find((s) => s.id === sessionId);
  if (!session) notFound();

  return (
    <div className="flex min-h-screen flex-col bg-background">
      <AppHeader role="Valet · Check-out" />
      <main className="mx-auto w-full max-w-2xl flex-1 px-6 py-10">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="font-mono text-xl text-foreground">
              {session.plate}
            </h1>
            <p className="mt-1 text-sm text-foreground-soft">
              {session.guestName} · Kamar {session.roomNumber} ·{" "}
              {session.parkingZone}
            </p>
          </div>
          <StatusPill status={session.status} />
        </div>

        <section className="mt-8 space-y-4">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            Bandingkan kondisi
          </h2>
          <div className="grid grid-cols-2 gap-3">
            <div className="rounded-sm border border-line bg-card p-4">
              <div className="font-mono text-[11px] uppercase tracking-wider text-foreground-soft">
                Saat check-in
              </div>
              <div className="mt-3 flex aspect-video items-center justify-center rounded-sm border border-dashed border-line text-xs text-foreground-soft">
                Foto check-in
              </div>
            </div>
            <div className="rounded-sm border border-line bg-card p-4">
              <div className="font-mono text-[11px] uppercase tracking-wider text-foreground-soft">
                Sekarang
              </div>
              <button className="mt-3 flex aspect-video w-full items-center justify-center rounded-sm border border-dashed border-accent-soft text-xs text-accent-deep hover:bg-accent/10">
                Ambil foto check-out
              </button>
            </div>
          </div>
        </section>

        {session.damageMarks.length > 0 && (
          <section className="mt-8 rounded-sm border border-danger/30 bg-danger/5 p-4">
            <h2 className="font-mono text-xs uppercase tracking-wider text-danger">
              Perbedaan kondisi ditemukan
            </h2>
            <ul className="mt-3 space-y-2 text-sm text-foreground">
              {session.damageMarks.map((d) => (
                <li key={d.id} className="flex items-start gap-2">
                  <span className="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-danger" />
                  <span>
                    <span className="font-medium">{d.point}</span>
                    {d.note ? ` — ${d.note}` : ""}
                  </span>
                </li>
              ))}
            </ul>
            <button className="mt-4 rounded-sm bg-danger px-4 py-2 text-xs font-medium text-white hover:opacity-90">
              Buat laporan insiden
            </button>
          </section>
        )}

        <section className="mt-8 space-y-4">
          <h2 className="font-mono text-xs uppercase tracking-wider text-foreground-soft">
            Persetujuan tamu
          </h2>
          <div className="flex gap-3">
            <button className="flex-1 rounded-sm border border-success/40 bg-success/10 px-4 py-3 text-sm font-medium text-success hover:bg-success/20">
              Kondisi sesuai
            </button>
            <button className="flex-1 rounded-sm border border-danger/40 bg-danger/10 px-4 py-3 text-sm font-medium text-danger hover:bg-danger/20">
              Ajukan keberatan
            </button>
          </div>
        </section>
      </main>
    </div>
  );
}
