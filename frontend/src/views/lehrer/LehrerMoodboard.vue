<template>
  <div class="teacher-page">
    <!-- ✅ TeacherNavbar direkt eingebunden -->
    <TeacherNavbar />

    <section class="page">
      <header class="head">
        <h1 class="title">Moodboard</h1>
        <p class="subtitle">
          Durchschnittliche Stimmung deiner Schüler je Klasse und Zeitraum.
        </p>
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

        <button
          class="btn"
          type="button"
          @click="loadMood"
          :disabled="!selectedKlasseId || loading"
        >
          Aktualisieren
        </button>
      </div>

      <div v-if="loading" class="state">Lade Mood-Daten …</div>
      <div v-else-if="error" class="state error">Fehler: {{ error }}</div>

      <div v-else class="board">
        <!-- left mood scale -->
        <div class="mood-scale">
          <div class="mood-dot">
            <div  class="svg-emoji" v-html="getLegendSvg('gut')"></div>
            <span class="txt">gut</span>
          </div>
          <div class="mood-dot">
            <div class="svg-emoji" v-html="getLegendSvg('neutral')"></div>
            <span class="txt">neutral</span>
          </div>
          <div class="mood-dot">
            <div class="svg-emoji" v-html="getLegendSvg('schlecht')"></div>
            <span class="txt">schlecht</span>
          </div>
        </div>

        <!-- chart -->
        <div class="card">
          <div class="card-head">
            <div class="card-title">
              Lern-Mood: <span class="muted">{{ klasseName }}</span>
            </div>

            <div class="avg" v-if="overallAvg !== null">Ø {{ overallAvg }}</div>
            <div class="avg muted" v-else>Keine Daten</div>
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
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, nextTick } from "vue";
import Chart from "chart.js/auto";
import { apiClient } from "@/services/apiClient";
import TeacherNavbar from "@/components/TeacherNavbar.vue";

const klassen = ref([]);
const selectedKlasseId = ref("");
const selectedRange = ref("weekly");

const labels = ref([]);
const values = ref([]);
const overallAvg = ref(null);

const loading = ref(false);
const error = ref("");

const moodChartEl = ref(null);
let chart = null;

const klasseName = computed(() => {
  const k = klassen.value.find(
    (x) => String(x.id) === String(selectedKlasseId.value),
  );
  return k?.name ?? "—";
});

async function loadKlassen() {
  const res = await apiClient.get("/lehrer/klassen");
  klassen.value = res.data || [];

  if (!selectedKlasseId.value && klassen.value.length > 0) {
    selectedKlasseId.value = String(klassen.value[0].id);
  }
}

/* =======================
   ✅ SVGs (grau) für Legende + Chart-Achse
   ======================= */

const svgNeutral = `
<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<line x1="28" y1="76.5" x2="83" y2="76.5" stroke="#B6B6B6" stroke-width="3"/>
</svg>
`;

const svgSchlecht = `
<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<path d="M28 83C28 75.268 39.6406 69 54 69C68.3594 69 80 75.268 80 83" stroke="#B6B6B6" stroke-width="3"/>
</svg>
`;

const svgGut = `
<svg width="108" height="108" viewBox="0 0 108 108" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="54" cy="54" r="52.5" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="31.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<circle cx="75.5" cy="40.5" r="4" stroke="#B6B6B6" stroke-width="3"/>
<path d="M81 70C81 77.732 69.3594 84 55 84C40.6406 84 29 77.732 29 70" stroke="#B6B6B6" stroke-width="3"/>
<path d="M27.5 68.5H82.5" stroke="#B6B6B6" stroke-width="3"/>
</svg>
`;

function getLegendSvg(type) {
  if (type === "gut") return svgGut;
  if (type === "neutral") return svgNeutral;
  return svgSchlecht;
}

