<template>
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-2xl w-[600px] shadow-xl">
      <h2 class="text-2xl font-semibold mb-6">Neue Klasse erstellen</h2>

      <!-- Klassenname -->
      <label class="block text-sm font-medium text-gray-600 mb-1">Klassenbezeichnung</label>
      <input
        v-model="className"
        type="text"
        placeholder="z.B. 4AI"
        class="w-full border rounded-lg p-2 mb-4 focus:ring-2 focus:ring-indigo-400 outline-none"
      />

      <!-- Schüler-Suche -->
      <label class="block text-sm font-medium text-gray-600 mb-1">Schüler*innen hinzufügen</label>
      <div class="relative">
        <input
          v-model="searchQuery"
          @input="searchStudents"
          type="text"
          placeholder="Schüler*innen suchen und hinzufügen..."
          class="w-full border rounded-lg p-2 mb-2 focus:ring-2 focus:ring-indigo-400 outline-none"
        />
        <ul
          v-if="searchResults.length && searchQuery"
          class="absolute bg-white border rounded-lg mt-1 max-h-40 overflow-y-auto w-full z-10"
        >
          <li
            v-for="student in searchResults"
            :key="student.id"
            @click="addStudent(student)"
            class="p-2 cursor-pointer hover:bg-indigo-100"
          >
            {{ student.vorname }} {{ student.nachname }}
          </li>
        </ul>
      </div>

      <!-- Ausgewählte Schüler -->
      <div class="flex flex-wrap gap-2 border rounded-lg p-2 min-h-[50px] mb-6">
        <div
          v-for="s in selectedStudents"
          :key="s.id"
          class="flex items-center bg-gray-100 px-3 py-1 rounded-full"
        >
          {{ s.vorname }} {{ s.nachname }}
          <button @click="removeStudent(s.id)" class="ml-2 text-gray-500 hover:text-red-500">✕</button>
        </div>
      </div>

      <!-- Buttons -->
      <div class="flex justify-end gap-2">
        <button @click="$emit('close')" class="bg-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300">
          Abbrechen
        </button>
        <button
          @click="createClass"
          class="bg-gradient-to-r from-indigo-400 to-indigo-600 text-white px-5 py-2 rounded-lg hover:opacity-90"
        >
          Klasse erstellen
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axios from "axios";

const emit = defineEmits(["close", "class-created"]);

const className = ref("");
const searchQuery = ref("");
const searchResults = ref([]);
const selectedStudents = ref([]);

const searchStudents = async () => {
  if (searchQuery.value.trim().length < 2) {
    searchResults.value = [];
    return;
  }
  const res = await axios.get(`/api/schueler?search=${searchQuery.value}`);
  searchResults.value = res.data;
};

const addStudent = (student) => {
  if (!selectedStudents.value.find((s) => s.id === student.id)) {
    selectedStudents.value.push(student);
  }
  searchResults.value = [];
  searchQuery.value = "";
};

const removeStudent = (id) => {
  selectedStudents.value = selectedStudents.value.filter((s) => s.id !== id);
};

const createClass = async () => {
  if (!className.value.trim()) return alert("Bitte einen Klassennamen eingeben!");

  // 1️⃣ Klasse erstellen
  const { data } = await axios.post("/api/klassen", { name: className.value });

  // 2️⃣ Schüler hinzufügen
  for (const s of selectedStudents.value) {
    await axios.post(`/api/klassen/${data.id}/schueler`, { schueler_id: s.id });
  }

  emit("class-created");
  emit("close");
};
</script>

<style scoped>
/* sanfte Transition für Dropdown */
ul {
  transition: all 0.2s ease-in-out;
}
</style>
