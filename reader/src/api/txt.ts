import { http } from './http'

export interface Chapter {
    index: number
    title?: string | null
    offset: number
    length: number
    file_id?: number
    book_id?: number
}

export interface ChaptersResponse {
    book?: {
        id: number
        title: string
        author?: string
        cover?: string
    } | null
    chapters: Chapter[]
}

export const txtApi = {
    listChapters: (fileId: number, params?: { pattern?: string; dry?: boolean }) => {
        return http.get<ChaptersResponse>(`/api/v1/txt/${fileId}/chapters`, { params })
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
}
