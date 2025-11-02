<template>
    <div class="klassen-view">
        <!-- obere Steuerleiste -->
        <div class="toolbar">
            <div class="left-controls">
                <h1 class="title">Datenschutzkonzept</h1>
            </div>

            <div class="right-controls">

                <button class="btn create-btn" @click="downloadPDF">
                    PDF herunterladen
                </button>
            </div>
        </div>


        <div class="content-wrapper" v-html="htmlContent"></div>
    </div>




</template>

<script setup>
import { ref, onMounted } from 'vue'

const htmlContent = ref('')

onMounted(async () => {
    try {
        // Response als ArrayBuffer lesen, dann als Windows-1252 dekodieren
        const response = await fetch('/TransparentGrading_Datenschutzkonzept_v1.0.html')
        const arrayBuffer = await response.arrayBuffer()

        // Windows-1252 zu UTF-8 konvertieren
        const decoder = new TextDecoder('windows-1252')
        const text = decoder.decode(arrayBuffer)

        htmlContent.value = text
    } catch (error) {
        console.error('Fehler beim Laden der HTML-Datei:', error)
        htmlContent.value = '<p>Fehler beim Laden des Inhalts.</p>'
    }
})

function downloadPDF() {
    const url = '/TransparentGrading_Datenschutzkonzept_v1.0.pdf'
    const a = document.createElement('a')
    a.href = url
    a.download = 'TransparentGrading_Datenschutzkonzept_v1.0.pdf'
    document.body.appendChild(a)
    a.click()
    a.remove()
}

</script>

<style scoped> 
.loading {
    font-style: italic;
    color: #666;
}

* {
    text-align: left;
}

.klassen-view {
    padding: 1rem 2rem;
}

.title {
    font-size: 2rem;
    margin-bottom: 2rem;
    text-align: left;
    font-weight: 650;
}

/* Toolbar */
.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.2rem;
}

.left-controls,
.right-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Buttons */
.btn {
    background-color: var(--first-background-color);
    border: 1.5px solid #EAEAEA;
    border-radius: 20px;
    padding: 0.4rem 0.8rem;
    cursor: pointer;
    transition: background-color 0.2s;
    padding: 16px 30px;
    min-width: 180px;
}

.create-btn {
    background-image: linear-gradient(to right, #6A16CC, #73A0F1);
    color: white;
    border: none;
}
</style>
