<script setup>
import { useRouter, useRoute } from "vue-router"

const router = useRouter()
const route = useRoute()

const token = route.query.token

if (token) {
  // Token speichern
  localStorage.setItem("token", token)

  // Token-Daten auslesen
  const payload = JSON.parse(atob(token.split('.')[1]))
  const role = payload.role

  // URL aufräumen
  router.replace({ query: {} })

  // Weiterleitung nach Rolle
  if (role === "Schueler") {
    router.push("/schueler/faecher")
  } else if (role === "Lehrer") {
    router.push("/lehrer/faecher")
  } else {
    router.push("/login")
  }
} else {
  router.push("/login")
}
</script>

<template>
  <p>Authentifizierung läuft...</p>
</template>
