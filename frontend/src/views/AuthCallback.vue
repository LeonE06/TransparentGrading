<template>
  <div class="loading">
    <h2>Authentifiziere dich...</h2>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";

const router = useRouter();

const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get("token");

if (!token) {
  router.push("/login");
} else {
  // JWT speichern
  localStorage.setItem("token", token);

  // Cookie für Symfony setzen → wichtig für Backend!
  document.cookie = `auth_token=${token}; Path=/; Secure; SameSite=None`;

  // Rolle auslesen
  const payload = JSON.parse(atob(token.split(".")[1]));
  const role = payload.role;

  // Weiterleitung basierend auf Rolle
  if (role === "Schueler") {
    router.push("/schueler/faecher");
  } else if (role === "Lehrer") {
    router.push("/lehrer/faecher"); // Lehrerbereich richtig definieren!
  } else {
    router.push("/login");
  }
}
</script>

<style scoped>
.loading {
  margin-top: 100px;
  text-align: center;
  font-size: 22px;
  color: #0078d4;
}
</style>
