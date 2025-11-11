import { createRouter, createWebHistory } from 'vue-router'

const routes = [
// --- ADMIN PANEL ---
{ path: '/admin/klassen', component: () => import('../views/KlassenView.vue'), meta: { navbar: 'admin' } },
{ path: '/admin/schueler', component: () => import('../views/SchuelerView.vue'), meta: { navbar: 'admin' } },
{ path: '/admin/lehrer', component: () => import('../views/LehrerView.vue'), meta: { navbar: 'admin' } },
{ path: '/admin/einstellungen', component: () => import('../views/EinstellungenView.vue'), meta: { navbar: 'admin' } },
{ path: '/admin/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'admin' } },

// --- SCHÜLER PANEL ---
{ path: '/schueler/faecher', component: () => import('../views/schueler/FaecherView.vue'), meta: { navbar: 'student' } },
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
  routes,
})

export default router
