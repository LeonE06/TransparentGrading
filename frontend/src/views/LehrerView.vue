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
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in filteredTeachers" :key="t.id">
            <td>{{ t.vorname }}</td>
            <td>{{ t.nachname }}</td>
            <td>{{ t.email }}</td>
            <td class="actions">
              <button class="delete-btn" @click="deleteTeacher(t.id)">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                  xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M21 5.97998C17.67 5.64998 14.32 5.47998 10.98 5.47998C9 5.47998 7.02 5.57998 5.04 5.77998L3 5.97998"
                    stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  <path
                    d="M8.5 4.97L8.72 3.66C8.88 2.71 9 2 10.69 2H13.31C15 2 15.13 2.75 15.28 3.67L15.5 4.97"
                    stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  <path
                    d="M18.8499 9.14001L18.1999 19.21C18.0899 20.78 17.9999 22 15.2099 22H8.7899C5.9999 22 5.9099 20.78 5.7999 19.21L5.1499 9.14001"
                    stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="M10.3301 16.5H13.6601" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round" />
                  <path d="M9.5 12.5H14.5" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round" />
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
  background: #f7f7f7;
  padding: 12px 18px;
  font-weight: 600;
  color: #333;
  border-bottom: 1px solid #ddd;
}

.teacher-table td {
  padding: 10px 18px;
  border-bottom: 1px solid #eee;
}

.teacher-table tr:hover {
  background-color: #f9f5ff;
}

/* Aktionen */
.actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
}

.delete-btn {
  border: none;
  background-color: #fff;
  cursor: pointer;
  transition: transform 0.15s;
}

.delete-btn:hover {
  transform: scale(1.1);
}
</style>
