<template>
  <div class="faecher-view">
    <h1 class="title">Meine Fächer</h1>

    <!-- Modal anzeigen, wenn kein Geburtsdatum vorhanden ist -->
    <AddGeburtsdatumModal v-if="showGeburtsdatumModal" @close="showGeburtsdatumModal = false"
      @updated="handleStudentUpdated" />

    <AddElternEmailModal v-if="showElternEmailModal" @close="showElternEmailModal = false"
      @updated="handleStudentUpdated" />

    <div class="toolbar">
      <button class="btn" :class="{ active: tab === 'alle' }" @click="tab = 'alle'">Alle</button>
      <button class="btn" :class="{ active: tab === 'visible' }" @click="tab = 'visible'">Eingeblendete</button>
      <button class="btn" :class="{ active: tab === 'hidden' }" @click="tab = 'hidden'">Ausgeblendete</button>
    </div>

    <input v-model="searchTerm" type="text" class="search-input" placeholder="Nach Fächern suchen..." />

    <ul class="subject-list">
      <li v-for="fach in visibleSubjects" :key="fach.kurs_id" class="subject-item">
        <div class="subject-info" @click="goToDetail(fach.kurs_id)">
          <div class="fach-image">{{ fach.fach_name.charAt(0) }}</div>

          <div class="fach-text">
            <strong>{{ fach.fach_name }}</strong>
            <span class="class-name">{{ fach.klasse_name }}</span>
          </div>
        </div>

        <div v-if="fach.gesamtnote != null" class="grade-badge">
          Gesamtnote {{ formatGrade(fach.gesamtnote) }}
        </div>

        <div class="actions">
          <button type="button" class="icon-btn bell" @click.stop="toggleNotif(fach.kurs_id)">
            <span v-if="fach.notif_enabled == 1">🔔</span>
            <span v-else>🔕</span>
          </button>

          <button type="button" class="icon-btn menu" @click.stop="toggleMenu(fach.kurs_id)">⋮</button>

          <div v-if="openMenuId === fach.kurs_id" class="context-menu">
            <div class="context-item" v-if="fach.sichtbar == 1" @click="toggleVisibility(fach.kurs_id)">👁 Fach
              ausblenden</div>

            <div class="context-item" v-else @click="toggleVisibility(fach.kurs_id)">➕ Fach einblenden</div>
          </div>

        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import AddGeburtsdatumModal from "@/components/AddGeburtsdatumModal.vue";
import AddElternEmailModal from "@/components/AddElternEmailModal.vue";

const router = useRouter();
const searchTerm = ref("");
const subjects = ref([]);
const tab = ref("alle");

const geburtsdatum = ref(null); // Neuer State für das Geburtsdatum
const elternEmail = ref(null); // Neuer State für die Eltern-Email
const showGeburtsdatumModal = ref(false); // State für Modal-Sichtbarkeit
const showElternEmailModal = ref(false); // State für Modal-Sichtbarkeit



const openMenuId = ref(null);

const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''

// Wenn Dev → direkt über Proxy `/api`
// Wenn Prod → volle URL, aber ohne zusätzliches /api doppeln
const apiPrefix = isDev ? '' : `${apiBase}/api`

function stableSortSubjects(list = []) {
  return [...list].sort((a, b) => {
    const fach = String(a.fach_name || '').localeCompare(String(b.fach_name || ''), 'de')
    if (fach !== 0) return fach

    const klasse = String(a.klasse_name || '').localeCompare(String(b.klasse_name || ''), 'de')
    if (klasse !== 0) return klasse

    return Number(a.kurs_id || 0) - Number(b.kurs_id || 0)
  })
}

// Funktion zum Berechnen des Alters
function calculateAge(birthDate) {
  if (!birthDate) return null;

  const today = new Date();
  const birth = new Date(birthDate);
  let age = today.getFullYear() - birth.getFullYear();
  const monthDiff = today.getMonth() - birth.getMonth();

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--;
  }

  return age;
}

// Computed Property um zu prüfen, ob der Schüler 18+ ist
const isOver18 = computed(() => {
  if (!geburtsdatum.value) return false;
  const age = calculateAge(geburtsdatum.value);
  return age >= 18;
});

// Neue Funktion zum Laden der Schüler-Daten
async function loadCurrentStudent() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${apiPrefix}/schueler/me`, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
    const student = res.data;
    geburtsdatum.value = student.geburtsdatum;

    // Eltern-Email speichern (robust prüfen)
    const emailValue = student.einstellungen?.elternemail;
    elternEmail.value = (emailValue && emailValue.trim() !== '') ? emailValue : null;

    // Modal anzeigen, wenn kein Geburtsdatum vorhanden ist
    if (!student.geburtsdatum) {
      showGeburtsdatumModal.value = true;
      showElternEmailModal.value = false;
      return; // Früh beenden, wenn kein Geburtsdatum
    }

    // Geburtsdatum vorhanden → Geburtsdatum-Modal schließen
    showGeburtsdatumModal.value = false;

    // Prüfen ob unter 18 und keine Eltern-Email vorhanden
    const age = calculateAge(student.geburtsdatum);
    const hasEmail = elternEmail.value && elternEmail.value.trim() !== '';

    // Modal nur anzeigen, wenn unter 18 UND keine Email vorhanden
    if (age < 18 && !hasEmail) {
      showElternEmailModal.value = true;
    } else {
      showElternEmailModal.value = false; // Modal schließen
    }
  } catch (error) {
    console.error("Fehler beim Laden der Schüler-Daten:", error);
  }
}

// Handler für wenn Student aktualisiert wurde
async function handleStudentUpdated() {
  // Schüler-Daten neu laden, um die aktualisierten Daten zu bekommen
  await loadCurrentStudent();
  // Das Modal wird automatisch geschlossen, wenn die Email vorhanden ist (siehe loadCurrentStudent)
}

// Neue Funktion zum Laden der Subjects
async function loadSubjects() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${apiPrefix}/schueler/faecher`, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
    subjects.value = stableSortSubjects(res.data || []);
  } catch (error) {
    console.error("Fehler beim Laden der Fächer:", error);
  }
}

