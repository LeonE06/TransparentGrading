<template>
  <div class="modal-backdrop" @click.self="close">
    <div class="modal">
      <h2>Neue Klasse erstellen</h2>

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
          class="search"
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

const emit = defineEmits(['close', 'created'])

const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''

// Wenn Dev → direkt über Proxy `/api`
// Wenn Prod → volle URL, aber ohne zusätzliches /api doppeln
const apiPrefix = isDev ? '' : `${apiBase}/api`

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
    const response = await axios.get(`${apiPrefix}/students?search=${studentSearch.value}`)
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
    await axios.post(`${apiPrefix}/classes`, {
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


h2 {
  font-size: 2rem;
  margin-bottom: 2rem;
  text-align: left;
  font-weight: 650;
}

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
  background-color: var(--first-background-color);
  border-radius: 12px;
  padding: 2rem 5rem;
  width: 800px;
  min-height: 50vh;
  max-width: 90%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Eingabefelder */
.form-group {
  margin-bottom: 1rem;
}

label {
  
  font-weight: 600;
  display: block;
  margin-bottom: 0.3rem;
  text-align: left;;
}

.search{
  padding: 0.8rem 1.6rem;
  padding-left: 3rem;
  border: 1px solid var(--aczent-color);
  border-radius: 6px;
  width: 91%;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  background: var(--second-background-color) url("/searchIcon.svg") no-repeat 15px center;
  background-size: 15px 15px;
}

input {
  padding: 0.8rem 1.6rem;
  border-radius: 6px;
  border:none;
  width: 94%;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  background: var(--second-background-color);
  background-size: 15px 15px;
}

/* Schüler-Suchergebnisse */
.search-results {
  background-color: var(--second-background-color);
  border: 1px solid var(--aczent-color);
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
  background-color: var(--first-background-color);
}

.search-icon {
  color: var(--icon-color);
}

/* Chips */
.selected-students {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  margin-top: 0.6rem;
  
}

.student-chip {
  background-color: var(--second-background-color);
  border: 1px solid var(--second-background-color);
  border-radius: 15px;
  padding: 0.4rem 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-right: 1rem;
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
  border-radius: 20px;
  padding: 0.4rem 0.8rem;
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 16px 10px;
  min-width: 180px;
  background-color: var(--second-background-color);
  color: var(--aczent-color);
  border: none;
}

.create {
  border-radius: 20px;
  padding: 0.4rem 0.8rem;
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 16px 10px;
  min-width: 180px;
  background-image: linear-gradient(to right, var(--primary), var(--secondary));
  color: var(--aczent-color);
  border: none;
}

.create:disabled {
  background: #a8b5ff;
  cursor: not-allowed;
}
</style>
