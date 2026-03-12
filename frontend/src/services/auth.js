function decodeTokenPayload(token) {
  if (!token) return null

  try {
    const base64Url = token.split('.')[1]
    if (!base64Url) return null

    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/')
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split('')
        .map((char) => `%${(`00${char.charCodeAt(0).toString(16)}`).slice(-2)}`)
        .join('')
    )

    return JSON.parse(jsonPayload)
  } catch (error) {
    console.warn('Token konnte nicht gelesen werden:', error)
    return null
  }
}

export function getTokenPayload() {
  const token = localStorage.getItem('token')
  return decodeTokenPayload(token)
}

export function getRolesFromToken() {
  const payload = getTokenPayload()
  if (!payload) return []

  if (Array.isArray(payload.roles)) {
    return payload.roles
  }

  if (payload.role) {
    return [payload.role]
  }

  const symfonyRoles = payload.authorities || payload.roleS
  if (Array.isArray(symfonyRoles)) {
    const roles = []
    if (symfonyRoles.includes('ROLE_ADMIN')) roles.push('Admin')
    if (symfonyRoles.includes('ROLE_LEHRER')) roles.push('Lehrer')
    if (symfonyRoles.includes('ROLE_SCHUELER')) roles.push('Schueler')
    return roles
  }

  return []
}

export function hasRole(role) {
  return getRolesFromToken().includes(role)
}

export function getPrimaryRole() {
  const roles = getRolesFromToken()

  if (roles.includes('Schueler')) return 'Schueler'
  if (roles.includes('Lehrer')) return 'Lehrer'
  if (roles.includes('Admin')) return 'Admin'

  return null
}
