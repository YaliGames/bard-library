<template>
  <TxtReaderDesktop
    v-if="!isMobileView"
    @jump-to-result="handleJumpToSearchResult"
    @search-chapter="handleChapterSearch"
    @search-global="handleGlobalSearch"
  >
    <ReaderBody />
  </TxtReaderDesktop>

  <TxtReaderMobile
    v-else
    @jump-to-result="handleJumpToSearchResult"
    @search-chapter="handleChapterSearch"
    @search-global="handleGlobalSearch"
  >
    <ReaderBody />
  </TxtReaderMobile>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed, onUnmounted, nextTick, provide } from 'vue'
import type { ReaderContext } from '@/types/readerContext'
import type {
  ReaderSettings,
  SearchHighlightOptions,
  TxtContentInstance,
  SelectionAction,
  ThemeKey,
  SearchPanelInstance,
  SearchResult,
} from '@/types/reader'
import type { Chapter } from '@/api/txt'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { txtApi } from '@/api/txt'
import { progressApi, type ProgressPayload } from '@/api/progress'
import { bookmarksApi } from '@/api/bookmarks'
import type { Book, Bookmark } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import TxtReaderDesktop from '@/components/Reader/Txt/Layout/TxtReaderDesktop.vue'
import TxtReaderMobile from '@/components/Reader/Txt/Layout/TxtReaderMobile.vue'
import ReaderBody from '@/components/Reader/Txt/Shared/ReaderBody.vue'
import { useSettingsStore } from '@/stores/settings'
import {
  buildSentenceOffsets,
  findAllOccurrences,
  extractSearchPreview,
  buildSearchRegex,
  mapAbsToChapter,
  splitRangeToSegments,
  splitByLines,
} from '@/utils/reader'
import { useSimpleLoading } from '@/composables/useLoading'
import { getCachedBook, type CachedBook } from '@/utils/txtCache'

const route = useRoute()
const router = useRouter()
const settingsStore = useSettingsStore()
const userSettings = settingsStore.settings
const authStore = useAuthStore()
const isLoggedIn = computed(() => authStore.isLoggedIn)
const { loading, setLoading } = useSimpleLoading(true)
const fileId = Number(route.params.id)
const bookId = ref<number>(0)
const initialChapterIndex = ref<number | undefined>(undefined)
const LS_LAST_CHAPTER_KEY = (fid: number) => `reader.lastChapter.${fid}`

const isMobileView = ref(typeof window !== 'undefined' ? window.innerWidth < 768 : false)

function updateIsMobileView() {
  try {
    isMobileView.value = window.innerWidth < 768
  } catch {}
}

onMounted(() => {
  updateIsMobileView()
  window.addEventListener('resize', updateIsMobileView)
})
onUnmounted(() => {
  window.removeEventListener('resize', updateIsMobileView)
})

function resolveInitialContext() {
  const pBookRaw = (route.params as any)?.bookId
  const pChapRaw = (route.params as any)?.chapterIndex
  const pBook =
    typeof pBookRaw === 'string'
      ? Number(pBookRaw)
      : Number.isFinite(pBookRaw)
        ? Number(pBookRaw)
        : NaN
  const pChap =
    typeof pChapRaw === 'string'
      ? Number(pChapRaw)
      : Number.isFinite(pChapRaw)
        ? Number(pChapRaw)
        : NaN
  if (Number.isFinite(pBook)) bookId.value = Number(pBook)
  if (Number.isFinite(pChap)) initialChapterIndex.value = Number(pChap)

  // 支持通过 query 传递 chapterIndex
  const qChap = route.query.chapterIndex
  if (typeof qChap === 'string' && qChap.trim() !== '') {
    const n = Number(qChap)
    if (Number.isFinite(n)) {
      initialChapterIndex.value = Number(n)
      try {
        const newQuery = { ...route.query }
        delete (newQuery as any).chapterIndex
        router.replace({ path: route.path, query: newQuery as any }).catch(() => {})
      } catch {}
    }
  }

  try {
    if (initialChapterIndex.value == null) {
      const c = Number(localStorage.getItem(LS_LAST_CHAPTER_KEY(fileId)) || 'NaN')
      if (Number.isFinite(c)) initialChapterIndex.value = c
    }
  } catch {}
}

