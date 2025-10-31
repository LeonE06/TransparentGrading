<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal">
      <div class="header">
        <h2>Neue Klasse erstellen</h2>
        <button class="close-btn" @click="close">×</button>
      </div>

      <!-- Klassenbezeichnung -->
      <label class="label">Klassenbezeichnung</label>
      <input
        v-model="className"
        type="text"
        placeholder="z. B. 4AI"
        class="input"
      />

      <!-- Schüler*innen hinzufügen -->
      <label class="label">Schüler*innen hinzufügen</label>
      <input
        v-model="searchTerm"
        type="text"
        placeholder="Schüler*innen suchen und hinzufügen..."
        class="input"
        @input="searchStudents"
      />

      <!-- Suchergebnisse -->
      <div v-if="searchResults.length" class="search-results">
        <div
          v-for="student in searchResults"
          :key="student.id"
          class="search-item"
          @click="addStudent(student)"
        >
          {{ student.vorname }} {{ student.nachname }}
        </div>
      </div>

      <!-- Ausgewählte Schüler*innen -->
      <div v-if="selectedStudents.length" class="selected-list">
        <div
          v-for="student in selectedStudents"
          :key="student.id"
          class="selected-item"
        >
          {{ student.vorname }} {{ student.nachname }}
          <button class="remove-btn" @click="removeStudent(student)">×</button>
        </div>
      </div>

      <!-- Aktionen -->
      <div class="actions">
        <button class="btn cancel" @click="close">Abbrechen</button>
        <button class="btn create" @click="createClass">Klasse erstellen</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import debounce from 'lodash.debounce'

const emit = defineEmits(['close', 'created'])

const className = ref('')
const searchTerm = ref('')
const searchResults = ref([])
const selectedStudents = ref([])

const close = () => emit('close')

// 🔍 Schüler suchen (mit debounce)
const searchStudents = debounce(async () => {
  if (searchTerm.value.trim().length < 2) {
    searchResults.value = []
    return
  }

  try {
    const response = await axios.get('/api/students', {
      params: { search: searchTerm.value }
    })
    searchResults.value = response.data
  } catch (error) {
    console.error('Fehler beim Suchen:', error)
    searchResults.value = []
  }
}, 300)

// ➕ Schüler hinzufügen
const addStudent = (student) => {
  if (!selectedStudents.value.find(s => s.id === student.id)) {
    selectedStudents.value.push(student)
  }
  searchResults.value = []
  searchTerm.value = ''
}

// ➖ Schüler entfernen
const removeStudent = (student) => {
  selectedStudents.value = selectedStudents.value.filter(s => s.id !== student.id)
}

// 🏗️ Klasse erstellen
const createClass = async () => {
  try {
    await axios.post('/api/classes', {
      name: className.value,
      students: selectedStudents.value.map(s => s.id),
    })
    emit('created')
    close()
  } catch (error) {
    console.error('Fehler beim Erstellen der Klasse:', error)
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 50;
}

.modal {
  background: #f8f8f8;
  border-radius: 12px;
  padding: 2rem;
  width: 600px;
  max-width: 90%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  position: relative;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.close-btn {
  border: none;
  background: none;
  font-size: 1.5rem;
  cursor: pointer;
}

.label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.3rem;
  margin-top: 1rem;
}

.input {
  width: 100%;
  padding: 0.6rem;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.search-results {
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  max-height: 200px;
  overflow-y: auto;
  margin-bottom: 0.5rem;
}

.search-item {
  padding: 0.5rem;
  cursor: pointer;
}
.search-item:hover {
  background: #f0f0f0;
}

.selected-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 0.5rem;
  min-height: 50px;
}

.selected-item {
  display: flex;
  align-items: center;
  background: #e7e7e7;
  border-radius: 20px;
  padding: 0.3rem 0.7rem;
}

.remove-btn {
  margin-left: 6px;
  border: none;
  background: none;
  font-size: 1.1rem;
  cursor: pointer;
}

.actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 1.5rem;
  gap: 0.5rem;
}

.btn {
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  cursor: pointer;
  border: none;
  font-weight: 500;
}

.cancel {
  background: #e0e0e0;
}

.create {
  background: linear-gradient(to right, #6a5af9, #8369f4);
  color: white;
}
</style>
