<template>
  <div class="app">
    <!-- Dynamische Navbar: Admin, Schüler oder keine -->
    <component :is="currentNavbar" v-if="currentNavbar" />

    <main class="content">
      <Header/>
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import Header from './components/Header.vue'
import AdminNavbar from './components/AdminNavbar.vue'
import StudentNavbar from './components/StudentNavbar.vue'
// import DarkLightMode from '@/components/DarkLightMode.vue' // optional, nur wenn du sie verwendest

const route = useRoute()

// Navbar abhängig von meta.navbar wählen
const currentNavbar = computed(() => {
  switch (route.meta.navbar) {
    case 'admin':
      return AdminNavbar
    case 'student':
      return StudentNavbar
    default:
      return null // keine Navbar, z. B. bei Login
  }
})
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
