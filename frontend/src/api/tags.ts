import { http } from './http'
import type { Tag } from './types'

export const tagsApi = {
  list: (params?: { q?: string; type?: string }) => {
    const u = new URL('/api/v1/tags', window.location.origin)
    if (params?.q) u.searchParams.set('q', params.q)
    if (params?.type) u.searchParams.set('type', params.type)
    return http.get<Tag[]>(u.toString())
  },
  create: (name: string) => {
    return http.post<Tag>('/api/v1/tags', { name })
  },
  update: (id: number, name: string) => {
    return http.patch<Tag>(`/api/v1/tags/${id}`, { name })
  },
  remove: (id: number) => {
    return http.delete<void>(`/api/v1/tags/${id}`)
  },
  // Raw：支持附带额外字段（如 type）
  createRaw: (payload: Partial<Tag> & Record<string, any>) => {
    return http.post<Tag>('/api/v1/tags', payload)
  },
  updateRaw: (id: number, payload: Partial<Tag> & Record<string, any>) => {
    return http.patch<Tag>(`/api/v1/tags/${id}`, payload)
  },
}
