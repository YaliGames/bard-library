import { http } from './http'

export interface AdminFileItem {
  id: number
  book_id: number | null
  book: { id: number; title: string } | null
  used_as_cover: boolean
  format: string
  size: number
  mime: string
  sha256: string
  filename: string
  path: string
  storage: string
  created_at: string
  physical_exists: boolean
}

export const adminFilesApi = {
  list: (params?: { q?: string; format?: string; unused_covers?: boolean; missing_physical?: boolean; sortKey?: string; sortOrder?: 'asc'|'desc' }) => {
    const u = new URL('/api/v1/admin/files', window.location.origin)
    if (params) {
      for (const [k,v] of Object.entries(params)) {
        if (v !== undefined && v !== null && String(v) !== '') u.searchParams.set(k, String(v))
      }
    }
    return http.get<{ count: number; items: AdminFileItem[] }>(u.toString())
  },
  remove: (id: number, physical = false) => {
    const u = new URL(`/api/v1/admin/files/${id}`, window.location.origin)
    if (physical) u.searchParams.set('physical', 'true')
    return http.delete<void>(u.toString())
  },
  cleanup: (body: { kind?: 'covers'|'all'|'orphans'; kinds?: Array<'covers'|'dangling'|'missing'|'orphans'>; dry?: boolean; removePhysical?: boolean }) => {
    return http.post<{ dry: boolean; summary: any; removed?: any }>(`/api/v1/admin/files/cleanup`, body)
  }
}