const chapters = ref<Chapter[]>([])
const content = ref('')
const sentences = ref<string[]>([])
// 每个句子的字符全局偏移范围，用于将全文位置映射到句内位置
let sentenceOffsets: Array<{ start: number; end: number }> = []
const err = ref('')
const currentChapterIndex = ref<number | null>(null)
const settingsVisible = ref(false)
const contentRef = ref<TxtContentInstance | null>(null)
const searchPanelRef = ref<SearchPanelInstance | null>(null)
const mobileSearchDrawerRef = ref<SearchPanelInstance | null>(null)
const leftTab = ref<'chapters' | 'bookmarks'>('chapters')
const searchVisible = ref(false) // PC 端搜索
const mobileSearchVisible = ref(false) // 移动端搜索
const searchHighlight = ref<SearchHighlightOptions | null>(null) // 当前搜索配置
const showCacheManager = ref(false)
const cachedBook = ref<CachedBook | null>(null)
const showMobileDrawer = ref(false)
const mobileDrawerDefaultTab = ref<'chapters' | 'bookmarks' | 'cache' | 'settings'>('chapters')
const mobileBottomBarVisible = ref(true)
let lastScrollY = 0
const hasPrevChapter = computed(
  () => typeof currentChapterIndex.value === 'number' && currentChapterIndex.value > 0,
)
const hasNextChapter = computed(
  () =>
    typeof currentChapterIndex.value === 'number' &&
    chapters.value.length > 0 &&
    currentChapterIndex.value < chapters.value.length - 1,
)
// 选区浮动菜单
const showSelectionMenu = ref(false)
const selectionMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })
// 高亮点击菜单
const showHighlightMenu = ref(false)
const highlightMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })
const currentHitBookmarkId = ref<number | null>(null)
const currentHitBookmark = computed(() =>
  bookmarks.value.find(b => b.id === currentHitBookmarkId.value),
)
const currentHitNote = computed(() => currentHitBookmark.value?.note || '')
const currentHitColor = computed(() => currentHitBookmark.value?.color || null)
const selectionActions = computed(() => {
  const acts: SelectionAction[] = []
  if (isLoggedIn.value) {
    if (selectionRange.value) {
      acts.push({
        key: 'highlight',
        label: '高亮',
        onClick: () => {
          highlightSelection()
          hideSelectionMenu()
        },
      })
    }
    acts.push({
      key: 'copy',
      label: '复制',
      onClick: () => {
        copySelection()
        hideSelectionMenu()
      },
    })
  } else {
    acts.push({
      key: 'copy',
      label: '复制',
      onClick: () => {
        copySelection()
        hideSelectionMenu()
      },
    })
  }
  return acts
})

// 右侧工具面板状态（用于 Desktop）
const rightPanelOpen = ref(false)
const rightPanelTab = ref<'none' | 'search' | 'cache' | 'settings'>('none')

function openRightPanel(tab: 'search' | 'cache' | 'settings') {
  rightPanelTab.value = tab
  rightPanelOpen.value = true
}

function closeRightPanel() {
  rightPanelOpen.value = false
  rightPanelTab.value = 'none'
}

const themeColors: Record<ThemeKey, { bg: string; fg: string }> = {
  light: { bg: '#ffffff', fg: '#333333' },
  sepia: { bg: '#f5ecd9', fg: '#3b2f1e' },
  dark: { bg: '#111111', fg: '#dddddd' },
}

const SETTINGS_KEY = 'reader.settings.txt'
const settings = ref<ReaderSettings>({
  fontSize: 16,
  lineHeight: 1.7,
  contentWidth: 720,
  theme: 'light',
})

function loadSettings() {
  try {
    const raw = localStorage.getItem(SETTINGS_KEY)
    if (raw) {
      const obj = JSON.parse(raw)
      settings.value = {
        fontSize: Number(obj.fontSize) || 16,
        lineHeight: Number(obj.lineHeight) || 1.7,
        contentWidth: Number(obj.contentWidth) || 720,
        theme: (['light', 'sepia', 'dark'].includes(obj.theme) ? obj.theme : 'light') as ThemeKey,
      }
    }
  } catch {
  } finally {
    hideSelectionMenu()
  }
}

function persistSettings() {
  try {
    localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings.value))
  } catch {}
}

watch(settings, persistSettings, { deep: true })

// 当前选择的句子范围 [start, end]
const selectionRange = ref<{ start: number; end: number } | null>(null)
const selectionTextBuffer = ref<string | null>(null)
// 当前章节的服务端书签记录
const book = ref<Book | {}>({})
const bookmarks = ref<Bookmark[]>([])
const filteredBookmarks = computed(() => {
  if (!bookId.value || !fileId || bookmarks.value.length === 0) return []
  return bookmarks.value.filter(b => {
    try {
      const loc = JSON.parse(b.location || '{}')
      // 优先使用 location.fileId；若没有，则回退到条目上的 file_id 字段
      const fid = Number(loc?.fileId ?? b.file_id ?? 0)
      return fid === fileId
    } catch {
      return false
    }
  })
})
// 句内精准标注：sentence index -> array of [{start,end,bookmarkId?,color?}]（句内偏移，半开区间）
const markRanges = new Map<
  number,
  Array<{ start: number; end: number; bookmarkId?: number; color?: string | null }>
