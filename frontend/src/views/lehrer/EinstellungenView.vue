<template>
  <section class="page">
    <h1 class="title">Einstellungen</h1>

    <div class="card">
      <div class="card-head">
        <div class="icon">🌐</div>
        <div>
          <div class="card-title">Sprache</div>
          <div class="card-sub">Sprache der Webapp auswählen</div>
        </div>
      </div>
      <div class="card-body">
        <select class="select" v-model="language" @change="saveLanguage">
          <option value="Deutsch">Deutsch</option>
        </select>
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <div class="icon">☾</div>
        <div>
          <div class="card-title">Light- / Darkmode</div>
          <div class="card-sub">Design der Webapp wählen</div>
        </div>
      </div>
      <div class="card-body">
        <div class="toggle">
          <button class="toggle-btn" :class="{ active: !isDark }" type="button" @click="setTheme(false)">Light</button>
          <button class="toggle-btn" :class="{ active: isDark }" type="button" @click="setTheme(true)">Dark</button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="hint">Lade …</div>
    <div v-else-if="error" class="hint">Fehler: {{ error }}</div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useTheme } from '@/composables/useTheme'
import { getTeacherSettings, updateTeacherSettings } from '@/services/teacherData'

const { isDark, loadFromServer } = useTheme()
const language = ref('Deutsch')
const loading = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    await loadFromServer()
    const s = await getTeacherSettings()
    if (s?.sprache) language.value = s.sprache
  } catch (e) {
    error.value = e?.message || 'Unbekannter Fehler'
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function saveLanguage() {
  try {
    await updateTeacherSettings({ sprache: language.value })
  } catch (e) {
    alert('Konnte Sprache nicht speichern.')
    console.warn(e)
  }
}

async function setTheme(value) {
  try {
    await updateTeacherSettings({ light_darkmode: !!value })
    document.documentElement.classList.toggle('dark', !!value)
    isDark.value = !!value
  } catch (e) {
    alert('Konnte Theme nicht speichern.')
    console.warn(e)
  }
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
.page {
  max-width: 1100px;
  margin: 0 auto;
  padding-bottom: 2rem;
}

.title {
  font-size: 2rem;
  font-weight: 650;
  margin: 0.5rem 0 1.25rem;
}

.card {
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
  padding: 1.2rem 1.25rem;
  margin-bottom: 1rem;
}

.card-head {
  display: flex;
  gap: 0.9rem;
  align-items: center;
}

.icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  display: grid;
  place-items: center;
}

.card-title {
  font-weight: 650;
}

.card-sub {
  color: var(--muted);
  font-size: 0.9rem;
  margin-top: 0.15rem;
}

.card-body {
  margin-top: 0.75rem;
}

.select {
  border-radius: 10px;
  padding: 0.65rem 0.85rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.05);
  color: var(--text);
  outline: none;
  width: 220px;
}

html:not(.dark) .select {
  background: rgba(0, 0, 0, 0.04);
}

.toggle {
  width: 240px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 4px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px;
}

.toggle-btn {
  border: none;
  border-radius: 999px;
  padding: 0.55rem 0.9rem;
  background: transparent;
  color: var(--text);
  cursor: pointer;
  font-weight: 650;
}

.toggle-btn.active {
  background: rgba(144, 125, 255, 0.95);
  color: #fff;
}

.hint {
  color: var(--muted);
  margin-top: 0.5rem;
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

