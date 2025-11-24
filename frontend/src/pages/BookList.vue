<template>
  <section class="book-list">
    <div class="container mx-auto px-4 py-4 max-w-7xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">书库</h2>
        <el-button v-permission="'books.view'" type="primary" plain @click="goAdminBooks">
          <span class="material-symbols-outlined mr-1 text-lg">settings</span>
          管理图书
        </el-button>
      </div>

      <BookFilters
        v-model="filters"
        :showShelves="true"
        :showTags="true"
        :showAuthor="true"
        :showReadState="true"
        :showRating="true"
        :enableExpand="true"
        :defaultExpanded="userSettings.preferences?.expandFilterMenu"
        @search="searchPage(1)"
        @reset="resetFilters"
        class="mb-4"
      />

      <!-- 排序控件 -->
      <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
        <SortControl v-model:sort="sort" v-model:order="order" @change="() => searchPage(1)" />
      </div>

      <BookGrid
        :data="data"
        :loading="loading"
        :meta="meta"
        @page-change="searchPage"
        @author-click="filterByAuthor"
        @toggle-read="toggleRead"
      />
    </div>
  </section>
</template>
<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BookFilters from '@/components/Book/Filters.vue'
import BookGrid from '@/components/Book/BookGrid.vue'
import SortControl from '@/components/SortControl.vue'
import { booksApi } from '@/api/books'
import type { Book } from '@/api/types'
import { useSettingsStore } from '@/stores/settings'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePagination } from '@/composables/usePagination'
import { useBookActions } from '@/composables/useBookActions'

const { handleError } = useErrorHandler()
const { toggleReadMark } = useBookActions()

const route = useRoute()
const router = useRouter()

// 统一的筛选模型
const filters = ref({
  q: '',
  authorId: null as number | null,
  tagIds: [] as number[],
  shelfId: null as number | null,
  readState: null as any,
  ratingRange: [0, 5] as [number, number],
  publisher: null as string | null,
  publishedRange: null as [string, string] | null,
  language: null as string | null,
  series_value: null as string | number | null,
  isbn: null as string | null,
})
const err = ref('')
// 作者筛选：单选
const sort = ref<'modified' | 'created' | 'rating' | 'id'>('created')
const order = ref<'asc' | 'desc'>('desc')

// 系统设置
const settingsStore = useSettingsStore()
const userSettings = settingsStore.settings

const { data, loading, currentPage, lastPage, total, perPage, loadPage } = usePagination<Book>({
  fetcher: async (page: number) => {
    try {
      const tagParam = filters.value.tagIds.length ? filters.value.tagIds : undefined
      const r = await booksApi.list({
        q: filters.value.q || undefined,
        page,
        authorId: filters.value.authorId || undefined,
        tagId: tagParam,
        shelfId: filters.value.shelfId || undefined,
        read_state: filters.value.readState || undefined,
        min_rating: filters.value.ratingRange?.[0],
        max_rating: filters.value.ratingRange?.[1],
        publisher: filters.value.publisher || undefined,
        published_from: filters.value.publishedRange?.[0] || undefined,
        published_to: filters.value.publishedRange?.[1] || undefined,
        language: filters.value.language || undefined,
        series_value: filters.value.series_value || undefined,
        isbn: filters.value.isbn || undefined,
        sort: sort.value,
        order: order.value,
      })
      return r
    } catch (e: any) {
      err.value = e?.message || '加载失败'
      handleError(e, { context: 'BookList.fetchBooks', showToast: false })
      throw e
    }
  },
  onError: (e: any) => {
    err.value = e?.message || '加载失败'
  },
})

const meta = computed(() => ({
  current_page: currentPage.value,
  last_page: lastPage.value,
  per_page: perPage.value,
  total: total.value,
}))

function filterByAuthor(id: number) {
  filters.value.authorId = id
  loadPage(1)
}

function searchPage(page: number) {
  loadPage(page)
}

function resetFilters() {
  Object.assign(filters.value, {
    q: '',
    authorId: null,
    tagIds: [],
    shelfId: null,
    readState: null,
    ratingRange: [0, 5] as [number, number],
    publisher: null,
    publishedRange: null,
    language: null,
    series_value: null,
    isbn: null,
  })
  sort.value = 'created'
  order.value = 'desc'
  loadPage(1)
}

// 封面地址由 CoverImage 组件内部处理

async function toggleRead(b: Book) {
  await toggleReadMark(b)
}

function goAdminBooks() {
  router.push({ name: 'admin-book-list' })
}

// 选项数据改由 BookFilters 组件加载

// 从路由 query 初始化筛选（作者/标签）
onMounted(() => {
  const authorIdQ = route.query.author_id
  const tagIdQ = route.query.tag_id
  if (typeof authorIdQ === 'string' && authorIdQ.trim()) {
    const n = Number(authorIdQ)
    if (Number.isFinite(n)) filters.value.authorId = n
  }
  if (typeof tagIdQ === 'string' && tagIdQ.trim()) {
    const n = Number(tagIdQ)
    if (Number.isFinite(n)) filters.value.tagIds = [n]
  }
  // 应用初始化筛选
  loadPage(1)
})
</script>
