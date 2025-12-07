<script setup>
import { useRouter, useRoute } from "vue-router"

const router = useRouter()
const route = useRoute()

const token = route.query.token

if (!token) {
  router.push("/login")
  return
}

// Token speichern
localStorage.setItem("token", token)

// URL bereinigen
router.replace({ query: {} })

// Rolle auslesen
const payload = JSON.parse(atob(token.split('.')[1]))
const role = payload.role

if (role === "Schueler") {
  router.push("/schueler/faecher")
} else if (role === "Lehrer") {
  router.push("/lehrer/faecher")
} else {
  router.push("/login")
}
</script>

<template>
  <p>Authentifizierung läuft...</p>
</template>
