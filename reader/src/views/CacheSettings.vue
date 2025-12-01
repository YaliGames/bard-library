<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">缓存管理</h1>

    <!-- Cache Statistics Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">缓存统计</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
          <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">library_books</span>
            <span class="text-sm text-gray-600 dark:text-gray-400">缓存书籍</span>
          </div>
          <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.totalBooks }}</div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
          <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400">storage</span>
            <span class="text-sm text-gray-600 dark:text-gray-400">占用空间</span>
          </div>
          <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatSize(stats.totalSize) }}</div>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
          <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">schedule</span>
            <span class="text-sm text-gray-600 dark:text-gray-400">最早缓存</span>
          </div>
          <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ stats.oldestCache ? formatDate(stats.oldestCache) : '-' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Metadata Cache Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">元数据缓存</h2>
      
      <div class="space-y-3">
        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">书库列表</span>
          <el-tag :type="metaCache.booksList ? 'success' : 'info'">
            {{ metaCache.booksList ? '已缓存' : '未缓存' }}
          </el-tag>
        </div>
        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">作者列表</span>
          <el-tag :type="metaCache.authors ? 'success' : 'info'">
            {{ metaCache.authors ? '已缓存' : '未缓存' }}
          </el-tag>
        </div>
        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">标签列表</span>
          <el-tag :type="metaCache.tags ? 'success' : 'info'">
            {{ metaCache.tags ? '已缓存' : '未缓存' }}
          </el-tag>
        </div>
        <div class="flex items-center justify-between py-2">
          <span class="text-gray-700 dark:text-gray-300">书架列表</span>
          <el-tag :type="metaCache.shelves ? 'success' : 'info'">
            {{ metaCache.shelves ? '已缓存' : '未缓存' }}
          </el-tag>
        </div>
      </div>
    </div>

    <!-- Book List Cache Details -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">书库列表缓存详情</h2>
      
      <div v-if="bookListPages.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
        暂无缓存的书库列表页面
      </div>
      
      <div v-else class="space-y-2">
        <div class="grid grid-cols-4 gap-4 pb-2 border-b border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-600 dark:text-gray-400">
          <div>页码</div>
          <div>排序方式</div>
          <div>排序顺序</div>
          <div>书籍数量</div>
        </div>
        <div 
          v-for="page in bookListPages" 
          :key="`${page.page}-${page.sort}-${page.order}`"
          class="grid grid-cols-4 gap-4 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0"
        >
          <div class="text-gray-900 dark:text-gray-100">第 {{ page.page }} 页</div>
          <div class="text-gray-700 dark:text-gray-300">{{ getSortLabel(page.sort) }}</div>
          <div class="text-gray-700 dark:text-gray-300">{{ page.order === 'desc' ? '降序' : '升序' }}</div>
          <div class="text-gray-700 dark:text-gray-300">{{ page.bookCount }} 本</div>
        </div>
        <div class="pt-3 text-sm text-gray-500 dark:text-gray-400">
          共缓存 {{ bookListPages.length }} 个页面
        </div>
      </div>
    </div>

    <!-- Management Actions Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">管理操作</h2>
      
      <div class="space-y-3">
        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
          <div>
            <div class="font-medium text-gray-900 dark:text-gray-100">清空书籍缓存</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">删除所有已缓存的书籍内容</div>
          </div>
          <el-button 
            type="danger" 
            plain 
            :disabled="stats.totalBooks === 0"
            @click="handleClearBookCache"
          >
            清空
          </el-button>
        </div>

        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
          <div>
            <div class="font-medium text-gray-900 dark:text-gray-100">清空书库列表缓存</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">删除所有已缓存的书库列表页面</div>
          </div>
          <el-button 
            type="danger" 
            plain 
            :disabled="bookListPages.length === 0"
            @click="handleClearBookListCache"
          >
            清空
          </el-button>
        </div>

        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
          <div>
            <div class="font-medium text-gray-900 dark:text-gray-100">清空元数据缓存</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">删除书库、作者、标签、书架等缓存</div>
          </div>
          <el-button 
            type="danger" 
            plain 
            @click="handleClearMetaCache"
          >
            清空
          </el-button>
        </div>

        <div class="flex items-center justify-between py-3">
          <div>
            <div class="font-medium text-gray-900 dark:text-gray-100">清空所有缓存</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">删除所有缓存数据（包括书籍和元数据）</div>
          </div>
          <el-button 
            type="danger" 
            @click="handleClearAllCache"
          >
            全部清空
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { getCacheStats, clearAllCache } from '@/utils/txtCache'
import { offlineStorage } from '@/utils/offline'
import { getCachedBookListPages } from '@/utils/bookCache'

