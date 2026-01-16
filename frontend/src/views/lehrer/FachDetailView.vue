<template>
  <section class="page" v-if="subject">
    <header class="page__header">
      <div class="title-stack">
        <div class="breadcrumb">
          <router-link to="/lehrer/faecher">‹ Meine Fächer</router-link>
        </div>
        <h1>{{ subject.title }}</h1>
      </div>
      <div class="actions">
        <button class="btn ghost" @click="setTab('students')">Schüler:innen</button>
        <button class="btn primary" @click="openModal">neue Leistungsfeststellung erstellen</button>
        <button class="btn primary outline" @click="openModal">neue Schülerleistung erstellen</button>
        <div class="topbar">
          <button class="icon-btn">🌙</button>
          <button class="profile-btn">👤</button>
        </div>
      </div>
    </header>

    <div class="tabs card">
      <button class="tab" :class="{ active: activeTab === 'overview' }" @click="setTab('overview')">Übersicht</button>
      <button class="tab" :class="{ active: activeTab === 'students' }" @click="setTab('students')">Schüler:innen</button>
      <button class="tab" :class="{ active: activeTab === 'assessments' }" @click="setTab('assessments')">Leistungsfeststellungen</button>
      <input
        v-if="activeTab === 'assessments'"
        v-model="search"
        class="search"
        placeholder="Nach Leistungsfeststellung suchen"
      />
    </div>

    <div v-if="activeTab === 'overview'" class="stats overview-grid">
      <div class="stat-card card">
        <p class="label">Klassenschnitt</p>
        <h2 class="huge">{{ courseResult.finalGrade ?? '–' }}</h2>
        <p class="muted">{{ courseResult.finalPercent ? courseResult.finalPercent + '%' : 'ø nach Gewichtung' }}</p>
      </div>
      <div class="stat-card card">
        <p class="label">Durchschnittliche Teilnahmequote</p>
        <h2 class="huge">{{ participationRate }}</h2>
        <p class="muted">aus {{ assessments.length || 0 }} Leistungsfeststellungen</p>
      </div>
      <div class="chart-card card">
        <div class="chart-head">
          <p class="label">Klassenschnitt Verlauf</p>
        </div>
        <svg v-if="chartPath" class="line-chart" viewBox="0 0 100 40" preserveAspectRatio="none">
          <defs>
            <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="var(--primary)" stop-opacity="0.55" />
              <stop offset="100%" stop-color="var(--primary)" stop-opacity="0.15" />
            </linearGradient>
          </defs>
          <path :d="chartArea" fill="url(#chartGradient)" />
          <path :d="chartPath" stroke="var(--primary)" stroke-width="1.2" fill="none" />
          <circle
            v-for="(p, idx) in chartDots"
            :key="idx"
            :cx="p.x"
            :cy="p.y"
            r="1.2"
            :fill="p.color"
            stroke="var(--first-background-color)"
            stroke-width="0.4"
          />
        </svg>
        <div class="chart-months">
          <span v-for="m in months" :key="m">{{ m }}</span>
        </div>
      </div>
    </div>

    <div v-if="activeTab === 'assessments'" class="card table-card">
      <div class="pill-row">
        <div class="pill">
          <p class="label">Klassenschnitt</p>
          <strong>{{ courseResult.finalPercent || '60%' }} ({{ courseResult.finalGrade || 4 }})</strong>
        </div>
        <div class="pill">
          <p class="label">Gewichtung</p>
          <strong>{{ activeAssessment?.gewichtung ?? 1 }}x</strong>
        </div>
        <div class="pill">
          <p class="label">Teilnahmequote</p>
          <strong>100%</strong>
        </div>
        <div class="pill">
          <p class="label">mögliche Punkte</p>
          <strong>{{ activeAssessment?.maxPoints ?? scheme.maxPoints }}</strong>
        </div>
        <button class="btn primary" @click="openModal">Neue Schülerleistung erstellen</button>
      </div>

      <div class="table-head">
        <h3>Schülerleistungen</h3>
        <div class="table-actions">
          <label>
            Anzeige:
            <select v-model="selectedAssessmentId">
              <option v-for="ass in filteredAssessments" :key="ass.id" :value="ass.id">{{ ass.title }}</option>
            </select>
          </label>
        </div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Vorname</th>
            <th>Nachname</th>
            <th>Leistung</th>
            <th>Note</th>
            <th>Datum</th>
            <th>Kommentar</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in resultRows" :key="row.studentId">
            <td>{{ row.vorname }}</td>
            <td>{{ row.nachname }}</td>
            <td>{{ row.points }} / {{ activeAssessment?.maxPoints }}</td>
            <td>{{ row.grade }}</td>
            <td>{{ formatDate(activeAssessment?.datum) }}</td>
            <td>{{ row.comment || '—' }}</td>
          </tr>
        </tbody>
      </table>

      <div class="result-form">
        <h4>Schülerleistung hinzufügen</h4>
        <div class="result-grid">
          <label>Schüler*in
            <select v-model="studentResult.studentId">
              <option value="">Bitte wählen</option>
              <option v-for="s in students" :key="s.id" :value="s.id">{{ s.vorname }} {{ s.nachname }} ({{ s.klasse }})</option>
            </select>
          </label>
          <label>Punkte
            <input type="number" v-model.number="studentResult.points" min="0" :max="activeAssessment?.maxPoints || scheme.maxPoints" />
          </label>
          <label>Kommentar
            <input type="text" v-model="studentResult.comment" placeholder="optional" />
          </label>
          <button class="btn primary" @click="addStudentResult">Speichern</button>
        </div>
      </div>
    </div>

    <div v-if="activeTab === 'students'" class="card table-card">
      <div class="table-head">
        <h3>Schüler:innen</h3>
        <p class="muted">Zugeordnet zu {{ subject.title }}</p>
      </div>
      <div class="table-actions search-line">
        <input class="search" placeholder="Nach Schüler:innen suchen" v-model="searchStudent" />
        <button class="btn primary" @click="openModal">Neue Schülerleistung erstellen</button>
      </div>
      <table>
        <thead>
          <tr>
            <th>Vorname</th>
            <th>Nachname</th>
            <th>Datum</th>
            <th>Leistungsstand</th>
            <th>Note</th>
            <th>Teilnahme</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in filteredStudentRows" :key="row.id">
            <td>{{ row.vorname }}</td>
            <td>{{ row.nachname }}</td>
            <td>{{ row.lastDate }}</td>
            <td>{{ row.performance }}</td>
            <td>{{ row.lastGrade }}</td>
            <td>{{ row.participation }}</td>
            <td>➔</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal">
        <header class="modal-head">
          <h3>Neue Leistungsfeststellung erstellen</h3>
          <button class="icon-btn" @click="closeModal">✕</button>
        </header>
        <div class="modal-body">
          <label>Titel
            <input v-model="form.title" type="text" placeholder="z.B. PLF Objektorientiertes Programmieren" />
          </label>
          <label>Typ
            <select v-model="form.typ">
              <option>PLF</option>
              <option>Schularbeit</option>
              <option>Test</option>
              <option>Projekt</option>
              <option>Mündlich</option>
            </select>
          </label>
          <label>Datum
            <input v-model="form.datum" type="date" />
          </label>
          <div class="two-col">
            <label>Max. Punkte
              <input v-model.number="form.maxPoints" type="number" min="1" />
            </label>
            <label>Gewichtung (x)
              <input v-model.number="form.gewichtung" type="number" min="0.1" step="0.1" />
            </label>
          </div>
        </div>
        <footer class="modal-actions">
          <button class="btn ghost" @click="closeModal">Abbrechen</button>
          <button class="btn primary" @click="createAssessment">Leistungsfeststellung erstellen</button>
        </footer>
      </div>
    </div>
  </section>
  <p v-else>Lade Fach ...</p>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import grading from '@/services/grading'
