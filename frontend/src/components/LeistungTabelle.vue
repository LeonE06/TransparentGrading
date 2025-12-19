<template>
  <div class="card">
    <h3>Erfasste Leistungen</h3>

    <!-- Filter nach Schüler -->
    <div class="form-group">
      <label for="filterStudent">Filtern nach Schüler*in:</label>
      <select id="filterStudent" v-model="filterStudentId" class="form-control">
        <option value="">-- Alle Schüler*innen --</option>
        <option v-for="s in students" :key="s.id" :value="s.id">
          {{ s.vorname }} {{ s.nachname }}
        </option>
      </select>
    </div>

    <div v-if="loading" class="loading">⏳ Lade Leistungen...</div>

    <table v-else-if="filteredItems && filteredItems.length" class="teacher-table">
      <thead>
        <tr>
          <th>Schüler*in</th>
          <th>Typ</th>
          <th v-if="scheme.scoreType === 'points'">Punkte</th>
          <th v-if="scheme.scoreType === 'grades'">Note</th>
          <th>Berechnet</th>
          <th>Gewichtung</th>
          <th>Kategorie</th>
          <th>Datum</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="it in filteredItems" :key="it.id">
          <td><strong>{{ getStudentName(it.studentId) }}</strong></td>
          <td>{{ it.typ }}</td>
          <td v-if="scheme.scoreType === 'points'">{{ it.points ?? '-' }} / {{ scheme.maxPoints }}</td>
          <td v-if="scheme.scoreType === 'grades'">{{ it.note ?? '-' }}</td>
          <td>{{ displayGrade(it) }}</td>
          <td>{{ it.gewichtung ?? '-' }}</td>
          <td>{{ it.category ?? '-' }}</td>
          <td>{{ formatDate(it.datum) }}</td>
          <td>
            <button class="btn danger" @click="remove(it.id)">Löschen</button>
          </td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2"><strong>Gewichteter Durchschnitt:</strong></td>
          <td colspan="7">{{ finalDisplay }}</td>
        </tr>
      </tfoot>
    </table>

    <p v-else>Keine Leistungen erfasst.</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getLeistungen, deleteLeistung, getStudents } from '@/api/leistungen'
import grading from '@/services/grading'

const items = ref([])
const students = ref([])
const loading = ref(false)
const filterStudentId = ref('')
const scheme = ref(grading.loadScheme())

async function load() {
  loading.value = true
  try {
    const res = await getLeistungen()
    items.value = res.data || []
  } catch (err) {
    console.error('Fehler beim Laden', err)
  } finally {
    loading.value = false
  }
}

async function loadStudents() {
  try {
    const res = await getStudents()
    students.value = res.data || []
  } catch (err) {
    console.error('Fehler beim Laden der Schüler', err)
  }
}

async function remove(id) {
  if (!confirm('Wirklich löschen?')) return
  try {
    await deleteLeistung(id)
    await load()
  } catch (err) {
    console.error('Fehler beim Löschen', err)
    alert('Fehler beim Löschen')
  }
}

// Filter nach Schüler
const filteredItems = computed(() => {
  if (!filterStudentId.value) return items.value
  return items.value.filter(it => it.studentId === filterStudentId.value)
})

// Berechne Durchschnitt nur für gefilterte Items
const final = computed(() => {
  return grading.computeFinalGrade(filteredItems.value, scheme.value)
})

const finalDisplay = computed(() => {
  return final.value.finalGrade != null ? final.value.finalGrade : '-'
})

// Zeige die berechnete Note an (entweder direkt oder konvertiert von Punkten)
function displayGrade(item) {
  if (scheme.value.scoreType === 'points' && item.points !== undefined) {
    return grading.pointsToGrade(item.points, scheme.value.maxPoints)
  } else if (item.note !== undefined) {
    return item.note
  }
  return '-'
}

// Hole Schülernamen
function getStudentName(studentId) {
  const student = students.value.find(s => s.id === studentId)
  return student ? `${student.vorname} ${student.nachname}` : 'Unbekannt'
}

function formatDate(d) {
  try { return new Date(d).toLocaleDateString('de-DE') } catch { return d }
}

// expose load for parent
defineExpose({ load })

onMounted(async () => {
  await load()
  await loadStudents()
})
</script>
