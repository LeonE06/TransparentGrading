<template>
  <div class="login-container">
    <h1>Login</h1>

    <div class="info-box">
      <h3>Anmeldeinformationen</h3>
      <p>Anmeldung nur mit Microsoft Schulkonto</p>
      <p><strong>SchülerInnen:</strong> 1234@htl.rennweg.at</p>
      <p><strong>LehrerInnen:</strong> ABC@htl.rennweg.at</p>
    </div>

    <button class="ms-btn" @click="loginMicrosoft">
      <img
        src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg"
        width="18"
        alt="Microsoft Logo"
      />
      <span>Microsoft</span>
    </button>
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

onMounted(() => {
  const token = localStorage.getItem("token");

  if (!token) {
    return; // bleibe auf Login
  }

  try {
    const payload = JSON.parse(atob(token.split(".")[1]));
    const role = payload.role;

    if (role === "Schueler") {
      router.push("/schueler/faecher");
    } else if (role === "Lehrer") {
      router.push("/admin/klassen");
    }
  } catch(e) {
    // Ungültiger Token -> sauber ausloggen
    localStorage.removeItem("token");
  }
});

function loginMicrosoft() {
  window.location.href = "https://transparentgrading.onrender.com/microsoft";
}
</script>

<style scoped>

span {
  color: var(--text)
}
.login-container {
  max-width: 400px;
  margin: 60px auto;
  text-align: center;
  padding: 40px;
  background: 
    linear-gradient(var(--first-background-color) 0 0) padding-box,
    linear-gradient(to right, var(--primary), var(--secondary)) border-box;
  border: 1.5px solid transparent;
  border-radius: 10px;
  box-shadow: 0 0 12px rgba(0, 120, 212, 0.1);
  font-family: "Segoe UI", sans-serif;
      display: flex;
    flex-direction: column;
    justify-content: space-around;
    height: 60vh;
}

.info-box {
  background: var(--first-background-color);
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  text-align: left;
}

.ms-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: var(--first-background-color);
  border: 1.5px solid var(--aczent-color);
  border-radius: 10px;
  padding: 10px 20px;
  cursor: pointer;
  font-weight: 500;
  transition: 0.2s;
}

.ms-btn:hover {
  background: 
    linear-gradient(var(--first-background-color) 0 0) padding-box,
    linear-gradient(to right, var(--primary), var(--secondary)) border-box;
  border: 1.5px solid transparent;
  border-radius: 10px;
  transform: scale(1.02);
}
</style>
