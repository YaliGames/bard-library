<template>
  <section class="book-list h-full overflow-auto">
    <div class="container mx-auto px-4 py-4 max-w-7xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">书库</h2>
      </div>

      <!-- Search bar -->
      <div class="mb-4">
        <el-input
          v-model="filters.q"
          placeholder="搜索书名、作者..."
          clearable
          @keyup.enter="searchPage(1)"
        >
          <template #append>
            <el-button @click="searchPage(1)">搜索</el-button>
          </template>
        </el-input>
      </div>

      <!-- Sort control -->
      <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
        <el-select v-model="sort" @change="searchPage(1)" placeholder="排序方式">
          <el-option label="创建时间" value="created" />
          <el-option label="修改时间" value="modified" />
          <el-option label="评分" value="rating" />
        </el-select>
        <el-select v-model="order" @change="searchPage(1)" placeholder="排序顺序">
          <el-option label="降序" value="desc" />
          <el-option label="升序" value="asc" />
        </el-select>
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
import { useRoute } from 'vue-router'
import BookGrid from '@/components/Book/BookGrid.vue'
import { booksApi } from '@/api/books'
import type { Book } from '@/api/types'
import { usePagination } from '@/composables/usePagination'
import { ElMessage } from 'element-plus'

const route = useRoute()

const filters = ref({
  q: '',
  authorId: null as number | null,
})
const err = ref('')
const sort = ref<'modified' | 'created' | 'rating' | 'id'>('created')
const order = ref<'asc' | 'desc'>('desc')

const { data, loading, currentPage, lastPage, total, perPage, loadPage } = usePagination<Book>({
  fetcher: async (page: number) => {
    try {
      const r = await booksApi.list({
        q: filters.value.q || undefined,
        page,
        authorId: filters.value.authorId || undefined,
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
  loadPage(page)
}

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
