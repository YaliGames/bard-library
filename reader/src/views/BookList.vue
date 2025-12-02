<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- 标题栏 -->
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">书库</h1>
      <div class="flex items-center gap-2">
        <button
          @click="showFilterDrawer = true"
          class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          title="筛选"
        >
          <span class="material-symbols-outlined">filter_list</span>
        </button>
      </div>
    </div>

    <!-- Cache notification banner -->
    <div
      v-if="showCacheNotification"
      class="mb-4 bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg border border-blue-100 dark:border-blue-900/50"
    >
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-base">info</span>
        <span class="text-sm font-medium">网络连接失败，正在显示缓存数据</span>
      </div>
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
      :showMarkReadButton="false"
      @search="searchPage(1)"
      class="hidden md:block"
    />

    <BookGrid
      :data="data"
      :loading="loading"
      :meta="meta"
      @page-change="searchPage"
      @author-click="filterByAuthor"
      @toggle-read="toggleRead"
    />

    <!-- 移动端筛选弹窗 -->
    <BookFilterDrawer
      v-model="showFilterDrawer"
      :filters="filters"
      :sort="sort"
      :order="order"
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
      @update:filters="filters = $event"
      @update:sort="sort = $event"
      @update:order="order = $event"
      @search="searchPage(1)"
      @reset="resetFilters"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import BookGrid from '@/components/Book/BookGrid.vue'
import BookFilter, { type FiltersModel } from '@/components/Book/BookFilter.vue'
import BookFilterDrawer from '@/components/Book/BookFilterDrawer.vue'
import { booksApi } from '@/api/books'
import type { Book } from '@/api/types'
import { usePagination } from '@/composables/usePagination'
import { ElMessage } from 'element-plus'
import { isUsingCachedData, resetCachedDataFlag } from '@/utils/offline'
import { useOfflineStore } from '@/stores/offline'
import {
  enableShelfFilter,
  enableTagFilter,
  enableAuthorFilter,
  enableReadStateFilter,
  enableRatingFilter,
  enableAdvancedFilters,
} from '@/config'

const route = useRoute()
const offlineStore = useOfflineStore()

const showCacheNotification = ref(false)
const showFilterDrawer = ref(false)

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
const err = ref('')
const sort = ref<'modified' | 'created' | 'rating' | 'id'>('created')
const order = ref<'asc' | 'desc'>('desc')

const { data, loading, currentPage, lastPage, total, perPage, loadPage } = usePagination<Book>({
  fetcher: async (page: number) => {
    try {
      // Check if we have filters applied
      const hasFilters = !!(
        filters.value.q ||
        filters.value.authorId ||
        filters.value.tagIds.length > 0 ||
        filters.value.shelfId ||
        filters.value.readState ||
        (filters.value.ratingRange[0] !== 0 || filters.value.ratingRange[1] !== 5)
      )

      // If we have filters and either:
      // 1. Already showing cache notification (previous load used cache), OR
      // 2. We're offline
      // Then use local filtering instead of API
      if (hasFilters && (showCacheNotification.value || offlineStore.isOffline)) {
        try {
          const { getAllCachedBooks, filterBooksLocally } = await import('@/utils/bookCache')
          const allBooks = await getAllCachedBooks()
          
          // If we have cached books, use them
          if (allBooks.length > 0) {
            const filtered = filterBooksLocally(allBooks, {
              q: filters.value.q,
              authorId: filters.value.authorId,
              tagIds: filters.value.tagIds,
              shelfId: filters.value.shelfId,
              readState: filters.value.readState,
              ratingRange: filters.value.ratingRange,
            })

            // Sort locally
            filtered.sort((a, b) => {
              let cmp = 0
              switch (sort.value) {
                case 'created':
                  cmp = (a.created_at || '').localeCompare(b.created_at || '')
                  break
                case 'modified':
                  cmp = (a.updated_at || '').localeCompare(b.updated_at || '')
                  break
                case 'rating':
                  cmp = (a.rating || 0) - (b.rating || 0)
                  break
                case 'id':
                  cmp = a.id - b.id
                  break
              }
              return order.value === 'desc' ? -cmp : cmp
            })

            // Paginate locally
            const pageSize = 20
            const start = (page - 1) * pageSize
            const end = start + pageSize
            const paginatedData = filtered.slice(start, end)

            return {
              data: paginatedData,
              current_page: page,
              last_page: Math.ceil(filtered.length / pageSize),
              per_page: pageSize,
              total: filtered.length,
              meta: {
                current_page: page,
                last_page: Math.ceil(filtered.length / pageSize),
                per_page: pageSize,
                total: filtered.length,
              },
            }
          }
        } catch (e) {
          console.warn('Failed to use local filtering, falling back to API:', e)
        }
      }

      // Otherwise, use API
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
      throw e
    }
  },
  onError: (e: any) => {
    err.value = e?.message || '加载失败'
    ElMessage.error(err.value)
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
  // Don't reset cache notification when applying filters
  // Only reset when changing pages without filters
  const hasFilters = !!(
    filters.value.q ||
    filters.value.authorId ||
    filters.value.tagIds.length > 0 ||
    filters.value.shelfId ||
    filters.value.readState ||
    (filters.value.ratingRange[0] !== 0 || filters.value.ratingRange[1] !== 5)
  )
  
  if (!hasFilters) {
    resetCachedDataFlag()
    showCacheNotification.value = false
  }
  loadPage(page)
}

// Watch for loading completion to check if cached data was used
watch(loading, (isLoading) => {
  if (!isLoading) {
    showCacheNotification.value = isUsingCachedData()
  }
})

async function toggleRead(b: Book) {
  try {
    await booksApi.markRead(b.id, !b.is_read_mark)
    b.is_read_mark = !b.is_read_mark
    ElMessage.success(b.is_read_mark ? '已标记为已读' : '已取消已读标记')
  } catch (e: any) {
    ElMessage.error(e?.message || '操作失败')
  }
}

onMounted(() => {
  const authorIdQ = route.query.author_id
  if (typeof authorIdQ === 'string' && authorIdQ.trim()) {
    const n = Number(authorIdQ)
    if (Number.isFinite(n)) filters.value.authorId = n
  }
  loadPage(1)
})
</script>
