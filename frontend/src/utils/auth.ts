// Thin compatibility layer: re-export helpers from the auth store
export { useAuthStore } from '@/stores/auth'

import { useAuthStore as __useAuthStore } from '@/stores/auth'

// Legacy-compatible wrapper (keeps the same shape as previous utils/useAuth)
export const useAuth = () => {
  const s = __useAuthStore()
  return {
    tokenRef: s.tokenRef,
    roleRef: s.roleRef,
    loggedIn: s.loggedIn,
    roles: s.roles,
    isLoggedIn: s.isLoggedIn,
    isRole: s.isRole,
    hasAnyRole: s.hasAnyRole,
    hasAllRoles: s.hasAllRoles,
  }
}

// Backwards-compatible pure functions (useful for modules outside setup context)
export function getToken(): string | null {
  return localStorage.getItem('token')
}
export function getUserRole(): string | null {
  return localStorage.getItem('userRole')
}
export function isLoggedIn(): boolean {
  return !!getToken()
}
export function isRole(role: string): boolean {
  const raw = getUserRole()
  if (!raw) return false
  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) return parsed.map(String).includes(role)
  } catch {}
  return String(raw) === role
}
export function hasAnyRole(list: string[]): boolean {
  if (!list?.length) return false
  const raw = getUserRole()
  if (!raw) return false
  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) return list.some(r => parsed.map(String).includes(r))
  } catch {}
  return list.includes(String(raw))
}
export function hasAllRoles(list: string[]): boolean {
  if (!list?.length) return false
  const raw = getUserRole()
  if (!raw) return false
  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) return list.every(r => parsed.map(String).includes(r))
  } catch {}
  return list.every(r => String(raw) === r)
}
