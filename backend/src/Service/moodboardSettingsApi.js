import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8000",
  // wenn du Cookies/Auth brauchst, aktivieren:
  // withCredentials: true,
});

export async function getMoodboardSettings() {
  const res = await api.get("/api/moodboard/settings");
  return res.data; // { mood_benachrichtigung: true/false }
}

export async function updateMoodboardSettings(mood_benachrichtigung) {
  const res = await api.put("/api/moodboard/settings", { mood_benachrichtigung });
  return res.data; // { status:'ok', mood_benachrichtigung: ... }
}
