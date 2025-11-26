<template>
  <section class="flex gap-4 items-start p-4 md:p-6">
    <!-- 左侧导览 -->
    <aside
      class="hidden md:block w-[320px] shrink-0 sticky top-4 self-start max-h-[calc(100vh-2rem)]"
    >
      <div
        class="bg-white dark:bg-[var(--el-bg-color-overlay)] border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden flex flex-col max-h-full"
      >
        <div
          class="flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
        >
          <h3 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">导览</h3>
          <div class="flex">
            <el-button @click="showCacheManager = true">
              <span class="material-symbols-outlined text-base">
                {{ cachedBook ? 'check' : 'download' }}
              </span>
              {{ cachedBook ? '已缓存' : '缓存' }}
            </el-button>
            <el-button type="primary" v-permission="'books.edit'" @click="goEditChapters">
              编辑章节
            </el-button>
          </div>
        </div>
        <div class="p-2 flex-1 overflow-y-auto">
          <TxtNavTabs
            v-model:tab="leftTab"
            :chapters="chapters"
            :current-chapter-index="currentChapterIndex"
            :is-logged-in="isLoggedIn"
            :filtered-bookmarks="filteredBookmarks"
            :auto-scroll-category="userSettings.txtReader?.autoScrollCategory"
            @open-chapter="openChapter"
            @jump="jumpToBookmark"
            @remove="removeBookmarkConfirm"
          />
        </div>
      </div>
    </aside>
    <!-- 主阅读区 -->
    <main class="flex-1 min-w-0 pb-20 md:pb-0">
      <!-- 顶部工具栏（PC 显示） -->
      <div class="hidden md:flex items-center justify-between mb-3 sticky top-4 z-10">
        <div
          class="bg-white dark:bg-[var(--el-bg-color-overlay)] border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm w-full"
        >
          <div class="flex items-center justify-between px-3 py-2">
            <h3 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">
              {{ 'title' in book && book.title ? book.title : '正文' }}
            </h3>
            <div class="flex items-center">
              <TxtChapterNav
                :has-prev="hasPrevChapter"
                :has-next="hasNextChapter"
                @prev="goPrevChapter"
                @back="backToBook"
                @next="goNextChapter"
              />
            </div>
            <div class="flex items-center gap-2">
              <el-button @click="toggleSearch">
                <span class="material-symbols-outlined text-base">search</span>
              </el-button>
              <el-button type="primary" @click="settingsVisible = true">阅读设置</el-button>
            </div>
          </div>
        </div>
      </div>
      <div
        class="border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm"
        :style="{
          '--reader-font-size': settings.fontSize + 'px',
          '--reader-line-height': String(settings.lineHeight),
          '--reader-content-width': settings.contentWidth + 'px',
          '--reader-bg': themeColors[settings.theme].bg,
          '--reader-fg': themeColors[settings.theme].fg,
          background: 'var(--reader-bg)',
          color: 'var(--reader-fg)',
          padding: '8px 0',
        }"
      >
        <template v-if="loading">
          <div style="max-width: var(--reader-content-width); margin: 0 auto; padding: 12px 16px">
            <el-skeleton :rows="8" animated />
          </div>
        </template>
        <template v-else-if="!content">
          <div style="max-width: var(--reader-content-width); margin: 0 auto; padding: 24px 16px">
            <el-empty description="选择章节开始阅读">
              <el-button type="primary" class="md:!hidden" @click="openMobileDrawer('chapters')">
                打开目录
              </el-button>
            </el-empty>
          </div>
        </template>
        <template v-else>
          <div class="max-w-[var(--reader-content-width)] mx-auto px-4">
            <div class="overflow-hidden">
              <TxtReaderContent
                ref="contentRef"
                :content="content"
                :sentences="sentences"
                :mark-ranges="markRanges"
                :mark-tick="markTick"
                :search-highlight="searchHighlight"
                @selection="onSelectionEvent"
                @mark-click="onMarkClickEvent"
              />
              <div class="px-3 py-2 mb-24">
                <TxtChapterNav
                  :has-prev="hasPrevChapter"
                  :has-next="hasNextChapter"
                  @prev="goPrevChapter"
                  @back="backToBook"
                  @next="goNextChapter"
                />
              </div>
            </div>
          </div>
          <SelectionMenu
            :show="showSelectionMenu"
            :x="selectionMenuPos.x"
            :y="selectionMenuPos.y"
            :actions="selectionActions"
          />
          <HighlightMenu
            :show="showHighlightMenu"
            :x="highlightMenuPos.x"
            :y="highlightMenuPos.y"
            :note="currentHitNote"
            :current-color="currentHitColor"
            @add-note="onAddNote"
            @pick-color="onPickColor"
            @delete="onDeleteFromMenu"
          />
        </template>
      </div>
      <!-- PC 端设置抽屉 -->
      <el-drawer
        v-model="settingsVisible"
        title="阅读设置"
        direction="rtl"
        size="320px"
        class="hidden md:block"
      >
        <div class="flex flex-col gap-4">
          <div>
            <div class="mb-2">主题</div>
            <el-radio-group v-model="settings.theme">
              <el-radio-button label="light">明亮</el-radio-button>
              <el-radio-button label="sepia">米黄</el-radio-button>
              <el-radio-button label="dark">深色</el-radio-button>
            </el-radio-group>
          </div>
          <div>
            <div class="flex justify-between mb-1">
              <span>字体大小</span>
              <span>{{ settings.fontSize }}px</span>
            </div>
            <el-slider v-model="settings.fontSize" :min="14" :max="24" :step="1" />
          </div>
          <div>
            <div class="flex justify-between mb-1">
              <span>行高</span>
              <span>{{ settings.lineHeight.toFixed(1) }}</span>
            </div>
            <el-slider v-model="settings.lineHeight" :min="1.4" :max="2.2" :step="0.1" />
          </div>
          <div>
            <div class="flex justify-between mb-1">
              <span>内容宽度</span>
              <span>{{ settings.contentWidth }}px</span>
            </div>
            <el-slider v-model="settings.contentWidth" :min="560" :max="960" :step="10" />
          </div>
        </div>
      </el-drawer>
    </main>

    <!-- PC 端搜索面板 -->
    <SearchPanel
      ref="searchPanelRef"
      :visible="searchVisible"
      :current-chapter-content="content"
      :current-chapter-index="currentChapterIndex"
      :chapters="chapters"
      :sentences="sentences"
      class="hidden md:block"
      @close="handleSearchClose"
      @jump-to-result="handleJumpToSearchResult"
      @search-chapter="handleChapterSearch"
      @search-global="handleGlobalSearch"
    />
  </section>

  <!-- 移动端搜索抽屉 -->
  <MobileSearchDrawer
    ref="mobileSearchDrawerRef"
    :visible="mobileSearchVisible"
    :current-chapter-content="content"
    :current-chapter-index="currentChapterIndex"
    :chapters="chapters"
    :sentences="sentences"
    class="md:hidden"
    @close="handleMobileSearchClose"
    @jump-to-result="handleJumpToSearchResult"
    @search-chapter="handleChapterSearch"
    @search-global="handleGlobalSearch"
  />

  <!-- PC 端缓存管理对话框 -->
  <CacheManager
    v-model="showCacheManager"
    :file-id="fileId"
    :book-title="'title' in book && book.title ? book.title : `文件 ${fileId}`"
    :chapters="chapters"
    @cache-complete="loadCacheStatus"
    class="hidden md:block"
  />

  <!-- 移动端底部菜单 -->
  <MobileBottomBar
    class="md:hidden"
    :visible="mobileBottomBarVisible"
    :cached="!!cachedBook"
    :has-prev="hasPrevChapter"
    :has-next="hasNextChapter"
    @prev="goPrevChapter"
    @menu="openMobileDrawer('chapters')"
    @next="goNextChapter"
    @search="toggleSearch"
    @settings="openMobileDrawer('settings')"
  />

  <!-- 移动端目录/书签抽屉 -->
  <MobileDrawer
    :visible="showMobileDrawer"
    :chapters="chapters"
    :bookmarks="filteredBookmarks"
    :current-chapter-index="currentChapterIndex"
    :file-id="fileId"
    :book-title="'title' in book && book.title ? book.title : `文件 ${fileId}`"
    :settings="settings"
    :cached-book="cachedBook"
    :default-tab="mobileDrawerDefaultTab"
    :auto-scroll-category="userSettings.txtReader?.autoScrollCategory"
    @close="showMobileDrawer = false"
    @open-chapter="openChapter"
    @jump-bookmark="jumpToBookmark"
    @remove-bookmark="removeBookmarkConfirm"
    @cache-complete="loadCacheStatus"
    @update-theme="theme => (settings.theme = theme as any)"
    @update-font-size="size => (settings.fontSize = size)"
    @update-line-height="height => (settings.lineHeight = height)"
    @update-content-width="width => (settings.contentWidth = width)"
  />
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { txtApi } from '@/api/txt'
import { progressApi, type ProgressPayload } from '@/api/progress'
import { bookmarksApi } from '@/api/bookmarks'
import type { Book, Bookmark } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import HighlightMenu from '@/components/Reader/Shared/HighlightMenu.vue'
import SelectionMenu from '@/components/Reader/Shared/SelectionMenu.vue'
import TxtNavTabs from '@/components/Reader/Txt/TxtNavTabs.vue'
import TxtChapterNav from '@/components/Reader/Txt/TxtChapterNav.vue'
import TxtReaderContent from '@/components/Reader/Txt/TxtContent.vue'
import SearchPanel from '@/components/Reader/Shared/SearchPanel.vue'
import CacheManager from '@/components/Reader/Shared/CacheManager.vue'
import MobileBottomBar from '@/components/Reader/Mobile/MobileBottomBar.vue'
import MobileDrawer from '@/components/Reader/Mobile/MobileDrawer.vue'
import MobileSearchDrawer from '@/components/Reader/Mobile/MobileSearchDrawer.vue'
import { useSettingsStore } from '@/stores/settings'
import {
  splitIntoSentences,
  buildSentenceOffsets,
  findAllOccurrences,
  extractSearchPreview,
  buildSearchRegex,
} from '@/utils/reader'
import { useSimpleLoading } from '@/composables/useLoading'
import { getCachedBook, type CachedBook } from '@/utils/txtCache'
import type { SearchResult } from '@/types/reader'

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

