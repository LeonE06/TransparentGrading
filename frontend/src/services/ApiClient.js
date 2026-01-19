import axios from 'axios'

function resolveBaseURL() {
  return (
    import.meta.env.VITE_API_BASE_URL ||
    import.meta.env.VITE_API_URL ||
    (import.meta.env.DEV ? '/api' : 'https://transparentgrading.onrender.com')
  )
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