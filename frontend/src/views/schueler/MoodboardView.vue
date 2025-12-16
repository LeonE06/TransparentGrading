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
const svgSchlecht = `
<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<line x1="28" y1="76.5" x2="83" y2="76.5" stroke="#B6B6B6" stroke-width="3"/>
</svg>
`

const svgSchlechtAktiv = `
<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="url(#paint0_linear_2217_8761)" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="url(#paint1_linear_2217_8761)" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="url(#paint2_linear_2217_8761)" stroke-width="3"/>
<circle cx="54" cy="54" r="52.5" stroke="url(#paint3_linear_2217_8761)" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="url(#paint4_linear_2217_8761)" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="url(#paint5_linear_2217_8761)" stroke-width="3"/>
<line x1="28" y1="76.5" x2="83" y2="76.5" stroke="url(#paint6_linear_2217_8761)" stroke-width="3"/>
<defs>
<linearGradient id="paint0_linear_2217_8761" x1="54" y1="0" x2="54" y2="108" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint1_linear_2217_8761" x1="31.5" y1="35" x2="31.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint2_linear_2217_8761" x1="75.5" y1="35" x2="75.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint3_linear_2217_8761" x1="54" y1="0" x2="54" y2="108" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint4_linear_2217_8761" x1="31.5" y1="35" x2="31.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint5_linear_2217_8761" x1="75.5" y1="35" x2="75.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint6_linear_2217_8761" x1="55.5" y1="78" x2="55.5" y2="79" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
</defs>
</svg>
`

const svgNeutral = `<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<path d="M28 83C28 75.268 39.6406 69 54 69C68.3594 69 80 75.268 80 83" stroke="#B6B6B6" stroke-width="3"/>
</svg>`
const svgNeutralAktiv = `<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="url(#paint0_linear_2217_8753)" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="url(#paint1_linear_2217_8753)" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="url(#paint2_linear_2217_8753)" stroke-width="3"/>
<circle cx="54" cy="54" r="52.5" stroke="url(#paint3_linear_2217_8753)" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="url(#paint4_linear_2217_8753)" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="url(#paint5_linear_2217_8753)" stroke-width="3"/>
<path d="M28 83C28 75.268 39.6406 69 54 69C68.3594 69 80 75.268 80 83" stroke="url(#paint6_linear_2217_8753)" stroke-width="3"/>
<defs>
<linearGradient id="paint0_linear_2217_8753" x1="54" y1="0" x2="54" y2="108" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint1_linear_2217_8753" x1="31.5" y1="35" x2="31.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint2_linear_2217_8753" x1="75.5" y1="35" x2="75.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint3_linear_2217_8753" x1="54" y1="0" x2="54" y2="108" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint4_linear_2217_8753" x1="31.5" y1="35" x2="31.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint5_linear_2217_8753" x1="75.5" y1="35" x2="75.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint6_linear_2217_8753" x1="54" y1="83" x2="54" y2="69" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
</defs>
</svg>`
const svgGut = `<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<path d="M81 70C81 77.732 69.3594 84 55 84C40.6406 84 29 77.732 29 70" stroke="#B6B6B6" stroke-width="3"/>
<path d="M27.5 68.5H82.5" stroke="#B6B6B6" stroke-width="3"/>
</svg>`
const svgGutAktiv = `<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="url(#paint0_linear_2217_8769)" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="url(#paint1_linear_2217_8769)" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="url(#paint2_linear_2217_8769)" stroke-width="3"/>
<circle cx="54" cy="54" r="52.5" stroke="url(#paint3_linear_2217_8769)" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="url(#paint4_linear_2217_8769)" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="url(#paint5_linear_2217_8769)" stroke-width="3"/>
<path d="M81 70C81 77.732 69.3594 84 55 84C40.6406 84 29 77.732 29 70" stroke="url(#paint6_linear_2217_8769)" stroke-width="3"/>
<path d="M27.5 68.5H82.5" stroke="url(#paint7_linear_2217_8769)" stroke-width="3"/>
<defs>
<linearGradient id="paint0_linear_2217_8769" x1="54" y1="0" x2="54" y2="108" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint1_linear_2217_8769" x1="31.5" y1="35" x2="31.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint2_linear_2217_8769" x1="75.5" y1="35" x2="75.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint3_linear_2217_8769" x1="54" y1="0" x2="54" y2="108" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint4_linear_2217_8769" x1="31.5" y1="35" x2="31.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint5_linear_2217_8769" x1="75.5" y1="35" x2="75.5" y2="46" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint6_linear_2217_8769" x1="55" y1="70" x2="55" y2="84" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
<linearGradient id="paint7_linear_2217_8769" x1="55" y1="68.5" x2="55" y2="69.5" gradientUnits="userSpaceOnUse">
<stop stop-color="#6A16CC"/>
<stop offset="1" stop-color="#73A0F1"/>
</linearGradient>
</defs>
</svg>`

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
