<template>
  <section class="page">
    <div class="breadcrumb">
      <router-link class="back" to="/lehrer/faecher">‹ Meine Fächer</router-link>
    </div>

    <header class="head">
      <h1 class="course-title">{{ course?.name || "—" }}</h1>
      <div class="head-actions">
        <button class="btn primary" @click="openCreateAssessment">
          neue Leistungsfeststellung erstellen
        </button>
        <button class="btn primary ghost" @click="goToAssessments">
          neue Schülerleistung erstellen
        </button>
        <button class="btn danger" @click="removeCourse">
          Kurs löschen
        </button>
      </div>
    </header>

    <TgTabs v-model="tab" :tabs="tabs">
      <template #right>
        <input
          v-if="tab === 'assessments'"
          v-model="search"
          class="search"
          placeholder="Nach Leistungsfeststellung suchen"
        />
      </template>
    </TgTabs>

    <div v-if="loading" class="empty">Lade …</div>
    <div v-else-if="error" class="empty">Fehler: {{ error }}</div>

    <!-- ================= Übersicht ================= -->
    <div v-else-if="tab === 'overview'" class="overview">
      <div class="stats">
        <div class="stat">
          <div class="label">Klassenschnitt</div>
          <div class="value">{{ overview?.klassenschnitt ?? "—" }}</div>
        </div>
        <div class="stat">
          <div class="label">Durchschnittliche Teilnahmequote</div>
          <div class="value">
            {{ overview?.teilnahmequote != null ? `${overview.teilnahmequote}%` : "—" }}
          </div>
        </div>
      </div>

      <!-- ✅ Schema wird hier ausgewählt -->
      <div class="scheme-card">
        <div class="scheme-title">Aktives Bewertungsschema</div>
        <div class="scheme-sub">
          Dieses Schema wird für die Berechnung der Gesamtnote in diesem Fach verwendet.
        </div>

        <label class="field">
          <span class="field-label">Schema auswählen</span>
          <select class="input" v-model="courseSchemeId" @change="saveCourseScheme">
            <option v-for="s in schemes" :key="s.id" :value="s.id">
              {{ s.name }}
            </option>
          </select>
        </label>
      </div>

      <div class="chart-card">
        <div class="chart-label">Notenverlauf</div>
        <canvas ref="chartEl" height="110"></canvas>
      </div>
    </div>

    <!-- ================= Schüler ================= -->
    <div v-else-if="tab === 'students'" class="panel">
      <DataTable
        :columns="studentColumns"
        :rows="students"
        row-key="id"
        empty-text="Keine Daten vorhanden."
      />
    </div>

    <!-- ================= Leistungsfeststellungen ================= -->
    <div v-else class="panel">
      <DataTable
        :columns="assessmentColumns"
        :rows="filteredAssessments"
        row-key="id"
        empty-text="Keine Daten vorhanden."
      >
        <template #cell-datum="{ row }">{{ formatDate(row.datum) }}</template>
        <template #cell-gewichtungProzent="{ row }">
          {{ row.gewichtungProzent != null ? `${row.gewichtungProzent}%` : "—" }}
        </template>
        <template #cell-klassenschnitt="{ row }">
          {{
            row.klassenschnittProzent != null && row.klassenschnitt != null
              ? `${row.klassenschnittProzent}% (${formatGrade(row.klassenschnitt)})`
              : "—"
          }}
        </template>
        <template #cell-teilnahmequote="{ row }">
          {{ row.teilnahmequote != null ? `${row.teilnahmequote}%` : "—" }}
        </template>
        <template #actions="{ row }">
          <button class="icon-action" type="button" title="Öffnen" @click="openAssessment(row.id)">
            <ExternalLink :size="18" />
          </button>
          <button class="icon-action" type="button" title="Löschen" @click="removeAssessment(row.id)">
            <Trash2 :size="18" />
          </button>
        </template>
      </DataTable>
    </div>

    <!-- ================= Modal: Leistungsfeststellung erstellen ================= -->
    <ModalForm
      :open="createOpen"
      title="Neue Leistungsfeststellung erstellen"
      @close="closeCreateAssessment"
    >
      <div class="form">
        <!-- ✅ Schema im Modal NUR anzeigen (keine Auswahl) -->
        <div class="scheme-inline">
          <div class="scheme-inline-title">Aktives Bewertungsschema</div>
          <div class="scheme-inline-sub">
            Dieses Schema wird für neue Leistungsfeststellungen in diesem Fach verwendet.
          </div>

          <div class="scheme-pill">
           <p>Aktuell: <strong>{{ activeSchemeName }}</strong></p> 
          </div>
        </div>

        <label class="field">
          <span class="field-label">Thema</span>
          <input
            v-model="createForm.thema"
            class="input"
            placeholder="z.B. Objektorientiertes Programmieren"
          />
        </label>

        <label class="field">
          <span class="field-label">Feststellungsart auswählen</span>
          <select v-model="createForm.benotungsartId" class="input">
            <option value="">Feststellungsart wählen</option>
            <option v-for="t in benotungsarten" :key="t.id" :value="t.id">
              {{ t.name }}
            </option>
          </select>
        </label>

        <div class="row">
          <label class="field">
            <span class="field-label">Mögliche Punkte</span>
            <input
              v-model.number="createForm.maxPunkte"
              class="input"
              type="number"
              min="0"
              placeholder="z.B. 24"
            />
          </label>
          <label class="field">
            <span class="field-label">Datum</span>
            <input v-model="createForm.datum" class="input" type="date" />
          </label>
          <label class="field">
            <span class="field-label">Gewichtung (%)</span>
            <input
              v-model.number="createForm.gewichtungProzent"
              class="input"
              type="number"
              min="0"
              max="100"
              placeholder="z.B. 50"
            />
          </label>
        </div>
      </div>

      <template #actions>
        <button class="btn ghost" type="button" @click="closeCreateAssessment">
          Abbrechen
        </button>
        <button
          class="btn primary"
          type="button"
          :disabled="createSaving"
          @click="submitCreateAssessment"
        >
          Leistungsfeststellung erstellen
        </button>
      </template>
    </ModalForm>
  </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import grading from "@/services/grading";
