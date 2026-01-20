<template>
  <section class="page">
    <div class="breadcrumb">
      <router-link class="back" :to="backToCourse"
        >‹ {{ backLabel }}</router-link
      >
    </div>

    <header class="head">
      <div class="title-wrap">
        <h1 class="title">{{ detail ? detail.thema : "—" }}</h1>
        <div class="sub">
          {{ detail?.datum ? formatDate(detail.datum) : "" }}
        </div>
      </div>
      <button class="btn primary" @click="openCreate">
        Neue Schülerleistung erstellen
      </button>
    </header>

    <div v-if="loading" class="empty">Lade …</div>
    <div v-else-if="error" class="empty">Fehler: {{ error }}</div>

    <div v-else>
      <div class="kpis">
        <div class="kpi">
          <div class="kpi-label">Klassenschnitt</div>
          <div class="kpi-value">
            {{
              detail?.klassenschnittProzent != null &&
              detail?.klassenschnitt != null
                ? `${detail.klassenschnittProzent}% (${formatGrade(detail.klassenschnitt)})`
                : "—"
            }}
          </div>
        </div>
        <div class="kpi">
          <div class="kpi-label">Gewichtung</div>
          <div class="kpi-value">
            {{
              detail?.gewichtungProzent != null
                ? `${detail.gewichtungProzent}%`
                : "—"
            }}
          </div>
        </div>
        <div class="kpi">
          <div class="kpi-label">Teilnahmequote</div>
          <div class="kpi-value">
            {{
              detail?.teilnahmequote != null ? `${detail.teilnahmequote}%` : "—"
            }}
          </div>
        </div>
        <div class="kpi">
          <div class="kpi-label">mögliche Punkte</div>
          <div class="kpi-value">
            {{ detail?.maxPunkte != null ? detail.maxPunkte : "—" }}
          </div>
        </div>
      </div>

      <div class="table-head">
        <div class="table-title">Schülerleistungen</div>
        <input
          v-model="search"
          class="search"
          placeholder="Nach Schüler*in suchen"
        />
      </div>

      <DataTable
        :columns="columns"
        :rows="filteredRows"
        row-key="id"
        empty-text="Keine Daten vorhanden."
      >
        <template #cell-leistung="{ row }">
          <span v-if="row.punkte != null && detail?.maxPunkte != null">
            {{ row.punkte }} Punkte ({{
              Math.round((row.punkte / (detail.maxPunkte || 1)) * 100)
            }}%)
          </span>
          <span v-else>—</span>
        </template>
        <template #cell-note="{ row }">{{
          row.note != null ? formatGrade(row.note) : "—"
        }}</template>
        <template #cell-datum="{ row }">{{ formatDate(row.datum) }}</template>
        <template #cell-kommentar="{ row }">{{
          row.kommentar || "—"
        }}</template>
      </DataTable>
    </div>

    <ModalForm
      :open="createOpen"
      title="Neue Schülerleistung erstellen"
      @close="closeCreate"
    >
      <div class="form">
        <!-- ✅ Aktives Schema im Modal -->
        <div class="scheme-inline">
          <div class="scheme-inline-title">Aktives Bewertungsschema</div>
          <div class="scheme-inline-sub">
            Dieses Schema wird für die Berechnung in diesem Fach verwendet.
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

        <!-- dein bestehendes Formular -->
        <label class="field">
          <span class="field-label">Schüler*in</span>
          <select v-model="createForm.schuelerId" class="input">
            <option value="">Schüler*in auswählen</option>
            <option v-for="s in students" :key="s.id" :value="s.id">
              {{ s.vorname }} {{ s.nachname }}
            </option>
          </select>
        </label>

        <div class="row">
          <label class="field">
            <span class="field-label">Punkte</span>
            <input
              v-model.number="createForm.punkte"
              class="input"
              type="number"
              min="0"
              :max="detail?.maxPunkte || 9999"
            />
          </label>

          <label class="field">
            <span class="field-label">Note</span>
            <input
              v-model.number="createForm.note"
              class="input"
              type="number"
              min="1"
              max="5"
              step="0.1"
            />
          </label>

          <label class="field">
            <span class="field-label">Datum</span>
            <input v-model="createForm.datum" class="input" type="date" />
          </label>
        </div>

        <label class="field">
          <span class="field-label">Kommentar</span>
          <textarea
            v-model="createForm.kommentar"
            class="input textarea"
            rows="3"
          />
        </label>
      </div>

      <template #actions>
        <button class="btn ghost" type="button" @click="closeCreate">
          Abbrechen
        </button>
        <button
          class="btn primary"
          type="button"
          :disabled="saving"
          @click="submitCreate"
        >
          Schülerleistung erstellen
        </button>
      </template>
    </ModalForm>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import DataTable from "@/components/DataTable.vue";
