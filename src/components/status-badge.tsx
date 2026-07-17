import { Badge } from "@/components/ui/badge";
import type { VehicleStatus } from "@/lib/types";

const STATUS_CONFIG: Record<
  VehicleStatus,
  { label: string; variant: "success" | "warning" | "danger" | "outline" }
> = {
  available: { label: "Available", variant: "success" },
  in_use: { label: "In-Use", variant: "warning" },
  maintenance: { label: "Maintenance", variant: "danger" },
  dirty: { label: "Dirty", variant: "outline" },
};

export function StatusBadge({ status }: { status: VehicleStatus }) {
  const config = STATUS_CONFIG[status];
  return <Badge variant={config.variant}>{config.label}</Badge>;
}
