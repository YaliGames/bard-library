<template>
  <section class="flex gap-4 items-start p-4 md:p-6">
    <!-- 左侧导览 -->
    <aside class="hidden md:block w-[320px] shrink-0">
      <div class="bg-white dark:bg-[var(--el-bg-color-overlay)] border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-200 dark:border-gray-700">
          <h3 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">导览</h3>
          <el-button type="primary" v-if="isAdmin" @click="goEditChapters">编辑章节</el-button>
        </div>
        <div class="p-2">
          <ReaderNavTabs v-model:tab="leftTab" :chapters="chapters" :current-chapter-index="currentChapterIndex"
            :is-logged-in="isLoggedIn" :filtered-bookmarks="filteredBookmarks" 
            :auto-scroll-category="userSettings.txtReader?.autoScrollCategory"
            @open-chapter="openChapter"
            @jump="jumpToBookmark" @remove="removeBookmarkConfirm" />
        </div>
      </div>
    </aside>
    <!-- 主阅读区 -->
    <main class="flex-1 min-w-0">
      <!-- 顶部工具栏（PC 显示） -->
      <div class="hidden md:flex items-center justify-between mb-3">
        <div class="bg-white dark:bg-[var(--el-bg-color-overlay)] border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm w-full">
          <div class="flex items-center justify-between px-3 py-2">
            <h3 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">{{ 'title' in book && book.title ? book.title : '正文' }}</h3>
            <div class="flex items-center">
              <ChapterNav
                :has-prev="hasPrevChapter"
                :has-next="hasNextChapter"
                @prev="goPrevChapter"
                @back="backToBook"
                @next="goNextChapter"
              />
            </div>
            <div class="flex items-center">
              <el-button type="primary" @click="settingsVisible = true">阅读设置</el-button>
            </div>
          </div>
        </div>
      </div>
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm" :style="{
        '--reader-font-size': settings.fontSize + 'px',
        '--reader-line-height': String(settings.lineHeight),
        '--reader-content-width': settings.contentWidth + 'px',
        '--reader-bg': themeColors[settings.theme].bg,
        '--reader-fg': themeColors[settings.theme].fg,
        background: 'var(--reader-bg)',
        color: 'var(--reader-fg)',
        padding: '8px 0',
      }">
        <template v-if="loading">
          <div style="max-width:var(--reader-content-width); margin:0 auto; padding: 12px 16px;">
            <el-skeleton :rows="8" animated />
          </div>
        </template>
        <template v-else-if="!content">
          <div style="max-width:var(--reader-content-width); margin:0 auto; padding: 24px 16px;">
            <el-empty description="选择章节开始阅读">
              <el-button type="primary" class="md:!hidden" @click="leftDrawerVisible = true">打开目录</el-button>
            </el-empty>
          </div>
        </template>
        <template v-else>
          <div class="max-w-[var(--reader-content-width)] mx-auto px-4">
            <div class="overflow-hidden">
              <ReaderContent
                ref="contentRef"
                :content="content"
                :sentences="sentences"
                :mark-ranges="markRanges"
                :mark-tick="markTick"
                @selection="onSelectionEvent"
                @mark-click="onMarkClickEvent"
              />
              <div class="px-3 py-2 mb-24">
                <ChapterNav
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
      <el-drawer v-model="settingsVisible" title="阅读设置" direction="rtl" size="320px">
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
              <span>字体大小</span><span>{{ settings.fontSize }}px</span>
            </div>
            <el-slider v-model="settings.fontSize" :min="14" :max="24" :step="1" />
          </div>
          <div>
            <div class="flex justify-between mb-1">
              <span>行高</span><span>{{ settings.lineHeight.toFixed(1) }}</span>
            </div>
            <el-slider v-model="settings.lineHeight" :min="1.4" :max="2.2" :step="0.1" />
          </div>
          <div>
            <div class="flex justify-between mb-1">
              <span>内容宽度</span><span>{{ settings.contentWidth }}px</span>
            </div>
            <el-slider v-model="settings.contentWidth" :min="560" :max="960" :step="10" />
          </div>
        </div>
      </el-drawer>
      <!-- 移动端悬浮设置按钮 -->
      <div class="fixed right-4 bottom-4 z-10 md:hidden">
        <el-button type="primary" class="px-3 py-2 rounded-full" @click="settingsVisible = true">设置</el-button>
      </div>
      <!-- 移动端悬浮目录按钮 -->
      <div class="fixed left-4 bottom-4 z-10 md:hidden">
        <el-button type="primary" class="px-3 py-2 rounded-full" @click="leftDrawerVisible = true">目录</el-button>
      </div>
    </main>
  </section>
  <!-- 移动端：左侧导览抽屉，仅小屏显示 -->
  <el-drawer v-model="leftDrawerVisible" title="导览" direction="ltr" size="80%" class="md:!hidden">
    <div class="px-2">
      <ReaderNavTabs v-model:tab="leftTab" :chapters="chapters" :current-chapter-index="currentChapterIndex"
        :is-logged-in="isLoggedIn" :filtered-bookmarks="filteredBookmarks"
        :auto-scroll-category="userSettings.txtReader?.autoScrollCategory"
        @open-chapter="(i) => { openChapter(i); leftDrawerVisible = false }"
        @jump="(b) => { jumpToBookmark(b); leftDrawerVisible = false }" @remove="removeBookmarkConfirm" />
    </div>
  </el-drawer>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import { txtApi } from '@/api/txt'
