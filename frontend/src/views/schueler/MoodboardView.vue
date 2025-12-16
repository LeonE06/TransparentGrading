<template>
  <div class="moodboard-wrapper">
    <h1>Moodboard</h1>

    <div class="mood-card">
      <h2>Wie ist deine Lernmotivation heute?</h2>

      <!-- ✅ NUR DIESE ZEILE IST GEÄNDERT -->
      <div class="emoji-row">
        <div
          class="emoji"
          :class="{ active: mood === 'gut' }"
          @click="setMood('gut')"
          v-html="getSvg('gut')"
        ></div>

        <div
          class="emoji"
          :class="{ active: mood === 'neutral' }"
          @click="setMood('neutral')"
          v-html="getSvg('neutral')"
        ></div>

        <div
          class="emoji"
          :class="{ active: mood === 'schlecht' }"
          @click="setMood('schlecht')"
          v-html="getSvg('schlecht')"
        ></div>
      </div>

      <select v-if="mood" v-model="note">
        <option disabled value="">Wähle eine passende Antwort aus:</option>
        <option v-for="option in moodOptions[mood]" :key="option">
          {{ option }}
        </option>
      </select>

      <button class="save-btn" @click="saveMood">
        Speichern
      </button>
    </div>

    <div class="chart-card">
      <h2>Dein Lern-Mood Verlauf</h2>
      <div class="chart-placeholder">
        Diagramm per Chart.js …
      </div>
    </div>

    <p v-if="saved" class="saved-message">
      Deine Stimmung wurde gespeichert!
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const mood = ref('')
const note = ref('')
const saved = ref(false)

const moodOptions = {
  gut: [
    'Super motiviert! 💪',
    'Voller Energie 🚀',
    'Heute läuft’s richtig gut 😄'
  ],
  neutral: [
    'Geht so… 😐',
    'Könnte besser sein 🤷‍♂️',
    'Weder gut noch schlecht'
  ],
  schlecht: [
    'Müde / unmotiviert 😴',
    'Konzentration fällt schwer 😞',
    'Heute ist kein guter Lerntag 😔'
  ]
}

function setMood(m) {
  mood.value = m
  note.value = ''
  saved.value = false
}

/* 🔥 SVG-LOGIK */
function getSvg(type) {
  const active = mood.value === type

  if (type === 'gut') {
    return active ? svgGutAktiv : svgGut
  }
  if (type === 'neutral') {
    return active ? svgNeutralAktiv : svgNeutral
  }
  return active ? svgSchlechtAktiv : svgSchlecht
}

/* 🖼️ SVGs – HIER kannst du später 1:1 Laras finale SVGs reinkopieren */
const svgGut = `
<svg width="108" height="108" viewBox="0 0 108 108" xmlns="http://www.w3.org/2000/svg">
  <circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
  <circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
  <circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
  <path d="M28 76.5H83" stroke="#B6B6B6" stroke-width="3"/>
</svg>
`

const svgGutAktiv = `
<svg width="108" height="108" viewBox="0 0 108 108" xmlns="http://www.w3.org/2000/svg">
  <circle cx="54" cy="54" r="52.5" stroke="var(--primary)" stroke-width="3"/>
  <circle cx="31.5" cy="40.5" r="4" stroke="var(--primary)" stroke-width="3"/>
  <circle cx="75.5" cy="40.5" r="4" stroke="var(--primary)" stroke-width="3"/>
  <path d="M28 76.5H83" stroke="var(--primary)" stroke-width="3"/>
</svg>
`

const svgNeutral = svgGut
const svgNeutralAktiv = svgGutAktiv
const svgSchlecht = svgGut
const svgSchlechtAktiv = svgGutAktiv

/* ✅ 401 FIX: COOKIE AUTH */
async function saveMood() {
  if (!mood.value || !note.value) {
    alert('Bitte wähle Stimmung UND Antwort aus.')
    return
  }

  await fetch('https://transparentgrading.onrender.com/api/mood', {
    method: 'POST',
    credentials: 'include', // 🔥 DAS IST DER FIX
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
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

h1 {
  font-size: 2rem;
  margin-bottom: 1.5rem;
}

h2 {
  font-size: 1.3rem;
  margin-bottom: 1.5rem;
}

.mood-card,
.chart-card {
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
  width: 108px;
  height: 108px;
  cursor: pointer;
  opacity: 0.5;
  transition: 0.2s;
}

.emoji.active {
  opacity: 1;
  transform: scale(1.15);
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
