import { notFound, redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { AppHeader } from "@/components/app-header";
import { ChecklistForm } from "./checklist-form";
import type { Vehicle } from "@/lib/types";

export default async function ChecklistPage({
  params,
}: {
  params: Promise<{ vehicleId: string }>;
}) {
  const { vehicleId } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect(`/login?next=/checklist/${vehicleId}`);
  }

  const { data: vehicle } = await supabase
    .from("vehicles")
    .select("*")
    .eq("id", vehicleId)
    .single();

  if (!vehicle) {
    notFound();
  }

  return (
    <div className="min-h-screen bg-background">
      <AppHeader email={user.email} />
      <main className="mx-auto max-w-md px-4 py-6">
        <ChecklistForm vehicle={vehicle as Vehicle} userId={user.id} />
      </main>
    </div>
  );
}
