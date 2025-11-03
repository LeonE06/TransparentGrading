import axios from 'axios'
import { useDark, useToggle } from '@vueuse/core'
import { ref, watch } from 'vue'

const isDark = useDark() // verwaltet html.class und localStorage automatisch
const _toggle = useToggle(isDark)
const ready = ref(false)

export async function loadFromServer() {
  try {
    const res = await axios.get('/settings')
    const serverValue = res.data?.light_darkmode
    if (serverValue !== null && serverValue !== undefined) {
      isDark.value = !!serverValue
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
    console.debug('Theme: change ignored until ready', v)
    return
  }
  console.debug('Theme: changed ->', v)
  writeToServer(v)
})

// Exponiere sauberen Toggle-Namen, kein Namenskonflikt
export function toggleTheme() {
  _toggle()
}

export function useTheme() {
  return { isDark, toggleTheme, loadFromServer, ready }
}