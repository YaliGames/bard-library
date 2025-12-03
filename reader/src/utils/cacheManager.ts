import localforage from 'localforage'
import type { Book } from '@/api/types'

// Configure localforage instances
const bookStore = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'books',
})

const metaStore = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'meta', // authors, tags, shelves
})

const fileStore = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'files',
})

const bookmarkStore = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'bookmarks_offline',
})

const offlineActionStore = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'offline_actions',
})

// Helper to generate cache keys
export function getCacheKey(prefix: string, params?: any): string {
    if (!params) return prefix
    // Sort keys to ensure consistent cache keys
    const sortedKeys = Object.keys(params).sort()
    const queryPart = sortedKeys
        .map((k) => `${k}=${JSON.stringify(params[k])}`)
        .join('&')
    return `${prefix}?${queryPart}`
}

// Track if we're using cached data
let usingCachedData = false

export const CacheManager = {
    // Storage Instances
    storage: {
        books: bookStore,
        meta: metaStore,
        files: fileStore,
        bookmarks: bookmarkStore,
        offlineActions: offlineActionStore,
    },

    // State
    isUsingCachedData: () => usingCachedData,
    resetCachedDataFlag: () => { usingCachedData = false },

    // Generic Cache Wrapper
    async withCache<T>(
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
                // Update cache in background
                fetcher()
                    .then((data) => store.setItem(key, data))
                    .catch((err) => console.warn(`[CacheManager] Background update failed for ${key}`, err))
                return cached
            }
            const data = await fetcher()
            await store.setItem(key, data)
            return data
        }
    },

    // Management
    async clearAll() {
        await Promise.all([
            bookStore.clear(),
            metaStore.clear(),
            fileStore.clear(),
            bookmarkStore.clear(),
            offlineActionStore.clear(),
            // We need to import clearTxtCache dynamically or move it to a separate cleanup utility
            // For now, let's keep it here but we might need to refactor txtCache to not depend on CacheManager if it does
        ])
        // Dynamic import to avoid circular dependency if txtCache imports CacheManager
        const { clearAllCache } = await import('./txtCache')
        await clearAllCache()
    },

    async getStats() {
        const { getCacheStats } = await import('./txtCache')
        const txtStats = await getCacheStats()
        // TODO: Add stats for other stores if needed
        return {
            txt: txtStats,
        }
    }
}
