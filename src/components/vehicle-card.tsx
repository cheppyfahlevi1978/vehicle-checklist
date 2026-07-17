import Link from "next/link";
import { Bus, Car, CarFront } from "lucide-react";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { StatusBadge } from "@/components/status-badge";
import type { Vehicle } from "@/lib/types";

const TYPE_ICON = {
  SUV: CarFront,
  Van: Bus,
  Buggy: Car,
} as const;

export function VehicleCard({ vehicle }: { vehicle: Vehicle }) {
  const Icon = TYPE_ICON[vehicle.type];

  return (
    <Card>
      <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-md bg-muted">
            <Icon className="h-5 w-5 text-primary" />
          </div>
          <div>
            <p className="text-sm font-semibold leading-tight">{vehicle.name}</p>
            <p className="text-xs text-muted-foreground">{vehicle.plate_number}</p>
          </div>
        </div>
        <StatusBadge status={vehicle.status} />
      </CardHeader>
      <CardContent>
        <div className="flex items-center justify-between">
          <span className="text-xs uppercase tracking-wide text-muted-foreground">
            {vehicle.type}
          </span>
          {vehicle.status === "available" ? (
            <Button asChild size="sm" variant="accent">
              <Link href={`/checklist/${vehicle.id}`}>Start Checklist</Link>
            </Button>
          ) : (
            <Button size="sm" variant="outline" disabled>
              Unavailable
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
