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
    <input
      v-model="searchTerm"
      type="text"
      class="search-input"
      placeholder="Nach Schüler*in suchen..."
    />

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
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in filteredStudents" :key="s.schueler_id">
            <td>{{ s.vorname }}</td>
            <td>{{ s.nachname }}</td>
            <td>{{ s.email }}</td>
            <td>{{ s.klassenname || '–' }}</td>
            <td class="actions">
              <button class="edit-btn" @click="openEditModal(s)">✏️</button>
              <button class="delete-btn" @click="deleteStudent(s.schueler_id)">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" v-if="totalPages > 1">
      <button
        v-for="n in totalPages"
        :key="n"
        @click="changePage(n)"
        :class="['page-btn', { active: page === n }]"
      >
        {{ n }}
      </button>
    </div>

    <!-- Edit Modal (Platzhalter für später) -->
    <EditStudentModal
      v-if="showEditModal"
      :student="selectedStudent"
      @close="closeEditModal"
      @updated="onStudentUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import EditStudentModal from '../components/EditStudentModal.vue' // späterer Modal

const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''
const apiPrefix = isDev ? '' : `${apiBase}/api`

const students = ref([])
const page = ref(1)
const limit = ref(20)
const totalPages = ref(1)
const searchTerm = ref('')
const loading = ref(false)

// Modal
const showEditModal = ref(false)
const selectedStudent = ref(null)

// 🧠 Modal öffnen/schließen
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

// 📦 Schüler laden
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

// 🔤 Sortieren
function sortByName() {
  students.value.sort((a, b) => a.nachname.localeCompare(b.nachname))
}

// 📄 Seitenwechsel
function changePage(n) {
  page.value = n
  loadStudents()
}

// 🔍 Filter
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

// 🗑️ Löschen
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
  font-weight: 650;
}

/* Toolbar */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.left-controls {
  display: flex;
  gap: 1rem;
}

/* Buttons */
.btn {
  background-color: #f9f9f9;
  border: 1.5px solid #EAEAEA;
  border-radius: 20px;
  padding: 12px 26px;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 160px;
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

/* Tabelle */
.student-table {
  width: 100%;
  border-collapse: collapse;
}

.student-table th {
  text-align: left;
  background: #f7f7f7;
  padding: 12px 18px;
  font-weight: 600;
  color: #333;
  border-bottom: 1px solid #ddd;
}

.student-table td {
  padding: 10px 18px;
  border-bottom: 1px solid #eee;
}

.student-table tr:hover {
  background-color: #f9f5ff;
}

/* Aktionen */
.actions {
  display: flex;
  gap: 0.6rem;
  justify-content: flex-end;
}

.actions button {
  border: none;
  border-radius: 50%;
  background: #f7f7f7;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.15s;
}

.actions button:hover {
  transform: scale(1.15);
}

.edit-btn {
  color: #4a67ff;
}

.delete-btn {
  color: #e53e3e;
}

.edit-btn:hover {
  background-color: #e6e8ff;
}

.delete-btn:hover {
  background-color: #ffe6e6;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  margin-top: 1.5rem;
  gap: 0.5rem;
}

.page-btn {
  border: 1px solid #ccc;
  padding: 6px 12px;
  border-radius: 8px;
  background: white;
  cursor: pointer;
}

.page-btn.active {
  background-color: #6a16cc;
  color: white;
  border-color: #6a16cc;
}

.page-btn:hover {
  background-color: #ece7ff;
}
</style>
