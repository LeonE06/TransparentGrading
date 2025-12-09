import { createRouter, createWebHistory } from "vue-router"

function getRoleFromToken() {
  const token = localStorage.getItem("token")
  if (!token) return null

  const payload = JSON.parse(atob(token.split(".")[1]))
  return payload.role
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
/*
    // LEHRER
    { path: '/lehrer/faecher', component: () => import('../views/lehrer/FaecherView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/einstellungen', component: () => import('../views/lehrer/EinstellungenView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
*/

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
  const role = getRoleFromToken()

  // Kein Login?
if (!token && to.path !== "/login" && !to.path.startsWith("/auth")) {
    return next("/login")
}

  // Kein Zugriff mit falscher Rolle?
  if (to.meta.role && to.meta.role !== role) {
    return next("/login")
  }

  next()
})

export default router
