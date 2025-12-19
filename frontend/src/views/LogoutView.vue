<script setup>
localStorage.removeItem("token");
document.cookie = "auth_token=; Path=/; Max-Age=0; SameSite=None; Secure";

const redirectUri = encodeURIComponent(
  "https://transparent-grading-flax.vercel.app/login"
);

window.location.href =
  `https://login.microsoftonline.com/common/oauth2/v2.0/logout` +
  `?post_logout_redirect_uri=${redirectUri}`;
</script>

<template>
  <div>Abmeldung...</div>
</template>
