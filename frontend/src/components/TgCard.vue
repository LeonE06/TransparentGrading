<template>
  <div class="card" :class="variant">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  variant: { type: String, default: '' }
})
</script>

<style scoped>
.card {
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
}
</style>

