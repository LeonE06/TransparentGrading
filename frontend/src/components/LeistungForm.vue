<template>
  <div class="card">
    <h3>Neue Leistung erfassen</h3>

    <!-- Aktives Schema anzeigen -->
    <div class="form-group">
      <label>Aktives Gewichtungsschema:</label>
      <div class="note">
        {{ scheme.mode }} 
        <span v-if="scheme.mode==='group'">(Kategorien: {{ scheme.categories.length }})</span>
        | Bewertungstyp: <strong>{{ scheme.scoreType === 'points' ? `Punkte (max ${scheme.maxPoints})` : 'Noten (1-5)' }}</strong>
      </div>
    </div>

    <form @submit.prevent="submit">
      <!-- Schüler-Auswahl -->
      <div class="form-group">
        <label for="student">Schüler*in</label>
        <select id="student" v-model="form.studentId" class="form-control" required>
          <option value="">-- Schüler*in auswählen --</option>
          <option v-for="s in students" :key="s.id" :value="s.id">
            {{ s.vorname }} {{ s.nachname }}
          </option>
        </select>
        <small v-if="loadingStudents">⏳ Lade Schüler...</small>
      </div>

      <div class="form-group">
        <label for="typ">Typ</label>
        <select id="typ" v-model="form.typ" class="form-control">
          <option>Test</option>
          <option>Schularbeit</option>
          <option>PLF</option>
          <option>SMÜ</option>
          <option>Mündlich</option>
          <option>Mitarbeit</option>
        </select>
      </div>

      <!-- Noten-Input (wenn scoreType=grades) -->
      <div v-if="scheme.scoreType === 'grades'" class="form-group">
        <label for="note">Note (1-5)</label>
        <input id="note" type="number" v-model.number="form.note" min="1" max="5" step="0.1" required class="form-control" />
      </div>

      <!-- Punkte-Input (wenn scoreType=points) -->
      <div v-if="scheme.scoreType === 'points'" class="form-group">
        <label for="points">Punkte (0-{{ scheme.maxPoints }})</label>
        <input id="points" type="number" v-model.number="form.points" min="0" :max="scheme.maxPoints" required class="form-control" />
        <small>→ Konvertiert zu Note: {{ computeConvertedGrade }}</small>
      </div>

      <div class="form-group" v-if="scheme.mode === 'per-item'">
        <label for="gewichtung">Gewichtung (individuell)</label>
        <input id="gewichtung" type="number" v-model.number="form.gewichtung" min="0.1" step="0.1" class="form-control" />
      </div>

      <div class="form-group" v-if="scheme.mode === 'group'">
        <label for="category">Kategorie</label>
        <select id="category" v-model="form.category" class="form-control">
          <option v-for="c in scheme.categories" :key="c.key" :value="c.key">{{ c.name }} ({{ c.percent }}%)</option>
        </select>
      </div>

      <div class="form-group">
        <label for="datum">Datum</label>
        <input id="datum" type="date" v-model="form.datum" required class="form-control" />
      </div>

      <div class="form-actions">
        <button class="btn primary" type="submit">Hinzufügen</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { addLeistung, getStudents } from '@/api/leistungen'
import grading from '@/services/grading'

const emit = defineEmits(['added', 'final-updated'])

const form = ref({ 
  studentId: '',
  typ: 'Test', 
  note: null, 
  points: null,
  gewichtung: 1, 
  datum: new Date().toISOString().split('T')[0], 
  category: null 
})
const scheme = ref(grading.loadScheme())
const students = ref([])
const loadingStudents = ref(false)

onMounted(async () => {
  scheme.value = grading.loadScheme()
  if (scheme.value.mode === 'group' && scheme.value.categories && scheme.value.categories.length) {
    form.value.category = scheme.value.categories[0].key
  }
  await loadStudents()
})

async function loadStudents() {
  loadingStudents.value = true
  try {
    const res = await getStudents()
    students.value = res.data || []
  } catch (err) {
    console.error('Fehler beim Laden der Schüler', err)
    students.value = []
  } finally {
    loadingStudents.value = false
  }
}

const computeConvertedGrade = computed(() => {
  if (scheme.value.scoreType !== 'points' || !form.value.points) return '-'
  return grading.pointsToGrade(form.value.points, scheme.value.maxPoints)
})

async function submit() {
  if (!form.value.studentId) {
    alert('Bitte wählen Sie einen Schüler aus')
    return
  }

  try {
    const payload = {
      studentId: form.value.studentId,
      typ: form.value.typ,
      datum: form.value.datum,
      gewichtung: scheme.value.mode === 'per-item' ? form.value.gewichtung : undefined,
      category: scheme.value.mode === 'group' ? form.value.category : undefined
    }

    if (scheme.value.scoreType === 'points') {
      payload.points = form.value.points
      payload.note = grading.pointsToGrade(form.value.points, scheme.value.maxPoints)
    } else {
      payload.note = form.value.note
    }

    await addLeistung(payload)
    emit('added')

    const { getLeistungen } = await import('@/api/leistungen')
    const res = await getLeistungen()
    const items = res.data || []
    const result = grading.computeFinalGrade(items, scheme.value)
    emit('final-updated', result)

    form.value = { 
      studentId: '',
      typ: 'Test', 
      note: null, 
      points: null,
      gewichtung: 1, 
      datum: new Date().toISOString().split('T')[0], 
      category: form.value.category 
    }
  } catch (err) {
    console.error('Fehler beim Anlegen der Leistung', err)
    alert('Fehler beim Anlegen der Leistung')
  }
}
</script>