>()
const markTick = ref(0)
// 章节跳转后待滚动的目标
const pendingScroll = ref<{
  chapterIndex: number
  selectionText?: string
  startSid?: number
  isSearchJump?: boolean
  matchPosition?: number
  matchLength?: number
} | null>(null)

async function loadBookmarksForChapter() {
  if (!isLoggedIn.value || !bookId.value || currentChapterIndex.value == null) return
  try {
    const bookmarksResponse = await bookmarksApi.list(bookId.value, fileId)
    const list = bookmarksResponse.bookmarks || []
    // 只在 book 信息还未加载时才从 bookmarks 接口获取
    if (!book.value || !('title' in book.value)) {
      book.value = bookmarksResponse.book || {}
    }

    bookmarks.value = list.filter(b => {
      if (!b.location) return false
      try {
        const loc = JSON.parse(b.location)
        // 仅保留 txt 格式，并且 fileId 一致
        const fid = Number(loc?.fileId ?? (b as any).file_id ?? 0)
        const okFile = fid ? fid === fileId : true
        return loc?.format === 'txt' && okFile
      } catch {
        return false
      }
    })
    markRanges.clear()
    for (const b of bookmarks.value) {
      try {
        const loc = JSON.parse(b.location || '{}')
        // 只处理 txt 格式的 location
        if (!(loc && loc.format === 'txt')) continue

        // 如果后端提供 absStart/absEnd，则映射到章节并拆分为句子范围
        if (typeof loc.absStart === 'number' && typeof loc.absEnd === 'number') {
          const absStart = Number(loc.absStart)
          const absEnd = Number(loc.absEnd)
          const mapped = mapAbsToChapter(absStart, absEnd, chapters.value)
          if (!mapped) continue

          // 仅当该书签属于当前章节才渲染高亮
          if (mapped.chapterIndex !== currentChapterIndex.value) continue

          const segs = splitRangeToSegments(mapped.localStart, mapped.localEnd, sentenceOffsets)
          for (const seg of segs) {
            const arr = markRanges.get(seg.idx) || []
            arr.push({
              start: seg.start,
              end: seg.end,
              bookmarkId: b.id,
              color: b.color || null,
            })
            markRanges.set(seg.idx, arr)
          }
        }
      } catch {}
    }
  } catch {}
}

// 加载缓存状态
async function loadCacheStatus() {
  try {
    cachedBook.value = await getCachedBook(fileId)
  } catch (error) {
    console.error('Failed to load cache status:', error)
    cachedBook.value = null
  }
}

async function loadChapters() {
  err.value = ''
  setLoading(true)
  try {
    // const response = await txtApi.listChapters(fileId)

    // if (response.book) {
    //   book.value = response.book
    //   bookId.value = response.book.id
    // }
    
    let responseChapters: any[] = []

    // 优先使用缓存的章节列表
    if (cachedBook.value && cachedBook.value.chapters.length > 0) {
      responseChapters = cachedBook.value.chapters
      if (cachedBook.value.bookId) {
        bookId.value = cachedBook.value.bookId
        if (!book.value || !('id' in book.value)) {
          book.value = { id: cachedBook.value.bookId, title: cachedBook.value.bookTitle || '' } as any
        }
      }
    } else {
      // 没有缓存请求API
      const response = await txtApi.listChapters(fileId)
      if (response.book) {
        book.value = response.book
        bookId.value = response.book.id
      }
      responseChapters = response.chapters
    }

    chapters.value = responseChapters
    // 优先使用外部明确指定的章节（例如路由参数/历史 state）
    if (typeof initialChapterIndex.value === 'number') {
      await openChapter(initialChapterIndex.value)
    } else if (bookId.value && isLoggedIn.value) {
      // 服务端进度（仅登录用户）
      try {
        const p = await progressApi.get(bookId.value, fileId)
        const loc = typeof p?.location === 'string' ? JSON.parse(p.location || '{}') : undefined
        // 优先使用 absStart 恢复
        if (loc && typeof loc.absStart === 'number') {
          const abs = Number(loc.absStart)
          let idx = 0
          for (let i = 0; i < chapters.value.length; i++) {
            const ch = chapters.value[i]
            if (abs >= ch.offset && abs < ch.offset + ch.length) {
              idx = i
              break
            }
          }
          await openChapter(idx)
        } else if (typeof loc?.chapterIndex === 'number') {
          await openChapter(Number(loc.chapterIndex))
        }
      } catch {}
    } else {
      // 本地记忆的章节
      try {
        const last = Number(localStorage.getItem(LS_LAST_CHAPTER_KEY(fileId)) || 'NaN')
        if (Number.isFinite(last)) await openChapter(last)
      } catch {}
    }
  } catch (e: any) {
    err.value = e?.message || '加载目录失败'
  } finally {
    setLoading(false)
  }
}

