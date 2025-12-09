<script setup>
import { onMounted } from "vue";

onMounted(() => {
    console.log("Logout executed!");
  // JWT entfernen
  localStorage.removeItem("token");
  document.cookie = "auth_token=; Path=/; Max-Age=0; SameSite=None; Secure";

  // Automatischer Microsoft Logout + Redirect zur Login-Seite
  const redirectUri = encodeURIComponent("https://transparent-grading-flax.vercel.app/login");
  window.location.href = `https://login.microsoftonline.com/logout.srf?post_logout_redirect_uri=${redirectUri}`;
});
</script>

<template>
  <div>Abmeldung...</div>
</template>