import { getSubjectById, getAssessmentsForSubject, getStudentsByClass, addAssessment, getTrendForSubject, updateAssessment } from '@/services/teacherData'

const route = useRoute()
const scheme = { mode: 'per-item', scoreType: 'points', maxPoints: 24, gradeBands: grading.loadScheme().gradeBands }

const subject = computed(() => getSubjectById(route.params.id))
const assessments = ref(getAssessmentsForSubject(route.params.id))
const selectedAssessmentId = ref(assessments.value[0]?.id)
const activeTab = ref('overview')
const search = ref('')
const searchStudent = ref('')
const showModal = ref(false)
const form = ref({
  title: '',
  typ: 'PLF',
  datum: new Date().toISOString().slice(0, 10),
  maxPoints: 24,
  gewichtung: 1
})
const studentResult = ref({
  studentId: '',
  points: '',
  comment: ''
})

const selectedAssessment = computed(() => assessments.value.find(a => a.id === selectedAssessmentId.value))
const filteredAssessments = computed(() => {
  if (!search.value) return assessments.value
  return assessments.value.filter(a => a.title.toLowerCase().includes(search.value.toLowerCase()))
})
const activeAssessment = computed(() => selectedAssessment.value || filteredAssessments.value[0])
const students = computed(() => {
  const list = getStudentsByClass(subject.value?.klasse)
  return list && list.length ? list : getStudentsByClass(null)
})
const months = ['SEP', 'OKT', 'NOV', 'DEZ', 'JAN', 'FEB', 'MRZ', 'APR', 'MAI', 'JUN', 'JUL']

