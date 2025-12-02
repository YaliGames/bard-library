/**
 * 统一的缓存管理器
 * 提供所有离线缓存相关功能的统一接口
 */

import localforage from 'localforage'
import type { Book, Bookmark } from '@/api/types'
import type { BookListResponse } from '@/api/books'

// 配置 localforage 实例
const bookStore = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'books',
})

const metaStore = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'meta', // authors, tags, shelves
})

const bookmarkStore = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'bookmarks',
})

// 缓存键生成
export function getCacheKey(prefix: string, params?: any): string {
    if (!params) return prefix
    const sortedKeys = Object.keys(params).sort()
    const queryPart = sortedKeys
        .map((k) => `${k}=${JSON.stringify(params[k])}`)
        .join('&')
    return `${prefix}?${queryPart}`
}

// 跟踪是否正在使用缓存数据
let usingCachedData = false

export function isUsingCachedData() {
    return usingCachedData
}

export function resetCachedDataFlag() {
    usingCachedData = false
}

// 通用缓存包装器
export async function withCache<T>(
    key: string,
    fetcher: () => Promise<T>,
    store: LocalForage = metaStore,
    networkFirst = true,
    notifyOnCache = false
): Promise<T> {
    if (networkFirst) {
        try {
            const data = await fetcher()
            await store.setItem(key, data)
            usingCachedData = false
            return data
        } catch (error) {
            console.warn(`[CacheManager] Network failed for ${key}, trying cache...`, error)
            const cached = await store.getItem<T>(key)
            if (cached) {
                usingCachedData = notifyOnCache
                return cached
            }
            throw error
        }
    } else {
        const cached = await store.getItem<T>(key)
        if (cached) {
            // 后台更新缓存
            fetcher()
                .then((data) => store.setItem(key, data))
                .catch((err) => console.warn(`[CacheManager] Background update failed for ${key}`, err))
            return cached
        }
        const data = await fetcher()
        await store.setItem(key, data)
        return data
    }
}

// 书籍缓存相关
export const bookCache = {
    // 获取所有缓存的书籍列表页面
    async getCachedBookListPages(): Promise<{
        page: number
        sort: string
        order: string
        bookCount: number
        cachedAt: number
    }[]> {
        const keys = await bookStore.keys()
        const bookListKeys = keys.filter(k => k.startsWith('books-list?'))

        const pages = await Promise.all(
            bookListKeys.map(async (key) => {
                const data = await bookStore.getItem<BookListResponse>(key)
                if (!data) return null

                const params = new URLSearchParams(key.split('?')[1])
                const page = parseInt(params.get('page') || '1')
                const sort = params.get('sort') || 'created'
                const order = params.get('order') || 'desc'

                return {
                    page,
                    sort,
                    order,
                    bookCount: data.data.length,
                    cachedAt: Date.now(),
                }
            })
        )

        return pages.filter((p): p is NonNullable<typeof p> => p !== null)
            .sort((a, b) => a.page - b.page)
    },

    // 获取所有缓存的书籍
    async getAllCachedBooks(): Promise<Book[]> {
        const keys = await bookStore.keys()
        const bookListKeys = keys.filter(k => k.startsWith('books-list?'))

        const allBooks: Book[] = []
        const bookMap = new Map<number, Book>()

        for (const key of bookListKeys) {
            const data = await bookStore.getItem<BookListResponse>(key)
            if (data && data.data) {
                data.data.forEach(book => {
                    if (!bookMap.has(book.id)) {
                        bookMap.set(book.id, book)
                    }
                })
            }
        }

        return Array.from(bookMap.values())
    },

    // 本地过滤书籍
    filterBooksLocally(
        books: Book[],
        filters: {
            q?: string
            authorId?: number | null
            tagIds?: number[]
            shelfId?: number | null
            readState?: string | null
            ratingRange?: [number, number]
        }
    ): Book[] {
        let result = [...books]

        if (filters.q) {
            const query = filters.q.toLowerCase()
            result = result.filter(book =>
                book.title.toLowerCase().includes(query) ||
                book.authors?.some(a => a.name.toLowerCase().includes(query))
            )
        }

        if (filters.authorId) {
            result = result.filter(book =>
                book.authors?.some(a => a.id === filters.authorId)
            )
        }

        if (filters.tagIds && filters.tagIds.length > 0) {
            result = result.filter(book =>
                book.tags?.some(t => filters.tagIds!.includes(t.id))
            )
        }

        if (filters.shelfId) {
            result = result.filter(book =>
                book.shelves?.some(s => s.id === filters.shelfId)
            )
        }

        if (filters.readState) {
            switch (filters.readState) {
                case 'read':
                    result = result.filter(book => book.is_read_mark)
                    break
                case 'reading':
                    result = result.filter(book => book.is_reading)
                    break
                case 'unread':
                    result = result.filter(book => !book.is_read_mark && !book.is_reading)
                    break
            }
        }

        if (filters.ratingRange) {
            const [min, max] = filters.ratingRange
            result = result.filter(book => {
                const rating = book.rating || 0
                return rating >= min && rating <= max
            })
        }

        return result
    },
}

