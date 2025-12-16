<template>
  <div class="moodboard-wrapper">
    <h1>Moodboard</h1>

    <!-- Mood Auswahl -->
    <div class="mood-card">
      <h2>Wie ist deine Lernmotivation heute?</h2>

      <div class="emoji-row">
        <div
          class="emoji"
          :class="{ active: mood === 'gut' }"
          @click="setMood('gut')"
          v-html="getMoodSvg('gut')"
        />
        <div
          class="emoji"
          :class="{ active: mood === 'neutral' }"
          @click="setMood('neutral')"
          v-html="getMoodSvg('neutral')"
        />
        <div
          class="emoji"
          :class="{ active: mood === 'schlecht' }"
          @click="setMood('schlecht')"
          v-html="getMoodSvg('schlecht')"
        />
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

    <!-- Verlauf (für später) -->
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
import { ref, computed } from 'vue'

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

/* Darkmode-Erkennung */
const isDark = computed(() =>
  document.documentElement.classList.contains('dark')
)

/*
REIHENFOLGE:
0–2  light inaktiv
3–5  light aktiv
6–8  dark inaktiv
9–11 dark aktiv
*/
const svgs = [
/* 0 light gut */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none"
xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="32" cy="40" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="76" cy="40" r="4" stroke="#B6B6B6" stroke-width="3"/>
<line x1="28" y1="76" x2="83" y2="76" stroke="#B6B6B6" stroke-width="3"/>
</svg>`,

/* 1 light neutral */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none"
xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="32" cy="40" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="76" cy="40" r="4" stroke="#B6B6B6" stroke-width="3"/>
<path d="M28 83C28 75 40 69 54 69C68 69 80 75 80 83"
stroke="#B6B6B6" stroke-width="3"/>
</svg>`,

/* 2 light schlecht */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none"
xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="32" cy="40" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="76" cy="40" r="4" stroke="#B6B6B6" stroke-width="3"/>
<path d="M81 70C81 78 69 84 55 84C41 84 29 78 29 70"
stroke="#B6B6B6" stroke-width="3"/>
<line x1="27" y1="68" x2="83" y2="68" stroke="#B6B6B6" stroke-width="3"/>
</svg>`,

/* 3 light gut aktiv */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none"
xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#4CAF50" stroke-width="3"/>
<circle cx="32" cy="40" r="4" stroke="#4CAF50" stroke-width="3"/>
<circle cx="76" cy="40" r="4" stroke="#4CAF50" stroke-width="3"/>
<line x1="28" y1="76" x2="83" y2="76" stroke="#4CAF50" stroke-width="3"/>
</svg>`,

/* 4 light neutral aktiv */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none"
xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#FFC107" stroke-width="3"/>
<circle cx="32" cy="40" r="4" stroke="#FFC107" stroke-width="3"/>
<circle cx="76" cy="40" r="4" stroke="#FFC107" stroke-width="3"/>
<path d="M28 83C28 75 40 69 54 69C68 69 80 75 80 83"
stroke="#FFC107" stroke-width="3"/>
</svg>`,

/* 5 light schlecht aktiv */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none"
xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#F44336" stroke-width="3"/>
<circle cx="32" cy="40" r="4" stroke="#F44336" stroke-width="3"/>
<circle cx="76" cy="40" r="4" stroke="#F44336" stroke-width="3"/>
<path d="M81 70C81 78 69 84 55 84C41 84 29 78 29 70"
stroke="#F44336" stroke-width="3"/>
<line x1="27" y1="68" x2="83" y2="68" stroke="#F44336" stroke-width="3"/>
</svg>`,

/* 6–11 dark (gleiche Formen, helle Farbe) */
...Array(6).fill('').map((_,i)=>svgs[i].replace(/#B6B6B6|#4CAF50|#FFC107|#F44336/g,'#F7F7F7'))
]

function getMoodSvg(m) {
  const base = { gut: 0, neutral: 1, schlecht: 2 }[m]
  const active = mood.value === m ? 3 : 0
  const dark = isDark.value ? 6 : 0
  return svgs[base + active + dark]
}

function setMood(m) {
  mood.value = m
  note.value = ''
  saved.value = false
}

async function saveMood() {
  const token = localStorage.getItem('token')
  if (!token) return alert('Nicht eingeloggt')

  await fetch('https://transparentgrading.onrender.com/api/mood', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: 'Bearer ' + token
    },
    body: JSON.stringify({ mood: mood.value })
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
  transform: scale(1.2);
}

.save-btn {
  width: 100%;
  padding: 0.9rem;
  border-radius: 12px;
  font-weight: 600;
  background: linear-gradient(to right, var(--primary), var(--secondary));
  color: white;
  border: none;
}
</style>
