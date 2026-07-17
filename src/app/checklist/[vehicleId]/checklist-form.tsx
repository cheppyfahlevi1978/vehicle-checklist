"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm, useFieldArray, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Loader2, Upload, CheckCircle2, XCircle } from "lucide-react";
import { toast } from "sonner";
import { createClient } from "@/lib/supabase/client";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
  Accordion,
  AccordionItem,
  AccordionTrigger,
  AccordionContent,
} from "@/components/ui/accordion";
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from "@/components/ui/select";
import {
  checklistFormSchema,
  type ChecklistFormValues,
} from "@/lib/validations/checklist";
import { CHECKLIST_CATEGORIES, FUEL_LEVEL_OPTIONS } from "@/lib/types";
import type { Vehicle } from "@/lib/types";
import { cn } from "@/lib/utils";

export function ChecklistForm({
  vehicle,
  userId,
}: {
  vehicle: Vehicle;
  userId: string;
}) {
  const router = useRouter();
  const [submitting, setSubmitting] = useState(false);
  const [photoUploading, setPhotoUploading] = useState<number | null>(null);

  const defaultItems = CHECKLIST_CATEGORIES.flatMap((category) =>
    category.items.map((itemName) => ({
      category: category.key,
      item_name: itemName,
      condition: "good" as const,
      notes: "",
      photo_url: "",
    })),
  );

  const {
    control,
    handleSubmit,
    register,
    formState: { errors },
  } = useForm<ChecklistFormValues>({
    resolver: zodResolver(checklistFormSchema),
    defaultValues: {
      vehicle_id: vehicle.id,
      fuel_level: undefined,
      items: defaultItems,
    },
  });

  const { fields, update } = useFieldArray({ control, name: "items" });

  async function handlePhotoUpload(index: number, file: File) {
    setPhotoUploading(index);
    const supabase = createClient();
    const path = `${vehicle.id}/${Date.now()}-${file.name}`;

    const { error } = await supabase.storage
      .from("checklist-photos")
      .upload(path, file);

    if (error) {
      toast.error("Photo upload failed. Please try again.");
      setPhotoUploading(null);
      return;
    }

    const {
      data: { publicUrl },
    } = supabase.storage.from("checklist-photos").getPublicUrl(path);

    update(index, { ...fields[index], photo_url: publicUrl });
    setPhotoUploading(null);
    toast.success("Photo attached.");
  }

  async function onSubmit(values: ChecklistFormValues) {
    setSubmitting(true);
    const supabase = createClient();

    const hasIssues = values.items.some((item) => item.condition === "issue");

    const { data: checklist, error: checklistError } = await supabase
      .from("checklists")
      .insert({
        vehicle_id: values.vehicle_id,
        user_id: userId,
        fuel_level: values.fuel_level,
        status: hasIssues ? "issues_found" : "passed",
      })
      .select()
      .single();

    if (checklistError || !checklist) {
      toast.error("Could not submit checklist. Please try again.");
      setSubmitting(false);
      return;
    }

    const { error: itemsError } = await supabase.from("checklist_items").insert(
      values.items.map((item) => ({
        checklist_id: checklist.id,
        category: item.category,
        item_name: item.item_name,
        condition: item.condition,
        notes: item.notes || null,
        photo_url: item.photo_url || null,
      })),
    );

    if (itemsError) {
      toast.error("Checklist saved, but some items failed to save.");
      setSubmitting(false);
      return;
    }

    await supabase
      .from("vehicles")
      .update({ status: hasIssues ? "maintenance" : "in_use" })
      .eq("id", vehicle.id);

    toast.success("Checklist submitted successfully.");
    setSubmitting(false);
    router.push("/dashboard");
    router.refresh();
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold">{vehicle.name}</h1>
        <p className="text-sm text-muted-foreground">
          {vehicle.plate_number} · {vehicle.type}
        </p>
      </div>

      <div className="space-y-1.5">
        <Label htmlFor="fuel_level">Fuel / Battery Level</Label>
        <Controller
          control={control}
          name="fuel_level"
          render={({ field }) => (
            <Select value={field.value} onValueChange={field.onChange}>
              <SelectTrigger id="fuel_level">
                <SelectValue placeholder="Select level" />
              </SelectTrigger>
              <SelectContent>
                {FUEL_LEVEL_OPTIONS.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
        />
        {errors.fuel_level && (
          <p className="text-xs text-danger">{errors.fuel_level.message}</p>
        )}
      </div>

      <div className="rounded-lg border border-border bg-card">
        <Accordion type="multiple" defaultValue={["exterior"]}>
          {CHECKLIST_CATEGORIES.map((category) => (
            <AccordionItem key={category.key} value={category.key} className="px-4">
              <AccordionTrigger>{category.label}</AccordionTrigger>
              <AccordionContent>
                <div className="space-y-4">
                  {fields.map((field, index) => {
                    if (field.category !== category.key) return null;

                    return (
                      <div key={field.id} className="space-y-2 border-t border-border pt-3 first:border-t-0 first:pt-0">
                        <div className="flex items-center justify-between gap-2">
                          <span className="text-sm font-medium">{field.item_name}</span>
                          <div className="flex gap-1.5">
                            <button
                              type="button"
                              onClick={() => update(index, { ...field, condition: "good" })}
                              className={cn(
                                "flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-medium transition-colors",
                                field.condition === "good"
                                  ? "border-success bg-success text-success-foreground"
                                  : "border-border text-muted-foreground hover:bg-muted",
                              )}
                            >
                              <CheckCircle2 className="h-3.5 w-3.5" />
                              Good
                            </button>
                            <button
                              type="button"
                              onClick={() => update(index, { ...field, condition: "issue" })}
                              className={cn(
                                "flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-medium transition-colors",
                                field.condition === "issue"
                                  ? "border-danger bg-danger text-danger-foreground"
                                  : "border-border text-muted-foreground hover:bg-muted",
                              )}
                            >
                              <XCircle className="h-3.5 w-3.5" />
                              Issue
                            </button>
                          </div>
                        </div>

                        {field.condition === "issue" && (
                          <div className="space-y-2 rounded-md bg-muted p-3">
                            <div className="space-y-1">
                              <Label htmlFor={`notes-${index}`} className="text-xs">
                                Issue Details
                              </Label>
                              <Textarea
                                id={`notes-${index}`}
                                {...register(`items.${index}.notes`)}
                                placeholder="Describe the issue…"
                                className="bg-card"
                              />
                              {errors.items?.[index]?.notes && (
                                <p className="text-xs text-danger">
                                  {errors.items[index]?.notes?.message}
                                </p>
                              )}
                            </div>
                            <div className="space-y-1">
                              <Label className="text-xs">Upload Photo</Label>
                              <label className="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-card px-3 py-2 text-xs text-muted-foreground hover:bg-muted">
                                <Upload className="h-3.5 w-3.5" />
                                {photoUploading === index
                                  ? "Uploading…"
                                  : field.photo_url
                                    ? "Photo attached — tap to replace"
                                    : "Choose a photo"}
                                <input
                                  type="file"
                                  accept="image/*"
                                  className="hidden"
                                  onChange={(e) => {
                                    const file = e.target.files?.[0];
                                    if (file) handlePhotoUpload(index, file);
                                  }}
                                />
                              </label>
                            </div>
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>

      <Button
        type="submit"
        variant="accent"
        size="lg"
        disabled={submitting}
        className="w-full"
      >
        {submitting ? (
          <>
            <Loader2 className="h-4 w-4 animate-spin" />
            Submitting…
          </>
        ) : (
          "Submit Checklist"
        )}
      </Button>
    </form>
  );
}
