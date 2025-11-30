import { http } from './http'
import type { User } from './types'
import { useUserStore } from '@/stores/user'

// 类型定义
export interface LoginResponse {
    user: User
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

        const userStore = useUserStore()
        // 不再需要 token,使用 Cookie 认证
        userStore.setUser(res.user)

        return res
    },

    /**
     * 用户登出
     * 即使后端请求失败也会清除本地状态
     */
    logout: async () => {
        try {
            await http.post<void>('/api/v1/auth/logout')
        } finally {
            const userStore = useUserStore()
            userStore.logout()
        }
    },

    /**
     * 获取当前登录用户信息
     * @returns 用户信息
     */
    me: () => {
        return http.get<User>('/api/v1/me')
    },
}
