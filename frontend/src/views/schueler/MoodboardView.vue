<template>
  <div class="moodboard-wrapper">
    <h1>Moodboard</h1>

    <div class="mood-card">
      <h2>Wie ist deine Lernmotivation heute?</h2>

      <div class="emoji-row">
        <div
          v-for="m in moods"
          :key="m"
          class="emoji-svg"
          @click="setMood(m)"
          v-html="getMoodSvg(m)"
        />
      </div>

      <button class="save-btn" @click="saveMood">
        Speichern
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'

const mood = ref('')

const moods = ['gut', 'neutral', 'schlecht']

const isDark = computed(() =>
  document.documentElement.classList.contains('dark')
)

/* 🔥 ALLE SVGs INLINE */
const svgs = [

/* 0 – light gut */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<line x1="28" y1="76.5" x2="83" y2="76.5" stroke="#B6B6B6" stroke-width="3"/>
</svg>`,

/* 1 – light neutral */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<path d="M28 83C28 75.268 39.6 69 54 69C68.4 69 80 75.268 80 83" stroke="#B6B6B6" stroke-width="3"/>
</svg>`,

/* 2 – light schlecht */
`<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<path d="M81 70C81 77.7 69.4 84 55 84C40.6 84 29 77.7 29 70" stroke="#B6B6B6" stroke-width="3"/>
<line x1="27.5" y1="68.5" x2="82.5" y2="68.5" stroke="#B6B6B6" stroke-width="3"/>
</svg>`,

/* 3–5 light aktiv (Gradient) */
/* 6–11 dark (gleiche Reihenfolge, andere Farben) */

/* 👉 HIER einfach die restlichen SVGs von Lara 1:1 einfügen */
]

function setMood(m) {
  mood.value = m
}

function getMoodSvg(m) {
  const baseIndex = { gut: 0, neutral: 1, schlecht: 2 }[m]
  const activeOffset = mood.value === m ? 3 : 0
  const darkOffset = isDark.value ? 6 : 0

  return svgs[baseIndex + activeOffset + darkOffset]
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
}
</script>
<style scoped>
.emoji-row {
  display: flex;
  justify-content: center;
  gap: 2.5rem;
}

.emoji-svg {
  width: 108px;
  height: 108px;
  cursor: pointer;
  transition: transform 0.2s;
}

.emoji-svg:hover {
  transform: scale(1.1);
}
</style>
