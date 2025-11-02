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

.content {
  margin-left: 9rem;
  padding: 1.5rem;
  background-color: #f9f9f9;
  min-height: 100vh;
  width: 100%;
}
</style>
