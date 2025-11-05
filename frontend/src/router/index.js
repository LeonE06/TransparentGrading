import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // --- ADMIN PANEL ---
  { path: '/', redirect: '/login' },
  { path: '/klassen', component: () => import('../views/KlassenView.vue') },
  { path: '/schueler', component: () => import('../views/SchuelerView.vue') },
  { path: '/lehrer', component: () => import('../views/LehrerView.vue') },
  { path: '/einstellungen', component: () => import('../views/EinstellungenView.vue') },
  { path: '/hilfe', component: () => import('../views/HilfeView.vue') },

  // --- SCHÜLER PANEL ---
  { path: '/schueler/Faecher', component: () => import('../views/schueler/FaecherView.vue'), meta: { layout: 'student' } },
  { path: '/schueler/benachrichtigungen', component: () => import('../views/schueler/BenachrichtigungenView.vue'), meta: { layout: 'student' } },
  { path: '/schueler/moodboard', component: () => import('../views/schueler/MoodboardView.vue'), meta: { layout: 'student' } },
  { path: '/schueler/einstellungen', component: () => import('../views/schueler/EinstellungenView.vue'), meta: { layout: 'student' } },
  { path: '/schueler/hilfe', component: () => import('../views/schueler/HilfeView.vue'), meta: { layout: 'student' } },

  // --- LOGIN / LOGOUT ---
  { path: '/login', component: () => import('../views/LoginView.vue') },
  { path: '/logout', component: () => import('../views/LoginView.vue') },

  // --- 404 FALLBACK ---
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
