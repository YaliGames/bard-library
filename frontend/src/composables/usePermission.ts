import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import type { Permission } from '@/api/types'

export function usePermission() {
  const authStore = useAuthStore()

  const user = computed(() => authStore.user)

  /**
   * 检查用户是否有某个权限
   */
  const hasPermission = (permission: string): boolean => {
    if (!user.value || !user.value.roles) return false

    return user.value.roles.some(role => {
      if (!role.permissions) return false

      return role.permissions.some((p: Permission) => {
        // 全局权限 *
        if (p.name === '*') return true

        // 精确匹配
        if (p.name === permission) return true

        // 通配符匹配: books.* 匹配 books.create
        if (p.name.includes('*')) {
          const pattern = p.name.replace(/\*/g, '.*')
          const regex = new RegExp(`^${pattern}$`)
          return regex.test(permission)
        }

        return false
      })
    })
  }

  /**
   * 检查用户是否有任一权限
   */
  const hasAnyPermission = (permissions: string[]): boolean => {
    return permissions.some(p => hasPermission(p))
  }

  /**
   * 检查用户是否有所有权限
   */
  const hasAllPermissions = (permissions: string[]): boolean => {
    return permissions.every(p => hasPermission(p))
  }

  /**
   * 检查用户是否有某个角色
   */
  const hasRole = (role: string | string[]): boolean => {
    if (!user.value || !user.value.roles) return false

    const roles = Array.isArray(role) ? role : [role]
    return user.value.roles.some(r => roles.includes(r.name))
  }

  /**
   * 获取用户的最高角色优先级
   */
  const getHighestPriority = (): number => {
    if (!user.value || !user.value.roles || user.value.roles.length === 0) return 0
    return Math.max(...user.value.roles.map(r => r.priority))
  }

  // 别名方法
  const can = hasPermission
  const canAny = hasAnyPermission
  const canAll = hasAllPermissions
  const is = hasRole

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    getHighestPriority,
    can,
    canAny,
    canAll,
    is,
  }
}
