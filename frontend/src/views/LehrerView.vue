<template>
  <div class="lehrer-view">
    <!-- Überschrift -->
    <h1 class="title">Alle Lehrer*innen</h1>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="left-controls">
        <button class="btn" @click="loadTeachers">Alle</button>
        <button class="btn" @click="sortByName">Sortiert A-Z</button>
      </div>
    </div>

    <!-- Suchfeld -->
    <input
      v-model="searchTerm"
      type="text"
      class="search-input"
      placeholder="Nach Lehrer*in suchen..."
    />

    <!-- Lehrer-Liste -->
    <div class="teacher-list">
      <div v-if="loading" class="loading">⏳ Lade Lehrer...</div>

      <div v-else-if="filteredTeachers.length === 0">
        <p>Keine Lehrer*innen gefunden.</p>
      </div>

      <table v-else class="teacher-table">
        <thead>
          <tr>
            <th>Vorname</th>
            <th>Nachname</th>
            <th>Email</th>
            <th style="text-align: right;">Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in filteredTeachers" :key="t.id">
            <td>{{ t.vorname }}</td>
            <td>{{ t.nachname }}</td>
            <td>{{ t.email }}</td>
            <td class="class-actions">
              <button class="edit-btn" @click="deleteTeacher(t.id)">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22H15C20 22 22 20 22 15V13" stroke="#292D32"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  <path
                    d="M16.0399 3.02001L8.15988 10.9C7.85988 11.2 7.55988 11.79 7.49988 12.22L7.06988 15.23C6.90988 16.32 7.67988 17.08 8.76988 16.93L11.7799 16.5C12.1999 16.44 12.7899 16.14 13.0999 15.84L20.9799 7.96001C22.3399 6.60001 22.9799 5.02001 20.9799 3.02001C18.9799 1.02001 17.3999 1.66001 16.0399 3.02001Z"
                    stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
                  <path d="M14.9102 4.15002C15.5802 6.54002 17.4502 8.41002 19.8502 9.09002" stroke="#292D32"
                    stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''
const apiPrefix = isDev ? '' : `${apiBase}/api`

const teachers = ref([])
const searchTerm = ref('')
const loading = ref(false)

// Lehrer laden
async function loadTeachers() {
  loading.value = true
  try {
    const response = await axios.get(`${apiPrefix}/teachers/view`)
    teachers.value = response.data
  } catch (error) {
    console.error('❌ Fehler beim Laden der Lehrer:', error)
  } finally {
    loading.value = false
  }
}

// Lehrer löschen
async function deleteTeacher(id) {
  if (!confirm('Willst du diesen Lehrer wirklich löschen?')) return
  try {
    await axios.delete(`${apiPrefix}/teachers/${id}`)
    console.log('✅ Lehrer gelöscht:', id)
    loadTeachers()
  } catch (err) {
    console.error('❌ Fehler beim Löschen des Lehrers:', err)
    alert('Fehler beim Löschen des Lehrers.')
  }
}

// Filter / Suche
const filteredTeachers = computed(() => {
  if (!searchTerm.value.trim()) return teachers.value
  const term = searchTerm.value.toLowerCase()
  return teachers.value.filter(
    t =>
      t.vorname.toLowerCase().includes(term) ||
      t.nachname.toLowerCase().includes(term) ||
      t.email.toLowerCase().includes(term)
  )
})

// Sortierfunktion
function sortByName() {
  teachers.value.sort((a, b) => a.nachname.localeCompare(b.nachname))
}

onMounted(() => {
  loadTeachers()
})
</script>

<style scoped>
.lehrer-view {
  padding: 1rem 2rem;
}

.title {
  font-size: 2rem;
  margin-bottom: 2rem;
  text-align: left;
  font-weight: 650;
}

/* Toolbar */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.2rem;
}

.left-controls,
.right-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Buttons */
.btn {
  background-color: var(--first-background-color);
  border: 1.5px solid #EAEAEA;
  border-radius: 20px;
  padding: 16px 30px;
  cursor: pointer;
  transition: background-color 0.2s;
  min-width: 180px;
}

.btn:hover {
  background-color: #f1f1f1;
}

/* Suchfeld */
.search-input {
  padding: 0.8rem 1.6rem;
  padding-left: 3rem;
  border: 1px solid #4D495C;
  border-radius: 10px;
  width: 94%;
  margin-bottom: 1.5rem;
  background: white url("/searchIcon.svg") no-repeat 15px center;
  background-size: 15px 15px;
}

/* Lehrer-Tabelle */
.teacher-list {
  background-color: #fff;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.teacher-table {
  width: 100%;
  border-collapse: collapse;
}

.teacher-table th {
  text-align: left;
  background: #fff;
  padding: 12px 18px;
  font-weight: 600;
  color: #333;
  border-bottom: 1px solid #ddd;
}

.teacher-table td {
  padding: 10px 18px;
  border-bottom: 1px solid #eee;
}

/* Aktionen */
.class-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.edit-btn {
  border: none;
  background-color: #ffff;
}

.edit-btn:hover {
  transform: scale(1.1);
}

.delete-btn {
  border: none;
  background-color: #ffff;
}

.delete-btn:hover {
  transform: scale(1.1);
}
</style>
