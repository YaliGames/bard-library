import { http } from './http'
import type { Book, Bookmark } from './types'
import { offlineStorage, getCacheKey } from '@/utils/offline'
import { bookmarkCache } from '@/utils/bookmarkCache'

export const bookmarksApi = {
    list: async (bookId: number, fileId?: number | null) => {
        const path = fileId
            ? `/api/v1/books/${bookId}/${fileId}/bookmarks`
            : `/api/v1/books/${bookId}/bookmarks`

        const key = getCacheKey('bookmarks-list', { bookId, fileId })

        try {
            // Try network first
            const data = await http.get<{ book: Book; bookmarks: Bookmark[] }>(path)
            // Cache the result
            await offlineStorage.meta.setItem(key, data)

            // Merge with offline changes
            const mergedBookmarks = await bookmarkCache.getOfflineBookmarks(bookId, fileId || 0)
            return { ...data, bookmarks: mergedBookmarks }
        } catch (e) {
            console.warn('[Bookmarks] Network failed, trying offline...', e)
            // If network fails, try to construct response from cache + offline
            const cached = await offlineStorage.meta.getItem<{ book: Book; bookmarks: Bookmark[] }>(key)
            const mergedBookmarks = await bookmarkCache.getOfflineBookmarks(bookId, fileId || 0)

            if (cached || mergedBookmarks.length > 0) {
                return {
                    book: cached?.book || {} as Book,
                    bookmarks: mergedBookmarks
                }
            }
            throw e
        }
    },
    create: async (bookId: number, payload: Partial<Bookmark>, fileId: number) => {
        const path = `/api/v1/books/${bookId}/${fileId}/bookmarks`
        try {
            const res = await http.post<Bookmark>(path, payload)
            return res
        } catch (e) {
            console.warn('[Bookmarks] Create failed, saving offline...', e)
            // Save offline
            return bookmarkCache.addOfflineBookmark(bookId, fileId, payload)
        }
    },
    update: async (bookId: number, bookmarkId: number, payload: Partial<Bookmark>) => {
        try {
            const res = await http.patch<Bookmark>(`/api/v1/books/${bookId}/bookmarks/${bookmarkId}`, payload)
            return res
        } catch (e) {
            console.warn('[Bookmarks] Update failed, saving offline...', e)
            return bookmarkCache.updateOfflineBookmark(bookId, bookmarkId, payload)
        }
    },
    remove: async (bookId: number, bookmarkId: number) => {
        try {
            await http.delete<void>(`/api/v1/books/${bookId}/bookmarks/${bookmarkId}`)
        } catch (e) {
            console.warn('[Bookmarks] Remove failed, saving offline...', e)
            await bookmarkCache.removeOfflineBookmark(bookId, bookmarkId)
        }
    },
}
