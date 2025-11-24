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
    return http.get<Chapter[]>(`/api/v1/txt/${fileId}/chapters`, { params })
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
  getFullContent: (fileId: number) => {
    return http.get<{
      book_id?: number
      file_id: number
      book_title?: string
      file_name: string
      chapters: Chapter[]
      contents: Record<number, string>
    }>(`/api/v1/txt/${fileId}/full-content`)
  },
  renameChapter: (fileId: number, index: number, title: string | null) => {
    return http.patch(`/api/v1/txt/${fileId}/chapters/${index}`, { title })
  },
  deleteChapterWithMerge: (fileId: number, index: number, merge: 'prev' | 'next') => {
    return http.delete(`/api/v1/txt/${fileId}/chapters/${index}`, { params: { merge } })
  },
}
