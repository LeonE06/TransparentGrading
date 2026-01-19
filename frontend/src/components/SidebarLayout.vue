<template>
  <div class="layout">
    <aside class="sidebar">
      <slot name="sidebar" />
    </aside>
    <div class="main">
      <slot />
    </div>
  </div>
</template>

<script setup></script>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  min-height: 100vh;
}

.sidebar {
  border-right: 1px solid rgba(255, 255, 255, 0.06);
}
</style>

