<template>
  <section class="page">
    <header class="page__header">
      <div>
        <p class="eyebrow">Meine Fächer</p>
        <h1>{{ greeting }}</h1>
      </div>
      <div class="actions">
        <button class="btn ghost">Entwürfe</button>
        <button class="btn primary" @click="showCreate.open = true">Neues Fach erstellen</button>
        <div class="topbar">
          <button class="icon-btn">🌙</button>
          <button class="profile-btn">👤</button>
        </div>
      </div>
    </header>

    <div class="filters card">
      <div class="filter">
        <label>Jahr</label>
        <select v-model="filters.year">
          <option value="all">Alle</option>
          <option>2025/26</option>
          <option>2024/25</option>
        </select>
      </div>
      <div class="filter">
        <label>Organisator</label>
        <select v-model="filters.orga">
          <option value="all">Alle</option>
          <option>HTL</option>
          <option>PTL</option>
        </select>
      </div>
      <div class="filter">
        <label>Angebotsfeld</label>
        <select v-model="filters.field">
          <option value="all">Alle</option>
          <option>Informatik</option>
          <option>Medientechnik</option>
        </select>
      </div>
      <div class="filter">
        <label>Sortiert nach</label>
        <select v-model="filters.sort">
          <option value="name">Name</option>
          <option value="klasse">Klasse</option>
        </select>
      </div>
      <button class="btn secondary">Filter speichern</button>
    </div>

    <div class="cards">
      <article
        v-for="subject in sortedSubjects"
        :key="subject.id"
        class="card subject-card"
        :style="{ '--accent': subject.color || 'var(--primary)' }"
        @click="openDetail(subject.id)"
      >
        <div class="card__badge">{{ subject.klasse }}</div>
        <div class="card__image" :style="{ background: `linear-gradient(135deg, ${subject.color}33, ${subject.color || '#7A79E9'})` }">
          <div class="placeholder-icon">📘</div>
        </div>
        <h3>{{ subject.title }}</h3>
        <p>{{ subject.short }}</p>
        <button class="icon-btn" title="Optionen">⋯</button>
      </article>
    </div>

    <SubjectCreateModal :open="showCreate.open" @close="showCreate.open = false" @created="onCreated" />
  </section>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { getSubjects } from '@/services/teacherData'
import SubjectCreateModal from '@/components/SubjectCreateModal.vue'

const router = useRouter()
const filters = reactive({ year: 'all', orga: 'all', field: 'all', sort: 'name' })

const greeting = computed(() => 'Willkommen im Lehrer-Dashboard')
const subjects = computed(() => getSubjects())
const showCreate = reactive({ open: false })

const sortedSubjects = computed(() => {
  const list = [...subjects.value]
  if (filters.sort === 'klasse') list.sort((a, b) => a.klasse.localeCompare(b.klasse))
  else list.sort((a, b) => a.title.localeCompare(b.title))
  return list
})

function openDetail(id) {
  router.push(`/lehrer/fach/${id}`)
}

function onCreated(payload) {
  // Lokale Demo: füge das Fach nur in localStorage hinzu
  const list = getSubjects()
  const newSubject = {
    id: Date.now(),
    title: payload.title,
    short: payload.title.split(' ').slice(-2).join(' ') || payload.title,
    klasse: payload.klasse || 'ohne Klasse',
    img: '/cards/card-new.svg',
    color: '#7A79E9'
  }
  const saved = JSON.parse(localStorage.getItem('tg-teacher-demo') || '{}')
  const subjects = saved.subjects || list
  subjects.unshift(newSubject)
  localStorage.setItem('tg-teacher-demo', JSON.stringify({ ...saved, subjects }))
}
</script>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  width: 100%;
  max-width: 1400px;
  margin: 0;
  padding: 0 0 2rem;
}

.page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.eyebrow {
  font-size: 0.85rem;
  color: var(--muted);
  margin: 0;
}

h1 {
  margin: 0.1rem 0 0;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.topbar {
  display: flex;
  gap: 0.4rem;
  align-items: center;
}

.icon-btn,
.profile-btn {
  border: 1px solid var(--shadow);
  background: var(--second-background-color);
  color: var(--text);
  border-radius: 50%;
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  cursor: pointer;
}

.filters {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
  gap: 1rem;
  align-items: end;
}

.filter label {
  display: block;
  font-size: 0.85rem;
  margin-bottom: 0.25rem;
  color: var(--muted);
}

.filter select {
  width: 100%;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  background: var(--second-background-color);
  border: 1px solid var(--shadow);
  color: var(--text);
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.2rem;
}

.card {
  background: var(--first-background-color);
  border: 1px solid var(--shadow);
  border-radius: 14px;
  padding: 1rem;
  box-shadow: 0 14px 40px rgba(0, 0, 0, 0.08);
}

.subject-card {
  position: relative;
  cursor: pointer;
  overflow: hidden;
}

.card__badge {
  position: absolute;
  top: 12px;
  right: 12px;
  background: var(--second-background-color);
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  font-size: 0.8rem;
}

.card__image {
  width: 100%;
  height: 140px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  margin-bottom: 0.9rem;
}

.placeholder-icon {
  font-size: 2rem;
}

.subject-card h3 {
  margin: 0;
  font-size: 1.05rem;
}

.subject-card p {
  margin: 0.15rem 0 0.4rem;
  color: var(--muted);
}

.icon-btn {
  position: absolute;
  bottom: 12px;
  right: 12px;
  border: none;
  background: transparent;
  color: var(--text);
  font-size: 1.3rem;
  cursor: pointer;
}

.btn {
  border: none;
  border-radius: 10px;
  padding: 0.7rem 1rem;
  cursor: pointer;
  font-weight: 600;
}

.btn.primary {
  background: linear-gradient(120deg, var(--primary), var(--secondary));
  color: white;
}

.btn.secondary {
  background: var(--second-background-color);
  color: var(--text);
}

.btn.ghost {
  background: transparent;
  border: 1px solid var(--shadow);
  color: var(--text);
}
</style>
