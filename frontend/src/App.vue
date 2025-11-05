<template>
  <div class="app">
    <Navbar />
    
    <main class="content">
      <Header />
      <router-view />
    </main>
  </div>
</template>

<script setup>
import Navbar from './components/AdminNavbar.vue'
import Header from './components/Header.vue';
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
