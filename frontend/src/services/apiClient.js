import axios from 'axios'

function resolveBaseURL() {
  let base =
    import.meta.env.VITE_API_BASE_URL ||
    import.meta.env.VITE_API_URL ||
    (import.meta.env.DEV ? '/api' : 'https://transparentgrading.onrender.com')

  
  if (!import.meta.env.DEV) {
    base = base.replace(/\/$/, '')
    if (!base.endsWith('/api')) base += '/api'
  }

  return base
}

export const apiClient = axios.create({
  baseURL: resolveBaseURL(),
  withCredentials: true
})

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers = config.headers || {}
    config.headers.Authorization = `Bearer ${token}`
  }
  return config

})

