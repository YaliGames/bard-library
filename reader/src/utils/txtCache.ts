/**
 * TXT 阅读器本地缓存业务逻辑
 * 使用统一的缓存管理器
 */

import { txtCache as cacheManager } from './cacheManager'

export interface Chapter {
    index: number
    title?: string | null
    offset: number
    length: number
}

export interface CachedBook {
    fileId: number
    bookId?: number
    bookTitle?: string
    fileName: string
    chapters: Chapter[]
    contents: Map<number, string>
    cachedAt: number
    version: number
}

/**
 * 保存整本书到缓存
 */
export async function cacheBook(
    fileId: number,
    chapters: Chapter[],
    contents: Map<number, string>,
    options: { bookId?: number; bookTitle?: string; fileName: string }
): Promise<void> {
    return cacheManager.cacheBook(fileId, chapters, contents, options)
}

/**
 * 获取缓存的书籍
 */
export async function getCachedBook(fileId: number): Promise<CachedBook | null> {
    return cacheManager.getCachedBook(fileId)
}

/**
 * 删除缓存的书籍
 */
export async function deleteCachedBook(fileId: number): Promise<void> {
    return cacheManager.deleteCachedBook(fileId)
}

/**
 * 获取所有缓存的书籍列表
 */
export async function getAllCachedBooks(): Promise<
    Array<{
        fileId: number
        bookId?: number
        bookTitle?: string
        fileName: string
        chapterCount: number
        cachedAt: number
        size: number
    }>
> {
    return cacheManager.getAllCachedBooks()
}

/**
 * 清空所有缓存
 */
export async function clearAllCache(): Promise<void> {
    return cacheManager.clearAllCache()
}

/**
 * 获取缓存统计信息
 */
export async function getCacheStats(): Promise<{
    totalBooks: number
    totalSize: number
    oldestCache: number | null
}> {
    return cacheManager.getCacheStats()
}
