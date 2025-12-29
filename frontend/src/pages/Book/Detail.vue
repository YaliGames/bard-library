<template>
  <section class="container mx-auto px-4 py-4 max-w-6xl">
    <div v-if="loading">
      <!-- 头部骨架 -->
      <div class="flex flex-col md:flex-row gap-6 md:gap-8 bg-white shadow-sm rounded-lg p-6">
        <!-- 左侧封面骨架（移动端居中） -->
        <div class="w-full md:w-48 flex justify-center md:justify-start mb-4 md:mb-0">
          <el-skeleton animated :loading="true">
            <template #template>
              <el-skeleton-item variant="image" class="w-full h-64 rounded" />
            </template>
          </el-skeleton>
        </div>
        <!-- 右侧详情骨架 -->
        <div class="w-full md:w-3/4 md:pl-8 flex flex-col justify-between">
          <el-skeleton animated :loading="true">
            <template #template>
              <div class="mb-2">
                <el-skeleton-item variant="text" class="w-3/5 h-8" />
              </div>
              <div class="mb-4">
                <el-skeleton-item variant="text" class="w-2/5 h-4" />
              </div>
              <div class="flex items-center gap-4 mt-3">
                <el-skeleton-item variant="text" class="w-64 h-6" />
                <el-skeleton-item variant="rect" class="w-16 h-6 rounded" />
              </div>
              <div class="grid grid-cols-2 gap-y-1 gap-x-6 mt-5">
                <el-skeleton-item variant="text" class="w-full h-4 mb-2" />
                <el-skeleton-item variant="text" class="w-full h-4 mb-2" />
                <el-skeleton-item variant="text" class="w-full h-4 mb-2" />
                <el-skeleton-item variant="text" class="w-full h-4 mb-2" />
                <el-skeleton-item variant="text" class="w-full h-4 mb-2" />
                <el-skeleton-item variant="text" class="w-full h-4 mb-2" />
              </div>
              <div class="flex gap-3 mt-6">
                <el-skeleton-item variant="rect" class="w-32 h-10 rounded" />
                <el-skeleton-item variant="rect" class="w-32 h-10 rounded" />
                <el-skeleton-item variant="rect" class="w-32 h-10 rounded" />
                <el-skeleton-item variant="rect" class="w-32 h-10 rounded" />
              </div>
            </template>
          </el-skeleton>
        </div>
      </div>

      <!-- body骨架 -->
      <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <el-skeleton animated :loading="true">
            <template #template>
              <el-skeleton-item variant="text" class="w-20 h-5 mb-2" />
              <div class="bg-white rounded-lg shadow-sm p-4">
                <el-skeleton-item variant="rect" class="w-full h-[120px] rounded-lg" />
              </div>
            </template>
          </el-skeleton>
        </div>

        <div>
          <el-skeleton animated :loading="true">
            <template #template>
              <el-skeleton-item variant="text" class="w-20 h-5 mb-2" />
              <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="space-y-2">
                  <el-skeleton-item variant="text" class="w-[90%] h-[14px]" />
                  <el-skeleton-item variant="text" class="w-[80%] h-[14px]" />
                  <el-skeleton-item variant="text" class="w-[85%] h-[14px]" />
                  <el-skeleton-item variant="text" class="w-[70%] h-[14px]" />
                  <el-skeleton-item variant="text" class="w-[88%] h-[14px]" />
                </div>
              </div>
            </template>
          </el-skeleton>
        </div>
      </div>
    </div>
    <div v-else>
      <div v-if="!book">未找到该书籍</div>
      <div v-else>
        <div class="flex flex-col md:flex-row gap-6 md:gap-8 bg-white shadow-sm rounded-lg p-6">
          <!-- 左侧封面（移动端居中） -->
          <div class="w-full md:w-48 mb-4 md:mb-0">
            <div class="w-full max-w-[260px] mx-auto relative">
              <CoverImage
                :file-id="book.cover_file_id || null"
                :title="book.title"
                :authors="(book.authors || []).map(a => a.name)"
                class="rounded w-full h-auto object-contain"
              />
              <div
                class="absolute top-2 right-2 flex gap-1"
                v-if="userSettings.bookDetail?.showReadTag"
              >
                <el-tag v-if="isReadMark" type="success" size="small">已读</el-tag>
                <el-tag v-else-if="isReading" type="warning" size="small">正在阅读</el-tag>
              </div>
            </div>
          </div>

          <!-- 右侧详情 -->
          <div class="w-full md:w-3/4 md:pl-8 flex flex-col justify-between">
            <div>
              <h1 class="text-2xl md:text-3xl font-bold mb-1">{{ book.title || '#' + book.id }}</h1>
              <div
                class="text-sky-600 hover:underline text-sm md:text-base break-words"
                v-if="book.authors?.length"
              >
                <span v-for="(a, idx) in book.authors" :key="a.id">
                  <a href="#" @click.prevent="goBooksByAuthor(a.id)">{{ a.name }}</a>
                  <span v-if="idx < book.authors.length - 1">/</span>
                </span>
              </div>
            </div>

            <div class="flex items-center text-gray-600 mt-3 flex-wrap">
              <el-rate :model-value="book.rating || 0" disabled text-color="#facc15" class="mr-2" />
              <span class="text-sm mr-4 text-green-600">
                {{ (book.rating || 0).toFixed(1) }} / 5.0
              </span>
              <!-- <span class="text-sm mr-4">{{ (book as any).comment_count || 0 }} comments</span> -->
              <span class="material-symbols-outlined mr-1">book</span>
              <span class="text-sm mr-4">平装</span>
            </div>
            <div v-if="book.tags?.length" class="mt-3 flex flex-wrap gap-2">
              <el-button
                v-for="t in book.tags"
                :key="t.id"
                type="primary"
                plain
                class="px-2 py-1 rounded-full text-xs"
                @click="goBooksByTag(t.id)"
              >
                {{ t.name }}
              </el-button>
            </div>
            <div class="mt-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div
                  v-for="(item, idx) in metadataEntries"
                  :key="idx"
                  class="flex flex-col sm:flex-row sm:items-start bg-white"
                >
                  <div class="text-xs text-gray-500 sm:mb-0 sm:w-24">{{ item.label }}</div>
                  <div class="text-sm text-gray-800 break-words">{{ item.value }}</div>
                </div>
              </div>
            </div>
            <BookActions
              :book-id="book.id"
              :files="files"
              :continue-target="continueTarget"
              :is-logged-in="isLoggedIn"
              :is-read-mark="isReadMark"
              :user="authStore.user"
              @read="onRead"
              @download="onDownload"
              @toggle-read="toggleRead"
            >
              <template #buttons>
                <router-link
                  v-if="isLoggedIn && hasPermission('books.edit')"
                  :to="`/admin/books/${book.id}`"
                >
                  <el-button type="default" size="large">
                    <span class="material-symbols-outlined mr-2">edit</span>
                    编辑本书
                  </el-button>
                </router-link>
              </template>
            </BookActions>
          </div>
        </div>

        <!-- body：简介、章节 -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2">
            <h2 class="text-lg font-medium mb-2">简介</h2>
            <div class="bg-white rounded-lg shadow-sm p-4 whitespace-pre-wrap">
              <div v-if="book.description">
                {{ book.description }}
              </div>
              <el-empty v-else description="暂无简介"></el-empty>
            </div>
          </div>

          <div>
            <h2 class="text-lg font-medium mb-2">章节</h2>
            <div class="bg-white rounded-lg shadow-sm p-4">
              <div v-if="chaptersLoading" class="space-y-2">
                <el-skeleton-item variant="text" class="w-[90%] h-[14px]" />
                <el-skeleton-item variant="text" class="w-[80%] h-[14px]" />
                <el-skeleton-item variant="text" class="w-[85%] h-[14px]" />
                <el-skeleton-item variant="text" class="w-[70%] h-[14px]" />
                <el-skeleton-item variant="text" class="w-[88%] h-[14px]" />
              </div>
              <div v-else-if="chapters.length === 0" class="text-gray-500">暂无章节</div>
              <div v-else class="max-h-[360px] overflow-auto space-y-1">
                <div
                  v-for="c in chapters"
                  :key="c.index"
                  class="px-2 py-1 rounded-md cursor-pointer transition flex items-center gap-2 hover:bg-gray-200"
                  @click="openChapterFromDetail(c.index)"
                >
                  <span class="text-xs text-gray-500">#{{ c.index }}</span>
                  <span class="truncate">{{ c.title || '(无标题)' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getDownloadUrl } from '@/utils/signedUrls'
import CoverImage from '@/components/CoverImage.vue'
import BookActions from '@/components/Book/Actions.vue'
import { booksApi } from '@/api/books'
import { progressApi } from '@/api/progress'
import { txtApi } from '@/api/txt'
import type { FileRec, Book } from '@/api/types'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useLoading } from '@/composables/useLoading'
import { useBookActions } from '@/composables/useBookActions'
import { humanSize } from '@/utils/file'

