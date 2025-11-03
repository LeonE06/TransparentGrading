<template>
  <div class="app">
    <Navbar />
    
    <main class="content">
      <DarkLightMode />
      <router-view />
    </main>
  </div>
</template>

<script setup>
import Navbar from './components/AdminNavbar.vue'
import DarkLightMode from '@/components/DarkLightMode.vue'
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
