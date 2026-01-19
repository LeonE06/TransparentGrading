<template>
  <div class="tabs">
    <button
      v-for="t in tabs"
      :key="t.key"
      class="tab"
      :class="{ active: modelValue === t.key }"
      type="button"
      @click="$emit('update:modelValue', t.key)"
    >
      {{ t.label }}
    </button>
    <div class="tabs-right">
      <slot name="right" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: String, default: '' },
  tabs: { type: Array, default: () => [] }
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.tabs {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.75rem;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
}

.tab {
  padding: 0.55rem 1rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: transparent;
  color: var(--text);
  cursor: pointer;
}

.tab.active {
  border-color: rgba(144, 125, 255, 0.65);
}

.tabs-right {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
</style>