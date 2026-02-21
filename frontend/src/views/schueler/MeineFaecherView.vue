<template>
  <div class="faecher-view">
    <h1 class="title">Meine Fächer</h1>

    <!-- Neuer Bereich für den Namen -->
    <div v-if="studentName" class="student-name">
      Hallo, {{ studentName }}!
      <span v-if="isOver18" class="age-badge">✓ 18+</span>
      <span v-else-if="geburtsdatum" class="age-badge">-18</span>

      <!-- Eltern-Email anzeigen, falls vorhanden -->
      <div v-if="elternEmail" class="eltern-email">
        Eltern-Email: {{ elternEmail }}
      </div>
    </div>

    <!-- Modal anzeigen, wenn kein Geburtsdatum vorhanden ist -->
    <AddGeburtsdatumModal v-if="showGeburtsdatumModal" @close="showGeburtsdatumModal = false"
      @updated="handleStudentUpdated" />

    <AddElternEmailModal v-if="showElternEmailModal" @close="showElternEmailModal = false"
      @updated="handleStudentUpdated" />

    <div class="toolbar">
      <button class="btn" :class="{ active: tab === 'alle' }" @click="tab = 'alle'">Alle</button>
      <button class="btn" :class="{ active: tab === 'visible' }" @click="tab = 'visible'">Eingeblendete</button>
      <button class="btn" :class="{ active: tab === 'hidden' }" @click="tab = 'hidden'">Ausgeblendete</button>
      <button class="btn" @click="toggleSorting">Sortiert A–Z</button>
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

        <div class="actions">
          <span class="bell" @click.stop="toggleNotif(fach.kurs_id)">
            <span v-if="fach.notif_enabled == 1">🔔</span>
            <span v-else>🔕</span>
          </span>

          <span class="menu" @click.stop="toggleMenu(fach.kurs_id)">⋮</span>

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
const sortByName = ref(false);

const studentName = ref(""); // Neuer State für den Namen
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
    studentName.value = `${student.vorname} ${student.nachname}`;
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
    subjects.value = res.data;
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

function toggleSorting() {
  sortByName.value = !sortByName.value;
}

const visibleSubjects = computed(() => {
  let list = subjects.value;

  if (tab.value === "visible") list = list.filter(s => s.sichtbar == 1);
  if (tab.value === "hidden") list = list.filter(s => s.sichtbar == 0);

  if (searchTerm.value)
    list = list.filter(s =>
      s.fach_name.toLowerCase().includes(searchTerm.value.toLowerCase())
    );

  if (sortByName.value)
    list = [...list].sort((a, b) =>
      a.fach_name.localeCompare(b.fach_name)
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
  border: 1px solid #ccc;
  padding: 10px 18px;
  border-radius: 20px;
  cursor: pointer;
}

.btn.active {
  background: #111;
  color: #fff;
}

.search-input {
  width: 100%;
  padding: .8rem;
  margin-bottom: 1rem;
  border: 1px solid #bbb;
  border-radius: 8px;
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
}

.subject-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
}

.fach-image {
  width: 50px;
  height: 50px;
  background: #efefef;
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

.bell,
.menu {
  cursor: pointer;
  font-size: 22px;
}

.off {
  opacity: .35;
}

.context-menu {
  position: absolute;
  right: 0;
  top: 28px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
  padding: 8px 0;
  min-width: 160px;
  z-index: 999;
}

.context-item {
  padding: 10px 16px;
  cursor: pointer;
  transition: background 0.15s;
}

.context-item:hover {
  background: #efefef;
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
