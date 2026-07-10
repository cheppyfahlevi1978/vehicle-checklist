export type SessionStatus =
  | "checked_in"
  | "parked"
  | "requested"
  | "checked_out"
  | "incident";

export type DamageSeverity = "minor" | "moderate" | "major";

export interface DamageMark {
  id: string;
  point: string; // e.g. "front-bumper", "rear-door-left"
  severity: DamageSeverity;
  note?: string;
  photoUrl?: string;
}

export interface ChecklistCategory {
  id: string;
  label: string;
  items: ChecklistItem[];
}

export interface ChecklistItem {
  id: string;
  label: string;
  checked: boolean;
}

export interface InspectionSession {
  id: string;
  plate: string;
  guestName: string;
  roomNumber: string;
  status: SessionStatus;
  valetName: string;
  parkingZone?: string;
  checkedInAt: string;
  checkedOutAt?: string;
  fuelLevel?: number; // 0-1
  odometer?: number;
  damageMarks: DamageMark[];
}
