import { ref, computed, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { progressApi } from '@/api/progress'
import type { useReaderCore } from '@/composables/reader/useReaderCore'

export function useReaderNavigation(
  fileId: number,
  core: ReturnType<typeof useReaderCore>
) {
  const router = useRouter()
  const route = useRoute()
  
  const currentChapterIndex = ref<number | null>(null)
  const LS_LAST_CHAPTER_KEY = `reader.lastChapter.${fileId}`

  const hasPrevChapter = computed(() => 
    typeof currentChapterIndex.value === 'number' && currentChapterIndex.value > 0
  )
  
  const hasNextChapter = computed(() => 
    typeof currentChapterIndex.value === 'number' && 
    core.chapters.value.length > 0 && 
    currentChapterIndex.value < core.chapters.value.length - 1
  )

  function getInitialChapterIndex(): number {
    // 1. Try query param
    const qChap = route.query.chapterIndex
    if (typeof qChap === 'string') {
      const n = Number(qChap)
      if (Number.isFinite(n)) return n
    }
    
    // 2. Try LocalStorage
    try {
      const local = localStorage.getItem(LS_LAST_CHAPTER_KEY)
      if (local) {
        const n = Number(local)
        if (Number.isFinite(n)) return n
      }
    } catch {}
    
    return 0
  }

  async function openChapter(index: number) {
    // 允许 index 为 0
    if (index < 0) return
    
    // 如果章节列表已加载，检查边界
    if (core.chapters.value.length > 0 && index >= core.chapters.value.length) {
      console.warn('Chapter index out of bounds:', index)
      return
    }

    try {
      await core.loadChapterContent(index)
      currentChapterIndex.value = index
      
      // 移除 URL 中的 chapterIndex 参数 (如果存在)
      if (route.query.chapterIndex) {
        const newQuery = { ...route.query }
        delete newQuery.chapterIndex
        router.replace({ query: newQuery }).catch(() => {})
      }
      
      // 保存本地进度
      try {
        localStorage.setItem(LS_LAST_CHAPTER_KEY, index.toString())
      } catch {}
      
      // 上报服务端进度
      if (core.bookId.value) {
        const total = core.chapters.value.length || 1
        const base = core.chapters.value[index]?.offset ?? 0
        const payload = {
          file_id: fileId,
          progress: Math.min(1, Math.max(0, (index + 1) / total)),
          location: JSON.stringify({ format: 'txt', absStart: base })
        }
        progressApi.save(core.bookId.value, payload, fileId).catch(() => {})
      }
      
    } catch (e) {
      console.error('Failed to open chapter:', e)
    }
  }

  async function initializeNavigation() {
    // 1. 优先检查 URL 参数
    const qChap = route.query.chapterIndex
    if (typeof qChap === 'string') {
      const n = Number(qChap)
      if (Number.isFinite(n)) {
        await openChapter(n)
        return
      }
    }

    // 2. 检查服务端进度 (需要 bookId)
    // 注意：这里假设 core.loadChapters 已经被调用，bookId 可能已经有了，或者我们需要等待它
    // 但通常 initializeNavigation 会在 loadChapters 之后调用
    if (core.bookId.value) {
      try {
        const p = await progressApi.get(core.bookId.value, fileId)
        if (p) {
          const loc = typeof p.location === 'string' ? JSON.parse(p.location) : p.location
          // 优先使用 absStart
          if (loc && typeof loc.absStart === 'number') {
             // 需要根据 absStart 找到对应章节
             const abs = Number(loc.absStart)
             const chapters = core.chapters.value
             let idx = 0
             for (let i = 0; i < chapters.length; i++) {
               const ch = chapters[i]
               if (abs >= ch.offset && abs < ch.offset + ch.length) {
                 idx = i
                 break
               }
             }
             await openChapter(idx)
             return
          } else if (typeof loc?.chapterIndex === 'number') {
             await openChapter(Number(loc.chapterIndex))
             return
          }
        }
      } catch (e) {
        console.error('Failed to load progress:', e)
      }
    }

    // 3. 检查本地存储
    try {
      const local = localStorage.getItem(LS_LAST_CHAPTER_KEY)
      if (local) {
        const n = Number(local)
        if (Number.isFinite(n)) {
          await openChapter(n)
          return
        }
      }
    } catch {}

    // 4. 默认第一章
    await openChapter(0)
  }

  function goPrevChapter() {
    if (hasPrevChapter.value && currentChapterIndex.value !== null) {
      openChapter(currentChapterIndex.value - 1)
    }
  }

  function goNextChapter() {
    if (hasNextChapter.value && currentChapterIndex.value !== null) {
      openChapter(currentChapterIndex.value + 1)
    }
  }

  function backToBook() {
    if (core.bookId.value) {
      try {
        router.push({ name: 'book-detail', params: { id: String(core.bookId.value) } })
      } catch {
        router.push({ name: 'books' })
      }
    } else {
      router.push({ name: 'books' })
    }
  }

  function goEditChapters() {
    router.push({ name: 'admin-txt-chapters', params: { id: String(fileId) } })
  }

  return {
    currentChapterIndex,
    hasPrevChapter,
    hasNextChapter,
    getInitialChapterIndex,
    openChapter,
    goPrevChapter,
    goNextChapter,
    backToBook,
    goEditChapters,
    initializeNavigation
  }
}
