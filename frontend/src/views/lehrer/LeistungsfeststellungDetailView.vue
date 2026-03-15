<template>
  <section class="page">
    <div class="breadcrumb">
      <router-link class="back" :to="backToCourse">
        ‹ {{ backLabel }}
      </router-link>
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
        <button
          class="btn primary"
          type="button"
          :disabled="saving || dirtyRows.length === 0"
          @click="saveResults"
        >
          {{ saveButtonLabel }}
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

      <div class="grading-panel">
        <div class="grading-info">
          <div class="table-title">Schülerleistungen</div>
          <div class="table-subtitle">
            Punkte direkt in der Tabelle eintragen und gesammelt speichern.
          </div>
        </div>

        <div class="grading-controls">
          <label class="field scheme-field">
            <span class="field-label">Bewertungsschema</span>
            <select
              v-model="courseSchemeId"
              class="input"
              @change="handleSchemeChange"
            >
              <option v-for="scheme in schemes" :key="scheme.id" :value="scheme.id">
                {{ scheme.name }}
              </option>
            </select>
          </label>

          <div class="search-wrap">
            <input
              v-model="search"
              class="search"
              placeholder="Nach Schüler*in suchen"
              @input="clearSaveFeedback"
            />
          </div>
        </div>
      </div>

      <div v-if="saveFeedback" :class="['save-feedback', saveFeedbackTone]">
        {{ saveFeedback }}
      </div>

      <DataTable
        :columns="columns"
        :rows="filteredRows"
        row-key="rowKey"
        empty-text="Keine Schüler vorhanden."
      >
        <template #cell-punkte="{ row }">
          <div class="cell-stack">
            <input
              v-model="row.punkteInput"
              class="input cell-input number-input"
              type="number"
              min="0"
              :max="detail?.maxPunkte ?? undefined"
              placeholder="Punkte"
              @input="clearSaveFeedback"
            />
            <span class="cell-meta">{{ formatPointsMeta(row) }}</span>
          </div>
        </template>

        <template #cell-note="{ row }">
          <div class="cell-stack">
            <template v-if="usesPointBasedGrading">
              <div class="calculated-grade">
                {{ formatCalculatedGrade(row) }}
              </div>
              <span class="cell-meta">wird aus Punkten berechnet</span>
            </template>
            <template v-else>
              <input
                v-model="row.noteInput"
                class="input cell-input number-input"
                type="number"
                min="1"
                max="5"
                step="0.1"
                placeholder="Note"
                @input="clearSaveFeedback"
              />
            </template>
          </div>
        </template>

        <template #cell-datum="{ row }">
          <input
            v-model="row.datumInput"
            class="input cell-input"
            type="date"
            @input="clearSaveFeedback"
          />
        </template>

        <template #cell-kommentar="{ row }">
          <textarea
            v-model="row.kommentarInput"
            class="input cell-input cell-textarea"
            rows="2"
            placeholder="Optionaler Kommentar"
            @input="clearSaveFeedback"
          />
        </template>
      </DataTable>

      <div class="footer-actions">
        <div class="footer-hint">
          {{ dirtyRows.length }} Änderung{{ dirtyRows.length === 1 ? "" : "en" }}
          zum Speichern vorgemerkt.
        </div>
        <button
          class="btn primary"
          type="button"
          :disabled="saving || dirtyRows.length === 0"
          @click="saveResults"
        >
          {{ saveButtonLabel }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import DataTable from "@/components/DataTable.vue";
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
const editableRows = ref([]);
const saving = ref(false);
const saveFeedback = ref("");
const saveFeedbackTone = ref("success");
const search = ref("");

const columns = [
  { key: "vorname", label: "Vorname", width: "14%" },
  { key: "nachname", label: "Nachname", width: "16%" },
  { key: "punkte", label: "Punkte", width: "18%" },
  { key: "note", label: "Note", width: "14%" },
  { key: "datum", label: "Datum", width: "16%" },
  { key: "kommentar", label: "Kommentar", width: "22%" },
];

const kursId = computed(() => detail.value?.kurs?.id ?? null);
const usesPointBasedGrading = computed(() => detail.value?.maxPunkte != null);

const backToCourse = computed(() =>
  kursId.value ? `/lehrer/faecher/${kursId.value}` : "/lehrer/faecher",
);

const backLabel = computed(() =>
  detail.value?.kurs?.name ? detail.value.kurs.name : "Meine Fächer",
);

const filteredRows = computed(() => {
  const list = editableRows.value;
  if (!search.value) return list;

  const query = search.value.toLowerCase();
  return list.filter((row) =>
    `${row.vorname} ${row.nachname}`.toLowerCase().includes(query),
  );
});

const dirtyRows = computed(() =>
  editableRows.value.filter(
    (row) => snapshotRow(row) !== row.originalSnapshot && (row.resultId != null || rowHasScore(row)),
  ),
);

const saveButtonLabel = computed(() => {
  if (saving.value) return "Speichert …";
  if (dirtyRows.value.length === 0) return "Keine Änderungen";
  if (dirtyRows.value.length === 1) return "1 Änderung speichern";
  return `${dirtyRows.value.length} Änderungen speichern`;
});

const schemes = ref([]);
const courseSchemeId = ref("default");
const activeCourseScheme = computed(
  () => grading.getActiveSchemeForCourse?.(kursId.value)?.scheme || grading.loadScheme(),
);

function clearSaveFeedback() {
  saveFeedback.value = "";
}

function normalizeDateInput(value) {
  if (!value) return "";

  const text = String(value);
  const isoMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (isoMatch) {
    return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function todayInputValue() {
  return new Date().toISOString().slice(0, 10);
}

function defaultDateValue(existingDate = "") {
  return (
    normalizeDateInput(existingDate) ||
    normalizeDateInput(detail.value?.datum) ||
    todayInputValue()
  );
}

function parseOptionalInt(value) {
  if (value === "" || value == null) return null;
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) return null;
  return Math.round(numeric);
}

function parseOptionalFloat(value) {
  if (value === "" || value == null) return null;
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) return null;
  return Number(numeric);
}

function normalizeComment(value) {
  const text = String(value ?? "").trim();
  return text || "";
}

function formatDate(value) {
  if (!value) return "—";
  try {
    return new Date(value).toLocaleDateString("de-DE");
  } catch {
    return value;
  }
}

function formatGrade(value) {
  if (value == null) return "—";
  return Number(value).toFixed(1).replace(".", ",");
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

function syncCourseSchemeUi() {
  schemes.value = grading.loadAllSchemes();

  const currentCourseId = kursId.value;
  courseSchemeId.value =
    (currentCourseId != null
      ? grading.getActiveSchemeIdForCourse?.(currentCourseId)
      : null) ||
    grading.getActiveSchemeId?.() ||
    "default";
}

function handleSchemeChange() {
  const currentCourseId = kursId.value;
  if (currentCourseId == null) return;

  grading.setActiveSchemeIdForCourse(currentCourseId, courseSchemeId.value);
  clearSaveFeedback();
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

function buildEditableRow(student, result) {
  const row = {
    rowKey: result?.id ? `result-${result.id}` : `student-${student.id}`,
    resultId: result?.id ?? null,
    schuelerId: Number(result?.schuelerId ?? student.id),
    vorname: result?.vorname ?? student.vorname ?? "",
    nachname: result?.nachname ?? student.nachname ?? "",
    punkteInput: result?.punkte != null ? String(result.punkte) : "",
    noteInput: result?.note != null ? String(result.note) : "",
    datumInput: defaultDateValue(result?.datum),
    kommentarInput: result?.kommentar ?? "",
    originalPunkte: result?.punkte != null ? Number(result.punkte) : null,
    originalSnapshot: "",
  };

  row.originalSnapshot = snapshotRow(row);
  return row;
}

function rebuildEditableRows() {
  const rowsByStudent = new Map(
    (detail.value?.schuelerleistungen || []).map((row) => [Number(row.schuelerId), row]),
  );
  const studentIds = new Set();

  const nextRows = students.value.map((student) => {
    const studentId = Number(student.id);
    studentIds.add(studentId);
    return buildEditableRow(student, rowsByStudent.get(studentId));
  });

  for (const row of detail.value?.schuelerleistungen || []) {
    const studentId = Number(row.schuelerId);
    if (studentIds.has(studentId)) continue;

    nextRows.push(
      buildEditableRow(
        {
          id: studentId,
          vorname: row.vorname,
          nachname: row.nachname,
        },
        row,
      ),
    );
  }

  editableRows.value = nextRows.sort((a, b) => {
    const lastName = a.nachname.localeCompare(b.nachname, "de");
    if (lastName !== 0) return lastName;
    return a.vorname.localeCompare(b.vorname, "de");
  });
}

function snapshotRow(row) {
  return JSON.stringify({
    punkte: parseOptionalInt(row.punkteInput),
    note: parseOptionalFloat(row.noteInput),
    datum: row.datumInput || "",
    kommentar: normalizeComment(row.kommentarInput),
  });
}

function rowHasScore(row) {
  if (usesPointBasedGrading.value) {
    return parseOptionalInt(row.punkteInput) != null;
  }

  return parseOptionalFloat(row.noteInput) != null;
}

function resolveRowGrade(row) {
  if (!usesPointBasedGrading.value) {
    return parseOptionalFloat(row.noteInput);
  }

  const points = parseOptionalInt(row.punkteInput);
  if (points == null) {
    return parseOptionalFloat(row.noteInput);
  }

  if (row.resultId != null && points === row.originalPunkte) {
    return parseOptionalFloat(row.noteInput) ?? calculateNoteFromPoints(points);
  }

  return calculateNoteFromPoints(points);
}

function formatCalculatedGrade(row) {
  return formatGrade(resolveRowGrade(row));
}

function formatPointsMeta(row) {
  const points = parseOptionalInt(row.punkteInput);
  const maxPoints = Number(detail.value?.maxPunkte);

  if (points == null) {
    return detail.value?.maxPunkte != null ? `max. ${detail.value.maxPunkte} Punkte` : "—";
  }

  if (!Number.isFinite(maxPoints) || maxPoints <= 0) {
    return `${points} Punkte`;
  }

  const percentage = Math.round((points / maxPoints) * 100);
  return `${points}/${maxPoints} Punkte (${percentage}%)`;
}

function buildSubmissionPayload(row) {
  const punkte = parseOptionalInt(row.punkteInput);

  return {
    schuelerId: row.schuelerId,
    punkte,
    note: resolveRowGrade(row),
    datum: row.datumInput || defaultDateValue(),
    kommentar: normalizeComment(row.kommentarInput) || null,
  };
}

function validateRows(rows) {
  const invalid = [];
  const maxPoints = Number(detail.value?.maxPunkte);

  for (const row of rows) {
    const payload = buildSubmissionPayload(row);
    const label = `${row.vorname} ${row.nachname}`.trim();

    if (usesPointBasedGrading.value) {
      if (payload.punkte == null) {
        invalid.push(`${label}: Punkte fehlen`);
        continue;
      }

      if (payload.punkte < 0) {
        invalid.push(`${label}: Punkte dürfen nicht negativ sein`);
        continue;
      }

      if (Number.isFinite(maxPoints) && payload.punkte > maxPoints) {
        invalid.push(`${label}: Punkte dürfen maximal ${maxPoints} sein`);
        continue;
      }
    } else {
      if (payload.note == null) {
        invalid.push(`${label}: Note fehlt`);
        continue;
      }

      if (payload.note < 1 || payload.note > 5) {
        invalid.push(`${label}: Note muss zwischen 1 und 5 liegen`);
        continue;
      }
    }

    if (!payload.datum) {
      invalid.push(`${label}: Datum fehlt`);
    }
  }

  return invalid;
}

async function load() {
  loading.value = true;
  error.value = "";

  try {
    detail.value = await getAssessmentDetail(id.value);

    const currentCourseId = detail.value?.kurs?.id;
    students.value = currentCourseId ? await getCourseStudents(currentCourseId) : [];

    rebuildEditableRows();
    syncCourseSchemeUi();
  } catch (e) {
    error.value = e?.message || "Unbekannter Fehler";
  } finally {
    loading.value = false;
  }
}

async function saveResults() {
  const rowsToSave = dirtyRows.value;
  if (rowsToSave.length === 0) return;

  const invalidRows = validateRows(rowsToSave);
  if (invalidRows.length > 0) {
    saveFeedbackTone.value = "error";
    saveFeedback.value = invalidRows.slice(0, 3).join(" | ");
    return;
  }

  saving.value = true;
  saveFeedback.value = "";

  try {
    await Promise.all(
      rowsToSave.map((row) => createStudentResult(id.value, buildSubmissionPayload(row))),
    );

    await load();
    saveFeedbackTone.value = "success";
    saveFeedback.value =
      rowsToSave.length === 1
        ? "1 Schülerleistung gespeichert."
        : `${rowsToSave.length} Schülerleistungen gespeichert.`;
  } catch (e) {
    saveFeedbackTone.value = "error";
    saveFeedback.value = e?.message || "Speichern fehlgeschlagen.";
  } finally {
    saving.value = false;
  }
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

onMounted(load);
watch(id, load);
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

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
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

.grading-panel {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.9rem;
}

.grading-info {
  display: grid;
  gap: 0.2rem;
}

.table-title {
  font-size: 1.05rem;
  font-weight: 700;
}

.table-subtitle {
  color: var(--muted);
  font-size: 0.92rem;
}

.grading-controls {
  display: flex;
  align-items: end;
  gap: 0.8rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.scheme-field {
  min-width: 220px;
}

.search-wrap {
  min-width: 280px;
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
  width: 100%;
  box-sizing: border-box;
}

html:not(.dark) .input {
  background: rgba(0, 0, 0, 0.04);
}

.search {
  width: 100%;
  border-radius: 999px;
  padding: 0.65rem 1rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text);
  outline: none;
}

.save-feedback {
  margin-bottom: 0.85rem;
  padding: 0.8rem 1rem;
  border-radius: 12px;
  font-size: 0.92rem;
}

.save-feedback.success {
  background: rgba(34, 197, 94, 0.14);
  border: 1px solid rgba(34, 197, 94, 0.28);
  color: #bbf7d0;
}

.save-feedback.error {
  background: rgba(248, 113, 113, 0.12);
  border: 1px solid rgba(248, 113, 113, 0.28);
  color: #fecaca;
}

.cell-stack {
  display: grid;
  gap: 0.3rem;
}

.cell-input {
  min-width: 0;
}

.number-input {
  min-width: 96px;
}

.cell-textarea {
  resize: vertical;
  min-height: 72px;
}

.cell-meta {
  color: var(--muted);
  font-size: 0.78rem;
  line-height: 1.35;
}

.calculated-grade {
  min-height: 42px;
  display: flex;
  align-items: center;
  font-weight: 700;
}

.footer-actions {
  margin-top: 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.footer-hint {
  color: var(--muted);
  font-size: 0.92rem;
}

.empty {
  padding: 2rem 0;
  color: var(--muted);
}

@media (max-width: 960px) {
  .head,
  .grading-panel,
  .footer-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .head-actions,
  .grading-controls {
    justify-content: stretch;
  }

  .kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .kpis {
    grid-template-columns: 1fr;
  }
}
</style>
