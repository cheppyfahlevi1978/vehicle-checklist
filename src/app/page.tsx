import Link from "next/link";

const roles = [
  {
    href: "/valet",
    tag: "Di lapangan",
    title: "Valet / Driver",
    desc: "Check-in, check-out, checklist kondisi, dan foto bukti untuk tiap kendaraan.",
  },
  {
    href: "/admin",
    tag: "Di kantor",
    title: "Duty Manager / Admin",
    desc: "Pantau antrean real-time, insiden, dan performa valet dari satu dashboard.",
  },
  {
    href: "/guest/vic-2401",
    tag: "Untuk tamu",
    title: "Guest Portal",
    desc: "Lihat laporan kondisi mobil dan riwayat inspeksi tanpa perlu instal aplikasi.",
  },
];

export default function Home() {
  return (
    <div className="flex min-h-screen flex-col bg-panel text-on-panel">
      <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col justify-center px-6 py-20">
        <div className="font-mono text-xs uppercase tracking-[0.14em] text-accent-soft">
          Hotel Bintang 4 &amp; 5 · Fleet &amp; Guest Vehicle Care
        </div>
        <h1 className="mt-4 max-w-xl font-serif text-4xl leading-tight text-on-panel sm:text-5xl">
          Vehicle Inspection <em className="not-italic text-accent-soft">Car</em>
        </h1>
        <p className="mt-4 max-w-lg text-on-panel-soft">
          Pilih peran untuk masuk ke bagian aplikasi yang relevan dengan
          tugasmu.
        </p>

        <div className="mt-12 grid gap-px overflow-hidden rounded-sm border border-panel-line bg-panel-line sm:grid-cols-3">
          {roles.map((role) => (
            <Link
              key={role.href}
              href={role.href}
              className="group flex flex-col gap-3 bg-panel-2 p-6 transition-colors hover:bg-panel"
            >
              <span className="font-mono text-[11px] uppercase tracking-wider text-accent-soft">
                {role.tag}
              </span>
              <span className="font-serif text-xl text-on-panel">
                {role.title}
              </span>
              <span className="text-sm text-on-panel-soft">{role.desc}</span>
              <span className="mt-auto pt-2 text-xs text-accent-soft opacity-0 transition-opacity group-hover:opacity-100">
                Masuk →
              </span>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}
