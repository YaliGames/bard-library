<template>
  <div
    class="flex flex-col md:flex-row max-w-7xl mx-auto mt-4 md:mt-8 px-4 md:px-6 md:space-x-10 gap-4 md:gap-0"
  >
    <!-- 左侧菜单 -->
    <aside class="w-full md:w-64 space-y-4 text-sm">
      <div>
        <h2 class="text-xl font-semibold mb-4">缓存管理</h2>
        <div class="space-y-1">
          <div
            v-for="m in menu"
            :key="m.id"
            @click="active = m.id"
            :class="[
              'flex items-center px-3 py-2 rounded-md cursor-pointer',
              active === m.id
                ? 'bg-gray-200 text-primary font-medium'
                : 'hover:bg-gray-200 text-gray-700',
            ]"
          >
            <span class="material-symbols-outlined mr-2 text-lg">{{ m.icon }}</span>
            <span>{{ m.label }}</span>
          </div>
        </div>
      </div>
    </aside>

    <!-- 右侧内容 -->
    <section class="flex-1 space-y-6">
      <div class="bg-white shadow-sm rounded-xl p-6">
        <!-- 概览 -->
        <div v-if="active === 'overview'">
          <h2 class="text-xl font-semibold mb-4">缓存概览</h2>

          <!-- 缓存统计 -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
              <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">已缓存书籍</div>
              <div class="text-2xl font-semibold text-primary dark:text-blue-400">
                {{ stats.totalBooks }}
              </div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
              <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">总占用空间</div>
              <div class="text-2xl font-semibold text-primary dark:text-blue-400">
                {{ formatSize(stats.totalSize) }}
              </div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
              <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">最早缓存</div>
              <div class="text-2xl font-semibold text-primary dark:text-blue-400">
                {{ stats.oldestCache ? formatDate(stats.oldestCache) : '—' }}
              </div>
            </div>
          </div>

          <!-- 说明 -->
          <div class="border-b border-gray-200 dark:border-gray-700 py-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">
              关于阅读器缓存
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              阅读器缓存功能可以将图书内容保存到浏览器本地，以便离线阅读和加快加载速度。
            </p>
          </div>

          <div class="py-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">缓存说明</h3>
            <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1">
              <p>• 缓存数据仅保存在当前浏览器中，更换设备或清空浏览器数据会丢失缓存</p>
              <p>• 全文搜索功能需要先缓存完整书籍内容</p>
              <p>• 可以在阅读器中缓存新的书籍</p>
              <p>• 缓存的书籍会在阅读器中标记为「已缓存」状态</p>
              <p>• 建议定期清理不再需要的缓存以释放浏览器存储空间</p>
            </div>
          </div>
        </div>

        <!-- 已缓存列表 -->
        <div v-if="active === 'list'">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">已缓存书籍</h2>
            <el-button
              v-if="cachedBooks.length > 0"
              type="danger"
              :loading="clearing"
              @click="handleClearAll"
            >
              清空所有缓存
            </el-button>
          </div>

          <div v-if="loading" class="space-y-3">
            <el-skeleton v-for="i in 3" :key="i" animated>
              <template #template>
                <div class="flex items-center gap-4 p-4 border rounded-lg">
                  <el-skeleton-item variant="image" style="width: 60px; height: 80px" />
                  <div class="flex-1">
                    <el-skeleton-item variant="h3" style="width: 60%" />
                    <el-skeleton-item variant="text" style="width: 40%; margin-top: 8px" />
                  </div>
                </div>
              </template>
            </el-skeleton>
          </div>

          <div
            v-else-if="cachedBooks.length === 0"
            class="text-center py-16 bg-gray-50 dark:bg-gray-800/30 rounded-xl"
          >
            <div
              class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-700 mb-4"
            >
              <span class="material-symbols-outlined text-5xl text-gray-400">cloud_off</span>
            </div>
            <div class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">暂无缓存</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              在阅读器中点击「缓存」按钮可以缓存书籍
            </div>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="book in cachedBooks"
              :key="book.fileId"
              class="group relative bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-blue-300 dark:hover:border-blue-700 transition-all duration-200"
            >
              <!-- 书籍信息 -->
              <div class="flex items-start gap-4">
                <!-- 图标 -->
                <div
                  class="flex-shrink-0 w-14 h-14 rounded-lg bg-primary flex items-center justify-center shadow-sm"
                >
                  <span class="material-symbols-outlined text-3xl text-white">book</span>
                </div>

                <!-- 内容 -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                      <!-- 标题 -->
                      <div
                        class="font-semibold text-gray-900 dark:text-gray-100 truncate mb-2 group-hover:text-primary dark:group-hover:text-blue-400 transition-colors"
                      >
                        {{ book.bookTitle || book.fileName }}
                      </div>

                      <!-- 元数据 -->
                      <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600 dark:text-gray-400"
                      >
                        <div class="flex items-center gap-1">
                          <span class="material-symbols-outlined text-base">menu_book</span>
                          <span>{{ book.chapterCount }} 章</span>
                        </div>
                        <div class="flex items-center gap-1">
                          <span class="material-symbols-outlined text-base">data_usage</span>
                          <span>{{ formatSize(book.size) }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                          <span class="material-symbols-outlined text-base">schedule</span>
                          <span class="group-hover:hidden">{{ formatDate(book.cachedAt) }}</span>
                          <span class="hidden group-hover:inline">
                            {{ formatDateTime(book.cachedAt) }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                      <button
                        v-if="book.bookId"
                        @click="goToBook(book.bookId)"
                        title="查看书籍"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                      >
                        <span class="material-symbols-outlined text-xl">open_in_new</span>
                      </button>
                      <button
                        @click="handleDelete(book.fileId)"
                        :disabled="deletingIds.has(book.fileId)"
                        title="删除缓存"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <span
                          v-if="deletingIds.has(book.fileId)"
                          class="material-symbols-outlined text-xl animate-spin"
                        >
                          progress_activity
                        </span>
                        <span v-else class="material-symbols-outlined text-xl">delete</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 清理建议 -->
        <div v-if="active === 'cleanup'">
          <h2 class="text-xl font-semibold mb-4">清理建议</h2>

          <div v-if="cachedBooks.length > 0" class="mt-6 p-4 bg-yellow-50 rounded-lg">
            <h3 class="font-medium text-yellow-800 mb-2">清理建议</h3>
            <div class="text-sm text-yellow-700 space-y-1">
              <div v-if="oldBooks.length > 0">
                • 发现 {{ oldBooks.length }} 本书籍缓存时间超过 30 天，建议清理
              </div>
              <div v-if="stats.totalSize > 50 * 1024 * 1024">
                • 缓存总大小超过 50MB，建议清理部分不常用的书籍
              </div>
              <div v-if="oldBooks.length === 0 && stats.totalSize <= 50 * 1024 * 1024">
                • 当前缓存状态良好，暂无需清理
              </div>
            </div>
          </div>

          <div class="border-b border-gray-200 dark:border-gray-700 py-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">
              何时需要清理缓存
            </h3>
            <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1">
              <p>• 浏览器提示存储空间不足时</p>
              <p>• 长期不阅读的书籍可以删除缓存</p>
              <p>• 更换阅读设备后，旧设备的缓存可以清理</p>
              <p>• 发现缓存内容与最新版本不符时</p>
            </div>
          </div>

          <div class="py-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">
              清理缓存说明
            </h3>
            <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1">
              <p>• 删除缓存不会影响服务器上的阅读进度和书签数据</p>
              <p>• 删除后需要重新缓存才能使用全文搜索功能</p>
              <p>• 再次打开该书籍时会重新从服务器加载章节内容</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import { getAllCachedBooks, getCacheStats, deleteCachedBook, clearAllCache } from '@/utils/txtCache'
import { useErrorHandler } from '@/composables/useErrorHandler'

const { handleError, handleSuccess } = useErrorHandler()
const router = useRouter()

type MenuId = 'overview' | 'list' | 'cleanup'
const active = ref<MenuId>('overview')
const menu: Array<{ id: MenuId; label: string; icon: string }> = [
  { id: 'overview', label: '概览', icon: 'dashboard' },
  { id: 'list', label: '已缓存书籍', icon: 'list' },
  { id: 'cleanup', label: '清理建议', icon: 'cleaning_services' },
]

const loading = ref(true)
const clearing = ref(false)
const deletingIds = ref(new Set<number>())

const stats = ref({
  totalBooks: 0,
  totalSize: 0,
  oldestCache: null as number | null,
})

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

// 超过30天的书籍
const oldBooks = computed(() => {
  const thirtyDaysAgo = Date.now() - 30 * 24 * 60 * 60 * 1000
  return cachedBooks.value.filter(book => book.cachedAt < thirtyDaysAgo)
})

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    const [booksData, statsData] = await Promise.all([getAllCachedBooks(), getCacheStats()])
    cachedBooks.value = booksData.sort((a, b) => b.cachedAt - a.cachedAt) // 按缓存时间倒序
    stats.value = statsData
  } catch (e: any) {
    handleError(e, { context: 'CacheManagement.loadData' })
  } finally {
    loading.value = false
  }
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
  if (diffDays < 7) return `${diffDays} 天前`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} 周前`
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} 月前`
  return `${Math.floor(diffDays / 365)} 年前`
}

function formatDateTime(timestamp: number): string {
  const date = new Date(timestamp)
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function handleDelete(fileId: number) {
  try {
    await ElMessageBox.confirm('确定要删除这本书的缓存吗？删除后可以重新缓存。', '删除确认', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消',
    })
  } catch {
    return
  }

  deletingIds.value.add(fileId)
  try {
    await deleteCachedBook(fileId)
    handleSuccess('已删除缓存')
    await loadData()
  } catch (e: any) {
    handleError(e, { context: 'CacheManagement.deleteCache' })
  } finally {
    deletingIds.value.delete(fileId)
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
      },
    )
  } catch {
    return
  }

  clearing.value = true
  try {
    await clearAllCache()
    handleSuccess('已清空所有缓存')
    await loadData()
  } catch (e: any) {
    handleError(e, { context: 'CacheManagement.clearAllCache' })
  } finally {
    clearing.value = false
  }
}

function goToBook(bookId: number) {
  router.push({ name: 'book-detail', params: { id: bookId } })
}
</script>
