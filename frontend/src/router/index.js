import { createRouter, createWebHistory } from "vue-router"

function getRoleFromToken() {
  const token = localStorage.getItem("token");
  if (!token) return null;

  try {
    const base64Url = token.split(".")[1];
    if (!base64Url) return null;

    // base64url -> base64
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split("")
        .map(c => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
        .join("")
    );

    const payload = JSON.parse(jsonPayload);

    // Role direkt
    if (payload.role) return payload.role;

    // Wenn Rolle als Array
    const roles = payload.roles || payload.roleS || payload.authorities;
    if (Array.isArray(roles)) {
      if (roles.includes("ROLE_ADMIN")) return "Admin";
      if (roles.includes("ROLE_LEHRER")) return "Lehrer";
      if (roles.includes("ROLE_SCHUELER")) return "Schueler";
    }

    return null;
  } catch (e) {
    console.warn("Token konnte nicht gelesen werden:", e);
    return null;
  }
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ADMIN
    { path: '/admin/klassen', component: () => import('../views/admin/KlassenView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/schueler', component: () => import('../views/admin/SchuelerView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/lehrer', component: () => import('../views/admin/LehrerView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/einstellungen', component: () => import('../views/admin/EinstellungenView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'admin', role: 'Admin' }},

   // LEHRER
    { path: '/lehrer/faecher', component: () => import('../views/lehrer/FaecherView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    // LEHRER
    { path: '/lehrer/faecher', component: () => import('../views/lehrer/FaecherView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},

    // Detail: beide Varianten erlauben
    { path: '/lehrer/faecher/:id', component: () => import('../views/lehrer/FachDetailView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/fach/:id', redirect: to => `/lehrer/faecher/${to.params.id}` },
    { path: '/lehrer/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/einstellungen', component: () => import('../views/lehrer/EinstellungenView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/leistungsfeststellungen/:id', component: () => import('../views/lehrer/LeistungsfeststellungDetailView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/moodboard', name: 'LehrerMoodboard', component: () => import('@/views/lehrer/LehrerMoodboard.vue')},

    // SCHÜLER
    { path: '/schueler/faecher', component: () => import('../views/schueler/MeineFaecherView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/faecher/:id', component: () => import('../views/schueler/FachDetailView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/benachrichtigungen', component: () => import('../views/schueler/NotificationsView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/moodboard', component: () => import('../views/schueler/MoodboardView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/einstellungen', component: () => import('../views/schueler/EinstellungenView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'student', role: 'Schueler' }},

    // LOGIN / LOGOUT / CALLBACK
    { path: '/login', component: () => import('../views/LoginView.vue'), meta: { navbar: 'none' }},
    { path: '/logout', name: 'logout', component: () => import('../views/LogoutView.vue'), meta: { navbar: 'none' }},
    { path: '/auth/callback', component: () => import('../views/AuthCallback.vue'), meta: { navbar: 'none' }},

    // FALLBACK
    { path: '/:pathMatch(.*)*', redirect: '/login' }
  ]
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");
  const role = getRoleFromToken();

  // Token vorhanden aber kaputt/ungültig
  if (token && !role && to.path !== "/login" && !to.path.startsWith("/auth")) {
    localStorage.removeItem("token");
    return next("/login");
  }

  // Nicht eingeloggt?
  if (!token && to.path !== "/login" && !to.path.startsWith("/auth")) {
    return next("/login");
  }

  // Admin-Routen Schutz
  if (to.meta.role === 'Admin' && role !== 'Admin') {
    return next("/login"); // Umleitung zum Login, wenn der Benutzer kein Admin ist
  }

  next();
});


export default router





