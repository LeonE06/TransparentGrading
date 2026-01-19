<template>
  <p style="color:red">DEBUG: notifications view rendered</p>
<p>messages: {{ messages.length }}</p>
  <div class="notifications">
    <h1 class="title">Meine Benachrichtigungen</h1>

    <!-- UNGELESEN -->
    <section>
      <h2>ungelesen</h2>

      <div v-if="unread.length === 0" class="empty">
        Keine ungelesenen Nachrichten.
      </div>

      <MessageCard
        v-for="msg in unread"
        :key="msg.id"
        :msg="msg"
        :reload="loadData"
      />
    </section>

    <!-- GELESEN -->
    <section>
      <h2>gelesen</h2>

      <div v-if="read.length === 0" class="empty">
        Keine gelesenen Nachrichten.
      </div>

      <MessageCard
        v-for="msg in read"
        :key="msg.id"
        :msg="msg"
        :reload="loadData"
      />
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import MessageCard from './components/MessageCard.vue'

const messages = ref([])

console.log("🔔 Notifications component loaded");

async function loadData() {
  console.log("➡️ loadData() called");

  const token = localStorage.getItem("token");
  console.log("🔑 token exists?", !!token);

  try {
    const res = await axios.get(
      "https://transparentgrading.onrender.com/api/schueler/nachrichten",
      { headers: { Authorization: `Bearer ${token}` } }
    );

    console.log("✅ API OK, items:", res.data?.length);
    console.log("📦 data:", res.data);

    messages.value = res.data;
  } catch (err) {
    console.error("❌ API ERROR:", err);
    console.error("status:", err?.response?.status);
    console.error("data:", err?.response?.data);
  }
}

const unread = computed(() => messages.value.filter(m => m.gelesen == 0))
const read = computed(() => messages.value.filter(m => m.gelesen == 1))

onMounted(() => {
  console.log("✅ Notifications mounted");
  loadData();
});
</script>

<style scoped>
.title {
  font-size: 2rem;
  margin-bottom: 2rem;
}

section {
  margin-bottom: 2.5rem;
}

.empty {
  color: #777;
  margin: 1rem 0;
}
</style>
