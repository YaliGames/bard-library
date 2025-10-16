import { reactive, ref, computed, onMounted, onBeforeUnmount } from 'vue'
import type { User } from '@/api/types'

const state = reactive<{ user: User | null }>({ user: null })

const TOKEN_KEY = 'token'
const ROLE_KEY = 'userRole'

function parseRoles(raw: string | null): string[] {
  if (!raw) return []
  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) return parsed.map(String)
  } catch {}
  return raw ? [String(raw)] : []
}

export function getToken(): string | null { return localStorage.getItem(TOKEN_KEY) }
export function getUserRole(): string | null { return localStorage.getItem(ROLE_KEY) }
export function isLoggedIn(): boolean { return !!getToken() }

// Module-level reactive refs so non-setup code can update auth state immediately
export const tokenRef = ref<string | null>(getToken())
export const roleRef = ref<string | null>(getUserRole())

export function logoutLocal() {
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(ROLE_KEY)
  tokenRef.value = null
  roleRef.value = null
  state.user = null
}
export function isRole(role: string): boolean {
  const raw = getUserRole()
  const list = parseRoles(raw)
  return list.includes(role)
}

export function useAuthStore() {
  // basic store
  function setUser(u: User | null) { state.user = u }

  const onStorage = (e: StorageEvent) => {
    if (e.key === TOKEN_KEY) tokenRef.value = getToken()
    if (e.key === ROLE_KEY) roleRef.value = getUserRole()
  }

  onMounted(() => window.addEventListener('storage', onStorage))
  onBeforeUnmount(() => window.removeEventListener('storage', onStorage))
  const loggedIn = computed(() => !!tokenRef.value)
  const roles = computed(() => parseRoles(roleRef.value))

  return {
    state,
    setUser,
    // token/role helpers
    tokenRef,
    roleRef,
    loggedIn,
    roles,
    getToken,
    getUserRole,
    setToken,
    setUserRole,
    logoutLocal,
    isLoggedIn: () => !!tokenRef.value,
    isRole: (role: string) => roles.value.includes(role),
    hasAnyRole: (list: string[]) => list.some((r) => roles.value.includes(r)),
    hasAllRoles: (list: string[]) => list.every((r) => roles.value.includes(r)),
  }
}

export function setToken(value: string | null) {
  if (value == null) localStorage.removeItem(TOKEN_KEY)
  else localStorage.setItem(TOKEN_KEY, value)
  tokenRef.value = value
}

export function setUserRole(role: string | null) {
  if (role == null) localStorage.removeItem(ROLE_KEY)
  else localStorage.setItem(ROLE_KEY, role)
  roleRef.value = role
}