const resultRows = computed(() => {
  const ass = activeAssessment.value
  if (!ass) return []
  return ass.results.map(res => {
    const student = students.value.find(s => s.id === res.studentId) || {}
    return {
      studentId: res.studentId,
      vorname: student.vorname || '—',
      nachname: student.nachname || '—',
      points: res.points,
      grade: grading.pointsToGrade(res.points, ass.maxPoints),
      comment: res.comment
    }
  })
})

const studentRows = computed(() => {
  const list = students.value
  return list.map(student => {
    const relevantAss = assessments.value.filter(a => a.results?.some(r => r.studentId === student.id))
    const last = relevantAss.find(a => true)?.results?.find(r => r.studentId === student.id)
    const allPoints = relevantAss.flatMap(a => a.results.filter(r => r.studentId === student.id).map(r => ({ pts: r.points, max: a.maxPoints })))
    const avg = allPoints.length ? (allPoints.reduce((s, p) => s + p.pts, 0) / allPoints.length).toFixed(1) : '—'
    const lastGrade = last ? grading.pointsToGrade(last.points, relevantAss[0]?.maxPoints || scheme.maxPoints) : '—'
    return {
      ...student,
      lastGrade,
      avgPoints: avg,
      performance: allPoints.length ? `${Math.round((allPoints[0].pts / allPoints[0].max) * 100)}%` : '—',
      participation: relevantAss.length ? `${Math.round((relevantAss.length / (assessments.value.length || 1)) * 100)}%` : '0%',
      lastDate: relevantAss[0]?.datum ? formatDate(relevantAss[0].datum) : '—'
    }
  })
})

const filteredStudentRows = computed(() => {
  if (!searchStudent.value) return studentRows.value
  return studentRows.value.filter(s =>
    `${s.vorname} ${s.nachname}`.toLowerCase().includes(searchStudent.value.toLowerCase())
  )
})

const courseResult = computed(() => {
  const items = assessments.value.map(ass => {
    if (!ass.results?.length) return { note: null, gewichtung: ass.gewichtung || 1 }
    const avgPct = ass.results.reduce((s, r) => s + (r.points / ass.maxPoints) * 100, 0) / ass.results.length
    return {
      note: grading.percentageToGrade(avgPct, scheme.gradeBands),
      gewichtung: ass.gewichtung || 1,
      maxPoints: ass.maxPoints,
      percentage: avgPct
    }
  })
  return grading.computeFinalGrade(items, scheme)
})

