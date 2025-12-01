import { http } from './http'
import type { Author } from './types'
import { withCache, offlineStorage, getCacheKey } from '@/utils/offline'

export const authorsApi = {
    list: (params?: { q?: string }) => {
        const key = getCacheKey('authors-list', params)
        return withCache(
            key,
            () => http.get<Author[]>('/api/v1/authors', { params }),
            offlineStorage.meta
        )
    },
    create: (name: string) => {
        return http.post<Author>('/api/v1/authors', { name })
    },
    update: (id: number, name: string) => {
        return http.patch<Author>(`/api/v1/authors/${id}`, { name })
    },
    remove: (id: number) => {
        return http.delete<void>(`/api/v1/authors/${id}`)
    },
    // Raw：支持附带额外字段（如 sort_name）
    createRaw: (payload: Partial<Author> & Record<string, any>) => {
        return http.post<Author>('/api/v1/authors', payload)
    },
    updateRaw: (id: number, payload: Partial<Author> & Record<string, any>) => {
        return http.patch<Author>(`/api/v1/authors/${id}`, payload)
    },
}
