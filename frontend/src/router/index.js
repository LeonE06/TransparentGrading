import { createRouter, createWebHistory } from 'vue-router'

function getCookie(name) {
  const cookieMatch = document.cookie.match(
    new RegExp('(^| )' + name + '=([^;]+)')
  )
  return cookieMatch ? cookieMatch[2] : null
}

function getRoleFromToken() {
  const token = getCookie('auth_token')
  if (!token) return null

  const payload = JSON.parse(atob(token.split('.')[1]))
  return payload.role
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // --- ADMIN PANEL ---
    { path: '/admin/klassen', component: () => import('../views/admin/KlassenView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/schueler', component: () => import('../views/admin/SchuelerView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/lehrer', component: () => import('../views/admin/LehrerView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/einstellungen', component: () => import('../views/admin/EinstellungenView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'admin', role: 'Admin' }},

    // --- SCHÜLER PANEL ---
    { path: '/schueler/faecher', component: () => import('../views/schueler/FaecherView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/benachrichtigungen', component: () => import('../views/schueler/BenachrichtigungenView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/moodboard', component: () => import('../views/schueler/MoodboardView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/einstellungen', component: () => import('../views/schueler/EinstellungenView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'student', role: 'Schueler' }},

    // --- LOGIN ---
    { path: '/login', component: () => import('../views/LoginView.vue'), meta: { navbar: 'none' }},
    { path: '/logout', name: 'logout', component: () => import('../views/LogoutView.vue'), meta: { navbar: 'none' }},

    // === AUTH CALL BACK ROUTE ===
    { path: '/auth/callback', component: () => import('../views/AuthCallback.vue'), meta: { navbar: 'none' }},

    // --- 404 ---
    { path: '/:pathMatch(.*)*', redirect: '/login' },
  ],
})

// Route Guard
router.beforeEach((to, from, next) => {
  const token = getCookie("auth_token")
  const role = getRoleFromToken()

  // Keine Rolle → kein Login
  if (!token && to.path !== '/login' && to.path !== '/auth/callback') {
    return next('/login')
  }

  // Rollenprüfung
  if (to.meta.role && to.meta.role !== role) {
    return next('/login') // Zugriff verweigert → Zurück zum Login
  }

  next()
})

export default router
