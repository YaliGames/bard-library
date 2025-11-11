import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/api/types'

const STORAGE_KEYS = {
  USER_ROLE: 'userRole',
  USER_DATA: 'userData',
  USER_PERMISSIONS: 'userPermissions',
} as const

function parseRoles(raw: string | null): string[] {
  if (!raw) return []
  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) return parsed.map(String)
  } catch {}
  return raw ? [String(raw)] : []
}

function loadCachedUser(): User | null {
  try {
    const cached = localStorage.getItem(STORAGE_KEYS.USER_DATA)
    if (cached) {
      return JSON.parse(cached)
    }
  } catch {}
  return null
}

function loadCachedPermissions(): string[] {
  try {
    const cached = localStorage.getItem(STORAGE_KEYS.USER_PERMISSIONS)
    if (cached) {
      return JSON.parse(cached)
    }
  } catch {}
  return []
}

export const useAuthStore = defineStore('auth', () => {
  // State - 从缓存中恢复
  const user = ref<User | null>(loadCachedUser())
  const role = ref<string | null>(localStorage.getItem(STORAGE_KEYS.USER_ROLE))
  const permissions = ref<string[]>(loadCachedPermissions())
  const permissionsLoaded = ref<boolean>(!!user.value && permissions.value.length > 0)

  // Getters (computed)
  // Cookie 认证: 如果有用户信息就认为已登录
  const isLoggedIn = computed(() => !!user.value)

  const roles = computed(() => {
    // 从 user.roles 中提取角色名称
    if (!user.value?.roles) return parseRoles(role.value)
    return user.value.roles.map(r => r.name)
  })

  // Actions
  function setUserRole(val: string | null) {
    role.value = val
    if (val) {
      localStorage.setItem(STORAGE_KEYS.USER_ROLE, val)
    } else {
      localStorage.removeItem(STORAGE_KEYS.USER_ROLE)
    }
  }

  function setUser(val: User | null) {
    console.log('Setting user in auth store:', val)
    user.value = val

    // 缓存用户数据
    if (val) {
      localStorage.setItem(STORAGE_KEYS.USER_DATA, JSON.stringify(val))
    } else {
      localStorage.removeItem(STORAGE_KEYS.USER_DATA)
    }

    // 自动提取并设置权限
    if (val?.roles) {
      const allPermissions = new Set<string>()
      val.roles.forEach(role => {
        role.permissions?.forEach(perm => {
          allPermissions.add(perm.name)
        })
      })
      permissions.value = Array.from(allPermissions)
      permissionsLoaded.value = true

      // 缓存权限数据
      localStorage.setItem(STORAGE_KEYS.USER_PERMISSIONS, JSON.stringify(permissions.value))

      // 设置角色(用于向后兼容)
      const roleNames = val.roles.map(r => r.name)
      if (roleNames.includes('admin')) {
        setUserRole('admin')
      } else if (roleNames.length > 0) {
        setUserRole(JSON.stringify(roleNames))
      }
    } else {
      permissions.value = []
      permissionsLoaded.value = false
      localStorage.removeItem(STORAGE_KEYS.USER_PERMISSIONS)
    }
  }

  function logout() {
    user.value = null
    role.value = null
    permissions.value = []
    permissionsLoaded.value = false
    localStorage.removeItem(STORAGE_KEYS.USER_ROLE)
    localStorage.removeItem(STORAGE_KEYS.USER_DATA)
    localStorage.removeItem(STORAGE_KEYS.USER_PERMISSIONS)
  }

  function hasPermission(permission: string): boolean {
    return permissions.value.includes(permission)
  }

  function hasAnyPermission(permissionList: string[]): boolean {
    return permissionList.some(p => permissions.value.includes(p))
  }

  function hasAllPermissions(permissionList: string[]): boolean {
    return permissionList.every(p => permissions.value.includes(p))
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
      if (e.key === STORAGE_KEYS.USER_ROLE) {
        role.value = e.newValue
      }
    })
  }

  return {
    // State
    user,
    role,
    permissions,
    permissionsLoaded,
    // Getters
    isLoggedIn,
    roles,
    // Actions
    setUserRole,
    setUser,
    logout,
    hasAnyRole,
    hasAllRoles,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
  }
})