import { progressApi, type ProgressPayload } from '@/api/progress'
import { bookmarksApi } from '@/api/bookmarks'
import type { Book, Bookmark } from '@/api/types'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import ReaderNavTabs from '@/components/Reader/NavTabs.vue'
import HighlightMenu from '@/components/Reader/HighlightMenu.vue'
import SelectionMenu from '@/components/Reader/SelectionMenu.vue'
import ReaderContent from '@/components/Reader/Content.vue'
import ChapterNav from '@/components/Reader/ChapterNav.vue'
import { useSettingsStore } from '@/stores/settings'
import { splitIntoSentences, buildSentenceOffsets, findAllOccurrences } from '@/utils/reader'

const route = useRoute()
const router = useRouter()
const { state: userSettings } = useSettingsStore()
const { loggedIn, isRole } = useAuthStore()
const isLoggedIn = loggedIn
const isAdmin = computed(()=> isRole('admin'))
const fileId = Number(route.params.id)
const bookId = ref<number>(0)
const initialChapterIndex = ref<number | undefined>(undefined)
const LS_LAST_CHAPTER_KEY = (fid: number) => `reader.lastChapter.${fid}`

function resolveInitialContext() {
  const pBookRaw = (route.params as any)?.bookId
  const pChapRaw = (route.params as any)?.chapterIndex
  const pBook = typeof pBookRaw === 'string' ? Number(pBookRaw) : Number.isFinite(pBookRaw) ? Number(pBookRaw) : NaN
  const pChap = typeof pChapRaw === 'string' ? Number(pChapRaw) : Number.isFinite(pChapRaw) ? Number(pChapRaw) : NaN
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
  } catch { }
}

interface Chapter { index: number; title?: string | null; offset: number; length: number }
const chapters = ref<Chapter[]>([])
const content = ref('')
const sentences = ref<string[]>([])
// 每个句子的字符全局偏移范围，用于将全文位置映射到句内位置
let sentenceOffsets: Array<{ start: number; end: number }> = []
const loading = ref(true)
const err = ref('')
const currentChapterIndex = ref<number | null>(null)
const settingsVisible = ref(false)
const leftDrawerVisible = ref(false)
const contentRef = ref<{ scrollToTarget: (opts: { startSid?: number; endSid?: number; selectionText?: string }) => void } | null>(null)
const leftTab = ref<'chapters' | 'bookmarks'>('chapters')
const hasPrevChapter = computed(() => typeof currentChapterIndex.value === 'number' && currentChapterIndex.value > 0)
const hasNextChapter = computed(() => typeof currentChapterIndex.value === 'number' && chapters.value.length > 0 && currentChapterIndex.value < chapters.value.length - 1)
// 选区浮动菜单
const showSelectionMenu = ref(false)
const selectionMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })
// 高亮点击菜单
const showHighlightMenu = ref(false)
const highlightMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })
const currentHitBookmarkId = ref<number | null>(null)
const currentHitNote = computed(() => bookmarks.value.find(b => b.id === currentHitBookmarkId.value)?.note || '')
const currentHitColor = computed(() => bookmarks.value.find(b => b.id === currentHitBookmarkId.value)?.color || null)
const selectionActions = computed(() => {
  const acts: Array<{ key: string; label: string; onClick: () => void }> = []
  if (isLoggedIn.value) {
    if (selectionRange.value) {
      acts.push({ key: 'highlight', label: '高亮', onClick: () => { highlightSelection(); hideSelectionMenu() } })
    }
    acts.push({ key: 'copy', label: '复制', onClick: () => { copySelection(); hideSelectionMenu() } })
  } else {
    acts.push({ key: 'copy', label: '复制', onClick: () => { copySelection(); hideSelectionMenu() } })
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
const settings = ref<{ fontSize: number; lineHeight: number; contentWidth: number; theme: ThemeKey }>({
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
  } catch { }
  finally { hideSelectionMenu() }
}

function persistSettings() {
  try { localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings.value)) } catch { }
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
    } catch { return false }
  })
})
// 句内精准标注：sentence index -> array of [{start,end,bookmarkId?,color?}]（句内偏移，半开区间）
const markRanges = new Map<number, Array<{ start: number; end: number; bookmarkId?: number; color?: string | null }>>()
const markTick = ref(0)
// 章节跳转后待滚动的目标
const pendingScroll = ref<{ chapterIndex: number; selectionText?: string; startSid?: number } | null>(null)

