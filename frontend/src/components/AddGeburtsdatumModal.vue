<template>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Elternbenachrichtigung</h2>
                <h2>{{ student.vorname }} {{ student.nachname }} bearbeiten</h2>

            </div>

            <div class="modal-body">
                <p>Da Sie noch nicht das 18. Lebensjahr vollendet haben müssen Ihre Eltern bei einer Leistungsgefährdung
                    benachrichtigt werden.
                    Daher bitten wir Sie die Emailadresse ihrer Erziehungsberechtigten in dem Feld unten einzutragen.
                </p>
                <!-- Email -->
                <label for="email">Email</label>
                <input id="email" v-model="email" type="email" placeholder="example@gmail.com" required />

                <p>
                    Nach Abschluss Ihres 18. Lebensjahres können Sie jederzeit in den Einstellungen die Benachrichtigung
                    deaktivieren.
                </p>
                <!-- Nachname -->
                <label for="nachname">Nachname</label>
                <input id="nachname" type="text" :value="student.nachname" readonly />

                <!-- Email -->
                <label for="email">Email</label>
                <input id="email" type="email" :value="student.email" readonly />

                <!-- Klasse ändern -->
                <label for="klasse">Klasse ändern</label>
                <select id="klasse" v-model="selectedClass">
                    <option disabled value="">Bitte auswählen</option>
                    <option v-for="k in classes" :key="k.id" :value="k.name">
                        {{ k.name }}
                    </option>
                </select>
            </div>

            <div class="modal-footer">
                <button class="cancel-btn" @click="$emit('close')">Abbrechen</button>
                <button class="save-btn" @click="saveChanges">Änderungen speichern</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
    student: {
        type: Object,
        required: true
    }
})
const emit = defineEmits(['close', 'updated'])

// API setup
const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''
const apiPrefix = isDev ? '' : `${apiBase}/api`

// State
const classes = ref([])
const selectedClass = ref(props.student.klassenname || '')

// 🔹 Klassen laden
async function loadClasses() {
    try {
        const res = await axios.get(`${apiPrefix}/classes`)
        classes.value = res.data
    } catch (err) {
        console.error('❌ Fehler beim Laden der Klassen:', err)
    }
}

// 🔹 Änderungen speichern
async function saveChanges() {
    try {
        await axios.put(`${apiPrefix}/students/${props.student.schueler_id}`, {
            klasse: selectedClass.value
        })
        emit('updated') // 🔹 Meldet Erfolg an Parent-Komponente
        emit('close')   // 🔹 Schließt Modal danach
    } catch (err) {
        console.error('❌ Fehler beim Speichern der Änderungen:', err)
        alert('Fehler beim Speichern der Änderungen.')
    }
}

onMounted(loadClasses)
</script>

<style scoped>
/* Overlay */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

/* Modal */
.modal {
    background-color: var(--first-background-color);
    border-radius: 12px;
    padding: 2rem 5rem;
    width: 800px;
    min-height: 50vh;
    max-width: 90%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Header */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.modal-header h2 {
    font-size: 1.4rem;
    font-weight: 700;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: var(--text);
}

/* Body */
.modal-body {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

label {
    font-weight: 600;
    margin-top: 0.8rem;
}

input,
select {
    width: 100%;
    padding: 0.6rem;
    border-radius: 8px;
    background-color: #f9f9f9;
    border: none;
    outline: none;
    transition: border-color 0.2s;
}

input[readonly] {
    color: var(--disabled-text);
    background: var(--disabled);
    cursor: not-allowed;
}

select {
    color: var(--text);
    background: var(--second-background-color);
    cursor: pointer;
}

/* Footer */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.cancel-btn {
    border-radius: 20px;
    padding: 0.4rem 0.8rem;
    cursor: pointer;
    transition: background-color 0.2s;
    padding: 16px 10px;
    min-width: 180px;
    background-color: var(--second-background-color);
    color: var(--aczent-color);
    border: none;
}

.save-btn {
    border-radius: 20px;
    padding: 0.4rem 0.8rem;
    cursor: pointer;
    transition: background-color 0.2s;
    padding: 16px 10px;
    min-width: 180px;
    background-image: linear-gradient(to right, var(--primary), var(--secondary));
    color: var(--white);
    border: none;
}
</style>