<template>
  <div class="lehrer-view">
    <!-- Überschrift -->
    <h1 class="title">Alle Lehrer*innen</h1>

    <!-- obere Steuerleiste -->
    <div class="toolbar">
      <div class="left-controls">
        <button class="btn" :class="{ active: filter === 'all' }" @click="filter = 'all'">Alle</button>
        <button class="btn" :class="{ active: filter === 'az' }" @click="filter = 'az'">Sortiert A-Z</button>
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
      <div v-if="loading" class="loading">⏳ Lade Lehrer*innen...</div>

      <div v-else-if="filteredTeachers.length === 0">
        <p>Keine Lehrer*innen gefunden.</p>
      </div>

      <ul v-else>
        <li v-for="lehrer in filteredTeachers" :key="lehrer.lehrer_id" class="teacher-item">
          <div class="teacher-info">
            <strong>{{ lehrer.vorname }} {{ lehrer.nachname }}</strong>
            <span class="email">{{ lehrer.email }}</span>
          </div>

          <div class="teacher-actions">
            <button class="delete-btn" @click="deleteTeacher(lehrer.lehrer_id)">
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
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// API setup
const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''
const apiPrefix = isDev ? '' : `${apiBase}/api`

// States
const teachers = ref([])
const loading = ref(false)
const searchTerm = ref('')
const filter = ref('all')

// 🔹 Lehrer laden
async function loadTeachers() {
  loading.value = true
  try {
    const res = await axios.get(`${apiPrefix}/teachers/view`)
    teachers.value = res.data
  } catch (err) {
    console.error('❌ Fehler beim Laden der Lehrer*innen:', err)
  } finally {
    loading.value = false
  }
}

// 🔹 Lehrer löschen
async function deleteTeacher(id) {
  if (!confirm('Willst du diesen Lehrer wirklich löschen?')) return
  try {
    await axios.delete(`${apiPrefix}/teachers/${id}`)
    alert('✅ Lehrer gelöscht.')
    loadTeachers()
  } catch (err) {
    console.error('❌ Fehler beim Löschen:', err)
    alert('Fehler beim Löschen.')
  }
}

// 🔹 Filter + Suche + Sortierung
const filteredTeachers = computed(() => {
  let list = teachers.value

  // Suche
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    list = list.filter(
      (t) =>
        t.vorname.toLowerCase().includes(term) ||
        t.nachname.toLowerCase().includes(term) ||
        (t.email && t.email.toLowerCase().includes(term))
    )
  }

  // Sortierung
  if (filter.value === 'az') {
    list = [...list].sort((a, b) => a.nachname.localeCompare(b.nachname))
  }

  return list
})

onMounted(loadTeachers)
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

.left-controls {
  display: flex;
  gap: 0.5rem;
}

/* Buttons */
.btn {
  background-color: #f9f9f9;
  border: 1.5px solid #EAEAEA;
  border-radius: 20px;
  padding: 16px 30px;
  min-width: 180px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn.active {
  background-image: linear-gradient(to right, #6A16CC, #73A0F1);
  color: white;
  border: none;
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

/* Lehrer-Liste */
.teacher-list {
  background-color: #fff;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.teacher-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.2rem 0;
  border-bottom: 1px solid #eee;
}

.teacher-item:last-child {
  border-bottom: none;
}

.teacher-info strong {
  font-weight: 600;
  font-size: 1.05rem;
}

.email {
  color: #777;
  margin-left: 1rem;
}

.teacher-actions {
  display: flex;
  gap: 0.5rem;
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
