import { createRouter, createWebHistory } from 'vue-router'

const routes = [
// --- ADMIN PANEL ---
{ path: '/admin/klassen', component: () => import('../views/admin/KlassenView.vue'), meta: { navbar: 'admin' } },
{ path: '/admin/schueler', component: () => import('../views/admin/SchuelerView.vue'), meta: { navbar: 'admin' } },
{ path: '/admin/lehrer', component: () => import('../views/admin/LehrerView.vue'), meta: { navbar: 'admin' } },
{ path: '/admin/einstellungen', component: () => import('../views/admin/EinstellungenView.vue'), meta: { navbar: 'admin' } },
{ path: '/admin/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'admin' } },

// --- LEHRER PANEL ---
{ path: '/lehrer/faecher', component: () => import('../views/lehrer/FaecherView.vue'), meta: { navbar: 'teacher' } },
{ path: '/lehrer/fach/:id', component: () => import('../views/lehrer/FachDetailView.vue'), meta: { navbar: 'teacher' } },
{ path: '/lehrer/leistungserfassung', redirect: '/lehrer/faecher', meta: { navbar: 'teacher' } },
{ path: '/lehrer/einstellungen', component: () => import('../views/lehrer/EinstellungenView.vue'), meta: { navbar: 'teacher' } },
{ path: '/lehrer/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'teacher' } },

// --- SCHÜLER PANEL ---
{ path: '/schueler/benachrichtigungen', component: () => import('../views/schueler/NotificationsView.vue'), meta: { navbar: 'student' }},
{ path: '/schueler/faecher', component: () => import('../views/schueler/MeineFaecherView.vue'), meta: { navbar: 'student' }},
{ path: '/schueler/faecher/:id', component: () => import('../views/schueler/FachDetailView.vue'),meta: { navbar: 'student' }},
{ path: '/schueler/benachrichtigungen', component: () => import('../views/schueler/BenachrichtigungenView.vue'), meta: { navbar: 'student' } },
{ path: '/schueler/moodboard', component: () => import('../views/schueler/MoodboardView.vue'), meta: { navbar: 'student' } },
{ path: '/schueler/einstellungen', component: () => import('../views/schueler/EinstellungenView.vue'), meta: { navbar: 'student' } },
{ path: '/schueler/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'student' } },

// --- LOGIN ---
{ path: '/login', component: () => import('../views/LoginView.vue'), meta: { navbar: 'none' } },
{ path: '/logout', redirect: '/login', meta: { navbar: 'none' } },

  // --- 404 FALLBACK ---
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]

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
  const role = getRoleFromToken()

  // Kein Login?
if (!token && to.path !== "/login" && !to.path.startsWith("/auth")) {
    return next("/login")
}

  // Kein Zugriff mit falscher Rolle?
if (to.meta.role && role !== 'Schueler' && to.meta.role !== role) {
  return next("/login")
}

  next()
})

export default router
