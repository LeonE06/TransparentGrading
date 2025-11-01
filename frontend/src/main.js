import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import axios from 'axios'

// 🔧 Backend automatisch richtig wählen (lokal vs. Vercel)
axios.defaults.baseURL = import.meta.env.PROD
  ? 'https://transparentgrading.onrender.com/' // dein echtes Render-Backend hier eintragen!
  : '/api'

// 🚀 App starten + Router aktivieren
createApp(App)
  .use(router)
  .mount('#app')
