<template>
  <div class="fach-detail-view">
    <button class="back-btn" @click="goBack">← Zurück</button>

    <div class="title-row">
      <div class="title-block">
        <h1 class="fach-title">Fachdaten – {{ fachName }}</h1>
        <div v-if="dataLoaded" class="title-badges">
          <span class="title-badge primary">Gesamtnote {{ gesamtnote }}</span>
          <span class="title-badge">{{ gesamtnoteMeta }}</span>
        </div>
      </div>
      <div class="export-actions">
        <button class="export-btn" type="button" @click="downloadCsv">
          CSV exportieren
        </button>
        <button class="export-btn primary" type="button" @click="exportPdf">
          PDF exportieren
        </button>
      </div>
    </div>

    <!-- GRID: Links Werte, rechts Diagramm -->
    <div class="grid-container">
      <!-- LEFT SIDE -->
      <div class="left-boxes">
        <div class="stat-card big">
          <h3>Gesamtnote</h3>
          <p class="stat-value">{{ dataLoaded ? gesamtnote : "-" }}</p>
          <p class="stat-meta">{{ gesamtnoteMeta }}</p>
        </div>

        <div class="stat-card big">
          <h3>Klassenschnitt</h3>
          <p class="stat-value">{{ dataLoaded ? klassenschnitt : '-' }}</p>
        </div>
      </div>

      <!-- RIGHT SIDE – CHART -->
      <div class="chart-section">
        <h3>Leistungsentwicklung</h3>
        <canvas v-if="hasChartData" ref="notenChartEl"></canvas>
        <div v-else class="chart-empty">
          Noch nicht genug Noten für einen Verlauf vorhanden.
        </div>
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
            <th>Leistung</th>
            <th>Gewichtung</th>
            <th>Kommentar</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="noten.length === 0">
            <td colspan="6" class="empty-row">Keine Einträge vorhanden</td>
          </tr>

          <tr v-for="note in noten" :key="note.id">
            <td>{{ formatDisplayDate(note.datum) }}</td>
            <td>{{ note.typ_name }}</td>
            <td>{{ note.note }}</td>
            <td>{{ formatLeistung(note) }}</td>
            <td>{{ note.gewichtung }}</td>
            <td>{{ note.kommentar || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, nextTick, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import Chart from "chart.js/auto";
import { apiClient } from "@/services/apiClient"; // ✅ WICHTIG: statt axios

const route = useRoute();
const router = useRouter();
const kursId = route.params.id;

const noten = ref([]);
const gesamtnote = ref("-");
const klassenschnitt = ref("-");
const anzahlBenoteteLeistungen = ref(0);
const fachName = ref("Fachdetails");
const schuelerName = ref("Schüler:in");
const dataLoaded = ref(false);

const notenChartEl = ref(null);
let chart = null;

const hasChartData = computed(() =>
  noten.value.some((note) => Number.isFinite(Number(note?.note)))
);
const gesamtnoteMeta = computed(() => {
  if (!dataLoaded.value) return "";
  if (anzahlBenoteteLeistungen.value > 0) {
    return `${anzahlBenoteteLeistungen.value} benotete Leistungen`;
  }

  return "Noch keine benoteten Leistungen";
});

function formatDisplayDate(value) {
  if (!value) return "—";
  try {
    return new Date(value).toLocaleDateString("de-DE");
  } catch {
    return value;
  }
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function csvCell(value) {
  const text = value == null ? "" : String(value);
  if (text.includes('"') || text.includes(";") || text.includes("\n")) {
    return `"${text.replace(/"/g, '""')}"`;
  }
  return text;
}

function formatLeistung(note) {
  const punkte = note?.punkte ?? note?.punkte_erreicht ?? null;
  const maxPunkte = note?.max_punkte ?? note?.maxPunkte ?? null;
  const prozent = note?.prozent ?? note?.prozentsatz ?? null;

  if (punkte != null && maxPunkte != null) {
    const suffix = prozent != null ? ` (${String(prozent).replace(".", ",")}%)` : "";
    return `${punkte}/${maxPunkte} Pkt.${suffix}`;
  }

  if (punkte != null) {
    return `${punkte} Pkt.`;
  }

  if (prozent != null) {
    return `${String(prozent).replace(".", ",")}%`;
  }

  return "—";
}

function formatGradeValue(value) {
  if (value == null || value === "") return "—";

  const parsed = Number(value);
  if (!Number.isFinite(parsed)) {
    return String(value);
  }

  return parsed.toFixed(2).replace(".", ",");
}

function goBack() {
  router.push("/schueler/faecher");
}

function downloadCsv() {
  if (!window.confirm("CSV-Export für dieses Fach starten?")) {
    return;
  }

  const rows = [
    ["Datum", "Art", "Note", "Leistung", "Gewichtung", "Kommentar"],
    ...noten.value.map((note) => [
      formatDisplayDate(note.datum),
      note.typ_name || "",
      note.note ?? "",
      formatLeistung(note),
      note.gewichtung ?? "",
      note.kommentar || "",
    ]),
  ];

  const csvContent =
    "\uFEFF" + rows.map((row) => row.map((cell) => csvCell(cell)).join(";")).join("\n");

  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const href = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = href;
  link.download = `noten_${String(fachName.value || "fach").replace(/\s+/g, "_")}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(href);
}

function exportPdf() {
  if (!window.confirm("PDF-Export für dieses Fach starten?")) {
    return;
  }

  const printWindow = window.open("", "_blank", "width=960,height=720");
  if (!printWindow) {
    console.warn("PDF-Export konnte nicht gestartet werden.");
    return;
  }

  const rows = noten.value.map((note) => `
      <tr>
        <td>${escapeHtml(formatDisplayDate(note.datum))}</td>
        <td>${escapeHtml(note.typ_name || "—")}</td>
        <td>${escapeHtml(note.note ?? "—")}</td>
        <td>${escapeHtml(formatLeistung(note))}</td>
        <td>${escapeHtml(note.gewichtung ?? "—")}</td>
        <td>${escapeHtml(note.kommentar || "—")}</td>
      </tr>
    `).join("");

  printWindow.document.write(`
    <!doctype html>
    <html lang="de">
      <head>
        <meta charset="utf-8" />
        <title>Notenexport ${escapeHtml(fachName.value)}</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 32px; color: #1f2937; }
          h1 { margin: 0 0 8px; font-size: 28px; }
          .meta { margin-bottom: 24px; color: #4b5563; }
          .stats { display: flex; gap: 16px; margin-bottom: 24px; }
          .stat { padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 12px; min-width: 160px; }
          .stat-label { font-size: 12px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
          .stat-value { font-size: 24px; font-weight: 700; }
          table { width: 100%; border-collapse: collapse; }
          th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
          th { font-size: 12px; text-transform: uppercase; color: #6b7280; }
        </style>
      </head>
      <body>
        <h1>Notenexport ${escapeHtml(fachName.value || "Fach")}</h1>
        <div class="meta">
          <div><strong>Schüler:in:</strong> ${escapeHtml(schuelerName.value || "—")}</div>
          <div>Erstellt am ${escapeHtml(new Date().toLocaleDateString("de-DE"))}</div>
        </div>
        <div class="stats">
          <div class="stat">
            <div class="stat-label">Gesamtnote</div>
            <div class="stat-value">${escapeHtml(gesamtnote.value ?? "—")}</div>
          </div>
          <div class="stat">
            <div class="stat-label">Klassenschnitt</div>
            <div class="stat-value">${escapeHtml(klassenschnitt.value ?? "—")}</div>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Datum</th>
              <th>Art</th>
              <th>Note</th>
              <th>Leistung</th>
              <th>Gewichtung</th>
              <th>Kommentar</th>
            </tr>
          </thead>
          <tbody>
            ${rows || '<tr><td colspan="6">Keine Einträge vorhanden</td></tr>'}
          </tbody>
        </table>
      </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
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
  const primaryColor = styles.getPropertyValue("--primary").trim() || "#6a16cc";
  const mutedGrid = textColor ? `${textColor}22` : "rgba(0, 0, 0, 0.12)";

  const chartEntries = noten.value.map((n) => ({
    label: formatDisplayDate(n.datum),
    grade: Number(n.note),
    datum: formatDisplayDate(n.datum),
    typ: n.typ_name || "—",
    kommentar: n.kommentar || "—",
    gewichtung: n.gewichtung ?? "—",
  })).filter((entry) => Number.isFinite(entry.grade));

  if (chartEntries.length === 0) {
    destroyChart();
    return;
  }

  chart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: chartEntries.map((entry) => entry.label),
      datasets: [
        {
          label: "Note",
          data: chartEntries.map((entry) => ({
            x: entry.label,
            y: entry.grade,
            grade: entry.grade,
            datum: entry.datum,
            typ: entry.typ,
            gewichtung: entry.gewichtung,
            kommentar: entry.kommentar,
          })),
          backgroundColor: `${primaryColor}B3`,
          borderColor: primaryColor,
          borderWidth: 1,
          borderRadius: 8,
          base: 5,
          minBarLength: 3,
          maxBarThickness: 48,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: {
          top: 8,
          right: 12,
          bottom: 4,
          left: 8,
        },
      },
      interaction: {
        mode: "nearest",
        intersect: true,
      },
      scales: {
        y: {
          min: 1,
          max: 5,
          reverse: true,
          ticks: {
            color: textColor,
            stepSize: 1,
            padding: 10,
            font: {
              size: 13,
            },
          },
          grid: {
            color: mutedGrid,
          },
          border: {
            color: mutedGrid,
          },
        },
        x: {
          ticks: {
            color: textColor,
            padding: 10,
            autoSkip: false,
            maxRotation: 0,
            minRotation: 0,
            font: {
              size: 12,
            },
          },
          grid: {
            color: mutedGrid,
            drawTicks: false,
          },
          border: {
            color: mutedGrid,
          },
        },
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: "#111827",
          titleColor: "#ffffff",
          bodyColor: "#ffffff",
          displayColors: false,
          padding: 12,
          callbacks: {
            title: (items) => items[0]?.raw?.datum || "Note",
            label: (context) => `Note: ${context.raw?.grade ?? "—"}`,
            afterLabel: (context) => [
              `Art: ${context.raw?.typ ?? "—"}`,
              `Gewichtung: ${context.raw?.gewichtung ?? "—"}`,
              `Kommentar: ${context.raw?.kommentar ?? "—"}`,
            ],
            labelColor: () => ({
              borderColor: primaryColor,
              backgroundColor: primaryColor,
            }),
          },
        },
      },
    },
  });
}

async function loadFachName() {
  try {
    // ✅ apiClient statt axios
    const res = await apiClient.get("/schueler/faecher");
    const fach = (res.data || []).find(
      (f) => String(f.kurs_id ?? f.id) === String(kursId)
    );
    fachName.value =
      fach?.fach?.name ||
      fach?.fach_name ||
      fach?.name ||
      "Fachdetails";
  } catch (e) {
    fachName.value = "Fachdetails";
  }
}

async function loadSchuelerName() {
  try {
    const res = await apiClient.get("/schueler/me");
    const student = res.data || {};
    const fullName = [student.vorname, student.nachname].filter(Boolean).join(" ").trim();
    schuelerName.value = fullName || "Schüler:in";
  } catch (e) {
    schuelerName.value = "Schüler:in";
  }
}

async function loadData() {
  try {
    // ✅ apiClient statt axios
    const response = await apiClient.get(`/schueler/faecher/${kursId}/noten`);
    const data = response.data;

    noten.value = data?.noten ?? [];
    gesamtnote.value = formatGradeValue(data?.gesamtnote ?? data?.schueler_notenstand);
    klassenschnitt.value = formatGradeValue(data?.klassenschnitt);
    anzahlBenoteteLeistungen.value = Number(data?.anzahl_noten ?? 0);

    dataLoaded.value = true;

    await nextTick();
    renderChart();
  } catch (err) {
    console.error("Fehler beim Laden der Fachdaten", err);
    dataLoaded.value = false;
    noten.value = [];
    gesamtnote.value = "-";
    klassenschnitt.value = "-";
    anzahlBenoteteLeistungen.value = 0;
    destroyChart();
  }
}

onMounted(async () => {
  await loadSchuelerName();
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

.title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 2rem;
}

.title-block {
  display: grid;
  gap: 0.75rem;
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
  margin-bottom: 0;
}

.title-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.title-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.45rem 0.8rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text);
  font-size: 0.92rem;
  font-weight: 500;
}

.title-badge.primary {
  background: linear-gradient(to right, var(--primary), var(--secondary));
  border-color: transparent;
  color: #fff;
}

.export-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.export-btn {
  border-radius: 999px;
  padding: 0.7rem 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: var(--second-background-color);
  color: var(--text);
  cursor: pointer;
  font: inherit;
}

.export-btn.primary {
  background: linear-gradient(to right, var(--primary), var(--secondary));
  color: #fff;
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

.stat-meta {
  margin-top: 0.25rem;
  color: rgba(0, 0, 0, 0.6);
  font-size: 0.95rem;
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
  height: 320px !important;
}

.chart-empty {
  min-height: 320px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: rgba(0, 0, 0, 0.55);
  font-size: 1rem;
  padding: 1rem;
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
  vertical-align: top;
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
  .title-row {
    flex-direction: column;
    align-items: stretch;
  }

  .export-actions {
    width: 100%;
  }

  .export-btn {
    flex: 1 1 100%;
  }

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
    min-width: 760px;
  }
}
</style>
