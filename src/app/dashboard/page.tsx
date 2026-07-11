import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { AppHeader } from "@/components/app-header";
import { VehicleCard } from "@/components/vehicle-card";
import type { Vehicle } from "@/lib/types";

export default async function DashboardPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login?next=/dashboard");
  }

  const { data: profile } = await supabase
    .from("users")
    .select("role")
    .eq("id", user.id)
    .single();

  const { data: vehicles } = await supabase
    .from("vehicles")
    .select("*")
    .order("name", { ascending: true });

  return (
    <div className="min-h-screen bg-background">
      <AppHeader email={user.email} role={profile?.role} />
      <main className="mx-auto max-w-md px-4 py-6">
        <h1 className="text-lg font-semibold">Fleet Overview</h1>
        <p className="mt-1 text-sm text-muted-foreground">
          Select an available vehicle to start its checklist.
        </p>

        <div className="mt-4 space-y-3">
          {(vehicles as Vehicle[] | null)?.map((vehicle) => (
            <VehicleCard key={vehicle.id} vehicle={vehicle} />
          ))}
          {vehicles?.length === 0 && (
            <p className="py-8 text-center text-sm text-muted-foreground">
              No vehicles found in the fleet.
            </p>
          )}
        </div>
      </main>
    </div>
  );
}
