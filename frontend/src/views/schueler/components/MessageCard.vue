<template>
  <div class="msg-card">
    <div class="date-line">
      {{ formatDate(msg.erstellt_am) }}
    </div>

    <div class="card-content">
      
      <svg class="svg_nav" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M12.02 2.90991C8.71 2.90991 6.02 5.59991 6.02 8.90991V11.7999C6.02 12.4099 5.76 13.3399 5.45 13.8599L4.3 15.7699C3.59 16.9499 4.08 18.2599 5.38 18.6999C9.69 20.1399 14.34 20.1399 18.65 18.6999C19.86 18.2999 20.39 16.8699 19.73 15.7699L18.58 13.8599C18.28 13.3399 18.02 12.4099 18.02 11.7999V8.90991C18.02 5.60991 15.32 2.90991 12.02 2.90991Z"
          stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" />
        <path
          d="M13.87 3.19994C13.56 3.10994 13.24 3.03994 12.91 2.99994C11.95 2.87994 11.03 2.94994 10.17 3.19994C10.46 2.45994 11.18 1.93994 12.02 1.93994C12.86 1.93994 13.58 2.45994 13.87 3.19994Z"
          stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M15.02 19.0601C15.02 20.7101 13.67 22.0601 12.02 22.0601C11.2 22.0601 10.44 21.7201 9.9 21.1801C9.36 20.6401 9.02 19.8801 9.02 19.0601"
          stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" />
      </svg>

      <div class="text">
        <h3>{{ msg.titel }}</h3>
        <p>{{ msg.inhalt }}</p>

        <div class="meta">
          <span>{{ msg.fach_name }}</span>
        </div>

        <div class="actions" v-if="msg.system !== 1">
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
const NOTIFICATIONS_UPDATED_EVENT = 'student-notifications-updated'

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
  const token = localStorage.getItem("token")

  const url = read
    ? `/api/schueler/nachrichten/${props.msg.id}/lesen`
    : `/api/schueler/nachrichten/${props.msg.id}/ungelesen`

  await axios.put(url, null, {
    headers: { Authorization: `Bearer ${token}` }
  })
  await props.reload()
  window.dispatchEvent(new CustomEvent(NOTIFICATIONS_UPDATED_EVENT))
}

async function removeMsg() {
  if (!confirm('Willst du diese Benachrichtigung wirklich löschen?')) {
    return
  }

  const token = localStorage.getItem("token")

  await axios.delete(
    `/api/schueler/nachrichten/${props.msg.id}`,
    { headers: { Authorization: `Bearer ${token}` } }
  )

  await props.reload()
  window.dispatchEvent(new CustomEvent(NOTIFICATIONS_UPDATED_EVENT))
}
</script>

<style scoped>
.svg_nav path {
  stroke: var(--icon-color);
}

.svg_nav {
  width: 50px;
  height: 50px;
}

.msg-card {
  background: var(--second-background-color);
  padding: 1.5rem;
  border-radius: 14px;
  margin: 1rem 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.date-line {
  font-size: 0.9rem;
  color: var(--text);
  margin-bottom: .5rem;
}
.card-content {
  align-items: center;
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
  color: var(--muted);
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
  background: linear-gradient(to right, var(--primary), var(--secondary));
  color: white;
  font-size: .8rem;
}
.actions .danger {
  background: #c92a2a;
}
.actions .danger:hover {
  background: #871515;
}
</style>
