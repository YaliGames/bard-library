import localforage from 'localforage'

localforage.config({
    name: 'bard-reader',
    storeName: 'books_cache'
})

export const bookStorage = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'books'
})

export const fileStorage = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'files'
})

export const metaStorage = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'meta'
})

export const bookmarkStorage = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'bookmarks_offline'
})

export const offlineActionStorage = localforage.createInstance({
    name: 'bard-reader',
    storeName: 'offline_actions'
})

export const storage = {
    books: bookStorage,
    files: fileStorage,
    meta: metaStorage,
    bookmarks: bookmarkStorage,
    offlineActions: offlineActionStorage
}

export default localforage
