export type UserRole = "driver" | "supervisor" | "engineering";

export type VehicleType = "SUV" | "Van" | "Buggy";

export type VehicleStatus = "available" | "in_use" | "maintenance" | "dirty";

export type FuelLevel = "full" | "3/4" | "1/2" | "1/4" | "empty";

export type ChecklistStatus = "passed" | "issues_found";

export type ChecklistCategory =
  | "exterior"
  | "interior"
  | "amenities"
  | "engine";

export type ItemCondition = "good" | "issue";

export interface AppUser {
  id: string;
  email: string;
  role: UserRole;
  created_at: string;
}

export interface Vehicle {
  id: string;
  plate_number: string;
  name: string;
  type: VehicleType;
  status: VehicleStatus;
  created_at: string;
}

export interface Checklist {
  id: string;
  vehicle_id: string;
  user_id: string;
  fuel_level: FuelLevel;
  status: ChecklistStatus;
  created_at: string;
}

export interface ChecklistItemRecord {
  id: string;
  checklist_id: string;
  category: ChecklistCategory;
  item_name: string;
  condition: ItemCondition;
  notes: string | null;
  photo_url: string | null;
}

export const CHECKLIST_CATEGORIES: {
  key: ChecklistCategory;
  label: string;
  items: string[];
}[] = [
  {
    key: "exterior",
    label: "Exterior",
    items: [
      "Body & Paint",
      "Windshield & Windows",
      "Tires & Wheels",
      "Lights (Head/Tail/Signal)",
      "Mirrors",
    ],
  },
  {
    key: "interior",
    label: "Interior",
    items: [
      "Seats & Upholstery",
      "Dashboard & Controls",
      "Air Conditioning",
      "Carpet & Floor Mats",
      "Seatbelts",
    ],
  },
  {
    key: "amenities",
    label: "Amenities",
    items: [
      "Umbrella",
      "First Aid Kit",
      "Bottled Water",
      "Phone Charger",
      "Fire Extinguisher",
    ],
  },
  {
    key: "engine",
    label: "Engine",
    items: [
      "Engine Oil Level",
      "Coolant Level",
      "Brake Function",
      "Battery Condition",
      "Unusual Noise",
    ],
  },
];

export const FUEL_LEVEL_OPTIONS: { value: FuelLevel; label: string }[] = [
  { value: "full", label: "Full" },
  { value: "3/4", label: "3/4" },
  { value: "1/2", label: "1/2" },
  { value: "1/4", label: "1/4" },
  { value: "empty", label: "Empty" },
];