import Chart from "chart.js/auto";
import { ExternalLink, Trash2 } from "lucide-vue-next";
import DataTable from "@/components/DataTable.vue";
import ModalForm from "@/components/ModalForm.vue";
import TgTabs from "@/components/TgTabs.vue";
import {
  createAssessment,
  deleteCourse,
  deleteAssessment,
  getAssessmentsForCourse,
  getCourseDetail,
  getCourseOverview,
  getCourseStudents,
  getGradingTypes,
} from "@/services/teacherData";

const route = useRoute();
const router = useRouter();

const kursId = computed(() => Number(route.params.id));

const loading = ref(false);
const error = ref("");

const course = ref(null);
const overview = ref(null);
const students = ref([]);
const assessments = ref([]);
const benotungsarten = ref([]);

// --- Schema state (Auswahl in Overview) ---
const schemes = ref([]);
const courseSchemeId = ref("default");

// Name des aktiven Schemas (für Modal-Anzeige)
const activeSchemeName = computed(() => {
  const s = schemes.value.find(
    x => String(x.id) === String(courseSchemeId.value)
  )
  return s?.name || "Standard"
})

// --- Tabs ---
const tab = ref(route.path.endsWith("/leistungsfeststellungen") ? "assessments" : "overview");
const tabs = [
  { key: "overview", label: "Übersicht" },
  { key: "students", label: "Schüler*innen" },
  { key: "assessments", label: "Leistungsfeststellungen" },
];

watch(
  () => route.path,
  (p) => {
    if (p.endsWith("/leistungsfeststellungen")) tab.value = "assessments";
  }
);

// --- Search ---
const search = ref("");
const filteredAssessments = computed(() => {
  if (!search.value) return assessments.value;
  const q = search.value.toLowerCase();
  return assessments.value.filter((a) => String(a.thema || "").toLowerCase().includes(q));
});