async function openChapter(index: number) {
  setLoading(true)
  try {
    let chapterContent: string

    // 优先使用缓存
    if (cachedBook.value && cachedBook.value.contents.has(index)) {
      chapterContent = cachedBook.value.contents.get(index)!
      // 从缓存中获取 bookId
      if (cachedBook.value.bookId && !bookId.value) {
        bookId.value = cachedBook.value.bookId
      }
    } else {
      // 从API加载
      const data = await txtApi.getChapterContent(fileId, index)
      // 若章节内容接口返回了 book_id，则以其为准（仅支持新结构）
      if ((data as any)?.book_id) {
        const b = Number((data as any).book_id)
        if (Number.isFinite(b) && b > 0) bookId.value = b
      }
      chapterContent = data.content
    }

    content.value = chapterContent
    // 使用按换行拆分，避免句子切分导致高亮位置偏移或被拆断
    sentences.value = splitByLines(chapterContent)
    sentenceOffsets = buildSentenceOffsets(sentences.value)
    currentChapterIndex.value = index
    selectionRange.value = null
    hideSelectionMenu()
    await loadBookmarksForChapter()
    // 提前结束 loading 以渲染正文，再进行定位
    setLoading(false)
    await nextTick()
    // 章节切换目录自动滚动
    if (pendingScroll.value && pendingScroll.value.chapterIndex === index) {
      const scrollOpts = {
        startSid: pendingScroll.value.startSid,
        isSearchJump: pendingScroll.value.isSearchJump || false,
        selectionText: pendingScroll.value.selectionText,
        matchPosition: pendingScroll.value.matchPosition,
        matchLength: pendingScroll.value.matchLength,
      }
      contentRef.value?.scrollToTarget(scrollOpts)
      pendingScroll.value = null
    }
    // 保存阅读进度
    try {
      localStorage.setItem(LS_LAST_CHAPTER_KEY(fileId), String(index))
    } catch {}
    if (bookId.value && isLoggedIn.value) {
      const total = chapters.value.length > 0 ? chapters.value.length : 1
      const base = chapters.value[index]?.offset ?? 0
      const payload: ProgressPayload = {
        file_id: fileId,
        progress: Math.min(1, Math.max(0, (index + 1) / total)),
        location: JSON.stringify({ format: 'txt', absStart: base }),
      }
      try {
        await progressApi.save(bookId.value, payload, fileId)
      } catch {}
    }
  } finally {
    setLoading(false)
  }
}

// 合并 mounted：先加载设置，再解析上下文并加载章节与缓存状态
onMounted(async () => {
  loadSettings()
  resolveInitialContext()
  // 先加载本地缓存状态，确保 openChapter 在有缓存时不发起网络请求
  await loadCacheStatus()
  await loadChapters()

  // 移动端滚动监听
  window.addEventListener('scroll', handleMobileScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleMobileScroll)
})

// 移动端滚动处理：向下隐藏底部栏，向上显示
function handleMobileScroll() {
  const currentScrollY = window.scrollY

  const viewportHeight = window.innerHeight
  const docHeight = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight)
  const distanceToBottom = docHeight - (currentScrollY + viewportHeight)
  const bottomThreshold = 120 // px，接近底部时保持显示

  // 如果接近底部，始终显示底部栏（覆盖向下隐藏逻辑）
  if (distanceToBottom <= bottomThreshold) {
    mobileBottomBarVisible.value = true
  } else if (currentScrollY > lastScrollY && currentScrollY > 100) {
    // 向下滚动且超过100px，隐藏底部栏
    mobileBottomBarVisible.value = false
  } else if (currentScrollY < lastScrollY) {
    // 向上滚动，显示底部栏
    mobileBottomBarVisible.value = true
  }

  lastScrollY = currentScrollY
}

function goEditChapters() {
  router.push({ name: 'admin-txt-chapters', params: { id: String(fileId) } })
}

function backToBook() {
  if (bookId.value) {
    try {
      router.push({ name: 'book-detail', params: { id: String(bookId.value) } })
    } catch {
      router.push({ name: 'books' })
    }
  } else {
    router.push({ name: 'books' })
  }
}

function goPrevChapter() {
  if (!hasPrevChapter.value) return
  const idx = (currentChapterIndex.value || 0) - 1
  openChapter(idx)
}

function goNextChapter() {
  if (!hasNextChapter.value) return
  const idx = (currentChapterIndex.value || 0) + 1
  openChapter(idx)
}