const stats = ref({
  totalBooks: 0,
  totalSize: 0,
  oldestCache: null as number | null,
})

const metaCache = ref({
  booksList: false,
  authors: false,
  tags: false,
  shelves: false,
})

const bookListPages = ref<{
  page: number
  sort: string
  order: string
  bookCount: number
  cachedAt: number
}[]>([])

onMounted(async () => {
  await loadStats()
  await checkMetaCache()
  await loadBookListPages()
})

async function loadStats() {
  try {
    const statsData = await getCacheStats()
    stats.value = statsData
  } catch (e) {
    console.error('Failed to load cache stats:', e)
  }
}

async function loadBookListPages() {
  try {
    bookListPages.value = await getCachedBookListPages()
  } catch (e) {
    console.error('Failed to load book list pages:', e)
  }
}

async function checkMetaCache() {
  try {
    // Check if metadata is cached
    const [booksList, authors, tags, shelves] = await Promise.all([
      offlineStorage.books.keys(),
      offlineStorage.meta.getItem('authors-list'),
      offlineStorage.meta.getItem('tags-list'),
      offlineStorage.meta.getItem('shelves-all'),
    ])
    
    metaCache.value = {
      booksList: booksList.some(k => k.startsWith('books-list')),
      authors: !!authors,
      tags: !!tags,
      shelves: !!shelves,
    }
  } catch (e) {
    console.error('Failed to check meta cache:', e)
  }
}

async function handleClearBookCache() {
  try {
    await ElMessageBox.confirm(
      `确定要清空所有书籍缓存吗？这将删除 ${stats.value.totalBooks} 本书的缓存数据。`,
      '清空确认',
      {
        type: 'warning',
        confirmButtonText: '清空',
        cancelButtonText: '取消',
      }
    )
    
    await clearAllCache()
    ElMessage.success('已清空书籍缓存')
    await loadStats()
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error('清空失败')
    }
  }
}

async function handleClearBookListCache() {
  try {
    await ElMessageBox.confirm(
      `确定要清空所有书库列表缓存吗？这将删除 ${bookListPages.value.length} 个缓存页面。`,
      '清空确认',
      {
        type: 'warning',
        confirmButtonText: '清空',
        cancelButtonText: '取消',
      }
    )
    
    // Delete all book list cache keys
    const keys = await offlineStorage.books.keys()
    const bookListKeys = keys.filter(k => k.startsWith('books-list?'))
    await Promise.all(bookListKeys.map(k => offlineStorage.books.removeItem(k)))
    
    ElMessage.success('已清空书库列表缓存')
    await loadBookListPages()
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error('清空失败')
    }
  }
}

async function handleClearMetaCache() {
  try {
    await ElMessageBox.confirm(
      '确定要清空所有元数据缓存吗？下次访问时需要重新加载。',
      '清空确认',
      {
        type: 'warning',
        confirmButtonText: '清空',
        cancelButtonText: '取消',
      }
    )
    
    await offlineStorage.meta.clear()
    ElMessage.success('已清空元数据缓存')
    await checkMetaCache()
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error('清空失败')
    }
  }
}

async function handleClearAllCache() {
  try {
    await ElMessageBox.confirm(
      `确定要清空所有缓存吗？这将删除 ${stats.value.totalBooks} 本书的缓存数据和所有元数据。`,
      '清空确认',
      {
        type: 'warning',
        confirmButtonText: '清空',
        cancelButtonText: '取消',
      }
    )
    
    await Promise.all([
      clearAllCache(),
      offlineStorage.meta.clear(),
    ])
    ElMessage.success('已清空所有缓存')
    await loadStats()
    await checkMetaCache()
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error('清空失败')
    }
  }
}

function getSortLabel(sort: string): string {
  const labels: Record<string, string> = {
    created: '创建时间',
    modified: '修改时间',
    rating: '评分',
    id: 'ID',
  }
  return labels[sort] || sort
}

function formatSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

function formatDate(timestamp: number): string {
  const date = new Date(timestamp)
  const now = new Date()
  const diffDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return '今天'
  if (diffDays === 1) return '昨天'
  if (diffDays < 7) return `${diffDays}天前`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}周前`
  if (diffDays < 365) return `${Math.floor(diffDays / 30)}月前`
  return `${Math.floor(diffDays / 365)}年前`
}
</script>