// Neue Funktion für das Toggle der Sichtbarkeit
async function toggleVisibility(id) {
  try {
    const token = localStorage.getItem('token')
    await axios.put(`${apiPrefix}/schueler/faecher/${id}/toggle-visibility`, {}, { // ← leeres Objekt {} als Body
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
    await loadSubjects();
    openMenuId.value = null;
  } catch (error) {
    console.error("Fehler beim Toggle der Sichtbarkeit:", error);
  }
}

// Neue Funktion für das Toggle der Benachrichtigungen
async function toggleNotif(id) {
  try {
    const token = localStorage.getItem('token')
    await axios.put(`${apiPrefix}/schueler/faecher/${id}/toggle-notif`, {}, { // ← leeres Objekt {} als Body
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
    await loadSubjects();
  } catch (error) {
    console.error("Fehler beim Toggle der Benachrichtigungen:", error);
  }
}

function formatGrade(value) {
  if (value == null || value === "") return "—";

  const parsed = Number(value);
  if (!Number.isFinite(parsed)) {
    return String(value);
  }

  return parsed.toFixed(2).replace(".", ",");
}

const visibleSubjects = computed(() => {
  let list = subjects.value;

  if (tab.value === "visible") list = list.filter(s => s.sichtbar == 1);
  if (tab.value === "hidden") list = list.filter(s => s.sichtbar == 0);

  if (searchTerm.value)
    list = list.filter(s =>
      s.fach_name.toLowerCase().includes(searchTerm.value.toLowerCase())
    );

  return list;
});

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id;
}

document.addEventListener("click", (e) => {
  if (!e.target.closest(".menu") && !e.target.closest(".context-menu")) {
    openMenuId.value = null;
  }
});

function goToDetail(id) {
  router.push(`/schueler/faecher/${id}`);
}

onMounted(() => {
  loadCurrentStudent(); // Schüler-Daten laden
  loadSubjects();

});
</script>

<style scoped>

.title {
  font-size: 2rem;
  margin-bottom: 1.5rem;
  font-weight: 650;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: .7rem;
  margin-bottom: 1.2rem;
}

.btn {
  border: 1px solid var(--aczent-color);
  padding: 10px 18px;
  border-radius: 20px;
  cursor: pointer;
  background-color: var(--first-background-color);
  color: var(--text);
}

.btn.active {
  background:
    linear-gradient(var(--first-background-color) 0 0) padding-box,
    linear-gradient(to right, var(--primary), var(--secondary)) border-box;
  border: 1.5px solid transparent;
  color: var(--text);

}

.search-input {
  width: 100%;
  padding: .8rem;
  margin-bottom: 1rem;
  border: 1px solid var(--aczent-color);
  border-radius: 8px;
  background-color: var(--search-background);
  color: var(--aczent-color);
}

.subject-list {
  list-style: none;
  padding: 0;
}

.subject-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: .9rem 0;
  border-bottom: 1px solid #eee;
  gap: 1rem;
}

.subject-info {
  display: flex;
  flex: 1;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  min-width: 0;
}

.grade-badge {
  margin-left: auto;
  padding: 0.4rem 0.85rem;
  border-radius: 999px;
  background: var(--second-background-color);
  border: 1px solid var(--aczent-color);
  font-size: 0.9rem;
  font-weight: 600;
  flex-shrink: 0;
  white-space: nowrap;
}

.fach-text {
  min-width: 0;
}

.fach-image {
  width: 50px;
  height: 50px;
  background: var(--second-background-color);
  color: --text;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  border-radius: 10px;
}

.actions {
  position: relative;
  display: flex;
  align-items: center;
  gap: 15px;
}

.icon-btn {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: var(--text);
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  padding: 0;
}

.icon-btn:hover {
  background: rgba(255, 255, 255, 0.06);
}

.off {
  opacity: .35;
}

.context-menu {
  position: absolute;
  right: 0;
  bottom: calc(100% + 8px);
  background: var(--second-background-color);
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
  padding: 8px 0;
  min-width: 160px;
  z-index: 999;
  color:var(--text);
}

.context-item {
  padding: 10px 16px;
  cursor: pointer;
  transition: background 0.15s;
}

.class-name {
  margin-left: 10px;
}


@media (max-width: 480px) {
  .toolbar .btn {
    flex: 1 1 100%;
    min-width: 0;
  }

  .title {
    font-size: 1.5rem;
  }

  .subject-item {
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .subject-info {
    flex: 1;
    min-width: 0;
  }

  .fach-image {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
}
</style>
