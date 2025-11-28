<template>
  <div class="space-y-6">
    <!-- 页面标题 -->
    <div class="flex justify-between items-center">
      <h1 class="text-3xl font-bold text-gray-900">图书列表</h1>
      <div class="flex items-center space-x-4">
        <el-input
          v-model="searchQuery"
          placeholder="搜索图书..."
          class="w-64"
          clearable
          @input="handleSearch"
        >
          <template #prefix>
            <span class="material-symbols-outlined">search</span>
          </template>
        </el-input>
        <el-select v-model="sortBy" class="w-32" @change="handleSort">
          <el-option label="标题" value="title" />
          <el-option label="评分" value="rating" />
          <el-option label="时间" value="created_at" />
        </el-select>
      </div>
    </div>

    <!-- 加载状态 -->
    <div v-if="bookStore.loading" class="flex justify-center py-12">
      <el-icon class="is-loading text-4xl text-blue-500">
        <Loading />
      </el-icon>
    </div>

    <!-- 错误状态 -->
    <div v-else-if="bookStore.error" class="text-center py-12">
      <el-alert
        :title="bookStore.error"
        type="error"
        show-icon
        class="max-w-md mx-auto"
      />
      <el-button @click="loadBooks" class="mt-4" type="primary">
        重试
      </el-button>
    </div>

    <!-- 图书网格 -->
    <div v-else-if="bookStore.hasBooks" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div
        v-for="book in bookStore.books"
        :key="book.id"
        class="card hover:shadow-lg transition-shadow cursor-pointer"
        @click="goToBookDetail(book.id)"
      >
        <!-- 封面 -->
        <div class="aspect-[3/4] bg-gray-100 rounded-lg mb-4 overflow-hidden">
          <img
            v-if="getBookCoverUrl(book)"
            :src="getBookCoverUrl(book)!"
            :alt="book.title"
            class="w-full h-full object-cover"
            @error="handleImageError"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
            <span class="material-symbols-outlined text-6xl">book</span>
          </div>
        </div>

        <!-- 图书信息 -->
        <div class="space-y-2">
          <h3 class="font-semibold text-lg text-gray-900 line-clamp-2" :title="book.title">
            {{ book.title }}
          </h3>

          <p v-if="book.subtitle" class="text-sm text-gray-600 line-clamp-1" :title="book.subtitle">
            {{ book.subtitle }}
          </p>

          <div v-if="book.authors && book.authors.length > 0" class="flex flex-wrap gap-1">
            <span
              v-for="author in book.authors.slice(0, 2)"
              :key="author.id"
              class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"
            >
              {{ author.name }}
            </span>
            <span v-if="book.authors.length > 2" class="text-xs text-gray-500">
              +{{ book.authors.length - 2 }}
            </span>
          </div>

          <div v-if="book.rating" class="flex items-center space-x-1">
            <span class="material-symbols-outlined text-yellow-400 text-sm">star</span>
            <span class="text-sm text-gray-600">{{ book.rating }}/10</span>
          </div>

          <div class="flex justify-between items-center text-sm text-gray-500">
            <span>{{ book.files?.length || 0 }} 个文件</span>
            <span v-if="book.published_at">{{ book.published_at }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 空状态 -->
    <div v-else class="text-center py-12">
      <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">library_books</span>
      <h3 class="text-lg font-medium text-gray-900 mb-2">暂无图书</h3>
      <p class="text-gray-500">请检查网络连接或稍后重试</p>
    </div>

    <!-- 分页 -->
    <div v-if="bookStore.hasBooks && totalPages > 1" class="flex justify-center">
      <el-pagination
        :current-page="currentPage"
        :page-size="bookStore.perPage"
        :total="totalBooks"
        layout="prev, pager, next"
        @current-change="handlePageChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Loading } from '@element-plus/icons-vue'
import { useBookStore } from '@/stores'
import { getBooks, getBookCoverUrl, type BookListParams } from '@/api/books'

const router = useRouter()
const bookStore = useBookStore()

// 搜索和筛选
const searchQuery = ref('')
const sortBy = ref<'title' | 'rating' | 'created_at'>('title')
const perPage = 20

// 计算属性
const totalBooks = computed(() => bookStore.totalBooks)
const totalPages = computed(() => bookStore.lastPage)
const currentPage = computed({
  get: () => bookStore.currentPage,
  set: (value) => {
    // 这里不需要手动设置，pagination会在setBooks时更新
  }
})

// 加载图书列表
async function loadBooks(page = 1) {
  try {
    bookStore.setLoading(true)
    bookStore.setError(null)

    const params: BookListParams = {
      page,
      per_page: perPage,
      sort: sortBy.value,
      order: 'desc',
    }

    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }

    const response = await getBooks(params)
    console.log(response)
    bookStore.setBooks(response.data)
  } catch (error) {
    console.error('Failed to load books:', error)
    bookStore.setError('加载图书失败，请检查网络连接')
  } finally {
    bookStore.setLoading(false)
  }
}

// 搜索处理
function handleSearch() {
  loadBooks(1)
}

// 排序处理
function handleSort() {
  loadBooks(1)
}

// 分页处理
function handlePageChange(page: number) {
  currentPage.value = page
  loadBooks(page)
}

// 跳转到图书详情
function goToBookDetail(bookId: number) {
  router.push(`/books/${bookId}`)
}

// 图片加载错误处理
function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
  const parent = img.parentElement
  if (parent) {
    parent.innerHTML = '<div class="w-full h-full flex items-center justify-center text-gray-400"><span class="material-symbols-outlined text-6xl">book</span></div>'
  }
}

// 组件挂载时加载数据
onMounted(() => {
  loadBooks()
})
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-clamp: 1;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-clamp: 2;
}
</style>