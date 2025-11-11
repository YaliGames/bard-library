import { http } from './http'
import type { Role, Permission } from './types'

export const rolesApi = {
  // 获取角色列表
  list: (params?: { search?: string; sort?: string; order?: string }) =>
    http.get<Role[]>('/api/v1/admin/roles', { params }),

  // 获取单个角色
  get: (id: number) => http.get<Role>(`/api/v1/admin/roles/${id}`),

  // 创建角色
  create: (data: { name: string; display_name: string; description?: string; priority?: number }) =>
    http.post<Role>('/api/v1/admin/roles', data),

  // 更新角色
  update: (
    id: number,
    data: {
      name?: string
      display_name?: string
      description?: string
      priority?: number
    },
  ) => http.patch<Role>(`/api/v1/admin/roles/${id}`, data),

  // 删除角色
  delete: (id: number) => http.delete(`/api/v1/admin/roles/${id}`),

  // 同步角色的权限
  syncPermissions: (id: number, permissionIds: number[]) =>
    http.post(`/api/v1/admin/roles/${id}/permissions`, { permission_ids: permissionIds }),

  // 获取角色的权限列表
  getPermissions: (id: number) => http.get<Permission[]>(`/api/v1/admin/roles/${id}/permissions`),
}

export const permissionsApi = {
  // 获取所有权限
  list: () => http.get<Permission[]>('/api/v1/admin/permissions'),

  // 获取按分组归类的权限
  grouped: () => http.get<Record<string, Permission[]>>('/api/v1/admin/permissions/grouped'),

  // 获取所有分组
  groups: () => http.get<string[]>('/api/v1/admin/permissions/groups'),
}
