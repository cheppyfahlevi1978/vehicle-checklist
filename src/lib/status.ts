import { SessionStatus } from "./types";

export const statusLabel: Record<SessionStatus, string> = {
  checked_in: "Baru masuk",
  parked: "Terparkir",
  requested: "Diminta tamu",
  checked_out: "Selesai",
  incident: "Insiden",
};