const averageNote = computed(() => {
  const notes = assessments.value
    .map(ass => {
      if (!ass.results?.length) return null
      const avgPct = ass.results.reduce((s, r) => s + (r.points / ass.maxPoints) * 100, 0) / ass.results.length
      return grading.percentageToGrade(avgPct, scheme.gradeBands)
    })
    .filter(Boolean)
  if (!notes.length) return '—'
  const avg = notes.reduce((s, n) => s + n, 0) / notes.length
  return avg.toFixed(2)
})

const averagePoints = computed(() => {
  const ass = activeAssessment.value
  if (!ass || !ass.results?.length) return '—'
  const avg = ass.results.reduce((s, r) => s + r.points, 0) / ass.results.length
  return avg.toFixed(1)
})

const participationRate = computed(() => {
  const totalSlots = students.value.length * (assessments.value.length || 1)
  const actual = assessments.value.reduce((sum, a) => sum + (a.results?.length || 0), 0)
  if (!totalSlots) return '80%'
  const pct = Math.round((actual / totalSlots) * 100)
  return `${pct || 80}%`
})

const monthlyGrades = computed(() => {
  const values = new Array(months.length).fill(null)
  assessments.value.forEach(a => {
    const m = new Date(a.datum).getMonth() // 0-11
    const idx = m >= 8 ? m - 8 : m + 4 // map Sep(8) ->0 ... Jul(6)
    if (!a.results?.length) return
    const avgPct = a.results.reduce((s, r) => s + (r.points / a.maxPoints) * 100, 0) / a.results.length
    values[idx] = grading.percentageToGrade(avgPct, scheme.gradeBands)
  })
  return values
})

const chartPoints = computed(() => {
  const vals = monthlyGrades.value
  const hasData = vals.some(v => v !== null && !isNaN(v))
  if (!hasData) {
    const fallback = getTrendForSubject(route.params.id)
    if (fallback && fallback.length) return fallback.slice(0, months.length)
    return [2.3, 2.1, 2.8, 3.2, 2.5, 2.9, 2.7, 2.4, 2.6, 2.3, 2.5]
  }
  // fill gaps with previous value for smoother line
  let last = vals.find(v => v != null) || 3
  return vals.map(v => {
    if (v == null) return last
    last = v
    return v
  })
})

const chartPath = computed(() => {
  const pts = chartPoints.value
  if (!pts.length) return ''
  const max = Math.max(...pts, 5)
  const min = Math.min(...pts, 1)
  const spread = max - min || 1
  const stepX = 100 / (pts.length - 1 || 1)
  return pts.map((v, i) => {
    const x = i * stepX
    const y = 35 - ((v - min) / spread) * 30
    return `${i === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`
  }).join(' ')
})

const chartArea = computed(() => {
  const pts = chartPoints.value
  if (!pts.length) return ''
  const max = Math.max(...pts, 5)
  const min = Math.min(...pts, 1)
  const spread = max - min || 1
  const stepX = 100 / (pts.length - 1 || 1)
  const coords = pts.map((v, i) => {
    const x = i * stepX
    const y = 35 - ((v - min) / spread) * 30
    return `${x.toFixed(2)},${y.toFixed(2)}`
  })
  return `M 0 35 L ${coords.join(' L ')} L 100 35 Z`
})

const chartDots = computed(() => {
  const pts = chartPoints.value
  if (!pts.length) return []
  const max = Math.max(...pts, 5)
  const min = Math.min(...pts, 1)
  const spread = max - min || 1
  const stepX = 100 / (pts.length - 1 || 1)
  return pts.map((v, i) => {
    const x = i * stepX
    const y = 35 - ((v - min) / spread) * 30
    return { x, y, color: 'var(--secondary)' }
  })
})

