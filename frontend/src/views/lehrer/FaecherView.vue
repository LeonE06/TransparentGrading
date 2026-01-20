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
        <button class="btn primary" disabled title="Noch nicht implementiert (DB-Feld fehlt)">
          Neues Fach erstellen
        </button>
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

.icon-btn {
  position: absolute;
  right: 10px;
  bottom: 10px;
  border: none;
  background: transparent;
  color: var(--text);
  font-size: 1.2rem;
  cursor: pointer;
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
</style>