<template>
  <div class="klassen-view">
    <!-- Überschrift -->
    <h1 class="title">Alle Klassen</h1>

    <!-- obere Steuerleiste -->
    <div class="toolbar">
      <div class="left-controls">
        <button class="btn">Alle</button>
        <button class="btn">Sortiert nach Stufe</button>
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

    <!-- Klassenliste (Platzhalter, später echte Daten) -->
    <div class="class-list">
      <p>Hier werden später alle Klassen angezeigt...</p>
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
import { ref } from 'vue'
import ClassCreateModal from '../components/ClassCreateModal.vue' // ✅ Modal importieren

const searchTerm = ref('')
const showCreateForm = ref(false)

function openCreateModal() {
  showCreateForm.value = true
}

function closeCreateModal() {
  showCreateForm.value = false
}

// Wird aufgerufen, wenn eine neue Klasse erstellt wurde
function handleClassCreated() {
  console.log('Neue Klasse wurde erfolgreich erstellt!')
  // später: z. B. Klassenliste neu laden
  closeCreateModal()
}
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
</style>
