import { http } from './http'
import type { User } from './types'
import { setToken, setUserRole, logoutLocal } from '@/stores/auth'

export interface LoginResp {
  token: string
  user: any
}

export const authApi = {
  login: async (email: string, password: string) => {
    const res = await http.post<LoginResp>('/api/v1/auth/login', {
      email,
      password,
    })
    setToken(res.token)
    const role = (res.user as any)?.role || ''
    if (role) setUserRole(role)
    else setUserRole(null)
    return res
  },
  register: async (name: string, email: string, password: string) => {
    // 新流程：后端不返回 token，而是发送验证邮件
    return http.post<{ success: boolean }>('/api/v1/auth/register', {
      name,
      email,
      password,
    })
  },
  logout: async () => {
    try {
      await http.post<void>('/api/v1/auth/logout')
    } finally {
      logoutLocal()
    }
  },
  me: () => http.get<User>('/api/v1/me'),
  updateMe: (
    payload: Partial<User> & {
      location?: string
      website?: string
      bio?: string
    },
  ) => http.patch<User>('/api/v1/me', payload),
  changePassword: (payload: { current_password: string; new_password: string }) =>
    http.post<{ success: boolean }>('/api/v1/me/change-password', payload),
  requestDelete: (reason?: string) =>
    http.post<{ success: boolean }>('/api/v1/me/request-delete', { reason }),
  forgotPassword: (email: string) =>
    http.post<{ success: boolean }>('/api/v1/auth/forgot-password', { email }),
  resetPassword: (payload: { email: string; password: string; token: string }) =>
    http.post<{ success: boolean }>('/api/v1/auth/reset-password', payload),
  resendVerification: (email: string) =>
    http.post<{ success: boolean }>('/api/v1/auth/resend-verification', {
      email,
    }),
}
