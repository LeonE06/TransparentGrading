import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import { useTheme } from './composables/useTheme.js'

// 🔧 Backend automatisch richtig wählen (lokal vs. Vercel)
axios.defaults.baseURL = import.meta.env.PROD
  ? 'https://transparentgrading.onrender.com/' // dein echtes Render-Backend hier eintragen!
  : '/api'


axios.defaults.withCredentials = true
const token = localStorage.getItem('token')
if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

// 🚀 App starten + Router aktivieren
createApp(App)
  .use(router)
  .mount('#app')

// Theme initialisieren (nicht-blockierend)
const { loadFromServer } = useTheme()
loadFromServer()