function seedIfEmpty() {
  if (assessments.value.length) return
  const trend = getTrendForSubject(route.params.id) || [2.4, 2.6, 2.8, 2.5]
  const studentsList = students.value
  const maxPoints = 24
  trend.slice(0, 6).forEach((grade, idx) => {
    const pts = Math.max(0, Math.round((5 - grade) / 4 * maxPoints))
    const results = studentsList.map(s => ({ studentId: s.id, points: pts, comment: '' }))
    const month = idx + 9 // Sep=9
    const date = new Date(2025, month % 12, 10).toISOString().slice(0, 10)
    addAssessment(route.params.id, {
      title: `Auto-Assessment ${idx + 1}`,
      typ: 'Test',
      datum: date,
      maxPoints,
      gewichtung: 1,
      results
    })
  })
  refreshAssessments()
}

onMounted(() => {
  seedIfEmpty()
})

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('de-DE', { day: '2-digit', month: 'short', year: 'numeric' })
}

function setTab(tab) {
  activeTab.value = tab
}

function openModal() {
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function resetForm() {
  form.value = {
    title: '',
    typ: 'PLF',
    datum: new Date().toISOString().slice(0, 10),
    maxPoints: 24,
    gewichtung: 1
  }
}

function refreshAssessments() {
  assessments.value = getAssessmentsForSubject(route.params.id)
}

function createAssessment() {
  if (!form.value.title) {
    alert('Bitte einen Titel eingeben.')
    return
  }
  const payload = {
    title: form.value.title,
    typ: form.value.typ,
    datum: form.value.datum,
    maxPoints: Number(form.value.maxPoints),
    gewichtung: Number(form.value.gewichtung),
    results: []
  }
  const newAss = addAssessment(route.params.id, payload)
  refreshAssessments()
  selectedAssessmentId.value = newAss.id
  showModal.value = false
  resetForm()
}

function addStudentResult() {
  if (!activeAssessment.value) {
    alert('Bitte zuerst eine Leistungsfeststellung auswählen.')
    return
  }
  if (!studentResult.value.studentId) {
    alert('Bitte Schüler*in wählen.')
    return
  }
  const pts = Number(studentResult.value.points)
  if (Number.isNaN(pts)) {
    alert('Bitte Punkte eintragen.')
    return
  }
  const updated = updateAssessment(route.params.id, activeAssessment.value.id, a => {
    const results = Array.isArray(a.results) ? [...a.results] : []
    const idx = results.findIndex(r => r.studentId === studentResult.value.studentId)
    const entry = { studentId: studentResult.value.studentId, points: pts, comment: studentResult.value.comment || '' }
    if (idx >= 0) results[idx] = entry
    else results.push(entry)
    return { ...a, results }
  })
  if (updated) {
    refreshAssessments()
    studentResult.value = { studentId: '', points: '', comment: '' }
  }
}
</script>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-width: 1400px;
  width: 100%;
  margin: 0;
  padding: 0 0 2rem;
}

.page__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.title-stack h1 {
  margin: 0;
}

.eyebrow {
  font-size: 0.85rem;
  color: var(--muted);
  margin: 0;
}

.breadcrumb {
  display: flex;
  gap: 0.4rem;
  align-items: center;
  color: var(--muted);
}

.breadcrumb a {
  color: var(--secondary);
  text-decoration: none;
}

h1 {
  margin: 0.25rem 0 0;
}

.actions {
  display: flex;
  gap: 0.5rem;
}
.actions .btn {
  height: 42px;
}

