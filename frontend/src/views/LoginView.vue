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
  if (!token) return;

  const payload = JSON.parse(atob(token.split(".")[1]));
  const role = payload.role;

  if (role === "Schueler") {
    router.push("/schueler/faecher");
  } else if (role === "Lehrer") {
    router.push("/lehrer/faecher");
  } else {
    router.push("/login");
  }
});

function loginMicrosoft() {
  window.location.href = "https://transparentgrading.onrender.com/microsoft";
}
</script>

<style scoped>
.login-container {
  max-width: 400px;
  margin: 60px auto;
  text-align: center;
  padding: 40px;
  border: 1px solid #0078d4;
  border-radius: 12px;
  box-shadow: 0 0 12px rgba(0, 120, 212, 0.1);
  font-family: "Segoe UI", sans-serif;
}

.info-box {
  background: #f8f8f8;
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
  background: white;
  border: 1px solid #5e5e5e;
  border-radius: 6px;
  padding: 10px 20px;
  cursor: pointer;
  font-weight: 500;
  transition: 0.2s;
}

.ms-btn:hover {
  border-color: #0078d4;
  color: #0078d4;
  transform: scale(1.02);
}
</style>
