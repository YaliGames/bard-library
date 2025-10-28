<template>
  <section class="book-list">
    <div class="container mx-auto px-4 py-4 max-w-7xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">书架 · {{ shelf?.name || ('#' + shelfId) }}</h2>
        <div class="flex items-center">
          <el-button @click="back">
            <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span> 返回
          </el-button>
        </div>
      </div>

      <!-- 书架信息 -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <el-skeleton animated :loading="shelfLoading">
          <template #template>
            <el-skeleton-item variant="text" class="w-[40%] h-[18px]" />
            <el-skeleton-item variant="text" class="w-[70%] h-[14px]" />
            <div class="flex items-center justify-between mt-2">
              <el-skeleton-item variant="text" class="w-[50%] h-[18px]" />
            </div>
          </template>
          <div class="text-gray-700 text-sm leading-6">
            <div class="mb-1">
              <span class="text-gray-500">名称：</span>
              <span class="font-medium">{{ shelf?.name || '未命名书架' }}</span>
            </div>
            <div class="mb-1">
              <span class="text-gray-500">简介：</span>
              <span>{{ shelf?.description || '暂无简介' }}</span>
            </div>
            <div class="mt-2 text-gray-400">更多信息区域 · 占位</div>
          </div>
        </el-skeleton>
      </div>

      <template v-if="false">
        <BookFilters v-model="filters" :showShelves="false" :showTags="true" :showAuthor="true"
          :showReadState="true" :showRating="true" :enableExpand="true"
          :defaultExpanded="userSettings.preferences?.expandFilterMenu" @search="searchPage(1)"
          @reset="resetFilters" class="mb-4" />
      </template>

      <!-- 排序控件（与书库页一致） -->
      <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
        <div class="flex flex-row items-center">
          <el-select v-model="sort" placeholder="选择排序" @change="() => searchPage(1)" class="min-w-[150px]">
            <el-option label="创建时间" value="created" />
            <el-option label="修改时间" value="modified" />
            <el-option label="评分" value="rating" />
            <el-option label="ID" value="id" />
          </el-select>
        </div>
        <el-button @click="toggleOrder">
          <span class="material-symbols-outlined" v-if="sort == 'created'">
            {{ order === 'desc' ? 'clock_arrow_down' : 'clock_arrow_up' }}
          </span>
          <span class="material-symbols-outlined" v-else-if="sort == 'modified'">
            {{ order === 'desc' ? 'edit_arrow_down' : 'edit_arrow_up' }}
          </span>
          <span class="material-symbols-outlined" v-else>
            {{ order === 'desc' ? 'arrow_downward' : 'arrow_upward' }}
          </span>
        </el-button>
      </div>

      <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <div v-for="i in skeletonCount" :key="i" class="bg-white rounded-lg shadow-sm p-4">
          <el-skeleton animated :loading="true">
            <template #template>
              <div class="flex flex-col gap-2">
                <div class="w-full aspect-[3/4] flex">
                  <el-skeleton-item variant="image" class="w-full h-full rounded" />
                </div>
                <el-skeleton-item variant="text" class="w-[70%] h-[18px] mt-1.5" />
                <el-skeleton-item variant="text" class="w-[50%] h-[14px]" />
                <div class="flex items-center justify-between mt-2">
                  <el-skeleton-item variant="text" class="w-[120px] h-[18px]" />
                </div>
              </div>
            </template>
          </el-skeleton>
        </div>
      </div>
      <template v-else>
        <div v-if="data.length > 0"
          class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
          <div class="bg-white rounded-lg shadow-sm p-4" v-for="b in data" :key="b.id">
            <div class="flex flex-col gap-1.5">
              <router-link :to="`/books/${b.id}`">
                <CoverImage :file-id="b.cover_file_id || null" :title="b.title" :authors="(b.authors || []).map(a => a.name)" class="rounded">
                  <template #overlay v-if="userSettings.bookList?.showReadTag">
                    <el-tag v-if="b.is_read_mark" type="success" effect="dark" size="small">已读</el-tag>
                    <el-tag v-else-if="b.is_reading" type="warning" effect="dark" size="small">正在阅读</el-tag>
                  </template>
                </CoverImage>
                <div class="font-semibold mt-2">{{ b.title || ('#' + b.id) }}</div>
              </router-link>
              <div class="text-gray-600 text-sm flex flex-wrap gap-1">
                <template v-for="(a, idx) in (b.authors || [])" :key="a.id">
                  <div class="cursor-pointer text-primary" @click="filterByAuthor(a.id)">
                    {{ a.name }}
                  </div>
                  <span v-if="idx < (((b?.authors ?? []).length) - 1)"> / </span>
                </template>
                <div class="text-gray-500" v-if="(b.authors || []).length === 0">暂无作者</div>
              </div>
              <div class="flex items-center justify-between gap-2">
                <el-rate v-model="b.rating" :max="5" allow-half disabled show-score score-template="{value}" />
                <template v-if="userSettings.bookList?.showMarkReadButton && isLoggedIn">
                  <el-tooltip :content="b.is_read_mark ? '取消已读' : '标为已读'" placement="top">
                    <el-button size="small" :type="b.is_read_mark ? 'success' : 'default'" @click="toggleRead(b)" circle>
                      <span class="material-symbols-outlined">done_all</span>
                    </el-button>
                  </el-tooltip>
                </template>
              </div>
            </div>
          </div>
        </div>
        <el-empty description="暂无书籍" v-else />

        <div v-if="meta" class="mt-3 flex justify-center">
          <el-pagination background layout="prev, pager, next, jumper" :total="meta.total" :page-size="meta.per_page"
            :current-page="meta.current_page" @current-change="searchPage" />
        </div>
      </template>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import CoverImage from '@/components/CoverImage.vue'
