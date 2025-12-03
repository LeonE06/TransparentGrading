<template>
  <div>Lade ...</div>
</template>

<script setup>
import { useRouter } from "vue-router"

const router = useRouter()
const urlParams = new URLSearchParams(window.location.search)
const token = urlParams.get("token")

if (!token) {
  router.push("/login")
} else {
  localStorage.setItem("auth_token", token)
  router.push("/") // oder direkte Zielroute
}
</script>
