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
  // 拉取单页（当后端为分页返回时使用）
  listPage: async (params?: { page?: number; per_page?: number }): Promise<PageResp<Shelf>> => {
    const u = new URL('/api/v1/shelves', window.location.origin)
    if (params?.page) u.searchParams.set('page', String(params.page))
    if (params?.per_page) u.searchParams.set('per_page', String(params.per_page))
    const raw = await http.get<any>(u.toString())
    return normalizePage<Shelf>(raw)
  },
  listAll: async (): Promise<Shelf[]> => {
    return http.get<Shelf[]>('/api/v1/shelves/all');
  },
  listSummaries: async (limit = 5): Promise<Shelf[]> => {
    const raw = await http.get<any>(`/api/v1/shelves/summary?limit=${encodeURIComponent(String(limit))}`)
    return raw as Shelf[];
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
