<template>
  <el-dialog
    v-model="visible"
    title="添加书籍到书架"
    width="90%"
    top="5vh"
    class="bg-[#f5f7fa]"
    @closed="onClosed"
  >
    <!-- 筛选器 -->
    <BookFilters
      v-model="filters"
      :showShelves="false"
      :showTags="true"
      :showAuthor="true"
      :showReadState="true"
      :showRating="false"
      :showPublisher="false"
      :showPublishedAt="false"
      :showLanguage="false"
      :showSeries="false"
      :showIsbn="false"
      :enableExpand="true"
      @search="searchBooks(1)"
      @reset="resetFilters"
      class="mb-4"
    />

    <!-- 排序控件 -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
      <div class="text-sm text-gray-500">共 {{ meta.total || 0 }} 本书籍</div>
      <SortControl v-model:sort="sort" v-model:order="order" @change="() => searchBooks(1)" />
    </div>

    <!-- 书籍网格 -->
    <div v-if="loading" class="flex justify-center py-8">
      <el-icon class="is-loading" :size="32">
        <component :is="'Loading'" />
      </el-icon>
    </div>
    <el-empty v-else-if="books.length === 0" description="未找到书籍" />
    <div v-else>
      <div
        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4"
      >
        <div
          v-for="book in books"
          :key="book.id"
          class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow"
        >
          <router-link :to="`/books/${book.id}`" class="block relative aspect-[2/3] bg-gray-100">
            <CoverImage
              :file-id="book.cover_file_id || null"
              :alt="book.title"
              class="w-full h-full object-cover"
            />
          </router-link>
          <div class="p-3">
            <router-link
              :to="`/books/${book.id}`"
              class="block font-medium text-sm truncate mb-1 hover:text-blue-600"
            >
              {{ book.title }}
            </router-link>
            <div class="text-xs text-gray-500 truncate mb-2">
              {{ (book.authors || []).map(a => a.name).join(' / ') || '无作者' }}
            </div>
            <el-button
              size="small"
              type="primary"
              :disabled="isBookExcluded(book.id)"
              @click="handleAdd(book)"
              class="w-full"
            >
              {{ isBookExcluded(book.id) ? '已在书架' : '添加到书架' }}
            </el-button>
          </div>
        </div>
      </div>

      <!-- 分页 -->
      <div class="mt-6 flex justify-center">
        <el-pagination
          v-model:current-page="currentPage"
          :page-size="meta.per_page"
          :total="meta.total"
          layout="prev, pager, next, jumper"
          @current-change="searchBooks"
        />
      </div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import BookFilters from '@/components/Book/Filters.vue'
import CoverImage from '@/components/CoverImage.vue'
import SortControl from '@/components/SortControl.vue'
import { booksApi } from '@/api/books'
import type { Book } from '@/api/types'
import { usePagination } from '@/composables/usePagination'
import { useErrorHandler } from '@/composables/useErrorHandler'

const props = defineProps<{
  modelValue: boolean
  excludeBookIds?: number[] // 已在书架中的书籍ID列表
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'add', book: Book): void
}>()

const { handleError } = useErrorHandler()

const visible = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

// 筛选和排序
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
const sort = ref<'modified' | 'created' | 'rating' | 'id'>('created')
const order = ref<'asc' | 'desc'>('desc')

// 分页数据
const {
  data: books,
  loading,
  currentPage,
  lastPage,
  total,
  perPage,
  loadPage,
} = usePagination<Book>({
  fetcher: async (page: number) => {
    const tagParam = filters.value.tagIds.length ? filters.value.tagIds : undefined
    const r = await booksApi.list({
      q: filters.value.q || undefined,
      page,
      authorId: filters.value.authorId || undefined,
      tagId: tagParam,
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
  },
  onError: (e: any) => {
    handleError(e, { context: 'AddBooksDialog.loadBooks' })
  },
})

const meta = computed(() => ({
  current_page: currentPage.value,
  last_page: lastPage.value,
  per_page: perPage.value,
  total: total.value,
}))

function searchBooks(page: number) {
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

function isBookExcluded(bookId: number): boolean {
  return props.excludeBookIds?.includes(bookId) ?? false
}

function handleAdd(book: Book) {
  if (isBookExcluded(book.id)) {
    return
  }
  emit('add', book)
}

function onClosed() {
  // 对话框关闭时重置状态
  resetFilters()
}

// 当对话框打开时加载数据
watch(
  () => props.modelValue,
  v => {
    if (v) {
      loadPage(1)
    }
  },
)
</script>

<style scoped></style>
