import { createRouter, createWebHistory } from "vue-router"

function getRoleFromToken() {
  const token = localStorage.getItem("token")
  if (!token) return null

  try {
    const payload = JSON.parse(atob(token.split(".")[1]))
    return payload.role
  } catch {
    return null
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

    // SCHÜLER
    { path: '/schueler/faecher', component: () => import('../views/schueler/FaecherView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/benachrichtigungen', component: () => import('../views/schueler/BenachrichtigungenView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
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

// Route Guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token")

  // AuthCallback immer zulassen -> Token wird hier erst gespeichert!
  if (to.path.startsWith("/auth/callback")) {
    return next()
  }

// Auth Callback IMMER erlauben – auch mit Query ?token=...
if (to.path.startsWith("/auth/callback")) {
  return next()
}

// Kein Login + kein Zugriff auf Login/Logout?
if (!token && !["/login", "/logout"].includes(to.path)) {
  return next("/login")
}

  // Rolle prüfen NACH Login
  const role = getRoleFromToken()

  if (to.meta.role && to.meta.role !== role) {
    return next("/login")
  }

  next()
})

export default router
