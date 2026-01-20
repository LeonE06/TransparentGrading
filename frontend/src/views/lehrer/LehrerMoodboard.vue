<!-- frontend/src/views/lehrer/LehrerMoodboard.vue  (oder MoodboardView.vue) -->
<template>
  <section class="page">
    <header class="head">
      <h1 class="title">Moodboard</h1>
      <p class="subtitle">Durchschnittliche Stimmung deiner Schüler je Klasse und Zeitraum.</p>
    </header>

    <div class="toolbar">
      <label class="field">
        <span class="label">Klasse</span>
        <select class="select" v-model="selectedKlasseId">
          <option value="">Auswählen</option>
          <option v-for="k in klassen" :key="k.id" :value="String(k.id)">
            {{ k.name }}
          </option>
        </select>
      </label>

      <label class="field">
        <span class="label">Zeitraum</span>
        <select class="select" v-model="selectedRange">
          <option value="daily">Täglich</option>
          <option value="weekly">Wöchentlich</option>
          <option value="monthly">Monatlich</option>
        </select>
      </label>

      <div class="spacer"></div>

      <button class="btn" type="button" @click="loadMood" :disabled="!selectedKlasseId || loading">
        Aktualisieren
      </button>
    </div>

    <div v-if="loading" class="state">Lade Mood-Daten …</div>
    <div v-else-if="error" class="state error">Fehler: {{ error }}</div>

    <div v-else class="board">
      <!-- left mood scale -->
      <div class="mood-scale">
        <div class="mood-dot">
          <span class="emoji">🙂</span>
          <span class="txt">gut</span>
        </div>
        <div class="mood-dot">
          <span class="emoji">😐</span>
          <span class="txt">neutral</span>
        </div>
        <div class="mood-dot">
          <span class="emoji">🙁</span>
          <span class="txt">schlecht</span>
        </div>
      </div>

      <!-- chart -->
      <div class="card">
        <div class="card-head">
          <div class="card-title">
            Lern-Mood: <span class="muted">{{ klasseName }}</span>
          </div>

          <div class="avg" v-if="overallAvg !== null">
            Ø {{ overallAvg }}
          </div>
          <div class="avg muted" v-else>
            Keine Daten
          </div>
        </div>

        <div class="chart-wrap">
          <canvas ref="moodChartEl"></canvas>
        </div>

        <div class="hint" v-if="labels.length === 0">
          Keine Mood-Einträge für die Auswahl vorhanden.
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Chart from 'chart.js/auto'
import { apiClient } from '@/services/apiClient'
import { nextTick } from 'vue'


const klassen = ref([])
const selectedKlasseId = ref('')
const selectedRange = ref('weekly')

const labels = ref([])
const values = ref([])
const overallAvg = ref(null)

const loading = ref(false)
const error = ref('')

const moodChartEl = ref(null)
let chart = null

const klasseName = computed(() => {
  const k = klassen.value.find(x => String(x.id) === String(selectedKlasseId.value))
  return k?.name ?? '—'
})

async function loadKlassen() {
  const res = await apiClient.get('/lehrer/klassen')
  klassen.value = res.data || []

  // Auto-select erste Klasse (wenn vorhanden)
  if (!selectedKlasseId.value && klassen.value.length > 0) {
    selectedKlasseId.value = String(klassen.value[0].id)
  }
}

async function loadMood() {
  if (!selectedKlasseId.value) return

  loading.value = true
  error.value = ''

  try {
    const res = await apiClient.get('/lehrer/mood', {
      params: { klasseId: selectedKlasseId.value, range: selectedRange.value }
    })

    labels.value = res.data?.labels ?? []
    values.value = res.data?.values ?? []
    overallAvg.value = res.data?.overall_avg ?? null
  } catch (e) {
    error.value = e?.response?.data?.error || e?.message || 'Unbekannter Fehler'
    labels.value = []
    values.value = []
    overallAvg.value = null
    destroyChart()
  } finally {
    loading.value = false

    // ✅ Warten bis der Canvas wieder im DOM ist
    await nextTick()

    // ✅ Chart erst jetzt rendern
    if (!error.value && labels.value.length > 0) {
      renderChart()
    } else {
      destroyChart()
    }
  }
}

