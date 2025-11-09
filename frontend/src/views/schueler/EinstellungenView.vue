<template>
    <div class="einstellungen-view">

        <h1 class="title">Einstellungen</h1>
        <div class="container">

            <div class="container-header">
                <i class="icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M17 20.5H7C4 20.5 2 19 2 15.5V8.5C2 5 4 3.5 7 3.5H17C20 3.5 22 5 22 8.5V15.5C22 19 20 20.5 17 20.5Z"
                            stroke="var(--icon-color)" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M17 9L13.87 11.5C12.84 12.32 11.15 12.32 10.12 11.5L7 9" stroke="var(--icon-color)"
                            stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>

                </i>


                <div class="toggle">
                    <div>
                        <h3>Elternbenachrichtigung</h3>
                        <p>Elternemail automatisch senden</p>
                        <input v-if="isUeber18" type="email" v-model="elternEmail" @blur="saveElternEmail()"
                            placeholder="Eltern-E-Mail-Adresse" :disabled="loading" />
                        <input v-else type="text" :value="elternEmail || 'Keine E-Mail-Adresse gespeichert'" readonly />
                    </div>
                    <div>
                        {{ elternEmailSenden ? 'Ein' : 'Aus' }}
                        <label class="switch">
                            <input type="checkbox" v-model="elternEmailSenden"
                                @change="sendElternEmail(elternEmailSenden)" :disabled="loading || !isUeber18">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>


            </div>
        </div>

        <div class="container">

            <div class="container-header">
                <i class="icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12.02 2.91016C8.71 2.91016 6.02 5.60016 6.02 8.91016V11.8002C6.02 12.4102 5.76 13.3402 5.45 13.8602L4.3 15.7702C3.59 16.9502 4.08 18.2602 5.38 18.7002C9.69 20.1402 14.34 20.1402 18.65 18.7002C19.86 18.3002 20.39 16.8702 19.73 15.7702L18.58 13.8602C18.28 13.3402 18.02 12.4102 18.02 11.8002V8.91016C18.02 5.61016 15.32 2.91016 12.02 2.91016Z"
                            stroke="var(--icon-color)" stroke-width="1.5" stroke-miterlimit="10"
                            stroke-linecap="round" />
                        <path
                            d="M13.87 3.19994C13.56 3.10994 13.24 3.03994 12.91 2.99994C11.95 2.87994 11.03 2.94994 10.17 3.19994C10.46 2.45994 11.18 1.93994 12.02 1.93994C12.86 1.93994 13.58 2.45994 13.87 3.19994Z"
                            stroke="var(--icon-color)" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M15.02 19.0601C15.02 20.7101 13.67 22.0601 12.02 22.0601C11.2 22.0601 10.44 21.7201 9.89999 21.1801C9.35999 20.6401 9.01999 19.8801 9.01999 19.0601"
                            stroke="var(--icon-color)" stroke-width="1.5" stroke-miterlimit="10" />
                    </svg>

                </i>
                <div class="toggle">
                    <div>
                        <h3>Benachrichtigungen</h3>
                        <p>Benachrichtigungen für alle Fächer aktivieren</p>
                    </div>
                    <div>
                        {{ benachrichtigungen ? 'Ein' : 'Aus' }}
                        <label class="switch">

                            <input type="checkbox" v-model="benachrichtigungen"
                                @change="saveBenachrichtigungen(benachrichtigungen)" :disabled="loading">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>





        </div>

        <div class="container">

            <div class="container-header">
                <i class="icon">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <mask id="path-1-inside-1_0_1" fill="white">
                            <path
                                d="M10 4.95911e-05C12.6522 4.95911e-05 15.1957 1.04308 17.0711 2.89969C18.9464 4.7563 20 7.27441 20 9.90005C20 12.5257 18.9464 15.0438 17.0711 16.9004C15.1957 18.757 12.6522 19.8 10 19.8L10 18.0306C12.1781 18.0306 14.2671 17.174 15.8073 15.6492C17.3474 14.1245 18.2127 12.0564 18.2127 9.90005C18.2127 7.74368 17.3474 5.67564 15.8073 4.15086C14.2671 2.62608 12.1781 1.76947 10 1.76947V4.95911e-05Z" />
                        </mask>
                        <path
                            d="M10 4.95911e-05C12.6522 4.95911e-05 15.1957 1.04308 17.0711 2.89969C18.9464 4.7563 20 7.27441 20 9.90005C20 12.5257 18.9464 15.0438 17.0711 16.9004C15.1957 18.757 12.6522 19.8 10 19.8L10 18.0306C12.1781 18.0306 14.2671 17.174 15.8073 15.6492C17.3474 14.1245 18.2127 12.0564 18.2127 9.90005C18.2127 7.74368 17.3474 5.67564 15.8073 4.15086C14.2671 2.62608 12.1781 1.76947 10 1.76947V4.95911e-05Z"
                            fill="#F7F7F7" stroke="var(--icon-color)" stroke-width="2"
                            mask="url(#path-1-inside-1_0_1)" />
                        <path
                            d="M10 19.8C7.34784 19.8 4.8043 18.757 2.92893 16.9004C1.05357 15.0437 0 12.5256 0 9.9C0 7.27436 1.05357 4.75625 2.92893 2.89964C4.80429 1.04303 7.34783 3.96461e-07 10 0L10 9.9L10 19.8Z"
                            fill="var(--icon-color)" />
                    </svg>
                </i>
                <div>
                    <h3>Light- / Darkmode</h3>
                    <p>Design der Webapp wählen</p>
                </div>
            </div>
            <div class="mode-toggle">

                <button class="btn theme-toggle" @click="toggleTheme"
                    :title="isDark ? 'Wechsel zu Hell' : 'Wechsel zu Dunkel'">
                    <span v-if="isDark">

                        <span>Light</span>
                        <span class="active">Dark</span>

                    </span>
                    <span v-else>

                        <span class="active">Light</span>
                        <span>Dark</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>


