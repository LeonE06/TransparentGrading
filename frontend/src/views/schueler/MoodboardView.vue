<template>
  <div class="moodboard-wrapper">
    <h1>Moodboard</h1>

    <div class="mood-card">
      <h2>Wie ist deine Lernmotivation heute?</h2>

      <div class="emoji-row">
        <div class="emoji" :class="{ active: mood === 'gut' }" @click="setMood('gut')">🙂</div>
        <div class="emoji" :class="{ active: mood === 'neutral' }" @click="setMood('neutral')">😐</div>
        <div class="emoji" :class="{ active: mood === 'schlecht' }" @click="setMood('schlecht')">🙁</div>
      </div>

      <select v-if="mood" v-model="note">
        <option disabled value="">Wähle eine passende Antwort aus:</option>
        <option v-for="option in moodOptions[mood]" :key="option">
          {{ option }}
        </option>
      </select>

      <button class="save-btn" @click="saveMood">Speichern</button>
    </div>

    <div class="chart-card">
      <h2>Dein Lern-Mood Verlauf</h2>
      <div class="chart-placeholder">Diagramm per Chart.js …</div>
    </div>

    <p v-if="saved" class="saved-message">Deine Stimmung wurde gespeichert!</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const mood = ref('')
const note = ref('')
const saved = ref(false)

const moodOptions = {
  gut: ['Super motiviert! 💪', 'Voller Energie 🚀', 'Heute läuft’s richtig gut 😄'],
  neutral: ['Geht so… 😐', 'Könnte besser sein 🤷‍♂️', 'Weder gut noch schlecht'],
  schlecht: ['Müde / unmotiviert 😴', 'Konzentration fällt schwer 😞', 'Heute ist kein guter Lerntag 😔']
}

function setMood(m) {
  mood.value = m
  note.value = ''
  saved.value = false
}

async function saveMood() {
  if (!mood.value || !note.value) {
    alert('Bitte wähle Stimmung UND Antwort aus.')
    return
  }

  await fetch('https://transparentgrading.onrender.com/api/mood', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      schueler_id: 1,
      mood: mood.value
    })
  })

  saved.value = true
}
</script>

<style scoped>
.moodboard-wrapper {
  max-width: 900px;
  margin: 2rem auto;
  font-family: "Inter", sans-serif;
}

h1 { font-size: 2rem; margin-bottom: 1.5rem; }
h2 { font-size: 1.3rem; margin-bottom: 1.5rem; }

.mood-card, .chart-card {
  background: var(--first-background-color);
  border-radius: 14px;
  padding: 2rem;
  margin-bottom: 2rem;
  border: 1px solid #ddd;
}

.emoji-row {
  display: flex;
  justify-content: center;
  gap: 3rem;
  margin-bottom: 1.5rem;
}

.emoji {
  font-size: 3.5rem;
  cursor: pointer;
  opacity: 0.5;
}

.emoji.active {
  opacity: 1;
  transform: scale(1.2);
  filter: drop-shadow(0 0 8px var(--primary));
}

select {
  width: 100%;
  padding: 0.8rem;
  border-radius: 10px;
  border: 1px solid #ccc;
  margin-bottom: 1.5rem;
}

.save-btn {
  width: 100%;
  padding: 0.9rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  background: linear-gradient(to right, var(--primary), var(--secondary));
  color: white;
}

.chart-placeholder {
  background: #fafafa;
  padding: 2rem;
  border-radius: 10px;
  border: 1px dashed #bbb;
  text-align: center;
}

.saved-message {
  color: var(--primary);
  font-weight: 600;
  text-align: center;
}
</style>
