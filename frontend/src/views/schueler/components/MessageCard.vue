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

        <div class="actions">
          <button v-if="msg.gelesen == 0" @click="toggle(true)">
            ✔ als gelesen
          </button>

          <button v-if="msg.gelesen == 1" @click="toggle(false)">
            ⟳ als ungelesen
          </button>

          <button class="danger" @click="removeMsg">
            🗑 löschen
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'

const props = defineProps(['msg', 'reload'])

function formatDate(d) {
  const date = new Date(d)
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    weekday: 'long'
  })
}

async function toggle(read) {
  const url = read
    ? `/schueler/nachrichten/${props.msg.id}/lesen`
    : `/schueler/nachrichten/${props.msg.id}/ungelesen`

  await axios.put(url)
  props.reload()
}

async function removeMsg() {
  if (!confirm('Willst du diese Benachrichtigung wirklich löschen?')) {
    return
  }

  await axios.delete(`/schueler/nachrichten/${props.msg.id}`)
  props.reload()
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
.actions {
  margin-top: 1rem;
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.actions button {
  padding: .4rem .6rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  background: #1864ab;
  color: white;
  font-size: .8rem;
}
.actions button:hover {
  background: #0c3c6a;
}
.actions .danger {
  background: #c92a2a;
}
.actions .danger:hover {
  background: #871515;
}
</style>