// --- Tables ---
const assessmentColumns = [
  { key: "thema", label: "Thema", width: "42%" },
  { key: "typ", label: "Feststellungsart" },
  { key: "datum", label: "Datum" },
  { key: "gewichtungProzent", label: "Gewichtung" },
  { key: "klassenschnitt", label: "Klassenschnitt" },
  { key: "teilnahmequote", label: "Teilnahmequote" },
];

const studentColumns = [
  { key: "vorname", label: "Vorname" },
  { key: "nachname", label: "Nachname" },
  { key: "klasse", label: "Klasse" },
];

function formatDate(d) {
  if (!d) return "—";
  try {
    return new Date(d).toLocaleDateString("de-DE");
  } catch {
    return d;
  }
}

function formatGrade(v) {
  if (v == null) return "—";
  return Number(v).toFixed(1).replace(".", ",");
}

// --- Schema helpers ---
function syncCourseSchemeUi() {
  schemes.value = grading.loadAllSchemes();
  courseSchemeId.value =
    grading.getActiveSchemeIdForCourse?.(kursId.value) ||
    grading.getActiveSchemeId?.() ||
    "default";
}

function saveCourseScheme() {
  grading.setActiveSchemeIdForCourse(kursId.value, courseSchemeId.value);
}

// --- Load data ---
async function loadAll() {
  loading.value = true;
  error.value = "";
  try {
    const [c, o, s, a, t] = await Promise.all([
      getCourseDetail(kursId.value),
      getCourseOverview(kursId.value),
      getCourseStudents(kursId.value),
      getAssessmentsForCourse(kursId.value),
      getGradingTypes(kursId.value),
    ]);
    course.value = c;
    overview.value = o;
    students.value = s;
    assessments.value = a;
    benotungsarten.value = t;

    await nextTick();
    renderChart();
  } catch (e) {
    error.value = e?.message || "Unbekannter Fehler";
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  syncCourseSchemeUi();
  loadAll();
});

watch(kursId, (v) => {
  if (!v) return;
  syncCourseSchemeUi();
  loadAll();
});

// --- Chart ---
const chartEl = ref(null);
let chart = null;

function renderChart() {
  if (!chartEl.value) return;
  if (chart) {
    chart.destroy();
    chart = null;
  }

  const trend = overview.value?.trend || [];
  const labels = trend.map((t) => (t.ym || "").replace("-", " "));
  const values = trend.map((t) => t.avgNote);

  chart = new Chart(chartEl.value, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          data: values,
          borderColor: "rgba(144, 125, 255, 1)",
          backgroundColor: "rgba(144, 125, 255, 0.12)",
          tension: 0.35,
          fill: true,
          pointRadius: 0,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: "#9aa0a6", maxRotation: 0 } },
        y: { grid: { color: "rgba(255,255,255,0.06)" }, ticks: { color: "#9aa0a6" } },
      },
    },
  });
}

onBeforeUnmount(() => {
  if (chart) chart.destroy();
});

// --- Navigation/actions ---
function goToAssessments() {
  router.push(`/lehrer/faecher/${kursId.value}/leistungsfeststellungen`);
}

function openAssessment(id) {
  router.push(`/lehrer/leistungsfeststellungen/${id}`);
}

async function removeAssessment(id) {
  if (!confirm("Leistungsfeststellung löschen?")) return;
  try {
    await deleteAssessment(id);
    assessments.value = await getAssessmentsForCourse(
      kursId.value,
      search.value ? { search: search.value } : {}
    );
  } catch (e) {
    alert("Konnte nicht löschen.");
    console.warn(e);
  }
}

async function removeCourse() {
  const courseName = course.value?.name || "diesen Kurs";
  if (!confirm(`Kurs \"${courseName}\" wirklich löschen?`)) return;

  try {
    await deleteCourse(kursId.value);
    router.push("/lehrer/faecher");
  } catch (e) {
    const apiError = e?.response?.data?.error;
    alert(apiError ? `Kurs konnte nicht gelöscht werden: ${apiError}` : "Kurs konnte nicht gelöscht werden.");
    console.warn(e);
  }
}

// --- Modal create assessment ---
const createOpen = ref(false);
const createSaving = ref(false);

const createForm = ref({
  thema: "",
  benotungsartId: "",
  maxPunkte: null,
  datum: new Date().toISOString().slice(0, 10),
  gewichtungProzent: null,
});

