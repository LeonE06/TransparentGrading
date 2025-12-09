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
  router.replace("/login");
} else {
  // Token speichern
  localStorage.setItem("token", token);

  // Cookie für Symfony (Backend Auth)
  localStorage.setItem("token", token);


  // Token-Infos (Rolle) auslesen
  const payload = JSON.parse(atob(token.split(".")[1]));
  const role = payload.role;

  console.log("ROLE:", role);

  if (role === "Schueler") {
    router.replace("/schueler/faecher");
  } else if (role === "Lehrer") {
    router.replace("/admin/klassen");
  } else {
    router.replace("/login");
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
