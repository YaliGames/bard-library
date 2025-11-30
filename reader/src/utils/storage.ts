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

export default localforage
