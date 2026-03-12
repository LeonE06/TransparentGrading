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
      <div class="head-actions">
        <button class="btn ghost" type="button" @click="downloadCsvTemplate">
          CSV-Vorlage herunterladen
        </button>
        <button
          class="btn ghost"
          type="button"
          :disabled="importing"
          @click="openCsvImport"
        >
          {{ importing ? "CSV wird importiert…" : "CSV importieren" }}
        </button>
        <button class="btn primary" type="button" @click="openCreate">
          Neue Schülerleistung erstellen
        </button>
      </div>
    </header>

    <input
      ref="csvFileInput"
      type="file"
      accept=".csv,text/csv"
      class="sr-only"
      @change="handleCsvImport"
    />

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
              v-if="detail?.maxPunkte != null"
              :value="autoCalculatedNoteDisplay"
              class="input"
              type="text"
              readonly
            />
            <input
              v-else
              v-model.number="createForm.note"
              class="input"
              type="number"
              min="1"
              max="5"
              step="0.1"
            />
            <span v-if="detail?.maxPunkte != null" class="field-help">
              Wird automatisch aus Punkten und Bewertungsschema berechnet.
            </span>
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
import { useRoute } from "vue-router";
import DataTable from "@/components/DataTable.vue";
import ModalForm from "@/components/ModalForm.vue";
import grading from "@/services/grading";
import {
  createStudentResult,
  getAssessmentDetail,
  getCourseStudents,
} from "@/services/teacherData";

const route = useRoute();

const id = computed(() => route.params.id);

const loading = ref(false);
const error = ref("");
const detail = ref(null);
const students = ref([]);
const csvFileInput = ref(null);
const importing = ref(false);

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

const backToCourse = computed(() =>
  kursId.value ? `/lehrer/faecher/${kursId.value}` : "/lehrer/faecher"
);

