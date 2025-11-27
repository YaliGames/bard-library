<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  getAllCachedBooks,
  deleteCachedBook,
  clearAllCache,
  cacheBook,
  type CachedBook,
} from '@/utils/txtCache'
import { txtApi } from '@/api/txt'
import { ElMessageBox, ElMessage } from 'element-plus'

interface CachedBookInfo {
  fileId: number
  bookTitle: string
  fileName: string
  chapterCount: number
  size: string
}

interface Chapter {
  title?: string | null
  index: number
  offset: number
  length: number
}

interface Props {
  fileId: number
  bookId?: number
  bookTitle: string
  chapters: Chapter[]
  cachedBook: CachedBook | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'cache-complete': []
}>()

// 状态
const cachedBooks = ref<CachedBookInfo[]>([])
const cacheLoading = ref(false)

// 计算属性
const isCached = computed(() => !!props.cachedBook)

const cacheStats = computed(() => {
  const totalBooks = cachedBooks.value.length
  const totalBytes = cachedBooks.value.reduce((sum, book) => {
    const sizeMatch = book.size.match(/([\d.]+)\s*([A-Z]+)/)
    if (sizeMatch) {
      const value = parseFloat(sizeMatch[1])
      const unit = sizeMatch[2]
      if (unit === 'KB') return sum + value * 1024
      if (unit === 'MB') return sum + value * 1024 * 1024
    }
    return sum
  }, 0)

  let totalSize = '0 KB'
  if (totalBytes < 1024 * 1024) {
    totalSize = `${(totalBytes / 1024).toFixed(1)} KB`
  } else {
    totalSize = `${(totalBytes / (1024 * 1024)).toFixed(1)} MB`
  }

  return { totalBooks, totalSize }
})

// 方法
async function loadCachedBooks() {
  try {
    const books = await getAllCachedBooks()
    cachedBooks.value = books.map(book => ({
      fileId: book.fileId,
      bookTitle: book.bookTitle || book.fileName,
      fileName: book.fileName,
      chapterCount: book.chapterCount,
      size: formatSize(book.size),
    }))
  } catch (error) {
    console.error('Failed to load cached books:', error)
  }
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

async function handleCacheCurrentBook() {
  if (cacheLoading.value) return

  cacheLoading.value = true
  try {
    const data = await txtApi.getFullContent(props.fileId)

    const contentsMap = new Map<number, string>()
    for (const [key, value] of Object.entries(data.contents)) {
      contentsMap.set(Number(key), value as string)
    }

    // 使用 props.bookId 或者从 API 返回的 book_id
    const bookId = props.bookId || data.book_id

    await cacheBook(props.fileId, props.chapters, contentsMap, {
      bookId: bookId,
      bookTitle: props.bookTitle || data.book_title,
      fileName: data.file_name,
    })

    ElMessage.success('缓存成功')
    emit('cache-complete')
    await loadCachedBooks()
  } catch (error) {
    console.error('Failed to cache book:', error)
    ElMessage.error('缓存失败')
  } finally {
    cacheLoading.value = false
  }
}

async function handleDeleteCache(fileId: number) {
  try {
    await ElMessageBox.confirm('确定要删除这本书的缓存吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })

    await deleteCachedBook(fileId)
    ElMessage.success('删除成功')
    await loadCachedBooks()

    if (fileId === props.fileId) {
      emit('cache-complete')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to delete cache:', error)
    }
  }
}

async function handleClearAllCache() {
  try {
    await ElMessageBox.confirm('确定要清空所有缓存吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })

    await clearAllCache()
    ElMessage.success('清空成功')
    cachedBooks.value = []
    emit('cache-complete')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to clear all cache:', error)
    }
  }
}

// 初始化
onMounted(() => {
  loadCachedBooks()
})

// 暴露给父组件
defineExpose({
  isCached,
  cacheStats,
  cachedBooks,
  cacheLoading,
  loadCachedBooks,
  handleCacheCurrentBook,
  handleDeleteCache,
  handleClearAllCache,
})
</script>

<template>
  <slot
    :is-cached="isCached"
    :cache-stats="cacheStats"
    :cached-books="cachedBooks"
    :cache-loading="cacheLoading"
    :handle-cache-current-book="handleCacheCurrentBook"
    :handle-delete-cache="handleDeleteCache"
    :handle-clear-all-cache="handleClearAllCache"
  >
    <!-- 默认UI -->
    <div>
      <div>缓存统计: {{ cacheStats.totalBooks }} 本 / {{ cacheStats.totalSize }}</div>
      <button @click="handleCacheCurrentBook" :disabled="cacheLoading">
        {{ cacheLoading ? '缓存中...' : isCached ? '已缓存' : '缓存本书' }}
      </button>
      <div v-if="cachedBooks.length > 0">
        <button @click="handleClearAllCache">清空所有缓存</button>
        <div v-for="book in cachedBooks" :key="book.fileId">
          <div>{{ book.bookTitle }}</div>
          <button @click="handleDeleteCache(book.fileId)">删除</button>
        </div>
      </div>
    </div>
  </slot>
</template>
