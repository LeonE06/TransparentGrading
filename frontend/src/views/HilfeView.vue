<template>
    <div class="hilfe-container">
        <!-- Download-Button -->
        <div class="download-section">
            <a href="/TransparentGrading_Datenschutzkonzept_v1.0.pdf" download class="download-btn">
                📄 PDF herunterladen
            </a>
        </div>

        <!-- HTML-Inhalt -->
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
</script>

<style scoped>
.download-section {
    margin-bottom: 2rem;
    text-align: center;
}

.download-btn {
    display: inline-block;
    background-color: #4a90e2;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.2s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.download-btn:hover {
    background-color: #3b7ccc;
    text-decoration: none;
}


</style>