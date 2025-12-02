<template>
  <div class="faecher-view">
    <h1 class="title">Meine Fächer</h1>

    <div class="toolbar">
      <div class="left-controls">
        <button class="btn" @click="loadSubjects">Alle</button>
        <button class="btn">Eingeblendete</button>
        <button class="btn">Ausgeblendete</button>
        <button class="btn" @click="sortByName">Sortiert A–Z</button>
      </div>
    </div>

    <input
      v-model="searchTerm"
      type="text"
      class="search-input"
      placeholder="Nach Fächern suchen..."
    />

    <div class="subject-list">
      <div v-if="loading" class="loading">⏳ Lade Fächer...</div>

      <div v-else-if="filteredSubjects.length === 0">
        <p>Keine Fächer gefunden.</p>
      </div>

      <ul v-else>
        <li
          v-for="fach in filteredSubjects"
          :key="fach.kurs_id"
          class="subject-item"
          @click="goToDetail(fach.kurs_id)"
        >
          <div class="subject-info">
            <img
              class="fach-image"
              :src="getImage(fach.fach_name)"
              alt="Fachbild"
            />

            <div class="fach-text">
              <strong>{{ fach.fach_name }}</strong>
              <span class="class-name">
                {{ fach.klasse_name || 'Keine Klasse' }}
              </span>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const searchTerm = ref('')
const subjects = ref([])
const loading = ref(false)

async function loadSubjects() {
  loading.value = true
  try {
    const response = await axios.get(`/schueler/faecher`)
    subjects.value = response.data
  } catch (error) {
    console.error('❌ Fehler beim Laden der Fächer:', error)
  } finally {
    loading.value = false
  }
}

const filteredSubjects = computed(() => {
  if (!searchTerm.value.trim()) return subjects.value
  return subjects.value.filter((s) =>
    s.fach_name.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
})

function sortByName() {
  subjects.value.sort((a, b) => a.fach_name.localeCompare(b.fach_name))
}

function getImage(name) {
  const map = {
    Mathematik: '/images/m.png',
    Englisch: '/img/englisch.png',
    Deutsch: '/images/d.png',
    Softwareentwicklung: '/img/software.png',
    Medientechnik: '/img/medien.png',
  }
  return map[name] || '/img/default.png'
}

function goToDetail(id) {
  router.push(`/schueler/faecher/${id}`)
}

onMounted(() => {
  loadSubjects()
})
</script>

<style scoped>
.faecher-view {
  padding: 1rem 2rem;
}
.title {
  font-size: 2rem;
  margin-bottom: 2rem;
  font-weight: 650;
}
.toolbar {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2.2rem;
}
.btn {
  background-color: var(--first-background-color);
  border: 1.5px solid #EAEAEA;
  border-radius: 20px;
  padding: 16px 30px;
  min-width: 180px;
  cursor: pointer;
}
.btn:hover {
  background-color: #f1f1f1;
}
.search-input {
  padding: 0.8rem 1.6rem;
  border: 1px solid #4D495C;
  border-radius: 10px;
  width: 94%;
  margin-bottom: 1.5rem;
}
.subject-list {
  background-color: #fff;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
.subject-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.2rem 0;
  border-bottom: 1px solid #eee;
  cursor: pointer;
}
.subject-item:hover {
  background: #f8f8f8;
}
.subject-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.fach-image {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  object-fit: cover;
}
.class-name {
  color: #777;
  font-size: 0.9rem;
}
.loading {
  color: #555;
  font-style: italic;
}
</style>
