/**
 * 书籍缓存业务逻辑
 * 使用统一的缓存管理器
 */

import { bookCache as cacheManager } from './cacheManager'
import type { Book } from '@/api/types'

/**
 * Get all cached book list pages
 */
export async function getCachedBookListPages() {
    return cacheManager.getCachedBookListPages()
}

/**
 * Get all cached books from all pages
 */
export async function getAllCachedBooks(): Promise<Book[]> {
    return cacheManager.getAllCachedBooks()
}

/**
 * Filter books locally
 */
export function filterBooksLocally(
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
    return cacheManager.filterBooksLocally(books, filters)
}
