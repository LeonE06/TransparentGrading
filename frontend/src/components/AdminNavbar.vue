<template>
  <nav class="navbar">
    <router-link to="/klassen" class="nav-item" active-class="active">Klassen</router-link>
    <router-link to="/schueler" class="nav-item" active-class="active">Schüler*innen</router-link>
    <router-link to="/lehrer" class="nav-item" active-class="active">Lehrer*innen</router-link>
    <router-link to="/einstellungen" class="nav-item" active-class="active">Einstellungen</router-link>
    <router-link to="/hilfe" class="nav-item" active-class="active">Hilfe / Datenschutz</router-link>
    <router-link to="/logout" class="nav-item" active-class="active">Logout</router-link>
  </nav>
</template>

<style scoped>
.navbar {
  position: fixed;        /* Bleibt immer am linken Rand */
  left: 0;
  top: 0;
  width: 220px;           /* Breite der Sidebar */
  height: 100vh;          /* Ganze Höhe */
  background-color: #f9f9f9;
  display: flex;
  flex-direction: column; /* Elemente untereinander */
  align-items: flex-start;
  padding: 1rem;
  border-right: #333 1px solid;
 
}

.nav-item {
  width: 100%;
  padding-top: 20px;
  padding-bottom: 20px;
  border-radius: 6px;
  text-decoration: none;
  color: #333;

}

.nav-item:hover {
  background-color: #f3f3f3;
}

.active {
  background-color: #e7f0ff;
  border: 1px solid #8cb4ff;
}
</style>
