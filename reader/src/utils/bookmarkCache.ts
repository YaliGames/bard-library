import { storage } from './storage'
import { getCacheKey } from './offline'

export const bookmarkCache = {
    async getOfflineBookmarks(bookId: number, fileId?: number): Promise<any[]> {
        // Get cached server bookmarks
        const key = getCacheKey('bookmarks-list', { bookId, fileId })
        const cached = (await storage.meta.getItem<{ bookmarks: any[] }>(key))?.bookmarks || []

        // Get local offline bookmarks
        const localKeys = await storage.bookmarks.keys()
        const localBookmarks: any[] = []
        for (const k of localKeys) {
            if (k.startsWith(`temp-${bookId}-`)) {
                const b = await storage.bookmarks.getItem(k)
                if (b) localBookmarks.push(b)
            }
        }

        // Get pending delete actions
        const actionKeys = await storage.offlineActions.keys()
        const deletedIds = new Set<number>()
        for (const k of actionKeys) {
            const action = await storage.offlineActions.getItem<any>(k)
            if (action && action.type === 'delete' && action.bookId === bookId) {
                deletedIds.add(action.bookmarkId)
            }
        }

        // Merge: cached + local - deleted
        return [...cached.filter(b => !deletedIds.has(b.id)), ...localBookmarks]
    },

    async addOfflineBookmark(bookId: number, fileId: number, payload: any): Promise<any> {
        const tempId = -Date.now() // Negative ID for temp
        const bookmark = {
            id: tempId,
            book_id: bookId,
            file_id: fileId,
            ...payload,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            is_offline: true,
        }
        // Save to local store
        await storage.bookmarks.setItem(`temp-${bookId}-${tempId}`, bookmark)
        // Record action
        await storage.offlineActions.setItem(`create-${tempId}`, {
            type: 'create',
            bookId,
            fileId,
            payload,
            tempId,
            timestamp: Date.now(),
        })
        return bookmark
    },

    async removeOfflineBookmark(bookId: number, bookmarkId: number): Promise<void> {
        if (bookmarkId < 0) {
            // It's a temp bookmark, just remove from local store and action queue
            await storage.bookmarks.removeItem(`temp-${bookId}-${bookmarkId}`)
            await storage.offlineActions.removeItem(`create-${bookmarkId}`)
        } else {
            // It's a server bookmark, record delete action
            await storage.offlineActions.setItem(`delete-${bookmarkId}`, {
                type: 'delete',
                bookId,
                bookmarkId,
                timestamp: Date.now(),
            })
        }
    },

    async updateOfflineBookmark(bookId: number, bookmarkId: number, payload: any): Promise<any> {
        if (bookmarkId < 0) {
            // Update temp bookmark
            const key = `temp-${bookId}-${bookmarkId}`
            const b = await storage.bookmarks.getItem<any>(key)
            if (b) {
                const updated = { ...b, ...payload, updated_at: new Date().toISOString() }
                await storage.bookmarks.setItem(key, updated)
                // Update the create action payload too if possible, or add update action?
                // Simpler: just update the create action if it exists
                const createAction = await storage.offlineActions.getItem<any>(`create-${bookmarkId}`)
                if (createAction) {
                    createAction.payload = { ...createAction.payload, ...payload }
                    await storage.offlineActions.setItem(`create-${bookmarkId}`, createAction)
                }
                return updated
            }
        } else {
            // Server bookmark, record update action
            await storage.offlineActions.setItem(`update-${bookmarkId}-${Date.now()}`, {
                type: 'update',
                bookId,
                bookmarkId,
                payload,
                timestamp: Date.now(),
            })
        }
    },

    async sync(api: any) {
        const keys = await storage.offlineActions.keys()
        if (keys.length === 0) return

        console.log('[BookmarkCache] Syncing bookmarks...', keys.length)
        // Sort by timestamp
        const actions = []
        for (const k of keys) {
            const a = await storage.offlineActions.getItem<any>(k)
            if (a) actions.push({ key: k, ...a })
        }
        actions.sort((a, b) => a.timestamp - b.timestamp)

        for (const action of actions) {
            try {
                if (action.type === 'create') {
                    await api.create(action.bookId, action.payload, action.fileId)
                    // Remove temp bookmark and action
                    await storage.bookmarks.removeItem(`temp-${action.bookId}-${action.tempId}`)
                    await storage.offlineActions.removeItem(action.key)
                } else if (action.type === 'delete') {
                    await api.remove(action.bookId, action.bookmarkId)
                    await storage.offlineActions.removeItem(action.key)
                } else if (action.type === 'update') {
                    await api.update(action.bookId, action.bookmarkId, action.payload)
                    await storage.offlineActions.removeItem(action.key)
                }
            } catch (e) {
                console.error('[BookmarkCache] Sync failed for action', action, e)
                // Keep in queue? Or remove if 4xx?
            }
        }
    }
}
