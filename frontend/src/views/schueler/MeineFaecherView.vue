<template>
  <div class="faecher-view">
    <h1 class="title">Meine Fächer</h1>

    <div class="toolbar">
      <button class="btn" :class="{active: tab === 'alle'}" @click="tab = 'alle'">Alle</button>
      <button class="btn" :class="{active: tab === 'visible'}" @click="tab = 'visible'">Eingeblendete</button>
      <button class="btn" :class="{active: tab === 'hidden'}" @click="tab = 'hidden'">Ausgeblendete</button>
      <button class="btn" @click="toggleSorting">Sortiert A–Z</button>
    </div>

    <input
      v-model="searchTerm"
      type="text"
      class="search-input"
      placeholder="Nach Fächern suchen..."
    />

    <ul class="subject-list">
      <li
        v-for="fach in visibleSubjects"
        :key="fach.kurs_id"
        class="subject-item"
      >
        <div class="subject-info" @click="goToDetail(fach.kurs_id)">
          <div class="fach-image">{{ fach.fach_name.charAt(0) }}</div>

          <div class="fach-text">
            <strong>{{ fach.fach_name }}</strong>
            <span class="class-name">{{ fach.klasse_name }}</span>
          </div>
        </div>

        <div class="actions">
          <span
            class="bell"
            @click.stop="toggleNotif(fach.kurs_id)">
            <span v-if="fach.notif_enabled == 1">🔔</span>
            <span v-else>🔕</span>
          </span>

          <span
            class="menu"
            @click.stop="toggleMenu(fach.kurs_id)"
          >⋮</span>

          <div
            v-if="openMenuId === fach.kurs_id"
            class="context-menu"
          >
            <div
              class="context-item"
              v-if="fach.sichtbar == 1"
              @click="toggleVisibility(fach.kurs_id)"
            >👁 Fach ausblenden</div>

            <div
              class="context-item"
              v-else
              @click="toggleVisibility(fach.kurs_id)"
            >➕ Fach einblenden</div>
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

const router = useRouter();
const searchTerm = ref("");
const subjects = ref([]);
const tab = ref("alle");
const sortByName = ref(false);

const openMenuId = ref(null);

async function loadSubjects() {
  const res = await axios.get("/schueler/faecher");
  subjects.value = res.data;
}

async function toggleVisibility(id) {
  await axios.put(`/schueler/faecher/${id}/toggle-visibility`);
  await loadSubjects();
  openMenuId.value = null;
}

async function toggleNotif(id) {
  await axios.put(`/schueler/faecher/${id}/toggle-notif`);
  await loadSubjects();
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

onMounted(loadSubjects);
</script>

<style scoped>
.title {
  font-size: 2rem;
  margin-bottom: 1.5rem;
  font-weight: 650;
}

.toolbar {
  display: flex;
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

.bell, .menu {
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
  box-shadow: 0 2px 12px rgba(0,0,0,0.15);
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
</style>
