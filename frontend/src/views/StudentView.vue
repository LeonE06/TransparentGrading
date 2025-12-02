<template>
  <div class="student-view">
    <h1>Schüler*innen Ansicht (Prototyp)</h1>
    <p>Diese Ansicht zeigt eine schülerzentrierte, read-only Darstellung der eigenen Leistungen und der aktuellen Gesamtnote.</p>

    <div class="card">
      <h3>Meine Leistungen</h3>
      <table class="teacher-table">
        <thead>
          <tr><th>Typ</th><th>Note</th><th>Gewichtung</th><th>Datum</th></tr>
        </thead>
        <tbody>
          <tr v-for="it in items" :key="it.id">
            <td>{{ it.typ }}</td>
            <td>{{ it.note }}</td>
            <td>{{ it.gewichtung ?? '-' }}</td>
            <td>{{ formatDate(it.datum) }}</td>
          </tr>
        </tbody>
      </table>
      <div style="margin-top:1rem"><strong>Gesamtnote:</strong> {{ finalDisplay }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { getLeistungen } from '@/api/leistungen'
import grading from '@/services/grading'

const items = ref([])
const scheme = ref(grading.loadScheme())

async function load() {
  try {
    const res = await getLeistungen()
    items.value = res.data || []
  } catch (err) {
    console.error(err)
  }
}

onMounted(load)

const final = computed(() => grading.computeFinalGrade(items.value, scheme.value))
const finalDisplay = computed(() => final.value.finalGrade != null ? final.value.finalGrade : '-')

function formatDate(d) { try { return new Date(d).toLocaleDateString('de-DE') } catch { return d } }
</script>
