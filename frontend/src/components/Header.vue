<template>
  <div class="header">
    <button
      v-if="showHamburger"
      class="hamburger icon-btn"
      @click="$emit('toggle-sidebar')"
      aria-label="Menü öffnen"
    >
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M3 12H21" stroke="var(--icon-color)" stroke-width="2" stroke-linecap="round" />
        <path d="M3 6H21" stroke="var(--icon-color)" stroke-width="2" stroke-linecap="round" />
        <path d="M3 18H21" stroke="var(--icon-color)" stroke-width="2" stroke-linecap="round" />
      </svg>
    </button>

    <button class="icon-btn" @click="toggleTheme" :title="isDark ? 'Lightmode' : 'Darkmode'">
      <svg v-if="isDark" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M2.03009 12.42C2.39009 17.57 6.76009 21.76 11.9901 21.99C15.6801 22.15 18.9801 20.43 20.9601 17.72C21.7801 16.61 21.3401 15.87 19.9701 16.12C19.3001 16.24 18.6101 16.29 17.8901 16.26C13.0001 16.06 9.00009 11.97 8.98009 7.13996C8.97009 5.83996 9.24009 4.60996 9.73009 3.48996C10.2701 2.24996 9.62009 1.65996 8.37009 2.18996C4.41009 3.85996 1.70009 7.84996 2.03009 12.42Z"
          stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <svg v-else width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M12 18.5C15.5899 18.5 18.5 15.5899 18.5 12C18.5 8.41015 15.5899 5.5 12 5.5C8.41015 5.5 5.5 8.41015 5.5 12C5.5 15.5899 8.41015 18.5 12 18.5Z"
          stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M19.14 19.14L19.01 19.01M19.01 4.99L19.14 4.86L19.01 4.99ZM4.86 19.14L4.99 19.01L4.86 19.14ZM12 2.08V2V2.08ZM12 22V21.92V22ZM2.08 12H2H2.08ZM22 12H21.92H22ZM4.99 4.99L4.86 4.86L4.99 4.99Z"
          stroke="var(--icon-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </button>

    <div class="lang">
      <span class="flag" aria-hidden="true">🇩🇪</span>
      <select class="lang-select" v-model="language" @change="persistLanguage">
        <option value="Deutsch">Deutsch</option>
      </select>
    </div>

    <div ref="profileMenuRef" class="profile-wrap">
      <button
        class="profile-btn"
        type="button"
        aria-label="Profilmenü öffnen"
        aria-haspopup="menu"
        :aria-expanded="profileOpen"
        @click="toggleProfileMenu"
      >
        <svg class="profile" width="44" height="44" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="30" cy="30" r="30" fill="var(--second-background-color)" />
          <path
            d="M30.0002 28C32.9917 28 35.4168 25.5748 35.4168 22.5833C35.4168 19.5917 32.9917 17.1666 30.0002 17.1666C27.0086 17.1666 24.5835 19.5917 24.5835 22.5833C24.5835 25.5748 27.0086 28 30.0002 28Z"
            stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          <path
            d="M39.306 38.8333C39.306 34.6408 35.1352 31.25 30.0002 31.25C24.8652 31.25 20.6943 34.6408 20.6943 38.8333"
            stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </button>

      <div v-if="profileOpen" class="profile-menu" role="menu">
        <div class="profile-card">
          <div class="profile-name">{{ profile.name || 'Mein Profil' }}</div>
          <div v-if="profile.email" class="profile-meta">{{ profile.email }}</div>
          <div v-if="profile.klasse" class="profile-meta">Klasse: {{ profile.klasse }}</div>
          <div class="profile-role">{{ roleLabel }}</div>
        </div>

        <div class="menu-section">
          <button type="button" class="menu-item" @click="goToProfile">Profil</button>
          <button
            v-if="isStudent"
            type="button"
            class="menu-item"
            @click="goTo('/schueler/benachrichtigungen')"
          >
            Benachrichtigungen
          </button>
          <button
            type="button"
            class="menu-item"
            @click="goTo(isStudent ? '/schueler/einstellungen' : '/lehrer/einstellungen')"
          >
            Einstellungen
          </button>
          <button
            type="button"
            class="menu-item"
            @click="goTo(isStudent ? '/schueler/hilfe' : '/lehrer/hilfe')"
          >
            Hilfe / Datenschutz
          </button>
        </div>

        <div class="menu-section">
          <div class="menu-row">
            <span>Sprache</span>
            <span>{{ language }}</span>
          </div>
          <button type="button" class="menu-item" @click="toggleThemeFromMenu">
            {{ isDark ? 'Lightmode aktivieren' : 'Darkmode aktivieren' }}
          </button>
        </div>

        <div class="menu-section">
          <button type="button" class="menu-item danger" @click="goTo('/logout')">Logout</button>
        </div>
      </div>
    </div>
  </div>

</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTheme } from '@/composables/useTheme'
import { apiClient } from '@/services/apiClient'
import { getTeacherSettings, updateTeacherSettings } from '@/services/teacherData'

defineProps({
  showHamburger: { type: Boolean, default: false }
})
defineEmits(['toggle-sidebar'])

const route = useRoute()
const router = useRouter()
const { isDark, toggleTheme, loadFromServer } = useTheme()
const language = ref('Deutsch')
const profileOpen = ref(false)
const profileMenuRef = ref(null)
const profile = ref({
  name: '',
  email: '',
  klasse: ''
})

