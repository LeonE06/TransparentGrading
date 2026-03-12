import { createRouter, createWebHistory } from 'vue-router'
import { getRolesFromToken } from '../services/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/admin/klassen', component: () => import('../views/admin/KlassenView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/schueler', component: () => import('../views/admin/SchuelerView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/lehrer', component: () => import('../views/admin/LehrerView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/einstellungen', component: () => import('../views/admin/EinstellungenView.vue'), meta: { navbar: 'admin', role: 'Admin' }},
    { path: '/admin/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'admin', role: 'Admin' }},

    { path: '/lehrer/faecher', component: () => import('../views/lehrer/FaecherView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/faecher/:id', component: () => import('../views/lehrer/FachDetailView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/fach/:id', redirect: to => `/lehrer/faecher/${to.params.id}` },
    { path: '/lehrer/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/einstellungen', component: () => import('../views/lehrer/EinstellungenView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/leistungsfeststellungen/:id', component: () => import('../views/lehrer/LeistungsfeststellungDetailView.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},
    { path: '/lehrer/moodboard', name: 'LehrerMoodboard', component: () => import('@/views/lehrer/LehrerMoodboard.vue'), meta: { navbar: 'teacher', role: 'Lehrer' }},

    { path: '/schueler/faecher', component: () => import('../views/schueler/MeineFaecherView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/profil', component: () => import('../views/schueler/ProfilView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/faecher/:id', component: () => import('../views/schueler/FachDetailView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/benachrichtigungen', component: () => import('../views/schueler/NotificationsView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/moodboard', component: () => import('../views/schueler/MoodboardView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/einstellungen', component: () => import('../views/schueler/EinstellungenView.vue'), meta: { navbar: 'student', role: 'Schueler' }},
    { path: '/schueler/hilfe', component: () => import('../views/HilfeView.vue'), meta: { navbar: 'student', role: 'Schueler' }},

    { path: '/login', component: () => import('../views/LoginView.vue'), meta: { navbar: 'none' }},
    { path: '/logout', name: 'logout', component: () => import('../views/LogoutView.vue'), meta: { navbar: 'none' }},
    { path: '/auth/callback', component: () => import('../views/AuthCallback.vue'), meta: { navbar: 'none' }},

    { path: '/:pathMatch(.*)*', redirect: '/login' }
  ]
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const roles = getRolesFromToken()

  if (token && roles.length === 0 && to.path !== '/login' && !to.path.startsWith('/auth')) {
    localStorage.removeItem('token')
    return next('/login')
  }

  if (!token && to.path !== '/login' && !to.path.startsWith('/auth')) {
    return next('/login')
  }

  if (to.meta.role && !roles.includes(to.meta.role)) {
    return next('/login')
  }

  next()
})

export default router
