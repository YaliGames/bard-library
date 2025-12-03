/**
 * TXT 阅读器本地缓存管理
 * 使用 IndexedDB 存储整本书的内容和章节信息
 */

export interface Chapter {
    index: number
    title?: string | null
    offset: number
    length: number
}

export interface CachedBook {
    fileId: number
    bookId?: number
    bookTitle?: string // 书籍标题（来自 book 表）
    fileName: string // 文件名
    chapters: Chapter[]
    contents: Map<number, string> // chapterIndex -> content
    cachedAt: number // 缓存时间戳
    version: number // 缓存版本号
}

interface CachedBookData {
    fileId: number
    bookId?: number
    bookTitle?: string
    fileName: string
    chapters: Chapter[]
    contents: Record<number, string> // Map 转为普通对象存储
    cachedAt: number
    version: number
}

const DB_NAME = 'BardLibraryTxtCache'
const DB_VERSION = 1
const STORE_NAME = 'books'
const CACHE_VERSION = 1 // 当缓存结构变化时递增

let dbPromise: Promise<IDBDatabase> | null = null

// 初始化 IndexedDB
function getDB(): Promise<IDBDatabase> {
    if (dbPromise) return dbPromise

    dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION)

        request.onerror = () => reject(request.error)
        request.onsuccess = () => resolve(request.result)

        request.onupgradeneeded = event => {
            const db = (event.target as IDBOpenDBRequest).result

            // 创建对象存储
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, { keyPath: 'fileId' })
                store.createIndex('cachedAt', 'cachedAt', { unique: false })
            }
        }
    })

    return dbPromise
}

/**
 * 保存整本书到缓存
 */
export async function cacheBook(
    fileId: number,
    chapters: Chapter[],
    contents: Map<number, string>,
    options: { bookId?: number; bookTitle?: string; fileName: string },
): Promise<void> {
    const db = await getDB()

    // 将 Map 转换为普通对象，并确保 chapters 是可序列化的
    const contentsObj: Record<number, string> = {}
    contents.forEach((value, key) => {
        contentsObj[key] = value
    })

    const data: CachedBookData = {
        fileId,
        bookId: options.bookId,
        bookTitle: options.bookTitle,
        fileName: options.fileName,
        chapters: JSON.parse(JSON.stringify(chapters)), // 深拷贝确保可序列化
        contents: contentsObj,
        cachedAt: Date.now(),
        version: CACHE_VERSION,
    }

    return new Promise((resolve, reject) => {
        const transaction = db.transaction([STORE_NAME], 'readwrite')
        const store = transaction.objectStore(STORE_NAME)
        const request = store.put(data)

        request.onsuccess = () => resolve()
        request.onerror = () => reject(request.error)
    })
}

/**
 * 获取缓存的书籍
 */
export async function getCachedBook(fileId: number): Promise<CachedBook | null> {
    const db = await getDB()

    return new Promise((resolve, reject) => {
        const transaction = db.transaction([STORE_NAME], 'readonly')
        const store = transaction.objectStore(STORE_NAME)
        const request = store.get(fileId)

        request.onsuccess = () => {
            const data = request.result as CachedBookData | undefined
            if (!data) {
                resolve(null)
                return
            }

            // 检查版本号
            if (data.version !== CACHE_VERSION) {
                // 版本不匹配,删除旧缓存
                deleteCachedBook(fileId).catch(() => { })
                resolve(null)
                return
            }

            resolve({
                ...data,
                contents: new Map(Object.entries(data.contents).map(([k, v]) => [Number(k), v])),
            })
        }
        request.onerror = () => reject(request.error)
    })
}

/**
 * 删除缓存的书籍
 */
export async function deleteCachedBook(fileId: number): Promise<void> {
    const db = await getDB()

    return new Promise((resolve, reject) => {
        const transaction = db.transaction([STORE_NAME], 'readwrite')
        const store = transaction.objectStore(STORE_NAME)
        const request = store.delete(fileId)

        request.onsuccess = () => resolve()
        request.onerror = () => reject(request.error)
    })
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
        size: number // 估算大小(字节)
    }>
> {
    const db = await getDB()

    return new Promise((resolve, reject) => {
        const transaction = db.transaction([STORE_NAME], 'readonly')
        const store = transaction.objectStore(STORE_NAME)
        const request = store.getAll()

        request.onsuccess = () => {
            const books = request.result as CachedBookData[]
            resolve(
                books.map(book => {
                    // 估算大小
                    const size = JSON.stringify(book.contents).length
                    return {
                        fileId: book.fileId,
                        bookId: book.bookId,
                        bookTitle: book.bookTitle,
                        fileName: book.fileName,
                        chapterCount: book.chapters.length,
                        cachedAt: book.cachedAt,
                        size,
                    }
                }),
            )
        }
        request.onerror = () => reject(request.error)
    })
}

/**
 * 清空所有缓存
 */
export async function clearAllCache(): Promise<void> {
    const db = await getDB()

    return new Promise((resolve, reject) => {
        const transaction = db.transaction([STORE_NAME], 'readwrite')
        const store = transaction.objectStore(STORE_NAME)
        const request = store.clear()

        request.onsuccess = () => resolve()
        request.onerror = () => reject(request.error)
    })
}

/**
 * 获取缓存统计信息
 */
export async function getCacheStats(): Promise<{
    totalBooks: number
    totalSize: number
    oldestCache: number | null
}> {
    const books = await getAllCachedBooks()

    return {
        totalBooks: books.length,
        totalSize: books.reduce((sum, book) => sum + book.size, 0),
        oldestCache: books.length > 0 ? Math.min(...books.map(b => b.cachedAt)) : null,
    }
}