<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useTheme } from '@/composables/useTheme.js'
const { isDark, toggleTheme } = useTheme()

// Benachrichtigungen State
const benachrichtigungen = ref(false)
const loading = ref(false)

// Eltern-Email State
const elternEmail = ref('')
const elternEmailSenden = ref(false)
const isUeber18 = ref(false)

// Einstellungen vom Server laden
async function loadSettings() {
    try {
        const res = await axios.get('/settings')
        benachrichtigungen.value = res.data?.benachrichtigungen ?? false
        elternEmail.value = res.data?.elternemail ?? ''
        elternEmailSenden.value = res.data?.elternaktivierung ?? false
        isUeber18.value = res.data?.isUeber18 ?? false
    } catch (err) {
        console.warn('Benachrichtigungen: Laden fehlgeschlagen', err)
    }
}

// Benachrichtigungen zum Server speichern
let saveTimer = null
async function saveBenachrichtigungen(value) {
    if (saveTimer) clearTimeout(saveTimer)
    saveTimer = setTimeout(async () => {
        loading.value = true
        try {
            await axios.put('/settings', { benachrichtigungen: !!value })
        } catch (err) {
            console.warn('Benachrichtigungen: Speichern fehlgeschlagen', err)
        } finally {
            loading.value = false
        }
    }, 300) // Debounce: 300ms warten
}

// Eltern-Email speichern
let emailSaveTimer = null
async function saveElternEmail() {
    if (!isUeber18.value) return

    if (emailSaveTimer) clearTimeout(emailSaveTimer)
    emailSaveTimer = setTimeout(async () => {
        loading.value = true
        try {
            await axios.put('/settings', {
                elternemail: elternEmail.value || null
            })
        } catch (err) {
            console.warn('Eltern-Email: Speichern fehlgeschlagen', err)
        } finally {
            loading.value = false
        }
    }, 500)
}

// Eltern-Email Aktivierung speichern
async function sendElternEmail(value) {
    if (!isUeber18.value) {
        elternEmailSenden.value = false
        return
    }

    loading.value = true
    try {
        await axios.put('/settings', {
            elternaktivierung: !!value
        })
    } catch (err) {
        console.warn('Eltern-Aktivierung: Speichern fehlgeschlagen', err)
        elternEmailSenden.value = !value // Rollback bei Fehler
    } finally {
        loading.value = false
    }
}


// Beim Laden der Komponente Einstellungen laden
onMounted(() => {
    loadSettings()
})
</script>

<style scoped>
.info-text {
    font-size: 0.85rem;
    color: var(--text);
    margin-top: 0.5rem;
    font-style: italic;
}


.einstellungen-view {
    padding: 1rem 2rem;
}

.title {
    font-size: 2rem;
    margin-bottom: 2rem;
    text-align: left;
    font-weight: 650;
}

.container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: var(--first-background-color);
    border: 1px solid var(--second-background-color);
    border-radius: 10px;
    padding: 1rem 1.5rem;
    margin-top: 2rem;
    min-height: 100px;
}

.container-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.container-header h3 {
    margin: 0;
    font-weight: 600;
}

.container-header p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text);
}

i {
    margin-top: 0.4rem;
}

.theme-toggle {
    min-width: auto;
    padding: 8px;
    background-color: var(--firstbackgroundcolor);
    border: none;
}

button span span {
    padding: 0.3rem 2.5rem;
    color: var(--text);
}

.active {
    position: relative;
    border: 2px solid transparent;
    border-radius: 5px;
    background: linear-gradient(var(--first-background-color), var(--first-background-color)) padding-box,
        linear-gradient(to right, var(--primary), var(--secondary)) border-box;
}

.btn theme-toggle {
    background-color: var(--firstbackgroundcolor);
    border: none;
    display: flex;
    align-items: center;
}

.mode-toggle {
    cursor: pointer;
    background-color: var(--second-background-color);
    border-radius: 8px;
    margin-left: 2.25rem;
    padding-top: 0.1rem;
    padding-bottom: 0.1rem;

    width: min-content;
}


.switch {
    position: relative;
    display: inline-block;
    width: 42px;
    height: 23px;
    border-radius: 20px;
    margin-left: 0.5rem;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0.5px;
    background-color: var(--second-background-color);
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 3px;
    background-color: var(--first-background-color);
    transition: .4s;
    border-radius: 50%;
}

input:checked+.slider {
    background-image: linear-gradient(to right, var(--primary), var(--secondary));
}

input:checked+.slider:before {
    transform: translateX(18px);
    /* angepasst an neue Breite */
}

.toggle {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
}

.toggle > div:first-child {
    flex: 1;
    min-width: 0; /* Wichtig für Text-Overflow */
}

input,
select {
    max-width: 100%;
    min-width: 250px;
    padding: 0.6rem;
    border-radius: 8px;
    background-color: #f9f9f9;
    border: none;
    outline: none;
    transition: border-color 0.2s;
    margin-top: 0.8rem;
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
</style>