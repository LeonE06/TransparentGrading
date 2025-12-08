<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()

localStorage.removeItem("token")
document.cookie = "auth_token=; Path=/; Max-Age=0; SameSite=None; Secure"

// Microsoft SSO Logout → dann zurück zu Login
setTimeout(() => {
  window.location.href = "https://login.microsoftonline.com/logout.srf"
}, 500)

</script>

<template>
  <div>Abmeldung...</div>
</template>
