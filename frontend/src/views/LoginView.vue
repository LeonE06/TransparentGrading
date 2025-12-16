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
      <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" width="18"
        alt="Microsoft Logo" />
      <span>Microsoft</span>
    </button>
    <button class="btn create-btn" @click="downloadPDF">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M10.49 2.23006L5.5 4.11006C4.35 4.54006 3.41 5.90006 3.41 7.12006V14.5501C3.41 15.7301 4.19 17.2801 5.14 17.9901L9.44 21.2001C10.85 22.2601 13.17 22.2601 14.58 21.2001L18.88 17.9901C19.83 17.2801 20.61 15.7301 20.61 14.5501V7.12006C20.61 5.89006 19.67 4.53006 18.52 4.10006L13.53 2.23006C12.68 1.92006 11.32 1.92006 10.49 2.23006Z"
          stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M9.04999 11.8701L10.66 13.4801L14.96 9.18005" stroke="var(--icon-color)" stroke-width="1.5"
          stroke-linecap="round" stroke-linejoin="round" />
      </svg>

      Hilfe / Datenschutz
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
  } catch (e) {
    // Ungültiger Token -> sauber ausloggen
    localStorage.removeItem("token");
  }
});

function loginMicrosoft() {
  window.location.href = "https://transparentgrading.onrender.com/microsoft";
}

function downloadPDF() {
  const url = '/TransparentGrading_Datenschutzkonzept_v1.0.pdf'
  const a = document.createElement('a')
  a.href = url
  a.download = 'TransparentGrading_Datenschutzkonzept_v1.0.pdf'
  document.body.appendChild(a)
  a.click()
  a.remove()
}
</script>

<style scoped>
.create-btn {
  background-color: var(--first-background-color);
  border: none;
  cursor: pointer;
  color: var(--text);
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
  align-items: center;
  transition: 0.2s;
}

.create-btn:hover {
  transform: scale(1.02);
}

svg {
      margin-right: 6px;
}

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