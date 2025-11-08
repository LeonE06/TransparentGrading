<template>
  <div class="schueler-view">
    <!-- Überschrift -->
    <h1 class="title">Alle Schüler*innen</h1>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="left-controls">
        <button class="btn" @click="loadStudents">Alle</button>
        <button class="btn" @click="sortByName">Sortiert A-Z</button>
      </div>
    </div>

    <!-- Suchfeld -->
    <input v-if="!isDark" v-model="searchTerm" type="text" class="search-input"
      placeholder="Nach Schüler*in suchen..." />
    <input v-else v-model="searchTerm" type="text" class="search-input-dark" placeholder="Nach Schüler*in suchen..." />


    <!-- Schülerliste -->
    <div class="student-list">
      <div v-if="loading" class="loading">⏳ Lade Schüler...</div>

      <div v-else-if="filteredStudents.length === 0">
        <p>Keine Schüler*innen gefunden.</p>
      </div>

      <table v-else class="student-table">
        <thead>
          <tr>
            <th>Vorname</th>
            <th>Nachname</th>
            <th>Email</th>
            <th>Klasse</th>
            <th style="text-align: right;">Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in filteredStudents" :key="s.schueler_id">
            <td>{{ s.vorname }}</td>
            <td>{{ s.nachname }}</td>
            <td>{{ s.email }}</td>
            <td>{{ s.klassenname || '–' }}</td>
            <td class="class-actions">
              <button class="edit-btn" @click="openEditModal(s)">
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
              <button class="delete-btn" @click="deleteStudent(s.schueler_id)">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M21 5.97998C17.67 5.64998 14.32 5.47998 10.98 5.47998C9 5.47998 7.02 5.57998 5.04 5.77998L3 5.97998"
                    stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="M8.5 4.97L8.72 3.66C8.88 2.71 9 2 10.69 2H13.31C15 2 15.13 2.75 15.28 3.67L15.5 4.97"
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

    <!-- Pagination -->
    <div class="pagination" v-if="totalPages > 1">
      <button v-for="n in totalPages" :key="n" @click="changePage(n)" :class="['page-btn', { active: page === n }]">
        {{ n }}
      </button>
    </div>

    <!-- Edit Modal -->
    <EditStudentModal v-if="showEditModal" :student="selectedStudent" @close="closeEditModal"
      @updated="onStudentUpdated" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import EditStudentModal from '../components/EditStudentModal.vue'
import { useTheme } from '@/composables/useTheme.js'
const { isDark, toggleTheme } = useTheme()

const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''
const apiPrefix = isDev ? '' : `${apiBase}/api`

const students = ref([])
const page = ref(1)
const limit = ref(10)
const totalPages = ref(1)
const searchTerm = ref('')
const loading = ref(false)

// Modal
const showEditModal = ref(false)
const selectedStudent = ref(null)

function openEditModal(student) {
  selectedStudent.value = student
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  selectedStudent.value = null
}

function onStudentUpdated() {
  loadStudents()
  closeEditModal()
}

async function loadStudents() {
  loading.value = true
  try {
    const response = await axios.get(`${apiPrefix}/students/view?page=${page.value}&limit=${limit.value}`)
    students.value = response.data.data
    totalPages.value = response.data.pages
  } catch (error) {
    console.error('❌ Fehler beim Laden der Schüler:', error)
  } finally {
    loading.value = false
  }
}

function sortByName() {
  students.value.sort((a, b) => a.nachname.localeCompare(b.nachname))
}

function changePage(n) {
  page.value = n
  loadStudents()
}

const filteredStudents = computed(() => {
  if (!searchTerm.value.trim()) return students.value
  const term = searchTerm.value.toLowerCase()
  return students.value.filter(
    s =>
      s.vorname.toLowerCase().includes(term) ||
      s.nachname.toLowerCase().includes(term) ||
      s.email.toLowerCase().includes(term)
  )
})

async function deleteStudent(id) {
  if (!confirm('Willst du diesen Schüler wirklich löschen?')) return
  try {
    await axios.delete(`${apiPrefix}/students/${id}`)
    alert('🗑️ Schüler gelöscht!')
    loadStudents()
  } catch (err) {
    console.error('❌ Fehler beim Löschen des Schülers:', err)
    alert('Fehler beim Löschen des Schülers.')
  }
}

onMounted(loadStudents)
</script>

<style scoped>
.schueler-view {
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

.left-controls {
  display: flex;
  gap: 0.5rem;
}

/* Buttons */
.btn {
  background-color: var(--first-background-color);
  border: 1.5px solid var(--second-background-color);
  border-radius: 20px;
  padding: 0.4rem 0.8rem;
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 16px 30px;
  min-width: 180px;
  color: var(--text);
}

.btn:hover {
  background-color: var(--second-background-color);
}

/* Suchfeld */
.search-input {
  padding: 0.8rem 1.6rem;
  padding-left: 3rem;
  border: 1px solid var(--aczent-color);
  color: var(--aczent-color);
  border-radius: 6px;
  width: 94%;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  background: white url("/searchIcon.svg") no-repeat 15px center;
  background-size: 15px 15px;

}

.search-input-dark {
  padding: 0.8rem 1.6rem;
  padding-left: 3rem;
  border: 1px solid var(--aczent-color);
  color: var(--aczent-color);
  border-radius: 6px;
  width: 94%;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  background: #322d37 url("/searchIconDark.svg") no-repeat 15px center;
  background-size: 15px 15px;

}

input svg path {
  stroke: var(--icon-color);
}

svg path {
  stroke: var(--icon-color);
}

::placeholder {
  color: var(--aczent-color);
}

/* Tabelle im Klassenstil */
.student-list {
  background-color: var(--card);
  border-radius: 8px;
  border: 1px solid #EAEAEA;
  padding: 1rem;
  box-shadow: 0 2px 6px var(--shadow);
}

.student-table {
  width: 100%;
  border-collapse: collapse;
}

.student-table th {
  text-align: left;
  background: var(--card);
  padding: 12px 18px;
  font-weight: 600;
  color: var(--text);
  border-bottom: 1px solid #EAEAEA;
}

.student-table td {
  padding: 10px 18px;
  border-bottom: 1px solid #EAEAEA;
}


/* Aktionen */

.class-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.edit-btn {
  border: none;
  background-color: var(--card);
}

.edit-btn:hover {
  transform: scale(1.1);
}

.delete-btn {
  border: none;
  background-color: var(--card);
}

.delete-btn:hover {
  transform: scale(1.1);
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 1.8rem;
  gap: 0.6rem;
}

.page-btn {
  background-color: var(--card);
  border: 1.5px solid var(--second-background-color);
  border-radius: 10px;
  padding: 8px 16px;
  min-width: 40px;
  cursor: pointer;
  font-weight: 500;
  color: var(--text);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease-in-out;
}

.page-btn:hover {
  background-color: var(--second-background-color);
  transform: translateY(-1px);
}

.page-btn.active {
  background-image: linear-gradient(to right, var(--primary), var(--secondary));
  color: var(--white);
  border: none;
  font-weight: 600;
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(106, 22, 204, 0.25);
}
</style>
