<template>
  <div class="app">
    <Navbar />
    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import Navbar from './components/AdminNavbar.vue'
</script>

<style>
.app {
  display: flex;
}

/* Abstand links lassen, damit Content nicht unter der Navbar verschwindet */
.content {
  margin-left: 220px;
  padding: 1.5rem;
  background-color: #f9f9f9;
  min-height: 100vh;
  width: 100%;
}
</style>