// 选区/点击事件由子组件上报
function onSelectionEvent(p: {
  show: boolean
  x?: number
  y?: number
  range?: { start: number; end: number } | null
  text?: string | null
}) {
  if (p.range) selectionRange.value = p.range
  selectionTextBuffer.value = p.text ?? null
  if (typeof p.x === 'number' && typeof p.y === 'number')
    selectionMenuPos.value = { x: p.x, y: p.y }
  showSelectionMenu.value = !!p.show
}

function onMarkClickEvent(p: {
  show: boolean
  x?: number
  y?: number
  bookmarkId?: number | null
}) {
  hideSelectionMenu()
  if (typeof p.x === 'number' && typeof p.y === 'number')
    highlightMenuPos.value = { x: p.x, y: p.y }
  currentHitBookmarkId.value = p.bookmarkId ?? null
  showHighlightMenu.value = !!(p.show && currentHitBookmarkId.value != null)
}

async function copySelection() {
  // 优先使用 selectionTextBuffer（精确的选中文本）
  let text = ''
  if (selectionTextBuffer.value) {
    text = selectionTextBuffer.value
  } else if (selectionRange.value && sentences.value.length > 0) {
    // 回退：使用句子范围
    const s = Math.min(selectionRange.value.start, selectionRange.value.end)
    const e = Math.max(selectionRange.value.start, selectionRange.value.end)
    text = sentences.value.slice(s, e + 1).join('')
  } else {
    return
  }
  try {
    await navigator.clipboard.writeText(text)
  } catch {}
  hideSelectionMenu()
}

async function highlightSelection() {
  if (
    !isLoggedIn.value ||
    !bookId.value ||
    currentChapterIndex.value == null ||
    !selectionRange.value
  )
    return
  const s = Math.min(selectionRange.value.start, selectionRange.value.end)
  const e = Math.max(selectionRange.value.start, selectionRange.value.end)

  const selectionText =
    selectionTextBuffer.value && selectionTextBuffer.value.length > 0
      ? selectionTextBuffer.value
      : sentences.value.slice(s, e + 1).join('')

  // 计算绝对偏移锚点和本地高亮范围（一次性查找所有匹配）
  const baseOffset = chapters.value[currentChapterIndex.value]?.offset ?? 0
  let absStart: number | null = null
  let absEnd: number | null = null
  const addedBySentence = new Map<number, Array<{ start: number; end: number }>>()

  if (selectionText) {
    // 一次性查找所有匹配
    const occ = findAllOccurrences(content.value, selectionText)

    // 选择最接近用户选中句子的匹配项作为实际创建的锚点：
    // 优先选中与选中句子范围有交集的匹配；若无，则回退到第一个匹配
    let chosen: { start: number; end: number } | undefined = undefined
    if (occ.length > 0) {
      const sentStart = sentenceOffsets[s]?.start ?? 0
      const sentEnd = sentenceOffsets[e]?.end ?? Infinity
      chosen = occ.find(o => !(o.end <= sentStart || o.start >= sentEnd)) || occ[0]
      absStart = baseOffset + chosen.start
      absEnd = baseOffset + chosen.end
    }

    // 1) 乐观更新：只高亮用户实际选择的匹配（不要一次性高亮所有匹配）
    if (chosen) {
      const r = chosen
      for (let i = 0; i < sentenceOffsets.length; i++) {
        const seg = sentenceOffsets[i]
        if (r.end <= seg.start || r.start >= seg.end) continue
        const localStart = Math.max(0, r.start - seg.start)
        const localEnd = Math.min(seg.end - seg.start, r.end - seg.start)
        const addArr = addedBySentence.get(i) || []
        addArr.push({ start: localStart, end: localEnd })
        addedBySentence.set(i, addArr)
      }
    }
  }
  // 应用到 markRanges
  for (const [i, arrAdded] of addedBySentence.entries()) {
    const cur = markRanges.get(i) || []
    cur.push(
      ...arrAdded.map(r => ({ start: r.start, end: r.end, bookmarkId: undefined, color: null })),
    )
    markRanges.set(i, cur)
  }
  markTick.value++
  selectionRange.value = null
  hideSelectionMenu()

  // 2) 调后端，成功则保持，不成功则回滚
  try {
    const payload: Partial<Bookmark> = {
      // 以绝对偏移作为稳定锚点；保留 selectionText 便于渲染高亮
      location: JSON.stringify({ format: 'txt', fileId: fileId, absStart, absEnd, selectionText }),
      file_id: fileId,
    }
    const b = await bookmarksApi.create(bookId.value, payload, fileId)
    bookmarks.value.push(b)
    // 回填 id/color
    for (const [i, arrAdded] of addedBySentence.entries()) {
      const cur = markRanges.get(i) || []
      for (const seg of cur) {
        if (
          arrAdded.some(a => a.start === seg.start && a.end === seg.end) &&
          seg.bookmarkId == null
        ) {
          seg.bookmarkId = b.id
          seg.color = b.color || null
        }
      }
      markRanges.set(i, cur)
    }
    markTick.value++
  } catch {
    // 回滚已添加的高亮
    for (const [i, arrAdded] of addedBySentence.entries()) {
      const cur = markRanges.get(i) || []
      if (cur.length === 0) continue
      const filtered = cur.filter(r => !arrAdded.some(a => a.start === r.start && a.end === r.end))
      if (filtered.length > 0) markRanges.set(i, filtered)
      else markRanges.delete(i)
    }
    markTick.value++
  }
}

