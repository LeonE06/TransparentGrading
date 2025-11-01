<template>
  <div class="modal-backdrop" @click.self="close">
    <div class="modal">
      <h2>Neues Klasse erstellen</h2>

      <!-- Klassenbezeichnung -->
      <div class="form-group">
        <label for="className">Klassenbezeichnung</label>
        <input
          id="className"
          v-model="className"
          type="text"
          placeholder="z.B. 4AI"
        />
      </div>

      <!-- Schüler*innen hinzufügen -->
      <div class="form-group">
        <label>Schüler*innen hinzufügen</label>
        <input
          v-model="studentSearch"
          @input="searchStudents"
          type="text"
          placeholder="Schüler*innen suchen und hinzufügen..."
        />
        <!-- Suchergebnisse -->
        <ul v-if="searchResults.length > 0" class="search-results">
          <li
            v-for="student in searchResults"
            :key="student.id"
            @click="addStudent(student)"
          >
            {{ student.vorname }} {{ student.nachname }}
          </li>
        </ul>

        <!-- Ausgewählte Schüler -->
        <div class="selected-students">
          <div
            v-for="student in selectedStudents"
            :key="student.id"
            class="student-chip"
          >
            {{ student.vorname }} {{ student.nachname }}
            <button class="remove-btn" @click="removeStudent(student.id)">×</button>
          </div>
        </div>
      </div>

      <!-- Aktionen -->
      <div class="actions">
        <button class="cancel" @click="close">Abbrechen</button>
        <button class="create" @click="createClass" :disabled="loading">
          {{ loading ? 'Erstelle...' : 'Klasse erstellen' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import debounce from 'lodash/debounce'

// Props / Emits
const emit = defineEmits(['close', 'created'])

// Reaktive States
const className = ref('')
const studentSearch = ref('')
const searchResults = ref([])
const selectedStudents = ref([])
const loading = ref(false)

// Schüler suchen (debounced)
const searchStudents = debounce(async () => {
  if (!studentSearch.value.trim()) {
    searchResults.value = []
    return
  }

  try {
    const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/students?search=${studentSearch.value}`)
    searchResults.value = response.data
  } catch (err) {
    console.error('Fehler bei der Schülersuche:', err)
  }
}, 300)

// Schüler hinzufügen
function addStudent(student) {
  if (!selectedStudents.value.find(s => s.id === student.id)) {
    selectedStudents.value.push(student)
  }
  searchResults.value = []
  studentSearch.value = ''
}

// Schüler entfernen
function removeStudent(id) {
  selectedStudents.value = selectedStudents.value.filter(s => s.id !== id)
}

// Klasse erstellen
async function createClass() {
  if (!className.value.trim()) {
    alert('Bitte gib einen Klassennamen ein.')
    return
  }

  loading.value = true
  try {
   await axios.post('/api/classes', {
  name: className.value,
  students: selectedStudents.value.map(s => s.id),
})
    emit('created')
  } catch (err) {
    console.error('Fehler beim Erstellen der Klasse:', err)
    alert('Fehler beim Erstellen der Klasse.')
  } finally {
    loading.value = false
  }
}

// Modal schließen
function close() {
  emit('close')
}
</script>

<style scoped>
/* Hintergrund */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.3);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

/* Fenster */
.modal {
  background-color: white;
  border-radius: 12px;
  padding: 2rem;
  width: 600px;
  max-width: 90%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Eingabefelder */
.form-group {
  margin-bottom: 1.5rem;
}

label {
  font-weight: 600;
  display: block;
  margin-bottom: 0.3rem;
}

input {
  width: 100%;
  padding: 0.6rem;
  border: 1px solid #ccc;
  border-radius: 8px;
}

/* Schüler-Suchergebnisse */
.search-results {
  background-color: #fff;
  border: 1px solid #ccc;
  border-radius: 8px;
  margin-top: 0.4rem;
  max-height: 180px;
  overflow-y: auto;
  list-style: none;
  padding: 0;
}

.search-results li {
  padding: 0.5rem 0.8rem;
  cursor: pointer;
}

.search-results li:hover {
  background-color: #f0f4ff;
}

/* Chips */
.selected-students {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.6rem;
}

.student-chip {
  background-color: #f1f3f9;
  border: 1px solid #d3daf3;
  border-radius: 20px;
  padding: 0.4rem 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.remove-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
}

/* Aktionen */
.actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.8rem;
  margin-top: 1rem;
}

.cancel {
  background-color: #f0f0f0;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
}

.create {
  background: linear-gradient(90deg, #4a90e2, #6675ff);
  border: none;
  color: white;
  padding: 0.5rem 1.2rem;
  border-radius: 8px;
  cursor: pointer;
}

.create:disabled {
  background: #a8b5ff;
  cursor: not-allowed;
}
</style>
