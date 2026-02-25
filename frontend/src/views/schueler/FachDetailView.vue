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
        <canvas ref="notenChartEl"></canvas>
      </div>
    </div>

    <!-- TABLE -->
    <div class="noten-table">
      <table>
        <thead>
          <tr>
            <th>Datum</th>
            <th>Art</th>
            <th>Note</th>
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
            <td>{{ note.typ_name }}</td>
            <td>{{ note.note }}</td>
            <td>{{ note.gewichtung }}</td>
            <td>{{ note.kommentar || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import Chart from "chart.js/auto";
import { apiClient } from "@/services/apiClient"; // ✅ WICHTIG: statt axios

const route = useRoute();
const router = useRouter();
const kursId = route.params.id;

const noten = ref([]);
const schuelerNotenstand = ref("-");
const klassenschnitt = ref("-");
const fachName = ref("Fachdetails");
const dataLoaded = ref(false);

const notenChartEl = ref(null);
let chart = null;

function goBack() {
  router.push("/schueler/faecher");
}

function destroyChart() {
  if (chart) {
    chart.destroy();
    chart = null;
  }
}

function renderChart() {
  const canvas = notenChartEl.value;
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  if (!ctx) return;

  destroyChart();

  // 🔥 CSS Variablen richtig auslesen
  const styles = getComputedStyle(document.documentElement);
  const textColor = styles.getPropertyValue("--text").trim();
  const secondBg = styles.getPropertyValue("--second-background-color").trim();

  const gradient = ctx.createLinearGradient(0, 0, 0, 300);
  gradient.addColorStop(0, "rgba(106,22,204,0.0)");
  gradient.addColorStop(1, "rgba(106,22,204,0.25)");

  chart = new Chart(ctx, {
    type: "line",
    data: {
      labels: noten.value.map((n) => n.datum),
      datasets: [
        {
          label: "Note",
          data: noten.value.map((n) => Number(n.note)),
          borderColor: textColor,          // ✅ jetzt korrekt
          backgroundColor: gradient,
          tension: 0.35,
          fill: { target: "start" },
          pointBackgroundColor: secondBg,  // ✅ jetzt korrekt
          pointRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          min: 1,
          max: 5,
          reverse: true,
          ticks: {
            color: textColor,  // 🔥 wichtig für Darkmode
            stepSize: 1,
          },
          grid: {
            color: textColor // optional anpassen
          }
        },
        x: {
          ticks: {
            color: textColor
          }
        }
      },
      plugins: {
        legend: { display: false },
      },
    },
  });
}

async function loadFachName() {
  try {
    // ✅ apiClient statt axios
    const res = await apiClient.get("/schueler/faecher");
    const fach = (res.data || []).find((f) => String(f.id) === String(kursId));
    fachName.value = fach?.fach?.name || "Fachdetails";
  } catch (e) {
    fachName.value = "Fachdetails";
  }
}

async function loadData() {
  try {
    // ✅ apiClient statt axios
    const response = await apiClient.get(`/schueler/faecher/${kursId}/noten`);
    const data = response.data;

    noten.value = data?.noten ?? [];
    schuelerNotenstand.value = data?.schueler_notenstand ?? "-";
    klassenschnitt.value = data?.klassenschnitt ?? "-";

    dataLoaded.value = true;

    await nextTick();
    renderChart();
  } catch (err) {
    console.error("Fehler beim Laden der Fachdaten", err);
    dataLoaded.value = false;
    noten.value = [];
    schuelerNotenstand.value = "-";
    klassenschnitt.value = "-";
    destroyChart();
  }
}

onMounted(async () => {
  await loadFachName();
  await loadData();
});

onBeforeUnmount(() => {
  destroyChart();
});
</script>

<style scoped>
html,
body {
  overflow-x: hidden;
}

.fach-detail-view {
  padding: 2rem;
}

.back-btn {
  background: none;
  border: none;
  color: var(--primary);
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
  background: var(--second-background-color);
  border-radius: 16px;
  border: 2px solid #e0d6f8;
  text-align: center;
}

.stat-value {
  font-size: 3rem;
  font-weight: 700;
  margin-top: 0.5rem;
}

.chart-section {
  background: var(--second-background-color);
  padding: 1.5rem;
  border-radius: 16px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  /* Damit Chart Höhe hat */
  min-height: 320px;
}

.chart-section canvas {
  width: 100% !important;
  height: 260px !important;
}

/* Table */
.noten-table {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.noten-table table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  background: var(--first-background-color);
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
  background: var(--second-background-color);
  font-weight: 600;
}

.empty-row {
  text-align: center;
  padding: 2rem;
  color: #999;
}

@media (max-width: 768px) {
  .grid-container {
    grid-template-columns: 1fr;
  }

  .fach-title {
    font-size: 1.5rem;
  }

  .stat-value {
    font-size: 2rem;
  }

  .noten-table table {
    min-width: 500px;
  }
}
</style>
