import { storage } from './storage'
import type { LocalForage } from 'localforage'

export const offlineStorage = {
    books: storage.books,
    meta: storage.meta,
}

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

export function isUsingCachedData() {
    return usingCachedData
}

export function resetCachedDataFlag() {
    usingCachedData = false
}

// Generic cache wrapper
export async function withCache<T>(
    key: string,
    fetcher: () => Promise<T>,
    store: LocalForage = storage.meta,
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
            console.warn(`[Offline] Network failed for ${key}, trying cache...`, error)
            const cached = await store.getItem<T>(key)
            if (cached) {
                usingCachedData = notifyOnCache
                return cached
            }
            throw error
        }
    } else {
        // Cache first (not used for now based on plan, but good to have)
        const cached = await store.getItem<T>(key)
        if (cached) {
            // Update cache in background
            fetcher()
                .then((data) => store.setItem(key, data))
                .catch((err) => console.warn(`[Offline] Background update failed for ${key}`, err))
            return cached
        }
        const data = await fetcher()
        await store.setItem(key, data)
        return data
    }
}
