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

    </div>

    <!-- ================= Schüler ================= -->
    <div v-else-if="tab === 'students'" class="panel">
      <div class="student-manage">
        <div class="student-manage-head">
          <div>
            <div class="student-manage-title">Schüler*innen hinzufügen</div>
            <div class="student-manage-sub">
              Nach Namen suchen, auswählen und anschließend zum Kurs hinzufügen.
            </div>
          </div>
          <button
            class="btn primary"
            type="button"
            :disabled="addingStudents || selectedStudentsToAdd.length === 0"
            @click="addStudentsToCurrentCourse"
          >
            {{ addingStudents ? "Fügt hinzu …" : "Ausgewählte hinzufügen" }}
          </button>
        </div>

        <input
          v-model="studentSearchQuery"
          class="input"
          type="text"
          placeholder="Schüler*in suchen"
        />

        <div v-if="selectedStudentsToAdd.length" class="student-chip-list">
          <button
            v-for="student in selectedStudentsToAdd"
            :key="student.id"
            class="student-chip"
            type="button"
            @click="removePendingStudent(student.id)"
          >
            {{ formatStudentLabel(student) }} ×
          </button>
        </div>

        <div
          v-if="studentSearchQuery.trim().length < MIN_STUDENT_QUERY_LENGTH"
          class="student-search-hint"
        >
          Mindestens {{ MIN_STUDENT_QUERY_LENGTH }} Zeichen eingeben.
        </div>
        <div v-else-if="studentSearchLoading" class="student-search-hint">
          Suche läuft …
        </div>
        <div v-else-if="studentSearchError" class="student-search-error">
          {{ studentSearchError }}
        </div>
        <div v-else-if="studentSearchResults.length" class="student-search-results">
          <button
            v-for="student in studentSearchResults"
            :key="student.id"
            class="student-search-result"
            type="button"
            @click="addPendingStudent(student)"
          >
            {{ formatStudentLabel(student) }}
          </button>
        </div>
      </div>

      <DataTable
        :columns="studentColumns"
        :rows="students"
        row-key="id"
        empty-text="Keine Daten vorhanden."
      >
        <template #cell-gesamtnote="{ row }">
          {{ row.gesamtnote != null ? formatGrade(row.gesamtnote) : "—" }}
        </template>
        <template #actions="{ row }">
          <button
            class="icon-action danger"
            type="button"
            title="Schüler*in entfernen"
            @click="removeStudent(row)"
          >
            <Trash2 :size="18" />
          </button>
        </template>
      </DataTable>
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
          <span v-if="selectedBenotungsart?.gewichtung != null" class="field-help">
            Standardgewichtung: {{ formatWeight(selectedBenotungsart.gewichtung) }}%
          </span>
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import grading from "@/services/grading";
import { ExternalLink, Trash2 } from "lucide-vue-next";
import DataTable from "@/components/DataTable.vue";
import ModalForm from "@/components/ModalForm.vue";
import TgTabs from "@/components/TgTabs.vue";
import {
  createAssessment,
  addCourseStudents,
  deleteCourse,
  deleteAssessment,
  getAssessmentsForCourse,
  getCourseDetail,
  getCourseOverview,
  getCourseStudents,
  getGradingTypes,
  removeCourseStudent,
  searchCourseStudents,
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
const studentSearchQuery = ref("");
const studentSearchResults = ref([]);
const selectedStudentsToAdd = ref([]);
const studentSearchLoading = ref(false);
const studentSearchError = ref("");
const addingStudents = ref(false);
const MIN_STUDENT_QUERY_LENGTH = 2;
let studentSearchTimer = null;
let studentSearchRequestId = 0;

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
  { key: "gesamtnote", label: "Gesamtnote" },
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

function formatWeight(v) {
  if (v == null || v === "") return "0";
  return Number(v).toFixed(2).replace(/\.00$/, "").replace(".", ",");
}

function formatStudentLabel(student) {
  const klasse = student?.klasse ? ` (${student.klasse})` : "";
  return `${student?.vorname || ""} ${student?.nachname || ""}${klasse}`.trim();
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

function resetStudentSearch() {
  if (studentSearchTimer) {
    clearTimeout(studentSearchTimer);
    studentSearchTimer = null;
  }
  studentSearchRequestId += 1;
  studentSearchQuery.value = "";
  studentSearchResults.value = [];
  selectedStudentsToAdd.value = [];
  studentSearchLoading.value = false;
  studentSearchError.value = "";
}

function addPendingStudent(student) {
  const id = Number(student.id);
  if (selectedStudentsToAdd.value.some((item) => Number(item.id) === id)) return;

  selectedStudentsToAdd.value.push({
    id,
    vorname: student.vorname || "",
    nachname: student.nachname || "",
    klasse: student.klasse || "",
  });
  studentSearchQuery.value = "";
  studentSearchResults.value = [];
  studentSearchError.value = "";
}

function removePendingStudent(studentId) {
  const id = Number(studentId);
  selectedStudentsToAdd.value = selectedStudentsToAdd.value.filter(
    (student) => Number(student.id) !== id,
  );
}

async function searchStudentsForCourse(query) {
  const requestId = ++studentSearchRequestId;
  studentSearchLoading.value = true;
  studentSearchError.value = "";

  try {
    const data = await searchCourseStudents(query, 20);
    if (requestId !== studentSearchRequestId) return;

    const existingIds = new Set(students.value.map((student) => Number(student.id)));
    const selectedIds = new Set(selectedStudentsToAdd.value.map((student) => Number(student.id)));
    studentSearchResults.value = data.filter((student) => {
      const id = Number(student.id);
      return !existingIds.has(id) && !selectedIds.has(id);
    });
  } catch (e) {
    if (requestId !== studentSearchRequestId) return;
    const apiError = e?.response?.data?.error;
    studentSearchError.value = apiError || e?.message || "Schüler konnten nicht geladen werden.";
    studentSearchResults.value = [];
  } finally {
    if (requestId === studentSearchRequestId) {
      studentSearchLoading.value = false;
    }
  }
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
  resetStudentSearch();
  loadAll();
});

watch(
  () => studentSearchQuery.value,
  (value) => {
    if (studentSearchTimer) {
      clearTimeout(studentSearchTimer);
      studentSearchTimer = null;
    }

    const query = value.trim();
    if (query.length < MIN_STUDENT_QUERY_LENGTH) {
      studentSearchRequestId += 1;
      studentSearchLoading.value = false;
      studentSearchError.value = "";
      studentSearchResults.value = [];
      return;
    }

    studentSearchTimer = setTimeout(() => {
      searchStudentsForCourse(query);
    }, 300);
  },
);

onBeforeUnmount(() => {
  if (studentSearchTimer) {
    clearTimeout(studentSearchTimer);
    studentSearchTimer = null;
  }
});

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

async function removeStudent(student) {
  const studentName = `${student?.vorname || ""} ${student?.nachname || ""}`.trim() || "diese Person";
  if (!confirm(`Schüler*in \"${studentName}\" aus diesem Kurs entfernen? Bereits erfasste Bewertungen in diesem Kurs werden ebenfalls entfernt.`)) {
    return;
  }

  try {
    await removeCourseStudent(kursId.value, student.id);
    await loadAll();
  } catch (e) {
    const apiError = e?.response?.data?.error;
    alert(apiError || "Schüler*in konnte nicht entfernt werden.");
    console.warn(e);
  }
}

async function addStudentsToCurrentCourse() {
  if (selectedStudentsToAdd.value.length === 0) return;

  addingStudents.value = true;
  try {
    await addCourseStudents(
      kursId.value,
      selectedStudentsToAdd.value.map((student) => Number(student.id)),
    );
    await loadAll();
    resetStudentSearch();
  } catch (e) {
    const apiError = e?.response?.data?.error;
    alert(apiError || "Schüler*innen konnten nicht hinzugefügt werden.");
    console.warn(e);
  } finally {
    addingStudents.value = false;
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
const selectedBenotungsart = computed(() =>
  benotungsarten.value.find(
    (item) => String(item.id) === String(createForm.value.benotungsartId),
  ) || null,
);

function openCreateAssessment() {
  grading.setActiveSchemeIdForCourse(kursId.value, courseSchemeId.value)
  createOpen.value = true
}

function closeCreateAssessment() {
  createOpen.value = false;
  createSaving.value = false;
  createForm.value = {
    thema: "",
    benotungsartId: "",
    maxPunkte: null,
    datum: new Date().toISOString().slice(0, 10),
    gewichtungProzent: null,
  };
}

async function submitCreateAssessment() {
  if (!createForm.value.thema || !createForm.value.datum) {
    alert("Bitte Thema und Datum ausfüllen.");
    return;
  }
  if (
    createForm.value.gewichtungProzent != null &&
    (Number(createForm.value.gewichtungProzent) < 0 ||
      Number(createForm.value.gewichtungProzent) > 100)
  ) {
    alert("Die Gewichtung muss zwischen 0 und 100 liegen.");
    return;
  }
  if (
    createForm.value.maxPunkte != null &&
    Number(createForm.value.maxPunkte) < 0
  ) {
    alert("Die möglichen Punkte dürfen nicht negativ sein.");
    return;
  }
  createSaving.value = true;
  try {
    await createAssessment(kursId.value, { ...createForm.value });
    closeCreateAssessment();
    assessments.value = await getAssessmentsForCourse(kursId.value);
    overview.value = await getCourseOverview(kursId.value);
  } catch (e) {
    alert("Konnte nicht erstellen.");
    console.warn(e);
  } finally {
    createSaving.value = false;
  }
}

watch(
  () => createForm.value.benotungsartId,
  (value) => {
    const type = benotungsarten.value.find((item) => String(item.id) === String(value));
    if (!type) {
      return;
    }

    const weight = type.gewichtung != null ? Number(type.gewichtung) : null;
    if (weight == null || Number.isNaN(weight)) {
      return;
    }

    createForm.value.gewichtungProzent = weight;
  },
);
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

.student-manage {
  display: grid;
  gap: 0.85rem;
  margin-bottom: 1rem;
  padding: 1rem;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
}

.student-manage-head {
  display: flex;
  align-items: start;
  justify-content: space-between;
  gap: 1rem;
}

.student-manage-title {
  font-weight: 700;
}

.student-manage-sub {
  margin-top: 0.25rem;
  color: var(--muted);
  font-size: 0.85rem;
}

.student-chip-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.student-chip {
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.06);
  color: var(--text);
  border-radius: 999px;
  padding: 0.45rem 0.8rem;
  cursor: pointer;
}

.student-search-results {
  display: grid;
  gap: 0.5rem;
}

.student-search-result {
  text-align: left;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.04);
  color: var(--text);
  padding: 0.75rem 0.9rem;
  cursor: pointer;
}

.student-search-hint,
.student-search-error {
  font-size: 0.9rem;
  color: var(--muted);
}

.student-search-error {
  color: #fca5a5;
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
  grid-template-columns: minmax(280px, 360px) minmax(320px, 1fr);
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

.icon-action.danger {
  color: #fca5a5;
  border-color: rgba(248, 113, 113, 0.35);
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

.field-help {
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
  min-height: 100%;
  display: grid;
  align-content: start;
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

  .student-manage-head {
    flex-direction: column;
    align-items: stretch;
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
