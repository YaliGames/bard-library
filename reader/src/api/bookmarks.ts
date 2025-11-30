import { http } from './http'
import type { Book, Bookmark } from './types'

export const bookmarksApi = {
    list: (bookId: number, fileId?: number | null) => {
        const path = fileId
            ? `/api/v1/books/${bookId}/${fileId}/bookmarks`
            : `/api/v1/books/${bookId}/bookmarks`
        return http.get<{ book: Book; bookmarks: Bookmark[] }>(path)
    },
    create: (bookId: number, payload: Partial<Bookmark>, fileId: number) => {
        const path = `/api/v1/books/${bookId}/${fileId}/bookmarks`
        return http.post<Bookmark>(path, payload)
    },
    update: (bookId: number, bookmarkId: number, payload: Partial<Bookmark>) => {
        return http.patch<Bookmark>(`/api/v1/books/${bookId}/bookmarks/${bookmarkId}`, payload)
    },
    remove: (bookId: number, bookmarkId: number) => {
        return http.delete<void>(`/api/v1/books/${bookId}/bookmarks/${bookmarkId}`)
    },
}
