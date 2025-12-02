import { ref, shallowRef, nextTick } from 'vue'
import { txtApi } from '@/api/txt'
import type { Chapter } from '@/api/txt'
import type { Book } from '@/api/types'
import { useSimpleLoading } from '@/composables/useLoading'
import { getCachedBook, type CachedBook } from '@/utils/txtCache'
import { buildSentenceOffsets, splitByLines } from '@/utils/reader'
import { ElMessage } from 'element-plus'

export function useReaderCore(fileId: number) {
  const { loading, setLoading } = useSimpleLoading(true)

  const bookId = ref<number>(0)
  const bookTitle = ref<string>('')
  const book = ref<Book | {}>({})
  const chapters = ref<Chapter[]>([])
  const content = ref('')
  const sentences = ref<string[]>([])
  const sentenceOffsets = shallowRef<Array<{ start: number; end: number }>>([])

  const cachedBook = ref<CachedBook | null>(null)
  const showCacheManager = ref(false)
  const err = ref('')

  async function loadChapters(options: { keepLoading?: boolean } = {}) {
    try {
      setLoading(true)
      const res = await txtApi.listChapters(fileId)
      chapters.value = res.chapters
      if (res.book) {
        bookId.value = res.book.id
        bookTitle.value = res.book.title
        book.value = res.book as any
      }
    } catch (e: any) {
      console.error(e)
      err.value = e.message || '加载章节列表失败'
      ElMessage.error('加载章节列表失败')
    } finally {
      if (!options.keepLoading) {
        setLoading(false)
      }
    }
  }

  async function loadChapterContent(index: number) {
    if (index < 0) return
    if (chapters.value.length > 0 && index >= chapters.value.length) return

    try {
      setLoading(true)
      let chapterContent = ''

      if (cachedBook.value && cachedBook.value.contents.has(index)) {
        chapterContent = cachedBook.value.contents.get(index)!
        if (cachedBook.value.bookId && !bookId.value) {
          bookId.value = cachedBook.value.bookId
        }
      } else {
        const data = await txtApi.getChapterContent(fileId, index)
        if ((data as any)?.book_id) {
          const b = Number((data as any).book_id)
          if (Number.isFinite(b) && b > 0) bookId.value = b
        }
        chapterContent = data.content
      }

      content.value = chapterContent
      sentences.value = splitByLines(chapterContent)
      sentenceOffsets.value = buildSentenceOffsets(sentences.value)

      if (typeof window !== 'undefined') {
        nextTick(() => {
          window.scrollTo(0, 0)
        })
      }
    } catch (e: any) {
      console.error(e)
      err.value = e.message || '加载章节内容失败'
    } finally {
      setLoading(false)
    }
  }

  async function loadCacheStatus() {
    try {
      cachedBook.value = await getCachedBook(fileId)
      if (cachedBook.value?.bookTitle) {
        bookTitle.value = cachedBook.value.bookTitle
      }
    } catch (error) {
      console.error('Failed to load cache status:', error)
      cachedBook.value = null
    }
  }

  function resolveInitialContext() {
    // Stub for compatibility
  }

  return {
    fileId,
    bookId,
    bookTitle,
    book,
    chapters,
    content,
    sentences,
    sentenceOffsets,
    loading,
    setLoading,
    cachedBook,
    showCacheManager,
    err,
    resolveInitialContext,
    loadChapters,
    loadChapterContent,
    loadCacheStatus,
  }
}
