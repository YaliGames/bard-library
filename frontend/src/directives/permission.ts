import type { Directive } from 'vue'
import { usePermission } from '@/composables/usePermission'

/**
 * v-permission 指令
 * 用法: v-permission="'books.create'"
 * 如果用户没有该权限,元素将被隐藏
 */
export const vPermission: Directive = {
  mounted(el, binding) {
    const { hasPermission } = usePermission()
    const permission = binding.value

    if (!permission) {
      console.warn('v-permission: permission value is required')
      return
    }

    if (!hasPermission(permission)) {
      // 移除元素
      el.parentNode?.removeChild(el)
    }
  },
}

/**
 * v-role 指令
 * 用法: v-role="'admin'" 或 v-role="['admin', 'editor']"
 * 如果用户没有该角色,元素将被隐藏
 */
export const vRole: Directive = {
  mounted(el, binding) {
    const { hasRole } = usePermission()
    const role = binding.value

    if (!role) {
      console.warn('v-role: role value is required')
      return
    }

    if (!hasRole(role)) {
      // 移除元素
      el.parentNode?.removeChild(el)
    }
  },
}

/**
 * v-any-permission 指令
 * 用法: v-any-permission="['books.create', 'books.edit']"
 * 如果用户没有任一权限,元素将被隐藏
 */
export const vAnyPermission: Directive = {
  mounted(el, binding) {
    const { hasAnyPermission } = usePermission()
    const permissions = binding.value

    if (!Array.isArray(permissions)) {
      console.warn('v-any-permission: permissions must be an array')
      return
    }

    if (!hasAnyPermission(permissions)) {
      // 移除元素
      el.parentNode?.removeChild(el)
    }
  },
}
