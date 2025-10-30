import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'   // ✅ Router importieren

import axios from 'axios'

// ⚙️ Axios-Standard-URL setzen (z. B. für dein Backend)
axios.defaults.baseURL = 'http://backend:8000' // bei Docker passt das so

// 🚀 App starten + Router aktivieren
createApp(App)
  .use(router) // ✅ Router aktivieren
  .mount('#app')
