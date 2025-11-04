import { http, PageResp } from './http'
import type { Book, FileRec } from './types'

export const booksApi = {
  list: async (params?: {
    q?: string
    page?: number
    per_page?: number
    author_id?: number[] | string | number
    tag_id?: number[] | string | number
    shelf_id?: number
    read_state?: 'read' | 'unread' | 'reading'
    min_rating?: number
    max_rating?: number
    publisher?: string
    published_at?: string // deprecated: use published_from/published_to
    published_from?: string
    published_to?: string
    language?: string
    series_value?: string | number
    isbn?: string
    sort?: 'modified' | 'created' | 'rating' | 'id'
    order?: 'asc' | 'desc'
  }): Promise<PageResp<Book>> => {
    const u = new URL('/api/v1/books', window.location.origin)
    if (params?.q) u.searchParams.set('q', params.q)
    if (params?.page) u.searchParams.set('page', String(params.page))
    if (params?.per_page) u.searchParams.set('per_page', String(params.per_page))
    const toCsv = (v: any) => (Array.isArray(v) ? v.join(',') : (v ?? ''))
    if (params?.author_id) u.searchParams.set('author_id', toCsv(params.author_id))
    if (params?.tag_id) u.searchParams.set('tag_id', toCsv(params.tag_id))
    if (params?.shelf_id) u.searchParams.set('shelf_id', String(params.shelf_id))
    if (params?.read_state) u.searchParams.set('read_state', params.read_state)
    if (typeof params?.min_rating === 'number')
      u.searchParams.set('min_rating', String(params.min_rating))
    if (typeof params?.max_rating === 'number')
      u.searchParams.set('max_rating', String(params.max_rating))
    if (params?.publisher) u.searchParams.set('publisher', params.publisher)
    if (params?.published_at) u.searchParams.set('published_at', params.published_at)
    if (params?.published_from) u.searchParams.set('published_from', params.published_from)
    if (params?.published_to) u.searchParams.set('published_to', params.published_to)
    if (params?.language) u.searchParams.set('language', params.language)
    if (params?.series_value !== undefined && params?.series_value !== null)
      u.searchParams.set('series_value', String(params.series_value))
    if (params?.isbn) u.searchParams.set('isbn', params.isbn)
    if (params?.sort) u.searchParams.set('sort', params.sort)
    if (params?.order) u.searchParams.set('order', params.order)
    const raw = await http.get<any>(u.toString())
    // 兼容 { data, meta } 或 Laravel 分页的平铺结构
    if (raw && raw.data && raw.meta) {
      return { data: raw.data as Book[], meta: raw.meta }
    }
    return {
      data: Array.isArray(raw?.data) ? raw.data : [],
      meta: {
        current_page: Number(raw?.current_page ?? 1),
        last_page: Number(raw?.last_page ?? 1),
        per_page: Number(raw?.per_page ?? (Array.isArray(raw?.data) ? raw.data.length : 0)),
        total: Number(raw?.total ?? (Array.isArray(raw?.data) ? raw.data.length : 0)),
      },
    }
  },
  get: (id: number) => {
    return http.get<Book>(`/api/v1/books/${id}`)
  },
  setShelves: (id: number, shelf_ids: number[]) => {
    return http.post<Book>(`/api/v1/books/${id}/shelves`, { shelf_ids })
  },
  create: (payload: Partial<Book>) => {
    return http.post<Book>('/api/v1/books', payload)
  },
  update: (id: number, payload: Partial<Book>) => {
    return http.patch<Book>(`/api/v1/books/${id}`, payload)
  },
  remove: (id: number, opts?: { withFiles?: boolean }) => {
    const u = new URL(`/api/v1/books/${id}`, window.location.origin)
    if (opts?.withFiles) u.searchParams.set('with_files', 'true')
    return http.delete<void>(u.toString())
  },
  files: (id: number, opts?: { include_cover?: boolean }) => {
    const u = new URL(`/api/v1/books/${id}/files`, window.location.origin)
    if (opts?.include_cover) u.searchParams.set('include_cover', 'true')
    return http.get<FileRec[]>(u.toString())
  },
  setAuthors: (id: number, author_ids: number[]) => {
    return http.post<Book>(`/api/v1/books/${id}/authors`, { author_ids })
  },
  setTags: (id: number, tag_ids: number[]) => {
    return http.post<Book>(`/api/v1/books/${id}/tags`, { tag_ids })
  },
  markRead: (id: number, is_read: boolean) => {
    return http.post<{ success: boolean }>(`/api/v1/books/${id}/mark-read`, {
      is_read,
    })
  },
}
