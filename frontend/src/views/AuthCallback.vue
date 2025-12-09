<template>
  <div class="loading">
    <h2>Wird geladen...</h2>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";

const router = useRouter();

// Token aus URL holen
const params = new URLSearchParams(window.location.search);
const token = params.get("token");

if (!token) {
  // Kein Token -> zurück zum Login
  router.replace("/login");
} else {
  try {
    // Token speichern
    localStorage.setItem("token", token);

    const payload = JSON.parse(atob(token.split(".")[1]));
    const role = payload.role;

    console.log("Token gespeichert:", token);
    console.log("Rolle erkannt:", role);

    // Weiterleitung nach Rolle
    if (role === "Schueler") {
      router.replace("/schueler/faecher");
    } else if (role === "Lehrer") {
      router.replace("/admin/klassen");
    } else {
      router.replace("/login");
    }

  } catch (err) {
    console.error("Token ungültig", err);
    localStorage.clear();
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
