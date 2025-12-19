<template>
  <div class="lehrer-view">
    <h1 class="title">Leistungserfassung (Lehrer)</h1>

    <div class="layout">
      <div class="left">
        <LeistungForm @added="onAdded" @final-updated="onFinalUpdated" />
      </div>
      <div class="right">
        <LeistungTabelle ref="tabelle" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import LeistungForm from '@/components/LeistungForm.vue'
import LeistungTabelle from '@/components/LeistungTabelle.vue'

const tabelle = ref(null)

function onAdded() {
  if (tabelle.value && tabelle.value.load) tabelle.value.load()
}

function onFinalUpdated(result) {
  // optional: show toast or console
  console.log('Aktuelle Gesamtnote nach Schema:', result)
}
</script>

<style scoped>
.layout { display:flex; gap:1.2rem }
.left { flex: 1 }
.right { flex: 2 }
</style>
