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
        <input
          v-model="searchTerm"
          type="text"
          class="search-input"
          placeholder="Klasse suchen..."
        />
        <button class="btn create-btn" @click="openCreateModal">
          Neue Klasse erstellen
        </button>
      </div>
    </div>

    <!-- Klassenliste -->
    <div class="class-list">
      <div v-if="loading" class="loading">⏳ Lade Klassen...</div>

      <div v-else-if="filteredClasses.length === 0">
        <p>Keine Klassen gefunden.</p>
      </div>

      <ul v-else>
        <li
          v-for="klasse in filteredClasses"
          :key="klasse.id"
          class="class-item"
        >
          <strong>{{ klasse.name }}</strong>
          <span class="count"
            >{{ klasse.schueler?.length || 0 }} Schüler*innen</span
          >
        </li>
      </ul>
    </div>

    <!-- ClassCreateModal-Komponente -->
    <ClassCreateModal
      v-if="showCreateForm"
      @close="closeCreateModal"
      @created="handleClassCreated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import ClassCreateModal from '../components/ClassCreateModal.vue'

// Reaktive States
const searchTerm = ref('')
const showCreateForm = ref(false)
const classes = ref([])
const loading = ref(false)

// Klassen laden
async function loadClasses() {
  loading.value = true
  try {
    const response = await axios.get('/api/classes')
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
  margin-bottom: 1rem;
}

/* Toolbar */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.left-controls,
.right-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Buttons */
.btn {
  background-color: #e7f0ff;
  border: 1px solid #8cb4ff;
  border-radius: 6px;
  padding: 0.4rem 0.8rem;
  cursor: pointer;
  font-size: 0.95rem;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: #dce8ff;
}

.create-btn {
  background-color: #4a90e2;
  color: white;
  border: none;
}

.create-btn:hover {
  background-color: #3b7ccc;
}

.search-input {
  padding: 0.4rem 0.8rem;
  border: 1px solid #ccc;
  border-radius: 6px;
}

/* Klassenliste */
.class-list {
  background-color: #fff;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.class-item {
  display: flex;
  justify-content: space-between;
  padding: 0.6rem 0;
  border-bottom: 1px solid #eee;
}

.class-item:last-child {
  border-bottom: none;
}

.count {
  color: #777;
  font-size: 0.9rem;
}

.loading {
  color: #555;
  font-style: italic;
}
</style>