function getRoleFromToken() {
  const token = localStorage.getItem('token')
  if (!token) return null
  try {
    return JSON.parse(atob(token.split('.')[1])).role || null
  } catch {
    return null
  }
}

const currentRole = computed(() => getRoleFromToken())
const isStudent = computed(() => currentRole.value === 'Schueler')
const roleLabel = computed(() => {
  if (currentRole.value === 'Schueler') return 'Schülerkonto'
  if (currentRole.value === 'Lehrer') return 'Lehrerkonto'
  if (currentRole.value === 'Admin') return 'Administratorkonto'
  return 'Konto'
})

async function persistLanguage() {
  if (getRoleFromToken() !== 'Lehrer') return
  try {
    await updateTeacherSettings({ sprache: language.value })
  } catch (e) {
    console.warn('Konnte Sprache nicht speichern', e)
  }
}

async function loadProfile() {
  const role = getRoleFromToken()
  if (role === 'Schueler') {
    try {
      const res = await apiClient.get('/schueler/me')
      const student = res.data || {}
      profile.value = {
        name: [student.vorname, student.nachname].filter(Boolean).join(' ') || 'Schülerprofil',
        email: student.email || '',
        klasse: student.klasse?.name || student.klasse_name || student.klassenname || '',
      }
      return
    } catch (e) {
      console.warn('Konnte Schülerprofil nicht laden', e)
    }
  }

  if (role === 'Lehrer') {
    try {
      const res = await apiClient.get('/lehrer/me')
      const teacher = res.data || {}
      profile.value = {
        name: [teacher.vorname, teacher.nachname].filter(Boolean).join(' ') || 'Lehrerprofil',
        email: teacher.email || '',
        klasse: '',
      }
      return
    } catch (e) {
      console.warn('Konnte Lehrerprofil nicht laden', e)
    }
  }

  profile.value = {
    name: role === 'Lehrer' ? 'Lehrerprofil' : 'Profil',
    email: '',
    klasse: '',
  }
}

function toggleProfileMenu() {
  profileOpen.value = !profileOpen.value
}

function closeProfileMenu() {
  profileOpen.value = false
}

function handleDocumentClick(event) {
  if (!profileMenuRef.value?.contains(event.target)) {
    closeProfileMenu()
  }
}

function goTo(path) {
  closeProfileMenu()
  if (route.path !== path) {
    router.push(path)
  }
}

function goToProfile() {
  if (isStudent.value) {
    goTo('/schueler/profil')
    return
  }
  if (currentRole.value === 'Lehrer') {
    goTo('/lehrer/profil')
    return
  }
  goTo('/lehrer/einstellungen')
}

function toggleThemeFromMenu() {
  toggleTheme()
  closeProfileMenu()
}

onMounted(async () => {
  await loadFromServer()
  await loadProfile()
  if (getRoleFromToken() !== 'Lehrer') return
  try {
    const s = await getTeacherSettings()
    if (s?.sprache) language.value = s.sprache
  } catch (e) {
    console.warn('Konnte Lehrer-Einstellungen nicht laden', e)
  }
})

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
})

watch(() => route.path, () => {
  closeProfileMenu()
})
</script>

<style>
.header {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  align-items: center;
  gap: 0.8rem;
  padding-right: 2rem;
  min-width: 0;
  max-width: 100%;
}

.icon-btn {
  width: 38px;
  height: 38px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: var(--second-background-color);
  display: grid;
  place-items: center;
  cursor: pointer;
}

.lang {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border-radius: 999px;
  padding: 0.25rem 0.6rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.flag {
  font-size: 0.95rem;
  line-height: 1;
}

.lang-select {
  appearance: none;
  border: none;
  background: transparent;
  color: var(--text);
  font-size: 0.9rem;
  outline: none;
  cursor: pointer;
}

.profile-wrap {
  position: relative;
}

.profile-btn {
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  border-radius: 999px;
}

.profile-menu {
  position: absolute;
  top: calc(100% + 0.6rem);
  right: 0;
  width: min(320px, 82vw);
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background:
    linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03)),
    var(--first-background-color);
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.18);
  overflow: hidden;
  z-index: 20;
}

.profile-card {
  padding: 1rem 1.1rem 0.9rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.profile-name {
  font-size: 1rem;
  font-weight: 700;
}

.profile-meta,
.profile-role,
.menu-row {
  color: var(--muted);
  font-size: 0.9rem;
}

.profile-meta + .profile-meta,
.profile-meta + .profile-role {
  margin-top: 0.2rem;
}

.profile-role {
  margin-top: 0.45rem;
}

.menu-section {
  padding: 0.45rem;
}

.menu-section + .menu-section {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.menu-item,
.menu-row {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 0;
  background: transparent;
  color: var(--text);
  text-align: left;
  padding: 0.8rem 0.75rem;
  border-radius: 12px;
}

.menu-item {
  cursor: pointer;
  font: inherit;
}

.menu-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.menu-item.danger {
  color: #d45454;
}

.hamburger {
  display: none;
}

@media (max-width: 1024px) {
  .hamburger {
    display: grid;
    margin-right: auto;
  }

  .header {
    padding-right: 0.5rem;
    width: 100%;
  }
}

@media (max-width: 480px) {
  .header {
    padding-right: 0.25rem;
    gap: 0.5rem;
  }

  .icon-btn {
    width: 34px;
    height: 34px;
  }

  .profile {
    width: 36px;
    height: 36px;
  }

  .profile-menu {
    right: -0.25rem;
  }
}

@media (max-width: 480px) {
  .lang {
    display: none;
  }
}
</style>
