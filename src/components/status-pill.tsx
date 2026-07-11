import { SessionStatus } from "@/lib/types";
import { statusLabel } from "@/lib/status";

const styles: Record<SessionStatus, string> = {
  checked_in: "bg-accent/15 text-accent-deep border-accent/30",
  parked: "bg-foreground-soft/10 text-foreground-soft border-line",
  requested: "bg-accent/15 text-accent-deep border-accent/30",
  checked_out: "bg-success/10 text-success border-success/30",
  incident: "bg-danger/10 text-danger border-danger/30",
};

export function StatusPill({ status }: { status: SessionStatus }) {
  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium ${styles[status]}`}
    >
      <span
        className="h-1.5 w-1.5 rounded-full bg-current"
        aria-hidden="true"
      />
      {statusLabel[status]}
    </span>
  );
}
