<template>
  <section class="profile-view">
    <header class="hero">
      <div class="avatar">{{ initials }}</div>
      <div class="hero-copy">
        <p class="eyebrow">Lehrerprofil</p>
        <h1 class="title">{{ fullName || "Mein Profil" }}</h1>
        <p class="subtitle">
          Zentrale Profildaten, Fachzuordnungen und schnelle Zugriffe auf wichtige Bereiche.
        </p>
      </div>
    </header>

    <div v-if="loading" class="state">Profil wird geladen…</div>
    <div v-else-if="error" class="state">Fehler: {{ error }}</div>

    <div v-else class="grid">
      <section class="card">
        <h2 class="card-title">Allgemein</h2>
        <div class="facts">
          <div class="fact">
            <span class="label">Name</span>
            <strong>{{ fullName || "—" }}</strong>
          </div>
          <div class="fact">
            <span class="label">E-Mail</span>
            <strong>{{ teacher.email || "—" }}</strong>
          </div>
          <div class="fact">
            <span class="label">Rolle</span>
            <strong>Lehrkraft</strong>
          </div>
          <div class="fact">
            <span class="label">Anzahl Fächer</span>
            <strong>{{ subjects.length }}</strong>
          </div>
        </div>
      </section>

      <section class="card accent">
        <h2 class="card-title">Schnellzugriff</h2>
        <div class="quick-links">
          <button type="button" class="quick-link" @click="router.push('/lehrer/faecher')">
            Meine Fächer
          </button>
          <button type="button" class="quick-link" @click="router.push('/lehrer/einstellungen')">
            Einstellungen
          </button>
          <button type="button" class="quick-link" @click="router.push('/lehrer/hilfe')">
            Hilfe / Datenschutz
          </button>
          <button type="button" class="quick-link danger" @click="router.push('/logout')">
            Logout
          </button>
        </div>
      </section>

      <section class="card full-width">
        <h2 class="card-title">Fächer</h2>
        <div v-if="subjects.length" class="chips">
          <span v-for="subject in subjects" :key="subject.id" class="chip">
            {{ subject.name }}
          </span>
        </div>
        <p v-else class="empty">Aktuell sind keine Fächer zugeordnet.</p>
      </section>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { apiClient } from "@/services/apiClient";

const router = useRouter();

const loading = ref(false);
const error = ref("");
const teacher = ref({});

const fullName = computed(() =>
  [teacher.value?.vorname, teacher.value?.nachname].filter(Boolean).join(" "),
);

const initials = computed(() => {
  const first = teacher.value?.vorname?.[0] || "";
  const last = teacher.value?.nachname?.[0] || "";
  return `${first}${last}`.toUpperCase() || "LP";
});

const subjects = computed(() => teacher.value?.faecher || []);

async function loadProfile() {
  loading.value = true;
  error.value = "";
  try {
    const res = await apiClient.get("/lehrer/me");
    teacher.value = res.data || {};
  } catch (e) {
    error.value = e?.message || "Profil konnte nicht geladen werden.";
  } finally {
    loading.value = false;
  }
}

onMounted(loadProfile);
</script>

<style scoped>
.profile-view {
  max-width: 1100px;
  margin: 0 auto;
}

.hero {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.25rem 0 1.75rem;
}

.avatar {
  width: 92px;
  height: 92px;
  border-radius: 28px;
  display: grid;
  place-items: center;
  background:
    linear-gradient(135deg, rgba(255, 145, 77, 0.18), rgba(92, 103, 242, 0.18)),
    var(--second-background-color);
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
}

.eyebrow {
  margin: 0 0 0.35rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.8rem;
}

.title {
  margin: 0;
  font-size: 2.3rem;
  line-height: 1.05;
}

.subtitle {
  margin: 0.45rem 0 0;
  color: var(--muted);
  max-width: 52ch;
}

.state {
  color: var(--muted);
  padding: 2rem 0;
}

.grid {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.9fr);
  gap: 1rem;
}

.card {
  border-radius: 24px;
  padding: 1.25rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background:
    linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02)),
    var(--second-background-color);
}

.card.accent {
  background:
    radial-gradient(circle at top right, rgba(255, 145, 77, 0.16), transparent 40%),
    linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02)),
    var(--second-background-color);
}

.full-width {
  grid-column: 1 / -1;
}

.card-title {
  margin: 0 0 1rem;
  font-size: 1.15rem;
}

.facts {
  display: grid;
  gap: 0.9rem;
}

.fact {
  display: grid;
  gap: 0.2rem;
  padding-bottom: 0.9rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.fact:last-child {
  padding-bottom: 0;
  border-bottom: 0;
}

.label {
  color: var(--muted);
  font-size: 0.86rem;
}

.quick-links {
  display: grid;
  gap: 0.75rem;
}

.quick-link {
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.03);
  color: var(--text);
  text-align: left;
  padding: 0.95rem 1rem;
  font: inherit;
  cursor: pointer;
}

.quick-link:hover {
  background: rgba(255, 255, 255, 0.06);
}

.quick-link.danger {
  color: #d45454;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.chip {
  padding: 0.6rem 0.9rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.empty {
  margin: 0;
  color: var(--muted);
}

@media (max-width: 900px) {
  .grid {
    grid-template-columns: 1fr;
  }

  .full-width {
    grid-column: auto;
  }
}

@media (max-width: 640px) {
  .hero {
    align-items: flex-start;
    flex-direction: column;
  }

  .title {
    font-size: 1.9rem;
  }
}
</style>
