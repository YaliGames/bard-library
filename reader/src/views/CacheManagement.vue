<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">离线书架</h1>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-4">
          <span>已缓存 {{ stats.totalBooks }} 本书</span>
          <span>占用 {{ formatSize(stats.totalSize) }}</span>
        </div>
      </div>
      
      <div class="flex items-center gap-3">
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
    </div>

    <!-- Content -->
    <BookGrid
      :data="bookList"
      :loading="loading"
      :show-delete="isManageMode"
      @delete="handleDeleteBook"
    />

    <!-- Empty State -->
    <div v-if="!loading && bookList.length === 0" class="text-center py-20">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
        <span class="material-symbols-outlined text-5xl text-gray-400">cloud_off</span>
      </div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">暂无缓存书籍</h3>
      <p class="text-gray-500 dark:text-gray-400">在阅读器中阅读时会自动缓存，或手动点击缓存按钮</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { getAllCachedBooks, getCacheStats, deleteCachedBook, clearAllCache } from '@/utils/txtCache'
import { useErrorHandler } from '@/composables/useErrorHandler'
import BookGrid from '@/components/Book/BookGrid.vue'
import type { Book } from '@/api/types'

const { handleError, handleSuccess } = useErrorHandler()

const loading = ref(true)
const isManageMode = ref(false)
const stats = ref({
  totalBooks: 0,
  totalSize: 0,
  oldestCache: null as number | null,
})

const cachedBooks = ref<Array<{
  fileId: number
  bookId?: number
  bookTitle?: string
  fileName: string
  chapterCount: number
  cachedAt: number
  size: number
}>>([])

// 转换为 BookGrid 需要的数据格式
const bookList = computed<Book[]>(() => {
  return cachedBooks.value.map(cb => ({
    id: cb.bookId || cb.fileId, // 优先使用bookId，否则用fileId作为临时ID
    title: cb.bookTitle || cb.fileName,
    // 构造一个包含 TXT 文件的 files 数组，以便 BookGrid 点击时能找到
    files: [{
      id: cb.fileId,
      format: 'txt',
      path: '', // 离线模式不需要 path
      mime: 'text/plain'
    }],
    authors: [], // 缓存中暂无作者信息
    cover_file_id: undefined, // 缓存中暂无封面
    is_read_mark: false,
    is_reading: false
  }))
})

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    const [booksData, statsData] = await Promise.all([getAllCachedBooks(), getCacheStats()])
    cachedBooks.value = booksData.sort((a, b) => b.cachedAt - a.cachedAt)
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
