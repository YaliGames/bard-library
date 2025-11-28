<template>
  <div class="max-w-6xl mx-auto space-y-6">
    <!-- 页面标题 -->
    <div class="flex justify-between items-center">
      <h1 class="text-3xl font-bold text-gray-900">缓存管理</h1>
      <div class="flex items-center space-x-4">
        <el-button @click="refreshCache" :loading="loading" type="default">
          <span class="material-symbols-outlined mr-1">refresh</span>
          刷新
        </el-button>
        <el-button @click="clearAllCache" type="danger" :disabled="cachedBooks.length === 0">
          <span class="material-symbols-outlined mr-1">delete_sweep</span>
          清空所有缓存
        </el-button>
      </div>
    </div>

    <!-- 缓存统计 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-blue-100 rounded-lg">
            <span class="material-symbols-outlined text-blue-600 text-2xl">library_books</span>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">缓存图书数量</p>
            <p class="text-2xl font-bold text-gray-900">{{ cacheStats.totalBooks }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-green-100 rounded-lg">
            <span class="material-symbols-outlined text-green-600 text-2xl">data_usage</span>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">缓存大小</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatFileSize(cacheStats.totalSize) }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-orange-100 rounded-lg">
            <span class="material-symbols-outlined text-orange-600 text-2xl">schedule</span>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">最早缓存</p>
            <p class="text-2xl font-bold text-gray-900">
              {{ cacheStats.oldestCache ? formatDate(cacheStats.oldestCache) : '无' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- 缓存图书列表 -->
    <div class="card">
      <h3 class="text-lg font-medium text-gray-900 mb-4">已缓存图书</h3>

      <!-- 加载状态 -->
      <div v-if="loading" class="flex justify-center py-8">
        <el-icon class="is-loading text-2xl text-blue-500">
          <Loading />
        </el-icon>
      </div>

      <!-- 空状态 -->
      <div v-else-if="cachedBooks.length === 0" class="text-center py-12">
        <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">cached</span>
        <h3 class="text-lg font-medium text-gray-900 mb-2">暂无缓存</h3>
        <p class="text-gray-500">阅读图书时可以选择缓存到本地</p>
      </div>

      <!-- 图书列表 -->
      <div v-else class="space-y-4">
        <div
          v-for="book in cachedBooks"
          :key="book.fileId"
          class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center space-x-4">
            <!-- 图书图标 -->
            <div class="p-2 bg-blue-100 rounded-lg">
              <span class="material-symbols-outlined text-blue-600">book</span>
            </div>

            <!-- 图书信息 -->
            <div>
              <h4 class="font-medium text-gray-900">
                {{ book.bookTitle || book.fileName }}
              </h4>
              <p class="text-sm text-gray-500">
                {{ book.chapterCount }} 章节 · {{ formatFileSize(book.size) }}
              </p>
              <p class="text-xs text-gray-400">
                缓存时间: {{ formatDate(book.cachedAt) }}
              </p>
            </div>
          </div>

          <!-- 操作按钮 -->
          <div class="flex items-center space-x-2">
            <el-button
              type="primary"
              size="small"
              @click="readCachedBook(book.fileId)"
            >
              <span class="material-symbols-outlined mr-1">visibility</span>
              阅读
            </el-button>

            <el-button
              type="danger"
              size="small"
              @click="deleteCache(book.fileId)"
            >
              <span class="material-symbols-outlined mr-1">delete</span>
              删除
            </el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'
import { getAllCachedBooks, deleteCachedBook, clearAllCache as clearAllCacheFromDB, getCacheStats } from '@/utils/cache'

interface CachedBookInfo {
  fileId: number
  bookId?: number
  bookTitle?: string
  fileName: string
  chapterCount: number
  cachedAt: number
  size: number
}

const cachedBooks = ref<CachedBookInfo[]>([])
const loading = ref(false)
const cacheStats = ref({
  totalBooks: 0,
  totalSize: 0,
  oldestCache: null as number | null,
})

// 加载缓存列表
async function loadCacheList() {
  try {
    loading.value = true
    cachedBooks.value = await getAllCachedBooks()
    cacheStats.value = await getCacheStats()
  } catch (error) {
    console.error('Failed to load cache list:', error)
    ElMessage.error('加载缓存列表失败')
  } finally {
    loading.value = false
  }
}

// 刷新缓存列表
async function refreshCache() {
  await loadCacheList()
  ElMessage.success('缓存列表已刷新')
}

// 删除单个缓存
async function deleteCache(fileId: number) {
  try {
    await ElMessageBox.confirm(
      '确定要删除这个缓存吗？删除后需要重新下载才能阅读。',
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    )

    await deleteCachedBook(fileId)
    await loadCacheList()
    ElMessage.success('缓存已删除')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to delete cache:', error)
      ElMessage.error('删除缓存失败')
    }
  }
}

// 清空所有缓存
async function clearAllCache() {
  try {
    await ElMessageBox.confirm(
      '确定要清空所有缓存吗？此操作不可恢复。',
      '确认清空',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    )

    await clearAllCacheFromDB()
    await loadCacheList()
    ElMessage.success('所有缓存已清空')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to clear cache:', error)
      ElMessage.error('清空缓存失败')
    }
  }
}

// 阅读缓存的图书
function readCachedBook(fileId: number) {
  // 这里需要根据文件类型跳转到对应的阅读器
  // 暂时跳转到 TXT 阅读器
  // TODO: 需要根据实际文件类型判断
  window.open(`/reader/txt/${fileId}`, '_blank')
}

// 格式化文件大小
function formatFileSize(bytes: number): string {
  const units = ['B', 'KB', 'MB', 'GB']
  let size = bytes
  let unitIndex = 0

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex++
  }

  return `${size.toFixed(1)} ${units[unitIndex]}`
}

// 格式化日期
function formatDate(timestamp: number): string {
  return new Date(timestamp).toLocaleString('zh-CN')
}

// 组件挂载时加载数据
onMounted(() => {
  loadCacheList()
})
</script>