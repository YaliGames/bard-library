import { offlineStorage } from './offline'
import type { Book } from '@/api/types'
import type { BookListResponse } from '@/api/books'

/**
 * Get all cached book list pages
 */
export async function getCachedBookListPages(): Promise<{
    page: number
    sort: string
    order: string
    bookCount: number
    cachedAt: number
}[]> {
    const keys = await offlineStorage.books.keys()
    const bookListKeys = keys.filter(k => k.startsWith('books-list?'))

    const pages = await Promise.all(
        bookListKeys.map(async (key) => {
            const data = await offlineStorage.books.getItem<BookListResponse>(key)
            if (!data) return null

            // Parse key to extract params
            const params = new URLSearchParams(key.split('?')[1])
            const page = parseInt(params.get('page') || '1')
            const sort = params.get('sort') || 'created'
            const order = params.get('order') || 'desc'

            return {
                page,
                sort,
                order,
                bookCount: data.data.length,
                cachedAt: Date.now(), // We don't store timestamp, so use current time
            }
        })
    )

    return pages.filter((p): p is NonNullable<typeof p> => p !== null)
        .sort((a, b) => a.page - b.page)
}

/**
 * Get all cached books from all pages
 */
export async function getAllCachedBooks(): Promise<Book[]> {
    const keys = await offlineStorage.books.keys()
    const bookListKeys = keys.filter(k => k.startsWith('books-list?'))

    const allBooks: Book[] = []
    const bookMap = new Map<number, Book>()

    for (const key of bookListKeys) {
        const data = await offlineStorage.books.getItem<BookListResponse>(key)
        if (data && data.data) {
            data.data.forEach(book => {
                if (!bookMap.has(book.id)) {
                    bookMap.set(book.id, book)
                }
            })
        }
    }

    return Array.from(bookMap.values())
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
    let result = [...books]

    // Search query
    if (filters.q) {
        const query = filters.q.toLowerCase()
        result = result.filter(book =>
            book.title.toLowerCase().includes(query) ||
            book.authors?.some(a => a.name.toLowerCase().includes(query))
        )
    }

    // Author filter
    if (filters.authorId) {
        result = result.filter(book =>
            book.authors?.some(a => a.id === filters.authorId)
        )
    }

    // Tag filter
    if (filters.tagIds && filters.tagIds.length > 0) {
        result = result.filter(book =>
            book.tags?.some(t => filters.tagIds!.includes(t.id))
        )
    }

    // Shelf filter
    if (filters.shelfId) {
        result = result.filter(book =>
            book.shelves?.some(s => s.id === filters.shelfId)
        )
    }

    // Read state filter
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

    // Rating filter
    if (filters.ratingRange) {
        const [min, max] = filters.ratingRange
        result = result.filter(book => {
            const rating = book.rating || 0
            return rating >= min && rating <= max
        })
    }

    return result
}