const { handleError } = useErrorHandler()
import { usePermission } from '@/composables/usePermission'

const { toggleReadMark } = useBookActions()
const { hasPermission } = usePermission()
const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)

const authStore = useAuthStore()
const isLoggedIn = computed(() => authStore.isLoggedIn)

const { isLoadingKey, startLoading, stopLoading } = useLoading()
const loading = computed(() => isLoadingKey('book'))
const chaptersLoading = computed(() => isLoadingKey('chapters'))

const book = ref<Book | null>(null)
const files = ref<FileRec[]>([])
const err = ref('')
const continueTarget = ref<{ fileId: number; chapterIndex?: number } | null>(null)
const chapters = ref<{ index: number; title?: string | null; offset: number; length: number }[]>([])

const isReadMark = computed(() => book.value?.is_read_mark || book.value?.is_read_mark === 1)
const isReading = computed(() => book.value?.is_reading || book.value?.is_reading === 1)

const settingsStore = useSettingsStore()
const userSettings = settingsStore.settings

const metadataEntries = computed(() => {
  if (!book.value) return []
  const out: Array<{ label: string; value: string }> = []
  const push = (label: string, val: any) => {
    if (val === null || val === undefined) return
    const s = String(val).trim()
    if (s === '') return
    out.push({ label, value: s })
  }

  push('出版社', book.value.publisher)
  push('出版日期', book.value.published_at)
  push('语言', book.value.language)
  push('页数', book.value.pages)
  push('ISBN 10', book.value.isbn10)
  push('ISBN 13', book.value.isbn13)
  if (book.value.series?.name) {
    let s = book.value.series.name
    if (book.value.series_index) s += ` — 第${book.value.series_index}卷`
    push('丛书', s)
  }
  if (files.value && files.value.length > 0) {
    const f = files.value[0]
    const s = `${(f.format || '').toUpperCase()}${f.size ? ', ' + humanSize(f.size) : ''}`
    push('文件', s)
  }
  return out
})

