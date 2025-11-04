<template>
  <nav class="navbar">
    <img src="/Logo_Transparent_Grading.png" alt="Logo Transparent Grading" class="logo">
    <router-link to="/klassen" class="nav-item" active-class="active">
      <svg class="svg_nav" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M10.05 2.53004L4.03002 6.46004C2.10002 7.72004 2.10002 10.54 4.03002 11.8L10.05 15.73C11.13 16.44 12.91 16.44 13.99 15.73L19.98 11.8C21.9 10.54 21.9 7.73004 19.98 6.47004L13.99 2.54004C12.91 1.82004 11.13 1.82004 10.05 2.53004Z"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M5.63012 13.08L5.62012 17.77C5.62012 19.04 6.60012 20.4 7.80012 20.8L10.9901 21.86C11.5401 22.04 12.4501 22.04 13.0101 21.86L16.2001 20.8C17.4001 20.4 18.3801 19.04 18.3801 17.77V13.13"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M21.3999 15V9" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      Klassen
    </router-link>
    <router-link to="/schueler" class="nav-item" active-class="active">
      <svg class="svg_nav" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M9.16006 10.87C9.06006 10.86 8.94006 10.86 8.83006 10.87C6.45006 10.79 4.56006 8.84 4.56006 6.44C4.56006 3.99 6.54006 2 9.00006 2C11.4501 2 13.4401 3.99 13.4401 6.44C13.4301 8.84 11.5401 10.79 9.16006 10.87Z"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M16.41 4C18.35 4 19.91 5.57 19.91 7.5C19.91 9.39 18.41 10.93 16.54 11C16.46 10.99 16.37 10.99 16.28 11"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M4.15997 14.56C1.73997 16.18 1.73997 18.82 4.15997 20.43C6.90997 22.27 11.42 22.27 14.17 20.43C16.59 18.81 16.59 16.17 14.17 14.56C11.43 12.73 6.91997 12.73 4.15997 14.56Z"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M18.3401 20C19.0601 19.85 19.7401 19.56 20.3001 19.13C21.8601 17.96 21.8601 16.03 20.3001 14.86C19.7501 14.44 19.0801 14.16 18.3701 14"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      Schüler*innen
    </router-link>
    <router-link to="/lehrer" class="nav-item" active-class="active">
      <svg class="svg_nav" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M9.16006 10.87C9.06006 10.86 8.94006 10.86 8.83006 10.87C6.45006 10.79 4.56006 8.84 4.56006 6.44C4.56006 3.99 6.54006 2 9.00006 2C11.4501 2 13.4401 3.99 13.4401 6.44C13.4301 8.84 11.5401 10.79 9.16006 10.87Z"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M16.41 4C18.35 4 19.91 5.57 19.91 7.5C19.91 9.39 18.41 10.93 16.54 11C16.46 10.99 16.37 10.99 16.28 11"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M4.15997 14.56C1.73997 16.18 1.73997 18.82 4.15997 20.43C6.90997 22.27 11.42 22.27 14.17 20.43C16.59 18.81 16.59 16.17 14.17 14.56C11.43 12.73 6.91997 12.73 4.15997 14.56Z"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M18.3401 20C19.0601 19.85 19.7401 19.56 20.3001 19.13C21.8601 17.96 21.8601 16.03 20.3001 14.86C19.7501 14.44 19.0801 14.16 18.3701 14"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      Lehrer*innen
    </router-link>
    <router-link to="/einstellungen" class="nav-item" active-class="active">
      <svg class="svg_nav" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z"
          stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M2 12.88V11.12C2 10.08 2.85 9.22 3.9 9.22C5.71 9.22 6.45 7.94 5.54 6.37C5.02 5.47 5.33 4.3 6.24 3.78L7.97 2.79C8.76 2.32 9.78 2.6 10.25 3.39L10.36 3.58C11.26 5.15 12.74 5.15 13.65 3.58L13.76 3.39C14.23 2.6 15.25 2.32 16.04 2.79L17.77 3.78C18.68 4.3 18.99 5.47 18.47 6.37C17.56 7.94 18.3 9.22 20.11 9.22C21.15 9.22 22.01 10.07 22.01 11.12V12.88C22.01 13.92 21.16 14.78 20.11 14.78C18.3 14.78 17.56 16.06 18.47 17.63C18.99 18.54 18.68 19.7 17.77 20.22L16.04 21.21C15.25 21.68 14.23 21.4 13.76 20.61L13.65 20.42C12.75 18.85 11.27 18.85 10.36 20.42L10.25 20.61C9.78 21.4 8.76 21.68 7.97 21.21L6.24 20.22C5.33 19.7 5.02 18.53 5.54 17.63C6.45 16.06 5.71 14.78 3.9 14.78C2.85 14.78 2 13.92 2 12.88Z"
          stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      Einstellungen
    </router-link>
    <router-link to="/hilfe" class="nav-item" active-class="active">
      <svg class="svg_nav" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M10.4899 2.23L5.49991 4.11C4.34991 4.54 3.40991 5.9 3.40991 7.12V14.55C3.40991 15.73 4.18991 17.28 5.13991 17.99L9.43991 21.2C10.8499 22.26 13.1699 22.26 14.5799 21.2L18.8799 17.99C19.8299 17.28 20.6099 15.73 20.6099 14.55V7.12C20.6099 5.89 19.6699 4.53 18.5199 4.1L13.5299 2.23C12.6799 1.92 11.3199 1.92 10.4899 2.23Z"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M9.05005 11.87L10.66 13.48L14.96 9.17999" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"
          stroke-linejoin="round" />
      </svg>
      Hilfe / Datenschutz
    </router-link>
    <router-link to="/logout" class="nav-item" active-class="active">
      <svg class="svg_nav" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M8.8999 7.55999C9.2099 3.95999 11.0599 2.48999 15.1099 2.48999H15.2399C19.7099 2.48999 21.4999 4.27999 21.4999 8.74999V15.27C21.4999 19.74 19.7099 21.53 15.2399 21.53H15.1099C11.0899 21.53 9.2399 20.08 8.9099 16.54"
          stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M15.0001 12H3.62012" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"
          stroke-linejoin="round" />
        <path d="M5.85 8.65002L2.5 12L5.85 15.35" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"
          stroke-linejoin="round" />
      </svg>

      Logout
    </router-link>
  </nav>


</template>

<style scoped>
.svg_nav {
  margin-right: 25px;
  margin-left: 25px;
}

.svg_nav path {
  stroke: var(--icon-color);
}

.logo {
  width: 200px;
  align-self: center;
  margin-bottom: 25px;
}

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

.nav-item {
  width: 90%;
  padding-top: 20px;
  padding-bottom: 20px;
  border-radius: 6px;
  text-decoration: none;
  color: var(--text);
  text-align: left;
  display: flex;
  margin: 3vh 0;
  display: flex;
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
