import { http, PageResp } from './http'
import type { Shelf } from './types'
import { withCache, offlineStorage, getCacheKey } from '@/utils/offline'

function normalizePage<T>(raw: any): PageResp<T> {
    if (raw && raw.data && raw.meta) {
        return { data: raw.data as T[], meta: raw.meta }
    }
    return {
        data: Array.isArray(raw?.data) ? (raw.data as T[]) : Array.isArray(raw) ? (raw as T[]) : [],
        meta: {
            current_page: Number(raw?.current_page ?? 1),
            last_page: Number(raw?.last_page ?? 1),
            per_page: Number(
                raw?.per_page ??
                (Array.isArray(raw?.data)
                    ? raw.data.length
                    : Array.isArray(raw)
                        ? (raw as any[]).length
                        : 0),
            ),
            total: Number(
                raw?.total ??
                (Array.isArray(raw?.data)
                    ? raw.data.length
                    : Array.isArray(raw)
                        ? (raw as any[]).length
                        : 0),
            ),
        },
    }
}

export const shelvesApi = {
    // 拉取单页（分页） 支持 q 与 book_limit（每个书架携带的书本数）
    listPage: async (params?: {
        page?: number
        per_page?: number
        q?: string
        book_limit?: number
        owner?: 'me' | 'admin'
        visibility?: 'public' | 'private'
    }): Promise<PageResp<Shelf>> => {
        const key = getCacheKey('shelves-page', params)
        return withCache(
            key,
            async () => {
                const raw = await http.get<any>('/api/v1/shelves', { params })
                return normalizePage<Shelf>(raw)
            },
            offlineStorage.meta
        )
    },
    listAll: async (): Promise<Shelf[]> => {
        return withCache(
            'shelves-all',
            () => http.get<Shelf[]>('/api/v1/shelves/all'),
            offlineStorage.meta
        )
    },
    // 兼容旧的 summary：服务器已改为分页返回，这里不再使用
    show: async (id: number): Promise<Shelf> => {
        return http.get<Shelf>(`/api/v1/shelves/${id}`)
    },
    setBooks: (id: number, bookIds: number[]) => {
        return http.post<Shelf>(`/api/v1/shelves/${id}/books`, { book_ids: bookIds })
    },
    createRaw: (payload: Partial<Shelf> & Record<string, any>) => {
        return http.post<Shelf>('/api/v1/shelves', payload)
    },
    updateRaw: (id: number, payload: Partial<Shelf> & Record<string, any>) => {
        return http.patch<Shelf>(`/api/v1/shelves/${id}`, payload)
    },
    remove: (id: number) => {
        return http.delete<void>(`/api/v1/shelves/${id}`)
    },
}