async function jumpToBookmark(b: Bookmark) {
  try {
    const loc = JSON.parse(b.location || '{}')
    const text = typeof loc.selectionText === 'string' ? loc.selectionText : undefined

    // 优先使用后端提供的绝对偏移（absStart/absEnd）进行精确定位
    if (
      typeof loc?.absStart === 'number' &&
      typeof loc?.absEnd === 'number' &&
      chapters.value.length > 0
    ) {
      const absStart = Number(loc.absStart)
      const absEnd = Number(loc.absEnd)
      const mapped = mapAbsToChapter(absStart, absEnd, chapters.value)
      if (!mapped) return

      // 若跳转到非当前章节，先设置 pendingScroll（包含章节局部位置），并加载目标章节
      if (mapped.chapterIndex !== currentChapterIndex.value) {
        pendingScroll.value = {
          chapterIndex: mapped.chapterIndex,
          selectionText: text,
          matchPosition: mapped.localStart,
          matchLength: mapped.localEnd - mapped.localStart,
        }
        await openChapter(mapped.chapterIndex)
        return
      }

      // 当前章节：找到对应的句子索引并滚动、高亮（同时计算 endSid，确保跨句子范围也会闪烁）
      const localPos = mapped.localStart
      const localEndPos = Math.max(0, mapped.localEnd - 1)
      let targetSid: number | undefined = undefined
      let endSid: number | undefined = undefined

      for (let i = 0; i < sentenceOffsets.length; i++) {
        const seg = sentenceOffsets[i]
        if (targetSid === undefined && localPos >= seg.start && localPos < seg.end) {
          targetSid = i
        }
        if (endSid === undefined && localEndPos >= seg.start && localEndPos < seg.end) {
          endSid = i
        }
        if (targetSid !== undefined && endSid !== undefined) break
      }

      nextTick(() =>
        contentRef.value?.scrollToTarget({
          startSid: targetSid,
          endSid,
          selectionText: text,
          matchPosition: mapped.localStart,
          matchLength: mapped.localEnd - mapped.localStart,
        }),
      )
      return
    }

    return
  } catch {}
}

function onDeleteFromMenu() {
  try {
    if (!currentHitBookmark.value) return
    removeBookmarkConfirm(currentHitBookmark.value)
    hideHighlightMenu()
  } catch {}
}

async function removeBookmarkConfirm(b: Bookmark) {
  ElMessageBox.confirm('是否删除该书签？', '删除确认', {
    confirmButtonText: '确认',
    cancelButtonText: '取消',
    type: 'warning',
  })
    .then(() => {
      removeBookmark(b)
    })
    .catch(() => {})
}

async function removeBookmark(b: Bookmark) {
  try {
    await bookmarksApi.remove(bookId.value, b.id)
    // 从本地列表移除并刷新高亮
    bookmarks.value = bookmarks.value.filter(x => x.id !== b.id)
    markRanges.clear()
    await loadBookmarksForChapter()
    markTick.value++
  } catch {}
}

function hideSelectionMenu() {
  showSelectionMenu.value = false
}
function hideHighlightMenu() {
  showHighlightMenu.value = false
}
function hideAllMenus() {
  showSelectionMenu.value = false
  showHighlightMenu.value = false
}
function onWindowScroll() {
  hideAllMenus()
}
function onWindowResize() {
  hideAllMenus()
}
function onDocMouseDown() {
  hideAllMenus()
}
onMounted(() => {
  window.addEventListener('scroll', onWindowScroll, { passive: true })
  window.addEventListener('resize', onWindowResize)
  document.addEventListener('mousedown', onDocMouseDown)
})
onUnmounted(() => {
  window.removeEventListener('scroll', onWindowScroll)
  window.removeEventListener('resize', onWindowResize)
  document.removeEventListener('mousedown', onDocMouseDown)
})

