import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/api/types'

const STORAGE_KEYS = {
  TOKEN: 'token',
  USER_ROLE: 'userRole',
} as const

function parseRoles(raw: string | null): string[] {
  if (!raw) return []
  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) return parsed.map(String)
  } catch {}
  return raw ? [String(raw)] : []
}

export const useAuthStore = defineStore('auth', () => {
  // State
  const token = ref<string | null>(localStorage.getItem(STORAGE_KEYS.TOKEN))
  const user = ref<User | null>(null)
  const role = ref<string | null>(localStorage.getItem(STORAGE_KEYS.USER_ROLE))

  // Getters (computed)
  const isLoggedIn = computed(() => !!token.value)
  const isAdmin = computed(() => role.value === 'admin')
  const roles = computed(() => parseRoles(role.value))

  // Actions
  function setToken(val: string | null) {
    token.value = val
    if (val) {
      localStorage.setItem(STORAGE_KEYS.TOKEN, val)
    } else {
      localStorage.removeItem(STORAGE_KEYS.TOKEN)
    }
  }

  function setUserRole(val: string | null) {
    role.value = val
    if (val) {
      localStorage.setItem(STORAGE_KEYS.USER_ROLE, val)
    } else {
      localStorage.removeItem(STORAGE_KEYS.USER_ROLE)
    }
  }

  function setUser(val: User | null) {
    user.value = val
  }

  function logout() {
    token.value = null
    user.value = null
    role.value = null
    localStorage.removeItem(STORAGE_KEYS.TOKEN)
    localStorage.removeItem(STORAGE_KEYS.USER_ROLE)
  }

  function isRole(roleName: string): boolean {
    return roles.value.includes(roleName)
  }

  function hasAnyRole(roleList: string[]): boolean {
    return roleList.some(r => roles.value.includes(r))
  }

  function hasAllRoles(roleList: string[]): boolean {
    return roleList.every(r => roles.value.includes(r))
  }

  // 监听 storage 事件（跨标签页同步）
  if (typeof window !== 'undefined') {
    window.addEventListener('storage', e => {
      if (e.key === STORAGE_KEYS.TOKEN) {
        token.value = e.newValue
      }
      if (e.key === STORAGE_KEYS.USER_ROLE) {
        role.value = e.newValue
      }
    })
  }

  return {
    // State
    token,
    user,
    role,
    // Getters
    isLoggedIn,
    isAdmin,
    roles,
    // Actions
    setToken,
    setUserRole,
    setUser,
    logout,
    isRole,
    hasAnyRole,
    hasAllRoles,
  }
})
