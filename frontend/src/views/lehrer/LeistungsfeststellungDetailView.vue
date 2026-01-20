<template>
  <section class="page">
    <div class="breadcrumb">
      <router-link class="back" to="/lehrer/faecher">
        ‹ Meine Fächer
      </router-link>
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
      </DataTable>
    </div>

    <!-- ================= Modal ================= -->
    <ModalForm
      :open="createOpen"
      title="Neue Leistungsfeststellung erstellen"
      @close="closeCreateAssessment"
    >
      <div class="form">
        <!-- ✅ Bewertungsschema im Modal -->
        <div class="scheme-inline">
          <div class="scheme-inline-title">Aktives Bewertungsschema</div>
          <div class="scheme-inline-sub">
            Dieses Schema wird für neue Leistungsfeststellungen in diesem Fach verwendet.
          </div>

          <label class="field">
            <span class="field-label">Schema auswählen</span>
            <select
              class="input"
              v-model="courseSchemeId"
              @change="saveCourseScheme"
            >
              <option v-for="s in schemes" :key="s.id" :value="s.id">
                {{ s.name }}
              </option>
            </select>
          </label>
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
          <span class="field-label">Feststellungsart</span>
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
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import grading from "@/services/grading";
import DataTable from "@/components/DataTable.vue";
import ModalForm from "@/components/ModalForm.vue";
import TgTabs from "@/components/TgTabs.vue";
import {
  createAssessment,
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

const schemes = ref([]);
const courseSchemeId = ref("default");

const tab = ref("overview");
const tabs = [
  { key: "overview", label: "Übersicht" },
  { key: "students", label: "Schüler*innen" },
  { key: "assessments", label: "Leistungsfeststellungen" },
];

const search = ref("");
const filteredAssessments = computed(() => {
  if (!search.value) return assessments.value;
  const q = search.value.toLowerCase();
  return assessments.value.filter((a) =>
    String(a.thema || "").toLowerCase().includes(q),
  );
});

const assessmentColumns = [
  { key: "thema", label: "Thema" },
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
  return new Date(d).toLocaleDateString("de-DE");
}

function formatGrade(v) {
  if (v == null) return "—";
  return Number(v).toFixed(1).replace(".", ",");
}

function syncCourseSchemeUi() {
  schemes.value = grading.loadAllSchemes();
  courseSchemeId.value =
    grading.getActiveSchemeIdForCourse(kursId.value) ||
    grading.getActiveSchemeId() ||
    "default";
}

function saveCourseScheme() {
  grading.setActiveSchemeIdForCourse(kursId.value, courseSchemeId.value);
}

async function loadAll() {
  loading.value = true;
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
  } catch (e) {
    error.value = e?.message || "Unbekannter Fehler";
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadAll();
  syncCourseSchemeUi();
});

watch(kursId, () => {
  loadAll();
  syncCourseSchemeUi();
});

function goToAssessments() {
  tab.value = "assessments";
}

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
  syncCourseSchemeUi();
  createOpen.value = true;
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
  } catch {
    alert("Konnte nicht erstellen.");
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
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.title-wrap {
  display: grid;
  gap: 0.2rem;
}

.title {
  margin: 0;
  font-size: 1.6rem;
  font-weight: 650;
}

.sub {
  color: var(--muted);
}

.btn {
  border-radius: 999px;
  padding: 0.65rem 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(144, 125, 255, 0.95);
  color: #fff;
  font-weight: 650;
  cursor: pointer;
}

.btn.ghost {
  background: rgba(255, 255, 255, 0.06);
  color: var(--text);
}

.kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
  margin: 0.5rem 0 1.25rem;
}

.kpi {
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
  padding: 0.9rem 1rem;
}

.kpi-label {
  color: var(--muted);
  font-size: 0.9rem;
}

.kpi-value {
  font-size: 1.6rem;
  font-weight: 750;
  margin-top: 0.25rem;
}

.table-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.table-title {
  font-weight: 650;
}

.search {
  width: 360px;
  max-width: 48vw;
  border-radius: 999px;
  padding: 0.55rem 1rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text);
  outline: none;
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

.textarea {
  resize: vertical;
}

.empty {
  padding: 2rem 0;
  color: var(--muted);
}
.scheme-inline {
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  padding: 0.9rem;
}

.scheme-inline-title {
  font-weight: 700;
  margin-bottom: 0.2rem;
}

.scheme-inline-sub {
  color: var(--muted);
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
  line-height: 1.25rem;
}
</style>
