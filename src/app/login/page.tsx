"use client";

import { useState, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { createClient } from "@/lib/supabase/client";

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const supabase = createClient();
    const { error: signInError } = await supabase.auth.signInWithPassword({
      email,
      password,
    });

    setLoading(false);

    if (signInError) {
      setError("Email atau kata sandi salah.");
      return;
    }

    router.push(searchParams.get("next") ?? "/valet");
    router.refresh();
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-panel px-6">
      <div className="w-full max-w-sm">
        <div className="font-mono text-xs uppercase tracking-[0.14em] text-accent-soft">
          Staf hotel
        </div>
        <h1 className="mt-3 font-serif text-2xl text-on-panel">
          Masuk ke Vehicle Inspection Car
        </h1>
        <p className="mt-2 text-sm text-on-panel-soft">
          Gunakan akun staf yang sudah terdaftar untuk mengakses valet dan
          dashboard.
        </p>

        <form onSubmit={handleSubmit} className="mt-8 space-y-4">
          <label className="flex flex-col gap-1.5 text-sm">
            <span className="text-on-panel-soft">Email</span>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="rounded-sm border border-panel-line bg-panel-2 px-3 py-2.5 text-on-panel outline-none focus:border-accent-soft"
              placeholder="nama@hotel.com"
            />
          </label>
          <label className="flex flex-col gap-1.5 text-sm">
            <span className="text-on-panel-soft">Kata sandi</span>
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="rounded-sm border border-panel-line bg-panel-2 px-3 py-2.5 text-on-panel outline-none focus:border-accent-soft"
              placeholder="••••••••"
            />
          </label>

          {error && <p className="text-sm text-danger">{error}</p>}

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-sm bg-accent px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-accent-deep disabled:opacity-60"
          >
            {loading ? "Memeriksa…" : "Masuk"}
          </button>
        </form>
      </div>
    </div>
  );
}

export default function LoginPage() {
  return (
    <Suspense fallback={null}>
      <LoginForm />
    </Suspense>
  );
}