// TXT缓存相关
export const txtCache = {
    // 复用现有的txtCache功能
    async cacheBook(
        fileId: number,
        chapters: any[],
        contents: Map<number, string>,
        options: { bookId?: number; bookTitle?: string; fileName: string }
    ): Promise<void> {
        const { cacheBook } = await import('./txtCache')
        return cacheBook(fileId, chapters, contents, options)
    },

    async getCachedBook(fileId: number) {
        const { getCachedBook } = await import('./txtCache')
        return getCachedBook(fileId)
    },

    async deleteCachedBook(fileId: number) {
        const { deleteCachedBook } = await import('./txtCache')
        return deleteCachedBook(fileId)
    },

    async getAllCachedBooks() {
        const { getAllCachedBooks } = await import('./txtCache')
        return getAllCachedBooks()
    },

    async clearAllCache() {
        const { clearAllCache } = await import('./txtCache')
        return clearAllCache()
    },

    async getCacheStats() {
        const { getCacheStats } = await import('./txtCache')
        return getCacheStats()
    },
}

// 书签缓存相关
export interface OfflineBookmark extends Omit<Bookmark, 'id'> {
    id: string // 离线时使用字符串ID
    offlineId: string
    createdAt: number
    synced: boolean
}

export const bookmarkCache = {
    // 生成离线书签ID
    generateOfflineId(): string {
        return `offline_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    },

    // 获取书签缓存键
    getBookmarkKey(bookId: number, fileId: number): string {
        return `bookmarks_${bookId}_${fileId}`
    },

    // 获取所有离线书签
    async getOfflineBookmarks(): Promise<OfflineBookmark[]> {
        const keys = await bookmarkStore.keys()
        const bookmarkKeys = keys.filter(k => k.startsWith('bookmarks_'))

        const allBookmarks: OfflineBookmark[] = []
        for (const key of bookmarkKeys) {
            const bookmarks = await bookmarkStore.getItem<OfflineBookmark[]>(key) || []
            allBookmarks.push(...bookmarks.filter(b => !b.synced))
        }

        return allBookmarks
    },

    // 保存书签（支持离线）
    async saveBookmark(bookId: number, fileId: number, bookmark: Partial<Bookmark>): Promise<OfflineBookmark> {
        const key = this.getBookmarkKey(bookId, fileId)
        const existingBookmarks = await bookmarkStore.getItem<OfflineBookmark[]>(key) || []

        const offlineBookmark: OfflineBookmark = {
            ...bookmark,
            id: this.generateOfflineId(),
            offlineId: this.generateOfflineId(),
            createdAt: Date.now(),
            synced: false,
        } as OfflineBookmark

        existingBookmarks.push(offlineBookmark)
        await bookmarkStore.setItem(key, existingBookmarks)

        return offlineBookmark
    },

    // 获取书签列表（包含离线书签）
    async getBookmarks(bookId: number, fileId: number): Promise<OfflineBookmark[]> {
        const key = this.getBookmarkKey(bookId, fileId)
        const cachedBookmarks = await bookmarkStore.getItem<OfflineBookmark[]>(key) || []
        return cachedBookmarks
    },

    // 删除书签
    async deleteBookmark(bookId: number, fileId: number, bookmarkId: string): Promise<void> {
        const key = this.getBookmarkKey(bookId, fileId)
        const bookmarks = await bookmarkStore.getItem<OfflineBookmark[]>(key) || []
        const filtered = bookmarks.filter(b => b.id !== bookmarkId)
        await bookmarkStore.setItem(key, filtered)
    },

    // 标记书签为已同步
    async markSynced(bookId: number, fileId: number, offlineId: string, serverId: number): Promise<void> {
        const key = this.getBookmarkKey(bookId, fileId)
        const bookmarks = await bookmarkStore.getItem<OfflineBookmark[]>(key) || []
        const bookmark = bookmarks.find(b => b.offlineId === offlineId)
        if (bookmark) {
            bookmark.synced = true
            bookmark.id = serverId.toString()
            await bookmarkStore.setItem(key, bookmarks)
        }
    },

    // 上传离线书签
    async uploadOfflineBookmarks(bookId: number, fileId: number): Promise<void> {
        const key = this.getBookmarkKey(bookId, fileId)
        const bookmarks = await bookmarkStore.getItem<OfflineBookmark[]>(key) || []
        const unsyncedBookmarks = bookmarks.filter(b => !b.synced)

        for (const bookmark of unsyncedBookmarks) {
            try {
                const { bookmarksApi } = await import('@/api/bookmarks')
                const serverBookmark = await bookmarksApi.create(bookId, {
                    chapter_index: bookmark.chapter_index,
                    position: bookmark.position,
                    note: bookmark.note,
                    color: bookmark.color,
                }, fileId)

                await this.markSynced(bookId, fileId, bookmark.offlineId, serverBookmark.id)
            } catch (error) {
                console.error('Failed to upload bookmark:', bookmark, error)
                // 继续上传其他书签
            }
        }
    },
}

// 导出存储实例供其他地方使用
export const storage = {
    books: bookStore,
    meta: metaStore,
    bookmarks: bookmarkStore,
}