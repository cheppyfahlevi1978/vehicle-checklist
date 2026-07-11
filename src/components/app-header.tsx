import Link from "next/link";
import { SignOutButton } from "./sign-out-button";

export function AppHeader({
  role,
  email,
}: {
  role: string;
  email?: string;
}) {
  return (
    <header className="border-b border-line bg-card">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <Link href="/" className="flex items-baseline gap-2">
          <span className="font-serif text-lg text-foreground">
            Vehicle Inspection <em className="not-italic text-accent">Car</em>
          </span>
        </Link>
        <div className="flex items-center gap-3">
          <span className="font-mono text-[11px] uppercase tracking-wider text-foreground-soft">
            {role}
            {email ? ` · ${email}` : ""}
          </span>
          {email && <SignOutButton />}
        </div>
      </div>
    </header>
  );
}
