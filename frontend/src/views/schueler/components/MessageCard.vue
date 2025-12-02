<template>
  <div class="msg-card">
    <div class="date-line">
      {{ formatDate(msg.erstellt_am) }}
    </div>

    <div class="card-content">
      <img class="icon" src="/images/notification.png" />

      <div class="text">
        <h3>{{ msg.titel }}</h3>
        <p>{{ msg.inhalt }}</p>

        <div class="meta">
          <span>{{ msg.fach_name }}</span>
          <span>{{ msg.kurs_name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps(['msg'])

function formatDate(d) {
  const date = new Date(d)
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    weekday: 'long'
  })
}
</script>

<style scoped>
.msg-card {
  background: #fff;
  padding: 1.5rem;
  border-radius: 14px;
  margin: 1rem 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.date-line {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: .5rem;
}
.card-content {
  display: flex;
  gap: 1rem;
}
.icon {
  width: 80px;
}
.text h3 {
  margin-bottom: .4rem;
}
.meta {
  margin-top: .3rem;
  color: #666;
  font-size: .85rem;
}
</style>
