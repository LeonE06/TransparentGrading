<template>
  <section class="page">
    <header class="head">
      <h1 class="title">Meine Fächer</h1>
    </header>

    <div class="toolbar">
      <div class="pill-group">
        <button class="pill" :class="{ active: filterMode === 'all' }" @click="filterMode = 'all'">Alle</button>
        <button class="pill" :class="{ active: filterMode === 'visible' }" @click="filterMode = 'visible'">Eingeblendete</button>
        <button class="pill" :class="{ active: filterMode === 'hidden' }" @click="filterMode = 'hidden'">Ausgeblendete</button>
      </div>

      <div class="right">
        <select class="select" v-model="sortMode">
          <option value="name">Sortiert A - Z</option>
          <option value="klasse">Sortiert nach Klasse</option>
        </select>
        <button class="btn primary" @click="openCreateModal">Neues Fach erstellen</button>
      </div>
    </div>

    <div v-if="loading" class="empty">Lade …</div>
    <div v-else-if="error" class="empty">Fehler: {{ error }}</div>
    <div v-else-if="courses.length === 0" class="empty">Keine Daten vorhanden.</div>

    <div v-else class="grid">
      <article v-for="c in sortedCourses" :key="c.id" class="course" @click="openDetail(c.id)">
        <div class="thumb" aria-hidden="true"></div>

        <div class="meta">
          <div class="jahr">{{ c.jahrgang || '—' }}</div>
          <div class="name">{{ c.fach || c.title || '—' }}</div>
          <div class="klasse">{{ c.klasse || '—' }}</div>

          <div class="schema-badge">
            Aktives Schema: <strong>{{ schemeNameForCourse(c.id) }}</strong>
          </div>
        </div>

        <button class="kebab" type="button" title="Optionen" @click.stop>⋮</button>
      </article>
    </div>

    <div v-if="showCreateModal" class="modal-backdrop" @click.self="closeCreateModal">
      <div class="modal">
        <header class="modal-head">
          <h2>Neuen Kurs anlegen</h2>
          <button class="modal-close" @click="closeCreateModal" aria-label="Schließen">×</button>
        </header>

        <div class="modal-body">
          <div class="form-row">
            <label>Fach *</label>
            <select v-model="form.fachId" class="select">
              <option :value="''">Bitte wählen</option>
              <option v-for="f in subjects" :key="f.id" :value="String(f.id)">
                {{ f.name }}
              </option>
            </select>
          </div>

          <div class="form-row">
            <label>Kursname</label>
            <input v-model.trim="form.kursName" type="text" class="input" placeholder="z.B. 2024/25 Mathematik 7b" />
          </div>

          <div class="form-row">
            <label>Klasse (optional)</label>
            <select v-model="form.klasseId" class="select">
              <option :value="''">Keine</option>
              <option v-for="k in classes" :key="k.id" :value="String(k.id)">
                {{ k.name }}
              </option>
            </select>
          </div>

          <div v-if="createError" class="form-error">{{ createError }}</div>
        </div>

        <footer class="modal-actions">
          <button class="btn" @click="closeCreateModal" :disabled="creating">Abbrechen</button>
          <button class="btn primary" @click="submitCreate" :disabled="creating">
            {{ creating ? 'Speichere…' : 'Erstellen' }}
          </button>
        </footer>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import grading from '@/services/grading'

const router = useRouter()

const courses = ref([])
const loading = ref(false)
const error = ref('')
const classes = ref([])
const subjects = ref([])

const showCreateModal = ref(false)
const creating = ref(false)
const createError = ref('')
const form = ref({
  fachId: '',
  kursName: '',
  klasseId: '',
})

const filterMode = ref('all')
const sortMode = ref('name')

const API_BASE = 'https://transparentgrading.onrender.com/api'

async function getMyCoursesHardcoded() {
  const token = localStorage.getItem('token')
  if (!token) throw new Error('Du bist nicht eingeloggt (Token fehlt).')

  const res = await fetch(`${API_BASE}/lehrer/faecher`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  })

  if (!res.ok) {
    const txt = await res.text().catch(() => '')
    throw new Error(`${res.status} ${res.statusText}${txt ? ` – ${txt}` : ''}`)
  }

  const data = await res.json()
  return data || []
}

async function getMyClasses() {
  const token = localStorage.getItem('token')
  if (!token) throw new Error('Du bist nicht eingeloggt (Token fehlt).')

  const res = await fetch(`${API_BASE}/lehrer/klassen`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  })

  if (!res.ok) {
    const txt = await res.text().catch(() => '')
    throw new Error(`${res.status} ${res.statusText}${txt ? ` – ${txt}` : ''}`)
  }

  const data = await res.json()
  return data || []
}

async function getMySubjects() {
  const token = localStorage.getItem('token')
  if (!token) throw new Error('Du bist nicht eingeloggt (Token fehlt).')

  const res = await fetch(`${API_BASE}/lehrer/faecher-liste`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  })

  if (!res.ok) {
    const txt = await res.text().catch(() => '')
    throw new Error(`${res.status} ${res.statusText}${txt ? ` – ${txt}` : ''}`)
  }

  const data = await res.json()
  return data || []
}

