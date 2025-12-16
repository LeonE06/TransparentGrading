<template>
  <div class="lehrer-view">
    <h1 class="title">Bewertungseinstellungen</h1>
    <p>Stellen Sie hier ein, wie Ihre Bewertungen berechnet werden sollen.</p>

    <div class="settings-container">
      <!-- Modus Auswahl -->
      <div class="card">
        <h3>Gewichtungsmodus</h3>
        <div class="form-group">
          <label>
            <input type="radio" v-model="localScheme.mode" value="per-item" @change="saveChanges" />
            <strong>Individuelle Gewichtung pro Leistung</strong>
            <p class="help-text">Jede Leistung hat ihre eigene Gewichtung (z.B. ein Test zählt doppelt).</p>
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="radio" v-model="localScheme.mode" value="group" @change="saveChanges" />
            <strong>Gruppen-Gewichtung nach Kategorien</strong>
            <p class="help-text">Leistungen werden in Kategorien eingeteilt (Tests, Schularbeiten, etc.) mit prozentualem Anteil.</p>
          </label>
        </div>
      </div>

      <!-- Score Type Auswahl -->
      <div class="card">
        <h3>Bewertungstyp</h3>
        <div class="form-group">
          <label>
            <input type="radio" v-model="localScheme.scoreType" value="grades" @change="saveChanges" />
            <strong>Noten (1-5)</strong>
            <p class="help-text">Verwenden Sie die österreichische Notenskala 1-5.</p>
          </label>
        </div>
        <div class="form-group">
          <label>
            <input type="radio" v-model="localScheme.scoreType" value="points" @change="saveChanges" />
            <strong>Punkte (0-max)</strong>
            <p class="help-text">Verwenden Sie ein Punkte-System. Definieren Sie die maximale Punktzahl:</p>
            <input 
              v-if="localScheme.scoreType === 'points'"
              type="number" 
              v-model.number="localScheme.maxPoints" 
              min="10" 
              @change="saveChanges"
              class="form-control"
              placeholder="z.B. 100"
            />
          </label>
        </div>
      </div>

      <!-- Kategorien (für group-mode) -->
      <div v-if="localScheme.mode === 'group'" class="card">
        <h3>Kategorien definieren</h3>
        <p>Definieren Sie Ihre Kategorien und deren prozentuale Gewichtung. Die Summe muss 100% ergeben.</p>

        <div class="categories-list">
          <div v-for="(cat, idx) in localScheme.categories" :key="idx" class="category-item">
            <div class="form-group">
              <label>Kategoriename</label>
              <input v-model="cat.name" type="text" class="form-control" @change="saveChanges" />
            </div>
            <div class="form-group">
              <label>Prozentanteil</label>
              <input v-model.number="cat.percent" type="number" min="0" max="100" class="form-control" @change="saveChanges" />
            </div>
            <button @click="removeCategory(idx)" class="btn danger">Entfernen</button>
          </div>
        </div>

        <div>
          <strong>Aktuelle Summe: {{ categoryPercentSum }}%</strong>
          <span v-if="categoryPercentSum !== 100" style="color: red;">(sollte 100% sein)</span>
        </div>

        <button @click="addCategory" class="btn primary" style="margin-top: 1rem;">Kategorie hinzufügen</button>
      </div>

      <!-- Vorschau -->
      <div class="card">
        <h3>Aktuelle Konfiguration</h3>
        <pre>{{ JSON.stringify(localScheme, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import grading from '@/services/grading'

const localScheme = ref({ mode: 'per-item', scoreType: 'grades', maxPoints: 100, categories: [] })

onMounted(() => {
  localScheme.value = { ...grading.loadScheme() }
})

const categoryPercentSum = computed(() => {
  if (!localScheme.value.categories) return 0
  return localScheme.value.categories.reduce((sum, cat) => sum + (Number(cat.percent) || 0), 0)
})

function saveChanges() {
  // Validiere Schema
  const validation = grading.validateScheme(localScheme.value)
  if (!validation.valid) {
    console.warn('Schema-Validierungsfehler:', validation.errors)
    alert('Fehler in der Konfiguration:\n' + validation.errors.join('\n'))
    return
  }
  // Speichere
  grading.saveScheme(localScheme.value)
  alert('✅ Einstellungen gespeichert!')
}

function addCategory() {
  if (!localScheme.value.categories) localScheme.value.categories = []
  localScheme.value.categories.push({
    key: `cat_${Date.now()}`,
    name: 'Neue Kategorie',
    percent: 0
  })
}

function removeCategory(idx) {
  localScheme.value.categories.splice(idx, 1)
  saveChanges()
}
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

.settings-container {
  max-width: 800px;
}

.card {
  background: var(--card);
  border: 1px solid var(--aczent-color);
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 6px var(--shadow);
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  cursor: pointer;
  margin-bottom: 0.5rem;
}

.form-group input[type="radio"],
.form-group input[type="checkbox"] {
  margin-top: 0.25rem;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--aczent-color);
  border-radius: 4px;
  font-size: 1rem;
}

.help-text {
  font-size: 0.875rem;
  color: var(--aczent-color);
  margin: 0.25rem 0 0 0;
}

.categories-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin: 1rem 0;
}

.category-item {
  border: 1px solid var(--aczent-color);
  padding: 1rem;
  border-radius: 4px;
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.5rem;
  align-items: flex-end;
}

.btn {
  background-color: var(--first-background-color);
  border: 1px solid var(--aczent-color);
  border-radius: 4px;
  padding: 0.5rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: var(--second-background-color);
}

.btn.primary {
  background-color: var(--first-background-color);
  color: var(--text);
}

.btn.danger {
  background-color: #ffcccb;
  color: #c00;
}

pre {
  background: var(--first-background-color);
  padding: 1rem;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.875rem;
}
</style>
