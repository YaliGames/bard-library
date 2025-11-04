import { http } from './http'

export interface Chapter {
  index: number
  title?: string | null
  offset: number
  length: number
  file_id?: number
  book_id?: number
}

export const txtApi = {
  listChapters: (fileId: number, params?: { pattern?: string; dry?: boolean }) => {
    const u = new URL(`/api/v1/txt/${fileId}/chapters`, window.location.origin)
    if (params?.pattern) u.searchParams.set('pattern', params.pattern)
    if (params?.dry) u.searchParams.set('dry', 'true')
    return http.get<Chapter[]>(u.toString())
  },
  saveChapters: (fileId: number, payload: { pattern?: string; replace?: boolean }) => {
    return http.post<Chapter[]>(`/api/v1/txt/${fileId}/chapters`, payload)
  },
  getChapterContent: (fileId: number, index: number) => {
    return http.get<{
      content: string
      book_id?: number
      file_id?: number
      index: number
      title?: string | null
    }>(`/api/v1/txt/${fileId}/chapters/${index}`)
  },
  renameChapter: (fileId: number, index: number, title: string | null) => {
    return http.patch(`/api/v1/txt/${fileId}/chapters/${index}`, { title })
  },
  deleteChapterWithMerge: (fileId: number, index: number, merge: 'prev' | 'next') => {
    const u = new URL(`/api/v1/txt/${fileId}/chapters/${index}`, window.location.origin)
    u.searchParams.set('merge', merge)
    return http.delete(u.toString())
  },
}
