<script setup>
import { useRouter, useRoute } from "vue-router";
import { onMounted } from "vue";

const router = useRouter();
const route = useRoute();

onMounted(() => {
  const token = route.query.token;

  // 1) Kein Token? → Zurück zum Login
  if (!token) {
    console.warn("Kein Token in Callback-URL gefunden!");
    router.push("/login");
    return;
  }

  try {
    // 2) Token speichern
    localStorage.setItem("token", token);

    // 3) Token Payload lesen
    const payloadBase64 = token.split(".")[1];
    const payloadJson = atob(payloadBase64);
    const payload = JSON.parse(payloadJson);

    const role = payload.role;

    // 4) URL bereinigen (damit Token nicht sichtbar bleibt)
    router.replace({ path: "/auth/callback", query: {} });

    // 5) Weiterleiten nach Rolle
    if (role === "Schueler") {
      router.push("/schueler/faecher");
    } else if (role === "Lehrer") {
      router.push("/lehrer/faecher");
    } else {
      console.warn("Unbekannte Rolle:", role);
      router.push("/login");
    }
  } catch (err) {
    console.error("Token konnte nicht verarbeitet werden:", err);
    router.push("/login");
  }
});
</script>

<template>
  <p style="margin: 50px; font-size: 22px;">Authentifizierung läuft…</p>
</template>
