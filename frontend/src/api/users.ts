import { http } from './http'
import type { User, Role, Permission, PaginatedResponse } from './types'

export const usersApi = {
  // 获取用户列表(分页)
  list: (params?: {
    search?: string
    role_id?: number
    email_verified?: string
    sort?: string
    order?: string
    page?: number
    per_page?: number
  }) => http.get<PaginatedResponse<User>>('/api/v1/admin/users', { params }),

  // 获取单个用户
  get: (id: number) => http.get<User>(`/api/v1/admin/users/${id}`),

  // 创建用户
  create: (data: { name: string; email: string; password: string; role_ids?: number[] }) =>
    http.post<User>('/api/v1/admin/users', data),

  // 更新用户
  update: (
    id: number,
    data: {
      name?: string
      email?: string
      password?: string
      location?: string
      website?: string
      bio?: string
    },
  ) => http.patch<User>(`/api/v1/admin/users/${id}`, data),

  // 删除用户
  delete: (id: number) => http.delete(`/api/v1/admin/users/${id}`),

  // 同步用户的角色
  syncRoles: (id: number, roleIds: number[]) =>
    http.post(`/api/v1/admin/users/${id}/roles`, { role_ids: roleIds }),

  // 获取用户的角色列表
  getRoles: (id: number) => http.get<Role[]>(`/api/v1/admin/users/${id}/roles`),

  // 获取用户的所有权限
  getPermissions: (id: number) => http.get<Permission[]>(`/api/v1/admin/users/${id}/permissions`),
}
