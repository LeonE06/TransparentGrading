<template>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Geburtsdatum hinzufügen</h2>
            </div>

            <div class="modal-body">
                <p>Bitte geben Sie Ihr Geburtsdatum ein:</p>
                
                <!-- Geburtsdatum -->
                <label for="geburtsdatum">Geburtsdatum</label>
                <input 
                    id="geburtsdatum" 
                    v-model="geburtsdatum" 
                    type="date" 
                    required 
                />
            </div>

            <div class="modal-footer">
                <button class="save-btn" @click="saveGeburtsdatum" :disabled="!geburtsdatum">
                    Speichern
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const emit = defineEmits(['close', 'updated'])

// API setup
const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''
const apiPrefix = isDev ? '' : `${apiBase}/api`

// State
const geburtsdatum = ref('')
const loading = ref(false)

// 🔹 Geburtsdatum speichern
async function saveGeburtsdatum() {
    if (!geburtsdatum.value) {
        alert('Bitte geben Sie ein Geburtsdatum ein.')
        return
    }

    loading.value = true
    try {
        const token = localStorage.getItem('token')
        
        // Hole zuerst die aktuelle Schüler-ID
        const studentRes = await axios.get(`${apiPrefix}/schueler/me`, {
            headers: {
                Authorization: `Bearer ${token}`
            }
        })
        
        const studentId = studentRes.data.id
        
        // Speichere das Geburtsdatum
        await axios.put(`${apiPrefix}/students/${studentId}`, {
            geburtsdatum: geburtsdatum.value
        }, {
            headers: {
                Authorization: `Bearer ${token}`
            }
        })
        
        emit('updated') // 🔹 Meldet Erfolg an Parent-Komponente
        emit('close')   // 🔹 Schließt Modal danach
    } catch (err) {
        console.error('❌ Fehler beim Speichern des Geburtsdatums:', err)
        alert('Fehler beim Speichern des Geburtsdatums.')
    } finally {
        loading.value = false
    }
}
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
    padding: 2rem 3rem;
    width: 500px;
    max-width: 90%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Header */
.modal-header {
    margin-bottom: 1.5rem;
}

.modal-header h2 {
    font-size: 1.4rem;
    font-weight: 700;
}

/* Body */
.modal-body {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.modal-body p {
    margin-bottom: 1rem;
}

label {
    font-weight: 600;
    margin-top: 0.8rem;
}

input {
    width: 100%;
    padding: 0.6rem;
    border-radius: 8px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    outline: none;
    transition: border-color 0.2s;
}

input:focus {
    border-color: var(--primary, #0078d4);
}

/* Footer */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.save-btn {
    border-radius: 20px;
    padding: 16px 10px;
    min-width: 180px;
    background-image: linear-gradient(to right, var(--primary), var(--secondary));
    color: var(--white);
    border: none;
    cursor: pointer;
    transition: opacity 0.2s;
}

.save-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>