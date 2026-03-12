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
        <button class="btn ghost" type="button" @click="exportCsv">
          CSV exportieren
        </button>
        <button class="btn ghost" type="button" @click="exportPdf">
          PDF exportieren
        </button>
        <button class="btn primary" type="button" @click="openCreate">
          Neue Schülerleistung erstellen
        </button>
      </div>
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

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function exportCsv() {
  if (!detail.value?.schuelerleistungen?.length) {
    alert("Keine Schülerleistungen zum Exportieren vorhanden.");
    return;
  }

  if (!window.confirm("CSV-Export für diese Schülerleistungen starten?")) {
    return;
  }

  const rows = [
    ["Vorname", "Nachname", "Leistung", "Note", "Datum", "Kommentar"],
    ...detail.value.schuelerleistungen.map((row) => [
      row.vorname || "",
      row.nachname || "",
      row.punkte != null && detail.value?.maxPunkte != null
        ? `${row.punkte} Punkte (${Math.round((row.punkte / (detail.value.maxPunkte || 1)) * 100)}%)`
        : "—",
      row.note != null ? csvNumber(row.note) : "",
      formatCsvDate(row.datum),
      row.kommentar || "",
    ]),
  ];

  const csvContent =
    "\uFEFF" + rows.map((row) => row.map((value) => csvCell(value)).join(";")).join("\n");

  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const href = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = href;
  link.download = `leistungsfeststellung_${String(detail.value?.thema || id.value).replace(/\s+/g, "_")}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(href);
}

function exportPdf() {
  if (!detail.value?.schuelerleistungen?.length) {
    alert("Keine Schülerleistungen zum Exportieren vorhanden.");
    return;
  }

  if (!window.confirm("PDF-Export für diese Schülerleistungen starten?")) {
    return;
  }

  const printWindow = window.open("", "_blank", "width=960,height=720");
  if (!printWindow) {
    console.warn("PDF-Export konnte nicht gestartet werden.");
    return;
  }

  const rows = detail.value.schuelerleistungen
    .map(
      (row) => `
      <tr>
        <td>${escapeHtml(row.vorname || "—")}</td>
        <td>${escapeHtml(row.nachname || "—")}</td>
        <td>${escapeHtml(
          row.punkte != null && detail.value?.maxPunkte != null
            ? `${row.punkte} Punkte (${Math.round((row.punkte / (detail.value.maxPunkte || 1)) * 100)}%)`
            : "—",
        )}</td>
        <td>${escapeHtml(row.note != null ? formatGrade(row.note) : "—")}</td>
        <td>${escapeHtml(formatDate(row.datum))}</td>
        <td>${escapeHtml(row.kommentar || "—")}</td>
      </tr>
    `,
    )
    .join("");

  printWindow.document.write(`
    <!doctype html>
    <html lang="de">
      <head>
        <meta charset="utf-8" />
        <title>Export ${escapeHtml(detail.value?.thema || "Leistungsfeststellung")}</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 32px; color: #1f2937; }
          h1 { margin: 0 0 8px; font-size: 28px; }
          .meta { margin-bottom: 24px; color: #4b5563; }
          .stats { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
          .stat { padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 12px; min-width: 160px; }
          .stat-label { font-size: 12px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
          .stat-value { font-size: 24px; font-weight: 700; }
          table { width: 100%; border-collapse: collapse; }
          th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
          th { font-size: 12px; text-transform: uppercase; color: #6b7280; }
        </style>
      </head>
      <body>
        <h1>Export ${escapeHtml(detail.value?.thema || "Leistungsfeststellung")}</h1>
        <div class="meta">
          <div><strong>Kurs:</strong> ${escapeHtml(detail.value?.kurs?.name || "—")}</div>
          <div><strong>Datum:</strong> ${escapeHtml(formatDate(detail.value?.datum))}</div>
          <div>Erstellt am ${escapeHtml(new Date().toLocaleDateString("de-DE"))}</div>
        </div>
        <div class="stats">
          <div class="stat">
            <div class="stat-label">Klassenschnitt</div>
            <div class="stat-value">${escapeHtml(
              detail.value?.klassenschnittProzent != null && detail.value?.klassenschnitt != null
                ? `${detail.value.klassenschnittProzent}% (${formatGrade(detail.value.klassenschnitt)})`
                : "—",
            )}</div>
          </div>
          <div class="stat">
            <div class="stat-label">Gewichtung</div>
            <div class="stat-value">${escapeHtml(
              detail.value?.gewichtungProzent != null ? `${detail.value.gewichtungProzent}%` : "—",
            )}</div>
          </div>
          <div class="stat">
            <div class="stat-label">Teilnahmequote</div>
            <div class="stat-value">${escapeHtml(
              detail.value?.teilnahmequote != null ? `${detail.value.teilnahmequote}%` : "—",
            )}</div>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Vorname</th>
              <th>Nachname</th>
              <th>Leistung</th>
              <th>Note</th>
              <th>Datum</th>
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
