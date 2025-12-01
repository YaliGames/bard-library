// 轻量 HTTP 封装，适配后端统一响应 { code, data, message }，基于 axios
import axios, { AxiosError, AxiosInstance } from 'axios'
import { defaultBackendUrl } from '@/config'

export interface PageMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
}
export interface PageResp<T> {
    data: T[]
    meta: PageMeta
}

type ApiEnvelope<T> = { code: number; data: T; message: string }

function createClient(): AxiosInstance {
    const instance = axios.create({
        baseURL: defaultBackendUrl,
        withCredentials: true, // 启用 Cookie 携带,支持 Sanctum 的 Cookie 认证
    })

    // 请求拦截：注入默认头
    instance.interceptors.request.use(config => {
        config.headers = config.headers || {}
        config.headers['Accept'] = 'application/json'
        return config
    })

    // 响应拦截：统一 envelope 与鉴权跳转
    instance.interceptors.response.use(
        response => {
            const json = response.data
            if (json && typeof json === 'object' && 'code' in json && 'data' in json) {
                const env = json as ApiEnvelope<any>
                if (env.code && env.code !== 0) {
                    if (env.code === 401) {
                        const redirectTo =
                            window.location.pathname + window.location.search + window.location.hash
                        window.dispatchEvent(new CustomEvent('app:unauthorized', { detail: { redirectTo } }))
                        return Promise.reject(
                            Object.assign(new Error('Unauthorized'), {
                                code: 401,
                                status: 401,
                            }),
                        )
                    }
                    return Promise.reject(
                        Object.assign(new Error(env.message || 'Request failed'), {
                            status: env.code,
                            data: env.data,
                        }),
                    )
                }
                return env.data
            }
            return json
        },
        async (error: AxiosError) => {
            const status = error.response?.status
            const data: any = error.response?.data
            const message = data?.message || data?.error || error.message || 'Network error'
            if (status === 401) {
                const redirectTo = window.location.pathname + window.location.search + window.location.hash
                window.dispatchEvent(new CustomEvent('app:unauthorized', { detail: { redirectTo } }))
                return Promise.reject(
                    Object.assign(new Error('Unauthorized'), {
                        code: 401,
                        status: 401,
                    }),
                )
            }
            const err: any = new Error(message)
            err.status = status
            err.data = data
            return Promise.reject(err)
        },
    )

    return instance
}

const client = createClient()

export interface HttpConfig {
    params?: Record<string, any>
    headers?: Record<string, string>
    [key: string]: any
}

export const http = {
    get: <T>(url: string, config?: HttpConfig): Promise<T> => client.get(url, config) as any,
    post: <T>(url: string, body?: any, config?: HttpConfig): Promise<T> =>
        client.post(url, body, config) as any,
    patch: <T>(url: string, body?: any, config?: HttpConfig): Promise<T> =>
        client.patch(url, body, config) as any,
    delete: <T>(url: string, config?: HttpConfig): Promise<T> => client.delete(url, config) as any,
}
