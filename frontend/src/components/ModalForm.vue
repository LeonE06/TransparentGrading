<template>
  <teleport to="body">
    <div v-if="open" class="overlay" @click.self="$emit('close')">
      <div class="modal" role="dialog" aria-modal="true">
        <header class="modal-head">
          <h2 class="modal-title">{{ title }}</h2>
          <button class="close" type="button" @click="$emit('close')" aria-label="Close">×</button>
        </header>
        <div class="modal-body">
          <slot />
        </div>
        <footer v-if="$slots.actions" class="modal-actions">
          <slot name="actions" />
        </footer>
      </div>
    </div>
  </teleport>
</template>

<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' }
})
defineEmits(['close'])
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: grid;
  place-items: center;
  z-index: 2000;
  padding: 2rem;
}

.modal {
  width: min(920px, 100%);
  border-radius: 16px;
  background-color: var(--first-background-color);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text);
  box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
}

html:not(.dark) .modal {
  background: rgba(255, 255, 255, 0.95);
}

.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.25rem 0.5rem;
}

.modal-title {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 650;
}

.close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: transparent;
  color: var(--text);
  cursor: pointer;
  font-size: 1.4rem;
  line-height: 1;
}

.modal-body {
  padding: 0.5rem 1.25rem 1rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 0 1.25rem 1.25rem;
}

@media (max-width: 480px) {
  .overlay {
    padding: 1rem;
    align-items: flex-start;
    overflow-y: auto;
  }

  .modal {
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-actions {
    flex-direction: column;
  }

  .modal-actions > * {
    width: 100%;
  }
}
</style>

