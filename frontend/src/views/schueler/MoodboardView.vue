<template>
  <div class="moodboard-container">
    <h1>Moodboard</h1>

    <div class="mood-card">
      <h2>Wie ist deine Lernmotivation heute?</h2>

      <div class="emoji-row">
        <span
          class="emoji"
          :class="{ selected: selectedMood === 'motiviert' }"
          @click="selectMood('motiviert')"
        >😎</span>

        <span
          class="emoji"
          :class="{ selected: selectedMood === 'neutral' }"
          @click="selectMood('neutral')"
        >😐</span>

        <span
          class="emoji"
          :class="{ selected: selectedMood === 'müde' }"
          @click="selectMood('müde')"
        >😴</span>
      </div>

      <textarea
        v-model="message"
        placeholder="Fügen eine Nachricht hinzu (optional):"
      ></textarea>

      <button @click="saveMood">Speichern</button>
    </div>

    <p v-if="saved" class="saved-message">✅ Stimmung gespeichert!</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const selectedMood = ref(null)
const message = ref('')
const saved = ref(false)

function selectMood(mood) {
  selectedMood.value = mood
  saved.value = false
}

function saveMood() {
  if (!selectedMood.value) {
    alert('Bitte wähle zuerst eine Stimmung aus.')
    return
  }

  console.log('Mood gespeichert:', {
    stimmung: selectedMood.value,
    nachricht: message.value,
  })

  saved.value = true
  message.value = ''
}
</script>

<style scoped>
.moodboard-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  font-family: "Inter", sans-serif;
  margin-top: 3rem;
}

h1 {
  font-size: 1.8rem;
  margin-bottom: 2rem;
  font-weight: 600;
  color: var(--text);
}

.mood-card {
  width: 90%;
  max-width: 500px;
  background-color: var(--first-background-color);
  border: 1.5px solid #ccc;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

h2 {
  font-size: 1.2rem;
  margin-bottom: 1.5rem;
  color: var(--text);
}

.emoji-row {
  display: flex;
  justify-content: center;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.emoji {
  font-size: 2.5rem;
  cursor: pointer;
  transition: transform 0.2s, filter 0.2s;
}

.emoji:hover {
  transform: scale(1.1);
}

.selected {
  transform: scale(1.3);
  filter: drop-shadow(0 0 8px var(--secondary));
}

textarea {
  width: 100%;
  border-radius: 6px;
  border: 1px solid #ccc;
  padding: 10px;
  font-family: "Inter", sans-serif;
  resize: none;
  margin-bottom: 1rem;
}

button {
  width: 100%;
  padding: 0.8rem;
  border: none;
  border-radius: 10px;
  background: linear-gradient(to right, var(--primary), var(--secondary));
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}

button:hover {
  opacity: 0.9;
}

.saved-message {
  margin-top: 1rem;
  color: var(--primary);
  font-weight: 500;
}
</style>