interface Chapter {
  index: number
  title?: string | null
  offset: number
  length: number
}
const chapters = ref<Chapter[]>([])
const content = ref('')
const sentences = ref<string[]>([])
// 每个句子的字符全局偏移范围，用于将全文位置映射到句内位置
let sentenceOffsets: Array<{ start: number; end: number }> = []
const err = ref('')
const currentChapterIndex = ref<number | null>(null)
const settingsVisible = ref(false)
const contentRef = ref<{
  scrollToTarget: (opts: {
    startSid?: number
    endSid?: number
    selectionText?: string
    isSearchJump?: boolean
  }) => void
} | null>(null)
const searchPanelRef = ref<{
  setGlobalResults: (results: any[]) => void
  setSearching: (value: boolean) => void
} | null>(null)
const mobileSearchDrawerRef = ref<{
  setGlobalResults: (results: any[]) => void
  setSearching: (value: boolean) => void
} | null>(null)
const leftTab = ref<'chapters' | 'bookmarks'>('chapters')
const searchVisible = ref(false) // PC 端搜索
const mobileSearchVisible = ref(false) // 移动端搜索
const searchHighlight = ref<{ keyword: string; caseSensitive: boolean; wholeWord: boolean } | null>(
  null,
) // 当前搜索配置
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
  const acts: Array<{ key: string; label: string; onClick: () => void }> = []
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

