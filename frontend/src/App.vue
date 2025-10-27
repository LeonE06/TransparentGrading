<template>
  <div class="flex min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md p-4 flex flex-col gap-6">
      <nav class="space-y-3">
        <button
          v-for="item in navItems"
          :key="item.label"
          @click="activeView = item.view"
          class="flex items-center gap-3 w-full text-left px-4 py-2 rounded-lg hover:bg-indigo-50"
          :class="{
            'bg-indigo-100 text-indigo-700 font-semibold': activeView === item.view
          }"
        >
          <component :is="item.icon" class="w-5 h-5" />
          <span>{{ item.label }}</span>
        </button>
      </nav>

      <div class="mt-auto">
        <button class="flex items-center gap-3 text-gray-600 hover:text-red-500 px-4 py-2 rounded-lg">
          <LogoutIcon class="w-5 h-5" />
          Logout
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6">
      <component :is="currentView" />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { GraduationCapIcon, UsersIcon, SettingsIcon, HelpCircleIcon, LogOutIcon as LogoutIcon } from "lucide-vue-next";
import ClassesView from "@/components/ClassesView.vue";

// Navigation items
const navItems = [
  { label: "Klassen", icon: GraduationCapIcon, view: "classes" },
  { label: "Schüler*innen", icon: UsersIcon, view: "students" },
  { label: "Lehrer*innen", icon: UsersIcon, view: "teachers" },
  { label: "Einstellungen", icon: SettingsIcon, view: "settings" },
  { label: "Hilfe / Datenschutz", icon: HelpCircleIcon, view: "help" },
];

const activeView = ref("classes");

const currentView = computed(() => {
  switch (activeView.value) {
    case "classes":
      return ClassesView;
    default:
      return {
        template: `<div class='text-gray-500'>Diese Seite ist noch in Arbeit 🚧</div>`,
      };
  }
});
</script>

<style>
body {
  font-family: 'Inter', sans-serif;
}
</style>