function svgToImg(svgString) {
  const img = new Image();
  const encoded = encodeURIComponent(svgString)
    .replace(/'/g, "%27")
    .replace(/"/g, "%22");
  img.src = `data:image/svg+xml;charset=utf-8,${encoded}`;
  return img;
}

const yIconGut = svgToImg(svgGut);
const yIconNeutral = svgToImg(svgNeutral);
const yIconSchlecht = svgToImg(svgSchlecht);

const yAxisIconsPlugin = {
  id: "yAxisIconsPlugin",
  afterDraw(chart) {
    const yScale = chart.scales?.y;
    if (!yScale) return;

    const ctx = chart.ctx;
    const size = 16;
    const x = yScale.left - size - 10;

    const drawAt = (value, img) => {
      const y = yScale.getPixelForValue(value) - size / 2;
      if (img?.complete) ctx.drawImage(img, x, y, size, size);
    };

    drawAt(3, yIconGut);
    drawAt(2, yIconNeutral);
    drawAt(1, yIconSchlecht);
  },
};

async function loadMood() {
  if (!selectedKlasseId.value) return;

  loading.value = true;
  error.value = "";

  try {
    const res = await apiClient.get("/lehrer/mood", {
      params: { klasseId: selectedKlasseId.value, range: selectedRange.value },
    });

    labels.value = res.data?.labels ?? [];
    values.value = res.data?.values ?? [];
    overallAvg.value = res.data?.overall_avg ?? null;
  } catch (e) {
    error.value =
      e?.response?.data?.error || e?.message || "Unbekannter Fehler";
    labels.value = [];
    values.value = [];
    overallAvg.value = null;
    destroyChart();
  } finally {
    loading.value = false;
    await nextTick();

    if (!error.value && labels.value.length > 0) {
      renderChart();
    } else {
      destroyChart();
    }
  }
}

function destroyChart() {
  if (chart) {
    chart.destroy();
    chart = null;
  }
}

function renderChart() {
  const canvas = moodChartEl.value;
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  if (!ctx) return;

  destroyChart();

  const gradient = ctx.createLinearGradient(0, 0, 0, 260);
  gradient.addColorStop(0, "rgba(106,22,204,0.0)");
  gradient.addColorStop(1, "rgba(106,22,204,0.25)");

  chart = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels.value,
      datasets: [
        {
          label: "Mood (Ø)",
          data: values.value,
          borderColor: "#6a16cc",
          backgroundColor: "transparent",
          tension: 0.35,
          fill: false, // ❗ ganz wichtig
          pointBackgroundColor: "#6a16cc",
          pointBorderColor: "#6a16cc",
          pointRadius: 4,
          pointHoverRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
      },
      scales: {
        y: {
          min: 1,
          max: 3,
          ticks: {
            stepSize: 1,
            callback: () => "",
          },
          grid: {
            color: "rgba(0,0,0,0.04)", // sehr dezent
          },
        },
        x: {
          grid: {
            color: "rgba(0,0,0,0.04)",
          },
          ticks: { maxRotation: 0 },
        },
      },
    },
    plugins: [yAxisIconsPlugin],
  });

  // falls Icons erst nach dem ersten Draw laden → nochmal updaten
  yIconGut.onload = () => chart?.update();
  yIconNeutral.onload = () => chart?.update();
  yIconSchlecht.onload = () => chart?.update();
}

onMounted(async () => {
  await loadKlassen();
  if (selectedKlasseId.value) await loadMood();
});

onBeforeUnmount(() => {
  destroyChart();
});
</script>

<style scoped>
/* Layout wrapper damit Navbar + Content nebeneinander sind */
.teacher-page {
  display: flex;
  min-height: 100vh;
}
.page {
  flex: 1;
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
  display: flex;
  flex-direction: column;
  justify-content: space-between;   /* 🔥 verteilt gut / neutral / schlecht */
  align-items: center;

  padding: 1.5rem 0.5rem;
  margin-right: 0.5rem;

  background: transparent;          /* ❗ kein Card-Hintergrund */
  border-right: 1px solid rgba(0,0,0,0.06); /* subtile Chart-Anmutung */
}

.mood-dot {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;          /* Abstand Emoji ↔ Text */
  text-align: center;
}
/* SVG-Legende links (klein) */
.svg-emoji {
  width: 44px;
  height: 44px;

  border-radius: 50%;
  border: 2px solid #6a16cc;
  background: white;

  display: flex;
  align-items: center;
  justify-content: center;
}
.svg-emoji :deep(svg) {
  width: 26px !important;
  height: 26px !important;
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
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
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
.mood-label {
  font-size: 0.75rem;
  color: #777;
  font-weight: 600;
  text-transform: lowercase;
}
</style>
