export type SessionStatus =
  | "checked_in"
  | "parked"
  | "requested"
  | "checked_out"
  | "incident";

export type DamageSeverity = "minor" | "moderate" | "major";

export type InspectionStage = "checkin" | "checkout";

export interface InspectionSession {
  id: string;
  plate: string;
  guest_name: string;
  room_number: string;
  status: SessionStatus;
  valet_name: string;
  parking_zone: string | null;
  fuel_level: number | null; // 0-1
  odometer: number | null;
  checkin_signed_at: string | null;
  checkout_signed_at: string | null;
  checked_in_at: string;
  checked_out_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface VehiclePhoto {
  id: string;
  session_id: string;
  stage: InspectionStage;
  label: string;
  storage_path: string;
  created_at: string;
}

export interface DamageMark {
  id: string;
  session_id: string;
  point: string;
  severity: DamageSeverity;
  note: string | null;
  photo_path: string | null;
  found_at_stage: InspectionStage;
  created_at: string;
}
