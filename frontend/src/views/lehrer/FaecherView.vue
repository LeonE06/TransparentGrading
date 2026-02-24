<template>
  <section class="page">
    <header class="head">
      <h1 class="title">Meine Fächer</h1>
    </header>

    <div class="toolbar">
      <div class="pill-group">
        <button class="pill" :class="{ active: filterMode === 'all' }" @click="filterMode = 'all'">Alle</button>
        <button class="pill" :class="{ active: filterMode === 'visible' }"
          @click="filterMode = 'visible'">Eingeblendete</button>
        <button class="pill" :class="{ active: filterMode === 'hidden' }"
          @click="filterMode = 'hidden'">Ausgeblendete</button>
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
          <div class="jahr">{{ c.fach || c.title || '—' }}</div>
          <div class="name">{{ c.name }}</div>
          <div class="klasse">{{ c.klasse || '—' }}</div>

          <div class="schema-badge">
            Aktives Schema: <strong>{{ schemeNameForCourse(c.id) }}</strong>
          </div>
        </div>

        <button class="kebab" type="button" title="Optionen" @click.stop>⋮</button>
      </article>
    </div>

    <ModalForm :open="showCreateModal" title="Neuen Kurs anlegen" @close="closeCreateModal">
      <div class="form">
        <label class="field">
          <span class="field-label">Fach *</span>
          <select v-model="form.fachId" class="input">
            <option :value="''">Bitte wählen</option>
            <option v-for="f in subjects" :key="f.id" :value="String(f.id)">
              {{ f.name }}
            </option>
          </select>
        </label>

        <label class="field">
          <span class="field-label">Kursname</span>
          <input v-model.trim="form.kursName" type="text" class="input" placeholder="z.B. 2024/25 Mathematik 7b" />
        </label>

        <label class="field">
          <span class="field-label">Klasse (optional)</span>
          <select v-model="form.klasseId" class="input">
            <option :value="''">Keine</option>
            <option v-for="k in classes" :key="k.id" :value="String(k.id)">
              {{ k.name }}
            </option>
          </select>
        </label>

        <div v-if="createError" class="form-error">{{ createError }}</div>
      </div>

      <template #actions>
        <button class="btn ghost" type="button" @click="closeCreateModal" :disabled="creating">Abbrechen</button>
        <button class="btn primary" type="button" @click="submitCreate" :disabled="creating">
          {{ creating ? 'Speichere…' : 'Erstellen' }}
        </button>
      </template>
    </ModalForm>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import grading from '@/services/grading'
import ModalForm from '@/components/ModalForm.vue'
import { apiClient } from '@/services/apiClient'

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

async function getMyCoursesHardcoded() {
  const res = await apiClient.get('/lehrer/faecher')
  return res.data || []
}

async function getMyClasses() {
  const res = await apiClient.get('/lehrer/klassen')
  return res.data || []
}

async function getMySubjects() {
  const res = await apiClient.get('/lehrer/faecher-liste')
  return res.data || []
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
//sachen
async function submitCreate() {
  createError.value = ''
  if (!form.value.fachId) {
    createError.value = 'Bitte ein Fach auswählen.'
    return
  }

  creating.value = true
  try {
    const payload = {
      fachId: Number(form.value.fachId),
      kursName: form.value.kursName || undefined,
      klasseId: form.value.klasseId ? Number(form.value.klasseId) : undefined,
    }
    await apiClient.post('/lehrer/faecher', payload)

    showCreateModal.value = false
    courses.value = await getMyCoursesHardcoded()
  } catch (e) {
    console.error(e)
    const apiError = e?.response?.data?.error
    createError.value = apiError || e?.message || 'Unbekannter Fehler'
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

.btn.ghost {
  background: rgba(255, 255, 255, 0.06);
  color: var(--text);
}

.form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.field {
  display: grid;
  gap: 0.35rem;
}

.field-label {
  color: var(--muted);
  font-size: 0.85rem;
}

.input {
  border-radius: 10px;
  padding: 0.65rem 0.85rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.05);
  color: var(--text);
  outline: none;
}

html:not(.dark) .input {
  background: rgba(0, 0, 0, 0.04);
}

.form-error {
  color: #ff9ea8;
  font-size: 0.9rem;
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

.empty {
  padding: 2rem 0;
  color: var(--muted);
}

.kebab {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid rgba(0, 0, 0, 0.08);
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
