import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', redirect: '/klassen' },
  { path: '/klassen', component: () => import('../views/KlassenView.vue') },
  { path: '/schueler', component: () => import('../views/SchuelerView.vue') },
  { path: '/lehrer', component: () => import('../views/LehrerView.vue') },
  { path: '/einstellungen', component: () => import('../views/EinstellungenView.vue') },
  { path: '/hilfe', component: () => import('../views/HilfeView.vue') },
  { path: '/logout', component: () => import('../views/LoginView.vue') },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