import ModalForm from "@/components/ModalForm.vue";
import grading from "@/services/grading";
import {
  createStudentResult,
  getAssessmentDetail,
  getCourseStudents,
} from "@/services/teacherData";

const route = useRoute();
const router = useRouter();

const id = computed(() => route.params.id);

const loading = ref(false);
const error = ref("");
const detail = ref(null);
const students = ref([]);

const search = ref("");
const columns = [
  { key: "vorname", label: "Vorname" },
  { key: "nachname", label: "Nachname" },
  { key: "leistung", label: "Leistung" },
  { key: "note", label: "Note" },
  { key: "datum", label: "Datum" },
  { key: "kommentar", label: "Kommentar", width: "42%" },
];

const filteredRows = computed(() => {
  const list = detail.value?.schuelerleistungen || [];
  if (!search.value) return list;
  const q = search.value.toLowerCase();
  return list.filter((r) =>
    `${r.vorname} ${r.nachname}`.toLowerCase().includes(q),
  );
});

// ✅ Kurs-ID kommt aus Assessment-Detail
const kursId = computed(() => detail.value?.kurs?.id ?? null);

const backToCourse = computed(() => {
  const kid = detail.value?.kurs?.id;
  return kid
    ? `/lehrer/faecher/${kid}/leistungsfeststellungen`
    : "/lehrer/faecher";
});

const backLabel = computed(() =>
  detail.value?.kurs?.name ? "Meine Fächer" : "Meine Fächer",
);

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

async function load() {
  loading.value = true;
  error.value = "";
  try {
    detail.value = await getAssessmentDetail(id.value);

    const kid = detail.value?.kurs?.id;
    if (kid) {
      students.value = await getCourseStudents(kid);
    } else {
      students.value = [];
    }
  } catch (e) {
    error.value = e?.message || "Unbekannter Fehler";
  } finally {
    loading.value = false;
  }
}

onMounted(load);
watch(id, load);

// --------------------
// ✅ Schema im Modal
// --------------------
const schemes = ref([]);
const courseSchemeId = ref("default");

function syncCourseSchemeUi() {
  schemes.value = grading.loadAllSchemes();

  const kid = kursId.value;
  courseSchemeId.value =
    (kid != null ? grading.getActiveSchemeIdForCourse?.(kid) : null) ||
    grading.getActiveSchemeId?.() ||
    "default";
}

function saveCourseScheme() {
  const kid = kursId.value;
  if (kid == null) return;
  grading.setActiveSchemeIdForCourse(kid, courseSchemeId.value);
}

// --------------------
// ✅ Modal Schülerleistung
// --------------------
const createOpen = ref(false);
const saving = ref(false);

const createForm = ref({
  schuelerId: "",
  punkte: null,
  note: null,
  datum: new Date().toISOString().slice(0, 10),
  kommentar: "",
});

function openCreate() {
  syncCourseSchemeUi(); // ✅ damit es immer aktuell ist
  createOpen.value = true;
}

function closeCreate() {
  createOpen.value = false;
  saving.value = false;
}

async function submitCreate() {
  if (!createForm.value.schuelerId || createForm.value.note == null) {
    alert("Bitte Schüler*in und Note ausfüllen.");
    return;
  }

  saving.value = true;
  try {
    await createStudentResult(id.value, {
      schuelerId: createForm.value.schuelerId,
      punkte: createForm.value.punkte,
      note: createForm.value.note,
      datum: createForm.value.datum,
      kommentar: createForm.value.kommentar,
    });
    closeCreate();
    await load();
  } catch (e) {
    alert("Konnte nicht erstellen.");
    console.warn(e);
  } finally {
    saving.value = false;
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
