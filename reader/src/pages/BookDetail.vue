<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- 加载状态 -->
    <div v-if="loading" class="flex justify-center py-12">
      <el-icon class="is-loading text-4xl text-blue-500">
        <Loading />
      </el-icon>
    </div>

    <!-- 错误状态 -->
    <div v-else-if="error" class="text-center py-12">
      <el-alert
        :title="error"
        type="error"
        show-icon
        class="max-w-md mx-auto"
      />
      <el-button @click="loadBook" class="mt-4" type="primary">
        重试
      </el-button>
    </div>

    <!-- 图书详情 -->
    <div v-else-if="book" class="space-y-6">
      <!-- 返回按钮 -->
      <div>
        <el-button @click="goBack" type="text" class="p-0">
          <span class="material-symbols-outlined mr-1">arrow_back</span>
          返回列表
        </el-button>
      </div>

      <!-- 图书信息卡片 -->
      <div class="card">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- 封面 -->
          <div class="md:col-span-1">
            <div class="aspect-[3/4] bg-gray-100 rounded-lg overflow-hidden">
              <img
                v-if="coverUrl"
                :src="coverUrl"
                :alt="book.title"
                class="w-full h-full object-cover"
                @error="handleImageError"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <span class="material-symbols-outlined text-8xl">book</span>
              </div>
            </div>
          </div>

          <!-- 图书信息 -->
          <div class="md:col-span-2 space-y-4">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ book.title }}</h1>
              <p v-if="book.subtitle" class="text-xl text-gray-600">{{ book.subtitle }}</p>
            </div>

            <!-- 作者 -->
            <div v-if="book.authors && book.authors.length > 0">
              <h3 class="text-sm font-medium text-gray-500 mb-1">作者</h3>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="author in book.authors"
                  :key="author.id"
                  class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"
                >
                  {{ author.name }}
                </span>
              </div>
            </div>

            <!-- 基本信息 -->
            <div class="grid grid-cols-2 gap-4">
              <div v-if="book.rating">
                <h3 class="text-sm font-medium text-gray-500 mb-1">评分</h3>
                <div class="flex items-center space-x-1">
                  <span class="material-symbols-outlined text-yellow-400">star</span>
                  <span class="font-medium">{{ book.rating }}/10</span>
                </div>
              </div>

              <div v-if="book.published_at">
                <h3 class="text-sm font-medium text-gray-500 mb-1">出版时间</h3>
                <p>{{ book.published_at }}</p>
              </div>

              <div v-if="book.publisher">
                <h3 class="text-sm font-medium text-gray-500 mb-1">出版社</h3>
                <p>{{ book.publisher }}</p>
              </div>

              <div v-if="book.language">
                <h3 class="text-sm font-medium text-gray-500 mb-1">语言</h3>
                <p>{{ book.language }}</p>
              </div>

              <div v-if="book.files && book.files.length > 0">
                <h3 class="text-sm font-medium text-gray-500 mb-1">文件数量</h3>
                <p>{{ book.files.length }} 个文件</p>
              </div>

              <div v-if="book.isbn13">
                <h3 class="text-sm font-medium text-gray-500 mb-1">ISBN</h3>
                <p>{{ book.isbn13 }}</p>
              </div>
            </div>

            <!-- 标签 -->
            <div v-if="book.tags && book.tags.length > 0">
              <h3 class="text-sm font-medium text-gray-500 mb-1">标签</h3>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="tag in book.tags"
                  :key="tag.id"
                  class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm"
                >
                  {{ tag.name }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- 描述 -->
        <div v-if="book.description" class="mt-6 pt-6 border-t border-gray-200">
          <h3 class="text-lg font-medium text-gray-900 mb-3">简介</h3>
          <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ book.description }}</p>
        </div>
      </div>

      <!-- 文件列表 -->
      <div class="card">
        <h3 class="text-lg font-medium text-gray-900 mb-4">文件列表</h3>

        <div v-if="book.files && book.files.length > 0" class="space-y-3">
          <div
            v-for="file in book.files"
            :key="file.id"
            class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center space-x-3">
              <span class="material-symbols-outlined text-gray-400">
                {{ getFileIcon(file.format) }}
              </span>
              <div>
                <p class="font-medium text-gray-900">{{ file.format?.toUpperCase() || '未知格式' }}</p>
                <p class="text-sm text-gray-500">
                  {{ formatFileSize(file.size) }}
                </p>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <!-- 缓存状态 -->
              <el-tag
                v-if="isFileCached(file.id)"
                size="small"
                type="success"
              >
                已缓存
              </el-tag>

              <!-- 阅读按钮 -->
              <el-button
                type="primary"
                size="small"
                @click="readBook(file)"
                :disabled="!networkStore.isOnline && !isFileCached(file.id)"
              >
                <span class="material-symbols-outlined mr-1">visibility</span>
                阅读
              </el-button>

              <!-- 缓存按钮 -->
              <el-button
                v-if="!isFileCached(file.id)"
                type="default"
                size="small"
                @click="cacheBook(file)"
                :loading="cachingFiles.has(file.id)"
                :disabled="!networkStore.isOnline"
              >
                <span class="material-symbols-outlined mr-1">download</span>
                缓存
              </el-button>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <span class="material-symbols-outlined text-4xl mb-2">description</span>
          <p>暂无文件</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'
