<script setup>
import { useRouter } from "vue-router"

const router = useRouter()

function getRoleFromToken() {
  const cookieMatch = document.cookie.match(/(?:^| )auth_token=([^;]+)/)
  if (!cookieMatch) return null

  const token = cookieMatch[1]
  const payload = JSON.parse(atob(token.split('.')[1]))
  return payload.role
}

setTimeout(() => {
  const role = getRoleFromToken()

  if (role === "Schueler") {
    router.push("/schueler/faecher")
  } else if (role === "Lehrer") {
    router.push("/lehrer/faecher")
  } else {
    router.push("/login")
  }
}, 300)
</script>

<template>
  <p>Authentifizierung läuft...</p>
</template>
