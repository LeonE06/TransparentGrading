<template>
  <div class="klassen-view">
    <!-- Überschrift -->
    <h1 class="title">Alle Klassen</h1>

    <!-- obere Steuerleiste -->
    <div class="toolbar">
      <div class="left-controls">
        <button class="btn" @click="loadClasses">Alle</button>
        <button class="btn" @click="sortByName">Sortiert nach Name</button>
      </div>

      <div class="right-controls">

        <button class="btn create-btn" @click="openCreateModal">
          Neue Klasse erstellen
        </button>
      </div>
    </div>

    <input v-model="searchTerm" type="text" class="search-input" placeholder="Nach Klassen suchen..." />


    <!-- Klassenliste -->
    <div class="class-list">
      <div v-if="loading" class="loading">⏳ Lade Klassen...</div>

      <div v-else-if="filteredClasses.length === 0">
        <p>Keine Klassen gefunden.</p>
      </div>

      <ul v-else>
        <li v-for="klasse in filteredClasses" :key="klasse.id" class="class-item">
          <div class="class-info">
            <strong>{{ klasse.name }}</strong>
            <span class="count">
              {{ klasse.schueler?.length || 0 }} Schüler*innen
            </span>
          </div>

          <div class="class-actions">
            <button class="edit-btn" @click="openEditModal(klasse)">
              <svg width="24" height="24" viewBox="0 0 24 24"
                fill="none" xmlns="http://www.w3.org/2000/svg">
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

            <button class="delete-btn" @click="deleteClass(klasse.id)">
              <svg width="24" height="24" viewBox="0 0 24 24"
                fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M21 5.97998C17.67 5.64998 14.32 5.47998 10.98 5.47998C9 5.47998 7.02 5.57998 5.04 5.77998L3 5.97998"
                  stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
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

    <!-- ClassCreateModal-Komponente -->
    <ClassCreateModal v-if="showCreateForm" @close="closeCreateModal" @created="handleClassCreated" />
  </div>



  <EditClassModal v-if="showEditModal" :klasse="selectedClass" @close="closeEditModal" @updated="handleClassUpdated" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import ClassCreateModal from '../components/ClassCreateModal.vue'
import EditClassModal from '../components/EditClassModal.vue'


const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''

// Wenn Dev → direkt über Proxy `/api`
// Wenn Prod → volle URL, aber ohne zusätzliches /api doppeln
const apiPrefix = isDev ? '' : `${apiBase}/api`


// Reaktive States
const searchTerm = ref('')
const showCreateForm = ref(false)
const classes = ref([])
const loading = ref(false)

// Klassen laden
async function loadClasses() {
  loading.value = true
  try {
    const response = await axios.get(`${apiPrefix}/classes`)
    classes.value = response.data
  } catch (error) {
    console.error('❌ Fehler beim Laden der Klassen:', error)
  } finally {
    loading.value = false
  }
}

// Filter / Suche
const filteredClasses = computed(() => {
  if (!searchTerm.value.trim()) return classes.value
  return classes.value.filter(c =>
    c.name.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
})


async function deleteClass(id) {
  if (!confirm('Willst du diese Klasse wirklich löschen?')) return

  try {
    await axios.delete(`${apiPrefix}/classes/${id}`)
    console.log('✅ Klasse gelöscht:', id)
    loadClasses() // Liste neu laden
  } catch (err) {
    console.error('❌ Fehler beim Löschen der Klasse:', err)
    alert('Fehler beim Löschen der Klasse.')
  }
}

const showEditModal = ref(false)
const selectedClass = ref(null)

// Öffnet das Bearbeiten-Modal
function openEditModal(klasse) {
  selectedClass.value = { ...klasse } // Kopie der Klasse
  showEditModal.value = true
}

// Schließt das Bearbeiten-Modal
function closeEditModal() {
  showEditModal.value = false
  selectedClass.value = null
}

// Wird aufgerufen, wenn das Modal gespeichert wurde
function handleClassUpdated() {
  console.log('✅ Klasse aktualisiert – Liste neu laden...')
  closeEditModal()
  loadClasses()
}

// Modal-Funktionen
function openCreateModal() {
  showCreateForm.value = true
}
function closeCreateModal() {
  showCreateForm.value = false
}
function handleClassCreated() {
  console.log('✅ Neue Klasse erstellt – aktualisiere Liste...')
  closeCreateModal()
  loadClasses()
}

// Sortierfunktion
function sortByName() {
  classes.value.sort((a, b) => a.name.localeCompare(b.name))
}

// Beim Laden der Seite direkt alle Klassen holen
onMounted(() => {
  loadClasses()
})
</script>

<style scoped>
.klassen-view {
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

.create-btn {
  background-image: linear-gradient(to right, var(--primary), var(--secondary));
  color: var(--white);
  border: none;
}

.create-btn:hover {
  background-color: #3b7ccc;
}

.search-input {
  padding: 0.8rem 1.6rem;
  padding-left: 3rem;
  border: 1px solid var(--aczent-color);
  color: var(--aczent-color);
  border-radius: 6px;
  width: 94%;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  background: var(--search-background) url("/searchIcon.svg") no-repeat 15px center;
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

/* Klassenliste */
.class-list {
  background-color: var(--search-background);
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.class-item:last-child {
  border-bottom: none;
}

.count {
  color: var(--aczent-color);
  font-size: 0.9rem;
  margin-left: 1rem;
}

.loading {
  color: var(--aczent-color);
  font-style: italic;
}

.class-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.6rem 0;
  border-bottom: 1px solid #eee;
}

.class-actions {
  display: flex;
  gap: 0.5rem;
}

.edit-btn {
  border: none;
  background: none;
}

.edit-btn:hover {
  transform: scale(1.1);
}

.delete-btn {
  border: none;
  background: none;
}

.delete-btn:hover {
  transform: scale(1.1);
}
</style>