.top-right {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.tabs {
  display: grid;
  grid-template-columns: repeat(3, auto) 1fr;
  gap: 0.5rem;
  align-items: center;
  background: transparent;
  border: none;
}

.tab {
  border: none;
  background: var(--second-background-color);
  color: var(--text);
  padding: 0.65rem 1rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

.tab.active {
  background: linear-gradient(120deg, var(--primary), var(--secondary));
  color: white;
}

.search {
  width: 100%;
  padding: 0.65rem 0.9rem;
  border-radius: 10px;
  border: 1px solid var(--shadow);
  background: var(--second-background-color);
  color: var(--text);
}

.stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.overview-grid {
  grid-template-columns: 220px 220px 1fr;
  align-items: stretch;
}

.stat-card {
  padding: 1rem 1.2rem;
}

.stat-card.wide {
  grid-column: span 2;
}

.label {
  margin: 0;
  color: var(--muted);
  font-size: 0.85rem;
}

.muted {
  color: var(--muted);
  margin: 0.2rem 0 0;
}

.stat-inline {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
}

.summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.chart-card {
  padding: 1rem 1.2rem;
}

.chart-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.6rem;
}

.line-chart {
  width: 100%;
  height: 200px;
}

.chart-months {
  display: grid;
  grid-template-columns: repeat(11, minmax(0, 1fr));
  gap: 0.4rem;
  font-size: 0.75rem;
  color: var(--muted);
  margin-top: 0.3rem;
}

.huge {
  font-size: 2.2rem;
  margin: 0.1rem 0;
}

.table-card {
  padding: 1rem 1.2rem;
}

.table-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.table-actions select {
  margin-left: 0.4rem;
  padding: 0.4rem 0.6rem;
  border-radius: 8px;
  border: 1px solid var(--shadow);
  background: var(--second-background-color);
  color: var(--text);
}

.result-form {
  margin-top: 1rem;
  padding: 0.75rem;
  border: 1px dashed var(--shadow);
  border-radius: 12px;
  background: var(--first-background-color);
}

.result-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.5rem;
  align-items: end;
}

.result-grid label {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-weight: 600;
}

.result-grid input,
.result-grid select {
  padding: 0.5rem 0.7rem;
  border-radius: 10px;
  border: 1px solid var(--shadow);
  background: var(--second-background-color);
  color: var(--text);
}

.search-line {
  margin: 0.5rem 0;
}

.pill-row {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 0.75rem;
}

.pill {
  background: var(--second-background-color);
  border-radius: 12px;
  padding: 0.75rem 1rem;
  border: 1px solid var(--shadow);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 0.7rem 0.5rem;
  text-align: left;
}

th {
  color: var(--muted);
  font-weight: 600;
  border-bottom: 1px solid var(--shadow);
}

tr:nth-child(odd) td {
  background: rgba(0,0,0,0.02);
}

.card {
  background: var(--first-background-color);
  border: 1px solid var(--shadow);
  border-radius: 14px;
  box-shadow: 0 14px 40px rgba(0, 0, 0, 0.08);
}

.btn {
  border: none;
  border-radius: 10px;
  padding: 0.7rem 1rem;
  cursor: pointer;
  font-weight: 600;
}

.btn.primary {
  background: linear-gradient(120deg, var(--primary), var(--secondary));
  color: white;
}

.btn.primary.outline {
  background: transparent;
  border: 1.5px solid var(--primary);
  color: var(--text);
}

.btn.ghost {
  background: transparent;
  border: 1px solid var(--shadow);
  color: var(--text);
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: grid;
  place-items: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: var(--first-background-color);
  border-radius: 16px;
  padding: 1.2rem;
  width: min(640px, 100%);
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.modal-body label {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-weight: 600;
}

.modal-body input,
.modal-body select {
  padding: 0.65rem 0.8rem;
  border-radius: 10px;
  border: 1px solid var(--shadow);
  background: var(--second-background-color);
  color: var(--text);
}

.two-col {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.6rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.icon-btn {
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1.1rem;
  color: var(--text);
}

@media (max-width: 1100px) {
  .stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .overview-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .page__header {
    flex-direction: column;
    align-items: flex-start;
  }
  .tabs {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .search {
    grid-column: span 2;
  }
  .stats {
    grid-template-columns: 1fr;
  }
  .summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  table {
    font-size: 0.95rem;
  }
  .overview-grid {
    grid-template-columns: 1fr;
  }
}
</style>
