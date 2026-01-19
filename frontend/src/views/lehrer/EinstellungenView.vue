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
}
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
</style>