onMounted(async () => {
  startLoading('book')
  try {
    const [b, fs] = await Promise.all([booksApi.get(id), booksApi.files(id)])
    book.value = b
    files.value = fs
    // 加载阅读进度，若指向 TXT 文件则提供“继续阅读”
    try {
      const p = await progressApi.get(id)
      const fileId = Number(p?.file_id || 0)
      if (fileId && fs.some(f => f.id === fileId && (f.format || '').toLowerCase() === 'txt')) {
        let chapterIndex: number | undefined
        try {
          chapterIndex = JSON.parse(p.location || '{}')?.chapterIndex
        } catch {}
        continueTarget.value = { fileId, chapterIndex }
      }
    } catch (e: any) {
      handleError(e, { context: 'BookDetail.loadReadingProgress' })
    }
    // 加载章节（若存在 txt 文件）
    const firstTxt = fs.find(f => (f.format || '').toLowerCase() === 'txt')
    if (firstTxt) {
      startLoading('chapters')
      try {
        const response = await txtApi.listChapters(firstTxt.id)
        chapters.value = response.chapters
      } catch (e: any) {
        handleError(e, { context: 'BookDetail.loadChapters' })
      } finally {
        stopLoading('chapters')
      }
    }
  } catch (e: any) {
    err.value = e?.message || '加载失败'
    handleError(e, { context: 'BookDetail.loadBook' })
  } finally {
    stopLoading('book')
  }
})

async function readFile(f: FileRec) {
  const fmt = (f.format || '').toLowerCase()
  if (fmt === 'txt') {
    router.push({ name: 'reader-txt', params: { id: String(f.id) } })
  } else if (fmt === 'epub') {
    router.push({ name: 'reader-epub', params: { id: String(f.id) } })
  } else if (fmt === 'pdf') {
    router.push({ name: 'reader-pdf', params: { id: String(f.id) } })
  } else {
    // 未知格式：直接下载（签名或直链）
    try {
      window.location.href = await getDownloadUrl(f.id)
    } catch {}
  }
}

function onRead(payload: { type: string; file: FileRec }) {
  if (!payload || !payload.file) return
  readFile(payload.file)
}

async function onDownload(fileId: number) {
  if (!fileId) return
  try {
    window.location.href = await getDownloadUrl(fileId)
  } catch {}
}

// 传递bookId和chapterIndex，当前不需要使用该方法，仅留作备用
function _continueRead() {
  if (!continueTarget.value) return
  const f = continueTarget.value
  const q: any = { bookId: String(id) }
  if (typeof f.chapterIndex === 'number') q.chapterIndex = String(f.chapterIndex)
  router.push({ name: 'reader-txt', params: { id: String(f.fileId) }, query: q })
}

async function toggleRead() {
  if (!book.value) return
  await toggleReadMark(book.value)
}

function openChapterFromDetail(index: number) {
  // 使用当前首个 TXT 文件进入阅读器并跳转到指定章节
  const firstTxt = files.value.filter(f => (f.format || '').toLowerCase() === 'txt')[0]
  if (!firstTxt) {
    handleError(new Error('未找到可阅读的 TXT 文件'), {
      context: 'BookDetail.openChapter',
      message: '未找到可阅读的 TXT 文件',
    })
    return
  }
  router.push({
    name: 'reader-txt',
    params: { id: String(firstTxt.id) },
    query: { chapterIndex: String(index) },
  })
}

function goBooksByAuthor(authorId: number) {
  router.push({ name: 'books', query: { author_id: String(authorId) } })
}
function goBooksByTag(tagId: number) {
  router.push({ name: 'books', query: { tag_id: String(tagId) } })
}
</script>
