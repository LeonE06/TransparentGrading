<template>
  <div v-if="open" class="modal-backdrop" @click.self="close">
    <div class="modal">
      <header class="modal-head">
        <h3>Neues Fach erstellen</h3>
        <button class="icon-btn" @click="close">✕</button>
      </header>

      <div class="modal-body">
        <label>Fachbezeichnung
          <input v-model="form.title" type="text" placeholder="z.B. 2025/26 Medientechnik 4BI" />
        </label>

        <label>Klasse auswählen
          <select v-model="form.klasse" :disabled="loading">
            <option value="">Klasse wählen (optional)</option>
            <option v-for="k in classes" :key="k" :value="k">{{ k }}</option>
          </select>
        </label>

        <label>Schüler*innen suchen hinzufügen
          <input v-model="studentSearch" type="text" placeholder="Schüler*innen suchen und hinzufügen..." />
        </label>

        <label class="hint">
          <input type="checkbox" v-model="form.locked" />
          Bitte wählen Sie eine Klasse oder fügen Sie einzelne Schüler*innen hinzu.
        </label>

        <p v-if="loadError" class="error">
          {{ loadError }}
        </p>
      </div>

      <footer class="modal-actions">
        <button class="btn ghost" @click="close">Abbrechen</button>
        <button class="btn primary" @click="create" :disabled="loading">Fach erstellen</button>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { getMyCourses } from '@/services/teacherData'

const emit = defineEmits(['close', 'created'])
const props = defineProps({
  open: { type: Boolean, default: false },
  defaultClass: { type: String, default: '' }
})

const form = ref({
  title: '',
  klasse: props.defaultClass || '',
  locked: false
})
const studentSearch = ref('')

const courses = ref([])
const loading = ref(false)
const loadError = ref('')

const classes = computed(() => {
  const ks = (courses.value || [])
    .map(c => c?.klasse)
    .filter(Boolean)
  return Array.from(new Set(ks))
})

async function loadCourses() {
  loading.value = true
  loadError.value = ''
  try {
    courses.value = await getMyCourses()
  } catch (e) {
    courses.value = []
    loadError.value = 'Kurse konnten nicht geladen werden.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Wenn Modal aufgeht: Kurse laden + defaultClass setzen
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      form.value.klasse = props.defaultClass || form.value.klasse || ''
      loadCourses()
    }
  },
  { immediate: true }
)

function close() {
  emit('close')
}

function create() {
  if (!form.value.title) {
    alert('Bitte Fachbezeichnung eingeben.')
    return
  }
  emit('created', { ...form.value, studentSearch: studentSearch.value })
  form.value = { title: '', klasse: '', locked: false }
  studentSearch.value = ''
  close()
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: grid;
  place-items: center;
  z-index: 2000;
  padding: 1rem;
}

.modal {
  width: min(720px, 100%);
  background: var(--first-background-color);
  color: var(--text);
  border-radius: 14px;
  padding: 1.4rem;
  box-shadow: 0 30px 60px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-weight: 600;
}

input, select {
  padding: 0.65rem 0.8rem;
  border-radius: 10px;
  border: 1px solid var(--shadow);
  background: var(--second-background-color);
  color: var(--text);
}

.hint {
  flex-direction: row;
  align-items: center;
  gap: 0.6rem;
  font-weight: 500;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
}

.btn {
  border: none;
  border-radius: 10px;
  padding: 0.7rem 1.1rem;
  cursor: pointer;
  font-weight: 700;
}

.btn.primary {
  background: linear-gradient(120deg, var(--primary), var(--secondary));
  color: white;
}

.btn.ghost {
  background: transparent;
  border: 1px solid var(--shadow);
  color: var(--text);
}

.icon-btn {
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1.1rem;
  color: var(--text);
}

.error {
  margin: 0;
  font-weight: 600;
}

@media (max-width: 480px) {
  .modal {
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-actions {
    flex-direction: column;
  }

  .modal-actions .btn {
    width: 100%;
  }
}
</style>