const backLabel = computed(() =>
  detail.value?.kurs?.name ? detail.value.kurs.name : "Meine Fächer"
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

function formatCsvDate(value) {
  if (!value) return "";
  const text = String(value);
  const isoMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (isoMatch) {
    return `${isoMatch[3]}.${isoMatch[2]}.${isoMatch[1]}`;
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();
  return `${day}.${month}.${year}`;
}

function csvCell(value) {
  const text = value == null ? "" : String(value);
  if (text.includes('"') || text.includes(";") || text.includes("\n")) {
    return `"${text.replace(/"/g, '""')}"`;
  }
  return text;
}

function csvNumber(value) {
  if (value == null || value === "") return "";
  return String(value).replace(".", ",");
}

function openCsvImport() {
  if (importing.value) return;
  csvFileInput.value?.click();
}

function downloadCsvTemplate() {
  if (!students.value.length) {
    alert("Keine Schüler*innen im Kurs gefunden.");
    return;
  }

  const existingRows = detail.value?.schuelerleistungen || [];
  const existingByStudentId = new Map(
    existingRows.map((row) => [Number(row.schuelerId), row]),
  );

  const header = [
    "Vorname",
    "Nachname",
    "Leistung(Punkten)",
    "Note",
    "Datum",
    "Kommentar",
  ];

  const rows = students.value.map((student) => {
    const existing = existingByStudentId.get(Number(student.id));
    return [
      student.vorname || "",
      student.nachname || "",
      existing?.punkte ?? "",
      csvNumber(existing?.note),
      formatCsvDate(existing?.datum || detail.value?.datum),
      existing?.kommentar || "",
    ];
  });

  const csvContent =
    "\uFEFF" +
    [header, ...rows]
      .map((row) => row.map((value) => csvCell(value)).join(";"))
      .join("\n");

  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const href = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = href;
  link.download = `leistungsfeststellung_${id.value}_vorlage.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(href);
}

function normalizeText(value) {
  return String(value || "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, " ");
}

function parseCsv(text) {
  const src = String(text || "")
    .replace(/^\uFEFF/, "")
    .replace(/\r\n/g, "\n")
    .replace(/\r/g, "\n");

  const firstLine = src.split("\n")[0] || "";
  const delimiter = firstLine.includes(";") ? ";" : ",";

  const rows = [];
  let row = [];
  let cell = "";
  let inQuotes = false;

  for (let i = 0; i < src.length; i += 1) {
    const ch = src[i];

    if (ch === '"') {
      if (inQuotes && src[i + 1] === '"') {
        cell += '"';
        i += 1;
      } else {
        inQuotes = !inQuotes;
      }
      continue;
    }

    if (ch === delimiter && !inQuotes) {
      row.push(cell);
      cell = "";
      continue;
    }

    if (ch === "\n" && !inQuotes) {
      row.push(cell);
      rows.push(row);
      row = [];
      cell = "";
      continue;
    }

    cell += ch;
  }

  row.push(cell);
  rows.push(row);

  return rows.filter((r) => r.some((c) => String(c).trim() !== ""));
}

function resolveCsvColumns(headerRow) {
  const headers = headerRow.map((value) =>
    normalizeText(value).replace(/[()]/g, ""),
  );
  const findIndex = (candidates) =>
    headers.findIndex((header) =>
      candidates.some((candidate) => header.includes(candidate)),
    );

  return {
    vorname: findIndex(["vorname", "first name"]),
    nachname: findIndex(["nachname", "last name"]),
    punkte: findIndex(["leistung", "punkte", "punkten", "points"]),
    note: findIndex(["note", "grade"]),
    datum: findIndex(["datum", "date"]),
    kommentar: findIndex(["kommentar", "comment"]),
  };
}

function parseOptionalNumber(raw, lineNumber, label) {
  const text = String(raw || "").trim();
  if (!text) return null;
  const parsed = Number(text.replace(",", "."));
  if (Number.isNaN(parsed)) {
    throw new Error(`Zeile ${lineNumber}: Ungültiger Wert in "${label}"`);
  }
  return parsed;
}

function parseOptionalDate(raw, lineNumber) {
  const text = String(raw || "").trim();
  if (!text) return null;

  if (/^\d{4}-\d{2}-\d{2}$/.test(text)) {
    return text;
  }

  const m = text.match(/^(\d{1,2})[./-](\d{1,2})[./-](\d{4})$/);
  if (!m) {
    throw new Error(
      `Zeile ${lineNumber}: Datum muss YYYY-MM-DD oder DD.MM.YYYY sein`,
    );
  }

  const day = String(Number(m[1])).padStart(2, "0");
  const month = String(Number(m[2])).padStart(2, "0");
  const year = m[3];
  return `${year}-${month}-${day}`;
}

async function handleCsvImport(event) {
  const file = event?.target?.files?.[0];
  if (!file) return;

  importing.value = true;
  try {
    const text = await file.text();
    const rows = parseCsv(text);
    if (rows.length < 2) {
      throw new Error("CSV enthält keine Datenzeilen.");
    }

    const columns = resolveCsvColumns(rows[0]);
    if (columns.vorname < 0 || columns.nachname < 0) {
      throw new Error("CSV benötigt mindestens die Spalten Vorname und Nachname.");
    }

    const studentsByName = new Map();
    for (const student of students.value) {
      const key = `${normalizeText(student.vorname)}|${normalizeText(student.nachname)}`;
      const list = studentsByName.get(key) || [];
      list.push(student);
      studentsByName.set(key, list);
    }

    let imported = 0;
    const issues = [];

    for (let i = 1; i < rows.length; i += 1) {
      const row = rows[i];
      const lineNumber = i + 1;

      const vorname = String(row[columns.vorname] || "").trim();
      const nachname = String(row[columns.nachname] || "").trim();
      const rowHasContent = row.some((value) => String(value || "").trim() !== "");
      if (!rowHasContent) continue;

      if (!vorname || !nachname) {
        issues.push(`Zeile ${lineNumber}: Vorname/Nachname fehlt.`);
        continue;
      }

      const key = `${normalizeText(vorname)}|${normalizeText(nachname)}`;
      const matches = studentsByName.get(key) || [];
      if (matches.length === 0) {
        issues.push(`Zeile ${lineNumber}: ${vorname} ${nachname} nicht im Kurs.`);
        continue;
      }
      if (matches.length > 1) {
        issues.push(`Zeile ${lineNumber}: ${vorname} ${nachname} ist nicht eindeutig.`);
        continue;
      }

      try {
        const punkteRaw = columns.punkte >= 0 ? row[columns.punkte] : "";
        const noteRaw = columns.note >= 0 ? row[columns.note] : "";
        const datumRaw = columns.datum >= 0 ? row[columns.datum] : "";
        const kommentarRaw = columns.kommentar >= 0 ? row[columns.kommentar] : "";

        const punkte = parseOptionalNumber(punkteRaw, lineNumber, "Leistung(Punkten)");
        const note = parseOptionalNumber(noteRaw, lineNumber, "Note");
        const derivedNote = note ?? calculateNoteFromPoints(punkte);
        if (derivedNote == null) {
          throw new Error(
            `Zeile ${lineNumber}: Note fehlt und konnte nicht aus den Punkten berechnet werden`,
          );
        }
        const datum = parseOptionalDate(datumRaw, lineNumber);
        const kommentar = String(kommentarRaw || "").trim() || null;

        await createStudentResult(id.value, {
          schuelerId: matches[0].id,
          punkte: punkte != null ? Math.round(punkte) : null,
          note: derivedNote,
          datum,
          kommentar,
        });
        imported += 1;
      } catch (importError) {
        const apiError = importError?.response?.data?.error;
        issues.push(
          `Zeile ${lineNumber}: ${apiError || importError?.message || "Import fehlgeschlagen"}`,
        );
      }
    }

    await load();

    let message = `${imported} Schülerleistungen importiert.`;
    if (issues.length > 0) {
      const preview = issues.slice(0, 8).join("\n");
      const more = issues.length > 8 ? `\n... +${issues.length - 8} weitere` : "";
      message += `\n\nProbleme:\n${preview}${more}`;
    }
    alert(message);
  } catch (e) {
    alert(e?.message || "CSV konnte nicht importiert werden.");
  } finally {
    importing.value = false;
    if (event?.target) {
      event.target.value = "";
    }
  }
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
const activeCourseScheme = computed(
  () => grading.getActiveSchemeForCourse?.(kursId.value)?.scheme || grading.loadScheme(),
);

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

function calculateNoteFromPoints(points) {
  const maxPoints = Number(detail.value?.maxPunkte);
  if (!Number.isFinite(maxPoints) || maxPoints <= 0) return null;
  if (points == null || points === "") return null;

  const numericPoints = Number(points);
  if (!Number.isFinite(numericPoints)) return null;

  const scheme = activeCourseScheme.value || {};
  const bands =
    Array.isArray(scheme.gradeBands) && scheme.gradeBands.length > 0
      ? scheme.gradeBands
      : undefined;
  const percentage = Math.max(0, Math.min(100, (numericPoints / maxPoints) * 100));

  return grading.percentageToGrade(percentage, bands);
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
const autoCalculatedNote = computed(() => calculateNoteFromPoints(createForm.value.punkte));
const autoCalculatedNoteDisplay = computed(() =>
  autoCalculatedNote.value != null ? formatGrade(autoCalculatedNote.value) : "—",
);

function openCreate() {
  syncCourseSchemeUi(); // ✅ damit es immer aktuell ist
  createOpen.value = true;
}

function closeCreate() {
  createOpen.value = false;
  saving.value = false;
  createForm.value = {
    schuelerId: "",
    punkte: null,
    note: null,
    datum: new Date().toISOString().slice(0, 10),
    kommentar: "",
  };
}

async function submitCreate() {
  const noteForSubmission =
    detail.value?.maxPunkte != null
      ? autoCalculatedNote.value
      : createForm.value.note;

  if (!createForm.value.schuelerId || noteForSubmission == null) {
    alert("Bitte Schüler*in und eine gültige Leistung erfassen.");
    return;
  }

  saving.value = true;
  try {
    await createStudentResult(id.value, {
      schuelerId: createForm.value.schuelerId,
      punkte: createForm.value.punkte,
      note: noteForSubmission,
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

.head-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
  justify-content: flex-end;
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
  background-image: linear-gradient(to right, var(--primary), var(--secondary));
  color: #fff;
  font-weight: 650;
  cursor: pointer;
}

.btn.ghost {
  background: rgba(255, 255, 255, 0.06);
  color: var(--text);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
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
  max-width: 100%;
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

@media (max-width: 768px) {
  .page {
    max-width: 100%;
  }

  .head {
    flex-direction: column;
    align-items: stretch;
  }

  .head-actions {
    justify-content: stretch;
  }

  .head-actions .btn {
    width: 100%;
  }

  .table-head {
    flex-direction: column;
    align-items: stretch;
  }

  .search {
    width: 100%;
  }

  .kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .row {
    grid-template-columns: 1fr;
  }

  .title {
    font-size: 1.35rem;
  }
}

@media (max-width: 480px) {
  .kpis {
    grid-template-columns: 1fr;
  }
}
</style>
