// 轻量 HTTP 封装，适配后端统一响应 { code, data, message }，基于 axios
import axios, { AxiosError, AxiosInstance } from 'axios'
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
    // baseURL 可留空，项目内大多传入绝对路径（/api/...）
    withCredentials: false,
  })

  // 请求拦截：注入 token 与默认头
  instance.interceptors.request.use(config => {
    const token = localStorage.getItem('token') || ''
    config.headers = config.headers || {}
    config.headers['Accept'] = 'application/json'
    if (token) config.headers['Authorization'] = `Bearer ${token}`
    // 让 axios 自动处理 FormData 的 Content-Type
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
            localStorage.removeItem('token')
            const redirect = encodeURIComponent(
              window.location.pathname + window.location.search + window.location.hash,
            )
            window.location.href = `/login?redirect=${redirect}`
            return new Promise(() => {})
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
        localStorage.removeItem('token')
        const redirect = encodeURIComponent(
          window.location.pathname + window.location.search + window.location.hash,
        )
        window.location.href = `/login?redirect=${redirect}`
        return new Promise(() => {})
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

export const http = {
  get: async <T>(url: string, config?: any): Promise<T> => client.get(url, config) as any,
  post: async <T>(url: string, body?: any, config?: any): Promise<T> =>
    client.post(url, body, config) as any,
  patch: async <T>(url: string, body?: any, config?: any): Promise<T> =>
    client.patch(url, body, config) as any,
  delete: async <T>(url: string, config?: any): Promise<T> => client.delete(url, config) as any,
}
