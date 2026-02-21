<template>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Elternbenachrichtigung</h2>
            </div>

            <div class="modal-body">
                <p>Da Sie noch nicht das 18. Lebensjahr vollendet haben müssen Ihre Eltern bei einer Leistungsgefährdung
                    benachrichtigt werden.
                    Daher bitten wir Sie die Emailadresse ihrer Erziehungsberechtigten in dem Feld unten einzutragen.
                </p>

                <!-- Elternemail -->
                <label for="email">Email</label>
                <input id="email" v-model="email" type="email" placeholder="example@gmail.com" required />

                <p>
                    Nach Abschluss Ihres 18. Lebensjahres können Sie jederzeit in den Einstellungen die Benachrichtigung
                    deaktivieren.
                </p>
            </div>

            <div class="modal-footer">
                <button class="save-btn" @click="saveEmail" :disabled="!email">
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
const email = ref('')
const loading = ref(false)

// 🔹 Elternemail speichern
async function saveEmail() {
    if (!email.value) {
        alert('Bitte geben Sie eine Emailadresse ein.')
        return
    }

    // Email-Validierung
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(email.value)) {
        alert('Bitte geben Sie eine gültige Emailadresse ein.')
        return
    }

    loading.value = true
    try {
        const token = localStorage.getItem('token')

        // Verwende den Settings-Endpoint, der bereits die richtige Logik hat
        await axios.put(`${apiPrefix}/settings`, {
            elternemail: email.value
        }, {
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })

        // Erst close, dann updated - damit das Modal sofort geschlossen wird
        emit('close')   // 🔹 Schließt Modal sofort
        emit('updated') // 🔹 Meldet Erfolg an Parent-Komponente (lädt Daten neu)
    } catch (err) {
        console.error('❌ Fehler beim Speichern der Elternemail:', err)
        const errorMsg = err.response?.data?.error || err.message || 'Unbekannter Fehler'
        alert('Fehler beim Speichern der Elternemail: ' + errorMsg)
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

@media (max-width: 480px) {
  .modal {
    max-width: 95%;
    margin: 1rem;
    padding: 1.25rem;
    max-height: 90vh;
    overflow-y: auto;
  }

  .save-btn {
    min-width: 100%;
  }
}
</style>