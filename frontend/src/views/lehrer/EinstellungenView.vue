<template>
  <div class="lehrer-view">
    <h1 class="title">Bewertungseinstellungen</h1>
    <p>Stellen Sie hier ein, wie Ihre Bewertungen berechnet werden sollen. Verwalten Sie mehrere Notenschemata und wählen Sie ein aktives Schema.</p>

    <div class="scheme-panel">
      <aside class="scheme-list card">
        <div class="header">
          <h3>Schemata</h3>
          <div>
            <button class="btn primary" @click="createNew">Neues Schema</button>
          </div>
        </div>
        <ul>
          <li v-for="s in schemes" :key="s.id" :class="{ active: s.id === activeSchemeId }">
            <label>
              <input type="radio" name="activeScheme" :value="s.id" v-model="activeSchemeId" @change="setActive(s.id)" />
              <strong>{{ s.name }}</strong>
            </label>
            <div class="actions-inline">
              <button class="btn" @click="selectScheme(s.id)">Bearbeiten</button>
              <button class="btn danger" @click="removeScheme(s.id)" v-if="s.id !== 'default'">Löschen</button>
            </div>
          </li>
        </ul>
      </aside>

      <section class="scheme-editor">
        <h2>Schema bearbeiten</h2>
        <div v-if="selectedScheme">
          <div class="form-row">
            <label>Name</label>
            <input v-model="selectedName" type="text" class="form-control" />
          </div>

          <!-- reuse existing settings form bound to localScheme -->
          <div class="card">
            <h3>Gewichtungsmodus</h3>
            <div class="form-group">
              <label>
                <input type="radio" v-model="localScheme.mode" value="per-item" />
                <strong>Individuelle Gewichtung pro Leistung</strong>
              </label>
            </div>
            <div class="form-group">
              <label>
                <input type="radio" v-model="localScheme.mode" value="group" />
                <strong>Gruppen-Gewichtung nach Kategorien</strong>
              </label>
            </div>
          </div>

          <div class="card">
            <h3>Bewertungstyp</h3>
            <div class="form-group">
              <label>
                <input type="radio" v-model="localScheme.scoreType" value="grades" />
                <strong>Noten (1-5)</strong>
              </label>
            </div>
            <div class="form-group">
              <label>
                <input type="radio" v-model="localScheme.scoreType" value="points" />
                <strong>Punkte (0-max)</strong>
                <p class="help-text">Definieren Sie die maximale Punktzahl:</p>
                <input 
                  v-if="localScheme.scoreType === 'points'"
                  type="number" 
                  v-model.number="localScheme.maxPoints" 
                  min="1" 
                  class="form-control"
                  placeholder="z.B. 100"
                />
              </label>
            </div>
          </div>

          <div v-if="localScheme.mode === 'group'" class="card">
            <h3>Kategorien definieren</h3>
            <div class="categories-list">
              <div v-for="(cat, idx) in localScheme.categories" :key="cat.key" class="category-item">
                <div class="form-group">
                  <label>Kategoriename</label>
                  <input v-model="cat.name" type="text" class="form-control" />
                </div>
                <div class="form-group">
                  <label>Prozentanteil</label>
                  <input v-model.number="cat.percent" type="number" min="0" max="100" class="form-control" />
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

          <div class="card">
            <h3>Aktuelle Konfiguration</h3>
            <pre>{{ JSON.stringify(localScheme, null, 2) }}</pre>
          </div>

          <div style="display:flex;gap:0.5rem;">
            <button class="btn primary" @click="saveSchemeChanges">Speichern</button>
            <button class="btn" @click="revertChanges">Zurücksetzen</button>
          </div>
        </div>
        <div v-else>
          <p>Kein Schema ausgewählt.</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import grading from '@/services/grading'

const schemes = ref([])
const selectedSchemeId = ref(null)
const activeSchemeId = ref(null)
const selectedName = ref('')

