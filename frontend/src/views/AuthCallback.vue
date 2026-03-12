<template>
  <div class="loading">
    <h2>Authentifiziere dich...</h2>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { getRolesFromToken } from '@/services/auth'

const router = useRouter()

const urlParams = new URLSearchParams(window.location.search)
const token = urlParams.get('token')

if (!token) {
  router.push('/login')
} else {
  localStorage.setItem('token', token)
  document.cookie = `auth_token=${token}; Path=/; Secure; SameSite=None`

  const roles = getRolesFromToken()

  if (roles.includes('Admin')) {
    router.push('/admin/klassen')
  } else if (roles.includes('Lehrer')) {
    router.push('/lehrer/faecher')
  } else if (roles.includes('Schueler')) {
    router.push('/schueler/faecher')
  } else {
    router.push('/login')
  }
}
</script>

<style scoped>
.loading {
  margin-top: 100px;
  text-align: center;
  font-size: 22px;
  color: #0078d4;
}
</style>