import { useBookStore, useNetworkStore } from '@/stores'
import { getBook, getBookCoverUrl } from '@/api/books'
import { getCachedBook, cacheBook as cacheBookToDB } from '@/utils/cache'
import type { Book, FileRec } from '@/types'

const route = useRoute()
const router = useRouter()
const bookStore = useBookStore()
const networkStore = useNetworkStore()

const book = ref<Book | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)
const cachingFiles = ref(new Set<number>())

// 计算属性
const coverUrl = computed(() => book.value ? getBookCoverUrl(book.value) : null)

// 加载图书详情
async function loadBook() {
  const bookId = parseInt(route.params.id as string)
  if (!bookId) {
    error.value = '无效的图书ID'
    return
  }

  try {
    loading.value = true
    error.value = null

    const bookData = await getBook(bookId)
    book.value = bookData.data
    bookStore.setCurrentBook(bookData.data)
  } catch (err) {
    console.error('Failed to load book:', err)
    error.value = '加载图书失败，请检查网络连接'
  } finally {
    loading.value = false
  }
}

// 返回上一页
function goBack() {
  router.go(-1)
}

// 获取文件图标
function getFileIcon(format?: string): string {
  switch (format?.toLowerCase()) {
    case 'txt':
      return 'description'
    case 'pdf':
      return 'picture_as_pdf'
    case 'epub':
      return 'book'
    default:
      return 'insert_drive_file'
  }
}

// 格式化文件大小
function formatFileSize(bytes?: number): string {
  if (!bytes) return '未知大小'
  const units = ['B', 'KB', 'MB', 'GB']
  let size = bytes
  let unitIndex = 0

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex++
  }

  return `${size.toFixed(1)} ${units[unitIndex]}`
}

// 检查文件是否已缓存
function isFileCached(fileId: number): boolean {
  // 这里可以实现更复杂的缓存检查逻辑
  // 暂时返回 false，需要后续完善
  return false
}

// 阅读图书
function readBook(file: FileRec) {
  const format = file.format?.toLowerCase()
  let routeName = ''

  switch (format) {
    case 'txt':
      routeName = 'reader-txt'
      break
    case 'pdf':
      routeName = 'reader-pdf'
      break
    case 'epub':
      routeName = 'reader-epub'
      break
    default:
      ElMessage.error('不支持的文件格式')
      return
  }

  router.push({ name: routeName, params: { id: file.id } })
}

// 缓存图书
async function cacheBook(file: FileRec) {
  if (!book.value) return

  cachingFiles.value.add(file.id)

  try {
    // 这里需要实现具体的缓存逻辑
    // 包括获取文件内容、章节信息等
    ElMessage.success('缓存功能开发中')
  } catch (err) {
    console.error('Failed to cache book:', err)
    ElMessage.error('缓存失败')
  } finally {
    cachingFiles.value.delete(file.id)
  }
}

// 图片加载错误处理
function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
  const parent = img.parentElement
  if (parent) {
    parent.innerHTML = '<div class="w-full h-full flex items-center justify-center text-gray-400"><span class="material-symbols-outlined text-8xl">book</span></div>'
  }
}

// 组件挂载时加载数据
onMounted(() => {
  loadBook()
})
</script>