// 更新本地书签信息
function updateLocalBookmark(updated: Bookmark) {
  const idx = bookmarks.value.findIndex(b => b.id === updated.id)
  if (idx >= 0) {
    bookmarks.value[idx] = Object.assign({}, bookmarks.value[idx], updated)
  }
}

async function onAddNote() {
  try {
    if (!isLoggedIn.value || !bookId.value || !currentHitBookmark.value) return
    const text = window.prompt('添加/修改批注', currentHitBookmark.value.note || '')
    if (text == null) return
    const updated = await bookmarksApi.update(bookId.value, currentHitBookmark.value.id, {
      note: text,
    })
    updateLocalBookmark(updated)
    hideHighlightMenu()
  } catch {}
}

async function onPickColor(color: string | Event) {
  try {
    if (!isLoggedIn.value || !bookId.value || !currentHitBookmark.value) return
    const picked = typeof color === 'string' ? color : (color as any)?.target?.value
    if (!picked) return
    const updated = await bookmarksApi.update(bookId.value, currentHitBookmark.value.id, {
      color: picked,
    })
    updateLocalBookmark(updated)
    // 更新 markRanges 中颜色
    for (const [sid, arr] of markRanges.entries()) {
      let changed = false
      for (const seg of arr) {
        if (seg.bookmarkId === updated.id) {
          seg.color = (updated as any).color || null
          changed = true
        }
      }
      if (changed) markRanges.set(sid, arr)
    }
    markTick.value++
    hideHighlightMenu()
  } catch {}
}

// ==================== 移动端抽屉辅助函数 ====================

function openMobileDrawer(tab: 'chapters' | 'bookmarks' | 'cache' | 'settings' = 'chapters') {
  mobileDrawerDefaultTab.value = tab
  showMobileDrawer.value = true
}

// ==================== 搜索功能 ====================

function toggleSearch() {
  // 检测设备类型,移动端打开抽屉,PC端打开弹窗
  const isMobile = window.innerWidth < 768

  if (isMobile) {
    if (mobileSearchVisible.value) {
      handleMobileSearchClose()
    } else {
      mobileSearchVisible.value = true
    }
  } else {
    if (rightPanelOpen.value) {
      closeRightPanel()
    } else {
      openRightPanel('search')
    }
  }
}

function handleSearchClose() {
  // close right-side panel (desktop) or mobile panel
  try {
    closeRightPanel()
  } catch {}
  searchVisible.value = false
  searchHighlight.value = null
}

function handleMobileSearchClose() {
  mobileSearchVisible.value = false
  searchHighlight.value = null
}

function handleJumpToSearchResult(result: SearchResult) {
  const scrollOptions = {
    startSid: result.sentenceIndex,
    matchPosition: result.position,
    matchLength: result.matchLength,
    isSearchJump: true,
  }

  if (currentChapterIndex.value === result.chapterIndex) {
    // 在当前章节，直接滚动
    nextTick(() => contentRef.value?.scrollToTarget(scrollOptions))
  } else {
    // 跳转到其他章节
    pendingScroll.value = {
      chapterIndex: result.chapterIndex,
      ...scrollOptions,
    }
    openChapter(result.chapterIndex)
  }
}

function handleChapterSearch(keyword: string, caseSensitive: boolean, wholeWord: boolean) {
  // 章节搜索时也设置高亮配置
  searchHighlight.value = { keyword, caseSensitive, wholeWord }
}

async function handleGlobalSearch(keyword: string, caseSensitive: boolean, wholeWord: boolean) {
  if (!keyword) return

  // 检查是否有缓存
  if (!cachedBook.value) {
    ElMessage.warning('全文搜索需要先缓存书籍内容，请先缓存后再试')
    // 移动端打开抽屉的缓存标签,PC端打开缓存弹窗
    const isMobile = window.innerWidth < 768
    if (isMobile) {
      openMobileDrawer('cache')
    } else {
        openRightPanel('cache')
    }
    searchPanelRef.value?.setSearching(false)
    mobileSearchDrawerRef.value?.setSearching(false)
    return
  }

  // 设置搜索高亮配置
  searchHighlight.value = { keyword, caseSensitive, wholeWord }
  searchPanelRef.value?.setSearching(true)
  mobileSearchDrawerRef.value?.setSearching(true)

  try {
    // 构建正则表达式
    const regex = buildSearchRegex(keyword, caseSensitive, wholeWord)

    const results: SearchResult[] = []

    // 使用缓存内容搜索
    for (let i = 0; i < chapters.value.length; i++) {
      const chapter = chapters.value[i]
      const chapterContent = cachedBook.value.contents.get(i)

      if (!chapterContent) continue

      try {
        // 查找所有匹配
        const matches = [...chapterContent.matchAll(regex)]

        for (const match of matches) {
          const position = match.index!
          const matchedText = match[0]
          const matchLength = matchedText.length

          // 提取预览文本（使用统一的函数）
          const preview = extractSearchPreview(chapterContent, position, matchedText)

          // 计算句子索引
          let sentenceIndex: number | undefined
          // 使用与正文一致的按换行分段策略来计算段索引（保证索引一致）
          const chapterSentences =
            i === currentChapterIndex.value ? sentences.value : splitByLines(chapterContent)

          let offset = 0
          for (let j = 0; j < chapterSentences.length; j++) {
            const len = chapterSentences[j].length
            if (position >= offset && position < offset + len) {
              sentenceIndex = j
              break
            }
            offset += len
          }

          results.push({
            chapterIndex: i,
            chapterTitle: chapter.title,
            position,
            matchLength,
            sentenceIndex,
            preview,
          })
        }
      } catch (e) {
        console.error(`搜索章节 ${i} 失败:`, e)
      }
    }

    // 将结果传递给搜索面板
    searchPanelRef.value?.setGlobalResults(results)
    mobileSearchDrawerRef.value?.setGlobalResults(results)
  } catch (e) {
    console.error('全文搜索失败:', e)
    searchPanelRef.value?.setSearching(false)
    mobileSearchDrawerRef.value?.setSearching(false)
  }
}