onMounted(async () => {
  loading.value = true
  error.value = ''
  try {
    courses.value = await getMyCoursesHardcoded()
  } catch (e) {
    console.error(e)
    error.value = e?.message || 'Unbekannter Fehler'
    courses.value = []
  } finally {
    loading.value = false
  }

  try {
    classes.value = await getMyClasses()
  } catch (e) {
    console.error(e)
    classes.value = []
  }

  try {
    subjects.value = await getMySubjects()
  } catch (e) {
    console.error(e)
    subjects.value = []
  }
})

const filteredCourses = computed(() => {
  // visibility ist aktuell nicht in DB → filtert (noch) nicht, UI bleibt aber.
  return courses.value || []
})

const sortedCourses = computed(() => {
  const list = [...filteredCourses.value]
  if (sortMode.value === 'klasse') {
    list.sort((a, b) => String(a.klasse || '').localeCompare(String(b.klasse || '')))
  } else {
    list.sort((a, b) => String(a.fach || a.title || '').localeCompare(String(b.fach || b.title || '')))
  }
  return list
})

function schemeNameForCourse(courseId) {
  return grading.getActiveSchemeForCourse?.(courseId)?.name || 'Standard'
}

function openDetail(id) {
  router.push(`/lehrer/faecher/${id}`)
}

function openCreateModal() {
  createError.value = ''
  form.value = { fachId: '', kursName: '', klasseId: '' }
  showCreateModal.value = true
}

function closeCreateModal() {
  if (creating.value) return
  showCreateModal.value = false
}

async function submitCreate() {
  createError.value = ''
  if (!form.value.fachId) {
    createError.value = 'Bitte ein Fach auswählen.'
    return
  }

  const token = localStorage.getItem('token')
  if (!token) {
    createError.value = 'Du bist nicht eingeloggt (Token fehlt).'
    return
  }

  creating.value = true
  try {
    const payload = {
      fachId: Number(form.value.fachId),
      kursName: form.value.kursName || undefined,
      klasseId: form.value.klasseId ? Number(form.value.klasseId) : undefined,
    }

    const res = await fetch(`${API_BASE}/lehrer/faecher`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    if (!res.ok) {
      const txt = await res.text().catch(() => '')
      throw new Error(`${res.status} ${res.statusText}${txt ? ` – ${txt}` : ''}`)
    }

    showCreateModal.value = false
    courses.value = await getMyCoursesHardcoded()
  } catch (e) {
    console.error(e)
    createError.value = e?.message || 'Unbekannter Fehler'
  } finally {
    creating.value = false
  }
}
</script>

<style scoped>
.page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 0 2rem;
}

.head {
  margin: 0.25rem 0 1rem;
}

.title {
  font-size: 2rem;
  font-weight: 650;
  margin: 0;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.pill-group {
  display: flex;
  gap: 0.6rem;
}

.pill {
  border-radius: 999px;
  padding: 0.55rem 1.1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: transparent;
  color: var(--text);
  cursor: pointer;
}

.pill.active {
  border-color: rgba(144, 125, 255, 0.65);
}

.right {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.select {
  border-radius: 999px;
  padding: 0.55rem 1rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text);
  outline: none;
}

.btn {
  border-radius: 999px;
  padding: 0.65rem 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(144, 125, 255, 0.95);
  color: #fff;
  font-weight: 650;
}

.btn[disabled] {
  opacity: 0.6;
  cursor: not-allowed;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.2rem;
}

.course {
  position: relative;
  display: grid;
  grid-template-columns: 96px 1fr;
  gap: 1rem;
  align-items: center;
  padding: 0.9rem;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  cursor: pointer;
}

.thumb {
  width: 96px;
  height: 96px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(144, 125, 255, 0.25), rgba(47, 67, 231, 0.25));
}

.meta {
  display: grid;
  gap: 0.25rem;
}

.jahr {
  font-size: 0.9rem;
  color: var(--muted);
}

.name {
  font-size: 1.05rem;
  font-weight: 650;
}

.klasse {
  font-size: 0.95rem;
  color: var(--text);
}

.schema-badge {
  margin-top: 0.5rem;
  font-size: 0.85rem;
  color: var(--muted);
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: grid;
  place-items: center;
  padding: 1.5rem;
  z-index: 50;
}

.modal {
  width: min(560px, 100%);
  background: #0f1118;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem 0.5rem;
}

.modal-body {
  padding: 0.5rem 1.25rem 1rem;
  display: grid;
  gap: 0.9rem;
}

.modal-actions {
  padding: 0 1.25rem 1.25rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
}

.modal-close {
  border: none;
  background: transparent;
  color: var(--text);
  font-size: 1.4rem;
  cursor: pointer;
}

.form-row {
  display: grid;
  gap: 0.35rem;
}

.input {
  border-radius: 10px;
  padding: 0.6rem 0.75rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text);
}

.form-error {
  color: #ff9ea8;
  font-size: 0.9rem;
}

.empty {
  padding: 2rem 0;
  color: var(--muted);
}
.kebab {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid rgba(0,0,0,0.08);
  background: transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  line-height: 1;
}

@media (max-width: 720px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .right {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
