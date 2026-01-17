<template>
  <div class="app">
    <!-- Dynamische Navbar: Admin, Schüler oder keine -->
    <component :is="currentNavbar" v-if="currentNavbar" />

    <Header v-if="login" />
    <LoginView v-if="login"></LoginView>


      <main class="content" v-if="!login">
        <Header />
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
import TeacherNavbar from './components/TeacherNavbar.vue'
import LoginView from './views/LoginView.vue'
// import DarkLightMode from '@/components/DarkLightMode.vue' // optional, nur wenn du sie verwendest

const route = useRoute()

// Navbar abhängig von meta.navbar wählen
const currentNavbar = computed(() => {
  switch (route.meta.navbar) {
    case 'admin':
      return AdminNavbar
    case 'student':
      return StudentNavbar
    case 'teacher':
      return TeacherNavbar
    default:
      return null // keine Navbar, z. B. bei Login
  }
})


const login = computed(() => {
  switch (route.meta.navbar) {
    case 'admin':
      return false
    case 'student':
      return false
    default:
      return true
  }
})
</script>

<style>
.content {
  margin-left: 240px;
  padding: 1.5rem;
  background-color: var(--first-background-color);
  min-height: 100vh;
  width: 75vw;
  padding-left: 100px;
}
</style>
