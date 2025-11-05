<template>
  <div class="app">
    <!-- Dynamische Navbar -->
    <component :is="currentNavbar" />

    <main class="content">
      <Header />
      <router-view />
    </main>
  </div>
</template>

<script setup>
<<<<<<< HEAD
import Navbar from './components/AdminNavbar.vue'
import Header from './components/Header.vue';
=======
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import AdminNavbar from './components/AdminNavbar.vue'
import StudentNavbar from './components/StudentNavbar.vue'
import DarkLightMode from '@/components/DarkLightMode.vue'

const route = useRoute()

// Wenn meta.layout === 'student' → Schüler-Navbar, sonst Admin
const currentNavbar = computed(() =>
  route.meta.layout === 'student' ? StudentNavbar : AdminNavbar
)
>>>>>>> daca09a (moodboard + schueler navbar)
</script>

<style>
.content {
  margin-left: 9rem;
  padding: 1.5rem;
  background-color: var(--first-background-color);
  min-height: 100vh;
  width: 100%;
}
</style>
