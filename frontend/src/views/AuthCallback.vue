<script setup>
import { useRouter, useRoute } from "vue-router"
import { onMounted } from "vue"

const router = useRouter()
const route = useRoute()

onMounted(() => {
  const token = route.query.token

  if (!token) {
    router.replace("/login")
    return
  }

  // Token speichern
  localStorage.setItem("token", token)

  // URL bereinigen
  router.replace({ query: {} })

  // Rolle aus Token bestimmen
  const payload = JSON.parse(atob(token.split('.')[1]))
  const role = payload.role

  if (role === "Schueler") {
    router.push("/schueler/faecher")
  } else if (role === "Lehrer") {
    router.push("/lehrer/faecher")
  } else {
    router.push("/login")
  }
})
</script>

<template>
  <p>Authentifizierung läuft...</p>
</template>