async function loadBookmarksForChapter() {
  if (!isLoggedIn.value || !bookId.value || currentChapterIndex.value == null) return
  try {
    const bookmarksResponse = await bookmarksApi.list(bookId.value)
    const list = bookmarksResponse.bookmarks || []
    book.value = bookmarksResponse.book || {}

    bookmarks.value = list.filter(b => {
      if (!b.location) return false
      try {
        const loc = JSON.parse(b.location)
        // 仅保留 txt 格式，并且 fileId 一致
        const fid = Number(loc?.fileId ?? (b as any).file_id ?? 0)
        const okFile = fid ? (fid === fileId) : true
        return loc?.format === 'txt' && okFile
      } catch { return false }
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
              arr.push({ start: localStart, end: localEnd, bookmarkId: b.id, color: (b as any).color || null })
              markRanges.set(i, arr)
            }
          }
        }
      } catch { }
    }
  } catch { }
}

async function loadChapters() {
  err.value = ''
  loading.value = true
  try {
    const list = await txtApi.listChapters(fileId)
    try {
      const b = (list && list.length > 0 && (list[0] as any).book_id) ? Number((list[0] as any).book_id) : 0
      if (Number.isFinite(b) && b > 0) {
        bookId.value = b
      }
    } catch { }
    chapters.value = list
    // 优先使用外部明确指定的章节（例如路由参数/历史 state）
    if (typeof initialChapterIndex.value === 'number') {
      await openChapter(initialChapterIndex.value)
    } else if (bookId.value && isLoggedIn.value) {
      // 服务端进度（仅登录用户）
      try {
        const p = await progressApi.get(bookId.value)
        const loc = typeof p?.location === 'string' ? (JSON.parse(p.location || '{}')) : undefined
        // 优先使用 absStart 恢复
        if (loc && typeof loc.absStart === 'number') {
          const abs = Number(loc.absStart)
          let idx = 0
          for (let i = 0; i < chapters.value.length; i++) {
            const ch = chapters.value[i]
            if (abs >= ch.offset && abs < ch.offset + ch.length) { idx = i; break }
          }
          await openChapter(idx)
        } else if (typeof loc?.chapterIndex === 'number') {
          await openChapter(Number(loc.chapterIndex))
        }
      } catch { }
    } else {
      // 本地记忆的章节
      try {
        const last = Number(localStorage.getItem(LS_LAST_CHAPTER_KEY(fileId)) || 'NaN')
        if (Number.isFinite(last)) await openChapter(last)
      } catch { }
    }
  } catch (e: any) { err.value = e?.message || '加载目录失败' }
  finally { loading.value = false }
}

async function openChapter(index: number) {
  loading.value = true
  try {
    const data = await txtApi.getChapterContent(fileId, index)
    // 若章节内容接口也返回了 book_id，则以其为准
    try {
      const b = Number((data as any).book_id || 0)
      if (Number.isFinite(b) && b > 0) {
        bookId.value = b
      }
    } catch { }
    content.value = data.content
    sentences.value = splitIntoSentences(data.content)
    sentenceOffsets = buildSentenceOffsets(sentences.value)
    currentChapterIndex.value = index
    selectionRange.value = null
    hideSelectionMenu()
    await loadBookmarksForChapter()
    // 提前结束 loading 以渲染正文，再进行定位
    loading.value = false
    await nextTick()
    // 章节切换目录自动滚动
    if (pendingScroll.value && pendingScroll.value.chapterIndex === index) {
      const sid = typeof pendingScroll.value.startSid === 'number' ? pendingScroll.value.startSid : undefined
      if (typeof sid === 'number') {
        contentRef.value?.scrollToTarget({ startSid: sid })
      } else if (pendingScroll.value.selectionText) {
        contentRef.value?.scrollToTarget({ selectionText: pendingScroll.value.selectionText })
      }
      pendingScroll.value = null
    }
    // 保存阅读进度
    try { localStorage.setItem(LS_LAST_CHAPTER_KEY(fileId), String(index)) } catch { }
    if (bookId.value && isLoggedIn.value) {
      const total = chapters.value.length > 0 ? chapters.value.length : 1
      const base = chapters.value[index]?.offset ?? 0
      const payload: ProgressPayload = {
        file_id: fileId,
        progress: Math.min(1, Math.max(0, (index + 1) / total)),
        location: JSON.stringify({ format: 'txt', absStart: base })
      }
      try { await progressApi.save(bookId.value, payload) } catch { }
    }
  } finally { loading.value = false }
}

