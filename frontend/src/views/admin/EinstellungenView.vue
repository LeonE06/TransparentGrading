<template>
    <div class="einstellungen-view">

        <h1 class="title">Einstellungen</h1>
        <div class="mode-container">

            <div class="mode-header">
                <i class="icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
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
import { useTheme } from '@/composables/useTheme.js'
const { isDark, toggleTheme } = useTheme()
</script>

<style scoped>
.einstellungen-view {
    padding: 1rem 2rem;
}

.title {
    font-size: 2rem;
    margin-bottom: 2rem;
    text-align: left;
    font-weight: 650;
}

.mode-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: var(--first-background-color);
    border: 1px solid var(--second-background-color);
    border-radius: 10px;
    padding: 1rem 1.5rem;
    margin-top: 2rem;
}

.mode-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mode-header h3 {
    margin: 0;
    font-weight: 600;
}

.mode-header p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text);
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
</style>