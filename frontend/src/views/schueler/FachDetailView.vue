<template>
  <div class="fach-detail-view">
    <button class="back-btn" @click="goBack">← Zurück</button>

    <h1 class="fach-title">Fachdaten – {{ fachName }}</h1>

    <!-- GRID: Links Werte, rechts Diagramm -->
    <div class="grid-container">
      
      <!-- LEFT SIDE -->
      <div class="left-boxes">
        <div class="stat-card big">
          <h3>Notenstand</h3>
          <p class="stat-value">{{ dataLoaded ? schuelerNotenstand : '-' }}</p>
        </div>

        <div class="stat-card big">
          <h3>Klassenschnitt</h3>
          <p class="stat-value">{{ dataLoaded ? klassenschnitt : '-' }}</p>
        </div>
      </div>

      <!-- RIGHT SIDE – CHART -->
      <div class="chart-section">
        <h3>Notenverlauf</h3>
        <canvas id="notenChart"></canvas>
      </div>

    </div>

    <!-- TABLE -->
    <div class="noten-table">
      <table>
        <thead>
          <tr>
            <th>Datum</th>
            <th>Note</th>
            <th>Art</th>
            <th>Gewichtung</th>
            <th>Kommentar</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="noten.length === 0">
            <td colspan="5" class="empty-row">Keine Einträge vorhanden</td>
          </tr>

          <tr v-for="note in noten" :key="note.id">
            <td>{{ note.datum }}</td>
            <td>{{ note.note }}</td>
            <td>{{ note.typ_name }}</td>
            <td>{{ note.gewichtung }}</td>
            <td>{{ note.kommentar || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Chart from 'chart.js/auto'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const kursId = route.params.id

const noten = ref([])
const schuelerNotenstand = ref('-')
const klassenschnitt = ref('-')
const fachName = ref('Fachdetails')
const dataLoaded = ref(false)

let chart = null

async function loadData() {
  try {
    const response = await axios.get(`/schueler/faecher/${kursId}/noten`)
    const data = response.data

    noten.value = data.noten
    schuelerNotenstand.value = data.schueler_notenstand
    klassenschnitt.value = data.klassenschnitt

  loadFachName()

    dataLoaded.value = true
    renderChart()
  } catch (err) {
    console.error('Fehler beim Laden der Fachdaten', err)
  }
}

function goBack() {
  router.push('/schueler/faecher')
}

function renderChart() {
  const canvas = document.getElementById('notenChart')
  if (!canvas) return

  if (chart) chart.destroy()

  const ctx = canvas.getContext('2d')

  const gradient = ctx.createLinearGradient(0, 0, 0, 300)
  gradient.addColorStop(0, 'rgba(106,22,204,0.0)')
  gradient.addColorStop(1, 'rgba(106,22,204,0.25)')

  chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: noten.value.map(n => n.datum),
      datasets: [
        {
          label: 'Note',
          data: noten.value.map(n => Number(n.note)),
          borderColor: '#6a16cc',
          backgroundColor: gradient,
          tension: 0.35,
          fill: {
            target: 'start'
          },
          pointBackgroundColor: '#6a16cc',
          pointRadius: 4
        }
      ]
    },
    options: {
      scales: {
        y: {
          min: 1,
          max: 5,
          reverse: true,
          ticks: {
            stepSize: 1,
            callback: v => v
          }
        }
      }
    }
  })
}

async function loadFachName() {
  try {
    const res = await axios.get(`/schueler/faecher`)
    const fach = res.data.find(f => f.id == kursId)

    fachName.value = fach?.fach?.name || 'Fachdetails'
  } catch (e) {
    fachName.value = 'Fachdetails'
  }
}


onMounted(() => {
  loadData()
  loadFachName()
})
</script>

<style scoped>
html, body {
  overflow-x: hidden;
}

.fach-detail-view {
  padding: 2rem;
}

.back-btn {
  background: none;
  border: none;
  color: #6a16cc;
  cursor: pointer;
  font-size: 1rem;
  margin-bottom: 1.5rem;
}

.fach-title {
  font-size: 2rem;
  font-weight: 600;
  margin-bottom: 2rem;
}

/* GRID – left stats + right chart */
.grid-container {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 2rem;
  margin-bottom: 2.5rem;
}

.left-boxes {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stat-card.big {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  border: 2px solid #e0d6f8;
  text-align: center;
}

.stat-value {
  font-size: 3rem;
  font-weight: 700;
  margin-top: 0.5rem;
}

.chart-section {
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

/* Table */
.noten-table table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 12px;
  overflow: hidden;
}

.noten-table th, .noten-table td {
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

.noten-table {
  width: 100%;
  overflow-x: hidden;
}

.noten-table table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed; /* 🔥 sorgt für perfekte Spalten-Ausrichtung */
  background: white;
  border-radius: 12px;
  overflow: hidden;
}

.noten-table th,
.noten-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.noten-table th {
  background: #fafafa;
  font-weight: 600;
}


.empty-row {
  text-align: center;
  padding: 2rem;
  color: #999;
}
</style>
