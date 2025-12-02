import axios from 'axios'
import { ref, watch } from 'vue'

const STORAGE_KEY = 'tg-theme'
const isDark = ref(false)
const ready = ref(false)

function applyTheme(value) {
  const root = document.documentElement
  if (value) root.classList.add('dark')
  else root.classList.remove('dark')
  localStorage.setItem(STORAGE_KEY, value ? 'dark' : 'light')
}

function loadLocal() {
  const saved = localStorage.getItem(STORAGE_KEY)
  if (saved === 'dark') isDark.value = true
  if (saved === 'light') isDark.value = false
  applyTheme(isDark.value)
  ready.value = true
}

export async function loadFromServer() {
  try {
    const res = await axios.get('/settings')
    const serverValue = res.data?.light_darkmode
    if (serverValue !== null && serverValue !== undefined) {
      isDark.value = !!serverValue
      applyTheme(isDark.value)
    }
  } catch (err) {
    console.warn('Theme: load failed', err)
  } finally {
    ready.value = true
  }
}

let writeTimer = null
function writeToServer(value) {
  if (writeTimer) clearTimeout(writeTimer)
  writeTimer = setTimeout(async () => {
    try {
      await axios.put('/settings', { light_darkmode: !!value })
    } catch (err) {
      console.warn('Theme: save failed', err)
    }
  }, 300)
}

// Watch nur wenn initialer Load abgeschlossen ist
watch(isDark, (v) => {
  if (!ready.value) {
    // initiale Änderung während Laden ignorieren
    applyTheme(v)
    return
  }
  applyTheme(v)
  writeToServer(v)
})

// Exponiere sauberen Toggle-Namen, kein Namenskonflikt
export function toggleTheme() {
  isDark.value = !isDark.value
}

export function useTheme() {
  if (!ready.value) loadLocal()
  return { isDark, toggleTheme, loadFromServer, ready }
}
