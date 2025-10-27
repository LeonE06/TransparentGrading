<template>
  <div class="p-6">
    <h1 class="text-3xl font-semibold mb-6">Klassenverwaltung</h1>

    <button
      @click="showModal = true"
      class="bg-gradient-to-r from-indigo-400 to-indigo-600 text-white px-5 py-2 rounded-lg hover:opacity-90"
    >
      Neue Klasse erstellen
    </button>

    <ClassCreateModal
      v-if="showModal"
      @close="showModal = false"
      @class-created="loadClasses"
    />

    <ul class="mt-8 space-y-2">
      <li
        v-for="klasse in klassen"
        :key="klasse.id"
        class="border p-3 rounded-lg flex justify-between items-center"
      >
        <span>{{ klasse.name }} ({{ klasse.anzahl_schueler }} Schüler*innen)</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import ClassCreateModal from "@/components/ClassCreateModal.vue";

const showModal = ref(false);
const klassen = ref([]);

const loadClasses = async () => {
  const res = await axios.get("/api/klassen");
  klassen.value = res.data;
};

onMounted(loadClasses);
</script>
