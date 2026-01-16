<script setup>
import { onMounted } from "vue";

onMounted(() => {
  // Token entfernen
  localStorage.removeItem("token");
  document.cookie = "auth_token=; Path=/; Max-Age=0; SameSite=None; Secure";

  // Microsoft Logout + Redirect zur Login-Seite
  const redirectUri = encodeURIComponent(
    "https://transparent-grading-flax.vercel.app/login"
  );

  window.location.href =
    `https://login.microsoftonline.com/common/oauth2/v2.0/logout` +
    `?post_logout_redirect_uri=${redirectUri}`;
});
</script>
