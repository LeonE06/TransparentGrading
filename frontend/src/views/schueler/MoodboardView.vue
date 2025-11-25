<template>
  <div class="moodboard-wrapper">
    <h1>Moodboard</h1>

    <!-- Mood Auswahl Card -->
    <div class="mood-card">
      <h2>Wie ist deine Lernmotivation heute?</h2>

      <div class="emoji-row">
        <div
          class="emoji emoji-good"
          :class="{ active: mood === 'gut' }"
          @click="setMood('gut')"
        >
          🙂
        </div>

        <div
          class="emoji emoji-neutral"
          :class="{ active: mood === 'neutral' }"
          @click="setMood('neutral')"
        >
          😐
        </div>

        <div
          class="emoji emoji-bad"
          :class="{ active: mood === 'schlecht' }"
          @click="setMood('schlecht')"
        >
          🙁
        </div>
      </div>

      <select v-model="note">
        <option disabled value="">Wähle eine passende Antwort aus:</option>
        <option>Super motiviert! 💪</option>
        <option>Geht so… 😐</option>
        <option>Müde / unmotiviert 😴</option>
      </select>

      <button class="save-btn" @click="saveMood">Speichern</button>
    </div>

    <!-- Mood Verlauf Placeholder -->
    <div class="chart-card">
      <h2>Dein Lern-Mood Verlauf</h2>

      <div class="chart-placeholder">
        Diagramm per Chart.js …
      </div>
    </div>

    <p v-if="saved" class="saved-message">Deine Stimmung wurde gespeichert!</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const mood = ref('')
const note = ref('')
const saved = ref(false)

function setMood(m) {
  mood.value = m
  saved.value = false
}

function saveMood() {
  if (!mood.value) {
    alert("Bitte wähle zuerst deine Stimmung aus.")
    return
  }

  console.log("Mood gespeichert:", {
    stimmung: mood.value,
    antwort: note.value
  })

  saved.value = true
}
</script>

<style scoped>
/* --- Basic page layout --- */
.moodboard-wrapper {
  max-width: 900px;
  margin: 2rem auto;
  font-family: "Inter", sans-serif;
}

h1 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

h2 {
  font-size: 1.3rem;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

/* --- Card Styles --- */
.mood-card,
.chart-card {
  background: var(--first-background-color);
  border-radius: 14px;
  padding: 2rem;
  margin-bottom: 2rem;
  border: 1px solid #ddd;
}

/* --- Emoji Row --- */
.emoji-row {
  display: flex;
  justify-content: center;
  margin-bottom: 1.5rem;
  gap: 3rem;
}

.emoji {
  font-size: 3.5rem;
  cursor: pointer;
  transition: 0.2s;
  opacity: 0.5;
}

.emoji.active {
  opacity: 1;
  transform: scale(1.2);
  filter: drop-shadow(0 0 8px var(--primary));
}

/* --- Dropdown --- */
select {
  width: 100%;
  padding: 0.8rem;
  border-radius: 10px;
  border: 1px solid #ccc;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
}

/* --- Button --- */
.save-btn {
  width: 100%;
  padding: 0.9rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  background: linear-gradient(to right, var(--primary), var(--secondary));
  color: white;
  cursor: pointer;
  transition: 0.2s;
}

.save-btn:hover {
  opacity: 0.9;
}

/* --- Chart Placeholder --- */
.chart-placeholder {
  background: #fafafa;
  padding: 2rem;
  border-radius: 10px;
  border: 1px dashed #bbb;
  text-align: center;
  color: #666;
  font-size: 1rem;
}

/* Saved message */
.saved-message {
  color: var(--primary);
  font-weight: 600;
  text-align: center;
}
</style>