onMounted(loadSettings)
// 先初始化鉴权状态，再解析初始上下文，最后加载章节
onMounted(async () => {
  await initAuthState()
  resolveInitialContext()
  await loadChapters()
})

function goEditChapters() {
  router.push({ name: 'admin-txt-chapters', params: { id: String(fileId) } })
}

function backToBook() {
  if (bookId.value) {
    try { router.push({ name: 'book-detail', params: { id: String(bookId.value) } }) } catch {
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
function onSelectionEvent(p: { show: boolean; x?: number; y?: number; range?: { start:number; end:number } | null; text?: string | null }) {
  if (p.range) selectionRange.value = p.range
  selectionTextBuffer.value = p.text ?? null
  if (typeof p.x === 'number' && typeof p.y === 'number') selectionMenuPos.value = { x: p.x, y: p.y }
  showSelectionMenu.value = !!p.show
}

function onMarkClickEvent(p: { show: boolean; x?: number; y?: number; bookmarkId?: number | null }) {
  hideSelectionMenu()
  if (typeof p.x === 'number' && typeof p.y === 'number') highlightMenuPos.value = { x: p.x, y: p.y }
  currentHitBookmarkId.value = p.bookmarkId ?? null
  showHighlightMenu.value = !!(p.show && currentHitBookmarkId.value != null)
}

async function copySelection() {
  if (sentences.value.length === 0 && !selectionTextBuffer.value) return
  let text = ''
  if (selectionRange.value && sentences.value.length > 0) {
    const s = Math.min(selectionRange.value.start, selectionRange.value.end)
    const e = Math.max(selectionRange.value.start, selectionRange.value.end)
    text = sentences.value.slice(s, e + 1).join('')
  } else if (selectionTextBuffer.value) {
    text = selectionTextBuffer.value
  } else {
    return
  }
  try { await navigator.clipboard.writeText(text) } catch { }
  hideSelectionMenu()
}

async function highlightSelection() {
  if (!isLoggedIn.value || !bookId.value || currentChapterIndex.value == null || !selectionRange.value) return
  const s = Math.min(selectionRange.value.start, selectionRange.value.end)
  const e = Math.max(selectionRange.value.start, selectionRange.value.end)

  const selectionText = (selectionTextBuffer.value && selectionTextBuffer.value.length > 0)
    ? selectionTextBuffer.value
    : sentences.value.slice(s, e + 1).join('')

  // 计算绝对偏移锚点（基于当前章节的起始 offset）
  const baseOffset = chapters.value[currentChapterIndex.value]?.offset ?? 0
  let absStart: number | null = null
  let absEnd: number | null = null
  if (selectionText) {
    const occ = findAllOccurrences(content.value, selectionText)
    if (occ.length > 0) {
      absStart = baseOffset + occ[0].start
      absEnd = baseOffset + occ[0].end
    }
  }

  // 1) 乐观更新：先把 UI 高亮起来
  const addedBySentence = new Map<number, Array<{ start: number; end: number }>>()
  if (selectionText) {
    const occ = findAllOccurrences(content.value, selectionText)
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
    cur.push(...arrAdded.map(r => ({ start: r.start, end: r.end, bookmarkId: undefined, color: null })))
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
    const b = await bookmarksApi.create(bookId.value, payload)
    bookmarks.value.push(b)
    // 回填 id/color
    for (const [i, arrAdded] of addedBySentence.entries()) {
      const cur = markRanges.get(i) || []
      for (const seg of cur) {
        if (arrAdded.some(a => a.start === seg.start && a.end === seg.end) && seg.bookmarkId == null) {
          seg.bookmarkId = b.id
          seg.color = (b as any).color || null
        }
      }
      markRanges.set(i, cur)
    }
  } catch (err) {
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

function jumpToBookmark(b: Bookmark) {
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
        if (abs >= ch.offset && abs < ch.offset + ch.length) { targetChapter = i; break }
      }
      const base = chapters.value[targetChapter]?.offset ?? 0
      const localPos = Math.max(0, abs - base)
      // 映射到句子索引
      let targetSid: number | undefined = undefined
      for (let i = 0; i < sentenceOffsets.length; i++) {
        const seg = sentenceOffsets[i]
        if (localPos >= seg.start && localPos < seg.end) { targetSid = i; break }
      }
      if (currentChapterIndex.value === targetChapter) {
        nextTick(() => contentRef.value?.scrollToTarget({ startSid: targetSid, selectionText: text }))
      } else {
        pendingScroll.value = { chapterIndex: targetChapter, selectionText: text, startSid: targetSid }
        openChapter(targetChapter)
      }
      return
    }
    // 兼容旧结构 chapterIndex
    if (typeof loc?.chapterIndex === 'number') {
      const targetChapter = Number(loc.chapterIndex)
      const targetSid = typeof loc.startSid === 'number' ? Number(loc.startSid) : undefined
      const endSid = typeof loc.endSid === 'number' ? Number(loc.endSid) : undefined
      if (currentChapterIndex.value === targetChapter) {
        nextTick(() => contentRef.value?.scrollToTarget({ startSid: targetSid, endSid, selectionText: text }))
      } else {
        pendingScroll.value = { chapterIndex: targetChapter, selectionText: text, startSid: targetSid }
        openChapter(targetChapter)
      }
    }
  } catch { }
}

function onDeleteFromMenu() {
  try {
    if (!currentHitBookmarkId.value) return
    const b = bookmarks.value.find((x: any) => x.id === currentHitBookmarkId.value)
    if (!b) return
    removeBookmarkConfirm(b)
    hideHighlightMenu()
  } catch { }
}

async function removeBookmarkConfirm(b: Bookmark) {
  ElMessageBox.confirm(
    '是否删除该书签？',
    '删除确认',
    {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    }
  )
    .then(() => {
      removeBookmark(b);
    })
    .catch(() => { })
}

async function removeBookmark(b: Bookmark) {
  try {
    await bookmarksApi.remove(bookId.value, b.id)
    // 从本地列表移除并刷新高亮
    bookmarks.value = bookmarks.value.filter(x => x.id !== b.id)
    markRanges.clear()
    await loadBookmarksForChapter()
    markTick.value++
  } catch { }
}

async function initAuthState() {
  if (!isLoggedIn.value) { return }
  try {
    const u: any = await authApi.me()
    const role = u?.role || ''
  } catch {
    // 忽略失败，保持登录态基于 token 存在
  }
}

function hideSelectionMenu() { showSelectionMenu.value = false }
function hideHighlightMenu() { showHighlightMenu.value = false }
function onWindowScroll() { hideSelectionMenu(); hideHighlightMenu() }
function onWindowResize() { hideSelectionMenu(); hideHighlightMenu() }
function onDocMouseDown() { hideSelectionMenu(); hideHighlightMenu() }
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

async function onAddNote() {
  try {
    if (!isLoggedIn.value || !bookId.value || currentHitBookmarkId.value == null) return
    const bm = bookmarks.value.find(b => b.id === currentHitBookmarkId.value)
    const prev = bm?.note || ''
    const text = window.prompt('添加/修改批注', prev || '')
    if (text == null) return
    const updated = await bookmarksApi.update(bookId.value, currentHitBookmarkId.value, { note: text })
    const idx = bookmarks.value.findIndex(b => b.id === updated.id)
    if (idx >= 0) bookmarks.value[idx] = Object.assign({}, bookmarks.value[idx], updated)
    hideHighlightMenu()
  } catch { }
}

async function onPickColor(color: string | Event) {
  try {
    if (!isLoggedIn.value || !bookId.value || currentHitBookmarkId.value == null) return
    const picked = typeof color === 'string' ? color : (color as any)?.target?.value
    if (!picked) return
    const updated = await bookmarksApi.update(bookId.value, currentHitBookmarkId.value, { color: picked })
    // 更新本地 bookmarks
    const idx = bookmarks.value.findIndex(b => b.id === updated.id)
    if (idx >= 0) bookmarks.value[idx] = Object.assign({}, bookmarks.value[idx], updated)
    // 更新 markRanges 中颜色
    for (const [sid, arr] of markRanges.entries()) {
      let changed = false
      for (const seg of arr) {
        if (seg.bookmarkId === updated.id) { seg.color = (updated as any).color || null; changed = true }
      }
      if (changed) markRanges.set(sid, arr)
    }
    markTick.value++
    hideHighlightMenu()
  } catch { }
}
</script>