function openCreateAssessment() {
  grading.setActiveSchemeIdForCourse(kursId.value, courseSchemeId.value)
  createOpen.value = true
}

function closeCreateAssessment() {
  createOpen.value = false;
  createSaving.value = false;
}

async function submitCreateAssessment() {
  if (!createForm.value.thema || !createForm.value.datum) {
    alert("Bitte Thema und Datum ausfüllen.");
    return;
  }
  createSaving.value = true;
  try {
    await createAssessment(kursId.value, { ...createForm.value });
    closeCreateAssessment();
    assessments.value = await getAssessmentsForCourse(kursId.value);
    overview.value = await getCourseOverview(kursId.value);
    await nextTick();
    renderChart();
  } catch (e) {
    alert("Konnte nicht erstellen.");
    console.warn(e);
  } finally {
    createSaving.value = false;
  }
}
</script>


<style scoped>
.page {
  max-width: 1400px;
  margin: 0 auto;
  padding-bottom: 2rem;
}

.breadcrumb {
  margin: 0.25rem 0 0.75rem;
}

.back {
  color: var(--muted);
  text-decoration: none;
}

.head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.course-title {
  margin: 0;
  font-size: 1.9rem;
  font-weight: 650;
}

.head-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn {
  border-radius: 999px;
  padding: 0.65rem 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background-image: linear-gradient(to right, var(--primary), var(--secondary));
  color: #fff;
  font-weight: 650;
  cursor: pointer;
}

.btn.ghost {
  background: rgba(255, 255, 255, 0.06);
  color: var(--text);
}

.btn.danger {
  background: rgba(204, 45, 45, 0.92);
  color: #fff;
  border-color: rgba(204, 45, 45, 0.92);
}

.panel {
  margin-top: 1rem;
}

.search {
  width: 420px;
  max-width: 100%;
  border-radius: 999px;
  padding: 0.55rem 1rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text);
  outline: none;
}

.overview {
  display: grid;
  grid-template-columns: 340px 340px 1fr;
  gap: 1.2rem;
  margin-top: 1rem;
  align-items: start;
}

.stats {
  display: grid;
  gap: 1rem;
}

.stat {
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
  padding: 1rem;
}

.label {
  color: var(--muted);
  font-size: 0.9rem;
}

.value {
  font-size: 2.2rem;
  font-weight: 700;
  margin-top: 0.2rem;
}

.chart-card {
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
  padding: 1rem;
}

.chart-label {
  color: var(--muted);
  font-size: 0.95rem;
  margin-bottom: 0.75rem;
}

.icon-action {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: transparent;
  color: var(--text);
  cursor: pointer;
  margin-left: 0.5rem;
}

.form {
  display: grid;
  gap: 1rem;
  padding-top: 0.5rem;
}

.row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
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

.empty {
  padding: 2rem 0;
  color: var(--muted);
}
.scheme-card {
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
  padding: 1rem;
}

.scheme-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.scheme-title {
  font-weight: 700;
  font-size: 1rem;
}

.scheme-sub {
  margin-top: 0.25rem;
  color: var(--muted);
  font-size: 0.85rem;
  line-height: 1.25rem;
}

.scheme-field {
  display: grid;
  gap: 0.35rem;
}

.scheme-label {
  color: var(--muted);
  font-size: 0.85rem;
}

.scheme-select {
  border-radius: 10px;
  padding: 0.65rem 0.85rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.05);
  color: var(--text);
  outline: none;
}

html:not(.dark) .scheme-select {
  background: rgba(0, 0, 0, 0.04);
}

@media (max-width: 768px) {
  .head {
    flex-direction: column;
    align-items: stretch;
  }

  .head-actions {
    flex-direction: column;
    flex-wrap: wrap;
  }

  .head-actions .btn {
    width: 100%;
  }

  .search {
    width: 100%;
  }

  .overview {
    grid-template-columns: 1fr;
  }

  .row {
    grid-template-columns: 1fr;
  }

  .course-title {
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {
  .head-actions .btn {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
  }
}
</style>
