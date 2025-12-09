<script setup>
// Token sofort entfernen
localStorage.removeItem("token");
document.cookie = "auth_token=; Path=/; Max-Age=0; SameSite=None; Secure";

// Richtige Redirect-URL nach MS Logout
const redirectUri = encodeURIComponent("https://transparent-grading-flax.vercel.app/auth/login");

window.location.href =
  `https://login.microsoftonline.com/logout.srf?post_logout_redirect_uri=${redirectUri}`;
</script>

<template>
  <div>Abmeldung...</div>
</template>
