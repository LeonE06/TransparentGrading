<template>
  <div class="header">
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

    <svg class="profile" width="44" height="44" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="30" cy="30" r="30" fill="var(--second-background-color)" />
      <path
        d="M30.0002 28C32.9917 28 35.4168 25.5748 35.4168 22.5833C35.4168 19.5917 32.9917 17.1666 30.0002 17.1666C27.0086 17.1666 24.5835 19.5917 24.5835 22.5833C24.5835 25.5748 27.0086 28 30.0002 28Z"
        stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      <path
        d="M39.306 38.8333C39.306 34.6408 35.1352 31.25 30.0002 31.25C24.8652 31.25 20.6943 34.6408 20.6943 38.8333"
        stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  </div>

</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useTheme } from '@/composables/useTheme'
import { getTeacherSettings, updateTeacherSettings } from '@/services/teacherData'

const { isDark, toggleTheme, loadFromServer } = useTheme()
const language = ref('Deutsch')

function getRoleFromToken() {
  const token = localStorage.getItem('token')
  if (!token) return null
  try {
    return JSON.parse(atob(token.split('.')[1])).role || null
  } catch {
    return null
  }
}

async function persistLanguage() {
  if (getRoleFromToken() !== 'Lehrer') return
  try {
    await updateTeacherSettings({ sprache: language.value })
  } catch (e) {
    console.warn('Konnte Sprache nicht speichern', e)
  }
}

onMounted(async () => {
  await loadFromServer()
  if (getRoleFromToken() !== 'Lehrer') return
  try {
    const s = await getTeacherSettings()
    if (s?.sprache) language.value = s.sprache
  } catch (e) {
    console.warn('Konnte Lehrer-Einstellungen nicht laden', e)
  }
})
</script>

<style>
.header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.8rem;
  padding-right: 2rem;
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
</style>