function destroyChart() {
  if (chart) {
    chart.destroy()
    chart = null
  }
}

function renderChart() {
  const canvas = moodChartEl.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  destroyChart()

  // Gradient (wie bei dir im Projekt)
  const gradient = ctx.createLinearGradient(0, 0, 0, 260)
  gradient.addColorStop(0, 'rgba(106,22,204,0.0)')
  gradient.addColorStop(1, 'rgba(106,22,204,0.25)')

  chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels.value,
      datasets: [{
        label: 'Mood (Ø)',
        data: values.value,
        borderColor: '#6a16cc',
        backgroundColor: gradient,
        tension: 0.35,
        fill: { target: 'start' },
        pointBackgroundColor: '#6a16cc',
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          min: 1,
          max: 3,
          ticks: {
            stepSize: 1,
            callback: (v) => {
              if (v === 3) return '🙂'
              if (v === 2) return '😐'
              if (v === 1) return '🙁'
              return v
            }
          }
        },
        x: {
          ticks: { maxRotation: 0 }
        }
      }
    }
  })
}


onMounted(async () => {
  await loadKlassen()
  if (selectedKlasseId.value) await loadMood()
})

onBeforeUnmount(() => {
  destroyChart()
})
</script>

<style scoped>
.page {
  padding: 2rem;
}

.head {
  margin-bottom: 1.25rem;
}

.title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
}

.subtitle {
  margin: 0.35rem 0 0;
  color: #666;
}

.toolbar {
  display: flex;
  gap: 1.25rem;
  align-items: end;
  margin: 1.5rem 0;
  flex-wrap: wrap;
}

.field {
  display: grid;
  gap: 0.5rem;
}

.label {
  font-weight: 600;
}

.select {
  width: 240px;
  padding: 0.8rem 1rem;
  border-radius: 12px;
  border: 2px solid #b99af2;
  background: #fff;
  outline: none;
}

.spacer {
  flex: 1;
}

.btn {
  padding: 0.85rem 1.1rem;
  border-radius: 12px;
  border: 2px solid #6a16cc;
  background: #6a16cc;
  color: white;
  font-weight: 700;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.state {
  padding: 1rem;
  border-radius: 14px;
  background: #fafafa;
  border: 1px solid #eee;
}

.state.error {
  background: #fff3f3;
  border-color: #ffd2d2;
  color: #7a1c1c;
}

.board {
  display: grid;
  grid-template-columns: 110px 1fr;
  gap: 1.25rem;
  align-items: stretch;
}

.mood-scale {
  background: #f7f7fb;
  border-radius: 16px;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  justify-content: center;
  align-items: center;
}

.mood-dot {
  width: 84px;
  text-align: center;
}

.emoji {
  display: inline-flex;
  width: 54px;
  height: 54px;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: 2px solid #6a16cc;
  background: #fff;
  font-size: 1.6rem;
}

.txt {
  display: block;
  margin-top: 0.35rem;
  color: #555;
  font-size: 0.9rem;
}

.card {
  background: #fff;
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  min-height: 380px;
  display: flex;
  flex-direction: column;
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  gap: 1rem;
}

.card-title {
  font-size: 1.2rem;
  font-weight: 800;
}

.muted {
  color: #777;
  font-weight: 600;
}

.avg {
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  border: 1px solid #eee;
  background: #fafafa;
  font-weight: 800;
}

.chart-wrap {
  position: relative;
  flex: 1;
  min-height: 260px;
}

.chart-wrap canvas {
  width: 100% !important;
  height: 100% !important;
}

.hint {
  margin-top: 0.75rem;
  text-align: center;
  color: #888;
}
</style>
