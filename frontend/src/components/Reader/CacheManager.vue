<template>
  <el-dialog v-model="visible" title="本地缓存管理" width="600px" :close-on-click-modal="false">
    <div class="space-y-4">
      <!-- 统计信息 -->
      <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ stats.totalBooks }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">已缓存书籍</div>
          </div>
          <div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ formatSize(stats.totalSize) }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">占用空间</div>
          </div>
          <div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ currentBookCached ? '✓' : '✗' }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">当前书籍</div>
          </div>
        </div>
      </div>

      <!-- 当前书籍操作 -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <h3 class="text-sm font-semibold mb-3 text-gray-800 dark:text-gray-200">当前书籍</h3>
        <div
          class="group flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <div>
            <div class="font-medium text-sm text-gray-800 dark:text-gray-200 truncate">
              {{ bookTitle }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ chapters.length }} 章</div>
          </div>
          <div class="flex gap-2">
            <el-button
              v-if="!currentBookCached"
              type="primary"
              size="small"
              :loading="caching"
              @click="handleCacheCurrentBook"
            >
              <span class="material-symbols-outlined text-base mr-1">download</span>
              缓存到本地
            </el-button>
            <el-button
              v-else
              type="danger"
              size="small"
              link
              class="opacity-0 group-hover:opacity-100 transition-opacity"
              @click="handleDeleteCurrentCache"
            >
              删除
            </el-button>
          </div>
        </div>
        <div v-if="caching" class="mt-3">
          <el-progress
            :percentage="cacheProgress"
            :status="cacheProgress === 100 ? 'success' : undefined"
          />
          <div class="text-xs text-gray-500 mt-1 text-center">
            正在缓存第 {{ cachedChapters }} / {{ chapters.length }} 章
          </div>
        </div>
      </div>

      <!-- 缓存列表 -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">所有缓存</h3>
          <el-button
            type="danger"
            size="small"
            :disabled="cachedBooks.length === 0"
            @click="handleClearAll"
          >
            <span class="material-symbols-outlined text-base mr-1">delete</span>
            删除所有缓存
          </el-button>
        </div>
        <div v-if="cachedBooks.length === 0" class="text-center py-4 text-gray-400">暂无缓存</div>
        <div v-else class="space-y-2 max-h-64 overflow-y-auto">
          <div
            v-for="book in cachedBooks"
            :key="book.fileId"
            class="group flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm text-gray-800 dark:text-gray-200 truncate">
                {{ book.bookTitle || book.fileName }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ book.chapterCount }} 章 · {{ formatSize(book.size) }} ·
                {{ formatDate(book.cachedAt) }}
              </div>
            </div>
            <el-button
              type="danger"
              size="small"
              link
              class="opacity-0 group-hover:opacity-100 transition-opacity"
              @click="handleDeleteCache(book.fileId)"
            >
              删除
            </el-button>
          </div>
        </div>
      </div>

      <!-- 操作按钮 -->
      <div class="flex justify-end">
        <el-button @click="visible = false">关闭</el-button>
      </div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { Chapter } from '@/api/txt'
import {
  getCachedBook,
  deleteCachedBook,
  getAllCachedBooks,
  clearAllCache,
  getCacheStats,
  cacheBook,
} from '@/utils/txtCache'

interface Props {
  modelValue: boolean
  fileId: number
  bookTitle: string
  chapters: Chapter[]
  onCacheComplete?: (cached: boolean) => void
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'cache-complete': []
}>()

const visible = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
})

const stats = ref({ totalBooks: 0, totalSize: 0, oldestCache: null as number | null })
const cachedBooks = ref<
  Array<{
    fileId: number
    bookId?: number
    bookTitle?: string
    fileName: string
    chapterCount: number
    cachedAt: number
    size: number
  }>
>([])
const currentBookCached = ref(false)
const caching = ref(false)
const cacheProgress = ref(0)
const cachedChapters = ref(0)

// 格式化文件大小
function formatSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// 格式化日期
function formatDate(timestamp: number): string {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now.getTime() - date.getTime()

  if (diff < 60000) return '刚刚'
  if (diff < 3600000) return Math.floor(diff / 60000) + ' 分钟前'
  if (diff < 86400000) return Math.floor(diff / 3600000) + ' 小时前'
  if (diff < 604800000) return Math.floor(diff / 86400000) + ' 天前'

  return date.toLocaleDateString()
}

// 加载缓存信息
async function loadCacheInfo() {
  try {
    stats.value = await getCacheStats()
    cachedBooks.value = await getAllCachedBooks()
    const cached = await getCachedBook(props.fileId)
    currentBookCached.value = cached !== null
  } catch (error) {
    console.error('Failed to load cache info:', error)
  }
}

// 缓存当前书籍
async function handleCacheCurrentBook() {
  if (caching.value) return

  try {
    caching.value = true
    cacheProgress.value = 0
    cachedChapters.value = 0

    // 导入 txtApi
    const { txtApi } = await import('@/api')

    // 使用新的 API 一次性获取整本书
    const data = await txtApi.getFullContent(props.fileId)

    // 转换为 Map
    const contents = new Map<number, string>()
    Object.entries(data.contents).forEach(([key, value]) => {
      contents.set(Number(key), value)
    })

    // 更新进度
    cachedChapters.value = data.chapters.length
    cacheProgress.value = 100

    // 保存到缓存
    await cacheBook(props.fileId, data.chapters, contents, {
      bookId: data.book_id,
      bookTitle: data.book_title,
      fileName: data.file_name,
    })

    ElMessage.success('缓存完成!')
    emit('cache-complete')
    await loadCacheInfo()
  } catch (error: any) {
    ElMessage.error(error.message || '缓存失败')
  } finally {
    caching.value = false
  }
}

// 删除当前书籍缓存
async function handleDeleteCurrentCache() {
  try {
    await ElMessageBox.confirm('确认删除当前书籍的缓存?', '确认删除', {
      type: 'warning',
    })

    await deleteCachedBook(props.fileId)
    ElMessage.success('删除成功')
    await loadCacheInfo()
    emit('cache-complete')
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 删除指定缓存
async function handleDeleteCache(fileId: number) {
  try {
    await deleteCachedBook(fileId)
    ElMessage.success('删除成功')
    await loadCacheInfo()
    if (fileId === props.fileId) {
      emit('cache-complete')
    }
  } catch {
    ElMessage.error('删除失败')
  }
}

// 清空所有缓存
async function handleClearAll() {
  try {
    await ElMessageBox.confirm(
      `将清空所有 ${stats.value.totalBooks} 本书的缓存,总计 ${formatSize(stats.value.totalSize)}`,
      '确认清空',
      { type: 'warning' },
    )

    await clearAllCache()
    ElMessage.success('清空成功')
    await loadCacheInfo()
    emit('cache-complete')
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error('清空失败')
    }
  }
}

// 监听显示状态
watch(visible, val => {
  if (val) {
    loadCacheInfo()
  }
})
</script>

<style scoped>
.space-y-4 > * + * {
  margin-top: 1rem;
}
.space-y-2 > * + * {
  margin-top: 0.5rem;
}
</style>
