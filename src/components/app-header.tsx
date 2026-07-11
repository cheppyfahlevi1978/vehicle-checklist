import Link from "next/link";
import { Car } from "lucide-react";
import { SignOutButton } from "./sign-out-button";

export function AppHeader({ email, role }: { email?: string; role?: string }) {
  return (
    <header className="sticky top-0 z-10 border-b border-border bg-primary">
      <div className="mx-auto flex max-w-md items-center justify-between px-4 py-3">
        <Link href="/dashboard" className="flex items-center gap-2">
          <Car className="h-5 w-5 text-accent" />
          <span className="text-sm font-semibold text-white">
            Hotel Fleet Check
          </span>
        </Link>
        <div className="flex items-center gap-3">
          {email && (
            <span className="hidden text-xs text-white/60 sm:inline">
              {email}
              {role ? ` · ${role}` : ""}
            </span>
          )}
          {email && (
            <span className="text-white/60 [&_button]:!text-white/60 [&_button:hover]:!text-accent">
              <SignOutButton />
            </span>
          )}
        </div>
      </div>
    </header>
  );
}
