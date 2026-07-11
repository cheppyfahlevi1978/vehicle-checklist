import { notFound } from "next/navigation";
import { AppHeader } from "@/components/app-header";
import { StatusPill } from "@/components/status-pill";
import { createClient } from "@/lib/supabase/server";
import { DamageMark, InspectionSession, VehiclePhoto } from "@/lib/types";
import { CheckoutClient } from "./checkout-client";

export default async function CheckoutPage({
  params,
}: {
  params: Promise<{ sessionId: string }>;
}) {
  const { sessionId } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { data: session } = await supabase
    .from("guest_vehicle_sessions")
    .select("*")
    .eq("id", sessionId)
    .single<InspectionSession>();

  if (!session) notFound();

  const { data: photos } = await supabase
    .from("guest_vehicle_photos")
    .select("*")
    .eq("session_id", sessionId)
    .eq("stage", "checkin")
    .returns<VehiclePhoto[]>();

  const { data: damageMarks } = await supabase
    .from("guest_vehicle_damage_marks")
    .select("*")
    .eq("session_id", sessionId)
    .returns<DamageMark[]>();

  return (
    <div className="flex min-h-screen flex-col bg-background">
      <AppHeader role="Valet · Check-out" email={user?.email ?? undefined} />
      <main className="mx-auto w-full max-w-2xl flex-1 px-6 py-10">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="font-mono text-xl text-foreground">
              {session.plate}
            </h1>
            <p className="mt-1 text-sm text-foreground-soft">
              {session.guest_name} · Kamar {session.room_number}
              {session.parking_zone ? ` · ${session.parking_zone}` : ""}
            </p>
          </div>
          <StatusPill status={session.status} />
        </div>

        <CheckoutClient
          session={session}
          checkinPhotos={photos ?? []}
          damageMarks={damageMarks ?? []}
        />
      </main>
    </div>
  );
}