import BookFilters from '@/components/BookFilters.vue'
import { booksApi } from '@/api/books'
import type { Book, Shelf } from '@/api/types'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import { shelvesApi } from '@/api/shelves'

const data = ref<Book[]>([])
const meta = ref<{ current_page: number; last_page: number; per_page: number; total: number } | null>(null)
const loading = ref(true)
const shelfLoading = ref(true)
const router = useRouter()
const route = useRoute()
const shelfId = computed(() => Number(route.params.id))
const shelf = ref<(Shelf & { description?: string }) | null>(null)

// 统一的筛选模型（固定 shelfId）
const filters = ref({
  q: '',
  authorId: null as number | null,
  tagIds: [] as number[],
  shelfId: shelfId.value as number | null,
  readState: null as any,
  ratingRange: [0, 5] as [number, number],
  publisher: null as string | null,
  publishedRange: null as [string, string] | null,
  language: null as string | null,
  series_value: null as string | number | null,
  isbn: null as string | null,
})

const err = ref('')
const sort = ref<'modified' | 'created' | 'rating' | 'id'>('created')
const order = ref<'asc' | 'desc'>('desc')
const { loggedIn } = useAuthStore()
const isLoggedIn = loggedIn
const skeletonCount = computed(() => Math.max(1, Number(meta.value?.per_page || 12)))
const { state: userSettings } = useSettingsStore()

function back() { router.back() }

function filterByAuthor(id: number) {
  filters.value.authorId = id
  searchPage(1)
}

async function fetchShelfInfo(){
  shelfLoading.value = true
  try {
    const list = await shelvesApi.listAll()
    shelf.value = list.find(s => s.id === shelfId.value) || { id: shelfId.value, name: `#${shelfId.value}` }
  } catch {
    shelf.value = { id: shelfId.value, name: `#${shelfId.value}` }
  } finally {
    shelfLoading.value = false
  }
}

async function fetchBooks(page = 1) {
  loading.value = true
  err.value = ''
  try {
    const tagParam = filters.value.tagIds.length ? filters.value.tagIds : undefined
    const r = await booksApi.list({
      q: filters.value.q || undefined,
      page,
      author_id: filters.value.authorId || undefined,
      tag_id: tagParam,
      shelf_id: shelfId.value || undefined,
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
    data.value = r.data
    meta.value = r.meta
  } catch (e: any) {
    err.value = e?.message || '加载失败'
  } finally {
    loading.value = false
  }
}

function searchPage(page: number) { fetchBooks(page) }
function toggleOrder(){ order.value = order.value === 'desc' ? 'asc' : 'desc'; searchPage(1) }
function resetFilters() {
  Object.assign(filters.value, { q: '', authorId: null, tagIds: [], readState: null, ratingRange: [0, 5] as [number, number], publisher: null, publishedRange: null, language: null, series_value: null, isbn: null })
  // 保持 shelfId 固定
  filters.value.shelfId = shelfId.value
  sort.value = 'created'
  order.value = 'desc'
  searchPage(1)
}

async function toggleRead(b: Book) {
  const target = !(b as any).is_read_mark || (b as any).is_read_mark === 0 ? true : false
  try {
    await booksApi.markRead(b.id, target)
    ;(b as any).is_read_mark = target ? 1 : 0
    ElMessage.success(target ? '已标记为已读' : '已取消已读')
  } catch (e: any) {
    ElMessage.error(e?.message || '操作失败')
  }
}

// 路由变化时，更新 shelfId 并重新加载
watch(() => route.params.id, () => {
  filters.value.shelfId = shelfId.value
  fetchShelfInfo()
  fetchBooks(1)
})

onMounted(() => {
  filters.value.shelfId = shelfId.value
  fetchShelfInfo()
  fetchBooks(1)
})
</script>

<style scoped>
</style>
