<template>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th v-for="c in columns" :key="c.key" :style="{ width: c.width || 'auto' }">{{ c.label }}</th>
          <th v-if="$slots.actions" class="actions-col"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!rows || rows.length === 0">
          <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="empty">{{ emptyText }}</td>
        </tr>
        <tr v-for="row in rows" :key="rowKey ? row[rowKey] : JSON.stringify(row)">
          <td v-for="c in columns" :key="c.key" class="cell">
            <slot :name="`cell-${c.key}`" :row="row">
              {{ row[c.key] ?? '—' }}
            </slot>
          </td>
          <td v-if="$slots.actions" class="actions">
            <slot name="actions" :row="row" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  columns: { type: Array, default: () => [] },
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: '' },
  emptyText: { type: String, default: 'Keine Daten vorhanden.' }
})
</script>

<style scoped>
.table-wrap {
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
}

html:not(.dark) .table-wrap {
  background: rgba(0, 0, 0, 0.02);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

thead th {
  text-align: left;
  font-size: 0.8rem;
  color: var(--muted);
  font-weight: 600;
  padding: 0.9rem 1rem;
  background: rgba(255, 255, 255, 0.03);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

tbody td {
  padding: 0.9rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  font-size: 0.92rem;
  vertical-align: top;
}

tbody tr:last-child td {
  border-bottom: none;
}

.actions-col {
  width: 92px;
}

.actions {
  text-align: right;
  white-space: nowrap;
}

.empty {
  padding: 1.5rem 1rem;
  color: var(--muted);
}
</style>