const localScheme = ref({ mode: 'per-item', scoreType: 'grades', maxPoints: 100, categories: [] })

function loadAll() {
  schemes.value = grading.loadAllSchemes()
  const active = grading.getActiveSchemeId() || grading.getActiveScheme().id
  activeSchemeId.value = active
  selectedSchemeId.value = active
  const activeObj = grading.getSchemeById(selectedSchemeId.value)
  if (activeObj) {
    selectedName.value = activeObj.name
    localScheme.value = JSON.parse(JSON.stringify(activeObj.scheme))
  }
}

onMounted(() => {
  loadAll()
})

const categoryPercentSum = computed(() => {
  if (!localScheme.value.categories) return 0
  return localScheme.value.categories.reduce((sum, cat) => sum + (Number(cat.percent) || 0), 0)
})

const selectedScheme = computed(() => schemes.value.find(s => s.id === selectedSchemeId.value) || null)

function selectScheme(id) {
  selectedSchemeId.value = id
  const s = grading.getSchemeById(id)
  if (s) {
    selectedName.value = s.name
    localScheme.value = JSON.parse(JSON.stringify(s.scheme))
  }
}

function createNew() {
  // prevent spam and huge lists
  try {
    const current = grading.loadAllSchemes()
    if (current.length >= 50) {
      alert('Maximale Anzahl an Schemata erreicht (50). Lösche zuerst nicht benötigte Schemata.')
      return
    }
  } catch (e) {
    console.warn('Konnte Schemaliste nicht lesen', e)
  }

  const created = grading.createScheme('Neues Schema')
  // set just-created as selected and active
  setActive(created.id)
  loadAll()
  selectScheme(created.id)
}

function removeScheme(id) {
  if (!confirm('Schema wirklich löschen?')) return
  grading.deleteScheme(id)
  // refresh and ensure selected/active make sense
  loadAll()
  const remaining = schemes.value
  if (!remaining.find(s => s.id === selectedSchemeId.value)) {
    // choose active or first
    selectedSchemeId.value = grading.getActiveSchemeId() || (remaining[0] && remaining[0].id) || null
    if (selectedSchemeId.value) selectScheme(selectedSchemeId.value)
  }
}

function setActive(id) {
  grading.setActiveSchemeId(id)
  activeSchemeId.value = id
  // also reflect selectedSchemeId so editor follows active by default
  selectedSchemeId.value = id
}

function saveSchemeChanges() {
  if (!selectedScheme.value) return
  // validate
  const validation = grading.validateScheme(localScheme.value)
  if (!validation.valid) {
    alert('Fehler: ' + validation.errors.join('\n'))
    return
  }
  grading.updateScheme(selectedSchemeId.value, { name: selectedName.value, scheme: localScheme.value })
  // also persist the single-scheme fallback for compatibility
  grading.saveScheme(localScheme.value)
  loadAll()
  alert('✅ Schema gespeichert')
}

function revertChanges() {
  if (!selectedScheme.value) return
  selectScheme(selectedSchemeId.value)
}

function addCategory() {
  if (!localScheme.value.categories) localScheme.value.categories = []
  localScheme.value.categories.push({ key: `cat_${Date.now()}`, name: 'Neue Kategorie', percent: 0 })
}

function removeCategory(idx) {
  localScheme.value.categories.splice(idx, 1)
}

// (helper functions defined above are used)
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

.scheme-panel {
  display: flex;
  gap: 1.2rem;
  align-items: flex-start;
}
.scheme-list {
  width: 300px;
  padding: 1rem;
}
.scheme-list .header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}
.scheme-list ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.scheme-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.45rem;
  border-radius: 6px;
}
.scheme-list li.active {
  background: var(--second-background-color);
  border: 1px solid var(--shadow);
}
.scheme-editor {
  flex: 1;
}
.form-row {
  margin-bottom: 1rem;
  display: flex;
  gap: 1rem;
  align-items: center;
}
.actions-inline button {
  margin-left: 0.5rem;
}
</style>
