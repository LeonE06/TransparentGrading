<template>
  <div class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ student.vorname }} {{ student.nachname }} bearbeiten</h2>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div class="modal-body">
        <!-- Vorname -->
        <label for="vorname">Vorname</label>
        <input id="vorname" type="text" :value="student.vorname" readonly />

        <!-- Nachname -->
        <label for="nachname">Nachname</label>
        <input id="nachname" type="text" :value="student.nachname" readonly />

        <!-- Email -->
        <label for="email">Email</label>
        <input id="email" type="email" :value="student.email" readonly />

        <!-- Klasse ändern -->
        <label for="klasse">Klasse ändern</label>
        <select id="klasse" v-model="selectedClass">
          <option disabled value="">Bitte auswählen</option>
          <option v-for="k in classes" :key="k.id" :value="k.name">
            {{ k.name }}
          </option>
        </select>
      </div>

      <div class="modal-footer">
        <button class="cancel-btn" @click="$emit('close')">Abbrechen</button>
        <button class="save-btn" @click="saveChanges">Änderungen speichern</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  student: {
    type: Object,
    required: true
  }
})
const emit = defineEmits(['close', 'updated'])

// API setup
const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''
const apiPrefix = isDev ? '' : `${apiBase}/api`

// State
const classes = ref([])
const selectedClass = ref(props.student.klassenname || '')

// 🔹 Klassen laden
async function loadClasses() {
  try {
    const res = await axios.get(`${apiPrefix}/classes`)
    classes.value = res.data
  } catch (err) {
    console.error('❌ Fehler beim Laden der Klassen:', err)
  }
}

// 🔹 Änderungen speichern
async function saveChanges() {
  try {
    await axios.put(`${apiPrefix}/students/${props.student.schueler_id}`, {
      klasse: selectedClass.value
    })
    emit('updated') // 🔹 Meldet Erfolg an Parent-Komponente
    emit('close')   // 🔹 Schließt Modal danach
  } catch (err) {
    console.error('❌ Fehler beim Speichern der Änderungen:', err)
    alert('Fehler beim Speichern der Änderungen.')
  }
}

onMounted(loadClasses)
</script>

<style scoped>
/* Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

/* Modal */
.modal {
  background: #fff;
  border-radius: 12px;
  width: 600px;
  max-width: 90%;
  padding: 1.5rem 2rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

/* Header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-header h2 {
  font-size: 1.4rem;
  font-weight: 700;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
}

/* Body */
.modal-body {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

label {
  font-weight: 600;
  margin-top: 0.8rem;
}

input,
select {
  width: 100%;
  padding: 0.6rem;
  border-radius: 8px;
  border: 1px solid #ddd;
  background-color: #f9f9f9;
  outline: none;
  transition: border-color 0.2s;
}

input[readonly] {
  color: #666;
  background-color: #f2f2f2;
  cursor: not-allowed;
}

select {
  background-color: #fff;
  cursor: pointer;
}

input:focus,
select:focus {
  border-color: #6a16cc;
}

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

.cancel-btn {
  background-color: #e8e8e8;
  color: #333;
  border: none;
  padding: 0.6rem 1.4rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.cancel-btn:hover {
  background-color: #d5d5d5;
}

.save-btn {
  background-image: linear-gradient(to right, #6a16cc, #73a0f1);
  color: white;
  border: none;
  padding: 0.6rem 1.4rem;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.15s;
}

.save-btn:hover {
  transform: scale(1.05);
}
</style>
