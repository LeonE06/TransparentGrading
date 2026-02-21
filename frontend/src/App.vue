<template>
  <div class="app">
    <component
      :is="currentNavbar"
      v-if="currentNavbar"
      :open="sidebarOpen"
      @close="sidebarOpen = false"
    />

    <main class="content">
      <Header
        :show-hamburger="!!currentNavbar"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import Header from './components/Header.vue'
import AdminNavbar from './components/AdminNavbar.vue'
import StudentNavbar from './components/StudentNavbar.vue'
import TeacherNavbar from './components/TeacherNavbar.vue'

const route = useRoute()
const sidebarOpen = ref(false)

watch(() => route.path, () => {
  sidebarOpen.value = false
})

const currentNavbar = computed(() => {
  switch (route.meta.navbar) {
    case 'admin':
      return AdminNavbar
    case 'student':
      return StudentNavbar
    case 'teacher':
      return TeacherNavbar
    default:
      return null
  }
})
</script>

<style>
.content {
  margin-left: var(--content-margin);
  padding: 1.5rem;
  background-color: var(--first-background-color);
  min-height: 100vh;
  width: 75vw;
  padding-left: 100px;
}

@media (max-width: 1024px) {
  .content {
    margin-left: 0;
    width: 100%;
    padding-left: 1rem;
    padding-right: 1rem;
  }
}
</style>
