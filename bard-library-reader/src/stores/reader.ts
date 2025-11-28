import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Book, Chapter, Bookmark, ReadingProgress } from '@/types'
import { txtApi } from '@/api/txt'
import { getCachedBook, type CachedBook } from '@/utils/cache'

// 类型定义
export interface HighlightRange {
  start: number
  end: number
  bookmarkId?: number
  color?: string | null
  isSearch?: boolean
}

export interface SearchResult {
  chapterIndex: number
  chapterTitle?: string
  position: number
  matchLength: number
  sentenceIndex?: number
  preview: string
}

export interface ReaderSettings {
  fontSize: number
  lineHeight: number
  contentWidth: number
  theme: 'light' | 'sepia' | 'dark'
}

export const useReaderStore = defineStore('reader', () => {
  // ===== 基础状态 =====
  const fileId = ref<number>(0)
  const bookId = ref<number>(0)
  const book = ref<Book | null>(null)
  const chapters = ref<Chapter[]>([])
  const currentChapterIndex = ref<number | null>(null)
  const content = ref<string>('')
  const sentences = ref<string[]>([])

  // ===== UI状态 =====
  const isLoading = ref<boolean>(false)
  const searchVisible = ref<boolean>(false)
  const settingsVisible = ref<boolean>(false)
  const mobileDrawerVisible = ref<boolean>(false)
  const mobileDrawerTab = ref<'chapters' | 'bookmarks' | 'settings' | 'cache'>('chapters')

  // ===== 阅读设置 =====
  const settings = ref<ReaderSettings>({
    fontSize: 16,
    lineHeight: 1.7,
    contentWidth: 720,
    theme: 'light'
  })

  // ===== 搜索状态 =====
  const searchState = ref({
    keyword: '',
    caseSensitive: false,
    wholeWord: false,
    isSearching: false,
    results: [] as SearchResult[],
    currentResultIndex: -1
  })

  // ===== 书签和高亮 =====
  const bookmarks = ref<Bookmark[]>([])
  const markRanges = ref(new Map<number, HighlightRange[]>())

  // ===== 缓存状态 =====
  const cachedBook = ref<CachedBook | null>(null)

  // ===== 计算属性 =====
  const hasPrevChapter = computed(() =>
    currentChapterIndex.value !== null && currentChapterIndex.value > 0
  )

  const hasNextChapter = computed(() =>
    currentChapterIndex.value !== null &&
    chapters.value.length > 0 &&
    currentChapterIndex.value < chapters.value.length - 1
  )

  const currentChapter = computed(() =>
    currentChapterIndex.value !== null ? chapters.value[currentChapterIndex.value] : null
  )

  const isCached = computed(() => cachedBook.value !== null)

  // ===== Actions =====

  /**
   * 加载章节列表
   */
  const loadChapters = async () => {
    if (!fileId.value) return

    isLoading.value = true
    try {
      const response = await txtApi.listChapters(fileId.value)

      if (response.book) {
        book.value = response.book
        bookId.value = response.book.id
      }

      chapters.value = response.chapters.map((chapter, index) => ({
        ...chapter,
        index
      }))

      // 加载缓存状态
      await loadCacheStatus()

      // 自动打开第一章或恢复进度
      await autoOpenChapter()

    } catch (error) {
      console.error('Failed to load chapters:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * 加载缓存状态
   */
  const loadCacheStatus = async () => {
    try {
      cachedBook.value = await getCachedBook(fileId.value)
    } catch (error) {
      console.error('Failed to load cache status:', error)
      cachedBook.value = null
    }
  }

  /**
   * 自动打开章节（第一章或恢复进度）
   */
  const autoOpenChapter = async () => {
    if (chapters.value.length === 0) return

    // 优先恢复本地保存的章节
    const savedChapter = getSavedChapterIndex()
    if (savedChapter !== null && savedChapter < chapters.value.length) {
      await openChapter(savedChapter)
      return
    }

    // 默认打开第一章
    await openChapter(0)
  }

  /**
   * 打开指定章节
   */
  const openChapter = async (index: number) => {
    if (index < 0 || index >= chapters.value.length) return

    isLoading.value = true
    try {
      let chapterContent: string

      // 优先使用缓存
      if (cachedBook.value && cachedBook.value.contents.has(index)) {
        chapterContent = cachedBook.value.contents.get(index)!
      } else {
        // 从API加载
        const data = await txtApi.getChapterContent(fileId.value, index)
        chapterContent = data.content

        // 更新bookId（如果API返回）
        if (data.book_id && !bookId.value) {
          bookId.value = data.book_id
        }
      }

      content.value = chapterContent
      sentences.value = splitIntoSentences(chapterContent)
      currentChapterIndex.value = index

      // 保存阅读进度到本地存储
      saveChapterProgress(index)

      // 加载书签（如果已登录且有bookId）
      if (bookId.value) {
        await loadBookmarks()
      }

    } catch (error) {
      console.error('Failed to open chapter:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * 加载书签
   */
  const loadBookmarks = async () => {
    // TODO: 实现书签加载逻辑
    // 这里需要调用bookmarks API
  }

  /**
   * 保存章节进度到本地存储
   */
  const saveChapterProgress = (index: number) => {
    try {
      localStorage.setItem(`reader.chapter.${fileId.value}`, String(index))
    } catch (error) {
      console.error('Failed to save chapter progress:', error)
    }
  }

  /**
   * 获取保存的章节索引
   */
  const getSavedChapterIndex = (): number | null => {
    try {
      const saved = localStorage.getItem(`reader.chapter.${fileId.value}`)
      return saved ? Number(saved) : null
    } catch (error) {
      return null
    }
  }

  /**
   * 切换到上一章
   */
  const goPrevChapter = async () => {
    if (hasPrevChapter.value && currentChapterIndex.value !== null) {
      await openChapter(currentChapterIndex.value - 1)
    }
  }

  /**
   * 切换到下一章
   */
  const goNextChapter = async () => {
    if (hasNextChapter.value && currentChapterIndex.value !== null) {
      await openChapter(currentChapterIndex.value + 1)
    }
  }

  /**
   * 切换搜索面板
   */
  const toggleSearch = () => {
    searchVisible.value = !searchVisible.value
  }

  /**
   * 切换设置面板
   */
  const toggleSettings = () => {
    settingsVisible.value = !settingsVisible.value
  }

  /**
   * 打开移动端抽屉
   */
  const openMobileDrawer = (tab: 'chapters' | 'bookmarks' | 'settings' | 'cache' = 'chapters') => {
    mobileDrawerTab.value = tab
    mobileDrawerVisible.value = true
  }

  /**
   * 关闭移动端抽屉
   */
  const closeMobileDrawer = () => {
    mobileDrawerVisible.value = false
  }

  return {
    // 状态
    fileId,
    bookId,
    book,
    chapters,
    currentChapterIndex,
    content,
    sentences,
    isLoading,
    searchVisible,
    settingsVisible,
    mobileDrawerVisible,
    mobileDrawerTab,
    settings,
    searchState,
    bookmarks,
    markRanges,
    cachedBook,

    // 计算属性
    hasPrevChapter,
    hasNextChapter,
    currentChapter,
    isCached,

    // Actions
    loadChapters,
    loadCacheStatus,
    openChapter,
    goPrevChapter,
    goNextChapter,
    toggleSearch,
    toggleSettings,
    openMobileDrawer,
    closeMobileDrawer,
    loadBookmarks
  }
})

// ===== 工具函数 =====

/**
 * 句子切分（简单中文/英文标点）
 */
function splitIntoSentences(text: string): string[] {
  if (!text) return []
  const parts: string[] = []
  let buf = ''
  for (const ch of text) {
    buf += ch
    if (/^[。！？!?;；]\s?$/.test(ch)) {
      parts.push(buf)
      buf = ''
    }
  }
  if (buf) parts.push(buf)
  return parts
}</content>
<parameter name="filePath">D:\Github\bard-library\bard-library-reader\src\stores\reader.ts