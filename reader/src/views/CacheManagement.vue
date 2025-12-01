<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">离线书架</h1>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          已缓存的书籍列表
        </div>
      </div>
      <el-button @click="$router.push({ name: 'cache-settings' })">
        <span class="material-symbols-outlined mr-1 text-base">settings</span>
        缓存管理
      </el-button>
    </div>

    <!-- Filter & Sort -->
    <BookFilter
      v-model="filters"
      v-model:sort="sort"
      v-model:order="order"
      :showShelves="enableShelfFilter"
      :showTags="enableTagFilter"
      :showAuthor="enableAuthorFilter"
      :showReadState="enableReadStateFilter"
      :showRating="enableRatingFilter"
      :showPublisher="enableAdvancedFilters"
      :showPublishedAt="enableAdvancedFilters"
      :showLanguage="enableAdvancedFilters"
      :showSeries="enableAdvancedFilters"
      :showIsbn="enableAdvancedFilters"
      show-cache-sort
      show-size-sort
    >
      <template #extra>
        <div class="flex items-center gap-3 ml-auto">
          <el-switch
            v-model="isManageMode"
            active-text="管理模式"
            inactive-text="阅读模式"
          />
          <el-button 
            v-if="isManageMode && stats.totalBooks > 0" 
            type="danger" 
            plain 
            size="small"
            @click="handleClearAll"
          >
            清空所有
          </el-button>
        </div>
      </template>
    </BookFilter>

    <!-- Content -->
    <BookGrid
      :data="filteredBooks"
      :loading="loading"
      :show-delete="isManageMode"
      @delete="handleDeleteBook"
    />

    <!-- Empty State -->
    <div v-if="!loading && filteredBooks.length === 0" class="text-center py-20">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
        <span class="material-symbols-outlined text-5xl text-gray-400">cloud_off</span>
      </div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
        {{ filters.q ? '未找到匹配的书籍' : '暂无缓存书籍' }}
      </h3>
      <p class="text-gray-500 dark:text-gray-400">
        {{ filters.q ? '请尝试更换搜索关键词' : '在阅读器中阅读时会自动缓存，或手动点击缓存按钮' }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { getAllCachedBooks, getCacheStats, deleteCachedBook, clearAllCache } from '@/utils/txtCache'
import { useErrorHandler } from '@/composables/useErrorHandler'
import BookGrid from '@/components/Book/BookGrid.vue'
import BookFilter, { type FiltersModel } from '@/components/Book/BookFilter.vue'
import type { Book } from '@/api/types'
import { filterBooksLocally } from '@/utils/bookCache'
import {
  enableShelfFilter,
  enableTagFilter,
  enableAuthorFilter,
  enableReadStateFilter,
  enableRatingFilter,
  enableAdvancedFilters,
} from '@/config'

const { handleError, handleSuccess } = useErrorHandler()

const loading = ref(true)
const isManageMode = ref(false)
const stats = ref({
  totalBooks: 0,
  totalSize: 0,
  oldestCache: null as number | null,
})

const filters = ref<FiltersModel>({
  q: '',
  authorId: null,
  tagIds: [],
  shelfId: null,
  readState: null,
  ratingRange: [0, 5],
  publisher: null,
  publishedRange: null,
  language: null,
  series_value: null,
  isbn: null,
})
const sort = ref('cached')
const order = ref<'asc' | 'desc'>('desc')

const cachedBooks = ref<Array<{
  fileId: number
  bookId?: number
  bookTitle?: string
  fileName: string
  chapterCount: number
  cachedAt: number
  size: number
}>>([])

// Convert to BookGrid format and apply filters
const filteredBooks = computed<Book[]>(() => {
  // First convert cached books to Book format
  let allBooks = cachedBooks.value.map(cb => ({
    id: cb.bookId || cb.fileId,
    title: cb.bookTitle || cb.fileName,
    files: [{
      id: cb.fileId,
      format: 'txt',
      path: '',
      mime: 'text/plain'
    }],
    authors: [],
    cover_file_id: undefined,
    is_read_mark: false,
    is_reading: false,
    // Attach raw data for sorting
    _raw: cb
  }))

  // Apply filters using the shared filtering logic
  const filtered = filterBooksLocally(allBooks, {
    q: filters.value.q,
    authorId: filters.value.authorId,
    tagIds: filters.value.tagIds,
    shelfId: filters.value.shelfId,
    readState: filters.value.readState,
    ratingRange: filters.value.ratingRange,
  })

  // Sort
  filtered.sort((a, b) => {
    let cmp = 0
    switch (sort.value) {
      case 'cached':
        cmp = (a._raw?.cachedAt || 0) - (b._raw?.cachedAt || 0)
        break
      case 'size':
        cmp = (a._raw?.size || 0) - (b._raw?.size || 0)
        break
      case 'created':
      case 'modified':
        cmp = (a._raw?.cachedAt || 0) - (b._raw?.cachedAt || 0)
        break
      case 'rating':
        cmp = 0
        break
      default:
        cmp = 0
    }
    return order.value === 'desc' ? -cmp : cmp
  })

  return filtered
})

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    const [booksData, statsData] = await Promise.all([getAllCachedBooks(), getCacheStats()])
    cachedBooks.value = booksData
    stats.value = statsData
  } catch (e: any) {
    handleError(e, { context: 'CacheManagement.loadData' })
  } finally {
    loading.value = false
  }
}

async function handleDeleteBook(book: Book) {
  // 从 book.files 中找到 fileId
  const fileId = book.files?.find(f => f.format === 'txt')?.id
  
  if (!fileId) {
    ElMessage.error('无法找到文件ID')
    return
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除《${book.title}》的缓存吗？`,
      '删除确认',
      {
        type: 'warning',
        confirmButtonText: '删除',
        cancelButtonText: '取消',
      }
    )
    
    await deleteCachedBook(fileId)
    handleSuccess('已删除缓存')
    await loadData()
  } catch (e) {
    if (e !== 'cancel') {
      handleError(e, { context: 'CacheManagement.delete' })
    }
  }
}

async function handleClearAll() {
  try {
    await ElMessageBox.confirm(
      `确定要清空所有缓存吗？这将删除 ${stats.value.totalBooks} 本书的缓存数据。`,
      '清空确认',
      {
        type: 'warning',
        confirmButtonText: '清空',
        cancelButtonText: '取消',
      }
    )
    
    await clearAllCache()
    handleSuccess('已清空所有缓存')
    await loadData()
    isManageMode.value = false
  } catch (e) {
    if (e !== 'cancel') {
      handleError(e, { context: 'CacheManagement.clearAll' })
    }
  }
}

function formatSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}
</script>
