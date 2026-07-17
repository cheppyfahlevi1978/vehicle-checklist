import { z } from "zod";

export const checklistItemSchema = z
  .object({
    category: z.enum(["exterior", "interior", "amenities", "engine"]),
    item_name: z.string().min(1),
    condition: z.enum(["good", "issue"]),
    notes: z.string().optional(),
    photo_url: z.string().optional(),
  })
  .superRefine((item, ctx) => {
    if (item.condition === "issue" && !item.notes?.trim()) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Please describe the issue.",
        path: ["notes"],
      });
    }
  });

export const checklistFormSchema = z.object({
  vehicle_id: z.string().uuid(),
  fuel_level: z.enum(["full", "3/4", "1/2", "1/4", "empty"], {
    message: "Select a fuel/battery level.",
  }),
  items: z.array(checklistItemSchema).min(1),
});

export type ChecklistFormValues = z.infer<typeof checklistFormSchema>;
export type ChecklistItemValues = z.infer<typeof checklistItemSchema>;
