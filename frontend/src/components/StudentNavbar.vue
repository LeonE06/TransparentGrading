<template>
  <nav class="navbar">
    <img v-if="!isDark" src="/Logo_Transparent_Grading.png" class="logo" />
    <img v-else src="/Logo_Transparent_Grading_Dark.png" class="logo" />

    <!-- SCHÜLER NAVIGATION -->
    <router-link to="/schueler/faecher" class="nav-item" active-class="active">
      Meine Fächer
    </router-link>

    <router-link to="/schueler/benachrichtigungen" class="nav-item" active-class="active">
      Benachrichtigungen
    </router-link>

    <router-link to="/schueler/moodboard" class="nav-item" active-class="active">
      Moodboard
    </router-link>

    <router-link to="/schueler/einstellungen" class="nav-item" active-class="active">
      Einstellungen
    </router-link>

    <router-link to="/schueler/hilfe" class="nav-item" active-class="active">
      Hilfe / Datenschutz
    </router-link>

    <!-- LOGOUT BUTTON (NEU) -->
    <div class="nav-item" @click="logout">
      Logout
    </div>
  </nav>
</template>

<script setup>
import { useRouter } from "vue-router";
import { useTheme } from "@/composables/useTheme.js";

const { isDark } = useTheme();
const router = useRouter();

function logout() {
  console.log("Logout CLICK fired!");

  // Token löschen
  localStorage.removeItem("token");

  // Auth-Cookie löschen
  document.cookie = "auth_token=; Path=/; Max-Age=0; SameSite=None; Secure";

  // Redirect
  router.push("/login");
}
</script>

<style scoped>
.navbar {
  position: fixed;
  left: 0;
  top: 0;
  width: 300px;
  height: 100vh;
  background-color: var(--first-background-color);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  border-right: var(--second-background-color) 2.5px solid;
}

.logo {
  width: 200px;
  margin: 20px 0 25px;
}

.nav-item {
  width: 90%;
  padding: 20px 0;
  border-radius: 6px;
  text-decoration: none;
  color: var(--text);
  text-align: left;
  margin: 3vh 0;
  cursor: pointer;
}

.nav-item:hover {
  background-color: var(--second-background-color);
}

.active {
  background:
    linear-gradient(var(--first-background-color) 0 0) padding-box,
    linear-gradient(to right, var(--primary), var(--secondary)) border-box;
  border: 1.5px solid transparent;
  border-radius: 10px;
}
</style>
