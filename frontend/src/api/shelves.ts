import { http, PageResp } from './http'
import type { Shelf } from './types'

function normalizePage<T>(raw: any): PageResp<T> {
  if (raw && raw.data && raw.meta) {
    return { data: raw.data as T[], meta: raw.meta }
  }
  return {
    data: Array.isArray(raw?.data) ? (raw.data as T[]) : Array.isArray(raw) ? (raw as T[]) : [],
    meta: {
      current_page: Number(raw?.current_page ?? 1),
      last_page: Number(raw?.last_page ?? 1),
      per_page: Number(raw?.per_page ?? (Array.isArray(raw?.data) ? raw.data.length : Array.isArray(raw) ? (raw as any[]).length : 0)),
      total: Number(raw?.total ?? (Array.isArray(raw?.data) ? raw.data.length : Array.isArray(raw) ? (raw as any[]).length : 0)),
    },
  }
}

export const shelvesApi = {
  // 拉取单页（分页） 支持 q 与 bookLimit（每个书架携带的书本数）
  listPage: async (params?: { page?: number; per_page?: number; q?: string; bookLimit?: number; owner?: 'me'; visibility?: 'public'|'private' }): Promise<PageResp<Shelf>> => {
    const u = new URL('/api/v1/shelves', window.location.origin)
    if (params?.page) u.searchParams.set('page', String(params.page))
    if (params?.per_page) u.searchParams.set('per_page', String(params.per_page))
    if (params?.q) u.searchParams.set('q', params.q)
    if (typeof params?.bookLimit === 'number') u.searchParams.set('bookLimit', String(params.bookLimit))
    if (params?.owner) u.searchParams.set('owner', params.owner)
    if (params?.visibility) u.searchParams.set('visibility', params.visibility)
    const raw = await http.get<any>(u.toString())
    return normalizePage<Shelf>(raw)
  },
  listAll: async (): Promise<Shelf[]> => {
    return http.get<Shelf[]>('/api/v1/shelves/all');
  },
  // 兼容旧的 summary：服务器已改为分页返回，这里不再使用
  show: async (id: number): Promise<Shelf> => {
    return http.get<Shelf>(`/api/v1/shelves/${id}`)
  },
  setBooks: (id: number, bookIds: number[]) => {
    return http.post<Shelf>(`/api/v1/shelves/${id}/books`, { book_ids: bookIds })
  },
  createRaw: (payload: Partial<Shelf> & Record<string, any>) => {
    return http.post<Shelf>('/api/v1/shelves', payload);
  },
  updateRaw: (id: number, payload: Partial<Shelf> & Record<string, any>) => {
    return http.patch<Shelf>(`/api/v1/shelves/${id}`, payload);
  },
  remove: (id: number) => {
    return http.delete<void>(`/api/v1/shelves/${id}`);
  },
}