type ThemeKey = 'light' | 'sepia' | 'dark'
const themeColors: Record<ThemeKey, { bg: string; fg: string }> = {
  light: { bg: '#ffffff', fg: '#333333' },
  sepia: { bg: '#f5ecd9', fg: '#3b2f1e' },
  dark: { bg: '#111111', fg: '#dddddd' },
}

const SETTINGS_KEY = 'reader.settings.txt'
const settings = ref<{
  fontSize: number
  lineHeight: number
  contentWidth: number
  theme: ThemeKey
}>({
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
      const fid = Number(loc?.fileId ?? (b as any).file_id ?? 0)
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
        if (typeof loc.selectionText === 'string' && loc.selectionText) {
          // 1) 在全文中查找所有出现位置
          const occ = findAllOccurrences(content.value, loc.selectionText)
          // 2) 将每个全文范围拆分映射到句内范围
          for (const r of occ) {
            // 找到覆盖的句子
            for (let i = 0; i < sentenceOffsets.length; i++) {
              const seg = sentenceOffsets[i]
              if (r.end <= seg.start || r.start >= seg.end) continue
              const localStart = Math.max(0, r.start - seg.start)
              const localEnd = Math.min(seg.end - seg.start, r.end - seg.start)
              const arr = markRanges.get(i) || []
              arr.push({
                start: localStart,
                end: localEnd,
                bookmarkId: b.id,
                color: (b as any).color || null,
              })
              markRanges.set(i, arr)
            }
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
    const response = await txtApi.listChapters(fileId)

    // 从响应中提取 book 和 chapters
    if (response.book) {
      book.value = response.book
      bookId.value = response.book.id
    } else {
      // 向后兼容：从 chapters 中提取 book_id
      try {
        const b =
          response.chapters && response.chapters.length > 0 && response.chapters[0].book_id
            ? Number(response.chapters[0].book_id)
            : 0
        if (Number.isFinite(b) && b > 0) {
          bookId.value = b
        }
      } catch {}
    }

    chapters.value = response.chapters
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
      // 若章节内容接口也返回了 book_id，则以其为准
      try {
        const b = Number((data as any).book_id || 0)
        if (Number.isFinite(b) && b > 0) {
          bookId.value = b
        }
      } catch {}
      chapterContent = data.content
    }

    content.value = chapterContent
    sentences.value = splitIntoSentences(chapterContent)
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

onMounted(loadSettings)
// 先初始化鉴权状态，再解析初始上下文，最后加载章节
onMounted(async () => {
  resolveInitialContext()
  await loadChapters()
  await loadCacheStatus() // 加载缓存状态

  // 移动端滚动监听
  window.addEventListener('scroll', handleMobileScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleMobileScroll)
})

// 移动端滚动处理：向下隐藏底部栏，向上显示
function handleMobileScroll() {
  const currentScrollY = window.scrollY

  if (currentScrollY > lastScrollY && currentScrollY > 100) {
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
    // 一次性查找所有匹配，避免重复计算
    const occ = findAllOccurrences(content.value, selectionText)

    // 计算绝对偏移（使用第一个匹配）
    if (occ.length > 0) {
      absStart = baseOffset + occ[0].start
      absEnd = baseOffset + occ[0].end
    }

    // 1) 乐观更新：先把 UI 高亮起来
    for (const r of occ) {
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
      file_id: fileId as any,
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
          seg.color = (b as any).color || null
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

    // 优先使用绝对偏移锚点
    if (typeof loc?.absStart === 'number' && chapters.value.length > 0) {
      const abs = Number(loc.absStart)
      // 找到所属章节
      let targetChapter = 0
      for (let i = 0; i < chapters.value.length; i++) {
        const ch = chapters.value[i]
        if (abs >= ch.offset && abs < ch.offset + ch.length) {
          targetChapter = i
          break
        }
      }

      // 如果跳转到非当前章节，需要先加载章节内容
      if (currentChapterIndex.value !== targetChapter) {
        pendingScroll.value = {
          chapterIndex: targetChapter,
          selectionText: text,
        }
        await openChapter(targetChapter)
        return
      }

      // 当前章节，计算句子索引
      const base = chapters.value[targetChapter]?.offset ?? 0
      const localPos = Math.max(0, abs - base)

      let targetSid: number | undefined = undefined
      for (let i = 0; i < sentenceOffsets.length; i++) {
        const seg = sentenceOffsets[i]
        if (localPos >= seg.start && localPos < seg.end) {
          targetSid = i
          break
        }
      }

      nextTick(() => contentRef.value?.scrollToTarget({ startSid: targetSid, selectionText: text }))
      return
    }

    // 兼容旧结构 chapterIndex
    if (typeof loc?.chapterIndex === 'number') {
      const targetChapter = Number(loc.chapterIndex)
      const targetSid = typeof loc.startSid === 'number' ? Number(loc.startSid) : undefined
      const endSid = typeof loc.endSid === 'number' ? Number(loc.endSid) : undefined

      if (currentChapterIndex.value === targetChapter) {
        nextTick(() =>
          contentRef.value?.scrollToTarget({ startSid: targetSid, endSid, selectionText: text }),
        )
      } else {
        pendingScroll.value = {
          chapterIndex: targetChapter,
          selectionText: text,
          startSid: targetSid,
        }
        await openChapter(targetChapter)
      }
    }
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
    if (searchVisible.value) {
      handleSearchClose()
    } else {
      searchVisible.value = true
    }
  }
}

function handleSearchClose() {
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
      showCacheManager.value = true
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
          const chapterSentences =
            i === currentChapterIndex.value ? sentences.value : splitIntoSentences(chapterContent)

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
    searchVisible.value = true
  }
  // Ctrl+Shift+F 或 Cmd+Shift+F：打开全文搜索
  else if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'F') {
    e.preventDefault()
    searchVisible.value = true
    // 可以在这里设置默认为全文搜索模式
  }
  // Esc：关闭搜索
  else if (e.key === 'Escape' && searchVisible.value) {
    searchVisible.value = false
  }
}

onMounted(() => {
  document.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeyDown)
})
</script>