// 监听键盘快捷键
function onKeyDown(e: KeyboardEvent) {
  // Ctrl+F 或 Cmd+F：打开章节搜索
  if ((e.ctrlKey || e.metaKey) && e.key === 'f' && !e.shiftKey) {
    e.preventDefault()
    openRightPanel('search')
  }
  // Ctrl+Shift+F 或 Cmd+Shift+F：打开全文搜索
  else if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'F') {
    e.preventDefault()
    openRightPanel('search')
    // 可以在这里设置默认为全文搜索模式
  }
  // Esc：关闭搜索
  else if (e.key === 'Escape' && rightPanelOpen.value) {
    closeRightPanel()
  }
}

onMounted(() => {
  document.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeyDown)
})

// 提供一个 readerContext 给子组件，子组件可以通过 inject('readerContext') 访问页面级状态与方法
const readerContext = {
  // 基本数据
  chapters,
  currentChapterIndex,
  bookmarks: filteredBookmarks,
  filteredBookmarks,
  book,
  bookId,
  fileId,
  bookTitle: computed(() => {
    try {
      const b = (book as any).value ?? book
      const titleFromBook = b && typeof b === 'object' ? b.title : undefined
      if (titleFromBook) return titleFromBook
    } catch {}
    if (cachedBook && (cachedBook as any).value && (cachedBook as any).value.bookTitle) {
      return (cachedBook as any).value.bookTitle
    }
    return `文件 ${fileId}`
  }),

  // 状态与配置
  settings,
  settingsVisible,
  cachedBook,
  showCacheManager,
  loading,
  themeColors,
  userSettings,

  // 阅读内容与句子
  content,
  sentences,
  contentRef,
  sentenceOffsets,

  // 章节/书签视图相关
  leftTab,
  mobileDrawerDefaultTab,
  showMobileDrawer,
  mobileSearchVisible,
  mobileBottomBarVisible,

  // 交互状态
  searchHighlight,
  markRanges,
  markTick,
  showSelectionMenu,
  selectionMenuPos,
  selectionActions,
  showHighlightMenu,
  highlightMenuPos,
  currentHitBookmarkId,
  currentHitNote,
  currentHitColor,

  // 计算属性 / 帮助
  isLoggedIn,
  hasPrevChapter,
  hasNextChapter,
  autoScrollCategory: computed(() => userSettings.txtReader?.autoScrollCategory),

  // 引用回调
  searchPanelRef,
  mobileSearchDrawerRef,
  searchVisible,

  // 方法（页面内实现）
  openChapter,
  openMobileDrawer,
  goPrevChapter,
  goNextChapter,
  backToBook,
  goEditChapters,
  jumpToBookmark,
  removeBookmarkConfirm,
  removeBookmark,
  loadBookmarksForChapter,
  loadCacheStatus,
  toggleSearch,
  handleMobileSearchClose,

  // 搜索/跳转相关
  handleSearchClose,
  handleJumpToSearchResult,
  handleChapterSearch,
  handleGlobalSearch,

  // 右侧工具面板（Search / Cache / Settings）
  rightPanelOpen,
  rightPanelTab,
  openRightPanel,
  closeRightPanel,

  // 选区 / 高亮相关
  onSelectionEvent,
  onMarkClickEvent,
  onAddNote,
  onPickColor,
  onDeleteFromMenu,
}

provide('readerContext', readerContext as unknown as ReaderContext)
</script>
