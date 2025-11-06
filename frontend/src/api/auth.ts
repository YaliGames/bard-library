import { http } from './http'
import type { User } from './types'
import { useAuthStore } from '@/stores/auth'

// 类型定义
export interface LoginResponse {
  token: string
  user: User
}

export interface ChangePasswordPayload {
  current_password: string
  new_password: string
}

export interface ResetPasswordPayload {
  email: string
  password: string
  token: string
}

export interface UpdateMePayload extends Partial<User> {
  location?: string
  website?: string
  bio?: string
}

// API 对象
export const authApi = {
  /**
   * 用户登录
   * @param email - 邮箱地址
   * @param password - 密码
   * @returns 登录响应(包含 token 和用户信息)
   */
  login: async (email: string, password: string) => {
    const res = await http.post<LoginResponse>('/api/v1/auth/login', { email, password })

    const authStore = useAuthStore()
    authStore.setToken(res.token)
    authStore.setUser(res.user)

    const role = (res.user as any)?.role || null
    authStore.setUserRole(role)

    return res
  },

  /**
   * 用户注册
   * 注意:后端不返回 token,而是发送验证邮件
   * @param name - 用户名
   * @param email - 邮箱地址
   * @param password - 密码
   * @returns 操作结果
   */
  register: (name: string, email: string, password: string) => {
    return http.post<{ success: boolean }>('/api/v1/auth/register', { name, email, password })
  },

  /**
   * 用户登出
   * 即使后端请求失败也会清除本地状态
   */
  logout: async () => {
    try {
      await http.post<void>('/api/v1/auth/logout')
    } finally {
      const authStore = useAuthStore()
      authStore.logout()
    }
  },

  /**
   * 获取当前登录用户信息
   * @returns 用户信息
   */
  me: () => {
    return http.get<User>('/api/v1/me')
  },

  /**
   * 更新当前用户信息
   * @param payload - 要更新的字段
   * @returns 更新后的用户信息
   */
  updateMe: (payload: UpdateMePayload) => {
    return http.patch<User>('/api/v1/me', payload)
  },

  /**
   * 修改密码
   * @param payload - 包含当前密码和新密码
   * @returns 操作结果
   */
  changePassword: (payload: ChangePasswordPayload) => {
    return http.post<{ success: boolean }>('/api/v1/me/change-password', payload)
  },

  /**
   * 请求删除账户
   * @param reason - 删除原因(可选)
   * @returns 操作结果
   */
  requestDelete: (reason?: string) => {
    return http.post<{ success: boolean }>('/api/v1/me/request-delete', { reason })
  },

  /**
   * 忘记密码 - 发送重置邮件
   * @param email - 邮箱地址
   * @returns 操作结果
   */
  forgotPassword: (email: string) => {
    return http.post<{ success: boolean }>('/api/v1/auth/forgot-password', { email })
  },

  /**
   * 重置密码
   * @param payload - 包含邮箱、新密码和重置 token
   * @returns 操作结果
   */
  resetPassword: (payload: ResetPasswordPayload) => {
    return http.post<{ success: boolean }>('/api/v1/auth/reset-password', payload)
  },

  /**
   * 重新发送验证邮件
   * @param email - 邮箱地址
   * @returns 操作结果
   */
  resendVerification: (email: string) => {
    return http.post<{ success: boolean }>('/api/v1/auth/resend-verification', { email })
  },
}
