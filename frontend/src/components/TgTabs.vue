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
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
  padding: 0.75rem;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
  min-width: 0;
  max-width: 100%;
}

.tab {
  padding: 0.55rem 1rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: transparent;
  color: var(--text);
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
}

.tab.active {
  border-color: rgba(144, 125, 255, 0.65);
}

.tabs-right {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  min-width: 0;
  flex: 1 1 auto;
}

@media (max-width: 768px) {
  .tabs {
    flex-wrap: wrap;
  }

  .tabs-right {
    margin-left: 0;
    flex-basis: 100%;
    order: 10;
  }

  .tabs-right :deep(input),
  .tabs-right :deep(.search) {
    width: 100%;
    max-width: 100%;
  }

  .tab {
    white-space: nowrap;
  }
}

@media (max-width: 480px) {
  .tab {
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
  }
}
</style>

