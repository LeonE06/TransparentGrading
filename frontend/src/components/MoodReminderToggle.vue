<template>
  <div class="setting-row">
    <div class="text">
      <h3>Mood-Erinnerung</h3>
      <p class="sub">
        Du kannst selbst entscheiden, ob du erinnert werden möchtest.
      </p>
    </div>

    <label class="switch">
      <input type="checkbox" :checked="enabled" @change="onToggle" />
      <span class="slider"></span>
    </label>
  </div>

  <p v-if="msg" class="msg">{{ msg }}</p>
</template>

<script>
import { getMoodboardSettings, updateMoodboardSettings } from "@/services/moodboardSettingsApi";

export default {
  name: "MoodReminderToggle",
  data() {
    return {
      enabled: true,
      msg: "",
      saving: false,
      loaded: false,
    };
  },
  async mounted() {
    try {
      const data = await getMoodboardSettings();
      this.enabled = !!data.mood_benachrichtigung;
      this.loaded = true;
    } catch (e) {
      this.msg = "Einstellung konnte nicht geladen werden.";
    }
  },
  methods: {
    async onToggle(e) {
      const next = e.target.checked;

      // Direkt UI setzen
      this.enabled = next;

      if (this.saving) return;
      this.saving = true;

      try {
        await updateMoodboardSettings(next);
        this.msg = next ? "Mood-Erinnerung aktiviert ✅" : "Mood-Erinnerung deaktiviert 🔕";
      } catch (e) {
        // rollback
        this.enabled = !next;
        this.msg = "Speichern fehlgeschlagen.";
      } finally {
        this.saving = false;
        setTimeout(() => (this.msg = ""), 1800);
      }
    },
  },
};
</script>

<style scoped>
.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 0;
}
.text h3 {
  margin: 0;
}
.sub {
  margin: 6px 0 0;
  opacity: 0.8;
  font-size: 0.9rem;
}
.msg {
  margin-top: 8px;
  font-size: 0.95rem;
}

/* Simple Switch */
.switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 28px;
}
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.slider {
  position: absolute;
  inset: 0;
  cursor: pointer;
  border-radius: 999px;
  background: #ccc;
  transition: 0.2s;
}
.slider:before {
  content: "";
  position: absolute;
  height: 22px;
  width: 22px;
  left: 3px;
  bottom: 3px;
  background: #fff;
  border-radius: 50%;
  transition: 0.2s;
}
input:checked + .slider {
  background: #4caf50;
}
input:checked + .slider:before {
  transform: translateX(20px);
}
</style>
