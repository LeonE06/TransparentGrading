import { createApp } from 'vue'
import './style.css'
import App from './App.vue'


createApp(App).mount('#app')


import axios from "axios";

axios.defaults.baseURL = "http://backend:8000"; // oder http://backend:8000 bei Docker
