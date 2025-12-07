<script setup>
import { useRouter } from "vue-router"

const router = useRouter()

// HTTP-only Cookie löschen
document.cookie = "auth_token=; Max-Age=0; Path=/; SameSite=Strict"

// Kleine Wartezeit, dann Redirect
setTimeout(() => {
  router.push("/login")
}, 300)
</script>

<template>
  <p>Abmeldung...</p>
</template>

<style scoped>
p {
  text-align: center;
  margin-top: 40px;
  font-size: 18px;
  color: #666;
}
</style>
