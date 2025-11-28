import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Book, PaginatedResponse, ReadingProgress } from '@/types'

// 图书浏览状态
export const useBookStore = defineStore('book', () => {
  const books = ref<Book[]>([])
  const pagination = ref<PaginatedResponse<Book> | null>(null)
  const currentBook = ref<Book | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // 计算属性
  const hasBooks = computed(() => books.value.length > 0)
  const bookCount = computed(() => books.value.length)
  const totalBooks = computed(() => pagination.value?.total || 0)
  const currentPage = computed(() => pagination.value?.current_page || 1)
  const lastPage = computed(() => pagination.value?.last_page || 1)
  const perPage = computed(() => pagination.value?.per_page || 20)

  // Actions
  function setBooks(paginatedBooks: PaginatedResponse<Book>) {
    books.value = paginatedBooks.data
    pagination.value = paginatedBooks
  }

  function setCurrentBook(book: Book | null) {
    currentBook.value = book
  }

  function setLoading(isLoading: boolean) {
    loading.value = isLoading
  }

  function setError(err: string | null) {
    error.value = err
  }

  function addBook(book: Book) {
    books.value.push(book)
  }

  function updateBook(updatedBook: Book) {
    const index = books.value.findIndex(b => b.id === updatedBook.id)
    if (index !== -1) {
      books.value[index] = updatedBook
    }
  }

  function removeBook(bookId: number) {
    books.value = books.value.filter(b => b.id !== bookId)
  }

  return {
    // State
    books,
    pagination,
    currentBook,
    loading,
    error,

    // Getters
    hasBooks,
    bookCount,
    totalBooks,
    currentPage,
    lastPage,
    perPage,

    // Actions
    setBooks,
    setCurrentBook,
    setLoading,
    setError,
    addBook,
    updateBook,
    removeBook,
  }
})

// 阅读进度状态
export const useReadingStore = defineStore('reading', () => {
  const progress = ref<Map<number, ReadingProgress>>(new Map())
  const currentFileId = ref<number | null>(null)
  const currentChapterIndex = ref<number>(0)

  // 从 localStorage 恢复进度
  function loadProgress() {
    try {
      const saved = localStorage.getItem('reading-progress')
      if (saved) {
        const data = JSON.parse(saved)
        progress.value = new Map(Object.entries(data).map(([k, v]) => [Number(k), v as ReadingProgress]))
      }
    } catch (error) {
      console.error('Failed to load reading progress:', error)
    }
  }

  // 保存进度到 localStorage
  function saveProgress() {
    try {
      const data = Object.fromEntries(progress.value)
      localStorage.setItem('reading-progress', JSON.stringify(data))
    } catch (error) {
      console.error('Failed to save reading progress:', error)
    }
  }

  // 更新阅读进度
  function updateProgress(bookId: number, fileId: number, chapterIndex: number, position: number, percentage: number) {
    const key = bookId
    const prog: ReadingProgress = {
      bookId,
      fileId,
      chapterIndex,
      position,
      percentage,
      updatedAt: Date.now(),
    }

    progress.value.set(key, prog)
    saveProgress()
  }

  // 获取阅读进度
  function getProgress(bookId: number): ReadingProgress | null {
    return progress.value.get(bookId) || null
  }

  // 设置当前阅读位置
  function setCurrentPosition(fileId: number, chapterIndex: number) {
    currentFileId.value = fileId
    currentChapterIndex.value = chapterIndex
  }

  return {
    progress,
    currentFileId,
    currentChapterIndex,
    loadProgress,
    updateProgress,
    getProgress,
    setCurrentPosition,
  }
})

// 网络状态
export const useNetworkStore = defineStore('network', () => {
  const isOnline = ref(navigator.onLine)

  function setOnline(status: boolean) {
    isOnline.value = status
  }

  // 监听网络状态变化
  function initNetworkListener() {
    window.addEventListener('online', () => setOnline(true))
    window.addEventListener('offline', () => setOnline(false))
  }

  return {
    isOnline,
    setOnline,
    initNetworkListener,
  }
})